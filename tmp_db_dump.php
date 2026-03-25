<?php
require_once 'php/db_conn.php';
$output = "";
$res = mysqli_query($conn, "SHOW TABLES");
if (!$res) {
    die("Error showing tables: " . mysqli_error($conn));
}
while($row = mysqli_fetch_row($res)) {
    $output .= $row[0] . "\n";
    $cols = mysqli_query($conn, "DESCRIBE " . $row[0]);
    while($c = mysqli_fetch_assoc($cols)) {
        $output .= "  - " . $c['Field'] . " (" . $c['Type'] . ")\n";
    }
}
file_put_contents('db_schema_dump.txt', $output);
echo "Dumped to db_schema_dump.txt in UTF-8\n";
?>
