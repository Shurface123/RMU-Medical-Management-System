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
    ORDER BY t.created_at DESC LIMIT 500
");
if ($q_tasks) {
    while ($row = mysqli_fetch_assoc($q_tasks)) {
        $tasks[] = $row;
    }
}

$active_tasks = array_filter($tasks, fn($t) => in_array($t['status'], ['pending', 'in progress']));
$completed_tasks = array_filter($tasks, fn($t) => $t['status'] === 'completed');

$highPriorityCount = count(array_filter($active_tasks, fn($t) => strtolower($t['priority']) === 'high'));
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
  --primary: #8b5cf6; /* Violet for Tasks */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
  --indigo: #6366f1;
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
.stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);
  padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);cursor:pointer;box-shadow:var(--shadow-sm); }
.stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); border-color:var(--primary); }
.stat-mini-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:1.8rem;background:var(--surface-2);color:var(--text-secondary); }
.stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--primary); }
.stat-mini-val.success { color:var(--success); }
.stat-mini-val.danger { color:var(--danger); }
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
textarea.form-control { resize:vertical; min-height:80px; }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Badges ── */
.badge-role { background:rgba(99, 102, 241, 0.15); color:var(--indigo); padding:0.3rem 0.8rem; border-radius:12px; font-size:0.95rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; border:1px solid rgba(99, 102, 241, 0.3);}
.badge-priority { padding:0.2rem 0.6rem; border-radius:4px; font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:1px;}
.badge-priority.high { background:var(--danger); color:#fff; }
.badge-priority.medium { background:var(--warning); color:#fff; }
.badge-priority.low { background:var(--success); color:#fff; }
.badge-status { padding:0.3rem 0.8rem; border-radius:12px; font-size:0.95rem; font-weight:700; text-transform:uppercase; letter-spacing:1px;}
.badge-status.completed { background:rgba(16, 185, 129, 0.15); color:var(--success); border:1px solid rgba(16, 185, 129, 0.3);}
.badge-status.pending { background:rgba(14, 165, 233, 0.15); color:#0ea5e9; border:1px solid rgba(14, 165, 233, 0.3);}
.badge-status.inprogress { background:rgba(245, 158, 11, 0.15); color:var(--warning); border:1px solid rgba(245, 158, 11, 0.3);}

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }

/* ── Modals ── */
.modal-bg { position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(5px);
  z-index:9999;display:none;align-items:center;justify-content:center;opacity:0;transition:opacity 0.3s ease; }
.modal-bg.active { display:flex;opacity:1; }
.modal-box { background:var(--surface);width:90%;max-width:700px;border-radius:var(--radius-lg);
  box-shadow:var(--shadow-lg);transform:translateY(20px);transition:transform 0.3s ease;overflow:hidden; border:1px solid var(--border); max-height:90vh; display:flex; flex-direction:column;}
.modal-bg.active .modal-box { transform:translateY(0); }
.modal-header { padding:1.5rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-2); flex-shrink:0;}
.modal-header h3 { margin:0;font-size:1.4rem;color:var(--text-primary);display:flex;align-items:center;gap:0.5rem; }
.modal-close { background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer; }
.modal-body { padding:2rem; overflow-y:auto; }

/* ── Toast ── */
#toastWrap { position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem; }
.toast-msg { padding:1.2rem 2rem; border-radius:var(--radius-sm); background:var(--surface); box-shadow:var(--shadow-lg); border-left:5px solid var(--primary); font-size:1.2rem; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:1rem; animation:fadePop .3s ease; }
.toast-msg.toast-danger { border-left-color:var(--danger); }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

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
    
    <div class="adm-content" style="animation:fadePop .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-clipboard-list hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-tasks"></i></div>
            <div class="staff-hero-info">
                <h2>Task Board & Delegation</h2>
                <p>Assign and track operational and clinical tasks across all hospital roles securely.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <button class="btn" onclick="document.getElementById('taskModal').classList.add('active');" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3); backdrop-filter:blur(5px);">
                    <i class="fas fa-plus"></i> Assign New Task
                </button>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--primary); background:var(--primary-light);"><i class="fas fa-spinner"></i></div>
                <div class="stat-mini-val"><?= count($active_tasks) ?></div>
                <div class="stat-mini-lbl">Active Tasks</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--danger); background:rgba(239,68,68,0.15);"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-mini-val danger"><?= $highPriorityCount ?></div>
                <div class="stat-mini-lbl">High Priority</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success); background:rgba(16,185,129,0.15);"><i class="fas fa-check-circle"></i></div>
                <div class="stat-mini-val success"><?= count($completed_tasks) ?></div>
                <div class="stat-mini-lbl">Completed Tasks</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2.5rem;">
            
            <!-- Active Tasks Panel -->
            <div class="card" style="margin-bottom:0;">
                <div class="card-header">
                    <h3><i class="fas fa-spinner fa-spin" style="color:var(--warning);"></i> Active Queue (<?php echo count($active_tasks); ?>)</h3>
                </div>
                <div class="card-body" style="padding:1rem;">
                    <?php if (empty($active_tasks)): ?>
                        <div style="padding:4rem;text-align:center;color:var(--text-muted);">
                            <i class="fas fa-check-double" style="font-size:3rem;color:var(--success);margin-bottom:1rem;display:block;"></i>
                            <h3 style="margin-bottom:0; color:var(--text-primary);">All Caught Up</h3>
                            <p style="margin-top:0.5rem; font-size:1rem;">No active tasks in the queue.</p>
                        </div>
                    <?php else: ?>
                        <table class="stf-table" id="activeTasksTable">
                            <thead>
                                <tr>
                                    <th>Task Info & Due Date</th>
                                    <th>Assignee</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_tasks as $t): 
                                    $pc = $t['priority'] === 'high' ? 'high' : ($t['priority'] === 'medium' ? 'medium' : 'low');
                                    $sc = $t['status'] === 'pending' ? 'pending' : 'inprogress';
                                ?>
                                <tr>
                                    <td>
                                        <strong style="color:var(--text-primary); font-size:1.15rem;"><?php echo htmlspecialchars($t['title']); ?></strong>
                                        <div style="margin-top:0.4rem;font-size:0.95rem;color:var(--text-muted);"><i class="far fa-calendar-alt"></i> Due: <?php echo date('M d', strtotime($t['due_date'])); ?></div>
                                        <div style="margin-top:0.4rem;"><span class="badge-priority <?= $pc ?>">Priority: <?php echo strtoupper($t['priority']); ?></span></div>
                                    </td>
                                    <td>
                                        <strong style="color:var(--primary); font-size:1.1rem;"><?php echo htmlspecialchars($t['assignee_name']); ?></strong><br>
                                        <div style="margin-top:0.2rem;"><span class="badge-role" style="font-size:0.75rem; padding:0.2rem 0.5rem;"><?php echo str_replace('_', ' ', $t['assignee_role']); ?></span></div>
                                    </td>
                                    <td>
                                        <span class="badge-status <?= $sc ?>"><?php echo ucfirst($t['status']); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Completed Tasks Panel -->
            <div class="card" style="margin-bottom:0;">
                <div class="card-header">
                    <h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Completed (<?php echo count($completed_tasks); ?>)</h3>
                </div>
                <div class="card-body" style="padding:1rem;">
                    <?php if (empty($completed_tasks)): ?>
                        <div style="padding:4rem;text-align:center;color:var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size:3rem;color:var(--border);margin-bottom:1rem;display:block;"></i>
                            <h3 style="margin-bottom:0; color:var(--text-primary);">No History</h3>
                            <p style="margin-top:0.5rem; font-size:1rem;">No completed tasks yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="stf-table" id="completedTasksTable">
                            <thead>
                                <tr>
                                    <th>Task Info</th>
                                    <th>Assignee</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($completed_tasks, 0, 50) as $t): ?>
                                <tr style="opacity:0.85;">
                                    <td>
                                        <strong style="color:var(--text-secondary); text-decoration:line-through; font-size:1.1rem;"><?php echo htmlspecialchars($t['title']); ?></strong>
                                        <div style="margin-top:0.3rem;font-size:0.9rem;color:var(--text-muted);">Assigned By: <?php echo htmlspecialchars($t['assigner_name'] ?? 'Admin'); ?></div>
                                    </td>
                                    <td>
                                        <strong style="color:var(--text-primary); font-size:1.1rem;"><?php echo htmlspecialchars($t['assignee_name']); ?></strong><br>
                                        <div style="margin-top:0.2rem;"><span class="badge-role" style="font-size:0.75rem; padding:0.2rem 0.5rem; background:var(--surface-2); border-color:var(--border); color:var(--text-muted);"><?php echo str_replace('_', ' ', $t['assignee_role']); ?></span></div>
                                    </td>
                                    <td><span class="badge-status completed">Completed</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</main>

