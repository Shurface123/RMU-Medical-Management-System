<?php
/**
 * migrate_login_system.php — Advanced Login System DB Migration
 * Run once via browser: http://localhost/RMU-Medical-Management-System/php/scripts/migrate_login_system.php
 * Safe to run multiple times — uses IF NOT EXISTS and IF EXISTS checks.
 */
require_once __DIR__ . '/../db_conn.php';

$results = [];
$errors  = [];

function run_sql($conn, $label, $sql) {
    global $results, $errors;
    if (mysqli_query($conn, $sql)) {
        $results[] = "✅ $label";
    } else {
        $errors[] = "❌ $label: " . mysqli_error($conn);
    }
}

function col_exists($conn, $table, $column) {
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$column'");
    $row = mysqli_fetch_assoc($res);
    return (int)$row['cnt'] > 0;
}

// ── 1. login_attempts ──────────────────────────────────────────────────────
run_sql($conn, 'Create login_attempts table', "
CREATE TABLE IF NOT EXISTS login_attempts (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100) NOT NULL DEFAULT '',
    user_id       INT UNSIGNED NULL,
    ip_address    VARCHAR(45) NOT NULL,
    user_agent    VARCHAR(500) NOT NULL DEFAULT '',
    failure_reason VARCHAR(200) NOT NULL DEFAULT '',
    attempted_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at),
    INDEX idx_user_time (user_id, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── 2. remember_me_tokens ─────────────────────────────────────────────────
run_sql($conn, 'Create remember_me_tokens table', "
CREATE TABLE IF NOT EXISTS remember_me_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  VARCHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_token (token_hash),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── 3. two_factor_attempts ────────────────────────────────────────────────
run_sql($conn, 'Create two_factor_attempts table', "
CREATE TABLE IF NOT EXISTS two_factor_attempts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    otp_hash      VARCHAR(255) NOT NULL,
    expires_at    DATETIME NOT NULL,
    attempts_made TINYINT UNSIGNED NOT NULL DEFAULT 0,
    resends_made  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    is_used       TINYINT(1) NOT NULL DEFAULT 0,
    ip_address    VARCHAR(45) NOT NULL DEFAULT '',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── 4. password_resets ────────────────────────────────────────────────────
run_sql($conn, 'Create password_resets table', "
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  VARCHAR(64) NOT NULL,
    is_used     TINYINT(1) NOT NULL DEFAULT 0,
    expires_at  DATETIME NOT NULL,
    ip_address  VARCHAR(45) NOT NULL DEFAULT '',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_token (token_hash),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── 5. password_history ───────────────────────────────────────────────────
run_sql($conn, 'Create password_history table', "
CREATE TABLE IF NOT EXISTS password_history (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── 6. active_sessions ────────────────────────────────────────────────────
run_sql($conn, 'Create active_sessions table', "
CREATE TABLE IF NOT EXISTS active_sessions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id   VARCHAR(128) NOT NULL,
    user_id      INT UNSIGNED NOT NULL,
    user_role    VARCHAR(50) NOT NULL DEFAULT '',
    ip_address   VARCHAR(45) NOT NULL DEFAULT '',
    user_agent   VARCHAR(500) NOT NULL DEFAULT '',
    last_active  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    logged_in_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_session (session_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── 7. login_security_config ─────────────────────────────────────────────
run_sql($conn, 'Create login_security_config table', "
CREATE TABLE IF NOT EXISTS login_security_config (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    max_attempts        TINYINT UNSIGNED NOT NULL DEFAULT 5,
    lockout_minutes     SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    ip_max_attempts     SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    ip_window_minutes   SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    session_timeout     SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    remember_me_days    SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    otp_expiry_minutes  TINYINT UNSIGNED NOT NULL DEFAULT 5,
    reset_expiry_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    enforce_2fa_roles   VARCHAR(500) NOT NULL DEFAULT '',
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Insert default config row if absent
$existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM login_security_config LIMIT 1"));
if (!$existing) {
    run_sql($conn, 'Insert default login_security_config', "
        INSERT INTO login_security_config (id) VALUES (1)
    ");
}

// ── 8. Alter users table — add new columns if missing ─────────────────────
$alter_cols = [
    'locked_until'          => "DATETIME NULL DEFAULT NULL",
    'force_password_change' => "TINYINT(1) NOT NULL DEFAULT 0",
    'two_fa_enabled'        => "TINYINT(1) NOT NULL DEFAULT 0",
    'last_login_at'         => "DATETIME NULL DEFAULT NULL",
    'last_login_ip'         => "VARCHAR(45) NOT NULL DEFAULT ''",
];

foreach ($alter_cols as $col => $def) {
    if (!col_exists($conn, 'users', $col)) {
        run_sql($conn, "Add users.$col", "ALTER TABLE users ADD COLUMN $col $def");
    } else {
        $results[] = "⏭ users.$col already exists — skipped";
    }
}

// ── DONE ──────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login System Migration</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #1C3A6B, #2F80ED); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #fff; border-radius: 16px; padding: 2.5rem; max-width: 700px; width: 95%; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
        h1  { font-size: 1.6rem; color: #2F80ED; margin-bottom: 1.5rem; }
        .ok  { color: #27ae60; font-size: .95rem; margin: 4px 0; }
        .err { color: #e74c3c; font-size: .95rem; margin: 4px 0; }
        .skip{ color: #7f8c8d; font-size: .95rem; margin: 4px 0; }
        .summary { margin-top: 1.5rem; padding: 1rem; border-radius: 10px; font-weight: 600; font-size: 1rem; }
        .all-ok  { background: #edfaf1; color: #27ae60; }
        .has-err { background: #fdedec; color: #e74c3c; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔐 Login System Migration Results</h1>
    <?php foreach ($results as $r): ?>
        <p class="<?= (strpos($r,'⏭') !== false) ? 'skip' : 'ok' ?>"><?= htmlspecialchars($r) ?></p>
    <?php endforeach; ?>
    <?php foreach ($errors as $e): ?>
        <p class="err"><?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>
    <div class="summary <?= empty($errors) ? 'all-ok' : 'has-err' ?>">
        <?= empty($errors) ? '✅ Migration complete — all tables ready.' : '⚠️ Some steps failed — review errors above.' ?>
    </div>
    <p style="margin-top:1rem;font-size:.85rem;color:#7f8c8d;">Delete or restrict access to this file after running in production.</p>
</div>
</body>
</html>
