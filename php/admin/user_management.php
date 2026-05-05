<?php
session_start();
require_once '../db_conn.php';
require_once '../classes/SecurityManager.php';
require_once '../classes/AuditLogger.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$securityManager = new SecurityManager($conn);
$auditLogger = new AuditLogger($conn);

$message = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_user':
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $name = $_POST['name'];
            $role = $_POST['role'];
            
            if (empty($username) || empty($email) || empty($password)) {
                $error = "Username, Email and Password are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format.";
            } else {
                // Check uniqueness
                $check = $conn->prepare("SELECT id FROM users WHERE user_name = ? OR email = ?");
                $check->bind_param("ss", $username, $email);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $error = "Username or Email already taken.";
                } else {
                    $validation = $securityManager->validatePassword($password, null);
                    if (!$validation['valid']) {
                        $error = $validation['message'];
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $query = "INSERT INTO users (user_name, email, password, name, user_role, is_active, is_verified, account_status) VALUES (?, ?, ?, ?, ?, 1, 1, 'active')";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssss", $username, $email, $hashedPassword, $name, $role);
                        if ($stmt->execute()) {
                            $new_uid = $stmt->insert_id;
                            $auditLogger->logAction($_SESSION['user_id'], 'user_create', 'users', $new_uid, "Admin created user: $username (role: $role)");
                            $message = "User account created successfully! The staff member can now log in.";
                        } else {
                            $error = "Failed to create user: " . $stmt->error;
                        }
                    }
                }
            }
            break;
            
        case 'update_user':
            $userId = $_POST['user_id'];
            $email = $_POST['email'];
            $name = $_POST['name'];
            $role = $_POST['role'];
            
            $query = "UPDATE users SET email = ?, name = ?, user_role = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $email, $name, $role, $userId);
            
            if ($stmt->execute()) {
                $auditLogger->logAction($_SESSION['user_id'], 'user_update', 'users', $userId, "Updated user ID: $userId");
                $message = "User updated successfully!";
            } else {
                $error = "Failed to update user.";
            }
            break;
            
        case 'delete_user':
            $userId = $_POST['user_id'];
            
            // Don't allow deleting self
            if ($userId == $_SESSION['user_id']) {
                $error = "You cannot delete your own account!";
            } else {
                $query = "DELETE FROM users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    $auditLogger->logAction($_SESSION['user_id'], 'user_delete', 'users', $userId, "Deleted user ID: $userId");
                    $message = "User deleted successfully!";
                } else {
                    $error = "Failed to delete user.";
                }
            }
            break;
            
        case 'reset_password':
            $userId = $_POST['user_id'];
            $newPassword = $_POST['new_password'];
            
            $validation = $securityManager->validatePassword($newPassword, $userId);
            if (!$validation['valid']) {
                $error = $validation['message'];
            } else {
                $result = $securityManager->updatePassword($userId, $newPassword);
                if ($result['success']) {
                    $auditLogger->logAction($_SESSION['user_id'], 'password_reset', 'users', $userId, "Admin reset password for user ID: $userId");
                    $message = "Password reset successfully!";
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'toggle_status':
            $userId = $_POST['user_id'];
            $currentStatus = $_POST['current_status'];
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            
            $query = "UPDATE users SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $newStatus, $userId);
            
            if ($stmt->execute()) {
                $auditLogger->logAction($_SESSION['user_id'], 'user_status_change', 'users', $userId, "Changed status to: $newStatus");
                $message = "User status updated!";
            } else {
                $error = "Failed to update status.";
            }
            break;
    }
}

// Get all users
$usersQuery = "SELECT u.*, 
               (SELECT COUNT(*) FROM login_attempts la WHERE la.user_id = u.id AND la.success = 1) as login_count,
               (SELECT MAX(created_at) FROM audit_log WHERE user_id = u.id) as last_activity
               FROM users u 
               ORDER BY u.created_at DESC";
