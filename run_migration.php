<?php
require_once 'php/db_conn.php';

$sql = file_get_contents('Database/migrations/phase6_security.sql');
$queries = explode(';', $sql);
$success = 0;
$failed = 0;

foreach ($queries as $query) {
    if (trim($query)) {
        if (mysqli_query($conn, $query)) {
            $success++;
        } else {
            echo "Failed: " . mysqli_error($conn) . "\n";
            $failed++;
        }
    }
}
echo "Migration complete. Success: $success, Failed: $failed\n";
?>
