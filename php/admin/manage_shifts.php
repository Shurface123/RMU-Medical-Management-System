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

$depts = [];
$qd = mysqli_query($conn, "SELECT department_id, name FROM staff_departments WHERE is_active=1 ORDER BY name ASC");
if ($qd) while($d = mysqli_fetch_assoc($qd)) $depts[] = $d;

// Next 7 days
$shifts = [];
$uniqueStaff = [];
$nightShiftsCount = 0;
$q_shifts = mysqli_query($conn, "
    SELECT sh.*, u.name, u.user_role 
    FROM staff_shifts sh
    JOIN staff s ON sh.staff_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE sh.shift_date >= CURRENT_DATE() 
      AND sh.shift_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
    ORDER BY sh.shift_date ASC, sh.start_time ASC
");
if ($q_shifts) {
    while ($r = mysqli_fetch_assoc($q_shifts)) {
        $shifts[] = $r;
        if(!in_array($r['name'], $uniqueStaff)) $uniqueStaff[] = $r['name'];
        if(strtolower($r['shift_type']) === 'night') $nightShiftsCount++;
    }
}
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
  --primary: #14b8a6; /* Teal for scheduling */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
  --indigo: #6366f1;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #0f766e);
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
.stat-mini-val.indigo { color:var(--indigo); }
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
.badge-status { background:rgba(14, 165, 233, 0.15); color:#0ea5e9; padding:0.3rem 0.8rem; border-radius:12px; font-size:0.95rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; border:1px solid rgba(14, 165, 233, 0.3);}
.badge-status.completed { background:rgba(16, 185, 129, 0.15); color:var(--success); border-color:rgba(16, 185, 129, 0.3);}
.badge-status.active { background:rgba(245, 158, 11, 0.15); color:var(--warning); border-color:rgba(245, 158, 11, 0.3);}

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }

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
.toast-msg.toast-warning { border-left-color:var(--warning); }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

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
    
    <div class="adm-content" style="animation:fadePop .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-calendar-week hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-clock"></i></div>
            <div class="staff-hero-info">
                <h2>Shift Scheduling & Roster</h2>
                <p>Manage and allocate working shifts for all hospital staff and personnel over the next 7 days.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <button class="btn" onclick="document.getElementById('shiftModal').classList.add('active');" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3); backdrop-filter:blur(5px);">
                    <i class="fas fa-plus"></i> Assign New Shift
                </button>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--primary); background:var(--primary-light);"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-mini-val"><?= count($shifts) ?></div>
                <div class="stat-mini-lbl">Upcoming Shifts</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success); background:rgba(16,185,129,0.15);"><i class="fas fa-user-md"></i></div>
                <div class="stat-mini-val success"><?= count($uniqueStaff) ?></div>
                <div class="stat-mini-lbl">Staff on Duty</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--indigo); background:rgba(99,102,241,0.15);"><i class="fas fa-moon"></i></div>
                <div class="stat-mini-val indigo"><?= $nightShiftsCount ?></div>
                <div class="stat-mini-lbl">Night Shifts</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list-ul" style="color:var(--primary);"></i> Upcoming Shifts Roster (Next 7 Days)</h3>
            </div>
            <div class="card-body" style="padding:1rem;">
                <table class="stf-table" id="shiftsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Shift Details</th>
                            <th>Staff Member</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shifts as $s): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--primary); font-size:1.15rem;"><?php echo date('M d, Y', strtotime($s['shift_date'])); ?></strong>
                                <div style="font-size:0.9rem; color:var(--text-muted); margin-top:0.2rem;"><?php echo date('l', strtotime($s['shift_date'])); ?></div>
                            </td>
                            <td>
                                <div style="font-weight:700; color:var(--text-primary);"><i class="far fa-clock" style="color:var(--warning);"></i> <?php echo date('g:i A', strtotime($s['start_time'])) . ' - ' . date('g:i A', strtotime($s['end_time'])); ?></div>
                                <div style="font-size:0.9rem; color:var(--text-secondary); text-transform:uppercase; margin-top:0.3rem; font-weight:600;"><i class="fas fa-tags" style="color:var(--text-muted);"></i> <?php echo htmlspecialchars($s['shift_type']); ?></div>
                            </td>
                            <td>
                                <strong style="font-size:1.15rem; color:var(--text-primary);"><?php echo htmlspecialchars($s['name']); ?></strong>
                            </td>
                            <td><span class="badge-role"><?php echo ucfirst(str_replace('_',' ',$s['user_role'])); ?></span></td>
                            <td>
                                <?php 
                                    $stClass = '';
                                    if(strtolower($s['status']) === 'completed') $stClass = 'completed';
                                    elseif(strtolower($s['status']) === 'active') $stClass = 'active';
                                ?>
                                <span class="badge-status <?= $stClass ?>"><?php echo ucfirst($s['status'] ?? 'Scheduled'); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal-bg" id="shiftModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-plus" style="color:var(--primary);"></i> Assign New Shift</h3>
            <button class="modal-close" onclick="document.getElementById('shiftModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="addShiftForm">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="form-group">
                    <label>Staff Member *</label>
                    <select name="staff_id" class="form-control" required>
                        <option value="">-- Select Staff --</option>
                        <?php foreach($staff_list as $st): ?>
                            <option value="<?php echo $st['id']; ?>"><?php echo htmlspecialchars($st['name']); ?> (<?php echo ucfirst(str_replace('_',' ',$st['user_role'])); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Shift Date *</label>
                        <input type="date" name="shift_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Shift Type *</label>
                        <select name="shift_type" class="form-control" required>
                            <option value="morning">Morning Shift</option>
                            <option value="afternoon">Afternoon Shift</option>
                            <option value="night">Night Shift</option>
                            <option value="rotating">Rotating</option>
                            <option value="regular">Regular</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time *</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End Time *</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ward / Department *</label>
                        <select name="location_ward_assigned" class="form-control" required>
                            <option value="">-- Select Location --</option>
                            <?php foreach($depts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d['name']); ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Shift Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="scheduled">Scheduled</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes / Special Instructions</label>
                    <textarea name="notes" class="form-control" placeholder="Optional notes..."></textarea>
                </div>

                <div class="form-group" style="display:flex; align-items:center; gap:0.8rem; background:var(--surface-2); padding:1rem; border-radius:8px; border:1px solid var(--border);">
                    <input type="checkbox" id="is_recurring" name="is_recurring" value="1" onchange="document.getElementById('recurrenceSettings').style.display = this.checked ? 'block' : 'none';" style="width:1.2rem; height:1.2rem; cursor:pointer; accent-color:var(--primary);">
                    <label for="is_recurring" style="margin:0;cursor:pointer; font-weight:700; color:var(--primary);">Enable Recurring Shift (Advanced)</label>
                </div>
                
                <div id="recurrenceSettings" style="display:none; padding:1.5rem; background:var(--surface-2); border-radius:8px; margin-bottom:1.6rem; border:1px solid var(--border); border-left:4px solid var(--primary);">
                    <p style="margin-top:0;font-size:1rem;color:var(--text-secondary); margin-bottom:1rem;"><i class="fas fa-info-circle"></i> This generates multiple shift records automatically based on the pattern.</p>
                    <div class="form-row" style="margin-bottom:0;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Recurrence Pattern *</label>
                            <select name="recurrence_pattern" class="form-control">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Repeat Until Date *</label>
                            <input type="date" name="recurrence_end_date" class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;"><i class="fas fa-check"></i> Assign Shift</button>
            </form>
        </div>
    </div>
</div>

<!-- Conflict Modal -->
<div class="modal-bg" id="conflictModal">
    <div class="modal-box" style="max-width:500px;">
        <div class="modal-header">
            <h3 style="color:var(--warning);"><i class="fas fa-exclamation-triangle"></i> Scheduling Conflict</h3>
            <button class="modal-close" onclick="document.getElementById('conflictModal').classList.remove('active');"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="font-size:1.15rem; color:var(--text-secondary); margin-bottom:1.5rem;" id="conflictMsg">This staff member already has a shift assigned that overlaps with the selected time.</p>
            <p style="font-size:1.1rem; color:var(--text-primary); font-weight:600;">Override and save anyway?</p>
        </div>
        <div class="modal-footer" style="padding:1.5rem 2rem; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:1rem; background:var(--surface-2);">
            <button class="btn btn-ghost" onclick="document.getElementById('conflictModal').classList.remove('active');">Cancel</button>
            <button class="btn btn-primary" id="btnOverrideShift" style="background:var(--warning); color:#fff;"><i class="fas fa-exclamation"></i> Override & Save</button>
        </div>
    </div>
</div>

<div id="toastWrap"></div>

<script>
    function showToast(msg, type='success') {
        const toast = document.createElement('div');
        toast.className = `toast-msg toast-${type}`;
        toast.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':(type==='danger'?'fa-exclamation-circle':'fa-info-circle')}"></i> <span>${msg}</span>`;
        document.getElementById('toastWrap').appendChild(toast);
        setTimeout(()=> { toast.style.opacity='0'; setTimeout(()=>toast.remove(),300); }, 3000);
    }

    $(document).ready(function() {
        if ($('#shiftsTable').length) {
            $('#shiftsTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, 'asc']],
                language: { search: "", searchPlaceholder: "Search shifts..." }
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

    let pendingShiftData = null;

    document.getElementById('addShiftForm').addEventListener('submit', async(e)=>{
        e.preventDefault();
        const form = e.target;
        const start = form.start_time.value;
        const end = form.end_time.value;
        
        if(start && end && start >= end) {
            showToast('End time cannot be before or exactly at the start time.', 'danger');
            return;
        }

        const fd = new FormData(form);
        fd.append('action', 'check_shift_conflict');

        try {
            const cRes = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
            const cData = await cRes.json();
            
            if (cData.has_conflict) {
                pendingShiftData = fd;
                document.getElementById('conflictMsg').innerText = `Warning: This staff member already has a shift assigned on ${form.shift_date.value} that overlaps with the selected time.`;
                document.getElementById('conflictModal').classList.add('active');
                return;
            }
            
            await submitShiftActual(fd);
        } catch(err) { 
            showToast('Network error communicating with server.', 'danger'); 
            console.error(err); 
        }
    });

    document.getElementById('btnOverrideShift').addEventListener('click', async () => {
        if(!pendingShiftData) return;
        document.getElementById('conflictModal').classList.remove('active');
        await submitShiftActual(pendingShiftData, true);
    });

    async function submitShiftActual(fd, override=false) {
        fd.set('action', 'add_shift');
        if(override) fd.append('conflict_override', '1');
        
        try {
            const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) { 
                showToast('Shift assigned successfully!', 'success'); 
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Error occurred', 'danger');
            }
        } catch(err) {
            showToast('Network error communicating with server.', 'danger'); 
            console.error(err); 
        }
    }
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
