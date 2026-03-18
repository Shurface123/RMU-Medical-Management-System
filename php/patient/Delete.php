<?php
session_start();
require_once '../db_conn.php';

// Check if user is logged in as Admin or Doctor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'doctor'])) {
    header("Location: /RMU-Medical-Management-System/php/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid Request. Patient ID is missing.'); window.location.href='/RMU-Medical-Management-System/php/patient/patient.php';</script>";
    exit();
}

$patient_uid = (int)$_GET['id'];

// Prevent duplicate execution logic
mysqli_begin_transaction($conn);
try {
    // We are performing a soft-delete (deactivation) because deleting users outright
    // would violate foreign key constraints in medical_records, appointments, etc.
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $patient_uid);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $update_usr = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $update_usr->bind_param("i", $patient_uid);
        $update_usr->execute();
    } else {
        throw new Exception("Patient not found.");
    }

    mysqli_commit($conn);
    header("Location: /RMU-Medical-Management-System/php/patient/patient.php?msg=deleted");
    exit();
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Patient Deletion Error: " . $e->getMessage());
    echo "<script>alert('System Error during deactivation: " . htmlspecialchars($e->getMessage()) . "'); window.location.href='/RMU-Medical-Management-System/php/patient/patient.php';</script>";
    exit();
}
?>
