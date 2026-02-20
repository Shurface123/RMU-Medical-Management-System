<?php
include 'db_conn.php';

$active_page = 'tests';
$page_title  = 'Lab Tests';
include '../includes/_sidebar.php';

// Stats from services table (the system uses 'services' for lab test catalogue, 'lab_tests' for patient tests)
$total_svc  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM services WHERE category='Diagnostic' OR category='Laboratory'"))[0] ?? 0;
$active_svc = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM services WHERE is_active=1 AND (category='Diagnostic' OR category='Laboratory')"))[0] ?? 0;
$total_ltst = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lab_tests"))[0] ?? 0;
$pending_lt = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lab_tests WHERE status='Pending'"))[0] ?? 0;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-flask" style="color:var(--primary);margin-right:.8rem;"></i>Lab Tests</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Lab Tests &amp; Diagnostics</h1>
                <p>Track all patient laboratory tests, results, and diagnostic services.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/test/add-test.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-plus"></i> Order New Test
            </a>
        </div>

        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_ltst; ?></div>
                <div class="adm-mini-card-label">Total Test Orders</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $pending_lt; ?></div>
                <div class="adm-mini-card-label">Pending</div>
            </div>
            <div class="adm-mini-card">
                <?php $completed_lt = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lab_tests WHERE status='Completed'"))[0] ?? 0; ?>
                <div class="adm-mini-card-num green"><?php echo $completed_lt; ?></div>
                <div class="adm-mini-card-label">Completed</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num blue"><?php echo $active_svc; ?></div>
                <div class="adm-mini-card-label">Services Available</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="adm-tabs" id="testTabs">
            <button class="adm-tab adm-tab-active" data-tab="orders">Patient Test Orders</button>
            <button class="adm-tab" data-tab="services">Service Catalogue</button>
        </div>

        <!-- Patient Test Orders -->
        <div class="adm-tab-panel" id="tab-orders">
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-clipboard-list"></i> Test Orders</h3>
                </div>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Test ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Test Name</th>
                                <th>Category</th>
                                <th>Test Date</th>
                                <th>Cost (GH₵)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT lt.*, 
                                           u_p.name AS patient_name, 
                                           u_d.name AS doctor_name
                                    FROM lab_tests lt
                                    JOIN patients pat ON lt.patient_id = pat.id
                                    JOIN users u_p   ON pat.user_id   = u_p.id
                                    JOIN doctors dr  ON lt.doctor_id  = dr.id
                                    JOIN users u_d   ON dr.user_id    = u_d.id
                                    ORDER BY lt.test_date DESC LIMIT 50";
                            $q = mysqli_query($conn, $sql);
                            if (!$q || mysqli_num_rows($q) === 0) {
                                echo "<tr><td colspan='9' style='text-align:center;padding:2rem;color:var(--text-muted);'>No test orders yet.</td></tr>";
                            } else {
                                $n = 1;
                                while ($t = mysqli_fetch_assoc($q)):
                                    $s_stat = $t['status'];
                                    $sc = ($s_stat === 'Completed') ? 'success' : (($s_stat === 'In Progress') ? 'info' : (($s_stat === 'Cancelled') ? 'danger' : 'warning'));
                            ?>
                            <tr>
                                <td><?php echo $n++; ?></td>
                                <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($t['test_id']); ?></span></td>
                                <td><?php echo htmlspecialchars($t['patient_name']); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($t['doctor_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($t['test_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($t['test_category'] ?? 'General'); ?></td>
                                <td><?php echo date('d M Y', strtotime($t['test_date'])); ?></td>
                                <td><?php echo number_format($t['cost'], 2); ?></td>
                                <td><span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo $t['status']; ?></span></td>
                            </tr>
                            <?php endwhile; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Service Catalogue -->
        <div class="adm-tab-panel" id="tab-services" style="display:none;">
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-list"></i> Service Catalogue</h3>
                </div>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Service ID</th>
                                <th>Service Name</th>
                                <th>Category</th>
                                <th>Price (GH₵)</th>
                                <th>Free for Students</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $srvs = mysqli_query($conn, "SELECT * FROM services ORDER BY category, service_name");
                            if (!$srvs || mysqli_num_rows($srvs) === 0) {
                                echo "<tr><td colspan='7' style='text-align:center;padding:2rem;color:var(--text-muted);'>No services defined.</td></tr>";
                            } else {
                                $n = 1;
                                while ($s = mysqli_fetch_assoc($srvs)):
                            ?>
                            <tr>
                                <td><?php echo $n++; ?></td>
                                <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($s['service_id']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($s['service_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($s['category']); ?></td>
                                <td><?php echo number_format($s['price'], 2); ?></td>
                                <td><?php echo $s['is_free_for_students'] ? '<span class="adm-badge adm-badge-success">Yes</span>' : '<span class="adm-badge adm-badge-warning">No</span>'; ?></td>
                                <td><?php echo $s['is_active'] ? '<span class="adm-badge adm-badge-success">Active</span>' : '<span class="adm-badge adm-badge-danger">Inactive</span>'; ?></td>
                            </tr>
                            <?php endwhile; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
// Tabs
document.querySelectorAll('.adm-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.adm-tab').forEach(t => t.classList.remove('adm-tab-active'));
        document.querySelectorAll('.adm-tab-panel').forEach(p => p.style.display = 'none');
        tab.classList.add('adm-tab-active');
        document.getElementById('tab-' + tab.dataset.tab).style.display = 'block';
    });
});
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