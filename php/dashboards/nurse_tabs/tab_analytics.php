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

<div class="tab-content" id="analytics">

    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-chart-pie me-2"></i> Performance Analytics</h4>
            <p class="text-muted mb-0">Visual breakdown of clinical activities, medication administration, and facility alerts.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button class="btn btn-outline-secondary rounded-pill shadow-sm" onclick="window.print()">
                <i class="fas fa-print me-2"></i> Print Dashboard
            </button>
        </div>
    </div>

    <div class="row g-4 mb-4">
        
        <!-- Task Completion Donut -->
        <div class="col-lg-4 col-md-6">
            <div class="card h-100" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0 text-muted text-uppercase"><i class="fas fa-tasks me-2"></i> My Tasks (This Month)</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="taskChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medication Trends Line -->
        <div class="col-lg-8 col-md-6">
            <div class="card h-100" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0 text-muted text-uppercase"><i class="fas fa-pills me-2"></i> Meds Administered (Last 7 Days)</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="medChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Alerts Polar -->
        <div class="col-lg-6">
            <div class="card h-100" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0 text-muted text-uppercase"><i class="fas fa-ambulance me-2"></i> Facility Alerts (This Month)</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="alertChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fluid Balance Bar -->
        <div class="col-lg-6">
            <div class="card h-100" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0 text-muted text-uppercase"><i class="fas fa-tint me-2"></i> Avg Fluid Balance (My Charting)</h6>
                </div>
                <div class="card-body">
                    <?php if(empty($fluidLabels)): ?>
                        <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                            <p><i class="fas fa-info-circle"></i> No fluid charts recorded in last 5 days.</p>
                        </div>
                    <?php else: ?>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="fluidChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Chart.js Injection Library is already available via main dashboard layout -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Standard Colors based on project theme
    const cPrimary = '#E67E22'; // Orange
    const cSuccess = '#28a745';
    const cWarning = '#ffc107';
    const cDanger  = '#dc3545';
    const cInfo    = '#17a2b8';

    // 1. Task Chart (Doughnut)
    const taskCtx = document.getElementById('taskChart').getContext('2d');
    new Chart(taskCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending', 'Overdue'],
            datasets: [{
                data: [
                    <?= $taskStats['Completed'] ?>, 
                    <?= $taskStats['Pending'] ?>, 
                    <?= $taskStats['Overdue'] ?>
                ],
                backgroundColor: [cSuccess, cWarning, cDanger],
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            }
        }
    });

    // 2. Med Chart (Line)
    const medCtx = document.getElementById('medChart').getContext('2d');
    // Create gradient
    let gradientMed = medCtx.createLinearGradient(0, 0, 0, 400);
    gradientMed.addColorStop(0, 'rgba(230, 126, 34, 0.4)'); // Primary Orange transparent
    gradientMed.addColorStop(1, 'rgba(230, 126, 34, 0.0)');

    new Chart(medCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($medLabels) ?>,
            datasets: [{
                label: 'Doses Administered',
                data: <?= json_encode($medValues) ?>,
                borderColor: cPrimary,
                backgroundColor: gradientMed,
                borderWidth: 3,
                pointBackgroundColor: cPrimary,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: cPrimary,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4 // Smooth curve
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] }, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
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
                    'rgba(220, 53, 69, 0.7)', // Danger
                    'rgba(255, 193, 7, 0.7)', // Warning
                    'rgba(23, 162, 184, 0.7)', // Info
                    'rgba(40, 167, 69, 0.7)', // Success
                    'rgba(108, 117, 125, 0.7)' // Secondary
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right' } }
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
                    label: 'Avg Intake (ml)',
                    data: <?= json_encode($fluidInData) ?>,
                    backgroundColor: cInfo,
                    borderRadius: 4
                },
                {
                    label: 'Avg Output (ml)',
                    data: <?= json_encode($fluidOutData) ?>,
                    backgroundColor: cDanger,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>
});
</script>
