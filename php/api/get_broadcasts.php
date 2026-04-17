<?php
/**
 * get_broadcasts.php
 * Fetch active broadcasts for the current user.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db_conn.php';
require_once '../classes/BroadcastManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
session_write_close(); // Release lock for polling
$role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

$broadcasts = $bm->getActiveForUser($user_id, $role);

echo json_encode([
    'success' => true,
    'broadcasts' => $broadcasts
]);
