<?php
require 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
$pass = password_hash('password123', PASSWORD_DEFAULT);
if (mysqli_query($conn, "INSERT INTO users (user_name, email, password, user_role, name) VALUES ('cleaner1', 'cleaner@rmu.edu', '$pass', 'cleaner', 'Test Cleaner')")) {
    $uid = mysqli_insert_id($conn);
    mysqli_query($conn, "INSERT INTO staff (user_id, full_name, role) VALUES ($uid, 'Test Cleaner', 'cleaner')");
    echo "Test cleaner created successfully. You can login with cleaner1 / password123\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>
