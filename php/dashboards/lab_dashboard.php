<?php
// ============================================================
// LAB TECHNICIAN DASHBOARD — RMU Medical Sickbay
// ============================================================
require_once 'lab_security.php';
initSecureSession();
setSecurityHeaders();
$user_id = enforceLabTechRole();
require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');
$csrf_token = generateCsrfToken();

$user_id   = (int)$_SESSION['user_id'];
$techName  = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Lab Tech';
$today     = date('Y-m-d');

// ── Auto-Detect Current Tab ─────────────────────────────────
$valid_tabs = [
    'overview', 'orders', 'samples', 'results', 'reference',
    'equipment', 'inventory', 'messages', 'reports', 'analytics',
    'audit', 'profile', 'settings'
];
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs) ? $_GET['tab'] : 'overview';

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ── Lab Technician Profile ──────────────────────────────────
$tech_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT l.id AS tech_pk, l.technician_id, l.full_name, l.designation, 
            l.specialization, l.profile_photo, l.status,
            u.email, u.phone
     FROM lab_technicians l JOIN users u ON l.user_id=u.id
     WHERE l.user_id=$user_id LIMIT 1"));

if (!$tech_row) {
    // Fallback if profile not created yet
    $tech_row = [
        'tech_pk' => 0, 'technician_id' => 'N/A', 'full_name' => $techName,
        'designation' => 'Lab Technician', 'specialization' => 'General',
        'profile_photo' => 'default-avatar.png', 'status' => 'Active',
        'email' => '', 'phone' => ''
    ];
}
$tech_pk = (int)$tech_row['tech_pk'];
$profile_image_path = !empty($tech_row['profile_photo']) ? e($tech_row['profile_photo']) : 'default-avatar.png';

// ── Global Stats ───────────────────────────────────────────
function qval($conn,$sql){$r=mysqli_query($conn,$sql);return $r?(mysqli_fetch_row($r)[0]??0):0;}

$unread_notifs = qval($conn,"SELECT COUNT(*) FROM lab_notifications WHERE recipient_id=$user_id AND is_read=0");

// ── Phase 9: Record active session ─────────────────────────
recordLabSession($user_id, $conn);

