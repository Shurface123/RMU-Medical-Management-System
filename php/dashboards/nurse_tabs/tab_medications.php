<!-- ═══════════════════════════════════════════════════════════
     MODULE 3: MEDICATION ADMINISTRATION — tab_medications.php
     ═══════════════════════════════════════════════════════════ -->
<?php
// ── Today's medication schedule ───────────────────────────
$med_schedule = dbSelect($conn,
    "SELECT ma.*, u.name AS patient_name, p.patient_id AS p_ref
     FROM medication_administration ma
     JOIN patients pt ON ma.patient_id=pt.id JOIN users u ON pt.user_id=u.id
     JOIN patients p ON ma.patient_id=p.id
     WHERE ma.nurse_id=? AND DATE(ma.scheduled_time)=?
     ORDER BY ma.scheduled_time ASC","is",[$nurse_pk,$today]);

// ── Active medication schedules ───────────────────────────
$active_schedules = dbSelect($conn,
    "SELECT ms.*, u.name AS patient_name, p.patient_id AS p_ref
     FROM medication_schedules ms
     JOIN patients pt ON ms.patient_id=pt.id JOIN users u ON pt.user_id=u.id
     JOIN patients p ON ms.patient_id=p.id
     WHERE ms.status='Active' AND ms.start_date<=? AND (ms.end_date IS NULL OR ms.end_date>=?)
     ORDER BY u.name ASC","ss",[$today,$today]);

// ── Active prescriptions (from doctor) ────────────────────
$active_rx = dbSelect($conn,
    "SELECT pr.id, pr.prescription_id AS rx_ref, pr.status, pr.prescription_date,
            u.name AS patient_name, p.patient_id AS p_ref, ud.name AS doctor_name,
            pi.medicine_name, pi.dosage, pi.frequency, pi.instructions, pi.id AS item_id
     FROM prescriptions pr
     JOIN patients p ON pr.patient_id=p.id JOIN users u ON p.user_id=u.id
     JOIN doctors d ON pr.doctor_id=d.id JOIN users ud ON d.user_id=ud.id
     LEFT JOIN prescription_items pi ON pi.prescription_id=pr.id
     WHERE pr.status IN('Pending','Active','Partially Dispensed')
     ORDER BY pr.prescription_date DESC LIMIT 200");
?>
<div id="sec-medications" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-pills"></i> Medication Administration</h2>
    <div style="display:flex;gap:.8rem;">
      <button class="btn btn-primary" onclick="openModal('administerMedModal')"><i class="fas fa-syringe"></i> Administer Medication</button>
    </div>
  </div>

  <!-- ── Filter Tabs ── -->
  <div class="filter-tabs">
    <span class="ftab active" onclick="filterMeds('all',this)">All Today</span>
    <span class="ftab" onclick="filterMeds('Pending',this)">⏳ Pending</span>
    <span class="ftab" onclick="filterMeds('Administered',this)">✅ Administered</span>
    <span class="ftab" onclick="filterMeds('Missed',this)">❌ Missed</span>
    <span class="ftab" onclick="filterMeds('Refused',this)">🚫 Refused</span>
    <span class="ftab" onclick="filterMeds('Held',this)">⏸️ Held</span>
  </div>

  <!-- ── Today's Schedule Table ── -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-calendar-day" style="color:var(--role-accent);"></i> Today's Medication Schedule</h3>
    <div class="table-responsive">
      <table class="adm-table" id="medTable">
        <thead><tr>
          <th>Time</th><th>Patient</th><th>Medicine</th><th>Dosage</th><th>Route</th><th>Status</th><th>Administered At</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if(empty($med_schedule)):?>
          <tr><td colspan="8" class="text-center text-muted" style="padding:3rem;">No medications scheduled for today</td></tr>
        <?php else: foreach($med_schedule as $ms):
          $stime = $ms['scheduled_time'] ? date('h:i A',strtotime($ms['scheduled_time'])) : '—';
          $is_overdue = ($ms['status']==='Pending' && $ms['scheduled_time'] && strtotime($ms['scheduled_time']) < time());
          $status_map = ['Pending'=>'badge-warning','Administered'=>'badge-success','Missed'=>'badge-danger','Refused'=>'badge-danger','Held'=>'badge-info','Late'=>'badge-warning'];
          $badge_cls = $status_map[$ms['status']] ?? 'badge-secondary';
        ?>
          <tr data-med-status="<?=e($ms['status'])?>" <?=$is_overdue?'style="border-left:3px solid var(--danger);"':''?>>
            <td><strong><?=$stime?></strong><?=$is_overdue?' <span class="badge badge-danger" style="font-size:.9rem;">OVERDUE</span>':''?></td>
            <td><?=e($ms['patient_name'])?><br><small class="text-muted"><?=e($ms['p_ref']??'')?></small></td>
            <td><strong><?=e($ms['medicine_name'])?></strong></td>
            <td><?=e($ms['dosage']??'—')?></td>
            <td><?=e($ms['route']??'Oral')?></td>
            <td><span class="badge <?=$badge_cls?>"><?=e($ms['status'])?></span></td>
            <td><?=$ms['administered_at']?date('h:i A',strtotime($ms['administered_at'])):'—'?></td>
            <td class="action-btns">
              <?php if($ms['status']==='Pending'):?>
                <button class="btn btn-xs btn-success" onclick="confirmAdminister(<?=$ms['id']?>,'<?=e($ms['medicine_name'])?>','<?=e($ms['patient_name'])?>','<?=e($ms['dosage']??'')?>')" title="Administer"><i class="fas fa-check"></i></button>
                <button class="btn btn-xs btn-warning" onclick="markMedStatus(<?=$ms['id']?>,'Held')" title="Hold"><i class="fas fa-pause"></i></button>
                <button class="btn btn-xs btn-danger" onclick="markMedStatus(<?=$ms['id']?>,'Missed')" title="Mark Missed"><i class="fas fa-times"></i></button>
              <?php endif;?>
            </td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── Active Prescriptions from Doctors ── -->
  <div class="info-card">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-prescription" style="color:var(--primary);"></i> Active Prescriptions</h3>
    <div class="table-responsive">
      <table class="data-table">
        <thead><tr><th>Rx ID</th><th>Patient</th><th>Doctor</th><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
        <?php if(empty($active_rx)):?>
          <tr><td colspan="8" class="text-center text-muted" style="padding:2rem;">No active prescriptions</td></tr>
        <?php else: foreach($active_rx as $rx):?>
          <tr>
            <td><?=e($rx['rx_ref']??'')?></td>
            <td><?=e($rx['patient_name'])?></td>
            <td>Dr. <?=e($rx['doctor_name'])?></td>
            <td><strong><?=e($rx['medicine_name']??'—')?></strong></td>
            <td><?=e($rx['dosage']??'—')?></td>
            <td><?=e($rx['frequency']??'—')?></td>
            <td><?=$rx['prescription_date']?date('d M Y',strtotime($rx['prescription_date'])):'—'?></td>
            <td><span class="badge badge-<?=$rx['status']==='Active'?'success':'warning'?>"><?=e($rx['status'])?></span></td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div><!-- /sec-medications -->

<!-- ═══════ ADMINISTER / CONFIRM MODAL ═══════ -->
<div class="modal-bg" id="administerMedModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-syringe" style="color:var(--role-accent);"></i> Administer Medication</h3><button class="modal-close" onclick="closeModal('administerMedModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Patient *</label>
      <select id="am_patient" class="form-control">
        <option value="">Select Patient</option>
        <?php foreach($all_patients_for_vitals as $ap):?>
          <option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?> (<?=e($ap['p_ref'])?>)</option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="form-group"><label>Medicine Name *</label><input id="am_medicine" class="form-control" placeholder="Medicine name"></div>
    <div class="form-row">
      <div class="form-group"><label>Dosage *</label><input id="am_dosage" class="form-control" placeholder="e.g. 500mg"></div>
      <div class="form-group"><label>Route</label>
        <select id="am_route" class="form-control">
          <option value="Oral">Oral</option><option value="IV">IV</option><option value="IM">IM</option>
          <option value="SC">Subcutaneous</option><option value="Topical">Topical</option>
          <option value="Inhaled">Inhaled</option><option value="Rectal">Rectal</option>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Verification Method</label>
      <select id="am_verify" class="form-control"><option value="Manual">Manual Check</option><option value="Barcode">Barcode Scan</option><option value="Double-Check">Double Check</option></select>
    </div>
    <div class="form-group"><label>Notes</label><textarea id="am_notes" class="form-control" rows="2"></textarea></div>
    <label style="display:flex;align-items:center;gap:.8rem;margin-bottom:1.4rem;font-size:1.2rem;cursor:pointer;">
      <input type="checkbox" id="am_confirm_check"> <span>I confirm that the correct patient, medication, dosage, route, and time have been verified.</span>
    </label>
    <button class="btn btn-success" onclick="submitAdminister()" style="width:100%;"><i class="fas fa-check-circle"></i> Confirm Administration</button>
  </div>
</div>

<!-- ═══════ CONFIRMATION MODAL FOR TABLE-ROW ADMIN ═══════ -->
<div class="modal-bg" id="confirmAdminModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-shield-check" style="color:var(--success);"></i> Confirm Medication</h3><button class="modal-close" onclick="closeModal('confirmAdminModal')"><i class="fas fa-times"></i></button></div>
    <div style="background:var(--warning-light);border:1px solid var(--warning);border-radius:var(--radius-sm);padding:1.2rem;margin-bottom:1.5rem;">
      <p style="font-weight:700;font-size:1.3rem;"><i class="fas fa-exclamation-triangle" style="color:var(--warning);"></i> Medication Verification</p>
      <p style="margin-top:.5rem;">Please verify the following details:</p>
    </div>
    <p style="font-size:1.3rem;margin:.5rem 0;"><strong>Patient:</strong> <span id="ca_patient_name"></span></p>
    <p style="font-size:1.3rem;margin:.5rem 0;"><strong>Medicine:</strong> <span id="ca_medicine_name"></span></p>
    <p style="font-size:1.3rem;margin:.5rem 0;"><strong>Dosage:</strong> <span id="ca_dosage"></span></p>
    <input type="hidden" id="ca_med_id">
    <label style="display:flex;align-items:center;gap:.8rem;margin:1.5rem 0;font-size:1.2rem;cursor:pointer;">
      <input type="checkbox" id="ca_verify_check"> <span>I confirm all details above are correct</span>
    </label>
    <button class="btn btn-success" onclick="executeAdminister()" style="width:100%;"><i class="fas fa-check-circle"></i> Administer Now</button>
  </div>
</div>

<!-- ═══════ REASON MODAL (for Missed/Refused/Held) ═══════ -->
<div class="modal-bg" id="medReasonModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-comment-medical" style="color:var(--warning);"></i> Reason Required</h3><button class="modal-close" onclick="closeModal('medReasonModal')"><i class="fas fa-times"></i></button></div>
    <input type="hidden" id="mr_med_id"><input type="hidden" id="mr_new_status">
    <div class="form-group"><label>Status: <span id="mr_status_label"></span></label></div>
    <div class="form-group"><label>Reason *</label><textarea id="mr_reason" class="form-control" rows="3" placeholder="Explain why this medication was not administered..."></textarea></div>
    <button class="btn btn-warning" onclick="submitMedReason()" style="width:100%;"><i class="fas fa-save"></i> Save</button>
  </div>
</div>

<script>
function filterMeds(status,el){
  document.querySelectorAll('#sec-medications .ftab').forEach(f=>f.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#medTable tbody tr').forEach(row=>{
    if(status==='all') row.style.display='';
    else row.style.display=(row.dataset.medStatus===status)?'':'none';
  });
}

function confirmAdminister(medId,medicine,patient,dosage){
  document.getElementById('ca_med_id').value=medId;
  document.getElementById('ca_patient_name').textContent=patient;
  document.getElementById('ca_medicine_name').textContent=medicine;
  document.getElementById('ca_dosage').textContent=dosage||'As prescribed';
  document.getElementById('ca_verify_check').checked=false;
  openModal('confirmAdminModal');
}

async function executeAdminister(){
  if(!document.getElementById('ca_verify_check').checked){showToast('Please verify medication details first','error');return;}
  const r=await nurseAction({action:'administer_medication',med_id:document.getElementById('ca_med_id').value});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success){closeModal('confirmAdminModal');setTimeout(()=>location.reload(),1200);}
}

function markMedStatus(medId,newStatus){
  document.getElementById('mr_med_id').value=medId;
  document.getElementById('mr_new_status').value=newStatus;
  document.getElementById('mr_status_label').textContent=newStatus;
  document.getElementById('mr_reason').value='';
  openModal('medReasonModal');
}

async function submitMedReason(){
  const reason=document.getElementById('mr_reason').value.trim();
  if(!reason){showToast('Please provide a reason','error');return;}
  const r=await nurseAction({action:'update_med_status',med_id:document.getElementById('mr_med_id').value,
    new_status:document.getElementById('mr_new_status').value, reason:reason});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success){closeModal('medReasonModal');setTimeout(()=>location.reload(),1200);}
}

async function submitAdminister(){
  if(!validateForm({am_patient:'Patient',am_medicine:'Medicine',am_dosage:'Dosage'})) return;
  if(!document.getElementById('am_confirm_check').checked){showToast('Please confirm verification checkbox','error');return;}
  const r=await nurseAction({action:'administer_new_medication',
    patient_id:document.getElementById('am_patient').value,
    medicine_name:document.getElementById('am_medicine').value,
    dosage:document.getElementById('am_dosage').value,
    route:document.getElementById('am_route').value,
    verified_by:document.getElementById('am_verify').value,
    notes:document.getElementById('am_notes').value});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success){closeModal('administerMedModal');setTimeout(()=>location.reload(),1200);}
}
</script>
