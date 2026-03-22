<?php
// ============================================================
// DOCTOR AJAX ACTIONS HANDLER
// PHP/dashboards/doctor_actions.php
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}
require_once '../db_conn.php';
require_once __DIR__ . '/cross_notify.php';
$user_id = (int)$_SESSION['user_id'];

// Get doctor pk
$dr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM doctors WHERE user_id=$user_id LIMIT 1"));
$doc_pk = $dr ? (int)$dr['id'] : 0;

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$post  = array_merge($_POST, $body);
$action= $post['action'] ?? '';

function esc($conn,$v){ return mysqli_real_escape_string($conn,(string)$v); }
function ok($d=[]){ echo json_encode(array_merge(['success'=>true],$d)); exit; }
function fail($m){ echo json_encode(['success'=>false,'message'=>$m]); exit; }
function notify($conn,$uid,$role,$type,$title,$msg,$module,$relId=null){
    $uid=(int)$uid; $role=esc($conn,$role); $type=esc($conn,$type);
    $title=esc($conn,$title); $msg=esc($conn,$msg); $module=esc($conn,$module);
    $rel=$relId?"$relId":'NULL';
    mysqli_query($conn,"INSERT INTO notifications (user_id,user_role,type,title,message,is_read,related_module,related_id,created_at)
      VALUES($uid,'$role','$type','$title','$msg',0,'$module',$rel,NOW())");
}
function getPatientUserId($conn,$patient_id){
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id FROM patients WHERE id=$patient_id LIMIT 1"));
    return $r?(int)$r['user_id']:0;
}

switch ($action) {

// ── Approve Appointment ──────────────────────────────────
case 'approve_appointment':
    $id=(int)($post['id']??0);
    if(!$id) fail('Invalid appointment ID');
    $q=mysqli_query($conn,"SELECT a.*, p.user_id AS pat_uid FROM appointments a JOIN patients p ON a.patient_id=p.id WHERE a.id=$id AND a.doctor_id=$doc_pk");
    $a=mysqli_fetch_assoc($q);
    if(!$a) fail('Appointment not found');
    mysqli_query($conn,"UPDATE appointments SET status='Confirmed',updated_at=NOW() WHERE id=$id");
    $dr2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM doctors WHERE id=$doc_pk"));
    $dn=$dr2['full_name']??'Your Doctor';
    notify($conn,$a['pat_uid'],'patient','appointment','Appointment Confirmed',
      "Dr. $dn has confirmed your appointment on {$a['appointment_date']} at {$a['appointment_time']}.","appointments",$id);
    ok(['message'=>'Appointment confirmed']);

// ── Reschedule Appointment ────────────────────────────────
case 'reschedule_appointment':
    $id=(int)($post['id']??0); $nd=esc($conn,$post['new_date']??''); $nt=esc($conn,$post['new_time']??''); $r=esc($conn,$post['reason']??'');
    if(!$id||!$nd||!$nt||!$r) fail('Missing fields');
    $q=mysqli_query($conn,"SELECT a.*, p.user_id AS pat_uid FROM appointments a JOIN patients p ON a.patient_id=p.id WHERE a.id=$id AND a.doctor_id=$doc_pk");
    $a=mysqli_fetch_assoc($q);
    if(!$a) fail('Appointment not found');
    mysqli_query($conn,"UPDATE appointments SET status='Rescheduled',appointment_date='$nd',appointment_time='$nt',reschedule_date='$nd',reschedule_time='$nt',reschedule_reason='$r',notification_sent=0,updated_at=NOW() WHERE id=$id");
    $dr2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM doctors WHERE id=$doc_pk"));
    $dn=$dr2['full_name']??'Your Doctor';
    notify($conn,$a['pat_uid'],'patient','appointment','Appointment Rescheduled',
      "Dr. $dn rescheduled your appointment to $nd at $nt. Reason: $r","appointments",$id);
    ok(['message'=>'Rescheduled and patient notified']);

// ── Cancel Appointment ────────────────────────────────────
case 'cancel_appointment':
    $id=(int)($post['id']??0); $r=esc($conn,$post['reason']??'');
    if(!$id||!$r) fail('Missing fields');
    $q=mysqli_query($conn,"SELECT a.*, p.user_id AS pat_uid FROM appointments a JOIN patients p ON a.patient_id=p.id WHERE a.id=$id AND a.doctor_id=$doc_pk");
    $a=mysqli_fetch_assoc($q);
    if(!$a) fail('Appointment not found');
    mysqli_query($conn,"UPDATE appointments SET status='Cancelled',cancellation_reason='$r',cancelled_by=$user_id,notes=CONCAT(IFNULL(notes,''),' | Cancellation reason: $r'),updated_at=NOW() WHERE id=$id");
    $dr2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM doctors WHERE id=$doc_pk"));
    $dn=$dr2['full_name']??'Your Doctor';
    notify($conn,$a['pat_uid'],'patient','appointment','Appointment Cancelled',
      "Dr. $dn has cancelled your appointment. Reason: $r","appointments",$id);
    ok(['message'=>'Cancelled and patient notified']);

// ── Add Medical Record ────────────────────────────────────
case 'add_record':
    $pat_id=(int)($post['patient_id']??0); $vd=esc($conn,$post['visit_date']??date('Y-m-d'));
    $diag=esc($conn,$post['diagnosis']??''); $sym=esc($conn,$post['symptoms']??'');
    $treat=esc($conn,$post['treatment']??''); $notes=esc($conn,$post['notes']??'');
    $fu=(int)($post['follow_up_required']??0); $fud=esc($conn,$post['follow_up_date']??'');
    if(!$pat_id||!$diag||!$treat) fail('Missing required fields');
    $rec_id='REC-'.strtoupper(substr(md5(uniqid()),0,8));
    mysqli_query($conn,"INSERT INTO medical_records (record_id,patient_id,doctor_id,visit_date,diagnosis,symptoms,treatment,notes,follow_up_required,follow_up_date,created_at)
      VALUES('$rec_id',$pat_id,$doc_pk,'$vd','$diag','$sym','$treat','$notes',$fu,".($fud?"'$fud'":'NULL').",NOW())");
    $rid=(int)mysqli_insert_id($conn);
    if(!$rid) fail('Failed to create record');
    $pat_uid=getPatientUserId($conn,$pat_id);
    if($pat_uid) notify($conn,$pat_uid,'patient','system','New Medical Record','A new medical record has been added after your consultation.','records',$rid);
    ok(['record_id'=>$rec_id]);

// ── Request Lab Test ──────────────────────────────────────
case 'request_lab_test':
    $pat_id = (int)($post['patient_id']??0);
    $cat_id = (int)($post['test_catalog_id']??0);
    $priority = esc($conn, $post['priority']??'Routine');
    $notes = esc($conn, $post['clinical_notes']??'');

    if (!$pat_id || !$cat_id) fail('Missing required fields for lab test');

    $dr2 = mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM doctors WHERE id=$doc_pk"));
    $doc_name = $dr2['full_name'] ?? 'Doctor';

    // Insert into lab_test_orders
    $q = "INSERT INTO lab_test_orders (patient_id, doctor_id, test_catalog_id, priority, clinical_notes, status, created_at)
          VALUES ($pat_id, $doc_pk, $cat_id, '$priority', '$notes', 'Pending', NOW())";
    
    if (mysqli_query($conn, $q)) {
        $order_id = mysqli_insert_id($conn);
        
        // Notify all lab technicians
        $techs = mysqli_query($conn, "SELECT user_id FROM lab_technicians");
        if ($techs) {
            while ($t = mysqli_fetch_assoc($techs)) {
                $uid = (int)$t['user_id'];
                notify($conn, $uid, 'lab_technician', 'lab', 'New Lab Request', "Dr. $doc_name has ordered a new lab test (ORD-$order_id) with $priority priority.", 'lab', $order_id);
            }
        }
        ok(['message' => 'Lab request submitted']);
    } else {
        fail('Database error: ' . mysqli_error($conn));
    }

// ── Clarification from Doctor to Lab ────────────────────────
case 'lab_clarification':
    $order_id = (int)($post['order_id'] ?? 0);
    $msg_txt  = esc($conn, $post['message'] ?? '');
    if (!$order_id || !$msg_txt) fail('Missing required fields');

    $dr2 = mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM doctors WHERE id=$doc_pk"));
    $doc_name = $dr2['full_name'] ?? 'Doctor';

    // Notify all lab technicians
    $techs = mysqli_query($conn, "SELECT user_id FROM lab_technicians");
    if ($techs) {
        while ($t = mysqli_fetch_assoc($techs)) {
            $uid = (int)$t['user_id'];
            notify($conn, $uid, 'lab_technician', 'message', 'Doctor Clarification Request', "Dr. $doc_name asks about ORD-$order_id: $msg_txt", 'Messages', $order_id);
        }
    }
    ok(['message' => 'Clarification request sent to Lab']);

// ── Create Prescription ───────────────────────────────────
case 'create_prescription':
    $pat_id=(int)($post['patient_id']??0); $med=esc($conn,$post['medicine_name']??'');
    $dos=esc($conn,$post['dosage']??''); $freq=esc($conn,$post['frequency']??'');
    $dur=esc($conn,$post['duration']??''); $qty=(int)($post['quantity']??1);
    $inst=esc($conn,$post['instructions']??''); $pdate=esc($conn,$post['prescription_date']??date('Y-m-d'));
    if(!$pat_id||!$med||!$dos||!$freq||!$dur) fail('Missing required fields');
    $rx_id='RX-'.strtoupper(substr(md5(uniqid()),0,8));
    mysqli_query($conn,"INSERT INTO prescriptions (prescription_id,patient_id,doctor_id,prescription_date,medication_name,dosage,frequency,duration,instructions,quantity,status,created_at)
      VALUES('$rx_id',$pat_id,$doc_pk,'$pdate','$med','$dos','$freq','$dur','$inst',$qty,'Pending',NOW())");
    $rxid=(int)mysqli_insert_id($conn);
    if(!$rxid) fail('Could not create prescription');
    $pat_uid=getPatientUserId($conn,$pat_id);
    if($pat_uid) notify($conn,$pat_uid,'patient','prescription','New Prescription','A new prescription has been issued for you: '.$med,'prescriptions',$rxid);

    // ── Cross-Dashboard: Notify ALL pharmacists about new Rx ──
    $dr2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM doctors WHERE id=$doc_pk"));
    $drName=$dr2['full_name']??'A doctor';
    $pharmUsers=mysqli_query($conn,"SELECT u.id FROM users u WHERE u.user_role='pharmacist' AND u.is_active=1");
    while($pu=mysqli_fetch_assoc($pharmUsers)){
        notify($conn,$pu['id'],'pharmacist','prescription','New Prescription Pending',
          "Dr. $drName prescribed $med (Qty: $qty) — pending dispensing.",'prescriptions',$rxid);
    }

    // ── Stock availability warning for the doctor ──
    $stockWarn='';
    $stkC=mysqli_fetch_assoc(mysqli_query($conn,"SELECT stock_quantity,reorder_level FROM medicines WHERE medicine_name='$med' LIMIT 1"));
    if($stkC){
        if((int)$stkC['stock_quantity']===0) $stockWarn='⚠️ Warning: '.$med.' is OUT OF STOCK in the pharmacy.';
        elseif((int)$stkC['stock_quantity']<=$qty) $stockWarn='⚠️ Warning: Only '.$stkC['stock_quantity'].' units of '.$med.' available (you prescribed '.$qty.').';
        elseif((int)$stkC['stock_quantity']<=(int)$stkC['reorder_level']) $stockWarn='ℹ️ Note: '.$med.' is running low ('.$stkC['stock_quantity'].' units left).';
    }

    ok(['prescription_id'=>$rx_id,'stock_warning'=>$stockWarn]);

// ── Cancel Prescription ───────────────────────────────────
case 'cancel_prescription':
    $id=(int)($post['id']??0);
    if(!$id) fail('Invalid ID');
    $rx=mysqli_fetch_assoc(mysqli_query($conn,"SELECT medication_name,patient_id FROM prescriptions WHERE id=$id AND doctor_id=$doc_pk LIMIT 1"));
    if(!$rx) fail('Prescription not found');
    mysqli_query($conn,"UPDATE prescriptions SET status='Cancelled',updated_at=NOW() WHERE id=$id AND doctor_id=$doc_pk");
    $pat_uid=getPatientUserId($conn,(int)$rx['patient_id']);
    if($pat_uid) notify($conn,$pat_uid,'patient','prescription','Prescription Cancelled','Your prescription for '.$rx['medication_name'].' has been cancelled by your doctor.','prescriptions',$id);
    ok();

// ── Create Lab Request (Deprecated) ───────────────
case 'create_lab_request':
    fail('Lab module is currently disabled.');

// ── Review Lab Result (Deprecated) ───────────────
case 'review_lab':
    fail('Lab module is currently disabled.');

// ── Add Patient Note ──────────────────────────────────────
case 'add_patient_note':
    $pat_id=(int)($post['patient_id']??0); $note=esc($conn,$post['note']??''); $type=esc($conn,$post['note_type']??'General');
    if(!$pat_id||!$note) fail('Missing fields');
    $nid='NOTE-'.strtoupper(substr(md5(uniqid()),0,8));
    mysqli_query($conn,"INSERT INTO doctor_patient_notes (note_id,doctor_id,patient_id,note,note_type,is_private,created_at)
      VALUES('$nid',$doc_pk,$pat_id,'$note','$type',1,NOW())");
    ok(['note_id'=>$nid]);

// ── Assign Bed ────────────────────────────────────────────
case 'assign_bed':
    $pat_id=(int)($post['patient_id']??0); $bed_id=(int)($post['bed_id']??0);
    $reas=esc($conn,$post['reason']??''); $adate=esc($conn,$post['admission_date']??date('Y-m-d H:i:s'));
    if(!$pat_id||!$bed_id||!$reas) fail('Missing fields');
    $assign_id='BA-'.strtoupper(substr(md5(uniqid()),0,8));
    mysqli_query($conn,"INSERT INTO bed_assignments (assignment_id,patient_id,bed_id,admission_date,reason,status,created_at)
      VALUES('$assign_id',$pat_id,$bed_id,'$adate','$reas','Active',NOW())");
    mysqli_query($conn,"UPDATE beds SET status='Occupied',updated_at=NOW() WHERE id=$bed_id");
    $pat_uid=getPatientUserId($conn,$pat_id);
    if($pat_uid) notify($conn,$pat_uid,'patient','system','Bed Assigned','A bed has been assigned for your admission.','beds');
    ok(['assignment_id'=>$assign_id]);

// ── Send Staff Note (Deprecated) ──────────────────
case 'send_staff_note':
    fail('This feature is currently disabled.');

// ── Update Profile ────────────────────────────────────────
case 'update_profile':
    $name=esc($conn,$post['name']??''); $spec=esc($conn,$post['specialization']??'');
    $email=esc($conn,$post['email']??''); $phone=esc($conn,$post['phone']??'');
    $bio=esc($conn,$post['bio']??''); $lic=esc($conn,$post['license_number']??'');
    if(empty($name)) fail('Name is required');
    mysqli_query($conn,"UPDATE users SET name='$name',email='$email',phone='$phone',updated_at=NOW() WHERE id=$user_id");
    mysqli_query($conn,"UPDATE doctors SET specialization='$spec',bio='$bio',license_number='$lic',updated_at=NOW() WHERE user_id=$user_id");
    ok(['message'=>'Profile updated']);

// ── Change Password ───────────────────────────────────────
case 'change_password':
    $cur=($post['current_password']??''); $new=($post['new_password']??''); $conf=($post['confirm_password']??'');
    if($new!==$conf) fail('Passwords do not match');
    if(strlen($new)<8) fail('Password must be at least 8 characters');
    $row=mysqli_fetch_assoc(mysqli_query($conn,"SELECT password FROM users WHERE id=$user_id LIMIT 1"));
    if(!$row||!password_verify($cur,$row['password'])) fail('Current password is incorrect');
    $hash=password_hash($new,PASSWORD_DEFAULT);
    $hs=esc($conn,$hash);
    mysqli_query($conn,"UPDATE users SET password='$hs',updated_at=NOW() WHERE id=$user_id");
    ok(['message'=>'Password changed']);

// ── Update Availability ───────────────────────────────────
case 'update_availability':
    $days=esc($conn,$post['available_days']??''); $avail=(int)($post['is_available']??1);
    $hfrom=esc($conn,$post['hours_from']??'08:00'); $hto=esc($conn,$post['hours_to']??'17:00');
    $hours=$hfrom.'-'.$hto;
    mysqli_query($conn,"UPDATE doctors SET available_days='$days',available_hours='$hours',is_available=$avail,updated_at=NOW() WHERE user_id=$user_id");
    ok(['message'=>'Schedule updated']);

// ── Assign Task to Nurse (Deprecated) ─────────────
case 'assign_nurse_task':
    fail('Nurse module is currently disabled.');

// ── Approve Bed Transfer (Deprecated) ─────────────
case 'approve_bed_transfer':
    fail('Nurse module is currently disabled.');

// ── Create Prescription ──────────────────────────
case 'create_prescription_with_schedule':
    // Simplified to just create prescription without nurse schedule
    $pat_id=(int)($post['patient_id']??0); $med=esc($conn,$post['medicine_name']??'');
    $dos=esc($conn,$post['dosage']??''); $freq=esc($conn,$post['frequency']??'');
    $dur=esc($conn,$post['duration']??''); $qty=(int)($post['quantity']??1);
    $inst=esc($conn,$post['instructions']??'');
    if(!$pat_id||!$med||!$dos||!$freq) fail('Missing required fields');
    $rx_id='RX-'.strtoupper(substr(md5(uniqid()),0,8));
    mysqli_query($conn,"INSERT INTO prescriptions (prescription_id,patient_id,doctor_id,prescription_date,medication_name,dosage,frequency,duration,instructions,quantity,status,created_at)
      VALUES('$rx_id',$pat_id,$doc_pk,CURDATE(),'$med','$dos','$freq','$dur','$inst',$qty,'Active',NOW())");
    $rxid=(int)mysqli_insert_id($conn);
    if(!$rxid) fail('Could not create prescription');
    
    // Notify patient
    $pat_uid=getPatientUserId($conn,$pat_id);
    if($pat_uid) notify($conn,$pat_uid,'patient','prescription','New Prescription','A new prescription has been issued: '.$med,'prescriptions',$rxid);
    ok(['prescription_id'=>$rx_id]);

default:
    fail('Unknown action: '.$action);
}
