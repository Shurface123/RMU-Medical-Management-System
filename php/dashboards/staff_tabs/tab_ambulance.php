<?php
/**
 * tab_ambulance.php — Module 3: Ambulance Driver Module (Modernized)
 */
if ($staffRole !== 'ambulance_driver') { echo '<div id="sec-ambulance" class="dash-section"></div>'; return; }

$trip_requests = dbSelect($conn,"SELECT * FROM ambulance_requests WHERE status='Pending' ORDER BY emergency_type DESC, id ASC LIMIT 20");
$active_trip = dbRow($conn,"SELECT * FROM ambulance_trips WHERE driver_id=? AND trip_status NOT IN ('completed','cancelled') ORDER BY trip_id DESC LIMIT 1","i",[$staff_id]);
$vehicle = dbRow($conn,"SELECT * FROM vehicles WHERE assigned_driver_id=? LIMIT 1","i",[$staff_id]);
$recent_trips = dbSelect($conn,"SELECT * FROM ambulance_trips WHERE driver_id=? ORDER BY created_at DESC LIMIT 50","i",[$staff_id]);
?>
<div id="sec-ambulance" class="dash-section">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-ambulance" style="color:var(--role-accent);"></i> Mission Control</h2>
            <p style="font-size:1.35rem;color:var(--text-muted);margin:0.5rem 0 0;">Manage emergency responses and vehicle logistics</p>
        </div>
        <div style="display:flex;gap:1rem;">
            <button class="btn btn-outline" onclick="location.reload()"><i class="fas fa-sync-alt mr-2"></i> Sync Cloud</button>
            <?php if($vehicle): ?>
            <button class="btn btn-primary" onclick="openModal('fuelModal')"><i class="fas fa-gas-pump mr-2"></i> Refuel Log</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Mission Center -->
    <?php if($active_trip):
        $stages = ['accepted'=>'Mission start','en route'=>'En Route','patient onboard'=>'Patient Secured','arrived'=>'Arrival','completed'=>'Finalized'];
        $cur_st = strtolower($active_trip['trip_status']);
    ?>
    <div class="mission-banner card" style="border-top:4px solid var(--role-accent); background:rgba(255,255,255,0.03); backdrop-filter:blur(15px); margin-bottom:3rem;">
        <div style="padding:2.5rem 3rem; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:2rem; border-bottom:1px solid var(--border);">
            <div style="flex:1; min-width:300px;">
                <div style="display:flex; align-items:center; gap:1.2rem; margin-bottom:1rem;">
                    <span class="mission-pulse"></span>
                    <h3 style="font-size:1.8rem; font-weight:800; margin:0;">Active Mission #<?= $active_trip['trip_id'] ?></h3>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:2rem; margin-top:2rem;">
                    <div class="mission-meta"><span>Patient Focus</span><strong><?= e($active_trip['patient_name']??'Emergency Case') ?></strong></div>
                    <div class="mission-meta"><span>Pickup Point</span><strong><?= e($active_trip['pickup_location']??'—') ?></strong></div>
                    <div class="mission-meta"><span>Route Destination</span><strong><?= e($active_trip['destination']??'—') ?></strong></div>
                </div>
            </div>
            
            <div class="mission-pipeline">
                <?php foreach(['en route','patient onboard','arrived','completed'] as $st):
                    $is_done = false; // Logic to determine if stage is done
                    $is_active = ($cur_st === $st);
                    $s_icons = ['en route'=>'fa-car-side','patient onboard'=>'fa-user-injured','arrived'=>'fa-hospital-alt','completed'=>'fa-check-double'];
                ?>
                <div class="pipe-step <?= $is_active?'active':'' ?>" onclick="updateTripStatus(<?= $active_trip['trip_id'] ?>,'<?= $st ?>')">
                    <div class="pipe-node"><i class="fas <?= $s_icons[$st] ?>"></i></div>
                    <span class="pipe-lbl"><?= ucwords($st) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="padding:1.5rem 3rem; background:var(--surface-2); display:flex; gap:1.5rem;">
             <button class="btn btn-outline btn-sm" onclick="openModal('tripNotesModal')"><i class="fas fa-sticky-note mr-2"></i> Mission Notes</button>
             <?php if(!empty($active_trip['patient_condition'])): ?>
             <div style="margin-left:auto; font-size:1.2rem; display:flex; align-items:center; gap:.8rem;">
                <i class="fas fa-heartbeat text-danger"></i> <strong>Condition:</strong> <?= e($active_trip['patient_condition']) ?>
             </div>
             <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-bottom:3rem;">
        <!-- Regional Requests -->
        <div class="card">
            <div class="card-header" style="padding:1.8rem 2.5rem; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:1.6rem; font-weight:800;"><i class="fas fa-siren text-danger mr-2"></i> Area Requests</h3>
                <?php if(count($trip_requests)>0): ?><span class="badge" style="background:#EB5757; color:#fff;"><?= count($trip_requests) ?> NEW</span><?php endif; ?>
            </div>
            <div style="padding:1.5rem 2.5rem; max-height:500px; overflow-y:auto;">
                <?php if(empty($trip_requests)): ?>
                <div style="padding:4rem; text-align:center; color:var(--text-muted);">
                    <i class="fas fa-satellite-dish" style="font-size:3rem; opacity:.2; display:block; margin-bottom:1.5rem;"></i>
                    <p style="font-weight:700;">Scanning for signals...</p>
                    <span style="font-size:1.1rem;">No active emergency calls in your vicinity.</span>
                </div>
                <?php else: foreach($trip_requests as $r):
                    $urg = strtolower($r['emergency_type']??'normal');
                    $urg_cl = ['critical'=>'#EB5757','urgent'=>'#F2994A','normal'=>'#2F80ED'][$urg]??'#2F80ED';
                ?>
                <div class="req-card" style="border-left:4px solid <?= $urg_cl ?>; background:var(--surface-2); border-radius:14px; padding:1.8rem; margin-bottom:1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
                        <div>
                            <strong style="font-size:1.4rem; font-weight:800;"><?= e($r['patient_name']??'Case #'.$r['id']) ?></strong>
                            <p style="font-size:1.15rem; color:var(--text-muted); margin-top:.3rem;"><?= e($r['condition_notes']??'Emergency response requested') ?></p>
                        </div>
                        <span class="p-badge" style="background:<?= $urg_cl ?>22; color:<?= $urg_cl ?>;"><?= ucfirst($urg) ?></span>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.6rem; font-size:1.2rem; color:var(--text-secondary); margin-bottom:1.5rem;">
                        <span><i class="fas fa-map-marker-alt text-muted mr-2"></i> <?= e($r['pickup_location']) ?></span>
                        <span><i class="fas fa-hospital text-muted mr-2"></i> <?= e($r['destination']) ?></span>
                    </div>
                    <div style="display:flex; gap:1rem;">
                        <button class="btn btn-primary btn-sm flex-1" onclick="acceptTrip(<?= $r['id'] ?>)"><i class="fas fa-check mr-2"></i> Accept Case</button>
                        <button class="btn btn-outline btn-sm" onclick="openRejectModal(<?= $r['id'] ?>)"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Logistics Hub -->
        <div class="card">
            <div class="card-header" style="padding:1.8rem 2.5rem;">
                <h3 style="font-size:1.6rem; font-weight:800;"><i class="fas fa-truck-monster mr-2"></i> Vehicle Logistics</h3>
            </div>
            <div style="padding:2.5rem;">
                <?php if($vehicle): ?>
                <div style="margin-bottom:2.5rem; text-align:center;">
                    <div style="width:70px; height:70px; border-radius:20px; background:var(--surface-2); display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem; font-size:3rem; color:var(--role-accent);">
                        <i class="fas fa-truck-moving"></i>
                    </div>
                    <h4 style="font-size:1.8rem; font-weight:900; margin:0;"><?= e($vehicle['make_model']??'Ambulance') ?></h4>
                    <span class="badge" style="background:var(--role-accent)22; color:var(--role-accent); margin-top:.8rem;"><?= e($vehicle['registration_number']) ?></span>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2.5rem;">
                    <div class="log-stat"><span>Systems</span><strong class="text-success"><?= ucfirst($vehicle['status']??'Active') ?></strong></div>
                    <div class="log-stat"><span>Last Audit</span><strong><?= $vehicle['last_service_date']??'—' ?></strong></div>
                    <div class="log-stat"><span>Safety Expiry</span><strong><?= $vehicle['insurance_expiry']??'—' ?></strong></div>
                    <div class="log-stat"><span>Engine State</span><strong>Operational</strong></div>
                </div>
                
                <div style="display:flex; gap:1rem;">
                    <button class="btn btn-outline btn-wide" onclick="openModal('vehicleIssueModal')"><i class="fas fa-exclamation-triangle mr-2"></i> Report Fault</button>
                </div>
                <?php else: ?>
                <div style="padding:5rem; text-align:center; color:var(--text-muted);">
                    <i class="fas fa-key" style="font-size:4rem; opacity:.1; display:block; margin-bottom:2rem;"></i>
                    <p style="font-size:1.3rem;">No logistics assigned.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Operation Logs -->
    <div class="card">
        <div class="card-header" style="background:var(--surface-2); padding:1.5rem 2.5rem;">
            <h3 style="font-size:1.5rem; font-weight:800;"><i class="fas fa-clipboard-list mr-2"></i> Mission Archives</h3>
        </div>
        <div style="padding:1.5rem 2.5rem;">
            <table id="tblHistory" class="display responsive nowrap" style="width:100%">
                <thead><tr><th>Trip ID</th><th>Subject</th><th>Route Matrix</th><th>Timeline</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach($recent_trips as $t): 
                        $ts = strtolower($t['trip_status']??'pending');
                        $tc = ['completed'=>'#27AE60','cancelled'=>'#EB5757','en route'=>'#2F80ED','accepted'=>'#F2C94C'][$ts]??'var(--primary)';
                    ?>
                    <tr>
                        <td style="font-weight:800; color:var(--role-accent);">#<?= $t['trip_id'] ?></td>
                        <td><div style="font-weight:700;"><?= e($t['patient_name']??'—') ?></div></td>
                        <td>
                            <div style="font-size:1.15rem; color:var(--text-secondary);"><i class="fas fa-long-arrow-alt-right mr-1 opacity-50"></i> <?= e($t['pickup_location']) ?> → <?= e($t['destination']) ?></div>
                        </td>
                        <td><span style="font-size:1.15rem; color:var(--text-muted);"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></span></td>
                        <td><span class="p-badge" style="background:<?= $tc ?>22; color:<?= $tc ?>;"><?= ucwords($ts) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
