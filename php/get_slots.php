<?php
// ============================================================
// GET SLOTS — returns booked time slots for a doctor on a date
// ============================================================
header('Content-Type: application/json');
require_once 'db_conn.php';

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$date      = isset($_GET['date']) ? $_GET['date'] : '';

if (!$doctor_id || !$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['booked' => []]);
    exit;
}

$safe_date = mysqli_real_escape_string($conn, $date);
$q = mysqli_query($conn,
    "SELECT appointment_time FROM appointments
     WHERE doctor_id = $doctor_id AND appointment_date = '$safe_date'
     AND status NOT IN('Cancelled','No-Show')"
);

$booked = [];
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        // Normalize to HH:MM
        $booked[] = substr($r['appointment_time'], 0, 5);
    }
}

echo json_encode(['booked' => $booked]);
