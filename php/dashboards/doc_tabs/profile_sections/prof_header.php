<?php // SECTION A: Profile Header / Identity Card ?>
<div id="prof-header" class="prof-section" style="display:block;">
  <div class="adm-card" style="margin-bottom:1.5rem;overflow:hidden;">
    <!-- Gradient banner -->
    <div style="height:120px;background:linear-gradient(135deg,var(--role-accent),#2F80ED);position:relative;"></div>
    <div style="padding:0 2.5rem 2rem;margin-top:-60px;position:relative;">
      <div style="display:flex;align-items:flex-end;gap:2rem;flex-wrap:wrap;">
        <!-- Photo -->
        <div style="position:relative;">
          <?php $img=$prof['profile_image']??''; $hasPhoto=(!empty($img)&&$img!=='default-avatar.png'); ?>
          <div id="profPhotoWrap" style="width:120px;height:120px;border-radius:50%;border:4px solid var(--surface);overflow:hidden;background:linear-gradient(135deg,var(--role-accent),#2F80ED);display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-md);">
            <?php if($hasPhoto):?>
            <img id="profPhotoImg" src="/RMU-Medical-Management-System/<?=htmlspecialchars($img)?>" style="width:100%;height:100%;object-fit:cover;">
            <?php else:?>
            <span id="profPhotoInit" style="color:#fff;font-size:3.5rem;font-weight:800;"><?=strtoupper(substr($prof['name']??'D',0,1))?></span>
            <?php endif;?>
          </div>
          <label for="photoInput" style="position:absolute;bottom:4px;right:4px;width:32px;height:32px;border-radius:50%;background:var(--role-accent);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:var(--shadow-sm);font-size:1.2rem;" title="Change Photo"><i class="fas fa-camera"></i></label>
          <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="uploadPhoto(this)">
        </div>
        <!-- Name & Info -->
        <div style="flex:1;min-width:200px;padding-bottom:.5rem;">
          <h2 style="font-size:2.2rem;font-weight:800;margin:0;"><?=htmlspecialchars($prof['name']??'Doctor')?></h2>
          <div style="display:flex;flex-wrap:wrap;gap:.8rem;margin-top:.5rem;align-items:center;">
            <span class="adm-badge adm-badge-teal"><?=htmlspecialchars($prof['specialization']??'General Practice')?></span>
            <?php if(!empty($prof['professional_title'])):?><span class="adm-badge adm-badge-primary"><?=htmlspecialchars($prof['professional_title'])?></span><?php endif;?>
            <span class="adm-badge adm-badge-info"><i class="fas fa-id-badge"></i> <?=htmlspecialchars($prof['doctor_id']??'N/A')?></span>
          </div>
          <div style="font-size:1.2rem;color:var(--text-muted);margin-top:.6rem;">
            <?php if($prof['experience_years']??0):?><i class="fas fa-briefcase-medical"></i> <?=$prof['experience_years']?> yrs experience &middot; <?php endif;?>
            <i class="fas fa-calendar"></i> Member since <?=date('M Y',strtotime($prof['member_since']??'now'))?>
            <?php if($prof['last_login_at']??''):?> &middot; <i class="fas fa-clock"></i> Last login <?=date('d M H:i',strtotime($prof['last_login_at']))?><?php endif;?>
          </div>
        </div>
        <!-- Status Toggle -->
        <div style="display:flex;flex-direction:column;gap:.6rem;align-items:flex-end;">
          <select id="availStatusSel" onchange="toggleStatus(this.value)" style="padding:.5rem 1.2rem;border-radius:20px;font-weight:600;font-size:1.2rem;border:2px solid var(--border);background:var(--surface);cursor:pointer;">
            <?php foreach(['Online','Busy','Offline'] as $st):?>
            <option value="<?=$st?>" <?=$avail_status===$st?'selected':''?>><?=$st?></option>
            <?php endforeach;?>
          </select>
          <div id="statusDot" style="width:12px;height:12px;border-radius:50%;background:<?=$avail_status==='Online'?'var(--success)':($avail_status==='Busy'?'var(--warning)':'var(--text-muted)')?>"></div>
        </div>
      </div>
      <!-- Completion bar -->
      <div style="margin-top:1.5rem;background:var(--surface-2);border-radius:10px;padding:1rem 1.4rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
          <span style="font-weight:600;font-size:1.2rem;">Profile Completion</span>
          <span style="font-weight:700;font-size:1.3rem;color:var(--role-accent);"><?=$comp_pct?>%</span>
        </div>
        <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden;">
          <div style="height:100%;width:<?=$comp_pct?>%;background:linear-gradient(90deg,var(--role-accent),#2F80ED);border-radius:4px;transition:width .5s;"></div>
        </div>
        <?php if($comp_pct<100):?>
        <div style="font-size:1.1rem;color:var(--text-muted);margin-top:.5rem;">
          <?php
          $missing=[];
          $comp = is_array($completeness) ? $completeness : [];
          if(empty($comp['personal_info'])) $missing[]='Personal Info';
          if(empty($comp['professional_profile'])) $missing[]='Professional Profile';
          if(empty($comp['qualifications'])) $missing[]='Qualifications';
          if(empty($comp['availability_set'])) $missing[]='Availability';
          if(empty($comp['photo_uploaded'])) $missing[]='Profile Photo';
          if(empty($comp['documents_uploaded'])) $missing[]='Documents';
          if(!empty($missing)):?>Complete your <?=implode(', ',$missing)?> to reach 100%.<?php endif;?>
        </div>
        <?php endif;?>
      </div>
    </div>
  </div>
</div>

<script>
async function uploadPhoto(inp){
  if(!inp.files[0])return;
  const fd=new FormData(); fd.append('action','upload_photo'); fd.append('photo',inp.files[0]);
  const res=await profAction(fd,true);
  if(res.success){
    toast('Profile photo updated!');
    const w=document.getElementById('profPhotoWrap');
    w.innerHTML='<img id="profPhotoImg" src="'+res.photo_url+'" style="width:100%;height:100%;object-fit:cover;">';
  } else toast(res.message||'Upload failed','danger');
}
async function toggleStatus(v){
  const res=await profAction({action:'toggle_status',status:v});
  if(res.success){
    toast('Status: '+v);
    const dot=document.getElementById('statusDot');
    dot.style.background=v==='Online'?'var(--success)':v==='Busy'?'var(--warning)':'var(--text-muted)';
  } else toast(res.message||'Error','danger');
}
</script>
