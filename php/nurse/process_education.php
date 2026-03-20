<?php
// ============================================================
// PROCESS PATIENT EDUCATION & DISCHARGE (AJAX Endpoint)
// ============================================================
require_once '../dashboards/nurse_security.php';
initSecureSession();
$nurse_id = enforceNurseRole();
require_once '../db_conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}

verifyCsrfToken($_POST['_csrf'] ?? '');
$action = sanitize($_POST['action'] ?? '');

$nurse_pk = dbVal($conn, "SELECT id FROM nurses WHERE user_id=$nurse_id LIMIT 1", "i", []);

if ($action === 'log_education') {
    $patient_id    = validateInt($_POST['patient_id'] ?? 0);
    $method        = sanitize($_POST['method'] ?? 'Verbal');
    $topic         = sanitize($_POST['topic'] ?? '');
    $understanding = sanitize($_POST['understanding'] ?? 'Good');
    $needs_follow  = isset($_POST['needs_followup']) ? 1 : 0;
    $notes         = sanitize($_POST['notes'] ?? '');

    if (!$patient_id || empty($topic) || empty($notes)) {
        echo json_encode(['success' => false, 'message' => 'Patient, Topic, and Notes are required.']);
        exit;
    }

    $edu_id = 'EDU-' . strtoupper(uniqid());

    $stmt = mysqli_prepare($conn, "
        INSERT INTO patient_education (education_id, patient_id, nurse_id, education_topic, method, understanding_level, requires_follow_up, follow_up_notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "siisssis", $edu_id, $patient_id, $nurse_pk, $topic, $method, $understanding, $needs_follow, $notes);

    if (mysqli_stmt_execute($stmt)) {
        secureLogNurse($conn, $nurse_pk, "Logged health education ($topic) for Patient PK $patient_id", "education");
        echo json_encode(['success' => true, 'message' => 'Education session logged securely.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error recording education session.']);
    }
    exit;

} elseif ($action === 'log_discharge') {
    $patient_id = validateInt($_POST['patient_id'] ?? 0);
    $content    = sanitize($_POST['content'] ?? '');
    $notes      = sanitize($_POST['notes'] ?? '');

    if (!$patient_id || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Patient and Discharge Content are required.']);
        exit;
    }

    $instruction_id = 'DIS-' . strtoupper(uniqid());

    $stmt = mysqli_prepare($conn, "
        INSERT INTO discharge_instructions (instruction_id, patient_id, nurse_id, instruction_content, notes, patient_acknowledged) 
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    mysqli_stmt_bind_param($stmt, "siiss", $instruction_id, $patient_id, $nurse_pk, $content, $notes);

    if (mysqli_stmt_execute($stmt)) {
        secureLogNurse($conn, $nurse_pk, "Formulated discharge instructions for Patient PK $patient_id", "discharge");
        
        // Notify doctor that nurse prepared discharge instructions
        // Find assigned doctor
        $doc_id = dbVal($conn, "SELECT doctor_id FROM patients WHERE id=?", "i", [$patient_id]);
        if($doc_id) {
            $doc_user_id = dbVal($conn, "SELECT user_id FROM doctors WHERE id=?", "i", [$doc_id]);
            if($doc_user_id) {
                dbExecute($conn, 
                    "INSERT INTO notifications (user_id, message, type, related_module, created_at) VALUES (?, ?, 'Discharge Plan', 'discharge', NOW())",
                    "is", [$doc_user_id, "Nurse has formulated hospital discharge instructions for your patient (Patient PK $patient_id)."]
                );
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Discharge instructions finalized and doctor notified.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error saving discharge instructions.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Action']);
