<?php
// Include authentication middleware
require_once '../includes/auth_middleware.php';
requireRole('admin');
require_once '../db_conn.php';
require_once '../classes/SessionManager.php';

$active_page = 'session_management';
$page_title = 'Global Session Control';
$success = '';

// Handle Force Logout Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'force_logout') {
        $sid = mysqli_real_escape_string($conn, $_POST['session_id']);
        $uid = intval($_POST['user_id'] ?? 0);
        $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? 'Session terminated by Administrator.');
        $sm = new SessionManager($conn);
        $sm->killOtherSessions($uid, $sid);
        mysqli_query($conn, "DELETE FROM active_sessions WHERE session_id='$sid'");
        mysqli_query($conn, "INSERT INTO forced_logout_queue (user_id, reason, queued_by) VALUES ($uid, '$reason', " . $_SESSION['user_id'] . ")");
        $success = "Session terminated successfully.";
    } elseif ($action === 'logout_all') {
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
if ($q) while ($r = mysqli_fetch_assoc($q)) $sessions[] = $r;

$mySessionId = session_id();
$otherCount = count(array_filter($sessions, fn($s) => $s['session_id'] !== $mySessionId));

include '../includes/_sidebar.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">
<style>
:root{--primary:#ef4444;--primary-light:rgba(239,68,68,.15);--success:#10b981;--warning:#f59e0b;--info:#3b82f6;}
.staff-hero{display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;background:linear-gradient(135deg,#1e3a5f,#111827);border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap;position:relative;overflow:hidden;}
.staff-hero-avatar{width:72px;height:72px;border-radius:50%;border:3px solid rgba(255,255,255,.35);background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0;z-index:2;}
.staff-hero-info{z-index:2;}.staff-hero-info h2{font-size:2rem;font-weight:700;margin:0;}.staff-hero-info p{font-size:1.3rem;margin:.3rem 0 0;opacity:.85;}
.hero-bg-icon{position:absolute;right:-20px;bottom:-40px;font-size:15rem;opacity:.07;transform:rotate(-15deg);z-index:1;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2.5rem;}
.stat-mini{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);box-shadow:var(--shadow-sm);}
.stat-mini:hover{box-shadow:var(--shadow-md);transform:translateY(-3px);}
.stat-mini-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:1.8rem;}
.stat-mini-val{font-size:3rem;font-weight:800;line-height:1;color:var(--info);}
.stat-mini-val.success{color:var(--success);}.stat-mini-val.danger{color:var(--danger,#ef4444);}
.stat-mini-lbl{font-size:1.1rem;font-weight:600;color:var(--text-secondary);margin-top:.6rem;text-transform:uppercase;letter-spacing:.05em;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:2.5rem;}
.card-header{padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-2);}
.card-header h3{font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0;}
.stf-table{width:100%;border-collapse:collapse;font-size:1.15rem;}
.stf-table th{background:var(--surface-2);color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left;}
.stf-table td{padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle;}
.stf-table tr:hover td{background:var(--surface-2);}
.online-pulse{width:12px;height:12px;border-radius:50%;background:var(--success);display:inline-block;margin-right:8px;box-shadow:0 0 0 3px rgba(16,185,129,.2);animation:livePulse 2s infinite;}
@keyframes livePulse{0%{box-shadow:0 0 0 0 rgba(16,185,129,.4);}70%{box-shadow:0 0 0 6px rgba(16,185,129,0);}100%{box-shadow:0 0 0 0 rgba(16,185,129,0);}}
.btn{display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none;justify-content:center;}
.btn-danger{background:#ef4444;color:#fff;}.btn-danger:hover{opacity:.88;}
.btn-ghost{background:transparent;color:var(--text-secondary);}.btn-ghost:hover{background:var(--surface-2);}
.alert-success{background:rgba(16,185,129,.15);color:var(--success);padding:1.2rem 1.8rem;border-radius:var(--radius-sm);font-weight:600;font-size:1.15rem;display:flex;align-items:center;gap:1rem;margin-bottom:2rem;border:1px solid rgba(16,185,129,.3);}
#toastWrap{position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem;}
.toast-msg{padding:1.2rem 2rem;border-radius:var(--radius-sm);background:var(--surface);box-shadow:var(--shadow-lg);border-left:5px solid var(--success);font-size:1.2rem;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:1rem;animation:fadePop .3s ease;}
@keyframes fadePop{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-network-wired"></i> Session Control Center</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content" style="animation:fadePop .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-server hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-network-wired"></i></div>
            <div class="staff-hero-info">
                <h2>Global Session Control</h2>
                <p>Monitor and force-terminate active connections routing through RMU servers in real time.</p>
            </div>
            <div style="margin-left:auto;z-index:2;">
                <form method="POST" style="margin:0;" onsubmit="return confirm('CRITICAL: Terminate ALL active user sessions across the entire system? This cannot be undone.');">
                    <input type="hidden" name="action" value="logout_all">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-skull-crossbones"></i> Purge All Active Sessions</button>
                </form>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--info);background:rgba(59,130,246,.15);"><i class="fas fa-users"></i></div>
                <div class="stat-mini-val"><?= count($sessions) ?></div>
                <div class="stat-mini-lbl">Total Connections</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success);background:rgba(16,185,129,.15);"><i class="fas fa-user-shield"></i></div>
                <div class="stat-mini-val success">1</div>
                <div class="stat-mini-lbl">Your Session</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:#ef4444;background:rgba(239,68,68,.15);"><i class="fas fa-door-open"></i></div>
                <div class="stat-mini-val danger"><?= $otherCount ?></div>
                <div class="stat-mini-lbl">External Sessions</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><span class="online-pulse"></span> Live System Connections (<?= count($sessions) ?>)</h3>
                <span style="font-size:1rem;color:var(--text-muted);font-weight:600;">Auto-updates every 60s</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="stf-table" id="sessionsTable">
                    <thead>
                        <tr>
                            <th>User Context</th>
                            <th>Role</th>
                            <th>IP Endpoint</th>
                            <th>Hardware / Client</th>
                            <th>Session Timeline</th>
                            <th style="text-align:right;">Enforcement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                            <tr><td colspan="6" style="text-align:center;padding:4rem;color:var(--text-muted);">No active connections found.</td></tr>
                        <?php else: foreach ($sessions as $s): $is_me = ($s['session_id'] === $mySessionId); ?>
                            <tr style="<?= $is_me ? 'background:rgba(16,185,129,.05);' : '' ?>">
                                <td>
                                    <div style="font-weight:700;font-size:1.1rem;color:var(--text-primary);display:flex;align-items:center;gap:.5rem;">
                                        <?php if ($is_me): ?><span class="online-pulse"></span><?php endif; ?>
                                        <?= htmlspecialchars($s['name'] ?? 'System User') ?>
                                    </div>
                                    <div style="font-size:.9rem;color:var(--text-muted);margin-top:.2rem;"><?= htmlspecialchars($s['email'] ?? '') ?></div>
                                </td>
                                <td>
                                    <span style="background:rgba(99,102,241,.15);color:#6366f1;padding:.3rem .8rem;border-radius:12px;font-weight:700;font-size:.9rem;border:1px solid rgba(99,102,241,.3);"><?= ucfirst(htmlspecialchars($s['user_role'] ?? 'unknown')) ?></span>
                                </td>
                                <td style="font-family:monospace;color:var(--info);font-weight:600;"><?= htmlspecialchars($s['ip_address'] ?? '0.0.0.0') ?></td>
                                <td style="max-width:200px;">
                                    <div style="color:var(--text-secondary);font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($s['user_agent'] ?? '') ?>">
                                        <i class="fas fa-desktop" style="color:var(--text-muted);"></i> <?= htmlspecialchars(substr($s['user_agent'] ?? 'Unknown', 0, 35)) ?>...
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:600;font-size:1rem;color:var(--text-primary);"><i class="fas fa-wifi" style="color:var(--success);font-size:.9rem;"></i> Last: <?= date('M j, g:i A', strtotime($s['last_active'])) ?></div>
                                    <div style="font-size:.85rem;color:var(--text-muted);margin-top:.2rem;"><i class="fas fa-sign-in-alt"></i> Login: <?= date('g:i A', strtotime($s['logged_in_at'])) ?></div>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($is_me): ?>
                                        <span style="background:rgba(16,185,129,.15);color:var(--success);padding:.4rem 1rem;border-radius:12px;font-weight:700;font-size:.9rem;border:1px solid rgba(16,185,129,.3);">✓ Your Route</span>
                                    <?php else: ?>
                                        <form method="POST" style="margin:0;" onsubmit="return confirm('Immediately terminate connection for <?= htmlspecialchars(addslashes($s['name'] ?? 'user')) ?>?');">
                                            <input type="hidden" name="action" value="force_logout">
                                            <input type="hidden" name="session_id" value="<?= htmlspecialchars($s['session_id']) ?>">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($s['user_id']) ?>">
                                            <button type="submit" class="btn btn-ghost" style="color:#ef4444;padding:.6rem 1rem;"><i class="fas fa-ban"></i> Terminate</button>
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

<div id="toastWrap"></div>
<script>
$(document).ready(function(){
    if($('#sessionsTable').length){
        $('#sessionsTable').DataTable({responsive:true,pageLength:25,ordering:false,language:{search:'',searchPlaceholder:'Search sessions...'}});
        $('.dataTables_filter input').css({width:'220px',display:'inline-block','margin-left':'10px'});
    }
    // Auto-refresh every 60 seconds
    setTimeout(()=>location.reload(), 60000);
});
const themeIcon=document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click',()=>{const html=document.documentElement;const t=html.getAttribute('data-theme')==='dark'?'light':'dark';html.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);if(themeIcon)themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon';});
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>