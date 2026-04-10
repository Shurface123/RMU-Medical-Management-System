<?php
/**
 * settings_landing_page.php
 * Landing Page Admin Manager — centralized UI to manage all
 * public-facing landing page content, chatbot KB, and bookings.
 */

session_start();
require_once '../db_conn.php';

// ── Auth: Admin Only ──────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: /RMU-Medical-Management-System/php/index.php');
    exit;
}
$admin_name = $_SESSION['user_name'] ?? 'Admin';

// ── Quick DB Health Check ─────────────────────────────────────────
$tables_ok = true;
$required_tables = [
    'lp_hero_config', 'lp_stats', 'lp_announcements', 'lp_services',
    'lp_faq', 'lp_gallery', 'lp_testimonials', 'lp_team_members',
    'lp_director_profile', 'lp_chatbot_knowledge', 'lp_chat_logs',
    'lp_site_config'
];
$missing = [];
foreach ($required_tables as $t) {
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    if (!mysqli_num_rows($r)) { $tables_ok = false; $missing[] = $t; }
}

// ── Counts for dashboard cards ────────────────────────────────────
function dbCount($conn, $table, $where = '1') {
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM `$table` WHERE $where");
    return $r ? (int)mysqli_fetch_assoc($r)['c'] : 0;
}

