<!-- ═══════════════════════════════════════════════════════════
     MODULE 1: OVERVIEW / WELCOME — tab_overview.php
     ═══════════════════════════════════════════════════════════ -->
<div id="sec-overview" class="dash-section">

<!-- ── Hero Banner ── -->
<div class="nurse-hero">
  <div class="nurse-avatar-hero">
    <?php $avi=$nurse_row['profile_photo']??$nurse_row['profile_image']??'';
      if($avi && $avi!=='default-avatar.png'):?>
      <img src="/RMU-Medical-Management-System/<?=e($avi)?>" alt="Avatar">
    <?php else:?>
      <i class="fas fa-user-nurse"></i>
    <?php endif;?>
  </div>
  <div class="nurse-hero-info">
    <h2>Welcome, <?=e(explode(' ',$nurse_row['full_name']??$nurseName)[0])?> 👋</h2>
    <p><?=e($nurse_row['designation']??'Staff Nurse')?> — <?=e($nurse_row['department']??'Nursing')?></p>
    <div style="display:flex;gap:.8rem;margin-top:.8rem;flex-wrap:wrap;">
      <span class="hero-badge"><i class="fas fa-clock"></i> <?=e($nurse_row['shift_type']??'Morning')?> Shift</span>
      <?php if(($nurse_row['ward_assigned']??'')):?><span class="hero-badge"><i class="fas fa-hospital"></i> <?=e($nurse_row['ward_assigned'])?></span><?php endif;?>
      <span class="hero-badge"><i class="fas fa-calendar-day"></i> <?=date('l, d M Y')?></span>
      <?php if($current_shift):?><span class="hero-badge"><i class="fas fa-hourglass-half"></i> <?=date('h:i A',strtotime($current_shift['start_time']))?>–<?=date('h:i A',strtotime($current_shift['end_time']))?></span><?php endif;?>
    </div>
  </div>
</div>

<!-- ── Summary Cards ── -->
<div class="adm-summary-strip">
  <div class="adm-mini-card" onclick="showTab('vitals',null)">
    <div class="adm-mini-card-num blue"><?=$stats['patients_today']?></div>
    <div class="adm-mini-card-label">Patients Assigned</div>
  </div>
  <div class="adm-mini-card" onclick="showTab('medications',null)">
    <div class="adm-mini-card-num orange"><?=$stats['pending_meds']?></div>
    <div class="adm-mini-card-label">Pending Meds</div>
  </div>
  <div class="adm-mini-card" onclick="showTab('vitals',null)">
    <div class="adm-mini-card-num <?=$stats['vitals_due']>0?'red':'green'?>"><?=$stats['vitals_due']?></div>
    <div class="adm-mini-card-label">Vitals Due</div>
  </div>
  <div class="adm-mini-card" onclick="showTab('tasks',null)">
    <div class="adm-mini-card-num <?=$stats['overdue_tasks']>0?'red':'teal'?>"><?=$stats['pending_tasks']?><?php if($stats['overdue_tasks']>0):?><span style="font-size:1.1rem;color:var(--danger);"> (<?=$stats['overdue_tasks']?> overdue)</span><?php endif;?></div>
    <div class="adm-mini-card-label">Pending Tasks</div>
  </div>
  <div class="adm-mini-card" onclick="showTab('emergency',null)">
    <div class="adm-mini-card-num <?=$stats['active_emergencies']>0?'red':'green'?>"><?=$stats['active_emergencies']?></div>
    <div class="adm-mini-card-label">Active Alerts</div>
  </div>
  <div class="adm-mini-card" onclick="showTab('tasks',null)">
    <div class="adm-mini-card-num <?=$stats['handover_pending']>0?'orange':'green'?>"><?=$stats['handover_pending']>0?'Pending':'Done'?></div>
    <div class="adm-mini-card-label">Shift Handover</div>
  </div>
</div>

