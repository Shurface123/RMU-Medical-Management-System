<?php
// ============================================================
// DOCTOR DASHBOARD — RMU Medical Sickbay
// ============================================================
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('doctor');

require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');
$user_id = (int)$_SESSION['user_id'];
$today   = date('Y-m-d');
$month_start = date('Y-m-01');

// ── Doctor Profile ────────────────────────────────────────
$doc_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT d.id AS doc_pk, d.doctor_id, d.specialization, d.experience_years,
            d.available_days, d.available_hours, d.is_available, d.bio, d.license_number,
            u.name, u.email, u.phone, u.gender, u.profile_image, u.date_of_birth, u.two_fa_enabled
     FROM doctors d JOIN users u ON d.user_id=u.id
     WHERE d.user_id=$user_id LIMIT 1"));
if (!$doc_row) {
    $doc_pk  = 0;
    $doc_row = ['name'=>$_SESSION['user_name']??'Doctor','specialization'=>'','doctor_id'=>'N/A',
                'is_available'=>0,'experience_years'=>0,'profile_image'=>'','email'=>'','phone'=>''];
} else { $doc_pk = (int)$doc_row['doc_pk']; }

// ── Stats Cards ───────────────────────────────────────────
function qval($conn,$sql){$r=mysqli_query($conn,$sql);return $r?(mysqli_fetch_row($r)[0]??0):0;}
$stats['today_appts']    = qval($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND appointment_date='$today'");
$stats['total_patients'] = qval($conn,"SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id=$doc_pk");
$stats['active_rx']      = qval($conn,"SELECT COUNT(*) FROM prescriptions WHERE doctor_id=$doc_pk AND status='Pending'");
$stats['active_rx']      = qval($conn,"SELECT COUNT(*) FROM prescriptions WHERE doctor_id=$doc_pk AND status='Pending'");
$stats['avail_beds']     = qval($conn,"SELECT COUNT(*) FROM beds WHERE status='Available'");
$stats['low_stock']      = qval($conn,"SELECT COUNT(*) FROM medicines WHERE stock_quantity<=reorder_level");
$stats['unread_notifs']  = qval($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND is_read=0");
$stats['pending_appts']  = qval($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND status='Pending'");

// ── Recent Activity ───────────────────────────────────────
$activity = [];
$q = mysqli_query($conn,
    "SELECT 'Appointment' AS type, a.status, u.name AS person, a.created_at AS ts,
            CONCAT('/RMU-Medical-Management-System/php/dashboards/doctor_dashboard.php?tab=appointments') AS link
     FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE a.doctor_id=$doc_pk
     UNION ALL
     SELECT 'Prescription', pr.status, u.name, pr.created_at, '#'
     FROM prescriptions pr JOIN patients p ON pr.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE pr.doctor_id=$doc_pk
     ORDER BY ts DESC LIMIT 8");
if ($q) while ($r=mysqli_fetch_assoc($q)) $activity[]=$r;

// ── Appointments List ─────────────────────────────────────
$appointments=[];
$q=mysqli_query($conn,
    "SELECT a.*, u.name AS patient_name, u.phone AS patient_phone, u.gender AS patient_gender,
            p.patient_id AS p_ref, p.blood_group, p.allergies
     FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE a.doctor_id=$doc_pk ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 100");
if ($q) while ($r=mysqli_fetch_assoc($q)) $appointments[]=$r;

// ── Prescriptions ─────────────────────────────────────────
$prescriptions=[];
$q=mysqli_query($conn,
    "SELECT pr.*, u.name AS patient_name, p.patient_id AS p_ref
     FROM prescriptions pr JOIN patients p ON pr.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE pr.doctor_id=$doc_pk ORDER BY pr.prescription_date DESC LIMIT 100");
if ($q) while ($r=mysqli_fetch_assoc($q)) $prescriptions[]=$r;

// Lab Requests (Deprecated)
$lab_requests=[];

// ── Patients Directory ────────────────────────────────────
$patients=[];
$q=mysqli_query($conn,
    "SELECT DISTINCT p.id, p.patient_id AS p_ref, p.blood_group, p.allergies,
            p.is_student, p.chronic_conditions, p.emergency_contact_name,
            u.name, u.email, u.phone, u.gender, u.date_of_birth
     FROM patients p JOIN users u ON p.user_id=u.id
     JOIN appointments a ON a.patient_id=p.id
     WHERE a.doctor_id=$doc_pk ORDER BY u.name ASC LIMIT 200");
if ($q) while ($r=mysqli_fetch_assoc($q)) $patients[]=$r;

// ── Medicine Inventory ────────────────────────────────────
$medicines=[];
$q=mysqli_query($conn,"SELECT * FROM medicine_inventory ORDER BY stock_status ASC, medicine_name ASC LIMIT 150");
if ($q) while ($r=mysqli_fetch_assoc($q)) $medicines[]=$r;

// ── Medical Records ───────────────────────────────────────
$med_records=[];
$q=mysqli_query($conn,
    "SELECT mr.*, u.name AS patient_name, p.patient_id AS p_ref
     FROM medical_records mr JOIN patients p ON mr.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE mr.doctor_id=$doc_pk ORDER BY mr.visit_date DESC LIMIT 100");
if ($q) while ($r=mysqli_fetch_assoc($q)) $med_records[]=$r;

// ── Bed Management ────────────────────────────────────────
$beds=[];
$q=mysqli_query($conn,"SELECT * FROM bed_management ORDER BY ward, bed_number");
if ($q) while ($r=mysqli_fetch_assoc($q)) $beds[]=$r;

// ── Staff Directory ───────────────────────────────────────
$staff=[];
$q=mysqli_query($conn,
    "SELECT u.id, u.name AS full_name, u.email, u.phone, 
            CASE 
                WHEN u.user_role='doctor' THEN 'Doctor'
                WHEN u.user_role='nurse' THEN 'Nurse'
                WHEN u.user_role='pharmacist' THEN 'Pharmacist'
                WHEN u.user_role='admin' THEN 'Admin'
                ELSE 'Staff'
            END AS role,
            s.department, s.staff_id, 'Active' AS status
     FROM users u 
     LEFT JOIN staff s ON u.id = s.user_id
     WHERE u.user_role IN ('doctor','nurse','pharmacist','admin','staff')
     ORDER BY role, full_name LIMIT 100");
if ($q) while ($r=mysqli_fetch_assoc($q)) $staff[]=$r;


// ── Notifications ─────────────────────────────────────────
$notifs=[];
$q=mysqli_query($conn,"SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 15");
if ($q) while ($r=mysqli_fetch_assoc($q)) $notifs[]=$r;

// ── Analytics Data (JSON for Chart.js) ────────────────────
$appt_week = []; // last 7 days appt counts
for ($i=6;$i>=0;$i--) {
    $d=date('Y-m-d',strtotime("-$i days"));
    $c=qval($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND appointment_date='$d'");
    $appt_week[]=['label'=>date('D',strtotime($d)),'count'=>(int)$c];
}
$appt_status=[];
foreach(['Pending','Confirmed','Completed','Cancelled','Rescheduled'] as $st) {
    $c=qval($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND status='$st'");
    $appt_status[]=['status'=>$st,'count'=>(int)$c];
}
$top_diagnoses=[];
$q=mysqli_query($conn,"SELECT diagnosis, COUNT(*) as cnt FROM medical_records WHERE doctor_id=$doc_pk AND visit_date>='$month_start' GROUP BY diagnosis ORDER BY cnt DESC LIMIT 5");
if ($q) while ($r=mysqli_fetch_assoc($q)) $top_diagnoses[]=$r;

$weekly_labels  = json_encode(array_column($appt_week,'label'));
$weekly_data    = json_encode(array_column($appt_week,'count'));
$status_labels  = json_encode(array_column($appt_status,'status'));
$status_data    = json_encode(array_column($appt_status,'count'));
$diag_labels    = json_encode(array_column($top_diagnoses,'diagnosis'));
$diag_data      = json_encode(array_column($top_diagnoses,'cnt'));

// ── Handle AJAX tab from URL ──────────────────────────────
$active_tab = htmlspecialchars($_GET['tab'] ?? 'overview');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Doctor Dashboard — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root{--role-accent:#1abc9c;--role-accent-dark:#16a085;--role-accent-light:#e8f8f5;}
[data-theme="dark"]{--role-accent-light:#0d3d30;}

/* ── Hero Banner ── */
.doc-hero{background:linear-gradient(135deg,#1C3A6B 0%,#2F80ED 55%,#1abc9c 100%);color:#fff;border-radius:var(--radius-lg);padding:2.2rem 2.8rem;margin-bottom:2rem;display:flex;align-items:center;gap:1.8rem;flex-wrap:wrap;position:relative;overflow:hidden;}
.doc-hero::after{content:'';position:absolute;right:-30px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06);}
.doc-avatar-hero{width:76px;height:76px;border-radius:50%;background:rgba(255,255,255,.18);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;font-size:2.2rem;border:3px solid rgba(255,255,255,.35);flex-shrink:0;}
.doc-hero-info h2{font-size:1.7rem;font-weight:700;margin:0 0 .3rem;}
.doc-hero-info p{margin:0;opacity:.85;font-size:.9rem;}
.hero-badge{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);border-radius:50px;padding:.25rem .85rem;font-size:.78rem;display:inline-flex;align-items:center;gap:.4rem;margin:.25rem .25rem 0 0;}

/* ── Mini Stat Strip ── */
.adm-summary-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:2rem;}
.adm-mini-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.4rem 1.2rem;text-align:center;box-shadow:var(--shadow-sm);transition:var(--transition);cursor:pointer;}
.adm-mini-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);}
.adm-mini-card-num{font-size:2.8rem;font-weight:800;color:var(--text-primary);line-height:1;}
.adm-mini-card-num.green{color:var(--success);}
.adm-mini-card-num.orange{color:var(--warning);}
.adm-mini-card-num.blue{color:var(--primary);}
.adm-mini-card-num.teal{color:var(--role-accent);}
.adm-mini-card-num.red{color:var(--danger);}
.adm-mini-card-label{font-size:.78rem;color:var(--text-secondary);margin-top:.4rem;font-weight:500;}

/* ── Tab Sections ── */
.dash-section{display:none;animation:fadeIn .3s ease;}
.dash-section.active{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* ── Section Header ── */
.sec-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.8rem;flex-wrap:wrap;gap:1rem;}
.sec-header h2{font-size:2rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.8rem;}
.sec-header h2 i{color:var(--role-accent);}

/* ── Filter Tabs ── */
.filter-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;}
.ftab{padding:.55rem 1.2rem;border-radius:20px;font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition);}
.ftab.active,.ftab:hover{background:var(--role-accent);color:#fff;border-color:var(--role-accent);}

/* ── Status Badges ── */
.adm-badge{display:inline-flex;align-items:center;padding:.3rem .8rem;border-radius:20px;font-size:1.1rem;font-weight:600;gap:.4rem;}
.adm-badge-success{background:var(--success-light);color:var(--success);}
.adm-badge-warning{background:var(--warning-light);color:var(--warning);}
.adm-badge-danger{background:var(--danger-light);color:var(--danger);}
.adm-badge-info{background:var(--info-light);color:var(--info);}
.adm-badge-primary{background:var(--primary-light);color:var(--primary);}
.adm-badge-teal{background:var(--role-accent-light);color:var(--role-accent);}

/* ── Table ── */
.adm-table-wrap{overflow-x:auto;border-radius:var(--radius-md);}
.adm-table{width:100%;border-collapse:collapse;font-size:1.3rem;}
.adm-table th{background:var(--surface-2);padding:1.2rem 1.4rem;text-align:left;font-weight:600;color:var(--text-secondary);font-size:1.1rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1.5px solid var(--border);}
.adm-table td{padding:1.2rem 1.4rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle;}
.adm-table tr:last-child td{border:none;}
.adm-table tr:hover td{background:var(--surface-2);}
.adm-table .action-btns{display:flex;gap:.5rem;flex-wrap:wrap;}

/* ── Cards Grid ── */
.cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem;}
.info-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.6rem;box-shadow:var(--shadow-sm);transition:var(--transition);}
.info-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);}
.info-card-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;}
.info-card-icon{width:44px;height:44px;border-radius:12px;background:var(--role-accent-light);color:var(--role-accent);display:flex;align-items:center;justify-content:center;font-size:1.6rem;}

/* ── Modal ── */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2000;align-items:center;justify-content:center;padding:1rem;}
.modal-bg.open{display:flex;}
.modal-box{background:var(--surface);border-radius:var(--radius-lg);padding:2.6rem;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg);animation:fadeIn .25s ease;}
.modal-box.wide{max-width:800px;}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;}
.modal-header h3{font-size:1.8rem;font-weight:700;}
.modal-close{background:none;border:none;font-size:2rem;cursor:pointer;color:var(--text-muted);transition:color .2s;}
.modal-close:hover{color:var(--danger);}

