<?php
// MODULE 4: MY PRESCRIPTIONS (Redesigned v2)
$my_rx = [];
$q = mysqli_query($conn, "SELECT pr.*, u.name AS doctor_name, d.specialization
  FROM prescriptions pr JOIN doctors d ON pr.doctor_id=d.id JOIN users u ON d.user_id=u.id
  WHERE pr.patient_id=$pat_pk ORDER BY pr.prescription_date DESC LIMIT 100");
if ($q) while ($r = mysqli_fetch_assoc($q)) $my_rx[] = $r;
$rx_active   = count(array_filter($my_rx, fn($r) => in_array($r['status'], ['Active','Pending'])));
$rx_refill   = count(array_filter($my_rx, fn($r) => $r['status'] === 'Refill Requested'));
$rx_done     = count(array_filter($my_rx, fn($r) => in_array($r['status'], ['Dispensed','Completed'])));
?>
<div id="sec-prescriptions" class="dash-section">

<style>
.rx-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:1rem;box-shadow:var(--shadow-sm);border-left:5px solid transparent;overflow:hidden;transition:var(--transition);}
.rx-card:hover{box-shadow:var(--shadow-md);}
.rx-card.st-active,.rx-card.st-pending{border-left-color:var(--warning);}
.rx-card.st-dispensed,.rx-card.st-completed{border-left-color:var(--success);}
.rx-card.st-cancelled{border-left-color:var(--danger);opacity:.75;}
.rx-card.st-refill{border-left-color:var(--info);}

.rx-pill-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0;}

.rx-refill-bar{height:6px;border-radius:3px;background:var(--border);overflow:hidden;margin-top:.5rem;}
.rx-refill-bar-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--success),var(--primary));transition:width .5s;}