<!-- ── Quick Action Buttons ── -->
<div class="quick-actions">
  <button class="quick-action-btn" onclick="showTab('vitals',null)"><i class="fas fa-heartbeat"></i> Record Vitals</button>
  <button class="quick-action-btn" onclick="showTab('medications',null)"><i class="fas fa-pills"></i> Administer Medication</button>
  <button class="quick-action-btn" onclick="openModal('emergencyModal')"><i class="fas fa-triangle-exclamation" style="color:var(--danger);"></i> Emergency Alert</button>
  <button class="quick-action-btn" onclick="showTab('notes',null)"><i class="fas fa-notes-medical"></i> Add Nursing Note</button>
  <button class="quick-action-btn" onclick="showTab('fluids',null)"><i class="fas fa-droplet"></i> IV & Fluids</button>
  <button class="quick-action-btn" onclick="showTab('messages',null)"><i class="fas fa-comment-medical"></i> Message Doctor</button>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

  <!-- ── Critical Patient Alerts ── -->
  <div class="info-card" style="grid-column:1/3;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;">
      <h3 style="font-size:1.6rem;font-weight:700;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-triangle-exclamation" style="color:var(--danger);"></i> Critical Patient Alerts</h3>
      <span class="badge badge-danger"><?=count($flagged_vitals)?> flagged</span>
    </div>
    <?php if(empty($flagged_vitals)):?>
      <p style="text-align:center;color:var(--text-muted);padding:2rem 0;"><i class="fas fa-check-circle" style="color:var(--success);margin-right:.5rem;"></i> No critical vital flags today</p>
    <?php else:?>
      <?php foreach(array_slice($flagged_vitals,0,5) as $fv):?>
        <div class="alert-card" style="border-left:3px solid var(--danger);">
          <div class="alert-icon red"><i class="fas fa-heartbeat"></i></div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:1.3rem;"><?=e($fv['patient_name'])?></div>
            <div style="font-size:1.15rem;color:var(--text-secondary);margin-top:.2rem;">
              <?=e($fv['flag_reason']??'Abnormal reading detected')?>
            </div>
            <div style="font-size:1.05rem;color:var(--text-muted);margin-top:.3rem;">
              BP: <?=e($fv['bp_systolic']??'-')?>/<?=e($fv['bp_diastolic']??'-')?> | HR: <?=e($fv['pulse_rate']??'-')?> | Temp: <?=e($fv['temperature']??'-')?>°C | SpO2: <?=e($fv['oxygen_saturation']??'-')?>%
              · <?=date('h:i A',strtotime($fv['recorded_at']))?>
            </div>
          </div>
          <span class="badge badge-danger"><?=$fv['doctor_notified']?'Doctor Notified':'Pending'?></span>
        </div>
      <?php endforeach;?>
    <?php endif;?>
  </div>

  <!-- ── Recent Activity Feed ── -->
  <div class="info-card">
    <h3 style="font-size:1.6rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-clock-rotate-left" style="color:var(--role-accent);"></i> Recent Activity</h3>
    <?php if(empty($activity)):?>
      <p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No activity recorded yet today</p>
    <?php else:?>
      <?php foreach(array_slice($activity,0,8) as $a):
        $dotcolor='teal';
        if(($a['type']??'')==='Task') $dotcolor='blue';
        elseif(($a['type']??'')==='Medication') $dotcolor='green';
        elseif(($a['type']??'')==='Note') $dotcolor='orange';
      ?>
        <div class="activity-item">
          <div class="activity-dot <?=$dotcolor?>"></div>
          <div style="flex:1;">
            <div style="font-size:1.2rem;font-weight:500;color:var(--text-primary);"><?=e($a['description']??'')?></div>
            <div style="font-size:1.05rem;color:var(--text-muted);"><?=$a['ts']?date('h:i A',strtotime($a['ts'])):''?></div>
          </div>
        </div>
      <?php endforeach;?>
    <?php endif;?>
  </div>

  <!-- ── Mini Analytics ── -->
  <div class="info-card">
    <h3 style="font-size:1.6rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-chart-pie" style="color:var(--role-accent);"></i> Today's Performance</h3>
    <?php
      $total_tasks = $stats['tasks_done_today'] + $stats['pending_tasks'] + $stats['overdue_tasks'];
      $task_rate   = $total_tasks > 0 ? round(($stats['tasks_done_today']/$total_tasks)*100) : 0;
      $total_meds  = $stats['meds_given_today'] + $stats['pending_meds'];
      $med_rate    = $total_meds > 0 ? round(($stats['meds_given_today']/$total_meds)*100) : 0;
    ?>
    <div style="display:flex;flex-direction:column;gap:1.4rem;">
      <!-- Task Completion -->
      <div>
        <div style="display:flex;justify-content:space-between;font-size:1.2rem;font-weight:600;margin-bottom:.4rem;">
          <span>Task Completion</span><span><?=$task_rate?>% (<?=$stats['tasks_done_today']?>/<?=$total_tasks?>)</span>
        </div>
        <div style="height:6px;background:var(--surface-2);border-radius:3px;overflow:hidden;">
          <div style="height:100%;width:<?=$task_rate?>%;background:var(--success);border-radius:3px;transition:width .6s ease;"></div>
        </div>
      </div>
      <!-- Med Administration -->
      <div>
        <div style="display:flex;justify-content:space-between;font-size:1.2rem;font-weight:600;margin-bottom:.4rem;">
          <span>Medication Admin</span><span><?=$med_rate?>% (<?=$stats['meds_given_today']?>/<?=$total_meds?>)</span>
        </div>
        <div style="height:6px;background:var(--surface-2);border-radius:3px;overflow:hidden;">
          <div style="height:100%;width:<?=$med_rate?>%;background:var(--role-accent);border-radius:3px;transition:width .6s ease;"></div>
        </div>
      </div>
      <!-- Vitals Recorded -->
      <div>
        <div style="display:flex;justify-content:space-between;font-size:1.2rem;font-weight:600;margin-bottom:.4rem;">
          <span>Vitals Recorded</span><span><?=$stats['vitals_today']?> recorded / <?=$stats['vitals_due']?> due</span>
        </div>
        <div style="height:6px;background:var(--surface-2);border-radius:3px;overflow:hidden;">
          <?php $v_rate = ($stats['vitals_today']+$stats['vitals_due']) > 0 ? round(($stats['vitals_today']/($stats['vitals_today']+$stats['vitals_due']))*100) : 0; ?>
          <div style="height:100%;width:<?=$v_rate?>%;background:var(--primary);border-radius:3px;transition:width .6s ease;"></div>
        </div>
      </div>
      <!-- Notes Today -->
      <div style="display:flex;align-items:center;gap:1rem;padding-top:.6rem;border-top:1px solid var(--border);">
        <div style="font-size:1.2rem;font-weight:600;">Nursing Notes Today</div>
        <span class="badge badge-info"><?=$stats['notes_today']?></span>
      </div>
    </div>
  </div>

</div>
</div><!-- /sec-overview -->
