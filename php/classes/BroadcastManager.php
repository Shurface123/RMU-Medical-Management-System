<?php
/**
 * BroadcastManager Class
 * Handles multi-layered targeting, scheduling, and delivery of broadcasts.
 */
class BroadcastManager {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Create a new broadcast
     * @param array $data Contains subject, body, priority, sender_id, audience_type, audience_ids, scheduled_at, expires_at, requires_acknowledgement
     * @return array Success/failure status and broadcast ID
     */
    public function createBroadcast($data) {
        $subject = $data['subject'];
        $body = $data['body'];
        $priority = $data['priority'] ?? 'Informational';
        $sender_id = $data['sender_id'];
        $audience_type = $data['audience_type'] ?? 'Everyone';
        $audience_ids = isset($data['audience_ids']) ? json_encode($data['audience_ids']) : null;
        $scheduled_at = $data['scheduled_at'] ?? date('Y-m-d H:i:s');
        $expires_at = $data['expires_at'] ?? null;
        $requires_ack = $data['requires_acknowledgement'] ?? 0;
        $attachment = $data['attachment_path'] ?? null;

        $query = "INSERT INTO broadcasts (subject, body, priority, sender_id, audience_type, audience_ids, attachment_path, requires_acknowledgement, scheduled_at, expires_at, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssisssiss", $subject, $body, $priority, $sender_id, $audience_type, $audience_ids, $attachment, $requires_ack, $scheduled_at, $expires_at);
        
        if ($stmt->execute()) {
            $broadcast_id = $stmt->insert_id;
            // If scheduled for now, trigger delivery immediately
            if (strtotime($scheduled_at) <= time()) {
                $this->deliverBroadcast($broadcast_id);
            }
            return ['success' => true, 'broadcast_id' => $broadcast_id];
        } else {
            return ['success' => false, 'message' => $stmt->error];
        }
    }

    /**
     * Resolve audience layers to individual user IDs and populate broadcast_recipients
     * @param int $broadcast_id
     */
    public function deliverBroadcast($broadcast_id) {
        $query = "SELECT * FROM broadcasts WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $broadcast_id);
        $stmt->execute();
        $broadcast = $stmt->get_result()->fetch_assoc();

        if (!$broadcast) return false;

        $target_user_ids = [];
        $audience_type = $broadcast['audience_type'];
        $audience_ids = json_decode($broadcast['audience_ids'], true);

        switch ($audience_type) {
            case 'Everyone':
                $res = mysqli_query($this->conn, "SELECT id, user_role FROM users WHERE is_active = 1");
                while ($u = mysqli_fetch_assoc($res)) {
                    $target_user_ids[] = $u;
                }
                break;

            case 'Role':
                // audience_ids is an array of roles
                $roles = implode("','", array_map(function($r) { return mysqli_real_escape_string($this->conn, $r); }, $audience_ids));
                $res = mysqli_query($this->conn, "SELECT id, user_role FROM users WHERE user_role IN ('$roles') AND is_active = 1");
                while ($u = mysqli_fetch_assoc($res)) {
                    $target_user_ids[] = $u;
                }
                break;

            case 'Department':
                // audience_ids is an array of department names or ward names
                $depts = implode("','", array_map(function($d) { return mysqli_real_escape_string($this->conn, $d); }, $audience_ids));
                // Staff in these departments
                $res = mysqli_query($this->conn, "SELECT user_id AS id, 'staff' as role FROM staff WHERE department IN ('$depts')");
                while ($u = mysqli_fetch_assoc($res)) {
                    // Need to get actual role from users table for recipient_role
                    $u_res = mysqli_query($this->conn, "SELECT id, user_role FROM users WHERE id = {$u['id']} AND is_active = 1");
                    if ($ur = mysqli_fetch_assoc($u_res)) $target_user_ids[] = $ur;
                }
                break;

            case 'Individual':
                // audience_ids is an array of user IDs
                $ids = implode(",", array_map('intval', $audience_ids));
                $res = mysqli_query($this->conn, "SELECT id, user_role FROM users WHERE id IN ($ids) AND is_active = 1");
                while ($u = mysqli_fetch_assoc($res)) {
                    $target_user_ids[] = $u;
                }
                break;
        }

        // Insert recipients
        if (!empty($target_user_ids)) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO broadcast_recipients (broadcast_id, recipient_id, recipient_role) VALUES (?, ?, ?)");
            foreach ($target_user_ids as $u) {
                $stmt->bind_param("iis", $broadcast_id, $u['id'], $u['user_role']);
                $stmt->execute();
            }
        }

        // Update broadcast status
        mysqli_query($this->conn, "UPDATE broadcasts SET status = 'Sent' WHERE id = $broadcast_id");
        return true;
    }

    /**
     * Mark a broadcast as acknowledged by a user
     */
    public function acknowledge($broadcast_id, $user_id) {
        $stmt = $this->conn->prepare("UPDATE broadcast_recipients SET acknowledged_at = NOW() WHERE broadcast_id = ? AND recipient_id = ?");
        $stmt->bind_param("ii", $broadcast_id, $user_id);
        return $stmt->execute();
    }

    /**
     * Mark a broadcast as read by a user
     */
    public function markAsRead($broadcast_id, $user_id) {
        $stmt = $this->conn->prepare("UPDATE broadcast_recipients SET read_at = NOW() WHERE broadcast_id = ? AND recipient_id = ?");
        $stmt->bind_param("ii", $broadcast_id, $user_id);
        return $stmt->execute();
    }

    /**
     * Get active broadcasts for a user (real-time delivery hook)
     */
    public function getActiveForUser($user_id, $role) {
        $query = "SELECT b.*, r.read_at, r.acknowledged_at 
                  FROM broadcasts b
                  JOIN broadcast_recipients r ON b.id = r.broadcast_id
                  WHERE r.recipient_id = ? 
                  AND (b.expires_at IS NULL OR b.expires_at > NOW())
                  AND b.status = 'Sent'
                  ORDER BY b.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
