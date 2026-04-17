<?php
$conn = mysqli_connect('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

echo "--- SEARCHING USERS ---\n";
$res = mysqli_query($conn, "SELECT * FROM users WHERE name LIKE '%Jemima%' OR email LIKE '%Jemima%'");
while($row = mysqli_fetch_assoc($res)) print_r($row);

echo "\n--- SEARCHING PHARMACIST_PROFILE ---\n";
$res = mysqli_query($conn, "SELECT * FROM pharmacist_profile WHERE full_name LIKE '%Jemima%'");
while($row = mysqli_fetch_assoc($res)) print_r($row);

echo "\n--- SEARCHING REGISTRATION_SESSIONS ---\n";
$res = mysqli_query($conn, "SELECT * FROM registration_sessions WHERE name LIKE '%Jemima%' OR email LIKE '%Jemima%'");
while($row = mysqli_fetch_assoc($res)) print_r($row);

echo "\n--- SEARCHING DISPENSING_RECORDS ---\n";
$res = mysqli_query($conn, "SELECT * FROM dispensing_records WHERE pharmacist_id IN (SELECT id FROM users WHERE name LIKE '%Jemima%')");
while($row = mysqli_fetch_assoc($res)) print_r($row);
?>
