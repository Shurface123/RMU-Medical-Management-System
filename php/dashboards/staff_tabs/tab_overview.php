<?php
/**
 * tab_overview.php — Dashboard Overview (Role-Adaptive)
 * Module 1: Role-specific KPI cards, quick actions, and activity feed.
 */

// ── Role-Specific Stats ───────────────────────────────────────
$overview_stats = [];
$quick_actions  = [];
$activity_items = [];

switch ($staffRole) {
    case 'ambulance_driver':
        $overview_stats = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM ambulance_requests WHERE status='pending'"),       'label'=>'Pending Requests',   'icon'=>'fa-siren-on','color'=>'var(--danger)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM ambulance_trips WHERE driver_id=? AND DATE(created_at)=?","is",[$staff_id,$today]), 'label'=>'Trips Today','icon'=>'fa-ambulance','color'=>'var(--primary)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM ambulance_trips WHERE driver_id=? AND trip_status='completed' AND DATE(completed_at)=?","is",[$staff_id,$today]), 'label'=>'Completed Today','icon'=>'fa-check-circle','color'=>'var(--success)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM vehicles WHERE assigned_driver_id=? AND status='active'","i",[$staff_id]) ?: '—', 'label'=>'My Vehicle','icon'=>'fa-truck','color'=>'var(--info)'],
        ];
        $quick_actions = [
            ['label'=>'View Requests',  'icon'=>'fa-siren-on', 'tab'=>'ambulance'],
            ['label'=>'My Active Trip', 'icon'=>'fa-route',    'tab'=>'ambulance'],
            ['label'=>'Fuel Log',       'icon'=>'fa-gas-pump', 'tab'=>'ambulance'],
        ];
        break;
    case 'cleaner':
        $overview_stats = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM cleaning_schedules WHERE assigned_to=? AND shift_date=? AND status='scheduled'","is",[$staff_id,$today]), 'label'=>'Pending Tasks',  'icon'=>'fa-broom',    'color'=>'var(--warning)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM cleaning_logs WHERE staff_id=? AND DATE(created_at)=? AND completed_at IS NOT NULL","is",[$staff_id,$today]), 'label'=>'Completed Today','icon'=>'fa-check',    'color'=>'var(--success)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM contamination_reports WHERE staff_id=? AND status='reported'","i",[$staff_id]), 'label'=>'Open Reports', 'icon'=>'fa-biohazard', 'color'=>'var(--danger)'],
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
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM laundry_batches WHERE staff_id=? AND status NOT IN ('delivered','cancelled')","i",[$staff_id]), 'label'=>'Active Batches',  'icon'=>'fa-tshirt',  'color'=>'var(--primary)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM laundry_batches WHERE staff_id=? AND status='collected'","i",[$staff_id]),  'label'=>'Pending Pickup','icon'=>'fa-box-open', 'color'=>'var(--warning)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM laundry_batches WHERE staff_id=? AND DATE(delivered_at)=?","is",[$staff_id,$today]), 'label'=>'Delivered Today','icon'=>'fa-check-circle','color'=>'var(--success)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM laundry_inventory WHERE quantity <= reorder_level"), 'label'=>'Low Stock Alerts','icon'=>'fa-box','color'=>'var(--danger)'],
        ];
        $quick_actions = [
            ['label'=>'Register Batch','icon'=>'fa-plus','tab'=>'laundry','modal'=>'newBatchModal'],
            ['label'=>'Update Batch',  'icon'=>'fa-sync','tab'=>'laundry'],
            ['label'=>'Report Damage', 'icon'=>'fa-exclamation','tab'=>'laundry'],
        ];
        break;
    case 'maintenance':
        $overview_stats = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM maintenance_requests WHERE status='open' AND (assigned_to IS NULL OR assigned_to=0)"), 'label'=>'Open Requests',  'icon'=>'fa-wrench',  'color'=>'var(--danger)'],
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
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM security_logs WHERE staff_id=? AND DATE(logged_at)=? AND log_type='patrol_checkin'","is",[$staff_id,$today]), 'label'=>'Patrol Check-ins','icon'=>'fa-map-marker-alt','color'=>'var(--success)'],
        ];
        $quick_actions = [
            ['label'=>'Log Incident',   'icon'=>'fa-exclamation','tab'=>'security','modal'=>'incidentModal'],
            ['label'=>'Log Visitor',    'icon'=>'fa-user-plus',  'tab'=>'visitors','modal'=>'addVisitorModal'],
            ['label'=>'Patrol Check-in','icon'=>'fa-map-pin',    'modal'=>'patrolModal'],
        ];
        break;
    case 'kitchen_staff':
        $overview_stats = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM kitchen_tasks WHERE assigned_to=? AND DATE(scheduled_time)=? AND status='pending'","is",[$staff_id,$today]), 'label'=>'Pending Meals',   'icon'=>'fa-utensils','color'=>'var(--warning)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM kitchen_tasks WHERE assigned_to=? AND status='delivered' AND DATE(delivered_at)=?","is",[$staff_id,$today]), 'label'=>'Delivered Today',  'icon'=>'fa-check',  'color'=>'var(--success)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM kitchen_dietary_flags WHERE status='flagged' AND DATE(flagged_at)=?","s",[$today]), 'label'=>'Dietary Alerts','icon'=>'fa-allergies','color'=>'var(--danger)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM kitchen_tasks WHERE assigned_to=? AND status='in preparation'","i",[$staff_id]), 'label'=>'In Prep','icon'=>'fa-fire','color'=>'var(--info)'],
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

