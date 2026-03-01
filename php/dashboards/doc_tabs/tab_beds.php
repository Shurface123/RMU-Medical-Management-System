<?php // TAB: BED MANAGEMENT ?>
<div id="sec-beds" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-bed"></i> Bed Management</h2>
    <button onclick="openModal('modalBedRequest')" class="adm-btn adm-btn-primary"><i class="fas fa-plus"></i> Request Bed Assignment</button>
  </div>

  <?php
  $avail_beds_count=count(array_filter($beds,fn($b)=>$b['bed_status']==='Available'));
  $occup_beds_count=count(array_filter($beds,fn($b)=>$b['bed_status']==='Occupied'));
  $maint_beds_count=count(array_filter($beds,fn($b)=>$b['bed_status']==='Maintenance'));
  ?>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;">
    <div class="adm-mini-card"><div class="adm-mini-card-num green"><?=$avail_beds_count?></div><div class="adm-mini-card-label">Available</div></div>
    <div class="adm-mini-card"><div class="adm-mini-card-num orange"><?=$occup_beds_count?></div><div class="adm-mini-card-label">Occupied</div></div>
    <div class="adm-mini-card"><div class="adm-mini-card-num red"><?=$maint_beds_count?></div><div class="adm-mini-card-label">Maintenance</div></div>
  </div>

  <div class="filter-tabs">
    <button class="ftab active" onclick="filterBeds('all',this)">All Beds</button>
    <button class="ftab" onclick="filterBeds('Available',this)">Available</button>
    <button class="ftab" onclick="filterBeds('Occupied',this)">Occupied</button>
    <button class="ftab" onclick="filterBeds('Maintenance',this)">Maintenance</button>
  </div>

  <?php $wards=array_unique(array_column($beds,'ward')); ?>
  <?php foreach($wards as $ward): $ward_beds=array_filter($beds,fn($b)=>$b['ward']===$ward); ?>
  <div class="adm-card" style="margin-bottom:1.5rem;">
    <div class="adm-card-header"><h3><i class="fas fa-building"></i> <?=htmlspecialchars($ward)?></h3>
      <span style="font-size:1.2rem;color:var(--text-muted);"><?=count(array_filter($ward_beds,fn($b)=>$b['bed_status']==='Available'))?> available</span>
    </div>
    <div style="padding:1.5rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;">
      <?php foreach($ward_beds as $bed):
        $bc=match($bed['bed_status']){
          'Available'=>['#27AE60','#EAFAF1'],'Occupied'=>['#E74C3C','#FDEDEC'],
          'Maintenance'=>['#F39C12','#FEF9E7'],default=>['#7f8c8d','#f0f0f0']};
      ?>
      <div class="bed-card" data-bedstatus="<?=$bed['bed_status']?>" style="border:2px solid <?=$bc[0]?>;border-radius:12px;padding:1.2rem;background:<?=$bc[1]?>;text-align:center;transition:transform .2s;">
        <div style="font-size:1.5rem;margin-bottom:.4rem;" title="<?=$bed['bed_type']?>">
          <?=match($bed['bed_type']){
            'ICU'=>'🏥','Private'=>'🛏️','Semi-Private'=>'🏨',default=>'🛌'}?>
        </div>
        <div style="font-weight:700;font-size:1.3rem;"><?=htmlspecialchars($bed['bed_number'])?></div>
        <div style="font-size:1rem;color:#555;"><?=$bed['bed_type']?></div>
        <div style="font-size:1rem;font-weight:600;color:<?=$bc[0]?>;margin-top:.3rem;"><?=$bed['bed_status']?></div>
        <?php if($bed['patient_name']):?>
        <div style="font-size:1rem;color:#666;margin-top:.3rem;border-top:1px solid <?=$bc[0]?>22;padding-top:.3rem;"><?=htmlspecialchars($bed['patient_name'])?></div>
        <?php endif;?>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <?php endforeach;?>

  <?php if(empty($beds)):?>
  <div class="adm-card" style="text-align:center;padding:3rem;">
    <i class="fas fa-bed" style="font-size:3rem;opacity:.3;margin-bottom:1rem;display:block;"></i>
    <p style="color:var(--text-muted);">No bed data available.</p>
  </div>
  <?php endif;?>
</div>

<!-- Modal: Request Bed -->
<div class="modal-bg" id="modalBedRequest">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-bed" style="color:var(--role-accent);"></i> Request Bed Assignment</h3>
      <button class="modal-close" onclick="closeModal('modalBedRequest')">&times;</button>
    </div>
    <form id="formBedReq" onsubmit="submitBedReq(event)">
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
      <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-bed"></i> Assign Bed</button>
    </form>
  </div>
</div>
<script>
function filterBeds(status,btn){
  document.querySelectorAll('.filter-tabs .ftab').forEach(b=>b.classList.remove('active'));
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
