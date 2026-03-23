<?php
require_once '../db_conn.php';

$sql = file_get_contents('../../database/migrations/fix_collations.sql');
$queries = explode(';', $sql);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        echo "Executing: " . substr($query, 0, 50) . "...\n";
        if (mysqli_query($conn, $query)) {
            echo "Success.\n";
        } else {
            echo "Error: " . mysqli_error($conn) . "\n";
        }
    }
}
