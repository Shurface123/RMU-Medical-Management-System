<?php
// ============================================================
// LAB TECHNICIAN ACTIONS — AJAX Handler
// All backend actions for the lab technician dashboard
// ============================================================
define('AJAX_REQUEST', true);
require_once 'lab_security.php';
initSecureSession();
$user_id = enforceLabTechRole();
require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');
require_once 'cross_notify.php';

header('Content-Type: application/json');

// CSRF check for all POST
verifyCsrfToken($_POST['_csrf'] ?? $_GET['_csrf'] ?? '');

$post = $_POST;
$action = $post['action'] ?? '';

// Get technician PK
$tech_pk = (int)(dbVal($conn,"SELECT id FROM lab_technicians WHERE user_id=?","i",[$user_id]) ?? 0);
if(!$tech_pk){ echo json_encode(['success'=>false,'message'=>'Technician profile not found']); exit; }

function ok($data=[]){ echo json_encode(array_merge(['success'=>true,'message'=>'Success'],$data)); exit; }
function fail($msg){ echo json_encode(['success'=>false,'message'=>$msg]); exit; }
function esc($conn,$v){ return mysqli_real_escape_string($conn, sanitize($v)); }

switch($action){

// ═══════════════════════════════════════════
// ORDER MANAGEMENT
// ═══════════════════════════════════════════
case 'accept_order':
    $oid=(int)($post['order_id']??0); if(!$oid) fail('Invalid order');
    dbExecute($conn,"UPDATE lab_test_orders SET technician_id=?, order_status='Accepted', updated_at=NOW() WHERE id=? AND order_status='Pending'","ii",[$tech_pk,$oid]);
    logLabActivity($conn,$tech_pk,'accept_order','orders',$oid);
    // Notify doctor
    $order=dbRow($conn,"SELECT lto.*, d.user_id AS doc_uid FROM lab_test_orders lto LEFT JOIN doctors d ON lto.doctor_id=d.id WHERE lto.id=?","i",[$oid]);
    if($order && $order['doc_uid']){
        crossNotify($conn,(int)$order['doc_uid'],'doctor','lab','Lab Order Accepted','Your lab test order '.$order['order_id'].' has been accepted and is being processed.','lab',$oid);
    }
    ok();

// Phase 6: Reassign Workload
case 'reassign_order':
    $oid=(int)($post['order_id']??0);
    $new_tech=(int)($post['new_tech_id']??0);
    if(!$oid || !$new_tech) fail('Order ID and New Technician ID required');
    // Verify target technician exists
    $tech_exists = dbRow($conn,"SELECT user_id, full_name FROM lab_technicians WHERE id=? AND status='Active'","i",[$new_tech]);
    if(!$tech_exists) fail('Technician not found or inactive');
    // Ensure order is not already completed
    $order_state = dbRow($conn,"SELECT order_status, order_id, technician_id FROM lab_test_orders WHERE id=?","i",[$oid]);
    if(!$order_state) fail('Order not found');
    if($order_state['order_status'] === 'Completed' || $order_state['order_status'] === 'Rejected') fail('Cannot reassign completed/rejected orders');
    
    // Perform reassignment
    dbExecute($conn,"UPDATE lab_test_orders SET technician_id=?, updated_at=NOW() WHERE id=?","ii",[$new_tech,$oid]);
    // Log Activity (transferring workload)
    logLabActivity($conn,$tech_pk,'reassign_order','orders',$oid);
    // Notify the unassigned tech
    if($order_state['technician_id'] && $order_state['technician_id'] != $tech_pk) {
        // Find their uid to notify
        $old_tech = dbRow($conn,"SELECT user_id FROM lab_technicians WHERE id=?","i",[(int)$order_state['technician_id']]);
        if($old_tech && $old_tech['user_id']) {
            dbExecute($conn,"INSERT INTO lab_notifications (recipient_id, message, type, title, related_module, related_record_id, priority) VALUES (?, 'Order {$order_state['order_id']} was reassigned away from you.', 'System', 'Workload Reassigned', 'orders', ?, 'normal')", "ii", [(int)$order_state['technician_id'], $oid]);
        }
    }
    // Notify the *new* assigned tech
    if($new_tech != $tech_pk) {
        dbExecute($conn,"INSERT INTO lab_notifications (recipient_id, message, type, title, related_module, related_record_id, priority) VALUES (?, 'Order {$order_state['order_id']} has been reassigned to you parameters.', 'System', 'New Order Assigned', 'orders', ?, 'high')", "ii", [$new_tech, $oid]);
    }
    
    ok();

case 'reject_order':
    $oid=(int)($post['order_id']??0); $reason=sanitize($post['reason']??'');
    if(!$oid||!$reason) fail('Order ID and reason required');
    dbExecute($conn,"UPDATE lab_test_orders SET order_status='Rejected', rejection_reason=?, technician_id=?, updated_at=NOW() WHERE id=? AND order_status='Pending'","sii",[$reason,$tech_pk,$oid]);
    logLabActivity($conn,$tech_pk,'reject_order','orders',$oid);
    $order=dbRow($conn,"SELECT lto.*, d.user_id AS doc_uid FROM lab_test_orders lto LEFT JOIN doctors d ON lto.doctor_id=d.id WHERE lto.id=?","i",[$oid]);
    if($order && $order['doc_uid']){
        crossNotify($conn,(int)$order['doc_uid'],'doctor','lab','Lab Order Rejected','Your lab test order '.$order['order_id'].' was rejected. Reason: '.$reason,'lab',$oid);
    }
    ok();

case 'collect_sample':
    $oid=(int)($post['order_id']??0); if(!$oid) fail('Invalid order');
    $sample_id='SMP-'.strtoupper(substr(md5(uniqid()),0,8));
    $sample_code='BC-'.date('Ymd').'-'.strtoupper(substr(md5(rand()),0,6));
    $order=dbRow($conn,"SELECT * FROM lab_test_orders WHERE id=?","i",[$oid]);
    if(!$order) fail('Order not found');
    dbExecute($conn,"INSERT INTO lab_samples (sample_id,order_id,patient_id,technician_id,sample_type,sample_code,collection_date,collection_time,collected_by,status) VALUES(?,?,?,?,'Blood',?,CURDATE(),CURTIME(),?,'Collected')","siissi",[$sample_id,$oid,(int)$order['patient_id'],$tech_pk,$sample_code,$user_id]);
    dbExecute($conn,"UPDATE lab_test_orders SET order_status='Sample Collected', updated_at=NOW() WHERE id=?","i",[$oid]);
    // Sync old lab_tests table so doctor dashboard reflects status
    if($order['request_id']) dbExecute($conn,"UPDATE lab_tests SET status='In Progress',updated_at=NOW() WHERE id=?","i",[(int)$order['request_id']]);
    logLabActivity($conn,$tech_pk,'collect_sample','samples',$oid);
    ok(['sample_id'=>$sample_id]);

case 'start_processing':
    $oid=(int)($post['order_id']??0); if(!$oid) fail('Invalid order');
    dbExecute($conn,"UPDATE lab_test_orders SET order_status='Processing', updated_at=NOW() WHERE id=?","i",[$oid]);
    // Sync old lab_tests so doctor sees 'In Progress'
    $o2=dbRow($conn,"SELECT request_id FROM lab_test_orders WHERE id=?","i",[$oid]);
    if($o2&&$o2['request_id']) dbExecute($conn,"UPDATE lab_tests SET status='In Progress',updated_at=NOW() WHERE id=?","i",[(int)$o2['request_id']]);
    logLabActivity($conn,$tech_pk,'start_processing','orders',$oid);
    ok();

// ═══════════════════════════════════════════
// SAMPLE MANAGEMENT
// ═══════════════════════════════════════════
case 'log_sample':
    $oid=(int)($post['order_id']??0); $pid=(int)($post['patient_id']??0);
    if(!$oid) fail('Select an order');
    $sample_id='SMP-'.strtoupper(substr(md5(uniqid()),0,8));
    $sample_code='BC-'.date('Ymd').'-'.strtoupper(substr(md5(rand()),0,6));
    $type=esc($conn,$post['sample_type']??'Blood');$container=esc($conn,$post['container_type']??'');
    $volume=esc($conn,$post['volume']??'');$storage=esc($conn,$post['storage']??'');
    $condition=esc($conn,$post['condition']??'Good');$notes=esc($conn,$post['notes']??'');
    dbExecute($conn,"INSERT INTO lab_samples (sample_id,order_id,patient_id,technician_id,sample_type,sample_code,collection_date,collection_time,collected_by,container_type,volume_collected,storage_location,condition_on_receipt,notes,status) VALUES(?,?,?,?,?,?,CURDATE(),CURTIME(),?,?,?,?,?,?,'Collected')","siisssissssss",[$sample_id,$oid,$pid,$tech_pk,$type,$sample_code,$user_id,$container,$volume,$storage,$condition,$notes]);
    logLabActivity($conn,$tech_pk,'log_sample','samples',null);
    ok(['sample_id'=>$sample_id]);

case 'update_sample_status':
    $sid=(int)($post['sample_id']??0); $status=esc($conn,$post['status']??'');
    if(!$sid||!$status) fail('Missing data');
    dbExecute($conn,"UPDATE lab_samples SET status=? WHERE id=? AND technician_id=?","sii",[$status,$sid,$tech_pk]);
    logLabActivity($conn,$tech_pk,'update_sample','samples',$sid);
    ok();

case 'reject_sample':
    $sid=(int)($post['sample_id']??0); $reason=sanitize($post['reason']??'');
    dbExecute($conn,"UPDATE lab_samples SET status='Rejected', rejection_reason=? WHERE id=?","si",[$reason,$sid]);
    logLabActivity($conn,$tech_pk,'reject_sample','samples',$sid);
    ok();

// ═══════════════════════════════════════════
// RESULTS
// ═══════════════════════════════════════════
case 'save_result':
    $oid=(int)($post['order_id']??0); if(!$oid) fail('Select an order');
    $order=dbRow($conn,"SELECT * FROM lab_test_orders WHERE id=?","i",[$oid]);
    if(!$order) fail('Order not found');
    $result_id='RES-'.strtoupper(substr(md5(uniqid()),0,8));
    $test_name=esc($conn,$post['test_name']??$order['test_name']);
    $vals=esc($conn,$post['result_values']??'');
    $unit=esc($conn,$post['unit']??'');
    $ref_min=$post['ref_min']!==''?(float)$post['ref_min']:null;
    $ref_max=$post['ref_max']!==''?(float)$post['ref_max']:null;
    $interp=esc($conn,$post['interpretation']??'Normal');
    $comments=esc($conn,$post['comments']??'');
    // Handle file upload
    $file_path='';
    if(isset($_FILES['report_file'])&&$_FILES['report_file']['error']===UPLOAD_ERR_OK){
        $v=validateUpload($_FILES['report_file'],['application/pdf','image/jpeg','image/png'],10485760);
        if(!$v['valid']) fail($v['error']);
        $ext=pathinfo($_FILES['report_file']['name'],PATHINFO_EXTENSION);
        $fname='lab_report_'.$result_id.'_'.time().'.'.$ext;
        $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/lab_reports/';
        if(!is_dir($dir)) mkdir($dir,0755,true);
        move_uploaded_file($_FILES['report_file']['tmp_name'],$dir.$fname);
        $file_path='uploads/lab_reports/'.$fname;
    }
    // Get sample if exists
    $sample=dbRow($conn,"SELECT id FROM lab_samples WHERE order_id=? LIMIT 1","i",[$oid]);
    $sample_id=$sample?$sample['id']:null;

    // Anomaly Detection Protocol (Phase 6)
    $ignore_anomaly = (int)($post['ignore_anomaly']??0);
    $param_data = json_decode($post['param_data']??'[]', true);
    
    // Process parameter anomaly detection
    $anomalies_detected = [];
    if(is_array($param_data) && !$ignore_anomaly){
        foreach($param_data as $p) {
            $val = (float)($p['value']??0);
            if($val == 0 && !is_numeric($p['value'])) continue; // Skip non-numeric qualitative results
            // Query patient's historical baseline for this specific parameter
            $hist_q = $conn->prepare("SELECT value FROM lab_result_parameters lrp JOIN lab_results_v2 lr2 ON lrp.result_id=lr2.id WHERE lr2.patient_id=? AND lrp.parameter_name=? AND lr2.result_status IN('Validated','Released') ORDER BY lr2.created_at DESC LIMIT 5");
            $hist_q->bind_param("is", $order['patient_id'], $p['param']);
            $hist_q->execute();
            $hist_res = $hist_q->get_result();
            $hist_vals = [];
            while($r = $hist_res->fetch_assoc()){
                $hval = (float)$r['value'];
                if(is_numeric($r['value'])) $hist_vals[] = $hval;
            }
            // If we have at least 2 historical points, calculate baseline
            if(count($hist_vals) >= 2) {
                $avg = array_sum($hist_vals) / count($hist_vals);
                if($avg > 0) {
                    $variance_pct = abs($val - $avg) / $avg * 100;
                    if($variance_pct > 30.0) { // 30% deviation threshold
                        $anomalies_detected[] = $p['param'] . " (" . number_format($variance_pct,1) . "% deviation from historical average of " . number_format($avg,2) . ")";
                    }
                }
            }
        }
        
        if(!empty($anomalies_detected)) {
            $msg = "Statistical Anomaly Detected: The following parameters deviate significantly from the patient's historical baseline:\\n\\n• " . implode("\\n• ", $anomalies_detected);
            echo json_encode(['success'=>false, 'message'=>$msg, 'is_anomaly'=>true]);
            exit;
        }
    }

    dbExecute($conn,"INSERT INTO lab_results_v2 (result_id,order_id,sample_id,patient_id,doctor_id,technician_id,test_name,result_values,unit_of_measurement,reference_range_min,reference_range_max,result_interpretation,result_status,report_file_path,technician_comments) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,'Draft',?,?)","siiiissssddssss",[$result_id,$oid,$sample_id,(int)$order['patient_id'],(int)$order['doctor_id'],$tech_pk,$test_name,$vals,$unit,$ref_min,$ref_max,$interp,$file_path,$comments]);
    $result_pk = mysqli_insert_id($conn);

    // Insert individual parameters for charting and future anomaly detection
    if(is_array($param_data)) {
        foreach($param_data as $p) {
            $p_name = esc($conn,$p['param']??'');
            $p_val = esc($conn,$p['value']??'');
            $p_unit = esc($conn,$p['unit']??'');
            $p_rmin = ($p['ref_min']!=='')?(float)$p['ref_min']:null;
            $p_rmax = ($p['ref_max']!=='')?(float)$p['ref_max']:null;
            $p_interp = esc($conn,$p['interp']??'Normal');
            // If this was an anomaly but ignored, update the flag
            if($ignore_anomaly && $p_interp === 'Normal') {
                $hist_q2 = $conn->prepare("SELECT value FROM lab_result_parameters lrp JOIN lab_results_v2 lr2 ON lrp.result_id=lr2.id WHERE lr2.patient_id=? AND lrp.parameter_name=? ORDER BY lr2.created_at DESC LIMIT 5");
                $hist_q2->bind_param("is", $order['patient_id'], $p_name);
                $hist_q2->execute();
                $hres = $hist_q2->get_result();
                $hvals=[]; while($r=$hres->fetch_assoc()) if(is_numeric($r['value'])) $hvals[]=(float)$r['value'];
                if(count($hvals)>=2) {
                    $avg = array_sum($hvals)/count($hvals);
                    if($avg>0 && abs((float)$p_val - $avg)/$avg * 100 > 30.0) {
                        $p_interp = ((float)$p_val > $avg) ? 'High' : 'Low'; // Tag anomaly direction
                    }
                }
            }
            dbExecute($conn,"INSERT INTO lab_result_parameters (result_id,parameter_name,value,unit,reference_range_min,reference_range_max,flag) VALUES(?,?,?,?,?,?,?)","isssdds",[$result_pk,$p_name,$p_val,$p_unit,$p_rmin,$p_rmax,$p_interp]);
        }
    }

    // Also update the old lab_results table for backward compat with doctor/patient dashboards
    $old_exists=dbVal($conn,"SELECT COUNT(*) FROM lab_results WHERE test_id=?","s",[$order['test_id']??$order['order_id']]);
    if(!$old_exists){
        dbExecute($conn,"INSERT INTO lab_results (test_id,patient_id,results,normal_range,interpretation,technician_notes,submitted_by,status,result_file_path,patient_accessible,result_interpretation) VALUES(?,?,?,?,?,?,?,'Pending',?,0,?)","sisssssisss",[$order['test_id']??$order['order_id'],(int)$order['patient_id'],$vals,$ref_min.'-'.$ref_max,$interp,$comments,$user_id,$file_path,$interp]);
    }

    logLabActivity($conn,$tech_pk,'save_result','results',null);

    // Notify the ordering doctor that a draft result is ready for validation review
    $doc_notify=dbRow($conn,"SELECT d.user_id AS doc_uid,u.name AS doc_name FROM lab_test_orders lto JOIN doctors d ON lto.doctor_id=d.id JOIN users u ON d.user_id=u.id WHERE lto.id=?","i",[$oid]);
    if($doc_notify&&$doc_notify['doc_uid']){
        crossNotify($conn,(int)$doc_notify['doc_uid'],'doctor','lab',
            '🔬 Lab Result Entered: '.$test_name,
            'A result has been entered for '.$test_name.' and is now undergoing validation. You will be notified once released.',
            'lab',$oid,'normal');
    }

    ok(['result_id'=>$result_id]);

case 'submit_for_validation':
    $rid=(int)($post['result_id']??0); if(!$rid) fail('Invalid ID');
    dbExecute($conn,"UPDATE lab_results_v2 SET result_status='Pending Validation', updated_at=NOW() WHERE id=? AND technician_id=?","ii",[$rid,$tech_pk]);
    logLabActivity($conn,$tech_pk,'submit_validation','results',$rid);
    ok();

case 'validate_result':
    $rid=(int)($post['result_id']??0); if(!$rid) fail('Invalid ID');
    enforceResultOwnership($conn,$rid,$tech_pk); // RBAC: only owner can validate
    dbExecute($conn,"UPDATE lab_results_v2 SET result_status='Validated', validated_by=?, validated_at=NOW(), updated_at=NOW() WHERE id=?","ii",[$tech_pk,$rid]);
    // Check if critical → notify immediately
    $res=dbRow($conn,"SELECT * FROM lab_results_v2 WHERE id=?","i",[$rid]);
    if($res && $res['result_interpretation']==='Critical'){
        $order=dbRow($conn,"SELECT d.user_id AS doc_uid FROM lab_test_orders lto JOIN doctors d ON lto.doctor_id=d.id WHERE lto.id=?","i",[(int)$res['order_id']]);
        if($order) crossNotify($conn,(int)$order['doc_uid'],'doctor','lab','⚠️ CRITICAL Lab Result','CRITICAL VALUE for test: '.$res['test_name'].'. Requires immediate attention.','lab',$rid,'high');
    }
    logLabActivity($conn,$tech_pk,'validate_result','results',$rid);
    ok();

case 'release_to_doctor':
    $rid=(int)($post['result_id']??0); if(!$rid) fail('Invalid ID');
    enforceResultOwnership($conn,$rid,$tech_pk); // RBAC: only owner can release
    enforceValidatedGate($conn,$rid);            // DB-level gate: must be Validated
    dbExecute($conn,"UPDATE lab_results_v2 SET released_to_doctor=1, released_at=NOW(), result_status='Released', updated_at=NOW() WHERE id=?","i",[$rid]);
    // Update order status
    $res=dbRow($conn,"SELECT * FROM lab_results_v2 WHERE id=?","i",[$rid]);
    if($res){
        dbExecute($conn,"UPDATE lab_test_orders SET order_status='Completed', updated_at=NOW() WHERE id=?","i",[(int)$res['order_id']]);
        $order=dbRow($conn,"SELECT request_id,test_name FROM lab_test_orders WHERE id=?","i",[(int)$res['order_id']]);

        // ── Sync old tables so doctor + patient dashboards reflect completion
        if($order&&$order['request_id']){
            $req_id=(int)$order['request_id'];
            dbExecute($conn,"UPDATE lab_tests SET status='Submitted',updated_at=NOW() WHERE id=?","i",[$req_id]);
            // Update or insert old lab_results row with the actual result values
            $old_exists=dbVal($conn,"SELECT COUNT(*) FROM lab_results WHERE test_id=(SELECT test_id FROM lab_tests WHERE id=? LIMIT 1)","i",[$req_id]);
            $test_id_str=dbVal($conn,"SELECT test_id FROM lab_tests WHERE id=? LIMIT 1","i",[$req_id]);
            if($old_exists){
                mysqli_query($conn,"UPDATE lab_results SET results='".esc($conn,$res['result_values'])."',
                    interpretation='".esc($conn,$res['result_interpretation'])."',
                    normal_range='".esc($conn,($res['reference_range_min']??'').'-'.($res['reference_range_max']??''))."',
                    technician_notes='".esc($conn,$res['technician_comments']??'')."',
                    submitted_by=$user_id, status='Submitted',
                    result_file_path='".esc($conn,$res['report_file_path']??'')."',
                    patient_accessible=0, doctor_reviewed=0
                    WHERE test_id='".esc($conn,$test_id_str)."'");
            }else{
                mysqli_query($conn,"INSERT INTO lab_results (test_id,patient_id,results,normal_range,interpretation,technician_notes,submitted_by,status,result_file_path,patient_accessible,result_interpretation)
                    VALUES('".esc($conn,$test_id_str)."',{$res['patient_id']},'".esc($conn,$res['result_values'])."','".esc($conn,($res['reference_range_min']??'').'-'.($res['reference_range_max']??''))."','".esc($conn,$res['result_interpretation'])."','".esc($conn,$res['technician_comments']??'')."',$user_id,'Submitted','".esc($conn,$res['report_file_path']??'')."',0,'".esc($conn,$res['result_interpretation'])."')");
            }
        }

        // ── Notify doctor urgently (use high priority for critical results)
        $is_critical=$res['result_interpretation']==='Critical';
        $doc=dbRow($conn,"SELECT d.user_id AS doc_uid FROM lab_test_orders lto JOIN doctors d ON lto.doctor_id=d.id WHERE lto.id=?","i",[(int)$res['order_id']]);
        if($doc){
            $title=$is_critical?'⚠️ CRITICAL Result Ready: '.$res['test_name']:'✅ Lab Results Ready: '.$res['test_name'];
            $msg=$is_critical
                ?'CRITICAL VALUE reported for '.$res['test_name'].'. Result: '.$res['result_values'].'. Immediate attention required.'
                :'Lab results for '.$res['test_name'].' are validated and ready for your review.';
            crossNotify($conn,(int)$doc['doc_uid'],'doctor','lab',$title,$msg,'lab',$rid,$is_critical?'high':'normal');
        }
    }
    logLabActivity($conn,$tech_pk,'release_result','results',$rid);
    ok();

// ═══════════════════════════════════════════
// CATALOG
// ═══════════════════════════════════════════
case 'save_test_catalog':
    $id=(int)($post['id']??0);
    $name=esc($conn,$post['test_name']??'');$code=esc($conn,$post['test_code']??'');
    $cat=esc($conn,$post['category']??'Other');$sample=esc($conn,$post['sample_type']??'Blood');
    $container=esc($conn,$post['container_type']??'');$price=(float)($post['price']??0);
    $proc=(float)($post['processing_time']??1);$tat=(float)($post['tat']??24);
    $instr=esc($conn,$post['instructions']??'');$fasting=(int)($post['fasting']??0);
    if(!$name||!$code) fail('Name and code required');
    if($id){
        dbExecute($conn,"UPDATE lab_test_catalog SET test_name=?,test_code=?,category=?,sample_type=?,container_type=?,price=?,processing_time_hours=?,normal_turnaround_hours=?,collection_instructions=?,requires_fasting=? WHERE id=?","sssssdddsii",[$name,$code,$cat,$sample,$container,$price,$proc,$tat,$instr,$fasting,$id]);
    }else{
        dbExecute($conn,"INSERT INTO lab_test_catalog (test_name,test_code,category,sample_type,container_type,price,processing_time_hours,normal_turnaround_hours,collection_instructions,requires_fasting) VALUES(?,?,?,?,?,?,?,?,?,?)","sssssdddsi",[$name,$code,$cat,$sample,$container,$price,$proc,$tat,$instr,$fasting]);
    }
    logLabActivity($conn,$tech_pk,$id?'update_catalog':'add_catalog','catalog',$id);
    ok();

case 'delete_test_catalog':
    $id=(int)($post['id']??0); if(!$id) fail('Invalid');
    dbExecute($conn,"UPDATE lab_test_catalog SET is_active=0 WHERE id=?","i",[$id]);
    logLabActivity($conn,$tech_pk,'delete_catalog','catalog',$id);
    ok();

// ═══════════════════════════════════════════
// EQUIPMENT
// ═══════════════════════════════════════════
case 'save_equipment':
    $id=(int)($post['id']??0);
    $name=esc($conn,$post['name']??'');if(!$name) fail('Name required');
    $fields=['model','serial_number','manufacturer','category','location','purchase_date','warranty_expiry','status','next_calibration_date','notes'];
    $vals=[]; foreach($fields as $f) $vals[$f]=esc($conn,$post[$f]??'');
    if($id){
        $sets=[]; foreach($vals as $k=>$v) $sets[]="`$k`='$v'";
        mysqli_query($conn,"UPDATE lab_equipment SET name='$name',".implode(',',$sets).",updated_at=NOW() WHERE id=$id");
    }else{
        $cols=['name']; $vps=["'$name'"];
        foreach($vals as $k=>$v){ $cols[]="`$k`"; $vps[]="'$v'"; }
        mysqli_query($conn,"INSERT INTO lab_equipment (".implode(',',$cols).") VALUES(".implode(',',$vps).")");
    }
    logLabActivity($conn,$tech_pk,$id?'update_equipment':'add_equipment','equipment',$id);
    $eq_id_new=$id?$id:(int)mysqli_insert_id($conn);
    // Notify all admins if status is critical or calibration is overdue
    $alert_statuses=['Out of Service','Calibration Due','Maintenance'];
    $new_status=$vals['status']??'';
    $next_cal=$vals['next_calibration_date']??'';
    $cal_overdue=($next_cal&&$next_cal<=date('Y-m-d'));
    if(in_array($new_status,$alert_statuses)||$cal_overdue){
        $alert_msg=$cal_overdue
            ?"Lab equipment '$name' calibration is overdue (due: $next_cal). Status: $new_status."
            :"Lab equipment '$name' has critical status: $new_status. Immediate attention required.";
        notifyAllAdmins($conn,'equipment','⚠️ Lab Equipment Alert: '.$name,$alert_msg,'equipment',$eq_id_new,'high');
    }
    ok();

case 'log_maintenance':
    $eq_id=(int)($post['equipment_id']??0); if(!$eq_id) fail('Invalid');
    $type=esc($conn,$post['maintenance_type']??'Service');
    $at=esc($conn,$post['performed_at']??date('Y-m-d H:i:s'));
    $next=esc($conn,$post['next_due']??'');
    $findings=esc($conn,$post['findings']??'');
    $cost=(float)($post['cost']??0);
    dbExecute($conn,"INSERT INTO equipment_maintenance_log (equipment_id,maintenance_type,performed_by_id,performed_at,next_due_date,findings,cost) VALUES(?,?,?,?,?,?,?)","sisssd",[$eq_id,$type,$tech_pk,$at,$next?:null,$findings,$cost]);
    // Update equipment dates
    if($type==='Calibration') mysqli_query($conn,"UPDATE lab_equipment SET last_calibration_date=CURDATE(), next_calibration_date='$next', status='Operational', updated_at=NOW() WHERE id=$eq_id");
    else mysqli_query($conn,"UPDATE lab_equipment SET last_maintenance_date=CURDATE(), next_maintenance_date='$next', updated_at=NOW() WHERE id=$eq_id");
    logLabActivity($conn,$tech_pk,'log_maintenance','equipment',$eq_id);
    ok();

// ═══════════════════════════════════════════
// REAGENTS
// ═══════════════════════════════════════════
case 'save_reagent':
    $id=(int)($post['id']??0);$name=esc($conn,$post['name']??'');if(!$name) fail('Name required');
    $cat_num=esc($conn,$post['catalog_number']??'');$mfr=esc($conn,$post['manufacturer']??'');$cat=esc($conn,$post['category']??'');
    $unit=esc($conn,$post['unit']??'pcs');$qty=(int)($post['quantity']??0);$reorder=(int)($post['reorder_level']??5);
    $cost=(float)($post['unit_cost']??0);$exp=esc($conn,$post['expiry_date']??'');$batch=esc($conn,$post['batch_number']??'');
    $storage=esc($conn,$post['storage_conditions']??'');
    // Auto status
    $status='In Stock';
    if($qty<=0) $status='Out of Stock';
    elseif($qty<=$reorder) $status='Low Stock';
    if($exp && $exp<date('Y-m-d')) $status='Expired';
    elseif($exp && $exp<=date('Y-m-d',strtotime('+30 days'))) $status='Expiring Soon';
    if($id){
        mysqli_query($conn,"UPDATE reagent_inventory SET name='$name',catalog_number='$cat_num',manufacturer='$mfr',category='$cat',unit='$unit',quantity_in_stock=$qty,reorder_level=$reorder,unit_cost=$cost,expiry_date=".($exp?"'$exp'":"NULL").",batch_number='$batch',storage_conditions='$storage',status='$status',updated_at=NOW() WHERE id=$id");
    }else{
        mysqli_query($conn,"INSERT INTO reagent_inventory (name,catalog_number,manufacturer,category,unit,quantity_in_stock,reorder_level,unit_cost,expiry_date,batch_number,storage_conditions,status) VALUES('$name','$cat_num','$mfr','$cat','$unit',$qty,$reorder,$cost,".($exp?"'$exp'":"NULL").",'$batch','$storage','$status')");
    }
    logLabActivity($conn,$tech_pk,$id?'update_reagent':'add_reagent','reagents',$id);
    // Notify all admins if stock is critically low, out, or expired
    if(in_array($status,['Low Stock','Out of Stock','Expired','Expiring Soon'])){
        if($status==='Out of Stock')     { $alert_title='Reagent Out of Stock: '.$name;   $alert_msg="Reagent '$name' is completely out of stock (0 $unit). Procurement required immediately."; }
        elseif($status==='Expired')      { $alert_title='Expired Reagent: '.$name;         $alert_msg="Reagent '$name' has expired (expiry: $exp). Must be disposed and restocked."; }
        elseif($status==='Expiring Soon'){ $alert_title='Reagent Expiring Soon: '.$name;   $alert_msg="Reagent '$name' expires on $exp. Only $qty $unit remaining."; }
        else                             { $alert_title='Low Reagent Stock: '.$name;        $alert_msg="Reagent '$name' is low (remaining: $qty $unit, reorder level: $reorder). Please arrange procurement."; }
        notifyAllAdmins($conn,'reagent',$alert_title,$alert_msg,'reagents',$id?:0,
            in_array($status,['Out of Stock','Expired'])?'high':'normal');
    }
    ok();

case 'reagent_transaction':
    $rid=(int)($post['reagent_id']??0);$type=esc($conn,$post['type']??'');$qty=(int)($post['quantity']??0);
    if(!$rid||!$qty) fail('Invalid data');
    $rg=dbRow($conn,"SELECT * FROM reagent_inventory WHERE id=?","i",[$rid]);
    if(!$rg) fail('Reagent not found');
    $prev=(int)$rg['quantity_in_stock'];
    $new_qty=$type==='Used'||$type==='Disposed'?max(0,$prev-$qty):$prev+$qty;
    dbExecute($conn,"INSERT INTO reagent_transactions (reagent_id,transaction_type,quantity,previous_quantity,new_quantity,performed_by) VALUES(?,?,?,?,?,?)","isiiis",[$rid,$type,$qty,$prev,$new_qty,$tech_pk]);
    // Update status
    $status='In Stock';
    if($new_qty<=0) $status='Out of Stock';
    elseif($new_qty<=(int)$rg['reorder_level']) $status='Low Stock';
    mysqli_query($conn,"UPDATE reagent_inventory SET quantity_in_stock=$new_qty, status='$status', updated_at=NOW() WHERE id=$rid");
    logLabActivity($conn,$tech_pk,'reagent_'.$type,'reagents',$rid);
    // Notify admins if stock just dropped to critical level
    if(in_array($status,['Low Stock','Out of Stock'])&&in_array($type,['Used','Disposed'])){
        $rg_name=esc($conn,$rg['name']);
        $unit_lbl=esc($conn,$rg['unit']??'units');
        $title=$status==='Out of Stock'?"\u{1F6D1} Reagent Out of Stock: $rg_name":"\u26A0\uFE0F Low Reagent Stock: $rg_name";
        $msg=$status==='Out of Stock'
            ?"Reagent '$rg_name' is now out of stock after a $type transaction. Reorder immediately."
            :"Reagent '$rg_name' dropped to $new_qty $unit_lbl (reorder level: {$rg['reorder_level']}). Please arrange procurement.";
        notifyAllAdmins($conn,'reagent',$title,$msg,'reagents',$rid,$status==='Out of Stock'?'high':'normal');
    }
    ok(['new_quantity'=>$new_qty]);

// ═══════════════════════════════════════════
// QUALITY CONTROL
// ═══════════════════════════════════════════
case 'save_qc':
    $eq_id=(int)($post['equipment_id']??0);$tc_id=(int)($post['test_catalog_id']??0);
    $level=esc($conn,$post['qc_level']??'Normal');
    $material=esc($conn,$post['qc_material']??'');$lot=esc($conn,$post['lot_number']??'');
    $expected=esc($conn,$post['expected_value']??'');$actual=esc($conn,$post['actual_value']??'');
    $sd=esc($conn,$post['standard_deviation']??'');$qcResult=esc($conn,$post['qc_result']??'PASS');
    $remarks=esc($conn,$post['remarks']??'');
    if(!$actual) fail('Actual value required');
    dbExecute($conn,"INSERT INTO lab_quality_control (technician_id,equipment_id,test_catalog_id,qc_date,qc_level,qc_material,lot_number,expected_value,actual_value,standard_deviation,qc_result,remarks) VALUES(?,?,?,CURDATE(),?,?,?,?,?,?,?,?)","iiisssssssss",[$tech_pk,$eq_id?:null,$tc_id?:null,$level,$material,$lot,$expected,$actual,$sd,$qcResult,$remarks]);
    logLabActivity($conn,$tech_pk,'qc_run','quality_control',null);
    ok(['passed'=>$qcResult==='PASS']);

case 'log_corrective_action':
    $qc_id=(int)($post['qc_id']??0);$action_text=sanitize($post['corrective_action']??'');
    if(!$qc_id||!$action_text) fail('Required');
    dbExecute($conn,"UPDATE lab_quality_control SET corrective_action=?, updated_at=NOW() WHERE id=?","si",[$action_text,$qc_id]);
    logLabActivity($conn,$tech_pk,'log_corrective_action','quality_control',$qc_id);
    ok();

// ═══════════════════════════════════════════
// NOTIFICATIONS
// ═══════════════════════════════════════════
case 'mark_notification_read':
    $id=(int)($post['notification_id']??$post['id']??0);
    // Mark in lab_notifications table (lab dashboard)
    dbExecute($conn,"UPDATE lab_notifications SET is_read=1 WHERE id=? AND recipient_id=?","ii",[$id,$tech_pk]);
    // Also mark in shared notifications table (for cross-dashboard notifications addressed to this user)
    dbExecute($conn,"UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?","ii",[$id,$user_id]);
    ok();

case 'mark_all_notifications_read':
    dbExecute($conn,"UPDATE lab_notifications SET is_read=1 WHERE recipient_id=? AND is_read=0","i",[$tech_pk]);
    // Also mark all shared cross-dashboard notifications as read for this user
    dbExecute($conn,"UPDATE notifications SET is_read=1 WHERE user_id=? AND user_role='lab_technician' AND is_read=0","i",[$user_id]);
    ok();

// ═══════════════════════════════════════════
// REFERENCE RANGES
// ═══════════════════════════════════════════
case 'save_ref_range':
    $id=(int)($post['id']??0);
    $tc_id=(int)($post['test_catalog_id']??0);$param=sanitize($post['parameter_name']??'');
    $gender=esc($conn,$post['gender']??'All');$age_group=esc($conn,$post['age_group']??'All');
    $unit=esc($conn,$post['unit']??'');$nmin=(float)($post['normal_min']??0);$nmax=(float)($post['normal_max']??0);
    $clow=$post['critical_low']!==''?(float)$post['critical_low']:null;
    $chigh=$post['critical_high']!==''?(float)$post['critical_high']:null;
    if(!$param) fail('Parameter name required');
    if($id){
        mysqli_query($conn,"UPDATE lab_reference_ranges SET test_catalog_id=".($tc_id?:0).",parameter_name='".esc($conn,$param)."',gender='$gender',age_group='$age_group',unit='$unit',normal_min=$nmin,normal_max=$nmax,critical_low=".($clow!==null?$clow:'NULL').",critical_high=".($chigh!==null?$chigh:'NULL').",updated_at=NOW() WHERE id=$id");
    }else{
        mysqli_query($conn,"INSERT INTO lab_reference_ranges (test_catalog_id,parameter_name,gender,age_group,unit,normal_min,normal_max,critical_low,critical_high) VALUES($tc_id,'".esc($conn,$param)."','$gender','$age_group','$unit',$nmin,$nmax,".($clow!==null?$clow:'NULL').",".($chigh!==null?$chigh:'NULL').")");
    }
    logLabActivity($conn,$tech_pk,$id?'update_ref_range':'add_ref_range','reference_ranges',$id);
    ok();

case 'delete_ref_range':
    $id=(int)($post['id']??0); if(!$id) fail('Invalid');
    dbExecute($conn,"DELETE FROM lab_reference_ranges WHERE id=?","i",[$id]);
    logLabActivity($conn,$tech_pk,'delete_ref_range','reference_ranges',$id);
    ok();

case 'get_ref_params':
    $tc_id=(int)($post['test_catalog_id']??0);
    $params=[];
    $q=mysqli_query($conn,"SELECT * FROM lab_reference_ranges WHERE test_catalog_id=$tc_id ORDER BY parameter_name");
    if($q) while($r=mysqli_fetch_assoc($q)) $params[]=$r;
    ok(['params'=>$params]);

case 'preview_ref_range':
    $tc_id=(int)($post['test_catalog_id']??0);$param=sanitize($post['parameter_name']??'');
    $value=(float)($post['value']??0);$gender=esc($conn,$post['gender']??'All');
    $rr=dbRow($conn,"SELECT * FROM lab_reference_ranges WHERE test_catalog_id=? AND parameter_name=? AND (gender=? OR gender='All') LIMIT 1","iss",[$tc_id,$param,$gender]);
    if(!$rr) fail('No reference range found for this parameter');
    $flag='Normal';
    if($rr['critical_low']!==null && $value<(float)$rr['critical_low']) $flag='Critical Low';
    elseif($rr['critical_high']!==null && $value>(float)$rr['critical_high']) $flag='Critical High';
    elseif($value<(float)$rr['normal_min']) $flag='Low';
    elseif($value>(float)$rr['normal_max']) $flag='High';
    ok(['flag'=>$flag,'unit'=>$rr['unit'],'normal_min'=>$rr['normal_min'],'normal_max'=>$rr['normal_max']]);

// ═══════════════════════════════════════════
// MESSAGES
// ═══════════════════════════════════════════
case 'send_message':
    $recipient=(int)($post['recipient_id']??0);$msg=sanitize($post['message']??'');
    if(!$recipient||!$msg) fail('Recipient and message required');
    $order_id=(int)($post['order_id']??0);$msg_type=esc($conn,$post['message_type']??'General');
    $subject=sanitize($post['subject']??'');
    dbExecute($conn,"INSERT INTO lab_internal_messages (sender_id,recipient_id,order_id,message_type,subject,message) VALUES(?,?,?,?,?,?)","iiisss",[$user_id,$recipient,$order_id?:null,$msg_type,$subject,$msg]);
    // If critical alert, also send cross-notification
    if($msg_type==='Critical Alert'){
        crossNotify($conn,$recipient,'doctor','lab','⚠️ Critical Value Alert from Lab',$msg,'lab',$order_id);
    }
    logLabActivity($conn,$tech_pk,'send_message','messages',null);
    ok();

case 'mark_message_read':
    $id=(int)($post['id']??0);
    dbExecute($conn,"UPDATE lab_internal_messages SET is_read=1, read_at=NOW() WHERE id=?","i",[$id]);
    ok();

// ═══════════════════════════════════════════
// ENHANCED SAMPLE MANAGEMENT
// ═══════════════════════════════════════════
case 'collect_sample_detailed':
    $oid=(int)($post['order_id']??0); if(!$oid) fail('Invalid order');
    $order=dbRow($conn,"SELECT * FROM lab_test_orders WHERE id=?","i",[$oid]);
    if(!$order) fail('Order not found');
    $sample_id='SMP-'.strtoupper(substr(md5(uniqid()),0,8));
    $sample_code='BC-'.date('Ymd').'-'.strtoupper(substr(md5(rand()),0,6));
    $type=esc($conn,$post['sample_type']??'Blood');$container=esc($conn,$post['container_type']??'');
    $volume=esc($conn,$post['volume']??'');$condition=esc($conn,$post['condition']??'Good');
    $storage=esc($conn,$post['storage']??'');$notes=esc($conn,$post['notes']??'');
    // Bad condition → reject sample, notify doctor for re-collection
    if(in_array($condition,['Haemolysed','Clotted','Insufficient','Contaminated'])){
        dbExecute($conn,"INSERT INTO lab_samples (sample_id,order_id,patient_id,technician_id,sample_type,sample_code,container_type,volume_collected,collection_date,collection_time,collected_by,condition_on_receipt,storage_location,notes,status,rejection_reason) VALUES(?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),?,?,?,?,'Rejected',?)","siisssssisssss",[$sample_id,$oid,(int)$order['patient_id'],$tech_pk,$type,$sample_code,$container,$volume,$user_id,$condition,$storage,$notes,'Sample condition: '.$condition]);
        $doc=dbRow($conn,"SELECT d.user_id AS doc_uid FROM lab_test_orders lto JOIN doctors d ON lto.doctor_id=d.id WHERE lto.id=?","i",[$oid]);
        if($doc) crossNotify($conn,(int)$doc['doc_uid'],'doctor','lab','Sample Rejected — Re-collection Needed','Sample for order '.$order['order_id'].' was rejected due to: '.$condition.'. Please arrange re-collection.','lab',$oid);
        logLabActivity($conn,$tech_pk,'reject_sample','samples',$oid);
        ok(['message'=>'Sample rejected ('.$condition.'). Doctor notified for re-collection.','sample_id'=>$sample_id]);
    }
    dbExecute($conn,"INSERT INTO lab_samples (sample_id,order_id,patient_id,technician_id,sample_type,sample_code,container_type,volume_collected,collection_date,collection_time,collected_by,condition_on_receipt,storage_location,notes,status) VALUES(?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),?,?,?,?,'Collected')","siisssssissss",[$sample_id,$oid,(int)$order['patient_id'],$tech_pk,$type,$sample_code,$container,$volume,$user_id,$condition,$storage,$notes]);
    dbExecute($conn,"UPDATE lab_test_orders SET order_status='Sample Collected', updated_at=NOW() WHERE id=?","i",[$oid]);
    // Also sync old lab_tests table for doctor dashboard status visibility
    if($order['request_id']) dbExecute($conn,"UPDATE lab_tests SET status='In Progress',updated_at=NOW() WHERE id=?","i",[(int)$order['request_id']]);
    logLabActivity($conn,$tech_pk,'collect_sample','samples',$oid);
    ok(['sample_id'=>$sample_id]);

case 'mark_sample_received':
    $oid=(int)($post['order_id']??0); if(!$oid) fail('Invalid');
    $sample=dbRow($conn,"SELECT id FROM lab_samples WHERE order_id=? LIMIT 1","i",[$oid]);
    if($sample) dbExecute($conn,"UPDATE lab_samples SET status='Received' WHERE id=?","i",[(int)$sample['id']]);
    logLabActivity($conn,$tech_pk,'receive_sample','samples',$oid);
    ok();

case 'update_sample_location':
    $sid=(int)($post['sample_id']??0);$loc=sanitize($post['location']??'');
    if(!$sid||!$loc) fail('Required');
    dbExecute($conn,"UPDATE lab_samples SET storage_location=? WHERE id=?","si",[$loc,$sid]);
    logLabActivity($conn,$tech_pk,'update_location','samples',$sid);
    ok();

// ═══════════════════════════════════════════
// RESULT AMENDMENT
// ═══════════════════════════════════════════
case 'amend_result':
    $rid=(int)($post['result_id']??0); $new_val=sanitize($post['new_value']??''); $reason=sanitize($post['reason']??'');
    if(!$rid||!$new_val||!$reason) fail('All fields required');
    // Rate limit: max 5 amendments per minute to prevent abuse
    if(rateLimitAction($conn,$tech_pk,'amend_result',5)) fail('Too many amendment requests. Please wait.');
    // RBAC + status gates
    enforceResultOwnership($conn,$rid,$tech_pk); // Must own the result
    enforceAmendGate($conn,$rid);                 // Must be Validated/Released/Amended
    $old=dbRow($conn,"SELECT * FROM lab_results_v2 WHERE id=?","i",[$rid]);
    if(!$old) fail('Result not found');
    // Immutable audit trail insert using prepared statement (not raw SQL)
    dbExecute($conn,
        "INSERT INTO lab_audit_trail (technician_id,action_type,module_affected,record_id,old_value,new_value,ip_address,device_info) VALUES(?,?,?,?,?,?,?,?)",
        "isissssss",
        [$tech_pk,'amend_result','results',$rid,$old['result_values'],$new_val,$_SERVER['REMOTE_ADDR']??'',substr($_SERVER['HTTP_USER_AGENT']??'',0,255)]
    );
    dbExecute($conn,"UPDATE lab_results_v2 SET result_values=?, amended_reason=?, result_status='Amended', updated_at=NOW() WHERE id=?","ssi",[$new_val,$reason,$rid]);
    // Notify doctor about amendment with old+new values
    $doc=dbRow($conn,"SELECT d.user_id AS doc_uid FROM lab_test_orders lto JOIN doctors d ON lto.doctor_id=d.id WHERE lto.id=?","i",[(int)$old['order_id']]);
    if($doc) crossNotify($conn,(int)$doc['doc_uid'],'doctor','lab',
        'Lab Result Amended: '.$old['test_name'],
        'Result for '.$old['test_name'].' was amended. Previous: '.$old['result_values'].' → New: '.$new_val.'. Reason: '.$reason,
        'lab',$rid,'normal');
    logLabActivity($conn,$tech_pk,'amend_result','results',$rid);
    ok(['message'=>'Result amended. Doctor notified with old and new values.']);

// Critical Value Acknowledgement
case 'acknowledge_critical':
    $rid=(int)($post['result_id']??0); $method=sanitize($post['notification_method']??'dashboard');
    if(!$rid) fail('Invalid result ID');
    // Verify result is critical
    $res=dbRow($conn,"SELECT * FROM lab_results_v2 WHERE id=? AND technician_id=?","ii",[$rid,$tech_pk]);
    if(!$res) fail('Result not found or not yours');
    if($res['result_interpretation']!=='Critical') fail('Result is not flagged as Critical');
    // Log the acknowledgement as a timestamped, audited event
    acknowledgeCriticalValue($conn,$tech_pk,$rid,$method);
    // Also immediately crossNotify the doctor (reinforcement)
    $doc=dbRow($conn,"SELECT d.user_id AS doc_uid FROM lab_test_orders lto JOIN doctors d ON lto.doctor_id=d.id WHERE lto.id=?","i",[(int)$res['order_id']]);
    if($doc) crossNotify($conn,(int)$doc['doc_uid'],'doctor','lab',
        '🚨 URGENT: Critical Value — '.$res['test_name'],
        'CRITICAL VALUE ALERT: '.$res['test_name'].' result is '.$res['result_values'].'. Technician has confirmed notification via: '.$method.'. Immediate clinical action required.',
        'lab',$rid,'high');
    ok(['message'=>'Critical value acknowledgement logged. Doctor re-notified.']);

// ═══════════════════════════════════════════
// PROFILE
// ═══════════════════════════════════════════
case 'update_profile_photo':
    if(!isset($_FILES['photo'])) fail('No file');
    $v=validateUpload($_FILES['photo'],['image/jpeg','image/png'],2097152);
    if(!$v['valid']) fail($v['error']);
    $ext=pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION);
    $fname='lab_photo_'.$tech_pk.'_'.time().'.'.$ext;
    $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/lab_photos/';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    move_uploaded_file($_FILES['photo']['tmp_name'],$dir.$fname);
    $path='uploads/lab_photos/'.$fname;
    dbExecute($conn,"UPDATE lab_technicians SET profile_photo=? WHERE id=?","si",[$path,$tech_pk]);
    logLabActivity($conn,$tech_pk,'upload_photo','profile',null);
    ok();

case 'upload_profile_photo':
    if(!isset($_FILES['photo'])) fail('No file');
    $v=validateUpload($_FILES['photo'],['image/jpeg','image/png'],2097152);
    if(!$v['valid']) fail($v['error']);
    $ext=pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION);
    $fname='lab_photo_'.$tech_pk.'_'.time().'.'.$ext;
    $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/lab_photos/';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    move_uploaded_file($_FILES['photo']['tmp_name'],$dir.$fname);
    $path='uploads/lab_photos/'.$fname;
    dbExecute($conn,"UPDATE lab_technicians SET profile_photo=? WHERE id=?","si",[$path,$tech_pk]);
    logLabActivity($conn,$tech_pk,'upload_photo','profile',null);
    ok();

case 'update_personal_info':
    $map=['name'=>'full_name','email'=>'email','phone'=>'phone','dob'=>'date_of_birth','gender'=>'gender',
        'nationality'=>'nationality','address'=>'street_address','marital_status'=>'marital_status',
        'religion'=>'religion','national_id'=>'national_id','postal_code'=>'postal_code',
        'secondary_phone'=>'secondary_phone','personal_email'=>'personal_email',
        'street_address'=>'street_address','city'=>'city','region'=>'region','country'=>'country'];
    $sets=[]; $types=''; $params=[];
    foreach($map as $fk=>$col){
        if(isset($post[$fk])){$sets[]="`$col`=?";$types.='s';$params[]=sanitize($post[$fk]);}
        elseif(isset($_POST[$fk])){$sets[]="`$col`=?";$types.='s';$params[]=sanitize($_POST[$fk]);}
    }
    if(empty($sets)) fail('No data');
    $params[]=$tech_pk; $types.='i';
    dbExecute($conn,"UPDATE lab_technicians SET ".implode(',',$sets).",updated_at=NOW() WHERE id=?",$types,$params);
    if(isset($post['name'])||isset($_POST['name']))
        dbExecute($conn,"UPDATE users SET name=? WHERE id=?","si",[sanitize($post['name']??$_POST['name']??''),$user_id]);
    if(isset($_POST['email'])&&$_POST['email'])
        dbExecute($conn,"UPDATE users SET email=? WHERE id=?","si",[$_POST['email'],$user_id]);
    updateProfileCompleteness($conn,$tech_pk);
    logLabActivity($conn,$tech_pk,'update_personal','profile',null);
    ok(['message'=>'Personal information updated successfully']);

case 'update_professional_info':
    $fields=['specialization','designation','license_number','license_expiry','years_of_experience',
        'sub_specialization','license_issuing_body','institution_attended','graduation_year',
        'postgraduate_details','bio'];
    $sets=[]; $types=''; $params=[];
    foreach($fields as $f){
        $val=$post[$f]??$_POST[$f]??null;
        if($val!==null){$sets[]="`$f`=?";$types.='s';$params[]=sanitize($val);}
    }
    // languages_spoken stored as JSON
    $langs_raw=$post['languages_spoken']??$_POST['languages_spoken']??null;
    if($langs_raw!==null){
        $langs=array_filter(array_map('trim',explode(',',$langs_raw)));
        $sets[]='`languages_spoken`=?';$types.='s';$params[]=json_encode(array_values($langs));
    }
    if(empty($sets)) fail('No data');
    // Check license expiry → notify if within 60 days
    $lic_exp=$post['license_expiry']??$_POST['license_expiry']??null;
    if($lic_exp){
        $days_left=ceil((strtotime($lic_exp)-time())/86400);
        if($days_left>0&&$days_left<=60){
            $msg="⚠️ Your lab technician license expires in $days_left day".($days_left==1?'':'s').". Please renew it.";
            dbExecute($conn,"INSERT IGNORE INTO lab_notifications (recipient_id,message,type) VALUES(?,?,'System')","is",[$tech_pk,$msg]);
        }
    }
    $params[]=$tech_pk; $types.='i';
    dbExecute($conn,"UPDATE lab_technicians SET ".implode(',',$sets).",updated_at=NOW() WHERE id=?",$types,$params);
    updateProfileCompleteness($conn,$tech_pk);
    logLabActivity($conn,$tech_pk,'update_professional','profile',null);
    ok(['message'=>'Professional profile updated successfully']);

case 'update_availability':
    $status=esc($conn,$post['status']??$_POST['status']??'Available');
    $allowed_avail=['Available','Busy','On Break','Off Duty'];
    if(!in_array($status,$allowed_avail)) fail('Invalid status');
    dbExecute($conn,"UPDATE lab_technicians SET availability_status=? WHERE id=?","si",[$status,$tech_pk]);
    logLabActivity($conn,$tech_pk,'update_availability','profile',null);
    ok(['message'=>'Availability set to '.$status]);

case 'add_qualification':
    $deg=sanitize($post['degree']??'');$inst=sanitize($post['institution']??'');$year=(int)($post['year']??0);
    if(!$deg) fail('Degree required');
    $file_path='';
    if(isset($_FILES['certificate'])&&$_FILES['certificate']['error']===UPLOAD_ERR_OK){
        $v=validateUpload($_FILES['certificate'],['application/pdf','image/jpeg','image/png'],5242880);
        if(!$v['valid']) fail($v['error']);
        $ext=pathinfo($_FILES['certificate']['name'],PATHINFO_EXTENSION);$fname='lab_qual_'.$tech_pk.'_'.time().'.'.$ext;
        $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/lab_docs/';if(!is_dir($dir)) mkdir($dir,0755,true);
        move_uploaded_file($_FILES['certificate']['tmp_name'],$dir.$fname);$file_path='uploads/lab_docs/'.$fname;
    }
    dbExecute($conn,"INSERT INTO lab_technician_qualifications (technician_id,degree_name,institution,year_awarded,certificate_file) VALUES(?,?,?,?,?)","issss",[$tech_pk,$deg,$inst,$year,$file_path]);
    logLabActivity($conn,$tech_pk,'add_qualification','profile',null);
    ok();

case 'delete_qualification':
    $id=(int)($post['id']??0);
    dbExecute($conn,"DELETE FROM lab_technician_qualifications WHERE id=? AND technician_id=?","ii",[$id,$tech_pk]);
    logLabActivity($conn,$tech_pk,'delete_qualification','profile',$id);
    ok();

case 'add_certification':
    $name=sanitize($post['name']??'');if(!$name) fail('Name required');
    $body=sanitize($post['body']??'');$issue=esc($conn,$post['issue_date']??'');$expiry=esc($conn,$post['expiry_date']??'');
    $file_path='';
    if(isset($_FILES['certificate'])&&$_FILES['certificate']['error']===UPLOAD_ERR_OK){
        $v=validateUpload($_FILES['certificate'],['application/pdf','image/jpeg','image/png'],5242880);
        if(!$v['valid']) fail($v['error']);
        $ext=pathinfo($_FILES['certificate']['name'],PATHINFO_EXTENSION);$fname='lab_cert_'.$tech_pk.'_'.time().'.'.$ext;
        $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/lab_docs/';if(!is_dir($dir)) mkdir($dir,0755,true);
        move_uploaded_file($_FILES['certificate']['tmp_name'],$dir.$fname);$file_path='uploads/lab_docs/'.$fname;
    }
    dbExecute($conn,"INSERT INTO lab_technician_certifications (technician_id,certification_name,issuing_body,issue_date,expiry_date,certificate_file) VALUES(?,?,?,?,?,?)","isssss",[$tech_pk,$name,$body,$issue?:null,$expiry?:null,$file_path]);
    logLabActivity($conn,$tech_pk,'add_certification','profile',null);
    ok();

case 'delete_certification':
    $id=(int)($post['id']??0);
    dbExecute($conn,"DELETE FROM lab_technician_certifications WHERE id=? AND technician_id=?","ii",[$id,$tech_pk]);
    logLabActivity($conn,$tech_pk,'delete_certification','profile',$id);
    ok();

case 'upload_document':
    if(!isset($_FILES['file'])||$_FILES['file']['error']!==UPLOAD_ERR_OK) fail('No file');
    $v=validateUpload($_FILES['file'],['application/pdf','image/jpeg','image/png','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'],10485760);
    if(!$v['valid']) fail($v['error']);
    $name=sanitize($post['name']??$_FILES['file']['name']);$type=esc($conn,$post['type']??'Other');$desc=sanitize($post['description']??'');
    $ext=pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);$fname='lab_doc_'.$tech_pk.'_'.time().'.'.$ext;
    $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/lab_docs/';if(!is_dir($dir)) mkdir($dir,0755,true);
    move_uploaded_file($_FILES['file']['tmp_name'],$dir.$fname);
    dbExecute($conn,"INSERT INTO lab_technician_documents (technician_id,document_name,file_path,document_type,file_size,description) VALUES(?,?,?,?,?,?)","isssss",[$tech_pk,$name,'uploads/lab_docs/'.$fname,$type,(int)$_FILES['file']['size'],$desc]);
    logLabActivity($conn,$tech_pk,'upload_document','profile',null);
    ok();

