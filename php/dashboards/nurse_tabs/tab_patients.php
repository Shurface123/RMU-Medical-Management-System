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

    <!-- Patients List Card -->
    <div class="adm-card shadow-sm">
        <div class="adm-card-header" style="justify-content:space-between;">
            <h3 style="font-size:1.5rem; font-weight:700;"><i class="fas fa-user-injured text-primary"></i> Ward Census</h3>
            <span class="adm-badge adm-badge-info"><?= count($patients) ?> ACTIVE PATIENTS</span>
        </div>
        <div class="adm-card-body" style="padding:0;">
            <div class="adm-table-wrap">
                <table class="adm-table" id="patientsTable">
                    <thead>
                        <tr>
                            <th>Patient Identity</th>
                            <th>Demographics</th>
                            <th>Location/Unit</th>
                            <th>Medical Flags</th>
                            <th>Last Vitals Status</th>
                            <th style="text-align:right;">Care Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($patients)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:5rem 2rem; color:var(--text-muted);">
                                    <div style="opacity:0.2; margin-bottom:1.5rem;">
                                        <i class="fas fa-bed" style="font-size:4rem;"></i>
                                    </div>
                                    <h5 style="font-weight:600; font-size:1.4rem;">No Patients Assigned</h5>
                                    <p style="font-size:1.2rem;">Currently no occupied beds found in <?= e($ward_assigned) ?>.</p>
                                </td>
                            </tr>
                        <?php else: foreach($patients as $p): ?>
                            <tr style="transition:all .2s ease;">
                                <td>
                                    <div style="font-family:monospace; font-weight:700; color:var(--text-primary); letter-spacing:.02em; font-size:1.3rem;">#<?= e($p['patient_id']) ?></div>
                                    <small style="color:var(--text-muted); text-transform:uppercase; font-size:1.05rem; font-weight:600;">Reg Code: EX-<?= rand(100,999) ?></small>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:1.2rem;">
                                        <div style="width:42px; height:42px; border-radius:12px; background:<?= $p['gender']=='Male' ? 'rgba(52, 152, 219, 0.1)' : 'rgba(231, 76, 60, 0.1)' ?>; color:<?= $p['gender']=='Male' ? 'var(--primary)' : 'var(--danger)' ?>; display:flex; align-items:center; justify-content:center; font-size:1.6rem; font-weight:800; border:1px solid rgba(0,0,0,0.05); flex-shrink:0;">
                                            <?= strtoupper(substr($p['patient_name'],0,1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:700; font-size:1.45rem; color:var(--text-primary); margin-bottom:.1rem;"><?= e($p['patient_name']) ?></div>
                                            <div style="display:flex; align-items:center; gap:.6rem; font-size:1.15rem; color:var(--text-muted); font-weight:500;">
                                                <i class="fas <?= $p['gender']=='Male'?'fa-mars':'fa-venus' ?>"></i> <?= e($p['gender']) ?> · <i class="fas fa-hourglass-half"></i> <?= $p['age'] ?>y
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:.3rem;">
                                        <span class="adm-badge adm-badge-primary" style="font-size:1.1rem; padding:.3rem .8rem;"><i class="fas fa-door-open"></i> <?= e($p['ward']) ?></span>
                                        <span class="adm-badge border" style="font-size:1.1rem; padding:.3rem .8rem;"><i class="fas fa-bed"></i> Bed <?= e($p['bed_number']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; flex-wrap:wrap; gap:.5rem;">
                                        <?php if(!empty($p['allergies'])): ?>
                                            <span class="adm-badge adm-badge-danger" title="<?= e($p['allergies']) ?>" style="cursor:help; font-weight:700;"><i class="fas fa-skull-crossbones"></i> ALLERGY</span>
                                        <?php endif; ?>
                                        <span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); font-weight:700;"><i class="fas fa-tint"></i> <?= e($p['blood_group'] ?: 'N/A') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if($p['last_vital_time']): ?>
                                        <div style="font-size:1.25rem; font-weight:700; color:var(--text-primary); margin-bottom:.4rem;"><?= date('H:i', strtotime($p['last_vital_time'])) ?> <small style="font-weight:400; color:var(--text-muted);"><?= date('d M', strtotime($p['last_vital_time'])) ?></small></div>
                                        <?php if($p['last_vital_flagged']): ?>
                                            <span class="adm-badge adm-badge-danger pulse-fade" style="font-weight:700;"><i class="fas fa-heart-crack"></i> ABNORMAL</span>
                                        <?php else: ?>
                                            <span class="adm-badge adm-badge-success" style="font-weight:700;"><i class="fas fa-check-circle"></i> STABLE</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="adm-badge adm-badge-warning" style="font-weight:700; opacity:0.8;"><i class="fas fa-clock"></i> PENDING</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; gap:.6rem; justify-content:flex-end;">
                                        <button class="btn btn-primary" onclick="openVitalsModal(<?= $p['patient_pk'] ?>, '<?= e(addslashes($p['patient_name'])) ?>', '<?= e($p['patient_id']) ?>')" style="padding:.6rem 1.2rem; border-radius:10px; font-weight:700;"><span class="btn-text">
                                            <i class="fas fa-file-medical"></i> Log
                                        </span></button>
                                        <button class="btn btn-ghost" onclick="viewVitalsHistory(<?= $p['patient_pk'] ?>)" style="padding:.6rem 1rem; border-radius:10px;"><span class="btn-text">
                                            <i class="fas fa-history"></i>
                                        </span></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- MODAL: RECORD VITALS (Premium Design System)    -->
<!-- ═══════════════════════════════════════════════ -->
<div class="modal-bg" id="recordVitalsModal">
    <div class="modal-box" style="max-width:720px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        <div class="modal-header" style="background:var(--primary); padding:1.8rem 2.5rem;">
            <h3 style="color:#fff; font-size:1.8rem; font-weight:800; letter-spacing:-0.01em; margin:0;"><i class="fas fa-heartbeat" style="margin-right:.8rem;"></i> Vital Signs Entry</h3>
            <button class="btn btn-primary modal-close" onclick="closeVitalsModal()" type="button" style="color:#fff; opacity:0.8;"><span class="btn-text">×</span></button>
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

                <!-- Input Groups -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                    <!-- Blood Pressure -->
                    <div style="background:rgba(52,152,219,0.03); border:1px solid var(--border); border-radius:12px; padding:1.5rem;">
                        <label style="display:block; font-size:1.15rem; font-weight:800; color:var(--text-secondary); margin-bottom:1rem; text-transform:uppercase; letter-spacing:.05em;"><i class="fas fa-gauge-high text-primary"></i> Blood Pressure</label>
                        <div style="display:flex; align-items:center; gap:1rem;">
                            <input type="number" class="form-control" name="bp_systolic" placeholder="Sys" style="text-align:center; font-weight:700; font-size:1.5rem;">
                            <span style="font-size:1.8rem; color:var(--border); font-weight:300;">/</span>
                            <input type="number" class="form-control" name="bp_diastolic" placeholder="Dia" style="text-align:center; font-weight:700; font-size:1.5rem;">
                        </div>
                        <small style="display:block; text-align:center; color:var(--text-muted); margin-top:.5rem;">mmHg (Systolic / Diastolic)</small>
                    </div>

                    <!-- Heart & Oxygen -->
                    <div style="background:rgba(231,76,60,0.03); border:1px solid var(--border); border-radius:12px; padding:1.5rem;">
                        <label style="display:block; font-size:1.15rem; font-weight:800; color:var(--text-secondary); margin-bottom:1rem; text-transform:uppercase; letter-spacing:.05em;"><i class="fas fa-wave-square text-danger"></i> Pulse &amp; O2</label>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div class="input-group">
                                <span class="input-group-text" style="background:none; border-right:none;"><i class="fas fa-heartbeat text-danger"></i></span>
                                <input type="number" class="form-control" name="pulse_rate" placeholder="BPM" style="border-left:none; font-weight:700;">
                            </div>
                            <div class="input-group">
                                <span class="input-group-text" style="background:none; border-right:none;"><i class="fas fa-lungs text-info"></i></span>
                                <input type="number" class="form-control" name="oxygen_saturation" placeholder="SpO2%" style="border-left:none; font-weight:700;">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                    <div class="form-group">
                        <label><i class="fas fa-thermometer-half text-warning"></i> Temp (°C)</label>
                        <input type="number" step="0.1" class="form-control" name="temperature" placeholder="37.0" style="font-weight:700;">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-wind text-secondary"></i> Resp. Rate</label>
                        <input type="number" class="form-control" name="respiratory_rate" placeholder="16" style="font-weight:700;">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tint text-primary"></i> Glucose</label>
                        <input type="number" step="0.1" class="form-control" name="blood_glucose" placeholder="95" style="font-weight:700;">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                    <div class="form-group">
                        <label><i class="fas fa-weight"></i> Weight (kg)</label>
                        <input type="number" step="0.1" class="form-control calc-bmi" id="calc_weight" name="weight" placeholder="0.0">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-ruler-vertical"></i> Height (cm)</label>
                        <input type="number" step="0.1" class="form-control calc-bmi" id="calc_height" name="height" placeholder="0.0">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calculator text-primary"></i> Calculated BMI</label>
                        <input type="number" step="0.1" class="form-control" id="calc_bmi" name="bmi" readonly style="background:var(--surface-2); font-weight:700; color:var(--primary);">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-comment-medical text-info"></i> Clinical Observations / Notes</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Describe patient's general appearance, complaints, or any specific concerns..."></textarea>
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
function openVitalsModal(patientPk, patientName, patientId) {
    document.getElementById('vitals_patient_id').value = patientPk;
    document.getElementById('vitals_patient_name_display').textContent = patientName;
    document.getElementById('vitals_patient_id_display').textContent = patientId;
    document.getElementById('vitals_avatar_init').textContent = patientName.charAt(0).toUpperCase();
    document.getElementById('recordVitalsForm').reset();
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
                    setTimeout(() => location.reload(), 1500);
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

