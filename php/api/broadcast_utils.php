<?php
/**
 * Broadcast Utils API
 * Handles user interactions with broadcasts (acknowledge, read).
 */
session_start();
require_once '../db_conn.php';
require_once '../classes/BroadcastManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$bm = new BroadcastManager($conn);
$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

switch ($action) {
    case 'acknowledge':
        $res = $bm->acknowledge($id, $user_id);
        echo json_encode(['success' => $res]);
        break;

    case 'mark_read':
        $res = $bm->markAsRead($id, $user_id);
        echo json_encode(['success' => $res]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}
