<?php
// ============================================================
// TAB PROFILE — PART 2: Sections D through L + JavaScript
// Included by tab_profile.php
// ============================================================

$csrf_token = $_SESSION['csrf_token'] ?? '';
$thirty_days_date = date('Y-m-d', strtotime('+30 days'));
$sixty_days_date  = date('Y-m-d', strtotime('+60 days'));
?>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION D: CREDENTIALS & AWARDS -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-d" class="profile-section" style="display:none;">
    <!-- Qualifications -->
    <div class="info-card" style="margin-bottom:2rem; border-top: 4px solid var(--primary);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-weight:800;"><i class="fas fa-graduation-cap" style="color:var(--primary); margin-right:.8rem;"></i> Academic Qualifications</h3>
            <button class="adm-btn adm-btn-primary" onclick="$('#addQualModal').show()"><i class="fas fa-plus-circle"></i> Add Qualification</button>
        </div>
        <?php if(empty($qualifications)): ?>
            <div style="text-align:center; padding:3rem; color:var(--text-muted); background:var(--surface-2); border-radius:15px; border:2px dashed var(--border);">
                <i class="fas fa-user-graduate" style="font-size:3rem; margin-bottom:1rem; opacity:0.3;"></i>
                <p style="font-size:1.1rem; font-weight:600;">No academic records found in registry.</p>
            </div>
        <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table display">
                <thead><tr><th>Qualified Degree/Certificate</th><th>Conferring Institution</th><th>Cycle Year</th><th>Validation</th><th>Control</th></tr></thead>
                <tbody>
                <?php foreach($qualifications as $q): ?>
                <tr>
                    <td><strong style="font-size:1.1rem;"><?= e($q['degree_name']) ?></strong></td>
                    <td><div style="font-weight:700; color:var(--text-secondary);"><?= e($q['institution_name']) ?></div></td>
                    <td><span class="adm-badge" style="background:var(--surface-2); font-weight:800;"><?= e($q['year_awarded'] ?? '—') ?></span></td>
                    <td><?php if($q['certificate_file_path']): ?>
                        <a href="/RMU-Medical-Management-System/php/api/download_doc.php?file=<?= urlencode($q['certificate_file_path']) ?>" class="adm-btn adm-btn-sm" style="background:var(--surface-2); color:var(--primary);" target="_blank"><i class="fas fa-file-pdf"></i> Download</a>
                    <?php else: echo '<span style="color:var(--text-muted); font-size:0.85rem;">No digital copy</span>'; endif; ?></td>
                    <td><button class="adm-btn adm-btn-danger adm-btn-sm" onclick="deleteQualification(<?= $q['id'] ?>)"><i class="fas fa-trash-alt"></i></button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Certifications -->
    <div class="info-card" style="border-top: 4px solid var(--role-accent);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-weight:800;"><i class="fas fa-award" style="color:var(--role-accent); margin-right:.8rem;"></i> Specialist Certifications</h3>
            <button class="adm-btn adm-btn-primary" onclick="$('#addCertModal').show()"><i class="fas fa-plus-circle"></i> Register Certification</button>
        </div>
        <?php if(empty($certifications)): ?>
            <div style="text-align:center; padding:3rem; color:var(--text-muted); background:var(--surface-2); border-radius:15px; border:2px dashed var(--border);">
                <i class="fas fa-stamp" style="font-size:3rem; margin-bottom:1rem; opacity:0.3;"></i>
                <p style="font-size:1.1rem; font-weight:600;">No specialist certifications cataloged.</p>
            </div>
        <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table display">
                <thead><tr><th>Certification Identifier</th><th>Issuing Authority</th><th>Acquisition</th><th>Expiry Control</th><th>Evidence</th><th>Control</th></tr></thead>
                <tbody>
                <?php foreach($certifications as $c):
                    $exp_style = '';
                    if($c['expiry_date'] && $c['expiry_date'] <= $sixty_days_date) $exp_style = 'background:rgba(230,126,34,0.1); color:#e67e22; border:1px solid #e67e22; font-weight:800;';
                    if($c['expiry_date'] && $c['expiry_date'] < $today) $exp_style = 'background:rgba(231,76,60,0.1); color:var(--danger); border:1px solid var(--danger); font-weight:800;';
                ?>
                <tr>
                    <td><strong style="font-size:1.1rem;"><?= e($c['certification_name']) ?></strong></td>
                    <td><div style="font-weight:700; color:var(--text-secondary);"><?= e($c['issuing_organization'] ?? '—') ?></div></td>
                    <td><span style="font-weight:600; color:var(--text-muted);"><?= $c['issue_date'] ? date('d M Y', strtotime($c['issue_date'])) : '—' ?></span></td>
                    <td>
                        <?php if($c['expiry_date']): ?>
                            <span class="adm-badge" style="<?= $exp_style ?: 'background:var(--surface-2); color:var(--text-primary); font-weight:600;' ?>">
                                <?= date('d M Y', strtotime($c['expiry_date'])) ?>
                                <?php if($c['expiry_date'] < $today): ?> (EXPIRED)<?php endif; ?>
                            </span>
                        <?php else: echo '<span class="adm-badge" style="background:var(--surface-2); color:var(--text-muted);">Perpetual</span>'; endif; ?>
                    </td>
                    <td><?php if($c['certificate_file_path']): ?>
                        <a href="/RMU-Medical-Management-System/php/api/download_doc.php?file=<?= urlencode($c['certificate_file_path']) ?>" class="adm-btn adm-btn-sm" style="background:var(--surface-2); color:var(--primary);" target="_blank"><i class="fas fa-cloud-download-alt"></i></a>
                    <?php else: echo '—'; endif; ?></td>
                    <td><button class="adm-btn adm-btn-danger adm-btn-sm" onclick="deleteCertification(<?= $c['id'] ?>)"><i class="fas fa-trash-alt"></i></button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION E: PERFORMANCE METRICS -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-e" class="profile-section" style="display:none;">
    <div class="info-card" style="margin-bottom:2rem; padding:2rem;">
        <h3 style="margin-bottom:2rem; font-weight:800;"><i class="fas fa-chart-line" style="color:var(--primary); margin-right:.8rem;"></i> Operational Analytic Radar</h3>
        <div class="adm-summary-strip" id="perf-stats-strip" style="margin-bottom:0;">
            <div class="adm-mini-card"><div class="adm-mini-card-num teal" id="ps-orders-total">—</div><div class="adm-mini-card-label">Lifecycle Orders</div></div>
            <div class="adm-mini-card"><div class="adm-mini-card-num blue" id="ps-orders-month">—</div><div class="adm-mini-card-label">Current Cycle</div></div>
            <div class="adm-mini-card"><div class="adm-mini-card-num green" id="ps-results-total">—</div><div class="adm-mini-card-label">Validated Results</div></div>
            <div class="adm-mini-card"><div class="adm-mini-card-num red" id="ps-critical">—</div><div class="adm-mini-card-label">Critical Alerts</div></div>
            <div class="adm-mini-card"><div class="adm-mini-card-num orange" id="ps-avg-tat">—</div><div class="adm-mini-card-label">Mean TAT (Hrs)</div></div>
        </div>
    </div>
    <div style="display:grid; grid-template-columns: 1fr 400px; gap:2rem;">
        <div class="info-card" style="padding:2rem;">
            <h4 style="margin-bottom:1.5rem; font-weight:700; color:var(--text-primary);"><i class="fas fa-wave-square" style="color:var(--primary); margin-right:.5rem;"></i> Throughput Flux (7-Day Rolling)</h4>
            <div class="chart-wrap" style="height:280px;"><canvas id="perfVolChart"></canvas></div>
        </div>
        <div class="info-card" style="padding:2rem;">
            <h4 style="margin-bottom:1.5rem; font-weight:700; color:var(--text-primary);"><i class="fas fa-pie-chart" style="color:var(--primary); margin-right:.5rem;"></i> Classification Delta</h4>
            <div class="chart-wrap" style="height:280px;"><canvas id="perfStatusChart"></canvas></div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION F: ASSET CUSTODY -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-f" class="profile-section" style="display:none;">
    <div class="info-card" style="margin-bottom:2rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; padding-bottom:1.2rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-weight:800;"><i class="fas fa-microscope" style="color:var(--primary); margin-right:.8rem;"></i> Equipment Stewardship</h3>
            <a class="adm-btn adm-btn-primary adm-btn-sm" href="?tab=equipment"><i class="fas fa-external-link-alt"></i> Asset Hub</a>
        </div>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead><tr><th>Asset Descriptor</th><th>Model/Rev</th><th>Operational Status</th><th>Last Calibration</th><th>Next Due Delta</th></tr></thead>
                <tbody>
                <?php while($eq = mysqli_fetch_assoc($equip_res)):
                    $cal_style = '';
                    $seven_ahead = date('Y-m-d', strtotime('+7 days'));
                    if($eq['next_calibration_date'] && $eq['next_calibration_date'] < $today) $cal_style = 'background:rgba(231,76,60,0.1); color:var(--danger); font-weight:800; border:1px solid var(--danger);';
                    elseif($eq['next_calibration_date'] && $eq['next_calibration_date'] <= $seven_ahead) $cal_style = 'background:rgba(243,156,18,0.1); color:#f39c12; font-weight:700; border:1px solid #f39c12;';
                ?>
                <tr>
                    <td><strong style="font-size:1.05rem;"><?= e($eq['equipment_name']) ?></strong></td>
                    <td><span style="font-weight:600; color:var(--text-secondary);"><?= e($eq['model'] ?? '—') ?></span></td>
                    <td><span class="adm-badge <?= $eq['status']==='Active'?'adm-badge-success':($eq['status']==='Calibration Due'?'adm-badge-warning':'adm-badge-danger') ?>" style="padding:0.4rem 0.8rem; font-weight:700;"><?= e($eq['status']) ?></span></td>
                    <td><span style="font-weight:600; color:var(--text-muted);"><?= $eq['last_calibration_date'] ? date('d M Y', strtotime($eq['last_calibration_date'])) : '—' ?></span></td>
                    <td>
                        <?php if($eq['next_calibration_date']): ?>
                            <span class="adm-badge" style="<?= $cal_style ?: 'background:var(--surface-2); font-weight:600;' ?>">
                                <?= date('d M Y', strtotime($eq['next_calibration_date'])) ?>
                                <?php if($cal_style): ?> <i class="fas fa-exclamation-circle"></i><?php endif; ?>
                            </span>
                        <?php else: echo '—'; endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="info-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; padding-bottom:1.2rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-weight:800;"><i class="fas fa-vial-circle-check" style="color:var(--primary); margin-right:.8rem;"></i> Critical Reagent Oversight</h3>
            <a class="adm-btn adm-btn-primary adm-btn-sm" href="?tab=inventory"><i class="fas fa-external-link-alt"></i> Inventory</a>
        </div>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead><tr><th>Reagent Construct</th><th>Current Inventory</th><th>Alert Threshold</th><th>Stability Expiry</th><th>Status Matrix</th></tr></thead>
                <tbody>
                <?php while($rg = mysqli_fetch_assoc($reag_res)):
                    $r_style = ''; $r_badge = '';
                    if($rg['expiry_date'] && $rg['expiry_date'] < $today) { $r_style='color:var(--danger); font-weight:800;'; $r_badge='<span class="adm-badge adm-badge-danger">CRITICAL: EXPIRED</span>'; }
                    elseif($rg['expiry_date'] && $rg['expiry_date'] <= $thirty_days_date) { $r_style='color:#e67e22; font-weight:700;'; $r_badge='<span class="adm-badge adm-badge-warning">STABILITY ALERT</span>'; }
                    elseif($rg['quantity_in_stock'] <= $rg['reorder_level']) $r_badge='<span class="adm-badge adm-badge-warning" style="background:rgba(230,126,34,0.1); color:#e67e22; border:1px solid #e67e22;">DEPLETED STOCK</span>';
                    else $r_badge='<span class="adm-badge adm-badge-success" style="background:rgba(46,204,113,0.1); color:#27ae60; border:1px solid #27ae60;">STABLE</span>';
                ?>
                <tr>
                    <td><strong style="font-size:1.05rem;"><?= e($rg['name']) ?></strong></td>
                    <td><div style="font-weight:700; color:var(--primary);"><?= e($rg['quantity_in_stock']) ?> <small style="color:var(--text-muted); font-weight:600;"><?= e($rg['unit'] ?? '') ?></small></div></td>
                    <td><span style="font-weight:600; color:var(--text-muted);"><?= e($rg['reorder_level']) ?></span></td>
                    <td style="<?= $r_style ?>"><?= $rg['expiry_date'] ? date('d M Y', strtotime($rg['expiry_date'])) : '—' ?></td>
                    <td><?= $r_badge ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION G: SHIFT ALIGNMENT -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-g" class="profile-section" style="display:none;">
    <div class="info-card" style="padding:2.5rem;">
        <h3 style="margin-bottom:2rem; font-weight:800;"><i class="fas fa-calendar-alt" style="color:var(--primary); margin-right:.8rem;"></i> Shift & Availability Alignment</h3>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2.5rem;">
            <div style="padding:1.5rem; background:var(--surface-2); border-radius:15px; border:1px solid var(--border); transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted); font-weight:800; margin-bottom:0.8rem;">Current Assignment</div>
                <div style="font-size:1.6rem; font-weight:800; color:var(--primary); margin-bottom:0.3rem;"><?= e($profile['shift_type'] ?? 'Standard Day') ?></div>
                <div style="color:var(--text-secondary); font-weight:600; font-size:1rem;"><i class="fas fa-hospital-user" style="margin-right:0.5rem; opacity:0.6;"></i> <?= e($profile['lab_section'] ?? 'Core Diagnostics') ?></div>
            </div>
            <div style="padding:1.5rem; background:var(--surface-2); border-radius:15px; border:1px solid var(--border); transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted); font-weight:800; margin-bottom:0.8rem;">Real-time Presence</div>
                <?php
                $avail = $profile['availability_status'] ?? 'Available';
                $avail_color = ['Available'=>'#0d9488','Busy'=>'#e74c3c','On Break'=>'#f39c12','Off Duty'=>'#64748b'][$avail] ?? '#64748b';
                ?>
                <div style="font-size:1.6rem; font-weight:800; color:<?= $avail_color ?>; margin-bottom:0.3rem;"><i class="fas fa-circle" style="font-size:0.8rem; vertical-align:middle; margin-right:0.6rem;"></i> <?= e($avail) ?></div>
                <div style="color:var(--text-muted); font-weight:600; font-size:0.9rem;">Global Telemetry Status</div>
            </div>
        </div>
        <div class="form-group">
            <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.8rem;">Shift Preference & Scheduling Constraints <small style="color:var(--text-muted); font-weight:500;">(Visible to Personnel Management)</small></label>
            <textarea id="shift_notes" class="form-control" rows="4" placeholder="Document specific scheduling requirements or preferences here..." style="padding:1.2rem; font-size:1rem; border-radius:12px;"><?= e($profile['shift_notes'] ?? '') ?></textarea>
            <div style="margin-top:1.5rem; text-align:right;">
                <button class="adm-btn adm-btn-primary" onclick="saveShiftNotes()"><i class="fas fa-save"></i> Commit Preferences</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION H: CRYPTOGRAPHIC & SESSION CONTROL -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-h" class="profile-section" style="display:none;">
    <div class="info-card" style="margin-bottom:2rem; padding:2.5rem;">
        <h3 style="margin-bottom:2rem; font-weight:800;"><i class="fas fa-key-skeleton" style="color:var(--primary); margin-right:.8rem;"></i> Credential Rotation</h3>
        <div style="max-width:540px; display:grid; gap:1.5rem;">
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Existing Access Secret</label>
                <input type="password" id="cur_pass" class="form-control" style="padding:0.9rem; letter-spacing:0.2em;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">New Entropy String</label>
                <input type="password" id="new_pass" class="form-control" oninput="checkPasswordStrength(this.value)" style="padding:0.9rem; letter-spacing:0.2em;">
                <div id="pass-strength-bar" style="height:6px; border-radius:30px; margin-top:12px; background:var(--surface-2); overflow:hidden;">
                    <div id="pass-strength-fill" style="height:100%; width:0; transition:width 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);"></div>
                </div>
                <div id="pass-strength-label" style="font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-top:6px;"></div>
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Confirm New Secret</label>
                <input type="password" id="conf_pass" class="form-control" style="padding:0.9rem; letter-spacing:0.2em;">
            </div>
            <div style="margin-top:0.5rem;">
                <button class="adm-btn adm-btn-primary" style="padding:0.8rem 2rem;" onclick="changePassword()"><i class="fas fa-shield-check"></i> Authorize Rotation</button>
            </div>
        </div>
    </div>
    <div class="info-card" style="padding:2.5rem;">
        <h3 style="margin-bottom:2rem; font-weight:800;"><i class="fas fa-tower-broadcast" style="color:var(--primary); margin-right:.8rem;"></i> Active Session Telemetry</h3>
        <?php if(empty($sessions)): ?>
            <div style="text-align:center; padding:2rem; background:var(--surface-2); border-radius:12px; color:var(--text-muted); font-weight:600;">No active telemetry streams detected.</div>
        <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead><tr><th>Node / Interface</th><th>Network IP</th><th>Link Established</th><th>Last Pulse</th><th>Control</th></tr></thead>
                <tbody>
                <?php foreach($sessions as $s): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:0.8rem;">
                            <i class="fas <?= str_contains(strtolower($s['device_info'] ?? ''), 'mobile') ? 'fa-mobile-android' : 'fa-laptop-code' ?>" style="color:var(--primary); font-size:1.1rem; opacity:0.7;"></i>
                            <div>
                                <strong style="font-size:1rem;"><?= e($s['browser'] ?? 'Unknown Agent') ?></strong>
                                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;"><?= e($s['device_info'] ?? 'Unknown Hardware') ?></div>
                            </div>
                            <?php if($s['is_current']): ?>
                                <span class="adm-badge adm-badge-success" style="font-size:0.65rem; font-weight:800; transform:translateY(-2px);">ACTIVE NODE</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><code style="background:var(--surface-2); padding:0.3rem 0.6rem; border-radius:5px; font-weight:700; color:var(--text-secondary);"><?= e($s['ip_address'] ?? '0.0.0.0') ?></code></td>
                    <td><span style="font-weight:600; color:var(--text-secondary);"><?= date('d M, h:i A', strtotime($s['login_time'])) ?></span></td>
                    <td><span style="font-weight:600; color:var(--text-muted);"><?= date('d M, h:i A', strtotime($s['last_active'])) ?></span></td>
                    <td>
                        <?php if(!$s['is_current']): ?>
                            <button class="adm-btn adm-btn-danger adm-btn-sm" style="border-radius:8px;" onclick="logoutSession(<?= $s['id'] ?>)" title="Terminate Link"><i class="fas fa-power-off"></i></button>
                        <?php else: echo '<span style="color:var(--text-muted); font-size:0.8rem; font-weight:800; text-transform:uppercase;">Self Node</span>'; endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION I: ALERT CALIBRATION MATRIX -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-i" class="profile-section" style="display:none;">
    <div class="info-card" style="padding:2.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-weight:800;"><i class="fas fa-bell-on" style="color:var(--primary); margin-right:.8rem;"></i> Alert Calibration Matrix</h3>
            <button class="adm-btn adm-btn-primary" onclick="saveNotificationSettings()"><i class="fas fa-save"></i> Synchronize Alerts</button>
        </div>
        <?php
        $notif_toggles = [
            'notif_new_order'             => 'Inbound test order telemetry',
            'notif_urgent_order'          => 'Urgent / STAT priority escalations',
            'notif_critical_value'        => 'Unacknowledged critical result flags',
            'notif_equipment_due'         => 'Asset calibration & maintenance due',
            'notif_reagent_low'           => 'Inventory depletion thresholds',
            'notif_reagent_expiry'        => 'Reagent stability & expiry alerts',
            'notif_result_amendment'      => 'Validated result modification logs',
            'notif_doctor_message'        => 'Clinician clarification requests',
            'notif_qc_failure'            => 'Quality Control variance alerts',
            'notif_license_expiry'        => 'Regulatory credential expiry warnings',
            'notif_shift_reminder'        => 'Operational shift schedule pings',
            'notif_system_announcements'  => 'Central Command announcements',
        ];
        ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; margin-bottom:2.5rem;">
        <?php foreach($notif_toggles as $key => $label): $val = (int)($notif_settings[$key] ?? 1); ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.2rem 1.5rem; background:var(--surface-2); border-radius:12px; border:1px solid var(--border); transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                <span style="font-size:0.95rem; font-weight:600; color:var(--text-secondary);"><?= $label ?></span>
                <label class="prof-toggle"><input type="checkbox" id="ns_<?= $key ?>" name="<?= $key ?>" value="1" <?= $val ? 'checked' : '' ?>>
                    <span class="prof-toggle-slider"></span>
                </label>
            </div>
        <?php endforeach; ?>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:2rem; padding:2rem; background:var(--surface-2); border-radius:15px; border:1px solid var(--border);">
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Linguistic interface</label>
                <select id="ns_lang" class="form-select" style="padding:0.7rem; font-weight:600;">
                    <?php foreach(['English','French','Twi','Hausa','Ewe','Ga'] as $l): ?>
                    <option value="<?= $l ?>" <?= ($notif_settings['preferred_language'] ?? 'English') === $l ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Audio Telemetry</label>
                <div style="display:flex; align-items:center; gap:1rem; margin-top:0.4rem;">
                    <label class="prof-toggle"><input type="checkbox" id="ns_sound" <?= ($notif_settings['alert_sound_enabled'] ?? 1) ? 'checked' : '' ?>>
                        <span class="prof-toggle-slider"></span>
                    </label>
                    <span style="font-size:0.85rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Enabled</span>
                </div>
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Email Forwarding</label>
                <div style="display:flex; align-items:center; gap:1rem; margin-top:0.4rem;">
                    <label class="prof-toggle"><input type="checkbox" id="ns_email" <?= ($notif_settings['email_notifications'] ?? 1) ? 'checked' : '' ?>>
                        <span class="prof-toggle-slider"></span>
                    </label>
                    <span style="font-size:0.85rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Synchronized</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION J: DOCUMENT ARCHIVE -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-j" class="profile-section" style="display:none;">
    <div class="info-card" style="padding:2.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-weight:800;"><i class="fas fa-folders" style="color:var(--primary); margin-right:.8rem;"></i> Professional Document Archive</h3>
            <button class="adm-btn adm-btn-primary" onclick="$('#addDocModal').show()"><i class="fas fa-cloud-upload"></i> Upload Evidence</button>
        </div>
        <?php if(empty($documents)): ?>
            <div style="text-align:center; padding:3rem; color:var(--text-muted); background:var(--surface-2); border-radius:15px; border:2px dashed var(--border);">
                <i class="fas fa-file-invoice" style="font-size:3.5rem; margin-bottom:1rem; opacity:0.3;"></i>
                <p style="font-size:1.1rem; font-weight:600;">No digitized documents in this dossier.</p>
            </div>
        <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead><tr><th>Document Descriptor</th><th>Classification</th><th>Format</th><th>Weight</th><th>Timestamp</th><th>Control</th></tr></thead>
                <tbody>
                <?php foreach($documents as $doc): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:0.8rem;">
                            <i class="fas fa-file-pdf" style="color:var(--danger); font-size:1.4rem;"></i>
                            <strong style="font-size:1.05rem;"><?= e($doc['file_name']) ?></strong>
                        </div>
                    </td>
                    <td><div style="color:var(--text-secondary); font-weight:600;"><?= e($doc['description'] ?? 'Unclassified') ?></div></td>
                    <td><span class="adm-badge" style="background:var(--surface-2); font-weight:800; text-transform:uppercase;"><?= e(explode('/', $doc['file_type'] ?? 'unknown')[1] ?? 'FILE') ?></span></td>
                    <td><span style="font-size:0.9rem; color:var(--text-muted); font-weight:600;"><?= number_format($doc['file_size'] / 1024, 1) ?> KB</span></td>
                    <td><span style="font-weight:600; color:var(--text-muted);"><?= date('d M Y', strtotime($doc['uploaded_at'])) ?></span></td>
                    <td>
                        <div class="action-btns">
                            <a href="/RMU-Medical-Management-System/php/api/download_doc.php?file=<?= urlencode($doc['file_path']) ?>" class="adm-btn adm-btn-sm" style="background:var(--surface-2); color:var(--primary); border-radius:8px;" target="_blank"><i class="fas fa-download"></i></a>
                            <button class="adm-btn adm-btn-danger adm-btn-sm" style="border-radius:8px;" onclick="deleteDocument(<?= $doc['id'] ?>)"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION K: SECURITY LEDGER -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-k" class="profile-section" style="display:none;">
    <div class="info-card" style="padding:2.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-weight:800;"><i class="fas fa-fingerprint" style="color:var(--primary); margin-right:.8rem;"></i> Personal Action Ledger <small style="font-size:0.65em; vertical-align:middle; margin-left:1rem; opacity:0.5; font-weight:400;">(IMMUTABLE HISTORY)</small></h3>
            <a href="lab_exports.php?type=audit&tech_id=<?= $tech_pk ?>&csrf_token=<?= $csrf_token ?>" class="adm-btn adm-btn-sm" style="background:var(--surface-2); color:var(--primary); font-weight:700;" target="_blank"><i class="fas fa-file-csv"></i> Export CSV</a>
        </div>
        <div class="adm-table-wrap">
            <table class="adm-table display" id="auditTable">
                <thead><tr><th>Action Protocol</th><th>Module Scope</th><th>Entity Reference</th><th>Timestamp</th><th>IP Gateway</th></tr></thead>
                <tbody>
                <?php foreach($audit_rows as $au): ?>
                <tr>
                    <td><strong style="color:var(--primary);"><?= e($au['action_type']) ?></strong></td>
                    <td><span style="font-weight:700; color:var(--text-secondary);"><?= e($au['module_affected'] ?? '—') ?></span></td>
                    <td><code style="background:var(--surface-2); padding:0.2rem 0.5rem; border-radius:4px; font-weight:700; color:var(--text-muted);"><?= e($au['record_id'] ?? '—') ?></code></td>
                    <td><span style="font-weight:600; color:var(--text-muted);"><?= date('d M Y, h:i A', strtotime($au['created_at'] ?? 'now')) ?></span></td>
                    <td><code style="font-size:0.85rem; color:var(--text-muted);"><?= e($au['ip_address'] ?? '—') ?></code></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION L: INTEGRITY ENGINE -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-l" class="profile-section" style="display:none;">
    <div class="info-card" style="padding:2.5rem;">
        <h3 style="margin-bottom:2rem; font-weight:800;"><i class="fas fa-clipboard-check" style="color:var(--primary); margin-right:.8rem;"></i> Registry Integrity Engine</h3>
        <div style="margin-bottom:2.5rem; padding:1.5rem; background:var(--surface-2); border-radius:15px; border:1px solid var(--border);">
            <div style="display:flex; justify-content:space-between; margin-bottom:0.8rem; align-items:flex-end;">
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted); font-weight:800; margin-bottom:0.2rem;">Data Completion Quotient</div>
                    <div style="font-size:1.8rem; font-weight:800; color:var(--primary);" id="big-pct"><?= $completeness_pct ?>%</div>
                </div>
                <div style="font-size:0.9rem; font-weight:700; color:var(--text-secondary);">Technician Integrity Tier: <?= $completeness_pct >= 90 ? '<span style="color:#27ae60;">ELITE</span>' : ($completeness_pct >= 60 ? '<span style="color:#f39c12;">OPERATIONAL</span>' : '<span style="color:var(--danger);">INCOMPLETE</span>') ?></div>
            </div>
            <div style="background:rgba(0,0,0,0.05); border-radius:30px; height:12px; overflow:hidden;">
                <div id="big-pct-bar" style="width:<?= $completeness_pct ?>%; height:100%; background:var(--primary); border-radius:30px; transition:width 1s cubic-bezier(0.19, 1, 0.22, 1); box-shadow:0 0 15px rgba(13,148,136,0.3);"></div>
            </div>
        </div>
        <?php
        $checks = [
            'personal_info_complete'       => ['Identity Matrix',                  'sec-b', 'fa-id-badge'],
            'professional_profile_complete' => ['Expertise Profile',                 'sec-c', 'fa-user-md'],
            'qualifications_complete'      => ['Academic Credentials',             'sec-d', 'fa-graduation-cap'],
            'equipment_assigned'           => ['Asset Stewardship',                'sec-f', 'fa-microscope'],
            'shift_profile_complete'       => ['Operational Availability',         'sec-g', 'fa-calendar-check'],
            'photo_uploaded'               => ['Biometric ID Photo',               'sec-a', 'fa-camera-retro'],
            'security_setup_complete'      => ['Alert Calibration',                'sec-i', 'fa-bell-slash'],
            'documents_uploaded'           => ['Documentary Evidence',             'sec-j', 'fa-folder-open'],
        ];
        ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem;">
        <?php foreach($checks as $key => [$label, $target_sec, $icon]): $done = (bool)($completeness_row[$key] ?? 0); ?>
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.2rem 1.5rem; background:var(--surface-2); border-radius:12px; border:1px solid var(--border); border-left:5px solid <?= $done ? '#27ae60' : '#f39c12' ?>; transition:var(--transition);" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
            <div style="display:flex; align-items:center; gap:1.2rem;">
                <div style="width:40px; height:40px; border-radius:10px; background:white; display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow-sm); border:1px solid var(--border);">
                    <i class="fas <?= $icon ?>" style="color:<?= $done ? '#27ae60' : '#f39c12' ?>; font-size:1.1rem;"></i>
                </div>
                <span style="font-size:1rem; font-weight:700; color:var(--text-primary);"><?= $label ?></span>
            </div>
            <?php if($done): ?>
                <div style="color:#27ae60; font-weight:800; font-size:0.85rem; display:flex; align-items:center; gap:0.5rem;"><i class="fas fa-check-double"></i> VERIFIED</div>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="showSection('<?= $target_sec ?>', null)" style="font-size:0.85rem; color:var(--primary); font-weight:800; text-decoration:none; text-transform:uppercase; letter-spacing:0.02em;">REMEDIATE →</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

