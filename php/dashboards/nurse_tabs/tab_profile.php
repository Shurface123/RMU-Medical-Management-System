<!-- ═══════════════════════════════════════════════════════════
     MODULE 13: ADVANCED NURSE PROFILE — tab_profile.php
     ═══════════════════════════════════════════════════════════ -->
<?php
$nurse_certs = dbSelect($conn,"SELECT * FROM nurse_certifications WHERE nurse_id=? ORDER BY created_at DESC","i",[$nurse_pk]);
$nurse_quals = dbSelect($conn,"SELECT * FROM nurse_qualifications WHERE nurse_id=? ORDER BY year DESC","i",[$nurse_pk]);
$nurse_docs  = dbSelect($conn,"SELECT * FROM nurse_documents WHERE nurse_id=? ORDER BY uploaded_at DESC","i",[$nurse_pk]);
$nurse_sess  = dbSelect($conn,"SELECT * FROM nurse_sessions WHERE nurse_id=? ORDER BY login_time DESC LIMIT 10","i",[$nurse_pk]);
$nurse_log   = dbSelect($conn,"SELECT * FROM nurse_activity_log WHERE nurse_id=? ORDER BY created_at DESC LIMIT 20","i",[$nurse_pk]);
$completeness= dbRow($conn,"SELECT * FROM nurse_profile_completeness WHERE nurse_id=?","i",[$nurse_pk]);
$settings    = dbRow($conn,"SELECT * FROM nurse_settings WHERE nurse_id=?","i",[$nurse_pk]);

// License expiry check
$license_expiry = $nurse_row['license_expiry'] ?? '';
$license_days = $license_expiry ? (int)((strtotime($license_expiry)-time())/86400) : 999;
$license_badge = $license_days<=0 ? '<span class="badge badge-danger">Expired</span>' :
                ($license_days<=60 ? '<span class="badge badge-warning">Expiring in '.$license_days.'d</span>' :
                '<span class="badge badge-success">Valid</span>');

$profile_pct = (int)($completeness['completeness_percentage']??0);

