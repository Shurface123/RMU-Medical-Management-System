<?php
require_once 'php/db_conn.php';
$target_tables = ['lab_tests', 'lab_test_orders', 'lab_test_catalog', 'lab_results'];
$fp = fopen('lab_schema_utf8.txt', 'w');
foreach ($target_tables as $table) {
    fwrite($fp, "--- $table ---\n");
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows > 0) {
        fwrite($fp, "EXISTS\n");
        $describe = $conn->query("DESCRIBE $table");
        while ($row = $describe->fetch_assoc()) {
            fwrite($fp, sprintf("%-20s %-20s\n", $row['Field'], $row['Type']));
        }
    } else {
        fwrite($fp, "NOT FOUND\n");
    }
    fwrite($fp, "\n");
}
fclose($fp);
echo "Schema written to lab_schema_utf8.txt\n";
?>
