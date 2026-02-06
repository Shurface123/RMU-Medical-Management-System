<?php
require_once '../session_check.php';
require_once '../db_conn.php';

// Verify user is a patient
if ($_SESSION['role'] !== 'patient') {
    header("Location: ../index.php?error=Unauthorized access");
    exit();
}

$patient_id = $_SESSION['user_id'];
$patient_name = $_SESSION['name'];

// Get current date
date_default_timezone_set('Africa/Accra');
$currentDate = date('l, F j, Y');
$currentTime = date('g:i A');

// Fetch patient statistics
$stats = [];

// Total appointments
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE patient_id = (SELECT id FROM patients WHERE user_id = $patient_id)");
$stats['appointments'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Upcoming appointments
$today = date('Y-m-d');
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE patient_id = (SELECT id FROM patients WHERE user_id = $patient_id) AND appointment_date >= '$today' AND status != 'Cancelled'");
$stats['upcoming'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Prescriptions
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM prescriptions WHERE patient_id = (SELECT id FROM patients WHERE user_id = $patient_id)");
$stats['prescriptions'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Lab tests
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM lab_tests WHERE patient_id = (SELECT id FROM patients WHERE user_id = $patient_id)");
$stats['lab_tests'] = mysqli_fetch_assoc($result)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - RMU Medical Sickbay</title>
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
            --primary-color: #16a085;
            --primary-dark: #138871;
            --accent-color: #e74c3c;
            --text-dark: #2c3e50;
            --white: #ffffff;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #ecf0f1;
            color: var(--text-dark);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: var(--white);
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            opacity: 0.9;
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
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            padding-left: 2.5rem;
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
            background: #2c3e50;
            color: var(--white);
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .logout-btn a:hover {
            background: #1a252f;
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
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: var(--white);
            padding: 3rem;
            border-radius: 1rem;
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
            border-radius: 1rem;
            box-shadow: var(--shadow);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
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
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--white);
        }

        .stat-card-icon.red {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .stat-card-icon.blue {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .stat-card-icon.green {
            background: linear-gradient(135deg, #27ae60, #229954);
        }

        .stat-card-icon.purple {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .stat-card-body h3 {
            font-size: 1.4rem;
            color: #7f8c8d;
            font-weight: 500;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }

        .stat-card-body .number {
            font-size: 4rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Quick Actions */
        .quick-actions {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            margin-bottom: 3rem;
        }

        .quick-actions h3 {
            font-size: 2.2rem;
            margin-bottom: 2rem;
            color: var(--text-dark);
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 1.5rem 2rem;
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(22, 160, 133, 0.3);
        }

        /* Appointments List */
        .appointments-section {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .appointments-section h3 {
            font-size: 2.2rem;
            margin-bottom: 2rem;
            color: var(--text-dark);
        }

        .appointment-item {
            padding: 1.5rem;
            border: 1px solid #e0e0e0;
            border-radius: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .appointment-doctor {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .appointment-date {
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
            <i class="fas fa-user-injured"></i>
            <h2>PATIENT</h2>
            <p><?php echo htmlspecialchars($patient_name); ?></p>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="#" class="active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../booking.php">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Book Appointment</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-calendar-alt"></i>
                    <span>My Appointments</span>
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
                    <i class="fas fa-flask"></i>
                    <span>Lab Results</span>
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
            <h1>Patient Portal</h1>
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
                <h2>Welcome, <?php echo htmlspecialchars($patient_name); ?>!</h2>
                <p>Manage your health and appointments with RMU Medical Sickbay.</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon red">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Total Appointments</h3>
                        <div class="number"><?php echo $stats['appointments']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon blue">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Upcoming</h3>
                        <div class="number"><?php echo $stats['upcoming']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon green">
                            <i class="fas fa-prescription"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Prescriptions</h3>
                        <div class="number"><?php echo $stats['prescriptions']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon purple">
                            <i class="fas fa-flask"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Lab Tests</h3>
                        <div class="number"><?php echo $stats['lab_tests']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="action-buttons">
                    <a href="../booking.php" class="action-btn">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Book Appointment</span>
                    </a>
                    <a href="#" class="action-btn">
                        <i class="fas fa-file-medical"></i>
                        <span>View Records</span>
                    </a>
                    <a href="#" class="action-btn">
                        <i class="fas fa-prescription"></i>
                        <span>My Prescriptions</span>
                    </a>
                    <a href="#" class="action-btn">
                        <i class="fas fa-flask"></i>
                        <span>Lab Results</span>
                    </a>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="appointments-section">
                <h3><i class="fas fa-calendar-alt"></i> Upcoming Appointments</h3>
                <?php
                $appointments_query = "SELECT a.*, d.D_Name as doctor_name 
                                      FROM appointments a 
                                      LEFT JOIN doctor d ON a.doctor_id = d.D_ID 
                                      WHERE a.patient_id = (SELECT id FROM patients WHERE user_id = $patient_id) 
                                      AND a.appointment_date >= '$today' 
                                      AND a.status != 'Cancelled'
                                      ORDER BY a.appointment_date ASC, a.appointment_time ASC 
                                      LIMIT 5";
                $appointments_result = mysqli_query($conn, $appointments_query);
                
                if (mysqli_num_rows($appointments_result) > 0) {
                    while ($appointment = mysqli_fetch_assoc($appointments_result)) {
                        $doctor_display_name = $appointment['doctor_name'] ?? 'Doctor';
                        echo '<div class="appointment-item">';
                        echo '<div class="appointment-header">';
                        echo '<div class="appointment-doctor"><i class="fas fa-user-md"></i> Dr. ' . htmlspecialchars($doctor_display_name) . '</div>';
                        echo '<div class="appointment-date"><i class="far fa-calendar"></i> ' . date('M d, Y', strtotime($appointment['appointment_date'])) . ' at ' . htmlspecialchars($appointment['appointment_time']) . '</div>';
                        echo '</div>';
                        echo '<div class="appointment-details">';
                        echo '<div><strong>Type:</strong> ' . htmlspecialchars($appointment['appointment_type']) . '</div>';
                        echo '<div><strong>Status:</strong> ' . htmlspecialchars($appointment['status']) . '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p style="text-align: center; color: #6c757d; padding: 2rem;">No upcoming appointments. <a href="../booking.php" style="color: var(--primary-color);">Book one now</a></p>';
                }
                ?>
            </div>
        </div>
    </main>
</body>
</html>
