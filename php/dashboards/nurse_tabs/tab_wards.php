<?php
// ============================================================
// NURSE DASHBOARD - WARD & BEDS (MODULE 4)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT & WARD ─────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? null;

// Handle edge case where nurse has no ward assigned
if (!$ward_assigned || $ward_assigned === 'Not Assigned') {
    echo '<div class="tab-content" id="wards">
            <div class="row"><div class="col-12 text-center p-5">
                <i class="fas fa-bed text-muted" style="font-size: 4rem; margin-bottom: 20px;"></i>
                <h4 class="text-muted">No Ward Assigned</h4>
                <p>You cannot manage beds until a ward is assigned for your shift.</p>
            </div></div>
          </div>';
    return;
}

// ── FETCH BED DATA FOR ACTIVE WARD ───────────────────────────
$beds = [];
$q_beds = mysqli_query($conn, "
    SELECT 
        b.id AS bed_id, b.bed_number, b.status AS bed_status, b.bed_type, b.price_per_day,
        ba.id AS assignment_id, ba.admission_date, ba.expected_discharge_date,
        p.id AS patient_pk, p.patient_id, 
        u.name AS patient_name, u.gender,
        (SELECT COUNT(*) FROM isolation_records ir WHERE ir.patient_id = p.id AND ir.status = 'Active') AS is_isolated
    FROM beds b
    LEFT JOIN bed_assignments ba ON b.id = ba.bed_id AND ba.status = 'Occupied'
    LEFT JOIN patients p ON ba.patient_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."'
    ORDER BY b.bed_number ASC
");

if ($q_beds) {
    while ($r = mysqli_fetch_assoc($q_beds)) {
        $beds[] = $r;
    }
}

// ── FETCH AVAILABLE WARDS/BEDS FOR TRANSFER DROPDOWN ─────────
$available_beds = [];
$q_avail = mysqli_query($conn, "SELECT id, ward, bed_number FROM beds WHERE status='Available' ORDER BY ward, bed_number");
if ($q_avail) {
    while($r = mysqli_fetch_assoc($q_avail)) {
        $available_beds[$r['ward']][] = $r;
    }
}
?>

<div class="tab-content" id="wards">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0"><i class="fas fa-bed text-muted me-2"></i> Ward Management</h4>
            <p class="text-muted mb-0">Current Ward: <strong><?= e($ward_assigned) ?></strong></p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-outline-primary" style="border-radius:20px;" onclick="location.reload();">
                <i class="fas fa-sync-alt"></i> Refresh Grid
            </button>
        </div>
    </div>

    <!-- Bed Grid -->
    <div class="row g-4 mb-4">
        <?php foreach ($beds as $bed): ?>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100 <?= $bed['bed_status'] == 'Available' ? 'bg-light border-0' : ($bed['is_isolated'] > 0 ? 'border-danger p-0' : 'border-0 shadow-sm') ?>" 
                     style="border-radius: 15px; position:relative; overflow:hidden;">
                    
                    <?php if($bed['is_isolated'] > 0): ?>
                        <div class="bg-danger text-white text-center py-1 fw-bold" style="font-size: 0.8rem;">
                            <i class="fas fa-biohazard"></i> ISOLATION
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-bold border border-2 <?= $bed['bed_status'] == 'Available' ? 'border-secondary text-secondary' : 'border-primary text-primary' ?> rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <?= e($bed['bed_number']) ?>
                            </h5>
                            <?php if($bed['bed_status'] == 'Available'): ?>
                                <span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check-circle"></i> Available</span>
                            <?php else: ?>
                                <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="fas fa-user-injured"></i> Occupied</span>
                            <?php endif; ?>
                        </div>

                        <?php if($bed['bed_status'] == 'Occupied' && !empty($bed['patient_pk'])): ?>
                            <div class="patient-info mb-3">
                                <h6 class="mb-1 fw-bold"><?= e($bed['patient_name']) ?></h6>
                                <p class="text-muted mb-1" style="font-size: 0.85rem;">
                                    <i class="fas fa-id-card"></i> <?= e($bed['patient_id']) ?>
                                </p>
                                <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                    <i class="fas fa-calendar-check"></i> Adm: <?= date('d M', strtotime($bed['admission_date'])) ?>
                                </p>
                            </div>
                            
                            <hr class="text-muted my-2">
                            
                            <div class="d-flex gap-2 justify-content-between mt-3">
                                <?php if($bed['is_isolated'] == 0): ?>
                                    <button class="btn btn-sm btn-outline-danger w-100 rounded-pill" onclick="openIsolationModal(<?= $bed['patient_pk'] ?>, <?= $bed['bed_id'] ?>)">
                                        <i class="fas fa-biohazard"></i> Isolate
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary w-100 rounded-pill" onclick="openTransferModal(<?= $bed['patient_pk'] ?>, <?= $bed['bed_id'] ?>, '<?= e($bed['bed_number']) ?>')">
                                    <i class="fas fa-exchange-alt"></i> Transfer
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-bed" style="font-size: 2rem; opacity: 0.2"></i>
                                <p class="mt-2 mb-0" style="font-size: 0.9rem;">Ready for Admission</p>
                                <small class="text-muted"><?= e($bed['bed_type']) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: BED TRANSFER REQUEST                -->
<!-- ========================================== -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header bg-light" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title"><i class="fas fa-exchange-alt text-primary me-2"></i> Request Bed Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="transferForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="request_transfer">
                <input type="hidden" name="patient_id" id="t_patient_id">
                <input type="hidden" name="from_bed_id" id="t_from_bed_id">
                <input type="hidden" name="from_ward" value="<?= e($ward_assigned) ?>">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Current Location</label>
                        <input type="text" class="form-control bg-light" id="t_current_loc" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Destination Bed</label>
                        <select class="form-select" name="to_bed_id" required>
                            <option value="">-- Select Destination --</option>
                            <?php foreach($available_beds as $w => $bds): ?>
                                <optgroup label="<?= e($w) ?>">
                                    <?php foreach($bds as $bb): ?>
                                        <option value="<?= $bb['id'] ?>">Bed <?= e($bb['bed_number']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Reason for Transfer</label>
                        <textarea class="form-control" name="transfer_reason" rows="3" placeholder="Condition change, isolation required, step-down, etc..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnTransfer">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ISOLATION RECORD                    -->
<!-- ========================================== -->
<div class="modal fade" id="isolationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title"><i class="fas fa-biohazard me-2"></i> Log Isolation Requirement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="isolationForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="log_isolation">
                <input type="hidden" name="patient_id" id="i_patient_id">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Isolation Type</label>
                        <select class="form-select" name="isolation_type" required>
                            <option value="">-- Select --</option>
                            <option value="Contact">Contact</option>
                            <option value="Droplet">Droplet</option>
                            <option value="Airborne">Airborne</option>
                            <option value="Protective">Protective (Reverse)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Reason / Infection</label>
                        <input type="text" class="form-control" name="reason" placeholder="e.g. MRSA, COVID-19, TB..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Required Precautions</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="precautions[]" value="Gloves" id="p1">
                            <label class="form-check-label" for="p1">Gloves</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="precautions[]" value="Gowns" id="p2">
                            <label class="form-check-label" for="p2">Gowns</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="precautions[]" value="N95 Mask" id="p3">
                            <label class="form-check-label" for="p3">N95 Mask</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="precautions[]" value="Eye Protection" id="p4">
                            <label class="form-check-label" for="p4">Eye Protection</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" id="btnIsolate">Apply Isolation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTransferModal(patientId, bedId, bedNum) {
    $('#t_patient_id').val(patientId);
    $('#t_from_bed_id').val(bedId);
    $('#t_current_loc').val("<?= e($ward_assigned) ?> - Bed " + bedNum);
    new bootstrap.Modal(document.getElementById('transferModal')).show();
}

function openIsolationModal(patientId, bedId) {
    $('#i_patient_id').val(patientId);
    new bootstrap.Modal(document.getElementById('isolationModal')).show();
}

$(document).ready(function() {
    // Submit Transfer
    $('#transferForm').on('submit', function(e) {
        e.preventDefault();
        if(!confirm("Are you sure you want to submit a formal bed transfer request for this patient?")) return;
        const btn = $('#btnTransfer');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: '../nurse/process_ward.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#transferModal').modal('hide');
                    alert(res.message);
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).html('Submit Request');
                }
            },
            error: function() {
                alert('An error occurred.');
                btn.prop('disabled', false).html('Submit Request');
            }
        });
    });

    // Submit Isolation
    $('#isolationForm').on('submit', function(e) {
        e.preventDefault();
        if(!confirm("Are you sure you want to initiate an active isolation protocol for this patient?")) return;
        const btn = $('#btnIsolate');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: '../nurse/process_ward.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#isolationModal').modal('hide');
                    alert(res.message);
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).html('Apply Isolation');
                }
            },
            error: function() {
                alert('An error occurred.');
                btn.prop('disabled', false).html('Apply Isolation');
            }
        });
    });
});
</script>
