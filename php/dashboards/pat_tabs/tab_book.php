<?php
// MODULE 2: BOOK APPOINTMENT
// Dynamic doctor selection + real-time slot lookup + double-booking prevention
?>
<div id="sec-book" class="dash-section">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-calendar-plus" style="color:var(--primary);"></i> Book an Appointment</h3></div>
    <div style="padding:2rem;">
      <form id="bookApptForm" onsubmit="submitBooking(event)">
        <div class="form-row">
          <!-- Doctor -->
          <div class="form-group">
            <label><i class="fas fa-user-doctor"></i> Select Doctor</label>
            <select id="bookDoctor" name="doctor_id" class="form-control" required onchange="onDoctorChange()">
              <option value="">— Loading doctors... —</option>
            </select>
          </div>
          <!-- Service type -->
          <div class="form-group">
            <label><i class="fas fa-tag"></i> Service Type</label>
            <select name="service_type" class="form-control">
              <option>Consultation</option><option>Follow-up</option><option>Check-up</option><option>Emergency</option><option>Vaccination</option><option>Lab Request</option><option>Other</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <!-- Date -->
          <div class="form-group">
            <label><i class="fas fa-calendar"></i> Preferred Date</label>
            <input type="date" id="bookDate" name="date" class="form-control" required min="<?=$today?>" onchange="loadSlots()">
          </div>
          <!-- Time -->
          <div class="form-group">
            <label><i class="fas fa-clock"></i> Available Time Slots</label>
            <select id="bookTime" name="time" class="form-control" required disabled>
              <option value="">— Select a doctor and date first —</option>
            </select>
          </div>
        </div>
        <!-- Slot status message -->
        <div id="slotMsg" style="font-size:1.2rem;margin-bottom:1rem;padding:.8rem 1rem;border-radius:8px;display:none;"></div>
        <div class="form-group">
          <label><i class="fas fa-comment-medical"></i> Reason / Symptoms</label>
          <textarea name="reason" class="form-control" rows="3" placeholder="Describe your reason for visit or symptoms..."></textarea>
        </div>
        <button type="submit" id="bookSubmitBtn" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.2rem;font-size:1.4rem;" disabled>
          <i class="fas fa-calendar-check"></i> Book Appointment
        </button>
      </form>
    </div>
  </div>

  <!-- Doctor Info Panel (shown when doctor selected) -->
  <div id="docInfoPanel" class="adm-card" style="margin-top:1.5rem;display:none;">
    <div class="adm-card-header"><h3><i class="fas fa-info-circle" style="color:var(--info);"></i> Doctor Availability</h3></div>
    <div id="docInfoContent" style="padding:1.5rem;font-size:1.25rem;"></div>
  </div>
</div>

<script>
// Load doctors list
(async function(){
  const r=await patAction({action:'get_doctors'});
  const sel=document.getElementById('bookDoctor');
  sel.innerHTML='<option value="">— Choose a doctor —</option>';
  if(r.success&&r.doctors){
    r.doctors.forEach(d=>{
      const s=d.availability_status==='Available'?'🟢':'🔴';
      sel.innerHTML+=`<option value="${d.id}">${s} Dr. ${d.name} — ${d.specialization}</option>`;
    });
  }
})();

function onDoctorChange(){
  document.getElementById('bookTime').innerHTML='<option value="">— Select a date —</option>';
  document.getElementById('bookTime').disabled=true;
  document.getElementById('bookSubmitBtn').disabled=true;
  document.getElementById('docInfoPanel').style.display='none';
  if(document.getElementById('bookDate').value) loadSlots();
}

async function loadSlots(){
  const docId=document.getElementById('bookDoctor').value;
  const date=document.getElementById('bookDate').value;
  const sel=document.getElementById('bookTime');
  const msg=document.getElementById('slotMsg');
  const btn=document.getElementById('bookSubmitBtn');
  if(!docId||!date){sel.innerHTML='<option value="">— Select a doctor and date —</option>';sel.disabled=true;btn.disabled=true;return;}
  sel.innerHTML='<option value="">Loading slots...</option>';sel.disabled=true;
  const r=await patAction({action:'get_doctor_slots',doctor_id:docId,date:date});
  if(!r.success){sel.innerHTML='<option value="">Error loading</option>';return;}
  const panel=document.getElementById('docInfoPanel');
  const info=document.getElementById('docInfoContent');
  if(r.slots&&r.slots.length>0){
    sel.innerHTML='<option value="">— Choose a time —</option>';
    r.slots.forEach(s=>{
      const hm=s.split(':');const h=parseInt(hm[0]);const ampm=h>=12?'PM':'AM';const h12=h>12?h-12:(h===0?12:h);
      sel.innerHTML+=`<option value="${s}">${h12}:${hm[1]} ${ampm}</option>`;
    });
    sel.disabled=false;
    msg.style.display='block';msg.style.background='var(--success-light)';msg.style.color='var(--success)';
    msg.innerHTML=`<i class="fas fa-check-circle"></i> ${r.slots.length} slot(s) available on ${r.day}`;
    panel.style.display='block';
    info.innerHTML=`<div style="display:flex;gap:2rem;flex-wrap:wrap;">
      <div><i class="fas fa-calendar-day" style="color:var(--primary);margin-right:.4rem;"></i><strong>Day:</strong> ${r.day}</div>
      <div><i class="fas fa-clock" style="color:var(--success);margin-right:.4rem;"></i><strong>Hours:</strong> ${r.start} — ${r.end}</div>
      <div><i class="fas fa-list" style="color:var(--info);margin-right:.4rem;"></i><strong>Available:</strong> ${r.slots.length} slots</div>
    </div>`;
    sel.onchange=()=>{btn.disabled=!sel.value;};
  } else {
    sel.innerHTML='<option value="">— No slots available —</option>';sel.disabled=true;btn.disabled=true;
    msg.style.display='block';msg.style.background='var(--warning-light)';msg.style.color='var(--warning)';
    msg.innerHTML=`<i class="fas fa-exclamation-circle"></i> ${r.message||'No available slots on this date'}`;
    panel.style.display='none';
  }
}

async function submitBooking(e){
  e.preventDefault();
  const fd=new FormData(e.target);
  const data={action:'book_appointment'};
  fd.forEach((v,k)=>data[k]=v);
  const btn=document.getElementById('bookSubmitBtn');
  btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Booking...';
  const r=await patAction(data);
  if(r.success){
    toast(r.message||'Appointment booked!');
    e.target.reset();
    document.getElementById('slotMsg').style.display='none';
    document.getElementById('docInfoPanel').style.display='none';
    document.getElementById('bookTime').innerHTML='<option value="">— Select a doctor and date —</option>';
    document.getElementById('bookTime').disabled=true;
    btn.innerHTML='<i class="fas fa-calendar-check"></i> Book Appointment';
    // Option: switch to appointments tab
    setTimeout(()=>showTab('appointments',document.querySelector('.adm-nav-item[onclick*=appointments]')),1500);
  } else {
    toast(r.message||'Booking failed','danger');
    btn.disabled=false;btn.innerHTML='<i class="fas fa-calendar-check"></i> Book Appointment';
  }
}
</script>
