<?php
// ===================================
// TWO-FACTOR AUTHENTICATION CLASS
// Handles 2FA setup and verification
// ===================================

class TwoFactorAuth {
    private $conn;
    private $issuer = 'RMU Medical Sickbay';
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Generate secret key for 2FA
     */
    public function generateSecret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Enable 2FA for user
     */
    public function enableTwoFactor($userId) {
        // Generate secret
        $secret = $this->generateSecret();
        
        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();
        $backupCodesJson = json_encode($backupCodes);
        
        // Check if user already has 2FA
        $checkQuery = "SELECT tfa_id FROM two_factor_auth WHERE user_id = ?";
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing
            $query = "UPDATE two_factor_auth 
                      SET secret_key = ?, backup_codes = ?, is_enabled = 0 
                      WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssi", $secret, $backupCodesJson, $userId);
        } else {
            // Insert new
            $query = "INSERT INTO two_factor_auth (user_id, secret_key, backup_codes, is_enabled) 
                      VALUES (?, ?, ?, 0)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iss", $userId, $secret, $backupCodesJson);
        }
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'secret' => $secret,
                'backup_codes' => $backupCodes,
                'qr_code_url' => $this->getQRCodeUrl($secret, $userId)
            ];
        } else {
            return ['success' => false, 'message' => $stmt->error];
        }
    }
    
    /**
     * Verify and activate 2FA
     */
    public function activateTwoFactor($userId, $code) {
        // Get secret
        $query = "SELECT secret_key FROM two_factor_auth WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => '2FA not set up'];
        }
        
        $row = $result->fetch_assoc();
        $secret = $row['secret_key'];
        
        // Verify code
        if ($this->verifyCode($secret, $code)) {
            // Activate 2FA
            $updateQuery = "UPDATE two_factor_auth SET is_enabled = 1 WHERE user_id = ?";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            return ['success' => true, 'message' => '2FA activated successfully'];
        } else {
            return ['success' => false, 'message' => 'Invalid code'];
        }
    }
    
    /**
     * Verify 2FA code
     */
    public function verifyTwoFactor($userId, $code) {
        // Get secret
        $query = "SELECT secret_key, backup_codes, is_enabled 
                  FROM two_factor_auth 
                  WHERE user_id = ? AND is_enabled = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => '2FA not enabled'];
        }
        
        $row = $result->fetch_assoc();
        $secret = $row['secret_key'];
        $backupCodes = json_decode($row['backup_codes'], true);
        
        // Try regular code first
        if ($this->verifyCode($secret, $code)) {
            return ['success' => true, 'method' => 'totp'];
        }
        
        // Try backup codes
        if (in_array($code, $backupCodes)) {
            // Remove used backup code
            $backupCodes = array_diff($backupCodes, [$code]);
            $backupCodesJson = json_encode(array_values($backupCodes));
            
            $updateQuery = "UPDATE two_factor_auth SET backup_codes = ? WHERE user_id = ?";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->bind_param("si", $backupCodesJson, $userId);
            $stmt->execute();
            
            return ['success' => true, 'method' => 'backup', 'remaining_codes' => count($backupCodes)];
        }
        
        return ['success' => false, 'message' => 'Invalid code'];
    }
    
    /**
     * Verify TOTP code
     */
    private function verifyCode($secret, $code, $window = 1) {
        $timeSlice = floor(time() / 30);
        
        for ($i = -$window; $i <= $window; $i++) {
            $calculatedCode = $this->getTOTP($secret, $timeSlice + $i);
            if ($calculatedCode === $code) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get TOTP code
     */
    private function getTOTP($secret, $timeSlice) {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 decode
     */
    private function base32Decode($secret) {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];
        
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return false;
            }
        }
        
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $base32charsFlipped)) {
                return false;
            }
            
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            
            $eightBits = str_split($x, 8);
            
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }
        
        return $binaryString;
    }
    
    /**
     * Generate backup codes
     */
    private function generateBackupCodes($count = 10) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
    
    /**
     * Get QR code URL
     */
    private function getQRCodeUrl($secret, $userId) {
        // Get user email
        $query = "SELECT email FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $email = $user['email'] ?? 'user@rmu.edu.gh';
        
        $otpauthUrl = "otpauth://totp/{$this->issuer}:{$email}?secret={$secret}&issuer={$this->issuer}";
        
        // Use Google Charts API for QR code
        return "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . urlencode($otpauthUrl);
    }
    
    /**
     * Disable 2FA
     */
    public function disableTwoFactor($userId) {
        $query = "UPDATE two_factor_auth SET is_enabled = 0 WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => '2FA disabled'];
        } else {
            return ['success' => false, 'message' => $stmt->error];
        }
    }
    
    /**
     * Check if user has 2FA enabled
     */
    public function isTwoFactorEnabled($userId) {
        $query = "SELECT is_enabled FROM two_factor_auth WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['is_enabled'] == 1;
        }
        
        return false;
    }
    
    /**
     * Get backup codes
     */
    public function getBackupCodes($userId) {
        $query = "SELECT backup_codes FROM two_factor_auth WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return json_decode($row['backup_codes'], true);
        }
        
        return [];
    }
    
    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes($userId) {
        $backupCodes = $this->generateBackupCodes();
        $backupCodesJson = json_encode($backupCodes);
        
        $query = "UPDATE two_factor_auth SET backup_codes = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $backupCodesJson, $userId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'backup_codes' => $backupCodes];
        } else {
            return ['success' => false, 'message' => $stmt->error];
        }
    }
}

?>
