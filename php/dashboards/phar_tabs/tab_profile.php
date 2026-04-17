<!-- ============================================================
     MODULE 9 — ADVANCED PHARMACIST PROFILE
     Sections A-H: identity, personal, professional, qualifications,
     security, notifications, documents, completeness engine
     ============================================================ -->
<div id="sec-profile" class="dash-section <?=($active_tab==='profile')?'active':''?>">
<?php
/* ── Fetch profile data ─────────────────────────────────── */
$prof = $pharm_row; // from pharmacy_dashboard.php
$pp   = $pharm_pk;
$quals = dbSelect($conn, "SELECT * FROM pharmacist_qualifications WHERE pharmacist_id=? ORDER BY year_awarded DESC", "i", [$pp]);
$certs = dbSelect($conn, "SELECT * FROM pharmacist_certifications WHERE pharmacist_id=? ORDER BY expiry_date ASC", "i", [$pp]);
$docs  = dbSelect($conn, "SELECT * FROM pharmacist_documents WHERE pharmacist_id=? ORDER BY uploaded_at DESC", "i", [$pp]);
$sessions = dbSelect($conn, "SELECT * FROM pharmacist_sessions WHERE pharmacist_id=? ORDER BY login_time DESC LIMIT 10", "i", [$pp]);
$actLog = dbSelect($conn, "SELECT * FROM pharmacist_activity_log WHERE pharmacist_id=? ORDER BY created_at DESC LIMIT 20", "i", [$pp]);
$compRow = dbRow($conn, "SELECT * FROM pharmacist_profile_completeness WHERE pharmacist_id=?", "i", [$pp]);
$pSettings = dbRow($conn, "SELECT * FROM pharmacy_settings WHERE pharmacist_id=?", "i", [$pp]);
/* completion calc */
$compSections = ['personal_info','professional_profile','qualifications','photo_uploaded','security_setup','documents_uploaded'];
$compDone = 0; if($compRow) foreach($compSections as $c) if(!empty($compRow[$c])) $compDone++;
$compPct = $compRow ? (int)$compRow['overall_pct'] : round(($compDone/count($compSections))*100);
$licDays = $prof['license_expiry'] ? (int)((strtotime($prof['license_expiry'])-time())/86400) : 999;
$licBadge = $licDays>60 ? '<span class="badge badge-success">Valid</span>' : ($licDays>0 ? '<span class="badge badge-warning">Expiring in '.$licDays.'d</span>' : '<span class="badge badge-danger">Expired</span>');
$photoSrc = $prof['profile_photo'] ? '/RMU-Medical-Management-System/'.e($prof['profile_photo']) : '';
$reg = $prof['created_at'] ?? '';
$age = $prof['date_of_birth'] ? (int)((time()-strtotime($prof['date_of_birth']))/31557600) : '';
?>

<!-- ── Profile Sub-Navigation ──────────────────────────── -->
<div class="profile-subnav">
<?php foreach([
  'header'=>'Header','personal'=>'Personal','professional'=>'Professional',
  'qualifications'=>'Qualifications','security'=>'Security','notifprefs'=>'Notifications',
  'documents'=>'Documents','completeness'=>'Completeness'
] as $k=>$v): ?>
  <button class="btn btn-primary prof-tab <?=$k==='header'?'active':''?>" onclick="showProfSection('<?=$k?>',this)"><span class="btn-text"><?=$v?></span></button>
<?php endforeach; ?>
</div>

<!-- ════════════════ SECTION A: PROFILE HEADER ════════════ -->
<div class="prof-section active" id="prof-header">
<div class="profile-header-card">
  <div class="ph-photo-wrap">
    <div class="ph-avatar" id="profAvatarWrap">
      <?php if($photoSrc): ?><img src="<?=$photoSrc?>" alt="Photo" id="profAvatarImg"><?php else: ?><i class="fas fa-user-circle fa-4x" id="profAvatarImg"></i><?php endif; ?>
    </div>
    <label class="ph-upload-btn" title="Change Photo"><i class="fas fa-camera"></i>
      <input type="file" id="profPhotoInput" accept="image/jpeg,image/png" style="display:none"
        onchange="uploadProfilePhoto(this)">
    </label>
  </div>
  <div class="ph-info">
    <h2><?=e($prof['full_name'])?></h2>
    <div class="ph-meta">
      <span><i class="fas fa-id-badge"></i> <?=e($prof['license_number']??'N/A')?></span>
      <span><?=$licBadge?></span>
      <span><i class="fas fa-building"></i> <?=e($prof['department']??'Pharmacy')?></span>
    </div>
    <div class="ph-meta" style="margin-top:.5rem;">
      <span class="status-dot <?=($prof['availability_status']??'Offline')==='Online'?'online':'offline'?>">
        <?=e($prof['availability_status']??'Offline')?>
      </span>
      <span><i class="fas fa-calendar-alt"></i> Member since <?=$reg?date('d M Y',strtotime($reg)):'N/A'?></span>
      <?php if($age): ?><span><i class="fas fa-birthday-cake"></i> <?=$age?> yrs</span><?php endif; ?>
    </div>
    <div class="ph-progress" style="margin-top:1rem;">
      <div class="progress-label">Profile <?=$compPct?>% complete</div>
      <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?=$compPct?>%"></div></div>
      <?php if($compPct<100): ?><small class="text-muted">Complete all sections to reach 100%</small><?php endif; ?>
    </div>
  </div>
  <button class="btn btn-outline-primary btn-sm" onclick="showProfSection('personal',document.querySelector('.prof-tab:nth-child(2)'))"><span class="btn-text"><i class="fas fa-pen"></i> Edit Profile</span></button>
