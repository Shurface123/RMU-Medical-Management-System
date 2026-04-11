<?php
// ============================================================
// NURSE DASHBOARD — RMU Medical Sickbay
// ============================================================
require_once 'nurse_security.php';
initSecureSession();
setSecurityHeaders();
$user_id = enforceNurseRole();
require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');
$csrf_token = generateCsrfToken();

$user_id   = (int)$_SESSION['user_id'];
$nurseName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Nurse';
$today     = date('Y-m-d');

// ── Auto-Detect Current Tab ─────────────────────────────────
$valid_tabs = [
    'overview', 'patients', 'medications', 'wards', 'notes',
    'tasks', 'emergency', 'fluids', 'education', 'messages',
    'analytics', 'reports', 'profile', 'settings'
];
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs) ? $_GET['tab'] : 'overview';

// ── Nurse Profile ──────────────────────────────────────────
$nurse_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT n.id AS nurse_pk, n.nurse_id, n.full_name, n.shift_type,
            n.designation, n.specialization, n.profile_photo, n.status,
            u.email, u.phone
     FROM nurses n JOIN users u ON n.user_id=u.id
     WHERE n.user_id=$user_id LIMIT 1"));

if (!$nurse_row) {
    // Fallback if profile not created yet
    $nurse_row = [
        'nurse_pk' => 0, 'nurse_id' => 'N/A', 'full_name' => $nurseName,
        'shift_type' => 'Not Assigned', 'designation' => 'Staff Nurse',
        'specialization' => '', 'profile_photo' => 'default-avatar.png',
        'status' => 'Active', 'email' => '', 'phone' => ''
    ];
}
$nurse_pk = (int)$nurse_row['nurse_pk'];
$profile_image_path = !empty($nurse_row['profile_photo']) ? htmlspecialchars($nurse_row['profile_photo']) : 'default-avatar.png';

// ── Global Stats (Used in sidebar or topbar) ───────────────
function qval($conn,$sql){$r=mysqli_query($conn,$sql);return $r?(mysqli_fetch_row($r)[0]??0):0;}

