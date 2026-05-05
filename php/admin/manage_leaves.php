<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'manage_leaves';
$page_title = 'Leave Requests';
include '../includes/_sidebar.php';

// Fetch leaves
$leaves = [];
$q_leaves = mysqli_query($conn, "
    SELECT lr.*, u.name, u.user_role as role
    FROM staff_leave_requests lr
    JOIN staff s ON lr.staff_id = s.id
    JOIN users u ON s.user_id = u.id
    ORDER BY lr.applied_at DESC LIMIT 500
");
if ($q_leaves) {
    while ($r = mysqli_fetch_assoc($q_leaves)) {
        $leaves[] = $r;
    }
}

$pending = array_filter($leaves, fn($l) => $l['status'] === 'pending');
$actioned = array_filter($leaves, fn($l) => $l['status'] !== 'pending');

$approvedCount = count(array_filter($leaves, fn($l) => $l['status'] === 'approved'));
$rejectedCount = count(array_filter($leaves, fn($l) => $l['status'] === 'rejected'));
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
  --primary: #f59e0b; /* Amber for Leaves */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
  --indigo: #6366f1;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #b45309);
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
.stat-mini-val.info { color:var(--info); }
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

/* ── Badges ── */
.badge-role { background:rgba(99, 102, 241, 0.15); color:var(--indigo); padding:0.3rem 0.8rem; border-radius:12px; font-size:0.95rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; border:1px solid rgba(99, 102, 241, 0.3);}
.badge-type { background:var(--primary-light); color:var(--warning); padding:0.3rem 0.8rem; border-radius:12px; font-size:0.95rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; border:1px solid rgba(245, 158, 11, 0.3);}
.badge-status { padding:0.3rem 0.8rem; border-radius:12px; font-size:0.95rem; font-weight:700; text-transform:uppercase; letter-spacing:1px;}
.badge-status.approved { background:rgba(16, 185, 129, 0.15); color:var(--success); border:1px solid rgba(16, 185, 129, 0.3);}
.badge-status.rejected { background:rgba(239, 68, 68, 0.15); color:var(--danger); border:1px solid rgba(239, 68, 68, 0.3);}
.badge-status.pending { background:rgba(14, 165, 233, 0.15); color:#0ea5e9; border:1px solid rgba(14, 165, 233, 0.3);}

/* ── Form Controls ── */
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.15rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-success { background:var(--success); color:#fff; }
.btn-success:hover { opacity:.88;transform:translateY(-1px); }
.btn-danger { background:var(--danger); color:#fff; }
.btn-danger:hover { opacity:.88;transform:translateY(-1px); }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }

/* ── Modals ── */
.modal-bg { position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(5px);
  z-index:9999;display:none;align-items:center;justify-content:center;opacity:0;transition:opacity 0.3s ease; }
.modal-bg.active { display:flex;opacity:1; }
.modal-box { background:var(--surface);width:90%;max-width:500px;border-radius:var(--radius-lg);
  box-shadow:var(--shadow-lg);transform:translateY(20px);transition:transform 0.3s ease;overflow:hidden; border:1px solid var(--border);}
.modal-bg.active .modal-box { transform:translateY(0); }
.modal-header { padding:1.5rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-2); }
.modal-header h3 { margin:0;font-size:1.4rem;color:var(--text-primary);display:flex;align-items:center;gap:0.5rem; }
.modal-close { background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer; }
.modal-body { padding:2rem; }
.modal-footer { padding:1.5rem 2rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:1rem;background:var(--surface-2); }

/* ── Toast ── */
#toastWrap { position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem; }
.toast-msg { padding:1.2rem 2rem; border-radius:var(--radius-sm); background:var(--surface); box-shadow:var(--shadow-lg); border-left:5px solid var(--primary); font-size:1.2rem; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:1rem; animation:fadePop .3s ease; }
.toast-msg.toast-danger { border-left-color:var(--danger); }
.toast-msg.toast-success { border-left-color:var(--success); }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-umbrella-beach"></i> Leave Requests</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadePop .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-plane-departure hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-umbrella-beach"></i></div>
            <div class="staff-hero-info">
                <h2>Leave & Absence Management</h2>
                <p>Review and act upon staff leave requests. Manage hospital resource availability.</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--primary); background:var(--primary-light);"><i class="fas fa-clock"></i></div>
                <div class="stat-mini-val"><?= count($pending) ?></div>
                <div class="stat-mini-lbl">Pending Actions</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success); background:rgba(16,185,129,0.15);"><i class="fas fa-check-circle"></i></div>
                <div class="stat-mini-val success"><?= $approvedCount ?></div>
                <div class="stat-mini-lbl">Total Approved</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--danger); background:rgba(239,68,68,0.15);"><i class="fas fa-times-circle"></i></div>
                <div class="stat-mini-val danger"><?= $rejectedCount ?></div>
                <div class="stat-mini-lbl">Total Rejected</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-exclamation-circle" style="color:var(--primary);"></i> Action Required (Pending)</h3>
            </div>
            <div class="card-body" style="padding:1rem;">
                <table class="stf-table" id="pendingTable">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Leave Type</th>
                            <th>Requested Dates</th>
                            <th>Reason / Notes</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $l): ?>
                        <tr>
                            <td>
                                <strong style="font-size:1.15rem; color:var(--text-primary);"><?php echo htmlspecialchars($l['name']); ?></strong><br>
                                <div style="margin-top:0.3rem;"><span class="badge-role"><?php echo str_replace('_', ' ', $l['role']); ?></span></div>
                            </td>
                            <td><span class="badge-type"><?php echo ucfirst(str_replace('_', ' ', $l['leave_type'])); ?></span></td>
                            <td>
                                <div style="font-weight:700; color:var(--text-primary);"><i class="far fa-calendar-alt" style="color:var(--primary);"></i> <?php echo date('M d', strtotime($l['start_date'])) . ' - ' . date('M d, Y', strtotime($l['end_date'])); ?></div>
                                <div style="font-size:.9rem; color:var(--text-muted); margin-top:0.3rem;">Applied: <?php echo date('d M Y', strtotime($l['applied_at'])); ?></div>
                            </td>
                            <td style="max-width:250px;">
                                <div style="color:var(--text-secondary); font-size:1rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($l['reason']); ?>">
                                    <?php echo htmlspecialchars($l['reason']); ?>
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:.5rem;">
                                    <button class="btn btn-success" style="padding:0.6rem 1rem;" onclick="approveLeave(<?php echo $l['leave_id']; ?>)" title="Approve"><i class="fas fa-check"></i></button>
                                    <button class="btn btn-danger" style="padding:0.6rem 1rem;" onclick="openRejectModal(<?php echo $l['leave_id']; ?>)" title="Reject"><i class="fas fa-times"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="background:var(--surface);">
                <h3><i class="fas fa-history" style="color:var(--text-muted);"></i> Recently Actioned</h3>
            </div>
            <div class="card-body" style="padding:1rem;">
                <table class="stf-table" id="actionedTable">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Leave Type</th>
                            <th>Dates</th>
                            <th>Status / Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($actioned, 0, 50) as $l): ?>
                        <tr>
                            <td><strong style="font-size:1.1rem; color:var(--text-primary);"><?php echo htmlspecialchars($l['name']); ?></strong></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $l['leave_type'])); ?></td>
                            <td><i class="far fa-calendar-alt"></i> <?php echo date('M d', strtotime($l['start_date'])) . ' - ' . date('M d, Y', strtotime($l['end_date'])); ?></td>
                            <td>
                                <?php if ($l['status'] === 'approved'): ?>
                                    <span class="badge-status approved">Approved</span>
                                <?php else: ?>
                                    <span class="badge-status rejected" title="<?php echo htmlspecialchars($l['admin_notes'] ?? ''); ?>">Rejected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- Reject Modal -->