</div><!-- end #profileContent -->
</div><!-- end layout grid -->

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- MODALS: HIGH-FIDELITY OVERLAYS -->
<!-- ══════════════════════════════════════════════════════════════════════ -->

<!-- Add Qualification Modal -->
<div id="addQualModal" class="modal fade" tabindex="-1" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9000; backdrop-filter:blur(8px);" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-dialog modal-dialog-centered" style="max-width:560px; margin:auto; top:50%; transform:translateY(-50%);">
        <div class="modal-content" style="background:var(--surface); border:1px solid var(--border); border-radius:20px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); overflow:hidden;">
            <div class="modal-header" style="padding:1.5rem 2rem; background:var(--surface-2); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <h4 style="margin:0; font-weight:800; color:var(--text-primary);"><i class="fas fa-graduation-cap" style="color:var(--primary); margin-right:0.8rem;"></i> Add Qualification</h4>
                <button type="button" style="background:none; border:none; font-size:1.4rem; color:var(--text-muted); cursor:pointer;" onclick="$('#addQualModal').hide()">&times;</button>
            </div>
            <div class="modal-body" style="padding:2rem;">
                <div class="form-group mb-4">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Degree / Certificate Nomenclature <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="q_degree" class="form-control" placeholder="e.g. BSc Medical Laboratory Science" style="padding:0.8rem;">
                </div>
                <div class="form-group mb-4">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Conferring Institution <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="q_inst" class="form-control" style="padding:0.8rem;">
                </div>
                <div class="form-group mb-4">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Cycle Year</label>
                    <input type="number" id="q_year" class="form-control" min="1970" max="<?= date('Y') ?>" style="padding:0.8rem;">
                </div>
                <div class="form-group">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Digital Evidence (PDF/Image, max 5MB)</label>
                    <div style="padding:1.5rem; border:2px dashed var(--border); border-radius:12px; text-align:center; background:var(--surface-2);">
                        <input type="file" id="q_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" onchange="$(this).next().text(this.files[0].name)">
                        <label for="q_file" style="cursor:pointer; color:var(--primary); font-weight:700;"><i class="fas fa-upload" style="margin-right:0.5rem;"></i> Choose File</label>
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.4rem;">Placeholder for file name</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding:1.5rem 2rem; background:var(--surface-2); border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:1rem;">
                <button class="adm-btn" style="background:white; border:1px solid var(--border); color:var(--text-secondary);" onclick="$('#addQualModal').hide()">Cancel</button>
                <button class="adm-btn adm-btn-primary" onclick="submitQualification()"><i class="fas fa-save"></i> Commit Record</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Certification Modal -->
