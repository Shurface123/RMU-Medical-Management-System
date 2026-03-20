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

<div class="tab-content active" id="tasks">

    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.3rem;"><i class="fas fa-tasks text-primary"></i> Tasks &amp; Shift Management</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Coordinate clinical activities and formal shift transitions.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center;">
            <?php if($shift_pk && !$handover_done): ?>
                <button class="adm-btn adm-btn-warning" onclick="openHandoverModal()" style="padding:.8rem 2rem; border-radius:12px; font-weight:700; box-shadow:0 4px 12px rgba(241,196,15,0.2);">
                    <i class="fas fa-file-export"></i> Submit Handover
                </button>
            <?php elseif($handover_done): ?>
                <button class="adm-btn adm-btn-ghost" disabled style="padding:.8rem 2rem; border-radius:12px; opacity:0.8; color:var(--success); border-color:var(--success);">
                    <i class="fas fa-check-double"></i> Handover Signed
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:2.5rem; align-items:flex-start;">
        <!-- Left Column: Tasks -->
        <div>

            
            <!-- Pending Clinical Tasks -->
            <div class="adm-card shadow-sm" style="margin-bottom:2.5rem;">
                <div class="adm-card-header" style="justify-content:space-between; border-bottom:1.5px solid var(--border);">
                    <h3 style="font-size:1.5rem; font-weight:700;"><i class="far fa-clock text-warning"></i> Pending Tasks</h3>
                    <span class="adm-badge" style="background:var(--warning); color:#fff; font-weight:800; border:none;"><?= count($tasks) ?> ACTIVE</span>
                </div>
                <div class="adm-card-body" style="padding:0;">
                    <?php if(empty($tasks)): ?>
                        <div style="height:250px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:1rem; text-align:center; padding:2rem;">
                            <div style="width:60px; height:60px; border-radius:50%; background:var(--surface-2); display:flex; align-items:center; justify-content:center; font-size:2.5rem; color:var(--success); opacity:0.4;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h4 style="font-weight:700; color:var(--text-primary); margin:0;">All Tasks Completed</h4>
                                <p style="font-size:1.2rem; color:var(--text-muted);">No pending clinical duties for this shift.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column;">
                            <?php foreach($tasks as $t): 
                                $is_overdue = (strtotime($t['due_time']) < time());
                                $accent = 'var(--primary)';
                                if($t['priority'] == 'High') $accent = 'var(--danger)';
                                elseif($t['priority'] == 'Medium') $accent = 'var(--warning)';
                            ?>
                                <div style="padding:2rem; border-bottom:1.5px solid var(--border); border-left:4px solid <?= $is_overdue ? 'var(--danger)' : $accent ?>; background:<?= $is_overdue ? 'rgba(231,76,60,0.02)' : 'transparent' ?>; display:flex; gap:1.8rem; align-items:center;">
                                    <div style="flex:1;">
                                        <div style="display:flex; align-items:center; gap:1rem; margin-bottom:.5rem;">
                                            <h4 style="font-weight:800; font-size:1.5rem; color:var(--text-primary); margin:0;"><?= e($t['task_title']) ?></h4>
                                            <span class="adm-badge" style="background:<?= $is_overdue ? 'var(--danger)' : 'var(--surface-2)' ?>; color:<?= $is_overdue ? '#fff' : 'var(--text-secondary)' ?>; font-weight:700; border:1px solid var(--border);">
                                                <i class="fas fa-exclamation-circle" style="margin-right:.4rem;"></i><?= strtoupper(e($t['priority'])) ?>
                                            </span>
                                        </div>
                                        <div style="margin-bottom:1rem;">
                                            <?php if($t['patient_name']): ?>
                                                <span style="font-weight:700; color:var(--text-secondary); font-size:1.2rem;">
                                                    <i class="fas fa-user-circle" style="color:var(--primary); opacity:0.7;"></i> <?= e($t['patient_name']) ?> <small style="font-weight:500; opacity:0.6;">(PT ID: <?= e($t['patient_id']) ?>)</small>
                                                </span>
                                            <?php else: ?>
                                                <span style="font-weight:700; color:var(--text-muted); font-size:1.2rem;">
                                                    <i class="fas fa-hospital-alt"></i> Ward Operational Task
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p style="font-size:1.3rem; color:var(--text-primary); line-height:1.5; margin:0; opacity:0.8;"><?= nl2br(e($t['task_description'])) ?></p>
                                    </div>
                                    <div style="text-align:right; min-width:140px;">
                                        <div style="margin-bottom:1rem;">
                                            <div style="font-size:1.3rem; font-weight:800; color:<?= $is_overdue ? 'var(--danger)' : 'var(--text-primary)' ?>;">
                                                <i class="far fa-clock"></i> <?= date('H:i', strtotime($t['due_time'])) ?>
                                            </div>
                                            <?php if($is_overdue): ?>
                                                <small style="color:var(--danger); font-weight:700; font-size:1rem; text-transform:uppercase; letter-spacing:0.05em;">Overdue</small>
                                            <?php endif; ?>
                                        </div>
                                        <button class="adm-btn adm-btn-ghost" onclick="completeTask(<?= $t['id'] ?>)" style="padding:.6rem 1.5rem; border-radius:8px; font-weight:700; width:100%;">
                                            <i class="fas fa-check"></i> Mark Done
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Completed Activity Log (Today) -->
            <div class="adm-card shadow-sm">
                <div class="adm-card-header" style="background:var(--surface-2); border-bottom:1.5px solid var(--border);">
                    <h3 style="font-size:1.3rem; font-weight:700; color:var(--text-secondary);"><i class="fas fa-check-double text-success"></i> Accomplished Today</h3>
                    <span class="adm-badge" style="border:none; background:var(--success); color:#fff; font-weight:800;"><?= count($completed_tasks) ?> DONE</span>
                </div>
                <div class="adm-card-body" style="padding:0;">
                    <?php if(empty($completed_tasks)): ?>
                        <div style="padding:3rem; text-align:center; color:var(--text-muted); font-size:1.2rem; font-weight:600;">
                            No tasks have been finalized during this shift session.
                        </div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column;">
                            <?php foreach($completed_tasks as $t): ?>
                                <div style="padding:1.2rem 2rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; opacity:0.75;">
                                    <div style="display:flex; align-items:center; gap:1.2rem;">
                                        <i class="fas fa-check-circle text-success" style="font-size:1.4rem;"></i>
                                        <div>
                                            <div style="font-weight:700; font-size:1.3rem; color:var(--text-primary); text-decoration:line-through;">
                                                <?= e($t['task_title']) ?>
                                                <?php if($t['patient_name']): ?>
                                                    <small style="text-decoration:none; display:inline-block; margin-left:.5rem; opacity:0.6;">· <?= e($t['patient_name']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="font-size:1.1rem; font-weight:700; color:var(--text-muted);">
                                        <i class="far fa-clock"></i> <?= date('H:i', strtotime($t['completed_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <!-- Right Column: Roster & Timeline -->
        <div>
            <div class="adm-card shadow-sm">
                <div class="adm-card-header" style="border-bottom:1.5px solid var(--border);">
                    <h3 style="font-size:1.5rem; font-weight:700;"><i class="far fa-calendar-alt text-primary"></i> My Roster</h3>
                    <span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); font-weight:700;">7-DAY VIEW</span>
                </div>
                <div class="adm-card-body" style="padding:0;">
                    <?php if(empty($upcoming_shifts)): ?>
                        <div style="padding:3rem; text-align:center; color:var(--text-muted);">
                            <i class="fas fa-calendar-times" style="font-size:3rem; opacity:0.2; margin-bottom:1rem; display:block;"></i>
                            <strong>No shifts scheduled</strong>
                        </div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column;">
                            <?php foreach($upcoming_shifts as $s): 
                                $is_today = ($s['shift_date'] == $today);
                                $is_active = ($s['status'] == 'Active');
                                $shift_icon = 'fa-sun text-warning';
                                if($s['shift_type'] == 'Afternoon') $shift_icon = 'fa-cloud-sun text-primary';
                                if($s['shift_type'] == 'Night') $shift_icon = 'fa-moon text-indigo';
                            ?>
                                <div style="padding:1.8rem 2rem; border-bottom:1px solid var(--border); background:<?= $is_active ? 'rgba(var(--primary-rgb), 0.05)' : 'transparent' ?>; position:relative;">
                                    <?php if($is_active): ?>
                                        <div style="position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--primary);"></div>
                                    <?php endif; ?>
                                    
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:.8rem;">
                                        <div>
                                            <div style="font-weight:800; font-size:1.3rem; color:<?= $is_today ? 'var(--primary)' : 'var(--text-primary)' ?>;">
                                                <?= $is_today ? 'Today' : date('D, d M', strtotime($s['shift_date'])) ?>
                                            </div>
                                            <div style="font-size:1.1rem; font-weight:600; color:var(--text-muted); margin-top:.2rem;">
                                                <i class="fas fa-map-marker-alt"></i> <?= e($s['ward_assigned'] ?: 'Internal Float') ?>
                                            </div>
                                        </div>
                                        <div style="text-align:right;">
                                            <span class="adm-badge" style="background:#fff; border:1px solid var(--border); font-weight:800; padding:.4rem .8rem;">
                                                <i class="fas <?= $shift_icon ?>" style="margin-right:.4rem;"></i><?= strtoupper(e($s['shift_type'])) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem;">
                                        <div style="font-size:1.2rem; font-weight:700; color:var(--text-secondary);">
                                            <?= date('H:i', strtotime($s['start_time'])) ?> — <?= date('H:i', strtotime($s['end_time'])) ?>
                                        </div>
                                        <?php if($is_active): ?>
                                            <span style="display:flex; align-items:center; gap:.5rem; font-size:1rem; font-weight:800; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em;">
                                                <span class="pulse-fade" style="width:8px; height:8px; background:var(--primary); border-radius:50%;"></span>
                                                Active Now
                                            </span>
                                        <?php endif; ?>
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
<!-- MODAL: CLINICAL SHIFT HANDOVER             -->
<!-- ========================================== -->
<div class="modal-bg" id="handoverModal">
    <div class="modal-box" style="max-width:720px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        <div class="modal-header" style="background:var(--warning); padding:1.8rem 2.5rem;">
            <h3 style="color:#fff; font-size:1.8rem; font-weight:800; letter-spacing:-0.01em; margin:0;"><i class="fas fa-clipboard-check" style="margin-right:.8rem;"></i> Clinical Shift Handover</h3>
            <button class="modal-close" onclick="closeHandoverModal()" type="button" style="color:#fff; opacity:0.8;">×</button>
        </div>
        
        <div style="padding:2.5rem;">
            <form id="handoverForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="submit_handover">
                <input type="hidden" name="shift_id" value="<?= $shift_pk ?>">
                
                <div style="background:rgba(241,196,15,0.05); border:1px solid rgba(241,196,15,0.2); border-radius:12px; padding:1.5rem; margin-bottom:2rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <small style="text-transform:uppercase; font-size:0.9rem; font-weight:700; color:var(--text-muted);">Current Active Shift</small>
                            <div style="font-weight:800; font-size:1.4rem; color:var(--text-primary);"><?= e($current_shift['shift_type']??'N/A') ?> — <?= e($ward_assigned) ?></div>
                        </div>
                        <i class="fas fa-file-signature text-warning" style="font-size:2rem; opacity:0.5;"></i>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:2rem;">
                    <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Incoming Nurse / Receiver</label>
                    <select class="form-control" name="incoming_nurse_id" style="font-weight:600; padding:.8rem;">
                        <option value="">-- Select Receiving Personnel --</option>
                        <?php
                            $n_q = mysqli_query($conn, "SELECT id, full_name, nurse_id FROM nurses WHERE id != $nurse_pk AND status='Active' ORDER BY full_name");
                            if($n_q) while($n = mysqli_fetch_assoc($n_q)) {
                                echo "<option value='{$n['id']}'>".e($n['full_name'])." ({$n['nurse_id']})</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:2rem;">
                    <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">General Ward Status Summary</label>
                    <textarea class="form-control" name="summary" rows="3" placeholder="Identify general ward conditions, admissions, discharges, etc..." required style="padding:1rem;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom:2rem;">
                    <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Critical Patients &amp; Pending Follow-ups</label>
                    <textarea class="form-control" name="critical_patients_notes" rows="3" placeholder="List patients requiring close monitoring, pending labs, or overdue tasks..." required style="padding:1rem;"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; margin-top:2.5rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="adm-btn adm-btn-ghost" onclick="closeHandoverModal()" style="font-weight:600;">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-warning" id="btnSubmitHandover" style="padding:.8rem 2.5rem; font-weight:800; border-radius:12px; box-shadow:0 4px 12px rgba(241,196,15,0.2);">
                        <i class="fas fa-check-double"></i> Sign Off &amp; Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function completeTask(taskId) {
    Swal.fire({
        title: 'Complete Clinical Task?',
        text: "This will move the activity to the finalized record for today's shift.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--success)',
        confirmButtonText: 'Yes, Task Done'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../nurse/process_tasks.php', {
                action: 'complete_task',
                task_id: taskId,
                _csrf: '<?= generateCsrfToken() ?>'
            }, function(res) {
                if(res.success) {
                    Swal.fire({ icon: 'success', title: 'Task Finalized!', timer: 1000, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            }, 'json');
        }
    });
}

function openHandoverModal() {
    document.getElementById('handoverForm').reset();
    document.getElementById('handoverModal').style.display = 'flex';
}
function closeHandoverModal() {
    document.getElementById('handoverModal').style.display = 'none';
}

$(document).ready(function() {
    $('#handoverForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Verify Shift Handover?',
            text: "You are about to sign off on your current clinical shift. This action is immutable and legally documented.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--warning)',
            confirmButtonText: 'Yes, Finalize Handover'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#btnSubmitHandover');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Securing Record...');
                
                $.ajax({
                    url: '../nurse/process_tasks.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            Swal.fire({ icon: 'success', title: 'Handover Successful', text: 'Clinical session closed and secured.', timer: 2000, showConfirmButton: false });
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Submission Failed', text: res.message });
                            btn.prop('disabled', false).html('<i class="fas fa-check-double"></i> Sign Off & Submit');
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'System Error', text: 'Encryption failure or database communication loss.' });
                        btn.prop('disabled', false).html('<i class="fas fa-check-double"></i> Sign Off & Submit');
                    }
                });
            }
        });
    });
});
</script>

