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
<!-- SECTION D: QUALIFICATIONS & CERTIFICATIONS -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-d" class="profile-section" style="display:none;">
    <!-- Qualifications -->
    <div class="info-card" style="margin-bottom:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fas fa-graduation-cap" style="color:var(--role-accent);"></i> Academic Qualifications</h3>
            <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="$('#addQualModal').show()"><i class="fas fa-plus"></i> Add Qualification</button>
        </div>
        <?php if(empty($qualifications)): ?>
            <p style="color:var(--text-muted);">No qualifications added yet.</p>
        <?php else: ?>
        <table class="adm-table" style="font-size:1rem;">
            <thead><tr><th>Degree/Certificate</th><th>Institution</th><th>Year</th><th>Certificate</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($qualifications as $q): ?>
            <tr>
                <td><strong><?= e($q['degree_name']) ?></strong></td>
                <td><?= e($q['institution_name']) ?></td>
                <td><?= e($q['year_awarded'] ?? '—') ?></td>
                <td><?php if($q['certificate_file_path']): ?>
                    <a href="/RMU-Medical-Management-System/php/api/download_doc.php?file=<?= urlencode($q['certificate_file_path']) ?>" class="adm-btn adm-btn-sm" style="background:var(--surface-2);" target="_blank"><i class="fas fa-download"></i></a>
                <?php else: echo '—'; endif; ?></td>
                <td><button class="adm-btn adm-btn-danger adm-btn-sm" onclick="deleteQualification(<?= $q['id'] ?>)"><i class="fas fa-trash"></i></button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Certifications -->
    <div class="info-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fas fa-certificate" style="color:var(--role-accent);"></i> Professional Certifications</h3>
            <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="$('#addCertModal').show()"><i class="fas fa-plus"></i> Add Certification</button>
        </div>
        <?php if(empty($certifications)): ?>
            <p style="color:var(--text-muted);">No certifications added yet.</p>
        <?php else: ?>
        <table class="adm-table" style="font-size:1rem;">
            <thead><tr><th>Certification</th><th>Issuing Body</th><th>Issued</th><th>Expires</th><th>File</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($certifications as $c):
                $exp_style = '';
                if($c['expiry_date'] && $c['expiry_date'] <= $sixty_days_date) $exp_style = 'color:var(--warning);font-weight:600;';
                if($c['expiry_date'] && $c['expiry_date'] < $today) $exp_style = 'color:var(--danger);font-weight:700;';
            ?>
            <tr>
                <td><strong><?= e($c['certification_name']) ?></strong></td>
                <td><?= e($c['issuing_organization'] ?? '—') ?></td>
                <td><?= $c['issue_date'] ? date('d M Y', strtotime($c['issue_date'])) : '—' ?></td>
                <td style="<?= $exp_style ?>"><?= $c['expiry_date'] ? date('d M Y', strtotime($c['expiry_date'])) : '—' ?>
                    <?php if($exp_style && $c['expiry_date'] >= $today): ?><span class="adm-badge" style="background:var(--warning);color:white;font-size:0.7em;">EXPIRING</span><?php endif; ?>
                </td>
                <td><?php if($c['certificate_file_path']): ?>
                    <a href="/RMU-Medical-Management-System/php/api/download_doc.php?file=<?= urlencode($c['certificate_file_path']) ?>" class="adm-btn adm-btn-sm" style="background:var(--surface-2);" target="_blank"><i class="fas fa-download"></i></a>
                <?php else: echo '—'; endif; ?></td>
                <td><button class="adm-btn adm-btn-danger adm-btn-sm" onclick="deleteCertification(<?= $c['id'] ?>)"><i class="fas fa-trash"></i></button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION E: PERFORMANCE STATISTICS -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-e" class="profile-section" style="display:none;">
    <div class="info-card" style="margin-bottom:1.5rem;">
        <h3 style="margin-bottom:1.5rem;"><i class="fas fa-chart-bar" style="color:var(--role-accent);"></i> My Workload & Performance</h3>
        <div class="adm-summary-strip" id="perf-stats-strip">
            <div class="adm-mini-card"><div class="adm-mini-card-num teal" id="ps-orders-total">—</div><div class="adm-mini-card-label">Total Orders</div></div>
            <div class="adm-mini-card"><div class="adm-mini-card-num blue" id="ps-orders-month">—</div><div class="adm-mini-card-label">This Month</div></div>
            <div class="adm-mini-card"><div class="adm-mini-card-num green" id="ps-results-total">—</div><div class="adm-mini-card-label">Results Validated</div></div>
            <div class="adm-mini-card"><div class="adm-mini-card-num red" id="ps-critical">—</div><div class="adm-mini-card-label">Critical Results</div></div>
            <div class="adm-mini-card"><div class="adm-mini-card-num orange" id="ps-avg-tat">—</div><div class="adm-mini-card-label">Avg TAT (hrs)</div></div>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
        <div class="info-card">
            <h4 style="margin-bottom:1rem;font-size:1rem;">Results — Last 7 Days</h4>
            <div class="chart-wrap" style="height:200px;"><canvas id="perfVolChart"></canvas></div>
        </div>
        <div class="info-card">
            <h4 style="margin-bottom:1rem;font-size:1rem;">Status Distribution</h4>
            <div class="chart-wrap" style="height:200px;"><canvas id="perfStatusChart"></canvas></div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION F: EQUIPMENT & REAGENTS -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-f" class="profile-section" style="display:none;">
    <div class="info-card" style="margin-bottom:1.5rem;">
        <div style="display:flex;justify-content:space-between;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fas fa-microscope" style="color:var(--role-accent);"></i> Equipment Responsibility</h3>
            <a class="adm-btn adm-btn-primary adm-btn-sm" href="?tab=equipment"><i class="fas fa-external-link-alt"></i> Go to Equipment</a>
        </div>
        <table class="adm-table" style="font-size:0.95rem;"><thead><tr><th>Equipment</th><th>Model</th><th>Status</th><th>Last Calibration</th><th>Next Due</th></tr></thead><tbody>
        <?php while($eq = mysqli_fetch_assoc($equip_res)):
            $cal_style = '';
            $seven_ahead = date('Y-m-d', strtotime('+7 days'));
            if($eq['next_calibration_date'] && $eq['next_calibration_date'] < $today) $cal_style = 'color:var(--danger);font-weight:700;';
            elseif($eq['next_calibration_date'] && $eq['next_calibration_date'] <= $seven_ahead) $cal_style = 'color:var(--warning);font-weight:600;';
        ?>
        <tr>
            <td><strong><?= e($eq['equipment_name']) ?></strong></td>
            <td><?= e($eq['model'] ?? '—') ?></td>
            <td><span class="adm-badge <?= $eq['status']==='Active'?'adm-badge-success':($eq['status']==='Calibration Due'?'adm-badge-warning':'adm-badge-danger') ?>"><?= e($eq['status']) ?></span></td>
            <td><?= $eq['last_calibration_date'] ? date('d M Y', strtotime($eq['last_calibration_date'])) : '—' ?></td>
            <td style="<?= $cal_style ?>"><?= $eq['next_calibration_date'] ? date('d M Y', strtotime($eq['next_calibration_date'])) : '—' ?>
                <?php if($cal_style): ?><i class="fas fa-exclamation-triangle"></i><?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?></tbody></table>
    </div>
    <div class="info-card">
        <div style="display:flex;justify-content:space-between;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fas fa-flask" style="color:var(--role-accent);"></i> Reagent Responsibility</h3>
            <a class="adm-btn adm-btn-primary adm-btn-sm" href="?tab=inventory"><i class="fas fa-external-link-alt"></i> Go to Inventory</a>
        </div>
        <table class="adm-table" style="font-size:0.95rem;"><thead><tr><th>Reagent</th><th>Stock</th><th>Reorder Level</th><th>Expiry</th><th>Status</th></tr></thead><tbody>
        <?php while($rg = mysqli_fetch_assoc($reag_res)):
            $r_style = ''; $r_badge = '';
            if($rg['expiry_date'] && $rg['expiry_date'] < $today) { $r_style='color:var(--danger);'; $r_badge='<span class="adm-badge adm-badge-danger">Expired</span>'; }
            elseif($rg['expiry_date'] && $rg['expiry_date'] <= $thirty_days_date) { $r_style='color:var(--warning);'; $r_badge='<span class="adm-badge adm-badge-warning">Expiring Soon</span>'; }
            elseif($rg['quantity_in_stock'] <= $rg['reorder_level']) $r_badge='<span class="adm-badge adm-badge-warning">Low Stock</span>';
            else $r_badge='<span class="adm-badge adm-badge-success">OK</span>';
        ?>
        <tr>
            <td><strong><?= e($rg['name']) ?></strong></td>
            <td><?= e($rg['quantity_in_stock']) ?> <?= e($rg['unit'] ?? '') ?></td>
            <td><?= e($rg['reorder_level']) ?></td>
            <td style="<?= $r_style ?>"><?= $rg['expiry_date'] ? date('d M Y', strtotime($rg['expiry_date'])) : '—' ?></td>
            <td><?= $r_badge ?></td>
        </tr>
        <?php endwhile; ?></tbody></table>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION G: SHIFT & AVAILABILITY -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-g" class="profile-section" style="display:none;">
    <div class="info-card">
        <h3 style="margin-bottom:1.2rem;"><i class="fas fa-calendar-alt" style="color:var(--role-accent);"></i> Shift & Availability</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;margin-bottom:1.5rem;">
            <div style="padding:1.2rem;background:var(--surface-2);border-radius:var(--radius-md);">
                <div style="font-size:0.8rem;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.4rem;">Current Shift</div>
                <div style="font-size:1.3rem;font-weight:700;color:var(--text-primary);"><?= e($profile['shift_type'] ?? 'Day Shift') ?></div>
                <div style="color:var(--text-secondary);font-size:0.9rem;"><?= e($profile['lab_section'] ?? 'General Lab') ?></div>
            </div>
            <div style="padding:1.2rem;background:var(--surface-2);border-radius:var(--radius-md);">
                <div style="font-size:0.8rem;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.4rem;">Current Availability</div>
                <?php
                $avail = $profile['availability_status'] ?? 'Available';
                $avail_color = ['Available'=>'var(--success)','Busy'=>'var(--danger)','On Break'=>'var(--warning)','Off Duty'=>'var(--text-muted)'][$avail] ?? 'var(--text-muted)';
                ?>
                <div style="font-size:1.3rem;font-weight:700;color:<?= $avail_color ?>;"><?= e($avail) ?></div>
                <div style="color:var(--text-muted);font-size:0.85rem;">Updates instantly across dashboards</div>
            </div>
        </div>
        <div class="form-group">
            <label>Shift Preference Notes <small style="color:var(--text-muted);">(visible to admin for scheduling)</small></label>
            <textarea id="shift_notes" class="form-control" rows="3" placeholder="E.g. Prefer morning shifts on weekdays..."><?= e($profile['shift_notes'] ?? '') ?></textarea>
            <button class="adm-btn adm-btn-primary adm-btn-sm" style="margin-top:0.8rem;" onclick="saveShiftNotes()"><i class="fas fa-save"></i> Save Notes</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION H: ACCOUNT & SECURITY -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-h" class="profile-section" style="display:none;">
    <div class="info-card" style="margin-bottom:1.5rem;">
        <h3 style="margin-bottom:1.2rem;"><i class="fas fa-key" style="color:var(--role-accent);"></i> Change Password</h3>
        <div style="max-width:480px;display:grid;gap:1rem;">
            <div class="form-group"><label>Current Password</label><input type="password" id="cur_pass" class="form-control"></div>
            <div class="form-group"><label>New Password</label><input type="password" id="new_pass" class="form-control" oninput="checkPasswordStrength(this.value)">
                <div id="pass-strength-bar" style="height:6px;border-radius:3px;margin-top:6px;background:var(--surface-2);overflow:hidden;"><div id="pass-strength-fill" style="height:100%;width:0;transition:width 0.3s;"></div></div>
                <div id="pass-strength-label" style="font-size:0.8rem;color:var(--text-muted);margin-top:3px;"></div>
            </div>
            <div class="form-group"><label>Confirm New Password</label><input type="password" id="conf_pass" class="form-control"></div>
            <button class="adm-btn adm-btn-primary" onclick="changePassword()"><i class="fas fa-save"></i> Update Password</button>
        </div>
    </div>
    <div class="info-card" style="margin-bottom:1.5rem;">
        <h3 style="margin-bottom:1.2rem;"><i class="fas fa-laptop" style="color:var(--role-accent);"></i> Active Sessions</h3>
        <?php if(empty($sessions)): ?>
            <p style="color:var(--text-muted);">No recorded sessions.</p>
        <?php else: ?>
        <table class="adm-table" style="font-size:0.9rem;"><thead><tr><th>Device / Browser</th><th>IP Address</th><th>Login Time</th><th>Last Active</th><th>Action</th></tr></thead><tbody>
        <?php foreach($sessions as $s): ?>
        <tr>
            <td><?= e($s['browser'] ?? $s['device_info'] ?? 'Unknown') ?><?= $s['is_current'] ? ' <span class="adm-badge adm-badge-success" style="font-size:0.7em;">Current</span>' : '' ?></td>
            <td><?= e($s['ip_address'] ?? '—') ?></td>
            <td><?= date('d M, h:i A', strtotime($s['login_time'])) ?></td>
            <td><?= date('d M, h:i A', strtotime($s['last_active'])) ?></td>
            <td><?php if(!$s['is_current']): ?>
                <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="logoutSession(<?= $s['id'] ?>)"><i class="fas fa-sign-out-alt"></i></button>
            <?php else: echo '<span style="color:var(--text-muted);font-size:0.85em;">You</span>'; endif; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody></table>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION I: NOTIFICATION PREFERENCES -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-i" class="profile-section" style="display:none;">
    <div class="info-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fas fa-bell" style="color:var(--role-accent);"></i> Notification Preferences</h3>
            <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="saveNotificationSettings()"><i class="fas fa-save"></i> Save Preferences</button>
        </div>
        <?php
        $notif_toggles = [
            'notif_new_order'             => 'New lab test order received',
            'notif_urgent_order'          => 'Urgent / STAT order alerts',
            'notif_critical_value'        => 'Unacknowledged critical value reminders',
            'notif_equipment_due'         => 'Equipment calibration due alerts',
            'notif_reagent_low'           => 'Reagent low stock alerts',
            'notif_reagent_expiry'        => 'Reagent expiry alerts',
            'notif_result_amendment'      => 'Result amendment notifications',
            'notif_doctor_message'        => 'Doctor messages and clarification requests',
            'notif_qc_failure'            => 'Quality Control failure alerts',
            'notif_license_expiry'        => 'License or certification expiry warnings',
            'notif_shift_reminder'        => 'Shift schedule reminders',
            'notif_system_announcements'  => 'System announcements from admin',
        ];
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.8rem;margin-bottom:1.5rem;">
        <?php foreach($notif_toggles as $key => $label): $val = (int)($notif_settings[$key] ?? 1); ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.8rem 1rem;background:var(--surface-2);border-radius:6px;">
                <span style="font-size:0.9rem;"><?= $label ?></span>
                <label class="prof-toggle"><input type="checkbox" id="ns_<?= $key ?>" name="<?= $key ?>" value="1" <?= $val ? 'checked' : '' ?>>
                    <span class="prof-toggle-slider"></span>
                </label>
            </div>
        <?php endforeach; ?>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
            <div class="form-group"><label>Preferred Language</label>
                <select id="ns_lang" class="form-select">
                    <?php foreach(['English','French','Twi','Hausa','Ewe','Ga'] as $l): ?>
                    <option value="<?= $l ?>" <?= ($notif_settings['preferred_language'] ?? 'English') === $l ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Alert Sound</label>
                <label class="prof-toggle" style="margin-top:0.6rem;display:inline-flex;"><input type="checkbox" id="ns_sound" <?= ($notif_settings['alert_sound_enabled'] ?? 1) ? 'checked' : '' ?>>
                    <span class="prof-toggle-slider"></span>
                </label>
            </div>
            <div class="form-group"><label>Email Notifications</label>
                <label class="prof-toggle" style="margin-top:0.6rem;display:inline-flex;"><input type="checkbox" id="ns_email" <?= ($notif_settings['email_notifications'] ?? 1) ? 'checked' : '' ?>>
                    <span class="prof-toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION J: DOCUMENTS -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-j" class="profile-section" style="display:none;">
    <div class="info-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fas fa-folder-open" style="color:var(--role-accent);"></i> Professional Documents</h3>
            <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="$('#addDocModal').show()"><i class="fas fa-upload"></i> Upload Document</button>
        </div>
        <?php if(empty($documents)): ?>
            <p style="color:var(--text-muted);">No documents uploaded yet.</p>
        <?php else: ?>
        <table class="adm-table" style="font-size:0.95rem;"><thead><tr><th>File Name</th><th>Description</th><th>Type</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody>
        <?php foreach($documents as $doc): ?>
        <tr>
            <td><i class="fas fa-file-pdf" style="color:var(--danger);margin-right:0.4rem;"></i><strong><?= e($doc['file_name']) ?></strong></td>
            <td><?= e($doc['description'] ?? '—') ?></td>
            <td><?= e(explode('/', $doc['file_type'] ?? 'unknown')[1] ?? 'file') ?></td>
            <td><?= number_format($doc['file_size'] / 1024, 1) ?> KB</td>
            <td><?= date('d M Y', strtotime($doc['uploaded_at'])) ?></td>
            <td>
                <div class="action-btns">
                    <a href="/RMU-Medical-Management-System/php/api/download_doc.php?file=<?= urlencode($doc['file_path']) ?>" class="adm-btn adm-btn-sm" style="background:var(--surface-2);" target="_blank"><i class="fas fa-download"></i></a>
                    <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="deleteDocument(<?= $doc['id'] ?>)"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody></table>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION K: AUDIT TRAIL -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-k" class="profile-section" style="display:none;">
    <div class="info-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fas fa-history" style="color:var(--role-accent);"></i> My Audit Trail <small style="font-size:0.7em;color:var(--text-muted);font-weight:400;">(Read-only — Immutable)</small></h3>
            <a href="lab_exports.php?type=audit&tech_id=<?= $tech_pk ?>&csrf_token=<?= $csrf_token ?>" class="adm-btn adm-btn-sm" style="background:var(--surface-2);" target="_blank"><i class="fas fa-file-csv"></i> Export CSV</a>
        </div>
        <div class="adm-table-wrap">
        <table class="adm-table display" id="auditTable" style="font-size:0.9rem;">
            <thead><tr><th>Action</th><th>Module</th><th>Record</th><th>Timestamp</th><th>IP Address</th></tr></thead>
            <tbody>
            <?php foreach($audit_rows as $au): ?>
            <tr>
                <td><?= e($au['action_type']) ?></td>
                <td><?= e($au['module_affected'] ?? '—') ?></td>
                <td><?= e($au['record_id_affected'] ?? '—') ?></td>
                <td><?= date('d M Y, h:i A', strtotime($au['created_at'] ?? $au['timestamp'] ?? 'now')) ?></td>
                <td><?= e($au['ip_address'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECTION L: PROFILE COMPLETENESS ENGINE -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="sec-l" class="profile-section" style="display:none;">
    <div class="info-card">
        <h3 style="margin-bottom:1.5rem;"><i class="fas fa-tasks" style="color:var(--role-accent);"></i> Profile Completeness Checklist</h3>
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem;">
                <span style="font-size:0.9rem;color:var(--text-muted);">Overall Completion</span>
                <span style="font-weight:700;color:var(--role-accent);" id="big-pct"><?= $completeness_pct ?>%</span>
            </div>
            <div style="background:var(--surface-2);border-radius:6px;height:14px;overflow:hidden;">
                <div id="big-pct-bar" style="width:<?= $completeness_pct ?>%;height:100%;background:var(--role-accent);border-radius:6px;transition:width 0.6s;"></div>
            </div>
        </div>
        <?php
        $checks = [
            'personal_info_complete'       => ['Personal Information',              'sec-b', 'fas fa-address-card'],
            'professional_profile_complete' => ['Professional Profile',              'sec-c', 'fas fa-briefcase-medical'],
            'qualifications_complete'      => ['Qualifications & Certifications',   'sec-d', 'fas fa-graduation-cap'],
            'equipment_assigned'           => ['Equipment Assignment',              'sec-f', 'fas fa-microscope'],
            'shift_profile_complete'       => ['Shift Profile',                    'sec-g', 'fas fa-calendar-alt'],
            'photo_uploaded'               => ['Profile Photo',                    'sec-a', 'fas fa-camera'],
            'security_setup_complete'      => ['Notification Settings',            'sec-i', 'fas fa-bell'],
            'documents_uploaded'           => ['Documents Uploaded',               'sec-j', 'fas fa-folder-open'],
        ];
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.8rem;">
        <?php foreach($checks as $key => [$label, $target_sec, $icon]): $done = (bool)($completeness_row[$key] ?? 0); ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1.1rem;background:var(--surface-2);border-radius:8px;border-left:3px solid <?= $done ? 'var(--success)' : 'var(--warning)' ?>;">
            <div style="display:flex;align-items:center;gap:0.8rem;">
                <i class="fas <?= $icon ?>" style="color:<?= $done ? 'var(--success)' : 'var(--warning)' ?>;width:18px;"></i>
                <span style="font-size:0.9rem;"><?= $label ?></span>
            </div>
            <?php if($done): ?>
                <i class="fas fa-check-circle" style="color:var(--success);"></i>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="showSection('<?= $target_sec ?>', null)" style="font-size:0.8rem;color:var(--role-accent);font-weight:600;">Complete Now →</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

</div><!-- end #profileContent -->
</div><!-- end layout grid -->

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- MODALS -->
<!-- ══════════════════════════════════════════════════════════════════════ -->

<!-- Add Qualification Modal -->
<div id="addQualModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9000;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:var(--surface);border-radius:var(--radius-lg);padding:2rem;min-width:420px;max-width:560px;box-shadow:var(--shadow-lg);">
        <h4 style="margin:0 0 1.5rem;"><i class="fas fa-graduation-cap" style="color:var(--role-accent);"></i> Add Qualification</h4>
        <div class="form-group"><label>Degree / Certificate Name *</label><input type="text" id="q_degree" class="form-control" placeholder="e.g. BSc Medical Laboratory Science"></div>
        <div class="form-group"><label>Institution Name *</label><input type="text" id="q_inst" class="form-control"></div>
        <div class="form-group"><label>Year Awarded</label><input type="number" id="q_year" class="form-control" min="1970" max="<?= date('Y') ?>"></div>
        <div class="form-group"><label>Certificate Upload (PDF/JPG/PNG, max 5MB)</label><input type="file" id="q_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
        <div style="display:flex;gap:0.8rem;justify-content:flex-end;margin-top:1.5rem;">
            <button class="adm-btn adm-btn-sm" style="background:var(--surface-2);" onclick="$('#addQualModal').hide()">Cancel</button>
            <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="submitQualification()"><i class="fas fa-save"></i> Save Qualification</button>
        </div>
    </div>
</div>

<!-- Add Certification Modal -->
<div id="addCertModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9000;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:var(--surface);border-radius:var(--radius-lg);padding:2rem;min-width:420px;max-width:560px;box-shadow:var(--shadow-lg);">
        <h4 style="margin:0 0 1.5rem;"><i class="fas fa-certificate" style="color:var(--role-accent);"></i> Add Certification</h4>
        <div class="form-group"><label>Certification Name *</label><input type="text" id="c_name" class="form-control"></div>
        <div class="form-group"><label>Issuing Organization</label><input type="text" id="c_org" class="form-control"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group"><label>Issue Date</label><input type="date" id="c_issue" class="form-control"></div>
            <div class="form-group"><label>Expiry Date</label><input type="date" id="c_expiry" class="form-control"></div>
        </div>
        <div class="form-group"><label>Certificate Upload</label><input type="file" id="c_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
        <div style="display:flex;gap:0.8rem;justify-content:flex-end;margin-top:1.5rem;">
            <button class="adm-btn adm-btn-sm" style="background:var(--surface-2);" onclick="$('#addCertModal').hide()">Cancel</button>
            <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="submitCertification()"><i class="fas fa-save"></i> Save Certification</button>
        </div>
    </div>
</div>

<!-- Add Document Modal -->
<div id="addDocModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9000;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:var(--surface);border-radius:var(--radius-lg);padding:2rem;min-width:420px;max-width:560px;box-shadow:var(--shadow-lg);">
        <h4 style="margin:0 0 1.5rem;"><i class="fas fa-upload" style="color:var(--role-accent);"></i> Upload Document</h4>
        <div class="form-group"><label>Document File * (PDF, DOC, JPG, PNG — max 10MB)</label><input type="file" id="doc_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
        <div class="form-group"><label>Description / Label</label><input type="text" id="doc_desc" class="form-control" placeholder="e.g. Lab Technician License 2025"></div>
        <div style="display:flex;gap:0.8rem;justify-content:flex-end;margin-top:1.5rem;">
            <button class="adm-btn adm-btn-sm" style="background:var(--surface-2);" onclick="$('#addDocModal').hide()">Cancel</button>
            <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="submitDocument()"><i class="fas fa-upload"></i> Upload</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- CSS: Toggle Switches + Nav Active State -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<style>
.prof-toggle { position:relative; display:inline-block; width:46px; height:26px; }
.prof-toggle input { opacity:0; width:0; height:0; }
.prof-toggle-slider { position:absolute; cursor:pointer; inset:0; background:var(--surface-2); border-radius:26px; border:1px solid var(--border); transition:.3s; }
.prof-toggle input:checked + .prof-toggle-slider { background:var(--role-accent); border-color:var(--role-accent); }
.prof-toggle-slider:before { content:""; position:absolute; width:18px; height:18px; left:3px; bottom:3px; background:white; border-radius:50%; transition:.3s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
.prof-toggle input:checked + .prof-toggle-slider:before { transform:translateX(20px); }
.prof-nav-item:hover, .prof-nav-item.active { background:var(--role-accent-light); border-left-color:var(--role-accent) !important; color:var(--role-accent) !important; }
.prof-nav-item.active { font-weight:600; }
</style>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- JAVASCRIPT -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<script>
const PROF_ACTION = 'lab_profile_actions.php';
const CSRF = '<?= $csrf_token ?>';

function profToast(msg, ok=true) {
    const t = $('#profileToast');
    t.html(`<div style="padding:1rem 1.4rem;background:${ok?'var(--success)':'var(--danger)'};color:white;border-radius:8px;box-shadow:var(--shadow-lg);font-size:0.95rem;">${ok?'<i class="fas fa-check-circle"></i>':'<i class="fas fa-times-circle"></i>'} ${msg}</div>`);
    t.fadeIn(200);
    setTimeout(() => t.fadeOut(400), 3500);
}

function updateCompletenessUI(pct) {
    if (!pct && pct !== 0) return;
    $('#completeness-bar-side, #big-pct-bar').css('width', pct+'%');
    $('#completeness-pct-side').text(pct+'%');
    $('#big-pct').text(pct+'%');
    $('#completeness-label').text('Profile '+pct+'% Complete');
}

function showSection(id, el) {
    $('.profile-section').hide();
    $('#'+id).show();
    $('.prof-nav-item').removeClass('active');
    if(el) $(el).addClass('active');
    else $(`[onclick*="${id}"]`).addClass('active');
    if(id === 'sec-e') loadPerfStats();
    if(id === 'sec-k') { if(!$.fn.DataTable.isDataTable('#auditTable')) $('#auditTable').DataTable({pageLength:20,order:[[3,'desc']}); }
}

function uploadPhoto(input) {
    if (!input.files.length) return;
    const fd = new FormData();
    fd.append('photo', input.files[0]);
    fd.append('action', 'upload_profile_photo');
    fd.append('csrf_token', CSRF);
    $.ajax({ url: PROF_ACTION, type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
        success: r => {
            if(r.success) {
                $('#profile-photo-img').attr('src', '/RMU-Medical-Management-System/uploads/profiles/'+r.new_photo+'?t='+Date.now());
                updateCompletenessUI(r.completeness);
                profToast('Profile photo updated!');
            } else profToast(r.message, false);
        }
    });
}

function setAvailability(status) {
    $.post(PROF_ACTION, {action:'toggle_availability', status, csrf_token:CSRF}, r => {
        if(r.success) profToast('Status set to: '+status);
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
        if(r.success) { updateCompletenessUI(r.completeness); profToast(r.message); }
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
        if(r.success) { updateCompletenessUI(r.completeness); profToast(r.message); }
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
        success: r => { if(r.success){ profToast(r.message); setTimeout(()=>location.reload(),1200); } else profToast(r.message,false); }
    });
}

function deleteQualification(id) {
    if(!confirm('Remove this qualification?')) return;
    $.post(PROF_ACTION, {action:'delete_qualification', qual_id:id, csrf_token:CSRF}, r => {
        if(r.success){ profToast(r.message); setTimeout(()=>location.reload(),1200); } else profToast(r.message,false);
    }, 'json');
}

function submitCertification() {
    const fd = new FormData();
    fd.append('action','upload_certification'); fd.append('csrf_token',CSRF);
    fd.append('certification_name', $('#c_name').val()); fd.append('issuing_organization', $('#c_org').val());
    fd.append('issue_date', $('#c_issue').val()); fd.append('expiry_date', $('#c_expiry').val());
    if ($('#c_file')[0].files.length) fd.append('certificate', $('#c_file')[0].files[0]);
    $.ajax({ url:PROF_ACTION, type:'POST', data:fd, processData:false, contentType:false, dataType:'json',
        success: r => { if(r.success){ profToast(r.message); setTimeout(()=>location.reload(),1200); } else profToast(r.message,false); }
    });
}

function deleteCertification(id) {
    if(!confirm('Remove this certification?')) return;
    $.post(PROF_ACTION, {action:'delete_certification', cert_id:id, csrf_token:CSRF}, r => {
        if(r.success){ profToast(r.message); setTimeout(()=>location.reload(),1200); } else profToast(r.message,false);
    }, 'json');
}

function submitDocument() {
    if (!$('#doc_file')[0].files.length) { profToast('Please choose a file.', false); return; }
    const fd = new FormData();
    fd.append('action','upload_document'); fd.append('csrf_token',CSRF);
    fd.append('document', $('#doc_file')[0].files[0]);
    fd.append('description', $('#doc_desc').val());
    $.ajax({ url:PROF_ACTION, type:'POST', data:fd, processData:false, contentType:false, dataType:'json',
        success: r => {
            if(r.success){ profToast(r.message); updateCompletenessUI(r.completeness); setTimeout(()=>location.reload(),1200); }
            else profToast(r.message,false);
        }
    });
}

function deleteDocument(id) {
    if(!confirm('Permanently delete this document?')) return;
    $.post(PROF_ACTION, {action:'delete_document', doc_id:id, csrf_token:CSRF}, r => {
        if(r.success){ profToast(r.message); setTimeout(()=>location.reload(),1200); } else profToast(r.message,false);
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
        if(r.success){ updateCompletenessUI(r.completeness); profToast(r.message); }
        else profToast(r.message, false);
    }, 'json');
}

function changePassword() {
    $.post(PROF_ACTION, {action:'change_password', csrf_token:CSRF,
        current_password: $('#cur_pass').val(),
        new_password: $('#new_pass').val(),
        confirm_password: $('#conf_pass').val()
    }, r => {
        if(r.success){ profToast(r.message); setTimeout(()=>location.href='/RMU-Medical-Management-System/php/login.php',2000); }
        else profToast(r.message, false);
    }, 'json');
}

function checkPasswordStrength(val) {
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const labels = ['','Weak','Fair','Strong','Very Strong'];
    const colors = ['','var(--danger)','var(--warning)','var(--success)','#00b894'];
    $('#pass-strength-fill').css({width:(score*25)+'%', background: colors[score]});
    $('#pass-strength-label').css('color', colors[score]).text(labels[score]);
}

function logoutSession(id) {
    if(!confirm('Log out this device?')) return;
    $.post(PROF_ACTION, {action:'logout_session', session_id:id, csrf_token:CSRF}, r => {
        if(r.success){ profToast(r.message); setTimeout(()=>location.reload(),1200); }
        else profToast(r.message,false);
    }, 'json');
}

function saveShiftNotes() {
    // Reuses personal info action with shift_notes field
    $.post(PROF_ACTION, {action:'save_personal_info', csrf_token:CSRF,
        full_name: '<?= addslashes($profile['full_name']) ?>',
        shift_notes: $('#shift_notes').val()
    }, r => { profToast(r.success ? 'Shift notes saved.' : r.message, r.success); }, 'json');
}

function calcAge() {
    const dob = new Date($('#pi_dob').val());
    if (isNaN(dob)) return;
    const age = Math.floor((Date.now() - dob) / 31557600000);
    $('#pi_age').val(age + ' years');
}

// Performance Stats (Section E)
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
            data: { labels: s.vol_labels, datasets:[{label:'Results',data:s.vol_values,backgroundColor:'rgba(13,148,136,0.7)',borderRadius:4}] },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
        });
        new Chart(document.getElementById('perfStatusChart').getContext('2d'), {
            type: 'doughnut',
            data: { labels:['Normal','Abnormal','Critical'], datasets:[{data:[s.results_total - s.critical_total - Math.floor(s.results_total*0.1), Math.floor(s.results_total*0.1), s.critical_total], backgroundColor:['#27ae60','#f39c12','#e74c3c'], borderWidth:0}] },
            options: { responsive:true, maintainAspectRatio:false, cutout:'70%' }
        });
        perfChartsInitialized = true;
    }, 'json');
}

// Completeness Donut (Section A)
document.addEventListener('DOMContentLoaded', function() {
    const pct = <?= $completeness_pct ?>;
    const ctx = document.getElementById('completenessDonut');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: { datasets:[{ data:[pct, 100-pct], backgroundColor:['var(--role-accent)','var(--surface-2)'], borderWidth:0 }] },
            options: { responsive:false, cutout:'78%', plugins:{legend:{display:false},tooltip:{enabled:false}},
                animation:{ animateRotate:true, duration:900 }}
        });
    }
});
</script>
