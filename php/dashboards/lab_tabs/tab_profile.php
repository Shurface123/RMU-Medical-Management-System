<?php
// ═══════════ MODULE 15: ADVANCED LAB TECHNICIAN PROFILE ═══════════
if(!defined('BASE')) define('BASE','/RMU-Medical-Management-System');

// ── Fetch extended data ──────────────────────────────────────────
$prof_settings = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM lab_technician_settings WHERE technician_id=$tech_pk LIMIT 1"))??[];
$qualifications= [];
$q2=mysqli_query($conn,"SELECT * FROM lab_technician_qualifications WHERE technician_id=$tech_pk ORDER BY year_awarded DESC");
if($q2) while($r=mysqli_fetch_assoc($q2)) $qualifications[]=$r;
$certifications=[];
$q3=mysqli_query($conn,"SELECT * FROM lab_technician_certifications WHERE technician_id=$tech_pk ORDER BY expiry_date ASC");
if($q3) while($r=mysqli_fetch_assoc($q3)) $certifications[]=$r;
$documents=[];
$q4=mysqli_query($conn,"SELECT * FROM lab_technician_documents WHERE technician_id=$tech_pk ORDER BY uploaded_at DESC");
if($q4) while($r=mysqli_fetch_assoc($q4)) $documents[]=$r;
$sessions=[];
$q5=mysqli_query($conn,"SELECT * FROM lab_technician_sessions WHERE technician_id=$tech_pk ORDER BY last_active DESC LIMIT 10");
if($q5) while($r=mysqli_fetch_assoc($q5)) $sessions[]=$r;
$my_equipment=[];
$q6=mysqli_query($conn,"SELECT * FROM lab_equipment WHERE assigned_technician_id=$tech_pk ORDER BY status");
if($q6) while($r=mysqli_fetch_assoc($q6)) $my_equipment[]=$r;
$my_reagents=[];
$q7=mysqli_query($conn,"SELECT ri.* FROM reagent_inventory ri WHERE ri.linked_equipment_id IN (SELECT id FROM lab_equipment WHERE assigned_technician_id=$tech_pk) OR ri.id IN (SELECT reagent_id FROM reagent_transactions WHERE performed_by=$tech_pk AND created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY))");
if($q7) while($r=mysqli_fetch_assoc($q7)) $my_reagents[]=$r;
$recent_audit=[];
$q8=mysqli_query($conn,"SELECT * FROM lab_audit_trail WHERE technician_id=$tech_pk ORDER BY created_at DESC LIMIT 100");
if($q8) while($r=mysqli_fetch_assoc($q8)) $recent_audit[]=$r;

// ── Workload stats ────────────────────────────────────────────────
$ws=[
  'total_orders'    => (int)qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk","",""),
  'month_orders'    => (int)qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND created_at>='$month_start'","",""),
  'completed'       => (int)qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND order_status='Completed'","",""),
  'month_completed' => (int)qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND order_status='Completed' AND updated_at>='$month_start'","",""),
  'in_progress'     => (int)qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND order_status IN('Processing','Sample Collected','Accepted')","",""),
  'validated'       => (int)qv($conn,"SELECT COUNT(*) FROM lab_results_v2 WHERE technician_id=$tech_pk","",""),
  'month_validated' => (int)qv($conn,"SELECT COUNT(*) FROM lab_results_v2 WHERE technician_id=$tech_pk AND created_at>='$month_start'","",""),
  'critical'        => (int)qv($conn,"SELECT COUNT(*) FROM lab_results_v2 WHERE technician_id=$tech_pk AND result_interpretation='Critical'","",""),
  'month_critical'  => (int)qv($conn,"SELECT COUNT(*) FROM lab_results_v2 WHERE technician_id=$tech_pk AND result_interpretation='Critical' AND created_at>='$month_start'","",""),
  'amended'         => (int)qv($conn,"SELECT COUNT(*) FROM lab_results_v2 WHERE technician_id=$tech_pk AND result_status='Amended'","",""),
  'rejected_samples'=> (int)qv($conn,"SELECT COUNT(*) FROM lab_samples WHERE technician_id=$tech_pk AND status='Rejected'","",""),
  'total_samples'   => (int)qv($conn,"SELECT COUNT(*) FROM lab_samples WHERE technician_id=$tech_pk","",""),
  'calibrations'    => (int)qv($conn,"SELECT COUNT(*) FROM equipment_maintenance_log WHERE performed_by_id=$tech_pk AND maintenance_type='Calibration'","",""),
  'reagent_logs'    => (int)qv($conn,"SELECT COUNT(*) FROM reagent_transactions WHERE performed_by=$tech_pk","",""),
  'qc_passed'       => (int)qv($conn,"SELECT COUNT(*) FROM lab_quality_control WHERE technician_id=$tech_pk AND passed=1","",""),
  'qc_total'        => (int)qv($conn,"SELECT COUNT(*) FROM lab_quality_control WHERE technician_id=$tech_pk","",""),
  'avg_tat'         => (float)qv($conn,"SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR,created_at,updated_at)),1) FROM lab_test_orders WHERE technician_id=$tech_pk AND order_status='Completed'","",""),
];
$ws['rejection_rate'] = $ws['total_samples']>0 ? round(($ws['rejected_samples']/$ws['total_samples'])*100,1) : 0;
$ws['qc_pass_rate']   = $ws['qc_total']>0     ? round(($ws['qc_passed']/$ws['qc_total'])*100,1) : 0;

// ── Completeness engine ───────────────────────────────────────────
$completeness_checks=[
  'personal_info'        => !empty($tech_row['date_of_birth'])&&!empty($tech_row['phone'])&&!empty($tech_row['nationality']),
  'professional_profile' => !empty($tech_row['specialization'])&&!empty($tech_row['designation'])&&!empty($tech_row['license_number']),
  'qualifications'       => count($qualifications)>0,
  'equipment_assigned'   => count($my_equipment)>0,
  'shift_profile'        => !empty($tech_row['shift_preference_notes']),
  'photo_uploaded'       => !empty($tech_row['profile_photo']),
  'security_setup'       => !empty($tech_row['two_fa_enabled']),
  'documents_uploaded'   => count($documents)>0,
];
$completeness_pct = round((count(array_filter($completeness_checks))/count($completeness_checks))*100);

// ── License expiry alert ──────────────────────────────────────────
$lic_expiry_days = $tech_row['license_expiry'] ? round((strtotime($tech_row['license_expiry'])-time())/86400) : null;

