<?php
include '../db_conn.php';

$active_page = 'appointments';
$page_title  = 'Appointments';
include '../includes/_sidebar.php';

// ── Filters ────────────────────────────────────────────────────────────────
$filter_date   = isset($_GET['date'])   ? mysqli_real_escape_string($conn, $_GET['date'])   : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$filter_doctor = isset($_GET['doctor']) ? (int)$_GET['doctor']                               : 0;
$search        = isset($_GET['q'])      ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';

$where_parts = [];
if ($filter_date)   $where_parts[] = "a.appointment_date = '$filter_date'";
if ($filter_status) $where_parts[] = "a.status = '$filter_status'";
if ($filter_doctor) $where_parts[] = "a.doctor_id = $filter_doctor";
if ($search)        $where_parts[] = "(u_p.name LIKE '%$search%' OR a.appointment_id LIKE '%$search%')";
$where = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

// ── Status toggle action ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $apt_id     = (int)$_POST['apt_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $allowed    = ['Pending','Confirmed','Completed','Cancelled','No-Show'];
    if (in_array($new_status, $allowed) && $apt_id > 0) {
        mysqli_query($conn, "UPDATE appointments SET status='$new_status' WHERE id=$apt_id");
    }
    $redirect = '?' . http_build_query(array_filter(['date'=>$_GET['date']??'','status'=>$_GET['status']??'','doctor'=>$_GET['doctor']??'','q'=>$_GET['q']??'']));
    header('Location: appointment.php' . $redirect);
    exit;
}

// ── Stats ─────────────────────────────────────────────────────────────────
$today = date('Y-m-d');
$stat_today    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM appointments WHERE appointment_date='$today'"))[0] ?? 0;
$stat_pending  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM appointments WHERE status='Pending'"))[0] ?? 0;
$stat_confirmed= mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM appointments WHERE status='Confirmed'"))[0] ?? 0;
$stat_total    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM appointments"))[0] ?? 0;

