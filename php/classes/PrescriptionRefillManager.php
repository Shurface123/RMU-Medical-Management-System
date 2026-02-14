<?php
// ===================================
// PRESCRIPTION REFILL MANAGER CLASS
// Handles prescription refill requests
// ===================================

class PrescriptionRefillManager {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Request prescription refill
     */
    public function requestRefill($prescriptionId, $patientId, $notes = '') {
        // Verify prescription belongs to patient
        $query = "SELECT p.*, pi.medicine_name, pi.dosage, pi.quantity 
                  FROM prescriptions p
                  JOIN prescription_items pi ON p.prescription_id = pi.prescription_id
                  WHERE p.prescription_id = ? AND p.patient_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $prescriptionId, $patientId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Prescription not found'];
        }
        
        $prescription = $result->fetch_assoc();
        
        // Check if prescription is still valid
        if ($prescription['status'] === 'Cancelled' || $prescription['status'] === 'Expired') {
            return ['success' => false, 'message' => 'Prescription is no longer valid'];
        }
        
        // Check if prescription allows refills
        if ($prescription['refills_allowed'] <= 0) {
            return ['success' => false, 'message' => 'No refills remaining'];
        }
        
        // Check if there's already a pending refill request
        $checkQuery = "SELECT * FROM prescription_refills 
                       WHERE prescription_id = ? 
                       AND status = 'Pending'";
        
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->bind_param("i", $prescriptionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'You already have a pending refill request'];
        }
        
        // Create refill request
        $insertQuery = "INSERT INTO prescription_refills 
                        (prescription_id, requested_by, request_notes, status) 
                        VALUES (?, ?, ?, 'Pending')";
        
        $stmt = $this->conn->prepare($insertQuery);
        $stmt->bind_param("iis", $prescriptionId, $patientId, $notes);
        
        if ($stmt->execute()) {
            $refillId = $stmt->insert_id;
            
            // Send notification to doctor
            $this->notifyDoctor($prescription['doctor_id'], $prescriptionId, $refillId);
            
            return [
                'success' => true,
                'refill_id' => $refillId,
                'message' => 'Refill request submitted successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to submit refill request'];
        }
    }
    
    /**
     * Approve refill request
     */
    public function approveRefill($refillId, $doctorId, $notes = '') {
        // Get refill details
        $query = "SELECT pr.*, p.doctor_id, p.refills_allowed 
                  FROM prescription_refills pr
                  JOIN prescriptions p ON pr.prescription_id = p.prescription_id
                  WHERE pr.refill_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $refillId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Refill request not found'];
        }
        
        $refill = $result->fetch_assoc();
        
        // Verify doctor authorization
        if ($refill['doctor_id'] != $doctorId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        // Check if already processed
        if ($refill['status'] !== 'Pending') {
            return ['success' => false, 'message' => 'Refill request already processed'];
        }
        
        // Update refill status
        $updateQuery = "UPDATE prescription_refills 
                        SET status = 'Approved', 
                            approved_by = ?, 
                            approved_at = NOW(),
                            approval_notes = ?
                        WHERE refill_id = ?";
        
        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bind_param("isi", $doctorId, $notes, $refillId);
        $stmt->execute();
        
        // Decrement refills_allowed
        $decrementQuery = "UPDATE prescriptions 
                           SET refills_allowed = refills_allowed - 1 
                           WHERE prescription_id = ?";
        
        $stmt = $this->conn->prepare($decrementQuery);
        $stmt->bind_param("i", $refill['prescription_id']);
        $stmt->execute();
        
        // Notify patient
        $this->notifyPatient($refill['requested_by'], $refillId, 'approved');
        
        return ['success' => true, 'message' => 'Refill approved'];
    }
    
    /**
     * Deny refill request
     */
    public function denyRefill($refillId, $doctorId, $reason = '') {
        // Get refill details
        $query = "SELECT pr.*, p.doctor_id 
                  FROM prescription_refills pr
                  JOIN prescriptions p ON pr.prescription_id = p.prescription_id
                  WHERE pr.refill_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $refillId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Refill request not found'];
        }
        
        $refill = $result->fetch_assoc();
        
        // Verify doctor authorization
        if ($refill['doctor_id'] != $doctorId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        // Update refill status
        $updateQuery = "UPDATE prescription_refills 
                        SET status = 'Denied', 
                            approved_by = ?, 
                            approved_at = NOW(),
                            approval_notes = ?
                        WHERE refill_id = ?";
        
        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bind_param("isi", $doctorId, $reason, $refillId);
        
        if ($stmt->execute()) {
            // Notify patient
            $this->notifyPatient($refill['requested_by'], $refillId, 'denied', $reason);
            
            return ['success' => true, 'message' => 'Refill denied'];
        } else {
            return ['success' => false, 'message' => 'Failed to deny refill'];
        }
    }
    
    /**
     * Get patient refill requests
     */
    public function getPatientRefills($patientId, $status = null) {
        $query = "SELECT pr.*, p.prescription_date, d.D_Name as doctor_name
                  FROM prescription_refills pr
                  JOIN prescriptions p ON pr.prescription_id = p.prescription_id
                  JOIN doctors d ON p.doctor_id = d.D_ID
                  WHERE p.patient_id = ?";
        
        $params = [$patientId];
        $types = 'i';
        
        if ($status) {
            $query .= " AND pr.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $query .= " ORDER BY pr.requested_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $refills = [];
        while ($row = $result->fetch_assoc()) {
            $refills[] = $row;
        }
        
        return $refills;
    }
    
    /**
     * Get doctor refill requests
     */
    public function getDoctorRefills($doctorId, $status = 'Pending') {
        $query = "SELECT pr.*, p.prescription_date, pat.P_Name as patient_name
                  FROM prescription_refills pr
                  JOIN prescriptions p ON pr.prescription_id = p.prescription_id
                  JOIN patients pat ON p.patient_id = pat.P_ID
                  WHERE p.doctor_id = ? AND pr.status = ?
                  ORDER BY pr.requested_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $doctorId, $status);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $refills = [];
        while ($row = $result->fetch_assoc()) {
            $refills[] = $row;
        }
        
        return $refills;
    }
    
    /**
     * Notify doctor of refill request
     */
    private function notifyDoctor($doctorId, $prescriptionId, $refillId) {
        // Get doctor user_id
        $query = "SELECT user_id, D_Email FROM doctors WHERE D_ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $doctor = $result->fetch_assoc();
        
        if ($doctor) {
            // Create notification
            require_once __DIR__ . '/NotificationManager.php';
            $notificationManager = new NotificationManager($this->conn, null);
            
            $notificationManager->createNotification(
                $doctor['user_id'],
                'prescription',
                'New Prescription Refill Request',
                "A patient has requested a refill for prescription #$prescriptionId",
                'normal'
            );
        }
    }
    
    /**
     * Notify patient of refill decision
     */
    private function notifyPatient($patientId, $refillId, $decision, $reason = '') {
        // Get patient user_id
        $query = "SELECT user_id, P_Email FROM patients WHERE P_ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();
        
        if ($patient) {
            require_once __DIR__ . '/NotificationManager.php';
            $notificationManager = new NotificationManager($this->conn, null);
            
            $title = $decision === 'approved' ? 'Prescription Refill Approved' : 'Prescription Refill Denied';
            $message = $decision === 'approved' 
                ? "Your prescription refill request has been approved. You can pick it up at the pharmacy."
                : "Your prescription refill request has been denied. Reason: $reason";
            
            $notificationManager->createNotification(
                $patient['user_id'],
                'prescription',
                $title,
                $message,
                'high'
            );
        }
    }
    
    /**
     * Get refill statistics
     */
    public function getRefillStatistics($doctorId = null, $days = 30) {
        $query = "SELECT 
                    DATE(requested_at) as date,
                    status,
                    COUNT(*) as count
                  FROM prescription_refills pr";
        
        if ($doctorId) {
            $query .= " JOIN prescriptions p ON pr.prescription_id = p.prescription_id
                        WHERE p.doctor_id = ?";
        }
        
        $query .= " AND requested_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DATE(requested_at), status
                    ORDER BY date DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($doctorId) {
            $stmt->bind_param("ii", $doctorId, $days);
        } else {
            $stmt->bind_param("i", $days);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        return $stats;
    }
}

?>
