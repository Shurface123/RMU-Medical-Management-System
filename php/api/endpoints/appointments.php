<?php
/**
 * APPOINTMENTS API ENDPOINTS
 */

function handleAppointments($method, $userId, $userRole, $appointmentId) {
    global $conn;
    
    switch ($method) {
        case 'GET':
            if ($appointmentId) {
                getAppointment($conn, $userId, $userRole, $appointmentId);
            } else {
                getAppointments($conn, $userId, $userRole);
            }
            break;
            
        case 'POST':
            createAppointment($conn, $userId);
            break;
            
        case 'PUT':
            updateAppointment($conn, $userId, $userRole, $appointmentId);
            break;
            
        case 'DELETE':
            cancelAppointment($conn, $userId, $userRole, $appointmentId);
            break;
            
        default:
            ApiResponse::error('Method not allowed', 405);
    }
}

function getAppointments($conn, $userId, $userRole) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
    $offset = ($page - 1) * $perPage;
    
    // Build query based on role
    if ($userRole === 'patient') {
        $query = "SELECT a.*, d.name as doctor_name 
                  FROM appointments a
                  LEFT JOIN users d ON a.doctor_id = d.id
                  WHERE a.patient_id = ?
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $userId, $perPage, $offset);
    } elseif ($userRole === 'doctor') {
        $query = "SELECT a.*, p.name as patient_name 
                  FROM appointments a
                  LEFT JOIN users p ON a.patient_id = p.id
                  WHERE a.doctor_id = ?
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $userId, $perPage, $offset);
    } else {
        ApiResponse::error('Unauthorized', 403);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = [
            'id' => $row['id'],
            'date' => $row['appointment_date'],
            'time' => $row['appointment_time'],
            'status' => $row['status'],
            'reason' => $row['reason'] ?? '',
            'doctor_name' => $row['doctor_name'] ?? null,
            'patient_name' => $row['patient_name'] ?? null
        ];
    }
    
    // Get total count
    $countQuery = $userRole === 'patient' ? 
        "SELECT COUNT(*) as total FROM appointments WHERE patient_id = ?" :
        "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ?";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param("i", $userId);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    ApiResponse::paginated($appointments, $total, $page, $perPage);
}

function getAppointment($conn, $userId, $userRole, $appointmentId) {
    $query = "SELECT a.*, d.name as doctor_name, p.name as patient_name 
              FROM appointments a
              LEFT JOIN users d ON a.doctor_id = d.id
              LEFT JOIN users p ON a.patient_id = p.id
              WHERE a.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        ApiResponse::error('Appointment not found', 404);
    }
    
    $appointment = $result->fetch_assoc();
    
    // Check authorization
    if ($userRole === 'patient' && $appointment['patient_id'] != $userId) {
        ApiResponse::error('Unauthorized', 403);
    }
    if ($userRole === 'doctor' && $appointment['doctor_id'] != $userId) {
        ApiResponse::error('Unauthorized', 403);
    }
    
    ApiResponse::success([
        'id' => $appointment['id'],
        'date' => $appointment['appointment_date'],
        'time' => $appointment['appointment_time'],
        'status' => $appointment['status'],
        'reason' => $appointment['reason'] ?? '',
        'doctor_name' => $appointment['doctor_name'],
        'patient_name' => $appointment['patient_name'],
        'notes' => $appointment['notes'] ?? ''
    ]);
}

function createAppointment($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $doctorId = $input['doctor_id'] ?? null;
    $date = $input['date'] ?? '';
    $time = $input['time'] ?? '';
    $reason = $input['reason'] ?? '';
    
    if (!$doctorId || !$date || !$time) {
        ApiResponse::error('Doctor, date, and time are required', 400);
    }
    
    // Check if doctor exists
    $doctorCheck = $conn->prepare("SELECT id FROM users WHERE id = ? AND user_role = 'doctor'");
    $doctorCheck->bind_param("i", $doctorId);
    $doctorCheck->execute();
    if ($doctorCheck->get_result()->num_rows === 0) {
        ApiResponse::error('Invalid doctor ID', 400);
    }
    
    // Create appointment
    $status = 'Scheduled';
    $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iissss", $userId, $doctorId, $date, $time, $reason, $status);
    
    if ($stmt->execute()) {
        ApiResponse::success([
            'appointment_id' => $stmt->insert_id,
            'status' => $status
        ], 'Appointment created successfully', 201);
    } else {
        ApiResponse::error('Failed to create appointment', 500);
    }
}

function updateAppointment($conn, $userId, $userRole, $appointmentId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Verify ownership
    $query = "SELECT * FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
    
    if (!$appointment) {
        ApiResponse::error('Appointment not found', 404);
    }
    
    if ($userRole === 'patient' && $appointment['patient_id'] != $userId) {
        ApiResponse::error('Unauthorized', 403);
    }
    
    // Update fields
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($input['date'])) {
        $updates[] = "appointment_date = ?";
        $params[] = $input['date'];
        $types .= 's';
    }
    if (isset($input['time'])) {
        $updates[] = "appointment_time = ?";
        $params[] = $input['time'];
        $types .= 's';
    }
    if (isset($input['status']) && $userRole === 'doctor') {
        $updates[] = "status = ?";
        $params[] = $input['status'];
        $types .= 's';
    }
    
    if (empty($updates)) {
        ApiResponse::error('No fields to update', 400);
    }
    
    $params[] = $appointmentId;
    $types .= 'i';
    
    $updateQuery = "UPDATE appointments SET " . implode(', ', $updates) . " WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param($types, ...$params);
    
    if ($updateStmt->execute()) {
        ApiResponse::success([], 'Appointment updated successfully');
    } else {
        ApiResponse::error('Failed to update appointment', 500);
    }
}

function cancelAppointment($conn, $userId, $userRole, $appointmentId) {
    // Verify ownership
    $query = "SELECT * FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
    
    if (!$appointment) {
        ApiResponse::error('Appointment not found', 404);
    }
    
    if ($userRole === 'patient' && $appointment['patient_id'] != $userId) {
        ApiResponse::error('Unauthorized', 403);
    }
    
    // Update status to Cancelled
    $updateQuery = "UPDATE appointments SET status = 'Cancelled' WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("i", $appointmentId);
    
    if ($updateStmt->execute()) {
        ApiResponse::success([], 'Appointment cancelled successfully');
    } else {
        ApiResponse::error('Failed to cancel appointment', 500);
    }
}
