<?php
// ============================================================
// PHARMACY DOWNLOAD HANDLER — Secure File Serving
// File paths never exposed directly — served through this handler
// ============================================================
require_once 'pharmacy_security.php';
initSecureSession();
$user_id = enforcePharmacistRole();
require_once '../db_conn.php';

$fileId   = (int)($_GET['id'] ?? 0);
$fileType = htmlspecialchars($_GET['type'] ?? 'report');

if (!$fileId) {
    http_response_code(400);
    die('Invalid request');
}

$filePath = null;

switch ($fileType) {
    case 'report':
        $row = dbRow($conn, "SELECT file_path, format, report_type FROM pharmacy_reports WHERE id=? AND generated_by=?", "ii", [$fileId, $user_id]);
        if ($row) $filePath = $row['file_path'];
        break;

    case 'document':
        $row = dbRow($conn, "SELECT file_path, file_name FROM pharmacist_documents WHERE id=? AND pharmacist_id=?", "ii", [$fileId, $user_id]);
        if ($row) $filePath = $row['file_path'];
        break;

    default:
        http_response_code(400);
        die('Unknown file type');
}

if (!$filePath) {
    http_response_code(404);
    die('File not found or access denied');
}

// Resolve full path — prevent directory traversal
$basePath = realpath(__DIR__ . '/../../');
$fullPath = realpath($basePath . '/' . $filePath);

if (!$fullPath || strpos($fullPath, $basePath) !== 0 || !is_file($fullPath)) {
    http_response_code(404);
    die('File not found');
}

// Determine MIME type safely
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($fullPath);

$allowedMimes = [
    'text/csv', 'text/plain',
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'image/jpeg', 'image/png',
];

if (!in_array($mime, $allowedMimes)) {
    http_response_code(403);
    die('File type not permitted for download');
}

// Serve file
$fileName = basename($filePath);
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
exit;
