<?php
require 'db_conn.php';
$r = mysqli_query($conn, "SELECT * FROM system_email_config WHERE is_active=1 ORDER BY id LIMIT 1");
if ($r && $row = mysqli_fetch_assoc($r)) {
    print_r($row);
} else {
    echo "No system_email_config found or empty. Using fallback.";
}
