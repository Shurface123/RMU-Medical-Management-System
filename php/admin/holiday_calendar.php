<?php
session_start();
require_once '../db_conn.php';
require_once '../classes/AuditLogger.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$auditLogger = new AuditLogger($conn);

$message = '';
$error = '';

// Handle holiday actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_holiday':
            $holidayName = $_POST['holiday_name'];
            $holidayDate = $_POST['holiday_date'];
            $holidayType = $_POST['holiday_type'];
            $description = $_POST['description'] ?? '';
            $recurring = isset($_POST['recurring']) ? 1 : 0;
            
            $query = "INSERT INTO holidays (holiday_name, holiday_date, holiday_type, description, recurring, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $userId = $_SESSION['user_id'];
            $stmt->bind_param("sssiii", $holidayName, $holidayDate, $holidayType, $recurring, $userId);
            
            if ($stmt->execute()) {
                $auditLogger->logAction($_SESSION['user_id'], 'holiday_create', 'holidays', $stmt->insert_id, "Added holiday: $holidayName");
                $message = "Holiday added successfully!";
            } else {
                $error = "Failed to add holiday.";
            }
            break;
            
        case 'update_holiday':
            $holidayId = $_POST['holiday_id'];
            $holidayName = $_POST['holiday_name'];
            $holidayDate = $_POST['holiday_date'];
            $holidayType = $_POST['holiday_type'];
            $description = $_POST['description'] ?? '';
            $recurring = isset($_POST['recurring']) ? 1 : 0;
            
            $query = "UPDATE holidays SET holiday_name = ?, holiday_date = ?, holiday_type = ?, description = ?, recurring = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssiii", $holidayName, $holidayDate, $holidayType, $recurring, $holidayId);
            
            if ($stmt->execute()) {
                $auditLogger->logAction($_SESSION['user_id'], 'holiday_update', 'holidays', $holidayId, "Updated holiday: $holidayName");
                $message = "Holiday updated successfully!";
            } else {
                $error = "Failed to update holiday.";
            }
            break;
            
        case 'delete_holiday':
            $holidayId = $_POST['holiday_id'];
            
            $query = "DELETE FROM holidays WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $holidayId);
            
            if ($stmt->execute()) {
                $auditLogger->logAction($_SESSION['user_id'], 'holiday_delete', 'holidays', $holidayId, "Deleted holiday ID: $holidayId");
                $message = "Holiday deleted successfully!";
            } else {
                $error = "Failed to delete holiday.";
            }
            break;
    }
}

// Get current year
$currentYear = date('Y');
$selectedYear = $_GET['year'] ?? $currentYear;

// Get holidays for selected year
$holidaysQuery = "SELECT h.*, u.user_name as created_by_name 
                  FROM holidays h
                  LEFT JOIN users u ON h.created_by = u.id
                  WHERE YEAR(holiday_date) = ? OR recurring = 1
                  ORDER BY holiday_date ASC";
$stmt = $conn->prepare($holidaysQuery);
$stmt->bind_param("i", $selectedYear);
$stmt->execute();
$holidays = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get holiday statistics
$statsQuery = "SELECT 
                COUNT(*) as total_holidays,
                SUM(CASE WHEN holiday_type = 'public' THEN 1 ELSE 0 END) as public_holidays,
                SUM(CASE WHEN holiday_type = 'medical' THEN 1 ELSE 0 END) as medical_holidays,
                SUM(CASE WHEN recurring = 1 THEN 1 ELSE 0 END) as recurring_holidays
               FROM holidays
               WHERE YEAR(holiday_date) = ?";
