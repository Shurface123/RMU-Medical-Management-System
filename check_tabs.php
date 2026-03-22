<?php
require_once 'php/db_conn.php';
$res = mysqli_query($conn, "SHOW TABLES LIKE '%noti%'");
while($row = mysqli_fetch_row($res)) {
    echo $row[0] . "\n";
}
echo "----\n";
// Describe generic notifications table if it exists
$desc = mysqli_query($conn, "DESCRIBE notifications");
if($desc) {
    while($r = mysqli_fetch_assoc($desc)) echo json_encode($r)."\n";
} else echo "No generic 'notifications' table.\n";
