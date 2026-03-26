<?php
/**
 * staff_actions.php — RMU Medical Sickbay
 * Central AJAX handler for the complete Staff Dashboard.
 * All 15 modules handled here. POST only.
 */
define('AJAX_REQUEST', true);
require_once 'staff_security.php';

// ── Export requests arrive via GET (browser download link) ──
$is_export = (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['action']) &&
    $_GET['action'] === 'export_report'
);

if ($is_export) {
    // Export mode — read params from GET, skip CSRF (download link)
    $action    = 'export_report';
    $user_id   = (int)$_SESSION['user_id'];
    $staffRole = $_SESSION['user_role'] ?? 'staff';
    // skip JSON header — will be overridden by download headers
} else {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Method Not Allowed', 405);

    $action   = sanitize($_POST['action'] ?? '');
    $user_id  = (int)$_SESSION['user_id'];
    $staffRole = $_SESSION['user_role'] ?? 'staff';

    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($token)) {
        json_err('Invalid Security Token (CSRF). Refresh page and try again.', 403);
    }
} // end else (non-export POST block)

// Get staff record (required for most actions)
$staff = dbRow($conn, "SELECT s.*, r.role_display_name, r.icon_class FROM staff s LEFT JOIN staff_roles r ON s.role=r.role_slug WHERE s.user_id=? LIMIT 1", "i", [$user_id]);
$staff_id = $staff ? (int)$staff['id'] : 0;

if (!$staff_id && !in_array($action, ['create_staff_profile', 'health_check'])) {
    json_err('Staff profile not found. Contact admin.', 403);
}

switch ($action) {

// ════════════════════════════════════════════════════════════
// MODULE: PROFILE — Personal Info
// ════════════════════════════════════════════════════════════
case 'update_personal_info':
    $fields = ['full_name','date_of_birth','gender','nationality','marital_status','phone','secondary_phone','email','national_id','address'];
    $data = [];
    foreach ($fields as $f) $data[$f] = sanitize($_POST[$f] ?? '');
    if (!$data['full_name'] || !$data['email']) json_err('Name and email are required.');
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) json_err('Invalid email address.');

    $ok = dbExecute($conn,
        "UPDATE staff SET full_name=?,date_of_birth=?,gender=?,nationality=?,marital_status=?,phone=?,secondary_phone=?,email=?,national_id=?,address=?,updated_at=NOW() WHERE id=?",
        "ssssssssssi",
        [$data['full_name'],$data['date_of_birth'],$data['gender'],$data['nationality'],$data['marital_status'],
         $data['phone'],$data['secondary_phone'],$data['email'],$data['national_id'],$data['address'],$staff_id]
    );
    dbExecute($conn,"UPDATE users SET name=?,email=?,phone=? WHERE id=?","sssi",[$data['full_name'],$data['email'],$data['phone'],$user_id]);
    logStaffActivity($conn,$staff_id,'update_personal_info','profile');
    if ($ok !== false) json_ok('Personal information updated successfully.');
    json_err('Database error. Please try again.');

// ════════════════════════════════════════════════════════════
// MODULE: PROFILE — Photo Upload
// ════════════════════════════════════════════════════════════
case 'upload_photo':
    $path = handleUpload('photo','photos',['jpg','jpeg','png','webp'],2);
    if (is_array($path)) json_err($path['error']);
    if (!$path) json_err('No file uploaded or invalid type.');
    dbExecute($conn,"UPDATE staff SET profile_photo=?,updated_at=NOW() WHERE id=?","si",[$path,$staff_id]);
    dbExecute($conn,"UPDATE users SET profile_image=? WHERE id=?","si",[$path,$user_id]);
    logStaffActivity($conn,$staff_id,'upload_photo','profile');
    json_ok('Profile photo updated.', ['photo_url'=>"/RMU-Medical-Management-System/$path"]);

// ════════════════════════════════════════════════════════════
// MODULE: PROFILE — Qualifications
// ════════════════════════════════════════════════════════════
case 'save_qualification':
    $cert_name = sanitize($_POST['certificate_name'] ?? '');
    $institution = sanitize($_POST['institution'] ?? '');
    $year = (int)($_POST['year_awarded'] ?? 0);
    if (!$cert_name || !$institution || !$year) json_err('All qualification fields required.');
    $file_path = handleUpload('document','qualifications',['pdf','jpg','jpeg','png'],5);
    if (is_array($file_path)) json_err($file_path['error']);
    $id = dbInsert($conn,
        "INSERT INTO staff_qualifications (staff_id,certificate_name,institution,year_awarded,file_path,created_at) VALUES (?,?,?,?,?,NOW())",
        "ississ",[$staff_id,$cert_name,$institution,$year,$file_path]
    );
    if ($id) { logStaffActivity($conn,$staff_id,'add_qualification','profile',$id); json_ok('Qualification added successfully.'); }
    json_err('Failed to save qualification.');

