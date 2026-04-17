<?php
$conn = mysqli_connect('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$user_id = 203;

echo "--- FINAL STATUS FOR USERS ID $user_id ---\n";
$res1 = mysqli_query($conn, "SELECT id, name, user_role, status, account_status, is_active, is_verified FROM users WHERE id=$user_id");
print_r(mysqli_fetch_assoc($res1));

echo "\n--- FINAL STATUS FOR PHARMACIST_PROFILE ID $user_id ---\n";
$res2 = mysqli_query($conn, "SELECT * FROM pharmacist_profile WHERE user_id=$user_id");
print_r(mysqli_fetch_assoc($res2));
?>