$notif_prefs = json_decode($settings['notification_preferences']??'{}',true) ?: [];
?>
<div id="sec-profile" class="dash-section">

  <!-- ═══ SECTION A: PROFILE HEADER ═══ -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <div style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;">
      <div style="position:relative;">
        <?php $avi=$nurse_row['profile_photo']??$nurse_row['profile_image']??'';
        if($avi && $avi!=='default-avatar.png'):?>
          <img src="/RMU-Medical-Management-System/<?=e($avi)?>" alt="" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--role-accent);">
        <?php else:?>
          <div style="width:100px;height:100px;border-radius:50%;background:var(--role-accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:3.5rem;font-weight:700;"><?=strtoupper(substr($nurse_row['full_name']??'N',0,1))?></div>
        <?php endif;?>
        <label style="position:absolute;bottom:0;right:0;width:32px;height:32px;border-radius:50%;background:var(--role-accent);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.1rem;border:2px solid var(--surface);" title="Change Photo">
          <i class="fas fa-camera"></i>
          <input type="file" id="profilePhotoInput" accept=".jpg,.jpeg,.png" style="display:none;" onchange="uploadProfilePhoto(this)">
        </label>
      </div>
      <div style="flex:1;">
        <h2 style="font-size:2rem;font-weight:800;margin:0;"><?=e($nurse_row['full_name']??$nurseName)?></h2>
        <div style="display:flex;gap:.8rem;margin:.5rem 0;flex-wrap:wrap;">
          <span class="badge badge-primary"><?=e($nurse_row['nurse_id']??'—')?></span>
          <span class="badge badge-info"><?=e($nurse_row['designation']??'Staff Nurse')?></span>
          <?=$license_badge?>
          <span class="badge badge-secondary"><?=e($nurse_row['department']??'Nursing')?></span>
          <span class="badge badge-<?=($nurse_row['shift_type']??'')==='Morning'?'warning':(($nurse_row['shift_type']??'')==='Night'?'info':'primary')?>"><?=e($nurse_row['shift_type']??'—')?> Shift</span>
        </div>
        <div style="font-size:1.15rem;color:var(--text-secondary);margin-top:.4rem;">
          License: <?=e($nurse_row['license_number']??'N/A')?> · Member since <?=$nurse_row['member_since']?date('M Y',strtotime($nurse_row['member_since'])):'—'?>
          · Last login: <?=$nurse_row['last_login']?date('d M Y h:i A',strtotime($nurse_row['last_login'])):'—'?>
        </div>
        <!-- Profile Completion -->
        <div style="margin-top:.8rem;">
          <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:600;margin-bottom:.3rem;"><span>Profile Completion</span><span><?=$profile_pct?>%</span></div>
          <div style="height:6px;background:var(--surface-2);border-radius:3px;overflow:hidden;"><div style="height:100%;width:<?=$profile_pct?>%;background:var(--<?=$profile_pct>=80?'success':($profile_pct>=50?'warning':'danger')?>);border-radius:3px;"></div></div>
        </div>
      </div>
      <button class="btn btn-outline" onclick="toggleProfileEdit()"><i class="fas fa-edit"></i> Edit Profile</button>
    </div>
  </div>

  <!-- ═══ SECTION B: PERSONAL INFORMATION ═══ -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-user" style="color:var(--role-accent);"></i> Personal Information</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.2rem;" id="personalInfo">
      <div><label style="font-size:1.1rem;color:var(--text-muted);display:block;">Full Name</label><div class="profile-val" data-field="full_name"><?=e($nurse_row['full_name']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);display:block;">Date of Birth</label><div class="profile-val" data-field="date_of_birth"><?=$nurse_row['date_of_birth']?date('d M Y',strtotime($nurse_row['date_of_birth'])):'—'?></div><?php if($nurse_row['date_of_birth']):?><small style="color:var(--text-muted);">Age: <?=date_diff(date_create($nurse_row['date_of_birth']),date_create('now'))->y?></small><?php endif;?></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);display:block;">Gender</label><div class="profile-val" data-field="gender"><?=e($nurse_row['gender']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);display:block;">Nationality</label><div class="profile-val" data-field="nationality"><?=e($nurse_row['nationality']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);display:block;">Phone</label><div class="profile-val" data-field="phone"><?=e($nurse_row['phone']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);display:block;">Email</label><div class="profile-val" data-field="email"><?=e($nurse_row['email']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);display:block;">Marital Status</label><div class="profile-val"><?=e($nurse_row['marital_status']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);display:block;">National ID</label><div class="profile-val"><?=e($nurse_row['national_id']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);display:block;">Office Location</label><div class="profile-val"><?=e($nurse_row['office_location']??'—')?></div></div>
    </div>
  </div>

  <!-- ═══ SECTION C: PROFESSIONAL PROFILE ═══ -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-stethoscope" style="color:var(--role-accent);"></i> Professional Profile</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.2rem;">
      <div><label style="font-size:1.1rem;color:var(--text-muted);">License Number</label><div style="font-size:1.3rem;font-weight:600;"><?=e($nurse_row['license_number']??'—')?> <?=$license_badge?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);">License Issuing Body</label><div style="font-size:1.3rem;"><?=e($nurse_row['license_issuing_body']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);">License Expiry</label><div style="font-size:1.3rem;"><?=$license_expiry?date('d M Y',strtotime($license_expiry)):'—'?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);">Specialization</label><div style="font-size:1.3rem;"><?=e($nurse_row['specialization']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);">Designation</label><div style="font-size:1.3rem;"><?=e($nurse_row['designation']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);">Years of Experience</label><div style="font-size:1.3rem;"><?=e($nurse_row['years_of_experience']??'0')?> years</div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);">Nursing School</label><div style="font-size:1.3rem;"><?=e($nurse_row['nursing_school']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);">Graduation Year</label><div style="font-size:1.3rem;"><?=e($nurse_row['graduation_year']??'—')?></div></div>
      <div><label style="font-size:1.1rem;color:var(--text-muted);">Postgrad Training</label><div style="font-size:1.3rem;"><?=e($nurse_row['postgrad_training']??'—')?></div></div>
    </div>
    <?php if($nurse_row['bio']??''):?><div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);"><label style="font-size:1.1rem;color:var(--text-muted);">Bio</label><p style="font-size:1.25rem;line-height:1.6;"><?=e($nurse_row['bio'])?></p></div><?php endif;?>
  </div>

  <!-- ═══ SECTION D: QUALIFICATIONS & CERTIFICATIONS ═══ -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
      <h3 style="font-size:1.5rem;font-weight:700;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-certificate" style="color:var(--warning);"></i> Qualifications & Certifications</h3>
      <div style="display:flex;gap:.5rem;"><button class="btn btn-sm btn-outline" onclick="openModal('addQualModal')"><i class="fas fa-plus"></i> Qualification</button><button class="btn btn-sm btn-outline" onclick="openModal('addCertModal')"><i class="fas fa-plus"></i> Certification</button></div>
    </div>
    <?php if(!empty($nurse_quals)):?><h4 style="margin-bottom:.5rem;">Qualifications</h4>
    <?php foreach($nurse_quals as $q):?><div style="display:flex;align-items:center;gap:1rem;padding:.6rem 0;border-bottom:1px solid var(--border);">
      <div style="flex:1;"><strong><?=e($q['degree'])?></strong> — <?=e($q['institution'])?> (<?=e($q['year'])?>)</div>
      <button class="btn btn-xs btn-outline" onclick="deleteQual(<?=$q['id']?>)"><i class="fas fa-trash"></i></button>
    </div><?php endforeach; endif;?>
    <?php if(!empty($nurse_certs)):?><h4 style="margin:.8rem 0 .5rem;">Certifications</h4>
    <?php foreach($nurse_certs as $c):
      $exp_days = $c['expiry_date'] ? (int)((strtotime($c['expiry_date'])-time())/86400) : 999;
    ?><div style="display:flex;align-items:center;gap:1rem;padding:.6rem 0;border-bottom:1px solid var(--border);">
      <div style="flex:1;"><strong><?=e($c['certification_name'])?></strong> — <?=e($c['issuing_body'])?> <?php if($c['expiry_date']):?>· Exp: <?=date('d M Y',strtotime($c['expiry_date']))?> <?=$exp_days<=60?'<span class="badge badge-'.($exp_days<=0?'danger':'warning').'">'.$exp_days.'d</span>':''?><?php endif;?></div>
      <button class="btn btn-xs btn-outline" onclick="deleteCert(<?=$c['id']?>)"><i class="fas fa-trash"></i></button>
    </div><?php endforeach; endif;?>
    <?php if(empty($nurse_quals) && empty($nurse_certs)):?><p class="text-center text-muted" style="padding:1.5rem;">No qualifications or certifications added</p><?php endif;?>
  </div>

  <!-- ═══ SECTION E: ACCOUNT & SECURITY ═══ -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-shield-alt" style="color:var(--danger);"></i> Account & Security</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
      <div>
        <h4>Change Password</h4>
        <div class="form-group"><input id="pw_current" type="password" class="form-control" placeholder="Current Password"></div>
        <div class="form-group"><input id="pw_new" type="password" class="form-control" placeholder="New Password" oninput="checkPasswordStrength(this.value)"></div>
        <div id="pw_strength" style="font-size:1.1rem;margin-bottom:.5rem;"></div>
        <div class="form-group"><input id="pw_confirm" type="password" class="form-control" placeholder="Confirm New Password"></div>
        <button class="btn btn-sm btn-primary" onclick="changePassword()"><i class="fas fa-key"></i> Change Password</button>
      </div>
      <div>
        <h4>Active Sessions</h4>
        <?php foreach(array_slice($nurse_sess,0,5) as $s):?>
          <div style="padding:.5rem 0;border-bottom:1px solid var(--border);font-size:1.15rem;">
            <i class="fas fa-desktop" style="color:var(--text-muted);"></i> <?=e(substr($s['device']??'Unknown',0,50))?> · <?=e($s['ip_address']??'')?><br>
            <small class="text-muted"><?=date('d M h:i A',strtotime($s['login_time']))?></small>
          </div>
        <?php endforeach;?>
        <h4 style="margin-top:1rem;">Recent Activity</h4>
        <?php foreach(array_slice($nurse_log,0,5) as $l):?>
          <div style="padding:.3rem 0;font-size:1.1rem;color:var(--text-secondary);">
            <?=e($l['action_type'])?> — <?=e(substr($l['action_description'],0,60))?> · <?=date('d M h:i A',strtotime($l['created_at']))?>
          </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>

  <!-- ═══ SECTION F: NOTIFICATION PREFERENCES ═══ -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-bell-concierge" style="color:var(--warning);"></i> Notification Preferences</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
      <?php $notif_types = ['new_task'=>'New task assigned','med_reminder'=>'Medication reminders','vital_due'=>'Vital signs due','abnormal_vital'=>'Abnormal vital flags','shift_reminder'=>'Shift schedule reminders','handover_alert'=>'Handover alerts','doctor_message'=>'Doctor messages','emergency_update'=>'Emergency updates','system_announce'=>'System announcements'];
      foreach($notif_types as $nk => $nl):?>
        <label style="display:flex;align-items:center;gap:.8rem;padding:.6rem;cursor:pointer;border:1px solid var(--border);border-radius:var(--radius-sm);">
          <input type="checkbox" class="notif_pref" data-key="<?=$nk?>" <?=($notif_prefs[$nk]??true)?'checked':''?> onchange="saveNotifPref()"> <span style="font-size:1.2rem;"><?=$nl?></span>
        </label>
      <?php endforeach;?>
    </div>
  </div>

  <!-- ═══ SECTION G: DOCUMENTS ═══ -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
      <h3 style="font-size:1.5rem;font-weight:700;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-folder-open" style="color:var(--primary);"></i> Documents</h3>
      <button class="btn btn-sm btn-outline" onclick="openModal('uploadDocModal')"><i class="fas fa-upload"></i> Upload</button>
    </div>
    <?php if(empty($nurse_docs)):?><p class="text-center text-muted" style="padding:1.5rem;">No documents uploaded</p>
    <?php else:?><div class="table-responsive"><table class="data-table"><thead><tr><th>Name</th><th>Type</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody>
    <?php foreach($nurse_docs as $d):?><tr>
      <td><i class="fas fa-file" style="color:var(--primary);"></i> <?=e($d['document_name'])?></td><td><?=e($d['document_type'])?></td>
      <td><?=round(($d['file_size']??0)/1024)?>KB</td><td><?=date('d M Y',strtotime($d['uploaded_at']))?></td>
      <td class="action-btns"><a href="/RMU-Medical-Management-System/<?=e($d['file_path'])?>" class="btn btn-xs btn-outline" download><i class="fas fa-download"></i></a><button class="btn btn-xs btn-danger" onclick="deleteDoc(<?=$d['id']?>)"><i class="fas fa-trash"></i></button></td>
    </tr><?php endforeach;?></tbody></table></div><?php endif;?>
  </div>

  <!-- ═══ SECTION H: COMPLETENESS ENGINE ═══ -->
  <div class="info-card">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-tasks" style="color:var(--success);"></i> Profile Completeness</h3>
    <?php
    $checks = [
      ['key'=>'personal_info','label'=>'Personal Information','done'=>(int)($completeness['personal_info']??0)],
      ['key'=>'professional_profile','label'=>'Professional Profile','done'=>(int)($completeness['professional_profile']??0)],
      ['key'=>'qualifications','label'=>'Qualifications','done'=>(int)($completeness['qualifications']??0)],
      ['key'=>'documents','label'=>'Documents','done'=>(int)($completeness['documents_uploaded']??0)],
      ['key'=>'photo','label'=>'Profile Photo','done'=>(int)($completeness['profile_photo']??0)],
      ['key'=>'security','label'=>'Security Setup','done'=>(int)($completeness['security_setup']??0)],
    ];
    ?>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;">
    <?php foreach($checks as $ch):?>
      <div style="display:flex;align-items:center;gap:.8rem;padding:.8rem;border:1px solid var(--border);border-radius:var(--radius-sm);">
        <i class="fas <?=$ch['done']?'fa-check-circle':'fa-exclamation-circle'?>" style="font-size:1.6rem;color:var(--<?=$ch['done']?'success':'warning'?>);"></i>
        <div><div style="font-weight:600;"><?=$ch['label']?></div>
          <?php if(!$ch['done']):?><a href="#" onclick="event.preventDefault();" style="font-size:1.05rem;color:var(--role-accent);">Complete Now →</a><?php else:?><span style="font-size:1rem;color:var(--success);">Complete</span><?php endif;?>
        </div>
      </div>
    <?php endforeach;?>
    </div>
  </div>
