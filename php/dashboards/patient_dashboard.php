<?php
// ============================================================
// PATIENT DASHBOARD — RMU Medical Sickbay
// Tabbed SPA mirroring doctor dashboard architecture
// ============================================================
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('patient');

require_once '../db_conn.php';
require_once '../includes/maintenance_guard.php';
date_default_timezone_set('Africa/Accra');

$user_id = (int)$_SESSION['user_id'];
$active_tab = $_GET['tab'] ?? 'overview';

// ── Patient Record ──────────────────────────────────────────
$pat_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.*, u.name, u.email, u.phone, u.gender, u.date_of_birth,
            u.profile_image, u.created_at AS member_since, u.last_login_at
     FROM patients p JOIN users u ON p.user_id=u.id
     WHERE p.user_id=$user_id LIMIT 1")) ?? [];
$pat_row['two_fa_enabled'] = (int)($pat_row['two_fa_enabled'] ?? 0);
$pat_pk = (int)($pat_row['id'] ?? 0);
$pat_ref = $pat_row['patient_id'] ?? 'N/A';

// ── Stats ───────────────────────────────────────────────────
$today = date('Y-m-d');
$stats = [];
$r=mysqli_query($conn,"SELECT COUNT(*) c FROM appointments WHERE patient_id=$pat_pk AND appointment_date>='$today' AND status NOT IN('Cancelled','No-Show')");
$stats['upcoming']=$r?(int)mysqli_fetch_assoc($r)['c']:0;
$r=mysqli_query($conn,"SELECT COUNT(*) c FROM prescriptions WHERE patient_id=$pat_pk AND status IN('Pending','Active')");
$stats['active_rx']=$r?(int)mysqli_fetch_assoc($r)['c']:0;
$r=mysqli_query($conn,"SELECT COUNT(*) c FROM notifications WHERE user_id=$user_id AND is_read=0");
$stats['unread_notif']=$r?(int)mysqli_fetch_assoc($r)['c']:0;
$r=mysqli_query($conn,"SELECT COUNT(*) c FROM emergency_contacts WHERE patient_id=$pat_pk");
$stats['emerg_contacts']=$r?(int)mysqli_fetch_assoc($r)['c']:0;
$r=mysqli_query($conn,"SELECT COUNT(*) c FROM appointments WHERE patient_id=$pat_pk");
$stats['total_appts']=$r?(int)mysqli_fetch_assoc($r)['c']:0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Dashboard — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/notifications.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root { --role-accent:#8e44ad; --role-accent-light:#f5eef8; }
[data-theme="dark"] { --role-accent-light:#2d1b3d; }

/* ── Dash sections ── */
.dash-section { display:none; }
.dash-section.active { display:block; animation:fadeTab .3s ease; }
@keyframes fadeTab { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }

/* ── Filter tabs ── */
.filter-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;}
.ftab{padding:.55rem 1.2rem;border-radius:20px;font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition);}
.ftab.active,.ftab:hover{background:var(--role-accent);color:#fff;border-color:var(--role-accent);}

/* ── Badges ── */
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
.adm-table tr:hover{background:var(--surface-2);}

/* ── Modals ── */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2000;align-items:center;justify-content:center;}
.modal-bg.open{display:flex;}
.modal-box{background:var(--surface);border-radius:var(--radius-lg);width:90%;max-width:680px;max-height:85vh;overflow-y:auto;box-shadow:var(--shadow-lg);padding:2.5rem;}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;}
.modal-header h3{font-size:1.8rem;font-weight:700;}
.modal-close{background:none;border:none;font-size:2rem;cursor:pointer;color:var(--text-muted);transition:color .2s;}
.modal-close:hover{color:var(--danger);}

/* ── Forms ── */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;}
.form-group{margin-bottom:1.4rem;}
.form-group label{display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.04em;}
.form-control{width:100%;padding:1rem 1.2rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.3rem;transition:var(--transition);outline:none;}
.form-control:focus{border-color:var(--role-accent);box-shadow:0 0 0 3px rgba(142,68,173,.12);}

/* ── File rows ── */
.file-row{display:flex;align-items:center;gap:1rem;padding:1rem 1.2rem;border-bottom:1px solid var(--border);transition:background .15s;}
.file-row:hover{background:var(--surface-2);}
.file-icon-box{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem;flex-shrink:0;}

/* ── Toggle switch (reuse from notif CSS) ── */
.notif-slider{position:absolute;inset:0;background:var(--border);border-radius:24px;transition:.3s;}
.notif-slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;}
input:checked+.notif-slider{background:var(--role-accent);}
input:checked+.notif-slider::before{transform:translateX(18px);}

/* ── Responsive ── */
@media(max-width:900px){
  .adm-sidebar{transform:translateX(-100%);}
  .adm-sidebar.active{transform:none;}
  .adm-main{margin-left:0!important;}
  .adm-overlay.active{display:block;}
  .form-row{grid-template-columns:1fr;}
}
.adm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;}
</style>
</head>
<body>
<div class="adm-layout">

