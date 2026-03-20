<?php
require 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
$q1 = "ALTER TABLE nurses ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'";
$q2 = "ALTER TABLE nurses ADD COLUMN rejection_reason TEXT NULL";

if(mysqli_query($conn, $q1)) echo "Col 1 added\n"; else echo mysqli_error($conn) . "\n";
if(mysqli_query($conn, $q2)) echo "Col 2 added\n"; else echo mysqli_error($conn) . "\n";
echo "Done";
