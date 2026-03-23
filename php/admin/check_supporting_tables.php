<?php
require_once '../db_conn.php';

$tables = ['wards', 'contamination_reports', 'kitchen_tasks'];
$output = "";

foreach ($tables as $table) {
    if (mysqli_query($conn, "DESCRIBE $table")) {
        $output .= "Table $table exists.\n";
    } else {
        $output .= "Table $table DOES NOT exist. Error: " . mysqli_error($conn) . "\n";
    }
}

file_put_contents('supporting_tables_info.txt', $output);
echo "Output written to supporting_tables_info.txt\n";
