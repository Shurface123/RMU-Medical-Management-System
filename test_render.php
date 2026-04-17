<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'doctor';
$_SESSION['user_name'] = 'Test Doctor';

ob_start();
try {
    include 'php/dashboards/doctor_dashboard.php';
} catch (Throwable $e) {
    echo "\n\nPHP FATAL ERROR CAUGHT: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
}
$content = ob_get_clean();
file_put_contents('test_doctor_render.html', $content);
echo "Render completed. Length: " . strlen($content) . "\n";
if (strpos($content, 'FATAL ERROR') !== false) {
    echo "Fatal error found in render!\n";
}
