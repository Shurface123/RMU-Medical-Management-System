<?php
$conn = mysqli_connect('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if (!$conn) die('Connection failed');

$sql = file_get_contents('c:/wamp64/www/RMU-Medical-Management-System/database/migrations/landing_page_tables.sql');

// Split by semicolon but be careful of content inside strings (this is a simple parser)
// A better way is using mysqli_multi_query
if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($res = mysqli_store_result($conn)) {
            mysqli_free_result($res);
        }
    } while (mysqli_next_result($conn));
    echo "Migration completed successfully!\n";
} else {
    echo "Migration failed: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
