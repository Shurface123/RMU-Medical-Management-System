<?php
/**
 * Admin Profile API Endpoint
 * Handles personal info, security, notifications, and activity summaries.
 */

function handleProfile($method, $userId, $userRole, $pathPart) {
    global $conn;

    // While focused on admin, these endpoints can ideally serve any user checking their own profile
    if (!isset($_SESSION['user_id'])) {
        ApiResponse::error('Unauthorized', 401);
    }
    
    // Ensure the acting user is the one modifying their profile, or is an admin modifying someone else
    $targetUserId = $_SESSION['user_id'];

    try {
        if ($method === 'GET' && $pathPart === 'overview') {
            getProfileOverview($targetUserId);
        } else if ($method === 'POST' && $pathPart === 'update-info') {
            updatePersonalInfo($targetUserId);
        } else if ($method === 'POST' && $pathPart === 'change-password') {
            changePassword($targetUserId);
        } else if ($method === 'GET' && $pathPart === 'sessions') {
            getActiveSessions($targetUserId);
        } else if ($method === 'POST' && $pathPart === 'revoke-session') {
            revokeSession($targetUserId);
        } else if ($method === 'GET' && $pathPart === 'notifications') {
            getNotificationPrefs($targetUserId);
        } else if ($method === 'POST' && $pathPart === 'update-notifications') {
            updateNotificationPrefs($targetUserId);
        } else if ($method === 'GET' && $pathPart === 'activity') {
            getActivitySummary($targetUserId);
        } else if ($method === 'POST' && $pathPart === 'upload-photo') {
            uploadProfilePhoto($targetUserId);
        } else {
            ApiResponse::error('Invalid profile endpoint', 400);
        }
    } catch (Exception $e) {
        ApiResponse::error('Profile API Error: ' . $e->getMessage(), 500);
    }
}

function getProfileOverview($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, name, email, user_role, is_active, created_at, last_login_at, two_factor_enabled, profile_photo, emergency_contact_name, emergency_contact_phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) ApiResponse::error('User not found', 404);

    // Get active session count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_sessions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user['session_count'] = $stmt->get_result()->fetch_assoc()['count'];

    ApiResponse::success($user);
}

function updatePersonalInfo($userId) {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = mysqli_real_escape_string($conn, $data['name']);
    $email = mysqli_real_escape_string($conn, $data['email']);
    $emName = mysqli_real_escape_string($conn, $data['emergency_contact_name'] ?? '');
    $emPhone = mysqli_real_escape_string($conn, $data['emergency_contact_phone'] ?? '');
    // Note: Production should validate email uniqueness and format

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, emergency_contact_name = ?, emergency_contact_phone = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $email, $emName, $emPhone, $userId);
    
    if ($stmt->execute()) {
        $_SESSION['name'] = $name; // Update session
        ApiResponse::success(['message' => 'Profile updated successfully']);
    } else {
        ApiResponse::error('Update failed');
    }
}

function changePassword($userId) {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true);
    
    $current = $data['current_password'];
    $new = $data['new_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $hash = $stmt->get_result()->fetch_assoc()['password'];

    if (!password_verify($current, $hash)) {
        ApiResponse::error('Incorrect current password', 400);
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $newHash, $userId);
    
    if ($stmt->execute()) {
        ApiResponse::success(['message' => 'Password changed successfully']);
    } else {
        ApiResponse::error('Failed to update password');
    }
}

function getActiveSessions($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, ip_address, device_type, browser, last_active, created_at FROM user_sessions WHERE user_id = ? ORDER BY last_active DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $sessions = [];
    while ($row = $res->fetch_assoc()) $sessions[] = $row;
    
    // Also fetch last 10 audit logins
    $stmt = $conn->prepare("SELECT action_type, description, ip_address, created_at FROM audit_logs WHERE user_id = ? AND action_type LIKE '%login%' ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $history = [];
    while ($row = $res->fetch_assoc()) $history[] = $row;

    ApiResponse::success(['active' => $sessions, 'history' => $history]);
}

function revokeSession($userId) {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = (int)$data['session_id'];

    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $sessionId, $userId);
    if ($stmt->execute()) {
        ApiResponse::success(['message' => 'Session revoked']);
    } else {
        ApiResponse::error('Failed to revoke session');
    }
}

function getNotificationPrefs($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM user_notification_prefs WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $prefs = [];
    while ($row = $res->fetch_assoc()) $prefs[] = $row;
    ApiResponse::success($prefs);
}

function updateNotificationPrefs($userId) {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true);
    
    $event = mysqli_real_escape_string($conn, $data['event_type']);
    $inApp = (int)$data['in_app'];
    $email = (int)$data['email'];
    $push = (int)$data['push'];
    
    $stmt = $conn->prepare("INSERT INTO user_notification_prefs (user_id, event_type, in_app, email, push) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE in_app=VALUES(in_app), email=VALUES(email), push=VALUES(push)");
    $stmt->bind_param("isiii", $userId, $event, $inApp, $email, $push);
    
    if ($stmt->execute()) ApiResponse::success(['message' => 'Preferences updated']);
    else ApiResponse::error('Failed to update preferences');
}

function getActivitySummary($userId) {
    global $conn;
    
    // Quick month stats
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM audit_logs WHERE user_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE())");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $monthActions = $stmt->get_result()->fetch_assoc()['count'];
    
    // Recent feed
    $stmt = $conn->prepare("SELECT action_type, description, created_at FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $feed = [];
    while ($row = $res->fetch_assoc()) $feed[] = $row;
    
    ApiResponse::success(['monthly_actions' => $monthActions, 'feed' => $feed]);
}

function uploadProfilePhoto($userId) {
    global $conn;
    
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== 0) {
        ApiResponse::error('No file uploaded or upload error');
    }

    $file = $_FILES['photo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        ApiResponse::error('Invalid file type. Only JPG, PNG, and WebP are allowed.', 400);
    }

    if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
        ApiResponse::error('File too large. Maximum size is 2MB.', 400);
    }

    $targetDir = "../../../uploads/profiles/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "profile_" . $userId . "_" . time() . "." . $ext;
    $targetPath = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $dbPath = "uploads/profiles/" . $filename;
        $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
        $stmt->bind_param("si", $dbPath, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['profile_image'] = $filename; // Update session
            ApiResponse::success(['url' => $dbPath, 'message' => 'Profile photo updated successfully']);
        } else {
            ApiResponse::error('Failed to update database');
        }
    } else {
        ApiResponse::error('Failed to move uploaded file');
    }
}