// ── Phase 9: License expiry notification (once per day) ────
// Check technician's license from professional_profile OR lab_technicians
$lic_q = $conn->prepare("
    SELECT COALESCE(pp.license_expiry_date, lt.license_expiry) AS lic_exp
    FROM lab_technicians lt
    LEFT JOIN lab_technician_professional_profile pp ON pp.technician_id = lt.id
    WHERE lt.user_id = ? LIMIT 1");
if ($lic_q) {
    $lic_q->bind_param('i', $user_id);
    $lic_q->execute();
    $lic_row = $lic_q->get_result()->fetch_assoc();
    $lic_q->close();
    if (!empty($lic_row['lic_exp'])) {
        $days_left = (int)ceil((strtotime($lic_row['lic_exp']) - time()) / 86400);
        if ($days_left <= 60 && $days_left >= 0) {
            // Guard against duplicate notifications on same day
            $dup_chk = $conn->prepare("
                SELECT id FROM notifications WHERE user_id=? AND type='license_expiry'
                AND DATE(created_at)=CURDATE() LIMIT 1");
            if ($dup_chk) {
                $dup_chk->bind_param('i', $user_id);
                $dup_chk->execute();
                $has_notif = $dup_chk->get_result()->num_rows > 0;
                $dup_chk->close();
                if (!$has_notif) {
                    $exp_date_fmt = date('d M Y', strtotime($lic_row['lic_exp']));
                    $lic_msg = "Your lab technician license expires on {$exp_date_fmt} ({$days_left} days remaining). Please renew it.";
                    // Notify the technician
                    $n1 = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, created_at) VALUES (?, 'lab_technician', 'license_expiry', 'License Expiring Soon', ?, 0, 'Profile', NOW())");
                    if ($n1) { $n1->bind_param('is', $user_id, $lic_msg); $n1->execute(); $n1->close(); }
                    // Notify admins
                    $admin_ids_q = mysqli_query($conn, "SELECT id FROM users WHERE user_role='admin' LIMIT 5");
                    while ($adm = mysqli_fetch_assoc($admin_ids_q)) {
                        $adm_msg = "Lab technician {$tech_row['full_name']} (ID: {$tech_row['technician_id']}) license expires in {$days_left} days ({$exp_date_fmt}).";
                        $n2 = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, created_at) VALUES (?, 'admin', 'license_expiry', 'Staff License Alert', ?, 0, 'Lab Staff', NOW())");
                        if ($n2) { $n2->bind_param('is', $adm['id'], $adm_msg); $n2->execute(); $n2->close(); }
                    }
                }
            }
        }
    }
}
// ─────────────────────────────────────────────────────────────

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Technician Dashboard | RMU Medical</title>
    
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= e($csrf_token) ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js & DataTables -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Shared Admin/Dashboard CSS -->
    <link rel="stylesheet" href="../../css/admin-dashboard.css">
    
    <style>
        /* ── Role Accent Tokens (Lab Technician Theme - Teal) ── */
        :root {
            --role-accent: #0d9488; /* Deep Teal */
            --role-accent-dark: #0f766e;
            --role-accent-light: #ccfbf1;
            
            --primary-color: var(--primary);
            --primary-dark: var(--primary);
            --accent-color: var(--danger);
            --bs-primary: #0d9488; 
            --bs-danger: #e74c3c;
            --bs-success: #27ae60;
            --bs-warning: #f1c40f;
            --bs-info: #2980b9;
        }
        [data-theme="dark"] { --role-accent-light: #134e4a; }

        /* ── Hero Banner ── */
        .staff-hero { background:linear-gradient(135deg,#1C3A6B 0%,#2F80ED 55%,#0d9488 100%);color:#fff;border-radius:var(--radius-lg);padding:2.2rem 2.8rem;margin-bottom:2rem;display:flex;align-items:center;gap:1.8rem;flex-wrap:wrap;position:relative;overflow:hidden; box-shadow: var(--shadow-md); transition: transform 0.3s ease; }
        .staff-hero:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .staff-hero-avatar { width:76px;height:76px;border-radius:50%;background:rgba(255,255,255,.18);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;font-size:2.6rem;border:3px solid rgba(255,255,255,.35);flex-shrink:0; }
        .staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0 0 .3rem; }
        .staff-hero-info p { margin:0;opacity:.85;font-size:.9rem; }
        .hero-badge { background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);border-radius:50px;padding:.35rem 1rem;font-size:1.1rem;font-weight:500;display:inline-flex;align-items:center;gap:.5rem;margin:.25rem .25rem 0 0; }

        /* ── Summary & Stats Strip ── */
        .adm-summary-strip { display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:2rem; }
        .adm-mini-card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.4rem 1.2rem;text-align:center;box-shadow:var(--shadow-sm);transition:var(--transition);cursor:pointer; }
        .adm-mini-card:hover { transform:translateY(-3px);box-shadow:var(--shadow-md); }
        .adm-mini-card-num { font-size:2.8rem;font-weight:800;color:var(--text-primary);line-height:1; }
        .adm-mini-card-num.green { color:var(--success); }
        .adm-mini-card-num.orange { color:var(--warning); }
        .adm-mini-card-num.blue { color:var(--primary); }
        .adm-mini-card-num.teal { color:var(--role-accent); }
        .adm-mini-card-num.red { color:var(--danger); }
        .adm-mini-card-label { font-size:.78rem;color:var(--text-secondary);margin-top:.4rem;font-weight:500;text-transform:uppercase;letter-spacing:0.04em; }
        
        .stat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.5rem;margin-bottom:2.5rem; }
        .stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);cursor:pointer;box-shadow:var(--shadow-sm); }
        .stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); }
        .stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--role-accent); }
        .stat-mini-lbl { font-size:1.15rem;font-weight:500;color:var(--text-secondary);margin-top:.6rem; }

        /* ── Tab Sections ── */
        .tab-content { display:none;animation:fadeIn .3s ease; }
        .tab-content.active { display:block; }
        .dash-section { display:none;animation:fadeIn .3s ease; }
        .dash-section.active { display:block; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

        .sec-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:1.8rem;flex-wrap:wrap;gap:1rem; }
        .sec-header h2 { font-size:2rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.8rem; }
        .sec-header h2 i { color:var(--role-accent); }

        /* ── Filter Tabs & Sub-navigation (Premium) ── */
        .adm-tab-group { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.8rem; border-bottom:1px solid var(--border); padding-bottom:1rem; }
        .ftab { 
            padding:.6rem 1.4rem; border-radius:20px; font-size:1.25rem; font-weight:600; 
            border:1.5px solid var(--border); background:var(--surface); color:var(--text-secondary);
            cursor:pointer; transition:var(--transition); display:flex; align-items:center; gap:.6rem;
        }
        .ftab i { font-size:1.3rem; opacity:0.8; }
        .ftab:hover { border-color:var(--role-accent); color:var(--role-accent); background:var(--primary-light); }
        .ftab.active { background:var(--role-accent); color:#fff; border-color:var(--role-accent); box-shadow:0 4px 12px color-mix(in srgb, var(--role-accent) 25%, transparent); }

        /* ── Table Aesthetics ── */
        .adm-table-wrap { overflow-x:auto;border-radius:var(--radius-md);border:1px solid var(--border); }
        .adm-table { width:100%;border-collapse:collapse;font-size:1.3rem; }
        .adm-table th { background:var(--surface-2);padding:1.2rem 1.4rem;text-align:left;font-weight:600;color:var(--text-secondary);font-size:1.1rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1.5px solid var(--border); }
        .adm-table td { padding:1.2rem 1.4rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
        .adm-table tr:last-child td { border:none; }
        .adm-table tr:hover td { background:var(--surface-2); }
        .adm-table .action-btns { display:flex;gap:.5rem;flex-wrap:wrap; }

        /* ── Standard CSS Grids (Replacing Bootstrap cols) ── */
        .cards-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem;margin-bottom:2rem; }
        .info-card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.6rem;box-shadow:var(--shadow-sm);transition:var(--transition); }
        .info-card:hover { box-shadow:var(--shadow-md);transform:translateY(-2px); }
        .info-card-head { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem; }
        .info-card-icon { width:44px;height:44px;border-radius:12px;background:var(--role-accent-light);color:var(--role-accent);display:flex;align-items:center;justify-content:center;font-size:1.6rem; }
        
        .charts-grid { display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem; }
        .chart-wrap { position:relative;height:280px;width:100%;transition:var(--transition); }
        .chart-wrap:hover { transform: scale(1.01); }

        /* ── Form Grids ── */
        .form-row { display:grid;grid-template-columns:1fr 1fr;gap:1.2rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom:1.4rem; }
        .form-group label { display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.04em; }
        .form-control { width:100%;padding:1rem 1.2rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.3rem;transition:var(--transition);outline:none; }
        .form-control:focus { border-color:var(--role-accent);box-shadow:0 0 0 3px color-mix(in srgb, var(--role-accent) 15%, transparent 85%); }
        .form-control select, .form-select { appearance:none; }

        /* ── Activity Feed ── */
        .activity-item { display:flex;align-items:flex-start;gap:1rem;padding:1.4rem 0;border-bottom:1px solid var(--border);transition:var(--transition); }
        .activity-item:last-child { border:none; }
        .activity-item:hover { transform: translateX(5px); }
        .activity-dot { width:10px;height:10px;border-radius:50%;background:var(--role-accent);flex-shrink:0;margin-top:.5rem;box-shadow:0 0 0 3px color-mix(in srgb, var(--role-accent) 10%, transparent); }

        /* Missing Badges / Adjustments */
        .adm-badge-teal { background:var(--role-accent-light); color:var(--role-accent); }
        
        /* Ensure DataTables integrates */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--role-accent) !important; color: white !important; border: 1px solid var(--role-accent) !important; }
        [data-theme="dark"] .form-control, [data-theme="dark"] .form-select { background-color: var(--surface); color: var(--text-primary); border-color: var(--border); }
        
        @media(max-width:768px) { .charts-grid { grid-template-columns:1fr; } .form-row { grid-template-columns:1fr; } }
    </style>