</div>
</div>

<!-- ════════════════ SECTION B: PERSONAL INFO ════════════ -->
<div class="prof-section" id="prof-personal">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-user"></i> Personal Information</h3><button class="btn btn-sm btn-primary" onclick="savePersonalInfo()"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button></div>
  <div class="form-grid-2">
    <div class="form-group"><label>Full Name</label><input id="pf_name" class="form-control" value="<?=e($prof['full_name'])?>"></div>
    <div class="form-group"><label>Date of Birth</label><input id="pf_dob" type="date" class="form-control" value="<?=e($prof['date_of_birth']??'')?>"></div>
    <div class="form-group"><label>Gender</label><select id="pf_gender" class="form-control"><option value="">Select</option><?php foreach(['Male','Female','Other'] as $g): ?><option <?=($prof['gender']??'')===$g?'selected':''?>><?=$g?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Nationality</label><input id="pf_nationality" class="form-control" value="<?=e($prof['nationality']??'')?>"></div>
    <div class="form-group"><label>Marital Status</label><select id="pf_marital" class="form-control"><option value="">Select</option><?php foreach(['Single','Married','Divorced','Widowed'] as $m): ?><option <?=($prof['marital_status']??'')===$m?'selected':''?>><?=$m?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>National ID</label><input id="pf_nid" class="form-control" value="<?=e($prof['national_id']??'')?>"></div>
    <div class="form-group"><label>Primary Phone</label><input id="pf_phone" class="form-control" value="<?=e($prof['phone']??'')?>"></div>
    <div class="form-group"><label>Secondary Phone</label><input id="pf_phone2" class="form-control" value="<?=e($prof['secondary_phone']??'')?>"></div>
    <div class="form-group"><label>Official Email</label><input id="pf_email" type="email" class="form-control" value="<?=e($prof['email']??'')?>"></div>
    <div class="form-group"><label>Personal Email</label><input id="pf_pemail" type="email" class="form-control" value="<?=e($prof['personal_email']??'')?>"></div>
  </div>
  <h4 style="margin:1.5rem 0 .8rem;"><i class="fas fa-map-marker-alt"></i> Address</h4>
  <div class="form-grid-2">
    <div class="form-group"><label>Street Address</label><input id="pf_street" class="form-control" value="<?=e($prof['street_address']??'')?>"></div>
    <div class="form-group"><label>City</label><input id="pf_city" class="form-control" value="<?=e($prof['city']??'')?>"></div>
    <div class="form-group"><label>Region</label><input id="pf_region" class="form-control" value="<?=e($prof['region']??'')?>"></div>
    <div class="form-group"><label>Country</label><input id="pf_country" class="form-control" value="<?=e($prof['country']??'Ghana')?>"></div>
    <div class="form-group"><label>Postal Code</label><input id="pf_postal" class="form-control" value="<?=e($prof['postal_code']??'')?>"></div>
    <div class="form-group"><label>Office / Dispensary Location</label><input id="pf_office" class="form-control" value="<?=e($prof['office_location']??'')?>"></div>
  </div>
</div>
</div>

<!-- ════════════════ SECTION C: PROFESSIONAL ═════════════ -->
<div class="prof-section" id="prof-professional">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-briefcase"></i> Professional Profile</h3><button class="btn btn-sm btn-primary" onclick="saveProfessional()"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button></div>
  <div class="form-grid-2">
    <div class="form-group"><label>License Number</label><input id="pp_license" class="form-control" value="<?=e($prof['license_number']??'')?>"></div>
    <div class="form-group"><label>Issuing Body</label><input id="pp_issuer" class="form-control" value="<?=e($prof['license_issuing_body']??'')?>"></div>
    <div class="form-group"><label>License Expiry</label><input id="pp_expiry" type="date" class="form-control" value="<?=e($prof['license_expiry']??'')?>"></div>
    <div class="form-group"><label>Specialization</label><select id="pp_spec" class="form-control"><option value="">Select</option><?php foreach(['Clinical Pharmacy','Hospital Pharmacy','Community Pharmacy','Industrial Pharmacy','Pharmacology','Pharmaceutical Chemistry','Other'] as $s): ?><option <?=($prof['specialization']??'')===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Department</label><input id="pp_dept" class="form-control" value="<?=e($prof['department']??'Pharmacy')?>"></div>
    <div class="form-group"><label>Years of Experience</label><input id="pp_yoe" type="number" min="0" class="form-control" value="<?=(int)($prof['years_of_experience']??0)?>"></div>
    <div class="form-group"><label>Pharmacy School / University</label><input id="pp_school" class="form-control" value="<?=e($prof['pharmacy_school']??'')?>"></div>
    <div class="form-group"><label>Year of Graduation</label><input id="pp_gradyr" type="number" min="1950" max="2030" class="form-control" value="<?=e($prof['graduation_year']??'')?>"></div>
  </div>
  <div class="form-group" style="margin-top:1rem;"><label>Postgraduate Training</label><textarea id="pp_postgrad" class="form-control" rows="3"><?=e($prof['postgrad_training']??'')?></textarea></div>
  <div class="form-group" style="margin-top:1rem;"><label>Bio / Professional Summary</label><textarea id="pp_bio" class="form-control" rows="4"><?=e($prof['bio']??'')?></textarea></div>
