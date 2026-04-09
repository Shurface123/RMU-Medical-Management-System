<?php
/**
 * Paystack Webhook Handler
 * URL: /php/finance/paystack_webhook.php
 * Add this URL to your Paystack dashboard under Webhooks
 *
 * SECURITY: Validates HMAC-SHA512 signature before processing.
 * Responds HTTP 200 IMMEDIATELY, then processes asynchronously.
 */

// ── MUST respond 200 immediately before any processing ──────
http_response_code(200);
echo 'OK';
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
else { ob_end_flush(); flush(); }

// ── Init ─────────────────────────────────────────────────────
require_once __DIR__.'/../db_conn.php';
require_once __DIR__.'/paystack_helper.php';
require_once __DIR__.'/../classes/FinanceNotifier.php';

$fn = new FinanceNotifier($conn);

$raw_body    = file_get_contents('php://input');
$header_sig  = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if (!$raw_body || !$header_sig) {
    error_log('[Paystack Webhook] Missing body or signature header.');
    exit;
}

// ── Fetch webhook secret ──────────────────────────────────────
$cfg = _paystackConfig($conn);
$webhook_secret = $cfg['paystack_webhook_secret'] ?? '';

if (!$webhook_secret) {
    error_log('[Paystack Webhook] Webhook secret not configured.');
    exit;
}

// ── Validate signature ────────────────────────────────────────
$sig_valid = validateWebhookSignature($raw_body, $header_sig, $webhook_secret);
$payload   = json_decode($raw_body, true);

if (!$payload) {
    error_log('[Paystack Webhook] Invalid JSON payload.');
    exit;
}

$event     = $payload['event'] ?? '';
$data      = $payload['data'] ?? [];
$reference = $data['reference'] ?? '';
$raw_esc   = mysqli_real_escape_string($conn, $raw_body);

// ── Log raw webhook to paystack_transactions ──────────────────
$existing = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT transaction_id, payment_id FROM paystack_transactions WHERE paystack_reference='".mysqli_real_escape_string($conn,$reference)."' LIMIT 1"));

