<?php
// ============================================================
// PHARMACY DASHBOARD — Updated Phase 4
// Uses: admin-dashboard.css, medicine_inventory view, new schema
// ============================================================
session_start();
require_once '../db_conn.php';

$role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
if ($role !== 'pharmacist') {
    header('Location: ../index.php?error=Unauthorized'); exit;
}
date_default_timezone_set('Africa/Accra');

$userId        = (int)$_SESSION['user_id'];
$pharmacyName  = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Pharmacist';
$today         = date('Y-m-d');

// ── POST: Dispense prescription ───────────────────────────
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='dispense') {
    $rxId = (int)$_POST['rx_id'];
    $rx   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM prescriptions WHERE id=$rxId LIMIT 1"));
    if ($rx && $rx['status']==='Pending') {
        mysqli_query($conn,"UPDATE prescriptions SET status='Dispensed',updated_at=NOW() WHERE id=$rxId");
        // Deduct stock
        $med = mysqli_real_escape_string($conn,$rx['medication_name']);
        mysqli_query($conn,"UPDATE medicines SET stock_quantity=GREATEST(0,stock_quantity-{$rx['quantity']}) WHERE medicine_name='$med' LIMIT 1");
        // Notify patient
        $patUid=(int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id FROM patients WHERE id={$rx['patient_id']} LIMIT 1"))['user_id']??0);
        if($patUid) mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,created_at) VALUES($patUid,'patient','prescription','Prescription Ready','Your prescription for {$rx['medication_name']} has been dispensed. Please collect it from the pharmacy.',0,'prescriptions',NOW())");
        $msg = 'Prescription dispensed successfully!';
    } else { $err = 'Cannot dispense — prescription not found or already processed.'; }
}

