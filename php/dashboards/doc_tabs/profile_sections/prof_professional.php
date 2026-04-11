<?php // SECTION C: Professional Profile ?>
<div id="prof-professional" class="prof-section" style="display:none;">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-stethoscope"></i> Professional Profile</h3></div>
    <div style="padding:2rem;">
      <?php
        $licExp=$prof['license_expiry_date']??'';
        $licWarn=(!empty($licExp) && strtotime($licExp)<=strtotime('+60 days') && strtotime($licExp)>time());
        $licExpired=(!empty($licExp) && strtotime($licExp)<time());
      ?>
      <?php if($licExpired):?>
      <div style="background:var(--danger-light);color:var(--danger);border-left:4px solid var(--danger);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;"><i class="fas fa-exclamation-triangle"></i> <strong>License Expired!</strong> Your medical license expired on <?=date('d M Y',strtotime($licExp))?>. Please renew immediately.</div>
      <?php elseif($licWarn):?>
      <div style="background:var(--warning-light);color:var(--warning);border-left:4px solid var(--warning);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;"><i class="fas fa-exclamation-circle"></i> License expires on <?=date('d M Y',strtotime($licExp))?>. Renew soon.</div>
      <?php endif;?>
      <form id="formProfessional" onsubmit="saveProfessional(event)">
        <div class="form-row">
          <div class="form-group"><label>Specialization</label>
            <select name="specialization" class="form-control">
              <option value="">Select</option>
              <?php foreach(['General Practice','Cardiology','Pediatrics','Surgery','Internal Medicine','Obstetrics & Gynecology','Ophthalmology','Dermatology','Psychiatry','Orthopedics','Neurology','Radiology','Anesthesiology','Pathology','Emergency Medicine','ENT','Urology','Oncology','Dental'] as $sp):?>
              <option value="<?=$sp?>" <?=($prof['specialization']??'')===$sp?'selected':''?>><?=$sp?></option>
              <?php endforeach;?>
            </select>
          </div>
          <div class="form-group"><label>Sub-specialization</label><input type="text" name="sub_specialization" class="form-control" value="<?=htmlspecialchars($prof['sub_specialization']??'')?>" placeholder="Optional"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Department</label>
            <select name="department_id" class="form-control">
              <option value="">Select</option>
              <?php foreach($departments as $dp):?><option value="<?=$dp['id']?>" <?=($prof['department_id']??0)==$dp['id']?'selected':''?>><?=htmlspecialchars($dp['name'])?></option><?php endforeach;?>
            </select>
          </div>
          <div class="form-group"><label>Designation / Title</label>
            <select name="designation" class="form-control">
              <option value="">Select</option>
              <?php foreach(['House Officer','Registrar','Senior Registrar','Consultant','Senior Consultant','Head of Department','Medical Director'] as $ds):?>
              <option value="<?=$ds?>" <?=($prof['designation']??'')===$ds?'selected':''?>><?=$ds?></option>
              <?php endforeach;?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Professional Title Badge</label><input type="text" name="professional_title" class="form-control" value="<?=htmlspecialchars($prof['professional_title']??'')?>" placeholder="e.g. Senior Consultant"></div>
          <div class="form-group"><label>Years of Experience</label><input type="number" name="experience_years" class="form-control" value="<?=(int)($prof['experience_years']??0)?>" min="0" max="60"></div>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0;">
        <h4 style="font-size:1.4rem;margin-bottom:1rem;"><i class="fas fa-id-card" style="color:var(--role-accent);"></i> License Details</h4>
        <div class="form-row">
          <div class="form-group"><label>License Number</label><input type="text" name="license_number" class="form-control" value="<?=htmlspecialchars($prof['license_number']??'')?>"></div>
          <div class="form-group"><label>Issuing Body</label><input type="text" name="license_issuing_body" class="form-control" value="<?=htmlspecialchars($prof['license_issuing_body']??'')?>" placeholder="e.g. Medical & Dental Council"></div>
        </div>
        <div class="form-group"><label>License Expiry Date</label><input type="date" name="license_expiry_date" class="form-control" value="<?=htmlspecialchars($licExp)?>"></div>
        <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0;">
        <h4 style="font-size:1.4rem;margin-bottom:1rem;"><i class="fas fa-graduation-cap" style="color:var(--role-accent);"></i> Education</h4>
        <div class="form-row">
          <div class="form-group"><label>Medical School / University</label><input type="text" name="medical_school" class="form-control" value="<?=htmlspecialchars($prof['medical_school']??'')?>"></div>
          <div class="form-group"><label>Year of Graduation</label><input type="number" name="graduation_year" class="form-control" value="<?=(int)($prof['graduation_year']??0)?>" min="1950" max="<?=date('Y')?>"></div>
        </div>
        <div class="form-group"><label>Postgraduate / Residency</label><textarea name="postgraduate_details" class="form-control" rows="3"><?=htmlspecialchars($prof['postgraduate_details']??'')?></textarea></div>
        <div class="form-group"><label>Languages Spoken</label>
          <?php $langs=json_decode($prof['languages_spoken']??'[]',true)?:[];?>
          <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.5rem;">
          <?php foreach(['English','French','Twi','Ga','Ewe','Hausa','Fante','Arabic','Spanish','Portuguese','German','Other'] as $l):?>
            <label style="display:flex;align-items:center;gap:.4rem;padding:.4rem .8rem;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;font-size:1.2rem;">
              <input type="checkbox" name="languages_spoken[]" value="<?=$l?>" <?=in_array($l,$langs)?'checked':''?>> <?=$l?>
            </label>
          <?php endforeach;?>
          </div>
        </div>
        <div class="form-group"><label>Professional Bio / Summary</label><textarea name="bio" class="form-control" rows="4" placeholder="Shown on public staff page"><?=htmlspecialchars($prof['bio']??'')?></textarea></div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-save"></i> Save Professional Profile</span></button>
      </form>
    </div>
  </div>
</div>
<script>
async function saveProfessional(e){
  e.preventDefault();
  const fd=new FormData(e.target),data={action:'update_professional'};
  fd.forEach((v,k)=>{if(k==='languages_spoken[]'){if(!data.languages_spoken)data.languages_spoken=[];data.languages_spoken.push(v);}else data[k]=v;});
  const res=await profAction(data);
  if(res.success)toast('Professional profile saved!');
  else toast(res.message||'Error','danger');
}
</script>
