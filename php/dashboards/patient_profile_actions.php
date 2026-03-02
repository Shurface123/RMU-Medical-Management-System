<?php
// ============================================================
// PATIENT PROFILE — AJAX HANDLER
// /php/dashboards/patient_profile_actions.php
// Handles all Section A-H actions for Module 10
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../db_conn.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role']??$_SESSION['role']??'',['patient'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}
$user_id=(int)$_SESSION['user_id'];
$pr=mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM patients WHERE user_id=$user_id LIMIT 1"));
$pat_pk=(int)($pr['id']??0);
if(!$pat_pk){echo json_encode(['success'=>false,'message'=>'Patient record not found']);exit;}

function esc($c,$v){return mysqli_real_escape_string($c,(string)$v);}
function ok($d=[]){echo json_encode(array_merge(['success'=>true],$d));exit;}
function fail($m){echo json_encode(['success'=>false,'message'=>$m]);exit;}
function logActivity($conn,$pat_pk,$user_id,$type,$desc){
    $ip=esc($conn,$_SERVER['REMOTE_ADDR']??'');
    $dev=esc($conn,substr($_SERVER['HTTP_USER_AGENT']??'',0,250));
    $type=esc($conn,$type);$desc=esc($conn,$desc);
    mysqli_query($conn,"INSERT INTO patient_activity_log(patient_id,user_id,action_type,action_description,ip_address,device_info,created_at) VALUES($pat_pk,$user_id,'$type','$desc','$ip','$dev',NOW())");
}
function recalcCompleteness($conn,$pat_pk){
    $p=mysqli_fetch_assoc(mysqli_query($conn,"SELECT name,date_of_birth,gender,phone,address FROM patients WHERE id=$pat_pk"));
    $pi=($p&&$p['name']&&$p['date_of_birth']&&$p['gender']&&$p['phone'])?1:0;
    $mp=mysqli_fetch_assoc(mysqli_query($conn,"SELECT blood_type,height_cm,weight_kg FROM patient_medical_profile WHERE patient_id=$pat_pk"));
    $mpc=($mp&&$mp['blood_type']&&$mp['height_cm']&&$mp['weight_kg'])?1:0;
    $ec=(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM emergency_contacts WHERE patient_id=$pat_pk"))[0]??0)>0?1:0;
    $ins=mysqli_fetch_assoc(mysqli_query($conn,"SELECT provider_name,policy_number FROM patient_insurance WHERE patient_id=$pat_pk"));
    $inc=($ins&&$ins['provider_name']&&$ins['policy_number'])?1:0;
    $ph=mysqli_fetch_assoc(mysqli_query($conn,"SELECT profile_image FROM patients WHERE id=$pat_pk"));
    $phc=($ph&&$ph['profile_image']&&$ph['profile_image']!=='default-avatar.png')?1:0;
    $sec=0; // 2FA not implemented yet, just password
    $doc=(int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM patient_documents WHERE patient_id=$pat_pk"))[0]??0)>0?1:0;
    $total=($pi+$mpc+$ec+$inc+$phc+$sec+$doc);
    $pct=round(($total/7)*100);
    mysqli_query($conn,"INSERT INTO patient_profile_completeness(patient_id,personal_info,medical_profile,emergency_contact,insurance_info,photo_uploaded,security_setup,documents_uploaded,overall_percentage,last_updated) VALUES($pat_pk,$pi,$mpc,$ec,$inc,$phc,$sec,$doc,$pct,NOW()) ON DUPLICATE KEY UPDATE personal_info=$pi,medical_profile=$mpc,emergency_contact=$ec,insurance_info=$inc,photo_uploaded=$phc,security_setup=$sec,documents_uploaded=$doc,overall_percentage=$pct,last_updated=NOW()");
    mysqli_query($conn,"UPDATE patients SET profile_completion=$pct WHERE id=$pat_pk");
    return $pct;
}

// Determine if multipart or JSON
$isMultipart=str_contains(($_SERVER['CONTENT_TYPE']??''),'multipart');
if($isMultipart){$post=$_POST;}else{$post=array_merge($_POST,json_decode(file_get_contents('php://input'),true)??[]);}
$action=$post['action']??'';

switch($action){

// ── SECTION A: Upload Profile Photo ──────────────────────
case 'upload_profile_photo':
    if(!isset($_FILES['photo'])||$_FILES['photo']['error']!==UPLOAD_ERR_OK) fail('No file uploaded');
    $file=$_FILES['photo'];
    $allowed=['image/jpeg','image/png','image/webp'];
    if(!in_array($file['type'],$allowed)) fail('Only JPG, PNG, WEBP allowed');
    if($file['size']>2*1024*1024) fail('File must be under 2MB');
    $ext=pathinfo($file['name'],PATHINFO_EXTENSION);
    $fname='patient_'.$pat_pk.'_'.time().'.'.$ext;
    $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/patient_photos/';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    if(!move_uploaded_file($file['tmp_name'],$dir.$fname)) fail('Upload failed');
    $path='uploads/patient_photos/'.$fname;
    $pathE=esc($conn,$path);
    mysqli_query($conn,"UPDATE patients SET profile_image='$pathE',updated_at=NOW() WHERE id=$pat_pk");
    logActivity($conn,$pat_pk,$user_id,'profile_update','Profile photo updated');
    recalcCompleteness($conn,$pat_pk);
    ok(['photo_url'=>'/RMU-Medical-Management-System/'.$path]);

// ── SECTION B: Update Personal Info ──────────────────────
case 'update_personal_info':
    $fields=['name','date_of_birth','gender','marital_status','nationality','religion','occupation',
             'national_id','phone','secondary_phone','email','street_address','city','region','country','postal_code','address'];
    $sets=[];
    foreach($fields as $f){
        if(isset($post[$f])){$sets[]="$f='".esc($conn,$post[$f])."'";}
    }
    if(empty($sets)) fail('No fields to update');
    $sets[]="updated_at=NOW()";
    mysqli_query($conn,"UPDATE patients SET ".implode(',',$sets)." WHERE id=$pat_pk");
    // Also update users table name+email
    if(isset($post['name'])){$n=esc($conn,$post['name']);mysqli_query($conn,"UPDATE users SET name='$n' WHERE id=$user_id");}
    if(isset($post['email'])){$e=esc($conn,$post['email']);mysqli_query($conn,"UPDATE users SET email='$e' WHERE id=$user_id");}
    logActivity($conn,$pat_pk,$user_id,'profile_update','Personal information updated');
    $pct=recalcCompleteness($conn,$pat_pk);
    ok(['message'=>'Personal information saved','completeness'=>$pct]);

// ── SECTION C: Update Medical Profile ────────────────────
case 'update_medical_profile':
    $bt=esc($conn,$post['blood_type']??'');
    $h=floatval($post['height_cm']??0);
    $w=floatval($post['weight_kg']??0);
    $bmi=0;$bmiCat='';
    if($h>0&&$w>0){$bmi=round($w/(($h/100)**2),1);
      if($bmi<18.5)$bmiCat='Underweight';elseif($bmi<25)$bmiCat='Normal';elseif($bmi<30)$bmiCat='Overweight';else $bmiCat='Obese';
    }
    $allergies=esc($conn,json_encode($post['allergies']??[]));
    $chronic=esc($conn,json_encode($post['chronic_conditions']??[]));
    $disabilities=esc($conn,$post['disabilities']??'');
    $meds=esc($conn,json_encode($post['current_medications']??[]));
    $vacc=esc($conn,json_encode($post['vaccination_history']??[]));
    $fam=esc($conn,json_encode($post['family_medical_history']??[]));
    mysqli_query($conn,"INSERT INTO patient_medical_profile(patient_id,blood_type,height_cm,weight_kg,bmi,bmi_category,allergies,chronic_conditions,disabilities,current_medications,vaccination_history,family_medical_history,updated_at)
      VALUES($pat_pk,'$bt',$h,$w,$bmi,'$bmiCat','$allergies','$chronic','$disabilities','$meds','$vacc','$fam',NOW())
      ON DUPLICATE KEY UPDATE blood_type='$bt',height_cm=$h,weight_kg=$w,bmi=$bmi,bmi_category='$bmiCat',allergies='$allergies',chronic_conditions='$chronic',disabilities='$disabilities',current_medications='$meds',vaccination_history='$vacc',family_medical_history='$fam',updated_at=NOW()");
    // Also update patients.blood_group for compatibility
    if($bt) mysqli_query($conn,"UPDATE patients SET blood_group='$bt' WHERE id=$pat_pk");
    logActivity($conn,$pat_pk,$user_id,'profile_update','Medical profile updated');
    $pct=recalcCompleteness($conn,$pat_pk);
    ok(['message'=>'Medical profile saved','bmi'=>$bmi,'bmi_category'=>$bmiCat,'completeness'=>$pct]);

// ── SECTION D: Update Insurance ──────────────────────────
case 'update_insurance':
    $prov=esc($conn,$post['provider_name']??'');
    $pol=esc($conn,$post['policy_number']??'');
    $exp=esc($conn,$post['expiry_date']??'');
    $cov=esc($conn,$post['coverage_type']??'Individual');
    $pay=esc($conn,$post['payment_preference']??'Cash');
    $bill=esc($conn,$post['billing_address']??'');
    mysqli_query($conn,"INSERT INTO patient_insurance(patient_id,provider_name,policy_number,expiry_date,coverage_type,payment_preference,billing_address,updated_at)
      VALUES($pat_pk,'$prov','$pol',".($exp?"'$exp'":"NULL").",'$cov','$pay','$bill',NOW())
      ON DUPLICATE KEY UPDATE provider_name='$prov',policy_number='$pol',expiry_date=".($exp?"'$exp'":"NULL").",coverage_type='$cov',payment_preference='$pay',billing_address='$bill',updated_at=NOW()");
    logActivity($conn,$pat_pk,$user_id,'profile_update','Insurance information updated');
    $pct=recalcCompleteness($conn,$pat_pk);
    ok(['message'=>'Insurance info saved','completeness'=>$pct]);

// ── SECTION E: Change Password ───────────────────────────
case 'change_password_profile':
    $cur=$post['current_password']??'';
    $new=$post['new_password']??'';
    $conf=$post['confirm_password']??'';
    if($new!==$conf) fail('Passwords do not match');
    if(strlen($new)<8) fail('Password must be at least 8 characters');
    $u=mysqli_fetch_assoc(mysqli_query($conn,"SELECT password FROM users WHERE id=$user_id"));
    if(!$u||!password_verify($cur,$u['password'])) fail('Current password is incorrect');
    $hash=password_hash($new,PASSWORD_DEFAULT);
    mysqli_query($conn,"UPDATE users SET password='$hash' WHERE id=$user_id");
    logActivity($conn,$pat_pk,$user_id,'password_change','Password changed');
    ok(['message'=>'Password changed successfully']);

// ── SECTION E: Toggle 2FA ────────────────────────────────
case 'toggle_2fa':
    $enabled=(int)($post['enabled']??0);
    mysqli_query($conn,"UPDATE patient_settings SET two_factor_enabled=$enabled WHERE patient_id=$pat_pk");
    if(!mysqli_affected_rows($conn)){
        mysqli_query($conn,"INSERT INTO patient_settings(patient_id,two_factor_enabled) VALUES($pat_pk,$enabled) ON DUPLICATE KEY UPDATE two_factor_enabled=$enabled");
    }
    logActivity($conn,$pat_pk,$user_id,'security','2FA '.($enabled?'enabled':'disabled'));
    recalcCompleteness($conn,$pat_pk);
    ok(['message'=>'2FA '.($enabled?'enabled':'disabled')]);

// ── SECTION E: Get Active Sessions ───────────────────────
case 'get_sessions':
    $sessions=[];
    $q=mysqli_query($conn,"SELECT * FROM patient_sessions WHERE patient_id=$pat_pk ORDER BY login_time DESC LIMIT 20");
    if($q) while($r=mysqli_fetch_assoc($q)) $sessions[]=$r;
    ok(['sessions'=>$sessions]);

// ── SECTION E: Logout a session ──────────────────────────
case 'logout_session':
    $sid=(int)($post['session_id']??0);
    if(!$sid) fail('Invalid session');
    mysqli_query($conn,"DELETE FROM patient_sessions WHERE id=$sid AND patient_id=$pat_pk");
    logActivity($conn,$pat_pk,$user_id,'security','Logged out session #'.$sid);
    ok(['message'=>'Session terminated']);

// ── SECTION E: Logout all other sessions ─────────────────
case 'logout_all_sessions':
    $currentToken=esc($conn,session_id());
    mysqli_query($conn,"DELETE FROM patient_sessions WHERE patient_id=$pat_pk AND session_token!='$currentToken'");
    logActivity($conn,$pat_pk,$user_id,'security','Logged out all other sessions');
    ok(['message'=>'All other sessions terminated']);

// ── SECTION E: Request account deactivation ──────────────
case 'request_deactivation':
    $reason=esc($conn,$post['reason']??'');
    mysqli_query($conn,"UPDATE patients SET account_status='deactivation_requested',updated_at=NOW() WHERE id=$pat_pk");
    // Notify admin
    $admins=mysqli_query($conn,"SELECT id FROM users WHERE role='admin' LIMIT 5");
    if($admins) while($a=mysqli_fetch_assoc($admins)){
        $aid=(int)$a['id'];
        $pname=esc($conn,$_SESSION['user_name']??$_SESSION['name']??'Patient');
        mysqli_query($conn,"INSERT INTO notifications(user_id,user_role,type,title,message,is_read,related_module,created_at) VALUES($aid,'admin','system','Account Deactivation Request','Patient $pname has requested account deactivation. Reason: $reason',0,'patients',NOW())");
    }
    logActivity($conn,$pat_pk,$user_id,'account','Account deactivation requested');
    ok(['message'=>'Deactivation request submitted. Admin will review.']);

// ── SECTION F: Update Notification Preferences ───────────
case 'update_notification_prefs':
    $toggles=['email_notifications','sms_notifications','appointment_reminders','prescription_alerts',
              'lab_result_alerts','medical_record_alerts','emergency_contact_alerts','system_announcements'];
    $sets=[];
    foreach($toggles as $t){$v=(int)($post[$t]??0);$sets[]="$t=$v";}
    $lang=esc($conn,$post['language_preference']??'English');
    $channel=esc($conn,$post['preferred_channel']??'dashboard');
    $sets[]="language_preference='$lang'";
    $sets[]="preferred_channel='$channel'";
    $check=mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM patient_settings WHERE patient_id=$pat_pk"));
    if($check){
        mysqli_query($conn,"UPDATE patient_settings SET ".implode(',',$sets).",updated_at=NOW() WHERE patient_id=$pat_pk");
    }else{
        $cols='patient_id,'.implode(',',array_map(fn($s)=>explode('=',$s)[0],$sets)).',updated_at';
        $vals="$pat_pk,".implode(',',array_map(fn($s)=>explode('=',$s)[1]??'0',$sets)).",NOW()";
        mysqli_query($conn,"INSERT INTO patient_settings($cols) VALUES($vals)");
    }
    logActivity($conn,$pat_pk,$user_id,'settings','Notification preferences updated');
    ok(['message'=>'Notification preferences saved']);

// ── SECTION G: Upload Document ───────────────────────────
case 'upload_document':
    if(!isset($_FILES['document'])||$_FILES['document']['error']!==UPLOAD_ERR_OK) fail('No file uploaded');
    $file=$_FILES['document'];
    $allowed=['application/pdf','image/jpeg','image/png','image/webp','application/msword',
              'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if(!in_array($file['type'],$allowed)) fail('Allowed: PDF, JPG, PNG, WEBP, DOC, DOCX');
    if($file['size']>5*1024*1024) fail('File must be under 5MB');
    $cat=esc($conn,$post['category']??'Other');
    $desc=esc($conn,$post['description']??'');
    $ext=pathinfo($file['name'],PATHINFO_EXTENSION);
    $origName=esc($conn,pathinfo($file['name'],PATHINFO_FILENAME));
    $fname='doc_'.$pat_pk.'_'.time().'.'.$ext;
    $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/patient_documents/';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    if(!move_uploaded_file($file['tmp_name'],$dir.$fname)) fail('Upload failed');
    $path='uploads/patient_documents/'.$fname;
    $pathE=esc($conn,$path);
    $ftype=esc($conn,$file['type']);
    $fsize=(int)$file['size'];
    $fnameE=esc($conn,$file['name']);
    mysqli_query($conn,"INSERT INTO patient_documents(patient_id,file_name,file_path,file_type,file_size,description,document_category,uploaded_at) VALUES($pat_pk,'$fnameE','$pathE','$ftype',$fsize,'$desc','$cat',NOW())");
    $docId=(int)mysqli_insert_id($conn);
    logActivity($conn,$pat_pk,$user_id,'document_upload','Uploaded document: '.$file['name']);
    recalcCompleteness($conn,$pat_pk);
    ok(['message'=>'Document uploaded','doc_id'=>$docId]);

// ── SECTION G: Delete Document ───────────────────────────
case 'delete_document':
    $did=(int)($post['doc_id']??0);
    if(!$did) fail('Invalid document ID');
    $doc=mysqli_fetch_assoc(mysqli_query($conn,"SELECT file_path FROM patient_documents WHERE id=$did AND patient_id=$pat_pk"));
    if(!$doc) fail('Document not found');
    $fullPath=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/'.$doc['file_path'];
    if(file_exists($fullPath)) @unlink($fullPath);
    mysqli_query($conn,"DELETE FROM patient_documents WHERE id=$did AND patient_id=$pat_pk");
    logActivity($conn,$pat_pk,$user_id,'document_delete','Deleted document #'.$did);
    recalcCompleteness($conn,$pat_pk);
    ok(['message'=>'Document deleted']);

// ── SECTION G: List Documents ────────────────────────────
case 'get_documents':
    $docs=[];
    $q=mysqli_query($conn,"SELECT * FROM patient_documents WHERE patient_id=$pat_pk ORDER BY uploaded_at DESC");
    if($q) while($r=mysqli_fetch_assoc($q)) $docs[]=$r;
    ok(['documents'=>$docs]);

// ── SECTION H: Get Completeness ──────────────────────────
case 'get_completeness':
    $pct=recalcCompleteness($conn,$pat_pk);
    $comp=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM patient_profile_completeness WHERE patient_id=$pat_pk"));
    ok(['completeness'=>$comp,'percentage'=>$pct]);

// ── SECTION B: Calculate BMI (AJAX) ──────────────────────
case 'calc_bmi':
    $h=floatval($post['height_cm']??0);$w=floatval($post['weight_kg']??0);
    if($h<=0||$w<=0) fail('Invalid values');
    $bmi=round($w/(($h/100)**2),1);
    $cat='';if($bmi<18.5)$cat='Underweight';elseif($bmi<25)$cat='Normal';elseif($bmi<30)$cat='Overweight';else $cat='Obese';
    ok(['bmi'=>$bmi,'category'=>$cat]);

// ── Activity Log ─────────────────────────────────────────
case 'get_activity_log':
    $logs=[];
    $q=mysqli_query($conn,"SELECT * FROM patient_activity_log WHERE patient_id=$pat_pk ORDER BY created_at DESC LIMIT 50");
    if($q) while($r=mysqli_fetch_assoc($q)) $logs[]=$r;
    ok(['logs'=>$logs]);

default:
    fail('Unknown action: '.$action);
}