</div>
</div>

<!-- ════════════════ SECTION D: QUALIFICATIONS ═══════════ -->
<div class="prof-section" id="prof-qualifications">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-graduation-cap"></i> Qualifications & Certifications</h3></div>

  <!-- Qualifications -->
  <h4 style="margin:1rem 0 .6rem;"><i class="fas fa-award"></i> Qualifications</h4>
  <button class="btn btn-sm btn-primary" onclick="openModal('addQualModal')"><span class="btn-text"><i class="fas fa-plus"></i> Add Qualification</span></button>
  <div class="table-responsive" style="margin-top:.8rem;">
    <table class="adm-table"><thead><tr><th>Degree</th><th>Institution</th><th>Year</th><th>Certificate</th><th>Actions</th></tr></thead><tbody id="qualTable">
    <?php foreach($quals as $q): ?>
      <tr id="qual-<?=$q['id']?>">
        <td data-label="Degree"><?=e($q['degree_name'])?></td>
        <td data-label="Institution"><?=e($q['institution'])?></td>
        <td data-label="Year"><?=e($q['year_awarded']??'')?></td>
        <td data-label="Certificate"><?php if($q['cert_file_path']): ?><a href="/RMU-Medical-Management-System/php/dashboards/pharmacy_download.php?type=document&id=<?=$q['id']?>" class="btn-icon btn btn-xs btn-outline"><span class="btn-text"><i class="fas fa-download"></i></span></a><?php else: ?>—<?php endif; ?></td>
        <td data-label="Actions"><button class="btn btn-xs btn-danger" onclick="deleteQual(<?=$q['id']?>)"><span class="btn-text"><i class="fas fa-trash"></i></span></button></td>
      </tr>
    <?php endforeach; if(!$quals): ?><tr><td colspan="5" class="text-center text-muted">No qualifications added</td></tr><?php endif; ?>
    </tbody></table>
  </div>

  <!-- Certifications -->
  <h4 style="margin:1.5rem 0 .6rem;"><i class="fas fa-certificate"></i> Certifications</h4>
  <button class="btn btn-sm btn-primary" onclick="openModal('addCertModal')"><span class="btn-text"><i class="fas fa-plus"></i> Add Certification</span></button>
  <div class="table-responsive" style="margin-top:.8rem;">
    <table class="adm-table"><thead><tr><th>Name</th><th>Issuing Body</th><th>Issued</th><th>Expires</th><th>Status</th><th>File</th><th>Actions</th></tr></thead><tbody id="certTable">
    <?php foreach($certs as $c):
      $cd = $c['expiry_date'] ? (int)((strtotime($c['expiry_date'])-time())/86400) : 999;
      $cb = $cd>60 ? 'badge-success' : ($cd>0 ? 'badge-warning' : 'badge-danger');
      $cl = $cd>60 ? 'Valid' : ($cd>0 ? "Expires in {$cd}d" : 'Expired');
    ?>
      <tr id="cert-<?=$c['id']?>">
        <td data-label="Name"><?=e($c['cert_name'])?></td>
        <td data-label="Issuing Body"><?=e($c['issuing_body']??'')?></td>
        <td data-label="Issued"><?=$c['issue_date']?date('d M Y',strtotime($c['issue_date'])):''?></td>
        <td data-label="Expires"><?=$c['expiry_date']?date('d M Y',strtotime($c['expiry_date'])):''?></td>
        <td data-label="Status"><span class="badge <?=$cb?>"><?=$cl?></span></td>
        <td data-label="File"><?php if($c['cert_file_path']): ?><a href="/RMU-Medical-Management-System/php/dashboards/pharmacy_download.php?type=document&id=<?=$c['id']?>" class="btn-icon btn btn-xs btn-outline"><span class="btn-text"><i class="fas fa-download"></i></span></a><?php else: ?>—<?php endif; ?></td>
        <td data-label="Actions"><button class="btn btn-xs btn-danger" onclick="deleteCert(<?=$c['id']?>)"><span class="btn-text"><i class="fas fa-trash"></i></span></button></td>
      </tr>
    <?php endforeach; if(!$certs): ?><tr><td colspan="7" class="text-center text-muted">No certifications added</td></tr><?php endif; ?>
    </tbody></table>
  </div>
</div>
</div>

