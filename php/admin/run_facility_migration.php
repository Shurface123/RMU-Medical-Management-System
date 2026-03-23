<?php
require_once '../db_conn.php';

$sqlFile = '../../database/migrations/fix_facility_schemas.sql';
if (!file_exists($sqlFile)) die("SQL file not found.");

$sql = file_get_contents($sqlFile);
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
