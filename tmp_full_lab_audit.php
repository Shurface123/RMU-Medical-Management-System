<?php
require_once 'php/db_conn.php';
$tables = [
    'lab_results', 'lab_test_orders', 'lab_tests', 'lab_test_catalog', 
    'lab_samples', 'lab_audit_trail', 'lab_notifications', 'lab_equipment', 
    'reagent_inventory', 'lab_reference_ranges'
];
$fp = fopen('lab_full_schema_check.txt', 'w');
foreach ($tables as $table) {
    fwrite($fp, "--- $table ---\n");
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            fwrite($fp, sprintf("%-25s %-20s\n", $row['Field'], $row['Type']));
        }
    } else {
        fwrite($fp, "ERROR: " . $conn->error . "\n");
    }
    fwrite($fp, "\n");
}
fclose($fp);
echo "Full schema written to lab_full_schema_check.txt\n";
?>
