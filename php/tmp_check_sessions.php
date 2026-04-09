<?php
require 'db_conn.php';
$r = mysqli_query($conn, "SELECT * FROM registration_sessions");
print_r(mysqli_fetch_all($r, MYSQLI_ASSOC));
