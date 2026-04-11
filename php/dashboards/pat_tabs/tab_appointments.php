<?php
// MODULE 3: MY APPOINTMENTS
$all_appts=[];
$q=mysqli_query($conn,"SELECT a.*, u.name AS doctor_name, d.specialization
  FROM appointments a JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id
  WHERE a.patient_id=$pat_pk ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $all_appts[]=$r;
?>
<div id="sec-appointments" class="dash-section">
  <div class="adm-card">
    <div class="adm-card-header">
      <h3><i class="fas fa-calendar-check" style="color:var(--primary);"></i> My Appointments</h3>
      <button class="btn-icon btn btn-primary btn-sm" onclick="showTab('book',document.querySelector('.adm-nav-item[onclick*=book]'))"><span class="btn-text"><i class="fas fa-plus"></i> Book New</span></button>
    </div>
    <!-- Filters -->
    <div class="filter-tabs" style="padding:.5rem 1.5rem 0;" id="apptFilters">
      <span class="ftab active" onclick="filterAppts('all',this)">All (<?=count($all_appts)?>)</span>
      <span class="ftab" onclick="filterAppts('upcoming',this)">Upcoming</span>
      <span class="ftab" onclick="filterAppts('past',this)">Past</span>
      <span class="ftab" onclick="filterAppts('cancelled',this)">Cancelled</span>
    </div>
    <div class="adm-table-wrap" style="padding:0 .5rem;">
      <table class="adm-table" id="apptsTable">
        <thead><tr><th>ID</th><th>Doctor</th><th>Date & Time</th><th>Type</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php if(empty($all_appts)):?><tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);">No appointments yet</td></tr>
          <?php else: foreach($all_appts as $a):
            $dt=$a['appointment_date'];
            $past=strtotime($dt)<strtotime($today)?'past':'upcoming';
            $cancelled=$a['status']==='Cancelled'||$a['status']==='No-Show';
            $cls=$cancelled?'cancelled':$past;
            $scMap=['Approved'=>'success','Confirmed'=>'success','Pending'=>'warning','Rescheduled'=>'info','Cancelled'=>'danger','No-Show'=>'danger','Completed'=>'teal']; $sc=$scMap[$a['status']]??'primary';
          ?>
          <tr class="appt-row appt-<?=$cls?>" data-id="<?=$a['id']?>">
            <td style="font-weight:600;font-size:1.1rem;"><?=htmlspecialchars($a['appointment_id']??'#'.$a['id'])?></td>
            <td>
              <div style="font-weight:600;">Dr. <?=htmlspecialchars($a['doctor_name'])?></div>
              <div style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($a['specialization'])?></div>
            </td>
            <td>
              <div style="font-weight:600;"><?=date('d M Y',strtotime($dt))?></div>
              <div style="font-size:1.1rem;color:var(--text-muted);"><?=date('g:i A',strtotime($a['appointment_time']))?></div>
              <?php if($a['reschedule_date']):?><div style="font-size:1rem;color:var(--info);margin-top:.2rem;"><i class="fas fa-redo"></i> Moved to: <?=date('d M',strtotime($a['reschedule_date']))?></div><?php endif;?>
            </td>
            <td><?=htmlspecialchars($a['service_type']??'Consultation')?></td>
            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?=htmlspecialchars($a['reason']??'')?>"><?=htmlspecialchars($a['reason']??'—')?></td>
            <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$a['status']?></span></td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <button class="btn btn-primary btn btn-sm" onclick='viewApptDetail(<?=json_encode($a)?>)' title="Details"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
                <?php if(in_array($a['status'],['Pending','Confirmed','Approved'])):?>
                <button class="btn btn-danger btn-sm" onclick="cancelAppt(<?=$a['id']?>)" title="Cancel"><span class="btn-text"><i class="fas fa-times"></i></span></button>
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

<!-- Appointment Detail Modal -->
<div class="modal-bg" id="modalApptDetail">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-calendar-alt" style="color:var(--primary);margin-right:.5rem;"></i>Appointment Details</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalApptDetail')"><span class="btn-text">&times;</span></button></div>
    <div id="apptDetailBody" style="font-size:1.3rem;line-height:2;"></div>
  </div>
</div>

<!-- Cancel Modal -->
<div class="modal-bg" id="modalCancelAppt">
  <div class="modal-box">
    <div class="modal-header"><h3 style="color:var(--danger);"><i class="fas fa-times-circle" style="margin-right:.5rem;"></i>Cancel Appointment</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalCancelAppt')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="confirmCancelAppt(event)">
      <input type="hidden" id="cancelApptId" name="id">
      <div class="form-group"><label>Reason for Cancellation</label><textarea name="reason" class="form-control" rows="3" required placeholder="Please provide a reason..."></textarea></div>
      <button type="submit" class="btn-icon btn btn-danger" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-times"></i> Confirm Cancellation</span></button>
    </form>
  </div>
</div>

<script>
function filterAppts(filter,btn){
  document.querySelectorAll('#apptFilters .ftab').forEach(f=>f.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.appt-row').forEach(r=>{
    if(filter==='all') r.style.display='';
    else if(filter==='cancelled') r.style.display=r.classList.contains('cancelled')?'':'none';
    else if(filter==='upcoming') r.style.display=r.classList.contains('upcoming')&&!r.classList.contains('cancelled')?'':'none';
    else if(filter==='past') r.style.display=r.classList.contains('past')&&!r.classList.contains('cancelled')?'':'none';
  });
}
function viewApptDetail(a){
  const sc={'Approved':'success','Confirmed':'success','Pending':'warning','Rescheduled':'info','Cancelled':'danger','No-Show':'danger','Completed':'teal'};
  let h=`<div style="display:grid;gap:.6rem;">
    <div><strong>Appointment ID:</strong> ${a.appointment_id||'#'+a.id}</div>
    <div><strong>Doctor:</strong> Dr. ${a.doctor_name} (${a.specialization})</div>
    <div><strong>Date:</strong> ${a.appointment_date}</div>
    <div><strong>Time:</strong> ${a.appointment_time}</div>
    <div><strong>Service:</strong> ${a.service_type||'Consultation'}</div>
    <div><strong>Reason:</strong> ${a.reason||'—'}</div>
    <div><strong>Status:</strong> <span class="adm-badge adm-badge-${sc[a.status]||'primary'}">${a.status}</span></div>`;
  if(a.reschedule_reason) h+=`<div><strong>Reschedule Reason:</strong> ${a.reschedule_reason}</div>`;
  if(a.reschedule_date) h+=`<div><strong>New Date:</strong> ${a.reschedule_date}</div>`;
  if(a.cancellation_reason) h+=`<div><strong>Cancellation Reason:</strong> ${a.cancellation_reason}</div>`;
  if(a.notes) h+=`<div><strong>Doctor's Notes:</strong> ${a.notes}</div>`;
  h+='</div>';
  document.getElementById('apptDetailBody').innerHTML=h;
  openModal('modalApptDetail');
}
function cancelAppt(id){document.getElementById('cancelApptId').value=id;openModal('modalCancelAppt');}
async function confirmCancelAppt(e){
  e.preventDefault();const fd=new FormData(e.target);
  const data={action:'cancel_appointment',id:fd.get('id'),reason:fd.get('reason')};
  const r=await patAction(data);
  if(r.success){toast('Appointment cancelled');closeModal('modalCancelAppt');location.reload();}
  else toast(r.message||'Error','danger');
}
</script>
