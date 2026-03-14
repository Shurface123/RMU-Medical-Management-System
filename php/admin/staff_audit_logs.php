<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'staff_audit_logs';
$page_title  = 'Staff Audit Logs';
include '../includes/_sidebar.php';

// Pagination and Filtering
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$module_f = isset($_GET['module']) ? trim($_GET['module']) : '';
$action_f = isset($_GET['action_type']) ? trim($_GET['action_type']) : '';
$search   = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build WHERE clause
$where_parts = ["u.user_role NOT IN ('patient', 'admin')"]; // Only track staff/doctors/nurses
if ($module_f) $where_parts[] = "a.module = '" . mysqli_real_escape_string($conn, $module_f) . "'";
if ($action_f) $where_parts[] = "a.action_type = '" . mysqli_real_escape_string($conn, $action_f) . "'";
if ($search)   $where_parts[] = "(u.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR a.description LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";

$where = 'WHERE ' . implode(' AND ', $where_parts);

// Count total for pagination
$c_sql = "SELECT COUNT(*) FROM staff_audit_trail a JOIN users u ON a.user_id = u.id $where";
$total_logs = mysqli_fetch_row(mysqli_query($conn, $c_sql))[0] ?? 0;
$total_pages = ceil($total_logs / $limit);

// Fetch logs
$logs = [];
$q_logs = mysqli_query($conn, "
    SELECT a.id, a.action_type, a.module, a.description, a.created_at,
           u.name as user_name, u.user_role as role
    FROM staff_audit_trail a
    JOIN users u ON a.user_id = u.id
    $where
    ORDER BY a.created_at DESC
    LIMIT $limit OFFSET $offset
");
if ($q_logs) while ($row = mysqli_fetch_assoc($q_logs)) $logs[] = $row;

// Fetch unique modules and actions for filter dropdowns
$modules = [];
$mq = mysqli_query($conn, "SELECT DISTINCT module FROM staff_audit_trail WHERE module IS NOT NULL");
if ($mq) while ($r = mysqli_fetch_row($mq)) $modules[] = $r[0];

$action_types = [];
$aq = mysqli_query($conn, "SELECT DISTINCT action_type FROM staff_audit_trail WHERE action_type IS NOT NULL");
if ($aq) while ($r = mysqli_fetch_row($aq)) $action_types[] = $r[0];
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-history"></i> Staff Audit Trail</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div>
                <h1>System Security & Audit Log</h1>
                <p>Track all actions securely logged by system staff and medical personnel.</p>
            </div>
            <div style="background:var(--bg-card);padding:.75rem 1.5rem;border-radius:12px;box-shadow:var(--shadow-sm);border:1px solid var(--border);">
                <div style="font-size:.85rem;color:var(--text-muted);">Total Events Logged</div>
                <div style="font-size:1.6rem;font-weight:700;color:var(--primary);"><?php echo number_format($total_logs); ?></div>
            </div>
        </div>

        <!-- Filters -->
        <form method="get" class="adm-card" style="padding:1rem 1.5rem;display:flex;flex-wrap:wrap;gap:1.5rem;align-items:flex-end;margin-bottom:2rem;">
            <div style="flex:1;min-width:250px;">
                <label style="display:block;font-size:.85rem;color:var(--text-muted);margin-bottom:.4rem;">Search Staff Name or Description</label>
                <input type="text" name="q" class="adm-search-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="E.g., John Doe... or Task ID...">
            </div>
            <div style="width:200px;">
                <label style="display:block;font-size:.85rem;color:var(--text-muted);margin-bottom:.4rem;">Module Filter</label>
                <select name="module" class="adm-search-input">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>" <?php if ($module_f===$m) echo 'selected'; ?>><?php echo ucfirst($m); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="width:200px;">
                <label style="display:block;font-size:.85rem;color:var(--text-muted);margin-bottom:.4rem;">Event Action</label>
                <select name="action_type" class="adm-search-input">
                    <option value="">All Actions</option>
                    <?php foreach ($action_types as $a): ?>
                        <option value="<?php echo htmlspecialchars($a); ?>" <?php if ($action_f===$a) echo 'selected'; ?>><?php echo htmlspecialchars($a); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:1rem;">
                <button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-filter"></i> Apply Filter</button>
                <a href="staff_audit_logs.php" class="adm-btn adm-btn-back"><i class="fas fa-redo"></i> Reset</a>
            </div>
        </form>

        <!-- Log Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-list"></i> Activity Feed 
                    <span style="font-size:.85rem;color:var(--text-muted);font-weight:normal;margin-left:1rem;">Showing page <?php echo $page; ?> of <?php echo max(1, $total_pages); ?></span>
                </h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Staff Member</th>
                            <th>Role</th>
                            <th>Module</th>
                            <th>Action Type</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="6" style="padding:3rem;text-align:center;color:var(--text-muted);">No log entries found.</td></tr>
                        <?php else: foreach ($logs as $l): ?>
                        <tr>
                            <td style="white-space:nowrap;color:var(--text-secondary);font-size:.9rem;">
                                <?php echo date('d M Y - g:i:s A', strtotime($l['created_at'])); ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($l['user_name']); ?></strong></td>
                            <td><span class="adm-badge" style="background:rgba(0,0,0,0.05);color:var(--text);"><?php echo ucfirst(str_replace('_',' ',$l['role'])); ?></span></td>
                            <td><span class="adm-badge adm-badge-info"><?php echo htmlspecialchars($l['module']); ?></span></td>
                            <td>
                                <?php 
                                    $cl = 'primary';
                                    if (str_contains($l['action_type'], 'fail') || str_contains($l['action_type'], 'delete') || str_contains($l['action_type'], 'blocked')) $cl = 'danger';
                                    if (str_contains($l['action_type'], 'success') || str_contains($l['action_type'], 'approve') || str_contains($l['action_type'], 'complete')) $cl = 'success';
                                    if (str_contains($l['action_type'], 'update') || str_contains($l['action_type'], 'edit')) $cl = 'warning';
                                ?>
                                <span class="adm-badge adm-badge-<?php echo $cl; ?>"><?php echo htmlspecialchars($l['action_type']); ?></span>
                            </td>
                            <td style="max-width:350px;">
                                <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($l['description']); ?>">
                                    <?php echo htmlspecialchars($l['description']); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="display:flex;justify-content:center;gap:.5rem;margin-top:2rem;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&q=<?php echo urlencode($search); ?>&module=<?php echo urlencode($module_f); ?>&action_type=<?php echo urlencode($action_f); ?>" class="adm-btn adm-btn-back"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            
            <button class="adm-btn adm-btn-primary" disabled>Page <?php echo $page; ?></button>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&q=<?php echo urlencode($search); ?>&module=<?php echo urlencode($module_f); ?>&action_type=<?php echo urlencode($action_f); ?>" class="adm-btn adm-btn-back"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</main>
<script>
const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
</body>
</html>
