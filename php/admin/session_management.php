<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

// Handle Force Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'force_logout') {
    $uid = (int)$_POST['user_id'];
    $reason = trim($_POST['reason']);
    if (empty($reason)) $reason = "Administrative override.";
    
    // Insert into forced_logout_queue
    $ins = mysqli_prepare($conn, "INSERT INTO forced_logout_queue (user_id, reason) VALUES (?, ?)");
    mysqli_stmt_bind_param($ins, 'is', $uid, $reason);
    mysqli_stmt_execute($ins);
    
    header("Location: session_management.php?success=1");
    exit;
}

// Fetch Active Sessions
$sessions = [];
$q = mysqli_query($conn, "
    SELECT a.*, u.username, u.role as user_type 
    FROM active_sessions a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.last_active DESC
");
if ($q) {
    while($row = mysqli_fetch_assoc($q)) $sessions[] = $row;
}

$active_page = 'session_management';
$page_title = 'Session Control';
include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title">Active Session Control</span>
        </div>
        <div class="adm-topbar-right">
            <div class="adm-avatar"><i class="fas fa-user-tie"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-welcome" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2><i class="fas fa-network-wired" style="margin-right:.5rem;"></i> Active Sessions</h2>
                <p>Monitor concurrently connected users and enforce immediate terminations across the network.</p>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
        <div class="adm-alert adm-alert-success" style="margin-bottom:1.5rem;"><i class="fas fa-check-circle"></i> Forced logout successfully queued. The user will be intercepted on their next request.</div>
        <?php endif; ?>

        <div class="adm-card">
            <div class="adm-card-body" style="padding:0;">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>User Context</th>
                            <th>IP Address</th>
                            <th>Device &amp; Browser</th>
                            <th>Last Active</th>
                            <th align="right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sessions as $sess): 
                            $is_self = ($sess['user_id'] == $_SESSION['user_id'] && $sess['session_id'] === session_id());
                            $last_active_time = strtotime($sess['last_active']);
                            $idle_mins = round((time() - $last_active_time) / 60);
                        ?>
                        <tr style="<?= $is_self ? 'background:#f0f7ff;' : '' ?>">
                            <td>
                                <strong><?= htmlspecialchars($sess['username']) ?></strong>
                                <?php if($is_self): ?> <span class="adm-badge adm-badge-info" style="font-size:0.7rem;margin-left:5px;">This Device</span> <?php endif; ?>
                                <br><small style="color:var(--primary); font-weight:600;"><?= strtoupper($sess['user_type']) ?></small>
                            </td>
                            <td><span style="font-family:monospace;"><?= htmlspecialchars($sess['ip_address']) ?></span></td>
                            <td>
                                <i class="fas <?= stripos($sess['device_info'], 'mobile') !== false ? 'fa-mobile-alt' : 'fa-laptop' ?>" style="color:var(--text-muted);"></i> 
                                <?= htmlspecialchars(substr($sess['browser'], 0, 30)) ?>...
                            </td>
                            <td>
                                <?php if($idle_mins < 5): ?>
                                    <span style="color:var(--success);"><i class="fas fa-circle" style="font-size:8px;"></i> Online</span>
                                <?php else: ?>
                                    <span style="color:var(--warning);"><i class="fas fa-circle" style="font-size:8px;"></i> Idle <?= $idle_mins ?>m</span>
                                <?php endif; ?>
                            </td>
                            <td align="right">
                                <?php if(!$is_self): ?>
                                <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="forceLogout(<?= $sess['user_id'] ?>, '<?= htmlspecialchars(addslashes($sess['username'])) ?>')"><i class="fas fa-ban"></i> Terminate</button>
                                <?php else: ?>
                                <span style="font-size:0.8rem;color:var(--text-muted);font-style:italic;">Current Session</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($sessions)): ?>
                        <tr><td colspan="5" align="center" style="padding:2rem;color:var(--text-muted);">No active sessions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Force Logout Modal -->
<div id="forceModal" class="adm-overlay" style="z-index:9999; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.6);">
    <div style="background:#fff; padding:2rem; border-radius:16px; width:90%; max-width:400px; text-align:center; box-shadow:0 15px 40px rgba(0,0,0,0.2);">
        <div style="width:60px; height:60px; background:linear-gradient(135deg,#e74c3c,#c0392b); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; color:#fff; font-size:1.5rem;"><i class="fas fa-skull-crossbones"></i></div>
        <h3 style="color:#2c3e50; margin-bottom:.5rem;">Force Termination</h3>
        <p style="color:#7f8c8d; font-size:.9rem; margin-bottom:1.5rem;">You are about to instantly disconnect <strong id="targetUserSpan" style="color:var(--primary);"></strong>. Please provide a reason.</p>
        
        <form method="POST">
            <input type="hidden" name="action" value="force_logout">
            <input type="hidden" name="user_id" id="targetUserId" value="">
            <input type="text" name="reason" class="adm-input" placeholder="e.g. Account suspended, security sweep..." required style="width:100%; box-sizing:border-box; margin-bottom:1.5rem; border:1px solid #ddd; padding:10px; border-radius:8px;">
            
            <div style="display:flex; gap:1rem;">
                <button type="button" class="adm-btn" onclick="document.getElementById('forceModal').style.display='none'" style="flex:1; background:#f1f5f9; color:#475569;">Cancel</button>
                <button type="submit" class="adm-btn adm-btn-danger" style="flex:1;"><i class="fas fa-bolt"></i> Execute</button>
            </div>
        </form>
    </div>
</div>

<script>
function forceLogout(uid, username) {
    document.getElementById('targetUserId').value = uid;
    document.getElementById('targetUserSpan').innerText = username;
    document.getElementById('forceModal').style.display = 'flex';
}
</script>
</body>
</html>
