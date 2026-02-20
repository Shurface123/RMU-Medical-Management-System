<?php
session_start();
require_once '../db_conn.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get analytics data
// User statistics
$userStatsQuery = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN user_role = 'patient' THEN 1 ELSE 0 END) as patients,
                    SUM(CASE WHEN user_role = 'doctor' THEN 1 ELSE 0 END) as doctors,
                    SUM(CASE WHEN user_role = 'pharmacist' THEN 1 ELSE 0 END) as pharmacists,
                    SUM(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_30d
                   FROM users";
$userStats = mysqli_fetch_assoc(mysqli_query($conn, $userStatsQuery));

// Appointment statistics
$appointmentStatsQuery = "SELECT 
                           COUNT(*) as total_appointments,
                           SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
                           SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                           SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
                           SUM(CASE WHEN appointment_date > NOW() THEN 1 ELSE 0 END) as upcoming
                          FROM appointments";
$appointmentStats = mysqli_fetch_assoc(mysqli_query($conn, $appointmentStatsQuery));

// Login activity (last 7 days)
$loginActivityQuery = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as logins
                       FROM audit_log
                       WHERE action = 'login' 
                       AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                       GROUP BY DATE(created_at)
                       ORDER BY date ASC";
$loginActivity = mysqli_query($conn, $loginActivityQuery);

// User registration trend (last 30 days)
$registrationTrendQuery = "SELECT 
                            DATE(created_at) as date,
                            COUNT(*) as registrations
                           FROM users
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                           GROUP BY DATE(created_at)
                           ORDER BY date ASC";
$registrationTrend = mysqli_query($conn, $registrationTrendQuery);

// Top active users
$topUsersQuery = "SELECT 
                   u.user_name,
                   u.name,
                   COUNT(al.id) as activity_count
                  FROM users u
                  LEFT JOIN audit_log al ON u.id = al.user_id
                  WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  GROUP BY u.id
                  ORDER BY activity_count DESC
                  LIMIT 10";
$topUsers = mysqli_query($conn, $topUsersQuery);

// System health
$systemHealthQuery = "SELECT 
                       (SELECT COUNT(*) FROM audit_log WHERE action = 'login_failed' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)) as failed_logins_1h,
                       (SELECT COUNT(*) FROM users WHERE is_active = 0) as locked_accounts,
                       (SELECT COUNT(*) FROM audit_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)) as events_1h";
