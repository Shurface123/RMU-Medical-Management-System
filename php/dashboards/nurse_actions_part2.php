<?php
/* ═══════════════════════════════════════════════════════════
   NURSE ACTIONS — Part 2 (Modules 8-14)
   Included by nurse_actions.php on unmatched actions
   ═══════════════════════════════════════════════════════════ */

switch($action){

/* ═══ MODULE 8: IV & FLUIDS ═══ */
case 'start_iv':
  $pid  = (int)$_POST['patient_id'];
  $type = sanitize($_POST['fluid_type']??'');
  $vol  = (float)($_POST['volume']??0);
  $rate = (float)($_POST['rate']??0);
  $notes= sanitize($_POST['notes']??'');
  if(!$pid||!$type||!$vol){$response=['success'=>false,'message'=>'Patient, fluid type and volume required'];break;}
  dbInsert($conn,"INSERT INTO iv_fluid_records (patient_id,nurse_id,fluid_type,volume_ordered,volume_infused,infusion_rate,status,start_time,notes,created_at) VALUES (?,?,?,?,0,?,'Running',NOW(),?,NOW())","iisdds",[$pid,$nurse_pk,$type,$vol,$rate,$notes]);
  logNurseActivity($conn,$nurse_pk,'iv_started','Started '.$type.' IV for patient #'.$pid);
  // ── Security: Rate limit IV starts ──
  if(!rateLimitAction($conn, $nurse_pk, 'iv_started', 20)){
    $response=['success'=>false,'message'=>'Rate limit exceeded for IV starts. Please wait.'];break;
  }
  $response=['success'=>true,'message'=>'IV fluid started'];
  break;

case 'update_iv_status':
  $ivid = (int)$_POST['iv_id'];
  $st   = sanitize($_POST['new_status']??'');
  if(!in_array($st,['Running','Paused','Stopped','Completed'])){$response=['success'=>false,'message'=>'Invalid status'];break;}
  $upd = "UPDATE iv_fluid_records SET status=?,updated_at=NOW()";
  if($st==='Stopped'||$st==='Completed') $upd.=",end_time=NOW()";
  dbExecute($conn,$upd." WHERE id=? AND nurse_id=?","sii",[$st,$ivid,$nurse_pk]);
  $response=['success'=>true,'message'=>'IV status updated to '.$st];
  break;

case 'update_iv_infused':
  $ivid = (int)$_POST['iv_id'];
  $inf  = (float)($_POST['volume_infused']??0);
  $rate = $_POST['new_rate']??null;
  $sql  = "UPDATE iv_fluid_records SET volume_infused=?,updated_at=NOW()";
  $params = [$inf]; $types = "d";
  if($rate){$sql.=",infusion_rate=?";$params[]=(float)$rate;$types.="d";}
  $sql.=" WHERE id=? AND nurse_id=?";$params[]=$ivid;$params[]=$nurse_pk;$types.="ii";
  dbExecute($conn,$sql,$types,$params);
  // ── Cross-Dashboard: Check if IV fluid is critically low ──
  $ivRec = dbRow($conn,"SELECT volume_ordered,volume_infused,patient_id FROM iv_fluid_records WHERE id=?","i",[$ivid]);
  if($ivRec){
    $remaining = (float)$ivRec['volume_ordered'] - $inf;
    if($remaining < 50 && $remaining >= 0){
      $pName = getPatientNameById($conn, (int)$ivRec['patient_id']);
      $nName = getNurseName($conn, $nurse_pk);
      $docPk = getAttendingDoctorPk($conn, (int)$ivRec['patient_id']);
      if($docPk) notifyDoctor($conn, $docPk, 'iv_alert', '⚠️ IV Fluid Critical Level',
        "IV fluid for $pName is critically low ({$remaining}mL remaining). Nurse: $nName", 'fluids', $ivid, 'high');
    }
  }
  $response=['success'=>true,'message'=>'IV updated'];
  break;

case 'record_fluid':
  $pid = (int)$_POST['patient_id'];
  $type= sanitize($_POST['type']??'Intake');
  $cat = sanitize($_POST['category']??'');
  $amt = (float)($_POST['amount']??0);
  $notes=sanitize($_POST['notes']??'');
  if(!$pid||!$amt){$response=['success'=>false,'message'=>'Patient and amount required'];break;}
  dbInsert($conn,"INSERT INTO fluid_balance (patient_id,nurse_id,type,category,amount,notes,recorded_at) VALUES (?,?,?,?,?,?,NOW())","iissds",[$pid,$nurse_pk,$type,$cat,$amt,$notes]);
  $response=['success'=>true,'message'=>'Fluid record saved'];
  break;

/* ═══ MODULE 9: EDUCATION & DISCHARGE ═══ */
case 'add_education':
  $pid  = (int)$_POST['patient_id'];
  $topic= sanitize($_POST['topic']??'');
  $method=sanitize($_POST['method_used']??'Verbal');
  $content=sanitize($_POST['content']??'');
  $und  = sanitize($_POST['understanding_level']??'Good');
  $fu   = (int)($_POST['requires_followup']??0);
  if(!$pid||!$topic){$response=['success'=>false,'message'=>'Patient and topic required'];break;}
  dbInsert($conn,"INSERT INTO patient_education (patient_id,nurse_id,topic,method_used,content,understanding_level,requires_followup,created_at) VALUES (?,?,?,?,?,?,?,NOW())","iissssi",[$pid,$nurse_pk,$topic,$method,$content,$und,$fu]);
  $response=['success'=>true,'message'=>'Education record saved'];
  break;

case 'add_discharge_instructions':
  $pid = (int)$_POST['patient_id'];
  $meds= sanitize($_POST['medication_instructions']??'');
  if(!$pid||!$meds){$response=['success'=>false,'message'=>'Patient and medication instructions required'];break;}
  dbInsert($conn,"INSERT INTO discharge_instructions (patient_id,nurse_id,medication_instructions,activity_restrictions,follow_up_details,warning_signs,emergency_contact,created_at) VALUES (?,?,?,?,?,?,?,NOW())","iisssss",[$pid,$nurse_pk,$meds,sanitize($_POST['activity_restrictions']??''),sanitize($_POST['follow_up_details']??''),sanitize($_POST['warning_signs']??''),sanitize($_POST['emergency_contact']??'')]);
  // ── Cross-Dashboard: Notify patient about discharge instructions ──
  $nName = getNurseName($conn, $nurse_pk);
  notifyPatient($conn, $pid, 'discharge', 'Discharge Instructions Available',
    "Nurse $nName has prepared your discharge instructions. Please review your medications, activity restrictions, and follow-up details.", 'education');
  $response=['success'=>true,'message'=>'Discharge instructions saved'];
  break;

case 'get_education_detail':
  $eid = (int)$_POST['education_id'];
  $e = dbRow($conn,"SELECT pe.*,u.name AS patient_name FROM patient_education pe JOIN patients p ON pe.patient_id=p.id JOIN users u ON p.user_id=u.id WHERE pe.id=?","i",[$eid]);
  $response = $e ? ['success'=>true,'data'=>$e] : ['success'=>false,'message'=>'Not found'];
  break;

case 'get_discharge_detail':
  $did = (int)$_POST['discharge_id'];
  $d = dbRow($conn,"SELECT di.*,u.name AS patient_name FROM discharge_instructions di JOIN patients p ON di.patient_id=p.id JOIN users u ON p.user_id=u.id WHERE di.id=?","i",[$did]);
  $response = $d ? ['success'=>true,'data'=>$d] : ['success'=>false,'message'=>'Not found'];
  break;

/* ═══ MODULE 10: MESSAGES ═══ */
case 'get_messages':
  $oid = (int)$_POST['other_user_id'];
  $msgs= dbSelect($conn,"SELECT * FROM nurse_doctor_messages WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY sent_at ASC LIMIT 200","iiii",[$user_id,$oid,$oid,$user_id]);
  $response=['success'=>true,'data'=>$msgs];
  break;

case 'send_message':
  $rid = (int)$_POST['receiver_id'];
  $txt = sanitize($_POST['message_text']??'');
  if(!$rid||!$txt){$response=['success'=>false,'message'=>'Receiver and message required'];break;}
  $pid = (int)($_POST['patient_id']??0);
  $pri = sanitize($_POST['priority']??'Normal');
  dbInsert($conn,"INSERT INTO nurse_doctor_messages (sender_id,receiver_id,patient_id,message_text,priority,sent_at) VALUES (?,?,?,?,?,NOW())","iiiss",[$user_id,$rid,$pid?:null,$txt,$pri]);
  // ── Cross-Dashboard: Notify doctor about new message ──
  $nName = getNurseName($conn, $nurse_pk);
  crossNotify($conn, $rid, 'doctor', 'message', 'New Message from Nurse',
    "Nurse $nName sent you a message" . ($pri==='Urgent' ? ' (URGENT)' : '') . ": " . substr($txt,0,100),
    'messages', null, $pri==='Urgent' ? 'high' : 'normal');
  $response=['success'=>true,'message'=>'Message sent'];
  break;

case 'mark_messages_read':
  $oid = (int)$_POST['other_user_id'];
  dbExecute($conn,"UPDATE nurse_doctor_messages SET is_read=1,read_at=NOW() WHERE sender_id=? AND receiver_id=? AND is_read=0","ii",[$oid,$user_id]);
  $response=['success'=>true];
  break;

/* ═══ MODULE 11: ANALYTICS ═══ */
case 'get_analytics':
  $range = $_POST['range']??'7d';
  $days  = match($range){'30d'=>30,'90d'=>90,default=>7};
  $since = date('Y-m-d',strtotime("-{$days} days"));

  // Vitals daily
  $vd = dbSelect($conn,"SELECT DATE(recorded_at) AS d,COUNT(*) AS c FROM patient_vitals WHERE nurse_id=? AND recorded_at>=? GROUP BY DATE(recorded_at) ORDER BY d","is",[$nurse_pk,$since]);

  // Med compliance
  $ma = (int)dbVal($conn,"SELECT COUNT(*) FROM medication_administration WHERE nurse_id=? AND status='Administered' AND scheduled_time>=?","is",[$nurse_pk,$since]);
  $mm = (int)dbVal($conn,"SELECT COUNT(*) FROM medication_administration WHERE nurse_id=? AND status='Missed' AND scheduled_time>=?","is",[$nurse_pk,$since]);
  $mr = (int)dbVal($conn,"SELECT COUNT(*) FROM medication_administration WHERE nurse_id=? AND status='Refused' AND scheduled_time>=?","is",[$nurse_pk,$since]);
  $mh = (int)dbVal($conn,"SELECT COUNT(*) FROM medication_administration WHERE nurse_id=? AND status='Held' AND scheduled_time>=?","is",[$nurse_pk,$since]);

  // Task rate
  $td = dbSelect($conn,"SELECT DATE(created_at) AS d,SUM(status='Completed') AS completed,SUM(status IN('Pending','Overdue')) AS pending FROM nurse_tasks WHERE nurse_id=? AND created_at>=? GROUP BY DATE(created_at) ORDER BY d","is",[$nurse_pk,$since]);

  // Emergency
  $ea = dbSelect($conn,"SELECT DATE(triggered_at) AS d,COUNT(*) AS c FROM emergency_alerts WHERE triggered_at>=? GROUP BY DATE(triggered_at) ORDER BY d","s",[$since]);

  // Fluid
  $fl = dbSelect($conn,"SELECT DATE(recorded_at) AS d,SUM(CASE WHEN type='Intake' THEN amount ELSE 0 END) AS intake,SUM(CASE WHEN type='Output' THEN amount ELSE 0 END) AS output FROM fluid_balance WHERE nurse_id=? AND recorded_at>=? GROUP BY DATE(recorded_at) ORDER BY d","is",[$nurse_pk,$since]);

  // Beds
  $bo = (int)dbVal($conn,"SELECT COUNT(*) FROM bed_assignments WHERE status='Active'");
  $ba2= (int)dbVal($conn,"SELECT COUNT(*) FROM bed_management WHERE status='Available'");
  $bm = (int)dbVal($conn,"SELECT COUNT(*) FROM bed_management WHERE status='Maintenance'");

  // Education
  $ed = dbSelect($conn,"SELECT DATE(created_at) AS d,COUNT(*) AS c FROM patient_education WHERE nurse_id=? AND created_at>=? GROUP BY DATE(created_at) ORDER BY d","is",[$nurse_pk,$since]);

  $response=['success'=>true,'data'=>[
    'vitals_daily'=>['labels'=>array_column($vd,'d'),'data'=>array_column($vd,'c')],
    'med_admin'=>$ma,'med_missed'=>$mm,'med_refused'=>$mr,'med_held'=>$mh,
    'task_rate'=>['labels'=>array_column($td,'d'),'completed'=>array_column($td,'completed'),'pending'=>array_column($td,'pending')],
    'emergency'=>['labels'=>array_column($ea,'d'),'data'=>array_column($ea,'c')],
    'fluid'=>['labels'=>array_column($fl,'d'),'intake'=>array_column($fl,'intake'),'output'=>array_column($fl,'output')],
    'beds_occupied'=>$bo,'beds_available'=>$ba2,'beds_maintenance'=>$bm,
    'education'=>['labels'=>array_column($ed,'d'),'data'=>array_column($ed,'c')]
  ]];
  break;

/* ═══ MODULE 12: REPORTS ═══ */
case 'generate_report':
  $rtype  = sanitize($_POST['report_type']??'');
  $from   = sanitize($_POST['date_from']??'');
  $to     = sanitize($_POST['date_to']??'');
  $pid    = (int)($_POST['patient_id']??0);
  $format = sanitize($_POST['format']??'csv');

  $tables = ['vitals'=>'patient_vitals','medications'=>'medication_administration','notes'=>'nursing_notes','fluids'=>'fluid_balance','tasks'=>'nurse_tasks','emergency'=>'emergency_alerts','wounds'=>'wound_care_records','handover'=>'shift_handover','education'=>'patient_education'];
  $tbl = $tables[$rtype]??'';
  if(!$tbl){$response=['success'=>false,'message'=>'Invalid report type'];break;}

  $where = "WHERE created_at BETWEEN ? AND ?"; $params=[$from,$to.' 23:59:59']; $types='ss';
  if(in_array($rtype,['vitals'])){$where="WHERE recorded_at BETWEEN ? AND ?"; }
  if(in_array($rtype,['emergency'])){$where="WHERE triggered_at BETWEEN ? AND ?";}
  if($pid){$where.=" AND patient_id=?";$params[]=$pid;$types.='i';}

  $rows=dbSelect($conn,"SELECT * FROM $tbl $where ORDER BY 1 DESC LIMIT 5000",$types,$params);
  if(empty($rows)){$response=['success'=>true,'data'=>'No data found for this range'];break;}

  $csv = implode(',',array_keys($rows[0]))."\n";
  foreach($rows as $r) $csv.=implode(',',array_map(fn($v)=>'"'.str_replace('"','""',$v??'').'"',array_values($r)))."\n";
  $response=['success'=>true,'data'=>$csv];
  break;

/* ═══ MODULE 13: PROFILE ═══ */
case 'change_password':
  $cur = $_POST['current']??'';
  $new = $_POST['new_password']??'';
  $user = dbRow($conn,"SELECT password FROM users WHERE id=?","i",[$user_id]);
  if(!password_verify($cur,$user['password'])){$response=['success'=>false,'message'=>'Current password is incorrect'];break;}
  // ── Security: Password strength enforcement ──
  $pwErrors = enforcePasswordStrength($new);
  if(!empty($pwErrors)){$response=['success'=>false,'message'=>'Password must contain: '.implode(', ',$pwErrors)];break;}
  $hash = password_hash($new,PASSWORD_DEFAULT);
  dbExecute($conn,"UPDATE users SET password=? WHERE id=?","si",[$hash,$user_id]);
  logNurseActivity($conn,$nurse_pk,'password_changed','Changed account password');
  // ── Security: Notify admins of password change ──
  $nName = getNurseName($conn, $nurse_pk);
  notifyAllAdmins($conn, 'system', 'Nurse Password Changed', "Nurse $nName changed their account password.", 'security');
  $response=['success'=>true,'message'=>'Password changed successfully'];
  break;

case 'upload_profile_photo':
  if(!isset($_FILES['photo'])){$response=['success'=>false,'message'=>'No file uploaded'];break;}
  // ── Security: Use validateUpload for MIME verification + PHP injection scan ──
  $upVal = validateUpload($_FILES['photo'], ['image/jpeg','image/png'], 5*1024*1024);
  if(!$upVal['valid']){$response=['success'=>false,'message'=>$upVal['error']];break;}
  $file=$_FILES['photo'];
  $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
  $fn='uploads/nurses/photo_'.$nurse_pk.'_'.time().'.'.$ext;
  $dest=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/'.$fn;
  @mkdir(dirname($dest),0755,true);
  if(move_uploaded_file($file['tmp_name'],$dest)){
    dbExecute($conn,"UPDATE nurses SET profile_photo=? WHERE id=?","si",[$fn,$nurse_pk]);
    dbExecute($conn,"UPDATE users SET profile_image=? WHERE id=?","si",[$fn,$user_id]);
    logNurseActivity($conn,$nurse_pk,'photo_upload','Updated profile photo');
    $response=['success'=>true,'message'=>'Photo updated'];
  } else $response=['success'=>false,'message'=>'Upload failed'];
  break;

case 'add_qualification':
  $deg  = sanitize($_POST['degree']??'');
  $inst = sanitize($_POST['institution']??'');
  $year = (int)($_POST['year']??0);
  dbInsert($conn,"INSERT INTO nurse_qualifications (nurse_id,degree,institution,year,created_at) VALUES (?,?,?,?,NOW())","issi",[$nurse_pk,$deg,$inst,$year]);
  $response=['success'=>true,'message'=>'Qualification added'];
  break;

case 'add_certification':
  $name = sanitize($_POST['name']??'');
  $body = sanitize($_POST['issuing_body']??'');
  $issue= $_POST['issue_date']??null;
  $exp  = $_POST['expiry_date']??null;
  dbInsert($conn,"INSERT INTO nurse_certifications (nurse_id,certification_name,issuing_body,issue_date,expiry_date,created_at) VALUES (?,?,?,?,?,NOW())","issss",[$nurse_pk,$name,$body,$issue,$exp]);
  $response=['success'=>true,'message'=>'Certification added'];
  break;

case 'delete_qualification':
  dbExecute($conn,"DELETE FROM nurse_qualifications WHERE id=? AND nurse_id=?","ii",[(int)$_POST['qual_id'],$nurse_pk]);
  $response=['success'=>true,'message'=>'Deleted'];
  break;

case 'delete_certification':
  dbExecute($conn,"DELETE FROM nurse_certifications WHERE id=? AND nurse_id=?","ii",[(int)$_POST['cert_id'],$nurse_pk]);
  $response=['success'=>true,'message'=>'Deleted'];
  break;

case 'upload_document':
  if(!isset($_FILES['file'])){$response=['success'=>false,'message'=>'No file'];break;}
  // ── Security: Use validateUpload for MIME verification + PHP injection scan ──
  $allowedDoc = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/jpeg','image/png'];
  $upVal2 = validateUpload($_FILES['file'], $allowedDoc, 10*1024*1024);
  if(!$upVal2['valid']){$response=['success'=>false,'message'=>$upVal2['error']];break;}
  $file=$_FILES['file'];
  $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
  $fn='uploads/nurses/docs/'.$nurse_pk.'_'.time().'.'.$ext;
  $dest=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/'.$fn;
  @mkdir(dirname($dest),0755,true);
  if(move_uploaded_file($file['tmp_name'],$dest)){
    dbInsert($conn,"INSERT INTO nurse_documents (nurse_id,document_type,document_name,file_path,file_size,uploaded_at) VALUES (?,?,?,?,?,NOW())","isssi",[$nurse_pk,sanitize($_POST['document_type']??'Other'),sanitize($_POST['document_name']??$file['name']),$fn,$file['size']]);
    logNurseActivity($conn,$nurse_pk,'doc_upload','Uploaded document: '.sanitize($_POST['document_name']??$file['name']));
    $response=['success'=>true,'message'=>'Document uploaded'];
  } else $response=['success'=>false,'message'=>'Upload failed'];
  break;

case 'delete_document':
  $docId = (int)$_POST['doc_id'];
  // ── Security: Verify ownership before deletion ──
  if(!validateNurseOwnership($conn, 'nurse_documents', $docId, $nurse_pk)){
    $response=['success'=>false,'message'=>'Access denied'];break;
  }
  $doc=dbRow($conn,"SELECT file_path FROM nurse_documents WHERE id=? AND nurse_id=?","ii",[$docId,$nurse_pk]);
  if($doc){
    @unlink($_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/'.$doc['file_path']);
    dbExecute($conn,"DELETE FROM nurse_documents WHERE id=? AND nurse_id=?","ii",[$docId,$nurse_pk]);
    logNurseActivity($conn,$nurse_pk,'doc_delete','Deleted document #'.$docId);
  }
  $response=['success'=>true,'message'=>'Document deleted'];
  break;

case 'save_notification_prefs':
  $prefs = json_encode($_POST['preferences']??[]);
  dbExecute($conn,"UPDATE nurse_settings SET notification_preferences=?,updated_at=NOW() WHERE nurse_id=?","si",[$prefs,$nurse_pk]);
  $response=['success'=>true,'message'=>'Preferences saved'];
  break;

/* ═══ MODULE 14: SETTINGS ═══ */
case 'save_settings':
  $stype = $_POST['setting_type']??'';
  switch($stype){
    case 'display':
      dbExecute($conn,"UPDATE nurse_settings SET theme=?,language=?,timezone=?,display_preferences=?,updated_at=NOW() WHERE nurse_id=?","ssssi",[
        sanitize($_POST['theme']??'light'),sanitize($_POST['language']??'en'),sanitize($_POST['timezone']??'Africa/Accra'),
        json_encode(['font_size'=>$_POST['font_size']??'medium','density'=>$_POST['density']??'comfortable']),$nurse_pk]);
      $response=['success'=>true,'message'=>'Display settings saved'];break;
    case 'alerts':
      dbExecute($conn,"UPDATE nurse_settings SET vital_alert_threshold=?,med_reminder_minutes=?,auto_refresh_interval=?,sound_alerts=?,desktop_notifications=?,email_notifications=?,updated_at=NOW() WHERE nurse_id=?","iiiiiis",[
        (int)($_POST['vital_threshold']??4),(int)($_POST['med_reminder']??15),(int)($_POST['auto_refresh']??60),
        (int)($_POST['sound_alerts']??1),(int)($_POST['desktop_notifs']??0),(int)($_POST['email_notifs']??0),$nurse_pk]);
      $response=['success'=>true,'message'=>'Alert settings saved'];break;
    case 'shift':
      dbExecute($conn,"UPDATE nurse_settings SET preferred_shift=?,preferred_ward=?,allow_shift_swap=?,overtime_available=?,updated_at=NOW() WHERE nurse_id=?","ssiii",[
        sanitize($_POST['preferred_shift']??''),sanitize($_POST['preferred_ward']??''),(int)($_POST['allow_swap']??1),(int)($_POST['overtime']??0),$nurse_pk]);
      $response=['success'=>true,'message'=>'Shift preferences saved'];break;
    default:$response=['success'=>false,'message'=>'Invalid setting type'];
  }
  break;

/* ═══ NOTIFICATIONS ═══ */
case 'mark_all_notifications_read':
  dbExecute($conn,"UPDATE nurse_notifications SET is_read=1 WHERE nurse_id=? AND is_read=0","i",[$nurse_pk]);
  dbExecute($conn,"UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0","i",[$user_id]);
  $response=['success'=>true,'message'=>'All marked as read'];
  break;

case 'clear_all_notifications':
  dbExecute($conn,"DELETE FROM nurse_notifications WHERE nurse_id=? AND is_read=1","i",[$nurse_pk]);
  $response=['success'=>true,'message'=>'Read notifications cleared'];
  break;

case 'export_my_data':
  $data=['profile'=>$nurse_row,'qualifications'=>dbSelect($conn,"SELECT * FROM nurse_qualifications WHERE nurse_id=?","i",[$nurse_pk]),
    'certifications'=>dbSelect($conn,"SELECT * FROM nurse_certifications WHERE nurse_id=?","i",[$nurse_pk]),
    'tasks'=>dbSelect($conn,"SELECT * FROM nurse_tasks WHERE nurse_id=? ORDER BY created_at DESC LIMIT 1000","i",[$nurse_pk])];
  $csv="Section,Key,Value\n";
  foreach($data['profile'] as $k=>$v) $csv.="Profile,\"$k\",\"".str_replace('"','""',$v??'')."\"\n";
  $response=['success'=>true,'data'=>$csv];
  break;

case 'download_activity_log':
  $logs=dbSelect($conn,"SELECT * FROM nurse_activity_log WHERE nurse_id=? ORDER BY created_at DESC LIMIT 1000","i",[$nurse_pk]);
  $csv="ID,Action Type,Description,IP,Device,Created At\n";
  foreach($logs as $l) $csv.="\"{$l['id']}\",\"{$l['action_type']}\",\"".str_replace('"','""',$l['action_description']??'')."\",\"{$l['ip_address']}\",\"".str_replace('"','""',substr($l['device']??'',0,60))."\",\"{$l['created_at']}\"\n";
  $response=['success'=>true,'data'=>$csv];
  break;

case 'request_account_deletion':
  dbInsert($conn,"INSERT INTO nurse_notifications (nurse_id,type,message,created_at) VALUES (?,'System','Account deletion requested by nurse. Pending admin review.',NOW())","i",[$nurse_pk]);
  logNurseActivity($conn,$nurse_pk,'account_deletion_request','Requested account deletion');
  // ── Cross-Dashboard: Notify all admins ──
  $nName = getNurseName($conn, $nurse_pk);
  notifyAllAdmins($conn, 'system', 'Account Deletion Request',
    "Nurse $nName has requested account deletion. Please review.", 'staff');
  $response=['success'=>true,'message'=>'Account deletion request submitted to administrator'];
  break;

/* ═══════════════════════════════════════════════════════════
   MODULE 15: ADVANCED NURSE PROFILE ACTIONS
   ═══════════════════════════════════════════════════════════ */

case 'update_personal_info':
  $fields = ['full_name','date_of_birth','gender','nationality','marital_status','religion','national_id',
    'phone','secondary_phone','email','personal_email','office_location',
    'street_address','city','region','country','postal_code'];
  $sets=[]; $vals=[]; $types='';
  foreach($fields as $f){
    if(isset($_POST[$f])){
      $sets[]="$f=?"; $vals[]=sanitize($_POST[$f]); $types.='s';
    }
  }
  if(!empty($sets)){
    $vals[]=$nurse_pk; $types.='i';
    dbExecute($conn,"UPDATE nurses SET ".implode(',',$sets)." WHERE id=?",$types,$vals);
    logNurseActivity($conn,$nurse_pk,'profile_update','Updated personal information');
    // Update completeness
    $hasName = !empty($_POST['full_name']); $hasPhone = !empty($_POST['phone']); $hasEmail = !empty($_POST['email']);
    if($hasName && $hasPhone && $hasEmail){
      dbExecute($conn,"INSERT INTO nurse_profile_completeness (nurse_id,personal_info) VALUES (?,1) ON DUPLICATE KEY UPDATE personal_info=1","i",[$nurse_pk]);
    }
  }
  $response=['success'=>true,'message'=>'Personal information updated'];
  break;

case 'update_professional_profile':
  $flds = ['specialization','sub_specialization','department_id','designation','years_of_experience',
    'license_number','license_issuing_body','license_expiry_date','nursing_school',
    'graduation_year','postgraduate_details','bio'];
  $data = [];
  foreach($flds as $f) $data[$f] = sanitize($_POST[$f] ?? '');
  // Handle languages as JSON
  $langs = $_POST['languages_spoken'] ?? '';
  if(is_array($langs)) $data['languages_spoken'] = json_encode($langs);
  else $data['languages_spoken'] = json_encode(array_filter(array_map('trim',explode(',',$langs))));
  $data['nurse_id'] = $nurse_pk;
  // Upsert into nurse_professional_profile
  $cols = array_keys($data); $placeholders = array_fill(0,count($data),'?');
  $updates = array_map(fn($c)=>"$c=VALUES($c)", $cols);
  $sql = "INSERT INTO nurse_professional_profile (".implode(',',$cols).") VALUES (".implode(',',$placeholders).") ON DUPLICATE KEY UPDATE ".implode(',',$updates);
  $t = str_repeat('s',count($data)-1).'i'; // all strings except nurse_id
  // Fix types for int fields
  $t = ''; foreach($cols as $c){ $t .= in_array($c,['nurse_id','department_id','years_of_experience','graduation_year'])?'i':'s'; }
  dbExecute($conn,$sql,$t,array_values($data));
  // Also update nurses table for key fields
  dbExecute($conn,"UPDATE nurses SET specialization=?,designation=?,years_of_experience=?,license_number=?,license_expiry=? WHERE id=?",
    "ssissi",[$data['specialization'],$data['designation'],(int)$data['years_of_experience'],$data['license_number'],$data['license_expiry_date'],$nurse_pk]);
  logNurseActivity($conn,$nurse_pk,'profile_update','Updated professional profile');
  // License expiry warning
  if($data['license_expiry_date']){
    $days = (int)((strtotime($data['license_expiry_date'])-time())/86400);
    if($days <= 60 && $days > 0){
      $nName = getNurseName($conn,$nurse_pk);
      notifyAllAdmins($conn,'system','License Expiring',"Nurse $nName's license expires in {$days} days.",'staff',null,'high');
      nurseNotify($conn,$nurse_pk,"⚠️ Your nursing license expires in $days days. Please renew.",'Alert','profile');
    }
  }
  // Update completeness
  if(!empty($data['specialization']) && !empty($data['license_number'])){
    dbExecute($conn,"INSERT INTO nurse_profile_completeness (nurse_id,professional_profile) VALUES (?,1) ON DUPLICATE KEY UPDATE professional_profile=1","i",[$nurse_pk]);
  }
  $response=['success'=>true,'message'=>'Professional profile updated'];
  break;

case 'update_availability':
  $status = sanitize($_POST['status']??'Available');
  $allowed = ['Available','Busy','On Break','Off Duty'];
  if(!in_array($status,$allowed)){$response=['success'=>false,'message'=>'Invalid status'];break;}
  dbExecute($conn,"UPDATE nurses SET availability_status=? WHERE id=?","si",[$status,$nurse_pk]);
  logNurseActivity($conn,$nurse_pk,'availability_change','Changed status to '.$status);
  // Cross-dashboard: notify all doctors of availability change
  $nName = getNurseName($conn,$nurse_pk);
  notifyAllDoctors($conn,'system','Nurse Availability',"$nName is now $status",'staff');
  $response=['success'=>true,'message'=>'Status updated to '.$status];
  break;

case 'save_shift_pref_notes':
  $notes = sanitize($_POST['notes']??'');
  dbExecute($conn,"UPDATE nurses SET shift_preference_notes=? WHERE id=?","si",[$notes,$nurse_pk]);
  logNurseActivity($conn,$nurse_pk,'profile_update','Updated shift preference notes');
  $response=['success'=>true,'message'=>'Shift preferences saved'];
  break;

case 'toggle_2fa':
  $enabled = (int)($_POST['enabled']??0);
  dbExecute($conn,"UPDATE nurses SET two_fa_enabled=? WHERE id=?","ii",[$enabled,$nurse_pk]);
  logNurseActivity($conn,$nurse_pk,'security_change','2FA '.($enabled?'enabled':'disabled'));
  if($enabled){
    dbExecute($conn,"INSERT INTO nurse_profile_completeness (nurse_id,security_setup) VALUES (?,1) ON DUPLICATE KEY UPDATE security_setup=1","i",[$nurse_pk]);
  }
  $response=['success'=>true,'message'=>'Two-factor authentication '.($enabled?'enabled':'disabled')];
  break;

case 'logout_session':
  $sid = (int)($_POST['session_id']??0);
  dbExecute($conn,"DELETE FROM nurse_sessions WHERE id=? AND nurse_id=?","ii",[$sid,$nurse_pk]);
  logNurseActivity($conn,$nurse_pk,'security_change','Logged out session #'.$sid);
  $response=['success'=>true,'message'=>'Session terminated'];
  break;

case 'logout_all_sessions':
  $currentSessId = session_id();
  dbExecute($conn,"DELETE FROM nurse_sessions WHERE nurse_id=? AND session_id!=?","is",[$nurse_pk,$currentSessId]);
  logNurseActivity($conn,$nurse_pk,'security_change','Logged out all other sessions');
  $response=['success'=>true,'message'=>'All other sessions terminated'];
  break;

case 'save_notification_toggles':
  $toggles = ['notif_new_task','notif_task_overdue','notif_med_reminder','notif_vital_due','notif_abnormal_vital',
    'notif_shift_reminder','notif_handover','notif_doctor_msg','notif_emergency','notif_cert_expiry','notif_system'];
  $sets=[]; $vals=[]; $types='';
  foreach($toggles as $t_key){
    $sets[]="$t_key=?"; $vals[]=(int)($_POST[$t_key]??1); $types.='i';
  }
  $sets[]="preferred_channel=?"; $vals[]=sanitize($_POST['preferred_channel']??'dashboard'); $types.='s';
  $sets[]="critical_sound_enabled=?"; $vals[]=(int)($_POST['critical_sound_enabled']??1); $types.='i';
  $sets[]="preferred_notif_lang=?"; $vals[]=sanitize($_POST['preferred_notif_lang']??'en'); $types.='s';
  $vals[]=$nurse_pk; $types.='i';
  dbExecute($conn,"UPDATE nurse_settings SET ".implode(',',$sets)." WHERE nurse_id=?",$types,$vals);
  logNurseActivity($conn,$nurse_pk,'settings_update','Updated notification preferences');
  $response=['success'=>true,'message'=>'Notification preferences saved'];
  break;

case 'recalculate_completeness':
  $n = dbRow($conn,"SELECT * FROM nurses WHERE id=?","i",[$nurse_pk]);
  $p = dbRow($conn,"SELECT * FROM nurse_professional_profile WHERE nurse_id=?","i",[$nurse_pk]);
  $pi = (!empty($n['full_name']) && !empty($n['phone']) && !empty($n['email'])) ? 1 : 0;
  $pp = (!empty($p['specialization']) && !empty($p['license_number'])) ? 1 : 0;
  $qu = (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_qualifications WHERE nurse_id=?","i",[$nurse_pk]) > 0 ? 1 : 0;
  $sp = (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_shifts WHERE nurse_id=?","i",[$nurse_pk]) > 0 ? 1 : 0;
  $ph = (!empty($n['profile_photo']) && $n['profile_photo']!=='default-avatar.png') ? 1 : 0;
  $se = (int)($n['two_fa_enabled']??0);
  $dc = (int)dbVal($conn,"SELECT COUNT(*) FROM nurse_documents WHERE nurse_id=?","i",[$nurse_pk]) > 0 ? 1 : 0;
  $total = $pi+$pp+$qu+$sp+$ph+$se+$dc;
  $pct = round(($total/7)*100);
  dbExecute($conn,"INSERT INTO nurse_profile_completeness (nurse_id,personal_info,professional_profile,qualifications,shift_profile,profile_photo,security_setup,documents_uploaded,completeness_percentage) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE personal_info=VALUES(personal_info),professional_profile=VALUES(professional_profile),qualifications=VALUES(qualifications),shift_profile=VALUES(shift_profile),profile_photo=VALUES(profile_photo),security_setup=VALUES(security_setup),documents_uploaded=VALUES(documents_uploaded),completeness_percentage=VALUES(completeness_percentage)",
    "iiiiiiiii",[$nurse_pk,$pi,$pp,$qu,$sp,$ph,$se,$dc,$pct]);
  $response=['success'=>true,'percentage'=>$pct];
  break;

default:
  $response=['success'=>false,'message'=>'Unknown action: '.$action];
}