<div class="modal-bg" id="taskModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-tasks" style="color:var(--primary);"></i> Assign New Staff Task</h3>
            <button class="modal-close" onclick="document.getElementById('taskModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="assignTaskForm">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="form-group">
                    <label>Select Staff Member *</label>
                    <select name="staff_id" class="form-control" required>
                        <option value="">-- Choose Staff --</option>
                        <?php foreach ($dept_staff as $dept => $ppl): ?>
                            <optgroup label="── <?php echo $dept; ?> ──">
                            <?php foreach ($ppl as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Task Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Conduct deep cleaning" required>
                    </div>
                    <div class="form-group">
                        <label>Task Category *</label>
                        <select name="category" class="form-control" required>
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
                </div>

                <div class="form-group">
                    <label>Description & Instructions *</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Provide detailed instructions..." required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Priority Level *</label>
                        <select name="priority" class="form-control" required>
                            <option value="low">Low Priority</option>
                            <option value="medium" selected>Medium Priority</option>
                            <option value="high">High / Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Due Date *</label>
                        <input type="date" name="due_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Due Time *</label>
                        <input type="time" name="due_time" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;"><i class="fas fa-paper-plane"></i> Dispatch Task</button>
            </form>
        </div>
    </div>
</div>

<div id="toastWrap"></div>

<script>
    function showToast(msg, type='success') {
        const toast = document.createElement('div');
        toast.className = `toast-msg toast-${type}`;
        toast.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> <span>${msg}</span>`;
        document.getElementById('toastWrap').appendChild(toast);
        setTimeout(()=> { toast.style.opacity='0'; setTimeout(()=>toast.remove(),300); }, 3000);
    }

    $(document).ready(function() {
        if ($('#activeTasksTable').length) {
            $('#activeTasksTable').DataTable({
                responsive: true,
                pageLength: 5,
                lengthMenu: [5, 10, 25],
                ordering: false,
                language: { search: "", searchPlaceholder: "Search active..." }
            });
        }
        if ($('#completedTasksTable').length) {
            $('#completedTasksTable').DataTable({
                responsive: true,
                pageLength: 5,
                lengthMenu: [5, 10, 25],
                ordering: false,
                language: { search: "", searchPlaceholder: "Search completed..." }
            });
        }
        $('.dataTables_filter input').addClass('form-control').css({'width':'150px','display':'inline-block', 'margin-left':'10px'});
    });

    const themeIcon = document.getElementById('themeIcon');
    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const html = document.documentElement;
        const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
        html.setAttribute('data-theme', t);
        localStorage.setItem('rmu_theme', t);
        if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
    });

    document.getElementById('assignTaskForm').addEventListener('submit', async(e)=>{
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'assign_task');
        try {
            const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                showToast('Task dispatched successfully!', 'success');
                document.getElementById('taskModal').classList.remove('active');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Error occurred', 'danger');
            }
        } catch(err) { 
            showToast('Network error', 'danger'); 
            console.error(err); 
        }
    });
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>