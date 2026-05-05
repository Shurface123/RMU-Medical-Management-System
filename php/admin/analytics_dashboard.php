<?php
/**
 * RMU Medical Sickbay — Analytics Dashboard v2.0
 * Comprehensive, Real-time Clinical & Operational Analytics
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db_conn.php';

// Authentication Guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$page_title = "Analytics Dashboard";
$active_page = "analytics";
include '../includes/_sidebar.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">

<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #2F80ED;
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #000 40%));
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Stat Mini Cards ── */
.stat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2.5rem; }
.stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);
  padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);cursor:pointer;box-shadow:var(--shadow-sm); }
.stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); border-color:var(--primary); }
.stat-mini-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:1.8rem;background:var(--surface-2);color:var(--text-secondary); }
.stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--primary); }
.stat-mini-val.success { color:var(--success); }
.stat-mini-val.warning { color:var(--warning); }
.stat-mini-val.danger { color:var(--danger); }
.stat-mini-val.info { color:var(--info); }
.stat-mini-lbl { font-size:1.15rem;font-weight:600;color:var(--text-secondary);margin-top:.6rem; text-transform:uppercase; letter-spacing:0.05em; }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; height:100%; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Control Bar ── */
.control-bar { display:flex; gap:1.5rem; align-items:flex-end; background:rgba(255,255,255,0.1); padding:1rem 1.5rem; border-radius:var(--radius-sm); backdrop-filter:blur(5px); border:1px solid rgba(255,255,255,0.2); }
.control-group label { display:block; font-size:0.9rem; color:rgba(255,255,255,0.8); margin-bottom:0.4rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }
.control-group input { padding:0.6rem 1rem; border:1px solid rgba(255,255,255,0.3); border-radius:var(--radius-sm); background:rgba(255,255,255,0.9); color:var(--text-primary); outline:none; font-family:'Poppins',sans-serif; }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; }
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }

/* ── Table Container ── */
.table-container { width:100%; overflow-x:auto; }

/* Section Title */
.section-title { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); margin: 3rem 0 1.5rem 0; display:flex; align-items:center; gap:1rem; }
.section-title i { color: var(--primary); }

@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-chart-line"></i> Analytics Dashboard</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content" style="animation:fadePop .35s ease;">
        
        <div class="staff-hero">
            <i class="fas fa-chart-pie hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-chart-line"></i></div>
            <div class="staff-hero-info">
                <h2>Clinical Intelligence Center</h2>
                <p>Real-time oversight of hospital operations and performance metrics.</p>
            </div>
            <div style="margin-left:auto; z-index:2;">
                <div class="control-bar">
                    <div class="control-group">
                        <label>Start Date</label>
                        <input type="date" id="startDate" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                    </div>
                    <div class="control-group">
                        <label>End Date</label>
                        <input type="date" id="endDate" value="<?= date('Y-m-d') ?>">
                    </div>
                    <button id="applyFilters" class="btn" style="background:#fff; color:var(--primary);"><i class="fas fa-filter"></i> Apply</button>
                    <button class="btn" onclick="window.print()" style="background:transparent; color:#fff; border:1px solid rgba(255,255,255,0.4);"><i class="fas fa-print"></i></button>
                </div>
            </div>
        </div>

        <!-- Executive Summary -->
        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--info); background:var(--info-light);"><i class="fas fa-user-injured"></i></div>
                <div class="stat-mini-val info" id="patientsToday">0</div>
                <div class="stat-mini-lbl">Patients Today</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--primary); background:var(--primary-light);"><i class="fas fa-procedures"></i></div>
                <div class="stat-mini-val" id="activeAdmissions">0</div>
                <div class="stat-mini-lbl">Active Admissions</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success); background:var(--success-light);"><i class="fas fa-user-md"></i></div>
                <div class="stat-mini-val success" id="staffOnDuty">0</div>
                <div class="stat-mini-lbl">Staff On Duty</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--danger); background:var(--danger-light);"><i class="fas fa-ambulance"></i></div>
                <div class="stat-mini-val danger" id="pendingEmergencies">0</div>
                <div class="stat-mini-lbl">Pending Emergencies</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success); background:var(--success-light);"><i class="fas fa-pills"></i></div>
                <div class="stat-mini-val success" id="medsToday">0</div>
                <div class="stat-mini-lbl">Meds Administered</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--info); background:var(--info-light);"><i class="fas fa-flask"></i></div>
                <div class="stat-mini-val info" id="labsToday">0</div>
                <div class="stat-mini-lbl">Labs Completed</div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-procedures"></i> Patient Analytics</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2.5rem;">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-chart-line" style="color:var(--primary);"></i> Admission Trends</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="admissionChart"></canvas></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-bed" style="color:var(--info);"></i> Ward Occupancy</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="wardChart"></canvas></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-clock" style="color:var(--warning);"></i> Length of Stay</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="losChart"></canvas></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-users" style="color:var(--success);"></i> Age Distribution</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="ageChart"></canvas></div></div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-heartbeat"></i> Clinical Operations</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2.5rem;">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-heartbeat" style="color:var(--danger);"></i> Vital Signs Flagging</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="vitalsChart"></canvas></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-vial" style="color:var(--info);"></i> Lab Turnaround</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="tatChart"></canvas></div></div>
            </div>
            <div class="card" style="grid-column: 1 / -1;">
                <div class="card-header"><h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Medication Compliance</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="medComplianceChart"></canvas></div></div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-user-md"></i> Staff Efficiency</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2.5rem;">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-tasks" style="color:var(--primary);"></i> Task Completion</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="staffTaskChart"></canvas></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-chart-bar" style="color:var(--warning);"></i> Workload Volume</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="volumeChart"></canvas></div></div>
            </div>
            <div class="card" style="grid-column: 1 / -1;">
                <div class="card-header"><h3><i class="fas fa-sign-in-alt" style="color:var(--info);"></i> Recent Staff Logins</h3></div>
                <div class="card-body" style="padding: 0;">
                    <div id="loginActivityTable" class="table-container"></div>
                </div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-pills"></i> Pharmacy & Inventory</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2.5rem;">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-prescription-bottle-alt" style="color:var(--info);"></i> Top Prescriptions</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="pharmacyChart"></canvas></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-clipboard-check" style="color:var(--success);"></i> Fulfillment Rate</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="fulfillmentChart"></canvas></div></div>
            </div>
            <div class="card" style="grid-column: 1 / -1;">
                <div class="card-header"><h3><i class="fas fa-exclamation-triangle" style="color:var(--danger);"></i> Critical Stock Alerts</h3></div>
                <div class="card-body" style="padding: 0;">
                    <div id="stockAlertsTable" class="table-container"></div>
                </div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-server"></i> Financial & System Health</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2.5rem; margin-bottom:4rem;">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-money-bill-wave" style="color:var(--success);"></i> Revenue Growth</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="revenueChart"></canvas></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-building" style="color:var(--primary);"></i> Dept Revenue</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="deptRevenueChart"></canvas></div></div>
            </div>
            <div class="card" style="grid-column: 1 / -1;">
                <div class="card-header"><h3><i class="fas fa-credit-card" style="color:var(--warning);"></i> Payment Methods</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="paymentMethodChart"></canvas></div></div>
            </div>
            <div class="card" style="grid-column: 1 / -1;">
                <div class="card-header"><h3><i class="fas fa-user-clock" style="color:var(--info);"></i> Daily Active Users</h3></div>
                <div class="card-body"><div style="height:350px;"><canvas id="usageChart"></canvas></div></div>
            </div>
        </div>

    </div>
</main>

<script src="analytics_dashboard.js"></script>

<script>
const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>