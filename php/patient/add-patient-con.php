<?php
session_start();
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $email        = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $phone        = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $dob          = mysqli_real_escape_string($conn, $_POST['date_of_birth'] ?? '');
    $gender       = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
    $blood_group  = mysqli_real_escape_string($conn, $_POST['blood_group'] ?? '');
    $address      = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
    $patient_type = mysqli_real_escape_string($conn, $_POST['patient_type'] ?? 'Outpatient');
    $emg_name     = mysqli_real_escape_string($conn, trim($_POST['emergency_contact_name'] ?? ''));
    $emg_phone    = mysqli_real_escape_string($conn, trim($_POST['emergency_contact_phone'] ?? ''));
    $admit_date   = mysqli_real_escape_string($conn, $_POST['admit_date'] ?? date('Y-m-d'));

    if ($full_name && $gender) {
        $last_p = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients"))[0] ?? 0;
        $patient_id = 'PAT-' . str_pad($last_p + 1, 5, '0', STR_PAD_LEFT);

        $user_id = null;
        if ($email) {
            $chk = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
            if (mysqli_num_rows($chk) > 0) {
                $user_id = mysqli_fetch_assoc($chk)['id'];
            } else {
                $pass = password_hash('rmu@123', PASSWORD_DEFAULT);
                $sql_u = "INSERT INTO users (name, email, phone, gender, password, role, is_active)
                          VALUES ('$full_name','$email','$phone','$gender','$pass','patient',1)";
                if (mysqli_query($conn, $sql_u)) $user_id = mysqli_insert_id($conn);
            }
        }

        $age_val = 'NULL';
        if ($dob) $age_val = (int)((time() - strtotime($dob)) / (365.25 * 86400));
        $uid_val  = $user_id ? $user_id : 'NULL';
        $dob_val  = $dob        ? "'$dob'" : 'NULL';
        $emg_val  = $emg_name   ? "'$emg_name'" : 'NULL';
        $emgp_val = $emg_phone  ? "'$emg_phone'" : 'NULL';
        $bg_val   = $blood_group ? "'$blood_group'" : 'NULL';
        $addr_val = $address    ? "'$address'" : 'NULL';

        $sql = "INSERT INTO patients (user_id, patient_id, full_name, gender, age, date_of_birth, blood_group, address, patient_type, emergency_contact_name, emergency_contact_phone, admit_date)
                VALUES ($uid_val,'$patient_id','$full_name','$gender',$age_val,$dob_val,$bg_val,$addr_val,'$patient_type',$emg_val,$emgp_val,'$admit_date')";
        if (mysqli_query($conn, $sql)) {
            header('Location: patient.php?success=Patient+registered+successfully');
            exit();
        }
    }
    header('Location: add-patient.php?error=Failed+to+register+patient');
    exit();
}
?>