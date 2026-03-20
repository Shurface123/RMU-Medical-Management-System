<?php
require 'php/db_conn.php';
ob_start();

$r = mysqli_query($conn, "SHOW FULL TABLES");
echo "Database Tables/Views:\n";
while($row = mysqli_fetch_array($r)) {
    echo "{$row[0]} ({$row[1]})\n";
}

$r = mysqli_query($conn, "SELECT COUNT(*) FROM staff_directory");
if ($r) {
    echo "\n'staff_directory' COUNT: " . mysqli_fetch_row($r)[0] . "\n";
} else {
    echo "\n'staff_directory' does NOT exist or query failed: " . mysqli_error($conn) . "\n";
}

$output = ob_get_clean();
file_put_contents('debug_results_v2.txt', $output);
echo "Results written to debug_results_v2.txt\n";
?>
