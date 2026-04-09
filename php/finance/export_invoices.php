<?php
/**
 * Export Invoices — RMU Medical Sickbay Finance
 * URL: /php/finance/export_invoices.php?format=xlsx&ids=1,2,3
 */
session_start();
require_once __DIR__.'/../includes/auth_middleware.php';
require_once __DIR__.'/../db_conn.php';

// Only finance staff
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['finance_officer', 'finance_manager', 'admin'])){
    die('Unauthorized access.');
}

$ids = isset($_GET['ids']) ? array_map('intval', explode(',', $_GET['ids'])) : [];
$format = $_GET['format'] ?? 'csv'; // Default to CSV for compatibility
$date_from = $_GET['from'] ?? '';
$date_to = $_GET['to'] ?? '';

$sql = "SELECT bi.*, u.name AS patient_name, pt.patient_id AS pat_code, u2.name AS created_by_name
        FROM billing_invoices bi
        JOIN patients pt ON bi.patient_id = pt.id
        JOIN users u ON pt.user_id = u.id
        LEFT JOIN users u2 ON bi.generated_by = u2.id
        WHERE 1=1";

if(!empty($ids)) {
    $id_list = implode(',', $ids);
    $sql .= " AND bi.invoice_id IN ($id_list)";
} elseif(!empty($date_from) && !empty($date_to)) {
    $sql .= " AND bi.invoice_date BETWEEN '".mysqli_real_escape_string($conn, $date_from)."' AND '".mysqli_real_escape_string($conn, $date_to)."'";
}

$sql .= " ORDER BY bi.invoice_date DESC, bi.invoice_id DESC";
$result = mysqli_query($conn, $sql);

if(!$result) die('Export failed: ' . mysqli_error($conn));

// Headers for forced download
$filename = "RMU_Invoices_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 support
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Header row
fputcsv($output, [
    'Invoice #', 
    'Patient Name', 
    'Patient ID', 
    'Date', 
    'Due Date', 
    'Subtotal (GHS)', 
    'Tax (GHS)', 
    'Discount (GHS)', 
    'Total (GHS)', 
    'Paid (GHS)', 
    'Balance (GHS)', 
    'Status', 
    'Created By',
    'Notes'
]);

while($row = mysqli_fetch_assoc($result)){
    fputcsv($output, [
        $row['invoice_number'],
        $row['patient_name'],
        $row['pat_code'],
        $row['invoice_date'],
        $row['due_date'],
        number_format($row['subtotal'], 2, '.', ''),
        number_format($row['tax_total'], 2, '.', ''),
        number_format($row['discount_total'], 2, '.', ''),
        number_format($row['total_amount'], 2, '.', ''),
        number_format($row['paid_amount'], 2, '.', ''),
        number_format($row['balance_due'], 2, '.', ''),
        $row['status'],
        $row['created_by_name'] ?? 'System',
        $row['notes']
    ]);
}

fclose($output);
exit;
