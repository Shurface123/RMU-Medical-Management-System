<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'ambulance';
$page_title  = 'Emergency Fleet';
include '../includes/_sidebar.php';

// Stats
$total_amb  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances"))[0] ?? 0;
$avail_amb  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE status='Available'"))[0] ?? 0;
$onduty_amb = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE status='On Duty'"))[0] ?? 0;
$maint_amb  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE status='Maintenance'"))[0] ?? 0;
?>

<!-- DataTables Dependencies -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<style>
/* ── V2 Ambulance Fleet Styles ── */
.fleet-hero {
    background: linear-gradient(135deg, #E67E22 0%, #1a2a6c 100%);
    color: white;
    padding: 3rem;
    border-radius: var(--radius-lg);
    margin-bottom: 3rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.stat-v2-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-v2-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 2.2rem;
    display: flex;
    align-items: center;
    gap: 1.8rem;
    transition: var(--transition);
}

.vehicle-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 2.5rem;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.vehicle-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); }

.vehicle-status-tag {
    position: absolute;
    top: 2rem;
    right: 2rem;
    padding: 0.5rem 1.2rem;
    border-radius: 10px;
    font-weight: 800;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-available { background: rgba(39, 174, 96, 0.1); color: #27AE60; }
.status-onduty { background: rgba(47, 128, 237, 0.1); color: #2F80ED; }
.status-maintenance { background: rgba(243, 156, 18, 0.1); color: #F39C12; }
.status-out { background: rgba(231, 76, 60, 0.1); color: #E74C3C; }

/* Pulse animation for on-duty vehicles */
.pulse-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: currentColor;
    border-radius: 50%;
    margin-right: 0.6rem;
    animation: statusPulse 1.5s infinite;
}
@keyframes statusPulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
    100% { opacity: 1; transform: scale(1); }
}

.fleet-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2.5rem;
    margin-bottom: 4rem;
}

.alert-glass {
    background: rgba(243, 156, 18, 0.1);
    backdrop-filter: blur(10px);
    border-left: 5px solid #F39C12;
    padding: 1.5rem 2rem;
    border-radius: 14px;
    margin-bottom: 2.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    color: #F39C12;
}
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-truck-medical" style="color:#E67E22;margin-right:.8rem;"></i>Fleet Control Hub</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar" style="overflow:hidden; border:2px solid rgba(230, 126, 34, 0.2);">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" style="width:100%; height:100%; object-fit:cover;">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <div class="fleet-hero">
            <div>
                <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 0.5rem;">Emergency Response Fleet</h1>
                <p style="opacity: 0.9; font-size: 1.3rem;">Logistics monitoring, emergency dispatch synchronization, and vehicle maintenance lifecycle management.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/Ambulence/add-ambulence.php" class="btn btn-primary" style="background:white; color:#E67E22; border:none; padding:1.2rem 2.5rem; font-weight:700; border-radius:15px; box-shadow:0 10px 20px rgba(0,0,0,0.1);"><span class="btn-text">
                <i class="fas fa-plus"></i> Add New Unit
            </span></a>
        </div>

        <?php
        $due_maint = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE next_service_date IS NOT NULL AND next_service_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)"))[0] ?? 0;
        if ($due_maint > 0): ?>
        <div class="alert-glass">
            <i class="fas fa-tools" style="font-size:2rem;"></i>
            <div>
                <h4 style="margin:0; font-weight:800;">Fleet Servicing Required</h4>
                <p style="margin:0.2rem 0 0; opacity:0.8; font-size:1.1rem;"><strong><?= $due_maint ?></strong> vehicles are scheduled for mandatory maintenance within the next 14 days.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="stat-v2-grid">
            <div class="stat-v2-card">
                <div style="font-size:3rem; font-weight:900; color:#E67E22;"><?= $total_amb ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Total Units</div>
            </div>
            <div class="stat-v2-card" style="border-left:4px solid var(--success);">
                <div style="font-size:3rem; font-weight:900; color:var(--success);"><?= $avail_amb ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Operational</div>
            </div>
            <div class="stat-v2-card" style="border-left:4px solid var(--primary);">
                <div style="font-size:3rem; font-weight:900; color:var(--primary);"><?= $onduty_amb ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">On Dispatch</div>
            </div>
            <div class="stat-v2-card" style="border-left:4px solid var(--warning);">
                <div style="font-size:3rem; font-weight:900; color:var(--warning);"><?= $maint_amb ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">In Garage</div>
            </div>
        </div>

        <div class="fleet-grid">
            <?php
            $ambs = mysqli_query($conn, "SELECT * FROM ambulances ORDER BY status ASC, ambulance_id ASC");
            while ($amb = mysqli_fetch_assoc($ambs)):
                $status_key = strtolower(str_replace(' ', '', $amb['status']));
                $s_cls = 'status-' . $status_key;
                $is_dispatch = ($amb['status'] === 'On Duty');
            ?>
            <div class="vehicle-card">
                <span class="vehicle-status-tag <?= $s_cls ?>"><?= $is_dispatch ? '<span class="pulse-indicator"></span>' : '' ?><?= $amb['status'] ?></span>
                
                <div style="margin-bottom:2rem;">
                    <h3 style="font-size:2.2rem; font-weight:900; color:var(--text-primary); margin:0;"><?= htmlspecialchars($amb['vehicle_number']) ?></h3>
                    <p style="color:var(--text-muted); font-size:1.1rem; font-weight:700; text-transform:uppercase; letter-spacing:1px;"><?= htmlspecialchars($amb['ambulance_id']) ?></p>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem; padding-top:1.5rem; border-top:1px solid var(--border);">
                    <div>
                        <div style="font-size:0.9rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; margin-bottom:0.4rem;">Designated Driver</div>
                        <div style="font-weight:700; color:var(--text-primary); font-size:1.2rem;"><?= htmlspecialchars($amb['driver_name'] ?? 'PENDING') ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.9rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; margin-bottom:0.4rem;">Emergency Line</div>
                        <div style="font-weight:700; color:var(--primary); font-size:1.2rem;"><?= htmlspecialchars($amb['driver_phone'] ?? 'N/A') ?></div>
                    </div>
                </div>

                <div style="background:var(--surface-2); padding:1.2rem; border-radius:12px; margin-bottom:2rem;">
                    <div style="display:flex; justify-content:space-between; font-size:1.1rem;">
                        <span style="color:var(--text-muted); font-weight:600;">Next Maintenance:</span>
                        <span style="font-weight:800; color:var(--text-primary);"><?= $amb['next_service_date'] ? date('d M Y', strtotime($amb['next_service_date'])) : 'SCHEDULED ON REQUEST' ?></span>
                    </div>
                </div>

                <div style="display:flex; gap:1rem;">
                    <a href="update.php?id=<?= $amb['id'] ?>" class="btn btn-primary" style="flex:1; border-radius:12px; justify-content:center; padding:1.1rem;"><span class="btn-text"><i class="fas fa-tools"></i> Service Log</span></a>
                    <a href="Delete.php?id=<?= $amb['id'] ?>" class="btn btn-danger" style="width:50px; height:50px; border-radius:12px; justify-content:center; padding:0;" onclick="return confirm('Decommission unit?');"><i class="fas fa-trash-alt"></i></a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <?php
        $reqs = mysqli_query($conn, "SELECT * FROM ambulance_requests WHERE status NOT IN ('Completed','Cancelled') ORDER BY request_time DESC");
        if ($reqs && mysqli_num_rows($reqs) > 0): ?>
        <div class="adm-card" style="padding:2.5rem; border-radius:24px; border-top:5px solid #E74C3C;">
            <div style="margin-bottom:2rem;">
                <h3 style="font-size:1.8rem; font-weight:800; color:#E74C3C; display:flex; align-items:center; gap:1rem;"><i class="fas fa-exclamation-circle"></i> Active Emergency Dispatches</h3>
                <p style="color:var(--text-muted); font-size:1.1rem; margin-top:0.3rem;">Real-time stream of high-priority ambulance requests</p>
            </div>
            <table class="clinical-table display responsive nowrap" id="emergencyRequestsTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Patient Entity</th>
                        <th>Geographical Pickup</th>
                        <th>Emergency Classification</th>
                        <th>Dispatch Status</th>
                        <th>Time Index</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($req = mysqli_fetch_assoc($reqs)): 
                        $req_status = $req['status'];
                        $sc2 = ($req_status === 'Dispatched') ? 'adm-badge-info' : (($req_status === 'In Transit') ? 'adm-badge-warning' : 'adm-badge-danger');
                    ?>
                    <tr>
                        <td><span class="adm-badge adm-badge-primary"><?= htmlspecialchars($req['request_id']) ?></span></td>
                        <td>
                            <div style="font-weight:800; color:var(--text-primary);"><?= htmlspecialchars($req['patient_name']) ?></div>
                            <div style="font-size:1rem; color:var(--text-muted); font-weight:600;"><i class="fas fa-phone"></i> <?= htmlspecialchars($req['patient_phone']) ?></div>
                        </td>
                        <td><div style="max-width:220px; overflow:hidden; text-overflow:ellipsis; font-weight:600;"><?= htmlspecialchars($req['pickup_location']) ?></div></td>
                        <td><span class="adm-badge" style="background:rgba(231,76,60,0.1); color:#E74C3C; font-weight:800;"><?= htmlspecialchars($req['emergency_type'] ?? 'CRITICAL') ?></span></td>
                        <td><span class="adm-badge <?= $sc2 ?>"><?= $req['status'] ?></span></td>
                        <td><div style="font-weight:700; color:var(--text-primary);"><?= date('H:i:s', strtotime($req['request_time'])) ?></div><div style="font-size:0.8rem; color:var(--text-muted);"><?= date('d M Y', strtotime($req['request_time'])) ?></div></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
$(document).ready(function() {
    $('#emergencyRequestsTable').DataTable({
        responsive: true,
        pageLength: 5,
        language: { search: "_INPUT_", searchPlaceholder: "Search emergencies..." },
        dom: '<"top"f>rt<"bottom"lip><"clear">',
    });

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon   = document.getElementById('themeIcon');
    const html        = document.documentElement;
    function applyTheme(t) { html.setAttribute('data-theme',t); localStorage.setItem('rmu_theme',t); themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon'; }
    themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
    
    const sidebar = document.getElementById('admSidebar');
    const overlay = document.getElementById('admOverlay');
    document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
    overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
});
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>