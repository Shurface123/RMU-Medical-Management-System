<?php // TAB: BED MANAGEMENT ?>
<div id="sec-beds" class="dash-section">

<style>
.adm-tab-group { display:flex; gap:.8rem; flex-wrap:wrap; margin-bottom:1.8rem; padding-bottom:1rem; border-bottom:1px solid var(--border); }
.ftab-v2 { 
  display:inline-flex;align-items:center;gap:.6rem;padding:.55rem 1.4rem;border-radius:20px;
  font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);
  background:var(--surface);color:var(--text-secondary);transition:all 0.3s ease;
}
.ftab-v2:hover { background:var(--primary-light);color:var(--primary);border-color:var(--primary);transform:translateY(-1px); }
.ftab-v2.active { background:var(--primary);color:#fff;border-color:var(--primary);box-shadow:0 4px 12px rgba(47,128,237,.25); }

.bed-stat-card { background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:2rem 1.8rem;position:relative;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.04); }
.bed-stat-card::before { content:'';position:absolute;top:0;left:0;right:0;height:5px;border-radius:16px 16px 0 0;background:var(--card-accent,var(--role-accent));}
.bed-stat-card .sc-icon { width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin-bottom:1.2rem; }
.bed-stat-card .sc-val { font-size:3rem;font-weight:800;line-height:1;color:var(--text-primary);margin-bottom:.5rem; letter-spacing:-1px; }
.bed-stat-card .sc-lbl { font-size:1.2rem;font-weight:600;color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.04em; }

.ward-card-v2 { background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,.04); overflow:hidden; margin-bottom:2rem; }
.ward-card-header { padding:1.8rem 2rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface); }
.premium-modal { border-radius:18px; border:1px solid rgba(255,255,255,0.1); }
</style>

  <div class="sec-header">
    <h2><i class="fas fa-bed" style="color:var(--primary);"></i> Bed Management Routing</h2>
    <div style="display:flex;gap:.7rem;flex-wrap:wrap;">
      <button onclick="openModal('modalBedRequest')" class="btn btn-primary" style="border-radius:12px;padding:.8rem 1.4rem;"><span class="btn-text"><i class="fas fa-plus"></i> Request Bed Assignment</span></button>
    </div>
  </div>

  <?php
  $avail_beds_count=count(array_filter($beds,fn($b)=>$b['bed_status']==='Available'));
  $occup_beds_count=count(array_filter($beds,fn($b)=>$b['bed_status']==='Occupied'));
  $maint_beds_count=count(array_filter($beds,fn($b)=>$b['bed_status']==='Maintenance'));
  ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem;">
    <div class="bed-stat-card" style="--card-accent:var(--success);">
      <div class="sc-icon" style="background:var(--success-light);color:var(--success);"><i class="fas fa-bed"></i></div>
      <div class="sc-val"><?=$avail_beds_count?></div><div class="sc-lbl">Available</div>
    </div>
    <div class="bed-stat-card" style="--card-accent:var(--warning);">
      <div class="sc-icon" style="background:var(--warning-light);color:var(--warning);"><i class="fas fa-bed-pulse"></i></div>
      <div class="sc-val"><?=$occup_beds_count?></div><div class="sc-lbl">Occupied</div>
    </div>
    <div class="bed-stat-card" style="--card-accent:var(--danger);">
      <div class="sc-icon" style="background:var(--danger-light);color:var(--danger);"><i class="fas fa-screwdriver-wrench"></i></div>
      <div class="sc-val"><?=$maint_beds_count?></div><div class="sc-lbl">Maintenance</div>
    </div>
  </div>

  <div class="adm-tab-group">
    <button class="ftab-v2 active" onclick="filterBeds('all',this)"><i class="fas fa-list"></i> All Beds</button>
    <button class="ftab-v2" onclick="filterBeds('Available',this)"><i class="fas fa-check-circle" style="color:var(--success);"></i> Available</button>
    <button class="ftab-v2" onclick="filterBeds('Occupied',this)"><i class="fas fa-user-injured" style="color:var(--warning);"></i> Occupied</button>
    <button class="ftab-v2" onclick="filterBeds('Maintenance',this)"><i class="fas fa-wrench" style="color:var(--danger);"></i> Maintenance</button>
  </div>

  <?php $wards=array_unique(array_column($beds,'ward')); ?>
  <?php foreach($wards as $ward): $ward_beds=array_filter($beds,fn($b)=>$b['ward']===$ward); ?>
  <div class="ward-card-v2">
    <div class="ward-card-header"><h3 style="margin:0;"><i class="fas fa-building" style="color:var(--primary);margin-right:.5rem;"></i> <?=htmlspecialchars($ward)?></h3>
      <span class="adm-badge" style="background:var(--success-light);color:var(--success);font-size:1.1rem;padding:.5rem 1rem;"><?=count(array_filter($ward_beds,fn($b)=>$b['bed_status']==='Available'))?> available</span>
    </div>
    <div style="padding:2rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.5rem;background:var(--surface-2);">
      <?php foreach($ward_beds as $bed):
        $bc=match($bed['bed_status']){
          'Available'=>['#27AE60','var(--surface)'],'Occupied'=>['#E74C3C','var(--surface)'],
          'Maintenance'=>['#F39C12','var(--surface)'],default=>['#7f8c8d','var(--surface)']};
      ?>
      <div class="bed-card" data-bedstatus="<?=$bed['bed_status']?>" style="border-top:4px solid <?=$bc[0]?>;border-radius:12px;padding:1.5rem;background:<?=$bc[1]?>;box-shadow:0 4px 10px rgba(0,0,0,0.05);position:relative;transition:transform .2s;">
        <div style="font-size:2rem;margin-bottom:.5rem;" title="<?=$bed['bed_type']?>">
          <?=match($bed['bed_type']){
            'ICU'=>'🏥','Private'=>'🛏️','Semi-Private'=>'🏨',default=>'🛌'}?>
        </div>
        <div style="font-weight:800;font-size:1.5rem;color:var(--text-primary);"><?=htmlspecialchars($bed['bed_number'])?></div>
        <div style="font-size:1.1rem;color:var(--text-secondary);"><?=$bed['bed_type']?></div>
        <div style="position:absolute;top:1.5rem;right:1.5rem;">
           <span style="display:inline-block;padding:.3rem .8rem;border-radius:6px;font-size:1rem;font-weight:700;background:<?=$bc[0]?>1A;color:<?=$bc[0]?>;"><?=$bed['bed_status']?></span>
        </div>
        <?php if($bed['patient_name']):?>
        <div style="font-size:1.1rem;color:var(--text-secondary);margin-top:.8rem;border-top:1px solid var(--border);padding-top:.8rem;"><i class="fas fa-procedures" style="color:<?=$bc[0]?>;margin-right:.4rem;"></i><?=htmlspecialchars($bed['patient_name'])?></div>
        <?php endif;?>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <?php endforeach;?>

  <?php if(empty($beds)):?>
  <div class="ward-card-v2" style="text-align:center;padding:4rem 2rem;">
    <i class="fas fa-bed" style="font-size:4rem;opacity:.2;margin-bottom:1rem;display:block;"></i>
    <p style="color:var(--text-muted);font-size:1.2rem;">No bed data available in the directory.</p>
  </div>
  <?php endif;?>
