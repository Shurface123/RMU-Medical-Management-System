<?php
// ============================================================
// DOCTOR DASHBOARD  — RMU Medical Sickbay
// ============================================================
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: /RMU-Medical-Management-System/php/login.php');
    exit;
}

require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');
$user_id = (int)$_SESSION['user_id'];

// ── Doctor Record ─────────────────────────────────────────────────────────
$doc_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT d.id AS doc_pk, d.doctor_id, d.specialization, d.experience_years,
            d.available_days, d.is_available,
            u.name, u.email, u.phone, u.gender, u.profile_image
     FROM doctors d
     JOIN users u ON d.user_id = u.id
     WHERE d.user_id = $user_id
     LIMIT 1"
));

if (!$doc_row) {
    // No doctor profile yet
    $doc_pk = 0;
    $doc_row = ['name'=>$_SESSION['user_name']??'Doctor','specialization'=>'','doctor_id'=>'N/A','is_available'=>0,'experience_years'=>0,'profile_image'=>'default-avatar.png'];
} else {
    $doc_pk = (int)$doc_row['doc_pk'];
}

$today = date('Y-m-d');

// ── Stats ─────────────────────────────────────────────────────────────────
$stats = [];

// Total unique patients ever seen
$r = mysqli_query($conn, "SELECT COUNT(DISTINCT patient_id) as t FROM appointments WHERE doctor_id=$doc_pk");
$stats['patients'] = $r ? (mysqli_fetch_assoc($r)['t'] ?? 0) : 0;

// Today's appointments
$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM appointments WHERE doctor_id=$doc_pk AND appointment_date='$today'");
$stats['today'] = $r ? (mysqli_fetch_assoc($r)['t'] ?? 0) : 0;

// Pending (status = Pending or Confirmed)
$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM appointments WHERE doctor_id=$doc_pk AND status IN('Pending','Confirmed') AND appointment_date>='$today'");
$stats['pending'] = $r ? (mysqli_fetch_assoc($r)['t'] ?? 0) : 0;

// Completed this month
$month_start = date('Y-m-01');
$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM appointments WHERE doctor_id=$doc_pk AND status='Completed' AND appointment_date>='$month_start'");
$stats['completed'] = $r ? (mysqli_fetch_assoc($r)['t'] ?? 0) : 0;

// Pending prescriptions — written by this doctor, not yet dispensed
$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM prescriptions WHERE doctor_id=$doc_pk AND status='Pending'");
$stats['prescriptions'] = $r ? (mysqli_fetch_assoc($r)['t'] ?? 0) : 0;

// ── Today's Appointments ─────────────────────────────────────────────────
$today_appts = [];
$q = mysqli_query($conn,
    "SELECT a.id, a.appointment_id, a.appointment_time, a.service_type, a.status, a.symptoms,
            u.name AS patient_name, u.gender AS patient_gender, u.phone AS patient_phone,
            p.patient_id AS patient_pid, p.blood_group, p.allergies
     FROM appointments a
     JOIN patients p ON a.patient_id = p.id
     JOIN users u    ON p.user_id    = u.id
     WHERE a.doctor_id = $doc_pk AND a.appointment_date = '$today'
     ORDER BY a.appointment_time ASC"
);
if ($q) while ($row = mysqli_fetch_assoc($q)) $today_appts[] = $row;