case 'delete_document':
    $id=(int)($post['id']??$_POST['id']??0);
    $doc_row=dbRow($conn,"SELECT file_path FROM lab_technician_documents WHERE id=? AND technician_id=?","ii",[$id,$tech_pk]);
    if($doc_row&&$doc_row['file_path']){
        $abs=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/'.$doc_row['file_path'];
        if(file_exists($abs)) @unlink($abs);
    }
    dbExecute($conn,"DELETE FROM lab_technician_documents WHERE id=? AND technician_id=?","ii",[$id,$tech_pk]);
    updateProfileCompleteness($conn,$tech_pk);
    logLabActivity($conn,$tech_pk,'delete_document','profile',$id);
    ok(['message'=>'Document deleted']);

// ═══════════════════════════════════════════
// SETTINGS
// ═══════════════════════════════════════════
case 'update_setting':
    $key=sanitize($post['key']??''); $value=sanitize($post['value']??'');
    if(!$key) fail('Invalid');
    // Whitelist of allowed setting keys
    $allowed=['theme_preference','alert_sound','auto_refresh_interval','notify_new_orders','notify_critical_results','notify_equipment_alerts','notify_low_reagents','notify_qc_failures','notify_messages','notify_result_amendments'];
    // Map to actual column names
    $col_map=['theme_preference'=>'theme_preference','alert_sound'=>'alert_sound_enabled','auto_refresh_interval'=>'auto_refresh_interval','notify_new_orders'=>'notif_new_order','notify_critical_results'=>'notif_critical_result','notify_equipment_alerts'=>'notif_equipment_alert','notify_low_reagents'=>'notif_reagent_alert','notify_qc_failures'=>'notif_qc_reminder','notify_messages'=>'notif_doctor_msg','notify_result_amendments'=>'notif_system'];
    if(!in_array($key,$allowed)) fail('Invalid setting key');
    $col=$col_map[$key];
    // Use prepared statement with whitelisted column name (safe from injection)
    dbExecute($conn,"UPDATE lab_technician_settings SET `$col`=? WHERE technician_id=?","si",[$value,$tech_pk]);
    ok();

