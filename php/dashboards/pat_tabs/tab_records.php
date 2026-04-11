<?php
// MODULE 6: MEDICAL RECORDS — read-only for patient
$my_records=[];
$q=mysqli_query($conn,
  "SELECT mr.*, u.name AS doctor_name, d.specialization
   FROM medical_records mr
   JOIN doctors d ON mr.doctor_id=d.id JOIN users u ON d.user_id=u.id
   WHERE mr.patient_id=$pat_pk AND (mr.patient_visible IS NULL OR mr.patient_visible=1)
   ORDER BY mr.visit_date DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $my_records[]=$r;
?>
<div id="sec-records" class="dash-section">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-file-medical" style="color:var(--primary);"></i> Medical Records</h3></div>
    <!-- Search/Filter -->
    <div style="padding:.5rem 1.5rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
      <input type="text" id="recSearch" class="form-control" style="max-width:300px;margin-bottom:0;" placeholder="Search by doctor or diagnosis..." oninput="filterRecords()">
      <div style="display:flex;gap:.5rem;">
        <input type="date" id="recDateFrom" class="form-control" style="max-width:160px;margin-bottom:0;" onchange="filterRecords()" title="From">
        <input type="date" id="recDateTo" class="form-control" style="max-width:160px;margin-bottom:0;" onchange="filterRecords()" title="To">
      </div>
    </div>
    <div class="adm-table-wrap" style="padding:0 .5rem;">
      <table class="adm-table">
        <thead><tr><th>Visit Date</th><th>Doctor</th><th>Diagnosis</th><th>Treatment</th><th>Severity</th><th>Action</th></tr></thead>
        <tbody id="recordsBody">
          <?php if(empty($my_records)):?><tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text-muted);">No medical records found</td></tr>
          <?php else: foreach($my_records as $mr):
            $sevMap=['Mild'=>'success','Moderate'=>'warning','Severe'=>'danger','Critical'=>'danger']; $sevCls=$sevMap[$mr['severity']??'']??'primary';
          ?>
          <tr class="rec-row" data-date="<?=$mr['visit_date']?>" data-doctor="<?=strtolower($mr['doctor_name'])?>" data-diag="<?=strtolower($mr['diagnosis']??'')?>">
            <td style="font-weight:600;"><?=date('d M Y',strtotime($mr['visit_date']))?></td>
            <td>
              <div style="font-weight:600;">Dr. <?=htmlspecialchars($mr['doctor_name'])?></div>
              <div style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($mr['specialization'])?></div>
            </td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($mr['diagnosis']??'—')?></td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($mr['treatment']??'—')?></td>
            <td><?php if($mr['severity']):?><span class="adm-badge adm-badge-<?=$sevCls?>"><?=$mr['severity']?></span><?php else:?>—<?php endif;?></td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <button class="btn btn-primary btn btn-sm" onclick='viewRecordDetail(<?=json_encode($mr)?>)' title="View"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
                <button class="btn btn-outline btn-icon btn btn-sm" onclick='printRecord(<?=json_encode($mr)?>)' title="Print"><span class="btn-text"><i class="fas fa-print"></i></span></button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Record Detail Modal -->
<div class="modal-bg" id="modalRecordDetail">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-file-medical" style="color:var(--primary);margin-right:.5rem;"></i>Medical Record</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalRecordDetail')"><span class="btn-text">&times;</span></button></div>
    <div id="recordDetailBody" style="font-size:1.3rem;line-height:2;"></div>
    <div style="margin-top:1.5rem;text-align:right;"><button class="btn-icon btn btn-primary btn-sm" id="printRecBtn"><span class="btn-text"><i class="fas fa-print"></i> Print</span></button></div>
  </div>
</div>

