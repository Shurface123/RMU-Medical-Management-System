<?php
/**
 * actions_communication.php — Messaging, Notifications & Task Management Staff Actions
 */
if (!defined('AJAX_REQUEST')) exit;

switch ($action) {

    case 'update_task_status':
        $task_id = (int)($_POST['task_id'] ?? 0);
        $status  = sanitize($_POST['status'] ?? '');
        $notes   = sanitize($_POST['notes'] ?? '');
        $valid_statuses = ['pending','in progress','completed','cancelled'];
        if (!in_array($status,$valid_statuses)) json_err('Invalid status.');
        $t = dbRow($conn,"SELECT task_id FROM staff_tasks WHERE task_id=? AND assigned_to=?","ii",[$task_id,$staff_id]);
        if (!$t) json_err('Task not found.',403);
        $extra_sql = ($status === 'completed') ? ",completed_at=NOW()" : "";
        dbExecute($conn,"UPDATE staff_tasks SET status=?,completion_notes=?,updated_at=NOW()$extra_sql WHERE task_id=?","ssi",[$status,$notes,$task_id]);
        if ($status === 'completed' && isset($_FILES['proof'])) {
            $photo = handleUpload('proof','task_proofs',['jpg','jpeg','png'],5);
            if ($photo && !is_array($photo)) dbExecute($conn,"UPDATE staff_tasks SET completion_photo=? WHERE task_id=?","si",[$photo,$task_id]);
        }
        logStaffActivity($conn,$staff_id,'update_task_status','tasks',$task_id,null,['status'=>$status]);
        json_ok('Task updated successfully.');

    case 'complete_task_checklist':
        $chk_id = (int)($_POST['checklist_id'] ?? 0);
        $task_id= (int)($_POST['task_id'] ?? 0);
        $state  = (int)($_POST['state'] ?? 0);
        dbExecute($conn,"UPDATE staff_task_checklists SET is_completed=?,completed_by=?,completed_at=".($state?'NOW()':'NULL')." WHERE checklist_id=? AND task_id=?","iiii",[$state,$staff_id,$chk_id,$task_id]);
        json_ok('Checklist item updated.');

    case 'mark_notification_read':
        $nid = (int)($_POST['notif_id'] ?? 0);
        if ($nid) dbExecute($conn,"UPDATE staff_notifications SET is_read=1 WHERE notification_id=? AND staff_id=?","ii",[$nid,$staff_id]);
        else dbExecute($conn,"UPDATE staff_notifications SET is_read=1 WHERE staff_id=?","i",[$staff_id]);
        json_ok('Notifications marked as read.');

    case 'send_message':
        $receiver = (int)($_POST['receiver_id'] ?? 0);
        $subject  = sanitize($_POST['subject'] ?? '');
        $content  = sanitize($_POST['body'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'normal');
        if (!$receiver || !$content) json_err('Recipient and message body required.');
        $id = dbInsert($conn,
            "INSERT INTO staff_messages (sender_id,receiver_id,subject,message_content,priority,is_read,sent_at) VALUES (?,?,?,?,?,0,NOW())",
            "iisss", [$user_id, $receiver, $subject, $content, $priority]
        );
        if ($id) {
            logStaffActivity($conn, $staff_id, 'send_message', 'communication', $id);
            
            // Notify recipient
            $recipStaffId = getStaffId($conn, $receiver);
            if ($recipStaffId) {
                $senderName = dbVal($conn, "SELECT name FROM users WHERE id=? LIMIT 1", "i", [$user_id]);
                notifyStaff($conn, $recipStaffId, 'message', "New secure message from $senderName", 'messages');
            }
            
            json_ok('Message dispatched successfully.', ['message_id'=>$id]);
        }
        json_err('Failed to send message.');

    case 'get_available_recipients':
        $list = dbSelect($conn, "SELECT id, name AS full_name, user_role AS role FROM users WHERE id != ? AND user_role != 'patient' AND status='active' ORDER BY name ASC", "i", [$user_id]);
        json_ok('Recipients fetched.', ['recipients' => $list]);
        break;

    case 'mark_message_read':
        $mid = (int)($_POST['message_id'] ?? 0);
        dbExecute($conn, "UPDATE staff_messages SET is_read=1 WHERE message_id=? AND receiver_id=?", "ii", [$mid, $user_id]);
        json_ok('Marked as read');
        break;

    case 'update_password': // Included in communication/security usually
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

    case 'save_settings':
        $theme = sanitize($_POST['theme'] ?? 'light');
        $notif_sound = (int)($_POST['notif_sound'] ?? 1);
        $lang = sanitize($_POST['language'] ?? 'en');
        $existing = dbRow($conn,"SELECT settings_id FROM staff_settings WHERE staff_id=? LIMIT 1","i",[$staff_id]);
        if ($existing) dbExecute($conn,"UPDATE staff_settings SET theme=?,notif_sound=?,language=?,updated_at=NOW() WHERE staff_id=?","sisi",[$theme,$notif_sound,$lang,$staff_id]);
        else dbInsert($conn,"INSERT INTO staff_settings (staff_id,theme,notif_sound,language,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())","isis",[$staff_id,$theme,$notif_sound,$lang]);
        json_ok('Settings saved.');

    case 'toggle_2fa':
        $enable = (int)($_POST['enable'] ?? 0);
        dbExecute($conn, "UPDATE users SET two_fa_enabled=?, updated_at=NOW() WHERE id=?", "ii", [$enable, $user_id]);
        require_once '../classes/AuditLogger.php';
        $audit = new AuditLogger($conn);
        $audit->log2FAChange($user_id, $enable);
        json_ok('2FA ' . ($enable ? 'enabled' : 'disabled'));

    case 'logout_session':
        $sid = (int)($_POST['session_id'] ?? 0);
        dbExecute($conn,"DELETE FROM staff_sessions WHERE session_id=? AND staff_id=?","ii",[$sid,$staff_id]);
        logStaffActivity($conn,$staff_id,'terminate_session','settings',$sid);
        json_ok('Session terminated.');

    case 'logout_all_sessions':
        dbExecute($conn,"DELETE FROM staff_sessions WHERE staff_id=? AND is_current=0","i",[$staff_id]);
        logStaffActivity($conn,$staff_id,'terminate_all_sessions','settings');
        json_ok('All other sessions terminated.');
}
