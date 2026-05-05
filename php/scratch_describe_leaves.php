<?php
include 'db_conn.php';
$table = 'staff_leaves';
$res = mysqli_query($conn, "DESCRIBE $table");
echo "Columns for $table:\n";
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
