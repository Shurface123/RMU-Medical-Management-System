<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'beds';
$page_title  = 'Hospital Occupancy';
include '../includes/_sidebar.php';

// Stats
$total_beds  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds"))[0] ?? 0;
$avail_beds  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds WHERE status='Available'"))[0] ?? 0;
$occup_beds  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds WHERE status='Occupied'"))[0] ?? 0;
$maint_beds  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM beds WHERE status='Maintenance'"))[0] ?? 0;
$occup_rate  = $total_beds > 0 ? round(($occup_beds / $total_beds) * 100) : 0;
?>

<!-- DataTables Dependencies -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<style>
/* ── V2 Bed Management Styles ── */
.clinical-hero {
    background: linear-gradient(135deg, #27ae60 0%, #1a2a6c 100%);
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

.occupancy-meter-wrap {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 3rem;
    box-shadow: var(--shadow-sm);
}

.meter-container {
    height: 14px;
    background: var(--surface-2);
    border-radius: 10px;
    overflow: hidden;
    margin: 1.5rem 0;
    display: flex;
}
.meter-segment { height: 100%; transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1); }

.stat-v2-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}
.stat-pill {
    padding: 1rem 1.5rem;
    border-radius: 14px;
    background: var(--surface-2);
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid var(--border);
}
.stat-pill i { font-size: 1.4rem; }
.stat-pill strong { font-size: 1.6rem; font-weight: 800; color: var(--text-primary); }
.stat-pill span { font-size: 1rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }

