<?php
/**
 * FinanceNotifier.php
 * Helper to dispatch alerts across modules.
 */

class FinanceNotifier {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Dispatch to finance modules (logs to finance_notifications & broadcasts to finance staff)
     */
    public function notifyFinance($type, $title, $message, $priority='normal', $module='', $record_id=0, $sender_id=null) {
        $sid = $sender_id ?? ($_SESSION['user_id'] ?? 0);
        $q = "INSERT INTO finance_notifications (recipient_id, sender_id, type, title, message, priority, action_url, related_module, related_record_id, created_at) 
              VALUES (0, ?, ?, ?, ?, ?, '', ?, ?, NOW())";
        $stmt = $this->conn->prepare($q);
        if ($stmt) {
            $stmt->bind_param("isssssi", $sid, $type, $title, $message, $priority, $module, $record_id);
            $stmt->execute();
        }

        // Broadcast to general notifications
        require_once __DIR__ . '/NotificationManager.php';
        $nm = new NotificationManager($this->conn);
        $nm->broadcastToRole('finance_manager', $title, $message, $priority);
        // We only broadcast urgent/high to officers to avoid spam
        if ($priority === 'urgent' || $priority === 'high') {
            $nm->broadcastToRole('finance_officer', $title, $message, $priority);
        }
    }

    /**
     * Dispatch an urgent alert to Admin Dashboard
     */
    public function notifyAdmin($title, $message, $priority='normal') {
        require_once __DIR__ . '/NotificationManager.php';
        $nm = new NotificationManager($this->conn);
        $nm->broadcastToRole('admin', $title, $message, $priority);
    }

    /**
     * Dispatch to specific Patient Dashboard
     */
    public function notifyPatient($patient_user_id, $title, $message, $priority='normal') {
        if (!$patient_user_id) return;
        require_once __DIR__ . '/NotificationManager.php';
        $nm = new NotificationManager($this->conn);
        $nm->createNotification($patient_user_id, 'billing', $title, $message, $priority);
    }
}
?>
