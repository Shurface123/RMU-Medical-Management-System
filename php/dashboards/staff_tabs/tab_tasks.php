<?php
/**
 * tab_tasks.php
 * Module: Task checklists and assignments for all staff
 */
?>
<div id="sec-tasks" class="dash-section <?=($active_tab==='tasks')?'active':''?>">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
        <h2 style="font-size:2.2rem;font-weight:700;color:var(--text-primary);"><i class="fas fa-clipboard-list" style="color:var(--role-accent);"></i> My Tasks</h2>
        
        <div class="filter-tabs" style="display:flex;gap:.5rem;margin:0;">
            <button class="ftab active" onclick="filterTasks('pending')">Pending</button>
            <button class="ftab" onclick="filterTasks('in_progress')">In Progress</button>
            <button class="ftab" onclick="filterTasks('completed')">Completed</button>
        </div>
    </div>

    <?php
    $tasks = dbSelect($conn, "SELECT * FROM staff_tasks WHERE assigned_to=? ORDER BY due_date ASC, due_time ASC", "i", [$staff_id]);
    if(empty($tasks)): ?>
        <div class="adm-card" style="text-align:center;padding:5rem 2rem;">
            <i class="fas fa-clipboard-check" style="font-size:5rem;color:var(--text-muted);margin-bottom:1.5rem;"></i>
            <h3 style="font-size:1.8rem;color:var(--text-secondary);">No Tasks Assigned</h3>
            <p style="color:var(--text-muted);">You currently have no tasks in your queue.</p>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:2rem;">
        <?php foreach($tasks as $t): 
            $status_class = [
                'pending' => 'var(--warning)',
                'in progress' => 'var(--info)',
                'completed' => 'var(--success)',
                'overdue' => 'var(--danger)'
            ][$t['status']] ?? 'var(--text-muted)';
            
            $cat_icons = [
                'cleaning'=>'fa-broom', 'laundry'=>'fa-tshirt', 'maintenance'=>'fa-tools',
                'transport'=>'fa-ambulance', 'security'=>'fa-shield-alt', 'kitchen'=>'fa-utensils', 'general'=>'fa-tasks'
            ];
            $icon = $cat_icons[$t['task_category']] ?? 'fa-tasks';
            $t_status = str_replace(' ', '_', $t['status']); // for JS filtering
        ?>
            <div class="adm-card task-card" data-status="<?=e($t_status)?>" style="border-top:4px solid <?=$status_class?>;position:relative;">
                <div class="adm-card-body" style="padding:2rem;">
                    
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
                        <h4 style="font-size:1.6rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.8rem;">
                            <i class="fas <?=$icon?>" style="color:var(--text-muted);"></i> <?=e($t['task_title'])?>
                        </h4>
                        <span class="adm-badge" style="background:<?=str_replace(')','',str_replace('var(','',$status_class))?>20;color:<?=$status_class?>;">
                            <?=ucwords(e($t['status']))?>
                        </span>
                    </div>
                    
                    <p style="font-size:1.3rem;color:var(--text-secondary);margin-bottom:1.5rem;line-height:1.4;">
                        <?=e($t['task_description'])?>
                    </p>

                    <div style="display:flex;flex-wrap:wrap;gap:1.5rem;margin-bottom:1.5rem;font-size:1.2rem;color:var(--text-muted);">
                        <?php if($t['location']): ?><span><i class="fas fa-map-marker-alt"></i> <?=e($t['location'])?></span><?php endif; ?>
                        <?php if($t['due_date']): ?><span><i class="far fa-calendar-alt"></i> <?=date('d M Y', strtotime($t['due_date']))?></span><?php endif; ?>
                        <?php if($t['due_time']): ?><span><i class="far fa-clock"></i> <?=date('h:i A', strtotime($t['due_time']))?></span><?php endif; ?>
                        <?php if($t['priority']): ?><span><i class="fas fa-flag"></i> <?=ucfirst(e($t['priority']))?></span><?php endif; ?>
                    </div>

                    <!-- Sub-Checklists -->
                    <?php
                    $checks = dbSelect($conn, "SELECT * FROM staff_task_checklists WHERE task_id=?", "i", [$t['task_id']]);
                    if(!empty($checks)):
                    ?>
                    <div style="background:var(--surface-2);border-radius:8px;padding:1rem 1.5rem;margin-bottom:1.5rem;">
                        <h5 style="font-size:1.2rem;font-weight:600;margin-bottom:.8rem;color:var(--text-secondary);text-transform:uppercase;">Checklist</h5>
                        <div style="display:flex;flex-direction:column;gap:.8rem;">
                        <?php foreach($checks as $chk): 
                            $is_c = (int)$chk['is_completed'] === 1;
                        ?>
                            <label style="display:flex;align-items:flex-start;gap:1rem;font-size:1.3rem;cursor:pointer;">
                                <input type="checkbox" style="margin-top:.4rem;width:16px;height:16px;accent-color:var(--role-accent);" onchange="toggleChecklist(<?=$chk['checklist_id']?>, <?=$t['task_id']?>, this.checked)" <?= $is_c ? 'checked':'' ?> <?= $t['status']==='completed' ? 'disabled':'' ?>>
                                <span style="line-height:1.4; <?= $is_c ? 'text-decoration:line-through;color:var(--text-muted);':'' ?>"><?=e($chk['checklist_item'])?></span>
                            </label>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($t['status'] !== 'completed'): ?>
                        <div style="display:flex;gap:1rem;">
                            <?php if($t['status'] === 'pending'): ?>
                                <button class="adm-btn adm-btn-primary" style="flex:1;" onclick="updateTaskStatus(<?=$t['task_id']?>, 'in progress')"><i class="fas fa-play"></i> Start Task</button>
                            <?php elseif($t['status'] === 'in progress'): ?>
                                <button class="adm-btn" style="flex:1;background:var(--success);color:#fff;" onclick="openTaskCompleteModal(<?=$t['task_id']?>)"><i class="fas fa-check"></i> Mark Complete</button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="background:var(--success-light);color:var(--success);padding:1rem;border-radius:8px;text-align:center;font-weight:600;font-size:1.2rem;">
                            <i class="fas fa-check-circle"></i> Completed on <?=date('d M Y, h:i A', strtotime($t['completed_at']))?>
                        </div>
                        <?php if($t['completion_notes']): ?>
                            <p style="margin-top:1rem;font-size:1.2rem;color:var(--text-secondary);font-style:italic;">"<?=e($t['completion_notes'])?>"</p>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Task Complete Modal -->
