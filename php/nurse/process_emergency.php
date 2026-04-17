<?php
// ============================================================
// PROCESS EMERGENCY ALERTS (AJAX Endpoint)
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

if ($action === 'trigger_alert') {
    $alert_type = sanitize($_POST['alert_type'] ?? '');
    $location   = sanitize($_POST['location'] ?? '');
    $patient_id = validateInt($_POST['patient_id'] ?? 0);
    $message    = sanitize($_POST['message'] ?? '');

    if (empty($alert_type) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Alert Type and Location are required.']);
        exit;
    }

    $severity = 'Medium';
    if (in_array($alert_type, ['Code Blue', 'Cardiac Arrest', 'Rapid Response'])) $severity = 'Critical';
    elseif (in_array($alert_type, ['Fall', 'Fire'])) $severity = 'High';

    $alert_id = 'ERT-' . strtoupper(uniqid());
    $patient_val = $patient_id > 0 ? $patient_id : null;

    $stmt = mysqli_prepare($conn, "
        INSERT INTO emergency_alerts (alert_id, nurse_id, patient_id, alert_type, severity, location, message, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')
    ");
    mysqli_stmt_bind_param($stmt, "siissss", $alert_id, $nurse_pk, $patient_val, $alert_type, $severity, $location, $message);

    if (mysqli_stmt_execute($stmt)) {
        secureLogNurse($conn, $nurse_pk, "Triggered a $severity severity $alert_type alert at $location", "emergency");

        // Broadcast to ALL doctors and admins
        $notif_msg = "🚨 $alert_type ALERT at $location. $message";
        dbExecute($conn, 
            "INSERT INTO notifications (user_id, message, type, related_module, created_at) 
             SELECT id, ?, 'Critical Alert', 'emergency_alerts', NOW() FROM users WHERE user_role IN ('admin','doctor')",
            "s", [$notif_msg]
        );

        echo json_encode(['success' => true, 'message' => "$alert_type Alert Broadcasted Successfully!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'System error recording the alert.']);
    }
    exit;

} elseif ($action === 'update_alert') {
    $alert_id = validateInt($_POST['alert_id'] ?? 0);
    $status   = sanitize($_POST['status'] ?? '');

    if (!$alert_id || !in_array($status, ['Responded', 'Resolved', 'False Alarm'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status update request.']);
        exit;
    }

    $query = "UPDATE emergency_alerts SET status = ?";
    $params = [$status];
    $types = "s";

    if ($status === 'Responded') {
        $query .= ", responded_at = NOW(), resolved_by = ? WHERE id = ?";
        $params[] = $nurse_id;
        $types .= "ii";
    } else {
        $query .= ", resolved_at = NOW(), resolved_by = ? WHERE id = ?";
        $params[] = $nurse_id; // Store user ID of the resolver
        $types .= "ii";
    }
    $params[] = $alert_id;
    $types .= "i";

    if (dbExecute($conn, $query, $types, $params)) {
        secureLogNurse($conn, $nurse_pk, "Updated Emergency Alert PK $alert_id to status: $status", "emergency");
        echo json_encode(['success' => true, 'message' => "Alert status marked as $status."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not update alert status.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Action']);