</div>

<!-- ═══════ ADD QUALIFICATION MODAL ═══════ -->
<div class="modal-bg" id="addQualModal">
  <div class="modal-box">
    <div class="modal-header"><h3>Add Qualification</h3><button class="modal-close" onclick="closeModal('addQualModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Degree *</label><input id="qual_degree" class="form-control" placeholder="e.g. BSN"></div>
    <div class="form-group"><label>Institution *</label><input id="qual_inst" class="form-control" placeholder="University name"></div>
    <div class="form-group"><label>Year *</label><input id="qual_year" type="number" class="form-control" placeholder="2020"></div>
    <div class="form-group"><label>Certificate</label><input type="file" id="qual_file" class="form-control" accept=".pdf,.jpg,.png"></div>
    <button class="btn btn-primary" onclick="submitQual()" style="width:100%;"><i class="fas fa-save"></i> Save</button>
  </div>
</div>

<!-- ═══════ ADD CERTIFICATION MODAL ═══════ -->
<div class="modal-bg" id="addCertModal">
  <div class="modal-box">
    <div class="modal-header"><h3>Add Certification</h3><button class="modal-close" onclick="closeModal('addCertModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Certification Name *</label><input id="cert_name" class="form-control" placeholder="e.g. BLS Certification"></div>
    <div class="form-group"><label>Issuing Body *</label><input id="cert_body" class="form-control" placeholder="e.g. AHA"></div>
    <div class="form-row"><div class="form-group"><label>Issue Date</label><input id="cert_issue" type="date" class="form-control"></div>
    <div class="form-group"><label>Expiry Date</label><input id="cert_expiry" type="date" class="form-control"></div></div>
    <div class="form-group"><label>Certificate File</label><input type="file" id="cert_file" class="form-control" accept=".pdf,.jpg,.png"></div>
    <button class="btn btn-primary" onclick="submitCert()" style="width:100%;"><i class="fas fa-save"></i> Save</button>
  </div>
