<!-- ═══════════════════════════════════════════════════════════
     MODULE 15: ADVANCED NURSE PROFILE — tab_profile.php
     Sections A-J: Full comprehensive profile
     ═══════════════════════════════════════════════════════════ -->
<?php
// ── Data Queries ──
$prof = dbRow($conn,"SELECT * FROM nurse_professional_profile WHERE nurse_id=?","i",[$nurse_pk]);
$nurse_certs = dbSelect($conn,"SELECT * FROM nurse_certifications WHERE nurse_id=? ORDER BY created_at DESC","i",[$nurse_pk]);
$nurse_quals = dbSelect($conn,"SELECT * FROM nurse_qualifications WHERE nurse_id=? ORDER BY year DESC","i",[$nurse_pk]);
$nurse_docs  = dbSelect($conn,"SELECT * FROM nurse_documents WHERE nurse_id=? ORDER BY uploaded_at DESC","i",[$nurse_pk]);
$nurse_sess  = dbSelect($conn,"SELECT * FROM nurse_sessions WHERE nurse_id=? ORDER BY login_time DESC LIMIT 10","i",[$nurse_pk]);
$nurse_log   = dbSelect($conn,"SELECT * FROM nurse_activity_log WHERE nurse_id=? ORDER BY created_at DESC LIMIT 20","i",[$nurse_pk]);
$completeness= dbRow($conn,"SELECT * FROM nurse_profile_completeness WHERE nurse_id=?","i",[$nurse_pk]);
$settings    = dbRow($conn,"SELECT * FROM nurse_settings WHERE nurse_id=?","i",[$nurse_pk]);
$shifts7     = dbSelect($conn,"SELECT * FROM nurse_shifts WHERE nurse_id=? AND shift_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) ORDER BY shift_date","i",[$nurse_pk]);
$today_shift = dbRow($conn,"SELECT * FROM nurse_shifts WHERE nurse_id=? AND shift_date=CURDATE() LIMIT 1","i",[$nurse_pk]);
$departments = dbSelect($conn,"SELECT * FROM departments ORDER BY name");

$license_expiry = $nurse_row['license_expiry'] ?? $prof['license_expiry_date'] ?? '';
$license_days = $license_expiry ? (int)((strtotime($license_expiry)-time())/86400) : 999;
$license_badge = $license_days<=0 ? '<span class="badge badge-danger">Expired</span>' :
                ($license_days<=60 ? '<span class="badge badge-warning">Expiring in '.$license_days.'d</span>' :
                '<span class="badge badge-success">Valid</span>');
$profile_pct = (int)($completeness['completeness_percentage']??0);
$avail = e($nurse_row['availability_status']??'Available');
$avail_colors = ['Available'=>'success','Busy'=>'warning','On Break'=>'info','Off Duty'=>'secondary'];
$avail_color = $avail_colors[$avail] ?? 'secondary';
?>
<div id="sec-profile" class="dash-section">
<style>
.profile-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem;}
.profile-section h3{font-size:1.5rem;font-weight:700;margin:0 0 1.2rem;display:flex;align-items:center;gap:.6rem;}
.profile-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.2rem;}
.profile-grid.two{grid-template-columns:1fr 1fr;}
.pf-label{font-size:1.05rem;color:var(--text-muted);margin-bottom:.2rem;}
.pf-value{font-size:1.25rem;font-weight:500;}
.pf-edit-input{display:none;width:100%;padding:.4rem .6rem;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:1.15rem;}
.pf-edit-input.active{display:block;}
.pf-value.editing{display:none;}
.avail-dot{width:12px;height:12px;border-radius:50%;display:inline-block;margin-right:.4rem;}
.stat-mini{text-align:center;padding:1rem;border:1px solid var(--border);border-radius:var(--radius-sm);}
.stat-mini .stat-num{font-size:2rem;font-weight:800;color:var(--role-accent);}
.stat-mini .stat-lbl{font-size:1rem;color:var(--text-muted);}
.session-row{display:flex;align-items:center;gap:1rem;padding:.7rem 0;border-bottom:1px solid var(--border);}
.check-item{display:flex;align-items:center;gap:.8rem;padding:.8rem;border:1px solid var(--border);border-radius:var(--radius-sm);}
@media(max-width:768px){.profile-grid{grid-template-columns:1fr 1fr;}.profile-grid.two{grid-template-columns:1fr;}}
</style>

