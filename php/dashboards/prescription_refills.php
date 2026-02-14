<?php
session_start();
require_once '../db_conn.php';
require_once '../classes/PrescriptionRefillManager.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$refillManager = new PrescriptionRefillManager($conn);
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

// Handle refill request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'request_refill') {
        $prescriptionId = $_POST['prescription_id'];
        $notes = $_POST['notes'] ?? '';
        
        $result = $refillManager->requestRefill($prescriptionId, $patientId, $notes);
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Get patient's prescriptions
$prescriptionsQuery = "SELECT p.*, d.D_Name as doctor_name, 
                       (SELECT COUNT(*) FROM prescription_refills pr 
                        WHERE pr.prescription_id = p.prescription_id 
                        AND pr.status = 'Pending') as pending_refills
                       FROM prescriptions p
                       JOIN doctors d ON p.doctor_id = d.D_ID
                       WHERE p.patient_id = ? 
                       AND p.status NOT IN ('Cancelled', 'Expired')
                       ORDER BY p.prescription_date DESC";

$stmt = $conn->prepare($prescriptionsQuery);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get refill history
$refillHistory = $refillManager->getPatientRefills($patientId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Refills - RMU Medical Sickbay</title>
    
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
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 12px 24px;
            background: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: #3498db;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
        
        .prescription-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .prescription-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .prescription-header h3 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .prescription-meta {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .prescription-items {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .prescription-items h4 {
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .medication-item {
            padding: 10px;
            background: white;
            border-radius: 6px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .refill-info {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            font-size: 14px;
        }
        
        .refill-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
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
        
        .btn-primary:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-denied {
            background: #f8d7da;
            color: #721c24;
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
        
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .history-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
            background: white;
            border-radius: 10px;
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
            <h1><i class="fas fa-pills"></i> Prescription Refills</h1>
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
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('prescriptions')">
                <i class="fas fa-prescription-bottle"></i> My Prescriptions
            </button>
            <button class="tab" onclick="switchTab('history')">
                <i class="fas fa-history"></i> Refill History
            </button>
        </div>
        
        <!-- Prescriptions Tab -->
        <div id="prescriptions" class="tab-content active">
            <?php if (empty($prescriptions)): ?>
                <div class="empty-state">
                    <i class="fas fa-prescription-bottle"></i>
                    <h2>No Active Prescriptions</h2>
                    <p>You don't have any active prescriptions.</p>
                </div>
            <?php else: ?>
                <?php foreach ($prescriptions as $prescription): ?>
                    <div class="prescription-card">
                        <div class="prescription-header">
                            <div>
                                <h3>Prescription #<?php echo $prescription['prescription_id']; ?></h3>
                                <div class="prescription-meta">
                                    <i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?>
                                    | <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($prescription['prescription_date'])); ?>
                                </div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($prescription['status']); ?>">
                                <?php echo $prescription['status']; ?>
                            </span>
                        </div>
                        
                        <?php
                        // Get prescription items
                        $itemsQuery = "SELECT * FROM prescription_items WHERE prescription_id = ?";
                        $stmt = $conn->prepare($itemsQuery);
                        $stmt->bind_param("i", $prescription['prescription_id']);
                        $stmt->execute();
                        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        ?>
                        
                        <?php if (!empty($items)): ?>
                            <div class="prescription-items">
                                <h4>Medications:</h4>
                                <?php foreach ($items as $item): ?>
                                    <div class="medication-item">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['medicine_name']); ?></strong>
                                            <div style="font-size: 13px; color: #7f8c8d;">
                                                <?php echo htmlspecialchars($item['dosage']); ?> - <?php echo htmlspecialchars($item['instructions']); ?>
                                            </div>
                                        </div>
                                        <div style="color: #7f8c8d; font-size: 13px;">
                                            Qty: <?php echo $item['quantity']; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="refill-info">
                            <span>
                                <i class="fas fa-sync"></i>
                                <strong>Refills Available:</strong> <?php echo $prescription['refills_allowed']; ?>
                            </span>
                            <?php if ($prescription['pending_refills'] > 0): ?>
                                <span style="color: #f39c12;">
                                    <i class="fas fa-clock"></i>
                                    <strong>Pending Request</strong>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <button 
                            class="btn btn-primary" 
                            onclick="openRefillModal(<?php echo $prescription['prescription_id']; ?>)"
                            <?php echo ($prescription['refills_allowed'] <= 0 || $prescription['pending_refills'] > 0) ? 'disabled' : ''; ?>
                        >
                            <i class="fas fa-redo"></i> Request Refill
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- History Tab -->
        <div id="history" class="tab-content">
            <?php if (empty($refillHistory)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h2>No Refill History</h2>
                    <p>You haven't requested any refills yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($refillHistory as $refill): ?>
                    <div class="history-item">
                        <div class="history-header">
                            <div>
                                <strong>Prescription #<?php echo $refill['prescription_id']; ?></strong>
                                <div style="font-size: 13px; color: #7f8c8d; margin-top: 5px;">
                                    Dr. <?php echo htmlspecialchars($refill['doctor_name']); ?>
                                </div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($refill['status']); ?>">
                                <?php echo $refill['status']; ?>
                            </span>
                        </div>
                        
                        <div style="margin: 10px 0; font-size: 14px; color: #7f8c8d;">
                            <i class="fas fa-calendar"></i> Requested: <?php echo date('M j, Y g:i A', strtotime($refill['requested_at'])); ?>
                        </div>
                        
                        <?php if ($refill['request_notes']): ?>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; margin: 10px 0; font-size: 14px;">
                                <strong>Your Notes:</strong> <?php echo htmlspecialchars($refill['request_notes']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($refill['status'] !== 'Pending' && $refill['approval_notes']): ?>
                            <div style="background: #e7f3ff; padding: 10px; border-radius: 6px; margin: 10px 0; font-size: 14px;">
                                <strong>Doctor's Response:</strong> <?php echo htmlspecialchars($refill['approval_notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="patient_dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Refill Request Modal -->
    <div id="refillModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Request Prescription Refill</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="request_refill">
                <input type="hidden" name="prescription_id" id="refill_prescription_id">
                
                <div class="form-group">
                    <label>Additional Notes (Optional)</label>
                    <textarea name="notes" rows="4" placeholder="Any additional information for your doctor..."></textarea>
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> Your doctor will review your refill request and you'll be notified once it's processed.
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Submit Refill Request
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function openRefillModal(prescriptionId) {
            document.getElementById('refill_prescription_id').value = prescriptionId;
            document.getElementById('refillModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('refillModal').classList.remove('active');
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
