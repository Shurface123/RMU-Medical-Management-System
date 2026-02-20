<?php
include_once 'db_conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $doctor_name = mysqli_real_escape_string($conn, $_POST['D_Name']);
    $gender = mysqli_real_escape_string($conn, $_POST['Gender']);
    $work_day = mysqli_real_escape_string($conn, $_POST['Work_Day']);
    $speciality = mysqli_real_escape_string($conn, $_POST['Speciality']);
    
    // Note: You'll need to handle user_id and doctor_id separately
    // This assumes you have a user creation process that provides these IDs
    // For now, this is a placeholder - you'll need to adjust based on your user creation flow
    
    $sql = "INSERT INTO doctors (full_name, gender, available_days, specialization) 
            VALUES ('$doctor_name', '$gender', '$work_day', '$speciality')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: doctor.php?success=Doctor added successfully");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
    
    mysqli_close($conn);
}
?>