case 'save_settings':
    $sets=[]; $types=''; $params=[];
    if(isset($post['theme'])){$sets[]='theme_preference=?';$types.='s';$params[]=sanitize($post['theme']);}
    if(isset($post['alert_sound'])){$sets[]='alert_sound_enabled=?';$types.='i';$params[]=(int)$post['alert_sound'];}
    if(!empty($sets)){$params[]=$tech_pk;$types.='i';dbExecute($conn,"UPDATE lab_technician_settings SET ".implode(',',$sets)." WHERE technician_id=?",$types,$params);}
    ok();

case 'save_notification_toggles':
    $toggles=['notif_new_order','notif_critical_result','notif_equipment_alert','notif_reagent_alert','notif_qc_reminder','notif_doctor_msg','notif_system'];
    $sets=[];$types='';$params=[];
    foreach($toggles as $t){if(isset($post[$t])){$sets[]="`$t`=?";$types.='i';$params[]=(int)$post[$t];}}
    if(!empty($sets)){$params[]=$tech_pk;$types.='i';dbExecute($conn,"UPDATE lab_technician_settings SET ".implode(',',$sets)." WHERE technician_id=?",$types,$params);}
    ok();

case 'update_shift_notes':
    $notes=trim($post['notes']??$_POST['notes']??'');
    dbExecute($conn,"UPDATE lab_technicians SET shift_preference_notes=?,updated_at=NOW() WHERE id=?","si",[$notes,$tech_pk]);
    updateProfileCompleteness($conn,$tech_pk);
    ok(['message'=>'Shift preferences saved']);

