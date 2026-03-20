<?php
// ============================================================
// NURSE DASHBOARD - EMERGENCY RESPONSE (MODULE 7)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT & WARD ─────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'Unknown Ward';

// ── FETCH PATIENTS FOR DROP DOWN ─────────────────────────────
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

// ── FETCH ACTIVE & RECENT EMERGENCY ALERTS ───────────────────
$alerts = [];
$q_alerts = mysqli_query($conn, "
    SELECT 
        e.*, 
        n.full_name AS triggering_nurse,
        u.name AS patient_name, u.gender, p.patient_id as pid,
        r_user.name AS resolver_name, r_user.user_role AS resolver_role
    FROM emergency_alerts e
    LEFT JOIN nurses n ON e.nurse_id = n.id
    LEFT JOIN patients p ON e.patient_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN users r_user ON e.resolved_by = r_user.id
    WHERE e.status IN ('Active', 'Responded') 
       OR (e.status IN ('Resolved', 'False Alarm') AND DATE(e.triggered_at) = '$today')
    ORDER BY 
        CASE e.status 
            WHEN 'Active' THEN 1 
            WHEN 'Responded' THEN 2 
            ELSE 3 
        END ASC, 
        e.triggered_at DESC
    LIMIT 30
");
if ($q_alerts) {
    while ($r = mysqli_fetch_assoc($q_alerts)) {
        $alerts[] = $r;
    }
}
?>

<div class="tab-content" id="emergency">

    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0 text-danger fw-bold"><i class="fas fa-ambulance me-2"></i> Emergency Response</h4>
            <p class="text-muted mb-0">Trigger and manage critical rapid response alerts across the facility.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-outline-danger" style="border-radius:20px;" onclick="location.reload();">
                <i class="fas fa-sync-alt"></i> Refresh Feed
            </button>
        </div>
    </div>

    <div class="row g-4 border-top pt-4">
        
        <!-- Trigger Panel -->
        <div class="col-lg-5">
            <div class="card h-100 bg-danger bg-opacity-10 border-danger border-2" style="border-radius: 15px; border-style: dashed !important;">
                <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                    
                    <div class="mb-4">
                        <div class="btn btn-danger rounded-circle d-inline-flex align-items-center justify-content-center pulse-animation mb-3" 
                             style="width: 100px; height: 100px; box-shadow: 0 0 0 10px rgba(220, 53, 69, 0.2);"
                             onclick="showTriggerModal('Code Blue')">
                            <i class="fas fa-heartbeat text-white" style="font-size: 2.5rem;"></i>
                        </div>
                        <h4 class="fw-bold text-danger mb-1">CODE BLUE</h4>
                        <p class="text-danger opacity-75 small">Cardiac / Respiratory Arrest</p>
                    </div>

                    <div class="row g-2 justify-content-center mt-2">
                        <div class="col-6">
                            <button class="btn btn-outline-danger w-100 fw-bold py-2 shadow-sm rounded-pill" onclick="showTriggerModal('Rapid Response')">
                                <i class="fas fa-user-md me-1"></i> Rapid Response
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-warning w-100 fw-bold py-2 shadow-sm rounded-pill text-dark" onclick="showTriggerModal('Fall')">
                                <i class="fas fa-user-injured me-1"></i> Patient Fall
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-dark w-100 fw-bold py-2 shadow-sm rounded-pill" onclick="showTriggerModal('Security')">
                                <i class="fas fa-shield-alt me-1"></i> Security Event
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 fw-bold py-2 shadow-sm rounded-pill" onclick="showTriggerModal('General Emergency')">
                                <i class="fas fa-exclamation-triangle me-1"></i> Other Assist
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Alerts Feed -->
        <div class="col-lg-7">
            <div class="card h-100" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-header bg-white border-bottom-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-bell text-danger me-2"></i> Active Facility Alerts</h5>
                    <?php 
                        $active_count = array_reduce($alerts, fn($c,$a) => $c + (in_array($a['status'],['Active','Responded'])?1:0), 0);
                        if($active_count > 0): 
                    ?>
                        <span class="badge bg-danger rounded-pill pulse-badge"><?= $active_count ?> Active</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0 mt-3" style="max-height: 500px; overflow-y: auto;">
                    <?php if(empty($alerts)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="far fa-check-circle fs-1 mb-3 text-success opacity-50"></i>
                            <h5 class="text-success">All Clear</h5>
                            <p>There are no active emergencies in the facility.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush border-top border-light">
                            <?php foreach($alerts as $a): 
                                $is_active = ($a['status'] == 'Active');
                                $is_responded = ($a['status'] == 'Responded');
                                $is_resolved = in_array($a['status'], ['Resolved','False Alarm']);
                                
                                $bg_class = 'bg-light';
                                $border_class = '';
                                if($is_active) {
                                    $bg_class = 'bg-danger bg-opacity-10';
                                    $border_class = 'border-start border-4 border-danger';
                                } elseif($is_responded) {
                                    $bg_class = 'bg-warning bg-opacity-10';
                                    $border_class = 'border-start border-4 border-warning';
                                }
                            ?>
                                <div class="list-group-item p-4 <?= $bg_class ?> <?= $border_class ?>">
                                    <div class="d-flex justify-content-between mb-2">
                                        <h5 class="fw-bold <?= $is_active ? 'text-danger' : ($is_responded ? 'text-warning text-dark' : 'text-muted text-decoration-line-through') ?>">
                                            <?php if($a['alert_type'] == 'Code Blue'): ?>
                                                <i class="fas fa-heartbeat"></i>
                                            <?php elseif(in_array($a['alert_type'], ['Rapid Response','Cardiac Arrest'])): ?>
                                                <i class="fas fa-stethoscope"></i>
                                            <?php else: ?>
                                                <i class="fas fa-exclamation-triangle"></i>
                                            <?php endif; ?>
                                            <?= strtoupper(e($a['alert_type'])) ?>
                                        </h5>
                                        <div class="text-end">
                                            <span class="badge <?= $is_active ? 'bg-danger pulse-fade' : ($is_responded ? 'bg-warning text-dark' : 'bg-secondary') ?> rounded-pill mb-1 d-inline-block">
                                                <?= e($a['status']) ?>
                                            </span>
                                            <br>
                                            <small class="text-muted fw-bold"><?= date('H:i', strtotime($a['triggered_at'])) ?></small>
                                        </div>
                                    </div>
                                    
                                    <p class="mb-2 text-dark" style="font-size: 1.05rem;">
                                        <strong>Location:</strong> <?= e($a['location']) ?>
                                    </p>
                                    
                                    <?php if($a['patient_name']): ?>
                                        <div class="p-2 mb-2 rounded bg-white shadow-sm border" style="font-size: 0.9rem;">
                                            <i class="fas fa-user-injured text-muted me-1"></i> <strong>Patient involved:</strong> <?= e($a['patient_name']) ?> (<?= e($a['pid']) ?>)
                                        </div>
                                    <?php endif; ?>

                                    <p class="mb-3 text-muted" style="font-size: 0.9rem;">
                                        <em>"<?= e($a['message']) ?>"</em>
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-dark border-opacity-10">
                                        <small class="text-muted"><i class="fas fa-user-nurse"></i> Initiated by: <?= e($a['triggering_nurse']) ?> (<?= e($a['alert_id']) ?>)</small>
                                        
                                        <div class="btn-group">
                                            <?php if($is_active): ?>
                                                <button class="btn btn-sm btn-warning fw-bold text-dark rounded-pill px-3 shadow-sm me-2" onclick="updateAlert(<?= $a['id'] ?>, 'Responded')">
                                                    <i class="fas fa-running"></i> I am Responding
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if($is_active || $is_responded): ?>
                                                <button class="btn btn-sm btn-success fw-bold rounded-pill px-3 shadow-sm" onclick="showResolveModal(<?= $a['id'] ?>)">
                                                    <i class="fas fa-check"></i> Mark Resolved
                                                </button>
                                            <?php endif; ?>

                                            <?php if($is_resolved): ?>
                                                <small class="text-success"><i class="fas fa-check-double mt-1"></i> Cleared by <?= e($a['resolver_name']) ?></small>
                                            <?php endif; ?>
                                        </div>
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

<style>
@keyframes pulse-ring {
  0% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 20px rgba(220, 53, 69, 0); }
  100% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
.pulse-animation {
  animation: pulse-ring 2s infinite;
  transition: all 0.2s;
  cursor: pointer;
}
.pulse-animation:hover { transform: scale(1.05); }

@keyframes fade-pulse {
  0% { opacity: 1; }
  50% { opacity: 0.6; }
  100% { opacity: 1; }
}
.pulse-fade {
  animation: fade-pulse 1.5s infinite;
}
</style>

<!-- ========================================== -->
<!-- MODAL: TRIGGER EMERGENCY ALERT             -->
<!-- ========================================== -->
<div class="modal fade" id="triggerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-danger border-2" style="border-radius: 15px;">
            <div class="modal-header bg-danger text-white" style="border-radius: 13px 13px 0 0;">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-circle me-2"></i> Trigger Alert: <span id="lblAlertType">Code Blue</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="triggerForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="trigger_alert">
                <input type="hidden" name="alert_type" id="inpAlertType">
                
                <div class="modal-body p-4 bg-light">
                    
                    <div class="mb-3">
                        <label class="form-label text-danger fw-bold small text-uppercase">Exact Location</label>
                        <input type="text" class="form-control fw-bold border-danger" name="location" id="inpLocation" value="<?= e($ward_assigned) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Patient Involved (Optional)</label>
                        <select class="form-select" name="patient_id" id="inpPatient">
                            <option value="">-- No specific patient / Unknown --</option>
                            <?php foreach($patients_in_ward as $p): ?>
                                <option value="<?= $p['id'] ?>">Bed <?= e($p['bed_number']) ?>: <?= e($p['name']) ?> (<?= e($p['patient_id']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Emergency Details</label>
                        <textarea class="form-control" name="message" id="inpMessage" rows="2" placeholder="Brief details to help responders..." required></textarea>
                    </div>

                </div>
                <div class="modal-footer border-0 bg-light p-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-5 fw-bold pulse-fade" id="btnTriggerConfirm">
                        <i class="fas fa-bullhorn me-2"></i> BROADCAST ALERT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: RESOLVE EMERGENCY ALERT             -->
<!-- ========================================== -->
<div class="modal fade" id="resolveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header bg-success text-white" style="border-radius: 13px 13px 0 0;">
                <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-2"></i> Resolve Alert</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="resolveForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_alert">
                <input type="hidden" name="alert_id" id="resAlertId">
                
                <div class="modal-body p-4 text-center">
                    <p class="mb-3">Mark this emergency as:</p>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold py-2" onclick="submitResolve('Resolved')">Resolved successfully</button>
                        <button type="button" class="btn btn-outline-secondary py-2" onclick="submitResolve('False Alarm')">False Alarm</button>
                    </div>
                    <input type="hidden" name="status" id="resStatus">
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showTriggerModal(type) {
    document.getElementById('lblAlertType').textContent = type;
    document.getElementById('inpAlertType').value = type;
    
    // Auto populate message based on type
    if(type === 'Code Blue') $('#inpMessage').val('Patient unresponsive / Cardiac arrest.');
    else if(type === 'Rapid Response') $('#inpMessage').val('Acute deterioration in patient condition.');
    else if(type === 'Fall') $('#inpMessage').val('Patient found on floor.');
    else $('#inpMessage').val('');
    
    new bootstrap.Modal(document.getElementById('triggerModal')).show();
}

function showResolveModal(id) {
    document.getElementById('resAlertId').value = id;
    new bootstrap.Modal(document.getElementById('resolveModal')).show();
}

function submitResolve(statusVal) {
    document.getElementById('resStatus').value = statusVal;
    $('#resolveForm').submit();
}

function updateAlert(alertId, statusVal) {
    $.post('../nurse/process_emergency.php', {
        action: 'update_alert',
        alert_id: alertId,
        status: statusVal,
        _csrf: '<?= generateCsrfToken() ?>'
    }, function(res) {
        if(res.success) location.reload();
        else alert(res.message);
    }, 'json').fail(function(){ alert("Error communicating with server."); });
}

$(document).ready(function() {
    $('#triggerForm').on('submit', function(e) {
        e.preventDefault();
        if(!confirm("CRITICAL WARNING: Are you sure you want to broadcast this facility-wide EMERGENCY alert?")) return;
        const btn = $('#btnTriggerConfirm');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> BROADCASTING...');
        
        $.ajax({
            url: '../nurse/process_emergency.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#triggerModal').modal('hide');
                    alert(res.message); // In production this might be a toast
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).html('<i class="fas fa-bullhorn me-2"></i> BROADCAST ALERT');
                }
            },
            error: function() {
                alert('An error occurred. Check connection.');
                btn.prop('disabled', false).html('<i class="fas fa-bullhorn me-2"></i> BROADCAST ALERT');
            }
        });
    });

    $('#resolveForm').on('submit', function(e) {
        e.preventDefault();
        if(!confirm("Are you certain this emergency event should be marked as resolved?")) return;
        $.ajax({
            url: '../nurse/process_emergency.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) location.reload();
                else alert('Error: ' + res.message);
            }
        });
    });
});
</script>
