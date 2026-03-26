<?php
session_start();
$_SESSION['user_id'] = 1; // mock admin ID
$_SESSION['role'] = 'admin';
$_POST['csrf_token'] = 'test';
$_SESSION['csrf_token'] = 'test';

require_once __DIR__ . '/db_conn.php';

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
require_once __DIR__ . '/admin/admin_staff_actions.php';
$output = ob_get_clean();

echo "Output of admin_staff_actions:\n";
echo $output . "\n";

// Verify user table state
$res = mysqli_query($conn, "SELECT is_active, account_status FROM users WHERE id = $uid");
$user_state = mysqli_fetch_assoc($res);
echo "User state after approval: is_active=" . $user_state['is_active'] . ", account_status=" . $user_state['account_status'] . "\n";

// Clean up test data
mysqli_query($conn, "DELETE FROM doctors WHERE id = $did");
mysqli_query($conn, "DELETE FROM users WHERE id = $uid");
echo "Cleaned up.\n";
