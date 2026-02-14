<?php
/**
 * REST API ROUTER
 * Central routing for all API endpoints
 */

// Enable CORS for mobile apps
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../db_conn.php';
require_once 'ApiResponse.php';
require_once 'ApiAuth.php';

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? $_GET['path'] : '';
$pathParts = explode('/', trim($path, '/'));

// Authentication (except for login/register)
$publicEndpoints = ['login', 'register'];
if (!in_array($pathParts[0] ?? '', $publicEndpoints)) {
    $auth = ApiAuth::authenticate();
    if (!$auth['success']) {
        ApiResponse::error($auth['message'], 401);
    }
    $userId = $auth['user_id'];
    $userRole = $auth['role'];
}

// Route requests
try {
    switch ($pathParts[0] ?? '') {
        // Authentication
        case 'login':
            require_once 'endpoints/auth.php';
            handleLogin();
            break;
            
        case 'register':
            require_once 'endpoints/auth.php';
            handleRegister();
            break;
            
        case 'logout':
            require_once 'endpoints/auth.php';
            handleLogout($userId);
            break;
            
        // User profile
        case 'profile':
            require_once 'endpoints/profile.php';
            handleProfile($method, $userId);
            break;
            
        // Appointments
        case 'appointments':
            require_once 'endpoints/appointments.php';
            handleAppointments($method, $userId, $userRole, $pathParts[1] ?? null);
            break;
            
        // Notifications
        case 'notifications':
            require_once 'endpoints/notifications.php';
            handleNotifications($method, $userId, $pathParts[1] ?? null);
            break;
            
        // Medical records
        case 'medical-records':
            require_once 'endpoints/medical_records.php';
            handleMedicalRecords($method, $userId, $userRole, $pathParts[1] ?? null);
            break;
            
        // Prescriptions
        case 'prescriptions':
            require_once 'endpoints/prescriptions.php';
            handlePrescriptions($method, $userId, $userRole, $pathParts[1] ?? null);
            break;
            
        // Doctors list
        case 'doctors':
            require_once 'endpoints/doctors.php';
            handleDoctors($method);
            break;
            
        default:
            ApiResponse::error('Endpoint not found', 404);
    }
} catch (Exception $e) {
    ApiResponse::error('Server error: ' . $e->getMessage(), 500);
}
