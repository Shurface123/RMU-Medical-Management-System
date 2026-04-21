<?php
/**
 * tab_settings.php — Module 15: System Settings (Modernized)
 */
$settings = dbRow($conn, "SELECT * FROM staff_settings WHERE staff_id=? LIMIT 1", "i", [$staff_id]);
$theme     = $settings['theme'] ?? 'light';
$lang      = $settings['language'] ?? 'en';
$notif_prefs = is_string($settings['notification_preferences'] ?? '')
    ? json_decode($settings['notification_preferences'] ?? '{}', true)
    : [];
?>
<div id="sec-settings" class="dash-section">
    
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-sliders-h" style="color:var(--role-accent);"></i> Core Configurations</h2>
            <p style="font-size:1.3rem;color:var(--text-muted);margin:0.5rem 0 0;">Personalize your workstation environment</p>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1.2fr; gap:3rem; margin-bottom:3rem;">
        
        <!-- UI & Interface Section -->
        <div style="display:flex; flex-direction:column; gap:2.5rem;">
            
            <!-- Appearance Card -->
            <div class="card" style="padding:2.5rem;">
                <div style="display:flex; align-items:center; gap:1.2rem; margin-bottom:2rem;">
                    <div style="width:40px; height:40px; border-radius:12px; background:rgba(47,128,237,0.1); color:var(--role-accent); display:flex; align-items:center; justify-content:center; font-size:1.6rem;">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3 style="font-size:1.7rem; font-weight:700; margin:0;">Visual Identity</h3>
                </div>
                
                <div style="margin-bottom:2.5rem;">
                    <label style="font-weight:700; display:block; margin-bottom:1.5rem; font-size:1.2rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted);">Active Theme</label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                        <label class="theme-option <?= $theme==='light'?'active':'' ?>" onclick="applyTheme('light')">
                            <input type="radio" name="theme_sel" value="light" <?= $theme==='light'?'checked':'' ?> style="display:none;">
                            <div class="theme-preview light">
                                <div class="dot d1"></div><div class="dot d2"></div><div class="dot d3"></div>
                            </div>
                            <span style="font-weight:700;">Light Mode</span>
                        </label>
                        <label class="theme-option <?= $theme==='dark'?'active':'' ?>" onclick="applyTheme('dark')">
                            <input type="radio" name="theme_sel" value="dark" <?= $theme==='dark'?'checked':'' ?> style="display:none;">
                            <div class="theme-preview dark">
                                <div class="dot d1"></div><div class="dot d2"></div><div class="dot d3"></div>
                            </div>
                            <span style="font-weight:700;">Nova Dark</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label style="font-weight:700; display:block; margin-bottom:1rem; font-size:1.2rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted);">Regional Language</label>
                    <select id="langSel" class="form-control" style="padding:1.2rem; border-radius:10px; font-weight:600;" onchange="saveLang(this.value)">
                        <option value="en" <?= $lang==='en'?'selected':'' ?>>English (Global)</option>
                        <option value="fr" <?= $lang==='fr'?'selected':'' ?>>Français (Afrique)</option>
                        <option value="es" <?= $lang==='es'?'selected':'' ?>>Español</option>
                    </select>
                </div>
            </div>

            <!-- Security Card -->
            <div class="card" style="padding:2.5rem;">
                <div style="display:flex; align-items:center; gap:1.2rem; margin-bottom:2rem;">
                    <div style="width:40px; height:40px; border-radius:12px; background:rgba(39,174,96,0.1); color:#27AE60; display:flex; align-items:center; justify-content:center; font-size:1.6rem;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 style="font-size:1.7rem; font-weight:700; margin:0;">Security & Access</h3>
                </div>

                <div class="setting-toggle-row">
                    <div>
                        <div style="font-weight:800; font-size:1.4rem;">Multi-Factor Auth (MFA)</div>
                        <p style="font-size:1.15rem; color:var(--text-muted); margin-top:.4rem;">Secure logins with primary email verification</p>
                    </div>
                    <label class="st-switch">
                        <input type="checkbox" id="twoFaToggle" <?= !empty($staff['two_fa_enabled'])?'checked':'' ?> onchange="toggle2FA(this)">
                        <span class="st-slider"></span>
                    </label>
                </div>
                
                <button class="btn btn-outline btn-wide" style="margin-top:1rem;" onclick="openModal('mdlChangePass')">
                   <span class="btn-text"><i class="fas fa-key"></i> Rotate Password</span>
                </button>
            </div>
        </div>

        <!-- Notification Preferences -->
        <div class="card" style="padding:2.5rem;">
            <div style="display:flex; align-items:center; gap:1.2rem; margin-bottom:2.5rem;">
                <div style="width:40px; height:40px; border-radius:12px; background:rgba(231,76,60,0.1); color:#E74C3C; display:flex; align-items:center; justify-content:center; font-size:1.6rem;">
                    <i class="fas fa-bell"></i>
                </div>
                <h3 style="font-size:1.7rem; font-weight:700; margin:0;">Notification Routing</h3>
            </div>

            <div style="display:flex; flex-direction:column; gap:.5rem;">
                <?php
                $notif_toggles = [
                    ['key'=>'task_assigned',   'ico'=>'fa-tasks',     'label'=>'Duty Assignments',   'desc'=>'Real-time alerts for new job tickets'],
                    ['key'=>'task_due',        'ico'=>'fa-clock',     'label'=>'Deadline Alerts',    'desc'=>'Warning 1 hour before task expiration'],
                    ['key'=>'shift_reminder',  'ico'=>'fa-calendar',  'label'=>'Shift Countdown',    'desc'=>'30-minute reminder before shift duty'],
                    ['key'=>'leave_status',    'ico'=>'fa-umbrella',  'label'=>'HR Outcomes',        'desc'=>'Status updates for leave requests'],
                    ['key'=>'new_message',     'ico'=>'fa-envelope',  'label'=>'Private Comms',      'desc'=>'Instant notice for internal messages'],
                    ['key'=>'system_updates',  'ico'=>'fa-terminal',  'label'=>'Kernel Logs',        'desc'=>'Critical system-wide infrastructure notes'],
                ];
                foreach($notif_toggles as $nt):
                    $is_on = !isset($notif_prefs[$nt['key']]) || (bool)$notif_prefs[$nt['key']];
                ?>
                <div class="setting-toggle-row premium">
                    <div style="display:flex; align-items:center; gap:1.5rem;">
                        <i class="fas <?= $nt['ico'] ?>" style="width:20px; text-align:center; opacity:.4;"></i>
                        <div>
                            <div style="font-weight:700; font-size:1.3rem;"><?= $nt['label'] ?></div>
                            <p style="font-size:1.1rem; color:var(--text-muted); margin-top:.2rem;"><?= $nt['desc'] ?></p>
                        </div>
                    </div>
                    <label class="st-switch">
                        <input type="checkbox" class="notif-toggle" data-key="<?= e($nt['key']) ?>" <?= $is_on?'checked':'' ?> onchange="saveNotifPref('<?= e($nt['key']) ?>', this.checked)">
                        <span class="st-slider"></span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Danger Zone Card -->
    <div class="card" style="border:1.5px solid rgba(231,76,60,0.3); overflow:hidden;">
        <div style="background:rgba(231,76,60,0.05); padding:1.5rem 2.5rem; border-bottom:1px solid rgba(231,76,60,0.1); display:flex; align-items:center; gap:1.2rem;">
            <i class="fas fa-biohazard" style="color:#E74C3C; font-size:1.8rem;"></i>
            <h3 style="margin:0; font-size:1.5rem; color:#E74C3C; font-weight:800; text-transform:uppercase; letter-spacing:0.1em;">Critical Actions Zone</h3>
        </div>
        <div class="card-body" style="padding:2.5rem;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:4rem;">
                <div>
                   <h4 style="font-weight:800; font-size:1.4rem; margin-bottom:.5rem;">Session Management</h4>
                   <p style="color:var(--text-muted); font-size:1.2rem; margin-bottom:1.5rem;">Forcibly terminate all active sessions originating from other devices. Use this if you suspect unauthorized access.</p>
                   <button class="btn btn-danger" style="padding:.8rem 1.8rem;" onclick="logoutAllSessions();">
                      <span class="btn-text"><i class="fas fa-power-off"></i> Purge Other Sessions</span>
                   </button>
                </div>
                <div style="border-left:1px solid var(--border); padding-left:4rem;">
                   <h4 style="font-weight:800; font-size:1.4rem; margin-bottom:.5rem;">Data Hygiene</h4>
                   <p style="color:var(--text-muted); font-size:1.2rem; margin-bottom:1.5rem;">Permanently remove all read notifications and alerts older than 30 business days to optimize database performance.</p>
                   <button class="btn btn-outline" style="color:#E74C3C; border-color:#E74C3C; padding:.8rem 1.8rem;" onclick="clearNotifs();">
                      <span class="btn-text"><i class="fas fa-broom"></i> Deep Clean Logs</span>
                   </button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/active_sessions_panel.php'; ?>
