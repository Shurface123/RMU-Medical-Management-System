<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'manage_shifts';
$page_title  = 'Shift Scheduling';
include '../includes/_sidebar.php';

// Prepare lists for modals (Active Staff)
$staff_list = [];
$q_staff = mysqli_query($conn, "SELECT s.id, u.name, u.user_role FROM staff s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1 AND u.user_role NOT IN ('admin','patient') ORDER BY u.name");
if ($q_staff) while ($r = mysqli_fetch_assoc($q_staff)) $staff_list[] = $r;

// Next 7 days
$shifts = [];
$q_shifts = mysqli_query($conn, "
    SELECT sh.*, u.name, u.user_role 
    FROM staff_shifts sh
    JOIN staff s ON sh.staff_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE sh.shift_date >= CURRENT_DATE() 
      AND sh.shift_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
    ORDER BY sh.shift_date ASC, sh.start_time ASC
");
if ($q_shifts) while ($r = mysqli_fetch_assoc($q_shifts)) $shifts[] = $r;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-calendar-alt"></i> Shift Scheduling</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header" style="margin-bottom:2rem;">
            <div>
                <h1>Shift Roster (Next 7 Days)</h1>
                <p>Manage and allocate shifts for all hospital staff.</p>
            </div>
            <button class="adm-btn adm-btn-primary" onclick="document.getElementById('shiftModal').classList.add('active')">
                <i class="fas fa-plus"></i> Add New Shift
            </button>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-clock"></i> Upcoming Shifts Roster</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Date</th><th>Shift Details</th><th>Staff Member</th><th>Role</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($shifts)): ?>
                            <tr><td colspan="5" style="padding:2rem;text-align:center;color:var(--text-muted);">No shifts scheduled for the next 7 days.</td></tr>
                        <?php else: foreach ($shifts as $s): ?>
                        <tr>
                            <td><strong><?php echo date('D, M d Y', strtotime($s['shift_date'])); ?></strong></td>
                            <td>
                                <div><i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($s['start_time'])) . ' - ' . date('g:i A', strtotime($s['end_time'])); ?></div>
                                <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;margin-top:2px;">Type: <?php echo htmlspecialchars($s['shift_type']); ?></div>
                            </td>
                            <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                            <td><span class="adm-badge adm-badge-info"><?php echo ucfirst(str_replace('_',' ',$s['user_role'])); ?></span></td>
                            <td><span class="adm-badge" style="background:#e0f2fe;color:#0284c7;border:1px solid #0284c7;"><?php echo ucfirst($s['status'] ?? 'Scheduled'); ?></span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="adm-modal" id="shiftModal">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3><i class="fas fa-calendar-plus"></i> Assign New Shift</h3>
            <button class="adm-modal-close" onclick="document.getElementById('shiftModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <form id="addShiftForm">
                <div class="adm-form-group">
                    <label>Staff Member</label>
                    <select name="staff_id" class="adm-search-input" required>
                        <option value="">-- Select Staff --</option>
                        <?php foreach($staff_list as $st): ?>
                            <option value="<?php echo $st['id']; ?>"><?php echo htmlspecialchars($st['name']); ?> (<?php echo ucfirst(str_replace('_',' ',$st['user_role'])); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label>Shift Date</label>
                    <input type="date" name="shift_date" class="adm-search-input" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div style="display:flex;gap:1rem;">
                    <div class="adm-form-group" style="flex:1;">
                        <label>Start Time</label>
                        <input type="time" name="start_time" class="adm-search-input" required>
                    </div>
                    <div class="adm-form-group" style="flex:1;">
                        <label>End Time</label>
                        <input type="time" name="end_time" class="adm-search-input" required>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label>Shift Type</label>
                    <select name="shift_type" class="adm-search-input" required>
                        <option value="regular">Regular</option>
                        <option value="morning">Morning Shift</option>
                        <option value="afternoon">Afternoon Shift</option>
                        <option value="night">Night Shift</option>
                        <option value="overtime">Overtime</option>
                        <option value="on_call">On Call (Standby)</option>
                    </select>
                </div>
                <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;">Assign Shift</button>
            </form>
        </div>
    </div>
</div>

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

document.getElementById('addShiftForm').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'add_shift');
    try {
        const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) { alert('Shift assigned!'); window.location.reload(); }
        else alert(data.message || 'Error occurred');
    } catch(err) { alert('Network error'); console.error(err); }
});
</script>
</body>
</html>
