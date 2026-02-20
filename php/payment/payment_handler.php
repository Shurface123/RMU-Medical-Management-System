<?php
header('Content-Type: application/json');

require_once '../db_conn.php';

// Check table
$tbl = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
if (!$tbl || mysqli_num_rows($tbl) === 0) {
    echo json_encode(['success' => false, 'message' => 'Payments table not configured. Run the setup SQL first.']);
    exit;
}

$action = $_POST['action'] ?? 'add_payment';

if ($action === 'add_payment') {
    $patient_name = mysqli_real_escape_string($conn, trim($_POST['patient_name'] ?? ''));
    $amount       = (float)($_POST['amount'] ?? 0);
    $method       = mysqli_real_escape_string($conn, $_POST['method'] ?? 'Cash');
    $status       = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Pending');
    $notes        = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));

    if (!$patient_name || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Patient name and a valid amount are required.']);
        exit;
    }

    $allowed_methods = ['Cash', 'GhIPSS', 'Mobile Money', 'Card'];
    $allowed_statuses = ['Paid', 'Pending', 'Overdue'];
    if (!in_array($method, $allowed_methods))  $method = 'Cash';
    if (!in_array($status, $allowed_statuses)) $status = 'Pending';

    // Generate receipt ID
    $receipt_id = 'RCV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

    $paid_at_sql = $status === 'Paid' ? ', paid_at=NOW()' : '';
    $ok = mysqli_query($conn,
        "INSERT INTO payments (receipt_id, patient_id, amount, method, status, notes $paid_at_sql)
         VALUES ('$receipt_id', NULL, $amount, '$method', '$status', '$notes'"
        . ($status === 'Paid' ? ', NOW()' : '') . ")"
    );

    if ($ok) {
        echo json_encode(['success' => true, 'receipt_id' => $receipt_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'update_status') {
    $id         = (int)($_POST['id'] ?? 0);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status'] ?? '');
    $allowed    = ['Paid', 'Pending', 'Overdue', 'Refunded'];
    if ($id > 0 && in_array($new_status, $allowed)) {
        $paid_clause = $new_status === 'Paid' ? ', paid_at=NOW()' : '';
        mysqli_query($conn, "UPDATE payments SET status='$new_status'$paid_clause WHERE id=$id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
