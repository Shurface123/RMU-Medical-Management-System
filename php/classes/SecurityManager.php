<?php
// ===================================
// SECURITY MANAGER CLASS
// Handles password policies, rate limiting, and login tracking
// ===================================

class SecurityManager {
    private $conn;
    
    // Password policy settings
    private $minPasswordLength = 8;
    private $requireUppercase = true;
    private $requireLowercase = true;
    private $requireNumbers = true;
    private $requireSpecialChars = true;
    private $passwordHistoryCount = 5;
    private $passwordExpiryDays = 90;
    
    // Rate limiting settings
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 minutes in seconds
    private $attemptWindow = 300; // 5 minutes in seconds
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->loadSettings();
    }
    
    /**
     * Load settings from database
     */
    private function loadSettings() {
        $query = "SELECT setting_key, setting_value FROM system_settings 
                  WHERE setting_key IN ('password_min_length', 'max_login_attempts', 'session_timeout')";
        $result = mysqli_query($this->conn, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            switch ($row['setting_key']) {
                case 'password_min_length':
                    $this->minPasswordLength = (int)$row['setting_value'];
                    break;
                case 'max_login_attempts':
                    $this->maxLoginAttempts = (int)$row['setting_value'];
                    break;
            }
        }
    }
    
    /**
     * Validate password strength
     */
    public function validatePassword($password) {
        $errors = [];
        
        // Check length
        if (strlen($password) < $this->minPasswordLength) {
            $errors[] = "Password must be at least {$this->minPasswordLength} characters long";
        }
        
        // Check uppercase
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        // Check lowercase
        if ($this->requireLowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        // Check numbers
        if ($this->requireNumbers && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        // Check special characters
        if ($this->requireSpecialChars && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        // Check common passwords
        if ($this->isCommonPassword($password)) {
            $errors[] = "This password is too common. Please choose a stronger password";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Check if password was used before
     */
    public function checkPasswordHistory($userId, $newPassword) {
        $query = "SELECT password_hash FROM password_history 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC 
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $userId, $this->passwordHistoryCount);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if (password_verify($newPassword, $row['password_hash'])) {
                return false; // Password was used before
            }
        }
        
        return true; // Password is new
    }
    
    /**
     * Add password to history
     */
    public function addPasswordToHistory($userId, $passwordHash) {
        $query = "INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $userId, $passwordHash);
        $stmt->execute();
        
        // Clean up old history
        $deleteQuery = "DELETE FROM password_history 
                        WHERE user_id = ? 
                        AND history_id NOT IN (
                            SELECT history_id FROM (
                                SELECT history_id FROM password_history 
                                WHERE user_id = ? 
                                ORDER BY created_at DESC 
                                LIMIT ?
                            ) AS recent
                        )";
        
        $stmt = $this->conn->prepare($deleteQuery);
        $stmt->bind_param("iii", $userId, $userId, $this->passwordHistoryCount);
        $stmt->execute();
    }
    
    /**
     * Record login attempt
     */
    public function recordLoginAttempt($username, $success, $ipAddress, $userAgent = '', $failureReason = '') {
        $query = "INSERT INTO login_attempts (username, ip_address, success, user_agent, failure_reason) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sisss", $username, $ipAddress, $success, $userAgent, $failureReason);
        $stmt->execute();
    }
    
    /**
     * Check if account is locked
     */
    public function isAccountLocked($username, $ipAddress) {
        // Check failed attempts in the last window
        $query = "SELECT COUNT(*) as attempts 
                  FROM login_attempts 
                  WHERE (username = ? OR ip_address = ?) 
                  AND success = 0 
                  AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $username, $ipAddress, $this->attemptWindow);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['attempts'] >= $this->maxLoginAttempts) {
            // Check if lockout period has passed
            $lastAttemptQuery = "SELECT attempted_at 
                                 FROM login_attempts 
                                 WHERE (username = ? OR ip_address = ?) 
                                 AND success = 0 
                                 ORDER BY attempted_at DESC 
                                 LIMIT 1";
            
            $stmt = $this->conn->prepare($lastAttemptQuery);
            $stmt->bind_param("ss", $username, $ipAddress);
            $stmt->execute();
            $result = $stmt->get_result();
            $lastAttempt = $result->fetch_assoc();
            
            $lockoutEnd = strtotime($lastAttempt['attempted_at']) + $this->lockoutDuration;
            
            if (time() < $lockoutEnd) {
                $remainingTime = $lockoutEnd - time();
                return [
                    'locked' => true,
                    'remaining_seconds' => $remainingTime,
                    'remaining_minutes' => ceil($remainingTime / 60)
                ];
            }
        }
        
        return ['locked' => false];
    }
    
    /**
     * Get remaining login attempts
     */
    public function getRemainingAttempts($username, $ipAddress) {
        $query = "SELECT COUNT(*) as attempts 
                  FROM login_attempts 
                  WHERE (username = ? OR ip_address = ?) 
                  AND success = 0 
                  AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $username, $ipAddress, $this->attemptWindow);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return max(0, $this->maxLoginAttempts - $row['attempts']);
    }
    
    /**
     * Clear failed attempts after successful login
     */
    public function clearFailedAttempts($username, $ipAddress) {
        $query = "DELETE FROM login_attempts 
                  WHERE (username = ? OR ip_address = ?) 
                  AND success = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $username, $ipAddress);
        $stmt->execute();
    }
    
    /**
     * Check if password is common
     */
    private function isCommonPassword($password) {
        $commonPasswords = [
            'password', '12345678', 'qwerty', 'abc123', 'monkey', '1234567890',
            'letmein', 'trustno1', 'dragon', 'baseball', 'iloveyou', 'master',
            'sunshine', 'ashley', 'bailey', 'passw0rd', 'shadow', '123123',
            'password1', 'password123', 'admin', 'welcome', 'login'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    /**
     * Generate secure random password
     */
    public function generateSecurePassword($length = 12) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }
    
    /**
     * Check password expiry
     */
    public function isPasswordExpired($userId) {
        $query = "SELECT password_updated_at FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['password_updated_at']) {
                $expiryDate = strtotime($row['password_updated_at'] . " +{$this->passwordExpiryDays} days");
                return time() > $expiryDate;
            }
        }
        
        return false;
    }
    
    /**
     * Get login statistics
     */
    public function getLoginStatistics($userId = null, $days = 30) {
        if ($userId) {
            $query = "SELECT 
                        DATE(attempted_at) as date,
                        COUNT(*) as total_attempts,
                        SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful,
                        SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
                      FROM login_attempts la
                      JOIN users u ON la.username = u.username
                      WHERE u.id = ?
                      AND attempted_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                      GROUP BY DATE(attempted_at)
                      ORDER BY date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $userId, $days);
        } else {
            $query = "SELECT 
                        DATE(attempted_at) as date,
                        COUNT(*) as total_attempts,
                        SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful,
                        SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
                      FROM login_attempts
                      WHERE attempted_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                      GROUP BY DATE(attempted_at)
                      ORDER BY date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $days);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        return $stats;
    }
}

?>
