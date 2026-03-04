<!-- ═══════════════════════════════════════════════════════════
     MODULE 9: PATIENT EDUCATION & DISCHARGE — tab_education.php
     ═══════════════════════════════════════════════════════════ -->
<?php
$education_records = dbSelect($conn,
    "SELECT pe.*, u.name AS patient_name
     FROM patient_education pe
     JOIN patients p ON pe.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE pe.nurse_id=?
     ORDER BY pe.created_at DESC LIMIT 100","i",[$nurse_pk]);

$discharge_instructions = dbSelect($conn,
    "SELECT di.*, u.name AS patient_name
     FROM discharge_instructions di
     JOIN patients p ON di.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE di.nurse_id=?
     ORDER BY di.created_at DESC LIMIT 100","i",[$nurse_pk]);
?>
<div id="sec-education" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-book-medical"></i> Education & Discharge</h2>
    <div style="display:flex;gap:.8rem;">
      <button class="btn btn-primary" onclick="openModal('addEducationModal')"><i class="fas fa-plus"></i> Record Education</button>
      <button class="btn btn-outline" onclick="openModal('addDischargeModal')"><i class="fas fa-file-medical"></i> Discharge Instructions</button>
    </div>
  </div>

  <!-- ── Education Records ── -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-chalkboard-teacher" style="color:var(--role-accent);"></i> Patient Education Records</h3>
    <div class="table-responsive"><table class="adm-table"><thead><tr>
      <th>Date</th><th>Patient</th><th>Topic</th><th>Method</th><th>Understanding</th><th>Follow-Up</th><th>Actions</th>
    </tr></thead><tbody>
    <?php if(empty($education_records)):?>
      <tr><td colspan="7" class="text-center text-muted" style="padding:3rem;">No education records yet</td></tr>
    <?php else: foreach($education_records as $ed):
      $und_colors = ['Good'=>'success','Fair'=>'warning','Poor'=>'danger'];
    ?>
      <tr>
        <td><?=date('d M Y h:i A',strtotime($ed['created_at']))?></td>
        <td><?=e($ed['patient_name'])?></td>
        <td><strong><?=e($ed['topic'])?></strong></td>
        <td><?=e($ed['method_used']??'—')?></td>
        <td><span class="badge badge-<?=$und_colors[$ed['understanding_level']]??'secondary'?>"><?=e($ed['understanding_level'])?></span></td>
        <td><?=$ed['requires_followup']?'<span class="badge badge-warning">Yes</span>':'<span class="badge badge-success">No</span>'?></td>
        <td><button class="btn btn-xs btn-outline" onclick="viewEducation(<?=$ed['id']?>)"><i class="fas fa-eye"></i></button></td>
      </tr>
    <?php endforeach; endif;?></tbody></table></div>
  </div>

  <!-- ── Discharge Instructions ── -->
  <div class="info-card">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-file-medical" style="color:var(--primary);"></i> Discharge Instructions</h3>
    <div class="table-responsive"><table class="data-table"><thead><tr>
      <th>Date</th><th>Patient</th><th>Medications</th><th>Follow-Up</th><th>Acknowledged</th><th>Actions</th>
    </tr></thead><tbody>
    <?php if(empty($discharge_instructions)):?>
      <tr><td colspan="6" class="text-center text-muted" style="padding:2rem;">No discharge instructions yet</td></tr>
    <?php else: foreach($discharge_instructions as $di):?>
      <tr>
        <td><?=date('d M Y',strtotime($di['created_at']))?></td>
        <td><?=e($di['patient_name'])?></td>
        <td style="max-width:200px;"><?=e(substr($di['medication_instructions']??'—',0,80))?></td>
        <td><?=e(substr($di['follow_up_details']??'—',0,60))?></td>
        <td><?=$di['patient_acknowledged']?'<span class="badge badge-success"><i class="fas fa-check"></i> Yes</span>':'<span class="badge badge-warning">Pending</span>'?></td>
        <td><button class="btn btn-xs btn-outline" onclick="viewDischarge(<?=$di['id']?>)"><i class="fas fa-eye"></i></button></td>
      </tr>
    <?php endforeach; endif;?></tbody></table></div>
  </div>
</div>

<!-- ═══════ ADD EDUCATION MODAL ═══════ -->
<div class="modal-bg" id="addEducationModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-chalkboard-teacher" style="color:var(--role-accent);"></i> Record Patient Education</h3><button class="modal-close" onclick="closeModal('addEducationModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Patient *</label>
      <select id="edu_patient" class="form-control"><option value="">Select Patient</option>
        <?php foreach($all_patients_for_vitals as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?></option><?php endforeach;?></select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Topic *</label>
        <select id="edu_topic" class="form-control">
          <option value="">Select</option><option value="Medication Use">Medication Use</option><option value="Wound Care">Wound Care</option>
          <option value="Diet & Nutrition">Diet & Nutrition</option><option value="Exercise">Exercise</option>
          <option value="Disease Management">Disease Management</option><option value="Pain Management">Pain Management</option>
          <option value="Post-Op Care">Post-Op Care</option><option value="Fall Prevention">Fall Prevention</option><option value="Other">Other</option>
        </select>
      </div>
      <div class="form-group"><label>Method Used</label>
        <select id="edu_method" class="form-control"><option value="Verbal">Verbal</option><option value="Written">Written Material</option>
          <option value="Demonstration">Demonstration</option><option value="Video">Video</option><option value="Combined">Combined</option></select>
      </div>
    </div>
    <div class="form-group"><label>Content / Details</label><textarea id="edu_content" class="form-control" rows="4" placeholder="Describe the education provided in detail..."></textarea></div>
    <div class="form-row">
      <div class="form-group"><label>Understanding Level *</label>
        <select id="edu_understanding" class="form-control"><option value="Good">Good</option><option value="Fair">Fair</option><option value="Poor">Poor</option></select>
      </div>
      <div class="form-group"><label>Requires Follow-Up?</label>
        <select id="edu_followup" class="form-control"><option value="0">No</option><option value="1">Yes</option></select>
      </div>
    </div>
    <div class="form-group"><label>Materials Provided</label><input type="file" id="edu_files" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.png"></div>
    <button class="btn btn-primary" onclick="submitEducation()" style="width:100%;"><i class="fas fa-save"></i> Save Education Record</button>
  </div>
</div>

<!-- ═══════ ADD DISCHARGE MODAL ═══════ -->
<div class="modal-bg" id="addDischargeModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-file-medical" style="color:var(--primary);"></i> Discharge Instructions</h3><button class="modal-close" onclick="closeModal('addDischargeModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Patient *</label>
      <select id="dc_patient" class="form-control"><option value="">Select Patient</option>
        <?php foreach($all_patients_for_vitals as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?></option><?php endforeach;?></select>
    </div>
    <div class="form-group"><label>Medication Instructions *</label><textarea id="dc_meds" class="form-control" rows="3" placeholder="List medications, dosages, frequency, and special instructions..."></textarea></div>
    <div class="form-group"><label>Activity Restrictions</label><textarea id="dc_activity" class="form-control" rows="2" placeholder="Any physical activity restrictions..."></textarea></div>
    <div class="form-group"><label>Follow-Up Details</label><textarea id="dc_followup" class="form-control" rows="2" placeholder="Follow-up appointment date, location, doctor..."></textarea></div>
    <div class="form-group"><label>Warning Signs</label><textarea id="dc_warnings" class="form-control" rows="2" placeholder="Symptoms that require immediate medical attention..."></textarea></div>
    <div class="form-group"><label>Emergency Contact Info</label><input id="dc_emergency" class="form-control" placeholder="Hospital emergency number, nurse station..."></div>
    <div class="form-group"><label>Supporting Documents</label><input type="file" id="dc_files" class="form-control" multiple accept=".pdf,.doc,.docx"></div>
    <button class="btn btn-primary" onclick="submitDischarge()" style="width:100%;"><i class="fas fa-save"></i> Save Discharge Instructions</button>
  </div>
</div>

<script>
async function submitEducation(){
  if(!validateForm({edu_patient:'Patient',edu_topic:'Topic'})) return;
  const fd=new FormData();
  fd.append('action','add_education');
  fd.append('patient_id',document.getElementById('edu_patient').value);
  fd.append('topic',document.getElementById('edu_topic').value);
  fd.append('method_used',document.getElementById('edu_method').value);
  fd.append('content',document.getElementById('edu_content').value);
  fd.append('understanding_level',document.getElementById('edu_understanding').value);
  fd.append('requires_followup',document.getElementById('edu_followup').value);
  const files=document.getElementById('edu_files').files;
  for(let i=0;i<files.length;i++) fd.append('materials[]',files[i]);
  const r=await nurseAction(fd);
  showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success){closeModal('addEducationModal');setTimeout(()=>location.reload(),1200);}
}

async function submitDischarge(){
  if(!validateForm({dc_patient:'Patient',dc_meds:'Medication instructions'})) return;
  const fd=new FormData();
  fd.append('action','add_discharge_instructions');
  fd.append('patient_id',document.getElementById('dc_patient').value);
  fd.append('medication_instructions',document.getElementById('dc_meds').value);
  fd.append('activity_restrictions',document.getElementById('dc_activity').value);
  fd.append('follow_up_details',document.getElementById('dc_followup').value);
  fd.append('warning_signs',document.getElementById('dc_warnings').value);
  fd.append('emergency_contact',document.getElementById('dc_emergency').value);
  const files=document.getElementById('dc_files').files;
  for(let i=0;i<files.length;i++) fd.append('documents[]',files[i]);
  const r=await nurseAction(fd);
  showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success){closeModal('addDischargeModal');setTimeout(()=>location.reload(),1200);}
}

async function viewEducation(id){
  const r=await nurseAction({action:'get_education_detail',education_id:id});
  if(!r.success){showToast(r.message||'Error','error');return;}
  const d=r.data;
  alert(`Topic: ${d.topic}\nMethod: ${d.method_used}\nUnderstanding: ${d.understanding_level}\n\n${d.content||'No details'}`);
}

async function viewDischarge(id){
  const r=await nurseAction({action:'get_discharge_detail',discharge_id:id});
  if(!r.success){showToast(r.message||'Error','error');return;}
  const d=r.data;
  alert(`Medications: ${d.medication_instructions}\nActivity: ${d.activity_restrictions||'None'}\nFollow-Up: ${d.follow_up_details||'None'}\nWarnings: ${d.warning_signs||'None'}`);
}
</script>
