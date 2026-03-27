<!-- ════════════════════════════════════════════════════════════
     SETTINGS TAB
     ════════════════════════════════════════════════════════════ -->
<?php
  $pharm_settings=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM pharmacy_settings WHERE pharmacist_id=$pharm_pk LIMIT 1"));
  if(!$pharm_settings) $pharm_settings=['notif_new_prescription'=>1,'notif_low_stock'=>1,'notif_expiring_meds'=>1,'notif_purchase_orders'=>1,'notif_refill_requests'=>1,'notif_system_alerts'=>1,'preferred_channel'=>'dashboard','theme_preference'=>'light','language'=>'English'];
?>
<div id="sec-settings" class="dash-section <?=($active_tab==='settings')?'active':''?>">

  <div class="sec-header">
    <h2><i class="fas fa-gear"></i> Settings</h2>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
    <!-- Notification Preferences -->
    <div class="adm-card" style="padding:1.8rem;">
      <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1.5rem;"><i class="fas fa-bell" style="color:var(--role-accent);margin-right:.5rem;"></i>Notification Preferences</h3>
      <form id="formNotifSettings" onsubmit="saveSettings(event)">
        <?php foreach([
          ['notif_new_prescription','New Prescriptions','Get notified when a doctor writes a new prescription'],
          ['notif_low_stock','Low Stock Alerts','Alert when medicine stock falls below reorder level'],
          ['notif_expiring_meds','Expiring Medicines','Alert for medicines expiring within 30 days'],
          ['notif_purchase_orders','Purchase Orders','Updates on purchase order status changes'],
          ['notif_refill_requests','Refill Requests','Patient refill request notifications'],
          ['notif_system_alerts','System Alerts','General system notifications and updates'],
        ] as [$key,$label,$desc]):
          $checked=($pharm_settings[$key]??1)?'checked':'';
        ?>
        <div style="display:flex;align-items:flex-start;gap:1rem;padding:1rem 0;border-bottom:1px solid var(--border);">
          <label style="position:relative;display:inline-block;width:48px;height:26px;flex-shrink:0;margin-top:.2rem;">
            <input type="checkbox" name="<?=$key?>" value="1" <?=$checked?> style="opacity:0;width:0;height:0;">
            <span style="position:absolute;cursor:pointer;inset:0;background:var(--border);border-radius:26px;transition:.3s;"></span>
            <style>input:checked + span{background:var(--role-accent)!important;}input:checked + span::before{transform:translateX(22px)!important;}</style>
            <span style="position:absolute;content:'';height:20px;width:20px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;"></span>
          </label>
          <div>
            <strong style="font-size:1.3rem;"><?=$label?></strong>
            <p style="font-size:1.1rem;color:var(--text-muted);margin:.2rem 0 0;"><?=$desc?></p>
          </div>
        </div>
        <?php endforeach;?>

        <div class="form-group" style="margin-top:1.5rem;">
          <label>Preferred Channel</label>
          <select class="form-control" name="preferred_channel">
            <option value="dashboard" <?=($pharm_settings['preferred_channel']??'')==='dashboard'?'selected':''?>>Dashboard Only</option>
            <option value="email" <?=($pharm_settings['preferred_channel']??'')==='email'?'selected':''?>>Email</option>
            <option value="sms" <?=($pharm_settings['preferred_channel']??'')==='sms'?'selected':''?>>SMS</option>
            <option value="all" <?=($pharm_settings['preferred_channel']??'')==='all'?'selected':''?>>All Channels</option>
          </select>
        </div>

        <button type="submit" class="adm-btn adm-btn-success" style="width:100%;justify-content:center;margin-top:1rem;"><i class="fas fa-save"></i> Save Notification Settings</button>
      </form>
    </div>

    <!-- Appearance & General -->
    <div>
      <div class="adm-card" style="padding:1.8rem;margin-bottom:1.5rem;">
        <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1.5rem;"><i class="fas fa-palette" style="color:var(--primary);margin-right:.5rem;"></i>Appearance</h3>
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

      <div class="adm-card" style="padding:1.8rem;margin-bottom:1.5rem;">
        <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1.5rem;"><i class="fas fa-key" style="color:var(--warning);margin-right:.5rem;"></i>Security & 2FA</h3>
        <form onsubmit="changePassword(event)">
          <div class="form-group"><label>Current Password</label><input type="password" class="form-control" name="current_password" required></div>
          <div class="form-group"><label>New Password</label><input type="password" class="form-control" name="new_password" required minlength="6"></div>
          <div class="form-group"><label>Confirm New Password</label><input type="password" class="form-control" name="confirm_password" required></div>
          <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-lock"></i> Change Password</button>
        </form>
        <hr style="margin:1.5rem 0;border:none;border-bottom:1px solid var(--border);">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <div>
            <div style="font-weight:600;font-size:1.3rem;">2FA Authentication</div>
            <div style="font-size:1.1rem;color:var(--text-muted);">Protect your account with OTP</div>
          </div>
          <label style="position:relative;display:inline-block;width:48px;height:26px;cursor:pointer;">
            <input type="checkbox" id="twoFaToggle" <?=!empty($pharm_row['two_fa_enabled'])?'checked':''?> onchange="toggle2FA(this)" style="opacity:0;width:0;height:0;">
            <span style="position:absolute;cursor:pointer;inset:0;background:var(--border);border-radius:26px;transition:.3s;"></span>
            <span style="position:absolute;content:'';height:20px;width:20px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;"></span>
          </label>
        </div>
      </div>
      <style>#twoFaToggle:checked + span{background:var(--success)!important;}#twoFaToggle:checked + span + span{transform:translateX(22px)!important;}</style>

      <div class="adm-card" style="padding:1.8rem;">
        <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-laptop" style="color:var(--info);margin-right:.5rem;"></i>Active Sessions</h3>
        <?php
          $sessions=[];
          $q=mysqli_query($conn,"SELECT * FROM pharmacist_sessions WHERE pharmacist_id=$pharm_pk ORDER BY last_active DESC LIMIT 5");
          if($q) while($r=mysqli_fetch_assoc($q)) $sessions[]=$r;
        ?>
        <?php if(empty($sessions)):?>
        <p style="color:var(--text-muted);text-align:center;padding:1rem;">No session data available</p>
        <?php else: foreach($sessions as $sess):?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.8rem 0;border-bottom:1px solid var(--border);">
          <div>
            <div style="font-weight:600;"><?=htmlspecialchars($sess['browser']??'Unknown Browser')?></div>
            <div style="font-size:1.1rem;color:var(--text-muted);">IP: <?=htmlspecialchars($sess['ip_address']??'—')?> · <?=$sess['last_active']?date('d M, g:i A',strtotime($sess['last_active'])):'—'?></div>
          </div>
          <?php if($sess['is_current']):?><span class="adm-badge adm-badge-success">Current</span>
          <?php else:?><button class="adm-btn adm-btn-danger adm-btn-sm" onclick="revokeSession(<?=$sess['id']?>)"><i class="fas fa-times"></i></button><?php endif;?>
        </div>
        <?php endforeach; endif;?>
      <?php include __DIR__.'/../../includes/active_sessions_panel.php'; ?>
    </div>
  </div>
</div>

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
  if(r.success) toast('Settings saved');
  else toast(r.message||'Error','danger');
}

async function changePassword(e){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target));
  if(fd.new_password!==fd.confirm_password){toast('Passwords do not match','danger');return;}
  fd.action='change_password';
  const r=await pharmAction(fd);
  if(r.success){toast('Password changed');e.target.reset();}
  else toast(r.message||'Error','danger');
}

async function revokeSession(id){
  if(!confirm('Revoke this session?')) return;
  const r=await pharmAction({action:'revoke_session',session_id:id});
  if(r.success){toast('Session revoked');setTimeout(()=>location.reload(),600);}
  else toast(r.message||'Error','danger');
}

async function toggle2FA(cb){
  const enable = cb.checked ? 1 : 0;
  const res = await pharmAction({action:'toggle_2fa', enable:enable});
  if(res.success) toast('2FA ' + (enable ? 'enabled':'disabled'));
  else { toast(res.message||'Error','danger'); cb.checked = !cb.checked; }
}
</script>
