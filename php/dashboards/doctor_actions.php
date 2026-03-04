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

// ── Create Lab Request ────────────────────────────────────
case 'create_lab_request':
    $pat_id=(int)($post['patient_id']??0); $tn=esc($conn,$post['test_name']??'');
    $tc=esc($conn,$post['test_category']??''); $urg=esc($conn,$post['urgency_level']??'Routine');
    $td=esc($conn,$post['test_date']??date('Y-m-d')); $notes=esc($conn,$post['request_notes']??'');
    if(!$pat_id||!$tn) fail('Missing fields');
    $test_id='LAB-'.strtoupper(substr(md5(uniqid()),0,8));
    mysqli_query($conn,"INSERT INTO lab_tests (test_id,patient_id,doctor_id,test_name,test_category,test_date,urgency_level,request_notes,status,cost,created_at)
      VALUES('$test_id',$pat_id,$doc_pk,'$tn','$tc','$td','$urg','$notes','Pending',0.00,NOW())");
    $lid=(int)mysqli_insert_id($conn);
    if(!$lid) fail('Could not create lab request');
    // Notify all lab technicians
    $techs=mysqli_query($conn,"SELECT u.id FROM users u WHERE u.role='lab_technician' LIMIT 10");
    if($techs) while($t=mysqli_fetch_assoc($techs)){
        notify($conn,$t['id'],'lab_technician','system','New Lab Request',
          "Dr. has requested a '$tn' test (Urgency: $urg). Please process.","lab",$lid);
    }
    $pat_uid=getPatientUserId($conn,$pat_id);
    if($pat_uid) notify($conn,$pat_uid,'patient','system','Lab Test Requested','A lab test has been ordered for you: '.$tn,'lab',$lid);
    ok(['test_id'=>$test_id]);

// ── Review Lab Result ─────────────────────────────────────
case 'review_lab':
    $id=(int)($post['id']??0); $notes=esc($conn,$post['notes']??'');
    $makeAccessible=(int)($post['patient_accessible']??1);
    if(!$id) fail('Invalid ID');
    mysqli_query($conn,"UPDATE lab_tests SET status='Reviewed',updated_at=NOW() WHERE id=$id AND doctor_id=$doc_pk");
    if($notes) mysqli_query($conn,"UPDATE lab_results SET doctor_reviewed=1,doctor_notes='$notes',patient_accessible=$makeAccessible,patient_notified=$makeAccessible WHERE test_id=$id");
    else mysqli_query($conn,"UPDATE lab_results SET doctor_reviewed=1,patient_accessible=$makeAccessible,patient_notified=$makeAccessible WHERE test_id=$id");
    // Notify the patient that results are now accessible
    if($makeAccessible){
      $lt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT lt.test_name,lt.patient_id FROM lab_tests lt WHERE lt.id=$id LIMIT 1"));
      if($lt){
        $pat_uid=getPatientUserId($conn,(int)$lt['patient_id']);
        if($pat_uid) notify($conn,$pat_uid,'patient','lab','Lab Results Available',
          'Your '.$lt['test_name'].' lab results have been reviewed and are now available for you to view.','lab',$id);
      }
    }
    ok();

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

// ── Send Staff Note ───────────────────────────────────────
case 'send_staff_note':
    $tid=(int)($post['target_user_id']??0); $msg=esc($conn,$post['message']??'');
    if(!$tid||!$msg) fail('Missing fields');
    $dr2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM doctors WHERE id=$doc_pk"));
    $dn=$dr2['full_name']??'Doctor';
    notify($conn,$tid,'nurse','system',"Instruction from Dr. $dn",$msg,'staff');
    ok();

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

// ── Assign Task to Nurse ──────────────────────────────────
case 'assign_nurse_task':
    $nurse_id=(int)($post['nurse_id']??0);
    $pat_id=(int)($post['patient_id']??0);
    $title=esc($conn,$post['task_title']??'');
    $desc=esc($conn,$post['task_description']??'');
    $priority=esc($conn,$post['priority']??'Medium');
    $due=esc($conn,$post['due_time']??'');
    if(!$nurse_id||!$title) fail('Nurse ID and task title required');
    mysqli_query($conn,"INSERT INTO nurse_tasks (nurse_id,assigned_by,patient_id,task_title,task_description,priority,due_time,status,created_at)
      VALUES($nurse_id,$doc_pk,".($pat_id?:"NULL").",'$title','$desc','$priority',".($due?"'$due'":'NULL').",'Pending',NOW())");
    $tid=(int)mysqli_insert_id($conn);
    $dr2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM doctors WHERE id=$doc_pk"));
    $dn=$dr2['full_name']??'Doctor';
    notifyNurse($conn, $nurse_id, 'task', 'New Task Assigned',
      "Dr. $dn assigned you a new task: $title" . ($priority==='High'||$priority==='Urgent' ? " (Priority: $priority)" : ''), 'tasks', $tid,
      $priority==='Urgent' ? 'urgent' : ($priority==='High' ? 'high' : 'normal'));
    ok(['task_id'=>$tid, 'message'=>'Task assigned to nurse']);

// ── Approve Bed Transfer ──────────────────────────────────
case 'approve_bed_transfer':
    $tid=(int)($post['transfer_id']??0);
    $action_type=esc($conn,$post['approval']??'Approved');
    if(!$tid) fail('Transfer ID required');
    if(!in_array($action_type,['Approved','Rejected'])) fail('Invalid approval');
    $tr=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM bed_transfers WHERE id=$tid LIMIT 1"));
    if(!$tr) fail('Transfer not found');
    mysqli_query($conn,"UPDATE bed_transfers SET status='$action_type',approved_by=$doc_pk,approved_at=NOW() WHERE id=$tid");
    if($action_type==='Approved'){
      // Update bed assignments
      $pid=(int)$tr['patient_id']; $newBed=esc($conn,$tr['to_bed_id']); $newWard=esc($conn,$tr['to_ward']);
      mysqli_query($conn,"UPDATE bed_assignments SET status='Transferred',discharge_date=NOW() WHERE patient_id=$pid AND status='Active'");
      mysqli_query($conn,"UPDATE bed_management SET status='Available' WHERE bed_id='{$tr['from_bed_id']}'");
      mysqli_query($conn,"UPDATE bed_management SET status='Occupied' WHERE bed_id='$newBed'");
    }
    // Notify the requesting nurse
    $reqNurse=(int)$tr['requested_by'];
    $dr2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM doctors WHERE id=$doc_pk"));
    $dn=$dr2['full_name']??'Doctor';
    $pName=getPatientNameById($conn,(int)$tr['patient_id']);
    notifyNurse($conn, $reqNurse, 'bed_transfer', "Bed Transfer $action_type",
      "Dr. $dn has {$action_type} the bed transfer for $pName.", 'beds', $tid);
    ok(['message'=>"Transfer $action_type"]);

// ── Create Prescription + Nurse Med Schedule ──────────────
case 'create_prescription_with_schedule':
    $pat_id=(int)($post['patient_id']??0); $med=esc($conn,$post['medicine_name']??'');
    $dos=esc($conn,$post['dosage']??''); $freq=esc($conn,$post['frequency']??'');
    $dur=esc($conn,$post['duration']??''); $qty=(int)($post['quantity']??1);
    $inst=esc($conn,$post['instructions']??''); $nurse_id=(int)($post['nurse_id']??0);
    if(!$pat_id||!$med||!$dos||!$freq) fail('Missing required fields');
    $rx_id='RX-'.strtoupper(substr(md5(uniqid()),0,8));
    mysqli_query($conn,"INSERT INTO prescriptions (prescription_id,patient_id,doctor_id,prescription_date,medication_name,dosage,frequency,duration,instructions,quantity,status,created_at)
      VALUES('$rx_id',$pat_id,$doc_pk,CURDATE(),'$med','$dos','$freq','$dur','$inst',$qty,'Active',NOW())");
    $rxid=(int)mysqli_insert_id($conn);
    if(!$rxid) fail('Could not create prescription');
    // Create medication administration schedule entries for the nurse
    $times=['08:00','12:00','18:00','22:00'];
    $freq_map=['once daily'=>1,'od'=>1,'twice daily'=>2,'bd'=>2,'three times daily'=>3,'tds'=>3,'four times daily'=>4,'qds'=>4];
    $freq_count=$freq_map[strtolower($freq)]??1;
    for($i=0;$i<min($freq_count,4);$i++){
      $sched_time=date('Y-m-d').' '.$times[$i].':00';
      mysqli_query($conn,"INSERT INTO medication_administration (patient_id,nurse_id,prescription_id,medicine_name,dosage,route,scheduled_time,status,created_at)
        VALUES($pat_id,".($nurse_id?:"NULL").",$rxid,'$med','$dos','Oral','$sched_time','Pending',NOW())");
    }
    // Notify patient
    $pat_uid=getPatientUserId($conn,$pat_id);
    if($pat_uid) notify($conn,$pat_uid,'patient','prescription','New Prescription','A new prescription has been issued: '.$med,'prescriptions',$rxid);
    // Notify pharmacists
    $dr2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM doctors WHERE id=$doc_pk"));
    $drName=$dr2['full_name']??'Doctor';
    $pharmUsers=mysqli_query($conn,"SELECT u.id FROM users u WHERE u.user_role='pharmacist' AND u.is_active=1");
    while($pu=mysqli_fetch_assoc($pharmUsers)){
      notify($conn,$pu['id'],'pharmacist','prescription','New Prescription',"Dr. $drName prescribed $med — pending dispensing.",'prescriptions',$rxid);
    }
    // Notify assigned nurse
    if($nurse_id){
      notifyNurse($conn, $nurse_id, 'medication', 'New Medication Schedule',
        "Dr. $drName prescribed $med ($dos, $freq) for ".getPatientNameById($conn,$pat_id).". Added to your medication schedule.", 'medications', $rxid);
    }
    ok(['prescription_id'=>$rx_id]);

default:
    fail('Unknown action: '.$action);
}
