<?php
/**
 * SETTINGS LANDING PAGE V2
 * Centralized manager for public-facing website content, chatbot KB, and bookings.
 * Modernized to Glassmorphic V2 UI.
 */

require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'landing_page';
$page_title  = 'Landing Page Content Manager';

// ── Quick DB Health Check ──
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
    if (!$r || mysqli_num_rows($r) == 0) { $tables_ok = false; $missing[] = $t; }
}

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

include '../includes/_sidebar.php';
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
  --primary: #3b82f6; /* Blue for site config */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
  --indigo: #6366f1;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #1e40af);
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Stat Row ── */
.la-stat-row { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:1.2rem; margin-bottom:2.5rem; }
.la-stat-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-md); padding:1.5rem; text-align:center; transition:var(--transition); cursor:pointer; box-shadow:var(--shadow-sm); }
.la-stat-card:hover { box-shadow:var(--shadow-md); transform:translateY(-3px); border-color:var(--primary); }
.la-stat-card i { font-size:1.8rem; color:var(--primary); margin-bottom:0.8rem; display:block; }
.la-stat-num { font-size:2.4rem; font-weight:800; color:var(--text-primary); line-height:1; }
.la-stat-lbl { font-size:1rem; font-weight:600; color:var(--text-secondary); margin-top:0.5rem; text-transform:uppercase; letter-spacing:0.05em; }

/* ── Tabbed Container ── */
.la-tabs-wrap { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-md); box-shadow:var(--shadow-sm); overflow:hidden; }
.la-tabs-nav { display:flex; flex-wrap:wrap; background:var(--surface-2); border-bottom:1px solid var(--border); padding:0.5rem 0.5rem 0; gap:0.2rem; }
.la-tab-btn { padding:1rem 1.5rem; font-size:1rem; font-weight:600; color:var(--text-secondary); background:none; border:none; border-bottom:3px solid transparent; cursor:pointer; transition:var(--transition); border-radius:var(--radius-sm) var(--radius-sm) 0 0; display:flex; align-items:center; gap:0.6rem; }
.la-tab-btn:hover { color:var(--text-primary); background:rgba(0,0,0,0.02); }
.la-tab-btn.active { color:var(--primary); border-bottom-color:var(--primary); background:var(--surface); }

.la-tab-panels { padding:2.5rem; }
.la-tab-panel { display:none; animation:fadeIn 0.3s ease; }
.la-tab-panel.active { display:block; }

/* ── Warning Banner ── */
.la-warning-banner { background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); border-radius:var(--radius-sm); padding:1.5rem; margin-bottom:2rem; display:flex; gap:1.2rem; align-items:flex-start; color:#b91c1c; }
.la-warning-banner i { font-size:1.5rem; margin-top:2px; }

