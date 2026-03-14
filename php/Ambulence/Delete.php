<?php
include_once 'db_conn.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: ambulence.php?error=Invalid+ambulance+ID');
    exit;
}

$sql = "DELETE FROM ambulances WHERE id='$id'";
if (mysqli_query($conn, $sql)) {
    header('Location: ambulence.php?success=Ambulance+deleted+successfully');
}
else {
    header('Location: ambulence.php?error=Failed+to+delete+ambulance');
}
mysqli_close($conn);
exit;
?>