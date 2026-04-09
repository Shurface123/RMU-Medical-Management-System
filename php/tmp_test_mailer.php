<?php
require 'db_conn.php';
require 'includes/reg_mailer.php';

echo "Sending email...\n";
$start = microtime(true);
$res = reg_send_otp_email($conn, 'test@example.com', 'Test User', '123456');
$end = microtime(true);

echo "Result: " . json_encode($res) . "\n";
echo "Time taken: " . ($end - $start) . " seconds\n";
