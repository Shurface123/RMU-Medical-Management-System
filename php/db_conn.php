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