.rx-stat-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:1rem;margin-bottom:2rem;}
.rx-stat{padding:1.2rem;border-radius:var(--radius-md);text-align:center;border:1px solid var(--border);cursor:pointer;transition:var(--transition);}
.rx-stat:hover{transform:translateY(-2px);box-shadow:var(--shadow-sm);}
.rx-stat .num{font-size:2.5rem;font-weight:800;line-height:1.1;}
.rx-stat .lbl{font-size:1.1rem;color:var(--text-muted);}
</style>

  <!-- Stat Strip -->
  <div class="rx-stat-strip">
    <div class="rx-stat" style="background:var(--warning-light);" onclick="filterRx2('active')">
      <div class="num" style="color:var(--warning);"><?= $rx_active ?></div>
      <div class="lbl">Active</div>
    </div>
    <div class="rx-stat" style="background:var(--info-light);" onclick="filterRx2('refill')">
      <div class="num" style="color:var(--info);"><?= $rx_refill ?></div>
      <div class="lbl">Refill Pending</div>
    </div>
    <div class="rx-stat" style="background:var(--success-light);" onclick="filterRx2('completed')">
      <div class="num" style="color:var(--success);"><?= $rx_done ?></div>
      <div class="lbl">Completed</div>
    </div>
    <div class="rx-stat" style="background:var(--surface-2);" onclick="filterRx2('all')">
      <div class="num" style="color:var(--text-secondary);"><?= count($my_rx) ?></div>
      <div class="lbl">Total</div>
    </div>
  </div>

  <!-- Filter Tabs -->
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <div class="filter-tabs" style="margin:0;" id="rxFilters2">
      <span class="ftab active" onclick="filterRx2('all',this)">All</span>
      <span class="ftab" onclick="filterRx2('active',this)">Active</span>
      <span class="ftab" onclick="filterRx2('completed',this)">Completed</span>
      <span class="ftab" onclick="filterRx2('refill',this)">Refill Requested</span>
    </div>
  </div>

  <!-- Prescription Cards -->
  <?php if (empty($my_rx)): ?>
  <div class="adm-card" style="text-align:center;padding:4rem;">
    <i class="fas fa-prescription-bottle" style="font-size:3.5rem;opacity:.2;display:block;margin-bottom:1rem;"></i>
    <h3 style="color:var(--text-muted);">No Prescriptions Yet</h3>
    <p style="color:var(--text-muted);font-size:1.3rem;margin-top:.5rem;">Your prescriptions will appear here after consultations with your doctor.</p>
  </div>
  <?php else: foreach ($my_rx as $rx):
    $scMap = ['Active'=>'warning','Pending'=>'warning','Dispensed'=>'success','Completed'=>'success','Cancelled'=>'danger','Refill Requested'=>'info'];
    $sc = $scMap[$rx['status']] ?? 'primary';
    $statusCls = in_array($rx['status'], ['Active','Pending']) ? 'active'
      : (in_array($rx['status'], ['Dispensed','Completed']) ? 'completed'
      : ($rx['status'] === 'Refill Requested' ? 'refill'
      : ($rx['status'] === 'Cancelled' ? 'cancelled' : 'other')));
    $refillUsed    = (int)($rx['refill_count'] ?? 0);
    $refillAllowed = (int)($rx['refills_allowed'] ?? 0);
    $refillPct     = $refillAllowed > 0 ? round(($refillUsed / $refillAllowed) * 100) : 0;
  ?>
  <div class="rx-card st-<?= $statusCls ?> rx-record" data-filter="<?= $statusCls ?>">
    <div style="display:flex;align-items:flex-start;gap:1.2rem;padding:1.5rem;flex-wrap:wrap;">
      <!-- Pill Icon -->
      <div class="rx-pill-icon" style="background:<?= $sc === 'warning' ? 'var(--warning-light)' : ($sc === 'success' ? 'var(--success-light)' : ($sc === 'info' ? 'var(--info-light)' : 'var(--danger-light)')) ?>;color:var(--<?= $sc ?>);">
        <i class="fas fa-pills"></i>
      </div>
      <!-- Main Info -->
      <div style="flex:1;min-width:180px;">
        <div style="font-size:1.6rem;font-weight:800;display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;">
          <?= htmlspecialchars($rx['medication_name']) ?>
          <span class="adm-badge adm-badge-<?= $sc ?>"><?= $rx['status'] ?></span>
        </div>
        <div style="font-size:1.25rem;color:var(--text-secondary);margin:.4rem 0;">
          <span><i class="fas fa-capsules" style="color:var(--role-accent);"></i> <?= htmlspecialchars($rx['dosage']) ?></span>
          &nbsp;·&nbsp;
          <span><i class="fas fa-clock"></i> <?= htmlspecialchars($rx['frequency']) ?></span>
          <?= $rx['duration'] ? ' &nbsp;·&nbsp; <span><i class="fas fa-hourglass-half"></i> ' . htmlspecialchars($rx['duration']) . '</span>' : '' ?>
        </div>
        <div style="font-size:1.15rem;color:var(--text-muted);">
          <i class="fas fa-user-doctor"></i> Dr. <?= htmlspecialchars($rx['doctor_name']) ?>
          &nbsp;·&nbsp;
          <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($rx['prescription_date'])) ?>
        </div>
        <?php if ($rx['instructions'] ?? ''): ?>
        <div style="margin-top:.6rem;font-size:1.2rem;color:var(--text-secondary);background:var(--surface-2);border-radius:8px;padding:.6rem 1rem;display:inline-flex;align-items:center;gap:.5rem;">
          <i class="fas fa-info-circle" style="color:var(--primary);"></i> <?= htmlspecialchars($rx['instructions']) ?>
        </div>
        <?php endif; ?>
        <!-- Refill Progress -->
        <?php if ($refillAllowed > 0): ?>
        <div style="margin-top:.8rem;max-width:280px;">
          <div style="display:flex;justify-content:space-between;font-size:1.1rem;color:var(--text-muted);margin-bottom:.3rem;">
            <span>Refills Used: <?= $refillUsed ?>/<?= $refillAllowed ?></span>
            <span><?= $refillPct ?>%</span>
          </div>
          <div class="rx-refill-bar">
            <div class="rx-refill-bar-fill" style="width:<?= $refillPct ?>%;background:<?= $refillPct >= 100 ? 'var(--danger)' : 'linear-gradient(90deg,var(--success),var(--primary))' ?>;"></div>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <!-- Actions -->
      <div style="display:flex;flex-direction:column;gap:.5rem;flex-shrink:0;">
        <button class="btn-icon btn btn-primary btn-sm" onclick='viewRxDetail2(<?= json_encode($rx) ?>)' title="View Full Details">
          <span class="btn-text"><i class="fas fa-eye"></i> Details</span>
        </button>
        <?php if (in_array($rx['status'], ['Active','Dispensed','Completed']) && ($refillAllowed === 0 || $refillUsed < $refillAllowed)): ?>
        <button class="btn-icon btn btn-primary btn-sm" onclick="requestRefill2(<?= $rx['id'] ?>)" title="Request Refill" style="background:var(--info);border-color:var(--info);">
          <span class="btn-text"><i class="fas fa-redo"></i> Refill</span>
        </button>
        <?php endif; ?>
        <button class="btn-icon btn btn-sm" onclick='printRx2(<?= json_encode($rx) ?>)' title="Print" style="background:var(--surface-2);border:1px solid var(--border);">
          <span class="btn-text"><i class="fas fa-print"></i></span>
        </button>
      </div>
    </div>
  </div>
  <?php endforeach; endif; ?>
