<?php
// MODULE 2: BOOK APPOINTMENT (Redesigned v2)
// Dynamic doctor grid + real-time slot selection + double-booking prevention

$all_book_docs = [];
$qDocs = mysqli_query($conn, "SELECT d.id, d.specialization, d.availability_status, u.name FROM doctors d JOIN users u ON d.user_id=u.id WHERE d.is_available=1 ORDER BY u.name");
if ($qDocs) while ($r = mysqli_fetch_assoc($qDocs)) $all_book_docs[] = $r;
?>
<div id="sec-book" class="dash-section">

<style>
/* ── Booking-specific styles ── */
#sec-book { position: relative; min-height: calc(100vh - 120px); padding: 2rem; display: none; align-items: center; justify-content: center; overflow: hidden; border-radius: 20px; }
#sec-book.active { display: flex !important; animation: fadeTab .3s ease; }

.book-step { display: none; }
.book-step.active { display: block; animation: slideUpFade .4s forwards; }

@keyframes slideUpFade {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.doc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;margin-bottom:1.5rem;}
.doc-card{background:var(--surface);border:2px solid var(--border);border-radius:var(--radius-md);padding:1.5rem;cursor:pointer;transition:var(--transition);position:relative;overflow:hidden;}
.doc-card::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,var(--role-accent),#2F80ED);opacity:0;transition:opacity .25s;}
.doc-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:var(--role-accent);}
.doc-card.selected{border-color:var(--role-accent);box-shadow:0 0 0 3px rgba(142,68,173,.2);}
.doc-card .doc-avatar{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--role-accent),#2F80ED);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700;margin-bottom:1rem;flex-shrink:0;}
.doc-card .doc-name{font-size:1.4rem;font-weight:700;margin-bottom:.3rem;}
.doc-card .doc-spec{font-size:1.15rem;color:var(--text-muted);}
.avail-dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:.4rem;}
.avail-dot.available{background:var(--success);box-shadow:0 0 5px var(--success);}
.avail-dot.busy{background:var(--danger);}

