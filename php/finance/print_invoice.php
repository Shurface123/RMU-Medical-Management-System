<?php
/**
 * Print Invoice/View Invoice — RMU Medical Sickbay Finance
 * URL: /php/finance/print_invoice.php?id=[invoice_id]
 */
session_start();
require_once __DIR__.'/../includes/auth_middleware.php';
require_once __DIR__.'/../db_conn.php';

if(!isset($_SESSION['user_id'])) { header('Location: /RMU-Medical-Management-System/php/login.php'); exit; }
$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? '';

$inv_id = (int)($_GET['id'] ?? 0);
if(!$inv_id) die('Invoice ID required.');

$inv = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT bi.*, pt.patient_id AS pat_code, u.name AS patient_name, u.email, u.phone, u2.name AS generator
     FROM billing_invoices bi
     JOIN patients pt ON bi.patient_id = pt.id
     JOIN users u ON pt.user_id = u.id
     LEFT JOIN users u2 ON bi.generated_by = u2.id
     WHERE bi.invoice_id=$inv_id LIMIT 1"
));

if(!$inv) die('Invoice not found.');

// Authorization: If patient, must be their own invoice
if($user_role==='patient') {
    $is_mine = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM patients WHERE user_id=$user_id AND id={$inv['patient_id']} LIMIT 1"));
    if(!$is_mine) die('Unauthorized.');
}

// Line items
$lines = [];
$lq = mysqli_query($conn, "SELECT ili.*, fs.service_name FROM invoice_line_items ili LEFT JOIN fee_schedule fs ON ili.fee_id=fs.fee_id WHERE ili.invoice_id=$inv_id");
if($lq) while($r=mysqli_fetch_assoc($lq)) $lines[]=$r;

// Payments
$pays = [];
$pq = mysqli_query($conn, "SELECT * FROM payments WHERE invoice_id=$inv_id AND status='Completed' ORDER BY created_at");
if($pq) while($r=mysqli_fetch_assoc($pq)) $pays[]=$r;

$badge_map = [
    'Draft' => ['#666', '#f0f0f0'],
    'Pending' => ['#2F80ED', '#eef5fe'],
    'Partially Paid' => ['#E67E22', '#fdf3e8'],
    'Paid' => ['#27AE60', '#eafaf1'],
    'Overdue' => ['#E74C3C', '#fceeee'],
    'Cancelled' => ['#444', '#e0e0e0']
];
$status_color = $badge_map[$inv['status']][0] ?? '#666';
$status_bg = $badge_map[$inv['status']][1] ?? '#f0f0f0';