// ── 7-day volume chart data ──────────────────────────────────────
$vol7_labels=[]; $vol7_data=[];
for($i=6;$i>=0;$i--){
  $d=date('Y-m-d',strtotime("-$i days"));
  $vol7_labels[]= '"'.date('D',strtotime($d)).'"';
  $vol7_data[]  = (int)qv($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=$tech_pk AND DATE(created_at)='$d'","","");
}
$vol7_labels=implode(',',$vol7_labels);
$vol7_data=implode(',',$vol7_data);

$avi = $tech_row['profile_photo']??null;
?>

<!-- ═══════════════════ PROFILE PAGE ═══════════════════ -->
<style>
.prof-nav{display:flex;overflow-x:auto;gap:.5rem;margin-bottom:2rem;padding-bottom:.5rem;border-bottom:2px solid var(--border);}
.prof-nav a{white-space:nowrap;padding:.6rem 1.4rem;border-radius:20px;font-size:1.2rem;font-weight:600;color:var(--text-secondary);text-decoration:none;transition:.2s;}
.prof-nav a.active,.prof-nav a:hover{background:var(--role-accent);color:#fff;}
.prof-section{display:none;}.prof-section.active{display:block;}
.info-row{display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid var(--border);font-size:1.25rem;}
.info-row:last-child{border:none;}
.info-row strong{color:var(--text-secondary);min-width:160px;}
.stat-card{background:var(--surface-2);border-radius:12px;padding:1.5rem;text-align:center;}
.stat-card .stat-num{font-size:2.2rem;font-weight:800;color:var(--role-accent);}
.stat-card .stat-lbl{font-size:1.1rem;color:var(--text-muted);margin-top:.2rem;}
.toggle-row{display:flex;justify-content:space-between;align-items:center;padding:.8rem 0;border-bottom:1px solid var(--border);font-size:1.25rem;}
.toggle-row:last-child{border:none;}
.prof-header-banner{background:linear-gradient(135deg,var(--role-accent),#A569BD);border-radius:16px;padding:2.5rem 2rem;color:#fff;margin-bottom:2rem;position:relative;overflow:hidden;}
.prof-header-banner::before{content:'';position:absolute;top:-50px;right:-50px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.1);}
.check-item{display:flex;justify-content:space-between;align-items:center;padding:.7rem 0;border-bottom:1px solid var(--border);font-size:1.25rem;}
.check-item:last-child{border:none;}
</style>

<!-- Section A: Identity Card Banner -->
<div class="prof-header-banner">
  <div style="display:flex;gap:2rem;align-items:center;flex-wrap:wrap;">
    <!-- Avatar -->
    <div style="position:relative;flex-shrink:0;">
      <div style="width:110px;height:110px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:3rem;color:#fff;font-weight:800;overflow:hidden;border:4px solid rgba(255,255,255,.5);">
        <?php if($avi):?><img src="<?=BASE?>/<?=e($avi)?>" style="width:100%;height:100%;object-fit:cover;">
        <?php else:?><?=strtoupper(substr($techName,0,2))?><?php endif;?>
      </div>
      <label style="position:absolute;bottom:2px;right:2px;width:30px;height:30px;border-radius:50%;background:#fff;color:var(--role-accent);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.1rem;" title="Change Photo">
        <i class="fas fa-camera"></i><input type="file" accept="image/jpeg,image/png" style="display:none;" onchange="uploadProfilePhoto(this)">
      </label>
    </div>
    <!-- Identity Info -->
    <div style="flex:1;min-width:200px;">
      <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:.4rem;">
        <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><?=e($techName)?></h2>
        <span style="background:rgba(255,255,255,.25);padding:.3rem .9rem;border-radius:20px;font-size:1.2rem;font-weight:700;"><?=e($tech_row['designation']??'Lab Technician')?></span>
      </div>
      <p style="opacity:.85;font-size:1.3rem;margin-bottom:.4rem;"><i class="fas fa-id-badge"></i> <?=e($tech_row['technician_id']??'LAB-TECH')?> &bull; <i class="fas fa-flask"></i> <?=e($tech_row['specialization']??'General Laboratory')?></p>
      <div style="display:flex;gap:.8rem;flex-wrap:wrap;">
        <select id="availabilityToggle" class="form-control" style="max-width:180px;height:32px;padding:0 .5rem;font-size:1.2rem;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.4);color:#fff;border-radius:20px;" onchange="updateAvailability(this.value)">
          <?php foreach(['Available','Busy','On Break','Off Duty'] as $av):?>
          <option value="<?=$av?>" <?=($tech_row['availability_status']??'Available')===$av?'selected':''?> style="color:var(--text-primary);"><?=$av?></option>
          <?php endforeach;?>
        </select>
        <span style="background:rgba(255,255,255,.25);padding:.3rem .9rem;border-radius:20px;font-size:1.2rem;"><i class="fas fa-tasks"></i> <?=$ws['in_progress']?> active orders</span>
        <span style="background:rgba(255,255,255,.25);padding:.3rem .9rem;border-radius:20px;font-size:1.2rem;"><i class="fas fa-calendar-alt"></i> Since <?=$tech_row['member_since']?date('M Y',strtotime($tech_row['member_since'])):'—'?></span>
        <?php if($lic_expiry_days!==null&&$lic_expiry_days<=60):?>
        <span style="background:<?=$lic_expiry_days<=0?'#e74c3c':'#e67e22'?>;padding:.3rem .9rem;border-radius:20px;font-size:1.2rem;font-weight:700;"><i class="fas fa-exclamation-triangle"></i> License <?=$lic_expiry_days<=0?'Expired':'Expires in '.$lic_expiry_days.' days'?></span>
        <?php endif;?>
      </div>
    </div>
    <!-- Completeness Ring -->
    <div style="text-align:center;flex-shrink:0;">
      <svg width="90" height="90" style="transform:rotate(-90deg);">
        <circle cx="45" cy="45" r="38" fill="none" stroke="rgba(255,255,255,.2)" stroke-width="8"/>
        <circle cx="45" cy="45" r="38" fill="none" stroke="#fff" stroke-width="8" stroke-dasharray="<?=round(238*$completeness_pct/100)?> 238" stroke-linecap="round"/>
      </svg>
      <div style="margin-top:-70px;font-size:2rem;font-weight:800;"><?=$completeness_pct?>%</div>
      <div style="font-size:1.1rem;opacity:.8;margin-top:52px;">Profile Complete</div>
    </div>
  </div>
</div>

<!-- Profile Section Navigation -->
<nav class="prof-nav">
  <a href="#" class="active" onclick="showProfSection('sec-personal',this)"><i class="fas fa-user"></i> Personal</a>
  <a href="#" onclick="showProfSection('sec-professional',this)"><i class="fas fa-briefcase"></i> Professional</a>
  <a href="#" onclick="showProfSection('sec-qualifications',this)"><i class="fas fa-graduation-cap"></i> Qualifications</a>
  <a href="#" onclick="showProfSection('sec-stats',this)"><i class="fas fa-chart-bar"></i> Workload Stats</a>
  <a href="#" onclick="showProfSection('sec-responsibility',this)"><i class="fas fa-microscope"></i> Equipment & Reagents</a>
  <a href="#" onclick="showProfSection('sec-shift',this)"><i class="fas fa-clock"></i> Shift & Availability</a>
  <a href="#" onclick="showProfSection('sec-security',this)"><i class="fas fa-shield-alt"></i> Security</a>
  <a href="#" onclick="showProfSection('sec-notifications',this)"><i class="fas fa-bell"></i> Notifications</a>
  <a href="#" onclick="showProfSection('sec-documents',this)"><i class="fas fa-folder"></i> Documents</a>
  <a href="#" onclick="showProfSection('sec-audit',this)"><i class="fas fa-history"></i> Audit Trail</a>
  <a href="#" onclick="showProfSection('sec-completeness',this)"><i class="fas fa-tasks"></i> Profile Checklist</a>
</nav>

<!-- ═══ SECTION B: PERSONAL INFORMATION ═══ -->
<div class="prof-section active" id="sec-personal">
  <div class="adm-card">
    <div class="adm-card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3><i class="fas fa-user" style="color:var(--role-accent);"></i> Personal Information</h3>
      <button class="adm-btn adm-btn-ghost adm-btn-sm" onclick="openModal('editPersonalModal')"><i class="fas fa-edit"></i> Edit</button>
    </div>
    <div class="adm-card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
        <div>
          <div class="info-row"><strong>Full Name</strong> <span><?=e($techName)?></span></div>
          <div class="info-row"><strong>Date of Birth</strong> <span><?=$tech_row['date_of_birth']?date('d M Y',strtotime($tech_row['date_of_birth'])):'—'?></span></div>
          <div class="info-row"><strong>Age</strong> <span><?=$tech_row['date_of_birth']?date_diff(date_create($tech_row['date_of_birth']),date_create('today'))->y.' years':'—'?></span></div>
          <div class="info-row"><strong>Gender</strong> <span><?=e($tech_row['gender']??'—')?></span></div>
          <div class="info-row"><strong>Nationality</strong> <span><?=e($tech_row['nationality']??'—')?></span></div>
          <div class="info-row"><strong>Marital Status</strong> <span><?=e($tech_row['marital_status']??'—')?></span></div>
          <div class="info-row"><strong>Religion</strong> <span><?=e($tech_row['religion']??'—')?></span></div>
          <div class="info-row"><strong>National ID</strong> <span><?=e($tech_row['national_id']??'—')?></span></div>
        </div>
        <div>
          <div class="info-row"><strong>Primary Phone</strong> <span><?=e($tech_row['phone']??'—')?></span></div>
          <div class="info-row"><strong>Secondary Phone</strong> <span><?=e($tech_row['secondary_phone']??'—')?></span></div>
          <div class="info-row"><strong>Official Email</strong> <span><?=e($tech_row['email']??'—')?></span></div>
          <div class="info-row"><strong>Personal Email</strong> <span><?=e($tech_row['personal_email']??'—')?></span></div>
          <div class="info-row"><strong>Street Address</strong> <span><?=e($tech_row['street_address']??'—')?></span></div>
          <div class="info-row"><strong>City</strong> <span><?=e($tech_row['city']??'—')?></span></div>
          <div class="info-row"><strong>Region</strong> <span><?=e($tech_row['region']??'—')?></span></div>
          <div class="info-row"><strong>Country</strong> <span><?=e($tech_row['country']??'Ghana')?></span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ SECTION C: PROFESSIONAL PROFILE ═══ -->
<div class="prof-section" id="sec-professional">
  <div class="adm-card">
    <div class="adm-card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3><i class="fas fa-briefcase" style="color:var(--role-accent);"></i> Professional Profile</h3>
      <button class="adm-btn adm-btn-ghost adm-btn-sm" onclick="openModal('editProfModal')"><i class="fas fa-edit"></i> Edit</button>
    </div>
    <div class="adm-card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
        <div>
          <div class="info-row"><strong>Designation</strong> <span><b style="color:var(--role-accent);"><?=e($tech_row['designation']??'Lab Technician')?></b></span></div>
          <div class="info-row"><strong>Specialization</strong> <span><?=e($tech_row['specialization']??'—')?></span></div>
          <div class="info-row"><strong>Sub-Specialization</strong> <span><?=e($tech_row['sub_specialization']??'—')?></span></div>
          <div class="info-row"><strong>Department</strong> <span><?=e($tech_row['department_name']??'—')?></span></div>
          <div class="info-row"><strong>Years of Experience</strong> <span><?=$tech_row['years_of_experience']??0?> years</span></div>
          <div class="info-row"><strong>Languages Spoken</strong> <span><?=e(is_array($tech_row['languages_spoken'])?implode(', ',json_decode($tech_row['languages_spoken']??'[]',true)):($tech_row['languages_spoken']??'—'))?></span></div>
        </div>
        <div>
          <div class="info-row"><strong>License Number</strong> <span style="font-family:monospace;"><?=e($tech_row['license_number']??'—')?></span></div>
          <div class="info-row"><strong>Issuing Body</strong> <span><?=e($tech_row['license_issuing_body']??'—')?></span></div>
          <div class="info-row"><strong>License Expiry</strong>
            <span><?=$tech_row['license_expiry']?date('d M Y',strtotime($tech_row['license_expiry'])):'—'?>
            <?php if($lic_expiry_days!==null&&$lic_expiry_days<=60):?>&nbsp;<span class="adm-badge adm-badge-<?=$lic_expiry_days<=0?'danger':'warning'?>"><?=$lic_expiry_days<=0?'Expired':'Exp. soon'?></span><?php endif;?>
            </span></div>
          <div class="info-row"><strong>Institution Attended</strong> <span><?=e($tech_row['institution_attended']??'—')?></span></div>
          <div class="info-row"><strong>Graduation Year</strong> <span><?=e($tech_row['graduation_year']??'—')?></span></div>
          <div class="info-row"><strong>Postgrad Training</strong> <span><?=e($tech_row['postgraduate_details']??'—')?></span></div>
        </div>
      </div>
      <?php if(!empty($tech_row['bio'])):?>
      <div style="margin-top:1.5rem;padding:1.5rem;background:var(--surface-2);border-radius:10px;">
        <strong style="display:block;margin-bottom:.5rem;">Professional Summary</strong>
        <p style="font-size:1.25rem;line-height:1.7;"><?=nl2br(e($tech_row['bio']))?></p>
      </div>
      <?php endif;?>
    </div>
  </div>
</div>

<!-- ═══ SECTION D: QUALIFICATIONS & CERTIFICATIONS ═══ -->
<div class="prof-section" id="sec-qualifications">
  <div style="display:grid;gap:2rem;">
    <!-- Qualifications -->
    <div class="adm-card">
      <div class="adm-card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3><i class="fas fa-graduation-cap" style="color:var(--role-accent);"></i> Academic Qualifications</h3>
        <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="openModal('addQualModal')"><i class="fas fa-plus"></i> Add</button>
      </div>
      <div class="adm-card-body" style="padding:0;">
        <div class="adm-table-wrap">
          <table class="adm-table">
            <thead><tr><th>Degree/Certificate</th><th>Institution</th><th>Year</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if(empty($qualifications)):?>
            <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:2rem;">No qualifications added yet</td></tr>
            <?php else: foreach($qualifications as $qf):?>
            <tr>
              <td><strong><?=e($qf['degree_name'])?></strong></td>
              <td><?=e($qf['institution'])?></td>
              <td><?=$qf['year_awarded']??'—'?></td>
              <td class="adm-table-actions">
                <?php if($qf['certificate_file']):?><a href="<?=BASE?>/<?=e($qf['certificate_file'])?>" target="_blank" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fas fa-download"></i></a><?php endif;?>
                <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="deleteQualification(<?=$qf['id']?>)"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
            <?php endforeach; endif;?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Certifications -->
    <div class="adm-card">
      <div class="adm-card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3><i class="fas fa-certificate" style="color:var(--role-accent);"></i> Professional Certifications</h3>
        <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="openModal('addCertModal')"><i class="fas fa-plus"></i> Add</button>
      </div>
      <div class="adm-card-body" style="padding:0;">
        <div class="adm-table-wrap">
          <table class="adm-table">
            <thead><tr><th>Certification</th><th>Issuing Body</th><th>Issue Date</th><th>Expires</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if(empty($certifications)):?>
            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">No certifications added yet</td></tr>
            <?php else: foreach($certifications as $cf):
              $days_left=$cf['expiry_date']?round((strtotime($cf['expiry_date'])-time())/86400):null;
            ?>
            <tr>
              <td><strong><?=e($cf['certification_name'])?></strong></td>
              <td><?=e($cf['issuing_body']??'—')?></td>
              <td><?=$cf['issue_date']?date('d M Y',strtotime($cf['issue_date'])):'—'?></td>
              <td><?=$cf['expiry_date']?date('d M Y',strtotime($cf['expiry_date'])):'—'?>
                <?php if($days_left!==null&&$days_left<=60):?><span class="adm-badge adm-badge-<?=$days_left<=0?'danger':'warning'?>"><?=$days_left<=0?'Expired':'Soon'?></span><?php endif;?>
              </td>
              <td class="adm-table-actions">
                <?php if($cf['certificate_file']):?><a href="<?=BASE?>/<?=e($cf['certificate_file'])?>" target="_blank" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fas fa-download"></i></a><?php endif;?>
                <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="deleteCertification(<?=$cf['id']?>)"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
            <?php endforeach; endif;?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ SECTION E: WORKLOAD & PERFORMANCE STATISTICS ═══ -->
<div class="prof-section" id="sec-stats">
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1.5rem;margin-bottom:2rem;">
    <div class="stat-card"><div class="stat-num"><?=$ws['total_orders']?></div><div class="stat-lbl">Total Orders</div></div>
    <div class="stat-card"><div class="stat-num"><?=$ws['month_orders']?></div><div class="stat-lbl">This Month</div></div>
    <div class="stat-card"><div class="stat-num"><?=$ws['completed']?></div><div class="stat-lbl">Completed</div></div>
    <div class="stat-card"><div class="stat-num"><?=$ws['in_progress']?></div><div class="stat-lbl">In Progress</div></div>
    <div class="stat-card"><div class="stat-num"><?=$ws['validated']?></div><div class="stat-lbl">Results Validated</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--danger);"><?=$ws['critical']?></div><div class="stat-lbl">Critical Results</div></div>
    <div class="stat-card"><div class="stat-num"><?=$ws['amended']?></div><div class="stat-lbl">Amended Results</div></div>
    <div class="stat-card"><div class="stat-num"><?=$ws['rejection_rate']?>%</div><div class="stat-lbl">Rejection Rate</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--success);"><?=$ws['qc_pass_rate']?>%</div><div class="stat-lbl">QC Pass Rate</div></div>
    <div class="stat-card"><div class="stat-num"><?=$ws['avg_tat']?></div><div class="stat-lbl">Avg TAT (hrs)</div></div>
    <div class="stat-card"><div class="stat-num"><?=$ws['calibrations']?></div><div class="stat-lbl">Calibrations Logged</div></div>
    <div class="stat-card"><div class="stat-num"><?=$ws['reagent_logs']?></div><div class="stat-lbl">Reagent Usage Logs</div></div>
  </div>
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;">
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3>Test Volume — Last 7 Days</h3></div>
      <div class="adm-card-body" style="height:200px;position:relative;">
        <canvas id="profVolumeChart"></canvas>
      </div>
    </div>
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3>Status Distribution</h3></div>
      <div class="adm-card-body" style="height:200px;position:relative;">
        <canvas id="profStatusChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- ═══ SECTION F: EQUIPMENT & REAGENT RESPONSIBILITY ═══ -->
<div class="prof-section" id="sec-responsibility">
  <div style="display:grid;gap:2rem;">
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3><i class="fas fa-microscope" style="color:var(--role-accent);"></i> Assigned Equipment</h3></div>
      <div class="adm-card-body" style="padding:0;">
        <div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Name</th><th>Model</th><th>Status</th><th>Last Calibration</th><th>Next Due</th><th>Actions</th></tr></thead><tbody>
        <?php if(empty($my_equipment)):?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No equipment currently assigned</td></tr>
        <?php else: foreach($my_equipment as $eq):
          $cal_due=$eq['next_calibration_date']?round((strtotime($eq['next_calibration_date'])-time())/86400):null;
        ?>
        <tr>
          <td><strong><?=e($eq['name'])?></strong></td>
          <td><?=e($eq['model']??'—')?></td>
          <td><span class="adm-badge adm-badge-<?=(['Operational'=>'success','Maintenance'=>'warning','Calibration Due'=>'warning','Out of Service'=>'danger','Decommissioned'=>'info'][$eq['status']]??'info')?>"><?=e($eq['status'])?></span></td>
          <td><?=$eq['last_calibration_date']?date('d M Y',strtotime($eq['last_calibration_date'])):'Never'?></td>
          <td><?=$eq['next_calibration_date']?date('d M Y',strtotime($eq['next_calibration_date'])):'—'?>
            <?php if($cal_due!==null&&$cal_due<=7):?>&nbsp;<span class="adm-badge adm-badge-<?=$cal_due<=0?'danger':'warning'?>"><?=$cal_due<=0?'Overdue':'Due soon'?></span><?php endif;?>
          </td>
          <td><button class="adm-btn adm-btn-sm adm-btn-ghost" onclick="showTab('equipment',null)" title="Go to Equipment tab"><i class="fas fa-external-link-alt"></i></button></td>
        </tr>
        <?php endforeach; endif;?>
        </tbody></table></div>
      </div>
    </div>
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3><i class="fas fa-prescription-bottle" style="color:var(--role-accent);"></i> Reagent Responsibility</h3></div>
      <div class="adm-card-body" style="padding:0;">
        <div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Reagent</th><th>Stock</th><th>Reorder Level</th><th>Expiry</th><th>Status</th></tr></thead><tbody>
        <?php if(empty($my_reagents)):?>
        <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted);">No reagents found</td></tr>
        <?php else: foreach($my_reagents as $rg):
          $re_days=$rg['expiry_date']?round((strtotime($rg['expiry_date'])-time())/86400):null;
          $re_cls=['In Stock'=>'success','Low Stock'=>'warning','Out of Stock'=>'danger','Expired'=>'danger','Expiring Soon'=>'warning'][$rg['status']]??'info';
        ?>
        <tr>
          <td><strong><?=e($rg['name'])?></strong></td>
          <td style="font-weight:700;color:<?=$rg['quantity_in_stock']<=$rg['reorder_level']?'var(--danger)':'var(--text-primary)'?>;"><?=$rg['quantity_in_stock']?> <?=e($rg['unit'])?></td>
          <td><?=$rg['reorder_level']?></td>
          <td><?=$rg['expiry_date']?date('d M Y',strtotime($rg['expiry_date'])):'—'?>
            <?php if($re_days!==null&&$re_days<=30):?>&nbsp;<span class="adm-badge adm-badge-<?=$re_days<=0?'danger':'warning'?>"><?=$re_days<=0?'Expired':'Soon'?></span><?php endif;?>
          </td>
          <td><span class="adm-badge adm-badge-<?=$re_cls?>"><?=e($rg['status'])?></span></td>
        </tr>
        <?php endforeach; endif;?>
        </tbody></table></div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ SECTION G: SHIFT & AVAILABILITY ═══ -->
