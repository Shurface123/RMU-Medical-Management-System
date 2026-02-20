<?php
include 'db_conn.php';

$active_page = 'patients';
$page_title  = 'Patients';
include '../includes/_sidebar.php';

// Stats — patients table joins users for name/gender/phone/email
$total_pat   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients p JOIN users u ON p.user_id=u.id WHERE u.is_active=1"))[0] ?? 0;
$male_pat    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients p JOIN users u ON p.user_id=u.id WHERE u.gender='Male'"))[0] ?? 0;
$fem_pat     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients p JOIN users u ON p.user_id=u.id WHERE u.gender='Female'"))[0] ?? 0;
$students    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients WHERE is_student=1"))[0] ?? 0;
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
                <h1>Manage Patients</h1>
                <p>View and manage all registered patients in the sickbay system.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/patient/add-patient.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-user-plus"></i> Add Patient
            </a>
        </div>

        <!-- Triage Legend -->
        <div style="display:flex;gap:1rem;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;">
            <span style="font-size:.85rem;font-weight:600;color:var(--text-secondary);">Triage Key:</span>
            <span class="adm-triage adm-triage-emergency">🔴 Emergency</span>
            <span class="adm-triage adm-triage-urgent">🟡 Urgent</span>
            <span class="adm-triage adm-triage-routine">🟢 Routine</span>
        </div>

        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_pat; ?></div>
                <div class="adm-mini-card-label">Total Patients</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num blue"><?php echo $male_pat; ?></div>
                <div class="adm-mini-card-label">Male</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $fem_pat; ?></div>
                <div class="adm-mini-card-label">Female</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $students; ?></div>
                <div class="adm-mini-card-label">Students</div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-list"></i> Patient Registry</h3>
                <form method="get" class="adm-search-form" style="margin:0;">
                    <div class="adm-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="adm-search-input"
                               placeholder="Search by name, ID, or blood group..."
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
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Blood Grp</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Allergies</th>
                            <th>Triage</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
                        $where = $search ? "WHERE (u.name LIKE '%$search%' OR p.patient_id LIKE '%$search%' OR p.blood_group LIKE '%$search%')" : '';
                        $sql = "SELECT p.id, p.patient_id, p.blood_group, p.allergies, p.is_student,
                                       p.chronic_conditions,
                                       u.name, u.gender, u.phone, u.email, u.created_at
                                FROM patients p
                                JOIN users u ON p.user_id = u.id
                                $where
                                ORDER BY p.id DESC";
                        $query = mysqli_query($conn, $sql);
                        if (!$query || mysqli_num_rows($query) === 0) {
                            echo "<tr><td colspan='10' style='text-align:center;padding:3rem;color:var(--text-muted);'>
                                    No patients found. <a href='add-patient.php' style='color:var(--primary);font-weight:600;'>Register one now.</a>
                                  </td></tr>";
                        } else {
                            $n = 1;
                            while ($pat = mysqli_fetch_assoc($query)):
                                // Auto-assign triage based on chronic conditions / allergies
                                $conditions = strtolower($pat['chronic_conditions'] ?? '');
                                $allergies  = strtolower($pat['allergies'] ?? '');
                                if (str_contains($conditions, 'critical') || str_contains($allergies, 'severe')) {
                                    $triage_class = 'adm-triage adm-triage-emergency';
                                    $triage_label = '🔴 Emergency';
                                    $row_cls      = 'row-danger';
                                } elseif (!empty($pat['chronic_conditions'])) {
                                    $triage_class = 'adm-triage adm-triage-urgent';
                                    $triage_label = '🟡 Urgent';
                                    $row_cls      = 'row-warning';
                                } else {
                                    $triage_class = 'adm-triage adm-triage-routine';
                                    $triage_label = '🟢 Routine';
                                    $row_cls      = '';
                                }
                                $type_badge = $pat['is_student'] ? '<span class="adm-badge adm-badge-info">Student</span>' : '<span class="adm-badge adm-badge-primary">Staff/Other</span>';
                        ?>
                        <tr class="<?php echo $row_cls; ?>">
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($pat['patient_id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($pat['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($pat['gender']); ?></td>
                            <td><?php echo $pat['blood_group'] ? '<span class="adm-badge adm-badge-danger">'.htmlspecialchars($pat['blood_group']).'</span>' : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($pat['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo $type_badge; ?></td>
                            <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?php echo $pat['allergies'] ? htmlspecialchars($pat['allergies']) : '<span style="color:var(--text-muted);">None</span>'; ?>
                            </td>
                            <td><span class="<?php echo $triage_class; ?>"><?php echo $triage_label; ?></span></td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/patient/update.php?id=<?php echo $pat['id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="/RMU-Medical-Management-System/php/patient/Delete.php?id=<?php echo $pat['id']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm"
                                       onclick="return confirm('Remove this patient?');"><i class="fas fa-trash"></i></a>
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