</head>
<body>

<!-- Mobile Overlay -->
<div class="adm-overlay" id="admOverlay"></div>

<!-- ═══════════════════════════ SIDEBAR ═══════════════════════════ -->
<aside class="adm-sidebar" id="admSidebar">

    <!-- Brand -->
    <div class="adm-sidebar-brand">
        <div class="adm-sidebar-brand-icon">
            <i class="fas fa-microscope"></i>
        </div>
        <div class="adm-sidebar-brand-text">
            <h2>RMU SICKBAY</h2>
            <span>Lab Panel</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="adm-sidebar-nav">
        <span class="adm-nav-section-label">Main System</span>

        <a href="?tab=overview" class="adm-nav-item <?= $active_tab=='overview'?'active':'' ?>">
            <i class="fas fa-chart-pie"></i><span>Overview</span>
        </a>

        <span class="adm-nav-section-label">Clinical Laboratory</span>

        <a href="?tab=orders" class="adm-nav-item <?= $active_tab=='orders'?'active':'' ?>">
            <i class="fas fa-notes-medical"></i><span>Test Orders</span>
        </a>
        
        <a href="?tab=samples" class="adm-nav-item <?= $active_tab=='samples'?'active':'' ?>">
            <i class="fas fa-vials"></i><span>Sample Tracking</span>
        </a>
        
        <a href="?tab=results" class="adm-nav-item <?= $active_tab=='results'?'active':'' ?>">
            <i class="fas fa-file-medical-alt"></i><span>Result Entry</span>
        </a>

        <a href="?tab=reference" class="adm-nav-item <?= $active_tab=='reference'?'active':'' ?>">
            <i class="fas fa-sliders-h"></i><span>Reference Ranges</span>
        </a>

        <span class="adm-nav-section-label">Lab Operations</span>

        <a href="?tab=equipment" class="adm-nav-item <?= $active_tab=='equipment'?'active':'' ?>">
            <i class="fas fa-microscope"></i><span>Equipment Management</span>
        </a>

        <a href="?tab=inventory" class="adm-nav-item <?= $active_tab=='inventory'?'active':'' ?>">
            <i class="fas fa-boxes"></i><span>Reagent Inventory</span>
        </a>

        <a href="?tab=messages" class="adm-nav-item <?= $active_tab=='messages'?'active':'' ?>">
            <i class="fas fa-comments"></i><span>Doctor Communication</span>
        </a>

        <span class="adm-nav-section-label">Data & Compliance</span>
        
        <a href="?tab=reports" class="adm-nav-item <?= $active_tab=='reports'?'active':'' ?>">
            <i class="fas fa-print"></i><span>Reports & Export</span>
        </a>

        <a href="?tab=analytics" class="adm-nav-item <?= $active_tab=='analytics'?'active':'' ?>">
            <i class="fas fa-chart-line"></i><span>Lab Analytics</span>
        </a>

        <a href="?tab=audit" class="adm-nav-item <?= $active_tab=='audit'?'active':'' ?>">
            <i class="fas fa-history"></i><span>Audit Trail</span>
        </a>
        
        <span class="adm-nav-section-label">Administration</span>

        <a href="?tab=profile" class="adm-nav-item <?= $active_tab=='profile'?'active':'' ?>">
            <i class="fas fa-user-circle"></i><span>My Profile</span>
        </a>

        <a href="?tab=settings" class="adm-nav-item <?= $active_tab=='settings'?'active':'' ?>">
            <i class="fas fa-cog"></i><span>System Settings</span>
        </a>

    </nav>

    <!-- Footer / Logout -->
    <div class="adm-sidebar-footer">
        <a href="../logout.php" class="adm-logout-btn">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</aside>

