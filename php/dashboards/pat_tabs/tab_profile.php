<?php
// MODULE 10: ADVANCED PATIENT PROFILE — Sections A-H
// Fetches all data server-side for initial render
$medProfile=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM patient_medical_profile WHERE patient_id=$pat_pk"))?? [];
$insurance=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM patient_insurance WHERE patient_id=$pat_pk"))?? [];
$patDocs=[];$q=mysqli_query($conn,"SELECT * FROM patient_documents WHERE patient_id=$pat_pk ORDER BY uploaded_at DESC");
if($q) while($r=mysqli_fetch_assoc($q)) $patDocs[]=$r;
$patSessions=[];$q=mysqli_query($conn,"SELECT * FROM patient_sessions WHERE patient_id=$pat_pk ORDER BY login_time DESC LIMIT 20");
if($q) while($r=mysqli_fetch_assoc($q)) $patSessions[]=$r;
$activityLog=[];$q=mysqli_query($conn,"SELECT * FROM patient_activity_log WHERE patient_id=$pat_pk ORDER BY created_at DESC LIMIT 30");
if($q) while($r=mysqli_fetch_assoc($q)) $activityLog[]=$r;
$profComp=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM patient_profile_completeness WHERE patient_id=$pat_pk"))?? [];
$patSettings=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM patient_settings WHERE patient_id=$pat_pk"))?? [];
$pctComplete=(int)($profComp['overall_percentage']??0);
// JSON decode helpers
$allergies=json_decode($medProfile['allergies']??'[]',true)??[];
$chronic=json_decode($medProfile['chronic_conditions']??'[]',true)??[];
$currentMeds=json_decode($medProfile['current_medications']??'[]',true)??[];
$vaccinations=json_decode($medProfile['vaccination_history']??'[]',true)??[];
$familyHx=json_decode($medProfile['family_medical_history']??'[]',true)??[];
// Age calc
$dob=$pat_row['date_of_birth']??'';
$age=$dob?floor((time()-strtotime($dob))/31557600):'—';
$memberSince=$pat_row['registration_date']??$pat_row['created_at']??'';
$lastLogin=$pat_row['last_login_at']??'';
?>
<div id="sec-profile" class="dash-section">
<!-- AJAX helper for profile actions -->
<script>
async function profAction(data,isFile=false){
  const opts={method:'POST'};
  if(isFile){opts.body=data;}else{opts.headers={'Content-Type':'application/json'};opts.body=JSON.stringify(data);}
  const r=await fetch('patient_profile_actions.php',opts);
  return r.json();
}
function calcBMI(){
  const h=parseFloat(document.getElementById('profHeight').value)||0;
  const w=parseFloat(document.getElementById('profWeight').value)||0;
  const el=document.getElementById('bmiDisplay');
  if(h>0&&w>0){const b=(w/((h/100)**2)).toFixed(1);let c='';
    if(b<18.5)c='Underweight';else if(b<25)c='Normal';else if(b<30)c='Overweight';else c='Obese';
    const colors={Underweight:'var(--info)',Normal:'var(--success)',Overweight:'var(--warning)',Obese:'var(--danger)'};
    el.innerHTML=`<strong style="font-size:2rem;">${b}</strong> <span class="adm-badge" style="background:${colors[c]};color:#fff;">${c}</span>`;
  }else{el.innerHTML='<span style="color:var(--text-muted);">Enter height & weight</span>';}
}
</script>

<!-- ═══ SECTION H: Profile Completeness Bar (top) ═══ -->
<div class="adm-card" style="margin-bottom:1.5rem;background:linear-gradient(135deg,rgba(142,68,173,.06),rgba(47,128,237,.04));">
  <div style="padding:1.5rem 2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
      <h3 style="margin:0;font-size:1.5rem;"><i class="fas fa-chart-line" style="color:var(--role-accent);margin-right:.5rem;"></i>Profile Completeness</h3>
      <span style="font-size:2rem;font-weight:800;color:var(--role-accent);"><?=$pctComplete?>%</span>
    </div>
    <div style="background:var(--border);border-radius:20px;height:12px;overflow:hidden;">
      <div style="width:<?=$pctComplete?>%;height:100%;border-radius:20px;background:linear-gradient(90deg,var(--role-accent),#2F80ED);transition:width .5s;"></div>
    </div>
    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem;font-size:1.15rem;">
      <?php $checks=[
        ['personal_info','Personal Info','fas fa-user'],['medical_profile','Medical Profile','fas fa-heartbeat'],
        ['emergency_contact','Emergency Contact','fas fa-phone'],['insurance_info','Insurance','fas fa-shield-alt'],
        ['photo_uploaded','Photo','fas fa-camera'],['security_setup','Security','fas fa-lock'],['documents_uploaded','Documents','fas fa-file']
      ]; foreach($checks as [$key,$label,$icon]):
        $done=(int)($profComp[$key]??0);
      ?>
      <span style="display:flex;align-items:center;gap:.3rem;color:<?=$done?'var(--success)':'var(--warning)'?>;">
        <i class="fas <?=$done?'fa-check-circle':'fa-exclamation-circle'?>"></i> <?=$label?>
      </span>
      <?php endforeach;?>
    </div>
  </div>
</div>

