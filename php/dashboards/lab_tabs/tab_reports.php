<div class="tab-content <?= ($active_tab === 'reports') ? 'active' : '' ?>" id="reports">

<div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.01)); border:1px solid rgba(47,128,237,0.15); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1.5rem;">
    <div>
        <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.5rem;">
            <i class="fas fa-file-pdf" style="color:var(--primary); margin-right:.8rem;"></i> Operational Diagnostics Hub
        </h2>
        <p style="font-size:1.3rem; color:var(--text-muted); margin:0;">Synthesize, export, and review longitudinal clinical reporting data.</p>
    </div>
    <div style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
        <button class="adm-btn adm-btn-primary" onclick="exportLabReport('csv')" style="border-radius:10px; font-weight:800;"><span class="btn-text"><i class="fas fa-file-csv"></i> Export CSV</span></button>
        <button class="adm-btn adm-btn-primary" onclick="exportLabReport('excel')" style="border-radius:10px; font-weight:800; background:linear-gradient(135deg,#1C3A6B,var(--secondary));"><span class="btn-text"><i class="fas fa-file-excel"></i> Export Excel</span></button>
        <button class="adm-btn" onclick="window.print()" style="border-radius:10px; font-weight:800; background:var(--surface); color:var(--text-secondary); border:2px dashed var(--border);"><span class="btn-text"><i class="fas fa-print"></i> Print PDF</span></button>
    </div>
</div>

<?php
// qval() helper is globally defined in lab_dashboard.php

$total_completed = (int)qval($conn, "SELECT COUNT(*) FROM lab_test_orders WHERE order_status = 'Completed'");
$critical_rate = (float)qval($conn, "SELECT (COUNT(CASE WHEN result_interpretation='Critical' THEN 1 END) / NULLIF(COUNT(*), 0)) * 100 FROM lab_results");
$avg_tat_rep = (float)qval($conn, "SELECT AVG(TIMESTAMPDIFF(HOUR, o.created_at, r.validated_at)) FROM lab_results r JOIN lab_test_orders o ON r.order_id = o.id WHERE r.validated_at IS NOT NULL");
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; margin-bottom: 2.5rem;">
    <div class="adm-card shadow-sm" style="border-radius:16px; padding: 2rem; border-bottom: 5px solid var(--primary); text-align:center;">
        <div style="font-size: 3.5rem; font-weight: 900; color:var(--primary); line-height: 1;"><?= number_format($total_completed) ?></div>
        <div style="font-size: 1.1rem; color: var(--text-secondary); margin-top: .8rem; font-weight: 700; text-transform:uppercase; letter-spacing:1px;">Historical Completes</div>
    </div>
    <div class="adm-card shadow-sm" style="border-radius:16px; padding: 2rem; border-bottom: 5px solid var(--warning); text-align:center;">
        <div style="font-size: 3.5rem; font-weight: 900; color:var(--warning); line-height: 1;"><?= round($avg_tat_rep, 1) ?>h</div>
        <div style="font-size: 1.1rem; color: var(--text-secondary); margin-top: .8rem; font-weight: 700; text-transform:uppercase; letter-spacing:1px;">SLA Turnaround Check</div>
    </div>
    <div class="adm-card shadow-sm" style="border-radius:16px; padding: 2rem; border-bottom: 5px solid var(--danger); text-align:center;">
        <div style="font-size: 3.5rem; font-weight: 900; color:var(--danger); line-height: 1;"><?= round($critical_rate, 1) ?>%</div>
        <div style="font-size: 1.1rem; color: var(--text-secondary); margin-top: .8rem; font-weight: 700; text-transform:uppercase; letter-spacing:1px;">Critical Signal Rate</div>
    </div>
    <div class="adm-card shadow-sm" style="border-radius:16px; padding: 2rem; border-bottom: 5px solid var(--secondary); text-align:center;">
        <div style="font-size: 3.5rem; font-weight: 900; color:var(--secondary); line-height: 1;"><?= (int)qval($conn, "SELECT COUNT(*) FROM lab_samples WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)") ?></div>
        <div style="font-size: 1.1rem; color: var(--text-secondary); margin-top: .8rem; font-weight: 700; text-transform:uppercase; letter-spacing:1px;">24H Specimen Intake</div>
    </div>
