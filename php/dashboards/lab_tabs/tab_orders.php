<?php
// ============================================================
// LAB DASHBOARD - TAB ORDERS (PREMIUM UI REWRITE)
// ============================================================
if (!isset($user_id)) { exit; }

// Fetch Filters
$status_filter = $_GET['filter_status'] ?? 'All';

// Build Query
$query = "SELECT o.id, o.urgency, o.created_at, o.required_by_date, o.order_status,
                 c.test_name, p.full_name AS patient_name, p.patient_id as patient_code,
                 d.full_name AS doctor_name, o.clinical_notes
          FROM lab_test_orders o
          JOIN lab_test_catalog c ON o.test_catalog_id = c.id
          JOIN patients p ON o.patient_id = p.id
          JOIN doctors d ON o.doctor_id = d.id";

if ($status_filter !== 'All') {
    $query .= " WHERE o.order_status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}
$query .= " ORDER BY o.created_at DESC";
$orders_res = mysqli_query($conn, $query);

function getUrgencyBadge($u) {
    if($u==='STAT') return '<span class="adm-badge" style="background:var(--danger);color:white;box-shadow:0 0 10px rgba(231,76,60,0.4);"><i class="fas fa-exclamation-triangle"></i> STAT</span>';
    if($u==='Critical') return '<span class="adm-badge" style="background:#8b0000;color:white;"><i class="fas fa-radiation"></i> Critical</span>';
    if($u==='Urgent') return '<span class="adm-badge" style="background:var(--warning);color:#fff;"><i class="fas fa-clock"></i> Urgent</span>';
    return '<span class="adm-badge" style="background:var(--surface-2);color:var(--text-secondary);"><i class="fas fa-check-circle"></i> Routine</span>';
}
function getTestStatusBadge($s) {
    $c = 'var(--text-secondary)'; $bg = 'var(--surface-2)'; $ic = 'fa-circle';
    if($s==='Pending') { $bg='rgba(245,158,11,0.1)'; $c='#f59e0b'; $ic='fa-hourglass-start'; }
    if($s==='Accepted') { $bg='rgba(59,130,246,0.1)'; $c='#3b82f6'; $ic='fa-check-double'; }
    if($s==='Sample Collected') { $bg='rgba(14,165,233,0.1)'; $c='#0ea5e9'; $ic='fa-vial'; }
    if($s==='Processing') { $bg='rgba(13,148,136,0.1)'; $c='#0d9488'; $ic='fa-microscope fa-spin'; }
    if($s==='Completed') { $bg='rgba(34,197,94,0.1)'; $c='#22c55e'; $ic='fa-check-circle'; }
    if($s==='Rejected') { $bg='rgba(244,63,94,0.1)'; $c='#f43f5e'; $ic='fa-times-circle'; }
    return "<span class=\"adm-badge\" style=\"background:$bg; color:$c; font-weight:700;\"><i class=\"fas $ic\" style=\"margin-right:4px;\"></i>$s</span>";
}
?>

<div class="tab-content <?= ($active_tab === 'orders') ? 'active' : '' ?>" id="orders">

    <div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.01)); border:1px solid rgba(47,128,237,0.15); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.5rem;">
                <i class="fas fa-notes-medical" style="color:var(--role-accent); margin-right:.8rem;"></i> Test Order Management
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted); margin:0;">Process, validate, and track laboratory investigation requests from clinical departments.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center; margin-top:1.5rem;">
            <select class="form-control" id="statusFilter" style="width:250px; font-weight:700; border:2px solid var(--border);" onchange="window.location.href='?tab=orders&filter_status='+this.value">
                <option value="All" <?= $status_filter=='All'?'selected':'' ?>>All Order Statuses</option>
                <option value="Pending" <?= $status_filter=='Pending'?'selected':'' ?>>⏳ Pending Review</option>
                <option value="Accepted" <?= $status_filter=='Accepted'?'selected':'' ?>>✅ Accepted / Awaiting Sample</option>
                <option value="Processing" <?= $status_filter=='Processing'?'selected':'' ?>>🔬 Interventions in Progress</option>
                <option value="Completed" <?= $status_filter=='Completed'?'selected':'' ?>>🎉 Completed</option>
            </select>
            <button class="adm-btn adm-adm-btn adm-btn-primary" onclick="window.location.reload();" style="border-radius:10px; font-weight:800;"><span class="btn-text"><i class="fas fa-sync-alt"></i> Refresh Queue</span></button>
        </div>
    </div>

    <div class="adm-card shadow-sm" style="border-radius:16px;">
        <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-list-ul" style="margin-right:.5rem; color:var(--role-accent);"></i> Central Clinical Register</h3>
        </div>
        <div class="adm-card-body" style="padding:1rem;">
            <div class="adm-table-wrap">
                <table id="ordersTable" class="adm-table">
                    <thead>
                        <tr>
                            <th>Order Details</th>
                            <th>Patient Identity</th>
                            <th>Attending Physician</th>
                            <th>Priority Protocol</th>
                            <th>Order Timeline</th>
                            <th>System Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($orders_res)): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--primary); font-size:1.2rem;">#ORD-<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></strong><br>
                                <span style="font-weight:700; color:var(--text-primary); font-size:1.3rem; margin-top:.4rem; display:block;"><?= e($row['test_name']) ?></span>
                            </td>
                            <td>
                                <span style="font-weight:800; color:var(--text-primary);"><?= e($row['patient_name']) ?></span><br>
                                <span style="font-size:1.1rem; color:var(--text-muted);"><i class="fas fa-id-card"></i> <?= e($row['patient_code']) ?></span>
                            </td>
                            <td style="font-weight:600; color:var(--text-secondary);">Dr. <?= e($row['doctor_name']) ?></td>
                            <td><?= getUrgencyBadge($row['urgency']) ?></td>
                            <td>
                                <span style="font-weight:600;"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></span><br>
                                <?php if($row['required_by_date']): ?>
                                    <small style="color:var(--danger); font-weight:700;"><i class="far fa-clock"></i> Req By: <?= date('d M Y', strtotime($row['required_by_date'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= getTestStatusBadge($row['order_status']) ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="adm-btn adm-btn-ghost btn-icon" title="View Detail" onclick="viewOrderDetails(<?= htmlspecialchars(json_encode($row)) ?>)"><span class="btn-text"><i class="fas fa-eye text-primary"></i></span></button>
                                    
                                    <?php if($row['order_status'] === 'Pending'): ?>
                                        <button class="adm-btn adm-adm-btn adm-btn-primary btn-sm" onclick="processOrder(<?= $row['id'] ?>, 'Accepted')" style="border-radius:8px;"><span class="btn-text"><i class="fas fa-check"></i> Accept</span></button>
                                        <button class="adm-btn adm-btn-ghost text-danger btn-sm" onclick="showRejectModal(<?= $row['id'] ?>)" style="border-radius:8px; border:1px solid rgba(231,76,60,0.2);"><span class="btn-text"><i class="fas fa-times"></i> Deny</span></button>
                                    <?php endif; ?>
                                    
                                    <?php if($row['order_status'] === 'Accepted'): ?>
                                        <button class="adm-btn btn-sm" style="background:var(--primary); color:#fff; border-radius:8px;" onclick="processOrder(<?= $row['id'] ?>, 'Processing')"><span class="btn-text"><i class="fas fa-flask"></i> Mark Processing</span></button>
                                    <?php endif; ?>

                                    <?php if($row['order_status'] === 'Processing'): ?>
                                        <button class="adm-btn btn-sm" style="background:var(--warning); color:#fff; border-radius:8px;" onclick="window.location.href='?tab=results&order_id=<?= $row['id'] ?>&auto_open=true'"><span class="btn-text"><i class="fas fa-edit"></i> Result Data</span></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ORDER DETAILS                       -->
<!-- ========================================== -->
<div class="modal-bg" id="orderDetailModal">
    <div class="modal-box" style="max-width:600px; padding:0; overflow:hidden;">
        <div class="modal-header" style="background:linear-gradient(135deg,#1C3A6B,var(--primary)); padding:2rem 3rem; margin:0;">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-microscope"></i> Clinical Order Synopsis</h3>
            <button class="adm-btn modal-close" onclick="document.getElementById('orderDetailModal').style.display='none'" type="button" style="color:#fff; background:transparent; font-size:2rem; padding:0;"><span class="btn-text">&times;</span></button>
        </div>
        <div style="padding:3rem;">
            <div style="font-size:1.3rem; line-height:1.8; color:var(--text-primary);">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
                    <div><span style="color:var(--text-muted); font-weight:600; text-transform:uppercase; font-size:1.1rem; display:block;">Order Tracking ID</span><strong style="font-size:1.5rem; color:var(--role-accent);" id="vd_id"></strong></div>
                    <div><span style="color:var(--text-muted); font-weight:600; text-transform:uppercase; font-size:1.1rem; display:block;">Test Panel</span><strong style="font-size:1.5rem;" id="vd_test"></strong></div>
                </div>
                <div style="margin-bottom:1.5rem;"><span style="color:var(--text-muted); font-weight:600; text-transform:uppercase; font-size:1.1rem; display:block;">Patient Details</span><strong style="font-size:1.4rem;" id="vd_patient"></strong></div>
                <div style="margin-bottom:1.5rem;"><span style="color:var(--text-muted); font-weight:600; text-transform:uppercase; font-size:1.1rem; display:block;">Requisitioning Physician</span><strong style="font-size:1.4rem;" id="vd_doctor"></strong></div>
                <div style="margin-bottom:2rem; background:rgba(245,158,11,0.05); border-left:4px solid var(--warning); padding:1rem 1.5rem;"><span style="color:var(--warning); font-weight:800; text-transform:uppercase; font-size:1.1rem; display:block;"><i class="fas fa-sticky-note"></i> Clinical Instructions / Notes</span><span style="font-weight:600;" id="vd_notes"></span></div>
            </div>
            <div style="display:flex; justify-content:flex-end; padding-top:1.5rem; border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-adm-btn adm-btn-primary" onclick="document.getElementById('orderDetailModal').style.display='none'" style="border-radius:8px;"><span class="btn-text">Close Panel</span></button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: REJECT ORDER                        -->
<!-- ========================================== -->
<div class="modal-bg" id="rejectModal">
    <div class="modal-box" style="max-width:550px; padding:0; overflow:hidden;">
        <div class="modal-header" style="background:var(--danger); padding:2rem 3rem; margin:0;">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-ban text-white"></i> Process Rejection Protocol</h3>
            <button class="adm-btn modal-close" onclick="document.getElementById('rejectModal').style.display='none'" type="button" style="color:#fff; background:transparent; font-size:2rem; padding:0;"><span class="btn-text">&times;</span></button>
        </div>
        <div style="padding:3rem;">
            <input type="hidden" id="rej_order_id">
            <div class="form-group" style="margin-bottom:2rem;">
                <label style="font-size:1.2rem; font-weight:800; color:var(--danger); margin-bottom:.8rem; display:block; text-transform:uppercase;">Reason for Rejection <span style="color:var(--danger);">*</span></label>
                <div style="display:flex;gap:.8rem;margin-bottom:1rem;overflow-x:auto;">
                    <span class="adm-badge" style="background:var(--surface-2);color:var(--text-secondary);cursor:pointer;" onclick="document.getElementById('rej_reason').value='Test unavailable temporarily.'">Test Unavailable</span>
                    <span class="adm-badge" style="background:var(--surface-2);color:var(--text-secondary);cursor:pointer;" onclick="document.getElementById('rej_reason').value='Incorrect processing protocol requested.'">Wrong Protocol</span>
                </div>
                <textarea id="rej_reason" class="form-control" rows="4" placeholder="Specify why the order cannot be fulfilled... (This will notify the doctor)" style="font-size:1.3rem; padding:1.2rem; font-weight:500; border:2px solid rgba(231,76,60,0.3); border-radius:10px;"></textarea>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:1.5rem; border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('rejectModal').style.display='none'" style="font-weight:700;"><span class="btn-text">Cancel</span></button>
                <button type="button" class="adm-btn" style="background:var(--danger); color:#fff; font-weight:800; border-radius:10px;" onclick="submitRejection()"><span class="btn-text"><i class="fas fa-trash-alt" style="margin-right:.5rem;"></i> Confirmed Rejection</span></button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#ordersTable').DataTable({
        pageLength: 15,
        order: [[4, 'desc']], // Sort by Timeline descending
        language: { search: "", searchPlaceholder: "Search patient, ID, or test..." }
    });
});