<!-- ═══ SECTION A: Profile Header / Identity Card ═══ -->
<div class="adm-card" style="margin-bottom:1.5rem;">
  <div style="padding:2rem;display:flex;gap:2rem;align-items:center;flex-wrap:wrap;">
    <!-- Photo -->
    <div style="text-align:center;">
      <div id="profAvatarWrap" style="width:120px;height:120px;border-radius:50%;overflow:hidden;border:4px solid var(--role-accent);margin:0 auto .8rem;display:flex;align-items:center;justify-content:center;background:var(--role-accent);color:#fff;font-size:3.5rem;">
        <?php $pimg=$pat_row['profile_image']??'';if(!empty($pimg)&&$pimg!=='default-avatar.png'):?>
        <img src="/RMU-Medical-Management-System/<?=htmlspecialchars($pimg)?>" style="width:100%;height:100%;object-fit:cover;" id="profAvatarImg">
        <?php else:?><span id="profAvatarInit"><?=strtoupper(substr($pat_row['name']??'P',0,1))?></span><?php endif;?>
      </div>
      <label class="adm-btn adm-btn-sm adm-btn-primary" style="cursor:pointer;">
        <i class="fas fa-camera"></i> Change
        <input type="file" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="uploadProfPhoto(this)">
      </label>
    </div>
    <!-- Identity -->
    <div style="flex:1;">
      <div style="display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;">
        <h2 style="margin:0;font-size:2rem;"><?=htmlspecialchars($pat_row['name']??'')?></h2>
        <span style="width:12px;height:12px;border-radius:50%;background:<?=($pat_row['is_online']??0)?'var(--success)':'var(--text-muted)'?>;"></span>
        <span style="font-size:1.1rem;color:var(--text-muted);"><?=($pat_row['is_online']??0)?'Online':'Offline'?></span>
      </div>
      <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:.8rem;font-size:1.25rem;color:var(--text-secondary);">
        <span><i class="fas fa-id-badge" style="color:var(--role-accent);"></i> <?=htmlspecialchars($pat_row['patient_id']??'—')?></span>
        <span><i class="fas fa-birthday-cake" style="color:var(--warning);"></i> <?=$age?> yrs</span>
        <span><i class="fas fa-venus-mars" style="color:var(--info);"></i> <?=htmlspecialchars($pat_row['gender']??'—')?></span>
        <span><i class="fas fa-tint" style="color:var(--danger);"></i> <?=htmlspecialchars($pat_row['blood_group']??'—')?></span>
      </div>
      <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:.6rem;font-size:1.1rem;color:var(--text-muted);">
        <?php if($memberSince):?><span><i class="fas fa-calendar-alt"></i> Member since <?=date('M Y',strtotime($memberSince))?></span><?php endif;?>
        <?php if($lastLogin):?><span><i class="fas fa-clock"></i> Last login: <?=date('d M Y, g:i A',strtotime($lastLogin))?></span><?php endif;?>
      </div>
    </div>
  </div>
</div>

<!-- ═══ Profile Sub-Tabs ═══ -->
<div class="filter-tabs" style="margin-bottom:1.5rem;" id="profSubTabs">
  <span class="ftab active" onclick="showProfSec('personal',this)"><i class="fas fa-user"></i> Personal</span>
  <span class="ftab" onclick="showProfSec('medical',this)"><i class="fas fa-heartbeat"></i> Medical</span>
  <span class="ftab" onclick="showProfSec('insurance',this)"><i class="fas fa-shield-alt"></i> Insurance</span>
  <span class="ftab" onclick="showProfSec('security',this)"><i class="fas fa-lock"></i> Security</span>
  <span class="ftab" onclick="showProfSec('notifprefs',this)"><i class="fas fa-bell"></i> Notifications</span>
  <span class="ftab" onclick="showProfSec('documents',this)"><i class="fas fa-file-upload"></i> Documents</span>
</div>