<!-- ═══ SECTION A: PROFILE HEADER / IDENTITY CARD ═══ -->
<div class="profile-section" style="position:relative;">
  <div style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;">
    <div style="position:relative;">
      <?php $avi=$nurse_row['profile_photo']??'';
      if($avi && $avi!=='default-avatar.png'):?>
        <img src="/RMU-Medical-Management-System/<?=e($avi)?>" alt="" style="width:110px;height:110px;border-radius:50%;object-fit:cover;border:4px solid var(--role-accent);" id="profileAvatar">
      <?php else:?>
        <div id="profileAvatar" style="width:110px;height:110px;border-radius:50%;background:var(--role-accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:3.5rem;font-weight:700;"><?=strtoupper(substr($nurse_row['full_name']??'N',0,1))?></div>
      <?php endif;?>
      <label style="position:absolute;bottom:0;right:0;width:34px;height:34px;border-radius:50%;background:var(--role-accent);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.1rem;border:2px solid var(--surface);" title="Change Photo">
        <i class="fas fa-camera"></i>
        <input type="file" id="profilePhotoInput" accept=".jpg,.jpeg,.png" style="display:none;" onchange="uploadProfilePhoto(this)">
      </label>
    </div>
    <div style="flex:1;">
      <h2 style="font-size:2rem;font-weight:800;margin:0;"><?=e($nurse_row['full_name']??'')?></h2>
      <div style="display:flex;gap:.6rem;margin:.5rem 0;flex-wrap:wrap;">
        <span class="badge badge-primary"><?=e($nurse_row['nurse_id']??'—')?></span>
        <span class="badge badge-info"><?=e($nurse_row['designation']??$prof['designation']??'Staff Nurse')?></span>
        <?=$license_badge?>
        <span class="badge badge-secondary"><?=e($nurse_row['department']??'Nursing')?></span>
        <span class="badge badge-<?=($nurse_row['shift_type']??'')==='Morning'?'warning':(($nurse_row['shift_type']??'')==='Night'?'info':'primary')?>"><?=e($nurse_row['shift_type']??'—')?> Shift</span>
        <span class="badge badge-<?=$avail_color?>"><span class="avail-dot" style="background:var(--<?=$avail_color?>);"></span><?=$avail?></span>
      </div>
      <div style="font-size:1.1rem;color:var(--text-secondary);margin-top:.3rem;">
        Specialization: <?=e($nurse_row['specialization']??$prof['specialization']??'General Nursing')?> · <?=e($nurse_row['years_of_experience']??$prof['years_of_experience']??'0')?> yrs exp · Ward: <?=e($nurse_row['ward_assigned']??'—')?>
      </div>
      <div style="font-size:1rem;color:var(--text-muted);margin-top:.3rem;">
        Member since <?=($nurse_row['member_since']??'')?date('M Y',strtotime($nurse_row['member_since'])):'—'?>
        · Last login: <?=($nurse_row['last_login']??'')?date('d M Y h:i A',strtotime($nurse_row['last_login'])):'—'?>
      </div>
      <!-- Profile Completion Bar -->
      <div style="margin-top:.8rem;">
        <div style="display:flex;justify-content:space-between;font-size:1.05rem;font-weight:600;margin-bottom:.3rem;"><span>Profile Completion</span><span><?=$profile_pct?>%</span></div>
        <div style="height:8px;background:var(--surface-2);border-radius:4px;overflow:hidden;"><div style="height:100%;width:<?=$profile_pct?>%;background:var(--<?=$profile_pct>=80?'success':($profile_pct>=50?'warning':'danger')?>);border-radius:4px;transition:width .5s;"></div></div>
        <?php if($profile_pct<100):?><small style="color:var(--text-muted);">Complete your profile to improve visibility</small><?php endif;?>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:.5rem;">
      <button class="btn btn-outline" onclick="toggleAllEditing()"><i class="fas fa-edit"></i> Edit Profile</button>
      <!-- Availability Toggle -->
      <select id="availabilityStatus" class="form-control" style="font-size:1.1rem;" onchange="updateAvailability(this.value)">
        <?php foreach(['Available','Busy','On Break','Off Duty'] as $st):?>
          <option value="<?=$st?>" <?=$avail===$st?'selected':''?>><?=$st?></option>
        <?php endforeach;?>
      </select>
    </div>
  </div>
