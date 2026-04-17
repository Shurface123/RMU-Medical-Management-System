<!-- ════════════════════════════════════════════════════════════
     SETTINGS TAB
     ════════════════════════════════════════════════════════════ -->
<?php
  $pharm_settings=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM pharmacy_settings WHERE pharmacist_id=$pharm_pk LIMIT 1"));
  if(!$pharm_settings) $pharm_settings=['notif_new_prescription'=>1,'notif_low_stock'=>1,'notif_expiring_meds'=>1,'notif_purchase_orders'=>1,'notif_refill_requests'=>1,'notif_system_alerts'=>1,'preferred_channel'=>'dashboard','theme_preference'=>'light','language'=>'English'];
?>
<div id="sec-settings" class="dash-section <?=($active_tab==='settings')?'active':''?>">

  <div class="sec-header">
    <div style="display:flex;align-items:center;gap:1.5rem;">
        <div style="width:50px;height:50px;border-radius:15px;background:var(--role-accent-light);color:var(--role-accent);display:flex;align-items:center;justify-content:center;font-size:1.8rem;">
            <i class="fas fa-gear"></i>
        </div>
        <div>
            <h2 style="margin:0;font-size:2rem;font-weight:700;">Settings & Preferences</h2>
            <p style="margin:.3rem 0 0;color:var(--text-muted);font-size:1.1rem;">Manage your account and app settings</p>
        </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(400px, 1fr));gap:2rem;">
    <!-- Notification Preferences -->
    <div class="adm-card" style="padding:2rem;box-shadow: 0 8px 30px rgba(0,0,0,0.04); border: 1px solid var(--border);border-radius:16px;">
      <h3 style="font-size:1.5rem;font-weight:700;margin:0 0 1.5rem;display:flex;align-items:center;gap:.8rem;">
          <div style="background:var(--role-accent-light);color:var(--role-accent);width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="fas fa-bell"></i></div>
          Notification Preferences
      </h3>
      <form id="formNotifSettings" onsubmit="saveSettings(event)">
        <div style="display:flex;flex-direction:column;gap:1rem;">
            <?php foreach([
              ['notif_new_prescription','New Prescriptions','Get notified when a doctor writes a new prescription','fa-prescription'],
              ['notif_low_stock','Low Stock Alerts','Alert when medicine stock falls below reorder level','fa-arrow-trend-down'],
              ['notif_expiring_meds','Expiring Medicines','Alert for medicines expiring within 30 days','fa-calendar-xmark'],
              ['notif_purchase_orders','Purchase Orders','Updates on purchase order status changes','fa-file-invoice'],
              ['notif_refill_requests','Refill Requests','Patient refill request notifications','fa-rotate-right'],
              ['notif_system_alerts','System Alerts','General system notifications and updates','fa-server'],
            ] as [$key,$label,$desc,$icon]):
              $checked=($pharm_settings[$key]??1)?'checked':'';
            ?>
            <div style="display:flex;align-items:center;gap:1.2rem;padding:1rem;border:1px solid var(--border);border-radius:12px;background:var(--bg-main);transition:all .2s;">
              <div style="width:40px;height:40px;border-radius:10px;background:#fff;display:flex;align-items:center;justify-content:center;color:var(--primary);flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,0.05);"><i class="fas <?=$icon?>"></i></div>
              <div style="flex:1;">
                <strong style="font-size:1.2rem;color:var(--text-color);"><?=$label?></strong>
                <p style="font-size:1rem;color:var(--text-muted);margin:.2rem 0 0;line-height:1.4;"><?=$desc?></p>
              </div>
              <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;cursor:pointer;">
                <input type="checkbox" name="<?=$key?>" value="1" <?=$checked?> style="opacity:0;width:0;height:0;">
                <span class="slider" style="position:absolute;inset:0;background:var(--border);border-radius:34px;transition:.3s;"></span>
                <span class="knob" style="position:absolute;height:22px;width:22px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 2px 4px rgba(0,0,0,0.2);"></span>
              </label>
            </div>
            <?php endforeach;?>
        </div>

        <div class="form-group" style="margin-top:2rem;">
          <label style="font-weight:600;">Preferred Notification Channel</label>
          <div style="position:relative;">
              <select class="form-control" name="preferred_channel" style="padding-left:40px;font-size:1.2rem;height:45px;">
                <option value="dashboard" <?=($pharm_settings['preferred_channel']??'')==='dashboard'?'selected':''?>>Dashboard Only</option>
                <option value="email" <?=($pharm_settings['preferred_channel']??'')==='email'?'selected':''?>>Email</option>
                <option value="sms" <?=($pharm_settings['preferred_channel']??'')==='sms'?'selected':''?>>SMS</option>
                <option value="all" <?=($pharm_settings['preferred_channel']??'')==='all'?'selected':''?>>All Channels</option>
              </select>
              <i class="fas fa-satellite-dish" style="position:absolute;left:15px;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:1.2rem;font-size:1.3rem;margin-top:1rem;border-radius:10px;"><span class="btn-text"><i class="fas fa-save"></i> Save Notification Settings</span></button>
      </form>
    </div>

    <!-- Appearance & General -->
    <div style="display:flex;flex-direction:column;gap:2rem;">
      <div class="adm-card" style="padding:2rem;box-shadow: 0 8px 30px rgba(0,0,0,0.04); border: 1px solid var(--border);border-radius:16px;">
        <h3 style="font-size:1.5rem;font-weight:700;margin:0 0 1.5rem;display:flex;align-items:center;gap:.8rem;">
            <div style="background:var(--primary-light);color:var(--primary);width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="fas fa-palette"></i></div>
            Appearance & Locale
        </h3>
        <div class="form-row">
            <div class="form-group">
              <label>Theme</label>
              <select class="form-control" id="settingsTheme" onchange="applyTheme(this.value)">
                <option value="light" <?=(($pharm_settings['theme_preference']??'light')==='light')?'selected':''?>>Light</option>
                <option value="dark" <?=(($pharm_settings['theme_preference']??'light')==='dark')?'selected':''?>>Dark</option>
              </select>
            </div>
            <div class="form-group">
              <label>Language</label>
              <select class="form-control" name="language" id="settingsLang">
                <option <?=($pharm_settings['language']??'English')==='English'?'selected':''?>>English</option>
              </select>
            </div>
        </div>
      </div>

      <div class="adm-card" style="padding:2rem;box-shadow: 0 8px 30px rgba(0,0,0,0.04); border: 1px solid var(--border);border-radius:16px;">
        <h3 style="font-size:1.5rem;font-weight:700;margin:0 0 1.5rem;display:flex;align-items:center;gap:.8rem;">
            <div style="background:var(--warning-light);color:var(--warning);width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="fas fa-shield-halved"></i></div>
            Security Settings
        </h3>
        <div style="background:var(--bg-main);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <div>
                <div style="font-weight:700;font-size:1.2rem;color:var(--text-color);margin-bottom:.2rem;">Two-Factor Authentication</div>
                <div style="font-size:1.1rem;color:var(--text-muted);">Protect your account with OTP</div>
              </div>
              <label style="position:relative;display:inline-block;width:52px;height:28px;cursor:pointer;">
                <input type="checkbox" id="twoFaToggle" <?=!empty($pharm_row['two_fa_enabled'])?'checked':''?> onchange="toggle2FA(this)" style="opacity:0;width:0;height:0;">
                <span class="slider" style="position:absolute;inset:0;background:var(--border);border-radius:34px;transition:.3s;"></span>
                <span class="knob" style="position:absolute;height:22px;width:22px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 2px 4px rgba(0,0,0,0.2);"></span>
              </label>
            </div>
        </div>
        
        <form onsubmit="changePassword(event)">
          <div class="form-group"><label>Current Password</label><input type="password" class="form-control" name="current_password" required placeholder="••••••••"></div>
          <div class="form-row">
              <div class="form-group"><label>New Password</label><input type="password" class="form-control" name="new_password" required minlength="6" placeholder="••••••••"></div>
              <div class="form-group"><label>Confirm New</label><input type="password" class="form-control" name="confirm_password" required placeholder="••••••••"></div>
          </div>
          <button type="submit" class="btn btn-outline" style="width:100%;justify-content:center;padding:1rem;color:var(--text-color);border-color:var(--border);"><span class="btn-text"><i class="fas fa-key"></i> Update Password</span></button>
        </form>
      </div>

      <?php include __DIR__.'/../../includes/active_sessions_panel.php'; ?>
    </div>
  </div>
