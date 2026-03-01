<?php
// ============================================================
// MY APPOINTMENTS — Patient-facing appointments page
// Updated: Phase 4 — correct schema, admin-dashboard.css
// ============================================================
session_start();
require_once '../db_conn.php';
require_once '../classes/AppointmentManager.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role']??$_SESSION['role']??'', ['patient'])) {
    header('Location: ../index.php'); exit;
}
date_default_timezone_set('Africa/Accra');
$appointmentManager = new AppointmentManager($conn);
$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'patient';
$today    = date('Y-m-d');

// ── Patient ID (new schema: patients.id, not P_ID) ────────
$pidRow   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id, patient_id AS p_ref FROM patients WHERE user_id=$userId LIMIT 1"));
$patId    = (int)($pidRow['id'] ?? 0);

$message = ''; $error = '';

// ── POST actions ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $apptId = (int)$_POST['appointment_id'];
    $reason = trim($_POST['reason'] ?? '');

    if ($_POST['action'] === 'reschedule') {
        $newDate = $_POST['new_date'] ?? '';
        $newTime = $_POST['new_time'] ?? '';
        $result  = $appointmentManager->requestReschedule($apptId, $userId, $newDate, $newTime, $reason);
        // Notify the doctor
        $appt = mysqli_fetch_assoc(mysqli_query($conn,"SELECT a.doctor_id, u.name AS pat_name FROM appointments a JOIN users u ON u.id=$userId WHERE a.id=$apptId LIMIT 1"));
        if ($appt) {
            $docUid = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id FROM doctors WHERE id={$appt['doctor_id']} LIMIT 1"))['user_id'] ?? 0);
            if ($docUid) {
                $pname = mysqli_real_escape_string($conn,$appt['pat_name']??'Patient');
                mysqli_query($conn,"INSERT INTO notifications (user_id,user_role,type,title,message,is_read,related_module,created_at)
                  VALUES($docUid,'doctor','appointment','Reschedule Request','{$pname} has requested to reschedule appointment #$apptId.',0,'appointments',NOW())");
            }
        }
    } elseif ($_POST['action'] === 'cancel') {
        $result = $appointmentManager->cancelAppointment($apptId, $userId, $reason);
    }
    if (isset($result)) {
        $result['success'] ? $message = $result['message'] : $error = $result['message'];
    }
}

// ── AJAX: available slots ─────────────────────────────────
if (isset($_GET['get_slots'])) {
    $slots = $appointmentManager->getAvailableSlots((int)$_GET['doctor_id'], $_GET['date']);
    header('Content-Type: application/json'); echo json_encode($slots); exit;
}

