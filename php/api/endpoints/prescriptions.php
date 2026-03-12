<?php
// ================================================================
// API ENDPOINT: /api/prescriptions
// Handles prescription listing per user/role
// ================================================================

/**
 * Handle prescriptions requests.
 *
 * @param string   $method    HTTP method
 * @param int      $userId    Authenticated user ID
 * @param string   $userRole  Role of the authenticated user
 * @param int|null $rxId      Optional prescription ID from URL path
 */
function handlePrescriptions(string $method, int $userId, string $userRole, ?int $rxId = null): void {
    global $conn;

    if (strtoupper($method) !== 'GET') {
        ApiResponse::error('Method not allowed', 405);
        return;
    }

    $limit  = min((int)($_GET['limit'] ?? 50), 100);
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    if ($userRole === 'patient') {
        $patientRow = $conn->query("SELECT id FROM patients WHERE user_id = $userId LIMIT 1")->fetch_assoc();
        $patientId  = (int)($patientRow['id'] ?? 0);
        $where = $rxId
            ? "pr.patient_id = ? AND pr.id = ?"
            : "pr.patient_id = ?";
        $types  = $rxId ? 'ii' : 'i';
        $params = $rxId ? [$patientId, $rxId] : [$patientId];

    } elseif ($userRole === 'doctor') {
        $doctorRow = $conn->query("SELECT id FROM doctors WHERE user_id = $userId LIMIT 1")->fetch_assoc();
        $doctorId  = (int)($doctorRow['id'] ?? 0);
        $where  = $rxId ? "pr.doctor_id = ? AND pr.id = ?" : "pr.doctor_id = ?";
        $types  = $rxId ? 'ii' : 'i';
        $params = $rxId ? [$doctorId, $rxId] : [$doctorId];

    } elseif (in_array($userRole, ['admin', 'pharmacist'])) {
        $where  = $rxId ? "pr.id = ?" : "1=1";
        $types  = $rxId ? 'i' : '';
        $params = $rxId ? [$rxId] : [];

    } else {
        ApiResponse::error('Access denied', 403);
        return;
    }

    // Append LIMIT/OFFSET
    $types  .= 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare(
        "SELECT pr.id, pr.patient_id, pr.doctor_id, pr.medication_name,
                pr.dosage, pr.frequency, pr.duration_days, pr.quantity,
                pr.status, pr.prescribed_date, pr.notes,
                up.name AS patient_name, ud.name AS doctor_name
         FROM prescriptions pr
         JOIN patients p  ON pr.patient_id = p.id
         JOIN users    up ON p.user_id = up.id
         JOIN doctors  d  ON pr.doctor_id = d.id
         JOIN users    ud ON d.user_id = ud.id
         WHERE $where
         ORDER BY pr.prescribed_date DESC
         LIMIT ? OFFSET ?"
    );

    if (!$stmt) {
        ApiResponse::error('DB error', 500);
        return;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $prescriptions = [];
    while ($row = $result->fetch_assoc()) {
        $prescriptions[] = $row;
    }

    ApiResponse::success(['prescriptions' => $prescriptions]);
}
