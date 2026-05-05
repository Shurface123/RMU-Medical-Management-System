<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'staff_approvals';
$page_title  = 'Pending Staff Approvals';
include '../includes/_sidebar.php';

// Fetch pending registrations
$pending = [];
$q_pending = mysqli_query($conn, "
    SELECT s.id as staff_id, s.employee_id, s.role, u.name, u.email, u.phone, u.created_at, CAST('staff' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM staff s JOIN users u ON s.user_id = u.id WHERE s.approval_status = 'pending'
    UNION ALL
    SELECT n.id as staff_id, n.nurse_id as employee_id, CAST('nurse' AS CHAR) COLLATE utf8mb4_unicode_ci as role, u.name, u.email, u.phone, u.created_at, CAST('nurse' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM nurses n JOIN users u ON n.user_id = u.id WHERE n.approval_status = 'pending'
    UNION ALL
    SELECT lt.id as staff_id, lt.technician_id as employee_id, CAST('lab_technician' AS CHAR) COLLATE utf8mb4_unicode_ci as role, u.name, u.email, u.phone, u.created_at, CAST('lab_technician' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM lab_technicians lt JOIN users u ON lt.user_id = u.id WHERE lt.approval_status = 'pending'
    UNION ALL
    SELECT d.id as staff_id, d.doctor_id as employee_id, CAST('doctor' AS CHAR) COLLATE utf8mb4_unicode_ci as role, u.name, u.email, u.phone, u.created_at, CAST('doctor' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.approval_status = 'pending'
    UNION ALL
    SELECT p.id as staff_id, p.license_number as employee_id, CAST('pharmacist' AS CHAR) COLLATE utf8mb4_unicode_ci as role, u.name, u.email, u.phone, u.created_at, CAST('pharmacist_profile' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM pharmacist_profile p JOIN users u ON p.user_id = u.id WHERE p.approval_status = 'pending'
    UNION ALL
    SELECT f.finance_staff_id as staff_id, f.staff_code as employee_id, f.role_level as role, u.name, u.email, u.phone, u.created_at, CAST('finance_staff' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM finance_staff f JOIN users u ON f.user_id = u.id WHERE f.approval_status = 'pending'
    ORDER BY created_at DESC
");
if ($q_pending) while ($row = mysqli_fetch_assoc($q_pending)) $pending[] = $row;

$recent = [];
$q_recent = mysqli_query($conn, "
    SELECT s.id as staff_id, s.employee_id, s.role, s.approval_status, s.rejection_reason, s.approved_at, u.name as staff_name, ua.name as admin_name, CAST('staff' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM staff s JOIN users u ON s.user_id = u.id LEFT JOIN users ua ON s.approved_by = ua.id WHERE s.approval_status IN ('approved','rejected') AND s.approved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT n.id as staff_id, n.nurse_id as employee_id, CAST('nurse' AS CHAR) COLLATE utf8mb4_unicode_ci as role, n.approval_status, n.rejection_reason, n.approved_at, u.name as staff_name, ua.name as admin_name, CAST('nurse' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM nurses n JOIN users u ON n.user_id = u.id LEFT JOIN users ua ON n.approved_by = ua.id WHERE n.approval_status IN ('approved','rejected') AND n.approved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT lt.id as staff_id, lt.technician_id as employee_id, CAST('lab_technician' AS CHAR) COLLATE utf8mb4_unicode_ci as role, lt.approval_status, lt.rejection_reason, lt.approved_at, u.name as staff_name, ua.name as admin_name, CAST('lab_technician' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM lab_technicians lt JOIN users u ON lt.user_id = u.id LEFT JOIN users ua ON lt.approved_by = ua.id WHERE lt.approval_status IN ('approved','rejected') AND lt.approved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT d.id as staff_id, d.doctor_id as employee_id, CAST('doctor' AS CHAR) COLLATE utf8mb4_unicode_ci as role, d.approval_status, d.rejection_reason, d.approved_at, u.name as staff_name, ua.name as admin_name, CAST('doctor' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM doctors d JOIN users u ON d.user_id = u.id LEFT JOIN users ua ON d.approved_by = ua.id WHERE d.approval_status IN ('approved','rejected') AND d.approved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT p.id as staff_id, p.license_number as employee_id, CAST('pharmacist' AS CHAR) COLLATE utf8mb4_unicode_ci as role, p.approval_status, p.rejection_reason, p.approved_at, u.name as staff_name, ua.name as admin_name, CAST('pharmacist_profile' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM pharmacist_profile p JOIN users u ON p.user_id = u.id LEFT JOIN users ua ON p.approved_by = ua.id WHERE p.approval_status IN ('approved','rejected') AND p.approved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT f.finance_staff_id as staff_id, f.staff_code as employee_id, f.role_level as role, f.approval_status, f.rejection_reason, f.approved_at, u.name as staff_name, ua.name as admin_name, CAST('finance_staff' AS CHAR) COLLATE utf8mb4_unicode_ci as source_table
    FROM finance_staff f JOIN users u ON f.user_id = u.id LEFT JOIN users ua ON f.approved_by = ua.id WHERE f.approval_status IN ('approved','rejected') AND f.approved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY approved_at DESC LIMIT 15
");
if ($q_recent) while ($row = mysqli_fetch_assoc($q_recent)) $recent[] = $row;

$tot_pending = count($pending);
$tot_recent_app = 0;
$tot_recent_rej = 0;
foreach($recent as $r) {
    if($r['approval_status'] === 'approved') $tot_recent_app++;
    else $tot_recent_rej++;
}

// Ensure CSRF token is available
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
  --primary: #2F80ED;
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
}
/* ── Stat Mini Cards ── */
.stat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.5rem;margin-bottom:2.5rem; }
.stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);
  padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);cursor:pointer;box-shadow:var(--shadow-sm); }
.stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); }
.stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--primary); }
.stat-mini-val.green { color:var(--success); }
.stat-mini-val.red { color:var(--danger); }
.stat-mini-lbl { font-size:1.15rem;font-weight:500;color:var(--text-secondary);margin-top:.6rem; }

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #000 40%));
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; }
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }

/* ── Table Styles ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── DataTables Overrides ── */
.dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary) !important; color: white !important; border: 1px solid var(--primary) !important; border-radius:6px !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--primary-light) !important; color: var(--primary) !important; border-color:var(--primary) !important;}
.dataTables_wrapper .dataTables_filter input { border: 1.5px solid var(--border) !important; border-radius:8px !important; padding: 0.5rem 1rem !important; background: var(--surface) !important; color: var(--text-primary) !important; outline: none; }
.dataTables_wrapper .dataTables_filter input:focus { border-color: var(--primary) !important; box-shadow: 0 0 0 3px var(--primary-light); }
.dataTables_wrapper .dataTables_length select { border: 1.5px solid var(--border) !important; border-radius:8px !important; padding: 0.3rem 0.5rem !important; background: var(--surface) !important; color: var(--text-primary) !important; }
.dataTables_wrapper .dataTables_info { color: var(--text-secondary) !important; font-size: 1.1rem; }
[data-theme="dark"] .dataTables_wrapper .dataTables_filter input, [data-theme="dark"] .dataTables_wrapper .dataTables_length select { background-color: var(--surface) !important; color: var(--text-primary) !important; border-color: var(--border) !important; }

