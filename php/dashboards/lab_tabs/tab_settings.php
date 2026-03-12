<!-- ═══════════════ MODULE 14: SETTINGS ═══════════════ -->
<?php $s=$prof_settings??[];?>
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-gear" style="color:var(--role-accent);margin-right:.6rem;"></i> Settings</h1>
    <p>Preferences, notifications, security, and accessibility</p>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
  <!-- Theme & Display -->
  <div class="adm-card" style="margin:0;">
    <div class="adm-card-header"><h3><i class="fas fa-palette"></i> Theme & Display</h3></div>
    <div class="adm-card-body">
      <div style="display:flex;justify-content:space-between;align-items:center;padding:.8rem 0;border-bottom:1px solid var(--border);">
        <div><strong style="font-size:1.35rem;">Dark Mode</strong><br><span style="color:var(--text-muted);font-size:1.15rem;">Switch between light and dark themes</span></div>
        <label style="position:relative;display:inline-block;width:52px;height:28px;cursor:pointer;">
          <input type="checkbox" id="stTheme" onchange="toggleTheme()" style="display:none;" <?=($s['theme']??'light')==='dark'?'checked':''?>>
          <span style="position:absolute;inset:0;background:var(--border);border-radius:20px;transition:.3s;"></span>
          <span style="position:absolute;left:3px;top:3px;width:22px;height:22px;background:#fff;border-radius:50%;transition:.3s;"></span>
        </label>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:.8rem 0;border-bottom:1px solid var(--border);">
        <div><strong style="font-size:1.35rem;">Alert Sound</strong><br><span style="color:var(--text-muted);font-size:1.15rem;">Play sound for critical alerts</span></div>
        <label style="position:relative;display:inline-block;width:52px;height:28px;cursor:pointer;">
          <input type="checkbox" id="stSound" onchange="updateSetting('alert_sound',this.checked?1:0)" style="display:none;" <?=($s['alert_sound']??1)?'checked':''?>>
          <span style="position:absolute;inset:0;background:var(--border);border-radius:20px;transition:.3s;"></span>
          <span style="position:absolute;left:3px;top:3px;width:22px;height:22px;background:#fff;border-radius:50%;transition:.3s;"></span>
        </label>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:.8rem 0;">
        <div><strong style="font-size:1.35rem;">Auto-Refresh</strong><br><span style="color:var(--text-muted);font-size:1.15rem;">Dashboard auto-refresh interval</span></div>
        <select id="stRefresh" class="form-control" style="width:120px;" onchange="updateSetting('auto_refresh_interval',this.value)">
          <option value="30" <?=($s['auto_refresh_interval']??60)==30?'selected':'';?>>30s</option>
          <option value="60" <?=($s['auto_refresh_interval']??60)==60?'selected':'';?>>1 min</option>
          <option value="120" <?=($s['auto_refresh_interval']??60)==120?'selected':'';?>>2 min</option>
          <option value="300" <?=($s['auto_refresh_interval']??60)==300?'selected':'';?>>5 min</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Notification Preferences -->
  <div class="adm-card" style="margin:0;">
    <div class="adm-card-header"><h3><i class="fas fa-bell"></i> Notification Preferences</h3></div>
    <div class="adm-card-body">
      <?php
      $notif_prefs=[
        ['key'=>'notify_new_orders','label'=>'New Test Orders','desc'=>'When a doctor submits a new lab order'],
        ['key'=>'notify_critical_results','label'=>'Critical Results','desc'=>'Critical value alerts requiring immediate action'],
        ['key'=>'notify_equipment_alerts','label'=>'Equipment Alerts','desc'=>'Calibration due, maintenance reminders'],
        ['key'=>'notify_low_reagents','label'=>'Low Reagent Alerts','desc'=>'Stock below reorder level'],
        ['key'=>'notify_qc_failures','label'=>'QC Failures','desc'=>'Failed quality control checks'],
        ['key'=>'notify_messages','label'=>'Messages','desc'=>'New message from doctors'],
        ['key'=>'notify_result_amendments','label'=>'Result Amendments','desc'=>'When results are amended or reviewed'],
      ];
      foreach($notif_prefs as $np):?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:.7rem 0;border-bottom:1px solid var(--border);">
        <div><strong style="font-size:1.25rem;"><?=$np['label']?></strong><br><span style="color:var(--text-muted);font-size:1.1rem;"><?=$np['desc']?></span></div>
        <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;">
          <input type="checkbox" onchange="updateSetting('<?=$np['key']?>',this.checked?1:0)" style="display:none;" <?=($s[$np['key']]??1)?'checked':''?>>
          <span style="position:absolute;inset:0;background:var(--border);border-radius:20px;transition:.3s;"></span>
          <span style="position:absolute;left:2px;top:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:.3s;"></span>
        </label>
      </div>
      <?php endforeach;?>
    </div>
  </div>

  <!-- Availability -->
  <div class="adm-card" style="margin:0;">
    <div class="adm-card-header"><h3><i class="fas fa-signal"></i> Availability Status</h3></div>
    <div class="adm-card-body">
      <p style="color:var(--text-muted);margin-bottom:1.5rem;">Set your current availability status — visible to other lab staff and doctors</p>
      <div style="display:flex;gap:1rem;flex-wrap:wrap;">
        <?php $avail_opts=['Available'=>'success','Busy'=>'warning','On Break'=>'info','Off Duty'=>'danger']; foreach($avail_opts as $av=>$cls):?>
        <button class="adm-btn adm-btn-<?=$cls?> <?=($tech_row['availability_status']??'Available')===$av?'':'adm-btn-outline'?>" onclick="updateAvailability('<?=$av?>')" style="flex:1;min-width:100px;font-size:1.2rem;padding:.8rem;"><?=$av?></button>
        <?php endforeach;?>
      </div>
    </div>
  </div>

  <!-- Security -->
  <div class="adm-card" style="margin:0;">
    <div class="adm-card-header"><h3><i class="fas fa-lock"></i> Security</h3></div>
    <div class="adm-card-body">
      <h4 style="font-weight:700;margin-bottom:1rem;">Change Password</h4>
      <div class="form-group"><label>Current Password</label><input id="st_curpass" type="password" class="form-control"></div>
      <div class="form-group"><label>New Password</label><input id="st_newpass" type="password" class="form-control"></div>
      <div class="form-group"><label>Confirm New Password</label><input id="st_confpass" type="password" class="form-control"></div>
      <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="changePassword()"><i class="fas fa-key"></i> Update Password</button>
      <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--border);">
        <h4 style="font-weight:700;margin-bottom:.8rem;">Session Info</h4>
        <div style="font-size:1.2rem;color:var(--text-muted);">
          <div><strong>Last Login:</strong> <?=$tech_row['last_login']?date('d M Y, h:i A',strtotime($tech_row['last_login'])):'—'?></div>
          <div><strong>Session Started:</strong> <?=date('d M Y, h:i A')?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Toggle switch styling fix