</div>

<!-- ── Export Hub Panel ── -->
<div class="adm-card shadow-sm" style="border-radius:16px; margin-bottom:2rem; border-left: 5px solid var(--primary);">
    <div class="adm-card-header" style="background:linear-gradient(135deg,rgba(47,128,237,0.08),transparent); padding:1.8rem 2rem; border-bottom:1px solid var(--border);">
        <h4 style="margin:0; font-size:1.5rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:.8rem;"><i class="fas fa-cloud-download-alt" style="color:var(--primary);"></i> Data Export Hub</h4>
    </div>
    <div class="adm-card-body" style="padding:2.5rem;">
        <p style="font-size:1.3rem; color:var(--text-muted); margin-bottom:2rem;">Export clinical data to your preferred format. Use the date range filter to scope results, then click your preferred format button.</p>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
            <div>
                <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.6rem; text-transform:uppercase;">Date From</label>
                <input type="date" id="export_date_from" class="form-control" value="<?= date('Y-m-01') ?>" style="font-size:1.2rem; padding:.9rem;">
            </div>
            <div>
                <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.6rem; text-transform:uppercase;">Date To</label>
                <input type="date" id="export_date_to" class="form-control" value="<?= date('Y-m-d') ?>" style="font-size:1.2rem; padding:.9rem;">
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:1.2rem;">
            <button class="adm-btn adm-btn-primary" onclick="exportLabReport('csv')" style="border-radius:10px; font-weight:800; padding:1.2rem 2.5rem;"><span class="btn-text"><i class="fas fa-file-csv" style="margin-right:.6rem;"></i> Lab Results (CSV)</span></button>
            <button class="adm-btn adm-btn-primary" onclick="exportLabReport('excel')" style="border-radius:10px; font-weight:800; padding:1.2rem 2.5rem; background:linear-gradient(135deg,#1C3A6B,var(--secondary));"><span class="btn-text"><i class="fas fa-file-excel" style="margin-right:.6rem;"></i> Lab Results (Excel)</span></button>
            <button class="adm-btn adm-btn-primary" onclick="exportAuditCSV()" style="border-radius:10px; font-weight:800; padding:1.2rem 2.5rem; background:linear-gradient(135deg,#4a1c65,#7c3aed);"><span class="btn-text"><i class="fas fa-history" style="margin-right:.6rem;"></i> Audit Trail (CSV)</span></button>
            <button class="adm-btn adm-btn-primary" onclick="exportInventory()" style="border-radius:10px; font-weight:800; padding:1.2rem 2.5rem; background:linear-gradient(135deg,#1C3A6B,#0d6a9f);"><span class="btn-text"><i class="fas fa-boxes" style="margin-right:.6rem;"></i> Inventory Snapshot</span></button>
            <button class="adm-btn" onclick="window.print()" style="border-radius:10px; font-weight:800; padding:1.2rem 2.5rem; background:var(--surface); color:var(--text-secondary); border:2px dashed var(--border);"><span class="btn-text"><i class="fas fa-print" style="margin-right:.6rem;"></i> Print to PDF</span></button>
        </div>
    </div>
</div>

<!-- Tab Navigation Overhaul -->
<div class="adm-tab-group" style="border-bottom:2px solid var(--border); margin-bottom:2rem; padding-bottom:1rem; gap:1.5rem;" id="reportTabs">
    <button class="ftab active" data-bs-toggle="tab" data-bs-target="#vol" style="border-radius:30px;"><i class="fas fa-chart-bar"></i> Volume Analytics</button>
    <button class="ftab" data-bs-toggle="tab" data-bs-target="#tat" style="border-radius:30px;"><i class="fas fa-clock"></i> SLA & TAT Check</button>
    <button class="ftab" data-bs-toggle="tab" data-bs-target="#crit" style="border-radius:30px;"><i class="fas fa-exclamation-triangle" style="color:var(--danger);"></i> Critical Ledger Logs</button>
    <button class="ftab" data-bs-toggle="tab" data-bs-target="#inv" style="border-radius:30px;"><i class="fas fa-boxes"></i> Consumption Burn Rate</button>
