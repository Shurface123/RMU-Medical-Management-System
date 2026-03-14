<?php
/**
 * staff_dashboard.php
 * Main shell for the General Staff Dashboard.
 */
require_once __DIR__ . '/staff_security.php';
require_once __DIR__ . '/../db_conn.php';

// Allow overriding active tab via GET
$active_tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'overview';
$valid_tabs = ['overview', 'tasks', 'schedule', 'maintenance', 'laundry', 'kitchen', 'ambulance', 'profile', 'notifications', 'settings'];
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'overview';
}

// Fetch Staff Details
$user_id = (int)$_SESSION['user_id'];
$staff_id = getStaffId($conn, $user_id);
$staffName = $_SESSION['name'] ?? 'Staff Member';
$staffRole = $_SESSION['user_role'] ?? 'staff';

$stmt = mysqli_prepare($conn, "SELECT s.*, r.role_display_name, r.icon_class 
    FROM staff s 
    LEFT JOIN staff_roles r ON s.role = r.role_slug 
    WHERE s.user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$staff_row = mysqli_fetch_assoc($res) ?: [];

$displayName = !empty($staff_row['full_name']) ? $staff_row['full_name'] : $staffName;
$displayRole = !empty($staff_row['role_display_name']) ? $staff_row['role_display_name'] : ucfirst(str_replace('_', ' ', $staffRole));
$roleIcon = !empty($staff_row['icon_class']) ? $staff_row['icon_class'] : 'fas fa-user-tie';

// Quick Stats
$stats = [
    'pending_tasks' => dbVal($conn, "SELECT COUNT(*) FROM staff_tasks WHERE assigned_to=? AND status='pending'", "i", [$staff_id]),
    'unread_notifs' => dbVal($conn, "SELECT COUNT(*) FROM staff_notifications WHERE staff_id=? AND is_read=0", "i", [$staff_id])
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | RMU Medical</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Base Styling -->
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    
    <style>
        /* ── Role Specific Theming: Staff (Indigo Theme) ── */
        :root {
            --role-accent: #4F46E5;       /* Indigo primary */
            --role-accent-hover: #4338CA;
            --role-accent-light: #EEF2FF;
            --role-accent-dark: #312E81;
        }
        [data-theme="dark"] {
            --role-accent-light: #1E1B4B;
        }

        /* Override sidebar gradient for staff */
        .adm-sidebar {
            background: linear-gradient(175deg, #1C3A6B 0%, #4F46E5 60%, #818CF8 100%) !important;
        }

        /* ── Tab Sections ── */
        .dash-section { display: none; animation: fadeIn .3s ease; }
        .dash-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px) } to { opacity: 1; transform: translateY(0) } }

        /* Role stat icon overrides */
        .adm-stat-icon.staff { background: linear-gradient(135deg, #4F46E5, #818CF8); }
        
        .adm-action-tile:hover {
            border-color: var(--role-accent);
            color: var(--role-accent);
        }
        .adm-action-tile:hover i {
            background: linear-gradient(135deg, var(--role-accent-dark), var(--role-accent));
        }

        /* ── Forms ── */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
        .form-group { margin-bottom: 1.4rem; }
        .form-group label {
            display: block; font-size: 1.2rem; font-weight: 600; color: var(--text-secondary);
            margin-bottom: .5rem; text-transform: uppercase; letter-spacing: .04em;
        }
        .form-control {
            width: 100%; padding: 1rem 1.2rem; border: 1.5px solid var(--border);
            border-radius: var(--radius-sm); background: var(--surface);
            color: var(--text-primary); font-family: 'Poppins', sans-serif;
            font-size: 1.3rem; transition: var(--transition); outline: none; box-sizing: border-box;
        }
        .form-control:focus {
            border-color: var(--role-accent);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
        }
        textarea.form-control { resize: vertical; min-height: 60px; }
        
        @media(max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="adm-layout">

<!-- ════════════════ SIDEBAR ════════════════ -->
<aside class="adm-sidebar" id="admSidebar">
    <div class="adm-sidebar-brand">
        <div class="adm-sidebar-brand-icon"><i class="<?= $roleIcon ?>"></i></div>
        <div class="adm-sidebar-brand-text">
            <h2>RMU Sickbay</h2>
            <span>Staff Portal</span>
        </div>
    </div>
    <nav class="adm-sidebar-nav">
        <span class="adm-nav-section-label">Main</span>
        <a href="#" class="adm-nav-item <?=($active_tab==='overview')?'active':''?>" onclick="showTab('overview',this)">
            <i class="fas fa-house"></i><span>Overview</span>
        </a>
        <a href="#" class="adm-nav-item <?=($active_tab==='tasks')?'active':''?>" onclick="showTab('tasks',this)">
            <i class="fas fa-clipboard-list"></i><span>My Tasks</span>
            <?php if($stats['pending_tasks']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['pending_tasks']?></span><?php endif;?>
        </a>
        <a href="#" class="adm-nav-item <?=($active_tab==='schedule')?'active':''?>" onclick="showTab('schedule',this)">
            <i class="fas fa-calendar-alt"></i><span>Shift Schedule</span>
        </a>

        <?php if($staffRole === 'cleaner'): ?>
        <span class="adm-nav-section-label">Sanitation</span>
        <a href="#" class="adm-nav-item <?=($active_tab==='cleaning')?'active':''?>" onclick="showTab('cleaning',this)">
            <i class="fas fa-broom"></i><span>Cleaning Logs</span>
        </a>
        <?php endif; ?>

        <?php if($staffRole === 'maintenance'): ?>
        <span class="adm-nav-section-label">Facility</span>
        <a href="#" class="adm-nav-item <?=($active_tab==='maintenance')?'active':''?>" onclick="showTab('maintenance',this)">
            <i class="fas fa-tools"></i><span>Work Orders</span>
        </a>
        <?php endif; ?>

        <?php if($staffRole === 'ambulance_driver'): ?>
        <span class="adm-nav-section-label">Transport</span>
        <a href="#" class="adm-nav-item <?=($active_tab==='ambulance')?'active':''?>" onclick="showTab('ambulance',this)">
            <i class="fas fa-ambulance"></i><span>Trips & Vehicles</span>
        </a>
        <?php endif; ?>

        <span class="adm-nav-section-label">Personal</span>
        <a href="#" class="adm-nav-item <?=($active_tab==='notifications')?'active':''?>" onclick="showTab('notifications',this)">
            <i class="fas fa-bell"></i><span>Notifications</span>
            <?php if($stats['unread_notifs']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['unread_notifs']?></span><?php endif;?>
        </a>
        <a href="#" class="adm-nav-item <?=($active_tab==='profile')?'active':''?>" onclick="showTab('profile',this)">
            <i class="fas fa-user-circle"></i><span>My Profile</span>
        </a>
        <a href="#" class="adm-nav-item <?=($active_tab==='settings')?'active':''?>" onclick="showTab('settings',this)">
            <i class="fas fa-gear"></i><span>Settings</span>
        </a>
    </nav>
    <div class="adm-sidebar-footer">
        <a href="/RMU-Medical-Management-System/php/logout.php" class="adm-logout-btn">
            <i class="fas fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </div>
</aside>
<div class="adm-overlay" id="admOverlay"></div>

<!-- ════════════════ MAIN CONTENT ════════════════ -->
<main class="adm-main">
    
    <!-- TOPBAR -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle" style="display:none;"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="<?= $roleIcon ?>" style="color:var(--role-accent);margin-right:.6rem;"></i><span id="pageTitleText">Overview</span></span>
        </div>
        <div class="adm-topbar-right">
            <span class="adm-topbar-datetime" style="font-size:1.2rem;color:var(--text-secondary);"><i class="fas fa-calendar-day"></i> <?=date('D, d M Y')?></span>
            
            <button class="adm-notif-btn <?=($stats['unread_notifs']>0)?'has-unread':''?>" onclick="showTab('notifications',document.querySelector('[onclick*=notifications]'))" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if($stats['unread_notifs']>0):?><span class="adm-notif-badge"><?=$stats['unread_notifs']?></span><?php endif;?>
            </button>
            
            <button class="adm-theme-toggle adm-notif-btn" id="themeToggle" title="Toggle Theme"><i class="fas fa-moon" id="themeIcon"></i></button>
            
            <div class="adm-avatar" onclick="showTab('profile',document.querySelector('[onclick*=profile]'))" style="cursor:pointer;overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--role-accent);color:#fff;font-weight:700;font-size:1.4rem;">
                <?php if(!empty($staff_row['profile_photo'])): ?>
                    <img src="/RMU-Medical-Management-System/<?=e($staff_row['profile_photo'])?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <?= strtoupper(substr($displayName, 0, 1)) ?>
                <?php endif; ?>
            </div>
            <span style="font-size:1.3rem;font-weight:600;color:var(--text-primary);cursor:pointer;" onclick="showTab('profile',null)"><?= e(explode(' ', $displayName)[0]) ?></span>
        </div>
    </div>

    <!-- TAB CONTENTS -->
    <div class="adm-content" style="padding:2.5rem 3rem;">
        
        <?php include __DIR__.'/staff_tabs/tab_overview.php'; ?>
        
        <!-- Placeholders for other tabs (will be implemented next) -->
        <div id="sec-tasks" class="dash-section <?=($active_tab==='tasks')?'active':''?>"><h2>Tasks Module</h2><p>Coming Soon...</p></div>
        <div id="sec-schedule" class="dash-section <?=($active_tab==='schedule')?'active':''?>"><h2>Schedule Module</h2><p>Coming Soon...</p></div>
        <div id="sec-profile" class="dash-section <?=($active_tab==='profile')?'active':''?>"><h2>Profile Module</h2><p>Coming Soon...</p></div>
        <div id="sec-notifications" class="dash-section <?=($active_tab==='notifications')?'active':''?>"><h2>Notifications</h2><p>Coming Soon...</p></div>
        <div id="sec-settings" class="dash-section <?=($active_tab==='settings')?'active':''?>"><h2>Settings</h2><p>Coming Soon...</p></div>

        <!-- Role Specific Tabs -->
        <?php if($staffRole === 'cleaner'): ?>
            <div id="sec-cleaning" class="dash-section <?=($active_tab==='cleaning')?'active':''?>"><h2>Cleaning Module</h2><p>Coming Soon...</p></div>
        <?php endif; ?>
        <?php if($staffRole === 'maintenance'): ?>
            <div id="sec-maintenance" class="dash-section <?=($active_tab==='maintenance')?'active':''?>"><h2>Maintenance Module</h2><p>Coming Soon...</p></div>
        <?php endif; ?>
        <?php if($staffRole === 'ambulance_driver'): ?>
            <div id="sec-ambulance" class="dash-section <?=($active_tab==='ambulance')?'active':''?>"><h2>Ambulance Module</h2><p>Coming Soon...</p></div>
        <?php endif; ?>

    </div>

</main>
</div><!-- /adm-layout -->

<!-- ════════════════ GLOBAL TOAST ════════════════ -->
<div id="toastWrap" style="position:fixed;bottom:2rem;right:2rem;z-index:9999;display:flex;flex-direction:column;gap:.7rem;"></div>

<script>
// ── CSRF Token ──
const CSRF_TOKEN = '<?php echo htmlspecialchars((string)($csrf_token ?? ''), ENT_QUOTES); ?>';

// ── Tab Navigation ──
const TAB_TITLES={
    overview:'Overview', tasks:'My Tasks', schedule:'Shift Schedule',
    cleaning:'Cleaning Logs', maintenance:'Work Orders', ambulance:'Trips & Vehicles',
    notifications:'Notifications', profile:'My Profile', settings:'Settings'
};
const TAB_ICONS={
    overview:'fa-house', tasks:'fa-clipboard-list', schedule:'fa-calendar-alt',
    cleaning:'fa-broom', maintenance:'fa-tools', ambulance:'fa-ambulance',
    notifications:'fa-bell', profile:'fa-user-circle', settings:'fa-gear'
};

function showTab(tab, el) {
    document.querySelectorAll('.dash-section').forEach(s => s.classList.remove('active'));
    const sec = document.getElementById('sec-' + tab);
    if(sec) sec.classList.add('active');
    
    document.querySelectorAll('.adm-nav-item').forEach(a => a.classList.remove('active'));
    if(el) {
        el.classList.add('active');
    } else {
        const fallback = document.querySelector(`.adm-nav-item[onclick*="${tab}"]`);
        if(fallback) fallback.classList.add('active');
    }
    
    document.getElementById('pageTitleText').textContent = TAB_TITLES[tab] || tab;
    const icon = document.querySelector('#pageTitle i') || document.querySelector('.adm-page-title i');
    if(icon && TAB_ICONS[tab]) icon.className = `fas ${TAB_ICONS[tab]}`;
    
    document.getElementById('admSidebar').classList.remove('active');
    document.getElementById('admOverlay').classList.remove('active');
    
    // Update URL hash
    window.location.hash = tab;
}

// ── Sidebar Toggle ──
document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('admSidebar').classList.toggle('active');
    document.getElementById('admOverlay').classList.toggle('active');
});
document.getElementById('admOverlay')?.addEventListener('click', () => {
    document.getElementById('admSidebar').classList.remove('active');
    document.getElementById('admOverlay').classList.remove('active');
});

// ── Theme Management ──
function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    const icon = document.getElementById('themeIcon');
    if(icon) icon.className = (t === 'dark') ? 'fas fa-sun' : 'fas fa-moon';
}
applyTheme(localStorage.getItem('rmu_theme') || 'light');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
});