</div>

<style>
input[type="checkbox"]:checked + .slider { background:var(--primary)!important; }
input[type="checkbox"]:checked ~ .knob { transform:translateX(24px)!important; }
#twoFaToggle:checked + .slider { background:var(--success)!important; }
</style>

<script>
async function saveSettings(e){
  e.preventDefault();
  const form=e.target;
  const data={action:'update_settings'};
  ['notif_new_prescription','notif_low_stock','notif_expiring_meds','notif_purchase_orders','notif_refill_requests','notif_system_alerts'].forEach(function(key){
    const el=form.querySelector('[name="'+key+'"]');
    data[key]=el&&el.checked?1:0;
  });
  data.preferred_channel=form.querySelector('[name="preferred_channel"]').value;
  data.theme_preference=document.getElementById('settingsTheme').value;
  data.language=document.getElementById('settingsLang').value;
  const r=await pharmAction(data);
  if(r.success) toast('Settings saved successfully');
  else toast(r.message||'Error','danger');
}

async function changePassword(e){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target));
  if(fd.new_password!==fd.confirm_password){toast('Passwords do not match','warning');return;}
  fd.action='change_password';
  const r=await pharmAction(fd);
  if(r.success){toast('Password changed successfully');e.target.reset();}
  else toast(r.message||'Error','danger');
}

async function revokeSession(id){
  if(!confirm('Revoke this session? You will be logged out of that device.')) return;
  const r=await pharmAction({action:'revoke_session',session_id:id});
  if(r.success){toast('Session revoked');setTimeout(()=>location.reload(),600);}
  else toast(r.message||'Error','danger');
}

async function toggle2FA(cb){
  const enable = cb.checked ? 1 : 0;
  const res = await pharmAction({action:'toggle_2fa', enable:enable});
  if(res.success) toast('2FA ' + (enable ? 'enabled':'disabled'));
  else { toast(res.message||'Error changing 2FA status','danger'); cb.checked = !cb.checked; }
}
</script>
