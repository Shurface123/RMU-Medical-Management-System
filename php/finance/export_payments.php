<?php
/**
 * Export Payments — RMU Medical Sickbay Finance
 * URL: /php/finance/export_payments.php?from=2024-01-01&to=2024-12-31
 */
session_start();
require_once __DIR__.'/../includes/auth_middleware.php';
require_once __DIR__.'/../db_conn.php';

// Only finance staff
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['finance_officer', 'finance_manager', 'admin'])){
    die('Unauthorized access.');
}

$date_from = $_GET['from'] ?? '';
$date_to = $_GET['to'] ?? '';
$method = $_GET['method'] ?? '';

$sql = "SELECT p.*, bi.invoice_number, u.name AS patient_name, pt.patient_id AS pat_code, u2.name AS processor_name
        FROM payments p
        JOIN billing_invoices bi ON p.invoice_id = bi.invoice_id
        JOIN patients pt ON p.patient_id = pt.id
        JOIN users u ON pt.user_id = u.id
        LEFT JOIN users u2 ON p.processed_by = u2.id
        WHERE 1=1";

if(!empty($date_from) && !empty($date_to)) {
    $sql .= " AND DATE(p.payment_date) BETWEEN '".mysqli_real_escape_string($conn, $date_from)."' AND '".mysqli_real_escape_string($conn, $date_to)."'";
}
if(!empty($method)) {
    $sql .= " AND p.payment_method = '".mysqli_real_escape_string($conn, $method)."'";
}

$sql .= " ORDER BY p.payment_date DESC, p.payment_id DESC";
$result = mysqli_query($conn, $sql);

if(!$result) die('Export failed: ' . mysqli_error($conn));

$filename = "RMU_Payments_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Header row
fputcsv($output, [
    'Receipt #',
    'Payment Ref',
    'Invoice #',
    'Patient Name',
    'Patient ID',
    'Amount (GHS)',
    'Date & Time',
    'Method',
    'Channel',
    'Paystack Ref',
    'Status',
    'Processed By',
    'Notes'
]);

while($row = mysqli_fetch_assoc($result)){
    fputcsv($output, [
        $row['receipt_number'],
        $row['payment_reference'],
        $row['invoice_number'],
        $row['patient_name'],
        $row['pat_code'],
        number_format($row['amount'], 2, '.', ''),
        $row['payment_date'],
        $row['payment_method'],
        $row['channel'],
        $row['paystack_reference'] ?? 'N/A',
        $row['status'],
        $row['processor_name'] ?? 'System / Online',
        $row['notes']
    ]);
}

fclose($output);
exit;