// ── Stats ─────────────────────────────────────────────────
function qv($c,$s){ $r=mysqli_query($c,$s); return $r ? (mysqli_fetch_row($r)[0]??0) : 0; }
$stats = [
    'total_medicines' => qv($conn,"SELECT COUNT(*) FROM medicines"),
    'in_stock'        => qv($conn,"SELECT COUNT(*) FROM medicines WHERE stock_quantity>reorder_level"),
    'low_stock'       => qv($conn,"SELECT COUNT(*) FROM medicines WHERE stock_quantity>0 AND stock_quantity<=reorder_level"),
    'out_of_stock'    => qv($conn,"SELECT COUNT(*) FROM medicines WHERE stock_quantity=0"),
    'expiring_soon'   => qv($conn,"SELECT COUNT(*) FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)"),
    'pending_rx'      => qv($conn,"SELECT COUNT(*) FROM prescriptions WHERE status='Pending'"),
    'dispensed_today' => qv($conn,"SELECT COUNT(*) FROM prescriptions WHERE status='Dispensed' AND DATE(updated_at)='$today'"),
];
$unread = (int)qv($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$userId AND is_read=0");

// ── Data fetches ──────────────────────────────────────────
// Pending prescriptions
$pending_rx = [];
$q = mysqli_query($conn,"SELECT pr.*, up.name AS patient_name, ud.name AS doctor_name, p.patient_id AS p_ref
  FROM prescriptions pr
  JOIN patients p ON pr.patient_id=p.id JOIN users up ON p.user_id=up.id
  JOIN doctors d ON pr.doctor_id=d.id JOIN users ud ON d.user_id=ud.id
  WHERE pr.status='Pending' ORDER BY pr.prescription_date DESC LIMIT 50");
if($q) while($r=mysqli_fetch_assoc($q)) $pending_rx[]=$r;

// Medicine inventory (from view)
$medicines = [];
$q = mysqli_query($conn,"SELECT * FROM medicine_inventory ORDER BY stock_status ASC, medicine_name ASC LIMIT 200");
if($q) while($r=mysqli_fetch_assoc($q)) $medicines[]=$r;

// Recent dispenses
$recent_disp = [];
$q = mysqli_query($conn,"SELECT pr.*, up.name AS patient_name
  FROM prescriptions pr JOIN patients p ON pr.patient_id=p.id JOIN users up ON p.user_id=up.id
  WHERE pr.status='Dispensed' AND DATE(pr.updated_at)='$today'
  ORDER BY pr.updated_at DESC LIMIT 20");
if($q) while($r=mysqli_fetch_assoc($q)) $recent_disp[]=$r;
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
<style>
:root{--role-accent:#27AE60;}
[data-theme="dark"]{--role-accent-light:#0d2b19;}
.filter-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.2rem;}
.ftab{padding:.55rem 1.2rem;border-radius:20px;font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition);}
.ftab.active,.ftab:hover{background:var(--role-accent);color:#fff;border-color:var(--role-accent);}
.adm-table-wrap{overflow-x:auto;border-radius:var(--radius-md);}
.adm-table{width:100%;border-collapse:collapse;font-size:1.3rem;}
.adm-table th{background:var(--surface-2);padding:1.2rem 1.4rem;text-align:left;font-weight:600;color:var(--text-secondary);font-size:1.1rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1.5px solid var(--border);}
.adm-table td{padding:1.2rem 1.4rem;border-bottom:1px solid var(--border);vertical-align:middle;}
.adm-table tr:last-child td{border:none;}
.adm-table tr:hover td{background:var(--surface-2);}
.tab-section{display:none;animation:fadeIn .3s ease;}
.tab-section.active{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>
<body>
<div class="adm-layout">

<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-sidebar-brand">
    <div class="adm-brand-icon"><i class="fas fa-prescription-bottle"></i></div>
    <div class="adm-brand-text"><span class="adm-brand-name">RMU Sickbay</span><span class="adm-brand-role">Pharmacy Portal</span></div>
  </div>
  <nav class="adm-nav" style="padding:1.5rem 1rem;">
    <div class="adm-nav-label">Main</div>
    <a href="#" class="adm-nav-item active" onclick="showSec('overview',this)"><i class="fas fa-house"></i><span>Overview</span></a>
    <a href="#" class="adm-nav-item" onclick="showSec('prescriptions',this)">
      <i class="fas fa-prescription-bottle-medical"></i><span>Prescriptions</span>
      <?php if($stats['pending_rx']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['pending_rx']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item" onclick="showSec('inventory',this)">
      <i class="fas fa-pills"></i><span>Inventory</span>
      <?php if($stats['low_stock']>0||$stats['expiring_soon']>0):?><span class="adm-badge adm-badge-danger" style="margin-left:auto;font-size:1rem;"><?=$stats['low_stock']+$stats['expiring_soon']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item" onclick="showSec('dispensed',this)"><i class="fas fa-check-circle"></i><span>Dispensed Today</span></a>
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
      <span class="adm-page-title" id="pgTitle"><i class="fas fa-house" style="color:var(--role-accent);margin-right:.6rem;"></i><span id="pgTitleText">Overview</span></span>
    </div>
    <div class="adm-topbar-right">
      <span style="font-size:1.2rem;color:var(--text-secondary);"><?=date('D, d M Y')?></span>
      <div style="position:relative;"><button class="adm-notif-btn"><i class="fas fa-bell"></i><?php if($unread>0):?><span style="position:absolute;top:-4px;right:-4px;min-width:16px;height:16px;background:var(--danger);color:#fff;border-radius:50%;font-size:.9rem;font-weight:700;display:flex;align-items:center;justify-content:center;"><?=$unread?></span><?php endif;?></button></div>
      <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
      <div class="adm-avatar" style="background:linear-gradient(135deg,#27AE60,#2F80ED);"><?=strtoupper(substr($pharmacyName,0,1))?></div>
    </div>
  </div>

  <div class="adm-content">
    <?php if($msg):?><div style="background:var(--success-light);color:var(--success);border-left:4px solid var(--success);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
    <?php if($err):?><div style="background:var(--danger-light);color:var(--danger);border-left:4px solid var(--danger);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;"><i class="fas fa-exclamation-circle"></i> <?=htmlspecialchars($err)?></div><?php endif;?>

    <!-- OVERVIEW -->
    <div id="sec-overview" class="tab-section active">
      <div style="margin-bottom:2rem;">
        <h2 style="font-size:2rem;font-weight:700;">Good <?=date('H')<12?'Morning':( date('H')<17?'Afternoon':'Evening')?>, <?=htmlspecialchars(explode(' ',$pharmacyName)[0])?> 👋</h2>
        <p style="color:var(--text-muted);font-size:1.3rem;"><?=date('l, d F Y')?> &middot; <?=date('g:i A')?></p>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:2rem;">
        <?php foreach([
          ['fa-pills','Total Medicines',$stats['total_medicines'],'blue'],
          ['fa-circle-check','In Stock',$stats['in_stock'],'green'],
          ['fa-triangle-exclamation','Low Stock',$stats['low_stock'],'orange'],
          ['fa-circle-xmark','Out of Stock',$stats['out_of_stock'],'red'],
          ['fa-clock','Expiring Soon',$stats['expiring_soon'],'orange'],
          ['fa-prescription','Pending Rx',$stats['pending_rx'],'blue'],
          ['fa-check-double','Dispensed Today',$stats['dispensed_today'],'green'],
        ] as [$ic,$lbl,$val,$col]):?>
        <div class="adm-card" style="text-align:center;padding:1.5rem;margin:0;">
          <div style="font-size:2.5rem;font-weight:800;color:var(--<?=$col==='blue'?'primary':($col==='green'?'success':($col==='red'?'danger':'warning'))?>);"><?=$val?></div>
          <div style="font-size:1.1rem;color:var(--text-muted);margin-top:.3rem;"><i class="fas <?=$ic?>" style="margin-right:.3rem;"></i><?=$lbl?></div>
        </div>
        <?php endforeach;?>
      </div>
      <?php if($stats['low_stock']>0||$stats['out_of_stock']>0||$stats['expiring_soon']>0):?>
      <div style="background:var(--danger-light);border-left:4px solid var(--danger);border-radius:0 12px 12px 0;padding:1.2rem 1.6rem;margin-bottom:1.5rem;font-size:1.3rem;color:var(--danger);">
        <i class="fas fa-triangle-exclamation"></i>
        <strong><?=$stats['out_of_stock']?> out of stock</strong>, <strong><?=$stats['low_stock']?> low stock</strong>, <strong><?=$stats['expiring_soon']?> expiring</strong> — review inventory before prescribing.
      </div>
      <?php endif;?>
    </div>

    <!-- PRESCRIPTIONS -->
    <div id="sec-prescriptions" class="tab-section">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <h2 style="font-size:1.8rem;font-weight:700;"><i class="fas fa-prescription-bottle-medical" style="color:var(--role-accent);margin-right:.6rem;"></i>Pending Prescriptions</h2>
        <div class="adm-search-wrap"><i class="fas fa-search"></i><input type="text" class="adm-search-input" id="rxSearch" placeholder="Search patient or medicine…" oninput="filterTbl(this.value,'rxTable')"></div>
      </div>
      <div class="adm-card">
        <div class="adm-table-wrap">
          <table class="adm-table" id="rxTable">
            <thead><tr><th>Rx ID</th><th>Patient</th><th>Doctor</th><th>Medicine</th><th>Dosage</th><th>Qty</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php if(empty($pending_rx)):?>
              <tr><td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No pending prescriptions.</td></tr>
            <?php else: foreach($pending_rx as $rx):
              // Check stock
              $med_stock = mysqli_fetch_assoc(mysqli_query($conn,"SELECT stock_quantity,stock_status FROM medicine_inventory WHERE medicine_name='".mysqli_real_escape_string($conn,$rx['medication_name'])."' LIMIT 1"));
              $canDisp = ($med_stock['stock_quantity']??0) >= ($rx['quantity']??1);
            ?>
            <tr>
              <td><code><?=htmlspecialchars($rx['prescription_id']??'#'.$rx['id'])?></code></td>
              <td><strong><?=htmlspecialchars($rx['patient_name'])?></strong><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($rx['p_ref']??'')?></span></td>
              <td>Dr. <?=htmlspecialchars($rx['doctor_name']??'')?></td>
              <td>
                <strong><?=htmlspecialchars($rx['medication_name'])?></strong><br>
                <?php if($med_stock): $ms_sc=['In Stock'=>'success','Low Stock'=>'warning','Out of Stock'=>'danger','Expiring Soon'=>'warning'][$med_stock['stock_status']]??'info'; ?>
                <span class="adm-badge adm-badge-<?=$ms_sc?>" style="font-size:.9rem;"><?=$med_stock['stock_status']; ?> (<?=$med_stock['stock_quantity']?>)</span><?php endif; ?>
              </td>
              <td><?=htmlspecialchars($rx['dosage']??'')?> &middot; <?=htmlspecialchars($rx['frequency']??'')?></td>
              <td><?=$rx['quantity']??1?></td>
              <td><?=date('d M Y',strtotime($rx['prescription_date']))?></td>
              <td>
                <?php if($canDisp):?>
                <form method="POST" onsubmit="return confirm('Dispense this prescription?')">
                  <input type="hidden" name="action" value="dispense"><input type="hidden" name="rx_id" value="<?=$rx['id']?>">
                  <button type="submit" class="adm-btn adm-btn-success adm-btn-sm"><i class="fas fa-check"></i> Dispense</button>
                </form>
                <?php else:?>
                <span class="adm-badge adm-badge-danger" title="Insufficient stock">Out of Stock</span>
                <?php endif;?>
              </td>
            </tr>
            <?php endforeach; endif;?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- INVENTORY -->
    <div id="sec-inventory" class="tab-section">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;flex-wrap:wrap;gap:1rem;">
        <h2 style="font-size:1.8rem;font-weight:700;"><i class="fas fa-pills" style="color:var(--role-accent);margin-right:.6rem;"></i>Medicine Inventory</h2>
        <div class="adm-search-wrap"><i class="fas fa-search"></i><input type="text" class="adm-search-input" id="medSearch" placeholder="Search medicine…" oninput="filterTbl(this.value,'medTable')"></div>
      </div>
      <div class="filter-tabs">
        <button class="ftab active" onclick="filterMed('all',this)">All</button>
        <button class="ftab" onclick="filterMed('In Stock',this)">In Stock</button>
        <button class="ftab" onclick="filterMed('Low Stock',this)">Low Stock</button>
        <button class="ftab" onclick="filterMed('Out of Stock',this)">Out of Stock</button>
        <button class="ftab" onclick="filterMed('Expiring Soon',this)">Expiring Soon</button>
      </div>
      <div class="adm-card">
        <div class="adm-table-wrap">
          <table class="adm-table" id="medTable">
            <thead><tr><th>Medicine</th><th>Category</th><th>Stock Qty</th><th>Unit Price</th><th>Expiry</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php if(empty($medicines)):?>
              <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No inventory data.</td></tr>
            <?php else: foreach($medicines as $med):
              $sc_map=['In Stock'=>'success','Out of Stock'=>'danger','Low Stock'=>'warning','Expiring Soon'=>'warning'];
              $sc=$sc_map[$med['stock_status']]??'info';
            ?>
            <tr data-medstatus="<?=htmlspecialchars($med['stock_status']??'')?>">
              <td><strong><?=htmlspecialchars($med['medicine_name'])?></strong><?php if($med['generic_name']??''):?><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($med['generic_name'])?></span><?php endif;?></td>
              <td><?=htmlspecialchars($med['category']??'—')?></td>
              <td><strong style="font-size:1.5rem;"><?=$med['stock_quantity']?></strong><span style="font-size:1rem;color:var(--text-muted);">/<?=$med['reorder_level']?> reorder</span></td>
              <td>GH₵<?=number_format($med['unit_price'],2)?></td>
              <td><?=$med['expiry_date']?date('d M Y',strtotime($med['expiry_date'])):'—'?></td>
              <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$med['stock_status']??'Unknown'?></span></td>
              <td>
                <button onclick="openRestockModal(<?=$med['id']?>,<?=json_encode($med['medicine_name'])?>)" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-plus"></i> Restock</button>
              </td>
            </tr>
            <?php endforeach; endif;?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- DISPENSED TODAY -->
    <div id="sec-dispensed" class="tab-section">
      <h2 style="font-size:1.8rem;font-weight:700;margin-bottom:1.5rem;"><i class="fas fa-check-double" style="color:var(--role-accent);margin-right:.6rem;"></i>Dispensed Today</h2>
      <div class="adm-card">
        <div class="adm-table-wrap">
          <table class="adm-table">
            <thead><tr><th>Rx ID</th><th>Patient</th><th>Medicine</th><th>Qty</th><th>Time</th></tr></thead>
            <tbody>
            <?php if(empty($recent_disp)):?>
              <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted);">Nothing dispensed yet today.</td></tr>
            <?php else: foreach($recent_disp as $d):?>
            <tr>
              <td><code><?=htmlspecialchars($d['prescription_id']??'#'.$d['id'])?></code></td>
              <td><?=htmlspecialchars($d['patient_name']??'')?></td>
              <td><?=htmlspecialchars($d['medication_name']??'')?></td>
              <td><?=$d['quantity']??1?></td>
              <td><?=date('g:i A',strtotime($d['updated_at']))?></td>
            </tr>
            <?php endforeach; endif;?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /adm-content -->
</main>
</div>

<!-- Restock Modal -->
<div id="modalRestock" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2000;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:var(--surface);border-radius:var(--radius-lg);padding:2.4rem;width:100%;max-width:440px;box-shadow:var(--shadow-lg);">
    <div style="display:flex;justify-content:space-between;margin-bottom:1.5rem;"><h3 style="font-size:1.6rem;font-weight:700;"><i class="fas fa-plus-circle" style="color:var(--role-accent);"></i> Restock Medicine</h3><button onclick="document.getElementById('modalRestock').style.display='none'" style="background:none;border:none;font-size:2rem;cursor:pointer;color:var(--text-muted);">&times;</button></div>
    <p id="restockMedName" style="font-weight:600;font-size:1.4rem;margin-bottom:1.5rem;"></p>
    <form method="POST" action="pharmacy_restock.php">
      <input type="hidden" name="medicine_id" id="restockMedId">
      <div style="margin-bottom:1.2rem;"><label style="display:block;font-size:1.2rem;font-weight:600;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);">Quantity to Add</label><input type="number" name="quantity" min="1" value="50" style="width:100%;padding:1rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text-primary);font-size:1.3rem;font-family:Poppins,sans-serif;" required></div>
      <button type="submit" class="adm-btn adm-btn-success" style="width:100%;justify-content:center;"><i class="fas fa-plus"></i> Add Stock</button>
    </form>
  </div>
</div>

<script>
function applyTheme(t){document.documentElement.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);document.getElementById('themeIcon').className=t==='dark'?'fas fa-sun':'fas fa-moon';}
applyTheme(localStorage.getItem('rmu_theme')||'light');
document.getElementById('themeToggle')?.addEventListener('click',()=>{applyTheme(document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark');});
document.getElementById('menuToggle')?.addEventListener('click',()=>{document.getElementById('admSidebar').classList.toggle('active');document.getElementById('admOverlay').classList.toggle('active');});
document.getElementById('admOverlay')?.addEventListener('click',()=>{document.getElementById('admSidebar').classList.remove('active');document.getElementById('admOverlay').classList.remove('active');});
const TITLES={overview:'Overview',prescriptions:'Prescriptions',inventory:'Medicine Inventory',dispensed:'Dispensed Today'};
function showSec(id,el){
  document.querySelectorAll('.tab-section').forEach(s=>s.classList.remove('active'));
  document.getElementById('sec-'+id).classList.add('active');
  document.querySelectorAll('.adm-nav-item').forEach(a=>a.classList.remove('active'));
  if(el)el.classList.add('active');
  document.getElementById('pgTitleText').textContent=TITLES[id]||id;
}
function filterTbl(q,tblId){q=q.toLowerCase();document.querySelectorAll('#'+tblId+' tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}
function filterMed(status,btn){document.querySelectorAll('.filter-tabs .ftab').forEach(b=>b.classList.remove('active'));if(btn)btn.classList.add('active');document.querySelectorAll('#medTable tbody tr').forEach(r=>{r.style.display=(status==='all'||r.dataset.medstatus===status)?'':'none';});}
function openRestockModal(id,name){document.getElementById('restockMedId').value=id;document.getElementById('restockMedName').textContent=name;document.getElementById('modalRestock').style.display='flex';}
</script>
</body>
</html>
