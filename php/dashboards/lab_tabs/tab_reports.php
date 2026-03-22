<?php
// ============================================================
// LAB DASHBOARD - TAB REPORTS (Module 8)
// ============================================================
if (!isset($user_id)) { exit; }
?>
<div class="sec-header">
    <h2><i class="fas fa-chart-line"></i> Laboratory Reports & Analytics</h2>
</div>

<!-- Sub-navigation for Reports -->
<ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist" style="border-bottom: 2px solid var(--border);">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="vol-tab" data-bs-toggle="tab" data-bs-target="#vol" type="button" role="tab" style="color:var(--text-primary); font-weight:500;"><i class="fas fa-chart-bar"></i> Test Volume</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="tat-tab" data-bs-toggle="tab" data-bs-target="#tat" type="button" role="tab" style="color:var(--text-primary); font-weight:500;"><i class="fas fa-clock"></i> Turnaround Time</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="crit-tab" data-bs-toggle="tab" data-bs-target="#crit" type="button" role="tab" style="color:var(--text-primary); font-weight:500;"><i class="fas fa-exclamation-circle text-danger"></i> Critical Values</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="inv-tab" data-bs-toggle="tab" data-bs-target="#inv" type="button" role="tab" style="color:var(--text-primary); font-weight:500;"><i class="fas fa-boxes"></i> Inventory Usage</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="audit-tab" data-bs-toggle="tab" data-bs-target="#audit" type="button" role="tab" style="color:var(--danger); font-weight:600;"><i class="fas fa-shield-alt"></i> Audit Trail</button>
  </li>
</ul>