if ($existing) {
    mysqli_query($conn,
        "UPDATE paystack_transactions SET
            paystack_raw_response='$raw_esc',
            webhook_received_at=NOW(),
            webhook_event='".mysqli_real_escape_string($conn,$event)."',
            webhook_signature_valid=".($sig_valid?1:0).",
            updated_at=NOW()
         WHERE paystack_reference='".mysqli_real_escape_string($conn,$reference)."'");
} else {
    $amount_ghs = round(($data['amount'] ?? 0) / 100, 2);
    $channel    = mysqli_real_escape_string($conn, $data['channel'] ?? '');
    $gw_resp    = mysqli_real_escape_string($conn, $data['gateway_response'] ?? '');
    $paid_at    = !empty($data['paid_at']) ? "'".mysqli_real_escape_string($conn,$data['paid_at'])."'" : 'NULL';
    $status_raw = mysqli_real_escape_string($conn, ucfirst($data['status'] ?? 'pending'));
    mysqli_query($conn,
        "INSERT INTO paystack_transactions(paystack_reference,amount_ghs,currency,channel,gateway_response,status,paid_at,webhook_received_at,webhook_event,webhook_signature_valid,paystack_raw_response,created_at)
         VALUES('".mysqli_real_escape_string($conn,$reference)."',$amount_ghs,'GHS','$channel','$gw_resp','$status_raw',$paid_at,NOW(),'".mysqli_real_escape_string($conn,$event)."',".($sig_valid?1:0).",'$raw_esc',NOW())");
}

if (!$sig_valid) {
    error_log('[Paystack Webhook] INVALID SIGNATURE for reference: '.$reference);
    exit; // Do not process further if signature is invalid
}

// ── DISPATCH EVENTS ──────────────────────────────────────────

switch ($event) {

    // ── charge.success ───────────────────────────────────────
    case 'charge.success':
        $amount_ghs = round(($data['amount'] ?? 0) / 100, 2);
        $email      = $data['customer']['email'] ?? '';
        $channel    = $data['channel'] ?? '';
        $paid_at    = $data['paid_at'] ?? date('Y-m-d H:i:s');

        // Find matching initialized transaction
        $txn = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT pt.*, p.invoice_id, p.patient_id FROM paystack_transactions pt
             LEFT JOIN payments p ON pt.payment_id=p.payment_id
             WHERE pt.paystack_reference='".mysqli_real_escape_string($conn,$reference)."' LIMIT 1"));

        if ($txn && $txn['payment_id']) {
            // Update existing payment record
            $pay_id = (int)$txn['payment_id'];
            mysqli_query($conn,"UPDATE payments SET status='Completed',payment_date='".mysqli_real_escape_string($conn,$paid_at)."',updated_at=NOW() WHERE payment_id=$pay_id");

            // Update invoice
            $pay = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM payments WHERE payment_id=$pay_id LIMIT 1"));
            if ($pay) {
                $inv = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM billing_invoices WHERE invoice_id={$pay['invoice_id']} LIMIT 1"));
                if ($inv) {
                    $new_paid  = round($inv['paid_amount'] + $amount_ghs, 2);
                    $new_bal   = round($inv['balance_due'] - $amount_ghs, 2);
                    $new_status= $new_bal <= 0 ? 'Paid' : 'Partially Paid';
                    mysqli_query($conn,"UPDATE billing_invoices SET paid_amount=$new_paid,balance_due=$new_bal,status='$new_status',updated_at=NOW() WHERE invoice_id={$inv['invoice_id']}");

                    // Notify patient
                    $patrow = mysqli_fetch_assoc(mysqli_query($conn,"SELECT p.user_id FROM patients p WHERE p.id={$inv['patient_id']} LIMIT 1"));
                    if ($patrow) {
                        $GLOBALS['fn']->notifyPatient((int)$patrow['user_id'], 'Payment Confirmed via Paystack', 'Payment of GHS '.number_format($amount_ghs,2).' received via Paystack (Ref: '.$reference.'). Invoice status: '.$new_status, 'normal');
                    }
                }
            }
        }

        // Update ps transaction status
        mysqli_query($conn,"UPDATE paystack_transactions SET status='Success',paid_at='".mysqli_real_escape_string($conn,$paid_at)."',updated_at=NOW() WHERE paystack_reference='".mysqli_real_escape_string($conn,$reference)."'");

        $GLOBALS['fn']->notifyFinance('Payment Received', 'Paystack Payment Success', 'GHS '.number_format($amount_ghs,2).' received via '.ucfirst($channel).' (Ref: '.$reference.')', 'normal', 'paystack', 0);

        // Audit
        mysqli_query($conn,"INSERT INTO finance_audit_trail(actor_user_id,action_type,table_affected,record_id,description,created_at) VALUES(0,'WEBHOOK_CHARGE_SUCCESS','paystack_transactions',0,'charge.success for $reference GHS $amount_ghs',NOW())");
        break;

    // ── charge.failed ────────────────────────────────────────
    case 'charge.failed':
        mysqli_query($conn,"UPDATE paystack_transactions SET status='Failed',updated_at=NOW() WHERE paystack_reference='".mysqli_real_escape_string($conn,$reference)."'");
        // Update linked payment if exists
        if ($existing && $existing['payment_id']) {
            mysqli_query($conn,"UPDATE payments SET status='Failed',updated_at=NOW() WHERE payment_id={$existing['payment_id']}");
            $pay = mysqli_fetch_assoc(mysqli_query($conn,"SELECT patient_id FROM payments WHERE payment_id={$existing['payment_id']} LIMIT 1"));
            if ($pay) {
                $patrow = mysqli_fetch_assoc(mysqli_query($conn,"SELECT p.user_id FROM patients p WHERE p.id={$pay['patient_id']} LIMIT 1"));
                if ($patrow) $GLOBALS['fn']->notifyPatient((int)$patrow['user_id'], 'Payment Failed', 'Your Paystack payment (Ref: '.$reference.') failed. Please try again or contact finance.', 'high');
            }
        }
        $GLOBALS['fn']->notifyFinance('System', 'Paystack Payment Failed', 'Payment Ref '.$reference.' failed.', 'high', 'paystack', 0);
        break;

    // ── refund.processed ────────────────────────────────────
    case 'refund.processed':
        $ps_refund_id = $data['id'] ?? '';
        $ref_amount   = round(($data['amount'] ?? 0) / 100, 2);
        mysqli_query($conn,"UPDATE refunds SET status='Completed',paystack_refund_reference='".mysqli_real_escape_string($conn,(string)$ps_refund_id)."',updated_at=NOW() WHERE paystack_refund_reference='".mysqli_real_escape_string($conn,(string)$ps_refund_id)."' OR (payment_id IN (SELECT payment_id FROM paystack_transactions WHERE paystack_reference='".mysqli_real_escape_string($conn,$reference)."'))");
        $GLOBALS['fn']->notifyFinance('Refund Processed', 'Refund Processed', 'Paystack refund of GHS '.number_format($ref_amount,2).' processed (Ref: '.$ps_refund_id.')', 'normal', 'paystack', 0);
        break;

    // ── transfer.success ─────────────────────────────────────
    case 'transfer.success':
        $GLOBALS['fn']->notifyFinance('System', 'Paystack Transfer Successful', 'Transfer completed. Ref: '.$reference, 'normal', 'paystack', 0);
        break;
}

exit;