<!-- ════════════════ SECTION E: SECURITY ═════════════════ -->
<div class="prof-section" id="prof-security">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-shield-alt"></i> Account & Security</h3></div>

  <div class="form-grid-2">
    <!-- Change Password -->
    <div class="section-card" style="padding:1.2rem;">
      <h4><i class="fas fa-key"></i> Change Password</h4>
      <div class="form-group"><label>Current Password</label><input id="sec_cur" type="password" class="form-control"></div>
      <div class="form-group"><label>New Password</label><input id="sec_new" type="password" class="form-control" oninput="checkPwStrength(this.value)"></div>
      <div id="pwStrengthBar" class="pw-strength"><div class="pw-bar"></div><span class="pw-label"></span></div>
      <div class="form-group"><label>Confirm New Password</label><input id="sec_confirm" type="password" class="form-control"></div>
      <button class="btn btn-primary btn-sm" onclick="changePharmPassword()"><span class="btn-text"><i class="fas fa-lock"></i> Update Password</span></button>
    </div>

    <!-- 2FA -->
    <div class="section-card" style="padding:1.2rem;">
      <h4><i class="fas fa-mobile-alt"></i> Two-Factor Authentication</h4>
      <p class="text-muted" style="font-size:.88rem;">Adds an extra layer of security to your account. When enabled, you'll need a verification code in addition to your password.</p>
      <label class="toggle-switch"><input type="checkbox" id="sec_2fa"><span class="toggle-slider"></span></label>
      <span id="sec_2fa_label" style="margin-left:.5rem;">Disabled</span>
    </div>
  </div>

  <!-- Active Sessions -->
  <h4 style="margin:1.5rem 0 .6rem;"><i class="fas fa-desktop"></i> Active Sessions</h4>
  <div class="table-responsive">
    <table class="adm-table"><thead><tr><th>Device</th><th>Browser</th><th>IP</th><th>Login Time</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach($sessions as $s): ?>
      <tr>
        <td data-label="Device"><?=e($s['device_info']??'Unknown')?></td>
        <td data-label="Browser"><?=e($s['browser']??'Unknown')?></td>
        <td data-label="IP"><code><?=e($s['ip_address']??'')?></code></td>
        <td data-label="Login Time"><?=date('d M Y, g:i A',strtotime($s['login_time']))?></td>
        <td data-label="Status"><?php if($s['is_current']): ?><span class="badge badge-success">Current</span><?php else: ?><span class="badge badge-secondary">Active</span><?php endif; ?></td>
        <td data-label="Action"><?php if(!$s['is_current']): ?><button class="btn btn-xs btn-danger" onclick="revokeSession(<?=$s['id']?>)"><span class="btn-text"><i class="fas fa-sign-out-alt"></i></span></button><?php else: ?>—<?php endif; ?></td>
      </tr>
    <?php endforeach; if(!$sessions): ?><tr><td colspan="6" class="text-center text-muted">No active sessions</td></tr><?php endif; ?>
    </tbody></table>
  </div>

  <!-- Activity Log -->
  <h4 style="margin:1.5rem 0 .6rem;"><i class="fas fa-history"></i> Account Activity Log</h4>
  <div class="table-responsive">
    <table class="adm-table"><thead><tr><th>Action</th><th>Type</th><th>IP</th><th>Date/Time</th></tr></thead><tbody>
    <?php foreach($actLog as $l): ?>
      <tr>
        <td data-label="Action"><?=e($l['action_description'])?></td>
        <td data-label="Type"><span class="badge badge-info"><?=e($l['action_type'])?></span></td>
        <td data-label="IP"><code><?=e($l['ip_address']??'')?></code></td>
        <td data-label="Date/Time"><?=date('d M Y, g:i A',strtotime($l['created_at']))?></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>

  <!-- Account Deactivation -->
  <div class="section-card" style="margin-top:1.5rem;padding:1.2rem;border-left:4px solid var(--danger);">
    <h4 style="color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Account Deactivation</h4>
    <p class="text-muted" style="font-size:.88rem;">Request account deactivation. Your data will be preserved and the request must be approved by an administrator.</p>
    <button class="btn btn-danger btn-sm" onclick="requestDeactivation()"><span class="btn-text"><i class="fas fa-user-times"></i> Request Deactivation</span></button>
  </div>
</div>
</div>