<div id="addCertModal" class="modal fade" tabindex="-1" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9000; backdrop-filter:blur(8px);" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-dialog modal-dialog-centered" style="max-width:560px; margin:auto; top:50%; transform:translateY(-50%);">
        <div class="modal-content" style="background:var(--surface); border:1px solid var(--border); border-radius:20px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); overflow:hidden;">
            <div class="modal-header" style="padding:1.5rem 2rem; background:var(--surface-2); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <h4 style="margin:0; font-weight:800; color:var(--text-primary);"><i class="fas fa-certificate" style="color:var(--primary); margin-right:0.8rem;"></i> Register Certification</h4>
                <button type="button" style="background:none; border:none; font-size:1.4rem; color:var(--text-muted); cursor:pointer;" onclick="$('#addCertModal').hide()">&times;</button>
            </div>
            <div class="modal-body" style="padding:2rem;">
                <div class="form-group mb-4">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Certification Descriptor <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="c_name" class="form-control" style="padding:0.8rem;">
                </div>
                <div class="form-group mb-4">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Issuing Registry</label>
                    <input type="text" id="c_org" class="form-control" style="padding:0.8rem;">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;" class="mb-4">
                    <div class="form-group">
                        <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Acquisition Date</label>
                        <input type="date" id="c_issue" class="form-control" style="padding:0.8rem;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Expiry Deadline</label>
                        <input type="date" id="c_expiry" class="form-control" style="padding:0.8rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Credential Evidence</label>
                    <div style="padding:1.5rem; border:2px dashed var(--border); border-radius:12px; text-align:center; background:var(--surface-2);">
                        <input type="file" id="c_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" onchange="$(this).next().text(this.files[0].name)">
                        <label for="c_file" style="cursor:pointer; color:var(--primary); font-weight:700;"><i class="fas fa-upload" style="margin-right:0.5rem;"></i> Choose File</label>
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.4rem;">PDF, JPG, or PNG</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding:1.5rem 2rem; background:var(--surface-2); border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:1rem;">
                <button class="adm-btn" style="background:white; border:1px solid var(--border); color:var(--text-secondary);" onclick="$('#addCertModal').hide()">Cancel</button>
                <button class="adm-btn adm-btn-primary" onclick="submitCertification()"><i class="fas fa-save"></i> Register Asset</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Document Modal -->
