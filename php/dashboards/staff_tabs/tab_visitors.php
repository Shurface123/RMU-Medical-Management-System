<?php
/**
 * tab_visitors.php — Security: Visitor Log Module
 */
if ($staffRole !== 'security') { echo '<div id="sec-visitors" class="dash-section"></div>'; return; }

$today_visitors = dbSelect($conn,"SELECT * FROM visitor_logs WHERE logged_by=? AND DATE(entry_time)=? ORDER BY log_id DESC","is",[$staff_id,$today]);
$active_visitors = dbSelect($conn,"SELECT * FROM visitor_logs WHERE logged_by=? AND DATE(entry_time)=? AND exit_time IS NULL ORDER BY log_id DESC","is",[$staff_id,$today]);
?>
<div id="sec-visitors" class="dash-section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
        <h2 style="font-size:2.2rem;font-weight:700;"><i class="fas fa-user-check" style="color:var(--role-accent);"></i> Visitor Log
            <?php if(!empty($active_visitors)): ?><span class="badge badge-progress" style="font-size:1.2rem;margin-left:.8rem;"><?=count($active_visitors)?> inside</span><?php endif; ?>
        </h2>
        <button class="btn btn-primary" onclick="openModal('addVisitorModal')"><span class="btn-text"><i class="fas fa-user-plus"></i> Log New Visitor</span></button>
    </div>

    <!-- Active Visitors (in premises) -->
    <?php if(!empty($active_visitors)): ?>
    <div class="card" style="margin-bottom:2rem;border-top:3px solid var(--warning);">
        <div class="card-header"><h3><i class="fas fa-users"></i> Currently Inside (<?=count($active_visitors)?>)</h3></div>
        <div class="card-body-flush"><table class="stf-table">
            <thead><tr><th>Name</th><th>ID#</th><th>Purpose</th><th>Visiting</th><th>Ward</th><th>Entry</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($active_visitors as $v): ?>
            <tr style="background:var(--warning-light);">
                <td><strong><?=e($v['visitor_name'])?></strong></td>
                <td style="font-family:monospace;"><?=e($v['visitor_id_number']??'—')?></td>
                <td><?=e($v['purpose']??'—')?></td>
                <td><?=e($v['person_visiting']??'—')?></td>
                <td><?=e($v['ward_department']??'—')?></td>
                <td><?=date('H:i',strtotime($v['entry_time']))?></td>
                <td><button class="btn btn-success btn-sm" onclick="logExit(<?=$v['log_id']?>)"><span class="btn-text"><i class="fas fa-sign-out-alt"></i> Log Exit</span></button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
    <?php endif; ?>

    <!-- All Today's Visitors -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-list"></i> Today's Visitor Log</h3></div>
        <?php if(empty($today_visitors)): ?>
        <div class="card-body" style="text-align:center;padding:4rem;"><p style="color:var(--text-muted);">No visitors logged today.</p></div>
        <?php else: ?>
        <div class="card-body-flush"><table class="stf-table">
            <thead><tr><th>Name</th><th>ID#</th><th>Purpose</th><th>Visiting</th><th>Ward</th><th>Entry</th><th>Exit</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($today_visitors as $v):
                $has_exit = !empty($v['exit_time']);
            ?>
            <tr>
                <td><strong><?=e($v['visitor_name'])?></strong></td>
                <td style="font-family:monospace;"><?=e($v['visitor_id_number']??'—')?></td>
                <td><?=e($v['purpose']??'—')?></td>
                <td><?=e($v['person_visiting']??'—')?></td>
                <td><?=e($v['ward_department']??'—')?></td>
                <td><?=date('H:i',strtotime($v['entry_time']))?></td>
                <td><?=$has_exit?date('H:i',strtotime($v['exit_time'])):'—'?></td>
                <td>
                    <?php if($has_exit): ?><span class="badge badge-cancelled">Exited</span>
                    <?php else: ?><span class="badge badge-progress">Inside</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Visitor Modal -->
<div class="modal-bg" id="addVisitorModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus" style="color:var(--role-accent);"></i> Log New Visitor</h3>
            <button class="btn btn-primary modal-close" onclick="closeModal('addVisitorModal')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <form id="frmVisitor" onsubmit="event.preventDefault();submitVisitor();">
            <input type="hidden" name="action" value="log_visitor">
            <div class="form-row">
                <div class="form-group"><label>Visitor Name *</label><input name="visitor_name" type="text" class="form-control" required placeholder="Full name"></div>
                <div class="form-group"><label>ID Number</label><input name="visitor_id_number" type="text" class="form-control" placeholder="National ID / Passport"></div>
            </div>
            <div class="form-group"><label>Purpose of Visit *</label>
                <select name="purpose" class="form-control" required>
                    <option value="">Select purpose</option>
                    <option value="Visiting Patient">Visiting Patient</option><option value="Official Business">Official Business</option>
                    <option value="Medical Appointment">Medical Appointment</option><option value="Delivery">Delivery</option>
                    <option value="Contractor">Contractor/Maintenance</option><option value="Other">Other</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Person Visiting</label><input name="person_visiting" type="text" class="form-control" placeholder="Name of patient/staff"></div>
                <div class="form-group"><label>Ward / Department</label><input name="ward" type="text" class="form-control" placeholder="Ward A, OPD, etc."></div>
            </div>
            <button type="submit" class="btn btn-primary btn-wide" id="btnVisitor"><span class="btn-text"><i class="fas fa-sign-in-alt"></i> Log Entry</span></button>
        </form>
    </div>
</div>
<script>
async function submitVisitor(){
    const btn=document.getElementById('btnVisitor'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmVisitor'));
    const res=await doAction(fd,'Visitor entry logged!');
    btn.innerHTML='<i class="fas fa-sign-in-alt"></i> Log Entry'; btn.disabled=false;
    if(res){closeModal('addVisitorModal');document.getElementById('frmVisitor').reset();setTimeout(()=>location.reload(),700);}
}
async function logExit(id){
    if(!confirmAction('Mark this visitor as exited?')) return;
    const res=await doAction({action:'log_visitor_exit', visitor_log_id:id},'Visitor exit logged.');
    if(res) setTimeout(()=>location.reload(),700);
}
</script>