<!-- ════════════════ SIDEBAR ════════════════ -->
<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-sidebar-brand">
    <div class="adm-brand-icon"><i class="fas fa-heart-pulse"></i></div>
    <div class="adm-brand-text">
      <span class="adm-brand-name">RMU Sickbay</span>
      <span class="adm-brand-role">Patient Portal</span>
    </div>
  </div>
  <nav class="adm-nav" style="padding:1.5rem 1rem;flex:1;">
    <div class="adm-nav-label">My Health</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='overview')?'active':''?>" onclick="showTab('overview',this)"><i class="fas fa-house-medical"></i><span>Overview</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='book')?'active':''?>" onclick="showTab('book',this)"><i class="fas fa-calendar-plus"></i><span>Book Appointment</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='appointments')?'active':''?>" onclick="showTab('appointments',this)">
      <i class="fas fa-calendar-check"></i><span>My Appointments</span>
      <?php if($stats['upcoming']>0):?><span class="adm-badge adm-badge-info" style="margin-left:auto;font-size:1rem;"><?=$stats['upcoming']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='prescriptions')?'active':''?>" onclick="showTab('prescriptions',this)">
      <i class="fas fa-pills"></i><span>My Prescriptions</span>
      <?php if($stats['active_rx']>0):?><span class="adm-badge adm-badge-warning" style="margin-left:auto;font-size:1rem;"><?=$stats['active_rx']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='records')?'active':''?>" onclick="showTab('records',this)"><i class="fas fa-file-medical"></i><span>Medical Records</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='billing')?'active':''?>" onclick="showTab('billing',this)"><i class="fas fa-receipt"></i><span>My Billing</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='emergency')?'active':''?>" onclick="showTab('emergency',this)"><i class="fas fa-phone-alt"></i><span>Emergency Contacts</span></a>
    <div class="adm-nav-label" style="margin-top:1rem;">Account</div>
    <a href="#" class="adm-nav-item <?=($active_tab==='profile')?'active':''?>" onclick="showTab('profile',this)"><i class="fas fa-id-card"></i><span>My Profile</span></a>
    <a href="#" class="adm-nav-item <?=($active_tab==='notif_page')?'active':''?>" onclick="showTab('notif_page',this)">
      <i class="fas fa-bell"></i><span>Notifications</span>
      <?php if($stats['unread_notif']>0):?><span class="adm-badge adm-badge-danger" style="margin-left:auto;font-size:1rem;"><?=$stats['unread_notif']?></span><?php endif;?>
    </a>
    <a href="#" class="adm-nav-item <?=($active_tab==='settings')?'active':''?>" onclick="showTab('settings',this)"><i class="fas fa-gear"></i><span>Settings</span></a>
  </nav>
  <div class="adm-sidebar-footer">
    <a href="/RMU-Medical-Management-System/php/logout.php" class="adm-logout-btn"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a>
  </div>
</aside>
<div class="adm-overlay" id="admOverlay"></div>

<!-- ════════════════ MAIN ════════════════ -->
<main class="adm-main">
  <div class="adm-topbar">
    <div class="adm-topbar-left">
      <button class="adm-menu-toggle" id="menuToggle" style="display:none;"><i class="fas fa-bars"></i></button>
      <span class="adm-page-title"><i class="fas fa-house-medical" id="pageTitleIcon" style="color:var(--role-accent);margin-right:.6rem;"></i><span id="pageTitleText">Overview</span></span>
    </div>
    <div class="adm-topbar-right">
      <span style="font-size:1.2rem;color:var(--text-secondary);"><?=date('D, d M Y')?></span>
      <!-- Notification bell -->
      <?php
        $bellCount = $stats['unread_notif'];
        $bellDisplay = $bellCount > 0 ? 'flex' : 'none';
      ?>
      <button class="adm-theme-toggle" id="rmuBellBtn" style="position:relative;" title="Notifications" onclick="showTab('notif_page',document.querySelector('.adm-nav-item[onclick*=notif_page]'))">
        <i class="fas fa-bell"></i>
        <span id="rmuBellBadge" style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;background:var(--danger);color:#fff;border-radius:50%;font-size:.85rem;font-weight:700;display:<?=$bellDisplay?>;align-items:center;justify-content:center;"><?=$bellCount?></span>
      </button>
      <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
      <div class="adm-avatar" style="background:var(--role-accent);">
        <?php $pimg=$pat_row['profile_image']??''; if(!empty($pimg)&&$pimg!=='default-avatar.png'):?>
        <img src="/RMU-Medical-Management-System/<?=htmlspecialchars($pimg)?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
        <?php else:?>
        <?=strtoupper(substr($pat_row['name']??'P',0,1))?>
        <?php endif;?>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="adm-content">
    <?php include __DIR__.'/pat_tabs/tab_overview.php'; ?>
    <?php include __DIR__.'/pat_tabs/tab_book.php'; ?>
    <?php include __DIR__.'/pat_tabs/tab_appointments.php'; ?>
    <?php include __DIR__.'/pat_tabs/tab_prescriptions.php'; ?>
    <?php include __DIR__.'/pat_tabs/tab_records.php'; ?>
    <?php include __DIR__.'/pat_tabs/tab_billing.php'; ?>
    <?php include __DIR__.'/pat_tabs/tab_emergency.php'; ?>
    <?php include __DIR__.'/pat_tabs/tab_notifications.php'; ?>
    <?php include __DIR__.'/pat_tabs/tab_settings.php'; ?>
    <?php include __DIR__.'/pat_tabs/tab_profile.php'; ?>
  </div>
