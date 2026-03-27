<?php
// ============================================================
// PRESCRIPTION REFILLS — Patient-facing
// Updated: Phase 4 — integrated with new tabbed dashboard,
// new columns (patient_notified, refill_count),
// unified notification system, consistent sidebar
// ============================================================
session_start();
require_once '../db_conn.php';
require_once '../classes/PrescriptionRefillManager.php';

$role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || $role !== 'patient') {
    header('Location: ../index.php'); exit;
}
date_default_timezone_set('Africa/Accra');
$refillManager = new PrescriptionRefillManager($conn);
$userId  = (int)$_SESSION['user_id'];
$today   = date('Y-m-d');

// Patient ID (new schema: patients.id)
$pidRow  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id, patient_id AS p_ref FROM patients WHERE user_id=$userId LIMIT 1"));
$patId   = (int)($pidRow['id'] ?? 0);

$message = ''; $error = '';

// ── POST: Request Refill ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='request_refill') {
    $rxId   = (int)$_POST['prescription_id'];
    $notes  = trim($_POST['notes'] ?? '');

    // Check max refills before proceeding
    $rxCheck = mysqli_fetch_assoc(mysqli_query($conn,"SELECT refills_allowed, refill_count FROM prescriptions WHERE id=$rxId AND patient_id=$patId LIMIT 1"));
    if ($rxCheck && (int)$rxCheck['refill_count'] >= (int)$rxCheck['refills_allowed'] && (int)$rxCheck['refills_allowed'] > 0) {
        $error = 'Maximum refills reached for this prescription.';
    } else {
        $result = $refillManager->requestRefill($rxId, $patId, $notes);
        if ($result['success']) {
            $message = $result['message'];
            // Update prescription status and mark patient notified
            mysqli_query($conn,"UPDATE prescriptions SET status='Refill Requested', patient_notified=1, updated_at=NOW() WHERE id=$rxId");
            // Notify the doctor
            $rx = mysqli_fetch_assoc(mysqli_query($conn,"SELECT pr.doctor_id, pr.medication_name, u.name AS pat_name FROM prescriptions pr JOIN users u ON u.id=$userId WHERE pr.id=$rxId LIMIT 1"));
            if ($rx) {
                $docUid=(int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id FROM doctors WHERE id={$rx['doctor_id']} LIMIT 1"))['user_id']??0);
                if($docUid){
                    $pname=mysqli_real_escape_string($conn,$rx['pat_name']??'Patient');
                    $medName=mysqli_real_escape_string($conn,$rx['medication_name']??'');
                    mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,related_id,created_at)
                      VALUES($docUid,'doctor','prescription','Prescription Refill Request','{$pname} has requested a refill for {$medName}.',0,'prescriptions',$rxId,NOW())");
                }
            }
        } else { $error = $result['message']; }
    }
}