<div id="addDocModal" class="modal fade" tabindex="-1" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9000; backdrop-filter:blur(8px);" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-dialog modal-dialog-centered" style="max-width:560px; margin:auto; top:50%; transform:translateY(-50%);">
        <div class="modal-content" style="background:var(--surface); border:1px solid var(--border); border-radius:20px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); overflow:hidden;">
            <div class="modal-header" style="padding:1.5rem 2rem; background:var(--surface-2); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <h4 style="margin:0; font-weight:800; color:var(--text-primary);"><i class="fas fa-file-upload" style="color:var(--primary); margin-right:0.8rem;"></i> Archive Document</h4>
                <button type="button" style="background:none; border:none; font-size:1.4rem; color:var(--text-muted); cursor:pointer;" onclick="$('#addDocModal').hide()">&times;</button>
            </div>
            <div class="modal-body" style="padding:2rem;">
                <div class="form-group mb-4">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Descriptor / Label <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="doc_desc" class="form-control" placeholder="e.g. Lab Technician License 2025" style="padding:0.8rem;">
                </div>
                <div class="form-group">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:0.6rem;">Binary Payload (max 10MB)</label>
                    <div style="padding:2rem; border:2px dashed var(--border); border-radius:12px; text-align:center; background:var(--surface-2);">
                        <input type="file" id="doc_file" class="form-control" style="display:none;" onchange="$(this).next().text(this.files[0].name)">
                        <label for="doc_file" style="cursor:pointer; color:var(--primary); font-weight:700;"><i class="fas fa-cloud-upload-alt" style="font-size:2rem; display:block; margin-bottom:0.8rem;"></i> Select Local Payload</label>
                        <div style="font-size:0.85rem; color:var(--text-muted);">PDF, Word, or Imagery supported</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding:1.5rem 2rem; background:var(--surface-2); border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:1rem;">
                <button class="adm-btn" style="background:white; border:1px solid var(--border); color:var(--text-secondary);" onclick="$('#addDocModal').hide()">Cancel</button>
                <button class="adm-btn adm-btn-primary" onclick="submitDocument()"><i class="fas fa-upload"></i> Initialize Upload</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- CSS: ADAPTIVE UI OVERRIDES -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<style>
