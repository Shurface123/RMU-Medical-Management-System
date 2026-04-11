<?php
/**
 * Print Receipt — RMU Medical Sickbay Finance
 * URL: /php/finance/print_receipt.php?id=[payment_id]
 */
session_start();
require_once __DIR__.'/../includes/auth_middleware.php';
require_once __DIR__.'/../db_conn.php';

// Allow any logged-in user to print their own receipt
if(!isset($_SESSION['user_id'])) { header('Location: /RMU-Medical-Management-System/php/login.php'); exit; }
$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? '';

$pay_id = (int)($_GET['id'] ?? 0);
if(!$pay_id) die('Receipt ID required.');

$pay = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.*, bi.invoice_number, bi.balance_due, bi.status AS inv_status, pt.patient_id AS pat_code, u.name AS patient_name, u2.name AS processor
     FROM payments p
     JOIN billing_invoices bi ON p.invoice_id = bi.invoice_id
     JOIN patients pt ON p.patient_id = pt.id
     JOIN users u ON pt.user_id = u.id
     LEFT JOIN users u2 ON p.processed_by = u2.id
     WHERE p.payment_id=$pay_id LIMIT 1"
));

if(!$pay) die('Receipt not found.');

// Authorization: If patient, must be their own receipt
if($user_role==='patient') {
    $is_mine = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM patients WHERE user_id=$user_id AND id={$pay['patient_id']} LIMIT 1"));
    if(!$is_mine) die('Unauthorized.');
}

$logo_path = '/RMU-Medical-Management-System/images/rmu_logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Receipt <?=htmlspecialchars($pay['receipt_number'])?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: #e0e0e0; margin: 0; padding: 2rem; color:#333; }
.receipt-box { max-width: 600px; margin: 0 auto; background: #fff; padding: 3rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); position:relative; }
.receipt-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; border-bottom: 2px solid #f0f0f0; padding-bottom: 2rem; }
.brand h1 { margin: 0; color: #1a9e6e; font-size: 2.2rem; font-weight: 800; line-height: 1; }
.brand p { margin: 0; color: #777; font-size: 1.1rem; }
.meta { text-align: right; }
.meta h2 { margin:0; font-size: 2rem; color: #333; letter-spacing: 1px; }
.meta p { margin:0; color: #777; }
.details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2.5rem; background:#f9fcfb; padding:1.5rem; border-radius:8px; border:1px solid #eef7f4;}
.details-grid label { display: block; font-size: 0.9rem; color: #888; margin-bottom: 0.2rem; text-transform:uppercase; letter-spacing:0.05em; }
.details-grid div.val { font-size: 1.3rem; font-weight: 600; color: #333; }
.amount-box { background: linear-gradient(135deg, #1a9e6e, #14795c); color: #fff; text-align: center; padding: 2rem; border-radius: 12px; margin-bottom: 2.5rem; }
.amount-box span { display: block; font-size: 1rem; opacity: 0.9; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.5rem; }
.amount-box h3 { margin: 0; font-size: 3.5rem; font-weight: 800; display:flex; align-items:center; justify-content:center; gap:0.5rem;}
.amount-box h3 small { font-size: 1.5rem; font-weight: 400; opacity:0.8;}
.footer { border-top: 2px dashed #e0e0e0; margin-top: 3rem; padding-top: 2rem; text-align: center; color: #888; font-size: 1rem; }
.watermark { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%) rotate(-30deg); font-size:8rem; opacity:0.03; font-weight:800; color:#1a9e6e; pointer-events:none; z-index:0; text-transform:uppercase; white-space:nowrap; }
.print-btn { display: block; margin: 2rem auto; max-width: 600px; background: #d4a017; color: #fff; text-align: center; padding: 1.2rem; border-radius: 8px; font-size: 1.2rem; font-weight: 600; text-decoration: none; cursor:pointer; border:none; transition:0.3s; }
.print-btn:hover { background: #b8860b; }
@media print {
  body { background: #fff; padding: 0; }
  .receipt-box { box-shadow: none; max-width: 100%; border-radius: 0; padding:1cm; }
  .print-btn { display: none; }
}
</style>
</head>
<body>
  <div class="receipt-box">
    <?php if($pay['status']==='Refunded' || $pay['status']==='Cancelled'): ?><div class="watermark"><?=$pay['status']?></div><?php endif; ?>
    <?php if($pay['status']==='Completed'): ?><div class="watermark">PAID</div><?php endif; ?>

    <div class="receipt-header" style="position:relative; z-index:1;">
      <div class="brand">
        <h1>RMU SICKBAY</h1>
        <p>Regional Maritime University</p>
        <p>Finance & Revenue Dept.</p>
      </div>
      <div class="meta">
        <h2>RECEIPT</h2>
        <p><?=htmlspecialchars($pay['receipt_number']??'Pending')?></p>
        <p style="margin-top:0.5rem; color:#1a9e6e; font-weight:600; font-size:1.1rem;"><?=date('d M Y, h:i A', strtotime($pay['payment_date']))?></p>
      </div>
    </div>

    <div class="amount-box" style="position:relative; z-index:1;">
      <span>Amount Paid</span>
      <h3><small>GHS</small> <?=number_format($pay['amount'], 2)?></h3>
    </div>

    <div class="details-grid" style="position:relative; z-index:1;">
      <div><label>Bill To / Patient</label><div class="val"><?=htmlspecialchars($pay['patient_name'])?></div><div style="font-size:1rem;color:#666;"><?=htmlspecialchars($pay['pat_code'])?></div></div>
      <div><label>Invoice Number</label><div class="val"><?=htmlspecialchars($pay['invoice_number'])?></div></div>
      <div>
        <label>Payment Method</label>
        <div class="val"><?=htmlspecialchars($pay['payment_method'])?></div>
        <div style="font-size:0.95rem;color:#666;">Channel: <?=htmlspecialchars($pay['channel'])?></div>
        <?php if($pay['paystack_reference']): ?><div style="font-size:0.85rem;color:#888;">Ref: <?=htmlspecialchars($pay['paystack_reference'])?></div><?php endif;?>
      </div>
      <div>
        <label>Status</label>
        <div class="val" style="color:<?=$pay['status']==='Completed'?'#27AE60':'#E74C3C'?>;"><?=htmlspecialchars($pay['status'])?></div>
        <?php if($pay['balance_due']>0): ?><div style="font-size:1rem;color:#E67E22; margin-top:0.2rem;">Inv. Balance: GHS <?=number_format($pay['balance_due'],2)?></div><?php endif;?>
      </div>
    </div>

    <div class="footer" style="position:relative; z-index:1;">
      <p>Processed by: <?=htmlspecialchars($pay['processor'] ?? 'System / Online')?></p>
      <p style="margin-top:0.5rem;">Thank you for your payment. This receipt is computer-generated and requires no physical signature.</p>
    </div>
  </div>

  <button class="btn btn-outline btn-icon print-btn" onclick="window.print()"><span class="btn-text">🖨️ Print Receipt</span></button>
</body>
</html>