/* ── Form Controls ── */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;}
.form-group{margin-bottom:1.4rem;}
.form-group label{display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.04em;}
.form-control{width:100%;padding:1rem 1.2rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.3rem;transition:var(--transition);outline:none;}
.form-control:focus{border-color:var(--role-accent);box-shadow:0 0 0 3px rgba(26,188,156,.12);}
.form-control select,.form-select{appearance:none;}

/* ── Activity Feed ── */
.activity-item{display:flex;align-items:flex-start;gap:1rem;padding:.9rem 0;border-bottom:1px solid var(--border);}
.activity-item:last-child{border:none;}
.activity-dot{width:10px;height:10px;border-radius:50%;background:var(--role-accent);flex-shrink:0;margin-top:.5rem;}
.activity-dot.orange{background:var(--warning);}
.activity-dot.red{background:var(--danger);}
.activity-dot.blue{background:var(--primary);}

/* ── Chart Containers ── */
.chart-wrap{position:relative;height:260px;width:100%;}
.charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;}
@media(max-width:768px){.charts-grid{grid-template-columns:1fr;}.form-row{grid-template-columns:1fr;}}

/* ── Notification Bell ── */
.notif-badge-count{position:absolute;top:-4px;right:-4px;min-width:16px;height:16px;background:var(--danger);color:#fff;border-radius:50%;font-size:.9rem;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 3px;}

/* ── Availability Toggle ── */
.avail-pill{display:inline-flex;align-items:center;gap:.5rem;padding:.4rem 1rem;border-radius:50px;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);}
.avail-pill.on{background:rgba(26,188,156,.18);color:var(--role-accent);}
.avail-pill.off{background:rgba(231,76,60,.15);color:var(--danger);}

