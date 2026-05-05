<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'notifications';
$page_title  = 'Notifications Center';
include '../includes/_sidebar.php';

$user_id = (int)$_SESSION['user_id'];

// Fetch Notifications
$notifications = [];
$cat_counts = ['security' => 0, 'approval' => 0, 'inventory' => 0, 'system' => 0];

$q = mysqli_query($conn, "SELECT * FROM notifications 
                          WHERE user_id = $user_id OR user_role = 'admin' OR user_role IS NULL 
                          ORDER BY created_at DESC LIMIT 200");
if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $notifications[] = $row;
        $type = strtolower($row['type']);
        if (strpos($type, 'security') !== false || strpos($type, 'urgent') !== false) {
            $cat_counts['security']++;
        } elseif (strpos($type, 'approval') !== false) {
            $cat_counts['approval']++;
        } elseif (strpos($type, 'inventory') !== false || strpos($type, 'stock') !== false) {
            $cat_counts['inventory']++;
        } else {
            $cat_counts['system']++;
        }
    }
}

// Low Stock Alert (Logically merged as notifications)
$q_low = mysqli_query($conn, "SELECT medicine_name, stock_quantity, reorder_level FROM medicines WHERE stock_quantity <= reorder_level");
if ($q_low) {
    while ($row = mysqli_fetch_assoc($q_low)) {
        $notifications[] = [
            'notification_id' => 'low_stock_' . uniqid(),
            'type' => 'inventory',
            'title' => 'Low Stock Alert',
            'message' => htmlspecialchars($row['medicine_name']) . ' has only ' . $row['stock_quantity'] . ' units left.',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'priority' => 'high'
        ];
        $cat_counts['inventory']++;
    }
}

// Sort the merged array by created_at DESC
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Calculate totals
$tot_unread = 0;
foreach($notifications as $n) {
    if (!isset($n['is_read']) || $n['is_read'] == 0) $tot_unread++;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">

<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #2F80ED;
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #000 40%));
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; }
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }

