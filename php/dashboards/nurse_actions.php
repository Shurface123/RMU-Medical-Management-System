<?php
/* ═══════════════════════════════════════════════════════════
   NURSE ACTIONS — AJAX Backend Handler
   Handles ALL nurse dashboard AJAX requests
   ═══════════════════════════════════════════════════════════ */
require_once __DIR__ . '/nurse_security.php';
require_once __DIR__ . '/cross_notify.php';
define('AJAX_REQUEST', true);
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success'=>false,'message'=>'Invalid action'];

// ── CSRF enforcement on all POST requests ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    verifyCsrfToken($token);
}

try {
  $user_id  = $_SESSION['user_id'];
  $nurse_row = dbRow($conn,"SELECT n.* FROM nurses n WHERE n.user_id=? LIMIT 1","i",[$user_id]);
  $nurse_pk  = $nurse_row['id'] ?? 0;
  $today     = date('Y-m-d');

  if(!$nurse_pk){ echo json_encode(['success'=>false,'message'=>'Nurse profile not found']); exit; }

  switch($action){

  /* ═══ MODULE 2: VITALS ═══ */
  case 'record_vitals':
    $pid   = (int)$_POST['patient_id'];
    $bps   = $_POST['bp_systolic']??null;
    $bpd   = $_POST['bp_diastolic']??null;
    $pulse = $_POST['pulse_rate']??null;
    $temp  = $_POST['temperature']??null;
    $spo2  = $_POST['oxygen_saturation']??null;
    $rr    = $_POST['respiratory_rate']??null;
    $gluc  = $_POST['blood_glucose']??null;
    $pain  = $_POST['pain_level']??null;
    $wt    = $_POST['weight']??null;
    $ht    = $_POST['height']??null;
    $bmi   = $_POST['bmi']??null;
    $notes = sanitize($_POST['notes']??'');
    if(!$pid){$response=['success'=>false,'message'=>'Patient required'];break;}

    // Auto-flag abnormal vitals
    $flagged=0; $flag_reason='';
    if($bps && ($bps>180||$bps<80)){$flagged=1;$flag_reason.='BP Systolic abnormal; ';}
    if($bpd && ($bpd>120||$bpd<50)){$flagged=1;$flag_reason.='BP Diastolic abnormal; ';}
    if($pulse && ($pulse>120||$pulse<50)){$flagged=1;$flag_reason.='Pulse abnormal; ';}
    if($temp && ($temp>38.5||$temp<35)){$flagged=1;$flag_reason.='Temperature abnormal; ';}
    if($spo2 && $spo2<92){$flagged=1;$flag_reason.='SpO2 low; ';}

    dbInsert($conn,"INSERT INTO patient_vitals (patient_id,nurse_id,bp_systolic,bp_diastolic,pulse_rate,temperature,oxygen_saturation,respiratory_rate,blood_glucose,pain_level,weight,height,bmi,notes,is_flagged,flag_reason,recorded_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
      "iidddddddddddsiss",[$pid,$nurse_pk,$bps,$bpd,$pulse,$temp,$spo2,$rr,$gluc,$pain,$wt,$ht,$bmi,$notes,$flagged,$flag_reason]);
    logNurseActivity($conn,$nurse_pk,'vital_recorded','Recorded vitals for patient #'.$pid);

    if($flagged){
      dbInsert($conn,"INSERT INTO nurse_notifications (nurse_id,type,message,created_at) VALUES (?,'Vital','Abnormal vital detected for patient #$pid: $flag_reason',NOW())","i",[$nurse_pk]);
      // ── Cross-Dashboard: Notify attending doctor + all admins ──
      $pName = getPatientNameById($conn, $pid);
      $nName = getNurseName($conn, $nurse_pk);
      $docPk = getAttendingDoctorPk($conn, $pid);
      if($docPk) notifyDoctor($conn, $docPk, 'vital_alert', '⚠️ Abnormal Vitals Detected',
        "Nurse $nName flagged abnormal vitals for $pName: $flag_reason", 'vitals', null, 'high');
      notifyAllAdmins($conn, 'vital_alert', '⚠️ Abnormal Vitals Alert',
        "Nurse $nName flagged abnormal vitals for $pName: $flag_reason", 'vitals', null, 'high');
    }
    $response=['success'=>true,'message'=>'Vital signs recorded','flagged'=>$flagged];
    break;

  case 'get_vital_history':
    $pid   = (int)$_POST['patient_id'];
    $range = $_POST['range']??'24h';
    $cutoff = match($range){'7d'=>'-7 days','30d'=>'-30 days',default=>'-24 hours'};
    $since = date('Y-m-d H:i:s',strtotime($cutoff));
    $data = dbSelect($conn,"SELECT * FROM patient_vitals WHERE patient_id=? AND recorded_at>=? ORDER BY recorded_at ASC","is",[$pid,$since]);
    $response=['success'=>true,'data'=>$data];
    break;

  case 'get_bedside_view':
    $pid = (int)$_POST['patient_id'];
    $p = dbRow($conn,"SELECT p.*,u.name,u.date_of_birth,u.gender FROM patients p JOIN users u ON p.user_id=u.id WHERE p.id=?","i",[$pid]);
    $v = dbRow($conn,"SELECT * FROM patient_vitals WHERE patient_id=? ORDER BY recorded_at DESC LIMIT 1","i",[$pid]);
    $doc = dbRow($conn,"SELECT u.name FROM bed_assignments ba JOIN doctors d ON ba.attending_doctor_id=d.id JOIN users u ON d.user_id=u.id WHERE ba.patient_id=? AND ba.status='Active' LIMIT 1","i",[$pid]);
    $rxs = dbSelect($conn,"SELECT pi.medicine_name,pi.dosage,pi.frequency FROM prescriptions pr JOIN prescription_items pi ON pi.prescription_id=pr.id WHERE pr.patient_id=? AND pr.status IN('Active','Pending') LIMIT 10","i",[$pid]);
    $rx_html='';foreach($rxs as $rx) $rx_html.='<p>• <strong>'.htmlspecialchars($rx['medicine_name']).'</strong> '.$rx['dosage'].' - '.$rx['frequency'].'</p>';
    $response=['success'=>true,'data'=>[
      'name'=>$p['name']??'','patient_id'=>$p['patient_id']??'','gender'=>$p['gender']??'',
      'date_of_birth'=>$p['date_of_birth']??'','blood_type'=>$p['blood_type']??'','allergies'=>$p['known_allergies']??'',
      'doctor'=>$doc['name']??'Unassigned','bp'=>($v['bp_systolic']??'-').'/'.($v['bp_diastolic']??'-'),
      'hr'=>$v['pulse_rate']??'','temp'=>$v['temperature']??'','spo2'=>$v['oxygen_saturation']??'',
      'rr'=>$v['respiratory_rate']??'','vital_time'=>$v['recorded_at']??'','flagged'=>(int)($v['is_flagged']??0),
      'prescriptions'=>$rx_html?:'<p class="text-muted">No active prescriptions</p>'
    ]];
    break;

  /* ═══ MODULE 3: MEDICATIONS ═══ */
  case 'administer_medication':
    $mid = (int)$_POST['med_id'];
    dbExecute($conn,"UPDATE medication_administration SET status='Administered',administered_at=NOW(),administered_by=? WHERE id=?","ii",[$nurse_pk,$mid]);
    logNurseActivity($conn,$nurse_pk,'med_administered','Administered medication #'.$mid);
    // ── Cross-Dashboard: Notify prescribing doctor ──
    $medRow = dbRow($conn,"SELECT ma.medicine_name,ma.patient_id,pr.doctor_id FROM medication_administration ma LEFT JOIN prescriptions pr ON ma.prescription_id=pr.id WHERE ma.id=?","i",[$mid]);
    if($medRow && ($medRow['doctor_id']??0)){
      $pName = getPatientNameById($conn, (int)$medRow['patient_id']);
      $nName = getNurseName($conn, $nurse_pk);
      notifyDoctor($conn, (int)$medRow['doctor_id'], 'medication', 'Medication Administered',
        "Nurse $nName administered {$medRow['medicine_name']} to $pName.", 'medications', $mid);
    }
    $response=['success'=>true,'message'=>'Medication administered successfully'];
    break;

  case 'update_med_status':
    $mid = (int)$_POST['med_id'];
    $ns  = sanitize($_POST['new_status']??'');
    $rsn = sanitize($_POST['reason']??'');
    if(!in_array($ns,['Missed','Refused','Held'])){$response=['success'=>false,'message'=>'Invalid status'];break;}
    dbExecute($conn,"UPDATE medication_administration SET status=?,notes=CONCAT(IFNULL(notes,''),'\n[".date('H:i')."] $ns: ',?) WHERE id=?","ssi",[$ns,$rsn,$mid]);
    logNurseActivity($conn,$nurse_pk,'med_status','Updated medication #'.$mid.' status to '.$ns);
    // ── Cross-Dashboard: Notify prescribing doctor about missed/refused ──
    $medRow2 = dbRow($conn,"SELECT ma.medicine_name,ma.patient_id,pr.doctor_id FROM medication_administration ma LEFT JOIN prescriptions pr ON ma.prescription_id=pr.id WHERE ma.id=?","i",[$mid]);
    if($medRow2 && ($medRow2['doctor_id']??0)){
      $pName = getPatientNameById($conn, (int)$medRow2['patient_id']);
      $nName = getNurseName($conn, $nurse_pk);
      $prio = ($ns==='Missed') ? 'high' : 'normal';
      notifyDoctor($conn, (int)$medRow2['doctor_id'], 'medication', "Medication $ns",
        "Nurse $nName marked {$medRow2['medicine_name']} as $ns for $pName. Reason: $rsn", 'medications', $mid, $prio);
    }
    $response=['success'=>true,'message'=>'Medication marked as '.$ns];
    break;

  case 'administer_new_medication':
    $pid  = (int)$_POST['patient_id'];
    $med  = sanitize($_POST['medicine_name']??'');
    $dose = sanitize($_POST['dosage']??'');
    $route= sanitize($_POST['route']??'Oral');
    $vby  = sanitize($_POST['verified_by']??'Manual');
    $notes= sanitize($_POST['notes']??'');
    if(!$pid||!$med||!$dose){$response=['success'=>false,'message'=>'Patient, medicine, and dosage required'];break;}
    dbInsert($conn,"INSERT INTO medication_administration (patient_id,nurse_id,medicine_name,dosage,route,status,scheduled_time,administered_at,verified_by,notes) VALUES (?,?,?,?,?,'Administered',NOW(),NOW(),?,?)","iisssss",[$pid,$nurse_pk,$med,$dose,$route,$vby,$notes]);
    logNurseActivity($conn,$nurse_pk,'med_administered','Administered '.$med.' to patient #'.$pid);
    $response=['success'=>true,'message'=>'Medication recorded successfully'];
    break;

  /* ═══ MODULE 4: BEDS & WARDS ═══ */
  case 'request_bed_transfer':
    $pid = (int)$_POST['patient_id'];
    $fw  = sanitize($_POST['from_ward']??'');
    $fb  = sanitize($_POST['from_bed']??'');
    $tw  = sanitize($_POST['to_ward']??'');
    $tb  = sanitize($_POST['to_bed']??'');
    $rsn = sanitize($_POST['reason']??'');
    dbInsert($conn,"INSERT INTO bed_transfers (patient_id,from_ward,from_bed_id,to_ward,to_bed_id,transfer_reason,requested_by,status,transfer_date,created_at) VALUES (?,?,?,?,?,?,?,'Requested',NOW(),NOW())","isssssi",[$pid,$fw,$fb,$tw,$tb,$rsn,$nurse_pk]);
    // ── Cross-Dashboard: Notify doctors + admins about transfer request ──
    $pName = getPatientNameById($conn, $pid);
    $nName = getNurseName($conn, $nurse_pk);
    $docPk = getAttendingDoctorPk($conn, $pid);
    if($docPk) notifyDoctor($conn, $docPk, 'bed_transfer', 'Bed Transfer Request',
      "Nurse $nName requests bed transfer for $pName from $fw/$fb to $tw/$tb. Reason: $rsn", 'beds');
    notifyAllAdmins($conn, 'bed_transfer', 'Bed Transfer Request',
      "Nurse $nName requests transfer for $pName ($fw→$tw). Reason: $rsn", 'beds');
    $response=['success'=>true,'message'=>'Transfer request submitted'];
    break;

  case 'set_isolation':
    $pid  = (int)$_POST['patient_id'];
    $type = sanitize($_POST['isolation_type']??'');
    $rsn  = sanitize($_POST['reason']??'');
    $prec = json_encode($_POST['precautions']??[]);
    dbInsert($conn,"INSERT INTO isolation_records (patient_id,isolation_type,reason,precautions,nurse_id,start_date,status,created_at) VALUES (?,?,?,?,?,NOW(),'Active',NOW())","isssi",[$pid,$type,$rsn,$prec,$nurse_pk]);
    $response=['success'=>true,'message'=>'Isolation activated'];
    break;

  case 'lift_isolation':
    $iid = (int)$_POST['isolation_id'];
    dbExecute($conn,"UPDATE isolation_records SET status='Lifted',end_date=NOW(),lifted_by=? WHERE id=?","ii",[$nurse_pk,$iid]);
    $response=['success'=>true,'message'=>'Isolation lifted'];
    break;

  /* ═══ MODULE 5: NURSING NOTES ═══ */
  case 'add_nursing_note':
    $pid  = (int)($_POST['patient_id']??0);
    $type = sanitize($_POST['note_type']??'General');
    $content = sanitize($_POST['note_content']??'');
    if(!$pid||!$content){$response=['success'=>false,'message'=>'Patient and note content required'];break;}
    $shift_id = dbVal($conn,"SELECT id FROM nurse_shifts WHERE nurse_id=? AND shift_date=? LIMIT 1","is",[$nurse_pk,$today]);
    dbInsert($conn,"INSERT INTO nursing_notes (patient_id,nurse_id,shift_id,note_type,note_content,created_at) VALUES (?,?,?,?,?,NOW())","iiiss",[$pid,$nurse_pk,$shift_id??0,$type,$content]);
    logNurseActivity($conn,$nurse_pk,'note_added','Added '.$type.' note for patient #'.$pid);
    // ── Cross-Dashboard: Notify attending doctor ──
    $pName = getPatientNameById($conn, $pid);
    $nName = getNurseName($conn, $nurse_pk);
    $docPk = getAttendingDoctorPk($conn, $pid);
    if($docPk) notifyDoctor($conn, $docPk, 'nursing_note', 'New Nursing Note',
      "Nurse $nName added a $type note for $pName. View in patient profile.", 'notes');
    $response=['success'=>true,'message'=>'Nursing note saved'];
    break;

  case 'get_note_detail':
    $nid = (int)$_POST['note_id'];
    $n = dbRow($conn,"SELECT nn.*,u.name AS patient_name FROM nursing_notes nn JOIN patients p ON nn.patient_id=p.id JOIN users u ON p.user_id=u.id WHERE nn.id=? AND nn.nurse_id=?","ii",[$nid,$nurse_pk]);
    if(!$n){$response=['success'=>false,'message'=>'Note not found'];break;}
    $response=['success'=>true,'data'=>['patient_name'=>$n['patient_name'],'patient_id'=>$n['patient_id'],'note_type'=>$n['note_type'],'note_content'=>htmlspecialchars($n['note_content']),'note_content_raw'=>$n['note_content'],'created_at'=>date('d M Y h:i A',strtotime($n['created_at'])),'is_locked'=>(int)($n['is_locked']??0)]];
    break;

  case 'add_wound_care':
    $pid = (int)($_POST['patient_id']??0);
    $loc = sanitize($_POST['wound_location']??'');
    if(!$pid||!$loc){$response=['success'=>false,'message'=>'Patient and wound location required'];break;}
    dbInsert($conn,"INSERT INTO wound_care_records (patient_id,nurse_id,wound_location,wound_type,wound_description,care_provided,dressing_type,healing_status,next_care_due,created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())","iissssssss",[$pid,$nurse_pk,$loc,sanitize($_POST['wound_type']??''),sanitize($_POST['wound_description']??''),sanitize($_POST['care_provided']??''),sanitize($_POST['dressing_type']??''),sanitize($_POST['healing_status']??'Stable'),$_POST['next_care_due']??null]);
    $response=['success'=>true,'message'=>'Wound care record saved'];
    break;

  /* ═══ MODULE 6: TASKS & SHIFTS ═══ */
  case 'update_task_status':
    $tid = (int)$_POST['task_id'];
    $st  = sanitize($_POST['status']??'');
    if(!in_array($st,['Pending','In Progress','Completed','Cancelled'])){$response=['success'=>false,'message'=>'Invalid status'];break;}
    dbExecute($conn,"UPDATE nurse_tasks SET status=?,updated_at=NOW() WHERE id=? AND nurse_id=?","sii",[$st,$tid,$nurse_pk]);
    $response=['success'=>true,'message'=>'Task updated to '.$st];
    break;

  case 'complete_task':
    $tid = (int)$_POST['task_id'];
    $notes = sanitize($_POST['notes']??'');
    dbExecute($conn,"UPDATE nurse_tasks SET status='Completed',completion_notes=?,completed_at=NOW(),updated_at=NOW() WHERE id=? AND nurse_id=?","sii",[$notes,$tid,$nurse_pk]);
    logNurseActivity($conn,$nurse_pk,'task_completed','Completed task #'.$tid);
    // ── Cross-Dashboard: Notify assigning doctor ──
    $task = dbRow($conn,"SELECT task_title,assigned_by,patient_id FROM nurse_tasks WHERE id=?","i",[$tid]);
    if($task && ($task['assigned_by']??0)){
      $nName = getNurseName($conn, $nurse_pk);
      notifyDoctor($conn, (int)$task['assigned_by'], 'task', 'Task Completed',
        "Nurse $nName completed task: {$task['task_title']}", 'tasks', $tid);
    }
    $response=['success'=>true,'message'=>'Task completed'];
    break;

  case 'submit_handover':
    $incoming = (int)($_POST['incoming_nurse_id']??0);
    $patients = sanitize($_POST['patient_summaries']??'');
    $pending  = sanitize($_POST['pending_tasks']??'');
    $critical = sanitize($_POST['critical_patients']??'');
    $notes    = sanitize($_POST['handover_notes']??'');
    $ward     = $nurse_row['ward_assigned']??'';
    dbInsert($conn,"INSERT INTO shift_handover (outgoing_nurse_id,incoming_nurse_id,shift_id,ward,patient_summaries,pending_tasks,critical_patients,handover_notes,submitted_at) VALUES (?,?,(SELECT id FROM nurse_shifts WHERE nurse_id=? AND shift_date=? LIMIT 1),?,?,?,?,?,NOW())","iiissssss",[$nurse_pk,$incoming,$nurse_pk,$today,$ward,$patients,$pending,$critical,$notes]);
    dbExecute($conn,"UPDATE nurse_shifts SET handover_submitted=1 WHERE nurse_id=? AND shift_date=?","is",[$nurse_pk,$today]);
    // ── Cross-Dashboard: Notify incoming nurse via both tables ──
    $nName = getNurseName($conn, $nurse_pk);
    if($incoming){
      dbInsert($conn,"INSERT INTO nurse_notifications (nurse_id,type,message,created_at) VALUES (?,'Handover','Shift handover submitted by $nName',NOW())","i",[$incoming]);
      notifyNurse($conn, $incoming, 'handover', 'Shift Handover Received',
        "Nurse $nName has submitted a shift handover for your review.", 'shifts');
    }
    $response=['success'=>true,'message'=>'Handover submitted'];
    break;

  case 'acknowledge_handover':
    $hid = (int)$_POST['handover_id'];
    dbExecute($conn,"UPDATE shift_handover SET acknowledged=1,acknowledged_at=NOW() WHERE id=? AND incoming_nurse_id=?","ii",[$hid,$nurse_pk]);
    $response=['success'=>true,'message'=>'Handover acknowledged'];
    break;

  case 'get_handover_detail':
    $hid = (int)$_POST['handover_id'];
    $h = dbRow($conn,"SELECT sh.*,uo.name AS outgoing_name,ui.name AS incoming_name FROM shift_handover sh LEFT JOIN nurses no2 ON sh.outgoing_nurse_id=no2.id LEFT JOIN users uo ON no2.user_id=uo.id LEFT JOIN nurses ni ON sh.incoming_nurse_id=ni.id LEFT JOIN users ui ON ni.user_id=ui.id WHERE sh.id=?","i",[$hid]);
    if(!$h){$response=['success'=>false,'message'=>'Not found'];break;}
    $response=['success'=>true,'data'=>$h];
    break;

  /* ═══ MODULE 7: EMERGENCY ═══ */
  case 'trigger_emergency':
    $type = sanitize($_POST['alert_type']??'General Emergency');
    $sev  = sanitize($_POST['severity']??'High');
    $loc  = sanitize($_POST['location']??'');
    $msg  = sanitize($_POST['message']??'');
    $pid  = (int)($_POST['patient_id']??0);
    // ── Security: Emergency cooldown — prevent double-triggering ──
    if(!checkEmergencyCooldown($conn, $nurse_pk)){
      $response=['success'=>false,'message'=>'Emergency alert already sent recently. Please wait 30 seconds before triggering again.'];
      break;
    }
    dbInsert($conn,"INSERT INTO emergency_alerts (alert_type,severity,location,message,patient_id,triggered_by,status,triggered_at) VALUES (?,?,?,?,?,?,'Active',NOW())","ssssiis",[$type,$sev,$loc,$msg,$pid?:null,$nurse_pk]);
    logNurseActivity($conn,$nurse_pk,'emergency','Triggered '.$type.' alert at '.$loc);
    // ── Cross-Dashboard: Broadcast to ALL doctors + ALL admins ──
    $nName = getNurseName($conn, $nurse_pk);
    $pInfo = $pid ? ' — Patient: '.getPatientNameById($conn, $pid) : '';
    notifyAllDoctors($conn, 'emergency', '🚨 '.$type, "Nurse $nName triggered $type at $loc$pInfo. $msg", 'emergency', null, 'critical');
    notifyAllAdmins($conn, 'emergency', '🚨 '.$type, "Nurse $nName triggered $type at $loc$pInfo. $msg", 'emergency', null, 'critical');
    $response=['success'=>true,'message'=>'🚨 Emergency alert sent to all staff'];
    break;

  case 'rapid_patient_lookup':
    $search = '%'.sanitize($_POST['search']??'').'%';
    $p = dbRow($conn,"SELECT p.*,u.name,u.date_of_birth AS dob,u.gender FROM patients p JOIN users u ON p.user_id=u.id WHERE u.name LIKE ? OR p.patient_id LIKE ? LIMIT 1","ss",[$search,$search]);
    if(!$p){$response=['success'=>false,'message'=>'Not found'];break;}
    $v = dbRow($conn,"SELECT * FROM patient_vitals WHERE patient_id=? ORDER BY recorded_at DESC LIMIT 1","i",[$p['id']]);
    $doc = dbRow($conn,"SELECT u.name FROM bed_assignments ba JOIN doctors d ON ba.attending_doctor_id=d.id JOIN users u ON d.user_id=u.id WHERE ba.patient_id=? AND ba.status='Active' LIMIT 1","i",[$p['id']]);
    $response=['success'=>true,'data'=>['name'=>$p['name'],'patient_id'=>$p['patient_id']??'','dob'=>$p['dob']??'','gender'=>$p['gender']??'','blood_type'=>$p['blood_type']??'','allergies'=>$p['known_allergies']??'None','doctor'=>$doc['name']??'Unassigned','bp'=>($v['bp_systolic']??'-').'/'.($v['bp_diastolic']??'-'),'hr'=>$v['pulse_rate']??'','temp'=>$v['temperature']??'','spo2'=>$v['oxygen_saturation']??'']];
    break;

  default:
    // Secure file download handler
    if($action === 'secure_download'){
      $fileId = (int)($_GET['file_id'] ?? $_POST['file_id'] ?? 0);
      $source = sanitize($_GET['source'] ?? $_POST['source'] ?? 'nurse_documents');
      if(!$fileId){$response=['success'=>false,'message'=>'File ID required'];break;}
      serveSecureFile($conn, $nurse_pk, $fileId, $source);
      break;
    }
    // Part 2 actions handled in include
    $part2 = __DIR__ . '/nurse_actions_part2.php';
    if(file_exists($part2)){ include $part2; }
    else { $response=['success'=>false,'message'=>'Unknown action: '.$action]; }
    break;
  }
} catch(Exception $e){
  $response=['success'=>false,'message'=>'Server error: '.$e->getMessage()];
}

echo json_encode($response);