// ── Recent Activity ──────────────────────────────────────────
$activity_raw = dbSelect($conn,"SELECT message, created_at, type FROM staff_notifications WHERE staff_id=? ORDER BY notification_id DESC LIMIT 8","i",[$staff_id]);
// ── Current Shift ────────────────────────────────────────────
$current_shift = dbRow($conn,"SELECT * FROM staff_shifts WHERE staff_id=? AND shift_date=? ORDER BY shift_id DESC LIMIT 1","is",[$staff_id,$today]);
?>
<div id="sec-overview" class="dash-section">

    <!-- Hero Banner -->
    <div class="staff-hero" style="margin-bottom:2.5rem;">
        <div class="staff-hero-avatar">
            <?php if (!empty($staff['profile_photo'])): ?>
                <img src="/RMU-Medical-Management-System/<?= e($staff['profile_photo']) ?>" alt="avatar">
            <?php else: echo strtoupper(substr($displayName,0,1)); endif; ?>
        </div>
        <div class="staff-hero-info" style="flex:1;">
            <h2>Welcome back, <?= e(explode(' ',$displayName)[0]) ?> 👋</h2>
            <p><i class="fas fa-calendar-day"></i> <?= date('l, d F Y') ?></p>
            <div style="display:flex;gap:.8rem;flex-wrap:wrap;margin-top:.8rem;">
                <span class="hero-badge"><i class="fas <?= e($roleIcon) ?>"></i> <?= e($displayRole) ?></span>
                <span class="hero-badge"><i class="fas fa-id-badge"></i> <?= e($staff['employee_id'] ?? 'Pending') ?></span>
                <?php if ($current_shift): ?>
                <span class="hero-badge"><i class="fas fa-clock"></i> <?= e(ucfirst($current_shift['shift_type'])) ?> Shift
                    (<?= date('H:i',strtotime($current_shift['start_time'])) ?> – <?= date('H:i',strtotime($current_shift['end_time'])) ?>)
                </span>
                <?php else: ?>
                <span class="hero-badge" style="background:rgba(255,255,255,.1);"><i class="fas fa-moon"></i> No Shift Today</span>
                <?php endif; ?>
                <?php $stat = $staff['status'] ?? 'active'; ?>
                <span class="hero-badge" style="background:<?= $stat==='Active'?'rgba(39,174,96,.3)':'rgba(231,76,60,.3)' ?>">
                    <i class="fas fa-circle" style="font-size:.8rem;"></i> <?= e($stat) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="stat-grid">
        <?php foreach ($overview_stats as $stat): ?>
        <div class="stat-mini" onclick="showTab('tasks',null)" style="cursor:pointer;">
            <div style="width:44px;height:44px;border-radius:12px;background:color-mix(in srgb,<?= $stat['color'] ?> 15%,#fff 85%);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="fas <?= e($stat['icon']) ?>" style="font-size:1.8rem;color:<?= $stat['color'] ?>;"></i>
            </div>
            <div class="stat-mini-val" style="color:<?= $stat['color'] ?>;"><?= $stat['val'] ?? 0 ?></div>
            <div class="stat-mini-lbl"><?= e($stat['label']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions + Activity Feed (2-col) -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:1rem;">
                <?php foreach ($quick_actions as $qa): ?>
                <button class="btn btn-outline" style="justify-content:flex-start;width:100%;"
                    onclick="<?= !empty($qa['modal']) ? "openModal('{$qa['modal']}')" : "showTab('{$qa['tab']}',null)" ?>">
                    <i class="fas <?= e($qa['icon']) ?>" style="width:20px;"></i> <?= e($qa['label']) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Alerts -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bell"></i> Recent Alerts</h3>
                <button class="btn btn-outline btn-sm" onclick="showTab('notifications',null)">See All</button>
            </div>
            <div class="card-body" style="padding:1.5rem;">
                <?php if (empty($activity_raw)): ?>
                    <p style="color:var(--text-muted);text-align:center;padding:2rem 0;">All caught up! No recent alerts.</p>
                <?php else: foreach ($activity_raw as $a):
                    $ico_map = ['task'=>'fa-check-circle','alert'=>'fa-exclamation-triangle','shift'=>'fa-calendar-alt','emergency'=>'fa-ambulance','system'=>'fa-cog','message'=>'fa-envelope'];
                    $ico = $ico_map[$a['type']] ?? 'fa-info-circle';
                ?><div class="activity-item">
                    <div class="activity-dot" style="background:var(--role-accent-light);color:var(--role-accent);">
                        <i class="fas <?= e($ico) ?>"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="margin:0;font-size:1.3rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($a['message']) ?></p>
                        <span style="font-size:1.1rem;color:var(--text-muted);"><?= date('d M, h:i A',strtotime($a['created_at'])) ?></span>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Shift Preview -->
    <?php
    $upcoming_shifts = dbSelect($conn,"SELECT * FROM staff_shifts WHERE staff_id=? AND shift_date >= ? ORDER BY shift_date ASC LIMIT 5","is",[$staff_id,$today]);
    if (!empty($upcoming_shifts)): ?>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-calendar-week"></i> Upcoming Shifts</h3>
            <button class="btn btn-outline btn-sm" onclick="showTab('schedule',null)">Full Schedule</button>
        </div>
        <div class="card-body-flush">
            <table class="stf-table">
                <thead><tr><th>Date</th><th>Shift</th><th>Time</th><th>Location</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($upcoming_shifts as $s):
                    $is_today = $s['shift_date'] === $today;
                    $st = $s['status'] ?? 'scheduled';
                    $st_color = ['active'=>'var(--success)','completed'=>'var(--text-muted)','missed'=>'var(--danger)','scheduled'=>'var(--info)','swapped'=>'var(--warning)'][$st] ?? 'var(--info)';
                ?>
                <tr style="<?= $is_today ? 'background:var(--role-accent-light);' : '' ?>">
                    <td><strong><?= date('D, d M',strtotime($s['shift_date'])) ?></strong><?= $is_today ? ' <span class="badge" style="background:var(--role-accent);color:#fff;font-size:1rem;">TODAY</span>' : '' ?></td>
                    <td><?= e(ucfirst($s['shift_type']??'—')) ?></td>
                    <td><?= date('H:i',strtotime($s['start_time']??'00:00')) ?> – <?= date('H:i',strtotime($s['end_time']??'00:00')) ?></td>
                    <td><?= e($s['location_ward_assigned']??'—') ?></td>
                    <td><span class="badge" style="background:color-mix(in srgb,<?=$st_color?> 15%,#fff 85%);color:<?=$st_color?>;"><?= ucfirst($st) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>