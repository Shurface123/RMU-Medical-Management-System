<?php
/**
 * Paystack Initialize — called from patient dashboard "Pay Now"
 * POST /php/finance/paystack_initialize.php
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__.'/../db_conn.php';
require_once __DIR__.'/paystack_helper.php';

if(!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }

$body  = json_decode(file_get_contents('php://input'), true) ?: [];
$inv_id = (int)($body['invoice_id'] ?? 0);
if(!$inv_id) { echo json_encode(['success'=>false,'message'=>'Invoice ID required']); exit; }

$inv = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT bi.*,u.email,u.name AS patient_name,pt.id AS pat_id
     FROM billing_invoices bi
     JOIN patients pt ON bi.patient_id=pt.id
     JOIN users u ON pt.user_id=u.id
     WHERE bi.invoice_id=$inv_id AND bi.status NOT IN('Paid','Cancelled','Void') LIMIT 1"));

if(!$inv){ echo json_encode(['success'=>false,'message'=>'Invoice not found or already paid']); exit; }

$amount_pesewas = (int)round($inv['balance_due'] * 100);
$reference = 'RMU-'.date('YmdHis').'-'.strtoupper(substr(uniqid(),0,6));
$base_url  = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off'?'https':'http').'://'.$_SERVER['HTTP_HOST'].'/RMU-Medical-Management-System';
$callback  = $base_url.'/php/finance/paystack_callback.php';

$res = initializeTransaction(
    $inv['email'],
    $inv['balance_due'],
    $reference,
    ['invoice_id'=>$inv_id,'invoice_number'=>$inv['invoice_number'],'patient_name'=>$inv['patient_name'],'custom_fields'=>[['display_name'=>'Invoice Number','variable_name'=>'invoice_number','value'=>$inv['invoice_number']]]],
    $callback
);

if(!$res['status']){ echo json_encode(['success'=>false,'message'=>$res['message']??'Paystack initialization failed']); exit; }

$auth_url = $res['data']['authorization_url'] ?? '';
$access_code = $res['data']['access_code'] ?? '';

// Create a pending payment record
$receipt_pending = '';
mysqli_query($conn,
    "INSERT INTO payments(invoice_id,patient_id,payment_reference,amount,payment_method,channel,payment_date,status,paystack_reference,created_at)
     VALUES($inv_id,{$inv['pat_id']},'$reference',{$inv['balance_due']},'Paystack','Online',NOW(),'Pending','$reference',NOW())");
$pay_id = (int)mysqli_insert_id($conn);

// Create paystack_transactions record
mysqli_query($conn,
    "INSERT INTO paystack_transactions(payment_id,paystack_reference,amount_ghs,currency,status,created_at)
     VALUES($pay_id,'$reference',{$inv['balance_due']},'GHS','Initialized',NOW())");

echo json_encode(['success'=>true,'authorization_url'=>$auth_url,'reference'=>$reference,'access_code'=>$access_code]);
exit;
