<?php
$files = [
    "c:/wamp64/www/RMU-Medical-Management-System/php/dashboards/doctor_dashboard.php",
    "c:/wamp64/www/RMU-Medical-Management-System/php/dashboards/patient_dashboard.php",
    "c:/wamp64/www/RMU-Medical-Management-System/php/dashboards/nurse_dashboard.php",
    "c:/wamp64/www/RMU-Medical-Management-System/php/dashboards/pharmacy_dashboard.php",
    "c:/wamp64/www/RMU-Medical-Management-System/php/dashboards/lab_dashboard.php",
    "c:/wamp64/www/RMU-Medical-Management-System/php/dashboards/medical_records.php",
    "c:/wamp64/www/RMU-Medical-Management-System/php/dashboards/staff_dashboard.php",
    "c:/wamp64/www/RMU-Medical-Management-System/php/finance/finance_dashboard.php"
];

foreach ($files as $f) {
    if (file_exists($f)) {
        $data = file_get_contents($f);
        $data = str_replace('assets/assets/css/', 'assets/css/', $data);
        $data = str_replace('assets/assets/js/', 'assets/js/', $data);
        file_put_contents($f, $data);
    }
}
echo "Cleaned up assets/assets.";
