<?php
// ================================================================
// API ENDPOINT: /api/profile
// Handles GET (fetch own profile) and PUT (update own profile)
// ================================================================

/**
 * Handle profile requests.
 *
 * @param string $method  HTTP method (GET, PUT, PATCH, etc.)
 * @param int    $userId  Authenticated user ID (from JWT / session)
 */
function handleProfile(string $method, int $userId): void {
    global $conn;

    switch (strtoupper($method)) {

        case 'GET':
            $stmt = $conn->prepare(
                "SELECT u.id, u.name, u.email, u.user_name, u.user_role,
                        u.phone, u.profile_image, u.created_at, u.last_login
                 FROM users u WHERE u.id = ? LIMIT 1"
            );
            if (!$stmt) {
                ApiResponse::error('DB error', 500);
                return;
            }
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                unset($row['password']);
                ApiResponse::success(['profile' => $row]);
            } else {
                ApiResponse::error('User not found', 404);
            }
            break;

        case 'PUT':
        case 'PATCH':
            $body   = json_decode(file_get_contents('php://input'), true) ?? [];
            $allowed = ['name', 'email', 'phone'];
            $sets    = [];
            $types   = '';
            $params  = [];

            foreach ($allowed as $field) {
                if (!empty($body[$field])) {
                    $sets[]   = "$field = ?";
                    $types   .= 's';
                    $params[] = trim($body[$field]);
                }
            }

            if (empty($sets)) {
                ApiResponse::error('No valid fields to update', 400);
                return;
            }

            $types   .= 'i';
            $params[] = $userId;

            $stmt = $conn->prepare("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?");
            if (!$stmt) {
                ApiResponse::error('DB error', 500);
                return;
            }
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                ApiResponse::success(null, 'Profile updated successfully');
            } else {
                ApiResponse::error('Update failed', 500);
            }
            break;

        default:
            ApiResponse::error('Method not allowed', 405);
    }
}
