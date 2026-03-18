<?php
/**
 * tab_ambulance.php — Module 3: Ambulance Driver Module
 */
if ($staffRole !== 'ambulance_driver') { echo '<div id="sec-ambulance" class="dash-section"></div>'; return; }

// Pending trip requests
$trip_requests = dbSelect($conn,"SELECT * FROM ambulance_requests WHERE status='Pending' ORDER BY emergency_type DESC, id ASC LIMIT 20");
// Active trips for THIS driver
$active_trip = dbRow($conn,"SELECT * FROM ambulance_trips WHERE driver_id=? AND trip_status NOT IN ('completed','cancelled') ORDER BY trip_id DESC LIMIT 1","i",[$staff_id]);
// Assigned vehicle
$vehicle = dbRow($conn,"SELECT * FROM vehicles WHERE assigned_driver_id=? LIMIT 1","i",[$staff_id]);
// Recent trips
$recent_trips = dbSelect($conn,"SELECT * FROM ambulance_trips WHERE driver_id=? ORDER BY created_at DESC LIMIT 15","i",[$staff_id]);
// Fuel logs
$fuel_logs = dbSelect($conn,"SELECT * FROM vehicle_fuel_logs WHERE logged_by_staff_id=? ORDER BY logged_at DESC LIMIT 10","i",[$staff_id]);
?>
<div id="sec-ambulance" class="dash-section">
    <h2 style="font-size:2.2rem;font-weight:700;margin-bottom:2.5rem;"><i class="fas fa-ambulance" style="color:var(--role-accent);"></i> Trip Manager</h2>

    <!-- Active Trip Banner -->
    <?php if($active_trip): ?>
    <div class="card" style="border:2px solid var(--role-accent);margin-bottom:2rem;">
        <div class="card-header" style="background:var(--role-accent-light);">
            <h3><i class="fas fa-route"></i> Active Trip #<?=$active_trip['trip_id']?></h3>
            <span class="badge" style="background:var(--role-accent);color:#fff;"><?=ucwords($active_trip['trip_status'])?></span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem;">
                <div><span class="form-group label">Patient</span><strong style="font-size:1.5rem;display:block;"><?=e($active_trip['patient_name']??'—')?></strong></div>
                <div><span class="form-group label">Pickup</span><strong style="font-size:1.5rem;display:block;"><?=e($active_trip['pickup_location']??'—')?></strong></div>
                <div><span class="form-group label">Destination</span><strong style="font-size:1.5rem;display:block;"><?=e($active_trip['destination']??'—')?></strong></div>
                <div><span class="form-group label">Condition</span><strong style="font-size:1.5rem;display:block;"><?=e($active_trip['patient_condition']??'—')?></strong></div>
            </div>

            <!-- Status Pipeline -->
            <div style="margin-bottom:2rem;">
                <p style="font-weight:600;margin-bottom:1rem;color:var(--text-secondary);">UPDATE TRIP STATUS</p>
                <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                    <?php $statuses=['en route'=>'fa-car','patient onboard'=>'fa-user-plus','arrived'=>'fa-hospital','completed'=>'fa-check-circle']; foreach($statuses as $st=>$icon):
                        $disabled=($active_trip['trip_status']===$st);
                    ?>
                    <button class="btn <?=$disabled?'btn-primary':'btn-outline'?>" <?=$disabled?'disabled':''?>
                        onclick="updateTripStatus(<?=$active_trip['trip_id']?>,'<?=$st?>')">
                        <i class="fas <?=$icon?>"></i> <?=ucwords($st)?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                <button class="btn btn-outline" onclick="openModal('tripNotesModal')"><i class="fas fa-sticky-note"></i> Add Notes</button>
                <?php if($vehicle): ?>
                <button class="btn btn-outline" onclick="openModal('fuelModal')"><i class="fas fa-gas-pump"></i> Log Fuel</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">
        <!-- Pending Requests -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-siren-on"></i> Trip Requests</h3>
                <?php if(count($trip_requests)>0): ?><span class="badge badge-urgent"><?=count($trip_requests)?> pending</span><?php endif; ?>
            </div>
            <div class="card-body" style="padding:1rem;">
            <?php if(empty($trip_requests)): ?>
                <p style="text-align:center;padding:3rem;color:var(--text-muted);">No pending requests.</p>
            <?php else: foreach($trip_requests as $r):
                $urg=$r['emergency_type']??'normal';
                $urg_c=['critical'=>'var(--danger)','urgent'=>'var(--warning)','normal'=>'var(--info)'][$urg]??'var(--info)';
            ?>
            <div style="border:1.5px solid <?=$urg_c?>;border-radius:12px;padding:1.5rem;margin-bottom:1rem;background:color-mix(in srgb,<?=$urg_c?> 8%,#fff 92%);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
                    <div>
                        <strong style="font-size:1.4rem;"><?=e($r['patient_name']??'Unknown Patient')?></strong>
                        <p style="font-size:1.2rem;color:var(--text-muted);margin:.2rem 0;"><?=e($r['condition_notes']??'—')?></p>
                    </div>
                    <span class="badge" style="background:color-mix(in srgb,<?=$urg_c?> 15%,#fff 85%);color:<?=$urg_c?>;"><?=ucfirst($urg)?></span>
                </div>
                <p style="font-size:1.2rem;margin-bottom:.5rem;"><i class="fas fa-map-pin" style="width:14px;color:var(--text-muted);"></i> <?=e($r['pickup_location']??'—')?></p>
                <p style="font-size:1.2rem;margin-bottom:1rem;"><i class="fas fa-hospital" style="width:14px;color:var(--text-muted);"></i> <?=e($r['destination']??'—')?></p>
                <p style="font-size:1.1rem;color:var(--text-muted);margin-bottom:1rem;"><i class="far fa-clock"></i> <?=date('d M, H:i',strtotime($r['requested_at']??'now'))?></p>
                <div style="display:flex;gap:.8rem;">
                    <button class="btn btn-success btn-sm" style="flex:1;" onclick="acceptTrip(<?=$r['id']?>)"><i class="fas fa-check"></i> Accept</button>
                    <button class="btn btn-danger btn-sm" style="flex:1;" onclick="openRejectModal(<?=$r['id']?>)"><i class="fas fa-times"></i> Reject</button>
                </div>
            </div>
            <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- My Vehicle -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-truck"></i> My Vehicle</h3></div>
            <div class="card-body">
            <?php if($vehicle): ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                    <?php $vfields=['Registration'=>'registration_number','Make/Model'=>'make_model','Status'=>'status','Last Service'=>'last_service_date','Insurance Expiry'=>'insurance_expiry'];
                    foreach($vfields as $lbl=>$col): if($col==='status'): ?>
                    <div><span style="font-size:1.1rem;color:var(--text-muted);display:block;margin-bottom:.3rem;"><?=$lbl?></span>
                        <span class="badge" style="background:<?=$vehicle[$col]==='active'?'var(--success-light)':'var(--warning-light)'?>;color:<?=$vehicle[$col]==='active'?'var(--success)':'var(--warning)'?>;"><?=ucfirst(e($vehicle[$col]??'—'))?></span></div>
                    <?php else: ?>
                    <div><span style="font-size:1.1rem;color:var(--text-muted);display:block;margin-bottom:.3rem;"><?=$lbl?></span>
                        <strong><?=e($vehicle[$col]??'—')?></strong></div>
                    <?php endif; endforeach; ?>
                </div>
                <div style="display:flex;gap:1rem;margin-top:1.5rem;flex-wrap:wrap;">
                    <button class="btn btn-outline btn-sm" onclick="openModal('vehicleIssueModal')"><i class="fas fa-exclamation-triangle"></i> Report Issue</button>
                    <button class="btn btn-outline btn-sm" onclick="openModal('fuelModal')"><i class="fas fa-gas-pump"></i> Log Fuel</button>
                </div>
            <?php else: ?>
                <p style="text-align:center;padding:3rem;color:var(--text-muted);">No vehicle currently assigned to you.</p>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Trip History -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-history"></i> Trip History</h3></div>
        <div class="card-body-flush">
        <?php if(empty($recent_trips)): ?>
            <p style="text-align:center;padding:3rem;color:var(--text-muted);">No trips recorded yet.</p>
        <?php else: ?>
        <table class="stf-table">
            <thead><tr><th>#</th><th>Patient</th><th>Pickup</th><th>Destination</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($recent_trips as $t):
                $ts=$t['trip_status']??'pending';
                $tc=['completed'=>'var(--success)','cancelled'=>'var(--danger)','en route'=>'var(--info)','accepted'=>'var(--warning)'][$ts]??'var(--primary)';
            ?>
            <tr>
                <td>#<?=$t['trip_id']?></td>
                <td><?=e($t['patient_name']??'—')?></td>
                <td><?=e($t['pickup_location']??'—')?></td>
                <td><?=e($t['destination']??'—')?></td>
                <td><?=date('d M Y, H:i',strtotime($t['created_at']))?></td>
                <td><span class="badge" style="background:color-mix(in srgb,<?=$tc?> 15%,#fff 85%);color:<?=$tc?>;"><?=ucwords($ts)?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal-bg" id="rejectModal">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <h3><i class="fas fa-times-circle" style="color:var(--danger);"></i> Reject Request</h3>
            <button class="modal-close" onclick="closeModal('rejectModal')"><i class="fas fa-times"></i></button>
        </div>
        <input type="hidden" id="reject_req_id">
        <div class="form-group"><label>Reason (Required)</label><textarea id="reject_reason" class="form-control" rows="3" placeholder="Provide reason for rejection..."></textarea></div>
        <button class="btn btn-danger btn-wide" onclick="submitReject()"><i class="fas fa-times"></i> Confirm Rejection</button>
    </div>
