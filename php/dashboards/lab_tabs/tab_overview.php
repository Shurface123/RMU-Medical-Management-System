<?php
// ============================================================
// LAB DASHBOARD - TAB OVERVIEW
// ============================================================
if (!isset($user_id)) { exit; } // Prevent direct access

// Initial DB pull for the Hero section and Quick Stats
$today = date('Y-m-d');

// Critical Results Flagged Today Details
$crit_query = mysqli_query($conn, "SELECT r.result_id, c.test_name, p.full_name, r.created_at 
    FROM lab_results r 
    JOIN lab_test_orders o ON r.order_id = o.id
    JOIN lab_test_catalog c ON o.test_catalog_id = c.id
    JOIN patients p ON o.patient_id = p.id
    WHERE r.result_interpretation = 'Critical' AND DATE(r.created_at) = '$today'
    ORDER BY r.created_at DESC LIMIT 5");

$critical_alerts = [];
while($row = mysqli_fetch_assoc($crit_query)) {
    $critical_alerts[] = $row;
}

// Recent Activity Feed
$act_query = mysqli_query($conn, "SELECT action_type, module_affected, created_at 
    FROM lab_audit_trail 
    WHERE technician_id = $tech_pk 
    ORDER BY created_at DESC LIMIT 6");

$activities = [];
while($row = mysqli_fetch_assoc($act_query)) {
    $activities[] = $row;
}
?>

<!-- Hero Banner -->
<div class="staff-hero">
    <div class="staff-hero-avatar">
        <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $profile_image_path ?>" 
             alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);"
             onerror="this.src='/RMU-Medical-Management-System/image/default-avatar.png'">
    </div>
    <div class="staff-hero-info">
        <h2 style="font-size: 2.2rem; margin-bottom: 0.5rem; letter-spacing: -0.02em;">Welcome back, <?= e($techName) ?>!</h2>
        <p style="font-size: 1.1rem; opacity: 0.9;"><?= e($tech_row['designation']) ?> | <?= e($tech_row['specialization']) ?> Department</p>
        <div style="margin-top: 1rem; display: flex; gap: 0.8rem; flex-wrap: wrap;">
            <span class="hero-badge"><i class="fas fa-id-badge"></i> <?= e($tech_row['technician_id']) ?></span>
            <span class="hero-badge"><i class="far fa-calendar-alt"></i> <?= date('l, d M Y') ?></span>
        </div>
    </div>
</div>

<!-- 7 Summary Cards (Auto-refreshing via AJAX) -->
<div class="adm-summary-strip" id="stats-container">
    <div class="adm-mini-card" onclick="window.location.href='?tab=orders&filter_status=Pending'">
        <div class="adm-mini-card-num orange" id="stat-pending">0</div>
        <div class="adm-mini-card-label">Pending Orders</div>
    </div>
    <div class="adm-mini-card" onclick="window.location.href='?tab=samples&filter_status=Collected'">
        <div class="adm-mini-card-num blue" id="stat-samples">0</div>
        <div class="adm-mini-card-label">Awaiting Samples</div>
    </div>
    <div class="adm-mini-card" onclick="window.location.href='?tab=orders&filter_status=Processing'">
        <div class="adm-mini-card-num teal" id="stat-processing">0</div>
        <div class="adm-mini-card-label">Tests Processing</div>
    </div>
    <div class="adm-mini-card" onclick="window.location.href='?tab=results&filter_status=Pending Validation'">
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
<div class="charts-grid" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
    
    <!-- Left Column: Quick Actions & Analytics -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <div>
            <div class="sec-header" style="margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.6rem; font-weight: 700;"><i class="fas fa-bolt"></i> Quick Lab Actions</h2>
            </div>
            <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin-bottom: 0;">
                <button class="adm-btn adm-btn-primary" onclick="window.location.href='?tab=orders'" style="padding: 1.2rem; border-radius: 12px;"><i class="fas fa-check-circle"></i> Accept Order</button>
                <button class="adm-btn adm-btn-primary" onclick="window.location.href='?tab=samples'" style="padding: 1.2rem; border-radius: 12px;"><i class="fas fa-vial"></i> Record Sample</button>
                <button class="adm-btn adm-btn-success" onclick="window.location.href='?tab=results'" style="padding: 1.2rem; border-radius: 12px;"><i class="fas fa-edit"></i> Enter Results</button>
                <button class="adm-btn adm-btn-primary" onclick="window.location.href='?tab=reports'" style="padding: 1.2rem; border-radius: 12px;"><i class="fas fa-file-pdf"></i> Gen. Report</button>
            </div>
        </div>

        <div>
            <div class="sec-header" style="margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.6rem; font-weight: 700;"><i class="fas fa-chart-line"></i> Performance Analytics</h2>
            </div>
            <div class="info-card" style="padding: 2rem;">
                <div class="chart-wrap" style="height: 240px;">
                    <canvas id="labVolumeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="info-card" style="border-left: 5px solid var(--warning); padding: 2rem; background: var(--surface);">
            <h3 style="color:var(--warning); font-size: 1.35rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                <i class="fas fa-bell"></i> Overdue & Escalated Tests
            </h3>
            <div id="escalated-tests-container">
                <div style="text-align:center; padding:2rem; color:var(--text-muted); font-size: 1.1rem;"><i class="fas fa-spinner fa-spin"></i> Polling Escalations...</div>
            </div>
        </div>
    </div>

    <!-- Right Column: Critical Alerts & Activity -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- Critical Results Panel -->
        <div class="info-card" style="border-left: 5px solid var(--danger); padding: 2rem;">
            <h3 style="color: var(--danger); font-size: 1.35rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                <i class="fas fa-exclamation-triangle"></i> Critical Alerts (Today)
            </h3>
            <?php if(empty($critical_alerts)): ?>
                <div style="display: flex; align-items: center; gap: 1rem; color: var(--success); font-weight: 600; font-size: 1.1rem; padding: 1rem; background: var(--success-light); border-radius: 8px;">
                    <i class="fas fa-check-circle" style="font-size: 1.4rem;"></i> No critical flags detected.
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach($critical_alerts as $alert): ?>
                        <div style="padding: 1.2rem; border-radius: 10px; background: var(--surface-2); border: 1px solid var(--border); transition: var(--transition); cursor: pointer;" onclick="window.location.href='?tab=results&id=<?= $alert['result_id'] ?>'">
                            <div style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem; margin-bottom: 0.2rem;"><?= e($alert['test_name']) ?></div>
                            <div style="font-size: 0.95rem; color: var(--text-secondary);"><?= e($alert['full_name']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--danger); font-weight: 600; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                                <i class="far fa-clock"></i> <?= date('h:i A', strtotime($alert['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Activity Feed -->
        <div class="info-card" style="padding: 2rem;">
            <h3 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                <i class="fas fa-history"></i> Recent Activity
            </h3>
            <?php if(empty($activities)): ?>
                <p style="color: var(--text-muted); font-size: 1rem; text-align: center; padding: 2rem;">No recent activities logged.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column;">
                    <?php foreach($activities as $act): ?>
                        <div class="activity-item">
                            <div class="activity-dot"></div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 1.05rem; color: var(--text-primary); margin-bottom: 0.2rem;"><?= e($act['action_type']) ?></div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); opacity: 0.8;"><?= e($act['module_affected']) ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.4rem;">
                                    <i class="far fa-clock" style="margin-right: 0.3rem;"></i> <?= date('d M, h:i A', strtotime($act['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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

    // Mini Analytics Charts (Mock Data)
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
});
</script>