case 'delete_qualification':
    $qid = (int)($_POST['qual_id'] ?? 0);
    // Verify ownership
    $q = dbRow($conn,"SELECT file_path FROM staff_qualifications WHERE id=? AND staff_id=?","ii",[$qid,$staff_id]);
    if (!$q) json_err('Qualification not found.',403);
    dbExecute($conn,"DELETE FROM staff_qualifications WHERE id=? AND staff_id=?","ii",[$qid,$staff_id]);
    if ($q['file_path'] && file_exists(__DIR__."/../../".$q['file_path'])) @unlink(__DIR__."/../../".$q['file_path']);
    json_ok('Qualification deleted.');

// ════════════════════════════════════════════════════════════
// MODULE: PROFILE — Documents
// ════════════════════════════════════════════════════════════
case 'upload_document':
    $doc_name = sanitize($_POST['doc_name'] ?? '');
    $doc_type = sanitize($_POST['doc_type'] ?? 'other');
    if (!$doc_name) json_err('Document name required.');
    $file_path = handleUpload('document','documents',['pdf','jpg','jpeg','png'],10);
    if (is_array($file_path)) json_err($file_path['error']);
    if (!$file_path) json_err('File upload failed.');
    $id = dbInsert($conn,
        "INSERT INTO staff_documents (staff_id,document_name,document_type,file_path,uploaded_at) VALUES (?,?,?,?,NOW())",
        "isss",[$staff_id,$doc_name,$doc_type,$file_path]
    );
    if ($id) json_ok('Document uploaded successfully.');
    json_err('Upload failed.');

case 'delete_document':
    $did = (int)($_POST['doc_id'] ?? 0);
    $d = dbRow($conn,"SELECT file_path FROM staff_documents WHERE id=? AND staff_id=?","ii",[$did,$staff_id]);
    if (!$d) json_err('Document not found.',403);
    dbExecute($conn,"DELETE FROM staff_documents WHERE id=? AND staff_id=?","ii",[$did,$staff_id]);
    if ($d['file_path'] && file_exists(__DIR__."/../../".$d['file_path'])) @unlink(__DIR__."/../../".$d['file_path']);
    json_ok('Document deleted.');

// ════════════════════════════════════════════════════════════
// MODULE: SETTINGS — Password
// ════════════════════════════════════════════════════════════
case 'update_password':
    $cur_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    if (strlen($new_pass) < 8) json_err('Password must be at least 8 characters.');
    if ($new_pass !== $confirm) json_err('New passwords do not match.');
    $user = dbRow($conn,"SELECT password FROM users WHERE id=? LIMIT 1","i",[$user_id]);
    if (!$user || !password_verify($cur_pass, $user['password'])) json_err('Current password is incorrect.');
    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    dbExecute($conn,"UPDATE users SET password=? WHERE id=?","si",[$hash,$user_id]);
    logStaffActivity($conn,$staff_id,'password_change','settings');
    json_ok('Password updated successfully.');

// ════════════════════════════════════════════════════════════
// MODULE: SETTINGS — Theme / Preferences
// ════════════════════════════════════════════════════════════
case 'save_settings':
    $theme = sanitize($_POST['theme'] ?? 'light');
    $notif_sound = (int)($_POST['notif_sound'] ?? 1);
    $lang = sanitize($_POST['language'] ?? 'en');
    // Upsert into staff_settings
    $existing = dbRow($conn,"SELECT settings_id FROM staff_settings WHERE staff_id=? LIMIT 1","i",[$staff_id]);
    if ($existing) {
        dbExecute($conn,"UPDATE staff_settings SET theme=?,notif_sound=?,language=?,updated_at=NOW() WHERE staff_id=?","sisi",[$theme,$notif_sound,$lang,$staff_id]);
    } else {
        dbInsert($conn,"INSERT INTO staff_settings (staff_id,theme,notif_sound,language,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())","isis",[$staff_id,$theme,$notif_sound,$lang]);
    }
    json_ok('Settings saved.');

case 'toggle_2fa':
    $enable = (int)($_POST['enable'] ?? 0);
    dbExecute($conn, "UPDATE users SET two_fa_enabled=?, updated_at=NOW() WHERE id=?", "ii", [$enable, $user_id]);
    
    require_once '../classes/AuditLogger.php';
    $audit = new AuditLogger($conn);
    $audit->log2FAChange($user_id, $enable);
    
    json_ok('2FA ' . ($enable ? 'enabled' : 'disabled'));

// ════════════════════════════════════════════════════════════
// MODULE: SETTINGS — Active Sessions
// ════════════════════════════════════════════════════════════
case 'logout_session':
    $sid = (int)($_POST['session_id'] ?? 0);
    dbExecute($conn,"DELETE FROM staff_sessions WHERE session_id=? AND staff_id=?","ii",[$sid,$staff_id]);
    logStaffActivity($conn,$staff_id,'terminate_session','settings',$sid);
    json_ok('Session terminated.');

case 'logout_all_sessions':
    dbExecute($conn,"DELETE FROM staff_sessions WHERE staff_id=? AND is_current=0","i",[$staff_id]);
    logStaffActivity($conn,$staff_id,'terminate_all_sessions','settings');
    json_ok('All other sessions terminated.');

