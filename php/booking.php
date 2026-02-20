<?php
session_start();
require_once 'db_conn.php';
date_default_timezone_set('Africa/Accra');

$is_logged_in   = isset($_SESSION['user_id']);
$logged_user_id = $is_logged_in ? (int)$_SESSION['user_id'] : 0;
$user_role      = $_SESSION['user_role'] ?? '';

$patient_info = null; $pat_pk = 0;
if ($is_logged_in && $user_role === 'patient') {
    $patient_info = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.id as pat_pk, p.patient_id, p.blood_group, p.allergies,
                u.name, u.email, u.phone, u.gender
         FROM patients p JOIN users u ON p.user_id = u.id
         WHERE p.user_id = $logged_user_id LIMIT 1"));
    if ($patient_info) $pat_pk = (int)$patient_info['pat_pk'];
}

$doctors = [];
$q = mysqli_query($conn,
    "SELECT d.id, d.doctor_id, d.specialization, d.experience_years, d.consultation_fee, d.available_days, d.is_available,
            u.name, u.gender
     FROM doctors d JOIN users u ON d.user_id = u.id
     WHERE d.is_available = 1 AND u.is_active = 1
     ORDER BY d.specialization, u.name");
if ($q) while ($r = mysqli_fetch_assoc($q)) $doctors[] = $r;

$specs = [];
foreach ($doctors as $doc) $specs[$doc['specialization']][] = $doc;
ksort($specs);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Book an Appointment — RMU Medical Sickbay</title>
<meta name="description" content="Book a medical appointment at RMU Sickbay online.">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<style>
:root{--bk-p:#2563EB;--bk-p2:#0ea5e9;--bk-g:#10b981;--bk-r:#ef4444;--bk-y:#f59e0b;--bk-bg:#f0f5fb;--bk-glass:rgba(255,255,255,.92);--bk-shadow:0 8px 32px rgba(37,99,235,.13);}
[data-theme="dark"]{--bk-bg:#0d1117;--bk-glass:rgba(22,27,40,.92);}
body{background:var(--bk-bg);font-family:'Poppins',sans-serif;}

/* HERO */
.bk-hero{background:linear-gradient(135deg,#1e3a8a 0%,#2563EB 45%,#0ea5e9 100%);padding:4rem 1.5rem 7rem;text-align:center;position:relative;overflow:hidden;}
.bk-hero::after{content:'';position:absolute;bottom:-1px;left:0;right:0;height:80px;background:var(--bk-bg);clip-path:ellipse(55% 100% at 50% 100%);}
.bk-hero-badge{display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.25);border-radius:50px;padding:.4rem 1.2rem;font-size:.8rem;font-weight:600;color:#fff;margin-bottom:1.5rem;}
.bk-hero h1{font-size:2.4rem;font-weight:800;color:#fff;margin-bottom:.6rem;line-height:1.2;}
.bk-hero p{color:rgba(255,255,255,.8);font-size:1rem;max-width:460px;margin:0 auto;}
.bk-hero-orbs{position:absolute;inset:0;pointer-events:none;overflow:hidden;}
.bk-orb{position:absolute;border-radius:50%;background:rgba(255,255,255,.06);animation:orb-float 8s ease-in-out infinite;}
.bk-orb:nth-child(1){width:300px;height:300px;top:-80px;right:-60px;animation-delay:0s;}
.bk-orb:nth-child(2){width:180px;height:180px;bottom:40px;left:-40px;animation-delay:3s;}
.bk-orb:nth-child(3){width:100px;height:100px;top:30%;left:20%;animation-delay:5s;}
@keyframes orb-float{0%,100%{transform:translateY(0) scale(1);}50%{transform:translateY(-20px) scale(1.05);}}

/* WRAPPER */
.bk-wrapper{max-width:860px;margin:-5rem auto 4rem;padding:0 1.5rem;position:relative;z-index:2;}

/* PROGRESS */
.bk-stepper{background:var(--bk-glass);backdrop-filter:blur(12px);border-radius:20px;padding:1.5rem 2rem;box-shadow:var(--bk-shadow);margin-bottom:1.5rem;border:1px solid var(--border);}
.bk-steps{display:flex;align-items:center;}
.bk-step{flex:1;display:flex;flex-direction:column;align-items:center;gap:.35rem;position:relative;}
.bk-step::after{content:'';position:absolute;top:18px;left:50%;width:100%;height:2px;background:var(--border);z-index:0;}
.bk-step:last-child::after{display:none;}
.bk-step.done::after,.bk-step.active::after{background:var(--bk-p);}
.bk-snum{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;position:relative;z-index:1;border:2.5px solid var(--border);background:var(--bg-card);color:var(--text-muted);transition:all .3s;}
.bk-step.active .bk-snum{border-color:var(--bk-p);background:var(--bk-p);color:#fff;box-shadow:0 0 0 5px rgba(37,99,235,.18);}
.bk-step.done .bk-snum{border-color:var(--bk-g);background:var(--bk-g);color:#fff;}
.bk-slabel{font-size:.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.03em;}
.bk-step.active .bk-slabel,.bk-step.done .bk-slabel{color:var(--text-primary);}

/* PANELS */
.bk-panel{background:var(--bk-glass);backdrop-filter:blur(12px);border-radius:20px;padding:2.2rem;box-shadow:var(--bk-shadow);display:none;border:1px solid var(--border);}
.bk-panel.active{display:block;animation:slideIn .35s cubic-bezier(.22,.61,.36,1);}
@keyframes slideIn{from{opacity:0;transform:translateX(24px)}to{opacity:1;transform:translateX(0)}}
.bk-ptitle{font-size:1.15rem;font-weight:700;margin-bottom:1.5rem;display:flex;align-items:center;gap:.75rem;color:var(--text-primary);}
.bk-ptitle i{color:var(--bk-p);font-size:1.1rem;}

/* SERVICE CARDS */
.svc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:1rem;}
.svc-card{border:2px solid var(--border);border-radius:16px;padding:1.4rem 1rem;text-align:center;cursor:pointer;transition:all .22s;background:var(--bg-secondary);position:relative;}
.svc-card:hover{border-color:var(--bk-p);transform:translateY(-4px);box-shadow:0 8px 24px rgba(37,99,235,.15);}
.svc-card.selected{border-color:var(--bk-p);background:rgba(37,99,235,.06);box-shadow:0 0 0 4px rgba(37,99,235,.12);}
.svc-icon-wrap{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto .9rem;font-size:1.5rem;}
.svc-card .sc-name{font-weight:700;font-size:.88rem;margin-bottom:.3rem;}
.svc-card .sc-desc{font-size:.73rem;color:var(--text-muted);}
.svc-badge{position:absolute;top:.6rem;right:.6rem;font-size:.65rem;font-weight:700;background:var(--bk-g);color:#fff;border-radius:50px;padding:.2rem .55rem;}

/* DOCTOR CARDS */
.doc-filter{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;}
.dfbtn{padding:.38rem .9rem;border-radius:50px;border:1.5px solid var(--border);background:var(--bg-secondary);font-size:.78rem;font-weight:500;cursor:pointer;transition:all .2s;color:var(--text-primary);}
.dfbtn.active,.dfbtn:hover{border-color:var(--bk-p);background:var(--bk-p);color:#fff;}
.doc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;max-height:420px;overflow-y:auto;padding-right:.2rem;}
.doc-card{border:2px solid var(--border);border-radius:16px;padding:1.25rem;cursor:pointer;transition:all .22s;background:var(--bg-secondary);display:flex;flex-direction:column;align-items:center;text-align:center;gap:.45rem;position:relative;}
.doc-card:hover{border-color:var(--bk-p);transform:translateY(-3px);box-shadow:0 6px 20px rgba(37,99,235,.12);}
.doc-card.selected{border-color:var(--bk-p);background:rgba(37,99,235,.06);box-shadow:0 0 0 4px rgba(37,99,235,.12);}
.doc-avatar{width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,var(--bk-p),var(--bk-p2));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;position:relative;}
.doc-verified{position:absolute;bottom:0;right:0;width:18px;height:18px;border-radius:50%;background:var(--bk-g);color:#fff;font-size:.6rem;display:flex;align-items:center;justify-content:center;border:2px solid var(--bg-card);}
.doc-name{font-weight:700;font-size:.9rem;}
.doc-spec{font-size:.76rem;color:var(--text-secondary);}
.doc-meta{font-size:.73rem;color:var(--text-muted);display:flex;flex-wrap:wrap;gap:.4rem;justify-content:center;}
.doc-fee{font-weight:700;color:var(--bk-p);font-size:.85rem;background:rgba(37,99,235,.08);border-radius:50px;padding:.2rem .75rem;}
.doc-stars{color:#f59e0b;font-size:.72rem;}

/* CALENDAR */
.cal-wrap{background:var(--bg-secondary);border-radius:16px;padding:1.25rem;border:1.5px solid var(--border);}
.cal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;}
.cal-month{font-weight:700;font-size:.95rem;}
.cal-nav{width:30px;height:30px;border:none;border-radius:50%;background:var(--bg-card);cursor:pointer;color:var(--text-primary);display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:all .2s;}
.cal-nav:hover{background:var(--bk-p);color:#fff;}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:.35rem;text-align:center;}
.cal-dow{font-size:.68rem;font-weight:600;color:var(--text-muted);padding:.2rem 0;text-transform:uppercase;}
.cal-day{font-size:.82rem;padding:.45rem .2rem;border-radius:10px;cursor:pointer;transition:all .18s;border:1.5px solid transparent;font-weight:500;}
.cal-day:hover:not(.cal-disabled){background:rgba(37,99,235,.1);border-color:var(--bk-p);}
.cal-day.cal-today{font-weight:700;color:var(--bk-p);}
.cal-day.cal-selected{background:var(--bk-p);color:#fff;border-color:var(--bk-p);}
.cal-day.cal-disabled{color:var(--text-muted);opacity:.4;cursor:not-allowed;pointer-events:none;}
.cal-day.cal-weekend{color:var(--bk-r);}
.cal-day.cal-other{opacity:.35;pointer-events:none;}
.slot-section-label{font-size:.76rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin:.9rem 0 .5rem;}
.slot-grid{display:flex;flex-wrap:wrap;gap:.5rem;}
.ts{padding:.42rem 1rem;border:1.5px solid var(--border);border-radius:50px;font-size:.8rem;font-weight:500;cursor:pointer;transition:all .2s;background:var(--bg-secondary);}
.ts:hover{border-color:var(--bk-p);color:var(--bk-p);}
.ts.selected{background:var(--bk-p);border-color:var(--bk-p);color:#fff;}
.ts.booked{opacity:.4;cursor:not-allowed;text-decoration:line-through;}

/* URGENCY CARDS */
.urg-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.85rem;}
@media(max-width:560px){.urg-grid{grid-template-columns:1fr;}}
.urg-card{border:2.5px solid var(--border);border-radius:16px;padding:1.2rem;text-align:center;cursor:pointer;transition:all .22s;background:var(--bg-secondary);}
.urg-card:hover{transform:translateY(-2px);}
.urg-card.sel-routine{border-color:var(--bk-g);background:rgba(16,185,129,.07);}
.urg-card.sel-urgent{border-color:var(--bk-y);background:rgba(245,158,11,.07);}
.urg-card.sel-emergency{border-color:var(--bk-r);background:rgba(239,68,68,.07);}
.urg-icon{font-size:1.6rem;margin-bottom:.5rem;}
.urg-title{font-weight:700;font-size:.9rem;}
.urg-sub{font-size:.73rem;color:var(--text-muted);margin-top:.2rem;}

/* FORM */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
@media(max-width:580px){.form-row{grid-template-columns:1fr;}}
.bk-fg{margin-bottom:1.1rem;}
.bk-lbl{font-weight:600;font-size:.87rem;margin-bottom:.4rem;display:block;color:var(--text-primary);}
.bk-inp{width:100%;padding:.75rem 1rem;border:2px solid var(--border);border-radius:12px;background:var(--bg-secondary);color:var(--text-primary);font-family:inherit;font-size:.88rem;outline:none;transition:border-color .2s;box-sizing:border-box;}
.bk-inp:focus{border-color:var(--bk-p);box-shadow:0 0 0 3px rgba(37,99,235,.12);}

/* CONFIRM */
.cf-card{background:var(--bg-secondary);border-radius:16px;overflow:hidden;border:1px solid var(--border);}
.cf-header{background:linear-gradient(135deg,var(--bk-p),var(--bk-p2));padding:1.2rem 1.5rem;color:#fff;display:flex;align-items:center;gap:.75rem;}
.cf-header i{font-size:1.3rem;}
.cf-header h3{font-size:1rem;font-weight:700;margin:0;}
.cf-body{padding:0;}
.cf-row{display:flex;justify-content:space-between;align-items:center;padding:.75rem 1.5rem;border-bottom:1px solid var(--border);font-size:.88rem;}
.cf-row:last-child{border:none;}
.cf-label{color:var(--text-secondary);display:flex;align-items:center;gap:.5rem;font-weight:500;}
.cf-label i{color:var(--bk-p);width:16px;text-align:center;}
.cf-value{font-weight:700;text-align:right;max-width:60%;}

/* BUTTONS */
.bk-nav{display:flex;justify-content:space-between;align-items:center;margin-top:1.6rem;}
.bk-btn{padding:.8rem 1.8rem;border-radius:12px;font-weight:600;font-size:.9rem;cursor:pointer;border:none;font-family:inherit;transition:all .22s;display:inline-flex;align-items:center;gap:.5rem;}
.bk-btn:disabled{opacity:.5;cursor:not-allowed;}
.btn-p{background:var(--bk-p);color:#fff;}
.btn-p:hover:not(:disabled){background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 14px rgba(37,99,235,.35);}
.btn-g{background:var(--bk-g);color:#fff;}
.btn-g:hover:not(:disabled){background:#059669;}
.btn-back{background:var(--bg-secondary);color:var(--text-primary);border:1.5px solid var(--border);}
.btn-back:hover{border-color:var(--text-secondary);}

/* SUCCESS */
.bk-success{text-align:center;padding:2.5rem 1.5rem;}
.success-circle{width:90px;height:90px;border-radius:50%;background:rgba(16,185,129,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2.5rem;color:var(--bk-g);animation:popIn .5s ease;}
@keyframes popIn{0%{transform:scale(0)}70%{transform:scale(1.15)}100%{transform:scale(1)}}
.bk-success h2{font-size:1.6rem;font-weight:800;margin-bottom:.5rem;}
.bk-success p{color:var(--text-secondary);margin-bottom:1.5rem;}

/* CONFETTI */
.confetti-wrap{position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:999;overflow:hidden;}
.cf-piece{position:absolute;width:10px;height:14px;border-radius:3px;animation:cf-fall 3.5s ease-in forwards;}
@keyframes cf-fall{0%{transform:translateY(-20px) rotate(0deg);opacity:1;}100%{transform:translateY(110vh) rotate(720deg);opacity:0;}}

/* INFO BANNER */
.bk-banner{background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.2);border-radius:12px;padding:.85rem 1.2rem;display:flex;align-items:center;gap:.75rem;margin-bottom:1.2rem;font-size:.86rem;color:var(--text-primary);}
.bk-banner i{color:var(--bk-p);}

/* NAV BAR */
.bk-topbar{background:var(--bg-card);border-bottom:1px solid var(--border);padding:.8rem 2rem;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 8px rgba(0,0,0,.06);position:sticky;top:0;z-index:100;}
.bk-brand{font-weight:800;font-size:1.05rem;color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:.5rem;}
.bk-topbar-right{display:flex;align-items:center;gap:1rem;}
</style>
</head>
<body>

<!-- NAV -->
<nav class="bk-topbar">
  <a class="bk-brand" href="/RMU-Medical-Management-System/"><i class="fas fa-heart-pulse"></i> RMU Sickbay</a>
  <div class="bk-topbar-right">
    <?php if ($is_logged_in): ?>
    <?php $back_url = $user_role==='doctor'?'/RMU-Medical-Management-System/php/dashboards/doctor_dashboard.php':'/RMU-Medical-Management-System/php/dashboards/patient_dashboard.php'; ?>
    <a href="<?php echo $back_url;?>" style="color:var(--text-secondary);text-decoration:none;font-size:.88rem;display:flex;align-items:center;gap:.4rem;"><i class="fas fa-arrow-left"></i> Dashboard</a>
    <?php else: ?>
    <a href="/RMU-Medical-Management-System/php/login.php" class="adm-btn adm-btn-primary adm-btn-sm">Login to Book</a>
    <?php endif; ?>
    <button id="themeToggle" class="adm-theme-toggle"><i class="fas fa-moon" id="themeIcon"></i></button>
  </div>
</nav>

<!-- HERO -->
<div class="bk-hero">
  <div class="bk-hero-orbs"><div class="bk-orb"></div><div class="bk-orb"></div><div class="bk-orb"></div></div>
  <div class="bk-hero-badge"><i class="fas fa-shield-heart"></i> Secure Online Booking</div>
  <h1><i class="fas fa-calendar-plus"></i> Book an Appointment</h1>
  <p>Choose your service, pick a doctor, and schedule your visit in minutes.</p>
</div>

<div class="bk-wrapper">

  <!-- STEPPER -->
  <div class="bk-stepper">
    <div class="bk-steps">
      <?php $steps=[['fa-stethoscope','Service'],['fa-user-doctor','Doctor'],['fa-calendar','Date & Time'],['fa-user','Details'],['fa-check','Confirm']]; foreach($steps as $i=>$s): $n=$i+1; ?>
      <div class="bk-step <?php echo $n===1?'active':'';?>" data-step="<?php echo $n;?>">
        <div class="bk-snum"><i class="fas fa-check" id="chk<?php echo $n;?>" style="display:none;"></i><span id="n<?php echo $n;?>"><?php echo $n;?></span></div>
        <div class="bk-slabel"><?php echo $s[1];?></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>

  <!-- STEP 1: Service -->
  <div class="bk-panel active" id="step1">
    <div class="bk-ptitle"><i class="fas fa-stethoscope"></i> What type of service do you need?</div>
    <div class="svc-grid">
      <?php $svcs=[
        ['General Consultation','fa-user-doctor','General check-up or illness query','#2563EB','rgba(37,99,235,.12)',true],
        ['Specialist Consultation','fa-microscope','Specific medical specialty','#7c3aed','rgba(124,58,237,.12)',false],
        ['Follow-up','fa-rotate-right','Review of a previous visit','#0891b2','rgba(8,145,178,.12)',false],
        ['Emergency Care','fa-truck-medical','Urgent same-day care','#ef4444','rgba(239,68,68,.12)',false],
        ['Health Checkup','fa-heart-pulse','Comprehensive wellness screen','#10b981','rgba(16,185,129,.12)',false],
        ['Mental Health','fa-brain','Counselling & support','#f59e0b','rgba(245,158,11,.12)',false],
      ]; foreach($svcs as $svc): ?>
      <div class="svc-card" data-service="<?php echo $svc[0];?>">
        <?php if($svc[4]??false): ?><div class="svc-badge">Popular</div><?php endif;?>
        <div class="svc-icon-wrap" style="background:<?php echo $svc[4];?>;color:<?php echo $svc[2];?>;"><i class="fas <?php echo $svc[1];?>"></i></div>
        <div class="sc-name"><?php echo $svc[0];?></div>
        <div class="sc-desc"><?php echo $svc[3];?></div>
      </div>
      <?php endforeach;?>
    </div>
    <div class="bk-nav"><span></span><button class="bk-btn btn-p" id="s1n" disabled>Next <i class="fas fa-arrow-right"></i></button></div>
  </div>

  <!-- STEP 2: Doctor -->
  <div class="bk-panel" id="step2">
    <div class="bk-ptitle"><i class="fas fa-user-doctor"></i> Choose Your Doctor</div>
    <?php if(empty($doctors)): ?>
    <div class="bk-banner"><i class="fas fa-info-circle"></i> No doctors are currently available. Please call our hotline: <strong>153</strong></div>
    <?php else: ?>
    <div class="doc-filter" id="specFilter">
      <button class="dfbtn active" data-spec="">All</button>
      <?php foreach(array_keys($specs) as $sp): ?><button class="dfbtn" data-spec="<?php echo htmlspecialchars($sp);?>"><?php echo htmlspecialchars($sp);?></button><?php endforeach;?>
    </div>
    <div class="doc-grid" id="doctorGrid">
      <?php foreach($doctors as $doc):
        $fee_label = $doc['consultation_fee']>0 ? 'GH₵ '.number_format($doc['consultation_fee'],2) : 'Free';
        $avail = $doc['available_days'] ?: 'Mon–Fri';
        $initials = strtoupper(substr($doc['name'],0,1));
        $stars = min(5, max(1, (int)($doc['experience_years']/3)+3));
      ?>
      <div class="doc-card" data-doc-id="<?php echo $doc['id'];?>" data-doc-name="Dr. <?php echo htmlspecialchars($doc['name']);?>" data-doc-spec="<?php echo htmlspecialchars($doc['specialization']);?>" data-doc-fee="<?php echo htmlspecialchars($fee_label);?>" data-specialization="<?php echo htmlspecialchars($doc['specialization']);?>">
        <div class="doc-avatar"><?php echo $initials;?><div class="doc-verified"><i class="fas fa-check"></i></div></div>
        <div class="doc-name">Dr. <?php echo htmlspecialchars($doc['name']);?></div>
        <div class="doc-spec"><?php echo htmlspecialchars($doc['specialization']);?></div>
        <div class="doc-stars"><?php echo str_repeat('★',$stars).str_repeat('☆',5-$stars);?></div>
        <div class="doc-meta"><span><i class="fas fa-calendar-week"></i> <?php echo htmlspecialchars($avail);?></span><?php if($doc['experience_years']): ?><span><i class="fas fa-briefcase"></i> <?php echo $doc['experience_years'];?> yrs</span><?php endif;?></div>
        <div class="doc-fee"><?php echo htmlspecialchars($fee_label);?></div>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>
    <div class="bk-nav">
      <button class="bk-btn btn-back" onclick="goStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
      <button class="bk-btn btn-p" id="s2n" disabled>Next <i class="fas fa-arrow-right"></i></button>
    </div>
  </div>

  <!-- STEP 3: Date & Time -->
  <div class="bk-panel" id="step3">
    <div class="bk-ptitle"><i class="fas fa-calendar-alt"></i> Select Date &amp; Time</div>
    <div class="cal-wrap" style="margin-bottom:1.2rem;">
      <div class="cal-header">
        <button class="cal-nav" id="calPrev"><i class="fas fa-chevron-left"></i></button>
        <span class="cal-month" id="calMonth"></span>
        <button class="cal-nav" id="calNext"><i class="fas fa-chevron-right"></i></button>
      </div>
      <div class="cal-grid">
        <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?><div class="cal-dow"><?php echo $d;?></div><?php endforeach;?>
        <div id="calDays" style="display:contents;"></div>
      </div>
    </div>
    <div id="slotArea"><p style="color:var(--text-muted);font-size:.86rem;text-align:center;padding:.5rem 0;">Select a date to see available slots</p></div>
    <div class="bk-nav">
      <button class="bk-btn btn-back" onclick="goStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
      <button class="bk-btn btn-p" id="s3n" disabled>Next <i class="fas fa-arrow-right"></i></button>
    </div>
  </div>

  <!-- STEP 4: Details -->
  <div class="bk-panel" id="step4">
    <div class="bk-ptitle"><i class="fas fa-user"></i> Your Details</div>
    <?php if($is_logged_in && $patient_info): ?>
    <div class="bk-banner"><i class="fas fa-user-check"></i> Details pre-filled from your profile. Please verify.</div>
    <?php endif;?>
    <div class="form-row">
      <div class="bk-fg">
        <label class="bk-lbl" for="patName">Full Name <span style="color:var(--bk-r);">*</span></label>
        <input type="text" id="patName" class="bk-inp" placeholder="Your full name" value="<?php echo htmlspecialchars($patient_info['name']??'');?>" required>
      </div>
      <div class="bk-fg">
        <label class="bk-lbl" for="patEmail">Email Address <span style="color:var(--bk-r);">*</span></label>
        <input type="email" id="patEmail" class="bk-inp" placeholder="your@email.com" value="<?php echo htmlspecialchars($patient_info['email']??'');?>" required>
      </div>
    </div>
    <div class="form-row">
      <div class="bk-fg">
        <label class="bk-lbl" for="patPhone">Phone Number <span style="color:var(--bk-r);">*</span></label>
        <input type="tel" id="patPhone" class="bk-inp" placeholder="0XX XXX XXXX" value="<?php echo htmlspecialchars($patient_info['phone']??'');?>" required>
      </div>
      <div class="bk-fg">
        <label class="bk-lbl" for="patGender">Gender</label>
        <select id="patGender" class="bk-inp">
          <option value="">Select gender</option>
          <option value="Male" <?php echo ($patient_info['gender']??'')==='Male'?'selected':'';?>>Male</option>
          <option value="Female" <?php echo ($patient_info['gender']??'')==='Female'?'selected':'';?>>Female</option>
          <option value="Other">Other</option>
        </select>
      </div>
    </div>
    <div class="bk-fg">
      <label class="bk-lbl" for="symptoms">Symptoms / Reason for Visit <span style="color:var(--bk-r);">*</span></label>
      <textarea id="symptoms" class="bk-inp" rows="3" placeholder="Briefly describe your symptoms or reason for this visit…" style="resize:vertical;"></textarea>
    </div>
    <div class="bk-fg">
      <label class="bk-lbl">Urgency Level</label>
      <div class="urg-grid">
        <div class="urg-card" data-val="Routine"><div class="urg-icon">🟢</div><div class="urg-title">Routine</div><div class="urg-sub">Standard appointment</div></div>
        <div class="urg-card" data-val="Urgent"><div class="urg-icon">🟡</div><div class="urg-title">Urgent</div><div class="urg-sub">Needs prompt attention</div></div>
        <div class="urg-card" data-val="Emergency"><div class="urg-icon">🔴</div><div class="urg-title">Emergency</div><div class="urg-sub">Immediate care required</div></div>
      </div>
      <input type="hidden" id="urgencyVal" value="Routine">
    </div>
    <div class="bk-nav">
      <button class="bk-btn btn-back" onclick="goStep(3)"><i class="fas fa-arrow-left"></i> Back</button>
      <button class="bk-btn btn-p" id="s4n">Review Booking <i class="fas fa-arrow-right"></i></button>
    </div>
  </div>

  <!-- STEP 5: Confirm -->
  <div class="bk-panel" id="step5">
    <div class="bk-ptitle"><i class="fas fa-check-circle"></i> Confirm Your Appointment</div>
    <div class="cf-card" style="margin-bottom:1rem;">
      <div class="cf-header"><i class="fas fa-calendar-check"></i><h3>Appointment Summary</h3></div>
      <div class="cf-body">
        <div class="cf-row"><span class="cf-label"><i class="fas fa-stethoscope"></i> Service</span><span class="cf-value" id="cf-service">—</span></div>
        <div class="cf-row"><span class="cf-label"><i class="fas fa-user-doctor"></i> Doctor</span><span class="cf-value" id="cf-doctor">—</span></div>
        <div class="cf-row"><span class="cf-label"><i class="fas fa-tag"></i> Specialization</span><span class="cf-value" id="cf-spec">—</span></div>
        <div class="cf-row"><span class="cf-label"><i class="fas fa-calendar"></i> Date</span><span class="cf-value" id="cf-date">—</span></div>
        <div class="cf-row"><span class="cf-label"><i class="fas fa-clock"></i> Time</span><span class="cf-value" id="cf-time">—</span></div>
        <div class="cf-row"><span class="cf-label"><i class="fas fa-user"></i> Patient</span><span class="cf-value" id="cf-name">—</span></div>
        <div class="cf-row"><span class="cf-label"><i class="fas fa-envelope"></i> Email</span><span class="cf-value" id="cf-email">—</span></div>
        <div class="cf-row"><span class="cf-label"><i class="fas fa-phone"></i> Phone</span><span class="cf-value" id="cf-phone">—</span></div>
        <div class="cf-row"><span class="cf-label"><i class="fas fa-exclamation-triangle"></i> Urgency</span><span class="cf-value" id="cf-urgency">—</span></div>
        <div class="cf-row"><span class="cf-label"><i class="fas fa-coins"></i> Fee</span><span class="cf-value" id="cf-fee" style="color:var(--bk-p);font-size:1rem;">—</span></div>
      </div>
    </div>
    <p style="font-size:.78rem;color:var(--text-muted);text-align:center;margin-bottom:1rem;">By confirming, you agree to our appointment policy. A confirmation email will be sent to you.</p>
    <div id="bk-err" style="display:none;" class="adm-alert adm-alert-danger"></div>
    <div class="bk-nav">
      <button class="bk-btn btn-back" onclick="goStep(4)"><i class="fas fa-arrow-left"></i> Edit</button>
      <button class="bk-btn btn-g" id="submitBtn" onclick="submitBooking()" style="flex:1;max-width:260px;justify-content:center;"><i class="fas fa-calendar-check"></i> Confirm Booking</button>
    </div>
  </div>

  <!-- SUCCESS -->
  <div class="bk-panel" id="stepSuccess">
    <div class="bk-success">
      <div class="success-circle"><i class="fas fa-check"></i></div>
      <h2>Appointment Booked! 🎉</h2>
      <p>Your appointment has been confirmed. Check your email for the confirmation details.</p>
      <div class="cf-card" id="successDetails" style="text-align:left;margin-bottom:1.5rem;"></div>
      <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
        <?php if($is_logged_in): ?>
        <a href="<?php echo $user_role==='doctor'?'/RMU-Medical-Management-System/php/dashboards/doctor_dashboard.php':'/RMU-Medical-Management-System/php/dashboards/patient_dashboard.php';?>" class="bk-btn btn-p"><i class="fas fa-home"></i> Dashboard</a>
        <?php endif;?>
        <button onclick="location.reload()" class="bk-btn btn-back"><i class="fas fa-calendar-plus"></i> Book Another</button>
      </div>
    </div>
  </div>

</div><!-- /bk-wrapper -->

<div class="confetti-wrap" id="confettiWrap" style="display:none;"></div>

<script>
/* ============================================================
   BOOKING WIZARD JAVASCRIPT
============================================================ */
const state = {
  step:1, service:'',
  doctorId:null, doctorName:'', doctorSpec:'', doctorFee:'',
  date:'', time:'', name:'', email:'', phone:'', gender:'', symptoms:'', urgency:'Routine'
};

// ── INIT default urgency ──────────────────────────────────────
document.querySelector('.urg-card[data-val="Routine"]').classList.add('sel-routine');

// ── STEP NAVIGATION ──────────────────────────────────────────
function goStep(n) {
  document.querySelectorAll('.bk-panel').forEach(p => p.classList.remove('active'));
  const panel = document.getElementById('step' + n);
  if (panel) panel.classList.add('active');
  document.querySelectorAll('.bk-step').forEach((el, i) => {
    const num = i + 1;
    el.classList.remove('active','done');
    const numEl = document.getElementById('n'+num);
    const chkEl = document.getElementById('chk'+num);
    if (num < n)        { el.classList.add('done');   if(chkEl){chkEl.style.display='inline';numEl.style.display='none';} }
    else if (num === n) { el.classList.add('active'); if(chkEl){chkEl.style.display='none';numEl.style.display='inline';} }
    else                { if(chkEl){chkEl.style.display='none';numEl.style.display='inline';} }
  });
  state.step = n;
  if (n === 5) populateConfirmation();
  window.scrollTo({top: document.querySelector('.bk-stepper').offsetTop - 80, behavior:'smooth'});
}

// ── STEP 1: Service ───────────────────────────────────────────
document.querySelectorAll('.svc-card').forEach(c => {
  c.addEventListener('click', () => {
    document.querySelectorAll('.svc-card').forEach(x => x.classList.remove('selected'));
    c.classList.add('selected');
    state.service = c.dataset.service;
    document.getElementById('s1n').disabled = false;
  });
});
document.getElementById('s1n').addEventListener('click', () => goStep(2));

// ── STEP 2: Doctor ────────────────────────────────────────────
document.querySelectorAll('.doc-card').forEach(c => {
  c.addEventListener('click', () => {
    document.querySelectorAll('.doc-card').forEach(x => x.classList.remove('selected'));
    c.classList.add('selected');
    state.doctorId   = c.dataset.docId;
    state.doctorName = c.dataset.docName;
    state.doctorSpec = c.dataset.docSpec;
    state.doctorFee  = c.dataset.docFee;
    document.getElementById('s2n').disabled = false;
  });
});
document.getElementById('s2n')?.addEventListener('click', () => goStep(3));

document.querySelectorAll('.dfbtn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.dfbtn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const spec = btn.dataset.spec;
    document.querySelectorAll('.doc-card').forEach(c => {
      c.style.display = (!spec || c.dataset.specialization === spec) ? 'flex' : 'none';
    });
  });
});

// ── STEP 3: Calendar ─────────────────────────────────────────
let calYear = new Date().getFullYear();
let calMonth = new Date().getMonth();
const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

function renderCalendar() {
  document.getElementById('calMonth').textContent = months[calMonth] + ' ' + calYear;
  const daysEl = document.getElementById('calDays');
  daysEl.innerHTML = '';
  const today = new Date(); today.setHours(0,0,0,0);
  const maxDate = new Date(today); maxDate.setMonth(maxDate.getMonth()+3);
  const firstDay = new Date(calYear, calMonth, 1).getDay();
  const daysInMonth = new Date(calYear, calMonth+1, 0).getDate();
  const prevDays = new Date(calYear, calMonth, 0).getDate();

  // prev month filler
  for(let i=firstDay-1;i>=0;i--){
    const d=document.createElement('div');
    d.className='cal-day cal-other';
    d.textContent=prevDays-i;
    daysEl.appendChild(d);
  }
  // current month
  for(let d=1;d<=daysInMonth;d++){
    const el=document.createElement('div');
    const thisDate=new Date(calYear,calMonth,d);
    const dateStr=`${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    let cls='cal-day';
    if(thisDate<today||thisDate>maxDate) cls+=' cal-disabled';
    if(thisDate.getTime()===today.getTime()) cls+=' cal-today';
    if(thisDate.getDay()===0||thisDate.getDay()===6) cls+=' cal-weekend';
    if(state.date===dateStr) cls+=' cal-selected';
    el.className=cls;
    el.textContent=d;
    if(!cls.includes('cal-disabled')){
      el.addEventListener('click',()=>{
        state.date=dateStr; state.time='';
        document.getElementById('s3n').disabled=true;
        document.querySelectorAll('.cal-day').forEach(x=>x.classList.remove('cal-selected'));
        el.classList.add('cal-selected');
        loadSlots(dateStr);
      });
    }
    daysEl.appendChild(el);
  }
}

document.getElementById('calPrev').addEventListener('click',()=>{
  calMonth--; if(calMonth<0){calMonth=11;calYear--;} renderCalendar();
});
document.getElementById('calNext').addEventListener('click',()=>{
  calMonth++; if(calMonth>11){calMonth=0;calYear++;} renderCalendar();
});
renderCalendar();

const AM=['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30'];
const PM=['13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30'];

function loadSlots(date){
  const area=document.getElementById('slotArea');
  area.innerHTML='<p style="color:var(--text-muted);text-align:center;padding:.5rem 0;"><i class="fas fa-spinner fa-spin"></i> Loading slots…</p>';
  fetch(`/RMU-Medical-Management-System/php/get_slots.php?doctor_id=${state.doctorId}&date=${date}`)
    .then(r=>r.json()).then(data=>renderSlots(data.booked||[]))
    .catch(()=>renderSlots([]));
}

function renderSlots(booked){
  const area=document.getElementById('slotArea');
  area.innerHTML='';
  [['Morning / AM',AM],['Afternoon / PM',PM]].forEach(([label,slots])=>{
    const lbl=document.createElement('div');
    lbl.className='slot-section-label'; lbl.textContent=label;
    area.appendChild(lbl);
    const grid=document.createElement('div'); grid.className='slot-grid';
    slots.forEach(slot=>{
      const el=document.createElement('div');
      const isB=booked.includes(slot);
      el.className='ts'+(isB?' booked':'');
      el.textContent=fmtTime(slot);
      if(!isB){
        el.addEventListener('click',()=>{
          document.querySelectorAll('.ts').forEach(s=>s.classList.remove('selected'));
          el.classList.add('selected');
          state.time=slot;
          document.getElementById('s3n').disabled=false;
        });
      }
      grid.appendChild(el);
    });
    area.appendChild(grid);
  });
}

function fmtTime(t){
  const [h,m]=t.split(':'); const hh=parseInt(h);
  return `${hh>12?hh-12:(hh===0?12:hh)}:${m} ${hh>=12?'PM':'AM'}`;
}
document.getElementById('s3n').addEventListener('click',()=>goStep(4));

// ── STEP 4: Urgency + Details ─────────────────────────────────
document.querySelectorAll('.urg-card').forEach(c=>{
  c.addEventListener('click',()=>{
    document.querySelectorAll('.urg-card').forEach(x=>x.classList.remove('sel-routine','sel-urgent','sel-emergency'));
    const v=c.dataset.val;
    state.urgency=v;
    document.getElementById('urgencyVal').value=v;
    const cls=v==='Emergency'?'sel-emergency':v==='Urgent'?'sel-urgent':'sel-routine';
    c.classList.add(cls);
  });
});

document.getElementById('s4n').addEventListener('click',()=>{
  state.name     = document.getElementById('patName').value.trim();
  state.email    = document.getElementById('patEmail').value.trim();
  state.phone    = document.getElementById('patPhone').value.trim();
  state.gender   = document.getElementById('patGender').value;
  state.symptoms = document.getElementById('symptoms').value.trim();
  if(!state.name||!state.email||!state.phone||!state.symptoms){
    alert('Please fill in all required fields (Name, Email, Phone, Symptoms).'); return;
  }
  goStep(5);
});

// ── STEP 5: Confirm ───────────────────────────────────────────
function populateConfirmation(){
  const fmt=d=>{if(!d)return'—';const dt=new Date(d+'T00:00:00');return dt.toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'});};
  document.getElementById('cf-service').textContent = state.service;
  document.getElementById('cf-doctor').textContent  = state.doctorName;
  document.getElementById('cf-spec').textContent    = state.doctorSpec;
  document.getElementById('cf-date').textContent    = fmt(state.date);
  document.getElementById('cf-time').textContent    = fmtTime(state.time);
  document.getElementById('cf-name').textContent    = state.name;
  document.getElementById('cf-email').textContent   = state.email;
  document.getElementById('cf-phone').textContent   = state.phone;
  document.getElementById('cf-urgency').textContent = state.urgency;
  document.getElementById('cf-fee').textContent     = state.doctorFee;
}

// ── Submit ────────────────────────────────────────────────────
async function submitBooking(){
  const btn=document.getElementById('submitBtn');
  btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Processing…';
  const errEl=document.getElementById('bk-err'); errEl.style.display='none';
  try{
    const res=await fetch('/RMU-Medical-Management-System/php/booking_handler.php',{
      method:'POST', headers:{'Content-Type':'application/json'},
      body:JSON.stringify({service:state.service,doctor_id:state.doctorId,appointment_date:state.date,appointment_time:state.time,patient_name:state.name,patient_email:state.email,patient_phone:state.phone,patient_gender:state.gender,symptoms:state.symptoms,urgency:state.urgency})
    });
    const data=await res.json();
    if(data.success){
      launchConfetti();
      document.querySelectorAll('.bk-panel').forEach(p=>p.classList.remove('active'));
      document.getElementById('stepSuccess').classList.add('active');
      document.getElementById('successDetails').innerHTML=`
        <div class="cf-header"><i class="fas fa-receipt"></i><h3>Reference: ${data.appointment_id}</h3></div>
        <div class="cf-body">
          <div class="cf-row"><span class="cf-label"><i class="fas fa-user-doctor"></i> Doctor</span><span class="cf-value">${state.doctorName}</span></div>
          <div class="cf-row"><span class="cf-label"><i class="fas fa-calendar"></i> Date & Time</span><span class="cf-value">${document.getElementById('cf-date').textContent} @ ${fmtTime(state.time)}</span></div>
          <div class="cf-row"><span class="cf-label"><i class="fas fa-stethoscope"></i> Service</span><span class="cf-value">${state.service}</span></div>
        </div>`;
      window.scrollTo({top:0,behavior:'smooth'});
    } else {
      errEl.innerHTML='<i class="fas fa-exclamation-triangle"></i> '+(data.message||'Booking failed. Please try again.');
      errEl.style.display='flex'; btn.disabled=false;
      btn.innerHTML='<i class="fas fa-calendar-check"></i> Confirm Booking';
    }
  }catch(e){
    errEl.innerHTML='<i class="fas fa-exclamation-triangle"></i> Network error. Please try again.';
    errEl.style.display='flex'; btn.disabled=false;
    btn.innerHTML='<i class="fas fa-calendar-check"></i> Confirm Booking';
  }
}

// ── Confetti ───────────────────────────────────────────────────
function launchConfetti(){
  const wrap=document.getElementById('confettiWrap');
  wrap.style.display='block';
  const colours=['#2563EB','#10b981','#f59e0b','#ef4444','#8b5cf6','#0ea5e9','#ec4899'];
  for(let i=0;i<55;i++){
    const p=document.createElement('div');
    p.className='cf-piece';
    p.style.cssText=`left:${Math.random()*100}%;background:${colours[i%colours.length]};width:${6+Math.random()*8}px;height:${10+Math.random()*8}px;animation-delay:${Math.random()*1.5}s;animation-duration:${2.5+Math.random()*2}s;border-radius:${Math.random()>0.5?'50%':'3px'};`;
    wrap.appendChild(p);
  }
  setTimeout(()=>{wrap.style.display='none';wrap.innerHTML='';},5000);
}

// ── Theme ───────────────────────────────────────────────────────
const html=document.documentElement;
const themeIcon=document.getElementById('themeIcon');
function applyTheme(t){html.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon';}
applyTheme(localStorage.getItem('rmu_theme')||'light');
document.getElementById('themeToggle')?.addEventListener('click',()=>applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
</script>
</body>
</html>
