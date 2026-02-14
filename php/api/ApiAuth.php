<?php
/**
 * API AUTHENTICATION
 * JWT-based authentication for API requests
 */

class ApiAuth {
    private static $secretKey = 'RMU_MEDICAL_SECRET_KEY_2026'; // Change this in production!
    
    /**
     * Generate JWT token
     */
    public static function generateToken($userId, $userRole, $expiresIn = 86400) {
        $issuedAt = time();
        $expire = $issuedAt + $expiresIn; // Token valid for specified time
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $userId,
            'role' => $userRole
        ];
        
        return self::encode($payload);
    }
    
    /**
     * Validate JWT token
     */
    public static function validateToken($token) {
        try {
            $payload = self::decode($token);
            
            // Check if token is expired
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return ['success' => false, 'message' => 'Token expired'];
            }
            
            return [
                'success' => true,
                'user_id' => $payload['user_id'],
                'role' => $payload['role']
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Invalid token'];
        }
    }
    
    /**
     * Authenticate current request
     */
    public static function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader)) {
            return ['success' => false, 'message' => 'Authorization header missing'];
        }
        
        // Extract token from "Bearer TOKEN" format
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            return self::validateToken($token);
        }
        
        return ['success' => false, 'message' => 'Invalid authorization format'];
    }
    
    /**
     * Simple JWT encoding
     */
    private static function encode($payload) {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('SHA256', "$headerEncoded.$payloadEncoded", self::$secretKey, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Simple JWT decoding
     */
    private static function decode($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
        
        // Verify signature
        $signature = hash_hmac('SHA256', "$headerEncoded.$payloadEncoded", self::$secretKey, true);
        $signatureCheck = self::base64UrlEncode($signature);
        
        if ($signatureCheck !== $signatureEncoded) {
            throw new Exception('Invalid signature');
        }
        
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        return $payload;
    }
    
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
