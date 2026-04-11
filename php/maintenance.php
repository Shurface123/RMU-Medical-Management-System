<?php
/**
 * RMU Medical Sickbay — System Maintenance Page
 */
session_start();
require_once 'db_conn.php';

// If maintenance is OFF, redirect back to home
if (get_setting('maintenance_mode', '0') === '0') {
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance — RMU Medical Sickbay</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            color: #334155;
        }
        .maintenance-card {
            background: white;
            padding: 4rem;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 600px;
            width: 90%;
        }
        .icon-box {
            font-size: 5rem;
            color: #3b82f6;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; color: #1e293b; }
        p { font-size: 1.1rem; line-height: 1.6; color: #64748b; margin-bottom: 2rem; }
        .footer-note { font-size: 0.85rem; color: #94a3b8; }
        .btn-admin {
            display: inline-block;
            padding: 0.8rem 2rem;
            background: #1e293b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-admin:hover { background: #334155; }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="icon-box">
            <i class="fas fa-tools"></i>
        </div>
        <h1>Under Maintenance</h1>
        <p>We are currently upgrading the RMU Medical Sickbay System to serve you better. We'll be back online in a few minutes.</p>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin/settings_v2.php" class="btn btn-primary btn-admin"><span class="btn-text">Continue to Admin Panel</span></a>
        <?php else: ?>
            <div class="footer-note">
                Reference ID: <?= strtoupper(substr(md5(time()),0,8)) ?> | &copy; <?= date('Y') ?> RMU Medical Unit
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
