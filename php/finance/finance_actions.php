<?php
/**
 * Finance Actions Handler — AJAX Endpoint
 * Phase 9 Security Hardened: Prepared Statements, CSRF, RBAC
 */
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__.'/finance_security.php';
require_once dirname(__DIR__).'/db_conn.php';
require_once dirname(__DIR__).'/classes/FinanceNotifier.php';

// ── CSRF VALIDATION ──────────────────────────────────────────
$headers = null;
if (function_exists('apache_request_headers')) { $headers = apache_request_headers(); }
else { $headers = $_SERVER; } // Fallback for some CGI

// Function to safely extract header regardless of case
function getHeader($headers, $key) {
    foreach ($headers as $k => $v) {
        if (strtolower($k) === strtolower($key) || strtolower($k) === strtolower('HTTP_' . str_replace('-','_',$key))) return $v;
    }
    return '';
}

$csrf_token = getHeader($headers, 'X-CSRF-Token') ?: ($_POST['csrf_token'] ?? '');
// Bypass CSRF strictly for debugging in development if absolutely necessary, but here we strictly enforce:
if (empty($csrf_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF validation failed.']);
    exit;
}

$fn = new FinanceNotifier($conn);

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$today     = date('Y-m-d');

// Read JSON or form body
$body = file_get_contents('php://input');
$data = json_decode($body, true) ?: $_POST;
$action = trim($data['action'] ?? '');

// ── Helpers ─────────────────────────────────────────────────
function ok($extra=[])  { echo json_encode(array_merge(['success'=>true], $extra)); exit; }
function fail($msg,$code=200) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg]); exit; }
function sanitize($v) { return htmlspecialchars(strip_tags(trim($v??'')), ENT_QUOTES, 'UTF-8'); }

function auditLog($conn, $uid, $action_type, $table, $rec_id, $desc){
    $stmt = $conn->prepare("INSERT INTO finance_audit_trail(actor_user_id,action_type,table_affected,record_id,description,created_at) VALUES(?,?,?,?,?,NOW())");
    $stmt->bind_param("issss", $uid, $action_type, $table, $rec_id, $desc);
    $stmt->execute();
    $stmt->close();
}

function nextInvoiceNumber($conn){
    $y=date('Ymd'); 
    $stmt = $conn->prepare("SELECT COUNT(*)+1 FROM billing_invoices WHERE DATE(created_at)=CURDATE()");
    $stmt->execute(); $res = $stmt->get_result(); $cnt = (int)$res->fetch_row()[0]; $stmt->close();
    return 'RMU-INV-'.$y.'-'.str_pad($cnt,4,'0',STR_PAD_LEFT);
}

function nextReceiptNumber($conn){
    $y=date('Ymd'); 
    $stmt = $conn->prepare("SELECT COUNT(*)+1 FROM payments WHERE DATE(created_at)=CURDATE()");
    $stmt->execute(); $res = $stmt->get_result(); $cnt = (int)$res->fetch_row()[0]; $stmt->close();
    return 'RMU-RCT-'.$y.'-'.str_pad($cnt,4,'0',STR_PAD_LEFT);
}

function nextRefundRef(){
    return 'RMU-RFD-'.date('Ymd').'-'.strtoupper(substr(uniqid(),0,6));
}

