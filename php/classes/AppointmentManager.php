<?php
// ===================================
// APPOINTMENT MANAGER CLASS
// Handles appointment rescheduling and management
// ===================================

class AppointmentManager {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Request appointment reschedule
     */
    public function requestReschedule($appointmentId, $userId, $newDate, $newTime, $reason = '') {
        // Get appointment details
        $query = "SELECT a.*, p.user_id as patient_user_id 
                  FROM appointments a
                  JOIN patients p ON a.patient_id = p.P_ID
                  WHERE a.appointment_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Appointment not found'];
        }
        
        $appointment = $result->fetch_assoc();
        
        // Verify user owns this appointment
        if ($appointment['patient_user_id'] != $userId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        // Check if appointment can be rescheduled
        if (in_array($appointment['status'], ['Completed', 'Cancelled'])) {
            return ['success' => false, 'message' => 'This appointment cannot be rescheduled'];
        }
        
        // Check if new date/time is in the future
        $newDateTime = $newDate . ' ' . $newTime;
        if (strtotime($newDateTime) <= time()) {
            return ['success' => false, 'message' => 'New date/time must be in the future'];
        }
        
        // Check if doctor is available at new time
        if (!$this->isDoctorAvailable($appointment['doctor_id'], $newDate, $newTime)) {
            return ['success' => false, 'message' => 'Doctor is not available at the requested time'];
        }
        
        // Store old date/time
        $oldDate = $appointment['appointment_date'];
        $oldTime = $appointment['appointment_time'];
        
        // Update appointment
        $updateQuery = "UPDATE appointments 
                        SET appointment_date = ?, 
                            appointment_time = ?,
                            status = 'Rescheduled',
                            notes = CONCAT(IFNULL(notes, ''), '\nRescheduled: ', ?)
                        WHERE appointment_id = ?";
        
        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bind_param("sssi", $newDate, $newTime, $reason, $appointmentId);
        
        if ($stmt->execute()) {
            // Log the reschedule
            $this->logReschedule($appointmentId, $oldDate, $oldTime, $newDate, $newTime, $reason);
            
            // Notify doctor
            $this->notifyDoctor($appointment['doctor_id'], $appointmentId, $newDate, $newTime);
            
            // Send confirmation email
            $this->sendRescheduleConfirmation($appointment['patient_id'], $appointmentId, $newDate, $newTime);
            
            return [
                'success' => true,
                'message' => 'Appointment rescheduled successfully',
                'new_date' => $newDate,
                'new_time' => $newTime
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to reschedule appointment'];
        }
    }
    
    /**
     * Cancel appointment
     */
    public function cancelAppointment($appointmentId, $userId, $reason = '') {
        // Get appointment details
        $query = "SELECT a.*, p.user_id as patient_user_id 
                  FROM appointments a
                  JOIN patients p ON a.patient_id = p.P_ID
                  WHERE a.appointment_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Appointment not found'];
        }
        
        $appointment = $result->fetch_assoc();
        
        // Verify user owns this appointment
        if ($appointment['patient_user_id'] != $userId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        // Check if already cancelled or completed
        if (in_array($appointment['status'], ['Completed', 'Cancelled'])) {
            return ['success' => false, 'message' => 'This appointment is already ' . strtolower($appointment['status'])];
        }
        
        // Update appointment status
        $updateQuery = "UPDATE appointments 
                        SET status = 'Cancelled',
                            notes = CONCAT(IFNULL(notes, ''), '\nCancelled: ', ?)
                        WHERE appointment_id = ?";
        
        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bind_param("si", $reason, $appointmentId);
        
        if ($stmt->execute()) {
            // Notify doctor
            $this->notifyDoctorCancellation($appointment['doctor_id'], $appointmentId);
            
            return ['success' => true, 'message' => 'Appointment cancelled successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to cancel appointment'];
        }
    }
    
    /**
     * Get available time slots
     */
    public function getAvailableSlots($doctorId, $date) {
        // Define working hours
        $workingHours = [
            '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
            '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30'
        ];
        
        // Get booked appointments for this doctor on this date
        $query = "SELECT appointment_time 
                  FROM appointments 
                  WHERE doctor_id = ? 
                  AND appointment_date = ? 
                  AND status NOT IN ('Cancelled', 'Completed')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $doctorId, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookedSlots = [];
        while ($row = $result->fetch_assoc()) {
            $bookedSlots[] = substr($row['appointment_time'], 0, 5); // Get HH:MM
        }
        
        // Filter out booked slots
        $availableSlots = array_diff($workingHours, $bookedSlots);
        
        return array_values($availableSlots);
    }
    
    /**
     * Check if doctor is available
     */
    private function isDoctorAvailable($doctorId, $date, $time) {
        $availableSlots = $this->getAvailableSlots($doctorId, $date);
        $timeSlot = substr($time, 0, 5); // Get HH:MM
        
        return in_array($timeSlot, $availableSlots);
    }
    
    /**
     * Log reschedule
     */
    private function logReschedule($appointmentId, $oldDate, $oldTime, $newDate, $newTime, $reason) {
        $query = "INSERT INTO appointment_reschedule_log 
                  (appointment_id, old_date, old_time, new_date, new_time, reason) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        // Create table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS appointment_reschedule_log (
            log_id INT PRIMARY KEY AUTO_INCREMENT,
            appointment_id INT NOT NULL,
            old_date DATE NOT NULL,
            old_time TIME NOT NULL,
            new_date DATE NOT NULL,
            new_time TIME NOT NULL,
            reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        mysqli_query($this->conn, $createTable);
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("isssss", $appointmentId, $oldDate, $oldTime, $newDate, $newTime, $reason);
        $stmt->execute();
    }
    
    /**
     * Notify doctor of reschedule
     */
    private function notifyDoctor($doctorId, $appointmentId, $newDate, $newTime) {
        $query = "SELECT user_id FROM doctors WHERE D_ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $doctor = $result->fetch_assoc();
        
        if ($doctor) {
            require_once __DIR__ . '/NotificationManager.php';
            $notificationManager = new NotificationManager($this->conn, null);
            
            $notificationManager->createNotification(
                $doctor['user_id'],
                'appointment',
                'Appointment Rescheduled',
                "Appointment #$appointmentId has been rescheduled to $newDate at $newTime",
                'normal'
            );
        }
    }
    
    /**
     * Notify doctor of cancellation
     */
    private function notifyDoctorCancellation($doctorId, $appointmentId) {
        $query = "SELECT user_id FROM doctors WHERE D_ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $doctor = $result->fetch_assoc();
        
        if ($doctor) {
            require_once __DIR__ . '/NotificationManager.php';
            $notificationManager = new NotificationManager($this->conn, null);
            
            $notificationManager->createNotification(
                $doctor['user_id'],
                'appointment',
                'Appointment Cancelled',
                "Appointment #$appointmentId has been cancelled by the patient",
                'normal'
            );
        }
    }
    
    /**
     * Send reschedule confirmation
     */
    private function sendRescheduleConfirmation($patientId, $appointmentId, $newDate, $newTime) {
        // Get patient and appointment details
        $query = "SELECT p.P_Name, p.P_Email, d.D_Name 
                  FROM patients p
                  JOIN appointments a ON p.P_ID = a.patient_id
                  JOIN doctors d ON a.doctor_id = d.D_ID
                  WHERE a.appointment_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if ($data) {
            require_once __DIR__ . '/EmailService.php';
            $emailService = new EmailService($this->conn);
            
            $details = [
                'date' => date('F j, Y', strtotime($newDate)),
                'time' => date('g:i A', strtotime($newTime)),
                'doctor' => $data['D_Name'],
                'type' => 'Rescheduled Appointment'
            ];
            
            $emailService->sendAppointmentConfirmation(
                $data['P_Email'],
                $data['P_Name'],
                $details
            );
        }
    }
    
    /**
     * Get patient appointments
     */
    public function getPatientAppointments($patientId, $status = null) {
        $query = "SELECT a.*, d.D_Name as doctor_name, d.D_Specialization 
                  FROM appointments a
                  JOIN doctors d ON a.doctor_id = d.D_ID
                  WHERE a.patient_id = ?";
        
        $params = [$patientId];
        $types = 'i';
        
        if ($status) {
            $query .= " AND a.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        
        return $appointments;
    }
}

?>
