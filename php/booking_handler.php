<?php
// ============================================================
// BOOKING HANDLER — RMU Medical Sickbay
// Accepts JSON POST body, creates the appointment, sends email
// ============================================================
header('Content-Type: application/json');
session_start();
require_once 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── Read JSON body ────────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    // Fallback to POST form data
    $data = $_POST;
}

// ── Validate required fields ──────────────────────────────────────────────
$required = ['service','doctor_id','appointment_date','appointment_time','patient_name','patient_email','patient_phone','symptoms'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

$doctor_id       = (int)$data['doctor_id'];
$apt_date        = mysqli_real_escape_string($conn, $data['appointment_date']);
$apt_time        = mysqli_real_escape_string($conn, $data['appointment_time']);
$service_type    = mysqli_real_escape_string($conn, $data['service']);
$patient_name    = mysqli_real_escape_string($conn, $data['patient_name']);
$patient_email   = filter_var($data['patient_email'], FILTER_VALIDATE_EMAIL);
$patient_phone   = mysqli_real_escape_string($conn, $data['patient_phone']);
$patient_gender  = mysqli_real_escape_string($conn, $data['patient_gender'] ?? '');
$symptoms        = mysqli_real_escape_string($conn, $data['symptoms']);
$urgency         = mysqli_real_escape_string($conn, $data['urgency'] ?? 'Routine');

if (!$patient_email) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}
$patient_email = mysqli_real_escape_string($conn, $patient_email);

// Validate date range (today to +3 months)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $apt_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}
if (strtotime($apt_date) < strtotime('today') || strtotime($apt_date) > strtotime('+3 months')) {
    echo json_encode(['success' => false, 'message' => 'Appointment date is out of allowed range']);
    exit;
}

// ── Validate doctor exists and is available ───────────────────────────────
$doc_q = mysqli_query($conn,
    "SELECT d.id AS doc_pk, d.consultation_fee, u.name AS doctor_name, u.email AS doctor_email
     FROM doctors d JOIN users u ON d.user_id = u.id
     WHERE d.id = $doctor_id AND d.is_available = 1 AND u.is_active = 1
     LIMIT 1"
);
if (!$doc_q || mysqli_num_rows($doc_q) === 0) {
    echo json_encode(['success' => false, 'message' => 'Selected doctor is not available']);
    exit;
}
$doc = mysqli_fetch_assoc($doc_q);

// ── Check for slot conflict ───────────────────────────────────────────────
$conflict = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM appointments
     WHERE doctor_id = $doctor_id AND appointment_date = '$apt_date' AND appointment_time = '$apt_time'
     AND status NOT IN('Cancelled','No-Show')"
))[0] ?? 0;

if ($conflict > 0) {
    echo json_encode(['success' => false, 'message' => 'This time slot has just been booked. Please choose a different time.']);
    exit;
}

// ── Resolve patient_id ────────────────────────────────────────────────────
$session_user  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$patient_id_fk = null;

if ($session_user) {
    $pr = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM patients WHERE user_id = $session_user LIMIT 1"
    ));
    if ($pr) $patient_id_fk = (int)$pr['id'];
}

// ── Generate appointment_id ───────────────────────────────────────────────
$apt_prefix  = strtoupper(date('Ym'));
$last_id_row = mysqli_fetch_row(mysqli_query($conn,
    "SELECT appointment_id FROM appointments ORDER BY id DESC LIMIT 1"
));
$seq = 1;
if ($last_id_row && preg_match('/RMU-APT-(\d+)-(\d+)/', $last_id_row[0], $m)) {
    $seq = (int)$m[2] + 1;
}
$appointment_id = "RMU-APT-{$apt_prefix}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);

// ── Insert Appointment ────────────────────────────────────────────────────
$patient_id_sql = $patient_id_fk ? $patient_id_fk : 'NULL';

$insert = mysqli_query($conn,
    "INSERT INTO appointments
     (appointment_id, patient_id, doctor_id, appointment_date, appointment_time,
      service_type, symptoms, urgency_level, status, created_at)
     VALUES
     ('$appointment_id', $patient_id_sql, $doctor_id, '$apt_date', '$apt_time',
      '$service_type', '$symptoms', '$urgency', 'Pending', NOW())"
);

