<?php
// ============================================================
// MODULE 13: DOCTOR PROFILE AJAX HANDLER
// /php/dashboards/doctor_profile_actions.php
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'doctor') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}
require_once '../db_conn.php';

$user_id = (int)$_SESSION['user_id'];
$dr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM doctors WHERE user_id=$user_id LIMIT 1"));
$doc_pk = $dr ? (int)$dr['id'] : 0;
if (!$doc_pk) { echo json_encode(['success'=>false,'message'=>'Doctor record not found']); exit; }

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$post   = array_merge($_POST, $body);
$action = $post['action'] ?? $_GET['action'] ?? '';
$ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$device = $_SERVER['HTTP_USER_AGENT'] ?? '';

function esc($conn,$v){ return mysqli_real_escape_string($conn,(string)$v); }
function ok($d=[]){ echo json_encode(array_merge(['success'=>true],$d)); exit; }
function fail($m){ echo json_encode(['success'=>false,'message'=>$m]); exit; }
function logActivity($conn,$doc_pk,$action,$ip,$device){
    $a=esc($conn,$action); $i=esc($conn,$ip); $d=esc($conn,substr($device,0,200));
    mysqli_query($conn,"INSERT INTO doctor_activity_log(doctor_id,action,ip_address,device) VALUES($doc_pk,'$a','$i','$d')");
}
function updateCompleteness($conn,$doc_pk,$user_id){
    // Calculate profile completeness
    $d=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM doctors WHERE id=$doc_pk"));
    $u=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id=$user_id"));
    $personal = (!empty($u['name']) && !empty($u['phone']) && !empty($u['email']) && !empty($u['date_of_birth']) && !empty($u['gender'])) ? 1 : 0;
    $professional = (!empty($d['specialization']) && !empty($d['license_number']) && $d['experience_years']>0) ? 1 : 0;
    $quals = (int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM doctor_qualifications WHERE doctor_id=$doc_pk"))[0] ?? 0) > 0 ? 1 : 0;
    $avail = (int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM doctor_availability WHERE doctor_id=$doc_pk AND is_available=1"))[0] ?? 0) > 0 ? 1 : 0;
    $photo = (!empty($u['profile_image']) && $u['profile_image']!=='default-avatar.png') ? 1 : 0;
    $security = 1; // password always set
    $docs = (int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM doctor_documents WHERE doctor_id=$doc_pk"))[0] ?? 0) > 0 ? 1 : 0;
    $total = $personal + $professional + $quals + $avail + $photo + $security + $docs;
    $pct = (int)round(($total / 7) * 100);
    mysqli_query($conn,"INSERT INTO doctor_profile_completeness(doctor_id,personal_info,professional_profile,qualifications,availability_set,photo_uploaded,security_setup,documents_uploaded,overall_pct)
      VALUES($doc_pk,$personal,$professional,$quals,$avail,$photo,$security,$docs,$pct)
      ON DUPLICATE KEY UPDATE personal_info=$personal,professional_profile=$professional,qualifications=$quals,availability_set=$avail,photo_uploaded=$photo,security_setup=$security,documents_uploaded=$docs,overall_pct=$pct,last_updated=NOW()");
    mysqli_query($conn,"UPDATE doctors SET profile_completion_pct=$pct WHERE id=$doc_pk");
    return $pct;
}

switch ($action) {

// ══════════════════════════════════════════════════════════
// SECTION B: PERSONAL INFORMATION
// ══════════════════════════════════════════════════════════
case 'update_personal_info':
    $name=esc($conn,$post['name']??''); $dob=esc($conn,$post['date_of_birth']??'');
    $gender=esc($conn,$post['gender']??''); $nationality=esc($conn,$post['nationality']??'');
    $marital=esc($conn,$post['marital_status']??''); $religion=esc($conn,$post['religion']??'');
    $nat_id=esc($conn,$post['national_id']??''); $phone=esc($conn,$post['phone']??'');
    $phone2=esc($conn,$post['secondary_phone']??''); $email=esc($conn,$post['email']??'');
    $pemail=esc($conn,$post['personal_email']??'');
    $street=esc($conn,$post['street_address']??''); $city=esc($conn,$post['city']??'');
    $region=esc($conn,$post['region']??''); $country=esc($conn,$post['country']??'');
    $postal=esc($conn,$post['postal_code']??''); $office=esc($conn,$post['office_location']??'');
    if(empty($name)) fail('Name is required');
    $stmt=$conn->prepare("UPDATE users SET name=?,email=?,phone=?,gender=?,date_of_birth=?,updated_at=NOW() WHERE id=?");
    $stmt->bind_param("sssssi",$name,$email,$phone,$gender,$dob,$user_id);
    $stmt->execute();
    $stmt2=$conn->prepare("UPDATE doctors SET nationality=?,marital_status=?,religion=?,national_id=?,secondary_phone=?,personal_email=?,street_address=?,city=?,region=?,country=?,postal_code=?,office_location=?,updated_at=NOW() WHERE id=?");
    $stmt2->bind_param("ssssssssssssi",$nationality,$marital,$religion,$nat_id,$phone2,$pemail,$street,$city,$region,$country,$postal,$office,$doc_pk);
    $stmt2->execute();
    logActivity($conn,$doc_pk,'Updated personal information',$ip,$device);
    updateCompleteness($conn,$doc_pk,$user_id);
    ok(['message'=>'Personal information updated']);

// ══════════════════════════════════════════════════════════
// SECTION C: PROFESSIONAL PROFILE
// ══════════════════════════════════════════════════════════
case 'update_professional':
    $spec=esc($conn,$post['specialization']??''); $subspec=esc($conn,$post['sub_specialization']??'');
    $dept=(int)($post['department_id']??0); $desig=esc($conn,$post['designation']??'');
    $title=esc($conn,$post['professional_title']??''); $exp=(int)($post['experience_years']??0);
    $lic=esc($conn,$post['license_number']??''); $licbody=esc($conn,$post['license_issuing_body']??'');
    $licexp=esc($conn,$post['license_expiry_date']??''); $school=esc($conn,$post['medical_school']??'');
    $gradyr=(int)($post['graduation_year']??0); $pg=esc($conn,$post['postgraduate_details']??'');
    $bio=esc($conn,$post['bio']??'');
    $langs=isset($post['languages_spoken']) ? (is_array($post['languages_spoken']) ? json_encode($post['languages_spoken']) : $post['languages_spoken']) : '[]';
    $langs=esc($conn,$langs);
    $deptVal=$dept>0?$dept:'NULL';
    $gradVal=$gradyr>0?$gradyr:'NULL';
    $licexpVal=!empty($licexp)?"'$licexp'":'NULL';
    mysqli_query($conn,"UPDATE doctors SET specialization='$spec',sub_specialization='$subspec',
      department_id=$deptVal,designation='$desig',professional_title='$title',experience_years=$exp,
      license_number='$lic',license_issuing_body='$licbody',license_expiry_date=$licexpVal,
      medical_school='$school',graduation_year=$gradVal,postgraduate_details='$pg',
      languages_spoken='$langs',bio='$bio',updated_at=NOW() WHERE id=$doc_pk");

    // Check license expiry — warn if within 60 days
    if(!empty($licexp)){
        $expDate=strtotime($licexp);
        $sixtyDays=strtotime('+60 days');
        if($expDate && $expDate <= $sixtyDays && $expDate > time()){
            $docName=mysqli_fetch_assoc(mysqli_query($conn,"SELECT name FROM users WHERE id=$user_id"))['name']??'Doctor';
            // Notify doctor
            $exists=(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND title LIKE 'License Expiring%' AND DATE(created_at)=CURDATE()"))[0]??0);
            if(!$exists){
                mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,created_at)
                  VALUES($user_id,'doctor','system','License Expiring Soon','Your medical license expires on $licexp. Please renew it promptly.',0,'profile',NOW())");
            }
            // Notify admins
            $admins=mysqli_query($conn,"SELECT id FROM users WHERE role='admin' LIMIT 5");
            if($admins) while($a=mysqli_fetch_assoc($admins)){
                $aex=(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM notifications WHERE user_id={$a['id']} AND title LIKE 'Doctor License%{$docName}%' AND DATE(created_at)=CURDATE()"))[0]??0);
                if(!$aex) mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,created_at)
                  VALUES({$a['id']},'admin','system','Doctor License Expiring — $docName','Dr. $docName\\'s medical license expires on $licexp.',0,'staff',NOW())");
            }
        }
    }
    logActivity($conn,$doc_pk,'Updated professional profile',$ip,$device);
    updateCompleteness($conn,$doc_pk,$user_id);
    ok(['message'=>'Professional profile updated']);

// ══════════════════════════════════════════════════════════
// SECTION A: PHOTO UPLOAD & STATUS TOGGLE
// ══════════════════════════════════════════════════════════
case 'upload_photo':
    if(empty($_FILES['photo'])) fail('No file uploaded');
    $f=$_FILES['photo'];
    if($f['error']!==UPLOAD_ERR_OK) fail('Upload error');
    if($f['size']>2*1024*1024) fail('File too large (max 2MB)');
    $allowed=['image/jpeg','image/png','image/webp'];
    $ftype=mime_content_type($f['tmp_name']);
    if(!in_array($ftype,$allowed)) fail('Only JPG, PNG, WebP allowed');
    $extMap=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; $ext=$extMap[$ftype]??'jpg';
    $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/profile_photos/';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    $fname='doc_'.$doc_pk.'_'.time().'.'.$ext;
    if(!move_uploaded_file($f['tmp_name'],$dir.$fname)) fail('Failed to save file');
    $path='uploads/profile_photos/'.$fname;
    $pesc=esc($conn,$path);
    mysqli_query($conn,"UPDATE users SET profile_image='$pesc',updated_at=NOW() WHERE id=$user_id");
    logActivity($conn,$doc_pk,'Updated profile photo',$ip,$device);
    updateCompleteness($conn,$doc_pk,$user_id);
    ok(['photo_url'=>'/RMU-Medical-Management-System/'.$path]);

case 'toggle_status':
    $status=esc($conn,$post['status']??'Offline');
    if(!in_array($status,['Online','Offline','Busy'])) fail('Invalid status');
    mysqli_query($conn,"UPDATE doctors SET availability_status='$status',updated_at=NOW() WHERE id=$doc_pk");
    logActivity($conn,$doc_pk,"Availability set to $status",$ip,$device);
    ok(['status'=>$status]);

// ══════════════════════════════════════════════════════════
// SECTION D: QUALIFICATIONS & CERTIFICATIONS
// ══════════════════════════════════════════════════════════
case 'add_qualification':
    $deg=esc($conn,$post['degree_name']??''); $inst=esc($conn,$post['institution']??'');
    $yr=(int)($post['year_awarded']??0);
    if(empty($deg)||empty($inst)) fail('Degree name and institution are required');
    $yrVal=$yr>0?$yr:'NULL';
    $certPath='';
    if(!empty($_FILES['cert_file']) && $_FILES['cert_file']['error']===UPLOAD_ERR_OK){
        $cf=$_FILES['cert_file'];
        if($cf['size']>5*1024*1024) fail('Certificate file too large (max 5MB)');
        $ext=pathinfo($cf['name'],PATHINFO_EXTENSION);
        $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/doctor_certs/';
        if(!is_dir($dir)) mkdir($dir,0755,true);
        $fn='qual_'.$doc_pk.'_'.time().'.'.$ext;
        move_uploaded_file($cf['tmp_name'],$dir.$fn);
        $certPath='uploads/doctor_certs/'.$fn;
    }
    $cp=esc($conn,$certPath);
    $stmt=$conn->prepare("INSERT INTO doctor_qualifications(doctor_id,degree_name,institution,year_awarded,cert_file_path) VALUES(?,?,?,?,?)");
    $yrBind=$yr>0?$yr:null;
    $stmt->bind_param("issis",$doc_pk,$deg,$inst,$yrBind,$cp);
    $stmt->execute();
    logActivity($conn,$doc_pk,"Added qualification: $deg",$ip,$device);
    updateCompleteness($conn,$doc_pk,$user_id);
    ok(['id'=>(int)$conn->insert_id]);

case 'delete_qualification':
    $id=(int)($post['id']??0);
    if(!$id) fail('Invalid ID');
    // Delete file if exists
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT cert_file_path FROM doctor_qualifications WHERE id=$id AND doctor_id=$doc_pk"));
    if($r && !empty($r['cert_file_path'])){
        $fp=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/'.$r['cert_file_path'];
        if(file_exists($fp)) unlink($fp);
    }
    mysqli_query($conn,"DELETE FROM doctor_qualifications WHERE id=$id AND doctor_id=$doc_pk");
    logActivity($conn,$doc_pk,'Deleted a qualification',$ip,$device);
    updateCompleteness($conn,$doc_pk,$user_id);
    ok();

case 'add_certification':
    $cn=esc($conn,$post['cert_name']??''); $org=esc($conn,$post['issuing_org']??'');
    $idate=esc($conn,$post['issue_date']??''); $edate=esc($conn,$post['expiry_date']??'');
    if(empty($cn)||empty($org)) fail('Certification name and org required');
    $certPath='';
    if(!empty($_FILES['cert_file']) && $_FILES['cert_file']['error']===UPLOAD_ERR_OK){
        $cf=$_FILES['cert_file'];
        if($cf['size']>5*1024*1024) fail('File too large (max 5MB)');
        $ext=pathinfo($cf['name'],PATHINFO_EXTENSION);
        $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/doctor_certs/';
        if(!is_dir($dir)) mkdir($dir,0755,true);
        $fn='cert_'.$doc_pk.'_'.time().'.'.$ext;
        move_uploaded_file($cf['tmp_name'],$dir.$fn);
        $certPath='uploads/doctor_certs/'.$fn;
    }
    $cp=esc($conn,$certPath);
    $idateVal=!empty($idate)?"'$idate'":'NULL'; $edateVal=!empty($edate)?"'$edate'":'NULL';
    mysqli_query($conn,"INSERT INTO doctor_certifications(doctor_id,cert_name,issuing_org,issue_date,expiry_date,cert_file_path)
      VALUES($doc_pk,'$cn','$org',$idateVal,$edateVal,'$cp')");
    // Check cert expiry
    if(!empty($edate) && strtotime($edate) <= strtotime('+60 days') && strtotime($edate) > time()){
        $exists=(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND title LIKE 'Certification Expiring%$cn%' AND DATE(created_at)=CURDATE()"))[0]??0);
        if(!$exists) mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,created_at)
          VALUES($user_id,'doctor','system','Certification Expiring: $cn','Your $cn certification expires on $edate. Please renew.',0,'profile',NOW())");
    }
    logActivity($conn,$doc_pk,"Added certification: $cn",$ip,$device);
    updateCompleteness($conn,$doc_pk,$user_id);
    ok(['id'=>(int)mysqli_insert_id($conn)]);

case 'delete_certification':
    $id=(int)($post['id']??0);
    if(!$id) fail('Invalid ID');
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT cert_file_path FROM doctor_certifications WHERE id=$id AND doctor_id=$doc_pk"));
    if($r && !empty($r['cert_file_path'])){
        $fp=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/'.$r['cert_file_path'];
        if(file_exists($fp)) unlink($fp);
    }
    mysqli_query($conn,"DELETE FROM doctor_certifications WHERE id=$id AND doctor_id=$doc_pk");
    logActivity($conn,$doc_pk,'Deleted a certification',$ip,$device);
    updateCompleteness($conn,$doc_pk,$user_id);
    ok();

// ══════════════════════════════════════════════════════════
// SECTION E: AVAILABILITY SCHEDULE
// ══════════════════════════════════════════════════════════
case 'save_availability':
    $schedule=$post['schedule']??[]; // array of {day,is_available,start,end,max,slot_min}
    if(!is_array($schedule)) fail('Invalid schedule data');
    foreach($schedule as $s){
        $day=esc($conn,$s['day']??'');
        $avail=(int)($s['is_available']??0);
        $st=esc($conn,$s['start_time']??'08:00');
        $et=esc($conn,$s['end_time']??'17:00');
        $mx=(int)($s['max_appointments']??20);
        $sd=(int)($s['slot_duration_min']??30);
        if(empty($day)) continue;
        mysqli_query($conn,"INSERT INTO doctor_availability(doctor_id,day_of_week,is_available,start_time,end_time,max_appointments,slot_duration_min)
          VALUES($doc_pk,'$day',$avail,'$st','$et',$mx,$sd)
          ON DUPLICATE KEY UPDATE is_available=$avail,start_time='$st',end_time='$et',max_appointments=$mx,slot_duration_min=$sd,updated_at=NOW()");
    }
    // Also update legacy available_days/hours on doctors table
    $avDays=[]; $hrs='08:00-17:00';
    $q=mysqli_query($conn,"SELECT day_of_week,start_time,end_time FROM doctor_availability WHERE doctor_id=$doc_pk AND is_available=1");
    if($q) while($r=mysqli_fetch_assoc($q)){ $avDays[]=substr($r['day_of_week'],0,3); $hrs=substr($r['start_time'],0,5).'-'.substr($r['end_time'],0,5); }
    $dayStr=esc($conn,implode(',',$avDays));
    $hrsStr=esc($conn,$hrs);
    mysqli_query($conn,"UPDATE doctors SET available_days='$dayStr',available_hours='$hrsStr',updated_at=NOW() WHERE id=$doc_pk");
    logActivity($conn,$doc_pk,'Updated availability schedule',$ip,$device);
    updateCompleteness($conn,$doc_pk,$user_id);
    ok(['message'=>'Availability schedule saved']);

case 'add_leave_exception':
    $dt=esc($conn,$post['exception_date']??''); $reason=esc($conn,$post['reason']??'');
    if(empty($dt)) fail('Date is required');
    if(strtotime($dt) < time()) fail('Date must be in the future');
    mysqli_query($conn,"INSERT INTO doctor_leave_exceptions(doctor_id,exception_date,reason) VALUES($doc_pk,'$dt','$reason')
      ON DUPLICATE KEY UPDATE reason='$reason'");
    logActivity($conn,$doc_pk,"Added leave exception: $dt",$ip,$device);
    ok(['id'=>(int)mysqli_insert_id($conn)]);

case 'delete_leave_exception':
    $id=(int)($post['id']??0);
    if(!$id) fail('Invalid ID');
    mysqli_query($conn,"DELETE FROM doctor_leave_exceptions WHERE id=$id AND doctor_id=$doc_pk");
    ok();

// ══════════════════════════════════════════════════════════
// SECTION G: ACCOUNT & SECURITY
// ══════════════════════════════════════════════════════════
case 'change_password':
    $cur=$post['current_password']??''; $new=$post['new_password']??''; $conf=$post['confirm_password']??'';
    if($new!==$conf) fail('Passwords do not match');
    if(strlen($new)<8) fail('Password must be at least 8 characters');
    $row=mysqli_fetch_assoc(mysqli_query($conn,"SELECT password FROM users WHERE id=$user_id"));
    if(!$row||!password_verify($cur,$row['password'])) fail('Current password is incorrect');
    $hash=password_hash($new,PASSWORD_DEFAULT);
    $stmt=$conn->prepare("UPDATE users SET password=?,updated_at=NOW() WHERE id=?");
    $stmt->bind_param("si",$hash,$user_id);
    $stmt->execute();
    logActivity($conn,$doc_pk,'Changed password',$ip,$device);
    ok(['message'=>'Password changed successfully']);

case 'update_email':
    $newemail=esc($conn,$post['new_email']??''); $pwd=$post['password']??'';
    if(empty($newemail)||!filter_var($newemail,FILTER_VALIDATE_EMAIL)) fail('Invalid email');
    $row=mysqli_fetch_assoc(mysqli_query($conn,"SELECT password FROM users WHERE id=$user_id"));
    if(!$row||!password_verify($pwd,$row['password'])) fail('Password is incorrect');
    $exists=(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM users WHERE email='$newemail' AND id!=$user_id"))[0]??0);
    if($exists) fail('This email is already in use');
    mysqli_query($conn,"UPDATE users SET email='$newemail',updated_at=NOW() WHERE id=$user_id");
    logActivity($conn,$doc_pk,"Updated email to $newemail",$ip,$device);
    ok(['message'=>'Email updated']);

case 'request_deactivation':
    $reason=esc($conn,$post['reason']??'');
    // Notify all admins
    $admins=mysqli_query($conn,"SELECT id FROM users WHERE role='admin' LIMIT 5");
    $docName=mysqli_fetch_assoc(mysqli_query($conn,"SELECT name FROM users WHERE id=$user_id"))['name']??'Doctor';
    if($admins) while($a=mysqli_fetch_assoc($admins)){
        mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,created_at)
          VALUES({$a['id']},'admin','system','Account Deactivation Request','Dr. $docName has requested account deactivation. Reason: $reason',0,'staff',NOW())");
    }
    logActivity($conn,$doc_pk,"Requested account deactivation: $reason",$ip,$device);
    ok(['message'=>'Deactivation request sent to admin']);

case 'get_activity_log':
    $limit=(int)($_GET['limit']??50);
    $q=mysqli_query($conn,"SELECT action,ip_address,device,created_at FROM doctor_activity_log WHERE doctor_id=$doc_pk ORDER BY created_at DESC LIMIT $limit");
    $log=[];
    if($q) while($r=mysqli_fetch_assoc($q)) $log[]=$r;
    ok(['log'=>$log]);

case 'get_sessions':
    $sid=session_id();
    $q=mysqli_query($conn,"SELECT id,device_info,browser,ip_address,login_time,last_active,is_current FROM doctor_sessions WHERE doctor_id=$doc_pk ORDER BY last_active DESC LIMIT 20");
    $sessions=[];
    if($q) while($r=mysqli_fetch_assoc($q)){
        $r['is_current']=(bool)$r['is_current'];
        $sessions[]=$r;
    }
    ok(['sessions'=>$sessions]);

case 'logout_session':
    $sid=(int)($post['session_id']??0);
    if(!$sid) fail('Invalid session');
    mysqli_query($conn,"DELETE FROM doctor_sessions WHERE id=$sid AND doctor_id=$doc_pk AND is_current=0");
    logActivity($conn,$doc_pk,'Logged out another session',$ip,$device);
    ok();

case 'logout_all_sessions':
    $currentSid=esc($conn,session_id());
    mysqli_query($conn,"DELETE FROM doctor_sessions WHERE doctor_id=$doc_pk AND session_id!='$currentSid'");
    logActivity($conn,$doc_pk,'Logged out all other sessions',$ip,$device);
    ok(['message'=>'All other sessions logged out']);

// ══════════════════════════════════════════════════════════
// SECTION H: NOTIFICATION PREFERENCES
// ══════════════════════════════════════════════════════════
case 'save_notification_prefs':
    $fields=['notif_new_appointment','notif_appt_reminders','notif_appt_cancellations','notif_lab_results',
             'notif_rx_refills','notif_record_updates','notif_nurse_messages','notif_inventory_alerts',
             'notif_license_expiry','notif_system_announcements'];
    $sets=[];
    foreach($fields as $f) $sets[]="$f=".(int)($post[$f]??1);
    $chan=esc($conn,$post['preferred_channel']??'dashboard');
    $lang=esc($conn,$post['preferred_language']??'English');
    $setStr=implode(',',$sets).",preferred_channel='$chan',preferred_language='$lang'";
    $fieldVals=array_map(function($f) use ($post){ return (int)($post[$f]??1); }, $fields);
    mysqli_query($conn,"INSERT INTO doctor_settings(doctor_id,$setStr,updated_at) VALUES($doc_pk,"
      .implode(',',$fieldVals).",'$chan','$lang',NOW())
      ON DUPLICATE KEY UPDATE $setStr,updated_at=NOW()");
    logActivity($conn,$doc_pk,'Updated notification preferences',$ip,$device);
    ok(['message'=>'Preferences saved']);

// ══════════════════════════════════════════════════════════
// SECTION I: DOCUMENTS
// ══════════════════════════════════════════════════════════
case 'upload_document':
    if(empty($_FILES['document'])) fail('No file uploaded');
    $f=$_FILES['document'];
    if($f['error']!==UPLOAD_ERR_OK) fail('Upload error');
    if($f['size']>10*1024*1024) fail('File too large (max 10MB)');
    $allowed=['application/pdf','image/jpeg','image/png','image/webp','application/msword',
              'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $ftype=mime_content_type($f['tmp_name']);
    if(!in_array($ftype,$allowed)) fail('File type not allowed');
    $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/doctor_docs/';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    $origName=pathinfo($f['name'],PATHINFO_FILENAME);
    $ext=pathinfo($f['name'],PATHINFO_EXTENSION);
    $fn='doc_'.$doc_pk.'_'.time().'_'.substr(md5(uniqid()),0,6).'.'.$ext;
    if(!move_uploaded_file($f['tmp_name'],$dir.$fn)) fail('Failed to save');
    $path='uploads/doctor_docs/'.$fn;
    $desc=esc($conn,$post['description']??'');
    $fname=esc($conn,$f['name']); $fpath=esc($conn,$path);
    $ftyp=esc($conn,$ext); $fsz=(int)$f['size'];
    mysqli_query($conn,"INSERT INTO doctor_documents(doctor_id,file_name,file_path,file_type,file_size,description) VALUES($doc_pk,'$fname','$fpath','$ftyp',$fsz,'$desc')");
    logActivity($conn,$doc_pk,"Uploaded document: {$f['name']}",$ip,$device);
    updateCompleteness($conn,$doc_pk,$user_id);
    ok(['id'=>(int)mysqli_insert_id($conn),'file_name'=>$f['name'],'file_path'=>'/RMU-Medical-Management-System/'.$path]);

case 'delete_document':
    $id=(int)($post['id']??0);
    if(!$id) fail('Invalid ID');
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT file_path FROM doctor_documents WHERE id=$id AND doctor_id=$doc_pk"));
    if($r && !empty($r['file_path'])){
        $fp=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/'.$r['file_path'];
        if(file_exists($fp)) unlink($fp);
    }
    mysqli_query($conn,"DELETE FROM doctor_documents WHERE id=$id AND doctor_id=$doc_pk");
    logActivity($conn,$doc_pk,'Deleted a document',$ip,$device);
    updateCompleteness($conn,$doc_pk,$user_id);
    ok();

// ══════════════════════════════════════════════════════════
// SECTION J: COMPLETENESS
// ══════════════════════════════════════════════════════════
case 'get_completeness':
    $pct=updateCompleteness($conn,$doc_pk,$user_id);
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM doctor_profile_completeness WHERE doctor_id=$doc_pk"));
    ok(['completeness'=>$r,'pct'=>$pct]);

// ══════════════════════════════════════════════════════════
// SECTION F: STATISTICS (read only)
// ══════════════════════════════════════════════════════════
case 'get_stats':
    $today=date('Y-m-d'); $mstart=date('Y-m-01');
    $s=[
        'total_patients'     =>(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id=$doc_pk"))[0]??0),
        'total_appointments' =>(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND status='Completed'"))[0]??0),
        'month_appointments' =>(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND appointment_date>='$mstart'"))[0]??0),
        'total_prescriptions'=>(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM prescriptions WHERE doctor_id=$doc_pk"))[0]??0),
        'month_prescriptions'=>(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM prescriptions WHERE doctor_id=$doc_pk AND prescription_date>='$mstart'"))[0]??0),
        'total_lab_requests' =>(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM lab_tests WHERE doctor_id=$doc_pk"))[0]??0),
        'month_lab_requests' =>(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM lab_tests WHERE doctor_id=$doc_pk AND created_at>='$mstart'"))[0]??0),
    ];
    // Busiest day
    $bd=mysqli_fetch_assoc(mysqli_query($conn,"SELECT DAYNAME(appointment_date) AS d,COUNT(*) AS c FROM appointments WHERE doctor_id=$doc_pk GROUP BY d ORDER BY c DESC LIMIT 1"));
    $s['busiest_day']=$bd['d']??'N/A';
    // Monthly chart data (last 6 months)
    $chart=[];
    for($i=5;$i>=0;$i--){
        $ms=date('Y-m-01',strtotime("-$i months")); $me=date('Y-m-t',strtotime("-$i months"));
        $cnt=(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND appointment_date BETWEEN '$ms' AND '$me'"))[0]??0);
        $chart[]=['label'=>date('M Y',strtotime($ms)),'value'=>$cnt];
    }
    $s['chart']=$chart;
    // Consultation hours this month
    $s['month_consult_hours']=round($s['month_appointments']*0.5,1);
    ok(['stats'=>$s]);

default:
    fail('Unknown action: '.$action);
}
