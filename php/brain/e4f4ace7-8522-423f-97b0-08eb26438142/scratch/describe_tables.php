<?php
$conn = mysqli_connect('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

function describe($conn, $table) {
    echo "--- STRUCTURE OF $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    while($row = mysqli_fetch_assoc($res)) {
        printf("%-20s %-20s %-10s %-10s %-10s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Default']);
    }
    echo "\n";
}

describe($conn, 'users');
describe($conn, 'pharmacist_profile');
?>
