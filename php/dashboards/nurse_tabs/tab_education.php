<?php
// ============================================================
// NURSE DASHBOARD - PATIENT EDUCATION & DISCHARGE (MODULE 9)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT & WARD ─────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'Unknown Ward';

// ── FETCH PATIENTS IN WARD ───────────────────────────────────
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

// ── FETCH RECENT EDUCATION LOGS ──────────────────────────────
$edu_logs = [];
$q_edu = mysqli_query($conn, "
    SELECT pe.*, u.name AS patient_name, u.gender, p.patient_id as pid, n.full_name as author_name
    FROM patient_education pe
    JOIN patients p ON pe.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    JOIN nurses n ON pe.nurse_id = n.id
    WHERE pe.nurse_id = $nurse_pk OR pe.recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY pe.recorded_at DESC LIMIT 30
");
if ($q_edu) {
    while($r = mysqli_fetch_assoc($q_edu)) $edu_logs[] = $r;
}

// ── FETCH RECENT DISCHARGE INSTRUCTIONS ──────────────────────
$discharge_logs = [];
$q_dis = mysqli_query($conn, "
    SELECT di.*, u.name AS patient_name, u.gender, p.patient_id as pid, n.full_name as author_name
    FROM discharge_instructions di
    JOIN patients p ON di.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    JOIN nurses n ON di.nurse_id = n.id
    WHERE di.nurse_id = $nurse_pk OR di.given_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY di.given_at DESC LIMIT 30
");
if ($q_dis) {
    while($r = mysqli_fetch_assoc($q_dis)) $discharge_logs[] = $r;
}
?>

<div class="tab-content" id="education">

    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0"><i class="fas fa-chalkboard-teacher text-primary me-2"></i> Education & Discharge</h4>
            <p class="text-muted mb-0">Record patient health teachings and formulate discharge instructions.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-outline-primary shadow-sm" style="border-radius:20px;" onclick="document.getElementById('eduForm').reset(); new bootstrap.Modal(document.getElementById('eduModal')).show();">
                <i class="fas fa-book-medical"></i> Log Education
            </button>
            <button class="btn btn-primary shadow-sm ms-2" style="border-radius:20px;" onclick="document.getElementById('dischargeForm').reset(); new bootstrap.Modal(document.getElementById('dischargeModal')).show();">
                <i class="fas fa-door-open"></i> Discharge Plan
            </button>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="eduTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-4" id="log-tab" data-bs-toggle="tab" data-bs-target="#log-content" type="button" role="tab">
                <i class="fas fa-list-alt text-primary me-1"></i> Education Logs
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" id="disc-tab" data-bs-toggle="tab" data-bs-target="#disc-content" type="button" role="tab">
                <i class="fas fa-file-export text-danger me-1"></i> Discharge Instructions
            </button>
        </li>
    </ul>

    <div class="tab-content" id="eduTabsContent">
        
        <!-- Education Logs Tab -->
        <div class="tab-pane fade show active border-0" id="log-content" role="tabpanel">
            <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-body p-0">
                    <?php if(empty($edu_logs)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-book-open fs-1 mb-3 opacity-25 text-primary"></i>
                            <h5>No Recent Charting</h5>
                            <p>You have not logged any health education teachings recently.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush" style="border-radius: 12px;">
                            <?php foreach($edu_logs as $e): 
                                $lvl_color = 'bg-success';
                                if($e['understanding_level'] == 'Fair') $lvl_color = 'bg-warning text-dark';
                                elseif($e['understanding_level'] == 'Poor') $lvl_color = 'bg-danger';
                                elseif($e['understanding_level'] == 'Unable to Assess') $lvl_color = 'bg-secondary';
                            ?>
                                <div class="list-group-item p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="fw-bold mb-0 text-primary">
                                            <?= e($e['education_topic']) ?>
                                        </h5>
                                        <div class="text-end">
                                            <span class="badge bg-light text-dark border"><i class="fas fa-laptop-medical"></i> <?= e($e['method']) ?></span>
                                            <small class="text-muted ms-2 fw-bold"><?= date('d M, H:i', strtotime($e['recorded_at'])) ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-secondary bg-opacity-10 text-dark me-2">
                                            <i class="fas fa-user-injured text-muted"></i> <?= e($e['patient_name']) ?> (<?= e($e['pid']) ?>)
                                        </span>
                                        <span class="badge <?= $lvl_color ?> me-2">
                                            <i class="fas fa-brain"></i> Understanding: <?= e($e['understanding_level']) ?>
                                        </span>
                                        <?php if($e['requires_follow_up']): ?>
                                            <span class="badge bg-danger pulse-fade"><i class="fas fa-exclamation-circle"></i> Needs Follow-up</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if($e['follow_up_notes']): ?>
                                        <p class="mb-2 text-dark" style="font-size: 0.95rem;">
                                            <strong>Notes:</strong> <?= nl2br(e($e['follow_up_notes'])) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="text-end mt-2 pt-2 border-top">
                                        <small class="text-muted"><i class="fas fa-user-nurse"></i> Logged by: <?= e($e['author_name']) ?> (<?= e($e['education_id']) ?>)</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Discharge Instructions Tab -->
        <div class="tab-pane fade border-0" id="disc-content" role="tabpanel">
            <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-body p-0">
                    <?php if(empty($discharge_logs)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-door-open fs-1 mb-3 opacity-25 text-danger"></i>
                            <h5>No Recent Discharges</h5>
                            <p>No discharge instructions have been formulated recently.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush" style="border-radius: 12px;">
                            <?php foreach($discharge_logs as $d): ?>
                                <div class="list-group-item p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3 bg-<?= $d['gender']=='Male'?'primary':'danger' ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px;">
                                                <i class="fas fa-walking"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-0 fw-bold"><?= e($d['patient_name']) ?></h5>
                                                <small class="text-muted">ID: <?= e($d['pid']) ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block fw-bold"><i class="far fa-clock"></i> <?= date('d M Y, H:i', strtotime($d['given_at'])) ?></small>
                                            <?php if($d['patient_acknowledged']): ?>
                                                <span class="badge bg-success mt-1"><i class="fas fa-check-double"></i> Acknowledged</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark mt-1"><i class="fas fa-clock"></i> Pending Ack.</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="p-3 bg-light rounded mb-3" style="font-family: monospace; font-size: 0.95rem;">
                                        <?= nl2br(e($d['instruction_content'])) ?>
                                    </div>

                                    <?php if($d['notes']): ?>
                                        <p class="mb-0 text-muted small"><i class="fas fa-info-circle"></i> <?= e($d['notes']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="text-end border-top pt-2 mt-3">
                                        <small class="text-muted">Prepared by: <?= e($d['author_name']) ?> (<?= e($d['instruction_id']) ?>)</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: LOG EDUCATION                       -->
<!-- ========================================== -->
<div class="modal fade" id="eduModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title"><i class="fas fa-book-medical me-2"></i> Log Health Education Teaching</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="eduForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="log_education">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Patient</label>
                            <select class="form-select" name="patient_id" required>
                                <option value="">-- Select Patient --</option>
                                <?php foreach($patients_in_ward as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (Bed <?= e($p['bed_number']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Method of Teaching</label>
                            <select class="form-select" name="method" required>
                                <option value="Verbal">Verbal Instruction</option>
                                <option value="Written">Written Materials</option>
                                <option value="Demonstration">Return Demonstration</option>
                                <option value="Video">Video / Multimedia</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Education Topic(s)</label>
                        <input type="text" class="form-control fw-bold text-primary" name="topic" placeholder="e.g. Injection technique, Dietary restrictions..." required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Patient Understanding</label>
                            <select class="form-select" name="understanding" required>
                                <option value="Good">Good - Verbalized/Demonstrated back</option>
                                <option value="Fair">Fair - Needs reinforcement</option>
                                <option value="Poor">Poor - Retrospective teaching required</option>
                                <option value="Unable to Assess">Unable to Assess</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check pt-2">
                                <input class="form-check-input border-danger" type="checkbox" name="needs_followup" id="chkFollow" value="1">
                                <label class="form-check-label text-danger fw-bold" for="chkFollow">
                                    Flag for Follow-up / Retraining
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-muted fw-bold small text-uppercase">Teaching Notes & Observations</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Additional context regarding the educational session..." required></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light" style="border-radius:0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSaveEdu">Save Education Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADD DISCHARGE INSTRUCTIONS          -->
<!-- ========================================== -->
<div class="modal fade" id="dischargeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title"><i class="fas fa-file-export me-2"></i> Formulate Discharge Instructions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="dischargeForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="log_discharge">
                <div class="modal-body p-4 bg-light">
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold small text-uppercase">Patient</label>
                        <select class="form-select" name="patient_id" required>
                            <option value="">-- Select Patient --</option>
                            <?php foreach($patients_in_ward as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (Bed <?= e($p['bed_number']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Detailed Discharge Plan</label>
                        <textarea class="form-control text-dark" name="content" rows="6" placeholder="Medications, follow-ups, wound care, diet, activities, danger signs to report..." required style="font-family: inherit;"></textarea>
                        <small class="text-muted">This text will be the official nursing discharge summary provided to the patient.</small>
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-muted fw-bold small text-uppercase">Internal Nurse Notes (Optional)</label>
                        <input type="text" class="form-control" name="notes" placeholder="e.g. Escorted to lobby by wheelchair">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-white" style="border-radius:0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSaveDisc">Finalize Discharge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#eduForm, #dischargeForm').on('submit', function(e) {
        e.preventDefault();
        const formId = $(this).attr('id');
        const btn = formId === 'eduForm' ? $('#btnSaveEdu') : $('#btnSaveDisc');
        const origText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: '../nurse/process_education.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).html(origText);
                }
            },
            error: function() {
                alert('An error occurred. Check connection.');
                btn.prop('disabled', false).html(origText);
            }
        });
    });
});
</script>