</div>

<div class="tab-content" id="reportTabsContent">
    <!-- Test Volume -->
    <div class="tab-pane fade show active" id="vol" role="tabpanel">
        <div class="adm-card shadow-sm" style="border-radius:16px;">
            <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border);">
                <h4 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-layer-group" style="color:#3b82f6; margin-right:.5rem;"></i> Diagnostic Intake Trajectory (14-Day Scale)</h4>
            </div>
            <div class="adm-card-body" style="padding:2.5rem;">
                <div style="height:380px; width:100%; position:relative;"><canvas id="volumeChart"></canvas></div>
            </div>
        </div>
    </div>
    
    <!-- Turnaround Time -->
    <div class="tab-pane fade" id="tat" role="tabpanel">
        <div class="adm-card shadow-sm" style="border-radius:16px;">
            <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border);">
                <h4 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-stopwatch" style="color:#f59e0b; margin-right:.5rem;"></i> Macro Turnaround Analysis (Mean SLA)</h4>
            </div>
            <div class="adm-card-body" style="padding:2.5rem;">
                <div style="height:380px; width:100%; position:relative;"><canvas id="tatChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Critical Values Log -->
    <div class="tab-pane fade" id="crit" role="tabpanel">
        <div class="adm-card shadow-sm" style="border-radius:16px;">
            <div class="adm-card-header" style="background:linear-gradient(135deg, rgba(239,68,68,0.1), rgba(0,0,0,0)); padding:1.5rem 2rem; border-bottom:1px solid var(--border);">
                <h4 style="margin:0; font-size:1.4rem; font-weight:800; color:#ef4444;"><i class="fas fa-radiation" style="margin-right:.5rem;"></i> Historical Critical Values Record</h4>
            </div>
            <div class="adm-card-body" style="padding:1rem;">
                <div class="adm-table-wrap">
                    <table class="adm-table" id="critTable">
                        <thead>
                            <tr>
                                <th>Incident Timestamp</th>
                                <th>Vector Ref</th>
                                <th>Target Biomarker</th>
                                <th>Subject Profile</th>
                                <th>Recorded Anomaly</th>
                                <th>Oversight Personnel</th>
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
                                            $crit_params[] = "{$data['name']} (<strong style='color:#ef4444;'>{$data['value']}</strong>)";
                                        }
                                    }
                                    $crit_str = !empty($crit_params) ? implode(", ", $crit_params) : "Manual System Flag";

                                    echo "<tr>
                                            <td><span style='font-weight:700; color:var(--text-secondary);'>".date('d M Y, h:i A', strtotime($cr['created_at']))."</span></td>
                                            <td><strong style='font-family:monospace; color:#3b82f6; font-size:1.1rem;'>#ORD-".str_pad($cr['order_id'],5,'0',STR_PAD_LEFT)."</strong></td>
                                            <td><div style='font-weight:800; font-size:1.1rem;'>".e($cr['test_name'])."</div></td>
                                            <td><div style='font-weight:800; font-size:1.1rem;'>".e($cr['pat_name'])."</div><div style='font-size:0.9rem; font-weight:600; color:var(--text-muted);'>ID: P-".str_pad($cr['patient_id'],4,'0',STR_PAD_LEFT)."</div></td>
                                            <td><div style='padding:8px 14px; background:rgba(239,68,68,0.1); border-left:3px solid #ef4444; border-radius:6px; font-size:1rem; font-weight:600;'>{$crit_str}</div></td>
                                            <td><span class='adm-badge' style='background:var(--surface-3); border:1px solid var(--border); color:var(--text-primary);'><i class='fas fa-user-shield text-success'></i> ".e($cr['tech_name'])."</span></td>
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

    <!-- Inventory Usage -->
    <div class="tab-pane fade" id="inv" role="tabpanel">
        <div class="adm-card shadow-sm" style="border-radius:16px;">
            <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border);">
                <h4 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-cubes" style="color:#ea580c; margin-right:.5rem;"></i> High-Frequency Consumption Events</h4>
            </div>
            <div class="adm-card-body" style="padding:1rem;">
                <div class="adm-table-wrap">
                    <table class="adm-table" id="invUsageTable">
                        <thead><tr><th>Cryptographic Hash Timestamp</th><th>Reagent Nomenclature</th><th>Operation Logic</th><th>Scale Delta</th><th>Authorized Extractor</th></tr></thead>
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
                                             <td style='font-weight:700; color:var(--text-secondary);'>".date('d M Y | H:i:s', strtotime($ir['created_at']))."</td>
                                             <td><strong style='font-size:1.2rem; color:var(--text-primary);'>".e($ir['item_name'])."</strong></td>
                                             <td><span class='adm-badge' style='background:rgba(245,158,11,0.1); color:#f59e0b; font-weight:700;'><i class='fas fa-burn'></i> ".e($ir['action_type'])."</span></td>
                                             <td><strong style='font-size:1.3rem; color:#ef4444;'>".e($ir['new_value'])." units</strong></td>
                                             <td><span style='font-weight:700;'><i class='fas fa-user-lock' style='color:var(--text-muted);'></i> ".e($ir['full_name'])."</span></td>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    $('#critTable, #invUsageTable').DataTable({ 
        order: [[0, 'desc']], 
        pageLength: 10, 
        language: { search: "", searchPlaceholder: "Deep query logic..." } 
    });
    
    // Auto-fetch chart data on tab activation
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if($(e.target).data('bs-target') === '#vol' || $(e.target).data('bs-target') === '#tat') {
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

function renderVolumeChart(data) {
    const ctx = document.getElementById('volumeChart');
    if(!ctx) return;
    
    const isDark = document.body.getAttribute('data-theme') === 'dark';
    Chart.defaults.color = 'gray';
    Chart.defaults.font.family = "'Outfit', 'Inter', sans-serif";

    if(window.volChart) window.volChart.destroy();
    window.volChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Processed Analyses',
                data: data.values || [],
                backgroundColor: '#3b82f6',
                borderWidth: 0,
                borderRadius: 8,
                barThickness: 20
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: { 
                y: { grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }, beginAtZero: true },
                x: { grid: { display: false } }
            },
            plugins: { 
                legend: { display: false },
                tooltip: { backgroundColor: '#3b82f6', padding: 12, borderRadius: 8, titleFont:{size:14,weight:'bold'} }
            }
        }
    });
}

