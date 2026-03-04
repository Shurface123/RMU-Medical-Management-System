<!-- ═══════════════════════════════════════════════════════════
     MODULE 14: SETTINGS — tab_settings.php
     ═══════════════════════════════════════════════════════════ -->
<?php
$nurse_settings = dbRow($conn,"SELECT * FROM nurse_settings WHERE nurse_id=?","i",[$nurse_pk]);
$notif_prefs_s = json_decode($nurse_settings['notification_preferences']??'{}',true) ?: [];
$display_prefs = json_decode($nurse_settings['display_preferences']??'{}',true) ?: [];
?>
<div id="sec-settings" class="dash-section">
  <div class="sec-header"><h2><i class="fas fa-cog"></i> Settings</h2></div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

    <!-- ═══ Display Settings ═══ -->
    <div class="info-card">
      <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;"><i class="fas fa-palette" style="color:var(--role-accent);"></i> Display Settings</h3>
      <div class="form-group"><label>Theme</label>
        <select id="st_theme" class="form-control" onchange="applyTheme(this.value)">
          <option value="light" <?=($nurse_settings['theme']??'light')==='light'?'selected':''?>>☀️ Light Theme</option>
          <option value="dark" <?=($nurse_settings['theme']??'light')==='dark'?'selected':''?>>🌙 Dark Theme</option>
        </select>
      </div>
      <div class="form-group"><label>Language</label>
        <select id="st_language" class="form-control">
          <option value="en" <?=($nurse_settings['language']??'en')==='en'?'selected':''?>>English</option>
          <option value="fr" <?=($nurse_settings['language']??'en')==='fr'?'selected':''?>>French</option>
          <option value="ar" <?=($nurse_settings['language']??'en')==='ar'?'selected':''?>>Arabic</option>
        </select>
      </div>
      <div class="form-group"><label>Timezone</label>
        <select id="st_timezone" class="form-control">
          <option value="Africa/Accra" <?=($nurse_settings['timezone']??'Africa/Accra')==='Africa/Accra'?'selected':''?>>Africa/Accra (GMT)</option>
          <option value="Africa/Lagos" <?=($nurse_settings['timezone']??'')==='Africa/Lagos'?'selected':''?>>Africa/Lagos (WAT+1)</option>
          <option value="Europe/London" <?=($nurse_settings['timezone']??'')==='Europe/London'?'selected':''?>>Europe/London (GMT)</option>
          <option value="America/New_York" <?=($nurse_settings['timezone']??'')==='America/New_York'?'selected':''?>>America/New York (EST)</option>
        </select>
      </div>
      <div class="form-group"><label>Font Size</label>
        <select id="st_fontsize" class="form-control">
          <option value="small" <?=($display_prefs['font_size']??'medium')==='small'?'selected':''?>>Small</option>
          <option value="medium" <?=($display_prefs['font_size']??'medium')==='medium'?'selected':''?>>Medium (Default)</option>
          <option value="large" <?=($display_prefs['font_size']??'medium')==='large'?'selected':''?>>Large</option>
        </select>
      </div>
      <div class="form-group"><label>Dashboard Density</label>
        <select id="st_density" class="form-control">
          <option value="comfortable" <?=($display_prefs['density']??'comfortable')==='comfortable'?'selected':''?>>Comfortable</option>
          <option value="compact" <?=($display_prefs['density']??'comfortable')==='compact'?'selected':''?>>Compact</option>
        </select>
      </div>
      <button class="btn btn-primary" onclick="saveDisplaySettings()" style="width:100%;"><i class="fas fa-save"></i> Save Display Settings</button>
    </div>

    <!-- ═══ Alert Settings ═══ -->
    <div class="info-card">
      <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;"><i class="fas fa-bell" style="color:var(--warning);"></i> Alert & Notification Settings</h3>
      <div class="form-group"><label>Vital Alert Threshold (hours since last vitals)</label>
        <input id="st_vital_threshold" type="number" class="form-control" value="<?=e($nurse_settings['vital_alert_threshold']??'4')?>" placeholder="4">
      </div>
      <div class="form-group"><label>Medication Reminder (minutes before scheduled)</label>
        <input id="st_med_reminder" type="number" class="form-control" value="<?=e($nurse_settings['med_reminder_minutes']??'15')?>" placeholder="15">
      </div>
      <div class="form-group"><label>Auto-Refresh Dashboard (seconds, 0=disabled)</label>
        <input id="st_auto_refresh" type="number" class="form-control" value="<?=e($nurse_settings['auto_refresh_interval']??'60')?>" placeholder="60">
      </div>
      <label style="display:flex;align-items:center;gap:.8rem;margin:.8rem 0;cursor:pointer;font-size:1.2rem;">
        <input type="checkbox" id="st_sound_alerts" <?=($nurse_settings['sound_alerts']??1)?'checked':''?>> Enable Sound Alerts
      </label>
      <label style="display:flex;align-items:center;gap:.8rem;margin:.8rem 0;cursor:pointer;font-size:1.2rem;">
        <input type="checkbox" id="st_desktop_notifs" <?=($nurse_settings['desktop_notifications']??0)?'checked':''?>> Enable Desktop Notifications
      </label>
      <label style="display:flex;align-items:center;gap:.8rem;margin:.8rem 0;cursor:pointer;font-size:1.2rem;">
        <input type="checkbox" id="st_email_notifs" <?=($nurse_settings['email_notifications']??0)?'checked':''?>> Enable Email Notifications
      </label>
      <button class="btn btn-primary" onclick="saveAlertSettings()" style="width:100%;"><i class="fas fa-save"></i> Save Alert Settings</button>
    </div>

    <!-- ═══ Shift Preferences ═══ -->
    <div class="info-card">
      <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;"><i class="fas fa-clock" style="color:var(--primary);"></i> Shift Preferences</h3>
      <div class="form-group"><label>Preferred Shift</label>
        <select id="st_pref_shift" class="form-control">
          <option value="">No Preference</option>
          <option value="Morning" <?=($nurse_settings['preferred_shift']??'')==='Morning'?'selected':''?>>Morning (6:00 AM - 2:00 PM)</option>
          <option value="Afternoon" <?=($nurse_settings['preferred_shift']??'')==='Afternoon'?'selected':''?>>Afternoon (2:00 PM - 10:00 PM)</option>
          <option value="Night" <?=($nurse_settings['preferred_shift']??'')==='Night'?'selected':''?>>Night (10:00 PM - 6:00 AM)</option>
        </select>
      </div>
      <div class="form-group"><label>Preferred Ward</label>
        <input id="st_pref_ward" class="form-control" value="<?=e($nurse_settings['preferred_ward']??'')?>" placeholder="e.g. Ward A">
      </div>
      <label style="display:flex;align-items:center;gap:.8rem;margin:.8rem 0;cursor:pointer;font-size:1.2rem;">
        <input type="checkbox" id="st_shift_swap" <?=($nurse_settings['allow_shift_swap']??1)?'checked':''?>> Allow Shift Swap Requests
      </label>
      <label style="display:flex;align-items:center;gap:.8rem;margin:.8rem 0;cursor:pointer;font-size:1.2rem;">
        <input type="checkbox" id="st_overtime" <?=($nurse_settings['overtime_available']??0)?'checked':''?>> Available for Overtime
      </label>
      <button class="btn btn-primary" onclick="saveShiftPrefs()" style="width:100%;"><i class="fas fa-save"></i> Save Shift Preferences</button>
    </div>

    <!-- ═══ Data & Privacy ═══ -->
    <div class="info-card">
      <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;"><i class="fas fa-database" style="color:var(--danger);"></i> Data & Privacy</h3>
      <button class="btn btn-outline" onclick="exportMyData()" style="width:100%;margin-bottom:.8rem;"><i class="fas fa-download"></i> Export My Data</button>
      <button class="btn btn-outline" onclick="downloadActivityLog()" style="width:100%;margin-bottom:.8rem;"><i class="fas fa-file-export"></i> Download Activity Log</button>
      <div style="border-top:1px solid var(--border);padding-top:1rem;margin-top:.5rem;">
        <h4 style="color:var(--danger);margin-bottom:.5rem;">Danger Zone</h4>
        <button class="btn btn-danger btn-outline" style="width:100%;" onclick="requestAccountDeletion()"><i class="fas fa-trash-alt"></i> Request Account Deletion</button>
        <p style="font-size:1rem;color:var(--text-muted);margin-top:.5rem;">This will submit a request to the administrator to delete your account and all associated data.</p>
      </div>
    </div>
  </div>
