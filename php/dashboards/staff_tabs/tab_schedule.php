<?php
/**
 * tab_schedule.php — Module 9: My Shift Schedule + Leave Requests
 */
$leaves = dbSelect($conn,"SELECT * FROM staff_leaves WHERE staff_id=? ORDER BY created_at DESC LIMIT 10","i",[$staff_id]);
$all_shifts = dbSelect($conn,"SELECT * FROM staff_shifts WHERE staff_id=? ORDER BY shift_date ASC LIMIT 21","i",[$staff_id]);
?>
<div id="sec-schedule" class="dash-section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
        <h2 style="font-size:2.2rem;font-weight:700;"><i class="fas fa-calendar-alt" style="color:var(--role-accent);"></i> Shift Schedule</h2>
        <button class="btn btn-primary" onclick="openModal('leaveModal')"><i class="fas fa-calendar-plus"></i> Request Leave</button>
    </div>

    <!-- Shift Table -->
    <div class="card" style="margin-bottom:2rem;">
        <div class="card-header"><h3><i class="fas fa-calendar-week"></i> My Shifts</h3></div>
        <div class="card-body-flush">
            <?php if(empty($all_shifts)): ?>
                <p style="text-align:center;padding:4rem;color:var(--text-muted);">No shifts scheduled yet.</p>
            <?php else: ?>
            <table class="stf-table">
                <thead><tr><th>Date</th><th>Day</th><th>Shift Type</th><th>Time</th><th>Ward / Location</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($all_shifts as $s):
                    $is_today = ($s['shift_date']==$today);
                    $st = $s['status']??'scheduled';
                    $st_c=['active'=>'var(--success)','completed'=>'var(--text-muted)','missed'=>'var(--danger)','scheduled'=>'var(--info)','swapped'=>'var(--warning)'][$st]??'var(--info)';
                    $shift_icon=['morning'=>'fa-sun','afternoon'=>'fa-cloud-sun','night'=>'fa-moon','rotating'=>'fa-sync'][$s['shift_type']]??'fa-clock';
                ?>
                <tr style="<?=$is_today?'background:var(--role-accent-light);':''?>">
                    <td><strong><?=date('d M Y',strtotime($s['shift_date']))?></strong>
                        <?=$is_today?'<span class="badge" style="background:var(--role-accent);color:#fff;font-size:1rem;margin-left:.5rem;">TODAY</span>':''?>
                    </td>
                    <td style="color:var(--text-secondary);"><?=date('l',strtotime($s['shift_date']))?></td>
                    <td><i class="fas <?=$shift_icon?>" style="color:var(--role-accent);margin-right:.5rem;"></i><?=e(ucfirst($s['shift_type']??'—'))?></td>
                    <td style="font-weight:600;"><?=date('H:i',strtotime($s['start_time']??'00:00'))?>–<?=date('H:i',strtotime($s['end_time']??'00:00'))?></td>
                    <td><?=e($s['location_ward_assigned']??'—')?></td>
                    <td><span class="badge" style="background:color-mix(in srgb,<?=$st_c?> 15%,#fff 85%);color:<?=$st_c?>;"><?=ucfirst($st)?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Leave Requests -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-umbrella-beach"></i> My Leave Requests</h3></div>
        <div class="card-body-flush">
            <?php if(empty($leaves)): ?>
                <p style="text-align:center;padding:4rem;color:var(--text-muted);">No leave requests submitted.</p>
            <?php else: ?>
            <table class="stf-table">
                <thead><tr><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Reason</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($leaves as $l):
                    $lst=$l['status']??'pending';
                    $lc=['pending'=>'var(--warning)','approved'=>'var(--success)','rejected'=>'var(--danger)'][$lst]??'var(--text-muted)';
                    $days=round((strtotime($l['end_date'])-strtotime($l['start_date']))/86400)+1;
                ?>
                <tr>
                    <td><strong><?=e(ucwords(str_replace('_',' ',$l['leave_type'])))?></strong></td>
                    <td><?=date('d M Y',strtotime($l['start_date']))?></td>
                    <td><?=date('d M Y',strtotime($l['end_date']))?></td>
                    <td><?=$days?> day<?=$days>1?'s':''?></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?=e($l['reason'])?>"><?=e(mb_strimwidth($l['reason'],0,50,'…'))?></td>
                    <td><span class="badge" style="background:color-mix(in srgb,<?=$lc?> 15%,#fff 85%);color:<?=$lc?>;"><?=ucfirst($lst)?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Leave Request Modal -->
<div class="modal-bg" id="leaveModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-umbrella-beach" style="color:var(--role-accent);"></i> Request Leave</h3>
            <button class="modal-close" onclick="closeModal('leaveModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmLeave" onsubmit="event.preventDefault();submitLeave();">
            <div class="form-group">
                <label>Leave Type *</label>
                <select name="leave_type" class="form-control" required>
                    <option value="">Select type</option>
                    <option value="annual">Annual Leave</option>
                    <option value="sick">Sick Leave</option>
                    <option value="emergency">Emergency Leave</option>
                    <option value="compassionate">Compassionate Leave</option>
                    <option value="maternity">Maternity Leave</option>
                    <option value="paternity">Paternity Leave</option>
                    <option value="unpaid">Unpaid Leave</option>
                    <option value="study">Study Leave</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" class="form-control" required min="<?=date('Y-m-d')?>">
                </div>
                <div class="form-group">
                    <label>End Date *</label>
                    <input type="date" name="end_date" class="form-control" required min="<?=date('Y-m-d')?>">
                </div>
            </div>
            <div class="form-group">
                <label>Reason for Leave *</label>
                <textarea name="reason" class="form-control" rows="3" required placeholder="Please provide reason..."></textarea>
            </div>
            <input type="hidden" name="action" value="submit_leave_request">
            <button type="submit" class="btn btn-primary btn-wide" id="btnLeave"><i class="fas fa-paper-plane"></i> Submit Request</button>
        </form>
    </div>
</div>

<script>
async function submitLeave() {
    const btn=document.getElementById('btnLeave'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Submitting...'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmLeave'));
    const res=await doAction(fd);
    btn.innerHTML='<i class="fas fa-paper-plane"></i> Submit Request'; btn.disabled=false;
    if(res){closeModal('leaveModal'); document.getElementById('frmLeave').reset(); setTimeout(()=>location.reload(),1000);}
}
</script>