/* ── Responsive Sidebar Overrides ── */
@media(max-width:991px){
  .adm-menu-toggle{display:flex!important;}
  .adm-sidebar{transform:translateX(-100%);}
  .adm-sidebar.active{transform:translateX(0);}
  .adm-main{margin-left:0!important;}
  .adm-overlay.active{display:block;}
}
.adm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;}
</style>
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/notifications.css">
</head>
<body>
<div class="adm-layout">

<!-- ════════════════ SIDEBAR ════════════════ -->
<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-sidebar-brand">
    <div class="adm-brand-icon"><i class="fas fa-stethoscope"></i></div>
    <div class="adm-brand-text">
      <span class="adm-brand-name">RMU Sickbay</span>
      <span class="adm-brand-role">Doctor Portal</span>
    </div>
  </div>
  <nav class="adm-nav" style="padding:1.5rem 1rem;flex:1;">
    <div class="adm-nav-label">Main</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='overview')?'active':''?>" onclick="showTab('overview',this)"><i class="fas fa-house-medical"></i><span>Overview</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='appointments')?'active':''?>" onclick="showTab('appointments',this)">
      <i class="fas fa-calendar-check"></i><span>Appointments</span>
      <?php if($stats['pending_appts']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['pending_appts']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='records')?'active':''?>" onclick="showTab('records',this)"><i class="fas fa-file-medical"></i><span>Medical Records</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='prescriptions')?'active':''?>" onclick="showTab('prescriptions',this)"><i class="fas fa-prescription-bottle-medical"></i><span>Prescriptions</span></a>
    <div class="adm-nav-label" style="margin-top:1rem;">Clinical</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='lab_requests')?'active':''?>" onclick="showTab('lab_requests',this)"><i class="fas fa-flask"></i><span>Lab Requests</span></a>

    <a href="#" class="adm-nav-item <?=($active_tab==='patients')?'active':''?>" onclick="showTab('patients',this)"><i class="fas fa-users"></i><span>Patient Records</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='medicine')?'active':''?>" onclick="showTab('medicine',this)">
      <i class="fas fa-pills"></i><span>Medicine Inventory</span>
      <?php if($stats['low_stock']>0):?><span class="adm-badge adm-badge-danger" style="margin-left:auto;font-size:1rem;"><?=$stats['low_stock']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='beds')?'active':''?>" onclick="showTab('beds',this)"><i class="fas fa-bed"></i><span>Bed Management</span></a>
    <div class="adm-nav-label" style="margin-top:1rem;">Workspace</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='staff')?'active':''?>" onclick="showTab('staff',this)"><i class="fas fa-address-book"></i><span>Staff Directory</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='analytics')?'active':''?>" onclick="showTab('analytics',this)"><i class="fas fa-chart-bar"></i><span>Analytics</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='reports')?'active':''?>" onclick="showTab('reports',this)"><i class="fas fa-file-export"></i><span>Reports</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='profile')?'active':''?>" onclick="showTab('profile',this)"><i class="fas fa-user-doctor"></i><span>My Profile</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='settings')?'active':''?>" onclick="showTab('settings',this)"><i class="fas fa-gear"></i><span>Settings</span></a>
  </nav>
  <div class="adm-sidebar-footer">
    <a href="/RMU-Medical-Management-System/php/logout.php" class="adm-logout-btn"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a>
  </div>
