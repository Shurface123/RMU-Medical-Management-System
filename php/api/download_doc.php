<?php
// ============================================
// SECURE FILE DOWNLOAD HANDLER
// /php/api/download_doc.php
// ============================================
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Unauthorized Access');
}

$file = $_GET['file'] ?? '';

// Prevent directory traversal attacks
if (empty($file) || strpos($file, '..') !== false || strpos($file, '/') !== false || strpos($file, '\\') !== false) {
    http_response_code(400);
    die('Invalid file path');
}

// Absolute path to protected directory
$protected_dir = realpath(__DIR__ . '/../../uploads/tech_docs/');
$file_path = $protected_dir . DIRECTORY_SEPARATOR . $file;

if (!file_exists($file_path)) {
    http_response_code(404);
    die('File not found');
}

// Get mime type based on extension
$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$mime_types = [
    'pdf' => 'application/pdf',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

$content_type = $mime_types[$ext] ?? 'application/octet-stream';

// Headers for forcing download or displaying inline
header('Content-Type: ' . $content_type);
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Clear output buffer and send file
ob_clean();
flush();
readfile($file_path);
exit;
