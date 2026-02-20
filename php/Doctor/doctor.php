<?php
include 'db_conn.php';

$active_page = 'doctors';
$page_title  = 'Doctors';
include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <!-- Top Bar -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-md" style="color:var(--primary);margin-right:.8rem;"></i>Doctors</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <!-- Page Header -->
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Manage Doctors</h1>
                <p>View, search, and manage all registered doctors in the sickbay.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/Doctor/add-doctor.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-user-plus"></i> Add New Doctor
            </a>
        </div>

        <!-- Summary Strip -->
        <?php
        $total_doc = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors"))[0] ?? 0;
        $male_doc  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors WHERE gender='Male' OR gender='male'"))[0] ?? 0;
        $fem_doc   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors WHERE gender='Female' OR gender='female'"))[0] ?? 0;
        ?>
        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_doc; ?></div>
                <div class="adm-mini-card-label">Total Doctors</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $male_doc; ?></div>
                <div class="adm-mini-card-label">Male</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $fem_doc; ?></div>
                <div class="adm-mini-card-label">Female</div>
            </div>
        </div>

        <!-- Search + Table Card -->
        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-list"></i> Doctor Records</h3>
                <form method="post" action="search-con.php" class="adm-search-form" style="margin:0;">
                    <div class="adm-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="adm-search-input" placeholder="Search by name or specialization...">
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Doctor ID</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Specialization</th>
                            <th>Available Days</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM doctors ORDER BY id DESC";
                        $query = mysqli_query($conn, $sql);
                        if (!$query || mysqli_num_rows($query) === 0) {
                            echo "<tr><td colspan='7' style='text-align:center;padding:3rem;color:var(--text-muted);'>No doctors found. <a href='add-doctor.php' style='color:var(--primary);font-weight:600;'>Add one now.</a></td></tr>";
                        } else {
                            $n = 1;
                            while ($d = mysqli_fetch_assoc($query)):
                        ?>
                        <tr>
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($d['id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($d['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($d['gender'] ?? 'N/A'); ?></td>
                            <td><span class="adm-badge adm-badge-info"><?php echo htmlspecialchars($d['specialization']); ?></span></td>
                            <td><?php echo htmlspecialchars($d['available_days'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/Doctor/update-doctor.php?D_ID=<?php echo $d['id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="/RMU-Medical-Management-System/php/Doctor/Delete.php?D_ID=<?php echo $d['id']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm"
                                       onclick="return confirm('Delete this doctor?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
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
// Mobile sidebar
const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
});
overlay?.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
});
// Theme toggle
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = document.getElementById('themeIcon');
const html        = document.documentElement;
function applyTheme(t) {
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    themeIcon.className = t === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}
applyTheme(localStorage.getItem('rmu_theme') || 'light');
themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'));
</script>
</body>
</html>