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
    } elseif ($_POST['action'] === 'toggle_kb') {
        $id = (int)$_POST['id']; $st = (int)$_POST['state'];
        mysqli_query($conn, "UPDATE chatbot_knowledge_base SET is_active=$st WHERE entry_id=$id");
        $message = "Entry status updated.";
    } elseif ($_POST['action'] === 'delete_kb') {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "DELETE FROM chatbot_knowledge_base WHERE entry_id=$id");
        $message = "Entry deleted.";
    }
}

// Fetch KB
$kb = [];
$q_kb = mysqli_query($conn, "SELECT * FROM chatbot_knowledge_base ORDER BY category ASC, entry_id DESC");
if ($q_kb) while($r = mysqli_fetch_assoc($q_kb)) $kb[] = $r;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Chatbot Manager - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    <style>
        .card-wrap { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
        .lbl { display:block; font-weight:600; margin-bottom:.5rem; font-size:.9rem; color:var(--text-secondary); }
        .inp { width:100%; padding:.8rem 1rem; border:1px solid var(--border); border-radius:8px; background:var(--bg); color:var(--text); font-family:inherit; margin-bottom:1rem; }
        .inp:focus { outline:none; border-color:var(--primary); }
        
        .switch { position:relative; display:inline-block; width:44px; height:24px; }
        .switch input { opacity:0; width:0; height:0; }
        .slider { position:absolute; cursor:pointer; inset:0; background-color:var(--border); transition:.3s; border-radius:24px; }
        .slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background-color:white; transition:.3s; border-radius:50%; }
        input:checked + .slider { background-color:var(--primary); }
        input:checked + .slider:before { transform:translateX(20px); }
    </style>
</head>
<body>
<?php include '../includes/_sidebar.php'; ?>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-robot" style="color:#8b5cf6;margin-right:10px;"></i> Chatbot AI Knowledge Base</span>
        </div>
    </div>
    
    <div class="adm-content">
        <?php if($message): ?>
            <div style="background:#10b98122; color:#10b981; padding:1rem; border-radius:8px; margin-bottom:1.5rem; font-weight:600;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if($kb_count === 0): ?>
            <div class="card-wrap" style="text-align:center; padding:3rem; background:rgba(139,92,246,0.05); border-color:#8b5cf6;">
                <i class="fas fa-database" style="font-size:4rem; color:#8b5cf6; margin-bottom:1.5rem;"></i>
                <h2>Knowledge Base is Empty</h2>
                <p style="margin-bottom:2rem; color:var(--text-muted);">The chatbot relies on a pre-programmed dataset to understand intents. Click below to strictly enforce the 40-entry seed requirement.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="seed">
                    <button type="submit" class="btn btn-primary" style="background:#8b5cf6; border:none; padding:1rem 2rem; font-size:1.1rem;"><span class="btn-text"><i class="fas fa-magic"></i> Auto-Seed 40 Entries</span></button>
                </form>
            </div>
        <?php else: ?>
            <div class="card-wrap" style="background:var(--bg-secondary);">
                <h3><i class="fas fa-plus-circle" style="color:var(--primary);"></i> Add Custom Intent</h3><br>
                <form method="POST" style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                    <input type="hidden" name="action" value="add_kb">
                    <div><label class="lbl">Category</label><input type="text" name="cat" class="inp" placeholder="General, Appointments..." required></div>
                    <div><label class="lbl">Intent Tag</label><input type="text" name="intent" class="inp" placeholder="e.g. greeting" required></div>
                    
                    <div style="grid-column:1 / -1;"><label class="lbl">Keywords Match Array (JSON)</label><input type="text" name="kw" class="inp" placeholder='["word1", "word2"]' required></div>
                    <div style="grid-column:1 / -1;"><label class="lbl">Response Variants (JSON)</label><textarea name="rt" class="inp" rows="2" placeholder='["Response A", "Response B"]' required></textarea></div>
                    
                    <div><label class="lbl">Question Variants (JSON)</label><input type="text" name="qv" class="inp" placeholder='["How do i?"]' required></div>
                    <div><label class="lbl">Follow-up Suggestion (Text)</label><input type="text" name="fs" class="inp" placeholder="Type X to book..."></div>
                    
                    <div style="grid-column:1 / -1;"><button class="btn btn-primary"><span class="btn-text"><i class="fas fa-save"></i> Save Intent</span></button></div>
                </form>
            </div>

            <div class="card-wrap">
                <table class="adm-table" style="width:100%; font-size:0.9rem;">
                    <tr><th>Category</th><th>Intent</th><th>Responses</th><th>Follow Up</th><th>Active</th><th>Action</th></tr>
                    <?php foreach($kb as $k): ?>
                    <tr>
                        <td><span class="adm-badge" style="background:var(--bg-secondary); color:var(--text);"><?= htmlspecialchars($k['category']) ?></span></td>
                        <td><b><?= htmlspecialchars($k['intent_tag']) ?></b></td>
                        <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($k['response_text'] ?? '') ?>"><?= htmlspecialchars($k['response_text'] ?? '') ?></td>
                        <td><?= htmlspecialchars($k['followup_suggestion'] ?? '') ?></td>
                        <td>
                            <form method="POST" style="margin:0; display:inline-block;">
                                <input type="hidden" name="action" value="toggle_kb">
                                <input type="hidden" name="id" value="<?= $k['entry_id'] ?>">
                                <input type="hidden" name="state" value="<?= $k['is_active'] ? 0 : 1 ?>">
                                <label class="switch">
                                    <input type="checkbox" onchange="this.form.submit()" <?= $k['is_active'] ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_kb"><input type="hidden" name="id" value="<?= $k['entry_id'] ?>"><button class="btn btn-ghost" style="color:var(--danger); padding:5px;"><span class="btn-text"><i class="fas fa-trash"></i></span></button></form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