/* ── Loader ── */
.la-loading { text-align:center; padding:5rem; color:var(--text-muted); }
.la-loading i { font-size:3rem; margin-bottom:1rem; color:var(--primary); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-outline { background:transparent; border:1.5px solid var(--primary); color:var(--primary); }
.btn-outline:hover { background:var(--primary); color:#fff; }

@keyframes fadeIn { from{opacity:0;transform:translateY(10px);} to{opacity:1;transform:translateY(0);} }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-globe"></i> Landing Page Control Center</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadeIn .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-desktop hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-magic"></i></div>
            <div class="staff-hero-info">
                <h2>Public Website Governance</h2>
                <p>Manage all public-facing content modules, chatbot intelligence, and appointment bookings.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <a href="/RMU-Medical-Management-System/index.php" target="_blank" class="btn" style="background:#fff; color:var(--primary);">
                    <i class="fas fa-external-link-alt"></i> Preview Public Site
                </a>
            </div>
        </div>

        <?php if (!$tables_ok): ?>
        <div class="la-warning-banner">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong style="font-size:1.3rem;">Critical Database Notice</strong><br>
                Some landing page tables are missing. Please run the migration to initialize the V2 content system.<br>
                <code style="display:block; margin:0.8rem 0; padding:0.5rem; background:rgba(0,0,0,0.05); border-radius:4px; font-size:1rem;"><?= implode(', ', $missing) ?></code>
                <a href="/RMU-Medical-Management-System/php/admin/run_landing_migration.php" class="btn btn-primary" style="margin-top:0.5rem; background:#b91c1c;">
                    <i class="fas fa-database"></i> Run Database Migration
                </a>
            </div>
        </div>
        <?php else: ?>

        <div class="la-stat-row">
            <div class="la-stat-card" onclick="switchTabTo('announcements')">
                <i class="fas fa-bullhorn"></i>
                <div class="la-stat-num"><?= $counts['announcements'] ?></div>
                <div class="la-stat-lbl">Active Alerts</div>
            </div>
            <div class="la-stat-card" onclick="switchTabTo('services')">
                <i class="fas fa-stethoscope"></i>
                <div class="la-stat-num"><?= $counts['services'] ?></div>
                <div class="la-stat-lbl">Services</div>
            </div>
            <div class="la-stat-card" onclick="switchTabTo('faq')">
                <i class="fas fa-question-circle"></i>
                <div class="la-stat-num"><?= $counts['faq'] ?></div>
                <div class="la-stat-lbl">FAQs</div>
            </div>
            <div class="la-stat-card" onclick="switchTabTo('gallery')">
                <i class="fas fa-images"></i>
                <div class="la-stat-num"><?= $counts['gallery'] ?></div>
                <div class="la-stat-lbl">Media</div>
            </div>
            <div class="la-stat-card" onclick="switchTabTo('chatbot')">
                <i class="fas fa-robot"></i>
                <div class="la-stat-num"><?= $counts['chatbot_kb'] ?></div>
                <div class="la-stat-lbl">AI Nodes</div>
            </div>
            <div class="la-stat-card" onclick="switchTabTo('bookings')">
                <i class="fas fa-calendar-check"></i>
                <div class="la-stat-num"><?= dbCount($conn, 'lp_bookings') ?></div>
                <div class="la-stat-lbl">Inquiries</div>
            </div>
        </div>

        <div class="la-tabs-wrap">
            <div class="la-tabs-nav" id="laTabNav">
                <button class="la-tab-btn active" data-tab="general"><i class="fas fa-sliders-h"></i> General</button>
                <button class="la-tab-btn" data-tab="hero"><i class="fas fa-image"></i> Hero</button>
                <button class="la-tab-btn" data-tab="announcements"><i class="fas fa-bullhorn"></i> Alerts</button>
                <button class="la-tab-btn" data-tab="services"><i class="fas fa-heartbeat"></i> Services</button>
                <button class="la-tab-btn" data-tab="faq"><i class="fas fa-comments"></i> FAQ</button>
                <button class="la-tab-btn" data-tab="gallery"><i class="fas fa-camera-retro"></i> Gallery</button>
                <button class="la-tab-btn" data-tab="team"><i class="fas fa-users"></i> Team</button>
                <button class="la-tab-btn" data-tab="chatbot"><i class="fas fa-microchip"></i> AI KB</button>
                <button class="la-tab-btn" data-tab="bookings"><i class="fas fa-envelope-open-text"></i> Inquiries</button>
                <button class="la-tab-btn" data-tab="config"><i class="fas fa-cog"></i> Config</button>
            </div>

            <div class="la-tab-panels">
                <div class="la-tab-panel active" id="panel-general">
                    <div class="la-loading"><i class="fas fa-circle-notch fa-spin"></i><br>Syncing Core Settings...</div>
                </div>
                <div class="la-tab-panel" id="panel-hero">
                    <div class="la-loading"><i class="fas fa-circle-notch fa-spin"></i><br>Fetching Visual Assets...</div>
                </div>
                <div class="la-tab-panel" id="panel-announcements">
                    <div class="la-loading"><i class="fas fa-circle-notch fa-spin"></i><br>Loading Bulletins...</div>
                </div>
                <div class="la-tab-panel" id="panel-services">
                    <div class="la-loading"><i class="fas fa-circle-notch fa-spin"></i><br>Fetching Clinical Services...</div>
                </div>
                <div class="la-tab-panel" id="panel-faq">
                    <div class="la-loading"><i class="fas fa-circle-notch fa-spin"></i><br>Loading Knowledge Base...</div>
                </div>
                <div class="la-tab-panel" id="panel-gallery">
                    <div class="la-loading"><i class="fas fa-circle-notch fa-spin"></i><br>Loading Media Library...</div>
                </div>
                <div class="la-tab-panel" id="panel-team">
                    <div class="la-loading"><i class="fas fa-circle-notch fa-spin"></i><br>Syncing Medical Staff...</div>
                </div>
                <div class="la-tab-panel" id="panel-chatbot">
                    <div class="la-loading"><i class="fas fa-circle-notch fa-spin"></i><br>Waking Up Chatbot Core...</div>
                </div>
                <div class="la-tab-panel" id="panel-bookings">
                    <div class="la-loading"><i class="fas fa-circle-notch fa-spin"></i><br>Fetching Public Inquiries...</div>
                </div>
                <div class="la-tab-panel" id="panel-config">
                    <div class="la-loading"><i class="fas fa-circle-notch fa-spin"></i><br>Accessing System Keys...</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- MODAL SYSTEM -->
<div class="modal" id="laModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="laModalTitle">Management Console</h3>
            <button class="btn btn-ghost" onclick="closeModal()" style="padding:0.5rem; width:40px; height:40px; border-radius:50%;"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="laModalBody">
            <!-- Dynamic Content -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal()">Discard</button>
            <button type="button" class="btn btn-primary" id="laModalSaveBtn">Apply Changes</button>
        </div>
    </div>
</div>

<script>
// Theme Management
(function() {
    const t = localStorage.getItem('rmu_theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
})();

const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});

// Tab Navigation Logic
function switchTabTo(tabName) {
    const btn = document.querySelector(`.la-tab-btn[data-tab="${tabName}"]`);
    if (btn) btn.click();
    document.querySelector('.la-tabs-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeModal() {
    document.getElementById('laModal').classList.remove('open');
}

// Internal scripts and AJAX handlers will be loaded via landing_admin_v2.js
// We'll bridge the legacy JS to the new UI structure
</script>
<script src="/RMU-Medical-Management-System/assets/js/landing_admin_v2.js"></script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
