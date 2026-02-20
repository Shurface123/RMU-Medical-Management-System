<?php
require_once '../db_conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bed Availability - RMU Medical Sickbay</title>
    <link rel="shortcut icon" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR8iLCWYue_TYmdWLVce7EYTVG4wZBjW9FJtw&s">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/main.css">
    <style>
        .beds-header {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            color: white;
            padding: 6rem 2rem 4rem;
            text-align: center;
        }
        
        .beds-header h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: -3rem auto 4rem;
            max-width: 1200px;
        }
        
        .stat-card {
            background: white;
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
            text-align: center;
            border-top: 4px solid var(--primary-color);
        }
        
        .stat-card .number {
            font-size: 5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .stat-card .label {
            font-size: 1.6rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .wards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 3rem;
            padding: 2rem;
        }
        
        .ward-card {
            background: white;
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .ward-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .ward-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .ward-icon i {
            font-size: 3.5rem;
            color: white;
        }
        
        .ward-card h3 {
            font-size: 2.4rem;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
        }
        
        .ward-card .bed-type {
            display: inline-block;
            padding: 0.6rem 1.5rem;
            background: #f3e5f5;
            color: #8e44ad;
            border-radius: 5rem;
            font-size: 1.3rem;
            margin-bottom: 2rem;
        }
        
        .availability-bar {
            margin: 2rem 0;
        }
        
        .availability-bar .label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: var(--text-light);
        }
        
        .availability-bar .bar {
            height: 12px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .availability-bar .fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            transition: width 0.3s;
        }
        
        .availability-bar .fill.medium {
            background: linear-gradient(90deg, #f39c12, #f1c40f);
        }
        
        .availability-bar .fill.low {
            background: linear-gradient(90deg, #e74c3c, #c0392b);
        }
        
        .ward-features {
            border-top: 1px solid #e0e0e0;
            padding-top: 2rem;
            margin-top: 2rem;
        }
        
        .ward-features p {
            font-size: 1.4rem;
            color: var(--text-light);
            margin: 0.8rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .ward-features i {
            color: var(--primary-color);
            width: 20px;
        }
        
        .facilities-section {
            background: #f8f9fa;
            padding: 6rem 2rem;
            margin-top: 4rem;
        }
        
        .facilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .facility-item {
            background: white;
            padding: 2.5rem;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
        }
        
        .facility-item i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .facility-item h4 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo-container">
            <img src="../../image/logo-ju-small.png" alt="RMU Logo" class="logo-img">
            <a href="/RMU-Medical-Management-System/html/index.html" class="logo">
                RMU <span>Medical</span> Sickbay
            </a>
        </div>
        <nav class="navbar">
            <a href="/RMU-Medical-Management-System/html/index.html">Home</a>
            <a href="/RMU-Medical-Management-System/html/services.html">Services</a>
            <a href="/RMU-Medical-Management-System/html/about.html">About</a>
            <a href="/RMU-Medical-Management-System/php/index.php">Login</a>
        </nav>
        
        <!-- Theme Toggle -->
        <button class="theme-toggle-header" id="themeToggle" aria-label="Toggle theme" title="Toggle Dark Mode">
            <i class="fas fa-moon"></i>
        </button>
        
        <div id="menu-btn" class="fas fa-bars"></div>
    </header>

    <!-- Beds Header -->
    <section class="beds-header">
        <h1><i class="fas fa-bed"></i> Inpatient Bed Facilities</h1>
        <p>Comfortable and well-equipped facilities for your recovery</p>
    </section>

    <!-- Statistics -->
    <div class="container">
        <div class="stats-grid">
            <?php
            // Fetch bed statistics
            $total_beds = 0;
            $available_beds = 0;
            $occupied_beds = 0;
            
            $sql = "SELECT COUNT(*) as total FROM beds";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $total_beds = mysqli_fetch_assoc($result)['total'];
            }
            
            $sql = "SELECT COUNT(*) as available FROM beds WHERE status = 'Available'";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $available_beds = mysqli_fetch_assoc($result)['available'];
            }
            
            $sql = "SELECT COUNT(*) as occupied FROM beds WHERE status = 'Occupied'";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $occupied_beds = mysqli_fetch_assoc($result)['occupied'];
            }
            ?>
            
            <div class="stat-card">
                <div class="number"><?php echo $total_beds; ?></div>
                <div class="label">Total Beds</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color: #27ae60;"><?php echo $available_beds; ?></div>
                <div class="label">Available</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color: #e74c3c;"><?php echo $occupied_beds; ?></div>
                <div class="label">Occupied</div>
            </div>
        </div>
    </div>

    <!-- Ward Information -->
    <div class="container">
        <h2 class="text-center mb-4" style="font-size: 3.5rem; margin-top: 4rem;">Our Wards</h2>
        <p class="text-center mb-4" style="font-size: 1.8rem; color: var(--text-light); max-width: 800px; margin: 0 auto 4rem;">
            Modern, clean, and comfortable facilities designed for your comfort and recovery
        </p>
        
        <div class="wards-grid">
            <?php
            // Fetch ward information
            $sql = "SELECT ward, bed_type, COUNT(*) as total_beds, 
                    SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available_beds
                    FROM beds 
                    GROUP BY ward, bed_type
                    ORDER BY ward, bed_type";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($ward = mysqli_fetch_assoc($result)) {
                    $availability_percent = ($ward['available_beds'] / $ward['total_beds']) * 100;
                    $fill_class = $availability_percent > 50 ? '' : ($availability_percent > 20 ? 'medium' : 'low');
                    
                    echo '<div class="ward-card">';
                    echo '    <div class="ward-icon"><i class="fas fa-hospital"></i></div>';
                    echo '    <h3>' . htmlspecialchars($ward['ward']) . '</h3>';
                    echo '    <span class="bed-type">' . htmlspecialchars($ward['bed_type']) . ' Ward</span>';
                    echo '    <div class="availability-bar">';
                    echo '        <div class="label">';
                    echo '            <span>Availability</span>';
                    echo '            <span><strong>' . $ward['available_beds'] . '/' . $ward['total_beds'] . '</strong> beds</span>';
                    echo '        </div>';
                    echo '        <div class="bar">';
                    echo '            <div class="fill ' . $fill_class . '" style="width: ' . $availability_percent . '%"></div>';
                    echo '        </div>';
                    echo '    </div>';
                    echo '    <div class="ward-features">';
                    echo '        <p><i class="fas fa-bed"></i> ' . htmlspecialchars($ward['bed_type']) . ' beds</p>';
                    echo '        <p><i class="fas fa-user-nurse"></i> 24/7 nursing care</p>';
                    echo '        <p><i class="fas fa-wifi"></i> Free WiFi access</p>';
                    echo '    </div>';
                    echo '</div>';
                }
            } else {
                echo '<p style="text-align: center; font-size: 1.8rem; color: var(--text-light); grid-column: 1/-1;">Ward information will be updated shortly.</p>';
            }
            
            mysqli_close($conn);
            ?>
        </div>
    </div>

    <!-- Facilities Section -->
    <section class="facilities-section">
        <div class="container">
            <h2 class="text-center mb-4" style="font-size: 3.5rem;">Ward Facilities</h2>
            <div class="facilities-grid">
                <div class="facility-item">
                    <i class="fas fa-bed"></i>
                    <h4>Comfortable Beds</h4>
                    <p>Adjustable hospital beds with quality mattresses</p>
                </div>
                <div class="facility-item">
                    <i class="fas fa-shower"></i>
                    <h4>Private Bathrooms</h4>
                    <p>Clean, modern bathrooms in private wards</p>
                </div>
                <div class="facility-item">
                    <i class="fas fa-tv"></i>
                    <h4>Entertainment</h4>
                    <p>TV and reading materials for patients</p>
                </div>
                <div class="facility-item">
                    <i class="fas fa-utensils"></i>
                    <h4>Meal Service</h4>
                    <p>Nutritious meals prepared by our kitchen staff</p>
                </div>
                <div class="facility-item">
                    <i class="fas fa-bell"></i>
                    <h4>Call System</h4>
                    <p>Bedside call buttons for immediate assistance</p>
                </div>
                <div class="facility-item">
                    <i class="fas fa-users"></i>
                    <h4>Visiting Hours</h4>
                    <p>Flexible visiting hours for family and friends</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 4rem;">
                <p style="font-size: 1.6rem; color: var(--text-light); margin-bottom: 2rem;">
                    Need admission? Contact our medical team
                </p>
                <a href="/RMU-Medical-Management-System/php/index.php" class="btn btn-lg btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login to Book
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" style="margin-top: 6rem;">
        <div class="credit">
            &copy; 2026 RMU Medical Sickbay | All Rights Reserved
        </div>
    </footer>

    <script src="../../js/main.js"></script>
    
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
