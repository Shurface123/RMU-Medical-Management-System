<?php
require_once '../session_check.php';
require_once '../db_conn.php';

// Verify user is a doctor
if ($_SESSION['role'] !== 'doctor') {
    header("Location: ../index.php?error=Unauthorized access");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$doctor_name = $_SESSION['name'];

// Get current date
date_default_timezone_set('Africa/Accra');
$currentDate = date('l, F j, Y');
$currentTime = date('g:i A');

// Fetch doctor's statistics
$stats = [];

// Total patients assigned to this doctor
$result = mysqli_query($conn, "SELECT COUNT(DISTINCT patient_id) as total FROM appointments WHERE doctor_id = (SELECT id FROM doctors WHERE user_id = $doctor_id)");
$stats['patients'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Today's appointments
$today = date('Y-m-d');
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = (SELECT id FROM doctors WHERE user_id = $doctor_id) AND appointment_date = '$today'");
$stats['appointments_today'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Pending appointments
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = (SELECT id FROM doctors WHERE user_id = $doctor_id) AND status = 'Pending'");
$stats['pending'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Completed appointments
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = (SELECT id FROM doctors WHERE user_id = $doctor_id) AND status = 'Completed'");
$stats['completed'] = mysqli_fetch_assoc($result)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - RMU Medical Sickbay</title>
    <link rel="shortcut icon" href="https://juniv.edu/images/favicon.ico">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="../../css/main.css">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 70px;
            --primary-color: #2F80ED;
            --primary-dark: #2366CC;
            --secondary-color: #56CCF2;
            --accent-color: #2F80ED;
            --text-dark: #2c3e50;
            --white: #ffffff;
            --bg-light: #F4F8FF;
            --shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
            --shadow-hover: 0px 15px 40px rgba(47, 128, 237, 0.12);
        }

        /* Dark Theme */
        [data-theme="dark"] {
            --bg-light: #1a1a1a;
            --white: #2d2d2d;
            --text-dark: #f8f9fa;
            --shadow: 0px 10px 30px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0px 15px 40px rgba(0, 0, 0, 0.4);
        }

        [data-theme="dark"] body {
            background: #1a1a1a;
            color: #f8f9fa;
        }

        [data-theme="dark"] .top-bar {
            background: #2d2d2d;
            color: #f8f9fa;
        }

        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .appointment-card {
            background: #2d2d2d;
            color: #f8f9fa;
        }

        [data-theme="dark"] .stat-card h3,
        [data-theme="dark"] .appointment-header {
            color: #b0b0b0;
        }

        /* Theme Toggle Button */
        .theme-toggle {
            position: fixed;
            bottom: 100px;
            left: calc(var(--sidebar-width) / 2 - 25px);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1001;
            backdrop-filter: blur(10px);
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(to right, #2F80ED, #56CCF2);
            color: var(--white);
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        .sidebar-header i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .sidebar-header h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .sidebar-header p {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        .sidebar-menu {
            list-style: none;
            padding: 2rem 0;
        }

        .sidebar-menu li {
            margin: 0.5rem 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 1.2rem 2rem;
            color: var(--white);
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 12px;
            margin: 0 1rem;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .sidebar-menu a i {
            margin-right: 1.5rem;
            font-size: 1.8rem;
            width: 25px;
        }

        .logout-btn {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 3rem);
        }

        .logout-btn a {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.2rem;
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            text-decoration: none;
            border-radius: 12px;
            font-size: 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .logout-btn a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .top-bar {
            background: var(--white);
            height: var(--header-height);
            padding: 0 3rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .top-bar h1 {
            font-size: 2.5rem;
            color: var(--text-dark);
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-info .date-time {
            font-size: 1.4rem;
            color: #7f8c8d;
        }

        .dashboard-content {
            padding: 3rem;
        }

        .welcome-section {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            color: var(--white);
            padding: 3rem;
            border-radius: 24px;
            margin-bottom: 3rem;
            box-shadow: var(--shadow);
        }

        .welcome-section h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .welcome-section p {
            font-size: 1.6rem;
            opacity: 0.95;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .stat-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--white);
        }

        .stat-card-icon.blue {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
        }

        .stat-card-icon.green {
            background: linear-gradient(135deg, #6FCF97, #A8E6CF);
        }

        .stat-card-icon.orange {
            background: linear-gradient(135deg, #FFB946, #FFA06B);
        }

        .stat-card-icon.purple {
            background: linear-gradient(135deg, #BB6BD9, #C89EFF);
        }

        .stat-card-body h3 {
            font-size: 1.4rem;
            color: #7f8c8d;
            font-weight: 500;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card-body .number {
            font-size: 4rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Appointments List */
        .appointments-section {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: var(--shadow);
            margin-bottom: 3rem;
        }

        .appointments-section h3 {
            font-size: 2.2rem;
            margin-bottom: 2rem;
            color: var(--text-dark);
        }

        .appointment-item {
            padding: 1.5rem;
            padding-left: 2rem;
            border-left: 4px solid #2F80ED;
            background: #F8FBFF;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .appointment-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow);
            background: var(--white);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .appointment-patient {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .appointment-time {
            font-size: 1.4rem;
            color: #7f8c8d;
        }

        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            font-size: 1.4rem;
            color: #6c757d;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .status-badge.pending {
            background: #FFF4E6;
            color: #E67E22;
        }

        .status-badge.completed {
            background: #E8F8F5;
            color: #27AE60;
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-user-md"></i>
            <h2>DOCTOR</h2>
            <p><?php echo htmlspecialchars($doctor_name); ?></p>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="#" class="active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-users"></i>
                    <span>My Patients</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-file-medical"></i>
                    <span>Medical Records</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-prescription"></i>
                    <span>Prescriptions</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
        </ul>

        <div class="logout-btn">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <h1>Doctor Dashboard</h1>
            <div class="user-info">
                <div class="date-time">
                    <i class="far fa-calendar"></i> <?php echo $currentDate; ?> | 
                    <i class="far fa-clock"></i> <?php echo $currentTime; ?>
                </div>
            </div>
        </div>

        <div class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2>Welcome Back, Dr. <?php echo htmlspecialchars($doctor_name); ?>!</h2>
                <p>Here's an overview of your medical practice today.</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Total Patients</h3>
                        <div class="number"><?php echo $stats['patients']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon green">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Today's Appointments</h3>
                        <div class="number"><?php echo $stats['appointments_today']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Pending</h3>
                        <div class="number"><?php echo $stats['pending']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon purple">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Completed</h3>
                        <div class="number"><?php echo $stats['completed']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="appointments-section">
                <h3><i class="fas fa-calendar-day"></i> Today's Appointments</h3>
                <?php
                $appointments_query = "SELECT a.*, p.full_name as patient_name 
                                      FROM appointments a 
                                      LEFT JOIN patients p ON a.patient_id = p.id 
                                      WHERE a.doctor_id = (SELECT id FROM doctors WHERE user_id = $doctor_id) 
                                      AND a.appointment_date = '$today' 
                                      ORDER BY a.appointment_time ASC 
                                      LIMIT 10";
                $appointments_result = mysqli_query($conn, $appointments_query);
                
                if (mysqli_num_rows($appointments_result) > 0) {
                    while ($appointment = mysqli_fetch_assoc($appointments_result)) {
                        $patient_display_name = $appointment['patient_name'] ?? $appointment['patient_name'] ?? 'Unknown Patient';
                        echo '<div class="appointment-item">';
                        echo '<div class="appointment-header">';
                        echo '<div class="appointment-patient"><i class="fas fa-user"></i> ' . htmlspecialchars($patient_display_name) . '</div>';
                        echo '<div class="appointment-time"><i class="far fa-clock"></i> ' . htmlspecialchars($appointment['appointment_time']) . '</div>';
                        echo '</div>';
                        echo '<div class="appointment-details">';
                        echo '<div><strong>Type:</strong> ' . htmlspecialchars($appointment['appointment_type']) . '</div>';
                        echo '<div><strong>Status:</strong> <span class="status-badge ' . strtolower($appointment['status']) . '">' . htmlspecialchars($appointment['status']) . '</span></div>';
                        if (!empty($appointment['symptoms'])) {
                            echo '<div><strong>Symptoms:</strong> ' . htmlspecialchars($appointment['symptoms']) . '</div>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p style="text-align: center; color: #6c757d; padding: 2rem;">No appointments scheduled for today.</p>';
                }
                ?>
            </div>
        </div>
    </main>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Theme Toggle Script -->
    <script>
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        const themeIcon = themeToggle.querySelector('i');

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);

        // Toggle theme
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        // Update icon
        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        }
    </script>
</body>
</html>