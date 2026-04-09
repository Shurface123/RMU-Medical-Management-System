<?php
/**
 * Paystack Callback Handler
 * URL: /php/finance/paystack_callback.php
 * Paystack redirects patient here after payment attempt
 */
session_start();
require_once __DIR__.'/../db_conn.php';
require_once __DIR__.'/paystack_helper.php';

$reference = mysqli_real_escape_string($conn, trim($_GET['reference'] ?? $_GET['trxref'] ?? ''));

if (!$reference) {
    header('Location: /RMU-Medical-Management-System/php/dashboards/patient_dashboard.php?pay=error&msg=no_reference');
    exit;
}

// ── Verify with Paystack API ──────────────────────────────────
$res = verifyTransaction($reference);

if (!$res['status'] || ($res['data']['status'] ?? '') !== 'success') {
    $msg = urlencode($res['message'] ?? 'Payment verification failed');
    // Update transaction as failed
    mysqli_query($conn,"UPDATE paystack_transactions SET status='Failed',updated_at=NOW() WHERE paystack_reference='$reference'");
    mysqli_query($conn,"UPDATE payments SET status='Failed',updated_at=NOW() WHERE paystack_reference='$reference'");
    header("Location: /RMU-Medical-Management-System/php/dashboards/patient_dashboard.php?pay=failed&ref=$reference&msg=$msg");
    exit;
}

$d          = $res['data'];
$amount_ghs = round($d['amount'] / 100, 2);
$channel    = mysqli_real_escape_string($conn, $d['channel'] ?? '');
$paid_at    = mysqli_real_escape_string($conn, $d['paid_at'] ?? date('Y-m-d H:i:s'));
$gw_resp    = mysqli_real_escape_string($conn, $d['gateway_response'] ?? 'Approved');

// ── Find the payment record created at initialization ─────────
$pay = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.*, bi.invoice_id, bi.patient_id, bi.invoice_number, bi.balance_due, bi.paid_amount, pt.user_id AS p_uid
     FROM payments p
     JOIN billing_invoices bi ON p.invoice_id = bi.invoice_id
     JOIN patients pt ON bi.patient_id = pt.id
     WHERE p.paystack_reference = '$reference' LIMIT 1"));

if (!$pay) {
    // Payment record not found — create from verification data
    // Try to find via paystack_transactions
    $txn = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM paystack_transactions WHERE paystack_reference='$reference' LIMIT 1"));

    if (!$txn || !$txn['payment_id']) {
        header("Location: /RMU-Medical-Management-System/php/dashboards/patient_dashboard.php?pay=error&msg=record_not_found");
        exit;
    }
    $pay_id = (int)$txn['payment_id'];
    $pay = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.*, bi.patient_id, bi.invoice_number, bi.balance_due, bi.paid_amount, pt.user_id AS p_uid
         FROM payments p JOIN billing_invoices bi ON p.invoice_id=bi.invoice_id JOIN patients pt ON bi.patient_id=pt.id
         WHERE p.payment_id=$pay_id LIMIT 1"));
}

if (!$pay) {
    header("Location: /RMU-Medical-Management-System/php/dashboards/patient_dashboard.php?pay=error&msg=no_record");
    exit;
}

$pay_id   = (int)$pay['payment_id'];
$inv_id   = (int)$pay['invoice_id'];
$pat_id   = (int)$pay['patient_id'];
$p_uid    = (int)$pay['p_uid'];

// ── Receipt number ───────────────────────────────────────────
$receipt_num = 'RMU-RCT-'.date('Ymd').'-'.str_pad((int)mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*)+1 FROM payments WHERE DATE(created_at)=CURDATE()"))[0],4,'0',STR_PAD_LEFT);

// ── Update payment record ────────────────────────────────────
mysqli_query($conn,
    "UPDATE payments SET
        status='Completed',
        payment_date='$paid_at',
        channel='$channel',
        receipt_number='".mysqli_real_escape_string($conn,$receipt_num)."',
        updated_at=NOW()
     WHERE payment_id=$pay_id");

// ── Update paystack_transactions ──────────────────────────────
mysqli_query($conn,
    "UPDATE paystack_transactions SET
        status='Success',
        channel='$channel',
        gateway_response='$gw_resp',
        paid_at='$paid_at',
        updated_at=NOW()
     WHERE paystack_reference='$reference'");

// ── Update invoice ────────────────────────────────────────────
$inv = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM billing_invoices WHERE invoice_id=$inv_id LIMIT 1"));

if ($inv) {
    $new_paid   = round($inv['paid_amount'] + $amount_ghs, 2);
    $new_bal    = round($inv['balance_due'] - $amount_ghs, 2);
    $new_status = $new_bal <= 0 ? 'Paid' : 'Partially Paid';
    mysqli_query($conn,
        "UPDATE billing_invoices SET
            paid_amount=$new_paid,
            balance_due=$new_bal,
            status='$new_status',
            updated_at=NOW()
         WHERE invoice_id=$inv_id");
}

// ── Notify patient ────────────────────────────────────────────
if ($p_uid) {
    mysqli_query($conn,
        "INSERT INTO notifications(user_id,title,message,type,is_read,created_at)
         VALUES($p_uid,
            'Payment Successful',
            'Your payment of GHS ".number_format($amount_ghs,2)." for invoice {$pay['invoice_number']} was successful. Receipt: $receipt_num',
            'finance',0,NOW())");
}

// ── Notify finance staff ──────────────────────────────────────
$fin_q = mysqli_query($conn,"SELECT id FROM users WHERE user_role IN('finance_officer','finance_manager') AND is_active=1");
if($fin_q) while($fr=mysqli_fetch_assoc($fin_q))
    mysqli_query($conn,"INSERT INTO notifications(user_id,title,message,type,is_read,created_at)VALUES({$fr['id']},'Paystack Payment Received','GHS ".number_format($amount_ghs,2)." received via Paystack for invoice {$pay['invoice_number']}. Ref: $reference','finance',0,NOW())");

// ── Audit trail ──────────────────────────────────────────────
mysqli_query($conn,
    "INSERT INTO finance_audit_trail(actor_user_id,action_type,table_affected,record_id,description,created_at)
     VALUES($p_uid,'PAYSTACK_CALLBACK_SUCCESS','payments',$pay_id,'Payment $reference verified. GHS $amount_ghs. Receipt: $receipt_num',NOW())");

// ── Session flash ────────────────────────────────────────────
$_SESSION['pay_success'] = [
    'reference'   => $reference,
    'amount'      => $amount_ghs,
    'receipt'     => $receipt_num,
    'invoice'     => $pay['invoice_number'],
    'status'      => $inv['status'] ?? 'Paid',
];

// ── Redirect to patient dashboard with success ────────────────
header("Location: /RMU-Medical-Management-System/php/dashboards/patient_dashboard.php?pay=success&ref=".urlencode($reference)."&receipt=".urlencode($receipt_num));
exit;
