<?php
require_once 'php/db_conn.php';
$queries = [
    "ALTER TABLE kitchen_tasks ADD COLUMN patient_id INT NULL AFTER task_id",
    "ALTER TABLE kitchen_tasks ADD COLUMN patient_name VARCHAR(150) NULL AFTER patient_id",
    "ALTER TABLE kitchen_tasks ADD COLUMN bed_number VARCHAR(50) NULL AFTER ward_department",
    "ALTER TABLE kitchen_tasks ADD COLUMN priority VARCHAR(50) DEFAULT 'Routine' AFTER quantity",
    "ALTER TABLE kitchen_tasks ADD COLUMN ordered_by INT NULL AFTER assigned_to"
];

foreach($queries as $q) {
    if(mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . " on $q\n";
    }
}
?>
