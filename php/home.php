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

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tests");
$stats['tests'] = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM beds");
$stats['beds'] = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM ambulances");
$stats['ambulance'] = mysqli_fetch_assoc($result)['total'] ?? 0;

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
            <!-- Low-Stock Notification -->
            <button class="adm-notif-btn" title="<?php echo count($low_stock_meds); ?> low-stock medicines">
                <i class="fas fa-bell"></i>
                <?php if (count($low_stock_meds) > 0): ?>
                    <span class="adm-notif-badge"><?php echo count($low_stock_meds); ?></span>
                <?php endif; ?>
            </button>
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
            <a href="/RMU-Medical-Management-System/php/test/test.php" class="adm-stat-card">
                <div class="adm-stat-icon tests"><i class="fas fa-flask"></i></div>
                <div class="adm-stat-label">Lab Tests</div>
                <div class="adm-stat-value"><?php echo $stats['tests']; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-arrow-right"></i> View Tests</div>
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

        <!-- ── Two-Column Row: Recent Patients + Report Generator ── -->
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

            <!-- Quick Report Export Hub -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-file-export"></i> Report Export Hub</h3>
                </div>
                <div class="adm-card-body">
                    <p style="color:var(--text-secondary);margin-bottom:2rem;font-size:1.35rem;">
                        Export data snapshots for records and compliance.
                    </p>
                    <div style="display:flex;flex-direction:column;gap:1.2rem;">
                        <a href="/RMU-Medical-Management-System/php/api/export.php?type=patients&fmt=csv"
                           class="adm-btn adm-btn-success" style="justify-content:flex-start;">
                            <i class="fas fa-file-csv"></i> Export Patients (CSV)
                        </a>
                        <a href="/RMU-Medical-Management-System/php/api/export.php?type=doctors&fmt=csv"
                           class="adm-btn adm-btn-primary" style="justify-content:flex-start;">
                            <i class="fas fa-file-csv"></i> Export Doctors (CSV)
                        </a>
                        <a href="/RMU-Medical-Management-System/php/api/export.php?type=medicines&fmt=csv"
                           class="adm-btn adm-btn-warning" style="justify-content:flex-start;">
                            <i class="fas fa-pills"></i> Export Medicine Inventory (CSV)
                        </a>
                        <a href="/RMU-Medical-Management-System/php/api/export.php?type=ambulances&fmt=csv"
                           class="adm-btn adm-btn-danger" style="justify-content:flex-start;">
                            <i class="fas fa-ambulance"></i> Export Ambulance Fleet (CSV)
                        </a>
                    </div>
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
                    <a href="/RMU-Medical-Management-System/php/test/add-test.php" class="adm-action-tile">
                        <i class="fas fa-flask"></i>
                        <span>Add Lab Test</span>
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
</script>

</body>
</html>