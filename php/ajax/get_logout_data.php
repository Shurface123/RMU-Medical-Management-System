<?php
/**
 * ajax/get_logout_data.php
 * Fetches the logout configurations and a randomized health message targeted at the user's role.
 */
session_start();
require_once '../db_conn.php';

$res = [
    'duration' => 3, 
    'csrf' => '', 
    'message' => 'Remember to stay hydrated and wash your hands regularly.', 
    'msg_id' => null, 
    'redirect_url' => '/RMU-Medical-Management-System/php/index.php'
];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$res['csrf'] = $_SESSION['csrf_token'];

$role = $_SESSION['role'] ?? null;

// Ensure database connection
if ($conn) {
    // 1. Fetch Config
    $cfgQ = mysqli_query($conn, "SELECT countdown_duration, redirect_url FROM logout_config LIMIT 1");
    if ($cfgQ && $cfg = mysqli_fetch_assoc($cfgQ)) {
        $res['duration'] = (int)$cfg['countdown_duration'];
        if (!empty($cfg['redirect_url'])) {
            $res['redirect_url'] = $cfg['redirect_url'];
        }
    }

    // 2. Fetch Random Health Message
    $query = "SELECT id, message_text FROM health_messages 
              WHERE is_active = 1 AND (target_role IS NULL OR target_role = ? OR target_role = '') 
              ORDER BY RAND() LIMIT 1";
    $msgQ = mysqli_prepare($conn, $query);
    if ($msgQ) {
        mysqli_stmt_bind_param($msgQ, 's', $role);
        mysqli_stmt_execute($msgQ);
        $msgRes = mysqli_stmt_get_result($msgQ);
        if ($m = mysqli_fetch_assoc($msgRes)) {
            $res['message'] = $m['message_text'];
            $res['msg_id'] = $m['id'];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($res);
?>
