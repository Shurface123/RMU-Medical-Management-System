<?php
require_once '../db_conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Inventory - RMU Medical Sickbay</title>
    <link rel="shortcut icon" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR8iLCWYue_TYmdWLVce7EYTVG4wZBjW9FJtw&s">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/main.css">
    <style>
        .inventory-header {
            background: linear-gradient(135deg, #16a085, #1abc9c);
            color: white;
            padding: 6rem 2rem 4rem;
            text-align: center;
        }
        
        .inventory-header h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .inventory-header p {
            font-size: 1.8rem;
            opacity: 0.95;
        }
        
        .search-filter-section {
            background: white;
            padding: 3rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: -3rem auto 4rem;
            max-width: 1200px;
            border-radius: 1rem;
        }
        
        .search-box {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .search-box input {
            flex: 1;
            padding: 1.5rem;
            font-size: 1.6rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.8rem;
        }
        
        .search-box button {
            padding: 1.5rem 3rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.8rem;
            font-size: 1.6rem;
            cursor: pointer;
        }
        
        .filter-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 1rem 2rem;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 5rem;
            cursor: pointer;
            font-size: 1.4rem;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .medicine-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem;
        }
        
        .medicine-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .medicine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .medicine-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #16a085, #1abc9c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .medicine-icon i {
            font-size: 3rem;
            color: white;
        }
        
        .medicine-card h3 {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .medicine-card .generic-name {
            font-size: 1.4rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }
        
        .medicine-card .category {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #e8f5f3;
            color: var(--primary-color);
            border-radius: 5rem;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .medicine-card .description {
            font-size: 1.4rem;
            color: var(--text-light);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .stock-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .stock-status.available {
            color: #27ae60;
        }
        
        .stock-status.low {
            color: #f39c12;
        }
        
        .stock-status.out {
            color: #e74c3c;
        }
        
        .stock-status i {
            font-size: 1.6rem;
        }
        
        .info-banner {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 2rem;
            margin: 2rem auto;
            max-width: 1200px;
            border-radius: 0.5rem;
        }
        
        .info-banner i {
            color: #ffc107;
            margin-right: 1rem;
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
        <div id="menu-btn" class="fas fa-bars"></div>
    </header>

    <!-- Inventory Header -->
    <section class="inventory-header">
        <h1><i class="fas fa-pills"></i> Medical Inventory</h1>
        <p>Browse our comprehensive range of available medications</p>
    </section>

    <!-- Search and Filter -->
    <div class="search-filter-section">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search medicines by name...">
            <button onclick="searchMedicines()"><i class="fas fa-search"></i> Search</button>
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" onclick="filterCategory('all')">All Medicines</button>
            <button class="filter-btn" onclick="filterCategory('Analgesic')">Analgesics</button>
            <button class="filter-btn" onclick="filterCategory('Antibiotic')">Antibiotics</button>
            <button class="filter-btn" onclick="filterCategory('Vitamin')">Vitamins</button>
            <button class="filter-btn" onclick="filterCategory('Antacid')">Antacids</button>
            <button class="filter-btn" onclick="filterCategory('NSAID')">NSAIDs</button>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="container">
        <div class="info-banner">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> This is a public view of available medications. For detailed pricing, stock quantities, and to request prescriptions, please <a href="/RMU-Medical-Management-System/php/index.php" style="color: var(--primary-color); text-decoration: underline;">login to your account</a>.
        </div>
    </div>

    <!-- Medicine Grid -->
    <div class="container">
        <div class="medicine-grid" id="medicineGrid">
            <?php
            // Fetch medicines from database
            $sql = "SELECT medicine_name, generic_name, category, description, stock_quantity, reorder_level 
                    FROM medicines 
                    ORDER BY medicine_name ASC";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($medicine = mysqli_fetch_assoc($result)) {
                    // Determine stock status without showing exact quantities
                    $stockStatus = 'available';
                    $statusText = 'Available';
                    $statusIcon = 'check-circle';
                    
                    if ($medicine['stock_quantity'] == 0) {
                        $stockStatus = 'out';
                        $statusText = 'Out of Stock';
                        $statusIcon = 'times-circle';
                    } elseif ($medicine['stock_quantity'] <= $medicine['reorder_level']) {
                        $stockStatus = 'low';
                        $statusText = 'Limited Stock';
                        $statusIcon = 'exclamation-circle';
                    }
                    
                    echo '<div class="medicine-card" data-category="' . htmlspecialchars($medicine['category']) . '">';
                    echo '    <div class="medicine-icon"><i class="fas fa-pills"></i></div>';
                    echo '    <h3>' . htmlspecialchars($medicine['medicine_name']) . '</h3>';
                    echo '    <p class="generic-name">' . htmlspecialchars($medicine['generic_name'] ?? 'N/A') . '</p>';
                    echo '    <span class="category">' . htmlspecialchars($medicine['category'] ?? 'General') . '</span>';
                    echo '    <p class="description">' . htmlspecialchars($medicine['description'] ?? 'Medication available at our pharmacy.') . '</p>';
                    echo '    <div class="stock-status ' . $stockStatus . '">';
                    echo '        <i class="fas fa-' . $statusIcon . '"></i>';
                    echo '        <span>' . $statusText . '</span>';
                    echo '    </div>';
                    echo '</div>';
                }
            } else {
                echo '<p style="text-align: center; font-size: 1.8rem; color: var(--text-light); grid-column: 1/-1;">No medicines available at the moment.</p>';
            }
            
            mysqli_close($conn);
            ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer" style="margin-top: 6rem;">
        <div class="credit">
            &copy; 2026 RMU Medical Sickbay | All Rights Reserved
        </div>
    </footer>

    <script src="../../js/main.js"></script>
    <script>
        function searchMedicines() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.medicine-card');
            
            cards.forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const generic = card.querySelector('.generic-name').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || generic.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function filterCategory(category) {
            const cards = document.querySelectorAll('.medicine-card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter cards
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Real-time search
        document.getElementById('searchInput').addEventListener('input', searchMedicines);
    </script>
</body>
</html>