<!-- ═══════════════════════════ MAIN CONTENT ═══════════════════════════ -->
<main class="adm-main">

    <!-- Topbar -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title">
                <i class="fas fa-vial" style="color:var(--primary);margin-right:.8rem;"></i>
                <?= e($tech_row['designation'] ?? 'Lab Technician') ?> Dashboard
            </span>
        </div>
        
        <div class="adm-topbar-right">

            <!-- Notifications -->
            <a href="?tab=overview#notifications" style="text-decoration: none; color: var(--text-muted); position: relative; margin-right: 20px; font-size: 1.2rem;">
                <i class="fas fa-bell"></i>
                <?php if($unread_notifs>0): ?>
                    <span style="position: absolute; top: -5px; right: -10px; background: var(--danger); color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold;"><?= $unread_notifs ?></span>
                <?php endif; ?>
            </a>

            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            
            <div class="adm-avatar" onclick="window.location.href='?tab=profile'" style="cursor:pointer; display:flex; align-items:center;">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $profile_image_path ?>" 
                     alt="Profile" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);" 
                     onerror="this.src='/RMU-Medical-Management-System/image/default-avatar.png'">
            </div>
        </div>
    </div>

    <!-- Tab Dynamic Content Wrapper -->
    <div class="adm-content">
        <?php
        // Security check on tab inclusion
        $tab_file = "lab_tabs/tab_{$active_tab}.php";
        if(file_exists($tab_file)) {
            include $tab_file;
        } else {
            echo "<div class='alert alert-warning' style='font-size: 1.4rem; padding: 2rem;'>
                    <i class='fas fa-exclamation-circle'></i> 
                    Module <strong>'{$active_tab}'</strong> is currently under development. Phase 3 construction is ongoing.
                  </div>";
        }
        ?>
    </div>