<div class="prof-section" id="sec-shift">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-clock" style="color:var(--role-accent);"></i> Shift &amp; Availability Profile</h3></div>
    <div class="adm-card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
        <div>
          <div class="info-row"><strong>Availability Status</strong><span><span class="adm-badge adm-badge-<?=($tech_row['availability_status']??'Available')==='Available'?'success':'warning'?>"><?=e($tech_row['availability_status']??'Available')?></span></span></div>
          <div class="info-row"><strong>Office / Lab Location</strong><span><?=e($tech_row['office_location']??'—')?></span></div>
          <div class="info-row"><strong>Last Login</strong><span><?=$tech_row['last_login']?date('d M Y H:i',strtotime($tech_row['last_login'])):'—'?></span></div>
          <div class="info-row"><strong>Member Since</strong><span><?=$tech_row['member_since']?date('d M Y',strtotime($tech_row['member_since'])):'—'?></span></div>
        </div>
        <div>
          <label style="font-weight:600;font-size:1.25rem;margin-bottom:.5rem;display:block;">Shift Preference Notes <small style="color:var(--text-muted);">(visible to Admin for scheduling)</small></label>
          <textarea id="shiftNotesInput" class="form-control" rows="5" placeholder="e.g. Prefer morning shifts, available for on-call on weekends..."><?=e($tech_row['shift_preference_notes']??'')?></textarea>
          <button class="adm-btn adm-btn-primary" style="margin-top:1rem;width:100%;" onclick="saveShiftNotes()"><i class="fas fa-save"></i> Save Shift Notes</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ SECTION H: ACCOUNT & SECURITY ═══ -->