</div>

<!-- Modal: Request Bed -->
<div class="modal-bg" id="modalBedRequest">
  <div class="modal-box wide premium-modal">
    <div class="modal-header">
      <h3><i class="fas fa-bed" style="color:#fff;"></i> Request Bed Assignment</h3>
      <button class="modal-close" onclick="closeModal('modalBedRequest')">&times;</button>
    </div>
    <form id="formBedReq" onsubmit="submitBedReq(event)" style="padding:1rem;">
      <div class="form-group"><label>Patient</label>
        <select class="form-control" name="patient_id" required>
          <option value="">-- Select Patient --</option>
          <?php foreach($patients as $pt):?>
          <option value="<?=$pt['id']?>"><?=htmlspecialchars($pt['name'])?> (<?=htmlspecialchars($pt['p_ref'])?>)</option>
          <?php endforeach;?>
        </select>
      </div>
      <div class="form-group"><label>Select Bed</label>
        <select class="form-control" name="bed_id" required>
          <option value="">-- Select Available Bed --</option>
          <?php foreach($beds as $bed): if($bed['bed_status']!=='Available') continue;?>
          <option value="<?=$bed['bed_pk']?>"><?=htmlspecialchars($bed['bed_number'])?> — <?=htmlspecialchars($bed['ward'])?> (<?=$bed['bed_type']?>) — GH₵<?=$bed['daily_rate']?>/day</option>
          <?php endforeach;?>
        </select>
      </div>
      <div class="form-group"><label>Admission Reason</label><textarea name="reason" class="form-control" rows="3" placeholder="Reason for hospitalization…" required></textarea></div>
      <div class="form-group"><label>Admission Date</label><input type="datetime-local" name="admission_date" class="form-control" value="<?=date('Y-m-d\TH:i')?>" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-bed"></i> Assign Bed</span></button>
    </form>
  </div>
</div>
<script>
function filterBeds(status,btn){
  document.querySelectorAll('#sec-beds .ftab-v2').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  document.querySelectorAll('.bed-card').forEach(c=>{
    c.style.display=(status==='all'||c.dataset.bedstatus===status)?'':'none';
  });
}
async function submitBedReq(e){
  e.preventDefault();
  const fd=new FormData(e.target), data={action:'assign_bed'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await docAction(data);
  if(res.success){toast('Bed assigned successfully!');closeModal('modalBedRequest');setTimeout(()=>location.reload(),1200);}
  else toast(res.message||'Error','danger');
}
</script>