</div>

<!-- ═══ SECTION B: PERSONAL INFORMATION ═══ -->
<div class="profile-section" id="section-personal">
  <div style="display:flex;justify-content:space-between;align-items:center;">
    <h3><i class="fas fa-user" style="color:var(--role-accent);"></i> Personal Information</h3>
    <button class="btn btn-sm btn-outline section-save-btn" data-section="personal" onclick="savePersonalInfo()" style="display:none;"><i class="fas fa-save"></i> Save</button>
  </div>
  <div class="profile-grid">
    <div><div class="pf-label">Full Name</div><div class="pf-value" data-field="full_name"><?=e($nurse_row['full_name']??'—')?></div><input class="pf-edit-input" data-field="full_name" value="<?=e($nurse_row['full_name']??'')?>"></div>
    <div><div class="pf-label">Date of Birth</div><div class="pf-value" data-field="date_of_birth"><?=($nurse_row['date_of_birth']??'')?date('d M Y',strtotime($nurse_row['date_of_birth'])):'—'?></div><input type="date" class="pf-edit-input" data-field="date_of_birth" value="<?=e($nurse_row['date_of_birth']??'')?>" onchange="calcAge(this.value)">
      <?php if($nurse_row['date_of_birth']??''):?><small class="text-muted" id="ageDisplay">Age: <?=date_diff(date_create($nurse_row['date_of_birth']),date_create('now'))->y?></small><?php endif;?>
    </div>
    <div><div class="pf-label">Gender</div><div class="pf-value" data-field="gender"><?=e($nurse_row['gender']??'—')?></div><select class="pf-edit-input" data-field="gender"><option value="">Select</option><?php foreach(['Male','Female','Other'] as $g):?><option value="<?=$g?>" <?=($nurse_row['gender']??'')===$g?'selected':''?>><?=$g?></option><?php endforeach;?></select></div>
    <div><div class="pf-label">Nationality</div><div class="pf-value" data-field="nationality"><?=e($nurse_row['nationality']??'—')?></div><input class="pf-edit-input" data-field="nationality" value="<?=e($nurse_row['nationality']??'')?>"></div>
    <div><div class="pf-label">Marital Status</div><div class="pf-value" data-field="marital_status"><?=e($nurse_row['marital_status']??'—')?></div><select class="pf-edit-input" data-field="marital_status"><option value="">Select</option><?php foreach(['Single','Married','Divorced','Widowed'] as $ms):?><option value="<?=$ms?>" <?=($nurse_row['marital_status']??'')===$ms?'selected':''?>><?=$ms?></option><?php endforeach;?></select></div>
    <div><div class="pf-label">Religion</div><div class="pf-value" data-field="religion"><?=e($nurse_row['religion']??'—')?></div><input class="pf-edit-input" data-field="religion" value="<?=e($nurse_row['religion']??'')?>"></div>
    <div><div class="pf-label">National ID / License No.</div><div class="pf-value" data-field="national_id"><?=e($nurse_row['national_id']??'—')?></div><input class="pf-edit-input" data-field="national_id" value="<?=e($nurse_row['national_id']??'')?>"></div>
    <div><div class="pf-label">Phone</div><div class="pf-value" data-field="phone"><?=e($nurse_row['phone']??'—')?></div><input class="pf-edit-input" data-field="phone" value="<?=e($nurse_row['phone']??'')?>"></div>
    <div><div class="pf-label">Secondary Phone</div><div class="pf-value" data-field="secondary_phone"><?=e($nurse_row['secondary_phone']??'—')?></div><input class="pf-edit-input" data-field="secondary_phone" value="<?=e($nurse_row['secondary_phone']??'')?>"></div>
    <div><div class="pf-label">Official Email</div><div class="pf-value" data-field="email"><?=e($nurse_row['email']??'—')?></div><input type="email" class="pf-edit-input" data-field="email" value="<?=e($nurse_row['email']??'')?>"></div>
    <div><div class="pf-label">Personal Email</div><div class="pf-value" data-field="personal_email"><?=e($nurse_row['personal_email']??'—')?></div><input type="email" class="pf-edit-input" data-field="personal_email" value="<?=e($nurse_row['personal_email']??'')?>"></div>
    <div><div class="pf-label">Office/Ward Location</div><div class="pf-value" data-field="office_location"><?=e($nurse_row['office_location']??'—')?></div><input class="pf-edit-input" data-field="office_location" value="<?=e($nurse_row['office_location']??'')?>"></div>
  </div>
  <h4 style="margin:1.2rem 0 .8rem;font-weight:600;">Residential Address</h4>
  <div class="profile-grid">
    <div><div class="pf-label">Street</div><div class="pf-value" data-field="street_address"><?=e($nurse_row['street_address']??'—')?></div><input class="pf-edit-input" data-field="street_address" value="<?=e($nurse_row['street_address']??'')?>"></div>
    <div><div class="pf-label">City</div><div class="pf-value" data-field="city"><?=e($nurse_row['city']??'—')?></div><input class="pf-edit-input" data-field="city" value="<?=e($nurse_row['city']??'')?>"></div>
    <div><div class="pf-label">Region/State</div><div class="pf-value" data-field="region"><?=e($nurse_row['region']??'—')?></div><input class="pf-edit-input" data-field="region" value="<?=e($nurse_row['region']??'')?>"></div>
    <div><div class="pf-label">Country</div><div class="pf-value" data-field="country"><?=e($nurse_row['country']??'Ghana')?></div><input class="pf-edit-input" data-field="country" value="<?=e($nurse_row['country']??'Ghana')?>"></div>
    <div><div class="pf-label">Postal Code</div><div class="pf-value" data-field="postal_code"><?=e($nurse_row['postal_code']??'—')?></div><input class="pf-edit-input" data-field="postal_code" value="<?=e($nurse_row['postal_code']??'')?>"></div>
  </div>
