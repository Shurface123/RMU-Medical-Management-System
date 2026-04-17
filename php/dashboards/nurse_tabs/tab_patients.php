<?php
// ============================================================
// NURSE DASHBOARD - PATIENTS & VITALS (MODULE 2)
// ============================================================
if (!isset($conn)) exit;

$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'All Wards';

$patients = [];
$q_str = "
    SELECT 
        p.id AS patient_pk, p.patient_id, p.blood_group, p.allergies, p.chronic_conditions,
        u.name AS patient_name, u.gender, u.date_of_birth, u.phone,
        b.ward, b.bed_number,
        (SELECT recorded_at FROM patient_vitals WHERE patient_id = p.id ORDER BY recorded_at DESC LIMIT 1) as last_vital_time,
        (SELECT is_flagged FROM patient_vitals WHERE patient_id = p.id ORDER BY recorded_at DESC LIMIT 1) as last_vital_flagged
    FROM patients p
    JOIN users u ON p.user_id = u.id
    JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status = 'Occupied'
    JOIN beds b ON ba.bed_id = b.id
";
if ($ward_assigned !== 'All Wards' && $ward_assigned !== 'Not Assigned') {
    $q_str .= " WHERE b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."'";
}
$q_str .= " ORDER BY b.ward ASC, b.bed_number ASC";
$q = mysqli_query($conn, $q_str);
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $age = 'N/A';
        if (!empty($r['date_of_birth'])) {
            $dob = new DateTime($r['date_of_birth']);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
        }
        $r['age'] = $age;
        $patients[] = $r;
    }
}
?>

