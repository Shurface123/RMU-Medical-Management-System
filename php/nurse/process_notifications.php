<?php
// ============================================================
// PROCESS NOTIFICATIONS (NURSE)
// ============================================================
require_once '../dashboards/nurse_security.php';
initSecureSession();
$nurse_id = enforceNurseRole();
require_once '../db_conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}

verifyCsrfToken($_POST['_csrf'] ?? '');
$action = sanitize($_POST['action'] ?? '');

if ($action === 'mark_read') {
    $notif_id = validateInt($_POST['notification_id'] ?? 0);
    if ($notif_id > 0) {
        if (dbExecute($conn, "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", "ii", [$notif_id, $nurse_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Query failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    }
    exit;
}

if ($action === 'mark_all_read') {
    if (dbExecute($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0", "i", [$nurse_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Query failed']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
