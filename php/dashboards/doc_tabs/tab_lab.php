<?php // TAB: LAB TESTS ?>
<div id="sec-lab" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-flask"></i> Lab Test Requests</h2>
    <button onclick="openModal('modalNewLab')" class="adm-btn adm-btn-primary"><i class="fas fa-plus"></i> New Lab Request</button>
  </div>

  <div class="filter-tabs">
    <button class="ftab active" onclick="filterByStatus('all','labTable',5)">All</button>
    <button class="ftab" onclick="filterByStatus('Pending','labTable',5)">Pending</button>
    <button class="ftab" onclick="filterByStatus('Submitted','labTable',5)">Results Submitted</button>
    <button class="ftab" onclick="filterByStatus('Reviewed','labTable',5)">Reviewed</button>
    <button class="ftab" onclick="filterByStatus('Completed','labTable',5)">Completed</button>
  </div>

  <div style="margin-bottom:1.2rem;">
    <div class="adm-search-wrap"><i class="fas fa-search"></i>
      <input type="text" class="adm-search-input" id="labSearch" placeholder="Search patient or test name…" oninput="filterTable('labSearch','labTable')">
    </div>
  </div>

  <div class="adm-card">
    <div class="adm-table-wrap">
      <table class="adm-table" id="labTable">
        <thead><tr><th>Test ID</th><th>Patient</th><th>Test Name</th><th>Urgency</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($lab_requests)):?>
          <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);">No lab requests found.</td></tr>
        <?php else: foreach($lab_requests as $lr):
          $sc=match($lr['status']??''){
            'Completed'=>'success','Reviewed'=>'info','Submitted'=>'warning',
            'Cancelled'=>'danger',default=>'warning'};
          $uc=match($lr['urgency_level']??'Routine'){
            'Critical'=>'danger','Urgent'=>'warning',default=>'info'};
          $lj=json_encode(['id'=>$lr['id'],'patient_name'=>$lr['patient_name'],'test_name'=>$lr['test_name'],
            'test_category'=>$lr['test_category'],'results'=>$lr['results'],'status'=>$lr['status'],
            'urgency_level'=>$lr['urgency_level'],'request_notes'=>$lr['request_notes'],
            'tech_name'=>$lr['tech_name'],'result_file_path'=>$lr['result_file_path']??''],JSON_HEX_QUOT|JSON_HEX_APOS);
        ?>
        <tr data-status="<?=$lr['status']?>">
          <td><code><?=htmlspecialchars($lr['test_id']??'#'.$lr['id'])?></code></td>
          <td><strong><?=htmlspecialchars($lr['patient_name'])?></strong><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($lr['p_ref'])?></span></td>
          <td><?=htmlspecialchars($lr['test_name'])?><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($lr['test_category']??'')?></span></td>
          <td><span class="adm-badge adm-badge-<?=$uc?>"><?=$lr['urgency_level']??'Routine'?></span></td>
          <td><?=date('d M Y',strtotime($lr['test_date']))?></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$lr['status']?></span></td>
          <td>
            <div class="action-btns">
              <button onclick='viewLabResult(<?=$lj?>)' class="adm-btn adm-btn-ghost adm-btn-sm"><i class="fas fa-eye"></i> View</button>
              <?php if(in_array($lr['status'],['Submitted'])):?>
              <button onclick="reviewLab(<?=$lr['id']?>)" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-check-double"></i> Mark Reviewed</button>
              <?php endif;?>
            </div>
          </td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: New Lab Request -->
