<?php
require 'db_conn.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$session_token = "abcd";
$email = "test@test.com";
$role = "doctor";
$temp_data = "{}";
$expires_at = "2026-04-09 10:00:00";

$s = mysqli_prepare($conn,
    "INSERT INTO registration_sessions
     (session_token,email,role,step_reached,temp_data,expires_at)
     VALUES (?,?,?,2,?,?)");

try {
    mysqli_stmt_bind_param($s,'ssiss', $session_token,$email,$role,$temp_data,$expires_at);
    mysqli_stmt_execute($s);
    echo "Success!";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
