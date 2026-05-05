<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'tests';
$page_title  = 'Lab & Diagnostics';
include '../includes/_sidebar.php';

// Stats
$total_svc  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM services WHERE category='Diagnostic' OR category='Laboratory'"))[0] ?? 0;
$active_svc = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM services WHERE is_active=1 AND (category='Diagnostic' OR category='Laboratory')"))[0] ?? 0;
$total_ltst = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lab_tests"))[0] ?? 0;
$pending_lt = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lab_tests WHERE status='Pending'"))[0] ?? 0;
$completed_lt = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lab_tests WHERE status='Completed'"))[0] ?? 0;
?>

<!-- DataTables Dependencies -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<style>
/* ── V2 Clinical Styles ── */
.clinical-hero {
    background: linear-gradient(135deg, #9b59b6 0%, #1a2a6c 100%);
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

/* Tab Enhancements */
.adm-tabs {
    display: flex;
    gap: 1rem;
    background: var(--surface-2);
    padding: 0.6rem;
    border-radius: 16px;
    margin-bottom: 2.5rem;
    border: 1px solid var(--border);
}

.adm-tab-btn {
    padding: 1rem 2rem;
    border-radius: 12px;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-secondary);
    background: transparent;
    border: none;
    transition: var(--transition);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.adm-tab-btn.active {
    background: var(--surface);
    color: var(--primary);
    box-shadow: var(--shadow-sm);
}

.status-badge { padding: 0.6rem 1.2rem; border-radius: 12px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; }
.status-pending { background: rgba(243, 156, 18, 0.1); color: #F39C12; }
.status-inprogress { background: rgba(47, 128, 237, 0.1); color: #2F80ED; }
.status-completed { background: rgba(39, 174, 96, 0.1); color: #27AE60; }
.status-cancelled { background: rgba(231, 76, 60, 0.1); color: #E74C3C; }

.clinical-table tbody td { vertical-align: middle !important; }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-flask" style="color:#9b59b6;margin-right:.8rem;"></i>Laboratory & Diagnostics</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar" style="overflow:hidden; border:2px solid rgba(155, 89, 182, 0.2);">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" style="width:100%; height:100%; object-fit:cover;">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <div class="clinical-hero">
            <div>
                <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 0.5rem;">Diagnostic Hub</h1>
                <p style="opacity: 0.9; font-size: 1.3rem;">Real-time tracking of lab requisitions, clinical results, and service pricing.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/test/add-test.php" class="btn btn-primary" style="background:white; color:#9b59b6; border:none; padding:1.2rem 2.5rem; font-weight:700; border-radius:15px; box-shadow:0 10px 20px rgba(0,0,0,0.1);"><span class="btn-text">
                <i class="fas fa-vial"></i> Order New Diagnostic
            </span></a>
        </div>

        <div class="stat-v2-grid">
            <div class="stat-v2-card">
                <div style="font-size:3rem; font-weight:900; color:#9b59b6;"><?= $total_ltst ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Test Orders</div>
            </div>
            <div class="stat-v2-card" style="border-bottom:4px solid var(--warning);">
                <div style="font-size:3rem; font-weight:900; color:var(--warning);"><?= $pending_lt ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Pending Results</div>
            </div>
            <div class="stat-v2-card" style="border-bottom:4px solid var(--success);">
                <div style="font-size:3rem; font-weight:900; color:var(--success);"><?= $completed_lt ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Finalized</div>
            </div>
            <div class="stat-v2-card" style="border-bottom:4px solid var(--primary);">
                <div style="font-size:3rem; font-weight:900; color:var(--primary);"><?= $active_svc ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Available Services</div>
            </div>
        </div>

        <div class="adm-tabs">
            <button class="adm-tab-btn active" onclick="switchTab('orders', this)"><i class="fas fa-clipboard-list"></i> Patient Orders</button>
            <button class="adm-tab-btn" onclick="switchTab('catalogue', this)"><i class="fas fa-book-medical"></i> Service Catalogue</button>
        </div>

        <!-- Orders Panel -->
        <div id="panel-orders" class="adm-card" style="padding:2.5rem; border-radius:24px;">
            <table class="clinical-table display responsive nowrap" id="ordersTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Patient</th>
                        <th>Referring Doc</th>
                        <th>Test Requested</th>
                        <th>Clinical Date</th>
                        <th>Financial (GH₵)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT lt.*, u_p.name AS patient_name, u_d.name AS doctor_name
                            FROM lab_tests lt
                            JOIN patients pat ON lt.patient_id = pat.id
                            JOIN users u_p   ON pat.user_id   = u_p.id
                            JOIN doctors dr  ON lt.doctor_id  = dr.id
                            JOIN users u_d   ON dr.user_id    = u_d.id
                            ORDER BY lt.test_date DESC";
                    $q = mysqli_query($conn, $sql);
                    while ($t = mysqli_fetch_assoc($q)):
                        $s_cls = 'status-' . strtolower(str_replace(' ', '', $t['status']));
                    ?>
                    <tr>
                        <td><span class="adm-badge adm-badge-primary"><?= htmlspecialchars($t['test_id']) ?></span></td>
                        <td><div style="font-weight:700; color:var(--text-primary);"><?= htmlspecialchars($t['patient_name']) ?></div></td>
                        <td><div style="font-size:1.1rem; color:var(--text-muted);">Dr. <?= htmlspecialchars($t['doctor_name']) ?></div></td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);"><?= htmlspecialchars($t['test_name']) ?></div>
                            <div style="font-size:0.95rem; color:var(--primary); font-weight:600;"><?= htmlspecialchars($t['test_category'] ?? 'Diagnostic') ?></div>
                        </td>
                        <td><div style="font-weight:600;"><?= date('d M Y', strtotime($t['test_date'])) ?></div></td>
                        <td><div style="font-weight:800; color:var(--text-primary);">GH₵ <?= number_format($t['cost'], 2) ?></div></td>
                        <td><span class="status-badge <?= $s_cls ?>"><?= $t['status'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Catalogue Panel -->
        <div id="panel-catalogue" class="adm-card" style="padding:2.5rem; border-radius:24px; display:none;">
            <table class="clinical-table display responsive nowrap" id="catalogueTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Svc ID</th>
                        <th>Nomenclature</th>
                        <th>Classification</th>
                        <th>Unit Price (GH₵)</th>
                        <th>Student Coverage</th>
                        <th>Availability</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $srvs = mysqli_query($conn, "SELECT * FROM services ORDER BY category, service_name");
                    while ($s = mysqli_fetch_assoc($srvs)):
                    ?>
                    <tr>
                        <td><span class="adm-badge adm-badge-primary"><?= htmlspecialchars($s['service_id']) ?></span></td>
                        <td><div style="font-weight:800; color:var(--text-primary);"><?= htmlspecialchars($s['service_name']) ?></div></td>
                        <td><span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary);"><?= htmlspecialchars($s['category']) ?></span></td>
                        <td><div style="font-weight:800; color:var(--primary); font-size:1.3rem;">GH₵ <?= number_format($s['price'], 2) ?></div></td>
                        <td>
                            <?php if($s['is_free_for_students']): ?>
                                <span class="adm-badge adm-badge-success" style="font-size:0.85rem;"><i class="fas fa-check-circle"></i> FULL COVERAGE</span>
                            <?php else: ?>
                                <span class="adm-badge adm-badge-danger" style="font-size:0.85rem;"><i class="fas fa-times-circle"></i> NO COVERAGE</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $s['is_active'] ? '<span class="adm-badge-success" style="padding:4px 10px; border-radius:8px;">ACTIVE</span>' : '<span class="adm-badge-danger" style="padding:4px 10px; border-radius:8px;">SUSPENDED</span>' ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.adm-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('[id^="panel-"]').forEach(p => p.style.display = 'none');
    btn.classList.add('active');
    document.getElementById('panel-' + tabId).style.display = 'block';
}

$(document).ready(function() {
    $('#ordersTable, #catalogueTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: { search: "_INPUT_", searchPlaceholder: "Search diagnostics..." },
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