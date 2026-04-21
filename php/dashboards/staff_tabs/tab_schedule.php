<?php
/**
 * tab_schedule.php — Module 9: My Shift Schedule + Leave Requests (Modernized)
 */
$leaves = dbSelect($conn, "SELECT * FROM staff_leaves WHERE staff_id=? ORDER BY created_at DESC LIMIT 50", "i", [$staff_id]);
$all_shifts = dbSelect($conn, "SELECT * FROM staff_shifts WHERE staff_id=? AND shift_date >= DATE_SUB(?, INTERVAL 7 DAY) ORDER BY shift_date ASC LIMIT 31", "is", [$staff_id, $today]);

// Group shifts for calendar-like display
$calendar_shifts = [];
foreach ($all_shifts as $s) {
    $calendar_shifts[$s['shift_date']] = $s;
}
?>
<div id="sec-schedule" class="dash-section">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2.5rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-calendar-alt" style="color:var(--role-accent);"></i> Workforce Schedule</h2>
            <p style="font-size:1.3rem;color:var(--text-muted);margin:0.5rem 0 0;">Track your shifts and manage time-off requests</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('mdlLeaveRequest')" style="padding:1.2rem 2rem; font-weight:700;">
            <span class="btn-text"><i class="fas fa-calendar-plus"></i> Request Leave</span>
        </button>
    </div>

    <!-- Active Shift Hero -->
    <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:2.5rem; margin-bottom:2.5rem;">
        
        <!-- Shift Timeline / Calendar -->
        <div class="card" style="padding:1.5rem;">
            <div class="card-header" style="border:none; margin-bottom:1.5rem;">
                <h3 style="font-size:1.6rem; font-weight:700;"><i class="fas fa-route"></i> Upcoming Timeline</h3>
            </div>
            <div style="display:flex; gap:1.2rem; overflow-x:auto; padding-bottom:1rem; scrollbar-width:thin;">
                <?php 
                for($i=0; $i<14; $i++): 
                    $d = date('Y-m-d', strtotime("+$i days"));
                    $day_name = date('D', strtotime($d));
                    $day_num  = date('d', strtotime($d));
                    $is_today = ($d === $today);
                    $shift    = $calendar_shifts[$d] ?? null;
                    
                    $bg = $is_today ? 'var(--role-accent)' : 'var(--surface-2)';
                    $tc = $is_today ? '#fff' : 'var(--text-primary)';
                ?>
                <div style="flex:0 0 85px; background:<?= $bg ?>; color:<?= $tc ?>; border-radius:16px; padding:1.5rem 1rem; text-align:center; display:flex; flex-direction:column; gap:.8rem; transition:.2s; <?= $shift ? 'box-shadow:0 10px 20px rgba(0,0,0,0.1); border:1.5px solid var(--role-accent);' : 'opacity:.7;' ?>">
                    <div style="font-weight:700; font-size:1.1rem; opacity:.8;"><?= $day_name ?></div>
                    <div style="font-size:2.2rem; font-weight:800; line-height:1;"><?= $day_num ?></div>
                    <?php if($shift): ?>
                        <div style="font-size:1.8rem; margin-top:.4rem;"><i class="fas <?= ['morning'=>'fa-sun','afternoon'=>'fa-cloud-sun','night'=>'fa-moon'][$shift['shift_type']??''] ?? 'fa-clock' ?>"></i></div>
                        <div style="font-size:0.9rem; font-weight:700; text-transform:uppercase;"><?= substr($shift['shift_type']??'Duty',0,3) ?></div>
                    <?php else: ?>
                        <div style="font-size:1.8rem; opacity:.2; margin-top:.4rem;"><i class="fas fa-minus"></i></div>
                        <div style="font-size:0.9rem; font-weight:700; opacity:.5;">OFF</div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Shift Details Card -->
        <div class="card" style="background:var(--surface); overflow:hidden;">
            <div class="card-header" style="background:var(--role-accent); color:#fff; padding:1.8rem 2.5rem; border:none;">
                <h3 style="font-size:1.6rem; font-weight:700; margin:0;"><i class="fas fa-clock"></i> Today's Status</h3>
            </div>
            <div style="padding:2.5rem;">
                <?php 
                $today_shift = $calendar_shifts[$today] ?? null;
                if($today_shift):
                    $st = $today_shift['status'] ?? 'active';
                ?>
                    <div style="display:flex; align-items:center; gap:2rem; margin-bottom:2rem;">
                        <div style="width:70px; height:70px; border-radius:18px; background:rgba(255,255,255,0.1); border:2px solid rgba(255,255,255,0.3); display:flex; align-items:center; justify-content:center; font-size:3rem;">
                           <i class="fas <?= ['morning'=>'fa-sun','afternoon'=>'fa-cloud-sun','night'=>'fa-moon'][$today_shift['shift_type']??''] ?? 'fa-clock' ?>"></i>
                        </div>
                        <div>
                            <div style="font-size:2rem; font-weight:800;"><?= ucfirst($today_shift['shift_type']) ?> Shift</div>
                            <div style="font-size:1.4rem; color:var(--text-secondary);"><?= date('H:i', strtotime($today_shift['start_time'])) ?> — <?= date('H:i', strtotime($today_shift['end_time'])) ?></div>
                        </div>
                    </div>
                    <div style="padding:1.5rem; background:var(--surface-2); border-radius:12px; margin-bottom:1.5rem;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:.8rem;">
                            <span style="font-weight:600; color:var(--text-muted);">Assigned Area</span>
                            <span style="font-weight:800; color:var(--role-accent);"><?= e($today_shift['location_ward_assigned'] ?? 'Facility Wide') ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="font-weight:600; color:var(--text-muted);">Status</span>
                            <span style="font-weight:800; color:var(--success);"><?= ucfirst($st) ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:2rem 0;">
                        <i class="fas fa-couch" style="font-size:5rem; color:var(--role-accent); opacity:.2; display:block; margin-bottom:1.5rem;"></i>
                        <div style="font-size:1.8rem; font-weight:800;">Rest Day</div>
                        <p style="font-size:1.2rem; color:var(--text-muted); margin-top:.5rem;">Enjoy your break! You have no shifts scheduled for today.</p>
                    </div>
                <?php endif; ?>
                
                <button class="btn btn-outline btn-wide" style="margin-top:auto;" onclick="showTab('notifications',null)">
                    <span class="btn-text"><i class="fas fa-bell"></i> Schedule Alerts</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Shift History Table -->
    <div class="card" style="margin-bottom:2.5rem;">
        <div class="card-header" style="background:var(--surface-2); padding:1.5rem 2rem;">
            <h3 style="font-size:1.6rem; font-weight:700;"><i class="fas fa-history"></i> Detailed Shift Roster</h3>
        </div>
        <div class="card-body" style="padding:1.5rem 2rem;">
            <table id="tblShifts" class="display responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Shift Type</th>
                        <th>Duration</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_shifts as $s): 
                        $is_today = ($s['shift_date'] === $today);
                        $st = $s['status'] ?? 'scheduled';
                        $st_class = ['active'=>'ov-pill-success','completed'=>'ov-pill-muted','missed'=>'ov-pill-danger','scheduled'=>'ov-pill-info','swapped'=>'ov-pill-warning'][$st] ?? 'ov-pill-info';
                    ?>
                    <tr <?= $is_today ? 'style="background:rgba(47,128,237,0.05);"' : '' ?>>
                        <td style="font-weight:700;"><?= date('d M Y', strtotime($s['shift_date'])) ?></td>
                        <td><?= date('l', strtotime($s['shift_date'])) ?></td>
                        <td>
                            <i class="fas <?= ['morning'=>'fa-sun','afternoon'=>'fa-cloud-sun','night'=>'fa-moon'][$s['shift_type']??''] ?? 'fa-clock' ?>" style="color:var(--role-accent); margin-right:.5rem;"></i>
                            <?= ucfirst($s['shift_type'] ?? '—') ?>
                        </td>
                        <td style="font-family:monospace; font-weight:600;"><?= date('H:i', strtotime($s['start_time'])) ?> - <?= date('H:i', strtotime($s['end_time'])) ?></td>
                        <td><?= e($s['location_ward_assigned'] ?? '—') ?></td>
                        <td><span class="ov-pill" style="padding:.2rem .8rem;"><?= ucfirst($st) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Leave History -->
    <div class="card">
        <div class="card-header" style="background:var(--surface-2); padding:1.5rem 2rem;">
            <h3 style="font-size:1.6rem; font-weight:700;"><i class="fas fa-umbrella-beach"></i> Leave & Absence History</h3>
        </div>
        <div class="card-body" style="padding:1.5rem 2rem;">
            <table id="tblLeaves" class="display responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Request Date</th>
                        <th>Leave Type</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($leaves as $l): 
                        $lst = strtolower($l['status'] ?? 'pending');
                        $st_c = ['pending'=>'#F39C12', 'approved'=>'#27AE60', 'rejected'=>'#E74C3C'][$lst] ?? '#95A5A6';
                        $days = round((strtotime($l['end_date']) - strtotime($l['start_date'])) / 86400) + 1;
                    ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($l['created_at'])) ?></td>
                        <td><strong><?= ucwords(str_replace('_',' ',$l['leave_type'])) ?></strong></td>
                        <td>
                            <div style="font-weight:700;"><?= $days ?> Day<?= $days>1?'s':'' ?></div>
                            <div style="font-size:1.1rem; color:var(--text-muted);"><?= date('d M', strtotime($l['start_date'])) ?> - <?= date('d M', strtotime($l['end_date'])) ?></div>
                        </td>
                        <td style="max-width:250px;" class="text-truncate" title="<?= e($l['reason']) ?>"><?= e($l['reason']) ?></td>
                        <td>
                            <span class="ov-pill" style="background:color-mix(in srgb, <?= $st_c ?> 13%, transparent 87%); color:<?= $st_c ?>;">
                                <?= ucfirst($lst) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ════════════════ LEAVE REQUEST MODAL ════════════════ -->