// ════════════════════════════════════════════════════════════
// MODULE: TASKS
// ════════════════════════════════════════════════════════════
case 'update_task_status':
    $task_id = (int)($_POST['task_id'] ?? 0);
    $status  = sanitize($_POST['status'] ?? '');
    $notes   = sanitize($_POST['notes'] ?? '');
    $valid_statuses = ['pending','in progress','completed','cancelled'];
    if (!in_array($status,$valid_statuses)) json_err('Invalid status.');
    // Verify ownership
    $t = dbRow($conn,"SELECT task_id FROM staff_tasks WHERE task_id=? AND assigned_to=?","ii",[$task_id,$staff_id]);
    if (!$t) json_err('Task not found.',403);
    $extra_sql = ($status === 'completed') ? ",completed_at=NOW()" : "";
    dbExecute($conn,"UPDATE staff_tasks SET status=?,completion_notes=?,updated_at=NOW()$extra_sql WHERE task_id=?","ssi",[$status,$notes,$task_id]);

    // Upload proof photo if attached
    if ($status === 'completed' && isset($_FILES['proof'])) {
        $photo = handleUpload('proof','task_proofs',['jpg','jpeg','png'],5);
        if ($photo && !is_array($photo)) {
            dbExecute($conn,"UPDATE staff_tasks SET completion_photo=? WHERE task_id=?","si",[$photo,$task_id]);
        }
    }
    logStaffActivity($conn,$staff_id,'update_task_status','tasks',$task_id,null,['status'=>$status]);
    json_ok('Task updated successfully.');

case 'complete_task_checklist':
    $chk_id = (int)($_POST['checklist_id'] ?? 0);
    $task_id= (int)($_POST['task_id'] ?? 0);
    $state  = (int)($_POST['state'] ?? 0);
    dbExecute($conn,"UPDATE staff_task_checklists SET is_completed=?,completed_by=?,completed_at=".($state?'NOW()':'NULL')." WHERE checklist_id=? AND task_id=?","iiii",[$state,$staff_id,$chk_id,$task_id]);
    json_ok('Checklist item updated.');

// ════════════════════════════════════════════════════════════
// MODULE: NOTIFICATIONS
// ════════════════════════════════════════════════════════════
case 'mark_notification_read':
    $nid = (int)($_POST['notif_id'] ?? 0);
    if ($nid) dbExecute($conn,"UPDATE staff_notifications SET is_read=1 WHERE id=? AND staff_id=?","ii",[$nid,$staff_id]);
    else dbExecute($conn,"UPDATE staff_notifications SET is_read=1 WHERE staff_id=?","i",[$staff_id]);
    json_ok('Notifications marked as read.');

// ════════════════════════════════════════════════════════════
// MODULE: INTERNAL MESSAGES
// ════════════════════════════════════════════════════════════
case 'send_message':
    $receiver = (int)($_POST['receiver_id'] ?? 0);
    $subject  = sanitize($_POST['subject'] ?? '');
    $body     = sanitize($_POST['body'] ?? '');
    $priority = sanitize($_POST['priority'] ?? 'normal');
    if (!$receiver || !$body) json_err('Recipient and message body required.');
    $id = dbInsert($conn,
        "INSERT INTO staff_messages (sender_id,receiver_id,subject,body,priority,is_read,sent_at) VALUES (?,?,?,?,?,0,NOW())",
        "iissss",[$staff_id,$receiver,$subject,$body,$priority]
    );
    if ($id) json_ok('Message sent successfully.', ['message_id'=>$id]);
    json_err('Failed to send message.');

case 'mark_message_read':
    $mid = (int)($_POST['message_id'] ?? 0);
    dbExecute($conn,"UPDATE staff_messages SET is_read=1 WHERE id=? AND receiver_id=?","ii",[$mid,$staff_id]);
    json_ok('Marked as read.');

// ════════════════════════════════════════════════════════════
// MODULE: SCHEDULE — Leave Requests
// ════════════════════════════════════════════════════════════
case 'submit_leave_request':
    $type       = sanitize($_POST['leave_type'] ?? '');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date   = sanitize($_POST['end_date'] ?? '');
    $reason     = sanitize($_POST['reason'] ?? '');
    if (!$type || !$start_date || !$end_date || !$reason) json_err('All leave request fields required.');
    if ($end_date < $start_date) json_err('End date cannot be before start date.');
    $id = dbInsert($conn,
        "INSERT INTO staff_leaves (staff_id,leave_type,start_date,end_date,reason,status,created_at) VALUES (?,?,?,?,?,'pending',NOW())",
        "issss",[$staff_id,$type,$start_date,$end_date,$reason]
    );
    if ($id) {
        // Notify admin (user_role='admin')
        $adminId = dbVal($conn,"SELECT id FROM staff WHERE role='admin' LIMIT 1") ?? 0;
        if ($adminId) notifyStaff($conn,$adminId,'leave_request',"New leave request from {$staff['full_name']} ($type: $start_date - $end_date)");
        json_ok('Leave request submitted successfully.',['leave_id'=>$id]);
    }
    json_err('Failed to submit leave request.');

