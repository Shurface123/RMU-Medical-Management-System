<?php
session_start();
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ambulance_id   = mysqli_real_escape_string($conn, strtoupper(trim($_POST['ambulance_id'] ?? '')));
    $vehicle_number = mysqli_real_escape_string($conn, strtoupper(trim($_POST['vehicle_number'] ?? '')));
    $driver_name    = mysqli_real_escape_string($conn, trim($_POST['driver_name'] ?? ''));
    $driver_phone   = mysqli_real_escape_string($conn, trim($_POST['driver_phone'] ?? ''));
    $status         = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Available');
    $last_service   = mysqli_real_escape_string($conn, $_POST['last_service_date'] ?? '');
    $next_service   = mysqli_real_escape_string($conn, $_POST['next_service_date'] ?? '');
    $model          = mysqli_real_escape_string($conn, trim($_POST['vehicle_model'] ?? ''));

    if ($ambulance_id && $vehicle_number) {
        $ls_val    = $last_service ? "'$last_service'" : 'NULL';
        $ns_val    = $next_service ? "'$next_service'" : 'NULL';
        $model_val = $model ? "'$model'" : 'NULL';
        $sql = "INSERT INTO ambulances (ambulance_id, vehicle_number, driver_name, driver_phone, status, last_service_date, next_service_date, vehicle_model)
                VALUES ('$ambulance_id','$vehicle_number','$driver_name','$driver_phone','$status',$ls_val,$ns_val,$model_val)";
        if (mysqli_query($conn, $sql)) {
            header('Location: ambulence.php?success=Ambulance+registered+successfully');
            exit();
        }
    }
    header('Location: add-ambulence.php?error=Failed+to+register+ambulance');
    exit();
}
?>