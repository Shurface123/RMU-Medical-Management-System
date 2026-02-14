<?php
session_start();
require_once '../db_conn.php';
require_once '../classes/AppointmentManager.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$appointmentManager = new AppointmentManager($conn);
$userId = $_SESSION['user_id'];

// Get patient ID
$query = "SELECT P_ID FROM patients WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$patientId = $patient['P_ID'];

$message = '';
$error = '';

// Handle reschedule request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reschedule') {
        $appointmentId = $_POST['appointment_id'];
        $newDate = $_POST['new_date'];
        $newTime = $_POST['new_time'];
        $reason = $_POST['reason'] ?? '';
        
        $result = $appointmentManager->requestReschedule($appointmentId, $userId, $newDate, $newTime, $reason);
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($_POST['action'] === 'cancel') {
        $appointmentId = $_POST['appointment_id'];
        $reason = $_POST['reason'] ?? '';
        
        $result = $appointmentManager->cancelAppointment($appointmentId, $userId, $reason);
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Get patient appointments
$appointments = $appointmentManager->getPatientAppointments($patientId);

// Get available slots for AJAX
if (isset($_GET['get_slots'])) {
    $doctorId = $_GET['doctor_id'];
    $date = $_GET['date'];
    $slots = $appointmentManager->getAvailableSlots($doctorId, $date);
    header('Content-Type: application/json');
    echo json_encode($slots);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - RMU Medical Sickbay</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .appointment-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
        }
        
        .appointment-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .appointment-details h3 {
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .appointment-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .appointment-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .appointment-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-scheduled {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rescheduled {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            color: #2c3e50;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .time-slots {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .time-slot {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .time-slot:hover {
            border-color: #3498db;
            background: #e7f3ff;
        }
        
        .time-slot.selected {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> My Appointments</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($appointments)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h2>No Appointments</h2>
                <p>You don't have any appointments scheduled.</p>
                <a href="patient_dashboard.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> Book Appointment
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($appointments as $appointment): ?>
                <div class="appointment-card">
                    <div class="appointment-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    
                    <div class="appointment-details">
                        <h3>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h3>
                        <div class="appointment-info">
                            <span>
                                <i class="fas fa-calendar"></i>
                                <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                            </span>
                            <span>
                                <i class="fas fa-clock"></i>
                                <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                            </span>
                            <span>
                                <i class="fas fa-user-md"></i>
                                <?php echo htmlspecialchars($appointment['D_Specialization']); ?>
                            </span>
                            <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                <?php echo $appointment['status']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!in_array($appointment['status'], ['Completed', 'Cancelled'])): ?>
                        <div class="appointment-actions">
                            <button class="btn btn-primary" onclick="openRescheduleModal(<?php echo $appointment['appointment_id']; ?>, <?php echo $appointment['doctor_id']; ?>)">
                                <i class="fas fa-calendar-alt"></i> Reschedule
                            </button>
                            <button class="btn btn-danger" onclick="openCancelModal(<?php echo $appointment['appointment_id']; ?>)">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="patient_dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reschedule Appointment</h2>
                <button class="close-modal" onclick="closeModal('rescheduleModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" name="appointment_id" id="reschedule_appointment_id">
                <input type="hidden" name="doctor_id" id="reschedule_doctor_id">
                
                <div class="form-group">
                    <label>New Date</label>
                    <input type="date" name="new_date" id="new_date" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required onchange="loadAvailableSlots()">
                </div>
                
                <div class="form-group">
                    <label>Available Time Slots</label>
                    <div id="timeSlotsContainer" class="time-slots">
                        <p style="grid-column: 1/-1; text-align: center; color: #7f8c8d;">Select a date to see available slots</p>
                    </div>
                    <input type="hidden" name="new_time" id="selected_time" required>
                </div>
                
                <div class="form-group">
                    <label>Reason for Rescheduling</label>
                    <textarea name="reason" rows="3" placeholder="Optional"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-check"></i> Confirm Reschedule
                </button>
            </form>
        </div>
    </div>
    
    <!-- Cancel Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cancel Appointment</h2>
                <button class="close-modal" onclick="closeModal('cancelModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="appointment_id" id="cancel_appointment_id">
                
                <div class="form-group">
                    <label>Reason for Cancellation</label>
                    <textarea name="reason" rows="3" placeholder="Optional"></textarea>
                </div>
                
                <p style="color: #e74c3c; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> Are you sure you want to cancel this appointment?
                </p>
                
                <button type="submit" class="btn btn-danger" style="width: 100%;">
                    <i class="fas fa-times"></i> Confirm Cancellation
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function openRescheduleModal(appointmentId, doctorId) {
            document.getElementById('reschedule_appointment_id').value = appointmentId;
            document.getElementById('reschedule_doctor_id').value = doctorId;
            document.getElementById('rescheduleModal').classList.add('active');
        }
        
        function openCancelModal(appointmentId) {
            document.getElementById('cancel_appointment_id').value = appointmentId;
            document.getElementById('cancelModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        async function loadAvailableSlots() {
            const date = document.getElementById('new_date').value;
            const doctorId = document.getElementById('reschedule_doctor_id').value;
            const container = document.getElementById('timeSlotsContainer');
            
            if (!date) return;
            
            container.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">Loading...</p>';
            
            try {
                const response = await fetch(`?get_slots=1&doctor_id=${doctorId}&date=${date}`);
                const slots = await response.json();
                
                if (slots.length === 0) {
                    container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #e74c3c;">No available slots for this date</p>';
                    return;
                }
                
                container.innerHTML = '';
                slots.forEach(slot => {
                    const slotDiv = document.createElement('div');
                    slotDiv.className = 'time-slot';
                    slotDiv.textContent = slot;
                    slotDiv.onclick = () => selectTimeSlot(slot, slotDiv);
                    container.appendChild(slotDiv);
                });
            } catch (error) {
                container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #e74c3c;">Error loading slots</p>';
            }
        }
        
        function selectTimeSlot(time, element) {
            // Remove previous selection
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Select new slot
            element.classList.add('selected');
            document.getElementById('selected_time').value = time + ':00';
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
