<?php
// ===================================
// EMAIL QUEUE PROCESSOR
// Cron job to process pending emails
// ===================================

// This script should be run periodically (e.g., every 5 minutes) via cron job
// Example cron entry:
// */5 * * * * php /path/to/process_email_queue.php

// Set execution time limit
set_time_limit(300); // 5 minutes

// Include required files
require_once __DIR__ . '/../db_conn.php';
require_once __DIR__ . '/../classes/EmailService.php';

// Log file
$logFile = __DIR__ . '/../../logs/email_queue.log';

// Function to log messages
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry; // Also output to console
}

// Start processing
logMessage("=== Email Queue Processor Started ===");

try {
    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    logMessage("Database connection established");
    
    // Email configuration
    $emailConfig = [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => '', // Set your SMTP username
        'smtp_password' => '', // Set your SMTP password
        'from_email' => 'Sickbay.txt@rmu.edu.gh',
        'from_name' => 'RMU Medical Sickbay'
    ];
    
    // Check if SMTP credentials are configured
    if (empty($emailConfig['smtp_username']) || empty($emailConfig['smtp_password'])) {
        logMessage("WARNING: SMTP credentials not configured. Please update the configuration.");
        logMessage("Email processing skipped.");
        exit(1);
    }
    
    // Initialize EmailService
    $emailService = new EmailService($conn, $emailConfig);
    logMessage("EmailService initialized");
    
    // Process queue (process up to 50 emails per run)
    $batchSize = 50;
    logMessage("Processing up to {$batchSize} emails...");
    
    $result = $emailService->processQueue($batchSize);
    
    logMessage("Processing complete:");
    logMessage("  - Emails sent: {$result['processed']}");
    logMessage("  - Emails failed: {$result['failed']}");
    
    // Clean up old failed emails (older than 7 days)
    $cleanupQuery = "DELETE FROM email_queue 
                     WHERE status = 'Failed' 
                     AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
    
    if (mysqli_query($conn, $cleanupQuery)) {
        $deletedCount = mysqli_affected_rows($conn);
        if ($deletedCount > 0) {
            logMessage("Cleaned up {$deletedCount} old failed emails");
        }
    }
    
    // Get queue statistics
    $statsQuery = "SELECT 
                    status,
                    COUNT(*) as count,
                    priority
                   FROM email_queue 
                   WHERE status IN ('Pending', 'Failed')
                   GROUP BY status, priority";
    
    $statsResult = mysqli_query($conn, $statsQuery);
    
    if ($statsResult && mysqli_num_rows($statsResult) > 0) {
        logMessage("Current queue status:");
        while ($row = mysqli_fetch_assoc($statsResult)) {
            logMessage("  - {$row['status']} ({$row['priority']} priority): {$row['count']} emails");
        }
    } else {
        logMessage("Queue is empty");
    }
    
    logMessage("=== Email Queue Processor Finished Successfully ===");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    logMessage("=== Email Queue Processor Failed ===");
    exit(1);
}

// Close database connection
if ($conn) {
    mysqli_close($conn);
}

exit(0);
?>
