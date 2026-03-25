<?php
require_once 'php/db_conn.php';
$tables = ['lab_technician_settings', 'lab_technicians', 'lab_samples'];
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
file_put_contents('lab_schema_target.txt', $out);
?>
