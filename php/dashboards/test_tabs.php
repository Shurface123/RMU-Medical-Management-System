<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';

function e($str) { return htmlspecialchars($str??'', ENT_QUOTES, 'UTF-8'); }
function qval($conn,$sql){$r=mysqli_query($conn,$sql);return $r?(mysqli_fetch_row($r)[0]??0):0;}
function csrfField() { return ''; }
function generateCsrfToken() { return 'test'; }
function verifyCsrfToken($t) { return true; }
function sanitize($v) { return htmlspecialchars(strip_tags(trim($v??'')), ENT_QUOTES); }
function dbRow($conn,$sql,...$args){$r=mysqli_query($conn,$sql);return $r?mysqli_fetch_assoc($r):null;}
function dbVal($conn,$sql,...$args){$r=mysqli_query($conn,$sql);return $r?(mysqli_fetch_row($r)[0]??null):null;}

$nurse_pk = 1;
$user_id = 1;
$nurseName = "Test Nurse";
$today = date('Y-m-d');
$ward_assigned = 'General Ward';
$shift_active = true;
$handover_done = 0;
$nurse_row = ['full_name' => 'Test', 'designation' => 'Staff Nurse', 'specialization' => 'General'];
$_SESSION = ['user_id' => 1];

$tabs = [
    'overview', 'patients', 'medications', 'wards', 'notes',
    'tasks', 'emergency', 'fluids', 'education', 'messages',
    'analytics', 'reports', 'profile', 'settings'
];

$results = [];

foreach($tabs as $tab) {
    if($tab == 'settings') { $results[$tab] = 'Skipped or Not found'; continue; }
    $file = "c:/wamp64/www/RMU-Medical-Management-System/php/dashboards/nurse_tabs/tab_{$tab}.php";
    if(!file_exists($file)) {
        $results[$tab] = "File not found";
        continue;
    }
    
    try {
        ob_start();
        include $file;
        ob_end_clean();
        $results[$tab] = "OK";
    } catch (Throwable $e) {
        ob_end_clean();
        $results[$tab] = "ERROR: " . $e->getMessage() . " on line " . $e->getLine() . " in file " . basename($e->getFile());
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
