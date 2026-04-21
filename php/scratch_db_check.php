<?php
require_once __DIR__ . '/db_conn.php';

echo "--- Tables ---\n";
$res = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($res)) {
    echo $row[0] . "\n";
}

echo "\n--- staff_notifications check ---\n";
$res = mysqli_query($conn, "DESC staff_notifications");
if (!$res) echo "staff_notifications table missing!\n";

echo "\n--- staff_messages check ---\n";
$res = mysqli_query($conn, "DESC staff_messages");
if (!$res) echo "staff_messages table missing!\n";

echo "\n--- staff_tasks check ---\n";
$res = mysqli_query($conn, "DESC staff_tasks");
if (!$res) echo "staff_tasks table missing!\n";

echo "\n--- maintenance_requests check ---\n";
$res = mysqli_query($conn, "DESC maintenance_requests");
if (!$res) echo "maintenance_requests table missing!\n";
?>
