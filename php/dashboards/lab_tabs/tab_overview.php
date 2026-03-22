<?php
// ============================================================
// LAB DASHBOARD - TAB OVERVIEW
// ============================================================
if (!isset($user_id)) { exit; } // Prevent direct access

// Initial DB pull for the Hero section and Quick Stats
$today = date('Y-m-d');

// Critical Results Flagged Today Details
$crit_query = mysqli_query($conn, "SELECT r.result_id, t.test_name, p.full_name, r.created_at 
    FROM lab_results r 
    JOIN lab_test_orders o ON r.order_id = o.id
    JOIN patients p ON o.patient_id = p.id
    WHERE r.result_interpretation = 'Critical' AND DATE(r.created_at) = '$today'
    ORDER BY r.created_at DESC LIMIT 5");

$critical_alerts = [];
while($row = mysqli_fetch_assoc($crit_query)) {
    $critical_alerts[] = $row;
}

// Recent Activity Feed
$act_query = mysqli_query($conn, "SELECT action_type, module_affected, timestamp 
    FROM lab_audit_trail 
    WHERE technician_id = $tech_pk 
    ORDER BY timestamp DESC LIMIT 6");

$activities = [];
while($row = mysqli_fetch_assoc($act_query)) {
    $activities[] = $row;
}
?>

<!-- Hero Banner -->
<div class="staff-hero">
    <div class="staff-hero-avatar">
        <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $profile_image_path ?>" 
             alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;"
             onerror="this.src='/RMU-Medical-Management-System/image/default-avatar.png'">
    </div>
    <div class="staff-hero-info">
        <h2>Welcome back, <?= e($techName) ?>!</h2>
        <p><?= e($tech_row['designation']) ?> | <?= e($tech_row['specialization']) ?> Department</p>
        <div style="margin-top: .8rem;">
            <span class="hero-badge"><i class="fas fa-id-badge"></i> <?= e($tech_row['technician_id']) ?></span>
            <span class="hero-badge"><i class="far fa-calendar-alt"></i> <?= date('l, d M Y') ?></span>
        </div>
    </div>
</div>

<!-- 7 Summary Cards (Auto-refreshing via AJAX) -->
<div class="adm-summary-strip" id="stats-container">
    <div class="adm-mini-card" onclick="window.location.href='?tab=orders&filter=pending'">
        <div class="adm-mini-card-num orange" id="stat-pending">0</div>
        <div class="adm-mini-card-label">Pending Orders</div>
    </div>
    <div class="adm-mini-card" onclick="window.location.href='?tab=samples&filter=awaiting'">
        <div class="adm-mini-card-num blue" id="stat-samples">0</div>
        <div class="adm-mini-card-label">Awaiting Samples</div>
    </div>
    <div class="adm-mini-card" onclick="window.location.href='?tab=orders&filter=processing'">
        <div class="adm-mini-card-num teal" id="stat-processing">0</div>
        <div class="adm-mini-card-label">Tests Processing</div>
    </div>
    <div class="adm-mini-card" onclick="window.location.href='?tab=results&filter=validation'">
        <div class="adm-mini-card-num orange" id="stat-validation">0</div>
        <div class="adm-mini-card-label">Awaiting Validation</div>
    </div>
    <div class="adm-mini-card" onclick="window.location.href='?tab=results&filter=critical'">
        <div class="adm-mini-card-num red" id="stat-critical">0</div>
        <div class="adm-mini-card-label">Critical Today</div>
    </div>
    <div class="adm-mini-card" onclick="window.location.href='?tab=equipment&filter=calibration'">
        <div class="adm-mini-card-num orange" id="stat-calibration">0</div>
        <div class="adm-mini-card-label">Calibration Due</div>
    </div>
    <div class="adm-mini-card" onclick="window.location.href='?tab=inventory&filter=low'">
        <div class="adm-mini-card-num red" id="stat-reagent">0</div>
        <div class="adm-mini-card-label">Low Reagent Alerts</div>
    </div>
</div>

<!-- Main Layout Grid -->
<div class="charts-grid" style="grid-template-columns: 2fr 1fr;">
    
    <!-- Left Column: Quick Actions & Analytics -->
    <div>
        <div class="sec-header" style="margin-bottom: 1rem;">
            <h2 style="font-size: 1.5rem;"><i class="fas fa-bolt"></i> Quick Actions</h2>
        </div>
        <div class="cards-grid" style="grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
            <button class="adm-btn adm-btn-primary" onclick="window.location.href='?tab=orders'"><i class="fas fa-check-circle"></i> Accept Order</button>
            <button class="adm-btn adm-btn-primary" onclick="window.location.href='?tab=samples'"><i class="fas fa-vial"></i> Record Sample</button>
            <button class="adm-btn adm-btn-success" onclick="window.location.href='?tab=results'"><i class="fas fa-edit"></i> Enter Results</button>
            <button class="adm-btn adm-btn-primary" onclick="window.location.href='?tab=reports'"><i class="fas fa-file-pdf"></i> Gen. Report</button>
        </div>

        <div class="sec-header">
            <h2 style="font-size: 1.5rem;"><i class="fas fa-chart-line"></i> Mini Analytics</h2>
        </div>
        <div class="info-card">
            <div class="chart-wrap" style="height: 200px;">
                <canvas id="labVolumeChart"></canvas>
            </div>
        </div>
        <div class="info-card mt-3" style="border-left: 4px solid var(--warning);">
            <h3 style="color:var(--warning); font-size: 1.2rem; margin-bottom: 1rem;"><i class="fas fa-bell"></i> Overdue & Escalated Tests</h3>
            <div id="escalated-tests-container">
                <div style="text-align:center; padding:1rem; color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Polling Escalations...</div>
            </div>
        </div>
    </div>

    <!-- Right Column: Critical Alerts & Activity -->
    <div>
        <!-- Critical Results Panel -->
        <div class="info-card" style="border-left: 4px solid var(--danger); margin-bottom: 1.5rem;">
            <h3 style="color: var(--danger); font-size: 1.2rem; margin-bottom: 1rem;"><i class="fas fa-exclamation-triangle"></i> Critical Results Today</h3>
            <?php if(empty($critical_alerts)): ?>
                <p style="color: var(--success); font-weight: 500;"><i class="fas fa-check-circle"></i> No critical results flagged today.</p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach($critical_alerts as $alert): ?>
                        <li style="margin-bottom: .8rem; padding-bottom: .8rem; border-bottom: 1px dashed var(--border);">
                            <div style="font-weight: 600; color: var(--text-primary);"><?= e($alert['test_name']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">Patient: <?= e($alert['full_name']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--danger);"><i class="far fa-clock"></i> <?= date('h:i A', strtotime($alert['created_at'])) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Activity Feed -->
        <div class="info-card">
            <h3 style="font-size: 1.2rem; margin-bottom: 1rem;"><i class="fas fa-history"></i> Recent Activity</h3>
            <?php if(empty($activities)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">No recent activities logged.</p>
            <?php else: ?>
                <?php foreach($activities as $act): ?>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div>
                            <div style="font-weight: 500; font-size: 0.95rem; color: var(--text-primary);"><?= e($act['action_type']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= e($act['module_affected']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="far fa-clock"></i> <?= date('d M, h:i A', strtotime($act['timestamp'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Real-time Polling & Charts Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // AJAX Poller for Live Dashboard Stats
    function fetchDashboardStats() {
        $.ajax({
            url: 'lab_actions.php',
            type: 'POST',
            data: {
                action: 'fetch_dashboard_stats',
                csrf_token: '<?= $csrf_token ?>'
            },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#stat-pending').text(res.stats.pending_orders);
                    $('#stat-samples').text(res.stats.samples_awaiting);
                    $('#stat-processing').text(res.stats.processing_tests);
                    $('#stat-validation').text(res.stats.results_awaiting_val);
                    $('#stat-critical').text(res.stats.critical_flagged);
                    $('#stat-calibration').text(res.stats.equip_calibration);
                    $('#stat-reagent').text(res.stats.low_reagent);
                    
                    // Inject Escalated (Overdue) Tasks
                    let escHtml = '';
                    if(res.stats.escalated && res.stats.escalated.length > 0) {
                        res.stats.escalated.forEach(function(ev) {
                            escHtml += `
                                <div style="margin-bottom:0.8rem; padding-bottom:0.8rem; border-bottom:1px dashed var(--border);">
                                    <div style="display:flex; justify-content:space-between;">
                                        <span style="font-weight:600; color:var(--text-primary);">${ev.test_name}</span>
                                        <span class="adm-badge" style="background:var(--warning); color:white; font-size:0.75rem;">+${ev.hours_overdue}h Overdue</span>
                                    </div>
                                    <div style="font-size:0.85rem; color:var(--text-secondary);">Pt: ${ev.patient_name} &bull; ORD-${ev.order_id.toString().padStart(5,'0')}</div>
                                </div>
                            `;
                        });
                    } else {
                        escHtml = `<p style="color:var(--success); font-weight:500;"><i class="fas fa-check-circle"></i> All tests are well within Turnaround Time (TAT).</p>`;
                    }
                    $('#escalated-tests-container').html(escHtml);
                }
            }
        });
    }

    // Initial fetch and set interval for every 30 seconds
    fetchDashboardStats();
    setInterval(fetchDashboardStats, 30000);

    // Mini Analytics Charts (Mock Data until full backend logic is wired for Analytics Module)
    // Test Volume Chart
    const ctxVol = document.getElementById('labVolumeChart').getContext('2d');
    new Chart(ctxVol, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Tests Ordered',
                data: [12, 19, 15, 22, 18, 10, 8],
                backgroundColor: 'rgba(13, 148, 136, 0.7)',
                borderColor: '#0d9488',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: { display: true, text: 'Test Volume (Last 7 Days)' }
            }
        }
    });

        }
    });
});
</script>
