<?php
require_once dirname(__DIR__, 2) . '/db_conn.php';

$sql = file_get_contents(__DIR__ . '/admin_profile_schema.sql');
if (!$sql) {
    die("Failed to read SQL file.");
}

$queries = explode(';', $sql);
$success = 0;
$errors = 0;

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (mysqli_query($conn, $query)) {
            $success++;
        } else {
            echo "Error executing query: " . mysqli_error($conn) . "\n";
            $errors++;
        }
    }
}

echo "Migration complete. Success: $success, Errors: $errors\n";
