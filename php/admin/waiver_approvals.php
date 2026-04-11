<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'waiver_approvals';
$page_title  = 'Waiver Approvals';
include '../includes/_sidebar.php';

// Pending Waivers
$pending = [];
$q_pending = mysqli_query($conn, "
    SELECT w.*, 
           u.name as patient_name,
           u.email as patient_email,
           i.invoice_number,
           i.total_amount as invoice_total
    FROM payment_waivers w
    JOIN patients p ON w.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN billing_invoices i ON w.invoice_id = i.invoice_id
    WHERE w.status = 'Pending'
    ORDER BY w.created_at DESC
");
if ($q_pending) while ($row = mysqli_fetch_assoc($q_pending)) $pending[] = $row;

// Recent Waivers
$recent = [];
$q_recent = mysqli_query($conn, "
    SELECT w.*, 
           u.name as patient_name,
           a.name as admin_name
    FROM payment_waivers w
    JOIN patients p ON w.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN users a ON w.approved_by = a.id
    WHERE w.status IN ('Approved', 'Rejected')
    ORDER BY w.approved_at DESC LIMIT 20
");
if ($q_recent) while ($row = mysqli_fetch_assoc($q_recent)) $recent[] = $row;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-hand-holding-usd"></i> Waiver Approvals</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header" style="margin-bottom:2rem;">
            <div>
                <h1>Waiver Approvals</h1>
                <p>Review and authorize financial waivers / discounts for patients.</p>
            </div>
        </div>

        <!-- Pending Queue -->
        <div class="adm-card" style="margin-bottom:3rem;">
            <div class="adm-card-header">
                <h3><i class="fas fa-clock"></i> Pending Requests</h3>
            </div>
            <div class="adm-table-wrap">
                <?php if (empty($pending)): ?>
                    <div style="text-align:center;padding:4rem;color:var(--text-muted);">
                        <i class="fas fa-check-circle" style="font-size:3rem;color:var(--success);margin-bottom:1rem;display:block;"></i>
                        <p style="font-size:1.2rem;font-weight:500;">All clear!</p>
                        <p style="font-size:.9rem;">There are no new waiver requests pending approval.</p>
                    </div>
                <?php else: ?>
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Waiver # / Invoice</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Amount (GHS)</th>
                            <th>Reason</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $p): ?>
                        <tr id="row_<?php echo $p['waiver_id']; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($p['waiver_number']); ?></strong>
                                <br><small style="color:var(--text-muted);">Inv: <?php echo htmlspecialchars($p['invoice_number'] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['patient_name']); ?></strong>
                            </td>
                            <td><span class="adm-badge adm-badge-info"><?php echo htmlspecialchars($p['waiver_type']); ?></span></td>
                            <td>
                                <div>Waived: <strong style="color:var(--success);"><?php echo number_format($p['waived_amount'], 2); ?></strong></div>
                                <div><small>Orig: <?php echo number_format($p['original_amount'], 2); ?></small></div>
                            </td>
                            <td><?php echo htmlspecialchars(substr($p['reason'], 0, 50)); ?>...</td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:.5rem;">
                                    <button class="btn-icon btn btn-success btn-sm" onclick="approveWaiver(<?php echo $p['waiver_id']; ?>)"><span class="btn-text">
                                        <i class="fas fa-check"></i> Approve
                                    </span></button>
                                    <button class="btn-icon btn btn-danger btn-sm" onclick="rejectWaiver(<?php echo $p['waiver_id']; ?>)"><span class="btn-text">
                                        <i class="fas fa-times"></i> Reject
                                    </span></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recently Actioned -->
        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-history"></i> Recent Waiver Actions</h3>
            </div>
            <div class="adm-table-wrap">
                <?php if (empty($recent)): ?>
                    <div style="padding:2rem;text-align:center;color:var(--text-muted);">No recent actions.</div>
                <?php else: ?>
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Waiver #</th>
                            <th>Patient</th>
                            <th>Amount (GHS)</th>
                            <th>Status</th>
                            <th>Actioned By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['waiver_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
                            <td><?php echo number_format($r['waived_amount'], 2); ?></td>
                            <td>
                                <?php if ($r['status'] === 'Approved'): ?>
                                    <span class="adm-badge" style="background:#edfaf1;color:#27ae60;border:1px solid #27ae60;">Approved</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge-danger" title="<?php echo htmlspecialchars($r['rejection_reason'] ?? ''); ?>">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($r['admin_name'] ?? 'System'); ?></td>
                            <td><?php echo date('d M Y g:i A', strtotime($r['approved_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
async function approveWaiver(id) {
    if (!confirm('Are you sure you want to approve this waiver? It will reflect immediately in the patient\'s invoice balance.')) return;
    const fd = new FormData(); fd.append('action', 'approve_waiver'); fd.append('waiver_id', id);

    try {
        const res = await fetch('waiver_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert(data.message); window.location.reload();
        } else alert(data.message || 'Error occurred');
    } catch (e) { alert('Network error'); console.error(e); }
}

async function rejectWaiver(id) {
    const reason = prompt('Please enter a reason for rejection:');
    if (reason === null) return;
    const fd = new FormData(); fd.append('action', 'reject_waiver'); fd.append('waiver_id', id); fd.append('reason', reason);

    try {
        const res = await fetch('waiver_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert(data.message); window.location.reload();
        } else alert(data.message || 'Error occurred');
    } catch (e) { alert('Network error'); console.error(e); }
}
</script>
</body>
</html>
