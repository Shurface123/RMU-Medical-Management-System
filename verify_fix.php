<?php
// Mock database connection for testing if needed, but here we'll use the real one to verify schema
require_once 'php/db_conn.php';

echo "Testing registration logic updates...\n";

// Test data
$fullname = "Test User " . rand(100, 999);
$email = "test" . rand(1000, 9999) . "@example.com";
$phone = "050" . rand(1000000, 9999999);
$username = "testuser" . rand(1000, 9999);
$password = "password123";
$role = "patient";
$is_active = 1;

// 1. Test Patient Insertion
echo "1. Testing Patient Insertion...\n";
$user_id = 99999; // Mock user_id for testing patient table insertion directly if we don't want to create a real user

// Generate unique patient_id (Logic copied from register_handler.php)
$last_p_res = mysqli_query($conn, "SELECT COUNT(*) FROM patients");
$last_p = mysqli_fetch_row($last_p_res)[0] ?? 0;
$patient_id_val = 'PAT-' . str_pad($last_p + 1, 5, '0', STR_PAD_LEFT);

$patient_sql = "INSERT INTO patients (user_id, patient_id, created_at) VALUES (?, ?, NOW())";
$patient_stmt = mysqli_prepare($conn, $patient_sql);
if ($patient_stmt) {
    mysqli_stmt_bind_param($patient_stmt, "is", $user_id, $patient_id_val);
    if (mysqli_stmt_execute($patient_stmt)) {
        echo "Successfully inserted patient with ID: $patient_id_val\n";
        // Clean up
        mysqli_query($conn, "DELETE FROM patients WHERE patient_id = '$patient_id_val'");
    } else {
        echo "FAILED to insert patient: " . mysqli_stmt_error($patient_stmt) . "\n";
    }
    mysqli_stmt_close($patient_stmt);
} else {
    echo "FAILED to prepare patient statement: " . mysqli_error($conn) . "\n";
}

// 2. Test Doctor Insertion
echo "2. Testing Doctor Insertion...\n";
$last_d_res = mysqli_query($conn, "SELECT COUNT(*) FROM doctors");
$last_d = mysqli_fetch_row($last_d_res)[0] ?? 0;
$doctor_id_val = 'DOC-' . str_pad($last_d + 1, 4, '0', STR_PAD_LEFT);

$doctor_sql = "INSERT INTO doctors (user_id, doctor_id, specialization, created_at) VALUES (?, ?, '', NOW())";
$doctor_stmt = mysqli_prepare($conn, $doctor_sql);
if ($doctor_stmt) {
    mysqli_stmt_bind_param($doctor_stmt, "is", $user_id, $doctor_id_val);
    if (mysqli_stmt_execute($doctor_stmt)) {
        echo "Successfully inserted doctor with ID: $doctor_id_val\n";
        // Clean up
        mysqli_query($conn, "DELETE FROM doctors WHERE doctor_id = '$doctor_id_val'");
    } else {
        echo "FAILED to insert doctor: " . mysqli_stmt_error($doctor_stmt) . "\n";
    }
    mysqli_stmt_close($doctor_stmt);
} else {
    echo "FAILED to prepare doctor statement: " . mysqli_error($conn) . "\n";
}

echo "Verification complete.\n";
?>
