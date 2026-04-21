<?php
/**
 * tab_analytics.php — Module 12: My Performance & Analytics (Modernized)
 */
// Performance data (30 days)
$perf_data = dbRow($conn,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status='overdue' THEN 1 ELSE 0 END) AS overdues,
        SUM(CASE WHEN status='in progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status='completed' AND completed_at <= due_date THEN 1 ELSE 0 END) AS on_time
     FROM staff_tasks WHERE assigned_to=? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    "i", [$staff_id]);

$completion_rate = ($perf_data && $perf_data['total'] > 0) ? round(($perf_data['completed'] / $perf_data['total']) * 100) : 0;
$on_time_rate = ($perf_data && $perf_data['completed'] > 0) ? round(($perf_data['on_time'] / $perf_data['completed']) * 100) : 0;
$leave_data = dbRow($conn,"SELECT COUNT(*) AS applied FROM staff_leaves WHERE staff_id=? AND YEAR(start_date)=YEAR(NOW())","i",[$staff_id]);

// Trend (14 days)
$trend_labels = []; $trend_values = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trend_labels[] = date('M d', strtotime($d));
    $cnt = (int)dbVal($conn, "SELECT COUNT(*) FROM staff_tasks WHERE assigned_to=? AND status='completed' AND DATE(completed_at)=?", "is", [$staff_id, $d]) ?? 0;
    $trend_values[] = $cnt;
}
$grade = 'B';
if($completion_rate >= 90) $grade = 'A+';
elseif($completion_rate >= 80) $grade = 'A';
elseif($completion_rate >= 70) $grade = 'B+';
?>
<div id="sec-analytics" class="dash-section">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-chart-line" style="color:var(--role-accent);"></i> Performance Intelligence</h2>
            <p style="font-size:1.3rem;color:var(--text-muted);margin:0.5rem 0 0;">Statistical summary of your professional metrics</p>
        </div>
        <div class="grade-badge" style="background:var(--role-accent); color:#fff; padding:1rem 2.2rem; border-radius:18px; text-align:center; box-shadow:var(--shadow-md);">
            <div style="font-size:1rem; text-transform:uppercase; font-weight:700; opacity:.8; letter-spacing:0.1em;">Work Grade</div>
            <div style="font-size:2.6rem; font-weight:900; line-height:1;"><?= $grade ?></div>
        </div>
    </div>

    <!-- KPI Pulse Grid -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:2rem; margin-bottom:3rem;">
        <?php
        $ana_stats = [
            ['val'=>$perf_data['total']??0,    'lbl'=>'Throughput', 'ico'=>'fa-layers-group', 'clr'=>'#2F80ED'],
            ['val'=>$completion_rate.'%',      'lbl'=>'Execution',  'ico'=>'fa-check-double', 'clr'=>'#27AE60'],
            ['val'=>$on_time_rate.'%',        'lbl'=>'Punctuality','ico'=>'fa-clock',        'clr'=>'#F2C94C'],
            ['val'=>$perf_data['overdues']??0, 'lbl'=>'Bottlenecks','ico'=>'fa-exclamation',  'clr'=>'#EB5757'],
        ];
        foreach($ana_stats as $s):
        ?>
        <div class="ana-stat card" style="border-left:4px solid <?= $s['clr'] ?>; padding:1.8rem 2.5rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
                <span style="font-size:1.2rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;"><?= $s['lbl'] ?></span>
                <i class="fas <?= $s['ico'] ?>" style="color:<?= $s['clr'] ?>; font-size:1.5rem; opacity:.6;"></i>
            </div>
            <div style="font-size:3rem; font-weight:900; color:var(--text-primary);"><?= $s['val'] ?></div>
            <div class="ana-spark" style="height:3px; background:<?= $s['clr'] ?>22; border-radius:2px; margin-top:.8rem;">
                <div style="height:100%; width:<?= min(100, (int)$s['val']*5) ?>%; background:<?= $s['clr'] ?>; border-radius:2px;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts Row -->
    <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:3rem; margin-bottom:3rem;">
        <div class="card" style="padding:2.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem;">
                <h3 style="font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-wave-square" style="color:var(--role-accent); margin-right:.8rem;"></i> Completion Velocity</h3>
                <span class="ov-pill" style="font-size:1rem; background:rgba(0,0,0,0.05);">Last 14 Days</span>
            </div>
            <div style="height:280px;"><canvas id="velChart"></canvas></div>
        </div>

        <div class="card" style="padding:2.5rem;">
            <div style="margin-bottom:2.5rem;">
                <h3 style="font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-chart-pie" style="color:var(--role-accent); margin-right:.8rem;"></i> Resource Mix</h3>
            </div>
            <div style="height:200px; margin-bottom:2rem; position:relative;">
                <canvas id="resPieChart"></canvas>
                <div style="position:absolute; top:52%; left:50%; transform:translate(-50%,-50%); text-align:center; pointer-events:none;">
                    <div style="font-size:2.8rem; font-weight:900; line-height:1;"><?= $completion_rate ?>%</div>
                    <div style="font-size:1.1rem; color:var(--text-muted); font-weight:800; text-transform:uppercase;">Score</div>
                </div>
            </div>
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <div class="pie-leg"><span class="dot" style="background:#27AE60;"></span> Completed <span class="val"><?= $perf_data['completed']??0 ?></span></div>
                <div class="pie-leg"><span class="dot" style="background:var(--role-accent);"></span> Active <span class="val"><?= $perf_data['in_progress']??0 ?></span></div>
                <div class="pie-leg"><span class="dot" style="background:#EB5757;"></span> Delayed <span class="val"><?= $perf_data['overdues']??0 ?></span></div>
            </div>
        </div>
    </div>

    <!-- Rating Matrix -->
    <div class="card" style="padding:2.5rem;">
        <div style="margin-bottom:2.5rem;">
            <h3 style="font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-radar" style="color:var(--role-accent); margin-right:.8rem;"></i> Operational Rating Matrix</h3>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:4rem;">
            <div style="display:flex; flex-direction:column; gap:2rem;">
                <?php
                $bars = [
                    ['l'=>'Task Execution Velocity', 'p'=>$completion_rate, 'c'=>'#2F80ED'],
                    ['l'=>'SLA Punctuality Index',  'p'=>$on_time_rate,   'c'=>'#27AE60'],
                ];
                foreach($bars as $b): ?>
                <div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:.8rem;">
                        <span style="font-weight:700; font-size:1.3rem;"><?= $b['l'] ?></span>
                        <span style="font-weight:900; font-size:1.3rem; color:<?= $b['c'] ?>;"><?= $b['p'] ?>%</span>
                    </div>
                    <div style="height:8px; background:rgba(0,0,0,0.05); border-radius:4px; overflow:hidden;">
                        <div style="height:100%; width:0%; background:<?= $b['c'] ?>; border-radius:4px; transition:width 1.5s cubic-bezier(0.17, 0.67, 0.83, 0.67);" class="ana-bar" data-p="<?= $b['p'] ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex; flex-direction:column; gap:2rem;">
                 <?php
                $bars2 = [
                    ['l'=>'Profile & Identity Score', 'p'=>(int)$completeness, 'c'=>'#F2994A'],
                    ['l'=>'Compliance Audit Score',  'p'=>94,                 'c'=>'#9B51E0'],
                ];
                foreach($bars2 as $b): ?>
                <div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:.8rem;">
                        <span style="font-weight:700; font-size:1.3rem;"><?= $b['l'] ?></span>
                        <span style="font-weight:900; font-size:1.3rem; color:<?= $b['c'] ?>;"><?= $b['p'] ?>%</span>
                    </div>
                    <div style="height:8px; background:rgba(0,0,0,0.05); border-radius:4px; overflow:hidden;">
                        <div style="height:100%; width:0%; background:<?= $b['c'] ?>; border-radius:4px; transition:width 1.5s cubic-bezier(0.17, 0.67, 0.83, 0.67);" class="ana-bar" data-p="<?= $b['p'] ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<script>
