<?php
// MODULE 5: LAB RESULTS
// Shows results only when doctor has marked patient_accessible=1
$my_labs=[];
$q=mysqli_query($conn,
  "SELECT lt.id, lt.test_id, lt.test_name, lt.test_category, lt.test_date, lt.status AS test_status, lt.cost,
          lr.result_id, lr.results, lr.normal_range, lr.interpretation, lr.result_file_path,
          lr.technician_notes, lr.patient_accessible, lr.patient_notified, lr.patient_viewed_at,
          lr.created_at AS result_date, lr.status AS result_status,
          ud.name AS doctor_name, d.specialization,
          ut.name AS tech_name
   FROM lab_tests lt
   LEFT JOIN lab_results lr ON lr.test_id=lt.test_id AND lr.patient_id=lt.patient_id
   LEFT JOIN doctors d ON lt.doctor_id=d.id
   LEFT JOIN users ud ON d.user_id=ud.id
   LEFT JOIN users ut ON lr.submitted_by=ut.id
   WHERE lt.patient_id=$pat_pk
   ORDER BY lt.test_date DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $my_labs[]=$r;
?>
<div id="sec-lab" class="dash-section">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-flask" style="color:var(--role-accent);"></i> Lab Results</h3></div>
    <div class="adm-table-wrap" style="padding:0 .5rem;">
      <table class="adm-table">
        <thead><tr><th>Test Name</th><th>Category</th><th>Date</th><th>Doctor</th><th>Technician</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php if(empty($my_labs)):?><tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);">No lab tests found</td></tr>
          <?php else: foreach($my_labs as $lb):
            $accessible=$lb['patient_accessible']??0;
            $hasResult=$lb['result_id']!==null;
            if($hasResult && $accessible):
              $statusLabel='Results Available';$statusCls='success';
            elseif($hasResult && !$accessible):
              $statusLabel='Awaiting Doctor Review';$statusCls='warning';
            elseif($lb['test_status']==='Completed' && !$hasResult):
              $statusLabel='Completed — No Results Yet';$statusCls='info';
            else:
              $statusLabel=$lb['test_status']??'Pending';$statusCls='warning';
            endif;
          ?>
          <tr>
            <td style="font-weight:600;"><?=htmlspecialchars($lb['test_name'])?></td>
            <td><?=htmlspecialchars($lb['test_category']??'General')?></td>
            <td><?=date('d M Y',strtotime($lb['test_date']))?></td>
            <td>Dr. <?=htmlspecialchars($lb['doctor_name']??'—')?></td>
            <td><?=htmlspecialchars($lb['tech_name']??'—')?></td>
            <td><span class="adm-badge adm-badge-<?=$statusCls?>"><?=$statusLabel?></span></td>
            <td>
              <?php if($hasResult && $accessible):?>
              <button class="adm-btn adm-btn-primary adm-btn-sm" onclick='viewLabResult(<?=json_encode($lb)?>)' title="View Result"><i class="fas fa-eye"></i></button>
              <?php if(!empty($lb['result_file_path'])):?>
              <a href="/RMU-Medical-Management-System/<?=htmlspecialchars($lb['result_file_path'])?>" target="_blank" class="adm-btn adm-btn-sm" title="Download"><i class="fas fa-download"></i></a>
              <?php endif;?>
              <?php else:?>
              <span style="font-size:1.1rem;color:var(--text-muted);"><i class="fas fa-lock"></i></span>
              <?php endif;?>
            </td>
          </tr>
          <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Lab Result Detail Modal -->
<div class="modal-bg" id="modalLabDetail">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-flask" style="color:var(--role-accent);margin-right:.5rem;"></i>Lab Result Details</h3><button class="modal-close" onclick="closeModal('modalLabDetail')">&times;</button></div>
    <div id="labDetailBody" style="font-size:1.3rem;line-height:2;"></div>
  </div>
</div>

<script>
function viewLabResult(lb){
  let h=`<div style="display:grid;gap:.6rem;">
    <div><strong>Test ID:</strong> ${lb.test_id}</div>
    <div><strong>Test Name:</strong> ${lb.test_name}</div>
    <div><strong>Category:</strong> ${lb.test_category||'General'}</div>
    <div><strong>Test Date:</strong> ${lb.test_date}</div>
    <div><strong>Doctor:</strong> Dr. ${lb.doctor_name||'—'}</div>
    <div><strong>Technician:</strong> ${lb.tech_name||'—'}</div>
    <hr style="border:none;border-top:1px solid var(--border);margin:.5rem 0;">
    <h4 style="color:var(--role-accent);"><i class="fas fa-vial"></i> Results</h4>
    <div><strong>Result:</strong><br><div style="background:var(--surface-2);padding:1rem;border-radius:8px;margin-top:.3rem;">${lb.results||'No results recorded'}</div></div>
    <div><strong>Normal Range:</strong> ${lb.normal_range||'—'}</div>
    <div><strong>Interpretation:</strong> ${lb.interpretation||'—'}</div>`;
  if(lb.technician_notes) h+=`<div><strong>Technician Notes:</strong> ${lb.technician_notes}</div>`;
  if(lb.result_file_path) h+=`<div><a href="/RMU-Medical-Management-System/${lb.result_file_path}" target="_blank" class="adm-btn adm-btn-primary adm-btn-sm" style="margin-top:.5rem;"><i class="fas fa-download"></i> Download File</a></div>`;
  h+='</div>';
  document.getElementById('labDetailBody').innerHTML=h;
  openModal('modalLabDetail');
}
</script>
