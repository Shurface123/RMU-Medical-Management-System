<?php
require_once 'db_conn.php';
$tables = ['users', 'patients', 'doctors', 'nurses', 'pharmacists', 'lab_technicians', 'staff'];
foreach ($tables as $t) {
    echo "--- $t ---\n";
    $res = mysqli_query($conn, "DESCRIBE `$t` ");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) {
            echo "{$row['Field']} | {$row['Type']}\n";
        }
    } else {
        echo "Table not found\n";
    }
}