.mission-pulse { width:12px; height:12px; background:#EB5757; border-radius:50%; box-shadow:0 0 0 0 rgba(235, 87, 87, 0.7); animation: m-pulse 1.5s infinite; }
@keyframes m-pulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(235, 87, 87, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(235, 87, 87, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(235, 87, 87, 0); } }

.mission-meta span { display:block; font-size:1.1rem; font-weight:800; text-transform:uppercase; color:var(--text-muted); margin-bottom:.5rem; }
.mission-meta strong { font-size:1.6rem; font-weight:800; color:var(--text-primary); }

.mission-pipeline { display:flex; gap:2.5rem; align-items:center; }
.pipe-step { display:flex; flex-direction:column; align-items:center; gap:1rem; cursor:pointer; opacity:.4; transition:.3s; }
.pipe-step.active { opacity:1; }
.pipe-step.active .pipe-node { background:var(--role-accent); color:#fff; transform:scale(1.1); box-shadow:0 8px 20px color-mix(in srgb, var(--role-accent) 40%, transparent); }
.pipe-node { width:52px; height:52px; border-radius:18px; background:var(--surface-2); display:flex; align-items:center; justify-content:center; font-size:2rem; transition:.3s; }
.pipe-lbl { font-size:1.1rem; font-weight:800; text-transform:uppercase; white-space:nowrap; }

.req-card { transition: .3s; }
.req-card:hover { transform: translateX(5px); box-shadow: var(--shadow-sm); }
.log-stat { background:var(--surface-2); padding:1.2rem; border-radius:14px; }
.log-stat span { display:block; font-size:1.05rem; font-weight:800; text-transform:uppercase; color:var(--text-muted); margin-bottom:.3rem; }
.log-stat strong { font-size:1.4rem; font-weight:800; }
</style>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#tblHistory').DataTable({
            responsive: true,
            pageLength: 10,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: { search: "_INPUT_", searchPlaceholder: "Search missions..." }
        });
    }
});

async function acceptTrip(reqId){
    const res = await doAction({action:'accept_trip_request',request_id:reqId}, 'Communication established. Mission accepted.');
    if(res) setTimeout(()=>location.reload(), 1200);
}
async function updateTripStatus(tripId, status){
    showToast('Transmitting telemetry...', 'info');
    const res = await doAction({action:'update_trip_status',trip_id:tripId,trip_status:status}, 'Status update protocol successful.');
    if(res) setTimeout(()=>location.reload(), 1000);
}
async function submitVehicleIssue(){
    const fd = new FormData(document.getElementById('frmVehicleIssue'));
    const res = await doAction(fd, 'Fault log transmitted to maintenance hub.');
    if(res) { closeModal('vehicleIssueModal'); }
}
async function submitFuel(){
    const fd = new FormData(document.getElementById('frmFuel'));
    const res = await doAction(fd, 'Resource consumption logged.');
    if(res) { closeModal('fuelModal'); }
}
</script>
