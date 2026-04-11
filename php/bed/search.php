<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include 'db_conn.php';

$active_page = 'beds';
$page_title  = 'Search Beds';
include '../includes/_sidebar.php';

$search_query = trim($_POST['search'] ?? ($_GET['search'] ?? ''));
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-search"></i> Search Beds</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Search Bed Matrix</h1>
                <p>Find beds by number, ward, type, or status.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/bed/bed.php" class="btn btn-ghost"><span class="btn-text">
                <i class="fas fa-arrow-left"></i> Back to Beds
            </span></a>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-search"></i> Search Results</h3>
                <form method="get" action="search.php" class="adm-search-form" style="margin:0;display:flex;gap:.5rem;">
                    <input type="text" name="search" class="adm-search-input" placeholder="Keyword..." value="<?php echo htmlspecialchars($search_query); ?>" required>
                    <button type="submit" class="btn btn-primary"><span class="btn-text"><i class="fas fa-search"></i></span></button>
                </form>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
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
                        if ($search_query):
                            $search_term = "%{$search_query}%";
                            $sql = "SELECT b.*, u.name AS patient_name 
                                    FROM beds b 
                                    LEFT JOIN bed_assignments ba ON b.id = ba.bed_id AND ba.status = 'Active'
                                    LEFT JOIN patients pat ON ba.patient_id = pat.id
                                    LEFT JOIN users u ON pat.user_id = u.id
                                    WHERE b.bed_number LIKE ? 
                                       OR b.ward LIKE ? 
                                       OR b.bed_type LIKE ? 
                                       OR b.status LIKE ?
                                    ORDER BY b.ward ASC, b.bed_number ASC";
                            
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "ssss", $search_term, $search_term, $search_term, $search_term);
                            mysqli_stmt_execute($stmt);
                            $q = mysqli_stmt_get_result($stmt);
                            
                            if (mysqli_num_rows($q) === 0) {
                                echo "<tr><td colspan='8' style='text-align:center;padding:2rem;color:var(--text-muted);'>No matching beds found.</td></tr>";
                            } else {
                                $n = 1;
                                while ($bed = mysqli_fetch_assoc($q)):
                                    $sc_map = ['Available'=>'success','Occupied'=>'danger','Maintenance'=>'warning','Reserved'=>'info'];
                                    $sc = $sc_map[$bed['status']] ?? 'primary';
                                    $row_cls = $bed['status'] === 'Occupied' ? 'row-danger' : ($bed['status'] === 'Maintenance' ? 'row-warning' : '');
                        ?>
                        <tr class="<?php echo $row_cls; ?>">
                            <td><?php echo $n++; ?></td>
                            <td><strong><?php echo htmlspecialchars($bed['bed_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($bed['ward']); ?></td>
                            <td><?php echo htmlspecialchars($bed['bed_type']); ?></td>
                            <td><?php echo number_format((float)$bed['daily_rate'], 2); ?></td>
                            <td><?php echo $bed['patient_name'] ? htmlspecialchars($bed['patient_name']) : '<span style="color:var(--text-muted);">Vacant</span>'; ?></td>
                            <td><span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo $bed['status']; ?></span></td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/bed/update.php?id=<?php echo $bed['id']; ?>"
                                       class="btn btn-warning btn-sm"><span class="btn-text"><i class="fas fa-edit"></i></span></a>
                                    <?php if ($bed['status'] === 'Available'): ?>
                                    <a href="/RMU-Medical-Management-System/php/bed/assign.php?bed_id=<?php echo $bed['id']; ?>"
                                       class="btn btn-primary btn-sm"><span class="btn-text"><i class="fas fa-user-plus"></i></span></a>
                                    <?php elseif ($bed['status'] === 'Occupied'): ?>
                                    <a href="/RMU-Medical-Management-System/php/bed/discharge.php?bed_id=<?php echo $bed['id']; ?>"
                                       class="btn btn-danger btn-sm" onclick="return confirm('Discharge patient?');"><span class="btn-text"><i class="fas fa-sign-out-alt"></i></span></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                                endwhile; 
                            }
                            mysqli_stmt_close($stmt);
                        else:
                            echo "<tr><td colspan='8' style='text-align:center;padding:2rem;color:var(--text-muted);'>Please enter a search term.</td></tr>";
                        endif; 
                        ?>
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