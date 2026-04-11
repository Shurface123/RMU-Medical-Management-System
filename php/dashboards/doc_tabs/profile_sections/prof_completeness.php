<?php // SECTION J: Profile Audit & Completeness Engine ?>
<div id="prof-completeness" class="prof-section" style="display:none;">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-tasks"></i> Profile Completeness Checklist</h3>
      <span style="font-size:1.8rem;font-weight:800;color:var(--role-accent);"><?=$comp_pct?>%</span>
    </div>
    <div style="padding:2rem;">
      <div style="height:10px;background:var(--border);border-radius:5px;overflow:hidden;margin-bottom:2rem;">
        <div style="height:100%;width:<?=$comp_pct?>%;background:linear-gradient(90deg,<?=$comp_pct>=80?'var(--success)':($comp_pct>=50?'var(--warning)':'var(--danger)')?>,var(--role-accent));border-radius:5px;transition:width .6s;"></div>
      </div>
      <?php
      $comp = is_array($completeness) ? $completeness : [];
      $sections=[
        ['personal_info','Personal Information','fa-user','personal','Complete your name, DOB, gender, phone, and email.'],
        ['professional_profile','Professional Profile','fa-stethoscope','professional','Add specialization, license number, and experience.'],
        ['qualifications','Qualifications & Certifications','fa-graduation-cap','qualifications','Add at least one qualification.'],
        ['availability_set','Availability Schedule','fa-calendar-alt','availability','Set your weekly availability for at least one day.'],
        ['photo_uploaded','Profile Photo','fa-camera','header','Upload a profile photo.'],
        ['security_setup','Security Setup','fa-shield-halved','security','Your password is set up.'],
        ['documents_uploaded','Documents Uploaded','fa-file-upload','documents','Upload your medical license or at least one document.'],
      ];
      foreach($sections as [$key,$label,$icon,$navTarget,$hint]):
        $done=!empty($comp[$key]);
      ?>
      <div style="display:flex;align-items:center;gap:1.2rem;padding:1rem 1.2rem;border-bottom:1px solid var(--border);">
        <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;
          background:<?=$done?'var(--success)':'var(--warning)'?>;color:#fff;">
          <i class="fas <?=$done?'fa-check':'fa-exclamation'?>"></i>
        </div>
        <div style="flex:1;">
          <div style="font-weight:600;font-size:1.3rem;color:<?=$done?'var(--text-primary)':'var(--warning)'?>;">
            <i class="fas <?=$icon?>" style="margin-right:.5rem;"></i><?=$label?>
          </div>
          <?php if(!$done):?><div style="font-size:1.1rem;color:var(--text-muted);"><?=$hint?></div><?php endif;?>
        </div>
        <?php if(!$done):?>
        <button class="btn btn-primary btn-sm" onclick="showProfSection('<?=$navTarget?>',document.querySelector('.prof-nav'))"><span class="btn-text">
          <i class="fas fa-arrow-right"></i> Complete Now
        </span></button>
        <?php else:?>
        <span style="color:var(--success);font-weight:600;font-size:1.2rem;"><i class="fas fa-check-circle"></i> Done</span>
        <?php endif;?>
      </div>
      <?php endforeach;?>

      <div style="text-align:center;margin-top:2rem;">
        <button class="btn btn-primary" onclick="refreshCompleteness()"><span class="btn-text"><i class="fas fa-sync-alt"></i> Recalculate</span></button>
      </div>
    </div>
  </div>
</div>
<script>
async function refreshCompleteness(){
  const res=await profAction({action:'get_completeness'});
  if(res.success){toast('Profile: '+res.pct+'% complete');location.reload();}
}
</script>
