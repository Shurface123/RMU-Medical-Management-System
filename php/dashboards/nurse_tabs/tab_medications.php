<?php
// ============================================================
// NURSE DASHBOARD - MEDICATION ADMINISTRATION (MODULE 3)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT & WARD ─────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'All Wards';

// ── FETCH MEDS FOR TODAY ─────────────────────────────────────
// We fetch records from `medication_administration` for today 
// matching patients in the nurse's ward.
$meds = [];
$q_str = "
    SELECT 
        ma.id AS admin_pk, ma.admin_id, ma.medicine_name, ma.dosage, ma.route, 
        ma.scheduled_time, ma.administered_at, ma.status, ma.notes,
        p.patient_id, u.name AS patient_name, u.gender, u.date_of_birth,
        b.ward, b.bed_number,
        COALESCE(u.profile_photo, '') AS profile_photo
    FROM medication_administration ma
    JOIN patients p ON ma.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status = 'Occupied'
    LEFT JOIN beds b ON ba.bed_id = b.id
    WHERE DATE(ma.scheduled_time) = '$today'
";

if ($ward_assigned !== 'All Wards' && $ward_assigned !== 'Not Assigned') {
    $q_str .= " AND b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."'";
}
$q_str .= " ORDER BY ma.scheduled_time ASC";

$q = mysqli_query($conn, $q_str);
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        // Calculate age
        $r['age'] = 'N/A';
        if (!empty($r['date_of_birth'])) {
            $dob = new DateTime($r['date_of_birth']);
            $now = new DateTime();
            $r['age'] = $now->diff($dob)->y;
        }
        
        // Categorize overdueness
        $sched = new DateTime($r['scheduled_time']);
        $now = new DateTime();
        $is_overdue = ($r['status'] == 'Pending' && $now > $sched);
        $r['is_overdue'] = $is_overdue;
        
        $meds[] = $r;
    }
}
?>