<div class="prof-section" id="sec-security">
  <div style="display:grid;gap:2rem;">
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3><i class="fas fa-lock" style="color:var(--role-accent);"></i> Change Password</h3></div>
      <div class="adm-card-body">
        <div class="form-row">
          <div class="form-group"><label>Current Password</label><input type="password" id="sec_old_pw" class="form-control" placeholder="Enter current password"></div>
          <div class="form-group"><label>New Password</label><input type="password" id="sec_new_pw" class="form-control" placeholder="Min 8 chars" oninput="checkPwStrength(this.value)"></div>
          <div class="form-group"><label>Confirm New Password</label><input type="password" id="sec_confirm_pw" class="form-control"></div>
        </div>
        <div id="pwStrengthBar" style="height:6px;border-radius:4px;background:var(--surface-2);transition:.3s;margin-bottom:.4rem;"><div id="pwStrengthFill" style="height:100%;width:0%;border-radius:4px;transition:.3s;"></div></div>
        <div id="pwStrengthLabel" style="font-size:1.1rem;margin-bottom:1rem;color:var(--text-muted);"></div>
        <button class="adm-btn adm-btn-primary" onclick="changePassword()"><i class="fas fa-key"></i> Update Password</button>
      </div>
    </div>
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3><i class="fas fa-shield-alt" style="color:var(--role-accent);"></i> Two-Factor Authentication (2FA)</h3></div>
      <div class="adm-card-body">
        <div style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;">
          <p style="font-size:1.25rem;flex:1;">Enable 2FA for enhanced login security. When active, you'll need an authenticator code each time you log in.</p>
          <label style="display:flex;align-items:center;gap:.8rem;cursor:pointer;font-size:1.25rem;font-weight:600;">
            <input type="checkbox" id="twoFaToggle" <?=!empty($tech_row['two_fa_enabled'])?'checked':''?> onchange="toggleTwoFA(this.checked)" style="display:none;">
            <div id="twoFaVisual" style="width:50px;height:26px;border-radius:13px;background:<?=!empty($tech_row['two_fa_enabled'])?'var(--success)':'var(--border)'?>;position:relative;transition:.3s;cursor:pointer;" onclick="document.getElementById('twoFaToggle').click();">
              <div style="position:absolute;top:3px;left:<?=!empty($tech_row['two_fa_enabled'])?'27':'3'?>px;width:20px;height:20px;border-radius:50%;background:#fff;transition:.3s;"></div>
            </div>
            <span><?=!empty($tech_row['two_fa_enabled'])?'Enabled':'Disabled'?></span>
          </label>
        </div>
      </div>
    </div>
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3><i class="fas fa-desktop" style="color:var(--role-accent);"></i> Active Sessions</h3>
        <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="logoutAllSessions()"><i class="fas fa-sign-out-alt"></i> Log Out All Others</button>
      </div>
      <div class="adm-card-body" style="padding:0;">
        <div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Device</th><th>Browser</th><th>IP Address</th><th>Login Time</th><th>Last Active</th><th>Action</th></tr></thead><tbody>
        <?php if(empty($sessions)):?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No session records found</td></tr>
        <?php else: foreach($sessions as $ss):?>
        <tr>
          <td><?=e($ss['device']??'Unknown')?> <?php if($ss['is_current']):?><span class="adm-badge adm-badge-success">Current</span><?php endif;?></td>
          <td><?=e($ss['browser']??'—')?></td>
          <td style="font-family:monospace;"><?=e($ss['ip_address']??'—')?></td>
          <td><?=$ss['login_time']?date('d M H:i',strtotime($ss['login_time'])):'—'?></td>
          <td><?=$ss['last_active']?date('d M H:i',strtotime($ss['last_active'])):'—'?></td>
          <td><?php if(!$ss['is_current']):?><button class="adm-btn adm-btn-sm adm-btn-danger" onclick="logoutSession(<?=$ss['id']?>)"><i class="fas fa-sign-out-alt"></i></button><?php endif;?></td>
        </tr>
        <?php endforeach; endif;?>
        </tbody></table></div>
      </div>
    </div>
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3><i class="fas fa-list-alt" style="color:var(--role-accent);"></i> Recent Account Activity</h3></div>
      <div class="adm-card-body" style="padding:0;">
        <div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Action</th><th>Module</th><th>Timestamp</th><th>IP</th></tr></thead><tbody>
        <?php foreach(array_slice($recent_audit,0,15) as $al):?>
        <tr><td><?=e($al['action_type']??'—')?></td><td><?=e($al['module_affected']??'—')?></td><td><?=$al['created_at']?date('d M Y H:i',strtotime($al['created_at'])):'—'?></td><td style="font-family:monospace;"><?=e($al['ip_address']??'—')?></td></tr>
        <?php endforeach;?>
        </tbody></table></div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ SECTION I: NOTIFICATION PREFERENCES ═══ -->