.bed-status-badge { padding: 0.5rem 1rem; border-radius: 10px; font-weight: 800; font-size: 0.85rem; }
.status-available { background: rgba(39, 174, 96, 0.1); color: #27AE60; }
.status-occupied { background: rgba(231, 76, 60, 0.1); color: #E74C3C; }
.status-maintenance { background: rgba(243, 156, 18, 0.1); color: #F39C12; }

/* Critical Alert */
.alert-glass {
    background: rgba(231, 76, 60, 0.1);
    backdrop-filter: blur(10px);
    border-left: 5px solid #E74C3C;
    padding: 1.5rem 2rem;
    border-radius: 14px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    color: #E74C3C;
}
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-bed" style="color:#27ae60;margin-right:.8rem;"></i>Hospital Occupancy Control</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar" style="overflow:hidden; border:2px solid rgba(39, 174, 96, 0.2);">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" style="width:100%; height:100%; object-fit:cover;">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <div class="clinical-hero">
            <div>
                <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 0.5rem;">Bed & Ward Management</h1>
                <p style="opacity: 0.9; font-size: 1.3rem;">Real-time inpatient flow tracking and clinical resource optimization.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/bed/add-bed.php" class="btn btn-primary" style="background:white; color:#27ae60; border:none; padding:1.2rem 2.5rem; font-weight:700; border-radius:15px; box-shadow:0 10px 20px rgba(0,0,0,0.1);"><span class="btn-text">
                <i class="fas fa-plus"></i> Add New Bed Resource
            </span></a>
        </div>

        <?php if ($occup_rate >= 80): ?>
        <div class="alert-glass">
            <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
            <div>
                <h4 style="margin:0; font-weight:800;">Critical Capacity Warning</h4>
                <p style="margin:0.2rem 0 0; opacity:0.8; font-size:1.1rem;">Inpatient occupancy has reached <strong><?= $occup_rate ?>%</strong>. Coordinate with triage for potential diversion.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="occupancy-meter-wrap">
            <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                <div>
                    <h2 style="font-size:1.8rem; font-weight:800; color:var(--text-primary);">Overall Occupancy</h2>
                    <p style="color:var(--text-muted); font-size:1.1rem; margin-top:0.3rem;">Live telemetry across all clinical wards</p>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:3.5rem; font-weight:900; color:<?= $occup_rate >= 80 ? '#E74C3C' : '#27AE60' ?>; line-height:1;"><?= $occup_rate ?>%</div>
                    <div style="font-size:0.9rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Admission Rate</div>
                </div>
            </div>

            <div class="meter-container">
                <div class="meter-segment" style="width:<?= $occup_rate ?>%; background:#E74C3C;"></div>
                <div class="meter-segment" style="width:<?= ($maint_beds/$total_beds)*100 ?>%; background:#F39C12;"></div>
                <div class="meter-segment" style="width:<?= ($avail_beds/$total_beds)*100 ?>%; background:#27AE60;"></div>
            </div>

            <div class="stat-v2-grid">
                <div class="stat-pill"><i class="fas fa-bed" style="color:#27AE60;"></i><div><strong><?= $avail_beds ?></strong><span>Available</span></div></div>
                <div class="stat-pill"><i class="fas fa-user-injured" style="color:#E74C3C;"></i><div><strong><?= $occup_beds ?></strong><span>Occupied</span></div></div>
                <div class="stat-pill"><i class="fas fa-tools" style="color:#F39C12;"></i><div><strong><?= $maint_beds ?></strong><span>Maint.</span></div></div>
                <div class="stat-pill"><i class="fas fa-hospital-user" style="color:var(--primary);"></i><div><strong><?= $total_beds ?></strong><span>Capacity</span></div></div>
            </div>
        </div>

        <div class="adm-card" style="padding:2.5rem; border-radius:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                <h3 style="font-size:1.7rem; font-weight:800; display:flex; align-items:center; gap:1rem;"><i class="fas fa-clipboard-list" style="color:var(--primary);"></i> Bed Registry</h3>
                <form method="get" id="wardFilter">
                    <select name="ward" class="form-control" style="width:200px; border-radius:12px;" onchange="this.form.submit()">
                        <option value="">All Clinical Wards</option>
                        <?php
                        $wards = mysqli_query($conn, "SELECT DISTINCT ward FROM beds ORDER BY ward");
                        while ($w = mysqli_fetch_assoc($wards)): ?>
                        <option value="<?= htmlspecialchars($w['ward']) ?>" <?= (($_GET['ward'] ?? '')===$w['ward']) ? 'selected' : '' ?>><?= htmlspecialchars($w['ward']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>

            <table class="clinical-table display responsive nowrap" id="bedsTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Bed Reference</th>
                        <th>Ward / Location</th>
                        <th>Class</th>
                        <th>Tariff (GH₵)</th>
                        <th>Active Patient</th>
                        <th>Resource Status</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $ward_filter = isset($_GET['ward']) ? mysqli_real_escape_string($conn, $_GET['ward']) : '';
                    $where = $ward_filter ? "WHERE b.ward = '$ward_filter'" : '';
                    $sql = "SELECT b.*, u.name AS patient_name
                            FROM beds b
                            LEFT JOIN bed_assignments ba ON b.id = ba.bed_id AND ba.status = 'Active'
                            LEFT JOIN patients pat ON ba.patient_id = pat.id
                            LEFT JOIN users u ON pat.user_id = u.id
                            $where
                            ORDER BY b.ward ASC, b.bed_number ASC";
                    $q = mysqli_query($conn, $sql);
                    while ($bed = mysqli_fetch_assoc($q)):
                        $s_cls = 'status-' . strtolower($bed['status']);
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:800; font-size:1.3rem; color:var(--text-primary);"><?= htmlspecialchars($bed['bed_number']) ?></div>
                            <div style="font-size:0.95rem; color:var(--text-muted); font-weight:600;"><?= htmlspecialchars($bed['bed_id']) ?></div>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);"><i class="fas fa-hospital-alt" style="margin-right:0.5rem; color:var(--primary);"></i> <?= htmlspecialchars($bed['ward']) ?></div>
                        </td>
                        <td><span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); font-weight:700;"><?= htmlspecialchars($bed['bed_type']) ?></span></td>
                        <td><div style="font-weight:800; color:var(--text-primary);">GH₵ <?= number_format($bed['daily_rate'], 2) ?></div><div style="font-size:0.8rem; color:var(--text-muted);">per 24h</div></td>
                        <td>
                            <?php if($bed['patient_name']): ?>
                                <div style="font-weight:700; color:var(--text-primary);"><i class="fas fa-user-injured" style="margin-right:0.5rem; color:#E74C3C;"></i> <?= htmlspecialchars($bed['patient_name']) ?></div>
                            <?php else: ?>
                                <span style="color:var(--text-muted); font-style:italic;">No active admission</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="bed-status-badge <?= $s_cls ?>"><?= $bed['status'] ?></span></td>
                        <td>
                            <div style="display:flex; gap:0.6rem;">
                                <a href="update.php?id=<?= $bed['id'] ?>" class="btn btn-warning btn-sm" style="border-radius:10px; width:40px; height:40px; padding:0; justify-content:center;"><i class="fas fa-edit"></i></a>
                                <?php if ($bed['status'] === 'Available'): ?>
                                    <a href="assign.php?bed_id=<?= $bed['id'] ?>" class="btn btn-primary btn-sm" style="border-radius:10px; padding:0 1.2rem;"><i class="fas fa-user-plus"></i> Assign</a>
                                <?php elseif ($bed['status'] === 'Occupied'): ?>
                                    <a href="discharge.php?bed_id=<?= $bed['id'] ?>" class="btn btn-danger btn-sm" style="border-radius:10px; padding:0 1.2rem;" onclick="return confirm('Initiate patient discharge?');"><i class="fas fa-door-open"></i> Discharge</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    $('#bedsTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: { search: "_INPUT_", searchPlaceholder: "Search bed number, ward or patient..." },
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