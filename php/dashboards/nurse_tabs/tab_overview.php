<?php
// ============================================================
// NURSE DASHBOARD - OVERVIEW TAB (MODULE 1)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT & WARD ─────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT shift_id, ward_assigned, handover_submitted FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'Not Assigned';
$shift_active  = $current_shift ? true : false;
$handover_done = $current_shift['handover_submitted'] ?? 0;

// ── GET STATS ────────────────────────────────────────────────
$stats = [
    'patients_assigned' => 0,
    'pending_meds'      => 0,
    'vitals_due'        => 0,
    'pending_tasks'     => 0,
    'overdue_tasks'     => 0,
    'active_emergencies'=> 0
];

if ($ward_assigned !== 'Not Assigned') {
    $stats['patients_assigned'] = qval($conn, "SELECT COUNT(*) FROM bed_assignments ba JOIN beds b ON ba.bed_id=b.id WHERE b.ward='$ward_assigned' AND ba.status='Occupied'");
}

$stats['pending_meds']        = qval($conn, "SELECT COUNT(*) FROM medication_administration WHERE status='Pending' AND scheduled_time <= NOW() + INTERVAL 2 HOUR");
$stats['pending_tasks']       = qval($conn, "SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=$nurse_pk AND status IN ('Pending','In Progress')");
$stats['overdue_tasks']       = qval($conn, "SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=$nurse_pk AND status IN ('Pending','In Progress') AND due_time < NOW()");
$stats['active_emergencies']  = qval($conn, "SELECT COUNT(*) FROM emergency_alerts WHERE status='Active'");
$stats['vitals_due']          = qval($conn, "
    SELECT COUNT(DISTINCT pat.id)
    FROM bed_assignments ba 
    JOIN beds b ON ba.bed_id=b.id 
    JOIN patients pat ON ba.patient_id=pat.id
    LEFT JOIN patient_vitals pv ON pat.id=pv.patient_id AND pv.recorded_at >= NOW() - INTERVAL 4 HOUR
    WHERE b.ward='$ward_assigned' AND ba.status='Occupied' AND pv.id IS NULL
");

// ── CRITICAL ALERTS ────────────────────────────────────────
$critical_vitals = [];
$q = mysqli_query($conn, "
    SELECT pv.*, u.name AS patient_name, b.ward, b.bed_number 
    FROM patient_vitals pv
    JOIN patients p ON pv.patient_id=p.id
    JOIN users u ON p.user_id=u.id
    JOIN bed_assignments ba ON p.id=ba.patient_id AND ba.status='Occupied'
    JOIN beds b ON ba.bed_id=b.id
    WHERE pv.is_flagged=1 AND pv.recorded_at >= NOW() - INTERVAL 24 HOUR
    ORDER BY pv.recorded_at DESC LIMIT 5
");
if($q) while($r=mysqli_fetch_assoc($q)) $critical_vitals[]=$r;

// ── RECENT ACTIVITY ────────────────────────────────────────
$recent_activity = [];
$q = mysqli_query($conn, "
    SELECT action, module, timestamp 
    FROM nurse_activity_log 
    WHERE nurse_id=$nurse_pk 
    ORDER BY timestamp DESC LIMIT 8
");
if($q) while($r=mysqli_fetch_assoc($q)) $recent_activity[]=$r;

// ── MINI ANALYTICS ──────────────────────────────────────────
$tasks_total = qval($conn, "SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=$nurse_pk AND DATE(created_at)='$today'");
$tasks_done  = qval($conn, "SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=$nurse_pk AND DATE(completed_at)='$today' AND status='Completed'");
$task_rate   = $tasks_total > 0 ? round(($tasks_done / $tasks_total) * 100) : 0;
$meds_total  = qval($conn, "SELECT COUNT(*) FROM medication_administration WHERE DATE(scheduled_time)='$today'");
$meds_done   = qval($conn, "SELECT COUNT(*) FROM medication_administration WHERE DATE(administered_at)='$today' AND status='Administered'");
$med_rate    = $meds_total > 0 ? round(($meds_done / $meds_total) * 100) : 0;
$greeting    = date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening');
?>

<div class="tab-content active" id="overview">

    <!-- ── HERO BANNER ── -->
    <div class="staff-hero" style="margin-bottom:2.5rem; background: linear-gradient(135deg, var(--primary) 0%, #34495e 100%);">
        <div class="staff-hero-avatar shadow-sm">
            <?php if(!empty($nurse_row['profile_photo'])): ?>
                <img src="/RMU-Medical-Management-System/<?= e($nurse_row['profile_photo']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%; border:3px solid rgba(255,255,255,0.2);">
            <?php else: ?>
                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.1); border-radius:50%;">
                    <i class="fas fa-user-nurse" style="font-size:3.5rem; color:#fff;"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="staff-hero-info" style="flex:1;">
            <h2 style="font-size:2.4rem; font-weight:800; letter-spacing:-0.02em; margin-bottom:.5rem;">Good <?= $greeting ?>, <span style="color:var(--info);"><?= e($nurse_row['full_name'] ?? 'Nurse') ?></span></h2>
            <div style="display:flex; align-items:center; gap:1.2rem; font-size:1.3rem; opacity:0.9; margin-bottom:1rem;">
                <span><i class="fas fa-hospital-alt" style="margin-right:.4rem; color:var(--info);"></i> Ward: <strong><?= e($ward_assigned) ?></strong></span>
                <span style="opacity:0.4;">|</span>
                <span><i class="fas fa-user-clock" style="margin-right:.4rem; color:var(--info);"></i> Shift: <strong><?= $shift_active ? 'Morning Shift' : 'Off-Duty' ?></strong></span>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:.8rem;">
                <span class="hero-badge" style="background:rgba(255,255,255,0.1); backdrop-filter:blur(4px);"><i class="far fa-calendar-check" style="margin-right:.4rem;"></i> <?= date('D, d M Y') ?></span>
                <?php if($shift_active): ?>
                    <span class="hero-badge" style="background:rgba(46,204,113,0.2); border:1px solid rgba(46,204,113,0.4); color:#2ecc71;">
                        <i class="fas fa-circle-notch fa-spin" style="font-size:.7rem; margin-right:.4rem;"></i> Active Now
                    </span>
                <?php endif; ?>
                <?php if($stats['active_emergencies'] > 0): ?>
                    <span class="hero-badge" style="background:rgba(231,76,60,0.25); border:1px solid rgba(231,76,60,0.4); color:#ff7675; animation:pulse 2s infinite;">
                        <i class="fas fa-exclamation-triangle" style="margin-right:.4rem;"></i> <?= (int)$stats['active_emergencies'] ?> Critical Alert<?= $stats['active_emergencies']>1?'s':''?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <!-- Quick Actions -->
        <div style="display:flex; gap:1rem; align-items:center; background:rgba(0,0,0,0.15); padding:1.2rem; border-radius:15px; border:1px solid rgba(255,255,255,0.1);">
            <div style="text-align:right; margin-right:.5rem;">
                <small style="display:block; text-transform:uppercase; font-size:.9rem; font-weight:700; color:rgba(255,255,255,0.5); letter-spacing:.05em;">Quick Entry</small>
                <span style="font-weight:600; color:#fff;">Record Data</span>
            </div>
            <a href="?tab=patients" class="adm-btn adm-btn-sm" style="background:#fff; color:var(--primary); font-weight:700; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                <i class="fas fa-heartbeat"></i> Vitals
            </a>
            <a href="?tab=medications" class="adm-btn adm-btn-sm" style="background:var(--info); color:#fff; font-weight:700;">
                <i class="fas fa-pills"></i> Meds
            </a>
            <a href="?tab=notes" class="adm-btn adm-btn-sm" style="background:rgba(255,255,255,0.15); color:#fff; font-weight:600; border:1px solid rgba(255,255,255,0.2);">
                <i class="fas fa-pen-nib"></i> Note
            </a>
        </div>
    </div>

    <!-- ── STATS STRIP ── -->
    <div class="adm-summary-strip" style="margin-bottom:2.5rem;">
        <div class="adm-mini-card">
            <div class="adm-mini-card-num blue"><?= (int)$stats['patients_assigned'] ?></div>
            <div class="adm-mini-card-label"><i class="fas fa-procedures text-primary" style="margin-right:.5rem;"></i>Patients Assigned</div>
        </div>
        <div class="adm-mini-card">
            <div class="adm-mini-card-num <?= $stats['vitals_due'] > 0 ? 'red' : 'green' ?>"><?= (int)$stats['vitals_due'] ?></div>
            <div class="adm-mini-card-label"><i class="fas fa-stethoscope text-info" style="margin-right:.5rem;"></i>Vitals Due</div>
        </div>
        <div class="adm-mini-card">
            <div class="adm-mini-card-num orange"><?= (int)$stats['pending_meds'] ?></div>
            <div class="adm-mini-card-label"><i class="fas fa-capsules text-warning" style="margin-right:.5rem;"></i>Pending Meds</div>
        </div>
        <div class="adm-mini-card">
            <div class="adm-mini-card-num <?= $stats['overdue_tasks'] > 0 ? 'red' : 'blue' ?>"><?= (int)$stats['pending_tasks'] ?></div>
            <div class="adm-mini-card-label">
                <i class="fas fa-tasks text-primary" style="margin-right:.5rem;"></i>Active Tasks
                <?php if($stats['overdue_tasks'] > 0): ?>
                    <span class="adm-badge adm-badge-danger" style="font-size:1rem; padding:.2rem .6rem; margin-left:.5rem;"><?= (int)$stats['overdue_tasks'] ?> overdue</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="adm-mini-card">
            <div class="adm-mini-card-num <?= $stats['active_emergencies'] > 0 ? 'red' : 'green' ?>"><?= (int)$stats['active_emergencies'] ?></div>
            <div class="adm-mini-card-label"><i class="fas fa-ambulance text-danger" style="margin-right:.5rem;"></i>Active Alerts</div>
        </div>
        <div class="adm-mini-card">
            <div class="adm-mini-card-num <?= $handover_done ? 'green' : 'orange' ?>">
                <i class="fas fa-exchange-alt" style="font-size:2.2rem; opacity:0.8;"></i>
            </div>
            <div class="adm-mini-card-label">Handover: <strong><?= $shift_active ? ($handover_done ? 'Submitted' : 'Pending') : 'N/A' ?></strong></div>
        </div>
    </div>

    <!-- ── CRITICAL ALERTS + ACTIVITY ── -->
    <div style="display:grid;grid-template-columns:1.6fr 1.1fr;gap:2rem;margin-bottom:2rem;">

        <!-- Critical Vitals -->
        <div class="adm-card">
            <div class="adm-card-header" style="justify-content:space-between;">
                <h3><i class="fas fa-exclamation-triangle text-danger"></i> Critical Alerts <span style="font-size:1.2rem; font-weight:500; color:var(--text-muted); margin-left:.5rem;">(Last 24h)</span></h3>
                <span class="adm-badge adm-badge-ghost"><?= count($critical_vitals) ?> Flagged</span>
            </div>
            <div class="adm-card-body" style="padding:0;">
                <?php if(count($critical_vitals) > 0): ?>
                    <div class="adm-table-wrap">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Location</th>
                                    <th>Recorded</th>
                                    <th>Alert Condition</th>
                                    <th>Action Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($critical_vitals as $cv): ?>
                                <tr>
                                    <td><strong><?= e($cv['patient_name']) ?></strong></td>
                                    <td><span class="adm-badge" style="background:var(--surface-2);"><?= e($cv['ward']) ?> · <?= e($cv['bed_number']) ?></span></td>
                                    <td style="color:var(--text-muted); font-size:1.15rem;"><?= date('H:i', strtotime($cv['recorded_at'])) ?></td>
                                    <td><span style="color:var(--danger); font-weight:700; font-size:1.2rem;"><?= e($cv['flag_reason'] ?? 'ABNORMAL') ?></span></td>
                                    <td>
                                        <?php if($cv['doctor_notified']): ?>
                                            <span class="adm-badge adm-badge-success"><i class="fas fa-check-circle"></i> MD Notified</span>
                                        <?php else: ?>
                                            <span class="adm-badge adm-badge-danger pulse-fade"><i class="fas fa-phone-slash"></i> MD Notification Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="padding:5rem 3rem; text-align:center;">
                        <div style="width:70px; height:70px; background:rgba(46,204,113,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
                            <i class="fas fa-check-double" style="font-size:2.5rem; color:var(--success);"></i>
                        </div>
                        <h4 style="font-size:1.6rem; font-weight:700; color:var(--text-primary);">All Vitals Stable</h4>
                        <p style="margin-top:.5rem; font-size:1.3rem; color:var(--text-muted);">No critical physiological flags recorded in the last 24 hours.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Progress + Activity Feed -->
        <div style="display:flex;flex-direction:column;gap:1.5rem;">

            <!-- Today's Progress -->
            <div class="adm-card shadow-sm">
                <div class="adm-card-header">
                    <h3><i class="fas fa-chart-line text-success"></i> Clinical Performance</h3>
                </div>
                <div class="adm-card-body" style="padding:2.2rem;">
                    <!-- Task Completion Bar -->
                    <div style="margin-bottom:2.5rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.8rem;">
                            <span style="font-size:1.2rem; font-weight:700; color:var(--text-secondary); letter-spacing:.02em;">TASK COMPLETION</span>
                            <span style="font-size:1.4rem; font-weight:800; color:var(--text-primary);"><?= $task_rate ?>%</span>
                        </div>
                        <div style="background:var(--surface-2); border:1px solid var(--border); border-radius:10px; height:12px; overflow:hidden; box-shadow:inset 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="height:100%; width:<?= $task_rate ?>%; background:linear-gradient(90deg, #2ecc71, #27ae60); border-radius:10px; transition:width .8s cubic-bezier(0.4, 0, 0.2, 1); box-shadow:0 0 10px rgba(46,204,113,0.3);"></div>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-top:.6rem;">
                            <small style="color:var(--text-muted); font-size:1.15rem;"><?= $tasks_done ?> of <?= $tasks_total ?> modules completed</small>
                            <?php if($task_rate >= 100): ?><i class="fas fa-check-circle text-success" title="Daily Goal Met"></i><?php endif; ?>
                        </div>
                    </div>
                    <!-- Medication Rate Bar -->
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.8rem;">
                            <span style="font-size:1.2rem; font-weight:700; color:var(--text-secondary); letter-spacing:.02em;">MAR ADHERENCE</span>
                            <span style="font-size:1.4rem; font-weight:800; color:var(--text-primary);"><?= $med_rate ?>%</span>
                        </div>
                        <div style="background:var(--surface-2); border:1px solid var(--border); border-radius:10px; height:12px; overflow:hidden; box-shadow:inset 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="height:100%; width:<?= $med_rate ?>%; background:linear-gradient(90deg, #3498db, #2980b9); border-radius:10px; transition:width .8s cubic-bezier(0.4, 0, 0.2, 1); box-shadow:0 0 10px rgba(52,152,219,0.3);"></div>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-top:.6rem;">
                            <small style="color:var(--text-muted); font-size:1.15rem;"><?= $meds_done ?> of <?= $meds_total ?> scheduled doses</small>
                            <?php if($med_rate >= 90): ?><i class="fas fa-award text-info" title="High Adherence"></i><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Feed -->
            <div class="adm-card shadow-sm" style="flex:1; display:flex; flex-direction:column;">
                <div class="adm-card-header">
                    <h3><i class="fas fa-history text-primary"></i> Live Activity Feed</h3>
                    <span class="adm-badge adm-badge-ghost">Nurse #<?= e($nurse_pk) ?></span>
                </div>
                <div class="adm-card-body" style="padding:0 2.5rem; flex:1; overflow-y:auto; scrollbar-width: thin;">
                    <?php if(empty($recent_activity)): ?>
                        <div style="padding:4rem 0; text-align:center; color:var(--text-muted);">
                            <i class="far fa-clipboard" style="font-size:3rem; opacity:0.1; margin-bottom:1rem; display:block;"></i>
                            <p>Standing by for logging...</p>
                        </div>
                    <?php else: ?>
                        <div style="padding:1rem 0;">
                            <?php foreach($recent_activity as $log):
                                $dot_color = 'var(--text-muted)';
                                $icon = 'fa-dot-circle';
                                if(strpos($log['module'],'vital')!==false) { $dot_color='var(--danger)'; $icon='fa-heartbeat'; }
                                elseif(strpos($log['module'],'med')!==false) { $dot_color='var(--info)'; $icon='fa-pills'; }
                                elseif(strpos($log['module'],'task')!==false) { $dot_color='var(--success)'; $icon='fa-tasks'; }
                                elseif(strpos($log['module'],'note')!==false) { $dot_color='var(--warning)'; $icon='fa-sticky-note'; }
                            ?>
                            <div class="activity-item" style="padding:1.5rem 0; border-bottom:1px solid var(--surface-2);">
                                <div class="activity-dot" style="background:<?= $dot_color ?>; width:12px; height:12px; box-shadow:0 0 0 4px rgba(0,0,0,0.03);"></div>
                                <div style="flex:1; margin-left:1.5rem;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.3rem;">
                                        <h5 style="font-size:1.35rem; font-weight:700; color:var(--text-primary); margin:0;"><?= e($log['action']) ?></h5>
                                        <small style="color:var(--text-muted); font-weight:600;"><?= date('H:i', strtotime($log['timestamp'])) ?></small>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:.8rem;">
                                        <i class="fas <?= $icon ?>" style="color:<?= $dot_color ?>; font-size:1.1rem; opacity:0.8;"></i>
                                        <span style="font-size:1.15rem; color:var(--text-secondary); text-transform:uppercase; font-weight:700; letter-spacing:.02em;"><?= e($log['module']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>