<div class="tab-content active" id="patients">

    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.2rem; font-weight:800; color:var(--text-primary); margin-bottom:.3rem;"><i class="fas fa-heartbeat text-danger"></i> Patient Status &amp; Vitals</h2>
            <p style="font-size:1.25rem; color:var(--text-muted);">Real-time physiological tracking for assigned ward patients.</p>
        </div>
        <div style="display:flex; gap:1rem; align-items:center;">
            <div style="background:var(--surface-2); padding:.8rem 1.5rem; border-radius:12px; border:1px solid var(--border); display:flex; align-items:center; gap:.8rem;">
                <i class="fas fa-hospital-alt text-primary"></i>
                <span style="font-size:1.2rem; font-weight:600; color:var(--text-secondary);">Active Ward: <strong style="color:var(--text-primary);"><?= e($ward_assigned) ?></strong></span>
            </div>
            <button class="btn btn-ghost btn-sm" onclick="location.reload();" style="border-radius:12px;"><span class="btn-text">
                <i class="fas fa-sync-alt"></i>
            </span></button>
        </div>
    </div>

    <!-- Patient Census: Advanced rec-card2 Accordion Layout -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <span class="adm-badge adm-badge-info" style="font-size:1.2rem;padding:.5rem 1.2rem;"><i class="fas fa-procedures"></i> <?= count($patients) ?> Active Patients</span>
        </div>
        <div style="display:flex;gap:.8rem;">
            <input type="text" id="patSearch" class="form-control" style="max-width:220px;" placeholder="Search patient..." oninput="filterPatients()">
        </div>
    </div>
    <?php if(empty($patients)): ?>
    <div class="adm-card" style="text-align:center;padding:5rem;">
        <i class="fas fa-bed" style="font-size:3.5rem;opacity:.2;display:block;margin-bottom:1rem;"></i>
        <h3 style="color:var(--text-muted);">No Patients Assigned</h3>
        <p style="color:var(--text-muted);font-size:1.3rem;margin-top:.5rem;">Currently no occupied beds found in <?= e($ward_assigned) ?>.</p>
    </div>
    <?php else: foreach($patients as $p):
        $is_male  = strtolower($p['gender'] ?? '') === 'male';
        $av_bg    = $is_male ? 'linear-gradient(135deg,#2F80ED,#56CCF2)' : 'linear-gradient(135deg,#FF6B6B,#FF8E53)';
        $av_icon  = $is_male ? 'fa-mars' : 'fa-venus';
        $vt_color = $p['last_vital_flagged'] ? 'var(--danger)' : 'var(--success)';
        $vt_label = !$p['last_vital_time'] ? 'Pending' : ($p['last_vital_flagged'] ? 'Abnormal' : 'Stable');
        $vt_badge = !$p['last_vital_time'] ? 'warning' : ($p['last_vital_flagged'] ? 'danger' : 'success');
    ?>
    <div class="rec-card2 pat-row" data-name="<?= strtolower(e($p['patient_name'])) ?>" data-ward="<?= strtolower(e($p['ward'])) ?>">
        <!-- Clickable Header -->
        <div class="rec-card2-header" onclick="togglePatientCard(<?= $p['patient_pk'] ?>)">
            <div style="flex:1;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
                <!-- Gender Gradient Avatar -->
                <div style="width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:<?= $av_bg ?>;color:#fff;font-size:2rem;flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                    <i class="fas <?= $av_icon ?>"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:800;color:var(--text-primary);margin-bottom:.3rem;"><?= e($p['patient_name']) ?></div>
                    <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                        <span class="rec-diag-chip" style="font-size:1.05rem;padding:.2rem .8rem;"><i class="fas fa-id-badge"></i> <?= e($p['patient_id']) ?></span>
                        <span class="adm-badge adm-badge-primary"><i class="fas fa-door-open"></i> <?= e($p['ward']) ?></span>
                        <span class="adm-badge" style="background:var(--surface-2);border:1px solid var(--border);"><i class="fas fa-bed"></i> Bed <?= e($p['bed_number']) ?></span>
                        <?php if(!empty($p['allergies'])): ?><span class="adm-badge adm-badge-danger" title="<?= e($p['allergies']) ?>" style="cursor:help;"><i class="fas fa-skull-crossbones"></i> ALLERGY</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Right: Vitals Status + Chevron -->
            <div style="display:flex;align-items:center;gap:1.2rem;flex-shrink:0;">
                <div style="text-align:right;">
                    <span class="adm-badge adm-badge-<?= $vt_badge ?>" style="font-size:1.1rem;font-weight:700;">
                        <?= $p['last_vital_time'] ? '<i class="fas fa-circle" style="font-size:.6rem;margin-right:.4rem;"></i>' : '<i class="fas fa-clock" style="margin-right:.4rem;"></i>' ?>
                        <?= $vt_label ?>
                    </span>
                    <?php if($p['last_vital_time']): ?><div style="font-size:1.05rem;color:var(--text-muted);margin-top:.3rem;"><?= date('d M, H:i', strtotime($p['last_vital_time'])) ?></div><?php endif; ?>
                </div>
                <i class="fas fa-chevron-down" id="patChev-<?= $p['patient_pk'] ?>" style="color:var(--text-muted);transition:transform .25s;"></i>
            </div>
        </div>

        <!-- Expandable Detail Body -->
        <div class="rec-expand-body" id="patExpand-<?= $p['patient_pk'] ?>">
            <div class="rec-field-grid">
                <div class="rec-field">
                    <div class="rec-field-label"><i class="fas fa-user" style="color:var(--primary);margin-right:.4rem;"></i>Demographics</div>
                    <div class="rec-field-value">
                        <span class="vital-chip"><i class="fas <?= $av_icon ?>" style="color:<?= $is_male ? 'var(--primary)' : 'var(--danger)' ?>;"></i> <?= e($p['gender']) ?></span>
                        <span class="vital-chip"><i class="fas fa-hourglass-half" style="color:var(--warning);"></i> <?= $p['age'] ?> yrs</span>
                        <?php if(!empty($p['phone'])): ?><span class="vital-chip"><i class="fas fa-phone" style="color:var(--success);"></i> <?= e($p['phone']) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="rec-field">
                    <div class="rec-field-label"><i class="fas fa-tint" style="color:var(--danger);margin-right:.4rem;"></i>Medical Flags</div>
                    <div class="rec-field-value">
                        <span class="vital-chip"><i class="fas fa-tint" style="color:var(--danger);"></i> <?= e($p['blood_group'] ?: 'N/A') ?></span>
                        <?php if(!empty($p['allergies'])): ?><span class="vital-chip" style="border-color:var(--danger);color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> <?= e($p['allergies']) ?></span><?php endif; ?>
                    </div>
                </div>
                <?php if(!empty($p['chronic_conditions'])): ?>
                <div class="rec-field">
                    <div class="rec-field-label"><i class="fas fa-lungs" style="color:var(--warning);margin-right:.4rem;"></i>Chronic Conditions</div>
                    <div class="rec-field-value" style="font-size:1.2rem;"><?= e($p['chronic_conditions']) ?></div>
                </div>
                <?php endif; ?>
                <div class="rec-field">
                    <div class="rec-field-label"><i class="fas fa-heartbeat" style="color:var(--danger);margin-right:.4rem;"></i>Vitals Status</div>
                    <div class="rec-field-value">
                        <?php if($p['last_vital_time']): ?>
                        <span class="vital-chip" style="border-color:<?= $vt_color ?>;color:<?= $vt_color ?>;font-weight:700;">
                            <i class="fas <?= $p['last_vital_flagged'] ? 'fa-heart-crack' : 'fa-check-circle' ?>"></i>
                            <?= $vt_label ?> Â· <?= date('d M, H:i', strtotime($p['last_vital_time'])) ?>
                        </span>
                        <?php else: ?>
                        <span class="vital-chip" style="border-color:var(--warning);color:var(--warning);"><i class="fas fa-clock"></i> No vitals recorded yet</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Actions -->
            <div style="display:flex;gap:.8rem;margin-top:1.5rem;padding-top:1.2rem;border-top:1px solid var(--border);flex-wrap:wrap;">
                <button class="btn btn-primary btn-sm" onclick="openVitalsModal(<?= $p['patient_pk'] ?>, '<?= e(addslashes($p['patient_name'])) ?>', '<?= e($p['patient_id']) ?>')">
                    <span class="btn-text"><i class="fas fa-file-medical"></i> Log Vitals</span>
                </button>
                <button class="btn btn-ghost btn-sm" onclick="viewVitalsHistory(<?= $p['patient_pk'] ?>)">
                    <span class="btn-text"><i class="fas fa-history"></i> Vitals History</span>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>

