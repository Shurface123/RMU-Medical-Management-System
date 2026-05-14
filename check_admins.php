<?php
require 'php/db_conn.php';
$r = mysqli_query($conn, "SELECT id, name, user_role, status FROM users WHERE name LIKE '%Admin%' OR user_role LIKE '%admin%'");
while($row = mysqli_fetch_assoc($r)) {
    print_r($row);
}
?>