.prof-toggle { position:relative; display:inline-block; width:48px; height:26px; }
.prof-toggle input { opacity:0; width:0; height:0; }
.prof-toggle-slider { position:absolute; cursor:pointer; inset:0; background:rgba(0,0,0,0.05); border-radius:26px; border:1px solid var(--border); transition:0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.prof-toggle input:checked + .prof-toggle-slider { background:var(--primary); border-color:var(--primary); }
.prof-toggle-slider:before { content:""; position:absolute; width:18px; height:18px; left:3px; bottom:3px; background:white; border-radius:50%; transition:0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow:0 2px 4px rgba(0,0,0,0.1); }
.prof-toggle input:checked + .prof-toggle-slider:before { transform:translateX(22px); }

.prof-nav-item:hover { background:rgba(13,148,136,0.05); color:var(--primary) !important; padding-left:1.8rem !important; }
.prof-nav-item.active { background:rgba(13,148,136,0.1); border-left-color:var(--primary) !important; color:var(--primary) !important; font-weight:800 !important; }

#profileContent .profile-section { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

.adm-table th { background:var(--surface-2); color:var(--text-muted); text-transform:uppercase; font-size:0.75rem; font-weight:800; letter-spacing:0.05em; padding:1.2rem 1.5rem; }
.adm-table td { padding:1.2rem 1.5rem; border-bottom:1px solid var(--border); vertical-align:middle; }
</style>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- JAVASCRIPT: TECHNICIAN REACTIVITY ENGINE -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<script>
const PROF_ACTION = 'lab_profile_actions.php';
const CSRF = '<?= $csrf_token ?>';

function profToast(msg, ok=true) {
    const t = $('#profileToast');
    t.html(`<div style="padding:1.2rem 1.6rem; background:${ok?'#0d9488':'#e74c3c'}; color:white; border-radius:15px; box-shadow:var(--shadow-lg); font-size:1rem; font-weight:700; display:flex; align-items:center; gap:1rem; animation:slideIn 0.3s ease-out;">
        ${ok?'<i class="fas fa-check-circle" style="font-size:1.4rem;"></i>':'<i class="fas fa-shield-exclamation" style="font-size:1.4rem;"></i>'}
        <span>${msg}</span>
    </div>`);
    t.fadeIn(200);
    setTimeout(() => t.fadeOut(400), 4000);
}

function updateCompletenessUI(pct) {
    if (pct === undefined) return;
    $('#completeness-bar-side, #big-pct-bar').css('width', pct+'%');
    $('#completeness-pct-side, #big-pct').text(pct+'%');
    $('#completeness-label').text(pct+'% Credential Path');
}

function showSection(id, el) {
    $('.profile-section').hide();
    $('#'+id).css('display','block');
    $('.prof-nav-item').removeClass('active');
    if(el) $(el).addClass('active');
    else $(`[onclick*="${id}"]`).addClass('active');
    
    if(id === 'sec-e') loadPerfStats();
    if(id === 'sec-k') { 
        if(!$.fn.DataTable.isDataTable('#auditTable')) {
            $('#auditTable').DataTable({
                pageLength: 15,
                order: [[3, 'desc']],
                language: { search: "_INPUT_", searchPlaceholder: "Search Action Logs..." }
            });
        }
    }
}

function uploadPhoto(input) {
    if (!input.files.length) return;
    const fd = new FormData();
    fd.append('photo', input.files[0]);
    fd.append('action', 'upload_profile_photo');
    fd.append('csrf_token', CSRF);
    profToast('Transmitting biometric packet...', true);
    $.ajax({ url: PROF_ACTION, type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
        success: r => {
            if(r.success) {
                $('#profile-photo-img').attr('src', '/RMU-Medical-Management-System/uploads/profiles/'+r.new_photo+'?t='+Date.now());
                updateCompletenessUI(r.completeness);
                profToast('Identity visualization updated.');
            } else profToast(r.message, false);
        }
    });
}

function setAvailability(status) {
    $.post(PROF_ACTION, {action:'toggle_availability', status, csrf_token:CSRF}, r => {
        if(r.success) profToast('Operational state: '+status);
        else profToast(r.message, false);
    }, 'json');
}

function savePersonalInfo() {
    $.post(PROF_ACTION, {
        action:'save_personal_info', csrf_token:CSRF,
        full_name: $('#pi_full_name').val(), dob: $('#pi_dob').val(),
        gender: $('#pi_gender').val(), nationality: $('#pi_nationality').val(),
        marital_status: $('#pi_marital').val(), phone: $('#pi_phone').val(),
        phone2: $('#pi_phone2').val(), address: $('#pi_address').val(),
        city: $('#pi_city').val(), region: $('#pi_region').val(),
        country: $('#pi_country').val(), postal_code: $('#pi_postal').val()
    }, r => {
        if(r.success) { updateCompletenessUI(r.completeness); profToast('Dossier synchronized with registry.'); }
        else profToast(r.message, false);
    }, 'json');
}

function saveProfessionalProfile() {
    $.post(PROF_ACTION, {
        action:'save_professional_profile', csrf_token:CSRF,
        specialization: $('#pp_spec').val(), sub_specialization: $('#pp_sub_spec').val(),
        department_id: $('#pp_dept').val(), designation: $('#pp_desig').val(),
        years_of_experience: $('#pp_yoe').val(), license_number: $('#pp_lic').val(),
        license_issuing_body: $('#pp_lic_body').val(), license_expiry_date: $('#pp_lic_exp').val(),
        institution_attended: $('#pp_inst').val(), graduation_year: $('#pp_grad').val(),
        postgraduate_details: $('#pp_pg').val(), languages_spoken: $('#pp_langs').val(),
        bio: $('#pp_bio').val()
    }, r => {
        if(r.success) { updateCompletenessUI(r.completeness); profToast('Expertise registry updated.'); }
        else profToast(r.message, false);
    }, 'json');
}

function submitQualification() {
    const fd = new FormData();
    fd.append('action','upload_qualification'); fd.append('csrf_token', CSRF);
    fd.append('degree_name', $('#q_degree').val());
    fd.append('institution_name', $('#q_inst').val());
    fd.append('year_awarded', $('#q_year').val());
    if ($('#q_file')[0].files.length) fd.append('certificate', $('#q_file')[0].files[0]);
    $.ajax({ url:PROF_ACTION, type:'POST', data:fd, processData:false, contentType:false, dataType:'json',
        success: r => { if(r.success){ profToast(r.message); setTimeout(()=>location.reload(),1000); } else profToast(r.message,false); }
    });
}

function deleteQualification(id) {
    Swal.fire({
        title: 'Purge Academic Record?',
        text: "This removal is permanent and will be logged.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--danger)',
        confirmButtonText: 'Yes, Purge Record'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(PROF_ACTION, {action:'delete_qualification', qual_id:id, csrf_token:CSRF}, r => {
                if(r.success){ profToast('Record purged.'); setTimeout(()=>location.reload(),800); } else profToast(r.message,false);
            }, 'json');
        }
    });
}