// ── Doctors list for filter dropdown ─────────────────────────────────────
$doctors_dd = mysqli_query($conn, "SELECT d.id, u.name FROM doctors d JOIN users u ON d.user_id=u.id ORDER BY u.name");
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-calendar-alt" style="color:var(--primary);margin-right:.8rem;"></i>Appointments</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Appointment Management</h1>
                <p>View, filter, and update the status of all patient appointments.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/booking.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-plus"></i> New Appointment
            </a>
        </div>

        <!-- Stats -->
        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $stat_total; ?></div>
                <div class="adm-mini-card-label">Total</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num blue"><?php echo $stat_today; ?></div>
                <div class="adm-mini-card-label">Today</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $stat_pending; ?></div>
                <div class="adm-mini-card-label">Pending</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $stat_confirmed; ?></div>
                <div class="adm-mini-card-label">Confirmed</div>
            </div>
        </div>

        <!-- Filters Bar -->
        <form method="get" class="adm-card" style="padding:1rem 1.5rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;margin-bottom:1rem;">
            <div style="flex:1;min-width:160px;">
                <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.3rem;color:var(--text-secondary);">Search</label>
                <input type="text" name="q" class="adm-search-input" placeholder="Patient name or Apt ID" value="<?php echo htmlspecialchars($_GET['q']??''); ?>">
            </div>
            <div style="min-width:140px;">
                <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.3rem;color:var(--text-secondary);">Date</label>
                <input type="date" name="date" class="adm-search-input" value="<?php echo htmlspecialchars($_GET['date']??''); ?>">
            </div>
            <div style="min-width:140px;">
                <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.3rem;color:var(--text-secondary);">Status</label>
                <select name="status" class="adm-search-input">
                    <option value="">All Statuses</option>
                    <?php foreach (['Pending','Confirmed','Completed','Cancelled','No-Show'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo ($filter_status===$s)?'selected':''; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="min-width:160px;">
                <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.3rem;color:var(--text-secondary);">Doctor</label>
                <select name="doctor" class="adm-search-input">
                    <option value="">All Doctors</option>
                    <?php while ($dd = mysqli_fetch_assoc($doctors_dd)): ?>
                    <option value="<?php echo $dd['id']; ?>" <?php echo ($filter_doctor==(int)$dd['id'])?'selected':''; ?>>Dr. <?php echo htmlspecialchars($dd['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="display:flex;gap:.5rem;">
                <button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="appointment.php" class="adm-btn adm-btn-back"><i class="fas fa-times"></i> Clear</a>
            </div>
        </form>

        <!-- Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-list"></i> Appointments Registry</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Apt ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Urgency</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT a.id, a.appointment_id, a.appointment_date, a.appointment_time,
                                       a.service_type, a.symptoms, a.urgency_level, a.status, a.created_at,
                                       u_p.name AS patient_name, u_p.phone AS patient_phone,
                                       p.patient_id AS patient_code,
                                       u_d.name AS doctor_name,
                                       d.specialization
                                FROM appointments a
                                LEFT JOIN patients p ON a.patient_id = p.id
                                LEFT JOIN users u_p ON p.user_id = u_p.id
                                JOIN doctors d ON a.doctor_id = d.id
                                JOIN users u_d ON d.user_id = u_d.id
                                $where
                                ORDER BY a.appointment_date DESC, a.appointment_time DESC
                                LIMIT 100";
                        $q = mysqli_query($conn, $sql);
                        if (!$q || mysqli_num_rows($q) === 0) {
                            echo "<tr><td colspan='10' style='text-align:center;padding:3rem;color:var(--text-muted);'><i class='fas fa-calendar-times' style='font-size:2rem;display:block;margin-bottom:.75rem;opacity:.3;'></i>No appointments found.</td></tr>";
                        } else {
                            $n = 1;
                            while ($a = mysqli_fetch_assoc($q)):
                                $status = $a['status'];
                                $sc = ($status === 'Confirmed') ? 'success' : (($status === 'Completed') ? 'info' : (($status === 'Cancelled') ? 'danger' : (($status === 'No-Show') ? 'dark' : 'warning')));
                                $urg = $a['urgency_level'] ?? 'Routine';
                                $urg_sc = ($urg === 'Emergency') ? 'danger' : (($urg === 'Urgent') ? 'warning' : 'success');
                                $is_today = ($a['appointment_date'] === $today);
                        ?>
                        <tr <?php echo $is_today ? "style='background:rgba(47,128,237,.04);'" : ''; ?>>
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary" style="font-size:.72rem;"><?php echo htmlspecialchars($a['appointment_id']); ?></span></td>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($a['patient_name'] ?? 'Walk-in'); ?></div>
                                <?php if ($a['patient_code']): ?><div style="font-size:.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($a['patient_code']); ?></div><?php endif; ?>
                            </td>
                            <td>Dr. <?php echo htmlspecialchars($a['doctor_name']); ?><br><span style="font-size:.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($a['specialization']); ?></span></td>
                            <td><?php echo htmlspecialchars($a['service_type']); ?></td>
                            <td>
                                <?php
                                $apt_day = date('d M Y', strtotime($a['appointment_date']));
                                echo $apt_day;
                                if ($is_today) echo ' <span class="adm-badge adm-badge-primary" style="font-size:.7rem;">Today</span>';
                                ?>
                            </td>
                            <td><?php echo date('g:i A', strtotime($a['appointment_time'])); ?></td>
                            <td><span class="adm-badge adm-badge-<?php echo $urg_sc; ?>"><?php echo $urg; ?></span></td>
                            <td>
                                <span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo $status; ?></span>
                            </td>
                            <td>
                                <!-- Inline status update form -->
                                <form method="post" style="display:inline-flex;gap:.3rem;align-items:center;" class="status-form">
                                    <input type="hidden" name="apt_id" value="<?php echo $a['id']; ?>">
                                    <select name="new_status" class="adm-search-input" style="padding:.3rem .5rem;font-size:.78rem;width:100px;">
                                        <?php foreach (['Pending','Confirmed','Completed','Cancelled','No-Show'] as $opt): ?>
                                        <option value="<?php echo $opt; ?>" <?php echo ($status===$opt)?'selected':''; ?>><?php echo $opt; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="toggle_status" class="adm-btn adm-btn-primary adm-btn-sm" title="Update Status"><i class="fas fa-check"></i></button>
                                </form>
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