<div class="tab-content" id="reportTabsContent">
    <!-- Test Volume -->
    <div class="tab-pane fade show active" id="vol" role="tabpanel">
        <div class="info-card">
            <h4 style="margin-bottom:1.5rem; color:var(--text-primary);">Test Volume (Last 14 Days)</h4>
            <div class="chart-wrap" style="height:350px;">
                <canvas id="volumeChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Turnaround Time -->
    <div class="tab-pane fade" id="tat" role="tabpanel">
        <div class="info-card">
            <h4 style="margin-bottom:1.5rem; color:var(--text-primary);">Average Turnaround Time (TAT) per Test Category</h4>
             <div class="chart-wrap" style="height:350px;">
                <canvas id="tatChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Critical Values Log -->
    <div class="tab-pane fade" id="crit" role="tabpanel">
        <div class="adm-table-wrap" style="background:var(--surface); padding:1.5rem; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
            <table class="adm-table display" id="critTable" style="width:100%;">
                <thead>
                    <tr><th>Date Flagged</th><th>Order ID</th><th>Test Name</th><th>Patient</th><th>Critical Value(s)</th><th>Logged By</th></tr>
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
                            // Extract JSON keys that have Critical flags
                            $crit_params = [];
                            $json = json_decode($cr['parameters_json'], true) ?: [];
                            foreach($json as $key => $data) {
                                if(isset($data['flag']) && stripos($data['flag'], 'Critical') !== false) {
                                    $crit_params[] = "{$data['name']} ({$data['value']})";
                                }
                            }
                            $crit_str = !empty($crit_params) ? implode(", ", $crit_params) : "Flagged manually";

                            echo "<tr>
                                    <td>".date('d M Y, h:i A', strtotime($cr['created_at']))."</td>
                                    <td><strong>#ORD-".str_pad($cr['order_id'],5,'0',STR_PAD_LEFT)."</strong></td>
                                    <td>".e($cr['test_name'])."</td>
                                    <td>".e($cr['pat_name'])."</td>
                                    <td style='color:var(--danger); font-weight:600;'>".e($crit_str)."</td>
                                    <td>".e($cr['tech_name'])."</td>
                                  </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Inventory Usage -->
    <div class="tab-pane fade" id="inv" role="tabpanel">
        <div class="adm-table-wrap" style="background:var(--surface); padding:1.5rem; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
            <h4 style="margin-bottom:1.5rem; color:var(--text-primary);">Recent Inventory Deductions</h4>
            <table class="table" style="color:var(--text-primary);">
                <thead><tr><th>Date</th><th>Item Name</th><th>Action</th><th>Quantity Changed</th><th>By Tech</th></tr></thead>
                <tbody>
                    <?php
                    $inv_q = mysqli_query($conn, "SELECT a.created_at, a.action_type, i.item_name, a.old_value, a.new_value, t.full_name 
                                                  FROM lab_audit_trail a
                                                  JOIN reagent_inventory i ON a.record_id_affected = i.id
                                                  LEFT JOIN lab_technicians t ON a.technician_id = t.user_id
                                                  WHERE a.module_affected = 'Reagent Inventory' AND a.action_type LIKE '%Deduction%'
                                                  ORDER BY a.created_at DESC LIMIT 50");
                    if ($inv_q && mysqli_num_rows($inv_q) > 0) {
                         while($ir = mysqli_fetch_assoc($inv_q)) {
                             echo "<tr>
                                     <td>".date('d M Y, H:i', strtotime($ir['created_at']))."</td>
                                     <td>".e($ir['item_name'])."</td>
                                     <td>".e($ir['action_type'])."</td>
                                     <td style='color:var(--warning);'><strong>".e($ir['new_value'])."</strong></td>
                                     <td>".e($ir['full_name'])."</td>
                                   </tr>";
                         }
                    } else {
                         echo "<tr><td colspan='5' class='text-center text-muted'>No recent inventory deductions found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Audit Trail -->
    <div class="tab-pane fade" id="audit" role="tabpanel">
         <div class="adm-table-wrap" style="background:var(--surface); padding:1.5rem; border:2px solid var(--danger); border-radius:var(--radius-md);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom: 1rem; border-bottom: 1px dashed var(--border);">
                <h4 style="color:var(--danger); margin:0;"><i class="fas fa-lock"></i> IMMUTABLE SECURITY LEDGER</h4>
                <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="exportAuditTrail()"><i class="fas fa-file-csv"></i> Export Audit Log</button>
            </div>
            <table class="adm-table display" id="auditTable" style="width:100%;">
                <thead>
                    <tr><th>Timestamp</th><th>Technician</th><th>Action</th><th>Module</th><th>Record ID</th><th>IP Address</th></tr>
                </thead>
                <tbody>
                    <?php
                    $aud_q = mysqli_query($conn, "SELECT a.*, l.full_name FROM lab_audit_trail a LEFT JOIN lab_technicians l ON a.technician_id = l.user_id ORDER BY a.created_at DESC LIMIT 300");
                    if($aud_q) {
                        while($ar = mysqli_fetch_assoc($aud_q)) {
                            // Determine style based on action
                            $act_color = 'var(--text-primary)';
                            if(stripos($ar['action_type'], 'Reject') !== false || stripos($ar['action_type'], 'Delete') !== false) $act_color = 'var(--danger)';
                            if(stripos($ar['action_type'], 'Release') !== false || stripos($ar['action_type'], 'Accept') !== false) $act_color = 'var(--success)';
                            if(stripos($ar['action_type'], 'Amend') !== false) $act_color = 'var(--warning)';

                            echo "<tr>
                                    <td style='font-size:0.9em;'>".date('y-m-d H:i:s', strtotime($ar['created_at']))."</td>
                                    <td>".($ar['full_name'] ? e($ar['full_name']) : "ID: ".$ar['technician_id'])."</td>
                                    <td style='color:$act_color; font-weight:600;'>".e($ar['action_type'])."</td>
                                    <td><span class='badge bg-secondary'>".e($ar['module_affected'])."</span></td>
                                    <td>#".e($ar['record_id_affected'])."</td>
                                    <td style='font-family:monospace; font-size:0.85em; color:var(--text-muted);'>".e($ar['ip_address'])."</td>
                                  </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#critTable').DataTable({ order: [[0, 'desc']], pageLength: 10, language: { search: "", searchPlaceholder: "Search criticals..." } });
    $('#auditTable').DataTable({ order: [[0, 'desc']], pageLength: 15, language: { search: "", searchPlaceholder: "Search audit logs..." } });
    
    // Auto-fetch chart data on tab activation
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if(e.target.id === 'vol-tab' || e.target.id === 'tat-tab') {
            fetchChartData();
        }
    });

    // Initial load for active tab
    fetchChartData();
});

function fetchChartData() {
    $.ajax({
        url: 'lab_actions.php',
        type: 'POST',
        data: { action: 'fetch_report_data', csrf_token: document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                renderVolumeChart(res.data.volume);
                renderTATChart(res.data.tat);
            } else {
                console.error("Failed to load chart data:", res.message);
            }
        },
        error: function(err) {
            console.error("AJAX Error fetching chart data", err);
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
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, suggestedMax: 5 } },
            plugins: { legend: { display: false } }
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
                borderColor: 'rgba(231, 76, 60, 1)',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                fill: true,
                tension: 0.3,
                pointBackgroundColor: 'rgba(231, 76, 60, 1)'
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, title: { display:true, text:'Hours' } } },
            plugins: { legend: { display: false } }
        }
    });
}

function exportAuditTrail() {
    // Generate secure export handler
    window.location.href = 'lab_exports.php?action=export_audit_trail&csrf=' + encodeURIComponent(document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
}
</script>