<script>
let currentRecData=null;
function filterRecords(){
  const q=document.getElementById('recSearch').value.toLowerCase();
  const from=document.getElementById('recDateFrom').value;
  const to=document.getElementById('recDateTo').value;
  document.querySelectorAll('.rec-row').forEach(r=>{
    const d=r.dataset.date,doc=r.dataset.doctor,diag=r.dataset.diag;
    let show=true;
    if(q&&!doc.includes(q)&&!diag.includes(q)) show=false;
    if(from&&d<from) show=false;
    if(to&&d>to) show=false;
    r.style.display=show?'':'none';
  });
}

function viewRecordDetail(mr){
  currentRecData=mr;
  const sevMap={'Mild':'success','Moderate':'warning','Severe':'danger','Critical':'danger'};
  let h=`<div style="display:grid;gap:.6rem;">
    <div><strong>Record ID:</strong> ${mr.record_id||'#'+mr.id}</div>
    <div><strong>Visit Date:</strong> ${mr.visit_date}</div>
    <div><strong>Doctor:</strong> Dr. ${mr.doctor_name} (${mr.specialization})</div>
    ${mr.severity?`<div><strong>Severity:</strong> <span class="adm-badge adm-badge-${sevMap[mr.severity]||'primary'}">${mr.severity}</span></div>`:''}
    <hr style="border:none;border-top:1px solid var(--border);">
    <div><strong>Diagnosis:</strong><div style="background:var(--surface-2);padding:1rem;border-radius:8px;">${mr.diagnosis||'—'}</div></div>
    <div><strong>Symptoms:</strong> ${mr.symptoms||'—'}</div>
    <div><strong>Treatment:</strong><div style="background:var(--surface-2);padding:1rem;border-radius:8px;">${mr.treatment||'—'}</div></div>
    ${mr.treatment_plan?`<div><strong>Treatment Plan:</strong><div style="background:var(--surface-2);padding:1rem;border-radius:8px;">${mr.treatment_plan}</div></div>`:''}
    ${mr.vital_signs?`<div><strong>Vital Signs:</strong> ${mr.vital_signs}</div>`:''}
    ${mr.notes?`<div><strong>Notes:</strong> ${mr.notes}</div>`:''}
    ${mr.follow_up_required?`<div><strong>Follow-up:</strong> ${mr.follow_up_date||'Recommended'}</div>`:''}
  </div>`;
  document.getElementById('recordDetailBody').innerHTML=h;
  document.getElementById('printRecBtn').onclick=()=>printRecord(mr);
  openModal('modalRecordDetail');
}

function printRecord(mr){
  const w=window.open('','','width=600,height=700');
  w.document.write(`<html><head><title>Medical Record</title><style>body{font-family:'Poppins',sans-serif;padding:2rem;font-size:14px;}h2{color:#8e44ad;}.row{padding:.5rem 0;border-bottom:1px solid #eee;}.lbl{color:#666;font-size:12px;text-transform:uppercase;}</style></head>
  <body><h2>RMU Medical Sickbay — Medical Record</h2><hr>
  <div class="row"><span class="lbl">Record ID</span><br><strong>${mr.record_id||mr.id}</strong></div>
  <div class="row"><span class="lbl">Visit Date</span><br><strong>${mr.visit_date}</strong></div>
  <div class="row"><span class="lbl">Doctor</span><br><strong>Dr. ${mr.doctor_name}</strong></div>
  <div class="row"><span class="lbl">Diagnosis</span><br><strong>${mr.diagnosis||'—'}</strong></div>
  <div class="row"><span class="lbl">Symptoms</span><br>${mr.symptoms||'—'}</div>
  <div class="row"><span class="lbl">Treatment</span><br>${mr.treatment||'—'}</div>
  ${mr.treatment_plan?`<div class="row"><span class="lbl">Treatment Plan</span><br>${mr.treatment_plan}</div>`:''}
  ${mr.notes?`<div class="row"><span class="lbl">Notes</span><br>${mr.notes}</div>`:''}
  <br><p style="text-align:center;color:#888;">Printed from RMU Medical Sickbay</p></body></html>`);
  w.document.close();w.print();
}
</script>
