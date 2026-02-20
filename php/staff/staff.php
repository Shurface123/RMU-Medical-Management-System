<?php
include 'db_conn.php';

$active_page = 'staff';
$page_title  = 'Staff';
include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-nurse" style="color:var(--primary);margin-right:.8rem;"></i>Staff</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Manage Staff</h1>
                <p>View and manage all medical and administrative staff members.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/staff/add-staff.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-user-plus"></i> Add New Staff
            </a>
        </div>

        <?php
        $total_staff = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff"))[0] ?? 0;
        $male_staff  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff WHERE Gender='Male' OR Gender='male'"))[0] ?? 0;
        $fem_staff   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff WHERE Gender='Female' OR Gender='female'"))[0] ?? 0;
        ?>
        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_staff; ?></div>
                <div class="adm-mini-card-label">Total Staff</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $male_staff; ?></div>
                <div class="adm-mini-card-label">Male</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $fem_staff; ?></div>
                <div class="adm-mini-card-label">Female</div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-list"></i> Staff Records</h3>
                <form method="post" action="search.php" class="adm-search-form" style="margin:0;">
                    <div class="adm-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="adm-search-input" placeholder="Search by name or work day...">
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Staff ID</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Work Day</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM staff ORDER BY S_ID DESC";
                        $query = mysqli_query($conn, $sql);
                        if (!$query || mysqli_num_rows($query) === 0) {
                            echo "<tr><td colspan='6' style='text-align:center;padding:3rem;color:var(--text-muted);'>No staff found. <a href='add-staff.php' style='color:var(--primary);font-weight:600;'>Add one now.</a></td></tr>";
                        } else {
                            $n = 1;
                            while ($s = mysqli_fetch_assoc($query)):
                        ?>
                        <tr>
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($s['S_ID']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($s['S_Name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($s['Gender']); ?></td>
                            <td><?php echo htmlspecialchars($s['Work_Day']); ?></td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/staff/update.php?S_ID=<?php echo $s['S_ID']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="/RMU-Medical-Management-System/php/staff/Delete.php?S_ID=<?php echo $s['S_ID']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm"
                                       onclick="return confirm('Delete this staff member?');"><i class="fas fa-trash"></i> Delete</a>
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