$systemHealth = mysqli_fetch_assoc(mysqli_query($conn, $systemHealthQuery));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - RMU Medical Sickbay</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #F4F8FF;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            padding: 25px;
            border-radius: 24px;
            margin-bottom: 20px;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
        }
        
        .header h1 {
            color: white;
            font-size: 28px;
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 24px;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-card .change {
            font-size: 13px;
            margin-top: 8px;
        }
        
        .stat-card .change.positive {
            color: #27ae60;
        }
        
        .stat-card .change.negative {
            color: #e74c3c;
        }
        
        .stat-card .icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 48px;
            opacity: 0.1;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 24px;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
        }
        
        .chart-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .table-card {
            background: white;
            padding: 25px;
            border-radius: 24px;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
            margin-bottom: 20px;
        }
        
        .table-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.3);
        }
        
        .health-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .health-good {
            background: #27ae60;
        }
        
        .health-warning {
            background: #f39c12;
        }
        
        .health-critical {
            background: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
            <p style="color: #7f8c8d; margin-top: 5px;">System overview and performance metrics</p>
        </div>
        
        <!-- Key Metrics -->
        <div class="stats-grid">
            <div class="stat-card" style="--accent-color: #3498db;">
                <i class="fas fa-users icon"></i>
                <h3>Total Users</h3>
                <div class="value"><?php echo number_format($userStats['total_users']); ?></div>
                <div class="change positive">
                    <i class="fas fa-arrow-up"></i> <?php echo $userStats['new_users_30d']; ?> new this month
                </div>
            </div>
            
            <div class="stat-card" style="--accent-color: #27ae60;">
                <i class="fas fa-calendar-check icon"></i>
                <h3>Total Appointments</h3>
                <div class="value"><?php echo number_format($appointmentStats['total_appointments']); ?></div>
                <div class="change">
                    <?php echo $appointmentStats['upcoming']; ?> upcoming
                </div>
            </div>
            
            <div class="stat-card" style="--accent-color: #9b59b6;">
                <i class="fas fa-user-md icon"></i>
                <h3>Doctors</h3>
                <div class="value"><?php echo number_format($userStats['doctors']); ?></div>
            </div>
            
            <div class="stat-card" style="--accent-color: #e74c3c;">
                <i class="fas fa-user-injured icon"></i>
                <h3>Patients</h3>
                <div class="value"><?php echo number_format($userStats['patients']); ?></div>
            </div>
        </div>
        
        <!-- System Health -->
        <div class="table-card">
            <h3><i class="fas fa-heartbeat"></i> System Health</h3>
            <table>
                <tr>
                    <td>
                        <span class="health-indicator <?php echo $systemHealth['failed_logins_1h'] < 5 ? 'health-good' : ($systemHealth['failed_logins_1h'] < 20 ? 'health-warning' : 'health-critical'); ?>"></span>
                        Failed Login Attempts (1 hour)
                    </td>
                    <td><strong><?php echo $systemHealth['failed_logins_1h']; ?></strong></td>
                </tr>
                <tr>
                    <td>
                        <span class="health-indicator <?php echo $systemHealth['locked_accounts'] == 0 ? 'health-good' : 'health-warning'; ?>"></span>
                        Locked Accounts
                    </td>
                    <td><strong><?php echo $systemHealth['locked_accounts']; ?></strong></td>
                </tr>
                <tr>
                    <td>
                        <span class="health-indicator health-good"></span>
                        System Events (1 hour)
                    </td>
                    <td><strong><?php echo $systemHealth['events_1h']; ?></strong></td>
                </tr>
            </table>
        </div>
        
        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>Login Activity (Last 7 Days)</h3>
                <canvas id="loginChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h3>User Distribution</h3>
                <canvas id="userDistributionChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h3>Appointment Status</h3>
                <canvas id="appointmentChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h3>User Registration Trend (30 Days)</h3>
                <canvas id="registrationChart"></canvas>
            </div>
        </div>
        
        <!-- Top Active Users -->
        <div class="table-card">
            <h3><i class="fas fa-trophy"></i> Top Active Users (Last 30 Days)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Activity Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while ($user = mysqli_fetch_assoc($topUsers)): 
                    ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['user_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo number_format($user['activity_count']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../home.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script>
        // Login Activity Chart
        const loginCtx = document.getElementById('loginChart').getContext('2d');
        const loginData = {
            labels: [
                <?php 
                mysqli_data_seek($loginActivity, 0);
                while ($row = mysqli_fetch_assoc($loginActivity)) {
                    echo "'" . date('M j', strtotime($row['date'])) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Logins',
                data: [
                    <?php 
                    mysqli_data_seek($loginActivity, 0);
                    while ($row = mysqli_fetch_assoc($loginActivity)) {
                        echo $row['logins'] . ',';
                    }
                    ?>
                ],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.4
            }]
        };
        
        new Chart(loginCtx, {
            type: 'line',
            data: loginData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // User Distribution Chart
        const userDistCtx = document.getElementById('userDistributionChart').getContext('2d');
        new Chart(userDistCtx, {
            type: 'doughnut',
            data: {
                labels: ['Patients', 'Doctors', 'Pharmacists'],
                datasets: [{
                    data: [
                        <?php echo $userStats['patients']; ?>,
                        <?php echo $userStats['doctors']; ?>,
                        <?php echo $userStats['pharmacists']; ?>
                    ],
                    backgroundColor: ['#27ae60', '#3498db', '#9b59b6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
        
        // Appointment Status Chart
        const appointmentCtx = document.getElementById('appointmentChart').getContext('2d');
        new Chart(appointmentCtx, {
            type: 'bar',
            data: {
                labels: ['Scheduled', 'Completed', 'Cancelled'],
                datasets: [{
                    label: 'Appointments',
                    data: [
                        <?php echo $appointmentStats['scheduled']; ?>,
                        <?php echo $appointmentStats['completed']; ?>,
                        <?php echo $appointmentStats['cancelled']; ?>
                    ],
                    backgroundColor: ['#3498db', '#27ae60', '#e74c3c']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Registration Trend Chart
        const regCtx = document.getElementById('registrationChart').getContext('2d');
        const regData = {
            labels: [
                <?php 
                mysqli_data_seek($registrationTrend, 0);
                while ($row = mysqli_fetch_assoc($registrationTrend)) {
                    echo "'" . date('M j', strtotime($row['date'])) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'New Users',
                data: [
                    <?php 
                    mysqli_data_seek($registrationTrend, 0);
                    while ($row = mysqli_fetch_assoc($registrationTrend)) {
                        echo $row['registrations'] . ',';
                    }
                    ?>
                ],
                borderColor: '#27ae60',
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                tension: 0.4
            }]
        };
        
        new Chart(regCtx, {
            type: 'line',
            data: regData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>