<?php
session_start();
require_once '../db_conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$curr_sid = session_id();
$action = $_POST['action'] ?? '';

if ($action === 'logout_specific') {
    $target_sid = $_POST['session_id'] ?? '';
    if (!$target_sid) {
        echo json_encode(['success' => false, 'message' => 'Missing session ID']);
        exit;
    }
    
    // Instead of directly deleting the session, queue a forced logout so the remote device gets the UI notification
    // But since the queue operates per user_id, a queue per session_id would be better. 
    // Since our queue currently uses user_id, we can safely delete from active_sessions directly to invalidate instantly:
    $d = mysqli_prepare($conn, "DELETE FROM active_sessions WHERE user_id = ? AND session_id = ? AND session_id != ?");
    mysqli_stmt_bind_param($d, 'iss', $user_id, $target_sid, $curr_sid);
    $ok = mysqli_stmt_execute($d);
    
    if($ok) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $role = $_SESSION['role'] ?? 'unknown';
        // Log manual termination
        $dl = mysqli_prepare($conn, "INSERT INTO logout_logs (user_id, role, session_id, logout_type, ip_address, browser, dashboard_origin) VALUES (?, ?, ?, 'manual', ?, ?, 'profile_multi_device')");
        mysqli_stmt_bind_param($dl, 'issss', $user_id, $role, $target_sid, $ip, $ua);
        @mysqli_stmt_execute($dl);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Query failed']);
    }
} 
elseif ($action === 'logout_all_other') {
    // Delete all except current
    $d = mysqli_prepare($conn, "DELETE FROM active_sessions WHERE user_id = ? AND session_id != ?");
    mysqli_stmt_bind_param($d, 'is', $user_id, $curr_sid);
    $ok = mysqli_stmt_execute($d);
    
    if($ok) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $role = $_SESSION['role'] ?? 'unknown';
        // We log one broad event for this
        $dl = mysqli_prepare($conn, "INSERT INTO logout_logs (user_id, role, session_id, logout_type, ip_address, browser, dashboard_origin) VALUES (?, ?, 'ALL_OTHERS', 'manual', ?, ?, 'profile_multi_device')");
        mysqli_stmt_bind_param($dl, 'isss', $user_id, $role, $ip, $ua);
        @mysqli_stmt_execute($dl);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Query failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