<div class="modal-bg" id="rejectModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="color:var(--danger);"><i class="fas fa-times-circle"></i> Reject Leave Request</h3>
            <button class="modal-close" onclick="closeRejectModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="font-size:1.15rem; color:var(--text-secondary); margin-bottom:1rem;">Please provide a reason for rejecting this leave request. This will be visible to the staff member.</p>
            <textarea id="rejectReason" class="form-control" rows="3" placeholder="Reason for rejection..."></textarea>
            <input type="hidden" id="rejectLeaveId">
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeRejectModal()">Cancel</button>
            <button class="btn btn-danger" onclick="submitReject()"><i class="fas fa-ban"></i> Confirm Rejection</button>
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
        if ($('#pendingTable').length) {
            $('#pendingTable').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: false,
                language: { search: "", searchPlaceholder: "Search pending..." }
            });
        }
        if ($('#actionedTable').length) {
            $('#actionedTable').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: false,
                language: { search: "", searchPlaceholder: "Search history..." }
            });
        }
        $('.dataTables_filter input').addClass('form-control').css({'width':'200px','display':'inline-block', 'margin-left':'10px'});
    });

    const themeIcon = document.getElementById('themeIcon');
    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const html = document.documentElement;
        const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
        html.setAttribute('data-theme', t);
        localStorage.setItem('rmu_theme', t);
        if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
    });

    async function approveLeave(id) {
        if (!confirm('Approve this leave request?')) return;
        const fd = new FormData(); fd.append('action', 'approve_leave'); fd.append('leave_id', id);
        try {
            const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) { 
                showToast('Leave Approved!', 'success'); 
                setTimeout(() => window.location.reload(), 1000); 
            } else showToast(data.message, 'danger');
        } catch(err) { showToast('Network Error', 'danger'); }
    }

    function openRejectModal(id) {
        document.getElementById('rejectLeaveId').value = id;
        document.getElementById('rejectReason').value = '';
        document.getElementById('rejectModal').classList.add('active');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('active');
    }

    async function submitReject() {
        const id = document.getElementById('rejectLeaveId').value;
        const reason = document.getElementById('rejectReason').value.trim();
        if(!reason) {
            showToast('Please provide a reason', 'danger');
            return;
        }
        
        const fd = new FormData(); fd.append('action', 'reject_leave'); fd.append('leave_id', id); fd.append('reason', reason);
        try {
            const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) { 
                showToast('Leave Rejected', 'success'); 
                closeRejectModal();
                setTimeout(() => window.location.reload(), 1000); 
            } else showToast(data.message, 'danger');
        } catch(err) { showToast('Network Error', 'danger'); }
    }
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>