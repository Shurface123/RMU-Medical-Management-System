<?php
require_once 'db_conn.php';
$sql = file_get_contents('migrate_broadcasts.sql');
if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($res = mysqli_store_result($conn)) {
            mysqli_free_result($res);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
    echo "Migration successful!\n";
} else {
    echo "Migration failed: " . mysqli_error($conn) . "\n";
}
?>
