<?php
require_once 'php/db_conn.php';
$tables = ['pharmacist_profile', 'doctors', 'nurses', 'lab_technicians', 'staff', 'finance_staff'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE `$table` ");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo "{$row['Field']} - {$row['Type']}\n";
        }
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
