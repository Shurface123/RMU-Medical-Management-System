<!-- ═══════════════════════════════════════════════════════════
     MODULE 5: NURSING NOTES & OBSERVATIONS — tab_notes.php
     ═══════════════════════════════════════════════════════════ -->
<?php
$nursing_notes = dbSelect($conn,
    "SELECT nn.*, u.name AS patient_name, p.patient_id AS p_ref,
            ns.shift_type
     FROM nursing_notes nn
     JOIN patients p ON nn.patient_id=p.id JOIN users u ON p.user_id=u.id
     LEFT JOIN nurse_shifts ns ON nn.shift_id=ns.id
     WHERE nn.nurse_id=?
     ORDER BY nn.created_at DESC LIMIT 100","i",[$nurse_pk]);

$wound_records = dbSelect($conn,
    "SELECT wc.*, u.name AS patient_name
     FROM wound_care_records wc
     JOIN patients p ON wc.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE wc.nurse_id=?
     ORDER BY wc.created_at DESC LIMIT 50","i",[$nurse_pk]);
?>
<div id="sec-notes" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-notes-medical"></i> Nursing Notes</h2>
    <div style="display:flex;gap:.8rem;">
      <button class="btn btn-primary" onclick="openModal('addNoteModal')"><i class="fas fa-plus"></i> Add Note</button>
      <button class="btn btn-outline" onclick="openModal('woundCareModal')"><i class="fas fa-band-aid"></i> Wound Care</button>
    </div>
  </div>

  <div class="filter-tabs">
    <span class="ftab active" onclick="filterNotes('all',this)">All Notes</span>
    <span class="ftab" onclick="filterNotes('General',this)">General</span>
    <span class="ftab" onclick="filterNotes('Observation',this)">Observation</span>
    <span class="ftab" onclick="filterNotes('Wound',this)">Wound</span>
    <span class="ftab" onclick="filterNotes('Behavior',this)">Behavior</span>
    <span class="ftab" onclick="filterNotes('Incident',this)">Incident</span>
  </div>

  <!-- ── Notes List ── -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <div class="table-responsive"><table class="adm-table" id="notesTable"><thead><tr>
      <th>Date/Time</th><th>Patient</th><th>Type</th><th>Note</th><th>Shift</th><th>Status</th><th>Actions</th>
    </tr></thead><tbody>
    <?php if(empty($nursing_notes)):?>
      <tr><td colspan="7" class="text-center text-muted" style="padding:3rem;">No nursing notes yet</td></tr>
    <?php else: foreach($nursing_notes as $nn):
      $locked = (int)($nn['is_locked']??0);
    ?>
      <tr data-note-type="<?=e($nn['note_type'])?>">
        <td><?=date('d M Y h:i A',strtotime($nn['created_at']))?></td>
        <td><?=e($nn['patient_name'])?><br><small class="text-muted"><?=e($nn['p_ref']??'')?></small></td>
        <td><span class="badge badge-<?=($nn['note_type']==='Incident')?'danger':(($nn['note_type']==='Wound')?'warning':'info')?>"><?=e($nn['note_type'])?></span></td>
        <td style="max-width:300px;"><?=e(substr($nn['note_content'],0,120))?><?=strlen($nn['note_content'])>120?'...':''?></td>
        <td><?=e($nn['shift_type']??'—')?></td>
        <td><?=$locked?'<span class="badge badge-secondary"><i class="fas fa-lock"></i> Locked</span>':'<span class="badge badge-success"><i class="fas fa-unlock"></i> Open</span>'?></td>
        <td class="action-btns">
          <button class="btn btn-xs btn-outline" onclick="viewNote(<?=$nn['id']?>)" title="View"><i class="fas fa-eye"></i></button>
          <?php if(!$locked):?><button class="btn btn-xs btn-primary" onclick="editNote(<?=$nn['id']?>)" title="Edit"><i class="fas fa-edit"></i></button><?php endif;?>
        </td>
      </tr>
    <?php endforeach; endif;?></tbody></table></div>
  </div>

  <!-- ── Wound Care Records ── -->
  <?php if(!empty($wound_records)):?>
  <div class="info-card">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-band-aid" style="color:var(--warning);"></i> Wound Care Records</h3>
    <div class="table-responsive"><table class="data-table"><thead><tr>
      <th>Patient</th><th>Location</th><th>Description</th><th>Dressing</th><th>Healing</th><th>Next Care</th><th>Date</th>
    </tr></thead><tbody>
    <?php foreach($wound_records as $wr):?>
      <tr>
        <td><?=e($wr['patient_name'])?></td>
        <td><?=e($wr['wound_location'])?></td>
        <td style="max-width:200px;"><?=e(substr($wr['wound_description']??'',0,80))?></td>
        <td><?=e($wr['dressing_type']??'—')?></td>
        <td><span class="badge badge-<?=($wr['healing_status']==='Improving')?'success':(($wr['healing_status']==='Worsening')?'danger':'warning')?>"><?=e($wr['healing_status'])?></span></td>
        <td><?=$wr['next_care_due']?date('d M h:i A',strtotime($wr['next_care_due'])):'—'?></td>
        <td><?=date('d M Y',strtotime($wr['created_at']))?></td>
      </tr>
    <?php endforeach;?></tbody></table></div>
  </div>
  <?php endif;?>
</div>

<!-- ═══════ ADD NOTE MODAL ═══════ -->
<div class="modal-bg" id="addNoteModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-notes-medical" style="color:var(--role-accent);"></i> Add Nursing Note</h3><button class="modal-close" onclick="closeModal('addNoteModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-row">
      <div class="form-group"><label>Patient *</label>
        <select id="nn_patient" class="form-control"><option value="">Select Patient</option>
          <?php foreach($all_patients_for_vitals as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?></option><?php endforeach;?></select>
      </div>
      <div class="form-group"><label>Note Type *</label>
        <select id="nn_type" class="form-control">
          <option value="General">General Observation</option><option value="Observation">Clinical Observation</option>
          <option value="Wound">Wound Care</option><option value="Behavior">Patient Behavior</option>
          <option value="Incident">Incident Report</option><option value="Assessment">Assessment</option>
          <option value="Pain">Pain Assessment</option><option value="Handoff">Shift Handoff</option>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Note Content *</label><textarea id="nn_content" class="form-control" rows="6" placeholder="Enter detailed nursing observation or note..."></textarea></div>
    <div class="form-group"><label>Attachments (images/docs)</label><input type="file" id="nn_files" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"></div>
    <button class="btn btn-primary" onclick="submitNote()" style="width:100%;"><i class="fas fa-save"></i> Save Note</button>
  </div>
</div>

<!-- ═══════ WOUND CARE MODAL ═══════ -->
<div class="modal-bg" id="woundCareModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-band-aid" style="color:var(--warning);"></i> Record Wound Care</h3><button class="modal-close" onclick="closeModal('woundCareModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Patient *</label>
      <select id="wc_patient" class="form-control"><option value="">Select Patient</option>
        <?php foreach($all_patients_for_vitals as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?></option><?php endforeach;?></select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Wound Location *</label><input id="wc_location" class="form-control" placeholder="e.g. Left lower leg"></div>
      <div class="form-group"><label>Wound Type</label>
        <select id="wc_type" class="form-control"><option value="">Select</option><option value="Surgical">Surgical</option>
          <option value="Pressure">Pressure</option><option value="Laceration">Laceration</option>
          <option value="Burn">Burn</option><option value="Diabetic">Diabetic</option><option value="Other">Other</option></select>
      </div>
    </div>
    <div class="form-group"><label>Description</label><textarea id="wc_desc" class="form-control" rows="3" placeholder="Wound appearance, size, drainage..."></textarea></div>
    <div class="form-row">
      <div class="form-group"><label>Care Provided</label><textarea id="wc_care" class="form-control" rows="2" placeholder="Cleaning method, medication applied..."></textarea></div>
      <div class="form-group"><label>Dressing Type</label><input id="wc_dressing" class="form-control" placeholder="e.g. Gauze, transparent film"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Healing Status</label>
        <select id="wc_healing" class="form-control"><option value="Stable">Stable</option><option value="Improving">Improving</option><option value="Worsening">Worsening</option><option value="Healed">Healed</option></select>
      </div>
      <div class="form-group"><label>Next Care Due</label><input id="wc_next" type="datetime-local" class="form-control"></div>
    </div>
    <div class="form-group"><label>Wound Images</label><input type="file" id="wc_images" class="form-control" multiple accept=".jpg,.jpeg,.png"></div>
    <button class="btn btn-warning" onclick="submitWoundCare()" style="width:100%;"><i class="fas fa-save"></i> Save Wound Care Record</button>
  </div>
</div>

<!-- ═══════ VIEW NOTE MODAL ═══════ -->
<div class="modal-bg" id="viewNoteModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-eye" style="color:var(--role-accent);"></i> Note Details</h3><button class="modal-close" onclick="closeModal('viewNoteModal')"><i class="fas fa-times"></i></button></div>
    <div id="viewNoteContent"><p class="text-center text-muted" style="padding:3rem;">Loading...</p></div>
  </div>
</div>

<script>
function filterNotes(type,el){
  document.querySelectorAll('#sec-notes .ftab').forEach(f=>f.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#notesTable tbody tr').forEach(row=>{
    if(type==='all') row.style.display='';
    else row.style.display=(row.dataset.noteType===type)?'':'none';
  });
}

async function submitNote(){
  if(!validateForm({nn_patient:'Patient',nn_content:'Note content'})) return;
  const fd=new FormData();
  fd.append('action','add_nursing_note');
  fd.append('patient_id',document.getElementById('nn_patient').value);
  fd.append('note_type',document.getElementById('nn_type').value);
  fd.append('note_content',document.getElementById('nn_content').value);
  const files=document.getElementById('nn_files').files;
  for(let i=0;i<files.length;i++) fd.append('attachments[]',files[i]);
  const r=await nurseAction(fd);
  showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success){closeModal('addNoteModal');setTimeout(()=>location.reload(),1200);}
}

async function submitWoundCare(){
  if(!validateForm({wc_patient:'Patient',wc_location:'Wound location'})) return;
  const fd=new FormData();
  fd.append('action','add_wound_care');
  fd.append('patient_id',document.getElementById('wc_patient').value);
  fd.append('wound_location',document.getElementById('wc_location').value);
  fd.append('wound_type',document.getElementById('wc_type').value);
  fd.append('wound_description',document.getElementById('wc_desc').value);
  fd.append('care_provided',document.getElementById('wc_care').value);
  fd.append('dressing_type',document.getElementById('wc_dressing').value);
  fd.append('healing_status',document.getElementById('wc_healing').value);
  fd.append('next_care_due',document.getElementById('wc_next').value);
  const imgs=document.getElementById('wc_images').files;
  for(let i=0;i<imgs.length;i++) fd.append('wound_images[]',imgs[i]);
  const r=await nurseAction(fd);
  showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success){closeModal('woundCareModal');setTimeout(()=>location.reload(),1200);}
}

async function viewNote(noteId){
  openModal('viewNoteModal');
  document.getElementById('viewNoteContent').innerHTML='<p class="text-center text-muted" style="padding:3rem;">Loading...</p>';
  const r=await nurseAction({action:'get_note_detail',note_id:noteId});
  if(!r.success){document.getElementById('viewNoteContent').innerHTML='<p class="text-center" style="color:var(--danger);">'+r.message+'</p>';return;}
  const n=r.data;
  document.getElementById('viewNoteContent').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
      <div><p><strong>Patient:</strong> ${n.patient_name}</p><p><strong>Type:</strong> ${n.note_type}</p></div>
      <div><p><strong>Date:</strong> ${n.created_at}</p><p><strong>Status:</strong> ${n.is_locked?'🔒 Locked':'🔓 Open'}</p></div>
    </div>
    <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:1.5rem;white-space:pre-wrap;font-size:1.25rem;line-height:1.7;">${n.note_content}</div>
    ${n.attachments?'<div style="margin-top:1rem;"><strong>Attachments:</strong> '+n.attachments+'</div>':''}`;
}

async function editNote(noteId){
  const r=await nurseAction({action:'get_note_detail',note_id:noteId});
  if(!r.success){showToast(r.message||'Error','error');return;}
  document.getElementById('nn_patient').value=r.data.patient_id;
  document.getElementById('nn_type').value=r.data.note_type;
  document.getElementById('nn_content').value=r.data.note_content_raw||r.data.note_content;
  openModal('addNoteModal');
}
</script>
