<?php
require_once 'php/db_conn.php';
$tables = ['lab_test_orders', 'lab_test_catalog', 'lab_results'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if (!$res) {
        echo "Error: " . $conn->error . "\n";
        continue;
    }
    while ($row = $res->fetch_assoc()) {
        echo sprintf("%-20s %-20s\n", $row['Field'], $row['Type']);
    }
    echo "\n";
}
?>
