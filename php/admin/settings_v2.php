<?php
/**
 * RMU Medical Sickbay — System Settings v2.0
 * Comprehensive Configuration Center
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db_conn.php';

// Authentication Guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$page_title = "System Settings";
$active_page = "settings";
include '../includes/_sidebar.php';

// Fetch Global Config
$config = [];
$res = mysqli_query($conn, "SELECT config_key, config_value FROM system_config");
while($row = mysqli_fetch_assoc($res)) {
    $config[$row['config_key']] = $row['config_value'];
}

// Fetch Hospital Settings
$h_res = mysqli_query($conn, "SELECT * FROM hospital_settings WHERE id = 1");
$hospital = mysqli_fetch_assoc($h_res) ?: [];

// Active Tab from URL
$active_tab = $_GET['tab'] ?? 'hospital';
?>

<style>
/* ── Settings Specific Enhancements ── */
.adm-tabs {
    display: flex;
    gap: 0.8rem;
    padding: 0.5rem;
    background: var(--surface-2);
    border-radius: var(--radius-md);
    margin-bottom: 2.5rem;
    overflow-x: auto;
    scrollbar-width: none;
    border: 1px solid var(--border);
    backdrop-filter: blur(10px);
}
.adm-tabs::-webkit-scrollbar { display: none; }

.adm-tab-btn {
    padding: 1rem 1.8rem;
    border-radius: 14px;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-secondary);
    background: transparent;
    border: none;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.8rem;
    white-space: nowrap;
    cursor: pointer;
}

.adm-tab-btn i { font-size: 1.4rem; }
.adm-tab-btn:hover { background: var(--primary-light); color: var(--primary); }
.adm-tab-btn.active { background: var(--primary); color: #fff; box-shadow: 0 4px 15px rgba(47, 128, 237, 0.3); }

.settings-card { 
    background: var(--surface); 
    border-radius: var(--radius-lg); 
    border: 1px solid var(--border); 
    padding: 2.8rem; 
    box-shadow: var(--shadow-sm); 
    margin-bottom: 2.8rem;
    transition: var(--transition);
}
.settings-card:hover { box-shadow: var(--shadow-md); }

.settings-card-header { 
    border-bottom: 1px solid var(--border); 
    margin-bottom: 2.8rem; 
    padding-bottom: 1.5rem; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
}
.settings-card-title { 
    font-size: 1.8rem; 
    font-weight: 700; 
    color: var(--text-primary); 
    display: flex; 
    align-items: center; 
    gap: 1.2rem; 
}
.settings-card-title i { color: var(--primary); }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; }
.form-group { margin-bottom: 2.2rem; }
.form-group label { 
    display: block; 
    margin-bottom: 1rem; 
    font-weight: 700; 
    color: var(--text-secondary); 
    text-transform: uppercase; 
    font-size: 0.95rem; 
    letter-spacing: 0.08em; 
}

.form-control {
    width: 100%;
    padding: 1.1rem 1.4rem;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--surface);
    color: var(--text-primary);
    font-family: inherit;
    font-size: 1.35rem;
    transition: var(--transition);
}
.form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-light); outline: none; }

.hero-banner {
    position: relative;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    padding: 3.5rem;
    border-radius: var(--radius-lg);
    margin-bottom: 3rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}
.hero-banner::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 450px;
    height: 450px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