<!-- ════════════════ SECTION F: NOTIF PREFS ══════════════ -->
<div class="prof-section" id="prof-notifprefs">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-bell"></i> Notification Preferences</h3><button class="btn btn-sm btn-primary" onclick="saveNotifPrefs()"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button></div>
  <div class="notif-toggles">
    <?php $nPrefs = [
      'notif_new_prescription'=>['New prescription from doctor','fa-prescription'],
      'notif_low_stock'=>['Low stock alert','fa-boxes'],
      'notif_expiring_meds'=>['Medicine expiring soon','fa-clock'],
      'notif_purchase_orders'=>['Purchase order status','fa-truck'],
      'notif_refill_requests'=>['Dispensing confirmations','fa-check-circle'],
      'notif_system_alerts'=>['System announcements','fa-bullhorn']
    ]; foreach($nPrefs as $k=>[$label,$icon]): ?>
    <div class="notif-toggle-row">
      <span><i class="fas <?=$icon?>"></i> <?=$label?></span>
      <label class="toggle-switch"><input type="checkbox" id="np_<?=$k?>" <?=($pSettings[$k]??1)?'checked':''?>><span class="toggle-slider"></span></label>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="form-group" style="margin-top:1.5rem;">
    <label>Preferred Channel</label>
    <select id="np_channel" class="form-control" style="max-width:280px;">
      <?php foreach(['dashboard'=>'In-Dashboard','email'=>'Email','sms'=>'SMS','all'=>'All Channels'] as $v=>$l): ?>
        <option value="<?=$v?>" <?=($pSettings['preferred_channel']??'dashboard')===$v?'selected':''?>><?=$l?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>
</div>

<!-- ════════════════ SECTION G: DOCUMENTS ════════════════ -->
<div class="prof-section" id="prof-documents">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-folder-open"></i> Documents & Uploads</h3></div>
  <div class="upload-zone" id="docUploadZone"
    ondragover="event.preventDefault();this.classList.add('drag-over')"
    ondragleave="this.classList.remove('drag-over')"
    ondrop="event.preventDefault();this.classList.remove('drag-over');handleDocDrop(event)">
    <i class="fas fa-cloud-upload-alt fa-2x" style="color:var(--role-accent);"></i>
    <p>Drag & drop files or <label for="docFileInput" style="color:var(--role-accent);cursor:pointer;font-weight:600;">browse</label></p>
    <small class="text-muted">PDF, JPG, PNG — Max 5MB</small>
    <input type="file" id="docFileInput" accept=".pdf,.jpg,.jpeg,.png" style="display:none" onchange="uploadDocument(this)">
  </div>
  <div class="table-responsive" style="margin-top:1rem;">
    <table class="adm-table"><thead><tr><th>File Name</th><th>Type</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody id="docsTable">
    <?php foreach($docs as $d): ?>
      <tr id="doc-<?=$d['id']?>">
        <td data-label="File Name"><i class="fas fa-file"></i> <?=e($d['file_name'])?></td>
        <td data-label="Type"><?=e($d['file_type']??'—')?></td>
        <td data-label="Size"><?=round(($d['file_size']??0)/1024)?>KB</td>
        <td data-label="Uploaded"><?=date('d M Y',strtotime($d['uploaded_at']))?></td>
        <td data-label="Actions">
          <a href="/RMU-Medical-Management-System/php/dashboards/pharmacy_download.php?type=document&id=<?=$d['id']?>" class="btn-icon btn btn-xs btn-outline" title="Download"><span class="btn-text"><i class="fas fa-download"></i></span></a>
          <button class="btn btn-xs btn-danger" onclick="deleteDoc(<?=$d['id']?>)" title="Delete"><span class="btn-text"><i class="fas fa-trash"></i></span></button>
        </td>
      </tr>
    <?php endforeach; if(!$docs): ?><tr><td colspan="5" class="text-center text-muted">No documents uploaded</td></tr><?php endif; ?>
    </tbody></table>
  </div>
</div>
</div>

<!-- ════════════════ SECTION H: COMPLETENESS ═════════════ -->
<div class="prof-section" id="prof-completeness">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-tasks"></i> Profile Completeness</h3></div>
  <div class="big-progress">
    <div class="big-pct"><?=$compPct?>%</div>
    <div class="progress-bar-wrap" style="height:16px;"><div class="progress-bar-fill" style="width:<?=$compPct?>%;background:<?=$compPct>=80?'var(--success)':($compPct>=50?'var(--warning)':'var(--danger)')?>"></div></div>
  </div>
  <div class="comp-checklist">
    <?php
    $checkItems = [
      'personal_info'       =>['Personal Information','personal'],
      'professional_profile'=>['Professional Profile','professional'],
      'qualifications'      =>['Qualifications','qualifications'],
      'photo_uploaded'      =>['Profile Photo','header'],
      'security_setup'      =>['Security Setup','security'],
      'documents_uploaded'  =>['Documents','documents']
    ];
    foreach($checkItems as $key=>[$label,$sect]):
      $done = !empty($compRow[$key]);
    ?>
    <div class="comp-item">
      <span class="comp-check <?=$done?'done':''?>"><i class="fas <?=$done?'fa-check-circle':'fa-exclamation-circle'?>"></i></span>
      <span class="comp-label"><?=$label?></span>
      <?php if(!$done): ?><button class="btn btn-xs btn-outline" onclick="showProfSection('<?=$sect?>',null)"><span class="btn-text">Complete Now</span></button><?php else: ?><span class="badge badge-success" style="font-size:.75rem;">Done</span><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</div>