function submitCertification() {
    const fd = new FormData();
    fd.append('action','upload_certification'); fd.append('csrf_token',CSRF);
    fd.append('certification_name', $('#c_name').val()); fd.append('issuing_organization', $('#c_org').val());
    fd.append('issue_date', $('#c_issue').val()); fd.append('expiry_date', $('#c_expiry').val());
    if ($('#c_file')[0].files.length) fd.append('certificate', $('#c_file')[0].files[0]);
    $.ajax({ url:PROF_ACTION, type:'POST', data:fd, processData:false, contentType:false, dataType:'json',
        success: r => { if(r.success){ profToast('Asset registered successfully.'); setTimeout(()=>location.reload(),1000); } else profToast(r.message,false); }
    });
}

function deleteCertification(id) {
    if(!confirm('Purge certification from registry?')) return;
    $.post(PROF_ACTION, {action:'delete_certification', cert_id:id, csrf_token:CSRF}, r => {
        if(r.success){ profToast(r.message); setTimeout(()=>location.reload(),800); } else profToast(r.message,false);
    }, 'json');
}

function submitDocument() {
    if (!$('#doc_file')[0].files.length) { profToast('Upload payload required.', false); return; }
    const fd = new FormData();
    fd.append('action','upload_document'); fd.append('csrf_token',CSRF);
    fd.append('document', $('#doc_file')[0].files[0]);
    fd.append('description', $('#doc_desc').val());
    $.ajax({ url:PROF_ACTION, type:'POST', data:fd, processData:false, contentType:false, dataType:'json',
        success: r => {
            if(r.success){ profToast('Payload archived.'); updateCompletenessUI(r.completeness); setTimeout(()=>location.reload(),1000); }
            else profToast(r.message,false);
        }
    });
}

