<?php
$conn = mysqli_connect('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$tables = [];
$res = mysqli_query($conn, "SHOW TABLES");
while($row = mysqli_fetch_row($res)) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    $cres = mysqli_query($conn, "DESCRIBE `$table` ");
    while($crow = mysqli_fetch_assoc($cres)) {
        echo "{$crow['Field']} ({$crow['Type']})\n";
    }
    echo "\n";
}
?>