/* ── Analytical Layout ── */
.top-grid { display:grid; grid-template-columns:1fr 2fr; gap:2.5rem; margin-bottom:2.5rem; }
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
.card-header h3 { font-size:1.6rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

.stat-box { background:var(--surface-2); padding:2rem; border-radius:var(--radius-sm); text-align:center; display:flex; flex-direction:column; justify-content:center; align-items:center; height:100%; }
.stat-box-val { font-size:4.5rem; font-weight:800; color:var(--primary); line-height:1; }
.stat-box-lbl { font-size:1.3rem; font-weight:600; color:var(--text-secondary); margin-top:1rem; text-transform:uppercase; letter-spacing:0.05em; }

.chart-container { height:220px; width:100%; position:relative; }

/* ── Activity Feed UI (Replacing standard table look) ── */
.activity-feed { background:var(--surface); border-radius:var(--radius-md); border:1px solid var(--border); overflow:hidden; }
.activity-item { display:flex; gap:1.5rem; padding:1.5rem 2rem; border-bottom:1px solid var(--border); transition:var(--transition); align-items:center;}
.activity-item:last-child { border-bottom:none; }
.activity-item.unread { background-color:var(--primary-light); }
.activity-dot { width:45px; height:45px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.6rem; flex-shrink:0; color:#fff; }
.activity-dot.danger { background:var(--danger); box-shadow:0 0 15px rgba(235,87,87,0.3); }
.activity-dot.warning { background:var(--warning); box-shadow:0 0 15px rgba(242,201,76,0.3); }
.activity-dot.success { background:var(--success); box-shadow:0 0 15px rgba(39,174,96,0.3); }
.activity-dot.info { background:var(--primary); box-shadow:0 0 15px var(--primary-light); }

.activity-content { flex:1; }
.activity-title { font-size:1.4rem; font-weight:700; color:var(--text-primary); margin:0 0 0.3rem 0; display:flex; align-items:center; gap:0.8rem; }
.activity-desc { font-size:1.15rem; color:var(--text-secondary); margin:0; line-height:1.4; }
.activity-meta { font-size:1rem; color:var(--text-muted); margin-top:0.4rem; display:flex; gap:1rem; }
.activity-actions { display:flex; gap:0.8rem; opacity:0.6; transition:opacity 0.2s; }
.activity-item:hover .activity-actions { opacity:1; }

.new-badge { font-size:0.85rem; background:var(--danger); color:white; padding:0.2rem 0.6rem; border-radius:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; }
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-danger { background:var(--danger);color:#fff; }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }
.btn-icon { padding:0.8rem; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; }

/* ── Filter Tabs ── */
.filter-tabs { display:flex;gap:.8rem;flex-wrap:wrap; margin-bottom: 1.5rem; }
.filter-tabs .ftab { padding:.6rem 1.4rem;border-radius:20px;font-size:1.1rem;font-weight:600;cursor:pointer;
  border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition); }
.filter-tabs .ftab.active, .filter-tabs .ftab:hover { background:var(--primary);color:#fff;border-color:var(--primary); box-shadow: 0 4px 10px var(--primary-light); }

/* ── Toast ── */
#toastWrap { position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem; }
.toast-msg { padding:1.2rem 2rem; border-radius:var(--radius-sm); background:var(--surface); box-shadow:var(--shadow-lg); border-left:5px solid var(--primary); font-size:1.2rem; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:1rem; animation:fadePop .3s ease; }

@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
@media(max-width:900px) { .top-grid { grid-template-columns:1fr; } }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-bell"></i> Notifications Center</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content" style="animation:fadePop .35s ease;">
        
        <div class="staff-hero">
            <div class="staff-hero-avatar"><i class="fas fa-satellite-dish"></i></div>
            <div class="staff-hero-info">
                <h2>Advanced Notification Hub</h2>
                <p>View, manage, and action all critical system alerts and events securely.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem;">
                <button class="btn btn-primary" onclick="markAllAsRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
                <button class="btn btn-danger" onclick="clearAllNotifications()"><i class="fas fa-trash-alt"></i> Clear All</button>
            </div>
        </div>

        <div class="top-grid">
            <div class="card">
                <div class="card-body" style="padding:1.5rem; height:100%;">
                    <div class="stat-box">
                        <div class="stat-box-val" id="unreadCountBadge"><?php echo $tot_unread; ?></div>
                        <div class="stat-box-lbl">Unread Alerts</div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Alert Distribution</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="notifChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="padding-bottom:1rem; border-bottom:none;">
                <div class="filter-tabs" style="margin:0;">
                    <button class="ftab active" data-filter="all">All Logs</button>
                    <button class="ftab" data-filter="security">Security</button>
                    <button class="ftab" data-filter="approval">Approvals</button>
                    <button class="ftab" data-filter="inventory">Inventory</button>
                    <button class="ftab" data-filter="system">System</button>
                </div>
            </div>
            
            <?php if (empty($notifications)): ?>
                <div style="text-align:center;padding:5rem;color:var(--text-muted);">
                    <i class="fas fa-bell-slash" style="font-size:5rem;color:var(--border);margin-bottom:1.5rem;display:block;"></i>
                    <h2 style="font-size:2rem;font-weight:700;color:var(--text-primary);margin-bottom:0.5rem;">No Notifications</h2>
                    <p style="font-size:1.3rem;">You're all caught up. The system is operating normally.</p>
                </div>
            <?php else: ?>
                <div class="activity-feed" id="activityFeed">
                    <?php 
                    foreach ($notifications as $n): 
                        $nid = $n['notification_id'] ?? $n['id'] ?? null;
                        $is_read = (isset($n['is_read']) && $n['is_read'] == 1);
                        
                        $typeStr = strtolower(isset($n['type']) ? $n['type'] : 'system');
                        $titleStr = isset($n['title']) ? $n['title'] : ucfirst($typeStr);
                        
                        $iconClass = 'fa-info';
                        $dotColor = 'info';
                        $filterCat = 'system';
                        
                        if (strpos($typeStr, 'security') !== false || (isset($n['priority']) && $n['priority'] === 'high')) {
                            $iconClass = 'fa-shield-alt'; $dotColor = 'danger'; $filterCat = 'security';
                        } elseif (strpos($typeStr, 'inventory') !== false || strpos($typeStr, 'stock') !== false) {
                            $iconClass = 'fa-boxes'; $dotColor = 'warning'; $filterCat = 'inventory';
                        } elseif (strpos($typeStr, 'approval') !== false) {
                            $iconClass = 'fa-user-check'; $dotColor = 'success'; $filterCat = 'approval';
                        } else {
                            $iconClass = 'fa-bell';
                        }
                    ?>
                    <div class="activity-item <?php echo $is_read ? '' : 'unread'; ?>" id="notif_<?php echo $nid; ?>" data-category="<?php echo $filterCat; ?>">
                        <div class="activity-dot <?php echo $dotColor; ?>">
                            <i class="fas <?php echo $iconClass; ?>"></i>
                        </div>
                        <div class="activity-content">
                            <h4 class="activity-title">
                                <?php echo htmlspecialchars($titleStr); ?>
                                <?php if (!$is_read): ?><span class="new-badge">New</span><?php endif; ?>
                            </h4>
                            <p class="activity-desc"><?php echo htmlspecialchars($n['message'] ?? ''); ?></p>
                            <div class="activity-meta">
                                <span><i class="fas fa-clock"></i> <?php echo date('M d, Y g:i A', strtotime($n['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="activity-actions">
                            <?php if (!$is_read && is_string($nid)): ?>
                                <!-- Low stock alerts can't really be marked read individually without DB record -->
                            <?php elseif (!$is_read): ?>
                                <button class="btn-icon btn-ghost mark-btn" onclick="markAsRead('<?php echo $nid; ?>')" title="Mark as Read">
                                    <i class="fas fa-check" style="color:var(--success);"></i>
                                </button>
                            <?php endif; ?>
                            <?php if (is_numeric($nid)): ?>
                            <button class="btn-icon btn-ghost del-btn" onclick="deleteNotification('<?php echo $nid; ?>')" title="Delete">
                                <i class="fas fa-trash" style="color:var(--danger);"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<div id="toastWrap"></div>

<script>
function showToast(msg, type='success') {
    const toast = document.createElement('div');
    toast.className = `toast-msg toast-${type}`;
    toast.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> <span>${msg}</span>`;
    document.getElementById('toastWrap').appendChild(toast);
    setTimeout(()=> { toast.style.opacity='0'; setTimeout(()=>toast.remove(),300); }, 3000);
}

// Chart.js Integration
const ctx = document.getElementById('notifChart');
if (ctx) {
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Security', 'Approvals', 'Inventory', 'System'],
            datasets: [{
                data: [
                    <?php echo $cat_counts['security']; ?>,
                    <?php echo $cat_counts['approval']; ?>,
                    <?php echo $cat_counts['inventory']; ?>,
                    <?php echo $cat_counts['system']; ?>
                ],
                backgroundColor: ['#EB5757', '#27AE60', '#F2C94C', '#2F80ED'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'right', labels: { font: { family: "'Poppins', sans-serif", size: 12 } } }
            }
        }
    });
}

// Filter Logic for Activity Feed
$('.ftab').on('click', function() {
    $('.ftab').removeClass('active');
    $(this).addClass('active');
    const filter = $(this).data('filter');
    
    if (filter === 'all') {
        $('.activity-item').fadeIn(200);
    } else {
        $('.activity-item').hide();
        $(`.activity-item[data-category="${filter}"]`).fadeIn(200);
    }
});

async function markAsRead(id) {
    try {
        const fd = new FormData();
        fd.append('action', 'mark_read');
        fd.append('id', id);
        const res = await fetch('notification_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const row = document.getElementById('notif_'+id);
            if (row) {
                row.classList.remove('unread');
                const badge = row.querySelector('.new-badge');
                if (badge) badge.remove();
                const btn = row.querySelector('.mark-btn');
                if (btn) btn.remove();
                
                // Update counter
                const countBadge = document.getElementById('unreadCountBadge');
                let count = parseInt(countBadge.textContent);
                if (count > 0) countBadge.textContent = count - 1;
                
                showToast('Notification marked as read');
            }
        }
    } catch (e) { console.error(e); }
}

async function deleteNotification(id) {
    if (!confirm('Delete this notification permanently?')) return;
    try {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        const res = await fetch('notification_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const row = $('#notif_'+id);
            if (row.hasClass('unread')) {
                const countBadge = document.getElementById('unreadCountBadge');
                let count = parseInt(countBadge.textContent);
                if (count > 0) countBadge.textContent = count - 1;
            }
            row.fadeOut(300, function() { $(this).remove(); });
            showToast('Notification deleted');
        }
    } catch (e) { console.error(e); }
}

async function markAllAsRead() {
    try {
        const fd = new FormData();
        fd.append('action', 'mark_all_read');
        const res = await fetch('notification_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('All notifications marked as read');
            setTimeout(() => window.location.reload(), 1000);
        }
    } catch (e) { console.error(e); }
}

async function clearAllNotifications() {
    if (!confirm('Are you sure you want to delete ALL notifications? This cannot be undone.')) return;
    try {
        const fd = new FormData();
        fd.append('action', 'clear_all');
        const res = await fetch('notification_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('All notifications cleared');
            setTimeout(() => window.location.reload(), 1000);
        }
    } catch (e) { console.error(e); }
}

const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
