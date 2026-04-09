<?php
// TAB: PROFILE — Module 12
$prof = $fs_row;
$age  = !empty($prof['date_of_birth']) ? (int)date_diff(date_create($prof['date_of_birth']), date_create('today'))->y : null;
$settings_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM finance_settings WHERE user_id=$user_id LIMIT 1")) ?: [];
$completeness_fields = ['photo'=>!empty($prof['profile_image']), 'name'=>!empty($prof['name']), 'email'=>!empty($prof['email']), 'phone'=>!empty($prof['phone']), 'dob'=>!empty($prof['date_of_birth']), 'designation'=>!empty($prof['designation']), 'department'=>!empty($prof['department'])];
$complete_pct = (int)round((count(array_filter($completeness_fields))/count($completeness_fields))*100);
?>
<div id="sec-profile" class="dash-section">

<!-- Profile Header -->
<div class="fin-hero" style="margin-bottom:2rem;">
  <div style="position:relative;">
    <?php if(!empty($prof['profile_image'])&&file_exists(dirname(dirname(dirname(__DIR__))).'/'.$prof['profile_image'])): ?>
      <img src="/RMU-Medical-Management-System/<?=htmlspecialchars($prof['profile_image'])?>" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.4);" alt="Profile">
    <?php else: ?>
      <div style="width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:3rem;border:3px solid rgba(255,255,255,.4);">
        <i class="fas fa-user-tie"></i>
      </div>
    <?php endif;?>
    <label for="profilePhotoInput" style="position:absolute;bottom:0;right:0;width:28px;height:28px;border-radius:50%;background:var(--role-gold);color:#000;display:flex;align-items:center;justify-content:center;font-size:1.1rem;cursor:pointer;" title="Change Photo"><i class="fas fa-camera"></i></label>
    <input type="file" id="profilePhotoInput" accept="image/jpeg,image/png" style="display:none;" onchange="uploadProfilePhoto(this)">
  </div>
  <div style="flex:1;position:relative;z-index:1;">
    <h2><?=htmlspecialchars($prof['name']??'Finance Staff')?></h2>
    <p><?=htmlspecialchars($prof['designation']??ucfirst(str_replace('_',' ',$user_role)?:'Finance Officer'))?> &mdash; <?=htmlspecialchars($prof['department']??'Finance & Revenue')?></p>
    <div style="margin-top:.6rem;">
      <span class="fin-hero-badge"><i class="fas fa-id-badge"></i><?=htmlspecialchars($prof['staff_code']??'FIN-000')?></span>
      <span class="fin-hero-badge"><i class="fas fa-envelope"></i><?=htmlspecialchars($prof['email']??'—')?></span>
      <?php if(!empty($prof['last_login'])): ?><span class="fin-hero-badge"><i class="fas fa-clock"></i>Last login: <?=date('d M Y, g:i A',strtotime($prof['last_login']))?></span><?php endif;?>
    </div>
    <!-- Completeness -->
    <div style="margin-top:1.2rem;">
      <div style="font-size:1.2rem;opacity:.8;margin-bottom:.4rem;">Profile <?=$complete_pct?>% complete</div>
      <div style="height:6px;background:rgba(255,255,255,.2);border-radius:10px;overflow:hidden;max-width:280px;">
        <div style="height:100%;width:<?=$complete_pct?>%;background:linear-gradient(90deg,#fff,rgba(255,255,255,.6));border-radius:10px;transition:width .6s ease;"></div>
      </div>
    </div>
  </div>
  <div style="position:relative;z-index:1;">
    <div style="display:flex;align-items:center;gap:.6rem;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.28);border-radius:50px;padding:.5rem 1.2rem;font-size:1.2rem;">
      <span style="width:10px;height:10px;border-radius:50%;background:#27AE60;box-shadow:0 0 6px #27AE60;"></span> Online
    </div>
  </div>
</div>

