<?php
// ============================================
// PHP CODE MUST COME FIRST - BEFORE ANY HTML!
// ============================================

// AUTHENTICATION CHECK - Admin Only
require_once 'includes/auth_middleware.php';
enforceSingleDashboard('admin');

// Database
include 'db_conn.php';

// Date & Time
date_default_timezone_set('Africa/Accra');
$currentDate = date('l, F j, Y');
$currentTime = date('g:i A');

// ── Statistics ──────────────────────────────
$stats = [];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM doctors");
$stats['doctors'] = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM staff");
$stats['staff'] = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM patients");
$stats['patients'] = mysqli_fetch_assoc($result)['total'] ?? 0;



$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM beds");
$stats['beds'] = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM ambulances");
$stats['ambulance'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// ── Today's Appointments ──────────────────────────
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE appointment_date='" . date('Y-m-d') . "'");
$stats['appointments_today'] = mysqli_fetch_assoc($result)['total'] ?? 0;

$today = date('Y-m-d');

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM medicines");
$stats['medicine'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// ── Medicine Low-Stock Alerts ────────────────
$low_stock_meds = [];
$q_low = mysqli_query($conn, "SELECT medicine_name, stock_quantity, reorder_level FROM medicines WHERE stock_quantity <= reorder_level ORDER BY stock_quantity ASC LIMIT 5");
if ($q_low) {
    while ($row = mysqli_fetch_assoc($q_low)) {
        $low_stock_meds[] = $row;
    }
}

// ── Recent Patients  ─────────────────────────
$recent_patients = [];
$q_patients = mysqli_query($conn, "SELECT full_name, patient_type, admit_date, gender FROM patients ORDER BY id DESC LIMIT 6");
if ($q_patients) {
    while ($row = mysqli_fetch_assoc($q_patients)) {
        $recent_patients[] = $row;
    }
}

// ── Today's Appointments ─────────────────────────────────────
$today_apts = [];
$q_tapts = mysqli_query($conn,
    "SELECT a.appointment_id, a.appointment_time, a.service_type, a.status, a.urgency_level,
            u_p.name AS patient_name, u_d.name AS doctor_name
     FROM appointments a
     LEFT JOIN patients p ON a.patient_id = p.id
     LEFT JOIN users u_p ON p.user_id = u_p.id
     JOIN doctors d ON a.doctor_id = d.id
     JOIN users u_d ON d.user_id = u_d.id
     WHERE a.appointment_date = '" . date('Y-m-d') . "'
     ORDER BY a.appointment_time ASC LIMIT 5"
);
if ($q_tapts) while ($row = mysqli_fetch_assoc($q_tapts)) $today_apts[] = $row;

// ── Chart Data: Monthly Patient Admissions ───
$monthly_labels = [];
$monthly_patients = [];
for ($i = 5; $i >= 0; $i--) {
    $month_label = date('M', strtotime("-$i months"));
    $month_num   = date('Y-m', strtotime("-$i months"));
    $monthly_labels[] = $month_label;
    $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM patients WHERE DATE_FORMAT(admit_date,'%Y-%m') = '$month_num'");
    $monthly_patients[] = $q ? (mysqli_fetch_assoc($q)['cnt'] ?? 0) : 0;
}
// ── Logout Analytics (Phase 3) ─────────────────
$logout_stats = [
    'today' => 0,
    'forced_week' => 0,
    'top_role' => 'N/A',
    'peak_hour' => 'N/A'
];
$q_today = mysqli_query($conn, "SELECT COUNT(*) as c FROM logout_logs WHERE DATE(created_at) = CURDATE()");
if ($q_today) $logout_stats['today'] = mysqli_fetch_assoc($q_today)['c'] ?? 0;

$q_forced = mysqli_query($conn, "SELECT COUNT(*) as c FROM logout_logs WHERE logout_type='forced' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
if ($q_forced) $logout_stats['forced_week'] = mysqli_fetch_assoc($q_forced)['c'] ?? 0;

$q_role = mysqli_query($conn, "SELECT role, COUNT(*) as c FROM logout_logs GROUP BY role ORDER BY c DESC LIMIT 1");
if ($q_role && $r = mysqli_fetch_assoc($q_role)) $logout_stats['top_role'] = ucfirst($r['role']);

$q_hour = mysqli_query($conn, "SELECT HOUR(created_at) as h, COUNT(*) as c FROM logout_logs WHERE DATE(created_at) = CURDATE() GROUP BY h ORDER BY c DESC LIMIT 1");
if ($q_hour && $h = mysqli_fetch_assoc($q_hour)) $logout_stats['peak_hour'] = str_pad($h['h'], 2, '0', STR_PAD_LEFT) . ':00';

$active_page = 'dashboard';
$page_title  = 'Admin Dashboard';
include 'includes/_sidebar.php';
?>

<!-- ══════════════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════════════ -->
<main class="adm-main">

    <!-- Top Bar -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <span class="adm-page-title">Admin Dashboard</span>
        </div>
        <div class="adm-topbar-right">
            <div class="adm-topbar-datetime">
                <i class="far fa-calendar-alt"></i>
                <span id="liveDate"><?php echo $currentDate; ?></span>
                &nbsp;|&nbsp;
                <i class="far fa-clock"></i>
                <span id="liveTime"><?php echo $currentTime; ?></span>
            </div>
            <?php
            // Live notification count: prefer notifications table, fallback to low-stock
            $notif_count = 0;
            $notif_items = [];
            $notif_tbl = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
            if ($notif_tbl && mysqli_num_rows($notif_tbl) > 0) {
                $notif_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM notifications WHERE is_read=0"))[0] ?? 0;
                $nq = mysqli_query($conn, "SELECT title, message, created_at FROM notifications WHERE is_read=0 ORDER BY created_at DESC LIMIT 5");
                if ($nq) while ($nr = mysqli_fetch_assoc($nq)) $notif_items[] = $nr;
            } else {
                $notif_count = count($low_stock_meds);
                foreach ($low_stock_meds as $med) {
                    $notif_items[] = ['title' => 'Low Stock', 'message' => htmlspecialchars($med['medicine_name']).' has only '.$med['stock_quantity'].' left.', 'created_at' => null];
                }
            }
            ?>
            <div style="position:relative;">
                <button class="adm-notif-btn" id="notifBtn" title="Notifications" style="position:relative;">
                    <i class="fas fa-bell"></i>
                    <?php if ($notif_count > 0): ?>
                    <span class="adm-notif-badge" id="notifBadge"><?php echo $notif_count > 9 ? '9+' : $notif_count; ?></span>
                    <?php endif; ?>
                </button>
                <!-- Dropdown -->
                <div id="notifDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:300px;background:var(--bg-card);border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.15);border:1px solid var(--border);z-index:200;overflow:hidden;">
                    <div style="padding:.85rem 1.2rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                        <strong style="font-size:.9rem;"><i class="fas fa-bell" style="color:var(--primary);margin-right:.4rem;"></i>Notifications</strong>
                        <?php if ($notif_count > 0): ?><span style="font-size:.75rem;color:var(--text-muted);"><?php echo $notif_count; ?> unread</span><?php endif; ?>
                    </div>
                    <div style="max-height:280px;overflow-y:auto;">
                    <?php if (empty($notif_items)): ?>
                        <p style="padding:2rem;text-align:center;color:var(--text-muted);font-size:.85rem;"><i class="fas fa-check-circle" style="display:block;font-size:1.5rem;margin-bottom:.5rem;color:var(--success);"></i>All caught up!</p>
                    <?php else: foreach ($notif_items as $ni): ?>
                        <div style="padding:.75rem 1.2rem;border-bottom:1px solid var(--border);">
                            <div style="font-weight:600;font-size:.82rem;margin-bottom:.2rem;"><?php echo htmlspecialchars($ni['title']); ?></div>
                            <div style="font-size:.78rem;color:var(--text-muted);"><?php echo htmlspecialchars($ni['message']); ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                    </div>
                    <div style="padding:.75rem 1.2rem;text-align:center;border-top:1px solid var(--border);">
                        <a href="/RMU-Medical-Management-System/php/medicine/medicine.php" style="font-size:.82rem;color:var(--primary);font-weight:600;">View All →</a>
                    </div>
                </div>
            </div>
            <!-- Theme Toggle -->
            <button class="adm-theme-toggle" id="themeToggle" title="Toggle Dark/Light Mode">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <!-- Content -->
    <div class="adm-content">

        <!-- Welcome Banner -->
        <div class="adm-welcome">
            <h2><i class="fas fa-hand-sparkles" style="margin-right:.8rem;"></i>Welcome Back, Administrator!</h2>
            <p>Here's a live overview of RMU Medical Sickbay — <?php echo $currentDate; ?></p>
        </div>

        <!-- ── Low-Stock Medicine Alert ── -->
        <?php if (!empty($low_stock_meds)): ?>
        <div class="adm-alert adm-alert-warning" style="margin-bottom:2rem;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Low-Stock Alert:</strong>
                <?php
                $names = array_column($low_stock_meds, 'medicine_name');
                echo implode(', ', array_map('htmlspecialchars', array_slice($names, 0, 3)));
                if (count($names) > 3) echo ' and ' . (count($names) - 3) . ' more';
                echo ' are running low.';
                ?>
                <a href="/RMU-Medical-Management-System/php/medicine/medicine.php" style="color:inherit;font-weight:700;text-decoration:underline;margin-left:.5rem;">View Inventory →</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Statistics Grid ── -->
        <div class="adm-stats-grid">
            <a href="/RMU-Medical-Management-System/php/Doctor/doctor.php" class="adm-stat-card">
                <div class="adm-stat-icon doctors"><i class="fas fa-user-md"></i></div>
                <div class="adm-stat-label">Total Doctors</div>
                <div class="adm-stat-value"><?php echo $stats['doctors']; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-arrow-right"></i> Manage Doctors</div>
            </a>
            <a href="/RMU-Medical-Management-System/php/staff/staff.php" class="adm-stat-card">
                <div class="adm-stat-icon staff"><i class="fas fa-user-nurse"></i></div>
                <div class="adm-stat-label">Total Staff</div>
                <div class="adm-stat-value"><?php echo $stats['staff']; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-arrow-right"></i> Manage Staff</div>
            </a>
            <a href="/RMU-Medical-Management-System/php/patient/patient.php" class="adm-stat-card">
                <div class="adm-stat-icon patients"><i class="fas fa-user-injured"></i></div>
                <div class="adm-stat-label">Total Patients</div>
                <div class="adm-stat-value"><?php echo $stats['patients']; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-arrow-right"></i> Manage Patients</div>
            </a>

            <a href="/RMU-Medical-Management-System/php/bed/bed.php" class="adm-stat-card">
                <div class="adm-stat-icon beds"><i class="fas fa-procedures"></i></div>
                <div class="adm-stat-label">Beds</div>
                <div class="adm-stat-value"><?php echo $stats['beds']; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-arrow-right"></i> Bed Status</div>
            </a>
            <a href="/RMU-Medical-Management-System/php/Ambulence/ambulence.php" class="adm-stat-card">
                <div class="adm-stat-icon ambulance"><i class="fas fa-ambulance"></i></div>
                <div class="adm-stat-label">Ambulances</div>
                <div class="adm-stat-value"><?php echo $stats['ambulance']; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-arrow-right"></i> Fleet Status</div>
            </a>
            <a href="/RMU-Medical-Management-System/php/medicine/medicine.php" class="adm-stat-card">
                <div class="adm-stat-icon medicine"><i class="fas fa-pills"></i></div>
                <div class="adm-stat-label">Medicines</div>
                <div class="adm-stat-value"><?php echo $stats['medicine']; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-arrow-right"></i> Inventory</div>
            </a>
            <a href="/RMU-Medical-Management-System/php/Appointment/appointment.php?date=<?php echo date('Y-m-d'); ?>" class="adm-stat-card">
                <div class="adm-stat-icon" style="background:rgba(139,92,246,.12);"><i class="fas fa-calendar-check" style="color:#8b5cf6;"></i></div>
                <div class="adm-stat-label">Today's Appointments</div>
                <div class="adm-stat-value"><?php echo $stats['appointments_today']; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-arrow-right"></i> View Today</div>
            </a>
        </div>

        <!-- ── Analytics Charts ── -->
        <div class="adm-charts-grid">
            <!-- Patient Admissions Trend -->
            <div class="adm-chart-card">
                <h3><i class="fas fa-chart-line"></i> Patient Admissions (Last 6 Months)</h3>
                <canvas id="chartAdmissions" height="200"></canvas>
            </div>
            <!-- Medicine Inventory Overview -->
            <div class="adm-chart-card">
                <h3><i class="fas fa-chart-doughnut"></i> Medicine Stock Overview</h3>
                <canvas id="chartMedicine" height="200"></canvas>
            </div>
        </div>

        <!-- ── Security & Logout Analytics Panel (Phase 3) ── -->
        <h3 style="margin-bottom: 1rem; margin-top: 2rem; color: var(--text-dark);"><i class="fas fa-shield-alt" style="color: #2F80ED;"></i> Security &amp; Logout Analytics</h3>
        <div class="adm-stats-grid" style="margin-bottom: 2.8rem;">
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background: rgba(47, 128, 237, 0.1); color: #2F80ED;"><i class="fas fa-sign-out-alt"></i></div>
                <div class="adm-stat-label">Total Logouts Today</div>
                <div class="adm-stat-value"><?php echo $logout_stats['today']; ?></div>
                <div class="adm-stat-footer" style="color:var(--text-muted);"><i class="fas fa-chart-pie"></i> Validated manual &amp; timeout</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;"><i class="fas fa-ban"></i></div>
                <div class="adm-stat-label">Forced Logouts (7 Days)</div>
                <div class="adm-stat-value"><?php echo $logout_stats['forced_week']; ?></div>
                <div class="adm-stat-footer" style="color:#e74c3c;"><i class="fas fa-exclamation-circle"></i> Security actions</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background: rgba(39, 174, 96, 0.1); color: #27ae60;"><i class="fas fa-users"></i></div>
                <div class="adm-stat-label">Most Active Role</div>
                <div class="adm-stat-value" style="font-size: 1.6rem; margin-top:0.3rem;"><?php echo $logout_stats['top_role']; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-user-tag"></i> Based on volume</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background: rgba(230, 126, 34, 0.1); color: #E67E22;"><i class="fas fa-clock"></i></div>
                <div class="adm-stat-label">Peak Logout Hour</div>
                <div class="adm-stat-value" style="font-size: 1.6rem; margin-top:0.3rem;"><?php echo $logout_stats['peak_hour']; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-history"></i> Highest volume hour</div>
            </div>
        </div>

        <!-- ── Two-Column Row: Recent Patients + Today's Appointments ── -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2.8rem;" class="adm-two-col">

            <!-- Recent Patients / Triage Queue -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-users"></i> Recent Patient Queue</h3>
                    <a href="/RMU-Medical-Management-System/php/patient/patient.php" class="adm-btn adm-btn-ghost adm-btn-sm">
                        <i class="fas fa-eye"></i> View All
                    </a>
                </div>
                <div class="adm-card-body" style="padding:0;">
                    <?php if (empty($recent_patients)): ?>
                        <p style="padding:2.5rem;color:var(--text-muted);text-align:center;">No patients found.</p>
                    <?php else: ?>
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Type / Triage</th>
                                <th>Admitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_patients as $pat): 
                                $type = strtolower($pat['patient_type'] ?? 'routine');
                                if (str_contains($type, 'emerg')) {
                                    $triage_class = 'adm-triage adm-triage-emergency';
                                    $triage_label = '🔴 Emergency';
                                } elseif (str_contains($type, 'urgent') || str_contains($type, 'semi')) {
                                    $triage_class = 'adm-triage adm-triage-urgent';
                                    $triage_label = '🟡 Urgent';
                                } else {
                                    $triage_class = 'adm-triage adm-triage-routine';
                                    $triage_label = '🟢 Routine';
                                }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($pat['full_name']); ?></strong>
                                    <br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($pat['gender'] ?? ''); ?></small>
                                </td>
                                <td><span class="<?php echo $triage_class; ?>"><?php echo $triage_label; ?></span></td>
                                <td style="font-size:1.25rem;color:var(--text-secondary);"><?php echo htmlspecialchars($pat['admit_date'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Today's Appointments widget -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-calendar-day"></i> Today's Appointments</h3>
                    <a href="/RMU-Medical-Management-System/php/Appointment/appointment.php?date=<?php echo date('Y-m-d'); ?>" class="adm-btn adm-btn-ghost adm-btn-sm"><i class="fas fa-eye"></i> View All</a>
                </div>
                <div class="adm-card-body" style="padding:0;">
                    <?php if (empty($today_apts)): ?>
                        <p style="padding:2.5rem;color:var(--text-muted);text-align:center;"><i class="fas fa-calendar-times" style="display:block;font-size:2rem;opacity:.3;margin-bottom:.75rem;"></i>No appointments scheduled for today.</p>
                    <?php else: ?>
                    <table class="adm-table">
                        <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($today_apts as $ta):
                            $sc = $ta['status']==='Confirmed'?'success':($ta['status']==='Completed'?'info':($ta['status']==='Cancelled'?'danger':'warning'));
                        ?>
                        <tr>
                            <td><strong><?php echo date('g:i A', strtotime($ta['appointment_time'])); ?></strong></td>
                            <td><?php echo htmlspecialchars($ta['patient_name'] ?? 'Walk-in'); ?></td>
                            <td style="font-size:.8rem;">Dr. <?php echo htmlspecialchars($ta['doctor_name']); ?></td>
                            <td><span class="adm-badge adm-badge-<?php echo $sc;?>"><?php echo $ta['status']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Quick Actions ── -->
        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div class="adm-card-body">
                <div class="adm-quick-actions">
                    <a href="/RMU-Medical-Management-System/php/patient/add-patient.php" class="adm-action-tile">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Patient</span>
                    </a>
                    <a href="/RMU-Medical-Management-System/php/Doctor/add-doctor.php" class="adm-action-tile">
                        <i class="fas fa-user-md"></i>
                        <span>Add Doctor</span>
                    </a>
                    <a href="/RMU-Medical-Management-System/php/staff/add-staff.php" class="adm-action-tile">
                        <i class="fas fa-user-nurse"></i>
                        <span>Add Staff</span>
                    </a>
                    <a href="/RMU-Medical-Management-System/php/medicine/add-medicine.php" class="adm-action-tile">
                        <i class="fas fa-pills"></i>
                        <span>Add Medicine</span>
                    </a>
                    <a href="/RMU-Medical-Management-System/php/booking.php" class="adm-action-tile">
                        <i class="fas fa-calendar-check"></i>
                        <span>Book Appointment</span>
                    </a>

                    <a href="/RMU-Medical-Management-System/php/Ambulence/add-ambulence.php" class="adm-action-tile">
                        <i class="fas fa-ambulance"></i>
                        <span>Add Ambulance</span>
                    </a>
                    <a href="/RMU-Medical-Management-System/php/admin/analytics_dashboard.php" class="adm-action-tile">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics</span>
                    </a>
                </div>
            </div>
        </div>

    </div><!-- /adm-content -->
</main><!-- /adm-main -->

<!-- ── Chart.js CDN ── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// ─── Live Clock ────────────────────────────────────
function updateClock() {
    const now = new Date();
    document.getElementById('liveTime').textContent = now.toLocaleTimeString('en-US', {hour:'numeric',minute:'2-digit',hour12:true});
}
setInterval(updateClock, 1000);

// ─── Mobile Sidebar Toggle ─────────────────────────
const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
const menuBtn  = document.getElementById('menuToggle');
menuBtn?.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
});
overlay?.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
});

