<?php
session_start();
// Include authentication middleware
require_once '../includes/auth_middleware.php';
requireRole('admin');
require_once '../db_conn.php';
require_once '../classes/SessionManager.php';

$active_page = 'session_management';
$page_title = 'Global Session Control';

// Handle Force Logout Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'force_logout') {
        $sid = mysqli_real_escape_string($conn, $_POST['session_id']);
        $uid = intval($_POST['user_id'] ?? 0);
        $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? 'System Administrator terminated your session.');

        $sm = new SessionManager($conn);
        $sm->killOtherSessions($uid, $sid);

        // Target specifically
        mysqli_query($conn, "DELETE FROM active_sessions WHERE session_id='$sid'");

        // Push explicitly into the target's queue
        mysqli_query($conn, "INSERT INTO forced_logout_queue (user_id, reason, queued_by) VALUES ($uid, '$reason', " . $_SESSION['user_id'] . ")");

        $success = "Session terminated successfully.";
    } elseif ($action === 'logout_all') {
        // Kill every single active session except current admin
        $currSid = session_id();
        mysqli_query($conn, "DELETE FROM active_sessions WHERE session_id != '$currSid'");
        mysqli_query($conn, "INSERT INTO forced_logout_queue (user_id, reason, queued_by) 
                             SELECT DISTINCT user_id, 'Global System Purge', " . $_SESSION['user_id'] . " FROM users WHERE id != " . $_SESSION['user_id']);
        $success = "All external sessions purged globally.";
    }
}

// Fetch all active sessions
$sessions = [];
$q = mysqli_query($conn, "
    SELECT a.*, u.name, u.email 
    FROM active_sessions a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.last_active DESC
");
if ($q)
    while ($r = mysqli_fetch_assoc($q))
        $sessions[] = $r;

include '../includes/_sidebar.php';
?>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title">Session Management & Control</span>
        </div>
    </div>

    <div class="adm-content">
        <?php if (!empty($success)): ?>
            <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <p style="color:var(--text-muted);">Monitor and force-terminate active connections physically routing
                through RMU servers.</p>
            <form method="POST" style="margin:0;"
                onsubmit="return confirm('Are you sure you want to terminate ALL active users across the entire system?');">
                <input type="hidden" name="action" value="logout_all">
                <button type="submit" class="adm-btn adm-btn-danger"><i class="fas fa-skull-crossbones"></i> Purge All
                    Active Sessions</button>
            </form>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-network-wired"></i> Live System Connections (<?= count($sessions) ?>)</h3>
            </div>
            <div style="padding:1.5rem;overflow-x:auto;">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>User Context</th>
                            <th>Role</th>
                            <th>IP Endpoint</th>
                            <th>Hardware / Software</th>
                            <th>Uptime</th>
                            <th>Enforcement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;color:var(--text-muted);">No active connections
                                    found.</td>
                            </tr>
                        <?php else:
                            foreach ($sessions as $s):
                                $is_me = ($s['session_id'] === session_id());
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?= htmlspecialchars($s['name'] ?? 'System User') ?></div>
                                        <div style="font-size:0.8rem;color:var(--text-muted);">
                                            <?= htmlspecialchars($s['email'] ?? 'Unknown Email') ?></div>
                                    </td>
                                    <td><span
                                            class="adm-badge adm-badge-info"><?= ucfirst(htmlspecialchars($s['user_role'] ?? 'unknown')) ?></span>
                                    </td>
                                    <td style="font-family:monospace;"><?= htmlspecialchars($s['ip_address'] ?? '0.0.0.0') ?>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;"><i class="fas fa-desktop"></i>
                                            <?= htmlspecialchars(substr($s['user_agent'] ?? 'Unknown', 0, 30)) ?>...</div>
                                    </td>
                                    <td>
                                        <div style="font-size:0.85rem;">Last:
                                            <?= date('M j, g:i A', strtotime($s['last_active'])) ?></div>
                                        <div style="font-size:0.75rem;color:var(--text-muted);">Login:
                                            <?= date('g:i A', strtotime($s['logged_in_at'])) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($is_me): ?>
                                            <span class="adm-badge adm-badge-success">Your Active Route</span>
                                        <?php else: ?>
                                            <form method="POST" style="margin:0;"
                                                onsubmit="return confirm('Immediately terminate connection for <?= htmlspecialchars(addslashes($s['name'] ?? 'user')) ?>?');">
                                                <input type="hidden" name="action" value="force_logout">
                                                <input type="hidden" name="session_id"
                                                    value="<?= htmlspecialchars($s['session_id']) ?>">
                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($s['user_id']) ?>">
                                                <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm"
                                                    style="color:var(--danger);"><i class="fas fa-ban"></i> Terminate TCP</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>