// ── Fetch appointments ────────────────────────────────────
$aResult = mysqli_query($conn,
    "SELECT a.*, u.name AS doctor_name, d.specialization, d.doctor_id AS doc_ref
     FROM appointments a
     JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id
     WHERE a.patient_id=$patId ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$appointments = []; if($aResult) while($r=mysqli_fetch_assoc($aResult)) $appointments[]=$r;

// ── Unread notifications ──────────────────────────────────
$unread = (int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$userId AND is_read=0"))[0] ?? 0);

// ── Stats ─────────────────────────────────────────────────
$stat_today    = count(array_filter($appointments, fn($a)=>$a['appointment_date']===$today));
$stat_upcoming = count(array_filter($appointments, fn($a)=>$a['appointment_date']>$today && $a['status']==='Confirmed'));
$stat_pending  = count(array_filter($appointments, fn($a)=>$a['status']==='Pending'));
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Appointments — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<style>
:root{--role-accent:#9B59B6;}
[data-theme="dark"]{--role-accent-light:#2d1b40;}
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2000;align-items:center;justify-content:center;padding:1rem;}
.modal-bg.open{display:flex;}
.modal-box{background:var(--surface);border-radius:var(--radius-lg);padding:2.4rem;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg);}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;}
.modal-close{background:none;border:none;font-size:2rem;cursor:pointer;color:var(--text-muted);}
.form-group{margin-bottom:1.3rem;}
.form-group label{display:block;font-size:1.2rem;font-weight:600;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);}
.form-control{width:100%;padding:1rem 1.2rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text-primary);font-family:Poppins,sans-serif;font-size:1.3rem;outline:none;transition:var(--transition);}
.form-control:focus{border-color:var(--role-accent);}
.appt-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.8rem;margin-bottom:1.2rem;box-shadow:var(--shadow-sm);border-left:4px solid var(--border);transition:var(--transition);}
.appt-card:hover{box-shadow:var(--shadow-md);}
.appt-card.Confirmed,.appt-card.Completed{border-left-color:var(--success);}
.appt-card.Pending{border-left-color:var(--warning);}
.appt-card.Cancelled{border-left-color:var(--danger);opacity:.75;}
.appt-card.Rescheduled{border-left-color:var(--info);}
.appt-card.today-card{background:linear-gradient(135deg,rgba(155,89,182,.06),rgba(47,128,237,.04));border-left-color:var(--role-accent);}
.filter-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;}
.ftab{padding:.55rem 1.2rem;border-radius:20px;font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition);}
.ftab.active,.ftab:hover{background:var(--role-accent);color:#fff;border-color:var(--role-accent);}
</style>
</head>
<body>
<div class="adm-layout">

<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-sidebar-brand">
    <div class="adm-brand-icon"><i class="fas fa-hospital-user"></i></div>
    <div class="adm-brand-text"><span class="adm-brand-name">RMU Sickbay</span><span class="adm-brand-role">Patient Portal</span></div>
  </div>
  <nav class="adm-nav" style="padding:1.5rem 1rem;">
    <a href="patient_dashboard.php" class="adm-nav-item"><i class="fas fa-house"></i><span>Dashboard</span></a>
    <a href="my_appointments.php" class="adm-nav-item active"><i class="fas fa-calendar-check"></i><span>My Appointments</span></a>
    <a href="medical_records.php" class="adm-nav-item"><i class="fas fa-folder-open"></i><span>Medical Records</span></a>
    <a href="prescription_refills.php" class="adm-nav-item"><i class="fas fa-prescription-bottle-medical"></i><span>Prescription Refills</span></a>
  </nav>
  <div class="adm-sidebar-footer">
    <a href="/RMU-Medical-Management-System/php/logout.php" class="adm-logout-btn"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a>
  </div>
</aside>
<div class="adm-overlay" id="admOverlay"></div>

<main class="adm-main">
  <div class="adm-topbar">
    <div class="adm-topbar-left">
      <button class="adm-menu-toggle" id="menuToggle" style="display:none;"><i class="fas fa-bars"></i></button>
      <span class="adm-page-title"><i class="fas fa-calendar-check" style="color:var(--role-accent);margin-right:.6rem;"></i>My Appointments</span>
    </div>
    <div class="adm-topbar-right">
      <span style="font-size:1.2rem;color:var(--text-secondary);"><?=date('D, d M Y')?></span>
      <div style="position:relative;"><button class="adm-notif-btn"><i class="fas fa-bell"></i><?php if($unread>0):?><span style="position:absolute;top:-4px;right:-4px;min-width:16px;height:16px;background:var(--danger);color:#fff;border-radius:50%;font-size:.9rem;font-weight:700;display:flex;align-items:center;justify-content:center;"><?=$unread?></span><?php endif;?></button></div>
      <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
      <div class="adm-avatar" style="background:linear-gradient(135deg,#9B59B6,#2F80ED);"><?=strtoupper(substr($_SESSION['user_name']??$_SESSION['name']??'P',0,1))?></div>
    </div>
  </div>

  <div class="adm-content">
    <?php if($message):?><div style="background:var(--success-light);color:var(--success);border-left:4px solid var(--success);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($message)?></div><?php endif;?>
    <?php if($error):?><div style="background:var(--danger-light);color:var(--danger);border-left:4px solid var(--danger);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;"><i class="fas fa-exclamation-circle"></i> <?=htmlspecialchars($error)?></div><?php endif;?>

    <!-- Stats Strip -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;">
      <div class="adm-card" style="text-align:center;padding:1.5rem;margin:0;"><div style="font-size:2.8rem;font-weight:800;color:var(--primary);"><?=$stat_today?></div><div style="font-size:1.2rem;color:var(--text-muted);">Today</div></div>
      <div class="adm-card" style="text-align:center;padding:1.5rem;margin:0;"><div style="font-size:2.8rem;font-weight:800;color:var(--success);"><?=$stat_upcoming?></div><div style="font-size:1.2rem;color:var(--text-muted);">Upcoming</div></div>
      <div class="adm-card" style="text-align:center;padding:1.5rem;margin:0;"><div style="font-size:2.8rem;font-weight:800;color:var(--warning);"><?=$stat_pending?></div><div style="font-size:1.2rem;color:var(--text-muted);">Pending</div></div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button class="ftab active" onclick="filterAppts('all',this)">All</button>
      <button class="ftab" onclick="filterAppts('today',this)">Today</button>
      <button class="ftab" onclick="filterAppts('Pending',this)">Pending</button>
      <button class="ftab" onclick="filterAppts('Confirmed',this)">Confirmed</button>
      <button class="ftab" onclick="filterAppts('Rescheduled',this)">Rescheduled</button>
      <button class="ftab" onclick="filterAppts('Cancelled',this)">Cancelled</button>
      <button class="ftab" onclick="filterAppts('Completed',this)">Completed</button>
    </div>

    <!-- Appointment Cards -->
    <?php if(empty($appointments)):?>
      <div class="adm-card" style="text-align:center;padding:4rem;">
        <i class="fas fa-calendar-xmark" style="font-size:3rem;opacity:.25;display:block;margin-bottom:1rem;"></i>
        <p style="color:var(--text-muted);font-size:1.3rem;">No appointments found.</p>
        <a href="/RMU-Medical-Management-System/php/book.php" class="adm-btn adm-btn-primary" style="margin-top:1rem;">Book an Appointment</a>
      </div>
    <?php else: foreach($appointments as $ap):
      $bStatus = $ap['status'] ?? 'Pending';
      $is_today = ($ap['appointment_date']===$today);
      $can_act  = !in_array($bStatus, ['Completed','Cancelled']);
      $sc_map   = ['Confirmed'=>'success','Completed'=>'info','Cancelled'=>'danger','Rescheduled'=>'warning'];
      $sc       = $sc_map[$bStatus] ?? 'warning';
      $h        = date('g', strtotime($ap['appointment_time']));
      $m        = date('i', strtotime($ap['appointment_time']));
      $ampm     = date('A', strtotime($ap['appointment_time']));
    ?>
    <div class="appt-card <?=$bStatus?> <?=$is_today?'today-card':''?>" data-status="<?=$bStatus?>" data-date="<?=$ap['appointment_date']?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
        <div style="flex:1;">
          <div style="display:flex;align-items:center;gap:1rem;margin-bottom:.8rem;flex-wrap:wrap;">
            <div style="text-align:center;">
              <div style="font-size:1.8rem;font-weight:800;color:var(--role-accent);line-height:1;"><?=$h.':'.$m?></div>
              <div style="font-size:1rem;color:var(--text-muted);"><?=$ampm?></div>
            </div>
            <div style="border-left:2px solid var(--border);height:40px;"></div>
            <div>
              <div style="font-size:1rem;color:var(--text-muted);"><?=date('l, d F Y',strtotime($ap['appointment_date']))?><?=$is_today?' <span style="background:var(--role-accent);color:#fff;border-radius:20px;padding:.1rem .6rem;font-size:.8rem;margin-left:.5rem;">Today</span>':''?></div>
              <div style="font-weight:700;font-size:1.4rem;">Dr. <?=htmlspecialchars($ap['doctor_name']??'')?></div>
              <div style="font-size:1.2rem;color:var(--text-muted);"><?=htmlspecialchars($ap['specialization']??'General')?> &middot; <?=htmlspecialchars($ap['service_type']??'Consultation')?></div>
            </div>
          </div>
          <?php if($ap['symptoms']??''):?><div style="font-size:1.2rem;color:var(--text-secondary);background:var(--surface-2);border-radius:8px;padding:.7rem 1rem;margin-top:.5rem;"><strong>Reason:</strong> <?=htmlspecialchars(substr($ap['symptoms'],0,120))?></div><?php endif;?>
          <?php if($ap['reschedule_reason']??''):?><div style="font-size:1.1rem;color:var(--info);margin-top:.5rem;"><i class="fas fa-calendar-pen"></i> Reschedule note: <?=htmlspecialchars($ap['reschedule_reason'])?></div><?php endif;?>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.6rem;">
          <span class="adm-badge adm-badge-<?=$sc?>" style="font-size:1.2rem;"><?=$bStatus?></span>
          <?php if($can_act):?>
          <button onclick="openReschedule(<?=$ap['id']?>,<?=json_encode('Dr. '.$ap['doctor_name'])?>,<?=$ap['doctor_id']?>)" class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-calendar-pen"></i> Reschedule</button>
          <button onclick="openCancel(<?=$ap['id']?>,<?=json_encode('Dr. '.$ap['doctor_name'])?>)" class="adm-btn adm-btn-danger adm-btn-sm"><i class="fas fa-xmark"></i> Cancel</button>
          <?php endif;?>
        </div>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>
</main>
</div>

<!-- Reschedule Modal -->
<div class="modal-bg" id="modalReschedule">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-calendar-pen" style="color:var(--warning);"></i> Request Reschedule</h3><button class="modal-close" onclick="this.closest('.modal-bg').classList.remove('open')">&times;</button></div>
    <p id="rsDoctorName" style="font-weight:600;font-size:1.4rem;margin-bottom:1.5rem;"></p>
    <form method="POST">
      <input type="hidden" name="action" value="reschedule">
      <input type="hidden" name="appointment_id" id="rsApptId">
      <div class="form-group"><label>Preferred New Date</label><input type="date" name="new_date" class="form-control" min="<?=date('Y-m-d')?>" required></div>
      <div class="form-group"><label>Preferred New Time</label><input type="time" name="new_time" class="form-control" required></div>
      <div class="form-group"><label>Reason</label><textarea name="reason" class="form-control" rows="3" placeholder="Why do you need to reschedule?" required></textarea></div>
      <button type="submit" class="adm-btn adm-btn-warning" style="width:100%;justify-content:center;"><i class="fas fa-paper-plane"></i> Send Reschedule Request</button>
    </form>
  </div>
</div>

<!-- Cancel Modal -->
<div class="modal-bg" id="modalCancel">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-xmark" style="color:var(--danger);"></i> Cancel Appointment</h3><button class="modal-close" onclick="this.closest('.modal-bg').classList.remove('open')">&times;</button></div>
    <p id="cancelDoctorName" style="font-weight:600;font-size:1.4rem;margin-bottom:.8rem;"></p>
    <div style="background:var(--danger-light);color:var(--danger);border-radius:10px;padding:1rem 1.4rem;margin-bottom:1.2rem;font-size:1.2rem;"><i class="fas fa-triangle-exclamation"></i> Your doctor will be notified of this cancellation.</div>
    <form method="POST">
      <input type="hidden" name="action" value="cancel">
      <input type="hidden" name="appointment_id" id="cancelApptId">
      <div class="form-group"><label>Cancellation Reason</label><textarea name="reason" class="form-control" rows="3" placeholder="Please tell us why you're cancelling…" required></textarea></div>
      <button type="submit" class="adm-btn adm-btn-danger" style="width:100%;justify-content:center;"><i class="fas fa-xmark"></i> Confirm Cancellation</button>
    </form>
  </div>
</div>

<script>
function applyTheme(t){document.documentElement.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);document.getElementById('themeIcon').className=t==='dark'?'fas fa-sun':'fas fa-moon';}
applyTheme(localStorage.getItem('rmu_theme')||'light');
document.getElementById('themeToggle')?.addEventListener('click',()=>{applyTheme(document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark');});
document.getElementById('menuToggle')?.addEventListener('click',()=>{document.getElementById('admSidebar').classList.toggle('active');document.getElementById('admOverlay').classList.toggle('active');});
document.getElementById('admOverlay')?.addEventListener('click',()=>{document.getElementById('admSidebar').classList.remove('active');document.getElementById('admOverlay').classList.remove('active');});
const today='<?=$today?>';
function filterAppts(status,btn){
  document.querySelectorAll('.filter-tabs .ftab').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  document.querySelectorAll('.appt-card').forEach(c=>{
    if(status==='all'){c.style.display='';return;}
    if(status==='today'){c.style.display=c.dataset.date===today?'':'none';return;}
    c.style.display=c.dataset.status===status?'':'none';
  });
}
function openReschedule(id,doc){document.getElementById('rsApptId').value=id;document.getElementById('rsDoctorName').textContent=doc;document.getElementById('modalReschedule').classList.add('open');}
function openCancel(id,doc){document.getElementById('cancelApptId').value=id;document.getElementById('cancelDoctorName').textContent=doc;document.getElementById('modalCancel').classList.add('open');}
document.querySelectorAll('.modal-bg').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');}));
</script>
</body>
</html>
