<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'manage_leaves';
$page_title = 'Leave Requests';
include '../includes/_sidebar.php';

// Fetch leaves
$leaves = [];
$q_leaves = mysqli_query($conn, "
    SELECT lr.*, u.name, u.user_role as role
    FROM staff_leave_requests lr
    JOIN staff s ON lr.staff_id = s.id
    JOIN users u ON s.user_id = u.id
    ORDER BY lr.applied_at DESC LIMIT 100
");
if ($q_leaves)
    while ($r = mysqli_fetch_assoc($q_leaves))
        $leaves[] = $r;

$pending = array_filter($leaves, fn($l) => $l['status'] === 'pending');
$actioned = array_filter($leaves, fn($l) => $l['status'] !== 'pending');
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-umbrella-beach"></i> Leave Requests</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header" style="margin-bottom:2rem;">
            <div>
                <h1>Leave & Absence Management</h1>
                <p>Review and act upon staff leave requests.</p>
            </div>
            <div style="background:var(--bg-card);padding:.5rem 1rem;border-radius:12px;border:1px solid var(--border);">
                <div style="font-size:.8rem;color:var(--text-muted);">Pending</div>
                <div style="font-size:1.5rem;font-weight:700;color:var(--primary);"><?php echo count($pending); ?></div>
            </div>
        </div>

        <div class="adm-card" style="margin-bottom:3rem;">
            <div class="adm-card-header"><h3><i class="fas fa-clock"></i> Action Required (Pending)</h3></div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Staff Member</th><th>Leave Type</th><th>Dates</th><th>Reason</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (empty($pending)): ?>
                            <tr><td colspan="5" style="padding:2.5rem;text-align:center;color:var(--text-muted);">No pending leave requests.</td></tr>
                        <?php
else:
    foreach ($pending as $l): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($l['name']); ?></strong><br>
                                <small style="color:var(--text-muted);text-transform:uppercase;"><?php echo str_replace('_', ' ', $l['role']); ?></small>
                            </td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo ucfirst(str_replace('_', ' ', $l['leave_type'])); ?></span></td>
                            <td>
                                <div><i class="far fa-calendar-alt"></i> <?php echo date('M d', strtotime($l['start_date'])) . ' - ' . date('M d, Y', strtotime($l['end_date'])); ?></div>
                                <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;">Requested: <?php echo date('d M Y', strtotime($l['applied_at'])); ?></div>
                            </td>
                            <td style="max-width:250px;">
                                <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($l['reason']); ?>">
                                    <?php echo htmlspecialchars($l['reason']); ?>
                                </div>
                            </td>
                            <td>
                                <div style="display:inline-flex;gap:.5rem;">
                                    <button class="adm-btn adm-btn-success adm-btn-sm" onclick="approveLeave(<?php echo $l['leave_id']; ?>)" title="Approve"><i class="fas fa-check"></i></button>
                                    <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="rejectLeave(<?php echo $l['leave_id']; ?>)" title="Reject"><i class="fas fa-times"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php
    endforeach;
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="adm-card">
            <div class="adm-card-header"><h3><i class="fas fa-history"></i> Recently Actioned</h3></div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Staff Member</th><th>Leave Type</th><th>Dates</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($actioned)): ?>
                            <tr><td colspan="4" style="padding:2.5rem;text-align:center;color:var(--text-muted);">No history.</td></tr>
                        <?php
else:
    foreach (array_slice($actioned, 0, 15) as $l): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($l['name']); ?></strong></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $l['leave_type'])); ?></td>
                            <td><?php echo date('d M', strtotime($l['start_date'])) . ' - ' . date('d M Y', strtotime($l['end_date'])); ?></td>
                            <td>
                                <?php if ($l['status'] === 'approved'): ?>
                                    <span class="adm-badge adm-badge-success">Approved</span>
                                <?php
        else: ?>
                                    <span class="adm-badge adm-badge-danger" title="<?php echo htmlspecialchars($l['admin_notes'] ?? ''); ?>">Rejected</span>
                                <?php
        endif; ?>
                            </td>
                        </tr>
                        <?php
    endforeach;
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>
<script>
const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});

async function approveLeave(id) {
    if (!confirm('Approve this leave request?')) return;
    const fd = new FormData(); fd.append('action', 'approve_leave'); fd.append('leave_id', id);
    try {
        const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) { alert('Approved!'); window.location.reload(); } else alert(data.message);
    } catch(err) { alert('Error'); }
}
async function rejectLeave(id) {
    const reason = prompt('Reason for rejection:');
    if (reason === null) return;
    const fd = new FormData(); fd.append('action', 'reject_leave'); fd.append('leave_id', id); fd.append('reason', reason);
    try {
        const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) { alert('Rejected!'); window.location.reload(); } else alert(data.message);
    } catch(err) { alert('Error'); }
}
</script>
</body>
</html>