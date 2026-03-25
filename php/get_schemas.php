<?php
require_once 'db_conn.php';
$tables = ['staff', 'patients', 'wards', 'departments'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo $row['Field'] . " | " . $row['Type'] . "\n";
        }
    } else {
        echo "Table $table does not exist.\n";
    }
}
?>
