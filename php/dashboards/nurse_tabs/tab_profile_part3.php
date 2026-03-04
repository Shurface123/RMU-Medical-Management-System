<!-- ═══ SECTION H: NOTIFICATION & COMMUNICATION PREFERENCES ═══ -->
<?php
$notif_toggles = [
  'notif_new_task'=>'New task assigned by doctor',
  'notif_task_overdue'=>'Task overdue alert',
  'notif_med_reminder'=>'Medication administration reminders',
  'notif_vital_due'=>'Vital signs due alerts',
  'notif_abnormal_vital'=>'Abnormal vital flag notifications',
  'notif_shift_reminder'=>'Shift schedule reminders',
  'notif_handover'=>'Handover acknowledgement alerts',
  'notif_doctor_msg'=>'Doctor direct messages',
  'notif_emergency'=>'Emergency alert updates',
  'notif_cert_expiry'=>'Certification/license expiry warnings',
  'notif_system'=>'System announcements from admin',
];
?>
<div class="profile-section" id="section-notifications">
  <h3><i class="fas fa-bell" style="color:var(--warning);"></i> Notification Preferences</h3>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-bottom:1.2rem;">
    <?php foreach($notif_toggles as $nk=>$nl):?>
    <label style="display:flex;align-items:center;gap:.8rem;padding:.6rem .8rem;cursor:pointer;border:1px solid var(--border);border-radius:var(--radius-sm);">
      <input type="checkbox" class="notif_toggle" data-key="<?=$nk?>" <?=(int)($settings[$nk]??1)?'checked':''?> onchange="saveNotifToggles()">
      <span style="font-size:1.1rem;"><?=$nl?></span>
    </label>
    <?php endforeach;?>
  </div>
  <div class="profile-grid" style="margin-top:1rem;">
    <div><div class="pf-label">Preferred Channel</div>
      <select id="prefChannel" class="form-control" onchange="saveNotifToggles()">
        <?php foreach(['dashboard'=>'In-Dashboard','email'=>'Email','sms'=>'SMS','dashboard,email'=>'Dashboard + Email'] as $ck=>$cl):?>
          <option value="<?=$ck?>" <?=($settings['preferred_channel']??'dashboard')===$ck?'selected':''?>><?=$cl?></option>
        <?php endforeach;?>
      </select>
    </div>
    <div><div class="pf-label">Critical Alert Sound</div>
      <label style="display:flex;align-items:center;gap:.8rem;margin-top:.3rem;">
        <input type="checkbox" id="critSound" <?=(int)($settings['critical_sound_enabled']??1)?'checked':''?> onchange="saveNotifToggles()">
        <span style="font-size:1.15rem;">Enable sound for emergency/critical alerts</span>
      </label>
    </div>
    <div><div class="pf-label">Notification Language</div>
      <select id="prefNotifLang" class="form-control" onchange="saveNotifToggles()">
        <option value="en" <?=($settings['preferred_notif_lang']??'en')==='en'?'selected':''?>>English</option>
        <option value="fr" <?=($settings['preferred_notif_lang']??'')==='fr'?'selected':''?>>French</option>
        <option value="tw" <?=($settings['preferred_notif_lang']??'')==='tw'?'selected':''?>>Twi</option>
      </select>
    </div>
  </div>
</div>

<!-- ═══ SECTION I: DOCUMENTS & UPLOADS ═══ -->
<div class="profile-section" id="section-documents">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
    <h3><i class="fas fa-folder-open" style="color:var(--primary);"></i> Documents & Uploads</h3>
    <button class="btn btn-sm btn-outline" onclick="openModal('uploadDocModal')"><i class="fas fa-upload"></i> Upload</button>
  </div>
  <?php if(empty($nurse_docs)):?><p class="text-center text-muted" style="padding:1.5rem;">No documents uploaded</p>
  <?php else:?><div class="table-responsive"><table class="data-table"><thead><tr><th>Name</th><th>Type</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody>
  <?php foreach($nurse_docs as $d):?><tr>
    <td><i class="fas fa-file-<?=in_array(pathinfo($d['file_path']??'',PATHINFO_EXTENSION),['pdf'])?'pdf':((in_array(pathinfo($d['file_path']??'',PATHINFO_EXTENSION),['jpg','jpeg','png']))?'image':'alt')?>" style="color:var(--primary);"></i> <?=e($d['document_name'])?></td>
    <td><span class="badge badge-secondary"><?=e($d['document_type'])?></span></td>
    <td><?=round(($d['file_size']??0)/1024)?>KB</td>
    <td><?=date('d M Y',strtotime($d['uploaded_at']))?></td>
    <td class="action-btns">
      <a href="nurse_actions.php?action=secure_download&file_id=<?=$d['id']?>&source=nurse_documents&_csrf=<?=e($csrf_token)?>" class="btn btn-xs btn-outline"><i class="fas fa-download"></i></a>
      <button class="btn btn-xs btn-danger" onclick="deleteDoc(<?=$d['id']?>)"><i class="fas fa-trash"></i></button>
    </td>
  </tr><?php endforeach;?></tbody></table></div><?php endif;?>
</div>