// ════════════════════════════════════════════════════════════
// MODULE: AMBULANCE DRIVER
// ════════════════════════════════════════════════════════════
case 'accept_trip_request':
    if ($staffRole !== 'ambulance_driver') json_err('Access denied.',403);
    $req_id = (int)($_POST['request_id'] ?? 0);
    $req = dbRow($conn,"SELECT * FROM ambulance_requests WHERE id=? AND status='pending' LIMIT 1","i",[$req_id]);
    if (!$req) json_err('Request not found or already accepted.');
    dbExecute($conn,"UPDATE ambulance_requests SET status='accepted',driver_id=?,accepted_at=NOW() WHERE id=?","ii",[$staff_id,$req_id]);
    // Create trip record
    $trip_id = dbInsert($conn,"INSERT INTO ambulance_trips (request_id,driver_id,pickup_location,destination,patient_name,patient_condition,trip_status,created_at) VALUES (?,?,?,?,?,?,'accepted',NOW())","iissss",[$req_id,$staff_id,$req['pickup_location'],$req['destination'],$req['patient_name'],$req['condition_notes']]);
    logStaffActivity($conn,$staff_id,'accept_trip','ambulance',$req_id);
    json_ok('Trip request accepted.',['trip_id'=>$trip_id]);

case 'reject_trip_request':
    if ($staffRole !== 'ambulance_driver') json_err('Access denied.',403);
    $req_id = (int)($_POST['request_id'] ?? 0);
    $reason = sanitize($_POST['reason'] ?? '');
    if (!$reason) json_err('Rejection reason is required.');
    dbExecute($conn,"UPDATE ambulance_requests SET status='rejected',rejection_reason=? WHERE id=?","si",[$reason,$req_id]);
    json_ok('Request rejected.');

case 'update_trip_status':
    if ($staffRole !== 'ambulance_driver') json_err('Access denied.',403);
    $trip_id    = (int)($_POST['trip_id'] ?? 0);
    $new_status = sanitize($_POST['trip_status'] ?? '');
    $notes      = sanitize($_POST['notes'] ?? '');
    $valid = ['en route','patient onboard','arrived','completed'];
    if (!in_array($new_status,$valid)) json_err('Invalid trip status.');
    $trip = dbRow($conn,"SELECT * FROM ambulance_trips WHERE trip_id=? AND driver_id=?","ii",[$trip_id,$staff_id]);
    if (!$trip) json_err('Trip not found.',403);
    $extra = '';
    if ($new_status==='en route') $extra=",accepted_at=NOW()";
    if ($new_status==='arrived') $extra=",arrived_at=NOW()";
    if ($new_status==='completed') $extra=",completed_at=NOW()";
    dbExecute($conn,"UPDATE ambulance_trips SET trip_status=?,notes=?,updated_at=NOW()$extra WHERE trip_id=?","ssi",[$new_status,$notes,$trip_id]);
    logStaffActivity($conn,$staff_id,'update_trip_status','ambulance',$trip_id,null,['status'=>$new_status]);
    json_ok('Trip status updated.');

case 'log_fuel':
    if ($staffRole !== 'ambulance_driver') json_err('Access denied.',403);
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
    $litres     = (float)($_POST['litres'] ?? 0);
    $cost       = (float)($_POST['cost'] ?? 0);
    $mileage    = (float)($_POST['mileage'] ?? 0);
    $id = dbInsert($conn,"INSERT INTO vehicle_fuel_logs (vehicle_id,logged_by_staff_id,litres,cost,mileage,logged_at) VALUES (?,?,?,?,?,NOW())","iiddd",[$vehicle_id,$staff_id,$litres,$cost,$mileage]);
    if ($id) json_ok('Fuel log recorded.');
    json_err('Failed to log fuel.');

case 'report_vehicle_issue':
    if ($staffRole !== 'ambulance_driver') json_err('Access denied.',403);
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
    $desc = sanitize($_POST['description'] ?? '');
    if (!$desc) json_err('Issue description required.');
    $photo = handleUpload('photo','vehicle_issues',['jpg','jpeg','png'],5);
    if (is_array($photo)) json_err($photo['error']);
    $id = dbInsert($conn,"INSERT INTO vehicle_issues (vehicle_id,reported_by_staff_id,description,photo_path,status,reported_at) VALUES (?,?,?,?,'open',NOW())","iiss",[$vehicle_id,$staff_id,$desc,$photo]);
    // Create maintenance request
    dbInsert($conn,"INSERT INTO maintenance_requests (location,issue_category,issue_description,priority,reported_by_role,reported_at,status) VALUES ('Vehicle','equipment',?,?,'ambulance_driver',NOW(),'open')","ss",["Vehicle Issue (ID#$vehicle_id): $desc",'high']);
    json_ok('Vehicle issue reported and maintenance team notified.');

