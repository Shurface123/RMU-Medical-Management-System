<?php
/**
 * AUTHENTICATION ENDPOINTS
 * Login, Register, Logout
 */

function handleLogin() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        ApiResponse::error('Username and password required', 400);
    }
    
    // Find user
    $query = "SELECT * FROM users WHERE user_name = ? OR email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        ApiResponse::error('Invalid credentials', 401);
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        ApiResponse::error('Invalid credentials', 401);
    }
    
    // Check if account is active
    if (isset($user['status']) && $user['status'] !== 'active') {
        ApiResponse::error('Account is inactive', 403);
    }
    
    // Generate token
    $token = ApiAuth::generateToken($user['id'], $user['user_role']);
    
    ApiResponse::success([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['user_name'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['user_role']
        ]
    ], 'Login successful');
}

function handleRegister() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $name = $input['name'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($name)) {
        ApiResponse::error('All fields are required', 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ApiResponse::error('Invalid email format', 400);
    }
    
    if (strlen($password) < 8) {
        ApiResponse::error('Password must be at least 8 characters', 400);
    }
    
    // Check if username or email exists
    $query = "SELECT id FROM users WHERE user_name = ? OR email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        ApiResponse::error('Username or email already exists', 409);
    }
    
    // Create user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $role = 'patient'; // Default role
    
    $query = "INSERT INTO users (user_name, email, password, name, user_role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $username, $email, $hashedPassword, $name, $role);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $token = ApiAuth::generateToken($userId, $role);
        
        ApiResponse::success([
            'token' => $token,
            'user' => [
                'id' => $userId,
                'username' => $username,
                'name' => $name,
                'email' => $email,
                'role' => $role
            ]
        ], 'Registration successful', 201);
    } else {
        ApiResponse::error('Registration failed', 500);
    }
}

function handleLogout($userId) {
    // In a real app, you might want to blacklist the token
    // For now, client-side token removal is sufficient
    ApiResponse::success([], 'Logout successful');
}
