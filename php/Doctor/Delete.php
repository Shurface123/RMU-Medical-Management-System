<?php
include_once 'db_conn.php';
$sql = "DELETE FROM doctors WHERE id='" . $_GET["D_ID"] . "'";
if (mysqli_query($conn, $sql)) {
    include 'doctor.php';
} else {
    echo "Error deleting record: " . mysqli_error($conn);
}
mysqli_close($conn);
?>