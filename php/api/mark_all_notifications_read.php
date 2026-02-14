<?php
/**
 * MARK ALL NOTIFICATIONS AS READ API
 */
session_start();
header('Content-Type: application/json');

require_once '../db_conn.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $query = "UPDATE notifications SET is_read = 1, read_at = NOW() 
              WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