</aside>
<div class="adm-overlay" id="admOverlay"></div>

<!-- ════════════════ MAIN ════════════════ -->
<main class="adm-main">

  <!-- TOPBAR -->
  <div class="adm-topbar">
    <div class="adm-topbar-left">
      <button class="adm-menu-toggle" id="menuToggle" style="display:none;"><i class="fas fa-bars"></i></button>
      <span class="adm-page-title" id="pageTitle"><i class="fas fa-house-medical" style="color:var(--role-accent);margin-right:.6rem;"></i><span id="pageTitleText">Overview</span></span>
    </div>
    <div class="adm-topbar-right">
      <span style="font-size:1.2rem;color:var(--text-secondary);"><?=date('D, d M Y')?></span>
      <!-- Notifications -->
      <?php
        $bell_has_unread = $stats['unread_notifs'] > 0;
        $bell_class      = $bell_has_unread ? 'adm-notif-btn has-unread' : 'adm-notif-btn';
        $bell_display    = $bell_has_unread ? 'flex' : 'none';
        $bell_label      = $stats['unread_notifs'] > 99 ? '99+' : $stats['unread_notifs'];
      ?>
      <div style="position:relative;">
        <button id="rmuBellBtn" class="<?=$bell_class?>" title="Notifications">
          <i class="fas fa-bell"></i>
          <span id="rmuBellCount" style="display:<?=$bell_display?>"><?=$bell_label?></span>
        </button>
      </div>
      <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
      <div class="adm-avatar" style="background:linear-gradient(135deg,var(--role-accent),#2F80ED);" title="<?=htmlspecialchars($doc_row['name'])?>">
        <?=strtoupper(substr($doc_row['name'],0,1))?>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="adm-content">

    <?php include __DIR__.'/doc_tabs/tab_overview.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_appointments.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_records.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_prescriptions.php'; ?>

    <?php include __DIR__.'/doc_tabs/tab_lab_requests.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_patients.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_medicine.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_beds.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_staff.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_analytics.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_reports.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_settings.php'; ?>
    <?php include __DIR__.'/doc_tabs/tab_profile.php'; ?>

  </div><!-- /adm-content -->
