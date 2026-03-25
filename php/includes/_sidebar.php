<?php
/**
 * _sidebar.php — Shared Admin Sidebar Include
 * Include this at the top of every admin module page.
 * Pass $active_page to highlight the current nav item.
 * Usage: <?php $active_page = 'doctors'; include '../includes/_sidebar.php'; ?>
 */
$active_page = $active_page ?? '';
require_once 'maintenance_guard.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($page_title ?? 'Admin') . ' — ' . ($hospital_profile['hospital_name'] ?? 'RMU Medical Sickbay'); ?></title>
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/<?= $hospital_profile['logo_path'] ?? 'image/logo-ju-small.png' ?>">
    <link rel="shortcut icon" type="image/png" href="/RMU-Medical-Management-System/<?= $hospital_profile['logo_path'] ?? 'image/logo-ju-small.png' ?>">
    <link rel="apple-touch-icon" href="/RMU-Medical-Management-System/<?= $hospital_profile['logo_path'] ?? 'image/logo-ju-small.png' ?>">

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
    <div class="adm-sidebar-brand" style="padding: 1.5rem 1rem;">
        <div class="adm-sidebar-brand-icon" style="flex: 0 0 50px; height: 50px; overflow: hidden; border-radius: 8px;">
            <img src="/RMU-Medical-Management-System/<?= $hospital_profile['logo_path'] ?? 'image/logo-ju-small.png' ?>" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <div class="adm-sidebar-brand-text">
            <h2 style="font-size: 0.95rem; white-space: normal; line-height: 1.2;"><?= strtoupper($hospital_profile['hospital_name'] ?? 'RMU SICKBAY') ?></h2>
            <span style="font-size: 0.75rem; color: var(--text-muted);"><?= $hospital_profile['facility_type'] ?? 'Admin Panel' ?></span>
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

        <!-- ── NEW: Staff Management Hub ── -->
        <a href="/RMU-Medical-Management-System/php/staff/staff.php"
           class="adm-nav-item <?php echo $active_page === 'staff' ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i>
            <span>All Staff</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/staff_approvals.php"
           class="adm-nav-item <?php echo $active_page === 'staff_approvals' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i>
            <span>Pending Approvals</span>
        </a>
        
        <a href="/RMU-Medical-Management-System/php/admin/staff_hub.php"
           class="adm-nav-item <?php echo $active_page === 'staff_hub' ? 'active' : ''; ?>">
            <i class="fas fa-id-card-alt"></i>
            <span>Staff & HR Hub</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/staff_audit_logs.php"
           class="adm-nav-item <?php echo $active_page === 'staff_audit_logs' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Audit Logs</span>
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

        <a href="/RMU-Medical-Management-System/php/Ambulence/ambulence.php"
           class="adm-nav-item <?php echo $active_page === 'ambulance' ? 'active' : ''; ?>">
            <i class="fas fa-ambulance"></i>
            <span>Ambulance</span>
        </a>

        <span class="adm-nav-section-label">Pharmacy & Store</span>

        <a href="/RMU-Medical-Management-System/php/admin/inventory_management.php"
           class="adm-nav-item <?php echo $active_page === 'inventory' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i>
            <span>Inventory Hub</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/procurement_center.php"
           class="adm-nav-item <?php echo $active_page === 'procurement' ? 'active' : ''; ?>">
            <i class="fas fa-truck-loading"></i>
            <span>Procurement Center</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/payment/payment.php"
           class="adm-nav-item <?php echo $active_page === 'payment' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i>
            <span>Payments</span>
        </a>

        <!-- ── NEW: Operations & Logistics ── -->
        <span class="adm-nav-section-label">Operations</span>

        <a href="/RMU-Medical-Management-System/php/admin/manage_tasks.php"
           class="adm-nav-item <?php echo $active_page === 'manage_tasks' ? 'active' : ''; ?>">
            <i class="fas fa-tasks"></i>
            <span>Task Assignments</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/manage_shifts.php"
           class="adm-nav-item <?php echo $active_page === 'manage_shifts' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Shift Scheduling</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/manage_leaves.php"
           class="adm-nav-item <?php echo $active_page === 'manage_leaves' ? 'active' : ''; ?>">
            <i class="fas fa-umbrella-beach"></i>
            <span>Leave Requests</span>
        </a>

        <!-- ── NEW: Facility & Services ── -->
        <span class="adm-nav-section-label">Services</span>
        
        <a href="/RMU-Medical-Management-System/php/admin/facility_ambulance.php"
           class="adm-nav-item <?php echo $active_page === 'ambulance' ? 'active' : ''; ?>">
            <i class="fas fa-ambulance"></i>
            <span>Ambulance Dispatch</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/facility_maintenance.php"
           class="adm-nav-item <?php echo $active_page === 'maintenance' ? 'active' : ''; ?>">
            <i class="fas fa-tools"></i>
            <span>Maintenance</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/facility_kitchen.php"
           class="adm-nav-item <?php echo $active_page === 'kitchen' ? 'active' : ''; ?>">
            <i class="fas fa-utensils"></i>
            <span>Dietary & Kitchen</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/facility_cleaning.php"
           class="adm-nav-item <?php echo $active_page === 'cleaning' ? 'active' : ''; ?>">
            <i class="fas fa-broom"></i>
            <span>Cleaning & Hygiene</span>
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

        <a href="/RMU-Medical-Management-System/php/admin/reporting_center.php"
           class="adm-nav-item <?php echo $active_page === 'reporting_center' ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice"></i>
            <span>Reporting Center</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/admin_profile.php"
           class="adm-nav-item <?php echo $active_page === 'admin_profile' ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i>
            <span>Professional Profile</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/settings_v2.php"
           class="adm-nav-item <?php echo $active_page === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>

        <a href="/RMU-Medical-Management-System/php/admin/settings_health_messages.php"
           class="adm-nav-item <?php echo $active_page === 'health_messages' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>
            <span>Broadcasts</span>
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

<!-- ── GLOBAL SCRIPTS ────────────────────────────────────── -->
<script src="/RMU-Medical-Management-System/php/includes/BroadcastReceiver.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Broadcast Receiver for Admin
    if (typeof BroadcastReceiver !== 'undefined') {
        window.rmuBroadcasts = new BroadcastReceiver(<?= $_SESSION['user_id'] ?>);
    }

    const sidebar  = document.getElementById('admSidebar');
    const overlay  = document.getElementById('admOverlay');
    document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
    overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
});
</script>

<script>
function showToast(message, type = 'success') {
    let container = document.querySelector('.adm-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'adm-toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `adm-toast ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-circle-exclamation';
    toast.innerHTML = `<i class="fas ${icon}"></i> <span>${message}</span>`;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(25px)';
        toast.style.transition = 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}
</script>