<div class="modal-bg" id="taskCompleteModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Complete Task</h3>
            <button class="modal-close" onclick="closeModal('taskCompleteModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmCompleteTask" onsubmit="event.preventDefault(); submitTaskComplete();">
            <input type="hidden" name="action" value="update_task_status">
            <input type="hidden" name="status" value="completed">
            <input type="hidden" name="task_id" id="comp_task_id">
            
            <div class="form-group">
                <label>Completion Notes (Optional)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes about the completion of this task..."></textarea>
            </div>
            
            <button type="submit" class="adm-btn" style="width:100%;padding:1rem;font-size:1.4rem;background:var(--success);color:#fff;"><i class="fas fa-check"></i> Submit Completion</button>
        </form>
    </div>
</div>

<script>
function filterTasks(status) {
    document.querySelectorAll('.filter-tabs .ftab').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    
    document.querySelectorAll('.task-card').forEach(c => {
        if(status === 'all' || c.getAttribute('data-status') === status) {
            c.style.display = 'block';
        } else {
            c.style.display = 'none';
        }
    });
}
// Init filter
document.addEventListener('DOMContentLoaded', () => {
    filterTasks('pending');
});

async function toggleChecklist(chkId, taskId, isChecked) {
    const res = await staffAction({
        action: 'complete_task_checklist',
        checklist_id: chkId,
        task_id: taskId,
        state: isChecked ? 1 : 0
    });
    if(!res.success) showToast(res.message, 'error');
}

async function updateTaskStatus(taskId, newStatus) {
    const res = await staffAction({
        action: 'update_task_status',
        task_id: taskId,
        status: newStatus
    });
    showToast(res.message, res.success ? 'success' : 'error');
    if(res.success) setTimeout(()=>location.reload(), 1000);
}

function openTaskCompleteModal(taskId) {
    document.getElementById('comp_task_id').value = taskId;
    openModal('taskCompleteModal');
}

async function submitTaskComplete() {
    const form = document.getElementById('frmCompleteTask');
    const fd = new FormData(form);
    const res = await staffAction(fd);
    showToast(res.message, res.success ? 'success' : 'error');
    if(res.success) {
        closeModal('taskCompleteModal');
        setTimeout(()=>location.reload(), 1000);
    }
}
</script>
