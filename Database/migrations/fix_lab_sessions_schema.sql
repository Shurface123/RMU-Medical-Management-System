-- fix_lab_sessions_schema.sql
-- Repairing lab_technician_sessions table

-- Disable foreign key checks to safely drop/recreate
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS lab_technician_sessions;

CREATE TABLE lab_technician_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    technician_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    device_info TEXT NULL,
    browser VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_active DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_current TINYINT(1) DEFAULT 1,
    UNIQUE KEY (session_token),
    FOREIGN KEY (technician_id) REFERENCES lab_technicians(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
