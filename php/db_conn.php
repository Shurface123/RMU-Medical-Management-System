<?php
// ============================================
// DATABASE CONNECTION — RMU Medical Sickbay
// ============================================

$sname    = "localhost";
$unmae    = "root";
$password = "Confrontation@433";
$db_name  = "rmu_medical_sickbay";

$conn = mysqli_connect($sname, $unmae, $password, $db_name);

if (!$conn) {
    error_log("DB Connection failed: " . mysqli_connect_error());
    die(json_encode(['error' => 'Database connection failed']));
}

// Universal utf8mb4 charset (supports emoji, Arabic etc.)
mysqli_set_charset($conn, 'utf8mb4');

// ── Global SMTP / Email Config ─────────────────────────────────────────────
// Shared by EmailService, booking_handler, etc.
$emailConfig = [
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => 587,
    'smtp_username' => 'sickbay.text@st.rmu.edu.gh',
    'smtp_password' => 'hqrr kkat ruqg nutf',
    'from_email'    => 'sickbay.text@st.rmu.edu.gh',
    'from_name'     => 'RMU Medical Sickbay',
];

// ── New: Load Dynamic System Settings ──────────────────────────────────────
$sys_settings = [];
$res = mysqli_query($conn, "SELECT config_key, config_value FROM system_config");
if ($res) {
    while($row = mysqli_fetch_assoc($res)) {
        $sys_settings[$row['config_key']] = $row['config_value'];
    }
}

/**
 * Helper to fetch a system setting with a default value.
 */
if (!function_exists('get_setting')) {
    function get_setting($key, $default = '') {
        global $sys_settings;
        return $sys_settings[$key] ?? $default;
    }
}

// ── New: Load Hospital Profile ─────────────────────────────────────────────
$h_res = mysqli_query($conn, "SELECT * FROM hospital_settings WHERE id = 1");
$hospital_profile = mysqli_fetch_assoc($h_res) ?: [];

// Apply Timezone immediately
if (isset($sys_settings['timezone'])) {
    date_default_timezone_set($sys_settings['timezone']);
} else {
    date_default_timezone_set('Africa/Accra'); // Default
}