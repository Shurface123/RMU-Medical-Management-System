<?php
// MODULE 1: OVERVIEW — Patient Dashboard Home (Redesigned v2)
// Queries run in parent: $pat_row, $stats, $pat_pk, $today

// Recent activity
$activity = [];
$q = mysqli_query($conn, "(SELECT 'Appointment' AS type, a.status, CONCAT('Dr. ',u.name) AS detail, a.updated_at AS ts
    FROM appointments a JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id
    WHERE a.patient_id=$pat_pk ORDER BY a.updated_at DESC LIMIT 4)
  UNION ALL
  (SELECT 'Prescription', pr.status, pr.medication_name, pr.updated_at
    FROM prescriptions pr WHERE pr.patient_id=$pat_pk ORDER BY pr.updated_at DESC LIMIT 4)
  ORDER BY ts DESC LIMIT 8");
if ($q) while ($r = mysqli_fetch_assoc($q)) $activity[] = $r;

// Upcoming appointments (next 5)
$upcoming = [];
$q = mysqli_query($conn, "SELECT a.id, a.appointment_date, a.appointment_time, a.service_type, a.status,
    u.name AS doctor_name, d.specialization
  FROM appointments a JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id
  WHERE a.patient_id=$pat_pk AND a.appointment_date>='$today' AND a.status NOT IN('Cancelled','No-Show')
  ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 5");
if ($q) while ($r = mysqli_fetch_assoc($q)) $upcoming[] = $r;

// Next appointment countdown
$next_appt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT appointment_date,appointment_time FROM appointments
  WHERE patient_id=$pat_pk AND appointment_date>='$today' AND status NOT IN('Cancelled','No-Show')
  ORDER BY appointment_date,appointment_time LIMIT 1"));
$days_until = null;
if ($next_appt) { $days_until = (int)date_diff(date_create($next_appt['appointment_date']), date_create('today'))->days; }

// Last visit & billing stats
$last_visit  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT visit_date,diagnosis FROM medical_records WHERE patient_id=$pat_pk ORDER BY visit_date DESC LIMIT 1"));
$outstanding = (float)(mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(balance_due),0) FROM billing_invoices WHERE patient_id=$pat_pk AND status NOT IN('Paid','Cancelled','Void')"))[0] ?? 0);
$dob     = $pat_row['date_of_birth'] ?? null;
$age     = $dob ? ((int)date_diff(date_create($dob), date_create('today'))->y) : null;
?>

<div id="sec-overview" class="dash-section">

<style>
/* ── Overview-specific micro-styles ── */
.ov-stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:2rem;display:flex;align-items:center;gap:1.5rem;box-shadow:var(--shadow-sm);transition:var(--transition);cursor:pointer;position:relative;overflow:hidden;}
.ov-stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;transition:opacity .3s;}
.ov-stat-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md);}
.ov-stat-card:hover::before{opacity:1;}
.ov-stat-icon{width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:2.2rem;color:#fff;flex-shrink:0;}
.ov-stat-num{font-size:3rem;font-weight:800;line-height:1;}
.ov-stat-label{font-size:1.2rem;color:var(--text-muted);font-weight:500;margin-top:.2rem;}

.ov-appt-item{display:flex;align-items:flex-start;gap:1.2rem;padding:1.2rem 0;border-bottom:1px solid var(--border);transition:background .15s;}
.ov-appt-item:last-child{border-bottom:none;}
.ov-appt-date-badge{min-width:52px;background:linear-gradient(135deg,var(--role-accent),#2F80ED);color:#fff;border-radius:10px;padding:.5rem .7rem;text-align:center;flex-shrink:0;}
.ov-appt-date-badge .day{font-size:1.6rem;font-weight:800;line-height:1;}
.ov-appt-date-badge .mon{font-size:.85rem;text-transform:uppercase;opacity:.9;}

.ov-timeline-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0;margin-top:5px;border:2px solid var(--surface);}
.ov-timeline-line{width:2px;background:var(--border);margin:0 auto;flex-grow:1;}

.ov-quick-card{background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.5rem;text-align:center;cursor:pointer;transition:var(--transition);text-decoration:none;color:var(--text-primary);display:block;}
.ov-quick-card:hover{background:var(--role-accent);color:#fff;transform:translateY(-3px);box-shadow:var(--shadow-md);}
.ov-quick-card:hover .ov-quick-icon{background:rgba(255,255,255,.2);color:#fff;}
.ov-quick-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.6rem;background:var(--surface);transition:var(--transition);}
.ov-quick-label{font-size:1.2rem;font-weight:700;}

.ov-health-row{display:flex;justify-content:space-between;align-items:center;padding:.7rem 0;border-bottom:1px solid var(--border);}
.ov-health-row:last-child{border-bottom:none;}

.ov-countdown{background:linear-gradient(135deg,#1abc9c,#27ae60);color:#fff;border-radius:var(--radius-md);padding:2rem;text-align:center;}
.ov-countdown .num{font-size:4rem;font-weight:800;line-height:1;display:block;}
.ov-countdown .label{font-size:1.2rem;opacity:.9;margin-top:.3rem;}
.ov-countdown .detail{font-size:1.15rem;opacity:.85;margin-top:.8rem;background:rgba(255,255,255,.15);border-radius:8px;padding:.5rem 1rem;}

@media(max-width:900px){.ov-main-grid{grid-template-columns:1fr!important;}}
</style>

  <!-- Welcome Banner -->
  <div class="adm-welcome" style="background:linear-gradient(135deg,var(--role-accent),#2F80ED);margin-bottom:2rem;padding:2.8rem 3rem;">
    <div style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;position:relative;z-index:1;">
      <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;border:3px solid rgba(255,255,255,.45);overflow:hidden;flex-shrink:0;box-shadow:0 4px 20px rgba(0,0,0,.2);">
        <?php 
        $g = strtolower($pat_row['gender'] ?? '');
        $is_female = ($g === 'female' || $g === 'f');
        $av_bg = $is_female ? 'linear-gradient(135deg, #FF6B6B, #FF8E53)' : 'linear-gradient(135deg, #2F80ED, #56CCF2)';
        $av_icon = $is_female ? 'fa-person-dress' : 'fa-person';
        ?>
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:<?=$av_bg?>;color:#fff;font-size:3.5rem;">
          <i class="fas <?=$av_icon?>"></i>
        </div>
      </div>
      <div style="flex:1;">
        <h2 style="font-size:2.4rem;margin-bottom:.4rem;">Welcome back, <?= htmlspecialchars(explode(' ', $pat_row['name'] ?? 'Patient')[0]) ?>! 👋</h2>
        <p style="font-size:1.35rem;opacity:.9;">
          <?= $pat_row['is_student'] ?? 0 ? 'Student Patient' : 'Community Patient' ?> &nbsp;·&nbsp;
          ID: <strong><?= htmlspecialchars($pat_ref) ?></strong> &nbsp;·&nbsp;
          <?= date('l, d F Y') ?>
        </p>
      </div>
      <!-- Quick stats chips -->
      <div style="display:flex;gap:.8rem;flex-wrap:wrap;">
        <?php if($outstanding > 0): ?>
        <div onclick="showTab('billing',document.querySelector('.adm-nav-item[onclick*=billing]'))" style="background:rgba(231,76,60,.25);border:1px solid rgba(231,76,60,.4);border-radius:20px;padding:.5rem 1.2rem;cursor:pointer;transition:all .2s;" onmouseover="this.style.background='rgba(231,76,60,.4)'" onmouseout="this.style.background='rgba(231,76,60,.25)'">
          <span style="font-size:1.1rem;color:#fff;font-weight:600;"><i class="fas fa-receipt"></i> GHS <?= number_format($outstanding, 2) ?> due</span>
        </div>
        <?php endif; ?>
        <?php if($stats['unread_notif'] > 0): ?>
        <div onclick="showTab('notif_page',document.querySelector('.adm-nav-item[onclick*=notif_page]'))" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);border-radius:20px;padding:.5rem 1.2rem;cursor:pointer;transition:all .2s;" onmouseover="this.style.background='rgba(255,255,255,.3)'" onmouseout="this.style.background='rgba(255,255,255,.2)'">
          <span style="font-size:1.1rem;color:#fff;font-weight:600;"><i class="fas fa-bell fa-shake"></i> <?= $stats['unread_notif'] ?> new alerts</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- KPI Strip -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1.2rem;margin-bottom:2rem;">
    <!-- Upcoming -->
    <div class="ov-stat-card" onclick="showTab('appointments',document.querySelector('.adm-nav-item[onclick*=appointments]'))" style="border-left:4px solid var(--primary);">
      <div class="ov-stat-icon" style="background:linear-gradient(135deg,#2F80ED,#56CCF2);">
        <i class="fas fa-calendar-check"></i>
      </div>
      <div>
        <div class="ov-stat-num" style="color:var(--primary);"><?= $stats['upcoming'] ?></div>
        <div class="ov-stat-label">Upcoming Appts</div>
      </div>
    </div>
    <!-- Active Rx -->
    <div class="ov-stat-card" onclick="showTab('prescriptions',document.querySelector('.adm-nav-item[onclick*=prescriptions]'))" style="border-left:4px solid var(--warning);">
      <div class="ov-stat-icon" style="background:linear-gradient(135deg,#F39C12,#F7CF68);">
        <i class="fas fa-pills"></i>
      </div>
      <div>
        <div class="ov-stat-num" style="color:var(--warning);"><?= $stats['active_rx'] ?></div>
        <div class="ov-stat-label">Active Prescriptions</div>
      </div>
    </div>
    <!-- Notifications -->
    <div class="ov-stat-card" onclick="showTab('notif_page',document.querySelector('.adm-nav-item[onclick*=notif_page]'))" style="border-left:4px solid var(--danger);">
      <div class="ov-stat-icon" style="background:linear-gradient(135deg,#E74C3C,#f16952);">
        <i class="fas fa-bell"></i>
      </div>
      <div>
        <div class="ov-stat-num" style="color:var(--danger);"><?= $stats['unread_notif'] ?></div>
        <div class="ov-stat-label">Unread Alerts</div>
      </div>
    </div>
    <!-- Total Appointments -->
    <div class="ov-stat-card" style="border-left:4px solid var(--success);">
      <div class="ov-stat-icon" style="background:linear-gradient(135deg,#27AE60,#58D68D);">
        <i class="fas fa-stethoscope"></i>
      </div>
      <div>
        <div class="ov-stat-num" style="color:var(--success);"><?= $stats['total_appts'] ?></div>
        <div class="ov-stat-label">Total Visits</div>
      </div>
    </div>
    <!-- Outstanding -->
    <div class="ov-stat-card" onclick="showTab('billing',document.querySelector('.adm-nav-item[onclick*=billing]'))" style="border-left:4px solid <?= $outstanding > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
      <div class="ov-stat-icon" style="background:linear-gradient(135deg,<?= $outstanding > 0 ? '#E74C3C,#c0392b' : '#27AE60,#58D68D' ?>);">
        <i class="fas fa-receipt"></i>
      </div>
      <div>
        <div class="ov-stat-num" style="font-size:2rem;color:<?= $outstanding > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
          GHS <?= number_format($outstanding, 2) ?>
        </div>
        <div class="ov-stat-label">Outstanding Balance</div>
      </div>
    </div>
  </div>

  <!-- Allergy & Chronic Alerts -->
  <?php if (!empty($pat_row['allergies'] ?? '')): ?>
  <div style="background:var(--danger-light);color:var(--danger);border-left:4px solid var(--danger);border-radius:0 12px 12px 0;padding:1rem 1.5rem;margin-bottom:.8rem;font-size:1.3rem;display:flex;align-items:center;gap:.8rem;">
    <i class="fas fa-exclamation-triangle"></i><div><strong>Known Allergies:</strong> <?= htmlspecialchars($pat_row['allergies']) ?></div>
  </div>
  <?php endif; ?>
  <?php if (!empty($pat_row['chronic_conditions'] ?? '')): ?>
  <div style="background:var(--warning-light);color:var(--warning);border-left:4px solid var(--warning);border-radius:0 12px 12px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;display:flex;align-items:center;gap:.8rem;">
    <i class="fas fa-heartbeat"></i><div><strong>Chronic Conditions:</strong> <?= htmlspecialchars($pat_row['chronic_conditions']) ?></div>
  </div>
  <?php endif; ?>

  <!-- Main Two-Column Grid -->
  <div class="ov-main-grid" style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">

    <!-- LEFT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">

      <!-- Upcoming Appointments -->
      <div class="adm-card">
        <div class="adm-card-header">
          <h3><i class="fas fa-calendar-alt" style="color:var(--primary);"></i> Upcoming Appointments</h3>
          <button class="btn-icon btn btn-primary btn-sm" onclick="showTab('book',document.querySelector('.adm-nav-item[onclick*=book]'))">
            <span class="btn-text"><i class="fas fa-plus"></i> Book New</span>
          </button>
        </div>
        <?php if (empty($upcoming)): ?>
        <div style="text-align:center;padding:3rem;color:var(--text-muted);">
          <i class="fas fa-calendar-times" style="font-size:2.8rem;margin-bottom:1rem;opacity:.3;display:block;"></i>
          <p style="font-size:1.3rem;margin-bottom:1rem;">No upcoming appointments</p>
          <button class="btn-icon btn btn-primary" onclick="showTab('book',document.querySelector('.adm-nav-item[onclick*=book]'))">
            <span class="btn-text"><i class="fas fa-calendar-plus"></i> Book Now</span>
          </button>
        </div>
        <?php else: ?>
        <div style="padding:.5rem 1.5rem;">
          <?php foreach ($upcoming as $apt):
            $adt = new DateTime($apt['appointment_date']);
            $isToday = ($apt['appointment_date'] === $today);
            $sc = ($apt['status'] === 'Confirmed' || $apt['status'] === 'Approved') ? 'success' : (($apt['status'] === 'Pending') ? 'warning' : 'info');
          ?>
          <div class="ov-appt-item">
            <div class="ov-appt-date-badge" style="<?= $isToday ? 'background:linear-gradient(135deg,var(--danger),#c0392b)' : '' ?>">
              <div class="day"><?= $adt->format('d') ?></div>
              <div class="mon"><?= $adt->format('M') ?></div>
            </div>
            <div style="flex:1;">
              <div style="font-weight:700;font-size:1.35rem;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                Dr. <?= htmlspecialchars($apt['doctor_name']) ?>
                <?= $isToday ? '<span style="background:var(--danger);color:#fff;border-radius:12px;padding:.1rem .7rem;font-size:.9rem;font-weight:700;">Today</span>' : '' ?>
              </div>
              <div style="font-size:1.15rem;color:var(--text-secondary);display:flex;gap:1rem;flex-wrap:wrap;margin:.3rem 0;">
                <span><i class="fas fa-stethoscope" style="color:var(--role-accent);"></i> <?= htmlspecialchars($apt['specialization']) ?></span>
                <span><i class="fas fa-clock" style="color:var(--primary);"></i> <?= date('g:i A', strtotime($apt['appointment_time'])) ?></span>
                <span><i class="fas fa-tag" style="color:var(--warning);"></i> <?= htmlspecialchars($apt['service_type'] ?? 'Consultation') ?></span>
              </div>
              <span class="adm-badge adm-badge-<?= $sc ?>"><?= $apt['status'] ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Recent Activity Timeline -->
      <div class="adm-card">
        <div class="adm-card-header">
          <h3><i class="fas fa-clock-rotate-left" style="color:var(--role-accent);"></i> Recent Activity</h3>
        </div>
        <div style="padding:1.5rem;">
          <?php if (empty($activity)): ?>
          <p style="text-align:center;padding:2rem;color:var(--text-muted);font-size:1.3rem;">No recent activity</p>
          <?php else: ?>
          <div style="position:relative;">
            <div style="position:absolute;left:11px;top:0;bottom:0;width:2px;background:var(--border);border-radius:1px;"></div>
            <?php foreach ($activity as $act):
              $dotMap = ['Appointment' => 'var(--primary)', 'Prescription' => 'var(--warning)', 'Lab Test' => 'var(--info)'];
              $dotColor = $dotMap[$act['type']] ?? 'var(--text-muted)';
              $icoMap = ['Appointment' => 'fa-calendar', 'Prescription' => 'fa-pills', 'Lab Test' => 'fa-flask'];
              $ico = $icoMap[$act['type']] ?? 'fa-circle';
              $sc = ($act['status'] === 'Completed' || $act['status'] === 'Approved') ? 'success' : (($act['status'] === 'Pending') ? 'warning' : 'info');
            ?>
            <div style="display:flex;align-items:flex-start;gap:1.2rem;padding:.8rem 0 .8rem 1rem;margin-left:.4rem;">
              <div style="width:22px;height:22px;border-radius:50%;background:<?= $dotColor ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;margin-top:.1rem;position:relative;z-index:1;box-shadow:0 0 0 3px var(--surface);">
                <i class="fas <?= $ico ?>" style="font-size:.7rem;"></i>
              </div>
              <div style="flex:1;">
                <div style="font-size:1.25rem;font-weight:600;color:var(--text-primary);"><?= $act['type'] ?> <span class="adm-badge adm-badge-<?= $sc ?>" style="font-size:.9rem;"><?= $act['status'] ?></span></div>
                <div style="font-size:1.15rem;color:var(--text-secondary);"><?= htmlspecialchars($act['detail']) ?></div>
                <div style="font-size:1.05rem;color:var(--text-muted);margin-top:.2rem;"><i class="fas fa-clock"></i> <?= date('d M, g:i A', strtotime($act['ts'])) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">

      <!-- Countdown Card -->
      <?php if ($next_appt && $days_until !== null): ?>
      <div class="ov-countdown" style="<?= $days_until === 0 ? 'background:linear-gradient(135deg,#e74c3c,#c0392b)' : 'background:linear-gradient(135deg,#1abc9c,#27ae60)' ?>">
        <div style="font-size:1.2rem;opacity:.9;margin-bottom:.3rem;"><i class="fas fa-clock"></i> Next Appointment In</div>
        <span class="num"><?= $days_until ?></span>
        <div class="label"><?= $days_until === 0 ? 'Today!' : ($days_until === 1 ? 'Day' : 'Days') ?></div>
        <div class="detail"><i class="fas fa-calendar-day"></i> <?= date('l, d M', strtotime($next_appt['appointment_date'])) ?> @ <?= date('g:i A', strtotime($next_appt['appointment_time'])) ?></div>
      </div>
      <?php endif; ?>

      <!-- Quick Actions -->
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-bolt" style="color:var(--warning);"></i> Quick Actions</h3></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;padding:1.2rem;">
          <a href="#" onclick="showTab('book',document.querySelector('.adm-nav-item[onclick*=book]'));return false;" class="ov-quick-card">
            <div class="ov-quick-icon" style="color:var(--primary);"><i class="fas fa-calendar-plus"></i></div>
            <div class="ov-quick-label">Book</div>
          </a>
          <a href="#" onclick="showTab('prescriptions',document.querySelector('.adm-nav-item[onclick*=prescriptions]'));return false;" class="ov-quick-card">
            <div class="ov-quick-icon" style="color:var(--warning);"><i class="fas fa-pills"></i></div>
            <div class="ov-quick-label">Rx</div>
          </a>
          <a href="#" onclick="showTab('records',document.querySelector('.adm-nav-item[onclick*=records]'));return false;" class="ov-quick-card">
            <div class="ov-quick-icon" style="color:var(--info);"><i class="fas fa-file-medical"></i></div>
            <div class="ov-quick-label">Records</div>
          </a>
          <a href="#" onclick="showTab('billing',document.querySelector('.adm-nav-item[onclick*=billing]'));return false;" class="ov-quick-card">
            <div class="ov-quick-icon" style="color:var(--role-accent);"><i class="fas fa-receipt"></i></div>
            <div class="ov-quick-label">Billing</div>
          </a>
        </div>
      </div>

      <!-- Health Snapshot -->
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-heart-pulse" style="color:#e74c3c;"></i> Health Snapshot</h3></div>
        <div style="padding:1.2rem 1.5rem;">
          <div class="ov-health-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-tint" style="color:#e74c3c;margin-right:.5rem;"></i>Blood Type</span>
            <strong style="font-size:1.35rem;color:var(--text-primary);"><?= htmlspecialchars($pat_row['blood_group'] ?? '—') ?></strong>
          </div>
          <?php if ($age): ?>
          <div class="ov-health-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-birthday-cake" style="color:var(--role-accent);margin-right:.5rem;"></i>Age</span>
            <strong style="font-size:1.35rem;color:var(--text-primary);"><?= $age ?> yrs</strong>
          </div>
          <?php endif; ?>
          <div class="ov-health-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-venus-mars" style="color:var(--primary);margin-right:.5rem;"></i>Gender</span>
            <strong style="font-size:1.35rem;color:var(--text-primary);"><?= htmlspecialchars($pat_row['gender'] ?? '—') ?></strong>
          </div>
          <div class="ov-health-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-calendar-check" style="color:var(--success);margin-right:.5rem;"></i>Last Visit</span>
            <strong style="font-size:1.2rem;color:var(--text-primary);"><?= $last_visit ? date('d M Y', strtotime($last_visit['visit_date'])) : 'No visits' ?></strong>
          </div>
          <div class="ov-health-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-prescription" style="color:var(--warning);margin-right:.5rem;"></i>Active Rx</span>
            <strong style="font-size:1.35rem;color:var(--text-primary);"><?= $stats['active_rx'] ?></strong>
          </div>
          <div class="ov-health-row">
            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-phone-alt" style="color:var(--danger);margin-right:.5rem;"></i>Emergency</span>
            <strong style="font-size:1.35rem;color:var(--text-primary);"><?= $stats['emerg_contacts'] ?> contact<?= $stats['emerg_contacts'] !== 1 ? 's' : '' ?></strong>
          </div>
          <?php if ($outstanding > 0): ?>
          <div class="ov-health-row" style="background:var(--danger-light);border-radius:8px;padding:.8rem 1rem;margin-top:.5rem;">
            <span style="color:var(--danger);font-size:1.2rem;font-weight:600;"><i class="fas fa-receipt" style="margin-right:.5rem;"></i>Balance Due</span>
            <strong style="font-size:1.35rem;color:var(--danger);">GHS <?= number_format($outstanding, 2) ?></strong>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
