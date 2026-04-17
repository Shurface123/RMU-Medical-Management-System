<?php
/**
 * process_reports.php
 * Extracts CSV/Printable datasets based on Nurse queries parameter filters.
 */
require_once '../dashboards/nurse_security.php';
initSecureSession();
$nurse_id = enforceNurseRole();
require_once '../db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// CSRF checking logic - using the robust implementation from nurse_security
if (!verifyCsrfToken($_POST['_csrf'] ?? '')) {
    die("Security Token Validation Failed. Please refresh the dashboard and try again.");
}

$type   = $_POST['report_type'] ?? '';
$start  = $_POST['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end    = $_POST['end_date'] ?? date('Y-m-d');
$format = $_POST['export_format'] ?? 'print';

if (!$type) die("Report type mapping error.");

// Helper function safely fetching user context data
function buildVitalsQuery($nurse_id, $start, $end) {
    return "SELECT pv.recorded_at, CONCAT(u.name, ' (', p.patient_id, ')'), CONCAT(pv.bp_systolic, '/', pv.bp_diastolic), pv.pulse_rate, pv.temperature, pv.oxygen_saturation, CASE WHEN pv.is_flagged=1 THEN 'Yes' ELSE 'No' END, pv.flag_reason 
            FROM patient_vitals pv 
            JOIN patients p ON pv.patient_id = p.id 
            JOIN users u ON p.user_id = u.id 
            WHERE pv.nurse_id = $nurse_id AND DATE(pv.recorded_at) BETWEEN '$start' AND '$end' 
            ORDER BY pv.recorded_at DESC";
}
function buildMedsQuery($nurse_id, $start, $end) {
    return "SELECT ma.scheduled_time, ma.administered_at, CONCAT(u.name, ' (', p.patient_id, ')'), ma.drug_name, ma.dosage, ma.route, ma.status, ma.notes 
            FROM medication_administration ma 
            JOIN patients p ON ma.patient_id = p.id 
            JOIN users u ON p.user_id = u.id 
            WHERE ma.nurse_id = $nurse_id AND DATE(ma.scheduled_time) BETWEEN '$start' AND '$end' 
            ORDER BY ma.scheduled_time DESC";
}
function buildFluidsQuery($nurse_id, $start, $end) {
    return "SELECT fb.recorded_at, CONCAT(u.name, ' (', p.patient_id, ')'), fb.fluid_type, fb.volume_ml, fb.route, fb.balance_type, fb.notes 
            FROM fluid_balance fb 
            JOIN patients p ON fb.patient_id = p.id 
            JOIN users u ON p.user_id = u.id 
            WHERE fb.nurse_id = $nurse_id AND DATE(fb.recorded_at) BETWEEN '$start' AND '$end' 
            ORDER BY fb.recorded_at DESC";
}
function buildTasksQuery($nurse_id, $start, $end) {
    return "SELECT nt.created_at, nt.completed_at, nt.task_type, nt.description, IFNULL(CONCAT(u.name, ' (', p.patient_id, ')'), 'Ward Task'), nt.status 
            FROM nurse_tasks nt 
            LEFT JOIN patients p ON nt.patient_id = p.id 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE nt.nurse_id = $nurse_id AND DATE(nt.created_at) BETWEEN '$start' AND '$end' 
            ORDER BY nt.created_at DESC";
}
function buildAlertsQuery($nurse_id, $start, $end) {
    return "SELECT ea.created_at, ea.alert_type, ea.ward_id, ea.status, ea.resolved_at, ea.resolved_by 
            FROM emergency_alerts ea 
            WHERE ea.triggered_by_nurse = $nurse_id AND DATE(ea.created_at) BETWEEN '$start' AND '$end' 
            ORDER BY ea.created_at DESC";
}

$start_safe = mysqli_real_escape_string($conn, $start);
$end_safe = mysqli_real_escape_string($conn, $end);

$sql = "";
$headers = [];
$filename = "Report_{$type}_{$start}_to_{$end}";

switch ($type) {
    case 'vitals': 
        $sql = buildVitalsQuery($nurse_id, $start_safe, $end_safe); 
        $headers = ['Date/Time', 'Patient', 'BP', 'HR (bpm)', 'Temp (C)', 'SpO2 (%)', 'Flagged?', 'Flag Reason'];
        break;
    case 'medications': 
        $sql = buildMedsQuery($nurse_id, $start_safe, $end_safe); 
        $headers = ['Scheduled Time', 'Given Time', 'Patient', 'Drug Name', 'Dosage', 'Route', 'Status', 'Notes'];
        break;
    case 'fluids': 
        $sql = buildFluidsQuery($nurse_id, $start_safe, $end_safe); 
        $headers = ['Recorded Time', 'Patient', 'Fluid Type', 'Vol (ml)', 'Route', 'In/Out', 'Notes'];
        break;
    case 'tasks': 
        $sql = buildTasksQuery($nurse_id, $start_safe, $end_safe); 
        $headers = ['Created At', 'Completed At', 'Task Type', 'Description', 'Patient Override', 'Status'];
        break;
    case 'emergencies': 
        $sql = buildAlertsQuery($nurse_id, $start_safe, $end_safe); 
        $headers = ['Triggered At', 'Alert Type', 'Ward ID', 'Status', 'Resolved At', 'Resolver ID'];
        break;
    default: die("Invalid logic hook.");
}

$res = mysqli_query($conn, $sql);
if (!$res) die("Database aggregation error: " . mysqli_error($conn));

// CSV EXPORT ROUTINE
if ($format === 'csv') {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
    $output = fopen("php://output", "w");
    fputcsv($output, $headers);
    while($row = mysqli_fetch_row($res)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// PRINT VIEW ROUTINE (HTML/PDF Rendering emulation)
if ($format === 'print') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= $filename ?> - Print View</title>
        <style>
            body { font-family: 'Arial', sans-serif; padding: 40px; color: #333; line-height: 1.4; }
            h1 { color: #2B5AA5; margin-bottom: 5px; }
            .meta { color: #777; margin-bottom: 30px; font-size: 0.9rem; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9rem; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; color: #444; }
            .footer-legal { margin-top: 50px; font-size: 0.8rem; color: #888; border-top: 1px solid #ccc; padding-top: 15px; text-align: center; }
        </style>
    </head>
    <body onload="window.print()">
        <h1>RMU Medical Sickbay — Clinical Report Extract</h1>
        <div class="meta">
            <strong>Module:</strong> <?= strtoupper(e($type)) ?> Log<br>
            <strong>Date Range:</strong> <?= e($start) ?> to <?= e($end) ?><br>
            <strong>Generated By Nurse ID:</strong> <?= $nurse_id ?><br>
            <strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?>
        </div>
        
        <table>
            <thead>
                <tr><?php foreach($headers as $h) echo "<th>{$h}</th>"; ?></tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_row($res)): ?>
                    <tr><?php foreach($row as $col) echo "<td>".htmlspecialchars($col ?? 'N/A')."</td>"; ?></tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($res) === 0) echo "<tr><td colspan='".count($headers)."' style='text-align:center;'>No records found for this period.</td></tr>"; ?>
            </tbody>
        </table>
        
        <div class="footer-legal">
            CONFIDENTIAL MEDICAL RECORD.<br>
            This document contains legally protected health information under RMU guidelines.<br>
            Any unauthorized duplication or distribution is prohibited.
        </div>
    </body>
    </html>
    <?php
}
?>
