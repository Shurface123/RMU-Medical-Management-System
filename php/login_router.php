<?php
/**
 * login_router.php — Centralized Role-Based Dashboard Router
 * Include this once and call login_route($role) after successful authentication.
 */

if (!function_exists('login_route')) {
    function login_route(string $role): void {
        $map = [
            'admin'             => '/RMU-Medical-Management-System/php/home.php',
            'doctor'            => '/RMU-Medical-Management-System/php/dashboards/doctor_dashboard.php',
            'patient'           => '/RMU-Medical-Management-System/php/dashboards/patient_dashboard.php',
            'pharmacist'        => '/RMU-Medical-Management-System/php/dashboards/pharmacy_dashboard.php',
            'nurse'             => '/RMU-Medical-Management-System/php/dashboards/nurse_dashboard.php',
            'lab_technician'    => '/RMU-Medical-Management-System/php/dashboards/lab_dashboard.php',
            'ambulance_driver'  => '/RMU-Medical-Management-System/php/dashboards/staff_dashboard.php',
            'cleaner'           => '/RMU-Medical-Management-System/php/dashboards/staff_dashboard.php',
            'laundry_staff'     => '/RMU-Medical-Management-System/php/dashboards/staff_dashboard.php',
            'maintenance'       => '/RMU-Medical-Management-System/php/dashboards/staff_dashboard.php',
            'security'          => '/RMU-Medical-Management-System/php/dashboards/staff_dashboard.php',
            'kitchen_staff'     => '/RMU-Medical-Management-System/php/dashboards/staff_dashboard.php',
            'staff'             => '/RMU-Medical-Management-System/php/dashboards/staff_dashboard.php',
            'finance_officer'   => '/RMU-Medical-Management-System/php/finance/finance_dashboard.php',
            'finance_manager'   => '/RMU-Medical-Management-System/php/finance/finance_dashboard.php',
        ];
        $dest = $map[$role] ?? '/RMU-Medical-Management-System/php/index.php?error=' . urlencode('Unknown role. Contact administrator.');
        header('Location: ' . $dest);
        exit;
    }
}
