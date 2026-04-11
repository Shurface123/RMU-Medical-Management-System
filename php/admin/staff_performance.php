<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'staff_performance';
$page_title  = 'Staff Performance & KPIs';
include '../includes/_sidebar.php';

// Prepare KPI Data by Staff Member
$staff_kpis = [];
$sql = "
    SELECT s.id, s.employee_id, s.department, u.name, u.user_role as role,
           -- Total Tasks assigned
           (SELECT COUNT(*) FROM staff_tasks t WHERE t.assigned_to = s.id) as total_tasks,
           -- Tasks completed
           (SELECT COUNT(*) FROM staff_tasks t WHERE t.assigned_to = s.id AND t.status = 'completed') as completed_tasks,
           -- Latest Performance Rating (if available)
           (SELECT rating FROM staff_performance p WHERE p.staff_id = s.id ORDER BY p.created_at DESC LIMIT 1) as latest_rating,
           (SELECT kpi_score FROM staff_performance p WHERE p.staff_id = s.id ORDER BY p.created_at DESC LIMIT 1) as kpi_score
    FROM staff s
    JOIN users u ON s.user_id = u.id
    WHERE u.user_role NOT IN ('admin','patient') AND u.is_active = 1
    ORDER BY u.name ASC
";
$q_staff = mysqli_query($conn, $sql);
if ($q_staff) {
    while ($row = mysqli_fetch_assoc($q_staff)) {
        // Calculate completion rate dynamically
        $comp_rate = $row['total_tasks'] > 0 ? round(($row['completed_tasks'] / $row['total_tasks']) * 100) : 100;
        $row['completion_rate'] = $comp_rate;
        $staff_kpis[] = $row;
    }
}
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-chart-line"></i> Staff Performance & KPIs</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header" style="margin-bottom:2rem;">
            <div>
                <h1>KPIs & Performance Tracking</h1>
                <p>Monitor completion rates and performance ratings for all hospital staff.</p>
            </div>
            <button class="btn-icon btn btn-primary" onclick="alert('Review cycle initiation is not fully configured in this demo.')"><span class="btn-text">
                <i class="fas fa-star-half-alt"></i> Start Review Cycle
            </span></button>
        </div>

        <!-- Performance Dashboard Grid -->
        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-award"></i> Hospital-Wide Staff Metrics</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Role / Dept</th>
                            <th>Tasks Activity</th>
                            <th>Completion Rate</th>
                            <th>Overall KPI Score</th>
                            <th>Latest Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staff_kpis)): ?>
                            <tr><td colspan="7" style="padding:2rem;text-align:center;color:var(--text-muted);">No staff found.</td></tr>
                        <?php else: foreach ($staff_kpis as $kp): 
                            // Bar colors based on rate
                            $bar_color = $kp['completion_rate'] >= 85 ? 'var(--success)' : ($kp['completion_rate'] >= 60 ? 'var(--warning)' : 'var(--danger)');
                            
                            // Rating stars
                            $rating = (float)($kp['latest_rating'] ?? 0);
                            $stars = '';
                            for ($i=1; $i<=5; $i++) {
                                if ($i <= $rating) $stars .= '<i class="fas fa-star" style="color:#F1C40F;"></i>';
                                else if ($i - 0.5 <= $rating) $stars .= '<i class="fas fa-star-half-alt" style="color:#F1C40F;"></i>';
                                else $stars .= '<i class="far fa-star" style="color:#E0E0E0;"></i>';
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($kp['name']); ?></strong><br>
                                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($kp['employee_id']); ?></small>
                            </td>
                            <td>
                                <div><span class="adm-badge adm-badge-primary"><?php echo ucfirst(str_replace('_',' ',$kp['role'])); ?></span></div>
                                <div style="font-size:.8rem;color:var(--text-muted);margin-top:.3rem;"><i class="fas fa-building" style="margin-right:4px;"></i><?php echo htmlspecialchars($kp['department'] ?? 'General'); ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?php echo $kp['completed_tasks'] . ' / ' . $kp['total_tasks']; ?></div>
                                <div style="font-size:.75rem;color:var(--text-muted);">Completed</div>
                            </td>
                            <td style="width:150px;">
                                <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:.2rem;">
                                    <span>Rate</span>
                                    <strong style="color:<?php echo $bar_color; ?>"><?php echo $kp['completion_rate']; ?>%</strong>
                                </div>
                                <div style="width:100%;height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
                                    <div style="width:<?php echo $kp['completion_rate']; ?>%;height:100%;background:<?php echo $bar_color; ?>;"></div>
                                </div>
                            </td>
                            <td>
                                <?php if ($kp['kpi_score']): ?>
                                    <strong style="font-size:1.1rem;color:var(--text);"><?php echo htmlspecialchars($kp['kpi_score']); ?></strong> <span style="font-size:.8rem;color:var(--text-muted);">/ 100</span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-style:italic;">Not Reviewed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="margin-bottom:.2rem;font-size:1.1rem;"><?php echo $stars; ?></div>
                                <?php if (!$rating): ?>
                                    <span style="font-size:.75rem;color:var(--text-muted);">No Rating</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-outline btn-icon btn btn-back btn-sm" title="Add Performance Review (Demo)" onclick="alert('Feature under construction.')"><span class="btn-text">
                                    <i class="fas fa-pen-nib"></i> Review
                                </span></button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
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
