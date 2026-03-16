<?php
require_once 'php/db_conn.php';

// Get a random active user (prefer staff to test role-agnostic too)
$user_q = mysqli_query($conn, "SELECT id, user_name, user_role FROM users WHERE is_active=1 LIMIT 1");
$user = mysqli_fetch_assoc($user_q);

if (!$user) {
    die("No active users found to test.");
}

$uname = $user['user_name'];
$role = $user['user_role'];
$uid = $user['id'];

echo "Testing brute-force lockout for user: $uname (ID: $uid, Role: $role)\n";

$base = "http://localhost/RMU-Medical-Management-System/php/login.php";

$data = http_build_query([
    'uname' => $uname,
    'password' => 'wrongpass_deliberate',
    'role' => $role
]);
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => $data,
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($options);

// Simulate 6 failed logins and output location header
for ($i=1; $i<=6; $i++) {
    file_get_contents($base, false, $context);
    echo "Attempt $i headers:\n";
    foreach($http_response_header as $h) {
        if (strpos($h, 'Location') !== false) {
            echo "  $h\n";
        }
    }
}

// Check database state to verify 'locked_until' is set
$lock_q = mysqli_query($conn, "SELECT locked_until FROM users WHERE id=$uid");
$lock_res = mysqli_fetch_assoc($lock_q);
$locked_until = $lock_res['locked_until'];

echo "\nDatabase State after 6 attempts:\n";
echo "Locked Until: " . ($locked_until ? $locked_until : 'NULL') . "\n";

// Now unlock the user and clean up to restore state
mysqli_query($conn, "UPDATE users SET locked_until=NULL WHERE id=$uid");
mysqli_query($conn, "DELETE FROM global_login_attempts WHERE user_id=$uid AND action_type='login_failed'");
echo "Test complete. User unlocked and attempts cleared.\n";
?>