</main>
</div><!-- /adm-layout -->

<!-- ── GLOBAL TOAST & BROADCASTS ────────────────────────── -->
<div id="toastWrap" style="position:fixed;bottom:2rem;right:2rem;z-index:9999;display:flex;flex-direction:column;gap:.7rem;"></div>
<script src="/RMU-Medical-Management-System/js/notifications.js"></script>
<script src="/RMU-Medical-Management-System/php/includes/BroadcastReceiver.js"></script>

<script>
// Initialize Broadcast Receiver
document.addEventListener('DOMContentLoaded', () => {
    if (typeof BroadcastReceiver !== 'undefined') {
        window.rmuBroadcasts = new BroadcastReceiver(<?= $_SESSION['user_id'] ?>);
    }
});

// ── Tab Navigation ─────────────────────────────────────────
const TAB_TITLES={overview:'Overview',appointments:'Appointments',records:'Medical Records',
  prescriptions:'Prescriptions',lab_requests:'Lab Test Requests',patients:'Patient Records',
  medicine:'Medicine Inventory',beds:'Bed Management',staff:'Staff Directory',
  analytics:'Analytics',reports:'Reports',profile:'My Profile',settings:'Settings'};
const TAB_ICONS={overview:'fa-house-medical',appointments:'fa-calendar-check',records:'fa-file-medical',
  prescriptions:'fa-prescription-bottle-medical',lab_requests:'fa-flask',patients:'fa-users',
  medicine:'fa-pills',beds:'fa-bed',staff:'fa-address-book',
  analytics:'fa-chart-bar',reports:'fa-file-export',profile:'fa-user-doctor',settings:'fa-gear'};