@media (max-width: 992px) { 
    .grid-2 { grid-template-columns: 1fr; }
    .hero-banner { padding: 2.5rem; }
    .adm-tabs { flex-wrap: nowrap; }
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<main class="adm-main">
    <!-- Topbar -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title">
                <i class="fas fa-cogs" style="color:var(--primary);margin-right:.8rem;"></i>
                Configuration Center
            </span>
        </div>
        
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar" style="overflow:hidden; border:2px solid var(--primary-light);">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" alt="Admin" style="width:100%; height:100%; object-fit:cover;">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <!-- Dashboard Hero -->
        <div class="hero-banner">
            <div style="position:relative; z-index:2;">
                <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 0.5rem; letter-spacing:-0.03em;">System Control Panel</h1>
                <p style="opacity: 0.9; font-size: 1.4rem; max-width: 650px; line-height: 1.6;">Configure hospital identity, clinical protocols, and security layers from one unified glassmorphic interface.</p>
            </div>
            <div style="font-size: 8rem; opacity: 0.18; position:relative; z-index:1;"><i class="fas fa-microchip"></i></div>
        </div>

        <!-- Settings Navigation Tabs -->
        <div class="adm-tabs">
            <?php
            $tabs = [
                'hospital'   => ['icon' => 'hospital', 'label' => 'Hospital'],
                'wards'      => ['icon' => 'procedures', 'label' => 'Departments'],
                'users'      => ['icon' => 'users-cog', 'label' => 'User Roles'],
                'shifts'     => ['icon' => 'clock', 'label' => 'Scheduling'],
                'vitals'     => ['icon' => 'heartbeat', 'label' => 'Clinical'],
                'meds'       => ['icon' => 'pills', 'label' => 'Formulary'],
                'notifs'     => ['icon' => 'bell', 'label' => 'Alerts'],
                'appearance' => ['icon' => 'palette', 'label' => 'Themes'],
                'security'   => ['icon' => 'shield-alt', 'label' => 'Security'],
                'integrations'=>['icon' => 'plug', 'label' => 'APIs'],
                'system'     => ['icon' => 'server', 'label' => 'Maintenance']
            ];

            foreach ($tabs as $id => $info):
                $active = ($active_tab == $id) ? 'active' : '';
            ?>
            <a href="?tab=<?= $id ?>" class="adm-tab-btn <?= $active ?>">
                <i class="fas fa-<?= $info['icon'] ?>"></i>
                <span><?= $info['label'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Flash Message Container -->
        <div id="settingsAlert" style="display:none; margin-bottom: 2rem;"></div>

        <!-- Tab Content Loader -->
        <div class="settings-tab-content" style="animation: fadeUp 0.4s ease-out;">
            <?php
            $tab_file = "settings_tabs/tab_{$active_tab}.php";
            if (file_exists($tab_file)) {
                include $tab_file;
            } else {
                echo "<div class='settings-card' style='padding: 6rem; text-align: center; border-style: dashed;'>
                        <div style='width:90px; height:90px; background:var(--warning-light); color:var(--warning); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 2.5rem; font-size:3rem;'>
                            <i class='fas fa-tools'></i>
                        </div>
                        <h2 style='font-size:2.2rem; font-weight:800; color:var(--text-primary);'>Module '{$active_tab}' Under Development</h2>
                        <p style='color:var(--text-secondary); font-size:1.3rem; margin-top:0.8rem; max-width:500px; margin-left:auto; margin-right:auto;'>We're finalizing this configuration layer as part of the Phase 2 clinical rollout. Check back soon for full integration.</p>
                        <a href='?tab=hospital' class='btn btn-primary' style='margin-top:2.5rem; border-radius:12px; padding:1.2rem 2.5rem;'><span class='btn-text'>Back to Hospital Profile</span></a>
                      </div>";
            }
            ?>
        </div>
    </div>
</main>

<script>
// Unified AJAX handler for settings
async function saveSettings(formId, action) {
    const form = document.getElementById(formId);
    if(!form) return;
    
    const formData = new FormData(form);
    formData.append('action', action);
    
    const submitBtn = form.querySelector('button[type="submit"]');
    if(!submitBtn) return;
    
    const originalBtnHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';

    try {
        const response = await fetch('admin_settings_actions.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?? '' ?>'
            }
        });
        const result = await response.json();
        
        if (typeof showToast === 'function') {
            showToast(result.message, result.success ? 'success' : 'error');
        } else {
            alert(result.message);
        }
        
        if (result.success && action === 'save_hospital_profile' && result.logo) {
            document.querySelectorAll('.logo-preview, #logoPreview').forEach(img => img.src = '/RMU-Medical-Management-System/' + result.logo);
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof showToast === 'function') showToast('A system error occurred. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHtml;
    }
}

// Sidebar toggle persistence
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('admSidebar');
    const overlay = document.getElementById('admOverlay');
    const menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        menuToggle.onclick = () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        };
    }
    if (overlay) {
        overlay.onclick = () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        };
    }

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    if (themeToggle) {
        themeToggle.onclick = () => {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme') || 'light';
            const target = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', target);
            localStorage.setItem('rmu_theme', target);
            if (themeIcon) themeIcon.className = target === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        };
    }
});
</script>

<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
