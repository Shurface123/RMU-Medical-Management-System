<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'doctor';
$_SESSION['user_name'] = 'Test';
$_SESSION['csrf_token'] = 'test';
try {
    require 'doctor_dashboard.php';
} catch (Throwable $e) {
    echo "\n\nFATAL ERROR CAUGHT:\n";
    echo $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n";
}
