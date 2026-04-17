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
                <button class="btn btn-warning" onclick="openHandoverModal()" style="padding:.8rem 2rem; border-radius:12px; font-weight:700; box-shadow:0 4px 12px rgba(241,196,15,0.2);"><span class="btn-text">
                    <i class="fas fa-file-export"></i> Submit Handover
                </span></button>
            <?php elseif($handover_done): ?>
                <button class="btn btn-ghost" disabled style="padding:.8rem 2rem; border-radius:12px; opacity:0.8; color:var(--success); border-color:var(--success);"><span class="btn-text">
                    <i class="fas fa-check-double"></i> Handover Signed
                </span></button>
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
                            <h4 style="font-weight:700; color:var(--text-primary); margin:0;">All Tasks Completed</h4>
                            <p style="font-size:1.2rem; color:var(--text-muted);">No pending clinical duties for this shift.</p>
                        </div>
                    <?php else: ?>
                        <div style="padding:1.5rem 1.8rem; display:flex;flex-direction:column;gap:0;">
                            <?php foreach($tasks as $idx => $t):
                                $is_overdue = (strtotime($t['due_time']) < time());
                                $p = $t['priority'];
                                $accent     = 'var(--primary)';
                                $chip_bg    = 'var(--info-gradient)';
                                $dot_color  = 'var(--primary)';
                                if($p == 'High')   { $accent = 'var(--danger)'; $chip_bg = 'var(--danger-gradient)'; $dot_color='var(--danger)'; }
                                elseif($p == 'Medium') { $accent = 'var(--warning)'; $chip_bg = 'var(--role-gradient)'; $dot_color='var(--warning)'; }
                                if($is_overdue)    { $accent = 'var(--danger)'; }
                                $is_last = ($idx === count($tasks) - 1);
                            ?>
                                <!-- Timeline Item -->
                                <div style="display:flex;gap:1.4rem;">
                                    <!-- Timeline Track -->
                                    <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:18px;">
                                        <div class="ov-timeline-dot" style="background:<?= $dot_color ?>;width:14px;height:14px;margin-top:1.8rem;"></div>
                                        <?php if(!$is_last): ?><div class="ov-timeline-line" style="flex:1;min-height:40px;"></div><?php endif; ?>
                                    </div>
                                    <!-- Card Body -->
                                    <div style="flex:1;background:var(--surface);border:1px solid var(--border);border-left:4px solid <?= $is_overdue ? 'var(--danger)' : $accent ?>;border-radius:12px;padding:1.5rem 1.8rem;margin-bottom:1.2rem;box-shadow:var(--shadow-sm);transition:var(--transition);">
                                        <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.6rem;flex-wrap:wrap;">
                                            <span class="rec-diag-chip" style="background:<?= $chip_bg ?>;font-size:1.05rem;padding:.2rem .8rem;"><?= strtoupper(e($p)) ?></span>
                                            <?php if($is_overdue): ?><span class="adm-badge adm-badge-danger pulse-fade" style="font-size:1rem;"><i class="fas fa-clock"></i> OVERDUE</span><?php endif; ?>
                                            <?php if($t['status'] == 'In Progress'): ?><span class="adm-badge" style="background:var(--info-gradient);color:#fff;font-size:1rem;"><i class="fas fa-spinner fa-spin"></i> IN PROGRESS</span><?php endif; ?>
                                        </div>
                                        <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:.4rem;"><?= e($t['task_title']) ?></div>
                                        <?php if($t['patient_name']): ?>
                                            <span style="font-size:1.2rem;color:var(--text-secondary);font-weight:600;"><i class="fas fa-user-circle" style="color:var(--primary);margin-right:.3rem;"></i> <?= e($t['patient_name']) ?> <small style="opacity:.6;">(<?= e($t['patient_id']) ?>)</small></span>
                                        <?php else: ?>
                                            <span style="font-size:1.2rem;color:var(--text-muted);"><i class="fas fa-hospital-alt"></i> Ward Task</span>
                                        <?php endif; ?>
                                        <?php if(!empty($t['task_description'])): ?>
                                        <p style="font-size:1.25rem;color:var(--text-primary);opacity:.8;margin:1rem 0 1.2rem;line-height:1.5;"><?= nl2br(e($t['task_description'])) ?></p>
                                        <?php endif; ?>
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.8rem;">
                                            <span style="font-size:1.15rem;color:<?= $is_overdue ? 'var(--danger)' : 'var(--text-muted)' ?>;font-weight:700;"><i class="far fa-clock"></i> Due: <?= date('H:i · d M', strtotime($t['due_time'])) ?></span>
                                            <button class="btn btn-ghost btn-sm" onclick="completeTask(<?= $t['id'] ?>)" style="border-radius:8px;font-weight:700;">
                                                <span class="btn-text"><i class="fas fa-check"></i> Mark Done</span>
                                            </button>
                                        </div>
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
                        <div style="padding:1.5rem 1.8rem;">
                            <?php foreach($completed_tasks as $t): ?>
                                <div class="activity-item" style="opacity:.8;">
                                    <div class="ov-timeline-dot" style="background:var(--success);"></div>
                                    <div style="flex:1;margin-left:1rem;">
                                        <div style="display:flex;justify-content:space-between;align-items:center;">
                                            <span style="font-weight:700;font-size:1.3rem;color:var(--text-primary);text-decoration:line-through;">
                                                <?= e($t['task_title']) ?>
                                                <?php if($t['patient_name']): ?><small style="text-decoration:none;opacity:.6;"> · <?= e($t['patient_name']) ?></small><?php endif; ?>
                                            </span>
                                            <small style="font-weight:700;color:var(--text-muted);white-space:nowrap;margin-left:1rem;"><i class="far fa-clock"></i> <?= date('H:i', strtotime($t['completed_at'])) ?></small>
                                        </div>
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
            <button class="btn btn-primary modal-close" onclick="closeHandoverModal()" type="button" style="color:#fff; opacity:0.8;"><span class="btn-text">×</span></button>
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
                            <div style="font-weight:800; font-size:1.4rem; color:var(--text-primary);"><?= e($current_shift['shift_type']??'N/A') ?> — <?= e($current_shift['ward_assigned'] ?? 'Unknown Ward') ?></div>
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
                    <button type="button" class="btn btn-ghost" onclick="closeHandoverModal()" style="font-weight:600;"><span class="btn-text">Cancel</span></button>
                    <button type="submit" class="btn btn-warning" id="btnSubmitHandover" style="padding:.8rem 2.5rem; font-weight:800; border-radius:12px; box-shadow:0 4px 12px rgba(241,196,15,0.2);"><span class="btn-text">
                        <i class="fas fa-check-double"></i> Sign Off &amp; Submit
                    </span></button>
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
                    setTimeout(() => window.location.href = '?tab=tasks', 1000);
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
                            setTimeout(() => window.location.href = '?tab=tasks', 2000);
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

