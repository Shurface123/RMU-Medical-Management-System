<?php
// MODULE 4: MY PRESCRIPTIONS
$my_rx=[];
$q=mysqli_query($conn,"SELECT pr.*, u.name AS doctor_name, d.specialization
  FROM prescriptions pr JOIN doctors d ON pr.doctor_id=d.id JOIN users u ON d.user_id=u.id
  WHERE pr.patient_id=$pat_pk ORDER BY pr.prescription_date DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $my_rx[]=$r;
?>
<div id="sec-prescriptions" class="dash-section">
  <div class="adm-card">
    <div class="adm-card-header">
      <h3><i class="fas fa-pills" style="color:var(--warning);"></i> My Prescriptions</h3>
    </div>
    <!-- Filters -->
    <div class="filter-tabs" style="padding:.5rem 1.5rem 0;" id="rxFilters">
      <span class="ftab active" onclick="filterRx('all',this)">All (<?=count($my_rx)?>)</span>
      <span class="ftab" onclick="filterRx('active',this)">Active</span>
      <span class="ftab" onclick="filterRx('completed',this)">Completed</span>
      <span class="ftab" onclick="filterRx('refill',this)">Refill Requested</span>
    </div>
    <div class="adm-table-wrap" style="padding:0 .5rem;">
      <table class="adm-table" id="rxTable">
        <thead><tr><th>Medicine</th><th>Dosage & Frequency</th><th>Doctor</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if(empty($my_rx)):?><tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text-muted);">No prescriptions yet</td></tr>
          <?php else: foreach($my_rx as $rx):
            $scMap=['Active'=>'warning','Pending'=>'warning','Dispensed'=>'success','Completed'=>'success','Cancelled'=>'danger','Refill Requested'=>'info']; $sc=$scMap[$rx['status']]??'primary';
            $statusCls=in_array($rx['status'],['Active','Pending'])?'active':(in_array($rx['status'],['Dispensed','Completed'])?'completed':($rx['status']==='Refill Requested'?'refill':'other'));
          ?>
          <tr class="rx-row rx-<?=$statusCls?>">
            <td>
              <div style="font-weight:700;font-size:1.3rem;"><?=htmlspecialchars($rx['medication_name'])?></div>
            </td>
            <td>
              <div><?=htmlspecialchars($rx['dosage'])?></div>
              <div style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($rx['frequency'])?> · <?=htmlspecialchars($rx['duration']??'—')?></div>
            </td>
            <td>
              <div style="font-weight:600;">Dr. <?=htmlspecialchars($rx['doctor_name'])?></div>
              <div style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($rx['specialization'])?></div>
            </td>
            <td><?=date('d M Y',strtotime($rx['prescription_date']))?></td>
            <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$rx['status']?></span></td>
            <td>
              <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                <button class="btn btn-primary btn btn-sm" onclick='viewRxDetail(<?=json_encode($rx)?>)' title="View"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
                <?php if(in_array($rx['status'],['Active','Dispensed','Completed'])):?>
                <button class="btn btn-primary btn-sm" onclick="requestRefill(<?=$rx['id']?>)" title="Request Refill"><span class="btn-text"><i class="fas fa-redo"></i></span></button>
                <?php endif;?>
                <button class="btn btn-outline btn-icon btn btn-sm" onclick='printRx(<?=json_encode($rx)?>)' title="Print"><span class="btn-text"><i class="fas fa-print"></i></span></button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Rx Detail Modal -->
<div class="modal-bg" id="modalRxDetail">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-prescription" style="color:var(--warning);margin-right:.5rem;"></i>Prescription Details</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalRxDetail')"><span class="btn-text">&times;</span></button></div>
    <div id="rxDetailBody" style="font-size:1.3rem;line-height:2;"></div>
    <div style="margin-top:1.5rem;text-align:right;"><button class="btn-icon btn btn-primary btn-sm" onclick="printCurrentRx()"><span class="btn-text"><i class="fas fa-print"></i> Print</span></button></div>
  </div>
</div>

