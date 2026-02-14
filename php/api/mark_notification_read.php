<?php
/**
 * MARK NOTIFICATION AS READ API
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
$input = json_decode(file_get_contents('php://input'), true);
$notificationId = isset($input['notification_id']) ? (int)$input['notification_id'] : 0;

if (!$notificationId) {
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    exit;
}

try {
    $query = "UPDATE notifications SET is_read = 1, read_at = NOW() 
              WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notificationId, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
