<?php
require_once 'php/db_conn.php';
$target_tables = ['lab_tests', 'lab_test_orders', 'lab_test_catalog', 'lab_results'];
foreach ($target_tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows > 0) {
        echo "EXISTS\n";
        $describe = $conn->query("DESCRIBE $table");
        while ($row = $describe->fetch_assoc()) {
            echo sprintf("%-20s %-20s\n", $row['Field'], $row['Type']);
        }
    } else {
        echo "NOT FOUND\n";
    }
    echo "\n";
}
?>
