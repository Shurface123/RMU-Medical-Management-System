<?php
session_start();
require_once '../db_conn.php';
require_once '../classes/AuditLogger.php';
require_once '../includes/auth_middleware.php';

// Check admin authentication
enforceSingleDashboard('admin');

$active_page = 'audit_logs';
$page_title = 'System Audit Logs';

$auditLogger = new AuditLogger($conn);

// Get filter parameters
$filterAction = $_GET['action'] ?? '';
$filterUser = $_GET['user'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build filter array
$filters = [];
if ($filterAction) $filters['action'] = $filterAction;
if ($filterUser) $filters['user_id'] = $filterUser;
if ($filterDateFrom) $filters['date_from'] = $filterDateFrom;
if ($filterDateTo) $filters['date_to'] = $filterDateTo;

// Get audit logs
$logs = $auditLogger->getAuditLogs($filters, $perPage, $offset);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM audit_log WHERE 1=1";
$params = [];
$types = '';

if ($filterAction) {
    $countQuery .= " AND action = ?";
    $params[] = $filterAction;
    $types .= 's';
}
if ($filterUser) {
    $countQuery .= " AND user_id = ?";
    $params[] = $filterUser;
    $types .= 'i';
}
if ($filterDateFrom) {
    $countQuery .= " AND created_at >= ?";
    $params[] = $filterDateFrom . ' 00:00:00';
    $types .= 's';
}
if ($filterDateTo) {
    $countQuery .= " AND created_at <= ?";
    $params[] = $filterDateTo . ' 23:59:59';
    $types .= 's';
}

$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalResult = $stmt->get_result();
$totalCount = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalCount / $perPage);

// Get statistics
$stats = $auditLogger->getAuditStatistics();

// Get unique actions for filter
$actionsQuery = "SELECT DISTINCT action FROM audit_log ORDER BY action";
$actionsResult = mysqli_query($conn, $actionsQuery);

// Get users for filter
$usersQuery = "SELECT id, user_name, name FROM users ORDER BY user_name";
$usersResult = mysqli_query($conn, $usersQuery);

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $auditLogger->exportToCSV($filters);
    exit();
}

include '../includes/_sidebar.php';
?>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">

<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #3b82f6; /* Blue for logs */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #06b6d4;
  --purple: #8b5cf6;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #1e3a8a);
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Stat Mini Cards ── */
.stat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2.5rem; }
.stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);
  padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);cursor:pointer;box-shadow:var(--shadow-sm); }
.stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); border-color:var(--primary); }
.stat-mini-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:1.8rem;background:var(--surface-2);color:var(--text-secondary); }
.stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--primary); }
.stat-mini-val.success { color:var(--success); }
.stat-mini-val.warning { color:var(--warning); }
.stat-mini-val.info { color:var(--info); }
.stat-mini-lbl { font-size:1.15rem;font-weight:600;color:var(--text-secondary);margin-top:.6rem; text-transform:uppercase; letter-spacing:0.05em; }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; background:var(--surface-2); }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Form Controls ── */
.form-row { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:1.6rem; }
.form-group { margin-bottom:1.6rem; }
.form-group label { display:block;font-size:1.1rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em; }
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.15rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.1rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:0.95rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Pagination ── */
.pagination { display:flex;justify-content:center;gap:10px;margin-top:2rem; }
.pagination a { padding:0.6rem 1rem;background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;color:var(--text-secondary);font-weight:600;transition:var(--transition); }
.pagination a.active { background:var(--primary);color:#fff;border-color:var(--primary); }
.pagination a:hover:not(.active) { border-color:var(--primary);color:var(--primary); }

/* ── Action Dots ── */
.activity-dot { width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:8px; }
.dot-login { background:var(--info); box-shadow:0 0 0 3px rgba(6,182,212,0.2); }
.dot-create { background:var(--success); box-shadow:0 0 0 3px rgba(16,185,129,0.2); }
.dot-update { background:var(--warning); box-shadow:0 0 0 3px rgba(245,158,11,0.2); }
.dot-delete { background:var(--danger); box-shadow:0 0 0 3px rgba(239,68,68,0.2); }
.dot-config { background:var(--purple); box-shadow:0 0 0 3px rgba(139,92,246,0.2); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-success { background:var(--success); color:#fff; }
.btn-danger { background:var(--danger); color:#fff; }
.btn-outline { background:transparent; border:1.5px solid var(--border); color:var(--text-secondary); }
.btn-outline:hover { background:var(--surface-2); color:var(--text-primary); border-color:var(--text-muted); }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-clipboard-list"></i> System Audit Logs</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadeIn .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-shield-alt hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-search"></i></div>
            <div class="staff-hero-info">
                <h2>Security & Audit Trail</h2>
                <p>Immutable system logs recording user activities, configurations, and critical changes.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <a href="?export=csv<?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user=' . urlencode($filterUser) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?>" class="btn" style="background:#fff; color:var(--primary);">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--primary); background:var(--primary-light);"><i class="fas fa-history"></i></div>
                <div class="stat-mini-val"><?= number_format($stats['total_events']) ?></div>
                <div class="stat-mini-lbl">Total Events</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--info); background:rgba(6,182,212,0.15);"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-mini-val info"><?= number_format($stats['today_events']) ?></div>
                <div class="stat-mini-lbl">Today's Events</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--danger); background:rgba(239,68,68,0.15);"><i class="fas fa-user-lock"></i></div>
                <div class="stat-mini-val" style="color:var(--danger);"><?= number_format($stats['failed_logins_24h']) ?></div>
                <div class="stat-mini-lbl">Failed Logins (24h)</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success); background:rgba(16,185,129,0.15);"><i class="fas fa-users"></i></div>
                <div class="stat-mini-val success"><?= number_format($stats['unique_users_24h']) ?></div>
                <div class="stat-mini-lbl">Active Users (24h)</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-filter" style="color:var(--primary);"></i> Log Filters</h3>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Action Type</label>
                            <select name="action" class="form-control">
                                <option value="">All Actions</option>
                                <?php while ($action = mysqli_fetch_assoc($actionsResult)): ?>
                                    <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $filterAction === $action['action'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($action['action']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>User</label>
                            <select name="user" class="form-control">
                                <option value="">All Users</option>
                                <?php while ($user = mysqli_fetch_assoc($usersResult)): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $filterUser == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['user_name']) . ' (' . htmlspecialchars($user['name']) . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filterDateFrom); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filterDateTo); ?>">
                        </div>
                    </div>
                    <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                        <a href="audit_log_viewer.php" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div style="overflow-x:auto;">
                <table class="stf-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Timestamp</th>
                            <th>User Context</th>
                            <th>Action</th>
                            <th>Target Table</th>
                            <th>Rec ID</th>
                            <th>IP Address</th>
                            <th>Detailed Signature</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 4rem; color: var(--text-muted);">
                                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; color:var(--border);"></i>
                                    <p style="font-size:1.2rem; font-weight:600;">No audit logs found matching your criteria</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="color:var(--text-muted); font-size:0.9rem;">#<?= $log['id'] ?></td>
                                    <td>
                                        <div style="font-weight:600; color:var(--text-primary);"><?= date('M j, Y', strtotime($log['created_at'])) ?></div>
                                        <div style="font-size:0.9rem; color:var(--text-secondary);"><i class="far fa-clock"></i> <?= date('g:i A', strtotime($log['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight:700; color:var(--text-primary);"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></div>
                                        <?php if(!empty($log['name'])): ?>
                                            <div style="font-size:0.9rem; color:var(--text-muted);"><?= htmlspecialchars($log['name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $dotClass = 'dot-login';
                                        if (strpos($log['action'], 'create') !== false) $dotClass = 'dot-create';
                                        elseif (strpos($log['action'], 'update') !== false) $dotClass = 'dot-update';
                                        elseif (strpos($log['action'], 'delete') !== false) $dotClass = 'dot-delete';
                                        elseif (strpos($log['action'], 'config') !== false) $dotClass = 'dot-config';
                                        ?>
                                        <div style="display:flex; align-items:center;">
                                            <span class="activity-dot <?= $dotClass ?>"></span>
                                            <span style="font-weight:600; font-size:0.9rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-primary);"><?= htmlspecialchars($log['action']) ?></span>
                                        </div>
                                    </td>
                                    <td style="font-family:monospace; color:var(--primary); font-size:0.9rem;"><?= htmlspecialchars($log['table_name'] ?? '-') ?></td>
                                    <td><span style="background:var(--surface-2); padding:0.2rem 0.5rem; border-radius:4px; font-size:0.9rem;"><?= htmlspecialchars($log['record_id'] ?? '-') ?></span></td>
                                    <td style="font-family:monospace; color:var(--text-secondary); font-size:0.9rem;"><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--text-secondary); font-size:0.9rem;" title="<?= htmlspecialchars($log['details'] ?? '') ?>">
                                        <?= htmlspecialchars($log['details'] ?? '-') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="padding: 2rem; border-top:1px solid var(--border); display:flex; flex-direction:column; align-items:center; gap:1rem; background:var(--surface-2);">
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user=' . urlencode($filterUser) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?>"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user=' . urlencode($filterUser) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user=' . urlencode($filterUser) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?>"><i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <div style="color:var(--text-muted); font-size:0.95rem; font-weight:600;">
                        Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalCount) ?> of <?= number_format($totalCount) ?> records
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</main>

<script>
    const themeIcon = document.getElementById('themeIcon');
    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const html = document.documentElement;
        const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
        html.setAttribute('data-theme', t);
        localStorage.setItem('rmu_theme', t);
        if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
