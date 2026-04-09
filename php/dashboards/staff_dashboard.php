<?php
/**
 * staff_dashboard.php — RMU Medical Sickbay
 * Complete Staff Dashboard — Single unified shell, role-based content.
 */
require_once '../includes/auth_middleware.php';
require_once '../db_conn.php';

// ── DB Helper Functions ──────────────────────────────────────
// Defined here because staff_dashboard is a self-contained shell
// that does not go through a shared helpers include.
if (!function_exists('sanitize')) {
    function sanitize($v) {
        return htmlspecialchars(trim(stripslashes((string)($v ?? ''))), ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('dbRow')) {
    function dbRow($conn, $sql, $types = '', $params = []) {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return null;
        if ($types && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }
}
if (!function_exists('dbVal')) {
    function dbVal($conn, $sql, $types = '', $params = []) {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return null;
        if ($types && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_row($res);
        mysqli_stmt_close($stmt);
        return $row ? $row[0] : null;
    }
}
if (!function_exists('dbSelect')) {
    function dbSelect($conn, $sql, $types = '', $params = []) {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return [];
        if ($types && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        mysqli_stmt_close($stmt);
        return $rows;
    }
}
if (!function_exists('dbExecute')) {
    function dbExecute($conn, $sql, $types = '', $params = []) {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return false;
        if ($types && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return $affected;
    }
}
if (!function_exists('dbInsert')) {
    function dbInsert($conn, $sql, $types = '', $params = []) {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return false;
        if ($types && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); return false; }
        $id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        return $id;
    }
}

enforceSingleDashboard('staff');

$user_id   = (int)$_SESSION['user_id'];
$staffRole = $_SESSION['user_role'] ?? 'staff';
$today     = date('Y-m-d');

// ── Fetch Staff Row ──────────────────────────────────────────
// NOTE: sc.completeness_score does not exist — real column is sc.overall_percentage
//       ST.theme does not exist           — real column is ST.theme_preference
// Both are aliased so downstream code reading completeness_score / theme still works.
$staff = dbRow($conn,
    "SELECT
            s.*,
            r.role_display_name,
            r.icon_class,
            d.name                        AS dept_name,
            COALESCE(sc.overall_percentage, 0)        AS completeness_score,
            sc.personal_info_complete,
            sc.documents_uploaded,
            sc.photo_uploaded,
            sc.security_setup_complete,
            COALESCE(ST.theme_preference, 'light')    AS theme,
            COALESCE(ST.language, 'en')               AS language,
            COALESCE(ST.alert_sound_enabled, 1)       AS alert_sound_enabled,
            u.two_fa_enabled
     FROM staff s
     LEFT JOIN users u                      ON s.user_id      = u.id
     LEFT JOIN staff_roles r                ON r.role_slug    = s.role
     LEFT JOIN staff_departments d          ON d.department_id = s.department_id
     LEFT JOIN staff_profile_completeness sc ON sc.staff_id   = s.id
     LEFT JOIN staff_settings ST            ON ST.staff_id    = s.id
     WHERE s.user_id = ? LIMIT 1", "i", [$user_id]);

// Graceful fallback if no staff record yet
if (!$staff) {
    $staff = ['full_name'=>$_SESSION['name']??'Staff Member','role'=>$staffRole,
              'employee_id'=>'Pending','department_id'=>0,'dept_name'=>'—','designation'=>'—',
              'profile_photo'=>'','shift_type'=>'—','status'=>'Active','date_joined'=>'',
              'phone'=>'','email'=>'','gender'=>'','date_of_birth'=>'','nationality'=>'',
              'marital_status'=>'','address'=>'','secondary_phone'=>'','national_id'=>'',
              'emergency_contact_name'=>'','emergency_contact_phone'=>'',
              'role_display_name'=>ucwords(str_replace('_',' ',$staffRole)),'icon_class'=>'fas fa-user-tie',
              'completeness_score'=>0,'theme'=>'light'];
}
$staff_id       = $staff['id'] ?? 0;
$displayName    = $staff['full_name'] ?? ($_SESSION['name'] ?? 'Staff Member');
$displayRole    = $staff['role_display_name'] ?? ucwords(str_replace('_',' ',$staffRole));
$roleIcon       = $staff['icon_class'] ?? 'fas fa-user-tie';
$savedTheme     = $staff['theme'] ?? 'light';
$completeness   = (int)($staff['completeness_score'] ?? 0);

// ── Active Tab ───────────────────────────────────────────────
$active_tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'overview';

// ── Live Stats (role-adaptive, used in sidebar badges) ───────
$unread_notifs = $staff_id ? (int)dbVal($conn,"SELECT COUNT(*) FROM staff_notifications WHERE staff_id=? AND is_read=0","i",[$staff_id]) : 0;
$unread_msgs   = $staff_id ? (int)dbVal($conn,"SELECT COUNT(*) FROM staff_messages WHERE receiver_id=? AND is_read=0","i",[$staff_id]) : 0;
$pending_tasks = $staff_id ? (int)dbVal($conn,"SELECT COUNT(*) FROM staff_tasks WHERE assigned_to=? AND status='pending'","i",[$staff_id]) : 0;

// ── Sidebar Nav Config (fetched from staff_roles, then mapped) ─
// Build the menu from DB + code defaults
// Server-side: only emit items relevant to this role
$universal_nav = [
    ['tab'=>'overview',      'icon'=>'fa-house',           'label'=>'Dashboard'],
    ['tab'=>'tasks',         'icon'=>'fa-clipboard-list',   'label'=>'My Tasks',      'badge'=>$pending_tasks],
    ['tab'=>'schedule',      'icon'=>'fa-calendar-alt',     'label'=>'Shift Schedule'],
    ['tab'=>'messages',      'icon'=>'fa-envelope',         'label'=>'Messages',       'badge'=>$unread_msgs],
    ['tab'=>'notifications', 'icon'=>'fa-bell',             'label'=>'Notifications',  'badge'=>$unread_notifs],
    ['tab'=>'analytics',     'icon'=>'fa-chart-bar',        'label'=>'My Performance'],
    ['tab'=>'reports',       'icon'=>'fa-file-alt',         'label'=>'Reports'],
];
$role_nav_map = [
    'ambulance_driver' => [['tab'=>'ambulance','icon'=>'fa-ambulance','label'=>'Trip Manager','section'=>'Transport']],
    'cleaner'          => [['tab'=>'cleaning','icon'=>'fa-broom','label'=>'Cleaning Logs','section'=>'Sanitation']],
    'laundry_staff'    => [['tab'=>'laundry','icon'=>'fa-tshirt','label'=>'Laundry','section'=>'Laundry']],
    'maintenance'      => [['tab'=>'maintenance','icon'=>'fa-tools','label'=>'Work Orders','section'=>'Facility']],
    'security'         => [['tab'=>'security','icon'=>'fa-shield-alt','label'=>'Security Ops','section'=>'Security'],
                           ['tab'=>'visitors','icon'=>'fa-user-check','label'=>'Visitor Log','section'=>'Security']],
    'kitchen_staff'    => [['tab'=>'kitchen','icon'=>'fa-utensils','label'=>'Kitchen Tasks','section'=>'Kitchen']],
];
$role_nav = $role_nav_map[$staffRole] ?? [];

$profile_nav = [
    ['tab'=>'profile',  'icon'=>'fa-id-card',   'label'=>'My Profile'],
    ['tab'=>'settings', 'icon'=>'fa-gear',       'label'=>'Settings'],
];

// Sidebar gradient per role
$gradients = [
    'ambulance_driver' => 'linear-gradient(175deg,#0F2027 0%,#cc0000 60%,#ff6b6b 100%)',
    'cleaner'          => 'linear-gradient(175deg,#0F2027 0%,#1ABC9C 60%,#48C9B0 100%)',
    'laundry_staff'    => 'linear-gradient(175deg,#0F2027 0%,#8E44AD 60%,#BB8FCE 100%)',
    'maintenance'      => 'linear-gradient(175deg,#0F2027 0%,#E67E22 60%,#F0A04C 100%)',
    'security'         => 'linear-gradient(175deg,#0F2027 0%,#2C3E50 60%,#4CA1AF 100%)',
    'kitchen_staff'    => 'linear-gradient(175deg,#0F2027 0%,#c0392b 60%,#e55039 100%)',
    'default'          => 'linear-gradient(175deg,#1C3A6B 0%,#4F46E5 60%,#818CF8 100%)',
];
$sidebar_gradient = $gradients[$staffRole] ?? $gradients['default'];

$role_accent_map = [
    'ambulance_driver' => '#CC0000',
    'cleaner'          => '#1ABC9C',
    'laundry_staff'    => '#8E44AD',
    'maintenance'      => '#E67E22',
    'security'         => '#2C3E50',
    'kitchen_staff'    => '#c0392b',
    'default'          => '#4F46E5',
];
$roleAccent = $role_accent_map[$staffRole] ?? $role_accent_map['default'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($savedTheme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= e($displayRole) ?> Dashboard — RMU Medical</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* ── Role Accent Tokens ── */
:root {
  --role-accent: <?= $roleAccent ?>;
  --role-accent-dark: color-mix(in srgb, <?= $roleAccent ?> 80%, #000 20%);
  --role-accent-light: color-mix(in srgb, <?= $roleAccent ?> 15%, #fff 85%);
}
[data-theme="dark"] { --role-accent-light: color-mix(in srgb, <?= $roleAccent ?> 20%, #0F1628 80%); }

/* ── Sidebar Role Override ── */
.adm-sidebar { background: <?= $sidebar_gradient ?> !important; }
.adm-nav-item.active { background: rgba(255,255,255,.18) !important; }
.adm-nav-item:hover  { background: rgba(255,255,255,.10) !important; }

/* ── Tab Section Animations ── */
.dash-section            { display:none; animation:fadePop .35s cubic-bezier(.4,0,.2,1); }
.dash-section.active     { display:block; }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--role-accent), color-mix(in srgb, var(--role-accent) 60%, #000 40%));
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; }
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; }
.staff-hero-avatar img { width:100%;height:100%;object-fit:cover; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-badge { display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);
  padding:.35rem 1rem;border-radius:20px;font-size:1.1rem;font-weight:500;backdrop-filter:blur(5px);margin-top:.6rem;}

/* ── Stat Mini Cards ── */
.stat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.5rem;margin-bottom:2.5rem; }
.stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);
  padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);cursor:pointer;box-shadow:var(--shadow-sm); }
.stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); }
.stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--role-accent); }
.stat-mini-lbl { font-size:1.15rem;font-weight:500;color:var(--text-secondary);margin-top:.6rem; }

/* ── Completeness Bar ── */
.completeness-bar { height:6px;background:var(--border);border-radius:3px;overflow:hidden;margin-top:.8rem; }
.completeness-fill { height:100%;background:linear-gradient(90deg,var(--role-accent),color-mix(in srgb,var(--role-accent) 60%,#fff 40%));border-radius:3px;transition:width .6s ease; }

/* ── Table Styles ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.3rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1.1rem;letter-spacing:.04em;padding:1.4rem 1.6rem;text-align:left; }
.stf-table td { padding:1.3rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:last-child td { border-bottom:none; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Badges ── */
.badge { display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .9rem;border-radius:20px;font-size:1.1rem;font-weight:600; }
.badge-pending  { background:var(--warning-light);color:var(--warning); }
.badge-progress { background:var(--info-light);color:var(--info); }
.badge-done     { background:var(--success-light);color:var(--success); }
.badge-overdue  { background:var(--danger-light);color:var(--danger); }
.badge-cancelled{ background:#f5f5f5;color:#777; }
.badge-urgent   { background:#fde8e8;color:var(--danger); }
.badge-high     { background:#fff3e0;color:#E67E22; }
.badge-medium   { background:var(--warning-light);color:var(--warning); }
.badge-low      { background:var(--success-light);color:var(--success); }

/* ── Forms ── */
.form-row { display:grid;grid-template-columns:1fr 1fr;gap:1.5rem; }
.form-group { margin-bottom:1.6rem; }
.form-group label { display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em; }
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.3rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--role-accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--role-accent) 15%,transparent 85%); }
textarea.form-control { resize:vertical;min-height:80px; }
select.form-control { cursor:pointer; }

/* ── Modals ── */
.modal-bg { display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;
  align-items:center;justify-content:center;padding:2rem;backdrop-filter:blur(3px); }
.modal-box { background:var(--surface);border-radius:var(--radius-lg);padding:2.5rem;width:100%;max-width:560px;
  max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg);border:1px solid var(--border); }
.modal-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem; }
.modal-header h3 { font-size:1.8rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.8rem; }
.modal-close { background:none;border:none;font-size:2rem;cursor:pointer;color:var(--text-muted);line-height:1;padding:.3rem; }
.modal-close:hover { color:var(--danger); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.3rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; }
.btn-primary { background:var(--role-accent);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-outline { background:transparent;color:var(--role-accent);border:1.5px solid var(--role-accent); }
.btn-outline:hover { background:var(--role-accent-light); }
.btn-danger { background:var(--danger);color:#fff; }
.btn-success { background:var(--success);color:#fff; }
.btn-sm { padding:.5rem 1.1rem;font-size:1.15rem; }
.btn-wide { width:100%;justify-content:center;padding:1.1rem; }

/* ── Card System ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
.card-header h3 { font-size:1.6rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-header h3 i { color:var(--role-accent); }
.card-body { padding:2rem; }
.card-body-flush { padding:0; }

/* ── Task Cards ── */
.task-card { border-radius:var(--radius-md);border:1.5px solid var(--border);background:var(--surface);padding:2rem;transition:var(--transition);position:relative; }
.task-card:hover { box-shadow:var(--shadow-md); }
.task-card.overdue { border-color:var(--danger);background:var(--danger-light); }

/* ── Activity Feed ── */
.activity-item { display:flex;align-items:flex-start;gap:1.5rem;padding:1.2rem 0;border-bottom:1px solid var(--border); }
.activity-item:last-child { border-bottom:none; }
.activity-dot { width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0; }

/* ── Responsive ── */
@media(max-width:900px) {
  .form-row { grid-template-columns:1fr; }
  .stat-grid { grid-template-columns:repeat(2,1fr); }
}
@media(max-width:600px) {
  .stat-grid { grid-template-columns:1fr 1fr; }
  .adm-content { padding:1.5rem !important; }
}

/* ── Filter Tab Pills ── */
.filter-tabs { display:flex;gap:.5rem;flex-wrap:wrap; }
.filter-tabs .ftab { padding:.5rem 1.3rem;border-radius:20px;font-size:1.2rem;font-weight:600;cursor:pointer;
  border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition); }
.filter-tabs .ftab.active,
.filter-tabs .ftab:hover { background:var(--role-accent);color:#fff;border-color:var(--role-accent); }

/* ── Toast Container ── */
#toastWrap { position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem; }
</style>
<!-- Phase 4 Hooks --><link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css"><meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>"></head>
<body>
<div class="adm-layout">

<!-- ══════════════════ SIDEBAR ══════════════════ -->
<aside class="adm-sidebar" id="admSidebar">
    <div class="adm-sidebar-brand">
        <div class="adm-sidebar-brand-icon"><i class="<?= e($roleIcon) ?>"></i></div>
        <div class="adm-sidebar-brand-text">
            <h2>RMU Sickbay</h2>
            <span><?= e($displayRole) ?></span>
        </div>
    </div>

    <div style="padding:1.2rem 1.8rem;border-bottom:1px solid rgba(255,255,255,.12);">
        <div style="display:flex;align-items:center;gap:1.2rem;">
            <div style="width:46px;height:46px;border-radius:50%;overflow:hidden;flex-shrink:0;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;font-weight:700;">
                <?php if (!empty($staff['profile_photo'])): ?>
                    <img src="/RMU-Medical-Management-System/<?= e($staff['profile_photo']) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: echo strtoupper(substr($displayName,0,1)); endif; ?>
            </div>
            <div>
                <div style="color:#fff;font-weight:700;font-size:1.3rem;line-height:1.3;"><?= e(explode(' ',$displayName)[0]) ?></div>
                <div style="color:rgba(255,255,255,.7);font-size:1.1rem;"><?= e($staff['employee_id'] ?? 'Pending ID') ?></div>
                <div style="margin-top:.4rem;">
                    <div class="completeness-bar" style="width:100%;">
                        <div class="completeness-fill" style="width:<?= $completeness ?>%;"></div>
                    </div>
                    <span style="color:rgba(255,255,255,.6);font-size:1rem;">Profile: <?= $completeness ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <nav class="adm-sidebar-nav" style="flex:1;overflow-y:auto;">
        <span class="adm-nav-section-label">MAIN</span>
        <?php foreach ($universal_nav as $nav): ?>
        <a href="#" class="adm-nav-item <?= ($active_tab===$nav['tab'])?'active':'' ?>" onclick="showTab('<?= e($nav['tab']) ?>',this);return false;">
            <i class="fas <?= e($nav['icon']) ?>"></i>
            <span><?= e($nav['label']) ?></span>
            <?php if (!empty($nav['badge']) && $nav['badge']>0): ?>
                <span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?= (int)$nav['badge'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>

        <?php if (!empty($role_nav)): ?>
            <span class="adm-nav-section-label"><?= e($role_nav[0]['section'] ?? 'ROLE') ?></span>
            <?php foreach ($role_nav as $nav): ?>
            <a href="#" class="adm-nav-item <?= ($active_tab===$nav['tab'])?'active':'' ?>" onclick="showTab('<?= e($nav['tab']) ?>',this);return false;">
                <i class="fas <?= e($nav['icon']) ?>"></i>
                <span><?= e($nav['label']) ?></span>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>

        <span class="adm-nav-section-label">ACCOUNT</span>
        <?php foreach ($profile_nav as $nav): ?>
        <a href="#" class="adm-nav-item <?= ($active_tab===$nav['tab'])?'active':'' ?>" onclick="showTab('<?= e($nav['tab']) ?>',this);return false;">
            <i class="fas <?= e($nav['icon']) ?>"></i>
            <span><?= e($nav['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="adm-sidebar-footer">
        <a href="/RMU-Medical-Management-System/php/logout.php" class="adm-logout-btn">
            <i class="fas fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </div>
</aside>
<div class="adm-overlay" id="admOverlay"></div>

<!-- ══════════════════ MAIN AREA ══════════════════ -->
<main class="adm-main">

    <!-- TOP BAR -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title">
                <i id="pageIcon" class="fas fa-house" style="color:var(--role-accent);margin-right:.6rem;"></i>
                <span id="pageTitleText">Dashboard</span>
            </span>
        </div>
        <div class="adm-topbar-right">
            <span style="font-size:1.2rem;color:var(--text-secondary);display:flex;align-items:center;gap:.4rem;">
                <i class="fas fa-calendar-day"></i> <?= date('D, d M Y') ?>
            </span>

            <!-- Notification Bell -->
            <button class="adm-notif-btn" id="notifBtn" onclick="showTab('notifications',null)" title="Notifications" style="position:relative;">
                <i class="fas fa-bell"></i>
                <?php if ($unread_notifs > 0): ?>
                <span class="adm-notif-badge" id="notifBadge"><?= $unread_notifs ?></span>
                <?php endif; ?>
            </button>

            <!-- Messages -->
            <button class="adm-notif-btn" onclick="showTab('messages',null)" title="Messages" style="position:relative;">
                <i class="fas fa-envelope"></i>
                <?php if ($unread_msgs > 0): ?>
                <span class="adm-notif-badge"><?= $unread_msgs ?></span>
                <?php endif; ?>
            </button>

            <!-- Theme Toggle -->
            <button class="adm-notif-btn" id="themeToggle" title="Toggle Theme">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>

            <!-- Role Badge + Avatar -->
            <span class="badge" style="background:var(--role-accent-light);color:var(--role-accent);font-size:1.1rem;">
                <i class="fas <?= e($roleIcon) ?>"></i> <?= e($displayRole) ?>
            </span>
            <div class="adm-avatar" onclick="showTab('profile',null)" style="cursor:pointer;background:var(--role-accent);color:#fff;font-weight:700;font-size:1.4rem;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                <?php if (!empty($staff['profile_photo'])): ?>
                    <img src="/RMU-Medical-Management-System/<?= e($staff['profile_photo']) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: echo strtoupper(substr($displayName,0,1)); endif; ?>
            </div>
        </div>
    </div>

    <!-- ══ TAB CONTENT AREA ══ -->
    <div class="adm-content" style="padding:2.5rem 3rem;">

        <?php
        // ── Include all tab files ────────────────────────────
        $tabsDir = __DIR__ . '/staff_tabs/';

        // Universal tabs (always included)
        $universal_tabs = ['tab_overview','tab_tasks','tab_schedule','tab_messages',
                           'tab_notifications','tab_analytics','tab_reports','tab_profile','tab_settings'];

        // Role specific tabs
        $role_tab_map = [
            'ambulance_driver' => ['tab_ambulance'],
            'cleaner'          => ['tab_cleaning'],
            'laundry_staff'    => ['tab_laundry'],
            'maintenance'      => ['tab_maintenance'],
            'security'         => ['tab_security','tab_visitors'],
            'kitchen_staff'    => ['tab_kitchen'],
        ];
        $role_tabs = $role_tab_map[$staffRole] ?? [];
        $all_tabs  = array_merge($universal_tabs, $role_tabs);

        foreach ($all_tabs as $tabFile) {
            $tabPath = $tabsDir . $tabFile . '.php';
            if (file_exists($tabPath)) {
                include $tabPath;
            }
        }
        ?>

    </div><!-- /adm-content -->
</main>
</div><!-- /adm-layout -->

<!-- ══ TOAST & BROADCASTS ══ -->
<div id="toastWrap"></div>
<script src="/RMU-Medical-Management-System/php/includes/BroadcastReceiver.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof BroadcastReceiver !== 'undefined') {
        window.rmuBroadcasts = new BroadcastReceiver(<?= $_SESSION['user_id'] ?>);
    }
});
</script>

<!-- ══ GLOBAL SCRIPTS ══ -->
<script>
/* ── Constants ── */
const CSRF = '<?= e($csrf_token) ?>';
const STAFF_ROLE = '<?= e($staffRole) ?>';
const STAFF_ID = <?= (int)$staff_id ?>;
const BASE = '/RMU-Medical-Management-System';

/* ── Tab Navigation ── */
const TAB_META = {
    overview:       {title:'Dashboard',         icon:'fa-house'},
    tasks:          {title:'My Tasks',          icon:'fa-clipboard-list'},
    schedule:       {title:'Shift Schedule',    icon:'fa-calendar-alt'},
    messages:       {title:'Messages',          icon:'fa-envelope'},
    notifications:  {title:'Notifications',     icon:'fa-bell'},
    analytics:      {title:'My Performance',    icon:'fa-chart-bar'},
    reports:        {title:'Reports',           icon:'fa-file-alt'},
    profile:        {title:'My Profile',        icon:'fa-id-card'},
    settings:       {title:'Settings',          icon:'fa-gear'},
    ambulance:      {title:'Trip Manager',      icon:'fa-ambulance'},
    cleaning:       {title:'Cleaning Logs',     icon:'fa-broom'},
    laundry:        {title:'Laundry',           icon:'fa-tshirt'},
    maintenance:    {title:'Work Orders',       icon:'fa-tools'},
    security:       {title:'Security Ops',      icon:'fa-shield-alt'},
    visitors:       {title:'Visitor Log',       icon:'fa-user-check'},
    kitchen:        {title:'Kitchen Tasks',     icon:'fa-utensils'},
};

function showTab(tab, el) {
    document.querySelectorAll('.dash-section').forEach(s => s.classList.remove('active'));
    const sec = document.getElementById('sec-' + tab);
    if (sec) sec.classList.add('active');

    document.querySelectorAll('.adm-nav-item').forEach(a => a.classList.remove('active'));
    if (el) el.classList.add('active');
    else {
        const found = document.querySelector(`.adm-nav-item[onclick*="${tab}"]`);
        if (found) found.classList.add('active');
    }

    const meta = TAB_META[tab] || {title: tab, icon: 'fa-circle'};
    document.getElementById('pageTitleText').textContent = meta.title;
    document.getElementById('pageIcon').className = `fas ${meta.icon}`;
    document.getElementById('pageIcon').style.color = 'var(--role-accent)';

    // Collapse mobile sidebar
    document.getElementById('admSidebar').classList.remove('active');
    document.getElementById('admOverlay').classList.remove('active');
    history.replaceState(null,'','?tab=' + tab);
}

/* ── Mobile Sidebar ── */
document.getElementById('menuToggle').addEventListener('click', () => {
    document.getElementById('admSidebar').classList.toggle('active');
    document.getElementById('admOverlay').classList.toggle('active');
});
document.getElementById('admOverlay').addEventListener('click', () => {
    document.getElementById('admSidebar').classList.remove('active');
    document.getElementById('admOverlay').classList.remove('active');
});

/* ── Theme ── */
function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('stf_theme', t);
    document.getElementById('themeIcon').className = (t === 'dark') ? 'fas fa-sun' : 'fas fa-moon';
    // Save to DB
    staffFetch({action:'save_settings', theme:t}).catch(()=>{});
}
const savedTheme = localStorage.getItem('stf_theme') || '<?= e($savedTheme) ?>';
applyTheme(savedTheme);
document.getElementById('themeToggle').addEventListener('click', () => {
    applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
});

/* ── Toast ── */
function showToast(msg, type='success') {
    const colors = {success:'#27AE60',error:'#E74C3C',warning:'#F39C12',info:'#2980B9'};
    const icons  = {success:'fa-check-circle',error:'fa-times-circle',warning:'fa-exclamation-triangle',info:'fa-info-circle'};
    const t = document.createElement('div');
    t.style.cssText = `padding:1.2rem 2rem;border-radius:12px;color:#fff;font-size:1.3rem;font-weight:500;
        box-shadow:0 8px 32px rgba(0,0,0,.18);display:flex;align-items:center;gap:.8rem;min-width:280px;
        font-family:'Poppins',sans-serif;background:${colors[type]||colors.success};
        animation:toastIn .3s ease;pointer-events:none;`;
    t.innerHTML = `<i class="fas ${icons[type]||icons.success}"></i>${msg}`;
    document.getElementById('toastWrap').appendChild(t);
    setTimeout(() => { t.style.transition='opacity .3s,transform .3s'; t.style.opacity='0'; t.style.transform='translateX(20px)'; setTimeout(()=>t.remove(),300); }, 4000);
}
const style = document.createElement('style');
style.textContent = '@keyframes toastIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}';
document.head.appendChild(style);

/* ── Modal ── */
function openModal(id){ const el=document.getElementById(id); if(el) el.style.display='flex'; }
function closeModal(id){ const el=document.getElementById(id); if(el) el.style.display='none'; }
document.addEventListener('keydown', e => { if(e.key==='Escape') document.querySelectorAll('.modal-bg').forEach(m=>m.style.display='none'); });

/* ── AJAX ── */
async function staffFetch(data) {
    const opts = {method:'POST'};
    if (data instanceof FormData) {
        opts.body = data;
    } else {
        opts.headers = {'Content-Type':'application/x-www-form-urlencoded'};
        opts.body = Object.entries(data).map(([k,v]) => encodeURIComponent(k)+'='+encodeURIComponent(v)).join('&');
    }
    const r = await fetch(`${BASE}/php/dashboards/staff_actions.php`, opts);
    if (!r.ok) throw new Error('Server error ' + r.status);
    return r.json();
}

async function doAction(data, successMsg=null) {
    try {
        const res = await staffFetch(data);
        if (res.success) { showToast(successMsg || res.message || 'Success', 'success'); return res; }
        else { showToast(res.message || 'An error occurred.', 'error'); return null; }
    } catch(e) { showToast('Network error. Please try again.', 'error'); return null; }
}

/* ── Confirm ── */
function confirmAction(msg) { return confirm(msg); }

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
    const urlTab = new URLSearchParams(window.location.search).get('tab') || '<?= e($active_tab) ?>';
    showTab(urlTab, document.querySelector(`.adm-nav-item[onclick*="${urlTab}"]`));
});
</script>


<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script></body>
</html>