$counts = $tables_ok ? [
    'announcements' => dbCount($conn, 'lp_announcements', 'is_active=1'),
    'services'      => dbCount($conn, 'lp_services', 'is_active=1'),
    'faq'           => dbCount($conn, 'lp_faq', 'is_active=1'),
    'gallery'       => dbCount($conn, 'lp_gallery'),
    'testimonials'  => dbCount($conn, 'lp_testimonials'),
    'chatbot_kb'    => dbCount($conn, 'lp_chatbot_knowledge', 'is_active=1'),
    'chat_logs'     => dbCount($conn, 'lp_chat_logs'),
    'team'          => dbCount($conn, 'lp_team_members'),
] : array_fill_keys(['announcements','services','faq','gallery','testimonials','chatbot_kb','chat_logs','team'], 0);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Landing Page Manager — RMU Admin</title>
  <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
  <style>
    /* ═══ PAGE LAYOUT ═══════════════════════════════════════════════ */
    body { font-family: 'Inter', sans-serif; background: var(--bg-primary); color: var(--text-primary); }

    .la-page {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem 1.5rem;
    }

    /* ═══ PAGE HEADER ════════════════════════════════════════════════ */
    .la-page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--border);
    }

    .la-page-title {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .la-page-title-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: linear-gradient(135deg, #2F80ED, #56CCF2);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      flex-shrink: 0;
    }

    .la-page-title h1 { font-size: 1.6rem; font-weight: 800; margin-bottom: 0.2rem; }
    .la-page-title p  { font-size: 0.88rem; color: var(--text-muted); }

    .la-page-actions { display: flex; gap: 0.8rem; align-items: center; flex-wrap: wrap; }

    /* ═══ STAT CARDS ══════════════════════════════════════════════════ */
    .la-stat-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .la-stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.2rem;
      text-align: center;
      transition: all 0.2s;
      cursor: pointer;
    }

    .la-stat-card:hover {
      border-color: #2F80ED;
      box-shadow: 0 8px 24px rgba(47,128,237,0.12);
      transform: translateY(-2px);
    }

    .la-stat-card i { font-size: 1.6rem; color: #2F80ED; margin-bottom: 0.5rem; display: block; }
    .la-stat-card .la-stat-num { font-size: 1.8rem; font-weight: 800; color: var(--text-primary); }
    .la-stat-card .la-stat-lbl { font-size: 0.78rem; color: var(--text-muted); font-weight: 500; margin-top: 0.2rem; }

    /* ═══ WARNING BANNER ══════════════════════════════════════════════ */
    .la-warning-banner {
      background: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.3);
      border-radius: 12px;
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      color: #b91c1c;
    }

    .la-warning-banner i { flex-shrink: 0; font-size: 1.2rem; margin-top: 2px; }

    /* ═══ TAB SYSTEM ══════════════════════════════════════════════════ */
    .la-tabs-wrap {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 18px;
      overflow: hidden;
    }

    .la-tabs-nav {
      display: flex;
      flex-wrap: wrap;
      gap: 0;
      background: var(--bg-secondary, var(--bg-alt));
      border-bottom: 1px solid var(--border);
      padding: 0.5rem 0.5rem 0;
    }

    .la-tab-btn {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.7rem 1.1rem;
      font-size: 0.85rem;
      font-weight: 500;
      color: var(--text-muted);
      background: none;
      border: none;
      border-bottom: 2px solid transparent;
      cursor: pointer;
      transition: all 0.2s;
      border-radius: 8px 8px 0 0;
      white-space: nowrap;
    }

    .la-tab-btn:hover { color: var(--text-primary); background: rgba(47,128,237,0.06); }
    .la-tab-btn.active { color: #2F80ED; border-bottom-color: #2F80ED; background: var(--bg-card); font-weight: 600; }
    .la-tab-btn i { font-size: 0.9rem; }

    .la-tab-panels { padding: 2rem; }

    .la-tab-panel { display: none; }
    .la-tab-panel.active { display: block; animation: fadeInPanel 0.25s ease; }

    @keyframes fadeInPanel { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

    /* ═══ TOOLBAR ════════════════════════════════════════════════════ */
    .la-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .la-section-head {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin: 0;
    }

    .la-section-head i { color: #2F80ED; }

    /* ═══ BUTTONS ════════════════════════════════════════════════════ */
    .la-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.55rem 1.2rem;
      border-radius: 10px;
      font-size: 0.88rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
      font-family: inherit;
    }

    .la-btn-primary { background: #2F80ED; color: #fff; }
    .la-btn-primary:hover { background: #2366cc; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(47,128,237,.3); }

    .la-btn-danger { background: #e74c3c; color: #fff; }
    .la-btn-danger:hover { background: #c0392b; }

    .la-btn-outline { background: transparent; color: #2F80ED; border: 1.5px solid #2F80ED; }
    .la-btn-outline:hover { background: #2F80ED; color: #fff; }

    .la-btn-sm {
      padding: 0.35rem 0.65rem;
      border-radius: 7px;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      transition: all 0.15s;
    }

    .la-btn-edit   { background: rgba(47,128,237,0.12); color: #2F80ED; }
    .la-btn-edit:hover { background: #2F80ED; color: #fff; }

    .la-btn-delete { background: rgba(231,76,60,0.12); color: #e74c3c; }
    .la-btn-delete:hover { background: #e74c3c; color: #fff; }

    .la-btn-toggle { background: rgba(39,174,96,0.12); color: #27ae60; }
    .la-btn-toggle:hover { background: #27ae60; color: #fff; }

    /* ═══ TABLE ══════════════════════════════════════════════════════ */
    .la-table-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid var(--border); }

    .la-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.88rem;
    }

    .la-table thead th {
      background: var(--bg-secondary, var(--bg-alt));
      color: var(--text-muted);
      font-weight: 600;
      font-size: 0.76rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 0.9rem 1.1rem;
      text-align: left;
      white-space: nowrap;
    }

    .la-table tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
    .la-table tbody tr:last-child { border: none; }
    .la-table tbody tr:hover { background: rgba(47,128,237,0.04); }
    .la-table tbody td { padding: 0.9rem 1.1rem; vertical-align: middle; }

    /* ═══ FORMS ══════════════════════════════════════════════════════ */
    .la-form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.2rem;
    }

    @media (max-width: 620px) { .la-form-grid { grid-template-columns: 1fr; } }

    .la-fg { display: flex; flex-direction: column; gap: 0.4rem; }
    .la-fg label { font-size: 0.83rem; font-weight: 600; color: var(--text-secondary); }

    .la-fg.la-full { grid-column: 1 / -1; }

    .la-inp {
      padding: 0.65rem 0.9rem;
      border: 1.5px solid var(--border);
      border-radius: 9px;
      background: var(--bg-secondary, var(--bg-alt));
      color: var(--text-primary);
      font-family: inherit;
      font-size: 0.88rem;
      outline: none;
      transition: border-color 0.2s;
      width: 100%;
      box-sizing: border-box;
    }

    .la-inp:focus { border-color: #2F80ED; box-shadow: 0 0 0 3px rgba(47,128,237,.1); }
    .la-inp-sm { padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.78rem; border: 1.5px solid var(--border); background: var(--bg-secondary, var(--bg-alt)); color: var(--text-primary); }

    /* ═══ BADGES & CHIPS ══════════════════════════════════════════════ */
    .la-badge {
      display: inline-block;
      padding: 0.2rem 0.65rem;
      border-radius: 50px;
      font-size: 0.72rem;
      font-weight: 700;
    }

    .la-badge-success { background: rgba(39,174,96,0.15); color: #27ae60; }
    .la-badge-muted   { background: rgba(107,114,128,0.15); color: #6b7280; }

    .la-chip {
      display: inline-block;
      padding: 0.2rem 0.7rem;
      border-radius: 50px;
      font-size: 0.75rem;
      font-weight: 600;
      background: rgba(47,128,237,0.1);
      color: #2F80ED;
    }

    .la-icon { font-size: 1.1rem; color: #2F80ED; }

    /* ═══ GALLERY GRID ════════════════════════════════════════════════ */
    .la-gallery-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1rem;
    }

    .la-gallery-card {
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid var(--border);
      background: var(--bg-secondary, var(--bg-alt));
      transition: all 0.2s;
    }

    .la-gallery-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }

    .la-gallery-card img {
      width: 100%;
      height: 150px;
      object-fit: cover;
      display: block;
    }

    .la-gallery-info {
      padding: 0.7rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
    }

    .la-gallery-title { font-size: 0.82rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .la-gallery-actions { display: flex; gap: 0.3rem; flex-shrink: 0; }

    /* ═══ MODAL ═══════════════════════════════════════════════════════ */
    .la-modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      backdrop-filter: blur(4px);
      z-index: 99999;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s;
    }

    .la-modal-backdrop.open { opacity: 1; pointer-events: all; }

    .la-modal {
      background: var(--bg-card);
      border-radius: 18px;
      width: 100%;
      max-width: 680px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 24px 80px rgba(0,0,0,0.25);
      transform: translateY(-20px);
      transition: transform 0.25s;
    }

    .la-modal-backdrop.open .la-modal { transform: translateY(0); }

    .la-modal-header {
      padding: 1.5rem 1.8rem 1.2rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .la-modal-header h3 { font-size: 1.1rem; font-weight: 700; }

    .la-modal-close {
      width: 32px; height: 32px;
      border-radius: 8px;
      background: var(--bg-secondary, var(--bg-alt));
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-muted);
      font-size: 0.9rem;
      transition: background 0.2s;
    }

    .la-modal-close:hover { background: rgba(231,76,60,0.1); color: #e74c3c; }

    .la-modal-body { padding: 1.5rem 1.8rem; }

    .la-modal-footer {
      padding: 1rem 1.8rem 1.5rem;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 0.8rem;
      justify-content: flex-end;
    }

    /* ═══ TOAST ═══════════════════════════════════════════════════════ */
    .la-toast {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      display: flex;
      align-items: center;
      gap: 0.7rem;
      padding: 0.85rem 1.4rem;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 600;
      box-shadow: 0 8px 32px rgba(0,0,0,0.15);
      z-index: 999999;
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
      max-width: 340px;
    }

    .la-toast.visible { opacity: 1; transform: translateY(0); }
    .la-toast-success { background: #27ae60; color: #fff; }
    .la-toast-error   { background: #e74c3c; color: #fff; }

    /* ═══ LOADING ═════════════════════════════════════════════════════ */
    .la-loading {
      text-align: center;
      padding: 3rem;
      color: var(--text-muted);
      font-size: 1rem;
    }

    .la-loading i { font-size: 1.5rem; display: block; margin-bottom: 0.7rem; color: #2F80ED; }

    /* ═══ LINK ════════════════════════════════════════════════════════ */
    .la-preview-link {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      color: #2F80ED;
      font-size: 0.88rem;
      font-weight: 500;
      padding: 0.4rem 0.8rem;
      border-radius: 7px;
      background: rgba(47,128,237,0.08);
      transition: all 0.2s;
    }

    .la-preview-link:hover { background: rgba(47,128,237,0.15); }

    @media (max-width: 768px) {
      .la-page { padding: 1rem; }
      .la-tabs-nav { overflow-x: auto; }
      .la-tab-btn { padding: 0.6rem 0.8rem; font-size: 0.78rem; }
    }
  </style>
</head>
<body>

<?php /* ── Sidebar/topbar already included by parent template in real usage.
           For standalone testing, include inline nav: */ ?>

<div class="la-page">

  <!-- ── PAGE HEADER ────────────────────────────────── -->
  <div class="la-page-header">
    <div class="la-page-title">
      <div class="la-page-title-icon"><i class="fas fa-globe"></i></div>
      <div>
        <h1>Landing Page Manager</h1>
        <p>Manage all public-facing content, chatbot, and bookings from one place</p>
      </div>
    </div>
    <div class="la-page-actions">
      <a href="/RMU-Medical-Management-System/html/index.html" target="_blank" class="la-preview-link">
        <i class="fas fa-external-link-alt"></i> Preview Site
      </a>
      <a href="/RMU-Medical-Management-System/php/admin/settings_health_messages.php" class="la-btn la-btn-outline">
        <i class="fas fa-arrow-left"></i> Back to Settings
      </a>
    </div>
  </div>

  <?php if (!$tables_ok): ?>
  <!-- ── DB WARNING ─────────────────────────────────── -->
  <div class="la-warning-banner">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
      <strong>Database tables missing!</strong> The following tables need to be created first by running the migration script:
      <code style="font-size:.82rem"><?= implode(', ', $missing) ?></code>
      <br>
      <a href="/RMU-Medical-Management-System/run_landing_migration.php" class="la-btn la-btn-danger" style="margin-top:.8rem">
        <i class="fas fa-database"></i> Run Migration Now
      </a>
    </div>
  </div>
  <?php else: ?>

  <!-- ── STAT CARDS ─────────────────────────────────── -->
  <div class="la-stat-row">
    <div class="la-stat-card" onclick="switchTabTo('announcements')">
      <i class="fas fa-bullhorn"></i>
      <div class="la-stat-num"><?= $counts['announcements'] ?></div>
      <div class="la-stat-lbl">Active Announcements</div>
    </div>
    <div class="la-stat-card" onclick="switchTabTo('services')">
      <i class="fas fa-stethoscope"></i>
      <div class="la-stat-num"><?= $counts['services'] ?></div>
      <div class="la-stat-lbl">Services Listed</div>
    </div>
    <div class="la-stat-card" onclick="switchTabTo('faq')">
      <i class="fas fa-circle-question"></i>
      <div class="la-stat-num"><?= $counts['faq'] ?></div>
      <div class="la-stat-lbl">FAQ Entries</div>
    </div>
    <div class="la-stat-card" onclick="switchTabTo('gallery')">
      <i class="fas fa-images"></i>
      <div class="la-stat-num"><?= $counts['gallery'] ?></div>
      <div class="la-stat-lbl">Gallery Images</div>
    </div>
    <div class="la-stat-card" onclick="switchTabTo('testimonials')">
      <i class="fas fa-star"></i>
      <div class="la-stat-num"><?= $counts['testimonials'] ?></div>
      <div class="la-stat-lbl">Testimonials</div>
    </div>
    <div class="la-stat-card" onclick="switchTabTo('chatbot')">
      <i class="fas fa-robot"></i>
      <div class="la-stat-num"><?= $counts['chatbot_kb'] ?></div>
      <div class="la-stat-lbl">KB Entries</div>
    </div>
    <div class="la-stat-card" onclick="switchTabTo('logs')">
      <i class="fas fa-comments"></i>
      <div class="la-stat-num"><?= $counts['chat_logs'] ?></div>
      <div class="la-stat-lbl">Chat Logs</div>
    </div>
    <div class="la-stat-card" onclick="switchTabTo('team')">
      <i class="fas fa-users"></i>
      <div class="la-stat-num"><?= $counts['team'] ?></div>
      <div class="la-stat-lbl">Team Members</div>
    </div>
  </div>

  <!-- ── TABBED MANAGER ─────────────────────────────── -->
  <div class="la-tabs-wrap">

    <!-- Tab Navigation -->
    <div class="la-tabs-nav" id="laTabNav">
      <button class="la-tab-btn" data-tab="general">
        <i class="fas fa-sliders"></i> General
      </button>
      <button class="la-tab-btn" data-tab="stats">
        <i class="fas fa-chart-bar"></i> Stats
      </button>
      <button class="la-tab-btn" data-tab="announcements">
        <i class="fas fa-bullhorn"></i> Announcements
      </button>
      <button class="la-tab-btn" data-tab="services">
        <i class="fas fa-stethoscope"></i> Services
      </button>
      <button class="la-tab-btn" data-tab="faq">
        <i class="fas fa-circle-question"></i> FAQ
      </button>
      <button class="la-tab-btn" data-tab="gallery">
        <i class="fas fa-images"></i> Gallery
      </button>
      <button class="la-tab-btn" data-tab="testimonials">
        <i class="fas fa-star"></i> Testimonials
      </button>
      <button class="la-tab-btn" data-tab="team">
        <i class="fas fa-users"></i> Team
      </button>
      <button class="la-tab-btn" data-tab="director">
        <i class="fas fa-user-tie"></i> Director
      </button>
      <button class="la-tab-btn" data-tab="chatbot">
        <i class="fas fa-robot"></i> Chatbot KB
      </button>
      <button class="la-tab-btn" data-tab="logs">
        <i class="fas fa-comments"></i> Chat Logs
      </button>
      <button class="la-tab-btn" data-tab="bookings">
        <i class="fas fa-calendar-check"></i> Bookings
      </button>
      <button class="la-tab-btn" data-tab="config">
        <i class="fas fa-cog"></i> Config
      </button>
    </div>

    <!-- Tab Panels -->
    <div class="la-tab-panels">

      <div class="la-tab-panel" id="panel-general">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-stats">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-announcements">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-services">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-faq">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-gallery">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-testimonials">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-team">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-director">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-chatbot">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-logs">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-bookings">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

      <div class="la-tab-panel" id="panel-config">
        <div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
      </div>

    </div><!-- /la-tab-panels -->
  </div><!-- /la-tabs-wrap -->

  <?php endif; ?>

</div><!-- /la-page -->

<!-- ═══ MODAL ═════════════════════════════════════════════════════ -->
<div class="la-modal-backdrop" id="laModal">
  <div class="la-modal">
    <div class="la-modal-header">
      <h3 id="laModalTitle">Edit</h3>
      <button class="la-modal-close" id="laModalClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="la-modal-body" id="laModalBody"></div>
    <div class="la-modal-footer">
      <button class="la-btn la-btn-outline" id="laModalCancelBtn">Cancel</button>
      <button class="la-btn la-btn-primary" id="laModalSaveBtn">
        <i class="fas fa-save"></i> Save
      </button>
    </div>
  </div>
</div>

<!-- ═══ SCRIPTS ═══════════════════════════════════════════════════ -->
<script>
  // Theme persistence
  (function() {
    const t = localStorage.getItem('rmu_theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
  })();

  // Quick stats card → tab jump
  function switchTabTo(tabName) {
    const btn = document.querySelector(`.la-tab-btn[data-tab="${tabName}"]`);
    if (btn) btn.click();
    document.querySelector('.la-tabs-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
</script>
<script src="/RMU-Medical-Management-System/js/landing_admin.js"></script>

</body>
</html>