<!-- Profile Sections via Tabs -->
<div class="adm-tabs" style="margin-bottom:2rem;">
  <button class="adm-tab-btn active" onclick="showProfileSection('profPersonal',this)"><i class="fas fa-user"></i> Personal</button>
  <button class="adm-tab-btn" onclick="showProfileSection('profProfessional',this)"><i class="fas fa-briefcase"></i> Professional</button>
  <button class="adm-tab-btn" onclick="showProfileSection('profSecurity',this)"><i class="fas fa-shield-halved"></i> Security</button>
  <button class="adm-tab-btn" onclick="showProfileSection('profNotifPrefs',this)"><i class="fas fa-bell"></i> Notifications</button>
</div>

<!-- Section A: Personal -->
<div id="profPersonal" class="prof-section">
<div class="adm-card">
  <div class="adm-card-header">
    <h3><i class="fas fa-user"></i> Personal Information</h3>
    <button onclick="toggleEdit('profPersonalForm')" class="adm-btn adm-btn-ghost adm-btn-sm" id="editProfBtn"><i class="fas fa-pen"></i> Edit</button>
  </div>
  <div class="adm-card-body">
    <form id="profPersonalForm">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
      <?php $fields=[
        ['Full Name','name','text',$prof['name']??''],
        ['Date of Birth','date_of_birth','date',$prof['date_of_birth']??''],
        ['Gender','gender','text',$prof['gender']??''],
        ['National ID','national_id','text',$prof['national_id']??''],
        ['Primary Phone','phone','tel',$prof['phone']??''],
        ['Official Email','email','email',$prof['email']??''],
        ['Residential Address','address','text',$prof['residential_address']??''],
      ];
      foreach($fields as [$label,$name,$type,$val]): ?>
      <div class="adm-form-group">
        <label><?=$label?></label>
        <input type="<?=$type?>" name="<?=$name?>" value="<?=htmlspecialchars($val)?>" class="adm-search-input profile-field" readonly>
        <?php if($name==='date_of_birth'&&$age!==null): ?><div style="font-size:1.15rem;color:var(--text-muted);margin-top:.3rem;">Age: <?=$age?> years</div><?php endif;?>
      </div>
      <?php endforeach;?>
    </div>
    </form>
  </div>
  <div id="savePersonalWrap" style="display:none;padding:1.5rem 2.5rem;border-top:1px solid var(--border);display:none;justify-content:flex-end;gap:1rem;">
    <button onclick="cancelEditProfile()" class="adm-btn adm-btn-ghost">Cancel</button>
    <button onclick="saveProfileSection('profPersonalForm','update_personal_info')" class="adm-btn adm-btn-primary"><i class="fas fa-check"></i> Save Changes</button>
  </div>
</div>
</div><!-- /profPersonal -->

<!-- Section B: Professional -->
<div id="profProfessional" class="prof-section" style="display:none;">
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-briefcase"></i> Professional Profile</h3></div>
  <div class="adm-card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
      <?php foreach([
        ['Designation',$prof['designation']??''],
        ['Department',$prof['department']??'Finance & Revenue'],
        ['Staff ID',$prof['staff_code']??''],
        ['Years of Experience',($prof['years_experience']??'').' years'],
        ['Role Level',ucfirst(str_replace('_',' ',$prof['role_level']??$user_role))],
        ['Date Joined',!empty($prof['created_at'])?date('d M Y',strtotime($prof['created_at'])):' —'],
      ] as [$label,$val]): ?>
      <div class="adm-form-group">
        <label><?=$label?></label>
        <div style="padding:1rem 1.2rem;background:var(--surface-2);border:1px solid var(--border);border-radius:10px;font-size:1.35rem;"><?=htmlspecialchars($val)?></div>
      </div>
      <?php endforeach;?>
    </div>
    <!-- Completeness Checklist -->
    <div style="margin-top:2rem;padding:1.5rem;background:var(--surface-2);border-radius:var(--radius-md);border:1px solid var(--border);">
      <div style="font-weight:700;font-size:1.4rem;margin-bottom:1.2rem;"><i class="fas fa-list-check" style="color:var(--role-accent);"></i> Profile Completeness</div>
      <?php foreach($completeness_fields as $field=>$done): ?>
      <div style="display:flex;align-items:center;gap:.8rem;padding:.5rem 0;">
        <i class="fas fa-<?=$done?'check-circle':'circle-exclamation'?>" style="color:<?=$done?'var(--success)':'var(--warning)'?>;font-size:1.4rem;"></i>
        <span style="font-size:1.3rem;color:var(--<?=$done?'text-primary':'text-muted'?>);"><?=ucfirst(str_replace('_',' ',$field))?></span>
        <?php if(!$done): ?><a href="#" onclick="showProfileSection('profPersonal',document.querySelector('.adm-tab-btn'));toggleEdit('profPersonalForm')" style="color:var(--role-accent);font-size:1.1rem;margin-left:auto;">Complete Now</a><?php endif;?>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</div>
