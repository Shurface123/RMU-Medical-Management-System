<!-- ═══ SECTION F: PATIENT LOAD & STATISTICS ═══ -->
<?php
$stat_patients = (int)dbVal($conn,"SELECT COUNT(DISTINCT patient_id) FROM bed_assignments WHERE nurse_id=? AND status='Active'","i",[$nurse_pk]);
$stat_vitals   = (int)dbVal($conn,"SELECT COUNT(*) FROM patient_vitals WHERE nurse_id=? AND DATE(recorded_at)=CURDATE()","i",[$nurse_pk]);
$stat_meds     = (int)dbVal($conn,"SELECT COUNT(*) FROM medication_administration WHERE nurse_id=? AND DATE(administered_at)=CURDATE() AND status='Administered'","i",[$nurse_pk]);
$stat_tasks_wk = (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=? AND status='Completed' AND completed_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)","i",[$nurse_pk]);
$stat_notes_mo = (int)dbVal($conn,"SELECT COUNT(*) FROM nursing_notes WHERE nurse_id=? AND created_at>=DATE_SUB(CURDATE(),INTERVAL 30 DAY)","i",[$nurse_pk]);
$stat_handovers= (int)dbVal($conn,"SELECT COUNT(*) FROM shift_handover WHERE outgoing_nurse_id=?","i",[$nurse_pk]);
$stat_emerg    = (int)dbVal($conn,"SELECT COUNT(*) FROM emergency_alerts WHERE triggered_by=?","i",[$nurse_pk]);
$stat_total_t  = (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=?","i",[$nurse_pk]);
$stat_done_t   = (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=? AND status='Completed'","i",[$nurse_pk]);
$task_rate     = $stat_total_t ? round(($stat_done_t/$stat_total_t)*100) : 0;
// 7-day task volume
$task7 = dbSelect($conn,"SELECT DATE(completed_at) AS d, COUNT(*) AS c FROM nurse_tasks WHERE nurse_id=? AND status='Completed' AND completed_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY d ORDER BY d","i",[$nurse_pk]);
// Task type distribution
$type_vitals = (int)dbVal($conn,"SELECT COUNT(*) FROM patient_vitals WHERE nurse_id=?","i",[$nurse_pk]);
$type_meds   = (int)dbVal($conn,"SELECT COUNT(*) FROM medication_administration WHERE nurse_id=? AND status='Administered'","i",[$nurse_pk]);
$type_notes  = (int)dbVal($conn,"SELECT COUNT(*) FROM nursing_notes WHERE nurse_id=?","i",[$nurse_pk]);
$type_emerg  = $stat_emerg;
?>
<div class="profile-section" id="section-stats">
  <h3><i class="fas fa-chart-bar" style="color:var(--info);"></i> Patient Load & Statistics</h3>
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.8rem;margin-bottom:1.5rem;">
    <div class="stat-mini"><div class="stat-num"><?=$stat_patients?></div><div class="stat-lbl">Patients Assigned</div></div>
    <div class="stat-mini"><div class="stat-num"><?=$stat_vitals?></div><div class="stat-lbl">Vitals (Today)</div></div>
    <div class="stat-mini"><div class="stat-num"><?=$stat_meds?></div><div class="stat-lbl">Meds (Today)</div></div>
    <div class="stat-mini"><div class="stat-num"><?=$stat_tasks_wk?></div><div class="stat-lbl">Tasks (Week)</div></div>
    <div class="stat-mini"><div class="stat-num"><?=$task_rate?>%</div><div class="stat-lbl">Completion Rate</div></div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.8rem;margin-bottom:1.5rem;">
    <div class="stat-mini"><div class="stat-num"><?=$stat_notes_mo?></div><div class="stat-lbl">Notes (Month)</div></div>
    <div class="stat-mini"><div class="stat-num"><?=$stat_handovers?></div><div class="stat-lbl">Handovers</div></div>
    <div class="stat-mini"><div class="stat-num"><?=$stat_emerg?></div><div class="stat-lbl">Emergencies</div></div>
    <div class="stat-mini"><div class="stat-num"><?php
      $shift_counts = dbSelect($conn,"SELECT shift_type, COUNT(*) AS c FROM nurse_tasks nt JOIN nurse_shifts ns ON nt.nurse_id=ns.nurse_id AND DATE(nt.created_at)=ns.shift_date WHERE nt.nurse_id=? GROUP BY shift_type ORDER BY c DESC LIMIT 1","i",[$nurse_pk]);
      echo e($shift_counts[0]['shift_type']??'—');
    ?></div><div class="stat-lbl">Busiest Shift</div></div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
    <div><canvas id="taskVolumeChart" height="180"></canvas></div>
    <div><canvas id="taskTypeChart" height="180"></canvas></div>
  </div>
</div>

<!-- ═══ SECTION G: ACCOUNT & SECURITY ═══ -->
<div class="profile-section" id="section-security">
  <h3><i class="fas fa-shield-alt" style="color:var(--danger);"></i> Account & Security</h3>
  <div class="profile-grid two">
    <div>
      <h4>Change Password</h4>
      <div class="form-group"><input id="pw_current" type="password" class="form-control" placeholder="Current Password"></div>
      <div class="form-group"><input id="pw_new" type="password" class="form-control" placeholder="New Password" oninput="checkPasswordStrength(this.value)"></div>
      <div id="pw_strength" style="font-size:1.05rem;margin-bottom:.5rem;"></div>
      <div class="form-group"><input id="pw_confirm" type="password" class="form-control" placeholder="Confirm New Password"></div>
      <button class="btn btn-sm btn-primary" onclick="changePassword()"><i class="fas fa-key"></i> Change Password</button>
      <h4 style="margin-top:1.5rem;">Two-Factor Authentication</h4>
      <div style="display:flex;align-items:center;gap:1rem;">
        <label class="toggle-switch"><input type="checkbox" id="twoFaToggle" <?=(int)($nurse_row['two_fa_enabled']??0)?'checked':''?> onchange="toggle2FA(this.checked)"><span class="toggle-slider"></span></label>
        <span style="font-size:1.15rem;font-weight:500;"><?=(int)($nurse_row['two_fa_enabled']??0)?'Enabled':'Disabled'?></span>
      </div>
      <small class="text-muted">When enabled, you will receive a code via email on each login</small>
    </div>
    <div>
      <h4>Active Sessions</h4>
      <?php foreach($nurse_sess as $si => $s): $isCurrent = ($s['session_id']??'')===session_id(); ?>
      <div class="session-row">
        <i class="fas fa-<?=stripos($s['device']??'','Mobile')!==false?'mobile-alt':'desktop'?>" style="font-size:1.5rem;color:var(--text-muted);"></i>
        <div style="flex:1;">
          <div style="font-weight:500;"><?=e(substr($s['browser']??$s['device']??'Unknown',0,40))?> <?=$isCurrent?'<span class="badge badge-success">Current</span>':''?></div>
          <small class="text-muted"><?=e($s['ip_address']??'')?> · <?=date('d M h:i A',strtotime($s['login_time']))?></small>
        </div>
        <?php if(!$isCurrent):?><button class="btn btn-xs btn-danger" onclick="logoutSession(<?=$s['id']?>)"><i class="fas fa-sign-out-alt"></i></button><?php endif;?>
      </div>
      <?php endforeach;?>
      <?php if(count($nurse_sess)>1):?><button class="btn btn-sm btn-outline" style="margin-top:.5rem;" onclick="logoutAllSessions()"><i class="fas fa-sign-out-alt"></i> Log Out All Other Devices</button><?php endif;?>
      <h4 style="margin-top:1rem;">Recent Activity</h4>
      <div style="max-height:200px;overflow-y:auto;">
      <?php foreach(array_slice($nurse_log,0,10) as $l):?>
        <div style="padding:.3rem 0;font-size:1.05rem;color:var(--text-secondary);border-bottom:1px solid var(--border);">
          <strong><?=e($l['action_type'])?></strong> — <?=e(substr($l['action_description'],0,60))?> · <?=date('d M h:i A',strtotime($l['created_at']))?>
          <small class="text-muted" style="display:block;"><?=e($l['ip_address']??'')?></small>
        </div>
      <?php endforeach;?>
      </div>
    </div>
  </div>
  <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border);">
    <button class="btn btn-sm btn-outline" style="color:var(--danger);border-color:var(--danger);" onclick="requestDeactivation()"><i class="fas fa-user-slash"></i> Request Account Deactivation</button>
    <small class="text-muted" style="margin-left:.5rem;">Sent to admin for review — your data will not be deleted</small>
  </div>
</div>