function viewOrderDetails(data) {
    $('#vd_id').text('#ORD-' + String(data.id).padStart(5, '0'));
    $('#vd_test').text(data.test_name);
    $('#vd_patient').text(`${data.patient_name} (${data.patient_code})`);
    $('#vd_doctor').text(`Dr. ${data.doctor_name}`);
    $('#vd_notes').text(data.clinical_notes || 'No specific clinical instructions provided.');
    document.getElementById('orderDetailModal').style.display = 'flex';
}

function processOrder(id, status) {
    if(!confirm('Affirm state transition to ' + status.toUpperCase() + '?')) return;
    $.post('lab_actions.php', {
        action: 'update_order_status',
        order_id: id,
        status: status,
        _csrf: '<?= $csrf_token ?>'
    }, function(res) {
        if(res.success) {
            window.location.reload();
        } else {
            alert('Encountered an anomaly: ' + res.message);
        }
    }, 'json');
}

function showRejectModal(id) {
    $('#rej_order_id').val(id);
    $('#rej_reason').val('');
    document.getElementById('rejectModal').style.display = 'flex';
}

function submitRejection() {
    const rsn = $('#rej_reason').val().trim();
    if(!rsn) { alert('A justification narrative is strictly required for rejection.'); return; }
    
    $.post('lab_actions.php', {
        action: 'update_order_status',
        order_id: $('#rej_order_id').val(),
        status: 'Rejected',
        notes: rsn,
        _csrf: '<?= $csrf_token ?>'
    }, function(res) {
        if(res.success) {
            window.location.reload();
        } else {
            alert('Rejection sequence failed: ' + res.message);
        }
    }, 'json');
}
</script>
