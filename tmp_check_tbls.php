<?php
require 'c:\wamp64\www\RMU-Medical-Management-System\php\db_conn.php';
function check_tbl($conn, $tbl) {
    try {
        $res = mysqli_query($conn, "DESCRIBE `$tbl`");
        echo "$tbl: " . ($res ? "Exists\n" : "Missing\n");
    } catch(Exception $e) {
        echo "$tbl: Exception " . $e->getMessage() . "\n";
    }
}
check_tbl($conn, "beds");
check_tbl($conn, "bed_management");
check_tbl($conn, "medicines");
check_tbl($conn, "medicine_inventory");