</div>

<script>
async function saveDisplaySettings(){
  const r=await nurseAction({action:'save_settings',setting_type:'display',
    theme:document.getElementById('st_theme').value,
    language:document.getElementById('st_language').value,
    timezone:document.getElementById('st_timezone').value,
    font_size:document.getElementById('st_fontsize').value,
    density:document.getElementById('st_density').value});
  showToast(r.message||'Saved',r.success?'success':'error');
}

async function saveAlertSettings(){
  const r=await nurseAction({action:'save_settings',setting_type:'alerts',
    vital_threshold:document.getElementById('st_vital_threshold').value,
    med_reminder:document.getElementById('st_med_reminder').value,
    auto_refresh:document.getElementById('st_auto_refresh').value,
    sound_alerts:document.getElementById('st_sound_alerts').checked?1:0,
    desktop_notifs:document.getElementById('st_desktop_notifs').checked?1:0,
    email_notifs:document.getElementById('st_email_notifs').checked?1:0});
  showToast(r.message||'Saved',r.success?'success':'error');
}

async function saveShiftPrefs(){
  const r=await nurseAction({action:'save_settings',setting_type:'shift',
    preferred_shift:document.getElementById('st_pref_shift').value,
    preferred_ward:document.getElementById('st_pref_ward').value,
    allow_swap:document.getElementById('st_shift_swap').checked?1:0,
    overtime:document.getElementById('st_overtime').checked?1:0});
  showToast(r.message||'Saved',r.success?'success':'error');
}

async function exportMyData(){
  showToast('Generating data export...','info');
  const r=await nurseAction({action:'export_my_data'});
  if(r.success&&r.download_url){const a=document.createElement('a');a.href=r.download_url;a.download='';a.click();showToast('Download started','success');}
  else showToast(r.message||'Export failed','error');
}

async function downloadActivityLog(){
  showToast('Downloading activity log...','info');
  const r=await nurseAction({action:'download_activity_log'});
  if(r.success&&r.data){
    const blob=new Blob([r.data],{type:'text/csv'});const url=URL.createObjectURL(blob);
    const a=document.createElement('a');a.href=url;a.download='nurse_activity_log.csv';a.click();
    showToast('Downloaded','success');
  } else showToast(r.message||'Failed','error');
}

function requestAccountDeletion(){
  if(!confirmAction('⚠️ Are you sure you want to request account deletion? This action cannot be undone. An administrator will review your request.')) return;
  nurseAction({action:'request_account_deletion'}).then(r=>showToast(r.message||'Request submitted',r.success?'success':'error'));
}
</script>
