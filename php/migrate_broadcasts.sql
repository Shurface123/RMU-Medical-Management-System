-- Phase 5: Broadcast Message System Schema

CREATE TABLE IF NOT EXISTS broadcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    priority ENUM('Informational', 'Important', 'Urgent', 'Critical') DEFAULT 'Informational',
    sender_id INT NOT NULL,
    audience_type ENUM('Everyone', 'Role', 'Department', 'Individual') DEFAULT 'Everyone',
    audience_ids JSON DEFAULT NULL, -- Store array of roles, department IDs, or user IDs
    attachment_path VARCHAR(255) DEFAULT NULL,
    requires_acknowledgement TINYINT(1) DEFAULT 0,
    scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    status ENUM('Draft', 'Scheduled', 'Sent', 'Cancelled', 'Expired') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status_schedule (status, scheduled_at),
    KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS broadcast_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    broadcast_id INT NOT NULL,
    recipient_id INT NOT NULL,
    recipient_role VARCHAR(50) NOT NULL,
    delivered_at DATETIME DEFAULT NULL,
    read_at DATETIME DEFAULT NULL,
    acknowledged_at DATETIME DEFAULT NULL,
    FOREIGN KEY (broadcast_id) REFERENCES broadcasts(id) ON DELETE CASCADE,
    KEY idx_recipient (recipient_id, recipient_role),
    KEY idx_read (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
