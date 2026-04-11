<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'budget_approvals';
$page_title  = 'Budget Approvals';
include '../includes/_sidebar.php';

// Pending Budgets
$pending = [];
$q_pending = mysqli_query($conn, "
    SELECT b.*, 
           u.name as creator_name
    FROM budget_allocations b
    LEFT JOIN users u ON b.created_by = u.id
    WHERE b.status = 'Draft'
    ORDER BY b.created_at DESC
");
if ($q_pending) while ($row = mysqli_fetch_assoc($q_pending)) $pending[] = $row;

// Recent Actions
$recent = [];
$q_recent = mysqli_query($conn, "
    SELECT b.*, 
           u.name as creator_name,
           a.name as admin_name
    FROM budget_allocations b
    LEFT JOIN users u ON b.created_by = u.id
    LEFT JOIN users a ON b.approved_by = a.id
    WHERE b.status IN ('Active', 'Rejected', 'Revised')
    ORDER BY b.approved_at DESC LIMIT 20
");
if ($q_recent) while ($row = mysqli_fetch_assoc($q_recent)) $recent[] = $row;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-file-invoice-dollar"></i> Budget Approvals</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header" style="margin-bottom:2rem;">
            <div>
                <h1>Budget Authorizations</h1>
                <p>Review and authorize departmental financial budgets.</p>
            </div>
        </div>

        <div class="adm-card" style="margin-bottom:3rem;">
            <div class="adm-card-header">
                <h3><i class="fas fa-clock"></i> Draft Budgets Pending Approval</h3>
            </div>
            <div class="adm-table-wrap">
                <?php if (empty($pending)): ?>
                    <div style="text-align:center;padding:4rem;color:var(--text-muted);">
                        <i class="fas fa-check-circle" style="font-size:3rem;color:var(--success);margin-bottom:1rem;display:block;"></i>
                        <p style="font-size:1.2rem;font-weight:500;">All clear!</p>
                        <p style="font-size:.9rem;">No budgets are pending authorization.</p>
                    </div>
                <?php else: ?>
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Fiscal Year / Period</th>
                            <th>Amount (GHS)</th>
                            <th>Notes</th>
                            <th>Proposed By</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $p): ?>
                        <tr id="row_<?php echo $p['allocation_id']; ?>">
                            <td><strong><?php echo htmlspecialchars($p['department']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['fiscal_year'] . ' - ' . $p['fiscal_period']); ?></td>
                            <td style="font-size:1.1rem;font-weight:bold;color:var(--text-primary);">
                                <?php echo number_format($p['allocated_amount'], 2); ?>
                            </td>
                            <td><?php echo htmlspecialchars(substr($p['notes'] ?? '', 0, 50)); ?>...</td>
                            <td><?php echo htmlspecialchars($p['creator_name']); ?></td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:.5rem;">
                                    <button class="btn-icon btn btn-success btn-sm" onclick="approveBudget(<?php echo $p['allocation_id']; ?>)"><span class="btn-text">
                                        <i class="fas fa-check"></i> Approve
                                    </span></button>
                                    <button class="btn-icon btn btn-danger btn-sm" onclick="rejectBudget(<?php echo $p['allocation_id']; ?>)"><span class="btn-text">
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

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-history"></i> Recent Processed Budgets</h3>
            </div>
            <div class="adm-table-wrap">
                <?php if (empty($recent)): ?>
                    <div style="padding:2rem;text-align:center;color:var(--text-muted);">No recent actions.</div>
                <?php else: ?>
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Amount (GHS)</th>
                            <th>Status</th>
                            <th>Actioned By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($r['department']); ?></strong><br>
                                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($r['fiscal_year'] . ' ' . $r['fiscal_period']); ?></small>
                            </td>
                            <td><?php echo number_format($r['allocated_amount'], 2); ?></td>
                            <td>
                                <?php if ($r['status'] === 'Active'): ?>
                                    <span class="adm-badge" style="background:#edfaf1;color:#27ae60;border:1px solid #27ae60;">Active</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge-danger"><?php echo htmlspecialchars($r['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($r['admin_name'] ?? 'System'); ?></td>
                            <td><?php echo date('d M Y', strtotime($r['approved_at'] ?? $r['updated_at'])); ?></td>
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
async function approveBudget(id) {
    if (!confirm('Are you sure you want to approve this budget and make it active?')) return;
    const fd = new FormData(); fd.append('action', 'approve_budget'); fd.append('budget_id', id);

    try {
        const res = await fetch('budget_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert(data.message); window.location.reload();
        } else alert(data.message || 'Error occurred');
    } catch (e) { alert('Network error'); console.error(e); }
}

async function rejectBudget(id) {
    const reason = prompt('Please enter a reason for rejection or revision prompt:');
    if (reason === null) return;
    const fd = new FormData(); fd.append('action', 'reject_budget'); fd.append('budget_id', id); fd.append('reason', reason);

    try {
        const res = await fetch('budget_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert(data.message); window.location.reload();
        } else alert(data.message || 'Error occurred');
    } catch (e) { alert('Network error'); console.error(e); }
}
</script>
</body>
</html>
