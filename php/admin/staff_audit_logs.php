<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'staff_audit_logs';
$page_title  = 'Security Audit Logs';
include '../includes/_sidebar.php';

// Fetch registration logs
$stmt = mysqli_query($conn, "
    SELECT a.audit_id, a.user_id, a.action, a.notes, a.performed_by, a.ip_address, a.created_at, u.name as user_name, u.email as user_email, u.user_role 
    FROM user_registration_audit a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 200
");
$logs = [];
if ($stmt) {
    while ($row = mysqli_fetch_assoc($stmt)) {
        $logs[] = $row;
    }
}

// Fetch logout logs
$stmt2 = mysqli_query($conn, "
    SELECT l.id as audit_id, l.user_id, l.logout_type as action, CONCAT('Device: ', IFNULL(l.device_info,'?'), ' / Browser: ', IFNULL(l.browser,'?')) as notes, 'self' as performed_by, l.ip_address, l.created_at, u.name as user_name, u.email as user_email, u.user_role 
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

// Global Order By
usort($logs, function($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">

<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #f59e0b; /* Amber for security logs */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #b45309);
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Stat Mini Cards ── */
.stat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2.5rem; }
.stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);box-shadow:var(--shadow-sm); }
.stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); border-color:var(--primary); }
.stat-mini-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:1.8rem;background:var(--surface-2);color:var(--text-secondary); }
.stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--primary); }
.stat-mini-lbl { font-size:1.15rem;font-weight:600;color:var(--text-secondary);margin-top:.6rem; text-transform:uppercase; letter-spacing:0.05em; }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; background:var(--surface-2); }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Badges ── */
.badge { display:inline-block; padding:0.3rem 0.8rem; border-radius:12px; font-size:0.9rem; font-weight:700; text-transform:uppercase; border:1px solid transparent; }
.badge-success { background:rgba(16,185,129,0.15); color:var(--success); border-color:rgba(16,185,129,0.3); }
.badge-danger { background:rgba(239,68,68,0.15); color:var(--danger); border-color:rgba(239,68,68,0.3); }
.badge-warning { background:rgba(245,158,11,0.15); color:var(--warning); border-color:rgba(245,158,11,0.3); }
.badge-info { background:rgba(59,130,246,0.15); color:var(--info); border-color:rgba(59,130,246,0.3); }
.badge-muted { background:var(--surface-2); color:var(--text-muted); border-color:var(--border); }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-history"></i> Security Audit Trail</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadeIn .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-user-shield hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-fingerprint"></i></div>
            <div class="staff-hero-info">
                <h2>Access & Compliance Logs</h2>
                <p>Monitor all user registration, authentication, and session termination events securely.</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--info); background:rgba(59,130,246,0.15);"><i class="fas fa-list-ul"></i></div>
                <div class="stat-mini-val"><?= count($logs) ?></div>
                <div class="stat-mini-lbl">Recent Events</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success); background:rgba(16,185,129,0.15);"><i class="fas fa-user-plus"></i></div>
                <div class="stat-mini-val">
                    <?php 
                        echo count(array_filter($logs, fn($l) => $l['action'] === 'registered'));
                    ?>
                </div>
                <div class="stat-mini-lbl">Registrations</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--danger); background:rgba(239,68,68,0.15);"><i class="fas fa-sign-out-alt"></i></div>
                <div class="stat-mini-val">
                    <?php 
                        echo count(array_filter($logs, fn($l) => strpos($l['action'], 'logout') !== false));
                    ?>
                </div>
                <div class="stat-mini-lbl">Exits Recorded</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-stream" style="color:var(--primary);"></i> Live Activity Feed</h3>
            </div>
            <div class="card-body" style="padding:1rem;">
                <table class="stf-table" id="auditTable">
                    <thead>
                        <tr>
                            <th>Timeline</th>
                            <th>Action Type</th>
                            <th>User Context</th>
                            <th>IP Endpoint</th>
                            <th>Event Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $b_class = 'badge-muted';
                            $icon = 'fa-info-circle';
                            switch($log['action']) {
                                case 'registered': $b_class = 'badge-info'; $icon = 'fa-user-plus'; break;
                                case 'otp_verified': $b_class = 'badge-success'; $icon = 'fa-check-circle'; break;
                                case 'approved': $b_class = 'badge-success'; $icon = 'fa-user-check'; break;
                                case 'rejected': $b_class = 'badge-danger'; $icon = 'fa-user-times'; break;
                                case 'logout_manual': $b_class = 'badge-muted'; $icon = 'fa-sign-out-alt'; break;
                                case 'logout_timeout': $b_class = 'badge-warning'; $icon = 'fa-hourglass-end'; break;
                                case 'logout_forced': $b_class = 'badge-danger'; $icon = 'fa-ban'; break;
                            }
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:700; color:var(--text-primary);"><?= date('M j, Y', strtotime($log['created_at'])) ?></div>
                                <div style="font-size:0.9rem; color:var(--text-muted);"><?= date('g:i:s A', strtotime($log['created_at'])) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $b_class ?>">
                                    <i class="fas <?= $icon ?>" style="margin-right:6px;"></i>
                                    <?= str_replace('_', ' ', $log['action']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['user_name']): ?>
                                    <div style="font-weight:700; color:var(--text-primary);"><?= htmlspecialchars($log['user_name']) ?></div>
                                    <div style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($log['user_role'] ?? 'User') ?></div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-style:italic;">External/Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-family:monospace; color:var(--info); font-weight:600;"><?= htmlspecialchars($log['ip_address'] ?? '0.0.0.0') ?></td>
                            <td>
                                <div style="max-width:300px; font-size:1.05rem; color:var(--text-secondary); line-height:1.4;" title="<?= htmlspecialchars($log['notes'] ?? '') ?>">
                                    <?= htmlspecialchars($log['notes'] ?? 'No additional data') ?>
                                </div>
                                <div style="font-size:0.85rem; color:var(--text-muted); margin-top:4px;">
                                    ID: <code style="background:var(--surface-2); padding:2px 4px; border-radius:4px;"><?= htmlspecialchars($log['audit_id']) ?></code>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    $(document).ready(function() {
        if ($('#auditTable').length) {
            $('#auditTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: { search: "", searchPlaceholder: "Search audit trails..." }
            });
            $('.dataTables_filter input').addClass('form-control').css({'width':'250px','display':'inline-block', 'margin-left':'10px'});
        }
    });

    const themeIcon = document.getElementById('themeIcon');
    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const html = document.documentElement;
        const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
        html.setAttribute('data-theme', t);
        localStorage.setItem('rmu_theme', t);
        if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
