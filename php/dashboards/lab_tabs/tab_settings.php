<?php
// ============================================================
// LAB DASHBOARD - TAB SETTINGS (Module 14)
// ============================================================
if (!isset($user_id)) { exit; }

// Fetch user settings
$set_q = mysqli_query($conn, "SELECT * FROM lab_technician_settings WHERE user_id = $user_id LIMIT 1");
$settings = mysqli_fetch_assoc($set_q);

// Defaults if not set
$theme = $settings['theme_preference'] ?? 'light';
$notif_email = $settings['email_notifications'] ?? 1;
$notif_sms = $settings['sms_notifications'] ?? 0;
$default_view = $settings['default_view'] ?? 'overview';
?>

<div class="sec-header">
    <h2><i class="fas fa-cog"></i> System Settings</h2>
</div>

<div class="charts-grid" style="grid-template-columns: 1fr 1fr;">

    <!-- User Preferences -->
    <div class="info-card">
        <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem;"><i class="fas fa-sliders-h"></i> Dashboard Preferences</h3>
        
        <form onsubmit="savePreferences(event)">
            <div class="form-group">
                <label>Default Startup View</label>
                <select id="def_view" class="form-select">
                    <option value="overview" <?= $default_view === 'overview' ? 'selected' : '' ?>>Main Overview</option>
                    <option value="orders" <?= $default_view === 'orders' ? 'selected' : '' ?>>Test Orders Queue</option>
                    <option value="samples" <?= $default_view === 'samples' ? 'selected' : '' ?>>Sample Tracking</option>
                    <option value="results" <?= $default_view === 'results' ? 'selected' : '' ?>>Result Entry</option>
                </select>
                <small style="color:var(--text-muted);">Which page should open when you log in?</small>
            </div>

            <div class="form-group mt-4">
                <label>Theme Preference</label>
                <div style="display:flex; gap:1rem; margin-top: .5rem;">
                    <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer;">
                        <input type="radio" name="theme" value="light" <?= $theme === 'light' ? 'checked' : '' ?> onchange="applyTheme('light')"> Light Mode
                    </label>
                    <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer;">
                        <input type="radio" name="theme" value="dark" <?= $theme === 'dark' ? 'checked' : '' ?> onchange="applyTheme('dark')"> Dark Mode
                    </label>
                </div>
            </div>

            <div class="form-group mt-4">
                <label>Notification Settings</label>
                <div style="margin-top: .5rem;">
                    <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer; margin-bottom: .8rem;">
                        <input type="checkbox" id="notif_email" <?= $notif_email ? 'checked' : '' ?>> Receive Critical Alerts via Email
                    </label>
                    <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer;">
                        <input type="checkbox" id="notif_sms" <?= $notif_sms ? 'checked' : '' ?>> Receive STAT Orders via SMS
                    </label>
                </div>
            </div>

            <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-save"></i> Save Preferences</button>
            </div>
        </form>
    </div>

    <!-- Security Settings -->
    <div class="info-card">
        <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem;"><i class="fas fa-shield-alt"></i> Security & Password</h3>
        
        <form onsubmit="changePassword(event)">
            <div class="form-group">
                <label>Current Password <span style="color:var(--danger)">*</span></label>
                <input type="password" id="cur_pass" class="form-control" required>
            </div>
            <div class="form-group">
                <label>New Password <span style="color:var(--danger)">*</span></label>
                <input type="password" id="new_pass" class="form-control" required minlength="8">
                <small style="color:var(--text-muted);">Must be at least 8 characters long.</small>
            </div>
            <div class="form-group">
                <label>Confirm New Password <span style="color:var(--danger)">*</span></label>
                <input type="password" id="conf_pass" class="form-control" required minlength="8">
            </div>

            <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="submit" class="adm-btn adm-btn-teal"><i class="fas fa-key"></i> Update Password</button>
            </div>
        </form>
    </div>

    <!-- Developer / DB Reset -->
    <div class="info-card" style="border-top: 3px solid var(--danger);">
        <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; color:var(--danger);"><i class="fas fa-database"></i> Database Migrations</h3>
        <p style="color:var(--text-secondary); font-size:0.95em; margin-bottom:1.5rem;">Execute Phase 7 data seeder script to securely inject standardized medical test catalogs, parameters, and reference ranges into the system. Note: This will safely abort if data already exists to prevent duplicate corruption.</p>
        <button type="button" class="adm-btn adm-btn-danger" id="seederBtn" onclick="runDatabaseSeeder()"><i class="fas fa-bolt"></i> Execute Initial Setup Seeder</button>
    </div>

</div>

<script>
function savePreferences(e) {
    e.preventDefault();
    alert('Saving user settings via AJAX to `lab_technician_settings` (Pending handler in lab_actions.php)');
}

function changePassword(e) {
    e.preventDefault();
    const c = document.getElementById('new_pass').value;
    const cf = document.getElementById('conf_pass').value;
    if (c !== cf) {
        alert("New passwords do not match.");
        return;
    }
    alert('Updating password securely... (Pending handler)');
}

function runDatabaseSeeder() {
    if(!confirm("WARNING: This will initiate the Phase 7 database seeding script. Proceed?")) return;
    
    let btn = $('#seederBtn');
    let originHtml = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i> Seeding Database...').prop('disabled', true);
    
    $.ajax({
        url: 'lab_actions.php',
        type: 'POST',
        data: { action: 'run_database_seeder', csrf_token: document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        dataType: 'json',
        success: function(res) {
            btn.html(originHtml).prop('disabled', false);
            if(res.success) {
                alert("Success: \n" + res.message);
                location.reload();
            } else {
                alert("Aborted: " + res.message);
            }
        },
        error: function(err) {
            btn.html(originHtml).prop('disabled', false);
            console.error(err);
            alert("Critical AJAX Fault. Check console logs.");
        }
    });
}
</script>
