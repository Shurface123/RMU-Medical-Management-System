<?php
/**
 * tab_kitchen.php — Module 8: Kitchen Staff Module
 */
if ($staffRole !== 'kitchen_staff') { echo '<div id="sec-kitchen" class="dash-section"></div>'; return; }

$kitchen_tasks = dbSelect($conn,"SELECT * FROM kitchen_tasks WHERE assigned_to=? ORDER BY FIELD(preparation_status,'pending','in preparation','ready'),scheduled_time ASC LIMIT 30","i",[$staff_id]);
$dietary_flags = dbSelect($conn,"SELECT * FROM kitchen_dietary_flags WHERE DATE(flagged_at)=? ORDER BY flag_id DESC LIMIT 10","s",[$today]);
?>
<div id="sec-kitchen" class="dash-section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
        <h2 style="font-size:2.2rem;font-weight:700;"><i class="fas fa-utensils" style="color:var(--role-accent);"></i> Kitchen Tasks</h2>
        <button class="btn-icon btn btn-outline" onclick="openModal('dietaryModal')"><span class="btn-text"><i class="fas fa-allergies"></i> Flag Dietary Issue</span></button>
    </div>

    <!-- Dietary Alerts Banner -->
    <?php if(!empty($dietary_flags)): ?>
    <div style="background:var(--danger-light);border:1.5px solid var(--danger);border-radius:var(--radius-md);padding:1.5rem 2rem;margin-bottom:2rem;display:flex;align-items:flex-start;gap:1.5rem;">
        <i class="fas fa-allergies" style="font-size:2.5rem;color:var(--danger);flex-shrink:0;"></i>
        <div>
            <strong style="font-size:1.5rem;color:var(--danger);">⚠️ Active Dietary Alerts Today</strong>
            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:.8rem;">
                <?php foreach($dietary_flags as $df): ?>
                <span style="background:#fff;border:1.5px solid var(--danger);padding:.3rem .9rem;border-radius:20px;font-size:1.2rem;font-weight:600;color:var(--danger);">
                    <?=e($df['patient_name']??'Patient')?> — <?=e(mb_strimwidth($df['issue_description'],0,40,'…'))?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Meal Tasks -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-utensils"></i> My Meal Tasks (<?=date('d M Y')?>)</h3></div>
        <?php if(empty($kitchen_tasks)): ?>
        <div class="card-body" style="text-align:center;padding:4rem;"><p style="color:var(--text-muted);">No meal tasks assigned yet.</p></div>
        <?php else: ?>
        <div class="card-body-flush"><table class="stf-table">
            <thead><tr><th>Meal Type</th><th>Ward/Dept</th><th>Qty</th><th>Dietary Req.</th><th>Scheduled</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($kitchen_tasks as $t):
                $st=$t['preparation_status']??'pending';
                $st_c=['pending'=>'var(--warning)','in preparation'=>'var(--primary)','ready'=>'var(--success)','delivered'=>'var(--text-muted)'][$st]??'var(--info)';
                $has_dietary=!empty($t['dietary_requirements']) && $t['dietary_requirements'] !== 'null';
                $next=['pending'=>'in preparation','in preparation'=>'ready','ready'=>'delivered'][$st]??null;
            ?>
            <tr style="<?=$has_dietary?'border-left:3px solid var(--danger);':''?>">
                <td>
                    <?php if($has_dietary): ?><i class="fas fa-exclamation-circle" style="color:var(--danger);margin-right:.4rem;" title="Special dietary"></i><?php endif; ?>
                    <strong><?=e(ucfirst($t['meal_type']??'Meal'))?></strong>
                </td>
                <td><?=e($t['ward_department']??'—')?></td>
                <td><?=$t['quantity']??'—'?></td>
                <td>
                    <?php if($has_dietary): ?>
                    <span class="badge badge-urgent" title="<?=e($t['dietary_requirements'])?>"><?=e(mb_strimwidth($t['dietary_requirements'],0,25,'…'))?></span>
                    <?php else: ?><span style="color:var(--text-muted);">Standard</span><?php endif; ?>
                </td>
                <td><?=$t['scheduled_time']?date('H:i',strtotime($t['scheduled_time'])):'—'?></td>
                <td><span class="badge" style="background:color-mix(in srgb,<?=$st_c?> 15%,#fff 85%);color:<?=$st_c?>;"><?=ucfirst($st)?></span></td>
                <td>
                    <?php if($next): ?>
                    <button class="btn-icon btn btn-primary btn-sm" onclick="updateKitchenTask(<?=$t['task_id']?>,'<?=e($next)?>')"><span class="btn-text">
                        <?php $icons=['in preparation'=>'fa-fire','ready'=>'fa-check','delivered'=>'fa-truck']; ?>
                        <i class="fas <?=$icons[$next]??'fa-chevron-right'?>"></i> <?=ucfirst($next)?>
                    </span></button>
                    <?php elseif($st==='delivered'): ?>
                    <span style="color:var(--success);"><i class="fas fa-check-double"></i></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
</div>

<!-- Dietary Issue Modal -->
<div class="modal-bg" id="dietaryModal">
    <div class="modal-box" style="max-width:460px;">
        <div class="modal-header">
            <h3><i class="fas fa-allergies" style="color:var(--danger);"></i> Flag Dietary Issue</h3>
            <button class="btn btn-primary modal-close" onclick="closeModal('dietaryModal')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <form id="frmDietary" onsubmit="event.preventDefault();submitDietary();">
            <input type="hidden" name="action" value="report_dietary_issue">
            <div class="form-group"><label>Patient Name</label><input type="text" name="patient_name" class="form-control" placeholder="Patient's name"></div>
            <div class="form-group"><label>Ward / Department</label><input type="text" name="ward" class="form-control" placeholder="Ward A, OPD..."></div>
            <div class="form-group"><label>Issue Description *</label><textarea name="issue" class="form-control" rows="3" required placeholder="Describe the dietary concern or ingredient shortage..."></textarea></div>
            <button type="submit" class="btn btn-danger btn-wide" id="btnDietary"><span class="btn-text"><i class="fas fa-paper-plane"></i> Submit Flag</span></button>
        </form>
    </div>
</div>

<script>
async function updateKitchenTask(id, status){
    const res=await doAction({action:'update_kitchen_task_status', task_id:id, status});
    if(res) setTimeout(()=>location.reload(),700);
}
async function submitDietary(){
    const btn=document.getElementById('btnDietary'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmDietary'));
    const res=await doAction(fd,'Dietary issue flagged!');
    btn.innerHTML='<i class="fas fa-paper-plane"></i> Submit Flag'; btn.disabled=false;
    if(res){ closeModal('dietaryModal'); document.getElementById('frmDietary').reset(); setTimeout(()=>location.reload(),700); }
}
</script>
