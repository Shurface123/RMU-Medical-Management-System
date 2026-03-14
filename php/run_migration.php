<?php
require_once 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
$sql = file_get_contents('c:/wamp64/www/RMU-Medical-Management-System/Database/migrations/phase5_staff_dashboard.sql');
if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
    echo "Migration completed successfully.\n";
} else {
    echo "Error executing migration: " . mysqli_error($conn) . "\n";
}
?>
