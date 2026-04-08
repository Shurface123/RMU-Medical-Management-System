<?php
require 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
$r = mysqli_query($conn, "DESCRIBE staff");
while($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
