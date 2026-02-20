<?php
include 'db_conn.php';

$active_page = 'tests';
$page_title  = 'Lab Tests';
include '../includes/_sidebar.php';
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
                <h1>Lab Tests</h1>
                <p>Manage available diagnostic tests, pricing, and availability status.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/test/add-test.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-plus"></i> Add Test
            </a>
        </div>

        <?php
        $total    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tests"))[0] ?? 0;
        $active   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tests WHERE is_active = 1"))[0] ?? 0;
        $inactive = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tests WHERE is_active = 0"))[0] ?? 0;
        ?>
        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total; ?></div>
                <div class="adm-mini-card-label">Total Tests</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $active; ?></div>
                <div class="adm-mini-card-label">Active</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num red"><?php echo $inactive; ?></div>
                <div class="adm-mini-card-label">Inactive</div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-list"></i> Test Catalogue</h3>
                <form method="post" action="search.php" class="adm-search-form" style="margin:0;">
                    <div class="adm-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="adm-search-input" placeholder="Search by name or code...">
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Test Code</th>
                            <th>Test Name</th>
                            <th>Category</th>
                            <th>Price (GH₵)</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql   = "SELECT * FROM tests ORDER BY category, test_name";
                        $query = mysqli_query($conn, $sql);
                        if (!$query || mysqli_num_rows($query) === 0) {
                            echo "<tr><td colspan='8' style='text-align:center;padding:3rem;color:var(--text-muted);'>No tests found. <a href='add-test.php' style='color:var(--primary);font-weight:600;'>Add one now.</a></td></tr>";
                        } else {
                            $n = 1;
                            while ($test = mysqli_fetch_assoc($query)):
                        ?>
                        <tr>
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($test['test_code']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($test['test_name']); ?></strong></td>
                            <td><span class="adm-badge adm-badge-info"><?php echo htmlspecialchars($test['category']); ?></span></td>
                            <td><?php echo number_format($test['price'], 2); ?></td>
                            <td><?php echo $test['duration_mins'] ? $test['duration_mins'] . ' mins' : 'N/A'; ?></td>
                            <td>
                                <?php if ($test['is_active']): ?>
                                    <span class="adm-badge adm-badge-success"><i class="fas fa-check-circle"></i> Active</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge-danger"><i class="fas fa-times-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/test/update.php?id=<?php echo $test['id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="/RMU-Medical-Management-System/php/test/Delete.php?id=<?php echo $test['id']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm"
                                       onclick="return confirm('Delete this test?');"><i class="fas fa-trash"></i> Delete</a>
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