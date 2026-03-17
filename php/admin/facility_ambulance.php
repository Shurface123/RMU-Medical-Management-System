<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'ambulance';
$page_title = 'Ambulance Dispatch Control';
include '../includes/_sidebar.php';

// Removed old POST dispatcher, now handled via AJAX API in admin_ambulance_actions.php

// Fetch available drivers (status not 'On Trip')
$drivers = [];
$qd = mysqli_query($conn, "SELECT s.id, u.name FROM staff s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1 AND u.user_role = 'ambulance_driver' AND s.status != 'On Trip'");
if ($qd)
    while ($r = mysqli_fetch_assoc($qd))
        $drivers[] = $r;

// Fetch registered patients for dropdown
$patients = [];
$qp = mysqli_query($conn, "SELECT p.id, u.name FROM patients p JOIN users u ON p.user_id = u.id WHERE u.is_active = 1 ORDER BY u.name");
if ($qp) 
    while ($r = mysqli_fetch_assoc($qp)) $patients[] = $r;

// Fetch active ambulances
$ambulances = [];
$qa = mysqli_query($conn, "SELECT id, vehicle_number, ambulance_id, status FROM ambulances WHERE status = 'Available'");
if ($qa)
    while ($r = mysqli_fetch_assoc($qa))
        $ambulances[] = $r;

