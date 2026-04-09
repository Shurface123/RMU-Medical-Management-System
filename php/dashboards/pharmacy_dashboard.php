<?php
// ============================================================
// PHARMACY DASHBOARD — RMU Medical Sickbay
// Mirrors admin/doctor/patient dashboard architecture
// ============================================================
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('pharmacist');

require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');
$csrf_token = generateCsrfToken();

$user_id       = (int)$_SESSION['user_id'];
$pharmacistName= $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Pharmacist';
$today         = date('Y-m-d');
$month_start   = date('Y-m-01');

// ── Helper ────────────────────────────────────────────────
function qv($c,$s){ $r=mysqli_query($c,$s); return $r?(mysqli_fetch_row($r)[0]??0):0; }

// ── Pharmacist Profile ────────────────────────────────────
$pharm_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT pp.*, u.name, u.email, u.phone, u.profile_image, u.date_of_birth, u.two_fa_enabled
     FROM pharmacist_profile pp JOIN users u ON pp.user_id=u.id
     WHERE pp.user_id=$user_id LIMIT 1"));
if (!$pharm_row) {
    $pharm_pk  = 0;
    $pharm_row = ['full_name'=>$pharmacistName,'license_number'=>'N/A','profile_photo'=>'',
                  'years_of_experience'=>0,'specialization'=>'','profile_image'=>''];
} else { $pharm_pk = (int)$pharm_row['id']; }

