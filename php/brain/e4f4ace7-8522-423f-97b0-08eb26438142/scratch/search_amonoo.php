<?php
$conn = mysqli_connect('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

echo "--- SEARCHING ALL TABLES FOR 'AMONOO' ---\n";
$tables = ['users', 'pharmacist_profile', 'registration_sessions', 'dispensing_records'];
foreach ($tables as $t) {
    echo "Table: $t\n";
    $res = mysqli_query($conn, "SELECT * FROM $t WHERE name LIKE '%Amonoo%' OR email LIKE '%Amonoo%' OR full_name LIKE '%Amonoo%' OR session_token LIKE '%Amonoo%'");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) print_r($row);
    }
}
?>
