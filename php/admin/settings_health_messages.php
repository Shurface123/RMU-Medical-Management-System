<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'health_messages';
$page_title  = 'Broadcasts & Health Messages';
include '../includes/_sidebar.php';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_msg') {
    $subject  = trim($_POST['subject']);
    $msg      = trim($_POST['message']);
    $role     = trim($_POST['target_role']); // 'all' or specific role
    $priority = trim($_POST['priority']); // info, warning, alert
    $admin_id = $_SESSION['user_id'];
    
    $sql = "INSERT INTO staff_messages (sender_id, target_role, subject, body, priority, is_broadcast, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issss", $admin_id, $role, $subject, $msg, $priority);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: settings_health_messages.php?success=1");
    exit();
}

// Fetch messages
$msgs = [];
$q = mysqli_query($conn, "
    SELECT m.*, u.name as sender_name 
    FROM staff_messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.is_broadcast = 1 
    ORDER BY m.created_at DESC LIMIT 50
");
if ($q) while ($r = mysqli_fetch_assoc($q)) $msgs[] = $r;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-bullhorn"></i> Global Broadcasts</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div>
                <h1>Hospital Broadcast Messages</h1>
                <p>Send crucial policy updates, shift alerts, and health directives directly to staff dashboards.</p>
            </div>
            <button class="adm-btn adm-btn-primary" onclick="document.getElementById('msgModal').classList.add('active')">
                <i class="fas fa-paper-plane"></i> Send Broadcast
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Broadcast message successfully delivered.</div>
        <?php endif; ?>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-mail-bulk"></i> Broadcast History</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Sent</th><th>Subject & Message Segment</th><th>Target Audience</th><th>Priority</th><th>Sender</th></tr></thead>
                    <tbody>
                        <?php if (empty($msgs)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;">No broadcasts sent yet.</td></tr>
                        <?php else: foreach ($msgs as $m): 
                            $pc = $m['priority']==='alert'?'danger':($m['priority']==='warning'?'warning':'info');
                        ?>
                        <tr>
                            <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($m['created_at'])); ?><br><small style="color:var(--text-muted);"><?php echo date('g:i A', strtotime($m['created_at'])); ?></small></td>
                            <td style="max-width:350px;">
                                <strong><?php echo htmlspecialchars($m['subject']); ?></strong>
                                <div style="font-size:.8rem;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($m['body']); ?>">
                                    <?php echo htmlspecialchars($m['body']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($m['target_role'] === 'all'): ?>
                                    <span class="adm-badge" style="background:var(--primary);color:#fff;"><i class="fas fa-globe"></i> Entire Hospital</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge-secondary"><?php echo ucfirst(str_replace('_',' ',$m['target_role'])); ?> Only</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="adm-badge adm-badge-<?php echo $pc; ?>"><?php echo strtoupper($m['priority']); ?></span></td>
                            <td><?php echo htmlspecialchars($m['sender_name']); ?> (Admin)</td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="adm-modal" id="msgModal">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3><i class="fas fa-bullhorn"></i> Compose Broadcast</h3>
            <button class="adm-modal-close" onclick="document.getElementById('msgModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <form method="post" action="settings_health_messages.php">
                <input type="hidden" name="action" value="add_msg">
                <div class="adm-form-group">
                    <label>Broadcast Subject</label>
                    <input type="text" name="subject" class="adm-search-input" required placeholder="e.g. New Sanitization Protocols">
                </div>
                <div style="display:flex;gap:1rem;">
                    <div class="adm-form-group" style="flex:1;">
                        <label>Target Audience</label>
                        <select name="target_role" class="adm-search-input">
                            <option value="all" selected>All Hospital Staff</option>
                            <option value="ambulance_driver">Ambulance Drivers Only</option>
                            <option value="cleaner">Cleaning Staff Only</option>
                            <option value="laundry_staff">Laundry Staff Only</option>
                            <option value="maintenance">Maintenance Staff Only</option>
                            <option value="kitchen_staff">Kitchen & Dietary Only</option>
                            <option value="security">Security Only</option>
                            <option value="nurse">Nursing Staff Only</option>
                            <option value="doctor">Doctors Only</option>
                        </select>
                    </div>
                    <div class="adm-form-group" style="flex:1;">
                        <label>Priority Level</label>
                        <select name="priority" class="adm-search-input">
                            <option value="info" selected>Informational (Blue)</option>
                            <option value="warning">Warning / Notice (Yellow)</option>
                            <option value="alert">Critical Alert (Red)</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label>Full Message Content</label>
                    <textarea name="message" class="adm-search-input" rows="5" required placeholder="Type the broadcast message here..."></textarea>
                </div>
                <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;"><i class="fas fa-paper-plane"></i> Send Immediately</button>
            </form>
        </div>
    </div>
</div>
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
</script>
</body>
</html>
