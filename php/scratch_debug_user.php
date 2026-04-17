<?php
require_once 'db_conn.php';
$search = 'Shurface';

echo "--- Searching in 'users' ---\n";
$q = mysqli_query($conn, "SELECT id, user_name, email, user_role, account_status, is_active, is_verified FROM users WHERE user_name LIKE '%$search%' OR email LIKE '%$search%'");
while($r = mysqli_fetch_assoc($q)) print_r($r);

echo "\n--- Searching in 'pharmacist_profile' ---\n";
// Use the correct table name we discovered earlier
$q2 = mysqli_query($conn, "SELECT * FROM pharmacist_profile WHERE full_name LIKE '%$search%'");
if ($q2) {
    while($r = mysqli_fetch_assoc($q2)) print_r($r);
} else {
    echo "Table pharmacist_profile check failed or table missing.\n";
}

echo "\n--- Searching in 'registration_sessions' ---\n";
$q3 = mysqli_query($conn, "SELECT email, role, step_reached FROM registration_sessions WHERE email LIKE '%$search%'");
while($r = mysqli_fetch_assoc($q3)) print_r($r);
