<?php
/**
 * tab_tasks.php — Module 2: My Tasks (All Roles, Role-Filtered)
 */

// Update overdue automatically
if ($staff_id) {
    dbExecute($conn,"UPDATE staff_tasks SET status='overdue' WHERE assigned_to=? AND status IN('pending','in progress') AND due_date < CURDATE()","i",[$staff_id]);
    dbExecute($conn,"UPDATE staff_tasks SET status='overdue' WHERE assigned_to=? AND status IN('pending','in progress') AND due_date = CURDATE() AND due_time < TIME(NOW()) AND due_time IS NOT NULL","i",[$staff_id]);
}

$filter_status   = $_GET['task_status'] ?? 'all';
$filter_priority = $_GET['task_priority'] ?? 'all';

$tasks = dbSelect($conn,"SELECT * FROM staff_tasks WHERE assigned_to=? ORDER BY FIELD(status,'overdue','pending','in progress','completed','cancelled'), priority DESC, due_date ASC, due_time ASC","i",[$staff_id]);
?>
<div id="sec-tasks" class="dash-section">

    <!-- Header + Filters -->
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1.5rem;margin-bottom:2.5rem;">
        <h2 style="font-size:2.2rem;font-weight:700;"><i class="fas fa-clipboard-list" style="color:var(--role-accent);"></i> My Tasks</h2>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
            <select id="filterPriority" class="form-control" style="width:auto;" onchange="filterTasks()">
                <option value="all">All Priorities</option>
                <option value="urgent">Urgent</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
            <div class="filter-tabs" id="taskFilterTabs">
                <?php foreach(['all','pending','in progress','in_progress','overdue','completed','cancelled'] as $st): ?>
                <?php if(in_array($st,['in_progress'])) continue; ?>
                <button class="btn btn-primary ftab <?= ($st==='all')?'active':'' ?>" onclick="filterByStatus('<?= e($st) ?>')"><span class="btn-text"><?= ucfirst($st) ?></span></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if (empty($tasks)): ?>
    <div class="card" style="text-align:center;padding:6rem 2rem;">
        <i class="fas fa-clipboard-check" style="font-size:5rem;color:var(--text-muted);display:block;margin-bottom:1.5rem;"></i>
        <h3 style="font-size:1.8rem;color:var(--text-secondary);margin-bottom:.5rem;">No Tasks Assigned</h3>
        <p style="color:var(--text-muted);">Your task queue is empty. Tasks assigned by admin appear here instantly.</p>
    </div>
    <?php else: ?>

    <div id="taskGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:2rem;">
    <?php foreach ($tasks as $t):
        $s   = $t['status'] ?? 'pending';
        $pri = strtolower($t['priority'] ?? 'medium');
        $is_overdue = ($s === 'overdue');
        $cat_icons  = ['cleaning'=>'fa-broom','laundry'=>'fa-tshirt','maintenance'=>'fa-tools','transport'=>'fa-ambulance','security'=>'fa-shield-alt','kitchen'=>'fa-utensils','general'=>'fa-tasks'];
        $ico = $cat_icons[$t['task_category']??''] ?? 'fa-tasks';
        $pri_top_colors = ['urgent'=>'#E74C3C','high'=>'#E67E22','medium'=>'#F39C12','low'=>'#27AE60'];
        $top_color = $pri_top_colors[$pri] ?? '#ccc';

        // Checklist items
        $checklists = dbSelect($conn,"SELECT * FROM staff_task_checklists WHERE task_id=? ORDER BY id ASC","i",[$t['task_id']]);
        $checked_count = count(array_filter($checklists, fn($c) => $c['is_completed']));
        $total_checks  = count($checklists);
    ?>
    <div class="task-card <?= $is_overdue?'overdue':'' ?>"
         data-status="<?= e($s) ?>"
         data-priority="<?= e($pri) ?>"
         style="border-top:4px solid <?= e($top_color) ?>;">

        <!-- Task Header -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.2rem;">
            <div style="display:flex;align-items:center;gap:1rem;flex:1;min-width:0;">
                <div style="width:38px;height:38px;border-radius:10px;background:color-mix(in srgb,<?= e($top_color) ?> 15%,#fff 85%);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas <?= e($ico) ?>" style="font-size:1.5rem;color:<?= e($top_color) ?>;"></i>
                </div>
                <h4 style="font-size:1.5rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;" title="<?= e($t['task_title']) ?>">
                    <?= e($t['task_title']) ?>
                </h4>
            </div>
            <span class="badge badge-<?= str_replace(' ','-',$s) ?>">
                <?= ucwords($s) ?>
            </span>
        </div>

        <p style="font-size:1.3rem;color:var(--text-secondary);margin-bottom:1.2rem;line-height:1.5;"><?= e(mb_strimwidth($t['task_description']??'',0,100,'…')) ?></p>

        <!-- Meta Info -->
        <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:1.4rem;font-size:1.2rem;color:var(--text-muted);">
            <?php if(!empty($t['location'])): ?><span><i class="fas fa-map-marker-alt"></i> <?= e($t['location']) ?></span><?php endif; ?>
            <?php if(!empty($t['due_date'])): ?><span><i class="far fa-calendar-alt"></i> <?= date('d M Y',strtotime($t['due_date'])) ?><?= !empty($t['due_time'])?' '.date('H:i',strtotime($t['due_time'])):'' ?></span><?php endif; ?>
            <span class="badge badge-<?= e($pri) ?>" style="font-size:1rem;"><i class="fas fa-flag"></i> <?= ucfirst($pri) ?></span>
        </div>

        <!-- Checklist Progress -->
        <?php if ($total_checks > 0): ?>
        <div style="margin-bottom:1.4rem;">
            <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;font-size:1.2rem;color:var(--text-secondary);">
                <span><i class="fas fa-list-check"></i> Checklist</span>
                <span><?= $checked_count ?>/<?= $total_checks ?></span>
            </div>
            <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
                <div style="height:100%;width:<?= $total_checks?round(($checked_count/$total_checks)*100):0 ?>%;background:var(--success);border-radius:3px;transition:width .3s;"></div>
            </div>
            <?php if ($s !== 'completed'): ?>
            <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.7rem;" id="chk-<?= $t['task_id'] ?>">
                <?php foreach ($checklists as $chk): ?>
                <label style="display:flex;align-items:center;gap:1rem;font-size:1.3rem;cursor:pointer;">
                    <input type="checkbox"
                        style="width:16px;height:16px;accent-color:var(--role-accent);"
                        onchange="toggleChecklist(<?= $chk['checklist_id'] ?>,<?= $t['task_id'] ?>,this.checked)"
                        <?= $chk['is_completed']?'checked':'' ?>
                        <?= $s==='completed'?'disabled':'' ?>>
                    <span style="<?= $chk['is_completed']?'text-decoration:line-through;color:var(--text-muted);':'' ?>"><?= e($chk['checklist_item']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <?php if ($s === 'pending' || $s === 'overdue'): ?>
        <button class="btn btn-primary" style="width:100%;" onclick="startTask(<?= $t['task_id'] ?>)"><span class="btn-text">
            <i class="fas fa-play"></i> Start Task
        </span></button>
        <?php elseif ($s === 'in progress'): ?>
        <button class="btn btn-success" style="width:100%;" onclick="openCompleteModal(<?= $t['task_id'] ?>)"><span class="btn-text">
            <i class="fas fa-check"></i> Mark Complete
        </span></button>
        <?php elseif ($s === 'completed'): ?>
        <div style="background:var(--success-light);color:var(--success);padding:1rem;border-radius:8px;text-align:center;font-weight:600;font-size:1.2rem;">
            <i class="fas fa-check-double"></i> Completed <?= $t['completed_at'] ? date('d M, H:i',strtotime($t['completed_at'])) : '' ?>
        </div>
        <?php if(!empty($t['completion_notes'])): ?>
        <p style="font-size:1.2rem;color:var(--text-muted);margin-top:.8rem;font-style:italic;">"<?= e($t['completion_notes']) ?>"</p>
        <?php endif; ?>
        <?php elseif($s==='cancelled'): ?>
        <div style="background:#f5f5f5;color:#777;padding:1rem;border-radius:8px;text-align:center;font-size:1.2rem;">Cancelled</div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Complete Task Modal -->
<div class="modal-bg" id="completeTaskModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Complete Task</h3>
            <button class="btn btn-primary modal-close" onclick="closeModal('completeTaskModal')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <form id="frmCompleteTask" onsubmit="event.preventDefault();submitCompleteTask();">
            <input type="hidden" name="action" value="update_task_status">
            <input type="hidden" name="status" value="completed">
            <input type="hidden" name="task_id" id="comp_task_id">
            <div class="form-group">
                <label>Completion Notes (Optional)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Any notes about this task completion..."></textarea>
            </div>
            <div class="form-group">
                <label>Photo Proof (Optional)</label>
                <input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png">
            </div>
            <button type="submit" class="btn btn-success btn-wide" id="btnCompleteSubmit"><span class="btn-text">
                <i class="fas fa-check"></i> Submit Completion
            </span></button>
        </form>
    </div>
</div>

<script>
function filterByStatus(st) {
    document.querySelectorAll('#taskFilterTabs .ftab').forEach(b=>b.classList.remove('active'));
    event.target.classList.add('active');
    document.querySelectorAll('#taskGrid .task-card').forEach(c => {
        c.style.display = (st==='all' || c.dataset.status===st) ? 'block' : 'none';
    });
}
function filterTasks() {
    const pri = document.getElementById('filterPriority').value;
    document.querySelectorAll('#taskGrid .task-card').forEach(c => {
        const matchP = (pri==='all' || c.dataset.priority===pri);
        if(matchP) c.style.removeProperty('display');
        else c.style.display='none';
    });
}
async function startTask(taskId) {
    const res = await doAction({action:'update_task_status', task_id:taskId, status:'in progress'});
    if(res) setTimeout(()=>location.reload(),800);
}
function openCompleteModal(taskId) {
    document.getElementById('comp_task_id').value = taskId;
    openModal('completeTaskModal');
}
async function submitCompleteTask() {
    const btn = document.getElementById('btnCompleteSubmit');
    btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Submitting...'; btn.disabled=true;
    const fd = new FormData(document.getElementById('frmCompleteTask'));
    const res = await doAction(fd);
    btn.innerHTML='<i class="fas fa-check"></i> Submit Completion'; btn.disabled=false;
    if(res) { closeModal('completeTaskModal'); setTimeout(()=>location.reload(),800); }
}
async function toggleChecklist(chkId, taskId, isChecked) {
    await doAction({action:'complete_task_checklist', checklist_id:chkId, task_id:taskId, state:isChecked?1:0});
}
</script>
