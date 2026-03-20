<?php
// ============================================================
// PROCESS TASKS & HANDOVERS (AJAX Endpoint)
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

if ($action === 'complete_task') {
    $task_id = validateInt($_POST['task_id'] ?? 0);
    
    if (!$task_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid Task ID']);
        exit;
    }

    // Get task details before updating
    $task_row = dbRow($conn, "SELECT assigned_by_id, task_title, patient_id FROM nurse_tasks WHERE id=? AND nurse_id=?", "ii", [$task_id, $nurse_pk]);

    $stmt = mysqli_prepare($conn, "
        UPDATE nurse_tasks 
        SET status = 'Completed', completed_at = NOW() 
        WHERE id = ? AND nurse_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $task_id, $nurse_pk);

    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        secureLogNurse($conn, $nurse_pk, "Completed clinical task ID $task_id", "tasks");
        
        // Notify the Assignee
        if ($task_row && $task_row['assigned_by_id']) {
            $msg = "Task Completed: '{$task_row['task_title']}'";
            dbExecute($conn, 
                "INSERT INTO notifications (user_id, message, type, related_module, related_id, created_at) VALUES (?, ?, 'Task Completed', 'nurse_tasks', ?, NOW())",
                "isi", [$task_row['assigned_by_id'], $msg, $task_id]
            );
        }

        echo json_encode(['success' => true, 'message' => 'Task marked as completed.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Task could not be updated. Either it does not exist or you do not have permission.']);
    }
    exit;

} elseif ($action === 'submit_handover') {
    $shift_id          = validateInt($_POST['shift_id'] ?? 0);
    $incoming_nurse_id = validateInt($_POST['incoming_nurse_id'] ?? 0);
    $summary           = sanitize($_POST['summary'] ?? '');
    $critical_notes    = sanitize($_POST['critical_patients_notes'] ?? '');

    if (!$shift_id) {
        echo json_encode(['success' => false, 'message' => 'No active shift found to handover.']);
        exit;
    }
    if (empty($summary) || empty($critical_notes)) {
        echo json_encode(['success' => false, 'message' => 'Handover summaries are legally required fields.']);
        exit;
    }

    // Verify shift belongs to nurse and is active
    $shift_check = dbRow($conn, "SELECT status, handover_submitted, ward_assigned FROM nurse_shifts WHERE id=? AND nurse_id=?", "ii", [$shift_id, $nurse_pk]);
    if (!$shift_check || $shift_check['status'] !== 'Active') {
        echo json_encode(['success' => false, 'message' => 'Cannot submit handover for an inactive or invalid shift.']);
        exit;
    }
    if ($shift_check['handover_submitted']) {
        echo json_encode(['success' => false, 'message' => 'Handover has already been submitted for this shift.']);
        exit;
    }

    $handover_id = 'HND-SHF-' . strtoupper(uniqid());
    $incoming_nurse_val = $incoming_nurse_id > 0 ? $incoming_nurse_id : null;

    mysqli_begin_transaction($conn);
    try {
        // 1. Insert Handover Record
        $stmt1 = mysqli_prepare($conn, "
            INSERT INTO shift_handover (handover_id, outgoing_nurse_id, incoming_nurse_id, shift_id, handover_time, summary, critical_patients_notes)
            VALUES (?, ?, ?, ?, NOW(), ?, ?)
        ");
        mysqli_stmt_bind_param($stmt1, "siiiss", $handover_id, $nurse_pk, $incoming_nurse_val, $shift_id, $summary, $critical_notes);
        mysqli_stmt_execute($stmt1);

        // 2. Lock Nursing Notes for this Shift
        dbExecute($conn, "UPDATE nursing_notes SET is_locked = 1, locked_at = NOW() WHERE shift_id = ? AND nurse_id = ?", "ii", [$shift_id, $nurse_pk]);

        // 3. Mark Shift as Handed Over
        dbExecute($conn, "UPDATE nurse_shifts SET handover_submitted = 1 WHERE id = ?", "i", [$shift_id]);

        mysqli_commit($conn);

        secureLogNurse($conn, $nurse_pk, "Submitted clinical handover for shift $shift_id", "handover");
        
        // Notify Incoming Nurse
        if ($incoming_nurse_id > 0) {
            $inc_user_id = dbVal($conn, "SELECT user_id FROM nurses WHERE id=?", "i", [$incoming_nurse_id]);
            if ($inc_user_id) {
                dbExecute($conn, 
                    "INSERT INTO notifications (user_id, message, type, related_module, related_id, created_at) VALUES (?, ?, 'Shift Handover', 'shift_handover', ?, NOW())",
                    "isi", [$inc_user_id, "You have a new shift handover waiting for your review.", $shift_id]
                );
            }
        }

        echo json_encode(['success' => true, 'message' => 'Shift handover successfully submitted and signed off. Notes locked.']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Database error occurred during handover submission.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);
