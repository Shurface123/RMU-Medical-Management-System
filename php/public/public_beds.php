<?php
require_once '../db_conn.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMU Inpatient Bed Facilities</title>
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/landing.css">
    <style>
        .beds-hero { padding: 8rem 2rem 5rem; text-align: center; }
        .beds-hero h1 { font-size: clamp(2.5rem, 5vw, 4rem); margin-bottom: 1rem; color: var(--lp-text); }
        .beds-hero p { font-size: 1.25rem; color: var(--lp-text-muted); }

        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 2rem;
            margin: -3rem auto 4rem; max-width: 1000px; position: relative; z-index: 10;
        }
        .stat-card {
            background: var(--lp-bg-card); padding: 2rem; border-radius: 20px;
            text-align: center; border: 1px solid var(--lp-border);
            box-shadow: 0 10px 30px rgba(47, 128, 237, 0.08);
            transition: transform 0.3s var(--lp-ease);
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .number { font-size: 3rem; font-weight: 800; color: var(--lp-text); margin-bottom: 0.5rem; }
        .stat-card .label { font-size: 1rem; color: var(--lp-text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

        .wards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2.5rem; padding: 2rem 0; }
        .ward-card {
            background: var(--lp-bg-card); border-radius: 24px; padding: 2.5rem;
            border: 1px solid var(--lp-border);
            transition: transform 0.3s var(--lp-ease), box-shadow 0.3s var(--lp-ease);
            display: flex; flex-direction: column; height: 100%;
        }
        .ward-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .ward-icon {
            width: 64px; height: 64px; border-radius: 16px;
            background: var(--lp-primary-bg); color: var(--lp-primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; margin-bottom: 1.5rem;
        }
        .ward-card h3 { font-size: 1.6rem; color: var(--lp-text); margin-bottom: 0.5rem; font-weight: 800; }
        .bed-type {
            display: inline-block; padding: 0.4rem 1rem; background: var(--lp-bg);
            color: var(--lp-primary); border-radius: 50px; font-size: 0.85rem; font-weight: 600;
            margin-bottom: 2rem; border: 1px solid var(--lp-border);
        }
        
        .availability-section { margin-top: auto; }
        .avail-labels { display: flex; justify-content: space-between; margin-bottom: 0.8rem; font-size: 0.95rem; font-weight: 600; }
        .avail-labels span:last-child { color: var(--lp-text-muted); }
        .progress-track {
            height: 12px; background: rgba(0,0,0,0.05); border-radius: 50px; overflow: hidden;
        }
        [data-theme="dark"] .progress-track { background: rgba(255,255,255,0.05); }
        .progress-fill { height: 100%; border-radius: 50px; transition: width 1s ease-out; }
        .fill-full { background: #ef4444; }
        .fill-limited { background: #f59e0b; }
        .fill-available { background: #10b981; }
        
        .admission-section { margin-top: 6rem; padding: 5rem 0; background: var(--lp-bg-card); border-top: 1px solid var(--lp-border); }
        .admission-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 3rem; }
        .feat-item { display: flex; gap: 1.5rem; align-items: flex-start; }
        .feat-icon {
            width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
            background: var(--lp-bg); color: var(--lp-primary); border: 1px solid var(--lp-border);
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }
        .feat-text h4 { font-size: 1.2rem; color: var(--lp-text); margin-bottom: 0.5rem; font-weight: 700; }
        .feat-text p { font-size: 0.95rem; color: var(--lp-text-muted); line-height: 1.6; }
    </style>
</head>
<body>
    <div id="lpAnnouncements"></div>
    <?php
    $active_page = 'services';
    $_base = '/RMU-Medical-Management-System';
    require_once dirname(__DIR__) . '/includes/nav_landing.php';
    ?>

    <section class="beds-hero lp-hero">
        <div class="lp-container">
            <h1><i class="fas fa-bed" style="color:var(--lp-primary);"></i> Bed Availability</h1>
            <p>Real-time occupancy and capacity of our inpatient wards.</p>
        </div>
    </section>

    <!-- Statistics -->
    <div class="lp-container">
        <div class="stats-grid">
            <?php
            $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status='Available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status='Occupied' THEN 1 ELSE 0 END) as occupied
                    FROM beds";
            $res = mysqli_query($conn, $sql);
            $stats = mysqli_fetch_assoc($res);
            ?>
            <div class="stat-card">
                <div class="number"><?php echo $stats['total'] ?: 0; ?></div>
                <div class="label">Total Beds</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color: #10b981;"><?php echo $stats['available'] ?: 0; ?></div>
                <div class="label">Available</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color: #ef4444;"><?php echo $stats['occupied'] ?: 0; ?></div>
                <div class="label">Occupied</div>
            </div>
        </div>
    </div>

    <!-- Ward Information -->
    <div class="lp-container">
        <h2 class="lp-text-center lp-mb-4" style="font-size: 2.5rem; font-weight: 800; color: var(--lp-text);">Our Wards</h2>
        <div class="wards-grid">
            <?php
            $sql = "SELECT ward, bed_type, COUNT(*) as total_beds, 
                    SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available_beds
                    FROM beds 
                    GROUP BY ward, bed_type
                    ORDER BY ward, bed_type";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($ward = mysqli_fetch_assoc($result)) {
                    $avail = (int)$ward['available_beds'];
                    $total = (int)$ward['total_beds'];
                    $percent = $total > 0 ? ($avail / $total) * 100 : 0;
                    
                    if ($percent >= 50) {
                        $fillClass = 'fill-available';
                        $statusTxt = '<span style="color:#10b981;">Available</span>';
                    } elseif ($percent > 0) {
                        $fillClass = 'fill-limited';
                        $statusTxt = '<span style="color:#f59e0b;">Limited</span>';
                    } else {
                        $fillClass = 'fill-full';
                        $statusTxt = '<span style="color:#ef4444;">Full</span>';
                    }
                    
                    echo '<div class="ward-card">';
                    echo '    <div class="ward-icon"><i class="fas fa-hospital"></i></div>';
                    echo '    <h3>' . htmlspecialchars($ward['ward']) . '</h3>';
                    echo '    <div><span class="bed-type">' . htmlspecialchars($ward['bed_type']) . ' Ward</span></div>';
                    echo '    <div class="availability-section">';
                    echo '        <div class="avail-labels">';
                    echo '            <span>Status: ' . $statusTxt . '</span>';
                    echo '            <span>' . $avail . ' / ' . $total . ' Free</span>';
                    echo '        </div>';
                    echo '        <div class="progress-track">';
                    echo '            <div class="progress-fill ' . $fillClass . '" style="width: 0%;" data-width="' . $percent . '%"></div>';
                    echo '        </div>';
                    echo '    </div>';
                    echo '</div>';
                }
            } else {
                echo '<p style="text-align: center; font-size: 1.2rem; color: var(--lp-text-muted); grid-column: 1/-1;">No ward information available.</p>';
            }
            ?>
        </div>
    </div>

    <!-- Admission Information Section -->
    <section class="admission-section">
        <div class="lp-container">
            <h2 class="lp-text-center lp-mb-4" style="font-size: 2.5rem; font-weight: 800; color: var(--lp-text); margin-bottom: 3.5rem;">Admission Information</h2>
            <div class="admission-grid">
                <div class="feat-item lp-card" style="padding:2rem;">
                    <div class="feat-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="feat-text">
                        <h4>Admission Process</h4>
                        <p>Admissions are typically processed through our outpatient doctors after an initial consultation, or directly through emergency depending on patient urgency.</p>
                    </div>
                </div>
                <div class="feat-item lp-card" style="padding:2rem;">
                    <div class="feat-icon"><i class="fas fa-suitcase-rolling"></i></div>
                    <div class="feat-text">
                        <h4>What to Bring</h4>
                        <p>Please bring your RMU student/staff ID, any current medications, basic personal toiletries, and comfortable loose-fitting clothing.</p>
                    </div>
                </div>
                <div class="feat-item lp-card" style="padding:2rem;">
                    <div class="feat-icon"><i class="fas fa-clock"></i></div>
                    <div class="feat-text">
                        <h4>Visiting Hours</h4>
                        <p>General visiting hours are between 4:00 PM and 6:00 PM on weekdays, and an additional 10:00 AM to 12:00 PM slot on weekends.</p>
                    </div>
                </div>
                <div class="feat-item lp-card" style="padding:2rem;">
                    <div class="feat-icon"><i class="fas fa-ban"></i></div>
                    <div class="feat-text">
                        <h4>Ward Rules</h4>
                        <p>To preserve patient rest, only two visitors are allowed per bed at any time. Outside food must be approved by the duty nurse.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once dirname(__DIR__) . '/includes/footer_landing.php'; ?>
    <?php require_once dirname(__DIR__) . '/includes/chatbot_landing.php'; ?>
    <script src="/RMU-Medical-Management-System/js/landing.js"></script>
    <script src="/RMU-Medical-Management-System/js/landing-chatbot.js"></script>
    <script>
        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.querySelectorAll('.progress-fill').forEach(fill => {
                    fill.style.width = fill.getAttribute('data-width');
                });
            }, 300);
        });
    </script>
</body>
</html>