</div>

<!-- ═══ SECTION C: PROFESSIONAL PROFILE ═══ -->
<div class="profile-section" id="section-professional">
  <div style="display:flex;justify-content:space-between;align-items:center;">
    <h3><i class="fas fa-stethoscope" style="color:var(--role-accent);"></i> Professional Profile</h3>
    <button class="btn btn-sm btn-outline section-save-btn" data-section="professional" onclick="saveProfessionalInfo()" style="display:none;"><i class="fas fa-save"></i> Save</button>
  </div>
  <div class="profile-grid">
    <div><div class="pf-label">Specialization</div><div class="pf-value" data-field="specialization"><?=e($prof['specialization']??$nurse_row['specialization']??'—')?></div>
      <select class="pf-edit-input" data-field="specialization"><?php foreach(['General Nursing','ICU','Pediatric','Theatre','Emergency','Midwifery','Oncology','Community Health','Mental Health','Geriatric'] as $sp):?><option value="<?=$sp?>" <?=($prof['specialization']??$nurse_row['specialization']??'')===$sp?'selected':''?>><?=$sp?></option><?php endforeach;?></select></div>
    <div><div class="pf-label">Sub-specialization</div><div class="pf-value" data-field="sub_specialization"><?=e($prof['sub_specialization']??'—')?></div><input class="pf-edit-input" data-field="sub_specialization" value="<?=e($prof['sub_specialization']??'')?>"></div>
    <div><div class="pf-label">Department</div><div class="pf-value" data-field="department"><?=e($nurse_row['department']??'—')?></div>
      <select class="pf-edit-input" data-field="department_id"><option value="">Select</option><?php foreach($departments as $dp):?><option value="<?=$dp['id']?>" <?=(($prof['department_id']??0)==$dp['id'])?'selected':''?>><?=e($dp['name'])?></option><?php endforeach;?></select></div>
    <div><div class="pf-label">Designation</div><div class="pf-value" data-field="designation"><?=e($prof['designation']??$nurse_row['designation']??'Staff Nurse')?></div>
      <select class="pf-edit-input" data-field="designation"><?php foreach(['Staff Nurse','Senior Nurse','Charge Nurse','Head of Ward','Nursing Officer','Midwife'] as $d):?><option value="<?=$d?>" <?=($prof['designation']??$nurse_row['designation']??'')===$d?'selected':''?>><?=$d?></option><?php endforeach;?></select></div>
    <div><div class="pf-label">Years of Experience</div><div class="pf-value" data-field="years_of_experience"><?=e($prof['years_of_experience']??$nurse_row['years_of_experience']??'0')?> years</div><input type="number" class="pf-edit-input" data-field="years_of_experience" value="<?=e($prof['years_of_experience']??$nurse_row['years_of_experience']??'0')?>" min="0" max="50"></div>
    <div><div class="pf-label">License Number</div><div class="pf-value" data-field="license_number"><?=e($prof['license_number']??$nurse_row['license_number']??'—')?> <?=$license_badge?></div><input class="pf-edit-input" data-field="license_number" value="<?=e($prof['license_number']??$nurse_row['license_number']??'')?>"></div>
    <div><div class="pf-label">License Issuing Body</div><div class="pf-value" data-field="license_issuing_body"><?=e($prof['license_issuing_body']??$nurse_row['license_issuing_body']??'—')?></div><input class="pf-edit-input" data-field="license_issuing_body" value="<?=e($prof['license_issuing_body']??$nurse_row['license_issuing_body']??'')?>"></div>
    <div><div class="pf-label">License Expiry</div><div class="pf-value" data-field="license_expiry_date"><?=$license_expiry?date('d M Y',strtotime($license_expiry)):'—'?></div><input type="date" class="pf-edit-input" data-field="license_expiry_date" value="<?=e($license_expiry)?>"></div>
    <div><div class="pf-label">Nursing School</div><div class="pf-value" data-field="nursing_school"><?=e($prof['nursing_school']??$nurse_row['nursing_school']??'—')?></div><input class="pf-edit-input" data-field="nursing_school" value="<?=e($prof['nursing_school']??$nurse_row['nursing_school']??'')?>"></div>
    <div><div class="pf-label">Graduation Year</div><div class="pf-value" data-field="graduation_year"><?=e($prof['graduation_year']??$nurse_row['graduation_year']??'—')?></div><input type="number" class="pf-edit-input" data-field="graduation_year" value="<?=e($prof['graduation_year']??$nurse_row['graduation_year']??'')?>" min="1970" max="2030"></div>
    <div><div class="pf-label">Postgrad Training</div><div class="pf-value" data-field="postgraduate_details"><?=e($prof['postgraduate_details']??$nurse_row['postgrad_training']??'—')?></div><input class="pf-edit-input" data-field="postgraduate_details" value="<?=e($prof['postgraduate_details']??$nurse_row['postgrad_training']??'')?>"></div>
    <div><div class="pf-label">Languages Spoken</div><div class="pf-value" data-field="languages_spoken"><?php $langs=json_decode($prof['languages_spoken']??'[]',true)?:[];echo $langs?implode(', ',$langs):'—';?></div><input class="pf-edit-input" data-field="languages_spoken" value="<?=e(implode(', ',$langs))?>" placeholder="English, Twi, French"></div>
  </div>
  <?php $bio=$prof['bio']??$nurse_row['bio']??'';?>
  <div style="margin-top:1rem;"><div class="pf-label">Bio / Professional Summary</div>
    <div class="pf-value" data-field="bio" style="font-size:1.15rem;line-height:1.6;"><?=$bio?e($bio):'<span class="text-muted">No bio added</span>'?></div>
    <textarea class="pf-edit-input" data-field="bio" rows="3" style="resize:vertical;"><?=e($bio)?></textarea>
  </div>
