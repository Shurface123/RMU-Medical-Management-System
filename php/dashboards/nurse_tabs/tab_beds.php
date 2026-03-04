<!-- ═══════════════════════════════════════════════════════════
     MODULE 4: BED & WARD MANAGEMENT — tab_beds.php
     ═══════════════════════════════════════════════════════════ -->
<?php
// ── All beds with current assignments ─────────────────────
$beds = dbSelect($conn,
    "SELECT bm.*, ba.patient_id, ba.admission_date, ba.status AS assign_status, ba.assigned_nurse_id,
            u.name AS patient_name, p.patient_id AS p_ref,
            iso.isolation_type, iso.status AS iso_status
     FROM bed_management bm
     LEFT JOIN bed_assignments ba ON ba.bed_id=bm.id AND ba.status='Active'
     LEFT JOIN patients p ON ba.patient_id=p.id
     LEFT JOIN users u ON p.user_id=u.id
     LEFT JOIN isolation_records iso ON iso.patient_id=ba.patient_id AND iso.status='Active'
     ORDER BY bm.ward, bm.bed_number ASC");

// ── Group by ward ─────────────────────────────────────────
$wards = [];
foreach($beds as $b){ $w = $b['ward'] ?? 'Unassigned'; $wards[$w][] = $b; }

// ── Pending transfers ─────────────────────────────────────
$transfers = dbSelect($conn,
    "SELECT bt.*, u.name AS patient_name, p.patient_id AS p_ref
     FROM bed_transfers bt
     JOIN patients p ON bt.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE bt.status IN('Requested','Approved')
     ORDER BY bt.created_at DESC LIMIT 50");

// ── Isolation records ─────────────────────────────────────
$isolations = dbSelect($conn,
    "SELECT ir.*, u.name AS patient_name
     FROM isolation_records ir
     JOIN patients p ON ir.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE ir.status='Active'
     ORDER BY ir.created_at DESC");

$bed_stats = [
  'total' => count($beds),
  'occupied' => count(array_filter($beds, fn($b) => !empty($b['patient_id']))),
  'available' => count(array_filter($beds, fn($b) => empty($b['patient_id']) && ($b['status']??'')!=='Maintenance')),
  'maintenance' => count(array_filter($beds, fn($b) => ($b['status']??'')==='Maintenance')),
  'isolation' => count(array_filter($beds, fn($b) => !empty($b['isolation_type']))),
];
?>
<div id="sec-patients" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-bed-pulse"></i> Beds & Ward Management</h2>
    <div style="display:flex;gap:.8rem;">
      <button class="btn btn-primary" onclick="openModal('transferModal')"><i class="fas fa-right-left"></i> Request Transfer</button>
      <button class="btn btn-outline" onclick="openModal('isolationModal')"><i class="fas fa-shield-virus"></i> Set Isolation</button>
    </div>
  </div>

  <!-- ── Bed Stats Strip ── -->
  <div class="adm-summary-strip" style="grid-template-columns:repeat(5,1fr);">
    <div class="adm-mini-card"><div class="adm-mini-card-num blue"><?=$bed_stats['total']?></div><div class="adm-mini-card-label">Total Beds</div></div>
    <div class="adm-mini-card"><div class="adm-mini-card-num teal"><?=$bed_stats['occupied']?></div><div class="adm-mini-card-label">Occupied</div></div>
    <div class="adm-mini-card"><div class="adm-mini-card-num green"><?=$bed_stats['available']?></div><div class="adm-mini-card-label">Available</div></div>
    <div class="adm-mini-card"><div class="adm-mini-card-num orange"><?=$bed_stats['maintenance']?></div><div class="adm-mini-card-label">Maintenance</div></div>
    <div class="adm-mini-card"><div class="adm-mini-card-num red"><?=$bed_stats['isolation']?></div><div class="adm-mini-card-label">Isolation</div></div>
  </div>

  <!-- ── WARD MAP (Visual Grid) ── -->
  <?php foreach($wards as $ward_name => $ward_beds):?>
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;">
      <i class="fas fa-hospital" style="color:var(--role-accent);"></i> <?=e($ward_name)?>
      <span class="badge badge-secondary"><?=count($ward_beds)?> beds</span>
    </h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem;">
      <?php foreach($ward_beds as $b):
        $has_patient = !empty($b['patient_id']);
        $is_maint = ($b['status']??'')==='Maintenance';
        $is_iso = !empty($b['isolation_type']);
        if($is_iso){ $bed_color='var(--warning)'; $bed_bg='var(--warning-light)'; $icon='fa-shield-virus'; }
        elseif($is_maint){ $bed_color='var(--text-muted)'; $bed_bg='var(--surface-2)'; $icon='fa-wrench'; }
        elseif($has_patient){ $bed_color='var(--primary)'; $bed_bg='var(--primary-light)'; $icon='fa-bed'; }
        else{ $bed_color='var(--success)'; $bed_bg='var(--success-light)'; $icon='fa-bed'; }
      ?>
        <div style="background:<?=$bed_bg?>;border:1.5px solid <?=$bed_color?>;border-radius:var(--radius-sm);padding:1rem;text-align:center;cursor:<?=$has_patient?'pointer':'default'?>;transition:var(--transition);"
             <?=$has_patient?'onclick="openBedDetail('.(int)$b['id'].','.(int)$b['patient_id'].')"':''?>
             title="<?=$has_patient?e($b['patient_name']):($is_maint?'Under Maintenance':'Available')?>">
          <i class="fas <?=$icon?>" style="font-size:1.8rem;color:<?=$bed_color?>;margin-bottom:.5rem;display:block;"></i>
          <div style="font-weight:700;font-size:1.3rem;color:<?=$bed_color?>;">Bed <?=e($b['bed_number']??$b['id'])?></div>
          <?php if($has_patient):?>
            <div style="font-size:1.05rem;color:var(--text-secondary);margin-top:.3rem;"><?=e($b['patient_name'])?></div>
            <?php if($is_iso):?><span class="badge badge-warning" style="margin-top:.4rem;font-size:.9rem;"><i class="fas fa-shield-virus"></i> <?=e($b['isolation_type'])?></span><?php endif;?>
          <?php elseif($is_maint):?>
            <div style="font-size:1rem;color:var(--text-muted);margin-top:.3rem;">Maintenance</div>
          <?php else:?>
            <div style="font-size:1rem;color:var(--success);margin-top:.3rem;">Available</div>
          <?php endif;?>
        </div>
      <?php endforeach;?>
    </div>
  </div>
  <?php endforeach;?>

  <!-- ── Pending Transfers ── -->
  <?php if(!empty($transfers)):?>
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-right-left" style="color:var(--warning);"></i> Pending Transfers</h3>
    <div class="table-responsive"><table class="data-table"><thead><tr>
      <th>Patient</th><th>From</th><th>To</th><th>Reason</th><th>Status</th><th>Date</th>
    </tr></thead><tbody>
    <?php foreach($transfers as $t):?>
      <tr>
        <td><?=e($t['patient_name'])?></td>
        <td><?=e($t['from_ward']??'—')?> / Bed <?=e($t['from_bed_id']??'—')?></td>
        <td><?=e($t['to_ward']??'—')?> / Bed <?=e($t['to_bed_id']??'—')?></td>
        <td><?=e(substr($t['transfer_reason']??'—',0,60))?></td>
        <td><span class="badge badge-<?=$t['status']==='Approved'?'success':'warning'?>"><?=e($t['status'])?></span></td>
        <td><?=$t['transfer_date']?date('d M h:i A',strtotime($t['transfer_date'])):'—'?></td>
      </tr>
    <?php endforeach;?></tbody></table></div>
  </div>
  <?php endif;?>

  <!-- ── Active Isolations ── -->
  <?php if(!empty($isolations)):?>
  <div class="info-card">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-shield-virus" style="color:var(--danger);"></i> Active Isolations</h3>
    <div class="table-responsive"><table class="data-table"><thead><tr>
      <th>Patient</th><th>Type</th><th>Reason</th><th>Precautions</th><th>Since</th><th>Actions</th>
    </tr></thead><tbody>
    <?php foreach($isolations as $iso):
      $precautions = json_decode($iso['precautions']??'[]',true) ?: [];
    ?>
      <tr>
        <td><?=e($iso['patient_name'])?></td>
        <td><span class="badge badge-warning"><?=e($iso['isolation_type'])?></span></td>
        <td><?=e(substr($iso['reason']??'—',0,80))?></td>
        <td><?php foreach($precautions as $pc):?><span class="badge badge-info" style="margin:.2rem;"><?=e($pc)?></span><?php endforeach;?></td>
        <td><?=date('d M Y',strtotime($iso['start_date']))?></td>
        <td><button class="btn btn-xs btn-outline" onclick="liftIsolation(<?=$iso['id']?>)"><i class="fas fa-unlock"></i> Lift</button></td>
      </tr>
    <?php endforeach;?></tbody></table></div>
  </div>
  <?php endif;?>
</div><!-- /sec-patients -->

<!-- ═══════ TRANSFER MODAL ═══════ -->
<div class="modal-bg" id="transferModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-right-left" style="color:var(--role-accent);"></i> Request Bed Transfer</h3><button class="modal-close" onclick="closeModal('transferModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Patient *</label>
      <select id="tf_patient" class="form-control"><option value="">Select Patient</option>
        <?php foreach($all_patients_for_vitals as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?></option><?php endforeach;?></select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>From Ward</label><input id="tf_from_ward" class="form-control" placeholder="Current ward"></div>
      <div class="form-group"><label>From Bed</label><input id="tf_from_bed" class="form-control" placeholder="Current bed #"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>To Ward *</label><input id="tf_to_ward" class="form-control" placeholder="Target ward"></div>
      <div class="form-group"><label>To Bed</label><input id="tf_to_bed" class="form-control" placeholder="Target bed #"></div>
    </div>
    <div class="form-group"><label>Reason *</label><textarea id="tf_reason" class="form-control" rows="3" placeholder="Reason for transfer..."></textarea></div>
    <button class="btn btn-primary" onclick="submitTransfer()" style="width:100%;"><i class="fas fa-paper-plane"></i> Submit Transfer Request</button>
  </div>
</div>

<!-- ═══════ ISOLATION MODAL ═══════ -->
<div class="modal-bg" id="isolationModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-shield-virus" style="color:var(--warning);"></i> Set Patient Isolation</h3><button class="modal-close" onclick="closeModal('isolationModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Patient *</label>
      <select id="iso_patient" class="form-control"><option value="">Select Patient</option>
        <?php foreach($all_patients_for_vitals as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?></option><?php endforeach;?></select>
    </div>
    <div class="form-group"><label>Isolation Type *</label>
      <select id="iso_type" class="form-control">
        <option value="">Select Type</option><option value="Contact">Contact</option><option value="Droplet">Droplet</option>
        <option value="Airborne">Airborne</option><option value="Protective">Protective</option><option value="Combined">Combined</option>
      </select>
    </div>
    <div class="form-group"><label>Reason *</label><textarea id="iso_reason" class="form-control" rows="2" placeholder="Clinical reason for isolation..."></textarea></div>
    <div class="form-group"><label>Required Precautions</label>
      <div style="display:flex;gap:1rem;flex-wrap:wrap;">
        <label style="cursor:pointer;"><input type="checkbox" class="iso_prec" value="gown"> Gown</label>
        <label style="cursor:pointer;"><input type="checkbox" class="iso_prec" value="gloves"> Gloves</label>
        <label style="cursor:pointer;"><input type="checkbox" class="iso_prec" value="N95"> N95 Mask</label>
        <label style="cursor:pointer;"><input type="checkbox" class="iso_prec" value="face_shield"> Face Shield</label>
        <label style="cursor:pointer;"><input type="checkbox" class="iso_prec" value="goggles"> Goggles</label>
        <label style="cursor:pointer;"><input type="checkbox" class="iso_prec" value="negative_pressure"> Negative Pressure</label>
      </div>
    </div>
    <button class="btn btn-warning" onclick="submitIsolation()" style="width:100%;"><i class="fas fa-shield-virus"></i> Activate Isolation</button>
  </div>
</div>

<!-- ═══════ BED DETAIL MODAL ═══════ -->
<div class="modal-bg" id="bedDetailModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-bed" style="color:var(--role-accent);"></i> Bed Details</h3><button class="modal-close" onclick="closeModal('bedDetailModal')"><i class="fas fa-times"></i></button></div>
    <div id="bedDetailContent"><p class="text-center text-muted" style="padding:3rem;">Loading...</p></div>
  </div>
</div>

<script>
async function openBedDetail(bedId,patientId){
  openModal('bedDetailModal');
  document.getElementById('bedDetailContent').innerHTML='<p class="text-center text-muted" style="padding:3rem;">Loading...</p>';
  const r=await nurseAction({action:'get_bedside_view',patient_id:patientId});
  if(!r.success){document.getElementById('bedDetailContent').innerHTML='<p class="text-center" style="color:var(--danger);">'+r.message+'</p>';return;}
  const p=r.data;
  document.getElementById('bedDetailContent').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;">
      <div><p><strong>Patient:</strong> ${p.name||'—'}</p><p><strong>ID:</strong> ${p.patient_id||'—'}</p>
      <p><strong>Admitted:</strong> ${p.admission_date||'—'}</p><p><strong>Doctor:</strong> ${p.doctor||'—'}</p></div>
      <div><p><strong>BP:</strong> ${p.bp||'—'}</p><p><strong>HR:</strong> ${p.hr||'—'} bpm</p>
      <p><strong>Temp:</strong> ${p.temp||'—'}°C</p><p><strong>SpO2:</strong> ${p.spo2||'—'}%</p>
      <p><strong>Last Vitals:</strong> ${p.vital_time||'Never'}</p></div>
    </div>`;
}

async function submitTransfer(){
  if(!validateForm({tf_patient:'Patient',tf_to_ward:'Target ward',tf_reason:'Reason'})) return;
  const r=await nurseAction({action:'request_bed_transfer',patient_id:document.getElementById('tf_patient').value,
    from_ward:document.getElementById('tf_from_ward').value,from_bed:document.getElementById('tf_from_bed').value,
    to_ward:document.getElementById('tf_to_ward').value,to_bed:document.getElementById('tf_to_bed').value,
    reason:document.getElementById('tf_reason').value});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success){closeModal('transferModal');setTimeout(()=>location.reload(),1200);}
}

async function submitIsolation(){
  if(!validateForm({iso_patient:'Patient',iso_type:'Isolation type',iso_reason:'Reason'})) return;
  const precs=[...document.querySelectorAll('.iso_prec:checked')].map(c=>c.value);
  const r=await nurseAction({action:'set_isolation',patient_id:document.getElementById('iso_patient').value,
    isolation_type:document.getElementById('iso_type').value,reason:document.getElementById('iso_reason').value,precautions:precs});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success){closeModal('isolationModal');setTimeout(()=>location.reload(),1200);}
}

async function liftIsolation(isoId){
  if(!confirmAction('Lift this isolation? Doctor will be notified.')) return;
  const r=await nurseAction({action:'lift_isolation',isolation_id:isoId});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),1200);
}
</script>
