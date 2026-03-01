<?php // TAB: APPOINTMENTS ?>
<div id="sec-appointments" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-calendar-check"></i> Appointments Management</h2>
    <div style="display:flex;gap:.7rem;flex-wrap:wrap;">
      <button onclick="document.getElementById('apptCalView').style.display=document.getElementById('apptCalView').style.display==='none'?'block':'none'" class="adm-btn adm-btn-ghost adm-btn-sm"><i class="fas fa-calendar"></i> Calendar View</button>
    </div>
  </div>

  <!-- Filter Tabs -->
  <div class="filter-tabs">
    <button class="ftab active" onclick="filterAppts('all',this)">All</button>
    <button class="ftab" onclick="filterAppts('Pending',this)">Pending</button>
    <button class="ftab" onclick="filterAppts('Confirmed',this)">Confirmed</button>
    <button class="ftab" onclick="filterAppts('Rescheduled',this)">Rescheduled</button>
    <button class="ftab" onclick="filterAppts('Completed',this)">Completed</button>
    <button class="ftab" onclick="filterAppts('Cancelled',this)">Cancelled</button>
    <button class="ftab" onclick="filterAppts('today',this)">Today</button>
  </div>

  <!-- Search -->
  <div style="margin-bottom:1.2rem;">
    <div class="adm-search-wrap"><i class="fas fa-search"></i>
      <input type="text" class="adm-search-input" placeholder="Search patient name or appointment ID…" oninput="filterTable('apptSearch','apptTable');" id="apptSearch">
    </div>
  </div>

  <!-- Calendar placeholder-->
  <div id="apptCalView" style="display:none;margin-bottom:1.5rem;" class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-calendar"></i> Calendar View</h3></div>
    <div style="padding:2rem;text-align:center;color:var(--text-muted);">
      <i class="fas fa-calendar-days" style="font-size:3rem;opacity:.35;margin-bottom:1rem;display:block;"></i>
      <p>Calendar integration: appointments for the selected month appear below the table.</p>
    </div>
  </div>

  <!-- Table -->
  <div class="adm-card">
    <div class="adm-table-wrap">
      <table class="adm-table" id="apptTable">
        <thead><tr>
          <th>Appt ID</th><th>Patient</th><th>Date & Time</th><th>Service</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if(empty($appointments)):?>
          <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text-muted);">No appointments found.</td></tr>
        <?php else: foreach($appointments as $ap):
          $sc=match($ap['status']??''){
            'Confirmed'=>'success','Completed'=>'info','Cancelled'=>'danger',
            'Rescheduled'=>'warning','No-Show'=>'danger',default=>'warning'};
          $iso_date=date('Y-m-d',strtotime($ap['appointment_date']));
          $is_today=($iso_date===$today);
          $can_act=!in_array($ap['status'],['Completed','Cancelled']);
          $appt_json=json_encode(['id'=>$ap['id'],'patient_name'=>$ap['patient_name'],'p_ref'=>$ap['p_ref'],
            'date'=>$ap['appointment_date'],'time'=>$ap['appointment_time'],
            'service'=>$ap['service_type'],'symptoms'=>$ap['symptoms'],
            'blood_group'=>$ap['blood_group'],'allergies'=>$ap['allergies'],
            'reason'=>$ap['reason']??'','status'=>$ap['status']],JSON_HEX_QUOT|JSON_HEX_APOS);
        ?>
        <tr data-status="<?=$ap['status']?>" data-date="<?=$iso_date?>" <?=$is_today?'style="background:rgba(26,188,156,.05);"':''?>>
          <td><code style="font-size:1.1rem;"><?=htmlspecialchars($ap['appointment_id']??'#'.$ap['id'])?></code></td>
          <td>
            <strong><?=htmlspecialchars($ap['patient_name']??'')?></strong><br>
            <span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($ap['p_ref']??'')?></span>
          </td>
          <td>
            <div style="font-weight:600;"><?=date('d M Y',strtotime($ap['appointment_date']))?></div>
            <div style="font-size:1.1rem;color:var(--text-muted);"><?=date('g:i A',strtotime($ap['appointment_time']))?></div>
            <?php if($is_today):?><span class="adm-badge adm-badge-teal" style="font-size:.9rem;margin-top:.2rem;">Today</span><?php endif;?>
          </td>
          <td><?=htmlspecialchars($ap['service_type']??'Consultation')?></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$ap['status']?></span></td>
          <td>
            <div class="action-btns">
              <button onclick='viewAppt(<?=$appt_json?>)' class="adm-btn adm-btn-ghost adm-btn-sm" title="View"><i class="fas fa-eye"></i></button>
              <?php if($can_act):?>
              <?php if($ap['status']==='Pending'):?>
              <button onclick="approveAppt(<?=$ap['id']?>,this)" class="adm-btn adm-btn-success adm-btn-sm" title="Approve"><i class="fas fa-check"></i></button>
              <?php endif;?>
              <button onclick='rescheduleAppt(<?=$ap["id"]?>,<?=json_encode($ap["patient_name"])?>)' class="adm-btn adm-btn-warning adm-btn-sm" title="Reschedule"><i class="fas fa-calendar-pen"></i></button>
              <button onclick='cancelAppt(<?=$ap["id"]?>,<?=json_encode($ap["patient_name"])?>)' class="adm-btn adm-btn-danger adm-btn-sm" title="Cancel"><i class="fas fa-xmark"></i></button>
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

<!-- Modal: View Appointment -->
<div class="modal-bg" id="modalViewAppt">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-calendar-check" style="color:var(--role-accent);"></i> Appointment Details</h3>
      <button class="modal-close" onclick="closeModal('modalViewAppt')">&times;</button>
    </div>
    <div id="apptDetail" style="font-size:1.3rem;line-height:2;"></div>
  </div>
