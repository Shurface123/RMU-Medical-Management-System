<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'staff_hub';
$page_title = 'Staff & HR Hub';
include '../includes/_sidebar.php';

// Fetch real stats
$totalStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE user_role != 'patient'"))['count'] ?? 0;
$pendingLeaves = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM leave_requests WHERE status = 'Pending'"))['count'] ?? 0;
$pendingApprovals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE status = 'pending'"))['count'] ?? 0;
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
  --primary: #2F80ED;
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #000 40%));
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

/* ── Layout Grid ── */
.staff-hub-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2.5rem; }
@media (max-width: 1100px) { .staff-hub-grid { grid-template-columns: 1fr; } }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; height:100%; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
.card-header h3 { font-size:1.6rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Performance Cards ── */
.perf-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1.5rem; }
.perf-card { padding: 1.5rem; border-radius: var(--radius-sm); background: var(--surface-2); border: 1px solid var(--border); text-align: center; transition: var(--transition); }
.perf-card:hover { transform: translateY(-3px); border-color: var(--primary); background: var(--surface); box-shadow: var(--shadow-sm); }
.perf-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.2rem; font-size: 1.5rem; background: var(--primary-light); color: var(--primary); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center; }
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-outline { background:transparent;color:var(--primary);border:1.5px solid var(--primary); }
.btn-outline:hover { background:var(--primary-light); }

/* ── Form Controls ── */
.form-control { width:100%;padding:0.8rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.1rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Badges ── */
.badge { display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .9rem;border-radius:20px;font-size:1rem;font-weight:600; }
.badge-success { background:var(--success-light);color:var(--success); }
.badge-danger { background:var(--danger-light);color:var(--danger); }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-users-cog"></i> Human Resources Hub</span>
        </div>
        <div class="adm-topbar-right">
            <div class="adm-topbar-datetime" style="color:var(--text-secondary); font-weight:600; font-size:1.1rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="far fa-calendar-alt"></i>
                <span><?php echo date('D, M d, Y'); ?></span>
            </div>
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content" style="animation:fadePop .35s ease;">
        
        <div class="staff-hero">
            <i class="fas fa-network-wired hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-id-card-alt"></i></div>
            <div class="staff-hero-info">
                <h2>Workforce Intelligence</h2>
                <p>Monitor staff performance, manage duty rosters, and handle administrative requests.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <a href="staff_approvals.php" class="btn" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3); backdrop-filter:blur(5px);">
                    <i class="fas fa-user-check"></i> Pending Approvals
                    <?php if($pendingApprovals > 0): ?>
                        <span style="background:var(--danger); color:white; padding:0.2rem 0.6rem; border-radius:10px; font-size:1rem; margin-left:0.5rem;"><?php echo $pendingApprovals; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--primary); background:var(--primary-light);"><i class="fas fa-users"></i></div>
                <div class="stat-mini-val"><?php echo number_format($totalStaff); ?></div>
                <div class="stat-mini-lbl">Total Workforce</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success); background:var(--success-light);"><i class="fas fa-user-clock"></i></div>
                <div class="stat-mini-val success" id="onDutyCount">12</div>
                <div class="stat-mini-lbl">Currently On-Duty</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--warning); background:var(--warning-light);"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-mini-val warning"><?php echo $pendingLeaves; ?></div>
                <div class="stat-mini-lbl">Leave Requests</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--info); background:var(--info-light);"><i class="fas fa-chart-line"></i></div>
                <div class="stat-mini-val info">94<span style="font-size:1.5rem; opacity:0.6;">%</span></div>
                <div class="stat-mini-lbl">Avg Performance</div>
            </div>
        </div>

        <div class="staff-hub-grid">
            <!-- Performance Section -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-area" style="color:var(--primary);"></i> Departmental Performance</h3>
                    <div style="position:relative;">
                        <i class="fas fa-filter" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:1rem;"></i>
                        <select class="form-control" style="padding-left:2.5rem; width:200px;">
                            <option>All Departments</option>
                            <option>Clinical</option>
                            <option>Administration</option>
                            <option>Support Services</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="perf-grid" id="performanceGrid">
                        <div style="text-align:center; padding:5rem; color:var(--text-muted); grid-column:1/-1;">
                            <i class="fas fa-sync fa-spin" style="font-size:2rem; margin-bottom:1rem; display:block; color:var(--primary);"></i>
                            Computing metrics...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Duty Roster Section -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-day" style="color:var(--primary);"></i> Live Duty Roster</h3>
                    <span class="badge badge-success">LIVE</span>
                </div>
                <div style="padding:0;">
                    <table class="stf-table">
                        <thead>
                            <tr>
                                <th>Staff Member</th>
                                <th>Shift</th>
                            </tr>
                        </thead>
                        <tbody id="rosterTableBody">
                            <tr><td colspan="2" style="text-align:center; padding:2rem; color:var(--text-muted);">Syncing roster...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div style="padding: 1.5rem; border-top: 1px solid var(--border); background: var(--surface-2);">
                    <button class="btn btn-primary" style="width:100%;">
                        <i class="fas fa-calendar-alt"></i> View Detailed Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const apiBase = '/RMU-Medical-Management-System/php/api/router.php?path=hr/';

    async function loadHRData() {
        try {
            const res = await fetch(apiBase + 'performance');
            const result = await res.json();
            if (result.success) renderPerformance(result.data);

            const res2 = await fetch(apiBase + 'roster');
            const result2 = await res2.json();
            if (result2.success) renderRoster(result2.data);
        } catch (e) { console.error(e); }
    }

    function renderPerformance(deps) {
        const grid = document.getElementById('performanceGrid');
        if(!grid) return;
        grid.innerHTML = deps.map(dep => `
            <div class="perf-card">
                <div class="perf-icon"><i class="fas fa-building"></i></div>
                <div style="font-size:1.2rem; font-weight:700; color:var(--text-primary); margin-bottom:0.4rem;">${dep.department}</div>
                <div style="font-size:1rem; color:var(--text-muted); margin-bottom:1.2rem;">Avg. Efficiency</div>
                <div style="font-size:2.2rem; font-weight:800; color:${dep.efficiency >= 90 ? 'var(--success)' : 'var(--primary)'}">${dep.efficiency}%</div>
                <div style="height:6px; background:var(--border); border-radius:3px; margin-top:1rem; overflow:hidden;">
                    <div style="width:${dep.efficiency}%; height:100%; background:${dep.efficiency >= 90 ? 'var(--success)' : 'var(--primary)'}"></div>
                </div>
            </div>
        `).join('');
    }

    function renderRoster(staff) {
        const body = document.getElementById('rosterTableBody');
        if(!body) return;
        body.innerHTML = staff.slice(0, 8).map(s => `
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <div style="width:36px; height:36px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1rem;">
                            ${s.name.charAt(0)}
                        </div>
                        <div>
                            <div style="font-weight:600; font-size:1.1rem; color:var(--text-primary);">${s.name}</div>
                            <div style="font-size:1rem; color:var(--text-muted); text-transform:capitalize;">${s.role}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-success">MORNING</span></td>
            </tr>
        `).join('');
    }

    loadHRData();

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
