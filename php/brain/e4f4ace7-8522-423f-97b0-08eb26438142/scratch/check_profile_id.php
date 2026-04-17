<?php
$conn = mysqli_connect('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

echo "--- PHARMACIST_PROFILE FOR ID 203 ---\n";
$res = mysqli_query($conn, "SELECT * FROM pharmacist_profile WHERE user_id = 203");
while($row = mysqli_fetch_assoc($res)) print_r($row);
?>
