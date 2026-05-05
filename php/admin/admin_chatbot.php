<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'chatbot';
$page_title  = 'Chatbot AI Manager';
$message = '';

// Determine row count
$count_res = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM chatbot_knowledge_base"));
$kb_count = (int)$count_res[0];

// Handle Seed if 0
if ($kb_count === 0 && isset($_POST['action']) && $_POST['action'] === 'seed') {
    $seed_data = [
        // GENERAL 
        ['General', 'greeting', '["hi","hello","hey","good morning","good afternoon"]', '["Hello there! How can I assist you at RMU Sickbay today?","Hi! Welcome to RMU Sickbay. What do you need help with?"]', 'Would you like to book an appointment or check our services?'],
        ['General', 'about', '["who are you","what is this","chatbot","system"]', '["I am the RMU Medical Sickbay AI Assistant. I can help you with bookings, finding doctors, and learning about our services.","I am an automated assistant for RMU Medical!"]', 'Type "services" to see what we offer.'],
        ['General', 'hours', '["time","open","close","working hours","when"]', '["We operate from Monday to Friday, 8am to 8pm, and weekends 9am to 5pm. Emergency is 24/7.","Our general outpatient hours are 8am-8pm on weekdays!"]', 'Do you need our emergency contact?'],
        ['General', 'location', '["where","location","address","map","find"]', '["We are located within the Regional Maritime University campus in Nungua, Accra.","You can find the sickbay right on the RMU campus."]','Want me to show you the contact numbers?'],
        ['General', 'contact', '["phone","number","call","email","contact"]', '["You can reach us at 0302716071 or sickbay@rmu.edu.gh.","Our front desk phone is 0302716071."]','Say "emergency" for our rapid hotline.'],
        ['General', 'payment', '["pay","money","cash","insurance","cost"]', '["We accept cash, mobile money, and major insurance cards including NHIS.","Payments can be made via Paystack online or at the counter."]','Would you like to speak to finance?'],
        ['General', 'wifi', '["internet","wifi","password"]', '["We offer free guest Wi-Fi in the waiting area. Please ask the front desk for the passcode.","Guest WiFi is available!"]',''],
        ['General', 'parking', '["park","car","vehicle"]', '["Dedicated parking is available for patients right in front of the clinic.","We have ample parking space."]',''],
        ['General', 'pharmacy_hours', '["pharmacy open","drug store"]', '["The pharmacy is open 24/7 alongside the emergency ward.","You can get medications 24/7."]',''],
        ['General', 'thanks', '["thank","thanks","appreciate","bye"]', '["You are very welcome! Have a healthy day.","Glad I could help. Goodbye!"]',''],
        // APPOINTMENTS
        ['Appointments', 'book', '["book","schedule","appointment","see doctor"]', '["You can book an appointment easily by clicking the Book Appointment link at the top of the page.","Head over to the Booking portal to schedule a visit!"]', 'Click the Book Appointment button to start.'],
        ['Appointments', 'cancel', '["cancel","delete","remove","stop"]', '["To cancel an appointment, please log in and visit the My Bookings section.","You can cancel via your patient dashboard."]',''],
        ['Appointments', 'reschedule', '["change","reschedule","move","postpone"]', '["Currently, you must cancel your existing appointment and book a new one to reschedule.","Please cancel the active one and re-book."]',''],
        ['Appointments', 'cost', '["fee","consultation fee","price"]', '["General consultation fees start at GHS 50, but vary by specialist. Students are covered by school fees.","Consultations vary by doctor."]',''],
        ['Appointments', 'doctors', '["who","list doctors","available doctors"]', '["We have general practitioners and specialists available. Check the Our Doctors page!","Our doctors schedule is posted on the Booking page."]',''],
        ['Appointments', 'walkin', '["walk","walk in","without appointment"]', '["Yes, walk-ins are accepted, but booked appointments are prioritized.","Walk-ins are fine but you might wait longer."]',''],
        ['Appointments', 'wait_time', '["long","wait","time"]', '["Average waiting time for walk-ins is 20-30 minutes.","Booked patients are seen immediately."]',''],
        ['Appointments', 'virtual', '["online","video","telehealth"]', '["Currently we only offer in-person consultations.","All visits are physical at the moment."]',''],
        ['Appointments', 'referral', '["refer","transfer"]', '["You will need to be seen by a general doctor first to get a specialist referral.","Referrals require an initial checkup."]',''],
        ['Appointments', 'records', '["history","file","medical record"]', '["Your medical history is completely digitized and available in your patient dashboard.","Log in to view your records."]',''],
        // SERVICES
        ['Services', 'list', '["services","what do you do","offer"]', '["We offer General Consultation, Lab Tests, Pharmacy, Ambulance, and Bed Facilities.","Check our Services page for a full list!"]', 'Want to know more about the lab?'],
        ['Services', 'lab', '["laboratory","blood","test","urine"]', '["Our ultra-modern laboratory conducts blood, urine, and pathology tests.","We have a full lab on-site."]',''],
        ['Services', 'pharmacy', '["drugs","medicine","pill","prescription"]', '["Our pharmacy is fully stocked. Some items may require a prescription from our doctors.","Visit the Pharmacy tab to check inventory."]',''],
        ['Services', 'ambulance_service', '["ambulance info","transport"]', '["Our ambulance is equipped for life support and available for community dispatch.","We have 24/7 ambulance services."]',''],
        ['Services', 'beds', '["ward","admit","admission","bed"]', '["We have general and semi-private wards for inpatient care.","We offer comfortable admission facilities."]',''],
        ['Services', 'maternity', '["pregnant","baby","birth","maternity"]', '["We handle basic antenatal care, but specialized deliveries are referred to the General Hospital.","We do antenatal checkups."]',''],
        ['Services', 'dental', '["teeth","tooth","dental","dentist"]', '["We currently do not have a dental wing on-site.","Dental services are not available at this exact branch."]',''],
        ['Services', 'eye', '["eye","vision","optician"]', '["Optometry is available on Wednesdays and Fridays by appointment.","Eye clinic runs twice a week."]',''],
        ['Services', 'therapy', '["physio","massage","therapy"]', '["Physiotherapy must be specifically booked through a specialist.","We offer basic physiotherapy."]',''],
        ['Services', 'xray', '["xray","scan","ultrasound"]', '["We have an ultrasound machine; X-Ray services are referred out.","Basic scans are available."]',''],
        // EMERGENCY
        ['Emergency', 'sos', '["help","emergency","dying","urgent","crash","accident"]', '["This is an EMERGENCY! Please call our immediate hotline at 153 or 0302716071 NOW!","For life threatening emergencies call 153 immediately!"]', 'Call 153 now!'],
        ['Emergency', 'ambulance', '["need ambulance","send ambulance","dispatch"]', '["If you need an ambulance, call 153 or use the Ambulance Request portal on the website ASAP.","Use the quick ambulance portal!"]',''],
        ['Emergency', 'first_aid', '["bleed","burn","choke","first aid"]', '["Please do not wait for the bot. Call 153 for immediate professional guidance over the phone.","Call 153 for first aid guidance."]',''],
        ['Emergency', 'poison', '["poison","swallow","chemical"]', '["For poison control, head to the Emergency Ward immediately or call 153.","Rush to the emergency room!"]',''],
        ['Emergency', 'heart', '["chest pain","heart","attack"]', '["Chest pain is a critical emergency. Please dial 153 for an ambulance immediately.","Call 153 immediately!"]',''],
        ['Emergency', 'breathing', '["breathe","asthma","choking"]', '["Severe breathing difficulty requires immediate intervention. Call 153!","Dial 153 immediately!"]',''],
        ['Emergency', 'unconscious', '["faint","passed out","wake"]', '["Do not move the person unless in danger. Call 153 for ambulance dispatch.","Call 153!"]',''],
        ['Emergency', 'burns', '["fire","burn","hot"]', '["Run cool (not freezing) water over the burn and call 153 for severe burns.","Wash with cool water and report to clinic."]',''],
        ['Emergency', 'allergy', '["swelling","allergic","anaphylaxis"]', '["Severe allergic reactions are emergencies. Call 153 or report immediately.","Use an EpiPen if available and call 153!"]',''],
        ['Emergency', 'seizure', '["fit","seizure","shaking"]', '["Clear the area of hard objects and call 153. Do not put anything in their mouth.","Call 153 immediately for seizures."]','']
    ];
    $stmt = mysqli_prepare($conn, "INSERT INTO chatbot_knowledge_base (category, intent_tag, keywords, question_variants, response_text, followup_suggestion, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
    foreach($seed_data as $sd) {
        mysqli_stmt_bind_param($stmt, "ssssss", $sd[0], $sd[1], $sd[2], $sd[3], $sd[4], $sd[5]);
        mysqli_stmt_execute($stmt);
    }
    $message = "Successfully seeded Chatbot Knowledge Base with 40 entries.";
    $kb_count = 40;
}

// Handle Update/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_kb') {
        $stmt = mysqli_prepare($conn, "INSERT INTO chatbot_knowledge_base (category, intent_tag, keywords, question_variants, response_text, followup_suggestion, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        mysqli_stmt_bind_param($stmt, "ssssss", $_POST['cat'], $_POST['intent'], $_POST['kw'], $_POST['qv'], $_POST['rt'], $_POST['fs']);
        mysqli_stmt_execute($stmt);
        $message = "Knowledge base entry added.";
        $kb_count++;
    } elseif ($_POST['action'] === 'toggle_kb') {
        $id = (int)$_POST['id']; $st = (int)$_POST['state'];
        mysqli_query($conn, "UPDATE chatbot_knowledge_base SET is_active=$st WHERE entry_id=$id");
        $message = "Entry status updated.";
    } elseif ($_POST['action'] === 'delete_kb') {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "DELETE FROM chatbot_knowledge_base WHERE entry_id=$id");
        $message = "Entry deleted.";
        $kb_count--;
    }
}

