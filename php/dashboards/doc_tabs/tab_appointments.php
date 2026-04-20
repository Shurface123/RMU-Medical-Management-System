<?php // TAB: APPOINTMENTS ?>
<div id="sec-appointments" class="dash-section">

<style>
/* Modern Filter Tabs */
.adm-tab-group { display:flex; gap:.8rem; flex-wrap:wrap; margin-bottom:1.8rem; padding-bottom:1rem; border-bottom:1px solid var(--border); }
.ftab-v2 { 
  display:inline-flex;align-items:center;gap:.6rem;padding:.55rem 1.4rem;border-radius:20px;
  font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);
  background:var(--surface);color:var(--text-secondary);transition:all 0.3s ease;
}
.ftab-v2:hover { background:var(--primary-light);color:var(--primary);border-color:var(--primary);transform:translateY(-1px); }
.ftab-v2.active { background:var(--primary);color:#fff;border-color:var(--primary);box-shadow:0 4px 12px rgba(47,128,237,.25); }

.appt-card-v2 { background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,.04); overflow:hidden; margin-bottom:2rem; }
.appt-card-header { padding:1.8rem 2rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface); }
</style>

  <div class="sec-header">
    <h2><i class="fas fa-calendar-check" style="color:var(--primary);"></i> Appointments Management</h2>
    <div style="display:flex;gap:.7rem;flex-wrap:wrap;">
      <button onclick="document.getElementById('apptCalView').style.display=document.getElementById('apptCalView').style.display==='none'?'block':'none'" class="btn btn-outline-primary" style="font-weight:600;border-radius:20px;"><span class="btn-text"><i class="fas fa-calendar-alt"></i> Toggle Calendar</span></button>
    </div>
  </div>

  <!-- Filter Tabs -->
  <div class="adm-tab-group">
    <button class="ftab-v2 active" onclick="filterAppts('all',this)"><i class="fas fa-list"></i> All</button>
    <button class="ftab-v2" onclick="filterAppts('today',this)"><i class="fas fa-calendar-day"></i> Today</button>
    <button class="ftab-v2" onclick="filterAppts('Pending',this)"><i class="fas fa-clock" style="color:var(--warning);"></i> Pending</button>
    <button class="ftab-v2" onclick="filterAppts('Confirmed',this)"><i class="fas fa-check-circle" style="color:var(--success);"></i> Confirmed</button>
    <button class="ftab-v2" onclick="filterAppts('Rescheduled',this)"><i class="fas fa-calendar-pen" style="color:var(--info);"></i> Resched</button>
    <button class="ftab-v2" onclick="filterAppts('Completed',this)"><i class="fas fa-clipboard-check" style="color:var(--primary);"></i> Completed</button>
  </div>


  <!-- Calendar placeholder-->
  <div id="apptCalView" style="display:none;margin-bottom:2rem;" class="adm-card shadow-sm">
    <div class="adm-card-header"><h3 style="font-size:1.4rem;margin:0;"><i class="fas fa-calendar-alt" style="color:var(--primary);margin-right:.5rem;"></i> Master Schedule View</h3></div>
    <div style="padding:4rem 2rem;text-align:center;color:var(--text-muted);">
      <i class="fas fa-calendar-days" style="font-size:4rem;color:var(--border);margin-bottom:1rem;display:block;"></i>
      <p style="font-size:1.3rem;">Calendar GUI synchronization is active. Select a date block to isolate appts.</p>
    </div>
  </div>

  <!-- Table -->
  <div class="adm-card shadow-sm" style="overflow:hidden;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="apptTable">
        <thead><tr style="background:linear-gradient(90deg, var(--surface-2), var(--surface));">
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
              <button onclick='viewAppt(<?=$appt_json?>)' class="btn btn-ghost btn-sm" title="View"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
              <?php if($can_act):?>
              <?php if($ap['status']==='Pending'):?>
              <button onclick="approveAppt(<?=$ap['id']?>,this)" class="btn btn-success btn-sm" title="Approve"><span class="btn-text"><i class="fas fa-check"></i></span></button>
              <?php endif;?>
              <button onclick='rescheduleAppt(<?=$ap["id"]?>,<?=json_encode($ap["patient_name"])?>)' class="btn btn-warning btn-sm" title="Reschedule"><span class="btn-text"><i class="fas fa-calendar-pen"></i></span></button>
              <button onclick='cancelAppt(<?=$ap["id"]?>,<?=json_encode($ap["patient_name"])?>)' class="btn btn-danger btn-sm" title="Cancel"><span class="btn-text"><i class="fas fa-xmark"></i></span></button>
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
      <button class="btn btn-primary modal-close" onclick="closeModal('modalViewAppt')"><span class="btn-text">&times;</span></button>
    </div>
    <div id="apptDetail" style="font-size:1.3rem;line-height:2;"></div>
  </div>
