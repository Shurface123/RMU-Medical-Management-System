<?php
// ============================================================
// PROCESS NURSING NOTES (AJAX Endpoint)
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

$nurse_pk = dbVal($conn, "SELECT id FROM nurses WHERE user_id=$nurse_id LIMIT 1", "i", []);

if ($action === 'add_note') {
    $shift_id     = validateInt($_POST['shift_id'] ?? 0);
    $patient_id   = validateInt($_POST['patient_id'] ?? 0);
    $note_type    = sanitize($_POST['note_type'] ?? 'General');
    $note_content = sanitize($_POST['note_content'] ?? '');

    if (!$shift_id) {
        echo json_encode(['success' => false, 'message' => 'An active shift is required to document clinical notes.']);
        exit;
    }

    if (!$patient_id || empty($note_content)) {
        echo json_encode(['success' => false, 'message' => 'Patient selection and clinical content are required.']);
        exit;
    }

    // Database-level enforcement: Prevent additions if shift is closed/handed over
    $shift_check = dbRow($conn, "SELECT status, handover_submitted FROM nurse_shifts WHERE id=? AND nurse_id=?", "ii", [$shift_id, $nurse_pk]);
    if (!$shift_check || $shift_check['status'] !== 'Active' || $shift_check['handover_submitted'] == 1) {
        echo json_encode(['success' => false, 'message' => 'Security Error: This shift has been closed or handed over. Further clinical notes are strictly locked.']);
        exit;
    }

    $note_id = 'NTE-' . strtoupper(uniqid());

    $stmt = mysqli_prepare($conn, "
        INSERT INTO nursing_notes (note_id, nurse_id, patient_id, shift_id, note_type, note_content, is_locked) 
        VALUES (?, ?, ?, ?, ?, ?, 0)
    ");
    // is_locked defaults to 0. It gets locked at end of shift.
    mysqli_stmt_bind_param($stmt, "siisss", $note_id, $nurse_pk, $patient_id, $shift_id, $note_type, $note_content);

    if (mysqli_stmt_execute($stmt)) {
        secureLogNurse($conn, $nurse_pk, "Added $note_type note for Patient PK $patient_id", "notes");
        echo json_encode(['success' => true, 'message' => 'Nursing note documented successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);
