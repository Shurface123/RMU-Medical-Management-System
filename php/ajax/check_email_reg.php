<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/db_conn.php';
require_once dirname(__DIR__) . '/includes/reg_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'msg'=>'Invalid method']); exit; }

$email       = trim($_POST['email'] ?? '');
$role        = trim($_POST['role'] ?? '');
$patient_type= trim($_POST['patient_type'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok'=>false,'msg'=>'Invalid email format']); exit;
}

// ── Domain rule check ─────────────────────────────────────────
$rules = EMAIL_DOMAIN_RULES;
if ($role === 'patient' && $patient_type) {
    $key = 'patient_' . $patient_type;
    if (isset($rules[$key])) {
        $domain = $rules[$key]['domain'];
        if (!str_ends_with(strtolower($email), strtolower($domain))) {
            echo json_encode(['ok'=>false,'domain_error'=>true,'msg'=>$rules[$key]['message']]); exit;
        }
    }
}

// ── Duplicate check ───────────────────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['ok'=>false,'msg'=>'This email is already registered']); exit;
}
echo json_encode(['ok'=>true,'msg'=>'Email is available']);
