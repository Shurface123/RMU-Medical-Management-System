<?php
/* ═══════════════════════════════════════════════════════════
   CROSS-DASHBOARD NOTIFICATION HELPER
   Shared by all dashboard action handlers for unified
   cross-dashboard notifications via the `notifications` table
   ALL queries use prepared statements — ZERO raw SQL
   ═══════════════════════════════════════════════════════════ */

/**
 * Insert a notification into the shared notifications table using prepared statements.
 */
function crossNotify($conn, $userId, $role, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $userId  = (int)$userId;
    $title   = substr($title, 0, 200);
    $message = substr($message, 0, 2000);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO notifications (user_id, user_role, type, title, message, is_read, priority, related_module, related_id, created_at)
         VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, NOW())");
    if(!$stmt) return;
    mysqli_stmt_bind_param($stmt, "issssssi", $userId, $role, $type, $title, $message, $priority, $module, $relId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/* ── Broadcast helpers ─────────────────────────────────────── */

/** Notify ALL active doctors */
function notifyAllDoctors($conn, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $q = mysqli_prepare($conn, "SELECT id FROM users WHERE user_role='doctor' AND is_active=1");
    if(!$q) return;
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);
    while($r = mysqli_fetch_assoc($result)){
        crossNotify($conn, $r['id'], 'doctor', $type, $title, $message, $module, $relId, $priority);
    }
    mysqli_stmt_close($q);
}

/** Notify ALL active admins */
function notifyAllAdmins($conn, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $q = mysqli_prepare($conn, "SELECT id FROM users WHERE user_role='admin' AND is_active=1");
    if(!$q) return;
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);
    while($r = mysqli_fetch_assoc($result)){
        crossNotify($conn, $r['id'], 'admin', $type, $title, $message, $module, $relId, $priority);
    }
    mysqli_stmt_close($q);
}

/** Notify a specific doctor by doctors.id (PK) */
function notifyDoctor($conn, $doctorPk, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $doctorPk = (int)$doctorPk;
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM doctors WHERE id=? LIMIT 1");
    if(!$stmt) return;
    mysqli_stmt_bind_param($stmt, "i", $doctorPk);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $r = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if($r) crossNotify($conn, $r['user_id'], 'doctor', $type, $title, $message, $module, $relId, $priority);
}



/** Notify a specific patient by patients.id (PK) */
function notifyPatient($conn, $patientPk, $type, $title, $message, $module='', $relId=null, $priority='normal'){
    $patientPk = (int)$patientPk;
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM patients WHERE id=? LIMIT 1");
    if(!$stmt) return;
    mysqli_stmt_bind_param($stmt, "i", $patientPk);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $r = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if($r) crossNotify($conn, $r['user_id'], 'patient', $type, $title, $message, $module, $relId, $priority);
}



/* ── Name lookup helpers (prepared statements) ─────────────── */



function getDoctorName($conn, $doctorPk){
    $doctorPk = (int)$doctorPk;
    $stmt = mysqli_prepare($conn, "SELECT full_name FROM doctors WHERE id=? LIMIT 1");
    if(!$stmt) return 'Doctor';
    mysqli_stmt_bind_param($stmt, "i", $doctorPk);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $r = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $r['full_name'] ?? 'Doctor';
}

function getPatientNameById($conn, $patientPk){
    $patientPk = (int)$patientPk;
    $stmt = mysqli_prepare($conn, "SELECT u.name FROM patients p JOIN users u ON p.user_id=u.id WHERE p.id=? LIMIT 1");
    if(!$stmt) return 'Patient';
    mysqli_stmt_bind_param($stmt, "i", $patientPk);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $r = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $r['name'] ?? 'Patient';
}

/** Get the attending doctor PK for a patient (from active bed assignment) */
function getAttendingDoctorPk($conn, $patientPk){
    $patientPk = (int)$patientPk;
    $stmt = mysqli_prepare($conn, "SELECT attending_doctor_id FROM bed_assignments WHERE patient_id=? AND status='Active' LIMIT 1");
    if(!$stmt) return 0;
    mysqli_stmt_bind_param($stmt, "i", $patientPk);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $r = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $r ? (int)$r['attending_doctor_id'] : 0;
}


