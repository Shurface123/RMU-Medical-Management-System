<?php
session_start();
require_once '../db_conn.php';
require_once '../classes/FileUploadManager.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$fileUploadManager = new FileUploadManager($conn);
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

$message = '';
$error = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['medical_file'])) {
    $recordId = $_POST['record_id'];
    $description = $_POST['description'] ?? '';
    
    $result = $fileUploadManager->uploadMedicalAttachment(
        $recordId,
        $_FILES['medical_file'],
        $description,
        $userId
    );
    
    if ($result['success']) {
        $message = 'File uploaded successfully!';
    } else {
        $error = $result['message'];
    }
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $attachmentId = $_GET['delete'];
    $result = $fileUploadManager->deleteAttachment($attachmentId, $userId);
    
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Get medical records based on role
if ($userRole === 'patient') {
    $query = "SELECT P_ID FROM patients WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    $patientId = $patient['P_ID'];
    
    $recordsQuery = "SELECT * FROM medical_records WHERE patient_id = ? ORDER BY record_date DESC";
    $stmt = $conn->prepare($recordsQuery);
    $stmt->bind_param("i", $patientId);
} elseif ($userRole === 'doctor') {
    $query = "SELECT D_ID FROM doctors WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();
    $doctorId = $doctor['D_ID'];
    
    $recordsQuery = "SELECT * FROM medical_records WHERE doctor_id = ? ORDER BY record_date DESC";
    $stmt = $conn->prepare($recordsQuery);
    $stmt->bind_param("i", $doctorId);
} else {
    $recordsQuery = "SELECT * FROM medical_records ORDER BY record_date DESC";
    $stmt = $conn->prepare($recordsQuery);
}

$stmt->execute();
$medicalRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - RMU Medical Sickbay</title>
    
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
        
        .record-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .record-header h3 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .record-meta {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .attachments-section {
            margin-top: 15px;
        }
        
        .attachments-section h4 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .attachment-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .file-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .file-icon.pdf {
            background: #e74c3c;
        }
        
        .file-icon.image {
            background: #3498db;
        }
        
        .file-icon.doc {
            background: #2980b9;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-info strong {
            color: #2c3e50;
            display: block;
            margin-bottom: 3px;
        }
        
        .file-info small {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .file-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .upload-area {
            border: 3px dashed #bdc3c7;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
        }
        
        .upload-area:hover {
            border-color: #3498db;
            background: #e7f3ff;
        }
        
        .upload-area.dragover {
            border-color: #27ae60;
            background: #d4edda;
        }
        
        .upload-area i {
            font-size: 48px;
            color: #bdc3c7;
            margin-bottom: 15px;
        }
        
        .upload-area input[type="file"] {
            display: none;
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
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-folder-open"></i> Medical Records & Attachments</h1>
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
        
        <?php if (empty($medicalRecords)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h2>No Medical Records</h2>
                <p>No medical records found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($medicalRecords as $record): ?>
                <div class="record-card">
                    <div class="record-header">
                        <div>
                            <h3>Medical Record #<?php echo $record['record_id']; ?></h3>
                            <div class="record-meta">
                                <i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($record['record_date'])); ?>
                                | <i class="fas fa-notes-medical"></i> <?php echo htmlspecialchars($record['diagnosis']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($record['symptoms']): ?>
                        <div style="margin-bottom: 15px;">
                            <strong style="color: #2c3e50;">Symptoms:</strong>
                            <p style="color: #7f8c8d; margin-top: 5px;"><?php echo htmlspecialchars($record['symptoms']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="attachments-section">
                        <h4><i class="fas fa-paperclip"></i> Attachments</h4>
                        
                        <?php
                        $attachments = $fileUploadManager->getRecordAttachments($record['record_id']);
                        ?>
                        
                        <?php if (empty($attachments)): ?>
                            <div class="empty-state">
                                <i class="fas fa-file"></i>
                                <p>No attachments for this record</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($attachments as $attachment): ?>
                                <?php
                                $extension = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
                                $iconClass = 'pdf';
                                $icon = 'fa-file-pdf';
                                
                                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                                    $iconClass = 'image';
                                    $icon = 'fa-file-image';
                                } elseif (in_array($extension, ['doc', 'docx'])) {
                                    $iconClass = 'doc';
                                    $icon = 'fa-file-word';
                                }
                                ?>
                                <div class="attachment-item">
                                    <div class="file-icon <?php echo $iconClass; ?>">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="file-info">
                                        <strong><?php echo htmlspecialchars($attachment['file_name']); ?></strong>
                                        <small>
                                            <?php echo number_format($attachment['file_size'] / 1024, 2); ?> KB
                                            | Uploaded: <?php echo date('M j, Y', strtotime($attachment['uploaded_at'])); ?>
                                            <?php if ($attachment['description']): ?>
                                                | <?php echo htmlspecialchars($attachment['description']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="file-actions">
                                        <a href="../download_attachment.php?id=<?php echo $attachment['attachment_id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                        <?php if ($userRole === 'doctor' || $userRole === 'admin'): ?>
                                            <a href="?delete=<?php echo $attachment['attachment_id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this file?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if ($userRole === 'doctor' || $userRole === 'admin'): ?>
                            <form method="POST" enctype="multipart/form-data" id="uploadForm<?php echo $record['record_id']; ?>">
                                <input type="hidden" name="record_id" value="<?php echo $record['record_id']; ?>">
                                
                                <div class="upload-area" onclick="document.getElementById('file<?php echo $record['record_id']; ?>').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <h3>Upload New Attachment</h3>
                                    <p>Click to browse or drag and drop files here</p>
                                    <p style="font-size: 12px; color: #7f8c8d; margin-top: 10px;">
                                        Supported: PDF, JPG, PNG, DOC, DOCX (Max 10MB)
                                    </p>
                                    <input type="file" name="medical_file" id="file<?php echo $record['record_id']; ?>" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onchange="showFileDetails(this, <?php echo $record['record_id']; ?>)">
                                </div>
                                
                                <div id="fileDetails<?php echo $record['record_id']; ?>" style="display: none; margin-top: 15px;">
                                    <div class="form-group">
                                        <label>File Description (Optional)</label>
                                        <textarea name="description" rows="2" placeholder="Brief description of this file..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-upload"></i> Upload File
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="<?php echo $userRole === 'admin' ? '../home.php' : $userRole . '_dashboard.php'; ?>" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script>
        function showFileDetails(input, recordId) {
            if (input.files && input.files[0]) {
                document.getElementById('fileDetails' + recordId).style.display = 'block';
            }
        }
        
        // Drag and drop functionality
        document.querySelectorAll('.upload-area').forEach(area => {
            area.addEventListener('dragover', (e) => {
                e.preventDefault();
                area.classList.add('dragover');
            });
            
            area.addEventListener('dragleave', () => {
                area.classList.remove('dragover');
            });
            
            area.addEventListener('drop', (e) => {
                e.preventDefault();
                area.classList.remove('dragover');
                
                const fileInput = area.querySelector('input[type="file"]');
                fileInput.files = e.dataTransfer.files;
                
                const recordId = fileInput.id.replace('file', '');
                showFileDetails(fileInput, recordId);
            });
        });
    </script>
</body>
</html>
