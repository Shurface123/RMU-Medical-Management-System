<?php
include 'db_conn.php';

$active_page = 'beds';
$page_title  = 'Bed Management';
include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-procedures" style="color:var(--primary);margin-right:.8rem;"></i>Bed Management</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Bed Management</h1>
                <p>Monitor real-time bed availability, occupancy, and ward assignments across the sickbay.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/bed/add-bed.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-plus"></i> Add Bed
            </a>
        </div>

        <?php
        $total_beds = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds"))[0] ?? 0;
        $occupied   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds WHERE status='Occupied' OR status='occupied'"))[0] ?? 0;
        $available  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds WHERE status='Available' OR status='available'"))[0] ?? 0;
        $maintenance= mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds WHERE status='Maintenance' OR status='maintenance'"))[0] ?? 0;
        $occupancy_rate = $total_beds > 0 ? round(($occupied / $total_beds) * 100) : 0;
        ?>
        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_beds; ?></div>
                <div class="adm-mini-card-label">Total Beds</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $available; ?></div>
                <div class="adm-mini-card-label">Available</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num red"><?php echo $occupied; ?></div>
                <div class="adm-mini-card-label">Occupied</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $maintenance; ?></div>
                <div class="adm-mini-card-label">Maintenance</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num <?php echo $occupancy_rate >= 90 ? 'red' : ($occupancy_rate >= 70 ? 'orange' : 'green'); ?>">
                    <?php echo $occupancy_rate; ?>%
                </div>
                <div class="adm-mini-card-label">Occupancy Rate</div>
            </div>
        </div>

        <!-- High occupancy alert -->
        <?php if ($occupancy_rate >= 90): ?>
        <div class="adm-alert adm-alert-danger">
            <i class="fas fa-bed"></i>
            <div><strong>Critical Bed Shortage!</strong> Occupancy is at <?php echo $occupancy_rate; ?>%. Consider activating overflow protocols immediately.</div>
        </div>
        <?php elseif ($occupancy_rate >= 75): ?>
        <div class="adm-alert adm-alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div><strong>High Bed Occupancy:</strong> <?php echo $occupancy_rate; ?>% of beds are occupied. Monitor incoming admissions carefully.</div>
        </div>
        <?php endif; ?>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-list"></i> Bed Registry</h3>
                <form method="post" action="search.php" class="adm-search-form" style="margin:0;">
                    <div class="adm-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="adm-search-input" placeholder="Search by ward, bed number, or type...">
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Bed ID</th>
                            <th>Bed No.</th>
                            <th>Ward</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Daily Rate (GH₵)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql   = "SELECT * FROM beds ORDER BY ward, bed_number";
                        $query = mysqli_query($conn, $sql);
                        if (!$query || mysqli_num_rows($query) === 0) {
                            echo "<tr><td colspan='8' style='text-align:center;padding:3rem;color:var(--text-muted);'>No beds found. <a href='add-bed.php' style='color:var(--primary);font-weight:600;'>Add one now.</a></td></tr>";
                        } else {
                            $n = 1;
                            while ($bed = mysqli_fetch_assoc($query)):
                                $status = strtolower($bed['status'] ?? '');
                                if (str_contains($status, 'avail')) {
                                    $badge = 'adm-badge adm-badge-success';
                                    $row_cls = 'row-ok';
                                } elseif (str_contains($status, 'occup')) {
                                    $badge = 'adm-badge adm-badge-danger';
                                    $row_cls = 'row-danger';
                                } else {
                                    $badge = 'adm-badge adm-badge-warning';
                                    $row_cls = 'row-warning';
                                }
                        ?>
                        <tr class="<?php echo $row_cls; ?>">
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($bed['bed_id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($bed['bed_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($bed['ward']); ?></td>
                            <td><span class="adm-badge adm-badge-info"><?php echo htmlspecialchars($bed['bed_type']); ?></span></td>
                            <td><span class="<?php echo $badge; ?>"><?php echo htmlspecialchars($bed['status']); ?></span></td>
                            <td><?php echo number_format($bed['daily_rate'], 2); ?></td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/bed/update.php?bed_id=<?php echo $bed['bed_id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="/RMU-Medical-Management-System/php/bed/Delete.php?bed_id=<?php echo $bed['bed_id']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm"
                                       onclick="return confirm('Remove this bed?');"><i class="fas fa-trash"></i> Delete</a>
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