<?php
// ============================================================
// LAB DASHBOARD - TAB ANALYTICS (Module 10) — Phase 8
// ============================================================
if (!isset($user_id)) { exit; }

function qv8($conn, $q) { $r = mysqli_query($conn, $q); return $r ? (mysqli_fetch_row($r)[0] ?? 0) : 0; }

$total_tests    = (int)qv8($conn, "SELECT COUNT(*) FROM lab_results WHERE result_status IN ('Validated','Released','Amended')");
$avg_tat_raw    = (float)qv8($conn, "SELECT AVG(TIMESTAMPDIFF(HOUR, o.created_at, r.validated_at)) FROM lab_results r JOIN lab_test_orders o ON r.order_id = o.id WHERE r.validated_at IS NOT NULL");
$avg_tat        = $avg_tat_raw > 0 ? round($avg_tat_raw, 1) . 'h' : 'N/A';
$rejection_num  = (int)qv8($conn, "SELECT COUNT(*) FROM lab_samples WHERE status='Rejected'");
$total_samples  = max(1, (int)qv8($conn, "SELECT COUNT(*) FROM lab_samples"));
$rejection_rate = round(($rejection_num / $total_samples) * 100, 1);
$critical_count = (int)qv8($conn, "SELECT COUNT(*) FROM lab_results WHERE result_interpretation='Critical'");

