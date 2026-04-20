<?php
// ============================================================
// LAB DASHBOARD — SECURE EXPORT HANDLER
// lab_exports.php
// ============================================================
session_start();
require_once '../../php/includes/lab_security.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lab_technician') {
    http_response_code(403);
    exit('Access denied.');
}

$conn = $pdo ?? null;
if (!$conn) {
    require_once '../../php/db_connect.php';
}

$action = $_GET['action'] ?? '';
$format = strtolower($_GET['format'] ?? 'csv');

// ── Helper: fetch rows as associative arrays ──
function fetchAll($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    $rows = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    }
    return $rows;
}

// ── CSV output ──
function outputCSV($filename, array $headers, array $rows) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, array_values($row));
    }
    fclose($out);
    exit;
}

// ── Excel (simple HTML-table trick) ──
function outputExcel($filename, array $headers, array $rows) {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta charset="UTF-8"/></head><body>';
    echo '<table border="1"><tr>';
    foreach ($headers as $h) echo '<th>' . htmlspecialchars($h) . '</th>';
    echo '</tr>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach (array_values($row) as $cell) {
            echo '<td>' . htmlspecialchars((string)$cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

$tech_id = (int)$_SESSION['user_id'];
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to']   ?? date('Y-m-d');
$date_from = mysqli_real_escape_string($conn, $date_from);
$date_to   = mysqli_real_escape_string($conn, $date_to);

// ════════════════════════════════════════════════
// ACTION: export_lab_report
// ════════════════════════════════════════════════
if ($action === 'export_lab_report') {

    $rows = fetchAll($conn, "
        SELECT 
            r.id                                         AS 'Result ID',
            CONCAT('#ORD-', LPAD(o.id,5,'0'))            AS 'Order Ref',
            p.full_name                                  AS 'Patient',
            p.patient_id                                 AS 'Patient ID',
            c.test_name                                  AS 'Test Name',
            c.category                                   AS 'Category',
            r.result_value                               AS 'Result Value',
            r.unit                                       AS 'Unit',
            r.result_status                              AS 'Status',
            r.is_critical                                AS 'Critical Flag',
            DATE_FORMAT(r.created_at, '%Y-%m-%d %H:%i') AS 'Date Entered',
            DATE_FORMAT(r.validated_at, '%Y-%m-%d %H:%i') AS 'Validated At'
        FROM lab_results r
        JOIN lab_test_orders o ON r.order_id = o.id
        JOIN patients p ON o.patient_id = p.id
        JOIN lab_test_catalog c ON r.test_catalog_id = c.id
        WHERE DATE(r.created_at) BETWEEN '$date_from' AND '$date_to'
        ORDER BY r.created_at DESC
        LIMIT 5000
    ");

    $headers = empty($rows) ? ['Result ID','Order Ref','Patient','Patient ID','Test Name','Category','Result Value','Unit','Status','Critical Flag','Date Entered','Validated At'] : array_keys($rows[0]);
    $fname   = 'Lab_Results_' . date('Ymd_Hi') . ($format === 'excel' ? '.xls' : '.csv');

    if ($format === 'excel') {
        outputExcel($fname, $headers, $rows);
    } else {
        outputCSV($fname, $headers, $rows);
    }
}

// ════════════════════════════════════════════════
// ACTION: export_audit_trail
// ════════════════════════════════════════════════
elseif ($action === 'export_audit_trail') {

    $rows = fetchAll($conn, "
        SELECT 
            CONCAT('#', LPAD(a.id,6,'0'))                AS 'Audit ID',
            DATE_FORMAT(a.created_at,'%Y-%m-%d %H:%i:%s') AS 'Timestamp',
            a.tech_name                                  AS 'Technician',
            a.action_type                                AS 'Action',
            a.module_affected                            AS 'Module',
            a.record_id                                  AS 'Record ID',
            a.old_value                                  AS 'Previous Value',
            a.new_value                                  AS 'New Value',
            a.ip_address                                 AS 'IP Address'
        FROM lab_audit_trail a
        WHERE DATE(a.created_at) BETWEEN '$date_from' AND '$date_to'
        ORDER BY a.created_at DESC
        LIMIT 10000
    ");

    $headers = empty($rows) ? ['Audit ID','Timestamp','Technician','Action','Module','Record ID','Previous Value','New Value','IP Address'] : array_keys($rows[0]);
    $fname   = 'Lab_Audit_Trail_' . date('Ymd_Hi') . ($format === 'excel' ? '.xls' : '.csv');

    if ($format === 'excel') {
        outputExcel($fname, $headers, $rows);
    } else {
        outputCSV($fname, $headers, $rows);
    }
}

// ════════════════════════════════════════════════
// ACTION: export_inventory
// ════════════════════════════════════════════════
elseif ($action === 'export_inventory') {

    $rows = fetchAll($conn, "
        SELECT 
            item_code                                    AS 'Item Code',
            name                                         AS 'Reagent Name',
            supplier                                     AS 'Supplier',
            quantity_in_stock                            AS 'Qty In Stock',
            unit                                         AS 'Unit',
            reorder_level                                AS 'Reorder Level',
            storage_conditions                           AS 'Storage',
            DATE_FORMAT(expiry_date,'%Y-%m-%d')          AS 'Expiry Date',
            status                                       AS 'Status',
            batch_number                                 AS 'Batch #'
        FROM reagent_inventory
        ORDER BY status ASC, name ASC
        LIMIT 2000
    ");

    $headers = empty($rows) ? ['Item Code','Reagent Name','Supplier','Qty In Stock','Unit','Reorder Level','Storage','Expiry Date','Status','Batch #'] : array_keys($rows[0]);
    $fname   = 'Lab_Inventory_' . date('Ymd_Hi') . ($format === 'excel' ? '.xls' : '.csv');

    if ($format === 'excel') {
        outputExcel($fname, $headers, $rows);
    } else {
        outputCSV($fname, $headers, $rows);
    }
}

else {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown export action: ' . htmlspecialchars($action)]);
}
