<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- GLOBAL CONFIGURATION & PREFERENCES (Module 14) -->
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

<div style="margin-bottom:2.5rem; display:flex; align-items:center; gap:1.2rem;">
    <div style="width:50px; height:50px; border-radius:12px; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; font-size:1.5rem; box-shadow:0 10px 15px -3px rgba(13,148,136,0.3);">
        <i class="fas fa-sliders"></i>
    </div>
    <div>
        <h2 style="margin:0; font-weight:800; letter-spacing:-0.02em;">System Configuration</h2>
        <div style="color:var(--text-muted); font-size:0.9rem; font-weight:600;">Calibrate your operational interface environment</div>
    </div>
</div>

<div class="charts-grid" style="grid-template-columns: 1fr 1fr; gap:2.5rem; display:grid;">

    <!-- User Preferences -->
    <div class="info-card" style="padding:2.5rem; display:flex; flex-direction:column;">
        <h3 style="margin:0 0 2rem; font-weight:800; display:flex; align-items:center; gap:0.8rem;">
            <i class="fas fa-desktop" style="color:var(--primary);"></i> Interface Calibration
        </h3>
        
        <form onsubmit="savePreferences(event)" style="flex:1; display:flex; flex-direction:column; gap:2rem;">
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.8rem; display:block;">Primary Ingress Node</label>
                <select id="def_view" class="form-select" style="padding:1rem; font-weight:600; border-radius:12px;">
                    <option value="overview" <?= $default_view === 'overview' ? 'selected' : '' ?>>Command Overview</option>
                    <option value="orders" <?= $default_view === 'orders' ? 'selected' : '' ?>>Test Ingress Queue</option>
                    <option value="samples" <?= $default_view === 'samples' ? 'selected' : '' ?>>Biometric Logistics</option>
                    <option value="results" <?= $default_view === 'results' ? 'selected' : '' ?>>Analytical Matrix</option>
                </select>
                <div style="font-size:0.85rem; color:var(--text-muted); margin-top:0.6rem; font-weight:500;">Select the default module to initialize upon system authentication.</div>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:1rem; display:block;">Chromatic Environment</label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem;">
                    <label style="cursor:pointer;">
                        <input type="radio" name="theme" value="light" <?= $theme === 'light' ? 'checked' : '' ?> onchange="applyTheme('light')" style="display:none;">
                        <div class="theme-box <?= $theme==='light'?'active':'' ?>" style="padding:1.2rem; border-radius:15px; border:2px solid var(--border); text-align:center; transition:var(--transition); background:white;">
                            <i class="fas fa-sun" style="font-size:1.5rem; color:#f39c12; margin-bottom:0.6rem;"></i>
                            <div style="font-weight:700; color:#334155;">Light Core</div>
                        </div>
                    </label>
                    <label style="cursor:pointer;">
                        <input type="radio" name="theme" value="dark" <?= $theme === 'dark' ? 'checked' : '' ?> onchange="applyTheme('dark')" style="display:none;">
                        <div class="theme-box <?= $theme==='dark'?'active':'' ?>" style="padding:1.2rem; border-radius:15px; border:2px solid var(--border); text-align:center; transition:var(--transition); background:#0f172a;">
                            <i class="fas fa-moon" style="font-size:1.5rem; color:#6366f1; margin-bottom:0.6rem;"></i>
                            <div style="font-weight:700; color:white;">Dark Void</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:1.2rem; display:block;">Telemetry Echoes (Notifications)</label>
                <div style="display:grid; gap:1rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 1.2rem; background:var(--surface-2); border-radius:12px; border:1px solid var(--border);">
                        <span style="font-size:0.95rem; font-weight:600; color:var(--text-secondary);">Relay critical alerts to SMTP</span>
                        <label class="prof-toggle"><input type="checkbox" id="notif_email" <?= $notif_email ? 'checked' : '' ?>>
                            <span class="prof-toggle-slider"></span>
                        </label>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 1.2rem; background:var(--surface-2); border-radius:12px; border:1px solid var(--border);">
                        <span style="font-size:0.95rem; font-weight:600; color:var(--text-secondary);">Push STAT orders to SMS Gateway</span>
                        <label class="prof-toggle"><input type="checkbox" id="notif_sms" <?= $notif_sms ? 'checked' : '' ?>>
                            <span class="prof-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div style="margin-top:auto; padding-top:2rem; border-top:1px solid var(--border); text-align:right;">
                <button type="submit" class="adm-btn adm-btn-primary" style="padding:0.9rem 2.5rem;"><i class="fas fa-save"></i> Synchronize Preferences</button>
            </div>
        </form>
    </div>

    <!-- Security Settings -->
    <div class="info-card" style="padding:2.5rem; background:linear-gradient(135deg, var(--surface) 0%, var(--surface-2) 100%);">
        <h3 style="margin:0 0 2rem; font-weight:800; display:flex; align-items:center; gap:0.8rem;">
            <i class="fas fa-shield-halved" style="color:var(--primary);"></i> Cryptographic Control
        </h3>
        
        <form onsubmit="changePasswordSettings(event)" style="display:grid; gap:1.8rem;">
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.8rem; display:block;">Active Keyphrase <span style="color:var(--danger)">*</span></label>
                <input type="password" id="set_cur_pass" class="form-control" placeholder="Required for authorization" style="padding:1rem; letter-spacing:0.2em;">
            </div>
            
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.8rem; display:block;">New Entropy String <span style="color:var(--danger)">*</span></label>
                <input type="password" id="set_new_pass" class="form-control" oninput="checkSetPassStrength(this.value)" style="padding:1rem; letter-spacing:0.2em;">
                <div id="set-pass-strength" style="height:6px; border-radius:30px; margin-top:12px; background:var(--surface); overflow:hidden; border:1px solid var(--border);">
                    <div id="set-pass-fill" style="height:100%; width:0; transition:width 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);"></div>
                </div>
                <div id="set-pass-label" style="font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; margin-top:6px; height:1rem;"></div>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.8rem; display:block;">Re-verify New Secret <span style="color:var(--danger)">*</span></label>
                <input type="password" id="set_conf_pass" class="form-control" style="padding:1rem; letter-spacing:0.2em;">
            </div>

            <div style="margin-top:1.5rem; padding:1.5rem; background:rgba(13,148,136,0.03); border:1px solid rgba(13,148,136,0.1); border-radius:15px;">
                <div style="display:flex; align-items:flex-start; gap:1rem;">
                    <i class="fas fa-info-circle" style="color:var(--primary); margin-top:0.2rem;"></i>
                    <div style="font-size:0.9rem; color:var(--text-secondary); line-height:1.5;">
                        Updating your access credentials will terminate all existing telemetry links (sessions) and require re-authentication.
                    </div>
                </div>
            </div>

            <div style="margin-top:1rem; border-top:1px solid var(--border); padding-top:2rem; text-align:right;">
                <button type="submit" class="adm-btn adm-btn-primary" style="width:100%; padding:1rem; justify-content:center;"><i class="fas fa-key"></i> Commit Credential Rotation</button>
            </div>
        </form>
    </div>
