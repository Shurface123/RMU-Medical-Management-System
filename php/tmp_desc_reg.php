<?php
require 'db_conn.php';
$r = mysqli_query($conn, "DESCRIBE registration_sessions");
while($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
