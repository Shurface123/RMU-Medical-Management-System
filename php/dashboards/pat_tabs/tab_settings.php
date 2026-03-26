<?php
// MODULE 9: SETTINGS
// Fetch current settings
$pat_settings=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM patient_settings WHERE patient_id=$pat_pk LIMIT 1"))??[];
// Activity log
$activity_log=[];
$q=mysqli_query($conn,"SELECT * FROM user_sessions WHERE user_id=$user_id ORDER BY login_time DESC LIMIT 20");
if($q) while($r=mysqli_fetch_assoc($q)) $activity_log[]=$r;
?>
<div id="sec-settings" class="dash-section">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

    <!-- Profile Settings -->
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3><i class="fas fa-user-edit" style="color:var(--primary);"></i> Profile Settings</h3></div>
      <div style="padding:2rem;">
        <!-- Photo upload -->
        <div style="text-align:center;margin-bottom:1.5rem;">
          <div id="settingsAvatar" style="width:100px;height:100px;border-radius:50%;margin:0 auto 1rem;overflow:hidden;border:3px solid var(--role-accent);display:flex;align-items:center;justify-content:center;background:var(--role-accent);color:#fff;font-size:3rem;">
            <?php $pimg=$pat_row['profile_image']??''; if(!empty($pimg)&&$pimg!=='default-avatar.png'):?>
            <img src="/RMU-Medical-Management-System/<?=htmlspecialchars($pimg)?>" style="width:100%;height:100%;object-fit:cover;" id="settingsPhotoImg">
            <?php else:?><span id="settingsPhotoInit"><?=strtoupper(substr($pat_row['name']??'P',0,1))?></span><?php endif;?>
          </div>
          <label class="adm-btn adm-btn-sm adm-btn-primary" style="cursor:pointer;">
            <i class="fas fa-camera"></i> Change Photo
            <input type="file" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="uploadPatPhoto(this)">
          </label>
        </div>
        <form onsubmit="saveProfile(event)">
          <div class="form-row">
            <div class="form-group"><label>Full Name</label><input type="text" name="name" class="form-control" value="<?=htmlspecialchars($pat_row['name']??'')?>" required></div>
            <div class="form-group"><label>Phone</label><input type="tel" name="phone" class="form-control" value="<?=htmlspecialchars($pat_row['phone']??'')?>"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" class="form-control" value="<?=htmlspecialchars($pat_row['date_of_birth']??'')?>"></div>
            <div class="form-group"><label>Gender</label>
              <select name="gender" class="form-control">
                <option value="">Select...</option>
                <?php foreach(['Male','Female','Other'] as $g):?>
                <option <?=($pat_row['gender']??'')===$g?'selected':''?>><?=$g?></option>
                <?php endforeach;?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Blood Type</label>
              <select name="blood_group" class="form-control">
                <option value="">Select...</option>
                <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg):?>
                <option <?=($pat_row['blood_group']??'')===$bg?'selected':''?>><?=$bg?></option>
                <?php endforeach;?>
              </select>
            </div>
            <div class="form-group"><label>Address</label><input type="text" name="address" class="form-control" value="<?=htmlspecialchars($pat_row['address']??'')?>"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Allergies</label><input type="text" name="allergies" class="form-control" value="<?=htmlspecialchars($pat_row['allergies']??'')?>"></div>
            <div class="form-group"><label>Chronic Conditions</label><input type="text" name="chronic_conditions" class="form-control" value="<?=htmlspecialchars($pat_row['chronic_conditions']??'')?>"></div>
          </div>
          <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-save"></i> Save Profile</button>
        </form>
      </div>
    </div>

    <!-- Right Column -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
      <!-- Change Password -->
      <div class="adm-card" style="margin:0;">
        <div class="adm-card-header"><h3><i class="fas fa-lock" style="color:var(--warning);"></i> Change Password</h3></div>
        <div style="padding:2rem;">
          <form onsubmit="changePwd(event)">
            <div class="form-group"><label>Current Password</label><input type="password" name="current_password" class="form-control" required></div>
            <div class="form-group"><label>New Password</label><input type="password" name="new_password" id="patNewPwd" class="form-control" required minlength="8" oninput="checkPwdStr(this)"><div id="patPwdStr" style="margin-top:.4rem;font-size:1.1rem;font-weight:600;"></div></div>
            <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
            <button type="submit" class="adm-btn adm-btn-warning" style="width:100%;justify-content:center;"><i class="fas fa-key"></i> Update Password</button>
          </form>
        </div>
      </div>

      <!-- Notification Preferences -->
      <div class="adm-card" style="margin:0;">
        <div class="adm-card-header"><h3><i class="fas fa-bell" style="color:var(--role-accent);"></i> Notification Preferences</h3></div>
        <div style="padding:1.5rem;">
          <form onsubmit="saveSettings(event)">
            <?php
            $toggles=[
              ['email_notifications','Email Notifications','Receive email alerts'],
              ['sms_notifications','SMS Notifications','Receive SMS alerts'],
              ['appointment_reminders','Appointment Reminders','Reminders before appointments'],
              ['prescription_alerts','Prescription Alerts','New prescriptions and refill updates'],
              ['lab_result_alerts','Lab Result Alerts','When lab results are available'],
              ['medical_record_alerts','Medical Record Updates','New medical records added'],
            ];
            foreach($toggles as [$key,$label,$desc]):
              $checked=($pat_settings[$key]??1)?'checked':'';
            ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.7rem 0;border-bottom:1px solid var(--border);">
              <div><div style="font-weight:600;font-size:1.25rem;"><?=$label?></div><div style="font-size:1.05rem;color:var(--text-muted);"><?=$desc?></div></div>
              <label style="position:relative;width:42px;height:24px;cursor:pointer;">
                <input type="checkbox" name="<?=$key?>" value="1" <?=$checked?> style="opacity:0;width:0;height:0;position:absolute;">
                <span class="notif-slider"></span>
              </label>
            </div>
            <?php endforeach;?>

            <!-- Privacy -->
            <div style="margin-top:1.2rem;">
              <div class="form-group"><label>Profile Visibility</label>
                <select name="profile_visibility" class="form-control">
                  <?php foreach(['public'=>'Public','doctors_only'=>'Doctors Only','private'=>'Private'] as $v=>$l):?>
                  <option value="<?=$v?>" <?=($pat_settings['profile_visibility']??'doctors_only')===$v?'selected':''?>><?=$l?></option>
                  <?php endforeach;?>
                </select>
              </div>
              <div class="form-group"><label>Language</label>
                <select name="language_preference" class="form-control">
                  <?php foreach(['English','French','Twi','Ga','Ewe'] as $l):?>
                  <option <?=($pat_settings['language_preference']??'English')===$l?'selected':''?>><?=$l?></option>
                  <?php endforeach;?>
                </select>
              </div>
            </div>
            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-save"></i> Save Settings</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Security & 2FA -->
  <div class="adm-card" style="margin-top:1.5rem;">
    <div class="adm-card-header"><h3><i class="fas fa-shield-halved" style="color:var(--success);"></i> Security & 2FA</h3></div>
    <div style="padding:2rem;">
      <div style="display:flex;justify-content:space-between;align-items:center;max-width:600px;">
        <div>
          <div style="font-weight:600;font-size:1.35rem;">Two-Factor Authentication (2FA)</div>
          <div style="font-size:1.1rem;color:var(--text-muted);margin-top:.2rem;">Adds an extra layer of security by requiring an email code at login.</div>
        </div>
        <label style="position:relative;width:50px;height:26px;cursor:pointer;flex-shrink:0;">
          <input type="checkbox" id="twoFaToggle" <?=!empty($pat_row['two_fa_enabled'])?'checked':''?> onchange="toggle2FA(this)" style="opacity:0;width:0;height:0;position:absolute;">
          <span class="tfa-slider"></span>
        </label>
      </div>
      <p style="margin-top:1.2rem;font-size:1.1rem;color:var(--text-muted);"><i class="fas fa-info-circle"></i> OTP codes will be sent to <strong><?=htmlspecialchars($pat_row['email']??'')?></strong>.</p>
      <style>
        .tfa-slider{position:absolute;inset:0;background:var(--border);border-radius:14px;transition:.3s;cursor:pointer;}
        .tfa-slider::before{content:'';position:absolute;width:20px;height:20px;border-radius:50%;background:#fff;bottom:3px;left:3px;transition:.3s;}
        input:checked+.tfa-slider{background:var(--success);}
        input:checked+.tfa-slider::before{transform:translateX(24px);}
      </style>
    </div>
  </div>

  <!-- Activity Log -->
  <div class="adm-card" style="margin-top:1.5rem;">
    <div class="adm-card-header"><h3><i class="fas fa-clipboard-list" style="color:var(--info);"></i> Account Activity Log</h3></div>
    <div class="adm-table-wrap" style="padding:0 .5rem;">
      <table class="adm-table">
        <thead><tr><th>Login Time</th><th>IP Address</th><th>Browser / Device</th><th>Last Active</th><th>Status</th></tr></thead>
        <tbody>
          <?php if(empty($activity_log)):?><tr><td colspan="5" style="text-align:center;color:var(--text-muted);">No login history</td></tr>
          <?php else: foreach($activity_log as $al):?>
          <tr>
            <td><?=date('d M Y, g:i A',strtotime($al['login_time']))?></td>
            <td><?=htmlspecialchars($al['ip_address']??'—')?></td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($al['user_agent']??'—')?></td>
            <td><?=$al['last_activity']?date('g:i A',strtotime($al['last_activity'])):'—'?></td>
            <td><?php if($al['is_active']):?><span class="adm-badge adm-badge-success">Active</span><?php else:?><span class="adm-badge adm-badge-info">Ended</span><?php endif;?></td>
          </tr>
          <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function checkPwdStr(inp){
  const v=inp.value,el=document.getElementById('patPwdStr');
  let s=0;if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  const labels=['','Weak','Fair','Strong','Very Strong'],colors=['','var(--danger)','var(--warning)','var(--info)','var(--success)'];
  el.textContent=labels[s]||'';el.style.color=colors[s]||'';
}
async function saveProfile(e){
  e.preventDefault();const fd=new FormData(e.target),data={action:'update_profile'};
  fd.forEach((v,k)=>data[k]=v);
  const r=await patAction(data);
  if(r.success) toast('Profile updated!'); else toast(r.message||'Error','danger');
}
async function uploadPatPhoto(input){
  if(!input.files[0])return;
  const fd=new FormData();fd.append('action','upload_profile_photo');fd.append('photo',input.files[0]);
  const r=await patAction(fd,true);
  if(r.success){
    toast('Photo updated!');
    const av=document.getElementById('settingsAvatar');
    av.innerHTML=`<img src="${r.photo_url}" style="width:100%;height:100%;object-fit:cover;">`;
  } else toast(r.message||'Error','danger');
}
async function changePwd(e){
  e.preventDefault();const fd=new FormData(e.target),data={action:'change_password'};
  fd.forEach((v,k)=>data[k]=v);
  const r=await patAction(data);
  if(r.success){toast('Password changed!');e.target.reset();document.getElementById('patPwdStr').textContent='';}
  else toast(r.message||'Error','danger');
}
async function saveSettings(e){
  e.preventDefault();const fd=new FormData(e.target),data={action:'save_settings'};
  ['email_notifications','sms_notifications','appointment_reminders','prescription_alerts','lab_result_alerts','medical_record_alerts'].forEach(k=>data[k]=fd.has(k)?1:0);
  data.profile_visibility=fd.get('profile_visibility');
  data.language_preference=fd.get('language_preference');
  const r=await patAction(data);
  if(r.success) toast('Settings saved!'); else toast(r.message||'Error','danger');
}
async function toggle2FA(cb){
  const enable = cb.checked ? 1 : 0;
  const res = await patAction({action:'toggle_2fa', enable:enable});
  if(res.success) toast('2FA ' + (enable ? 'enabled':'disabled'));
  else { toast(res.message||'Error','danger'); cb.checked = !cb.checked; }
}
</script>
