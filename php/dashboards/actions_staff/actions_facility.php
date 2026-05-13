<?php
/**
 * actions_facility.php — Facility, Security & Maintenance Staff Actions
 */
if (!defined('AJAX_REQUEST')) exit;

switch ($action) {

    case 'accept_maintenance_request':
        if ($staffRole !== 'maintenance') json_err('Access denied.', 403);
        $req_id = (int)($_POST['request_id'] ?? 0);
        $updated = dbExecute($conn, "UPDATE maintenance_requests SET status='assigned', assigned_to=?, assigned_at=NOW() WHERE request_id=? AND (assigned_to IS NULL OR assigned_to=0)", "ii", [$staff_id, $req_id]);
        if ($updated === 0) json_err('Request not found or already assigned.');
        logStaffActivity($conn, $staff_id, 'accept_maintenance', 'maintenance', $req_id);
        json_ok('Request accepted and assigned to you.');
        break;

    case 'update_maintenance_status':
        if ($staffRole !== 'maintenance') json_err('Access denied.', 403);
        $req_id = (int)($_POST['request_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $notes  = sanitize($_POST['action_notes'] ?? $_POST['completion_notes'] ?? '');
        $valid  = ['in progress', 'on hold', 'completed'];
        if (!in_array($status, $valid)) json_err('Invalid status.');
        $req = dbRow($conn, "SELECT request_id, status FROM maintenance_requests WHERE request_id=? AND assigned_to=?", "ii", [$req_id, $staff_id]);
        if (!$req) json_err('Request not found or not assigned to you.', 403);
        $extra = "";
        if ($status === 'in progress') $extra = ", started_at=NOW()";
        if ($status === 'completed')   $extra = ", completed_at=NOW()";
        $updated = dbExecute($conn, "UPDATE maintenance_requests SET status=?, completion_notes=?, updated_at=NOW() $extra WHERE request_id=? AND assigned_to=?", "ssii", [$status, $notes, $req_id, $staff_id]);
        if ($updated !== false) {
            logStaffActivity($conn, $staff_id, "update_maintenance_$status", 'maintenance', $req_id, ['old_status' => $req['status']], ['new_status' => $status]);
            if (isset($_FILES['before_photo'])) {
                $bp = handleUpload('before_photo', 'maintenance_photos', ['jpg', 'jpeg', 'png'], 5);
                if ($bp && !is_array($bp)) dbExecute($conn, "UPDATE maintenance_requests SET images_path=JSON_SET(COALESCE(images_path,'{}'),'$.before',?) WHERE request_id=? AND assigned_to=?", "sii", [$bp, $req_id, $staff_id]);
            }
            if (isset($_FILES['after_photo'])) {
                $ap = handleUpload('after_photo', 'maintenance_photos', ['jpg', 'jpeg', 'png'], 5);
                if ($ap && !is_array($ap)) dbExecute($conn, "UPDATE maintenance_requests SET completion_images_path=JSON_SET(COALESCE(completion_images_path,'{}'),'$.after',?) WHERE request_id=? AND assigned_to=?", "sii", [$ap, $req_id, $staff_id]);
            }
            json_ok("Maintenance request marked as $status.");
        }
        json_err('Failed to update maintenance request.');
        break;

    case 'log_patrol_checkin':
        if ($staffRole !== 'security') json_err('Access denied.', 403);
        $checkpoint = sanitize($_POST['checkpoint'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        dbInsert($conn, "INSERT INTO security_logs (staff_id, incident_type, location, description, reported_at, notes) VALUES (?, 'patrol log', ?, 'Patrol checkpoint logged.', NOW(), ?)", "iss", [$staff_id, $checkpoint, $notes]);
        json_ok('Patrol check-in logged.');
        break;

    case 'report_incident':
        if ($staffRole !== 'security') json_err('Access denied.', 403);
        $type      = sanitize($_POST['type'] ?? $_POST['incident_type'] ?? '');
        $location  = sanitize($_POST['location'] ?? '');
        $desc      = sanitize($_POST['description'] ?? '');
        $severity  = sanitize($_POST['severity'] ?? 'low');
        $persons   = sanitize($_POST['persons_involved'] ?? '');
        $actions   = sanitize($_POST['actions_taken'] ?? '');
        if (!$type || !$location || !$desc) json_err('Incident type, location, and description required.');
        $id = dbInsert($conn, "INSERT INTO security_incidents (staff_id, incident_type, location, description, severity, persons_involved, actions_taken, status, reported_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'reported', NOW())", "issssss", [$staff_id, $type, $location, $desc, $severity, $persons, $actions]);
        
        if ($id) {
            $name = dbVal($conn, "SELECT name FROM users WHERE id=? LIMIT 1", "i", [$user_id]);
            $admins = dbSelect($conn, "SELECT id FROM staff WHERE role='admin'");
            foreach($admins as $a) notifyStaff($conn, $a['id'], 'alert', "SECURITY INCIDENT: $type at $location reported by $name");
            json_ok('Incident reported.', ['incident_id' => $id]);
        }
        json_err('Failed to report incident.');
        break;

    case 'log_visitor':
        if ($staffRole !== 'security') json_err('Access denied.', 403);
        $name    = sanitize($_POST['visitor_name'] ?? '');
        $id_num  = sanitize($_POST['id_number'] ?? '');
        $purpose = sanitize($_POST['purpose'] ?? '');
        $visiting= sanitize($_POST['person_visiting'] ?? '');
        $ward    = sanitize($_POST['ward'] ?? '');
        if (!$name || !$purpose) json_err('Visitor name and purpose required.');
        dbInsert($conn, "INSERT INTO visitor_logs (logged_by, visitor_name, visitor_id_number, purpose, person_visiting, ward_department, entry_time) VALUES (?, ?, ?, ?, ?, ?, NOW())", "isssss", [$staff_id, $name, $id_num, $purpose, $visiting, $ward]);
        json_ok('Visitor logged successfully.');
        break;

    case 'log_visitor_exit':
        if ($staffRole !== 'security') json_err('Access denied.', 403);
        $vid = (int)($_POST['visitor_log_id'] ?? 0);
        dbExecute($conn, "UPDATE visitor_logs SET exit_time=NOW(), status='checked_out' WHERE log_id=? AND logged_by=?", "ii", [$vid, $staff_id]);
        json_ok('Visitor exit logged.');
        break;
}
