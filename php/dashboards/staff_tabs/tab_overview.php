<?php
/**
 * tab_overview.php — Dashboard Overview (Role-Adaptive)
 * Module 1: Role-specific KPI cards, quick actions, and activity feed.
 */

// â”€â”€ Role-Specific Stats â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$overview_stats = [];
$quick_actions  = [];
$activity_items = [];

switch ($staffRole) {
    case 'ambulance_driver':
        $overview_stats = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM ambulance_requests WHERE status='pending'"),       'label'=>'Pending Requests',   'icon'=>'fa-siren-on','color'=>'var(--danger)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM ambulance_trips WHERE driver_id=? AND DATE(created_at)=?","is",[$staff_id,$today]), 'label'=>'Trips Today','icon'=>'fa-ambulance','color'=>'var(--primary)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM ambulance_trips WHERE driver_id=? AND trip_status='completed' AND DATE(completed_at)=?","is",[$staff_id,$today]), 'label'=>'Completed Today','icon'=>'fa-check-circle','color'=>'var(--success)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM vehicles WHERE assigned_driver_id=? AND status='available'","i",[$staff_id]) ?: '—', 'label'=>'My Vehicle','icon'=>'fa-truck','color'=>'var(--info)'],
        ];
        $quick_actions = [
            ['label'=>'View Requests',  'icon'=>'fa-siren-on', 'tab'=>'ambulance'],
            ['label'=>'My Active Trip', 'icon'=>'fa-route',    'tab'=>'ambulance'],
            ['label'=>'Fuel Log',       'icon'=>'fa-gas-pump', 'tab'=>'ambulance'],
        ];
        break;
    case 'cleaner':
        $overview_stats = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM cleaning_schedules WHERE assigned_to=? AND schedule_date=? AND status='scheduled'","is",[$staff_id,$today]), 'label'=>'Pending Tasks',  'icon'=>'fa-broom',    'color'=>'var(--warning)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM cleaning_logs WHERE staff_id=? AND DATE(created_at)=? AND completed_at IS NOT NULL","is",[$staff_id,$today]), 'label'=>'Completed Today','icon'=>'fa-check',    'color'=>'var(--success)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM contamination_reports WHERE reported_by=? AND status='reported'","i",[$staff_id]), 'label'=>'Open Reports', 'icon'=>'fa-biohazard', 'color'=>'var(--danger)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM cleaning_schedules WHERE assigned_to=? AND status='urgent'","i",[$staff_id]) ?: 0, 'label'=>'Urgent','icon'=>'fa-exclamation-triangle','color'=>'#E67E22'],
        ];
        $quick_actions = [
            ['label'=>'My Schedule',         'icon'=>'fa-calendar',  'tab'=>'cleaning'],
            ['label'=>'Report Contamination','icon'=>'fa-biohazard', 'tab'=>'cleaning','modal'=>'contamReportModal'],
            ['label'=>'Sanitation Board',    'icon'=>'fa-clipboard', 'tab'=>'cleaning'],
        ];
        break;
    case 'laundry_staff':
        $overview_stats = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM laundry_batches WHERE assigned_to=? AND delivery_status NOT IN ('delivered')","i",[$staff_id]), 'label'=>'Active Batches',  'icon'=>'fa-tshirt',  'color'=>'var(--primary)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM laundry_batches WHERE assigned_to=? AND collection_status='collected'","i",[$staff_id]),  'label'=>'Pending Pickup','icon'=>'fa-box-open', 'color'=>'var(--warning)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM laundry_batches WHERE assigned_to=? AND DATE(delivered_at)=?","is",[$staff_id,$today]), 'label'=>'Delivered Today','icon'=>'fa-check-circle','color'=>'var(--success)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM laundry_inventory WHERE available_quantity <= reorder_level"), 'label'=>'Low Stock Alerts','icon'=>'fa-box','color'=>'var(--danger)'],
        ];
        $quick_actions = [
            ['label'=>'Register Batch','icon'=>'fa-plus','tab'=>'laundry','modal'=>'newBatchModal'],
            ['label'=>'Update Batch',  'icon'=>'fa-sync','tab'=>'laundry'],
            ['label'=>'Report Damage', 'icon'=>'fa-exclamation','tab'=>'laundry'],
        ];
        break;
    case 'maintenance':
        $overview_stats = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM maintenance_requests WHERE status='reported' AND (assigned_to IS NULL OR assigned_to=0)"), 'label'=>'Open Requests',  'icon'=>'fa-wrench',  'color'=>'var(--danger)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM maintenance_requests WHERE assigned_to=? AND status='in progress'","i",[$staff_id]),  'label'=>'In Progress',    'icon'=>'fa-tools',   'color'=>'var(--warning)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM maintenance_requests WHERE assigned_to=? AND status='completed' AND DATE(completed_at)=?","is",[$staff_id,$today]), 'label'=>'Completed Today','icon'=>'fa-check','color'=>'var(--success)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM maintenance_requests WHERE assigned_to=? AND status='on hold'","i",[$staff_id]), 'label'=>'On Hold','icon'=>'fa-pause-circle','color'=>'var(--info)'],
        ];
        $quick_actions = [
            ['label'=>'View Requests','icon'=>'fa-list','tab'=>'maintenance'],
            ['label'=>'My Active Jobs','icon'=>'fa-tools','tab'=>'maintenance'],
        ];
        break;
    case 'security':
        $overview_stats = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM security_incidents WHERE staff_id=? AND DATE(reported_at)=?","is",[$staff_id,$today]), 'label'=>'Incidents Today','icon'=>'fa-shield-alt','color'=>'var(--danger)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM visitor_logs WHERE logged_by=? AND DATE(entry_time)=? AND exit_time IS NULL","is",[$staff_id,$today]), 'label'=>'Active Visitors','icon'=>'fa-users','color'=>'var(--warning)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM visitor_logs WHERE logged_by=? AND DATE(entry_time)=?","is",[$staff_id,$today]), 'label'=>'Visitors Today','icon'=>'fa-user-check','color'=>'var(--primary)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM security_logs WHERE staff_id=? AND DATE(reported_at)=? AND incident_type='patrol log'","is",[$staff_id,$today]), 'label'=>'Patrol Check-ins','icon'=>'fa-map-marker-alt','color'=>'var(--success)'],
        ];
        $quick_actions = [
            ['label'=>'Log Incident',   'icon'=>'fa-exclamation','tab'=>'security','modal'=>'incidentModal'],
            ['label'=>'Log Visitor',    'icon'=>'fa-user-plus',  'tab'=>'visitors','modal'=>'addVisitorModal'],
            ['label'=>'Patrol Check-in','icon'=>'fa-map-pin',    'modal'=>'patrolModal'],
        ];
        break;
    case 'kitchen_staff':
        $overview_stats = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM kitchen_tasks WHERE assigned_to=? AND DATE(created_at)=? AND preparation_status='pending'","is",[$staff_id,$today]), 'label'=>'Pending Meals',   'icon'=>'fa-utensils','color'=>'var(--warning)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM kitchen_tasks WHERE assigned_to=? AND delivery_status='delivered' AND DATE(delivered_at)=?","is",[$staff_id,$today]), 'label'=>'Delivered Today',  'icon'=>'fa-check',  'color'=>'var(--success)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM kitchen_dietary_flags WHERE status='flagged' AND DATE(flagged_at)=?","s",[$today]), 'label'=>'Dietary Alerts','icon'=>'fa-allergies','color'=>'var(--danger)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM kitchen_tasks WHERE assigned_to=? AND preparation_status='in preparation'","i",[$staff_id]), 'label'=>'In Prep','icon'=>'fa-fire','color'=>'var(--info)'],
        ];
        $quick_actions = [
            ['label'=>'My Meal Tasks','icon'=>'fa-list','tab'=>'kitchen'],
            ['label'=>'Flag Dietary Issue','icon'=>'fa-allergies','tab'=>'kitchen','modal'=>'dietaryModal'],
        ];
        break;
    default: // General staff
        $overview_stats = [
            ['val'=>$pending_tasks, 'label'=>'Pending Tasks','icon'=>'fa-clipboard-list','color'=>'var(--warning)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM staff_tasks WHERE assigned_to=? AND status='completed' AND DATE(completed_at)=?","is",[$staff_id,$today]) ?? 0, 'label'=>'Completed Today','icon'=>'fa-check','color'=>'var(--success)'],
            ['val'=>$unread_notifs,'label'=>'New Alerts','icon'=>'fa-bell','color'=>'var(--danger)'],
            ['val'=>$unread_msgs,  'label'=>'Unread Messages','icon'=>'fa-envelope','color'=>'var(--primary)'],
        ];
        $quick_actions = [['label'=>'View Tasks','icon'=>'fa-tasks','tab'=>'tasks'],['label'=>'My Profile','icon'=>'fa-user','tab'=>'profile']];
}

