<?php
// ================================================================
// API ENDPOINT: /api/doctors
// Public endpoint — lists available doctors for booking
// ================================================================

/**
 * Handle doctor listing requests.
 *
 * @param string $method  HTTP method (only GET is supported)
 */
function handleDoctors(string $method): void {
    global $conn;

    if (strtoupper($method) !== 'GET') {
        ApiResponse::error('Method not allowed', 405);
        return;
    }

    $limit  = min((int)($_GET['limit'] ?? 50), 100);
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    // Optional filters
    $specialization = isset($_GET['specialization']) ? trim($_GET['specialization']) : null;

    $where  = "d.is_available = 1 AND u.is_active = 1";
    $types  = '';
    $params = [];

    if ($specialization) {
        $where  .= " AND d.specialization LIKE ?";
        $types  .= 's';
        $params[] = '%' . $specialization . '%';
    }

    $types  .= 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare(
        "SELECT d.id, u.name, d.specialization, d.consultation_fee,
                d.years_of_experience, d.is_available, u.profile_image
         FROM doctors d
         JOIN users u ON d.user_id = u.id
         WHERE $where
         ORDER BY u.name ASC
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

    $doctors = [];
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }

    ApiResponse::success(['doctors' => $doctors, 'count' => count($doctors)]);
}
