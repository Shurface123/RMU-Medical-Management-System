<?php
// ===================================
// FILE UPLOAD MANAGER CLASS
// Handles secure file uploads for medical records
// ===================================

class FileUploadManager {
    private $conn;
    private $uploadDir;
    private $maxFileSize = 10485760; // 10MB
    private $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    private $allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    public function __construct($db_connection, $uploadDir = null) {
        $this->conn = $db_connection;
        $this->uploadDir = $uploadDir ?? __DIR__ . '/../../uploads/medical_records/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Upload medical record attachment
     */
    public function uploadMedicalAttachment($recordId, $file, $description = '', $uploadedBy = null) {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'medical_' . $recordId . '_' . uniqid() . '.' . $extension;
        $filepath = $this->uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'message' => 'Failed to upload file'];
        }
        
        // Save to database
        $query = "INSERT INTO medical_attachments 
                  (record_id, file_name, file_path, file_type, file_size, description, uploaded_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $fileType = $file['type'];
        $fileSize = $file['size'];
        $originalName = $file['name'];
        
        $stmt->bind_param("isssiis", $recordId, $originalName, $filepath, $fileType, $fileSize, $description, $uploadedBy);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'attachment_id' => $stmt->insert_id,
                'filename' => $filename,
                'path' => $filepath
            ];
        } else {
            // Delete file if database insert fails
            unlink($filepath);
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $maxSizeMB = $this->maxFileSize / 1048576;
            return ['valid' => false, 'error' => "File size exceeds maximum of {$maxSizeMB}MB"];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['valid' => false, 'error' => 'File type not allowed. Allowed types: ' . implode(', ', $this->allowedExtensions)];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            return ['valid' => false, 'error' => 'Invalid file type'];
        }
        
        // Check for malicious content (basic)
        if ($this->containsMaliciousContent($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'File contains potentially malicious content'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Check for malicious content
     */
    private function containsMaliciousContent($filepath) {
        // Read first 1KB of file
        $handle = fopen($filepath, 'rb');
        $content = fread($handle, 1024);
        fclose($handle);
        
        // Check for common malicious patterns
        $maliciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/base64_decode/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/passthru\s*\(/i'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get attachment
     */
    public function getAttachment($attachmentId) {
        $query = "SELECT * FROM medical_attachments WHERE attachment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $attachmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get record attachments
     */
    public function getRecordAttachments($recordId) {
        $query = "SELECT * FROM medical_attachments WHERE record_id = ? ORDER BY uploaded_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attachments = [];
        while ($row = $result->fetch_assoc()) {
            $attachments[] = $row;
        }
        
        return $attachments;
    }
    
    /**
     * Download attachment
     */
    public function downloadAttachment($attachmentId, $userId) {
        $attachment = $this->getAttachment($attachmentId);
        
        if (!$attachment) {
            return ['success' => false, 'message' => 'Attachment not found'];
        }
        
        // Check access permissions (implement based on your requirements)
        if (!$this->canAccessAttachment($userId, $attachment['record_id'])) {
            return ['success' => false, 'message' => 'Access denied'];
        }
        
        $filepath = $attachment['file_path'];
        
        if (!file_exists($filepath)) {
            return ['success' => false, 'message' => 'File not found'];
        }
        
        // Set headers for download
        header('Content-Type: ' . $attachment['file_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');
        
        // Output file
        readfile($filepath);
        exit;
    }
    
    /**
     * Delete attachment
     */
    public function deleteAttachment($attachmentId, $userId) {
        $attachment = $this->getAttachment($attachmentId);
        
        if (!$attachment) {
            return ['success' => false, 'message' => 'Attachment not found'];
        }
        
        // Check permissions
        if (!$this->canDeleteAttachment($userId, $attachment['record_id'])) {
            return ['success' => false, 'message' => 'Access denied'];
        }
        
        // Delete file
        if (file_exists($attachment['file_path'])) {
            unlink($attachment['file_path']);
        }
        
        // Delete from database
        $query = "DELETE FROM medical_attachments WHERE attachment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $attachmentId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Attachment deleted'];
        } else {
            return ['success' => false, 'message' => 'Database error'];
        }
    }
    
    /**
     * Check if user can access attachment
     */
    private function canAccessAttachment($userId, $recordId) {
        // Get user role
        $query = "SELECT role FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Admin and doctors can access all
        if (in_array($user['role'], ['admin', 'doctor'])) {
            return true;
        }
        
        // Patients can only access their own records
        if ($user['role'] === 'patient') {
            $recordQuery = "SELECT patient_id FROM medical_records WHERE record_id = ?";
            $stmt = $this->conn->prepare($recordQuery);
            $stmt->bind_param("i", $recordId);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result->fetch_assoc();
            
            // Check if this patient owns the record
            $patientQuery = "SELECT P_ID FROM patients WHERE user_id = ?";
            $stmt = $this->conn->prepare($patientQuery);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $patient = $result->fetch_assoc();
            
            return $patient && $record && $patient['P_ID'] == $record['patient_id'];
        }
        
        return false;
    }
    
    /**
     * Check if user can delete attachment
     */
    private function canDeleteAttachment($userId, $recordId) {
        // Only admin and the doctor who created the record can delete
        $query = "SELECT role FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user['role'] === 'admin') {
            return true;
        }
        
        if ($user['role'] === 'doctor') {
            $recordQuery = "SELECT doctor_id FROM medical_records WHERE record_id = ?";
            $stmt = $this->conn->prepare($recordQuery);
            $stmt->bind_param("i", $recordId);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result->fetch_assoc();
            
            // Check if this doctor created the record
            $doctorQuery = "SELECT D_ID FROM doctors WHERE user_id = ?";
            $stmt = $this->conn->prepare($doctorQuery);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $doctor = $result->fetch_assoc();
            
            return $doctor && $record && $doctor['D_ID'] == $record['doctor_id'];
        }
        
        return false;
    }
    
    /**
     * Get upload statistics
     */
    public function getUploadStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_files,
                    SUM(file_size) as total_size,
                    file_type,
                    COUNT(*) as count_by_type
                  FROM medical_attachments
                  GROUP BY file_type";
        
        $result = mysqli_query($this->conn, $query);
        
        $stats = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $stats[] = $row;
        }
        
        return $stats;
    }
}

?>