// Fetch KB
$kb = [];
$activeCount = 0;
$categories = [];
$q_kb = mysqli_query($conn, "SELECT * FROM chatbot_knowledge_base ORDER BY category ASC, entry_id DESC");
if ($q_kb) {
    while($r = mysqli_fetch_assoc($q_kb)) {
        $kb[] = $r;
        if($r['is_active'] == 1) $activeCount++;
        if(!in_array($r['category'], $categories)) $categories[] = $r['category'];
    }
}

include '../includes/_sidebar.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">

<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #8b5cf6; /* Violet accent for AI */
  --primary-light: rgba(139, 92, 246, 0.15);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #6d28d9);
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Stat Mini Cards ── */
.stat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2.5rem; }
.stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);
  padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);cursor:pointer;box-shadow:var(--shadow-sm); }
.stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); border-color:var(--primary); }
.stat-mini-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:1.8rem;background:var(--surface-2);color:var(--text-secondary); }
.stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--primary); }
.stat-mini-val.success { color:var(--success); }
.stat-mini-val.info { color:var(--info); }
.stat-mini-lbl { font-size:1.15rem;font-weight:600;color:var(--text-secondary);margin-top:.6rem; text-transform:uppercase; letter-spacing:0.05em; }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; background:var(--surface-2); }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Form Controls ── */
.form-row { display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.6rem; }
@media(max-width:768px){.form-row{grid-template-columns:1fr;}}
.form-group { margin-bottom:1.6rem; }
.form-group label { display:block;font-size:1.1rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em; }
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.15rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }

