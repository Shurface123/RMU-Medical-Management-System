<?php
require_once '../db_conn.php';

$tables = ['patients', 'cleaning_schedules'];
$output = "";

foreach ($tables as $table) {
    $output .= "--- Table: $table ---\n";
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $output .= "Column: {$row['Field']} | Type: {$row['Type']}\n";
        }
        $output .= "\n";
    } else {
        $output .= "Error: " . mysqli_error($conn) . "\n\n";
    }
}

file_put_contents('facility_schema_info.txt', $output);
echo "Output written to facility_schema_info.txt\n";
