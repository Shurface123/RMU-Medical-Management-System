<?php
// API Endpoint: Fetch Health Message for current role
session_start();
header('Content-Type: application/json');

require_once 'db_conn.php';

$role = $_SESSION['role'] ?? 'patient';
$safe_role = mysqli_real_escape_string($conn, $role);

// Fetch random active message designed for this role OR all roles (NULL)
$query = "SELECT message_text FROM health_messages 
          WHERE is_active = 1 
          AND (target_role = '$safe_role' OR target_role IS NULL OR target_role = '') 
          ORDER BY RAND() LIMIT 1";

$result = mysqli_query($conn, $query);

// Fetch active configuration natively
$cfgQ = mysqli_query($conn, "SELECT countdown_duration_seconds, redirect_url FROM logout_config LIMIT 1");
$cfg = mysqli_fetch_assoc($cfgQ);
$duration = $cfg ? (int)$cfg['countdown_duration_seconds'] : 3;
$redirect = ($cfg && !empty($cfg['redirect_url'])) ? $cfg['redirect_url'] : '/RMU-Medical-Management-System/php/index.php';

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    // Cache the drawn message into session so logout.php can grab it for the audit log
    $_SESSION['health_message_shown'] = $row['message_text'];
    echo json_encode(['success' => true, 'message' => $row['message_text'], 'duration' => $duration, 'redirect' => $redirect]);
} else {
    $default = "Your health is your greatest wealth. Stay safe.";
    $_SESSION['health_message_shown'] = $default;
    echo json_encode(['success' => true, 'message' => $default, 'duration' => $duration, 'redirect' => $redirect]);
}
