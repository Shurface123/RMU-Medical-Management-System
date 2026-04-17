<?php
require_once 'php/db_conn.php';
date_default_timezone_set('Africa/Accra');

$doc_pk = 1;
$user_id = 1;
$today = date('Y-m-d');
$month_start = date('Y-m-01');

try {
    function qval($conn,$sql){$r=mysqli_query($conn,$sql);return $r?(mysqli_fetch_row($r)[0]??0):0;}
    
    mysqli_query($conn, "SELECT a.*, u.name AS patient_name, u.phone AS patient_phone, u.gender AS patient_gender,
            p.patient_id AS p_ref, p.blood_group, p.allergies
     FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE a.doctor_id=$doc_pk");
     
    mysqli_query($conn, "SELECT pr.*, u.name AS patient_name, p.patient_id AS p_ref
     FROM prescriptions pr JOIN patients p ON pr.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE pr.doctor_id=$doc_pk");
     
    mysqli_query($conn,"SELECT DISTINCT p.id, p.patient_id AS p_ref, p.blood_group, p.allergies,
            p.is_student, p.chronic_conditions, p.emergency_contact_name,
            u.name, u.email, u.phone, u.gender, u.date_of_birth
     FROM patients p JOIN users u ON p.user_id=u.id
     JOIN appointments a ON a.patient_id=p.id
     WHERE a.doctor_id=$doc_pk");
     
    mysqli_query($conn, "SELECT mr.*, u.name AS patient_name, p.patient_id AS p_ref
     FROM medical_records mr JOIN patients p ON mr.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE mr.doctor_id=$doc_pk");
     
    mysqli_query($conn, "SELECT u.id, u.name AS full_name, u.email, u.phone, 
            CASE 
                WHEN u.user_role='doctor' THEN 'Doctor'
                WHEN u.user_role='nurse' THEN 'Nurse'
                WHEN u.user_role='pharmacist' THEN 'Pharmacist'
                WHEN u.user_role='admin' THEN 'Admin'
                ELSE 'Staff'
            END AS role,
            s.department, s.staff_id, 'Active' AS status
     FROM users u 
     LEFT JOIN staff s ON u.id = s.user_id");

    mysqli_query($conn, "SELECT 'Appointment' AS type, a.status, u.name AS person, a.created_at AS ts,
            CONCAT('/RMU-Medical-Management-System/php/dashboards/doctor_dashboard.php?tab=appointments') AS link
     FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE a.doctor_id=$doc_pk
     UNION ALL
     SELECT 'Prescription', pr.status, u.name, pr.created_at, '#'
     FROM prescriptions pr JOIN patients p ON pr.patient_id=p.id JOIN users u ON p.user_id=u.id
     WHERE pr.doctor_id=$doc_pk");
     
     mysqli_query($conn, "SELECT diagnosis, COUNT(*) as cnt FROM medical_records WHERE doctor_id=$doc_pk AND visit_date>='$month_start' GROUP BY diagnosis");

    echo "All advanced queries successful.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
