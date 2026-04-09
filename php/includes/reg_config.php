<?php
// ============================================================
// REGISTRATION PORTAL — CONFIGURATION
// All domain rules, reCAPTCHA keys, and security thresholds
// in one place. NEVER hardcode these values in other files.
// ============================================================

// ── Google reCAPTCHA v3 Keys ─────────────────────────────────
// Fetched securely from the database; fallbacks provided
$_site_key = '6LdlQ5csAAAAANm5iKW7NArFpAQSLjLlXN8eRmzS';
$_secret_key = '6LdlQ5csAAAAAOILHMEs_LeekTuKDdT4wTNLWpSC';
$_threshold = 0.5;

if (isset($conn) && $conn instanceof mysqli) {
    $q_sys = mysqli_query($conn, "SELECT config_key, config_value FROM system_config WHERE config_key IN ('recaptcha_site_key', 'recaptcha_secret_key', 'recaptcha_score_threshold')");
    if ($q_sys) {
        while ($r_sys = mysqli_fetch_assoc($q_sys)) {
            if ($r_sys['config_key'] === 'recaptcha_site_key' && !empty($r_sys['config_value'])) { $_site_key = $r_sys['config_value']; }
            if ($r_sys['config_key'] === 'recaptcha_secret_key' && !empty($r_sys['config_value'])) { $_secret_key = $r_sys['config_value']; }
            if ($r_sys['config_key'] === 'recaptcha_score_threshold' && is_numeric($r_sys['config_value'])) { $_threshold = (float)$r_sys['config_value']; }
        }
    }
}

define('RECAPTCHA_SITE_KEY', $_site_key);
define('RECAPTCHA_SECRET_KEY', $_secret_key);
define('RECAPTCHA_THRESHOLD', $_threshold);
define('RECAPTCHA_ACTION', 'register');

// ── Email Domain Rules ────────────────────────────────────────
// Indexed by role / patient_type
define('EMAIL_DOMAIN_RULES', [
    'patient_student' => [
        'domain' => '@st.rmu.edu.gh',
        'message' => 'Student patients must use their official RMU student email ending in @st.rmu.edu.gh',
    ],
    'patient_staff' => [
        'domain' => '@rmu.edu.gh',
        'message' => 'Staff and lecturer patients must use their official RMU staff email ending in @rmu.edu.gh',
    ],
    // Doctors, nurses, lab_technicians, pharmacists: no restriction
]);

// ── Password Policy ────────────────────────────────────────────
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPER', true);
define('PASSWORD_REQUIRE_LOWER', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SYMBOL', true);

// ── OTP Settings ──────────────────────────────────────────────
define('OTP_EXPIRY_MINUTES', 10);
define('OTP_MAX_ATTEMPTS', 5);
define('OTP_MAX_RESENDS', 3);
define('OTP_LENGTH', 6);

// ── Rate Limiting ──────────────────────────────────────────────
define('REG_MAX_ATTEMPTS_PER_HOUR', 5); // per IP
define('REG_LOCKOUT_MINUTES', 60);

// ── File Upload ────────────────────────────────────────────────
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2 MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('UPLOAD_DIR', dirname(__DIR__, 2) . '/uploads/profile_photos/');
define('UPLOAD_URL_PATH', '/RMU-Medical-Management-System/uploads/profile_photos/');

// ── Registration Session ───────────────────────────────────────
define('REG_SESSION_EXPIRY_MINUTES', 30);

// ── Roles that require admin approval ─────────────────────────
define('APPROVAL_REQUIRED_ROLES', ['doctor', 'nurse', 'lab_technician', 'pharmacist', 'maintenance', 'security', 'cleaner', 'ambulance_driver', 'laundry_staff', 'kitchen_staff', 'finance_officer', 'finance_manager']);

// ── Available Roles for self-registration ─────────────────────
define('REGISTERABLE_ROLES', [
    'patient' => ['label' => 'Patient', 'icon' => 'fa-user-injured', 'color' => '#E74C3C'],
    'doctor' => ['label' => 'Doctor', 'icon' => 'fa-user-md', 'color' => '#2F80ED'],
    'nurse' => ['label' => 'Nurse', 'icon' => 'fa-user-nurse', 'color' => '#E67E22'],
    'lab_technician' => ['label' => 'Lab Technician', 'icon' => 'fa-flask', 'color' => '#9B59B6'],
    'pharmacist' => ['label' => 'Pharmacist', 'icon' => 'fa-prescription-bottle-alt', 'color' => '#27AE60'],
    'maintenance' => ['label' => 'Maintenance Officer', 'icon' => 'fa-tools', 'color' => '#D35400'],
    'security' => ['label' => 'Security Officer', 'icon' => 'fa-shield-alt', 'color' => '#2C3E50'],
    'cleaner' => ['label' => 'Cleaner', 'icon' => 'fa-broom', 'color' => '#16A085'],
    'ambulance_driver' => ['label' => 'Ambulance Driver', 'icon' => 'fa-ambulance', 'color' => '#C0392B'],
    'laundry_staff' => ['label' => 'Laundry Personnel', 'icon' => 'fa-tshirt', 'color' => '#8E44AD'],
    'kitchen_staff' => ['label' => 'Kitchen Staff', 'icon' => 'fa-utensils', 'color' => '#F39C12'],
    'finance_officer' => ['label' => 'Finance Officer', 'icon' => 'fa-file-invoice-dollar', 'color' => '#27AE60'],
    'finance_manager' => ['label' => 'Finance Manager', 'icon' => 'fa-wallet', 'color' => '#16A085'],
]);