</div>

<!-- Report Vehicle Issue Modal -->
<div class="modal-bg" id="vehicleIssueModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle" style="color:var(--warning);"></i> Report Vehicle Issue</h3>
            <button class="modal-close" onclick="closeModal('vehicleIssueModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmVehicleIssue" onsubmit="event.preventDefault();submitVehicleIssue();">
            <input type="hidden" name="action" value="report_vehicle_issue">
            <input type="hidden" name="vehicle_id" value="<?=$vehicle['id']??0?>">
            <div class="form-group"><label>Issue Description *</label><textarea name="description" class="form-control" rows="4" required placeholder="Describe the issue in detail..."></textarea></div>
            <div class="form-group"><label>Photo (Optional)</label><input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png"></div>
            <button type="submit" class="btn btn-primary btn-wide" id="btnVehicleIssue"><i class="fas fa-paper-plane"></i> Report Issue</button>
        </form>
    </div>
</div>

<!-- Fuel Log Modal -->
<div class="modal-bg" id="fuelModal">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <h3><i class="fas fa-gas-pump" style="color:var(--role-accent);"></i> Log Fuel Top-up</h3>
            <button class="modal-close" onclick="closeModal('fuelModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmFuel" onsubmit="event.preventDefault();submitFuel();">
            <input type="hidden" name="action" value="log_fuel">
            <input type="hidden" name="vehicle_id" value="<?=$vehicle['id']??0?>">
            <div class="form-row">
                <div class="form-group"><label>Litres *</label><input type="number" name="litres" step="0.01" class="form-control" required min="0"></div>
                <div class="form-group"><label>Cost (GHS) *</label><input type="number" name="cost" step="0.01" class="form-control" required min="0"></div>
            </div>
            <div class="form-group"><label>Mileage at Top-up (km)</label><input type="number" name="mileage" step="0.1" class="form-control" min="0"></div>
            <button type="submit" class="btn btn-primary btn-wide" id="btnFuel"><i class="fas fa-plus"></i> Log Fuel</button>
        </form>
    </div>
