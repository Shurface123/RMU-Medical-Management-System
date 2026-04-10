<?php
/**
 * Seed enhanced chatbot knowledge base for RMU Medical Sickbay
 * Run once: http://localhost/RMU-Medical-Management-System/seed_chatbot_kb.php
 */

require_once 'php/db_conn.php';

$entries = [
    // ── Greetings ──────────────────────────────────────────────────
    ['greeting', 'General', ['hello','hi','hey','good morning','good afternoon','good evening','sup','howdy'], ['How can I help you today?','Hello there','Hi, what can I do for you?'], "Hello! Welcome to RMU Medical Sickbay. I'm your virtual medical assistant. I can help you with information about our services, appointments, doctors, and more. What would you like to know? 😊", 'Book an appointment'],

    // ── Services ────────────────────────────────────────────────────
    ['services', 'Services', ['services','offer','provide','available','what do you do','treatment'], ['What services do you offer?','Tell me about your services'], "**RMU Medical Sickbay offers a comprehensive range of healthcare services:**\n\n• 🚑 **Emergency Care** — 24/7 emergency services\n• 👨‍⚕️ **General Consultation** — Expert medical consultations\n• 💊 **Pharmacy Services** — Prescription & OTC medications\n• 🔬 **Laboratory Tests** — Diagnostic tests with 24-48hr results\n• 🛏️ **Inpatient Care** — 50 comfortable beds\n• 🧠 **Mental Health Support** — Confidential counselling\n• 💉 **Vaccination** — Immunisation programs\n• 🚗 **Ambulance Service** — 24/7 rapid response", 'Book an appointment'],

    // ── Emergency ───────────────────────────────────────────────────
    ['emergency', 'Emergency', ['emergency','urgent','critical','accident','bleeding','faint','unconscious','emergency line','hotline'], ['What is the emergency number?','Emergency contact','I need emergency help'], "🚨 **EMERGENCY CONTACT:**\n\n📞 **Dial 153** — our 24/7 emergency hotline (on-campus)\n📞 **0502371207** — main line\n\nOur ambulance service is **available 24/7** and will respond to any campus emergency immediately. If it's a life-threatening emergency, please call 153 NOW.", 'Call Emergency: 153'],

    // ── Appointment ─────────────────────────────────────────────────
    ['book_appointment', 'Appointments', ['book','appointment','schedule','reserve','consultation','see a doctor','how to book','visit'], ['How do I book an appointment?','I want to see a doctor','Book appointment'], "**To book an appointment at RMU Medical Sickbay:**\n\n1. 🌐 Visit our online booking page\n2. 📝 Log in or register your account\n3. 📅 Choose your preferred date and time\n4. 👨‍⚕️ Select the doctor or department\n5. ✅ Confirm your booking\n\nOr simply walk in to the sickbay during working hours (Mon–Fri, 8AM–5PM). Emergency patients are seen immediately at any time.", 'Book now online'],

    // ── Hours ────────────────────────────────────────────────────────
    ['opening_hours', 'General', ['hours','open','opening','close','closing','time','when','monday','friday','weekend'], ['What are your opening hours?','When are you open?','Are you open 24 hours?'], "🕐 **RMU Medical Sickbay Hours:**\n\n• **Regular Hours:** Monday – Friday, 8:00 AM – 5:00 PM\n• **Emergency Services:** 24 hours, 7 days a week\n• **Pharmacy:** Monday – Friday, 8:00 AM – 8:00 PM\n• **Laboratory:** Monday – Friday, 8:00 AM – 4:00 PM\n\nFor emergencies outside regular hours, please call **153** immediately.", 'Emergency: 153'],

    // ── Location ─────────────────────────────────────────────────────
    ['location', 'General', ['where','location','address','find','directions','map','campus'], ['Where are you located?','Where is the sickbay?','How do I find you?'], "📍 **RMU Medical Sickbay Location:**\n\nWe are located on the **Regional Maritime University campus**, Nungua, Accra, Ghana.\n\nThe sickbay is the red-roofed building behind the **Registrar's Office** and near the male hostels. Look for the medical cross signage.\n\n**GPS:** 5.8613756, -0.3410349", 'Get directions'],

    // ── Pharmacy ─────────────────────────────────────────────────────
    ['pharmacy', 'Services', ['pharmacy','medicine','drug','medication','prescription','pills','tablet','drug store'], ['Do you have a pharmacy?','Where can I get my drugs?','Pharmacy hours'], "💊 **Pharmacy Services:**\n\nYes! We have a fully stocked pharmacy on-site:\n\n• ✅ Prescription medications dispensed\n• ✅ Over-the-counter drugs available\n• ✅ Student medications subsidised\n• ✅ Drug counselling provided by licensed pharmacist\n\n**Hours:** Mon–Fri, 8AM–8PM\n\nBring your prescription from the doctor and a valid student/staff ID.", 'See our services'],

    // ── Laboratory ───────────────────────────────────────────────────
    ['laboratory', 'Services', ['lab','laboratory','test','blood test','malaria','urine','sample','result','diagnostic'], ['How do I get lab tests done?','Lab test results','Blood test'], "🔬 **Laboratory Services:**\n\n• Malaria rapid diagnostic test (RDT)\n• Full blood count (FBC)\n• Urinalysis\n• Blood glucose monitoring\n• Hepatitis B surface antigen test\n• HIV screening (with consent)\n• Liver & kidney function tests\n• Pregnancy test\n\n**Results:** Available within 24–48 hours\n**Hours:** Mon–Fri, 8AM–4PM\n\nA doctor's request form is required for most tests.", 'Book consultation'],

    // ── Cost / Fees ──────────────────────────────────────────────────
    ['fees', 'General', ['cost','fee','charge','pay','free','price','how much','expensive','cheap'], ['Is it free for students?','How much does it cost?','Do I have to pay?'], "💰 **Healthcare Costs at RMU Sickbay:**\n\n**For Students:**\n• General consultation: **FREE**\n• Basic medications: **Subsidised or FREE**\n• Emergency care: **FREE**\n• Some specialist tests may have a small fee\n\n**For Staff & Faculty:**\n• Consultation: Nominal fee applies\n• Medications at subsidised rates\n\nPlease bring your **student/staff ID** to access subsidised services.", 'Book appointment'],

    // ── Mental Health ────────────────────────────────────────────────
    ['mental_health', 'Services', ['mental','stress','anxiety','depression','counselling','counselor','psychology','emotional','mental health'], ['Do you have mental health support?','I need counselling','Feeling stressed'], "🧠 **Mental Health & Counselling Services:**\n\nWe provide **confidential** mental health support:\n\n• One-on-one counselling sessions\n• Stress management workshops\n• Academic pressure support\n• Relationship counselling\n• Grief and loss support\n• Referral to specialist psychiatrists when needed\n\n**All sessions are completely confidential.** Book via the appointment system or walk in during office hours.\n\nRemember: **It's okay to ask for help.** 💙", 'Book counselling'],

    // ── Doctors ──────────────────────────────────────────────────────
    ['doctors', 'Staff', ['doctor','physician','specialist','who are','medical team','GP','general practitioner'], ['Who are your doctors?','Can I choose my doctor?','List of doctors'], "👨‍⚕️ **Our Medical Team:**\n\nRMU Medical Sickbay has 12+ qualified doctors including:\n\n• General Physicians\n• Emergency Medicine Specialists\n• Mental Health Practitioners\n\nYou can view full doctor profiles and specialisations on our **Doctors** page. Appointments can be booked with a preferred doctor subject to availability.", 'Meet our doctors'],

    // ── Ambulance ────────────────────────────────────────────────────
    ['ambulance', 'Emergency', ['ambulance','transport','vehicle','ride','accident scene','evacuation'], ['Do you have an ambulance?','Ambulance service'], "🚑 **Ambulance Service:**\n\nYes! We operate **3 ambulances** staffed by trained paramedics:\n\n• Available **24/7**, including weekends and public holidays\n• On-campus emergency response within **5 minutes**\n• Off-campus evacuation and hospital transfers\n• Equipped with basic life support (BLS) equipment\n\n**To request an ambulance:** Call **153** immediately", 'Emergency: 153'],

    // ── Registration ─────────────────────────────────────────────────
    ['registration', 'General', ['register','sign up','create account','new patient','first time','how to register'], ['How do I register?','Create new account','First time visiting'], "📋 **How to Register:**\n\n1. Visit our website and click **Register**\n2. Fill in your name, student/staff ID, date of birth\n3. Create a secure password\n4. Verify your email address\n5. Your account is ready!\n\nOnce registered, you can:\n✅ Book appointments online\n✅ View your medical history\n✅ Check lab results\n✅ Manage prescriptions", 'Register now'],

    // ── Inpatient ────────────────────────────────────────────────────
    ['inpatient', 'Services', ['bed','admit','hospitalise','ward','inpatient','overnight','stay','treatment'], ['Do you have beds for patients?','Can I be admitted?','Inpatient ward'], "🛏️ **Inpatient Facilities:**\n\nWe have **50 inpatient beds** available:\n\n• Male and female wards (separated)\n• Clean, air-conditioned rooms\n• 24/7 nursing supervision\n• Meals provided during admission\n• Regular doctor ward rounds\n\nAdmission is based on clinical assessment by the attending doctor. Students are admitted at **no charge**.", 'Book consultation'],

    // ── Vaccination ──────────────────────────────────────────────────
    ['vaccination', 'Services', ['vaccine','vaccination','immunisation','jab','shot','hepatitis','yellow fever','meningitis','typhoid'], ['Do you offer vaccines?','Vaccination services','Hepatitis B vaccine'], "💉 **Vaccination Services:**\n\nWe provide the following vaccines:\n\n• Hepatitis B\n• Tetanus-diphtheria (Td)\n• Yellow fever (with certificate)\n• Meningococcal vaccine\n• Typhoid\n• Influenza (seasonal)\n\nBring your **vaccination booklet** for record updating. Some vaccines may require advance notice. Please call to confirm availability.", 'Book vaccination'],

    // ── Contact ──────────────────────────────────────────────────────
    ['contact', 'General', ['contact','phone number','call','email','reach','get in touch','telephone'], ['How do I contact you?','Phone number','Email address'], "📞 **Contact RMU Medical Sickbay:**\n\n• **Emergency (24/7):** 153\n• **Main Line:** 0502371207\n• **Email:** sickbay.text@st.rmu.edu.gh\n• **Address:** RMU Campus, Nungua, Accra, Ghana\n• **Office Hours:** Mon–Fri, 8AM–5PM\n\nFor urgent matters outside office hours, always use the **emergency line: 153**", 'Call now'],

    // ── Covid / Illness ──────────────────────────────────────────────
    ['illness_symptoms', 'Services', ['sick','ill','fever','cough','headache','pain','symptom','malaria','flu','cold','infection','diarrhea','vomiting'], ['I am feeling sick','I have fever','I have headache','I think I have malaria'], "🤒 **Feeling Unwell?**\n\nWe're here to help! Here's what to do:\n\n1. **Walk in** to the sickbay anytime during working hours (no appointment needed for sick consultations)\n2. If outside hours and it's an emergency, **call 153**\n3. If you suspect malaria, we have **rapid malaria tests** available\n\n**Common conditions we treat:**\n• Malaria & fever\n• Upper respiratory infections\n• Gastroenteritis\n• Wounds & injuries\n• Skin conditions\n\nDon't delay — come in and see us! 💙", 'Visit us now'],

    // ── Login / Portal ───────────────────────────────────────────────
    ['portal_login', 'General', ['login','log in','account','password','forgot password','portal','dashboard'], ['How do I login?','I forgot my password','Account access'], "🔐 **Patient Portal Access:**\n\n• Visit the homepage and click **Login**\n• Enter your registered email and password\n• **Forgot password?** Use the 'Reset Password' link\n\nIf you haven't registered yet, click **Register** to create a free account. Contact us at **0502371207** if you have account issues.", 'Login now'],

    // ── Thank you / Goodbye ──────────────────────────────────────────
    ['goodbye', 'General', ['thanks','thank you','bye','goodbye','ok','okay','great','perfect','awesome','noted'], ['Thank you','Goodbye','Okay'], "You're welcome! 😊 If you have any more questions, I'm always here to help. Take care and stay healthy! 💙\n\nFor emergencies, remember: **call 153** any time.", 'View our services'],
];

