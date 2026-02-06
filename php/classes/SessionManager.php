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
        
        // Generate unique session ID
        $sessionId = $this->generateSessionId();
        
        // Start PHP session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set session variables
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $userRole;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Store session in database
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
        $query = "SELECT * FROM user_sessions WHERE session_id = ? AND user_id = ? AND is_active = TRUE";
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
        
        // Check for session timeout
        $lastActivity = strtotime($session['last_activity']);
        $currentTime = time();
        
        if (($currentTime - $lastActivity) > $this->session_timeout) {
            // Session timed out
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
        $query = "UPDATE user_sessions SET is_active = FALSE, logout_time = NOW() WHERE user_id = ? AND is_active = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    
    /**
     * Destroy current session
     */
    public function destroyCurrentSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['session_id'])) {
            $sessionId = $_SESSION['session_id'];
            
            // Mark session as inactive in database
            $query = "UPDATE user_sessions SET is_active = FALSE, logout_time = NOW() WHERE session_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $sessionId);
            $stmt->execute();
        }
        
        // Destroy PHP session
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
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
        $query = "SELECT COUNT(*) as count FROM user_sessions WHERE user_id = ? AND is_active = TRUE";
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
        
        $query = "INSERT INTO user_sessions (session_id, user_id, user_role, login_time, last_activity, ip_address, user_agent, is_active) 
                  VALUES (?, ?, ?, NOW(), NOW(), ?, ?, TRUE)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sisss", $sessionId, $userId, $userRole, $ipAddress, $userAgent);
        $stmt->execute();
    }
    
    private function updateLastActivity($sessionId) {
        $query = "UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
    }
    
    private function updateLastLogin($userId) {
        $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    
    /**
     * Clean up expired sessions (call this periodically)
     */
    public function cleanupExpiredSessions() {
        $timeout = $this->session_timeout;
        $query = "UPDATE user_sessions 
                  SET is_active = FALSE, logout_time = NOW() 
                  WHERE is_active = TRUE 
                  AND TIMESTAMPDIFF(SECOND, last_activity, NOW()) > ?";
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
