<?php
/**
 * RMU Medical Sickbay — System Settings v2.0
 * Comprehensive Configuration Center
 */
session_start();
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
            <div class="adm-avatar">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" alt="Admin">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <!-- Dashboard Hero -->
        <div class="hero-banner" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h1 style="font-size: 2.2rem; font-weight: 700; margin-bottom: 0.5rem;">System Configuration</h1>
                <p style="opacity: 0.9; font-size: 1.1rem;">Manage hospital profile, departments, security policies, and clinical thresholds.</p>
            </div>
            <div style="font-size: 4rem; opacity: 0.2;"><i class="fas fa-tools"></i></div>
        </div>

        <!-- Settings Navigation Tabs -->
        <div class="adm-tabs">
            <?php
            $tabs = [
                'hospital'   => ['icon' => 'hospital', 'label' => 'Hospital Profile'],
                'wards'      => ['icon' => 'procedures', 'label' => 'Depts & Wards'],
                'users'      => ['icon' => 'users-cog', 'label' => 'Users & Roles'],
                'shifts'     => ['icon' => 'clock', 'label' => 'Shifts & Schedule'],
                'vitals'     => ['icon' => 'heartbeat', 'label' => 'Vitals Thresholds'],
                'meds'       => ['icon' => 'pills', 'label' => 'Medications'],
                'notifs'     => ['icon' => 'bell', 'label' => 'Notifications'],
                'appearance' => ['icon' => 'palette', 'label' => 'Appearance'],
                'security'   => ['icon' => 'shield-alt', 'label' => 'Security'],
                'integrations'=>['icon' => 'plug', 'label' => 'Integrations & APIs'],
                'system'     => ['icon' => 'server', 'label' => 'Maintenance']
            ];

            foreach ($tabs as $id => $info):
                $active = ($active_tab == $id) ? 'active' : '';
            ?>
            <a href="?tab=<?= $id ?>" class="btn btn-primary adm-tab-btn <?= $active ?>"><span class="btn-text">
                <i class="fas fa-<?= $info['icon'] ?>"></i>
                <span><?= $info['label'] ?></span>
            </span></a>
            <?php endforeach; ?>
        </div>

        <!-- Flash Message Container -->
        <div id="settingsAlert" style="display:none; margin-bottom: 1.5rem;"></div>

        <!-- Tab Content Loader -->
        <div class="settings-tab-content">
            <?php
            $tab_file = "settings_tabs/tab_{$active_tab}.php";
            if (file_exists($tab_file)) {
                include $tab_file;
            } else {
                echo "<div class='card' style='padding: 3rem; text-align: center;'>
                        <i class='fas fa-construction fa-4x' style='color: var(--warning); margin-bottom: 1rem;'></i>
                        <h2>Module '{$active_tab}' Coming Soon</h2>
                        <p>This section is currently under construction in Phase 1.</p>
                      </div>";
            }
            ?>
        </div>
    </div>
</main>

<style>
.settings-card { background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 2.2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem; }
.settings-card-header { border-bottom: 1px solid var(--border); margin-bottom: 2rem; padding-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
.settings-card-title { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 0.8rem; }
.settings-card-title i { color: var(--primary); }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; margin-bottom: 0.6rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; }

@media (max-width: 992px) { .grid-2 { grid-template-columns: 1fr; } }
</style>

<?php include __DIR__.'/../includes/active_sessions_panel.php'; ?>
<script>
// Unified AJAX handler for settings
async function saveSettings(formId, action) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    formData.append('action', action);
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

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
            // Update any logo previews if applicable
            document.querySelectorAll('.logo-preview').forEach(img => img.src = '/RMU-Medical-Management-System/' + result.logo);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('A system error occurred. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        submitBtn.innerHTML = originalBtnHtml;
    }
}
</script>

</body>
</html>