<!-- ════════════════ MODALS ══════════════════════════════ -->
<!-- Add Qualification -->
<div class="modal-bg glass-panel" id="addQualModal">
<div class="modal-content">
  <div class="modal-head"><h3>Add Qualification</h3><button onclick="closeModal('addQualModal')" class="btn btn-primary"><span class="btn-text"><i class="fas fa-times"></i></span></button></div>
  <div class="form-group"><label>Degree Name *</label><input id="aq_degree" class="form-control" placeholder="e.g. B.Pharm, PharmD"></div>
  <div class="form-group"><label>Institution *</label><input id="aq_inst" class="form-control"></div>
  <div class="form-group"><label>Year Awarded</label><input id="aq_year" type="number" min="1950" max="2030" class="form-control"></div>
  <div class="form-group"><label>Certificate File (optional)</label><input id="aq_file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="form-control"></div>
  <button class="btn btn-primary" onclick="addQualification()"><span class="btn-text"><i class="fas fa-plus"></i> Add</span></button>
</div>
</div>
<!-- Add Certification -->
<div class="modal-bg glass-panel" id="addCertModal">
<div class="modal-content">
  <div class="modal-head"><h3>Add Certification</h3><button onclick="closeModal('addCertModal')" class="btn btn-primary"><span class="btn-text"><i class="fas fa-times"></i></span></button></div>
  <div class="form-group"><label>Certification Name *</label><input id="ac_name" class="form-control"></div>
  <div class="form-group"><label>Issuing Body</label><input id="ac_issuer" class="form-control"></div>
  <div class="form-grid-2">
    <div class="form-group"><label>Issue Date</label><input id="ac_issued" type="date" class="form-control"></div>
    <div class="form-group"><label>Expiry Date</label><input id="ac_expiry" type="date" class="form-control"></div>
  </div>
  <div class="form-group"><label>Certificate File (optional)</label><input id="ac_file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="form-control"></div>
  <button class="btn btn-primary" onclick="addCertification()"><span class="btn-text"><i class="fas fa-plus"></i> Add</span></button>
</div>
</div>