<div class="tab-content" id="medications">

    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0"><i class="fas fa-pills text-muted me-2"></i> Medication Schedule</h4>
            <p class="text-muted mb-0">Today's schedule for patients in <strong><?= e($ward_assigned) ?></strong></p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-outline-primary" style="border-radius:20px;" onclick="location.reload();">
                <i class="fas fa-sync-alt"></i> Refresh Schedule
            </button>
        </div>
    </div>

    <!-- Stats Summary -->
    <?php
        $t_pending = array_reduce($meds, fn($c,$m) => $c + ($m['status']=='Pending'?1:0), 0);
        $t_admin   = array_reduce($meds, fn($c,$m) => $c + ($m['status']=='Administered'?1:0), 0);
        $t_missed  = array_reduce($meds, fn($c,$m) => $c + (in_array($m['status'],['Missed','Refused','Held'])?1:0), 0);
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-light border-0 shadow-sm" style="border-radius:10px; border-left: 4px solid var(--primary-color) !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Pending</h6>
                        <h3 class="mb-0 text-dark"><?= $t_pending ?></h3>
                    </div>
                    <div style="font-size:2rem; opacity:0.2;"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light border-0 shadow-sm" style="border-radius:10px; border-left: 4px solid #27ae60 !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Administered</h6>
                        <h3 class="mb-0 text-success"><?= $t_admin ?></h3>
                    </div>
                    <div style="font-size:2rem; opacity:0.2;"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light border-0 shadow-sm" style="border-radius:10px; border-left: 4px solid #e74c3c !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Missed/Held</h6>
                        <h3 class="mb-0 text-danger"><?= $t_missed ?></h3>
                    </div>
                    <div style="font-size:2rem; opacity:0.2;"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Medication List -->
    <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="medsTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Time</th>
                            <th>Patient</th>
                            <th>Medication (Route)</th>
                            <th>Dosage</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($meds)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No medications scheduled for today in this ward.</td></tr>
                        <?php else: foreach($meds as $m): ?>
                            <tr class="<?= $m['is_overdue'] ? 'bg-danger bg-opacity-10' : '' ?>">
                                <td class="ps-4">
                                    <span class="fw-bold <?= $m['is_overdue']?'text-danger':($m['status']=='Administered'?'text-success':'text-dark') ?>">
                                        <?= date('H:i', strtotime($m['scheduled_time'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3 bg-<?= $m['gender']=='Male'?'primary':'danger' ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width:35px;height:35px;opacity:0.8;font-size:0.9rem;">
                                            <?= strtoupper(substr($m['patient_name'],0,1)) ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold" style="font-size:0.95rem;"><?= e($m['patient_name']) ?></h6>
                                            <small class="text-muted"><?= e($m['ward']) ?>-B<?= e($m['bed_number']) ?> (<?= e($m['patient_id']) ?>)</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold" style="color:var(--primary-dark);"><?= e($m['medicine_name']) ?></div>
                                    <small class="text-muted"><i class="fas fa-route"></i> <?= e($m['route']) ?></small>
                                </td>
                                <td class="fw-bold text-dark"><?= e($m['dosage']) ?></td>
                                <td>
                                    <?php if($m['status'] == 'Pending'): ?>
                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fas fa-clock"></i> Pending</span>
                                    <?php elseif($m['status'] == 'Administered'): ?>
                                        <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-check"></i> Administered <br><small><?= date('H:i', strtotime($m['administered_at'])) ?></small></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fas fa-times"></i> <?= e($m['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($m['status'] == 'Pending'): ?>
                                        <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="openAdministerModal(<?= $m['admin_pk'] ?>, '<?= e(addslashes($m['patient_name'])) ?>', '<?= e(addslashes($m['medicine_name'])) ?>', '<?= e(addslashes($m['dosage'])) ?>', '<?= e(addslashes($m['route'])) ?>')">
                                            <i class="fas fa-syringe"></i> Verify
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" disabled>
                                            <i class="fas fa-lock"></i> Locked
                                        </button>
                                    <?php endif; ?>
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
<!-- MODAL: ADMINISTER MEDICATION               -->
<!-- ========================================== -->
<div class="modal fade" id="administerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title"><i class="fas fa-prescription-bottle-alt me-2"></i> Verify & Administer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="administerForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="administer_med">
                <input type="hidden" name="admin_id" id="med_admin_id">
                
                <div class="modal-body p-4">
                    
                    <div class="alert alert-warning mb-4" style="border-radius: 10px; border-left: 4px solid #f39c12;">
                        <h6 class="fw-bold mb-1"><i class="fas fa-exclamation-triangle"></i> Complete the 5 Rights</h6>
                        <small>Right Patient, Right Drug, Right Dose, Right Route, Right Time.</small>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Patient:</span>
                            <span class="fw-bold" id="med_patient_name"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Drug:</span>
                            <span class="fw-bold text-primary" id="med_drug_name"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Dosage:</span>
                            <span class="fw-bold" id="med_dosage"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Route:</span>
                            <span class="fw-bold" id="med_route"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Outcome Status</label>
                        <select class="form-select" name="med_status" id="med_status" required>
                            <option value="Administered">Administered</option>
                            <option value="Refused">Refused by Patient</option>
                            <option value="Held">Held (Doctor's Orders/Contraindicated)</option>
                            <option value="Missed">Missed/Unavailable</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Verification Method</label>
                        <select class="form-select" name="verification_method" required>
                            <option value="Manual">Manual Verification</option>
                            <option value="Barcode">Barcode Scanned</option>
                            <option value="eMAR">Double eMAR Check</option>
                        </select>
                    </div>

                    <div class="mb-2" id="med_notes_div">
                        <label class="form-label text-muted fw-bold small text-uppercase">Clinical Notes</label>
                        <textarea class="form-control" name="notes" id="med_notes" rows="2" placeholder="Required if refused or held..."></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnAdminSave">
                        <i class="fas fa-check-circle me-1"></i> Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAdministerModal(adminId, patientName, drugName, dosage, route) {
    document.getElementById('med_admin_id').value = adminId;
    document.getElementById('med_patient_name').textContent = patientName;
    document.getElementById('med_drug_name').textContent = drugName;
    document.getElementById('med_dosage').textContent = dosage;
    document.getElementById('med_route').textContent = route;
    
    document.getElementById('administerForm').reset();
    $('#med_status').trigger('change'); // trigger logic
    
    new bootstrap.Modal(document.getElementById('administerModal')).show();
}

$(document).ready(function() {
    $('#medsTable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "order": [[0, "asc"]], // Time
        "language": { "search": "", "searchPlaceholder": "Search schedule..." }
    });

    // Make notes required if not Administered
    $('#med_status').on('change', function() {
        if($(this).val() !== 'Administered') {
            $('#med_notes').prop('required', true);
            $('#med_notes_div label').html('Clinical Notes <span class="text-danger">*</span>');
        } else {
            $('#med_notes').prop('required', false);
            $('#med_notes_div label').html('Clinical Notes');
        }
    });

    $('#administerForm').on('submit', function(e) {
        e.preventDefault();
        if(!confirm("Are you sure you want to commit this medication record? This action cannot be undone.")) return;
        const btn = $('#btnAdminSave');
        btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        $.ajax({
            url: '../nurse/process_medication.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#administerModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.html('<i class="fas fa-check-circle me-1"></i> Confirm').prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred.');
                btn.html('<i class="fas fa-check-circle me-1"></i> Confirm').prop('disabled', false);
            }
        });
    });
});
</script>
