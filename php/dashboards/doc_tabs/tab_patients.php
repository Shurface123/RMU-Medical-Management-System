<?php // TAB: PATIENT RECORDS ?>
<div id="sec-patients" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-users"></i> Patient Records</h2>
  </div>

  <div style="margin-bottom:1.2rem;">
    <div class="adm-search-wrap"><i class="fas fa-search"></i>
      <input type="text" class="adm-search-input" id="patSearch" placeholder="Search by name, ID, or blood group…" oninput="filterTable('patSearch','patTable')">
    </div>
  </div>

  <div class="adm-card">
    <div class="adm-table-wrap">
      <table class="adm-table" id="patTable">
        <thead><tr><th>Patient ID</th><th>Name</th><th>Gender / Age</th><th>Blood Group</th><th>Allergies</th><th>Type</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($patients)):?>
          <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);">No patients found. Patients appear here once they have an appointment with you.</td></tr>
        <?php else: foreach($patients as $pt):
          $dob=$pt['date_of_birth']??null;
          $age=$dob?date_diff(date_create($dob),date_create())->y:'—';
          $pj=json_encode(['id'=>$pt['id'],'name'=>$pt['name'],'p_ref'=>$pt['p_ref'],
            'email'=>$pt['email'],'phone'=>$pt['phone'],'gender'=>$pt['gender'],
            'blood_group'=>$pt['blood_group'],'allergies'=>$pt['allergies'],
            'chronic_conditions'=>$pt['chronic_conditions'],'is_student'=>$pt['is_student'],
            'emergency_contact_name'=>$pt['emergency_contact_name']],JSON_HEX_QUOT|JSON_HEX_APOS);
        ?>
        <tr>
          <td><code><?=htmlspecialchars($pt['p_ref']??'')?></code></td>
          <td>
            <div style="display:flex;align-items:center;gap:.8rem;">
              <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--role-accent),var(--primary));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem;flex-shrink:0;"><?=strtoupper(substr($pt['name'],0,1))?></div>
              <div><strong><?=htmlspecialchars($pt['name'])?></strong><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($pt['email']??'')?></span></div>
            </div>
          </td>
          <td><?=htmlspecialchars($pt['gender']?:'—')?> / <?=$age?></td>
          <td><?=$pt['blood_group']?('<strong style="color:var(--danger);">'.$pt['blood_group'].'</strong>'):'<span style="color:var(--text-muted);">—</span>'?></td>
          <td><?=$pt['allergies']?('<span style="color:var(--warning);font-size:1.1rem;">'.htmlspecialchars(substr($pt['allergies'],0,30)).'</span>'):'<span style="color:var(--success);">None</span>'?></td>
          <td><?=$pt['is_student']?'<span class="adm-badge adm-badge-primary">Student</span>':'<span class="adm-badge adm-badge-info">Non-Student</span>'?></td>
          <td>
            <div class="action-btns">
              <button onclick='viewPatient(<?=$pj?>)' class="adm-btn adm-btn-ghost adm-btn-sm"><i class="fas fa-eye"></i> View</button>
              <button onclick='openNoteModal(<?=$pt["id"]?>,<?=json_encode($pt["name"])?>)' class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-note-sticky"></i> Note</button>
            </div>
          </td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: View Patient -->
<div class="modal-bg" id="modalViewPatient">
  <div class="modal-box wide">
    <div class="modal-header">
      <h3><i class="fas fa-user" style="color:var(--role-accent);"></i> Patient Profile</h3>
      <button class="modal-close" onclick="closeModal('modalViewPatient')">&times;</button>
    </div>
    <div id="patientDetail"></div>
  </div>
</div>

<!-- Modal: Add Patient Note -->
<div class="modal-bg" id="modalAddNote">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-note-sticky" style="color:var(--role-accent);"></i> Add Consultation Note</h3>
      <button class="modal-close" onclick="closeModal('modalAddNote')">&times;</button>
    </div>
    <p id="notePatientName" style="font-weight:600;font-size:1.4rem;margin-bottom:1.5rem;"></p>
    <div class="form-group"><label>Note Type</label>
      <select id="noteType" class="form-control">
        <option value="General">General</option><option value="Follow-up">Follow-up</option>
        <option value="Warning">Warning</option><option value="Allergy">Allergy</option>
        <option value="Observation">Observation</option><option value="Referral">Referral</option>
      </select>
    </div>
    <div class="form-group"><label>Note</label><textarea id="noteContent" class="form-control" rows="4" placeholder="Enter your clinical note…"></textarea></div>
    <button onclick="submitNote()" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-save"></i> Save Note</button>
  </div>
</div>

<script>
let currentNotePatientId=null;
function viewPatient(p){
  document.getElementById('patientDetail').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;font-size:1.3rem;margin-bottom:1.5rem;">
      <div><strong>Full Name</strong><br>${p.name}</div>
      <div><strong>Patient ID</strong><br>${p.p_ref}</div>
      <div><strong>Gender</strong><br>${p.gender||'—'}</div>
      <div><strong>Phone</strong><br>${p.phone||'—'}</div>
      <div><strong>Email</strong><br>${p.email||'—'}</div>
      <div><strong>Blood Group</strong><br><strong style="color:var(--danger);">${p.blood_group||'Unknown'}</strong></div>
    </div>
    ${p.allergies?`<div style="background:var(--warning-light);border-left:3px solid var(--warning);border-radius:8px;padding:1rem 1.2rem;margin-bottom:1rem;">⚠️ <strong>Allergies:</strong> ${p.allergies}</div>`:''}
    ${p.chronic_conditions?`<div style="background:var(--info-light);border-left:3px solid var(--info);border-radius:8px;padding:1rem 1.2rem;margin-bottom:1rem;"><strong>Chronic Conditions:</strong> ${p.chronic_conditions}</div>`:''}
    ${p.emergency_contact_name?`<div style="background:var(--surface-2);border-radius:8px;padding:1rem 1.2rem;"><strong>Emergency Contact:</strong> ${p.emergency_contact_name}</div>`:''}
    <div style="margin-top:1.5rem;display:flex;gap:.8rem;">
      <button onclick="closeModal('modalViewPatient');openNoteModal(${p.id},'${p.name}')" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-note-sticky"></i> Add Note</button>
      <button onclick="closeModal('modalViewPatient');openModal('modalNewRx')" class="adm-btn adm-btn-ghost adm-btn-sm"><i class="fas fa-prescription-bottle-medical"></i> Prescribe</button>
    </div>
  `;
  openModal('modalViewPatient');
}
function openNoteModal(patientId,name){
  currentNotePatientId=patientId;
  document.getElementById('notePatientName').textContent='Patient: '+name;
  openModal('modalAddNote');
}
async function submitNote(){
  const note=document.getElementById('noteContent').value;
  const type=document.getElementById('noteType').value;
  if(!note.trim()){toast('Please enter a note','warning');return;}
  const res=await docAction({action:'add_patient_note',patient_id:currentNotePatientId,note,note_type:type});
  if(res.success){toast('Note saved!');closeModal('modalAddNote');document.getElementById('noteContent').value='';}
  else toast(res.message||'Error','danger');
}
</script>