// â”€â”€ Recent Activity â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$activity_raw = dbSelect($conn,"SELECT message, created_at, type FROM staff_notifications WHERE staff_id=? ORDER BY notification_id DESC LIMIT 8","i",[$staff_id]);
// â”€â”€ Current Shift â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$current_shift = dbRow($conn,"SELECT * FROM staff_shifts WHERE staff_id=? AND shift_date=? ORDER BY shift_id DESC LIMIT 1","is",[$staff_id,$today]);
// â”€â”€ Recent Maintenance Activity (for maintenance role) â”€â”€â”€â”€â”€â”€
if ($staffRole === 'maintenance') {
    $recent_jobs = dbSelect($conn,
        "SELECT request_id, equipment_or_area, location, issue_category, priority, status, reported_at, completed_at
         FROM maintenance_requests WHERE assigned_to=? ORDER BY COALESCE(completed_at,reported_at) DESC LIMIT 6",
        "i", [$staff_id]);

    // Weekly completion sparkline data
    $spark_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $cnt = (int)dbVal($conn,
            "SELECT COUNT(*) FROM maintenance_requests WHERE assigned_to=? AND status='completed' AND DATE(completed_at)=?",
            "is", [$staff_id, $d]) ?? 0;
        $spark_data[] = $cnt;
    }
} else {
    $recent_jobs = [];
    $spark_data  = [0,0,0,0,0,0,0];
}
?>
<div id="sec-overview" class="dash-section">
<style>
.ov-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,var(--role-accent),color-mix(in srgb,var(--role-accent) 45%,#0F2027 55%));border-radius:20px;padding:3rem;margin-bottom:2.5rem;display:flex;align-items:center;gap:2.5rem;flex-wrap:wrap;box-shadow:0 20px 60px rgba(47,128,237,0.22);color:#fff;}
.ov-hero::before{content:'';position:absolute;top:-60px;right:-60px;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,.06);pointer-events:none;}
.ov-hero::after{content:'';position:absolute;bottom:-80px;right:120px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.04);pointer-events:none;}
.ov-avatar{width:88px;height:88px;border-radius:20px;flex-shrink:0;border:3px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.15);backdrop-filter:blur(10px);display:flex;align-items:center;justify-content:center;font-size:3rem;font-weight:800;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.2);}
.ov-avatar img{width:100%;height:100%;object-fit:cover;}
.ov-info h2{font-size:2.4rem;font-weight:800;margin:0 0 .4rem;}
.ov-info p{font-size:1.35rem;margin:0 0 1rem;opacity:.85;}
.ov-badges{display:flex;gap:.8rem;flex-wrap:wrap;}
.ov-badge{display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.25);padding:.4rem 1.1rem;border-radius:20px;font-size:1.15rem;font-weight:600;}
.ov-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1.8rem;margin-bottom:2.5rem;}
.ov-kpi{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:2rem 2rem 1.6rem;transition:all .25s ease;position:relative;overflow:hidden;box-shadow:var(--shadow-sm);}
.ov-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--kpi-color,var(--role-accent));border-radius:16px 16px 0 0;}
.ov-kpi:hover{transform:translateY(-4px);box-shadow:var(--shadow-md);}
.ov-kpi-icon{width:48px;height:48px;border-radius:12px;background:color-mix(in srgb,var(--kpi-color,var(--role-accent)) 12%,transparent 88%);display:flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:1.4rem;}
.ov-kpi-val{font-size:3.4rem;font-weight:900;line-height:1;color:var(--kpi-color,var(--role-accent));margin-bottom:.5rem;}
.ov-kpi-lbl{font-size:1.2rem;font-weight:600;color:var(--text-secondary);}
.ov-grid-2{display:grid;grid-template-columns:1fr 1.3fr;gap:2rem;margin-bottom:2rem;}
@media(max-width:900px){.ov-grid-2{grid-template-columns:1fr;}}
.ov-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);}
.ov-card-header{padding:1.6rem 2rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:var(--surface-2);}
.ov-card-header h3{font-size:1.5rem;font-weight:700;color:var(--text-primary);margin:0;display:flex;align-items:center;gap:.7rem;}
.ov-action-btn{display:flex;align-items:center;gap:1.2rem;padding:1.3rem 1.8rem;border:1.5px solid var(--border);border-radius:12px;background:var(--surface);color:var(--text-primary);font-size:1.3rem;font-weight:600;cursor:pointer;transition:.2s ease;text-align:left;width:100%;}
.ov-action-btn:hover{border-color:var(--role-accent);background:color-mix(in srgb,var(--role-accent) 6%,var(--surface) 94%);transform:translateX(4px);}
.oa-ico{width:36px;height:36px;border-radius:10px;background:color-mix(in srgb,var(--role-accent) 15%,transparent 85%);display:flex;align-items:center;justify-content:center;color:var(--role-accent);font-size:1.5rem;flex-shrink:0;}
.ov-feed-item{display:flex;align-items:flex-start;gap:1.4rem;padding:1.5rem 2rem;border-bottom:1px solid var(--border);transition:.15s ease;}
.ov-feed-item:last-child{border-bottom:none;}
.ov-feed-item:hover{background:var(--surface-2);}
.ov-feed-dot{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0;}
.pr-urgent{color:#E74C3C;background:rgba(231,76,60,.12);}
.pr-high{color:#E67E22;background:rgba(230,126,34,.12);}
.pr-medium{color:#F39C12;background:rgba(243,156,18,.12);}
.pr-low{color:#27AE60;background:rgba(39,174,96,.12);}
.ov-pill{display:inline-flex;align-items:center;padding:.25rem .8rem;border-radius:20px;font-size:1.05rem;font-weight:700;}
.ov-hbtn{background:none;border:1.5px solid var(--border);border-radius:10px;padding:.4rem 1rem;font-size:1.1rem;font-weight:700;color:var(--text-secondary);cursor:pointer;transition:.2s;}
.ov-hbtn:hover{border-color:var(--role-accent);color:var(--role-accent);}
</style>

<!-- â”€â”€ Hero Banner â”€â”€ -->
<div class="ov-hero">
    <div class="ov-avatar">
        <?php if (!empty($staff['profile_photo'])): ?>
            <img src="/RMU-Medical-Management-System/<?= e($staff['profile_photo']) ?>" alt="avatar">
        <?php else: echo strtoupper(substr($displayName,0,1)); endif; ?>
    </div>
    <div class="ov-info" style="flex:1;">
        <h2>Welcome back, <?= e(explode(' ',$displayName)[0]) ?> &#128075;</h2>
        <p><i class="fas fa-calendar-day"></i> <?= date('l, d F Y') ?></p>
        <div class="ov-badges">
            <span class="ov-badge"><i class="fas <?= e($roleIcon) ?>"></i> <?= e($displayRole) ?></span>
            <span class="ov-badge"><i class="fas fa-id-badge"></i> <?= e($staff['employee_id'] ?? 'Pending ID') ?></span>
            <?php if (!empty($staff['dept_name']) && $staff['dept_name'] !== 'â€”'): ?>
            <span class="ov-badge"><i class="fas fa-building"></i> <?= e($staff['dept_name']) ?></span>
            <?php endif; ?>
            <?php if ($current_shift): ?>
            <span class="ov-badge" style="background:rgba(39,174,96,.25);border-color:rgba(39,174,96,.4);">
                <i class="fas fa-clock"></i> <?= e(ucfirst($current_shift['shift_type'])) ?> Shift
                (<?= date('H:i',strtotime($current_shift['start_time'])) ?>–<?= date('H:i',strtotime($current_shift['end_time'])) ?>)
            </span>
            <?php else: ?>
            <span class="ov-badge" style="background:rgba(255,255,255,.1);"><i class="fas fa-moon"></i> No Shift Today</span>
            <?php endif; ?>
        </div>
    </div>
    <div style="text-align:center;flex-shrink:0;">
        <svg width="80" height="80" style="transform:rotate(-90deg);">
            <circle cx="40" cy="40" r="34" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="6"/>
            <circle cx="40" cy="40" r="34" fill="none" stroke="rgba(255,255,255,.85)" stroke-width="6"
                stroke-dasharray="<?= round(213.6*$completeness/100) ?> 213.6" stroke-linecap="round"/>
        </svg>
        <div style="margin-top:-56px;position:relative;font-size:1.4rem;font-weight:900;"><?= $completeness ?>%</div>
        <div style="font-size:1.1rem;opacity:.75;margin-top:42px;">Profile</div>
    </div>
</div>

<!-- â”€â”€ KPI Cards â”€â”€ -->
<div class="ov-kpi-grid">
<?php
$kpi_colors = ['var(--danger)','var(--warning)','var(--success)','var(--info)'];
foreach ($overview_stats as $i => $stat):
    $c = $kpi_colors[$i] ?? 'var(--role-accent)';
?>
<div class="ov-kpi" style="--kpi-color:<?= $c ?>;">
    <div class="ov-kpi-icon"><i class="fas <?= e($stat['icon']) ?>" style="color:<?= $c ?>;"></i></div>
    <div class="ov-kpi-val"><?= $stat['val'] ?? 0 ?></div>
    <div class="ov-kpi-lbl"><?= e($stat['label']) ?></div>
</div>
<?php endforeach; ?>
</div>

<!-- â”€â”€ Quick Actions + Activity Feed â”€â”€ -->
<div class="ov-grid-2">
    <div class="ov-card">
        <div class="ov-card-header"><h3><i class="fas fa-bolt" style="color:var(--role-accent);"></i> Quick Actions</h3></div>
        <div style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
            <?php foreach ($quick_actions as $qa): ?>
            <button class="ov-action-btn" onclick="<?= !empty($qa['modal']) ? "openModal('{$qa['modal']}')" : "showTab('{$qa['tab']}',null)" ?>">
                <span class="oa-ico"><i class="fas <?= e($qa['icon']) ?>"></i></span>
                <?= e($qa['label']) ?>
                <i class="fas fa-chevron-right" style="margin-left:auto;opacity:.35;font-size:1.1rem;"></i>
            </button>
            <?php endforeach; ?>
            <button class="ov-action-btn" onclick="showTab('schedule',null)">
                <span class="oa-ico"><i class="fas fa-calendar-plus"></i></span>
                Request Leave
                <i class="fas fa-chevron-right" style="margin-left:auto;opacity:.35;font-size:1.1rem;"></i>
            </button>
            <button class="ov-action-btn" onclick="showTab('messages',null)">
                <span class="oa-ico"><i class="fas fa-envelope"></i></span>
                Compose Message
                <i class="fas fa-chevron-right" style="margin-left:auto;opacity:.35;font-size:1.1rem;"></i>
            </button>
        </div>
    </div>

    <div class="ov-card">
        <div class="ov-card-header">
            <h3><i class="fas fa-history" style="color:var(--role-accent);"></i>
                <?= $staffRole === 'maintenance' ? 'Recent Work Orders' : 'Recent Alerts' ?>
            </h3>
            <button class="ov-hbtn" onclick="showTab('<?= $staffRole==='maintenance'?'maintenance':'notifications' ?>',null)">View All</button>
        </div>
        <?php if ($staffRole === 'maintenance' && !empty($recent_jobs)): foreach ($recent_jobs as $j):
            $pmap = ['urgent'=>'pr-urgent','high'=>'pr-high','medium'=>'pr-medium','low'=>'pr-low'];
            $pc   = $pmap[strtolower($j['priority']??'low')]??'pr-low';
            $smap = ['assigned'=>['#2F80ED','fa-check-circle'],'in progress'=>['#F39C12','fa-spinner'],'completed'=>['#27AE60','fa-check-double'],'reported'=>['#E74C3C','fa-exclamation-circle'],'on hold'=>['#95A5A6','fa-pause-circle']];
            [$sc,$si] = $smap[strtolower($j['status']??'reported')]??['#95A5A6','fa-circle'];
        ?>
        <div class="ov-feed-item">
            <div class="ov-feed-dot <?= $pc ?>"><i class="fas <?= $si ?>"></i></div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-bottom:.4rem;">
                    <strong style="font-size:1.3rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($j['equipment_or_area'] ?? $j['issue_category'] ?? 'Work Order') ?></strong>
                    <span class="ov-pill" style="background:color-mix(in srgb,<?= $sc ?> 15%,transparent 85%);color:<?= $sc ?>;white-space:nowrap;"><?= ucfirst($j['status']) ?></span>
                </div>
                <div style="font-size:1.15rem;color:var(--text-muted);">
                    <i class="fas fa-map-marker-alt"></i> <?= e($j['location']??'—') ?>
                    &middot; <span class="ov-pill <?= $pc ?>" style="padding:.15rem .5rem;font-size:1rem;"><?= ucfirst($j['priority']??'low') ?></span>
                </div>
            </div>
        </div>
        <?php endforeach;
        elseif (!empty($activity_raw)): foreach ($activity_raw as $a):
            $ico_map=['task'=>'fa-check-circle','alert'=>'fa-exclamation-triangle','shift'=>'fa-calendar-alt','emergency'=>'fa-ambulance','system'=>'fa-cog','message'=>'fa-envelope','maintenance'=>'fa-tools','leave'=>'fa-umbrella-beach'];
            $ico=$ico_map[$a['type']]??'fa-info-circle';
        ?>
        <div class="ov-feed-item">
            <div class="ov-feed-dot" style="background:color-mix(in srgb,var(--role-accent) 12%,transparent 88%);color:var(--role-accent);">
                <i class="fas <?= e($ico) ?>"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <p style="margin:0 0 .3rem;font-size:1.3rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($a['message']) ?></p>
                <span style="font-size:1.1rem;color:var(--text-muted);"><?= date('d M, h:i A',strtotime($a['created_at'])) ?></span>
            </div>
        </div>
        <?php endforeach; else: ?>
        <div style="padding:5rem 2rem;text-align:center;color:var(--text-muted);">
            <i class="fas fa-check-circle" style="font-size:4rem;opacity:.15;display:block;margin-bottom:1.5rem;"></i>
            <div style="font-weight:700;font-size:1.4rem;">All Clear!</div>
            <p style="font-size:1.2rem;margin-top:.5rem;">No recent activity to display.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- â”€â”€ Sparkline + Upcoming Shifts â”€â”€ -->
<?php if ($staffRole === 'maintenance'): ?>
<div style="display:grid;grid-template-columns:1fr 1.2fr;gap:2rem;margin-bottom:2rem;">
    <div class="ov-card">
        <div class="ov-card-header"><h3><i class="fas fa-chart-area" style="color:var(--role-accent);"></i> Jobs Completed — Last 7 Days</h3></div>
        <div style="padding:2rem;"><canvas id="ovSparkline" height="130"></canvas></div>
    </div>
    <?php $upcoming_shifts = dbSelect($conn,"SELECT * FROM staff_shifts WHERE staff_id=? AND shift_date >= ? ORDER BY shift_date ASC LIMIT 5","is",[$staff_id,$today]); ?>
    <div class="ov-card">
        <div class="ov-card-header">
            <h3><i class="fas fa-calendar-week" style="color:var(--role-accent);"></i> Upcoming Shifts</h3>
            <button class="ov-hbtn" onclick="showTab('schedule',null)">Schedule</button>
        </div>
        <?php if (empty($upcoming_shifts)): ?>
        <div style="padding:3rem;text-align:center;color:var(--text-muted);font-size:1.3rem;">No upcoming shifts scheduled.</div>
        <?php else: foreach ($upcoming_shifts as $s):
            $is_today=$s['shift_date']===$today; $st=$s['status']??'scheduled';
            $shift_icon=['morning'=>'fa-sun','afternoon'=>'fa-cloud-sun','night'=>'fa-moon','rotating'=>'fa-sync'][$s['shift_type']??'']??'fa-clock';
            $st_c=['active'=>'#27AE60','completed'=>'#95A5A6','missed'=>'#E74C3C','scheduled'=>'#2980B9','swapped'=>'#F39C12'][$st]??'#2980B9';
        ?>
        <div class="ov-feed-item" style="<?= $is_today?'background:color-mix(in srgb,var(--role-accent) 5%,var(--surface) 95%);':'' ?>">
            <div class="ov-feed-dot" style="background:color-mix(in srgb,<?= $st_c ?> 15%,transparent 85%);color:<?= $st_c ?>;"><i class="fas <?= $shift_icon ?>"></i></div>
            <div style="flex:1;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <strong style="font-size:1.3rem;"><?= date('D, d M',strtotime($s['shift_date'])) ?>
                        <?= $is_today?'<span style="background:var(--role-accent);color:#fff;font-size:.9rem;padding:.15rem .6rem;border-radius:10px;margin-left:.5rem;font-weight:700;">TODAY</span>':'' ?>
                    </strong>
                    <span style="font-size:1.15rem;font-weight:700;color:<?= $st_c ?>;"><?= ucfirst($st) ?></span>
                </div>
                <div style="font-size:1.15rem;color:var(--text-muted);margin-top:.3rem;">
                    <i class="fas <?= $shift_icon ?>"></i> <?= ucfirst($s['shift_type']??'â€”') ?>
                    · <?= date('H:i',strtotime($s['start_time']??'00:00')) ?>–<?= date('H:i',strtotime($s['end_time']??'00:00')) ?>
                    <?php if(!empty($s['location_ward_assigned'])): ?> · <?= e($s['location_ward_assigned']) ?><?php endif;?>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<script>
(function(){
    const cvs=document.getElementById('ovSparkline'); if(!cvs) return;
    const accent=getComputedStyle(document.documentElement).getPropertyValue('--role-accent').trim()||'#2F80ED';
    const isDark=document.documentElement.getAttribute('data-theme')==='dark';
    const gridClr=isDark?'rgba(255,255,255,.06)':'rgba(0,0,0,.05)';
    const labels=<?= json_encode(array_map(fn($i)=>date('D',strtotime("-{$i} days")),range(6,0))) ?>;
    const data=<?= json_encode($spark_data) ?>;
    const ctx=cvs.getContext('2d');
    const grad=ctx.createLinearGradient(0,0,0,110);
    grad.addColorStop(0,accent+'44'); grad.addColorStop(1,accent+'00');
    new Chart(cvs,{type:'line',data:{labels,datasets:[{label:'Completed',data,borderColor:accent,backgroundColor:grad,
        tension:.4,fill:true,pointRadius:5,pointHoverRadius:8,borderWidth:2.5,
        pointBackgroundColor:accent,pointBorderColor:'#fff',pointBorderWidth:2}]},
        options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false}},
            scales:{x:{grid:{color:gridClr},ticks:{color:'var(--text-muted)',font:{size:11}}},
                    y:{grid:{color:gridClr},ticks:{color:'var(--text-muted)',font:{size:11},stepSize:1,precision:0},beginAtZero:true}}}});
})();
</script>
<?php endif; ?>

</div><!-- /sec-overview -->

