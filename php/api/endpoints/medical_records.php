<?php
// ================================================================
// API ENDPOINT: /api/medical-records
// Handles fetching medical records (read-only for patients / doctors)
// ================================================================

/**
 * Handle medical records requests.
 *
 * @param string   $method    HTTP method
 * @param int      $userId    Authenticated user ID
 * @param string   $userRole  Role of the authenticated user
 * @param int|null $recordId  Optional record ID from URL path
 */
function handleMedicalRecords(string $method, int $userId, string $userRole, ?int $recordId = null): void {
    global $conn;

    // Only GET is supported for medical records via the public API
    if (strtoupper($method) !== 'GET') {
        ApiResponse::error('Method not allowed', 405);
        return;
    }

    // Determine the patient_id filter based on role
    if ($userRole === 'patient') {
        // Patient can only see their own records
        $patientRow = $conn->query("SELECT id FROM patients WHERE user_id = $userId LIMIT 1")->fetch_assoc();
        $patientId  = (int)($patientRow['id'] ?? 0);
        if (!$patientId) {
            ApiResponse::success(['records' => []]);
            return;
        }
        $whereClause = "mr.patient_id = ?";
        $bindTypes   = 'i';
        $bindParams  = [$patientId];
    } elseif (in_array($userRole, ['doctor', 'admin', 'lab_technician', 'nurse'])) {
        if ($recordId) {
            $whereClause = "mr.id = ?";
            $bindTypes   = 'i';
            $bindParams  = [$recordId];
        } else {
            $whereClause = "1=1";
            $bindTypes   = '';
            $bindParams  = [];
        }
    } else {
        ApiResponse::error('Access denied', 403);
        return;
    }

    $stmt = $conn->prepare(
        "SELECT mr.id, mr.patient_id, mr.record_type, mr.description,
                mr.created_at, u.name AS patient_name
         FROM medical_records mr
         JOIN patients p  ON mr.patient_id = p.id
         JOIN users    u  ON p.user_id = u.id
         WHERE $whereClause
         ORDER BY mr.created_at DESC
         LIMIT 100"
    );

    if (!$stmt) {
        ApiResponse::error('DB error', 500);
        return;
    }

    if (!empty($bindParams)) {
        $stmt->bind_param($bindTypes, ...$bindParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

    ApiResponse::success(['records' => $records]);
}
