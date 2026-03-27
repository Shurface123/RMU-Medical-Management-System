<?php
// ===================================
// SESSION MANAGER CLASS
// Handles single-role session enforcement
// ===================================

class SessionManager {
    private $conn;
    private $session_timeout = 3600; // 1 hour in seconds
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Start a new session for a user
     * Destroys any existing sessions for the user first
     */
    public function startSession($userId, $userRole) {
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
    public function validateSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if session variables exist
        if (!isset($_SESSION['session_id']) || !isset($_SESSION['user_id'])) {
            return false;
        }
        
        $sessionId = $_SESSION['session_id'];
        $userId = $_SESSION['user_id'];
        
        // Check if session exists in database and is active
        $query = "SELECT * FROM active_sessions WHERE session_id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $sessionId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Session not found or inactive
            $this->destroyCurrentSession();
            return false;
        }
        
        $session = $result->fetch_assoc();
        
        // Check for session timeout (column is 'last_active' in active_sessions table)
        $lastActivity = strtotime($session['last_active'] ?? $session['last_activity'] ?? '0');
        $currentTime = time();
        
        if ($lastActivity === 0 || ($currentTime - $lastActivity) > $this->session_timeout) {
            // Session timed out or column unreadable
            $this->destroyCurrentSession();
            return false;
        }
        
        // Update last activity time
        $this->updateLastActivity($sessionId);
        $_SESSION['last_activity'] = $currentTime;
        
        return true;
    }
    
    /**
     * Destroy all active sessions for a specific user
     */
    public function destroyUserSessions($userId) {
        $query = "DELETE FROM active_sessions WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    
    /**
     * Destroy current session
     */
    public function destroyCurrentSession($user_id = null, $session_id = null, $type = 'manual', $dashboard = 'unknown') {
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
    public function endSession() {
        return $this->destroyCurrentSession();
    }
    
    /**
     * Get current user information
     */
    public function getCurrentUser() {
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
    public function hasRole($role) {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        return $_SESSION['user_role'] === $role;
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole($roles) {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        return in_array($_SESSION['user_role'], $roles);
    }
    
    /**
     * Get active sessions count for a user
     */
    public function getActiveSessionsCount($userId) {
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
    
    private function generateSessionId() {
        return bin2hex(random_bytes(32));
    }
    
    private function storeSessionInDB($sessionId, $userId, $userRole) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $query = "INSERT INTO active_sessions (session_id, user_id, user_role, logged_in_at, last_active, ip_address, user_agent) 
                  VALUES (?, ?, ?, NOW(), NOW(), ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sisss", $sessionId, $userId, $userRole, $ipAddress, $userAgent);
        $stmt->execute();
    }
    
    private function updateLastActivity($sessionId) {
        $query = "UPDATE active_sessions SET last_active = NOW() WHERE session_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
    }
    
    private function updateLastLogin($userId) {
        $query = "UPDATE users SET last_login_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    
    /**
     * Clean up expired sessions (call this periodically)
     */
    public function cleanupExpiredSessions() {
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
    public function logAction($action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
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
}

// ===================================
// HELPER FUNCTIONS
// ===================================

/**
 * Require authentication - redirect if not logged in
 */
function requireAuth($sessionManager, $redirectUrl = '../php/index.php') {
    if (!$sessionManager->validateSession()) {
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Require specific role - redirect if user doesn't have role
 */
function requireRole($sessionManager, $role, $redirectUrl = '../php/index.php') {
    if (!$sessionManager->validateSession() || !$sessionManager->hasRole($role)) {
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Require any of specified roles
 */
function requireAnyRole($sessionManager, $roles, $redirectUrl = '../php/index.php') {
    if (!$sessionManager->validateSession() || !$sessionManager->hasAnyRole($roles)) {
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Check if user is logged in (doesn't redirect)
 */
function isLoggedIn($sessionManager) {
    return $sessionManager->validateSession();
}

/**
 * Get current user role
 */
function getCurrentRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

?>