<?php
require 'db_conn.php';
$res = mysqli_query($conn, "SHOW TABLES LIKE '%session%'");
while($row = mysqli_fetch_row($res)) {
    echo $row[0] . "\n";
    $cols = mysqli_query($conn, "DESCRIBE " . $row[0]);
    while($col = mysqli_fetch_assoc($cols)) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
?>
