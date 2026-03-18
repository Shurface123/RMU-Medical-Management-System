<!-- ═══════════════════════════════════════════════════════════
     MODULE 8: IV & FLUID MANAGEMENT — tab_fluids.php
     ═══════════════════════════════════════════════════════════ -->
<?php
$iv_records = dbSelect($conn,
    "SELECT iv.*, u.user_name AS patient_name, p.patient_id AS p_ref
     FROM iv_fluid_records iv
     JOIN patients pt ON iv.patient_id=pt.id JOIN users u ON pt.user_id=u.id
     JOIN patients p ON iv.patient_id=p.id
     WHERE iv.nurse_id=?
     ORDER BY FIELD(iv.status,'Running','Paused','Stopped','Completed'), iv.start_time DESC
     LIMIT 100","i",[$nurse_pk]);

$fluid_balances = dbSelect($conn,
    "SELECT fb.*, u.user_name AS patient_name
     FROM fluid_balance fb
     JOIN patients p ON fb.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE fb.record_date=?
     ORDER BY fb.created_at DESC LIMIT 100","s",[$today]);

// Group fluid balance by patient
$fb_by_patient = [];
foreach($fluid_balances as $fb){
  $pid = $fb['patient_id'];
  if(!isset($fb_by_patient[$pid])) $fb_by_patient[$pid]=['name'=>$fb['patient_name'],'intake'=>0,'output'=>0,'records'=>[]];
  $fb_by_patient[$pid]['intake'] += (float)($fb['total_intake_ml']??0);
  $fb_by_patient[$pid]['output'] += (float)($fb['total_output_ml']??0);
  $fb_by_patient[$pid]['records'][] = $fb;
}
?>
<div id="sec-fluids" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-droplet"></i> IV & Fluid Management</h2>
    <div style="display:flex;gap:.8rem;">
      <button class="btn btn-primary" onclick="openModal('newIVModal')"><i class="fas fa-plus"></i> New IV</button>
      <button class="btn btn-outline" onclick="openModal('fluidIntakeModal')"><i class="fas fa-glass-water"></i> Record Fluid</button>
    </div>
  </div>

  <!-- ── Active IV Drips ── -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-droplet" style="color:var(--primary);"></i> IV Fluid Tracking</h3>
    <div class="table-responsive"><table class="adm-table"><thead><tr>
      <th>Patient</th><th>Fluid Type</th><th>Volume</th><th>Rate</th><th>Infused</th><th>Remaining</th><th>Status</th><th>Start</th><th>Actions</th>
    </tr></thead><tbody>
    <?php if(empty($iv_records)):?>
      <tr><td colspan="9" class="text-center text-muted" style="padding:3rem;">No IV records</td></tr>
    <?php else: foreach($iv_records as $iv):
      $vol = (float)($iv['volume_ordered']??0);
      $infused = (float)($iv['volume_infused']??0);
      $remaining = max(0, $vol - $infused);
      $pct = $vol>0 ? round(($infused/$vol)*100) : 0;
      $low_fluid = ($remaining < 50 && $iv['status']==='Running');
      $st_colors = ['Running'=>'success','Paused'=>'warning','Stopped'=>'danger','Completed'=>'secondary'];
    ?>
      <tr <?=$low_fluid?'style="border-left:3px solid var(--danger);"':''?>>
        <td><?=e($iv['patient_name'])?><br><small class="text-muted"><?=e($iv['p_ref']??'')?></small></td>
        <td><strong><?=e($iv['fluid_type'])?></strong></td>
        <td><?=$vol?> mL</td>
        <td><?=e($iv['infusion_rate']??'—')?> mL/hr</td>
        <td>
          <div style="display:flex;align-items:center;gap:.5rem;">
            <div style="flex:1;height:6px;background:var(--surface-2);border-radius:3px;overflow:hidden;"><div style="height:100%;width:<?=$pct?>%;background:var(--<?=$low_fluid?'danger':'success'?>);border-radius:3px;"></div></div>
            <span style="font-size:1.1rem;font-weight:600;"><?=$infused?> mL</span>
          </div>
        </td>
        <td style="font-weight:600;color:var(--<?=$low_fluid?'danger':'text-primary'?>);"><?=$remaining?> mL <?=$low_fluid?'⚠️':''?></td>
        <td><span class="badge badge-<?=$st_colors[$iv['status']]??'secondary'?>"><?=e($iv['status'])?></span></td>
        <td><?=$iv['start_time']?date('d M h:i A',strtotime($iv['start_time'])):'—'?></td>
        <td class="action-btns">
          <?php if($iv['status']==='Running'):?>
            <button class="btn btn-xs btn-outline" onclick="updateIV(<?=$iv['id']?>,'update')" title="Update infused"><i class="fas fa-edit"></i></button>
            <button class="btn btn-xs btn-warning" onclick="updateIV(<?=$iv['id']?>,'Paused')" title="Pause"><i class="fas fa-pause"></i></button>
            <button class="btn btn-xs btn-danger" onclick="updateIV(<?=$iv['id']?>,'Stopped')" title="Stop"><i class="fas fa-stop"></i></button>
          <?php elseif($iv['status']==='Paused'):?>
            <button class="btn btn-xs btn-success" onclick="updateIV(<?=$iv['id']?>,'Running')" title="Resume"><i class="fas fa-play"></i></button>
            <button class="btn btn-xs btn-danger" onclick="updateIV(<?=$iv['id']?>,'Stopped')" title="Stop"><i class="fas fa-stop"></i></button>
          <?php endif;?>
        </td>
      </tr>
    <?php endforeach; endif;?></tbody></table></div>
  </div>

  <!-- ── Daily Fluid Balance ── -->
  <div class="info-card">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-balance-scale" style="color:var(--warning);"></i> Today's Fluid Balance</h3>
    <?php if(empty($fb_by_patient)):?>
      <p class="text-center text-muted" style="padding:2rem;">No fluid records today</p>
    <?php else:?>
    <div class="cards-grid">
      <?php foreach($fb_by_patient as $pid => $fb):
        $net = $fb['intake'] - $fb['output'];
        $net_color = ($net > 500) ? 'warning' : (($net < -500) ? 'danger' : 'success');
      ?>
        <div class="info-card" style="border-left:3px solid var(--<?=$net_color?>);">
          <h4 style="margin-bottom:.8rem;"><?=e($fb['name'])?></h4>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;text-align:center;">
            <div><div style="font-size:1.6rem;font-weight:700;color:var(--primary);"><?=$fb['intake']?></div><div style="font-size:1rem;color:var(--text-secondary);">Intake (mL)</div></div>
            <div><div style="font-size:1.6rem;font-weight:700;color:var(--warning);"><?=$fb['output']?></div><div style="font-size:1rem;color:var(--text-secondary);">Output (mL)</div></div>
            <div><div style="font-size:1.6rem;font-weight:700;color:var(--<?=$net_color?>);"><?=($net>=0?'+':'').$net?></div><div style="font-size:1rem;color:var(--text-secondary);">Net Balance</div></div>
          </div>
        </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>
  </div>
</div>

<!-- ═══════ NEW IV MODAL ═══════ -->
<div class="modal-bg" id="newIVModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-droplet" style="color:var(--primary);"></i> New IV Fluid Order</h3><button class="modal-close" onclick="closeModal('newIVModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Patient *</label>
      <select id="iv_patient" class="form-control"><option value="">Select Patient</option>
        <?php foreach($all_patients_for_vitals as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?></option><?php endforeach;?></select>
    </div>
    <div class="form-group"><label>Fluid Type *</label>
      <select id="iv_fluid" class="form-control"><option value="">Select</option>
        <option>Normal Saline (0.9% NaCl)</option><option>Dextrose 5% (D5W)</option><option>Ringer's Lactate</option>
        <option>Half Normal Saline (0.45% NaCl)</option><option>Dextrose 10%</option><option>Packed RBCs</option>
        <option>Fresh Frozen Plasma</option><option>Albumin</option><option>Other</option></select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Volume (mL) *</label><input id="iv_volume" type="number" class="form-control" placeholder="e.g. 1000"></div>
      <div class="form-group"><label>Infusion Rate (mL/hr) *</label><input id="iv_rate" type="number" class="form-control" placeholder="e.g. 125"></div>
    </div>
    <div class="form-group"><label>Notes</label><textarea id="iv_notes" class="form-control" rows="2"></textarea></div>
    <button class="btn btn-primary" onclick="submitNewIV()" style="width:100%;"><i class="fas fa-play"></i> Start IV</button>
  </div>
</div>

<!-- ═══════ FLUID INTAKE/OUTPUT MODAL ═══════ -->
<div class="modal-bg" id="fluidIntakeModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-glass-water" style="color:var(--primary);"></i> Record Fluid</h3><button class="modal-close" onclick="closeModal('fluidIntakeModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Patient *</label>
      <select id="fl_patient" class="form-control"><option value="">Select Patient</option>
        <?php foreach($all_patients_for_vitals as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?></option><?php endforeach;?></select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Type *</label>
        <select id="fl_type" class="form-control"><option value="Intake">Intake</option><option value="Output">Output</option></select>
      </div>
      <div class="form-group"><label>Category</label>
        <select id="fl_cat" class="form-control">
          <option value="IV Fluid">IV Fluid</option><option value="Oral">Oral</option><option value="Nasogastric">Nasogastric</option>
          <option value="Urine">Urine</option><option value="Drain">Drain</option><option value="Vomit">Vomit</option><option value="Other">Other</option></select>
      </div>
    </div>
    <div class="form-group"><label>Amount (mL) *</label><input id="fl_amount" type="number" class="form-control" placeholder="e.g. 250"></div>
    <div class="form-group"><label>Notes</label><input id="fl_notes" class="form-control" placeholder="Optional notes"></div>
    <button class="btn btn-primary" onclick="submitFluid()" style="width:100%;"><i class="fas fa-save"></i> Save</button>
  </div>
</div>

<!-- ═══════ UPDATE IV MODAL ═══════ -->
<div class="modal-bg" id="updateIVModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-edit" style="color:var(--role-accent);"></i> Update IV</h3><button class="modal-close" onclick="closeModal('updateIVModal')"><i class="fas fa-times"></i></button></div>
    <input type="hidden" id="uiv_id">
    <div class="form-group"><label>Volume Infused (mL)</label><input id="uiv_infused" type="number" class="form-control"></div>
    <div class="form-group"><label>New Rate (mL/hr, optional)</label><input id="uiv_rate" type="number" class="form-control"></div>
    <div class="form-group"><label>Reason for change</label><input id="uiv_reason" class="form-control" placeholder="Optional"></div>
    <button class="btn btn-primary" onclick="submitIVUpdate()" style="width:100%;"><i class="fas fa-save"></i> Update</button>
  </div>
</div>

<script>
async function submitNewIV(){
  if(!validateForm({iv_patient:'Patient',iv_fluid:'Fluid type',iv_volume:'Volume',iv_rate:'Rate'})) return;
  const r=await nurseAction({action:'start_iv',patient_id:document.getElementById('iv_patient').value,
    fluid_type:document.getElementById('iv_fluid').value,volume:document.getElementById('iv_volume').value,
    rate:document.getElementById('iv_rate').value,notes:document.getElementById('iv_notes').value});
  showToast(r.message||'IV Started',r.success?'success':'error');
  if(r.success){closeModal('newIVModal');setTimeout(()=>location.reload(),1200);}
}

async function submitFluid(){
  if(!validateForm({fl_patient:'Patient',fl_amount:'Amount'})) return;
  const r=await nurseAction({action:'record_fluid',patient_id:document.getElementById('fl_patient').value,
    type:document.getElementById('fl_type').value,category:document.getElementById('fl_cat').value,
    amount:document.getElementById('fl_amount').value,notes:document.getElementById('fl_notes').value});
  showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success){closeModal('fluidIntakeModal');setTimeout(()=>location.reload(),1200);}
}

function updateIV(ivId,action){
  if(action==='update'){
    document.getElementById('uiv_id').value=ivId;
    document.getElementById('uiv_infused').value='';
    document.getElementById('uiv_rate').value='';
    openModal('updateIVModal');
  } else {
    const reason=prompt(`Reason for ${action} IV:`);
    if(reason===null) return;
    nurseAction({action:'update_iv_status',iv_id:ivId,new_status:action,reason:reason}).then(r=>{
      showToast(r.message||'Updated',r.success?'success':'error');
      if(r.success) setTimeout(()=>location.reload(),1000);
    });
  }
}

async function submitIVUpdate(){
  const r=await nurseAction({action:'update_iv_infused',iv_id:document.getElementById('uiv_id').value,
    volume_infused:document.getElementById('uiv_infused').value,
    new_rate:document.getElementById('uiv_rate').value,reason:document.getElementById('uiv_reason').value});
  showToast(r.message||'Updated',r.success?'success':'error');
  if(r.success){closeModal('updateIVModal');setTimeout(()=>location.reload(),1000);}
}
</script>