function renderTATChart(data) {
    const ctx = document.getElementById('tatChart');
    if(!ctx) return;
    
    const isDark = document.body.getAttribute('data-theme') === 'dark';

    if(window.tatChart) window.tatChart.destroy();
    window.tatChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Critical SLA Drift (h)',
                data: data.values || [],
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#f59e0b',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                borderWidth: 3
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: { 
                y: { grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }, beginAtZero: true, title: { display:true, text:'Drift Index (Hours)', font:{weight:'bold'} } },
                x: { grid: { display: false } }
            },
            plugins: { 
                legend: { display: false },
                tooltip: { backgroundColor: '#f59e0b', padding: 12, borderRadius: 8, titleFont:{size:14,weight:'bold'} }
            }
        }
    });
}

// ── Export Functions ──────────────────────────
function getExportDates() {
    const from = document.getElementById('export_date_from')?.value || '';
    const to   = document.getElementById('export_date_to')?.value   || '';
    return { from, to };
}

function exportLabReport(format) {
    const { from, to } = getExportDates();
    const url = `lab_exports.php?action=export_lab_report&format=${format}&date_from=${from}&date_to=${to}`;
    window.location.href = url;
}

function exportAuditCSV() {
    const { from, to } = getExportDates();
    const url = `lab_exports.php?action=export_audit_trail&format=csv&date_from=${from}&date_to=${to}`;
    window.location.href = url;
}

function exportInventory() {
    window.location.href = 'lab_exports.php?action=export_inventory&format=csv';
}
</script>

</div><!-- /.tab-content#reports -->
