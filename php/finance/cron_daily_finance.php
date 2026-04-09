<?php
/**
 * cron_daily_finance.php
 * Daily automated checks for:
 * 1. Overdue Invoices
 * 2. Budget Overruns
 */

require_once dirname(__DIR__).'/db_conn.php';
require_once dirname(__DIR__).'/classes/FinanceNotifier.php';

$fn = new FinanceNotifier($conn);

echo "Starting Daily Finance Checks...\n";

// 1. Check Overdue Invoices
$overdue_q = mysqli_query($conn, "SELECT bi.*, u.user_id as patient_uid 
                                  FROM billing_invoices bi 
                                  JOIN patients p ON bi.patient_id = p.id 
                                  JOIN users u ON p.user_id = u.id 
                                  WHERE bi.status = 'Pending' AND bi.due_date < CURDATE()");
$overdue_count = 0;
while ($inv = mysqli_fetch_assoc($overdue_q)) {
    $inv_id = $inv['invoice_id'];
    mysqli_query($conn, "UPDATE billing_invoices SET status = 'Overdue', updated_at = NOW() WHERE invoice_id = $inv_id");
    
    // Notify Finance
    $fn->notifyFinance('Invoice Overdue', 'Invoice Overdue', "Invoice {$inv['invoice_number']} is now overdue by system check.", 'high', 'billing', $inv_id);
    
    // Notify Patient
    if ($inv['patient_uid']) {
        $fn->notifyPatient($inv['patient_uid'], 'Payment Overdue', "Your invoice {$inv['invoice_number']} for GHS {$inv['balance_due']} is overdue.", 'high');
    }
    $overdue_count++;
}
echo "- Marked $overdue_count invoices as Overdue.\n";

// 2. Check Budget Overruns
$budget_q = mysqli_query($conn, "SELECT * FROM budget_allocations WHERE spent_amount > allocated_amount AND status != 'Exhausted'");
$budget_count = 0;
while ($bdg = mysqli_fetch_assoc($budget_q)) {
    $b_id = $bdg['allocation_id'];
    // Notify Finance Manager
    $fn->notifyFinance('Budget Alert', 'Budget Overrun Detected', "Department {$bdg['department']} has exceeded its allocated budget of GHS {$bdg['allocated_amount']}.", 'urgent', 'budgets', $b_id);
    
    // Notify Admin
    $fn->notifyAdmin('Budget Overrun Detected', "Department {$bdg['department']} has exceeded its allocated budget. Spent: GHS {$bdg['spent_amount']}", 'urgent');
    
    $budget_count++;
}
echo "- Detected $budget_count budget overruns.\n";

echo "Done.\n";
?>
