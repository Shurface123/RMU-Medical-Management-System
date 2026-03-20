<?php
// ============================================================
// NURSE DASHBOARD — RMU Medical Sickbay
// ============================================================
require_once 'nurse_security.php';
initSecureSession();
setSecurityHeaders();
$user_id = enforceNurseRole();
require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');
$csrf_token = generateCsrfToken();

$user_id   = (int)$_SESSION['user_id'];
$nurseName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Nurse';
$today     = date('Y-m-d');

// ── Auto-Detect Current Tab ─────────────────────────────────
$valid_tabs = [
    'overview', 'patients', 'medications', 'wards', 'notes',
    'tasks', 'emergency', 'fluids', 'education', 'messages',
    'analytics', 'reports', 'profile', 'settings'
];
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs) ? $_GET['tab'] : 'overview';

// ── Nurse Profile ──────────────────────────────────────────
$nurse_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT n.id AS nurse_pk, n.nurse_id, n.full_name, n.shift_type,
            n.designation, n.specialization, n.profile_photo, n.status,
            u.email, u.phone
     FROM nurses n JOIN users u ON n.user_id=u.id
     WHERE n.user_id=$user_id LIMIT 1"));

if (!$nurse_row) {
    // Fallback if profile not created yet
    $nurse_row = [
        'nurse_pk' => 0, 'nurse_id' => 'N/A', 'full_name' => $nurseName,
        'shift_type' => 'Not Assigned', 'designation' => 'Staff Nurse',
        'specialization' => '', 'profile_photo' => 'default-avatar.png',
        'status' => 'Active', 'email' => '', 'phone' => ''
    ];
}
$nurse_pk = (int)$nurse_row['nurse_pk'];
$profile_image_path = !empty($nurse_row['profile_photo']) ? htmlspecialchars($nurse_row['profile_photo']) : 'default-avatar.png';

// ── Global Stats (Used in sidebar or topbar) ───────────────
function qval($conn,$sql){$r=mysqli_query($conn,$sql);return $r?(mysqli_fetch_row($r)[0]??0):0;}

