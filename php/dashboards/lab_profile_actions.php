<?php
// ============================================================
// LAB PROFILE ACTIONS — AJAX Handler (Phase 9)
// ============================================================
require_once 'lab_security.php';
initSecureSession();
$user_id = enforceLabTechRole();

require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// CSRF Verification
$headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
$received_token = $_POST['csrf_token'] ?? $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
verifyCsrfToken($received_token);

$action   = $_POST['action'];
$response = ['success' => false, 'message' => 'Unknown action'];

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// --- Fetch the technician's primary key ---
$stmt_tk = $conn->prepare("SELECT id FROM lab_technicians WHERE user_id = ? LIMIT 1");
$stmt_tk->bind_param("i", $user_id);
$stmt_tk->execute();
$tk_res = $stmt_tk->get_result()->fetch_assoc();
$stmt_tk->close();
$tech_pk = (int)($tk_res['id'] ?? 0);

if ($tech_pk === 0) {
    echo json_encode(['success' => false, 'message' => 'Technician profile not found.']);
    exit;
}

// Helper: log to activity log
function logActivity($conn, $tech_pk, $description) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
    $stmt = $conn->prepare("INSERT INTO lab_technician_activity_log (technician_id, action_description, ip_address, device_info) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $tech_pk, $description, $ip, $ua);
    $stmt->execute();
    $stmt->close();
}