<div class="modal-bg" id="modalNewLab">
  <div class="modal-box wide">
    <div class="modal-header">
      <h3><i class="fas fa-flask" style="color:var(--role-accent);"></i> New Lab Test Request</h3>
      <button class="modal-close" onclick="closeModal('modalNewLab')">&times;</button>
    </div>
    <form id="formNewLab" onsubmit="submitLab(event)">
      <div class="form-row">
        <div class="form-group"><label>Select Patient</label>
          <select class="form-control" name="patient_id" required>
            <option value="">-- Choose Patient --</option>
            <?php foreach($patients as $pt):?>
            <option value="<?=$pt['id']?>"><?=htmlspecialchars($pt['name'])?> (<?=htmlspecialchars($pt['p_ref'])?>)</option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="form-group"><label>Test Name</label>
          <select class="form-control" name="test_name" required>
            <option value="">-- Select Test --</option>
            <?php foreach(['Full Blood Count (FBC)','Malaria Test','Urinalysis','Blood Glucose (Fasting)','Liver Function Test (LFT)','Kidney Function Test (KFT)','HIV Screening','Hepatitis B Test','Stool Microscopy','Pregnancy Test (urine)','Serum Electrolytes','Chest X-ray','ECG','COVID-19 Rapid Antigen','Thyroid Function Test','Other (specify in notes)'] as $tn):?>
            <option value="<?=$tn?>"><?=$tn?></option>
            <?php endforeach;?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Test Category</label><input type="text" name="test_category" class="form-control" placeholder="e.g. Haematology, Microbiology…"></div>
        <div class="form-group"><label>Urgency Level</label>
          <select class="form-control" name="urgency_level" required>
            <option value="Routine">Routine</option>
            <option value="Urgent">Urgent</option>
            <option value="Critical">Critical</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label>Test Date</label><input type="date" name="test_date" class="form-control" value="<?=$today?>" required></div>
      <div class="form-group"><label>Clinical Notes / Indication</label><textarea name="request_notes" class="form-control" rows="3" placeholder="Reason for requesting, relevant history…"></textarea></div>
      <div class="adm-alert" style="background:var(--primary-light);color:var(--primary);border-radius:10px;padding:1rem 1.5rem;margin-bottom:1.2rem;font-size:1.2rem;">
        <i class="fas fa-info-circle"></i> The lab technician will be notified automatically when this request is submitted.
      </div>
      <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-flask"></i> Submit Lab Request</button>
    </form>
  </div>
</div>

<!-- Modal: View Lab Result -->
<div class="modal-bg" id="modalViewLab">
  <div class="modal-box wide">
    <div class="modal-header">
      <h3><i class="fas fa-microscope" style="color:var(--role-accent);"></i> Lab Test Details</h3>
      <button class="modal-close" onclick="closeModal('modalViewLab')">&times;</button>
    </div>
    <div id="labDetail"></div>
    <div id="labReviewNotes" style="margin-top:1.5rem;display:none;">
      <div class="form-group"><label>Doctor's Review Notes</label>
        <textarea id="labDoctorNotes" class="form-control" rows="3" placeholder="Add review notes or interpretation…"></textarea>
      </div>
    </div>
  </div>
</div>

<script>
let currentLabId=null;
function viewLabResult(r){
  currentLabId=r.id;
  const hasResult=(r.status==='Submitted'||r.status==='Reviewed'||r.status==='Completed');
  document.getElementById('labDetail').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;font-size:1.3rem;margin-bottom:1.5rem;">
      <div><strong>Patient</strong><br>${r.patient_name}</div>
      <div><strong>Test</strong><br>${r.test_name}</div>
      <div><strong>Urgency</strong><br>${r.urgency_level||'Routine'}</div>
      <div><strong>Status</strong><br><span class="adm-badge adm-badge-primary">${r.status}</span></div>
      ${r.tech_name?`<div><strong>Technician</strong><br>${r.tech_name}</div>`:''}
    </div>
    ${r.request_notes?`<div class="adm-card" style="padding:1.2rem;margin:0 0 1rem;"><strong>Request Notes</strong><p style="color:var(--text-secondary);margin-top:.4rem;">${r.request_notes}</p></div>`:''}
    ${hasResult&&r.results?`<div class="adm-card" style="padding:1.2rem;margin:0 0 1rem;border:1.5px solid var(--role-accent);"><strong>Lab Results</strong><p style="color:var(--text-primary);margin-top:.5rem;white-space:pre-wrap;">${r.results}</p></div>`:'<div style="text-align:center;padding:2rem;color:var(--text-muted);"><i class="fas fa-hourglass-half" style="font-size:2rem;opacity:.4;display:block;margin-bottom:1rem;"></i>Results not yet submitted by lab technician.</div>'}
    ${r.result_file_path?`<a href="/RMU-Medical-Management-System/${r.result_file_path}" target="_blank" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-download"></i> Download Result File</a>`:''}
  `;
  document.getElementById('labReviewNotes').style.display=r.status==='Submitted'?'block':'none';
  openModal('modalViewLab');
}
async function submitLab(e){
  e.preventDefault();
  const fd=new FormData(e.target), data={action:'create_lab_request'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await docAction(data);
  if(res.success){toast('Lab request submitted! Technician notified.');closeModal('modalNewLab');setTimeout(()=>location.reload(),1200);}
  else toast(res.message||'Error','danger');
}
async function reviewLab(id){
  const notes=document.getElementById('labDoctorNotes')?.value||'';
  const res=await docAction({action:'review_lab',id,notes});
  if(res.success){toast('Lab result marked as reviewed!');closeModal('modalViewLab');setTimeout(()=>location.reload(),1000);}
  else toast(res.message||'Error','danger');
}
</script>
