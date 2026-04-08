<?php
require 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
$r = mysqli_query($conn, "DESCRIBE staff");
$out = [];
while($row = mysqli_fetch_assoc($r)) {
    $out[] = $row['Field'] . ' ' . $row['Type'] . ' ' . $row['Null'] . ' ' . $row['Default'];
}
echo json_encode($out, JSON_PRETTY_PRINT);