<div class="prof-section" id="sec-notifications">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-bell" style="color:var(--role-accent);"></i> Notification &amp; Communication Preferences</h3></div>
    <div class="adm-card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 4rem;">
        <?php $notif_map=['notif_new_order'=>'New test order received','notif_stat_order'=>'Urgent / STAT order alerts','notif_critical_result'=>'Critical value reminders','notif_equipment_alert'=>'Equipment calibration due alerts','notif_reagent_alert'=>'Reagent low stock alerts','notif_reagent_expiry'=>'Reagent expiry alerts','notif_result_amend'=>'Result amendment notifications','notif_doctor_msg'=>'Doctor messages &amp; clarification requests','notif_qc_reminder'=>'QC failure alerts','notif_license_expiry'=>'License / Certification expiry warnings','notif_shift_reminder'=>'Shift schedule reminders','notif_system'=>'System announcements from admin'];
        foreach($notif_map as $key=>$label):?>
        <div class="toggle-row">
          <span><?=$label?></span>
          <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
            <input type="checkbox" <?=!empty($prof_settings[$key])?'checked':''?> onchange="saveNotifToggle('<?=$key?>',this.checked?1:0)" style="width:18px;height:18px;cursor:pointer;">
          </label>
        </div>
        <?php endforeach;?>
      </div>
      <hr style="margin:2rem 0;border-color:var(--border);">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:end;">
        <div class="form-group" style="margin:0;">
          <label>Preferred Communication Channel</label>
          <select class="form-control" onchange="saveNotifToggle('preferred_channel',this.value)">
            <?php foreach(['In-Dashboard','Email','SMS','Email & In-Dashboard'] as $ch):?>
            <option <?=($prof_settings['preferred_channel']??'In-Dashboard')===$ch?'selected':''?>><?=$ch?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div>
          <div class="toggle-row" style="border:none;">
            <span style="font-size:1.25rem;">Alert Sound for Critical Notifications</span>
            <input type="checkbox" <?=!empty($prof_settings['alert_sound_enabled'])?'checked':''?> onchange="saveNotifToggle('alert_sound_enabled',this.checked?1:0)" style="width:20px;height:20px;cursor:pointer;">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ SECTION J: DOCUMENTS & UPLOADS ═══ -->
<div class="prof-section" id="sec-documents">
  <div class="adm-card">
    <div class="adm-card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3><i class="fas fa-folder-open" style="color:var(--role-accent);"></i> Professional Documents</h3>
      <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="openModal('uploadDocModal')"><i class="fas fa-upload"></i> Upload Document</button>
    </div>
    <div class="adm-card-body" style="padding:0;">
      <div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Document</th><th>Type</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody>
      <?php if(empty($documents)):?>
      <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted);">No documents uploaded yet</td></tr>
      <?php else: foreach($documents as $doc):?>
      <tr>
        <td><i class="fas fa-file-alt" style="color:var(--role-accent);margin-right:.5rem;"></i><strong><?=e($doc['document_name'])?></strong><?php if($doc['description']):?><br><small style="color:var(--text-muted);"><?=e(substr($doc['description'],0,60))?></small><?php endif;?></td>
        <td><?=e($doc['document_type']??'Other')?></td>
        <td><?=number_format($doc['file_size']/1024,1)?> KB</td>
        <td><?=$doc['uploaded_at']?date('d M Y',strtotime($doc['uploaded_at'])):'—'?></td>
        <td class="adm-table-actions">
          <a href="<?=BASE?>/<?=e($doc['file_path'])?>" target="_blank" class="adm-btn adm-btn-sm adm-btn-ghost" title="Download"><i class="fas fa-download"></i></a>
          <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="deleteDocument(<?=$doc['id']?>)"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
      <?php endforeach; endif;?>
      </tbody></table></div>
    </div>
  </div>
</div>

