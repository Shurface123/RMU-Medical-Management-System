<?php
session_start();
require_once 'db_conn.php';
require_once 'classes/FileUploadManager.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$fileUploadManager = new FileUploadManager($conn);
$userId = $_SESSION['user_id'];

// Get attachment
if (!isset($_GET['id'])) {
    die('Attachment ID required');
}

$attachmentId = $_GET['id'];

// Download the file (includes access control check)
$fileUploadManager->downloadAttachment($attachmentId, $userId);
?>
