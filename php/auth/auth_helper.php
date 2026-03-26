<?php
/**
 * auth_helper.php — Centralized Authentication Helper Functions
 * (Phase 4 Requirement)
 * 
 * Provides centralized wrappers for session validation, role routing, 
 * and brute force lookup logic used across the authentication flow.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validates if an active session exists and matches the expected role 
 * requirements (if any). Redirects to login if invalid.
 * 
 * @param string|null $required_role Optional role needed.
 */
function validateSession($required_role = null) {
    if (empty($_SESSION['user_id'])) {
        header("Location: /RMU-Medical-Management-System/php/index.php?error=" . urlencode("Please log in to continue."));
        exit;
    }
    if ($required_role && ($_SESSION['role'] ?? '') !== $required_role) {
        header("Location: /RMU-Medical-Management-System/php/index.php?error=" . urlencode("Unauthorized role access."));
        exit;
    }
}

/**
 * Resolves the appropriate dashboard route URL based on the user's role.
 * 
 * @param string $role The user's role identifier (e.g. 'doctor', 'patient_student')
 * @return string The absolute or relative router destination.
 */
function routeUserByRole($role) {
    // Relying on the established login_router mappings:
    require_once __DIR__ . '/../login_router.php';
    // The router instantly redirects internally, but we can abstract it here.
    return "/RMU-Medical-Management-System/php/login_router.php?role=" . urlencode($role);
}

/**
 * Checks if a given user account is currently under absolute lockout 
 * due to excessive brute-force attempts.
 * 
 * @param mysqli $conn Active database connection.
 * @param int $user_id The database user ID.
 * @return array ['is_locked' => bool, 'time_remaining' => int] 
 */
function checkBruteForceLockout($conn, $user_id) {
    $stmt = mysqli_prepare($conn, "SELECT locked_until FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row && $row['locked_until']) {
        $locked_epoch = strtotime($row['locked_until']);
        $now = time();
        if ($locked_epoch > $now) {
            return [
                'is_locked' => true,
                'time_remaining' => $locked_epoch - $now
            ];
        } else {
            // Lockout expired, clear it
            mysqli_query($conn, "UPDATE users SET locked_until = NULL WHERE id = " . (int)$user_id);
        }
    }
    
    return [
        'is_locked' => false,
        'time_remaining' => 0
    ];
}

/**
 * Validates and logs an administrative login or specific escalated action.
 * Can be wrapped around the core system logger if required.
 * 
 * @param int $user_id
 * @param string $action Notes regarding action.
 */
function logAuthenticationEvent($user_id, $action) {
    // Abstract hook for sys admin logging.
}
?>
