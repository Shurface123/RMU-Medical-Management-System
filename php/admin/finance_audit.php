<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'finance_audit';
$page_title  = 'Finance Audit Trail';
include '../includes/_sidebar.php';

// Fetch Logs
$logs = [];
$q = mysqli_query($conn, "
    SELECT f.*, 
           u.name as user_name,
           u.email as user_email
    FROM finance_audit_trail f
    LEFT JOIN users u ON f.user_id = u.id
    ORDER BY f.created_at DESC
    LIMIT 200
");
if ($q) while ($row = mysqli_fetch_assoc($q)) $logs[] = $row;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-history"></i> Finance Audit Trail</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header" style="margin-bottom:2rem;">
            <div>
                <h1>Finance Audit Log</h1>
                <p>Read-only timeline of all financial transactions, waivers, and approvals.</p>
            </div>
            <div>
                <button class="btn-icon btn btn-outline" onclick="window.print()"><span class="btn-text">
                    <i class="fas fa-print"></i> Print Log
                </span></button>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-list-ul"></i> System Activity (Last 200 records)</h3>
            </div>
            <div class="adm-table-wrap">
                <?php if (empty($logs)): ?>
                    <div style="padding:4rem;text-align:center;color:var(--text-muted);">
                        <i class="fas fa-file-signature" style="font-size:3rem;margin-bottom:1rem;opacity:0.5;"></i>
                        <p>No audit records found.</p>
                    </div>
                <?php else: ?>
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Module / Action</th>
                            <th>User (Operator)</th>
                            <th>Description</th>
                            <th>Changes (JSON)</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td style="white-space:nowrap;">
                                <strong><?php echo date('M d, Y', strtotime($l['created_at'])); ?></strong><br>
                                <span style="color:var(--text-muted);font-size:.85rem;"><?php echo date('g:i A', strtotime($l['created_at'])); ?></span>
                            </td>
                            <td>
                                <span class="adm-badge adm-badge-info" style="margin-bottom:.3rem;"><?php echo htmlspecialchars($l['module']); ?></span><br>
                                <strong><?php echo htmlspecialchars($l['action']); ?></strong>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($l['user_name'] ?? 'System Process'); ?></div>
                                <div style="font-size:.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($l['user_email'] ?? ''); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($l['description']); ?></td>
                            <td style="font-size:.8rem;max-width:250px;">
                                <div style="background:var(--bg-hover);padding:.5rem;border-radius:4px;overflow-x:auto;">
                                    <?php 
                                    $nv = json_decode($l['new_values'] ?? '{}', true);
                                    if ($nv) {
                                        foreach ($nv as $k => $v) {
                                            echo "<div style='margin-bottom:2px;'><strong style='color:var(--text-secondary);'>$k:</strong> " . htmlspecialchars(is_array($v) ? json_encode($v) : $v) . "</div>";
                                        }
                                    } else {
                                        echo "<span style='color:var(--text-muted);'>No change data</span>";
                                    }
                                    ?>
                                </div>
                            </td>
                            <td style="font-size:.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($l['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>
<script>
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    document.getElementById('themeIcon').className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
</body>
</html>
