<?php
/**
 * tab_maintenance.php — Module 6: Maintenance Staff Module
 */
if ($staffRole !== 'maintenance') { echo '<div id="sec-maintenance" class="dash-section"></div>'; return; }

$open_requests  = dbSelect($conn,"SELECT * FROM maintenance_requests WHERE status='open' AND (assigned_to IS NULL OR assigned_to=0) ORDER BY FIELD(priority,'critical','high','medium','low'), reported_at ASC LIMIT 20");
$my_requests    = dbSelect($conn,"SELECT * FROM maintenance_requests WHERE assigned_to=? AND status NOT IN ('completed','cancelled') ORDER BY FIELD(status,'in progress','on hold','assigned'), priority DESC LIMIT 20","i",[$staff_id]);
$completed_today= dbSelect($conn,"SELECT * FROM maintenance_requests WHERE assigned_to=? AND status='completed' AND DATE(completed_at)=? ORDER BY completed_at DESC LIMIT 10","is",[$staff_id,$today]);
?>
<div id="sec-maintenance" class="dash-section">
    <h2 style="font-size:2.2rem;font-weight:700;margin-bottom:2.5rem;"><i class="fas fa-tools" style="color:var(--role-accent);"></i> Work Orders</h2>

    <!-- My Active Jobs -->
    <?php if(!empty($my_requests)): ?>
    <div class="card" style="margin-bottom:2rem;border-top:3px solid var(--role-accent);">
        <div class="card-header"><h3><i class="fas fa-hard-hat"></i> My Active Jobs (<?=count($my_requests)?>)</h3></div>
        <div class="card-body-flush">
        <table class="stf-table">
            <thead><tr><th>ID</th><th>Location</th><th>Issue</th><th>Priority</th><th>Status</th><th>Update</th></tr></thead>
            <tbody>
            <?php foreach($my_requests as $r):
                $pri=$r['priority']??'medium';
                $pri_c=['critical'=>'var(--danger)','high'=>'#E67E22','medium'=>'var(--warning)','low'=>'var(--success)'][$pri]??'var(--text-muted)';
                $st=$r['status']??'assigned';
                $st_c=['assigned'=>'var(--primary)','in progress'=>'var(--warning)','on hold'=>'var(--text-muted)'][$st]??'var(--info)';
            ?>
            <tr>
                <td style="font-family:monospace;font-weight:600;">#<?=$r['id']?></td>
                <td><?=e($r['location']??'—')?></td>
                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?=e($r['issue_description']??'')?>">
                    <span style="font-size:1.1rem;color:var(--text-secondary);"><?=e($r['issue_category']??'—')?></span><br>
                    <?=e(mb_strimwidth($r['issue_description']??'',0,60,'…'))?>
                </td>
                <td><span class="badge" style="background:color-mix(in srgb,<?=$pri_c?> 15%,#fff 85%);color:<?=$pri_c?>;"><?=ucfirst($pri)?></span></td>
                <td><span class="badge" style="background:color-mix(in srgb,<?=$st_c?> 15%,#fff 85%);color:<?=$st_c?>;"><?=ucwords($st)?></span></td>
                <td>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                        <?php if($st==='assigned'||$st==='on hold'): ?>
                        <button class="btn btn-outline btn-sm" onclick="updateMaint(<?=$r['id']?>,'in progress')"><i class="fas fa-play"></i> Start</button>
                        <?php endif; ?>
                        <?php if($st==='in progress'): ?>
                        <button class="btn btn-outline btn-sm" onclick="updateMaint(<?=$r['id']?>,'on hold')"><i class="fas fa-pause"></i> Hold</button>
                        <button class="btn btn-success btn-sm" onclick="openComplMaint(<?=$r['id']?>)"><i class="fas fa-check"></i> Complete</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Open Requests Queue -->
    <div class="card" style="margin-bottom:2rem;">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-list"></i> Open Request Queue</h3>
            <?php if(count($open_requests)>0): ?><span class="badge badge-urgent"><?=count($open_requests)?> open</span><?php endif; ?>
        </div>
        <?php if(empty($open_requests)): ?>
        <div class="card-body"><p style="text-align:center;color:var(--text-muted);padding:3rem 0;">No open requests available.</p></div>
        <?php else: ?>
        <div class="card-body-flush">
        <table class="stf-table">
            <thead><tr><th>ID</th><th>Location</th><th>Category</th><th>Priority</th><th>Issue</th><th>Reported</th><th></th></tr></thead>
            <tbody>
            <?php foreach($open_requests as $r):
                $pri=$r['priority']??'medium';
                $pri_c=['critical'=>'var(--danger)','high'=>'#E67E22','medium'=>'var(--warning)','low'=>'var(--success)'][$pri]??'var(--text-muted)';
            ?>
            <tr>
                <td style="font-family:monospace;">#<?=$r['id']?></td>
                <td><?=e($r['location']??'—')?></td>
                <td><?=e(ucfirst($r['issue_category']??'—'))?></td>
                <td><span class="badge" style="background:color-mix(in srgb,<?=$pri_c?> 15%,#fff 85%);color:<?=$pri_c?>;"><?=ucfirst($pri)?></span></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=e(mb_strimwidth($r['issue_description']??'',0,60,'…'))?></td>
                <td><?=date('d M, H:i',strtotime($r['reported_at']??'now'))?></td>
                <td><button class="btn btn-primary btn-sm" onclick="acceptMaint(<?=$r['id']?>)"><i class="fas fa-hand-pointer"></i> Accept</button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Completed Today -->
    <?php if(!empty($completed_today)): ?>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-check-double" style="color:var(--success);"></i> Completed Today (<?=count($completed_today)?>)</h3></div>
        <div class="card-body-flush">
        <table class="stf-table">
            <thead><tr><th>ID</th><th>Location</th><th>Issue</th><th>Completed At</th></tr></thead>
            <tbody>
            <?php foreach($completed_today as $r): ?>
            <tr>
                <td style="font-family:monospace;">#<?=$r['id']?></td>
                <td><?=e($r['location']??'—')?></td>
                <td><?=e(mb_strimwidth($r['issue_description']??'',0,60,'…'))?></td>
                <td><?=date('H:i',strtotime($r['completed_at']))?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Complete Maintenance Modal -->
