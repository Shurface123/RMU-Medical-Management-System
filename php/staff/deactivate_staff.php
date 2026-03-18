<?php
session_start();
require_once '../db_conn.php';

// Check if user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /RMU-Medical-Management-System/php/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid Request. Staff ID is missing.'); window.location.href='/RMU-Medical-Management-System/php/staff/staff.php';</script>";
    exit();
}

$staff_uid = (int)$_GET['id'];

// Prevent duplicate execution logic
mysqli_begin_transaction($conn);
try {
    // 1. Find the user record
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    if (!$stmt) throw new Exception(mysqli_error($conn));
    $stmt->bind_param("i", $staff_uid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        // We have an active assignment, deactivate the user
        // Using a soft delete to keep historical records intact
        $update_usr = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $update_usr->bind_param("i", $staff_uid);
        $update_usr->execute();
        
        // Also update the staff_directory table if applicable
        $update_staff = $conn->prepare("UPDATE staff_directory SET status = 'Inactive' WHERE user_id = ?");
        $update_staff->bind_param("i", $staff_uid);
        $update_staff->execute();
    } else {
        throw new Exception("User not found.");
    }

    mysqli_commit($conn);
    header("Location: /RMU-Medical-Management-System/php/staff/staff.php?msg=deactivated");
    exit();
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Staff Deactivation Error: " . $e->getMessage());
    echo "<script>alert('System Error during deactivation.'); window.location.href='/RMU-Medical-Management-System/php/staff/staff.php';</script>";
    exit();
}
?>
