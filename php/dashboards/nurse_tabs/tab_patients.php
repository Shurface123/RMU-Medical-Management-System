<?php
// ============================================================
// NURSE DASHBOARD - PATIENTS & VITALS (MODULE 2)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT & WARD ─────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'All Wards';

// ── FETCH PATIENTS IN WARD ───────────────────────────────────
$patients = [];
// Base query to get patients and their latest vitals
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
        // Calculate age
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

<div class="tab-content" id="patients">
    
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0"><i class="fas fa-procedures text-muted me-2"></i> Patients & Vitals</h4>
            <p class="text-muted mb-0">Showing patients currently allocated to <strong><?= e($ward_assigned) ?></strong></p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <!-- Filter toggles could go here -->
            <button class="btn btn-outline-primary" style="border-radius:20px;" onclick="location.reload();">
                <i class="fas fa-sync-alt"></i> Refresh List
            </button>
        </div>
    </div>

    <!-- Patients List -->
    <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="patientsTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Patient ID</th>
                            <th>Name & Details</th>
                            <th>Location</th>
                            <th>Medical Alerts</th>
                            <th>Last Vitals</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($patients)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No patients assigned to your current ward.</td></tr>
                        <?php else: foreach($patients as $p): ?>
                            <tr>
                                <td class="ps-4 font-weight-bold" style="color: var(--primary-dark);"><?= e($p['patient_id']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3 bg-<?= $p['gender']=='Male'?'primary':'danger' ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;opacity:0.8;">
                                            <?= strtoupper(substr($p['patient_name'],0,1)) ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?= e($p['patient_name']) ?></h6>
                                            <small class="text-muted"><?= e($p['gender']) ?>, <?= $p['age'] ?> yrs</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><i class="fas fa-map-marker-alt"></i> <?= e($p['ward']) ?> - Bed <?= e($p['bed_number']) ?></span>
                                </td>
                                <td>
                                    <?php if(!empty($p['allergies'])): ?>
                                        <span class="badge bg-danger mb-1" title="<?= e($p['allergies']) ?>"><i class="fas fa-exclamation-triangle"></i> Allergies</span>
                                    <?php endif; ?>
                                    <span class="badge" style="background-color:#9b59b6;"><i class="fas fa-tint"></i> <?= e($p['blood_group'] ?: 'Unknown') ?></span>
                                </td>
                                <td>
                                    <?php if($p['last_vital_time']): ?>
                                        <small class="d-block text-dark fw-bold"><?= date('d M, h:i A', strtotime($p['last_vital_time'])) ?></small>
                                        <?php if($p['last_vital_flagged']): ?>
                                            <span class="badge bg-danger rounded-pill"><i class="fas fa-flag"></i> Flagged</span>
                                        <?php else: ?>
                                            <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i> Normal</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Never Recorded</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill me-1" onclick="openVitalsModal(<?= $p['patient_pk'] ?>, '<?= e(addslashes($p['patient_name'])) ?>', '<?= e($p['patient_id']) ?>')" title="Record Vitals">
                                        <i class="fas fa-heartbeat"></i> 
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="viewVitalsHistory(<?= $p['patient_pk'] ?>)" title="View History Chart">
                                        <i class="fas fa-chart-line"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: RECORD VITALS                       -->
<!-- ========================================== -->
<div class="modal fade" id="recordVitalsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title"><i class="fas fa-heartbeat me-2"></i> Record Vitals</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="recordVitalsForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="record_vitals">
                <input type="hidden" name="patient_id" id="vitals_patient_id">
                
                <div class="modal-body p-4">
                    
                    <div class="alert alert-info" style="border-radius: 10px; border-left: 4px solid #17a2b8;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-circle fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold" id="vitals_patient_name_display">Patient Name</h6>
                                <small id="vitals_patient_id_display" class="font-monospace">PAT-XXXX</small>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Blood Pressure -->
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Blood Pressure (mmHg)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-stethoscope text-primary"></i></span>
                                <input type="number" class="form-control" name="bp_systolic" placeholder="Systolic (e.g. 120)" min="50" max="250">
                                <span class="input-group-text bg-light text-muted">/</span>
                                <input type="number" class="form-control" name="bp_diastolic" placeholder="Diastolic (e.g. 80)" min="30" max="150">
                            </div>
                        </div>
                        
                        <!-- Pulse -->
                        <div class="col-md-3">
                            <label class="form-label text-muted fw-bold small text-uppercase">Pulse (bpm)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-heartbeat text-danger"></i></span>
                                <input type="number" class="form-control" name="pulse_rate" placeholder="e.g. 75" min="30" max="250">
                            </div>
                        </div>
                        
                        <!-- SpO2 -->
                        <div class="col-md-3">
                            <label class="form-label text-muted fw-bold small text-uppercase">SpO2 (%)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lungs text-info"></i></span>
                                <input type="number" class="form-control" name="oxygen_saturation" placeholder="e.g. 98" min="50" max="100">
                            </div>
                        </div>

                        <!-- Temperature -->
                        <div class="col-md-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Temp (°C)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-thermometer-half text-warning"></i></span>
                                <input type="number" step="0.1" class="form-control" name="temperature" placeholder="e.g. 37.0" min="30" max="45">
                            </div>
                        </div>

                        <!-- Respiratory Rate -->
                        <div class="col-md-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Resp Rate</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-wind text-secondary"></i></span>
                                <input type="number" class="form-control" name="respiratory_rate" placeholder="breaths/min" min="5" max="60">
                            </div>
                        </div>

                        <!-- Blood Glucose -->
                        <div class="col-md-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Glucose (mg/dL)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-tint text-danger"></i></span>
                                <input type="number" step="0.1" class="form-control" name="blood_glucose" placeholder="e.g. 90.5" min="20" max="600">
                            </div>
                        </div>

                        <div class="col-12"><hr class="text-muted"></div>

                        <!-- Anthropometry -->
                        <div class="col-md-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Weight (kg)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" class="form-control calc-bmi" id="calc_weight" name="weight" placeholder="e.g. 70.5" min="1" max="300">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Height (cm)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" class="form-control calc-bmi" id="calc_height" name="height" placeholder="e.g. 175" min="30" max="250">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">BMI (Auto)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" class="form-control bg-light" id="calc_bmi" name="bmi" placeholder="Calculated" readonly>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12 mt-3">
                            <label class="form-label text-muted fw-bold small text-uppercase">Notes & Observations</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Any contextual notes for the doctor..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSaveVitals">
                        <i class="fas fa-save me-1"></i> Save Vitals
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Open Modal Flow
function openVitalsModal(patientPk, patientName, patientId) {
    document.getElementById('vitals_patient_id').value = patientPk;
    document.getElementById('vitals_patient_name_display').textContent = patientName;
    document.getElementById('vitals_patient_id_display').textContent = patientId;
    document.getElementById('recordVitalsForm').reset();
    
    var myModal = new bootstrap.Modal(document.getElementById('recordVitalsModal'));
    myModal.show();
}

// Auto-calculate BMI
document.querySelectorAll('.calc-bmi').forEach(input => {
    input.addEventListener('input', function() {
        let w = parseFloat(document.getElementById('calc_weight').value);
        let h = parseFloat(document.getElementById('calc_height').value);
        let bmiField = document.getElementById('calc_bmi');
        if (w > 0 && h > 0) {
            let h_meters = h / 100;
            let bmi = w / (h_meters * h_meters);
            bmiField.value = bmi.toFixed(1);
        } else {
            bmiField.value = '';
        }
    });
});

// View History Flow (Mockup for now)
function viewVitalsHistory(patientPk) {
    alert("History chart viewing for Patient ID " + patientPk + " will be wired into Module 11 Analytics or a dedicated modal.");
}

// AJAX Form Submission
$(document).ready(function() {
    $('#patientsTable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "order": [[2, "asc"]], // Ward
        "language": { "search": "", "searchPlaceholder": "Search patients..." }
    });

    $('#recordVitalsForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSaveVitals');
        btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        $.ajax({
            url: '../nurse/process_vitals.php', // the backend file we need to create
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#recordVitalsModal').modal('hide');
                    alert(res.message); // Replace with toast later
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.html('<i class="fas fa-save me-1"></i> Save Vitals').prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred while saving vitals.');
                btn.html('<i class="fas fa-save me-1"></i> Save Vitals').prop('disabled', false);
            }
        });
    });
});
</script>