$users = mysqli_query($conn, $usersQuery);
$user_array = [];
if($users) {
    while($row = mysqli_fetch_assoc($users)) {
        $user_array[] = $row;
    }
}

// Get user statistics
$statsQuery = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN user_role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                SUM(CASE WHEN user_role = 'doctor' THEN 1 ELSE 0 END) as doctor_count,
                SUM(CASE WHEN user_role = 'patient' THEN 1 ELSE 0 END) as patient_count,
                SUM(CASE WHEN user_role = 'pharmacist' THEN 1 ELSE 0 END) as pharmacist_count,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_30d
               FROM users";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);

// Establish layout variables
$active_page = 'user_management';
$page_title  = 'User Management';
include '../includes/_sidebar.php';

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
/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #000 40%));
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; }
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }

/* ── Stat Mini Cards ── */
.stat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.5rem;margin-bottom:2.5rem; }
.stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);
  padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);cursor:pointer;box-shadow:var(--shadow-sm); }
.stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); }
.stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--primary); }
.stat-mini-lbl { font-size:1.15rem;font-weight:500;color:var(--text-secondary);margin-top:.6rem; text-transform:uppercase; letter-spacing:0.05em; }

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
.badge-admin { background:var(--danger-light);color:var(--danger); }
.badge-doctor { background:var(--primary-light);color:var(--primary); }
.badge-patient { background:var(--success-light);color:var(--success); }
.badge-pharmacist { background:#f3e5f5;color:#8e44ad; }
.badge-nurse { background:var(--warning-light);color:var(--warning); }
.badge-active { background:var(--success-light);color:var(--success); }
.badge-inactive { background:var(--danger-light);color:var(--danger); }

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
.btn-danger { background:var(--danger);color:#fff; }
.btn-warning { background:var(--warning);color:#fff; }
.btn-success { background:var(--success);color:#fff; }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }
.btn-sm { padding:.6rem 1.2rem;font-size:1.1rem; }

/* ── Card System ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
.card-header h3 { font-size:1.6rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Form Controls ── */
.form-row { display:grid;grid-template-columns:1fr 1fr;gap:1.5rem; }
.form-group { margin-bottom:1.6rem; }
.form-group label { display:block;font-size:1.15rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em; }
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.2rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }

/* ── Toast ── */
#toastWrap { position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem; }
.toast-msg { padding:1.2rem 2rem; border-radius:var(--radius-sm); background:var(--surface); box-shadow:var(--shadow-lg); border-left:5px solid var(--primary); font-size:1.2rem; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:1rem; animation:fadePop .3s ease; }
.toast-success { border-left-color:var(--success); }
.toast-danger { border-left-color:var(--danger); }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-users-cog"></i> User Management</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content" style="animation:fadePop .35s ease;">
        
        <div class="staff-hero">
            <div class="staff-hero-avatar"><i class="fas fa-id-badge"></i></div>
            <div class="staff-hero-info">
                <h2>Staff Directory & Access Control</h2>
                <p>Manage users, assign roles, and maintain system security.</p>
            </div>
            <div style="margin-left:auto;">
                <button class="btn btn-primary" onclick="openModal('createModal')">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-val"><?php echo $stats['total_users']; ?></div>
                <div class="stat-mini-lbl">Total Users</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-val" style="color:var(--success);"><?php echo $stats['active_count']; ?></div>
                <div class="stat-mini-lbl">Active Users</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-val" style="color:var(--info);"><?php echo $stats['doctor_count']; ?></div>
                <div class="stat-mini-lbl">Doctors</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-val" style="color:var(--warning);"><?php echo $stats['new_users_30d']; ?></div>
                <div class="stat-mini-lbl">New (30 Days)</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-table"></i> System Users</h3>
            </div>
            <div class="card-body">
                <div class="filter-tabs">
                    <button class="ftab active" data-filter="">All Users</button>
                    <button class="ftab" data-filter="Admin">Admins</button>
                    <button class="ftab" data-filter="Doctor">Doctors</button>
                    <button class="ftab" data-filter="Nurse">Nurses</button>
                    <button class="ftab" data-filter="Pharmacist">Pharmacists</button>
                    <button class="ftab" data-filter="Patient">Patients</button>
                </div>

                <table class="stf-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>User Details</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Logins</th>
                            <th>Last Activity</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_array as $user): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:1rem;">
                                        <div style="width:40px;height:40px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:1.4rem;font-weight:700;">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong style="font-size:1.3rem;"><?php echo htmlspecialchars($user['name']); ?></strong><br>
                                            <small style="color:var(--text-muted);font-size:1.1rem;"><?php echo htmlspecialchars($user['user_name']); ?> | <?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['user_role'] === 'admin' ? 'admin' : ($user['user_role'] === 'doctor' ? 'doctor' : ($user['user_role'] === 'patient' ? 'patient' : ($user['user_role'] === 'pharmacist' ? 'pharmacist' : 'nurse'))); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['user_role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo ($user['status'] ?? 'active') === 'active' ? 'active' : 'inactive'; ?>">
                                        <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['login_count']; ?></td>
                                <td><?php echo $user['last_activity'] ? date('M d, Y', strtotime($user['last_activity'])) : 'Never'; ?></td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; gap:0.5rem;">
                                        <button class="btn btn-primary btn-sm" onclick='editUser(<?php echo json_encode($user); ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-warning btn-sm" onclick="openResetPassword(<?php echo $user['id']; ?>)" title="Reset Password"><i class="fas fa-key"></i></button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['user_name']); ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modals -->

<!-- Create User Modal -->
<div id="createModal" class="modal-bg">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus" style="color:var(--primary);"></i> Add New User</h3>
            <button class="modal-close" onclick="closeModal('createModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_user">
            
            <div class="form-group">
                <label>Username <span style="color:var(--danger)">*</span></label>
                <input type="text" name="username" id="usernameField" class="form-control" required>
                <div id="usernameMsg" style="font-size: 1.1rem; margin-top: 4px;"></div>
            </div>
            
            <div class="form-group">
                <label>Full Name <span style="color:var(--danger)">*</span></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email <span style="color:var(--danger)">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Role <span style="color:var(--danger)">*</span></label>
                    <select name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="doctor">Doctor</option>
                        <option value="nurse">Nurse</option>
                        <option value="pharmacist">Pharmacist</option>
                        <option value="lab_technician">Lab Technician</option>
                        <option value="maintenance">Maintenance Officer</option>
                        <option value="security">Security Officer</option>
                        <option value="cleaner">Cleaner</option>
                        <option value="ambulance_driver">Ambulance Driver</option>
                        <option value="laundry_staff">Laundry Personnel</option>
                        <option value="kitchen_staff">Kitchen Staff</option>
                        <option value="finance_officer">Finance Officer</option>
                        <option value="finance_manager">Finance Manager</option>
                        <option value="patient">Patient</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Password <span style="color:var(--danger)">*</span></label>
                <input type="password" name="password" class="form-control" required>
                <small style="color:var(--text-muted); font-size:1.1rem; margin-top:0.4rem; display:block;">Min 8 chars, uppercase, lowercase, number, special char</small>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:2.5rem;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('createModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal-bg">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit" style="color:var(--primary);"></i> Edit User</h3>
            <button class="modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label>Full Name <span style="color:var(--danger)">*</span></label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email <span style="color:var(--danger)">*</span></label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Role <span style="color:var(--danger)">*</span></label>
                    <select name="role" id="edit_role" class="form-control" required>
                        <option value="admin">Admin</option>
                        <option value="doctor">Doctor</option>
                        <option value="nurse">Nurse</option>
                        <option value="pharmacist">Pharmacist</option>
                        <option value="lab_technician">Lab Technician</option>
                        <option value="maintenance">Maintenance Officer</option>
                        <option value="security">Security Officer</option>
                        <option value="cleaner">Cleaner</option>
                        <option value="ambulance_driver">Ambulance Driver</option>
                        <option value="laundry_staff">Laundry Personnel</option>
                        <option value="kitchen_staff">Kitchen Staff</option>
                        <option value="finance_officer">Finance Officer</option>
                        <option value="finance_manager">Finance Manager</option>
                        <option value="patient">Patient</option>
                    </select>
                </div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:2.5rem;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal-bg">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-key" style="color:var(--warning);"></i> Reset Password</h3>
            <button class="modal-close" onclick="closeModal('resetPasswordModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="reset_user_id">
            
            <div class="form-group">
                <label>New Password <span style="color:var(--danger)">*</span></label>
                <input type="password" name="new_password" class="form-control" required>
                <small style="color:var(--text-muted); font-size:1.1rem; margin-top:0.4rem; display:block;">Min 8 chars, uppercase, lowercase, number, special char</small>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:2.5rem;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('resetPasswordModal')">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Reset Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" name="user_id" id="delete_user_id">
</form>

<div id="toastWrap"></div>

<script>
function showToast(msg, type='success') {
    const toast = document.createElement('div');
    toast.className = `toast-msg toast-${type}`;
    toast.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> <span>${msg}</span>`;
    document.getElementById('toastWrap').appendChild(toast);
    setTimeout(()=> { toast.style.opacity='0'; setTimeout(()=>toast.remove(),300); }, 3000);
}

// Show PHP-injected message if present
<?php if ($message): ?>
document.addEventListener('DOMContentLoaded', () => { showToast(<?php echo json_encode($message); ?>, 'success'); });
<?php endif; ?>
<?php if ($error): ?>
document.addEventListener('DOMContentLoaded', () => { showToast(<?php echo json_encode($error); ?>, 'danger'); });
<?php endif; ?>

let usersTable;
$(document).ready(function() {
    usersTable = $('#usersTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: { search: "", searchPlaceholder: "Search users..." }
    });

    // Filter pills logic
    $('.ftab').on('click', function() {
        $('.ftab').removeClass('active');
        $(this).addClass('active');
        const filterVal = $(this).data('filter');
        usersTable.column(1).search(filterVal).draw();
    });
});

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.user_role;
    openModal('editModal');
}

function openResetPassword(userId) {
    document.getElementById('reset_user_id').value = userId;
    openModal('resetPasswordModal');
}

function deleteUser(userId, username) {
    if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        document.getElementById('delete_user_id').value = userId;
        document.getElementById('deleteForm').submit();
    }
}

// AJAX Username Check
const usernameField = document.getElementById('usernameField');
const usernameMsg   = document.getElementById('usernameMsg');
let usernameTimer;

usernameField?.addEventListener('input', () => {
    clearTimeout(usernameTimer);
    const val = usernameField.value.trim();
    if (val.length < 3) {
        usernameMsg.innerHTML = '<span style="color:var(--text-muted);"><i class="fas fa-info-circle"></i> Too short...</span>';
        return;
    }

    usernameMsg.innerHTML = '<span style="color:var(--primary);"><i class="fas fa-spinner fa-spin"></i> Checking...</span>';
    usernameTimer = setTimeout(() => {
        const fd = new FormData();
        fd.append('username', val);
        fetch('../ajax/check_username.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    usernameMsg.innerHTML = '<span style="color:var(--success);"><i class="fas fa-check-circle"></i> ' + d.msg + '</span>';
                } else {
                    usernameMsg.innerHTML = '<span style="color:var(--danger);"><i class="fas fa-times-circle"></i> ' + d.msg + '</span>';
                }
            })
            .catch(() => {
                usernameMsg.innerHTML = '<span style="color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Error checking username.</span>';
            });
    }, 500);
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
