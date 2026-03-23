<?php
require_once '../db_conn.php';

$tables = ['staff', 'nurses', 'lab_technicians', 'users'];
$output = "";

foreach ($tables as $table) {
    $output .= "--- Table: $table ---\n";
    $result = mysqli_query($conn, "SHOW FULL COLUMNS FROM $table");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $output .= "Column: {$row['Field']} | Collation: {$row['Collation']}\n";
        }
        $output .= "\n";
    } else {
        $output .= "Error: " . mysqli_error($conn) . "\n\n";
    }
}

file_put_contents('collation_info.txt', $output);
echo "Output written to collation_info.txt\n";