</div>

<script>
async function saveLang(val) {
    const res = await doAction({action: 'save_settings', language: val}, 'Global language preference updated.');
}

async function saveNotifPref(key, val) {
    await doAction({action: 'save_notification_pref', pref_key: key, pref_val: val ? 1 : 0});
}

async function clearNotifs() {
    if (!confirmAction('Initiate deep cleaning of historical logs? (Greater than 30 days)')) return;
    await doAction({action: 'clear_old_notifications'}, 'Archive cleanup sequence complete.');
}

async function toggle2FA(cb) {
    const enable = cb.checked ? 1 : 0;
    const res = await doAction({action: 'toggle_2fa', enable: enable}, 'Multi-factor authentication status updated.');
    if (!res || !res.success) cb.checked = !cb.checked;
}
</script>

<style>
/* ── Theme Selection ── */
.theme-option { cursor: pointer; border: 2px solid var(--border); border-radius: 16px; padding: 1.2rem; text-align: center; transition: .2s; background: var(--surface-2); }
.theme-option:hover { border-color: var(--role-accent); background: rgba(47,128,237,0.03); }
.theme-option.active { border-color: var(--role-accent); background: rgba(47,128,237,0.08); }
.theme-preview { height: 60px; border-radius: 10px; margin-bottom: .8rem; position: relative; border: 1px solid rgba(0,0,0,0.05); }
.theme-preview.light { background: #F4F7FE; }
.theme-preview.dark { background: #0F1628; }
.theme-preview .dot { position: absolute; width: 10px; height: 10px; border-radius: 50%; }
.theme-preview.light .dot { background: #2F80ED; }
.theme-preview.dark .dot { background: #2F80ED; }
.theme-preview .d1 { top: 12px; left: 12px; }
.theme-preview .d2 { top: 12px; left: 28px; width: 25px; border-radius: 4px; }
.theme-preview .d3 { bottom: 12px; left: 12px; width: 40px; border-radius: 4px; }

/* ── Toggle Switches ── */
.setting-toggle-row { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 0; }
.setting-toggle-row.premium { padding: 1.2rem 1rem; border-radius: 12px; }
.setting-toggle-row.premium:hover { background: rgba(255,255,255,0.03); }

.st-switch { position: relative; display: inline-block; width: 44px; height: 22px; }
.st-switch input { opacity: 0; width: 0; height: 0; }
.st-slider { position: absolute; cursor: pointer; inset: 0; background-color: var(--border); border-radius: 34px; transition: .3s; }
.st-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: #fff; border-radius: 50%; transition: .3s; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
input:checked + .st-slider { background-color: var(--role-accent); }
input:checked + .st-slider:before { transform: translateX(22px); }

.card { border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
</style>

