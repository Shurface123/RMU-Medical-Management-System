<?php
require_once __DIR__ . '/db_conn.php';

function desc($conn, $t) {
    echo "\n--- $t ---\n";
    $res = mysqli_query($conn, "DESC $t");
    while ($row = mysqli_fetch_assoc($res)) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
}

desc($conn, 'staff_notifications');
desc($conn, 'maintenance_requests');
?>
