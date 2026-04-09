<?php
require 'db_conn.php';
$r = mysqli_query($conn, "SELECT * FROM recaptcha_logs ORDER BY id DESC LIMIT 5");
print_r(mysqli_fetch_all($r, MYSQLI_ASSOC));
