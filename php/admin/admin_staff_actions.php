<?php
/**
 * admin_staff_actions.php
 * Handles AJAX requests from the Admin Dashboard for Staff-related integrations
 * (approvals, tasks, shifts, leaves, etc.)
 */
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

header('Content-Type: application/json');

// Check POST payload
$action = $_POST['action'] ?? '';

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'No action specified.']);
    exit();
}

$admin_id = (int)$_SESSION['user_id'];

switch ($action) {
    case 'approve_staff':
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        if (!$staff_id) die(json_encode(['success' => false, 'message' => 'Invalid ID.']));
        
        $sql = "UPDATE staff SET approval_status = 'approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $admin_id, $staff_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log to audit body
            mysqli_query($conn, "INSERT INTO staff_approval_log (staff_id, admin_user_id, action, actioned_at) VALUES ($staff_id, $admin_id, 'approved', NOW())");
            echo json_encode(['success' => true, 'message' => 'Staff member approved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'reject_staff':
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        $reason   = trim($_POST['reason'] ?? 'Rejected by administration.');
        if (!$staff_id) die(json_encode(['success' => false, 'message' => 'Invalid ID.']));
        
        $sql = "UPDATE staff SET approval_status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isi", $admin_id, $reason, $staff_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $safe_reason = mysqli_real_escape_string($conn, $reason);
            mysqli_query($conn, "INSERT INTO staff_approval_log (staff_id, admin_user_id, action, reason, actioned_at) VALUES ($staff_id, $admin_id, 'rejected', '$safe_reason', NOW())");
            echo json_encode(['success' => true, 'message' => 'Staff member rejected.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'assign_task':
        $staff_id    = (int)($_POST['staff_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority    = trim($_POST['priority'] ?? 'medium');
        $deadline    = trim($_POST['deadline'] ?? '');

        if (!$staff_id || !$title || !$deadline) {
            die(json_encode(['success' => false, 'message' => 'Missing required task fields.']));
        }
        
        $sql = "INSERT INTO staff_tasks (assigned_to, assigned_by, title, description, priority, due_date, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissss", $staff_id, $admin_id, $title, $description, $priority, $deadline);
        
        if (mysqli_stmt_execute($stmt)) {
            // Also add notification for the assigned staff member
            $task_id = mysqli_insert_id($conn);
            $msg = "New task assigned: " . $title;
            mysqli_query($conn, "INSERT INTO staff_notifications (user_id, title, message, type, link, created_at) 
                                 VALUES ((SELECT user_id FROM staff WHERE id=$staff_id), 'New Task Assigned', '$msg', 'task', '#', NOW())");
            
            echo json_encode(['success' => true, 'message' => 'Task assigned successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'add_shift':
        $staff_id   = (int)($_POST['staff_id'] ?? 0);
        $shift_date = trim($_POST['shift_date'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time   = trim($_POST['end_time'] ?? '');
        $type       = trim($_POST['shift_type'] ?? 'regular');

        if (!$staff_id || !$shift_date || !$start_time || !$end_time) {
            die(json_encode(['success' => false, 'message' => 'Missing required shift fields.']));
        }

        $sql = "INSERT INTO staff_shifts (staff_id, shift_date, start_time, end_time, shift_type, assigned_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssi", $staff_id, $shift_date, $start_time, $end_time, $type, $admin_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Shift assigned successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'approve_leave':
        $leave_id = (int)($_POST['leave_id'] ?? 0);
        if (!$leave_id) die(json_encode(['success' => false, 'message' => 'Invalid Leave ID.']));
        
        $sql = "UPDATE staff_leave_requests SET status = 'approved', approved_by = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $admin_id, $leave_id);
        if (mysqli_stmt_execute($stmt)) echo json_encode(['success' => true, 'message' => 'Leave approved.']);
        else echo json_encode(['success' => false, 'message' => 'Database error']);
        mysqli_stmt_close($stmt);
        break;

    case 'reject_leave':
        $leave_id = (int)($_POST['leave_id'] ?? 0);
        $reason   = trim($_POST['reason'] ?? 'Rejected by administration');
        if (!$leave_id) die(json_encode(['success' => false, 'message' => 'Invalid Leave ID.']));
        
        $sql = "UPDATE staff_leave_requests SET status = 'rejected', approved_by = ?, rejection_reason = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isi", $admin_id, $reason, $leave_id);
        if (mysqli_stmt_execute($stmt)) echo json_encode(['success' => true, 'message' => 'Leave rejected.']);
        else echo json_encode(['success' => false, 'message' => 'Database error']);
        mysqli_stmt_close($stmt);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
?>
