<?php
require_once '../db_conn.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['nurse', 'doctor', 'admin'])) {
    die("Unauthorized access.");
}

$id = (int)($_GET['id'] ?? 0);
if(!$id) die("Invalid Note ID.");

$note = dbSelect($conn, 
    "SELECT nn.*, u.name AS patient_name, p.patient_id AS pat_num, n.full_name AS nurse_name, ba.bed_id 
     FROM nursing_notes nn 
     JOIN patients p ON nn.patient_id=p.id JOIN users u ON p.user_id=u.id
     JOIN nurses n ON nn.nurse_id=n.id
     LEFT JOIN bed_assignments ba ON ba.patient_id=p.id AND ba.status='Active'
     WHERE nn.id=?", "i", [$id]);

if(empty($note)) die("Clinical Note not found.");
$n = $note[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinical Note PDF Export</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; padding: 40px; margin: 0 auto; max-width: 800px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #E91E63; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #E91E63; font-size: 24px; }
        .meta-box { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-bottom: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .meta-box div { font-size: 14px; }
        .meta-box strong { color: #555; }
        .content { background: #fff; padding: 20px; border: 1px solid #eee; border-radius: 8px; font-size: 15px; }
        .content strong { color: #E91E63; display: block; margin-top: 15px; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #ddd; padding-top: 20px; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom:20px; text-align:right;">
        <button onclick="window.print()" style="background:#E91E63;color:white;border:none;padding:10px 20px;border-radius:4px;cursor:pointer;font-weight:bold;" class="btn btn-primary"><span class="btn-text">Save as PDF / Print</span></button>
    </div>

    <div class="header">
        <div>
            <h1>RMU Medical Sickbay</h1>
            <p style="margin:5px 0 0 0; color:#666;">Official Clinical Nursing Record</p>
        </div>
        <div style="text-align:right;">
            <strong style="font-size:18px;">Note Type: <?= htmlspecialchars($n['note_type']) ?></strong><br>
            Date: <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
        </div>
    </div>

    <div class="meta-box">
        <div><strong>Patient Name:</strong> <?= htmlspecialchars($n['patient_name']) ?></div>
        <div><strong>Patient ID:</strong> <?= htmlspecialchars($n['pat_num']) ?></div>
        <div><strong>Ward / Bed:</strong> <?= htmlspecialchars($n['bed_id'] ?? 'Unassigned') ?></div>
        <div><strong>Attending Nurse:</strong> <?= htmlspecialchars($n['nurse_name']) ?></div>
        <div><strong>Audit Status:</strong> <?= $n['is_locked'] ? 'LOCKED & VERIFIED' : 'ACTIVE EDIT' ?></div>
    </div>

    <div class="content">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Clinical Narrative (S-O-A-P)</h3>
        <?= nl2br(htmlspecialchars($n['note_content'])) ?>
    </div>

    <div class="footer">
        <p>This document is highly confidential and intended solely for designated medical personnel.<br>
        Generated electronically by RMU Medical System on <?= date('d M Y') ?></p>
    </div>
</body>
</html>
