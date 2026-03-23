<?php
require_once '../db_conn.php';
$res = mysqli_query($conn, "SHOW COLUMNS FROM patients");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . PHP_EOL;
}
?>
