<?php
// ============================================================
// NURSE DASHBOARD - ANALYTICS (MODULE 11)
// ============================================================
if (!isset($conn)) exit;

// ── 1. TASKS COMPLETION (Current Month) ──────────────────────
$q_tasks = mysqli_query($conn, "
    SELECT status, COUNT(*) as count 
    FROM nurse_tasks 
    WHERE nurse_id = $nurse_pk 
      AND MONTH(created_at) = MONTH(CURRENT_DATE())
      AND YEAR(created_at) = YEAR(CURRENT_DATE())
    GROUP BY status
");
$taskStats = ['Pending' => 0, 'Completed' => 0, 'Overdue' => 0];
if($q_tasks) {
    while($r = mysqli_fetch_assoc($q_tasks)) {
        if(isset($taskStats[$r['status']])) $taskStats[$r['status']] = (int)$r['count'];
    }
}

// ── 2. MEDICATIONS ADMINISTERED (Last 7 Days) ────────────────
$q_meds = mysqli_query($conn, "
    SELECT DATE(administered_at) as d, COUNT(*) as count 
    FROM medication_administration 
    WHERE nurse_id = $nurse_pk 
      AND status = 'Administered'
      AND administered_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
    GROUP BY DATE(administered_at)
    ORDER BY d ASC
");
$medLabels = [];
$medData = [];
// Pre-fill last 7 days with 0s
for($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $medLabels[] = date('D, M d', strtotime($d));
    $medData[$d] = 0;
}
if($q_meds) {
    while($r = mysqli_fetch_assoc($q_meds)) {
        if(isset($medData[$r['d']])) $medData[$r['d']] = (int)$r['count'];
    }
}
$medValues = array_values($medData);

// ── 3. EMERGENCY ALERTS DISTRIBUTION (Global Facility) ────────
$q_alerts = mysqli_query($conn, "
    SELECT alert_type, COUNT(*) as count 
    FROM emergency_alerts 
    WHERE MONTH(triggered_at) = MONTH(CURRENT_DATE())
      AND YEAR(triggered_at) = YEAR(CURRENT_DATE())
    GROUP BY alert_type
    ORDER BY count DESC
");
$alertLabels = [];
$alertData = [];
if($q_alerts) {
    while($r = mysqli_fetch_assoc($q_alerts)) {
        $alertLabels[] = $r['alert_type'];
        $alertData[] = (int)$r['count'];
    }
}
if(empty($alertData)) {
    $alertLabels = ['No Alerts']; $alertData = [1]; 
}

// ── 4. FLUID BALANCE AVERAGES (My Patients, last 5 days) ─────
$q_fluids = mysqli_query($conn, "
    SELECT record_date, AVG(total_intake) as avg_in, AVG(total_output) as avg_out 
    FROM fluid_balance 
    WHERE nurse_id = $nurse_pk 
      AND record_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 5 DAY)
    GROUP BY record_date
    ORDER BY record_date ASC
");
$fluidLabels = [];
$fluidInData = [];
$fluidOutData = [];
if($q_fluids) {
    while($r = mysqli_fetch_assoc($q_fluids)) {
        $fluidLabels[] = date('M d', strtotime($r['record_date']));
        $fluidInData[] = round($r['avg_in'], 1);
        $fluidOutData[] = round($r['avg_out'], 1);
    }
}
?>

<div class="tab-content active" id="analytics">

    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--primary); margin-bottom:.3rem;"><i class="fas fa-chart-line pulse-fade" style="margin-right:.8rem;"></i> Performance Analytics</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Real-time clinical metrics and departmental performance monitoring.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center;">
             <div style="background:rgba(var(--primary-rgb),0.05); border:1px solid rgba(var(--primary-rgb),0.1); padding:.8rem 1.5rem; border-radius:12px; display:flex; align-items:center; gap:1rem;">
                <span class="activity-dot shadow-sm" style="background:var(--success); position:static;"></span>
                <div style="font-size:1.2rem; font-weight:800; color:var(--text-primary);">Clinical Insight: <small style="font-weight:700; color:var(--success);">LIVE ACTIVE</small></div>
            </div>
            <button class="btn-icon btn btn-ghost" onclick="window.print()" style="border-radius:12px; font-weight:700; border-color:var(--primary); color:var(--primary);"><span class="btn-text">
                <i class="fas fa-print"></i> Print Analysis
            </span></button>
        </div>
    </div>

    <!-- Charts Grid Row 1: Doughnut + Line -->
    <div class="charts-grid" style="display:grid; grid-template-columns:1fr 1.5fr; gap:2.5rem; margin-bottom:2.5rem;">

        <!-- Task Completion Donut -->
        <div class="adm-card shadow-sm">
            <div class="adm-card-header" style="background:rgba(var(--primary-rgb),0.02); border-bottom:1.5px solid var(--border);">
                <h3 style="font-size:1.4rem; font-weight:700; color:var(--primary);"><i class="fas fa-tasks"></i> My Task Distribution <span style="font-size:1rem; color:var(--text-muted); font-weight:500;">(Monthly)</span></h3>
            </div>
            <div class="adm-card-body" style="padding:2.5rem;">
                <div class="chart-wrap" style="height:300px;"><canvas id="taskChart"></canvas></div>
            </div>
        </div>

        <!-- Medication Trends Line -->
        <div class="adm-card shadow-sm">
            <div class="adm-card-header" style="background:rgba(var(--primary-rgb),0.02); border-bottom:1.5px solid var(--border);">
                <h3 style="font-size:1.4rem; font-weight:700; color:var(--primary);"><i class="fas fa-pills"></i> Medication Administration <span style="font-size:1rem; color:var(--text-muted); font-weight:500;">(7 Day Trend)</span></h3>
            </div>
            <div class="adm-card-body" style="padding:2.5rem;">
                <div class="chart-wrap" style="height:300px;"><canvas id="medChart"></canvas></div>
            </div>
        </div>

    </div>

    <!-- Charts Grid Row 2: Polar + Bar -->
    <div class="charts-grid" style="display:grid; grid-template-columns:1.2fr 1fr; gap:2.5rem;">

        <!-- Emergency Alerts Polar Area -->
        <div class="adm-card shadow-sm">
            <div class="adm-card-header" style="background:rgba(var(--primary-rgb),0.02); border-bottom:1.5px solid var(--border);">
                <h3 style="font-size:1.4rem; font-weight:700; color:var(--danger);"><i class="fas fa-ambulance"></i> Emergency Response Log <span style="font-size:1rem; color:var(--text-muted); font-weight:500;">(Facility Wide)</span></h3>
            </div>
            <div class="adm-card-body" style="padding:2.5rem;">
                <div class="chart-wrap" style="height:350px;"><canvas id="alertChart"></canvas></div>
            </div>
        </div>

        <!-- Fluid Balance Bar -->
        <div class="adm-card shadow-sm">
            <div class="adm-card-header" style="background:rgba(var(--primary-rgb),0.02); border-bottom:1.5px solid var(--border);">
                <h3 style="font-size:1.4rem; font-weight:700; color:var(--info);"><i class="fas fa-tint"></i> Clinical Balance Average <span style="font-size:1rem; color:var(--text-muted); font-weight:500;">(Assigned Patients)</span></h3>
            </div>
            <div class="adm-card-body" style="padding:2.5rem;">
                <?php if(empty($fluidLabels)): ?>
                    <div style="padding:6rem 2rem; text-align:center; color:var(--text-muted);">
                        <i class="fas fa-chart-bar" style="font-size:4rem; opacity:0.1; margin-bottom:1.5rem; display:block;"></i>
                        <h5 style="font-size:1.4rem; font-weight:700;">Insufficient Data Points</h5>
                        <p style="font-size:1.15rem;">Complete more I&O charts to view trends.</p>
                    </div>
                <?php else: ?>
                    <div class="chart-wrap" style="height:350px;"><canvas id="fluidChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Premium Color Tokens
    const cPrimary = '#E67E22'; 
    const cSuccess = '#2ecc71';
    const cWarning = '#f1c40f';
    const cDanger  = '#e74c3c';
    const cInfo    = '#3498db';
    const cNeutral = '#95a5a6';

    const chartFont = { family: "'Inter', 'Segoe UI', sans-serif", size: 12, weight: '600' };

    // 1. Task Chart (Doughnut)
    const taskCtx = document.getElementById('taskChart').getContext('2d');
    new Chart(taskCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'In Progress', 'Critical'],
            datasets: [{
                data: [
                    <?= $taskStats['Completed'] ?>, 
                    <?= $taskStats['Pending'] ?>, 
                    <?= $taskStats['Overdue'] ?>
                ],
                backgroundColor: [cSuccess, cWarning, cDanger],
                hoverOffset: 15,
                borderWidth: 6,
                borderColor: '#fff',
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 30, font: chartFont } },
                tooltip: { padding: 15, backgroundColor: 'rgba(0,0,0,0.8)', titleFont: { size: 14, weight: '800' } }
            }
        }
    });

    // 2. Med Chart (Line)
    const medCtx = document.getElementById('medChart').getContext('2d');
    let gradientMed = medCtx.createLinearGradient(0, 0, 0, 300);
    gradientMed.addColorStop(0, 'rgba(230, 126, 34, 0.25)');
    gradientMed.addColorStop(1, 'rgba(230, 126, 34, 0.0)');

    new Chart(medCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($medLabels) ?>,
            datasets: [{
                label: 'Dosage Units',
                data: <?= json_encode($medValues) ?>,
                borderColor: cPrimary,
                backgroundColor: gradientMed,
                borderWidth: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: cPrimary,
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 9,
                fill: true,
                tension: 0.45
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [6, 6], color: 'rgba(0,0,0,0.05)' }, ticks: { font: chartFont, padding: 10 } },
                x: { grid: { display: false }, ticks: { font: chartFont, padding: 10 } }
            }
        }
    });

    // 3. Emergency Alerts Chart (Polar Area)
    const alertCtx = document.getElementById('alertChart').getContext('2d');
    new Chart(alertCtx, {
        type: 'polarArea',
        data: {
            labels: <?= json_encode($alertLabels) ?>,
            datasets: [{
                data: <?= json_encode($alertData) ?>,
                backgroundColor: [
                    'rgba(231, 76, 60, 0.75)', 
                    'rgba(241, 196, 15, 0.75)', 
                    'rgba(52, 152, 219, 0.75)', 
                    'rgba(46, 204, 113, 0.75)', 
                    'rgba(149, 165, 166, 0.75)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { r: { ticks: { display: false }, grid: { color: 'rgba(0,0,0,0.05)' } } },
            plugins: { legend: { position: 'right', labels: { usePointStyle: true, padding: 20, font: chartFont } } }
        }
    });

    <?php if(!empty($fluidLabels)): ?>
    // 4. Fluid Balance Chart (Bar)
    const fluidCtx = document.getElementById('fluidChart').getContext('2d');
    new Chart(fluidCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($fluidLabels) ?>,
            datasets: [
                {
                    label: 'Clinical Intake (ml)',
                    data: <?= json_encode($fluidInData) ?>,
                    backgroundColor: cInfo,
                    borderRadius: 8,
                    barPercentage: 0.6
                },
                {
                    label: 'Clinical Output (ml)',
                    data: <?= json_encode($fluidOutData) ?>,
                    backgroundColor: cDanger,
                    borderRadius: 8,
                    barPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 20, font: chartFont } } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [6, 6], color: 'rgba(0,0,0,0.05)' }, ticks: { font: chartFont, padding: 10 } },
                x: { grid: { display: false }, ticks: { font: chartFont, padding: 10 } }
            }
        }
    });
    <?php endif; ?>
});
</script>