// ── Fetch prescriptions (include new columns) ─────────────
$prescriptions = [];
$q = mysqli_query($conn,
    "SELECT pr.id, pr.prescription_id, pr.medication_name, pr.dosage, pr.frequency, pr.duration,
            pr.instructions, pr.prescription_date, pr.status, pr.quantity,
            pr.refills_allowed, pr.refill_count, pr.patient_notified,
            ud.name AS doctor_name, d.specialization,
            (SELECT COUNT(*) FROM prescription_refills prf WHERE prf.prescription_id=pr.id AND prf.status='Pending') AS pending_refills
     FROM prescriptions pr
     JOIN doctors d ON pr.doctor_id=d.id JOIN users ud ON d.user_id=ud.id
     WHERE pr.patient_id=$patId AND pr.status NOT IN ('Cancelled','Expired')
     ORDER BY pr.prescription_date DESC");
if($q) while($r=mysqli_fetch_assoc($q)) $prescriptions[]=$r;

// ── Refill history ────────────────────────────────────────
$refillHistory = $refillManager->getPatientRefills($patId);

// ── Unread notifications ──────────────────────────────────
$unread = (int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$userId AND is_read=0"))[0] ?? 0);

// ── Medicine stock check helper ───────────────────────────
function checkStock($conn, $medName) {
    $m = mysqli_real_escape_string($conn, $medName);
    $r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT stock_quantity,stock_status FROM medicine_inventory WHERE medicine_name='$m' LIMIT 1"));
    return $r ?? ['stock_quantity'=>0,'stock_status'=>'Unknown'];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Prescription Refills — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/notifications.css">
<style>
:root{--role-accent:#8e44ad;}
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
.rx-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.8rem;margin-bottom:1.2rem;box-shadow:var(--shadow-sm);border-left:4px solid var(--role-accent);}
.rx-card.Dispensed,.rx-card.Completed{border-left-color:var(--success);}
.rx-card.Pending,.rx-card.Active{border-left-color:var(--warning);}
.rx-card.refill-requested{border-left-color:var(--info);}
.tab-btn{padding:.6rem 1.4rem;border-radius:20px;font-weight:600;font-size:1.2rem;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);transition:var(--transition);}
.tab-btn.active,.tab-btn:hover{background:var(--role-accent);color:#fff;border-color:var(--role-accent);}
.tab-pane{display:none;}.tab-pane.show{display:block;}
</style>
</head>
<body>
<div class="adm-layout">

<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-sidebar-brand">
    <div class="adm-brand-icon"><i class="fas fa-heart-pulse"></i></div>
    <div class="adm-brand-text"><span class="adm-brand-name">RMU Sickbay</span><span class="adm-brand-role">Patient Portal</span></div>
  </div>
  <nav class="adm-nav" style="padding:1.5rem 1rem;">
    <div class="adm-nav-label">My Health</div>
    <a href="patient_dashboard.php" class="adm-nav-item"><i class="fas fa-house-medical"></i><span>Dashboard</span></a>
    <a href="patient_dashboard.php?tab=book" class="adm-nav-item"><i class="fas fa-calendar-plus"></i><span>Book Appointment</span></a>
    <a href="my_appointments.php" class="adm-nav-item"><i class="fas fa-calendar-check"></i><span>My Appointments</span></a>
    <a href="prescription_refills.php" class="adm-nav-item active"><i class="fas fa-pills"></i><span>Prescription Refills</span></a>
    <div class="adm-nav-label" style="margin-top:1rem;">Clinical</div>
    <a href="patient_dashboard.php?tab=lab" class="adm-nav-item"><i class="fas fa-flask"></i><span>Lab Results</span></a>
    <a href="patient_dashboard.php?tab=records" class="adm-nav-item"><i class="fas fa-file-medical"></i><span>Medical Records</span></a>
    <a href="patient_dashboard.php?tab=emergency" class="adm-nav-item"><i class="fas fa-phone-alt"></i><span>Emergency Contacts</span></a>
    <div class="adm-nav-label" style="margin-top:1rem;">Account</div>
    <a href="patient_dashboard.php?tab=notif_page" class="adm-nav-item"><i class="fas fa-bell"></i><span>Notifications</span></a>
    <a href="patient_dashboard.php?tab=settings" class="adm-nav-item"><i class="fas fa-gear"></i><span>Settings</span></a>
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
      <span class="adm-page-title"><i class="fas fa-prescription-bottle-medical" style="color:var(--role-accent);margin-right:.6rem;"></i>Prescription Refills</span>
    </div>
    <div class="adm-topbar-right">
      <span style="font-size:1.2rem;color:var(--text-secondary);"><?=date('D, d M Y')?></span>
      <?php $bd=$unread>0?'flex':'none'; $bl=$unread>99?'99+':$unread; $bc=$unread>0?'adm-notif-btn has-unread':'adm-notif-btn'; ?>
      <div style="position:relative;"><button id="rmuBellBtn" class="<?=$bc?>" title="Notifications"><i class="fas fa-bell"></i><span id="rmuBellCount" style="display:<?=$bd?>"><?=$bl?></span></button></div>
      <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
      <div class="adm-avatar" style="background:var(--role-accent);"><?=strtoupper(substr($_SESSION['user_name']??$_SESSION['name']??'P',0,1))?></div>
    </div>
  </div>

  <div class="adm-content">
    <?php if($message):?><div style="background:var(--success-light);color:var(--success);border-left:4px solid var(--success);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($message)?></div><?php endif;?>
    <?php if($error):?><div style="background:var(--danger-light);color:var(--danger);border-left:4px solid var(--danger);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;"><i class="fas fa-exclamation-circle"></i> <?=htmlspecialchars($error)?></div><?php endif;?>

    <!-- Tabs -->
    <div style="display:flex;gap:.5rem;margin-bottom:1.8rem;">
      <button class="tab-btn active" onclick="switchTab('active',this)"><i class="fas fa-pills"></i> Active Prescriptions</button>
      <button class="tab-btn" onclick="switchTab('history',this)"><i class="fas fa-clock-rotate-left"></i> Refill History</button>
    </div>

    <!-- Active Prescriptions -->
    <div id="tab-active" class="tab-pane show">
      <?php if(empty($prescriptions)):?>
        <div class="adm-card" style="text-align:center;padding:4rem;">
          <i class="fas fa-prescription-bottle-medical" style="font-size:3rem;opacity:.25;display:block;margin-bottom:1rem;"></i>
          <p style="color:var(--text-muted);font-size:1.3rem;">No active prescriptions found.</p>
        </div>
      <?php else: foreach($prescriptions as $rx):
        $stock = checkStock($conn, $rx['medication_name']);
        $sc_map = ['Pending'=>'warning','Active'=>'warning','Dispensed'=>'success','Completed'=>'success','Cancelled'=>'danger','Refill Requested'=>'info'];
        $sc = $sc_map[$rx['status']] ?? 'info';
        $has_pending_refill = $rx['pending_refills'] > 0;
        $refills_left = max(0, (int)($rx['refills_allowed']??0) - (int)($rx['refill_count']??0));
        $cardCls = $rx['status']==='Refill Requested' ? 'refill-requested' : $rx['status'];
      ?>
      <div class="rx-card <?=$cardCls?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
          <div>
            <div style="font-size:1.7rem;font-weight:800;"><?=htmlspecialchars($rx['medication_name'])?></div>
            <div style="font-size:1.2rem;color:var(--text-muted);">Dr. <?=htmlspecialchars($rx['doctor_name'])?> &middot; <?=htmlspecialchars($rx['specialization']??'General')?></div>
          </div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.5rem;">
            <span class="adm-badge adm-badge-<?=$sc?>"><?=$rx['status']?></span>
            <?php if($stock['stock_status']==='Out of Stock'):?>
              <span class="adm-badge adm-badge-danger" title="Currently out of stock"><i class="fas fa-triangle-exclamation"></i> Out of Stock</span>
            <?php elseif($stock['stock_status']==='Low Stock'):?>
              <span class="adm-badge adm-badge-warning"><i class="fas fa-triangle-exclamation"></i> Low Stock</span>
            <?php else:?>
              <span class="adm-badge adm-badge-success"><i class="fas fa-circle-check"></i> <?=$stock['stock_quantity']?> in stock</span>
            <?php endif;?>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.8rem;margin-bottom:1.2rem;">
          <?php foreach([['Dosage',$rx['dosage'],'fa-tablets'],['Frequency',$rx['frequency'],'fa-clock'],['Duration',$rx['duration'],'fa-hourglass'],['Qty',$rx['quantity'],'fa-hashtag'],['Issued',date('d M Y',strtotime($rx['prescription_date'])),'fa-calendar'],['Refills Left',$refills_left.'/'.$rx['refills_allowed'],'fa-redo']] as [$lbl,$val,$ic]):?>
          <div style="background:var(--surface-2);border-radius:8px;padding:.8rem 1rem;">
            <div style="font-size:1rem;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);font-weight:600;"><i class="fas <?=$ic?>" style="color:var(--role-accent);margin-right:.3rem;"></i><?=$lbl?></div>
            <div style="font-size:1.25rem;font-weight:600;margin-top:.2rem;"><?=htmlspecialchars((string)$val)?></div>
          </div>
          <?php endforeach;?>
        </div>
        <?php if($rx['instructions']??''):?><div style="font-size:1.2rem;color:var(--text-secondary);background:var(--surface-2);border-radius:8px;padding:.8rem 1rem;margin-bottom:1rem;"><i class="fas fa-note-sticky" style="color:var(--role-accent);margin-right:.4rem;"></i><?=htmlspecialchars($rx['instructions'])?></div><?php endif;?>
        <div style="display:flex;gap:.8rem;flex-wrap:wrap;">
          <?php if(!$has_pending_refill && in_array($rx['status'],['Dispensed','Active','Completed']) && $stock['stock_quantity']>0 && $refills_left>0):?>
          <button onclick="openRefill(<?=$rx['id']?>,<?=json_encode($rx['medication_name'])?>,<?=json_encode('Dr. '.$rx['doctor_name'])?>)" class="adm-btn adm-btn-primary"><i class="fas fa-rotate-right"></i> Request Refill</button>
          <?php elseif($has_pending_refill):?>
          <span class="adm-btn adm-btn-ghost" style="pointer-events:none;opacity:.7;"><i class="fas fa-clock"></i> Refill Pending Review</span>
          <?php elseif($refills_left<=0 && (int)$rx['refills_allowed']>0):?>
          <span style="font-size:1.2rem;color:var(--text-muted);"><i class="fas fa-ban"></i> Max refills reached</span>
          <?php elseif($stock['stock_quantity']==0):?>
          <span style="font-size:1.2rem;color:var(--danger);"><i class="fas fa-triangle-exclamation"></i> Refill unavailable — out of stock</span>
          <?php endif;?>
        </div>
      </div>
      <?php endforeach; endif;?>
    </div>

    <!-- Refill History -->
    <div id="tab-history" class="tab-pane">
      <div class="adm-card">
        <div style="overflow-x:auto;">
          <table style="width:100%;border-collapse:collapse;font-size:1.3rem;">
            <thead><tr>
              <?php foreach(['Refill ID','Medicine','Requested','Status','Notes'] as $h):?>
              <th style="background:var(--surface-2);padding:1.2rem 1.4rem;text-align:left;font-size:1.1rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.04em;border-bottom:1.5px solid var(--border);"><?=$h?></th>
              <?php endforeach;?>
            </tr></thead>
            <tbody>
            <?php if(empty($refillHistory)):?>
              <tr><td colspan="5" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No refill history yet.</td></tr>
            <?php else: foreach($refillHistory as $rf):
              $rsc=['Pending'=>'warning','Approved'=>'success','Rejected'=>'danger','Dispensed'=>'success'][$rf['status']]??'info';
            ?>
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:1.2rem 1.4rem;"><code>#<?=$rf['id']??$rf['refill_id']??'—'?></code></td>
              <td style="padding:1.2rem 1.4rem;"><?=htmlspecialchars($rf['medication_name']??$rf['medicine_name']??'—')?></td>
              <td style="padding:1.2rem 1.4rem;"><?=date('d M Y',strtotime($rf['request_date']??$rf['requested_at']??$rf['created_at']??'now'))?></td>
              <td style="padding:1.2rem 1.4rem;"><span class="adm-badge adm-badge-<?=$rsc?>"><?=$rf['status']?></span></td>
              <td style="padding:1.2rem 1.4rem;"><?=htmlspecialchars($rf['notes']??'—')?></td>
            </tr>
            <?php endforeach; endif;?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</main>
</div>

<!-- Refill Request Modal -->
<div class="modal-bg" id="modalRefill">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-rotate-right" style="color:var(--role-accent);"></i> Request Prescription Refill</h3>
      <button class="modal-close" onclick="this.closest('.modal-bg').classList.remove('open')">&times;</button>
    </div>
    <div id="refillMedInfo" style="background:var(--surface-2);border-radius:10px;padding:1rem 1.4rem;margin-bottom:1.5rem;"></div>
    <div style="background:rgba(142,68,173,.08);border-left:4px solid var(--role-accent);border-radius:0 10px 10px 0;padding:1rem 1.4rem;margin-bottom:1.2rem;font-size:1.2rem;">
      <i class="fas fa-info-circle" style="color:var(--role-accent);"></i> Your doctor will be notified and will need to approve this refill.
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="request_refill">
      <input type="hidden" name="prescription_id" id="refillRxId">
      <div class="form-group"><label>Notes (optional)</label><textarea name="notes" id="refillNotes" class="form-control" rows="3" placeholder="Any specific instructions or notes for your doctor…"></textarea></div>
      <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-paper-plane"></i> Submit Refill Request</button>
    </form>
  </div>
</div>

<script>
function applyTheme(t){document.documentElement.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);document.getElementById('themeIcon').className=t==='dark'?'fas fa-sun':'fas fa-moon';}
applyTheme(localStorage.getItem('rmu_theme')||'light');
document.getElementById('themeToggle')?.addEventListener('click',()=>{applyTheme(document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark');});
document.getElementById('menuToggle')?.addEventListener('click',()=>{document.getElementById('admSidebar').classList.toggle('active');document.getElementById('admOverlay').classList.toggle('active');});
document.getElementById('admOverlay')?.addEventListener('click',()=>{document.getElementById('admSidebar').classList.remove('active');document.getElementById('admOverlay').classList.remove('active');});
(function(){const mq=window.matchMedia('(max-width:900px)');function h(e){document.getElementById('menuToggle').style.display=e.matches?'flex':'none';}mq.addListener(h);h(mq);})();
function switchTab(id,btn){
  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('show'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+id).classList.add('show');
  if(btn)btn.classList.add('active');
}
function openRefill(id,med,doc){
  document.getElementById('refillRxId').value=id;
  document.getElementById('refillMedInfo').innerHTML=`<strong>${med}</strong><span style="color:var(--text-muted);margin-left:.5rem;">prescribed by ${doc}</span>`;
  document.getElementById('modalRefill').classList.add('open');
}
document.querySelectorAll('.modal-bg').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');}));
</script>
<script src="/RMU-Medical-Management-System/js/notifications.js"></script>


</body>
</html>
