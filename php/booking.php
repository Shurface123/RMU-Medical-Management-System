<?php
session_start();
require_once 'db_conn.php';

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_name = $is_logged_in ? ($_SESSION['name'] ?? 'Patient') : '';
$user_role = $is_logged_in ? ($_SESSION['user_role'] ?? 'patient') : '';

$message = '';
$messageType = '';

// Handle Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking']) && $is_logged_in) {
    // Only patients can cancel their own bookings, or doctors their own
    $b_id = (int)$_POST['booking_id'];
    $c_sql = "UPDATE public_appointment_bookings SET status = 'cancelled' WHERE booking_id = ? AND patient_user_id = ?";
    $stmt = mysqli_prepare($conn, $c_sql);
    mysqli_stmt_bind_param($stmt, "ii", $b_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Booking cancelled successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to cancel booking.";
        $messageType = "error";
    }
}

// Handle New Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking']) && $is_logged_in) {
    $doctor_id = (int)$_POST['doctor_id'];
    $service_id = (int)$_POST['service_id'];
    $app_date = mysqli_real_escape_string($conn, $_POST['pref_date']);
    $app_time = mysqli_real_escape_string($conn, $_POST['pref_time']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');
    
    $ins = "INSERT INTO public_appointment_bookings (patient_user_id, doctor_id, service_id, preferred_date, preferred_time, reason, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())";
    $stmt = mysqli_prepare($conn, $ins);
    mysqli_stmt_bind_param($stmt, "iiisss", $user_id, $doctor_id, $service_id, $app_date, $app_time, $reason);
    
    if (mysqli_stmt_execute($stmt)) {
        $new_booking_id = mysqli_insert_id($conn);
        $message = "Appointment booked successfully! Reference ID: BK-" . str_pad($new_booking_id, 5, '0', STR_PAD_LEFT);
        $messageType = "success";
    } else {
        $message = "Failed to book appointment. Please try again.";
        $messageType = "error";
    }
}

// Fetch Services
$services = [];
$s_res = mysqli_query($conn, "SELECT service_id, name, description, icon_class FROM landing_services WHERE is_active=1 ORDER BY display_order ASC");
if ($s_res) {
    while ($row = mysqli_fetch_assoc($s_res)) {
        $services[] = $row;
    }
}