/* ── Filter Tabs ── */
.filter-tabs { display:flex;gap:.8rem;flex-wrap:wrap; margin-bottom: 2rem; border-bottom:1px solid var(--border); padding-bottom:1.5rem; }
.filter-tabs .ftab { padding:.8rem 1.8rem;border-radius:20px;font-size:1.15rem;font-weight:600;cursor:pointer;
  border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition); }
.filter-tabs .ftab.active, .filter-tabs .ftab:hover { background:var(--primary);color:#fff;border-color:var(--primary); box-shadow: 0 4px 10px var(--primary-light); }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; }
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }

/* ── Toggle Switch ── */
.switch { position:relative; display:inline-block; width:44px; height:24px; margin:0;}
.switch input { opacity:0; width:0; height:0; }
.slider { position:absolute; cursor:pointer; inset:0; background-color:var(--border); transition:.3s; border-radius:24px; }
.slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background-color:white; transition:.3s; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.2);}
input:checked + .slider { background-color:var(--success); }
input:checked + .slider:before { transform:translateX(20px); }

/* ── Toast ── */
#toastWrap { position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem; }
.toast-msg { padding:1.2rem 2rem; border-radius:var(--radius-sm); background:var(--surface); box-shadow:var(--shadow-lg); border-left:5px solid var(--primary); font-size:1.2rem; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:1rem; animation:fadePop .3s ease; }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-robot"></i> Chatbot AI Manager</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadePop .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-microchip hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-robot"></i></div>
            <div class="staff-hero-info">
                <h2>AI Knowledge Base</h2>
                <p>Train the chatbot by configuring intents, keywords, and automated responses.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <button class="btn" onclick="document.getElementById('addIntentForm').scrollIntoView({behavior:'smooth'});" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3); backdrop-filter:blur(5px);">
                    <i class="fas fa-plus"></i> Add New Intent
                </button>
            </div>
        </div>

        <?php if($kb_count === 0): ?>
            <div class="card" style="text-align:center; padding:5rem; background:var(--primary-light); border-color:var(--primary);">
                <i class="fas fa-database" style="font-size:5rem; color:var(--primary); margin-bottom:1.5rem;"></i>
                <h2 style="font-size:2rem; font-weight:700; color:var(--text-primary); margin-bottom:1rem;">Knowledge Base is Empty</h2>
                <p style="font-size:1.2rem; color:var(--text-secondary); margin-bottom:2rem; max-width:600px; margin-left:auto; margin-right:auto;">The chatbot relies on a pre-programmed dataset to understand intents. Click below to strictly enforce the 40-entry seed requirement.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="seed">
                    <button type="submit" class="btn btn-primary" style="padding:1.2rem 2.5rem; font-size:1.3rem;"><i class="fas fa-magic"></i> Auto-Seed 40 Entries</button>
                </form>
            </div>
        <?php else: ?>

            <div class="stat-grid">
                <div class="stat-mini">
                    <div class="stat-mini-icon" style="color:var(--primary); background:var(--primary-light);"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-mini-val"><?= $kb_count ?></div>
                    <div class="stat-mini-lbl">Total Intents</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-icon" style="color:var(--success); background:var(--success-light);"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-mini-val success"><?= $activeCount ?></div>
                    <div class="stat-mini-lbl">Active Intents</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-icon" style="color:var(--info); background:var(--info-light);"><i class="fas fa-tags"></i></div>
                    <div class="stat-mini-val info"><?= count($categories) ?></div>
                    <div class="stat-mini-lbl">Categories</div>
                </div>
            </div>

            <div class="card" id="addIntentForm" style="background:var(--surface-2);">
                <div class="card-header" style="background:transparent; border-bottom:none;">
                    <h3><i class="fas fa-plus-circle" style="color:var(--primary);"></i> Add Custom Intent</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_kb">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Category</label>
                                <input type="text" name="cat" class="form-control" placeholder="General, Appointments..." required>
                            </div>
                            <div class="form-group">
                                <label>Intent Tag</label>
                                <input type="text" name="intent" class="form-control" placeholder="e.g. greeting" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Keywords Match Array (JSON)</label>
                            <input type="text" name="kw" class="form-control" placeholder='["word1", "word2"]' required>
                        </div>
                        <div class="form-group">
                            <label>Response Variants (JSON)</label>
                            <textarea name="rt" class="form-control" rows="3" placeholder='["Response A", "Response B"]' required></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Question Variants (JSON)</label>
                                <input type="text" name="qv" class="form-control" placeholder='["How do i?"]' required>
                            </div>
                            <div class="form-group">
                                <label>Follow-up Suggestion (Text)</label>
                                <input type="text" name="fs" class="form-control" placeholder="Type X to book...">
                            </div>
                        </div>
                        <button class="btn btn-primary"><i class="fas fa-save"></i> Save Intent</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-table" style="color:var(--primary);"></i> Knowledge Base Explorer</h3>
                </div>
                <div class="card-body" style="padding:1rem;">
                    
                    <div class="filter-tabs" style="border-bottom:none; margin-bottom:1rem; padding-bottom:0; padding:1rem;">
                        <button class="ftab active" data-filter="">All</button>
                        <?php foreach($categories as $cat): ?>
                            <button class="ftab" data-filter="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
                        <?php endforeach; ?>
                    </div>

                    <table class="stf-table" id="kbTable">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Intent</th>
                                <th>Responses</th>
                                <th>Follow Up</th>
                                <th>Status</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($kb as $k): ?>
                            <tr>
                                <td><span style="background:var(--surface-2); padding:0.3rem 0.8rem; border-radius:12px; font-size:1rem; font-weight:600; color:var(--text-secondary); border:1px solid var(--border);"><?= htmlspecialchars($k['category']) ?></span></td>
                                <td><strong style="font-size:1.2rem; color:var(--primary);"><?= htmlspecialchars($k['intent_tag']) ?></strong></td>
                                <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($k['response_text'] ?? '') ?>"><?= htmlspecialchars($k['response_text'] ?? '') ?></td>
                                <td style="max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($k['followup_suggestion'] ?? '') ?>"><?= htmlspecialchars($k['followup_suggestion'] ?? '') ?></td>
                                <td>
                                    <form method="POST" style="margin:0; display:inline-flex; align-items:center; gap:0.5rem;">
                                        <input type="hidden" name="action" value="toggle_kb">
                                        <input type="hidden" name="id" value="<?= $k['entry_id'] ?>">
                                        <input type="hidden" name="state" value="<?= $k['is_active'] ? 0 : 1 ?>">
                                        <span style="font-size:1rem; color:<?= $k['is_active'] ? 'var(--success)' : 'var(--text-muted)' ?>; font-weight:600;">
                                            <?= $k['is_active'] ? 'Active' : 'Offline' ?>
                                        </span>
                                        <label class="switch">
                                            <input type="checkbox" onchange="this.form.submit()" <?= $k['is_active'] ? 'checked' : '' ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </form>
                                </td>
                                <td style="text-align:right;">
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="delete_kb">
                                        <input type="hidden" name="id" value="<?= $k['entry_id'] ?>">
                                        <button class="btn btn-ghost" style="color:var(--danger);"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<div id="toastWrap"></div>

