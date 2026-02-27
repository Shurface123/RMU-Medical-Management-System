<?php
session_start();
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $email          = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $phone          = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $gender         = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
    $specialization = mysqli_real_escape_string($conn, trim($_POST['specialization'] ?? ''));
    $experience     = (int)($_POST['experience_years'] ?? 0);
    $avail_days     = mysqli_real_escape_string($conn, trim($_POST['available_days'] ?? ''));
    $schedule       = mysqli_real_escape_string($conn, trim($_POST['schedule_notes'] ?? ''));
    $is_available   = isset($_POST['is_available']) ? 1 : 0;

    if ($name && $email && $specialization) {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        $user_id = null;
        if (mysqli_num_rows($check) > 0) {
            $user_id = mysqli_fetch_assoc($check)['id'];
        } else {
            $default_pass = password_hash('rmu@123', PASSWORD_DEFAULT);
            $sql_user = "INSERT INTO users (name, email, phone, gender, password, role, is_active)
                         VALUES ('$name','$email','$phone','$gender','$default_pass','doctor',1)";
            if (mysqli_query($conn, $sql_user)) {
                $user_id = mysqli_insert_id($conn);
            }
        }

        if ($user_id) {
            $last_doc = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors"))[0] ?? 0;
            $doctor_id = 'DOC-' . str_pad($last_doc + 1, 4, '0', STR_PAD_LEFT);
            $sch_val = $schedule ? "'$schedule'" : 'NULL';
            $sql_doc = "INSERT INTO doctors (user_id, doctor_id, specialization, experience_years, available_days, schedule_notes, is_available)
                        VALUES ($user_id,'$doctor_id','$specialization',$experience,'$avail_days',$sch_val,$is_available)";
            if (mysqli_query($conn, $sql_doc)) {
                header('Location: doctor.php?success=Doctor+registered+successfully');
                exit();
            } else {
                mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");
            }
        }
    }
    header('Location: add-doctor.php?error=Failed+to+register+doctor');
    exit();
}
?>