// Fetch Doctors
$doctors = [];
$d_res = mysqli_query($conn, "
    SELECT d.id, u.name as doctor_name, d.specialization, d.available_days, d.available_hours 
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE u.is_active=1 AND d.approval_status='approved' AND d.is_available=1
");
if ($d_res) {
    while ($row = mysqli_fetch_assoc($d_res)) {
        $doctors[] = $row;
    }
}

// Fetch User's My Bookings
$my_bookings = [];
if ($is_logged_in) {
    $b_sql = "SELECT b.booking_id, b.preferred_date, b.preferred_time, b.status, s.name as service_name, u.name as doctor_name 
              FROM public_appointment_bookings b
              LEFT JOIN landing_services s ON b.service_id = s.service_id
              LEFT JOIN doctors d ON b.doctor_id = d.id
              LEFT JOIN users u ON d.user_id = u.id
              WHERE b.patient_user_id = ? 
              ORDER BY b.preferred_date DESC, b.preferred_time DESC";
    $stmt = mysqli_prepare($conn, $b_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($res)) {
        $my_bookings[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - RMU Sickbay</title>
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/landing.css">
    <style>
        .bk-hero { padding: 9rem 2rem 5rem; text-align: center; background: linear-gradient(135deg, #2563EB, #0ea5e9); color: white; position: relative; overflow: hidden; }
        .bk-hero::after { content: ''; position: absolute; inset: 0; background: url('/RMU-Medical-Management-System/image/pattern.png'); opacity: 0.1; }
        .bk-hero > div { position: relative; z-index: 2; }
        .bk-hero h1 { font-size: clamp(2.5rem, 6vw, 4.5rem); margin-bottom: 0.5rem; font-weight: 800; }
        .bk-hero p { font-size: 1.3rem; opacity: 0.9; }

        .bk-auth-gate {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 24px; padding: 5rem 3rem;
            text-align: center; max-width: 750px; margin: -4rem auto 4rem; position: relative; z-index: 10;
            box-shadow: 0 20px 45px rgba(37, 99, 235, 0.2); border: 1px solid rgba(255,255,255,0.4);
        }
        [data-theme="dark"] .bk-auth-gate { background: rgba(20, 30, 50, 0.9); border-color: rgba(255,255,255,0.05); }
        .bk-auth-gate i { font-size: 5.5rem; color: #2563EB; margin-bottom: 2rem; }
        .bk-auth-gate h2 { font-size: 2.4rem; margin-bottom: 1.2rem; color: var(--lp-text); font-weight: 800; }
        .bk-auth-gate p { font-size: 1.25rem; color: var(--lp-text-muted); margin-bottom: 3rem; line-height: 1.6; }

        .bk-container { max-width: 1000px; margin: -3rem auto 5rem; position: relative; z-index: 10; padding: 0 1rem; }
        
        .bk-form-card {
            background: var(--lp-bg-card); border-radius: 24px; padding: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid var(--lp-border);
        }

        .step-indicator { display: flex; align-items: center; justify-content: space-between; margin-bottom: 3rem; position: relative; }
        .step-indicator::before { content: ''; position: absolute; top: 18px; left: 0; right: 0; height: 3px; background: var(--lp-border); z-index: 0; }
        .step-progress { position: absolute; top: 18px; left: 0; height: 3px; background: var(--lp-primary); z-index: 0; transition: width 0.3s; }
        .step-dot { position: relative; z-index: 1; text-align: center; }
        .dot { width: 40px; height: 40px; border-radius: 50%; background: var(--lp-bg-card); border: 3px solid var(--lp-border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--lp-text-muted); margin: 0 auto 0.5rem; transition: all 0.3s; }
        .step-dot.active .dot { border-color: var(--lp-primary); background: var(--lp-primary); color: white; }
        .step-dot.completed .dot { border-color: var(--lp-primary); background: var(--lp-primary); color: white; }
        .step-label { font-size: 0.85rem; font-weight: 600; color: var(--lp-text-muted); position: absolute; left: 50%; transform: translateX(-50%); width: 100px; }

        .bk-step-content { display: none; }
        .bk-step-content.active { display: block; animation: fadeIn 0.4s; }
        
        .svc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .svc-sel {
            border: 2px solid var(--lp-border); border-radius: 16px; padding: 1.5rem; cursor: pointer; text-align: center;
            transition: all 0.2s; background: var(--lp-bg);
        }
        .svc-sel:hover { border-color: var(--lp-primary); background: var(--lp-primary-bg); }
        .svc-sel.active { border-color: var(--lp-primary); background: var(--lp-primary-bg); box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }
        .svc-icon { font-size: 2.5rem; margin-bottom: 1rem; color: var(--lp-primary); }
        .svc-sel h4 { font-size: 1.2rem; font-weight: 700; color: var(--lp-text); margin-bottom: 0.5rem; }
        
        .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .doc-sel { border: 2px solid var(--lp-border); border-radius: 16px; padding: 1.5rem; cursor: pointer; transition: all 0.2s; background: var(--lp-bg); display: flex; gap: 1.2rem; align-items: center; }
        .doc-sel:hover { border-color: var(--lp-primary); }
        .doc-sel.active { border-color: var(--lp-primary); background: var(--lp-primary-bg); }
        .doc-avatar { width: 64px; height: 64px; border-radius: 50%; background: rgba(37,99,235,0.1); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #2563EB; flex-shrink: 0; }
        
        .lp-input { width: 100%; padding: 1rem 1.2rem; border: 2px solid var(--lp-border); border-radius: 12px; background: var(--lp-bg); color: var(--lp-text); font-size: 1rem; margin-bottom: 1.5rem; font-family: inherit; }
        .lp-input:focus { border-color: var(--lp-primary); outline: none; }
        .lp-label { display: block; font-weight: 600; margin-bottom: 0.6rem; color: var(--lp-text); }
        
        .bk-nav-btn { display: flex; justify-content: space-between; margin-top: 2.5rem; border-top: 1px solid var(--lp-border); padding-top: 1.5rem; }

        .bk-summary { background: var(--lp-bg); border-radius: 16px; padding: 2rem; border: 1px solid var(--lp-border); margin-bottom: 2rem; }
        .sum-row { display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid var(--lp-border); }
        .sum-row:last-child { border-bottom: none; }
        .sum-lbl { font-weight: 600; color: var(--lp-text-muted); }
        .sum-val { font-weight: 800; color: var(--lp-text); }

        .my-bookings { margin-top: 5rem; }
        .my-bookings h2 { font-size: 2rem; font-weight: 800; margin-bottom: 2rem; color: var(--lp-text); }
        .booking-row { background: var(--lp-bg-card); border-radius: 16px; padding: 1.5rem; margin-bottom: 1rem; border: 1px solid var(--lp-border); display: flex; align-items: center; justify-content: space-between; }
        .b-date { font-size: 1.1rem; font-weight: 800; color: var(--lp-text); }
        .b-det { font-size: 0.95rem; color: var(--lp-text-muted); }
        .b-status { padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; }
        .st-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .st-confirmed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .st-cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        @media (max-width: 768px) {
            .booking-row { flex-direction: column; align-items: flex-start; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div id="lpAnnouncements"></div>
    <?php
    $active_page = 'booking';
    $_base = '/RMU-Medical-Management-System';
    require_once __DIR__ . '/includes/nav_landing.php';
    ?>

    <section class="bk-hero">
        <div class="lp-container">
            <h1><i class="fas fa-calendar-check"></i> Book an Appointment</h1>
            <p>Schedule your visit with our medical experts quickly and easily</p>
        </div>
    </section>

    <div class="bk-container">
        <?php if (!empty($message)): ?>
            <div style="background: <?php echo $messageType==='error'?'#fef2f2':'#f0fdf4'; ?>; color: <?php echo $messageType==='error'?'#ef4444':'#10b981'; ?>; padding: 1rem 1.5rem; border-radius: 12px; border: 1px solid <?php echo $messageType==='error'?'#fecaca':'#bbf7d0'; ?>; margin-bottom: 2rem; font-weight: 600; text-align: center;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$is_logged_in): ?>
            <div class="bk-auth-gate">
                <i class="fas fa-user-lock"></i>
                <h2>Authentication Required</h2>
                <p>To securely book an appointment and access your medical history, please log in to your RMU Medical account.</p>
                <div style="display:flex; justify-content:center; gap:1.5rem; flex-wrap:wrap;">
                    <a href="/RMU-Medical-Management-System/php/index.php" style="font-size:1.15rem; font-weight:700; padding:1rem 3rem; background:#2563EB; color:#fff; border-radius:12px; text-decoration:none; box-shadow:0 8px 20px rgba(37,99,235,0.3); transition:transform 0.2s;">Log In Now</a>
                    <a href="/RMU-Medical-Management-System/php/register.php" style="font-size:1.15rem; font-weight:700; padding:1rem 3rem; background:transparent; color:#2563EB; border:2px solid #2563EB; border-radius:12px; text-decoration:none; transition:background 0.2s;">Register</a>
                </div>
            </div>
        <?php else: ?>
            <div class="bk-form-card">
                <div class="step-indicator">
                    <div class="step-progress" id="stepProgress" style="width: 0%;"></div>
                    <div class="step-dot active" id="dot-1"><div class="dot">1</div><div class="step-label">Service</div></div>
                    <div class="step-dot" id="dot-2"><div class="dot">2</div><div class="step-label">Doctor</div></div>
                    <div class="step-dot" id="dot-3"><div class="dot">3</div><div class="step-label">Date</div></div>
                    <div class="step-dot" id="dot-4"><div class="dot">4</div><div class="step-label">Details</div></div>
                    <div class="step-dot" id="dot-5"><div class="dot">5</div><div class="step-label">Confirm</div></div>
                </div>

                <form method="POST" action="" id="bookingForm">
                    <!-- HIDDEN INPUTS -->
                    <input type="hidden" name="service_id" id="inp_service_id" required>
                    <input type="hidden" name="doctor_id" id="inp_doctor_id" required>
                    <input type="hidden" name="submit_booking" value="1">

                    <!-- STEP 1 -->
                    <div class="bk-step-content active" id="step-1">
                        <h3 style="font-size:1.5rem; margin-bottom:1.5rem; font-weight:800;"><i class="fas fa-stethoscope" style="color:#2563EB;"></i> Select Service</h3>
                        <div class="svc-grid">
                            <?php foreach($services as $svc): ?>
                            <div class="svc-sel" onclick="selectService(this, <?php echo $svc['service_id']; ?>, '<?php echo htmlspecialchars($svc['name']); ?>')">
                                <div class="svc-icon"><i class="<?php echo htmlspecialchars($svc['icon_class'] ?: 'fas fa-notes-medical'); ?>"></i></div>
                                <h4><?php echo htmlspecialchars($svc['name']); ?></h4>
                                <p style="font-size:0.85rem; color:var(--lp-text-muted);"><?php echo htmlspecialchars($svc['description']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="bk-nav-btn">
                            <div></div>
                            <button type="button" class="lp-btn lp-btn-solid" onclick="nextStep(1)">Next Step <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- STEP 2 -->
                    <div class="bk-step-content" id="step-2">
                        <h3 style="font-size:1.5rem; margin-bottom:1.5rem; font-weight:800;"><i class="fas fa-user-doctor" style="color:#2563EB;"></i> Select Doctor</h3>
                        <div class="doc-grid">
                            <?php foreach($doctors as $doc): ?>
                            <div class="doc-sel" onclick="selectDoctor(this, <?php echo $doc['id']; ?>, 'Dr. <?php echo htmlspecialchars(addslashes($doc['doctor_name'])); ?>')">
                                <div class="doc-avatar"><i class="fas fa-user-md"></i></div>
                                <div>
                                    <h4 style="font-weight:800; font-size:1.1rem; color:var(--lp-text);">Dr. <?php echo htmlspecialchars($doc['doctor_name']); ?></h4>
                                    <p style="font-size:0.85rem; color:var(--lp-primary); font-weight:600; margin-bottom:0.2rem;"><?php echo htmlspecialchars($doc['specialization'] ?: 'General Practice'); ?></p>
                                    <p style="font-size:0.8rem; color:var(--lp-text-muted);"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($doc['available_days'] ?: 'Mon-Fri'); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="bk-nav-btn">
                            <button type="button" class="lp-btn lp-btn-outline" onclick="prevStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
                            <button type="button" class="lp-btn lp-btn-solid" onclick="nextStep(2)">Next Step <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- STEP 3 -->
                    <div class="bk-step-content" id="step-3">
                        <h3 style="font-size:1.5rem; margin-bottom:1.5rem; font-weight:800;"><i class="fas fa-calendar" style="color:#2563EB;"></i> Choose Date & Time</h3>
                        <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
                            <div style="flex:1; min-width:260px;">
                                <label class="lp-label">Preferred Date *</label>
                                <input type="date" name="pref_date" id="pref_date" class="lp-input" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div style="flex:1; min-width:260px;">
                                <label class="lp-label">Preferred Time Slot *</label>
                                <select name="pref_time" id="pref_time" class="lp-input" required>
                                    <option value="" disabled selected>Select a time slot</option>
                                    <option value="08:00:00">08:00 AM - 09:00 AM</option>
                                    <option value="09:00:00">09:00 AM - 10:00 AM</option>
                                    <option value="10:00:00">10:00 AM - 11:00 AM</option>
                                    <option value="11:30:00">11:30 AM - 12:30 PM</option>
                                    <option value="13:30:00">01:30 PM - 02:30 PM</option>
                                    <option value="14:30:00">02:30 PM - 03:30 PM</option>
                                    <option value="15:30:00">03:30 PM - 04:30 PM</option>
                                </select>
                            </div>
                        </div>
                        <div class="bk-nav-btn">
                            <button type="button" class="lp-btn lp-btn-outline" onclick="prevStep(3)"><i class="fas fa-arrow-left"></i> Back</button>
                            <button type="button" class="lp-btn lp-btn-solid" onclick="nextStep(3)">Next Step <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- STEP 4 -->
                    <div class="bk-step-content" id="step-4">
                        <h3 style="font-size:1.5rem; margin-bottom:1.5rem; font-weight:800;"><i class="fas fa-notes-medical" style="color:#2563EB;"></i> Reason for Visit</h3>
                        <label class="lp-label">Describe your symptoms or reason for booking</label>
                        <textarea name="reason" id="reason_text" class="lp-input" rows="4" placeholder="Optional brief description..."></textarea>
                        
                        <div class="bk-nav-btn">
                            <button type="button" class="lp-btn lp-btn-outline" onclick="prevStep(4)"><i class="fas fa-arrow-left"></i> Back</button>
                            <button type="button" class="lp-btn lp-btn-solid" onclick="nextStep(4)">Review <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- STEP 5 -->
                    <div class="bk-step-content" id="step-5">
                        <h3 style="font-size:1.5rem; margin-bottom:1.5rem; font-weight:800;"><i class="fas fa-check-circle" style="color:#2563EB;"></i> Confirm Details</h3>
                        <div class="bk-summary">
                            <div class="sum-row"><span class="sum-lbl">Service</span><span class="sum-val" id="sum-svc"></span></div>
                            <div class="sum-row"><span class="sum-lbl">Doctor</span><span class="sum-val" id="sum-doc"></span></div>
                            <div class="sum-row"><span class="sum-lbl">Date</span><span class="sum-val" id="sum-date"></span></div>
                            <div class="sum-row"><span class="sum-lbl">Time</span><span class="sum-val" id="sum-time"></span></div>
                        </div>
                        
                        <div class="bk-nav-btn">
                            <button type="button" class="lp-btn lp-btn-outline" onclick="prevStep(5)"><i class="fas fa-arrow-left"></i> Back</button>
                            <button type="submit" class="lp-btn lp-btn-solid" style="background:#10b981; border-color:#10b981;"><i class="fas fa-paper-plane"></i> Confirm Booking</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- MY BOOKINGS -->
            <div class="my-bookings">
                <h2>My Bookings</h2>
                <?php if (empty($my_bookings)): ?>
                    <p style="color:var(--lp-text-muted);">You have no previous bookings.</p>
                <?php else: ?>
                    <?php foreach($my_bookings as $b): ?>
                    <div class="booking-row">
                        <div>
                            <div class="b-date"><i class="fas fa-calendar" style="color:#2563EB;margin-right:0.5rem;"></i> <?php echo date('D, M d Y', strtotime($b['preferred_date'])); ?> @ <?php echo date('h:i A', strtotime($b['preferred_time'])); ?></div>
                            <div class="b-det">Dr. <?php echo htmlspecialchars($b['doctor_name']); ?> • <?php echo htmlspecialchars($b['service_name']); ?></div>
                            <div class="b-det" style="font-size:0.8rem;">Ref: BK-<?php echo str_pad($b['booking_id'], 5, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        <div style="display:flex; align-items:center; gap:1.5rem;">
                            <span class="b-status st-<?php echo htmlspecialchars($b['status']); ?>"><?php echo htmlspecialchars($b['status']); ?></span>
                            <?php if ($b['status'] === 'pending'): ?>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                <button type="submit" name="cancel_booking" class="lp-btn lp-btn-outline" style="padding:0.4rem 1rem; border-color:#ef4444; color:#ef4444; font-size:0.85rem;"><i class="fas fa-times"></i> Cancel</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php require_once __DIR__ . '/includes/footer_landing.php'; ?>
    <?php require_once __DIR__ . '/includes/chatbot_landing.php'; ?>
    <script src="/RMU-Medical-Management-System/js/landing.js"></script>
    <script src="/RMU-Medical-Management-System/js/landing-chatbot.js"></script>
    <script>
        let currStep = 1;
        let p_svc='', p_doc='';

        function selectService(elem, id, name) {
            document.querySelectorAll('.svc-sel').forEach(el=>el.classList.remove('active'));
            elem.classList.add('active');
            document.getElementById('inp_service_id').value = id;
            p_svc = name;
        }

        function selectDoctor(elem, id, name) {
            document.querySelectorAll('.doc-sel').forEach(el=>el.classList.remove('active'));
            elem.classList.add('active');
            document.getElementById('inp_doctor_id').value = id;
            p_doc = name;
        }

        function nextStep(step) {
            // Validation
            if(step===1 && !document.getElementById('inp_service_id').value) return alert('Please select a service.');
            if(step===2 && !document.getElementById('inp_doctor_id').value) return alert('Please select a doctor.');
            if(step===3) {
                if(!document.getElementById('pref_date').value) return alert('Please choose a date.');
                if(!document.getElementById('pref_time').value) return alert('Please choose a time slot.');
            }

            if(step===4) {
                // Populate summary
                document.getElementById('sum-svc').textContent = p_svc;
                document.getElementById('sum-doc').textContent = p_doc;
                document.getElementById('sum-date').textContent = document.getElementById('pref_date').value;
                document.getElementById('sum-time').textContent = document.getElementById('pref_time').options[document.getElementById('pref_time').selectedIndex].text;
            }

            document.getElementById('step-'+step).classList.remove('active');
            document.getElementById('dot-'+step).classList.remove('active');
            document.getElementById('dot-'+step).classList.add('completed');
            
            currStep = step+1;
            document.getElementById('step-'+currStep).classList.add('active');
            document.getElementById('dot-'+currStep).classList.add('active');
            document.getElementById('stepProgress').style.width = ((currStep-1)*25) + '%';
        }

        function prevStep(step) {
            document.getElementById('step-'+step).classList.remove('active');
            document.getElementById('dot-'+step).classList.remove('active');
            
            currStep = step-1;
            document.getElementById('dot-'+currStep).classList.remove('completed');
            document.getElementById('dot-'+currStep).classList.add('active');
            document.getElementById('step-'+currStep).classList.add('active');
            document.getElementById('stepProgress').style.width = ((currStep-1)*25) + '%';
        }
    </script>
</body>
</html>
