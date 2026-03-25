<?php
require_once 'php/db_conn.php';
$tables = ['lab_test_orders', 'lab_samples', 'lab_results', 'lab_technician_settings', 'lab_technicians'];
foreach($tables as $t) {
    echo "--- TABLE: $t ---\n";
    $res = mysqli_query($conn, "DESCRIBE $t");
    if($res) {
        while($row = mysqli_fetch_assoc($res)) {
            echo "{$row['Field']} - {$row['Type']}\n";
        }
    } else {
        echo "Error: Could not describe $t\n";
    }
    echo "\n";
}
?>
