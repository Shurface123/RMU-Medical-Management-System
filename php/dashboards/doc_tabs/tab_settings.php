<?php // TAB: SETTINGS ?>
<div id="sec-settings" class="dash-section">
  <div class="sec-header"><h2><i class="fas fa-gear"></i> Settings</h2></div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

    <!-- Profile Settings -->
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3><i class="fas fa-user-pen"></i> Profile Settings</h3></div>
      <div style="padding:2rem;">
        <form id="formProfile" onsubmit="saveProfile(event)">
          <div style="text-align:center;margin-bottom:1.5rem;">
            <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--role-accent),var(--primary));color:#fff;display:flex;align-items:center;justify-content:center;font-size:2.4rem;font-weight:700;margin:0 auto 1rem;"><?=strtoupper(substr($doc_row['name'],0,1))?></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Full Name</label><input type="text" name="name" class="form-control" value="<?=htmlspecialchars($doc_row['name'])?>"></div>
            <div class="form-group"><label>Specialization</label><input type="text" name="specialization" class="form-control" value="<?=htmlspecialchars($doc_row['specialization']?:'')?>"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?=htmlspecialchars($doc_row['email']?:'')?>"></div>
            <div class="form-group"><label>Phone</label><input type="tel" name="phone" class="form-control" value="<?=htmlspecialchars($doc_row['phone']?:'')?>"></div>
          </div>
          <div class="form-group"><label>Bio / About</label><textarea name="bio" class="form-control" rows="3"><?=htmlspecialchars($doc_row['bio']??'')?></textarea></div>
          <div class="form-group"><label>License Number</label><input type="text" name="license_number" class="form-control" value="<?=htmlspecialchars($doc_row['license_number']??'')?>"></div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-save"></i> Save Profile</span></button>
        </form>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:1.5rem;">

      <!-- Change Password -->
      <div class="adm-card" style="margin:0;">
        <div class="adm-card-header"><h3><i class="fas fa-lock"></i> Change Password</h3></div>
        <div style="padding:2rem;">
          <form id="formPassword" onsubmit="changePassword(event)">
            <div class="form-group"><label>Current Password</label><input type="password" name="current_password" class="form-control" placeholder="Current password" required></div>
            <div class="form-group"><label>New Password</label><input type="password" name="new_password" id="newPwd" class="form-control" placeholder="Min 8 characters" required minlength="8"></div>
            <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" id="confPwd" class="form-control" placeholder="Repeat new password" required></div>
            <button type="submit" class="btn btn-warning" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-key"></i> Update Password</span></button>
          </form>
        </div>
      </div>

      <!-- Availability Schedule -->
      <div class="adm-card" style="margin:0;">
        <div class="adm-card-header"><h3><i class="fas fa-clock"></i> Availability Schedule</h3></div>
        <div style="padding:2rem;">
          <form id="formAvail" onsubmit="saveAvailability(event)">
            <div class="form-group"><label>Available Days</label>
              <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.5rem;">
                <?php
                $avail_days=explode(',',str_replace(' ','',$doc_row['available_days']??''));
                foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d):
                  $checked=in_array($d,$avail_days)?'checked':'';
                ?>
                <label style="display:flex;align-items:center;gap:.4rem;padding:.5rem .8rem;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;font-size:1.2rem;">
                  <input type="checkbox" name="available_days[]" value="<?=$d?>" <?=$checked?>> <?=$d?>
                </label>
                <?php endforeach;?>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group"><label>From Time</label>
                <?php $hours=explode('-',$doc_row['available_hours']??'08:00-17:00');?>
                <input type="time" name="hours_from" class="form-control" value="<?=trim($hours[0]??'08:00')?>">
              </div>
              <div class="form-group"><label>To Time</label><input type="time" name="hours_to" class="form-control" value="<?=trim($hours[1]??'17:00')?>"></div>
            </div>
            <div class="form-group">
              <label>Availability Status</label>
              <div style="display:flex;gap:1rem;margin-top:.5rem;">
                <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:1.3rem;">
                  <input type="radio" name="is_available" value="1" <?=$doc_row['is_available']?'checked':''?>> <span style="color:var(--success);">Available</span>
                </label>
                <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:1.3rem;">
                  <input type="radio" name="is_available" value="0" <?=!$doc_row['is_available']?'checked':''?>> <span style="color:var(--danger);">Unavailable</span>
                </label>
              </div>
            </div>
            <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-calendar-check"></i> Update Schedule</span></button>
          </form>
        </div>
      </div>

      <!-- Notification Preferences -->
      <div class="adm-card" style="margin:0;">
        <div class="adm-card-header"><h3><i class="fas fa-bell-slash"></i> Notification Preferences</h3></div>
        <div style="padding:2rem;">
          <?php foreach([
            ['notif_appointments','Appointment Notifications','New, rescheduled, or cancelled appointments','checked'],
            ['notif_lab','Lab Result Alerts','When technician submits lab results','checked'],
            ['notif_nurse','Nurse/Staff Messages','Direct instructions from nurses','checked'],
            ['notif_inventory','Inventory Alerts','Low stock or expiry warnings','checked'],
          ] as [$name,$label,$desc,$checked]):?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:.8rem 0;border-bottom:1px solid var(--border);">
            <div>
              <div style="font-weight:600;font-size:1.3rem;"><?=$label?></div>
              <div style="font-size:1.1rem;color:var(--text-muted);"><?=$desc?></div>
            </div>
            <label style="position:relative;width:42px;height:24px;cursor:pointer;">
              <input type="checkbox" name="<?=$name?>" <?=$checked?> onchange="saveNotifPref(this)" style="opacity:0;width:0;height:0;position:absolute;">
              <span style="position:absolute;inset:0;background:var(--border);border-radius:12px;transition:.3s;" class="notif-slider"></span>
            </label>
          </div>
          <?php endforeach;?>
          <style>.notif-slider{cursor:pointer;}.notif-slider::before{content:'';position:absolute;width:18px;height:18px;border-radius:50%;background:#fff;bottom:3px;left:3px;transition:.3s;}input:checked+.notif-slider{background:var(--role-accent);}input:checked+.notif-slider::before{transform:translateX(18px);}</style>
        </div>
      </div>

      <!-- Security Settings -->
      <div class="adm-card" style="margin:0;">
        <div class="adm-card-header"><h3><i class="fas fa-shield-halved"></i> Security & 2FA</h3></div>
        <div style="padding:2rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <div style="font-weight:600;font-size:1.3rem;">Two-Factor Authentication (2FA)</div>
              <div style="font-size:1.1rem;color:var(--text-muted);">Secure your account with an email OTP at login</div>
            </div>
            <label style="position:relative;width:48px;height:26px;cursor:pointer;">
              <input type="checkbox" id="twoFaToggle" <?=!empty($doc_row['two_fa_enabled'])?'checked':''?> onchange="toggle2FA(this)" style="opacity:0;width:0;height:0;position:absolute;">
              <span style="position:absolute;inset:0;background:var(--border);border-radius:14px;transition:.3s;" class="tfa-slider"></span>
            </label>
          </div>
          <p style="margin-top:1rem;font-size:1rem;color:var(--text-muted);"><i class="fas fa-circle-info"></i> When enabled, we'll send a 6-digit code to <strong><?=htmlspecialchars($doc_row['email'])?></strong> each time you sign in.</p>
          <style>.tfa-slider{cursor:pointer;}.tfa-slider::before{content:'';position:absolute;width:20px;height:20px;border-radius:50%;background:#fff;bottom:3px;left:3px;transition:.3s;}input:checked+.tfa-slider{background:var(--success);}input:checked+.tfa-slider::before{transform:translateX(22px);}</style>
        </div>
      </div>

    <?php include __DIR__.'/../../includes/active_sessions_panel.php'; ?>
  </div>
</div>
</div>
<script>
async function toggle2FA(cb){
  const enable = cb.checked ? 1 : 0;
  const res = await docAction({action:'toggle_2fa', enable:enable});
  if(res.success) toast('2FA ' + (enable ? 'enabled':'disabled'));
  else {
    toast(res.message||'Error','danger');
    cb.checked = !cb.checked;
  }
}
async function saveProfile(e){
  e.preventDefault();
  const fd=new FormData(e.target), data={action:'update_profile'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await docAction(data);
  if(res.success) toast('Profile updated successfully!');
  else toast(res.message||'Error updating profile','danger');
}
async function changePassword(e){
  e.preventDefault();
  const np=document.getElementById('newPwd').value, cp=document.getElementById('confPwd').value;
  if(np!==cp){toast('Passwords do not match','warning');return;}
  const fd=new FormData(e.target), data={action:'change_password'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await docAction(data);
  if(res.success){toast('Password changed!');e.target.reset();}
  else toast(res.message||'Error','danger');
}
async function saveAvailability(e){
  e.preventDefault();
  const fd=new FormData(e.target);
  const days=[...fd.getAll('available_days')].join(',');
  const data={action:'update_availability',available_days:days,hours_from:fd.get('hours_from'),hours_to:fd.get('hours_to'),is_available:fd.get('is_available')};
  const res=await docAction(data);
  if(res.success) toast('Schedule updated!');
  else toast(res.message||'Error','danger');
}
function saveNotifPref(cb){toast((cb.checked?'Enabled ':'Disabled ')+cb.name.replace('notif_','')+ ' notifications');}
</script>
