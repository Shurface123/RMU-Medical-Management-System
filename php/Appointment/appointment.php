<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'appointments';
$page_title  = 'Clinical Appointments';
include '../includes/_sidebar.php';

// ── Status toggle action ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $apt_id     = (int)$_POST['apt_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $allowed    = ['Pending','Confirmed','Completed','Cancelled','No-Show'];
    if (in_array($new_status, $allowed) && $apt_id > 0) {
        mysqli_query($conn, "UPDATE appointments SET status='$new_status' WHERE id=$apt_id");
    }
    header('Location: appointment.php');
    exit;
}

// ── Stats ─────────────────────────────────────────────────────────────────
$today = date('Y-m-d');
$stat_today    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM appointments WHERE appointment_date='$today'"))[0] ?? 0;
$stat_pending  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM appointments WHERE status='Pending'"))[0] ?? 0;
$stat_confirmed= mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM appointments WHERE status='Confirmed'"))[0] ?? 0;
$stat_total    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM appointments"))[0] ?? 0;
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
}

/* Status Badge Styles */
.apt-status { padding: 0.6rem 1.2rem; border-radius: 12px; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
.status-pending { background: rgba(243, 156, 18, 0.1); color: #F39C12; }
.status-confirmed { background: rgba(47, 128, 237, 0.1); color: #2F80ED; }
.status-completed { background: rgba(39, 174, 96, 0.1); color: #27AE60; }
.status-cancelled { background: rgba(231, 76, 60, 0.1); color: #E74C3C; }
.status-noshow { background: rgba(52, 73, 94, 0.1); color: #34495E; }

.urgency-indicator { font-weight: 800; font-size: 0.85rem; padding: 0.3rem 0.8rem; border-radius: 6px; }
.urg-emergency { color: #E74C3C; border: 1px solid #E74C3C; }
.urg-urgent { color: #F39C12; border: 1px solid #F39C12; }
.urg-routine { color: #27AE60; border: 1px solid #27AE60; }

.clinical-table tbody tr.is-today { border-left: 5px solid var(--primary); }

/* DataTable Fixes */
.dataTables_wrapper .dataTables_filter input { padding: 0.8rem 1.5rem; border-radius: 12px; border: 1.5px solid var(--border); background: var(--surface-2); color: var(--text-primary); margin-left: 1rem; outline: none; }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-calendar-check" style="color:var(--primary);margin-right:.8rem;"></i>Scheduling Center</span>
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
                <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 0.5rem;">Appointment Matrix</h1>
                <p style="opacity: 0.9; font-size: 1.3rem;">Synchronized view of clinical schedules, triage queues, and practitioner availability.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/booking.php" class="btn btn-primary" style="background:white; color:var(--primary); border:none; padding:1.2rem 2.5rem; font-weight:700; border-radius:15px; box-shadow:0 10px 20px rgba(0,0,0,0.1);"><span class="btn-text">
                <i class="fas fa-plus"></i> New Clinical Booking
            </span></a>
        </div>

        <div class="stat-v2-grid">
            <div class="stat-v2-card">
                <div style="font-size:3rem; font-weight:900; color:var(--primary);"><?= $stat_total ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Total Records</div>
            </div>
            <div class="stat-v2-card" style="border-left:4px solid var(--primary);">
                <div style="font-size:3rem; font-weight:900; color:var(--primary);"><?= $stat_today ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Today</div>
            </div>
            <div class="stat-v2-card" style="border-left:4px solid var(--warning);">
                <div style="font-size:3rem; font-weight:900; color:var(--warning);"><?= $stat_pending ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Pending</div>
            </div>
            <div class="stat-v2-card" style="border-left:4px solid var(--success);">
                <div style="font-size:3rem; font-weight:900; color:var(--success);"><?= $stat_confirmed ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Confirmed</div>
            </div>
        </div>

        <div class="adm-card" style="padding:2.5rem; border-radius:24px;">
            <table class="clinical-table display responsive nowrap" id="appointmentsTable">
                <thead>
                    <tr>
                        <th>Apt ID</th>
                        <th>Patient</th>
                        <th>Practitioner</th>
                        <th>Date & Time</th>
                        <th>Service</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT a.id, a.appointment_id, a.appointment_date, a.appointment_time,
                                   a.service_type, a.urgency_level, a.status,
                                   u_p.name AS patient_name, p.patient_id AS patient_code,
                                   u_d.name AS doctor_name, d.specialization
                            FROM appointments a
                            LEFT JOIN patients p ON a.patient_id = p.id
                            LEFT JOIN users u_p ON p.user_id = u_p.id
                            JOIN doctors d ON a.doctor_id = d.id
                            JOIN users u_d ON d.user_id = u_d.id
                            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                    $q = mysqli_query($conn, $sql);
                    while ($a = mysqli_fetch_assoc($q)):
                        $is_today = ($a['appointment_date'] === $today);
                        $s_class = 'status-' . strtolower(str_replace('-', '', $a['status']));
                        $u_class = 'urg-' . strtolower($a['urgency_level'] ?? 'routine');
                    ?>
                    <tr class="<?= $is_today ? 'is-today' : '' ?>">
                        <td><span class="adm-badge adm-badge-primary" style="font-weight:700;"><?= htmlspecialchars($a['appointment_id']) ?></span></td>
                        <td>
                            <div style="font-weight:800; font-size:1.3rem; color:var(--text-primary);"><?= htmlspecialchars($a['patient_name'] ?? 'Walk-in') ?></div>
                            <div style="font-size:0.95rem; color:var(--text-muted);"><?= $a['patient_code'] ?: 'ID Not Assigned' ?></div>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);">Dr. <?= htmlspecialchars($a['doctor_name']) ?></div>
                            <div style="font-size:0.95rem; color:var(--primary); font-weight:600;"><?= htmlspecialchars($a['specialization']) ?></div>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);"><?= date('d M Y', strtotime($a['appointment_date'])) ?></div>
                            <div style="font-size:1.1rem; color:var(--text-muted); font-weight:600;"><?= date('g:i A', strtotime($a['appointment_time'])) ?></div>
                        </td>
                        <td><span style="font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($a['service_type']) ?></span></td>
                        <td><span class="urgency-indicator <?= $u_class ?>"><?= $a['urgency_level'] ?: 'Routine' ?></span></td>
                        <td><span class="apt-status <?= $s_class ?>"><?= $a['status'] ?></span></td>
                        <td>
                            <form method="post" style="display:flex; gap:0.5rem; align-items:center;">
                                <input type="hidden" name="apt_id" value="<?= $a['id'] ?>">
                                <select name="new_status" class="form-control" style="width:110px; padding:0.5rem; font-size:0.9rem; height:35px;">
                                    <?php foreach (['Pending','Confirmed','Completed','Cancelled','No-Show'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($a['status']===$opt)?'selected':'' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="toggle_status" class="btn btn-primary" style="padding:0; width:35px; height:35px; border-radius:8px; justify-content:center;" title="Sync Status"><i class="fas fa-sync-alt" style="font-size:0.9rem;"></i></button>
                            </form>
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
    $('#appointmentsTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: { search: "_INPUT_", searchPlaceholder: "Search appointments..." },
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