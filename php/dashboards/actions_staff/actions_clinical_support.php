<?php
/**
 * actions_clinical_support.php — Kitchen & Clinical Support Staff Actions
 */
if (!defined('AJAX_REQUEST')) exit;

switch ($action) {

    case 'update_kitchen_task_status':
        if ($staffRole !== 'kitchen_staff') json_err('Access denied.',403);
        $task_id = (int)($_POST['task_id'] ?? 0);
        $status  = sanitize($_POST['status'] ?? '');
        $valid   = ['in preparation','ready','delivered'];
        if (!in_array($status,$valid)) json_err('Invalid status.');
        $t = dbRow($conn,"SELECT task_id FROM kitchen_tasks WHERE task_id=? AND assigned_to=?","ii",[$task_id,$staff_id]);
        if (!$t) json_err('Task not found.',403);
        $extra = ($status==='delivered') ? ",delivered_at=NOW()" : "";
        dbExecute($conn,"UPDATE kitchen_tasks SET preparation_status=?,updated_at=NOW()$extra WHERE task_id=?","si",[$status,$task_id]);
        json_ok('Task status updated.');

    case 'report_dietary_issue':
        if ($staffRole !== 'kitchen_staff') json_err('Access denied.',403);
        $patient_name = sanitize($_POST['patient_name'] ?? '');
        $ward = sanitize($_POST['ward'] ?? '');
        $issue = sanitize($_POST['issue'] ?? '');
        if (!$issue) json_err('Issue description required.');
        dbInsert($conn,"INSERT INTO kitchen_dietary_flags (staff_id,patient_name,ward,issue_description,flagged_at,status) VALUES (?,?,?,?,'flagged',NOW())","isss",[$staff_id,$patient_name,$ward,$issue]);
        json_ok('Dietary issue flagged. Admin and nursing notified.');
}
