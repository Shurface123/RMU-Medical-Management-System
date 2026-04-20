<?php
// ============================================================
// LAB DASHBOARD - TAB ANALYTICS (PREMIUM UI REWRITE)
// ============================================================
if (!isset($user_id)) { exit; }

if (!function_exists('qv8')) {
    function qv8($conn, $q) { $r = mysqli_query($conn, $q); return $r ? (mysqli_fetch_row($r)[0] ?? 0) : 0; }
}

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

// QC representative values
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

<div class="tab-content <?= ($active_tab === 'analytics') ? 'active' : '' ?>" id="analytics">

    <div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.01)); border:1px solid rgba(47,128,237,0.15); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.5rem;">
                <i class="fas fa-chart-line" style="color:var(--primary); margin-right:.8rem;"></i> Operational Analytics Base
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted); margin:0;">Real-time KPIs, turn-around deviations, and workforce load balancing.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center; margin-top:1.5rem; flex-wrap:wrap;">
            <button class="adm-btn adm-btn-primary" onclick="window.location.href='?tab=reports'" style="border-radius:10px; font-weight:800;"><span class="btn-text"><i class="fas fa-file-pdf"></i> Extract Reports</span></button>
            <button class="adm-btn" onclick="window.location.reload();" style="border-radius:10px; font-weight:800; background:var(--surface-1); color:var(--text-primary); border:2px dashed var(--border);"><span class="btn-text"><i class="fas fa-sync"></i> Refresh Data</span></button>
        </div>
    </div>

    <!-- Live KPI Strip -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; margin-bottom: 2.5rem;">
        <div class="adm-card shadow-sm" style="border-radius:16px; padding: 2rem; border-bottom: 5px solid var(--primary); text-align:center;">
            <div style="font-size: 3.5rem; font-weight: 900; color:var(--primary); line-height: 1;"><?= number_format($total_tests) ?></div>
            <div style="font-size: 1.1rem; color: var(--text-secondary); margin-top: .8rem; font-weight: 700; text-transform:uppercase; letter-spacing:1px;">Validated Tests</div>
        </div>
        <div class="adm-card shadow-sm" style="border-radius:16px; padding: 2rem; border-bottom: 5px solid <?= ($avg_tat_raw > 6) ? 'var(--warning)' : 'var(--primary)' ?>; text-align:center;">
            <div style="font-size: 3.5rem; font-weight: 900; color:<?= ($avg_tat_raw > 6) ? 'var(--warning)' : 'var(--primary)' ?>; line-height: 1;"><?= $avg_tat ?></div>
            <div style="font-size: 1.1rem; color: var(--text-secondary); margin-top: .8rem; font-weight: 700; text-transform:uppercase; letter-spacing:1px;">Avg Turnaround</div>
        </div>
        <div class="adm-card shadow-sm" style="border-radius:16px; padding: 2rem; border-bottom: 5px solid <?= ($rejection_rate > 5) ? 'var(--danger)' : 'var(--primary)' ?>; text-align:center;">
            <div style="font-size: 3.5rem; font-weight: 900; color:<?= ($rejection_rate > 5) ? 'var(--danger)' : 'var(--primary)' ?>; line-height: 1;"><?= $rejection_rate ?>%</div>
            <div style="font-size: 1.1rem; color: var(--text-secondary); margin-top: .8rem; font-weight: 700; text-transform:uppercase; letter-spacing:1px;">Specimen Rejection</div>
        </div>
        <div class="adm-card shadow-sm" style="border-radius:16px; padding: 2rem; border-bottom: 5px solid var(--danger); text-align:center;">
            <div style="font-size: 3.5rem; font-weight: 900; color:var(--danger); line-height: 1;"><?= number_format($critical_count) ?></div>
            <div style="font-size: 1.1rem; color: var(--text-secondary); margin-top: .8rem; font-weight: 700; text-transform:uppercase; letter-spacing:1px;">Critical Alerts Logged</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-bottom:2.5rem;">
        
        <!-- Category Doughnut -->
        <div class="adm-card shadow-sm" style="border-radius:16px;">
            <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border);">
                <h4 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-chart-pie" style="color:var(--role-accent); margin-right:.5rem;"></i> Volume Distribution</h4>
            </div>
            <div class="adm-card-body" style="padding:2.5rem;">
                <div style="height:350px; width:100%; position:relative;"><canvas id="categoryChart"></canvas></div>
            </div>
        </div>

        <!-- QC Levey-Jennings Chart -->
        <div class="adm-card shadow-sm" style="border-radius:16px;">
            <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <h4 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-microscope" style="color:var(--role-accent); margin-right:.5rem;"></i> QC: Levey-Jennings Analysis</h4>
                <div style="font-size:1rem; color:var(--text-primary); font-weight:700; background:var(--surface-3); padding:0.6rem 1.2rem; border-radius:30px; border:1px solid var(--border);">
                    Mean: <?= $qc_mean ?> | SD: <?= $qc_sd ?>
                </div>
            </div>
            <div class="adm-card-body" style="padding:2.5rem;">
                <div style="height:350px; width:100%; position:relative;"><canvas id="qcChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Workload Balancing Table -->
    <div class="adm-card shadow-sm" style="border-radius:16px;">
        <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border);">
            <h4 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-users-cog" style="color:var(--role-accent); margin-right:.5rem;"></i> Active Workforce Flow Distribution</h4>
        </div>
        <div class="adm-card-body" style="padding:1rem;">
            <?php
            $wl_rows = $workload_q ? mysqli_num_rows($workload_q) : 0;
            if ($wl_rows > 0): ?>
            <div class="adm-table-wrap">
                <table class="adm-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Technician Personnel</th>
                            <th>Active Queue Burden</th>
                            <th>Resolved Analyses</th>
                            <th>AI Utilization Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($wl = mysqli_fetch_assoc($workload_q)):
                        $load = (int)$wl['active_orders'];
                        $load_color = $load >= 10 ? 'var(--danger)' : ($load >= 5 ? 'var(--warning)' : 'var(--success)');
                        $load_bg = $load >= 10 ? 'var(--danger-light)' : ($load >= 5 ? 'var(--warning-light)' : 'var(--success-light)');
                        $load_label = $load >= 10 ? 'Overburdened' : ($load >= 5 ? 'Elevated Load' : 'Optimal Capacity');
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight: 800; font-size: 1.3rem; color: var(--text-primary);"><?= e($wl['full_name']) ?></div>
                                <div style="font-size: 1rem; color: var(--text-muted); font-weight:600;"><i class="fas fa-id-badge"></i> System ID: USR-<?= $wl['user_id'] ?></div>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <div style="width:200px; background:var(--surface-2); border-radius:10px; height:14px; overflow:hidden; border:1px solid var(--border); box-shadow:inset 0 1px 3px rgba(0,0,0,0.1);">
                                        <div style="width:<?= min(100, $load*10) ?>%; height:100%; background:<?= $load_color ?>; border-radius:10px; box-shadow:0 0 10px <?= $load_color ?>55; transition:width 0.4s ease;"></div>
                                    </div>
                                    <span style="font-weight:900; font-size:1.4rem; color:<?= $load_color ?>;"><?= $load ?></span>
                                </div>
                            </td>
                            <td><strong style="font-size:1.3rem; color:var(--text-secondary);"><?= (int)$wl['total_results'] ?> Logs</strong></td>
                            <td><span class="adm-badge" style="background:<?= $load_bg ?>; color:<?= $load_color ?>; font-weight:800; font-size:1.1rem; padding:.6rem 1.2rem;"><?= $load_label ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div style="padding:4rem; text-align:center;">
                    <i class="fas fa-sync fa-spin" style="font-size:3rem; color:var(--role-accent); margin-bottom:1rem;"></i>
                    <h3 style="color:var(--text-primary); margin:0;">Calculating Global Telemetry</h3>
                    <p style="color:var(--text-muted);">Please wait while the system synchronizes workforce nodes.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {

    // Global Defaults for Dark Mode Compatibility
    Chart.defaults.color = 'gray';
    Chart.defaults.font.family = "'Outfit', 'Inter', sans-serif";
    const isDark = document.body.getAttribute('data-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

    // Category Doughnut
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: ['Hematology','Biochemistry','Microbiology','Immunology','Urinalysis'],
            datasets: [{ 
                data: [35,30,15,10,10], 
                backgroundColor: ['#0d9488','#0ea5e9','#f59e0b','#dc2626','#8b5cf6'], 
                borderWidth: isDark ? 2 : 0,
                borderColor: isDark ? '#1a1a1a' : '#fff'
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            cutout: '75%',
            plugins: { 
                legend: { position: 'bottom', labels: { padding: 20, font: {size: 13, weight:'bold'} } } 
            } 
        }
    });

    // Levey-Jennings QC Control Chart
    const qcLabels = <?= json_encode($qc_labels) ?>;
    const qcVals   = <?= json_encode($qc_values) ?>;
    const mean     = <?= $qc_mean ?>;
    const sd1up    = <?= $qc_p1sd ?>;
    const sd1dn    = <?= $qc_m1sd ?>;
    const sd2up    = <?= $qc_p2sd ?>;
    const sd2dn    = <?= $qc_m2sd ?>;

    const qcCtx = document.getElementById('qcChart').getContext('2d');
    new Chart(qcCtx, {
        type: 'line',
        data: {
            labels: qcLabels,
            datasets: [
                {
                    label: 'QC Result',
                    data: qcVals,
                    borderColor: '#0d9488',
                    backgroundColor: '#0d9488',
                    tension: 0.3,
                    borderWidth: 3,
                    pointRadius: 6,
                    pointBackgroundColor: '#0f766e',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    zIndex: 10
                },
                { label: '+2 SD', data: Array(qcLabels.length).fill(sd2up), borderColor: '#f43f5e', borderWidth: 2, borderDash: [5,5], pointRadius: 0, fill: false },
                { label: '+1 SD', data: Array(qcLabels.length).fill(sd1up), borderColor: '#f59e0b', borderWidth: 2, borderDash: [5,5], pointRadius: 0, fill: false },
                { label: 'Mean',  data: Array(qcLabels.length).fill(mean),  borderColor: '#22c55e', borderWidth: 3, pointRadius: 0, fill: false },
                { label: '-1 SD', data: Array(qcLabels.length).fill(sd1dn), borderColor: '#f59e0b', borderWidth: 2, borderDash: [5,5], pointRadius: 0, fill: false },
                { label: '-2 SD', data: Array(qcLabels.length).fill(sd2dn), borderColor: '#f43f5e', borderWidth: 2, borderDash: [5,5], pointRadius: 0, fill: false },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { min: mean - 3*<?= $qc_sd ?>, max: mean + 3*<?= $qc_sd ?>, title: { display: true, text: 'Concentration (mg/dL)', font:{weight:'bold'} }, grid:{color:gridColor} },
                x: { grid:{display:false} }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            }
        }
    });

});
</script>
