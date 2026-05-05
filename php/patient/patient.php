<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'patients';
$page_title  = 'Patient Registry';
include '../includes/_sidebar.php';

// Stats
$total_pat = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients p JOIN users u ON p.user_id=u.id WHERE u.is_active=1"))[0] ?? 0;
$male_pat  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients p JOIN users u ON p.user_id=u.id WHERE u.gender='Male'"))[0] ?? 0;
$fem_pat   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients p JOIN users u ON p.user_id=u.id WHERE u.gender='Female'"))[0] ?? 0;
$students  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients WHERE is_student=1"))[0] ?? 0;
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
    background: linear-gradient(135deg, #E74C3C 0%, #1a2a6c 100%);
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
.clinical-hero::after {
    content: '';
    position: absolute;
    right: -50px;
    bottom: -50px;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
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
    box-shadow: var(--shadow-sm);
}
.stat-v2-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }

.stat-v2-icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
}

.stat-v2-info h3 { font-size: 2.4rem; font-weight: 800; color: var(--text-primary); margin: 0; line-height: 1; }
.stat-v2-info p { font-size: 1.1rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-top: 0.5rem; }

/* Triage Markers */
.triage-indicator { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 0.5rem; }
.triage-red { background: #dc3545; box-shadow: 0 0 10px rgba(220, 53, 69, 0.4); }
.triage-yellow { background: #ffc107; box-shadow: 0 0 10px rgba(255, 193, 7, 0.4); }
.triage-green { background: #28a745; box-shadow: 0 0 10px rgba(40, 167, 69, 0.4); }

/* DataTable Overrides */
.dataTables_wrapper .dataTables_filter input {
    padding: 0.8rem 1.5rem;
    border-radius: 12px;
    border: 1.5px solid var(--border);
    background: var(--surface-2);
    color: var(--text-primary);
    margin-left: 1rem;
    outline: none;
    transition: var(--transition);
}
.clinical-table { width: 100% !important; border-collapse: separate !important; border-spacing: 0 8px !important; }
.clinical-table thead th { background: transparent; border: none; color: var(--text-muted); font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px; padding: 1.5rem !important; }
.clinical-table tbody tr { background: var(--surface); transition: var(--transition); box-shadow: var(--shadow-sm); }
.clinical-table tbody tr:hover { transform: scale(1.005); box-shadow: var(--shadow-md); }
.clinical-table tbody td { padding: 1.5rem !important; border: none !important; color: var(--text-primary); vertical-align: middle; }
.clinical-table tbody td:first-child { border-radius: 12px 0 0 12px; }
.clinical-table tbody td:last-child { border-radius: 0 12px 12px 0; }

[data-theme="dark"] .clinical-table tbody tr { background: var(--surface-2); }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-injured" style="color:var(--danger);margin-right:.8rem;"></i>Patient Records</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar" style="overflow:hidden; border:2px solid var(--danger-light);">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" style="width:100%; height:100%; object-fit:cover;">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <div class="clinical-hero">
            <div>
                <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 0.5rem;">Patient Registry</h1>
                <p style="opacity: 0.9; font-size: 1.3rem;">Unified database for medical records, student health profiles, and triage monitoring.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/patient/add-patient.php" class="btn btn-primary" style="background:white; color:var(--danger); border:none; padding:1.2rem 2.5rem; font-weight:700; border-radius:15px; box-shadow:0 10px 20px rgba(0,0,0,0.1);"><span class="btn-text">
                <i class="fas fa-plus-circle"></i> Register New Patient
            </span></a>
        </div>

        <div class="stat-v2-grid">
            <div class="stat-v2-card">
                <div class="stat-v2-icon" style="background:var(--danger-light); color:var(--danger);"><i class="fas fa-users"></i></div>
                <div class="stat-v2-info"><h3><?= $total_pat ?></h3><p>Registered</p></div>
            </div>
            <div class="stat-v2-card">
                <div class="stat-v2-icon" style="background:var(--info-light); color:var(--info);"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-v2-info"><h3><?= $students ?></h3><p>RMU Students</p></div>
            </div>
            <div class="stat-v2-card">
                <div class="stat-v2-icon" style="background:var(--primary-light); color:var(--primary);"><i class="fas fa-mars"></i></div>
                <div class="stat-v2-info"><h3><?= $male_pat ?></h3><p>Male Patients</p></div>
            </div>
            <div class="stat-v2-card">
                <div class="stat-v2-icon" style="background:var(--accent); background:rgba(233,30,99,0.1); color:#E91E63;"><i class="fas fa-venus"></i></div>
                <div class="stat-v2-info"><h3><?= $fem_pat ?></h3><p>Female Patients</p></div>
            </div>
        </div>

        <div class="adm-card" style="padding:2.5rem; border-radius:24px;">
            <table class="clinical-table display responsive nowrap" id="patientsTable">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Full Name</th>
                        <th>Classification</th>
                        <th>Clinical Flags</th>
                        <th>Triage</th>
                        <th>Contact Info</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT p.id, p.patient_id, p.blood_group, p.allergies, p.is_student,
                                   p.chronic_conditions, u.name, u.gender, u.phone, u.email
                            FROM patients p
                            JOIN users u ON p.user_id = u.id
                            ORDER BY p.id DESC";
                    $query = mysqli_query($conn, $sql);
                    while ($pat = mysqli_fetch_assoc($query)):
                        // Triage Logic
                        $conditions = strtolower($pat['chronic_conditions'] ?? '');
                        $allergies  = strtolower($pat['allergies'] ?? '');
                        if (str_contains($conditions, 'critical') || str_contains($allergies, 'severe')) {
                            $t_cls = 'triage-red'; $t_lbl = 'EMERGENCY';
                        } elseif (!empty($pat['chronic_conditions'])) {
                            $t_cls = 'triage-yellow'; $t_lbl = 'URGENT';
                        } else {
                            $t_cls = 'triage-green'; $t_lbl = 'ROUTINE';
                        }
                    ?>
                    <tr>
                        <td><span class="adm-badge adm-badge-primary" style="font-weight:700; letter-spacing:1px;"><?= htmlspecialchars($pat['patient_id']) ?></span></td>
                        <td>
                            <div style="font-weight:800; font-size:1.4rem; color:var(--text-primary);"><?= htmlspecialchars($pat['name']) ?></div>
                            <div style="font-size:1rem; color:var(--text-muted); margin-top:0.3rem;"><i class="fas fa-venus-mars"></i> <?= htmlspecialchars($pat['gender']) ?> &bull; <?= $pat['blood_group'] ?: 'No Blood Group' ?></div>
                        </td>
                        <td>
                            <?php if($pat['is_student']): ?>
                                <span class="adm-badge adm-badge-info" style="font-weight:700;"><i class="fas fa-graduation-cap"></i> STUDENT</span>
                            <?php else: ?>
                                <span class="adm-badge adm-badge-primary" style="font-weight:700;"><i class="fas fa-user-tie"></i> STAFF/EXT</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="max-width:200px; font-size:1.1rem; line-height:1.4;">
                                <?php if($pat['allergies']): ?>
                                    <span style="color:var(--danger); font-weight:700;"><i class="fas fa-exclamation-circle"></i> Allergies:</span> <?= htmlspecialchars($pat['allergies']) ?>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">No known clinical flags</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.5rem; font-weight:700; font-size:1.1rem;">
                                <span class="triage-indicator <?= $t_cls ?>"></span>
                                <span style="color:var(--text-primary);"><?= $t_lbl ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:1.1rem; color:var(--text-primary); font-weight:600;"><i class="fas fa-phone" style="color:var(--text-muted); margin-right:0.5rem;"></i><?= htmlspecialchars($pat['phone']) ?></div>
                            <div style="font-size:1.05rem; color:var(--text-muted); margin-top:0.3rem; overflow:hidden; text-overflow:ellipsis; width:150px;"><i class="fas fa-envelope" style="margin-right:0.5rem;"></i><?= htmlspecialchars($pat['email']) ?></div>
                        </td>
                        <td>
                            <div style="display:flex; gap:0.8rem;">
                                <a href="update.php?id=<?= $pat['id'] ?>" class="btn btn-warning btn-sm" style="border-radius:10px; width:40px; height:40px; padding:0; justify-content:center;" title="Edit Profile"><i class="fas fa-edit"></i></a>
                                <a href="Delete.php?id=<?= $pat['id'] ?>" class="btn btn-danger btn-sm" style="border-radius:10px; width:40px; height:40px; padding:0; justify-content:center;" title="Delete Record" onclick="return confirm('Confirm permanent deletion of this record?');"><i class="fas fa-trash-alt"></i></a>
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
    $('#patientsTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records, IDs, phone numbers...",
        },
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