/* ── Filter Tabs ── */
.filter-tabs { display:flex;gap:.8rem;flex-wrap:wrap; margin-bottom: 1.5rem; }
.filter-tabs .ftab { padding:.6rem 1.4rem;border-radius:20px;font-size:1.1rem;font-weight:600;cursor:pointer;
  border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition); }
.filter-tabs .ftab.active, .filter-tabs .ftab:hover { background:var(--primary);color:#fff;border-color:var(--primary); box-shadow: 0 4px 10px var(--primary-light); }

/* ── Badges ── */
.badge { display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .9rem;border-radius:20px;font-size:1rem;font-weight:600; }
.badge-pending  { background:var(--warning-light);color:var(--warning); }
.badge-success     { background:var(--success-light);color:var(--success); }
.badge-danger  { background:var(--danger-light);color:var(--danger); }
.badge-info { background:var(--info-light);color:var(--info); }
.badge-primary { background:var(--primary-light);color:var(--primary); }

/* ── Modals ── */
.modal-bg { display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;
  align-items:center;justify-content:center;padding:2rem;backdrop-filter:blur(5px); opacity:0; transition:opacity 0.3s ease; }
.modal-bg.active { display:flex; opacity:1; }
.modal-box { background:var(--surface);border-radius:var(--radius-lg);padding:2.5rem;width:100%;max-width:560px;
  max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg);border:1px solid var(--border); transform:translateY(20px); transition:transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.modal-bg.active .modal-box { transform:translateY(0); }
.modal-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem; }
.modal-header h3 { font-size:1.8rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.8rem;margin:0; }
.modal-close { background:none;border:none;font-size:2rem;cursor:pointer;color:var(--text-muted);line-height:1;padding:.3rem; transition:color 0.2s;}
.modal-close:hover { color:var(--danger); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; }
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-outline { background:transparent;color:var(--primary);border:1.5px solid var(--primary); }
.btn-outline:hover { background:var(--primary-light); }
.btn-danger { background:var(--danger);color:#fff; }
.btn-success { background:var(--success);color:#fff; }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }
.btn-sm { padding:.6rem 1.2rem;font-size:1.1rem; }

/* ── Card System ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
.card-header h3 { font-size:1.6rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Animations ── */
.dash-section { display:none; animation:fadePop .35s cubic-bezier(.4,0,.2,1); }
.dash-section.active { display:block; }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

/* ── Form Controls ── */
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.2rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }
textarea.form-control { resize:vertical;min-height:100px; }

/* Detail Grid inside Modal */
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.detail-item { background: var(--surface-2); padding: 1.2rem; border-radius: var(--radius-sm); border: 1px solid var(--border); }
.detail-label { font-size: 1rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem; font-weight:600;}
.detail-value { font-size: 1.2rem; font-weight: 600; color: var(--text-primary); }

#toastWrap { position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem; }
.toast-msg { padding:1.2rem 2rem; border-radius:var(--radius-sm); background:var(--surface); box-shadow:var(--shadow-lg); border-left:5px solid var(--primary); font-size:1.2rem; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:1rem; animation:fadePop .3s ease; }
.toast-success { border-left-color:var(--success); }
.toast-danger { border-left-color:var(--danger); }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-plus"></i> Staff Approvals</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content dash-section active">
        
        <div class="staff-hero">
            <div class="staff-hero-avatar"><i class="fas fa-users-cog"></i></div>
            <div class="staff-hero-info">
                <h2>Pending Applications</h2>
                <p>Review, approve, and securely onboard new medical and administrative staff.</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-val"><?php echo $tot_pending; ?></div>
                <div class="stat-mini-lbl">Awaiting Review</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-val green"><?php echo $tot_recent_app; ?></div>
                <div class="stat-mini-lbl">Approved (7d)</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-val red"><?php echo $tot_recent_rej; ?></div>
                <div class="stat-mini-lbl">Rejected (7d)</div>
            </div>
        </div>

        <!-- Pending Queue -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Action Required</h3>
            </div>
            <div class="card-body">
                <?php if (empty($pending)): ?>
                    <div style="text-align:center;padding:4rem;color:var(--text-muted);">
                        <i class="fas fa-check-circle" style="font-size:4rem;color:var(--success);margin-bottom:1.5rem;display:block;"></i>
                        <h2 style="font-size:1.8rem;font-weight:600;margin-bottom:0.5rem;color:var(--text-primary);">All caught up!</h2>
                        <p style="font-size:1.2rem;">There are no new staff registrations pending approval.</p>
                    </div>
                <?php else: ?>
                <div class="filter-tabs">
                    <button class="ftab active" data-filter="">All</button>
                    <button class="ftab" data-filter="Doctor">Doctors</button>
                    <button class="ftab" data-filter="Nurse">Nurses</button>
                    <button class="ftab" data-filter="Lab technician">Lab Techs</button>
                    <button class="ftab" data-filter="Pharmacist">Pharmacists</button>
                </div>
                <table class="stf-table" id="pendingTable">
                    <thead>
                        <tr>
                            <th>Applicant Name</th>
                            <th>Applied Role</th>
                            <th>Contact Info</th>
                            <th>Registration Date</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $p): 
                            $role_lbl = ucfirst(str_replace('_',' ',$p['role'] ?? ''));
                            $role_colors = ['doctor'=>'primary', 'pharmacist'=>'success', 'nurse'=>'warning', 'lab_technician'=>'info', 'ambulance_driver'=>'primary','cleaner'=>'info','laundry_staff'=>'warning','maintenance'=>'success','security'=>'danger','kitchen_staff'=>'warning'];
                            $rc = $role_colors[$p['role']] ?? 'primary';
                        ?>
                        <tr id="row_<?php echo $p['staff_id']; ?>">
                            <td>
                                <strong style="font-size:1.3rem;"><?php echo htmlspecialchars($p['name']); ?></strong>
                                <br><small style="color:var(--text-muted);font-size:1.1rem;"><?php echo htmlspecialchars($p['employee_id'] ?? ''); ?></small>
                            </td>
                            <td><span class="badge badge-<?php echo $rc; ?>"><?php echo $role_lbl; ?></span></td>
                            <td>
                                <div style="margin-bottom:0.3rem;"><i class="fas fa-envelope" style="color:var(--text-muted);width:20px;"></i> <?php echo htmlspecialchars($p['email'] ?? ''); ?></div>
                                <div><i class="fas fa-phone" style="color:var(--text-muted);width:20px;"></i> <?php echo htmlspecialchars($p['phone'] ?? ''); ?></div>
                            </td>
                            <td><?php echo date('M d, Y g:i A', strtotime($p['created_at'])); ?></td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:.5rem;">
                                    <button class="btn btn-ghost btn-sm" onclick='inspectStaff(<?php echo json_encode($p); ?>)'><i class="fas fa-eye" style="color:var(--primary);"></i> View</button>
                                    <button class="btn btn-success btn-sm" onclick="confirmApprove(<?php echo $p['staff_id']; ?>, '<?php echo $p['source_table']; ?>', '<?php echo addslashes($p['name']); ?>')"><i class="fas fa-check"></i> Approve</button>
                                    <button class="btn btn-danger btn-sm" onclick="confirmReject(<?php echo $p['staff_id']; ?>, '<?php echo $p['source_table']; ?>', '<?php echo addslashes($p['name']); ?>')"><i class="fas fa-times"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recently Actioned -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Recent Approvals/Rejections (Last 7 Days)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($recent)): ?>
                    <div style="padding:3rem;text-align:center;color:var(--text-muted);font-size:1.2rem;">No recent actions found.</div>
                <?php else: ?>
                <table class="stf-table" id="recentTable">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actioned By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($r['staff_name']); ?></strong><br>
                                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($r['employee_id'] ?? ''); ?></small>
                            </td>
                            <td><?php echo ucfirst(str_replace('_',' ',$r['role'] ?? '')); ?></td>
                            <td>
                                <?php if ($r['approval_status'] === 'approved'): ?>
                                    <span class="badge badge-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" title="<?php echo htmlspecialchars($r['rejection_reason'] ?? ''); ?>">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($r['admin_name'] ?? 'System'); ?></td>
                            <td><?php echo date('M d, Y g:i A', strtotime($r['approved_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<!-- ── Modals ── -->

<!-- Inspect Modal -->
<div class="modal-bg" id="inspectModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-user-circle"></i> Applicant Details</h3>
            <button class="modal-close" onclick="closeModal('inspectModal')"><i class="fas fa-times"></i></button>
        </div>
        <div style="margin-bottom: 2rem;" id="inspectContent">
            <!-- Content injected via JS -->
        </div>
        <div style="display:flex; justify-content:flex-end; gap:1rem; border-top:1px solid var(--border); padding-top:1.5rem;">
            <button class="btn btn-outline" onclick="closeModal('inspectModal')">Close Panel</button>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal-bg" id="approveModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Confirm Approval</h3>
            <button class="modal-close" onclick="closeModal('approveModal')"><i class="fas fa-times"></i></button>
        </div>
        <div style="font-size:1.3rem; margin-bottom:2rem; line-height:1.6;">
            <p>Are you sure you want to approve the application for <strong id="approveName" style="color:var(--primary);"></strong>?</p>
            <div style="background:var(--success-light); color:var(--success); padding:1rem 1.5rem; border-radius:var(--radius-sm); margin-top:1.5rem; font-size:1.15rem; display:flex; gap:1rem; align-items:center;">
                <i class="fas fa-info-circle" style="font-size:1.5rem;"></i>
                <span>They will receive an email confirmation and gain dashboard access immediately.</span>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:1rem;">
            <button class="btn btn-ghost" onclick="closeModal('approveModal')">Cancel</button>
            <button class="btn btn-success" id="btnConfirmApprove"><i class="fas fa-check"></i> Approve Application</button>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal-bg" id="rejectModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-times-circle" style="color:var(--danger);"></i> Reject Application</h3>
            <button class="modal-close" onclick="closeModal('rejectModal')"><i class="fas fa-times"></i></button>
        </div>
        <div style="font-size:1.3rem; margin-bottom:2rem; line-height:1.6;">
            <p>You are about to reject the application for <strong id="rejectName" style="color:var(--danger);"></strong>.</p>
            <div style="margin-top:1.5rem;">
                <label style="display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;">Reason for Rejection (Visible to applicant)</label>
                <textarea id="rejectReason" class="form-control" placeholder="E.g., Invalid credentials, incomplete documentation..."></textarea>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:1rem;">
            <button class="btn btn-ghost" onclick="closeModal('rejectModal')">Cancel</button>
            <button class="btn btn-danger" id="btnConfirmReject"><i class="fas fa-times"></i> Reject Now</button>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastWrap"></div>

<script>
// Toast function
function showToast(msg, type='success') {
    const toast = document.createElement('div');
    toast.className = `toast-msg toast-${type}`;
    toast.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> <span>${msg}</span>`;
    document.getElementById('toastWrap').appendChild(toast);
    setTimeout(()=> { toast.style.opacity='0'; setTimeout(()=>toast.remove(),300); }, 3000);
}

let pendingTable, recentTable;
$(document).ready(function() {
    pendingTable = $('#pendingTable').DataTable({
        responsive: true, pageLength: 10,
        language: { search: "", searchPlaceholder: "Search applicants..." }
    });
    
    recentTable = $('#recentTable').DataTable({
        responsive: true, pageLength: 5,
        language: { search: "", searchPlaceholder: "Search records..." }
    });

    // Filter pills logic
    $('.ftab').on('click', function() {
        $('.ftab').removeClass('active');
        $(this).addClass('active');
        const filterVal = $(this).data('filter');
        pendingTable.column(1).search(filterVal).draw();
    });
});

// Modal Logic
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function inspectStaff(data) {
    const roleLbl = data.role.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    const dateStr = new Date(data.created_at).toLocaleString('en-US', {dateStyle:'medium', timeStyle:'short'});
    
    const html = `
        <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:2rem; background:var(--surface-2); padding:1.5rem; border-radius:var(--radius-md);">
            <div style="width:60px;height:60px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:2rem;font-weight:700;">
                ${data.name.charAt(0).toUpperCase()}
            </div>
            <div>
                <h4 style="font-size:1.6rem;font-weight:700;color:var(--text-primary);margin:0;">${data.name}</h4>
                <p style="font-size:1.2rem;color:var(--text-secondary);margin:0;">${data.email}</p>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Employee / License ID</div>
                <div class="detail-value">${data.employee_id || 'N/A'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Applied Role</div>
                <div class="detail-value" style="color:var(--primary);">${roleLbl}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Registration Date</div>
                <div class="detail-value">${dateStr}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Phone Number</div>
                <div class="detail-value">${data.phone || 'N/A'}</div>
            </div>
        </div>
    `;
    document.getElementById('inspectContent').innerHTML = html;
    openModal('inspectModal');
}

// Action States
let currentActionId = null;
let currentActionType = null;

function confirmApprove(id, type, name) {
    currentActionId = id; currentActionType = type;
    document.getElementById('approveName').textContent = name;
    openModal('approveModal');
}

function confirmReject(id, type, name) {
    currentActionId = id; currentActionType = type;
    document.getElementById('rejectName').textContent = name;
    document.getElementById('rejectReason').value = '';
    openModal('rejectModal');
}

document.getElementById('btnConfirmApprove').addEventListener('click', async () => {
    const btn = document.getElementById('btnConfirmApprove');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
    
    const fd = new FormData();
    fd.append('action', 'approve_staff');
    fd.append('staff_id', currentActionId);
    fd.append('type', currentActionType);
    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

    try {
        const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            closeModal('approveModal');
            pendingTable.row($('#row_'+currentActionId)).remove().draw();
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(data.message || 'Error occurred', 'danger');
            btn.innerHTML = '<i class="fas fa-check"></i> Approve Application';
            btn.disabled = false;
        }
    } catch (e) { 
        showToast('Network error', 'danger'); console.error(e); 
        btn.innerHTML = '<i class="fas fa-check"></i> Approve Application';
        btn.disabled = false;
    }
});

document.getElementById('btnConfirmReject').addEventListener('click', async () => {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) { showToast('Please provide a rejection reason.', 'danger'); return; }
    
    const btn = document.getElementById('btnConfirmReject');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
    
    const fd = new FormData();
    fd.append('action', 'reject_staff');
    fd.append('staff_id', currentActionId);
    fd.append('reason', reason);
    fd.append('type', currentActionType);
    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

    try {
        const res = await fetch('admin_staff_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            closeModal('rejectModal');
            pendingTable.row($('#row_'+currentActionId)).remove().draw();
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(data.message || 'Error occurred', 'danger');
            btn.innerHTML = '<i class="fas fa-times"></i> Reject Now';
            btn.disabled = false;
        }
    } catch (e) { 
        showToast('Network error', 'danger'); console.error(e); 
        btn.innerHTML = '<i class="fas fa-times"></i> Reject Now';
        btn.disabled = false;
    }
});

const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