</main>
</div>

<!-- ════════════════ GLOBAL TOAST & BROADCASTS ════════════════ -->
<div id="toastWrap" style="position:fixed;bottom:2rem;right:2rem;z-index:9999;display:flex;flex-direction:column;gap:.7rem;"></div>
<script src="/RMU-Medical-Management-System/php/includes/BroadcastReceiver.js"></script>

<script>
// Initialize Broadcast Receiver
document.addEventListener('DOMContentLoaded', () => {
    if (typeof BroadcastReceiver !== 'undefined') {
        window.rmuBroadcasts = new BroadcastReceiver(<?= $_SESSION['user_id'] ?>);
    }
});

// ── Toast ──────────────────────────────────────────────────
function toast(msg,type='success'){
  const t=document.createElement('div');
  const bg=type==='danger'?'var(--danger)':type==='warning'?'var(--warning)':'var(--success)';
  t.style.cssText=`padding:1rem 1.8rem;background:${bg};color:#fff;border-radius:12px;font-size:1.3rem;font-weight:600;box-shadow:var(--shadow-md);animation:fadeTab .3s ease;max-width:400px;`;
  t.textContent=msg;
  document.getElementById('toastWrap').appendChild(t);
  setTimeout(()=>t.remove(),4000);
}

// ── Tab Navigation ────────────────────────────────────────
const TAB_TITLES={overview:'Overview',book:'Book Appointment',appointments:'My Appointments',
  prescriptions:'My Prescriptions',lab:'Lab Results',records:'Medical Records',billing:'My Billing',
  emergency:'Emergency Contacts',notif_page:'Notifications',settings:'Settings',profile:'My Profile'};
const TAB_ICONS={overview:'fa-house-medical',book:'fa-calendar-plus',appointments:'fa-calendar-check',
  prescriptions:'fa-pills',lab:'fa-flask',records:'fa-file-medical',billing:'fa-receipt',
  emergency:'fa-phone-alt',notif_page:'fa-bell',settings:'fa-gear',profile:'fa-id-card'};

function showTab(tab,el){
  document.querySelectorAll('.dash-section').forEach(s=>s.classList.remove('active'));
  const sec=document.getElementById('sec-'+tab);
  if(sec) sec.classList.add('active');
  document.querySelectorAll('.adm-nav-item').forEach(a=>a.classList.remove('active'));
  if(el) el.classList.add('active');
  document.getElementById('pageTitleText').textContent=TAB_TITLES[tab]||tab;
  const icon=document.getElementById('pageTitleIcon');
  if(icon && TAB_ICONS[tab]) icon.className=`fas ${TAB_ICONS[tab]}`;
  document.getElementById('admSidebar').classList.remove('active');
  document.getElementById('admOverlay').classList.remove('active');
}

// ── Init ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded',()=>{
  const initTab='<?=$active_tab?>';
  const initEl=document.querySelector(`.adm-nav-item[onclick*="${initTab}"]`);
  showTab(initTab,initEl);
});

// ── Sidebar toggle ──────────────────────────────────────
document.getElementById('menuToggle')?.addEventListener('click',()=>{
  document.getElementById('admSidebar').classList.toggle('active');
  document.getElementById('admOverlay').classList.toggle('active');
});
document.getElementById('admOverlay')?.addEventListener('click',()=>{
  document.getElementById('admSidebar').classList.remove('active');
  document.getElementById('admOverlay').classList.remove('active');
});

// ── Theme ─────────────────────────────────────────────────
const themeToggle=document.getElementById('themeToggle');
const themeIcon=document.getElementById('themeIcon');
function applyTheme(t){document.documentElement.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon';}
applyTheme(localStorage.getItem('rmu_theme')||'light');
themeToggle?.addEventListener('click',()=>applyTheme(document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark'));

// ── AJAX Helper ──────────────────────────────────────────
const PAT_API='/RMU-Medical-Management-System/php/dashboards/patient_actions.php';
async function patAction(data,isFormData=false){
  const opts={method:'POST'};
  if(isFormData){opts.body=data;}
  else{opts.headers={'Content-Type':'application/json'};opts.body=JSON.stringify(data);}
  try{const r=await fetch(PAT_API,opts);return await r.json();}catch(e){return{success:false,message:e.message};}
}

// ── Modal Helpers ────────────────────────────────────────
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.addEventListener('click',e=>{if(e.target.classList.contains('modal-bg'))e.target.classList.remove('open');});

// ── Responsive menu toggle ──────────────────────────────
(function(){const mq=window.matchMedia('(max-width:900px)');function h(e){document.getElementById('menuToggle').style.display=e.matches?'flex':'none';}mq.addListener(h);h(mq);})();
</script>


</body>
</html>