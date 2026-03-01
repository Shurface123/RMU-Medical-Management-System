<?php
// ============================================================
// NOTIFICATION TRIGGER API
// /php/api/notify_trigger.php
// Called internally when system events occur:
//   - Lab result submitted
//   - Medicine low stock / expiring
//   - New appointment booking
//   - Nurse acknowledges instruction
// All other triggers (approve/reschedule/cancel/prescribe) are
// handled inline in doctor_actions.php / Phase 4 files.
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../db_conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit;
}
$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$event    = $body['event'] ?? '';

function esc($conn,$v){ return mysqli_real_escape_string($conn,(string)$v); }
function insertNotif($conn,$uid,$role,$type,$title,$msg,$module,$relId=null){
    $uid=(int)$uid; $role=esc($conn,$role); $type=esc($conn,$type);
    $title=esc($conn,$title); $msg=esc($conn,$msg); $module=esc($conn,$module);
    $rel=$relId?"$relId":'NULL';
    mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,related_id,created_at)
      VALUES($uid,'$role','$type','$title','$msg',0,'$module',$rel,NOW())");
    return (int)mysqli_insert_id($conn);
}
function getDoctorUserId($conn,$docPk){
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id FROM doctors WHERE id=$docPk LIMIT 1"));
    return $r ? (int)$r['user_id'] : 0;
}
function getPatientUserId($conn,$patPk){
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id FROM patients WHERE id=$patPk LIMIT 1"));
    return $r ? (int)$r['user_id'] : 0;
}

switch ($event) {

// ── Lab technician submits results ────────────────────────
case 'lab_result_submitted':
    $testId  = (int)($body['test_id'] ?? 0);
    $techName= esc($conn,$body['tech_name'] ?? 'Lab Technician');
    if (!$testId) { echo json_encode(['success'=>false,'error'=>'Missing test_id']); exit; }
    $lt = mysqli_fetch_assoc(mysqli_query($conn,"SELECT lt.*,u.name AS pname FROM lab_tests lt JOIN patients p ON lt.patient_id=p.id JOIN users u ON p.user_id=u.id WHERE lt.id=$testId LIMIT 1"));
    if ($lt) {
        $docUid = getDoctorUserId($conn, $lt['doctor_id']);
        if ($docUid) {
            insertNotif($conn,$docUid,'doctor','lab',
                "Lab Results Ready — {$lt['test_name']}",
                "$techName has submitted results for {$lt['pname']}'s {$lt['test_name']} test. Please review.",
                'lab', $testId);
        }
        // Also mark test as 'Submitted'
        mysqli_query($conn,"UPDATE lab_tests SET status='Submitted', updated_at=NOW() WHERE id=$testId");
    }
    echo json_encode(['success'=>true]);
    break;

// ── New appointment booking (patient → doctor) ────────────
case 'new_appointment':
    $apptId  = (int)($body['appointment_id'] ?? 0);
    if (!$apptId) { echo json_encode(['success'=>false,'error'=>'Missing appointment_id']); exit; }
    $ap = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT a.*,u.name AS pname FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id WHERE a.id=$apptId LIMIT 1"));
    if ($ap) {
        $docUid = getDoctorUserId($conn, $ap['doctor_id']);
        if ($docUid) {
            insertNotif($conn,$docUid,'doctor','appointment',
                "New Appointment Request",
                "{$ap['pname']} has booked an appointment for ".date('d M Y',strtotime($ap['appointment_date']))." at {$ap['appointment_time']}.",
                'appointments', $apptId);
        }
    }
    echo json_encode(['success'=>true]);
    break;

// ── Nurse acknowledges doctor instruction ─────────────────
case 'nurse_acknowledged':
    $notifId = (int)($body['original_notif_id'] ?? 0);
    $docUid  = (int)($body['doctor_user_id'] ?? 0);
    $nurseName = esc($conn,$body['nurse_name'] ?? 'Nurse');
    $task    = esc($conn,$body['task_description'] ?? 'your instruction');
    if ($docUid) {
        insertNotif($conn,$docUid,'doctor','system',
            "Instruction Acknowledged — $nurseName",
            "$nurseName has acknowledged and completed: $task",
            'staff', $notifId ?: null);
    }
    echo json_encode(['success'=>true]);
    break;

// ── Medicine expiry / stock alert (called by cron or on-page-load) ──
case 'inventory_alert':
    if (!in_array($userRole,['admin','pharmacist','doctor'])) {
        echo json_encode(['success'=>false,'error'=>'Restricted']); exit;
    }
    // Find all doctors to notify
    $doctors = mysqli_query($conn,"SELECT d.user_id FROM doctors d WHERE d.is_available=1 LIMIT 20");
    $doc_uids = [];
    if ($doctors) while($dr=mysqli_fetch_assoc($doctors)) $doc_uids[]=(int)$dr['user_id'];

    // Expiring soon
    $expiring = mysqli_query($conn,"SELECT medicine_name FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND expiry_alert_sent=0 LIMIT 10");
    if ($expiring) while($m=mysqli_fetch_assoc($expiring)){
        $mn=esc($conn,$m['medicine_name']);
        foreach($doc_uids as $du){
            // Dedup: don't send if already notified today
            $exists=(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$du AND title LIKE 'Expiring%$mn%' AND DATE(created_at)=CURDATE()"))[0]??0);
            if(!$exists) insertNotif($conn,$du,'doctor','inventory',"Expiring Soon: $m[medicine_name]","$m[medicine_name] is expiring within 30 days. Check inventory.",'medicine');
        }
        mysqli_query($conn,"UPDATE medicines SET expiry_alert_sent=1 WHERE medicine_name='$mn'");
    }

    // Out of stock
    $oos = mysqli_query($conn,"SELECT medicine_name FROM medicines WHERE stock_quantity=0 AND out_of_stock_alert_sent=0 LIMIT 10");
    if ($oos) while($m=mysqli_fetch_assoc($oos)){
        $mn=esc($conn,$m['medicine_name']);
        foreach($doc_uids as $du){
            $exists=(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$du AND title LIKE 'Out of Stock%$mn%' AND DATE(created_at)=CURDATE()"))[0]??0);
            if(!$exists) insertNotif($conn,$du,'doctor','inventory',"Out of Stock: $m[medicine_name]","$m[medicine_name] is now completely out of stock. Patients cannot be prescribed this medicine.",'medicine');
        }
        mysqli_query($conn,"UPDATE medicines SET out_of_stock_alert_sent=1 WHERE medicine_name='$mn'");
    }

    echo json_encode(['success'=>true]);
    break;

default:
    echo json_encode(['success'=>false,'error'=>"Unknown event: $event"]);
}
