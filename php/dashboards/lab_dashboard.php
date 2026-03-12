<?php
// ============================================================
// LAB TECHNICIAN DASHBOARD — RMU Medical Sickbay
// Exact mirror of admin/doctor/patient/pharmacy/nurse architecture
// ============================================================
require_once 'lab_security.php';
initSecureSession();
setSecurityHeaders();
$user_id = enforceLabTechRole();
require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');
$csrf_token = generateCsrfToken();

$user_id    = (int)$_SESSION['user_id'];
$techName   = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Lab Technician';
$today      = date('Y-m-d');
$yesterday  = date('Y-m-d', strtotime('-1 day'));
$month_start= date('Y-m-01');

// Helper
function qv($c,$s){ $r=mysqli_query($c,$s); return $r?(mysqli_fetch_row($r)[0]??0):0; }

// ── Lab Technician Profile ────────────────────────────────
$tech_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT lt.*, u.name, u.email AS user_email, u.phone AS user_phone, u.profile_image, u.date_of_birth AS user_dob
     FROM lab_technicians lt JOIN users u ON lt.user_id=u.id
     WHERE lt.user_id=$user_id LIMIT 1"));
if (!$tech_row) {
    mysqli_query($conn,"INSERT IGNORE INTO lab_technicians (user_id,full_name,member_since) VALUES ($user_id,'".mysqli_real_escape_string($conn,$techName)."','$today')");
    $tech_row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT lt.*, u.name, u.email AS user_email, u.phone AS user_phone, u.profile_image, u.date_of_birth AS user_dob
         FROM lab_technicians lt JOIN users u ON lt.user_id=u.id
         WHERE lt.user_id=$user_id LIMIT 1"));
}
$tech_pk = (int)($tech_row['id'] ?? 0);
mysqli_query($conn,"INSERT IGNORE INTO lab_technician_settings (technician_id) VALUES ($tech_pk)");
mysqli_query($conn,"UPDATE lab_technicians SET last_login=NOW() WHERE id=$tech_pk");

