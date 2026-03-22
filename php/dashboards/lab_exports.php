<?php
// ============================================
// LAB EXPORT HANDLER
// ============================================
require_once 'lab_security.php';
initSecureSession();
$user_id = enforceLabTechRole();

if (!isset($_GET['action']) || $_GET['action'] !== 'export_audit_trail') {
    die("Invalid export request.");
}

// Basic CSRF defense on GET requests
$got_csrf = $_GET['csrf'] ?? '';
verifyCsrfToken($got_csrf);

require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');

// Set headers to force download as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=lab_security_audit_export_' . date('Ymd_His') . '.csv');

// Stream to output
$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fputs($output, $bom =(chr(0xEF).chr(0xBB).chr(0xBF)));

// Header Row
fputcsv($output, ['Audit ID', 'Timestamp', 'Technician User ID', 'Technician Name', 'Action Executed', 'Module', 'Target Record ID', 'Old Value (JSON)', 'New Value (JSON)', 'IP Address', 'Device Information']);

$query = "SELECT a.*, l.full_name 
          FROM lab_audit_trail a 
          LEFT JOIN lab_technicians l ON a.technician_id = l.user_id 
          ORDER BY a.created_at DESC";

$aud_q = mysqli_query($conn, $query);

if ($aud_q) {
    while ($row = mysqli_fetch_assoc($aud_q)) {
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $row['technician_id'],
            $row['full_name'] ?: 'N/A',
            $row['action_type'],
            $row['module_affected'],
            $row['record_id_affected'],
            $row['old_value'],
            $row['new_value'],
            $row['ip_address'],
            $row['device_info']
        ]);
    }
}

fclose($output);
exit();