<script>
function togglePatientCard(pid) {
    const body = document.getElementById('patExpand-' + pid);
    const chev = document.getElementById('patChev-' + pid);
    const isOpen = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    if (chev) chev.style.transform = isOpen ? '' : 'rotate(180deg)';
}
function filterPatients() {
    const q = (document.getElementById('patSearch').value || '').toLowerCase();
    document.querySelectorAll('.pat-row').forEach(r => {
        const name = r.dataset.name || '';
        const ward = r.dataset.ward || '';
        r.style.display = (!q || name.includes(q) || ward.includes(q)) ? '' : 'none';
    });
}
</script>
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="modal-bg" id="recordVitalsModal">
    <div class="modal-box" style="max-width:750px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); overflow:hidden;">
        <div class="modal-header" style="background:var(--primary); padding:1.8rem 2.5rem;">
            <h3 style="color:#fff; font-size:1.8rem; font-weight:800; letter-spacing:-0.01em; margin:0;"><i class="fas fa-heartbeat text-danger" style="margin-right:.8rem;"></i> Vital Signs Entry</h3>
            <button class="btn btn-primary modal-close" onclick="closeVitalsModal()" type="button" style="color:#fff; opacity:0.8; background:rgba(255,255,255,0.2);"><span class="btn-text">Ã—</span></button>
        </div>

        <div style="padding:2.5rem;">
            <!-- Patient Context Banner -->
            <div style="background:var(--surface-2); border:1px solid var(--border); border-radius:15px; padding:1.5rem 2rem; margin-bottom:2.5rem; display:flex; align-items:center; gap:1.5rem;">
                <div style="width:50px; height:50px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.8rem; font-weight:800;">
                    <span id="vitals_avatar_init">P</span>
                </div>
                <div style="flex:1;">
                    <div style="display:flex; align-items:center; gap:.8rem;">
                        <strong id="vitals_patient_name_display" style="font-size:1.6rem; color:var(--text-primary);">Patient Name</strong>
                        <span class="adm-badge adm-badge-ghost" id="vitals_patient_id_display" style="font-family:monospace; font-weight:700;">PAT-000</span>
                    </div>
                    <small style="color:var(--text-muted); font-size:1.15rem; font-weight:600; text-transform:uppercase; letter-spacing:.02em;">Currently Observing Physiological Parameters</small>
                </div>
            </div>

            <form id="recordVitalsForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="record_vitals">
                <input type="hidden" name="patient_id" id="vitals_patient_id">

                <!-- UI Tab Navigation -->
                <div style="display:flex; border-bottom:2px solid var(--border); margin-bottom:2rem; gap:1.5rem;">
                    <div class="v-tab active" onclick="switchVTab('basic')" id="vTab_basic" style="padding:1rem 1.5rem; font-weight:700; color:var(--primary); border-bottom:3px solid var(--primary); cursor:pointer; font-size:1.2rem;"><i class="fas fa-stethoscope"></i> Basic Signs</div>
                    <div class="v-tab" onclick="switchVTab('advanced')" id="vTab_advanced" style="padding:1rem 1.5rem; font-weight:600; color:var(--text-muted); cursor:pointer; font-size:1.2rem;"><i class="fas fa-vial"></i> Advanced Details</div>
                    <div class="v-tab" onclick="switchVTab('notes')" id="vTab_notes" style="padding:1rem 1.5rem; font-weight:600; color:var(--text-muted); cursor:pointer; font-size:1.2rem;"><i class="fas fa-comment-medical"></i> Clinical Notes</div>
                </div>

                <!-- Tab 1: Basic Signs -->
                <div id="v_content_basic" style="display:block;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                        <!-- Blood Pressure -->
                        <div style="background:rgba(52,152,219,0.03); border:1px solid var(--border); border-radius:12px; padding:1.8rem;">
                            <label style="display:block; font-size:1.15rem; font-weight:800; color:var(--text-secondary); margin-bottom:1.5rem; text-transform:uppercase; letter-spacing:.05em;"><i class="fas fa-gauge-high text-primary"></i> Blood Pressure</label>
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <input type="number" class="form-control form-control-lg" name="bp_systolic" placeholder="Sys" style="text-align:center; font-weight:700; font-size:1.6rem; padding:1.2rem;">
                                <span style="font-size:2rem; color:var(--border); font-weight:300;">/</span>
                                <input type="number" class="form-control form-control-lg" name="bp_diastolic" placeholder="Dia" style="text-align:center; font-weight:700; font-size:1.6rem; padding:1.2rem;">
                            </div>
                            <small style="display:block; text-align:center; color:var(--text-muted); margin-top:.8rem; font-weight:500;">mmHg (Systolic / Diastolic)</small>
                        </div>

                        <!-- Heart & Oxygen -->
                        <div style="background:rgba(231,76,60,0.03); border:1px solid var(--border); border-radius:12px; padding:1.8rem;">
                            <label style="display:block; font-size:1.15rem; font-weight:800; color:var(--text-secondary); margin-bottom:1.5rem; text-transform:uppercase; letter-spacing:.05em;"><i class="fas fa-wave-square text-danger"></i> Pulse &amp; O2</label>
                            <div style="display:grid; gap:1.2rem;">
                                <div class="input-group">
                                    <span class="input-group-text" style="background:#fff; border-right:none; font-size:1.4rem; padding:0 1.2rem;"><i class="fas fa-heartbeat text-danger"></i></span>
                                    <input type="number" class="form-control form-control-lg" name="pulse_rate" placeholder="Heart Rate (BPM)" style="border-left:none; font-weight:700; font-size:1.4rem;">
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text" style="background:#fff; border-right:none; font-size:1.4rem; padding:0 1.2rem;"><i class="fas fa-lungs text-info"></i></span>
                                    <input type="number" class="form-control form-control-lg" name="oxygen_saturation" placeholder="SpO2 (%)" style="border-left:none; font-weight:700; font-size:1.4rem;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Advanced Details -->
                <div id="v_content_advanced" style="display:none; animation:fadeIn .3s;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                        <div class="form-group">
                            <label><i class="fas fa-thermometer-half text-warning"></i> Temperature (Â°C)</label>
                            <input type="number" step="0.1" class="form-control form-control-lg" name="temperature" placeholder="37.0" style="font-weight:700;">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-wind text-secondary"></i> Respiratory Rate</label>
                            <input type="number" class="form-control form-control-lg" name="respiratory_rate" placeholder="16" style="font-weight:700;">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.5rem; margin-bottom:1rem; padding:1.5rem; background:var(--surface-2); border-radius:12px; border:1px solid var(--border);">
                        <div class="form-group" style="margin:0;">
                            <label><i class="fas fa-weight"></i> Weight (kg)</label>
                            <input type="number" step="0.1" class="form-control calc-bmi" id="calc_weight" name="weight" placeholder="0.0">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label><i class="fas fa-ruler-vertical"></i> Height (cm)</label>
                            <input type="number" step="0.1" class="form-control calc-bmi" id="calc_height" name="height" placeholder="0.0">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label><i class="fas fa-calculator text-primary"></i> Calc. BMI</label>
                            <input type="number" step="0.1" class="form-control" id="calc_bmi" name="bmi" readonly style="background:rgba(var(--primary-rgb),0.1); font-weight:800; color:var(--primary); border-color:rgba(var(--primary-rgb),0.3);">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tint text-primary"></i> Blood Glucose (mg/dL)</label>
                        <input type="number" step="0.1" class="form-control form-control-lg" name="blood_glucose" placeholder="95" style="font-weight:700;">
                    </div>
                </div>

                <!-- Tab 3: Notes -->
                <div id="v_content_notes" style="display:none; animation:fadeIn .3s;">
                    <div class="form-group">
                        <label style="font-size:1.3rem; margin-bottom:1rem;"><i class="fas fa-comment-medical text-info"></i> Clinical Observations &amp; Additional Notes</label>
                        <textarea class="form-control" name="notes" rows="6" placeholder="Describe patient's general appearance, physical complaints, pain levels, or specific concerns..." style="font-size:1.3rem; padding:1.5rem; border-radius:12px; background:var(--surface-2);"></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; margin-top:2.5rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-ghost" onclick="closeVitalsModal()" style="font-weight:600;"><span class="btn-text">Cancel</span></button>
                    <button type="submit" class="btn btn-primary" id="btnSaveVitals" style="padding:.8rem 2.5rem; font-weight:700; border-radius:12px; box-shadow:0 4px 12px rgba(var(--primary-rgb), 0.3);"><span class="btn-text">
                        <i class="fas fa-check-circle"></i> Authenticate &amp; Save
                    </span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function switchVTab(tabStr) {
    document.querySelectorAll('.v-tab').forEach(el => {
        el.style.borderBottom = 'none';
        el.style.color = 'var(--text-muted)';
        el.style.fontWeight = '600';
    });
    document.getElementById('vTab_'+tabStr).style.borderBottom = '3px solid var(--primary)';
    document.getElementById('vTab_'+tabStr).style.color = 'var(--primary)';
    document.getElementById('vTab_'+tabStr).style.fontWeight = '700';
    
    document.getElementById('v_content_basic').style.display = 'none';
    document.getElementById('v_content_advanced').style.display = 'none';
    document.getElementById('v_content_notes').style.display = 'none';
    
    document.getElementById('v_content_'+tabStr).style.display = 'block';
}

