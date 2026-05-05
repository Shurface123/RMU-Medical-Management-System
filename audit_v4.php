<?php
require_once 'php/db_conn.php';
$tables = ['users', 'staff_messages'];
foreach($tables as $t){
    echo "\n--- Table: $t ---\n";
    $res = $conn->query("DESCRIBE $t");
    while($row = $res->fetch_assoc()){
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}
