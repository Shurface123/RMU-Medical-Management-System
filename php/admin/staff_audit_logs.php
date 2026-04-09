<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'staff_audit_logs';
$page_title  = 'Registration Audit Logs';
include '../includes/_sidebar.php';

// Fetch registration logs
$stmt = mysqli_query($conn, "
    SELECT a.audit_id, a.user_id, a.action, a.notes, a.performed_by, a.ip_address, a.created_at, u.name as user_name, u.email as user_email, u.user_role 
    FROM user_registration_audit a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 100
");
$logs = [];
if ($stmt) {
    while ($row = mysqli_fetch_assoc($stmt)) {
        $logs[] = $row;
    }
}

// Fetch logout logs
$stmt2 = mysqli_query($conn, "
    SELECT l.log_id as audit_id, l.user_id, l.logout_type as action, CONCAT('Device: ', IFNULL(l.device_info,'?'), ' / Browser: ', IFNULL(l.browser,'?'), ' / Origin: ', IFNULL(l.dashboard_logged_out_from,'?')) as notes, 'self' as performed_by, l.ip_address, l.created_at, u.name as user_name, u.email as user_email, u.user_role 
    FROM logout_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 100
");
if ($stmt2) {
    while ($row = mysqli_fetch_assoc($stmt2)) {
        $row['action'] = 'logout_' . $row['action'];
        $logs[] = $row;
    }
}

// Fetch general audit log natively matching Phase 5
$stmt3 = mysqli_query($conn, "
    SELECT a.id as audit_id, a.user_id, a.action, a.new_values as notes, 'self' as performed_by, a.ip_address, a.created_at, u.name as user_name, u.email as user_email, u.user_role
    FROM audit_log a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 100
");
if ($stmt3) {
    while ($row = mysqli_fetch_assoc($stmt3)) {
        $logs[] = $row;
    }
}

// Global Order By
usort($logs, function($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-history"></i> Registration Security Logs</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header" style="margin-bottom:2rem;">
            <div>
                <h1>Security Audit Trail</h1>
                <p>History of all registration, verification, and approval activities.</p>
            </div>
            <div style="background:var(--bg-card);padding:.75rem 1.5rem;border-radius:12px;box-shadow:var(--shadow-sm);border:1px solid var(--border);">
                <div style="font-size:.85rem;color:var(--text-muted);">Total Logs (Recent)</div>
                <div style="font-size:1.8rem;font-weight:700;color:var(--primary);line-height:1.2;"><?php echo count($logs); ?></div>
            </div>
        </div>

        <div class="adm-card shadow-sm" style="border-radius:20px;">
            <div class="adm-card-header" style="padding:1.5rem 2rem;">
                <h3><i class="fas fa-list"></i> Activity Log</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table" id="auditTable">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Audit ID</th>
                            <th>Action</th>
                            <th>Target User</th>
                            <th>Performer</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $b_color = 'secondary';
                            switch($log['action']) {
                                case 'registered': $b_color = 'primary'; break;
                                case 'otp_sent': $b_color = 'info'; break;
                                case 'otp_verified': $b_color = 'success'; break;
                                case 'otp_failed': $b_color = 'danger'; break;
                                case 'approved': $b_color = 'success'; break;
                                case 'rejected': $b_color = 'danger'; break;
                                case 'suspended': $b_color = 'warning'; break;
                                case 'logout_manual': $b_color = 'info'; break;
                                case 'logout_timeout': $b_color = 'warning'; break;
                                case 'logout_forced': $b_color = 'danger'; break;
                                case 'manual_logout': $b_color = 'info'; break;
                            }
                        ?>
                        <tr>
                            <td style="white-space:nowrap;color:var(--text-muted);font-size:0.85rem;"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td><code style="background:var(--bg-surface);padding:2px 6px;border-radius:4px;font-size:0.8rem;"><?php echo htmlspecialchars($log['audit_id']); ?></code></td>
                            <td><span class="adm-badge adm-badge-<?php echo $b_color; ?>" style="text-transform:uppercase;font-size:0.75rem;"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td>
                                <?php if ($log['user_name']): ?>
                                    <strong><?php echo htmlspecialchars($log['user_name']); ?></strong><br>
                                    <small style="color:var(--text-muted);"><?php echo htmlspecialchars($log['user_email']); ?></small>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);"><i>Unverified / Initial</i></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $log['performed_by'] === 'self' ? 'Sytem/Self' : 'Admin ID: ' . htmlspecialchars($log['performed_by']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?? 'Unknown'); ?></td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($log['notes'] ?? ''); ?>">
                                <?php echo htmlspecialchars($log['notes'] ?? '-'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:3rem;">No audit logs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
// Sidebar Toggle
const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
const menuToggle = document.getElementById('menuToggle');
if (menuToggle) {
    menuToggle.onclick = () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    };
}
if (overlay) {
    overlay.onclick = () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    };
}

// Theme Toggle
const themeToggle = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');
if (themeToggle) {
    themeToggle.onclick = () => {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme') || 'light';
        const target = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', target);
        localStorage.setItem('rmu_theme', target);
        if (themeIcon) themeIcon.className = target === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    };
}
</script>
</body>
</html>
