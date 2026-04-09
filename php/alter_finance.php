<?php
require 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
$q = "ALTER TABLE finance_staff 
      ADD COLUMN approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
      ADD COLUMN rejection_reason TEXT DEFAULT NULL,
      ADD COLUMN approved_by INT DEFAULT NULL,
      ADD COLUMN approved_at DATETIME DEFAULT NULL";
if (mysqli_query($conn, $q)) {
    echo "finance_staff table altered successfully.\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
