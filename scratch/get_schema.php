<?php
include 'php/db_conn.php';
$tables = ['broadcasts', 'broadcast_recipients', 'health_messages'];
foreach ($tables as $t) {
    echo "--- Table: $t ---" . PHP_EOL;
    $res = mysqli_query($conn, "DESCRIBE $t");
    while ($row = mysqli_fetch_assoc($res)) {
        echo "{$row['Field']} ({$row['Type']}) " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . PHP_EOL;
    }
    echo PHP_EOL;
}
