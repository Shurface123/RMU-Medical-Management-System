<?php
require 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
$tables = ['notifications', 'staff_notifications', 'finance_notifications'];
foreach ($tables as $t) {
    echo "--- $t ---\n";
    $q = mysqli_query($conn, "DESCRIBE $t");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) echo $r['Field'] . ' - ' . $r['Type'] . "\n";
    } else {
        echo "Table not found\n";
    }
}