// ════════════════════════════════════════════════════════════
// MODULE: CLEANER
// ════════════════════════════════════════════════════════════
case 'start_cleaning':
    if ($staffRole !== 'cleaner') json_err('Access denied.',403);
    $sched_id = (int)($_POST['schedule_id'] ?? 0);
    dbExecute($conn,"UPDATE cleaning_schedules SET status='in progress',started_at=NOW() WHERE id=? AND assigned_to=?","ii",[$sched_id,$staff_id]);
    dbInsert($conn,"INSERT INTO cleaning_logs (staff_id,schedule_id,ward_room_area,cleaning_type,started_at,sanitation_status,created_at) SELECT ?,id,ward_room_area,cleaning_type,NOW(),'in progress',NOW() FROM cleaning_schedules WHERE id=?","ii",[$staff_id,$sched_id]);
    json_ok('Cleaning started.');

case 'complete_cleaning':
    if ($staffRole !== 'cleaner') json_err('Access denied.',403);
    $sched_id = (int)($_POST['schedule_id'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');
    $san_status = sanitize($_POST['sanitation_status'] ?? 'clean');
    $photo = handleUpload('proof','cleaning_proofs',['jpg','jpeg','png'],5);
    if (is_array($photo)) json_err($photo['error']);
    dbExecute($conn,"UPDATE cleaning_schedules SET status='completed',completed_at=NOW() WHERE id=? AND assigned_to=?","ii",[$sched_id,$staff_id]);
    dbExecute($conn,"UPDATE cleaning_logs SET completed_at=NOW(),sanitation_status=?,notes=?,photo_proof=? WHERE schedule_id=? AND staff_id=?","sssii",[$san_status,$notes,$photo,$sched_id,$staff_id]);
    logStaffActivity($conn,$staff_id,'complete_cleaning','cleaning',$sched_id);
    json_ok('Cleaning marked as complete.');

case 'report_contamination':
    if ($staffRole !== 'cleaner') json_err('Access denied.',403);
    $location  = sanitize($_POST['location'] ?? '');
    $type      = sanitize($_POST['contamination_type'] ?? '');
    $severity  = sanitize($_POST['severity'] ?? 'medium');
    $desc      = sanitize($_POST['description'] ?? '');
    if (!$location || !$desc) json_err('Location and description required.');
    $photo = handleUpload('photo','contamination',['jpg','jpeg','png'],5);
    if (is_array($photo)) json_err($photo['error']);
    $id = dbInsert($conn,"INSERT INTO contamination_reports (staff_id,location,contamination_type,severity,description,photo_path,status,reported_at) VALUES (?,?,?,?,?,?,'reported',NOW())","isssss",[$staff_id,$location,$type,$severity,$desc,$photo]);
    json_ok('Contamination reported. Admin and clinical staff notified.');

// ════════════════════════════════════════════════════════════
// MODULE: LAUNDRY STAFF
// ════════════════════════════════════════════════════════════
case 'register_laundry_batch':
    if ($staffRole !== 'laundry_staff') json_err('Access denied.',403);
    $item_type = sanitize($_POST['item_type'] ?? '');
    $count = (int)($_POST['count'] ?? 0);
    $weight = (float)($_POST['weight'] ?? 0);
    $ward = sanitize($_POST['origin_ward'] ?? '');
    $contaminated = (int)($_POST['contaminated'] ?? 0);
    $batch_code = 'LB-' . strtoupper(substr($ward,0,3)) . '-' . date('Ymd') . '-' . str_pad(rand(1,999),3,'0',STR_PAD_LEFT);
    $id = dbInsert($conn,"INSERT INTO laundry_batches (batch_code,staff_id,item_type,item_count,weight_kg,origin_ward,contamination_flag,status,collected_at,created_at) VALUES (?,?,?,?,?,?,?,'collected',NOW(),NOW())","isiidsi",[$batch_code,$staff_id,$item_type,$count,$weight,$ward,$contaminated]);
    if ($id) json_ok('Batch registered.', ['batch_code'=>$batch_code,'batch_id'=>$id]);
    json_err('Failed to register batch.');

case 'update_batch_status':
    if ($staffRole !== 'laundry_staff') json_err('Access denied.',403);
    $batch_id = (int)($_POST['batch_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    $valid = ['collected','washing','ironing','quality check','delivered'];
    if (!in_array($status,$valid)) json_err('Invalid batch status.');
    $extra='';
    if ($status==='delivered') $extra=",delivered_at=NOW()";
    dbExecute($conn,"UPDATE laundry_batches SET status=?,updated_at=NOW()$extra WHERE id=? AND staff_id=?","sii",[$status,$batch_id,$staff_id]);
    json_ok('Batch status updated.');

case 'report_laundry_damage':
    if ($staffRole !== 'laundry_staff') json_err('Access denied.',403);
    $batch_id = (int)($_POST['batch_id'] ?? 0);
    $item_type = sanitize($_POST['item_type'] ?? '');
    $qty = (int)($_POST['quantity'] ?? 0);
    $desc = sanitize($_POST['description'] ?? '');
    $photo = handleUpload('photo','laundry_damage',['jpg','jpeg','png'],5);
    if (is_array($photo)) json_err($photo['error']);
    dbInsert($conn,"INSERT INTO laundry_damage_reports (batch_id,staff_id,item_type,quantity,description,photo_path,reported_at) VALUES (?,?,?,?,?,?,NOW())","iisiss",[$batch_id,$staff_id,$item_type,$qty,$desc,$photo]);
    json_ok('Damage report submitted.');

// ════════════════════════════════════════════════════════════
// MODULE: MAINTENANCE
// ════════════════════════════════════════════════════════════
case 'accept_maintenance_request':
    if ($staffRole !== 'maintenance') json_err('Access denied.',403);
    $req_id = (int)($_POST['request_id'] ?? 0);
    dbExecute($conn,"UPDATE maintenance_requests SET status='assigned',assigned_to=?,assigned_at=NOW() WHERE id=? AND (assigned_to IS NULL OR assigned_to=0)","ii",[$staff_id,$req_id]);
    logStaffActivity($conn,$staff_id,'accept_maintenance','maintenance',$req_id);
    json_ok('Request accepted and assigned to you.');

case 'update_maintenance_status':
    if ($staffRole !== 'maintenance') json_err('Access denied.',403);
    $req_id = (int)($_POST['request_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    $notes  = sanitize($_POST['action_notes'] ?? '');
    $valid  = ['in progress','on hold','completed'];
    if (!in_array($status,$valid)) json_err('Invalid status.');
    $req = dbRow($conn,"SELECT id FROM maintenance_requests WHERE id=? AND assigned_to=?","ii",[$req_id,$staff_id]);
    if (!$req) json_err('Request not found.',403);
    $extra = ($status==='completed') ? ",completed_at=NOW()" : "";
    dbExecute($conn,"UPDATE maintenance_requests SET status=?,action_notes=?,updated_at=NOW()$extra WHERE id=?","ssi",[$status,$notes,$req_id]);

    // Upload before/after photos
    if (isset($_FILES['before_photo'])) {
        $bp = handleUpload('before_photo','maintenance_photos',['jpg','jpeg','png'],5);
        if ($bp && !is_array($bp)) dbExecute($conn,"UPDATE maintenance_requests SET before_photo=? WHERE id=?","si",[$bp,$req_id]);
    }
    if (isset($_FILES['after_photo'])) {
        $ap = handleUpload('after_photo','maintenance_photos',['jpg','jpeg','png'],5);
        if ($ap && !is_array($ap)) dbExecute($conn,"UPDATE maintenance_requests SET after_photo=? WHERE id=?","si",[$ap,$req_id]);
    }
    json_ok('Maintenance request updated.');

// ════════════════════════════════════════════════════════════
// MODULE: SECURITY
// ════════════════════════════════════════════════════════════
case 'log_patrol_checkin':
    if ($staffRole !== 'security') json_err('Access denied.',403);
    $checkpoint = sanitize($_POST['checkpoint'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    dbInsert($conn,"INSERT INTO security_logs (staff_id,log_type,checkpoint,notes,logged_at) VALUES (?,'patrol_checkin',?,?,NOW())","iss",[$staff_id,$checkpoint,$notes]);
    json_ok('Patrol check-in logged.');

case 'report_incident':
    if ($staffRole !== 'security') json_err('Access denied.',403);
    $type      = sanitize($_POST['incident_type'] ?? '');
    $location  = sanitize($_POST['location'] ?? '');
    $desc      = sanitize($_POST['description'] ?? '');
    $severity  = sanitize($_POST['severity'] ?? 'low');
    $persons   = sanitize($_POST['persons_involved'] ?? '');
    $actions   = sanitize($_POST['actions_taken'] ?? '');
    if (!$type || !$location || !$desc) json_err('Incident type, location, and description required.');
    $id = dbInsert($conn,"INSERT INTO security_incidents (staff_id,incident_type,location,description,severity,persons_involved,actions_taken,status,reported_at) VALUES (?,?,?,?,?,?,?,'reported',NOW())","issssss",[$staff_id,$type,$location,$desc,$severity,$persons,$actions]);
    json_ok('Incident reported.', ['incident_id'=>$id]);

case 'log_visitor':
    if ($staffRole !== 'security') json_err('Access denied.',403);
    $name    = sanitize($_POST['visitor_name'] ?? '');
    $id_num  = sanitize($_POST['id_number'] ?? '');
    $purpose = sanitize($_POST['purpose'] ?? '');
    $visiting= sanitize($_POST['person_visiting'] ?? '');
    $ward    = sanitize($_POST['ward'] ?? '');
    if (!$name || !$purpose) json_err('Visitor name and purpose required.');
    dbInsert($conn,"INSERT INTO visitor_logs (logged_by,visitor_name,id_number,purpose,person_visiting,ward,entry_time) VALUES (?,?,?,?,?,?,NOW())","isssss",[$staff_id,$name,$id_num,$purpose,$visiting,$ward]);
    json_ok('Visitor logged successfully.');

case 'log_visitor_exit':
    if ($staffRole !== 'security') json_err('Access denied.',403);
    $vid = (int)($_POST['visitor_log_id'] ?? 0);
    dbExecute($conn,"UPDATE visitor_logs SET exit_time=NOW() WHERE id=? AND logged_by=?","ii",[$vid,$staff_id]);
    json_ok('Visitor exit logged.');

// ════════════════════════════════════════════════════════════
// MODULE: KITCHEN
// ════════════════════════════════════════════════════════════
case 'update_kitchen_task_status':
    if ($staffRole !== 'kitchen_staff') json_err('Access denied.',403);
    $task_id = (int)($_POST['task_id'] ?? 0);
    $status  = sanitize($_POST['status'] ?? '');
    $valid   = ['in preparation','ready','delivered'];
    if (!in_array($status,$valid)) json_err('Invalid status.');
    $t = dbRow($conn,"SELECT id FROM kitchen_tasks WHERE id=? AND assigned_to=?","ii",[$task_id,$staff_id]);
    if (!$t) json_err('Task not found.',403);
    $extra = ($status==='delivered') ? ",delivered_at=NOW()" : "";
    dbExecute($conn,"UPDATE kitchen_tasks SET status=?,updated_at=NOW()$extra WHERE id=?","si",[$status,$task_id]);
    json_ok('Task status updated.');

case 'report_dietary_issue':
    if ($staffRole !== 'kitchen_staff') json_err('Access denied.',403);
    $patient_name = sanitize($_POST['patient_name'] ?? '');
    $ward = sanitize($_POST['ward'] ?? '');
    $issue = sanitize($_POST['issue'] ?? '');
    if (!$issue) json_err('Issue description required.');
    dbInsert($conn,"INSERT INTO kitchen_dietary_flags (staff_id,patient_name,ward,issue_description,flagged_at,status) VALUES (?,?,?,?,'flagged',NOW())","isss",[$staff_id,$patient_name,$ward,$issue]);
    json_ok('Dietary issue flagged. Admin and nursing notified.');

// ════════════════════════════════════════════════════════════
// MODULE: PROFILE COMPLETENESS
// ════════════════════════════════════════════════════════════
case 'compute_completeness':
    $score = 0;
    $max = 7;
    if ($staff && $staff['full_name']) $score++;
    if ($staff && $staff['date_of_birth'] && $staff['gender']) $score++;
    if ($staff && $staff['profile_photo']) $score++;
    if ($staff && $staff['phone'] && $staff['email']) $score++;
    $qual_count = (int)dbVal($conn,"SELECT COUNT(*) FROM staff_qualifications WHERE staff_id=?","i",[$staff_id]);
    if ($qual_count > 0) $score++;
    $doc_count = (int)dbVal($conn,"SELECT COUNT(*) FROM staff_documents WHERE staff_id=?","i",[$staff_id]);
    if ($doc_count > 0) $score++;
    $settings = dbRow($conn,"SELECT settings_id FROM staff_settings WHERE staff_id=? LIMIT 1","i",[$staff_id]);
    if ($settings) $score++;
    $pct = round(($score/$max)*100);
    // Upsert completeness
    $ex = dbRow($conn,"SELECT record_id FROM staff_profile_completeness WHERE staff_id=? LIMIT 1","i",[$staff_id]);
    if ($ex) dbExecute($conn,"UPDATE staff_profile_completeness SET overall_percentage=?,last_updated=NOW() WHERE staff_id=?","ii",[$pct,$staff_id]);
    else dbInsert($conn,"INSERT INTO staff_profile_completeness (staff_id,overall_percentage,last_updated) VALUES (?,?,NOW())","ii",[$staff_id,$pct]);
    json_ok('Completeness computed.',['percent'=>$pct,'score'=>$score,'max'=>$max]);


// ════════════════════════════════════════════════════════════
// MODULE: REPORTS — Export (GET request, file download)
// ════════════════════════════════════════════════════════════
case 'export_report':
    $report_key = sanitize($_GET['report_key'] ?? '');
    $from       = sanitize($_GET['from']       ?? date('Y-m-01'));
    $to         = sanitize($_GET['to']         ?? date('Y-m-d'));
    $fmt        = strtolower(sanitize($_GET['format'] ?? 'csv'));

    // Validate dates
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');
    if (!$staff_id) { header('Content-Type: application/json'); json_err('Staff profile not found.', 403); }

    // ── Report definitions ──────────────────────────────────
    $report_map = [
        'tasks_completed' => [
            'title'  => 'Tasks Completed',
            'sql'    => 'SELECT task_title AS "Task", priority AS "Priority", due_date AS "Due Date", completed_at AS "Completed At", completion_notes AS "Notes" FROM staff_tasks WHERE assigned_to=? AND status="completed" AND completed_at BETWEEN ? AND ? ORDER BY completed_at DESC',
            'types'  => 'iss',
            'params' => [$staff_id, $from, $to . ' 23:59:59'],
        ],
        'tasks_all' => [
            'title'  => 'All Tasks',
            'sql'    => 'SELECT task_title AS "Task", priority AS "Priority", status AS "Status", due_date AS "Due Date", created_at AS "Created" FROM staff_tasks WHERE assigned_to=? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC',
            'types'  => 'iss',
            'params' => [$staff_id, $from, $to],
        ],
        'shifts' => [
            'title'  => 'Shift Schedule',
            'sql'    => 'SELECT shift_date AS "Date", shift_type AS "Shift", start_time AS "Start", end_time AS "End", location_ward_assigned AS "Ward", status AS "Status" FROM staff_shifts WHERE staff_id=? AND shift_date BETWEEN ? AND ? ORDER BY shift_date DESC',
            'types'  => 'iss',
            'params' => [$staff_id, $from, $to],
        ],
        'leave_requests' => [
            'title'  => 'Leave Requests',
            'sql'    => 'SELECT leave_type AS "Type", start_date AS "From", end_date AS "To", total_days AS "Days", status AS "Status", reason AS "Reason" FROM staff_leaves WHERE staff_id=? AND start_date BETWEEN ? AND ? ORDER BY start_date DESC',
            'types'  => 'iss',
            'params' => [$staff_id, $from, $to],
        ],
        'repairs_completed' => [
            'title'  => 'Repairs Completed',
            'sql'    => 'SELECT equipment_or_area AS "Issue", location AS "Location", priority AS "Priority", status AS "Status", reported_at AS "Reported", created_at AS "Updated" FROM maintenance_requests WHERE assigned_to=? AND status="completed" AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC',
            'types'  => 'iss',
            'params' => [$staff_id, $from, $to],
        ],
        'cleaning_logs' => [
            'title'  => 'Cleaning Logs',
            'sql'    => 'SELECT c.cleaning_type AS "Type", c.sanitation_status AS "Status", c.started_at AS "Started", c.completed_at AS "Completed", c.notes AS "Notes" FROM cleaning_logs c WHERE c.staff_id=? AND DATE(c.started_at) BETWEEN ? AND ? ORDER BY c.started_at DESC',
            'types'  => 'iss',
            'params' => [$staff_id, $from, $to],
        ],
        'laundry_batches' => [
            'title'  => 'Laundry Batches',
            'sql'    => 'SELECT batch_code AS "Batch", batch_type AS "Type", item_count AS "Items", delivery_status AS "Status", collected_at AS "Collected", delivered_at AS "Delivered" FROM laundry_batches WHERE assigned_to=? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC',
            'types'  => 'iss',
            'params' => [$staff_id, $from, $to],
        ],
        'incidents' => [
            'title'  => 'Security Incidents',
            'sql'    => 'SELECT incident_type AS "Type", location AS "Location", severity AS "Severity", status AS "Status", reported_at AS "Reported" FROM security_incidents WHERE staff_id=? AND DATE(reported_at) BETWEEN ? AND ? ORDER BY reported_at DESC',
            'types'  => 'iss',
            'params' => [$staff_id, $from, $to],
        ],
        'meal_deliveries' => [
            'title'  => 'Meal Deliveries',
            'sql'    => 'SELECT meal_type AS "Meal", ward_department AS "Ward", quantity AS "Qty", preparation_status AS "Prep Status", delivery_status AS "Delivery", scheduled_time AS "Scheduled" FROM kitchen_tasks WHERE assigned_to=? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC',
            'types'  => 'iss',
            'params' => [$staff_id, $from, $to],
        ],
    ];

    if (!isset($report_map[$report_key])) {
        header('Content-Type: application/json');
        json_err('Unknown report key: ' . $report_key, 400);
    }

    $rpt    = $report_map[$report_key];
    $rows   = dbSelect($conn, $rpt['sql'], $rpt['types'], $rpt['params']);
    $title  = $rpt['title'];
    $fname  = 'RMU_' . str_replace(' ', '_', $title) . '_' . $from . '_to_' . $to;

    if ($fmt === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fname . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        $out = fopen('php://output', 'w');
        // BOM for Excel UTF-8
        fputs($out, "ï»¿");
        // Report header rows
        fputcsv($out, ['RMU Medical Management System']);
        fputcsv($out, ['Report: ' . $title]);
        fputcsv($out, ['Period: ' . $from . ' to ' . $to]);
        fputcsv($out, ['Generated: ' . date('d M Y H:i')]);
        fputcsv($out, []);
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) fputcsv($out, array_values($row));
        } else {
            fputcsv($out, ['No data found for the selected period.']);
        }
        fclose($out);
        exit;
    }

    // Fallback for unsupported formats
    header('Content-Type: application/json');
    json_err('Unsupported format: ' . $fmt . '. Use csv.', 400);

// ════════════════════════════════════════════════════════════
// DEFAULT
// ════════════════════════════════════════════════════════════
default:
    json_err("Invalid action: $action");
}