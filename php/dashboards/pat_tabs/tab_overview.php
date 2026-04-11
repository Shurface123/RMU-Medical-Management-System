<?php
// MODULE 1: OVERVIEW — Patient Dashboard Home
// Queries run in parent: $pat_row, $stats, $pat_pk, $today

// Recent activity
$activity=[];
$q=mysqli_query($conn,"(SELECT 'Appointment' AS type, a.status, CONCAT('Dr. ',u.name) AS detail, a.updated_at AS ts
    FROM appointments a JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id
    WHERE a.patient_id=$pat_pk ORDER BY a.updated_at DESC LIMIT 3)
  UNION ALL
  (SELECT 'Prescription', pr.status, pr.medication_name, pr.updated_at
    FROM prescriptions pr WHERE pr.patient_id=$pat_pk ORDER BY pr.updated_at DESC LIMIT 3)
  ORDER BY ts DESC LIMIT 8");
if($q) while($r=mysqli_fetch_assoc($q)) $activity[]=$r;

// Upcoming appointments (next 5)
$upcoming=[];
$q=mysqli_query($conn,"SELECT a.id, a.appointment_date, a.appointment_time, a.service_type, a.status,
    u.name AS doctor_name, d.specialization
  FROM appointments a JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id
  WHERE a.patient_id=$pat_pk AND a.appointment_date>='$today' AND a.status NOT IN('Cancelled','No-Show')
  ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 5");
if($q) while($r=mysqli_fetch_assoc($q)) $upcoming[]=$r;

// Next appointment countdown
$next_appt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT appointment_date,appointment_time FROM appointments
  WHERE patient_id=$pat_pk AND appointment_date>='$today' AND status NOT IN('Cancelled','No-Show')
  ORDER BY appointment_date,appointment_time LIMIT 1"));
$days_until=null;
if($next_appt){$days_until=(int)date_diff(date_create($next_appt['appointment_date']),date_create('today'))->days;}

// Last visit
$last_visit=mysqli_fetch_assoc(mysqli_query($conn,"SELECT visit_date,diagnosis FROM medical_records WHERE patient_id=$pat_pk ORDER BY visit_date DESC LIMIT 1"));
$dob=$pat_row['date_of_birth']??null;
$age=$dob?((int)date_diff(date_create($dob),date_create('today'))->y):null;
?>

<div id="sec-overview" class="dash-section">

  <!-- Welcome Banner -->
  <div class="adm-welcome" style="background:linear-gradient(135deg,var(--role-accent),#2F80ED);margin-bottom:2rem;">
    <div style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;position:relative;z-index:1;">
      <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;border:3px solid rgba(255,255,255,.4);overflow:hidden;flex-shrink:0;">
        <?php $pimg=$pat_row['profile_image']??''; if(!empty($pimg)&&$pimg!=='default-avatar.png'):?>
        <img src="/RMU-Medical-Management-System/<?=htmlspecialchars($pimg)?>" style="width:100%;height:100%;object-fit:cover;">
        <?php else:?>
        <span style="font-size:2.8rem;font-weight:800;color:#fff;"><?=strtoupper(substr($pat_row['name']??'P',0,1))?></span>
        <?php endif;?>
      </div>
      <div>
        <h2 style="font-size:2.4rem;">Welcome, <?=htmlspecialchars($pat_row['name']??'Patient')?>!</h2>
        <p style="font-size:1.4rem;opacity:.88;">
          <?=$pat_row['is_student']??0?'Student Patient':'Community Patient'?> · ID: <?=htmlspecialchars($pat_ref)?> ·
          <?=date('l, d F Y')?>
        </p>
      </div>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="adm-summary-strip" style="margin-bottom:2rem;">
    <div class="adm-mini-card"><div class="adm-mini-card-num blue"><?=$stats['upcoming']?></div><div class="adm-mini-card-label">Upcoming Appts</div></div>
    <div class="adm-mini-card"><div class="adm-mini-card-num orange"><?=$stats['active_rx']?></div><div class="adm-mini-card-label">Active Rx</div></div>
    <div class="adm-mini-card"><div class="adm-mini-card-num" style="color:var(--danger);"><?=$stats['unread_notif']?></div><div class="adm-mini-card-label">Unread Alerts</div></div>
    <div class="adm-mini-card"><div class="adm-mini-card-num" style="color:var(--success);"><?=$stats['emerg_contacts']?></div><div class="adm-mini-card-label">Emerg. Contacts</div></div>
  </div>

  <!-- Allergy & Chronic Alerts -->
  <?php if(!empty($pat_row['allergies']??'')):?>
  <div style="background:var(--danger-light);color:var(--danger);border-left:4px solid var(--danger);border-radius:0 12px 12px 0;padding:1rem 1.5rem;margin-bottom:1rem;font-size:1.3rem;display:flex;align-items:center;gap:.8rem;">
    <i class="fas fa-exclamation-triangle"></i><div><strong>Known Allergies:</strong> <?=htmlspecialchars($pat_row['allergies'])?></div>
  </div>
  <?php endif;?>
  <?php if(!empty($pat_row['chronic_conditions']??'')):?>
  <div style="background:var(--warning-light);color:var(--warning);border-left:4px solid var(--warning);border-radius:0 12px 12px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;display:flex;align-items:center;gap:.8rem;">
    <i class="fas fa-heartbeat"></i><div><strong>Chronic Conditions:</strong> <?=htmlspecialchars($pat_row['chronic_conditions'])?></div>
  </div>
  <?php endif;?>

  <!-- Main Grid -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start;">

    <!-- LEFT: Upcoming Appointments + Activity Feed -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
      <!-- Upcoming Appointments -->
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-calendar-alt" style="color:var(--primary);"></i> Upcoming Appointments</h3>
          <button class="btn-icon btn btn-primary btn-sm" onclick="showTab('book',document.querySelector('.adm-nav-item[onclick*=book]'))"><span class="btn-text"><i class="fas fa-plus"></i> Book</span></button>
        </div>
        <?php if(empty($upcoming)):?>
        <div style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-calendar-times" style="font-size:2.5rem;margin-bottom:1rem;opacity:.4;display:block;"></i><p>No upcoming appointments</p>
          <button class="btn-icon btn btn-primary" style="margin-top:1rem;" onclick="showTab('book',document.querySelector('.adm-nav-item[onclick*=book]'))"><span class="btn-text"><i class="fas fa-calendar-plus"></i> Book Now</span></button>
        </div>
        <?php else:?>
        <div style="padding:.5rem;">
          <?php foreach($upcoming as $apt):
            $adt=new DateTime($apt['appointment_date']);
            $sc=($apt['status']==='Confirmed'||$apt['status']==='Approved')?'success':(($apt['status']==='Pending')?'warning':'info');
          ?>
          <div style="display:flex;align-items:flex-start;gap:1rem;padding:.85rem 0;border-bottom:1px solid var(--border);">
            <div style="background:var(--primary);color:#fff;border-radius:var(--radius-sm);padding:.4rem .8rem;text-align:center;min-width:52px;">
              <div style="font-size:1.4rem;font-weight:800;line-height:1;"><?=$adt->format('d')?></div>
              <div style="font-size:.7rem;text-transform:uppercase;"><?=$adt->format('M')?></div>
            </div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:1.3rem;">Dr. <?=htmlspecialchars($apt['doctor_name'])?></div>
              <div style="font-size:1.15rem;color:var(--text-secondary);display:flex;gap:.75rem;flex-wrap:wrap;margin:.2rem 0;">
                <span><i class="fas fa-stethoscope"></i> <?=htmlspecialchars($apt['specialization'])?></span>
                <span><i class="fas fa-clock"></i> <?=date('g:i A',strtotime($apt['appointment_time']))?></span>
              </div>
              <span class="adm-badge adm-badge-<?=$sc?>"><?=$apt['status']?></span>
            </div>
          </div>
          <?php endforeach;?>
        </div>
        <?php endif;?>
      </div>

      <!-- Recent Activity Feed -->
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-clock-rotate-left" style="color:var(--role-accent);"></i> Recent Activity</h3></div>
        <div style="padding:.5rem;">
          <?php if(empty($activity)):?><p style="text-align:center;padding:2rem;color:var(--text-muted);">No recent activity</p>
          <?php else: foreach($activity as $act):
            $dotMap=['Appointment'=>'var(--primary)','Prescription'=>'var(--warning)','Lab Test'=>'var(--info)']; $dotColor=$dotMap[$act['type']]??'var(--text-muted)';
            $icoMap=['Appointment'=>'fa-calendar','Prescription'=>'fa-pills','Lab Test'=>'fa-flask']; $icon=$icoMap[$act['type']]??'fa-circle';
          ?>
          <div style="display:flex;align-items:flex-start;gap:1rem;padding:.7rem 0;border-bottom:1px solid var(--border);">
            <div style="width:10px;height:10px;border-radius:50%;background:<?=$dotColor?>;flex-shrink:0;margin-top:.55rem;"></div>
            <div>
              <span style="font-weight:600;font-size:1.2rem;"><?=$act['type']?></span>
              <span class="adm-badge adm-badge-<?=($act['status']==='Completed'||$act['status']==='Approved')?'success':(($act['status']==='Pending')?'warning':'info')?>" style="font-size:.9rem;margin-left:.4rem;"><?=$act['status']?></span>
              <div style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($act['detail'])?> · <?=date('d M, g:i A',strtotime($act['ts']))?></div>
            </div>
          </div>
          <?php endforeach; endif;?>
        </div>
      </div>
    </div>

    <!-- RIGHT: Countdown + Quick Actions + Health Snapshot -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
      <!-- Countdown -->
      <?php if($next_appt&&$days_until!==null):?>
      <div style="background:linear-gradient(135deg,#1abc9c,#27ae60);color:#fff;border-radius:var(--radius-lg);padding:1.5rem;text-align:center;">
        <div style="font-size:1.2rem;opacity:.85;margin-bottom:.5rem;"><i class="fas fa-clock"></i> Next Appointment In</div>
        <div style="font-size:3.5rem;font-weight:800;line-height:1;"><?=$days_until?></div>
        <div style="font-size:1.2rem;opacity:.85;margin-top:.3rem;"><?=$days_until===1?'Day':'Days'?> — <?=date('l, d M',strtotime($next_appt['appointment_date']))?> @ <?=date('g:i A',strtotime($next_appt['appointment_time']))?></div>
      </div>
      <?php endif;?>

      <!-- Quick Actions -->
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding:.75rem;">
          <a href="#" onclick="showTab('book',document.querySelector('.adm-nav-item[onclick*=book]'));return false;" class="quick-action-card" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1.25rem;text-align:center;text-decoration:none;color:var(--text-primary);transition:all .2s;cursor:pointer;">
            <div style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;font-size:1.3rem;background:#e8f4fd;color:#2980b9;"><i class="fas fa-calendar-plus"></i></div>
            <div style="font-size:1.15rem;font-weight:600;">Book Appt</div>
          </a>
          <a href="#" onclick="showTab('prescriptions',document.querySelector('.adm-nav-item[onclick*=prescriptions]'));return false;" class="quick-action-card" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1.25rem;text-align:center;text-decoration:none;color:var(--text-primary);transition:all .2s;cursor:pointer;">
            <div style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;font-size:1.3rem;background:#e8f8f5;color:#1abc9c;"><i class="fas fa-pills"></i></div>
            <div style="font-size:1.15rem;font-weight:600;">View Rx</div>
          </a>
          <a href="#" onclick="showTab('records',document.querySelector('.adm-nav-item[onclick*=records]'));return false;" class="quick-action-card" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1.25rem;text-align:center;text-decoration:none;color:var(--text-primary);transition:all .2s;cursor:pointer;">
            <div style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;font-size:1.3rem;background:#fef9e7;color:#f39c12;"><i class="fas fa-file-medical"></i></div>
            <div style="font-size:1.15rem;font-weight:600;">Records</div>
          </a>
        </div>
      </div>

      <!-- Health Snapshot -->
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-heart-pulse" style="color:#e74c3c;"></i> Health Snapshot</h3></div>
        <div style="padding:1rem;">
          <div style="display:grid;gap:.6rem;font-size:1.25rem;">
            <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);"><span style="color:var(--text-muted);"><i class="fas fa-tint" style="color:#e74c3c;margin-right:.5rem;"></i>Blood Type</span><strong><?=htmlspecialchars($pat_row['blood_group']??'Not set')?></strong></div>
            <?php if($age):?><div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);"><span style="color:var(--text-muted);"><i class="fas fa-birthday-cake" style="color:var(--role-accent);margin-right:.5rem;"></i>Age</span><strong><?=$age?> years</strong></div><?php endif;?>
            <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);"><span style="color:var(--text-muted);"><i class="fas fa-venus-mars" style="color:var(--primary);margin-right:.5rem;"></i>Gender</span><strong><?=htmlspecialchars($pat_row['gender']??'—')?></strong></div>
            <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);"><span style="color:var(--text-muted);"><i class="fas fa-calendar-check" style="color:var(--success);margin-right:.5rem;"></i>Last Visit</span><strong><?=$last_visit?date('d M Y',strtotime($last_visit['visit_date'])):'No visits yet'?></strong></div>
            <div style="display:flex;justify-content:space-between;padding:.5rem 0;"><span style="color:var(--text-muted);"><i class="fas fa-prescription" style="color:var(--warning);margin-right:.5rem;"></i>Active Rx</span><strong><?=$stats['active_rx']?></strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
