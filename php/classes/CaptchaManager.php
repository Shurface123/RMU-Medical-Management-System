<?php
// ===================================
// CAPTCHA MANAGER CLASS
// Generates and validates CAPTCHA for security
// ===================================

class CaptchaManager {
    private $sessionKey = 'captcha_code';
    private $width = 200;
    private $height = 60;
    private $length = 6;
    
    /**
     * Generate CAPTCHA image
     */
    public function generateCaptcha() {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate random code
        $code = $this->generateCode();
        $_SESSION[$this->sessionKey] = $code;
        
        // Create image
        $image = imagecreatetruecolor($this->width, $this->height);
        
        // Colors
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $lineColor = imagecolorallocate($image, 200, 200, 200);
        $dotColor = imagecolorallocate($image, 150, 150, 150);
        
        // Fill background
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $bgColor);
        
        // Add noise lines
        for ($i = 0; $i < 5; $i++) {
            imageline(
                $image,
                rand(0, $this->width),
                rand(0, $this->height),
                rand(0, $this->width),
                rand(0, $this->height),
                $lineColor
            );
        }
        
        // Add noise dots
        for ($i = 0; $i < 100; $i++) {
            imagesetpixel(
                $image,
                rand(0, $this->width),
                rand(0, $this->height),
                $dotColor
            );
        }
        
        // Add text
        $fontPath = __DIR__ . '/../fonts/arial.ttf';
        
        // Check if font exists, otherwise use built-in font
        if (file_exists($fontPath)) {
            // Use TrueType font
            $fontSize = 24;
            $x = 20;
            
            for ($i = 0; $i < strlen($code); $i++) {
                $angle = rand(-15, 15);
                $y = rand(35, 45);
                
                imagettftext(
                    $image,
                    $fontSize,
                    $angle,
                    $x,
                    $y,
                    $textColor,
                    $fontPath,
                    $code[$i]
                );
                
                $x += 30;
            }
        } else {
            // Use built-in font
            $x = 30;
            $y = 20;
            
            for ($i = 0; $i < strlen($code); $i++) {
                imagestring($image, 5, $x, $y, $code[$i], $textColor);
                $x += 25;
            }
        }
        
        // Output image
        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }
    
    /**
     * Generate random code
     */
    private function generateCode() {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Exclude similar characters
        $code = '';
        
        for ($i = 0; $i < $this->length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
    
    /**
     * Validate CAPTCHA
     */
    public function validateCaptcha($userInput) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[$this->sessionKey])) {
            return false;
        }
        
        $storedCode = $_SESSION[$this->sessionKey];
        unset($_SESSION[$this->sessionKey]); // Clear after validation
        
        return strtoupper($userInput) === strtoupper($storedCode);
    }
    
    /**
     * Check if CAPTCHA is required based on failed attempts
     */
    public function isCaptchaRequired($identifier, $conn) {
        $query = "SELECT failed_attempts FROM login_attempts 
                  WHERE identifier = ? 
                  AND last_attempt > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Require CAPTCHA after 3 failed attempts
            return $row['failed_attempts'] >= 3;
        }
        
        return false;
    }
    
    /**
     * Get CAPTCHA HTML
     */
    public function getCaptchaHTML() {
        $html = '
        <div class="captcha-container" style="margin: 20px 0;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="generate_captcha.php" 
                     alt="CAPTCHA" 
                     id="captcha-image"
                     style="border: 2px solid #ddd; border-radius: 6px; cursor: pointer;"
                     onclick="refreshCaptcha()"
                     title="Click to refresh">
                <button type="button" 
                        onclick="refreshCaptcha()" 
                        style="padding: 10px 15px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <input type="text" 
                   name="captcha" 
                   placeholder="Enter the code above" 
                   required
                   style="width: 100%; padding: 12px; margin-top: 10px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 16px; letter-spacing: 2px; text-transform: uppercase;">
            <p style="font-size: 12px; color: #7f8c8d; margin-top: 5px;">
                <i class="fas fa-info-circle"></i> Enter the characters shown in the image above
            </p>
        </div>
        
        <script>
        function refreshCaptcha() {
            document.getElementById("captcha-image").src = "generate_captcha.php?" + Date.now();
        }
        </script>
        ';
        
        return $html;
    }
}

?>
