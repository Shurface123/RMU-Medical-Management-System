<?php
// ============================================================
// FINANCE DASHBOARD — RMU Medical Sickbay
// ============================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/finance_security.php';

require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'finance_officer';
$today     = date('Y-m-d');
$month_start = date('Y-m-01');
$month_end   = date('Y-m-t');

// ── Finance Staff Profile ─────────────────────────────────
$fs_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT fs.*, u.name, u.email, u.phone, u.profile_image, u.last_login
     FROM finance_staff fs
     JOIN users u ON fs.user_id = u.id
     WHERE fs.user_id = $user_id LIMIT 1"));
if (!$fs_row) {
    $fs_row = [
        'name'           => $_SESSION['user_name'] ?? 'Finance Officer',
        'staff_code'     => 'FIN-000',
        'role_level'     => $user_role,
        'department'     => 'Finance & Revenue',
        'profile_image'  => '',
        'email'          => '',
        'phone'          => '',
    ];
}

// ── Quick stats helper ────────────────────────────────────
function fval($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    return $r ? (mysqli_fetch_row($r)[0] ?? 0) : 0;
}

// ── KPI Stats ────────────────────────────────────────────
$kpi = [];
$kpi['revenue_today']     = (float)fval($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(payment_date)='$today' AND status='Completed'");
$kpi['revenue_month']     = (float)fval($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_date BETWEEN '$month_start' AND '$month_end 23:59:59' AND status='Completed'");
$kpi['pending_inv_count'] = (int)fval($conn,   "SELECT COUNT(*) FROM billing_invoices WHERE status IN ('Pending','Draft')");
$kpi['pending_inv_val']   = (float)fval($conn, "SELECT COALESCE(SUM(balance_due),0) FROM billing_invoices WHERE status IN ('Pending','Draft')");
$kpi['overdue_count']     = (int)fval($conn,   "SELECT COUNT(*) FROM billing_invoices WHERE status='Overdue' OR (due_date < '$today' AND status NOT IN ('Paid','Cancelled','Void','Written Off'))");
$kpi['overdue_val']       = (float)fval($conn, "SELECT COALESCE(SUM(balance_due),0) FROM billing_invoices WHERE status='Overdue' OR (due_date < '$today' AND status NOT IN ('Paid','Cancelled','Void','Written Off'))");
$kpi['paystack_today']    = (int)fval($conn,   "SELECT COUNT(*) FROM paystack_transactions WHERE DATE(created_at)='$today' AND status='Success'");
$kpi['insurance_count']   = (int)fval($conn,   "SELECT COUNT(*) FROM insurance_claims WHERE status IN ('Submitted','Under Review')");
$kpi['insurance_val']     = (float)fval($conn, "SELECT COALESCE(SUM(claim_amount),0) FROM insurance_claims WHERE status IN ('Submitted','Under Review')");
$kpi['outstanding']       = (float)fval($conn, "SELECT COALESCE(SUM(balance_due),0) FROM billing_invoices WHERE status NOT IN ('Paid','Cancelled','Void','Written Off')");
$kpi['waivers_month']     = (int)fval($conn,   "SELECT COUNT(*) FROM payment_waivers WHERE status='Approved' AND approved_at BETWEEN '$month_start' AND '$month_end 23:59:59'");
$kpi['unread_notifs']     = (int)fval($conn,   "SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND is_read=0");

// ── Revenue by Payment Method (current month) ─────────────
$rev_method = [];
$q = mysqli_query($conn, "SELECT payment_method, COALESCE(SUM(amount),0) AS total
     FROM payments WHERE payment_date BETWEEN '$month_start' AND '$month_end 23:59:59' AND status='Completed'
     GROUP BY payment_method");
if ($q) while ($r = mysqli_fetch_assoc($q)) $rev_method[] = $r;

// ── 30-day Revenue Trend ──────────────────────────────────
$trend_labels = $trend_data = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $v = (float)fval($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(payment_date)='$d' AND status='Completed'");
    $trend_labels[] = date('d M', strtotime($d));
    $trend_data[]   = $v;
}

// ── Recent Transactions ───────────────────────────────────
$recent_payments = [];
$q = mysqli_query($conn,
    "SELECT p.*, bi.invoice_number, u.name AS patient_name
     FROM payments p
     JOIN billing_invoices bi ON p.invoice_id = bi.invoice_id
     JOIN patients pt ON p.patient_id = pt.id
     JOIN users u ON pt.user_id = u.id
     ORDER BY p.created_at DESC LIMIT 10");
if ($q) while ($r = mysqli_fetch_assoc($q)) $recent_payments[] = $r;

// ── Overdue Invoices Alert (>7 days) ─────────────────────
$overdue_alerts = [];
$q = mysqli_query($conn,
    "SELECT bi.invoice_number, bi.due_date, bi.balance_due, u.name AS patient_name,
            DATEDIFF('$today', bi.due_date) AS days_overdue
     FROM billing_invoices bi
     JOIN patients pt ON bi.patient_id = pt.id
     JOIN users u ON pt.user_id = u.id
     WHERE (bi.status='Overdue' OR (bi.due_date < '$today' AND bi.status NOT IN ('Paid','Cancelled','Void','Written Off')))
       AND DATEDIFF('$today', bi.due_date) > 7
     ORDER BY days_overdue DESC LIMIT 10");
if ($q) while ($r = mysqli_fetch_assoc($q)) $overdue_alerts[] = $r;

// ── Active Tab ────────────────────────────────────────────
$active_tab = htmlspecialchars($_GET['tab'] ?? 'overview');

// ── Chart JSON ────────────────────────────────────────────
$trend_labels_j = json_encode($trend_labels);
$trend_data_j   = json_encode($trend_data);
$method_labels_j = json_encode(array_column($rev_method, 'payment_method'));
$method_data_j   = json_encode(array_map('floatval', array_column($rev_method, 'total')));
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Finance & Revenue — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/notifications.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ── Finance Role Accent: Emerald Green/Gold ── */
:root{
  --role-accent:#1a9e6e;
  --role-accent-dark:#14795c;
  --role-accent-light:#e6f7f2;
  --role-gold:#d4a017;
  --role-gold-light:#fdf6e3;
}
[data-theme="dark"]{
  --role-accent-light:#0d2e22;
  --role-gold-light:#2e2408;
}

/* ── Finance Hero Banner ── */
.fin-hero{
  background:linear-gradient(135deg,#0d3b2e 0%,#1a9e6e 55%,#d4a017 100%);
  color:#fff;border-radius:var(--radius-lg);
  padding:2.4rem 3rem;margin-bottom:2rem;
  display:flex;align-items:center;gap:2rem;flex-wrap:wrap;
  position:relative;overflow:hidden;box-shadow:var(--shadow-lg);
}
.fin-hero::after{content:'';position:absolute;right:-50px;top:-50px;
  width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.05);}
.fin-hero::before{content:'';position:absolute;right:60px;bottom:-70px;
  width:280px;height:280px;border-radius:50%;background:rgba(212,160,23,.08);}
.fin-hero-icon{width:80px;height:80px;border-radius:50%;
  background:rgba(255,255,255,.15);backdrop-filter:blur(8px);
  border:3px solid rgba(255,255,255,.3);
  display:flex;align-items:center;justify-content:center;
  font-size:2.4rem;flex-shrink:0;}
.fin-hero-info h2{font-size:1.9rem;font-weight:700;margin:0 0 .3rem;}
.fin-hero-info p{margin:0;opacity:.85;font-size:1rem;}
.fin-hero-badge{
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.28);
  border-radius:50px;padding:.3rem 1rem;font-size:1.1rem;
  display:inline-flex;align-items:center;gap:.5rem;margin:.3rem .3rem 0 0;
}

/* ── KPI Cards ── */
.fin-kpi-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(210px,1fr));
  gap:1.6rem;margin-bottom:2rem;
}
.fin-kpi-card{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--radius-md);padding:2rem 1.8rem;
  box-shadow:var(--shadow-sm);transition:var(--transition);
  cursor:pointer;position:relative;overflow:hidden;
}
.fin-kpi-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-hover);}
.fin-kpi-card::after{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  opacity:0;transition:opacity .28s ease;
}
.fin-kpi-card:hover::after{opacity:1;}
.fin-kpi-card.green::after{background:linear-gradient(90deg,#1a9e6e,#27AE60);}
.fin-kpi-card.blue::after{background:linear-gradient(90deg,#2F80ED,#56CCF2);}
.fin-kpi-card.orange::after{background:linear-gradient(90deg,#E67E22,#F39C12);}
.fin-kpi-card.red::after{background:linear-gradient(90deg,#E74C3C,#EC7063);}
.fin-kpi-card.gold::after{background:linear-gradient(90deg,#d4a017,#f0c040);}
.fin-kpi-card.purple::after{background:linear-gradient(90deg,#9B59B6,#C39BD3);}
.fin-kpi-icon{
  width:48px;height:48px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:2rem;color:#fff;margin-bottom:1.2rem;
}
.fin-kpi-icon.green{background:linear-gradient(135deg,#1a9e6e,#27AE60);}
.fin-kpi-icon.blue{background:linear-gradient(135deg,#2F80ED,#56CCF2);}
.fin-kpi-icon.orange{background:linear-gradient(135deg,#E67E22,#F39C12);}
.fin-kpi-icon.red{background:linear-gradient(135deg,#E74C3C,#EC7063);}
.fin-kpi-icon.gold{background:linear-gradient(135deg,#c8920a,#d4a017);}
.fin-kpi-icon.purple{background:linear-gradient(135deg,#7D3C98,#9B59B6);}
.fin-kpi-label{font-size:1.15rem;color:var(--text-secondary);font-weight:500;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem;}
.fin-kpi-value{font-size:2.8rem;font-weight:800;color:var(--text-primary);line-height:1;margin-bottom:.6rem;}
.fin-kpi-sub{font-size:1.15rem;color:var(--text-muted);border-top:1px solid var(--border);padding-top:.8rem;}

/* ── Dash Section Tabs ── */
.dash-section{display:none;animation:finFadeIn .3s ease;}
.dash-section.active{display:block;}
@keyframes finFadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* ── Overdue Alert Panel ── */
.overdue-panel{background:linear-gradient(135deg,rgba(231,76,60,.06),rgba(231,76,60,.02));
  border:1.5px solid rgba(231,76,60,.18);border-radius:var(--radius-md);
  padding:1.8rem 2rem;margin-bottom:2rem;}
.overdue-panel-title{font-size:1.5rem;font-weight:700;color:var(--danger);
  display:flex;align-items:center;gap:.8rem;margin-bottom:1.2rem;}
.overdue-item{display:flex;align-items:center;gap:1rem;padding:.8rem 0;
  border-bottom:1px solid rgba(231,76,60,.1);}
.overdue-item:last-child{border:none;}
.overdue-days{background:var(--danger);color:#fff;border-radius:8px;
  padding:.3rem .8rem;font-size:1.1rem;font-weight:700;white-space:nowrap;}

/* ── Finance Badges for invoice status ── */
.badge-fin{display:inline-flex;align-items:center;gap:.3rem;
  padding:.35rem .9rem;border-radius:20px;font-size:1.1rem;font-weight:600;white-space:nowrap;}
.badge-draft{background:#f0f0f0;color:#666;}
[data-theme="dark"] .badge-draft{background:#333;color:#aaa;}
.badge-pending{background:var(--info-light);color:var(--info);}
.badge-partial{background:var(--warning-light);color:var(--warning);}
.badge-paid{background:var(--success-light);color:var(--success);}
.badge-overdue-fin{background:var(--danger-light);color:var(--danger);}
.badge-cancelled{background:#e0e0e0;color:#444;}
[data-theme="dark"] .badge-cancelled{background:#2a2a2a;color:#888;}
.badge-void{background:#ede0f0;color:#7D3C98;}
.badge-refunded{background:#f3e6ff;color:#7D3C98;}

/* ── Quick Action Tiles (Finance) ── */
.fin-quick-actions{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1.2rem;margin-bottom:2rem;}
.fin-action-tile{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:.8rem;padding:1.8rem 1rem;text-align:center;
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--radius-md);font-size:1.3rem;font-weight:600;
  color:var(--text-primary);transition:var(--transition);
  box-shadow:var(--shadow-sm);cursor:pointer;
}
.fin-action-tile i{
  width:48px;height:48px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:2rem;color:#fff;
}
.fin-action-tile:hover{transform:translateY(-4px);box-shadow:var(--shadow-hover);border-color:var(--role-accent);color:var(--role-accent);}

/* ── Filter Row ── */
.fin-filter-row{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:center;}
.fin-filter-row select,.fin-filter-row input{
  padding:.9rem 1.2rem;border:1.5px solid var(--border);border-radius:10px;
  background:var(--surface);color:var(--text-primary);
  font-family:Poppins,sans-serif;font-size:1.3rem;outline:none;
  transition:var(--transition);
}
.fin-filter-row select:focus,.fin-filter-row input:focus{border-color:var(--role-accent);}

/* ── Line Item Row ── */
.line-item-row{display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr auto;gap:.8rem;align-items:center;padding:.8rem 0;border-bottom:1px solid var(--border);}
.line-item-row:last-child{border:none;}
@media(max-width:768px){.line-item-row{grid-template-columns:1fr 1fr;}}

/* ── Invoice Summary Box ── */
.inv-summary{background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.8rem;margin-top:1.5rem;}
.inv-summary-row{display:flex;justify-content:space-between;padding:.6rem 0;font-size:1.35rem;}
.inv-summary-row.total{border-top:2px solid var(--border);margin-top:.5rem;font-weight:800;font-size:1.6rem;color:var(--role-accent);}

/* ── Progress Bar ── */
.fin-progress{height:8px;background:var(--border);border-radius:10px;overflow:hidden;margin-top:.5rem;}
.fin-progress-fill{height:100%;border-radius:10px;transition:width .6s ease;}
.fin-progress-fill.green{background:linear-gradient(90deg,#1a9e6e,#27AE60);}
.fin-progress-fill.amber{background:linear-gradient(90deg,#E67E22,#F39C12);}
.fin-progress-fill.red{background:linear-gradient(90deg,#E74C3C,#EC7063);}

/* ── Paystack Status Badge ── */
.ps-success{background:#e6f7f2;color:#1a9e6e;}
.ps-failed{background:var(--danger-light);color:var(--danger);}
.ps-pending{background:var(--warning-light);color:var(--warning);}

/* Responsive overrides */
@media(max-width:991px){
  .adm-sidebar{transform:translateX(-100%);z-index:1001;}
  .adm-sidebar.active{transform:translateX(0);}
  .adm-main{margin-left:0;max-width:100vw;}
  .adm-menu-toggle{display:flex!important;}
  .adm-overlay.active{display:block;}
}
@media(max-width:768px){
  .fin-kpi-grid{grid-template-columns:repeat(2,1fr);}
  .fin-hero{padding:1.8rem 1.5rem;}
}
@media(max-width:480px){
  .fin-kpi-grid{grid-template-columns:1fr;}
}
</style>
<!-- Phase 4 Hooks --><link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css"><meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>"></head>
<body>
<div class="adm-layout" style="display:flex;">

<!-- ════ SIDEBAR ════ -->
<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-sidebar-brand">
    <div class="adm-sidebar-brand-icon"><i class="fas fa-coins"></i></div>
    <div class="adm-sidebar-brand-text">
      <h2>RMU Sickbay</h2>
      <span>Finance & Revenue</span>
    </div>
  </div>

  <nav class="adm-sidebar-nav">
    <span class="adm-nav-section-label">Main</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='overview')?'active':''?>" onclick="showTab('overview',this)"><i class="fas fa-gauge-high"></i><span>Overview</span></a>

    <span class="adm-nav-section-label">Billing</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='invoices')?'active':''?>" onclick="showTab('invoices',this)">
      <i class="fas fa-file-invoice-dollar"></i><span>Invoices</span>
      <?php if($kpi['pending_inv_count']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$kpi['pending_inv_count']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='payments')?'active':''?>" onclick="showTab('payments',this)"><i class="fas fa-money-bill-transfer"></i><span>Payments</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='paystack')?'active':''?>" onclick="showTab('paystack',this)"><i class="fas fa-credit-card"></i><span>Paystack Txns</span></a>

    <span class="adm-nav-section-label">Claims & Waivers</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='insurance')?'active':''?>" onclick="showTab('insurance',this)">
      <i class="fas fa-shield-halved"></i><span>Insurance Claims</span>
      <?php if($kpi['insurance_count']>0):?><span class="adm-badge adm-badge-info" style="margin-left:auto;font-size:1rem;"><?=$kpi['insurance_count']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='waivers')?'active':''?>" onclick="showTab('waivers',this)"><i class="fas fa-percent"></i><span>Waivers & Discounts</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='refunds')?'active':''?>" onclick="showTab('refunds',this)"><i class="fas fa-rotate-left"></i><span>Refunds</span></a>

    <span class="adm-nav-section-label">Finance Ops</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='reconciliation')?'active':''?>" onclick="showTab('reconciliation',this)"><i class="fas fa-scale-balanced"></i><span>Reconciliation</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='budget')?'active':''?>" onclick="showTab('budget',this)"><i class="fas fa-chart-pie"></i><span>Budget</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='fee_schedule')?'active':''?>" onclick="showTab('fee_schedule',this)"><i class="fas fa-list-ol"></i><span>Fee Schedule</span></a>

    <span class="adm-nav-section-label">Reports</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='analytics')?'active':''?>" onclick="showTab('analytics',this)"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='reports')?'active':''?>" onclick="showTab('reports',this)"><i class="fas fa-file-lines"></i><span>Reports</span></a>

    <span class="adm-nav-section-label">Account</span>
    <a href="#" class="adm-nav-item <?=($active_tab==='profile')?'active':''?>" onclick="showTab('profile',this)"><i class="fas fa-user-tie"></i><span>My Profile</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='settings')?'active':''?>" onclick="showTab('settings',this)"><i class="fas fa-gear"></i><span>Settings</span></a>
  </nav>

  <div class="adm-sidebar-footer">
    <a href="/RMU-Medical-Management-System/php/logout.php" class="adm-logout-btn">
      <i class="fas fa-right-from-bracket"></i><span>Logout</span>
    </a>
  </div>
</aside>
<div class="adm-overlay" id="admOverlay"></div>

<!-- ════ MAIN ════ -->
<main class="adm-main">

  <!-- TOPBAR -->
  <div class="adm-topbar">
    <div class="adm-topbar-left">
      <button class="adm-menu-toggle" id="menuToggle" style="display:none;"><i class="fas fa-bars"></i></button>
      <span class="adm-page-title" id="pageTitle">
        <i class="fas fa-gauge-high" style="color:var(--role-accent);margin-right:.6rem;" id="pageTitleIcon"></i>
        <span id="pageTitleText">Overview</span>
      </span>
    </div>
    <div class="adm-topbar-right">
      <span class="adm-topbar-datetime"><i class="fas fa-calendar-day"></i><?=date('D, d M Y')?></span>
      <div style="position:relative;">
        <button id="rmuBellBtn" class="adm-notif-btn <?=$kpi['unread_notifs']>0?'has-unread':''?>" title="Notifications">
          <i class="fas fa-bell"></i>
          <span id="rmuBellCount" style="display:<?=$kpi['unread_notifs']>0?'flex':'none'?>"><?=$kpi['unread_notifs']>99?'99+':$kpi['unread_notifs']?></span>
        </button>
      </div>
      <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
      <div class="adm-avatar" style="background:linear-gradient(135deg,var(--role-accent),var(--role-gold));" title="<?=htmlspecialchars($fs_row['name'])?>">
        <?=strtoupper(substr($fs_row['name'],0,1))?>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="adm-content">
    <?php include __DIR__.'/finance_tabs/tab_overview.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_invoices.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_payments.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_paystack.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_insurance.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_waivers.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_refunds.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_reconciliation.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_budget.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_fee_schedule.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_analytics.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_reports.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_profile.php'; ?>
    <?php include __DIR__.'/finance_tabs/tab_settings.php'; ?>
  </div>
</main>
</div><!-- /layout -->

<!-- Toast Container -->
<div id="toastWrap" style="position:fixed;bottom:2rem;right:2rem;z-index:9999;display:flex;flex-direction:column;gap:.7rem;"></div>
<script src="/RMU-Medical-Management-System/js/notifications.js"></script>
<script src="/RMU-Medical-Management-System/php/includes/BroadcastReceiver.js"></script>

<script>
// ─── Chart Data (PHP injected) ─────────────────────────────
const trendLabels = <?=$trend_labels_j?>;
const trendData   = <?=$trend_data_j?>;
const methodLabels = <?=$method_labels_j?>;
const methodData   = <?=$method_data_j?>;

// ─── Tab Navigation ─────────────────────────────────────────
const TAB_TITLES = {
  overview:'Overview', invoices:'Invoice Management', payments:'Payment Processing',
  paystack:'Paystack Transactions', insurance:'Insurance Claims', waivers:'Waivers & Discounts',
  refunds:'Refunds', reconciliation:'Daily Reconciliation', budget:'Budget Management',
  fee_schedule:'Fee Schedule', analytics:'Revenue Analytics', reports:'Financial Reports',
  profile:'My Profile', settings:'Finance Settings'
};
const TAB_ICONS = {
  overview:'fa-gauge-high', invoices:'fa-file-invoice-dollar', payments:'fa-money-bill-transfer',
  paystack:'fa-credit-card', insurance:'fa-shield-halved', waivers:'fa-percent',
  refunds:'fa-rotate-left', reconciliation:'fa-scale-balanced', budget:'fa-chart-pie',
  fee_schedule:'fa-list-ol', analytics:'fa-chart-line', reports:'fa-file-lines',
  profile:'fa-user-tie', settings:'fa-gear'
};

function showTab(tab, el) {
  document.querySelectorAll('.dash-section').forEach(s => s.classList.remove('active'));
  const sec = document.getElementById('sec-' + tab);
  if (sec) sec.classList.add('active');
  document.querySelectorAll('.adm-nav-item').forEach(a => a.classList.remove('active'));
  if (el) el.classList.add('active');
  document.getElementById('pageTitleText').textContent = TAB_TITLES[tab] || tab;
  const icon = document.getElementById('pageTitleIcon');
  if (icon && TAB_ICONS[tab]) icon.className = `fas ${TAB_ICONS[tab]}`;
  document.getElementById('admSidebar').classList.remove('active');
  document.getElementById('admOverlay').classList.remove('active');
  history.replaceState(null,'','?tab='+tab);
}

// ─── Init ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const initTab = '<?=$active_tab?>';
  const initEl  = document.querySelector(`.adm-nav-item[onclick*="${initTab}"]`);
  showTab(initTab, initEl);
  initCharts();
  if (typeof BroadcastReceiver !== 'undefined') {
    window.rmuBroadcasts = new BroadcastReceiver(<?=$_SESSION['user_id']?>);
  }
});

// ─── Sidebar Toggle ─────────────────────────────────────────
document.getElementById('menuToggle')?.addEventListener('click', () => {
  document.getElementById('admSidebar').classList.toggle('active');
  document.getElementById('admOverlay').classList.toggle('active');
});
document.getElementById('admOverlay')?.addEventListener('click', () => {
  document.getElementById('admSidebar').classList.remove('active');
  document.getElementById('admOverlay').classList.remove('active');
});

// ─── Theme ────────────────────────────────────────────────
function applyTheme(t) {
  document.documentElement.setAttribute('data-theme', t);
  localStorage.setItem('rmu_theme', t);
  document.getElementById('themeIcon').className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
}
applyTheme(localStorage.getItem('rmu_theme') || 'light');
document.getElementById('themeToggle')?.addEventListener('click', () => {
  applyTheme(document.documentElement.getAttribute('data-theme')==='dark' ? 'light' : 'dark');
});

// ─── Toast ────────────────────────────────────────────────
function toast(msg, type='success') {
  const colors = {success:'#1a9e6e',danger:'#E74C3C',warning:'#F39C12',info:'#2F80ED'};
  const t = document.createElement('div');
  t.style.cssText = `background:${colors[type]||colors.success};color:#fff;padding:1.2rem 2rem;border-radius:12px;font-family:Poppins,sans-serif;font-size:1.35rem;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.2);max-width:360px;animation:finFadeIn .3s ease;`;
  t.textContent = msg;
  document.getElementById('toastWrap').appendChild(t);
  setTimeout(() => t.remove(), 4500);
}

// ─── Modal Helpers ────────────────────────────────────────
function openModal(id)  { document.getElementById(id)?.classList.add('active'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('active'); }
document.addEventListener('click', e => { if(e.target.classList.contains('adm-modal')) e.target.classList.remove('active'); });

// ─── AJAX Helper ──────────────────────────────────────────
// CSRF Token embedded from security module
window.csrfToken = "<?= $_SESSION['csrf_token'] ?>";

async function finAction(data) {
  const res = await fetch('/RMU-Medical-Management-System/php/finance/finance_actions.php', {
    method:'POST', 
    headers:{
        'Content-Type':'application/json',
        'X-CSRF-Token': window.csrfToken
    }, 
    body:JSON.stringify(data)
  });
  return res.json();
}

// ─── Filter Table ─────────────────────────────────────────
function filterTable(inputId, tableId) {
  const val = document.getElementById(inputId)?.value.toUpperCase() || '';
  document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
    row.style.display = row.textContent.toUpperCase().includes(val) ? '' : 'none';
  });
}

// ─── Charts ───────────────────────────────────────────────
function initCharts() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridCol  = isDark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
  const textCol  = isDark ? '#9AAECB' : '#5A6A85';

  // Revenue Trend (30 days)
  const tCtx = document.getElementById('chartRevTrend');
  if (tCtx && trendLabels.length) {
    new Chart(tCtx, {
      type:'line',
      data:{
        labels:trendLabels,
        datasets:[{
          label:'Revenue (GHS)',
          data:trendData,
          borderColor:'#1a9e6e',
          backgroundColor:'rgba(26,158,110,.12)',
          borderWidth:2.5,
          fill:true,
          tension:.4,
          pointBackgroundColor:'#1a9e6e',
          pointRadius:3,
          pointHoverRadius:6
        }]
      },
      options:{responsive:true,maintainAspectRatio:false,
        plugins:{legend:{display:false}},
        scales:{
          y:{beginAtZero:true,ticks:{color:textCol,callback:v=>'GHS '+v.toLocaleString()},grid:{color:gridCol}},
          x:{ticks:{color:textCol,maxTicksLimit:10},grid:{display:false}}
        }
      }
    });
  }

  // Revenue by Payment Method
  const mCtx = document.getElementById('chartRevMethod');
  if (mCtx && methodLabels.length) {
    new Chart(mCtx, {
      type:'doughnut',
      data:{
        labels:methodLabels,
        datasets:[{
          data:methodData,
          backgroundColor:['#1a9e6e','#2F80ED','#d4a017','#9B59B6','#E74C3C','#56CCF2'],
          borderWidth:0,hoverOffset:8
        }]
      },
      options:{responsive:true,maintainAspectRatio:false,
        plugins:{legend:{position:'bottom',labels:{color:textCol,padding:16,font:{size:12}}}}
      }
    });
  }
}

// Format GHS currency
function ghs(n) { return 'GHS ' + parseFloat(n||0).toLocaleString('en-GH',{minimumFractionDigits:2}); }
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script></body>
</html>
