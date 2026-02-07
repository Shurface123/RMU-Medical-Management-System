<?php
/**
 * Authentication Middleware
 * Provides role-based access control and session validation
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Get current user's role
 * @return string|null
 */
function getCurrentRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Require authentication - redirect to login if not authenticated
 * @param string $redirectUrl URL to redirect after login
 */
function requireAuth($redirectUrl = '../php/index.php') {
    if (!isAuthenticated()) {
        // Store the current page to redirect back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Require specific role - redirect if user doesn't have the role
 * @param string $requiredRole The role required to access the page
 * @param string $redirectUrl URL to redirect if unauthorized
 */
function requireRole($requiredRole, $redirectUrl = '../php/index.php') {
    requireAuth($redirectUrl);
    
    $currentRole = getCurrentRole();
    if ($currentRole !== $requiredRole) {
        // User is authenticated but doesn't have the required role
        header("Location: $redirectUrl?error=Unauthorized access. Please login with appropriate credentials.");
        exit();
    }
}

/**
 * Require any of the specified roles
 * @param array $allowedRoles Array of allowed roles
 * @param string $redirectUrl URL to redirect if unauthorized
 */
function requireAnyRole($allowedRoles, $redirectUrl = '../php/index.php') {
    requireAuth($redirectUrl);
    
    $currentRole = getCurrentRole();
    if (!in_array($currentRole, $allowedRoles)) {
        header("Location: $redirectUrl?error=Unauthorized access. Please login with appropriate credentials.");
        exit();
    }
}

/**
 * Prevent access to multiple dashboards without logout
 * Ensures user can only access their assigned dashboard
 * @param string $expectedRole The role expected for this dashboard
 */
function enforceSingleDashboard($expectedRole) {
    if (!isAuthenticated()) {
        header("Location: ../php/index.php");
        exit();
    }
    
    $currentRole = getCurrentRole();
    
    // If user's role doesn't match the expected role for this dashboard
    if ($currentRole !== $expectedRole) {
        // Redirect to their appropriate dashboard
        switch ($currentRole) {
            case 'admin':
                header("Location: ../php/home.php");
                break;
            case 'doctor':
                header("Location: ../php/dashboards/doctor_dashboard.php");
                break;
            case 'patient':
                header("Location: ../php/dashboards/patient_dashboard.php");
                break;
            case 'pharmacist':
                header("Location: ../php/dashboards/pharmacy_dashboard.php");
                break;
            default:
                header("Location: ../php/index.php?error=Invalid session. Please login again.");
                break;
        }
        exit();
    }
}

/**
 * Check if user has permission to access admin features
 * @return bool
 */
function isAdmin() {
    return isAuthenticated() && getCurrentRole() === 'admin';
}

/**
 * Check if user has permission to access doctor features
 * @return bool
 */
function isDoctor() {
    return isAuthenticated() && getCurrentRole() === 'doctor';
}

/**
 * Check if user has permission to access patient features
 * @return bool
 */
function isPatient() {
    return isAuthenticated() && getCurrentRole() === 'patient';
}

/**
 * Check if user has permission to access pharmacist features
 * @return bool
 */
function isPharmacist() {
    return isAuthenticated() && getCurrentRole() === 'pharmacist';
}

/**
 * Get user information from session
 * @return array|null
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['user_name'] ?? null,
        'name' => $_SESSION['name'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'email' => $_SESSION['email'] ?? null
    ];
}

/**
 * Logout user and destroy session
 * @param string $redirectUrl URL to redirect after logout
 */
function logout($redirectUrl = '../php/index.php') {
    // Destroy all session data
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: $redirectUrl?message=Logged out successfully");
    exit();
}

/**
 * Redirect to appropriate dashboard based on user role
 */
function redirectToDashboard() {
    if (!isAuthenticated()) {
        header("Location: ../php/index.php");
        exit();
    }
    
    $role = getCurrentRole();
    
    switch ($role) {
        case 'admin':
            header("Location: ../php/home.php");
            break;
        case 'doctor':
            header("Location: ../php/dashboards/doctor_dashboard.php");
            break;
        case 'patient':
            header("Location: ../php/dashboards/patient_dashboard.php");
            break;
        case 'pharmacist':
            header("Location: ../php/dashboards/pharmacy_dashboard.php");
            break;
        default:
            header("Location: ../php/index.php?error=Invalid role");
            break;
    }
    exit();
}
?>