function deleteDocument(id) {
    if(!confirm('Permanently de-archive this asset?')) return;
    $.post(PROF_ACTION, {action:'delete_document', doc_id:id, csrf_token:CSRF}, r => {
        if(r.success){ profToast('Asset de-archived.'); setTimeout(()=>location.reload(),800); } else profToast(r.message,false);
    }, 'json');
}

function saveNotificationSettings() {
    const data = {action:'save_notification_settings', csrf_token:CSRF,
        preferred_language: $('#ns_lang').val(),
        alert_sound_enabled: $('#ns_sound').is(':checked')?1:0,
        email_notifications: $('#ns_email').is(':checked')?1:0,
        sms_notifications: 0
    };
    ['notif_new_order','notif_urgent_order','notif_critical_value','notif_equipment_due',
     'notif_reagent_low','notif_reagent_expiry','notif_result_amendment','notif_doctor_message',
     'notif_qc_failure','notif_license_expiry','notif_shift_reminder','notif_system_announcements'
    ].forEach(k => data[k] = $(`#ns_${k}`).is(':checked') ? 1 : 0);
    $.post(PROF_ACTION, data, r => {
        if(r.success){ updateCompletenessUI(r.completeness); profToast('Alert calibration synchronized.'); }
        else profToast(r.message, false);
    }, 'json');
}

