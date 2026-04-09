<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$admin_id = (int)$_SESSION['user_id'];

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'No action specified.']);
    exit();
}

switch ($action) {
    case 'approve_budget':
        $budget_id = (int)($_POST['budget_id'] ?? 0);
        if (!$budget_id) die(json_encode(['success' => false, 'message' => 'Invalid ID.']));
        
        $bq = mysqli_query($conn, "SELECT * FROM budget_allocations WHERE allocation_id = $budget_id AND status = 'Draft'");
        $budget = mysqli_fetch_assoc($bq);
        if (!$budget) die(json_encode(['success' => false, 'message' => 'Budget not found or already processed.']));

        mysqli_begin_transaction($conn);
        try {
            $sql = "UPDATE budget_allocations SET status = 'Active', remaining_amount = allocated_amount, approved_by = ?, approved_at = NOW() WHERE allocation_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $admin_id, $budget_id);
            mysqli_stmt_execute($stmt);

            // Audit
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $desc = "Approved Budget for {$budget['department']} - {$budget['fiscal_year']}";
            $old_v = json_encode(['status' => 'Draft']);
            $new_v = json_encode(['status' => 'Active']);
            mysqli_query($conn, "INSERT INTO finance_audit_trail (user_id, action, module, record_id, old_values, new_values, description, ip_address) 
                                 VALUES ($admin_id, 'Approve Budget', 'Budgets', $budget_id, '$old_v', '$new_v', '$desc', '$ip')");

            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Budget authorized.']);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Sys Error: ' . $e->getMessage()]);
        }
        break;

    case 'reject_budget':
        $budget_id = (int)($_POST['budget_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Rejected by Admin');
        if (!$budget_id) die(json_encode(['success' => false, 'message' => 'Invalid ID.']));
        
        $bq = mysqli_query($conn, "SELECT * FROM budget_allocations WHERE allocation_id = $budget_id AND status = 'Draft'");
        $budget = mysqli_fetch_assoc($bq);
        if (!$budget) die(json_encode(['success' => false, 'message' => 'Budget not found or already processed.']));

        mysqli_begin_transaction($conn);
        try {
            // Safe concat for notes
            $new_notes = $budget['notes'] . "\n\n--- REJECTED: " . $reason;
            
            $sql = "UPDATE budget_allocations SET status = 'Rejected', notes = ?, approved_by = ?, approved_at = NOW() WHERE allocation_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sii", $new_notes, $admin_id, $budget_id);
            mysqli_stmt_execute($stmt);

            // Audit
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $desc = "Rejected Budget for {$budget['department']}";
            $old_v = json_encode(['status' => 'Draft']);
            $new_v = json_encode(['status' => 'Rejected', 'notes' => $reason]);
            mysqli_query($conn, "INSERT INTO finance_audit_trail (user_id, action, module, record_id, old_values, new_values, description, ip_address) 
                                 VALUES ($admin_id, 'Reject Budget', 'Budgets', $budget_id, '$old_v', '$new_v', '$desc', '$ip')");

            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Budget rejected.']);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Sys Error: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
