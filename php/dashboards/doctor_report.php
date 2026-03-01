<?php
// ============================================================
// DOCTOR REPORT EXPORT HANDLER
// PHP/dashboards/doctor_report.php
// Supports: PDF (print layout), CSV, XLSX-as-CSV
// ============================================================
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    die('Unauthorized'); 
}
require_once '../db_conn.php';
$user_id = (int)$_SESSION['user_id'];
$dr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT d.id AS doc_pk, d.full_name AS doc_name, d.specialization FROM doctors d WHERE d.user_id=$user_id LIMIT 1"));
$doc_pk   = $dr ? (int)$dr['doc_pk'] : 0;
$doc_name = $dr['doc_name'] ?? 'Doctor';
$doc_spec = $dr['specialization'] ?? '';

$type   = $_GET['report_type']  ?? 'appointments';
$fmt    = strtolower($_GET['export_format'] ?? 'pdf');
$dfrom  = $_GET['date_from']    ?? date('Y-m-01');
$dto    = $_GET['date_to']      ?? date('Y-m-d');
$pat_id = (int)($_GET['patient_id'] ?? 0);
$status = $_GET['status_filter'] ?? '';

$dfrom_esc = mysqli_real_escape_string($conn,$dfrom);
$dto_esc   = mysqli_real_escape_string($conn,$dto);
$pat_cond  = $pat_id ? "AND patient_id=$pat_id" : '';
$st_cond   = $status ? "AND status='".mysqli_real_escape_string($conn,$status)."'" : '';
$today     = date('Y-m-d');

// ── Fetch data based on type ──────────────────────────────
$rows = [];
$headers = [];
$title = '';

