<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- GLOBAL CONFIGURATION & PREFERENCES (PREMIUM REWRITE) (Module 14) -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<?php
if (!isset($user_id)) { exit; }
$set_q = mysqli_query($conn, "SELECT * FROM lab_technician_settings WHERE technician_id = $user_id LIMIT 1");
$settings = mysqli_fetch_assoc($set_q);
$theme = $settings['theme_preference'] ?? 'light';
$notif_email = $settings['email_notifications'] ?? 1;
$notif_sms = $settings['sms_notifications'] ?? 0;
$default_view = $settings['default_view'] ?? 'overview';
?>

<div class="tab-content <?= ($active_tab === 'settings') ? 'active' : '' ?>" id="settings">

    <div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.01)); border:1px solid rgba(47,128,237,0.15); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.5rem;">
                <i class="fas fa-sliders-h" style="color:var(--primary); margin-right:.8rem;"></i> System Configuration
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted); margin:0;">Calibrate your operational interface environment and set cryptographic access protocols.</p>
        </div>
    </div>

    <div class="charts-grid" style="grid-template-columns: 1fr 1fr; gap:2.5rem; display:grid;">

        <!-- User Preferences -->
        <div class="adm-card shadow-sm" style="border-radius:16px; display:flex; flex-direction:column;">
            <div class="adm-card-header" style="background:var(--surface-1); padding:2rem; border-bottom:1px solid var(--border);">
                <h3 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.8rem;">
                    <i class="fas fa-desktop" style="color:var(--primary);"></i> Environment Calibration
                </h3>
            </div>
            
            <div class="adm-card-body" style="padding:2.5rem; flex:1; display:flex; flex-direction:column;">
                <form onsubmit="savePreferences(event)" style="flex:1; display:flex; flex-direction:column; gap:2.5rem;">
                    <div class="form-group">
                        <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:0.8rem; display:block; text-transform:uppercase;">Primary Ingress Node</label>
                        <select id="def_view" class="form-control" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                            <option value="overview" <?= $default_view === 'overview' ? 'selected' : '' ?>>Command Overview</option>
                            <option value="orders" <?= $default_view === 'orders' ? 'selected' : '' ?>>Test Ingress Queue</option>
                            <option value="samples" <?= $default_view === 'samples' ? 'selected' : '' ?>>Biometric Logistics</option>
                            <option value="results" <?= $default_view === 'results' ? 'selected' : '' ?>>Analytical Matrix</option>
                        </select>
                        <div style="font-size:0.95rem; color:var(--text-muted); margin-top:0.8rem; font-weight:600;">Select the default module to initialize upon system authentication.</div>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:1.2rem; display:block; text-transform:uppercase;">Chromatic Output Module</label>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                            <label style="cursor:pointer; display:block;">
                                <input type="radio" name="theme" value="light" <?= $theme === 'light' ? 'checked' : '' ?> onchange="applyTheme('light')" style="display:none;">
                                <div class="theme-box <?= $theme==='light'?'active':'' ?>" style="padding:1.5rem; border-radius:15px; border:3px solid var(--border); text-align:center; transition:var(--transition); background:white;">
                                    <i class="fas fa-sun" style="font-size:2rem; color:#f59e0b; margin-bottom:0.8rem;"></i>
                                    <div style="font-weight:800; font-size:1.1rem; color:#334155; text-transform:uppercase;">Standard Issue Mode</div>
                                </div>
                            </label>
                            <label style="cursor:pointer; display:block;">
                                <input type="radio" name="theme" value="dark" <?= $theme === 'dark' ? 'checked' : '' ?> onchange="applyTheme('dark')" style="display:none;">
                                <div class="theme-box <?= $theme==='dark'?'active':'' ?>" style="padding:1.5rem; border-radius:15px; border:3px solid var(--border); text-align:center; transition:var(--transition); background:#0f172a;">
                                    <i class="fas fa-moon" style="font-size:2rem; color:var(--primary); margin-bottom:0.8rem;"></i>
                                    <div style="font-weight:800; font-size:1.1rem; color:white; text-transform:uppercase;">Dark Void Matrix</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:1.5rem; display:block; text-transform:uppercase;">Telemetry Echoes (Push Notifications)</label>
                        <div style="display:grid; gap:1.2rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.5rem; background:var(--primary-light); border-radius:12px; border:2px solid rgba(47,128,237,0.15);">
                                <span style="font-size:1.1rem; font-weight:800; color:var(--text-primary);">Relay critical alerts to SMTP</span>
                                <label class="prof-toggle"><input type="checkbox" id="notif_email" <?= $notif_email ? 'checked' : '' ?>>
                                    <span class="prof-toggle-slider"></span>
                                </label>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.5rem; background:var(--primary-light); border-radius:12px; border:2px solid rgba(47,128,237,0.15);">
                                <span style="font-size:1.1rem; font-weight:800; color:var(--text-primary);">Push STAT orders to SMS Gateway</span>
                                <label class="prof-toggle"><input type="checkbox" id="notif_sms" <?= $notif_sms ? 'checked' : '' ?>>
                                    <span class="prof-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:auto; padding-top:2.5rem; border-top:1px solid var(--border); text-align:right;">
                        <button type="submit" class="adm-btn adm-btn-primary" style="background:#4f46e5; border-radius:10px; font-weight:900; padding:1rem 2rem;"><span class="btn-text"><i class="fas fa-save" style="margin-right:.5rem;"></i> Synchronize Preferences</span></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="adm-card shadow-sm" style="border-radius:16px;">
            <div class="adm-card-header" style="background:var(--surface-1); padding:2rem; border-bottom:1px solid var(--border);">
                <h3 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.8rem;">
                    <i class="fas fa-shield-halved" style="color:#ef4444;"></i> Cryptographic Access Control
                </h3>
            </div>

            <div class="adm-card-body" style="padding:2.5rem; background:linear-gradient(135deg, rgba(239,68,68,0.03), rgba(0,0,0,0));">
                <form onsubmit="changePasswordSettings(event)" style="display:grid; gap:2.5rem;">
                    <div class="form-group">
                        <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:0.8rem; display:block; text-transform:uppercase;">Active Keyphrase (Verification) <span style="color:#ef4444">*</span></label>
                        <input type="password" id="set_cur_pass" class="form-control" placeholder="Required for authorization" style="padding:1.2rem; font-size:1.2rem; letter-spacing:0.3em; font-family:monospace; font-weight:800;">
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:0.8rem; display:block; text-transform:uppercase;">New Entropy String <span style="color:#ef4444">*</span></label>
                        <input type="password" id="set_new_pass" class="form-control" oninput="checkSetPassStrength(this.value)" style="padding:1.2rem; font-size:1.2rem; letter-spacing:0.3em; font-family:monospace; font-weight:800;">
                        <div id="set-pass-strength" style="height:8px; border-radius:30px; margin-top:15px; background:var(--surface-3); overflow:hidden; border:1px solid var(--border);">
                            <div id="set-pass-fill" style="height:100%; width:0; transition:width 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);"></div>
                        </div>
                        <div id="set-pass-label" style="font-size:0.85rem; font-weight:900; text-transform:uppercase; letter-spacing:0.1em; margin-top:8px; height:1rem;"></div>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:0.8rem; display:block; text-transform:uppercase;">Re-verify New Secret <span style="color:#ef4444">*</span></label>
                        <input type="password" id="set_conf_pass" class="form-control" style="padding:1.2rem; font-size:1.2rem; letter-spacing:0.3em; font-family:monospace; font-weight:800;">
                    </div>

                    <div style="margin-top:0.5rem; padding:2rem; background:rgba(239,68,68,0.1); border:2px solid rgba(239,68,68,0.2); border-left:6px solid #ef4444; border-radius:12px;">
                        <div style="display:flex; align-items:flex-start; gap:1.2rem;">
                            <i class="fas fa-exclamation-triangle" style="color:#ef4444; font-size:1.5rem; margin-top:0.2rem;"></i>
                            <div style="font-size:1.05rem; font-weight:600; color:#dc2626; line-height:1.6;">
                                Rotating your root credentials will immediately terminate all active telemetry nodes globally. Re-authentication via the main portal will be required.
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:1rem; border-top:1px solid var(--border); padding-top:2.5rem; text-align:right;">
                        <button type="submit" class="adm-btn adm-btn-primary" style="width:100%; padding:1.2rem; justify-content:center; background:#ef4444; border-radius:12px; font-weight:900;"><span class="btn-text"><i class="fas fa-fingerprint" style="margin-right:.8rem;"></i> Authorize Cryptographic Re-Key</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.theme-box:hover { transform:translateY(-5px); box-shadow:0 15px 30px rgba(0,0,0,0.1); border-color:#4f46e5 !important; }
.theme-box.active { border-color:#4f46e5 !important; background:rgba(79,70,229,0.05) !important; border-width:3px; }
.theme-box.active div { color:#4f46e5 !important; }

.prof-toggle { position:relative; display:inline-block; width:56px; height:30px; }
.prof-toggle input { opacity:0; width:0; height:0; }
.prof-toggle-slider { position:absolute; cursor:pointer; inset:0; background:var(--surface-3); border-radius:30px; border:1px solid var(--border); transition:0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.prof-toggle input:checked + .prof-toggle-slider { background:#4f46e5; border-color:#4f46e5; }
.prof-toggle-slider:before { content:""; position:absolute; width:22px; height:22px; left:4px; bottom:3px; background:white; border-radius:50%; transition:0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow:0 2px 5px rgba(0,0,0,0.2); }
.prof-toggle input:checked + .prof-toggle-slider:before { transform:translateX(24px); }
</style>

<?php include __DIR__.'/../../includes/active_sessions_panel.php'; ?>
<script>
function checkSetPassStrength(v) {
    let s=0; if(v.length>=8)s++; if(/[A-Z]/.test(v))s++; if(/[0-9]/.test(v))s++; if(/[^A-Za-z0-9]/.test(v))s++;
    const ls=['CRITICAL FLAW','VULNERABLE','SECURE','FORTIFIED','MILITARY GRADE'], cs=['#ef4444','#f59e0b','#3b82f6','#10b981','#8b5cf6'];
    $('#set-pass-fill').css({width:(s*25)+'%', background:cs[s]});
    $('#set-pass-label').css('color',cs[s]).text(ls[s]);
}

function savePreferences(e) {
    e.preventDefault();
    const data = {
        action: 'save_technician_settings',
        csrf_token: $('meta[name="csrf-token"]').attr('content'),
        default_view: $('#def_view').val(),
        email_notif: $('#notif_email').is(':checked') ? 1 : 0,
        sms_notif: $('#notif_sms').is(':checked') ? 1 : 0
    };
    
    $.post('lab_actions.php', data, function(res) {
        if(res.success) {
            alert("SUCCESS: Integration paths initialized.");
        } else {
            alert("ERROR: Module alignment failed.");
        }
    }, 'json');
}

function changePasswordSettings(e) {
    e.preventDefault();
    const cur = $('#set_cur_pass').val();
    const n = $('#set_new_pass').val();
    const c = $('#set_conf_pass').val();
    
    if(n !== c) { alert("ERROR: Entropy signature mismatch."); return; }
    
    $.post('lab_profile_actions.php', {
        action: 'change_password',
        csrf_token: $('meta[name="csrf-token"]').attr('content'),
        current_password: cur,
        new_password: n,
        confirm_password: c
    }, function(res) {
        if(res.success) {
            alert("SUCCESS: Keys rotated. Authorization terminated.").then(() => location.href='logout.php');
        } else {
            alert("ERROR: Re-key authorization failed.");
        }
    }, 'json');
}
</script>
