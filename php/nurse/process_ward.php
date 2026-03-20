<?php
// ============================================================
// PROCESS WARD AND BEDS (AJAX Endpoint)
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

// Get nurse PK
$nurse_pk = dbVal($conn, "SELECT id FROM nurses WHERE user_id=$nurse_id LIMIT 1", "i", []);

if ($action === 'request_transfer') {
    $patient_id      = validateInt($_POST['patient_id'] ?? 0);
    $from_bed_id     = validateInt($_POST['from_bed_id'] ?? 0);
    $to_bed_id       = validateInt($_POST['to_bed_id'] ?? 0);
    $from_ward       = sanitize($_POST['from_ward'] ?? '');
    $transfer_reason = sanitize($_POST['transfer_reason'] ?? '');

    if (!$patient_id || !$to_bed_id || empty($transfer_reason)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields for transfer']);
        exit;
    }

    // Get Destination Ward name
    $to_ward = dbVal($conn, "SELECT ward FROM beds WHERE id=?", "i", [$to_bed_id]);
    if (!$to_ward) {
        echo json_encode(['success' => false, 'message' => 'Invalid destination bed']);
        exit;
    }

    // Check if bed is still available
    $bed_status = dbVal($conn, "SELECT status FROM beds WHERE id=?", "i", [$to_bed_id]);
    if ($bed_status !== 'Available') {
        echo json_encode(['success' => false, 'message' => 'Destination bed is no longer available.']);
        exit;
    }

    $transfer_id = 'TRF-' . strtoupper(uniqid());

    $stmt = mysqli_prepare($conn, "
        INSERT INTO bed_transfers (transfer_id, patient_id, nurse_id, from_bed_id, to_bed_id, from_ward, to_ward, transfer_reason, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Requested')
    ");
    mysqli_stmt_bind_param($stmt, "siiiisss", $transfer_id, $patient_id, $nurse_pk, $from_bed_id, $to_bed_id, $from_ward, $to_ward, $transfer_reason);

    if (mysqli_stmt_execute($stmt)) {
        secureLogNurse($conn, $nurse_pk, "Requested bed transfer for Patient PK $patient_id to $to_ward", "ward");
        
        // Notify Admins/Doctors about transfer request
        dbExecute($conn, 
            "INSERT INTO notifications (user_id, message, type, related_module, created_at) 
             SELECT id, ?, 'Ward Transfer Request', 'bed_transfers', NOW() FROM users WHERE user_role IN ('admin','doctor')",
            "s", ["A bed transfer request ($transfer_id) has been initiated from $from_ward to $to_ward."]
        );

        echo json_encode(['success' => true, 'message' => 'Transfer request submitted successfully and is pending approval.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;

} elseif ($action === 'log_isolation') {
    $patient_id      = validateInt($_POST['patient_id'] ?? 0);
    $isolation_type  = sanitize($_POST['isolation_type'] ?? '');
    $reason          = sanitize($_POST['reason'] ?? '');
    
    // JSON encode precautions array
    $precautions_arr = $_POST['precautions'] ?? [];
    $precautions_json = json_encode(is_array($precautions_arr) ? array_map('sanitize', $precautions_arr) : []);

    if (!$patient_id || empty($isolation_type) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Missing isolation details.']);
        exit;
    }

    $record_id = 'ISO-' . strtoupper(uniqid());
    $start_date = date('Y-m-d');

    $stmt = mysqli_prepare($conn, "
        INSERT INTO isolation_records (record_id, patient_id, nurse_id, isolation_type, reason, start_date, precautions, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')
    ");
    mysqli_stmt_bind_param($stmt, "siissss", $record_id, $patient_id, $nurse_pk, $isolation_type, $reason, $start_date, $precautions_json);

    if (mysqli_stmt_execute($stmt)) {
        secureLogNurse($conn, $nurse_pk, "Initiated $isolation_type isolation for Patient PK $patient_id", "ward");
        
        echo json_encode(['success' => true, 'message' => "$isolation_type Isolation activated successfully."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);