// ─── Theme Toggle ──────────────────────────────────
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = document.getElementById('themeIcon');
const html        = document.documentElement;

function applyTheme(t) {
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    if (t === 'dark') {
        themeIcon.classList.replace('fa-moon', 'fa-sun');
    } else {
        themeIcon.classList.replace('fa-sun', 'fa-moon');
    }
}

// Init from saved preference
applyTheme(localStorage.getItem('rmu_theme') || 'light');

themeToggle?.addEventListener('click', () => {
    applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
});

// ─── Chart: Patient Admissions ────────────────────
const labels   = <?php echo json_encode($monthly_labels); ?>;
const patients = <?php echo json_encode($monthly_patients); ?>;

const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor = () => isDark() ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
const textColor = () => isDark() ? '#9AAECB' : '#5A6A85';

const ctxAdm = document.getElementById('chartAdmissions')?.getContext('2d');
if (ctxAdm) {
    const admChart = new Chart(ctxAdm, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Patients Admitted',
                data: patients,
                backgroundColor: 'rgba(47,128,237,0.18)',
                borderColor: '#2F80ED',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, color: textColor() }, grid: { color: gridColor() } },
                x: { ticks: { color: textColor() }, grid: { display: false } }
            }
        }
    });
}

// ─── Chart: Medicine Overview ─────────────────────
<?php
$total_med   = $stats['medicine'];
$low_med_cnt = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines WHERE stock_quantity <= reorder_level AND stock_quantity > 0"))[0] ?? 0;
$out_med_cnt = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines WHERE stock_quantity = 0"))[0] ?? 0;
$ok_med_cnt  = max(0, $total_med - $low_med_cnt - $out_med_cnt);
?>
const ctxMed = document.getElementById('chartMedicine')?.getContext('2d');
if (ctxMed) {
    new Chart(ctxMed, {
        type: 'doughnut',
        data: {
            labels: ['In Stock', 'Low Stock', 'Out of Stock'],
            datasets: [{
                data: [<?php echo $ok_med_cnt; ?>, <?php echo $low_med_cnt; ?>, <?php echo $out_med_cnt; ?>],
                backgroundColor: ['rgba(39,174,96,0.8)', 'rgba(243,156,18,0.8)', 'rgba(231,76,60,0.8)'],
                borderColor: ['#27AE60', '#F39C12', '#E74C3C'],
                borderWidth: 2,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: { position: 'bottom', labels: { color: textColor(), padding: 16, font: { size: 13 } } }
            }
        }
    });
}

// Active nav highlights
document.querySelectorAll('.adm-nav-item').forEach(link => {
    if (link.getAttribute('href') === window.location.pathname) link.classList.add('active');
});

// Notification bell toggle
const notifBtn = document.getElementById('notifBtn');
const notifDd  = document.getElementById('notifDropdown');
if (notifBtn && notifDd) {
    notifBtn.addEventListener('click', e => { e.stopPropagation(); notifDd.style.display = notifDd.style.display === 'none' ? 'block' : 'none'; });
    document.addEventListener('click', () => { notifDd.style.display = 'none'; });
    notifDd.addEventListener('click', e => e.stopPropagation());
}
</script>

</body>
</html>