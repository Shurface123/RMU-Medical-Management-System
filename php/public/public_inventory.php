<?php
require_once '../db_conn.php';

// Fetch distinct categories for filters
$cat_result = mysqli_query($conn, "SELECT DISTINCT category FROM medicines WHERE category IS NOT NULL AND status='active' ORDER BY category ASC");
$categories = ['all' => 'All Medicines'];
while ($row = mysqli_fetch_assoc($cat_result)) {
    if (!empty(trim($row['category']))) {
        $categories[$row['category']] = $row['category'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMU Medical Inventory</title>
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/landing.css">
    <style>
        .inventory-header { padding: 8rem 2rem 5rem; text-align: center; }
        .inventory-header h1 { font-size: clamp(2.5rem, 5vw, 4rem); margin-bottom: 1rem; color: var(--lp-text); }
        .inventory-header p { font-size: 1.25rem; color: var(--lp-text-muted); }
        
        .search-filter-section {
            background: var(--lp-bg-card);
            padding: 3rem 2rem;
            box-shadow: 0 10px 30px rgba(47, 128, 237, 0.08);
            margin: -3rem auto 4rem;
            max-width: 1200px;
            border-radius: 24px;
            border: 1px solid var(--lp-border);
            position: relative;
            z-index: 10;
        }
        .search-box { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .search-box input {
            flex: 1; padding: 1.2rem; font-size: 1rem; border: 2px solid var(--lp-border);
            border-radius: 12px; background: var(--lp-bg); color: var(--lp-text); font-family: inherit;
        }
        .search-box input:focus { border-color: var(--lp-primary); outline: none; }
        .filter-buttons { display: flex; gap: 0.8rem; flex-wrap: wrap; }
        .filter-btn {
            padding: 0.8rem 1.5rem; border: 2px solid var(--lp-primary); background: transparent;
            color: var(--lp-primary); border-radius: 50px; cursor: pointer; font-size: 0.95rem;
            font-weight: 600; transition: all 0.3s;
        }
        .filter-btn.active, .filter-btn:hover { background: var(--lp-primary); color: white; }
        
        .medicine-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem; padding: 2rem 0;
        }
        .medicine-card {
            background: var(--lp-bg-card); border-radius: 20px; padding: 2rem;
            border: 1px solid var(--lp-border);
            transition: transform 0.3s var(--lp-ease), box-shadow 0.3s var(--lp-ease);
        }
        .medicine-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .medicine-icon {
            width: 56px; height: 56px;
            background: var(--lp-primary-bg); border-radius: 14px;
            display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;
        }
        .medicine-icon i { font-size: 1.8rem; color: var(--lp-primary); }
        .medicine-card h3 { font-size: 1.4rem; color: var(--lp-text); margin-bottom: 0.2rem; font-weight: 800; }
        .generic-name { font-size: 0.9rem; color: var(--lp-text-muted); margin-bottom: 1rem; }
        .category-badge {
            display: inline-block; padding: 0.4rem 0.8rem; background: var(--lp-bg);
            color: var(--lp-primary); border-radius: 50px; font-size: 0.8rem; font-weight: 600;
            margin-right: 0.5rem; margin-bottom: 1rem; border: 1px solid var(--lp-border);
        }
        .rx-badge { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2); }
        .medicine-desc { font-size: 0.95rem; color: var(--lp-text-muted); margin-bottom: 1.5rem; line-height: 1.6; }
        
        .stock-status { display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; font-weight: 700; }
        .stock-available { color: #10b981; } .stock-low { color: #f59e0b; } .stock-out { color: #ef4444; }
    </style>
</head>
<body>
    <div id="lpAnnouncements"></div>
    <?php
    $active_page = 'services';
    $_base = '/RMU-Medical-Management-System';
    require_once dirname(__DIR__) . '/includes/nav_landing.php';
    ?>

    <section class="inventory-header lp-hero">
        <div class="lp-container">
            <h1><i class="fas fa-pills" style="color:var(--lp-primary);"></i> Medical Inventory</h1>
            <p>Browse our comprehensive range of available medications</p>
        </div>
    </section>

    <!-- Search and Filter -->
    <div class="lp-container">
        <div class="search-filter-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search medicines by name or generic name...">
            </div>
            <div class="filter-buttons">
                <?php foreach ($categories as $key => $val): ?>
                <button class="btn btn-primary filter-btn <?php echo $key==='all'?'active':''; ?>" onclick="filterCategory(this, '<?php echo htmlspecialchars($key); ?>')"><span class="btn-text">
                    <?php echo htmlspecialchars($val); ?>
                </span></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="background: rgba(239, 68, 68, 0.08); border-left: 4px solid #ef4444; padding: 1.25rem 1.5rem; border-radius: 12px; margin-bottom: 3rem; color: var(--lp-text);">
            <i class="fas fa-exclamation-triangle" style="color: #ef4444; margin-right: 0.5rem;"></i>
            <strong>Prescription Notice:</strong> Most medicines require a valid prescription from a registered RMU doctor. For detailed pricing and to request prescriptions, please <a href="/RMU-Medical-Management-System/php/login.php" style="color: #ef4444; text-decoration: underline; font-weight: 600;">log in to your account</a>.
        </div>

        <div class="medicine-grid" id="medicineGrid">
            <?php
            $sql = "SELECT medicine_name, generic_name, category, description, stock_quantity, reorder_level, is_prescription_required 
                    FROM medicines 
                    WHERE status = 'active'
                    ORDER BY medicine_name ASC";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($medicine = mysqli_fetch_assoc($result)) {
                    $stockStatusClass = 'stock-available';
                    $statusText = 'Available';
                    $statusIcon = 'check-circle';
                    
                    if ($medicine['stock_quantity'] <= 0) {
                        $stockStatusClass = 'stock-out';
                        $statusText = 'Out of Stock';
                        $statusIcon = 'times-circle';
                    } elseif ($medicine['stock_quantity'] <= $medicine['reorder_level']) {
                        $stockStatusClass = 'stock-low';
                        $statusText = 'Low Stock';
                        $statusIcon = 'exclamation-triangle';
                    }
                    
                    $cat = htmlspecialchars($medicine['category'] ?? 'General');
                    echo '<div class="medicine-card" data-category="' . $cat . '" data-search="' . strtolower(htmlspecialchars($medicine['medicine_name'] . ' ' . $medicine['generic_name'])) . '">';
                    echo '    <div class="medicine-icon"><i class="fas fa-capsules"></i></div>';
                    echo '    <h3>' . htmlspecialchars($medicine['medicine_name']) . '</h3>';
                    echo '    <p class="generic-name">' . htmlspecialchars($medicine['generic_name'] ?? 'N/A') . '</p>';
                    echo '    <span class="category-badge">' . $cat . '</span>';
                    
                    if (!empty($medicine['is_prescription_required'])) {
                        echo '    <span class="category-badge rx-badge"><i class="fas fa-file-prescription"></i> Rx Required</span>';
                    }

                    echo '    <p class="medicine-desc">' . htmlspecialchars($medicine['description'] ?? 'Medication available at our pharmacy.') . '</p>';
                    echo '    <div class="stock-status ' . $stockStatusClass . '">';
                    echo '        <i class="fas fa-' . $statusIcon . '"></i>';
                    echo '        <span>' . $statusText . '</span>';
                    echo '    </div>';
                    echo '</div>';
                }
            } else {
                echo '<p style="text-align: center; font-size: 1.2rem; color: var(--lp-text-muted); grid-column: 1/-1;">No medicines available at the moment.</p>';
            }
            mysqli_close($conn);
            ?>
        </div>
    </div>

    <?php require_once dirname(__DIR__) . '/includes/footer_landing.php'; ?>
    <?php require_once dirname(__DIR__) . '/includes/chatbot_landing.php'; ?>
    <script src="/RMU-Medical-Management-System/js/landing.js"></script>
    <script src="/RMU-Medical-Management-System/js/landing-chatbot.js"></script>
    
    <script>
        function filterCategory(btn, category) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const term = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.medicine-card');
            
            cards.forEach(card => {
                const searchMatch = card.dataset.search.includes(term);
                const catMatch = category === 'all' || card.dataset.category === category;
                card.style.display = (searchMatch && catMatch) ? 'block' : 'none';
            });
        }

        document.getElementById('searchInput').addEventListener('input', (e) => {
            const activeBtn = document.querySelector('.filter-btn.active');
            // Re-trigger the active filter logic with current search term
            if(activeBtn) {
                const cat = activeBtn.textContent.trim() === 'All Medicines' ? 'all' : activeBtn.textContent.trim();
                filterCategory(activeBtn, cat);
            }
        });
    </script>
</body>
</html>