<?php
// ============================================================
// PROCESS DOCTOR-NURSE COMMUNICATION (AJAX Endpoint)
// ============================================================
require_once '../dashboards/nurse_security.php';
initSecureSession();
$nurse_id = enforceNurseRole(); // This is the user_id (PK of users table)
require_once '../db_conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}

verifyCsrfToken($_POST['_csrf'] ?? '');
$action = sanitize($_POST['action'] ?? '');

if ($action === 'send_message') {
    $receiver_id = validateInt($_POST['receiver_id'] ?? 0);
    $patient_id  = validateInt($_POST['patient_id'] ?? 0);
    $subject     = sanitize($_POST['subject'] ?? '');
    $content     = sanitize($_POST['message_content'] ?? '');

    if (!$receiver_id || empty($subject) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Recipient, Subject, and Message Body are required.']);
        exit;
    }

    // Identify receiver role
    $rec_role = dbVal($conn, "SELECT user_role FROM users WHERE id=?", "i", [$receiver_id]);
    if (!$rec_role || !in_array($rec_role, ['doctor', 'admin', 'nurse', 'lab_technician', 'pharmacist'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid message recipient role.']);
        exit;
    }

    $patient_val = $patient_id > 0 ? $patient_id : null;

    $stmt = mysqli_prepare($conn, "
        INSERT INTO lab_internal_messages (sender_id, sender_role, receiver_id, receiver_role, patient_id, subject, message_content) 
        VALUES (?, 'nurse', ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iisiss", $nurse_id, $receiver_id, $rec_role, $patient_val, $subject, $content);

    if (mysqli_stmt_execute($stmt)) {
        
        // Notify Receiver
        dbExecute($conn, 
            "INSERT INTO notifications (user_id, message, type, related_module, created_at) VALUES (?, ?, 'Nursing Message', 'messages', NOW())",
            "is", [$receiver_id, "New direct message from Nursing: " . substr($subject, 0, 30)]
        );

        echo json_encode(['success' => true, 'message' => 'Message successfully delivered.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error preventing message dispatch.']);
    }
    exit;

} elseif ($action === 'mark_read') {
    $msg_id = validateInt($_POST['msg_id'] ?? 0);

    if ($msg_id) {
        // Ensure this user is the receiver
        dbExecute($conn, "UPDATE lab_internal_messages SET is_read = 1, read_at = NOW() WHERE id = ? AND receiver_id = ? AND is_read = 0", "ii", [$msg_id, $nurse_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Action']);
