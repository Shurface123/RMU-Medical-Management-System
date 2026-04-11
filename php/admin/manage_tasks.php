<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'manage_tasks';
$page_title = 'Task Assignments';
include '../includes/_sidebar.php';

// Prepare lists for modals
$dept_staff = [];
$q_staff = mysqli_query($conn, "
    SELECT s.id, u.name, u.user_role 
    FROM staff s 
    JOIN users u ON s.user_id = u.id 
    WHERE u.is_active = 1 AND u.user_role NOT IN ('admin','patient')
    ORDER BY u.user_role ASC, u.name ASC
");
if ($q_staff) {
    while ($row = mysqli_fetch_assoc($q_staff)) {
        $r = ucfirst(str_replace('_', ' ', $row['user_role']));
        if (!isset($dept_staff[$r]))
            $dept_staff[$r] = [];
        $dept_staff[$r][] = $row;
    }
}

// Fetch all tasks view (Active & Completed separately)
$tasks = [];
$q_tasks = mysqli_query($conn, "
    SELECT t.task_id AS id, t.task_title AS title, t.priority, t.due_date, t.status, t.created_at,
           u1.name as assignee_name, u1.user_role as assignee_role,
           u2.name as assigner_name
    FROM staff_tasks t
    JOIN staff s ON t.assigned_to = s.id
    JOIN users u1 ON s.user_id = u1.id
    LEFT JOIN users u2 ON t.assigned_by = u2.id
    ORDER BY t.created_at DESC LIMIT 100
");
if ($q_tasks)
    while ($row = mysqli_fetch_assoc($q_tasks))
        $tasks[] = $row;

$active_tasks = array_filter($tasks, fn($t) => in_array($t['status'], ['pending', 'in progress']));
$completed_tasks = array_filter($tasks, fn($t) => $t['status'] === 'completed');
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-tasks"></i> Task Assignments</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header" style="margin-bottom:2rem;">
            <div>
                <h1>Current Task Board</h1>
                <p>Assign and track operational and clinical tasks across all hospital roles.</p>
            </div>
            <button class="btn btn-primary" onclick="document.getElementById('taskModal').classList.add('active')"><span class="btn-text">
                <i class="fas fa-plus"></i> Assign New Task
            </span></button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
            <!-- Active Tasks Panel -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-spinner fa-spin" style="color:var(--warning);margin-right:8px;"></i> Active Tasks (<?php echo count($active_tasks); ?>)</h3>
                </div>
                <div class="adm-card-body" style="padding:0;">
                    <?php if (empty($active_tasks)): ?>
                        <div style="padding:3rem;text-align:center;color:var(--text-muted);"><i class="fas fa-check-double" style="font-size:2rem;color:var(--success);margin-bottom:1rem;display:block;"></i>No active tasks.</div>
                    <?php
else: ?>
                    <div style="overflow-x: auto; width: 100%;">
                        <table class="adm-table" style="width: 100%; min-width: 800px;">
                            <thead><tr><th>Task Info & Due Date</th><th>Assignee</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($active_tasks as $t):
        $pc = $t['priority'] === 'high' ? 'danger' : ($t['priority'] === 'medium' ? 'warning' : 'success');
?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($t['title']); ?></strong>
                                    <div style="margin-top:.3rem;font-size:.8rem;color:var(--text-muted);">
                                        <i class="far fa-calendar-alt"></i> Due: <?php echo date('M d', strtotime($t['due_date'])); ?>
                                    </div>
                                    <div style="margin-top:.2rem;"><span class="adm-badge adm-badge-<?php echo $pc; ?>" style="font-size:0.65rem;padding:2px 6px;">Priority: <?php echo strtoupper($t['priority']); ?></span></div>
                                </td>
                                <td>
                                    <strong style="color:var(--primary);"><?php echo htmlspecialchars($t['assignee_name']); ?></strong>
                                    <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;"><?php echo str_replace('_', ' ', $t['assignee_role']); ?></div>
                                </td>
                                <td>
                                    <span class="adm-badge" style="background:#e0f2fe;color:#0284c7;border:1px solid #0284c7;">
                                        <?php echo ucfirst($t['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        </table>
                    </div>
                    <?php
endif; ?>
                </div>
            </div>

            <!-- Completed Tasks Panel -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-check-circle" style="color:var(--success);margin-right:8px;"></i> Recently Completed (<?php echo count($completed_tasks); ?>)</h3>
                </div>
                <div class="adm-card-body" style="padding:0;">
                    <?php if (empty($completed_tasks)): ?>
                        <div style="padding:3rem;text-align:center;color:var(--text-muted);">No completed tasks yet.</div>
                    <?php
else: ?>
                    <div style="overflow-x: auto; width: 100%;">
                        <table class="adm-table" style="width: 100%; min-width: 800px;">
                            <thead><tr><th>Task Info</th><th>Assignee</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($completed_tasks, 0, 10) as $t): ?>
                            <tr style="opacity:0.8;">
                                <td>
                                    <strike style="color:var(--text-muted);"><strong><?php echo htmlspecialchars($t['title']); ?></strong></strike>
                                    <div style="margin-top:.3rem;font-size:.8rem;color:var(--text-muted);">Assigned By: Admin <?php echo htmlspecialchars($t['assigner_name'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($t['assignee_name']); ?></strong><br>
                                    <small><?php echo str_replace('_', ' ', $t['assignee_role']); ?></small>
                                </td>
                                <td><span class="adm-badge adm-badge-success">Completed</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php
endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Assign Task Modal -->
<div class="adm-modal" id="taskModal">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3><i class="fas fa-tasks"></i> Assign New Staff Task</h3>
            <button class="btn btn-primary adm-modal-close" onclick="document.getElementById('taskModal').classList.remove('active')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <div class="adm-modal-body">
            <form id="assignTaskForm">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="adm-form-group">
                    <label>Select Staff Member</label>
                    <select name="staff_id" class="adm-search-input" required>
                        <option value="">-- Choose Staff --</option>
                        <?php foreach ($dept_staff as $dept => $ppl): ?>
                            <optgroup label="── <?php echo $dept; ?> ──">
                            <?php foreach ($ppl as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php
    endforeach; ?>
                            </optgroup>
                        <?php
endforeach; ?>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label>Task Title</label>
                    <input type="text" name="title" class="adm-search-input" placeholder="e.g. Conduct deep cleaning of Ward B" required>
                </div>
                <div class="adm-form-group" style="margin-top:1rem;">
                    <label>Task Category *</label>
                    <select name="category" class="adm-search-input" required>
                        <option value="">-- Select Category --</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="laundry">Laundry</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="transport">Transport</option>
                        <option value="security">Security</option>
                        <option value="kitchen">Kitchen</option>
                        <option value="general">General</option>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label>Description & Instructions</label>
                    <textarea name="description" class="adm-search-input" rows="3" placeholder="Additional details..." required></textarea>
                </div>
                <div style="display:flex;gap:1rem;">
                    <div class="adm-form-group" style="flex:1;">
                        <label>Priority Level</label>
                        <select name="priority" class="adm-search-input" required>
                            <option value="low">Low Priority</option>
                            <option value="medium" selected>Medium Priority</option>
                            <option value="high">High / Urgent</option>
                        </select>
                    </div>
                    <div class="adm-form-group" style="flex:1;">
                        <label>Due Date *</label>
                        <input type="date" name="due_date" class="adm-search-input" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="adm-form-group" style="flex:1;">
                        <label>Due Time *</label>
                        <input type="time" name="due_time" class="adm-search-input" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;"><span class="btn-text"><i class="fas fa-paper-plane"></i> Dispatch Task</span></button>
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

document.getElementById('assignTaskForm').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'assign_task');
    try {
        const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert('Task dispatched successfully!');
            window.location.reload();
        } else alert(data.message || 'Error occurred');
    } catch(err) { alert('Network error'); console.error(err); }
});
</script>
</body>
</html>