<?php
// MODULE 5: MEDICAL RECORDS (Redesigned v2) — read-only for patient
$my_records = [];
$q = mysqli_query($conn,
  "SELECT mr.*, u.name AS doctor_name, d.specialization
   FROM medical_records mr
   JOIN doctors d ON mr.doctor_id=d.id JOIN users u ON d.user_id=u.id
   WHERE mr.patient_id=$pat_pk AND (mr.patient_visible IS NULL OR mr.patient_visible=1)
   ORDER BY mr.visit_date DESC LIMIT 100");
if ($q) while ($r = mysqli_fetch_assoc($q)) $my_records[] = $r;
$total_records = count($my_records);
$recent_records = count(array_filter($my_records, fn($r) => strtotime($r['visit_date']) >= strtotime('-90 days')));
?>
<div id="sec-records" class="dash-section">

<style>
.rec-card2{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:1.2rem;box-shadow:var(--shadow-sm);overflow:hidden;transition:var(--transition);}
.rec-card2:hover{box-shadow:var(--shadow-md);}
.rec-card2-header{display:flex;align-items:center;gap:1.2rem;padding:1.5rem 1.8rem;cursor:pointer;transition:background .15s;flex-wrap:wrap;}
.rec-card2-header:hover{background:var(--surface-2);}
.rec-diag-chip{display:inline-flex;align-items:center;gap:.5rem;background:linear-gradient(135deg,var(--role-accent),#2F80ED);color:#fff;border-radius:20px;padding:.35rem 1rem;font-size:1.2rem;font-weight:700;white-space:nowrap;}
.rec-expand-body{display:none;border-top:1px solid var(--border);padding:1.5rem 1.8rem;background:var(--surface-2);}
.rec-expand-body.open{display:block;animation:fadeTab .2s ease;}
.rec-field-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;}
.rec-field{background:var(--surface);border-radius:10px;padding:1.2rem;}
.rec-field .rec-field-label{font-size:1rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.4rem;}
.rec-field .rec-field-value{font-size:1.3rem;color:var(--text-primary);}
.vital-chip{display:inline-flex;align-items:center;gap:.4rem;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.3rem .8rem;font-size:1.1rem;font-weight:600;margin:.2rem;}
</style>

  <!-- Page Header -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
      <h2 style="font-size:2rem;font-weight:800;margin-bottom:.3rem;"><i class="fas fa-file-medical" style="color:var(--role-accent);"></i> Medical Records</h2>
      <p style="font-size:1.3rem;color:var(--text-muted);"><?= $total_records ?> total records · <?= $recent_records ?> in last 90 days</p>
    </div>
    <!-- Search + Filter -->
    <div style="display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;">
      <input type="text" id="recSearch2" class="form-control" style="max-width:260px;" placeholder="Search diagnosis, doctor..." oninput="filterRecords2()">
      <input type="date" id="recDateFrom2" class="form-control" style="max-width:150px;" onchange="filterRecords2()" title="From date">
      <input type="date" id="recDateTo2" class="form-control" style="max-width:150px;" onchange="filterRecords2()" title="To date">
    </div>
  </div>

  <!-- Records -->
  <?php if (empty($my_records)): ?>
  <div class="adm-card" style="text-align:center;padding:5rem;">
    <i class="fas fa-folder-open" style="font-size:3.5rem;opacity:.2;display:block;margin-bottom:1rem;"></i>
    <h3 style="color:var(--text-muted);">No Medical Records Found</h3>
    <p style="color:var(--text-muted);font-size:1.3rem;margin-top:.5rem;">Records will appear here after your consultations.</p>
  </div>
  <?php else: foreach ($my_records as $mr):
    $visits_date = $mr['visit_date'] ?? $mr['record_date'] ?? null;
    $sevMap = ['Mild'=>'success','Moderate'=>'warning','Severe'=>'danger','Critical'=>'danger'];
    $sevCls = $sevMap[$mr['severity'] ?? ''] ?? 'info';
    $hasFollowUp = (int)($mr['follow_up_required'] ?? 0);
    // Try to parse vitals JSON or comma-separated
    $vitals_raw = $mr['vital_signs'] ?? '';
  ?>
  <div class="rec-card2 rec-item"
       data-date="<?= $mr['visit_date'] ?>"
       data-doctor="<?= strtolower($mr['doctor_name']) ?>"
       data-diag="<?= strtolower($mr['diagnosis'] ?? '') ?>">
    <!-- Header (clickable) -->
    <div class="rec-card2-header" onclick="toggleRecExpand(<?= $mr['id'] ?>)">
      <!-- Left: Diagnosis + meta -->
      <div style="flex:1;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div class="rec-diag-chip"><i class="fas fa-stethoscope"></i> <?= htmlspecialchars($mr['diagnosis'] ?? '—') ?></div>
        <?php if ($mr['severity'] ?? ''): ?>
        <span class="adm-badge adm-badge-<?= $sevCls ?>"><?= htmlspecialchars($mr['severity']) ?></span>
        <?php endif; ?>
        <?php if ($hasFollowUp): ?>
        <span class="adm-badge adm-badge-warning"><i class="fas fa-calendar-check"></i> Follow-up <?= $mr['follow_up_date'] ? date('d M', strtotime($mr['follow_up_date'])) : '' ?></span>
        <?php endif; ?>
        <div style="font-size:1.2rem;color:var(--text-muted);">
          <i class="fas fa-user-doctor" style="color:var(--primary);"></i> Dr. <?= htmlspecialchars($mr['doctor_name']) ?>
          &nbsp;·&nbsp;
          <i class="fas fa-calendar"></i> <?= $visits_date ? date('d M Y', strtotime($visits_date)) : '—' ?>
        </div>
      </div>
      <!-- Right: Expand chevron -->
      <div style="display:flex;align-items:center;gap:.8rem;color:var(--text-muted);">
        <span style="font-size:1.1rem;"><?= htmlspecialchars($mr['specialization'] ?? '') ?></span>
        <i class="fas fa-chevron-down" id="recChev-<?= $mr['id'] ?>" style="transition:transform .25s;"></i>
      </div>
    </div>
    <!-- Expanded Detail Body -->
    <div class="rec-expand-body" id="recExpand-<?= $mr['id'] ?>">
      <div class="rec-field-grid">
        <?php foreach (['Symptoms'=>'symptoms','Treatment'=>'treatment','Notes'=>'notes','Treatment Plan'=>'treatment_plan'] as $lbl => $key): if (empty($mr[$key])) continue; ?>
        <div class="rec-field">
          <div class="rec-field-label"><?= $lbl ?></div>
          <div class="rec-field-value"><?= htmlspecialchars($mr[$key]) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if ($vitals_raw): ?>
        <div class="rec-field" style="grid-column:1/-1;">
          <div class="rec-field-label"><i class="fas fa-heartbeat" style="color:var(--danger);margin-right:.3rem;"></i> Vital Signs</div>
          <div style="margin-top:.4rem;">
            <?php
            // Try JSON decode first, otherwise show raw
            $vitals_decoded = json_decode($vitals_raw, true);
            if (is_array($vitals_decoded)):
              foreach ($vitals_decoded as $vk => $vv): if (empty($vv)) continue;
            ?>
            <span class="vital-chip"><i class="fas fa-heartbeat" style="color:var(--danger);"></i> <?= htmlspecialchars($vk) ?>: <strong><?= htmlspecialchars($vv) ?></strong></span>
            <?php endforeach; else: ?>
            <span style="font-size:1.25rem;"><?= htmlspecialchars($vitals_raw) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <!-- Actions -->
      <div style="display:flex;gap:.8rem;margin-top:1.2rem;flex-wrap:wrap;">
        <button class="btn-icon btn btn-primary btn-sm" onclick='printRecord2(<?= json_encode($mr) ?>)'>
          <span class="btn-text"><i class="fas fa-print"></i> Print Record</span>
        </button>
      </div>
    </div>
  </div>
  <?php endforeach; endif; ?>
</div>

<script>
function toggleRecExpand(id){
  const body = document.getElementById('recExpand-'+id);
  const chev = document.getElementById('recChev-'+id);
  const isOpen = body.classList.contains('open');
  body.classList.toggle('open', !isOpen);
  if (chev) chev.style.transform = isOpen ? '' : 'rotate(180deg)';
}

function filterRecords2(){
  const q  = (document.getElementById('recSearch2').value || '').toLowerCase();
  const df = document.getElementById('recDateFrom2').value;
  const dt = document.getElementById('recDateTo2').value;
  document.querySelectorAll('.rec-item').forEach(r => {
    const d   = r.dataset.date;
    const doc = r.dataset.doctor || '';
    const dg  = r.dataset.diag || '';
    let show = true;
    if (q && !doc.includes(q) && !dg.includes(q)) show = false;
    if (df && d < df) show = false;
    if (dt && d > dt) show = false;
    r.style.display = show ? '' : 'none';
  });
}

function printRecord2(mr){
  const w = window.open('','','width=640,height=800');
  w.document.write(`<!DOCTYPE html><html><head><title>Medical Record — RMU Medical Sickbay</title>
  <style>body{font-family:'Segoe UI',sans-serif;padding:2.5rem;font-size:14px;color:#1a2035;}
  h2{color:#8e44ad;}p.meta{color:#888;font-size:12px;margin-bottom:1.5rem;}
  .row{padding:.55rem 0;border-bottom:1px solid #eee;display:flex;gap:1rem;}
  .lbl{color:#888;font-size:12px;text-transform:uppercase;letter-spacing:.04em;min-width:120px;padding-top:2px;}
  .val{font-weight:600;flex:1;}
  .badge{background:#8e44ad;color:#fff;border-radius:12px;padding:3px 10px;font-size:11px;}
  .footer{text-align:center;color:#bbb;margin-top:2rem;font-size:12px;}
  </style></head><body>
  <h2>RMU Medical Sickbay — Medical Record</h2>
  <p class="meta">Printed on ${new Date().toLocaleString()} &mdash; Confidential Patient Record</p><hr>
  <div class="row"><span class="lbl">Record ID</span><span class="val">${mr.record_id||mr.id}</span></div>
  <div class="row"><span class="lbl">Visit Date</span><span class="val">${mr.visit_date}</span></div>
  <div class="row"><span class="lbl">Doctor</span><span class="val">Dr. ${mr.doctor_name} (${mr.specialization})</span></div>
  <div class="row"><span class="lbl">Diagnosis</span><span class="val"><span class="badge">${mr.diagnosis||'—'}</span></span></div>
  ${mr.severity ? `<div class="row"><span class="lbl">Severity</span><span class="val">${mr.severity}</span></div>` : ''}
  ${mr.symptoms ? `<div class="row"><span class="lbl">Symptoms</span><span class="val">${mr.symptoms}</span></div>` : ''}
  ${mr.treatment ? `<div class="row"><span class="lbl">Treatment</span><span class="val">${mr.treatment}</span></div>` : ''}
  ${mr.treatment_plan ? `<div class="row"><span class="lbl">Treatment Plan</span><span class="val">${mr.treatment_plan}</span></div>` : ''}
  ${mr.vital_signs ? `<div class="row"><span class="lbl">Vital Signs</span><span class="val">${mr.vital_signs}</span></div>` : ''}
  ${mr.notes ? `<div class="row"><span class="lbl">Notes</span><span class="val">${mr.notes}</span></div>` : ''}
  ${mr.follow_up_required ? `<div class="row"><span class="lbl">Follow-up</span><span class="val">${mr.follow_up_date||'Recommended'}</span></div>` : ''}
  <div class="footer">This document was generated from RMU Medical Sickbay Management System.</div>
  </body></html>`);
  w.document.close(); w.print();
}
</script>