function showTab(tab, el){
  document.querySelectorAll('.dash-section').forEach(s=>s.classList.remove('active'));
  const sec=document.getElementById('sec-'+tab);
  if(sec) sec.classList.add('active');
  document.querySelectorAll('.adm-nav-item').forEach(a=>a.classList.remove('active'));
  if(el) el.classList.add('active');
  document.getElementById('pageTitleText').textContent=TAB_TITLES[tab]||tab;
  const icon=document.querySelector('#pageTitle i');
  if(icon && TAB_ICONS[tab]) icon.className=`fas ${TAB_ICONS[tab]}`;
  // close sidebar on mobile
  document.getElementById('admSidebar').classList.remove('active');
  document.getElementById('admOverlay').classList.remove('active');
}

// ── Init active tab ────────────────────────────────────────
document.addEventListener('DOMContentLoaded',()=>{
  const initTab='<?=$active_tab?>';
  const initEl=document.querySelector(`.adm-nav-item[onclick*="${initTab}"]`);
  showTab(initTab,initEl);
  // Charts
  initCharts();
});

// ── Sidebar Toggle ─────────────────────────────────────────
document.getElementById('menuToggle')?.addEventListener('click',()=>{
  const sb=document.getElementById('admSidebar');
  const ov=document.getElementById('admOverlay');
  sb.classList.toggle('active'); ov.classList.toggle('active');
});
document.getElementById('admOverlay')?.addEventListener('click',()=>{
  document.getElementById('admSidebar').classList.remove('active');
  document.getElementById('admOverlay').classList.remove('active');
});