</div>

<!-- ═══════ UPLOAD DOCUMENT MODAL ═══════ -->
<div class="modal-bg" id="uploadDocModal">
  <div class="modal-box">
    <div class="modal-header"><h3>Upload Document</h3><button class="modal-close" onclick="closeModal('uploadDocModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Document Type *</label>
      <select id="doc_type" class="form-control"><option value="License">Nursing License</option><option value="Certification">Certification</option><option value="Employment">Employment Letter</option><option value="Training">Training Certificate</option><option value="Other">Other</option></select>
    </div>
    <div class="form-group"><label>Document Name</label><input id="doc_name" class="form-control" placeholder="Document name"></div>
    <div class="form-group"><label>File *</label><input type="file" id="doc_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png"></div>
    <button class="btn btn-primary" onclick="submitDoc()" style="width:100%;"><i class="fas fa-upload"></i> Upload</button>
  </div>
</div>

<script>
function checkPasswordStrength(pw){
  let s=0;if(pw.length>=8)s++;if(pw.length>=12)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;
  const labels=['','Weak','Fair','Strong','Very Strong','Very Strong'];
  const colors=['','#E74C3C','#F39C12','#2ECC71','#27AE60','#27AE60'];
  document.getElementById('pw_strength').innerHTML=pw ?`<span style="color:${colors[s]};">Strength: ${labels[s]}</span> ${'█'.repeat(s)}${'░'.repeat(5-s)}`:'';
}

