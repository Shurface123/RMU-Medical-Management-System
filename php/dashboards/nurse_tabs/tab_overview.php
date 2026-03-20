<?php
// ============================================================
// NURSE DASHBOARD - OVERVIEW TAB (MODULE 1)
// ============================================================
if (!isset($conn)) exit; // Prevent direct access

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

$stats['pending_meds'] = qval($conn, "SELECT COUNT(*) FROM medication_administration WHERE status='Pending' AND scheduled_time <= NOW() + INTERVAL 2 HOUR");
$stats['pending_tasks'] = qval($conn, "SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=$nurse_pk AND status IN ('Pending','In Progress')");
$stats['overdue_tasks'] = qval($conn, "SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=$nurse_pk AND status IN ('Pending','In Progress') AND due_time < NOW()");
$stats['active_emergencies'] = qval($conn, "SELECT COUNT(*) FROM emergency_alerts WHERE status='Active'");

// Calculate vitals due (dummy approximation for now based on bed assignments in ward)
$stats['vitals_due'] = qval($conn, "
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

// ── MINI ANALYTICS FOR TODAY ────────────────────────────────
$tasks_total = qval($conn, "SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=$nurse_pk AND DATE(created_at)='$today'");
$tasks_done  = qval($conn, "SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=$nurse_pk AND DATE(completed_at)='$today' AND status='Completed'");
$task_rate   = $tasks_total > 0 ? round(($tasks_done / $tasks_total) * 100) : 0;

$meds_total = qval($conn, "SELECT COUNT(*) FROM medication_administration WHERE DATE(scheduled_time)='$today'");
$meds_done  = qval($conn, "SELECT COUNT(*) FROM medication_administration WHERE DATE(administered_at)='$today' AND status='Administered'");
$med_rate   = $meds_total > 0 ? round(($meds_done / $meds_total) * 100) : 0;
?>

<div class="tab-content active" id="overview">
    
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: 15px;">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h3 class="mb-1" style="font-weight: 600; color: white;">Good <?= (date('H')<12)?'Morning':((date('H')<17)?'Afternoon':'Evening') ?>, <?= e($nurse_row['full_name']) ?></h3>
                        <p class="mb-0 text-white-50" style="font-size: 1.1rem; opacity: 0.9;">
                            <i class="fas fa-map-marker-alt"></i> Ward Assigned: <strong><?= e($ward_assigned) ?></strong>
                            <span class="mx-2">|</span>
                            <i class="fas fa-clock"></i> Shift: <strong><?= $shift_active ? e($current_shift['shift_id']) . ' (Active)' : 'No Active Shift' ?></strong>
                        </p>
                    </div>
                    <div>
                        <div class="date-badge" style="background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 50px; font-weight: 500;">
                            <i class="far fa-calendar-alt"></i> <?= date('l, d M Y') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex gap-3 flex-wrap">
                <a href="?tab=patients" class="btn btn-primary" style="border-radius: 50px; padding: 10px 25px;"><i class="fas fa-heartbeat"></i> Record Vitals</a>
                <a href="?tab=medications" class="btn btn-outline-primary" style="border-radius: 50px; padding: 10px 25px; border-color: var(--primary-color); color: var(--primary-color);"><i class="fas fa-pills"></i> Administer Meds</a>
                <a href="?tab=notes" class="btn btn-outline-primary" style="border-radius: 50px; padding: 10px 25px; border-color: var(--primary-color); color: var(--primary-color);"><i class="fas fa-file-medical"></i> Add Note</a>
                <?php if($shift_active && !$handover_done): ?>
                    <a href="?tab=tasks" class="btn" style="background: #34495e; color: white; border-radius: 50px; padding: 10px 25px;"><i class="fas fa-clipboard-check"></i> Submit Handover</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-4">
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3" style="color: var(--primary-color); font-size: 2.5rem;"><i class="fas fa-procedures"></i></div>
                    <h5 class="text-muted" style="font-size: 0.9rem;">Patients in Ward</h5>
                    <h2 class="mb-0 font-weight-bold"><?= $stats['patients_assigned'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100 <?= $stats['vitals_due'] > 0 ? 'alert-critical' : '' ?>">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3" style="color: <?= $stats['vitals_due'] > 0 ? 'var(--accent-color)' : 'var(--primary-color)' ?>; font-size: 2.5rem;"><i class="fas fa-stethoscope"></i></div>
                    <h5 class="text-muted" style="font-size: 0.9rem;">Vitals Due</h5>
                    <h2 class="mb-0 font-weight-bold text-<?= $stats['vitals_due'] > 0 ? 'danger' : 'dark' ?>"><?= $stats['vitals_due'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3" style="color: var(--primary-color); font-size: 2.5rem;"><i class="fas fa-capsules"></i></div>
                    <h5 class="text-muted" style="font-size: 0.9rem;">Pending Meds</h5>
                    <h2 class="mb-0 font-weight-bold"><?= $stats['pending_meds'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3" style="color: <?= $stats['overdue_tasks'] > 0 ? 'var(--accent-color)' : 'var(--primary-color)' ?>; font-size: 2.5rem;"><i class="fas fa-tasks"></i></div>
                    <h5 class="text-muted" style="font-size: 0.9rem;">Active Tasks</h5>
                    <h2 class="mb-0 font-weight-bold">
                        <?= $stats['pending_tasks'] ?>
                        <?php if($stats['overdue_tasks'] > 0): ?>
                            <small class="text-danger" style="font-size: 0.8rem;">(<?= $stats['overdue_tasks'] ?> Overdue)</small>
                        <?php endif; ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100 <?= $stats['active_emergencies'] > 0 ? 'alert-critical' : '' ?>">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3" style="color: <?= $stats['active_emergencies'] > 0 ? 'var(--accent-color)' : '#95a5a6' ?>; font-size: 2.5rem;"><i class="fas fa-ambulance"></i></div>
                    <h5 class="text-muted" style="font-size: 0.9rem;">Active Alerts</h5>
                    <h2 class="mb-0 font-weight-bold text-<?= $stats['active_emergencies'] > 0 ? 'danger' : 'dark' ?>"><?= $stats['active_emergencies'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3" style="color: <?= $shift_active ? 'var(--primary-color)' : '#95a5a6' ?>; font-size: 2.5rem;"><i class="fas fa-exchange-alt"></i></div>
                    <h5 class="text-muted" style="font-size: 0.9rem;">Handover</h5>
                    <h4 class="mb-0 font-weight-bold text-<?= $handover_done ? 'success' : 'warning' ?>" style="margin-top: 10px;">
                        <?= $shift_active ? ($handover_done ? 'Submitted' : 'Pending') : 'N/A' ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Critical Alerts Panel -->
        <div class="col-lg-8">
            <div class="card h-100" style="border-radius: 12px; border: 1px solid #ffeeba;">
                <div class="card-header bg-white" style="border-bottom: 1px solid #ffeeba;">
                    <h5 class="mb-0 text-danger"><i class="fas fa-exclamation-circle me-2"></i> Critical Vitals & Alerts (24h)</h5>
                </div>
                <div class="card-body p-0">
                    <?php if(count($critical_vitals) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Patient</th>
                                        <th>Location</th>
                                        <th>Time Recorded</th>
                                        <th>Flag / Alert</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($critical_vitals as $cv): ?>
                                    <tr>
                                        <td class="ps-4 font-weight-bold"><?= e($cv['patient_name']) ?></td>
                                        <td><?= e($cv['ward']) ?> - Bed <?= e($cv['bed_number']) ?></td>
                                        <td><?= date('H:i (d M)', strtotime($cv['recorded_at'])) ?></td>
                                        <td><span class="text-danger fw-bold"><?= e($cv['flag_reason'] ?? 'Abnormal Reading') ?></span></td>
                                        <td>
                                            <?php if($cv['doctor_notified']): ?>
                                                <span class="badge bg-success" style="opacity:0.8"><i class="fas fa-check"></i> MD Notified</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-times"></i> Needs Review</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">
                            <div style="font-size: 3rem; color: #badbcc; margin-bottom: 15px;"><i class="fas fa-check-circle"></i></div>
                            <h5>No Critical Alerts</h5>
                            <p>All recorded vitals for assigned patients are within normal limits.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Mini Analytics -->
        <div class="col-lg-4">
            <!-- Mini Analytics -->
            <div class="card mb-4" style="border-radius: 12px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line text-muted me-2"></i> Today's Progress</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span style="font-size: 0.9rem; font-weight: 500;">Task Completion</span>
                            <span style="font-size: 0.9rem; font-weight: 600;"><?= $task_rate ?>%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar <?= $task_rate>80?'bg-success':($task_rate>50?'bg-warning':'bg-danger') ?>" role="progressbar" style="width: <?= $task_rate ?>%;"></div>
                        </div>
                        <small class="text-muted text-end d-block mt-1"><?= $tasks_done ?> of <?= $tasks_total ?> tasks</small>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span style="font-size: 0.9rem; font-weight: 500;">Medication Administration</span>
                            <span style="font-size: 0.9rem; font-weight: 600;"><?= $med_rate ?>%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar <?= $med_rate>90?'bg-success':($med_rate>60?'bg-primary':'bg-danger') ?>" style="background-color: var(--primary-color) !important; width: <?= $med_rate ?>%;"></div>
                        </div>
                        <small class="text-muted text-end d-block mt-1"><?= $meds_done ?> of <?= $meds_total ?> schedules</small>
                    </div>
                </div>
            </div>

            <!-- Feed -->
            <div class="card" style="border-radius: 12px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history text-muted me-2"></i> My Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                        <?php if(empty($recent_activity)): ?>
                            <li class="list-group-item text-center text-muted py-4">No recent activity</li>
                        <?php else: ?>
                            <?php foreach($recent_activity as $log): ?>
                                <?php 
                                    $icon = 'fa-check';
                                    $color = 'text-primary';
                                    if(strpos($log['module'],'vital') !== false) { $icon = 'fa-heartbeat'; $color = 'text-danger'; }
                                    elseif(strpos($log['module'],'medication') !== false) { $icon = 'fa-pills'; $color = 'text-info'; }
                                    elseif(strpos($log['module'],'task') !== false) { $icon = 'fa-tasks'; $color = 'text-success'; }
                                    elseif(strpos($log['module'],'note') !== false) { $icon = 'fa-file-medical'; $color = 'text-warning'; }
                                ?>
                                <li class="list-group-item py-3 px-4">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1" style="font-size: 0.9rem;"><i class="fas <?= $icon ?> <?= $color ?> me-2"></i> <?= e($log['action']) ?></h6>
                                        <small class="text-muted" style="font-size: 0.75rem; white-space: nowrap;"><?= date('H:i', strtotime($log['timestamp'])) ?></small>
                                    </div>
                                    <small class="text-muted d-block ms-4" style="font-size: 0.8rem;"><?= ucfirst(e($log['module'])) ?></small>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
