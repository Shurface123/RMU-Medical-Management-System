<?php
// ============================================================
// UNIFIED NOTIFICATIONS API
// /php/api/get_notifications.php  (fully updated Phase 5)
// Supports: list, mark-read, mark-all-read, unread-count, trigger
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../db_conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit;
}
$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'patient';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? $action;

// Deep-link map: module → URL
function buildLink($module, $relId, $userRole) {
    $base = '/RMU-Medical-Management-System/php/dashboards/';
    $map  = [
        'appointments'  => $userRole==='doctor' ? "doctor_dashboard.php?tab=appointments" : "my_appointments.php",
        'prescriptions' => $userRole==='doctor' ? "doctor_dashboard.php?tab=prescriptions" : "prescription_refills.php",
        'lab'           => $userRole==='doctor' ? "doctor_dashboard.php?tab=lab" : "#",
        'records'       => $userRole==='doctor' ? "doctor_dashboard.php?tab=records" : "medical_records.php",
        'beds'          => $userRole==='doctor' ? "doctor_dashboard.php?tab=beds" : "#",
        'staff'         => "doctor_dashboard.php?tab=staff",
        'medicine'      => $userRole==='doctor' ? "doctor_dashboard.php?tab=medicine" : "#",
        'inventory'     => $userRole==='pharmacist' ? "pharmacy_dashboard.php" : "doctor_dashboard.php?tab=medicine",
        'system'        => "#",
    ];
    $path = $map[$module] ?? "#";
    if ($path !== "#" && $relId) $path .= (str_contains($path,'?') ? "&" : "?")."notif_id=$relId";
    return $base . $path;
}

switch ($action) {

// ── List Notifications ────────────────────────────────────
case 'list':
    $since  = (int)($_GET['since'] ?? 0);
    $limit  = (int)($_GET['limit'] ?? 30);
    $unread_only = !empty($_GET['unread_only']);
    $cond   = $since ? "AND n.id > $since" : "";
    $ucond  = $unread_only ? "AND n.is_read=0" : "";

    $q = mysqli_query($conn,
        "SELECT n.id, n.title, n.message, n.type, n.is_read, n.related_module, n.related_id,
                n.created_at, u.name AS from_user
         FROM notifications n
         LEFT JOIN users u ON n.from_user_id=u.id
         WHERE n.user_id=$userId $cond $ucond
         ORDER BY n.created_at DESC LIMIT $limit");

    $notifs = [];
    if ($q) while ($row = mysqli_fetch_assoc($q)) {
        $notifs[] = [
            'id'      => (int)$row['id'],
            'title'   => $row['title'] ?? '',
            'message' => $row['message'] ?? '',
            'type'    => $row['type'] ?? 'system',
            'is_read' => (bool)$row['is_read'],
            'module'  => $row['related_module'] ?? 'system',
            'rel_id'  => $row['related_id'],
            'link'    => buildLink($row['related_module'] ?? 'system', $row['related_id'], $userRole),
            'time'    => $row['created_at'],
            'from'    => $row['from_user'] ?? null,
        ];
    }

    $unread_count = (int)(mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM notifications WHERE user_id=$userId AND is_read=0"))[0] ?? 0);

    echo json_encode(['success'=>true,'notifications'=>$notifs,'unread_count'=>$unread_count]);
    break;

// ── Mark One Read ─────────────────────────────────────────
case 'mark_read':
    $nid = (int)($body['notification_id'] ?? $_POST['notification_id'] ?? 0);
    if (!$nid) { echo json_encode(['success'=>false,'error'=>'No ID']); break; }
    mysqli_query($conn,"UPDATE notifications SET is_read=1, read_at=NOW() WHERE id=$nid AND user_id=$userId");
    echo json_encode(['success'=>true]);
    break;

// ── Mark All Read ─────────────────────────────────────────
case 'mark_all_read':
    mysqli_query($conn,"UPDATE notifications SET is_read=1, read_at=NOW() WHERE user_id=$userId AND is_read=0");
    echo json_encode(['success'=>true,'updated'=>mysqli_affected_rows($conn)]);
    break;

// ── Unread Count Only (for polling) ─────────────────────
case 'count':
    $c = (int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$userId AND is_read=0"))[0]??0);
    echo json_encode(['success'=>true,'unread_count'=>$c]);
    break;

// ── Trigger: fire a notification ─────────────────────────
// Called server-side. Not for direct client use.
default:
    echo json_encode(['success'=>false,'error'=>'Unknown action']);
}
