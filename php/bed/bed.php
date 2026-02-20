<?php
include 'db_conn.php';

$active_page = 'beds';
$page_title  = 'Bed Management';
include '../includes/_sidebar.php';

// Stats
$total_beds  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds"))[0] ?? 0;
$avail_beds  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds WHERE status='Available'"))[0] ?? 0;
$occup_beds  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds WHERE status='Occupied'"))[0] ?? 0;
$maint_beds  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds WHERE status='Maintenance'"))[0] ?? 0;
$occup_rate  = $total_beds > 0 ? round(($occup_beds / $total_beds) * 100) : 0;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-bed" style="color:var(--primary);margin-right:.8rem;"></i>Bed Management</span>
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
                <p>Monitor real-time bed availability across all wards and manage patient assignments.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/bed/add-bed.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-plus"></i> Add Bed
            </a>
        </div>

        <?php if ($occup_rate >= 90): ?>
        <div class="adm-alert adm-alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <div><strong>Critical Occupancy!</strong> Bed occupancy is at <b><?php echo $occup_rate; ?>%</b>. Urgent admission capacity review needed.</div>
        </div>
        <?php elseif ($occup_rate >= 75): ?>
        <div class="adm-alert adm-alert-warning">
            <i class="fas fa-exclamation-circle"></i>
            <div><strong>High Occupancy:</strong> Currently at <b><?php echo $occup_rate; ?>%</b> capacity. Consider discharge planning.</div>
        </div>
        <?php endif; ?>

        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_beds; ?></div>
                <div class="adm-mini-card-label">Total Beds</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $avail_beds; ?></div>
                <div class="adm-mini-card-label">Available</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num red"><?php echo $occup_beds; ?></div>
                <div class="adm-mini-card-label">Occupied</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $maint_beds; ?></div>
                <div class="adm-mini-card-label">Maintenance</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num <?php echo $occup_rate >= 90 ? 'red' : ($occup_rate >= 75 ? 'orange' : 'green'); ?>"><?php echo $occup_rate; ?>%</div>
                <div class="adm-mini-card-label">Occupancy Rate</div>
            </div>
        </div>

        <!-- Occupancy Progress Bar -->
        <div class="adm-card" style="padding:1.5rem;margin-bottom:1.5rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
                <span style="font-weight:600;">Occupancy Overview</span>
                <span style="font-size:.85rem;color:var(--text-secondary);"><?php echo $occup_beds; ?> of <?php echo $total_beds; ?> beds occupied</span>
            </div>
            <div style="background:var(--bg-secondary);border-radius:50px;overflow:hidden;height:12px;">
                <div style="height:100%;border-radius:50px;background:<?php echo $occup_rate>=90?'#e74c3c':($occup_rate>=75?'#f39c12':'#27ae60'); ?>;width:<?php echo $occup_rate; ?>%;transition:width .5s;"></div>
            </div>
            <div style="display:flex;gap:1.5rem;margin-top:1rem;flex-wrap:wrap;">
                <span style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;"><span style="width:10px;height:10px;border-radius:50%;background:#27ae60;"></span> Available (<?php echo $avail_beds; ?>)</span>
                <span style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;"><span style="width:10px;height:10px;border-radius:50%;background:#e74c3c;"></span> Occupied (<?php echo $occup_beds; ?>)</span>
                <span style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;"><span style="width:10px;height:10px;border-radius:50%;background:#f39c12;"></span> Maintenance (<?php echo $maint_beds; ?>)</span>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-bed"></i> Bed Register</h3>
                <form method="get" class="adm-search-form" style="margin:0;">
                    <select name="ward" class="adm-search-input" style="width:auto;" onchange="this.form.submit()">
                        <option value="">All Wards</option>
                        <?php
                        $wards = mysqli_query($conn, "SELECT DISTINCT ward FROM beds ORDER BY ward");
                        while ($w = mysqli_fetch_assoc($wards)): ?>
                        <option value="<?php echo htmlspecialchars($w['ward']); ?>" <?php echo (($_GET['ward'] ?? '')===($w['ward'])) ? 'selected' : ''; ?>><?php echo htmlspecialchars($w['ward']); ?></option>
                        <?php endwhile; ?>
                    </select>
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
                            <th>Daily Rate (GH₵)</th>
                            <th>Current Patient</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ward_filter = isset($_GET['ward']) ? mysqli_real_escape_string($conn, $_GET['ward']) : '';
                        $where = $ward_filter ? "WHERE b.ward = '$ward_filter'" : '';
                        $sql = "SELECT b.*,
                                       u.name AS patient_name
                                FROM beds b
                                LEFT JOIN bed_assignments ba ON b.id = ba.bed_id AND ba.status = 'Active'
                                LEFT JOIN patients pat ON ba.patient_id = pat.id
                                LEFT JOIN users u ON pat.user_id = u.id
                                $where
                                ORDER BY b.ward ASC, b.bed_number ASC";
                        $q = mysqli_query($conn, $sql);
                        if (!$q || mysqli_num_rows($q) === 0) {
                            echo "<tr><td colspan='9' style='text-align:center;padding:2rem;color:var(--text-muted);'>No beds found.</td></tr>";
                        } else {
                            $n = 1;
                            while ($bed = mysqli_fetch_assoc($q)):
                                $sc_map = ['Available'=>'success','Occupied'=>'danger','Maintenance'=>'warning','Reserved'=>'info'];
                                $sc = $sc_map[$bed['status']] ?? 'primary';
                                $row_cls = $bed['status'] === 'Occupied' ? 'row-danger' : ($bed['status'] === 'Maintenance' ? 'row-warning' : '');
                        ?>
                        <tr class="<?php echo $row_cls; ?>">
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($bed['bed_id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($bed['bed_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($bed['ward']); ?></td>
                            <td><?php echo htmlspecialchars($bed['bed_type']); ?></td>
                            <td><?php echo number_format($bed['daily_rate'], 2); ?></td>
                            <td><?php echo $bed['patient_name'] ? htmlspecialchars($bed['patient_name']) : '<span style="color:var(--text-muted);">Vacant</span>'; ?></td>
                            <td><span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo $bed['status']; ?></span></td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/bed/update.php?id=<?php echo $bed['id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i></a>
                                    <?php if ($bed['status'] === 'Available'): ?>
                                    <a href="/RMU-Medical-Management-System/php/bed/assign.php?bed_id=<?php echo $bed['id']; ?>"
                                       class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-user-plus"></i> Assign</a>
                                    <?php elseif ($bed['status'] === 'Occupied'): ?>
                                    <a href="/RMU-Medical-Management-System/php/bed/discharge.php?bed_id=<?php echo $bed['id']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm" onclick="return confirm('Discharge patient from this bed?');"><i class="fas fa-sign-out-alt"></i> Discharge</a>
                                    <?php endif; ?>
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