</div><!-- /profProfessional -->

<!-- Section C: Security -->
<div id="profSecurity" class="prof-section" style="display:none;">
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-lock"></i> Change Password</h3></div>
  <div class="adm-card-body">
    <form id="formChangePassword" style="max-width:480px;">
      <div class="adm-form-group">
        <label>Current Password</label>
        <input type="password" name="current_password" class="adm-search-input" required>
      </div>
      <div class="adm-form-group">
        <label>New Password</label>
        <input type="password" name="new_password" id="newPwdInput" class="adm-search-input" required oninput="checkPwdStrength(this.value)">
        <div style="margin-top:.5rem;height:6px;background:var(--border);border-radius:10px;overflow:hidden;"><div id="pwdStrengthBar" style="height:100%;width:0%;border-radius:10px;transition:all .3s;"></div></div>
        <div id="pwdStrengthLabel" style="font-size:1.1rem;margin-top:.3rem;color:var(--text-muted);"></div>
      </div>
      <div class="adm-form-group">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" class="adm-search-input" required>
      </div>
      <button type="button" onclick="submitPasswordChange()" class="adm-btn adm-btn-primary"><i class="fas fa-lock"></i> Update Password</button>
    </form>
  </div>
</div>
<div class="adm-card" style="margin-top:2rem;">
  <div class="adm-card-header"><h3><i class="fas fa-shield-check"></i> Account Activity</h3></div>
  <div class="adm-card-body">
    <?php
    $activity=[];
    $aq=mysqli_query($conn,"SELECT * FROM finance_audit_trail WHERE actor_user_id=$user_id ORDER BY created_at DESC LIMIT 15");
    if($aq) while($r=mysqli_fetch_assoc($aq)) $activity[]=$r;
    if(empty($activity)): ?>
      <p style="color:var(--text-muted);text-align:center;padding:2rem;">No activity logged yet.</p>
    <?php else: foreach($activity as $act): ?>
      <div style="display:flex;gap:1rem;padding:.8rem 0;border-bottom:1px solid var(--border);align-items:flex-start;">
        <div style="width:36px;height:36px;border-radius:10px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-clock-rotate-left"></i></div>
        <div style="flex:1;"><div style="font-size:1.3rem;font-weight:500;"><?=htmlspecialchars($act['action_type']??'Action')?></div>
        <div style="font-size:1.15rem;color:var(--text-muted);"><?=htmlspecialchars($act['description']??'')?></div></div>
        <div style="font-size:1.1rem;color:var(--text-muted);white-space:nowrap;"><?=date('d M, g:i A',strtotime($act['created_at']))?></div>
      </div>
    <?php endforeach; endif;?>
  </div>
</div>
</div><!-- /profSecurity -->

<!-- Section D: Notification Preferences -->
<div id="profNotifPrefs" class="prof-section" style="display:none;">
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-bell"></i> Notification Preferences</h3></div>
  <div class="adm-card-body">
    <div id="notifPrefsForm">
    <?php
    $notif_types=[
      ['new_payment_notif', 'New Payment Received'],
      ['invoice_overdue_notif', 'Invoice Overdue Alert'],
      ['insurance_claim_notif', 'Insurance Claim Update'],
      ['waiver_approved_notif', 'Waiver Approved/Rejected'],
      ['refund_processed_notif', 'Refund Processed'],
      ['paystack_alert_notif', 'Paystack Alert'],
      ['daily_report_notif', 'Daily Report Summary'],
      ['system_announcements_notif', 'System Announcements'],
    ];
    foreach($notif_types as [$key,$label]):
      $val = isset($settings_row[$key]) ? (int)$settings_row[$key] : 1;
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem 0;border-bottom:1px solid var(--border);">
      <div style="font-size:1.35rem;"><?=htmlspecialchars($label)?></div>
      <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;">
        <input type="checkbox" id="pref_<?=$key?>" <?=$val?'checked':''?> onchange="saveNotifPref('<?=$key?>',this.checked)" style="opacity:0;width:0;height:0;">
        <span id="toggle_<?=$key?>" style="position:absolute;inset:0;border-radius:24px;background:<?=$val?'var(--role-accent)':'var(--border)'?>;transition:.3s;"><span style="position:absolute;content:'';height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;transform:<?=$val?'translateX(20px)':'none'?>;display:block;"></span></span>
      </label>
    </div>
    <?php endforeach;?>
    </div>
  </div>
