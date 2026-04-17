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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="adm-main">
    <!-- Topbar -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title">
                <i class="fas fa-chart-line" style="color:var(--primary);margin-right:.8rem;"></i>
                Clinical Intelligence Center
            </span>
        </div>
        
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" alt="Admin">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <!-- Module Header -->
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Clinical Intelligence Center</h1>
                <p>Real-time oversight of hospital operations and performance metrics</p>
            </div>
            
            <div style="display:flex; gap:1rem; align-items:center;">
                <!-- Control Bar -->
                <div style="display:flex; gap:1rem; background:var(--surface); padding:1rem; border-radius:var(--radius-md); box-shadow:var(--shadow-sm); border:1px solid var(--border);">
                    <div>
                        <label style="font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:0.25rem;">START DATE</label>
                        <input type="date" id="startDate" style="border:1px solid var(--border); padding:0.5rem; border-radius:var(--radius-sm);" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                    </div>
                    <div>
                        <label style="font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:0.25rem;">END DATE</label>
                        <input type="date" id="endDate" style="border:1px solid var(--border); padding:0.5rem; border-radius:var(--radius-sm);" value="<?= date('Y-m-d') ?>">
                    </div>
                    <button id="applyFilters" class="btn btn-primary" style="margin-top:1.5rem;"><span class="btn-text">
                        <i class="fas fa-filter"></i>
                    </span></button>
                    <button class="btn-icon btn btn-outline" onclick="window.print()" style="margin-top:1.5rem; background:transparent; border:1px solid var(--border);"><span class="btn-text">
                        <i class="fas fa-print"></i>
                    </span></button>
                </div>
            </div>
        </div>

        <!-- ════════════════════ EXECUTIVE SUMMARY ════════════════════ -->
        
        <div class="adm-stats-grid" style="margin-top: 1rem;">
            <div class="adm-stat-card">
                <div class="adm-stat-icon patients"><i class="fas fa-user-injured"></i></div>
                <div class="adm-stat-label">Patients Today</div>
                <div class="adm-stat-value" id="patientsToday">0</div>
                <div class="adm-stat-footer" style="color:var(--success);"><i class="fas fa-caret-up"></i> Live Tracking</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon beds"><i class="fas fa-procedures"></i></div>
                <div class="adm-stat-label">Active Admissions</div>
                <div class="adm-stat-value" id="activeAdmissions">0</div>
                <div class="adm-stat-footer"><i class="fas fa-check-circle"></i> Currently Warded</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon staff"><i class="fas fa-user-md"></i></div>
                <div class="adm-stat-label">Staff On Duty</div>
                <div class="adm-stat-value" id="staffOnDuty">0</div>
                <div class="adm-stat-footer" style="color:var(--success);"><i class="fas fa-check-circle"></i> Verified Logins</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon ambulance"><i class="fas fa-ambulance"></i></div>
                <div class="adm-stat-label">Pending Emergencies</div>
                <div class="adm-stat-value" id="pendingEmergencies" style="color:var(--danger);">0</div>
                <div class="adm-stat-footer"><i class="fas fa-bell"></i> High Priority</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon medicine"><i class="fas fa-pills"></i></div>
                <div class="adm-stat-label">Meds Administered</div>
                <div class="adm-stat-value" id="medsToday">0</div>
                <div class="adm-stat-footer" style="color:var(--success);"><i class="fas fa-check-circle"></i> Successful</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon tests"><i class="fas fa-flask"></i></div>
                <div class="adm-stat-label">Labs Completed</div>
                <div class="adm-stat-value" id="labsToday">0</div>
                <div class="adm-stat-footer" style="color:var(--success);"><i class="fas fa-check"></i> Validated</div>
            </div>
        </div>

        <!-- ════════════════════ PATIENT ANALYTICS ════════════════════ -->
        <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem; margin-top: 2rem;">Patient Analytics</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2.8rem;">
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-chart-line"></i> Admission Trends</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="admissionChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-bed"></i> Ward Occupancy</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="wardChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-clock"></i> Length of Stay</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="losChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-users"></i> Age Distribution</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="ageChart"></canvas></div>
                </div>
            </div>
        </div>

        <!-- ════════════════════ CLINICAL OPERATIONS ════════════════════ -->
        <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem;">Clinical Operations</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2.8rem;">
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-heartbeat"></i> Vital Signs Flagging</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="vitalsChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-vial"></i> Lab Turnaround</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="tatChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card" style="grid-column: span 2;">
                <div class="adm-card-header">
                    <h3><i class="fas fa-check-circle"></i> Medication Compliance</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="medComplianceChart"></canvas></div>
                </div>
            </div>
        </div>

        <!-- ════════════════════ STAFF PERFORMANCE ════════════════════ -->
        <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem;">Staff Efficiency</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2.8rem;">
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-tasks"></i> Task Completion</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="staffTaskChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-chart-bar"></i> Workload Volume</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="volumeChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card" style="grid-column: span 2;">
                <div class="adm-card-header">
                    <h3><i class="fas fa-sign-in-alt"></i> Recent Staff Logins</h3>
                </div>
                <div class="adm-card-body" style="padding: 0;">
                    <div id="loginActivityTable" class="table-container"></div>
                </div>
            </div>
        </div>

        <!-- ════════════════════ PHARMACY & INVENTORY ════════════════════ -->
        <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem;">Pharmacy & Inventory</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2.8rem;">
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-prescription-bottle-alt"></i> Top Prescriptions</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="pharmacyChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-clipboard-check"></i> Fulfillment Rate</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="fulfillmentChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card" style="grid-column: span 2;">
                <div class="adm-card-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Critical Stock Alerts</h3>
                </div>
                <div class="adm-card-body" style="padding: 0;">
                    <div id="stockAlertsTable" class="table-container"></div>
                </div>
            </div>
        </div>

        <!-- ════════════════════ FINANCIAL & SYSTEM ════════════════════ -->
        <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem;">Financial & System Health</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2.8rem;">
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-money-bill-wave"></i> Revenue Growth</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="revenueChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-building"></i> Dept Revenue</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="deptRevenueChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card" style="grid-column: span 2;">
                <div class="adm-card-header">
                    <h3><i class="fas fa-credit-card"></i> Payment Methods</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="paymentMethodChart"></canvas></div>
                </div>
            </div>
            <div class="adm-card" style="grid-column: span 2;">
                <div class="adm-card-header">
                    <h3><i class="fas fa-user-clock"></i> Daily Active Users</h3>
                </div>
                <div class="adm-card-body">
                    <div style="height:350px;"><canvas id="usageChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="analytics_dashboard.js"></script>

<!-- Note: Tables injected via JS will need to adopt standard HTML table styling if they don't already -->

</body>
</html>