function openVitalsModal(patientPk, patientName, patientId) {
    document.getElementById('vitals_patient_id').value = patientPk;
    document.getElementById('vitals_patient_name_display').textContent = patientName;
    document.getElementById('vitals_patient_id_display').textContent = patientId;
    document.getElementById('vitals_avatar_init').textContent = patientName.charAt(0).toUpperCase();
    document.getElementById('recordVitalsForm').reset();
    switchVTab('basic');
    document.getElementById('recordVitalsModal').style.display = 'flex';
}
function closeVitalsModal() {
    document.getElementById('recordVitalsModal').style.display = 'none';
}
document.querySelectorAll('.calc-bmi').forEach(input => {
    input.addEventListener('input', function() {
        const w = parseFloat(document.getElementById('calc_weight').value);
        const h = parseFloat(document.getElementById('calc_height').value);
        const bmiField = document.getElementById('calc_bmi');
        if (w > 0 && h > 0) {
            bmiField.value = (w / Math.pow(h/100, 2)).toFixed(1);
        } else { bmiField.value = ''; }
    });
});
function viewVitalsHistory(patientPk) {
    // Standardized Premium Alert
    Swal.fire({
        title: 'Vitals History',
        text: 'This feature is currently being integrated with the Analytics module. Please check the Analytics tab for historical trends.',
        icon: 'info',
        confirmButtonText: 'Understood',
        confirmButtonColor: 'var(--primary)'
    });
}
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#patientsTable').DataTable({
            pageLength: 10, ordering: true, order: [[4, 'desc']],
            language: { search: '', searchPlaceholder: 'Quick Search Patients...' }
        });
        $('.dataTables_filter input').addClass('form-control').css({'width':'250px','border-radius':'10px'});
    }
    $('#recordVitalsForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSaveVitals');
        btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        $.ajax({
            url: '../nurse/process_vitals.php',
            type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(res) {
                if(res.success) { 
                    Swal.fire({ icon: 'success', title: 'Saved!', text: 'Vital signs recorded successfully.', timer: 1500, showConfirmButton: false });
                    setTimeout(() => window.location.href = '?tab=patients', 1500);
                } else { 
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Failed to save vitals.' });
                    btn.html('<i class="fas fa-check-circle"></i> Authenticate &amp; Save').prop('disabled', false); 
                }
            },
            error: function() { 
                Swal.fire({ icon: 'error', title: 'System Error', text: 'Communication failure with the server.' });
                btn.html('<i class="fas fa-check-circle"></i> Authenticate &amp; Save').prop('disabled', false); 
            }
        });
    });
});
</script>

