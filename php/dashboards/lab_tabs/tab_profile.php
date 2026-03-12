<!-- ═══════════════ MODULE 13: LAB TECHNICIAN PROFILE ═══════════════ -->
<?php
if (!defined('BASE')) define('BASE', '/RMU-Medical-Management-System');
$prof_settings=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM lab_technician_settings WHERE technician_id=$tech_pk LIMIT 1"))??[];
// Profile completeness
$fields_check=['full_name','email','phone','specialization','designation','license_number','department_id','profile_photo','date_of_birth','nationality','gender'];
$filled=0;foreach($fields_check as $fld){if(!empty($tech_row[$fld]))$filled++;}
$completeness=round(($filled/count($fields_check))*100);
?>
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-user-circle" style="color:var(--role-accent);margin-right:.6rem;"></i> My Profile</h1>
    <p>Manage your personal and professional information</p>
  </div>
</div>

<!-- Profile Header Card -->
<div class="adm-card" style="margin-bottom:2rem;">
  <div class="adm-card-body">
    <div style="display:flex;gap:2.5rem;align-items:center;flex-wrap:wrap;">
      <!-- Avatar -->
      <div style="position:relative;">
        <div style="width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,var(--role-accent),#C39BD3);display:flex;align-items:center;justify-content:center;font-size:3.5rem;color:#fff;font-weight:800;overflow:hidden;border:4px solid var(--role-accent);">
          <?php if($avi):?><img src="<?=BASE?>/<?=e($avi)?>" style="width:100%;height:100%;object-fit:cover;">
          <?php else:?><?=strtoupper(substr($techName,0,2))?><?php endif;?>
        </div>
        <label style="position:absolute;bottom:0;right:0;width:34px;height:34px;border-radius:50%;background:var(--role-accent);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;border:3px solid var(--surface);font-size:1.1rem;" title="Change Photo">
          <i class="fas fa-camera"></i><input type="file" accept="image/*" style="display:none;" onchange="uploadProfilePhoto(this)">
        </label>
      </div>
      <!-- Info -->
      <div style="flex:1;min-width:200px;">
        <h2 style="font-size:2.2rem;font-weight:800;margin-bottom:.3rem;"><?=e($techName)?></h2>
        <p style="color:var(--role-accent);font-weight:600;font-size:1.35rem;margin-bottom:.3rem;"><?=e($tech_row['designation']??'Lab Technician')?></p>
        <p style="color:var(--text-muted);font-size:1.2rem;margin-bottom:.5rem;"><i class="fas fa-id-badge"></i> <?=e($tech_row['technician_id']??'LAB-TECH')?> &bull; <i class="fas fa-flask"></i> <?=e($tech_row['specialization']??'General Laboratory')?></p>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:.5rem;">
          <span class="adm-badge adm-badge-<?=($tech_row['availability_status']??'Available')==='Available'?'success':'warning'?>"><?=e($tech_row['availability_status']??'Available')?></span>
          <span class="adm-badge adm-badge-info"><?=e($tech_row['years_of_experience']??0)?> years exp</span>
        </div>
      </div>
      <!-- Completeness -->
      <div style="text-align:center;min-width:120px;">
        <div style="width:90px;height:90px;border-radius:50%;border:4px solid <?=$completeness>=80?'var(--success)':($completeness>=50?'var(--warning)':'var(--danger)')?>;display:flex;align-items:center;justify-content:center;margin:0 auto .5rem;">
          <span style="font-size:2rem;font-weight:800;color:<?=$completeness>=80?'var(--success)':($completeness>=50?'var(--warning)':'var(--danger)')?>"><?=$completeness?>%</span>
        </div>
        <span style="font-size:1.1rem;color:var(--text-muted);">Complete</span>
      </div>
    </div>
  </div>
</div>

<!-- Personal + Professional Info Side-by-Side -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">
  <!-- Personal Info -->
  <div class="adm-card" style="margin:0;">
    <div class="adm-card-header"><h3><i class="fas fa-user"></i> Personal Information</h3><button class="adm-btn adm-btn-sm adm-btn-ghost" onclick="openModal('editPersonalModal')"><i class="fas fa-edit"></i> Edit</button></div>
    <div class="adm-card-body" style="font-size:1.25rem;">
      <div style="display:grid;gap:.8rem;">
        <div><strong>Full Name:</strong> <?=e($techName)?></div>
        <div><strong>Email:</strong> <?=e($tech_row['email']??$tech_row['user_email']??'—')?></div>
        <div><strong>Phone:</strong> <?=e($tech_row['phone']??$tech_row['user_phone']??'—')?></div>
        <div><strong>Date of Birth:</strong> <?=e($tech_row['date_of_birth']??$tech_row['user_dob']??'—')?></div>
        <div><strong>Gender:</strong> <?=e($tech_row['gender']??'—')?></div>
        <div><strong>Nationality:</strong> <?=e($tech_row['nationality']??'—')?></div>
        <div><strong>Address:</strong> <?=e($tech_row['address']??'—')?></div>
      </div>
    </div>
  </div>
  <!-- Professional Info -->
  <div class="adm-card" style="margin:0;">
    <div class="adm-card-header"><h3><i class="fas fa-briefcase"></i> Professional Information</h3><button class="adm-btn adm-btn-sm adm-btn-ghost" onclick="openModal('editProfessionalModal')"><i class="fas fa-edit"></i> Edit</button></div>
    <div class="adm-card-body" style="font-size:1.25rem;">
      <div style="display:grid;gap:.8rem;">
        <div><strong>Designation:</strong> <?=e($tech_row['designation']??'Lab Technician')?></div>
        <div><strong>Specialization:</strong> <?=e($tech_row['specialization']??'—')?></div>
        <div><strong>License Number:</strong> <?=e($tech_row['license_number']??'—')?></div>
        <div><strong>License Expiry:</strong> <?=$tech_row['license_expiry']?date('d M Y',strtotime($tech_row['license_expiry'])):'—'?></div>
        <div><strong>Department:</strong> <?=e($tech_row['department_name']??'—')?></div>
        <div><strong>Experience:</strong> <?=$tech_row['years_of_experience']??0?> years</div>
        <div><strong>Member Since:</strong> <?=$tech_row['member_since']?date('d M Y',strtotime($tech_row['member_since'])):'—'?></div>
        <div><strong>Status:</strong> <span class="adm-badge adm-badge-<?=($tech_row['status']??'active')==='active'?'success':'warning'?>"><?=e(ucfirst($tech_row['status']??'active'))?></span></div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Personal Modal -->