// Note: Ensure /download_invoice.php exists or just reuse print for patients
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Invoice <?=htmlspecialchars($inv['invoice_number'])?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: #e0e0e0; margin: 0; padding: 2rem; color:#333; }
.inv-box { max-width: 800px; margin: 0 auto; background: #fff; padding: 4rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); position:relative; }
.inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 3rem; }
.brand h1 { margin: 0; color: #1a9e6e; font-size: 2.2rem; font-weight: 800; line-height: 1; }
.brand p { margin: 0; color: #777; font-size: 1.1rem; }
.meta { text-align: right; }
.meta h2 { margin:0; font-size: 2.4rem; color: #333; letter-spacing: 1px; }
.meta p { margin:0; font-size: 1.2rem; font-weight:700; color: #555; }
.status-badge { display:inline-block; margin-top:1rem; padding:0.4rem 1.2rem; border-radius:20px; font-weight:700; font-size:1.1rem; background:<?=$status_bg?>; color:<?=$status_color?>; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem; background:#f9fcfb; padding:2rem; border-radius:8px; border:1px solid #eef7f4;}
.info-grid h3 { margin:0 0 .5rem 0; font-size:1rem; color:#888; text-transform:uppercase; letter-spacing:1px; }
.info-grid p { margin:0 0 .3rem 0; font-size:1.1rem; }
.info-grid strong { color:#333; }
.inv-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
.inv-table th { background: linear-gradient(135deg, #0d3b2e, #1a9e6e); color: #fff; padding: 1rem 1.5rem; text-align: left; font-size:1.1rem; font-weight:600;}
.inv-table th:last-child { border-radius:0 8px 0 0; }
.inv-table th:first-child { border-radius:8px 0 0 0; }
.inv-table th.right, .inv-table td.right { text-align: right; }
.inv-table th.center, .inv-table td.center { text-align: center; }
.inv-table td { padding: 1.2rem 1.5rem; border-bottom: 1px solid #eee; font-size:1.1rem;}
.summary-box { width:350px; margin-left:auto; }
.summary-row { display:flex; justify-content:space-between; padding:0.6rem 0; font-size:1.2rem;}
.summary-row.total { border-top:2px solid #ddd; margin-top:0.5rem; padding-top:1rem; font-size:1.6rem; font-weight:800; color:#1a9e6e; }
.summary-row.balance { font-size:1.4rem; font-weight:700; color:#E74C3C; }
.history { margin-top: 3rem; border-top: 2px dashed #eee; padding-top:2rem;}
.history h3 { font-size:1.2rem; margin:0 0 1rem 0; color:#555;}
.history-row { display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid #f0f0f0; font-size:1.1rem;}
.footer { margin-top: 4rem; text-align: center; color: #888; font-size: 1rem; border-top:1px solid #eee; padding-top:2rem;}
.watermark { position:absolute; top:40%; left:50%; transform:translate(-50%,-50%) rotate(-30deg); font-size:10rem; opacity:0.04; font-weight:800; color:<?=$status_color?>; pointer-events:none; z-index:0; text-transform:uppercase; white-space:nowrap; }
.print-btn { display: block; margin: 2rem auto; max-width: 800px; background: #d4a017; color: #fff; text-align: center; padding: 1.2rem; border-radius: 8px; font-size: 1.2rem; font-weight: 600; text-decoration: none; cursor:pointer; border:none; transition:0.3s; }
.print-btn:hover { background: #b8860b; }
@media print {
  body { background: #fff; padding: 0; }
  .inv-box { box-shadow: none; max-width: 100%; border-radius: 0; padding:1cm; }
  .print-btn { display: none; }
}
</style>
</head>
<body>
  <div class="inv-box">
    <?php if(in_array($inv['status'], ['Paid','Overdue','Cancelled','Draft'])): ?><div class="watermark"><?=$inv['status']?></div><?php endif; ?>

    <div class="inv-header" style="position:relative; z-index:1;">
      <div class="brand">
        <h1>RMU SICKBAY</h1>
        <p>Regional Maritime University</p>
        <p>Finance & Revenue Dept.</p>
      </div>
      <div class="meta">
        <h2>INVOICE</h2>
        <p><?=htmlspecialchars($inv['invoice_number'])?></p>
        <div class="status-badge"><?=htmlspecialchars($inv['status'])?></div>
      </div>
    </div>

    <div class="info-grid" style="position:relative; z-index:1;">
      <div>
        <h3>Bill To</h3>
        <p><strong><?=htmlspecialchars($inv['patient_name'])?></strong></p>
        <p><?=htmlspecialchars($inv['pat_code'])?></p>
        <p><?=htmlspecialchars($inv['email']??$inv['phone'])?></p>
      </div>
      <div style="text-align:right;">
        <p><span style="color:#888;">Invoice Date:</span> <strong style="color:#333;"><?=date('d M Y', strtotime($inv['invoice_date']))?></strong></p>
        <?php if($inv['due_date']): ?>
          <p><span style="color:#888;">Due Date:</span> <strong style="color:<?=strtotime($inv['due_date'])<time()&&$inv['status']!=='Paid'?'#E74C3C':'#333'?>"><?=date('d M Y', strtotime($inv['due_date']))?></strong></p>
        <?php endif;?>
        <p><span style="color:#888;">Generated By:</span> <strong style="color:#333;"><?=htmlspecialchars($inv['generator'] ?? 'System')?></strong></p>
      </div>
    </div>

    <table class="inv-table" style="position:relative; z-index:1;">
      <thead>
        <tr>
          <th>Service Description</th>
          <th class="center">Qty</th>
          <th class="right">Unit Price</th>
          <th class="right">Discount</th>
          <th class="right">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($lines as $li): ?>
        <tr>
          <td><?=htmlspecialchars($li['service_name'] ?? $li['service_description'])?></td>
          <td class="center"><?=(int)$li['quantity']?></td>
          <td class="right">GHS <?=number_format($li['unit_price'], 2)?></td>
          <td class="right" style="color:#27AE60;"><?=$li['discount_pct']>0 ? $li['discount_pct'].'%' : '—'?></td>
          <td class="right"><strong>GHS <?=number_format($li['line_total'], 2)?></strong></td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>

    <div class="summary-box" style="position:relative; z-index:1;">
      <div class="summary-row"><span style="color:#888;">Subtotal</span><span style="font-weight:600;">GHS <?=number_format($inv['subtotal'], 2)?></span></div>
      <?php if(isset($inv['discount_amount']) && $inv['discount_amount']>0): ?><div class="summary-row"><span style="color:#27AE60;">Discount</span><span style="color:#27AE60;font-weight:600;">-GHS <?=number_format($inv['discount_amount'], 2)?></span></div><?php endif; ?>
      <?php if($inv['tax_amount']>0): ?><div class="summary-row"><span style="color:#888;">Tax</span><span style="font-weight:600;">GHS <?=number_format($inv['tax_amount'], 2)?></span></div><?php endif; ?>
      <div class="summary-row total"><span>Total</span><span>GHS <?=number_format($inv['total_amount'], 2)?></span></div>
      
      <div class="summary-row" style="margin-top:1rem;"><span style="color:#27AE60;">Paid Amount</span><span style="color:#27AE60;font-weight:600;">GHS <?=number_format($inv['paid_amount'], 2)?></span></div>
      <div class="summary-row balance"><span>Balance Due</span><span>GHS <?=number_format($inv['balance_due'], 2)?></span></div>
    </div>

    <?php if(!empty($pays)): ?>
    <div class="history" style="position:relative; z-index:1;">
      <h3>Payment History</h3>
      <?php foreach($pays as $p): ?>
      <div class="history-row">
        <span><?=date('d M Y, h:i A', strtotime($p['payment_date']))?> — <?=htmlspecialchars($p['payment_method'])?> <span style="color:#888;font-size:0.9rem;">(<?=htmlspecialchars($p['receipt_number'])?>)</span></span>
        <span style="color:#27AE60;font-weight:600;">+ GHS <?=number_format($p['amount'], 2)?></span>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>

    <div class="footer" style="position:relative; z-index:1;">
      <p>Thank you for choosing RMU Sickbay. For any queries regarding this invoice, please contact the Finance & Revenue Department.</p>
    </div>
  </div>

  <button class="btn btn-outline btn-icon print-btn" onclick="window.print()"><span class="btn-text">🖨️ Print Invoice</span></button>
</body>
</html>