<div class="modal-bg" id="complMaintModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Complete Repair</h3>
            <button class="modal-close" onclick="closeModal('complMaintModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmComplMaint" onsubmit="event.preventDefault();submitComplMaint();">
            <input type="hidden" name="action" value="update_maintenance_status">
            <input type="hidden" name="status" value="completed">
            <input type="hidden" name="request_id" id="maintReqId">
            <div class="form-group"><label>Action Taken *</label><textarea name="action_notes" class="form-control" rows="3" required placeholder="Describe what was done to fix the issue..."></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Before Photo</label><input type="file" name="before_photo" class="form-control" accept=".jpg,.jpeg,.png"></div>
                <div class="form-group"><label>After Photo</label><input type="file" name="after_photo" class="form-control" accept=".jpg,.jpeg,.png"></div>
            </div>
            <button type="submit" class="btn btn-success btn-wide" id="btnComplMaint"><i class="fas fa-check"></i> Submit Completion</button>
        </form>
    </div>
</div>

<script>
async function acceptMaint(id){ const res=await doAction({action:'accept_maintenance_request',request_id:id},'Request accepted!'); if(res) setTimeout(()=>location.reload(),700); }
async function updateMaint(id,st){ const res=await doAction({action:'update_maintenance_status',request_id:id,status:st}); if(res) setTimeout(()=>location.reload(),700); }
function openComplMaint(id){ document.getElementById('maintReqId').value=id; openModal('complMaintModal'); }
async function submitComplMaint(){
    const btn=document.getElementById('btnComplMaint'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmComplMaint'));
    const res=await doAction(fd,'Repair logged and completed!');
    btn.innerHTML='<i class="fas fa-check"></i> Submit Completion'; btn.disabled=false;
    if(res){ closeModal('complMaintModal'); setTimeout(()=>location.reload(),700); }
}
</script>
