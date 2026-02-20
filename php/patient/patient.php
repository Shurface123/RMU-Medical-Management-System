<?php
include 'db_conn.php';

$active_page = 'patients';
$page_title  = 'Patients';
include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-injured" style="color:var(--primary);margin-right:.8rem;"></i>Patients</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Patient Management</h1>
                <p>Track, triage, and manage all patient records. Color-coded by urgency level.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/patient/add-patient.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-user-plus"></i> Register Patient
            </a>
        </div>

        <!-- Triage Legend -->
        <div style="display:flex;gap:1rem;margin-bottom:2rem;flex-wrap:wrap;align-items:center;">
            <span style="font-size:1.3rem;color:var(--text-secondary);font-weight:600;">Triage Key:</span>
            <span class="adm-triage adm-triage-emergency">🔴 Emergency</span>
            <span class="adm-triage adm-triage-urgent">🟡 Urgent</span>
            <span class="adm-triage adm-triage-routine">🟢 Routine</span>
        </div>

        <?php
        $total_p   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients"))[0] ?? 0;
        $male_p    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients WHERE gender='Male' OR gender='male'"))[0] ?? 0;
        $female_p  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients WHERE gender='Female' OR gender='female'"))[0] ?? 0;
        ?>
        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_p; ?></div>
                <div class="adm-mini-card-label">Total Patients</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $male_p; ?></div>
                <div class="adm-mini-card-label">Male</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $female_p; ?></div>
                <div class="adm-mini-card-label">Female</div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-list"></i> Patient Records</h3>
                <form method="post" action="search.php" class="adm-search-form" style="margin:0;">
                    <div class="adm-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="adm-search-input" placeholder="Search by name or patient type...">
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient ID</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Triage</th>
                            <th>Admitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql   = "SELECT * FROM patients ORDER BY id DESC";
                        $query = mysqli_query($conn, $sql);
                        if (!$query || mysqli_num_rows($query) === 0) {
                            echo "<tr><td colspan='8' style='text-align:center;padding:3rem;color:var(--text-muted);'>No patients found. <a href='add-patient.php' style='color:var(--primary);font-weight:600;'>Register one now.</a></td></tr>";
                        } else {
                            $n = 1;
                            while ($p = mysqli_fetch_assoc($query)):
                                $type = strtolower($p['patient_type'] ?? 'routine');
                                if (str_contains($type, 'emerg')) {
                                    $triage_class = 'adm-triage adm-triage-emergency';
                                    $triage_label = '🔴 Emergency';
                                    $row_class    = 'row-danger';
                                } elseif (str_contains($type, 'urgent') || str_contains($type, 'semi')) {
                                    $triage_class = 'adm-triage adm-triage-urgent';
                                    $triage_label = '🟡 Urgent';
                                    $row_class    = 'row-warning';
                                } else {
                                    $triage_class = 'adm-triage adm-triage-routine';
                                    $triage_label = '🟢 Routine';
                                    $row_class    = '';
                                }
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($p['id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($p['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['gender'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($p['age'] ?? 'N/A'); ?></td>
                            <td><span class="<?php echo $triage_class; ?>"><?php echo $triage_label; ?></span></td>
                            <td style="font-size:1.25rem;color:var(--text-secondary);">
                                <?php echo htmlspecialchars($p['admit_date'] ?? 'N/A'); ?>
                            </td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/patient/update-patient.php?P_ID=<?php echo $p['id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="/RMU-Medical-Management-System/php/patient/Delete-patient.php?P_ID=<?php echo $p['id']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm"
                                       onclick="return confirm('Delete this patient record?');"><i class="fas fa-trash"></i> Delete</a>
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