</div>

<!-- Modal: Reschedule -->
<div class="modal-bg" id="modalReschedule">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-calendar-pen" style="color:var(--warning);"></i> Reschedule Appointment</h3>
      <button class="modal-close" onclick="closeModal('modalReschedule')">&times;</button>
    </div>
    <p id="reschedPatient" style="margin-bottom:1.5rem;font-weight:600;font-size:1.4rem;color:var(--text-primary);"></p>
    <div class="form-group"><label>New Date</label><input type="date" id="newApptDate" class="form-control" min="<?=date('Y-m-d')?>"></div>
    <div class="form-group"><label>New Time</label><input type="time" id="newApptTime" class="form-control"></div>
    <div class="form-group"><label>Reason for Rescheduling</label><textarea id="reschedReason" class="form-control" rows="3" placeholder="Enter reason..."></textarea></div>
    <button onclick="submitReschedule()" class="adm-btn adm-btn-warning" style="width:100%;justify-content:center;"><i class="fas fa-calendar-check"></i> Confirm Reschedule</button>
  </div>
</div>

<!-- Modal: Cancel -->
<div class="modal-bg" id="modalCancel">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-xmark" style="color:var(--danger);"></i> Cancel Appointment</h3>
      <button class="modal-close" onclick="closeModal('modalCancel')">&times;</button>
    </div>
    <p id="cancelPatient" style="margin-bottom:1.5rem;font-weight:600;font-size:1.4rem;"></p>
    <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;border-radius:10px;padding:1rem 1.5rem;background:var(--danger-light);color:var(--danger);font-size:1.2rem;">
      <i class="fas fa-triangle-exclamation"></i> The patient will be notified of this cancellation.
    </div>
    <div class="form-group"><label>Cancellation Reason</label><textarea id="cancelReason" class="form-control" rows="3" placeholder="Enter reason..."></textarea></div>
    <button onclick="submitCancel()" class="adm-btn adm-btn-danger" style="width:100%;justify-content:center;"><i class="fas fa-xmark"></i> Confirm Cancellation</button>
  </div>
</div>

<script>
let currentApptId=null;
function filterAppts(status,btn){
  document.querySelectorAll('.filter-tabs .ftab').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  const today='<?=$today?>';
  document.querySelectorAll('#apptTable tbody tr').forEach(row=>{
    if(status==='all'){row.style.display='';return;}
    if(status==='today'){row.style.display=row.dataset.date===today?'':'none';return;}
    row.style.display=row.dataset.status===status?'':'none';
  });
}
function viewAppt(a){
  document.getElementById('apptDetail').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
      <div><strong>Patient</strong><br>${a.patient_name}</div>
      <div><strong>Patient ID</strong><br>${a.p_ref}</div>
      <div><strong>Date</strong><br>${a.date}</div>
      <div><strong>Time</strong><br>${a.time}</div>
      <div><strong>Service</strong><br>${a.service||'Consultation'}</div>
      <div><strong>Status</strong><br><span class="adm-badge adm-badge-primary">${a.status}</span></div>
      ${a.blood_group?`<div><strong>Blood Group</strong><br>${a.blood_group}</div>`:''}
      ${a.allergies?`<div><strong>Allergies</strong><br><span style="color:var(--danger);">${a.allergies}</span></div>`:''}
    </div>
    ${a.symptoms?`<div style="margin-top:1.5rem;"><strong>Symptoms / Reason</strong><p style="color:var(--text-secondary);margin-top:.4rem;">${a.symptoms}</p></div>`:''}
    ${a.reason?`<div style="margin-top:1rem;"><strong>Patient's Reason</strong><p style="color:var(--text-secondary);margin-top:.4rem;">${a.reason}</p></div>`:''}
  `;
  openModal('modalViewAppt');
}
async function approveAppt(id,btn){
  if(!confirm('Approve this appointment?')) return;
  btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
  const res=await docAction({action:'approve_appointment',id});
  if(res.success){toast('Appointment approved!');btn.closest('tr').querySelector('td:nth-child(5) .adm-badge').textContent='Confirmed';btn.remove();}
  else{toast(res.message||'Error','danger');btn.disabled=false;btn.innerHTML='<i class="fas fa-check"></i>';}
}
function rescheduleAppt(id,name){currentApptId=id;document.getElementById('reschedPatient').textContent='Patient: '+name;openModal('modalReschedule');}
async function submitReschedule(){
  const d=document.getElementById('newApptDate').value, t=document.getElementById('newApptTime').value, r=document.getElementById('reschedReason').value;
  if(!d||!t||!r){toast('Please fill all fields','warning');return;}
  const res=await docAction({action:'reschedule_appointment',id:currentApptId,new_date:d,new_time:t,reason:r});
  if(res.success){toast('Appointment rescheduled & patient notified!');closeModal('modalReschedule');setTimeout(()=>location.reload(),1200);}
  else toast(res.message||'Error','danger');
}
function cancelAppt(id,name){currentApptId=id;document.getElementById('cancelPatient').textContent='Patient: '+name;openModal('modalCancel');}
async function submitCancel(){
  const r=document.getElementById('cancelReason').value;
  if(!r){toast('Please enter a cancellation reason','warning');return;}
  const res=await docAction({action:'cancel_appointment',id:currentApptId,reason:r});
  if(res.success){toast('Appointment cancelled & patient notified!');closeModal('modalCancel');setTimeout(()=>location.reload(),1200);}
  else toast(res.message||'Error','danger');
}
</script>
