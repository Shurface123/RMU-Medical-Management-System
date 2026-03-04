<!-- ═══════════════════════════════════════════════════════════
     MODULE 2: PATIENT VITALS MANAGEMENT — tab_vitals.php
     ═══════════════════════════════════════════════════════════ -->
<?php
// ── Fetch assigned patients with latest vitals ────────────
$vital_patients = dbSelect($conn,
    "SELECT ba.patient_id, ba.bed_id, u.name AS patient_name, p.patient_id AS p_ref,
            (SELECT MAX(pv2.recorded_at) FROM patient_vitals pv2 WHERE pv2.patient_id=ba.patient_id) AS last_vital_time,
            pv.bp_systolic, pv.bp_diastolic, pv.pulse_rate, pv.temperature, pv.oxygen_saturation,
            pv.respiratory_rate, pv.blood_glucose, pv.weight, pv.height, pv.bmi, pv.is_flagged, pv.flag_reason
     FROM bed_assignments ba
     JOIN patients p ON ba.patient_id=p.id
     JOIN users u ON p.user_id=u.id
     LEFT JOIN patient_vitals pv ON pv.patient_id=ba.patient_id
       AND pv.recorded_at=(SELECT MAX(pv3.recorded_at) FROM patient_vitals pv3 WHERE pv3.patient_id=ba.patient_id)
     WHERE ba.status='Active'
     ORDER BY pv.is_flagged DESC, u.name ASC");

// ── Fetch thresholds ──────────────────────────────────────
$thresholds = [];
$thr_rows = dbSelect($conn,"SELECT * FROM vital_thresholds");
foreach($thr_rows as $tr) $thresholds[$tr['vital_type']] = $tr;

// ── All patients list for vitals (including non-admitted) ──
$all_patients_for_vitals = dbSelect($conn,
    "SELECT p.id, p.patient_id AS p_ref, u.name AS patient_name
     FROM patients p JOIN users u ON p.user_id=u.id
     ORDER BY u.name ASC LIMIT 500");
?>
<div id="sec-vitals" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-heartbeat"></i> Patient Vitals</h2>
    <div style="display:flex;gap:.8rem;">
      <button class="btn btn-primary" onclick="openModal('recordVitalsModal')"><i class="fas fa-plus"></i> Record Vitals</button>
      <button class="btn btn-outline" onclick="refreshVitals()"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>
  </div>

  <!-- ── Filter Tabs ── -->
  <div class="filter-tabs">
    <span class="ftab active" onclick="filterVitals('all',this)">All Patients</span>
    <span class="ftab" onclick="filterVitals('flagged',this)">⚠️ Flagged</span>
    <span class="ftab" onclick="filterVitals('critical',this)">🔴 Critical</span>
    <span class="ftab" onclick="filterVitals('normal',this)">✅ Normal</span>
    <span class="ftab" onclick="filterVitals('due',this)">⏰ Vitals Due</span>
  </div>

  <!-- ── Patient Vitals Table ── -->
  <div class="info-card">
    <div class="table-responsive">
      <table class="adm-table" id="vitalsTable">
        <thead><tr>
          <th>Patient</th><th>Bed</th><th>BP</th><th>HR</th><th>Temp</th><th>SpO2</th><th>RR</th>
          <th>Glucose</th><th>BMI</th><th>Status</th><th>Last Recorded</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php if(empty($vital_patients)):?>
            <tr><td colspan="12" style="text-align:center;color:var(--text-muted);padding:3rem;">No patients currently assigned</td></tr>
          <?php else: foreach($vital_patients as $vp):
            $flagged = (int)($vp['is_flagged']??0);
            $last_time = $vp['last_vital_time'] ? strtotime($vp['last_vital_time']) : 0;
            $hours_ago = $last_time ? round((time()-$last_time)/3600,1) : 999;
            $is_due = $hours_ago >= 4;
            $status_badge = $flagged ? '<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Flagged</span>' :
                           ($is_due ? '<span class="badge badge-warning"><i class="fas fa-clock"></i> Due</span>' :
                           '<span class="badge badge-success"><i class="fas fa-check"></i> Normal</span>');
            $row_class = $flagged ? 'style="border-left:3px solid var(--danger);"' : ($is_due ? 'style="border-left:3px solid var(--warning);"' : '');
          ?>
            <tr <?=$row_class?> data-status="<?=$flagged?'flagged':($is_due?'due':'normal')?>" data-critical="<?=$flagged?'1':'0'?>">
              <td><strong><?=e($vp['patient_name'])?></strong><br><small style="color:var(--text-muted);"><?=e($vp['p_ref']??'')?></small></td>
              <td><?=e($vp['bed_id']??'—')?></td>
              <td><?php $bp_s=$vp['bp_systolic']??'-'; $bp_d=$vp['bp_diastolic']??'-'; echo e("$bp_s/$bp_d");?></td>
              <td><?=e($vp['pulse_rate']??'—')?></td>
              <td><?=$vp['temperature']?e($vp['temperature']).'°C':'—'?></td>
              <td><?=$vp['oxygen_saturation']?e($vp['oxygen_saturation']).'%':'—'?></td>
              <td><?=e($vp['respiratory_rate']??'—')?></td>
              <td><?=$vp['blood_glucose']?e($vp['blood_glucose']).' mg/dL':'—'?></td>
              <td><?=$vp['bmi']?e($vp['bmi']):'—'?></td>
              <td><?=$status_badge?></td>
              <td><?=$vp['last_vital_time']?date('d M h:i A',strtotime($vp['last_vital_time'])):'Never'?><br><small style="color:var(--text-muted);"><?=$hours_ago<999?$hours_ago.'h ago':'—'?></small></td>
              <td class="action-btns">
                <button class="btn btn-xs btn-primary" onclick="openRecordVitals(<?=(int)$vp['patient_id']?>,'<?=e($vp['patient_name'])?>')" title="Record Vitals"><i class="fas fa-heartbeat"></i></button>
                <button class="btn btn-xs btn-outline" onclick="viewVitalHistory(<?=(int)$vp['patient_id']?>,'<?=e($vp['patient_name'])?>')" title="View History"><i class="fas fa-chart-line"></i></button>
                <button class="btn btn-xs btn-outline" onclick="openBedsideView(<?=(int)$vp['patient_id']?>)" title="Bedside View"><i class="fas fa-bed"></i></button>
              </td>
            </tr>
          <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div><!-- /sec-vitals -->

<!-- ═══════ RECORD VITALS MODAL ═══════ -->
<div class="modal-bg" id="recordVitalsModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-heartbeat" style="color:var(--role-accent);"></i> Record Vital Signs</h3><button class="modal-close" onclick="closeModal('recordVitalsModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Patient *</label>
      <select id="v_patient" class="form-control">
        <option value="">Select Patient</option>
        <?php foreach($all_patients_for_vitals as $ap):?>
          <option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?> (<?=e($ap['p_ref'])?>)</option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>BP Systolic (mmHg)</label><input id="v_bp_sys" type="number" class="form-control" placeholder="e.g. 120"></div>
      <div class="form-group"><label>BP Diastolic (mmHg)</label><input id="v_bp_dia" type="number" class="form-control" placeholder="e.g. 80"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Pulse Rate (bpm)</label><input id="v_pulse" type="number" class="form-control" placeholder="e.g. 72"></div>
      <div class="form-group"><label>Temperature (°C)</label><input id="v_temp" type="number" step="0.1" class="form-control" placeholder="e.g. 36.5"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Oxygen Saturation SpO2 (%)</label><input id="v_spo2" type="number" class="form-control" placeholder="e.g. 98"></div>
      <div class="form-group"><label>Respiratory Rate (breaths/min)</label><input id="v_rr" type="number" class="form-control" placeholder="e.g. 16"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Blood Glucose (mg/dL)</label><input id="v_glucose" type="number" step="0.1" class="form-control" placeholder="e.g. 95"></div>
      <div class="form-group"><label>Pain Level (0–10)</label><input id="v_pain" type="number" min="0" max="10" class="form-control" placeholder="e.g. 3"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Weight (kg)</label><input id="v_weight" type="number" step="0.1" class="form-control" placeholder="e.g. 70.5"></div>
      <div class="form-group"><label>Height (cm)</label><input id="v_height" type="number" step="0.1" class="form-control" placeholder="e.g. 170"></div>
    </div>
    <div class="form-group"><label>BMI (auto-calculated)</label><input id="v_bmi" class="form-control" readonly placeholder="Calculated from weight & height"></div>
    <div class="form-group"><label>Notes</label><textarea id="v_notes" class="form-control" rows="2" placeholder="Additional observations..."></textarea></div>
    <button class="btn btn-primary" onclick="submitVitals()" style="width:100%;"><i class="fas fa-save"></i> Save Vital Signs</button>
  </div>
</div>

<!-- ═══════ VITAL HISTORY MODAL ═══════ -->
<div class="modal-bg" id="vitalHistoryModal">
  <div class="modal-box wide" style="max-width:900px;">
    <div class="modal-header"><h3><i class="fas fa-chart-line" style="color:var(--role-accent);"></i> <span id="vhPatientName">Vital History</span></h3><button class="modal-close" onclick="closeModal('vitalHistoryModal')"><i class="fas fa-times"></i></button></div>
    <div class="filter-tabs" style="margin-bottom:1rem;">
      <span class="ftab active" onclick="loadVitalChart(window._vhPatient,'24h',this)">24 Hours</span>
      <span class="ftab" onclick="loadVitalChart(window._vhPatient,'7d',this)">7 Days</span>
      <span class="ftab" onclick="loadVitalChart(window._vhPatient,'30d',this)">30 Days</span>
    </div>
    <div class="charts-grid">
      <div class="info-card"><h4 style="margin-bottom:.8rem;">Blood Pressure</h4><div class="chart-wrap"><canvas id="chartBP"></canvas></div></div>
      <div class="info-card"><h4 style="margin-bottom:.8rem;">Heart Rate</h4><div class="chart-wrap"><canvas id="chartHR"></canvas></div></div>
      <div class="info-card"><h4 style="margin-bottom:.8rem;">Temperature</h4><div class="chart-wrap"><canvas id="chartTemp"></canvas></div></div>
      <div class="info-card"><h4 style="margin-bottom:.8rem;">SpO2</h4><div class="chart-wrap"><canvas id="chartSpO2"></canvas></div></div>
    </div>
    <div class="info-card" style="margin-top:1rem;">
      <h4 style="margin-bottom:.8rem;">Vital History Records</h4>
      <div class="table-responsive"><table class="data-table"><thead><tr>
        <th>Date/Time</th><th>BP</th><th>HR</th><th>Temp</th><th>SpO2</th><th>RR</th><th>Glucose</th><th>Pain</th><th>Status</th>
      </tr></thead><tbody id="vhTableBody"></tbody></table></div>
    </div>
  </div>
</div>

<!-- ═══════ BEDSIDE VIEW MODAL ═══════ -->
<div class="modal-bg" id="bedsideModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-bed" style="color:var(--role-accent);"></i> Bedside Digital Chart</h3><button class="modal-close" onclick="closeModal('bedsideModal')"><i class="fas fa-times"></i></button></div>
    <div id="bedsideContent" style="min-height:200px;"><p class="text-center text-muted" style="padding:3rem;">Loading...</p></div>
  </div>
</div>

<script>
// ── BMI auto-calc ──────────────────────────────────────────
document.getElementById('v_weight')?.addEventListener('input', calcBMI);
document.getElementById('v_height')?.addEventListener('input', calcBMI);
function calcBMI(){
  const w=parseFloat(document.getElementById('v_weight').value);
  const h=parseFloat(document.getElementById('v_height').value);
  if(w>0 && h>0){
    const bmi=(w/((h/100)**2)).toFixed(1);
    document.getElementById('v_bmi').value=bmi;
  }
}

// ── Submit Vitals ──────────────────────────────────────────
async function submitVitals(){
  if(!validateForm({v_patient:'Patient'})) return;
  const r = await nurseAction({
    action:'record_vitals',
    patient_id: document.getElementById('v_patient').value,
    bp_systolic: document.getElementById('v_bp_sys').value,
    bp_diastolic: document.getElementById('v_bp_dia').value,
    pulse_rate: document.getElementById('v_pulse').value,
    temperature: document.getElementById('v_temp').value,
    oxygen_saturation: document.getElementById('v_spo2').value,
    respiratory_rate: document.getElementById('v_rr').value,
    blood_glucose: document.getElementById('v_glucose').value,
    pain_level: document.getElementById('v_pain').value,
    weight: document.getElementById('v_weight').value,
    height: document.getElementById('v_height').value,
    bmi: document.getElementById('v_bmi').value,
    notes: document.getElementById('v_notes').value
  });
  showToast(r.message||'Saved', r.success?'success':'error');
  if(r.success){
    closeModal('recordVitalsModal');
    if(r.flagged) showToast('⚠️ Abnormal vital detected — Doctor has been notified','warning');
    setTimeout(()=>location.reload(),1200);
  }
}

function openRecordVitals(patientId, name){
  document.getElementById('v_patient').value=patientId;
  openModal('recordVitalsModal');
}

// ── Filter Vitals Table ────────────────────────────────────
function filterVitals(filter, el){
  document.querySelectorAll('#sec-vitals .ftab').forEach(f=>f.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#vitalsTable tbody tr').forEach(row=>{
    const st=row.dataset.status;
    const cr=row.dataset.critical;
    if(filter==='all') row.style.display='';
    else if(filter==='flagged') row.style.display=(st==='flagged')?'':'none';
    else if(filter==='critical') row.style.display=(cr==='1')?'':'none';
    else if(filter==='normal') row.style.display=(st==='normal')?'':'none';
    else if(filter==='due') row.style.display=(st==='due')?'':'none';
  });
}

// ── Vital History ──────────────────────────────────────────
let vitalCharts={};
async function viewVitalHistory(patientId, name){
  window._vhPatient = patientId;
  document.getElementById('vhPatientName').textContent = name + ' — Vital History';
  openModal('vitalHistoryModal');
  loadVitalChart(patientId, '24h', document.querySelector('#vitalHistoryModal .ftab.active'));
}

async function loadVitalChart(patientId, range, el){
  if(el){
    el.parentElement.querySelectorAll('.ftab').forEach(f=>f.classList.remove('active'));
    el.classList.add('active');
  }
  const r = await nurseAction({action:'get_vital_history', patient_id: patientId, range: range});
  if(!r.success) return showToast(r.message||'Error','error');

  const data = r.data || [];
  const labels = data.map(d => {
    const dt = new Date(d.recorded_at);
    return range==='24h' ? dt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}) : dt.toLocaleDateString([],{month:'short',day:'numeric'});
  });

  // Destroy old charts
  Object.values(vitalCharts).forEach(c=>c.destroy&&c.destroy());
  vitalCharts={};

  const chartOpts = (label,color,dataArr,canvasId) => {
    const ctx=document.getElementById(canvasId);
    if(!ctx) return;
    vitalCharts[canvasId]=new Chart(ctx,{type:'line',data:{labels,datasets:[{label,data:dataArr,borderColor:color,backgroundColor:color+'22',tension:.4,fill:true,pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{ticks:{maxTicksLimit:8,font:{size:10}}},y:{beginAtZero:false}}}});
  };

  chartOpts('Systolic','#E74C3C',data.map(d=>d.bp_systolic),'chartBP');
  chartOpts('Pulse Rate','#E91E63',data.map(d=>d.pulse_rate),'chartHR');
  chartOpts('Temperature','#F39C12',data.map(d=>d.temperature),'chartTemp');
  chartOpts('SpO2','#2980B9',data.map(d=>d.oxygen_saturation),'chartSpO2');

  // Table
  const tbody=document.getElementById('vhTableBody');
  tbody.innerHTML=data.map(d=>`<tr>
    <td>${new Date(d.recorded_at).toLocaleString()}</td>
    <td>${d.bp_systolic||'-'}/${d.bp_diastolic||'-'}</td><td>${d.pulse_rate||'-'}</td>
    <td>${d.temperature?d.temperature+'°C':'-'}</td><td>${d.oxygen_saturation?d.oxygen_saturation+'%':'-'}</td>
    <td>${d.respiratory_rate||'-'}</td><td>${d.blood_glucose||'-'}</td><td>${d.pain_level??'-'}</td>
    <td>${d.is_flagged==1?'<span class="badge badge-danger">Flagged</span>':'<span class="badge badge-success">Normal</span>'}</td>
  </tr>`).join('');
}

