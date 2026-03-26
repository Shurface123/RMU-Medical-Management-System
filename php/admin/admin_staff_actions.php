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
        $type = $_POST['type'] ?? 'staff';
        if (!$staff_id) die(json_encode(['success' => false, 'message' => 'Invalid ID.']));
        
        $table = ($type === 'nurse') ? 'nurses' : (($type === 'lab_technician') ? 'lab_technicians' : (($type === 'doctor') ? 'doctors' : (($type === 'pharmacist' || $type === 'pharmacist_profile') ? 'pharmacist_profile' : 'staff')));
        $sql = "UPDATE $table SET approval_status = 'approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $admin_id, $staff_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // ── Sync with users table and Send Email ──
            $get_uid = mysqli_query($conn, "SELECT u.id, u.email, u.name, u.user_role FROM $table t JOIN users u ON t.user_id = u.id WHERE t.id = $staff_id LIMIT 1");
            if ($u_row = mysqli_fetch_assoc($get_uid)) {
                $uid = (int)$u_row['id'];
                mysqli_query($conn, "UPDATE users SET is_active = 1, account_status = 'active', is_verified = 1 WHERE id = $uid");

                // Log to Audit Trail
                $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $audit_id = 'URA-' . uniqid();
                mysqli_query($conn, "INSERT INTO user_registration_audit (audit_id, user_id, action, performed_by, ip_address, notes) VALUES ('$audit_id', $uid, 'approved', '$admin_id', '$ip', 'Approved via Admin Dashboard')");

                // Send Welcome/Activation Email
                require_once '../includes/reg_mailer.php';
                @reg_send_welcome_email($conn, $u_row['email'], $u_row['name'], $u_row['user_role'], false);
            }

            if ($type === 'staff') {
                mysqli_query($conn, "INSERT INTO staff_approval_log (staff_id, admin_user_id, action, actioned_at) VALUES ($staff_id, $admin_id, 'approved', NOW())");
            }
            echo json_encode(['success' => true, 'message' => ucfirst(str_replace('_',' ',$type)) . ' member approved and account activated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'reject_staff':
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        $reason   = trim($_POST['reason'] ?? 'Rejected by administration.');
        $type     = $_POST['type'] ?? 'staff';
        if (!$staff_id) die(json_encode(['success' => false, 'message' => 'Invalid ID.']));
        
        $table = ($type === 'nurse') ? 'nurses' : (($type === 'lab_technician') ? 'lab_technicians' : (($type === 'doctor') ? 'doctors' : (($type === 'pharmacist' || $type === 'pharmacist_profile') ? 'pharmacist_profile' : 'staff')));
        $sql = "UPDATE $table SET approval_status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isi", $admin_id, $reason, $staff_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // ── Sync with users table and Send Email ──
            $get_uid = mysqli_query($conn, "SELECT u.id, u.email, u.name, u.user_role FROM $table t JOIN users u ON t.user_id = u.id WHERE t.id = $staff_id LIMIT 1");
            if ($u_row = mysqli_fetch_assoc($get_uid)) {
                $uid = (int)$u_row['id'];
                mysqli_query($conn, "UPDATE users SET is_active = 0, account_status = 'inactive' WHERE id = $uid");

                // Log to Audit Trail
                $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $audit_id = 'URA-' . uniqid();
                $safe_reason = mysqli_real_escape_string($conn, $reason);
                mysqli_query($conn, "INSERT INTO user_registration_audit (audit_id, user_id, action, performed_by, ip_address, notes) VALUES ('$audit_id', $uid, 'rejected', '$admin_id', '$ip', '$safe_reason')");

                // Send Rejection Email
                require_once '../includes/reg_mailer.php';
                @reg_send_rejection_email($conn, $u_row['email'], $u_row['name'], $u_row['user_role'], $reason);
            }

            if ($type === 'staff') {
                $safe_reason = mysqli_real_escape_string($conn, $reason);
                mysqli_query($conn, "INSERT INTO staff_approval_log (staff_id, admin_user_id, action, reason, actioned_at) VALUES ($staff_id, $admin_id, 'rejected', '$safe_reason', NOW())");
            }
            echo json_encode(['success' => true, 'message' => ucfirst(str_replace('_',' ',$type)) . ' member rejected and account disabled.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'assign_task':
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            die(json_encode(['success' => false, 'message' => 'Invalid Security Token. Refresh and try again.']));
        }
        $staff_id    = (int)($_POST['staff_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $priority    = trim($_POST['priority'] ?? 'medium');
        $due_date    = trim($_POST['due_date'] ?? '');
        $due_time    = trim($_POST['due_time'] ?? '');

        if (!$staff_id || !$title || !$category || !$due_date || !$due_time) {
            die(json_encode(['success' => false, 'message' => 'Missing required task fields.']));
        }
        
        $sql = "INSERT INTO staff_tasks (assigned_to, assigned_by, task_title, task_description, task_category, priority, due_date, due_time, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissssss", $staff_id, $admin_id, $title, $description, $category, $priority, $due_date, $due_time);
        
        if (mysqli_stmt_execute($stmt)) {
            // Also add notification for the assigned staff member
            $task_id = mysqli_insert_id($conn);
            $msg = mysqli_real_escape_string($conn, "New task assigned: " . $title . " (Due: $due_date $due_time)");
            mysqli_query($conn, "INSERT INTO staff_notifications (staff_id, message, type, related_module, related_record_id) 
                                 VALUES ($staff_id, '$msg', 'task', 'tasks', $task_id)");
            
            echo json_encode(['success' => true, 'message' => 'Task assigned successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'check_shift_conflict':
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die(json_encode(['has_conflict' => false]));
        $staff_id   = (int)($_POST['staff_id'] ?? 0);
        $shift_date = trim($_POST['shift_date'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time   = trim($_POST['end_time'] ?? '');
        
        if (!$staff_id || !$shift_date || !$start_time || !$end_time) die(json_encode(['has_conflict' => false]));

        // Overlap condition: (new_start < exist_end) AND (new_end > exist_start)
        $sql = "SELECT shift_id FROM staff_shifts 
                WHERE staff_id = ? AND shift_date = ? 
                AND start_time < ? AND end_time > ? 
                AND status NOT IN ('missed', 'swapped') LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isss", $staff_id, $shift_date, $end_time, $start_time);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        echo json_encode(['has_conflict' => mysqli_stmt_num_rows($stmt) > 0]);
        mysqli_stmt_close($stmt);
        break;

    case 'add_shift':
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            die(json_encode(['success' => false, 'message' => 'Invalid Security Token. Refresh and try again.']));
        }
        $staff_id   = (int)($_POST['staff_id'] ?? 0);
        $shift_date = trim($_POST['shift_date'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time   = trim($_POST['end_time'] ?? '');
        $type       = trim($_POST['shift_type'] ?? 'regular');
        
        $location   = trim($_POST['location_ward_assigned'] ?? '');
        $status     = trim($_POST['status'] ?? 'scheduled');
        $notes      = trim($_POST['notes'] ?? '');
        
        $is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == '1';
        $recur_pat    = trim($_POST['recurrence_pattern'] ?? '');
        $recur_end    = trim($_POST['recurrence_end_date'] ?? '');
        $override     = isset($_POST['conflict_override']) && $_POST['conflict_override'] == '1';

        if (!$staff_id || !$shift_date || !$start_time || !$end_time || !$location) {
            die(json_encode(['success' => false, 'message' => 'Missing required shift fields.']));
        }
        
        // 1. Server-side conflict check (unless overridden explicitly)
        if (!$override) {
            $c_sql = "SELECT shift_id FROM staff_shifts WHERE staff_id = ? AND shift_date = ? AND start_time < ? AND end_time > ? AND status NOT IN ('missed', 'swapped') LIMIT 1";
            $c_stmt = mysqli_prepare($conn, $c_sql);
            mysqli_stmt_bind_param($c_stmt, "isss", $staff_id, $shift_date, $end_time, $start_time);
            mysqli_stmt_execute($c_stmt);
            mysqli_stmt_store_result($c_stmt);
            $has_conflict = mysqli_stmt_num_rows($c_stmt) > 0;
            mysqli_stmt_close($c_stmt);
            
            if ($has_conflict) {
                die(json_encode(['success' => false, 'message' => 'Conflict detected. Client override required.']));
            }
        }

        // 2. Generate dates to insert
        $dates_to_insert = [$shift_date];
        if ($is_recurring && $recur_pat && $recur_end) {
            $current = strtotime($shift_date);
            $end_tz = strtotime($recur_end);
            $step = ($recur_pat === 'weekly') ? "+1 week" : "+1 day";
            
            $limit = 0; // Max 90 days protection
            while ($current < $end_tz && $limit < 90) {
                $current = strtotime($step, $current);
                if ($current <= $end_tz) {
                    $dates_to_insert[] = date('Y-m-d', $current);
                }
                $limit++;
            }
        }

        // 3. Perform Transaction
        mysqli_begin_transaction($conn);
        $success_count = 0;
        
        $sql = "INSERT INTO staff_shifts (staff_id, shift_type, shift_date, start_time, end_time, location_ward_assigned, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        
        foreach ($dates_to_insert as $d) {
            mysqli_stmt_bind_param($stmt, "isssssss", $staff_id, $type, $d, $start_time, $end_time, $location, $status, $notes);
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
            }
        }
        
        if ($success_count === count($dates_to_insert)) {
            mysqli_commit($conn);
            
            // Notification
            $msg = mysqli_real_escape_string($conn, "New shift assigned. Location: $location.");
            if ($is_recurring) $msg .= " (Recurring)";
            mysqli_query($conn, "INSERT INTO staff_notifications (staff_id, message, type, related_module) 
                                 VALUES ($staff_id, '$msg', 'shift', 'shifts')");
            
            echo json_encode(['success' => true, 'message' => 'Shift(s) assigned successfully.']);
        } else {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Database error preventing shift creation.']);
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
