<?php
require_once __DIR__ . '/db_conn.php';
$res = mysqli_query($conn, "SELECT * FROM staff_roles");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