case 'save_qualification':
    $deg=sanitize($post['degree_name']??$_POST['degree_name']??'');
    $inst=sanitize($post['institution']??$_POST['institution']??'');
    $year=(int)($post['year_awarded']??$_POST['year_awarded']??0);
    if(!$deg||!$inst) fail('Degree name and institution are required');
    $file_path='';
    if(isset($_FILES['file'])&&$_FILES['file']['error']===UPLOAD_ERR_OK){
        $v=validateUpload($_FILES['file'],['application/pdf','image/jpeg','image/png'],5242880);
        if(!$v['valid']) fail($v['error']);
        $ext=pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);
        $fname='lab_qual_'.$tech_pk.'_'.time().'.'.$ext;
        $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/lab_docs/';
        if(!is_dir($dir)) mkdir($dir,0755,true);
        move_uploaded_file($_FILES['file']['tmp_name'],$dir.$fname);
        $file_path='uploads/lab_docs/'.$fname;
    }
    dbExecute($conn,"INSERT INTO lab_technician_qualifications (technician_id,degree_name,institution,year_awarded,certificate_file) VALUES(?,?,?,?,?)",
        "issss",[$tech_pk,$deg,$inst,$year?:null,$file_path]);
    updateProfileCompleteness($conn,$tech_pk);
    logLabActivity($conn,$tech_pk,'add_qualification','profile',null);
    ok(['message'=>'Qualification added successfully']);