</div>

<!-- Trip Notes Modal -->
<div class="modal-bg" id="tripNotesModal">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <h3><i class="fas fa-sticky-note" style="color:var(--role-accent);"></i> Add Trip Notes</h3>
            <button class="modal-close" onclick="closeModal('tripNotesModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="form-group"><label>Notes</label><textarea id="tripNotesText" class="form-control" rows="4" placeholder="Any additional notes for this trip..."></textarea></div>
        <button class="btn btn-primary btn-wide" onclick="saveNotes()"><i class="fas fa-save"></i> Save Notes</button>
    </div>
</div>

<script>
async function acceptTrip(reqId){
    if(!confirmAction('Accept this trip request?')) return;
    const res=await doAction({action:'accept_trip_request',request_id:reqId},'Trip accepted successfully!');
    if(res) setTimeout(()=>location.reload(),800);
}
function openRejectModal(reqId){ document.getElementById('reject_req_id').value=reqId; openModal('rejectModal'); }
async function submitReject(){
    const reason=document.getElementById('reject_reason').value.trim();
    if(!reason){ showToast('Please provide a reason.','error'); return; }
    const res=await doAction({action:'reject_trip_request',request_id:document.getElementById('reject_req_id').value,reason});
    if(res){ closeModal('rejectModal'); setTimeout(()=>location.reload(),800); }
}
async function updateTripStatus(tripId, status){
    if(status==='completed' && !confirmAction('Mark this trip as COMPLETED?')) return;
    const res=await doAction({action:'update_trip_status',trip_id:tripId,trip_status:status});
    if(res) setTimeout(()=>location.reload(),800);
}
async function submitVehicleIssue(){
    const btn=document.getElementById('btnVehicleIssue'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmVehicleIssue'));
    const res=await doAction(fd,'Issue reported to maintenance team!');
    btn.innerHTML='<i class="fas fa-paper-plane"></i> Report Issue'; btn.disabled=false;
    if(res){ closeModal('vehicleIssueModal'); document.getElementById('frmVehicleIssue').reset(); }
}
async function submitFuel(){
    const btn=document.getElementById('btnFuel'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmFuel'));
    const res=await doAction(fd,'Fuel log recorded!');
    btn.innerHTML='<i class="fas fa-plus"></i> Log Fuel'; btn.disabled=false;
    if(res){ closeModal('fuelModal'); document.getElementById('frmFuel').reset(); }
}
async function saveNotes(){
    const notes=document.getElementById('tripNotesText').value;
    if(!notes) return;
    const res=await doAction({action:'update_trip_status',trip_id:<?=$active_trip['trip_id']??0?>,trip_status:'<?=e($active_trip['trip_status']??'accepted')?>',notes});
    if(res){ closeModal('tripNotesModal'); showToast('Notes saved.','success'); }
}
</script>
