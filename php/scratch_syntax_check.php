<?php
$files = [
    'c:\wamp64\www\RMU-Medical-Management-System\php\dashboards\staff_dashboard.php',
    'c:\wamp64\www\RMU-Medical-Management-System\php\includes\auth_middleware.php',
    'c:\wamp64\www\RMU-Medical-Management-System\php\db_conn.php',
    'c:\wamp64\www\RMU-Medical-Management-System\php\dashboards\staff_tabs\tab_overview.php',
    'c:\wamp64\www\RMU-Medical-Management-System\php\dashboards\staff_tabs\tab_maintenance.php'
];

foreach ($files as $f) {
    if (!file_exists($f)) {
        echo "MISSING: $f\n";
        continue;
    }
    $output = [];
    $ret = 0;
    exec("php -l \"$f\"", $output, $ret);
    if ($ret !== 0) {
        echo "SYNTAX ERROR in $f:\n";
        echo implode("\n", $output) . "\n";
    } else {
        echo "OK: $f\n";
    }
}
?>
