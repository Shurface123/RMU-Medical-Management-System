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

<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">
<style>
.staff-hero{display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;background:linear-gradient(135deg,#dc2626,#991b1b);border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap;position:relative;overflow:hidden;}
.staff-hero-avatar{width:72px;height:72px;border-radius:50%;border:3px solid rgba(255,255,255,.35);background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0;z-index:2;}
.staff-hero-info{z-index:2;}.staff-hero-info h2{font-size:2rem;font-weight:700;margin:0;}.staff-hero-info p{font-size:1.3rem;margin:.3rem 0 0;opacity:.85;}
.hero-bg-icon{position:absolute;right:-20px;bottom:-40px;font-size:15rem;opacity:.07;transform:rotate(-15deg);z-index:1;}
.stf-table{width:100%;border-collapse:collapse;font-size:1.15rem;}
.stf-table th{background:var(--surface-2);color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left;}
.stf-table td{padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle;}
.stf-table tr:hover td{background:var(--surface-2);}
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-ambulance"></i> Emergency Dispatch Hub</span>
        </div>
        <div class="adm-topbar-right">
            <div class="adm-topbar-datetime">
                <i class="far fa-calendar-alt"></i>
                <span><?php echo date('D, M d, Y'); ?></span>
            </div>
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?></div>
        </div>
    </div>

    <div class="adm-content" style="animation:fadeIn .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-ambulance hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-truck-medical"></i></div>
            <div class="staff-hero-info">
                <h2>Emergency Dispatch Command</h2>
                <p>Assign trips to drivers and monitor fleet movements in real-time.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <a href="/RMU-Medical-Management-System/php/Ambulence/ambulence.php" class="btn" style="background:rgba(255,255,255,0.15); color:#fff; border:1px solid rgba(255,255,255,0.3);"><i class="fas fa-car-side"></i> View Fleet</a>
                <button class="btn btn-primary" onclick="document.getElementById('dispModal').classList.add('active')" style="background:#fff; color:#dc2626; border:none; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                    <i class="fas fa-plus"></i> Dispatch Ambulance
                </button>
            </div>
        </div>

        <div class="adm-stats-grid">
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #ef4444, #dc2626);"><i class="fas fa-route"></i></div>
                <div class="adm-stat-label">Total Missions</div>
                <div class="adm-stat-value"><?php echo count($trips); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-history"></i> Lifetime trips</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #22c55e, #16a34a);"><i class="fas fa-check-circle"></i></div>
                <div class="adm-stat-label">Available Fleet</div>
                <div class="adm-stat-value"><?php echo count($ambulances); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-truck-medical"></i> Ready for dispatch</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #3b82f6, #2563eb);"><i class="fas fa-user-check"></i></div>
                <div class="adm-stat-label">Standby Drivers</div>
                <div class="adm-stat-value"><?php echo count($drivers); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-id-card"></i> Ready to move</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-satellite"></i></div>
                <div class="adm-stat-label">Active Missions</div>
                <div class="adm-stat-value">
                    <?php 
                        $active = array_filter($trips, fn($t) => in_array($t['trip_status'], ['assigned', 'en route', 'arrived']));
                        echo count($active);
                    ?>
                </div>
                <div class="adm-stat-footer"><i class="fas fa-signal"></i> Real-time tracking</div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="adm-alert adm-alert-success" style="margin-bottom:2rem; border-radius:12px;"><i class="fas fa-check-circle"></i> Ambulance dispatched successfully. Sent to driver's dashboard.</div>
        <?php endif; ?>

        <div class="adm-card shadow-sm" style="border-radius:20px; border:1px solid var(--border); overflow:hidden;">
            <div class="adm-card-header" style="padding: 1.8rem 2.5rem; background:var(--surface-2); border-bottom:1px solid var(--border);">
                <h3><i class="fas fa-route" style="color:var(--primary);"></i> Live Dispatch & Trip Logs</h3>
            </div>
            <div class="adm-table-wrap" style="padding:0;">
                <table class="stf-table">
                    <thead><tr><th>Dispatched</th><th>Driver & Vehicle</th><th>Route (Pickup ➔ Dropoff)</th><th>Purpose</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($trips)): ?><tr><td colspan="5" style="text-align:center;padding:5rem; color:var(--text-muted);">No trips logged.</td></tr>
                        <?php else:
                            foreach ($trips as $t):
                                $sc = $t['trip_status'] === 'completed' ? 'success' : ($t['trip_status'] === 'en route' ? 'info' : 'warning');
                        ?>
                        <tr>
                            <td style="white-space:nowrap;"><?php echo date('d M Y, g:i A', strtotime($t['created_at'])); ?></td>
                            <td>
                                <strong style="font-size:1.1rem; color:var(--text-primary);"><?php echo htmlspecialchars($t['driver_name']); ?></strong>
                                <div style="font-size:.9rem;color:var(--text-muted);"><i class="fas fa-truck-medical"></i> <?php echo htmlspecialchars($t['vehicle_number']); ?></div>
                            </td>
                            <td>
                                <div><span style="color:var(--success);"><i class="fas fa-map-marker-alt"></i></span> <?php echo htmlspecialchars($t['pickup_location']); ?></div>
                                <div style="margin-top:0.3rem;"><span style="color:var(--danger);"><i class="fas fa-map-pin"></i></span> <?php echo htmlspecialchars($t['destination'] ?: 'Hospital'); ?></div>
                            </td>
                            <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($t['trip_notes']); ?></td>
                            <td><span class="adm-badge" style="background:var(--<?php echo $sc; ?>-light); color:var(--<?php echo $sc; ?>); font-weight:700;"><?php echo ucfirst($t['trip_status']); ?></span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="adm-modal" id="dispModal">
    <div class="adm-modal-content">
        <div class="adm-modal-header" style="background: linear-gradient(135deg, #dc2626, #ef4444); color: #fff;">
            <h3><i class="fas fa-ambulance"></i> Emergency Dispatch</h3>
            <button class="btn btn-primary adm-modal-close" style="background:rgba(255,255,255,0.2);color:#fff;" onclick="document.getElementById('dispModal').classList.remove('active')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <div class="adm-modal-body">
            <form id="dispatchForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:2rem;margin-bottom:2.5rem;">
                    <div class="form-section">
                        <h4 style="margin-bottom:1.5rem;color:var(--primary);font-size:1.4rem;display:flex;align-items:center;gap:.8rem;">
                            <i class="fas fa-user-injured"></i> 1. Patient Info
                        </h4>
                        <div class="adm-form-group">
                            <label>Patient (Optional)</label>
                            <select name="patient_id" class="adm-search-input">
                                <option value="">-- Select Registered Patient --</option>
                                <?php foreach($patients as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="adm-form-group">
                            <label>Request Source *</label>
                            <select name="request_source" class="adm-search-input" required>
                                <option value="admin">Admin Dispatch</option>
                                <option value="doctor">Doctor Request</option>
                                <option value="nurse">Nurse Request</option>
                                <option value="walk-in">Emergency Call</option>
                            </select>
                        </div>
                        <div class="adm-form-group">
                            <label>Urgency Level *</label>
                            <select name="request_type" class="adm-search-input" required>
                                <option value="emergency">🚨 Emergency (Immediate)</option>
                                <option value="scheduled">⚠️ Urgent (Scheduled)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 style="margin-bottom:1.5rem;color:var(--primary);font-size:1.4rem;display:flex;align-items:center;gap:.8rem;">
                            <i class="fas fa-map-marked-alt"></i> 2. Location
                        </h4>
                        <div class="adm-form-group">
                            <label>Pickup Location *</label>
                            <input type="text" name="pickup_location" class="adm-search-input" required placeholder="Where from?">
                        </div>
                        <div class="adm-form-group">
                            <label>Destination *</label>
                            <input type="text" name="destination" class="adm-search-input" required value="RMU Medical Sickbay">
                        </div>
                        <div class="adm-form-group">
                            <label>Trip Purpose / Notes</label>
                            <textarea name="trip_notes" class="adm-search-input" rows="2" placeholder="Describe the emergency..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section" style="background:var(--surface-2);padding:2rem;border-radius:12px;margin-bottom:2.5rem;">
                    <h4 style="margin-bottom:1.5rem;color:var(--primary);font-size:1.4rem;display:flex;align-items:center;gap:.8rem;">
                        <i class="fas fa-truck-fast"></i> 3. Crew & Fleet
                    </h4>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                        <div class="adm-form-group">
                            <label>Assign Driver *</label>
                            <select name="driver_id" class="adm-search-input" required>
                                <option value="">-- Choose Driver --</option>
                                <?php foreach ($drivers as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="adm-form-group">
                            <label>Assign Vehicle *</label>
                            <select name="ambulance_id" class="adm-search-input" required>
                                <option value="">-- Choose Ambulance --</option>
                                <?php foreach ($ambulances as $a): ?>
                                    <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['vehicle_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="dispatch-timing" style="margin-bottom:2.5rem;">
                    <label style="display:block;margin-bottom:1rem;font-weight:600;color:var(--text-secondary);">Dispatch Timing</label>
                    <div style="display:flex;gap:2rem;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:.8rem;cursor:pointer;">
                            <input type="radio" name="dispatch_type" value="immediate" checked onchange="document.getElementById('schedGroupV2').style.display='none'"> 
                            <span style="font-weight:600;color:var(--danger);">Immediate Dispatch</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:.8rem;cursor:pointer;">
                            <input type="radio" name="dispatch_type" value="scheduled" onchange="document.getElementById('schedGroupV2').style.display='grid'"> 
                            <span>Schedule Later</span>
                        </label>
                    </div>
                </div>
                
                <div id="schedGroupV2" style="display:none;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2.5rem;background:var(--surface-2);padding:1.5rem;border-radius:12px;">
                    <div class="adm-form-group" style="margin:0;">
                        <label>Target Date</label>
                        <input type="date" name="sched_date" class="adm-search-input" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="adm-form-group" style="margin:0;">
                        <label>Target Time</label>
                        <input type="time" name="sched_time" class="adm-search-input">
                    </div>
                </div>

                <div style="display:flex;gap:1rem;">
                    <button type="submit" class="btn btn-danger" style="flex:2;padding:1.2rem;" <?php if(empty($drivers)||empty($ambulances)) echo 'disabled'; ?>><span class="btn-text">
                        <i class="fas fa-paper-plane"></i> EXECUTE DISPATCH
                    </span></button>
                    <button type="button" class="btn btn-ghost" style="flex:1;" onclick="document.getElementById('dispModal').classList.remove('active')"><span class="btn-text">Abort</span></button>
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