$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("i", $selectedYear);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Calendar - RMU Medical Sickbay</title>
    
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
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .year-selector {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
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
        
        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .holiday-card {
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid;
            background: #f8f9fa;
            position: relative;
        }
        
        .holiday-card.public {
            border-left-color: #3498db;
        }
        
        .holiday-card.medical {
            border-left-color: #e74c3c;
        }
        
        .holiday-card.other {
            border-left-color: #95a5a6;
        }
        
        .holiday-card h3 {
            color: #2c3e50;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .holiday-card .date {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .holiday-card .description {
            color: #7f8c8d;
            font-size: 13px;
            margin-bottom: 12px;
        }
        
        .holiday-card .actions {
            display: flex;
            gap: 8px;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-public {
            background: #3498db;
            color: white;
        }
        
        .badge-medical {
            background: #e74c3c;
            color: white;
        }
        
        .badge-other {
            background: #95a5a6;
            color: white;
        }
        
        .badge-recurring {
            background: #9b59b6;
            color: white;
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
            text-decoration: none;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
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
            <h1><i class="fas fa-calendar-alt"></i> Holiday Calendar</h1>
            <div class="header-actions">
                <select class="year-selector" onchange="window.location.href='?year=' + this.value">
                    <?php for ($y = $currentYear - 2; $y <= $currentYear + 5; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $selectedYear ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button class="btn btn-success" onclick="openModal('addModal')">
                    <i class="fas fa-plus"></i> Add Holiday
                </button>
            </div>
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
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Holidays</h3>
                <div class="value"><?php echo $stats['total_holidays']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Public Holidays</h3>
                <div class="value"><?php echo $stats['public_holidays']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Medical Holidays</h3>
                <div class="value"><?php echo $stats['medical_holidays']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Recurring</h3>
                <div class="value"><?php echo $stats['recurring_holidays']; ?></div>
            </div>
        </div>
        
        <!-- Holidays -->
        <div class="calendar-container">
            <h2 style="margin-bottom: 20px; color: #2c3e50;">
                <i class="fas fa-calendar-day"></i> Holidays for <?php echo $selectedYear; ?>
            </h2>
            
            <?php if (empty($holidays)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Holidays Defined</h3>
                    <p>Click "Add Holiday" to create your first holiday entry.</p>
                </div>
            <?php else: ?>
                <div class="calendar-grid">
                    <?php foreach ($holidays as $holiday): ?>
                        <div class="holiday-card <?php echo $holiday['holiday_type']; ?>">
                            <h3>
                                <?php echo htmlspecialchars($holiday['holiday_name']); ?>
                                <?php if ($holiday['recurring']): ?>
                                    <i class="fas fa-sync-alt" title="Recurring" style="font-size: 14px; color: #9b59b6;"></i>
                                <?php endif; ?>
                            </h3>
                            <div class="date">
                                <i class="fas fa-calendar"></i> 
                                <?php echo date('F j, Y (l)', strtotime($holiday['holiday_date'])); ?>
                            </div>
                            <?php if ($holiday['description']): ?>
                                <div class="description">
                                    <?php echo htmlspecialchars($holiday['description']); ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin: 10px 0;">
                                <span class="badge badge-<?php echo $holiday['holiday_type']; ?>">
                                    <?php echo ucfirst($holiday['holiday_type']); ?>
                                </span>
                                <?php if ($holiday['recurring']): ?>
                                    <span class="badge badge-recurring">Recurring</span>
                                <?php endif; ?>
                            </div>
                            <div class="actions">
                                <button class="btn btn-warning btn-sm" onclick='editHoliday(<?php echo json_encode($holiday); ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteHoliday(<?php echo $holiday['id']; ?>, '<?php echo htmlspecialchars($holiday['holiday_name']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../home.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Add Holiday Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Holiday</h2>
                <button class="close-modal" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_holiday">
                
                <div class="form-group">
                    <label>Holiday Name *</label>
                    <input type="text" name="holiday_name" required placeholder="e.g., Christmas Day">
                </div>
                
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="holiday_date" required>
                </div>
                
                <div class="form-group">
                    <label>Type *</label>
                    <select name="holiday_type" required>
                        <option value="public">Public Holiday</option>
                        <option value="medical">Medical Facility Closure</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Optional description..."></textarea>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="recurring" id="add_recurring">
                        <label for="add_recurring" style="margin: 0;">Recurring annually</label>
                    </div>
                    <small style="color: #7f8c8d; display: block; margin-top: 5px;">
                        Check this if the holiday occurs every year on the same date
                    </small>
                </div>
                
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-plus"></i> Add Holiday
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Holiday Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Holiday</h2>
                <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_holiday">
                <input type="hidden" name="holiday_id" id="edit_holiday_id">
                
                <div class="form-group">
                    <label>Holiday Name *</label>
                    <input type="text" name="holiday_name" id="edit_holiday_name" required>
                </div>
                
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="holiday_date" id="edit_holiday_date" required>
                </div>
                
                <div class="form-group">
                    <label>Type *</label>
                    <select name="holiday_type" id="edit_holiday_type" required>
                        <option value="public">Public Holiday</option>
                        <option value="medical">Medical Facility Closure</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="recurring" id="edit_recurring">
                        <label for="edit_recurring" style="margin: 0;">Recurring annually</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Update Holiday
                </button>
            </form>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_holiday">
        <input type="hidden" name="holiday_id" id="delete_holiday_id">
    </form>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function editHoliday(holiday) {
            document.getElementById('edit_holiday_id').value = holiday.id;
            document.getElementById('edit_holiday_name').value = holiday.holiday_name;
            document.getElementById('edit_holiday_date').value = holiday.holiday_date;
            document.getElementById('edit_holiday_type').value = holiday.holiday_type;
            document.getElementById('edit_description').value = holiday.description || '';
            document.getElementById('edit_recurring').checked = holiday.recurring == 1;
            openModal('editModal');
        }
        
        function deleteHoliday(holidayId, holidayName) {
            if (confirm(`Are you sure you want to delete "${holidayName}"? This action cannot be undone.`)) {
                document.getElementById('delete_holiday_id').value = holidayId;
                document.getElementById('deleteForm').submit();
            }
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