// ════════════════════════════════════════════════════════════
// MASTER ACTION SWITCH
// ════════════════════════════════════════════════════════════
try {
    switch($action):

    // ── PATIENT INFO ────────────────────────────────────────────
    case 'get_patient_info':
        $pid = (int)($data['patient_id']??0);
        $stmt = $conn->prepare("SELECT p.*, u.name, u.email FROM patients p JOIN users u ON p.user_id=u.id WHERE p.id=? LIMIT 1");
        $stmt->bind_param("i", $pid); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$row) fail('Patient not found.');

        $stmt2 = $conn->prepare("SELECT COALESCE(SUM(balance_due),0) FROM billing_invoices WHERE patient_id=? AND status NOT IN('Paid','Cancelled','Void','Written Off')");
        $stmt2->bind_param("i", $pid); $stmt2->execute();
        $outstanding = (float)$stmt2->get_result()->fetch_row()[0]; $stmt2->close();
        
        ok(['name'=>$row['name'],'patient_id'=>$row['patient_id'],'insurance'=>$row['insurance_provider']??'','email'=>$row['email'],'outstanding'=>$outstanding]);

    // ── CREATE / SAVE INVOICE ────────────────────────────────────
    case 'save_invoice_draft':
    case 'issue_invoice':
        $pat_id = (int)($data['patient_id']??0);
        if(!$pat_id) fail('Patient required.');
        $lines = $data['line_items']??[];
        if(empty($lines)) fail('No line items.');
        
        $inv_date = sanitize($data['invoice_date']??date('Y-m-d'));
        $due_date = sanitize($data['due_date']??date('Y-m-d',strtotime('+30 days')));
        $notes = sanitize($data['notes']??'');
        $status = ($action==='issue_invoice') ? 'Pending' : 'Draft';
        $inv_num = nextInvoiceNumber($conn);

        // Calculate totals
        $subtotal = $discount_total = $tax_total = 0;
        foreach($lines as $li){
            $qty = max(1,(float)($li['qty']??1)); $price = max(0,(float)($li['price']??0)); $disc = max(0,min(100,(float)($li['disc']??0)));
            $sub = $qty*$price; $da = $sub*($disc/100);
            $subtotal += $sub; $discount_total += $da;
        }
        $grand = $subtotal - $discount_total + $tax_total;

        $stmt = $conn->prepare("INSERT INTO billing_invoices(patient_id,invoice_number,invoice_date,due_date,subtotal,discount_total,tax_total,total_amount,paid_amount,balance_due,status,notes,generated_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
        $zero = 0;
        $stmt->bind_param("isssddddddssi", $pat_id, $inv_num, $inv_date, $due_date, $subtotal, $discount_total, $tax_total, $grand, $zero, $grand, $status, $notes, $user_id);
        if(!$stmt->execute()) fail('Failed to create invoice.');
        $inv_id = (int)$stmt->insert_id; $stmt->close();

        // Line items prepared statement
        $stmt_li = $conn->prepare("INSERT INTO invoice_line_items(invoice_id,fee_id,quantity,unit_price,discount_pct,discount_amount,tax_amount,line_total,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
        foreach($lines as $li){
            $fee_id = (int)($li['fee_id']??0); $qty = (float)($li['qty']??1); $price = (float)($li['price']??0); $disc = (float)($li['disc']??0);
            $sub_li = $qty*$price; $da_li = $sub_li*($disc/100); $total_li = $sub_li-$da_li;
            $fee_val = $fee_id > 0 ? $fee_id : null;
            $stmt_li->bind_param("iidddddd", $inv_id, $fee_val, $qty, $price, $disc, $da_li, $zero, $total_li);
            $stmt_li->execute();
        }
        $stmt_li->close();

        if($status==='Pending'){
            $stmt_notify = $conn->prepare("SELECT user_id FROM patients WHERE id=? LIMIT 1");
            $stmt_notify->bind_param("i", $pat_id); $stmt_notify->execute();
            $patrow = $stmt_notify->get_result()->fetch_assoc(); $stmt_notify->close();
            if($patrow) $fn->notifyPatient((int)$patrow['user_id'],'Invoice Issued','Invoice '. sanitize($inv_num) .' of GHS '.number_format($grand,2).' has been issued. Please log in to view and pay.');
        }
        auditLog($conn,$user_id,'INVOICE_CREATE','billing_invoices',$inv_id,"Created invoice $inv_num status=$status");
        ok(['invoice_id'=>$inv_id,'invoice_number'=>$inv_num,'status'=>$status]);

    // ── ISSUE INVOICE DIRECT ─────────────────────────────────────
    case 'issue_invoice_direct':
        $inv_id = (int)($data['invoice_id']??0);
        $stmt = $conn->prepare("SELECT * FROM billing_invoices WHERE invoice_id=? LIMIT 1");
        $stmt->bind_param("i", $inv_id); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$row) fail('Invoice not found.');

        $stmt = $conn->prepare("UPDATE billing_invoices SET status='Pending',updated_at=NOW() WHERE invoice_id=?");
        $stmt->bind_param("i", $inv_id); $stmt->execute(); $stmt->close();
        
        $stmt = $conn->prepare("SELECT user_id FROM patients WHERE id=? LIMIT 1");
        $stmt->bind_param("i", $row['patient_id']); $stmt->execute();
        $patrow = $stmt->get_result()->fetch_assoc(); $stmt->close();
        
        if($patrow) $fn->notifyPatient((int)$patrow['user_id'],'Invoice Issued','Invoice '.$row['invoice_number'].' has been issued.');
        auditLog($conn,$user_id,'INVOICE_ISSUE','billing_invoices',$inv_id,"Issued invoice {$row['invoice_number']}");
        ok();

    // ── RECORD MANUAL PAYMENT ────────────────────────────────────
    case 'record_manual_payment':
        $inv_id = (int)($data['invoice_id']??0);
        $amount = round((float)($data['amount']??0),2);
        if($amount<=0) fail('Invalid amount.');
        $method = sanitize($data['payment_method']??'Cash');
        $pay_date = sanitize($data['payment_date']??date('Y-m-d H:i:s'));
        $ref = sanitize($data['reference']??'');
        $notes_p = sanitize($data['notes']??'');
        $receipt = nextReceiptNumber($conn);

        $stmt = $conn->prepare("SELECT * FROM billing_invoices WHERE invoice_id=? LIMIT 1");
        $stmt->bind_param("i", $inv_id); $stmt->execute();
        $inv = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$inv) fail('Invoice not found.');
        
        $new_paid = round($inv['paid_amount']+$amount,2);
        $new_bal = round($inv['balance_due']-$amount,2);
        $new_status = $new_bal<=0?'Paid':($new_paid>0?'Partially Paid':'Pending');

        $pay_ref = 'RMU-'.date('YmdHis').'-'.strtoupper(substr(uniqid(),0,6));
        $ch = "Counter"; $st = "Completed";
        $stmt = $conn->prepare("INSERT INTO payments(invoice_id,patient_id,payment_reference,receipt_number,amount,payment_method,channel,payment_date,status,notes,processed_by,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())");
        $stmt->bind_param("iisssdssssi", $inv_id, $inv['patient_id'], $pay_ref, $receipt, $amount, $method, $ch, $pay_date, $st, $notes_p, $user_id);
        $stmt->execute();
        $pay_id = (int)$stmt->insert_id; $stmt->close();

        $stmt = $conn->prepare("UPDATE billing_invoices SET paid_amount=?,balance_due=?,status=?,updated_at=NOW() WHERE invoice_id=?");
        $stmt->bind_param("ddsi", $new_paid, $new_bal, $new_status, $inv_id);
        $stmt->execute(); $stmt->close();

        $stmt = $conn->prepare("SELECT user_id FROM patients WHERE id=? LIMIT 1");
        $stmt->bind_param("i", $inv['patient_id']); $stmt->execute();
        $patrow = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if($patrow) $fn->notifyPatient((int)$patrow['user_id'],'Payment Received','Payment of GHS '.number_format($amount,2).' received. Receipt: '.$receipt.'. Balance: GHS '.number_format(max(0,$new_bal),2));
        
        $fn->notifyFinance('Payment Received', "Payment Received ($method)", "Receipt $receipt for GHS $amount received for Invoice {$inv['invoice_number']}.", 'normal', 'billing', $pay_id);
        auditLog($conn,$user_id,'PAYMENT_RECORD','payments',$pay_id,"Recorded payment $receipt for invoice {$inv['invoice_number']} GHS $amount");
        ok(['payment_id'=>$pay_id,'receipt_number'=>$receipt,'new_status'=>$new_status]);

    // ── INVOICE DETAIL HTML ──────────────────────────────────────
    case 'get_invoice_detail':
        $inv_id = (int)($data['invoice_id']??0);
        $stmt = $conn->prepare("SELECT bi.*, u.name AS patient_name, u.email AS patient_email, pt.patient_id AS patient_ref, u2.name AS officer FROM billing_invoices bi JOIN patients pt ON bi.patient_id=pt.id JOIN users u ON pt.user_id=u.id LEFT JOIN users u2 ON bi.generated_by=u2.id WHERE bi.invoice_id=? LIMIT 1");
        $stmt->bind_param("i", $inv_id); $stmt->execute();
        $inv = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$inv) fail('Not found.');

        $lines = []; 
        $stmt = $conn->prepare("SELECT ili.*, fs.service_name FROM invoice_line_items ili LEFT JOIN fee_schedule fs ON ili.fee_id=fs.fee_id WHERE ili.invoice_id=?");
        $stmt->bind_param("i", $inv_id); $stmt->execute();
        $lq = $stmt->get_result(); while($r = $lq->fetch_assoc()) $lines[]=$r; $stmt->close();

        $pays = [];
        $stmt = $conn->prepare("SELECT * FROM payments WHERE invoice_id=? ORDER BY created_at");
        $stmt->bind_param("i", $inv_id); $stmt->execute();
        $pq = $stmt->get_result(); while($r = $pq->fetch_assoc()) $pays[]=$r; $stmt->close();

        $badge_map = ['Draft'=>'badge-draft','Pending'=>'badge-pending','Partially Paid'=>'badge-partial','Paid'=>'badge-paid','Overdue'=>'badge-overdue-fin','Cancelled'=>'badge-cancelled'];
        $badge = $badge_map[$inv['status']]??'badge-pending';
        ob_start(); ?>
        <div style="font-family:'Poppins',sans-serif;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
                <div><div style="font-size:2rem;font-weight:800;color:var(--role-accent);">RMU Medical Sickbay</div><div style="font-size:1.2rem;color:var(--text-muted);">Finance &amp; Revenue Department</div></div>
                <div style="text-align:right;"><div style="font-size:1.8rem;font-weight:700;"><?=htmlspecialchars($inv['invoice_number'])?></div><span class="badge-fin <?=$badge?>"><?=htmlspecialchars($inv['status'])?></span></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;padding:1.5rem;background:var(--surface-2);border-radius:10px;margin-bottom:2rem;">
                <div><div style="font-weight:700;margin-bottom:.5rem;">Bill To</div><div style="font-size:1.4rem;font-weight:600;"><?=htmlspecialchars($inv['patient_name'])?></div><div><?=htmlspecialchars($inv['patient_ref'])?></div><div><?=htmlspecialchars($inv['patient_email'])?></div></div>
                <div style="text-align:right;"><div><span style="color:var(--text-muted);">Invoice Date:</span> <strong><?=date('d M Y',strtotime($inv['invoice_date']))?></strong></div><?php if($inv['due_date']): ?><div><span style="color:var(--text-muted);">Due Date:</span> <strong style="color:<?=strtotime($inv['due_date'])<time()&&$inv['status']!=='Paid'?'var(--danger)':'inherit'?>"><?=date('d M Y',strtotime($inv['due_date']))?></strong></div><?php endif;?><div><span style="color:var(--text-muted);">Officer:</span> <?=htmlspecialchars($inv['officer']??'—')?></div></div>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:1.3rem;margin-bottom:2rem;">
                <thead><tr style="background:linear-gradient(135deg,#0d3b2e,#1a9e6e);color:#fff;"><th style="padding:1rem 1.5rem;text-align:left;">Service</th><th style="padding:1rem;text-align:center;">Qty</th><th style="padding:1rem;text-align:right;">Unit Price</th><th style="padding:1rem;text-align:right;">Discount</th><th style="padding:1rem;text-align:right;">Total</th></tr></thead>
                <tbody>
                <?php foreach($lines as $li): ?>
                <tr style="border-bottom:1px solid var(--border);"><td style="padding:1rem 1.5rem;"><?=htmlspecialchars($li['service_name']??'Service')?></td><td style="padding:1rem;text-align:center;"><?=intval($li['quantity'])?></td><td style="padding:1rem;text-align:right;">GHS <?=number_format($li['unit_price'],2)?></td><td style="padding:1rem;text-align:right;color:var(--success);"><?=$li['discount_pct']>0?$li['discount_pct'].'%':'—'?></td><td style="padding:1rem;text-align:right;font-weight:700;">GHS <?=number_format($li['line_total'],2)?></td></tr>
                <?php endforeach;?>
                </tbody>
            </table>
            <div style="display:flex;justify-content:flex-end;margin-bottom:2rem;"><div style="min-width:300px;"><div style="display:flex;justify-content:space-between;padding:.6rem 0;"><span style="color:var(--text-muted);">Subtotal</span><span>GHS <?=number_format($inv['subtotal'],2)?></span></div><?php if($inv['discount_total']>0): ?><div style="display:flex;justify-content:space-between;padding:.6rem 0;"><span style="color:var(--success);">Discount</span><span style="color:var(--success);">-GHS <?=number_format($inv['discount_total'],2)?></span></div><?php endif;?><?php if($inv['tax_total']>0): ?><div style="display:flex;justify-content:space-between;padding:.6rem 0;"><span>Tax</span><span>GHS <?=number_format($inv['tax_total'],2)?></span></div><?php endif;?><div style="display:flex;justify-content:space-between;padding:.8rem 0;font-size:2rem;font-weight:800;border-top:2px solid var(--border);margin-top:.4rem;"><span>Total</span><span style="color:var(--role-accent);">GHS <?=number_format($inv['total_amount'],2)?></span></div><div style="display:flex;justify-content:space-between;padding:.4rem 0;"><span style="color:var(--success);">Amount Paid</span><span style="color:var(--success);">GHS <?=number_format($inv['paid_amount'],2)?></span></div><div style="display:flex;justify-content:space-between;padding:.4rem 0;font-weight:700;"><span style="color:var(--danger);">Balance Due</span><span style="color:var(--danger);">GHS <?=number_format($inv['balance_due'],2)?></span></div></div></div>
            <?php if(!empty($pays)): ?>
            <div><div style="font-weight:700;font-size:1.5rem;margin-bottom:1rem;">Payment History</div>
            <?php foreach($pays as $p): ?><div style="display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid var(--border);font-size:1.3rem;"><span><?=date('d M Y, g:i A',strtotime($p['payment_date']))?> — <?=htmlspecialchars($p['payment_method'])?></span><span style="font-weight:700;color:var(--success);">+ GHS <?=number_format($p['amount'],2)?></span></div><?php endforeach;?>
            </div>
            <?php endif;?>
        </div>
        <?php $html=ob_get_clean(); ok(['html'=>$html]);

    // ── INSURANCE CLAIM ──────────────────────────────────────────
    case 'save_insurance_claim':
        $inv_id = (int)($data['invoice_id']??0);
        $stmt = $conn->prepare("SELECT * FROM billing_invoices WHERE invoice_id=? LIMIT 1");
        $stmt->bind_param("i", $inv_id); $stmt->execute();
        $inv = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$inv) fail('Invoice not found.');
        
        $status = in_array($data['status']??'',['Draft','Submitted']) ? sanitize($data['status']) : 'Draft';
        $provider = sanitize($data['insurance_provider']??'');
        $policy = sanitize($data['policy_number']??'');
        $amount = round((float)($data['claim_amount']??0),2);
        $insurer_ref = sanitize($data['insurer_reference']??'');
        $notes = sanitize($data['notes']??'');
        
        $stmt = $conn->prepare("SELECT COUNT(*)+1 FROM insurance_claims");
        $stmt->execute(); $cnt = (int)$stmt->get_result()->fetch_row()[0]; $stmt->close();
        $claim_num = 'RMU-CLM-'.date('Ymd').'-'.str_pad($cnt,4,'0',STR_PAD_LEFT);
        
        $submit_date = $status==='Submitted' ? date('Y-m-d') : null;
        
        $stmt = $conn->prepare("INSERT INTO insurance_claims(invoice_id,patient_id,claim_number,insurance_provider,policy_number,claim_amount,status,submission_date,insurer_reference,notes,claims_officer,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
        $stmt->bind_param("iissdsssssi", $inv_id, $inv['patient_id'], $claim_num, $provider, $policy, $amount, $status, $submit_date, $insurer_ref, $notes, $user_id);
        $stmt->execute();
        $claim_id = (int)$stmt->insert_id; $stmt->close();
        
        auditLog($conn,$user_id,'CLAIM_CREATE','insurance_claims',$claim_id,"Created claim $claim_num status=$status");
        ok(['claim_id'=>$claim_id,'claim_number'=>$claim_num]);

    case 'update_claim_status':
        $claim_id = (int)($data['claim_id']??0);
        $status = sanitize($data['status']??'');
        $approved = !empty($data['approved_amount']) ? round((float)$data['approved_amount'],2) : null;
        $copay = !empty($data['patient_copay']) ? round((float)$data['patient_copay'],2) : null;
        $resp_date = !empty($data['response_date']) ? sanitize($data['response_date']) : null;
        $notes = sanitize($data['notes']??'');
        $notes_append = $notes ? "\n[".date('d M Y')."] " . $notes : "";
        
        $stmt = $conn->prepare("UPDATE insurance_claims SET status=?, approved_amount=?, patient_copay=?, response_date=?, notes=CONCAT(IFNULL(notes,''), ?), updated_at=NOW() WHERE claim_id=?");
        $stmt->bind_param("sddssi", $status, $approved, $copay, $resp_date, $notes_append, $claim_id);
        $stmt->execute(); $stmt->close();
        
        auditLog($conn,$user_id,'CLAIM_UPDATE','insurance_claims',$claim_id,"Updated claim #$claim_id status=$status");
        $fn->notifyFinance('Insurance Update', "Insurance Claim Updated", "Claim #$claim_id status updated to $status.", 'normal', 'insurance', $claim_id);
        ok();

    // ── WAIVER ───────────────────────────────────────────────────
    case 'create_waiver':
        $inv_id = (int)($data['invoice_id']??0);
        $stmt = $conn->prepare("SELECT * FROM billing_invoices WHERE invoice_id=? LIMIT 1");
        $stmt->bind_param("i", $inv_id); $stmt->execute();
        $inv = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$inv) fail('Invoice not found.');
        
        $type = sanitize($data['waiver_type']??'Partial');
        $waived = round((float)($data['waived_amount']??0),2);
        $remaining = round($inv['balance_due']-$waived,2);
        $reason = sanitize($data['reason']??'');
        
        $stmt = $conn->prepare("SELECT COUNT(*)+1 FROM payment_waivers");
        $stmt->execute(); $cnt = (int)$stmt->get_result()->fetch_row()[0]; $stmt->close();
        $w_num = 'RMU-WVR-'.date('Ymd').'-'.str_pad($cnt,4,'0',STR_PAD_LEFT);
        
        $st = "Pending";
        $stmt = $conn->prepare("INSERT INTO payment_waivers(invoice_id,patient_id,waiver_number,waiver_type,original_amount,waived_amount,remaining_amount,reason,status,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
        $stmt->bind_param("iissdddssi", $inv_id, $inv['patient_id'], $w_num, $type, $inv['balance_due'], $waived, $remaining, $reason, $st, $user_id);
        $stmt->execute();
        $waiver_id = (int)$stmt->insert_id; $stmt->close();
        
        $fn->notifyFinance('Waiver Request', 'Waiver Approval Required', "New waiver request $w_num for GHS $waived awaiting approval.", 'high', 'waivers', $waiver_id);
        auditLog($conn,$user_id,'WAIVER_CREATE','payment_waivers',$waiver_id,"Created waiver $w_num GHS $waived");
        ok(['waiver_id'=>$waiver_id,'waiver_number'=>$w_num]);

    case 'approve_waiver':
        if($user_role!=='finance_manager'&&$user_role!=='admin') fail('Insufficient permissions.');
        $wid = (int)($data['waiver_id']??0);
        $stmt = $conn->prepare("SELECT * FROM payment_waivers WHERE waiver_id=? LIMIT 1");
        $stmt->bind_param("i", $wid); $stmt->execute();
        $w = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$w) fail('Waiver not found.');
        
        $stmt = $conn->prepare("UPDATE payment_waivers SET status='Approved',approved_by=?,approved_at=NOW(),updated_at=NOW() WHERE waiver_id=?");
        $stmt->bind_param("ii", $user_id, $wid); $stmt->execute(); $stmt->close();
        
        $new_bal = max(0,$w['remaining_amount']); $new_status = $new_bal<=0 ? 'Paid':'Partially Paid';
        $stmt = $conn->prepare("UPDATE billing_invoices SET balance_due=?,status=?,updated_at=NOW() WHERE invoice_id=?");
        $stmt->bind_param("dsi", $new_bal, $new_status, $w['invoice_id']); $stmt->execute(); $stmt->close();
        
        $stmt = $conn->prepare("SELECT p.user_id FROM patients p WHERE p.id=? LIMIT 1");
        $stmt->bind_param("i", $w['patient_id']); $stmt->execute();
        $patrow = $stmt->get_result()->fetch_assoc(); $stmt->close();
        
        if($patrow) $fn->notifyPatient((int)$patrow['user_id'],'Waiver Approved','Your waiver of GHS '.number_format($w['waived_amount'],2).' has been approved. New balance: GHS '.number_format($new_bal,2));
        $fn->notifyFinance('Waiver Request', 'Waiver Approved', "Waiver {$w['waiver_number']} has been approved.", 'normal', 'waivers', $wid);
        
        auditLog($conn,$user_id,'WAIVER_APPROVE','payment_waivers',$wid,"Approved waiver {$w['waiver_number']}");
        ok();

    case 'reject_waiver':
        if($user_role!=='finance_manager'&&$user_role!=='admin') fail('Insufficient permissions.');
        $wid = (int)($data['waiver_id']??0);
        $reason = sanitize($data['reason']??'');
        $stmt = $conn->prepare("UPDATE payment_waivers SET status='Rejected',approved_by=?,approved_at=NOW(),rejection_reason=?,updated_at=NOW() WHERE waiver_id=?");
        $stmt->bind_param("isi", $user_id, $reason, $wid);
        $stmt->execute(); $stmt->close();
        
        $fn->notifyFinance('Waiver Request', 'Waiver Rejected', "Waiver #$wid has been rejected.", 'normal', 'waivers', $wid);
        auditLog($conn,$user_id,'WAIVER_REJECT','payment_waivers',$wid,"Rejected waiver #$wid. Reason: $reason");
        ok();

    // ── REFUND ───────────────────────────────────────────────────
    case 'initiate_refund':
        $pay_id = (int)($data['payment_id']??0);
        $stmt = $conn->prepare("SELECT * FROM payments WHERE payment_id=? LIMIT 1");
        $stmt->bind_param("i", $pay_id); $stmt->execute();
        $pay = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$pay) fail('Payment not found.');
        
        $amount = round((float)($data['refund_amount']??0),2);
        $method = sanitize($data['refund_method']??'Cash');
        $reason = sanitize($data['reason']??'');
        $ref = nextRefundRef();
        
        $stmt = $conn->prepare("SELECT setting_value FROM finance_settings WHERE setting_key='refund_approval_threshold' LIMIT 1");
        $stmt->execute(); $res = $stmt->get_result(); $threshold = (float)($res->num_rows ? $res->fetch_row()[0] : 200); $stmt->close();
        
        $requires_approval = $amount >= $threshold;
        $status = $requires_approval ? 'Pending Approval' : 'Approved';
        $appver = $requires_approval ? null : $user_id;

        $stmt = $conn->prepare("INSERT INTO refunds(invoice_id,patient_id,payment_id,refund_reference,refund_amount,refund_method,reason,status,approved_by,approval_notes,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
        $blank = "";
        $stmt->bind_param("iiisdsssis", $pay['invoice_id'], $pay['patient_id'], $pay_id, $ref, $amount, $method, $reason, $status, $appver, $blank, $user_id);
        $stmt->execute();
        $refund_id = (int)$stmt->insert_id; $stmt->close();
        
        if($requires_approval){
            $fn->notifyFinance('Refund Request', 'Refund Approval Required', "Refund $ref of GHS $amount requires your approval.", 'high', 'refunds', $refund_id);
        }
        auditLog($conn,$user_id,'REFUND_INIT','refunds',$refund_id,"Initiated refund $ref GHS $amount method=$method");
        ok(['refund_id'=>$refund_id,'refund_reference'=>$ref,'requires_approval'=>$requires_approval]);

    case 'approve_refund':
        if($user_role!=='finance_manager'&&$user_role!=='admin') fail('Insufficient permissions.');
        $rid = (int)($data['refund_id']??0);
        $stmt = $conn->prepare("SELECT * FROM refunds WHERE refund_id=? LIMIT 1");
        $stmt->bind_param("i", $rid); $stmt->execute();
        $rf = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$rf) fail('Refund not found.');
        
        $ps_ref='';
        if($rf['refund_method']==='Paystack Refund'){
            $stmt = $conn->prepare("SELECT paystack_reference FROM payments WHERE payment_id=? LIMIT 1");
            $stmt->bind_param("i", $rf['payment_id']); $stmt->execute();
            $pay = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if(!empty($pay['paystack_reference'])){
                require_once __DIR__.'/../../finance/paystack_helper.php';
                $ps_res = createRefund($pay['paystack_reference'],(int)($rf['refund_amount']*100));
                if($ps_res['status']) $ps_ref=$ps_res['data']['id']??'';
            }
        }
        $stmt = $conn->prepare("UPDATE refunds SET status='Completed',approved_by=?,approved_at=NOW(),paystack_refund_reference=?,updated_at=NOW() WHERE refund_id=?");
        $stmt->bind_param("isi", $user_id, $ps_ref, $rid); $stmt->execute(); $stmt->close();
        
        $stmt = $conn->prepare("SELECT p.user_id FROM patients p WHERE p.id=? LIMIT 1");
        $stmt->bind_param("i", $rf['patient_id']); $stmt->execute();
        $patrow = $stmt->get_result()->fetch_assoc(); $stmt->close();
        
        if($patrow) $fn->notifyPatient((int)$patrow['user_id'],'Refund Processed','Your refund of GHS '.number_format($rf['refund_amount'],2).' has been processed via '.$rf['refund_method'].'.');
        $fn->notifyFinance('Refund Processed', 'Refund Approved & Processed', "Refund {$rf['refund_reference']} has been completed.", 'normal', 'refunds', $rid);
        auditLog($conn,$user_id,'REFUND_APPROVE','refunds',$rid,"Approved refund {$rf['refund_reference']}");
        ok(['paystack_refund_reference'=>$ps_ref]);

    case 'reject_refund':
        if($user_role!=='finance_manager'&&$user_role!=='admin') fail('Insufficient permissions.');
        $rid = (int)($data['refund_id']??0);
        $reason = sanitize($data['reason']??'');
        $stmt = $conn->prepare("UPDATE refunds SET status='Rejected',approved_by=?,approved_at=NOW(),approval_notes=?,updated_at=NOW() WHERE refund_id=?");
        $stmt->bind_param("isi", $user_id, $reason, $rid); $stmt->execute(); $stmt->close();
        auditLog($conn,$user_id,'REFUND_REJECT','refunds',$rid,"Rejected refund #$rid");
        ok();

    // ── BUDGET, RECONCILIATION, CONFIG ETC ──────────────────────
    case 'mark_reconciled':
        if($user_role!=='finance_manager'&&$user_role!=='admin') fail('Insufficient permissions.');
        $date = sanitize($data['date']??date('Y-m-d'));
        $stmt = $conn->prepare("SELECT report_id FROM daily_cash_reports WHERE report_date=? LIMIT 1");
        $stmt->bind_param("s", $date); $stmt->execute();
        $existing = $stmt->get_result()->num_rows > 0; $stmt->close();
        
        if($existing) {
            $stmt = $conn->prepare("UPDATE daily_cash_reports SET status='Reconciled',reconciled_at=NOW(),reconciled_by=?,updated_at=NOW() WHERE report_date=?");
            $stmt->bind_param("is", $user_id, $date); $stmt->execute(); $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO daily_cash_reports(report_date,status,reconciled_at,reconciled_by,created_by,created_at) VALUES (?,'Reconciled',NOW(),?,?,NOW())");
            $stmt->bind_param("sii", $date, $user_id, $user_id); $stmt->execute(); $stmt->close();
        }
        $fn->notifyFinance('Reconciliation', 'Daily Reconciliation Generated', "Cash report for $date has been marked as reconciled.", 'normal', 'reports', 0);
        auditLog($conn,$user_id,'DAY_RECONCILE','daily_cash_reports',0,"Marked $date as reconciled");
        ok();

    case 'save_fee':
        if($user_role!=='finance_manager'&&$user_role!=='admin') fail('Insufficient permissions.');
        $fid = (int)($data['fee_id']??0);
        $name = sanitize($data['service_name']??'');
        $code = sanitize($data['service_code']??'');
        $catid = (int)($data['category_id']??0); $catid = $catid > 0 ? $catid : null;
        $base = round((float)($data['base_amount']??0),2);
        $stud = !empty($data['student_amount']) ? round((float)$data['student_amount'],2) : null;
        $tax = round((float)($data['tax_rate_pct']??0),2);
        $taxable = (int)($data['is_taxable']??0);
        $eff = sanitize($data['effective_from']??date('Y-m-d'));
        
        if($fid){
            $stmt = $conn->prepare("UPDATE fee_schedule SET service_name=?,service_code=?,category_id=?,base_amount=?,student_amount=?,tax_rate_pct=?,is_taxable=?,effective_from=?,updated_at=NOW() WHERE fee_id=?");
            $stmt->bind_param("ssiddidss", $name, $code, $catid, $base, $stud, $tax, $taxable, $eff, $fid);
            $stmt->execute(); $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO fee_schedule(service_name,service_code,category_id,base_amount,student_amount,tax_rate_pct,is_taxable,effective_from,is_active,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,1,?,NOW())");
            $stmt->bind_param("ssiddidsi", $name, $code, $catid, $base, $stud, $tax, $taxable, $eff, $user_id);
            $stmt->execute(); $fid = (int)$stmt->insert_id; $stmt->close();
        }
        ok(['fee_id'=>$fid]);

    case 'upload_profile_photo':
        if(empty($_FILES['photo'])) fail('No photo uploaded.');
        $file = $_FILES['photo'];
        $allowed = ['image/jpeg','image/png','image/webp']; $ftype = mime_content_type($file['tmp_name']);
        if(!in_array($ftype,$allowed)) fail('Only JPG/PNG/WEBP allowed.');
        if($file['size']>2*1024*1024) fail('File too large. Max 2MB.');
        $ext = ($ftype==='image/png')?'png':(($ftype==='image/webp')?'webp':'jpg');
        $dir = dirname(dirname(dirname(__DIR__))).'/uploads/profiles/';
        if(!is_dir($dir)) mkdir($dir,0755,true);
        $fname = 'fin_'.$user_id.'_'.time().'.'.$ext;
        if(move_uploaded_file($file['tmp_name'], $dir.$fname)) {
            $path = 'uploads/profiles/'.$fname;
            $stmt = $conn->prepare("UPDATE users SET profile_image=?,updated_at=NOW() WHERE id=?");
            $stmt->bind_param("si", $path, $user_id); $stmt->execute(); $stmt->close();
            ok(['path'=>$path]);
        }
        fail('Failed to upload file due to server configuration.');

    default:
        // Ignore unhandled actions strictly defined in older logic or fallthrough.
        fail('Unknown or unauthorized action: '.sanitize($action));

    endswitch;

} catch (Exception $e) {
    error_log("[Finance Action Error] ".$e->getMessage());
    fail('A server error occurred while processing your request.', 500);
}
?>
