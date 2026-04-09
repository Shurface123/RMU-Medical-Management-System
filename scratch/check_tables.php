<?php
include 'php/db_conn.php';
$tables = ['broadcasts', 'broadcast_recipients', 'health_messages'];
foreach ($tables as $t) {
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    echo "$t: " . (mysqli_num_rows($res) > 0 ? 'exists' : 'MISSING') . PHP_EOL;
}
