<?php
// ============================================================
// SECURE FILE DOWNLOAD HANDLER — Lab Technician Dashboard
// Serves lab result reports and documents without exposing
// real file paths in URL. Enforces ownership + RBAC.
// ============================================================
define('AJAX_REQUEST', true);
require_once 'lab_security.php';
initSecureSession();
setSecurityHeaders();
$user_id = enforceLabTechRole();
require_once '../db_conn.php';

$file_id    = (int)($_GET['id']    ?? 0);
$table      = $_GET['type']        ?? '';
$tech_pk    = (int)(dbVal($conn,"SELECT id FROM lab_technicians WHERE user_id=?","i",[$user_id]) ?? 0);

if(!$file_id || !$tech_pk){
    http_response_code(400); exit('Invalid request');
}

// ── Allowed types + their ownership columns ────────────────
$type_map = [
    'report'      => ['table'=>'lab_reports',                 'path_col'=>'file_path',       'owner_col'=>'technician_id'],
    'result'      => ['table'=>'lab_results_v2',              'path_col'=>'report_file_path','owner_col'=>'technician_id'],
    'document'    => ['table'=>'lab_technician_documents',    'path_col'=>'file_path',       'owner_col'=>'technician_id'],
    'certificate' => ['table'=>'lab_technician_certifications','path_col'=>'certificate_file','owner_col'=>'technician_id'],
    'qualification'=>['table'=>'lab_technician_qualifications','path_col'=>'certificate_file','owner_col'=>'technician_id'],
];

if(!isset($type_map[$table])){
    http_response_code(403); exit('Invalid file type');
}

$meta       = $type_map[$table];
$db_table   = $meta['table'];
$path_col   = $meta['path_col'];
$owner_col  = $meta['owner_col'];

// Fetch the record, enforcing ownership at DB level
$row = dbRow($conn,
    "SELECT `$path_col` AS file_path FROM `$db_table` WHERE id=? AND `$owner_col`=? LIMIT 1",
    "ii", [$file_id, $tech_pk]
);

if(!$row || empty($row['file_path'])){
    http_response_code(404); exit('File not found or access denied');
}

// ── Path traversal protection ─────────────────────────────
$base = realpath($_SERVER['DOCUMENT_ROOT'] . '/RMU-Medical-Management-System/');
$path = realpath($base . '/' . ltrim($row['file_path'], '/\\'));

if(!$path || strpos($path, $base) !== 0 || !file_exists($path)){
    http_response_code(404); exit('File not found');
}

// ── Log the download ──────────────────────────────────────
logLabActivity($conn, $tech_pk, 'download_file', $table, $file_id);

// ── Determine MIME and serve ──────────────────────────────
$mime = mime_content_type($path) ?: 'application/octet-stream';
$allowed_mimes = [
    'application/pdf',
    'image/jpeg','image/png','image/gif','image/webp',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/csv',
];

if(!in_array($mime, $allowed_mimes)){
    http_response_code(415); exit('Unsupported file type');
}

// ── Send headers and stream file ─────────────────────────
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

// Prevent script execution if browser somehow runs it inline
ob_clean();
flush();
readfile($path);
exit;
