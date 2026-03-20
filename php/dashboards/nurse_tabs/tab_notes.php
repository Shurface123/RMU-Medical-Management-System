<?php
// ============================================================
// NURSE DASHBOARD - NURSING NOTES (MODULE 5)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT & WARD ─────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT id, ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'All Wards';
$shift_pk      = $current_shift['id'] ?? null;

// ── FETCH AVAILABLE PATIENTS FOR NEW NOTE DROPDOWN ───────────
$patients_in_ward = [];
$q_pw = mysqli_query($conn, "
    SELECT p.id, p.patient_id, u.name 
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

// ── FETCH RECENT NOTES ───────────────────────────────────────
$notes = [];
$q_str = "
    SELECT 
        nn.id AS note_pk, nn.note_id, nn.note_type, nn.note_content, nn.is_locked, nn.created_at,
        p.patient_id, u.name AS patient_name, u.gender,
        n_author.full_name AS author_name,
        b.ward, b.bed_number
    FROM nursing_notes nn
    JOIN patients p ON nn.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    JOIN nurses n_author ON nn.nurse_id = n_author.id
    LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status = 'Occupied'
    LEFT JOIN beds b ON ba.bed_id = b.id
";
// Show notes for patients in this ward OR notes created by this nurse recently
if ($ward_assigned !== 'All Wards' && $ward_assigned !== 'Not Assigned') {
    $q_str .= " WHERE (b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."' OR nn.nurse_id = $nurse_pk)";
}
$q_str .= " ORDER BY nn.created_at DESC LIMIT 50";

$q_notes = mysqli_query($conn, $q_str);
if ($q_notes) {
    while ($r = mysqli_fetch_assoc($q_notes)) {
        $notes[] = $r;
    }
}
?>

<div class="tab-content" id="notes">

    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0"><i class="fas fa-clipboard-list text-muted me-2"></i> Nursing Notes</h4>
            <p class="text-muted mb-0">Clinical documentation for <strong><?= e($ward_assigned) ?></strong></p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <?php if($shift_pk): ?>
                <button class="btn btn-primary" style="border-radius:20px;" onclick="newNoteModal()">
                    <i class="fas fa-plus"></i> Add New Note
                </button>
            <?php else: ?>
                <button class="btn btn-secondary disabled" style="border-radius:20px;" title="Active shift required">
                    <i class="fas fa-plus"></i> Add New Note
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notes Feed -->
    <div class="row">
        <div class="col-12">
            <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-body p-0">
                    <?php if(empty($notes)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-file-medical-alt fs-1 mb-3 opacity-25"></i>
                            <h5>No recent notes</h5>
                            <p>There are no nursing notes documented for patients in this ward yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush" style="border-radius: 12px;">
                            <?php foreach($notes as $n): ?>
                                <div class="list-group-item p-4">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3 bg-<?= $n['gender']=='Male'?'primary':'danger' ?> bg-opacity-10 text-<?= $n['gender']=='Male'?'primary':'danger' ?> rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px; font-weight:bold; font-size:1.2rem;">
                                                <?= strtoupper(substr($n['patient_name'],0,1)) ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold" style="font-size: 1.05rem;">
                                                    <?= e($n['patient_name']) ?> <span class="text-muted fw-normal fs-6 ms-1">(<?= e($n['patient_id']) ?>)</span>
                                                </h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt"></i> <?= e($n['ward'] ?: 'Unknown') ?> - Bed <?= e($n['bed_number'] ?: 'N/A') ?>
                                                    &nbsp;|&nbsp;
                                                    <i class="fas fa-user-nurse"></i> <?= e($n['author_name']) ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block fw-bold"><i class="far fa-clock"></i> <?= date('d M Y, H:i', strtotime($n['created_at'])) ?></small>
                                            <?php
                                                $b_color = 'bg-secondary';
                                                switch($n['note_type']) {
                                                    case 'Wound Care':  $b_color = 'bg-danger'; break;
                                                    case 'Observation': $b_color = 'bg-info'; break;
                                                    case 'Handover':    $b_color = 'bg-primary'; break;
                                                    case 'Assessment':  $b_color = 'bg-success'; break;
                                                    case 'Incident':    $b_color = 'bg-warning text-dark'; break;
                                                }
                                            ?>
                                            <span class="badge <?= $b_color ?> mt-1"><i class="fas fa-tag"></i> <?= e($n['note_type']) ?></span>
                                            
                                            <?php if($n['is_locked']): ?>
                                                <span class="badge bg-dark mt-1" title="Locked for compliance"><i class="fas fa-lock"></i> Locked</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mt-3 p-3 bg-light rounded" style="font-family: Georgia, serif; line-height: 1.6; color: #444;">
                                        <?= nl2br(e($n['note_content'])) ?>
                                    </div>
                                    <div class="mt-2 text-end">
                                        <small class="text-muted">Note ID: <?= e($n['note_id']) ?></small>
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
<!-- MODAL: ADD NURSING NOTE                    -->
<!-- ========================================== -->
<div class="modal fade" id="newNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title"><i class="fas fa-file-signature me-2"></i> Document Clinical Note</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="noteForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_note">
                <input type="hidden" name="shift_id" value="<?= $shift_pk ?>">
                
                <div class="modal-body p-4">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Patient</label>
                            <select class="form-select" name="patient_id" required>
                                <option value="">-- Select Patient in Ward --</option>
                                <?php foreach($patients_in_ward as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (<?= e($p['patient_id']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(empty($patients_in_ward)): ?>
                                <small class="text-danger mt-1 d-block"><i class="fas fa-info-circle"></i> No patients are currently assigned to this ward.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Note Category</label>
                            <select class="form-select" name="note_type" required>
                                <option value="General">General</option>
                                <option value="Assessment">Assessment</option>
                                <option value="Observation">Observation</option>
                                <option value="Wound Care">Wound Care</option>
                                <option value="Behavior">Behavior</option>
                                <option value="Incident">Incident</option>
                            </select>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Clinical Documentation</label>
                            <textarea class="form-control" name="note_content" rows="6" placeholder="Enter objective findings, interventions, and evaluations..." required style="resize: vertical;"></textarea>
                        </div>

                        <div class="col-12 mt-3">
                            <div class="alert alert-warning py-2 mb-0" style="font-size:0.85rem;">
                                <i class="fas fa-lock text-warning me-1"></i> <strong>Audit Compliance:</strong> Notes cannot be edited after submission. They will be permanently locked when your shift ends.
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSaveNote" <?= empty($patients_in_ward) ? 'disabled' : '' ?>>
                        <i class="fas fa-save me-1"></i> Submit Note
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function newNoteModal() {
    document.getElementById('noteForm').reset();
    new bootstrap.Modal(document.getElementById('newNoteModal')).show();
}

$(document).ready(function() {
    $('#noteForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSaveNote');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: '../nurse/process_notes.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#newNoteModal').modal('hide');
                    alert(res.message);
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Submit Note');
                }
            },
            error: function() {
                alert('An error occurred while saving the note.');
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Submit Note');
            }
        });
    });
});
</script>
