<?php
/**
 * GET NOTIFICATIONS API
 * Returns new notifications since last check
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
$sinceId = isset($_GET['since']) ? (int)$_GET['since'] : 0;

try {
    // Get new notifications
    $query = "SELECT n.*, u.user_name as from_user 
              FROM notifications n
              LEFT JOIN users u ON n.from_user_id = u.id
              WHERE n.user_id = ? AND n.id > ?
              ORDER BY n.created_at DESC
              LIMIT 20";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $sinceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'type' => $row['notification_type'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
            'from_user' => $row['from_user']
        ];
    }
    
    // Get unread count
    $countQuery = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param("i", $userId);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $unreadCount = $countResult->fetch_assoc()['unread_count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => (int)$unreadCount
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
