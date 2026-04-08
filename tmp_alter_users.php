<?php
require 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
mysqli_query($conn, "ALTER TABLE users ADD COLUMN accepted_terms TINYINT(1) DEFAULT 1");
mysqli_query($conn, "UPDATE users SET accepted_terms = 0 WHERE created_at < NOW()");
echo "Added accepted_terms to users table.\n";
