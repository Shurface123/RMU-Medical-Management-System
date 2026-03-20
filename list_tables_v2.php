<?php
require_once 'php/db_conn.php';
$res = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_array($res)) {
    echo $row[0] . "\n";
}
?>
