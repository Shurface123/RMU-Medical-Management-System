<?php
// ============================================================
// NURSE DASHBOARD — RMU Medical Sickbay
// Mirrors admin/doctor/patient/pharmacy dashboard architecture
// ============================================================
require_once 'nurse_security.php';

$user_id    = (int)$_SESSION['user_id'];
$nurseName  = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Nurse';
$today      = date('Y-m-d');
$month_start= date('Y-m-01');
$active_tab = $_GET['tab'] ?? 'overview';

// ── Nurse Profile ─────────────────────────────────────────
$nurse_row = dbRow($conn,
    "SELECT n.*, u.name, u.email, u.phone, u.profile_image, u.date_of_birth, u.created_at AS member_since, u.last_login
     FROM nurses n JOIN users u ON n.user_id=u.id
     WHERE n.user_id=? LIMIT 1", "i", [$user_id]);
if (!$nurse_row) {
    $nurse_pk  = 0;
    $nurse_row = ['full_name'=>$nurseName,'license_number'=>'N/A','profile_photo'=>'',
                  'years_of_experience'=>0,'specialization'=>'','shift_type'=>'Morning',
                  'ward_assigned'=>'','department'=>'Nursing','designation'=>'Staff Nurse',
                  'availability_status'=>'Online','status'=>'Active','profile_completion'=>0,
                  'date_of_birth'=>'','gender'=>'','nationality'=>'','marital_status'=>'',
                  'national_id'=>'','secondary_phone'=>'','personal_email'=>'',
                  'street_address'=>'','city'=>'','region'=>'','country'=>'Ghana','postal_code'=>'',
                  'office_location'=>'','license_issuing_body'=>'','license_expiry'=>'',
                  'nursing_school'=>'','graduation_year'=>'','postgrad_training'=>'','bio'=>'',
                  'profile_image'=>'','member_since'=>'','last_login'=>''];
} else { $nurse_pk = (int)$nurse_row['id']; }

// ── Stats Cards ───────────────────────────────────────────
$stats = [
    'patients_today'   => (int)dbVal($conn,"SELECT COUNT(DISTINCT ba.patient_id) FROM bed_assignments ba WHERE ba.status='Active'"),
    'pending_meds'     => (int)dbVal($conn,"SELECT COUNT(*) FROM medication_administration WHERE nurse_id=? AND status='Pending' AND DATE(scheduled_time)=?","is",[$nurse_pk,$today]),
    'vitals_due'       => (int)dbVal($conn,"SELECT COUNT(DISTINCT ba.patient_id) FROM bed_assignments ba LEFT JOIN patient_vitals pv ON ba.patient_id=pv.patient_id AND pv.recorded_at > DATE_SUB(NOW(),INTERVAL 4 HOUR) WHERE ba.status='Active' AND pv.id IS NULL"),
    'pending_tasks'    => (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=? AND status IN('Pending','In Progress')","i",[$nurse_pk]),
    'overdue_tasks'    => (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=? AND status='Overdue'","i",[$nurse_pk]),
    'active_emergencies'=>(int)dbVal($conn,"SELECT COUNT(*) FROM emergency_alerts WHERE status='Active'"),
    'handover_pending' => (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_shifts WHERE nurse_id=? AND shift_date=? AND handover_submitted=0 AND status='Completed'","is",[$nurse_pk,$today]),
    'unread_notifs'    => (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_notifications WHERE nurse_id=? AND is_read=0","i",[$nurse_pk]),
    'unread_msgs'      => (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_doctor_messages WHERE receiver_id=? AND is_read=0","i",[$user_id]),
    'meds_given_today' => (int)dbVal($conn,"SELECT COUNT(*) FROM medication_administration WHERE nurse_id=? AND status='Administered' AND DATE(administered_at)=?","is",[$nurse_pk,$today]),
    'vitals_today'     => (int)dbVal($conn,"SELECT COUNT(*) FROM patient_vitals WHERE nurse_id=? AND DATE(recorded_at)=?","is",[$nurse_pk,$today]),
    'tasks_done_today' => (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=? AND status='Completed' AND DATE(completed_at)=?","is",[$nurse_pk,$today]),
    'notes_today'      => (int)dbVal($conn,"SELECT COUNT(*) FROM nursing_notes WHERE nurse_id=? AND DATE(created_at)=?","is",[$nurse_pk,$today]),
    'iv_active'        => (int)dbVal($conn,"SELECT COUNT(*) FROM iv_fluid_records WHERE nurse_id=? AND status='Running'","i",[$nurse_pk]),
];

// ── Update overdue tasks ──────────────────────────────────
dbExecute($conn,"UPDATE nurse_tasks SET status='Overdue' WHERE nurse_id=? AND status IN('Pending','In Progress') AND due_time < NOW()","i",[$nurse_pk]);

// ── Flagged Vitals (critical patients) ────────────────────
$flagged_vitals = dbSelect($conn,
    "SELECT pv.*, u.name AS patient_name, ba.bed_id
     FROM patient_vitals pv
     JOIN patients p ON pv.patient_id=p.id JOIN users u ON p.user_id=u.id
     LEFT JOIN bed_assignments ba ON ba.patient_id=p.id AND ba.status='Active'
     WHERE pv.is_flagged=1 AND DATE(pv.recorded_at)=?
     ORDER BY pv.recorded_at DESC LIMIT 20", "s", [$today]);

// ── Recent Activity ───────────────────────────────────────
$activity = [];
$q1 = dbSelect($conn,"SELECT 'Vitals' AS type, CONCAT('Vitals recorded for ',u.name) AS description, pv.recorded_at AS ts
  FROM patient_vitals pv JOIN patients p ON pv.patient_id=p.id JOIN users u ON p.user_id=u.id
  WHERE pv.nurse_id=? ORDER BY pv.recorded_at DESC LIMIT 5","i",[$nurse_pk]);
$q2 = dbSelect($conn,"SELECT 'Task' AS type, CONCAT('Task completed: ',task_title) AS description, completed_at AS ts
  FROM nurse_tasks WHERE nurse_id=? AND status='Completed' ORDER BY completed_at DESC LIMIT 5","i",[$nurse_pk]);
$q3 = dbSelect($conn,"SELECT 'Medication' AS type, CONCAT('Administered: ',medicine_name) AS description, administered_at AS ts
  FROM medication_administration WHERE nurse_id=? AND status='Administered' ORDER BY administered_at DESC LIMIT 5","i",[$nurse_pk]);
$q4 = dbSelect($conn,"SELECT 'Note' AS type, CONCAT(note_type,' note added') AS description, created_at AS ts
  FROM nursing_notes WHERE nurse_id=? ORDER BY created_at DESC LIMIT 5","i",[$nurse_pk]);
$activity = array_merge($q1,$q2,$q3,$q4);
usort($activity, fn($a,$b) => strtotime($b['ts']??'0') - strtotime($a['ts']??'0'));
$activity = array_slice($activity, 0, 15);

// ── Current Shift ─────────────────────────────────────────
$current_shift = dbRow($conn,
    "SELECT * FROM nurse_shifts WHERE nurse_id=? AND shift_date=? ORDER BY start_time ASC LIMIT 1",
    "is", [$nurse_pk, $today]);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Nurse Dashboard — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ── Nurse Role Accent: Warm Pink/Coral ── */
:root{--role-accent:#E91E63;--role-accent-dark:#C2185B;--role-accent-light:#FCE4EC;}
[data-theme="dark"]{--role-accent-light:#3d1020;}

/* ── Hero Banner ── */
.nurse-hero{display:flex;align-items:center;gap:2rem;padding:2.2rem 2.5rem;background:linear-gradient(135deg,var(--role-accent),#AD1457 50%,#880E4F);border-radius:var(--radius-lg);color:#fff;margin-bottom:2rem;flex-wrap:wrap;box-shadow:var(--shadow-md);}
.nurse-avatar-hero{width:72px;height:72px;border-radius:50%;overflow:hidden;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;color:#fff;flex-shrink:0;border:3px solid rgba(255,255,255,.35);}
.nurse-avatar-hero img{width:100%;height:100%;object-fit:cover;}
.nurse-hero-info h2{font-size:2rem;font-weight:700;margin:0;}
.nurse-hero-info p{font-size:1.3rem;margin:.3rem 0 0;opacity:.85;}
.hero-badge{display:inline-flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);padding:.3rem .9rem;border-radius:20px;font-size:1.1rem;font-weight:500;backdrop-filter:blur(5px);}

/* ── Mini Stat Strip ── */
.adm-summary-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:1rem;margin-bottom:2rem;}
.adm-mini-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.4rem 1.2rem;text-align:center;box-shadow:var(--shadow-sm);transition:var(--transition);cursor:pointer;}
.adm-mini-card:hover{box-shadow:var(--shadow-md);transform:translateY(-3px);}
.adm-mini-card-num{font-size:2.4rem;font-weight:800;line-height:1;}
.adm-mini-card-label{font-size:1.1rem;font-weight:500;color:var(--text-secondary);margin-top:.5rem;}
.adm-mini-card-num.green{color:var(--success);}
.adm-mini-card-num.orange{color:var(--warning);}
.adm-mini-card-num.blue{color:var(--primary);}
.adm-mini-card-num.teal{color:var(--role-accent);}
.adm-mini-card-num.red{color:var(--danger);}

/* ── Emergency Button (always visible in topbar) ── */
.emergency-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 1.3rem;border-radius:var(--radius-sm);background:var(--danger);color:#fff;font-size:1.2rem;font-weight:700;border:none;cursor:pointer;transition:var(--transition);animation:pulse-emergency 2s infinite;font-family:'Poppins',sans-serif;}
.emergency-btn:hover{background:#C0392B;transform:scale(1.05);}
@keyframes pulse-emergency{0%,100%{box-shadow:0 0 0 0 rgba(231,76,60,.7);}70%{box-shadow:0 0 0 8px rgba(231,76,60,0);}}

/* ── Tab Sections ── */
.dash-section{display:none;animation:fadeIn .3s ease;}.dash-section.active{display:block;}
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
.form-control{width:100%;padding:1rem 1.2rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.3rem;transition:var(--transition);outline:none;box-sizing:border-box;}
.form-control:focus{border-color:var(--role-accent);box-shadow:0 0 0 3px rgba(233,30,99,.12);}

/* ── Activity Feed ── */
.activity-item{display:flex;align-items:flex-start;gap:1rem;padding:.9rem 0;border-bottom:1px solid var(--border);}
.activity-item:last-child{border:none;}
.activity-dot{width:10px;height:10px;border-radius:50%;background:var(--role-accent);flex-shrink:0;margin-top:.5rem;}
.activity-dot.orange{background:var(--warning);}
.activity-dot.red{background:var(--danger);}
.activity-dot.blue{background:var(--primary);}
.activity-dot.green{background:var(--success);}

/* ── Chart Containers ── */
.chart-wrap{position:relative;height:260px;width:100%;}
.charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;}
@media(max-width:768px){.charts-grid{grid-template-columns:1fr;}.form-row{grid-template-columns:1fr;}}

/* ── Quick Actions ── */
.quick-actions{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2rem;}
.quick-action-btn{display:inline-flex;align-items:center;gap:.6rem;padding:.8rem 1.5rem;border-radius:var(--radius-sm);font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text-primary);transition:var(--transition);font-family:'Poppins',sans-serif;}
.quick-action-btn:hover{background:var(--role-accent);color:#fff;border-color:var(--role-accent);transform:translateY(-2px);box-shadow:var(--shadow-md);}
.quick-action-btn i{font-size:1.3rem;}

/* ── Alert Cards ── */
.alert-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.5rem;display:flex;align-items:flex-start;gap:1.2rem;transition:var(--transition);margin-bottom:1rem;}
.alert-card:hover{box-shadow:var(--shadow-md);}
.alert-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;}
.alert-icon.red{background:var(--danger-light);color:var(--danger);}
.alert-icon.orange{background:var(--warning-light);color:var(--warning);}
.alert-icon.blue{background:var(--info-light);color:var(--info);}
.alert-icon.pink{background:var(--role-accent-light);color:var(--role-accent);}

/* ── Notification Bell ── */
.adm-notif-btn{position:relative;background:var(--surface-2);border:1px solid var(--border);width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:var(--text-secondary);cursor:pointer;transition:var(--transition);}
.adm-notif-btn:hover{background:var(--role-accent-light);color:var(--role-accent);border-color:var(--role-accent);}
.adm-notif-btn span{position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;font-size:.85rem;font-weight:700;min-width:18px;height:18px;border-radius:10px;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--surface);}

/* ── Responsive Sidebar ── */
@media(max-width:991px){
  .adm-menu-toggle{display:flex!important;}
  .adm-sidebar{transform:translateX(-100%);}
  .adm-sidebar.active{transform:translateX(0);}
  .adm-main{margin-left:0!important;}
  .adm-overlay.active{display:block;}
}
.adm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;}

/* ── Btn helpers ── */
.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 1.4rem;border-radius:var(--radius-sm);font-weight:600;font-size:1.2rem;border:1.5px solid transparent;cursor:pointer;transition:var(--transition);font-family:'Poppins',sans-serif;}
.btn-primary{background:var(--role-accent);color:#fff;border-color:var(--role-accent);}.btn-primary:hover{background:var(--role-accent-dark);}
.btn-success{background:var(--success);color:#fff;border-color:var(--success);}
.btn-danger{background:var(--danger);color:#fff;border-color:var(--danger);}
.btn-warning{background:var(--warning);color:#fff;border-color:var(--warning);}
.btn-outline{background:transparent;color:var(--text-primary);border-color:var(--border);}.btn-outline:hover{border-color:var(--role-accent);color:var(--role-accent);}
.btn-sm{padding:.5rem 1rem;font-size:1.1rem;}
.btn-xs{padding:.3rem .7rem;font-size:1rem;}

/* ── Badge helpers ── */
.badge{display:inline-flex;align-items:center;padding:.25rem .7rem;border-radius:20px;font-size:1rem;font-weight:600;}
.badge-success{background:var(--success-light);color:var(--success);}
.badge-warning{background:var(--warning-light);color:var(--warning);}
.badge-danger{background:var(--danger-light);color:var(--danger);}
.badge-info{background:var(--info-light);color:var(--info);}
.badge-primary{background:var(--primary-light);color:var(--primary);}
.badge-secondary{background:var(--surface-2);color:var(--text-secondary);}

/* ── Data Table ── */
.data-table{width:100%;border-collapse:collapse;font-size:1.25rem;}
.data-table th{background:var(--surface-2);padding:1rem 1.2rem;text-align:left;font-weight:600;color:var(--text-secondary);font-size:1.05rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1.5px solid var(--border);}
.data-table td{padding:1rem 1.2rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle;}
.data-table tr:last-child td{border:none;}
.data-table tr:hover td{background:var(--surface-2);}

.text-center{text-align:center;}.text-muted{color:var(--text-muted);}
.table-responsive{overflow-x:auto;border-radius:var(--radius-md);}
</style>
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/notifications.css">
</head>
<body>
<div class="adm-layout">

<!-- ════════════════ SIDEBAR ════════════════ -->
<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-sidebar-brand">
    <div class="adm-brand-icon"><i class="fas fa-user-nurse"></i></div>
    <div class="adm-brand-text">
      <span class="adm-brand-name">RMU Sickbay</span>
      <span class="adm-brand-role">Nurse Portal</span>
    </div>
  </div>
  <nav class="adm-nav" style="padding:1.5rem 1rem;flex:1;">
    <div class="adm-nav-label">Main</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='overview')?'active':''?>" onclick="showTab('overview',this)"><i class="fas fa-house-medical"></i><span>Overview</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='vitals')?'active':''?>" onclick="showTab('vitals',this)">
      <i class="fas fa-heartbeat"></i><span>Patient Vitals</span>
      <?php if($stats['vitals_due']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['vitals_due']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='medications')?'active':''?>" onclick="showTab('medications',this)">
      <i class="fas fa-pills"></i><span>Medications</span>
      <?php if($stats['pending_meds']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['pending_meds']?></span><?php endif;?>
    </a>
    <div class="adm-nav-label" style="margin-top:1rem;">Patient Care</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='patients')?'active':''?>" onclick="showTab('patients',this)"><i class="fas fa-bed-pulse"></i><span>Beds & Wards</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='notes')?'active':''?>" onclick="showTab('notes',this)"><i class="fas fa-notes-medical"></i><span>Nursing Notes</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='fluids')?'active':''?>" onclick="showTab('fluids',this)">
      <i class="fas fa-droplet"></i><span>IV & Fluids</span>
      <?php if($stats['iv_active']>0):?><span class="adm-badge adm-badge-info" style="margin-left:auto;font-size:1rem;"><?=$stats['iv_active']?></span><?php endif;?>
    </a>
    <div class="adm-nav-label" style="margin-top:1rem;">Workflow</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='tasks')?'active':''?>" onclick="showTab('tasks',this)">
      <i class="fas fa-clipboard-list"></i><span>Tasks & Shifts</span>
      <?php if($stats['pending_tasks']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['pending_tasks']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='emergency')?'active':''?>" onclick="showTab('emergency',this)">
      <i class="fas fa-truck-medical"></i><span>Emergency</span>
      <?php if($stats['active_emergencies']>0):?><span class="adm-badge adm-badge-danger" style="margin-left:auto;font-size:1rem;"><?=$stats['active_emergencies']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='messages')?'active':''?>" onclick="showTab('messages',this)">
      <i class="fas fa-comment-medical"></i><span>Messages</span>
      <?php if($stats['unread_msgs']>0):?><span class="adm-badge adm-badge-primary" style="margin-left:auto;font-size:1rem;"><?=$stats['unread_msgs']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='education')?'active':''?>" onclick="showTab('education',this)"><i class="fas fa-book-medical"></i><span>Education & D/C</span></a>
    <div class="adm-nav-label" style="margin-top:1rem;">Insights</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='analytics')?'active':''?>" onclick="showTab('analytics',this)"><i class="fas fa-chart-bar"></i><span>Analytics</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='reports')?'active':''?>" onclick="showTab('reports',this)"><i class="fas fa-file-export"></i><span>Reports</span></a>
    <div class="adm-nav-label" style="margin-top:1rem;">Account</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='notifications')?'active':''?>" onclick="showTab('notifications',this)">
      <i class="fas fa-bell"></i><span>Notifications</span>
      <?php if($stats['unread_notifs']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['unread_notifs']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='profile')?'active':''?>" onclick="showTab('profile',this)"><i class="fas fa-user-circle"></i><span>My Profile</span></a>
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
      <button class="emergency-btn" onclick="openModal('emergencyModal')" title="Trigger Emergency Alert"><i class="fas fa-triangle-exclamation"></i> EMERGENCY</button>
      <span style="font-size:1.2rem;color:var(--text-secondary);"><?=date('D, d M Y')?></span>
      <?php
        $bell_has = $stats['unread_notifs'] > 0;
        $bell_cls = $bell_has ? 'adm-notif-btn has-unread' : 'adm-notif-btn';
        $bell_dsp = $bell_has ? 'flex' : 'none';
        $bell_lbl = $stats['unread_notifs'] > 99 ? '99+' : $stats['unread_notifs'];
      ?>
      <div style="position:relative;">
        <button id="rmuBellBtn" class="<?=$bell_cls?>" title="Notifications" onclick="showTab('notifications',null)">
          <i class="fas fa-bell"></i>
          <span id="rmuBellCount" style="display:<?=$bell_dsp?>"><?=$bell_lbl?></span>
        </button>
      </div>
      <button id="themeToggle" class="adm-notif-btn" title="Toggle Theme"><i id="themeIcon" class="fas fa-moon"></i></button>
      <div style="display:flex;align-items:center;gap:.8rem;cursor:pointer;" onclick="showTab('profile',null)">
        <?php $img=$nurse_row['profile_photo']??$nurse_row['profile_image']??'';
          if($img && $img!=='default-avatar.png'):?>
          <img src="/RMU-Medical-Management-System/<?=e($img)?>" alt="Avatar" style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--role-accent);">
        <?php else:?>
          <div style="width:38px;height:38px;border-radius:50%;background:var(--role-accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.4rem;"><?=strtoupper(substr($nurse_row['full_name']??$nurseName,0,1))?></div>
        <?php endif;?>
        <span style="font-size:1.3rem;font-weight:600;color:var(--text-primary);"><?=e(explode(' ',$nurse_row['full_name']??$nurseName)[0])?></span>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="adm-content" style="padding:2.5rem 3rem;">

    <?php include __DIR__.'/nurse_tabs/tab_overview.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_vitals.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_medications.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_beds.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_notes.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_tasks.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_emergency.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_fluids.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_education.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_messages.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_analytics.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_reports.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_notifications.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_profile.php'; ?>
    <?php include __DIR__.'/nurse_tabs/tab_settings.php'; ?>

  </div><!-- /adm-content -->
</main>
</div><!-- /adm-layout -->

<!-- ════════════════ EMERGENCY MODAL ════════════════ -->
<div class="modal-bg" id="emergencyModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-triangle-exclamation" style="color:var(--danger);"></i> Trigger Emergency Alert</h3><button class="modal-close" onclick="closeModal('emergencyModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Emergency Type *</label>
      <select id="em_type" class="form-control">
        <option value="">Select Type</option>
        <option value="Code Blue">🔵 Code Blue — Cardiac/Respiratory Arrest</option>
        <option value="Rapid Response">🟠 Rapid Response — Deteriorating Patient</option>
        <option value="Fall">🟡 Patient Fall</option>
        <option value="Fire">🔴 Fire Emergency</option>
        <option value="General Emergency">⚪ General Emergency</option>
        <option value="Security">🟣 Security Alert</option>
      </select>
    </div>
    <div class="form-group"><label>Patient (optional)</label><input id="em_patient" class="form-control" placeholder="Search patient name or ID"></div>
    <div class="form-group"><label>Location / Ward / Bed *</label><input id="em_location" class="form-control" value="<?=e($nurse_row['ward_assigned']??'')?>" placeholder="e.g. Ward A, Bed 5"></div>
    <div class="form-group"><label>Severity *</label>
      <select id="em_severity" class="form-control">
        <option value="Critical">Critical</option>
        <option value="High" selected>High</option>
        <option value="Medium">Medium</option>
      </select>
    </div>
    <div class="form-group"><label>Message</label><textarea id="em_message" class="form-control" rows="3" placeholder="Describe the emergency..."></textarea></div>
    <button class="btn btn-danger" onclick="triggerEmergency()" style="width:100%;font-size:1.4rem;padding:1rem;"><i class="fas fa-triangle-exclamation"></i> ACTIVATE EMERGENCY ALERT</button>
  </div>
</div>

<!-- ════════════════ GLOBAL TOAST ════════════════ -->
<div id="toastWrap" style="position:fixed;bottom:2rem;right:2rem;z-index:9999;display:flex;flex-direction:column;gap:.7rem;"></div>
<script src="/RMU-Medical-Management-System/js/notifications.js"></script>

<script>
// ── CSRF Token ─────────────────────────────────────────────
const CSRF_TOKEN = '<?=$csrf_token?>';

// ── AJAX Helper ────────────────────────────────────────────
async function nurseAction(data) {
    if (typeof data === 'object' && !(data instanceof FormData)) {
        data._csrf = CSRF_TOKEN;
    }
    try {
        const opts = {method:'POST'};
        if (data instanceof FormData) {
            data.append('_csrf', CSRF_TOKEN);
            opts.body = data;
        } else {
            opts.headers = {'Content-Type':'application/json'};
            opts.body = JSON.stringify(data);
        }
        const r = await fetch('/RMU-Medical-Management-System/php/dashboards/nurse_actions.php', opts);
        const j = await r.json();
        if (j.message === 'Invalid security token. Please reload the page.') { location.reload(); }
        return j;
    } catch(e) { return {success:false, message:'Network error'}; }
}

// ── Tab Navigation ─────────────────────────────────────────
const TAB_TITLES={overview:'Overview',vitals:'Patient Vitals',medications:'Medications',
  patients:'Beds & Wards',notes:'Nursing Notes',tasks:'Tasks & Shifts',
  emergency:'Emergency',fluids:'IV & Fluids',education:'Education & D/C',
  messages:'Messages',analytics:'Analytics',reports:'Reports',
  notifications:'Notifications',profile:'My Profile',settings:'Settings'};
const TAB_ICONS={overview:'fa-house-medical',vitals:'fa-heartbeat',medications:'fa-pills',
  patients:'fa-bed-pulse',notes:'fa-notes-medical',tasks:'fa-clipboard-list',
  emergency:'fa-truck-medical',fluids:'fa-droplet',education:'fa-book-medical',
  messages:'fa-comment-medical',analytics:'fa-chart-bar',reports:'fa-file-export',
  notifications:'fa-bell',profile:'fa-user-circle',settings:'fa-gear'};

function showTab(tab, el){
  document.querySelectorAll('.dash-section').forEach(s=>s.classList.remove('active'));
  const sec=document.getElementById('sec-'+tab);
  if(sec) sec.classList.add('active');
  document.querySelectorAll('.adm-nav-item').forEach(a=>a.classList.remove('active'));
  if(el) el.classList.add('active');
  document.getElementById('pageTitleText').textContent=TAB_TITLES[tab]||tab;
  const icon=document.querySelector('#pageTitle i');
  if(icon && TAB_ICONS[tab]) icon.className=`fas ${TAB_ICONS[tab]}`;
  document.getElementById('admSidebar').classList.remove('active');
  document.getElementById('admOverlay').classList.remove('active');
}

// ── Init ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded',()=>{
  const initTab='<?=$active_tab?>';
  const initEl=document.querySelector(`.adm-nav-item[onclick*="${initTab}"]`);
  showTab(initTab,initEl);
});

// ── Sidebar Toggle ─────────────────────────────────────────
document.getElementById('menuToggle')?.addEventListener('click',()=>{
  document.getElementById('admSidebar').classList.toggle('active');
  document.getElementById('admOverlay').classList.toggle('active');
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
function showToast(msg,type='success'){
  const t=document.createElement('div');
  const colors={success:'#27AE60',error:'#E74C3C',warning:'#F39C12',info:'#2980B9'};
  t.style.cssText=`padding:1.2rem 2rem;border-radius:12px;color:#fff;font-size:1.3rem;font-weight:500;
    box-shadow:0 8px 32px rgba(0,0,0,.18);display:flex;align-items:center;gap:.8rem;min-width:280px;
    animation:fadeIn .3s ease;font-family:'Poppins',sans-serif;background:${colors[type]||colors.success}`;
  const icons={success:'fa-check-circle',error:'fa-times-circle',warning:'fa-exclamation-triangle',info:'fa-info-circle'};
  t.innerHTML=`<i class="fas ${icons[type]||icons.success}"></i>${msg}`;
  document.getElementById('toastWrap').appendChild(t);
  setTimeout(()=>{t.style.opacity='0';t.style.transform='translateX(20px)';setTimeout(()=>t.remove(),300);},4000);
}

// ── Modal ──────────────────────────────────────────────────
function openModal(id){ document.getElementById(id)?.classList.add('open'); }
function closeModal(id){ document.getElementById(id)?.classList.remove('open'); }

// ── Validation Helpers ─────────────────────────────────────
function validateForm(fields){
  for(const[id,label] of Object.entries(fields)){
    const el=document.getElementById(id);
    if(!el||!el.value.trim()){showToast(`${label} is required`,'error');el?.focus();return false;}
  }
  return true;
}
function validateNumber(val,min,label){
  if(isNaN(val)||Number(val)<min){showToast(`${label} must be at least ${min}`,'error');return false;}
  return true;
}
function confirmAction(msg){return confirm(msg);}

// ── Emergency Trigger ──────────────────────────────────────
async function triggerEmergency(){
  if(!validateForm({em_type:'Emergency type',em_location:'Location'})) return;
  if(!confirmAction('⚠️ This will alert ALL doctors and administrators. Continue?')) return;
  const r = await nurseAction({
    action:'trigger_emergency',
    alert_type: document.getElementById('em_type').value,
    severity: document.getElementById('em_severity').value,
    location: document.getElementById('em_location').value,
    message: document.getElementById('em_message').value,
    patient_search: document.getElementById('em_patient').value
  });
  showToast(r.message||'Alert sent', r.success?'success':'error');
  if(r.success){ closeModal('emergencyModal'); setTimeout(()=>location.reload(),1500); }
}
</script>
</body>
</html>
