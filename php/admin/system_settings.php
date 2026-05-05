<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';
require_once '../classes/AuditLogger.php';

$active_page = 'system_settings';
$page_title  = 'Global System Configuration';

$auditLogger = new AuditLogger($conn);
$message = '';
$error = '';

// Fetch current settings
$settings = [];
$q = mysqli_query($conn, "SELECT * FROM system_settings");
while($r = mysqli_fetch_assoc($q)) $settings[$r['setting_key']] = $r['setting_value'];

// Handle POST updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $to_update = [
        'hospital_name' => $_POST['hospital_name'],
        'hospital_email' => $_POST['hospital_email'],
        'hospital_phone' => $_POST['hospital_phone'],
        'hospital_address' => $_POST['hospital_address'],
        'system_timezone' => $_POST['system_timezone'],
        'session_timeout' => $_POST['session_timeout'],
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'allow_patient_registration' => isset($_POST['allow_patient_registration']) ? '1' : '0',
        'allow_staff_registration' => isset($_POST['allow_staff_registration']) ? '1' : '0',
        'require_otp' => isset($_POST['require_otp']) ? '1' : '0'
    ];

    $success_count = 0;
    foreach ($to_update as $key => $val) {
        $val_esc = mysqli_real_escape_string($conn, $val);
        $res = mysqli_query($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('$key', '$val_esc') ON DUPLICATE KEY UPDATE setting_value='$val_esc', updated_at=NOW()");
        if ($res) $success_count++;
    }

    if ($success_count > 0) {
        $auditLogger->logAction($_SESSION['user_id'], 'settings_update', 'system', null, "Updated system-wide configurations.");
        $message = "System settings updated successfully.";
        // Refresh local settings array
        $q = mysqli_query($conn, "SELECT * FROM system_settings");
        while($r = mysqli_fetch_assoc($q)) $settings[$r['setting_key']] = $r['setting_value'];
    } else {
        $error = "Failed to update system settings.";
    }
}

include '../includes/_sidebar.php';
?>

<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">
<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #6366f1; /* Indigo for settings */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #4338ca);
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Analytical Layout ── */
.settings-grid { display:grid; grid-template-columns:2fr 1fr; gap:2.5rem; margin-bottom:2.5rem; }
@media(max-width:1000px) { .settings-grid { grid-template-columns:1fr; } }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; background:var(--surface-2); }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Form Controls ── */
.form-row { display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.6rem; }
@media(max-width:768px){.form-row{grid-template-columns:1fr;}}
.form-group { margin-bottom:1.6rem; }
.form-group label { display:block;font-size:1.1rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em; }
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.15rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }

/* ── Toggle Switch ── */
.switch-group { display:flex; align-items:center; justify-content:space-between; padding:1.5rem; background:var(--surface-2); border-radius:var(--radius-sm); margin-bottom:1rem; border:1px solid var(--border); }
.switch-label { display:flex; flex-direction:column; gap:0.2rem; }
.switch-label strong { font-size:1.2rem; color:var(--text-primary); }
.switch-label span { font-size:0.95rem; color:var(--text-muted); }

.switch { position:relative; display:inline-block; width:52px; height:28px; }
.switch input { opacity:0; width:0; height:0; }
.slider { position:absolute; cursor:pointer; inset:0; background-color:var(--border); transition:.4s; border-radius:34px; }
.slider:before { position:absolute; content:""; height:20px; width:20px; left:4px; bottom:4px; background-color:white; transition:.4s; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.2);}
input:checked + .slider { background-color:var(--primary); }
input:checked + .slider:before { transform:translateX(24px); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); box-shadow:0 8px 24px var(--primary-light); }

.alert { padding:1.2rem 2rem; border-radius:var(--radius-sm); margin-bottom:2rem; display:flex; align-items:center; gap:1rem; font-weight:600; font-size:1.15rem; animation:fadeIn 0.3s ease; }
.alert-success { background:rgba(16,185,129,0.15); color:var(--success); border-left:5px solid var(--success); }
.alert-error { background:rgba(239,68,68,0.15); color:var(--danger); border-left:5px solid var(--danger); }
@keyframes fadeIn { from{opacity:0;transform:translateY(10px);} to{opacity:1;transform:translateY(0);} }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-cog"></i> Global System Settings</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadeIn .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-tools hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-sliders-h"></i></div>
            <div class="staff-hero-info">
                <h2>Platform Governance & Control</h2>
                <p>Configure hospital meta-data, security protocols, and system-wide feature flags.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="settings-grid">
                <div class="left-col">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-hospital-alt" style="color:var(--primary);"></i> Hospital Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Hospital Name</label>
                                <input type="text" name="hospital_name" class="form-control" value="<?= htmlspecialchars($settings['hospital_name'] ?? 'RMU Medical Sickbay') ?>" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Contact Email</label>
                                    <input type="email" name="hospital_email" class="form-control" value="<?= htmlspecialchars($settings['hospital_email'] ?? 'admin@rmu.edu.gh') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Contact Phone</label>
                                    <input type="text" name="hospital_phone" class="form-control" value="<?= htmlspecialchars($settings['hospital_phone'] ?? '+233 123 456 789') ?>" required>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Physical Address</label>
                                <textarea name="hospital_address" class="form-control" rows="3" required><?= htmlspecialchars($settings['hospital_address'] ?? 'Maritime University, Nungua, Accra') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-shield" style="color:var(--primary);"></i> Security & Session Control</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>System Timezone</label>
                                    <select name="system_timezone" class="form-control">
                                        <option value="UTC" <?= ($settings['system_timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                        <option value="Africa/Accra" <?= ($settings['system_timezone'] ?? '') === 'Africa/Accra' ? 'selected' : '' ?>>Africa/Accra (GMT)</option>
                                        <option value="Europe/London" <?= ($settings['system_timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>Europe/London</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Session Timeout (Minutes)</label>
                                    <input type="number" name="session_timeout" class="form-control" value="<?= htmlspecialchars($settings['session_timeout'] ?? '30') ?>" min="5" max="1440">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="right-col">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-toggle-on" style="color:var(--primary);"></i> System Flags</h3>
                        </div>
                        <div class="card-body" style="padding:1.5rem;">
                            <div class="switch-group">
                                <div class="switch-label">
                                    <strong>Maintenance Mode</strong>
                                    <span>Disable public access</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="maintenance_mode" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="switch-group">
                                <div class="switch-label">
                                    <strong>Patient Signup</strong>
                                    <span>Allow new self-registration</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="allow_patient_registration" <?= ($settings['allow_patient_registration'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="switch-group">
                                <div class="switch-label">
                                    <strong>Staff Signup</strong>
                                    <span>Enable staff portal registration</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="allow_staff_registration" <?= ($settings['allow_staff_registration'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="switch-group" style="margin-bottom:0;">
                                <div class="switch-label">
                                    <strong>2FA / OTP Requirement</strong>
                                    <span>Enforce login verification</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="require_otp" <?= ($settings['require_otp'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="border:none; background:transparent; box-shadow:none;">
                        <button type="submit" name="update_settings" class="btn btn-primary" style="width:100%; padding:1.2rem;">
                            <i class="fas fa-save"></i> Save Global Configuration
                        </button>
                        <p style="text-align:center; color:var(--text-muted); font-size:0.9rem; margin-top:1rem;">
                            <i class="fas fa-info-circle"></i> Changes affect all users immediately.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
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