async function changePassword(){
  const cur=document.getElementById('pw_current').value, nw=document.getElementById('pw_new').value, cnf=document.getElementById('pw_confirm').value;
  if(!cur||!nw){showToast('Fill all password fields','error');return;}
  if(nw!==cnf){showToast('Passwords do not match','error');return;}
  if(nw.length<8){showToast('Password must be at least 8 characters','error');return;}
  const r=await nurseAction({action:'change_password',current:cur,new_password:nw});
  showToast(r.message||'Done',r.success?'success':'error');
}

async function uploadProfilePhoto(input){
  if(!input.files[0])return;
  const fd=new FormData();fd.append('action','upload_profile_photo');fd.append('photo',input.files[0]);
  const r=await nurseAction(fd);showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),1000);
}

async function submitQual(){
  if(!validateForm({qual_degree:'Degree',qual_inst:'Institution',qual_year:'Year'})) return;
  const fd=new FormData();fd.append('action','add_qualification');
  fd.append('degree',document.getElementById('qual_degree').value);
  fd.append('institution',document.getElementById('qual_inst').value);
  fd.append('year',document.getElementById('qual_year').value);
  if(document.getElementById('qual_file').files[0]) fd.append('certificate',document.getElementById('qual_file').files[0]);
  const r=await nurseAction(fd);showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success){closeModal('addQualModal');setTimeout(()=>location.reload(),1000);}
}

