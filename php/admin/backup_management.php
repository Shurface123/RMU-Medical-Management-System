<?php
/**
 * DATABASE BACKUP UTILITY
 * Creates SQL backup of the entire database
 */

session_start();
require_once '../db_conn.php';
require_once '../classes/AuditLogger.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$auditLogger = new AuditLogger($conn);

// Database credentials
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'rmu_medical_sickbay';

// Backup directory
$backupDir = __DIR__ . '/../../backups/';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Handle backup creation
if (isset($_POST['create_backup'])) {
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . 'backup_' . $timestamp . '.sql';
    
    // Get all tables
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    $sqlDump = "-- RMU Medical Sickbay Database Backup\n";
    $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sqlDump .= "-- Database: $dbName\n\n";
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    // Loop through tables
    foreach ($tables as $table) {
        // Drop table statement
        $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Create table statement
        $createTable = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $row = mysqli_fetch_row($createTable);
        $sqlDump .= $row[1] . ";\n\n";
        
        // Insert data
        $rows = mysqli_query($conn, "SELECT * FROM `$table`");
        $numFields = mysqli_num_fields($rows);
        
        if (mysqli_num_rows($rows) > 0) {
            $sqlDump .= "INSERT INTO `$table` VALUES\n";
            $rowCount = 0;
            
            while ($row = mysqli_fetch_row($rows)) {
                $sqlDump .= "(";
                for ($i = 0; $i < $numFields; $i++) {
                    $row[$i] = addslashes($row[$i]);
                    $row[$i] = str_replace("\n", "\\n", $row[$i]);
                    if (isset($row[$i])) {
                        $sqlDump .= '"' . $row[$i] . '"';
                    } else {
                        $sqlDump .= 'NULL';
                    }
                    if ($i < ($numFields - 1)) {
                        $sqlDump .= ',';
                    }
                }
                $rowCount++;
                if ($rowCount < mysqli_num_rows($rows)) {
                    $sqlDump .= "),\n";
                } else {
                    $sqlDump .= ");\n\n";
                }
            }
        }
    }
    
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Save to file
    if (file_put_contents($backupFile, $sqlDump)) {
        $auditLogger->logAction($_SESSION['user_id'], 'backup_create', 'database', null, "Created database backup: $timestamp");
        $message = "Backup created successfully: backup_$timestamp.sql";
    } else {
        $error = "Failed to create backup file.";
    }
}

// Handle backup deletion
if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = $backupDir . $filename;
    
    if (file_exists($filepath) && unlink($filepath)) {
        $auditLogger->logAction($_SESSION['user_id'], 'backup_delete', 'database', null, "Deleted backup: $filename");
        $message = "Backup deleted successfully.";
    } else {
        $error = "Failed to delete backup.";
    }
}

// Get list of backups
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'date' => filemtime($backupDir . $file)
            ];
        }
    }
    // Sort by date, newest first
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Management - RMU Medical Sickbay</title>
    
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .info-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .info-card h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .btn {
            padding: 12px 24px;
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
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        tr:hover {
            background: #f8f9fa;
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
            <h1><i class="fas fa-database"></i> Backup Management</h1>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="create_backup" class="btn btn-success" onclick="return confirm('Create a new database backup? This may take a few moments.');">
                    <i class="fas fa-plus"></i> Create New Backup
                </button>
            </form>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Backups are stored in the <code>/backups/</code> directory. Make sure to download and store backups in a secure location outside the web server.
        </div>
        
        <div class="info-card">
            <h2>Database Backups</h2>
            
            <?php if (empty($backups)): ?>
                <div class="empty-state">
                    <i class="fas fa-database"></i>
                    <h3>No Backups Found</h3>
                    <p>Click "Create New Backup" to create your first database backup.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Date Created</th>
                            <th>Size</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($backup['name']); ?></strong></td>
                                <td><?php echo date('F j, Y g:i A', $backup['date']); ?></td>
                                <td><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                                <td>
                                    <a href="../../backups/<?php echo urlencode($backup['name']); ?>" class="btn btn-primary btn-sm" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <a href="?delete=<?php echo urlencode($backup['name']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this backup? This action cannot be undone.');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="info-card">
            <h2><i class="fas fa-info-circle"></i> Backup Information</h2>
            <ul style="line-height: 2; color: #7f8c8d;">
                <li>Backups include all database tables and data</li>
                <li>Backups are stored in SQL format and can be restored using phpMyAdmin or MySQL command line</li>
                <li>It's recommended to create backups before major system updates</li>
                <li>Store backups in a secure, off-site location</li>
                <li>Test backup restoration periodically to ensure data integrity</li>
            </ul>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../home.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