// Fetch recent trips
$trips = [];
$qt = mysqli_query($conn, "
    SELECT t.*, u.name as driver_name, a.vehicle_number 
    FROM ambulance_trips t 
    LEFT JOIN staff s ON t.driver_id = s.id 
    LEFT JOIN users u ON s.user_id = u.id 
    LEFT JOIN ambulances a ON t.vehicle_id = a.id 
    ORDER BY t.created_at DESC LIMIT 50
");
if ($qt)
    while ($r = mysqli_fetch_assoc($qt))
        $trips[] = $r;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-ambulance"></i> Emergency Dispatch Hub</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div>
                <h1>Ambulance Dispatch Hub</h1>
                <p>Assign trips to drivers and monitor fleet movements in real-time.</p>
            </div>
            <div style="display:flex;gap:1rem;">
                <a href="/RMU-Medical-Management-System/php/Ambulence/ambulence.php" class="adm-btn adm-btn-back"><i class="fas fa-car-side"></i> View Fleet (Vehicles)</a>
                <button class="adm-btn adm-btn-danger" onclick="document.getElementById('dispModal').classList.add('active')">
                    <i class="fas fa-siren-on"></i> Dispatch Ambulance
                </button>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Ambulance dispatched successfully. Sent to driver's dashboard.</div>
        <?php
endif; ?>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-route"></i> Live Dispatch & Trip Logs</h3>
            </div>
            <div class="adm-table-wrap" style="overflow-x: auto; width: 100%;">
                <table class="adm-table" style="width: 100%; min-width: 800px;">
                    <thead><tr><th>Dispatched</th><th>Driver & Vehicle</th><th>Route (Pickup ➔ Dropoff)</th><th>Purpose</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($trips)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;">No trips logged.</td></tr>
                        <?php
else:
    foreach ($trips as $t):
        $sc = $t['trip_status'] === 'completed' ? 'success' : ($t['trip_status'] === 'en route' ? 'info' : 'warning');
?>
                        <tr>
                            <td style="white-space:nowrap;"><?php echo date('d M Y, g:i A', strtotime($t['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($t['driver_name']); ?></strong>
                                <div style="font-size:.8rem;color:var(--text-muted);"><i class="fas fa-truck-medical"></i> <?php echo htmlspecialchars($t['vehicle_number']); ?></div>
                            </td>
                            <td>
                                <div><span style="color:var(--success);"><i class="fas fa-map-marker-alt"></i></span> <?php echo htmlspecialchars($t['pickup_location']); ?></div>
                                <div><span style="color:var(--danger);"><i class="fas fa-map-pin"></i></span> <?php echo htmlspecialchars($t['destination'] ?: 'Hospital'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($t['trip_notes']); ?></td>
                            <td><span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo ucfirst($t['trip_status']); ?></span></td>
                        </tr>
                        <?php
    endforeach;
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="adm-modal" id="dispModal">
    <div class="adm-modal-content" style="max-height:90vh;overflow-y:auto;">
        <div class="adm-modal-header" style="background:#dc2626;color:#fff;">
            <h3 style="color:#fff;"><i class="fas fa-siren-on"></i> Emergency Ambulance Dispatch</h3>
            <button class="adm-modal-close" style="color:#fff;" onclick="document.getElementById('dispModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <form id="dispatchForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <h4 style="margin-top:0;margin-bottom:1rem;color:var(--text-main);font-size:1rem;border-bottom:1px solid #e5e7eb;padding-bottom:0.5rem;"><i class="fas fa-user-injured" style="color:#dc2626;"></i> 1. Patient & Request Info</h4>
                
                <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <div class="adm-form-group" style="flex:1;min-width:200px;margin:0;">
                        <label>Patient (Optional if Walk-in/Unknown)</label>
                        <select name="patient_id" class="adm-search-input" id="patSelect">
                            <option value="">-- Select Registered Patient --</option>
                            <?php foreach($patients as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="adm-form-group" style="flex:1;min-width:200px;margin:0;">
                        <label>Request Source *</label>
                        <select name="request_source" class="adm-search-input" required>
                            <option value="admin">Admin Dispatch (Manual)</option>
                            <option value="doctor">Doctor Request</option>
                            <option value="nurse">Nurse Request</option>
                            <option value="walk-in">Walk-in / Emergency Call</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
                    <div class="adm-form-group" style="flex:1;min-width:200px;margin:0;">
                        <label>Urgency Level *</label>
                        <select name="request_type" class="adm-search-input" required style="font-weight:bold;">
                            <option value="emergency" style="color:#dc2626;">🚨 Emergency (Red)</option>
                            <option value="scheduled" style="color:#ca8a04;">⚠️ Urgent / Scheduled (Orange)</option>
                        </select>
                    </div>
                </div>

                <h4 style="margin-bottom:1rem;color:var(--text-main);font-size:1rem;border-bottom:1px solid #e5e7eb;padding-bottom:0.5rem;"><i class="fas fa-map-marked-alt" style="color:var(--primary);"></i> 2. Logistics & Route</h4>
                
                <div class="adm-form-group">
                    <label>Pickup Location *</label>
                    <input type="text" name="pickup_location" class="adm-search-input" required placeholder="Full exact address or internal Ward name">
                </div>
                
                <div class="adm-form-group">
                    <label>Destination / Dropoff *</label>
                    <input type="text" name="destination" class="adm-search-input" required value="RMU Medical Sickbay">
                </div>

                <div class="adm-form-group">
                    <label>Chief Complaint / Medical Reason *</label>
                    <textarea name="trip_notes" class="adm-search-input" rows="2" required placeholder="Brief description of emergency..."></textarea>
                </div>

                <h4 style="margin-bottom:1rem;color:var(--text-main);font-size:1rem;border-bottom:1px solid #e5e7eb;padding-bottom:0.5rem;"><i class="fas fa-truck-fast" style="color:#eab308;"></i> 3. Fleet & Crew Assignment</h4>
                
                <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
                    <div class="adm-form-group" style="flex:1;min-width:200px;margin:0;">
                        <label>Available Driver *</label>
                        <select name="driver_id" class="adm-search-input" required>
                            <option value="">-- Choose Driver --</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?> (Online)</option>
                            <?php
endforeach; ?>
                        </select>
                        <?php if(empty($drivers)): ?><span style="color:#dc2626;font-size:0.75rem;"><i class="fas fa-exclamation-triangle"></i> No drivers available!</span><?php endif; ?>
                    </div>

                    <div class="adm-form-group" style="flex:1;min-width:200px;margin:0;">
                        <label>Available Vehicle *</label>
                        <select name="ambulance_id" class="adm-search-input" required>
                            <option value="">-- Choose Ambulance --</option>
                            <?php foreach ($ambulances as $a): ?>
                                <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['vehicle_number']) . ' (' . htmlspecialchars($a['ambulance_id']) . ')'; ?></option>
                            <?php
endforeach; ?>
                        </select>
                        <?php if(empty($ambulances)): ?><span style="color:#dc2626;font-size:0.75rem;"><i class="fas fa-exclamation-triangle"></i> No vehicles available!</span><?php endif; ?>
                    </div>
                </div>

                <div class="adm-form-group" style="margin-bottom:1.5rem;">
                    <label>Dispatch Timing</label>
                    <div style="display:flex;gap:1.5rem;align-items:center;background:var(--bg-lite);padding:1rem;border-radius:6px;border:1px solid #e5e7eb;">
                        <label style="margin:0;display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                            <input type="radio" name="dispatch_type" value="immediate" checked onchange="document.getElementById('schedGroup').style.display='none'"> 
                            <span style="font-weight:600;color:#dc2626;">Immediate Dispatch (Now)</span>
                        </label>
                        <label style="margin:0;display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                            <input type="radio" name="dispatch_type" value="scheduled" onchange="document.getElementById('schedGroup').style.display='flex'"> 
                            <span>Scheduled Future Dispatch</span>
                        </label>
                    </div>
                </div>
                
                <div id="schedGroup" style="display:none;gap:1.5rem;background:var(--bg-lite);padding:1rem;border-radius:6px;border:1px solid #e5e7eb;margin-bottom:1.5rem;">
                    <div class="adm-form-group" style="flex:1;margin:0;">
                        <label>Target Date</label>
                        <input type="date" name="sched_date" class="adm-search-input" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="adm-form-group" style="flex:1;margin:0;">
                        <label>Target Time</label>
                        <input type="time" name="sched_time" class="adm-search-input">
                    </div>
                </div>

                <div style="display:flex;gap:1rem;">
                    <button type="submit" class="adm-btn adm-btn-danger" style="flex:1;font-size:1.1rem;padding:0.75rem;" <?php if(empty($drivers)||empty($ambulances)) echo 'disabled'; ?>>
                        <i class="fas fa-siren-on" style="margin-right:8px;"></i> EXECUTE DISPATCH
                    </button>
                    <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('dispModal').classList.remove('active')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" style="position:fixed;bottom:20px;right:20px;z-index:9999;"></div>

<script>
const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});

// Toast system
function showToast(msg, isSuccess=true) {
    const t = document.createElement('div');
    t.style.cssText = `background:${isSuccess?'var(--success)':'var(--danger)'};color:#fff;padding:1rem 1.5rem;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);margin-top:10px;display:flex;align-items:center;gap:10px;animation:slideIn 0.3s ease-out forwards;`;
    t.innerHTML = `<i class="fas ${isSuccess?'fa-check-circle':'fa-exclamation-triangle'}"></i> ${msg}`;
    document.getElementById('toastContainer').appendChild(t);
    setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.remove(),300); }, 4000);
}

document.getElementById('dispatchForm').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const origHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
    
    const fd = new FormData(e.target);
    
    try {
        const res = await fetch('admin_ambulance_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            showToast(data.message, true);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(data.message, false);
            btn.innerHTML = origHTML;
            btn.disabled = false;
        }
    } catch(err) { 
        showToast('Network error verifying dispatch constraints.', false); 
        console.error(err);
        btn.innerHTML = origHTML;
        btn.disabled = false;
    }
});
</script>
<style>
@keyframes slideIn { from{transform:translateX(100%);opacity:0;} to{transform:translateX(0);opacity:1;} }
</style>
</body>
</html>