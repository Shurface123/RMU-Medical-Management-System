<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'staff_hub';
$page_title = 'Staff & HR Hub';
include '../includes/_sidebar.php';

// Fetch real stats
$totalStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE user_role != 'patient'"))['count'];
$pendingLeaves = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM leave_requests WHERE status = 'Pending'"))['count'] ?? 0;
$pendingApprovals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE status = 'pending'"))['count'] ?? 0;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-users-cog"></i> Human Resources Hub</span>
        </div>
        <div class="adm-topbar-right">
            <div class="adm-topbar-datetime">
                <i class="far fa-calendar-alt"></i>
                <span><?php echo date('D, M d, Y'); ?></span>
            </div>
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Workforce Intelligence</h1>
                <p>Monitor staff performance, manage duty rosters, and handle administrative requests.</p>
            </div>
            <div style="display:flex; gap:1rem;">
                <a href="staff_approvals.php" class="adm-btn adm-btn-outline" style="background:var(--surface); border:1px solid var(--border); position:relative;">
                    <i class="fas fa-user-check"></i> Pending Approvals
                    <?php if($pendingApprovals > 0): ?>
                        <span style="position:absolute; top:-5px; right:-5px; background:var(--danger); color:white; width:20px; height:20px; border-radius:50%; font-size:0.7rem; display:flex; align-items:center; justify-content:center; border:2px solid white;"><?php echo $pendingApprovals; ?></span>
                    <?php endif; ?>
                </a>
                <button class="adm-btn adm-btn-primary">
                    <i class="fas fa-user-plus"></i> Onboard New Staff
                </button>
            </div>
        </div>

        <div class="adm-stats-grid">
            <div class="adm-stat-card">
                <div class="adm-stat-icon staff"><i class="fas fa-users"></i></div>
                <div class="adm-stat-label">Total Workforce</div>
                <div class="adm-stat-value"><?php echo number_format($totalStaff); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-id-card"></i> Registered Personnel</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background: linear-gradient(135deg, var(--success), #58D68D);"><i class="fas fa-clock"></i></div>
                <div class="adm-stat-label">Currently On-Duty</div>
                <div class="adm-stat-value" id="onDutyCount">12</div>
                <div class="adm-stat-footer" style="color:var(--success);"><i class="fas fa-check-circle"></i> Active Shift</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background: linear-gradient(135deg, var(--warning), #F7CF68);"><i class="fas fa-envelope-open-text"></i></div>
                <div class="adm-stat-label">Leave Requests</div>
                <div class="adm-stat-value"><?php echo $pendingLeaves; ?></div>
                <div class="adm-stat-footer"><i class="fas fa-calendar-minus"></i> Awaiting Review</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background: linear-gradient(135deg, #3498db, #5dade2);"><i class="fas fa-star"></i></div>
                <div class="adm-stat-label">Avg. Performance</div>
                <div class="adm-stat-value">94<span style="font-size:1.5rem; opacity:0.6;">%</span></div>
                <div class="adm-stat-footer"><i class="fas fa-chart-line"></i> Institutional Rating</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 400px; gap:2.5rem;">
            <!-- Performance Section -->
            <div>
                <div class="adm-card shadow-sm" style="border-radius:20px;">
                    <div class="adm-card-header" style="padding: 1.8rem 2.5rem;">
                        <h3><i class="fas fa-chart-area"></i> Departmental Performance</h3>
                        <div class="adm-search-wrapper" style="position:relative;">
                            <i class="fas fa-filter" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.8rem;"></i>
                            <select class="adm-form-input" style="padding-left:2.5rem; width:180px; height:38px; font-size:0.85rem; border-radius:10px;">
                                <option>All Departments</option>
                                <option>Clinical</option>
                                <option>Administration</option>
                                <option>Support Services</option>
                            </select>
                        </div>
                    </div>
                    <div class="adm-card-body">
                        <div id="performanceGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1.5rem;">
                            <!-- Dynamic rendering -->
                            <div style="text-align:center; padding:5rem; color:var(--text-muted); grid-column:1/-1;">
                                <i class="fas fa-sync fa-spin" style="font-size:2rem; margin-bottom:1rem; display:block; color:var(--primary);"></i>
                                Computing metrics...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Duty Roster Section -->
            <div style="display:flex; flex-direction:column; gap:2.5rem;">
                <div class="adm-card shadow-sm" style="border-radius:20px;">
                    <div class="adm-card-header" style="padding: 1.8rem 2rem;">
                        <h3><i class="fas fa-calendar-day"></i> Live Duty Roster</h3>
                        <span class="adm-badge" style="background:var(--success-light); color:var(--success);">LIVE</span>
                    </div>
                    <div class="adm-table-wrap" style="padding:0;">
                        <table class="adm-table" style="font-size:0.85rem;">
                            <thead>
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Designation</th>
                                    <th>Shift</th>
                                </tr>
                            </thead>
                            <tbody id="rosterTableBody">
                                <!-- Dynamic rendering -->
                                <tr><td colspan="3" style="text-align:center; padding:2rem; color:var(--text-muted);">Syncing roster...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="padding: 1.5rem; border-top: 1px solid var(--border); background: var(--bg-surface);">
                        <button class="adm-btn" style="width:100%; border:1px solid var(--border); justify-content:center;">
                            <i class="fas fa-calendar-alt"></i> Detailed Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.perf-card { padding: 1.5rem; border-radius: 16px; background: var(--bg-surface); border: 1px solid var(--border); text-align: center; transition: 0.2s; }
.perf-card:hover { transform: translateY(-3px); border-color: var(--primary); background: var(--surface); box-shadow: var(--shadow-sm); }
.perf-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.2rem; font-size: 1.2rem; background: var(--primary-light); color: var(--primary); }
</style>

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
                <div style="font-weight:700; color:var(--text-primary); margin-bottom:0.25rem;">${dep.department}</div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1rem;">Avg. Efficiency</div>
                <div style="font-size:1.5rem; font-weight:800; color:${dep.efficiency >= 90 ? 'var(--success)' : 'var(--primary)'}">${dep.efficiency}%</div>
                <div style="height:4px; background:var(--border); border-radius:2px; margin-top:0.8rem; overflow:hidden;">
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
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <div style="width:32px; height:32px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.75rem;">
                            ${s.name.charAt(0)}
                        </div>
                        <span style="font-weight:600;">${s.name}</span>
                    </div>
                </td>
                <td style="color:var(--text-secondary); text-transform:capitalize;">${s.role}</td>
                <td><span class="adm-badge" style="background:var(--success-light); color:var(--success); font-size:0.7rem;">MORNING</span></td>
            </tr>
        `).join('');
    }

    loadHRData();

    // UI Toggles
    const sidebar = document.getElementById('admSidebar');
    const overlay = document.getElementById('admOverlay');
    const menuToggle = document.getElementById('menuToggle');

    if (menuToggle) {
        menuToggle.onclick = () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        };
    }
    if (overlay) {
        overlay.onclick = () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        };
    }

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    if (themeToggle) {
        themeToggle.onclick = () => {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme') || 'light';
            const target = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', target);
            localStorage.setItem('rmu_theme', target);
            if (themeIcon) themeIcon.className = target === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        };
    }
</script>
</body>
</html>
