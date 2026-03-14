<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'ambulance';
$page_title  = 'Ambulance Dispatch Control';
include '../includes/_sidebar.php';

// Quick DB Insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'dispatch') {
    $driver_id = (int)$_POST['driver_id'];
    $amb_id    = (int)$_POST['ambulance_id'];
    $pickup    = trim($_POST['pickup']);
    $dropoff   = trim($_POST['dropoff']);
    $reason    = trim($_POST['reason']);
    
    // Add to ambulance_trips
    $sql = "INSERT INTO ambulance_trips (driver_id, ambulance_id, trip_start_location, trip_end_location, purpose, status, start_time, created_at) VALUES (?, ?, ?, ?, ?, 'en_route', NOW(), NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisss", $driver_id, $amb_id, $pickup, $dropoff, $reason);
    mysqli_stmt_execute($stmt);
    
    // Update ambulance status
    mysqli_query($conn, "UPDATE ambulances SET status = 'On Duty' WHERE id = $amb_id");
    
    mysqli_stmt_close($stmt);
    header("Location: facility_ambulance.php?success=1");
    exit();
}

// Fetch available drivers
$drivers = [];
$qd = mysqli_query($conn, "SELECT s.id, u.name FROM staff s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1 AND u.user_role = 'ambulance_driver'");
if ($qd) while ($r = mysqli_fetch_assoc($qd)) $drivers[] = $r;

// Fetch active ambulances
$ambulances = [];
$qa = mysqli_query($conn, "SELECT id, vehicle_number, ambulance_id, status FROM ambulances WHERE status = 'Available'");
if ($qa) while ($r = mysqli_fetch_assoc($qa)) $ambulances[] = $r;

// Fetch recent trips
$trips = [];
$qt = mysqli_query($conn, "
    SELECT t.*, u.name as driver_name, a.vehicle_number 
    FROM ambulance_trips t 
    LEFT JOIN staff s ON t.driver_id = s.id 
    LEFT JOIN users u ON s.user_id = u.id 
    LEFT JOIN ambulances a ON t.ambulance_id = a.id 
    ORDER BY t.created_at DESC LIMIT 50
");
if ($qt) while ($r = mysqli_fetch_assoc($qt)) $trips[] = $r;
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
        <?php endif; ?>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-route"></i> Live Dispatch & Trip Logs</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Dispatched</th><th>Driver & Vehicle</th><th>Route (Pickup ➔ Dropoff)</th><th>Purpose</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($trips)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;">No trips logged.</td></tr>
                        <?php else: foreach ($trips as $t): 
                            $sc = $t['status']==='completed'?'success':($t['status']==='en_route'?'info':'warning');
                        ?>
                        <tr>
                            <td style="white-space:nowrap;"><?php echo date('d M Y, g:i A', strtotime($t['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($t['driver_name']); ?></strong>
                                <div style="font-size:.8rem;color:var(--text-muted);"><i class="fas fa-truck-medical"></i> <?php echo htmlspecialchars($t['vehicle_number']); ?></div>
                            </td>
                            <td>
                                <div><span style="color:var(--success);"><i class="fas fa-map-marker-alt"></i></span> <?php echo htmlspecialchars($t['trip_start_location']); ?></div>
                                <div><span style="color:var(--danger);"><i class="fas fa-map-pin"></i></span> <?php echo htmlspecialchars($t['trip_end_location']?:'Hospital'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($t['purpose']); ?></td>
                            <td><span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo ucfirst(str_replace('_',' ',$t['status'])); ?></span></td>
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
        <div class="adm-modal-header">
            <h3><i class="fas fa-ambulance"></i> Assign Trip to Driver</h3>
            <button class="adm-modal-close" onclick="document.getElementById('dispModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <form method="post" action="facility_ambulance.php">
                <input type="hidden" name="action" value="dispatch">
                <div style="display:flex;gap:1rem;">
                    <div class="adm-form-group" style="flex:1;">
                        <label>Select Driver</label>
                        <select name="driver_id" class="adm-search-input" required>
                            <option value="">-- Choose Driver --</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group" style="flex:1;">
                        <label>Select Vehicle (Available)</label>
                        <select name="ambulance_id" class="adm-search-input" required>
                            <option value="">-- Choose Ambulance --</option>
                            <?php foreach ($ambulances as $a): ?>
                                <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['vehicle_number']) . ' (' . htmlspecialchars($a['ambulance_id']) . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label>Pickup Location</label>
                    <input type="text" name="pickup" class="adm-search-input" required placeholder="e.g. 123 Main St, Accident Site">
                </div>
                <div class="adm-form-group">
                    <label>Dropoff Location (Leave blank if Hospital)</label>
                    <input type="text" name="dropoff" class="adm-search-input" placeholder="e.g. City General (Transfer)">
                </div>
                <div class="adm-form-group">
                    <label>Emergency Type / Purpose</label>
                    <input type="text" name="reason" class="adm-search-input" required placeholder="e.g. Cardiac Arrest Patient Pickup">
                </div>
                <button type="submit" class="adm-btn adm-btn-danger" style="width:100%;"><i class="fas fa-siren-on"></i> Dispatch Now</button>
            </form>
        </div>
    </div>
</div>
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
</script>
</body>
</html>