case 'save_certification':
    $cert_name=sanitize($post['certification_name']??$_POST['certification_name']??'');
    if(!$cert_name) fail('Certification name is required');
    $body=sanitize($post['issuing_body']??$_POST['issuing_body']??'');
    $issue=esc($conn,$post['issue_date']??$_POST['issue_date']??'');
    $exp=esc($conn,$post['expiry_date']??$_POST['expiry_date']??'');
    $file_path='';
    if(isset($_FILES['file'])&&$_FILES['file']['error']===UPLOAD_ERR_OK){
        $v=validateUpload($_FILES['file'],['application/pdf','image/jpeg','image/png'],5242880);
        if(!$v['valid']) fail($v['error']);
        $ext=pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);
        $fname='lab_cert_'.$tech_pk.'_'.time().'.'.$ext;
        $dir=$_SERVER['DOCUMENT_ROOT'].'/RMU-Medical-Management-System/uploads/lab_docs/';
        if(!is_dir($dir)) mkdir($dir,0755,true);
        move_uploaded_file($_FILES['file']['tmp_name'],$dir.$fname);
        $file_path='uploads/lab_docs/'.$fname;
    }
    dbExecute($conn,"INSERT INTO lab_technician_certifications (technician_id,certification_name,issuing_body,issue_date,expiry_date,certificate_file) VALUES(?,?,?,?,?,?)",
        "isssss",[$tech_pk,$cert_name,$body,$issue?:null,$exp?:null,$file_path]);
    if($exp){
        $dl=ceil((strtotime($exp)-time())/86400);
        if($dl>0&&$dl<=60){
            $msg="⚠️ Your certification '$cert_name' expires in $dl days.";
            dbExecute($conn,"INSERT IGNORE INTO lab_notifications (recipient_id,message,type) VALUES(?,?,'System')","is",[$tech_pk,$msg]);
        }
    }
    updateProfileCompleteness($conn,$tech_pk);
    logLabActivity($conn,$tech_pk,'add_certification','profile',null);
    ok(['message'=>'Certification added successfully']);