<div class="modal-bg" id="editPersonalModal"><div class="modal-box wide">
  <div class="modal-header"><h3><i class="fas fa-user"></i> Edit Personal Info</h3><button class="modal-close" onclick="closeModal('editPersonalModal')">&times;</button></div>
  <div class="form-row"><div class="form-group"><label>Full Name</label><input id="prf_name" class="form-control" value="<?=e($techName)?>"></div><div class="form-group"><label>Email</label><input id="prf_email" type="email" class="form-control" value="<?=e($tech_row['email']??$tech_row['user_email']??'')?>"></div></div>
  <div class="form-row"><div class="form-group"><label>Phone</label><input id="prf_phone" class="form-control" value="<?=e($tech_row['phone']??$tech_row['user_phone']??'')?>"></div><div class="form-group"><label>Date of Birth</label><input id="prf_dob" type="date" class="form-control" value="<?=e($tech_row['date_of_birth']??$tech_row['user_dob']??'')?>"></div></div>
  <div class="form-row"><div class="form-group"><label>Gender</label><select id="prf_gender" class="form-control"><option>Male</option><option>Female</option><option value="<?=e($tech_row['gender']??'')?>"selected><?=e($tech_row['gender']??'—')?></option></select></div><div class="form-group"><label>Nationality</label><input id="prf_nat" class="form-control" value="<?=e($tech_row['nationality']??'')?>"></div></div>
  <div class="form-group"><label>Address</label><textarea id="prf_addr" class="form-control" rows="2"><?=e($tech_row['address']??'')?></textarea></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="updatePersonal()"><i class="fas fa-save"></i> Save Changes</button>
</div></div>

<!-- Edit Professional Modal -->
<div class="modal-bg" id="editProfessionalModal"><div class="modal-box wide">
  <div class="modal-header"><h3><i class="fas fa-briefcase"></i> Edit Professional Info</h3><button class="modal-close" onclick="closeModal('editProfessionalModal')">&times;</button></div>
  <div class="form-row"><div class="form-group"><label>Designation</label><select id="prf_desig" class="form-control"><option>Lab Technician</option><option>Senior Lab Technician</option><option>Lab Scientist</option><option>Chief Lab Technician</option><option>Lab Manager</option><option value="<?=e($tech_row['designation']??'')?>" selected><?=e($tech_row['designation']??'Lab Technician')?></option></select></div><div class="form-group"><label>Specialization</label><select id="prf_spec" class="form-control"><option>General Laboratory</option><option>Hematology</option><option>Biochemistry</option><option>Microbiology</option><option>Immunology</option><option>Histopathology</option><option>Parasitology</option><option value="<?=e($tech_row['specialization']??'')?>" selected><?=e($tech_row['specialization']??'General Laboratory')?></option></select></div></div>
  <div class="form-row"><div class="form-group"><label>License Number</label><input id="prf_license" class="form-control" value="<?=e($tech_row['license_number']??'')?>"></div><div class="form-group"><label>License Expiry</label><input id="prf_licexp" type="date" class="form-control" value="<?=e($tech_row['license_expiry']??'')?>"></div></div>
  <div class="form-group"><label>Years of Experience</label><input id="prf_exp" type="number" class="form-control" value="<?=$tech_row['years_of_experience']??0?>"></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="updateProfessional()"><i class="fas fa-save"></i> Save Changes</button>
</div></div>

<script>
async function uploadProfilePhoto(input){
  if(!input.files[0])return;const fd=new FormData();fd.append('action','update_profile_photo');fd.append('photo',input.files[0]);
  const r=await labAction(fd);showToast(r.message,r.success?'success':'error');if(r.success) setTimeout(()=>location.reload(),800);
}
async function updatePersonal(){
  const r=await labAction({action:'update_personal_info',name:document.getElementById('prf_name').value,email:document.getElementById('prf_email').value,phone:document.getElementById('prf_phone').value,dob:document.getElementById('prf_dob').value,gender:document.getElementById('prf_gender').value,nationality:document.getElementById('prf_nat').value,address:document.getElementById('prf_addr').value});
  showToast(r.message,r.success?'success':'error');if(r.success){closeModal('editPersonalModal');setTimeout(()=>location.reload(),800);}
}
async function updateProfessional(){
  const r=await labAction({action:'update_professional_info',designation:document.getElementById('prf_desig').value,specialization:document.getElementById('prf_spec').value,license_number:document.getElementById('prf_license').value,license_expiry:document.getElementById('prf_licexp').value,years_of_experience:document.getElementById('prf_exp').value});
  showToast(r.message,r.success?'success':'error');if(r.success){closeModal('editProfessionalModal');setTimeout(()=>location.reload(),800);}
}
</script>
