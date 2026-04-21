<?php
require_once __DIR__ . '/db_conn.php';

function desc($conn, $t) {
    echo "\n--- $t ---\n";
    $res = mysqli_query($conn, "DESC $t");
    while ($row = mysqli_fetch_assoc($res)) print_r($row);
}

desc($conn, 'staff');
desc($conn, 'staff_notifications');
desc($conn, 'maintenance_requests');
desc($conn, 'staff_messages');
desc($conn, 'staff_tasks');
?>
