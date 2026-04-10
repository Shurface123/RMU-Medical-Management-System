<?php
require_once '../db_conn.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Services - RMU Medical Sickbay</title>
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/landing.css">
    <style>
        .tests-hero { padding: 8rem 2rem 5rem; text-align: center; }
        .tests-hero h1 { font-size: clamp(2.5rem, 5vw, 4rem); margin-bottom: 1rem; color: var(--lp-text); }
        .tests-hero p { font-size: 1.25rem; color: var(--lp-text-muted); }
        
        .search-filter-section {
            background: var(--lp-bg-card); padding: 3rem 2rem; border-radius: 24px;
            box-shadow: 0 10px 30px rgba(47, 128, 237, 0.08); margin: -3rem auto 4rem;
            max-width: 1200px; border: 1px solid var(--lp-border); position: relative; z-index: 10;
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

        .tests-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem; padding: 2rem 0; }
        .test-card {
            background: var(--lp-bg-card); border-radius: 20px; padding: 2rem;
            border: 1px solid var(--lp-border); cursor: pointer;
            transition: transform 0.3s var(--lp-ease), box-shadow 0.3s var(--lp-ease);
            display: flex; flex-direction: column;
        }
        .test-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-color: rgba(47,128,237,0.3); }
        .test-icon {
            width: 56px; height: 56px; border-radius: 14px;
            background: var(--lp-primary-bg); color: var(--lp-primary);
            display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 1.5rem;
        }
        
        .test-card h3 { font-size: 1.4rem; color: var(--lp-text); margin-bottom: 0.5rem; font-weight: 800; }
        .cat-badge {
            display: inline-block; padding: 0.3rem 0.8rem; background: var(--lp-bg);
            color: var(--lp-primary); border-radius: 50px; font-size: 0.8rem; font-weight: 600;
            border: 1px solid var(--lp-border); margin-bottom: 1.5rem;
        }
        
        .test-details { border-top: 1px solid var(--lp-border); padding-top: 1.5rem; margin-top: auto; }
        .test-details p {
            display: flex; align-items: center; gap: 0.8rem; font-size: 0.9rem;
            color: var(--lp-text-muted); margin-bottom: 0.6rem;
        }
        .test-details i { width: 16px; color: var(--lp-primary); text-align: center; }

        .info-section { background: var(--lp-bg-card); padding: 5rem 0; margin-top: 5rem; border-top: 1px solid var(--lp-border); }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 2.5rem; }
        .info-card { text-align: center; padding: 2.5rem; background: var(--lp-bg); border-radius: 20px; border: 1px solid var(--lp-border); }
        .info-card i { font-size: 3rem; color: var(--lp-primary); margin-bottom: 1.5rem; }
        .info-card h4 { font-size: 1.3rem; margin-bottom: 1rem; color: var(--lp-text); font-weight: 800; }
        .info-card p { font-size: 0.95rem; color: var(--lp-text-muted); line-height: 1.6; }

        /* Modal specific styling override */
        .lp-modal-content h3 { font-size: 1.5rem; margin-bottom: 1rem; color: var(--lp-text); font-weight: 800; }
        .modal-detail-row { display: flex; align-items: baseline; gap: 1rem; padding: 0.8rem 0; border-bottom: 1px solid var(--lp-border); }
        .modal-detail-row:last-child { border-bottom: none; }
        .modal-label { font-weight: 700; color: var(--lp-text); min-width: 140px; display: flex; align-items: center; gap:0.5rem; }
        .modal-value { color: var(--lp-text-muted); flex: 1; }
    </style>