document.querySelectorAll('input[type=checkbox]+span+span').forEach(toggle=>{
  const cb=toggle.previousElementSibling.previousElementSibling;
  if(cb.checked){toggle.style.left='27px';toggle.previousElementSibling.style.background='var(--role-accent)';}
  cb.addEventListener('change',()=>{
    toggle.style.left=cb.checked?'27px':'3px';
    toggle.previousElementSibling.style.background=cb.checked?'var(--role-accent)':'var(--border)';
  });
});
async function updateSetting(key,value){
  const r=await labAction({action:'update_setting',key:key,value:value});
  showToast(r.success?'Setting updated':'Error','success');
}
async function updateAvailability(status){
  const r=await labAction({action:'update_availability',status:status});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),500);
}
async function changePassword(){
  const cur=document.getElementById('st_curpass').value,np=document.getElementById('st_newpass').value,conf=document.getElementById('st_confpass').value;
  if(!cur||!np||!conf){showToast('All fields required','error');return;}
  if(np!==conf){showToast('Passwords do not match','error');return;}
  if(np.length<8){showToast('Password must be at least 8 characters','error');return;}
  const r=await labAction({action:'change_password',current_password:cur,new_password:np});
  showToast(r.message,r.success?'success':'error');
  if(r.success){document.getElementById('st_curpass').value='';document.getElementById('st_newpass').value='';document.getElementById('st_confpass').value='';}
}
</script>
