<?php
// ================================================================
// API ENDPOINT: /api/notifications
// Handles listing and marking notifications read/dismissed
// ================================================================

/**
 * Handle notification requests.
 *
 * @param string   $method      HTTP method
 * @param int      $userId      Authenticated user ID
 * @param int|null $notifId     Optional notification ID from URL path
 */
function handleNotifications(string $method, int $userId, ?int $notifId = null): void {
    global $conn;

    switch (strtoupper($method)) {

        case 'GET':
            $limit  = min((int)($_GET['limit'] ?? 50), 100);
            $offset = max((int)($_GET['offset'] ?? 0), 0);
            $unread = isset($_GET['unread']) ? 1 : null;

            $where = "user_id = ?";
            $types = 'i';
            $params = [$userId];

            if ($unread !== null) {
                $where  .= " AND is_read = 0";
            }

            $stmt = $conn->prepare(
                "SELECT id, title, message, type, module, is_read, created_at
                 FROM notifications
                 WHERE $where
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?"
            );
            $types   .= 'ii';
            $params[] = $limit;
            $params[] = $offset;
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }

            // Unread count
            $countStmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $countStmt->bind_param('i', $userId);
            $countStmt->execute();
            $unreadCount = (int)$countStmt->get_result()->fetch_row()[0];

            ApiResponse::success([
                'notifications' => $notifications,
                'unread_count'  => $unreadCount,
            ]);
            break;

        case 'PUT':
        case 'PATCH':
            // Mark one or all as read
            if ($notifId) {
                $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->bind_param('ii', $notifId, $userId);
                $stmt->execute();
                ApiResponse::success(null, 'Notification marked as read');
            } else {
                // Mark all as read
                $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                ApiResponse::success(null, 'All notifications marked as read');
            }
            break;

        case 'DELETE':
            if (!$notifId) {
                ApiResponse::error('Notification ID required', 400);
                return;
            }
            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $notifId, $userId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                ApiResponse::success(null, 'Notification deleted');
            } else {
                ApiResponse::error('Notification not found', 404);
            }
            break;

        default:
            ApiResponse::error('Method not allowed', 405);
    }
}
