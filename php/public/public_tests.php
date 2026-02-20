<?php
require_once '../db_conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Services - RMU Medical Sickbay</title>
    <link rel="shortcut icon" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR8iLCWYue_TYmdWLVce7EYTVG4wZBjW9FJtw&s">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/main.css">
    <style>
        .tests-header {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            color: white;
            padding: 6rem 2rem 4rem;
            text-align: center;
        }
        
        .tests-header h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 3rem;
            padding: 2rem;
        }
        
        .test-card {
            background: white;
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border-top: 4px solid var(--primary-color);
        }
        
        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .test-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .test-icon i {
            font-size: 3.5rem;
            color: white;
        }
        
        .test-card h3 {
            font-size: 2.2rem;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }
        
        .test-card .category {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            background: #e8f4f8;
            color: #2980b9;
            border-radius: 5rem;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
        }
        
        .test-card .description {
            font-size: 1.5rem;
            color: var(--text-light);
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        
        .test-info {
            border-top: 1px solid #e0e0e0;
            padding-top: 1.5rem;
        }
        
        .test-info p {
            font-size: 1.4rem;
            color: var(--text-light);
            margin: 0.8rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .test-info i {
            color: var(--primary-color);
            width: 20px;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 6rem 2rem;
            margin-top: 4rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .info-card {
            background: white;
            padding: 3rem;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
        }
        
        .info-card i {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .info-card h4 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .info-card p {
            font-size: 1.5rem;
            color: var(--text-light);
            line-height: 1.6;
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

    <!-- Tests Header -->
    <section class="tests-header">
        <h1><i class="fas fa-microscope"></i> Diagnostic Services</h1>
        <p>Comprehensive laboratory testing and medical diagnostics</p>
    </section>

    <!-- Available Tests -->
    <div class="container">
        <h2 class="text-center mb-4" style="font-size: 3.5rem; margin-top: 4rem;">Available Tests</h2>
        <p class="text-center mb-4" style="font-size: 1.8rem; color: var(--text-light); max-width: 800px; margin: 0 auto 4rem;">
            Our modern laboratory offers a wide range of diagnostic tests to help identify and monitor health conditions
        </p>
        
        <div class="tests-grid">
            <!-- Sample tests - In production, fetch from database -->
            <div class="test-card">
                <div class="test-icon"><i class="fas fa-vial"></i></div>
                <h3>Complete Blood Count (CBC)</h3>
                <span class="category">Hematology</span>
                <p class="description">
                    Comprehensive blood test that measures red blood cells, white blood cells, and platelets to assess overall health and detect disorders.
                </p>
                <div class="test-info">
                    <p><i class="fas fa-clock"></i> Results in 24 hours</p>
                    <p><i class="fas fa-syringe"></i> Blood sample required</p>
                    <p><i class="fas fa-utensils"></i> Fasting not required</p>
                </div>
            </div>

            <div class="test-card">
                <div class="test-icon"><i class="fas fa-heartbeat"></i></div>
                <h3>Blood Sugar Test</h3>
                <span class="category">Endocrinology</span>
                <p class="description">
                    Measures glucose levels in blood to screen for and monitor diabetes and prediabetes conditions.
                </p>
                <div class="test-info">
                    <p><i class="fas fa-clock"></i> Results in 2 hours</p>
                    <p><i class="fas fa-syringe"></i> Blood sample required</p>
                    <p><i class="fas fa-utensils"></i> Fasting required (8-12 hours)</p>
                </div>
            </div>

            <div class="test-card">
                <div class="test-icon"><i class="fas fa-lungs"></i></div>
                <h3>Chest X-Ray</h3>
                <span class="category">Radiology</span>
                <p class="description">
                    Imaging test to examine the chest, lungs, heart, and chest wall for various conditions and abnormalities.
                </p>
                <div class="test-info">
                    <p><i class="fas fa-clock"></i> Results in 24-48 hours</p>
                    <p><i class="fas fa-radiation"></i> X-ray imaging</p>
                    <p><i class="fas fa-check"></i> No preparation needed</p>
                </div>
            </div>

            <div class="test-card">
                <div class="test-icon"><i class="fas fa-bacteria"></i></div>
                <h3>Urinalysis</h3>
                <span class="category">Clinical Chemistry</span>
                <p class="description">
                    Examines urine for various cells and chemicals to detect urinary tract infections, kidney disease, and diabetes.
                </p>
                <div class="test-info">
                    <p><i class="fas fa-clock"></i> Results in 24 hours</p>
                    <p><i class="fas fa-flask"></i> Urine sample required</p>
                    <p><i class="fas fa-check"></i> No fasting required</p>
                </div>
            </div>

            <div class="test-card">
                <div class="test-icon"><i class="fas fa-dna"></i></div>
                <h3>Lipid Profile</h3>
                <span class="category">Cardiology</span>
                <p class="description">
                    Measures cholesterol and triglyceride levels to assess cardiovascular disease risk and monitor treatment.
                </p>
                <div class="test-info">
                    <p><i class="fas fa-clock"></i> Results in 24 hours</p>
                    <p><i class="fas fa-syringe"></i> Blood sample required</p>
                    <p><i class="fas fa-utensils"></i> Fasting required (9-12 hours)</p>
                </div>
            </div>

            <div class="test-card">
                <div class="test-icon"><i class="fas fa-virus"></i></div>
                <h3>Malaria Test</h3>
                <span class="category">Infectious Disease</span>
                <p class="description">
                    Rapid diagnostic test to detect malaria parasites in blood, essential for prompt treatment.
                </p>
                <div class="test-info">
                    <p><i class="fas fa-clock"></i> Results in 30 minutes</p>
                    <p><i class="fas fa-syringe"></i> Blood sample required</p>
                    <p><i class="fas fa-check"></i> No preparation needed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Section -->
    <section class="info-section">
        <div class="container">
            <h2 class="text-center mb-4" style="font-size: 3.5rem;">How It Works</h2>
            <div class="info-grid">
                <div class="info-card">
                    <i class="fas fa-calendar-check"></i>
                    <h4>1. Book Appointment</h4>
                    <p>Login to your account and schedule a test appointment with our medical team</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-user-md"></i>
                    <h4>2. Visit Clinic</h4>
                    <p>Come to our facility at your scheduled time for sample collection</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-flask"></i>
                    <h4>3. Testing Process</h4>
                    <p>Our certified technicians process your samples using modern equipment</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-file-medical"></i>
                    <h4>4. Get Results</h4>
                    <p>Access your test results through your patient dashboard or collect in person</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 4rem;">
                <a href="/RMU-Medical-Management-System/php/index.php" class="btn btn-lg btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login to Book a Test
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
