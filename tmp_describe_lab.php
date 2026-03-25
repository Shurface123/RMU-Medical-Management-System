<?php
require_once 'php/db_conn.php';
$tables = ['lab_test_orders', 'lab_test_catalog', 'lab_results'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . ' ' . $row['Type'] . "\n";
    }
}
?>
