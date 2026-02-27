<?php
// ============================================================
// PATIENT DASHBOARD  — RMU Medical Sickbay
// ============================================================
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: /RMU-Medical-Management-System/php/login.php');
    exit;
}

require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');
$user_id = (int)$_SESSION['user_id'];

// ── Patient Record ─────────────────────────────────────────────────────────
$pat_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.id AS pat_pk, p.patient_id, p.blood_group, p.allergies,
            p.chronic_conditions, p.insurance_provider,
            p.emergency_contact_name, p.emergency_contact_phone,
            p.is_student, p.student_id,
            u.name, u.email, u.phone, u.gender, u.date_of_birth
     FROM patients p
     JOIN users u ON p.user_id = u.id
     WHERE p.user_id = $user_id
     LIMIT 1"
));

if (!$pat_row) {
    $pat_pk  = 0;
    $pat_row = ['name'=>$_SESSION['user_name']??'Patient','patient_id'=>'N/A','blood_group'=>null,'gender'=>'','is_student'=>0];
} else {
    $pat_pk = (int)$pat_row['pat_pk'];
}

$today = date('Y-m-d');

// ── Stats ─────────────────────────────────────────────────────────────────
$stats = [];

$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM appointments WHERE patient_id=$pat_pk");
$stats['total_appts'] = $r ? (mysqli_fetch_assoc($r)['t'] ?? 0) : 0;

$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM appointments WHERE patient_id=$pat_pk AND appointment_date>='$today' AND status NOT IN('Cancelled','No-Show')");
$stats['upcoming'] = $r ? (mysqli_fetch_assoc($r)['t'] ?? 0) : 0;

$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM prescriptions WHERE patient_id=$pat_pk AND status='Pending'");
$stats['pending_rx'] = $r ? (mysqli_fetch_assoc($r)['t'] ?? 0) : 0;

$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM lab_tests WHERE patient_id=$pat_pk AND status='Pending'");
$stats['pending_labs'] = $r ? (mysqli_fetch_assoc($r)['t'] ?? 0) : 0;

// ── Upcoming Appointments ─────────────────────────────────────────────────
$upcoming_appts = [];
$q = mysqli_query($conn,
    "SELECT a.id, a.appointment_id, a.appointment_date, a.appointment_time, a.service_type, a.status, a.symptoms,
            u.name AS doctor_name, d.specialization
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.id
     JOIN users u   ON d.user_id   = u.id
     WHERE a.patient_id = $pat_pk AND a.appointment_date >= '$today' AND a.status NOT IN('Cancelled','No-Show')
     ORDER BY a.appointment_date ASC, a.appointment_time ASC
     LIMIT 5"
);
if ($q) while ($row = mysqli_fetch_assoc($q)) $upcoming_appts[] = $row;

// ── Recent Prescriptions ─────────────────────────────────────────────────
$my_rx = [];
$q = mysqli_query($conn,
    "SELECT pr.prescription_id, pr.prescription_date, pr.medication_name, pr.dosage,
            pr.frequency, pr.duration, pr.status,
            u.name AS doctor_name
     FROM prescriptions pr
     JOIN doctors d ON pr.doctor_id = d.id
     JOIN users u   ON d.user_id    = u.id
     WHERE pr.patient_id = $pat_pk
     ORDER BY pr.prescription_date DESC
     LIMIT 5"
);
if ($q) while ($row = mysqli_fetch_assoc($q)) $my_rx[] = $row;

// ── Recent Lab Tests ─────────────────────────────────────────────────────
$my_labs = [];
$q = mysqli_query($conn,
    "SELECT lt.test_id, lt.test_name, lt.test_category, lt.test_date, lt.status, lt.cost
     FROM lab_tests lt
     WHERE lt.patient_id = $pat_pk
     ORDER BY lt.test_date DESC
     LIMIT 5"
);
if ($q) while ($row = mysqli_fetch_assoc($q)) $my_labs[] = $row;

