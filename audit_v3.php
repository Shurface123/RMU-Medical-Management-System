<?php
require_once 'php/db_conn.php';
$tables = ['users', 'staff_messages', 'staff_leaves', 'staff_notifications', 'wards', 'contamination_reports'];
foreach($tables as $t){
    echo "\n--- Table: $t ---\n";
    $res = $conn->query("DESCRIBE $t");
    if (!$res) { echo "Failed: " . $conn->error . "\n"; continue; }
    while($row = $res->fetch_assoc()){
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}