// ── Toast Notifications ──
function showToast(msg, type='success'){
    const colors = {success:'#27AE60', error:'#E74C3C', warning:'#F39C12', info:'#2980B9'};
    const icons = {success:'fa-check-circle', error:'fa-times-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle'};
    
    const t = document.createElement('div');
    t.style.cssText = `padding:1.2rem 2rem;border-radius:12px;color:#fff;font-size:1.3rem;font-weight:500;
        box-shadow:0 8px 32px rgba(0,0,0,.18);display:flex;align-items:center;gap:.8rem;min-width:280px;
        animation:fadeIn .3s ease;font-family:'Poppins',sans-serif;background:${colors[type]||colors.success}`;
    t.innerHTML = `<i class="fas ${icons[type]||icons.success}"></i>${msg}`;
    
    document.getElementById('toastWrap').appendChild(t);
    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateX(20px)';
        setTimeout(() => t.remove(), 300);
    }, 4000);
}

// ── Initialization based on Hash or PHP tab ──
document.addEventListener('DOMContentLoaded', () => {
    let initTab = '<?=$active_tab?>';
    if(window.location.hash) {
        initTab = window.location.hash.substring(1);
    }
    showTab(initTab, document.querySelector(`.adm-nav-item[onclick*="${initTab}"]`));
});

// ── AJAX Central Dispatcher ──
async function staffAction(data) {
    try {
        const opts = { method: 'POST' };
        if (data instanceof FormData) {
            opts.body = data;
        } else {
            opts.headers = { 'Content-Type': 'application/json' };
            opts.body = JSON.stringify(data);
        }
        const r = await fetch('/RMU-Medical-Management-System/php/dashboards/staff_actions.php', opts);
        const j = await r.json();
        return j;
    } catch(e) {
        return { success: false, message: 'Network Error' };
    }
}
</script>
</body>
</html>
