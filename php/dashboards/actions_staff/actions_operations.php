<?php
/**
 * actions_operations.php — Sanitation & Laundry Operations Staff Actions
 */
if (!defined('AJAX_REQUEST')) exit;

switch ($action) {

    case 'start_cleaning':
        if ($staffRole !== 'cleaner') json_err('Access denied.', 403);
        $sched_id = (int)($_POST['schedule_id'] ?? 0);
        dbExecute($conn, "UPDATE cleaning_schedules SET status='in progress', started_at=NOW() WHERE id=? AND assigned_to=?", "ii", [$sched_id, $staff_id]);
        dbInsert($conn, "INSERT INTO cleaning_logs (staff_id, schedule_id, ward_room_area, cleaning_type, started_at, sanitation_status, created_at) SELECT ?, id, ward_room_area, cleaning_type, NOW(), 'in progress', NOW() FROM cleaning_schedules WHERE id=?", "ii", [$staff_id, $sched_id]);
        json_ok('Cleaning started.');
        break;

    case 'complete_cleaning':
        if ($staffRole !== 'cleaner') json_err('Access denied.', 403);
        $sched_id = (int)($_POST['schedule_id'] ?? 0);
        $notes = sanitize($_POST['notes'] ?? '');
        $san_status = sanitize($_POST['sanitation_status'] ?? 'clean');
        $photo = handleUpload('proof', 'cleaning_proofs', ['jpg', 'jpeg', 'png'], 5);
        if (is_array($photo)) json_err($photo['error']);
        dbExecute($conn, "UPDATE cleaning_schedules SET status='completed', completed_at=NOW() WHERE id=? AND assigned_to=?", "ii", [$sched_id, $staff_id]);
        dbExecute($conn, "UPDATE cleaning_logs SET completed_at=NOW(), sanitation_status=?, notes=?, photo_proof=? WHERE schedule_id=? AND staff_id=?", "sssii", [$san_status, $notes, $photo, $sched_id, $staff_id]);
        logStaffActivity($conn, $staff_id, 'complete_cleaning', 'cleaning', $sched_id);
        json_ok('Cleaning marked as complete.');
        break;

    case 'report_contamination':
        if ($staffRole !== 'cleaner') json_err('Access denied.', 403);
        $location  = sanitize($_POST['location'] ?? '');
        $type      = sanitize($_POST['contamination_type'] ?? '');
        $severity  = sanitize($_POST['severity'] ?? 'medium');
        $desc      = sanitize($_POST['description'] ?? '');
        if (!$location || !$desc) json_err('Location and description required.');
        $photo = handleUpload('photo', 'contamination', ['jpg', 'jpeg', 'png'], 5);
        if (is_array($photo)) json_err($photo['error']);
        
        $cid = dbInsert($conn, "INSERT INTO contamination_reports (reported_by, location, contamination_type, severity, description, photo_path, status, reported_at) VALUES (?, ?, ?, ?, ?, ?, 'reported', NOW())", "isssss", [$staff_id, $location, $type, $severity, $desc, $photo]);
        
        if ($cid) {
            $name = dbVal($conn, "SELECT name FROM users WHERE id=? LIMIT 1", "i", [$user_id]);
            logStaffActivity($conn, $staff_id, 'report_hazard', 'operations', $cid);

            // Notify Admin & Maintenance
            $admins = dbSelect($conn, "SELECT id FROM staff WHERE role='admin'");
            foreach($admins as $a) notifyStaff($conn, $a['id'], 'alert', "CRITICAL: Biohazard reported at $location by $name");

            $maintenanceList = dbSelect($conn, "SELECT id FROM staff WHERE role='maintenance'");
            foreach($maintenanceList as $m) notifyStaff($conn, $m['id'], 'alert', "Sanitation Alert: $type risk at $location. Technical cleanup required.");

            json_ok('Hazard report logged. Safety protocols initiated.', ['report_id' => $cid]);
        }
        json_err('Failed to record hazard report.');
        break;

    case 'register_laundry_batch':
        if ($staffRole !== 'laundry_staff') json_err('Access denied.', 403);
        $item_type = sanitize($_POST['item_type'] ?? '');
        $count = (int)($_POST['item_count'] ?? 0);
        $weight = (float)($_POST['weight_kg'] ?? $_POST['weight'] ?? 0);
        $ward = sanitize($_POST['origin_ward'] ?? '');
        $batch_code = 'LB-' . strtoupper(substr($ward, 0, 3)) . '-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $id = dbInsert($conn, "INSERT INTO laundry_batches (batch_code, assigned_to, requested_by, batch_type, item_count, weight_kg, collection_status, collected_at, created_at) VALUES (?, ?, ?, ?, ?, ?, 'collected', NOW(), NOW())", "isssid", [$batch_code, $staff_id, $ward, $item_type, $count, $weight]);
        if ($id) json_ok('Batch registered.', ['batch_code' => $batch_code, 'batch_id' => $id]);
        json_err('Failed to register batch.');
        break;

    case 'update_batch_status':
        if ($staffRole !== 'laundry_staff') json_err('Access denied.', 403);
        $batch_id = (int)($_POST['batch_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $valid = ['collected', 'washing', 'ironing', 'quality check', 'delivered'];
        if (!in_array($status, $valid)) json_err('Invalid batch status.');
        $col_map = ['collected' => 'collection_status', 'washing' => 'washing_status', 'ironing' => 'ironing_status', 'delivered' => 'delivery_status'];
        $col = $col_map[$status] ?? 'washing_status';
        dbExecute($conn, "UPDATE laundry_batches SET $col=?, updated_at=NOW() WHERE batch_id=? AND assigned_to=?", "sii", [$status, $batch_id, $staff_id]);
        json_ok('Batch status updated.');
        break;

    case 'report_laundry_damage':
        if ($staffRole !== 'laundry_staff') json_err('Access denied.', 403);
        $batch_id = (int)($_POST['batch_id'] ?? 0);
        $item_type = sanitize($_POST['item_type'] ?? '');
        $qty = (int)($_POST['quantity'] ?? 0);
        $desc = sanitize($_POST['description'] ?? '');
        $photo = handleUpload('photo', 'laundry_damage', ['jpg', 'jpeg', 'png'], 5);
        if (is_array($photo)) json_err($photo['error']);
        dbInsert($conn, "INSERT INTO laundry_damage_reports (batch_id, staff_id, item_type, quantity, description, photo_path, reported_at) VALUES (?, ?, ?, ?, ?, ?, NOW())", "iisiss", [$batch_id, $staff_id, $item_type, $qty, $desc, $photo]);
        json_ok('Damage report submitted.');
        break;
}
