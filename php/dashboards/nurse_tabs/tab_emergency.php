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

<div class="tab-content active" id="emergency">

    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--danger); margin-bottom:.3rem;"><i class="fas fa-ambulance pulse-fade"></i> Emergency Response Center</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Real-time critical incident management and rapid response coordination.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center;">
             <div style="background:rgba(231,76,60,0.05); border:1px solid rgba(231,76,60,0.1); padding:.8rem 1.5rem; border-radius:12px; display:flex; align-items:center; gap:1rem;">
                <span style="width:10px; height:10px; border-radius:50%; background:var(--success); display:inline-block;"></span>
                <div style="font-size:1.4rem; font-weight:800; color:var(--text-primary);">System <small style="font-weight:700; color:var(--success);">READY</small></div>
            </div>
            <button class="adm-btn adm-btn-ghost" onclick="location.reload();" style="border-radius:12px; font-weight:700;">
                <i class="fas fa-sync-alt"></i> Refresh Feed
            </button>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1.5fr; gap:3rem; align-items:flex-start;">
        
        <!-- HIGH-URGENCY TRIGGER PANEL -->
        <div>
            <div class="adm-card shadow-sm" style="border:2px solid var(--danger); background:rgba(231,76,60,0.02);">
                <div class="adm-card-header" style="background:var(--danger); color:#fff; border-bottom:none;">
                    <h3 style="font-size:1.4rem; font-weight:800; letter-spacing:0.05em;"><i class="fas fa-exclamation-triangle"></i> TRIGGER CRITICAL RESPONSE</h3>
                </div>
                <div class="adm-card-body" style="padding:2.5rem; text-align:center;">
                    
                    <!-- MAIN CODE BLUE TRIGGER -->
                    <div style="margin-bottom:3rem;">
                        <div class="pulse-animation" 
                             style="width: 120px; height: 120px; background:var(--danger); border-radius:50%; margin:0 auto 1.5rem; display:flex; align-items:center; justify-content:center; color:#fff; cursor:pointer; box-shadow:0 0 0 10px rgba(231,76,60,0.1);"
                             onclick="showTriggerModal('Code Blue')">
                            <i class="fas fa-heartbeat" style="font-size: 4rem;"></i>
                        </div>
                        <h2 style="font-weight:900; color:var(--danger); letter-spacing:0.1em; margin:0;">CODE BLUE</h2>
                        <p style="font-weight:700; color:var(--text-secondary); margin-top:.5rem; text-transform:uppercase; font-size:1.1rem; opacity:0.8;">Cardiac / Respiratory Arrest</p>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem;">
                        <button class="adm-btn adm-btn-ghost" onclick="showTriggerModal('Rapid Response')" style="border-color:var(--danger); color:var(--danger); font-weight:700; padding:1.2rem .5rem; height:auto; display:flex; flex-direction:column; gap:.8rem;">
                            <i class="fas fa-user-md" style="font-size:1.8rem;"></i>
                            <span>Rapid Response</span>
                        </button>
                        <button class="adm-btn adm-btn-ghost" onclick="showTriggerModal('Fall')" style="border-color:var(--warning); color:var(--warning); font-weight:700; padding:1.2rem .5rem; height:auto; display:flex; flex-direction:column; gap:.8rem;">
                            <i class="fas fa-user-injured" style="font-size:1.8rem;"></i>
                            <span>Patient Fall</span>
                        </button>
                        <button class="adm-btn adm-btn-ghost" onclick="showTriggerModal('Security')" style="border-color:var(--text-primary); color:var(--text-primary); font-weight:700; padding:1.2rem .5rem; height:auto; display:flex; flex-direction:column; gap:.8rem;">
                            <i class="fas fa-shield-alt" style="font-size:1.8rem;"></i>
                            <span>Security Alert</span>
                        </button>
                        <button class="adm-btn adm-btn-ghost" onclick="showTriggerModal('General Emergency')" style="border-color:var(--text-muted); color:var(--text-muted); font-weight:700; padding:1.2rem .5rem; height:auto; display:flex; flex-direction:column; gap:.8rem;">
                            <i class="fas fa-exclamation-circle" style="font-size        <!-- REAL-TIME ALERTS FEED -->
        <div>
            <div class="adm-card shadow-sm">
                <div class="adm-card-header" style="justify-content:space-between; border-bottom:1.5px solid var(--border);">
                    <h3 style="font-size:1.5rem; font-weight:700;"><i class="fas fa-bell text-danger"></i> Facility Alert History</h3>
                    <div style="display:flex; gap:1rem; align-items:center;">
                        <?php 
                            $active_count = array_reduce($alerts, fn($c,$a) => $c + (in_array($a['status'],['Active','Responded'])?1:0), 0);
                            if($active_count > 0): 
                        ?>
                            <span class="adm-badge pulse-fade" style="background:var(--danger); color:#fff; font-weight:800; border:none; padding:.4rem 1rem;">
                                <?= $active_count ?> ACTIVE EMERGENCY
                            </span>
                        <?php else: ?>
                            <span class="adm-badge" style="background:var(--success); color:#fff; font-weight:800; border:none; padding:.4rem 1rem;">
                                <i class="fas fa-check-circle"></i> ALL CLEAR
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="adm-card-body" style="padding:0; max-height:650px; overflow-y:auto;">
                    <?php if(empty($alerts)): ?>
                        <div style="height:350px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:1.5rem; text-align:center; padding:2rem;">
                            <div style="width:70px; height:70px; border-radius:50%; background:rgba(46,204,113,0.1); display:flex; align-items:center; justify-content:center; font-size:3rem; color:var(--success);">
                                <i class="fas fa-shield-virus"></i>
                            </div>
                            <div>
                                <h4 style="font-weight:700; color:var(--text-primary); margin:0;">No Active Emergencies</h4>
                                <p style="font-size:1.2rem; color:var(--text-muted);">Facility monitoring is active. All wards currently report normal status.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column;">
                            <?php foreach($alerts as $a): 
                                $is_active = ($a['status'] == 'Active');
                                $is_responded = ($a['status'] == 'Responded');
                                $is_resolved = in_array($a['status'], ['Resolved','False Alarm']);
                                
                                $status_color = 'var(--text-muted)';
                                if($is_active) $status_color = 'var(--danger)';
                                elseif($is_responded) $status_color = 'var(--warning)';
                                elseif($is_resolved) $status_color = 'var(--success)';
                            ?>
                                <div style="padding:2rem; border-bottom:1.5px solid var(--border); background:<?= $is_active ? 'rgba(231,76,60,0.03)' : ($is_responded ? 'rgba(241,196,15,0.02)' : 'transparent') ?>; position:relative;">
                                    <?php if($is_active): ?>
                                        <div style="position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--danger);" class="pulse-fade"></div>
                                    <?php endif; ?>

                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem;">
                                        <div>
                                            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:.5rem;">
                                                <h3 style="font-weight:900; font-size:1.6rem; color:<?= $status_color ?>; margin:0; letter-spacing:-0.01em;">
                                                    <i class="fas <?= $a['alert_type'] == 'Code Blue' ? 'fa-heartbeat' : (in_array($a['alert_type'], ['Rapid Response','Cardiac Arrest']) ? 'fa-stethoscope' : 'fa-exclamation-triangle') ?>" style="margin-right:.6rem;"></i>
                                                    <?= strtoupper(e($a['alert_type'])) ?>
                                                </h3>
                                                <span class="adm-badge" style="background:<?= $is_active ? 'var(--danger)' : ($is_responded ? 'var(--warning)' : 'var(--surface-2)') ?>; color:<?= ($is_active || $is_responded) ? '#fff' : 'var(--text-secondary)' ?>; font-weight:800; border:none;">
                                                    <?= strtoupper(e($a['status'])) ?>
                                                </span>
                                            </div>
                                            <div style="font-size:1.3rem; font-weight:800; color:var(--text-primary);">
                                                <i class="fas fa-map-marker-alt" style="color:var(--danger); opacity:0.7;"></i> LOCATION: <?= e($a['location']) ?>
                                            </div>
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="font-size:1.2rem; font-weight:700; color:var(--text-muted);">
                                                <i class="far fa-clock"></i> <?= date('H:i:s', strtotime($a['triggered_at'])) ?>
                                            </div>
                                            <small style="font-weight:600; opacity:0.6; text-transform:uppercase;"><?= date('M d, Y', strtotime($a['triggered_at'])) ?></small>
                                        </div>
                                    </div>

                                    <?php if($a['patient_name']): ?>
                                        <div style="background:var(--surface-2); border-radius:10px; padding:1.2rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1.2rem; border-left:3px solid var(--primary);">
                                            <div style="width:36px; height:36px; border-radius:50%; background:rgba(var(--primary-rgb),0.1); display:flex; align-items:center; justify-content:center; color:var(--primary);">
                                                <i class="fas fa-user-injured"></i>
                                            </div>
                                            <div>
                                                <div style="font-size:1.2rem; font-weight:800; color:var(--text-primary);"><?= e($a['patient_name']) ?></div>
                                                <small style="font-weight:600; color:var(--text-muted);">PT ID: <?= e($a['pid']) ?> | <?= e($a['gender']) ?></small>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div style="background:rgba(0,0,0,0.02); border-radius:8px; padding:1.2rem; margin-bottom:1.5rem; font-style:italic; color:var(--text-secondary); font-size:1.2rem; line-height:1.5; border:1px solid var(--border);">
                                        "<?= e($a['message']) ?>"
                                    </div>

                                    <div style="display:flex; justify-content:space-between; align-items:center; pt-1.5rem; border-top:1px solid var(--border); padding-top:1.5rem;">
                                        <div style="display:flex; align-items:center; gap:.8rem;">
                                            <div style="width:30px; height:30px; border-radius:50%; background:var(--surface-3); display:flex; align-items:center; justify-content:center; font-size:.9rem; font-weight:800; color:var(--text-secondary);">
                                                <?= strtoupper(substr($a['triggering_nurse'], 0, 1)) ?>
                                            </div>
                                            <div style="font-size:1.1rem; font-weight:600; color:var(--text-muted);">
                                                Initiated by <span style="color:var(--text-secondary); font-weight:700;"><?= e($a['triggering_nurse']) ?></span>
                                            </div>
                                        </div>

                                        <div style="display:flex; gap:.8rem;">
                                            <?php if($is_active): ?>
                                                <button class="adm-btn adm-btn-warning" onclick="updateAlert(<?= $a['id'] ?>, 'Responded')" style="font-weight:800; font-size:1.1rem; padding:.6rem 1.4rem;">
                                                    <i class="fas fa-running"></i> I AM RESPONDING
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if($is_active || $is_responded): ?>
                                                <button class="adm-btn adm-btn-success" onclick="showResolveModal(<?= $a['id'] ?>)" style="font-weight:800; font-size:1.1rem; padding:.6rem 1.4rem;">
                                                    <i class="fas fa-check"></i> RESOLVE
                                                </button>
                                            <?php endif; ?>

                                            <?php if($is_resolved): ?>
                                                <div style="text-align:right;">
                                                    <div style="font-weight:800; color:var(--success); font-size:1.1rem;">
                                                        <i class="fas fa-check-double"></i> <?= strtoupper(e($a['status'])) ?>
                                                    </div>
                                                    <small style="color:var(--text-muted); font-weight:600;">By <?= e($a['resolver_name']) ?></small>
                                                </div>
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
  0% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 20px rgba(231, 76, 60, 0); }
  100% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
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
<div class="modal-bg" id="triggerModal">
    <div class="modal-box" style="max-width:550px; border:2px solid var(--danger); box-shadow:0 25px 50px -12px rgba(231,76,60,0.4);">
        <div class="modal-header" style="background:var(--danger); padding:1.8rem 2.5rem;">
            <h3 style="color:#fff; font-size:1.8rem; font-weight:800; letter-spacing:0.02em; margin:0;"><i class="fas fa-bullhorn" style="margin-right:.8rem;"></i> TRIGGER ALERT: <span id="lblAlertType" style="text-decoration:underline;">CODE BLUE</span></h3>
            <button class="modal-close" onclick="closeTriggerModal()" type="button" style="color:#fff; opacity:0.8;">×</button>
        </div>
        
        <div style="padding:2.5rem;">
            <form id="triggerForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="trigger_alert">
                <input type="hidden" name="alert_type" id="inpAlertType">
                
                <div style="background:rgba(231,76,60,0.05); border:1px solid rgba(231,76,60,0.1); border-radius:12px; padding:1.5rem; margin-bottom:2rem; color:var(--danger); font-weight:700; font-size:1.1rem; line-height:1.4;">
                    <i class="fas fa-exclamation-triangle" style="margin-right:.6rem;"></i> NOTICE: This will broadcast a facility-wide notification to all medical and security personnel.
                </div>

                <div class="form-group" style="margin-bottom:2rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase; letter-spacing:0.05em;">Incident Location</label>
                    <div style="position:relative;">
                        <i class="fas fa-map-marker-alt" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--danger);"></i>
                        <input type="text" class="form-control" name="location" id="inpLocation" value="<?= e($ward_assigned) ?>" required style="padding-left:3rem; font-weight:700; font-size:1.3rem; border:1.5px solid var(--border);">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:2rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase; letter-spacing:0.05em;">Patient Involved <small style="font-weight:500; opacity:0.6;">(Optional)</small></label>
                    <select class="form-control" name="patient_id" id="inpPatient" style="padding:.8rem; font-weight:600;">
                        <option value="">-- No specific patient / Unknown --</option>
                        <?php foreach($patients_in_ward as $p): ?>
                            <option value="<?= $p['id'] ?>">Bed <?= e($p['bed_number']) ?>: <?= e($p['name']) ?> (<?= e($p['patient_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:2.5rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase; letter-spacing:0.05em;">Emergency Details</label>
                    <textarea class="form-control" name="message" id="inpMessage" rows="2" placeholder="Brief details to help responders prepare..." required style="padding:1rem; font-size:1.2rem;"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="adm-btn adm-btn-ghost" onclick="closeTriggerModal()" style="font-weight:700;">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-danger pulse-fade" id="btnTriggerConfirm" style="padding:.8rem 3rem; font-weight:900; border-radius:12px; font-size:1.2rem; letter-spacing:0.05em;">
                        <i class="fas fa-satellite-dish"></i> BROADCAST ALERT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: RESOLVE EMERGENCY ALERT             -->
<!-- ========================================== -->
<div class="modal-bg" id="resolveModal">
    <div class="modal-box" style="max-width:480px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        <div class="modal-header" style="background:var(--success); padding:1.5rem 2rem;">
            <h3 style="color:#fff; font-size:1.5rem; font-weight:800; margin:0;"><i class="fas fa-check-circle" style="margin-right:.6rem;"></i> Clear Emergency Status</h3>
            <button class="modal-close" onclick="closeResolveModal()" type="button" style="color:#fff; opacity:0.8;">×</button>
        </div>
        
        <div style="padding:2rem;">
            <form id="resolveForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_alert">
                <input type="hidden" name="alert_id" id="resAlertId">
                <input type="hidden" name="status" id="resStatus">
                
                <p style="font-size:1.3rem; color:var(--text-secondary); margin-bottom:2rem; text-align:center; font-weight:600;">Select the appropriate outcome for this emergency event:</p>
                
                <div style="display:grid; grid-template-columns:1fr; gap:1.2rem;">
                    <button type="button" class="adm-btn adm-btn-success" onclick="submitResolve('Resolved')" style="padding:1.5rem; font-weight:800; font-size:1.3rem;">
                        <i class="fas fa-check-double"></i> SECURED / RESOLVED
                    </button>
                    <button type="button" class="adm-btn adm-btn-ghost" onclick="submitResolve('False Alarm')" style="padding:1.5rem; font-weight:800; font-size:1.3rem; border-color:var(--text-muted); color:var(--text-muted);">
                        <i class="fas fa-undo"></i> FALSE ALARM
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showTriggerModal(type) {
    document.getElementById('lblAlertType').textContent = type.toUpperCase();
    document.getElementById('inpAlertType').value = type;
    
    // Auto populate message based on type
    if(type === 'Code Blue') $('#inpMessage').val('Patient unresponsive / Potential Cardiac Arrest. Immediate ACLS required.');
    else if(type === 'Rapid Response') $('#inpMessage').val('Acute deterioration in clinical status. Requesting urgent medical review.');
    else if(type === 'Fall') $('#inpMessage').val('Patient fall detected. Post-fall assessment protocol initiated.');
    else $('#inpMessage').val('');
    
    document.getElementById('triggerModal').style.display = 'flex';
}
function closeTriggerModal() { document.getElementById('triggerModal').style.display = 'none'; }

function showResolveModal(id) {
    document.getElementById('resAlertId').value = id;
    document.getElementById('resolveModal').style.display = 'flex';
}
function closeResolveModal() { document.getElementById('resolveModal').style.display = 'none'; }

function submitResolve(statusVal) {
    document.getElementById('resStatus').value = statusVal;
    $('#resolveForm').submit();
}

function updateAlert(alertId, statusVal) {
    Swal.fire({
        title: 'Confirm Response?',
        text: "You are identifying yourself as a clinical responder to this emergency.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: 'var(--warning)',
        confirmButtonText: 'Yes, I am Responding'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../nurse/process_emergency.php', {
                action: 'update_alert',
                alert_id: alertId,
                status: statusVal,
                _csrf: '<?= generateCsrfToken() ?>'
            }, function(res) {
                if(res.success) {
                    Swal.fire({ icon: 'success', title: 'Response Noted', timer: 1000, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            }, 'json');
        }
    });
}

$(document).ready(function() {
    $('#triggerForm').on('submit', function(e) {
        e.preventDefault();
        const type = $('#inpAlertType').val();
        
        Swal.fire({
            title: 'BROADCAST EMERGENCY?',
            text: `You are about to trigger a facility-wide ${type} alert. This is a critical clinical notification.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--danger)',
            confirmButtonText: 'YES, BROADCAST NOW',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#btnTriggerConfirm');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> TRANSMITTING...');
                
                $.ajax({
                    url: '../nurse/process_emergency.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            closeTriggerModal();
                            Swal.fire({ icon: 'success', title: 'ALERT BROADCASTED', text: res.message, timer: 3000, showConfirmButton: false });
                            setTimeout(() => location.reload(), 3000);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Transmission Failed', text: res.message });
                            btn.prop('disabled', false).html('<i class="fas fa-bullhorn"></i> BROADCAST ALERT');
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'System Error', text: 'Critical communication failure.' });
                        btn.prop('disabled', false).html('<i class="fas fa-bullhorn"></i> BROADCAST ALERT');
                    }
                });
            }
        });
    });

    $('#resolveForm').on('submit', function(e) {
        e.preventDefault();
        const status = $('#resStatus').val();
        
        Swal.fire({
            title: 'Resolve Emergency?',
            text: `Confirm that this event is ${status.toLowerCase()} and clinical safety is restored.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: 'var(--success)',
            confirmButtonText: 'Yes, Secure Event'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../nurse/process_emergency.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            closeResolveModal();
                            Swal.fire({ icon: 'success', title: 'Event Secured', timer: 1500, showConfirmButton: false });
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                        }
                    }
                });
            }
        });
    });
});
</script>