// ── Stats ─────────────────────────────────────────────────
$stats = [
    'pending_orders'    => qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE order_status='Pending'"),
    'awaiting_samples'  => qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE order_status='Accepted'"),
    'processing'        => qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND order_status='Processing'"),
    'awaiting_validation'=> qv($conn,"SELECT COUNT(*) FROM lab_results_v2 WHERE technician_id=$tech_pk AND result_status IN('Draft','Pending Validation')"),
    'critical_results'  => qv($conn,"SELECT COUNT(*) FROM lab_results_v2 WHERE technician_id=$tech_pk AND result_interpretation='Critical' AND DATE(created_at)='$today'"),
    'equipment_alerts'  => qv($conn,"SELECT COUNT(*) FROM lab_equipment WHERE next_calibration_date<=CURDATE() OR status IN('Calibration Due','Out of Service','Maintenance')"),
    'low_reagents'      => qv($conn,"SELECT COUNT(*) FROM reagent_inventory WHERE status IN('Low Stock','Out of Stock','Expired')"),
    'completed_today'   => qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND order_status='Completed' AND DATE(updated_at)='$today'"),
    'completed_yest'    => qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND order_status='Completed' AND DATE(updated_at)='$yesterday'"),
    'total_this_month'  => qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND created_at>='$month_start'"),
    'unread_notifs'     => (int)qv($conn,"SELECT COUNT(*) FROM lab_notifications WHERE recipient_id=$tech_pk AND is_read=0")
                         + (int)qv($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND user_role='lab_technician' AND is_read=0"),
    'samples_today'     => qv($conn,"SELECT COUNT(*) FROM lab_samples WHERE DATE(created_at)='$today'"),
    'unreleased'        => qv($conn,"SELECT COUNT(*) FROM lab_results_v2 WHERE technician_id=$tech_pk AND released_to_doctor=0 AND result_status='Validated'"),
    'unread_messages'   => qv($conn,"SELECT COUNT(*) FROM lab_internal_messages WHERE recipient_id=$tech_pk AND is_read=0"),
];

// ── All Orders ────────────────────────────────────────────
$all_orders = [];
$q = mysqli_query($conn,"SELECT lto.*, u_p.name AS patient_name, u_d.name AS doctor_name, p.patient_id AS p_ref,
  lt_tech.full_name AS tech_name, p.id AS pat_pk
  FROM lab_test_orders lto
  LEFT JOIN patients p ON lto.patient_id=p.id LEFT JOIN users u_p ON p.user_id=u_p.id
  LEFT JOIN doctors d ON lto.doctor_id=d.id LEFT JOIN users u_d ON d.user_id=u_d.id
  LEFT JOIN lab_technicians lt_tech ON lto.technician_id=lt_tech.id
  ORDER BY FIELD(lto.urgency,'Critical','STAT','Urgent','Routine'), lto.created_at DESC LIMIT 300");
if($q) while($r=mysqli_fetch_assoc($q)) $all_orders[]=$r;

// ── Test catalog ──────────────────────────────────────────
$test_catalog = [];
$q = mysqli_query($conn,"SELECT * FROM lab_test_catalog WHERE is_active=1 ORDER BY category, test_name");
if($q) while($r=mysqli_fetch_assoc($q)) $test_catalog[]=$r;

// ── Samples ───────────────────────────────────────────────
$samples = [];
$q = mysqli_query($conn,"SELECT ls.*, u.name AS patient_name, lto.test_name, lto.urgency
  FROM lab_samples ls
  LEFT JOIN lab_test_orders lto ON ls.order_id=lto.id
  LEFT JOIN patients p ON ls.patient_id=p.id LEFT JOIN users u ON p.user_id=u.id
  ORDER BY ls.created_at DESC LIMIT 200");
if($q) while($r=mysqli_fetch_assoc($q)) $samples[]=$r;

// ── Equipment ─────────────────────────────────────────────
$equipment = [];
$q = mysqli_query($conn,"SELECT le.*, lt.full_name AS tech_name FROM lab_equipment le LEFT JOIN lab_technicians lt ON le.assigned_technician_id=lt.id ORDER BY FIELD(le.status,'Out of Service','Calibration Due','Maintenance','Operational','Decommissioned'), le.name");
if($q) while($r=mysqli_fetch_assoc($q)) $equipment[]=$r;

// ── Reagents ──────────────────────────────────────────────
$reagents = [];
$q = mysqli_query($conn,"SELECT * FROM reagent_inventory ORDER BY FIELD(status,'Out of Stock','Expired','Low Stock','Expiring Soon','In Stock'), name LIMIT 200");
if($q) while($r=mysqli_fetch_assoc($q)) $reagents[]=$r;

// ── Results ───────────────────────────────────────────────
$results = [];
$q = mysqli_query($conn,"SELECT lr.*, u_p.name AS patient_name, u_d.name AS doctor_name, lto.urgency
  FROM lab_results_v2 lr
  LEFT JOIN lab_test_orders lto ON lr.order_id=lto.id
  LEFT JOIN patients p ON lr.patient_id=p.id LEFT JOIN users u_p ON p.user_id=u_p.id
  LEFT JOIN doctors d ON lr.doctor_id=d.id LEFT JOIN users u_d ON d.user_id=u_d.id
  WHERE lr.technician_id=$tech_pk
  ORDER BY lr.created_at DESC LIMIT 200");
if($q) while($r=mysqli_fetch_assoc($q)) $results[]=$r;

// ── Reference Ranges ──────────────────────────────────────
$ref_ranges = [];
$q = mysqli_query($conn,"SELECT rr.*, tc.test_name FROM lab_reference_ranges rr LEFT JOIN lab_test_catalog tc ON rr.test_catalog_id=tc.id ORDER BY tc.test_name, rr.parameter_name");
if($q) while($r=mysqli_fetch_assoc($q)) $ref_ranges[]=$r;

// ── QC Records ────────────────────────────────────────────
$qc_records = [];
$q = mysqli_query($conn,"SELECT qc.*, le.name AS equip_name, tc.test_name
  FROM lab_quality_control qc
  LEFT JOIN lab_equipment le ON qc.equipment_id=le.id
  LEFT JOIN lab_test_catalog tc ON qc.test_catalog_id=tc.id
  WHERE qc.technician_id=$tech_pk
  ORDER BY qc.qc_date DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $qc_records[]=$r;

// ── Messages ──────────────────────────────────────────────
$messages = [];
$q = mysqli_query($conn,"SELECT lm.*, u_s.name AS sender_name, u_r.name AS recipient_name
  FROM lab_internal_messages lm
  LEFT JOIN users u_s ON lm.sender_id=u_s.id
  LEFT JOIN users u_r ON lm.recipient_id=u_r.id
  WHERE lm.sender_id=$user_id OR lm.recipient_id=$tech_pk
  ORDER BY lm.created_at DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $messages[]=$r;

// ── Audit Trail ───────────────────────────────────────────
$audit_trail = [];
$q = mysqli_query($conn,"SELECT at_log.*, lt.full_name AS tech_name FROM lab_audit_trail at_log LEFT JOIN lab_technicians lt ON at_log.technician_id=lt.id ORDER BY at_log.created_at DESC LIMIT 200");
if($q) while($r=mysqli_fetch_assoc($q)) $audit_trail[]=$r;

// ── Activity Feed ─────────────────────────────────────────
$activity = [];
$q = mysqli_query($conn,"SELECT action_type AS type, module_affected AS module, record_id, created_at AS ts FROM lab_audit_trail WHERE technician_id=$tech_pk ORDER BY created_at DESC LIMIT 10");
if($q) while($r=mysqli_fetch_assoc($q)) $activity[]=$r;

// ── Notifications (merged: lab_notifications + shared notifications) ──
$notifs = [];
// 1. Lab-specific notifications
$q = mysqli_query($conn,"SELECT id, type, title, message, is_read, module, related_id, priority, created_at FROM lab_notifications WHERE recipient_id=$tech_pk ORDER BY created_at DESC LIMIT 50");
if($q) while($r=mysqli_fetch_assoc($q)) { $r['_source']='lab'; $notifs[]=$r; }
// 2. Cross-dashboard notifications (from doctors/admins via crossNotify)
$q = mysqli_query($conn,"SELECT id, type, title, message, is_read, related_module AS module, related_id, priority, created_at FROM notifications WHERE user_id=$user_id AND user_role='lab_technician' ORDER BY created_at DESC LIMIT 50");
if($q) while($r=mysqli_fetch_assoc($q)) { $r['_source']='shared'; $notifs[]=$r; }
// Sort merged list by created_at desc, keep latest 60
usort($notifs, function($a,$b){ return strtotime($b['created_at']) - strtotime($a['created_at']); });
$notifs = array_slice($notifs, 0, 60);


// ── TAT Delay Monitor (runs on every page load) ────────────
// Finds orders processing beyond their normal turnaround time and notifies doctors (once per order)
require_once 'cross_notify.php';
$tat_overdue = [];
$q_tat = mysqli_query($conn,
    "SELECT lto.id, lto.order_id, lto.test_name, lto.doctor_id, lto.patient_id,
            lto.created_at, lto.urgency,
            COALESCE(tc.normal_turnaround_hours, 24) AS tat_hours,
            d.user_id AS doc_uid,
            TIMESTAMPDIFF(HOUR, lto.created_at, NOW()) AS hours_elapsed
     FROM lab_test_orders lto
     LEFT JOIN lab_test_catalog tc ON lto.test_catalog_id = tc.id
     LEFT JOIN doctors d ON lto.doctor_id = d.id
     WHERE lto.order_status IN ('Processing','Sample Collected','Accepted')
       AND TIMESTAMPDIFF(HOUR, lto.created_at, NOW()) > COALESCE(tc.normal_turnaround_hours, 24)
     ORDER BY hours_elapsed DESC LIMIT 20");
if($q_tat) while($row = mysqli_fetch_assoc($q_tat)){
    // Dedup: only notify if no tat_alert was sent for this order in the last 8 hours
    $already = (int)qv($conn,
        "SELECT COUNT(*) FROM lab_notifications WHERE recipient_id=$tech_pk
         AND type='tat_alert' AND related_id={$row['id']}
         AND created_at >= DATE_SUB(NOW(), INTERVAL 8 HOUR)");
    if($already) continue;
    $hr  = (int)$row['hours_elapsed'];
    $tat = (int)$row['tat_hours'];
    $msg = "Order {$row['order_id']} ({$row['test_name']}) has been processing for {$hr}h (expected TAT: {$tat}h). Please follow up.";
    // Notify doctor
    if($row['doc_uid']){
        crossNotify($conn,(int)$row['doc_uid'],'doctor','lab',
            'Lab Processing Delayed: '.$row['test_name'], $msg, 'orders', (int)$row['id'], 'normal');
    }
    // Log into lab_notifications for the technician (self-alert)
    mysqli_query($conn,"INSERT INTO lab_notifications (recipient_id,type,title,message,is_read,module,related_id,created_at)
        VALUES($tech_pk,'tat_alert','TAT Delay: ".mysqli_real_escape_string($conn,$row['test_name'])."',
        '".mysqli_real_escape_string($conn,$msg)."',0,'orders',{$row['id']},NOW())");
    $tat_overdue[] = $row;
}

// ── Analytics Data ────────────────────────────────────────

$vol_week = [];
for ($i=6;$i>=0;$i--) {
    $d=date('Y-m-d',strtotime("-$i days"));
    $c=qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND DATE(created_at)='$d'");
    $vol_week[]=['label'=>date('D',strtotime($d)),'count'=>(int)$c];
}
$cat_break = [];
$q=mysqli_query($conn,"SELECT tc.category, COUNT(*) AS cnt FROM lab_test_orders lto LEFT JOIN lab_test_catalog tc ON lto.test_catalog_id=tc.id WHERE lto.technician_id=$tech_pk AND lto.created_at>='$month_start' GROUP BY tc.category ORDER BY cnt DESC LIMIT 8");
if($q) while($r=mysqli_fetch_assoc($q)) $cat_break[]=$r;

// Top 3 tests today
$top3_tests = [];
$q=mysqli_query($conn,"SELECT test_name, COUNT(*) AS cnt FROM lab_test_orders WHERE DATE(created_at)='$today' GROUP BY test_name ORDER BY cnt DESC LIMIT 3");
if($q) while($r=mysqli_fetch_assoc($q)) $top3_tests[]=$r;

// Status breakdown
$status_break = [];
foreach(['Pending','Accepted','Sample Collected','Processing','Completed','Rejected'] as $st){
    $status_break[$st] = qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND order_status='$st' AND created_at>='$month_start'");
}

// Avg TAT
$avg_tat = qv($conn,"SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR,lto.created_at,lto.updated_at)),1) FROM lab_test_orders lto WHERE lto.technician_id=$tech_pk AND lto.order_status='Completed' AND lto.updated_at>='$month_start'");

// Interp counts
$interp_data=['Normal'=>0,'Abnormal'=>0,'Critical'=>0,'Inconclusive'=>0];
foreach($results as $r) if(isset($interp_data[$r['result_interpretation']])) $interp_data[$r['result_interpretation']]++;

// JSON for charts
$weekly_labels = json_encode(array_column($vol_week,'label'));
$weekly_data   = json_encode(array_column($vol_week,'count'));
$cat_labels    = json_encode(array_column($cat_break,'category'));
$cat_data      = json_encode(array_map(function($r){return (int)$r['cnt'];}, $cat_break));
$status_labels = json_encode(array_keys($status_break));
$status_data   = json_encode(array_values($status_break));

// Active tab
$active_tab = htmlspecialchars($_GET['tab'] ?? 'overview');
$avi = $tech_row['profile_photo'] ?? $tech_row['profile_image'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Lab Dashboard — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ── Lab Role Accent (Purple) ── */
:root{--role-accent:#8E44AD;--role-accent-dark:#6C3483;--role-accent-light:#F4ECF7;}
[data-theme="dark"]{--role-accent-light:#2d1a3e;}

/* Override sidebar gradient for lab */
.adm-sidebar{background:linear-gradient(175deg,#1C3A6B 0%,#8E44AD 60%,#C39BD3 100%)!important;}

/* ── Tab Sections ── */
.dash-section{display:none;animation:fadeIn .3s ease;}
.dash-section.active{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* Role stat icon overrides */
.adm-stat-icon.lab{background:linear-gradient(135deg,#8E44AD,#C39BD3);}
.adm-stat-icon.lab-danger{background:linear-gradient(135deg,#E74C3C,#EC7063);}
.adm-stat-icon.lab-warning{background:linear-gradient(135deg,#F39C12,#F7CF68);}
.adm-stat-icon.lab-success{background:linear-gradient(135deg,#27AE60,#58D68D);}
.adm-stat-icon.lab-info{background:linear-gradient(135deg,#2980B9,#5DADE2);}
.adm-stat-icon.lab-teal{background:linear-gradient(135deg,#1ABC9C,#48C9B0);}

/* Lab action tiles accent */
.adm-action-tile:hover{border-color:var(--role-accent);color:var(--role-accent);}
.adm-action-tile:hover i{background:linear-gradient(135deg,var(--role-accent-dark),var(--role-accent));}

/* ── Status Pipeline ── */
.status-pipeline{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;}
.pipeline-step{padding:.5rem 1rem;border-radius:20px;font-size:1.1rem;font-weight:600;background:var(--surface-2);color:var(--text-muted);position:relative;}
.pipeline-step.active{background:var(--role-accent);color:#fff;}
.pipeline-step.completed{background:var(--success-light);color:var(--success);}
.pipeline-arrow{color:var(--text-muted);font-size:1rem;}

/* ── Filter Tabs ── */
.filter-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;}
.ftab{padding:.55rem 1.2rem;border-radius:20px;font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition);}
.ftab.active,.ftab:hover{background:var(--role-accent);color:#fff;border-color:var(--role-accent);}

/* ── Modal ── */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2000;align-items:center;justify-content:center;padding:1rem;}
.modal-bg.open{display:flex;}
.modal-box{background:var(--surface);border-radius:var(--radius-lg);padding:2.6rem;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg);animation:fadeIn .25s ease;}
.modal-box.wide{max-width:800px;}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;}
.modal-header h3{font-size:1.8rem;font-weight:700;display:flex;align-items:center;gap:.8rem;}
.modal-header h3 i{color:var(--role-accent);}
.modal-close{background:none;border:none;font-size:2rem;cursor:pointer;color:var(--text-muted);transition:color .2s;}
.modal-close:hover{color:var(--danger);}

/* ── Form Controls ── */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;}
.form-group{margin-bottom:1.4rem;}
.form-group label{display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.04em;}
.form-control{width:100%;padding:1rem 1.2rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.3rem;transition:var(--transition);outline:none;box-sizing:border-box;}
.form-control:focus{border-color:var(--role-accent);box-shadow:0 0 0 3px rgba(142,68,173,.12);}
textarea.form-control{resize:vertical;min-height:60px;}

/* ── Urgency Badges ── */
.urgency-stat{padding:.3rem .8rem;border-radius:20px;font-size:1.1rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
.urgency-stat.stat-val{background:rgba(192,57,43,.12);color:var(--triage-emergency);border:1px solid rgba(192,57,43,.25);}
.urgency-critical{background:rgba(192,57,43,.12);color:#922B21;border:1px solid rgba(192,57,43,.25);animation:pulse-emergency 2s infinite;}
.urgency-urgent{background:rgba(230,126,34,.12);color:var(--triage-urgent);border:1px solid rgba(230,126,34,.25);}
.urgency-routine{background:rgba(39,174,96,.10);color:var(--triage-routine);border:1px solid rgba(39,174,96,.22);}

/* ── Responsive form ── */
@media(max-width:768px){.form-row{grid-template-columns:1fr;}}
</style>
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/notifications.css">
</head>
<body>
<div class="adm-layout">

<!-- ════════════════ SIDEBAR ════════════════ -->
<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-sidebar-brand">
    <div class="adm-sidebar-brand-icon"><i class="fas fa-flask"></i></div>
    <div class="adm-sidebar-brand-text">
      <h2>RMU Sickbay</h2>
      <span>Lab Portal</span>
    </div>
  </div>
  <nav class="adm-sidebar-nav">
    <span class="adm-nav-section-label">Main</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='overview')?'active':''?>" onclick="showTab('overview',this)"><i class="fas fa-house"></i><span>Overview</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='orders')?'active':''?>" onclick="showTab('orders',this)">
      <i class="fas fa-clipboard-list"></i><span>Test Orders</span>
      <?php if($stats['pending_orders']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['pending_orders']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='samples')?'active':''?>" onclick="showTab('samples',this)"><i class="fas fa-vial"></i><span>Sample Tracking</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='results')?'active':''?>" onclick="showTab('results',this)">
      <i class="fas fa-microscope"></i><span>Results</span>
      <?php if($stats['awaiting_validation']>0):?><span class="adm-badge adm-badge-info" style="margin-left:auto;font-size:1rem;"><?=$stats['awaiting_validation']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='catalog')?'active':''?>" onclick="showTab('catalog',this)"><i class="fas fa-book-medical"></i><span>Test Catalog</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='ref_ranges')?'active':''?>" onclick="showTab('ref_ranges',this)"><i class="fas fa-ruler-combined"></i><span>Reference Ranges</span></a>

    <span class="adm-nav-section-label">Inventory</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='equipment')?'active':''?>" onclick="showTab('equipment',this)">
      <i class="fas fa-tools"></i><span>Equipment</span>
      <?php if($stats['equipment_alerts']>0):?><span class="adm-badge adm-badge-danger" style="margin-left:auto;font-size:1rem;"><?=$stats['equipment_alerts']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='reagents')?'active':''?>" onclick="showTab('reagents',this)">
      <i class="fas fa-prescription-bottle"></i><span>Reagents</span>
      <?php if($stats['low_reagents']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['low_reagents']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='qc')?'active':''?>" onclick="showTab('qc',this)"><i class="fas fa-check-double"></i><span>Quality Control</span></a>

    <span class="adm-nav-section-label">Communication</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='messages')?'active':''?>" onclick="showTab('messages',this)">
      <i class="fas fa-comments"></i><span>Messages</span>
      <?php if($stats['unread_messages']>0):?><span class="adm-badge adm-badge-danger" style="margin-left:auto;font-size:1rem;"><?=$stats['unread_messages']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='notifications')?'active':''?>" onclick="showTab('notifications',this)">
      <i class="fas fa-bell"></i><span>Notifications</span>
      <?php if($stats['unread_notifs']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['unread_notifs']?></span><?php endif;?>
    </a>

    <span class="adm-nav-section-label">Insights</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='analytics')?'active':''?>" onclick="showTab('analytics',this)"><i class="fas fa-chart-bar"></i><span>Analytics</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='reports')?'active':''?>" onclick="showTab('reports',this)"><i class="fas fa-file-export"></i><span>Reports</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='audit')?'active':''?>" onclick="showTab('audit',this)"><i class="fas fa-shield-halved"></i><span>Audit Trail</span></a>

    <span class="adm-nav-section-label">Account</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='profile')?'active':''?>" onclick="showTab('profile',this)"><i class="fas fa-user-circle"></i><span>My Profile</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='settings')?'active':''?>" onclick="showTab('settings',this)"><i class="fas fa-gear"></i><span>Settings</span></a>
  </nav>
  <div class="adm-sidebar-footer">
    <a href="/RMU-Medical-Management-System/php/login.php?logout=1" class="adm-logout-btn"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a>
  </div>
</aside>
<div class="adm-overlay" id="admOverlay"></div>

<!-- ════════════════ MAIN ════════════════ -->
<main class="adm-main">
  <!-- TOPBAR -->
  <div class="adm-topbar">
    <div class="adm-topbar-left">
      <button class="adm-menu-toggle" id="menuToggle" style="display:none;"><i class="fas fa-bars"></i></button>
      <span class="adm-page-title"><i class="fas fa-flask" style="color:var(--role-accent);margin-right:.6rem;"></i><span id="pageTitleText">Overview</span></span>
    </div>
    <div class="adm-topbar-right">
      <span class="adm-topbar-datetime"><i class="fas fa-calendar-day"></i> <?=date('D, d M Y')?></span>
      <button class="adm-notif-btn" onclick="showTab('notifications',document.querySelector('[onclick*=notifications]'))" title="Notifications">
        <i class="fas fa-bell"></i>
        <?php if($stats['unread_notifs']>0):?><span class="adm-notif-badge"><?=$stats['unread_notifs']?></span><?php endif;?>
      </button>
      <button class="adm-theme-toggle" onclick="toggleTheme()" title="Toggle Theme"><i class="fas fa-moon" id="themeIcon"></i></button>
      <div class="adm-avatar" onclick="showTab('profile',document.querySelector('[onclick*=profile]'))" style="cursor:pointer;overflow:hidden;">
        <?php if($avi):?><img src="/RMU-Medical-Management-System/<?=e($avi)?>" style="width:100%;height:100%;object-fit:cover;">
        <?php else:?><?=strtoupper(substr($techName,0,1))?><?php endif;?>
      </div>
    </div>
  </div>

  <div class="adm-content">

  <!-- ═══════════════ TAB: OVERVIEW ═══════════════ -->
  <div id="sec-overview" class="dash-section <?=($active_tab==='overview')?'active':''?>">
    <!-- Welcome Banner -->
    <div class="adm-welcome">
      <h2>Welcome, <?=e(explode(' ',$techName)[0])?> 👋</h2>
      <p><?=date('l, d F Y')?> &bull; <?=e($tech_row['specialization']??'General Laboratory')?> &bull; ID: <?=e($tech_row['technician_id']??'LAB-TECH')?></p>
    </div>

    <!-- Stats Grid -->
    <div class="adm-stats-grid">
      <a class="adm-stat-card" onclick="showTab('orders',document.querySelector('[onclick*=orders]'))">
        <div class="adm-stat-icon lab-warning"><i class="fas fa-clipboard-list"></i></div>
        <span class="adm-stat-label">Pending Orders</span>
        <div class="adm-stat-value"><?=$stats['pending_orders']?></div>
        <div class="adm-stat-footer"><i class="fas fa-arrow-right"></i> New requests</div>
      </a>
      <a class="adm-stat-card" onclick="showTab('samples',document.querySelector('[onclick*=samples]'))">
        <div class="adm-stat-icon lab-info"><i class="fas fa-vial"></i></div>
        <span class="adm-stat-label">Awaiting Samples</span>
        <div class="adm-stat-value"><?=$stats['awaiting_samples']?></div>
        <div class="adm-stat-footer"><i class="fas fa-flask"></i> Need collection</div>
      </a>
      <a class="adm-stat-card" onclick="showTab('orders',document.querySelector('[onclick*=orders]'))">
        <div class="adm-stat-icon lab"><i class="fas fa-cog fa-spin"></i></div>
        <span class="adm-stat-label">Processing</span>
        <div class="adm-stat-value"><?=$stats['processing']?></div>
        <div class="adm-stat-footer"><i class="fas fa-spinner"></i> In progress</div>
      </a>
      <a class="adm-stat-card" onclick="showTab('results',document.querySelector('[onclick*=results]'))">
        <div class="adm-stat-icon lab-info"><i class="fas fa-microscope"></i></div>
        <span class="adm-stat-label">Awaiting Validation</span>
        <div class="adm-stat-value"><?=$stats['awaiting_validation']?></div>
        <div class="adm-stat-footer"><i class="fas fa-check-double"></i> Need review</div>
      </a>
      <a class="adm-stat-card" onclick="showTab('results',document.querySelector('[onclick*=results]'))">
        <div class="adm-stat-icon lab-danger"><i class="fas fa-exclamation-triangle"></i></div>
        <span class="adm-stat-label">Critical Results Today</span>
        <div class="adm-stat-value"><?=$stats['critical_results']?></div>
        <div class="adm-stat-footer"><i class="fas fa-bolt"></i> Urgent attention</div>
      </a>
      <a class="adm-stat-card" onclick="showTab('equipment',document.querySelector('[onclick*=equipment]'))">
        <div class="adm-stat-icon lab-warning"><i class="fas fa-tools"></i></div>
        <span class="adm-stat-label">Equipment Alerts</span>
        <div class="adm-stat-value"><?=$stats['equipment_alerts']?></div>
        <div class="adm-stat-footer"><i class="fas fa-wrench"></i> Calibration / service</div>
      </a>
      <a class="adm-stat-card" onclick="showTab('reagents',document.querySelector('[onclick*=reagents]'))">
        <div class="adm-stat-icon lab-danger"><i class="fas fa-prescription-bottle"></i></div>
        <span class="adm-stat-label">Low Reagents</span>
        <div class="adm-stat-value"><?=$stats['low_reagents']?></div>
        <div class="adm-stat-footer"><i class="fas fa-box"></i> Need restock</div>
      </a>
    </div>

    <!-- Critical Results Alert Panel -->
    <?php if($stats['critical_results']>0):
      $crit_results=[];
      $cq=mysqli_query($conn,"SELECT lr.*, u_p.name AS patient_name, u_d.name AS doctor_name FROM lab_results_v2 lr LEFT JOIN patients p ON lr.patient_id=p.id LEFT JOIN users u_p ON p.user_id=u_p.id LEFT JOIN doctors d ON lr.doctor_id=d.id LEFT JOIN users u_d ON d.user_id=u_d.id WHERE lr.technician_id=$tech_pk AND lr.result_interpretation='Critical' AND DATE(lr.created_at)='$today' LIMIT 10");
      if($cq) while($cr=mysqli_fetch_assoc($cq)) $crit_results[]=$cr;
    ?>
    <div class="adm-alert adm-alert-danger" style="margin-bottom:2rem;">
      <i class="fas fa-exclamation-triangle"></i>
      <div style="flex:1;">
        <strong style="font-size:1.5rem;">⚠️ Critical Results Require Immediate Doctor Notification</strong>
        <?php foreach($crit_results as $cr):?>
        <div style="margin-top:.8rem;padding:.8rem;background:rgba(255,255,255,.5);border-radius:8px;display:flex;align-items:center;justify-content:space-between;">
          <span><strong><?=e($cr['test_name'])?></strong> — Patient: <?=e($cr['patient_name']??'—')?> — Dr. <?=e($cr['doctor_name']??'—')?></span>
          <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="releaseToDoctor(<?=$cr['id']?>)"><i class="fas fa-share"></i> Release</button>
        </div>
        <?php endforeach;?>
      </div>
    </div>
    <?php endif;?>

    <!-- Quick Actions -->
    <div class="adm-quick-actions mb-3">
      <a class="adm-action-tile" onclick="showTab('orders',document.querySelector('[onclick*=orders]'))"><i class="fas fa-clipboard-check"></i>Accept New Order</a>
      <a class="adm-action-tile" onclick="showTab('samples',document.querySelector('[onclick*=samples]'))"><i class="fas fa-vial"></i>Record Sample</a>
      <a class="adm-action-tile" onclick="openModal('addResultModal')"><i class="fas fa-microscope"></i>Enter Results</a>
      <a class="adm-action-tile" onclick="showTab('reports',document.querySelector('[onclick*=reports]'))"><i class="fas fa-file-export"></i>Generate Report</a>
      <a class="adm-action-tile" onclick="showTab('qc',document.querySelector('[onclick*=qc]'))"><i class="fas fa-check-double"></i>Run QC</a>
    </div>

    <!-- Charts + Mini Analytics -->
    <div class="adm-charts-grid">
      <div class="adm-chart-card">
        <h3><i class="fas fa-chart-bar"></i> Test Volume — Last 7 Days</h3>
        <div style="position:relative;height:260px;"><canvas id="weeklyChart"></canvas></div>
        <div style="margin-top:1rem;display:flex;justify-content:space-between;font-size:1.2rem;">
          <span>Today: <strong style="color:var(--role-accent);"><?=$stats['completed_today']?></strong></span>
          <span>Yesterday: <strong><?=$stats['completed_yest']?></strong></span>
          <span style="color:<?=$stats['completed_today']>=$stats['completed_yest']?'var(--success)':'var(--danger)'?>;">
            <?=$stats['completed_today']>=$stats['completed_yest']?'↑':'↓'?> <?=abs($stats['completed_today']-$stats['completed_yest'])?> difference
          </span>
        </div>
      </div>
      <div class="adm-chart-card">
        <h3><i class="fas fa-chart-pie"></i> Test Status Breakdown</h3>
        <div style="position:relative;height:260px;"><canvas id="statusChart"></canvas></div>
      </div>
    </div>

    <!-- Top 3 Tests Today + Activity Feed side-by-side -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-trophy"></i> Top 3 Requested Tests Today</h3></div>
        <div class="adm-card-body">
          <?php if(empty($top3_tests)):?><p style="color:var(--text-muted);text-align:center;">No tests ordered today</p>
          <?php else: $rank=1; foreach($top3_tests as $tt):?>
          <div style="display:flex;align-items:center;gap:1rem;padding:1rem 0;border-bottom:1px solid var(--border);">
            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--role-accent),#C39BD3);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;"><?=$rank++?></div>
            <span style="flex:1;font-weight:600;"><?=e($tt['test_name'])?></span>
            <span class="adm-badge adm-badge-primary"><?=$tt['cnt']?> orders</span>
          </div>
          <?php endforeach; endif;?>
        </div>
      </div>
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-clock"></i> Recent Activity</h3></div>
        <div class="adm-card-body">
          <?php if(empty($activity)):?><p style="color:var(--text-muted);text-align:center;">No recent activity</p>
          <?php else: foreach($activity as $a):
            $act_icons=['accept_order'=>'check','reject_order'=>'times','collect_sample'=>'vial','save_result'=>'microscope','validate_result'=>'check-double','release_result'=>'share','log_sample'=>'vial','qc_run'=>'check-double'];
            $icon=$act_icons[$a['type']]??'clock';
          ?>
          <div style="display:flex;align-items:flex-start;gap:1rem;padding:.8rem 0;border-bottom:1px solid var(--border);">
            <div style="width:8px;height:8px;border-radius:50%;background:var(--role-accent);margin-top:.6rem;flex-shrink:0;"></div>
            <div style="flex:1;"><strong style="font-size:1.25rem;"><?=e(str_replace('_',' ',ucfirst($a['type'])))?></strong><br><span style="color:var(--text-muted);font-size:1.1rem;"><?=e($a['module']??'')?></span></div>
            <span style="color:var(--text-muted);font-size:1.05rem;white-space:nowrap;"><?=date('h:i A',strtotime($a['ts']))?></span>
          </div>
          <?php endforeach; endif;?>
        </div>
      </div>
    </div>
  </div><!-- end overview -->

  <!-- ═══════════════ TAB INCLUDES ═══════════════ -->
  <div id="sec-orders" class="dash-section <?=($active_tab==='orders')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_orders.php'; ?></div>
  <div id="sec-samples" class="dash-section <?=($active_tab==='samples')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_samples.php'; ?></div>
  <div id="sec-results" class="dash-section <?=($active_tab==='results')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_results.php'; ?></div>
  <div id="sec-catalog" class="dash-section <?=($active_tab==='catalog')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_catalog.php'; ?></div>
  <div id="sec-ref_ranges" class="dash-section <?=($active_tab==='ref_ranges')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_reference_ranges.php'; ?></div>
  <div id="sec-equipment" class="dash-section <?=($active_tab==='equipment')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_equipment.php'; ?></div>
  <div id="sec-reagents" class="dash-section <?=($active_tab==='reagents')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_reagents.php'; ?></div>
  <div id="sec-qc" class="dash-section <?=($active_tab==='qc')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_qc.php'; ?></div>
  <div id="sec-messages" class="dash-section <?=($active_tab==='messages')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_messages.php'; ?></div>
  <div id="sec-notifications" class="dash-section <?=($active_tab==='notifications')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_notifications.php'; ?></div>
  <div id="sec-analytics" class="dash-section <?=($active_tab==='analytics')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_analytics.php'; ?></div>
  <div id="sec-reports" class="dash-section <?=($active_tab==='reports')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_reports.php'; ?></div>
  <div id="sec-audit" class="dash-section <?=($active_tab==='audit')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_audit.php'; ?></div>
  <div id="sec-profile" class="dash-section <?=($active_tab==='profile')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_profile.php'; ?></div>
  <div id="sec-settings" class="dash-section <?=($active_tab==='settings')?'active':''?>"><?php include __DIR__.'/lab_tabs/tab_settings.php'; ?></div>

  </div><!-- end adm-content -->
</main>
</div><!-- end adm-layout -->

<!-- ════════════════ CORE JS ════════════════ -->
<script>
const CSRF='<?=$csrf_token?>';
const BASE='/RMU-Medical-Management-System';
const ACTIONS=BASE+'/php/dashboards/lab_actions.php';

// Title map
const tabTitles={overview:'Overview',orders:'Test Orders',samples:'Sample Tracking',results:'Results',catalog:'Test Catalog',ref_ranges:'Reference Ranges',equipment:'Equipment',reagents:'Reagents',qc:'Quality Control',messages:'Messages',notifications:'Notifications',analytics:'Analytics',reports:'Reports',audit:'Audit Trail',profile:'My Profile',settings:'Settings'};

// Tab Navigation
function showTab(name,el){
  document.querySelectorAll('.dash-section').forEach(s=>s.classList.remove('active'));
  const sec=document.getElementById('sec-'+name);if(sec)sec.classList.add('active');
  document.querySelectorAll('.adm-nav-item').forEach(a=>a.classList.remove('active'));if(el)el.classList.add('active');
  document.getElementById('admSidebar').classList.remove('active');document.getElementById('admOverlay').classList.remove('active');
  document.getElementById('pageTitleText').textContent=tabTitles[name]||name;
  history.replaceState(null,'','?tab='+name);
}

// Theme Toggle
function toggleTheme(){
  const html=document.documentElement;const t=html.getAttribute('data-theme')==='dark'?'light':'dark';
  html.setAttribute('data-theme',t);localStorage.setItem('theme',t);
  document.getElementById('themeIcon').className='fas fa-'+(t==='dark'?'sun':'moon');
}
(function(){const t=localStorage.getItem('theme');if(t){document.documentElement.setAttribute('data-theme',t);if(t==='dark')document.getElementById('themeIcon').className='fas fa-sun';}})();

// Mobile sidebar
document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('admSidebar').classList.toggle('active');document.getElementById('admOverlay').classList.toggle('active');});
document.getElementById('admOverlay').addEventListener('click',function(){document.getElementById('admSidebar').classList.remove('active');this.classList.remove('active');});

// Modal
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}

// Toast
function showToast(msg,type='success'){
  const t=document.createElement('div');
  t.style.cssText='position:fixed;top:20px;right:20px;padding:1.2rem 2rem;border-radius:10px;font-size:1.3rem;font-weight:600;z-index:9999;animation:fadeIn .3s;color:#fff;display:flex;align-items:center;gap:.8rem;max-width:400px;box-shadow:var(--shadow-lg);background:'+(type==='success'?'var(--success)':type==='error'?'var(--danger)':'var(--info)')+';';
  t.innerHTML='<i class="fas fa-'+(type==='success'?'check-circle':type==='error'?'times-circle':'info-circle')+'"></i> '+msg;
  document.body.appendChild(t);setTimeout(()=>t.remove(),3500);
}

// Confirm
function confirmAction(msg){return confirm(msg);}

// AJAX Helper
async function labAction(data){
  const isFormData=data instanceof FormData;
  if(!isFormData){data._csrf=CSRF;}else{data.append('_csrf',CSRF);}
  try{
    const resp=await fetch(ACTIONS,{method:'POST',headers:isFormData?{}:{'Content-Type':'application/x-www-form-urlencoded'},body:isFormData?data:new URLSearchParams(data)});
    return await resp.json();
  }catch(e){return {success:false,message:'Network error'};}
}

// Form Validation
function validateForm(fields){
  for(const[id,label] of Object.entries(fields)){
    const el=document.getElementById(id);
    if(!el||!el.value.trim()){showToast(label+' is required','error');if(el)el.focus();return false;}
  }return true;
}

// Global Search
function handleGlobalSearch(q){
  if(q.length<2){document.querySelectorAll('.dash-section.active .adm-table tbody tr').forEach(r=>r.style.display='');return;}
  document.querySelectorAll('.dash-section.active .adm-table tbody tr').forEach(r=>{
    r.style.display=r.textContent.toLowerCase().includes(q.toLowerCase())?'':'none';
  });
}

// Generic filter
function filterTable(tableId,status,el){
  el.parentNode.querySelectorAll('.ftab').forEach(f=>f.classList.remove('active'));el.classList.add('active');
  document.querySelectorAll('#'+tableId+' tbody tr').forEach(r=>{r.style.display=(status==='all'||r.dataset.status===status)?'':'none';});
}

// Auto-refresh every 60s
setInterval(function(){
  fetch(ACTIONS,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'get_stats',_csrf:CSRF})}).then(r=>r.json()).then(d=>{
    if(d.success&&d.stats){
      // Update sidebar badges silently
      console.log('Dashboard refreshed',d.stats);
    }
  }).catch(()=>{});
},60000);

// Overview Charts
document.addEventListener('DOMContentLoaded',function(){
  if(document.getElementById('weeklyChart')){
    new Chart(document.getElementById('weeklyChart'),{type:'bar',data:{labels:<?=$weekly_labels?>,datasets:[{label:'Orders',data:<?=$weekly_data?>,backgroundColor:'rgba(142,68,173,.6)',borderColor:'#8E44AD',borderWidth:1,borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
  }
  if(document.getElementById('statusChart')){
    new Chart(document.getElementById('statusChart'),{type:'doughnut',data:{labels:<?=$status_labels?>,datasets:[{data:<?=$status_data?>,backgroundColor:['#F39C12','#2980B9','#1ABC9C','#8E44AD','#27AE60','#E74C3C']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}}});
  }
});
</script>
</body>
</html>