</div>

<style>
.theme-box:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); border-color:var(--primary) !important; }
.theme-box.active { border-color:var(--primary) !important; background:rgba(13,148,136,0.05) !important; border-width:3px; }
.theme-box.active div { color:var(--primary) !important; }

.prof-toggle { position:relative; display:inline-block; width:48px; height:26px; }
.prof-toggle input { opacity:0; width:0; height:0; }
.prof-toggle-slider { position:absolute; cursor:pointer; inset:0; background:rgba(0,0,0,0.05); border-radius:26px; border:1px solid var(--border); transition:0.3s; }
.prof-toggle input:checked + .prof-toggle-slider { background:var(--primary); border-color:var(--primary); }
.prof-toggle-slider:before { content:""; position:absolute; width:18px; height:18px; left:3px; bottom:3px; background:white; border-radius:50%; transition:0.3s; box-shadow:0 2px 4px rgba(0,0,0,0.1); }
.prof-toggle input:checked + .prof-toggle-slider:before { transform:translateX(22px); }
</style>

<?php include __DIR__.'/../../includes/active_sessions_panel.php'; ?>
<script>
function checkSetPassStrength(v) {
    let s=0; if(v.length>=8)s++; if(/[A-Z]/.test(v))s++; if(/[0-9]/.test(v))s++; if(/[^A-Za-z0-9]/.test(v))s++;
    const ls=['INSECURE','WEAK','STABLE','ROBUST','ELITE'], cs=['#e74c3c','#e67e22','#3498db','#2ecc71','#00b894'];
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
            Swal.fire({ icon:'success', title:'Preferences Synced', text:'Interface calibration saved successfully.', timer:2000, showConfirmButton:false });
        } else {
            Swal.fire({ icon:'error', title:'Sync Failed', text:res.message });
        }
    }, 'json');
}

function changePasswordSettings(e) {
    e.preventDefault();
    const cur = $('#set_cur_pass').val();
    const n = $('#set_new_pass').val();
    const c = $('#set_conf_pass').val();
    
    if(n !== c) { Swal.fire('Error', 'New entropy strings do not match.', 'error'); return; }
    
    $.post('lab_profile_actions.php', {
        action: 'change_password',
        csrf_token: $('meta[name="csrf-token"]').attr('content'),
        current_password: cur,
        new_password: n,
        confirm_password: c
    }, function(res) {
        if(res.success) {
            Swal.fire('Success', 'Credential rotation successful. Re-authentication required.', 'success').then(() => location.href='logout.php');
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
}
</script>
