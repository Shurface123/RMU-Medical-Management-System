<?php
// ============================================================
// PROCESS VITALS (AJAX Endpoint)
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

// CSRF Validation
verifyCsrfToken($_POST['_csrf'] ?? '');

$action = sanitize($_POST['action'] ?? '');
if ($action !== 'record_vitals') {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ── 1. Gather & Validate Inputs ──────────────────────────────────
$patient_id  = validateInt($_POST['patient_id'] ?? 0);
$sys         = validateFloat($_POST['bp_systolic'] ?? '');
$dia         = validateFloat($_POST['bp_diastolic'] ?? '');
$pulse       = validateFloat($_POST['pulse_rate'] ?? '');
$oxy         = validateFloat($_POST['oxygen_saturation'] ?? '');
$temp        = validateFloat($_POST['temperature'] ?? '');
$resp        = validateFloat($_POST['respiratory_rate'] ?? '');
$gluco       = validateFloat($_POST['blood_glucose'] ?? '');
$weight      = validateFloat($_POST['weight'] ?? '');
$height      = validateFloat($_POST['height'] ?? '');
$notes       = sanitize($_POST['notes'] ?? '');

if (!$patient_id) {
    echo json_encode(['success'=>false, 'message'=>'Patient ID is missing.']); exit;
}

// Require at least one vital sign
if ($sys===false && $dia===false && $pulse===false && $oxy===false && $temp===false && $resp===false && $gluco===false) {
    echo json_encode(['success'=>false, 'message'=>'Please enter at least one vital sign reading.']); exit;
}

// Calculate BMI if missing but W/H exist
$bmi = validateFloat($_POST['bmi'] ?? '');
if ($bmi === false && $weight !== false && $height !== false && $height > 0) {
    $h_m = $height / 100;
    $bmi = round($weight / ($h_m * $h_m), 1);
}

// ── 2. Auto-Flagging Logic against Thresholds ────────────────────
$is_flagged = 0;
$flag_reasons = [];
$doctor_notified = 0;
$is_critical = 0;

// Fetch active thresholds
$thresholds = [];
$q_th = mysqli_query($conn, "SELECT vital_type, min_normal, max_normal, critical_low, critical_high FROM vital_thresholds");
if($q_th) while($r = mysqli_fetch_assoc($q_th)) {
    $thresholds[$r['vital_type']] = $r;
}

// Helper function to check a value against thresholds
function checkThreshold($key, $val, $thresholds, $name, &$flag_reasons, &$is_critical) {
    if ($val === false || !isset($thresholds[$key])) return;
    $th = $thresholds[$key];
    
    // Check criticals first
    if ($th['critical_high'] !== null && $val >= $th['critical_high']) {
        $flag_reasons[] = "$name CRITICAL HIGH ($val)";
        $is_critical = 1;
    } elseif ($th['critical_low'] !== null && $val <= $th['critical_low']) {
        $flag_reasons[] = "$name CRITICAL LOW ($val)";
        $is_critical = 1;
    } 
    // Check abnormals
    elseif ($th['max_normal'] !== null && $val > $th['max_normal']) {
        $flag_reasons[] = "$name High ($val)";
    } elseif ($th['min_normal'] !== null && $val < $th['min_normal']) {
        $flag_reasons[] = "$name Low ($val)";
    }
}

checkThreshold('bp_systolic', $sys, $thresholds, 'Sys BP', $flag_reasons, $is_critical);
checkThreshold('bp_diastolic', $dia, $thresholds, 'Dia BP', $flag_reasons, $is_critical);
checkThreshold('pulse_rate', $pulse, $thresholds, 'Pulse', $flag_reasons, $is_critical);
checkThreshold('oxygen_saturation', $oxy, $thresholds, 'SpO2', $flag_reasons, $is_critical);
checkThreshold('temperature', $temp, $thresholds, 'Temp', $flag_reasons, $is_critical);
checkThreshold('respiratory_rate', $resp, $thresholds, 'Resp', $flag_reasons, $is_critical);
checkThreshold('blood_glucose', $gluco, $thresholds, 'Glucose', $flag_reasons, $is_critical);
checkThreshold('bmi', $bmi, $thresholds, 'BMI', $flag_reasons, $is_critical);

if (count($flag_reasons) > 0) {
    $is_flagged = 1;
}
$flag_reason_str = empty($flag_reasons) ? null : implode(', ', $flag_reasons);

// ── 3. Find Doctor & Ward Info if Critical ───────────────────────
$assigned_doc_id = null;
$patient_name = "Patient #$patient_id";
$ward_loc = "Unknown";

$pat_q = mysqli_query($conn, "
    SELECT u.name, ba.doctor_id, b.ward, b.bed_number 
    FROM patients p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status='Occupied'
    LEFT JOIN beds b ON ba.bed_id = b.id
    WHERE p.id = $patient_id LIMIT 1
");
if ($pat_row = mysqli_fetch_assoc($pat_q)) {
    $assigned_doc_id = $pat_row['doctor_id'];
    $patient_name = $pat_row['name'];
    if ($pat_row['ward']) $ward_loc = "{$pat_row['ward']} Bed {$pat_row['bed_number']}";
}

if ($is_critical && $assigned_doc_id) {
    $doctor_notified = 1;
}

// ── 4. Insert Vitals ─────────────────────────────────────────────
$vital_id = 'VIT-' . strtoupper(uniqid());

// Get nurse PK
$nurse_pk = dbVal($conn, "SELECT id FROM nurses WHERE user_id=$nurse_id LIMIT 1", "i", []);

$stmt = mysqli_prepare($conn, "
    INSERT INTO patient_vitals (
        vital_id, patient_id, nurse_id, bp_systolic, bp_diastolic, 
        pulse_rate, temperature, oxygen_saturation, respiratory_rate, 
        blood_glucose, weight, height, bmi, notes, 
        is_flagged, flag_reason, doctor_notified
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// Convert false to null for DB mapping
$sv = fn($x) => ($x===false || $x==='') ? null : $x;

$sys_v   = $sv($sys);
$dia_v   = $sv($dia);
$pulse_v = $sv($pulse);
$temp_v  = $sv($temp);
$oxy_v   = $sv($oxy);
$resp_v  = $sv($resp);
$gluco_v = $sv($gluco);
$wt_v    = $sv($weight);
$ht_v    = $sv($height);
$bmi_v   = $sv($bmi);
$notes_v = $sv($notes);

mysqli_stmt_bind_param($stmt, "siiddddddddddssii", 
    $vital_id, $patient_id, $nurse_pk, 
    $sys_v, $dia_v, $pulse_v, $temp_v, $oxy_v, $resp_v, 
    $gluco_v, $wt_v, $ht_v, $bmi_v, $notes_v, 
    $is_flagged, $flag_reason_str, $doctor_notified
);

if (mysqli_stmt_execute($stmt)) {
    
    // Log Activity
    secureLogNurse($conn, $nurse_pk, "Recorded vitals for $patient_name" . ($is_flagged ? " (Flagged)" : ""), "vitals");

    // Send Notification to Doctor if Critical
    if ($doctor_notified) {
        $doc_user_id = dbVal($conn, "SELECT user_id FROM doctors WHERE id=?", "i", [$assigned_doc_id]);
        if ($doc_user_id) {
            $msg = "🚨 CRITICAL VITALS ALERT: $patient_name ($ward_loc). Reason: $flag_reason_str";
            dbExecute($conn, 
                "INSERT INTO notifications (user_id, message, type, related_module, related_id, is_read, created_at) VALUES (?, ?, 'Critical Alert', 'patient_vitals', ?, 0, NOW())",
                "isi", [$doc_user_id, $msg, $patient_id]
            );
        }
    }

    echo json_encode(['success' => true, 'message' => "Vitals saved successfully." . ($is_flagged ? "\nAlerts: $flag_reason_str" : "")]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
