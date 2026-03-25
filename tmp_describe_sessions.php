<?php
require_once 'php/db_conn.php';
$table = 'lab_technician_sessions';
echo "--- $table ---\n";
$res = $conn->query("DESCRIBE $table");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' ' . $row['Type'] . "\n";
}
?>