<div class="modal-bg" id="mdlLeaveRequest">
    <div class="modal-box" style="max-width:600px;">
        <div class="modal-header" style="background:linear-gradient(135deg, var(--role-accent), var(--role-accent-dark)); color:#fff; border:none; padding:1.8rem 2.5rem;">
            <h3 style="font-size:1.8rem; font-weight:700;"><i class="fas fa-calendar-plus"></i> Submit Leave Request</h3>
            <button class="modal-close" onclick="closeModal('mdlLeaveRequest')" style="background:rgba(255,255,255,0.2); color:#fff; border:none; width:34px; height:34px; border-radius:10px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:2.5rem;">
            <form id="frmLeaveRequest" onsubmit="event.preventDefault(); submitLeaveRequest();">
                <input type="hidden" name="action" value="submit_leave_request">
                
                <div class="form-group" style="margin-bottom:1.8rem;">
                    <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;">Leave Category *</label>
                    <select name="leave_type" class="form-control" required style="padding:1.2rem; border-radius:10px;">
                        <option value="">Select type of leave</option>
                        <option value="annual">Annual Leave (Vacation)</option>
                        <option value="sick">Sick Leave (Medical)</option>
                        <option value="emergency">Emergency Absence</option>
                        <option value="compassionate">Compassionate Leave</option>
                        <option value="maternity">Maternity Leave</option>
                        <option value="paternity">Paternity Leave</option>
                        <option value="unpaid">Unpaid Leave</option>
                        <option value="study">Study Leave / Training</option>
                    </select>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                    <div class="form-group">
                        <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;">Start Date *</label>
                        <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>" style="padding:1.2rem; border-radius:10px;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;">End Date *</label>
                        <input type="date" name="end_date" class="form-control" required min="<?= date('Y-m-d') ?>" style="padding:1.2rem; border-radius:10px;">
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom:2.5rem;">
                    <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;">Justification / Reason *</label>
                    <textarea name="reason" class="form-control" rows="4" required style="resize:none; padding:1.2rem; border-radius:10px;" placeholder="Detailed reason for your leave request..."></textarea>
                </div>
                
                <div style="display:flex; gap:1.2rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('mdlLeaveRequest')" style="flex:1;">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitLeave" style="flex:2; padding:1.3rem; font-weight:700; font-size:1.4rem;">
                        <span class="btn-text">Submit Request</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#tblShifts').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'asc']],
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: { search: "_INPUT_", searchPlaceholder: "Search shifts..." }
        });
        $('#tblLeaves').DataTable({
            responsive: true,
            pageLength: 5,
            order: [[0, 'desc']],
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: { search: "_INPUT_", searchPlaceholder: "Search leave history..." }
        });
    }
});

async function submitLeaveRequest() {
    const btn = document.getElementById('btnSubmitLeave');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    btn.disabled = true;
    
    const fd = new FormData(document.getElementById('frmLeaveRequest'));
    const res = await doAction(fd, "Leave request submitted for administration review.");
    
    if (res) {
        closeModal('mdlLeaveRequest');
        document.getElementById('frmLeaveRequest').reset();
        setTimeout(() => location.reload(), 1500);
    } else {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}
</script>

<style>
/* ── Schedule Specific Pills ── */
.ov-pill-success { background: rgba(39,174,96,0.13); color: #27AE60; }
.ov-pill-danger  { background: rgba(231,76,60,0.13); color: #E74C3C; }
.ov-pill-info    { background: rgba(41,128,185,0.13); color: #2980B9; }
.ov-pill-warning { background: rgba(243,156,18,0.13); color: #F39C12; }
.ov-pill-muted   { background: rgba(149,165,166,0.13); color: #95A5A6; }

.text-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.card { border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
</style>