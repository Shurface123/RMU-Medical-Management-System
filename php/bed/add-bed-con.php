<?php
session_start();
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bed_number = mysqli_real_escape_string($conn, trim($_POST['bed_number'] ?? ''));
    $ward       = mysqli_real_escape_string($conn, trim($_POST['ward'] ?? ''));
    $bed_type   = mysqli_real_escape_string($conn, $_POST['bed_type'] ?? 'Standard');
    $daily_rate = (float)($_POST['daily_rate'] ?? 0);
    $status     = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Available');
    $notes      = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));

    if ($bed_number && $ward) {
        $notes_val = $notes ? "'$notes'" : 'NULL';
        $sql = "INSERT INTO beds (bed_number, ward, bed_type, daily_rate, status, notes)
                VALUES ('$bed_number','$ward','$bed_type',$daily_rate,'$status',$notes_val)";
        if (mysqli_query($conn, $sql)) {
            header('Location: bed.php?success=Bed+added+successfully');
            exit();
        }
    }
    header('Location: add-bed.php?error=Failed+to+add+bed');
    exit();
}
?>