<!-- ═══ SECTION B: Personal Information ═══ -->
<div class="prof-sec" id="profSec-personal">
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-user-edit" style="color:var(--primary);"></i> Personal Information</h3></div>
  <div style="padding:2rem;">
    <form onsubmit="savePersonalInfo(event)">
      <div class="form-row">
        <div class="form-group"><label>Full Name *</label><input type="text" name="name" class="form-control" value="<?=htmlspecialchars($pat_row['name']??'')?>" required></div>
        <div class="form-group"><label>Date of Birth *</label><input type="date" name="date_of_birth" class="form-control" value="<?=$pat_row['date_of_birth']??''?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Gender *</label><select name="gender" class="form-control"><?php foreach(['Male','Female','Other'] as $g):?><option <?=($pat_row['gender']??'')===$g?'selected':''?>><?=$g?></option><?php endforeach;?></select></div>
        <div class="form-group"><label>Marital Status</label><select name="marital_status" class="form-control"><option value="">Select...</option><?php foreach(['Single','Married','Divorced','Widowed'] as $ms):?><option <?=($pat_row['marital_status']??'')===$ms?'selected':''?>><?=$ms?></option><?php endforeach;?></select></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Nationality</label><input type="text" name="nationality" class="form-control" value="<?=htmlspecialchars($pat_row['nationality']??'')?>"></div>
        <div class="form-group"><label>Religion</label><input type="text" name="religion" class="form-control" value="<?=htmlspecialchars($pat_row['religion']??'')?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Occupation</label><input type="text" name="occupation" class="form-control" value="<?=htmlspecialchars($pat_row['occupation']??'')?>"></div>
        <div class="form-group"><label>National ID / Passport</label><input type="text" name="national_id" class="form-control" value="<?=htmlspecialchars($pat_row['national_id']??'')?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Primary Phone *</label><input type="tel" name="phone" class="form-control" value="<?=htmlspecialchars($pat_row['phone']??'')?>" required></div>
        <div class="form-group"><label>Secondary Phone</label><input type="tel" name="secondary_phone" class="form-control" value="<?=htmlspecialchars($pat_row['secondary_phone']??'')?>"></div>
      </div>
      <div class="form-group"><label>Email Address</label><input type="email" name="email" class="form-control" value="<?=htmlspecialchars($pat_row['email']??'')?>"></div>
      <h4 style="margin:1.5rem 0 .8rem;color:var(--text-secondary);"><i class="fas fa-map-marker-alt" style="color:var(--role-accent);"></i> Address</h4>
      <div class="form-group"><label>Street Address</label><input type="text" name="street_address" class="form-control" value="<?=htmlspecialchars($pat_row['street_address']??'')?>"></div>
      <div class="form-row">
        <div class="form-group"><label>City</label><input type="text" name="city" class="form-control" value="<?=htmlspecialchars($pat_row['city']??'')?>"></div>
        <div class="form-group"><label>Region / State</label><input type="text" name="region" class="form-control" value="<?=htmlspecialchars($pat_row['region']??'')?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Country</label><input type="text" name="country" class="form-control" value="<?=htmlspecialchars($pat_row['country']??'Ghana')?>"></div>
        <div class="form-group"><label>Postal Code</label><input type="text" name="postal_code" class="form-control" value="<?=htmlspecialchars($pat_row['postal_code']??'')?>"></div>
      </div>
      <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.2rem;"><i class="fas fa-save"></i> Save Personal Information</button>
    </form>
  </div>
</div>
</div>

