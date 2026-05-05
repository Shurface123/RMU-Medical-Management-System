<?php
/**
 * actions_logistics.php — Logistics & Transportation Staff Actions
 */
if (!defined('AJAX_REQUEST')) exit;

switch ($action) {

    case 'submit_leave_request':
        $type       = sanitize($_POST['leave_type'] ?? '');
        $start_date = sanitize($_POST['start_date'] ?? '');
        $end_date   = sanitize($_POST['end_date'] ?? '');
        $reason     = sanitize($_POST['reason'] ?? '');
        if (!$type || !$start_date || !$end_date || !$reason) json_err('All leave request fields required.');
        if ($end_date < $start_date) json_err('End date cannot be before start date.');
        
        $start_ts = strtotime($start_date);
        $end_ts   = strtotime($end_date);
        $total_days = round(($end_ts - $start_ts) / 86400) + 1;

        $id = dbInsert($conn,
            "INSERT INTO staff_leaves (staff_id,leave_type,start_date,end_date,total_days,reason,status,created_at) VALUES (?,?,?,?,?,?,'pending',NOW())",
            "isssis",[$staff_id,$type,$start_date,$end_date,$total_days,$reason]
        );
        if ($id) {
            $name = dbVal($conn, "SELECT name FROM users WHERE id=? LIMIT 1", "i", [$user_id]);
            $adminId = dbVal($conn, "SELECT id FROM staff WHERE role='admin' LIMIT 1") ?? 0;
            if ($adminId) notifyStaff($conn, $adminId, 'leave_request', "New leave request from $name");
            json_ok('Leave request submitted successfully.', ['leave_id' => $id]);
        }
        json_err('Failed to submit leave request.');
        break;

    case 'accept_trip_request':
        if ($staffRole !== 'ambulance_driver') json_err('Access denied.',403);
        $req_id = (int)($_POST['request_id'] ?? 0);
        $req = dbRow($conn,"SELECT * FROM ambulance_requests WHERE id=? AND status='pending' LIMIT 1","i",[$req_id]);
        if (!$req) json_err('Request not found or already accepted.');
        dbExecute($conn,"UPDATE ambulance_requests SET status='accepted',driver_id=?,accepted_at=NOW() WHERE id=?","ii",[$staff_id,$req_id]);
        $trip_id = dbInsert($conn,"INSERT INTO ambulance_trips (request_id,driver_id,pickup_location,destination,patient_name,patient_condition,trip_status,created_at) VALUES (?,?,?,?,?,?,'accepted',NOW())","iissss",[$req_id,$staff_id,$req['pickup_location'],$req['destination'],$req['patient_name'],$req['condition_notes']]);
        logStaffActivity($conn,$staff_id,'accept_trip','ambulance',$req_id);
        json_ok('Trip request accepted.',['trip_id'=>$trip_id]);
        break;

    case 'reject_trip_request':
        if ($staffRole !== 'ambulance_driver') json_err('Access denied.',403);
        $req_id = (int)($_POST['request_id'] ?? 0);
        $reason = sanitize($_POST['reason'] ?? '');
        if (!$reason) json_err('Rejection reason is required.');
        dbExecute($conn,"UPDATE ambulance_requests SET status='rejected',rejection_reason=? WHERE id=?","si",[$reason,$req_id]);
        json_ok('Request rejected.');
        break;

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
        $id = dbInsert($conn,"INSERT INTO vehicle_fuel_logs (vehicle_id,logged_by_staff_id,fuel_litres,cost,odometer_reading,logged_at) VALUES (?,?,?,?,?,NOW())","iiddi",[$vehicle_id,$staff_id,$litres,$cost,$mileage]);
        if ($id) json_ok('Fuel log recorded.');
        json_err('Failed to log fuel.');

    case 'report_vehicle_issue':
        if ($staffRole !== 'ambulance_driver') json_err('Access denied.',403);
        $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
        $desc = sanitize($_POST['description'] ?? '');
        if (!$desc) json_err('Issue description required.');
        $photo = handleUpload('photo','vehicle_issues',['jpg','jpeg','png'],5);
        if (is_array($photo)) json_err($photo['error']);
        $id = dbInsert($conn,"INSERT INTO vehicle_maintenance_requests (vehicle_id,reported_by_staff_id,description,photo_path,status,reported_at) VALUES (?,?,?,?,'open',NOW())","iiss",[$vehicle_id,$staff_id,$desc,$photo]);
        dbInsert($conn,"INSERT INTO maintenance_requests (location,issue_category,issue_description,priority,reported_by_role,reported_at,status) VALUES ('Vehicle','equipment',?,?,'ambulance_driver',NOW(),'open')","ss",["Vehicle Issue (ID#$vehicle_id): $desc",'high']);
        json_ok('Vehicle issue reported and maintenance team notified.');
}