<!-- ═══ SECTION K: PERSONAL AUDIT TRAIL ═══ -->
<div class="prof-section" id="sec-audit">
  <div class="adm-card">
    <div class="adm-card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3><i class="fas fa-history" style="color:var(--role-accent);"></i> My Personal Audit Trail</h3>
      <span class="adm-badge adm-badge-info">Read-only — Immutable</span>
    </div>
    <div style="padding:1rem 1.5rem;background:var(--surface-2);display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;border-bottom:1px solid var(--border);">
      <div class="form-group" style="margin:0;flex:1;min-width:140px;"><label>Module</label>
        <select id="auditModFilter" class="form-control" onchange="filterAuditTable()">
          <option value="">All Modules</option>
          <?php $mods=array_unique(array_column($recent_audit,'module_affected')); foreach($mods as $m):if($m):?><option><?=e($m)?></option><?php endif;endforeach;?>
        </select>
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:140px;"><label>Date From</label><input type="date" id="auditFrom" class="form-control" onchange="filterAuditTable()"></div>
      <div class="form-group" style="margin:0;flex:1;min-width:140px;"><label>Date To</label><input type="date" id="auditTo" class="form-control" onchange="filterAuditTable()"></div>
    </div>
    <div class="adm-card-body" style="padding:0;">
      <div class="adm-table-wrap"><table class="adm-table" id="auditPersonalTable"><thead><tr><th>Action</th><th>Module</th><th>Record</th><th>IP Address</th><th>Timestamp</th></tr></thead><tbody>
      <?php if(empty($recent_audit)):?>
      <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted);">No audit records yet</td></tr>
      <?php else: foreach($recent_audit as $al):?>
      <tr data-module="<?=e($al['module_affected']??'')?>" data-date="<?=substr($al['created_at']??'',0,10)?>">
        <td><?=e($al['action_type']??'—')?></td>
        <td><?=e($al['module_affected']??'—')?></td>
        <td style="font-family:monospace;"><?=$al['record_id']?'#'.$al['record_id']:'—'?></td>
        <td style="font-family:monospace;"><?=e($al['ip_address']??'—')?></td>
        <td><?=$al['created_at']?date('d M Y H:i',strtotime($al['created_at'])):'—'?></td>
      </tr>
      <?php endforeach; endif;?>
      </tbody></table></div>
    </div>
  </div>
</div>

<!-- ═══ SECTION L: PROFILE COMPLETENESS ENGINE ═══ -->
<div class="prof-section" id="sec-completeness">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-tasks" style="color:var(--role-accent);"></i> Profile Completeness Engine — <?=$completeness_pct?>%</h3></div>
    <div class="adm-card-body">
      <div style="background:var(--surface-2);border-radius:8px;height:14px;margin-bottom:2rem;overflow:hidden;">
        <div style="height:100%;width:<?=$completeness_pct?>%;background:<?=$completeness_pct>=80?'var(--success)':($completeness_pct>=50?'var(--warning)':'var(--danger)')?>; border-radius:8px;transition:width .5s;"></div>
      </div>
      <?php $check_labels=['personal_info'=>'Personal Information (DOB, Phone, Nationality)','professional_profile'=>'Professional Profile (Specialization, License)','qualifications'=>'Qualifications &amp; Certifications (at least 1)','equipment_assigned'=>'Equipment Assigned (at least 1)','shift_profile'=>'Shift Preference Notes filled','photo_uploaded'=>'Profile Photo Uploaded','security_setup'=>'Security Setup (2FA Enabled)','documents_uploaded'=>'Documents Uploaded (at least 1 document)'];
      $goto_map=['personal_info'=>'sec-personal','professional_profile'=>'sec-professional','qualifications'=>'sec-qualifications','equipment_assigned'=>'sec-responsibility','shift_profile'=>'sec-shift','photo_uploaded'=>'sec-personal','security_setup'=>'sec-security','documents_uploaded'=>'sec-documents'];
      $nav_idx=['sec-personal'=>0,'sec-professional'=>1,'sec-qualifications'=>2,'sec-stats'=>3,'sec-responsibility'=>4,'sec-shift'=>5,'sec-security'=>6,'sec-notifications'=>7,'sec-documents'=>8,'sec-audit'=>9,'sec-completeness'=>10];
      foreach($completeness_checks as $k=>$done):?>
      <div class="check-item">
        <div style="display:flex;align-items:center;gap:1rem;">
          <i class="fas fa-<?=$done?'check-circle':'exclamation-circle'?>" style="color:<?=$done?'var(--success)':'var(--warning)'?>;font-size:1.6rem;flex-shrink:0;"></i>
          <span style="font-size:1.25rem;"><?=$check_labels[$k]??$k?></span>
        </div>
        <?php if(!$done):?>
        <a href="#" class="adm-btn adm-btn-sm adm-btn-ghost" onclick="event.preventDefault();gotoProfileSection('<?=$goto_map[$k]??'sec-personal'?>',<?=$nav_idx[$goto_map[$k]??'sec-personal']??0?>)">Complete Now &rarr;</a>
        <?php else:?><span class="adm-badge adm-badge-success"><i class="fas fa-check"></i> Done</span><?php endif;?>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</div>

<!-- ═══════════ ALL MODALS ═══════════ -->
<!-- Edit Personal Info Modal -->
<div class="modal-bg" id="editPersonalModal"><div class="modal-box wide">
  <div class="modal-header"><h3><i class="fas fa-user"></i> Edit Personal Information</h3><button class="modal-close" onclick="closeModal('editPersonalModal')">&times;</button></div>
  <div class="form-row"><div class="form-group"><label>Full Name *</label><input id="prf_name" class="form-control" value="<?=e($techName)?>"></div><div class="form-group"><label>Date of Birth</label><input id="prf_dob" type="date" class="form-control" value="<?=e($tech_row['date_of_birth']??'')?>"></div></div>
  <div class="form-row"><div class="form-group"><label>Gender</label><select id="prf_gender" class="form-control"><?php foreach(['Male','Female','Other'] as $g):?><option <?=($tech_row['gender']??'')===$g?'selected':''?>><?=$g?></option><?php endforeach;?></select></div><div class="form-group"><label>Marital Status</label><select id="prf_marital" class="form-control"><?php foreach(['Single','Married','Divorced','Widowed'] as $m):?><option <?=($tech_row['marital_status']??'')===$m?'selected':''?>><?=$m?></option><?php endforeach;?></select></div></div>
  <div class="form-row"><div class="form-group"><label>Nationality</label><input id="prf_nat" class="form-control" value="<?=e($tech_row['nationality']??'Ghanaian')?>"></div><div class="form-group"><label>Religion (optional)</label><input id="prf_religion" class="form-control" value="<?=e($tech_row['religion']??'')?>"></div></div>
  <div class="form-row"><div class="form-group"><label>National ID</label><input id="prf_nid" class="form-control" value="<?=e($tech_row['national_id']??'')?>"></div><div class="form-group"><label>Postal Code</label><input id="prf_postal" class="form-control" value="<?=e($tech_row['postal_code']??'')?>"></div></div>
  <div class="form-row"><div class="form-group"><label>Primary Phone</label><input id="prf_phone" class="form-control" value="<?=e($tech_row['phone']??'')?>"></div><div class="form-group"><label>Secondary Phone</label><input id="prf_phone2" class="form-control" value="<?=e($tech_row['secondary_phone']??'')?>"></div></div>
  <div class="form-row"><div class="form-group"><label>Official Email</label><input id="prf_email" type="email" class="form-control" value="<?=e($tech_row['email']??'')?>"></div><div class="form-group"><label>Personal Email</label><input id="prf_email2" type="email" class="form-control" value="<?=e($tech_row['personal_email']??'')?>"></div></div>
  <div class="form-row"><div class="form-group"><label>Street Address</label><input id="prf_street" class="form-control" value="<?=e($tech_row['street_address']??'')?>"></div><div class="form-group"><label>City</label><input id="prf_city" class="form-control" value="<?=e($tech_row['city']??'')?>"></div></div>
  <div class="form-row"><div class="form-group"><label>Region / State</label><input id="prf_region" class="form-control" value="<?=e($tech_row['region']??'')?>"></div><div class="form-group"><label>Country</label><input id="prf_country" class="form-control" value="<?=e($tech_row['country']??'Ghana')?>"></div></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;margin-top:1rem;" onclick="updatePersonalInfo()"><i class="fas fa-save"></i> Save Personal Information</button>
