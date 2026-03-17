<?php
/**
 * tab_analytics.php — Module 12: My Performance & Analytics
 */
// Task performance data (last 30 days)
$perf_data = dbRow($conn,
    "SELECT
        COUNT(*) AS total,
        SUM(status='completed') AS completed,
        SUM(status='overdue') AS overdues,
        SUM(status='in progress') AS in_progress,
        SUM(CASE WHEN status='completed' AND completed_at <= due_date THEN 1 ELSE 0 END) AS on_time
     FROM staff_tasks WHERE assigned_to=? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    "i", [$staff_id]);

$completion_rate = ($perf_data && $perf_data['total'] > 0)
    ? round(($perf_data['completed'] / $perf_data['total']) * 100)
    : 0;
$on_time_rate = ($perf_data && $perf_data['completed'] > 0)
    ? round(($perf_data['on_time'] / $perf_data['completed']) * 100)
    : 0;

// Leave usage
$leave_data = dbRow($conn,"SELECT COUNT(*) AS applied, SUM(status='approved') AS approved, SUM(status='rejected') AS rejected FROM staff_leaves WHERE staff_id=? AND YEAR(start_date)=YEAR(NOW())","i",[$staff_id]);

// Performance trend data per day (last 14 days)
$trend = dbSelect($conn,
    "SELECT DATE(completed_at) AS d, COUNT(*) AS cnt FROM staff_tasks
     WHERE assigned_to=? AND status='completed' AND completed_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
     GROUP BY DATE(completed_at) ORDER BY d ASC",
    "i", [$staff_id]);

$trend_labels = [];
$trend_values = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $lbl = date('M d', strtotime($d));
    $trend_labels[] = $lbl;
    $found = array_filter($trend, fn($t) => $t['d'] === $d);
    $trend_values[] = $found ? (int)array_values($found)[0]['cnt'] : 0;
}
// Profile completeness percentage — sourced from staff_dashboard.php $completeness variable
// which reads sc.overall_percentage from staff_profile_completeness table
$compl_pct = isset($completeness) ? (int)$completeness : 0;
?>
<div id="sec-analytics" class="dash-section">
    <h2 style="font-size:2.2rem;font-weight:700;margin-bottom:2.5rem;"><i class="fas fa-chart-bar" style="color:var(--role-accent);"></i> My Performance</h2>

    <!-- KPI Summary -->
    <div class="stat-grid" style="margin-bottom:2.5rem;">
        <?php
        $kpis = [
            ['val'=>$perf_data['total']??0,    'label'=>'Total Tasks (30d)',    'icon'=>'fa-clipboard-list', 'color'=>'var(--primary)'],
            ['val'=>$perf_data['completed']??0, 'label'=>'Tasks Completed',     'icon'=>'fa-check-circle',   'color'=>'var(--success)'],
            ['val'=>$completion_rate.'%',        'label'=>'Completion Rate',      'icon'=>'fa-percentage',     'color'=>'var(--role-accent)'],
            ['val'=>$perf_data['overdues']??0,  'label'=>'Overdue Tasks',        'icon'=>'fa-exclamation',    'color'=>'var(--danger)'],
            ['val'=>$on_time_rate.'%',           'label'=>'On-Time Rate',         'icon'=>'fa-clock',          'color'=>'var(--info)'],
            ['val'=>$leave_data['applied']??0,  'label'=>'Leave Requests (yr)',  'icon'=>'fa-umbrella-beach', 'color'=>'#8E44AD'],
        ];
        foreach($kpis as $k): ?>
        <div class="stat-mini">
            <div style="width:44px;height:44px;border-radius:12px;background:color-mix(in srgb,<?=$k['color']?> 15%,#fff 85%);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="fas <?=$k['icon']?>" style="font-size:1.8rem;color:<?=$k['color']?>;"></i>
            </div>
            <div class="stat-mini-val" style="color:<?=$k['color']?>;"><?=$k['val']?></div>
            <div class="stat-mini-lbl"><?=$k['label']?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">
        <!-- Task Completion Trend -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-chart-line"></i> Completion Trend (14 days)</h3></div>
            <div class="card-body"><canvas id="trendChart" height="200"></canvas></div>
        </div>

        <!-- Task Breakdown Doughnut -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-chart-pie"></i> Task Status Breakdown</h3></div>
            <div class="card-body"><canvas id="taskPieChart" height="200"></canvas></div>
        </div>
    </div>

    <!-- Progress Bars -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-star"></i> Performance Ratings</h3></div>
        <div class="card-body">
            <?php
            $perf_bars = [
                ['label'=>'Task Completion Rate','pct'=>$completion_rate,'color'=>'var(--success)'],
                ['label'=>'On-Time Delivery',    'pct'=>$on_time_rate,  'color'=>'var(--primary)'],
                ['label'=>'Profile Completeness','pct'=>$compl_pct,     'color'=>'var(--role-accent)'],
                ['label'=>'Leave Balance',       'pct'=>min(100,100-min(($leave_data['applied']??0)*10,100)), 'color'=>'var(--warning)'],
            ];
            foreach($perf_bars as $pb): ?>
            <div style="margin-bottom:1.8rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:.6rem;font-size:1.3rem;font-weight:500;">
                    <span><?=$pb['label']?></span>
                    <span style="color:<?=$pb['color']?>;font-weight:700;"><?=$pb['pct']?>%</span>
                </div>
                <div style="height:10px;background:var(--border);border-radius:5px;overflow:hidden;">
                    <div style="height:100%;width:<?=$pb['pct']?>%;background:<?=$pb['color']?>;border-radius:5px;transition:width .8s ease;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
(function(){
    const accent = getComputedStyle(document.documentElement).getPropertyValue('--role-accent').trim() || '#4F46E5';
    const isDark = document.documentElement.getAttribute('data-theme')==='dark';
    const gridColor = isDark ? 'rgba(255,255,255,.1)' : 'rgba(0,0,0,.08)';
    const textColor = isDark ? '#a0a0a0' : '#555';
    const defaults = { color:textColor, font:{family:'Poppins',size:11} };

    // Trend chart
    new Chart(document.getElementById('trendChart'), {
        type:'line',
        data:{
            labels:<?=json_encode($trend_labels)?>,
            datasets:[{
                label:'Tasks Completed',
                data:<?=json_encode($trend_values)?>,
                borderColor:accent, backgroundColor:accent+'22',
                tension:.4, fill:true, pointRadius:4, pointHoverRadius:7,
                borderWidth:2.5
            }]
        },
        options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false}},
                 scales:{x:{grid:{color:gridColor},ticks:defaults},y:{grid:{color:gridColor},ticks:{...defaults,stepSize:1,precision:0},beginAtZero:true}}}
    });

    // Pie chart
    const pf = <?=json_encode($perf_data)?>;
    const pd = [pf?pf.completed:0, pf?pf.in_progress:0, pf?pf.overdues:0];
    new Chart(document.getElementById('taskPieChart'), {
        type:'doughnut',
        data:{
            labels:['Completed','In Progress','Overdue'],
            datasets:[{data:pd, backgroundColor:[getComputedStyle(document.documentElement).getPropertyValue('--success').trim()||'#27AE60','#4F46E5','#E74C3C'], borderWidth:0, hoverOffset:8}]
        },
        options:{responsive:true,maintainAspectRatio:true,cutout:'70%',
                 plugins:{legend:{position:'bottom',labels:{...defaults,padding:16}}}}
    });
})();
</script>