case 'logout_session':
    $sid=(int)($post['session_id']??$_POST['session_id']??0);
    if(!$sid) fail('Invalid session ID');
    dbExecute($conn,"DELETE FROM lab_technician_sessions WHERE id=? AND technician_id=? AND is_current=0","ii",[$sid,$tech_pk]);
    ok(['message'=>'Session terminated']);

case 'logout_all_sessions':
    dbExecute($conn,"DELETE FROM lab_technician_sessions WHERE technician_id=? AND is_current=0","i",[$tech_pk]);
    logLabActivity($conn,$tech_pk,'logout_all_sessions','security',null);
    ok(['message'=>'All other sessions have been logged out']);

case 'change_password':
    $old=($post['current_password']??$post['old_password']??'');$new=($post['new_password']??'');
    if(!$old||!$new) fail('All fields required');
    $strength=enforcePasswordStrength($new);
    if($strength!==true) fail($strength);
    $user=dbRow($conn,"SELECT password FROM users WHERE id=?","i",[$user_id]);
    if(!$user||!password_verify($old,$user['password'])) fail('Current password incorrect');
    $hash=password_hash($new,PASSWORD_BCRYPT);
    dbExecute($conn,"UPDATE users SET password=? WHERE id=?","si",[$hash,$user_id]);
    logLabActivity($conn,$tech_pk,'change_password','security',null);
    ok(['message'=>'Password changed successfully']);

