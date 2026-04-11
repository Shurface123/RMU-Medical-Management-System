<?php
// ===================================
// SESSION MANAGER CLASS
// Handles single-role session enforcement
// ===================================

class SessionManager
{
    private $conn;
    private $session_timeout = 3600; // 1 hour in seconds

    public function __construct($db_connection)
    {
        $this->conn = $db_connection;
    }

    /**
     * Start a new session for a user
     * Destroys any existing sessions for the user first
     */
    public function startSession($userId, $userRole)
    {
        // Destroy any existing active sessions for this user
        $this->destroyUserSessions($userId);

        // Regenerate session ID to prevent session fixation, then use PHP's native session_id()
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);

        // Use PHP's native session_id() as the single source of truth
        $sessionId = session_id();

        // Set session variables
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $userRole;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        // Store session in database using the real PHP session ID
        $this->storeSessionInDB($sessionId, $userId, $userRole);

        // Update last login time
        $this->updateLastLogin($userId);

        return $sessionId;
    }

    /**
     * Validate current session
     * Returns true if valid, false otherwise
     */
    public function validateSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if session variables exist
        if (!isset($_SESSION['session_id']) || !isset($_SESSION['user_id'])) {
            return false;
        }

        $sessionId = $_SESSION['session_id'];
        $userId = $_SESSION['user_id'];

        // 1. Check forced logout queue
        $qQueue = "SELECT queue_id, reason FROM forced_logout_queue WHERE user_id = ? AND is_executed = 0 LIMIT 1";
        $stmtQ = $this->conn->prepare($qQueue);
        if ($stmtQ) {
            $stmtQ->bind_param("i", $userId);
            $stmtQ->execute();
            $resQ = $stmtQ->get_result();
            if ($resQ->num_rows > 0) {
                $rowQ = $resQ->fetch_assoc();
                $uQ = "UPDATE forced_logout_queue SET is_executed=1, executed_at=NOW() WHERE queue_id=?";
                $stmtU = $this->conn->prepare($uQ);
                $stmtU->bind_param("i", $rowQ['queue_id']);
                $stmtU->execute();

                $this->logLogout($userId, $sessionId, 'forced', 'system');
                $this->destroyCurrentSession();
                $this->renderInterstitial("You have been logged out by the system administrator.", $rowQ['reason'] ?: "Security enforcement.");
            }
        }

        // 2. Check if session exists in DB
        $query = "SELECT * FROM active_sessions WHERE session_id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $sessionId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Session invalidated externally (e.g. multi-device kill)
            $this->destroyCurrentSession();
            $this->renderInterstitial("Session Invalidated", "Your session is no longer active. You may have logged out from another device.");
        }

        $session = $result->fetch_assoc();

        // 3. Check for session timeout
        $lastActivity = strtotime($session['last_active'] ?? $session['last_activity'] ?? '0');
        $currentTime = time();

        if ($lastActivity === 0 || ($currentTime - $lastActivity) > $this->session_timeout) {
            $this->logLogout($userId, $sessionId, 'timeout', 'system');
            $this->destroyCurrentSession();
            $this->renderInterstitial("Your session has expired due to inactivity.", "You have been logged out for your security.");
        }

        // Update last activity time
        $this->updateLastActivity($sessionId);
        $_SESSION['last_activity'] = $currentTime;

        return true;
    }

    /**
     * Destroy all active sessions for a specific user
     */
    public function destroyUserSessions($userId)
    {
        $query = "DELETE FROM active_sessions WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }

    /**
     * Destroy current session
     */
    public function destroyCurrentSession($user_id = null, $session_id = null, $type = 'manual', $dashboard = 'unknown')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sid = $session_id ?? session_id();
        $uid = $user_id ?? ($_SESSION['user_id'] ?? null);

        if ($sid) {
            // Delete from active_sessions
            $query = "DELETE FROM active_sessions WHERE session_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $sid);
            $stmt->execute();
        }

        // Destroy PHP session
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();

        // Return redirect URL from config if available
        $redir = '/RMU-Medical-Management-System/php/index.php';
        $cfgQ = mysqli_query($this->conn, "SELECT redirect_url FROM logout_config LIMIT 1");
        if ($cfgQ && $row = mysqli_fetch_assoc($cfgQ)) {
            if (!empty($row['redirect_url'])) {
                $redir = $row['redirect_url'];
                if (strpos($redir, '/') !== 0 && strpos($redir, 'http') !== 0) {
                    $redir = '/RMU-Medical-Management-System/php/' . ltrim($redir, '/');
                }
            }
        }
        return $redir;
    }

    /**
     * Alias for destroyCurrentSession() - for backward compatibility
     * Use this when you want to end/logout the current user's session
     */
    public function endSession()
    {
        return $this->destroyCurrentSession();
    }

    /**
     * Get current user information
     */
    public function getCurrentUser()
    {
        if (!$this->validateSession()) {
            return null;
        }

        $userId = $_SESSION['user_id'];

        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role)
    {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }

        return $_SESSION['user_role'] === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole($roles)
    {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }

        return in_array($_SESSION['user_role'], $roles);
    }

    /**
     * Get active sessions count for a user
     */
    public function getActiveSessionsCount($userId)
    {
        $query = "SELECT COUNT(*) as count FROM active_sessions WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['count'];
    }

    /**
     * Private helper methods
     */

    private function generateSessionId()
    {
        return bin2hex(random_bytes(32));
    }

    private function storeSessionInDB($sessionId, $userId, $userRole)
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $query = "INSERT INTO active_sessions (session_id, user_id, user_role, logged_in_at, last_active, ip_address, user_agent) 
                  VALUES (?, ?, ?, NOW(), NOW(), ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sisss", $sessionId, $userId, $userRole, $ipAddress, $userAgent);
        $stmt->execute();
    }

    private function updateLastActivity($sessionId)
    {
        $query = "UPDATE active_sessions SET last_active = NOW() WHERE session_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
    }

    private function updateLastLogin($userId)
    {
        $query = "UPDATE users SET last_login_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }

    /**
     * Clean up expired sessions (call this periodically)
     */
    public function cleanupExpiredSessions()
    {
        $timeout = $this->session_timeout;
        $query = "DELETE FROM active_sessions 
                  WHERE TIMESTAMPDIFF(SECOND, last_active, NOW()) > ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $timeout);
        $stmt->execute();

        return $stmt->affected_rows;
    }

    /**
     * Log audit trail
     */
    public function logAction($action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null)
    {
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
        $newValuesJson = $newValues ? json_encode($newValues) : null;

        $query = "INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("isssssss", $userId, $action, $tableName, $recordId, $oldValuesJson, $newValuesJson, $ipAddress, $userAgent);
        $stmt->execute();
    }

    /**
     * Log the logout event manually
     */
    private function logLogout($userId, $sessionId, $type, $dashboard = 'unknown')
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'unknown';
        $device = 'Unknown';
        $browser = 'Unknown';
        $healthMsg = $_SESSION['health_message_shown'] ?? 'None';

        $stmt = $this->conn->prepare("INSERT INTO logout_logs (user_id, role, session_id, logout_type, logout_confirmed_at, ip_address, device_info, browser, dashboard_logged_out_from, health_message_shown) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issssssss", $userId, $role, $sessionId, $type, $ip, $device, $browser, $dashboard, $healthMsg);
            $stmt->execute();
        }
    }

    /**
     * Terminate all other sessions for current user
     */
    public function killOtherSessions($userId, $currentSessionId)
    {
        $q = "DELETE FROM active_sessions WHERE user_id = ? AND session_id != ?";
        $stmt = $this->conn->prepare($q);
        if ($stmt) {
            $stmt->bind_param("is", $userId, $currentSessionId);
            $stmt->execute();
        }
    }

    /**
     * Rendertial modal and exit
     */
    private function renderInterstitial($title, $msg)
    {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
        if ($isAjax) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => $title, 'redirect' => '/RMU-Medical-Management-System/php/index.php']);
            exit();
        }
        echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session Terminated</title>