if (!$insert) {
    error_log('Booking insert error: ' . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Booking could not be saved. Please try again.']);
    exit;
}

$new_id = mysqli_insert_id($conn);

// ── Send Confirmation Email ───────────────────────────────────────────────
$email_sent = false;
try {
    require_once __DIR__ . '/EmailService.php';
    $email_service = new EmailService();

    $apt_date_fmt = date('l, d F Y', strtotime($apt_date));
    [$h, $m_]     = explode(':', $apt_time);
    $h_12 = $h > 12 ? $h - 12 : ($h == 0 ? 12 : (int)$h);
    $ampm = $h >= 12 ? 'PM' : 'AM';
    $time_fmt = "$h_12:$m_ $ampm";

    $subject = "✅ Appointment Confirmed — $appointment_id";
    $body = "
    <!DOCTYPE html>
    <html><head><style>
      body{font-family:Poppins,Arial,sans-serif;background:#f0f4f8;margin:0;padding:20px;}
      .wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);}
      .header{background:linear-gradient(135deg,#2F80ED,#1abc9c);color:#fff;padding:2rem;text-align:center;}
      .header h1{font-size:1.5rem;margin:0 0 .25rem;}
      .body{padding:2rem;}
      .detail-row{display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:.75rem 0;font-size:.9rem;}
      .detail-label{color:#666;font-weight:600;}
      .detail-value{font-weight:700;text-align:right;}
      .badge{display:inline-block;padding:.3rem .8rem;border-radius:50px;font-size:.78rem;font-weight:700;background:#e8f4fd;color:#2F80ED;}
      .footer{background:#f8f9fa;padding:1.5rem;text-align:center;font-size:.82rem;color:#999;}
      .btn{display:inline-block;padding:.8rem 2rem;background:#2F80ED;color:#fff;text-decoration:none;border-radius:8px;font-weight:700;margin-top:1rem;}
    </style></head>
    <body><div class='wrap'>
      <div class='header'>
        <h1>&#x1F4C5; Appointment Confirmed</h1>
        <p style='margin:0;opacity:.85;font-size:.9rem;'>RMU Medical Sickbay</p>
      </div>
      <div class='body'>
        <p>Dear <strong>" . htmlspecialchars($patient_name) . "</strong>,</p>
        <p>Your appointment has been successfully scheduled. Here are your booking details:</p>
        <div class='detail-row'><span class='detail-label'>Appointment ID</span><span class='detail-value badge'>$appointment_id</span></div>
        <div class='detail-row'><span class='detail-label'>Doctor</span><span class='detail-value'>Dr. " . htmlspecialchars($doc['doctor_name']) . "</span></div>
        <div class='detail-row'><span class='detail-label'>Service</span><span class='detail-value'>$service_type</span></div>
        <div class='detail-row'><span class='detail-label'>Date</span><span class='detail-value'>$apt_date_fmt</span></div>
        <div class='detail-row'><span class='detail-label'>Time</span><span class='detail-value'>$time_fmt</span></div>
        <div class='detail-row'><span class='detail-label'>Urgency</span><span class='detail-value'>$urgency</span></div>
        <br>
        <p style='font-size:.9rem;color:#555;'>Please arrive at the RMU Medical Sickbay at least <strong>10 minutes early</strong> with your student/staff ID. If you need to cancel, please contact us at least 2 hours before your appointment.</p>
        <p style='font-size:.9rem;color:#555;'><strong>Emergency Hotline:</strong> 153</p>
      </div>
      <div class='footer'>RMU Medical Sickbay &mdash; <a href='mailto:sickbay.text@st.rmu.edu.gh'>sickbay.text@st.rmu.edu.gh</a></div>
    </div></body></html>";

    $email_sent = $email_service->sendEmail($patient_email, $patient_name, $subject, $body);

    // Also notify doctor
    if ($doc['doctor_email']) {
        $doc_subj = "New Appointment Scheduled — $appointment_id";
        $doc_body = "<p>Dear Dr. {$doc['doctor_name']},</p><p>A new appointment has been booked for you:</p>
                     <ul><li><strong>Patient:</strong> $patient_name</li>
                     <li><strong>Date:</strong> $apt_date_fmt at $time_fmt</li>
                     <li><strong>Service:</strong> $service_type</li>
                     <li><strong>Urgency:</strong> $urgency</li></ul>
                     <p>Please log in to your dashboard to view full details.</p>";
        $email_service->sendEmail($doc['doctor_email'], 'Dr. ' . $doc['doctor_name'], $doc_subj, $doc_body);
    }
} catch (Exception $e) {
    error_log('Email sending failed: ' . $e->getMessage());
}

echo json_encode([
    'success'        => true,
    'appointment_id' => $appointment_id,
    'message'        => 'Appointment booked successfully',
    'email_sent'     => $email_sent,
]);