// ═══════════════════════════════════════════
// REPORT GENERATION
// ═══════════════════════════════════════════
case 'generate_report':
    $type=esc($conn,$post['report_type']??'');$format=esc($conn,$post['format']??'pdf');
    $date_from=esc($conn,$post['date_from']??date('Y-m-01'));$date_to=esc($conn,$post['date_to']??date('Y-m-d'));
    $params_json=json_encode(array_diff_key($post,['action'=>1,'_csrf'=>1]));
    mysqli_query($conn,"INSERT INTO lab_reports (technician_id,report_type,report_format,date_from,date_to,parameters) VALUES($tech_pk,'$type','$format','$date_from','$date_to','".esc($conn,$params_json)."')");
    logLabActivity($conn,$tech_pk,'generate_report','reports',null);
    ok(['message'=>'Report generated successfully']);

case 'delete_report':
    $id=(int)($post['id']??0); if(!$id) fail('Invalid');
    dbExecute($conn,"DELETE FROM lab_reports WHERE id=? AND technician_id=?","ii",[$id,$tech_pk]);
    ok();

// ═══════════════════════════════════════════
// AUDIT TRAIL EXPORT
// ═══════════════════════════════════════════
case 'export_audit_trail':
    $format=esc($conn,$post['format']??'csv');
    logLabActivity($conn,$tech_pk,'export_audit','audit_trail',null);
    ok(['message'=>'Audit trail export initiated']);

