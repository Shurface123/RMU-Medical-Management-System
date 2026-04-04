<?php
/**
 * check_username.php — AJAX Endpoint for Username Availability
 * Returns JSON: { "ok": true/false, "msg": "..." }
 */
header('Content-Type: application/json');
require_once '../db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid request method.']);
    exit;
}

$username = trim($_POST['username'] ?? '');

if (empty($username)) {
    echo json_encode(['ok' => false, 'msg' => 'Username cannot be empty.']);
    exit;
}

if (strlen($username) < 3) {
    echo json_encode(['ok' => false, 'msg' => 'Username must be at least 3 characters.']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-0._]+$/', $username)) {
    echo json_encode(['ok' => false, 'msg' => 'Username can only contain letters, numbers, dots, and underscores.']);
    exit;
}

// Check for existing username
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE user_name = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['ok' => false, 'msg' => 'This username is already taken.']);
} else {
    echo json_encode(['ok' => true, 'msg' => 'Username is available.']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