</div></div>

<!-- Edit Professional Profile Modal -->
<div class="modal-bg" id="editProfModal"><div class="modal-box wide">
  <div class="modal-header"><h3><i class="fas fa-briefcase"></i> Edit Professional Profile</h3><button class="modal-close" onclick="closeModal('editProfModal')">&times;</button></div>
  <div class="form-row">
    <div class="form-group"><label>Designation</label><select id="prf_desig" class="form-control"><?php foreach(['Lab Technician','Senior Lab Technician','Lab Scientist','Senior Lab Scientist','Lab Supervisor','Lab Manager','Chief Medical Laboratory Scientist'] as $d):?><option <?=($tech_row['designation']??'')===$d?'selected':''?>><?=$d?></option><?php endforeach;?></select></div>
    <div class="form-group"><label>Specialization</label><select id="prf_spec" class="form-control"><?php foreach(['General Laboratory','Hematology','Clinical Chemistry','Biochemistry','Microbiology','Immunology','Histopathology','Serology','Urinalysis','Parasitology','Molecular Biology'] as $s):?><option <?=($tech_row['specialization']??'')===$s?'selected':''?>><?=$s?></option><?php endforeach;?></select></div>
  </div>
  <div class="form-row"><div class="form-group"><label>Sub-Specialization</label><input id="prf_subspec" class="form-control" value="<?=e($tech_row['sub_specialization']??'')?>"></div><div class="form-group"><label>Years of Experience</label><input id="prf_exp" type="number" class="form-control" value="<?=$tech_row['years_of_experience']??0?>"></div></div>
  <div class="form-row"><div class="form-group"><label>License Number</label><input id="prf_license" class="form-control" value="<?=e($tech_row['license_number']??'')?>"></div><div class="form-group"><label>Issuing Body</label><input id="prf_lic_body" class="form-control" value="<?=e($tech_row['license_issuing_body']??'Allied Health Professions Council')?>"></div></div>
  <div class="form-row"><div class="form-group"><label>License Expiry Date</label><input id="prf_licexp" type="date" class="form-control" value="<?=e($tech_row['license_expiry']??'')?>"></div><div class="form-group"><label>Institution Attended</label><input id="prf_inst" class="form-control" value="<?=e($tech_row['institution_attended']??'')?>"></div></div>
  <div class="form-row"><div class="form-group"><label>Graduation Year</label><input id="prf_gradyr" type="number" min="1960" max="2030" class="form-control" value="<?=$tech_row['graduation_year']??''?>"></div><div class="form-group"><label>Languages Spoken (comma-separated)</label><input id="prf_langs" class="form-control" value="<?=e(!empty($tech_row['languages_spoken'])?implode(', ',json_decode($tech_row['languages_spoken'],true)):'English')?>"></div></div>
  <div class="form-group"><label>Postgraduate / Specialty Training</label><input id="prf_pg" class="form-control" value="<?=e($tech_row['postgraduate_details']??'')?>"></div>
  <div class="form-group"><label>Professional Bio / Summary</label><textarea id="prf_bio" class="form-control" rows="5" placeholder="Write a short professional summary visible to admins..."><?=e($tech_row['bio']??'')?></textarea></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;margin-top:1rem;" onclick="updateProfessionalProfile()"><i class="fas fa-save"></i> Save Professional Profile</button>
</div></div>

<!-- Add Qualification Modal -->
<div class="modal-bg" id="addQualModal"><div class="modal-box">
  <div class="modal-header"><h3><i class="fas fa-graduation-cap"></i> Add Academic Qualification</h3><button class="modal-close" onclick="closeModal('addQualModal')">&times;</button></div>
  <div class="form-group"><label>Degree / Certificate Name *</label><input id="qual_degree" class="form-control" placeholder="e.g. BSc Medical Laboratory Science"></div>
  <div class="form-group"><label>Institution *</label><input id="qual_inst" class="form-control" placeholder="University / College name"></div>
  <div class="form-row"><div class="form-group"><label>Year Awarded</label><input id="qual_year" type="number" class="form-control" placeholder="e.g. 2019"></div><div class="form-group"><label>Certificate File (PDF/Image, max 5MB)</label><input type="file" id="qual_file" class="form-control" accept=".pdf,image/*"></div></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;margin-top:1rem;" onclick="saveQualification()"><i class="fas fa-save"></i> Save Qualification</button>
</div></div>

<!-- Add Certification Modal -->
<div class="modal-bg" id="addCertModal"><div class="modal-box">
  <div class="modal-header"><h3><i class="fas fa-certificate"></i> Add Professional Certification</h3><button class="modal-close" onclick="closeModal('addCertModal')">&times;</button></div>
  <div class="form-group"><label>Certification Name *</label><input id="cert_name" class="form-control"></div>
  <div class="form-group"><label>Issuing Organization</label><input id="cert_org" class="form-control"></div>
  <div class="form-row"><div class="form-group"><label>Issue Date</label><input id="cert_issued" type="date" class="form-control"></div><div class="form-group"><label>Expiry Date</label><input id="cert_exp" type="date" class="form-control"></div></div>
  <div class="form-group"><label>Certificate File (PDF/Image, max 5MB)</label><input type="file" id="cert_file" class="form-control" accept=".pdf,image/*"></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;margin-top:1rem;" onclick="saveCertification()"><i class="fas fa-save"></i> Save Certification</button>
</div></div>

<!-- Upload Document Modal -->
<div class="modal-bg" id="uploadDocModal"><div class="modal-box">
  <div class="modal-header"><h3><i class="fas fa-upload"></i> Upload Professional Document</h3><button class="modal-close" onclick="closeModal('uploadDocModal')">&times;</button></div>
  <div class="form-group"><label>Document Name *</label><input id="doc_name" class="form-control" placeholder="e.g. Lab License 2024"></div>
  <div class="form-group"><label>Document Type</label><select id="doc_type" class="form-control"><option>Lab Technician License</option><option>Professional Certification</option><option>Contract / Employment Letter</option><option>Training Certificate</option><option>Equipment Operation Certificate</option><option>Other</option></select></div>
  <div class="form-group"><label>Description (optional)</label><textarea id="doc_desc" class="form-control" rows="2"></textarea></div>
  <div class="form-group"><label>File (PDF / Image / Word, max 10MB) *</label><input type="file" id="doc_file" class="form-control" accept=".pdf,image/*,.doc,.docx"></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;margin-top:1rem;" onclick="uploadDocument()"><i class="fas fa-cloud-upload-alt"></i> Upload Document</button>
</div></div>

<!-- ═══════════ JAVASCRIPT ═══════════ -->
<script>
// Profile sub-section navigation
function showProfSection(id,el){
  document.querySelectorAll('.prof-section').forEach(s=>s.classList.remove('active'));
  const sec=document.getElementById(id);if(sec)sec.classList.add('active');
  document.querySelectorAll('.prof-nav a').forEach(a=>a.classList.remove('active'));
  if(el)el.classList.add('active');
}
function gotoProfileSection(secId,navIdx){
  const links=document.querySelectorAll('.prof-nav a');
  showProfSection(secId,links[navIdx]||null);
  window.scrollTo({top:200,behavior:'smooth'});
}

// Password strength
function checkPwStrength(v){
  const fill=document.getElementById('pwStrengthFill'),lbl=document.getElementById('pwStrengthLabel');
  let s=0;
  if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  const m=[[0,'',0,''],[1,'#e74c3c',10,'Weak'],[2,'#e67e22',45,'Fair'],[3,'#f1c40f',75,'Strong'],[4,'#27ae60',100,'Very Strong']];
  const d=m[Math.min(s,4)];
  fill.style.width=d[2]+'%';fill.style.background=d[1];
  lbl.textContent=d[3];lbl.style.color=d[1];
}