// ── Recent Prescriptions ─────────────────────────────────────────────────
$recent_rx = [];
$q = mysqli_query($conn,
    "SELECT pr.prescription_id, pr.prescription_date, pr.medication_name, pr.status, pr.dosage,
            u.name AS patient_name
     FROM prescriptions pr
     JOIN patients p ON pr.patient_id = p.id
     JOIN users u    ON p.user_id = u.id
     WHERE pr.doctor_id = $doc_pk
     ORDER BY pr.prescription_date DESC
     LIMIT 5"
);
if ($q) while ($row = mysqli_fetch_assoc($q)) $recent_rx[] = $row;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Dashboard — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<style>
  /* Doctor-specific accent */
  :root { --role-accent:#1abc9c; --role-accent-light:#e8f8f5; }
  [data-theme="dark"] { --role-accent-light:#0d4034; }

  .doc-header { background:linear-gradient(135deg,var(--primary),var(--role-accent)); color:#fff; border-radius:var(--radius-lg); padding:2rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; }
  .doc-avatar { width:72px; height:72px; border-radius:50%; background:#fff3; display:flex; align-items:center; justify-content:center; font-size:2rem; border:3px solid rgba(255,255,255,.4); flex-shrink:0; }
  .doc-info h2 { font-size:1.4rem; font-weight:700; margin:0 0 .25rem; }
  .doc-info p { margin:0; opacity:.85; font-size:.9rem; }
  .doc-badge { background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.35); border-radius:50px; padding:.3rem .9rem; font-size:.8rem; display:inline-flex; align-items:center; gap:.4rem; margin:.3rem .3rem 0 0; }

  .appt-card { border:1px solid var(--border); border-radius:var(--radius); padding:1rem 1.25rem; margin-bottom:.75rem; background:var(--bg-card); display:flex; align-items:flex-start; gap:1rem; transition:transform .2s; }
  .appt-card:hover { transform:translateX(4px); border-color:var(--primary); }
  .appt-time { text-align:center; min-width:60px; }
  .appt-time .time { font-size:1.1rem; font-weight:700; color:var(--primary); }
  .appt-time .period { font-size:.75rem; color:var(--text-muted); }
  .appt-body { flex:1; }
  .appt-name { font-weight:600; font-size:1rem; margin-bottom:.2rem; }
  .appt-meta { font-size:.8rem; color:var(--text-secondary); display:flex; flex-wrap:wrap; gap:.75rem; margin:.3rem 0; }
  .appt-actions { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.5rem; }

  .rx-item { display:flex; align-items:center; gap:1rem; padding:.75rem 0; border-bottom:1px solid var(--border); }
  .rx-item:last-child { border:none; }
  .rx-icon { width:38px; height:38px; border-radius:50%; background:var(--role-accent-light); display:flex; align-items:center; justify-content:center; color:var(--role-accent); flex-shrink:0; }
  .rx-body { flex:1; }
  .rx-name { font-weight:600; font-size:.9rem; }
  .rx-meta { font-size:.78rem; color:var(--text-secondary); }
</style>
</head>
<body>

<div class="adm-layout">
  <!-- LEFT SIDEBAR -->
  <aside class="adm-sidebar" id="admSidebar">
    <div class="adm-sidebar-brand">
      <div class="adm-brand-icon"><i class="fas fa-stethoscope"></i></div>
      <div class="adm-brand-text"><span class="adm-brand-name">RMU Sickbay</span><span class="adm-brand-role">Doctor Portal</span></div>
    </div>
    <nav class="adm-nav">
      <div class="adm-nav-label">Navigation</div>
      <a href="doctor_dashboard.php" class="adm-nav-item active"><i class="fas fa-home"></i><span>Dashboard</span></a>
      <a href="/RMU-Medical-Management-System/php/booking.php" class="adm-nav-item"><i class="fas fa-calendar-plus"></i><span>Appointments</span></a>
      <a href="/RMU-Medical-Management-System/php/medical_records.php" class="adm-nav-item"><i class="fas fa-file-medical"></i><span>Medical Records</span></a>
      <a href="/RMU-Medical-Management-System/php/prescriptions.php" class="adm-nav-item"><i class="fas fa-prescription"></i><span>Prescriptions</span></a>
      <a href="/RMU-Medical-Management-System/php/lab_results.php" class="adm-nav-item"><i class="fas fa-flask"></i><span>Lab Results</span></a>
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
        <span class="adm-page-title"><i class="fas fa-user-md" style="color:var(--role-accent);margin-right:.6rem;"></i>Doctor Dashboard</span>
      </div>
      <div class="adm-topbar-right">
        <span style="font-size:.85rem;color:var(--text-secondary);"><?php echo date('D, d M Y'); ?></span>
        <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
        <div class="adm-avatar"><i class="fas fa-user-md"></i></div>
      </div>
    </div>

    <div class="adm-content">

      <!-- Profile Header -->
      <div class="doc-header">
        <div class="doc-avatar"><i class="fas fa-user-md"></i></div>
        <div class="doc-info">
          <h2>Dr. <?php echo htmlspecialchars($doc_row['name']); ?></h2>
          <p><?php echo htmlspecialchars($doc_row['specialization'] ?: 'General Practitioner'); ?></p>
          <div>
            <span class="doc-badge"><i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($doc_row['doctor_id']); ?></span>
            <?php if ($doc_row['experience_years']): ?><span class="doc-badge"><i class="fas fa-star"></i> <?php echo $doc_row['experience_years']; ?> yrs exp</span><?php endif; ?>
            <span class="doc-badge <?php echo $doc_row['is_available']?'':''; ?>" style="background:<?php echo $doc_row['is_available']?'rgba(39,174,96,.3)':'rgba(231,76,60,.3)'; ?>;">
              <i class="fas fa-circle" style="font-size:.5rem;"></i> <?php echo $doc_row['is_available'] ? 'Available' : 'Unavailable'; ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Stats Strip -->
      <div class="adm-summary-strip">
        <div class="adm-mini-card">
          <div class="adm-mini-card-num"><?php echo $stats['patients']; ?></div>
          <div class="adm-mini-card-label">Total Patients</div>
        </div>
        <div class="adm-mini-card">
          <div class="adm-mini-card-num green"><?php echo $stats['today']; ?></div>
          <div class="adm-mini-card-label">Today's Appointments</div>
        </div>
        <div class="adm-mini-card">
          <div class="adm-mini-card-num orange"><?php echo $stats['pending']; ?></div>
          <div class="adm-mini-card-label">Upcoming</div>
        </div>
        <div class="adm-mini-card">
          <div class="adm-mini-card-num blue"><?php echo $stats['completed']; ?></div>
          <div class="adm-mini-card-label">Completed (Month)</div>
        </div>
        <div class="adm-mini-card">
          <div class="adm-mini-card-num <?php echo $stats['prescriptions']>0?'orange':'green'; ?>"><?php echo $stats['prescriptions']; ?></div>
          <div class="adm-mini-card-label">Pending Rx</div>
        </div>
      </div>

      <!-- Main Grid -->
      <div style="display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start;">

        <!-- Today's Schedule -->
        <div class="adm-card">
          <div class="adm-card-header">
            <h3><i class="fas fa-calendar-day" style="color:var(--primary);"></i> Today's Schedule
              <span class="adm-badge adm-badge-primary" style="margin-left:.5rem;"><?php echo count($today_appts); ?></span>
            </h3>
            <a href="/RMU-Medical-Management-System/php/booking.php" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-plus"></i> New</a>
          </div>
          <?php if (empty($today_appts)): ?>
          <div style="text-align:center;padding:3rem;color:var(--text-muted);">
            <i class="fas fa-calendar-check" style="font-size:2.5rem;margin-bottom:1rem;"></i>
            <p>No appointments scheduled for today.</p>
          </div>
          <?php else: ?>
          <div style="padding:.5rem;">
            <?php foreach ($today_appts as $apt):
              $apt_status = $apt['status'];
              $sc = ($apt_status === 'Confirmed') ? 'success' : (($apt_status === 'Completed') ? 'info' : (($apt_status === 'Cancelled') ? 'danger' : 'warning'));
              [$h,$m] = explode(':', $apt['appointment_time']);
              $h_12 = $h > 12 ? $h-12 : ($h==0?12:(int)$h);
              $ampm = $h >= 12 ? 'PM' : 'AM';
            ?>
            <div class="appt-card">
              <div class="appt-time">
                <div class="time"><?php echo $h_12.':'.$m; ?></div>
                <div class="period"><?php echo $ampm; ?></div>
              </div>
              <div class="appt-body">
                <div class="appt-name"><?php echo htmlspecialchars($apt['patient_name']); ?></div>
                <div class="appt-meta">
                  <span><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($apt['patient_pid']); ?></span>
                  <span><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars($apt['patient_gender']); ?></span>
                  <?php if ($apt['blood_group']): ?><span><i class="fas fa-tint"></i> <?php echo htmlspecialchars($apt['blood_group']); ?></span><?php endif; ?>
                  <span><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($apt['service_type'] ?? 'Consultation'); ?></span>
                </div>
                <?php if ($apt['symptoms']): ?>
                <div style="font-size:.8rem;color:var(--text-secondary);font-style:italic;margin:.25rem 0;">
                  <i class="fas fa-notes-medical"></i> <?php echo htmlspecialchars(substr($apt['symptoms'],0,80)).'…'; ?>
                </div>
                <?php endif; ?>
                <div class="appt-actions">
                  <span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo $apt['status']; ?></span>
                  <?php if ($apt['patient_phone']): ?>
                  <a href="tel:<?php echo htmlspecialchars($apt['patient_phone']); ?>" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-phone"></i></a>
                  <?php endif; ?>
                  <a href="/RMU-Medical-Management-System/php/medical_records.php?patient_id=<?php echo $apt['patient_pid']; ?>" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-file-medical"></i> Records</a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Right Panel -->
        <div style="display:flex;flex-direction:column;gap:1.5rem;">

          <!-- Quick Actions -->
          <div class="adm-card">
            <div class="adm-card-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding:.5rem;">
              <a href="/RMU-Medical-Management-System/php/prescriptions.php" class="adm-btn adm-btn-primary" style="padding:.75rem;display:flex;flex-direction:column;align-items:center;gap:.4rem;text-align:center;height:auto;">
                <i class="fas fa-prescription" style="font-size:1.2rem;"></i><span style="font-size:.8rem;">Write Prescription</span>
              </a>
              <a href="/RMU-Medical-Management-System/php/lab_results.php" class="adm-btn adm-btn-warning" style="padding:.75rem;display:flex;flex-direction:column;align-items:center;gap:.4rem;text-align:center;height:auto;">
                <i class="fas fa-flask" style="font-size:1.2rem;"></i><span style="font-size:.8rem;">Order Lab Test</span>
              </a>
              <a href="/RMU-Medical-Management-System/php/medical_records.php" class="adm-btn adm-btn-success" style="padding:.75rem;display:flex;flex-direction:column;align-items:center;gap:.4rem;text-align:center;height:auto;">
                <i class="fas fa-file-medical" style="font-size:1.2rem;"></i><span style="font-size:.8rem;">Add Record</span>
              </a>
              <a href="/RMU-Medical-Management-System/php/booking.php" class="adm-btn adm-btn-danger" style="padding:.75rem;display:flex;flex-direction:column;align-items:center;gap:.4rem;text-align:center;height:auto;">
                <i class="fas fa-calendar-plus" style="font-size:1.2rem;"></i><span style="font-size:.8rem;">Schedule Follow-up</span>
              </a>
            </div>
          </div>

          <!-- Recent Prescriptions -->
          <div class="adm-card">
            <div class="adm-card-header"><h3><i class="fas fa-pills"></i> Recent Prescriptions</h3></div>
            <div style="padding:.5rem;">
              <?php if (empty($recent_rx)): ?>
              <p style="text-align:center;padding:2rem;color:var(--text-muted);font-size:.9rem;">No prescriptions yet.</p>
              <?php else: ?>
              <?php foreach ($recent_rx as $rx):
                $rx_status = $rx['status'];
                $rx_sc = ($rx_status === 'Dispensed') ? 'success' : (($rx_status === 'Cancelled') ? 'danger' : 'warning');
              ?>
              <div class="rx-item">
                <div class="rx-icon"><i class="fas fa-pills"></i></div>
                <div class="rx-body">
                  <div class="rx-name"><?php echo htmlspecialchars($rx['medication_name']); ?></div>
                  <div class="rx-meta">
                    <?php echo htmlspecialchars($rx['patient_name']); ?> · <?php echo htmlspecialchars($rx['dosage']); ?> ·
                    <?php echo date('d M', strtotime($rx['prescription_date'])); ?>
                  </div>
                </div>
                <span class="adm-badge adm-badge-<?php echo $rx_sc; ?>"><?php echo $rx['status']; ?></span>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

        </div><!-- /right panel -->
      </div><!-- /grid -->

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