<?php
// ===================================
// EMAIL SERVICE CLASS
// Handles email sending and queue management
// ===================================

require_once __DIR__ . '/../../vendor/autoload.php'; // For PHPMailer if using Composer
// OR include PHPMailer manually if not using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $conn;
    private $mailer;
    private $config;
    
    // Email configuration
    private $smtp_host = 'smtp.gmail.com'; // Change as needed
    private $smtp_port = 587;
    private $smtp_username = 'sickbay.text@st.rmu.edu.gh'; // Set in constructor or config
    private $smtp_password = 'hqrr kkat ruqg nutf'; // Set in constructor or config
    private $from_email = 'sickbay.text@st.rmu.edu.gh';
    private $from_name = 'RMU Medical Sickbay';
    
    public function __construct($db_connection, $config = []) {
        $this->conn = $db_connection;
        $this->config = $config;
        
        // Override defaults with config
        if (isset($config['smtp_host'])) $this->smtp_host = $config['smtp_host'];
        if (isset($config['smtp_port'])) $this->smtp_port = $config['smtp_port'];
        if (isset($config['smtp_username'])) $this->smtp_username = $config['smtp_username'];
        if (isset($config['smtp_password'])) $this->smtp_password = $config['smtp_password'];
        if (isset($config['from_email'])) $this->from_email = $config['from_email'];
        if (isset($config['from_name'])) $this->from_name = $config['from_name'];
        
        $this->initializeMailer();
    }
    
    /**
     * Initialize PHPMailer
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->smtp_host;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->smtp_username;
            $this->mailer->Password = $this->smtp_password;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->smtp_port;
            
            // Sender
            $this->mailer->setFrom($this->from_email, $this->from_name);
            
            // Encoding
            $this->mailer->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log("Email initialization error: " . $e->getMessage());
        }
    }
    
    /**
     * Send email immediately
     */
    public function sendEmail($to, $subject, $body, $toName = '', $isHTML = true) {
        try {
            // Reset mailer
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($to, $toName);
            
            // Content
            $this->mailer->isHTML($isHTML);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            if ($isHTML) {
                // Generate plain text version
                $this->mailer->AltBody = strip_tags($body);
            }
            
            // Send
            $result = $this->mailer->send();
            
            // Log success
            $this->logEmailSent($to, $subject, 'Sent');
            
            return ['success' => true, 'message' => 'Email sent successfully'];
            
        } catch (Exception $e) {
            // Log failure
            $this->logEmailSent($to, $subject, 'Failed', $e->getMessage());
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Queue email for later sending
     */
    public function queueEmail($to, $subject, $body, $toName = '', $template = null, $priority = 'normal', $scheduledAt = null) {
        $query = "INSERT INTO email_queue (recipient_email, recipient_name, subject, body, template_name, priority, scheduled_at, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssss", $to, $toName, $subject, $body, $template, $priority, $scheduledAt);
        
        if ($stmt->execute()) {
            return ['success' => true, 'email_id' => $stmt->insert_id];
        } else {
            return ['success' => false, 'message' => $stmt->error];
        }
    }
    
    /**
     * Process email queue
     */
    public function processQueue($limit = 10) {
        $query = "SELECT * FROM email_queue 
                  WHERE status = 'Pending' 
                  AND attempts < max_attempts 
                  AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                  ORDER BY priority DESC, created_at ASC 
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $processed = 0;
        $failed = 0;
        
        while ($email = $result->fetch_assoc()) {
            $sendResult = $this->sendEmail(
                $email['recipient_email'],
                $email['subject'],
                $email['body'],
                $email['recipient_name']
            );
            
            if ($sendResult['success']) {
                $this->updateQueueStatus($email['email_id'], 'Sent');
                $processed++;
            } else {
                $this->updateQueueAttempt($email['email_id'], $sendResult['message']);
                $failed++;
            }
        }
        
        return ['processed' => $processed, 'failed' => $failed];
    }
    
    /**
     * Update queue email status
     */
    private function updateQueueStatus($emailId, $status) {
        $query = "UPDATE email_queue SET status = ?, sent_at = NOW() WHERE email_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $emailId);
        $stmt->execute();
    }
    
    /**
     * Update queue email attempt
     */
    private function updateQueueAttempt($emailId, $errorMessage) {
        $query = "UPDATE email_queue 
                  SET attempts = attempts + 1, 
                      error_message = ?,
                      status = CASE WHEN attempts + 1 >= max_attempts THEN 'Failed' ELSE 'Pending' END
                  WHERE email_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $errorMessage, $emailId);
        $stmt->execute();
    }
    
    /**
     * Log email sent
     */
    private function logEmailSent($to, $subject, $status, $error = null) {
        // Optional: Log to database or file
        $logMessage = date('Y-m-d H:i:s') . " - Email to {$to}: {$subject} - Status: {$status}";
        if ($error) {
            $logMessage .= " - Error: {$error}";
        }
        error_log($logMessage);
    }
    
    /**
     * Send registration welcome email
     */
    public function sendRegistrationEmail($userEmail, $userName) {
        $subject = "Welcome to RMU Medical Sickbay";
        $body = $this->getTemplate('registration_welcome', [
            'name' => $userName,
            'email' => $userEmail
        ]);
        
        return $this->queueEmail($userEmail, $subject, $body, $userName, 'registration_welcome');
    }
    
    /**
     * Send appointment confirmation email
     */
    public function sendAppointmentConfirmation($userEmail, $userName, $appointmentDetails) {
        $subject = "Appointment Confirmation - RMU Medical Sickbay";
        $body = $this->getTemplate('appointment_confirmation', [
            'name' => $userName,
            'date' => $appointmentDetails['date'],
            'time' => $appointmentDetails['time'],
            'doctor' => $appointmentDetails['doctor'],
            'type' => $appointmentDetails['type']
        ]);
        
        return $this->queueEmail($userEmail, $subject, $body, $userName, 'appointment_confirmation', 'high');
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($userEmail, $userName, $resetToken) {
        $resetLink = "http://localhost/RMU-Medical-Management-System/php/reset_password.php?token=" . $resetToken;
        
        $subject = "Password Reset Request - RMU Medical Sickbay";
        $body = $this->getTemplate('password_reset', [
            'name' => $userName,
            'reset_link' => $resetLink
        ]);
        
        return $this->queueEmail($userEmail, $subject, $body, $userName, 'password_reset', 'high');
    }
    
    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder($userEmail, $userName, $appointmentDetails) {
        $subject = "Appointment Reminder - Tomorrow";
        $body = $this->getTemplate('appointment_reminder', [
            'name' => $userName,
            'date' => $appointmentDetails['date'],
            'time' => $appointmentDetails['time'],
            'doctor' => $appointmentDetails['doctor']
        ]);
        
        // Schedule for 24 hours before appointment
        $scheduledAt = date('Y-m-d H:i:s', strtotime($appointmentDetails['datetime'] . ' -24 hours'));
        
        return $this->queueEmail($userEmail, $subject, $body, $userName, 'appointment_reminder', 'normal', $scheduledAt);
    }
    
    /**
     * Get email template
     */
    private function getTemplate($templateName, $data = []) {
        $templatePath = __DIR__ . "/../email_templates/{$templateName}.html";
        
        if (file_exists($templatePath)) {
            $template = file_get_contents($templatePath);
            
            // Replace placeholders
            foreach ($data as $key => $value) {
                $template = str_replace('{{' . $key . '}}', $value, $template);
            }
            
            return $template;
        }
        
        // Fallback to basic template
        return $this->getBasicTemplate($templateName, $data);
    }
    
    /**
     * Get basic email template (fallback)
     */
    private function getBasicTemplate($type, $data) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #16a085; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 10px 20px; background: #16a085; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>RMU Medical Sickbay</h1>
                </div>
                <div class="content">';
        
        switch ($type) {
            case 'registration_welcome':
                $html .= "<h2>Welcome, {$data['name']}!</h2>";
                $html .= "<p>Thank you for registering with RMU Medical Sickbay.</p>";
                $html .= "<p>You can now book appointments and access our services.</p>";
                break;
                
            case 'appointment_confirmation':
                $html .= "<h2>Appointment Confirmed</h2>";
                $html .= "<p>Dear {$data['name']},</p>";
                $html .= "<p>Your appointment has been confirmed:</p>";
                $html .= "<ul>";
                $html .= "<li><strong>Date:</strong> {$data['date']}</li>";
                $html .= "<li><strong>Time:</strong> {$data['time']}</li>";
                $html .= "<li><strong>Doctor:</strong> {$data['doctor']}</li>";
                $html .= "<li><strong>Type:</strong> {$data['type']}</li>";
                $html .= "</ul>";
                break;
                
            case 'password_reset':
                $html .= "<h2>Password Reset Request</h2>";
                $html .= "<p>Dear {$data['name']},</p>";
                $html .= "<p>Click the button below to reset your password:</p>";
                $html .= "<p><a href='{$data['reset_link']}' class='button'>Reset Password</a></p>";
                $html .= "<p>This link will expire in 1 hour.</p>";
                break;
                
            case 'appointment_reminder':
                $html .= "<h2>Appointment Reminder</h2>";
                $html .= "<p>Dear {$data['name']},</p>";
                $html .= "<p>This is a reminder of your appointment tomorrow:</p>";
                $html .= "<ul>";
                $html .= "<li><strong>Date:</strong> {$data['date']}</li>";
                $html .= "<li><strong>Time:</strong> {$data['time']}</li>";
                $html .= "<li><strong>Doctor:</strong> {$data['doctor']}</li>";
                $html .= "</ul>";
                break;
        }
        
        $html .= '
                </div>
                <div class="footer">
                    <p>RMU Medical Sickbay | Regional Maritime University, Accra, Ghana</p>
                    <p>Phone: 0502371207 | Email: Sickbay.txt@rmu.edu.gh</p>
                    <p>Emergency Hotline: 153</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}

?>
