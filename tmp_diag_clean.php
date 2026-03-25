<?php
require_once 'php/db_conn.php';
$tables = ['lab_test_orders', 'lab_samples', 'lab_results', 'lab_technician_settings', 'lab_technicians'];
$out = "";
foreach($tables as $t) {
    $out .= "--- TABLE: $t ---\n";
    $res = mysqli_query($conn, "DESCRIBE $t");
    if($res) {
        while($row = mysqli_fetch_assoc($res)) {
            $out .= "{$row['Field']} - {$row['Type']}\n";
        }
    } else {
        $out .= "Error: Could not describe $t\n";
    }
    $out .= "\n";
}
file_put_contents('lab_schema_utf8.txt', $out);
echo "Done written to lab_schema_utf8.txt\n";
?>
