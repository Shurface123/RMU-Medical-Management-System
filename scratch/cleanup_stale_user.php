<?php
require_once 'php/db_conn.php';

$userId = 309;
$email = 'www.lovelacejohnbaidoo@gmail.com';

echo "Starting cleanup for User ID: $userId and Email: $email\n\n";

// 1. Delete audit logs
$q1 = "DELETE FROM user_registration_audit WHERE user_id = $userId";
if (mysqli_query($conn, $q1)) {
    echo "Deleted audit logs for user $userId.\n";
} else {
    echo "Error deleting audit logs: " . mysqli_error($conn) . "\n";
}

// 2. Delete registration sessions
$q2 = "DELETE FROM registration_sessions WHERE email = '$email'";
if (mysqli_query($conn, $q2)) {
    echo "Deleted registration sessions for $email.\n";
} else {
    echo "Error deleting sessions: " . mysqli_error($conn) . "\n";
}

// 3. Delete email verifications
$q3 = "DELETE FROM email_verifications WHERE email = '$email'";
if (mysqli_query($conn, $q3)) {
    echo "Deleted email verifications for $email.\n";
} else {
    echo "Error deleting verifications: " . mysqli_error($conn) . "\n";
}

// 4. Delete user
$q4 = "DELETE FROM users WHERE id = $userId";
if (mysqli_query($conn, $q4)) {
    echo "Deleted user $userId from users table.\n";
} else {
    echo "Error deleting user: " . mysqli_error($conn) . "\n";
}

echo "\nCleanup complete. The user can now re-register.\n";
?>
