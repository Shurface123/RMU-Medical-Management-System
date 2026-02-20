<?php
require_once '../db_conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambulance Services - RMU Medical Sickbay</title>
    <link rel="shortcut icon" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR8iLCWYue_TYmdWLVce7EYTVG4wZBjW9FJtw&s">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/main.css">
    <style>
        .ambulance-header {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            color: white;
            padding: 6rem 2rem 4rem;
            text-align: center;
        }
        
        .ambulance-header h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .emergency-banner {
            background: #fff;
            color: #2F80ED;
            padding: 3rem;
            text-align: center;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
            margin: -3rem auto 4rem;
            max-width: 1200px;
            border-radius: 24px;
            border: 3px solid #2F80ED;
        }
        
        .emergency-banner h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .emergency-banner .phone {
            font-size: 5rem;
            font-weight: 700;
            margin: 2rem 0;
        }
        
        .ambulance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 3rem;
            padding: 2rem;
        }
        
        .ambulance-card {
            background: white;
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid var(--primary-color);
        }
        
        .ambulance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .ambulance-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .ambulance-icon i {
            font-size: 4rem;
            color: white;
        }
        
        .ambulance-card h3 {
            font-size: 2.4rem;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }
        
        .ambulance-card .vehicle-number {
            font-size: 1.8rem;
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .ambulance-info {
            margin: 1.5rem 0;
        }
        
        .ambulance-info p {
            font-size: 1.5rem;
            color: var(--text-light);
            margin: 0.8rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .ambulance-info i {
            color: var(--primary-color);
            width: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            border-radius: 5rem;
            font-size: 1.4rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .status-badge.available {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.on-duty {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.maintenance {
            background: #f8d7da;
            color: #721c24;
        }
        
        .services-section {
            background: #f8f9fa;
            padding: 6rem 2rem;
            margin-top: 4rem;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .service-item {
            background: white;
            padding: 2rem;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
        }
        
        .service-item i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .service-item h4 {
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

    <!-- Ambulance Header -->
    <section class="ambulance-header">
        <h1><i class="fas fa-ambulance"></i> 24/7 Ambulance Services</h1>
        <p>Rapid emergency medical response when you need it most</p>
    </section>

    <!-- Emergency Contact Banner -->
    <div class="container">
        <div class="emergency-banner">
            <h2><i class="fas fa-phone-alt"></i> Emergency Hotline</h2>
            <div class="phone">153</div>
            <p style="font-size: 1.8rem;">Available 24 hours a day, 7 days a week</p>
            <a href="tel:153" class="btn btn-lg" style="background: #e74c3c; margin-top: 2rem;">
                <i class="fas fa-phone"></i> Call Now
            </a>
        </div>
    </div>

    <!-- Available Ambulances -->
    <div class="container">
        <h2 class="text-center mb-4" style="font-size: 3.5rem;">Our Ambulance Fleet</h2>
        <p class="text-center mb-4" style="font-size: 1.8rem; color: var(--text-light); max-width: 700px; margin: 0 auto 4rem;">
            Our modern ambulances are equipped with advanced life-support systems and staffed by trained paramedics
        </p>
        
        <div class="ambulance-grid">
            <?php
            // Fetch ambulances from database
            $sql = "SELECT ambulance_id, vehicle_number, driver_name, driver_phone, status 
                    FROM ambulances 
                    ORDER BY status ASC, vehicle_number ASC";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($ambulance = mysqli_fetch_assoc($result)) {
                    $statusClass = strtolower(str_replace(' ', '-', $ambulance['status']));
                    $statusIcon = $ambulance['status'] === 'Available' ? 'check-circle' : 
                                 ($ambulance['status'] === 'On Duty' ? 'clock' : 'tools');
                    
                    echo '<div class="ambulance-card">';
                    echo '    <div class="ambulance-icon"><i class="fas fa-ambulance"></i></div>';
                    echo '    <h3>Ambulance ' . htmlspecialchars($ambulance['ambulance_id']) . '</h3>';
                    echo '    <p class="vehicle-number"><i class="fas fa-car"></i> ' . htmlspecialchars($ambulance['vehicle_number']) . '</p>';
                    echo '    <div class="ambulance-info">';
                    
                    if ($ambulance['driver_name']) {
                        echo '        <p><i class="fas fa-user"></i> Driver: ' . htmlspecialchars($ambulance['driver_name']) . '</p>';
                    }
                    
                    if ($ambulance['driver_phone'] && $ambulance['status'] === 'Available') {
                        echo '        <p><i class="fas fa-phone"></i> <a href="tel:' . htmlspecialchars($ambulance['driver_phone']) . '">' . htmlspecialchars($ambulance['driver_phone']) . '</a></p>';
                    }
                    
                    echo '    </div>';
                    echo '    <span class="status-badge ' . $statusClass . '">';
                    echo '        <i class="fas fa-' . $statusIcon . '"></i> ' . htmlspecialchars($ambulance['status']);
                    echo '    </span>';
                    echo '</div>';
                }
            } else {
                echo '<p style="text-align: center; font-size: 1.8rem; color: var(--text-light); grid-column: 1/-1;">Ambulance information will be updated shortly.</p>';
            }
            
            mysqli_close($conn);
            ?>
        </div>
    </div>

    <!-- Services Section -->
    <section class="services-section">
        <div class="container">
            <h2 class="text-center mb-4" style="font-size: 3.5rem;">What We Provide</h2>
            <div class="services-grid">
                <div class="service-item">
                    <i class="fas fa-heartbeat"></i>
                    <h4>Advanced Life Support</h4>
                    <p>Equipped with defibrillators, oxygen, and emergency medications</p>
                </div>
                <div class="service-item">
                    <i class="fas fa-user-md"></i>
                    <h4>Trained Paramedics</h4>
                    <p>Certified emergency medical technicians on every call</p>
                </div>
                <div class="service-item">
                    <i class="fas fa-clock"></i>
                    <h4>Rapid Response</h4>
                    <p>Average response time of 10-15 minutes within campus</p>
                </div>
                <div class="service-item">
                    <i class="fas fa-hospital"></i>
                    <h4>Hospital Transfer</h4>
                    <p>Safe transport to nearest medical facilities when needed</p>
                </div>
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
