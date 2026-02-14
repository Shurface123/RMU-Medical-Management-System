<?php
// ===================================
// AUDIT LOGGER CLASS
// Handles audit trail logging
// ===================================

class AuditLogger {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Log an action
     */
    public function log($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
        $ipAddress = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
        $newValuesJson = $newValues ? json_encode($newValues) : null;
        
        $query = "INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ississss", $userId, $action, $tableName, $recordId, $oldValuesJson, $newValuesJson, $ipAddress, $userAgent);
        
        return $stmt->execute();
    }
    
    /**
     * Log user login
     */
    public function logLogin($userId, $success = true) {
        $action = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
        return $this->log($userId, $action, 'users', $userId);
    }
    
    /**
     * Log user logout
     */
    public function logLogout($userId) {
        return $this->log($userId, 'LOGOUT', 'users', $userId);
    }
    
    /**
     * Log record creation
     */
    public function logCreate($userId, $tableName, $recordId, $values) {
        return $this->log($userId, 'CREATE', $tableName, $recordId, null, $values);
    }
    
    /**
     * Log record update
     */
    public function logUpdate($userId, $tableName, $recordId, $oldValues, $newValues) {
        return $this->log($userId, 'UPDATE', $tableName, $recordId, $oldValues, $newValues);
    }
    
    /**
     * Log record deletion
     */
    public function logDelete($userId, $tableName, $recordId, $values) {
        return $this->log($userId, 'DELETE', $tableName, $recordId, $values, null);
    }
    
    /**
     * Log password change
     */
    public function logPasswordChange($userId) {
        return $this->log($userId, 'PASSWORD_CHANGE', 'users', $userId);
    }
    
    /**
     * Log 2FA enable/disable
     */
    public function log2FAChange($userId, $enabled) {
        $action = $enabled ? '2FA_ENABLED' : '2FA_DISABLED';
        return $this->log($userId, $action, 'users', $userId);
    }
    
    /**
     * Log permission change
     */
    public function logPermissionChange($userId, $targetUserId, $oldRole, $newRole) {
        return $this->log($userId, 'PERMISSION_CHANGE', 'users', $targetUserId, 
                         ['role' => $oldRole], ['role' => $newRole]);
    }
    
    /**
     * Get audit logs
     */
    public function getLogs($filters = [], $limit = 100, $offset = 0) {
        $query = "SELECT al.*, u.username, u.email 
                  FROM audit_log al
                  LEFT JOIN users u ON al.user_id = u.id
                  WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Apply filters
        if (isset($filters['user_id'])) {
            $query .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        
        if (isset($filters['action'])) {
            $query .= " AND al.action = ?";
            $params[] = $filters['action'];
            $types .= 's';
        }
        
        if (isset($filters['table_name'])) {
            $query .= " AND al.table_name = ?";
            $params[] = $filters['table_name'];
            $types .= 's';
        }
        
        if (isset($filters['date_from'])) {
            $query .= " AND al.created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (isset($filters['date_to'])) {
            $query .= " AND al.created_at <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        $query .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * Get audit statistics
     */
    public function getStatistics($days = 30) {
        $query = "SELECT 
                    DATE(created_at) as date,
                    action,
                    COUNT(*) as count
                  FROM audit_log
                  WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(created_at), action
                  ORDER BY date DESC, count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        return $stats;
    }
    
    /**
     * Clean up old logs
     */
    public function cleanupOldLogs($days = 365) {
        $query = "DELETE FROM audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        
        return $stmt->affected_rows;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipAddress = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return $ipAddress;
    }
    
    /**
     * Export logs to CSV
     */
    public function exportToCSV($filters = [], $filename = 'audit_log.csv') {
        $logs = $this->getLogs($filters, 10000); // Get up to 10000 records
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Log ID', 'User', 'Action', 'Table', 'Record ID', 'IP Address', 'Date/Time']);
        
        // Data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['log_id'],
                $log['username'] ?? 'Unknown',
                $log['action'],
                $log['table_name'] ?? '',
                $log['record_id'] ?? '',
                $log['ip_address'],
                $log['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
}

?>
