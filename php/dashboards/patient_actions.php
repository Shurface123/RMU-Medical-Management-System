<?php
// ============================================================
// PATIENT DASHBOARD — AJAX HANDLER
// /php/dashboards/patient_actions.php
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role']??$_SESSION['role']??'') !== 'patient') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}
require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');

$user_id = (int)$_SESSION['user_id'];
$pr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id, patient_id FROM patients WHERE user_id=$user_id LIMIT 1"));
$pat_pk = $pr ? (int)$pr['id'] : 0;
if (!$pat_pk) { echo json_encode(['success'=>false,'message'=>'Patient record not found']); exit; }

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$post   = array_merge($_POST, $body);
$action = $post['action'] ?? $_GET['action'] ?? '';

function esc($conn,$v){ return mysqli_real_escape_string($conn,(string)$v); }
function ok($d=[]){ echo json_encode(array_merge(['success'=>true],$d)); exit; }
function fail($m){ echo json_encode(['success'=>false,'message'=>$m]); exit; }

switch ($action) {

// ══════════════════════════════════════════════════════════
// MODULE 2: BOOK APPOINTMENT
// ══════════════════════════════════════════════════════════
case 'get_doctors':
    $doctors=[];
    $q=mysqli_query($conn,"SELECT d.id, d.specialization, d.availability_status, u.name
      FROM doctors d JOIN users u ON d.user_id=u.id WHERE d.is_available=1 ORDER BY u.name");
    if($q) while($r=mysqli_fetch_assoc($q)) $doctors[]=$r;
    ok(['doctors'=>$doctors]);

case 'get_doctor_slots':
    $doc_id=(int)($post['doctor_id']??0);
    $date=esc($conn,$post['date']??'');
    if(!$doc_id||empty($date)) fail('Doctor and date required');
    $dayName=date('l',strtotime($date));
    // Get doctor availability for this day
    $avail=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM doctor_availability WHERE doctor_id=$doc_id AND day_of_week='$dayName' AND is_available=1 LIMIT 1"));
    if(!$avail) ok(['slots'=>[],'message'=>'Doctor not available on '.$dayName]);
    // Check leave exceptions
    $leave=mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM doctor_leave_exceptions WHERE doctor_id=$doc_id AND exception_date='$date' LIMIT 1"));
    if($leave) ok(['slots'=>[],'message'=>'Doctor is on leave on this date']);
    // Generate time slots
    $start=strtotime($avail['start_time']); $end=strtotime($avail['end_time']);
    $duration=((int)$avail['slot_duration_min'])*60;
    $maxAppts=(int)$avail['max_appointments'];
    // Count existing appointments for this date
    $booked=[];
    $bq=mysqli_query($conn,"SELECT appointment_time FROM appointments WHERE doctor_id=$doc_id AND appointment_date='$date' AND status NOT IN('Cancelled','No-Show')");
    if($bq) while($r=mysqli_fetch_assoc($bq)) $booked[]=substr($r['appointment_time'],0,5);
    $totalBooked=count($booked);
    $slots=[];
    for($t=$start;$t<$end&&$totalBooked<$maxAppts;$t+=$duration){
      $timeStr=date('H:i',$t);
      if(!in_array($timeStr,$booked)) $slots[]=$timeStr;
    }
    ok(['slots'=>$slots,'day'=>$dayName,'start'=>date('H:i',$start),'end'=>date('H:i',$end)]);

case 'book_appointment':
    $doc_id=(int)($post['doctor_id']??0);
    $date=esc($conn,$post['date']??'');
    $time=esc($conn,$post['time']??'');
    $reason=esc($conn,$post['reason']??'');
    $service=esc($conn,$post['service_type']??'Consultation');
    if(!$doc_id||empty($date)||empty($time)) fail('Doctor, date and time required');
    if(strtotime($date)<strtotime('today')) fail('Cannot book in the past');
    // Double-booking check
    $exists=mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM appointments WHERE doctor_id=$doc_id AND appointment_date='$date' AND appointment_time='$time' AND status NOT IN('Cancelled','No-Show') LIMIT 1"));
    if($exists) fail('This time slot is already taken. Please choose another.');
    // Also check patient hasn't already booked same day/doctor
    $selfBook=mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM appointments WHERE patient_id=$pat_pk AND doctor_id=$doc_id AND appointment_date='$date' AND status NOT IN('Cancelled','No-Show') LIMIT 1"));
    if($selfBook) fail('You already have an appointment with this doctor on this date');
    // Generate appointment ID
    $apptId='APT-'.strtoupper(substr(md5(uniqid()),0,8));
    $stmt=$conn->prepare("INSERT INTO appointments(appointment_id,patient_id,doctor_id,appointment_date,appointment_time,service_type,reason,status,created_at) VALUES(?,?,?,?,?,?,?,'Pending',NOW())");
    $stmt->bind_param("siissss",$apptId,$pat_pk,$doc_id,$date,$time,$service,$reason);
    $stmt->execute();
    // Notify doctor
    $docUid=(int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id FROM doctors WHERE id=$doc_id"))['user_id']??0);
    $patName=esc($conn,$_SESSION['user_name']??'Patient');
    if($docUid){
      mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,created_at)
        VALUES($docUid,'doctor','appointment','New Appointment Request','$patName has requested an appointment on ".date('d M Y',strtotime($date))." at $time.',0,'appointments',NOW())");
    }
    ok(['message'=>'Appointment booked! Status: Pending','appointment_id'=>$apptId]);

// ══════════════════════════════════════════════════════════
// MODULE 3: MY APPOINTMENTS
// ══════════════════════════════════════════════════════════
case 'cancel_appointment':
    $id=(int)($post['id']??0);
    $reason=esc($conn,$post['reason']??'');
    if(!$id) fail('Invalid appointment');
    $appt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM appointments WHERE id=$id AND patient_id=$pat_pk LIMIT 1"));
    if(!$appt) fail('Appointment not found');
    if(!in_array($appt['status'],['Pending','Confirmed','Approved'])) fail('Cannot cancel this appointment');
    mysqli_query($conn,"UPDATE appointments SET status='Cancelled',cancellation_reason='$reason',cancelled_by=$user_id,updated_at=NOW() WHERE id=$id");
    // Notify doctor
    $docUid=(int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id FROM doctors WHERE id={$appt['doctor_id']}"))['user_id']??0);
    $patName=esc($conn,$_SESSION['user_name']??'Patient');
    if($docUid){
      mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,created_at)
        VALUES($docUid,'doctor','appointment','Appointment Cancelled','$patName has cancelled their appointment on ".date('d M',strtotime($appt['appointment_date'])).".',0,'appointments',NOW())");
    }
    ok(['message'=>'Appointment cancelled']);

// ══════════════════════════════════════════════════════════
// MODULE 4: PRESCRIPTIONS
// ══════════════════════════════════════════════════════════
case 'request_refill':
    $rx_id=(int)($post['prescription_id']??0);
    $notes=esc($conn,$post['notes']??'');
    if(!$rx_id) fail('Invalid prescription');
    $rx=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM prescriptions WHERE id=$rx_id AND patient_id=$pat_pk LIMIT 1"));
    if(!$rx) fail('Prescription not found');
    if((int)$rx['refill_count']>=(int)$rx['refills_allowed']&&(int)$rx['refills_allowed']>0) fail('Maximum refills reached');
    // Check existing pending refill
    $existing=mysqli_fetch_assoc(mysqli_query($conn,"SELECT refill_id FROM prescription_refills WHERE prescription_id=$rx_id AND patient_id=$pat_pk AND status='Pending' LIMIT 1"));
    if($existing) fail('You already have a pending refill request for this prescription');
    $rxPid=esc($conn,$rx['prescription_id']);
    mysqli_query($conn,"INSERT INTO prescription_refills(prescription_id,patient_id,request_date,status,notes) VALUES('$rxPid',$pat_pk,NOW(),'Pending','$notes')");
    mysqli_query($conn,"UPDATE prescriptions SET status='Refill Requested',updated_at=NOW() WHERE id=$rx_id");
    // Notify doctor
    $docUid=(int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT user_id FROM doctors WHERE id={$rx['doctor_id']}"))['user_id']??0);
    $patName=esc($conn,$_SESSION['user_name']??'Patient');
    if($docUid){
      $med=esc($conn,$rx['medication_name']);
      mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,created_at)
        VALUES($docUid,'doctor','prescription','Refill Request','$patName has requested a refill for $med.',0,'prescriptions',NOW())");
    }
    ok(['message'=>'Refill request sent to your doctor']);

// ══════════════════════════════════════════════════════════
// MODULE 7: EMERGENCY CONTACTS
// ══════════════════════════════════════════════════════════
case 'add_emergency_contact':
    $name=esc($conn,$post['contact_name']??'');
    $rel=esc($conn,$post['relationship']??'');
    $phone=esc($conn,$post['phone']??'');
    $email=esc($conn,$post['email']??'');
    $addr=esc($conn,$post['address']??'');
    $primary=(int)($post['is_primary']??0);
    if(empty($name)||empty($rel)||empty($phone)) fail('Name, relationship and phone are required');
    if($primary) mysqli_query($conn,"UPDATE emergency_contacts SET is_primary=0 WHERE patient_id=$pat_pk");
    $stmt=$conn->prepare("INSERT INTO emergency_contacts(patient_id,contact_name,relationship,phone,email,address,is_primary) VALUES(?,?,?,?,?,?,?)");
    $stmt->bind_param("isssssi",$pat_pk,$name,$rel,$phone,$email,$addr,$primary);
    $stmt->execute();
    ok(['id'=>(int)$conn->insert_id,'message'=>'Emergency contact added']);

case 'update_emergency_contact':
    $id=(int)($post['id']??0);
    if(!$id) fail('Invalid ID');
    $name=esc($conn,$post['contact_name']??'');
    $rel=esc($conn,$post['relationship']??'');
    $phone=esc($conn,$post['phone']??'');
    $email=esc($conn,$post['email']??'');
    $addr=esc($conn,$post['address']??'');
    $primary=(int)($post['is_primary']??0);
    if(empty($name)||empty($phone)) fail('Name and phone are required');
    if($primary) mysqli_query($conn,"UPDATE emergency_contacts SET is_primary=0 WHERE patient_id=$pat_pk");
    mysqli_query($conn,"UPDATE emergency_contacts SET contact_name='$name',relationship='$rel',phone='$phone',email='$email',address='$addr',is_primary=$primary,updated_at=NOW() WHERE id=$id AND patient_id=$pat_pk");
    ok(['message'=>'Contact updated']);

case 'delete_emergency_contact':
    $id=(int)($post['id']??0);
    if(!$id) fail('Invalid ID');
    mysqli_query($conn,"DELETE FROM emergency_contacts WHERE id=$id AND patient_id=$pat_pk");
    ok(['message'=>'Contact deleted']);

// ══════════════════════════════════════════════════════════
// MODULE 8: NOTIFICATIONS
// ══════════════════════════════════════════════════════════
case 'mark_notification_read':
    $nid=(int)($post['id']??0);
    if($nid) mysqli_query($conn,"UPDATE notifications SET is_read=1,read_at=NOW() WHERE notification_id=$nid AND user_id=$user_id");
    ok();

case 'mark_all_read':
    mysqli_query($conn,"UPDATE notifications SET is_read=1,read_at=NOW() WHERE user_id=$user_id AND is_read=0");
    ok(['message'=>'All notifications marked as read']);

case 'get_notifications':
    $limit=(int)($_GET['limit']??50);
    $filter=$_GET['filter']??'all';
    $where="user_id=$user_id";
    if($filter==='unread') $where.=" AND is_read=0";
    $q=mysqli_query($conn,"SELECT * FROM notifications WHERE $where ORDER BY created_at DESC LIMIT $limit");
    $notifs=[];
    if($q) while($r=mysqli_fetch_assoc($q)) $notifs[]=$r;
    $unread=(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND is_read=0"))[0]??0);
    ok(['notifications'=>$notifs,'unread'=>$unread]);

// ══════════════════════════════════════════════════════════
// MODULE 9: SETTINGS
// ══════════════════════════════════════════════════════════
case 'update_profile':
    $name=esc($conn,$post['name']??''); $phone=esc($conn,$post['phone']??'');
    $dob=esc($conn,$post['date_of_birth']??''); $gender=esc($conn,$post['gender']??'');
    $address=esc($conn,$post['address']??'');
    $blood=esc($conn,$post['blood_group']??'');
    $allergies=esc($conn,$post['allergies']??'');
    $chronic=esc($conn,$post['chronic_conditions']??'');
    if(empty($name)) fail('Name is required');
    mysqli_query($conn,"UPDATE users SET name='$name',phone='$phone',date_of_birth='$dob',gender='$gender',address='$address',updated_at=NOW() WHERE id=$user_id");
    mysqli_query($conn,"UPDATE patients SET blood_group='$blood',allergies='$allergies',chronic_conditions='$chronic',updated_at=NOW() WHERE id=$pat_pk");
    ok(['message'=>'Profile updated']);

case 'upload_profile_photo':
    if(empty($_FILES['photo'])) fail('No file');
    $f=$_FILES['photo'];
    if($f['error']!==UPLOAD_ERR_OK) fail('Upload error');
    if($f['size']>2*1024*1024) fail('Max 2MB');
    $allowed=['image/jpeg','image/png','image/webp'];
    if(!in_array(mime_content_type($f['tmp_name']),$allowed)) fail('Only JPG/PNG/WebP');
    $extMap=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; $ext=$extMap[mime_content_type($f['tmp_name'])]??'jpg';
    $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/profile_photos/';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    $fn='pat_'.$pat_pk.'_'.time().'.'.$ext;
    move_uploaded_file($f['tmp_name'],$dir.$fn);
    $path='uploads/profile_photos/'.$fn;
    mysqli_query($conn,"UPDATE users SET profile_image='".esc($conn,$path)."',updated_at=NOW() WHERE id=$user_id");
    ok(['photo_url'=>'/RMU-Medical-Management-System/'.$path]);

case 'change_password':
    $cur=$post['current_password']??''; $new=$post['new_password']??''; $conf=$post['confirm_password']??'';
    if($new!==$conf) fail('Passwords do not match');
    if(strlen($new)<8) fail('Password must be at least 8 characters');
    $row=mysqli_fetch_assoc(mysqli_query($conn,"SELECT password FROM users WHERE id=$user_id"));
    if(!$row||!password_verify($cur,$row['password'])) fail('Current password is incorrect');
    $hash=password_hash($new,PASSWORD_DEFAULT);
    mysqli_query($conn,"UPDATE users SET password='".esc($conn,$hash)."',updated_at=NOW() WHERE id=$user_id");
    ok(['message'=>'Password changed']);

case 'save_settings':
    $fields=['email_notifications','sms_notifications','appointment_reminders','prescription_alerts','lab_result_alerts','medical_record_alerts'];
    $sets=[];
    foreach($fields as $f) $sets[]="$f=".(int)($post[$f]??1);
    $vis=esc($conn,$post['profile_visibility']??'doctors_only');
    $lang=esc($conn,$post['language_preference']??'English');
    $setStr=implode(',',$sets).",profile_visibility='$vis',language_preference='$lang'";
    mysqli_query($conn,"INSERT INTO patient_settings(patient_id,".implode(',',$fields).",profile_visibility,language_preference)
      VALUES($pat_pk,".implode(',',array_map(function($f) use ($post){ return (int)($post[$f]??1); },$fields)).",'$vis','$lang')
      ON DUPLICATE KEY UPDATE $setStr,updated_at=NOW()");
    ok(['message'=>'Settings saved']);

default:
    fail('Unknown action: '.$action);
}