</div>

<!-- ═══ SECTION D: QUALIFICATIONS & CERTIFICATIONS ═══ -->
<div class="profile-section" id="section-qualifications">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
    <h3><i class="fas fa-certificate" style="color:var(--warning);"></i> Qualifications & Certifications</h3>
    <div style="display:flex;gap:.5rem;"><button class="btn btn-sm btn-outline" onclick="openModal('addQualModal')"><i class="fas fa-plus"></i> Qualification</button><button class="btn btn-sm btn-outline" onclick="openModal('addCertModal')"><i class="fas fa-plus"></i> Certification</button></div>
  </div>
  <?php if(!empty($nurse_quals)):?><h4 style="margin-bottom:.5rem;">Qualifications</h4>
  <?php foreach($nurse_quals as $q):?><div style="display:flex;align-items:center;gap:1rem;padding:.6rem 0;border-bottom:1px solid var(--border);">
    <i class="fas fa-graduation-cap" style="color:var(--primary);font-size:1.4rem;"></i>
    <div style="flex:1;"><strong><?=e($q['degree'])?></strong> — <?=e($q['institution'])?> (<?=e($q['year'])?>)</div>
    <?php if($q['certificate_file']??''):?><a href="nurse_actions.php?action=secure_download&file_id=<?=$q['id']?>&source=nurse_qualifications&_csrf=<?=e($csrf_token)?>" class="btn btn-xs btn-outline"><i class="fas fa-download"></i></a><?php endif;?>
    <button class="btn btn-xs btn-danger" onclick="deleteQual(<?=$q['id']?>)"><i class="fas fa-trash"></i></button>
  </div><?php endforeach; endif;?>

  <?php if(!empty($nurse_certs)):?><h4 style="margin:.8rem 0 .5rem;">Certifications</h4>
  <?php foreach($nurse_certs as $c):
    $exp_days = $c['expiry_date'] ? (int)((strtotime($c['expiry_date'])-time())/86400) : 999;
  ?><div style="display:flex;align-items:center;gap:1rem;padding:.6rem 0;border-bottom:1px solid var(--border);">
    <i class="fas fa-award" style="color:var(--warning);font-size:1.4rem;"></i>
    <div style="flex:1;"><strong><?=e($c['certification_name'])?></strong> — <?=e($c['issuing_body'])?>
      <?php if($c['expiry_date']):?> · Exp: <?=date('d M Y',strtotime($c['expiry_date']))?> <?=$exp_days<=60?'<span class="badge badge-'.($exp_days<=0?'danger':'warning').'">'.$exp_days.'d</span>':''?><?php endif;?>
    </div>
    <?php if($c['certificate_file']??''):?><a href="nurse_actions.php?action=secure_download&file_id=<?=$c['id']?>&source=nurse_certifications&_csrf=<?=e($csrf_token)?>" class="btn btn-xs btn-outline"><i class="fas fa-download"></i></a><?php endif;?>
    <button class="btn btn-xs btn-danger" onclick="deleteCert(<?=$c['id']?>)"><i class="fas fa-trash"></i></button>
  </div><?php endforeach; endif;?>
  <?php if(empty($nurse_quals) && empty($nurse_certs)):?><p class="text-center text-muted" style="padding:1.5rem;">No qualifications or certifications added</p><?php endif;?>
