<?php
/**
 * staff_security.php
 * Handles session validation and brute-force protection
 * for the General Staff Dashboard.
 */
session_start();
require_once __DIR__ . '/../db_conn.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: ../index.php?error=Please login to access the Staff Dashboard");
    exit();
}

$allowed_roles = ['staff', 'ambulance_driver', 'cleaner', 'laundry_staff', 'maintenance', 'security', 'kitchen_staff'];

// Ensure user has a valid staff role
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: ../index.php?error=Access Denied. You do not have permission to view the Staff Dashboard.");
    exit();
}

// ── Shared Security Helpers ──

/**
 * Log Staff Activity to `staff_audit_trail`
 */
function logStaffActivity($conn, $staff_id, $action_type, $module, $record_id = null, $old = null, $new = null) {
    if (!$staff_id) return false;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $old_json = $old ? json_encode($old) : null;
    $new_json = $new ? json_encode($new) : null;

    $stmt = mysqli_prepare($conn, "INSERT INTO staff_audit_trail (staff_id, action_type, module, record_id_affected, old_value, new_value, ip_address, device, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ississss", $staff_id, $action_type, $module, $record_id, $old_json, $new_json, $ip, $ua);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return true;
    }
    return false;
}

/**
 * Helper to get active staff ID from user ID
 */
function getStaffId($conn, $user_id) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM staff WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        return (int)$row['id'];
    }
    return 0;
}
?>
