<?php
require_once 'php/db_conn.php';

$tables = ['cleaning_logs', 'contamination_reports', 'wards', 'staff'];
foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } else echo "Error: " . mysqli_error($conn) . "\n";
}
?>
