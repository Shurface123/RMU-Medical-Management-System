<?php
// ============================================================
// PROCESS REPORTS AND EXPORTS (Target=_blank Endpoint)
// ============================================================
require_once '../dashboards/nurse_security.php';
initSecureSession();
$nurse_id = enforceNurseRole(); 
require_once '../db_conn.php';

// Notice: This file does NOT output JSON by default because it streams file downloads or HTML print views.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method. Reports must be generated via the dashboard form.");
}

verifyCsrfToken($_POST['_csrf'] ?? '');

$nurse_pk = dbVal($conn, "SELECT id FROM nurses WHERE user_id=$nurse_id LIMIT 1", "i", []);

$report_type   = sanitize($_POST['report_type'] ?? '');
$start_date    = sanitize($_POST['start_date'] ?? date('Y-m-d', strtotime('-7 days')));
$end_date      = sanitize($_POST['end_date'] ?? date('Y-m-d'));
$export_format = sanitize($_POST['export_format'] ?? 'print'); // 'csv' or 'print'

if (empty($report_type)) die("Report Type is required.");

// Build Query based on type
$query = "";
$headers = [];
$title = "Clinical Report";

switch ($report_type) {
    case 'vitals':
        $title = "Patient Vitals Flowsheet";
        $headers = ["Date/Time", "Patient Name", "Patient ID", "BP (mmHg)", "Heart Rate (bpm)", "Resp Rate (bpm)", "Temp (°C)", "SpO2 (%)", "Weight (kg)", "Nurse ID"];
        $query = "
            SELECT v.recorded_at, u.name, p.patient_id, v.blood_pressure, v.heart_rate, v.respiratory_rate, v.temperature, v.oxygen_saturation, v.weight, v.nurse_id
            FROM patient_vitals v
            JOIN patients p ON v.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE DATE(v.recorded_at) BETWEEN '$start_date' AND '$end_date'
            ORDER BY v.recorded_at DESC
        ";
        break;

    case 'medications':
        $title = "Medication Administration Record (MAR)";
        $headers = ["Administered At", "Patient Name", "Medication Name", "Dosage", "Route", "Site", "Status", "Nurse ID", "Double Checked By"];
        $query = "
            SELECT m.administered_at, u.name, m.medication_name, m.dosage, m.route, m.site, m.status, m.nurse_id, m.verified_by
            FROM medication_administration m
            JOIN patients p ON m.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE DATE(m.administered_at) BETWEEN '$start_date' AND '$end_date'
            ORDER BY m.administered_at DESC
        ";
        break;
        
    case 'fluids':
        $title = "Fluid Balance Daily Summaries";
        $headers = ["Record Date", "Patient Name", "Total Intake (ml)", "Total Output (ml)", "Net Balance (ml)", "Nurse ID"];
        $query = "
            SELECT f.record_date, u.name, f.total_intake, f.total_output, f.net_balance, f.nurse_id
            FROM fluid_balance f
            JOIN patients p ON f.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE f.record_date BETWEEN '$start_date' AND '$end_date'
            ORDER BY f.record_date DESC, u.name ASC
        ";
        break;
        
    case 'emergencies':
        $title = "Emergency Alert Incident History";
        $headers = ["Date/Time", "Alert Type", "Location", "Severity", "Patient Involved", "Message", "Status", "Triggering Nurse ID"];
        $query = "
            SELECT e.triggered_at, e.alert_type, e.location, e.severity, 
                   COALESCE(u.name, 'N/A') as patient_name, 
                   e.message, e.status, e.nurse_id
            FROM emergency_alerts e
            LEFT JOIN patients p ON e.patient_id = p.id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE DATE(e.triggered_at) BETWEEN '$start_date' AND '$end_date'
            ORDER BY e.triggered_at DESC
        ";
        break;
        
    case 'tasks':
        $title = "Completed Shift Tasks";
        $headers = ["Completed At", "Task Title", "Description", "Patient Involved", "Ward", "Nurse ID"];
        $query = "
            SELECT t.completed_at, t.task_title, t.task_description, 
                   COALESCE(u.name, 'N/A') as patient_name, 
                   t.ward, t.nurse_id
            FROM nurse_tasks t
            LEFT JOIN patients p ON t.patient_id = p.id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE t.status = 'Completed' AND DATE(t.completed_at) BETWEEN '$start_date' AND '$end_date'
            ORDER BY t.completed_at DESC
        ";
        break;
        
    default:
        die("Invalid Report Type.");
}

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}

$data = [];
while ($row = mysqli_fetch_row($result)) {
    $data[] = $row;
}

// Log Export Action
secureLogNurse($conn, $nurse_pk, "Generated $title ($export_format) for period $start_date to $end_date", "reports");

// ------------------------------------------------------------------
// CSV EXPORT LOGIC
// ------------------------------------------------------------------
if ($export_format === 'csv') {
    $filename = strtolower(str_replace(' ', '_', $title)) . "_{$start_date}_to_{$end_date}.csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $fp = fopen('php://output', 'w');
    fputcsv($fp, $headers);
    foreach ($data as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
    exit;
}

// ------------------------------------------------------------------
// PRINT VIEW / HTML LOGIC
// ------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($title) ?> - Print View</title>
    <!-- Use standard bootstrap for print grid structure -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 20px; font-size: 14px; background-color: #f8f9fa; }
        .print-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 1100px; margin: 0 auto; }
        .rpt-header { border-bottom: 3px solid #E67E22; padding-bottom: 10px; margin-bottom: 20px; }
        .table th { background-color: #f0f0f0; color: #333; }
        @media print {
            body { background-color: white; padding: 0; }
            .print-container { padding: 0; box-shadow: none; max-width: 100%; border-radius: 0; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

<div class="print-container">
    <div class="d-flex justify-content-between align-items-center rpt-header">
        <div>
            <h2 class="mb-0 text-dark fw-bold"><?= e($title) ?></h2>
            <p class="mb-0 text-muted"><strong>Reporting Period:</strong> <?= $start_date ?> to <?= $end_date ?></p>
        </div>
        <div class="text-end">
            <h5 class="text-primary mb-0 fw-bold">RMU Medical System</h5>
            <small class="text-muted">Generated: <?= date('Y-m-d H:i:s') ?></small><br>
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm mt-3 btn-print">🖨️ Print Document</button>
        </div>
    </div>

    <?php if (empty($data)): ?>
        <div class="alert alert-secondary text-center py-5">
            <strong>No data found for the specified period.</strong>
        </div>
    <?php else: ?>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <?php foreach ($headers as $h): ?>
                        <th><?= e($h) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <td><?= e($cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="mt-4 pt-4 border-top text-center text-muted small">
            <p><strong>Strictly Confidential</strong> — Contains Protected Health Information (PHI).<br>
            Authorized generation by Nurse ID: <?= $nurse_pk ?>.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
