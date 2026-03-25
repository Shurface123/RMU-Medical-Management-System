<?php
/**
 * HR & Staff Management API Endpoint
 * Handles performance KPIs, shifts, and leaves.
 */

function handleHR($method, $userId, $userRole, $metricType) {
    global $conn;

    if ($userRole !== 'admin') {
        ApiResponse::error('Unauthorized', 403);
    }

    try {
        switch ($metricType) {
            case 'performance':
                getStaffPerformanceData();
                break;
            case 'duty_roster':
                getDutyRoster();
                break;
            case 'shifts':
                if ($method === 'GET') getShifts();
                else if ($method === 'POST') saveShift();
                break;
            case 'leaves':
                if ($method === 'GET') getLeaves();
                else if ($method === 'POST') updateLeaveStatus();
                break;
            default:
                ApiResponse::error('Invalid HR metric', 400);
        }
    } catch (Exception $e) {
        ApiResponse::error('HR API Error: ' . $e->getMessage(), 500);
    }
}

function getStaffPerformanceData() {
    global $conn;
    $sql = "
        SELECT s.id, u.name, u.user_role as role, s.department,
               (SELECT COUNT(*) FROM staff_tasks t WHERE t.assigned_to = s.id) as total_tasks,
               (SELECT COUNT(*) FROM staff_tasks t WHERE t.assigned_to = s.id AND t.status = 'completed') as completed_tasks,
               (SELECT kpi_score FROM staff_performance p WHERE p.staff_id = s.id ORDER BY p.created_at DESC LIMIT 1) as latest_kpi
        FROM staff s
        JOIN users u ON s.user_id = u.id
        WHERE u.is_active = 1 AND u.user_role NOT IN ('admin','patient')
    ";
    $res = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $row['completion_rate'] = $row['total_tasks'] > 0 ? round(($row['completed_tasks'] / $row['total_tasks']) * 100) : 100;
        $data[] = $row;
    }
    ApiResponse::success($data);
}

function getDutyRoster() {
    global $conn;
    // Simple logic: users who have a shift starting before now and ending after now
    $sql = "
        SELECT u.name, u.user_role as role, s.start_time, s.end_time, d.name as department
        FROM staff_shifts s
        JOIN staff st ON s.staff_id = st.id
        JOIN users u ON st.user_id = u.id
        JOIN departments d ON st.department_id = d.id
        WHERE CURTIME() BETWEEN s.start_time AND s.end_time
        AND s.shift_date = CURDATE()
    ";
    $res = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    ApiResponse::success($data);
}

function getShifts() {
    global $conn;
    $sql = "
        SELECT ss.*, u.name as staff_name, d.name as department
        FROM staff_shifts ss
        JOIN staff s ON ss.staff_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN departments d ON s.department_id = d.id
        WHERE ss.shift_date >= CURDATE()
        ORDER BY ss.shift_date ASC, ss.start_time ASC
    ";
    $res = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    ApiResponse::success($data);
}

function getLeaves() {
    global $conn;
    $sql = "
        SELECT l.*, u.name as staff_name, d.name as department
        FROM leave_requests l
        JOIN staff s ON l.staff_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN departments d ON s.department_id = d.id
        ORDER BY l.created_at DESC
    ";
    $res = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    ApiResponse::success($data);
}

function updateLeaveStatus() {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)$data['id'];
    $status = mysqli_real_escape_string($conn, $data['status']);
    $reason = mysqli_real_escape_string($conn, $data['rejection_reason'] ?? '');
    
    $sql = "UPDATE leave_requests SET status = ?, rejection_reason = ?, handled_by = ?, handled_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $adminId = $_SESSION['user_id'];
    $stmt->bind_param('ssii', $status, $reason, $adminId, $id);
    
    if ($stmt->execute()) ApiResponse::success(['message' => 'Leave request updated']);
    else ApiResponse::error('DB Update Failed');
}

function saveShift() {
    // Implement shift saving logic
}
