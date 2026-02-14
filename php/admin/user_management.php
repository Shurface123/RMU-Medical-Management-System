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
            
            // Validate password
            $validation = $securityManager->validatePassword($password, null);
            if (!$validation['valid']) {
                $error = $validation['message'];
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO users (user_name, email, password, name, user_role) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssss", $username, $email, $hashedPassword, $name, $role);
                
                if ($stmt->execute()) {
                    $auditLogger->logAction($_SESSION['user_id'], 'user_create', 'users', $stmt->insert_id, "Created user: $username");
                    $message = "User created successfully!";
                } else {
                    $error = "Failed to create user: " . $stmt->error;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - RMU Medical Sickbay</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-card .icon {
            float: right;
            font-size: 32px;
            opacity: 0.2;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: #e74c3c;
            color: white;
        }
        
        .badge-doctor {
            background: #3498db;
            color: white;
        }
        
        .badge-patient {
            background: #27ae60;
            color: white;
        }
        
        .badge-pharmacist {
            background: #9b59b6;
            color: white;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            color: #2c3e50;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> User Management</h1>
            <button class="btn btn-primary" onclick="openModal('createModal')">
                <i class="fas fa-plus"></i> Add New User
            </button>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users icon"></i>
                <h3>Total Users</h3>
                <div class="value"><?php echo $stats['total_users']; ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-shield icon"></i>
                <h3>Admins</h3>
                <div class="value"><?php echo $stats['admin_count']; ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-md icon"></i>
                <h3>Doctors</h3>
                <div class="value"><?php echo $stats['doctor_count']; ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-injured icon"></i>
                <h3>Patients</h3>
                <div class="value"><?php echo $stats['patient_count']; ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle icon"></i>
                <h3>Active Users</h3>
                <div class="value"><?php echo $stats['active_count']; ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-plus icon"></i>
                <h3>New (30 days)</h3>
                <div class="value"><?php echo $stats['new_users_30d']; ?></div>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="table-container">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search users..." onkeyup="searchTable()">
            </div>
            
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Logins</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['user_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['user_role']; ?>">
                                    <?php echo ucfirst($user['user_role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $user['status'] ?? 'active'; ?>">
                                    <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                </span>
                            </td>
                            <td><?php echo $user['login_count']; ?></td>
                            <td><?php echo $user['last_activity'] ? date('M j, Y', strtotime($user['last_activity'])) : 'Never'; ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick='editUser(<?php echo json_encode($user); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="openResetPassword(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['user_name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../home.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Create User Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New User</h2>
                <button class="close-modal" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_user">
                
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required>
                    <small style="color: #7f8c8d;">Min 8 chars, uppercase, lowercase, number, special char</small>
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="doctor">Doctor</option>
                        <option value="patient">Patient</option>
                        <option value="pharmacist">Pharmacist</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-plus"></i> Create User
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="edit_role" required>
                        <option value="admin">Admin</option>
                        <option value="doctor">Doctor</option>
                        <option value="patient">Patient</option>
                        <option value="pharmacist">Pharmacist</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Update User
                </button>
            </form>
        </div>
    </div>
    
    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reset Password</h2>
                <button class="close-modal" onclick="closeModal('resetPasswordModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" required>
                    <small style="color: #7f8c8d;">Min 8 chars, uppercase, lowercase, number, special char</small>
                </div>
                
                <button type="submit" class="btn btn-warning" style="width: 100%;">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="user_id" id="delete_user_id">
    </form>
    
    <script>
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
        
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('usersTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
