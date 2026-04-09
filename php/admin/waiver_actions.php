<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';
require_once '../classes/FinanceNotifier.php';

header('Content-Type: application/json');

$fn = new FinanceNotifier($conn);

$action = $_POST['action'] ?? '';
$admin_id = (int)$_SESSION['user_id'];

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'No action specified.']);
    exit();
}

switch ($action) {
    case 'approve_waiver':
        $waiver_id = (int)($_POST['waiver_id'] ?? 0);
        if (!$waiver_id) die(json_encode(['success' => false, 'message' => 'Invalid ID.']));
        
        // Fetch Waiver Info
        $wq = mysqli_query($conn, "SELECT * FROM payment_waivers WHERE waiver_id = $waiver_id AND status = 'Pending'");
        $waiver = mysqli_fetch_assoc($wq);
        if (!$waiver) die(json_encode(['success' => false, 'message' => 'Waiver not found or already processed.']));

        mysqli_begin_transaction($conn);
        try {
            // Update Waiver
            $sql = "UPDATE payment_waivers SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE waiver_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $admin_id, $waiver_id);
            mysqli_stmt_execute($stmt);

            // Update Invoice balance if an invoice is linked
            if (!empty($waiver['invoice_id'])) {
                $inv_id = (int)$waiver['invoice_id'];
                $waived_amount = (float)$waiver['waived_amount'];
                
                mysqli_query($conn, "UPDATE billing_invoices SET 
                    discount_amount = discount_amount + $waived_amount,
                    total_amount = GREATEST(0, total_amount - $waived_amount),
                    balance_due = GREATEST(0, balance_due - $waived_amount),
                    updated_at = NOW()
                    WHERE invoice_id = $inv_id");
            }

            // Audit Trail
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $desc = "Approved Waiver {$waiver['waiver_number']} for GHS {$waiver['waived_amount']}";
            $old_v = json_encode(['status' => 'Pending']);
            $new_v = json_encode(['status' => 'Approved']);
            mysqli_query($conn, "INSERT INTO finance_audit_trail (user_id, action, module, record_id, old_values, new_values, description, ip_address) 
                                 VALUES ($admin_id, 'Approve Waiver', 'Waivers', $waiver_id, '$old_v', '$new_v', '$desc', '$ip')");

            $fn->notifyFinance('Waiver Request', 'Waiver Approved by Admin', "Waiver {$waiver['waiver_number']} approved.", 'normal', 'waivers', $waiver_id);

            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Waiver approved successfully.']);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Sys Error: ' . $e->getMessage()]);
        }
        break;

    case 'reject_waiver':
        $waiver_id = (int)($_POST['waiver_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Rejected by Admin');
        if (!$waiver_id) die(json_encode(['success' => false, 'message' => 'Invalid ID.']));
        
        $wq = mysqli_query($conn, "SELECT * FROM payment_waivers WHERE waiver_id = $waiver_id AND status = 'Pending'");
        $waiver = mysqli_fetch_assoc($wq);
        if (!$waiver) die(json_encode(['success' => false, 'message' => 'Waiver not found or already processed.']));

        mysqli_begin_transaction($conn);
        try {
            $sql = "UPDATE payment_waivers SET status = 'Rejected', approved_by = ?, rejection_reason = ?, approved_at = NOW() WHERE waiver_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isi", $admin_id, $reason, $waiver_id);
            mysqli_stmt_execute($stmt);

            // Audit
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $desc = "Rejected Waiver {$waiver['waiver_number']}";
            $old_v = json_encode(['status' => 'Pending']);
            $new_v = json_encode(['status' => 'Rejected', 'reason' => $reason]);
            mysqli_query($conn, "INSERT INTO finance_audit_trail (user_id, action, module, record_id, old_values, new_values, description, ip_address) 
                                 VALUES ($admin_id, 'Reject Waiver', 'Waivers', $waiver_id, '$old_v', '$new_v', '$desc', '$ip')");

            $fn->notifyFinance('Waiver Request', 'Waiver Rejected by Admin', "Waiver {$waiver['waiver_number']} rejected. Reason: $reason", 'normal', 'waivers', $waiver_id);

            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Waiver rejected.']);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Sys Error: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
