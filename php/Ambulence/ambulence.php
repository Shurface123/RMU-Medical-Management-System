<?php
include 'db_conn.php';

$active_page = 'ambulance';
$page_title  = 'Ambulance Fleet';
include '../includes/_sidebar.php';
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
                <p>Monitor fleet availability, driver assignments, and service schedules.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/Ambulence/add-ambulence.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-plus"></i> Add Ambulance
            </a>
        </div>

        <?php
        $total_amb = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances"))[0] ?? 0;
        $avail_amb = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE status='Available' OR status='available'"))[0] ?? 0;
        $busy_amb  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE status='On Duty' OR status='on duty' OR status='OnDuty'"))[0] ?? 0;
        $maint_amb = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ambulances WHERE status='Maintenance' OR status='maintenance'"))[0] ?? 0;
        ?>
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
                <div class="adm-mini-card-num orange"><?php echo $busy_amb; ?></div>
                <div class="adm-mini-card-label">On Duty</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num red"><?php echo $maint_amb; ?></div>
                <div class="adm-mini-card-label">Maintenance</div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-truck-medical"></i> Vehicle Registry</h3>
                <form method="post" action="search.php" class="adm-search-form" style="margin:0;">
                    <div class="adm-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="adm-search-input" placeholder="Search by vehicle number or driver...">
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ambulance ID</th>
                            <th>Vehicle No.</th>
                            <th>Driver</th>
                            <th>Driver Phone</th>
                            <th>Status</th>
                            <th>Last Service</th>
                            <th>Next Service</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql   = "SELECT * FROM ambulances ORDER BY ambulance_id";
                        $query = mysqli_query($conn, $sql);
                        if (!$query || mysqli_num_rows($query) === 0) {
                            echo "<tr><td colspan='9' style='text-align:center;padding:3rem;color:var(--text-muted);'>No ambulances found. <a href='add-ambulence.php' style='color:var(--primary);font-weight:600;'>Add one now.</a></td></tr>";
                        } else {
                            $n = 1;
                            while ($amb = mysqli_fetch_assoc($query)):
                                $status = strtolower($amb['status'] ?? '');
                                if (str_contains($status, 'avail')) {
                                    $badge_class = 'adm-badge adm-badge-success';
                                } elseif (str_contains($status, 'duty') || str_contains($status, 'on')) {
                                    $badge_class = 'adm-badge adm-badge-warning';
                                } else {
                                    $badge_class = 'adm-badge adm-badge-danger';
                                }
                                // Next service highlight
                                $svc_style = '';
                                if (!empty($amb['next_service_date'])) {
                                    $days = (strtotime($amb['next_service_date']) - time()) / 86400;
                                    if ($days <= 7) $svc_style = 'color:var(--danger);font-weight:700;';
                                    elseif ($days <= 30) $svc_style = 'color:var(--warning);font-weight:600;';
                                }
                        ?>
                        <tr>
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($amb['ambulance_id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($amb['vehicle_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($amb['driver_name']); ?></td>
                            <td><?php echo htmlspecialchars($amb['driver_phone']); ?></td>
                            <td><span class="<?php echo $badge_class; ?>"><?php echo htmlspecialchars($amb['status']); ?></span></td>
                            <td style="color:var(--text-secondary);"><?php echo htmlspecialchars($amb['last_service_date'] ?? 'N/A'); ?></td>
                            <td style="<?php echo $svc_style; ?>"><?php echo htmlspecialchars($amb['next_service_date'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/Ambulence/update.php?ambulance_id=<?php echo $amb['ambulance_id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="/RMU-Medical-Management-System/php/Ambulence/Delete.php?ambulance_id=<?php echo $amb['ambulance_id']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm"
                                       onclick="return confirm('Remove this ambulance from the fleet?');"><i class="fas fa-trash"></i> Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
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