<?php
// ============================================================
// PROCESS MEDICATION (AJAX Endpoint)
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
if ($action !== 'administer_med') {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// Gather & Validate Inputs
$admin_pk = validateInt($_POST['admin_id'] ?? 0);
$status   = sanitize($_POST['med_status'] ?? '');
$v_method = sanitize($_POST['verification_method'] ?? 'Manual');
$notes    = sanitize($_POST['notes'] ?? '');

$valid_statuses = ['Administered', 'Refused', 'Held', 'Missed'];
if (!$admin_pk || !in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit;
}

if ($status !== 'Administered' && empty(trim($notes))) {
    echo json_encode(['success' => false, 'message' => 'Notes are required when status is not Administered']);
    exit;
}

// Get nurse PK
$nurse_pk = dbVal($conn, "SELECT id FROM nurses WHERE user_id=$nurse_id LIMIT 1", "i", []);

// Check record exists and is pending
$current_state = dbRow($conn, "SELECT status, patient_id, medicine_name FROM medication_administration WHERE id=?", "i", [$admin_pk]);
if (!$current_state) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit;
}

if ($current_state['status'] !== 'Pending') {
    echo json_encode(['success' => false, 'message' => 'This medication has already been processed']);
    exit;
}

// Update Database
$stmt = mysqli_prepare($conn, "
    UPDATE medication_administration 
    SET status = ?, 
        verification_method = ?, 
        notes = ?,
        nurse_id = ?, 
        administered_at = NOW() 
    WHERE id = ?
");
mysqli_stmt_bind_param($stmt, "sssii", $status, $v_method, $notes, $nurse_pk, $admin_pk);

if (mysqli_stmt_execute($stmt)) {
    // Log Activity
    $action_desc = "Marked {$current_state['medicine_name']} as $status for Patient PK: {$current_state['patient_id']}";
    secureLogNurse($conn, $nurse_pk, $action_desc, "medication");

    // Notify Doctor if Missed/Refused
    if (in_array($status, ['Refused', 'Missed'])) {
        $pat_q = mysqli_query($conn, "
            SELECT u.name, ba.doctor_id 
            FROM patients p 
            JOIN users u ON p.user_id = u.id 
            LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status='Occupied'
            WHERE p.id = {$current_state['patient_id']} LIMIT 1
        ");
        if ($pat_row = mysqli_fetch_assoc($pat_q)) {
            if ($pat_row['doctor_id']) {
                $doc_user_id = dbVal($conn, "SELECT user_id FROM doctors WHERE id=?", "i", [$pat_row['doctor_id']]);
                if ($doc_user_id) {
                    $msg = "Medication $status: {$current_state['medicine_name']} for Patient {$pat_row['name']}. Reason: $notes";
                    dbExecute($conn, 
                        "INSERT INTO notifications (user_id, message, type, related_module, related_id, created_at) VALUES (?, ?, 'Medication Alert', 'medication_administration', ?, NOW())",
                        "isi", [$doc_user_id, $msg, $admin_pk]
                    );
                }
            }
        }
    }

    echo json_encode(['success' => true, 'message' => "Medication status updated successfully."]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