// ── Next Appointment Countdown ──────────────────────────────────────────
$next_appt = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT appointment_date, appointment_time FROM appointments
     WHERE patient_id=$pat_pk AND appointment_date>='$today' AND status NOT IN('Cancelled','No-Show')
     ORDER BY appointment_date ASC, appointment_time ASC LIMIT 1"
));
$days_until = null;
if ($next_appt) {
    $dt_next = new DateTime($next_appt['appointment_date'], new DateTimeZone('Africa/Accra'));
    $dt_now  = new DateTime('today', new DateTimeZone('Africa/Accra'));
    $days_until = (int)$dt_now->diff($dt_next)->days;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Dashboard — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<style>
  :root { --role-accent:#8e44ad; --role-accent-light:#f5eef8; }
  [data-theme="dark"] { --role-accent-light:#2d1b3d; }

  .pat-header { background:linear-gradient(135deg,var(--primary),var(--role-accent)); color:#fff; border-radius:var(--radius-lg); padding:2rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; }
  .pat-avatar { width:72px; height:72px; border-radius:50%; background:#fff3; display:flex; align-items:center; justify-content:center; font-size:2rem; border:3px solid rgba(255,255,255,.4); flex-shrink:0; }
  .pat-info h2 { font-size:1.4rem; font-weight:700; margin:0 0 .25rem; }
  .pat-info p { margin:0; opacity:.85; font-size:.9rem; }
  .pat-badge { background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.35); border-radius:50px; padding:.3rem .9rem; font-size:.8rem; display:inline-flex; align-items:center; gap:.4rem; margin:.3rem .3rem 0 0; }

  .countdown-box { background:linear-gradient(135deg,#1abc9c,#27ae60); color:#fff; border-radius:var(--radius-lg); padding:1.5rem; text-align:center; margin-bottom:1.5rem; }
  .countdown-days { font-size:3.5rem; font-weight:800; line-height:1; }
  .countdown-label { font-size:.85rem; opacity:.85; margin-top:.25rem; }

  .appt-item { display:flex; align-items:flex-start; gap:1rem; padding:.85rem 0; border-bottom:1px solid var(--border); }
  .appt-item:last-child { border:none; }
  .appt-date-box { background:var(--primary); color:#fff; border-radius:var(--radius); padding:.4rem .8rem; text-align:center; min-width:52px; flex-shrink:0; }
  .appt-date-box .day { font-size:1.4rem; font-weight:800; line-height:1; }
  .appt-date-box .mon { font-size:.7rem; text-transform:uppercase; }
  .appt-details { flex:1; }
  .appt-doc { font-weight:600; font-size:.95rem; }
  .appt-meta { font-size:.78rem; color:var(--text-secondary); display:flex; gap:.75rem; flex-wrap:wrap; margin:.2rem 0; }

  .quick-action-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
  .quick-action-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:1.25rem; text-align:center; text-decoration:none; color:var(--text-primary); transition:all .2s; cursor:pointer; }
  .quick-action-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); border-color:var(--primary); }
  .quick-action-card .qa-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; margin:0 auto .75rem; font-size:1.3rem; }
  .quick-action-card .qa-label { font-size:.85rem; font-weight:600; }
</style>
</head>
<body>
<div class="adm-layout">
  <!-- SIDEBAR -->
  <aside class="adm-sidebar" id="admSidebar">
    <div class="adm-sidebar-brand">
      <div class="adm-brand-icon"><i class="fas fa-heart-pulse"></i></div>
      <div class="adm-brand-text"><span class="adm-brand-name">RMU Sickbay</span><span class="adm-brand-role">Patient Portal</span></div>
    </div>
    <nav class="adm-nav">
      <div class="adm-nav-label">My Health</div>
      <a href="patient_dashboard.php" class="adm-nav-item active"><i class="fas fa-home"></i><span>Dashboard</span></a>
      <a href="/RMU-Medical-Management-System/php/booking.php" class="adm-nav-item"><i class="fas fa-calendar-plus"></i><span>Book Appointment</span></a>
      <a href="/RMU-Medical-Management-System/php/my_appointments.php" class="adm-nav-item"><i class="fas fa-calendar-alt"></i><span>My Appointments</span></a>
      <a href="/RMU-Medical-Management-System/php/my_prescriptions.php" class="adm-nav-item"><i class="fas fa-pills"></i><span>My Prescriptions</span></a>
      <a href="/RMU-Medical-Management-System/php/my_lab_results.php" class="adm-nav-item"><i class="fas fa-flask"></i><span>Lab Results</span></a>
      <a href="/RMU-Medical-Management-System/php/my_medical_records.php" class="adm-nav-item"><i class="fas fa-file-medical"></i><span>Medical Records</span></a>
    </nav>
    <div class="adm-sidebar-footer">
      <a href="/RMU-Medical-Management-System/php/logout.php" class="adm-nav-item" style="color:#e74c3c;"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
  </aside>
  <div class="adm-overlay" id="admOverlay"></div>

  <!-- MAIN -->
  <main class="adm-main">
    <div class="adm-topbar">
      <div class="adm-topbar-left">
        <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <span class="adm-page-title"><i class="fas fa-user-circle" style="color:var(--role-accent);margin-right:.6rem;"></i>Patient Dashboard</span>
      </div>
      <div class="adm-topbar-right">
        <span style="font-size:.85rem;color:var(--text-secondary);"><?php echo date('D, d M Y'); ?></span>
        <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
        <div class="adm-avatar" style="background:var(--role-accent);"><i class="fas fa-user"></i></div>
      </div>
    </div>

    <div class="adm-content">

      <!-- Profile Header -->
      <div class="pat-header">
        <div class="pat-avatar"><i class="fas fa-user-circle"></i></div>
        <div class="pat-info">
          <h2><?php echo htmlspecialchars($pat_row['name']); ?></h2>
          <p><?php echo $pat_row['is_student'] ? 'Student Patient' : 'Staff / Community Patient'; ?> at RMU Medical Sickbay</p>
          <div>
            <span class="pat-badge"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($pat_row['patient_id']); ?></span>
            <?php if ($pat_row['blood_group']): ?><span class="pat-badge" style="background:rgba(231,76,60,.3);"><i class="fas fa-tint"></i> <?php echo htmlspecialchars($pat_row['blood_group']); ?></span><?php endif; ?>
            <?php if ($pat_row['gender']): ?><span class="pat-badge"><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars($pat_row['gender']); ?></span><?php endif; ?>
            <?php if (!empty($pat_row['phone'] ?? '')): ?><span class="pat-badge"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($pat_row['phone']); ?></span><?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Stats Strip -->
      <div class="adm-summary-strip">
        <div class="adm-mini-card">
          <div class="adm-mini-card-num"><?php echo $stats['total_appts']; ?></div>
          <div class="adm-mini-card-label">Total Appointments</div>
        </div>
        <div class="adm-mini-card">
          <div class="adm-mini-card-num blue"><?php echo $stats['upcoming']; ?></div>
          <div class="adm-mini-card-label">Upcoming</div>
        </div>
        <div class="adm-mini-card">
          <div class="adm-mini-card-num orange"><?php echo $stats['pending_rx']; ?></div>
          <div class="adm-mini-card-label">Pending Rx</div>
        </div>
        <div class="adm-mini-card">
          <div class="adm-mini-card-num orange"><?php echo $stats['pending_labs']; ?></div>
          <div class="adm-mini-card-label">Pending Labs</div>
        </div>
      </div>

      <!-- Alerts for allergies/chronic conditions -->
      <?php if (!empty($pat_row['allergies'] ?? '')): ?>
      <div class="adm-alert adm-alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <div><strong>Known Allergies:</strong> <?php echo htmlspecialchars($pat_row['allergies']); ?></div>
      </div>
      <?php endif; ?>
      <?php if (!empty($pat_row['chronic_conditions'] ?? '')): ?>
      <div class="adm-alert adm-alert-warning">
        <i class="fas fa-heartbeat"></i>
        <div><strong>Chronic Conditions:</strong> <?php echo htmlspecialchars($pat_row['chronic_conditions']); ?></div>
      </div>
      <?php endif; ?>

      <!-- Two Column Grid -->
      <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">

        <!-- Upcoming Appointments -->
        <div class="adm-card">
          <div class="adm-card-header">
            <h3><i class="fas fa-calendar-alt" style="color:var(--primary);"></i> Upcoming Appointments</h3>
            <a href="/RMU-Medical-Management-System/php/booking.php" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-plus"></i> Book</a>
          </div>
          <?php if (empty($upcoming_appts)): ?>
          <div style="text-align:center;padding:3rem;color:var(--text-muted);">
            <i class="fas fa-calendar-times" style="font-size:2.5rem;margin-bottom:1rem;opacity:.4;"></i>
            <p>No upcoming appointments.</p>
            <a href="/RMU-Medical-Management-System/php/booking.php" class="adm-btn adm-btn-primary" style="margin-top:.5rem;"><i class="fas fa-calendar-plus"></i> Book an Appointment</a>
          </div>
          <?php else: ?>
          <div style="padding:.5rem;">
            <?php foreach ($upcoming_appts as $apt):
              $apt_dt = new DateTime($apt['appointment_date']);
              $apt_status = $apt['status'];
              $sc = ($apt_status === 'Confirmed') ? 'success' : (($apt_status === 'Pending') ? 'warning' : 'info');
            ?>
            <div class="appt-item">
              <div class="appt-date-box">
                <div class="day"><?php echo $apt_dt->format('d'); ?></div>
                <div class="mon"><?php echo $apt_dt->format('M'); ?></div>
              </div>
              <div class="appt-details">
                <div class="appt-doc">Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?></div>
                <div class="appt-meta">
                  <span><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($apt['specialization']); ?></span>
                  <span><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></span>
                  <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($apt['service_type'] ?? 'Consultation'); ?></span>
                </div>
                <span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo $apt['status']; ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Recent Lab Tests -->
          <?php if (!empty($my_labs)): ?>
          <div class="adm-card-header" style="margin-top:1rem;"><h3><i class="fas fa-flask"></i> Recent Lab Tests</h3></div>
          <div class="adm-table-wrap">
            <table class="adm-table" style="font-size:.85rem;">
              <thead><tr><th>Test</th><th>Category</th><th>Date</th><th>Cost</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($my_labs as $lt):
                  $lt_stat = $lt['status'];
                  $lt_sc = ($lt_stat === 'Completed') ? 'success' : (($lt_stat === 'In Progress') ? 'info' : (($lt_stat === 'Cancelled') ? 'danger' : 'warning'));
                ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($lt['test_name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($lt['test_category'] ?? 'General'); ?></td>
                  <td><?php echo date('d M Y', strtotime($lt['test_date'])); ?></td>
                  <td>GH₵ <?php echo number_format($lt['cost'], 2); ?></td>
                  <td><span class="adm-badge adm-badge-<?php echo $lt_sc; ?>"><?php echo $lt['status']; ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>

        <!-- Right Panel -->
        <div style="display:flex;flex-direction:column;gap:1.5rem;">

          <!-- Next Appointment Countdown -->
          <?php if ($next_appt !== null && $days_until !== null): ?>
          <div class="countdown-box">
            <div style="font-size:.85rem;opacity:.85;margin-bottom:.5rem;"><i class="fas fa-clock"></i> Next Appointment In</div>
            <div class="countdown-days"><?php echo $days_until; ?></div>
            <div class="countdown-label"><?php echo $days_until === 1 ? 'Day' : 'Days'; ?> — <?php echo date('l, d M Y', strtotime($next_appt['appointment_date'])); ?> @ <?php echo date('g:i A', strtotime($next_appt['appointment_time'])); ?></div>
          </div>
          <?php endif; ?>

          <!-- Quick Actions -->
          <div class="adm-card">
            <div class="adm-card-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
            <div class="quick-action-grid" style="padding:.75rem;">
              <a href="/RMU-Medical-Management-System/php/booking.php" class="quick-action-card">
                <div class="qa-icon" style="background:#e8f4fd;color:#2980b9;"><i class="fas fa-calendar-plus"></i></div>
                <div class="qa-label">Book Appointment</div>
              </a>
              <a href="/RMU-Medical-Management-System/php/my_medical_records.php" class="quick-action-card">
                <div class="qa-icon" style="background:#fef9e7;color:#f39c12;"><i class="fas fa-file-medical"></i></div>
                <div class="qa-label">Medical Records</div>
              </a>
              <a href="/RMU-Medical-Management-System/php/my_prescriptions.php" class="quick-action-card">
                <div class="qa-icon" style="background:#e8f8f5;color:#1abc9c;"><i class="fas fa-pills"></i></div>
                <div class="qa-label">My Prescriptions</div>
              </a>
              <a href="/RMU-Medical-Management-System/php/my_lab_results.php" class="quick-action-card">
                <div class="qa-icon" style="background:#f5eef8;color:#8e44ad;"><i class="fas fa-flask"></i></div>
                <div class="qa-label">Lab Results</div>
              </a>
            </div>
          </div>

          <!-- Recent Prescriptions -->
          <div class="adm-card">
            <div class="adm-card-header"><h3><i class="fas fa-prescription-bottle"></i> My Prescriptions</h3></div>
            <div style="padding:.5rem;">
              <?php if (empty($my_rx)): ?>
              <p style="text-align:center;padding:1.5rem;color:var(--text-muted);font-size:.9rem;">No prescriptions yet.</p>
              <?php else: ?>
              <?php foreach ($my_rx as $rx):
                $rx_status = $rx['status'];
                $rx_sc = ($rx_status === 'Dispensed') ? 'success' : (($rx_status === 'Cancelled') ? 'danger' : 'warning');
              ?>
              <div style="display:flex;align-items:center;gap:.75rem;padding:.6rem 0;border-bottom:1px solid var(--border);">
                <div style="width:36px;height:36px;border-radius:50%;background:#fef9e7;display:flex;align-items:center;justify-content:center;color:#e67e22;flex-shrink:0;"><i class="fas fa-pills"></i></div>
                <div style="flex:1;">
                  <div style="font-weight:600;font-size:.88rem;"><?php echo htmlspecialchars($rx['medication_name']); ?></div>
                  <div style="font-size:.76rem;color:var(--text-secondary);">
                    <?php echo htmlspecialchars($rx['dosage']); ?>, <?php echo htmlspecialchars($rx['frequency']); ?> · Dr. <?php echo htmlspecialchars($rx['doctor_name']); ?>
                  </div>
                </div>
                <span class="adm-badge adm-badge-<?php echo $rx_sc; ?>" style="font-size:.7rem;"><?php echo $rx['status']; ?></span>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- Emergency Contact -->
          <?php if (!empty($pat_row['emergency_contact_name'] ?? '')): ?>
          <div class="adm-card">
            <div class="adm-card-header"><h3><i class="fas fa-phone-alt" style="color:#e74c3c;"></i> Emergency Contact</h3></div>
            <div style="padding:1rem;font-size:.9rem;line-height:2;">
              <div><i class="fas fa-user" style="margin-right:.5rem;color:var(--text-muted);"></i> <?php echo htmlspecialchars($pat_row['emergency_contact_name']); ?></div>
              <?php if ($pat_row['emergency_contact_phone']): ?>
              <div><i class="fas fa-phone" style="margin-right:.5rem;color:var(--text-muted);"></i>
                <a href="tel:<?php echo htmlspecialchars($pat_row['emergency_contact_phone']); ?>" style="color:var(--primary);"><?php echo htmlspecialchars($pat_row['emergency_contact_phone']); ?></a>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

        </div><!-- /right panel -->
      </div>
    </div>
  </main>
</div>

<script>
const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = document.getElementById('themeIcon');
const html        = document.documentElement;
function applyTheme(t) { html.setAttribute('data-theme',t); localStorage.setItem('rmu_theme',t); themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon'; }
applyTheme(localStorage.getItem('rmu_theme') || 'light');
themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
</script>
</body>
</html>