<?php
session_start();
require '../db_conn.php';

// Security: Must be logged in as Nurse
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    die("Unauthorized Access.");
}

$user_id = $_SESSION['user_id'];
$nurse = dbSelect($conn, "SELECT id FROM nurses WHERE user_id=?", "i", [$user_id]);
if (empty($nurse)) die("Nurse profile not located.");
$nurse_id = $nurse[0]['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type  = $_POST['report_type'] ?? '';
    $start = $_POST['start_date'] ?? date('Y-m-01');
    $end   = $_POST['end_date'] ?? date('Y-m-d');
    $pid   = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;

    $filename = "Export_{$type}_" . date('Ymd_Hi') . ".csv";

    // Set headers for forced download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');

    if ($type === 'medications') {
        fputcsv($output, ['Medication', 'Patient', 'Status', 'Administered At', 'Notes']);
        $sql = "SELECT m.medication_name, u.name as p_name, m.status, m.administered_at, m.notes 
                FROM medication_administration m 
                JOIN patients p ON m.patient_id=p.id JOIN users u ON p.user_id=u.id 
                WHERE m.nurse_id=? AND DATE(m.scheduled_time) BETWEEN ? AND ?";
        $params = [$nurse_id, $start, $end];
        $types = "iss";
        if($pid) { $sql .= " AND m.patient_id=?"; $params[] = $pid; $types .= "i"; }
        
        $data = dbSelect($conn, $sql, $types, $params);
        foreach($data as $r) {
            fputcsv($output, [$r['medication_name'], $r['p_name'], $r['status'], $r['administered_at'], $r['notes']]);
        }
    } 
    elseif ($type === 'vitals') {
        fputcsv($output, ['Patient', 'BP', 'Pulse', 'Temp', 'Resp', 'O2', 'Recorded At', 'Risk Level']);
        $sql = "SELECT u.name as p_name, pv.bp_systolic, pv.bp_diastolic, pv.heart_rate, pv.temperature, pv.respiratory_rate, pv.oxygen_saturation, pv.recorded_at, pv.risk_level 
                FROM patient_vitals pv 
                JOIN patients p ON pv.patient_id=p.id JOIN users u ON p.user_id=u.id 
                WHERE pv.nurse_id=? AND DATE(pv.recorded_at) BETWEEN ? AND ?";
        $params = [$nurse_id, $start, $end];
        $types = "iss";
        if($pid) { $sql .= " AND pv.patient_id=?"; $params[] = $pid; $types .= "i"; }
        
        $data = dbSelect($conn, $sql, $types, $params);
        foreach($data as $r) {
            $bp = $r['bp_systolic'].'/'.$r['bp_diastolic'];
            fputcsv($output, [$r['p_name'], $bp, $r['heart_rate'], $r['temperature'], $r['respiratory_rate'], $r['oxygen_saturation'], $r['recorded_at'], $r['risk_level']]);
        }
    }
    elseif ($type === 'notes') {
        fputcsv($output, ['Patient', 'Type', 'Content', 'Locked', 'Date']);
        $sql = "SELECT u.name as p_name, nn.note_type, nn.note_content, nn.is_locked, nn.created_at 
                FROM nursing_notes nn 
                JOIN patients p ON nn.patient_id=p.id JOIN users u ON p.user_id=u.id 
                WHERE nn.nurse_id=? AND DATE(nn.created_at) BETWEEN ? AND ?";
        $params = [$nurse_id, $start, $end];
        $types = "iss";
        if($pid) { $sql .= " AND nn.patient_id=?"; $params[] = $pid; $types .= "i"; }
        
        $data = dbSelect($conn, $sql, $types, $params);
        foreach($data as $r) {
            fputcsv($output, [$r['p_name'], $r['note_type'], $r['note_content'], $r['is_locked'] ? 'Yes' : 'No', $r['created_at']]);
        }
    }
    elseif ($type === 'activity') {
        fputcsv($output, ['Action Type', 'Description', 'Timestamp']);
        $sql = "SELECT action_type, action_description, created_at FROM nurse_activity_log WHERE nurse_id=? AND DATE(created_at) BETWEEN ? AND ?";
        $data = dbSelect($conn, $sql, "iss", [$nurse_id, $start, $end]);
        foreach($data as $r) {
            fputcsv($output, [$r['action_type'], $r['action_description'], $r['created_at']]);
        }
    }
    elseif ($type === 'fluids') {
        fputcsv($output, ['Patient', 'Date', 'Total Intake (ml)', 'Total Output (ml)', 'Net Balance (ml)', 'Notes']);
        $sql = "SELECT u.name as p_name, fb.record_date, fb.total_intake_ml, fb.total_output_ml, fb.net_balance_ml, fb.notes 
                FROM fluid_balance fb 
                JOIN patients p ON fb.patient_id=p.id JOIN users u ON p.user_id=u.id 
                WHERE fb.nurse_id=? AND fb.record_date BETWEEN ? AND ?";
        $params = [$nurse_id, $start, $end];
        $types = "iss";
        if($pid) { $sql .= " AND fb.patient_id=?"; $params[] = $pid; $types .= "i"; }
        
        $data = dbSelect($conn, $sql, $types, $params);
        foreach($data as $r) {
            fputcsv($output, [$r['p_name'], $r['record_date'], $r['total_intake_ml'], $r['total_output_ml'], $r['net_balance_ml'], $r['notes']]);
        }
    }
    
    // Log the export action securely
    dbInsert($conn, "INSERT INTO nurse_activity_log (nurse_id, action_type, action_description, created_at) VALUES (?, 'Data Export', ?, NOW())", "is", [$nurse_id, "Exported $type CSV report ($start to $end)"]);
    
    fclose($output);
    exit;
}
