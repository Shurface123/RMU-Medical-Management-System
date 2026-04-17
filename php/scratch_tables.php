<?php
require_once 'db_conn.php';
$res = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($res)) {
    echo $row[0] . "\n";
}