<style>
  body { margin: 0; padding: 0; font-family: "Poppins", sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; }
  .modal { background: #fff; border-radius: 16px; padding: 2.5rem; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.1); max-width: 440px; margin: 1rem; width:100%; border-top: 5px solid #e74c3c; animation: popIn 0.3s ease forwards; }
  @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
  .icon { color: #e74c3c; font-size: 3rem; margin-bottom: 1.25rem; background: rgba(231,76,60,0.1); width: 80px; height: 80px; line-height: 80px; border-radius: 50%; display: inline-block; }
  h2 { margin: 0 0 0.75rem 0; color: #1e293b; font-size: 1.5rem; }
  p { color: #64748b; font-size: 0.95rem; line-height: 1.5; margin-bottom: 2rem; }
  .btn { display: inline-block; background: #e74c3c; color: white; padding: 0.75rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: transform 0.2s, background 0.2s; }
  .btn:hover { background: #c0392b; transform: translateY(-2px); }
</style>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="modal">
  <div class="icon"><i class="fas fa-ban"></i></div>
  <h2>' . htmlspecialchars($title) . '</h2>
  <p>' . htmlspecialchars($msg) . '</p>
  <a href="/RMU-Medical-Management-System/php/index.php" class="btn btn-primary btn"><span class="btn-text">Go to Login</span></a>
</div>
<script>setTimeout(function(){ window.location.href="/RMU-Medical-Management-System/php/index.php"; }, 5000);</script>
</body>
</html>';
        exit();
    }
}

// ===================================
// HELPER FUNCTIONS
// ===================================

/**
 * Require authentication - redirect if not logged in
 */
if (!function_exists('requireAuth')) {
    function requireAuth($sessionManager, $redirectUrl = '../php/index.php')
    {
        if (!$sessionManager->validateSession()) {
            header("Location: $redirectUrl");
            exit();
        }
    }
}

/**
 * Require specific role - redirect if user doesn't have role
 */
if (!function_exists('requireRole')) {
    function requireRole($sessionManager, $role, $redirectUrl = '../php/index.php')
    {
        if (!$sessionManager->validateSession() || !$sessionManager->hasRole($role)) {
            header("Location: $redirectUrl");
            exit();
        }
    }
}

/**
 * Require any of specified roles
 */
// function requireAnyRole($sessionManager, $roles, $redirectUrl = '../php/index.php') {
//     if (!$sessionManager->validateSession() || !$sessionManager->hasAnyRole($roles)) {
//         header("Location: $redirectUrl");
//         exit();
//     }
// }

/**
 * Check if user is logged in (doesn't redirect)
 */
function isLoggedIn($sessionManager)
{
    return $sessionManager->validateSession();
}

/**
 * Get current user role
 */
// function getCurrentRole()
// {
//     return $_SESSION['user_role'] ?? null;
// }

/**
 * Get current user ID
 */
function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

?>