</div>

<!-- Rx Detail Modal -->
<div class="modal-bg" id="modalRxDetail2">
  <div class="modal-box" style="max-width:680px;">
    <div class="modal-header">
      <h3><i class="fas fa-prescription" style="color:var(--warning);margin-right:.5rem;"></i>Prescription Details</h3>
      <button class="btn btn-primary modal-close" onclick="closeModal('modalRxDetail2')"><span class="btn-text">&times;</span></button>
    </div>
    <div id="rxDetailBody2" style="font-size:1.3rem;line-height:1.9;"></div>
    <div style="margin-top:1.5rem;display:flex;gap:.8rem;justify-content:flex-end;">
      <button class="btn-icon btn btn-primary btn-sm" id="rxPrintBtn2"><span class="btn-text"><i class="fas fa-print"></i> Print</span></button>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('modalRxDetail2')"><span class="btn-text">Close</span></button>
    </div>
  </div>
</div>

<!-- Refill Request Modal -->
<div class="modal-bg" id="modalRefill2">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-redo" style="color:var(--info);margin-right:.5rem;"></i>Request Refill</h3>
      <button class="btn btn-primary modal-close" onclick="closeModal('modalRefill2')"><span class="btn-text">&times;</span></button>
    </div>
    <p style="font-size:1.3rem;color:var(--text-secondary);margin-bottom:1.2rem;">Your doctor will be notified and will review your refill request.</p>
    <form onsubmit="confirmRefill2(event)">
      <input type="hidden" id="refillRxId2" name="prescription_id">
      <div class="form-group"><label>Notes for your doctor (optional)</label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Any updates or notes for your doctor..."></textarea></div>
      <button type="submit" class="btn-icon btn btn-primary" style="width:100%;justify-content:center;padding:1.2rem;">
        <span class="btn-text"><i class="fas fa-paper-plane"></i> Send Refill Request</span>
      </button>
    </form>
  </div>
</div>

<script>
let currentRx2 = null;

function filterRx2(filter, btn){
  if (btn) {
    document.querySelectorAll('#rxFilters2 .ftab').forEach(f => f.classList.remove('active'));
    btn.classList.add('active');
  }
  document.querySelectorAll('.rx-record').forEach(r => {
    const df = r.dataset.filter || '';
    if (filter === 'all') r.style.display='';
    else if (filter === 'active') r.style.display=(df==='active'||df==='pending')?'':'none';
    else r.style.display=df===filter?'':'none';
  });
}

const scColors2 = {Active:'warning',Pending:'warning',Dispensed:'success',Completed:'success',Cancelled:'danger','Refill Requested':'info'};