.slot-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:.7rem;margin-top:1rem;}
.slot-btn{padding:.9rem;border:1.5px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text-primary);font-size:1.25rem;font-weight:600;cursor:pointer;transition:var(--transition);text-align:center;}
.slot-btn:hover{border-color:var(--role-accent);background:var(--role-accent-light, rgba(142,68,173,.08));}
.slot-btn.selected{background:var(--role-accent);color:#fff;border-color:var(--role-accent);box-shadow:0 4px 12px rgba(142,68,173,.3);}

.book-progress{display:flex;gap:0;margin-bottom:2rem;}
.book-step-ind{flex:1;padding:.8rem 1rem;text-align:center;font-size:1.15rem;font-weight:600;color:var(--text-muted);border-bottom:3px solid var(--border);transition:var(--transition);}
.book-step-ind.active{color:var(--role-accent);border-bottom-color:var(--role-accent);}
.book-step-ind.done{color:var(--success);border-bottom-color:var(--success);}

.book-summary-row{display:flex;justify-content:space-between;align-items:center;padding:.8rem 0;border-bottom:1px solid var(--border);}
.book-summary-row:last-child{border-bottom:none;}
</style>

<!-- Abstract Background Blobs -->
<div style="position:absolute;top:-10%;left:-5%;width:400px;height:400px;background:var(--role-accent);border-radius:50%;filter:blur(100px);opacity:0.15;z-index:0;"></div>
<div style="position:absolute;bottom:-10%;right:-5%;width:500px;height:500px;background:#2F80ED;border-radius:50%;filter:blur(120px);opacity:0.1;z-index:0;"></div>

<!-- Floating Modal Card -->
<div style="position:relative;z-index:10;width:100%;max-width:950px;background:var(--surface);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:24px;box-shadow:0 25px 60px rgba(0,0,0,0.1);overflow:hidden;transition:var(--transition);">
  <div style="background:linear-gradient(135deg,var(--role-accent),#2F80ED);padding:2.5rem 3rem;color:#fff;">
    <h3 style="margin:0;font-size:2rem;font-weight:800;"><i class="fas fa-calendar-plus" style="margin-right:.8rem;"></i> Book an Appointment</h3>
    <p style="margin:.5rem 0 0;font-size:1.15rem;opacity:.9;">Follow the wizard below to schedule a session with our specialists.</p>
  </div>
  <div style="padding:3rem;">

    <!-- Step Progress Indicator -->
    <div class="book-progress" id="bookProgress">
      <div class="book-step-ind active" id="stepInd1"><i class="fas fa-user-doctor"></i> 1. Pick Doctor</div>
      <div class="book-step-ind" id="stepInd2"><i class="fas fa-calendar"></i> 2. Choose Date</div>
      <div class="book-step-ind" id="stepInd3"><i class="fas fa-clock"></i> 3. Pick Time</div>
      <div class="book-step-ind" id="stepInd4"><i class="fas fa-check-circle"></i> 4. Confirm</div>
    </div>

    <!-- STEP 1: Doctor Selection -->
    <div class="book-step active" id="bookStepDoc">
      <h4 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;">Select a Doctor</h4>
      <div style="margin-bottom:1.5rem;">
        <select id="docSearchBox" class="form-control" style="max-width:400px;" onchange="handleDocDropdown(this)">
          <option value="">-- Click to choose a Doctor --</option>
          <?php foreach ($all_book_docs as $d): ?>
          <option value="<?= $d['id'] ?>" data-name="<?= htmlspecialchars($d['name']) ?>" data-spec="<?= htmlspecialchars($d['specialization'] ?? 'General Practice') ?>" data-avail="<?= $d['availability_status'] ?? 'Available' ?>">Dr. <?= htmlspecialchars($d['name']) ?> — <?= htmlspecialchars($d['specialization'] ?? 'General Practice') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="doc-grid" id="docGrid">
      </div>
    </div>

    <!-- STEP 2: Date Selection -->
    <div class="book-step" id="bookStepDate">
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <button class="btn btn-ghost btn-sm" onclick="goBookStep(1)"><span class="btn-text"><i class="fas fa-arrow-left"></i> Back</span></button>
        <div id="selectedDocDisplay" style="font-size:1.4rem;font-weight:700;"></div>
      </div>
      <h4 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;">Choose Appointment Date</h4>
      <div class="form-row">
        <div class="form-group">
          <label><i class="fas fa-calendar"></i> Preferred Date</label>
          <input type="date" id="bookDate" class="form-control" min="<?= $today ?>" onchange="loadSlotsNew()">
        </div>
        <div class="form-group">
          <label><i class="fas fa-tag"></i> Service Type</label>
          <select id="bookServiceType" class="form-control">
            <option>Consultation</option><option>Follow-up</option><option>Check-up</option>
            <option>Emergency</option><option>Vaccination</option><option>Lab Request</option><option>Other</option>
          </select>
        </div>
      </div>
      <div id="availInfoBanner" style="display:none;margin-top:.5rem;padding:1rem 1.2rem;border-radius:10px;font-size:1.25rem;"></div>
    </div>

    <!-- STEP 3: Time Slot -->
    <div class="book-step" id="bookStepTime">
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <button class="btn btn-ghost btn-sm" onclick="goBookStep(2)"><span class="btn-text"><i class="fas fa-arrow-left"></i> Back</span></button>
        <div id="selectedDateDisplay" style="font-size:1.4rem;font-weight:700;"></div>
      </div>
      <h4 style="font-size:1.5rem;font-weight:700;margin-bottom:.5rem;">Available Time Slots</h4>
      <p style="font-size:1.2rem;color:var(--text-muted);margin-bottom:1rem;">Click a slot to select your preferred appointment time.</p>
      <div id="slotLoadMsg" style="display:none;text-align:center;padding:2rem;color:var(--text-muted);"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
      <div class="slot-grid" id="slotGrid"></div>
      <input type="hidden" id="selectedSlot">
    </div>

    <!-- STEP 4: Confirm -->
    <div class="book-step" id="bookStepConfirm">
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <button class="btn btn-ghost btn-sm" onclick="goBookStep(3)"><span class="btn-text"><i class="fas fa-arrow-left"></i> Back</span></button>
        <h4 style="font-size:1.5rem;font-weight:700;margin:0;">Confirm Your Booking</h4>
      </div>
      <div class="adm-card" style="background:var(--surface-2);margin-bottom:1.5rem;">
        <div style="padding:1.5rem;">
          <div class="book-summary-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-user-doctor" style="color:var(--role-accent);margin-right:.5rem;"></i> Doctor</span>
            <strong id="confDoc" style="font-size:1.3rem;"></strong>
          </div>
          <div class="book-summary-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-stethoscope" style="color:var(--primary);margin-right:.5rem;"></i> Specialization</span>
            <strong id="confSpec" style="font-size:1.3rem;"></strong>
          </div>
          <div class="book-summary-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-calendar" style="color:var(--success);margin-right:.5rem;"></i> Date</span>
            <strong id="confDate" style="font-size:1.3rem;"></strong>
          </div>
          <div class="book-summary-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-clock" style="color:var(--warning);margin-right:.5rem;"></i> Time</span>
            <strong id="confTime" style="font-size:1.3rem;"></strong>
          </div>
          <div class="book-summary-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-tag" style="color:var(--info);margin-right:.5rem;"></i> Service</span>
            <strong id="confService" style="font-size:1.3rem;"></strong>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label><i class="fas fa-comment-medical"></i> Reason / Symptoms</label>
        <textarea id="bookReason" class="form-control" rows="3" placeholder="Briefly describe your reason for the visit or any symptoms..."></textarea>
      </div>
      <div style="display:flex;gap:1rem;align-items:center;">
        <button id="bookSubmitBtn" class="btn-icon btn btn-primary" style="flex:1;justify-content:center;padding:1.3rem;font-size:1.5rem;" onclick="submitBookingNew()">
          <span class="btn-text"><i class="fas fa-calendar-check"></i> Confirm Booking</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let bookDoctors = <?= json_encode($all_book_docs) ?>;
let selectedDoc = null;
let selectedSlotVal = '';

// ── Load doctors on tab init ──────────────────────────────
(function loadDoctors(){
  renderDocGrid(bookDoctors);
})();

function handleDocDropdown(sel){
  if (!sel.value) {
    renderDocGrid(bookDoctors); return;
  }
  const id = sel.value;
  const opt = sel.options[sel.selectedIndex];
  selectDoc(id, opt.dataset.name, opt.dataset.spec, opt.dataset.avail);
}

function renderDocGrid(docs){
  const grid = document.getElementById('docGrid');
  if (!docs || !docs.length) {
    grid.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-muted);grid-column:1/-1;">No available doctors found.</div>';
    return;
  }
  grid.innerHTML = docs.map(d => {
    const avail = d.availability_status === 'Available';
    const initials = d.name.split(' ').map(n=>n[0]).join('').slice(0,2).toUpperCase();
    return `
      <div class="doc-card" onclick="selectDoc(${d.id}, '${d.name.replace(/'/g,"\\'")}', '${(d.specialization||'').replace(/'/g,"\\'")}', '${d.availability_status}')" id="docCard${d.id}" data-name="${d.name.toLowerCase()}" data-spec="${(d.specialization||'').toLowerCase()}">
        <div style="display:flex;gap:1rem;align-items:flex-start;">
          <div class="doc-avatar">${initials}</div>
          <div style="flex:1;">
            <div class="doc-name">Dr. ${d.name}</div>
            <div class="doc-spec">${d.specialization || 'General Practice'}</div>
            <div style="margin-top:.6rem;font-size:1.1rem;font-weight:600;">
              <span class="avail-dot ${avail ? 'available' : 'busy'}"></span>
              <span style="color:${avail ? 'var(--success)' : 'var(--danger)'};">${d.availability_status || 'Available'}</span>
            </div>
          </div>
          <div style="position:absolute;top:1rem;right:1rem;width:28px;height:28px;border-radius:50%;border:2px solid var(--role-accent);background:var(--surface);display:flex;align-items:center;justify-content:center;display:none;" class="doc-check-mark">
            <i class="fas fa-check" style="color:var(--role-accent);font-size:.9rem;"></i>
          </div>
        </div>
      </div>`;
  }).join('');
}

