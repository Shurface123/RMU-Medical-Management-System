<?php
session_start();
require_once 'db_conn.php';

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_name = $is_logged_in ? ($_SESSION['name'] ?? 'Patient') : '';
$user_role = $_SESSION['user_role'] ?? 'patient';

$message = '';
$messageType = '';

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
while ($row = mysqli_fetch_assoc($s_res)) $services[] = $row;

// Fetch Doctors
$doctors = [];
$d_res = mysqli_query($conn, "
    SELECT d.id, u.name as doctor_name, d.specialization, d.available_days, d.available_hours 
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE u.is_active=1 AND d.approval_status='approved' AND d.is_available=1
");
while ($row = mysqli_fetch_assoc($d_res)) $doctors[] = $row;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Center | RMU Healthcare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563EB;
            --secondary: #0ea5e9;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --radius-xl: 32px;
            --radius-lg: 20px;
            --shadow-premium: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
        }

        [data-theme="dark"] {
            --surface: #0f172a;
            --surface-2: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border: #334155;
        }

        body { font-family: 'Outfit', sans-serif; background: var(--surface-2); color: var(--text-primary); margin: 0; line-height: 1.5; }

        .bk-hero {
            padding: 8rem 2rem 10rem;
            text-align: center;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            position: relative;
            clip-path: ellipse(150% 100% at 50% 0%);
        }

        .bk-container { max-width: 1100px; margin: -6rem auto 5rem; padding: 0 1.5rem; position: relative; z-index: 10; }

        .glass-card {
            background: rgba(var(--surface), 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 4rem;
            box-shadow: var(--shadow-premium);
        }
        [data-theme="light"] .glass-card { background: rgba(255, 255, 255, 0.9); }
        [data-theme="dark"] .glass-card { background: rgba(30, 41, 59, 0.7); }

        /* Stepper UI */
        .stepper { display: flex; justify-content: space-between; margin-bottom: 5rem; position: relative; }
        .stepper::before { content: ''; position: absolute; top: 22px; left: 0; width: 100%; height: 2px; background: var(--border); z-index: 0; }
        .step-progress { position: absolute; top: 22px; left: 0; height: 2px; background: var(--primary); z-index: 1; transition: 0.5s ease; }
        
        .step-item { position: relative; z-index: 2; text-align: center; width: 44px; }
        .step-circle { 
            width: 44px; height: 44px; border-radius: 14px; background: var(--surface); border: 2px solid var(--border);
            display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem;
            transition: 0.3s; color: var(--text-secondary);
        }
        .step-item.active .step-circle { border-color: var(--primary); background: var(--primary); color: white; transform: scale(1.1); box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2); }
        .step-item.completed .step-circle { border-color: var(--primary); background: var(--primary); color: white; }
        .step-label { position: absolute; top: 55px; left: 50%; transform: translateX(-50%); white-space: nowrap; font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
        .step-item.active .step-label { color: var(--primary); }

        .bk-pane { display: none; animation: paneIn 0.5s ease-out; }
        .bk-pane.active { display: block; }
        @keyframes paneIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* Grid Item Selectors */
        .grid-selector { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .select-item {
            background: var(--surface-2); border: 2px solid transparent; border-radius: 20px; padding: 2rem;
            cursor: pointer; transition: 0.2s; position: relative;
        }
        .select-item:hover { transform: translateY(-5px); border-color: var(--primary); }
        .select-item.active { border-color: var(--primary); background: rgba(37, 99, 235, 0.05); }
        .select-item.active::after { content: '\f058'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; top: 1rem; right: 1rem; color: var(--primary); font-size: 1.2rem; }

        .item-icon { font-size: 2.5rem; color: var(--primary); margin-bottom: 1.2rem; }
        .item-title { font-weight: 800; font-size: 1.2rem; margin-bottom: 0.5rem; }
        .item-desc { font-size: 0.95rem; color: var(--text-secondary); }

        /* Form Inputs */
        .form-group { margin-bottom: 2rem; }
        .form-label { display: block; font-weight: 700; margin-bottom: 0.8rem; color: var(--text-primary); }
        .form-control {
            width: 100%; padding: 1.2rem; border-radius: 15px; border: 2px solid var(--border); background: var(--surface);
            color: var(--text-primary); font-family: inherit; font-size: 1.1rem; transition: 0.2s; box-sizing: border-box;
        }
        .form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }

        .btn {
            padding: 1.2rem 2.5rem; border-radius: 15px; font-weight: 800; font-size: 1.1rem; cursor: pointer;
            transition: 0.2s; display: inline-flex; align-items: center; gap: 0.8rem; border: none;
        }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2); }
        .btn-primary:hover { transform: translateY(-3px); background: #1d4ed8; }
        .btn-outline { background: transparent; border: 2px solid var(--border); color: var(--text-primary); }
        .btn-outline:hover { background: var(--border); }

        .summary-card { background: var(--surface-2); border-radius: 20px; padding: 2.5rem; border: 1px solid var(--border); }
        .summary-row { display: flex; justify-content: space-between; padding: 1.2rem 0; border-bottom: 1px solid var(--border); }
        .summary-row:last-child { border-bottom: none; }
        .summary-label { font-weight: 600; color: var(--text-secondary); }
        .summary-value { font-weight: 800; color: var(--text-primary); }

        /* Header / Nav simulation */
        .bk-nav {
            position: absolute; top: 0; left: 0; right: 0; padding: 2rem; display: flex; justify-content: space-between; align-items: center; z-index: 100;
        }
        .logo { font-size: 1.5rem; font-weight: 900; color: white; text-decoration: none; display: flex; align-items: center; gap: 0.8rem; }
    </style>
</head>
<body>

    <nav class="bk-nav">
        <a href="/RMU-Medical-Management-System/index.php" class="logo">
            <img src="/RMU-Medical-Management-System/image/logo-ju-small.png" height="40" alt="Logo">
            RMU HEALTHCARE
        </a>
        <div style="display:flex; gap:1.5rem; align-items:center;">
            <?php if($is_logged_in): ?>
                <span style="color:white; font-weight:600; opacity:0.8;">Welcome, <?= htmlspecialchars($user_name) ?></span>
                <a href="/RMU-Medical-Management-System/php/logout.php" class="btn btn-outline" style="color:white; border-color:rgba(255,255,255,0.3); padding:0.6rem 1.5rem; font-size:0.9rem;">Sign Out</a>
            <?php else: ?>
                <a href="/RMU-Medical-Management-System/php/index.php" class="btn btn-primary" style="padding:0.6rem 2rem; font-size:0.9rem;">Sign In</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="bk-hero">
        <div style="max-width:800px; margin:0 auto;">
            <h1 style="font-size:3.5rem; font-weight:900; margin-bottom:1rem; letter-spacing:-2px;">Clinical Reservation</h1>
            <p style="font-size:1.3rem; opacity:0.7;">Secure your medical appointment through our synchronized booking gateway.</p>
        </div>
    </section>

    <div class="bk-container">
        <div class="glass-card">
            <?php if(!$is_logged_in): ?>
                <div style="text-align:center; padding:3rem 0;">
                    <i class="fas fa-user-shield" style="font-size:5rem; color:var(--primary); margin-bottom:2rem; opacity:0.3;"></i>
                    <h2 style="font-size:2.2rem; font-weight:800; margin-bottom:1rem;">Authentication Required</h2>
                    <p style="color:var(--text-secondary); font-size:1.2rem; margin-bottom:3rem; max-width:500px; margin-left:auto; margin-right:auto;">Please authenticate your credentials to access the clinical booking system and sync your medical records.</p>
                    <div style="display:flex; justify-content:center; gap:1.5rem;">
                        <a href="/RMU-Medical-Management-System/php/index.php" class="btn btn-primary" style="padding:1.2rem 3.5rem;">Access Secure Login</a>
                        <a href="/RMU-Medical-Management-System/php/register.php" class="btn btn-outline" style="padding:1.2rem 3.5rem;">Establish New Account</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="stepper">
                    <div class="step-progress" id="stepProgress" style="width: 0%;"></div>
                    <div class="step-item active" id="step1-dot"><div class="step-circle">1</div><div class="step-label">Clinical Service</div></div>
                    <div class="step-item" id="step2-dot"><div class="step-circle">2</div><div class="step-label">Practitioner</div></div>
                    <div class="step-item" id="step3-dot"><div class="step-circle">3</div><div class="step-label">Schedule</div></div>
                    <div class="step-item" id="step4-dot"><div class="step-circle">4</div><div class="step-label">Confirmation</div></div>
                </div>

                <form method="POST" id="bookingForm">
                    <input type="hidden" name="service_id" id="inp_service_id" required>
                    <input type="hidden" name="doctor_id" id="inp_doctor_id" required>
                    <input type="hidden" name="submit_booking" value="1">

                    <!-- STEP 1: SERVICE -->
                    <div class="bk-pane active" id="pane-1">
                        <h2 style="font-size:1.8rem; font-weight:900; margin-bottom:2.5rem; display:flex; align-items:center; gap:1rem;"><i class="fas fa-stethoscope" style="color:var(--primary);"></i> Select Medical Service</h2>
                        <div class="grid-selector">
                            <?php foreach($services as $svc): ?>
                            <div class="select-item" onclick="selectService(this, <?= $svc['service_id'] ?>, '<?= htmlspecialchars($svc['name']) ?>')">
                                <div class="item-icon"><i class="<?= $svc['icon_class'] ?: 'fas fa-notes-medical' ?>"></i></div>
                                <div class="item-title"><?= htmlspecialchars($svc['name']) ?></div>
                                <div class="item-desc"><?= htmlspecialchars($svc['description']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:4rem; display:flex; justify-content:flex-end;">
                            <button type="button" class="btn btn-primary" onclick="nextStep(1)">Proceed to Practitioner <i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>

                    <!-- STEP 2: DOCTOR -->
                    <div class="bk-pane" id="pane-2">
                        <h2 style="font-size:1.8rem; font-weight:900; margin-bottom:2.5rem; display:flex; align-items:center; gap:1rem;"><i class="fas fa-user-md" style="color:var(--primary);"></i> Choose Clinical Expert</h2>
                        <div class="grid-selector">
                            <?php foreach($doctors as $doc): ?>
                            <div class="select-item" onclick="selectDoctor(this, <?= $doc['id'] ?>, 'Dr. <?= htmlspecialchars(addslashes($doc['doctor_name'])) ?>')">
                                <div style="display:flex; align-items:center; gap:1.5rem;">
                                    <div style="width:60px; height:60px; border-radius:15px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.8rem;"><i class="fas fa-user-doctor"></i></div>
                                    <div>
                                        <div class="item-title" style="margin:0;">Dr. <?= htmlspecialchars($doc['doctor_name']) ?></div>
                                        <div style="font-size:0.9rem; color:var(--primary); font-weight:700; text-transform:uppercase;"><?= htmlspecialchars($doc['specialization']) ?></div>
                                    </div>
                                </div>
                                <div style="margin-top:1.2rem; font-size:0.85rem; color:var(--text-secondary); font-weight:600;"><i class="fas fa-clock"></i> <?= $doc['available_days'] ?> | <?= $doc['available_hours'] ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:4rem; display:flex; justify-content:space-between;">
                            <button type="button" class="btn btn-outline" onclick="prevStep(2)"><i class="fas fa-chevron-left"></i> Back</button>
                            <button type="button" class="btn btn-primary" onclick="nextStep(2)">Schedule Slot <i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>

                    <!-- STEP 3: DATE & TIME -->
                    <div class="bk-pane" id="pane-3">
                        <h2 style="font-size:1.8rem; font-weight:900; margin-bottom:2.5rem; display:flex; align-items:center; gap:1rem;"><i class="fas fa-calendar-alt" style="color:var(--primary);"></i> Select Temporal Slot</h2>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem;">
                            <div class="form-group">
                                <label class="form-label">Preferred Date</label>
                                <input type="date" name="pref_date" id="pref_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Consultation Window</label>
                                <select name="pref_time" id="pref_time" class="form-control" required>
                                    <option value="" disabled selected>Select a time window</option>
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
                        <div class="form-group">
                            <label class="form-label">Reason for Consultation (Optional)</label>
                            <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Briefly describe your symptoms or objective..."></textarea>
                        </div>
                        <div style="margin-top:4rem; display:flex; justify-content:space-between;">
                            <button type="button" class="btn btn-outline" onclick="prevStep(3)"><i class="fas fa-chevron-left"></i> Back</button>
                            <button type="button" class="btn btn-primary" onclick="nextStep(3)">Verify Details <i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>

                    <!-- STEP 4: CONFIRM -->
                    <div class="bk-pane" id="pane-4">
                        <h2 style="font-size:1.8rem; font-weight:900; margin-bottom:2.5rem; display:flex; align-items:center; gap:1rem;"><i class="fas fa-check-double" style="color:var(--success);"></i> Review Submission</h2>
                        <div class="summary-card">
                            <div class="summary-row"><span class="summary-label">Patient Identity</span><span class="summary-value"><?= htmlspecialchars($user_name) ?></span></div>
                            <div class="summary-row"><span class="summary-label">Medical Service</span><span class="summary-value" id="sum-svc">---</span></div>
                            <div class="summary-row"><span class="summary-label">Assigned Specialist</span><span class="summary-value" id="sum-doc">---</span></div>
                            <div class="summary-row"><span class="summary-label">Scheduled Date</span><span class="summary-value" id="sum-date">---</span></div>
                            <div class="summary-row"><span class="summary-label">Consultation Window</span><span class="summary-value" id="sum-time">---</span></div>
                        </div>
                        <div style="margin-top:4rem; display:flex; justify-content:space-between;">
                            <button type="button" class="btn btn-outline" onclick="prevStep(4)"><i class="fas fa-chevron-left"></i> Back</button>
                            <button type="submit" class="btn btn-primary" style="background:var(--success); border-color:var(--success); padding:1.2rem 4rem;">Confirm Clinical Booking <i class="fas fa-paper-plane"></i></button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <?php if($is_logged_in): ?>
        <div style="margin-top:5rem;">
            <h2 style="font-size:2.2rem; font-weight:900; margin-bottom:2rem; letter-spacing:-1px;">My Recent Bookings</h2>
            <div style="display:grid; gap:1.5rem;">
                <?php
                $b_sql = "SELECT b.*, s.name as service_name, u.name as doctor_name 
                          FROM public_appointment_bookings b
                          LEFT JOIN landing_services s ON b.service_id = s.service_id
                          LEFT JOIN doctors d ON b.doctor_id = d.id
                          LEFT JOIN users u ON d.user_id = u.id
                          WHERE b.patient_user_id = ? 
                          ORDER BY b.created_at DESC LIMIT 5";
                $stmt = mysqli_prepare($conn, $b_sql);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while($b = mysqli_fetch_assoc($res)):
                    $s_cls = ($b['status'] === 'pending') ? '#f59e0b' : (($b['status'] === 'confirmed') ? '#10b981' : '#ef4444');
                ?>
                <div class="glass-card" style="padding:2rem; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:1.2rem; font-weight:800; color:var(--text-primary);"><?= htmlspecialchars($b['service_name']) ?></div>
                        <div style="color:var(--text-secondary); font-weight:600; font-size:1rem; margin-top:0.3rem;">with Dr. <?= htmlspecialchars($b['doctor_name']) ?></div>
                        <div style="font-size:0.9rem; color:var(--text-muted); margin-top:0.5rem;"><i class="far fa-clock"></i> <?= date('d M Y', strtotime($b['preferred_date'])) ?> @ <?= date('h:i A', strtotime($b['preferred_time'])) ?></div>
                    </div>
                    <div style="text-align:right;">
                        <span style="background:<?= $s_cls ?>11; color:<?= $s_cls ?>; padding:0.6rem 1.2rem; border-radius:10px; font-weight:800; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px;"><?= $b['status'] ?></span>
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.8rem; font-weight:700;">REF: BK-<?= str_pad($b['booking_id'], 5, '0', STR_PAD_LEFT) ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        let p_svc = '', p_doc = '';

        function selectService(elem, id, name) {
            $('.select-item').removeClass('active');
            $(elem).addClass('active');
            $('#inp_service_id').val(id);
            p_svc = name;
        }

        function selectDoctor(elem, id, name) {
            $(elem).closest('.grid-selector').find('.select-item').removeClass('active');
            $(elem).addClass('active');
            $('#inp_doctor_id').val(id);
            p_doc = name;
        }

        function nextStep(step) {
            if(step === 1 && !$('#inp_service_id').val()) return alert('Please select a medical service.');
            if(step === 2 && !$('#inp_doctor_id').val()) return alert('Please select a clinical practitioner.');
            if(step === 3) {
                if(!$('#pref_date').val()) return alert('Please choose a preferred date.');
                if(!$('#pref_time').val()) return alert('Please choose a time window.');
                
                // Populate Summary
                $('#sum-svc').text(p_svc);
                $('#sum-doc').text(p_doc);
                $('#sum-date').text($('#pref_date').val());
                $('#sum-time').text($('#pref_time option:selected').text());
            }

            $(`#pane-${step}`).removeClass('active');
            $(`#step${step}-dot`).removeClass('active').addClass('completed');
            
            const next = step + 1;
            $(`#pane-${next}`).addClass('active');
            $(`#step${next}-dot`).addClass('active');
            $('#stepProgress').css('width', ((next - 1) * 33.33) + '%');
        }

        function prevStep(step) {
            $(`#pane-${step}`).removeClass('active');
            $(`#step${step}-dot`).removeClass('active');
            
            const prev = step - 1;
            $(`#pane-${prev}`).addClass('active');
            $(`#step${prev}-dot`).removeClass('completed').addClass('active');
            $('#stepProgress').css('width', ((prev - 1) * 33.33) + '%');
        }

        // Initialize Theme from localStorage
        const savedTheme = localStorage.getItem('rmu_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>
