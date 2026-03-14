<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include_once 'db_conn.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?error=' . urlencode('Invalid bed ID'));
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM beds WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?success=' . urlencode('Bed deleted successfully'));
} else {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?error=' . urlencode('Failed to delete bed'));
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit;
?>