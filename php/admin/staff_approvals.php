<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'staff_approvals';
$page_title  = 'Pending Staff Approvals';
include '../includes/_sidebar.php';

// Fetch pending registrations
$pending = [];
$q_pending = mysqli_query($conn, "
    SELECT s.id as staff_id, s.employee_id, s.role, u.name, u.email, u.phone, u.created_at
    FROM staff s
    JOIN users u ON s.user_id = u.id
    WHERE s.approval_status = 'pending'
    ORDER BY u.created_at DESC
");
if ($q_pending) while ($row = mysqli_fetch_assoc($q_pending)) $pending[] = $row;

// Fetch recently actioned (approved/rejected in last 7 days)
$recent = [];
$q_recent = mysqli_query($conn, "
    SELECT s.id as staff_id, s.employee_id, s.role, s.approval_status, s.rejection_reason, s.approved_at,
           u.name as staff_name, ua.name as admin_name
    FROM staff s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN users ua ON s.approved_by = ua.id
    WHERE s.approval_status IN ('approved','rejected')
      AND s.approved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY s.approved_at DESC LIMIT 15
");
if ($q_recent) while ($row = mysqli_fetch_assoc($q_recent)) $recent[] = $row;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-clipboard-check"></i> Pending Approvals</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header" style="margin-bottom:2rem;">
            <div>
                <h1>Pending Applications</h1>
                <p>Review and approve new staff member registrations.</p>
            </div>
            <div style="background:var(--bg-card);padding:.75rem 1.5rem;border-radius:12px;box-shadow:var(--shadow-sm);border:1px solid var(--border);">
                <div style="font-size:.85rem;color:var(--text-muted);">Awaiting Review</div>
                <div style="font-size:1.8rem;font-weight:700;color:var(--primary);line-height:1.2;"><?php echo count($pending); ?></div>
            </div>
        </div>

        <!-- Pending Queue -->
        <div class="adm-card" style="margin-bottom:3rem;">
            <div class="adm-card-header">
                <h3><i class="fas fa-clock"></i> Action Required</h3>
            </div>
            <div class="adm-table-wrap">
                <?php if (empty($pending)): ?>
                    <div style="text-align:center;padding:4rem;color:var(--text-muted);">
                        <i class="fas fa-check-circle" style="font-size:3rem;color:var(--success);margin-bottom:1rem;display:block;"></i>
                        <p style="font-size:1.2rem;font-weight:500;">All caught up!</p>
                        <p style="font-size:.9rem;">There are no new staff registrations pending approval.</p>
                    </div>
                <?php else: ?>
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Applied Role</th>
                            <th>Contact</th>
                            <th>Registration Date</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $p): 
                            $role_lbl = ucfirst(str_replace('_',' ',$p['role']));
                            $role_colors = ['ambulance_driver'=>'primary','cleaner'=>'info','laundry_staff'=>'warning','maintenance'=>'success','security'=>'danger','kitchen_staff'=>'warning'];
                            $rc = $role_colors[$p['role']] ?? 'secondary';
                        ?>
                        <tr id="row_<?php echo $p['staff_id']; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                <br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($p['employee_id']); ?></small>
                            </td>
                            <td><span class="adm-badge adm-badge-<?php echo $rc; ?>"><?php echo $role_lbl; ?></span></td>
                            <td>
                                <div><i class="fas fa-envelope" style="color:var(--text-muted);width:16px;"></i> <?php echo htmlspecialchars($p['email']); ?></div>
                                <div><i class="fas fa-phone" style="color:var(--text-muted);width:16px;"></i> <?php echo htmlspecialchars($p['phone']); ?></div>
                            </td>
                            <td><?php echo date('M d, Y g:i A', strtotime($p['created_at'])); ?></td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:.5rem;">
                                    <button class="adm-btn adm-btn-success adm-btn-sm" onclick="approveStaff(<?php echo $p['staff_id']; ?>)">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="rejectStaff(<?php echo $p['staff_id']; ?>)">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
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
                <h3><i class="fas fa-history"></i> Recent Approvals/Rejections (Last 7 Days)</h3>
            </div>
            <div class="adm-table-wrap">
                <?php if (empty($recent)): ?>
                    <div style="padding:2rem;text-align:center;color:var(--text-muted);">No recent actions.</div>
                <?php else: ?>
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actioned By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($r['staff_name']); ?></strong><br>
                                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($r['employee_id']); ?></small>
                            </td>
                            <td><?php echo ucfirst(str_replace('_',' ',$r['role'])); ?></td>
                            <td>
                                <?php if ($r['approval_status'] === 'approved'): ?>
                                    <span class="adm-badge" style="background:#edfaf1;color:#27ae60;border:1px solid #27ae60;">Approved</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge-danger" title="<?php echo htmlspecialchars($r['rejection_reason'] ?? ''); ?>">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($r['admin_name'] ?? 'System'); ?></td>
                            <td><?php echo date('d M g:i A', strtotime($r['approved_at'])); ?></td>
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
async function approveStaff(id) {
    if (!confirm('Are you sure you want to approve this application? They will gain dashboard access immediately.')) return;
    const fd = new FormData();
    fd.append('action', 'approve_staff');
    fd.append('staff_id', id);

    try {
        const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            document.getElementById('row_'+id).remove();
            window.location.reload();
        } else alert(data.message || 'Error occurred');
    } catch (e) { alert('Network error'); console.error(e); }
}

async function rejectStaff(id) {
    const reason = prompt('Please enter a reason for rejection (this will be shown to the applicant):');
    if (reason === null) return;
    
    const fd = new FormData();
    fd.append('action', 'reject_staff');
    fd.append('staff_id', id);
    fd.append('reason', reason);

    try {
        const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            document.getElementById('row_'+id).remove();
            window.location.reload();
        } else alert(data.message || 'Error occurred');
    } catch (e) { alert('Network error'); console.error(e); }
}

const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
</body>
</html>
