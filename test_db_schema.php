<?php
require_once 'php/db_conn.php';

$tables = ['staff_shifts'];
foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']} - {$row['Extra']}\n";
        }
    } else {
        echo "Error or table not found.\n";
    }
}
?>