function selectDoc(id, name, spec, avail){
  document.querySelectorAll('.doc-card').forEach(c => c.classList.remove('selected'));
  document.getElementById('docCard'+id)?.classList.add('selected');
  document.getElementById('docSearchBox').value = id; // Sync dropdown
  selectedDoc = {id, name, spec, avail};
  document.getElementById('selectedDocDisplay').innerHTML = `<i class="fas fa-user-doctor" style="color:var(--role-accent);margin-right:.5rem;"></i> Dr. ${name} — <span style="color:var(--text-muted);">${spec}</span>`;
  setTimeout(() => goBookStep(2), 400);
}

async function loadSlotsNew(){
  const date = document.getElementById('bookDate').value;
  if (!date || !selectedDoc) return;
  const banner = document.getElementById('availInfoBanner');
  banner.style.display = 'block';
  banner.style.background = 'var(--surface-2)';
  banner.style.color = 'var(--text-muted)';
  banner.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
  const r = await patAction({action:'get_doctor_slots', doctor_id:selectedDoc.id, date:date});
  if (r.slots && r.slots.length > 0) {
    banner.style.background = 'var(--success-light)';
    banner.style.color = 'var(--success)';
    banner.innerHTML = `<i class="fas fa-check-circle"></i> ${r.slots.length} slot(s) available on ${r.day} · Hours: ${r.start} – ${r.end}`;
    setTimeout(() => goBookStep(3, date, r.slots), 600);
  } else {
    banner.style.background = 'var(--warning-light)';
    banner.style.color = 'var(--warning)';
    banner.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${r.message || 'No slots available on this date. Please try another day.'}`;
  }
}

function populateSlots(slots){
  const grid = document.getElementById('slotGrid');
  selectedSlotVal = '';
  if (!slots.length) {
    grid.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-muted);grid-column:1/-1;">No slots available.</div>';
    return;
  }
  grid.innerHTML = slots.map(s => {
    const [h,m] = s.split(':');
    const hr = parseInt(h);
    const ampm = hr >= 12 ? 'PM' : 'AM';
    const h12 = hr > 12 ? hr-12 : (hr === 0 ? 12 : hr);
    return `<div class="slot-btn" onclick="selectSlot('${s}', this)">${h12}:${m} ${ampm}</div>`;
  }).join('');
}

function selectSlot(val, el){
  document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
  el.classList.add('selected');
  selectedSlotVal = val;
  document.getElementById('selectedSlot').value = val;
  const [h,m] = val.split(':');
  const hr = parseInt(h);
  const ampm = hr >= 12 ? 'PM' : 'AM';
  const h12 = hr > 12 ? hr-12 : (hr===0?12:hr);
  fillConfirmPage(h12+':'+m+' '+ampm);
}

function fillConfirmPage(timeStr){
  const date = document.getElementById('bookDate').value;
  const svc = document.getElementById('bookServiceType').value;
  document.getElementById('confDoc').textContent = 'Dr. ' + selectedDoc.name;
  document.getElementById('confSpec').textContent = selectedDoc.spec;
  document.getElementById('confDate').textContent = new Date(date+'T00:00').toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
  document.getElementById('confTime').textContent = timeStr;
  document.getElementById('confService').textContent = svc;
  setTimeout(() => goBookStep(4), 300);
}

let currentStep = 1;
function goBookStep(step, date, slots){
  currentStep = step;
  document.querySelectorAll('.book-step').forEach((s,i) => s.classList.toggle('active', i+1===step));
  document.querySelectorAll('.book-step-ind').forEach((ind,i) => {
    ind.classList.remove('active','done');
    if (i+1 === step) ind.classList.add('active');
    else if (i+1 < step) ind.classList.add('done');
  });
  if (step === 2 && selectedDoc) {
    document.getElementById('selectedDocDisplay').innerHTML = `<i class="fas fa-user-doctor" style="color:var(--role-accent);"></i> Dr. ${selectedDoc.name}`;
  }
  if (step === 3 && date && slots) {
    const d = new Date(date+'T00:00');
    document.getElementById('selectedDateDisplay').innerHTML = `<i class="fas fa-calendar" style="color:var(--success);"></i> ${d.toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long'})}`;
    populateSlots(slots);
  }
}

async function submitBookingNew(){
  if (!selectedDoc || !document.getElementById('bookDate').value || !selectedSlotVal) {
    toast('Please complete all steps','danger'); return;
  }
  const btn = document.getElementById('bookSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Booking...';
  const r = await patAction({
    action: 'book_appointment',
    doctor_id: selectedDoc.id,
    date: document.getElementById('bookDate').value,
    time: selectedSlotVal,
    service_type: document.getElementById('bookServiceType').value,
    reason: document.getElementById('bookReason').value
  });
  if (r.success) {
    toast('Appointment booked successfully! Status: Pending ✓');
    // Reset all
    selectedDoc = null; selectedSlotVal = '';
    document.getElementById('bookDate').value = '';
    document.getElementById('bookReason').value = '';
    document.getElementById('availInfoBanner').style.display = 'none';
    btn.disabled = false; btn.innerHTML = '<span class="btn-text"><i class="fas fa-calendar-check"></i> Confirm Booking</span>';
    goBookStep(1);
    renderDocGrid(bookDoctors);
    setTimeout(() => showTab('appointments', document.querySelector('.adm-nav-item[onclick*=appointments]')), 1800);
  } else {
    toast(r.message || 'Booking failed','danger');
    btn.disabled = false; btn.innerHTML = '<span class="btn-text"><i class="fas fa-calendar-check"></i> Confirm Booking</span>';
  }
}
</script>
</div>
