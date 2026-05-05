<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'doctors';
$page_title  = 'Doctors Registry';
include '../includes/_sidebar.php';

// Fetch stats
$total_doc = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors d JOIN users u ON d.user_id=u.id WHERE u.is_active=1"))[0] ?? 0;
$male_doc  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors d JOIN users u ON d.user_id=u.id WHERE u.gender='Male'"))[0] ?? 0;
$fem_doc   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors d JOIN users u ON d.user_id=u.id WHERE u.gender='Female'"))[0] ?? 0;
$avail_doc = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors WHERE is_available=1"))[0] ?? 0;
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
    background: linear-gradient(135deg, #2F80ED 0%, #1a2a6c 100%);
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

/* DataTable Overrides for Glassmorphism */
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
.dataTables_wrapper .dataTables_filter input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-light); }

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
            <span class="adm-page-title"><i class="fas fa-user-md" style="color:var(--primary);margin-right:.8rem;"></i>Medical Personnel</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar" style="overflow:hidden; border:2px solid var(--primary-light);">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" style="width:100%; height:100%; object-fit:cover;">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <div class="clinical-hero">
            <div>
                <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 0.5rem;">Doctors Registry</h1>
                <p style="opacity: 0.9; font-size: 1.3rem;">Manage credentials, specializations, and daily availability of clinical staff.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/Doctor/add-doctor.php" class="btn btn-primary" style="background:white; color:var(--primary); border:none; padding:1.2rem 2.5rem; font-weight:700; border-radius:15px; box-shadow:0 10px 20px rgba(0,0,0,0.1);"><span class="btn-text">
                <i class="fas fa-plus-circle"></i> Onboard New Doctor
            </span></a>
        </div>

        <div class="stat-v2-grid">
            <div class="stat-v2-card">
                <div class="stat-v2-icon" style="background:var(--primary-light); color:var(--primary);"><i class="fas fa-user-md"></i></div>
                <div class="stat-v2-info"><h3><?= $total_doc ?></h3><p>Total Staff</p></div>
            </div>
            <div class="stat-v2-card">
                <div class="stat-v2-icon" style="background:var(--info-light); color:var(--info);"><i class="fas fa-mars"></i></div>
                <div class="stat-v2-info"><h3><?= $male_doc ?></h3><p>Male Physicians</p></div>
            </div>
            <div class="stat-v2-card">
                <div class="stat-v2-icon" style="background:var(--danger-light); color:var(--danger);"><i class="fas fa-venus"></i></div>
                <div class="stat-v2-info"><h3><?= $fem_doc ?></h3><p>Female Physicians</p></div>
            </div>
            <div class="stat-v2-card">
                <div class="stat-v2-icon" style="background:var(--success-light); color:var(--success);"><i class="fas fa-clock"></i></div>
                <div class="stat-v2-info"><h3><?= $avail_doc ?></h3><p>Live Available</p></div>
            </div>
        </div>

        <div class="adm-card" style="padding:2.5rem; border-radius:24px;">
            <table class="clinical-table display responsive nowrap" id="doctorsTable">
                <thead>
                    <tr>
                        <th>Doctor ID</th>
                        <th>Name & Spec</th>
                        <th>Experience</th>
                        <th>Contact</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT d.id, d.doctor_id, d.specialization, d.experience_years, d.is_available,
                                   u.name, u.gender, u.phone, u.email, u.is_active
                            FROM doctors d
                            JOIN users u ON d.user_id = u.id
                            ORDER BY u.name ASC";
                    $query = mysqli_query($conn, $sql);
                    while ($doc = mysqli_fetch_assoc($query)):
                        $avail_class = $doc['is_available'] ? 'adm-badge-success' : 'adm-badge-warning';
                        $avail_lbl   = $doc['is_available'] ? 'AVAILABLE' : 'OFF DUTY';
                    ?>
                    <tr>
                        <td><span class="adm-badge adm-badge-primary" style="font-weight:700; letter-spacing:1px;"><?= htmlspecialchars($doc['doctor_id']) ?></span></td>
                        <td>
                            <div style="font-weight:800; font-size:1.4rem; color:var(--text-primary);">Dr. <?= htmlspecialchars($doc['name']) ?></div>
                            <div style="font-size:1rem; color:var(--primary); font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-top:0.3rem;"><i class="fas fa-stethoscope"></i> <?= htmlspecialchars($doc['specialization'] ?? 'General Practitioner') ?></div>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);"><?= $doc['experience_years'] ?> Years</div>
                            <div style="font-size:0.9rem; color:var(--text-muted);">Medical Practice</div>
                        </td>
                        <td>
                            <div style="font-size:1.15rem; color:var(--text-primary); font-weight:600;"><i class="fas fa-phone" style="color:var(--text-muted); margin-right:0.5rem; font-size:0.9rem;"></i><?= htmlspecialchars($doc['phone']) ?></div>
                            <div style="font-size:1.1rem; color:var(--text-muted); margin-top:0.3rem;"><i class="fas fa-envelope" style="margin-right:0.5rem; font-size:0.9rem;"></i><?= htmlspecialchars($doc['email']) ?></div>
                        </td>
                        <td><span class="adm-badge <?= $avail_class ?>" style="font-size:1rem; padding:0.6rem 1.2rem; border-radius:12px;"><?= $avail_lbl ?></span></td>
                        <td>
                            <div style="display:flex; gap:0.8rem;">
                                <a href="update.php?id=<?= $doc['id'] ?>" class="btn btn-warning btn-sm" style="border-radius:10px; width:40px; height:40px; padding:0; justify-content:center;" title="Edit Profile"><i class="fas fa-user-edit"></i></a>
                                <a href="Delete.php?id=<?= $doc['id'] ?>" class="btn btn-danger btn-sm" style="border-radius:10px; width:40px; height:40px; padding:0; justify-content:center;" title="Deactivate" onclick="return confirm('Confirm deactivation of Dr. <?= addslashes($doc['name']) ?>?');"><i class="fas fa-trash-alt"></i></a>
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
    $('#doctorsTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search doctors, IDs or specializations...",
            lengthMenu: "Show _MENU_ entries"
        },
        dom: '<"top"f>rt<"bottom"lip><"clear">',
    });

    // Theme Toggle
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon   = document.getElementById('themeIcon');
    const html        = document.documentElement;
    function applyTheme(t) { html.setAttribute('data-theme',t); localStorage.setItem('rmu_theme',t); themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon'; }
    themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
    
    // Sidebar toggle
    const sidebar = document.getElementById('admSidebar');
    const overlay = document.getElementById('admOverlay');
    document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
    overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
});
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>