<?php
session_start();
require_once '../db_conn.php';

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_name = $is_logged_in ? ($_SESSION['name'] ?? 'Patient') : '';
$user_phone = $is_logged_in ? ($_SESSION['phone'] ?? '') : '';

// Handle Form Submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_ambulance']) && $is_logged_in) {
    $pickup = mysqli_real_escape_string($conn, $_POST['pickup_location'] ?? '');
    $emergency_type = mysqli_real_escape_string($conn, $_POST['emergency_type'] ?? '');
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // Check for an active request
    $chk_sql = "SELECT id FROM ambulance_requests WHERE patient_phone = ? AND status IN ('Pending', 'Dispatched', 'In Transit')";
    $stmt = mysqli_prepare($conn, $chk_sql);
    mysqli_stmt_bind_param($stmt, "s", $user_phone);
    mysqli_stmt_execute($stmt);
    $chk_res = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($chk_res) > 0) {
        $message = "You already have an active ambulance request.";
        $messageType = "warning";
    } else {
        $req_id = 'AMB-' . strtoupper(substr(uniqid(), -5));
        $phone_str = !empty($user_phone) ? $user_phone : 'N/A'; // Need phone for table
        
        $ins = "INSERT INTO ambulance_requests (request_id, patient_name, patient_phone, pickup_location, emergency_type, notes, status, request_time) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())";
        $stmt_i = mysqli_prepare($conn, $ins);
        mysqli_stmt_bind_param($stmt_i, "ssssss", $req_id, $user_name, $phone_str, $pickup, $emergency_type, $notes);
        
        if (mysqli_stmt_execute($stmt_i)) {
            $message = "Ambulance requested successfully. Help is on the way (Ref: $req_id).";
            $messageType = "success";
        } else {
            $message = "Failed to submit request. Please call the hotline directly.";
            $messageType = "error";
        }
    }
}

// Fetch Active Request array
$active_req = null;
if ($is_logged_in && !empty($user_phone)) {
    $r_sql = "SELECT * FROM ambulance_requests WHERE patient_phone = ? AND status IN ('Pending', 'Dispatched', 'In Transit') ORDER BY request_time DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $r_sql);
    mysqli_stmt_bind_param($stmt, "s", $user_phone);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $active_req = $row;
    }
}