</div>
</div><!-- /profNotifPrefs -->
</div><!-- /sec-profile -->

<script>
function showProfileSection(id,btn){
  document.querySelectorAll('.prof-section').forEach(s=>s.style.display='none');
  document.getElementById(id).style.display='block';
  if(btn){document.querySelectorAll('.adm-tabs .adm-tab-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');}
}
function toggleEdit(formId){
  const form=document.getElementById(formId);
  const isEdit=[...form.querySelectorAll('.profile-field')][0]?.readOnly;
  form.querySelectorAll('.profile-field').forEach(f=>f.readOnly=!isEdit);
  const saveWrap=document.getElementById('savePersonalWrap');
  if(saveWrap) saveWrap.style.display=isEdit?'flex':'none';
  document.getElementById('editProfBtn').innerHTML=isEdit?'<i class="fas fa-xmark"></i> Cancel':'<i class="fas fa-pen"></i> Edit';
}
function cancelEditProfile(){ toggleEdit('profPersonalForm'); }
async function saveProfileSection(formId, action){
  const form=document.getElementById(formId);
  const fd=new FormData(form);
  const data={action};
  fd.forEach((v,k)=>data[k]=v);
  const d=await finAction(data);
  if(d.success){ toast('Profile updated!','success'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
function checkPwdStrength(pwd){
  const bar=document.getElementById('pwdStrengthBar');
  const lbl=document.getElementById('pwdStrengthLabel');
  let score=0;
  if(pwd.length>=8)score++;if(/[A-Z]/.test(pwd))score++;if(/\d/.test(pwd))score++;if(/[^A-Za-z0-9]/.test(pwd))score++;
  const colors=['#E74C3C','#E67E22','#F39C12','#27AE60'];
  const labels=['Weak','Fair','Good','Strong'];
  bar.style.width=(score*25)+'%';bar.style.background=colors[score-1]||'#E74C3C';
  lbl.textContent=score>0?labels[score-1]:'Very Weak';lbl.style.color=colors[score-1]||'#E74C3C';
}
async function submitPasswordChange(){
  const f=document.getElementById('formChangePassword');
  if(f.querySelector('[name=new_password]').value!==f.querySelector('[name=confirm_password]').value){ toast('Passwords do not match.','danger'); return; }
  const d=await finAction({action:'change_password',current_password:f.querySelector('[name=current_password]').value,new_password:f.querySelector('[name=new_password]').value});
  if(d.success){ toast('Password changed successfully!','success'); f.reset(); }
  else toast(d.message||'Error.','danger');
}
async function saveNotifPref(key,val){
  const toggle=document.getElementById('toggle_'+key);
  if(toggle){ toggle.style.background=val?'var(--role-accent)':'var(--border)'; }
  await finAction({action:'save_notif_pref',key,value:val?1:0});
}
async function uploadProfilePhoto(input){
  const file=input.files[0];
  if(!file) return;
  if(file.size>2*1024*1024){ toast('Photo must be under 2MB.','danger'); return; }
  const fd=new FormData();fd.append('action','upload_profile_photo');fd.append('photo',file);
  const res=await fetch('/RMU-Medical-Management-System/php/finance/finance_actions.php',{
      method:'POST',
      headers: { 'X-CSRF-Token': window.csrfToken },
      body:fd
  });
  const d=await res.json();
  if(d.success){ toast('Photo updated!','success'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Upload failed.','danger');
}
</script>