</head>
<body>
    <div id="lpAnnouncements"></div>
    <?php
    $active_page = 'services';
    $_base = '/RMU-Medical-Management-System';
    require_once dirname(__DIR__) . '/includes/nav_landing.php';

    // Fetch distinct categories
    $cat_result = mysqli_query($conn, "SELECT DISTINCT category FROM lab_test_catalog WHERE category IS NOT NULL AND is_active=1 ORDER BY category ASC");
    $categories = ['all' => 'All Tests'];
    while ($row = mysqli_fetch_assoc($cat_result)) {
        if (!empty(trim($row['category']))) $categories[$row['category']] = $row['category'];
    }
    ?>

    <section class="tests-hero lp-hero">
        <div class="lp-container">
            <h1><i class="fas fa-microscope" style="color:var(--lp-primary);"></i> Diagnostic Services</h1>
            <p>Comprehensive laboratory testing and medical diagnostics</p>
        </div>
    </section>

    <!-- Search and Filter -->
    <div class="lp-container">
        <div class="search-filter-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search lab tests by name or code...">
            </div>
            <div class="filter-buttons">
                <?php foreach ($categories as $key => $val): ?>
                <button class="filter-btn <?php echo $key==='all'?'active':''; ?>" onclick="filterTests(this, '<?php echo htmlspecialchars($key); ?>')">
                    <?php echo htmlspecialchars($val); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tests-grid" id="testsGrid">
            <?php
            $sql = "SELECT id, test_name, test_code, category, sample_type, normal_turnaround_hours, requires_fasting, collection_instructions 
                    FROM lab_test_catalog 
                    WHERE is_active=1 
                    ORDER BY test_name ASC";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($test = mysqli_fetch_assoc($result)) {
                    $cat = htmlspecialchars($test['category'] ?? 'General');
                    $searchData = strtolower(htmlspecialchars($test['test_name'] . ' ' . $test['test_code']));
                    
                    // JSON escape for modal
                    $modalData = htmlspecialchars(json_encode([
                        'title' => $test['test_name'],
                        'code' => $test['test_code'],
                        'category' => $test['category'],
                        'sample' => $test['sample_type'],
                        'tat' => $test['normal_turnaround_hours'] . ' hours',
                        'fasting' => $test['requires_fasting'] ? 'Yes, required' : 'Not required',
                        'instructions' => $test['collection_instructions'] ?: 'Standard collection procedure.'
                    ]), ENT_QUOTES, 'UTF-8');

                    echo '<div class="test-card" data-category="' . $cat . '" data-search="' . $searchData . '" onclick="showTestModal(this)" data-test=\'' . $modalData . '\'>';
                    echo '    <div class="test-icon"><i class="fas fa-vial"></i></div>';
                    echo '    <h3>' . htmlspecialchars($test['test_name']) . '</h3>';
                    echo '    <span class="cat-badge">' . $cat . '</span>';
                    
                    echo '    <div class="test-details">';
                    echo '        <p><i class="fas fa-flask"></i> Sample: ' . htmlspecialchars($test['sample_type']) . '</p>';
                    echo '        <p><i class="fas fa-clock"></i> Est. TAT: ' . htmlspecialchars($test['normal_turnaround_hours']) . 'h</p>';
                    if ($test['requires_fasting']) {
                        echo '        <p><i class="fas fa-utensils"></i> <span style="color:#ef4444; font-weight:600;">Fasting Required</span></p>';
                    }
                    echo '    </div>';
                    echo '</div>';
                }
            } else {
                echo '<p style="text-align: center; font-size: 1.2rem; color: var(--lp-text-muted); grid-column: 1/-1;">No tests available.</p>';
            }
            mysqli_close($conn);
            ?>
        </div>
    </div>

    <!-- Information Section -->
    <section class="info-section">
        <div class="lp-container">
            <h2 class="lp-text-center lp-mb-4" style="font-size: 2.5rem; font-weight: 800; color: var(--lp-text); margin-bottom: 3.5rem;">How It Works</h2>
            <div class="info-grid">
                <div class="info-card">
                    <i class="fas fa-calendar-check"></i>
                    <h4>1. Book Appointment</h4>
                    <p>Log in to your account and schedule a lab visit.</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-user-md"></i>
                    <h4>2. Visit Clinic</h4>
                    <p>Come to our facility at your scheduled time for sample collection.</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-flask"></i>
                    <h4>3. Testing Process</h4>
                    <p>Our certified technicians process samples using modern equipment.</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-file-medical"></i>
                    <h4>4. Get Results</h4>
                    <p>Access your results through the patient portal securely.</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 4rem;">
                <a href="/RMU-Medical-Management-System/php/index.php" class="lp-btn lp-btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login to Book a Test
                </a>
            </div>
        </div>
    </section>

    <?php require_once dirname(__DIR__) . '/includes/footer_landing.php'; ?>
    <?php require_once dirname(__DIR__) . '/includes/chatbot_landing.php'; ?>
    <script src="/RMU-Medical-Management-System/js/landing.js"></script>
    <script src="/RMU-Medical-Management-System/js/landing-chatbot.js"></script>
    
    <script>
        function filterTests(btn, category) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const term = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.test-card');
            
            cards.forEach(card => {
                const searchMatch = card.dataset.search.includes(term);
                const catMatch = category === 'all' || card.dataset.category === category;
                card.style.display = (searchMatch && catMatch) ? 'flex' : 'none';
            });
        }

        document.getElementById('searchInput').addEventListener('input', (e) => {
            const activeBtn = document.querySelector('.filter-btn.active');
            if(activeBtn) {
                const cat = activeBtn.textContent.trim() === 'All Tests' ? 'all' : activeBtn.textContent.trim();
                filterTests(activeBtn, cat);
            }
        });

        function showTestModal(elem) {
            const data = JSON.parse(elem.getAttribute('data-test'));
            if(window.lpModal) {
                const html = `
                    <h3>${data.title} (${data.code})</h3>
                    <div style="margin-top: 1.5rem;">
                        <div class="modal-detail-row">
                            <span class="modal-label"><i class="fas fa-tag"></i> Category:</span>
                            <span class="modal-value">${data.category}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-label"><i class="fas fa-flask"></i> Sample:</span>
                            <span class="modal-value">${data.sample}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-label"><i class="fas fa-clock"></i> Turnaround:</span>
                            <span class="modal-value">${data.tat}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-label"><i class="fas fa-utensils"></i> Fasting:</span>
                            <span class="modal-value" style="color: ${data.fasting.includes('Yes')?'#ef4444':'inherit'}">${data.fasting}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-label"><i class="fas fa-clipboard-list"></i> Prep:</span>
                            <span class="modal-value">${data.instructions}</span>
                        </div>
                    </div>
                `;
                window.lpModal.open(html, true);
            }
        }
    </script>
</body>
</html>