// ── Bedside View ───────────────────────────────────────────
async function openBedsideView(patientId){
  openModal('bedsideModal');
  document.getElementById('bedsideContent').innerHTML='<p class="text-center text-muted" style="padding:3rem;">Loading bedside chart...</p>';
  const r = await nurseAction({action:'get_bedside_view', patient_id: patientId});
  if(!r.success) { document.getElementById('bedsideContent').innerHTML='<p class="text-center" style="color:var(--danger);">'+r.message+'</p>'; return; }
  const p=r.data;
  document.getElementById('bedsideContent').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
      <div class="info-card">
        <h4 style="margin-bottom:1rem;"><i class="fas fa-user" style="color:var(--role-accent);"></i> Patient Details</h4>
        <p><strong>Name:</strong> ${p.name||'—'}</p><p><strong>ID:</strong> ${p.patient_id||'—'}</p>
        <p><strong>Gender:</strong> ${p.gender||'—'}</p><p><strong>DOB:</strong> ${p.date_of_birth||'—'}</p>
        <p><strong>Blood Type:</strong> ${p.blood_type||'—'}</p>
        <p><strong>Allergies:</strong> ${p.allergies||'None known'}</p>
        <p><strong>Doctor:</strong> ${p.doctor||'—'}</p>
      </div>
      <div class="info-card">
        <h4 style="margin-bottom:1rem;"><i class="fas fa-heartbeat" style="color:var(--danger);"></i> Latest Vitals</h4>
        <p><strong>BP:</strong> ${p.bp||'—'}</p><p><strong>HR:</strong> ${p.hr||'—'} bpm</p>
        <p><strong>Temp:</strong> ${p.temp||'—'}°C</p><p><strong>SpO2:</strong> ${p.spo2||'—'}%</p>
        <p><strong>RR:</strong> ${p.rr||'—'}</p>
        <p><strong>Recorded:</strong> ${p.vital_time||'Never'}</p>
        ${p.flagged?'<span class="badge badge-danger" style="margin-top:.5rem;">⚠️ Abnormal Reading</span>':''}
      </div>
    </div>
    <div class="info-card" style="margin-top:1rem;">
      <h4 style="margin-bottom:1rem;"><i class="fas fa-pills" style="color:var(--primary);"></i> Active Prescriptions</h4>
      ${p.prescriptions||'<p class="text-muted">No active prescriptions</p>'}
    </div>`;
}

function refreshVitals(){ location.reload(); }
</script>