</div>

<!-- Modal: Reschedule -->
<div class="modal-bg" id="modalReschedule">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-calendar-pen" style="color:var(--warning);"></i> Reschedule Appointment</h3>
      <button class="btn btn-primary modal-close" onclick="closeModal('modalReschedule')"><span class="btn-text">&times;</span></button>
    </div>
    <p id="reschedPatient" style="margin-bottom:1.5rem;font-weight:600;font-size:1.4rem;color:var(--text-primary);"></p>
    <div class="form-group"><label>New Date</label><input type="date" id="newApptDate" class="form-control" min="<?=date('Y-m-d')?>"></div>
    <div class="form-group"><label>New Time</label><input type="time" id="newApptTime" class="form-control"></div>
    <div class="form-group"><label>Reason for Rescheduling</label><textarea id="reschedReason" class="form-control" rows="3" placeholder="Enter reason..."></textarea></div>
    <button onclick="submitReschedule()" class="btn-icon btn btn-warning" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-calendar-check"></i> Confirm Reschedule</span></button>
  </div>
</div>

<!-- Modal: Cancel -->
<div class="modal-bg" id="modalCancel">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-xmark" style="color:var(--danger);"></i> Cancel Appointment</h3>
      <button class="btn btn-primary modal-close" onclick="closeModal('modalCancel')"><span class="btn-text">&times;</span></button>
    </div>
    <p id="cancelPatient" style="margin-bottom:1.5rem;font-weight:600;font-size:1.4rem;"></p>
    <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;border-radius:10px;padding:1rem 1.5rem;background:var(--danger-light);color:var(--danger);font-size:1.2rem;">
      <i class="fas fa-triangle-exclamation"></i> The patient will be notified of this cancellation.
    </div>
    <div class="form-group"><label>Cancellation Reason</label><textarea id="cancelReason" class="form-control" rows="3" placeholder="Enter reason..."></textarea></div>
    <button onclick="submitCancel()" class="btn-icon btn btn-danger" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-xmark"></i> Confirm Cancellation</span></button>
  </div>
</div>

<script>
$(document).ready(function() {
    if($.fn.DataTable) {
        $('#apptTable').DataTable({
            pageLength: 10,
            language: { search: "", searchPlaceholder: "Quick search..." }
        });
    }
});

let currentApptId=null;
function filterAppts(status,btn){
  document.querySelectorAll('.adm-tab-group .ftab-v2').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  const today='<?=$today?>';
  
  if ($.fn.DataTable && $.fn.DataTable.isDataTable('#apptTable')) {
      const dt = $('#apptTable').DataTable();
      if(status === 'all') { dt.search('').columns().search('').draw(); return; }
      if(status === 'today') { dt.columns().search('').search(today).draw(); return; }
      dt.columns(4).search(status, true, false).draw();
  } else {
      document.querySelectorAll('#apptTable tbody tr').forEach(row=>{
        if(status==='all'){row.style.display='';return;}
        if(status==='today'){row.style.display=row.dataset.date===today?'':'none';return;}
        row.style.display=row.dataset.status===status?'':'none';
      });
  }
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
