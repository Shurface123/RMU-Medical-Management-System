<?php
session_start();
require_once '../db_conn.php';
header('Content-Type: application/json');

if ($_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = (int)$_SESSION['user_id'];

if ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND (user_id = ? OR user_role = 'admin' OR user_role IS NULL)");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
            exit;
        }
    }
} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE notification_id = ? AND (user_id = ? OR user_role = 'admin' OR user_role IS NULL)");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
            exit;
        }
    }
} elseif ($action === 'mark_all_read') {
    $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND (user_id = ? OR user_role = 'admin' OR user_role IS NULL)");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
        exit;
    }
} elseif ($action === 'clear_all') {
    $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE user_id = ? OR user_role = 'admin' OR user_role IS NULL");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request or database error']);
