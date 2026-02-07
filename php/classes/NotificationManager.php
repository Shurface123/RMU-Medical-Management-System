<?php
// ===================================
// NOTIFICATION MANAGER CLASS
// Handles in-app notifications and coordination
// ===================================

class NotificationManager {
    private $conn;
    private $emailService;
    
    public function __construct($db_connection, $emailService = null) {
        $this->conn = $db_connection;
        $this->emailService = $emailService;
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($userId, $type, $title, $message, $priority = 'normal', $actionUrl = null) {
        $query = "INSERT INTO notifications (user_id, type, title, message, priority, action_url) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("isssss", $userId, $type, $title, $message, $priority, $actionUrl);
        
        if ($stmt->execute()) {
            return ['success' => true, 'notification_id' => $stmt->insert_id];
        } else {
            return ['success' => false, 'message' => $stmt->error];
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $limit = 20, $unreadOnly = false) {
        $query = "SELECT * FROM notifications WHERE user_id = ?";
        
        if ($unreadOnly) {
            $query .= " AND is_read = 0";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        return $notifications;
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId) {
        $query = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $notificationId);
        return $stmt->execute();
    }
    
    /**
     * Mark all user notifications as read
     */
    public function markAllAsRead($userId) {
        $query = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * Delete notification
     */
    public function deleteNotification($notificationId) {
        $query = "DELETE FROM notifications WHERE notification_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $notificationId);
        return $stmt->execute();
    }
    
    /**
     * Send appointment notification (in-app + email)
     */
    public function notifyAppointment($userId, $userEmail, $userName, $appointmentDetails, $notificationType = 'confirmation') {
        // Create in-app notification
        $title = $notificationType === 'confirmation' ? 'Appointment Confirmed' : 'Appointment Reminder';
        $message = "Your appointment with Dr. {$appointmentDetails['doctor']} is scheduled for {$appointmentDetails['date']} at {$appointmentDetails['time']}.";
        
        $this->createNotification($userId, 'appointment', $title, $message, 'high');
        
        // Send email if EmailService is available
        if ($this->emailService) {
            if ($notificationType === 'confirmation') {
                $this->emailService->sendAppointmentConfirmation($userEmail, $userName, $appointmentDetails);
            } else {
                $this->emailService->sendAppointmentReminder($userEmail, $userName, $appointmentDetails);
            }
        }
        
        return ['success' => true];
    }
    
    /**
     * Send prescription notification
     */
    public function notifyPrescription($userId, $userEmail, $userName, $prescriptionDetails) {
        // Create in-app notification
        $title = 'Prescription Ready';
        $message = "Your prescription from Dr. {$prescriptionDetails['doctor']} is ready for pickup.";
        
        $this->createNotification($userId, 'prescription', $title, $message, 'normal');
        
        // Send email
        if ($this->emailService) {
            $subject = "Prescription Ready - RMU Medical Sickbay";
            $body = $this->emailService->getTemplate('prescription_ready', [
                'name' => $userName,
                'doctor' => $prescriptionDetails['doctor'],
                'date' => $prescriptionDetails['date']
            ]);
            
            $this->emailService->queueEmail($userEmail, $subject, $body, $userName, 'prescription_ready');
        }
        
        return ['success' => true];
    }
    
    /**
     * Send test result notification
     */
    public function notifyTestResult($userId, $userEmail, $userName, $testDetails) {
        // Create in-app notification
        $title = 'Test Results Available';
        $message = "Your {$testDetails['test_name']} results are now available.";
        
        $this->createNotification($userId, 'test_result', $title, $message, 'high', '/view_test_results.php?id=' . $testDetails['result_id']);
        
        // Send email
        if ($this->emailService) {
            $subject = "Test Results Available - RMU Medical Sickbay";
            $body = $this->emailService->getTemplate('test_result_ready', [
                'name' => $userName,
                'test_name' => $testDetails['test_name'],
                'date' => $testDetails['date']
            ]);
            
            $this->emailService->queueEmail($userEmail, $subject, $body, $userName, 'test_result_ready', 'high');
        }
        
        return ['success' => true];
    }
    
    /**
     * Send system notification
     */
    public function notifySystem($userId, $title, $message, $priority = 'normal') {
        return $this->createNotification($userId, 'system', $title, $message, $priority);
    }
    
    /**
     * Broadcast notification to all users of a role
     */
    public function broadcastToRole($role, $title, $message, $priority = 'normal') {
        // Get all users with this role
        $query = "SELECT id FROM users WHERE role = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $count = 0;
        while ($user = $result->fetch_assoc()) {
            $this->createNotification($user['id'], 'system', $title, $message, $priority);
            $count++;
        }
        
        return ['success' => true, 'sent_to' => $count];
    }
    
    /**
     * Clean up old read notifications
     */
    public function cleanupOldNotifications($days = 30) {
        $query = "DELETE FROM notifications 
                  WHERE is_read = 1 
                  AND read_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        
        return ['success' => true, 'deleted' => $stmt->affected_rows];
    }
    
    /**
     * Get notification statistics
     */
    public function getStatistics($userId = null) {
        if ($userId) {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                        SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read,
                        type,
                        priority
                      FROM notifications 
                      WHERE user_id = ?
                      GROUP BY type, priority";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $userId);
        } else {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                        SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read,
                        type,
                        priority
                      FROM notifications 
                      GROUP BY type, priority";
            
            $stmt = $this->conn->prepare($query);
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
