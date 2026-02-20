<?php
// ============================================================
// NOTIFICATIONS API — returns unread count + recent list
// GET /php/notifications_api.php         → count for bell
// GET /php/notifications_api.php?list=1  → recent 10 notifications
// POST /php/notifications_api.php        → mark_read
// ============================================================
header('Content-Type: application/json');
session_start();
require_once 'db_conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0, 'notifications' => []]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ── Mark a notification as read ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'mark_read' && isset($body['id'])) {
        $nid = (int)$body['id'];
        mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE id=$nid AND user_id=$user_id");
        echo json_encode(['success' => true]);
    } elseif ($action === 'mark_all_read') {
        mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$user_id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}

// ── Get unread count ─────────────────────────────────────────────────────
$count_row = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND is_read=0"
));
$count = (int)($count_row[0] ?? 0);

if (!isset($_GET['list'])) {
    echo json_encode(['count' => $count]);
    exit;
}

// ── Get recent notifications (for dropdown list) ─────────────────────────
$notifs = [];
$q = mysqli_query($conn,
    "SELECT id, title, message, type, is_read, link, created_at
     FROM notifications
     WHERE user_id = $user_id
     ORDER BY created_at DESC
     LIMIT 10"
);
if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $row['time_ago'] = time_ago($row['created_at']);
        $notifs[] = $row;
    }
}

echo json_encode(['count' => $count, 'notifications' => $notifs]);

// ── Helper ────────────────────────────────────────────────────────────────
function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)   return 'Just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}
