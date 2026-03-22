<?php
require_once 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';

$migrationFile = 'c:/wamp64/www/RMU-Medical-Management-System/database/migrations/phase7_lab_dashboard_schema.sql';
if (!file_exists($migrationFile)) {
    die("Migration file not found: $migrationFile\n");
}

$sql = file_get_contents($migrationFile);

// Split SQL by semicolons to handle potential issues with multi_query, 
// though mysqli_multi_query should work for simple scripts.
if (mysqli_multi_query($conn, $sql)) {
    do {
        // Store first result set
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
        // Print error if any
        if (mysqli_errno($conn)) {
            echo "Error: " . mysqli_error($conn) . "\n";
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
    echo "Phase 7 Migration completed successfully.\n";
} else {
    echo "Error executing migration: " . mysqli_error($conn) . "\n";
}
?>
