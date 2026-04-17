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
            <?php 
                $gender = strtolower($nurse_row['gender'] ?? '');
                $is_female = ($gender === 'female' || $gender === 'f');
                if(!empty($nurse_row['profile_photo']) && $nurse_row['profile_photo'] !== 'default-avatar.png'): 
            ?>
                <img src="/RMU-Medical-Management-System/<?= e($nurse_row['profile_photo']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%; border:3px solid rgba(255,255,255,0.2);">
            <?php else: ?>
                <?php $av_bg = $is_female ? 'var(--danger-gradient)' : 'var(--info-gradient)'; ?>
                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:<?= $av_bg ?>; border-radius:50%;">
                    <i class="fas fa-user-nurse" style="font-size:3.5rem; color:#fff;"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="staff-hero-info" style="flex:1;">
            <h2 style="font-size:2.4rem; font-weight:800; letter-spacing:-0.02em; margin-bottom:.5rem;">Good <?= $greeting ?>, <span style="color:#fff; text-shadow:0 0 15px rgba(255,255,255,0.4);"><?= e($nurse_row['full_name'] ?? 'Nurse') ?></span></h2>
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
        <!-- Quick Actions (Refactored) -->
        <div style="display:flex; gap:1rem; align-items:stretch; background:rgba(255,255,255,0.05); padding:1rem; border-radius:15px; border:1px solid rgba(255,255,255,0.15);">
            <div style="display:flex; flex-direction:column; justify-content:center; text-align:right; margin-right:1rem; padding-right:1rem; border-right:1px solid rgba(255,255,255,0.15);">
                <span style="display:block; text-transform:uppercase; font-size:.9rem; font-weight:800; color:#fff; letter-spacing:.05em; opacity:0.8;">Action Center</span>
                <span style="font-weight:600; color:var(--info); font-size:1.1rem;">Record Data</span>
            </div>
            
            <a href="?tab=patients" class="hero-action-btn" style="background:#fff; color:var(--primary); display:flex; align-items:center; gap:0.5rem; padding: 0.8rem 1.5rem; border-radius:12px; font-weight:800; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.2); transition:all 0.2s;">
                <i class="fas fa-heartbeat" style="font-size:1.4rem; color:var(--danger);"></i> <span>Vitals</span>
            </a>
            
            <a href="?tab=medications" class="hero-action-btn" style="background:var(--info); color:#fff; display:flex; align-items:center; gap:0.5rem; padding: 0.8rem 1.5rem; border-radius:12px; font-weight:800; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.2); transition:all 0.2s;">
                <i class="fas fa-pills" style="font-size:1.4rem;"></i> <span>Meds</span>
            </a>
            
             <a href="?tab=notes" class="hero-action-btn" style="background:rgba(255,255,255,0.1); color:#fff; display:flex; align-items:center; gap:0.5rem; padding: 0.8rem 1.5rem; border-radius:12px; font-weight:800; text-decoration:none; border: 1px solid rgba(255,255,255,0.2); transition:all 0.2s;">
                <i class="fas fa-pen-nib" style="font-size:1.4rem;"></i> <span>Note</span>
            </a>
        </div>
    </div>

    <!-- ── STATS STRIP (ADVANCED UI) ── -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2.5rem;">
        
        <!-- Patients Assigned -->
        <div class="ov-stat-card" style="border-left:4px solid var(--primary);" onclick="window.location.href='?tab=wards'">
            <div class="ov-stat-icon" style="background:var(--info-gradient);">
                <i class="fas fa-procedures"></i>
            </div>
            <div>
                <div class="ov-stat-num" style="color:var(--primary);"><?= (int)$stats['patients_assigned'] ?></div>
                <div class="ov-stat-label">Patients Assigned</div>
            </div>
        </div>
        
        <!-- Vitals Due -->
        <div class="ov-stat-card" style="border-left:4px solid <?= $stats['vitals_due'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;" onclick="window.location.href='?tab=patients'">
            <div class="ov-stat-icon" style="background:<?= $stats['vitals_due'] > 0 ? 'var(--danger-gradient)' : 'var(--success-gradient)' ?>;">
                <i class="fas fa-heartbeat"></i>
            </div>
            <div>
                <div class="ov-stat-num" style="color:<?= $stats['vitals_due'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;"><?= (int)$stats['vitals_due'] ?></div>
                <div class="ov-stat-label">Vitals Due</div>
            </div>
        </div>

        <!-- Pending Meds -->
        <div class="ov-stat-card" style="border-left:4px solid var(--warning);" onclick="window.location.href='?tab=medications'">
            <div class="ov-stat-icon" style="background:var(--role-gradient);">
                <i class="fas fa-pills"></i>
            </div>
            <div>
                <div class="ov-stat-num" style="color:var(--warning);"><?= (int)$stats['pending_meds'] ?></div>
                <div class="ov-stat-label">Pending Meds</div>
            </div>
        </div>

        <!-- Active Tasks -->
        <div class="ov-stat-card" style="border-left:4px solid <?= $stats['overdue_tasks'] > 0 ? 'var(--danger)' : 'var(--primary)' ?>;" onclick="window.location.href='?tab=tasks'">
            <div class="ov-stat-icon" style="background:<?= $stats['overdue_tasks'] > 0 ? 'var(--danger-gradient)' : 'var(--info-gradient)' ?>;">
                <i class="fas fa-tasks"></i>
            </div>
            <div>
                <div class="ov-stat-num" style="color:<?= $stats['overdue_tasks'] > 0 ? 'var(--danger)' : 'var(--primary)' ?>;"><?= (int)$stats['pending_tasks'] ?></div>
                <div class="ov-stat-label">Active Tasks <?= $stats['overdue_tasks'] > 0 ? '<span class="adm-badge adm-badge-danger" style="margin-left:.5rem;font-size:.9rem;">'.$stats['overdue_tasks'].' overdue</span>' : '' ?></div>
            </div>
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

            <!-- Advanced Diagram: Clinical Performance Chart -->
            <div class="adm-card shadow-sm">
                <div class="adm-card-header">
                    <h3><i class="fas fa-chart-pie text-success"></i> Clinical Performance Diagram</h3>
                </div>
                <div class="adm-card-body" style="padding:2.2rem; display:flex; align-items:center; gap:2rem;">
                    
                    <div style="width: 180px; height: 180px; flex-shrink:0; position:relative;">
                        <canvas id="performanceChart"></canvas>
                        <div style="position:absolute; top:0; left:0; right:0; bottom:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none;">
                            <span style="font-size:2.2rem; font-weight:800; color:var(--text-primary); line-height:1;"><?= $task_rate ?>%</span>
                            <span style="font-size:1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Tasks</span>
                        </div>
                    </div>
                    
                    <div style="flex:1;">
                        <div class="ov-health-row">
                            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-tasks text-primary" style="margin-right:.5rem;"></i>Tasks Handled</span>
                            <strong style="font-size:1.35rem;color:var(--text-primary);"><?= $tasks_done ?> / <?= $tasks_total ?></strong>
                        </div>
                        <div class="ov-health-row">
                            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-pills text-warning" style="margin-right:.5rem;"></i>Meds Delivered</span>
                            <strong style="font-size:1.35rem;color:var(--text-primary);"><?= $meds_done ?> / <?= $meds_total ?></strong>
                        </div>
                        <div class="ov-health-row">
                            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-heartbeat text-danger" style="margin-right:.5rem;"></i>Vitals Accuracy</span>
                            <strong style="font-size:1.35rem;color:var(--success);">100%</strong>
                        </div>
                        <div class="ov-health-row">
                            <span style="color:var(--text-muted);font-size:1.2rem;"><i class="fas fa-exchange-alt text-success" style="margin-right:.5rem;"></i>Handover Status</span>
                            <strong style="font-size:1.35rem;color:<?= $handover_done ? 'var(--success)' : 'var(--warning)' ?>;"><?= $shift_active ? ($handover_done ? 'Submitted' : 'Pending') : 'N/A' ?></strong>
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

    <script>
        setInterval(() => {
            const clock = document.getElementById('liveClock');
            if(clock) {
                const now = new Date();
                clock.textContent = now.toLocaleTimeString('en-GB'); // 24-hour format HH:MM:SS
            }
        }, 1000);

        // Chart.js Diagram Initialization
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('performanceChart');
            if(ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Tasks Completed', 'Pending Tasks', 'Meds Delivered', 'Pending Meds'],
                        datasets: [{
                            data: [<?= $tasks_done ?>, <?= $tasks_total - $tasks_done ?>, <?= $meds_done ?>, <?= $meds_total - $meds_done ?>],
                            backgroundColor: [
                                '#27ae60', // Task Completed (Success)
                                '#e74c3c', // Task Pending (Danger)
                                '#f39c12', // Meds Delivered (Warning/Orange)
                                '#34495e'  // Pending Meds (Dark)
                            ],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                bodyFont: { size: 14, family: "'Poppins', sans-serif" },
                                padding: 12,
                                cornerRadius: 8,
                                displayColors: true
                            }
                        }
                    }
                });
            }
        });
    </script>
</div>
