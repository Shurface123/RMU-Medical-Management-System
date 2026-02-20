<?php
/**
 * _sidebar.php — Shared Admin Sidebar Include
 * Include this at the top of every admin module page.
 * Pass $active_page to highlight the current nav item.
 * Usage: <?php $active_page = 'doctors'; include '../includes/_sidebar.php'; ?>
 */
$active_page = $active_page ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($page_title ?? 'Admin') . ' — RMU Medical Sickbay'; ?></title>
    <link rel="shortcut icon" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR8iLCWYue_TYmdWLVce7EYTVG4wZBjW9FJtw&s">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Master Admin CSS -->
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
</head>
<body>

<!-- Mobile Overlay -->
<div class="adm-overlay" id="admOverlay"></div>

<!-- ═══════════════════════════ SIDEBAR ═══════════════════════════ -->
<aside class="adm-sidebar" id="admSidebar">

    <!-- Brand -->
    <div class="adm-sidebar-brand">
        <div class="adm-sidebar-brand-icon">
            <i class="fas fa-hospital-user"></i>
        </div>
        <div class="adm-sidebar-brand-text">
            <h2>RMU SICKBAY</h2>
            <span>Admin Panel</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="adm-sidebar-nav">
        <span class="adm-nav-section-label">Main</span>

        <a href="/RMU-Medical-Management-System/php/home.php"
           class="adm-nav-item <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>

        <span class="adm-nav-section-label">Management</span>

        <a href="/RMU-Medical-Management-System/php/Doctor/doctor.php"
           class="adm-nav-item <?php echo $active_page === 'doctors' ? 'active' : ''; ?>">
            <i class="fas fa-user-md"></i>
            <span>Doctors</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/staff/staff.php"
           class="adm-nav-item <?php echo $active_page === 'staff' ? 'active' : ''; ?>">
            <i class="fas fa-user-nurse"></i>
            <span>Staff</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/patient/patient.php"
           class="adm-nav-item <?php echo $active_page === 'patients' ? 'active' : ''; ?>">
            <i class="fas fa-user-injured"></i>
            <span>Patients</span>
        </a>

        <span class="adm-nav-section-label">Clinical</span>

        <a href="/RMU-Medical-Management-System/php/Appointment/appointment.php"
           class="adm-nav-item <?php echo $active_page === 'appointments' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Appointments</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/test/test.php"
           class="adm-nav-item <?php echo $active_page === 'tests' ? 'active' : ''; ?>">
            <i class="fas fa-flask"></i>
            <span>Lab Tests</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/bed/bed.php"
           class="adm-nav-item <?php echo $active_page === 'beds' ? 'active' : ''; ?>">
            <i class="fas fa-procedures"></i>
            <span>Bed Management</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/medicine/medicine.php"
           class="adm-nav-item <?php echo $active_page === 'medicine' ? 'active' : ''; ?>">
            <i class="fas fa-pills"></i>
            <span>Medicine Inventory</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/Ambulence/ambulence.php"
           class="adm-nav-item <?php echo $active_page === 'ambulance' ? 'active' : ''; ?>">
            <i class="fas fa-ambulance"></i>
            <span>Ambulance</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/payment/payment.php"
           class="adm-nav-item <?php echo $active_page === 'payment' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i>
            <span>Payments</span>
        </a>

        <span class="adm-nav-section-label">System</span>

        <a href="/RMU-Medical-Management-System/php/booking.php"
           class="adm-nav-item <?php echo $active_page === 'booking' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-plus"></i>
            <span>Book Appointment</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/analytics_dashboard.php"
           class="adm-nav-item <?php echo $active_page === 'analytics' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Analytics</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/system_settings.php"
           class="adm-nav-item <?php echo $active_page === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </nav>

    <!-- Footer / Logout -->
    <div class="adm-sidebar-footer">
        <a href="/RMU-Medical-Management-System/php/logout.php" class="adm-logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
<!-- ═══════════════════════════ END SIDEBAR ═══════════════════════ -->

<!-- Theme script (runs early to prevent flash) -->
<script>
(function(){
    var t = localStorage.getItem('rmu_theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
})();
</script>