<script>
    function showToast(msg, type='success') {
        const toast = document.createElement('div');
        toast.className = `toast-msg toast-${type}`;
        toast.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> <span>${msg}</span>`;
        document.getElementById('toastWrap').appendChild(toast);
        setTimeout(()=> { toast.style.opacity='0'; setTimeout(()=>toast.remove(),300); }, 3000);
    }

    <?php if ($message): ?>
    document.addEventListener('DOMContentLoaded', () => { showToast(<?= json_encode($message) ?>, 'success'); });
    <?php endif; ?>

    $(document).ready(function() {
        if ($('#kbTable').length) {
            const kbTable = $('#kbTable').DataTable({
                responsive: true,
                pageLength: 10,
                language: { search: "", searchPlaceholder: "Search intents..." }
            });
            
            // style datatables
            $('.dataTables_filter input').addClass('form-control').css({'width':'250px','display':'inline-block', 'margin-left':'10px'});
            
            $('.ftab').on('click', function() {
                $('.ftab').removeClass('active');
                $(this).addClass('active');
                const filterVal = $(this).data('filter');
                kbTable.column(0).search(filterVal ? '^.*' + filterVal + '.*$' : '', true, false).draw();
            });
        }
    });

    const themeIcon = document.getElementById('themeIcon');
    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const html = document.documentElement;
        const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
        html.setAttribute('data-theme', t);
        localStorage.setItem('rmu_theme', t);
        if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
