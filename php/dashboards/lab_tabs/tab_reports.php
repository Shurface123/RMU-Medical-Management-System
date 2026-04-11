<div class="sec-header">
    <h2 style="font-size: 1.8rem; font-weight: 700;"><i class="fas fa-chart-line"></i> Intelligence Hub & Analytics</h2>
</div>

<?php
// Fetch some quick stats for the report summary
$total_completed = (int)qv8($conn, "SELECT COUNT(*) FROM lab_test_orders WHERE order_status = 'Completed'");
$critical_rate = (float)qv8($conn, "SELECT (COUNT(CASE WHEN result_interpretation='Critical' THEN 1 END) / NULLIF(COUNT(*), 0)) * 100 FROM lab_results");
$avg_tat_rep = (float)qv8($conn, "SELECT AVG(TIMESTAMPDIFF(HOUR, o.created_at, r.validated_at)) FROM lab_results r JOIN lab_test_orders o ON r.order_id = o.id WHERE r.validated_at IS NOT NULL");
?>

<div class="adm-summary-strip" style="margin-bottom:2.5rem;">
    <div class="adm-mini-card">
        <div class="adm-mini-card-num teal"><?= number_format($total_completed) ?></div>
        <div class="adm-mini-card-label">Total Tests Completed</div>
    </div>
    <div class="adm-mini-card">
        <div class="adm-mini-card-num orange"><?= round($avg_tat_rep, 1) ?>h</div>
        <div class="adm-mini-card-label">Overall Avg. TAT</div>
    </div>
    <div class="adm-mini-card">
        <div class="adm-mini-card-num red"><?= round($critical_rate, 1) ?>%</div>
        <div class="adm-mini-card-label">Critical Result Rate</div>
    </div>
    <div class="adm-mini-card">
        <div class="adm-mini-card-num blue"><?= (int)qv8($conn, "SELECT COUNT(*) FROM lab_samples WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)") ?></div>
        <div class="adm-mini-card-label">Samples (Last 24h)</div>
    </div>
</div>

<!-- Sub-navigation for Reports -->
<ul class="nav nav-tabs mb-4 px-2" id="reportTabs" role="tablist" style="border-bottom: 2px solid var(--border); gap: 1rem;">
  <li class="nav-item" role="presentation">
    <button class="btn btn-primary nav-link active" id="vol-tab" data-bs-toggle="tab" data-bs-target="#vol" type="button" role="tab" style="border:none; background:none; padding:1rem 1.5rem; font-weight:700; color:var(--text-secondary); transition:var(--transition);"><span class="btn-text"><i class="fas fa-chart-bar"></i> Volume Analytics</span></button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="btn btn-primary nav-link" id="tat-tab" data-bs-toggle="tab" data-bs-target="#tat" type="button" role="tab" style="border:none; background:none; padding:1rem 1.5rem; font-weight:700; color:var(--text-secondary); transition:var(--transition);"><span class="btn-text"><i class="fas fa-clock"></i> SLA & TAT</span></button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="btn btn-primary nav-link" id="crit-tab" data-bs-toggle="tab" data-bs-target="#crit" type="button" role="tab" style="border:none; background:none; padding:1rem 1.5rem; font-weight:700; color:var(--text-secondary); transition:var(--transition);"><span class="btn-text"><i class="fas fa-exclamation-circle" style="color:var(--danger);"></i> Critical Ledger</span></button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="btn btn-primary nav-link" id="inv-tab" data-bs-toggle="tab" data-bs-target="#inv" type="button" role="tab" style="border:none; background:none; padding:1rem 1.5rem; font-weight:700; color:var(--text-secondary); transition:var(--transition);"><span class="btn-text"><i class="fas fa-boxes"></i> Consumption</span></button>
  </li>
</ul>