// ── Stats Cards ───────────────────────────────────────────
$stats = [
    'total_medicines'  => qv($conn,"SELECT COUNT(*) FROM medicines WHERE status='active'"),
    'in_stock'         => qv($conn,"SELECT COUNT(*) FROM medicines WHERE stock_quantity>reorder_level AND status='active'"),
    'low_stock'        => qv($conn,"SELECT COUNT(*) FROM medicines WHERE stock_quantity>0 AND stock_quantity<=reorder_level AND status='active'"),
    'out_of_stock'     => qv($conn,"SELECT COUNT(*) FROM medicines WHERE stock_quantity=0 AND status='active'"),
    'expiring_soon'    => qv($conn,"SELECT COUNT(*) FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND status='active'"),
    'expired'          => qv($conn,"SELECT COUNT(*) FROM medicines WHERE expiry_date < CURDATE() AND status='active'"),
    'pending_rx'       => qv($conn,"SELECT COUNT(*) FROM prescriptions WHERE status='Pending'"),
    'dispensed_today'  => qv($conn,"SELECT COUNT(*) FROM prescriptions WHERE status='Dispensed' AND DATE(dispensed_date)='$today'"),
    'total_stock_value'=> qv($conn,"SELECT COALESCE(SUM(stock_quantity*unit_price),0) FROM medicines WHERE status='active'"),
    'unread_notifs'    => qv($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND is_read=0"),
    'active_alerts'    => qv($conn,"SELECT COUNT(*) FROM stock_alerts WHERE is_resolved=0"),
    'pending_orders'   => qv($conn,"SELECT COUNT(*) FROM purchase_orders WHERE status IN('draft','sent')"),
    'refill_requests'  => qv($conn,"SELECT COUNT(*) FROM prescription_refills WHERE status='Pending'"),
];

// ── Pending Prescriptions ─────────────────────────────────
$pending_rx = [];
$q = mysqli_query($conn,"SELECT pr.*, up.name AS patient_name, ud.name AS doctor_name, p.patient_id AS p_ref
  FROM prescriptions pr
  JOIN patients p ON pr.patient_id=p.id JOIN users up ON p.user_id=up.id
  JOIN doctors d ON pr.doctor_id=d.id JOIN users ud ON d.user_id=ud.id
  WHERE pr.status IN('Pending','Partially Dispensed') ORDER BY pr.prescription_date DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $pending_rx[]=$r;

// ── All Prescriptions ─────────────────────────────────────
$all_rx = [];
$q = mysqli_query($conn,"SELECT pr.*, up.name AS patient_name, ud.name AS doctor_name, p.patient_id AS p_ref
  FROM prescriptions pr
  JOIN patients p ON pr.patient_id=p.id JOIN users up ON p.user_id=up.id
  JOIN doctors d ON pr.doctor_id=d.id JOIN users ud ON d.user_id=ud.id
  ORDER BY pr.prescription_date DESC LIMIT 200");
if($q) while($r=mysqli_fetch_assoc($q)) $all_rx[]=$r;

// ── Medicine Inventory ────────────────────────────────────
$medicines = [];
$q = mysqli_query($conn,"SELECT m.*, ps.supplier_name
  FROM medicines m LEFT JOIN pharmacy_suppliers ps ON m.supplier_name=ps.supplier_name
  WHERE m.status='active' ORDER BY
    CASE WHEN m.stock_quantity=0 THEN 0
         WHEN m.stock_quantity<=m.reorder_level THEN 1
         WHEN m.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) THEN 2
         ELSE 3 END ASC, m.medicine_name ASC LIMIT 300");
if($q) while($r=mysqli_fetch_assoc($q)) $medicines[]=$r;

// ── Dispensing Records ────────────────────────────────────
$dispensing_records = [];
$q = mysqli_query($conn,"SELECT dr.*, m.medicine_name, up.name AS patient_name, ud.name AS doctor_name,
  ph.name AS pharmacist_name, pr.prescription_id AS rx_ref
  FROM dispensing_records dr
  JOIN medicines m ON dr.medicine_id=m.id
  JOIN patients p ON dr.patient_id=p.id JOIN users up ON p.user_id=up.id
  LEFT JOIN prescriptions pr ON dr.prescription_id=pr.id
  LEFT JOIN doctors d ON pr.doctor_id=d.id LEFT JOIN users ud ON d.user_id=ud.id
  LEFT JOIN users ph ON dr.pharmacist_id=ph.id
  ORDER BY dr.dispensing_date DESC LIMIT 200");
if($q) while($r=mysqli_fetch_assoc($q)) $dispensing_records[]=$r;

// ── Stock Alerts ──────────────────────────────────────────
$alerts = [];
$q = mysqli_query($conn,"SELECT sa.*, m.medicine_name, m.stock_quantity, m.reorder_level, m.expiry_date, m.supplier_name
  FROM stock_alerts sa JOIN medicines m ON sa.medicine_id=m.id
  WHERE sa.is_resolved=0 ORDER BY sa.created_at DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $alerts[]=$r;

// ── Stock Transactions ────────────────────────────────────
$stock_txns = [];
$q = mysqli_query($conn,"SELECT st.*, m.medicine_name, u.name AS performed_by_name
  FROM stock_transactions st JOIN medicines m ON st.medicine_id=m.id
  JOIN users u ON st.performed_by=u.id
  ORDER BY st.transaction_date DESC LIMIT 200");
if($q) while($r=mysqli_fetch_assoc($q)) $stock_txns[]=$r;

// ── Purchase Orders ───────────────────────────────────────
$purchase_orders = [];
$q = mysqli_query($conn,"SELECT po.*, ps.supplier_name, u.name AS ordered_by_name
  FROM purchase_orders po
  JOIN pharmacy_suppliers ps ON po.supplier_id=ps.supplier_id
  JOIN users u ON po.ordered_by=u.id
  ORDER BY po.order_date DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $purchase_orders[]=$r;

// ── Suppliers ─────────────────────────────────────────────
$suppliers = [];
$q = mysqli_query($conn,"SELECT * FROM pharmacy_suppliers ORDER BY is_active DESC, supplier_name ASC");
if($q) while($r=mysqli_fetch_assoc($q)) $suppliers[]=$r;

// ── Recent Activity Feed ──────────────────────────────────
$activity = [];
$q = mysqli_query($conn,"(SELECT 'Dispensed' AS type, CONCAT('Dispensed ',m.medicine_name,' to ',up.name) AS description, dr.dispensing_date AS ts
  FROM dispensing_records dr JOIN medicines m ON dr.medicine_id=m.id JOIN patients p ON dr.patient_id=p.id JOIN users up ON p.user_id=up.id
  ORDER BY dr.dispensing_date DESC LIMIT 5)
  UNION ALL
  (SELECT 'Prescription' AS type, CONCAT('New Rx from Dr. ',ud.name,' for ',up.name) AS description, pr.created_at AS ts
  FROM prescriptions pr JOIN patients p ON pr.patient_id=p.id JOIN users up ON p.user_id=up.id
  JOIN doctors d ON pr.doctor_id=d.id JOIN users ud ON d.user_id=ud.id
  WHERE pr.status='Pending' ORDER BY pr.created_at DESC LIMIT 5)
  UNION ALL
  (SELECT 'Alert' AS type, CONCAT(sa.alert_type,': ',m.medicine_name) AS description, sa.created_at AS ts
  FROM stock_alerts sa JOIN medicines m ON sa.medicine_id=m.id WHERE sa.is_resolved=0 ORDER BY sa.created_at DESC LIMIT 5)
  ORDER BY ts DESC LIMIT 10");
if($q) while($r=mysqli_fetch_assoc($q)) $activity[]=$r;

// ── Analytics Data ────────────────────────────────────────
// Dispensing volume — last 7 days
$disp_week = [];
for ($i=6;$i>=0;$i--) {
    $d=date('Y-m-d',strtotime("-$i days"));
    $c=qv($conn,"SELECT COUNT(*) FROM dispensing_records WHERE DATE(dispensing_date)='$d'");
    $disp_week[]=['label'=>date('D',strtotime($d)),'count'=>(int)$c];
}
// Top 5 dispensed medicines
$top_meds = [];
$q=mysqli_query($conn,"SELECT m.medicine_name, SUM(dr.quantity_dispensed) AS total
  FROM dispensing_records dr JOIN medicines m ON dr.medicine_id=m.id
  WHERE dr.dispensing_date>=DATE_SUB(CURDATE(),INTERVAL 30 DAY)
  GROUP BY m.medicine_name ORDER BY total DESC LIMIT 5");
if($q) while($r=mysqli_fetch_assoc($q)) $top_meds[]=$r;

// Stock status breakdown
$stock_breakdown = [
    'In Stock'     => (int)$stats['in_stock'],
    'Low Stock'    => (int)$stats['low_stock'],
    'Out of Stock' => (int)$stats['out_of_stock'],
    'Expiring Soon'=> (int)$stats['expiring_soon'],
    'Expired'      => (int)$stats['expired'],
];

// Prescription fulfillment
$rx_fulfill = [];
foreach(['Pending','Dispensed','Partially Dispensed','Cancelled','Expired'] as $st){
    $c=qv($conn,"SELECT COUNT(*) FROM prescriptions WHERE status='$st'");
    $rx_fulfill[]=['status'=>$st,'count'=>(int)$c];
}

// JSON for charts
$weekly_labels = json_encode(array_column($disp_week,'label'));
$weekly_data   = json_encode(array_column($disp_week,'count'));
$top_med_labels= json_encode(array_column($top_meds,'medicine_name'));
$top_med_data  = json_encode(array_map(function($r){return (int)$r['total'];}, $top_meds));
$stock_labels  = json_encode(array_keys($stock_breakdown));
$stock_data    = json_encode(array_values($stock_breakdown));
$fulfill_labels= json_encode(array_column($rx_fulfill,'status'));
$fulfill_data  = json_encode(array_column($rx_fulfill,'count'));

// ── Notifications ─────────────────────────────────────────
$notifs = [];
$q=mysqli_query($conn,"SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 20");
if($q) while($r=mysqli_fetch_assoc($q)) $notifs[]=$r;

// ── Auto-generate stock alerts ────────────────────────────
// Low stock
$q=mysqli_query($conn,"SELECT id,medicine_name,stock_quantity,reorder_level FROM medicines WHERE stock_quantity>0 AND stock_quantity<=reorder_level AND status='active'");
if($q) while($m=mysqli_fetch_assoc($q)){
    $exists=qv($conn,"SELECT COUNT(*) FROM stock_alerts WHERE medicine_id={$m['id']} AND alert_type='low_stock' AND is_resolved=0");
    if(!$exists) mysqli_query($conn,"INSERT INTO stock_alerts(medicine_id,alert_type,threshold_value,current_value) VALUES({$m['id']},'low_stock',{$m['reorder_level']},{$m['stock_quantity']})");
}
// Out of stock
$q=mysqli_query($conn,"SELECT id FROM medicines WHERE stock_quantity=0 AND status='active'");
if($q) while($m=mysqli_fetch_assoc($q)){
    $exists=qv($conn,"SELECT COUNT(*) FROM stock_alerts WHERE medicine_id={$m['id']} AND alert_type='out_of_stock' AND is_resolved=0");
    if(!$exists) mysqli_query($conn,"INSERT INTO stock_alerts(medicine_id,alert_type,threshold_value,current_value) VALUES({$m['id']},'out_of_stock',0,0)");
}
// Expiring soon
$q=mysqli_query($conn,"SELECT id,DATEDIFF(expiry_date,CURDATE()) AS days_left FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND status='active'");
if($q) while($m=mysqli_fetch_assoc($q)){
    $exists=qv($conn,"SELECT COUNT(*) FROM stock_alerts WHERE medicine_id={$m['id']} AND alert_type='expiring_soon' AND is_resolved=0");
    if(!$exists) mysqli_query($conn,"INSERT INTO stock_alerts(medicine_id,alert_type,threshold_value,current_value) VALUES({$m['id']},'expiring_soon',30,{$m['days_left']})");
}
// Expired
$q=mysqli_query($conn,"SELECT id FROM medicines WHERE expiry_date < CURDATE() AND status='active'");
if($q) while($m=mysqli_fetch_assoc($q)){
    $exists=qv($conn,"SELECT COUNT(*) FROM stock_alerts WHERE medicine_id={$m['id']} AND alert_type='expired' AND is_resolved=0");
    if(!$exists) mysqli_query($conn,"INSERT INTO stock_alerts(medicine_id,alert_type,threshold_value,current_value) VALUES({$m['id']},'expired',0,0)");
}

// ── Cross-Dashboard: Daily admin summary alert ────────────
// Send once per day: consolidated pharmacy stock summary to all admins
$todayAlertSent = qv($conn,"SELECT COUNT(*) FROM notifications WHERE type='pharmacy_daily_summary' AND DATE(created_at)='$today' LIMIT 1");
if(!$todayAlertSent && ($stats['low_stock']>0 || $stats['out_of_stock']>0 || $stats['expiring_soon']>0 || $stats['expired']>0)){
    $summaryMsg = "📊 Pharmacy Daily Summary: ";
    $parts = [];
    if($stats['out_of_stock']>0) $parts[] = $stats['out_of_stock']." out of stock";
    if($stats['low_stock']>0) $parts[] = $stats['low_stock']." low stock";
    if($stats['expiring_soon']>0) $parts[] = $stats['expiring_soon']." expiring within 30 days";
    if($stats['expired']>0) $parts[] = $stats['expired']." expired";
    $summaryMsg .= implode(', ', $parts) . ". Review pharmacy alerts.";
    $admQ = mysqli_query($conn,"SELECT id FROM users WHERE user_role='admin' AND is_active=1");
    if($admQ) while($adm=mysqli_fetch_assoc($admQ)){
        $smEsc = mysqli_real_escape_string($conn, $summaryMsg);
        mysqli_query($conn,"INSERT INTO notifications (user_id, message, type, related_module, is_read, created_at) VALUES ({$adm['id']}, '$smEsc', 'pharmacy_daily_summary', 'pharmacy', 0, NOW())");
    }
}

// ── Handle active tab from URL ────────────────────────────
$active_tab = htmlspecialchars($_GET['tab'] ?? 'overview');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Pharmacy Dashboard — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root{--role-accent:#27AE60;--role-accent-dark:#1E8449;--role-accent-light:#EAFAF1;}
[data-theme="dark"]{--role-accent-light:#0d2b19;}

/* ── Hero Banner ── */
.pharm-hero{background:linear-gradient(135deg,#1C3A6B 0%,#27AE60 55%,#2F80ED 100%);color:#fff;border-radius:var(--radius-lg);padding:2.2rem 2.8rem;margin-bottom:2rem;display:flex;align-items:center;gap:1.8rem;flex-wrap:wrap;position:relative;overflow:hidden;}
.pharm-hero::after{content:'';position:absolute;right:-30px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06);}
.pharm-avatar-hero{width:76px;height:76px;border-radius:50%;background:rgba(255,255,255,.18);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;font-size:2.2rem;border:3px solid rgba(255,255,255,.35);flex-shrink:0;overflow:hidden;}
.pharm-avatar-hero img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.pharm-hero-info h2{font-size:1.7rem;font-weight:700;margin:0 0 .3rem;}
.pharm-hero-info p{margin:0;opacity:.85;font-size:.9rem;}
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
.form-control:focus{border-color:var(--role-accent);box-shadow:0 0 0 3px rgba(39,174,96,.12);}

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

/* ── Responsive Sidebar ── */
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
<!-- Phase 4 Hooks --><link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css"><meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>"></head>
<body>
<div class="adm-layout">

<!-- ════════════════ SIDEBAR ════════════════ -->
<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-sidebar-brand">
    <div class="adm-brand-icon"><i class="fas fa-prescription-bottle-medical"></i></div>
    <div class="adm-brand-text">
      <span class="adm-brand-name">RMU Sickbay</span>
      <span class="adm-brand-role">Pharmacy Portal</span>
    </div>
  </div>
  <nav class="adm-nav" style="padding:1.5rem 1rem;flex:1;">
    <div class="adm-nav-label">Main</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='overview')?'active':''?>" onclick="showTab('overview',this)"><i class="fas fa-house"></i><span>Overview</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='inventory')?'active':''?>" onclick="showTab('inventory',this)">
      <i class="fas fa-pills"></i><span>Medicine Inventory</span>
      <?php if($stats['low_stock']>0||$stats['out_of_stock']>0):?><span class="adm-badge adm-badge-danger" style="margin-left:auto;font-size:1rem;"><?=$stats['low_stock']+$stats['out_of_stock']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='prescriptions')?'active':''?>" onclick="showTab('prescriptions',this)">
      <i class="fas fa-prescription-bottle-medical"></i><span>Prescriptions</span>
      <?php if($stats['pending_rx']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['pending_rx']?></span><?php endif;?>
    </a>
    <div class="adm-nav-label" style="margin-top:1rem;">Inventory</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='stock')?'active':''?>" onclick="showTab('stock',this)"><i class="fas fa-boxes-stacked"></i><span>Stock Management</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='alerts')?'active':''?>" onclick="showTab('alerts',this)">
      <i class="fas fa-triangle-exclamation"></i><span>Alerts</span>
      <?php if($stats['active_alerts']>0):?><span class="adm-badge adm-badge-danger" style="margin-left:auto;font-size:1rem;"><?=$stats['active_alerts']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='dispensing')?'active':''?>" onclick="showTab('dispensing',this)"><i class="fas fa-hand-holding-medical"></i><span>Dispensing History</span></a>
    <div class="adm-nav-label" style="margin-top:1rem;">Insights</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='analytics')?'active':''?>" onclick="showTab('analytics',this)"><i class="fas fa-chart-bar"></i><span>Analytics</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='reports')?'active':''?>" onclick="showTab('reports',this)"><i class="fas fa-file-export"></i><span>Reports</span></a>
    <div class="adm-nav-label" style="margin-top:1rem;">Account</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='notifications')?'active':''?>" onclick="showTab('notifications',this)">
      <i class="fas fa-bell"></i><span>Notifications</span>
      <?php if($stats['unread_notifs']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['unread_notifs']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='settings')?'active':''?>" onclick="showTab('settings',this)"><i class="fas fa-gear"></i><span>Settings</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='profile')?'active':''?>" onclick="showTab('profile',this)"><i class="fas fa-user-circle"></i><span>My Profile</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='system_settings')?'active':''?>" onclick="showTab('system_settings',this)"><i class="fas fa-sliders"></i><span>System Settings</span></a>
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
      <span class="adm-page-title" id="pageTitle"><i class="fas fa-house" style="color:var(--role-accent);margin-right:.6rem;"></i><span id="pageTitleText">Overview</span></span>
    </div>
    <div class="adm-topbar-right">
      <span style="font-size:1.2rem;color:var(--text-secondary);"><?=date('D, d M Y')?></span>
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
      <div class="adm-avatar" style="background:linear-gradient(135deg,var(--role-accent),#2F80ED);" title="<?=htmlspecialchars($pharm_row['full_name']??$pharmacistName)?>">
        <?=strtoupper(substr($pharm_row['full_name']??$pharmacistName,0,1))?>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="adm-content">

    <?php include __DIR__.'/phar_tabs/tab_overview.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_inventory.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_prescriptions.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_stock.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_alerts.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_dispensing.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_analytics.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_reports.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_notifications.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_settings.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_profile.php'; ?>
    <?php include __DIR__.'/phar_tabs/tab_system_settings.php'; ?>

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
const TAB_TITLES={overview:'Overview',inventory:'Medicine Inventory',prescriptions:'Prescriptions',
  stock:'Stock Management',alerts:'Alerts',dispensing:'Dispensing History',
  analytics:'Analytics',reports:'Reports',notifications:'Notifications',settings:'Settings',
  profile:'My Profile',system_settings:'System Settings'};
const TAB_ICONS={overview:'fa-house',inventory:'fa-pills',prescriptions:'fa-prescription-bottle-medical',
  stock:'fa-boxes-stacked',alerts:'fa-triangle-exclamation',dispensing:'fa-hand-holding-medical',
  analytics:'fa-chart-bar',reports:'fa-file-export',notifications:'fa-bell',settings:'fa-gear',
  profile:'fa-user-circle',system_settings:'fa-sliders'};

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
  initCharts();
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
function filterByStatus(status,tableId,dataAttr='status'){
  document.querySelectorAll(`#${tableId} tbody tr`).forEach(row=>{
    row.style.display=(status==='all'||row.dataset[dataAttr]===status)?'':'none';
  });
}
function filterByAttr(status,tableId,btn){
  document.querySelectorAll('.filter-tabs .ftab').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  filterByStatus(status,tableId);
}

// ── Modal Helpers ──────────────────────────────────────────
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.addEventListener('click',e=>{if(e.target.classList.contains('modal-bg'))e.target.classList.remove('open');});

// ── AJAX Action Helper with CSRF ──────────────────────────
const CSRF_TOKEN = '<?=e($csrf_token)?>';

async function pharmAction(data){
  data._csrf = CSRF_TOKEN;
  const res = await fetch('/RMU-Medical-Management-System/php/dashboards/pharmacy_actions.php',{
    method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)
  });
  const json = await res.json();
  if (!json.success && json.message && json.message.includes('security token')) {
    showToast('Session expired. Reloading...','error');
    setTimeout(() => location.reload(), 1500);
    return json;
  }
  return json;
}

// ── Client-Side Validation Helpers ────────────────────────
function validateForm(fields) {
  for (const [id, label] of Object.entries(fields)) {
    const el = document.getElementById(id);
    if (!el || !el.value.trim()) {
      showToast('Please fill in: ' + label, 'error');
      if (el) el.focus();
      return false;
    }
  }
  return true;
}
function validateNumber(value, min, label) {
  min = min || 0; label = label || 'Value';
  const n = Number(value);
  if (isNaN(n) || n < min) { showToast(label + ' must be a number >= ' + min, 'error'); return false; }
  return true;
}
function confirmAction(msg) {
  return confirm(msg || 'Are you sure you want to proceed?');
}

// ── Charts ────────────────────────────────────────────────
const weeklyLabels = <?=$weekly_labels?>;
const weeklyData   = <?=$weekly_data?>;
const topMedLabels = <?=$top_med_labels?>;
const topMedData   = <?=$top_med_data?>;
const stockLabels  = <?=$stock_labels?>;
const stockData    = <?=$stock_data?>;
const fulfillLabels= <?=$fulfill_labels?>;
const fulfillData  = <?=$fulfill_data?>;

function initCharts(){
  const isDark=document.documentElement.getAttribute('data-theme')==='dark';
  const gridColor=isDark?'rgba(255,255,255,.08)':'rgba(0,0,0,.07)';
  const textColor=isDark?'#9AAECB':'#5A6A85';

  // Weekly Dispensing Bar
  const wCtx=document.getElementById('chartWeeklyDisp');
  if(wCtx && weeklyData.length){
    new Chart(wCtx,{type:'bar',data:{labels:weeklyLabels,datasets:[{label:'Dispensed',data:weeklyData,backgroundColor:'rgba(39,174,96,.3)',borderColor:'#27AE60',borderWidth:2,borderRadius:8}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1,color:textColor},grid:{color:gridColor}},x:{ticks:{color:textColor},grid:{display:false}}}}});
  }

  // Top Medicines Horizontal Bar
  const tmCtx=document.getElementById('chartTopMeds');
  if(tmCtx && topMedData.length){
    new Chart(tmCtx,{type:'bar',data:{labels:topMedLabels,datasets:[{label:'Units',data:topMedData,backgroundColor:'rgba(47,128,237,.7)',borderRadius:8}]},
    options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{color:textColor},grid:{color:gridColor}},y:{ticks:{color:textColor},grid:{display:false}}}}});
  }

  // Stock Status Doughnut
  const sCtx=document.getElementById('chartStockStatus');
  if(sCtx){
    new Chart(sCtx,{type:'doughnut',data:{labels:stockLabels,datasets:[{data:stockData,backgroundColor:['#27AE60','#F39C12','#E74C3C','#E67E22','#8E44AD'],borderWidth:0,hoverOffset:6}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:textColor,padding:16,font:{size:12}}}}}});
  }

  // Prescription Fulfillment Doughnut
  const fCtx=document.getElementById('chartFulfill');
  if(fCtx){
    new Chart(fCtx,{type:'doughnut',data:{labels:fulfillLabels,datasets:[{data:fulfillData,backgroundColor:['#F39C12','#27AE60','#2980B9','#E74C3C','#8E44AD'],borderWidth:0,hoverOffset:6}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:textColor,padding:16,font:{size:12}}}}}});
  }
}
</script>


<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script></body>
</html>
