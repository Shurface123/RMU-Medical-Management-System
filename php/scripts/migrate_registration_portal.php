<?php
// ============================================================
// MIGRATION: Advanced Registration Portal — Phase 2  (v2)
// Compatible with MySQL 5.7+ and MariaDB 10.2+
// Safe to re-run repeatedly — checks existence before each ALTER.
// ============================================================
require_once dirname(__DIR__) . '/db_conn.php';

$log    = [];
$errors = [];

// ── Helpers ──────────────────────────────────────────────────

function run_sql($conn, $label, $sql) {
    global $log, $errors;
    if (@mysqli_query($conn, $sql)) {
        $log[]    = "✅ $label";
    } else {
        $errors[] = "❌ $label — " . mysqli_error($conn);
    }
}

/**
 * Returns true if $column already exists in $table.
 */
function col_exists($conn, $table, $column) {
    $db = 'rmu_medical_sickbay';
    $r  = mysqli_query($conn,
        "SELECT COUNT(*) AS n FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$table'
         AND COLUMN_NAME='$column'");
    return ($r && (int)mysqli_fetch_assoc($r)['n'] > 0);
}

/**
 * Returns true if $table exists.
 */
function table_exists($conn, $table) {
    $db = 'rmu_medical_sickbay';
    $r  = mysqli_query($conn,
        "SELECT COUNT(*) AS n FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$table'");
    return ($r && (int)mysqli_fetch_assoc($r)['n'] > 0);
}

/**
 * Add a column only if it does not already exist.
 */
function add_col($conn, $table, $column, $definition) {
    if (col_exists($conn, $table, $column)) {
        global $log;
        $log[] = "⏭ $table.$column — already exists, skipped";
        return;
    }
    run_sql($conn, "ALTER $table ADD $column",
        "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
}

// ─────────────────────────────────────────────────────────────
// 1. ALTER users — add missing columns only
// ─────────────────────────────────────────────────────────────

add_col($conn, 'users', 'patient_type',
    "ENUM('student','staff') DEFAULT NULL COMMENT 'Only for patient role' AFTER user_role");

add_col($conn, 'users', 'is_verified',
    "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");

add_col($conn, 'users', 'profile_image',
    "VARCHAR(255) DEFAULT NULL AFTER is_verified");

add_col($conn, 'users', 'account_status',
    "ENUM('active','inactive','suspended','pending_verification')
     NOT NULL DEFAULT 'pending_verification' AFTER profile_image");

add_col($conn, 'users', 'updated_at',
    "TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at");

add_col($conn, 'users', 'last_login_at',
    "TIMESTAMP NULL DEFAULT NULL");

add_col($conn, 'users', 'gender',
    "ENUM('Male','Female','Other') DEFAULT NULL");

add_col($conn, 'users', 'date_of_birth',
    "DATE DEFAULT NULL");

// Sync account_status from existing is_active / is_verified values
run_sql($conn, 'users — sync account_status for existing rows',
    "UPDATE users SET account_status =
       CASE
         WHEN is_verified = 1 AND is_active = 1 THEN 'active'
         WHEN is_active  = 0 AND is_verified = 0 THEN 'pending_verification'
         WHEN is_active  = 0                     THEN 'inactive'
         ELSE 'active'
       END
     WHERE account_status = 'pending_verification'");

// ─────────────────────────────────────────────────────────────
// 2. email_verifications
// ─────────────────────────────────────────────────────────────
if (!table_exists($conn, 'email_verifications')) {
    run_sql($conn, 'CREATE email_verifications',
        "CREATE TABLE `email_verifications` (
            `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `verification_id`   VARCHAR(64)  NOT NULL UNIQUE
                                COMMENT 'UUID token sent in verification email',
            `user_id`           INT UNSIGNED DEFAULT NULL
                                COMMENT 'NULL until user row created (pre-reg OTP)',
            `email`             VARCHAR(150) NOT NULL,
            `otp_code`          VARCHAR(255) NOT NULL
                                COMMENT 'bcrypt hash of 6-digit OTP',
            `otp_expires_at`    DATETIME     NOT NULL,
            `attempts_made`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `is_used`           TINYINT(1)  NOT NULL DEFAULT 0,
            `verification_type` ENUM('registration','password_reset','email_change')
                                NOT NULL DEFAULT 'registration',
            `created_at`        TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_ev_email` (`email`),
            INDEX `idx_ev_user`  (`user_id`),
            INDEX `idx_ev_type`  (`verification_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} else {
    $log[] = "⏭ email_verifications — already exists, skipped";
}

// ─────────────────────────────────────────────────────────────
// 3. registration_sessions
// ─────────────────────────────────────────────────────────────
if (!table_exists($conn, 'registration_sessions')) {
    run_sql($conn, 'CREATE registration_sessions',
        "CREATE TABLE `registration_sessions` (
            `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `session_token`  VARCHAR(64) NOT NULL UNIQUE
                             COMMENT 'Hex token held in hidden field or cookie',
            `email`          VARCHAR(150) NOT NULL,
            `role`           VARCHAR(50)  NOT NULL,
            `step_reached`   TINYINT UNSIGNED NOT NULL DEFAULT 1
                             COMMENT '1=details 2=otp_sent 3=verified',
            `temp_data`      JSON NOT NULL
                             COMMENT 'Serialised form fields — no plain password',
            `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `expires_at`     DATETIME  NOT NULL,
            INDEX `idx_rs_email`   (`email`),
            INDEX `idx_rs_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} else {
    $log[] = "⏭ registration_sessions — already exists, skipped";
}

// ─────────────────────────────────────────────────────────────
// 4. role_permissions
// ─────────────────────────────────────────────────────────────
if (!table_exists($conn, 'role_permissions')) {
    run_sql($conn, 'CREATE role_permissions',
        "CREATE TABLE `role_permissions` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `role_name`   VARCHAR(50)  NOT NULL,
            `module_name` VARCHAR(100) NOT NULL,
            `can_view`    TINYINT(1)  NOT NULL DEFAULT 0,
            `can_create`  TINYINT(1)  NOT NULL DEFAULT 0,
            `can_edit`    TINYINT(1)  NOT NULL DEFAULT 0,
            `can_delete`  TINYINT(1)  NOT NULL DEFAULT 0,
            `updated_by`  INT UNSIGNED DEFAULT NULL
                          COMMENT 'Admin user_id who last changed this row',
            `updated_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
                          ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_rp_role_module` (`role_name`, `module_name`),
            INDEX `idx_rp_role` (`role_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed default permissions
    $seeds = [
        ['admin',          'registration_portal', 1, 1, 1, 1],
        ['doctor',         'registration_portal', 0, 0, 0, 0],
        ['patient',        'registration_portal', 1, 0, 0, 0],
        ['nurse',          'registration_portal', 0, 0, 0, 0],
        ['pharmacist',     'registration_portal', 0, 0, 0, 0],
        ['lab_technician', 'registration_portal', 0, 0, 0, 0],
        ['staff',          'registration_portal', 0, 0, 0, 0],
    ];
    foreach ($seeds as $r) {
        run_sql($conn, "role_permissions seed [{$r[0]}/{$r[1]}]",
            "INSERT IGNORE INTO role_permissions
             (role_name, module_name, can_view, can_create, can_edit, can_delete)
             VALUES ('{$r[0]}','{$r[1]}',{$r[2]},{$r[3]},{$r[4]},{$r[5]})");
    }
} else {
    $log[] = "⏭ role_permissions — already exists, skipped";
}

// ─────────────────────────────────────────────────────────────
// 5. login_attempts
// ─────────────────────────────────────────────────────────────
if (!table_exists($conn, 'login_attempts')) {
    run_sql($conn, 'CREATE login_attempts',
        "CREATE TABLE `login_attempts` (
            `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `email`           VARCHAR(150) DEFAULT NULL,
            `ip_address`      VARCHAR(45)  NOT NULL,
            `user_agent`      TEXT         DEFAULT NULL,
            `attempt_time`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `was_successful`  TINYINT(1)  NOT NULL DEFAULT 0,
            `failure_reason`  VARCHAR(255) DEFAULT NULL
                              COMMENT 'e.g. wrong_password, account_locked, role_mismatch',
            INDEX `idx_la_email` (`email`),
            INDEX `idx_la_ip`    (`ip_address`),
            INDEX `idx_la_time`  (`attempt_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} else {
    $log[] = "⏭ login_attempts — already exists, skipped";
}

// ─────────────────────────────────────────────────────────────
// 6. recaptcha_logs
// ─────────────────────────────────────────────────────────────
if (!table_exists($conn, 'recaptcha_logs')) {
    run_sql($conn, 'CREATE recaptcha_logs',
        "CREATE TABLE `recaptcha_logs` (
            `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `email`            VARCHAR(150) DEFAULT NULL,
            `ip_address`       VARCHAR(45)  NOT NULL,
            `recaptcha_score`  DECIMAL(4,3) DEFAULT NULL
                               COMMENT 'Google reCAPTCHA v3 score 0.0-1.0',
            `action`           VARCHAR(100) NOT NULL DEFAULT 'register',
            `passed`           TINYINT(1)  NOT NULL DEFAULT 0,
            `created_at`       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_rl_email` (`email`),
            INDEX `idx_rl_ip`    (`ip_address`),
            INDEX `idx_rl_time`  (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} else {
    $log[] = "⏭ recaptcha_logs — already exists, skipped";
}

// ─────────────────────────────────────────────────────────────
// 7. password_history
// ─────────────────────────────────────────────────────────────
if (!table_exists($conn, 'password_history')) {
    run_sql($conn, 'CREATE password_history',
        "CREATE TABLE `password_history` (
            `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id`         INT UNSIGNED NOT NULL,
            `hashed_password` VARCHAR(255) NOT NULL,
            `created_at`      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_ph_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} else {
    $log[] = "⏭ password_history — already exists, skipped";
}

// ─────────────────────────────────────────────────────────────
// 8. user_registration_audit
// ─────────────────────────────────────────────────────────────
if (!table_exists($conn, 'user_registration_audit')) {
    run_sql($conn, 'CREATE user_registration_audit',
        "CREATE TABLE `user_registration_audit` (
            `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `audit_id`     VARCHAR(32)  NOT NULL UNIQUE
                           COMMENT 'URA-{uniqid}',
            `user_id`      INT UNSIGNED DEFAULT NULL,
            `action`       ENUM(
                             'registered','otp_sent','otp_verified','otp_failed',
                             'approved','rejected','suspended','reactivated',
                             'password_reset','email_changed'
                           ) NOT NULL,
            `performed_by` VARCHAR(50)  NOT NULL DEFAULT 'self'
                           COMMENT 'self or admin user_id as string',
            `ip_address`   VARCHAR(45)  DEFAULT NULL,
            `device_info`  TEXT         DEFAULT NULL,
            `notes`        TEXT         DEFAULT NULL,
            `created_at`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_ura_user`   (`user_id`),
            INDEX `idx_ura_action` (`action`),
            INDEX `idx_ura_time`   (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} else {
    $log[] = "⏭ user_registration_audit — already exists, skipped";
}

// ─────────────────────────────────────────────────────────────
// 9. system_email_config
// ─────────────────────────────────────────────────────────────
if (!table_exists($conn, 'system_email_config')) {
    run_sql($conn, 'CREATE system_email_config',
        "CREATE TABLE `system_email_config` (
            `id`            TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `smtp_host`     VARCHAR(255) NOT NULL DEFAULT 'smtp.gmail.com',
            `smtp_port`     SMALLINT     NOT NULL DEFAULT 587,
            `smtp_username` VARCHAR(255) NOT NULL DEFAULT '',
            `smtp_password` TEXT         NOT NULL
                            COMMENT 'AES-256-CBC encrypted with APP_SECRET',
            `encryption`    ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
            `from_email`    VARCHAR(255) NOT NULL DEFAULT '',
            `from_name`     VARCHAR(150) NOT NULL DEFAULT 'RMU Medical Sickbay',
            `is_active`     TINYINT(1)  NOT NULL DEFAULT 1,
            `updated_by`    INT UNSIGNED DEFAULT NULL,
            `updated_at`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed from db_conn.php defaults
    run_sql($conn, 'system_email_config — seed',
        "INSERT INTO system_email_config
         (smtp_host, smtp_port, smtp_username, smtp_password,
          encryption, from_email, from_name, is_active)
         VALUES (
           'smtp.gmail.com', 587,
           'sickbay.text@st.rmu.edu.gh',
           AES_ENCRYPT('hqrr kkat ruqg nutf',
                        SHA2('RMU_SICKBAY_2025_SECRET',256)),
           'tls',
           'sickbay.text@st.rmu.edu.gh',
           'RMU Medical Sickbay', 1
         )");
} else {
    $log[] = "⏭ system_email_config — already exists, skipped";
}

// ─────────────────────────────────────────────────────────────
// Output Report
// ─────────────────────────────────────────────────────────────
$total  = count($log) + count($errors);
$passed = count($log);
$failed = count($errors);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Migration Report — Registration Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#2F80ED,#56CCF2);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;}
        .card{background:#fff;border-radius:24px;padding:3rem;max-width:780px;width:100%;box-shadow:0 20px 60px rgba(47,128,237,.25);}
        h1{font-size:2.4rem;color:#1A2035;margin-bottom:.3rem;}
        .subtitle{color:#5A6A85;font-size:1.3rem;margin-bottom:2rem;border-bottom:1px solid #E1EAFF;padding-bottom:1.5rem;}
        .section{margin-bottom:2rem;}
        .section h2{font-size:1.5rem;font-weight:700;color:#1C3A6B;margin-bottom:1rem;}
        .entry{font-size:1.25rem;padding:.65rem 1.2rem;border-radius:8px;margin-bottom:.45rem;font-weight:500;}
        .ok   {background:#EAFAF1;color:#1E8449;}
        .skip {background:#EBF3FF;color:#2F80ED;}
        .err  {background:#FDEDEC;color:#C0392B;}
        .summary{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:2rem;}
        .badge{padding:1.5rem;border-radius:16px;font-size:1.6rem;font-weight:700;text-align:center;color:#fff;}
        .badge.green {background:linear-gradient(135deg,#27AE60,#58D68D);}
        .badge.red   {background:linear-gradient(135deg,#E74C3C,#EC7063);}
        .note{margin-top:2rem;font-size:1.2rem;color:#5A6A85;background:#F4F8FF;border-radius:12px;padding:1.2rem;border-left:4px solid #2F80ED;}
    </style>
</head>
<body>
<div class="card">
    <h1><i class="fas fa-database"></i> Migration Report</h1>
    <p class="subtitle">Advanced Registration Portal — Schema v2.0 &nbsp;|&nbsp; <?= date('Y-m-d H:i:s') ?></p>

    <?php if (!empty($errors)): ?>
    <div class="section">
        <h2><i class="fas fa-times-circle" style="color:#E74C3C"></i> Errors (<?= count($errors) ?>)</h2>
        <?php foreach ($errors as $e): ?>
            <div class="entry err"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="section">
        <h2><i class="fas fa-list-check" style="color:#27AE60"></i> Operation Log (<?= count($log) ?>)</h2>
        <?php foreach ($log as $l): ?>
            <div class="entry <?= str_starts_with($l,'⏭') ? 'skip' : 'ok' ?>">
                <?= htmlspecialchars($l) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="summary">
        <div class="badge green"><i class="fas fa-check-circle"></i> <?= $passed ?> Passed</div>
        <div class="badge <?= $failed > 0 ? 'red' : 'green' ?>">
            <i class="fas fa-<?= $failed>0 ? 'xmark-circle' : 'check' ?>-circle"></i>
            <?= $failed ?> Failed
        </div>
    </div>

    <div class="note">
        <strong><i class="fas fa-lock"></i> Security Reminder:</strong>
        Delete or restrict access to this file (<code>migrate_registration_portal.php</code>)
        after confirming the migration is complete. <br>
        <strong>Next Step:</strong> Proceed to Phase 3 — Advanced Registration Portal frontend.
    </div>
</div>
</body>
</html>
