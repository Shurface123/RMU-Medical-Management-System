<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RMU Medical Sickbay</title>
    <link rel="shortcut icon" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR8iLCWYue_TYmdWLVce7EYTVG4wZBjW9FJtw&s">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="../css/main.css">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 70px;
            --primary-color: #16a085;
            --primary-dark: #138871;
            --accent-color: #e74c3c;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --bg-light: #ecf0f1;
            --white: #ffffff;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
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

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, #16a085 0%, #138871 100%);
            color: var(--white);
            transition: var(--transition);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--white);
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
            transition: var(--transition);
            position: relative;
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
            text-align: center;
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
            background: var(--accent-color);
            color: var(--white);
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .logout-btn a:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .logout-btn a i {
            margin-right: 1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition);
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
            color: var(--text-light);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.8rem;
            font-weight: 600;
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 3rem;
        }

        .welcome-section {
            background: linear-gradient(135deg, #16a085 0%, #1abc9c 100%);
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
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

        .stat-card-icon.doctors {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .stat-card-icon.staff {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .stat-card-icon.patients {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .stat-card-icon.tests {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .stat-card-icon.beds {
            background: linear-gradient(135deg, #1abc9c, #16a085);
        }

        .stat-card-icon.ambulance {
            background: linear-gradient(135deg, #e67e22, #d35400);
        }

        .stat-card-icon.medicine {
            background: linear-gradient(135deg, #27ae60, #229954);
        }

        .stat-card-body h3 {
            font-size: 1.4rem;
            color: var(--text-light);
            font-weight: 500;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card-body .number {
            font-size: 4rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1;
        }

        .stat-card-footer {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--bg-light);
            font-size: 1.3rem;
            color: var(--text-light);
        }

        .stat-card-footer i {
            margin-right: 0.5rem;
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
            transition: var(--transition);
        }

        .action-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(22, 160, 133, 0.3);
        }

        .action-btn i {
            font-size: 1.8rem;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .top-bar {
                padding: 0 2rem;
            }

            .dashboard-content {
                padding: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .top-bar h1 {
                font-size: 2rem;
            }

            .welcome-section h2 {
                font-size: 2.5rem;
            }

            .stat-card-body .number {
                font-size: 3rem;
            }

            .user-info .date-time {
                display: none;
            }
        }

        @media (max-width: 450px) {
            .dashboard-content {
                padding: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php
    // Include database connection
    include 'db_conn.php';
    
    // Get current date and time
    date_default_timezone_set('Africa/Accra');
    $currentDate = date('l, F j, Y');
    $currentTime = date('g:i A');
    
    // Fetch statistics
    $stats = [];
    
    // Doctors count
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM doctor");
    $stats['doctors'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Staff count
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM staff");
    $stats['staff'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Patients count
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM patient");
    $stats['patients'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Tests count
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM test");
    $stats['tests'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Beds count
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM bed");
    $stats['beds'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Ambulance count
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM ambulence");
    $stats['ambulance'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Medicine count
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM medicine");
    $stats['medicine'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    ?>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-user-shield"></i>
            <h2>ADMIN</h2>
            <p>Dashboard</p>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="/RMU-Medical-Management-System/php/home.php" class="active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/RMU-Medical-Management-System/php/Doctor/doctor.php">
                    <i class="fas fa-user-md"></i>
                    <span>Doctors</span>
                </a>
            </li>
            <li>
                <a href="/RMU-Medical-Management-System/php/staff/staff.php">
                    <i class="fas fa-user-nurse"></i>
                    <span>Staff</span>
                </a>
            </li>
            <li>
                <a href="/RMU-Medical-Management-System/php/patient/patient.php">
                    <i class="fas fa-user-injured"></i>
                    <span>Patients</span>
                </a>
            </li>
            <li>
                <a href="/RMU-Medical-Management-System/php/test/test.php">
                    <i class="fas fa-file-medical-alt"></i>
                    <span>Tests</span>
                </a>
            </li>
            <li>
                <a href="/RMU-Medical-Management-System/php/bed/bed.php">
                    <i class="fas fa-procedures"></i>
                    <span>Beds</span>
                </a>
            </li>
            <li>
                <a href="/RMU-Medical-Management-System/php/Ambulence/ambulence.php">
                    <i class="fas fa-ambulance"></i>
                    <span>Ambulance</span>
                </a>
            </li>
            <li>
                <a href="/RMU-Medical-Management-System/php/medicine/medicine.php">
                    <i class="fas fa-pills"></i>
                    <span>Medicine</span>
                </a>
            </li>
        </ul>

        <div class="logout-btn">
            <a href="/RMU-Medical-Management-System/php/index.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1>RMU Medical Sickbay</h1>
            <div class="user-info">
                <div class="date-time">
                    <i class="far fa-calendar"></i> <?php echo $currentDate; ?> | 
                    <i class="far fa-clock"></i> <?php echo $currentTime; ?>
                </div>
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2>Welcome Back, Administrator!</h2>
                <p>Here's what's happening with your medical facility today.</p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <!-- Doctors Card -->
                <a href="/RMU-Medical-Management-System/php/Doctor/doctor.php" class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon doctors">
                            <i class="fas fa-user-md"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Total Doctors</h3>
                        <div class="number"><?php echo $stats['doctors']; ?></div>
                    </div>
                    <div class="stat-card-footer">
                        <i class="fas fa-arrow-right"></i> View All Doctors
                    </div>
                </a>

                <!-- Staff Card -->
                <a href="/RMU-Medical-Management-System/php/staff/staff.php" class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon staff">
                            <i class="fas fa-user-nurse"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Total Staff</h3>
                        <div class="number"><?php echo $stats['staff']; ?></div>
                    </div>
                    <div class="stat-card-footer">
                        <i class="fas fa-arrow-right"></i> View All Staff
                    </div>
                </a>

                <!-- Patients Card -->
                <a href="/RMU-Medical-Management-System/php/patient/patient.php" class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon patients">
                            <i class="fas fa-user-injured"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Total Patients</h3>
                        <div class="number"><?php echo $stats['patients']; ?></div>
                    </div>
                    <div class="stat-card-footer">
                        <i class="fas fa-arrow-right"></i> View All Patients
                    </div>
                </a>

                <!-- Tests Card -->
                <a href="/RMU-Medical-Management-System/php/test/test.php" class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon tests">
                            <i class="fas fa-file-medical-alt"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Lab Tests</h3>
                        <div class="number"><?php echo $stats['tests']; ?></div>
                    </div>
                    <div class="stat-card-footer">
                        <i class="fas fa-arrow-right"></i> View All Tests
                    </div>
                </a>

                <!-- Beds Card -->
                <a href="/RMU-Medical-Management-System/php/bed/bed.php" class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon beds">
                            <i class="fas fa-procedures"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Available Beds</h3>
                        <div class="number"><?php echo $stats['beds']; ?></div>
                    </div>
                    <div class="stat-card-footer">
                        <i class="fas fa-arrow-right"></i> Manage Beds
                    </div>
                </a>

                <!-- Ambulance Card -->
                <a href="/RMU-Medical-Management-System/php/Ambulence/ambulence.php" class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon ambulance">
                            <i class="fas fa-ambulance"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Ambulances</h3>
                        <div class="number"><?php echo $stats['ambulance']; ?></div>
                    </div>
                    <div class="stat-card-footer">
                        <i class="fas fa-arrow-right"></i> Manage Fleet
                    </div>
                </a>

                <!-- Medicine Card -->
                <a href="/RMU-Medical-Management-System/php/medicine/medicine.php" class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon medicine">
                            <i class="fas fa-pills"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Medicines</h3>
                        <div class="number"><?php echo $stats['medicine']; ?></div>
                    </div>
                    <div class="stat-card-footer">
                        <i class="fas fa-arrow-right"></i> Manage Inventory
                    </div>
                </a>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="action-buttons">
                    <a href="/RMU-Medical-Management-System/php/booking.php" class="action-btn">
                        <i class="fas fa-calendar-plus"></i>
                        <span>New Appointment</span>
                    </a>
                    <a href="/RMU-Medical-Management-System/php/patient/patient.php" class="action-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Patient</span>
                    </a>
                    <a href="/RMU-Medical-Management-System/php/Doctor/doctor.php" class="action-btn">
                        <i class="fas fa-user-md"></i>
                        <span>Add Doctor</span>
                    </a>
                    <a href="/RMU-Medical-Management-System/php/medicine/medicine.php" class="action-btn">
                        <i class="fas fa-pills"></i>
                        <span>Add Medicine</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Update time every second
        setInterval(() => {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            const timeElements = document.querySelectorAll('.date-time');
            timeElements.forEach(el => {
                const dateText = el.textContent.split('|')[0];
                el.innerHTML = `${dateText}| <i class="far fa-clock"></i> ${timeString}`;
            });
        }, 1000);

        // Active link highlighting
        const currentPath = window.location.pathname;
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>