<?php
session_start();
$_SESSION['user_id'] = 1; // mock admin ID
$_SESSION['role'] = 'admin';
$_POST['csrf_token'] = 'test';
$_SESSION['csrf_token'] = 'test';

require_once __DIR__ . '/../db_conn.php';

// Check if user already exists
$res = mysqli_query($conn, "SELECT id FROM users WHERE email='testdoc@rmu.edu.gh'");
if ($row = mysqli_fetch_assoc($res)) {
    $uid = $row['id'];
    mysqli_query($conn, "DELETE FROM doctors WHERE user_id=$uid");
    mysqli_query($conn, "DELETE FROM users WHERE id=$uid");
}

// Create a dummy user
$q1 = mysqli_query($conn, "INSERT INTO users (user_name, email, password, name, user_role, is_active, is_verified, account_status)
VALUES ('testdoc', 'testdoc@rmu.edu.gh', 'pass', 'Dr. Test', 'doctor', 0, 1, 'inactive')");
$uid = mysqli_insert_id($conn);

// Create a dummy doctor linking to that user
$q2 = mysqli_query($conn, "INSERT INTO doctors (user_id, doctor_id, full_name, specialization) 
VALUES ($uid, 'DOC-TEST', 'Dr. Test', 'General')");
$did = mysqli_insert_id($conn);

echo "Created test doctor with user_id: $uid, doctor_id: $did\n";

// Simulate approve_staff
$_POST['action'] = 'approve_staff';
$_POST['staff_id'] = $did;
$_POST['type'] = 'doctor';

ob_start();
require_once __DIR__ . '/admin_staff_actions.php';
$output = ob_get_clean();

echo "Output of admin_staff_actions:\n";
echo $output . "\n\n";

// Verify user table state
$res = mysqli_query($conn, "SELECT is_active, account_status FROM users WHERE id = $uid");
$user_state = mysqli_fetch_assoc($res);
echo "User state after approval: is_active=" . $user_state['is_active'] . ", account_status=" . $user_state['account_status'] . "\n";

// Ensure audit log exists
$audit_res = mysqli_query($conn, "SELECT * FROM user_registration_audit WHERE user_id = $uid");
if (mysqli_num_rows($audit_res) > 0) {
    echo "Audit log entry created successfully.\n";
} else {
    echo "Audit log entry MISSING.\n";
}

// Clean up test data
mysqli_query($conn, "DELETE FROM doctors WHERE id = $did");
mysqli_query($conn, "DELETE FROM user_registration_audit WHERE user_id = $uid");
mysqli_query($conn, "DELETE FROM users WHERE id = $uid");
echo "Cleaned up.\n";
