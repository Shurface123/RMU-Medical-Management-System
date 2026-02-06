<?php
require_once 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate all required fields
    $required_fields = ['patient_name', 'patient_email', 'patient_phone', 'patient_age', 
                        'patient_gender', 'patient_type', 'doctor_id', 'appointment_date', 
                        'appointment_time', 'appointment_type'];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            header("Location: booking.php?error=All required fields must be filled");
            exit();
        }
    }

    // Sanitize inputs
    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $patient_name = validate($_POST['patient_name']);
    $patient_email = validate($_POST['patient_email']);
    $patient_phone = validate($_POST['patient_phone']);
    $patient_age = intval($_POST['patient_age']);
    $patient_gender = validate($_POST['patient_gender']);
    $patient_type = validate($_POST['patient_type']);
    $doctor_id = intval($_POST['doctor_id']);
    $appointment_date = validate($_POST['appointment_date']);
    $appointment_time = validate($_POST['appointment_time']);
    $appointment_type = validate($_POST['appointment_type']);
    $symptoms = isset($_POST['symptoms']) ? validate($_POST['symptoms']) : '';

    // Validate email
    if (!filter_var($patient_email, FILTER_VALIDATE_EMAIL)) {
        header("Location: booking.php?error=Invalid email format");
        exit();
    }

    // Validate date is not in the past
    if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        header("Location: booking.php?error=Appointment date cannot be in the past");
        exit();
    }

    // Check if doctor exists
    $doctor_check = "SELECT D_ID FROM doctor WHERE D_ID = ?";
    $stmt = mysqli_prepare($conn, $doctor_check);
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        header("Location: booking.php?error=Selected doctor not found");
        exit();
    }
    mysqli_stmt_close($stmt);

    // Insert appointment into appointments table (if exists)
    $appointment_sql = "INSERT INTO appointments (patient_name, patient_email, patient_phone, 
                        patient_age, patient_gender, patient_type, doctor_id, appointment_date, 
                        appointment_time, appointment_type, symptoms, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
    
    $stmt = mysqli_prepare($conn, $appointment_sql);
    mysqli_stmt_bind_param($stmt, "sssisssssss", 
        $patient_name, $patient_email, $patient_phone, $patient_age, 
        $patient_gender, $patient_type, $doctor_id, $appointment_date, 
        $appointment_time, $appointment_type, $symptoms
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $appointment_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        // Redirect to success page
        header("Location: booking_success.php?id=" . $appointment_id);
        exit();
    } else {
        // If appointments table doesn't exist, try patient table (legacy)
        mysqli_stmt_close($stmt);
        
        $patient_sql = "INSERT INTO patient (P_Name, Gender, Age, P_Type, A_Date) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $patient_sql);
        mysqli_stmt_bind_param($stmt, "ssiss", 
            $patient_name, $patient_gender, $patient_age, $patient_type, $appointment_date
        );
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: book.php");
            exit();
        } else {
            mysqli_stmt_close($stmt);
            header("Location: booking.php?error=Failed to book appointment. Please try again");
            exit();
        }
    }

} else {
    header("Location: booking.php");
    exit();
}
?>