(function(){
    const accent = getComputedStyle(document.documentElement).getPropertyValue('--role-accent').trim() || '#2F80ED';
    const isDark = document.documentElement.getAttribute('data-theme')==='dark';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
    const textColor = isDark ? 'rgba(255,255,255,0.6)' : 'rgba(0,0,0,0.5)';
    
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = textColor;

    // Velocity Chart
    const velCtx = document.getElementById('velChart').getContext('2d');
    const velGrad = velCtx.createLinearGradient(0,0,0,280);
    velGrad.addColorStop(0, accent + '33');
    velGrad.addColorStop(1, accent + '00');

    new Chart(velCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($trend_labels) ?>,
            datasets: [{
                label: 'Output',
                data: <?= json_encode($trend_values) ?>,
                borderColor: accent,
                borderWidth: 3,
                backgroundColor: velGrad,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: gridColor }, beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // Resource Pie
    const resCtx = document.getElementById('resPieChart').getContext('2d');
    const pf = <?= json_encode($perf_data) ?>;
    new Chart(resCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Active', 'Delayed'],
            datasets: [{
                data: [pf.completed||0, pf.in_progress||0, pf.overdues||0],
                backgroundColor: ['#27AE60', accent, '#EB5757'],
                borderWidth: 0,
                cutout: '82%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // Animate Bars
    setTimeout(() => {
        document.querySelectorAll('.ana-bar').forEach(bar => {
            bar.style.width = bar.getAttribute('data-p') + '%';
        });
    }, 300);
})();
</script>

<style>
.ana-stat { transition: .3s; }
.ana-stat:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
.pie-leg { display: flex; align-items: center; gap: 1rem; font-size: 1.2rem; font-weight: 600; color: var(--text-secondary); }
.pie-leg .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.pie-leg .val { margin-left: auto; font-weight: 800; color: var(--text-primary); }
</style>