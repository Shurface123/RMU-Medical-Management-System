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

// Calculate Global Metrics
$avg_completion = count($staff_kpis) > 0 ? round(array_sum(array_column($staff_kpis, 'completion_rate')) / count($staff_kpis)) : 0;
$top_performers = count(array_filter($staff_kpis, fn($k) => $k['completion_rate'] >= 90));
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">

<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #8b5cf6; /* Violet for performance */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
  --gold: #f1c40f;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #5b21b6);
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Stat Mini Cards ── */
.stat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2.5rem; }
.stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);box-shadow:var(--shadow-sm); }
.stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); border-color:var(--primary); }
.stat-mini-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:1.8rem;background:var(--surface-2);color:var(--text-secondary); }
.stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--primary); }
.stat-mini-val.success { color:var(--success); }
.stat-mini-val.warning { color:var(--warning); }
.stat-mini-lbl { font-size:1.15rem;font-weight:600;color:var(--text-secondary);margin-top:.6rem; text-transform:uppercase; letter-spacing:0.05em; }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; background:var(--surface-2); }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Progress Bars ── */
.prog-wrap { width:100%; min-width:140px; }
.prog-bar-bg { width:100%; height:8px; background:var(--surface-2); border-radius:4px; overflow:hidden; margin-top:6px; border:1px solid var(--border); }
.prog-bar-fill { height:100%; transition: width 0.6s ease; }

/* ── Badges ── */
.badge { display:inline-block; padding:0.3rem 0.8rem; border-radius:12px; font-size:0.9rem; font-weight:700; text-transform:uppercase; border:1px solid transparent; }
.badge-primary { background:var(--primary-light); color:var(--primary); border-color:rgba(139,92,246,0.3); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }
</style>

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
    
    <div class="adm-content" style="animation:fadeIn .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-award hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-star"></i></div>
            <div class="staff-hero-info">
                <h2>KPIs & Performance Tracking</h2>
                <p>Analyze staff efficiency, task completion rates, and historical performance metrics across all departments.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <button class="btn" style="background:#fff; color:var(--primary);" onclick="alert('Review cycle initiation is not fully configured.')">
                    <i class="fas fa-calendar-plus"></i> Start Review Cycle
                </button>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--primary); background:var(--primary-light);"><i class="fas fa-users"></i></div>
                <div class="stat-mini-val"><?= count($staff_kpis) ?></div>
                <div class="stat-mini-lbl">Active Staff</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success); background:rgba(16,185,129,0.15);"><i class="fas fa-tasks"></i></div>
                <div class="stat-mini-val success"><?= $avg_completion ?>%</div>
                <div class="stat-mini-lbl">Avg. Completion</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--warning); background:rgba(245,158,11,0.15);"><i class="fas fa-medal"></i></div>
                <div class="stat-mini-val warning"><?= $top_performers ?></div>
                <div class="stat-mini-lbl">Top Performers</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-check" style="color:var(--primary);"></i> Staff Efficiency Ledger</h3>
            </div>
            <div class="card-body" style="padding:1rem;">
                <table class="stf-table" id="performanceTable">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Role / Dept</th>
                            <th>Tasks Activity</th>
                            <th>Completion Rate</th>
                            <th>Overall KPI</th>
                            <th>Latest Rating</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staff_kpis)): ?>
                            <tr><td colspan="7" style="padding:4rem; text-align:center; color:var(--text-muted);">No staff records found for performance analysis.</td></tr>
                        <?php else: foreach ($staff_kpis as $kp): 
                            $bar_color = $kp['completion_rate'] >= 85 ? 'var(--success)' : ($kp['completion_rate'] >= 60 ? 'var(--warning)' : 'var(--danger)');
                            
                            $rating = (float)($kp['latest_rating'] ?? 0);
                            $stars = '';
                            for ($i=1; $i<=5; $i++) {
                                if ($i <= $rating) $stars .= '<i class="fas fa-star" style="color:var(--gold);"></i>';
                                else if ($i - 0.5 <= $rating) $stars .= '<i class="fas fa-star-half-alt" style="color:var(--gold);"></i>';
                                else $stars .= '<i class="far fa-star" style="color:var(--border);"></i>';
                            }
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:0.8rem;">
                                    <div style="width:40px; height:40px; border-radius:50%; background:var(--surface-2); color:var(--text-secondary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.2rem; border:2px solid var(--border);">
                                        <?= strtoupper(substr($kp['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <strong style="font-size:1.15rem; color:var(--text-primary);"><?= htmlspecialchars($kp['name']) ?></strong>
                                        <div style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($kp['employee_id']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?= ucfirst(str_replace('_',' ',$kp['role'])) ?></span>
                                <div style="font-size:0.9rem; color:var(--text-muted); margin-top:4px;"><i class="fas fa-building" style="margin-right:4px;"></i><?= htmlspecialchars($kp['department'] ?? 'General') ?></div>
                            </td>
                            <td>
                                <div style="font-weight:700; color:var(--text-primary); font-size:1.1rem;"><?= $kp['completed_tasks'] ?> / <?= $kp['total_tasks'] ?></div>
                                <div style="font-size:0.85rem; color:var(--text-muted);">Completed Tasks</div>
                            </td>
                            <td>
                                <div class="prog-wrap">
                                    <div style="display:flex; justify-content:space-between; font-size:0.9rem; font-weight:700;">
                                        <span style="color:var(--text-muted);">Efficiency</span>
                                        <span style="color:<?= $bar_color ?>;"><?= $kp['completion_rate'] ?>%</span>
                                    </div>
                                    <div class="prog-bar-bg">
                                        <div class="prog-bar-fill" style="width:<?= $kp['completion_rate'] ?>%; background:<?= $bar_color ?>;"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($kp['kpi_score']): ?>
                                    <div style="font-size:1.4rem; font-weight:800; color:var(--text-primary);"><?= htmlspecialchars($kp['kpi_score']) ?><span style="font-size:0.9rem; font-weight:500; color:var(--text-muted);">/100</span></div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-style:italic;">Not Reviewed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size:1.1rem; letter-spacing:1px;"><?= $stars ?></div>
                                <?php if (!$rating): ?><span style="font-size:0.8rem; color:var(--text-muted);">No Rating</span><?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <button class="btn btn-ghost btn-sm" onclick="alert('Review module is a demo feature.')" title="Add Performance Review">
                                    <i class="fas fa-pen-nib"></i> Review
                                </button>
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
    $(document).ready(function() {
        if ($('#performanceTable').length) {
            $('#performanceTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[3, 'desc']],
                language: { search: "", searchPlaceholder: "Search staff performance..." }
            });
            $('.dataTables_filter input').addClass('form-control').css({'width':'250px','display':'inline-block', 'margin-left':'10px'});
        }
    });

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
