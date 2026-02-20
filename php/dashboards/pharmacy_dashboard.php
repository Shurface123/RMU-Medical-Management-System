<?php
require_once '../session_check.php';
require_once '../db_conn.php';

// Verify user is a pharmacist
if ($_SESSION['role'] !== 'pharmacist') {
    header("Location: ../index.php?error=Unauthorized access");
    exit();
}

$pharmacist_id = $_SESSION['user_id'];
$pharmacist_name = $_SESSION['name'];

// Get current date
date_default_timezone_set('Africa/Accra');
$currentDate = date('l, F j, Y');
$currentTime = date('g:i A');

// Fetch pharmacy statistics
$stats = [];

// Total medicines
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM medicines");
$stats['total_medicines'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Low stock (less than 50)
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM medicines WHERE stock_quantity < 50");
$stats['low_stock'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Out of stock
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM medicines WHERE stock_quantity = 0");
$stats['out_of_stock'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total prescriptions
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM prescriptions");
$stats['prescriptions'] = mysqli_fetch_assoc($result)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard - RMU Medical Sickbay</title>
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
        [data-theme="dark"] .prescription-card,
        [data-theme="dark"] .medicine-card {
            background: #2d2d2d;
            color: #f8f9fa;
        }

        [data-theme="dark"] .stat-card h3 {
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

        .stat-card-icon.green {
            background: linear-gradient(135deg, #6FCF97, #A8E6CF);
        }

        .stat-card-icon.orange {
            background: linear-gradient(135deg, #FFB946, #FFA06B);
        }

        .stat-card-icon.red {
            background: linear-gradient(135deg, #FF6B9D, #FFA06B);
        }

        .stat-card-icon.blue {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
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

        /* Medicine Inventory */
        .inventory-section {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: var(--shadow);
            margin-bottom: 3rem;
        }

        .inventory-section h3 {
            font-size: 2.2rem;
            margin-bottom: 2rem;
            color: var(--text-dark);
        }

        .search-bar {
            margin-bottom: 2rem;
        }

        .search-bar input {
            width: 100%;;
            padding: 1.2rem 1.5rem;
            font-size: 1.5rem;
            border: 2px solid #E8F0FE;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            background: #F8FBFF;
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: var(--shadow);
        }

        .medicine-table {
            width: 100%;
            border-collapse: collapse;
        }

        .medicine-table th,
        .medicine-table td {
            padding: 1.5rem;
            text-align: left;
            border-bottom: 1px solid #E8F0FE;
        }

        .medicine-table th {
            background: #F8FBFF;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.4rem;
        }

        .medicine-table td {
            font-size: 1.4rem;
            color: #6c757d;
        }

        .medicine-table tr:hover {
            background: #F8FBFF;
            transition: all 0.3s ease;
        }

        .stock-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .stock-badge.in-stock {
            background: #E8F8F5;
            color: #27AE60;
        }

        .stock-badge.low-stock {
            background: #FFF4E6;
            color: #E67E22;
        }

        .stock-badge.out-of-stock {
            background: #FFE8EC;
            color: #E74C3C;
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .medicine-table {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-pills"></i>
            <h2>PHARMACY</h2>
            <p><?php echo htmlspecialchars($pharmacist_name); ?></p>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="#" class="active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../medicine/medicine.php">
                    <i class="fas fa-pills"></i>
                    <span>Medicine Inventory</span>
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
                    <i class="fas fa-box"></i>
                    <span>Stock Management</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Low Stock Alerts</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-chart-line"></i>
                    <span>Reports</span>
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
            <h1>Pharmacy Dashboard</h1>
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
                <h2>Welcome, <?php echo htmlspecialchars($pharmacist_name); ?>!</h2>
                <p>Manage pharmacy inventory and prescriptions efficiently.</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon green">
                            <i class="fas fa-pills"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Total Medicines</h3>
                        <div class="number"><?php echo $stats['total_medicines']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon orange">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Low Stock</h3>
                        <div class="number"><?php echo $stats['low_stock']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon red">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Out of Stock</h3>
                        <div class="number"><?php echo $stats['out_of_stock']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon blue">
                            <i class="fas fa-prescription"></i>
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Prescriptions</h3>
                        <div class="number"><?php echo $stats['prescriptions']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Medicine Inventory -->
            <div class="inventory-section">
                <h3><i class="fas fa-box"></i> Medicine Inventory</h3>
                
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search medicines..." onkeyup="searchMedicines()">
                </div>

                <table class="medicine-table" id="medicineTable">
                    <thead>
                        <tr>
                            <th>Medicine Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $medicines_query = "SELECT * FROM medicines ORDER BY medicine_name ASC LIMIT 20";
                        $medicines_result = mysqli_query($conn, $medicines_query);
                        
                        if (mysqli_num_rows($medicines_result) > 0) {
                            while ($medicine = mysqli_fetch_assoc($medicines_result)) {
                                $quantity = $medicine['stock_quantity'];
                                $status_class = 'in-stock';
                                $status_text = 'In Stock';
                                
                                if ($quantity == 0) {
                                    $status_class = 'out-of-stock';
                                    $status_text = 'Out of Stock';
                                } elseif ($quantity < 50) {
                                    $status_class = 'low-stock';
                                    $status_text = 'Low Stock';
                                }
                                
                                echo '<tr>';
                                echo '<td><strong>' . htmlspecialchars($medicine['medicine_name']) . '</strong></td>';
                                echo '<td>' . htmlspecialchars($medicine['category'] ?? 'General') . '</td>';
                                echo '<td>' . htmlspecialchars($quantity) . '</td>';
                                echo '<td>GH₵ ' . number_format($medicine['unit_price'] ?? 0, 2) . '</td>';
                                echo '<td><span class="stock-badge ' . $status_class . '">' . $status_text . '</span></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" style="text-align: center; padding: 2rem;">No medicines in inventory</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function searchMedicines() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('medicineTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[0];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Theme Toggle Script -->
    <script>
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        const themeIcon = themeToggle.querySelector('i');

        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

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