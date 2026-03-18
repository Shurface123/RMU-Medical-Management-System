<?php
session_start();
require_once 'db_conn.php';

// Check if user is Doctor, Nurse, Admin, or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'doctor', 'nurse', 'superadmin'])) {
    header("Location: /RMU-Medical-Management-System/php/login.php");
    exit();
}

if (!isset($_GET['bed_id'])) {
    echo "<script>alert('Invalid Request. Bed ID is missing.'); window.location.href='/RMU-Medical-Management-System/php/bed/bed.php';</script>";
    exit();
}

$bed_id = (int)$_GET['bed_id'];

// Prevent duplicate execution logic
mysqli_begin_transaction($conn);
try {
    // 1. Find the active assignment for this bed
    $stmt = $conn->prepare("SELECT patient_id FROM bed_assignments WHERE bed_id = ? AND status = 'Active' LIMIT 1");
    if (!$stmt) throw new Exception(mysqli_error($conn));
    $stmt->bind_param("i", $bed_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        // We have an active assignment, mark it completed/discharged
        $update_assign = $conn->prepare("UPDATE bed_assignments SET status = 'Discharged', discharge_date = NOW() WHERE bed_id = ? AND status = 'Active'");
        $update_assign->bind_param("i", $bed_id);
        $update_assign->execute();
    }
    
    // 2. Set the bed back to Available and optionally trigger clean required
    $update_bed = $conn->prepare("UPDATE beds SET status = 'Available' WHERE id = ?");
    $update_bed->bind_param("i", $bed_id);
    $update_bed->execute();

    mysqli_commit($conn);
    header("Location: /RMU-Medical-Management-System/php/bed/bed.php?msg=discharged");
    exit();
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Discharge Error: " . $e->getMessage());
    echo "<script>alert('System Error during discharge.'); window.location.href='/RMU-Medical-Management-System/php/bed/bed.php';</script>";
    exit();
}
?>