async function submitCert(){
  if(!validateForm({cert_name:'Certification name',cert_body:'Issuing body'})) return;
  const fd=new FormData();fd.append('action','add_certification');
  fd.append('name',document.getElementById('cert_name').value);
  fd.append('issuing_body',document.getElementById('cert_body').value);
  fd.append('issue_date',document.getElementById('cert_issue').value);
  fd.append('expiry_date',document.getElementById('cert_expiry').value);
  if(document.getElementById('cert_file').files[0]) fd.append('certificate_file',document.getElementById('cert_file').files[0]);
  const r=await nurseAction(fd);showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success){closeModal('addCertModal');setTimeout(()=>location.reload(),1000);}
}

async function submitDoc(){
  if(!document.getElementById('doc_file').files[0]){showToast('Select a file','error');return;}
  const fd=new FormData();fd.append('action','upload_document');
  fd.append('document_type',document.getElementById('doc_type').value);
  fd.append('document_name',document.getElementById('doc_name').value||document.getElementById('doc_file').files[0].name);
  fd.append('file',document.getElementById('doc_file').files[0]);
  const r=await nurseAction(fd);showToast(r.message||'Done',r.success?'success':'error');
  if(r.success){closeModal('uploadDocModal');setTimeout(()=>location.reload(),1000);}
}

async function deleteQual(id){if(!confirmAction('Delete this qualification?'))return;
  const r=await nurseAction({action:'delete_qualification',qual_id:id});showToast(r.message||'Deleted',r.success?'success':'error');if(r.success) setTimeout(()=>location.reload(),800);}
async function deleteCert(id){if(!confirmAction('Delete this certification?'))return;
  const r=await nurseAction({action:'delete_certification',cert_id:id});showToast(r.message||'Deleted',r.success?'success':'error');if(r.success) setTimeout(()=>location.reload(),800);}
async function deleteDoc(id){if(!confirmAction('Delete this document?'))return;
  const r=await nurseAction({action:'delete_document',doc_id:id});showToast(r.message||'Deleted',r.success?'success':'error');if(r.success) setTimeout(()=>location.reload(),800);}

async function saveNotifPref(){
  const prefs={};document.querySelectorAll('.notif_pref').forEach(cb=>{prefs[cb.dataset.key]=cb.checked;});
  const r=await nurseAction({action:'save_notification_prefs',preferences:prefs});
  showToast(r.success?'Preferences saved':'Error',r.success?'success':'error');
}

function toggleProfileEdit(){showToast('Use the inline fields to edit your profile','info');}
</script>
