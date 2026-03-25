<?php
// ============================================================
// LAB DASHBOARD - TAB ORDERS (Module 2)
// ============================================================
if (!isset($user_id)) { exit; }

// Fetch Filters
$status_filter = $_GET['filter_status'] ?? 'All';

// Build Query
$query = "SELECT o.id, o.urgency, o.created_at, o.required_by_date, o.order_status,
                 c.test_name, p.full_name AS patient_name, 
                 d.full_name AS doctor_name
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
    if($u==='STAT') return '<span class="adm-badge" style="background:var(--danger);color:white;">STAT</span>';
    if($u==='Critical') return '<span class="adm-badge" style="background:#8b0000;color:white;">Critical</span>';
    if($u==='Urgent') return '<span class="adm-badge adm-badge-warning">Urgent</span>';
    return '<span class="adm-badge adm-badge-success">Routine</span>';
}
function getTestStatusBadge($s) {
    switch($s) {
        case 'Pending': return '<span class="adm-badge adm-badge-warning">Pending</span>';
        case 'Accepted': return '<span class="adm-badge adm-badge-primary">Accepted</span>';
        case 'Sample Collected': return '<span class="adm-badge adm-badge-info">Sample Collected</span>';
        case 'Processing': return '<span class="adm-badge adm-badge-teal">Processing</span>';
        case 'Completed': return '<span class="adm-badge adm-badge-success">Completed</span>';
        case 'Rejected': return '<span class="adm-badge adm-badge-danger">Rejected</span>';
        default: return '<span class="adm-badge">'.e($s).'</span>';
    }
}
?>

<div class="sec-header">
    <h2><i class="fas fa-notes-medical"></i> Test Order Management</h2>
    <div style="display:flex; gap:1rem; align-items:center;">
        <select class="form-select" id="statusFilter" style="width:200px; display:inline-block;" onchange="window.location.href='?tab=orders&filter_status='+this.value">
            <option value="All" <?= $status_filter=='All'?'selected':'' ?>>All Statuses</option>
            <option value="Pending" <?= $status_filter=='Pending'?'selected':'' ?>>Pending</option>
            <option value="Accepted" <?= $status_filter=='Accepted'?'selected':'' ?>>Accepted</option>
            <option value="Processing" <?= $status_filter=='Processing'?'selected':'' ?>>Processing</option>
            <option value="Completed" <?= $status_filter=='Completed'?'selected':'' ?>>Completed</option>
        </select>
        <button class="adm-btn adm-btn-primary" onclick="refreshTable()"><i class="fas fa-sync-alt"></i> Refresh Queue</button>
    </div>
</div>

<div class="info-card">
    <div class="adm-table-wrap">
        <table id="ordersTable" class="adm-table display">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Patient Name</th>
                    <th>Doctor</th>
                    <th>Test Name</th>
                    <th>Urgency</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($orders_res)): ?>
                <tr>
                    <td><strong>#ORD-<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                    <td><?= e($row['patient_name']) ?></td>
                    <td>Dr. <?= e($row['doctor_name']) ?></td>
                    <td><span style="font-weight:600; color:var(--text-primary);"><?= e($row['test_name']) ?></span></td>
                    <td><?= getUrgencyBadge($row['urgency']) ?></td>
                    <td>
                        <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?><br>
                        <?php if($row['required_by_date']): ?>
                            <small style="color:var(--danger); font-weight:500;"><i class="far fa-clock"></i> Req By: <?= date('d M Y', strtotime($row['required_by_date'])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= getTestStatusBadge($row['order_status']) ?></td>
                    <td>
                        <div class="action-btns">
                            <button class="adm-btn adm-btn-sm" style="background:var(--surface-2);color:var(--text-primary);" onclick="viewOrder(<?= $row['id'] ?>)"><i class="fas fa-eye"></i> View</button>
                            
                            <?php if($row['order_status'] === 'Pending'): ?>
                                <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="processOrder(<?= $row['id'] ?>, 'Accepted')"><i class="fas fa-check"></i> Accept</button>
                                <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="rejectOrder(<?= $row['id'] ?>)"><i class="fas fa-times"></i> Reject</button>
                            <?php endif; ?>
                            
                            <?php if($row['order_status'] === 'Accepted'): ?>
                                <button class="adm-btn adm-btn-sm" style="background:var(--role-accent); color:#fff;" onclick="processOrder(<?= $row['id'] ?>, 'Processing')"><i class="fas fa-vials"></i> Mark Processing</button>
                            <?php endif; ?>

                            <?php if($row['order_status'] === 'Processing'): ?>
                                <button class="adm-btn adm-btn-success adm-btn-sm" onclick="window.location.href='?tab=results&order_id=<?= $row['id'] ?>'"><i class="fas fa-edit"></i> Enter Results</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg); border:none; box-shadow:0 15px 35px rgba(0,0,0,0.2);">
            <div class="modal-header" style="border-bottom:1px solid var(--border); padding:1.5rem 2rem;">
                <h5 class="modal-title" style="font-weight:700; font-size:1.4rem;"><i class="fas fa-times-circle" style="color:var(--danger); margin-right:.5rem;"></i> Reject Test Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body" style="padding:2rem;">
                <input type="hidden" id="rej_order_id">
                <div class="form-group mb-0">
                    <label style="font-size:1.1rem; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Reason for Rejection <span style="color:var(--danger);">*</span></label>
                    <textarea id="rej_reason" class="form-control" rows="4" placeholder="Specify why the order is rejected (e.g., test unavailable, patient condition changed)..." style="font-size:1.2rem; padding:1.2rem; resize:none;"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border); padding:1.5rem 2rem;">
                <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="adm-btn adm-btn-danger" onclick="submitRejection()"><i class="fas fa-trash-alt"></i> Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#ordersTable').DataTable({
        pageLength: 10,
        order: [[5, 'desc']], // Sort by Order Date descending
        language: { search: "", searchPlaceholder: "Search orders..." }
    });
});

function refreshTable() {
    window.location.reload();
}

function processOrder(id, status) {
    if(!confirm('Are you sure you want to change this order status to ' + status + '?')) return;
    
    $.ajax({
        url: 'lab_actions.php',
        type: 'POST',
        data: {
            action: 'update_order_status',
            order_id: id,
            status: status,
            csrf_token: '<?= $csrf_token ?>'
        },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                alert('Success: Order status updated to ' + status);
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        }
    });
}

function rejectOrder(id) {
    $('#rej_order_id').val(id);
    $('#rej_reason').val('');
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function submitRejection() {
    const id = $('#rej_order_id').val();
    const reason = $('#rej_reason').val().trim();
    if(!reason) return alert("Rejection reason is mandatory.");

    $.ajax({
        url: 'lab_actions.php',
        type: 'POST',
        data: {
            action: 'update_order_status',
            order_id: id,
            status: 'Rejected',
            rejection_reason: reason,
            csrf_token: '<?= $csrf_token ?>'
        },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                alert('Order rejected and doctor notified.');
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        }
    });
}

function viewOrder(id) {
    alert("Full Order Preview for ID #" + id + " \n[Modal Interface to be built out here]");
}
</script>
