<?php
require_once 'php/db_conn.php';
$sql = file_get_contents('database/migrations/fix_lab_sessions_schema.sql');
if ($conn->multi_query($sql)) {
    do {
        if ($res = $conn->store_result()) { $res->free(); }
    } while ($conn->next_result());
    echo "Migration successful.\n";
} else {
    echo "Migration failed: " . $conn->error . "\n";
}
?>
