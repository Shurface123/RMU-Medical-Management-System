<?php
require_once 'php/db_conn.php';
$tables = ['patients', 'doctors', 'staff', 'nurses'];
foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo $row['Field'] . "\n";
        }
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
