<?php
/**
 * Financial Reporting Engine — RMU Medical Sickbay Finance
 * URL: /php/finance/generate_report.php?type=daily_revenue&format=pdf&from=2024-01-01&to=2024-01-01
 */
session_start();
require_once __DIR__.'/../includes/auth_middleware.php';
require_once __DIR__.'/../db_conn.php';

// Access Control
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['finance_officer', 'finance_manager', 'admin'])){
    die('Unauthorized access.');
}

$user_id = (int)$_SESSION['user_id'];
$type = $_GET['type'] ?? 'custom';
$format = $_GET['format'] ?? 'pdf';
$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');

// Helper for formatted money
function money($v){ return 'GHS '.number_format($v,2); }

// ── 1. Logic: Data Aggregation ────────────────────────────────
$title = "Financial Report";
$data = [];
$summary = [];

switch($type){
    case 'daily_revenue':
        $title = "Daily Revenue Summary (" . date('d M Y', strtotime($from)) . ")";
        $q = mysqli_query($conn, "SELECT p.*, bi.invoice_number, u.name AS patient_name 
                                  FROM payments p 
                                  JOIN billing_invoices bi ON p.invoice_id = bi.invoice_id 
                                  JOIN patients pt ON p.patient_id = pt.id 
                                  JOIN users u ON pt.user_id = u.id 
                                  WHERE DATE(p.payment_date) BETWEEN '$from' AND '$to' AND p.status='Completed' 
                                  ORDER BY p.payment_date");
        if($q) while($r=mysqli_fetch_assoc($q)) $data[]=$r;
        $summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count, SUM(amount) AS total FROM payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to' AND status='Completed'"));
        break;

    case 'revenue_by_service':
        $title = "Revenue by Service Category";
        $q = mysqli_query($conn, "SELECT rc.category_name, COUNT(ili.line_item_id) AS qty, SUM(ili.line_total) AS total 
                                  FROM invoice_line_items ili 
                                  JOIN billing_invoices bi ON ili.invoice_id = bi.invoice_id 
                                  LEFT JOIN fee_schedule fs ON ili.fee_id = fs.fee_id 
                                  JOIN revenue_categories rc ON fs.category_id = rc.category_id 
                                  WHERE DATE(bi.invoice_date) BETWEEN '$from' AND '$to' AND bi.status IN ('Paid','Partially Paid') 
                                  GROUP BY rc.category_id ORDER BY total DESC");
        if($q) while($r=mysqli_fetch_assoc($q)) $data[]=$r;
        break;

    case 'outstanding':
        $title = "Outstanding Balances Report";
        $q = mysqli_query($conn, "SELECT bi.*, u.name AS patient_name, pt.patient_id AS pat_code 
                                  FROM billing_invoices bi 
                                  JOIN patients pt ON bi.patient_id = pt.id 
                                  JOIN users u ON pt.user_id = u.id 
                                  WHERE bi.balance_due > 0 AND bi.status NOT IN ('Cancelled','Void','Written Off') 
                                  ORDER BY bi.balance_due DESC");
        if($q) while($r=mysqli_fetch_assoc($q)) $data[]=$r;
        $summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count, SUM(balance_due) AS total FROM billing_invoices WHERE balance_due > 0 AND status NOT IN ('Cancelled','Void','Written Off')"));
        break;

    default:
        die('Report type not implemented yet.');
}

// Log generation in financial_reports for history
$title_esc = mysqli_real_escape_string($conn, $title);
mysqli_query($conn, "INSERT INTO financial_reports(report_type, title, period_start, period_end, file_format, generated_by, created_at) 
                     VALUES('$type', '$title_esc', '$from', '$to', '".strtoupper($format)."', $user_id, NOW())");

// ── 2. Output Handlers ────────────────────────────────────────

if($format === 'csv'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='.str_replace(' ','_',$title).'.csv');
    $out = fopen('php://output', 'w');
    fputs($out, chr(0xEF).chr(0xBB).chr(0xBF));
    if(!empty($data)) fputcsv($out, array_keys($data[0]));
    foreach($data as $line) fputcsv($out, $line);
    fclose($out);
    exit;
}

// PDF (HTML/Print) View
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?=$title?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background: #f4f7f6; margin: 0; padding: 3rem; color: #333; }
    .report-paper { background: #fff; max-width: 1000px; margin: 0 auto; padding: 4rem; box-shadow: 0 5px 25px rgba(0,0,0,0.05); border-radius: 8px; position: relative; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #eee; padding-bottom: 2rem; margin-bottom: 3rem; }
    .brand h1 { margin: 0; color: #1a9e6e; font-size: 2.2rem; font-weight: 800; }
    .brand p { margin: 0; color: #777; font-size: 1.1rem; }
    .meta { text-align: right; }
    .meta h2 { margin: 0; font-size: 1.8rem; color: #333; }
    .meta p { margin: 0; color: #888; }
    .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-bottom: 3rem; }
    .summary-card { padding: 1.5rem; background: #f9fbfb; border: 1px solid #eef7f4; border-radius: 8px; text-align: center; }
    .summary-card label { display: block; font-size: 0.9rem; color: #888; text-transform: uppercase; margin-bottom: 0.5rem; }
    .summary-card value { display: block; font-size: 1.6rem; font-weight: 700; color: #1a9e6e; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 3rem; font-size: 0.95rem; }
    th { background: #f4f7f6; padding: 1rem; text-align: left; border-bottom: 2px solid #eee; font-weight: 600; }
    td { padding: 1rem; border-bottom: 1px solid #f0f0f0; }
    tr:nth-child(even) { background: #fafafa; }
    .footer { text-align: center; border-top: 1px solid #eee; padding-top: 2rem; color: #888; font-size: 0.9rem; }
    .print-btn { position: fixed; bottom: 2rem; right: 2rem; background: #1a9e6e; color: #fff; border: none; padding: 1rem 2rem; border-radius: 30px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 15px rgba(26,158,110,0.3); }
    @media print { .print-btn { display: none; } body { background: #fff; padding: 0; } .report-paper { box-shadow: none; border: none; max-width: 100%; } }
</style>
</head>
<body>
    <div class="report-paper">
        <div class="header">
            <div class="brand">
                <h1>RMU SICKBAY</h1>
                <p>Regional Maritime University</p>
                <p>Finance & Revenue Department</p>
            </div>
            <div class="meta">
                <h2>FINANCIAL REPORT</h2>
                <p>Period: <?=date('d M Y', strtotime($from))?> to <?=date('d M Y', strtotime($to))?></p>
                <p>Generated: <?=date('d M Y, h:i A')?></p>
            </div>
        </div>

        <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem;"><?=htmlspecialchars($title)?></h3>

        <?php if(!empty($summary)): ?>
        <div class="summary-grid">
            <div class="summary-card"><label>Total Count</label><value><?=$summary['count']?></value></div>
            <div class="summary-card"><label>Total Value</label><value><?=money($summary['total'])?></value></div>
            <div class="summary-card"><label>Average Value</label><value><?=money($summary['count'] > 0 ? $summary['total']/$summary['count'] : 0)?></value></div>
        </div>
        <?php endif;?>

        <table>
            <thead>
                <tr>
                    <?php if(!empty($data)): foreach(array_keys($data[0]) as $h): if(in_array($h,['id','patient_id','invoice_id','processed_by','generated_by','is_active','metadata','paystack_response'])) continue; ?>
                        <th><?=ucwords(str_replace('_',' ',$h))?></th>
                    <?php endforeach; endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data as $row): ?>
                <tr>
                    <?php foreach($row as $k=>$v): if(in_array($k,['id','patient_id','invoice_id','processed_by','generated_by','is_active','metadata','paystack_response'])) continue; ?>
                        <td>
                            <?php 
                            if(strpos($k,'amount')!==false || strpos($k,'total')!==false || strpos($k,'balance')!==false || $k==='spent_amount' || $k==='allocated_amount') echo money($v);
                            elseif(strpos($k,'date')!==false || $k==='created_at') echo date('d M Y', strtotime($v));
                            else echo htmlspecialchars($v);
                            ?>
                        </td>
                    <?php endforeach;?>
                </tr>
                <?php endforeach;?>
                <?php if(empty($data)): ?><tr><td colspan="10" style="text-align:center;padding:3rem;color:#888;">No data found for this period.</td></tr><?php endif;?>
            </tbody>
        </table>

        <div class="footer">
            <p>This report was generated by <?=htmlspecialchars($_SESSION['user_name'] ?? 'Authorized Staff')?>.</p>
            <p>RMU Medical Sickbay Management System — Internal Use Only</p>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
</body>
</html>
