<?php
session_start();
require_once '../db_conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /RMU-Medical-Management-System/php/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password  = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_msg = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_msg = "Password must be at least 8 characters long.";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            if (password_verify($current_password, $user['password'])) {
                // Update to new password
                $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_new, $user_id);
                if ($update_stmt->execute()) {
                    $success_msg = "Password changed successfully.";
                } else {
                    $error_msg = "Failed to update password. Please try again.";
                }
            } else {
                $error_msg = "Incorrect current password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - RMU Medical Sickbay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="shortcut icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="apple-touch-icon" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <style>
        .auth-container { max-width: 500px; margin: 4rem auto; background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--text-primary); }
        .form-control { width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-family: inherit; }
        .form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(47,128,237,0.1); }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; width: 100%; transition: background 0.3s; }
        .btn-primary:hover { background: #246ac2; }
        .alert { padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--text-secondary); text-decoration: none; margin-bottom: 1rem; font-weight: 500; }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <div style="padding: 2rem;">
        <a href="javascript:history.back()" class="back-link"><i class="fas fa-arrow-left"></i> Back to Settings</a>
        <div class="auth-container">
            <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: var(--text-primary);">Change Password</h2>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">Update your account password securely.</p>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required placeholder="Enter current password">
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required placeholder="Enter new password (min. 8 characters)">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Re-enter new password">
                </div>
                <button type="submit" name="update_password" class="btn-primary">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>
