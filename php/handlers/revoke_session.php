<?php
session_start();
require_once '../../db_conn.php';
require_once '../../classes/SessionManager.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success'=>false, 'message'=>'Not authenticated']);
    exit;
}

$uid = $_SESSION['user_id'];
$sm = new SessionManager($conn);

$data = json_decode(file_get_contents('php://input'), true);
$targetSession = $data['session_id'] ?? 'all';
$currentSid = session_id();

if ($targetSession === 'all') {
    $sm->killOtherSessions($uid, $currentSid);
    
    // push to queue specifically for UI interrupt
    $qQueue = "INSERT INTO forced_logout_queue (user_id, reason) VALUES (?, 'You were logged out from another device.')";
    $stmtQ = $conn->prepare($qQueue);
    if($stmtQ){
        $stmtQ->bind_param("i", $uid);
        $stmtQ->execute();
    }
    echo json_encode(['success'=>true, 'message'=>'All other active devices have been logged out.']);
} else {
    // We don't want to kill the current session from this endpoint
    if ($targetSession === $currentSid) {
        echo json_encode(['success'=>false, 'message'=>'You cannot revoke your active session from here.']);
        exit;
    }
    
    $q = "DELETE FROM active_sessions WHERE user_id=? AND session_id=?";
    $stmt = $conn->prepare($q);
    $stmt->bind_param("is", $uid, $targetSession);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $qQueue = "INSERT INTO forced_logout_queue (user_id, reason) VALUES (?, 'Your specific device was logged out remotely.')";
        $stmtQ = $conn->prepare($qQueue);
        if($stmtQ){
            $stmtQ->bind_param("i", $uid);
            $stmtQ->execute();
        }
        echo json_encode(['success'=>true, 'message'=>'Device session revoked.']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Session not found.']);
    }
}
