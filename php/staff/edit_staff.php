<?php
session_start();
require_once '../db_conn.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /RMU-Medical-Management-System/php/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "<script>alert('Staff ID missing.'); window.location.href='/RMU-Medical-Management-System/php/staff/staff.php';</script>";
    exit;
}

$staff_uid = (int)$_GET['id'];
$success_msg = '';
$error_msg   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $name   = mysqli_real_escape_string($conn, $_POST['name']);
    $email  = mysqli_real_escape_string($conn, $_POST['email']);
    $phone  = mysqli_real_escape_string($conn, $_POST['phone']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $role   = mysqli_real_escape_string($conn, $_POST['role']);
    $dept   = mysqli_real_escape_string($conn, $_POST['department']);

    mysqli_begin_transaction($conn);
    try {
        // Update users table
        $u_stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, gender=?, user_role=? WHERE id=?");
        $u_stmt->bind_param("sssssi", $name, $email, $phone, $gender, $role, $staff_uid);
        $u_stmt->execute();
        
        // Update staff_directory table
        $s_stmt = $conn->prepare("UPDATE staff_directory SET full_name=?, email=?, phone=?, role=?, department=? WHERE user_id=?");
        $s_stmt->bind_param("sssssi", $name, $email, $phone, $role, $dept, $staff_uid);
        $s_stmt->execute();
        
        mysqli_commit($conn);
        $success_msg = "Staff record updated successfully.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_msg = "Error updating staff: " . $e->getMessage();
    }
}

// Fetch current details
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, u.phone, u.gender, u.user_role, u.is_active, s.department, s.staff_id 
    FROM users u 
    LEFT JOIN staff_directory s ON u.id = s.user_id 
    WHERE u.id = ?");
$stmt->bind_param("i", $staff_uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo "<script>alert('Staff not found.'); window.location.href='/RMU-Medical-Management-System/php/staff/staff.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff - RMU Medical Sickbay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <style>
        .edit-container { max-width: 800px; margin: 3rem auto; background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { width: 100%; }
        .form-label { display: block; font-weight: 600; font-size: 1.1rem; color: var(--text-secondary); margin-bottom: 0.6rem; text-transform: uppercase; letter-spacing: 1px; }
        .form-control { width: 100%; padding: 1rem 1.2rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; font-size: 1.2rem; color: var(--text-primary); background: var(--surface); }
        .form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(47,128,237,0.1); }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 1rem 2rem; border-radius: var(--radius-md); font-weight: 600; font-size: 1.2rem; cursor: pointer; transition: background 0.3s; }
        .btn-primary:hover { background: #246ac2; }
        .alert { padding: 1.2rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; font-size: 1.1rem; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--text-secondary); text-decoration: none; margin-bottom: 1rem; font-weight: 600; font-size: 1.2rem; }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <div style="padding: 2rem;">
        <a href="/RMU-Medical-Management-System/php/staff/staff.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Staff List</a>
        <div class="edit-container">
            <h2 style="font-size: 2.2rem; margin-bottom: 0.5rem; color: var(--text-primary);">Edit Staff Member</h2>
            <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 1.1rem;">Update biographical and role information for staff ID: <?php echo htmlspecialchars($row['staff_id'] ?? $row['id']); ?></p>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($row['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male" <?php echo $row['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $row['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $row['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">System Role</label>
                        <select name="role" class="form-control" required>
                            <option value="doctor" <?php echo $row['user_role'] === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                            <option value="nurse" <?php echo $row['user_role'] === 'nurse' ? 'selected' : ''; ?>>Nurse</option>
                            <option value="pharmacist" <?php echo $row['user_role'] === 'pharmacist' ? 'selected' : ''; ?>>Pharmacist</option>
                            <option value="staff" <?php echo $row['user_role'] === 'staff' ? 'selected' : ''; ?>>General Staff</option>
                            <option value="admin" <?php echo $row['user_role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-control" required>
                            <option value="General" <?php echo $row['department'] === 'General' ? 'selected' : ''; ?>>General</option>
                            <option value="Emergency" <?php echo $row['department'] === 'Emergency' ? 'selected' : ''; ?>>Emergency</option>
                            <option value="Pharmacy" <?php echo $row['department'] === 'Pharmacy' ? 'selected' : ''; ?>>Pharmacy</option>
                            <option value="Laboratory" <?php echo $row['department'] === 'Laboratory' ? 'selected' : ''; ?>>Laboratory</option>
                            <option value="Administration" <?php echo $row['department'] === 'Administration' ? 'selected' : ''; ?>>Administration</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem; align-items: center;">
                    <button type="submit" name="update_staff" class="btn btn-primary"><span class="btn-text"><i class="fas fa-save"></i> Update Record</span></button>
                    <?php if ($row['is_active']): ?>
                        <a href="deactivate_staff.php?id=<?php echo $row['id']; ?>" class="back-link" style="color: #e74c3c; margin: 0;" onclick="return confirm('Are you sure you want to deactivate this staff member?');"><i class="fas fa-ban"></i> Deactivate Account</a>
                    <?php else: ?>
                        <span style="color: #e74c3c; font-weight: 600;"><i class="fas fa-lock"></i> Account Currently Inactive</span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
