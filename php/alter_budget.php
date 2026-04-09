<?php
require 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
$q = "ALTER TABLE budget_allocations MODIFY status ENUM('Draft','Active','Exhausted','Closed','Revised','Rejected') DEFAULT 'Draft'";
if (mysqli_query($conn, $q)) {
    echo "budget_allocations table altered successfully.\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