// Helper: recalculate and save profile completeness
function updateCompleteness($conn, $tech_pk, $user_id) {
    // Check each dimension
    $chk = function($conn, $q, $p) {
        $s = $conn->prepare($q);
        $s->bind_param("i", $p);
        $s->execute();
        return (bool)($s->get_result()->fetch_row()[0] ?? 0);
    };

    $personal  = $chk($conn, "SELECT COUNT(*) FROM lab_technicians WHERE id = ? AND full_name IS NOT NULL AND phone IS NOT NULL AND address IS NOT NULL", $tech_pk);
    $prof      = $chk($conn, "SELECT COUNT(*) FROM lab_technician_professional_profile WHERE technician_id = ? AND license_number IS NOT NULL", $tech_pk);
    $quals     = $chk($conn, "SELECT COUNT(*) FROM lab_technician_qualifications WHERE technician_id = ?", $tech_pk);
    $equip     = $chk($conn, "SELECT COUNT(*) FROM lab_equipment WHERE 1", $tech_pk); // Any equipment exists in system
    $photo_s   = $conn->prepare("SELECT profile_photo FROM lab_technicians WHERE id = ?");
    $photo_s->bind_param("i", $tech_pk);
    $photo_s->execute();
    $photo_row = $photo_s->get_result()->fetch_assoc();
    $photo_s->close();
    $photo     = !empty($photo_row['profile_photo']) && $photo_row['profile_photo'] !== 'default-avatar.png';
    $docs      = $chk($conn, "SELECT COUNT(*) FROM lab_technician_documents WHERE technician_id = ?", $tech_pk);
    $settings  = $chk($conn, "SELECT COUNT(*) FROM lab_technician_settings WHERE technician_id = ?", $tech_pk);
    $shift     = true; // Shift data assumed if they are in the system

    $booleans  = [$personal, $prof, $quals, $equip, true, $photo, $settings, $docs];
    $pct       = (int)round((count(array_filter($booleans)) / count($booleans)) * 100);

    $pi = (int)$personal; $pp = (int)$prof; $qu = (int)$quals; $eq = (int)$equip;
    $ph = (int)$photo; $se = (int)$settings; $do = (int)$docs;

    $stmt = $conn->prepare("INSERT INTO lab_technician_profile_completeness
        (technician_id, personal_info_complete, professional_profile_complete, qualifications_complete,
         equipment_assigned, shift_profile_complete, photo_uploaded, security_setup_complete,
         documents_uploaded, overall_percentage)
        VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
         personal_info_complete=VALUES(personal_info_complete),
         professional_profile_complete=VALUES(professional_profile_complete),
         qualifications_complete=VALUES(qualifications_complete),
         equipment_assigned=VALUES(equipment_assigned),
         photo_uploaded=VALUES(photo_uploaded),
         security_setup_complete=VALUES(security_setup_complete),
         documents_uploaded=VALUES(documents_uploaded),
         overall_percentage=VALUES(overall_percentage)");
    $stmt->bind_param("iiiiiiiii", $tech_pk, $pi, $pp, $qu, $eq, $ph, $se, $do, $pct);
    $stmt->execute();
    $stmt->close();
    return $pct;
}

// ==============================================================
// ACTION: SAVE PERSONAL INFORMATION
// ==============================================================
if ($action === 'save_personal_info') {
    $full_name    = trim($_POST['full_name'] ?? '');
    $dob          = $_POST['dob'] ?? null;
    $gender       = $_POST['gender'] ?? '';
    $nationality  = trim($_POST['nationality'] ?? '');
    $marital      = $_POST['marital_status'] ?? '';
    $religion     = trim($_POST['religion'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $phone2       = trim($_POST['phone2'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $city         = trim($_POST['city'] ?? '');
    $region       = trim($_POST['region'] ?? '');
    $country      = trim($_POST['country'] ?? '');
    $postal_code  = trim($_POST['postal_code'] ?? '');

    if (empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'Full name is required.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE lab_technicians SET full_name=?, date_of_birth=?, gender=?, nationality=?, phone=?, address=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("ssssssi", $full_name, $dob, $gender, $nationality, $phone, $address, $tech_pk);
    $stmt->execute();
    $stmt->close();

    // Update phone on users table too
    $stmt2 = $conn->prepare("UPDATE users SET phone=? WHERE id=?");
    $stmt2->bind_param("si", $phone, $user_id);
    $stmt2->execute();
    $stmt2->close();

    logActivity($conn, $tech_pk, "Updated personal information");
    $pct = updateCompleteness($conn, $tech_pk, $user_id);

    $response = ['success' => true, 'message' => 'Personal information saved successfully.', 'completeness' => $pct];
}

// ==============================================================
// ACTION: SAVE PROFESSIONAL PROFILE
// ==============================================================
if ($action === 'save_professional_profile') {
    $spec       = trim($_POST['specialization'] ?? '');
    $sub_spec   = trim($_POST['sub_specialization'] ?? '');
    $dept_id    = (int)($_POST['department_id'] ?? 0);
    $desig      = trim($_POST['designation'] ?? '');
    $yoe        = (int)($_POST['years_of_experience'] ?? 0);
    $lic_num    = trim($_POST['license_number'] ?? '');
    $lic_body   = trim($_POST['license_issuing_body'] ?? '');
    $lic_exp    = $_POST['license_expiry_date'] ?: null;
    $inst       = trim($_POST['institution_attended'] ?? '');
    $grad_year  = (int)($_POST['graduation_year'] ?? 0);
    $pg_details = trim($_POST['postgraduate_details'] ?? '');
    $languages  = json_encode(array_filter(explode(',', $_POST['languages_spoken'] ?? '')));
    $bio        = trim($_POST['bio'] ?? '');

    $stmt = $conn->prepare("INSERT INTO lab_technician_professional_profile
        (technician_id, specialization, sub_specialization, department_id, designation, years_of_experience,
         license_number, license_issuing_body, license_expiry_date, institution_attended, graduation_year,
         postgraduate_details, languages_spoken, bio)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
         specialization=VALUES(specialization), sub_specialization=VALUES(sub_specialization),
         department_id=VALUES(department_id), designation=VALUES(designation),
         years_of_experience=VALUES(years_of_experience), license_number=VALUES(license_number),
         license_issuing_body=VALUES(license_issuing_body), license_expiry_date=VALUES(license_expiry_date),
         institution_attended=VALUES(institution_attended), graduation_year=VALUES(graduation_year),
         postgraduate_details=VALUES(postgraduate_details), languages_spoken=VALUES(languages_spoken), bio=VALUES(bio)");
    $stmt->bind_param("issiissssissss", $tech_pk, $spec, $sub_spec, $dept_id, $desig, $yoe, $lic_num, $lic_body, $lic_exp, $inst, $grad_year, $pg_details, $languages, $bio);
    $stmt->execute();
    $stmt->close();

    // Also update designation and specialization on lab_technicians
    $stmt2 = $conn->prepare("UPDATE lab_technicians SET designation=?, specialization=?, years_of_experience=?, license_number=?, license_expiry=? WHERE id=?");
    $stmt2->bind_param("ssissi", $desig, $spec, $yoe, $lic_num, $lic_exp, $tech_pk);
    $stmt2->execute();
    $stmt2->close();

    // Check license expiry and notify if within 60 days
    if ($lic_exp) {
        $days_until = (strtotime($lic_exp) - time()) / 86400;
        if ($days_until <= 60 && $days_until > 0) {
            $msg = "Lab License Expiry Warning: Technician ID #{$tech_pk} license expires on {$lic_exp} ({$days_until} days).";
            $notif_stmt = $conn->prepare("INSERT IGNORE INTO notifications (user_id, user_role, type, title, message, is_read, related_module, created_at) VALUES (?, 'lab_tech', 'license_expiry', 'License Expiring Soon', ?, 0, 'Profile', NOW())");
            $notif_stmt->bind_param("is", $user_id, $msg);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
    }

    logActivity($conn, $tech_pk, "Updated professional profile");
    $pct = updateCompleteness($conn, $tech_pk, $user_id);

    $response = ['success' => true, 'message' => 'Professional profile saved successfully.', 'completeness' => $pct];
}

// ==============================================================
// ACTION: UPLOAD QUALIFICATION
// ==============================================================
if ($action === 'upload_qualification') {
    $degree  = trim($_POST['degree_name'] ?? '');
    $inst    = trim($_POST['institution_name'] ?? '');
    $year    = (int)($_POST['year_awarded'] ?? 0);
    $filepath = null;

    if (!empty($_FILES['certificate']['tmp_name'])) {
        $allowed   = ['application/pdf', 'image/jpeg', 'image/png'];
        $ftype     = mime_content_type($_FILES['certificate']['tmp_name']);
        $fsize     = $_FILES['certificate']['size'];

        if (!in_array($ftype, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Only PDF, JPG, PNG allowed.']); exit;
        }
        if ($fsize > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Max file size is 5MB.']); exit;
        }

        $ext      = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
        $safe_name = 'qual_' . $tech_pk . '_' . time() . '.' . $ext;
        $upload_dir = dirname(__DIR__, 2) . '/uploads/qualifications/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        move_uploaded_file($_FILES['certificate']['tmp_name'], $upload_dir . $safe_name);
        $filepath = 'qualifications/' . $safe_name;
    }

    $stmt = $conn->prepare("INSERT INTO lab_technician_qualifications (technician_id, degree_name, institution_name, year_awarded, certificate_file_path) VALUES (?,?,?,?,?)");
    $stmt->bind_param("issis", $tech_pk, $degree, $inst, $year, $filepath);
    $stmt->execute();
    $stmt->close();

    logActivity($conn, $tech_pk, "Added qualification: {$degree}");
    $pct = updateCompleteness($conn, $tech_pk, $user_id);
    $response = ['success' => true, 'message' => 'Qualification added.', 'completeness' => $pct];
}

// ==============================================================
// ACTION: DELETE QUALIFICATION
// ==============================================================
if ($action === 'delete_qualification') {
    $qual_id = (int)($_POST['qual_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM lab_technician_qualifications WHERE id=? AND technician_id=?");
    $stmt->bind_param("ii", $qual_id, $tech_pk);
    $stmt->execute();
    $stmt->close();
    logActivity($conn, $tech_pk, "Deleted qualification ID #{$qual_id}");
    $response = ['success' => true, 'message' => 'Qualification removed.'];
}

// ==============================================================
// ACTION: UPLOAD CERTIFICATION
// ==============================================================
if ($action === 'upload_certification') {
    $cert_name = trim($_POST['certification_name'] ?? '');
    $org       = trim($_POST['issuing_organization'] ?? '');
    $issue_dt  = $_POST['issue_date'] ?: null;
    $expiry_dt = $_POST['expiry_date'] ?: null;
    $filepath  = null;

    if (!empty($_FILES['certificate']['tmp_name'])) {
        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        $ftype   = mime_content_type($_FILES['certificate']['tmp_name']);
        $fsize   = $_FILES['certificate']['size'];
        if (!in_array($ftype, $allowed)) { echo json_encode(['success'=>false,'message'=>'Only PDF, JPG, PNG allowed.']); exit; }
        if ($fsize > 5*1024*1024) { echo json_encode(['success'=>false,'message'=>'Max 5MB.']); exit; }
        $ext      = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
        $safe_name = 'cert_'.$tech_pk.'_'.time().'.'.$ext;
        $dir      = dirname(__DIR__,2).'/uploads/certifications/';
        if(!is_dir($dir)) mkdir($dir, 0755, true);
        move_uploaded_file($_FILES['certificate']['tmp_name'], $dir.$safe_name);
        $filepath = 'certifications/'.$safe_name;
    }

    $stmt = $conn->prepare("INSERT INTO lab_technician_certifications (technician_id, certification_name, issuing_organization, issue_date, expiry_date, certificate_file_path) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss", $tech_pk, $cert_name, $org, $issue_dt, $expiry_dt, $filepath);
    $stmt->execute();
    $stmt->close();

    logActivity($conn, $tech_pk, "Added certification: {$cert_name}");
    $response = ['success' => true, 'message' => 'Certification added.'];
}

// ==============================================================
// ACTION: DELETE CERTIFICATION
// ==============================================================
if ($action === 'delete_certification') {
    $cert_id = (int)($_POST['cert_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM lab_technician_certifications WHERE id=? AND technician_id=?");
    $stmt->bind_param("ii", $cert_id, $tech_pk);
    $stmt->execute();
    $stmt->close();
    $response = ['success' => true, 'message' => 'Certification removed.'];
}

// ==============================================================
// ACTION: UPLOAD DOCUMENT
// ==============================================================
if ($action === 'upload_document') {
    $desc = trim($_POST['description'] ?? '');
    if (empty($_FILES['document']['tmp_name'])) {
        echo json_encode(['success'=>false,'message'=>'No file uploaded.']); exit;
    }
    $allowed = ['application/pdf','image/jpeg','image/png','application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $ftype  = mime_content_type($_FILES['document']['tmp_name']);
    $fsize  = $_FILES['document']['size'];
    $fname  = basename($_FILES['document']['name']);
    if (!in_array($ftype, $allowed)) { echo json_encode(['success'=>false,'message'=>'File type not allowed.']); exit; }
    if ($fsize > 10 * 1024 * 1024) { echo json_encode(['success'=>false,'message'=>'Max 10MB.']); exit; }

    $ext       = pathinfo($fname, PATHINFO_EXTENSION);
    $safe_name = 'doc_'.$tech_pk.'_'.time().'.'.$ext;
    $dir       = dirname(__DIR__,2).'/uploads/tech_docs/';
    if(!is_dir($dir)) mkdir($dir, 0755, true);
    move_uploaded_file($_FILES['document']['tmp_name'], $dir.$safe_name);
    $filepath = 'tech_docs/'.$safe_name;

    $stmt = $conn->prepare("INSERT INTO lab_technician_documents (technician_id, file_name, file_path, file_type, file_size, description) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss", $tech_pk, $fname, $filepath, $ftype, $fsize, $desc);
    $stmt->execute();
    $stmt->close();

    logActivity($conn, $tech_pk, "Uploaded document: {$fname}");
    $pct = updateCompleteness($conn, $tech_pk, $user_id);
    $response = ['success' => true, 'message' => 'Document uploaded successfully.', 'completeness' => $pct];
}

// ==============================================================
// ACTION: DELETE DOCUMENT
// ==============================================================
if ($action === 'delete_document') {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    $stmt = $conn->prepare("SELECT file_path FROM lab_technician_documents WHERE id=? AND technician_id=?");
    $stmt->bind_param("ii", $doc_id, $tech_pk);
    $stmt->execute();
    $doc_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($doc_row) {
        $full_path = dirname(__DIR__,2).'/uploads/'.$doc_row['file_path'];
        if (file_exists($full_path)) @unlink($full_path);
        $stmt2 = $conn->prepare("DELETE FROM lab_technician_documents WHERE id=? AND technician_id=?");
        $stmt2->bind_param("ii", $doc_id, $tech_pk);
        $stmt2->execute();
        $stmt2->close();
        logActivity($conn, $tech_pk, "Deleted document ID #{$doc_id}");
    }
    $response = ['success' => true, 'message' => 'Document deleted.'];
}

// ==============================================================
// ACTION: UPLOAD PROFILE PHOTO
// ==============================================================
if ($action === 'upload_profile_photo') {
    if (empty($_FILES['photo']['tmp_name'])) {
        echo json_encode(['success'=>false,'message'=>'No photo uploaded.']); exit;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $ftype   = mime_content_type($_FILES['photo']['tmp_name']);
    $fsize   = $_FILES['photo']['size'];
    if (!in_array($ftype, $allowed)) { echo json_encode(['success'=>false,'message'=>'Only JPG, PNG, WebP allowed.']); exit; }
    if ($fsize > 2 * 1024 * 1024) { echo json_encode(['success'=>false,'message'=>'Max photo size is 2MB.']); exit; }

    $ext       = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $safe_name = 'prof_'.$tech_pk.'_'.time().'.'.$ext;
    $dir       = dirname(__DIR__,2).'/uploads/profiles/';
    if(!is_dir($dir)) mkdir($dir, 0755, true);
    move_uploaded_file($_FILES['photo']['tmp_name'], $dir.$safe_name);

    $stmt = $conn->prepare("UPDATE lab_technicians SET profile_photo=? WHERE id=?");
    $stmt->bind_param("si", $safe_name, $tech_pk);
    $stmt->execute();
    $stmt->close();

    logActivity($conn, $tech_pk, "Updated profile photo");
    $pct = updateCompleteness($conn, $tech_pk, $user_id);
    $response = ['success' => true, 'message' => 'Profile photo updated.', 'new_photo' => $safe_name, 'completeness' => $pct];
}

// ==============================================================
// ACTION: TOGGLE AVAILABILITY STATUS
// ==============================================================
if ($action === 'toggle_availability') {
    $status = $_POST['status'] ?? 'Available';
    $allowed_statuses = ['Available', 'Busy', 'On Break', 'Off Duty'];
    if (!in_array($status, $allowed_statuses)) {
        echo json_encode(['success'=>false,'message'=>'Invalid status.']); exit;
    }
    $stmt = $conn->prepare("UPDATE lab_technicians SET availability_status=? WHERE id=?");
    $stmt->bind_param("si", $status, $tech_pk);
    $stmt->execute();
    $stmt->close();
    logActivity($conn, $tech_pk, "Changed availability to: {$status}");
    $response = ['success' => true, 'message' => "Status set to {$status}.", 'status' => $status];
}

// ==============================================================
// ACTION: SAVE NOTIFICATION SETTINGS
// ==============================================================
if ($action === 'save_notification_settings') {
    $fields = ['notif_new_order','notif_urgent_order','notif_critical_value','notif_equipment_due',
               'notif_reagent_low','notif_reagent_expiry','notif_result_amendment','notif_doctor_message',
               'notif_qc_failure','notif_license_expiry','notif_shift_reminder','notif_system_announcements',
               'alert_sound_enabled','email_notifications','sms_notifications'];

    $vals = [];
    foreach($fields as $f) { $vals[$f] = isset($_POST[$f]) && $_POST[$f] == '1' ? 1 : 0; }
    $lang    = strip_tags($_POST['preferred_language'] ?? 'English');
    $channel = strip_tags($_POST['preferred_channel'] ?? 'dashboard');

    $stmt = $conn->prepare("INSERT INTO lab_technician_settings
        (technician_id, notif_new_order, notif_urgent_order, notif_critical_value, notif_equipment_due,
         notif_reagent_low, notif_reagent_expiry, notif_result_amendment, notif_doctor_message,
         notif_qc_failure, notif_license_expiry, notif_shift_reminder, notif_system_announcements,
         alert_sound_enabled, email_notifications, sms_notifications, preferred_language, preferred_channel)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
         notif_new_order=VALUES(notif_new_order), notif_urgent_order=VALUES(notif_urgent_order),
         notif_critical_value=VALUES(notif_critical_value), notif_equipment_due=VALUES(notif_equipment_due),
         notif_reagent_low=VALUES(notif_reagent_low), notif_reagent_expiry=VALUES(notif_reagent_expiry),
         notif_result_amendment=VALUES(notif_result_amendment), notif_doctor_message=VALUES(notif_doctor_message),
         notif_qc_failure=VALUES(notif_qc_failure), notif_license_expiry=VALUES(notif_license_expiry),
         notif_shift_reminder=VALUES(notif_shift_reminder), notif_system_announcements=VALUES(notif_system_announcements),
         alert_sound_enabled=VALUES(alert_sound_enabled), email_notifications=VALUES(email_notifications),
         sms_notifications=VALUES(sms_notifications), preferred_language=VALUES(preferred_language),
         preferred_channel=VALUES(preferred_channel)");

    $stmt->bind_param("iiiiiiiiiiiiiiiss",
        $tech_pk, $vals['notif_new_order'], $vals['notif_urgent_order'], $vals['notif_critical_value'],
        $vals['notif_equipment_due'], $vals['notif_reagent_low'], $vals['notif_reagent_expiry'],
        $vals['notif_result_amendment'], $vals['notif_doctor_message'], $vals['notif_qc_failure'],
        $vals['notif_license_expiry'], $vals['notif_shift_reminder'], $vals['notif_system_announcements'],
        $vals['alert_sound_enabled'], $vals['email_notifications'], $vals['sms_notifications'],
        $lang, $channel
    );
    $stmt->execute();
    $stmt->close();

    logActivity($conn, $tech_pk, "Updated notification preferences");
    $pct = updateCompleteness($conn, $tech_pk, $user_id);
    $response = ['success' => true, 'message' => 'Notification preferences saved.', 'completeness' => $pct];
}

// ==============================================================
// ACTION: CHANGE PASSWORD
// ==============================================================
if ($action === 'change_password') {
    $cur_pass  = $_POST['current_password'] ?? '';
    $new_pass  = $_POST['new_password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    if (strlen($new_pass) < 8) {
        echo json_encode(['success'=>false,'message'=>'New password must be at least 8 characters.']); exit;
    }
    if ($new_pass !== $conf_pass) {
        echo json_encode(['success'=>false,'message'=>'New passwords do not match.']); exit;
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $usr = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usr || !password_verify($cur_pass, $usr['password'])) {
        echo json_encode(['success'=>false,'message'=>'Current password is incorrect.']); exit;
    }

    $hash = password_hash($new_pass, PASSWORD_BCRYPT);
    $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt2->bind_param("si", $hash, $user_id);
    $stmt2->execute();
    $stmt2->close();

    logActivity($conn, $tech_pk, "Changed account password");
    $response = ['success' => true, 'message' => 'Password changed successfully. Please log in again.'];
}

// ==============================================================
// ACTION: LOGOUT SPECIFIC SESSION
// ==============================================================
if ($action === 'logout_session') {
    $sess_id = (int)($_POST['session_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM lab_technician_sessions WHERE id=? AND technician_id=?");
    $stmt->bind_param("ii", $sess_id, $tech_pk);
    $stmt->execute();
    $stmt->close();
    $response = ['success' => true, 'message' => 'Session terminated.'];
}

// ==============================================================
// ACTION: FETCH PERFORMANCE STATS (Mini charts on overview)
// ==============================================================
if ($action === 'fetch_performance_stats') {
    $stats = [];

    // All-time orders
    $s = $conn->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=?");
    $s->bind_param("i", $user_id); $s->execute();
    $stats['orders_total'] = (int)$s->get_result()->fetch_row()[0]; $s->close();

    // This month
    $s = $conn->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=? AND MONTH(created_at)=MONTH(NOW())");
    $s->bind_param("i", $user_id); $s->execute();
    $stats['orders_month'] = (int)$s->get_result()->fetch_row()[0]; $s->close();

    // Results validated
    $s = $conn->prepare("SELECT COUNT(*) FROM lab_results WHERE technician_id=?");
    $s->bind_param("i", $user_id); $s->execute();
    $stats['results_total'] = (int)$s->get_result()->fetch_row()[0]; $s->close();

    // Critical flagged
    $s = $conn->prepare("SELECT COUNT(*) FROM lab_results WHERE technician_id=? AND result_interpretation='Critical'");
    $s->bind_param("i", $user_id); $s->execute();
    $stats['critical_total'] = (int)$s->get_result()->fetch_row()[0]; $s->close();

    // Avg TAT
    $s = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR, o.created_at, r.validated_at)) FROM lab_results r JOIN lab_test_orders o ON r.order_id=o.id WHERE r.technician_id=? AND r.validated_at IS NOT NULL");
    $s->bind_param("i", $user_id); $s->execute();
    $stats['avg_tat'] = round((float)$s->get_result()->fetch_row()[0], 1); $s->close();

    // Last 7 days volume
    $vol_labels = []; $vol_values = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $lbl  = date('M d', strtotime("-{$i} days"));
        $sv = $conn->prepare("SELECT COUNT(*) FROM lab_results WHERE technician_id=? AND DATE(created_at)=?");
        $sv->bind_param("is", $user_id, $date); $sv->execute();
        $vol_labels[] = $lbl;
        $vol_values[] = (int)$sv->get_result()->fetch_row()[0];
        $sv->close();
    }
    $stats['vol_labels'] = $vol_labels;
    $stats['vol_values'] = $vol_values;

    $response = ['success' => true, 'stats' => $stats];
}

echo json_encode($response);
exit;