</div>

<!-- ═══ SECTION E: SHIFT & AVAILABILITY PROFILE ═══ -->
<div class="profile-section" id="section-shift">
  <h3><i class="fas fa-clock" style="color:var(--info);"></i> Shift & Availability</h3>
  <div class="profile-grid" style="margin-bottom:1.2rem;">
    <div class="stat-mini"><div class="pf-label">Current Shift</div><div class="pf-value" style="font-size:1.4rem;font-weight:700;"><?=e($today_shift['shift_type']??'No shift today')?></div>
      <?php if($today_shift):?><small><?=e($today_shift['start_time']??'')?> — <?=e($today_shift['end_time']??'')?></small><?php endif;?></div>
    <div class="stat-mini"><div class="pf-label">Ward Assignment</div><div class="pf-value" style="font-size:1.4rem;font-weight:700;"><?=e($today_shift['ward_assigned']??$nurse_row['ward_assigned']??'—')?></div></div>
    <div class="stat-mini"><div class="pf-label">Availability</div><div class="pf-value" style="font-size:1.4rem;font-weight:700;color:var(--<?=$avail_color?>);"><span class="avail-dot" style="background:var(--<?=$avail_color?>);"></span><?=$avail?></div></div>
  </div>
  <?php if($today_shift && $today_shift['end_time']):
    $nextEnd = strtotime($today_shift['shift_date'].' '.$today_shift['end_time']);
    $remain = $nextEnd - time();
    if($remain > 0):?><div style="background:var(--surface-2);padding:.8rem 1.2rem;border-radius:var(--radius-sm);margin-bottom:1rem;">
    <i class="fas fa-hourglass-half" style="color:var(--warning);"></i> Shift ends in <strong id="shiftCountdown"><?=floor($remain/3600)?>h <?=floor(($remain%3600)/60)?>m</strong>
  </div><?php endif; endif;?>

  <h4>Upcoming 7-Day Schedule</h4>
  <?php if(empty($shifts7)):?><p class="text-muted">No shifts scheduled for the next 7 days</p>
  <?php else:?><div class="table-responsive"><table class="data-table"><thead><tr><th>Date</th><th>Day</th><th>Shift</th><th>Ward</th><th>Time</th><th>Status</th></tr></thead><tbody>
  <?php foreach($shifts7 as $sh):
    $isToday = $sh['shift_date']===date('Y-m-d');
  ?><tr style="<?=$isToday?'background:rgba(46,204,113,.08);font-weight:600;':''?>">
    <td><?=date('d M',strtotime($sh['shift_date']))?> <?=$isToday?'<span class="badge badge-primary">Today</span>':''?></td>
    <td><?=date('l',strtotime($sh['shift_date']))?></td>
    <td><span class="badge badge-<?=$sh['shift_type']==='Morning'?'warning':($sh['shift_type']==='Night'?'info':'primary')?>"><?=e($sh['shift_type'])?></span></td>
    <td><?=e($sh['ward_assigned']??'—')?></td>
    <td><?=e($sh['start_time']??'')?> — <?=e($sh['end_time']??'')?></td>
    <td><span class="badge badge-<?=$sh['status']==='Active'?'success':($sh['status']==='Completed'?'secondary':'info')?>"><?=e($sh['status'])?></span></td>
  </tr><?php endforeach;?></tbody></table></div><?php endif;?>

  <div style="margin-top:1rem;"><div class="pf-label">Shift Preference Notes (visible to admin)</div>
    <textarea id="shiftPrefNotes" class="form-control" rows="2" placeholder="e.g. Prefer morning shifts, available for overtime on weekends"><?=e($nurse_row['shift_preference_notes']??'')?></textarea>
    <button class="btn btn-sm btn-outline" style="margin-top:.5rem;" onclick="saveShiftPrefNotes()"><i class="fas fa-save"></i> Save Notes</button>
  </div>
</div>

<?php include __DIR__ . '/tab_profile_part2.php'; ?>
<?php include __DIR__ . '/tab_profile_part3.php'; ?>
<?php include __DIR__ . '/tab_profile_js.php'; ?>