switch ($type) {
    case 'appointments':
        $title = 'Appointments Summary';
        $headers = ['Appt ID','Patient','Date','Time','Service','Status','Urgency'];
        $q = mysqli_query($conn,"SELECT a.appointment_id,u.name AS patient,a.appointment_date,a.appointment_time,
            a.service_type,a.status,a.urgency_level
          FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id
          WHERE a.doctor_id=$doc_pk AND a.appointment_date BETWEEN '$dfrom_esc' AND '$dto_esc' $st_cond $pat_cond
          ORDER BY a.appointment_date DESC");
        if($q) while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
        break;

    case 'prescriptions':
        $title = 'Prescription Report';
        $headers = ['Rx ID','Patient','Medicine','Dosage','Frequency','Duration','Date','Status'];
        $q = mysqli_query($conn,"SELECT pr.prescription_id,u.name AS patient,pr.medication_name,
            pr.dosage,pr.frequency,pr.duration,pr.prescription_date,pr.status
          FROM prescriptions pr JOIN patients p ON pr.patient_id=p.id JOIN users u ON p.user_id=u.id
          WHERE pr.doctor_id=$doc_pk AND pr.prescription_date BETWEEN '$dfrom_esc' AND '$dto_esc' $st_cond $pat_cond
          ORDER BY pr.prescription_date DESC");
        if($q) while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
        break;

    case 'lab_results':
        $title = 'Lab Results Report';
        $headers = ['Test ID','Patient','Test Name','Category','Urgency','Date','Status'];
        $q = mysqli_query($conn,"SELECT lt.test_id,u.name AS patient,lt.test_name,lt.test_category,
            lt.urgency_level,lt.test_date,lt.status
          FROM lab_tests lt JOIN patients p ON lt.patient_id=p.id JOIN users u ON p.user_id=u.id
          WHERE lt.doctor_id=$doc_pk AND lt.test_date BETWEEN '$dfrom_esc' AND '$dto_esc' $st_cond $pat_cond
          ORDER BY lt.test_date DESC");
        if($q) while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
        break;

    case 'patient_history':
        $title = 'Patient Visit History';
        $headers = ['Record ID','Patient','Visit Date','Diagnosis','Treatment','Follow-up'];
        $q = mysqli_query($conn,"SELECT mr.record_id,u.name AS patient,mr.visit_date,
            mr.diagnosis,mr.treatment,IF(mr.follow_up_required,mr.follow_up_date,'No') AS follow_up
          FROM medical_records mr JOIN patients p ON mr.patient_id=p.id JOIN users u ON p.user_id=u.id
          WHERE mr.doctor_id=$doc_pk AND mr.visit_date BETWEEN '$dfrom_esc' AND '$dto_esc' $pat_cond
          ORDER BY mr.visit_date DESC");
        if($q) while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
        break;

    case 'medicine_inventory':
        $title = 'Medicine Inventory Status';
        $headers = ['Medicine','Generic','Category','Stock Qty','Unit','Reorder Level','Status','Expiry'];
        $q = mysqli_query($conn,"SELECT medicine_name,generic_name,category,stock_quantity,unit,reorder_level,stock_status,expiry_date FROM medicine_inventory ORDER BY stock_status,medicine_name");
        if($q) while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
        break;

    case 'bed_management':
        $title = 'Bed Management Summary';
        $headers = ['Bed Number','Ward','Type','Status','Patient','Admission Date'];
        $q = mysqli_query($conn,"SELECT bed_number,ward,bed_type,bed_status,IFNULL(patient_name,'-') AS patient,IFNULL(admission_date,'-') AS admission_date FROM bed_management ORDER BY ward,bed_number");
        if($q) while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
        break;

    case 'analytics_summary':
        $title = 'Analytics Summary';
        $headers = ['Metric','Value'];
        function qv($c,$s){$r=mysqli_query($c,$s);return $r?(mysqli_fetch_row($r)[0]??0):0;}
        $rows = [
            ['Total Appointments (Period)', qv($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND appointment_date BETWEEN '$dfrom_esc' AND '$dto_esc'")],
            ['Completed Appointments',      qv($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND status='Completed' AND appointment_date BETWEEN '$dfrom_esc' AND '$dto_esc'")],
            ['Pending Appointments',        qv($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND status='Pending' AND appointment_date BETWEEN '$dfrom_esc' AND '$dto_esc'")],
            ['Prescriptions Issued',        qv($conn,"SELECT COUNT(*) FROM prescriptions WHERE doctor_id=$doc_pk AND prescription_date BETWEEN '$dfrom_esc' AND '$dto_esc'")],
            ['Lab Tests Requested',         qv($conn,"SELECT COUNT(*) FROM lab_tests WHERE doctor_id=$doc_pk AND test_date BETWEEN '$dfrom_esc' AND '$dto_esc'")],
            ['Medical Records Added',       qv($conn,"SELECT COUNT(*) FROM medical_records WHERE doctor_id=$doc_pk AND visit_date BETWEEN '$dfrom_esc' AND '$dto_esc'")],
            ['Unique Patients Seen',        qv($conn,"SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id=$doc_pk AND appointment_date BETWEEN '$dfrom_esc' AND '$dto_esc'")],
            ['Available Beds',              qv($conn,"SELECT COUNT(*) FROM beds WHERE status='Available'")],
            ['Low/Out of Stock Medicines',  qv($conn,"SELECT COUNT(*) FROM medicines WHERE stock_quantity<=reorder_level")],
        ];
        break;
}

// ── CSV / XLSX Export ──────────────────────────────────────
if ($fmt === 'csv' || $fmt === 'excel') {
    $ext = $fmt === 'excel' ? 'xlsx' : 'csv';
    $fn  = strtolower(str_replace(' ','_',$title)).'_'.date('Ymd').'.'.$ext;
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$fn.'"');
    $out = fopen('php://output','w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($out, ['RMU Medical Sickbay — '.$title]);
    fputcsv($out, ['Doctor: Dr. '.$doc_name.' | '.$doc_spec]);
    fputcsv($out, ['Period: '.$dfrom.' to '.$dto.' | Generated: '.date('d M Y H:i')]);
    fputcsv($out, []);
    fputcsv($out, $headers);
    foreach ($rows as $row) fputcsv($out, is_array($row) ? array_values($row) : $row);
    fclose($out);
    exit;
}

// ── PDF (Print-friendly HTML) ──────────────────────────────
$status_colors = [
    'Pending'=>['#FEF9E7','#F39C12'],
    'Completed'=>['#EAFAF1','#27AE60'],
    'Confirmed'=>['#EAFAF1','#27AE60'],
    'Cancelled'=>['#FDEDEC','#E74C3C'],
    'Rescheduled'=>['#FEF9E7','#F39C12'],
    'Dispensed'=>['#EBF5FB','#2980B9'],
    'In Stock'=>['#EAFAF1','#27AE60'],
    'Out of Stock'=>['#FDEDEC','#E74C3C'],
    'Low Stock'=>['#FEF9E7','#F39C12'],
    'Expiring Soon'=>['#FEF9E7','#E67E22'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?=htmlspecialchars($title)?> — RMU Medical Sickbay</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Poppins,sans-serif;font-size:12px;color:#1A2035;background:#fff;padding:2rem;}
.report-header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:1.5rem;border-bottom:3px solid #2F80ED;margin-bottom:1.5rem;}
.brand{display:flex;align-items:center;gap:1rem;}
.brand-icon{width:50px;height:50px;border-radius:12px;background:linear-gradient(135deg,#1C3A6B,#2F80ED);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;}
.brand-title{font-size:1.1rem;font-weight:700;color:#1C3A6B;}
.brand-sub{font-size:.9rem;color:#5A6A85;}
.meta{text-align:right;font-size:.9rem;color:#5A6A85;}
.meta strong{display:block;font-size:1rem;color:#1A2035;}
h1{font-size:1.6rem;font-weight:700;color:#2F80ED;margin-bottom:.3rem;}
.period{font-size:.85rem;color:#5A6A85;margin-bottom:1.5rem;}
table{width:100%;border-collapse:collapse;margin-bottom:2rem;}
th{background:linear-gradient(135deg,#1C3A6B,#2F80ED);color:#fff;padding:.6rem 1rem;text-align:left;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;}
td{padding:.6rem 1rem;border-bottom:1px solid #E1EAFF;font-size:.88rem;}
tr:nth-child(even) td{background:#F4F8FF;}
.badge{display:inline-block;padding:.2rem .7rem;border-radius:20px;font-size:.75rem;font-weight:600;}
.footer{margin-top:2rem;padding-top:1rem;border-top:1px solid #E1EAFF;display:flex;justify-content:space-between;font-size:.78rem;color:#8FA3BF;}
.no-data{text-align:center;padding:2rem;color:#8FA3BF;font-size:1rem;}
.analytics-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:2rem;}
.analytics-card{border:1px solid #E1EAFF;border-radius:10px;padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;}
.analytics-num{font-size:1.8rem;font-weight:800;color:#2F80ED;}
@media print{body{padding:0;}button{display:none!important;}.no-print{display:none;}}
</style>
</head>
<body>
<div class="report-header">
  <div class="brand">
    <div class="brand-icon">⚕</div>
    <div><div class="brand-title">RMU Medical Sickbay</div><div class="brand-sub">Doctor's Dashboard</div></div>
  </div>
  <div class="meta">
    <strong>Dr. <?=htmlspecialchars($doc_name)?></strong>
    <?=htmlspecialchars($doc_spec)?><br>
    Generated: <?=date('d M Y, g:i A')?>
  </div>
</div>

<h1><?=htmlspecialchars($title)?></h1>
<div class="period">Period: <?=date('d M Y',strtotime($dfrom))?> — <?=date('d M Y',strtotime($dto))?></div>

<button onclick="window.print()" class="no-print" style="margin-bottom:1.5rem;padding:.7rem 2rem;background:#2F80ED;color:#fff;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:1rem;cursor:pointer;font-weight:600;">
  🖨 Print / Save as PDF
</button>

<?php if ($type === 'analytics_summary'): ?>
<div class="analytics-grid">
  <?php foreach($rows as [$label,$val]):?>
  <div class="analytics-card">
    <div style="font-weight:600;font-size:.9rem;"><?=htmlspecialchars($label)?></div>
    <div class="analytics-num"><?=$val?></div>
  </div>
  <?php endforeach;?>
</div>
<?php else: ?>
<?php if(empty($rows)):?>
  <div class="no-data">No data found for the selected period and filters.</div>
<?php else:?>
<table>
  <thead><tr><?php foreach($headers as $h):?><th><?=htmlspecialchars($h)?></th><?php endforeach;?></tr></thead>
  <tbody>
  <?php foreach($rows as $row): $vals=array_values($row); ?>
  <tr>
    <?php foreach($vals as $i=>$v):
      // Detect status-like columns
      $sc=$status_colors[$v]??null;
      if($sc): ?>
      <td><span class="badge" style="background:<?=$sc[0]?>;color:<?=$sc[1]?>;"><?=htmlspecialchars($v??'')?></span></td>
      <?php else:?>
      <td><?=htmlspecialchars($v??'')?></td>
      <?php endif;?>
    <?php endforeach;?>
  </tr>
  <?php endforeach;?>
  </tbody>
</table>
<div style="font-size:.85rem;color:#5A6A85;">Total records: <strong><?=count($rows)?></strong></div>
<?php endif;?>
<?php endif;?>

<div class="footer">
  <span>RMU Medical Management System &copy; <?=date('Y')?></span>
  <span>Confidential — Dr. <?=htmlspecialchars($doc_name)?></span>
  <span>Page 1 of 1</span>
</div>
</body>
</html>
