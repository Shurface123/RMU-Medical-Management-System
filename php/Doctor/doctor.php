<?php
include 'db_conn.php';

$active_page = 'doctors';
$page_title  = 'Doctors';
include '../includes/_sidebar.php';

// Fetch stats — doctors table joins users for name/email/phone/gender
$total_doc = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors d JOIN users u ON d.user_id=u.id WHERE u.is_active=1"))[0] ?? 0;
$male_doc  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors d JOIN users u ON d.user_id=u.id WHERE u.gender='Male'"))[0] ?? 0;
$fem_doc   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors d JOIN users u ON d.user_id=u.id WHERE u.gender='Female'"))[0] ?? 0;
$avail_doc = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors WHERE is_available=1"))[0] ?? 0;
?>

<main class="adm-main">
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
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Manage Doctors</h1>
                <p>View and manage all registered medical staff in the sickbay.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/Doctor/add-doctor.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-user-plus"></i> Add New Doctor
            </a>
        </div>

        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_doc; ?></div>
                <div class="adm-mini-card-label">Total Doctors</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num blue"><?php echo $male_doc; ?></div>
                <div class="adm-mini-card-label">Male</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $fem_doc; ?></div>
                <div class="adm-mini-card-label">Female</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $avail_doc; ?></div>
                <div class="adm-mini-card-label">Available Today</div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-list"></i> Doctor Registry</h3>
                <form method="get" class="adm-search-form" style="margin:0;">
                    <div class="adm-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="adm-search-input"
                               placeholder="Search by name or specialization..."
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Search</button>
                </form>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Doctor ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Specialization</th>
                            <th>Experience</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
                        $where  = $search ? "WHERE (u.name LIKE '%$search%' OR d.specialization LIKE '%$search%' OR d.doctor_id LIKE '%$search%')" : '';
                        $sql = "SELECT d.id, d.doctor_id, d.specialization, d.experience_years, d.is_available,
                                       u.name, u.gender, u.phone, u.email, u.is_active
                                FROM doctors d
                                JOIN users u ON d.user_id = u.id
                                $where
                                ORDER BY u.name ASC";
                        $query = mysqli_query($conn, $sql);
                        if (!$query || mysqli_num_rows($query) === 0) {
                            echo "<tr><td colspan='10' style='text-align:center;padding:3rem;color:var(--text-muted);'>
                                    No doctors found. <a href='add-doctor.php' style='color:var(--primary);font-weight:600;'>Add one now.</a>
                                  </td></tr>";
                        } else {
                            $n = 1;
                            while ($doc = mysqli_fetch_assoc($query)):
                                $available = $doc['is_available'] ? 'adm-badge adm-badge-success' : 'adm-badge adm-badge-warning';
                                $avail_lbl = $doc['is_available'] ? 'Available' : 'Unavailable';
                        ?>
                        <tr>
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($doc['doctor_id']); ?></span></td>
                            <td><strong><i class="fas fa-user-md" style="color:var(--primary);margin-right:.4rem;"></i><?php echo htmlspecialchars($doc['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($doc['gender']); ?></td>
                            <td><span class="adm-badge adm-badge-info"><?php echo htmlspecialchars($doc['specialization']); ?></span></td>
                            <td><?php echo $doc['experience_years'] ? $doc['experience_years'].' yrs' : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($doc['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($doc['email'] ?? 'N/A'); ?></td>
                            <td><span class="<?php echo $available; ?>"><?php echo $avail_lbl; ?></span></td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/Doctor/update.php?id=<?php echo $doc['id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="/RMU-Medical-Management-System/php/Doctor/Delete.php?id=<?php echo $doc['id']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm"
                                       onclick="return confirm('Remove Dr. <?php echo addslashes($doc['name']); ?>?');"><i class="fas fa-trash"></i> Delete</a>
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