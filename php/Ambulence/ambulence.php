<?php
include 'db_conn.php';

$active_page = 'ambulance';
$page_title  = 'Ambulance Fleet';
include '../includes/_sidebar.php';

// Stats
$total_amb  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances"))[0] ?? 0;
$avail_amb  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE status='Available'"))[0] ?? 0;
$onduty_amb = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE status='On Duty'"))[0] ?? 0;
$maint_amb  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE status='Maintenance'"))[0] ?? 0;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-ambulance" style="color:var(--primary);margin-right:.8rem;"></i>Ambulance Fleet</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Ambulance Fleet Management</h1>
                <p>Track ambulance availability, maintenance schedules, and emergency dispatch records.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/Ambulence/add-ambulence.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-plus"></i> Register Ambulance
            </a>
        </div>

        <?php
        // Maintenance due alert
        $due_maint = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE next_service_date IS NOT NULL AND next_service_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)"))[0] ?? 0;
        if ($due_maint > 0): ?>
        <div class="adm-alert adm-alert-warning">
            <i class="fas fa-tools"></i>
            <div><strong>Maintenance Due!</strong> <b><?php echo $due_maint; ?></b> ambulance(s) need servicing within the next 14 days. Please schedule maintenance.</div>
        </div>
        <?php endif; ?>

        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_amb; ?></div>
                <div class="adm-mini-card-label">Total Fleet</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $avail_amb; ?></div>
                <div class="adm-mini-card-label">Available</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num blue"><?php echo $onduty_amb; ?></div>
                <div class="adm-mini-card-label">On Duty</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $maint_amb; ?></div>
                <div class="adm-mini-card-label">Maintenance</div>
            </div>
        </div>

        <!-- Ambulance Grid -->
        <div class="adm-fleet-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem;margin-bottom:2rem;">
            <?php
            $ambs = mysqli_query($conn, "SELECT * FROM ambulances ORDER BY status ASC, ambulance_id ASC");
            if ($ambs && mysqli_num_rows($ambs) > 0):
                while ($amb = mysqli_fetch_assoc($ambs)):
                    if ($amb['status'] === 'Available') {
                        $sc = ['adm-badge-success', '#27ae60', 'fa-check-circle'];
                    } elseif ($amb['status'] === 'On Duty') {
                        $sc = ['adm-badge-info', '#2980b9', 'fa-truck-medical'];
                    } elseif ($amb['status'] === 'Maintenance') {
                        $sc = ['adm-badge-warning', '#f39c12', 'fa-tools'];
                    } elseif ($amb['status'] === 'Out of Service') {
                        $sc = ['adm-badge-danger', '#e74c3c', 'fa-times-circle'];
                    } else {
                        $sc = ['adm-badge-primary', 'var(--primary)', 'fa-question'];
                    }
                    $next_svc = $amb['next_service_date'] ? date('d M Y', strtotime($amb['next_service_date'])) : 'Not scheduled';
                    $svc_style = ($amb['next_service_date'] && strtotime($amb['next_service_date']) <= strtotime('+14 days')) ? 'color:#e74c3c;font-weight:700;' : '';
            ?>
            <div class="adm-card" style="padding:1.5rem;position:relative;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                    <div style="display:flex;align-items:center;gap:.75rem;">
                        <div style="width:44px;height:44px;background:<?php echo $sc[1]; ?>22;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas <?php echo $sc[2]; ?>" style="color:<?php echo $sc[1]; ?>;font-size:1.2rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:1rem;"><?php echo htmlspecialchars($amb['vehicle_number']); ?></div>
                            <div style="font-size:.8rem;color:var(--text-secondary);"><?php echo htmlspecialchars($amb['ambulance_id']); ?></div>
                        </div>
                    </div>
                    <span class="adm-badge <?php echo $sc[0]; ?>"><?php echo $amb['status']; ?></span>
                </div>
                <div style="font-size:.875rem;color:var(--text-secondary);line-height:2;">
                    <div><i class="fas fa-user" style="margin-right:.5rem;width:16px;"></i><strong>Driver:</strong> <?php echo htmlspecialchars($amb['driver_name'] ?? 'Unassigned'); ?></div>
                    <div><i class="fas fa-phone" style="margin-right:.5rem;width:16px;"></i><strong>Phone:</strong> <?php echo htmlspecialchars($amb['driver_phone'] ?? 'N/A'); ?></div>
                    <div><i class="fas fa-calendar-check" style="margin-right:.5rem;width:16px;"></i><strong>Last Serviced:</strong> <?php echo $amb['last_service_date'] ? date('d M Y', strtotime($amb['last_service_date'])) : 'N/A'; ?></div>
                    <div style="<?php echo $svc_style; ?>"><i class="fas fa-calendar-times" style="margin-right:.5rem;width:16px;"></i><strong>Next Service:</strong> <?php echo $next_svc; ?></div>
                </div>
                <div style="display:flex;gap:.5rem;margin-top:1rem;">
                    <a href="/RMU-Medical-Management-System/php/Ambulence/update.php?id=<?php echo $amb['id']; ?>"
                       class="adm-btn adm-btn-warning adm-btn-sm" style="flex:1;text-align:center;"><i class="fas fa-edit"></i> Edit</a>
                    <a href="/RMU-Medical-Management-System/php/Ambulence/Delete.php?id=<?php echo $amb['id']; ?>"
                       class="adm-btn adm-btn-danger adm-btn-sm"
                       onclick="return confirm('Remove this ambulance from fleet?');"><i class="fas fa-trash"></i></a>
                </div>
            </div>
            <?php endwhile;
            else: ?>
            <div class="adm-card" style="padding:3rem;text-align:center;grid-column:1/-1;">
                <i class="fas fa-ambulance" style="font-size:3rem;color:var(--text-muted);margin-bottom:1rem;"></i>
                <p style="color:var(--text-muted);">No ambulances registered yet.</p>
                <a href="add-ambulence.php" class="adm-btn adm-btn-primary" style="margin-top:1rem;"><i class="fas fa-plus"></i> Register First Ambulance</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Active Emergency Requests -->
        <?php
        $reqs = mysqli_query($conn, "SELECT * FROM ambulance_requests WHERE status NOT IN ('Completed','Cancelled') ORDER BY request_time DESC LIMIT 10");
        if ($reqs && mysqli_num_rows($reqs) > 0): ?>
        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i> Active Emergency Requests</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>#</th><th>Request ID</th><th>Patient</th><th>Phone</th><th>Pickup</th><th>Type</th><th>Requested</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php $n=1; while ($req = mysqli_fetch_assoc($reqs)): $req_status = $req['status']; $sc2 = ($req_status === 'Dispatched') ? 'info' : (($req_status === 'In Transit') ? 'warning' : 'danger'); ?>
                        <tr>
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($req['request_id']); ?></span></td>
                            <td><?php echo htmlspecialchars($req['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($req['patient_phone']); ?></td>
                            <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($req['pickup_location']); ?></td>
                            <td><?php echo htmlspecialchars($req['emergency_type'] ?? 'General'); ?></td>
                            <td><?php echo date('d M, g:i A', strtotime($req['request_time'])); ?></td>
                            <td><span class="adm-badge adm-badge-<?php echo $sc2; ?>"><?php echo $req['status']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = document.getElementById('themeIcon');
const html        = document.documentElement;
function applyTheme(t) { html.setAttribute('data-theme',t); localStorage.setItem('rmu_theme',t); themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon'; }
applyTheme(localStorage.getItem('rmu_theme') || 'light');
themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
</script>
</body>
</html>