// Fetch Hotline Config
$hotline = "153";
$hc_res = mysqli_query($conn, "SELECT setting_value FROM landing_page_config WHERE setting_key = 'emergency_hotline'");
if ($hc_res && mysqli_num_rows($hc_res) > 0) {
    $row = mysqli_fetch_assoc($hc_res);
    if (!empty($row['setting_value'])) $hotline = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>24/7 Ambulance Services - RMU Sickbay</title>
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/landing.css">
    <style>
        .amb-hero { padding: 9rem 2rem 5rem; text-align: center; background: linear-gradient(135deg, #ef4444, #b91c1c); color: white; position: relative; overflow: hidden; }
        .amb-hero::after {
            content: ''; position: absolute; inset: 0;
            background: url('/RMU-Medical-Management-System/image/pattern.png'); opacity: 0.1;
        }
        .amb-hero > div { position: relative; z-index: 2; }
        .amb-hero h1 { font-size: clamp(2.5rem, 6vw, 4.5rem); margin-bottom: 0.5rem; font-weight: 800; }
        .amb-hero p { font-size: 1.3rem; opacity: 0.9; }

        .hotline-banner {
            background: var(--lp-bg-card); border-radius: 24px; padding: 3rem; text-align: center;
            max-width: 800px; margin: -4rem auto 4rem; position: relative; z-index: 10;
            box-shadow: 0 15px 35px rgba(239, 68, 68, 0.15); border: 1px solid rgba(239,68,68,0.2);
        }
        .hotline-banner h2 { font-size: 1.5rem; color: var(--lp-text); font-weight: 700; margin-bottom: 1rem; }
        .hotline-number { font-size: 4rem; font-weight: 900; color: #ef4444; letter-spacing: 0.05em; display: inline-block; margin-bottom: 1rem; }
        .hotline-banner a.call-btn {
            background: #ef4444; color: white; padding: 1rem 3rem; border-radius: 50px; font-size: 1.2rem;
            font-weight: 700; display: inline-flex; align-items: center; gap: 0.8rem; text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .hotline-banner a.call-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(239,68,68,0.3); }

        .req-section { max-width: 800px; margin: 0 auto 5rem; }
        .req-card { background: var(--lp-bg-card); border-radius: 24px; padding: 3rem; border: 1px solid var(--lp-border); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .req-card h3 { font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--lp-text); font-weight: 800; display: flex; align-items: center; gap: 0.8rem; }
        .req-card h3 i { color: #ef4444; }

        .lp-form-group { margin-bottom: 1.5rem; text-align: left; }
        .lp-form-group label { display: block; font-size: 0.95rem; font-weight: 600; color: var(--lp-text); margin-bottom: 0.5rem; }
        .lp-form-control {
            width: 100%; padding: 1rem 1.2rem; border: 2px solid var(--lp-border); border-radius: 12px;
            background: var(--lp-bg); color: var(--lp-text); font-family: inherit; font-size: 1rem; transition: border-color 0.3s;
        }
        .lp-form-control:focus { outline: none; border-color: #ef4444; }
        
        .status-tracker { background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239,68,68,0.2); border-radius: 16px; padding: 2rem; margin-bottom: 3rem; text-align: center; }
        .status-tracker h4 { color: #ef4444; font-size: 1.4rem; font-weight: 800; margin-bottom: 0.5rem; }
        .status-tracker p { font-size: 1.1rem; color: var(--lp-text); }
        .status-badge { display: inline-block; padding: 0.5rem 1.5rem; border-radius: 50px; background: #ef4444; color: white; font-weight: 700; margin-top: 1rem; font-size: 1.2rem; }

        .fleet-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .fleet-card { background: var(--lp-bg-card); border-radius: 20px; padding: 2rem; border: 1px solid var(--lp-border); transition: transform 0.3s; }
        .fleet-card:hover { transform: translateY(-5px); }
        .fleet-card .f-icon { width: 50px; height: 50px; border-radius: 12px; background: rgba(239, 68, 68, 0.1); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
        .f-status { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.85rem; font-weight: 700; margin-top: 1rem; }
        .f-avail { background: rgba(16,185,129,0.1); color: #10b981; }
        .f-busy { background: rgba(245,158,11,0.1); color: #f59e0b; }
    </style>
</head>
<body>
    <div id="lpAnnouncements"></div>
    <?php
    $active_page = 'services';
    $_base = '/RMU-Medical-Management-System';
    require_once dirname(__DIR__) . '/includes/nav_landing.php';
    ?>

    <section class="amb-hero">
        <div class="lp-container">
            <h1><i class="fas fa-ambulance"></i> Ambulance Services</h1>
            <p>Rapid emergency medical response when you need it most</p>
        </div>
    </section>

    <div class="lp-container">
        <div class="hotline-banner">
            <h2><i class="fas fa-phone-alt" style="color: #ef4444;"></i> Emergency Hotline</h2>
            <div class="hotline-number"><?php echo htmlspecialchars($hotline); ?></div>
            <p style="font-size: 1.2rem; color: var(--lp-text-muted); margin-bottom: 2rem;">Available 24 hours a day, 7 days a week</p>
            <a href="tel:<?php echo htmlspecialchars($hotline); ?>" class="call-btn">
                <i class="fas fa-phone"></i> Call Now
            </a>
        </div>

        <?php if (!empty($message)): ?>
            <div style="background: <?php echo $messageType==='error'?'#fef2f2':'#f0fdf4'; ?>; color: <?php echo $messageType==='error'?'#ef4444':'#10b981'; ?>; padding: 1rem 1.5rem; border-radius: 12px; border: 1px solid <?php echo $messageType==='error'?'#fecaca':'#bbf7d0'; ?>; margin-bottom: 2rem; font-weight: 600; text-align: center; max-width: 800px; margin: 0 auto 2rem;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="req-section">
            <?php if ($active_req): ?>
                <div class="status-tracker">
                    <h4><i class="fas fa-spinner fa-spin"></i> Active Request Found</h4>
                    <p>Reference: <strong><?php echo $active_req['request_id']; ?></strong></p>
                    <p>Location: <?php echo htmlspecialchars($active_req['pickup_location']); ?></p>
                    <div class="status-badge"><?php echo $active_req['status']; ?></div>
                </div>
            <?php else: ?>
                <div class="req-card">
                    <h3><i class="fas fa-location-crosshairs"></i> Request an Ambulance</h3>
                    <?php if (!$is_logged_in): ?>
                        <div style="text-align: center; padding: 2rem 0;">
                            <p style="font-size: 1.1rem; color: var(--lp-text-muted); margin-bottom: 2rem;">To request an ambulance online, please log in to your RMU Medical account so we can access your contact details quickly.</p>
                            <a href="/RMU-Medical-Management-System/php/index.php" class="lp-btn lp-btn-primary" style="background:#ef4444;border-color:#ef4444;font-size:1.1rem;padding:0.8rem 2.5rem;">Log In to Request</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="lp-form-group">
                                <label>Patient Name (Auto-filled)</label>
                                <input type="text" class="lp-form-control" value="<?php echo htmlspecialchars($user_name); ?>" readonly>
                            </div>
                            <div class="lp-form-group">
                                <label>Exact Pickup Location *</label>
                                <input type="text" name="pickup_location" class="lp-form-control" placeholder="E.g., Block B, Room 402, Main Campus" required>
                            </div>
                            <div class="lp-form-group">
                                <label>Nature of Emergency *</label>
                                <select name="emergency_type" class="lp-form-control" required>
                                    <option value="" disabled selected>Select emergency type...</option>
                                    <option value="Severe Injury / Trauma">Severe Injury / Trauma</option>
                                    <option value="Chest Pain / Asthma">Chest Pain / Breathing Issue</option>
                                    <option value="Unconscious / Fainting">Unconscious / Fainting</option>
                                    <option value="Maternity / Labour">Maternity / Labour</option>
                                    <option value="Other Medical Emergency">Other Medical Emergency</option>
                                </select>
                            </div>
                            <div class="lp-form-group">
                                <label>Additional Notes (Optional)</label>
                                <textarea name="notes" class="lp-form-control" rows="3" placeholder="Condition details or directions..."></textarea>
                            </div>
                            <button type="submit" name="request_ambulance" class="lp-btn lp-btn-primary" style="width: 100%; background: #ef4444; border-color: #ef4444; font-size: 1.1rem; padding: 1rem;"><i class="fas fa-paper-plane"></i> Submit Emergency Request</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="lp-text-center lp-mb-4" style="font-size: 2.5rem; font-weight: 800; color: var(--lp-text);">Our Fleet Status</h2>
        <div class="fleet-grid lp-mb-4">
            <?php
            $sql = "SELECT vehicle_number, driver_name, status FROM ambulances ORDER BY status ASC";
            $result = mysqli_query($conn, $sql);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($amb = mysqli_fetch_assoc($result)) {
                    $isAvail = $amb['status'] === 'Available';
                    echo '<div class="fleet-card">';
                    echo '  <div class="f-icon"><i class="fas fa-truck-medical"></i></div>';
                    echo '  <h3 style="font-size:1.3rem; margin-bottom:0.5rem; font-weight:800; color:var(--lp-text);">' . htmlspecialchars($amb['vehicle_number']) . '</h3>';
                    echo '  <p style="color:var(--lp-text-muted); font-size:0.95rem;">Assigned to: ' . ($amb['driver_name'] ? htmlspecialchars($amb['driver_name']) : 'Shift Admin') . '</p>';
                    echo '  <div class="f-status ' . ($isAvail ? 'f-avail' : 'f-busy') . '">' . $amb['status'] . '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p style="text-align: center; color: var(--lp-text-muted); grid-column: 1/-1;">Fleet information is currently unavailable.</p>';
            }
            ?>
        </div>
    </div>

    <?php require_once dirname(__DIR__) . '/includes/footer_landing.php'; ?>
    <?php require_once dirname(__DIR__) . '/includes/chatbot_landing.php'; ?>
    <script src="/RMU-Medical-Management-System/js/landing.js"></script>
    <script src="/RMU-Medical-Management-System/js/landing-chatbot.js"></script>
</body>
</html>