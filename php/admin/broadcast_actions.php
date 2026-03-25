<?php
/**
 * Broadcast Actions Handler
 * Handles AJAX requests from the Broadcast Management Panel.
 */
session_start();
require_once '../db_conn.php';
require_once '../classes/BroadcastManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$bm = new BroadcastManager($conn);
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $subject = trim($_POST['subject'] ?? '');
        $body = $_POST['body'] ?? ''; // This might contain HTML from a rich text editor
        $priority = $_POST['priority'] ?? 'Informational';
        $audience_type = $_POST['audience_type'] ?? 'Everyone';
        $audience_ids = isset($_POST['audience_ids']) ? json_decode($_POST['audience_ids'], true) : [];
        $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : date('Y-m-d H:i:s');
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $requires_ack = isset($_POST['requires_acknowledgement']) ? 1 : 0;

        if (empty($subject) || empty($body)) {
            echo json_encode(['success' => false, 'message' => 'Subject and body are required']);
            break;
        }

        // Handle File Attachment
        $attachment_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/broadcasts/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $filename = 'bc_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $filename)) {
                    $attachment_path = 'uploads/broadcasts/' . $filename;
                }
            }
        }

        $result = $bm->createBroadcast([
            'subject' => $subject,
            'body' => $body,
            'priority' => $priority,
            'sender_id' => $_SESSION['user_id'],
            'audience_type' => $audience_type,
            'audience_ids' => $audience_ids,
            'scheduled_at' => $scheduled_at,
            'expires_at' => $expires_at,
            'requires_acknowledgement' => $requires_ack,
            'attachment_path' => $attachment_path
        ]);

        echo json_encode($result);
        break;

    case 'cancel':
        $id = (int)$_POST['id'];
        $q = "UPDATE broadcasts SET status = 'Cancelled' WHERE id = ? AND status = 'Scheduled'";
        $stmt = $conn->prepare($q);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        break;

    case 'get_stats':
        $id = (int)$_GET['id'];
        $q = "SELECT 
                (SELECT COUNT(*) FROM broadcast_recipients WHERE broadcast_id = ?) as total,
                (SELECT COUNT(*) FROM broadcast_recipients WHERE broadcast_id = ? AND delivered_at IS NOT NULL) as delivered,
                (SELECT COUNT(*) FROM broadcast_recipients WHERE broadcast_id = ? AND read_at IS NOT NULL) as read_count,
                (SELECT COUNT(*) FROM broadcast_recipients WHERE broadcast_id = ? AND acknowledged_at IS NOT NULL) as ack_count";
        $stmt = $conn->prepare($q);
        $stmt->bind_param("iiii", $id, $id, $id, $id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    case 'get_recipients':
        $id = (int)$_GET['id'];
        $q = "SELECT r.*, u.name 
              FROM broadcast_recipients r
              JOIN users u ON r.recipient_id = u.id
              WHERE r.broadcast_id = ?
              ORDER BY u.name ASC";
        $stmt = $conn->prepare($q);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'recipients' => $recipients]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}