// ── Theme ──────────────────────────────────────────────────
function applyTheme(t){
  document.documentElement.setAttribute('data-theme',t);
  localStorage.setItem('rmu_theme',t);
  document.getElementById('themeIcon').className=t==='dark'?'fas fa-sun':'fas fa-moon';
}
applyTheme(localStorage.getItem('rmu_theme')||'light');
document.getElementById('themeToggle')?.addEventListener('click',()=>{
  applyTheme(document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark');
});

// ── Toast ──────────────────────────────────────────────────
function toast(msg,type='success'){
  const t=document.createElement('div');
  const colors={success:'#27AE60',danger:'#E74C3C',warning:'#F39C12',info:'#2F80ED'};
  t.style.cssText=`background:${colors[type]||colors.success};color:#fff;padding:1.2rem 2rem;border-radius:10px;font-family:Poppins,sans-serif;font-size:1.3rem;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.2);max-width:340px;animation:fadeIn .3s ease;`;
  t.textContent=msg;
  document.getElementById('toastWrap').appendChild(t);
  setTimeout(()=>t.remove(),4000);
}

// ── Filter Table ───────────────────────────────────────────
function filterTable(inputId,tableId){
  const val=document.getElementById(inputId).value.toUpperCase();
  document.querySelectorAll(`#${tableId} tbody tr`).forEach(row=>{
    row.style.display=row.textContent.toUpperCase().includes(val)?'':'none';
  });
}
function filterByStatus(status,tableId,col=4){
  document.querySelectorAll(`#${tableId} tbody tr`).forEach(row=>{
    const cell=row.cells[col];
    if(!cell){row.style.display='';return;}
    row.style.display=(status==='all'||cell.textContent.trim()===status)?'':'none';
  });
}

// ── Modal Helpers ──────────────────────────────────────────
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.addEventListener('click',e=>{if(e.target.classList.contains('modal-bg'))e.target.classList.remove('open');});

// ── AJAX Action Helper ─────────────────────────────────────
async function docAction(data){
  const res=await fetch('/RMU-Medical-Management-System/php/dashboards/doctor_actions.php',{
    method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)
  });
  return res.json();
}

// ── Charts ────────────────────────────────────────────────
const weeklyLabels = <?=$weekly_labels?>;
const weeklyData   = <?=$weekly_data?>;
const statusLabels = <?=$status_labels?>;
const statusData   = <?=$status_data?>;
const diagLabels   = <?=$diag_labels?>;
const diagData     = <?=$diag_data?>;

function initCharts(){
  const isDark=document.documentElement.getAttribute('data-theme')==='dark';
  const gridColor=isDark?'rgba(255,255,255,.08)':'rgba(0,0,0,.07)';
  const textColor=isDark?'#9AAECB':'#5A6A85';

  // Weekly Appointments Line Chart
  const wCtx=document.getElementById('chartWeekly');
  if(wCtx && weeklyData.length){
    new Chart(wCtx,{type:'bar',data:{labels:weeklyLabels,datasets:[{label:'Appointments',data:weeklyData,backgroundColor:'rgba(26,188,156,.25)',borderColor:'#1abc9c',borderWidth:2,borderRadius:8,fill:true}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1,color:textColor},grid:{color:gridColor}},x:{ticks:{color:textColor},grid:{display:false}}}}});
  }

  // Status Pie Chart
  const pCtx=document.getElementById('chartStatus');
  if(pCtx && statusData.length){
    new Chart(pCtx,{type:'doughnut',data:{labels:statusLabels,datasets:[{data:statusData,backgroundColor:['#F39C12','#27AE60','#2F80ED','#E74C3C','#1abc9c'],borderWidth:0,hoverOffset:6}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:textColor,padding:16,font:{size:12}}}}}});
  }

  // Top Diagnoses Bar
  const dCtx=document.getElementById('chartDiagnoses');
  if(dCtx && diagData.length){
    new Chart(dCtx,{type:'bar',data:{labels:diagLabels,datasets:[{label:'Cases',data:diagData,backgroundColor:'rgba(47,128,237,.7)',borderRadius:8}]},
    options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{color:textColor},grid:{color:gridColor}},y:{ticks:{color:textColor},grid:{display:false}}}}});
  }
}
</script>


</body>
</html>