<div class="tab-content" id="reportTabsContent">
    <!-- Test Volume -->
    <div class="tab-pane fade show active" id="vol" role="tabpanel">
        <div class="info-card">
            <h4 style="margin-bottom:2rem; color:var(--text-primary); font-weight:700;"><i class="fas fa-layer-group" style="color:var(--primary); margin-right:.5rem;"></i> Diagnostic Volume Throughput (14-Day Trend)</h4>
            <div class="chart-wrap" style="height:380px;">
                <canvas id="volumeChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Turnaround Time -->
    <div class="tab-pane fade" id="tat" role="tabpanel">
        <div class="info-card">
            <h4 style="margin-bottom:2rem; color:var(--text-primary); font-weight:700;"><i class="fas fa-stopwatch" style="color:var(--orange); margin-right:.5rem;"></i> Service Level Agreement: Mean TAT per Specialty</h4>
             <div class="chart-wrap" style="height:380px;">
                <canvas id="tatChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Critical Values Log -->
    <div class="tab-pane fade" id="crit" role="tabpanel">
        <div class="info-card">
            <div class="adm-table-wrap">
                <table class="adm-table display" id="critTable">
                    <thead>
                        <tr>
                            <th>Flagged Date</th>
                            <th>Order ID</th>
                            <th>Diagnosis/Test</th>
                            <th>Patient Identity</th>
                            <th>Critical Variance</th>
                            <th>Signed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $crit_q = mysqli_query($conn, "SELECT r.created_at, r.result_id, r.order_id, r.parameters_json, 
                                                       o.patient_id, p.full_name as pat_name, c.test_name, t.full_name as tech_name
                                                       FROM lab_results r
                                                       JOIN lab_test_orders o ON r.order_id = o.id
                                                       JOIN lab_test_catalog c ON o.test_catalog_id = c.id
                                                       JOIN patients p ON o.patient_id = p.id
                                                       LEFT JOIN lab_technicians t ON r.technician_id = t.user_id
                                                       WHERE r.result_interpretation = 'Critical' OR r.parameters_json LIKE '%Critical Low%' OR r.parameters_json LIKE '%Critical High%'
                                                       ORDER BY r.created_at DESC LIMIT 100");
                        if($crit_q) {
                            while($cr = mysqli_fetch_assoc($crit_q)) {
                                $crit_params = [];
                                $json = json_decode($cr['parameters_json'], true) ?: [];
                                foreach($json as $key => $data) {
                                    if(isset($data['flag']) && stripos($data['flag'], 'Critical') !== false) {
                                        $crit_params[] = "{$data['name']} (<strong style='color:var(--danger);'>{$data['value']}</strong>)";
                                    }
                                }
                                $crit_str = !empty($crit_params) ? implode(", ", $crit_params) : "Manual Flag";

                                echo "<tr>
                                        <td><span style='font-weight:600; color:var(--text-secondary);'>".date('d M Y, h:i A', strtotime($cr['created_at']))."</span></td>
                                        <td><strong style='font-family:monospace; color:var(--primary); font-size:1.1rem;'>#ORD-".str_pad($cr['order_id'],5,'0',STR_PAD_LEFT)."</strong></td>
                                        <td><div style='font-weight:700;'>".e($cr['test_name'])."</div></td>
                                        <td><div style='font-weight:700;'>".e($cr['pat_name'])."</div><div style='font-size:0.8rem;color:var(--text-muted);'>ID: P-".str_pad($cr['patient_id'],4,'0',STR_PAD_LEFT)."</div></td>
                                        <td><div style='padding:6px 12px; background:rgba(231,76,60,0.1); border-radius:6px; font-size:0.9rem; line-height:1.4;'>{$crit_str}</div></td>
                                        <td><span class='adm-badge' style='background:var(--surface-2); border:1px solid var(--border); color:var(--text-primary);'><i class='fas fa-user-check'></i> ".e($cr['tech_name'])."</span></td>
                                      </tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Inventory Usage -->
    <div class="tab-pane fade" id="inv" role="tabpanel">
        <div class="info-card">
            <h4 style="margin-bottom:1.5rem; color:var(--text-primary); font-weight:700;">Recent Stock Consumption Events</h4>
            <div class="adm-table-wrap">
                <table class="adm-table display" id="invUsageTable">
                    <thead><tr><th>Timestamp</th><th>Reagent/Consumable</th><th>Operation</th><th>Delta</th><th>Logged By</th></tr></thead>
                    <tbody>
                        <?php
                        $inv_q = mysqli_query($conn, "SELECT a.created_at, a.action_type, i.name as item_name, a.old_value, a.new_value, t.full_name 
                                                      FROM lab_audit_trail a
                                                      JOIN reagent_inventory i ON a.record_id = i.id
                                                      LEFT JOIN lab_technicians t ON a.technician_id = t.user_id
                                                      WHERE a.module_affected = 'Reagent Inventory' AND a.action_type LIKE '%Deduction%'
                                                      ORDER BY a.created_at DESC LIMIT 50");
                        if ($inv_q && mysqli_num_rows($inv_q) > 0) {
                             while($ir = mysqli_fetch_assoc($inv_q)) {
                                 echo "<tr>
                                         <td>".date('d M Y, H:i', strtotime($ir['created_at']))."</td>
                                         <td><strong style='font-size:1.1rem;'>".e($ir['item_name'])."</strong></td>
                                         <td><span class='adm-badge adm-badge-warning'><i class='fas fa-minus-circle'></i> ".e($ir['action_type'])."</span></td>
                                         <td><strong style='font-size:1.2rem; color:var(--danger);'>".e($ir['new_value'])." units</strong></td>
                                         <td>".e($ir['full_name'])."</td>
                                       </tr>";
                             }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#critTable, #invUsageTable').DataTable({ 
        order: [[0, 'desc']], 
        pageLength: 10, 
        language: { search: "", searchPlaceholder: "Filter reports..." } 
    });
    
    // Auto-fetch chart data on tab activation
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if(e.target.id === 'vol-tab' || e.target.id === 'tat-tab') {
            fetchChartData();
        }
    });

    fetchChartData();
});