$inserted = 0;
$errors   = 0;

// Clear existing KB
mysqli_query($conn, "DELETE FROM chatbot_knowledge_base WHERE 1");

foreach ($entries as [$intent, $cat, $kws, $variants, $response, $followup]) {
    $intent   = mysqli_real_escape_string($conn, $intent);
    $cat      = mysqli_real_escape_string($conn, $cat);
    $kw_json  = mysqli_real_escape_string($conn, json_encode($kws));
    $qv_json  = mysqli_real_escape_string($conn, json_encode($variants));
    $response = mysqli_real_escape_string($conn, $response);
    $followup = mysqli_real_escape_string($conn, $followup);

    $sql = "INSERT INTO chatbot_knowledge_base
            (intent_tag, category, question_variants, keywords, response_text, followup_suggestion, is_active)
            VALUES ('$intent','$cat','$qv_json','$kw_json','$response','$followup',1)";

    if (mysqli_query($conn, $sql)) $inserted++;
    else { $errors++; echo "Error on [$intent]: " . mysqli_error($conn) . "<br>"; }
}

echo "<br><strong>✅ Chatbot Knowledge Base Seeded!</strong><br>";
echo "Inserted: <strong>$inserted</strong> entries<br>";
if ($errors) echo "Errors: <strong style='color:red;'>$errors</strong> — see above<br>";
echo "<br><a href='/RMU-Medical-Management-System/html/index.html'>← Back to Home</a>";

mysqli_close($conn);