<!-- ═══ SECTION C: Medical Profile ═══ -->
<div class="prof-sec" id="profSec-medical" style="display:none;">
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-heartbeat" style="color:var(--danger);"></i> Medical Profile</h3></div>
  <div style="padding:2rem;">
    <form onsubmit="saveMedicalProfile(event)" id="medProfileForm">
      <div class="form-row">
        <div class="form-group"><label>Blood Type</label><select name="blood_type" class="form-control"><?php foreach(['','A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt):?><option value="<?=$bt?>" <?=($medProfile['blood_type']??$pat_row['blood_group']??'')===$bt?'selected':''?>><?=$bt?:' Select...'?></option><?php endforeach;?></select></div>
        <div class="form-group"><label>Height (cm)</label><input type="number" step="0.1" name="height_cm" id="profHeight" class="form-control" value="<?=$medProfile['height_cm']??''?>" oninput="calcBMI()"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Weight (kg)</label><input type="number" step="0.1" name="weight_kg" id="profWeight" class="form-control" value="<?=$medProfile['weight_kg']??''?>" oninput="calcBMI()"></div>
        <div class="form-group"><label>BMI</label><div id="bmiDisplay" style="padding:1rem;background:var(--surface-2);border-radius:8px;min-height:50px;display:flex;align-items:center;gap:.5rem;">
          <?php if(($medProfile['bmi']??0)>0):$bc=['Underweight'=>'var(--info)','Normal'=>'var(--success)','Overweight'=>'var(--warning)','Obese'=>'var(--danger)'];?><strong style="font-size:2rem;"><?=$medProfile['bmi']?></strong> <span class="adm-badge" style="background:<?=$bc[$medProfile['bmi_category']]??'var(--primary)'?>;color:#fff;"><?=$medProfile['bmi_category']?></span><?php else:?><span style="color:var(--text-muted);">Enter height & weight</span><?php endif;?>
        </div></div>
      </div>
      <!-- Allergies -->
      <div class="form-group"><label><i class="fas fa-allergies" style="color:var(--danger);"></i> Known Allergies</label>
        <div id="allergyList"><?php foreach($allergies as $i=>$a):?><div class="arr-item" style="display:flex;gap:.5rem;margin-bottom:.5rem;"><input type="text" name="allergies[<?=$i?>][name]" class="form-control" value="<?=htmlspecialchars($a['name']??'')?>" placeholder="Allergy" style="flex:2;"><select name="allergies[<?=$i?>][type]" class="form-control" style="flex:1;"><?php foreach(['Drug','Food','Environmental','Other'] as $t):?><option <?=($a['type']??'')===$t?'selected':''?>><?=$t?></option><?php endforeach;?></select><select name="allergies[<?=$i?>][severity]" class="form-control" style="flex:1;"><?php foreach(['Mild','Moderate','Severe'] as $s):?><option <?=($a['severity']??'')===$s?'selected':''?>><?=$s?></option><?php endforeach;?></select><button type="button" class="adm-btn adm-btn-danger adm-btn-sm" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button></div><?php endforeach;?></div>
        <button type="button" class="adm-btn adm-btn-sm" onclick="addArrItem('allergyList','allergies',['name|text|Allergy','type|select|Drug,Food,Environmental,Other','severity|select|Mild,Moderate,Severe'])"><i class="fas fa-plus"></i> Add Allergy</button>
      </div>
      <!-- Chronic Conditions -->
      <div class="form-group"><label><i class="fas fa-lungs" style="color:var(--warning);"></i> Chronic Conditions</label>
        <div id="chronicList"><?php foreach($chronic as $i=>$c):?><div class="arr-item" style="display:flex;gap:.5rem;margin-bottom:.5rem;"><input type="text" name="chronic_conditions[<?=$i?>][condition]" class="form-control" value="<?=htmlspecialchars($c['condition']??$c??'')?>" placeholder="Condition"><button type="button" class="adm-btn adm-btn-danger adm-btn-sm" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button></div><?php endforeach;?></div>
        <button type="button" class="adm-btn adm-btn-sm" onclick="addSimpleItem('chronicList','chronic_conditions','Condition')"><i class="fas fa-plus"></i> Add Condition</button>
      </div>
      <!-- Disabilities -->
      <div class="form-group"><label>Disabilities / Special Needs</label><textarea name="disabilities" class="form-control" rows="2"><?=htmlspecialchars($medProfile['disabilities']??'')?></textarea></div>
      <!-- Current Medications -->
      <div class="form-group"><label><i class="fas fa-capsules" style="color:var(--info);"></i> Current Medications (outside system)</label>
        <div id="medsList"><?php foreach($currentMeds as $i=>$m):?><div class="arr-item" style="display:flex;gap:.5rem;margin-bottom:.5rem;"><input type="text" name="current_medications[<?=$i?>][name]" class="form-control" value="<?=htmlspecialchars($m['name']??$m??'')?>" placeholder="Medication" style="flex:2;"><input type="text" name="current_medications[<?=$i?>][dosage]" class="form-control" value="<?=htmlspecialchars($m['dosage']??'')?>" placeholder="Dosage" style="flex:1;"><button type="button" class="adm-btn adm-btn-danger adm-btn-sm" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button></div><?php endforeach;?></div>
        <button type="button" class="adm-btn adm-btn-sm" onclick="addMedItem()"><i class="fas fa-plus"></i> Add Medication</button>
      </div>
      <!-- Vaccination History -->
      <div class="form-group"><label><i class="fas fa-syringe" style="color:var(--success);"></i> Vaccination History</label>
        <div id="vaccList"><?php foreach($vaccinations as $i=>$v):?><div class="arr-item" style="display:flex;gap:.5rem;margin-bottom:.5rem;"><input type="text" name="vaccination_history[<?=$i?>][vaccine]" class="form-control" value="<?=htmlspecialchars($v['vaccine']??'')?>" placeholder="Vaccine" style="flex:2;"><input type="date" name="vaccination_history[<?=$i?>][date]" class="form-control" value="<?=htmlspecialchars($v['date']??'')?>" style="flex:1;"><button type="button" class="adm-btn adm-btn-danger adm-btn-sm" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button></div><?php endforeach;?></div>
        <button type="button" class="adm-btn adm-btn-sm" onclick="addVaccItem()"><i class="fas fa-plus"></i> Add Vaccine</button>
      </div>
      <!-- Family Medical History -->
      <div class="form-group"><label><i class="fas fa-people-group" style="color:var(--role-accent);"></i> Family Medical History</label>
        <div id="famList"><?php foreach($familyHx as $i=>$f):?><div class="arr-item" style="display:flex;gap:.5rem;margin-bottom:.5rem;"><input type="text" name="family_medical_history[<?=$i?>][relation]" class="form-control" value="<?=htmlspecialchars($f['relation']??'')?>" placeholder="Relation" style="flex:1;"><input type="text" name="family_medical_history[<?=$i?>][condition]" class="form-control" value="<?=htmlspecialchars($f['condition']??'')?>" placeholder="Condition" style="flex:2;"><button type="button" class="adm-btn adm-btn-danger adm-btn-sm" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button></div><?php endforeach;?></div>
        <button type="button" class="adm-btn adm-btn-sm" onclick="addFamItem()"><i class="fas fa-plus"></i> Add Entry</button>
      </div>
      <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.2rem;"><i class="fas fa-save"></i> Save Medical Profile</button>
    </form>
  </div>
</div>
</div>

<!-- ═══ SECTION D: Insurance & Payment ═══ -->
<div class="prof-sec" id="profSec-insurance" style="display:none;">
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-shield-alt" style="color:var(--info);"></i> Insurance & Payment Information</h3></div>
  <div style="padding:2rem;">
    <?php $expSoon=($insurance['expiry_date']??false)&&strtotime($insurance['expiry_date'])<=strtotime('+30 days')&&strtotime($insurance['expiry_date'])>=strtotime('today');?>
    <?php if($expSoon):?><div style="background:var(--warning-light);color:var(--warning);border-radius:10px;padding:1rem;margin-bottom:1.5rem;font-size:1.2rem;"><i class="fas fa-exclamation-triangle"></i> Your insurance expires on <?=date('d M Y',strtotime($insurance['expiry_date']))?>. Please renew soon.</div><?php endif;?>
    <form onsubmit="saveInsurance(event)">
      <div class="form-row">
        <div class="form-group"><label>Insurance Provider</label><input type="text" name="provider_name" class="form-control" value="<?=htmlspecialchars($insurance['provider_name']??'')?>"></div>
        <div class="form-group"><label>Policy Number</label><input type="text" name="policy_number" class="form-control" value="<?=htmlspecialchars($insurance['policy_number']??'')?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date" class="form-control" value="<?=$insurance['expiry_date']??''?>"></div>
        <div class="form-group"><label>Coverage Type</label><select name="coverage_type" class="form-control"><?php foreach(['Individual','Family'] as $ct):?><option <?=($insurance['coverage_type']??'')===$ct?'selected':''?>><?=$ct?></option><?php endforeach;?></select></div>
      </div>
      <div class="form-group"><label>Payment Preference</label><select name="payment_preference" class="form-control"><?php foreach(['Cash','Insurance','Mobile Money'] as $pp):?><option <?=($insurance['payment_preference']??'')===$pp?'selected':''?>><?=$pp?></option><?php endforeach;?></select></div>
      <div class="form-group"><label>Billing Address</label><textarea name="billing_address" class="form-control" rows="2" placeholder="Leave blank to use residential address"><?=htmlspecialchars($insurance['billing_address']??'')?></textarea></div>
      <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.2rem;"><i class="fas fa-save"></i> Save Insurance Info</button>
    </form>
  </div>
</div>
</div>

<!-- ═══ SECTION E: Account & Security ═══ -->
<div class="prof-sec" id="profSec-security" style="display:none;">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
  <!-- Change Password -->
  <div class="adm-card" style="margin:0;">
    <div class="adm-card-header"><h3><i class="fas fa-key" style="color:var(--warning);"></i> Change Password</h3></div>
    <div style="padding:2rem;">
      <form onsubmit="changePwdProfile(event)">
        <div class="form-group"><label>Current Password</label><input type="password" name="current_password" class="form-control" required></div>
        <div class="form-group"><label>New Password</label><input type="password" name="new_password" class="form-control" required minlength="8" oninput="profPwdStr(this)"><div id="profPwdMeter" style="margin-top:.4rem;font-size:1.1rem;font-weight:600;"></div></div>
        <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
        <button type="submit" class="adm-btn adm-btn-warning" style="width:100%;justify-content:center;"><i class="fas fa-key"></i> Update Password</button>
      </form>
    </div>
  </div>
  <!-- 2FA + Deactivation -->
  <div style="display:flex;flex-direction:column;gap:1.5rem;">
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3><i class="fas fa-shield-halved" style="color:var(--success);"></i> Two-Factor Auth</h3></div>
      <div style="padding:1.5rem;display:flex;justify-content:space-between;align-items:center;">
        <div><div style="font-weight:600;font-size:1.3rem;">2FA Status</div><div style="font-size:1.1rem;color:var(--text-muted);">Extra security layer</div></div>
        <label style="position:relative;width:50px;height:28px;cursor:pointer;"><input type="checkbox" id="toggle2fa" <?=($patSettings['two_factor_enabled']??0)?'checked':''?> onchange="toggle2FA(this.checked)" style="opacity:0;width:0;height:0;position:absolute;"><span class="notif-slider"></span></label>
      </div>
    </div>
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3 style="color:var(--danger);"><i class="fas fa-user-slash"></i> Deactivate Account</h3></div>
      <div style="padding:1.5rem;">
        <p style="font-size:1.2rem;color:var(--text-muted);margin-bottom:1rem;">Request account deactivation. Admin will review your request.</p>
        <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="requestDeactivation()"><i class="fas fa-power-off"></i> Request Deactivation</button>
      </div>
    </div>
  </div>
</div>
<!-- Active Sessions -->
<div class="adm-card" style="margin-top:1.5rem;">
  <div class="adm-card-header"><h3><i class="fas fa-desktop" style="color:var(--info);"></i> Active Sessions</h3><button class="adm-btn adm-btn-danger adm-btn-sm" onclick="logoutAllSessions()"><i class="fas fa-sign-out-alt"></i> Logout All Others</button></div>
  <div class="adm-table-wrap" style="padding:0 .5rem;">
    <table class="adm-table"><thead><tr><th>Device / Browser</th><th>IP Address</th><th>Login Time</th><th>Last Active</th><th>Action</th></tr></thead>
    <tbody id="sessionsBody">
      <?php if(empty($patSessions)):?><tr><td colspan="5" style="text-align:center;color:var(--text-muted);">No sessions</td></tr>
      <?php else: foreach($patSessions as $s):?>
      <tr><td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($s['device_info']??$s['browser']??'—')?></td><td><?=htmlspecialchars($s['ip_address']??'—')?></td><td><?=date('d M, g:i A',strtotime($s['login_time']))?></td><td><?=$s['last_active']?date('g:i A',strtotime($s['last_active'])):'—'?></td>
      <td><?php if($s['is_current']):?><span class="adm-badge adm-badge-success">Current</span><?php else:?><button class="adm-btn adm-btn-danger adm-btn-sm" onclick="logoutSession(<?=$s['id']?>)"><i class="fas fa-sign-out-alt"></i></button><?php endif;?></td></tr>
      <?php endforeach; endif;?>
    </tbody></table>
  </div>
</div>
<!-- Activity Log -->
<div class="adm-card" style="margin-top:1.5rem;">
  <div class="adm-card-header"><h3><i class="fas fa-clipboard-list" style="color:var(--role-accent);"></i> Activity Log</h3></div>
  <div class="adm-table-wrap" style="padding:0 .5rem;">
    <table class="adm-table"><thead><tr><th>Action</th><th>Description</th><th>IP</th><th>Time</th></tr></thead>
    <tbody>
      <?php if(empty($activityLog)):?><tr><td colspan="4" style="text-align:center;color:var(--text-muted);">No activity</td></tr>
      <?php else: foreach($activityLog as $al):?>
      <tr><td><span class="adm-badge adm-badge-info"><?=htmlspecialchars($al['action_type'])?></span></td><td><?=htmlspecialchars($al['action_description'])?></td><td><?=htmlspecialchars($al['ip_address']??'—')?></td><td><?=date('d M, g:i A',strtotime($al['created_at']))?></td></tr>
      <?php endforeach; endif;?>
    </tbody></table>
  </div>
</div>
</div>

<!-- ═══ SECTION F: Notification Preferences ═══ -->
<div class="prof-sec" id="profSec-notifprefs" style="display:none;">
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-bell" style="color:var(--warning);"></i> Notification Preferences</h3></div>
  <div style="padding:1.5rem;">
    <form onsubmit="saveNotifPrefs(event)">
      <?php $ntogs=[
        ['appointment_reminders','Appointment Reminders','24 hours before appointment'],
        ['email_notifications','Appointment Status Updates','Approved / Rescheduled / Cancelled'],
        ['prescription_alerts','Prescription Alerts','New prescriptions and refill updates'],
        ['lab_result_alerts','Lab Result Alerts','When results are available'],
        ['medical_record_alerts','Medical Record Updates','New records added'],
        ['emergency_contact_alerts','Emergency Contact Confirmations','Changes to your contacts'],
        ['system_announcements','System Announcements','General system updates'],
        ['sms_notifications','SMS Notifications','Receive SMS alerts'],
      ]; foreach($ntogs as [$key,$label,$desc]):$checked=($patSettings[$key]??1)?'checked':'';?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:.8rem 0;border-bottom:1px solid var(--border);">
        <div><div style="font-weight:600;font-size:1.25rem;"><?=$label?></div><div style="font-size:1.05rem;color:var(--text-muted);"><?=$desc?></div></div>
        <label style="position:relative;width:42px;height:24px;cursor:pointer;"><input type="checkbox" name="<?=$key?>" value="1" <?=$checked?> style="opacity:0;width:0;height:0;position:absolute;"><span class="notif-slider"></span></label>
      </div>
      <?php endforeach;?>
      <div style="margin-top:1.5rem;" class="form-row">
        <div class="form-group"><label>Preferred Channel</label><select name="preferred_channel" class="form-control"><?php foreach(['dashboard'=>'In-Dashboard','email'=>'Email','sms'=>'SMS','all'=>'All Channels'] as $v=>$l):?><option value="<?=$v?>" <?=($patSettings['preferred_channel']??'dashboard')===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></div>
        <div class="form-group"><label>Language</label><select name="language_preference" class="form-control"><?php foreach(['English','French','Twi','Ga','Ewe'] as $l):?><option <?=($patSettings['language_preference']??'English')===$l?'selected':''?>><?=$l?></option><?php endforeach;?></select></div>
      </div>
      <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.2rem;"><i class="fas fa-save"></i> Save Preferences</button>
    </form>
  </div>
</div>
</div>

<!-- ═══ SECTION G: Documents & Uploads ═══ -->
<div class="prof-sec" id="profSec-documents" style="display:none;">
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-file-upload" style="color:var(--role-accent);"></i> Documents & Uploads</h3>
    <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="document.getElementById('docUploadForm').style.display='block'"><i class="fas fa-plus"></i> Upload</button>
  </div>
  <div style="padding:1.5rem;">
    <!-- Upload Form -->
    <div id="docUploadForm" style="display:none;background:var(--surface-2);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
      <form onsubmit="uploadDocument(event)">
        <div class="form-row">
          <div class="form-group"><label>Category</label><select name="category" class="form-control"><option>Medical Report</option><option>Insurance Card</option><option>National ID</option><option>Passport</option><option>Lab Report</option><option>Other</option></select></div>
          <div class="form-group"><label>Description</label><input type="text" name="description" class="form-control" placeholder="Brief description..."></div>
        </div>
        <div class="form-group"><label>File (PDF, JPG, PNG, DOCX — max 5MB)</label><input type="file" name="document" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"></div>
        <div style="display:flex;gap:.8rem;"><button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-upload"></i> Upload</button><button type="button" class="adm-btn" onclick="this.closest('#docUploadForm').style.display='none'">Cancel</button></div>
      </form>
    </div>
    <!-- Document List -->
    <div id="docListWrap">
      <?php if(empty($patDocs)):?><div style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-folder-open" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:1rem;"></i><p>No documents uploaded</p></div>
      <?php else:?>
      <table class="adm-table"><thead><tr><th>File</th><th>Category</th><th>Size</th><th>Date</th><th>Actions</th></tr></thead><tbody>
        <?php foreach($patDocs as $d): $sizeKB=round($d['file_size']/1024,1);?>
        <tr data-docid="<?=$d['id']?>">
          <td><div style="font-weight:600;"><?=htmlspecialchars($d['file_name'])?></div><?php if($d['description']):?><div style="font-size:1.05rem;color:var(--text-muted);"><?=htmlspecialchars($d['description'])?></div><?php endif;?></td>
          <td><span class="adm-badge adm-badge-info"><?=htmlspecialchars($d['document_category']??'Other')?></span></td>
          <td><?=$sizeKB?> KB</td>
          <td><?=date('d M Y',strtotime($d['uploaded_at']))?></td>
          <td><div style="display:flex;gap:.4rem;"><a href="/RMU-Medical-Management-System/<?=htmlspecialchars($d['file_path'])?>" target="_blank" class="adm-btn adm-btn-sm" title="Download"><i class="fas fa-download"></i></a><button class="adm-btn adm-btn-danger adm-btn-sm" onclick="deleteDoc(<?=$d['id']?>)" title="Delete"><i class="fas fa-trash"></i></button></div></td>
        </tr>
        <?php endforeach;?>
      </tbody></table>
      <?php endif;?>
    </div>
  </div>
</div>
</div>

</div><!-- end sec-profile -->

<script>
// Sub-tab navigation
function showProfSec(sec,btn){
  document.querySelectorAll('.prof-sec').forEach(s=>s.style.display='none');
  document.getElementById('profSec-'+sec).style.display='block';
  document.querySelectorAll('#profSubTabs .ftab').forEach(f=>f.classList.remove('active'));
  if(btn) btn.classList.add('active');
}
// Photo upload
async function uploadProfPhoto(input){
  if(!input.files[0])return;const fd=new FormData();fd.append('action','upload_profile_photo');fd.append('photo',input.files[0]);
  const r=await profAction(fd,true);
  if(r.success){toast('Photo updated!');document.getElementById('profAvatarWrap').innerHTML=`<img src="${r.photo_url}" style="width:100%;height:100%;object-fit:cover;">`;}else toast(r.message||'Error','danger');
}
// Personal info
async function savePersonalInfo(e){
  e.preventDefault();const fd=new FormData(e.target),data={action:'update_personal_info'};
  fd.forEach((v,k)=>data[k]=v);
  const r=await profAction(data);r.success?toast('Personal info saved!'):toast(r.message||'Error','danger');
}
// Medical profile — collect arrays from form
async function saveMedicalProfile(e){
  e.preventDefault();
  const data={action:'update_medical_profile'};
  const form=e.target;
  data.blood_type=form.querySelector('[name=blood_type]').value;
  data.height_cm=form.querySelector('[name=height_cm]').value;
  data.weight_kg=form.querySelector('[name=weight_kg]').value;
  data.disabilities=form.querySelector('[name=disabilities]').value;
  // Collect arrays
  data.allergies=[];document.querySelectorAll('#allergyList .arr-item').forEach(el=>{
    const n=el.querySelector('[name*="[name]"]');const t=el.querySelector('[name*="[type]"]');const s=el.querySelector('[name*="[severity]"]');
    if(n&&n.value) data.allergies.push({name:n.value,type:t?.value||'',severity:s?.value||''});
  });
  data.chronic_conditions=[];document.querySelectorAll('#chronicList .arr-item').forEach(el=>{
    const inp=el.querySelector('input');if(inp&&inp.value) data.chronic_conditions.push({condition:inp.value});
  });
  data.current_medications=[];document.querySelectorAll('#medsList .arr-item').forEach(el=>{
    const inputs=el.querySelectorAll('input');if(inputs[0]&&inputs[0].value) data.current_medications.push({name:inputs[0].value,dosage:inputs[1]?.value||''});
  });
  data.vaccination_history=[];document.querySelectorAll('#vaccList .arr-item').forEach(el=>{
    const inputs=el.querySelectorAll('input');if(inputs[0]&&inputs[0].value) data.vaccination_history.push({vaccine:inputs[0].value,date:inputs[1]?.value||''});
  });
  data.family_medical_history=[];document.querySelectorAll('#famList .arr-item').forEach(el=>{
    const inputs=el.querySelectorAll('input');if(inputs[0]&&inputs[0].value) data.family_medical_history.push({relation:inputs[0].value,condition:inputs[1]?.value||''});
  });
  const r=await profAction(data);
  if(r.success){toast('Medical profile saved!');if(r.bmi){const bc={Underweight:'var(--info)',Normal:'var(--success)',Overweight:'var(--warning)',Obese:'var(--danger)'};document.getElementById('bmiDisplay').innerHTML=`<strong style="font-size:2rem;">${r.bmi}</strong> <span class="adm-badge" style="background:${bc[r.bmi_category]};color:#fff;">${r.bmi_category}</span>`;}}
  else toast(r.message||'Error','danger');
}
// Insurance
async function saveInsurance(e){e.preventDefault();const fd=new FormData(e.target),data={action:'update_insurance'};fd.forEach((v,k)=>data[k]=v);const r=await profAction(data);r.success?toast('Insurance saved!'):toast(r.message||'Error','danger');}
// Password
function profPwdStr(inp){const v=inp.value,el=document.getElementById('profPwdMeter');let s=0;if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;const l=['','Weak','Fair','Strong','Very Strong'],c=['','var(--danger)','var(--warning)','var(--info)','var(--success)'];el.textContent=l[s]||'';el.style.color=c[s]||'';}
async function changePwdProfile(e){e.preventDefault();const fd=new FormData(e.target),data={action:'change_password_profile'};fd.forEach((v,k)=>data[k]=v);const r=await profAction(data);if(r.success){toast('Password changed!');e.target.reset();document.getElementById('profPwdMeter').textContent='';}else toast(r.message||'Error','danger');}
// 2FA
async function toggle2FA(on){const r=await profAction({action:'toggle_2fa',enabled:on?1:0});r.success?toast('2FA '+(on?'enabled':'disabled')):toast(r.message||'Error','danger');}
// Sessions
async function logoutSession(id){const r=await profAction({action:'logout_session',session_id:id});if(r.success){toast('Session terminated');document.querySelector(`#sessionsBody tr:has(button[onclick*="${id}"])`)?.remove();}else toast(r.message||'Error','danger');}
async function logoutAllSessions(){if(!confirm('Log out all other sessions?'))return;const r=await profAction({action:'logout_all_sessions'});r.success?toast('All other sessions terminated'):toast(r.message||'Error','danger');}
// Deactivation
async function requestDeactivation(){const reason=prompt('Reason for deactivation:');if(!reason)return;const r=await profAction({action:'request_deactivation',reason});r.success?toast('Deactivation request submitted'):toast(r.message||'Error','danger');}
// Notification Preferences
async function saveNotifPrefs(e){e.preventDefault();const fd=new FormData(e.target),data={action:'update_notification_prefs'};
  ['appointment_reminders','email_notifications','prescription_alerts','lab_result_alerts','medical_record_alerts','emergency_contact_alerts','system_announcements','sms_notifications'].forEach(k=>data[k]=fd.has(k)?1:0);
  data.preferred_channel=fd.get('preferred_channel');data.language_preference=fd.get('language_preference');
  const r=await profAction(data);r.success?toast('Preferences saved!'):toast(r.message||'Error','danger');}
// Documents
async function uploadDocument(e){e.preventDefault();const fd=new FormData(e.target);fd.append('action','upload_document');const r=await profAction(fd,true);if(r.success){toast('Document uploaded!');location.reload();}else toast(r.message||'Error','danger');}
async function deleteDoc(id){if(!confirm('Delete this document?'))return;const r=await profAction({action:'delete_document',doc_id:id});if(r.success){toast('Deleted');document.querySelector(`tr[data-docid="${id}"]`)?.remove();}else toast(r.message||'Error','danger');}
// Dynamic array helpers
let arrIdx=100;
function addArrItem(listId,fieldName,fields){
  const i=arrIdx++;const div=document.createElement('div');div.className='arr-item';div.style='display:flex;gap:.5rem;margin-bottom:.5rem;';
  let html='';fields.forEach(f=>{const[n,type,opts]=f.split('|');
    if(type==='select'){html+=`<select name="${fieldName}[${i}][${n}]" class="form-control" style="flex:1;">${opts.split(',').map(o=>`<option>${o}</option>`).join('')}</select>`;}
    else{html+=`<input type="text" name="${fieldName}[${i}][${n}]" class="form-control" placeholder="${opts||n}" style="flex:2;">`;}
  });
  html+=`<button type="button" class="adm-btn adm-btn-danger adm-btn-sm" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
  div.innerHTML=html;document.getElementById(listId).appendChild(div);
}
function addSimpleItem(listId,fieldName,ph){const i=arrIdx++;const div=document.createElement('div');div.className='arr-item';div.style='display:flex;gap:.5rem;margin-bottom:.5rem;';div.innerHTML=`<input type="text" name="${fieldName}[${i}][condition]" class="form-control" placeholder="${ph}"><button type="button" class="adm-btn adm-btn-danger adm-btn-sm" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;document.getElementById(listId).appendChild(div);}
function addMedItem(){const i=arrIdx++;const div=document.createElement('div');div.className='arr-item';div.style='display:flex;gap:.5rem;margin-bottom:.5rem;';div.innerHTML=`<input type="text" name="current_medications[${i}][name]" class="form-control" placeholder="Medication" style="flex:2;"><input type="text" name="current_medications[${i}][dosage]" class="form-control" placeholder="Dosage" style="flex:1;"><button type="button" class="adm-btn adm-btn-danger adm-btn-sm" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;document.getElementById('medsList').appendChild(div);}
function addVaccItem(){const i=arrIdx++;const div=document.createElement('div');div.className='arr-item';div.style='display:flex;gap:.5rem;margin-bottom:.5rem;';div.innerHTML=`<input type="text" name="vaccination_history[${i}][vaccine]" class="form-control" placeholder="Vaccine" style="flex:2;"><input type="date" name="vaccination_history[${i}][date]" class="form-control" style="flex:1;"><button type="button" class="adm-btn adm-btn-danger adm-btn-sm" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;document.getElementById('vaccList').appendChild(div);}
function addFamItem(){const i=arrIdx++;const div=document.createElement('div');div.className='arr-item';div.style='display:flex;gap:.5rem;margin-bottom:.5rem;';div.innerHTML=`<input type="text" name="family_medical_history[${i}][relation]" class="form-control" placeholder="Relation" style="flex:1;"><input type="text" name="family_medical_history[${i}][condition]" class="form-control" placeholder="Condition" style="flex:2;"><button type="button" class="adm-btn adm-btn-danger adm-btn-sm" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;document.getElementById('famList').appendChild(div);}
</script>