function fetchChartData() {
    $.ajax({
        url: 'lab_actions.php',
        type: 'POST',
        data: { action: 'fetch_report_data' },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                renderVolumeChart(res.data.volume);
                renderTATChart(res.data.tat);
            }
        }
    });
}

let volChart, tatChart;
function renderVolumeChart(data) {
    const ctx = document.getElementById('volumeChart');
    if(!ctx) return;
    if(volChart) volChart.destroy();
    volChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Tests Completed',
                data: data.values || [],
                backgroundColor: 'rgba(13, 148, 136, 0.7)',
                borderColor: 'rgba(13, 148, 136, 1)',
                borderWidth: 2,
                borderRadius: 8,
                barThickness: 25
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: { 
                y: { grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true },
                x: { grid: { display: false } }
            },
            plugins: { 
                legend: { display: false },
                tooltip: { backgroundColor: 'rgba(13, 148, 136, 0.9)', padding: 12, borderRadius: 8 }
            }
        }
    });
}

function renderTATChart(data) {
    const ctx = document.getElementById('tatChart');
    if(!ctx) return;
    if(tatChart) tatChart.destroy();
    tatChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Avg Turnaround (Hours)',
                data: data.values || [],
                borderColor: '#e67e22',
                backgroundColor: 'rgba(230, 126, 34, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#e67e22',
                pointRadius: 5,
                borderWidth: 3
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: { 
                y: { grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true, title: { display:true, text:'Hours (Mean)' } },
                x: { grid: { display: false } }
            },
            plugins: { 
                legend: { display: false },
                tooltip: { backgroundColor: '#e67e22', padding: 12, borderRadius: 8 }
            }
        }
    });
}
</script>

<style>
#reportTabs .nav-link:hover { color: var(--primary) !important; }
#reportTabs .nav-link.active { color: var(--primary) !important; border-bottom: 3px solid var(--primary) !important; background: rgba(var(--primary-rgb), 0.05) !important; }
</style>