function changePassword() {
    $.post(PROF_ACTION, {action:'change_password', csrf_token:CSRF,
        current_password: $('#cur_pass').val(),
        new_password: $('#new_pass').val(),
        confirm_password: $('#conf_pass').val()
    }, r => {
        if(r.success){ profToast('Entropy rotation successful.'); setTimeout(()=>location.href='/RMU-Medical-Management-System/php/login.php',2000); }
        else profToast(r.message, false);
    }, 'json');
}

function checkPasswordStrength(val) {
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const labels = ['INSECURE','WEAK','STABLE','ROBUST','ELITE'];
    const colors = ['#e74c3c','#e67e22','#3498db','#2ecc71','#00b894'];
    $('#pass-strength-fill').css({width:(score*25)+'%', background: colors[score]});
    $('#pass-strength-label').css('color', colors[score]).text(labels[score]);
}

function logoutSession(id) {
    if(!confirm('Terminate this telemetry link?')) return;
    $.post(PROF_ACTION, {action:'logout_session', session_id:id, csrf_token:CSRF}, r => {
        if(r.success){ profToast('Session link severed.'); setTimeout(()=>location.reload(),800); }
        else profToast(r.message,false);
    }, 'json');
}

function saveShiftNotes() {
    $.post(PROF_ACTION, {action:'save_personal_info', csrf_token:CSRF,
        full_name: '<?= addslashes($profile['full_name']) ?>',
        shift_notes: $('#shift_notes').val()
    }, r => { profToast(r.success ? 'Scheduling constraints registered.' : r.message, r.success); }, 'json');
}

function calcAge() {
    const dob = new Date($('#pi_dob').val());
    if (isNaN(dob)) return;
    const age = Math.floor((Date.now() - dob) / 31557600000);
    $('#pi_age').val(age + ' orbits');
}

let perfChartsInitialized = false;
function loadPerfStats() {
    if (perfChartsInitialized) return;
    $.post(PROF_ACTION, {action:'fetch_performance_stats', csrf_token:CSRF}, r => {
        if (!r.success) return;
        const s = r.stats;
        $('#ps-orders-total').text(s.orders_total);
        $('#ps-orders-month').text(s.orders_month);
        $('#ps-results-total').text(s.results_total);
        $('#ps-critical').text(s.critical_total);
        $('#ps-avg-tat').text(s.avg_tat || '—');

        new Chart(document.getElementById('perfVolChart').getContext('2d'), {
            type: 'bar',
            data: { 
                labels: s.vol_labels, 
                datasets:[{
                    label: 'Result Flux',
                    data: s.vol_values,
                    backgroundColor: 'rgba(13,148,136,0.6)',
                    borderColor: '#0d9488',
                    borderWidth: 2,
                    borderRadius: 6
                }] 
            },
            options: { 
                responsive:true, 
                maintainAspectRatio:false, 
                plugins:{legend:{display:false}},
                scales: { y: { beginAtZero:true, grid:{ color:'rgba(0,0,0,0.05)' } }, x: { grid:{ display:false } } }
            }
        });
        new Chart(document.getElementById('perfStatusChart').getContext('2d'), {
            type: 'doughnut',
            data: { 
                labels:['Normal','Abnormal','Critical'], 
                datasets:[{
                    data:[s.results_total - s.critical_total - Math.floor(s.results_total*0.1), Math.floor(s.results_total*0.1), s.critical_total], 
                    backgroundColor:['#2ecc71','#f1c40f','#e74c3c'], 
                    borderWidth:0,
                    hoverOffset: 15
                }] 
            },
            options: { responsive:true, maintainAspectRatio:false, cutout:'75%', plugins:{legend:{position:'bottom'}} }
        });
        perfChartsInitialized = true;
    }, 'json');
}

document.addEventListener('DOMContentLoaded', function() {
    const pct = <?= $completeness_pct ?>;
    const ctx = document.getElementById('completenessDonut');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: { datasets:[{ data:[pct, 100-pct], backgroundColor:['#0d9488','rgba(0,0,0,0.05)'], borderWidth:0 }] },
            options: { responsive:false, cutout:'82%', plugins:{legend:{display:false},tooltip:{enabled:false}}, animation:{ animateRotate:true, duration:1400 }}
        });
    }
});
</script>