<!-- Refill Modal -->
<div class="modal-bg" id="modalRefill">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-redo" style="color:var(--info);margin-right:.5rem;"></i>Request Refill</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalRefill')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="confirmRefill(event)">
      <input type="hidden" id="refillRxId" name="prescription_id">
      <div class="form-group"><label>Notes (optional)</label><textarea name="notes" class="form-control" rows="2" placeholder="Any notes for your doctor..."></textarea></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-paper-plane"></i> Send Refill Request</span></button>
    </form>
  </div>
</div>

<script>
let currentRxData=null;
function filterRx(filter,btn){
  document.querySelectorAll('#rxFilters .ftab').forEach(f=>f.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.rx-row').forEach(r=>{
    if(filter==='all') r.style.display='';
    else r.style.display=r.classList.contains('rx-'+filter)?'':'none';
  });
}
function viewRxDetail(rx){
  currentRxData=rx;
  const sc={'Active':'warning','Pending':'warning','Dispensed':'success','Completed':'success','Cancelled':'danger','Refill Requested':'info'};
  let h=`<div style="display:grid;gap:.6rem;">
    <div><strong>Prescription ID:</strong> ${rx.prescription_id||'#'+rx.id}</div>
    <div><strong>Medicine:</strong> ${rx.medication_name}</div>
    <div><strong>Dosage:</strong> ${rx.dosage}</div>
    <div><strong>Frequency:</strong> ${rx.frequency}</div>
    <div><strong>Duration:</strong> ${rx.duration||'—'}</div>
    <div><strong>Instructions:</strong> ${rx.instructions||'None'}</div>
    <div><strong>Quantity:</strong> ${rx.quantity||'—'}</div>
    <div><strong>Doctor:</strong> Dr. ${rx.doctor_name}</div>
    <div><strong>Date Issued:</strong> ${rx.prescription_date}</div>
    <div><strong>Refills Allowed:</strong> ${rx.refills_allowed||0}</div>
    <div><strong>Refills Used:</strong> ${rx.refill_count||0}</div>
    <div><strong>Status:</strong> <span class="adm-badge adm-badge-${sc[rx.status]||'primary'}">${rx.status}</span></div>
  </div>`;
  document.getElementById('rxDetailBody').innerHTML=h;
  openModal('modalRxDetail');
}
function requestRefill(id){document.getElementById('refillRxId').value=id;openModal('modalRefill');}
async function confirmRefill(e){
  e.preventDefault();const fd=new FormData(e.target);
  const data={action:'request_refill',prescription_id:fd.get('prescription_id'),notes:fd.get('notes')};
  const r=await patAction(data);
  if(r.success){toast('Refill request sent!');closeModal('modalRefill');location.reload();}
  else toast(r.message||'Error','danger');
}
function printRx(rx){
  currentRxData=rx;printCurrentRx();
}
function printCurrentRx(){
  if(!currentRxData)return;const rx=currentRxData;
  const w=window.open('','','width=600,height=700');
  w.document.write(`<html><head><title>Prescription</title><style>body{font-family:'Poppins',sans-serif;padding:2rem;font-size:14px;}h2{color:#8e44ad;}.row{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #eee;}</style></head>
  <body><h2>RMU Medical Sickbay — Prescription</h2><hr>
  <div class="row"><span>Prescription ID</span><strong>${rx.prescription_id||rx.id}</strong></div>
  <div class="row"><span>Medicine</span><strong>${rx.medication_name}</strong></div>
  <div class="row"><span>Dosage</span><strong>${rx.dosage}</strong></div>
  <div class="row"><span>Frequency</span><strong>${rx.frequency}</strong></div>
  <div class="row"><span>Duration</span><strong>${rx.duration||'—'}</strong></div>
  <div class="row"><span>Instructions</span><strong>${rx.instructions||'None'}</strong></div>
  <div class="row"><span>Quantity</span><strong>${rx.quantity||'—'}</strong></div>
  <div class="row"><span>Doctor</span><strong>Dr. ${rx.doctor_name}</strong></div>
  <div class="row"><span>Date Issued</span><strong>${rx.prescription_date}</strong></div>
  <div class="row"><span>Status</span><strong>${rx.status}</strong></div>
  <br><p style="text-align:center;color:#888;">Printed from RMU Medical Sickbay</p>
  </body></html>`);
  w.document.close();w.print();
}
</script>