// ═══════════════════════════════════════════
// DASHBOARD STATS REFRESH
// ═══════════════════════════════════════════
case 'get_stats':
    $today=date('Y-m-d');
    $stats=[
        'pending_orders'    => (int)dbVal($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE order_status='Pending'","",null),
        'awaiting_samples'  => (int)dbVal($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE order_status='Accepted'","",null),
        'processing'        => (int)dbVal($conn,"SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=? AND order_status='Processing'","i",[$tech_pk]),
        'awaiting_validation'=> (int)dbVal($conn,"SELECT COUNT(*) FROM lab_results_v2 WHERE technician_id=? AND result_status IN('Draft','Pending Validation')","i",[$tech_pk]),
        'critical_results'  => (int)dbVal($conn,"SELECT COUNT(*) FROM lab_results_v2 WHERE technician_id=? AND result_interpretation='Critical' AND DATE(created_at)=?","is",[$tech_pk,$today]),
        'equipment_alerts'  => (int)dbVal($conn,"SELECT COUNT(*) FROM lab_equipment WHERE next_calibration_date<=CURDATE() OR status IN('Calibration Due','Out of Service','Maintenance')","",null),
        'low_reagents'      => (int)dbVal($conn,"SELECT COUNT(*) FROM reagent_inventory WHERE status IN('Low Stock','Out of Stock','Expired')","",null),
        'unread_notifs'     => (int)dbVal($conn,"SELECT COUNT(*) FROM lab_notifications WHERE recipient_id=? AND is_read=0","i",[$tech_pk]),
    ];
    ok(['stats'=>$stats]);

default:
    fail('Unknown action: '.$action);
}

// ─── Helper: updateProfileCompleteness ───────────────────────────────────────
if(!function_exists('updateProfileCompleteness')){
function updateProfileCompleteness($conn,$tech_pk){
    $tr=dbRow($conn,"SELECT date_of_birth,phone,nationality,specialization,designation,license_number,profile_photo,shift_preference_notes,two_fa_enabled FROM lab_technicians WHERE id=?","i",[$tech_pk]);
    if(!$tr)return;
    $personal =(int)(!empty($tr['date_of_birth'])&&!empty($tr['phone'])&&!empty($tr['nationality']));
    $prof     =(int)(!empty($tr['specialization'])&&!empty($tr['designation'])&&!empty($tr['license_number']));
    $quals    =(int)(dbVal($conn,"SELECT COUNT(*) FROM lab_technician_qualifications WHERE technician_id=?","i",[$tech_pk])>0);
    $equip    =(int)(dbVal($conn,"SELECT COUNT(*) FROM lab_equipment WHERE assigned_technician_id=?","i",[$tech_pk])>0);
    $shift    =(int)(!empty($tr['shift_preference_notes']));
    $photo    =(int)(!empty($tr['profile_photo']));
    $secure   =(int)(!empty($tr['two_fa_enabled']));
    $docs     =(int)(dbVal($conn,"SELECT COUNT(*) FROM lab_technician_documents WHERE technician_id=?","i",[$tech_pk])>0);
    $pct      =round(($personal+$prof+$quals+$equip+$shift+$photo+$secure+$docs)/8*100);
    dbExecute($conn,"INSERT INTO lab_technician_profile_completeness (technician_id,personal_info,professional_profile,qualifications,equipment_assigned,shift_profile,photo_uploaded,security_setup,documents_uploaded,completeness_percentage) VALUES(?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE personal_info=VALUES(personal_info),professional_profile=VALUES(professional_profile),qualifications=VALUES(qualifications),equipment_assigned=VALUES(equipment_assigned),shift_profile=VALUES(shift_profile),photo_uploaded=VALUES(photo_uploaded),security_setup=VALUES(security_setup),documents_uploaded=VALUES(documents_uploaded),completeness_percentage=VALUES(completeness_percentage)","iiiiiiiiis",
        [$tech_pk,$personal,$prof,$quals,$equip,$shift,$photo,$secure,$docs,(string)$pct]);
}
}

if(!function_exists('handleFileUpload')){
function handleFileUpload($file,$subfolder,$allowed_exts,$max_mb){
    $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,$allowed_exts)) throw new Exception('Invalid file type: '.$ext);
    if($file['size']>$max_mb*1024*1024) throw new Exception('File too large. Max '.$max_mb.'MB');
    $upload_dir="../../uploads/lab_technician/$subfolder/";
    if(!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
    $fname=uniqid().'_'.time().'.'.$ext;
    if(!move_uploaded_file($file['tmp_name'],$upload_dir.$fname)) throw new Exception('Upload failed');
    return "uploads/lab_technician/$subfolder/$fname";
}
}

