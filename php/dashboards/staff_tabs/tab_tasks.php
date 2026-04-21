<?php
/**
 * tab_tasks.php — Module 2: My Tasks (Modernized)
 */
if ($staff_id) {
    dbExecute($conn,"UPDATE staff_tasks SET status='overdue' WHERE assigned_to=? AND status IN('pending','in progress') AND due_date < CURDATE()","i",[$staff_id]);
    dbExecute($conn,"UPDATE staff_tasks SET status='overdue' WHERE assigned_to=? AND status IN('pending','in progress') AND due_date = CURDATE() AND due_time < TIME(NOW()) AND due_time IS NOT NULL","i",[$staff_id]);
}

$tasks = dbSelect($conn,"SELECT * FROM staff_tasks WHERE assigned_to=? ORDER BY FIELD(status,'overdue','pending','in progress','completed','cancelled'), priority DESC, due_date ASC, due_time ASC","i",[$staff_id]);
?>
<div id="sec-tasks" class="dash-section">

    <!-- Header & Orchestration -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3rem;flex-wrap:wrap;gap:2rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-clipboard-check" style="color:var(--role-accent);"></i> Task Orchestration</h2>
            <p style="font-size:1.35rem;color:var(--text-muted);margin:0.5rem 0 0;">Manage your assigned operations and workflows</p>
        </div>
        <div style="display:flex;gap:1.2rem;flex-wrap:wrap;align-items:center;">
             <div class="filter-group">
                <button class="filter-btn active" onclick="filterByStatus('all', this)">All</button>
                <button class="filter-btn" onclick="filterByStatus('pending', this)">Pending</button>
                <button class="filter-btn" onclick="filterByStatus('in progress', this)">Active</button>
                <button class="filter-btn" onclick="filterByStatus('overdue', this)">Overdue</button>
                <button class="filter-btn" onclick="filterByStatus('completed', this)">History</button>
            </div>
            <select id="filterPriority" class="form-control" style="width:160px; height:45px; border-radius:12px;" onchange="filterTasks()">
                <option value="all">Any Priority</option>
                <option value="urgent">Urgent Only</option>
                <option value="high">High priority</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
        </div>
    </div>

    <?php if (empty($tasks)): ?>
    <div class="card" style="text-align:center;padding:8rem 2rem; background:rgba(255,255,255,0.02); border:2px dashed var(--border);">
        <div style="width:100px; height:100px; background:var(--surface-2); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 2.5rem;">
            <i class="fas fa-clipboard-check" style="font-size:4rem;color:var(--text-muted); opacity:.4;"></i>
        </div>
        <h3 style="font-size:2.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:1rem;">Clear Horizon</h3>
        <p style="font-size:1.4rem; color:var(--text-muted); max-width:400px; margin:0 auto;">Your mission queue is currently empty. New assignments will appear here instantly.</p>
    </div>
    <?php else: ?>

    <div id="taskGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(400px,1fr));gap:2.5rem;">
    <?php foreach ($tasks as $t):
        $s   = $t['status'] ?? 'pending';
        $pri = strtolower($t['priority'] ?? 'medium');
        $is_overdue = ($s === 'overdue');
        $cat_icons  = ['cleaning'=>'fa-broom','laundry'=>'fa-tshirt','maintenance'=>'fa-tools','transport'=>'fa-ambulance','security'=>'fa-shield-alt','kitchen'=>'fa-utensils','general'=>'fa-tasks'];
        $ico = $cat_icons[$t['task_category']??''] ?? 'fa-tasks';
        $pri_colors = ['urgent'=>'#EB5757','high'=>'#F2994A','medium'=>'#F2C94C','low'=>'#27AE60'];
        $clr = $pri_colors[$pri] ?? 'var(--role-accent)';

        $checklists = dbSelect($conn,"SELECT * FROM staff_task_checklists WHERE task_id=? ORDER BY id ASC","i",[$t['task_id']]);
        $checked_count = count(array_filter($checklists, fn($c) => $c['is_completed']));
        $total_checks  = count($checklists);
        $prog = $total_checks ? round(($checked_count/$total_checks)*100) : 0;
    ?>
    <div class="task-card-v2" 
         data-status="<?= e($s) ?>" 
         data-priority="<?= e($pri) ?>"
         style="--accent-clr:<?= e($clr) ?>;">
        
        <div class="task-head">
            <div class="task-icon-wrap">
                <i class="fas <?= e($ico) ?>"></i>
            </div>
            <div style="flex:1; min-width:0;">
                <h4 class="task-title" title="<?= e($t['task_title']) ?>"><?= e($t['task_title']) ?></h4>
                <div style="display:flex; align-items:center; gap:1rem; margin-top:.3rem;">
                    <span class="pri-pill"><i class="fas fa-flag"></i> <?= ucfirst($pri) ?></span>
                    <?php if(!empty($t['due_date'])): ?>
                    <span class="due-text <?= $is_overdue?'v-overdue':'' ?>">
                        <i class="far fa-clock"></i> <?= date('d M, H:i', strtotime($t['due_date'].' '.($t['due_time']??'23:59'))) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="status-pill-v2 <?= str_replace(' ','-',$s) ?>"><?= ucwords($s) ?></div>
        </div>

        <div class="task-desc">
            <?= e(mb_strimwidth($t['task_description']??'', 0, 120, '...')) ?>
        </div>

        <?php if(!empty($t['location'])): ?>
        <div class="task-loc"><i class="fas fa-map-marker-alt"></i> <?= e($t['location']) ?></div>
        <?php endif; ?>

        <?php if ($total_checks > 0): ?>
        <div class="task-progress">
             <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.8rem;">
                <span style="font-size:1.15rem; font-weight:700; color:var(--text-secondary);"><i class="fas fa-list-ul"></i> Objectives</span>
                <span style="font-size:1.15rem; font-weight:800; color:<?= $prog==100?'#27AE60':'var(--text-primary)' ?>;"><?= $checked_count ?>/<?= $total_checks ?> (<?= $prog ?>%)</span>
            </div>
            <div class="prog-track"><div class="prog-fill" style="width:<?= $prog ?>%; background:<?= $prog==100?'#27AE60':$clr ?>;"></div></div>
            
            <?php if ($s !== 'completed'): ?>
            <div class="task-checklist">
                <?php foreach ($checklists as $chk): ?>
                <label class="check-item">
                    <input type="checkbox" onchange="toggleChecklist(<?= $chk['checklist_id'] ?>,<?= $t['task_id'] ?>,this.checked)" <?= $chk['is_completed']?'checked':'' ?>>
                    <span class="check-box"></span>
                    <span class="check-lbl"><?= e($chk['checklist_item']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="task-actions">
            <?php if ($s === 'pending' || $s === 'overdue'): ?>
            <button class="btn btn-primary btn-wide" onclick="startTask(<?= $t['task_id'] ?>, this)">
                <span class="btn-text"><i class="fas fa-play-circle"></i> Initialize Task</span>
            </button>
            <?php elseif ($s === 'in progress'): ?>
            <button class="btn btn-success btn-wide" onclick="openCompleteModal(<?= $t['task_id'] ?>)">
                <span class="btn-text"><i class="fas fa-check-circle"></i> Finalize Mission</span>
            </button>
            <?php elseif ($s === 'completed'): ?>
            <div class="completed-msg">
                <i class="fas fa-medal"></i> Mission Accomplished <span><?= $t['completed_at'] ? date('d M, H:i',strtotime($t['completed_at'])) : '' ?></span>
            </div>
            <?php else: ?>
            <div class="cancelled-msg">Mission Aborted / Cancelled</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.filter-group { display:flex; background:var(--surface-2); padding:.4rem; border-radius:14px; border:1.5px solid var(--border); }
.filter-btn { border:none; background:none; padding:.7rem 1.6rem; border-radius:10px; font-size:1.2rem; font-weight:700; color:var(--text-secondary); cursor:pointer; transition:.2s; }
.filter-btn.active { background:var(--role-accent); color:#fff; box-shadow:0 4px 12px color-mix(in srgb, var(--role-accent) 25%, transparent); }

.task-card-v2 { background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:2.5rem; position:relative; transition:all .3s cubic-bezier(0.17, 0.67, 0.83, 0.67); overflow:hidden; display:flex; flex-direction:column; box-shadow:var(--shadow-sm); }
.task-card-v2::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:var(--accent-clr); opacity:.8; }
.task-card-v2:hover { transform:translateY(-5px); box-shadow:var(--shadow-md); border-color:color-mix(in srgb, var(--accent-clr) 30%, var(--border)); }

.task-head { display:flex; gap:1.8rem; align-items:flex-start; margin-bottom:1.8rem; }
.task-icon-wrap { width:52px; height:52px; border-radius:14px; background:color-mix(in srgb, var(--accent-clr) 12%, transparent 88%); display:flex; align-items:center; justify-content:center; font-size:2.2rem; color:var(--accent-clr); flex-shrink:0; }
.task-title { font-size:1.7rem; font-weight:800; margin:0; line-height:1.2; color:var(--text-primary); }

.pri-pill { font-size:1.05rem; font-weight:800; text-transform:uppercase; color:var(--accent-clr); background:color-mix(in srgb, var(--accent-clr) 10%, transparent); padding:.2rem .8rem; border-radius:20px; }
.due-text { font-size:1.15rem; font-weight:600; color:var(--text-muted); }
.due-text.v-overdue { color:#EB5757; }

.status-pill-v2 { font-size:1.05rem; font-weight:800; padding:.3rem .9rem; border-radius:20px; white-space:nowrap; }
.status-pill-v2.pending { background:#F2C94C22; color:#D4A017; }
.status-pill-v2.in-progress { background:var(--role-accent)22; color:var(--role-accent); animation: pulse-soft 2s infinite; }
.status-pill-v2.overdue { background:#EB575722; color:#EB5757; }
.status-pill-v2.completed { background:#27AE6022; color:#27AE60; }

.task-desc { font-size:1.35rem; color:var(--text-secondary); line-height:1.6; margin-bottom:1.5rem; flex:1; }
.task-loc { font-size:1.2rem; color:var(--text-muted); font-weight:600; display:flex; align-items:center; gap:.5rem; margin-bottom:1.8rem; }

.task-progress { background:var(--surface-2); padding:1.4rem; border-radius:16px; margin-bottom:2rem; }
.prog-track { height:8px; background:rgba(0,0,0,0.05); border-radius:4px; overflow:hidden; }
.prog-fill { height:100%; border-radius:4px; transition:width .6s cubic-bezier(0.17, 0.67, 0.83, 0.67); }

.task-checklist { margin-top:1.5rem; display:flex; flex-direction:column; gap:.8rem; }
.check-item { display:flex; align-items:center; gap:1.2rem; cursor:pointer; position:relative; }
.check-item input { display:none; }
.check-box { width:20px; height:20px; border:2px solid var(--border); border-radius:6px; background:var(--surface); flex-shrink:0; position:relative; transition:.2s; }
.check-item input:checked + .check-box { background:var(--role-accent); border-color:var(--role-accent); }
.check-item input:checked + .check-box::after { content:'\f00c'; font-family:'Font Awesome 5 Free'; font-weight:900; position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem; }
.check-lbl { font-size:1.3rem; font-weight:500; color:var(--text-primary); transition:.2s; }
.check-item input:checked ~ .check-lbl { text-decoration:line-through; opacity:.5; }

.completed-msg { background:rgba(39,174,96,0.08); color:#27AE60; padding:1.2rem; border-radius:12px; font-weight:800; font-size:1.25rem; display:flex; align-items:center; gap:.8rem; }
.completed-msg span { margin-left:auto; font-size:1.1rem; opacity:.7; }
.cancelled-msg { background:var(--surface-2); color:var(--text-muted); padding:1.2rem; border-radius:12px; text-align:center; font-weight:700; font-size:1.3rem; }
</style>

<script>
function filterByStatus(st, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.task-card-v2').forEach(c => {
        c.style.display = (st === 'all' || c.dataset.status === st) ? 'flex' : 'none';
    });
}
function filterTasks() {
    const pri = document.getElementById('filterPriority').value;
    document.querySelectorAll('.task-card-v2').forEach(c => {
        c.style.display = (pri === 'all' || c.dataset.priority === pri) ? 'flex' : 'none';
    });
}
async function startTask(taskId, btn) {
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initializing...';
    btn.disabled = true;
    const res = await doAction({action:'update_task_status', task_id:taskId, status:'in progress'}, 'Mission parameters initialized!');
    if(res) setTimeout(()=>location.reload(), 1000);
    else { btn.innerHTML = original; btn.disabled = false; }
}
function openCompleteModal(taskId) {
    const hid = document.getElementById('comp_task_id');
    if(hid) hid.value = taskId;
    openModal('completeTaskModal');
}
async function submitCompleteTask() {
    const btn = document.getElementById('btnCompleteSubmit');
    const original = btn.innerHTML;
    btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Finalizing...'; btn.disabled=true;
    const fd = new FormData(document.getElementById('frmCompleteTask'));
    const res = await doAction(fd, 'Mission objectives successfully verified and logged.');
    if(res) { closeModal('completeTaskModal'); setTimeout(()=>location.reload(), 1200); }
    else { btn.innerHTML = original; btn.disabled = false; }
}
async function toggleChecklist(chkId, taskId, isChecked) {
    await doAction({action:'complete_task_checklist', checklist_id:chkId, task_id:taskId, state:isChecked?1:0});
}
</script>