<!-- ════════════════ PROFILE JS ══════════════════════════ -->
<script>
function showProfSection(id,btn){
  document.querySelectorAll('.prof-section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.prof-tab').forEach(b=>b.classList.remove('active'));
  const el=document.getElementById('prof-'+id);
  if(el) el.classList.add('active');
  if(btn) btn.classList.add('active');
}
/* ── Personal Info Save ─────────────────────────────────── */
async function savePersonalInfo(){
  if(!validateForm({pf_name:'Full Name'})) return;
  const r=await pharmAction({action:'update_profile_personal',
    full_name:document.getElementById('pf_name').value,
    date_of_birth:document.getElementById('pf_dob').value,
    gender:document.getElementById('pf_gender').value,
    nationality:document.getElementById('pf_nationality').value,
    marital_status:document.getElementById('pf_marital').value,
    national_id:document.getElementById('pf_nid').value,
    phone:document.getElementById('pf_phone').value,
    secondary_phone:document.getElementById('pf_phone2').value,
    email:document.getElementById('pf_email').value,
    personal_email:document.getElementById('pf_pemail').value,
    street_address:document.getElementById('pf_street').value,
    city:document.getElementById('pf_city').value,
    region:document.getElementById('pf_region').value,
    country:document.getElementById('pf_country').value,
    postal_code:document.getElementById('pf_postal').value,
    office_location:document.getElementById('pf_office').value
  });
  showToast(r.message||'Saved',r.success?'success':'error');
}
/* ── Professional Save ──────────────────────────────────── */
async function saveProfessional(){
  const r=await pharmAction({action:'update_profile_professional',
    license_number:document.getElementById('pp_license').value,
    license_issuing_body:document.getElementById('pp_issuer').value,
    license_expiry:document.getElementById('pp_expiry').value,
    specialization:document.getElementById('pp_spec').value,
    department:document.getElementById('pp_dept').value,
    years_of_experience:document.getElementById('pp_yoe').value,
    pharmacy_school:document.getElementById('pp_school').value,
    graduation_year:document.getElementById('pp_gradyr').value,
    postgrad_training:document.getElementById('pp_postgrad').value,
    bio:document.getElementById('pp_bio').value
  });
  showToast(r.message||'Saved',r.success?'success':'error');
}
/* ── Photo Upload ───────────────────────────────────────── */
async function uploadProfilePhoto(input){
  if(!input.files[0]) return;
  const file=input.files[0];
  if(!['image/jpeg','image/png'].includes(file.type)){showToast('Only JPG/PNG allowed','error');return;}
  if(file.size>2*1024*1024){showToast('Max 2MB','error');return;}
  const fd=new FormData();fd.append('photo',file);fd.append('action','upload_photo');fd.append('_csrf',CSRF_TOKEN);
  const res=await fetch('/RMU-Medical-Management-System/php/dashboards/pharmacy_actions.php',{method:'POST',body:fd});
  const r=await res.json();
  if(r.success && r.photo_url){
    const img=document.getElementById('profAvatarImg');
    if(img.tagName==='IMG') img.src=r.photo_url; else document.getElementById('profAvatarWrap').innerHTML='<img src="'+r.photo_url+'" alt="Photo" id="profAvatarImg">';
  }
  showToast(r.message||'Done',r.success?'success':'error');
}
/* ── Qualifications ─────────────────────────────────────── */
async function addQualification(){
  const degree=document.getElementById('aq_degree').value.trim();
  const inst=document.getElementById('aq_inst').value.trim();
  if(!degree||!inst){showToast('Degree and institution required','error');return;}
  const fd=new FormData();
  fd.append('action','add_qualification');fd.append('_csrf',CSRF_TOKEN);
  fd.append('degree_name',degree);fd.append('institution',inst);
  fd.append('year_awarded',document.getElementById('aq_year').value);
  const fileEl=document.getElementById('aq_file');
  if(fileEl.files[0]) fd.append('cert_file',fileEl.files[0]);
  const res=await fetch('/RMU-Medical-Management-System/php/dashboards/pharmacy_actions.php',{method:'POST',body:fd});
  const r=await res.json();
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),800);
}
async function deleteQual(id){
  if(!confirmAction('Delete this qualification?')) return;
  const r=await pharmAction({action:'delete_qualification',id:id});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) document.getElementById('qual-'+id)?.remove();
}
/* ── Certifications ─────────────────────────────────────── */
async function addCertification(){
  const name=document.getElementById('ac_name').value.trim();
  if(!name){showToast('Certification name required','error');return;}
  const fd=new FormData();
  fd.append('action','add_certification');fd.append('_csrf',CSRF_TOKEN);
  fd.append('cert_name',name);fd.append('issuing_body',document.getElementById('ac_issuer').value);
  fd.append('issue_date',document.getElementById('ac_issued').value);
  fd.append('expiry_date',document.getElementById('ac_expiry').value);
  const fileEl=document.getElementById('ac_file');
  if(fileEl.files[0]) fd.append('cert_file',fileEl.files[0]);
  const res=await fetch('/RMU-Medical-Management-System/php/dashboards/pharmacy_actions.php',{method:'POST',body:fd});
  const r=await res.json();
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),800);
}
async function deleteCert(id){
  if(!confirmAction('Delete this certification?')) return;
  const r=await pharmAction({action:'delete_certification',id:id});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) document.getElementById('cert-'+id)?.remove();
}
/* ── Password ───────────────────────────────────────────── */
function checkPwStrength(pw){
  let score=0;
  if(pw.length>=6)score++;if(pw.length>=10)score++;if(/[A-Z]/.test(pw))score++;if(/[0-9]/.test(pw))score++;if(/[^A-Za-z0-9]/.test(pw))score++;
  const levels=['','Weak','Fair','Strong','Very Strong','Very Strong'];
  const colors=['','#E74C3C','#F39C12','#27AE60','#2F80ED','#2F80ED'];
  const bar=document.querySelector('#pwStrengthBar .pw-bar');
  const lbl=document.querySelector('#pwStrengthBar .pw-label');
  if(bar){bar.style.width=(score*25)+'%';bar.style.background=colors[score]||'#ccc';}
  if(lbl) lbl.textContent=pw?levels[score]:'';
}
async function changePharmPassword(){
  const cur=document.getElementById('sec_cur').value;
  const nw=document.getElementById('sec_new').value;
  const cf=document.getElementById('sec_confirm').value;
  if(!cur||!nw){showToast('Fill in all fields','error');return;}
  if(nw!==cf){showToast('Passwords do not match','error');return;}
  if(nw.length<6){showToast('Minimum 6 characters','error');return;}
  const r=await pharmAction({action:'change_password',current_password:cur,new_password:nw});
  showToast(r.message,r.success?'success':'error');
  if(r.success){document.getElementById('sec_cur').value='';document.getElementById('sec_new').value='';document.getElementById('sec_confirm').value='';}
}
async function revokeSession(id){
  if(!confirmAction('Revoke this session?')) return;
  const r=await pharmAction({action:'revoke_session',session_id:id});
  showToast(r.message,r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),600);
}
async function requestDeactivation(){
  if(!confirmAction('Are you sure? This will send a deactivation request to the administrator.')) return;
  const r=await pharmAction({action:'request_deactivation'});
  showToast(r.message,r.success?'success':'error');
}
/* ── Notification Prefs ─────────────────────────────────── */
async function saveNotifPrefs(){
  const r=await pharmAction({action:'update_settings',
    notif_new_prescription:document.getElementById('np_notif_new_prescription').checked?1:0,
    notif_low_stock:document.getElementById('np_notif_low_stock').checked?1:0,
    notif_expiring_meds:document.getElementById('np_notif_expiring_meds').checked?1:0,
    notif_purchase_orders:document.getElementById('np_notif_purchase_orders').checked?1:0,
    notif_refill_requests:document.getElementById('np_notif_refill_requests').checked?1:0,
    notif_system_alerts:document.getElementById('np_notif_system_alerts').checked?1:0,
    preferred_channel:document.getElementById('np_channel').value
  });
  showToast(r.message||'Saved',r.success?'success':'error');
}
/* ── Documents ──────────────────────────────────────────── */
function handleDocDrop(e){ const files=e.dataTransfer.files; if(files[0]){ const input=document.getElementById('docFileInput'); input.files=files; uploadDocument(input); }}
async function uploadDocument(input){
  if(!input.files[0]) return;
  const file=input.files[0];
  const allowed=['application/pdf','image/jpeg','image/png'];
  if(!allowed.includes(file.type)){showToast('Only PDF, JPG, PNG allowed','error');return;}
  if(file.size>5*1024*1024){showToast('Max 5MB','error');return;}
  const fd=new FormData();fd.append('document',file);fd.append('action','upload_document');fd.append('_csrf',CSRF_TOKEN);
  const res=await fetch('/RMU-Medical-Management-System/php/dashboards/pharmacy_actions.php',{method:'POST',body:fd});
  const r=await res.json();
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),800);
}
async function deleteDoc(id){
  if(!confirmAction('Delete this document?')) return;
  const r=await pharmAction({action:'delete_document',id:id});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) document.getElementById('doc-'+id)?.remove();
}
</script>

