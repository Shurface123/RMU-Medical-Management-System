<?php
require_once __DIR__ . '/db_conn.php';

function checkTable($conn, $table) {
    echo "\n--- $table ---\n";
    $res = mysqli_query($conn, "DESC $table");
    if (!$res) {
        echo "Table $table missing!\n";
        return;
    }
    while ($row = mysqli_fetch_assoc($res)) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
}

$tables = ['maintenance_requests', 'staff_notifications', 'staff_messages', 'staff_tasks', 'staff'];
foreach ($tables as $t) checkTable($conn, $t);
?>