function viewRxDetail2(rx){
  currentRx2 = rx;
  const sc = scColors2[rx.status] || 'primary';
  let h = `<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
    <div style="background:var(--surface-2);border-radius:10px;padding:1.2rem;">
      <div style="font-size:1rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.3rem;">Medication</div>
      <div style="font-size:1.5rem;font-weight:800;">${rx.medication_name}</div>
    </div>
    <div style="background:var(--surface-2);border-radius:10px;padding:1.2rem;">
      <div style="font-size:1rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.3rem;">Status</div>
      <span class="adm-badge adm-badge-${sc}" style="font-size:1.2rem;">${rx.status}</span>
    </div>
  </div>
  <div style="display:grid;gap:.6rem;">
    <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:.6rem 0;"><span style="color:var(--text-muted);">Prescription ID</span><strong>${rx.prescription_id||'#'+rx.id}</strong></div>
    <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:.6rem 0;"><span style="color:var(--text-muted);">Dosage</span><strong>${rx.dosage}</strong></div>
    <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:.6rem 0;"><span style="color:var(--text-muted);">Frequency</span><strong>${rx.frequency}</strong></div>
    <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:.6rem 0;"><span style="color:var(--text-muted);">Duration</span><strong>${rx.duration||'—'}</strong></div>
    <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:.6rem 0;"><span style="color:var(--text-muted);">Quantity</span><strong>${rx.quantity||'—'}</strong></div>
    <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:.6rem 0;"><span style="color:var(--text-muted);">Prescribed By</span><strong>Dr. ${rx.doctor_name}</strong></div>
    <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:.6rem 0;"><span style="color:var(--text-muted);">Date Issued</span><strong>${rx.prescription_date}</strong></div>
    <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:.6rem 0;"><span style="color:var(--text-muted);">Refills Allowed</span><strong>${rx.refills_allowed||0}</strong></div>
    <div style="display:flex;justify-content:space-between;padding:.6rem 0;"><span style="color:var(--text-muted);">Refills Used</span><strong>${rx.refill_count||0}</strong></div>
  </div>`;
  if (rx.instructions) h += `<div style="margin-top:1rem;background:var(--info-light);border-left:4px solid var(--info);border-radius:0 8px 8px 0;padding:1rem 1.2rem;color:var(--info);"><i class="fas fa-info-circle"></i> ${rx.instructions}</div>`;
  document.getElementById('rxDetailBody2').innerHTML = h;
  document.getElementById('rxPrintBtn2').onclick = () => printRx2(rx);
  openModal('modalRxDetail2');
}

function requestRefill2(id){
  document.getElementById('refillRxId2').value = id;
  openModal('modalRefill2');
}

async function confirmRefill2(e){
  e.preventDefault();
  const fd = new FormData(e.target);
  const r = await patAction({action:'request_refill', prescription_id:fd.get('prescription_id'), notes:fd.get('notes')});
  if (r.success) { toast('Refill request sent to your doctor!'); closeModal('modalRefill2'); location.reload(); }
  else toast(r.message||'Error','danger');
}

function printRx2(rx){
  const w = window.open('','','width=620,height=750');
  w.document.write(`<!DOCTYPE html><html><head><title>Prescription — RMU Medical Sickbay</title>
  <style>body{font-family:'Segoe UI',sans-serif;padding:2.5rem;font-size:14px;color:#1a2035;}
  h2{color:#8e44ad;margin-bottom:.3rem;}p.meta{color:#666;font-size:12px;margin-bottom:1.5rem;}
  .row{display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid #eee;}
  .lbl{color:#888;}.val{font-weight:700;}
  .footer{text-align:center;color:#bbb;margin-top:2rem;font-size:12px;}
  .badge{background:#f39c12;color:#fff;padding:3px 10px;border-radius:12px;font-size:11px;}
  </style></head><body>
  <h2>RMU Medical Sickbay</h2><p class="meta">Patient Prescription Document &mdash; Printed on ${new Date().toLocaleString()}</p><hr>
  <div class="row"><span class="lbl">Prescription ID</span><span class="val">${rx.prescription_id||rx.id}</span></div>
  <div class="row"><span class="lbl">Medication</span><span class="val">${rx.medication_name}</span></div>
  <div class="row"><span class="lbl">Dosage</span><span class="val">${rx.dosage}</span></div>
  <div class="row"><span class="lbl">Frequency</span><span class="val">${rx.frequency}</span></div>
  <div class="row"><span class="lbl">Duration</span><span class="val">${rx.duration||'—'}</span></div>
  <div class="row"><span class="lbl">Quantity</span><span class="val">${rx.quantity||'—'}</span></div>
  ${rx.instructions?`<div class="row"><span class="lbl">Instructions</span><span class="val">${rx.instructions}</span></div>`:''}
  <div class="row"><span class="lbl">Prescribed By</span><span class="val">Dr. ${rx.doctor_name}</span></div>
  <div class="row"><span class="lbl">Date Issued</span><span class="val">${rx.prescription_date}</span></div>
  <div class="row"><span class="lbl">Status</span><span class="val"><span class="badge">${rx.status}</span></span></div>
  <div class="footer">RMU Medical Sickbay &mdash; Confidential Patient Record</div>
  </body></html>`);
  w.document.close(); w.print();
}
</script>