$unread_notifs = qval($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND is_read=0");
$pending_tasks = qval($conn,"SELECT COUNT(*) FROM nurse_tasks WHERE nurse_id=$nurse_pk AND status IN('Pending','In Progress')");

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard | RMU Medical</title>
    
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js & DataTables -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    
    <!-- Shared Admin/Dashboard CSS -->
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    
    <!-- NURSE THEME OVERRIDES (ORANGE: #E67E22) -->
    <style>
        :root {
            --primary-color: #E67E22;
            --primary-dark: #D35400;
            --primary-light: #FDEBD0;
            --accent-color: #C0392B; /* Deep red for critical alerts */
        }
        
        /* Ensure sidebar active links use orange */
        .sidebar .nav-links li a.active, 
        .sidebar .nav-links li a:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            border-left: 4px solid var(--primary-color);
        }
        .sidebar .nav-links li a.active i, 
        .sidebar .nav-links li a:hover i {
            color: var(--primary-color);
        }

        /* Topbar styling */
        .topbar-right .profile-dropdown {
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 50px;
            background: var(--white);
            border: 1px solid #eee;
            transition: all 0.3s ease;
        }
        .topbar-right .profile-dropdown:hover {
            box-shadow: 0 4px 15px rgba(230, 126, 34, 0.15);
            border-color: var(--primary-light);
        }

        /* Buttons & Badges */
        .btn-primary {
            background-color: var(--primary-color) !important; 
            border-color: var(--primary-color) !important;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
        }
        .badge-warning { background-color: var(--primary-color); color: #fff; }
        .badge-danger { background-color: var(--accent-color); color: #fff; }

        .emergency-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            transition: all 0.3s;
            animation: pulse-red 2s infinite;
        }
        .emergency-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
            100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
        }

        /* Tab Content Hide */
        .tab-content { display: none; }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Card refinements */
        .stat-card {
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-bottom: 3px solid var(--primary-color);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(230, 126, 34, 0.15);
        }
        
        .stat-card.alert-critical {
            border-bottom: 3px solid var(--accent-color);
            background: linear-gradient(145deg, #fff, #ffebee);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border: 1px solid var(--primary-color) !important;
        }
    </style>
</head>
<body>

    <!-- ── SIDEBAR ───────────────────────────────────────────── -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-user-nurse"></i>
                <span>Nurse Panel</span>
            </div>
            <i class="fas fa-bars" id="sidebar-toggle"></i>
        </div>
        
        <ul class="nav-links">
            <li>
                <a href="?tab=overview" class="<?= $active_tab=='overview'?'active':'' ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Overview</span>
                </a>
            </li>
            <li>
                <a href="?tab=patients" class="<?= $active_tab=='patients'?'active':'' ?>">
                    <i class="fas fa-stethoscope"></i>
                    <span>Vitals & Patients</span>
                </a>
            </li>
            <li>
                <a href="?tab=medications" class="<?= $active_tab=='medications'?'active':'' ?>">
                    <i class="fas fa-pills"></i>
                    <span>Medications</span>
                </a>
            </li>
            <li>
                <a href="?tab=wards" class="<?= $active_tab=='wards'?'active':'' ?>">
                    <i class="fas fa-bed"></i>
                    <span>Ward & Beds</span>
                </a>
            </li>
            <li>
                <a href="?tab=notes" class="<?= $active_tab=='notes'?'active':'' ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Nursing Notes</span>
                </a>
            </li>
            <li>
                <a href="?tab=tasks" class="<?= $active_tab=='tasks'?'active':'' ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Tasks & Handovers</span>
                    <?php if($pending_tasks>0): ?>
                        <span class="badge badge-warning"><?= $pending_tasks ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="?tab=emergency" class="<?= $active_tab=='emergency'?'active':'' ?>">
                    <i class="fas fa-ambulance"></i>
                    <span>Emergency Alerts</span>
                </a>
            </li>
            <li>
                <a href="?tab=fluids" class="<?= $active_tab=='fluids'?'active':'' ?>">
                    <i class="fas fa-prescription-bottle-alt"></i>
                    <span>IV & Fluids</span>
                </a>
            </li>
            <li>
                <a href="?tab=messages" class="<?= $active_tab=='messages'?'active':'' ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            <li>
                <a href="?tab=reports" class="<?= $active_tab=='reports'?'active':'' ?>">
                    <i class="fas fa-file-pdf"></i>
                    <span>Reports</span>
                </a>
            </li>
            
            <li class="nav-divider"></li>
            
            <li>
                <a href="?tab=profile" class="<?= $active_tab=='profile'?'active':'' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li>
                <a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>


    <!-- ── MAIN CONTENT ──────────────────────────────────────── -->
    <main class="main-content">
        
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h2><?= htmlspecialchars($nurse_row['designation']) ?> Dashboard</h2>
                <?php if($nurse_row['shift_type'] !== 'Not Assigned'): ?>
                    <span class="badge badge-warning" style="margin-left: 15px;">
                        <i class="far fa-clock"></i> <?= e($nurse_row['shift_type']) ?> Shift
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="topbar-right">
                <!-- Code Blue Button -->
                <a href="?tab=emergency" class="emergency-btn">
                    <i class="fas fa-exclamation-triangle"></i> CODE BLUE
                </a>
                
                <!-- Notifications -->
                <div class="notification-wrapper">
                    <a href="?tab=overview#notifications" class="notif-btn">
                        <i class="fas fa-bell"></i>
                        <?php if($unread_notifs>0): ?>
                            <span class="notif-badge"><?= $unread_notifs ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Profile -->
                <div class="profile-dropdown" onclick="window.location.href='?tab=profile'">
                    <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $profile_image_path ?>" 
                         alt="Profile" class="profile-img" onerror="this.src='/RMU-Medical-Management-System/image/default-avatar.png'">
                    <div class="profile-info">
                        <span class="name"><?= e($nurse_row['full_name']) ?></span>
                        <span class="role">Nurse (<?= e($nurse_row['nurse_id']) ?>)</span>
                    </div>
                </div>
            </div>
        </header>


        <!-- ── TAB CONTENT INCLUSIONS ────────────────────────── -->
        <div class="content-wrapper">
            <?php
            // Security check on tab inclusion
            $tab_file = "nurse_tabs/tab_{$active_tab}.php";
            if(file_exists($tab_file)) {
                include $tab_file;
            } else {
                echo "<div class='alert alert-warning'>
                        <i class='fas fa-exclamation-circle'></i> 
                        Module '{$active_tab}' is currently under development.
                      </div>";
            }
            ?>
        </div>

    </main>

    <!-- ── GLOBAL SCRIPTS ────────────────────────────────────── -->
    <script>
        // Sidebar Toggle
        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        
        if(sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });
        }
        
        // Auto-close toast notifications after 5s
        setTimeout(() => {
            const toasts = document.querySelectorAll('.toast, .alert-success');
            toasts.forEach(t => t.style.opacity = '0');
            setTimeout(() => toasts.forEach(t => t.style.display = 'none'), 500);
        }, 5000);
    </script>
</body>
</html>