</main>

<!-- ── GLOBAL SCRIPTS ────────────────────────────────────── -->
<script src="/RMU-Medical-Management-System/php/includes/BroadcastReceiver.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof BroadcastReceiver !== 'undefined') {
        window.rmuBroadcasts = new BroadcastReceiver(<?= $_SESSION['user_id'] ?>);
    }
});

// Configure global AJAX CSRF injection
$.ajaxSetup({
    beforeSend: function(xhr, settings) {
        if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            xhr.setRequestHeader("X-CSRF-Token", token);
        }
    }
});

// Sidebar Toggle Logic
const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { 
    sidebar.classList.toggle('active'); 
    overlay.classList.toggle('active'); 
});
overlay?.addEventListener('click', () => { 
    sidebar.classList.remove('active'); 
    overlay.classList.remove('active'); 
});

// Theme Management Logic
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = document.getElementById('themeIcon');
const html        = document.documentElement;

function applyTheme(t) { 
    html.setAttribute('data-theme', t); 
    localStorage.setItem('rmu_theme', t); 
    themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon'; 
}

// Initialize theme on load
(function(){
    var t = localStorage.getItem('rmu_theme') || 'light';
    applyTheme(t);
})();

themeToggle?.addEventListener('click', () => {
    applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark');
});

// Auto-close Bootstrap toast and alert notifications after 5s
setTimeout(() => {
    const toasts = document.querySelectorAll('.toast, .alert-success');
    toasts.forEach(t => t.style.opacity = '0');
    setTimeout(() => toasts.forEach(t => t.style.display = 'none'), 500);
}, 5000);
</script>

</body>
</html>
