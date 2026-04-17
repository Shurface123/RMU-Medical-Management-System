<?php
// ============================================================
// NURSE DASHBOARD - IV & FLUID MANAGEMENT (MODULE 8)
// ============================================================
if (!isset($conn)) exit;

// â”€â”€ GET SHIFT & WARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'Unknown Ward';

// â”€â”€ FETCH PATIENTS IN WARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$patients_in_ward = [];
$q_pw = mysqli_query($conn, "
    SELECT p.id, p.patient_id, u.name, b.bed_number 
    FROM patients p 
    JOIN users u ON p.user_id = u.id 
    JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status='Occupied'
    JOIN beds b ON ba.bed_id = b.id
    WHERE b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."'
    ORDER BY u.name ASC
");
if ($q_pw) {
    while($r = mysqli_fetch_assoc($q_pw)) $patients_in_ward[] = $r;
}

// â”€â”€ FETCH ACTIVE IV INFUSIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$iv_records = [];
$q_iv = mysqli_query($conn, "
    SELECT iv.*, p.patient_id as pid, u.name AS patient_name, b.bed_number 
    FROM iv_fluid_records iv
    JOIN patients p ON iv.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status='Occupied'
    LEFT JOIN beds b ON ba.bed_id = b.id
    WHERE (b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."' OR iv.nurse_id = $nurse_pk)
      AND iv.status IN ('Ordered', 'Running', 'Paused')
    ORDER BY iv.start_time DESC
");
if ($q_iv) {
    while($r = mysqli_fetch_assoc($q_iv)) $iv_records[] = $r;
}

// â”€â”€ FETCH TODAY'S I&O CHARTS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$io_records = [];
$q_io = mysqli_query($conn, "
    SELECT fb.*, p.patient_id as pid, u.name AS patient_name, b.bed_number 
    FROM fluid_balance fb
    JOIN patients p ON fb.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status='Occupied'
    LEFT JOIN beds b ON ba.bed_id = b.id
    WHERE fb.record_date = '$today' AND (b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."' OR fb.nurse_id = $nurse_pk)
    ORDER BY p.id ASC
");
if ($q_io) {
    while($r = mysqli_fetch_assoc($q_io)) {
        // Parse JSON strings
        $r['in'] = json_decode($r['intake_sources'] ?? '{"oral":0, "iv":0, "ng_tube":0}', true);
        $r['out'] = json_decode($r['output_sources'] ?? '{"urine":0, "drain":0, "emesis":0}', true);
        $io_records[] = $r;
    }
}
?>

<div class="tab-content active" id="fluids">

    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--info); margin-bottom:.3rem;"><i class="fas fa-tint pulse-fade"></i> IV & Fluid Management</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Real-time monitoring of active infusions and daily fluid balance dynamics.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center;">
             <div style="background:rgba(52,152,219,0.05); border:1px solid rgba(52,152,219,0.1); padding:.8rem 1.5rem; border-radius:12px; display:flex; align-items:center; gap:1rem;">
                <span style="width:10px; height:10px; border-radius:50%; background:var(--info); display:inline-block;"></span>
                <div style="font-size:1.4rem; font-weight:800; color:var(--text-primary);">Monitoring <small style="font-weight:700; color:var(--info);">ACTIVE</small></div>
            </div>
            <button class="btn btn-primary" onclick="document.getElementById('ioForm').reset(); document.getElementById('ioModal').style.display='flex';" style="border-radius:12px; font-weight:700;"><span class="btn-text">
                <i class="fas fa-plus"></i> Record I&O
            </span></button>
            <button class="btn btn-ghost" onclick="document.getElementById('ivForm').reset(); document.getElementById('ivModal').style.display='flex';" style="border-radius:12px; font-weight:700; border-color:var(--info); color:var(--info);"><span class="btn-text">
                <i class="fas fa-syringe"></i> Start IV
            </span></button>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div style="margin-bottom:2.5rem; border-bottom:2px solid var(--border); display:flex; gap:3rem;">
        <button class="btn btn-primary tab-link active" onclick="switchFluidSubTab('iv')" id="btn-iv-tab" style="padding:1rem 0; font-weight:800; font-size:1.3rem; color:var(--primary); border-bottom:3px solid var(--primary); background:none; border-top:none; border-left:none; border-right:none; cursor:pointer; display:flex; align-items:center; gap:.8rem;"><span class="btn-text">
            <i class="fas fa-syringe"></i> ACTIVE INFUSIONS
        </span></button>
        <button class="btn btn-primary tab-link" onclick="switchFluidSubTab('io')" id="btn-io-tab" style="padding:1rem 0; font-weight:700; font-size:1.3rem; color:var(--text-muted); border-bottom:3px solid transparent; background:none; border-top:none; border-left:none; border-right:none; cursor:pointer; display:flex; align-items:center; gap:.8rem;"><span class="btn-text">
            <i class="fas fa-balance-scale"></i> CLINICAL I&O CHARTS
        </span></button>
    </div>

    <div id="fluid-sub-content">
        
        <!-- ACTIVE IV INFUSIONS â€” Premium Fluid Cards -->
        <div id="iv-content">
            <?php if(empty($iv_records)): ?>
            <div class="adm-card" style="text-align:center;padding:6rem;">
                <i class="fas fa-tint-slash" style="font-size:4rem;opacity:.2;display:block;margin-bottom:1.5rem;color:var(--info);"></i>
                <h3 style="color:var(--text-muted);font-weight:700;">No Active Infusions</h3>
                <p style="color:var(--text-muted);font-size:1.3rem;margin-top:.5rem;">All clear â€” no IV infusions currently running in <?= e($ward_assigned) ?>.</p>
                <button class="btn btn-primary" onclick="document.getElementById('ivForm').reset(); document.getElementById('ivModal').style.display='flex';" style="margin-top:2rem;border-radius:12px;">
                    <span class="btn-text"><i class="fas fa-plus"></i> Start New IV</span>
                </button>
            </div>
            <?php else: foreach($iv_records as $iv):
                $pct = ($iv['volume_ordered'] > 0) ? min(100, ($iv['volume_infused'] / $iv['volume_ordered'] * 100)) : 0;
                $status = $iv['status'];
                $inv_cls = $status === 'Running' ? 'inv-paid' : ($status === 'Paused' ? 'inv-pending' : 'inv-partially');
                $status_color = match($status) {
                    'Running' => 'var(--success)',
                    'Paused'  => 'var(--info)',
                    'Ordered' => 'var(--warning)',
                    default   => 'var(--text-muted)'
                };
                $bar_color = match($status) {
                    'Running' => 'var(--success-gradient)',
                    'Paused'  => 'linear-gradient(90deg,#2F80ED,#56CCF2)',
                    default   => 'linear-gradient(90deg,var(--warning),var(--role-accent))'
                };
            ?>
            <div class="inv-card <?= $inv_cls ?>" style="margin-bottom:1.2rem;">
                <div class="inv-card-header" style="gap:2rem;flex-wrap:wrap;">
                    <!-- Status Pulse Indicator -->
                    <div style="display:flex;flex-direction:column;align-items:center;gap:.3rem;flex-shrink:0;">
                        <div style="width:14px;height:14px;border-radius:50%;background:<?= $status_color ?>;<?= $status==='Running' ? 'animation:pulse-red 1.5s infinite;box-shadow:0 0 0 4px color-mix(in srgb,var(--success) 20%,transparent);' : '' ?>"></div>
                        <span style="font-size:.85rem;color:<?= $status_color ?>;font-weight:700;text-transform:uppercase;white-space:nowrap;"><?= e($status) ?></span>
                    </div>
                    <!-- Patient ID Block -->
                    <div style="flex-shrink:0;">
                        <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);"><?= e($iv['patient_name']) ?></div>
                        <div style="font-size:1.1rem;color:var(--info);font-weight:700;text-transform:uppercase;">Bed <?= e($iv['bed_number']) ?> <span style="color:var(--text-muted);font-weight:500;font-family:monospace;font-size:1rem;">| <?= e($iv['pid']) ?></span></div>
                    </div>
                    <!-- Fluid Type + Rate Chips -->
                    <div style="flex:1;padding:0 1rem;">
                        <div style="font-size:1.4rem;font-weight:800;color:var(--text-primary);margin-bottom:.6rem;"><?= e($iv['fluid_type']) ?></div>
                        <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                            <span class="vital-chip"><i class="fas fa-tint" style="color:var(--info);"></i> <?= number_format($iv['infusion_rate']) ?> ml/hr</span>
                            <?php if(!empty($iv['site'])): ?><span class="vital-chip"><i class="fas fa-map-pin" style="color:var(--warning);"></i> <?= e($iv['site']) ?></span><?php endif; ?>
                            <span class="vital-chip"><i class="fas fa-clock" style="color:var(--text-muted);"></i> <?= date('H:i', strtotime($iv['start_time'])) ?></span>
                        </div>
                    </div>
                    <!-- Volume Progress -->
                    <div style="min-width:180px;flex-shrink:0;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
                            <span style="font-size:1.1rem;font-weight:700;color:var(--text-secondary);"><?= number_format($iv['volume_infused']) ?> <small style="opacity:.6;">ml in</small></span>
                            <span style="font-size:1.1rem;color:var(--text-muted);"><?= number_format($iv['volume_ordered']) ?> ml</span>
                        </div>
                        <div class="pay-progress-bar" style="width:100%;height:10px;">
                            <div class="pay-progress-fill" style="width:<?= $pct ?>%;background:<?= $bar_color ?>;<?= $status==='Running' ? 'animation:width-pulse 2s ease-in-out infinite;' : '' ?>"></div>
                        </div>
                        <div style="font-size:1rem;color:var(--text-muted);margin-top:.4rem;text-align:right;"><?= round($pct) ?>% infused</div>
                    </div>
                    <!-- Management Actions -->
                    <div style="flex-shrink:0;display:flex;flex-direction:column;gap:.6rem;">
                        <?php if($status !== 'Running'): ?>
                        <button class="btn btn-primary btn-sm" onclick="updateIvStatus(<?= $iv['id'] ?>, 'Running')">
                            <span class="btn-text"><i class="fas fa-play"></i> Resume</span>
                        </button>
                        <?php else: ?>
                        <button class="btn btn-ghost btn-sm" onclick="updateIvStatus(<?= $iv['id'] ?>, 'Paused')" style="border-color:var(--warning);color:var(--warning);">
                            <span class="btn-text"><i class="fas fa-pause"></i> Pause</span>
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-ghost btn-sm" onclick="updateIvStatus(<?= $iv['id'] ?>, 'Completed')" style="border-color:var(--success);color:var(--success);">
                            <span class="btn-text"><i class="fas fa-check-double"></i> Complete</span>
                        </button>
                        <button class="btn btn-ghost btn-sm" onclick="updateIvStatus(<?= $iv['id'] ?>, 'Stopped')" style="border-color:var(--danger);color:var(--danger);">
                            <span class="btn-text"><i class="fas fa-stop"></i> Stop</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>


        <!-- DAILY I&O CHARTS -->
        <div id="io-content" style="display:none;">
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap:2.5rem;">
                <?php if(empty($io_records)): ?>
                    <div class="adm-card shadow-sm" style="grid-column: 1 / -1; height:350px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:1.5rem; text-align:center;">
                        <div style="width:70px; height:70px; border-radius:50%; background:rgba(52,152,219,0.1); display:flex; align-items:center; justify-content:center; font-size:3rem; color:var(--info);">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <div>
                            <h4 style="font-weight:700; color:var(--text-primary); margin:0;">No I&O Charts Active</h4>
                            <p style="font-size:1.2rem; color:var(--text-muted);">Record fluid intake and output to begin tracking daily balance.</p>
                        </div>
                    </div>
                <?php else: foreach($io_records as $io): 
                    $net = floatval($io['net_balance']);
                    $net_color = 'var(--success)';
                    if($net < -500) $net_color = 'var(--warning)';
                    if($net < -1000) $net_color = 'var(--danger)';
                    if($net > 1000) $net_color = 'var(--info)';
                ?>
                    <div class="adm-card shadow-sm" style="border:none; overflow:hidden;">
                        <div class="adm-card-header" style="background:var(--surface-2); border-bottom:1.5px solid var(--border); padding:1.5rem 2rem;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; width:100%;">
                                <div>
                                    <h5 style="font-weight:800; font-size:1.4rem; color:var(--text-primary); margin:0;"><?= e($io['patient_name']) ?></h5>
                                    <div style="font-size:1.1rem; color:var(--info); font-weight:700; text-transform:uppercase; margin-top:.3rem;">BED <?= e($io['bed_number']) ?> <span style="color:var(--text-muted); font-weight:500;">| <?= e($io['pid']) ?></span></div>
                                </div>
                                <div style="text-align:right;">
                                    <small style="font-size:1rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em;">Net Balance</small>
                                    <div style="font-size:1.5rem; font-weight:900; color:<?= $net_color ?>;">
                                        <?= $net > 0 ? '+' : '' ?><?= number_format($net,0) ?> <small style="font-size:1rem;">ml</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="adm-card-body" style="padding:2rem;">
                            
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.5px; background:var(--border); border-radius:12px; overflow:hidden; border:1px solid var(--border); margin-bottom:2rem;">
                                <!-- INTAKE SECTION -->
                                <div style="background:#fff; padding:1.5rem;">
                                    <div style="display:flex; align-items:center; gap:.8rem; margin-bottom:1.5rem;">
                                        <div style="width:32px; height:32px; border-radius:8px; background:rgba(52,152,219,0.1); display:flex; align-items:center; justify-content:center; color:var(--info);">
                                            <i class="fas fa-arrow-down" style="font-size:.9rem;"></i>
                                        </div>
                                        <span style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); text-transform:uppercase;">Intake</span>
                                        <span style="margin-left:auto; font-weight:900; font-size:1.4rem; color:var(--info);"><?= number_format($io['total_intake'],0) ?> <small style="font-size:.8rem; opacity:0.6;">ml</small></span>
                                    </div>
                                    <div style="display:flex; flex-direction:column; gap:.8rem;">
                                        <div style="display:flex; justify-content:space-between; font-size:1.1rem; font-weight:600; color:var(--text-muted);">
                                            <span>Oral / Enteral</span>
                                            <span><?= number_format($io['in']['oral'] ?? 0) ?> ml</span>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; font-size:1.1rem; font-weight:600; color:var(--text-muted);">
                                            <span>IV / Infusions</span>
                                            <span><?= number_format($io['in']['iv'] ?? 0) ?> ml</span>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; font-size:1.1rem; font-weight:600; color:var(--text-muted);">
                                            <span>Other (NG/Bolus)</span>
                                            <span><?= number_format($io['in']['ng_tube'] ?? 0) ?> ml</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- OUTPUT SECTION -->
                                <div style="background:#fff; padding:1.5rem;">
                                    <div style="display:flex; align-items:center; gap:.8rem; margin-bottom:1.5rem;">
                                        <div style="width:32px; height:32px; border-radius:8px; background:rgba(231,76,60,0.1); display:flex; align-items:center; justify-content:center; color:var(--danger);">
                                            <i class="fas fa-arrow-up" style="font-size:.9rem;"></i>
                                        </div>
                                        <span style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); text-transform:uppercase;">Output</span>
                                        <span style="margin-left:auto; font-weight:900; font-size:1.4rem; color:var(--danger);"><?= number_format($io['total_output'],0) ?> <small style="font-size:.8rem; opacity:0.6;">ml</small></span>
                                    </div>
                                    <div style="display:flex; flex-direction:column; gap:.8rem;">
                                        <div style="display:flex; justify-content:space-between; font-size:1.1rem; font-weight:600; color:var(--text-muted);">
                                            <span>Urine Output</span>
                                            <span><?= number_format($io['out']['urine'] ?? 0) ?> ml</span>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; font-size:1.1rem; font-weight:600; color:var(--text-muted);">
                                            <span>Surgical Drains</span>
                                            <span><?= number_format($io['out']['drain'] ?? 0) ?> ml</span>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; font-size:1.1rem; font-weight:600; color:var(--text-muted);">
                                            <span>Other / Emesis</span>
                                            <span><?= number_format($io['out']['emesis'] ?? 0) ?> ml</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; gap:1rem;">
                                <button class="btn-icon btn btn-ghost btn-sm" onclick="showIoQuickAdd(<?= $io['patient_id'] ?>, '<?= e($io['patient_name']) ?>')" style="flex:1; border-radius:8px; font-weight:700;"><span class="btn-text">
                                    <i class="fas fa-plus"></i> View Full Chart
                                </span></button>
                                <button class="btn btn-ghost btn-sm" onclick="showIoFormWithPatient(<?= $io['patient_id'] ?>)" style="flex:1; border-radius:8px; font-weight:700; border-color:var(--primary); color:var(--primary);"><span class="btn-text">
                                    <i class="fas fa-edit"></i> Update Balance
                                </span></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: START IV INFUSION                   -->
<!-- ========================================== -->
<div class="modal-bg" id="ivModal">
    <div class="modal-box" style="max-width:550px;">
        <div class="modal-header" style="background:var(--info);">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-syringe"></i> Initiate IV Infusion</h3>
            <button class="btn btn-primary modal-close" onclick="document.getElementById('ivModal').style.display='none'" type="button" style="color:#fff; opacity:0.8;"><span class="btn-text">Ã—</span></button>
        </div>
        <div style="padding:2.5rem;">
            <form id="ivForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="start_iv">
                
                <div class="form-group" style="margin-bottom:1.8rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Select Patient</label>
                    <select class="form-control" name="patient_id" required style="padding:.8rem; font-weight:600; font-size:1.3rem;">
                        <option value="">-- Clinical Subject --</option>
                        <?php foreach($patients_in_ward as $p): ?>
                            <option value="<?= $p['id'] ?>">Bed <?= e($p['bed_number']) ?>: <?= e($p['name']) ?> (<?= e($p['patient_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.8rem;">
                    <div class="form-group">
                        <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Fluid Type</label>
                        <input type="text" class="form-control" name="fluid_type" placeholder="e.g. Normal Saline 0.9%" required style="padding:.8rem; font-weight:600;">
                    </div>
                    <div class="form-group">
                        <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Access Site</label>
                        <input type="text" class="form-control" name="site" placeholder="e.g. Left Forearm" required style="padding:.8rem; font-weight:600;">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.8rem;">
                    <div class="form-group">
                        <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Ordered Volume (ml)</label>
                        <input type="number" step="1" class="form-control" name="volume_ordered" placeholder="e.g. 1000" required style="padding:.8rem; font-weight:600; font-size:1.3rem; color:var(--primary);">
                    </div>
                    <div class="form-group">
                        <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Flow Rate (ml/h)</label>
                        <input type="number" step="1" class="form-control" name="infusion_rate" placeholder="e.g. 125" required style="padding:.8rem; font-weight:600; font-size:1.3rem; color:var(--primary);">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:2.5rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Immediate Status</label>
                    <select class="form-control" name="initial_status" style="padding:.8rem; font-weight:700; color:var(--info);">
                        <option value="Ordered">Physician Order Only (Verification Pending)</option>
                        <option value="Running" selected>Initiate Infusion Immediately</option>
                    </select>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('ivModal').style.display='none'" style="font-weight:700;"><span class="btn-text">Cancel</span></button>
                    <button type="submit" class="btn btn-success btn-icon btn btn-info" style="padding:.8rem 3rem; font-weight:800; border-radius:12px;"><span class="btn-text">
                        <i class="fas fa-play"></i> CONFIRM & START
                    </span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: RECORD INTAKE & OUTPUT              -->
<!-- ========================================== -->
<div class="modal-bg" id="ioModal">
    <div class="modal-box" style="max-width:650px;">
        <div class="modal-header" style="background:var(--primary);">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-balance-scale"></i> Update Fluid Balance</h3>
            <button class="btn btn-primary modal-close" onclick="document.getElementById('ioModal').style.display='none'" type="button" style="color:#fff; opacity:0.8;"><span class="btn-text">Ã—</span></button>
        </div>
        <div style="padding:2.5rem;">
            <form id="ioForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_io">
                
                <div class="form-group" style="margin-bottom:2rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Select Patient</label>
                    <select class="form-control" name="patient_id" id="ioPatientSelect" required style="padding:.8rem; font-weight:800; font-size:1.4rem; border:1.5px solid var(--primary);">
                        <option value="">-- Clinical Subject --</option>
                        <?php foreach($patients_in_ward as $p): ?>
                            <option value="<?= $p['id'] ?>">Bed <?= e($p['bed_number']) ?>: <?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin-top:.8rem; padding:.8rem; background:rgba(var(--primary-rgb),0.05); border-radius:8px; font-size:1.1rem; color:var(--primary); font-weight:600;">
                        <i class="fas fa-info-circle"></i> This entry will be added to the patient's cumulative daily total.
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; margin-bottom:2.5rem;">
                    <!-- Intake Column -->
                    <div style="background:rgba(52,152,219,0.03); border:1.5px solid rgba(52,152,219,0.1); border-radius:12px; padding:1.5rem;">
                        <h4 style="font-size:1.2rem; font-weight:800; color:var(--info); margin-bottom:1.5rem; text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:.8rem;">
                            <i class="fas fa-plus-circle"></i> Intake (ml)
                        </h4>
                        <div style="display:flex; flex-direction:column; gap:1.5rem;">
                            <div class="form-group">
                                <label style="display:block; font-size:1rem; font-weight:700; color:var(--text-muted); margin-bottom:.5rem;">Oral / Bolus</label>
                                <input type="number" step="1" name="in_oral" class="form-control" value="0" style="font-weight:800; color:var(--info); font-size:1.4rem;">
                            </div>
                            <div class="form-group">
                                <label style="display:block; font-size:1rem; font-weight:700; color:var(--text-muted); margin-bottom:.5rem;">IV Fluids</label>
                                <input type="number" step="1" name="in_iv" class="form-control" value="0" style="font-weight:800; color:var(--info); font-size:1.4rem;">
                            </div>
                            <div class="form-group">
                                <label style="display:block; font-size:1rem; font-weight:700; color:var(--text-muted); margin-bottom:.5rem;">NG / Other</label>
                                <input type="number" step="1" name="in_ng" class="form-control" value="0" style="font-weight:800; color:var(--info); font-size:1.4rem;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Output Column -->
                    <div style="background:rgba(231,76,60,0.03); border:1.5px solid rgba(231,76,60,0.1); border-radius:12px; padding:1.5rem;">
                        <h4 style="font-size:1.2rem; font-weight:800; color:var(--danger); margin-bottom:1.5rem; text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:.8rem;">
                            <i class="fas fa-minus-circle"></i> Output (ml)
                        </h4>
                        <div style="display:flex; flex-direction:column; gap:1.5rem;">
                            <div class="form-group">
                                <label style="display:block; font-size:1rem; font-weight:700; color:var(--text-muted); margin-bottom:.5rem;">Urine</label>
                                <input type="number" step="1" name="out_urine" class="form-control" value="0" style="font-weight:800; color:var(--danger); font-size:1.4rem;">
                            </div>
                            <div class="form-group">
                                <label style="display:block; font-size:1rem; font-weight:700; color:var(--text-muted); margin-bottom:.5rem;">Surgical Drain</label>
                                <input type="number" step="1" name="out_drain" class="form-control" value="0" style="font-weight:800; color:var(--danger); font-size:1.4rem;">
                            </div>
                            <div class="form-group">
                                <label style="display:block; font-size:1rem; font-weight:700; color:var(--text-muted); margin-bottom:.5rem;">Emesis / Other</label>
                                <input type="number" step="1" name="out_emesis" class="form-control" value="0" style="font-weight:800; color:var(--danger); font-size:1.4rem;">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('ioModal').style.display='none'" style="font-weight:700;"><span class="btn-text">Cancel</span></button>
                    <button type="submit" class="btn btn-primary" id="btnSaveIo" style="padding:.8rem 3.5rem; font-weight:900; border-radius:12px; font-size:1.2rem;"><span class="btn-text">
                        <i class="fas fa-save" style="margin-right:.6rem;"></i> UPDATE LOG
                    </span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function switchFluidSubTab(tab) {
    $('.tab-link').removeClass('active').css({'color': 'var(--text-muted)', 'border-bottom-color': 'transparent', 'font-weight': '700'});
    $('#btn-'+tab+'-tab').addClass('active').css({'color': 'var(--role-accent)', 'border-bottom-color': 'var(--role-accent)', 'font-weight': '800'});
    
    if(tab === 'iv') {
        $('#iv-content').show();
        $('#io-content').hide();
    } else {
        $('#iv-content').hide();
        $('#io-content').show();
    }
}

function showIoFormWithPatient(patientId) {
    $('#ioPatientSelect').val(patientId);
    $('#ioForm')[0].reset();
    $('#ioPatientSelect').val(patientId);
    document.getElementById('ioModal').style.display = 'flex';
}

function updateIvStatus(iv_id, status) {
    const statusLower = status.toLowerCase();
    const actionText = status === 'Running' ? 'start / resume' : (status === 'Paused' ? 'pause' : (status === 'Completed' ? 'complete' : 'stop early'));
    
    Swal.fire({
        title: `Confirm IV Update?`,
        text: `You are about to ${actionText} this infusion record.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: status === 'Running' ? 'var(--success)' : (status === 'Paused' ? 'var(--warning)' : 'var(--info)'),
        confirmButtonText: 'Yes, Update Status'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../nurse/process_fluids.php', {
                action: 'update_iv_status',
                iv_id: iv_id,
                status: status,
                _csrf: '<?= generateCsrfToken() ?>'
            }, function(res) {
                if(res.success) {
                    Swal.fire({ icon: 'success', title: 'Infusion Updated', timer: 1000, showConfirmButton: false });
                    setTimeout(() => window.location.href = '?tab=fluids', 1000);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            }, 'json');
        }
    });
}

function switchFluidSubTab(tab) {
    $('.tab-link').removeClass('active').css({'color': 'var(--text-muted)', 'border-bottom-color': 'transparent', 'font-weight': '700'});
    $(`#btn-${tab}-tab`).addClass('active').css({'color': 'var(--primary)', 'border-bottom-color': 'var(--primary)', 'font-weight': '800'});
    if(tab === 'iv') { $('#iv-content').show(); $('#io-content').hide(); }
    else { $('#iv-content').hide(); $('#io-content').show(); }
}

$(document).ready(function() {
    $('#ivForm, #ioForm').on('submit', function(e) {
        e.preventDefault();
        const formId = $(this).attr('id');
        const isIo = formId === 'ioForm';
        const btn = isIo ? $('#btnSaveIo') : $(this).find('button[type=submit]');
        const origHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: '../nurse/process_fluids.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: isIo ? 'Balance Recorded' : 'Infusion Transmitted',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.href = '?tab=fluids', 1500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Action Failed', text: res.message });
                    btn.prop('disabled', false).html(origHtml);
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'System Error', text: 'Communication failure with clinical server.' });
                btn.prop('disabled', false).html(origHtml);
            }
        });
    });
});
</script>
