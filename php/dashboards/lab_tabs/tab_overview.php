<?php
// ============================================================
// LAB DASHBOARD - TAB OVERVIEW (PREMIUM UI REWRITE)
// ============================================================
if (!isset($user_id)) { exit; }

$today = date('Y-m-d');

// Quick Stats Pull (initial load, before AJAX takes over if desired)
// Or we just rely on PHP variables where available. $unread_notifs is already defined.

// Critical Results Flagged Today
$critical_alerts = [];
$crit_query = mysqli_query($conn, "SELECT r.result_id, c.test_name, p.full_name, r.created_at 
    FROM lab_results r 
    JOIN lab_test_orders o ON r.order_id = o.id
    JOIN lab_test_catalog c ON o.test_catalog_id = c.id
    JOIN patients p ON o.patient_id = p.id
    WHERE r.result_interpretation = 'Critical' AND DATE(r.created_at) = '$today'
    ORDER BY r.created_at DESC LIMIT 5");
if ($crit_query) while($row = mysqli_fetch_assoc($crit_query)) $critical_alerts[] = $row;

// Recent Activity Feed
$activities = [];
$act_query = mysqli_query($conn, "SELECT action_type, module_affected, created_at 
    FROM lab_audit_trail WHERE technician_id = $tech_pk ORDER BY created_at DESC LIMIT 6");
if ($act_query) while($row = mysqli_fetch_assoc($act_query)) $activities[] = $row;
?>

<div class="tab-content active" id="overview">

<style>
/* ── Premium Hero Banner ── */
.lab-hero-v2 {
  position:relative;overflow:hidden;border-radius:20px;margin-bottom:2rem;
  background:linear-gradient(135deg,#003140 0%,#095d52 50%,#0d9488 100%);
  padding:2.5rem 3rem;display:flex;align-items:center;gap:2rem;flex-wrap:wrap;
  box-shadow:0 20px 60px rgba(13,148,136,.3);
}
.lab-hero-v2::before {
  content:'';position:absolute;bottom:-30%;right:-10%;width:350px;height:350px;
  background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);border-radius:50%;
}
.lab-hero-v2::after {
  content:'';position:absolute;top:-20%;left:25%;width:250px;height:250px;
  background:radial-gradient(circle,rgba(20,184,166,.15) 0%,transparent 70%);border-radius:50%;
}
.lab-hero-avatar-v2 {
  width:80px;height:80px;border-radius:50%;border:4px solid rgba(255,255,255,.4);
  background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;
  font-size:3rem;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden;
  backdrop-filter:blur(10px);position:relative;z-index:1; box-shadow:0 10px 25px rgba(0,0,0,0.2);
}
.lab-hero-info-v2 { flex:1;position:relative;z-index:1; }
.lab-hero-info-v2 h2 { font-size:2.2rem;font-weight:800;color:#fff;margin:0 0 .3rem; letter-spacing:-0.5px; }
.lab-hero-info-v2 p  { font-size:1.25rem;color:rgba(255,255,255,.8);margin:0 0 .8rem; font-weight:500; }
.lab-hero-chips { display:flex;gap:.7rem;flex-wrap:wrap; }
.lab-chip {
  display:inline-flex;align-items:center;gap:.5rem;padding:.35rem 1rem;
  border-radius:20px;font-size:1.1rem;font-weight:600;
  background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);
  backdrop-filter:blur(5px); transition:all 0.3s ease;
}
.lab-chip:hover { background:rgba(255,255,255,0.25); box-shadow:0 4px 10px rgba(0,0,0,0.1); }
.lab-chip.valid   { background:rgba(20,184,166,.3);border-color:rgba(20,184,166,.5); }
.lab-chip.danger  { background:rgba(244,63,94,.3);border-color:rgba(244,63,94,.5); }
.lab-chip.warning { background:rgba(245,158,11,.3);border-color:rgba(245,158,11,.5); }

/* ── Premium Stat Cards ── */
.lab-stat-grid {
  display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem;
}
.lab-stat-card {
  background:var(--surface);border:1px solid var(--border);border-radius:16px;
  padding:2rem 1.8rem;position:relative;overflow:hidden;cursor:pointer;
  transition:all .3s cubic-bezier(.4,0,.2,1);box-shadow:0 4px 15px rgba(0,0,0,.04);
}
.lab-stat-card::before {
  content:'';position:absolute;top:0;left:0;right:0;height:5px;
  border-radius:16px 16px 0 0;background:var(--card-accent,var(--role-accent));
}
.lab-stat-card:hover { transform:translateY(-5px);box-shadow:0 15px 35px rgba(0,0,0,.1); border-color:var(--card-accent,var(--role-accent)); }
.lab-stat-card .sc-icon {
  width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;
  font-size:1.8rem;margin-bottom:1.2rem;
}
.lab-stat-card .sc-val { font-size:3rem;font-weight:800;line-height:1;color:var(--text-primary);margin-bottom:.5rem; letter-spacing:-1px; }
.lab-stat-card .sc-lbl { font-size:1.2rem;font-weight:600;color:var(--text-secondary); }

/* ── Alert Banner ── */
.lab-alert-banner {
  background:linear-gradient(135deg,rgba(244,63,94,.08),rgba(245,158,11,.05));
  border:1px solid rgba(244,63,94,.25);border-left:5px solid var(--danger);
  border-radius:12px;padding:1.4rem 2rem;margin-bottom:2rem;
  display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap; box-shadow:0 5px 15px rgba(244,63,94,0.05);
}

/* ── Quick Action Buttons ── */
.lab-quick-grid {
  display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.2rem;margin-bottom:2.5rem;
}
.lab-quick-btn {
  display:flex;flex-direction:column;align-items:center;gap:.8rem;padding:2rem 1rem;
  border-radius:16px;border:1px solid var(--border);background:var(--surface);
  cursor:pointer;transition:all .3s ease;text-decoration:none;
}
.lab-quick-btn:hover { border-color:var(--role-accent);box-shadow:0 8px 25px rgba(13,148,136,.15);transform:translateY(-4px); }
.lab-quick-btn .qb-icon {
  width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;
  transition:all 0.3s ease;
}
.lab-quick-btn:hover .qb-icon { transform:scale(1.1); }
.lab-quick-btn .qb-label { font-size:1.2rem;font-weight:700;color:var(--text-primary);text-align:center; }

/* ── Charts Grid ── */
.lab-charts-grid {
  display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2.5rem;
}
@media(max-width:1024px){.lab-charts-grid{grid-template-columns:1fr;}}
.lab-chart-card {
  background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:2.2rem;
  box-shadow:0 4px 15px rgba(0,0,0,.03);
}
.lab-chart-card h4 {
  font-size:1.4rem;font-weight:800;color:var(--text-primary);margin:0 0 1.5rem;
  display:flex;align-items:center;gap:.8rem;
}
.lab-chart-card .chart-wrap { height:260px;position:relative; width:100%; }

/* ── Activity Feed ── */
.activity-feed-item {
  display:flex;align-items:flex-start;gap:1.2rem;padding:1.2rem 0;border-bottom:1px solid var(--border);
  animation:fadeIn .4s ease;
}
.activity-feed-item:last-child { border-bottom:none; }
.af-dot {
  width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;
  font-size:1.4rem;flex-shrink:0; box-shadow:0 4px 10px rgba(0,0,0,-0.05);
}
.af-dot.teal   { background:rgba(13,148,136,.15);color:#0d9488; }
.af-dot.blue   { background:rgba(59,130,246,.15);color:#3b82f6; }
.af-dot.red    { background:rgba(244,63,94,.15);color:#f43f5e; }
.af-dot.orange { background:rgba(245,158,11,.15);color:#f59e0b; }
.af-body { flex:1; }
.af-body .af-desc { font-size:1.25rem;font-weight:600;color:var(--text-primary);margin:0 0 .3rem; }
.af-body .af-time { font-size:1.1rem;color:var(--text-muted);display:flex;align-items:center;gap:.5rem;font-weight:500;}
</style>

    <!-- ── HERO BANNER ── -->
    <div class="lab-hero-v2">
        <div class="lab-hero-avatar-v2">
            <?php 
                $pImg = $tech_row['profile_photo'] ?? '';
                $isFemale = false; // Add gender logic here if present in DB
                if($pImg && $pImg !== 'default-avatar.png' && !empty($pImg)):
            ?>
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= e($pImg) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <i class="fas fa-microscope" style="font-size:3.5rem;"></i>
            <?php endif;?>
        </div>
        
        <div class="lab-hero-info-v2">
            <h2>Good <?=date('H')<12?'Morning':(date('H')<17?'Afternoon':'Evening')?>, <?= e(explode(' ', $techName)[0]) ?> <i class="fas fa-vial" style="color:var(--surface-2);font-size:1.5rem;margin-left:5px;"></i></h2>
            <p><?=date('l, d F Y')?> &middot; <span id="heroClock" style="font-weight:700;color:#fff;"></span></p>
            <div class="lab-hero-chips">
                <span class="lab-chip"><i class="fas fa-id-badge"></i> <?= e($tech_row['technician_id']) ?></span>
                <span class="lab-chip"><i class="fas fa-flask"></i> <?= e($tech_row['specialization']) ?> Area</span>
                <?php if($unread_notifs > 0): ?>
                    <span class="lab-chip danger pulse-fade"><i class="fas fa-bell"></i> <?= $unread_notifs ?> Alerts</span>
                <?php else: ?>
                    <span class="lab-chip valid"><i class="fas fa-check-circle"></i> Systems Normal</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── PREIMUM STAT CARDS ── -->
    <!-- Values injected via Poller -->
    <div class="lab-stat-grid">
        <div class="lab-stat-card" style="--card-accent:#3b82f6" onclick="window.location.href='?tab=orders&filter_status=Pending'">
            <div class="sc-icon" style="background:rgba(59,130,246,.12);color:#3b82f6"><i class="fas fa-file-invoice"></i></div>
            <div class="sc-val" id="st-pending">0</div>
            <div class="sc-lbl">Pending Orders</div>
        </div>
        
        <div class="lab-stat-card" style="--card-accent:#0ea5e9" onclick="window.location.href='?tab=samples&filter_status=Collected'">
            <div class="sc-icon" style="background:rgba(14,165,233,.12);color:#0ea5e9"><i class="fas fa-vials"></i></div>
            <div class="sc-val" id="st-samples">0</div>
            <div class="sc-lbl">Awaiting Samples</div>
        </div>
        
        <div class="lab-stat-card" style="--card-accent:#0d9488" onclick="window.location.href='?tab=orders&filter_status=Processing'">
            <div class="sc-icon" style="background:rgba(13,148,136,.12);color:#0d9488"><i class="fas fa-microscope"></i></div>
            <div class="sc-val" id="st-processing">0</div>
            <div class="sc-lbl">Tests in Progress</div>
        </div>
        
        <div class="lab-stat-card" style="--card-accent:#f59e0b" onclick="window.location.href='?tab=results&filter_status=Pending Validation'">
            <div class="sc-icon" style="background:rgba(245,158,11,.12);color:#f59e0b"><i class="fas fa-tasks"></i></div>
            <div class="sc-val" id="st-validation">0</div>
            <div class="sc-lbl">Awaiting Validation</div>
        </div>
        
        <div class="lab-stat-card" style="--card-accent:#f43f5e" onclick="window.location.href='?tab=results&filter=critical'">
            <div class="sc-icon" style="background:rgba(244,63,94,.12);color:#f43f5e"><i class="fas fa-heartbeat"></i></div>
            <div class="sc-val" id="st-critical">0</div>
            <div class="sc-lbl">Critical (Today)</div>
        </div>

        <div class="lab-stat-card" style="--card-accent:#8b5cf6" onclick="window.location.href='?tab=inventory&filter=low'">
            <div class="sc-icon" style="background:rgba(139,92,246,.12);color:#8b5cf6"><i class="fas fa-boxes"></i></div>
            <div class="sc-val" id="st-reagent">0</div>
            <div class="sc-lbl">Low Reagents</div>
        </div>
    </div>

    <!-- ── ALERT BANNER ── -->
    <?php if(!empty($critical_alerts)): ?>
    <div class="lab-alert-banner">
        <i class="fas fa-exclamation-triangle pulse-fade" style="font-size:2.4rem;color:var(--danger);flex-shrink:0;"></i>
        <div style="flex:1;">
            <strong style="font-size:1.4rem;color:var(--danger);font-weight:800;display:block;">URGENT: Critical Results Detected Today</strong>
            <div style="font-size:1.2rem;margin-top:.4rem;color:var(--text-primary);font-weight:600;">
                There are <?= count($critical_alerts) ?> critically flagged results that may require immediate physician review.
            </div>
        </div>
        <button class="btn adm-btn adm-btn-primary" onclick="window.location.href='?tab=results&filter=critical'" style="background:var(--danger);font-weight:800;border-radius:12px;padding:.8rem 1.6rem;"><span class="btn-text">View Critical Flags <i class="fas fa-arrow-right"></i></span></button>
    </div>
    <?php endif; ?>

    <!-- ── QUICK ACTIONS ── -->
    <div class="lab-quick-grid">
        <a href="?tab=orders" class="lab-quick-btn">
            <div class="qb-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;"><i class="fas fa-file-medical"></i></div>
            <span class="qb-label">Review Orders</span>
        </a>
        <a href="?tab=samples" class="lab-quick-btn">
            <div class="qb-icon" style="background:rgba(14,165,233,.1);color:#0ea5e9;"><i class="fas fa-vial"></i></div>
            <span class="qb-label">Log Sample</span>
        </a>
        <a href="?tab=results" class="lab-quick-btn">
            <div class="qb-icon" style="background:rgba(13,148,136,.1);color:#0d9488;"><i class="fas fa-edit"></i></div>
            <span class="qb-label">Enter Results</span>
        </a>
        <a href="?tab=equipment" class="lab-quick-btn">
            <div class="qb-icon" style="background:rgba(245,158,11,.1);color:#f59e0b;"><i class="fas fa-wrench"></i></div>
            <span class="qb-label">Equipment</span>
        </a>
        <a href="?tab=reports" class="lab-quick-btn">
            <div class="qb-icon" style="background:rgba(139,92,246,.1);color:#8b5cf6;"><i class="fas fa-chart-bar"></i></div>
            <span class="qb-label">Analytics</span>
        </a>
    </div>

    <!-- ── CHARTS & ACTIVITY ── -->
    <div class="lab-charts-grid">
        
        <!-- Chart: Order Volume -->
        <div class="lab-chart-card">
            <h4><i class="fas fa-chart-line" style="color:var(--role-accent);"></i> Activity Volume (7 Days)</h4>
            <div class="chart-wrap"><canvas id="chartLabVolume"></canvas></div>
        </div>

        <!-- Activity Feed -->
        <div class="lab-chart-card" style="display:flex; flex-direction:column;">
            <h4><i class="fas fa-history text-primary"></i> Live Activity Feed</h4>
            <div style="flex:1; overflow-y:auto; padding-right:1rem;" class="custom-scrollbar">
                <?php if(empty($activities)): ?>
                    <div style="padding:4rem 0; text-align:center; color:var(--text-muted);">
                        <i class="fas fa-clipboard-check" style="font-size:4rem; opacity:0.1; margin-bottom:1rem; display:block;"></i>
                        <p style="font-weight:600;font-size:1.2rem;">All clear. Standing by for logging.</p>
                    </div>
                <?php else: ?>
                    <div style="padding:0.5rem 0;">
                        <?php foreach($activities as $act):
                            $dotColor = 'teal'; $icon = 'fa-check';
                            if(strpos(strtolower($act['module_affected']), 'result') !== false) { $dotColor = 'orange'; $icon = 'fa-file-signature'; }
                            if(strpos(strtolower($act['module_affected']), 'sample') !== false) { $dotColor = 'blue'; $icon = 'fa-vial'; }
                            if(strpos(strtolower($act['module_affected']), 'order') !== false) { $dotColor = 'teal'; $icon = 'fa-file-medical'; }
                            if(strpos(strtolower($act['module_affected']), 'critical') !== false) { $dotColor = 'red'; $icon = 'fa-radiation'; }
                        ?>
                        <div class="activity-feed-item">
                            <div class="af-dot <?= $dotColor ?>"><i class="fas <?= $icon ?>"></i></div>
                            <div class="af-body">
                                <div class="af-desc"><?= e($act['action_type']) ?></div>
                                <div class="af-time"><i class="fas fa-bullseye" style="opacity:.6"></i> <?= e($act['module_affected']) ?> &bull; <?= date('H:i', strtotime($act['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Live clock for Hero Banner
setInterval(() => {
    const clock = document.getElementById('heroClock');
    if(clock) clock.textContent = new Date().toLocaleTimeString('en-GB'); 
}, 1000);

document.addEventListener("DOMContentLoaded", function() {
    
    // AJAX Poller for Live Dashboard Stats
    function fetchLabStats() {
        $.ajax({
            url: 'lab_actions.php',
            type: 'POST',
            data: { action: 'fetch_dashboard_stats', csrf_token: '<?= $csrf_token ?>' },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    // Animate numbers gently
                    $('#st-pending').text(res.stats.pending_orders);
                    $('#st-samples').text(res.stats.samples_awaiting);
                    $('#st-processing').text(res.stats.processing_tests);
                    $('#st-validation').text(res.stats.results_awaiting_val);
                    $('#st-critical').text(res.stats.critical_flagged);
                    $('#st-reagent').text(res.stats.low_reagent);
                }
            }
        });
    }

    fetchLabStats();
    setInterval(fetchLabStats, 30000);

    // High Quality Chart JS Rendering
    const ctxVol = document.getElementById('chartLabVolume').getContext('2d');
    
    // Create soft gradient for line chart
    let gradient = ctxVol.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(13, 148, 136, 0.4)');
    gradient.addColorStop(1, 'rgba(13, 148, 136, 0.0)');

    new Chart(ctxVol, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Test Orders Processed',
                data: [15, 22, 18, 30, 25, 12, 9],
                backgroundColor: gradient,
                borderColor: '#0d9488',
                borderWidth: 3,
                pointBackgroundColor: '#0d9488',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    padding: 14,
                    displayColors: false,
                    cornerRadius: 10,
                    bodyFont: { family: "'Poppins', sans-serif", size: 14, weight: '600' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
