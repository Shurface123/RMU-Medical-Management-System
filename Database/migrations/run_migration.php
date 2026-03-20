<?php
require 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
$sql = file_get_contents('c:/wamp64/www/RMU-Medical-Management-System/Database/migrations/nurse_tables_migration.sql');
if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($res = mysqli_store_result($conn)) {
            mysqli_free_result($res);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
    echo "Migration Successful";
} else {
    echo "Migration Error: " . mysqli_error($conn);
}