<!-- ════════════════ PROFILE CSS ═════════════════════════ -->
<style>
.profile-subnav{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.5rem;padding-bottom:.8rem;border-bottom:1px solid var(--border);}
.prof-tab{padding:.5rem 1rem;border:none;background:transparent;border-radius:var(--radius);cursor:pointer;font-weight:500;color:var(--text-secondary);transition:.2s;}
.prof-tab:hover,.prof-tab.active{background:var(--role-accent-light);color:var(--role-accent);}
.prof-section{display:none;}.prof-section.active{display:block;animation:fadeIn .3s;}

/* Header Card */
.profile-header-card{display:flex;align-items:center;gap:2rem;flex-wrap:wrap;padding:2rem;background:var(--card-bg);border-radius:var(--radius-lg);border:1px solid var(--border);position:relative;}
.ph-photo-wrap{position:relative;flex-shrink:0;}
.ph-avatar{width:100px;height:100px;border-radius:50%;overflow:hidden;background:var(--bg-secondary);display:flex;align-items:center;justify-content:center;border:3px solid var(--role-accent);}
.ph-avatar img{width:100%;height:100%;object-fit:cover;}
.ph-upload-btn{position:absolute;bottom:4px;right:4px;width:30px;height:30px;border-radius:50%;background:var(--role-accent);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.8rem;box-shadow:0 2px 6px rgba(0,0,0,.15);}
.ph-info{flex:1;min-width:260px;}
.ph-info h2{margin:0 0 .4rem;}
.ph-meta{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;font-size:.88rem;color:var(--text-secondary);}
.status-dot{display:flex;align-items:center;gap:.3rem;}.status-dot::before{content:'';width:8px;height:8px;border-radius:50%;background:#aaa;}
.status-dot.online::before{background:#27AE60;}.status-dot.offline::before{background:#aaa;}
.progress-bar-wrap{width:100%;height:8px;background:var(--bg-secondary);border-radius:8px;overflow:hidden;}
.progress-bar-fill{height:100%;border-radius:8px;background:var(--role-accent);transition:width .4s;}

/* Section Cards */
.section-card{background:var(--card-bg);border-radius:var(--radius-lg);padding:1.8rem;border:1px solid var(--border);margin-bottom:1.2rem;}
.sc-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;flex-wrap:wrap;gap:.8rem;}
.sc-head h3{margin:0;font-size:1.15rem;}

/* Form Grid */
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
@media(max-width:700px){.form-grid-2{grid-template-columns:1fr;}}

/* Notification toggles */
.notif-toggles{display:flex;flex-direction:column;gap:.8rem;}
.notif-toggle-row{display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--border);}
.toggle-switch{position:relative;display:inline-block;width:44px;height:24px;}.toggle-switch input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#ccc;border-radius:24px;transition:.3s;}
.toggle-slider::before{content:'';position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;}
.toggle-switch input:checked+.toggle-slider{background:var(--role-accent);}
.toggle-switch input:checked+.toggle-slider::before{transform:translateX(20px);}

/* Password Strength */
.pw-strength{margin:.4rem 0;}.pw-bar{height:4px;border-radius:4px;transition:width .3s,background .3s;width:0;}
.pw-label{font-size:.8rem;font-weight:600;}

/* Upload zone */
.upload-zone{border:2px dashed var(--border);border-radius:var(--radius-lg);padding:2rem;text-align:center;transition:.2s;}
.upload-zone.drag-over{border-color:var(--role-accent);background:var(--role-accent-light);}

/* Completeness */
.big-progress{text-align:center;margin-bottom:1.5rem;}
.big-pct{font-size:2.5rem;font-weight:800;color:var(--role-accent);}
.comp-checklist{display:flex;flex-direction:column;gap:.6rem;}
.comp-item{display:flex;align-items:center;gap:.8rem;padding:.6rem;border-radius:var(--radius);background:var(--bg-secondary);}
.comp-check{font-size:1.2rem;color:var(--warning);}.comp-check.done{color:var(--success);}
.comp-label{flex:1;font-weight:500;}
</style>
</div><!-- /sec-profile -->