// Workload distribution per technician
$workload_q = mysqli_query($conn, "
    SELECT lt.full_name, lt.user_id,
           COUNT(CASE WHEN o.order_status IN ('Pending','Processing','Accepted') THEN 1 END) AS active_orders,
           COUNT(r.result_id) AS total_results
    FROM lab_technicians lt
    LEFT JOIN lab_test_orders o ON o.technician_id = lt.user_id
    LEFT JOIN lab_results r ON r.technician_id = lt.user_id
    GROUP BY lt.user_id, lt.full_name
    ORDER BY active_orders DESC
");

// QC representative values — would normally come from a lab_qc_log table
$qc_labels  = ['Day 1','Day 2','Day 3','Day 4','Day 5','Day 6','Day 7','Day 8','Day 9','Day 10'];
$qc_values  = [130, 134, 145, 128, 138, 160, 126, 135, 131, 142];
$qc_mean    = round(array_sum($qc_values) / count($qc_values), 1);
$qc_var     = array_sum(array_map(fn($v) => pow($v - $qc_mean, 2), $qc_values)) / count($qc_values);
$qc_sd      = round(sqrt($qc_var), 1);
$qc_p1sd    = round($qc_mean + $qc_sd, 1);
$qc_m1sd    = round($qc_mean - $qc_sd, 1);
$qc_p2sd    = round($qc_mean + 2*$qc_sd, 1);
$qc_m2sd    = round($qc_mean - 2*$qc_sd, 1);
?>

<div class="sec-header">
    <h2><i class="fas fa-chart-line"></i> Lab Analytics Dashboard</h2>
    <div style="display:flex; gap:1rem;">
        <button class="adm-btn adm-btn-primary" onclick="window.location.href='?tab=reports'"><i class="fas fa-file-pdf"></i> Full Reports</button>
    </div>
</div>

<!-- Live KPI Strip -->
<div class="adm-summary-strip" style="margin-bottom:2.5rem;">
    <div class="adm-mini-card">
        <div class="adm-mini-card-num teal"><?= number_format($total_tests) ?></div>
        <div class="adm-mini-card-label">Tests Validated</div>
    </div>
    <div class="adm-mini-card">
        <div class="adm-mini-card-num <?= ($avg_tat_raw > 6) ? 'orange' : 'teal' ?>"><?= $avg_tat ?></div>
        <div class="adm-mini-card-label">Avg. Turnaround</div>
    </div>
    <div class="adm-mini-card">
        <div class="adm-mini-card-num <?= ($rejection_rate > 5) ? 'red' : 'teal' ?>"><?= $rejection_rate ?>%</div>
        <div class="adm-mini-card-label">Rejection Rate</div>
    </div>
    <div class="adm-mini-card">
        <div class="adm-mini-card-num red"><?= number_format($critical_count) ?></div>
        <div class="adm-mini-card-label">Critical Alerts</div>
    </div>
</div>

<!-- Charts Row -->
<div class="charts-grid" style="grid-template-columns: 380px 1fr; gap: 2rem; margin-bottom:2.5rem;">

    <!-- Category Doughnut -->
    <div class="info-card">
        <h4 style="margin-bottom:1.5rem; color:var(--text-primary); font-weight:700;"><i class="fas fa-chart-pie" style="color:var(--primary); margin-right:.5rem;"></i> Volume Distribution</h4>
        <div class="chart-wrap" style="height:350px;"><canvas id="categoryChart"></canvas></div>
    </div>

    <!-- QC Levey-Jennings Chart -->
    <div class="info-card" style="border-top:4px solid var(--role-accent);">
        <h4 style="margin-bottom:.5rem; color:var(--text-primary); font-weight:700;"><i class="fas fa-microscope" style="color:var(--role-accent); margin-right:.5rem;"></i> QC Control: Glucose (Mindray BS-240)</h4>
        <div style="font-size:0.9rem; color:var(--text-muted); margin-bottom:1.5rem; font-weight:600; background:var(--surface-2); padding:0.5rem 1rem; border-radius:30px; display:inline-block;">
            Levey-Jennings Delta Analyst &mdash; Mean: <?= $qc_mean ?> | SD: <?= $qc_sd ?>
        </div>
        <div class="chart-wrap" style="height:350px;"><canvas id="qcChart"></canvas></div>
    </div>
</div>

<!-- Workload Balancing Table -->
<div class="info-card" style="border-left:5px solid var(--role-accent); margin-bottom:2.5rem;">
    <h4 style="margin-bottom:2rem; color:var(--text-primary); font-weight:700;"><i class="fas fa-balance-scale" style="color:var(--role-accent); margin-right:.5rem;"></i> Faculty Workload Equalizer</h4>
    <?php
    $wl_rows = $workload_q ? mysqli_num_rows($workload_q) : 0;
    if ($wl_rows > 0): ?>
    <div class="adm-table-wrap">
        <table class="adm-table display" style="width:100%;">
            <thead>
                <tr>
                    <th>Technician Personnel</th>
                    <th>Current Operation Load</th>
                    <th>Signed Certificates</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while($wl = mysqli_fetch_assoc($workload_q)):
                $load = (int)$wl['active_orders'];
                $load_color = $load >= 10 ? 'var(--danger)' : ($load >= 5 ? 'var(--warning)' : 'var(--success)');
                $load_label = $load >= 10 ? 'Overloaded' : ($load >= 5 ? 'Near Capacity' : 'Available');
            ?>
                <tr>
                    <td>
                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary);"><?= e($wl['full_name']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight:600;">System ID: USR-<?= $wl['user_id'] ?></div>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:160px; background:var(--surface-2); border-radius:30px; height:10px; overflow:hidden; border:1px solid var(--border);">
                                <div style="width:<?= min(100, $load*10) ?>%; height:100%; background:<?= $load_color ?>; border-radius:30px; box-shadow:0 0 8px <?= $load_color ?>55;"></div>
                            </div>
                            <span style="font-weight:800; color:<?= $load_color ?>;"><?= $load ?></span>
                        </div>
                    </td>
                    <td><strong style="font-size:1.15rem;"><?= (int)$wl['total_results'] ?></strong></td>
                    <td><span class="adm-badge" style="background:<?= $load_color ?>22; color:<?= $load_color ?>; border:1px solid <?= $load_color ?>44; font-weight:700;"><?= $load_label ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p style="color:var(--text-muted); text-align:center; padding:2rem; font-style:italic;">Synchronizing workforce telemetry...</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    // Category Doughnut
    new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Hematology','Biochemistry','Microbiology','Immunology','Urinalysis'],
            datasets: [{ data: [35,30,15,10,10], backgroundColor: ['#0d9488','#2980b9','#f39c12','#e74c3c','#8e44ad'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
    });

    // Levey-Jennings QC Control Chart
    const qcLabels = <?= json_encode($qc_labels) ?>;
    const qcVals   = <?= json_encode($qc_values) ?>;
    const mean     = <?= $qc_mean ?>;
    const p1sd     = <?= $qc_p1sd ?>;
    const m1sd     = <?= $qc_m1sd ?>;
    const p2sd     = <?= $qc_p2sd ?>;
    const m2sd     = <?= $qc_m2sd ?>;
    const n        = qcLabels.length;

    // Color control points: red if out of 2SD, orange if out of 1SD, green otherwise
    const pointColors = qcVals.map(v =>
        (v > p2sd || v < m2sd) ? '#e74c3c' :
        (v > p1sd || v < m1sd) ? '#f39c12' : '#0d9488'
    );

    new Chart(document.getElementById('qcChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: qcLabels,
            datasets: [
                {
                    label: 'QC Value', data: qcVals,
                    borderColor: '#0d9488', borderWidth: 2.5,
                    pointBackgroundColor: pointColors, pointRadius: 6,
                    fill: false, tension: 0.1
                },
                { label: 'Mean',  data: Array(n).fill(mean), borderColor: '#555', borderWidth: 1.5, borderDash: [6,4], pointRadius: 0, fill: false },
                { label: '+1SD',  data: Array(n).fill(p1sd), borderColor: '#27ae60', borderWidth: 1, borderDash: [4,3], pointRadius: 0, fill: false },
                { label: '-1SD',  data: Array(n).fill(m1sd), borderColor: '#27ae60', borderWidth: 1, borderDash: [4,3], pointRadius: 0, fill: false },
                { label: '+2SD (Warning)', data: Array(n).fill(p2sd), borderColor: '#f39c12', borderWidth: 1.5, borderDash: [5,3], pointRadius: 0, fill: false },
                { label: '-2SD (Warning)', data: Array(n).fill(m2sd), borderColor: '#f39c12', borderWidth: 1.5, borderDash: [5,3], pointRadius: 0, fill: false }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        afterLabel: ctx => {
                            if (ctx.datasetIndex !== 0) return;
                            const v = ctx.raw;
                            if (v > p2sd || v < m2sd) return '⚠ OUT OF CONTROL (>2SD)';
                            if (v > p1sd || v < m1sd) return '⚡ Warning zone (>1SD)';
                            return '✓ In control';
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Control Value (mg/dL)' },
                    suggestedMin: m2sd - 10,
                    suggestedMax: p2sd + 10
                }
            }
        }
    });
});
</script>