<!-- ═══ SECTION J: PROFILE AUDIT & COMPLETENESS ENGINE ═══ -->
<?php
$checks = [
  ['key'=>'personal_info','label'=>'Personal Information','done'=>(int)($completeness['personal_info']??0),'section'=>'section-personal'],
  ['key'=>'professional_profile','label'=>'Professional Profile','done'=>(int)($completeness['professional_profile']??0),'section'=>'section-professional'],
  ['key'=>'qualifications','label'=>'Qualifications & Certifications','done'=>(int)($completeness['qualifications']??0),'section'=>'section-qualifications'],
  ['key'=>'shift_profile','label'=>'Shift & Availability Profile','done'=>(int)($completeness['shift_profile']??0),'section'=>'section-shift'],
  ['key'=>'profile_photo','label'=>'Profile Photo','done'=>(int)($completeness['profile_photo']??0),'section'=>'sec-profile'],
  ['key'=>'security_setup','label'=>'Security Setup (2FA)','done'=>(int)($completeness['security_setup']??0),'section'=>'section-security'],
  ['key'=>'documents_uploaded','label'=>'Documents (License + Certification)','done'=>(int)($completeness['documents_uploaded']??0),'section'=>'section-documents'],
];
?>
<div class="profile-section" id="section-completeness">
  <h3><i class="fas fa-tasks" style="color:var(--success);"></i> Profile Completeness — <?=$profile_pct?>%</h3>
  <div style="height:10px;background:var(--surface-2);border-radius:5px;overflow:hidden;margin-bottom:1.2rem;">
    <div style="height:100%;width:<?=$profile_pct?>%;background:linear-gradient(90deg,var(--role-accent),var(--success));border-radius:5px;transition:width .6s;"></div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:.8rem;">
    <?php foreach($checks as $ch):?>
    <div class="check-item">
      <i class="fas <?=$ch['done']?'fa-check-circle':'fa-exclamation-circle'?>" style="font-size:1.6rem;color:var(--<?=$ch['done']?'success':'warning'?>);"></i>
      <div><div style="font-weight:600;"><?=$ch['label']?></div>
        <?php if(!$ch['done']):?><a href="#" onclick="event.preventDefault();document.getElementById('<?=$ch['section']?>').scrollIntoView({behavior:'smooth'});" style="font-size:1rem;color:var(--role-accent);">Complete Now →</a>
        <?php else:?><span style="font-size:.95rem;color:var(--success);">Complete</span><?php endif;?>
      </div>
    </div>
    <?php endforeach;?>
  </div>
</div>

</div><!-- end sec-profile -->

<!-- ═══════ MODALS ═══════ -->
<div class="modal-bg" id="addQualModal"><div class="modal-box">
  <div class="modal-header"><h3>Add Qualification</h3><button class="modal-close" onclick="closeModal('addQualModal')"><i class="fas fa-times"></i></button></div>
  <div class="form-group"><label>Degree / Certificate *</label><input id="qual_degree" class="form-control" placeholder="e.g. BSc Nursing"></div>
  <div class="form-group"><label>Institution *</label><input id="qual_inst" class="form-control" placeholder="University name"></div>
  <div class="form-group"><label>Year *</label><input id="qual_year" type="number" class="form-control" placeholder="2020"></div>
  <div class="form-group"><label>Certificate File</label><input type="file" id="qual_file" class="form-control" accept=".pdf,.jpg,.png"></div>
  <button class="btn btn-primary" onclick="submitQual()" style="width:100%;"><i class="fas fa-save"></i> Save</button>
</div></div>

<div class="modal-bg" id="addCertModal"><div class="modal-box">
  <div class="modal-header"><h3>Add Certification</h3><button class="modal-close" onclick="closeModal('addCertModal')"><i class="fas fa-times"></i></button></div>
  <div class="form-group"><label>Certification Name *</label><input id="cert_name" class="form-control" placeholder="e.g. BLS Certification"></div>
  <div class="form-group"><label>Issuing Organization *</label><input id="cert_body" class="form-control" placeholder="e.g. AHA"></div>
  <div class="form-row"><div class="form-group"><label>Issue Date</label><input id="cert_issue" type="date" class="form-control"></div>
  <div class="form-group"><label>Expiry Date</label><input id="cert_expiry" type="date" class="form-control"></div></div>
  <div class="form-group"><label>Certificate File</label><input type="file" id="cert_file" class="form-control" accept=".pdf,.jpg,.png"></div>
  <button class="btn btn-primary" onclick="submitCert()" style="width:100%;"><i class="fas fa-save"></i> Save</button>
</div></div>

<div class="modal-bg" id="uploadDocModal"><div class="modal-box">
  <div class="modal-header"><h3>Upload Document</h3><button class="modal-close" onclick="closeModal('uploadDocModal')"><i class="fas fa-times"></i></button></div>
  <div class="form-group"><label>Document Type *</label>
    <select id="doc_type" class="form-control"><option value="License">Nursing License</option><option value="Certification">Certification</option><option value="Employment">Employment Letter</option><option value="Training">Training Certificate</option><option value="Contract">Contract</option><option value="Other">Other</option></select></div>
  <div class="form-group"><label>Document Name</label><input id="doc_name" class="form-control" placeholder="Document name"></div>
  <div class="form-group"><label>File * (PDF, DOC, JPG, PNG — max 10MB)</label><input type="file" id="doc_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png"></div>
  <button class="btn btn-primary" onclick="submitDoc()" style="width:100%;"><i class="fas fa-upload"></i> Upload</button>
</div></div>