$unread_notifs = qval($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND is_read=0");
$pending_tasks = qval($conn,"SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=$nurse_pk AND status IN('Pending','In Progress')");

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard | RMU Medical</title>
    
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    
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
    <!-- Include Bootstrap locally or CDN (Assuming dashboard tabs rely on BS logic) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Shared Admin/Dashboard CSS (The Standard Universal Styling) -->
    <link rel="stylesheet" href="../../css/admin-dashboard.css">
    
    <style>
        /* ── Role Accent Tokens (Nurse Theme) ── */
        :root {
            --role-accent: #E67E22; /* Warm Medical Orange */
            --role-accent-dark: #CA6F1E;
            --role-accent-light: #FDEBD0;
            
            /* Bridging Legacy Tokens */
            --primary-color: var(--primary);
            --primary-dark: var(--primary);
            --accent-color: var(--danger);
            --bs-primary: #2B5AA5; 
            --bs-danger: #e74c3c;
            --bs-success: #27ae60;
            --bs-warning: #f1c40f;
            --bs-info: #2980b9;
        }
        [data-theme="dark"] { --role-accent-light: #3a2512; }

        /* ── Hero Banner ── */
        .staff-hero { background:linear-gradient(135deg,#1C3A6B 0%,#2F80ED 55%,#E67E22 100%);color:#fff;border-radius:var(--radius-lg);padding:2.2rem 2.8rem;margin-bottom:2rem;display:flex;align-items:center;gap:1.8rem;flex-wrap:wrap;position:relative;overflow:hidden; box-shadow: var(--shadow-md); }
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
        
        /* Main Stat Grid */
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

        /* ── Section Header ── */
        .sec-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:1.8rem;flex-wrap:wrap;gap:1rem; }
        .sec-header h2 { font-size:2rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.8rem; }
        .sec-header h2 i { color:var(--role-accent); }

        /* ── Filter Tabs ── */
        .filter-tabs { display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem; }
        .ftab { padding:.55rem 1.2rem;border-radius:20px;font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition); }
        .ftab.active,.ftab:hover { background:var(--role-accent);color:#fff;border-color:var(--role-accent); }

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
<!-- Phase 4 Hooks --><link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css"><meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>"></head>
<body>

<!-- Mobile Overlay -->
<div class="adm-overlay" id="admOverlay"></div>

<!-- ═══════════════════════════ SIDEBAR ═══════════════════════════ -->
<aside class="adm-sidebar" id="admSidebar">

    <!-- Brand -->
    <div class="adm-sidebar-brand">
        <div class="adm-sidebar-brand-icon">
            <i class="fas fa-hospital-user"></i>
        </div>
        <div class="adm-sidebar-brand-text">
            <h2>RMU SICKBAY</h2>
            <span>Nurse Panel</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="adm-sidebar-nav">
        <span class="adm-nav-section-label">Main System</span>

        <a href="?tab=overview" class="adm-nav-item <?= $active_tab=='overview'?'active':'' ?>">
            <i class="fas fa-chart-pie"></i><span>Overview</span>
        </a>

        <span class="adm-nav-section-label">Clinical Care</span>

        <a href="?tab=patients" class="adm-nav-item <?= $active_tab=='patients'?'active':'' ?>">
            <i class="fas fa-stethoscope"></i><span>Vitals & Patients</span>
        </a>
        
        <a href="?tab=medications" class="adm-nav-item <?= $active_tab=='medications'?'active':'' ?>">
            <i class="fas fa-pills"></i><span>Medications</span>
        </a>
        
        <a href="?tab=wards" class="adm-nav-item <?= $active_tab=='wards'?'active':'' ?>">
            <i class="fas fa-bed"></i><span>Ward & Beds</span>
        </a>

        <a href="?tab=fluids" class="adm-nav-item <?= $active_tab=='fluids'?'active':'' ?>">
            <i class="fas fa-prescription-bottle-alt"></i><span>IV & Fluids</span>
        </a>

        <span class="adm-nav-section-label">Operations</span>

        <a href="?tab=notes" class="adm-nav-item <?= $active_tab=='notes'?'active':'' ?>">
            <i class="fas fa-clipboard-list"></i><span>Nursing Notes</span>
        </a>

        <a href="?tab=tasks" class="adm-nav-item <?= $active_tab=='tasks'?'active':'' ?>">
            <i class="fas fa-tasks"></i><span>Tasks & Handovers</span>
            <?php if($pending_tasks>0): ?>
                <span style="background:var(--warning); color:#fff; border-radius:50%; padding:2px 8px; font-size:12px; margin-left:auto;"><?= $pending_tasks ?></span>
            <?php endif; ?>
        </a>

        <a href="?tab=messages" class="adm-nav-item <?= $active_tab=='messages'?'active':'' ?>">
            <i class="fas fa-envelope"></i><span>Messages</span>
        </a>
        
        <a href="?tab=reports" class="adm-nav-item <?= $active_tab=='reports'?'active':'' ?>">
            <i class="fas fa-file-pdf"></i><span>Reports</span>
        </a>
        
        <span class="adm-nav-section-label">Emergency & Profile</span>

        <a href="?tab=emergency" class="adm-nav-item <?= $active_tab=='emergency'?'active':'' ?>">
            <i class="fas fa-ambulance" style="color:var(--danger);"></i><span style="color:var(--danger);font-weight:600;">Emergency Alerts</span>
        </a>

        <a href="?tab=profile" class="adm-nav-item <?= $active_tab=='profile'?'active':'' ?>">
            <i class="fas fa-user-circle"></i><span>My Profile</span>
        </a>

    </nav>

    <!-- Footer / Logout -->
    <div class="adm-sidebar-footer">
        <a href="/RMU-Medical-Management-System/php/logout.php" class="btn btn-primary adm-logout-btn"><span class="btn-text">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </span></a>
    </div>
</aside>

<!-- ═══════════════════════════ MAIN CONTENT ═══════════════════════════ -->
<main class="adm-main">

    <!-- Topbar -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title">
                <i class="fas fa-user-nurse" style="color:var(--primary);margin-right:.8rem;"></i>
                <?= e($nurse_row['designation'] ?? 'Nurse') ?> Dashboard
            </span>
        </div>
        
        <div class="adm-topbar-right">
            <?php if($nurse_row['shift_type'] !== 'Not Assigned'): ?>
                <span class="adm-badge adm-badge-primary" style="margin-right: 15px;">
                    <i class="far fa-clock"></i> <?= e($nurse_row['shift_type']) ?> Shift
                </span>
            <?php endif; ?>

            <a href="?tab=emergency" class="btn btn-danger btn-sm" style="animation: pulse-red 2s infinite; margin-right: 15px; border-radius: 20px;"><span class="btn-text">
                <i class="fas fa-exclamation-triangle"></i> CODE BLUE
            </span></a>

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
        $tab_file = "nurse_tabs/tab_{$active_tab}.php";
        if(file_exists($tab_file)) {
            include $tab_file;
        } else {
            echo "<div class='alert alert-warning'>
                    <i class='fas fa-exclamation-circle'></i> 
                    Module '{$active_tab}' is currently under development.
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



<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script></body>
</html>
