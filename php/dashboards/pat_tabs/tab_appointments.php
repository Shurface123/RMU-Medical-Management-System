<?php
// MODULE 3: MY APPOINTMENTS (Redesigned v2)
$all_appts = [];
$q = mysqli_query($conn, "SELECT a.*, u.name AS doctor_name, d.specialization, d.id AS doc_id
  FROM appointments a JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id
  WHERE a.patient_id=$pat_pk ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 100");
if ($q) while ($r = mysqli_fetch_assoc($q)) $all_appts[] = $r;

// Quick stats
$stat_upcoming = count(array_filter($all_appts, fn($a) => $a['appointment_date'] >= $today && !in_array($a['status'], ['Cancelled','No-Show'])));
$stat_pending  = count(array_filter($all_appts, fn($a) => $a['status'] === 'Pending'));
$stat_completed = count(array_filter($all_appts, fn($a) => in_array($a['status'], ['Completed','Approved','Confirmed'])));
$stat_cancelled = count(array_filter($all_appts, fn($a) => in_array($a['status'], ['Cancelled','No-Show'])));
?>
<div id="sec-appointments" class="dash-section">

<style>
.appt-kanban-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:1rem;margin-bottom:2rem;}
.appt-stat-box{padding:1.5rem;border-radius:var(--radius-md);text-align:center;cursor:pointer;transition:var(--transition);border:1px solid var(--border);}
.appt-stat-box:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);}
.appt-stat-box .num{font-size:2.8rem;font-weight:800;line-height:1;margin-bottom:.3rem;}
.appt-stat-box .lbl{font-size:1.15rem;color:var(--text-muted);}

.appt-card2{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:0;margin-bottom:1rem;box-shadow:var(--shadow-sm);border-left:5px solid transparent;transition:var(--transition);overflow:hidden;}
.appt-card2:hover{box-shadow:var(--shadow-md);transform:translateX(3px);}
.appt-card2.status-Confirmed,.appt-card2.status-Approved,.appt-card2.status-Completed{border-left-color:var(--success);}
.appt-card2.status-Pending{border-left-color:var(--warning);}
.appt-card2.status-Cancelled,.appt-card2.status-No-Show{border-left-color:var(--danger);opacity:.75;}
.appt-card2.status-Rescheduled{border-left-color:var(--info);}
.appt-card2.is-today{background:linear-gradient(135deg,rgba(142,68,173,.04),rgba(47,128,237,.03));}

.appt-card2-header{display:flex;align-items:center;gap:1.2rem;padding:1.4rem 1.8rem;flex-wrap:wrap;}
.appt-card2-time{min-width:72px;text-align:center;background:var(--surface-2);border-radius:10px;padding:.6rem .8rem;flex-shrink:0;}
.appt-card2-time .t{font-size:1.6rem;font-weight:800;line-height:1;color:var(--role-accent);}
.appt-card2-time .ampm{font-size:1rem;color:var(--text-muted);}
.appt-card2-body{flex:1;}
.appt-card2-actions{display:flex;gap:.5rem;flex-shrink:0;}

