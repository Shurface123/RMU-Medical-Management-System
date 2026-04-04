<?php
require_once 'db_conn.php';
$tables = ['users', 'patients', 'doctors', 'nurses', 'pharmacists', 'lab_technicians', 'staff'];
foreach ($tables as $t) {
    echo "--- $t ---\n";
    $res = mysqli_query($conn, "DESCRIBE `$t` ");
    if ($res) {
        printf("%-25s | %-20s | %-10s | %-10s | %-20s\n", "Field", "Type", "Null", "Key", "Default");
        echo str_repeat("-", 95) . "\n";
        while($row = mysqli_fetch_assoc($res)) {
            printf("%-25s | %-20s | %-10s | %-10s | %-20s\n", 
                $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Default']);
        }
    } else {
        echo "Table not found\n";
    }
    echo "\n";
}