// Audit trail filter
function filterAuditTable(){
  const mod=document.getElementById('auditModFilter').value;
  const from=document.getElementById('auditFrom').value;
  const to=document.getElementById('auditTo').value;
  document.querySelectorAll('#auditPersonalTable tbody tr').forEach(r=>{
    const rm=r.dataset.module,rd=r.dataset.date;
    r.style.display=(!mod||rm===mod)&&(!from||rd>=from)&&(!to||rd<=to)?'':'none';
  });
}

// Profile photo upload
async function uploadProfilePhoto(input){
  if(!input.files[0])return;
  if(input.files[0].size>2097152){showToast('Photo must be under 2MB','error');return;}
  const fd=new FormData();fd.append('action','update_profile_photo');fd.append('_csrf',CSRF);fd.append('photo',input.files[0]);
  const r=await fetch(ACTIONS,{method:'POST',body:fd});const d=await r.json();
  showToast(d.message,d.success?'success':'error');if(d.success)setTimeout(()=>location.reload(),800);
}

// Availability
async function updateAvailability(status){
  const r=await labAction({action:'update_availability',status});
  showToast(r.message,r.success?'success':'error');
}

// Helper shorthand
function $v(id){return document.getElementById(id)?.value?.trim()||'';}

// Personal info
async function updatePersonalInfo(){
  if(!$v('prf_name')){showToast('Full name is required','error');return;}
  const r=await labAction({action:'update_personal_info',
    name:$v('prf_name'),dob:$v('prf_dob'),gender:$v('prf_gender'),marital_status:$v('prf_marital'),
    nationality:$v('prf_nat'),religion:$v('prf_religion'),national_id:$v('prf_nid'),postal_code:$v('prf_postal'),
    phone:$v('prf_phone'),secondary_phone:$v('prf_phone2'),
    email:$v('prf_email'),personal_email:$v('prf_email2'),
    street_address:$v('prf_street'),city:$v('prf_city'),region:$v('prf_region'),country:$v('prf_country')
  });
  showToast(r.message,r.success?'success':'error');
  if(r.success){closeModal('editPersonalModal');setTimeout(()=>location.reload(),800);}
}

// Professional profile
async function updateProfessionalProfile(){
  const r=await labAction({action:'update_professional_info',
    designation:$v('prf_desig'),specialization:$v('prf_spec'),sub_specialization:$v('prf_subspec'),
    years_of_experience:$v('prf_exp'),license_number:$v('prf_license'),
    license_issuing_body:$v('prf_lic_body'),license_expiry:$v('prf_licexp'),
    institution_attended:$v('prf_inst'),graduation_year:$v('prf_gradyr'),
    postgraduate_details:$v('prf_pg'),languages_spoken:$v('prf_langs'),bio:$v('prf_bio')
  });
  showToast(r.message,r.success?'success':'error');
  if(r.success){closeModal('editProfModal');setTimeout(()=>location.reload(),800);}
}

// Qualifications
async function saveQualification(){
  if(!$v('qual_degree')||!$v('qual_inst')){showToast('Degree and institution are required','error');return;}
  const fd=new FormData();
  fd.append('action','save_qualification');fd.append('_csrf',CSRF);
  fd.append('degree_name',$v('qual_degree'));fd.append('institution',$v('qual_inst'));fd.append('year_awarded',$v('qual_year'));
  const f=document.getElementById('qual_file').files[0];if(f)fd.append('file',f);
  const r=await fetch(ACTIONS,{method:'POST',body:fd});const d=await r.json();
  showToast(d.message,d.success?'success':'error');
  if(d.success){closeModal('addQualModal');setTimeout(()=>location.reload(),800);}
}
async function deleteQualification(id){
  if(!confirmAction('Delete this qualification?'))return;
  const r=await labAction({action:'delete_qualification',id});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}

// Certifications
async function saveCertification(){
  if(!$v('cert_name')){showToast('Certification name is required','error');return;}
  const fd=new FormData();
  fd.append('action','save_certification');fd.append('_csrf',CSRF);
  fd.append('certification_name',$v('cert_name'));fd.append('issuing_body',$v('cert_org'));
  fd.append('issue_date',$v('cert_issued'));fd.append('expiry_date',$v('cert_exp'));
  const f=document.getElementById('cert_file').files[0];if(f)fd.append('file',f);
  const r=await fetch(ACTIONS,{method:'POST',body:fd});const d=await r.json();
  showToast(d.message,d.success?'success':'error');
  if(d.success){closeModal('addCertModal');setTimeout(()=>location.reload(),800);}
}
async function deleteCertification(id){
  if(!confirmAction('Delete this certification?'))return;
  const r=await labAction({action:'delete_certification',id});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}

// Documents
async function uploadDocument(){
  const f=document.getElementById('doc_file').files[0];
  if(!$v('doc_name')||!f){showToast('Document name and file are required','error');return;}
  const fd=new FormData();fd.append('action','upload_document');fd.append('_csrf',CSRF);
  fd.append('name',$v('doc_name'));fd.append('type',$v('doc_type'));fd.append('description',$v('doc_desc'));fd.append('file',f);
  const r=await fetch(ACTIONS,{method:'POST',body:fd});const d=await r.json();
  showToast(d.message,d.success?'success':'error');
  if(d.success){closeModal('uploadDocModal');setTimeout(()=>location.reload(),800);}
}
async function deleteDocument(id){
  if(!confirmAction('Delete this document?'))return;
  const r=await labAction({action:'delete_document',id});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}

// Shift notes
async function saveShiftNotes(){
  const r=await labAction({action:'update_shift_notes',notes:document.getElementById('shiftNotesInput').value});
  showToast(r.message,r.success?'success':'error');
}

// Password change
async function changePassword(){
  const old=$v('sec_old_pw'),np=$v('sec_new_pw'),cp=$v('sec_confirm_pw');
  if(!old||!np){showToast('All password fields are required','error');return;}
  if(np!==cp){showToast('New passwords do not match','error');return;}
  const r=await labAction({action:'change_password',current_password:old,new_password:np});
  showToast(r.message,r.success?'success':'error');
  if(r.success){document.getElementById('sec_old_pw').value='';document.getElementById('sec_new_pw').value='';document.getElementById('sec_confirm_pw').value='';}
}

// 2FA toggle
async function toggleTwoFA(on){
  const r=await labAction({action:'update_setting',key:'two_fa_enabled',value:on?1:0});
  showToast(r.success?'2FA '+(on?'enabled':'disabled')+' successfully':r.message,r.success?'success':'error');
}

// Sessions
async function logoutSession(id){
  if(!confirmAction('Terminate this session?'))return;
  const r=await labAction({action:'logout_session',session_id:id});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}
async function logoutAllSessions(){
  if(!confirmAction('Log out all other devices? You will remain logged in here.'))return;
  const r=await labAction({action:'logout_all_sessions'});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}

// Notification toggle
async function saveNotifToggle(key,val){
  const r=await labAction({action:'update_setting',key,value:val});
  if(!r.success)showToast(r.message,'error');
}

// Charts (Section E - initialized on section open)
document.addEventListener('DOMContentLoaded',function(){
  if(document.getElementById('profVolumeChart')){
    new Chart(document.getElementById('profVolumeChart'),{type:'bar',data:{labels:[<?=$vol7_labels?>],datasets:[{label:'Orders',data:[<?=$vol7_data?>],backgroundColor:'rgba(142,68,173,.65)',borderColor:'#8E44AD',borderWidth:1,borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
  }
  if(document.getElementById('profStatusChart')){
    new Chart(document.getElementById('profStatusChart'),{type:'doughnut',data:{labels:['Completed','In Progress','Pending','Rejected'],datasets:[{data:[<?=$ws['completed']?>,<?=$ws['in_progress']?>,<?=max(0,$ws['month_orders']-$ws['completed']-$ws['in_progress'])?>,<?=$ws['rejected_samples']?>],backgroundColor:['#27AE60','#2980B9','#F39C12','#E74C3C']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{size:10}}}}}});
  }
});
</script>