.appt-expand-body{display:none;border-top:1px solid var(--border);padding:1.2rem 1.8rem;background:var(--surface-2);font-size:1.25rem;}
.appt-expand-body.open{display:block;animation:fadeTab .2s ease;}
</style>

  <!-- Stat Strip -->
  <div class="appt-kanban-strip">
    <div class="appt-stat-box" style="background:var(--primary-light);" onclick="filterAppts2('upcoming')">
      <div class="num" style="color:var(--primary);"><?= $stat_upcoming ?></div>
      <div class="lbl">Upcoming</div>
    </div>
    <div class="appt-stat-box" style="background:var(--warning-light);" onclick="filterAppts2('Pending')">
      <div class="num" style="color:var(--warning);"><?= $stat_pending ?></div>
      <div class="lbl">Pending</div>
    </div>
    <div class="appt-stat-box" style="background:var(--success-light);" onclick="filterAppts2('completed')">
      <div class="num" style="color:var(--success);"><?= $stat_completed ?></div>
      <div class="lbl">Completed</div>
    </div>
    <div class="appt-stat-box" style="background:var(--danger-light);" onclick="filterAppts2('Cancelled')">
      <div class="num" style="color:var(--danger);"><?= $stat_cancelled ?></div>
      <div class="lbl">Cancelled</div>
    </div>
    <div class="appt-stat-box" style="background:var(--surface-2);" onclick="filterAppts2('all')">
      <div class="num" style="color:var(--text-secondary);"><?= count($all_appts) ?></div>
      <div class="lbl">Total</div>
    </div>
  </div>

  <!-- Header Row -->
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <div class="filter-tabs" style="margin:0;" id="apptFilters2">
      <span class="ftab active" onclick="filterAppts2('all',this)">All</span>
      <span class="ftab" onclick="filterAppts2('upcoming',this)">Upcoming</span>
      <span class="ftab" onclick="filterAppts2('Pending',this)">Pending</span>
      <span class="ftab" onclick="filterAppts2('completed',this)">Completed</span>
      <span class="ftab" onclick="filterAppts2('Cancelled',this)">Cancelled</span>
    </div>
    <button class="btn-icon btn btn-primary btn-sm" onclick="showTab('book',document.querySelector('.adm-nav-item[onclick*=book]'))">
      <span class="btn-text"><i class="fas fa-plus"></i> Book New</span>
    </button>
  </div>

  <!-- Appointment Cards -->
  <?php if (empty($all_appts)): ?>
  <div class="adm-card" style="text-align:center;padding:4rem;">
    <i class="fas fa-calendar-xmark" style="font-size:3.5rem;opacity:.2;display:block;margin-bottom:1rem;"></i>
    <h3 style="color:var(--text-muted);margin-bottom:.5rem;">No Appointments Yet</h3>
    <p style="color:var(--text-muted);font-size:1.3rem;margin-bottom:1.5rem;">Book your first appointment to get started.</p>
    <button class="btn-icon btn btn-primary" onclick="showTab('book',document.querySelector('.adm-nav-item[onclick*=book]'))">
      <span class="btn-text"><i class="fas fa-calendar-plus"></i> Book Appointment</span>
    </button>
  </div>
  <?php else: foreach ($all_appts as $a):
    $dt = $a['appointment_date'];
    $isPast = strtotime($dt) < strtotime($today);
    $isToday = ($dt === $today);
    $cancelled = in_array($a['status'], ['Cancelled','No-Show']);
    $can_act = in_array($a['status'], ['Pending','Confirmed','Approved']);
    $scMap = ['Approved'=>'success','Confirmed'=>'success','Completed'=>'success','Pending'=>'warning','Rescheduled'=>'info','Cancelled'=>'danger','No-Show'=>'danger'];
    $sc = $scMap[$a['status']] ?? 'primary';
    $tArr = explode(':', $a['appointment_time']);
    $hr = (int)$tArr[0];
    $mn = $tArr[1] ?? '00';
    $ampm = $hr >= 12 ? 'PM' : 'AM';
    $h12 = $hr > 12 ? $hr-12 : ($hr === 0 ? 12 : $hr);
    $filterClass = 'appt-record';
    $dataFilter = strtolower($a['status']);
    if ($isToday) $dataFilter .= ' today';
    if (!$isPast && !$cancelled) $dataFilter .= ' upcoming';
    if (in_array($a['status'], ['Completed','Approved','Confirmed'])) $dataFilter .= ' completed';
  ?>
  <div class="appt-card2 status-<?= $a['status'] ?><?= $isToday ? ' is-today' : '' ?> <?= $filterClass ?>"
       data-filter="<?= htmlspecialchars($dataFilter) ?>"
       data-status="<?= $a['status'] ?>"
       data-date="<?= $a['appointment_date'] ?>">
    <div class="appt-card2-header">
      <!-- Time Badge -->
      <div class="appt-card2-time">
        <div class="t"><?= $h12 ?>:<?= $mn ?></div>
        <div class="ampm"><?= $ampm ?></div>
      </div>
      <!-- Info -->
      <div class="appt-card2-body">
        <div style="display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;margin-bottom:.4rem;">
          <span style="font-size:1.05rem;color:var(--text-muted);">
            <i class="fas fa-calendar"></i>
            <?= date('l, d M Y', strtotime($dt)) ?>
          </span>
          <?= $isToday ? '<span style="background:var(--role-accent);color:#fff;border-radius:12px;padding:.15rem .7rem;font-size:.95rem;font-weight:700;">Today</span>' : '' ?>
          <?= $isPast && !$isToday ? '<span style="background:var(--surface-2);color:var(--text-muted);border-radius:12px;padding:.15rem .7rem;font-size:.95rem;">Past</span>' : '' ?>
        </div>
        <div style="font-size:1.5rem;font-weight:700;display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;">
          Dr. <?= htmlspecialchars($a['doctor_name']) ?>
          <span class="adm-badge adm-badge-<?= $sc ?>"><?= $a['status'] ?></span>
        </div>
        <div style="font-size:1.2rem;color:var(--text-muted);margin-top:.3rem;">
          <span><i class="fas fa-stethoscope"></i> <?= htmlspecialchars($a['specialization']) ?></span>
          &nbsp;·&nbsp;
          <span><i class="fas fa-tag"></i> <?= htmlspecialchars($a['service_type'] ?? 'Consultation') ?></span>
          &nbsp;·&nbsp;
          <span style="font-size:1.05rem;">ID: <?= htmlspecialchars($a['appointment_id'] ?? '#' . $a['id']) ?></span>
        </div>
      </div>
      <!-- Actions -->
      <div class="appt-card2-actions">
        <button class="btn btn-primary btn-sm" onclick="toggleApptExpand(<?= $a['id'] ?>)" title="Details">
          <span class="btn-text"><i class="fas fa-chevron-down" id="chevron-<?= $a['id'] ?>"></i></span>
        </button>
        <?php if ($can_act): ?>
        <button class="btn btn-danger btn-sm" onclick="cancelAppt2(<?= $a['id'] ?>)" title="Cancel">
          <span class="btn-text"><i class="fas fa-times"></i></span>
        </button>
        <?php endif; ?>
      </div>
    </div>
    <!-- Expandable Detail -->
    <div class="appt-expand-body" id="apptExpand-<?= $a['id'] ?>">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
        <?php if ($a['reason'] ?? ''): ?>
        <div>
          <div style="font-size:1.05rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.3rem;"><i class="fas fa-comment-medical" style="color:var(--role-accent);"></i> Reason</div>
          <div><?= htmlspecialchars($a['reason']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($a['notes'] ?? ''): ?>
        <div>
          <div style="font-size:1.05rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.3rem;"><i class="fas fa-notes-medical" style="color:var(--primary);"></i> Doctor Notes</div>
          <div><?= htmlspecialchars(substr($a['notes'], 0, 200)) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($a['reschedule_date'] ?? ''): ?>
        <div>
          <div style="font-size:1.05rem;color:var(--info);font-weight:600;margin-bottom:.3rem;"><i class="fas fa-calendar-pen"></i> Rescheduled To</div>
          <div><?= date('d M Y', strtotime($a['reschedule_date'])) ?><?= $a['reschedule_time'] ? ' at ' . date('g:i A', strtotime($a['reschedule_time'])) : '' ?></div>
        </div>
        <?php endif; ?>
        <?php if ($a['cancellation_reason'] ?? ''): ?>
        <div>
          <div style="font-size:1.05rem;color:var(--danger);font-weight:600;margin-bottom:.3rem;"><i class="fas fa-comment-slash"></i> Cancellation Reason</div>
          <div><?= htmlspecialchars($a['cancellation_reason']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; endif; ?>
</div>

<!-- Cancel Modal -->
<div class="modal-bg" id="modalCancelAppt2">
  <div class="modal-box">
    <div class="modal-header">
      <h3 style="color:var(--danger);"><i class="fas fa-times-circle" style="margin-right:.5rem;"></i>Cancel Appointment</h3>
      <button class="btn btn-primary modal-close" onclick="closeModal('modalCancelAppt2')"><span class="btn-text">&times;</span></button>
    </div>
    <div style="background:var(--danger-light);color:var(--danger);border-radius:10px;padding:1rem 1.4rem;margin-bottom:1.2rem;font-size:1.25rem;">
      <i class="fas fa-triangle-exclamation"></i> Your doctor will be notified of this cancellation.
    </div>
    <form onsubmit="confirmCancelAppt2(event)">
      <input type="hidden" id="cancelApptId2" name="id">
      <div class="form-group"><label>Reason for Cancellation *</label>
        <textarea name="reason" id="cancelReasonInput" class="form-control" rows="3" required placeholder="Please provide a reason..."></textarea></div>
      <button type="submit" class="btn-icon btn btn-danger" style="width:100%;justify-content:center;padding:1.2rem;">
        <span class="btn-text"><i class="fas fa-times"></i> Confirm Cancellation</span>
      </button>
    </form>
  </div>
</div>

<script>
function toggleApptExpand(id){
  const body = document.getElementById('apptExpand-'+id);
  const chevron = document.getElementById('chevron-'+id);
  const isOpen = body.classList.contains('open');
  body.classList.toggle('open', !isOpen);
  if (chevron) chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
}

function filterAppts2(filter, btn){
  if (btn) {
    document.querySelectorAll('#apptFilters2 .ftab').forEach(f => f.classList.remove('active'));
    btn.classList.add('active');
  }
  document.querySelectorAll('.appt-card2').forEach(c => {
    const df = c.dataset.filter || '';
    const status = c.dataset.status || '';
    if (filter === 'all') { c.style.display = ''; return; }
    if (filter === 'upcoming') { c.style.display = df.includes('upcoming') ? '' : 'none'; return; }
    if (filter === 'completed') { c.style.display = df.includes('completed') ? '' : 'none'; return; }
    c.style.display = df.includes(filter.toLowerCase()) ? '' : 'none';
  });
}

function cancelAppt2(id){
  document.getElementById('cancelApptId2').value = id;
  document.getElementById('cancelReasonInput').value = '';
  openModal('modalCancelAppt2');
}

async function confirmCancelAppt2(e){
  e.preventDefault();
  const fd = new FormData(e.target);
  const r = await patAction({action:'cancel_appointment', id:fd.get('id'), reason:fd.get('reason')});
  if (r.success) {
    toast('Appointment cancelled');
    closeModal('modalCancelAppt2');
    location.reload();
  } else { toast(r.message || 'Error', 'danger'); }
}
</script>
