<?php
// ============================================================
// NURSE DASHBOARD - TASKS & SHIFTS (MODULE 6)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT INFO ───────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT * FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$shift_pk      = $current_shift['id'] ?? null;
$handover_done = $current_shift['handover_submitted'] ?? 0;

// ── FETCH PENDING TASKS ──────────────────────────────────────
$tasks = [];
$q_tasks = mysqli_query($conn, "
    SELECT t.*, p.patient_id, u.name AS patient_name, u.gender 
    FROM nurse_tasks t
    LEFT JOIN patients p ON t.patient_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE t.nurse_id = $nurse_pk AND t.status IN ('Pending', 'In Progress')
    ORDER BY t.due_time ASC, t.priority DESC
");
if ($q_tasks) {
    while ($r = mysqli_fetch_assoc($q_tasks)) $tasks[] = $r;
}

// ── FETCH COMPLETED TASKS (TODAY) ────────────────────────────
$completed_tasks = [];
$q_comp = mysqli_query($conn, "
    SELECT t.*, p.patient_id, u.name AS patient_name 
    FROM nurse_tasks t
    LEFT JOIN patients p ON t.patient_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE t.nurse_id = $nurse_pk AND t.status = 'Completed' AND DATE(t.completed_at) = '$today'
    ORDER BY t.completed_at DESC
");
if ($q_comp) {
    while ($r = mysqli_fetch_assoc($q_comp)) $completed_tasks[] = $r;
}

// ── FETCH UPCOMING SHIFTS ────────────────────────────────────
$upcoming_shifts = [];
$q_shifts = mysqli_query($conn, "
    SELECT * FROM nurse_shifts 
    WHERE nurse_id = $nurse_pk AND shift_date >= '$today' 
    ORDER BY shift_date ASC, start_time ASC LIMIT 7
");
if ($q_shifts) {
    while ($r = mysqli_fetch_assoc($q_shifts)) $upcoming_shifts[] = $r;
}
?>

<div class="tab-content" id="tasks">

    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0"><i class="fas fa-tasks text-muted me-2"></i> Task & Shift Management</h4>
            <p class="text-muted mb-0">Track clinical tasks and manage your rotations.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <?php if($shift_pk && !$handover_done): ?>
                <button class="btn btn-warning" style="border-radius:20px; font-weight:600;" onclick="openHandoverModal()">
                    <i class="fas fa-clipboard-check"></i> Submit Shift Handover
                </button>
            <?php elseif($handover_done): ?>
                <button class="btn btn-success" style="border-radius:20px;" disabled>
                    <i class="fas fa-check-circle"></i> Handover Submitted
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tasks Column -->
        <div class="col-lg-8">
            
            <!-- Pending Tasks -->
            <div class="card mb-4" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold" style="color: var(--primary-dark);"><i class="far fa-clock me-2 text-warning"></i> Pending Tasks (<?= count($tasks) ?>)</h5>
                </div>
                <div class="card-body p-0 mt-3">
                    <?php if(empty($tasks)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="far fa-check-circle fs-1 mb-3 opacity-25"></i>
                            <h5>All Caught Up!</h5>
                            <p>You have no pending clinical tasks.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush rounded-bottom">
                            <?php foreach($tasks as $t): 
                                $is_overdue = (strtotime($t['due_time']) < time());
                                $badge_color = 'bg-secondary';
                                if($t['priority'] == 'High') $badge_color = 'bg-danger';
                                elseif($t['priority'] == 'Medium') $badge_color = 'bg-warning text-dark';
                                elseif($t['priority'] == 'Low') $badge_color = 'bg-info text-dark';
                            ?>
                                <div class="list-group-item p-4 border-start border-4 <?= $is_overdue ? 'border-danger bg-danger bg-opacity-10' : 'border-primary' ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6 class="mb-1 fw-bold fs-5">
                                                <?= e($t['task_title']) ?>
                                                <span class="badge <?= $badge_color ?> ms-2" style="font-size:0.7rem;"><i class="fas fa-exclamation"></i> <?= e($t['priority']) ?></span>
                                            </h6>
                                            <?php if($t['patient_name']): ?>
                                                <small class="text-muted"><i class="fas fa-user-injured me-1"></i> <?= e($t['patient_name']) ?> (<?= e($t['patient_id']) ?>)</small>
                                            <?php else: ?>
                                                <small class="text-muted"><i class="fas fa-h-square me-1"></i> General Ward Task</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <div class="mb-2 <?= $is_overdue ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                <i class="far fa-clock"></i> Due: <?= date('H:i', strtotime($t['due_time'])) ?>
                                                <?php if($is_overdue): ?>
                                                    <br><small class="text-danger">(Overdue)</small>
                                                <?php endif; ?>
                                            </div>
                                            <button class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="completeTask(<?= $t['id'] ?>)">
                                                <i class="fas fa-check"></i> Mark Done
                                            </button>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-dark opacity-75" style="font-size: 0.95rem;"><?= nl2br(e($t['task_description'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Completed Tasks -->
            <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                    <h6 class="mb-0 text-muted"><i class="fas fa-check-double me-2"></i> Completed Today (<?= count($completed_tasks) ?>)</h6>
                </div>
                <div class="card-body p-0 mt-3">
                    <?php if(empty($completed_tasks)): ?>
                        <div class="px-4 py-3 text-muted small">No tasks completed yet today.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush rounded-bottom">
                            <?php foreach($completed_tasks as $t): ?>
                                <div class="list-group-item bg-light px-4 py-3 blur-sm">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div class="text-muted text-decoration-line-through">
                                            <i class="fas fa-check text-success me-2"></i><?= e($t['task_title']) ?>
                                            <?= $t['patient_name'] ? " - {$t['patient_name']}" : "" ?>
                                        </div>
                                        <small class="text-muted"><?= date('H:i', strtotime($t['completed_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Shifts Column -->
        <div class="col-lg-4">
            <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-header bg-white pb-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold"><i class="far fa-calendar-alt text-primary me-2"></i> My Roster</h5>
                </div>
                <div class="card-body px-0">
                    <div class="list-group list-group-flush">
                        <?php if(empty($upcoming_shifts)): ?>
                            <div class="px-4 py-3 text-muted text-center">No upcoming shifts scheduled.</div>
                        <?php else: ?>
                            <?php foreach($upcoming_shifts as $s): 
                                $is_today = ($s['shift_date'] == $today);
                                $is_active = ($s['status'] == 'Active');
                            ?>
                                <div class="list-group-item px-4 py-3 <?= $is_today ? ($is_active ? 'bg-primary bg-opacity-10 border-primary' : 'bg-light') : '' ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1 <?= $is_today ? 'fw-bold' : '' ?>">
                                                <?= $is_today ? 'Today' : date('l, d M', strtotime($s['shift_date'])) ?>
                                            </h6>
                                            <small class="text-muted"><i class="fas fa-map-marker-alt"></i> <?= e($s['ward_assigned'] ?: 'No Ward') ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="badge <?= $s['shift_type']=='Morning'?'bg-info':($s['shift_type']=='Afternoon'?'bg-warning text-dark':'bg-dark') ?> mb-1">
                                                <?= e($s['shift_type']) ?>
                                            </div>
                                            <br>
                                            <small class="fw-bold"><?= date('H:i', strtotime($s['start_time'])) ?> - <?= date('H:i', strtotime($s['end_time'])) ?></small>
                                        </div>
                                    </div>
                                    <?php if($is_active): ?>
                                        <div class="text-primary mt-2 small fw-bold"><i class="fas fa-circle ms-1" style="font-size:8px;"></i> Currently Active Shift</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: SHIFT HANDOVER                      -->
<!-- ========================================== -->
<div class="modal fade" id="handoverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header bg-warning" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-clipboard-check me-2"></i> Clinical Shift Handover</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="handoverForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="submit_handover">
                <input type="hidden" name="shift_id" value="<?= $shift_pk ?>">
                
                <div class="modal-body p-4">
                    
                    <div class="alert alert-info mb-4">
                        <strong>Shift:</strong> <?= e($current_shift['shift_type']??'') ?> (<?= e($ward_assigned) ?>) <br>
                        Please provide a comprehensive summary for the incoming nurse. This is a legally binding document.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Incoming Nurse (Optional)</label>
                            <select class="form-select" name="incoming_nurse_id">
                                <option value="">-- Select Incoming Nurse --</option>
                                <?php
                                    $n_q = mysqli_query($conn, "SELECT id, full_name, nurse_id FROM nurses WHERE id != $nurse_pk AND status='Active' ORDER BY full_name");
                                    if($n_q) while($n = mysqli_fetch_assoc($n_q)) {
                                        echo "<option value='{$n['id']}'>".e($n['full_name'])." ({$n['nurse_id']})</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">General Ward Status Summary</label>
                            <textarea class="form-control" name="summary" rows="3" placeholder="Identify general ward conditions, admissions, discharges, etc." required></textarea>
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label text-muted fw-bold small text-uppercase">Critical Patients / Pending Follow-ups</label>
                            <textarea class="form-control" name="critical_patients_notes" rows="3" placeholder="List patients requiring close monitoring, pending labs, or overdue tasks..." required></textarea>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-5 fw-bold" id="btnSubmitHandover">
                        <i class="fas fa-check-double me-1"></i> Sign off & Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function completeTask(taskId) {
    if(confirm("Mark this task as completed?")) {
        $.post('../nurse/process_tasks.php', {
            action: 'complete_task',
            task_id: taskId,
            _csrf: '<?= generateCsrfToken() ?>'
        }, function(res) {
            if(res.success) {
                location.reload();
            } else {
                alert(res.message);
            }
        }, 'json');
    }
}

function openHandoverModal() {
    document.getElementById('handoverForm').reset();
    new bootstrap.Modal(document.getElementById('handoverModal')).show();
}

$(document).ready(function() {
    $('#handoverForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSubmitHandover');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
        
        $.ajax({
            url: '../nurse/process_tasks.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#handoverModal').modal('hide');
                    alert(res.message);
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).html('<i class="fas fa-check-double me-1"></i> Sign off & Submit');
                }
            },
            error: function() {
                alert('An error occurred while submitting handover.');
                btn.prop('disabled', false).html('<i class="fas fa-check-double me-1"></i> Sign off & Submit');
            }
        });
    });
});
</script>
