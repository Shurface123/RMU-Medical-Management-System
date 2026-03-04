<?php
/* ═══════════════════════════════════════════════════════════
   CROSS-DASHBOARD NOTIFICATION HELPER
   Shared by all dashboard action handlers for unified
   cross-dashboard notifications via the `notifications` table
   ═══════════════════════════════════════════════════════════ */

/**
 * Insert a notification into the shared notifications table.
 * @param mysqli $conn  DB connection
 * @param int    $userId  Target user's users.id
 * @param string $role    Target user's role (doctor/nurse/patient/admin/pharmacist)
 * @param string $type    Notification type (vital_alert/emergency/task/medication/bed_transfer/handover/nursing_note/discharge/iv_alert/message/system/alert)
 * @param string $title   Short title
 * @param string $message Full message body
 * @param string $module  Related module name
 * @param int|null $relId Related record ID
 * @param string $priority Priority: low/normal/high/urgent/critical
 */
function crossNotify($conn, $userId, $role, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $userId  = (int)$userId;
    $role    = mysqli_real_escape_string($conn, $role);
    $type    = mysqli_real_escape_string($conn, $type);
    $title   = mysqli_real_escape_string($conn, substr($title, 0, 200));
    $message = mysqli_real_escape_string($conn, substr($message, 0, 2000));
    $module  = mysqli_real_escape_string($conn, $module);
    $priority= mysqli_real_escape_string($conn, $priority);
    $relSql  = $relId ? (int)$relId : 'NULL';

    mysqli_query($conn,
        "INSERT INTO notifications (user_id, user_role, type, title, message, is_read, priority, related_module, related_id, created_at)
         VALUES ($userId, '$role', '$type', '$title', '$message', 0, '$priority', '$module', $relSql, NOW())");
}

/* ── Broadcast helpers ─────────────────────────────────────── */

/** Notify ALL active doctors */
function notifyAllDoctors($conn, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $q = mysqli_query($conn, "SELECT u.id FROM users u WHERE u.user_role='doctor' AND u.is_active=1");
    while($q && $r = mysqli_fetch_assoc($q)){
        crossNotify($conn, $r['id'], 'doctor', $type, $title, $message, $module, $relId, $priority);
    }
}

/** Notify ALL active admins */
function notifyAllAdmins($conn, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $q = mysqli_query($conn, "SELECT u.id FROM users u WHERE u.user_role='admin' AND u.is_active=1");
    while($q && $r = mysqli_fetch_assoc($q)){
        crossNotify($conn, $r['id'], 'admin', $type, $title, $message, $module, $relId, $priority);
    }
}

/** Notify a specific doctor by doctors.id (PK) */
function notifyDoctor($conn, $doctorPk, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $doctorPk = (int)$doctorPk;
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM doctors WHERE id=$doctorPk LIMIT 1"));
    if($r) crossNotify($conn, $r['user_id'], 'doctor', $type, $title, $message, $module, $relId, $priority);
}

/** Notify a specific nurse by nurses.id (PK) */
function notifyNurse($conn, $nursePk, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $nursePk = (int)$nursePk;
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM nurses WHERE id=$nursePk LIMIT 1"));
    if($r) crossNotify($conn, $r['user_id'], 'nurse', $type, $title, $message, $module, $relId, $priority);
}

/** Notify a specific patient by patients.id (PK) */
function notifyPatient($conn, $patientPk, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $patientPk = (int)$patientPk;
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM patients WHERE id=$patientPk LIMIT 1"));
    if($r) crossNotify($conn, $r['user_id'], 'patient', $type, $title, $message, $module, $relId, $priority);
}

/* ── Name lookup helpers ───────────────────────────────────── */

function getNurseName($conn, $nursePk){
    $nursePk = (int)$nursePk;
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name FROM nurses WHERE id=$nursePk LIMIT 1"));
    return $r['full_name'] ?? 'Nurse';
}

function getDoctorName($conn, $doctorPk){
    $doctorPk = (int)$doctorPk;
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name FROM doctors WHERE id=$doctorPk LIMIT 1"));
    return $r['full_name'] ?? 'Doctor';
}

function getPatientNameById($conn, $patientPk){
    $patientPk = (int)$patientPk;
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT u.name FROM patients p JOIN users u ON p.user_id=u.id WHERE p.id=$patientPk LIMIT 1"));
    return $r['name'] ?? 'Patient';
}

/** Get the attending doctor PK for a patient (from active bed assignment) */
function getAttendingDoctorPk($conn, $patientPk){
    $patientPk = (int)$patientPk;
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT attending_doctor_id FROM bed_assignments WHERE patient_id=$patientPk AND status='Active' LIMIT 1"));
    return $r ? (int)$r['attending_doctor_id'] : 0;
}

/** Get nurse PK from user ID */
function getNursePkByUserId($conn, $userId){
    $userId = (int)$userId;
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM nurses WHERE user_id=$userId LIMIT 1"));
    return $r ? (int)$r['id'] : 0;
}
