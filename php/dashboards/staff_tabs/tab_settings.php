<?php
/**
 * tab_settings.php — Module 15: System Settings
 */
$settings = dbRow($conn,"SELECT * FROM staff_settings WHERE staff_id=? LIMIT 1","i",[$staff_id]);
$theme     = $settings['theme'] ?? 'light';
$lang      = $settings['language'] ?? 'en';
$notif_prefs = is_string($settings['notification_preferences']??'')
    ? json_decode($settings['notification_preferences']??'{}', true)
    : [];
?>
<div id="sec-settings" class="dash-section">
    <h2 style="font-size:2.2rem;font-weight:700;margin-bottom:2.5rem;"><i class="fas fa-gear" style="color:var(--role-accent);"></i> Settings</h2>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">

        <!-- Appearance -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-palette"></i> Appearance</h3></div>
            <div class="card-body">
                <div style="margin-bottom:2rem;">
                    <p style="font-weight:600;font-size:1.4rem;margin-bottom:1rem;">Theme</p>
                    <div style="display:flex;gap:1.2rem;">
                        <label style="cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:.6rem;">
                            <input type="radio" name="theme_sel" value="light" <?=$theme==='light'?'checked':''?> onclick="applyTheme('light')" style="accent-color:var(--role-accent);">
                            <div style="width:80px;height:55px;border-radius:10px;background:linear-gradient(135deg,#f0f4ff 0%,#e8f0ff 100%);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:2rem;">☀️</div>
                            <span style="font-size:1.2rem;">Light</span>
                        </label>
                        <label style="cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:.6rem;">
                            <input type="radio" name="theme_sel" value="dark" <?=$theme==='dark'?'checked':''?> onclick="applyTheme('dark')" style="accent-color:var(--role-accent);">
                            <div style="width:80px;height:55px;border-radius:10px;background:linear-gradient(135deg,#0F1628 0%,#1e2a45 100%);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:2rem;">🌙</div>
                            <span style="font-size:1.2rem;">Dark</span>
                        </label>
                    </div>
                </div>
                <div>
                    <p style="font-weight:600;font-size:1.4rem;margin-bottom:1rem;">Language</p>
                    <select id="langSel" class="form-control" style="max-width:250px;" onchange="saveLang(this.value)">
                        <option value="en" <?=$lang==='en'?'selected':''?>>English</option>
                        <option value="fr" <?=$lang==='fr'?'selected':''?>>Français</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Security & 2FA -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-shield-halved"></i> Security & 2FA</h3></div>
            <div class="card-body">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 0;">
                    <div>
                        <p style="font-weight:600;font-size:1.4rem;margin:0;">Two-Factor Authentication (2FA)</p>
                        <p style="font-size:1.1rem;color:var(--text-muted);margin:.3rem 0 0;">Requires an email OTP code at login</p>
                    </div>
                    <label style="position:relative;display:inline-block;width:48px;height:26px;flex-shrink:0;">
                        <input type="checkbox" id="twoFaToggle" <?=!empty($staff['two_fa_enabled'])?'checked':''?> style="opacity:0;width:0;height:0;" onchange="toggle2FA(this)">
                        <span style="position:absolute;cursor:pointer;inset:0;background:<?=!empty($staff['two_fa_enabled'])?'var(--success)':'var(--border)'?>;border-radius:24px;transition:.3s;">
                            <span style="position:absolute;content:'';height:20px;width:20px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;transform:<?=!empty($staff['two_fa_enabled'])?'translateX(22px)':'translateX(0)'?>"></span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Notification Preferences -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-bell"></i> Notification Preferences</h3></div>
            <div class="card-body">
                <?php
                $notif_toggles = [
                    ['key'=>'task_assigned',   'label'=>'Task Assigned',       'desc'=>'When a new task is assigned to me'],
                    ['key'=>'task_due',        'label'=>'Task Due Reminders',  'desc'=>'1 hour before task deadline'],
                    ['key'=>'shift_reminder',  'label'=>'Shift Reminders',     'desc'=>'30 minutes before shift starts'],
                    ['key'=>'leave_status',    'label'=>'Leave Request Update', 'desc'=>'When leave request is approved or rejected'],
                    ['key'=>'new_message',     'label'=>'New Messages',         'desc'=>'When I receive a new internal message'],
                    ['key'=>'system_updates',  'label'=>'System Alerts',        'desc'=>'Important system notifications'],
                ];
                foreach($notif_toggles as $nt):
                    $is_on = !isset($notif_prefs[$nt['key']]) || (bool)$notif_prefs[$nt['key']];
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 0;border-bottom:1px solid var(--border);">
                    <div>
                        <p style="font-weight:600;font-size:1.3rem;margin:0;"><?=e($nt['label'])?></p>
                        <p style="font-size:1.1rem;color:var(--text-muted);margin:.2rem 0 0;"><?=e($nt['desc'])?></p>
                    </div>
                    <label style="position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0;">
                        <input type="checkbox" class="notif-toggle" data-key="<?=e($nt['key'])?>" <?=$is_on?'checked':''?> style="opacity:0;width:0;height:0;" onchange="saveNotifPref('<?=e($nt['key'])?>',this.checked)">
                        <span style="position:absolute;cursor:pointer;inset:0;background:<?=$is_on?'var(--role-accent)':'var(--border)'?>;border-radius:24px;transition:.3s;" onclick="this.previousElementSibling.click();this.style.background=this.previousElementSibling.checked?'var(--role-accent)':'var(--border)'">
                            <span style="position:absolute;content:'';height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;transform:<?=$is_on?'translateX(20px)':'translateX(0)'?>"></span>
                        </span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="card" style="border:1.5px solid var(--danger-light);">
        <div class="card-header" style="background:var(--danger-light);">
            <h3 style="color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:2rem;">
                <div>
                    <p style="font-weight:700;font-size:1.5rem;margin:0;">Log Out of All Sessions</p>
                    <p style="font-size:1.2rem;color:var(--text-muted);margin:.3rem 0 0;">This will sign you out from all devices except the current one.</p>
                </div>
                <button class="btn btn-danger" onclick="logoutAllSessions()"><i class="fas fa-sign-out-alt"></i> Terminate All Sessions</button>
            </div>
            <hr style="border:none;border-top:1px solid var(--danger-light);margin:1.5rem 0;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:2rem;">
                <div>
                    <p style="font-weight:700;font-size:1.5rem;margin:0;">Clear Activity Log</p>
                    <p style="font-size:1.2rem;color:var(--text-muted);margin:.3rem 0 0;">Remove read notifications older than 30 days from your notification feed.</p>
                </div>
                <button class="btn btn-danger" onclick="clearNotifs()"><i class="fas fa-trash"></i> Clear Old Notifications</button>
            </div>
        </div>
    </div>
</div>

<script>
async function saveLang(val){
    await doAction({action:'save_settings', language:val},'Language preference saved!');
}
async function saveNotifPref(key, val){
    await doAction({action:'save_notification_pref', pref_key:key, pref_val:val?1:0});
}
async function clearNotifs(){
    if(!confirmAction('Clear read notifications older than 30 days?')) return;
    const res = await doAction({action:'clear_old_notifications'},'Old notifications cleared.');
}
async function toggle2FA(cb){
    const enable = cb.checked ? 1 : 0;
    const res = await doAction({action:'toggle_2fa', enable:enable});
    if(res.success){
        toast('2FA ' + (enable ? 'enabled':'disabled'));
    } else {
        cb.checked = !cb.checked;
    }
}
</script>
