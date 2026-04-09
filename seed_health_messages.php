<?php
require_once 'php/db_conn.php';

$messages = [
    // Patients
    ["Your health is your greatest wealth. Keep up with your appointments.", "motivational", "patient"],
    ["Remember to complete your prescribed medication course even if you feel better.", "reminder", "patient"],
    ["Hydration is key to recovery. Drink plenty of water today.", "wellness", "patient"],
    ["Regular check-ups help prevent major health issues. Stay proactive.", "health tip", "patient"],
    ["Sleep is when your body heals. Aim for 7-8 hours tonight.", "wellness", "patient"],
    ["Monitor your symptoms and don't hesitate to reach out if they worsen.", "safety", "patient"],
    ["A balanced diet fuels your immune system. Eat your greens!", "health tip", "patient"],
    ["Take a deep breath and relax. Stress management is vital for healing.", "wellness", "patient"],
    ["Follow your doctor's advice strictly for the fastest recovery.", "motivational", "patient"],
    ["Thank you for trusting RMU Medical System with your care.", "motivational", "patient"],

    // Doctors
    ["Thank you for your service. Rest well — your patients need you at your best.", "motivational", "doctor"],
    ["A well-rested doctor makes the best clinical decisions. Take a break.", "wellness", "doctor"],
    ["Review patient histories carefully before prescribing new medications.", "safety", "doctor"],
    ["Compassion cures as much as medicine does. Great work today.", "motivational", "doctor"],
    ["Ensure all case notes are thoroughly documented before signing off.", "reminder", "doctor"],
    ["Your diagnostic skills save lives every single day.", "motivational", "doctor"],
    ["Stay updated with the latest medical protocols.", "health tip", "doctor"],
    ["Take 5 minutes to stretch between long patient consultations.", "wellness", "doctor"],
    ["Double-check all conflicting medication alerts in the system.", "safety", "doctor"],
    ["The medical field is demanding. Prioritize your mental health.", "wellness", "doctor"],

    // Nurses
    ["You make a difference every single day. Take care of yourself too.", "motivational", "nurse"],
    ["Always verify patient identity before administering any treatment.", "safety", "nurse"],
    ["Hydrate! Nursing shifts are long and your body needs water.", "wellness", "nurse"],
    ["Ensure all vitals are recorded immediately after taking them.", "reminder", "nurse"],
    ["Your care and empathy are the heart of this hospital.", "motivational", "nurse"],
    ["Lift patients with proper ergonomics to avoid back injuries.", "safety", "nurse"],
    ["Take your scheduled breaks. You deserve them.", "wellness", "nurse"],
    ["Clear communication during shift handovers prevents critical errors.", "safety", "nurse"],
    ["Wash hands thoroughly between every patient interaction.", "health tip", "nurse"],
    ["You are an essential pillar of patient recovery. Thank you.", "motivational", "nurse"],

    // Pharmacists
    ["Accurate dispensing saves lives. Well done today.", "motivational", "pharmacist"],
    ["Double-check all dosage instructions before handing over medications.", "safety", "pharmacist"],
    ["Ensure all dangerous drugs are securely locked before leaving.", "safety", "pharmacist"],
    ["Verify all ambiguous prescriptions directly with the prescribing doctor.", "reminder", "pharmacist"],
    ["Keep the dispensary organized to prevent dispensing errors.", "safety", "pharmacist"],
    ["Patient education on side effects is just as critical as the medicine.", "health tip", "pharmacist"],
    ["Rest your eyes after long periods of reading labels.", "wellness", "pharmacist"],
    ["Your attention to detail prevents adverse drug interactions.", "motivational", "pharmacist"],
    ["Always check expiration dates when restocking shelves.", "reminder", "pharmacist"],
    ["Thank you for ensuring our patients get the right treatments safely.", "motivational", "pharmacist"],

    // Lab Technicians
    ["Precision in the lab is precision in patient care. Great work.", "motivational", "lab_technician"],
    ["Always wear appropriate PPE when handling biological samples.", "safety", "lab_technician"],
    ["Ensure all lab equipment is properly calibrated before your shift ends.", "reminder", "lab_technician"],
    ["Accurate results start with accurately labeled samples.", "safety", "lab_technician"],
    ["Your work behind the scenes is vital to accurate diagnoses.", "motivational", "lab_technician"],
    ["Decontaminate workspaces thoroughly before leaving the lab.", "safety", "lab_technician"],
    ["Avoid eye strain by following the 20-20-20 rule during microscope work.", "wellness", "lab_technician"],
    ["Properly store all reagents according to temperature requirements.", "reminder", "lab_technician"],
    ["Never rush a test. Quality always supersedes speed in the lab.", "health tip", "lab_technician"],
    ["Your dedication to accuracy protects our patients. Thank you.", "motivational", "lab_technician"],

    // Admins
    ["Behind every great hospital system is a great administrator. Thank you.", "motivational", "admin"],
    ["Remember to step away from the screen to rest your eyes.", "wellness", "admin"],
    ["Secure all sensitive data screens before leaving your desk.", "safety", "admin"],
    ["A well-managed hospital saves lives. Keep up the great work.", "motivational", "admin"],
    ["Review all pending approvals in the queue before end of day.", "reminder", "admin"],
    ["Data privacy is paramount. Ensure strict access controls are maintained.", "safety", "admin"],
    ["Take a walk. Sitting all day is harmful to your long-term health.", "wellness", "admin"],
    ["Your organizational skills keep the entire facility running smoothly.", "motivational", "admin"],
    ["Check system backup logs to ensure data integrity.", "reminder", "admin"],
    ["Thank you for maintaining the foundation of our healthcare delivery.", "motivational", "admin"],
    
    // All Roles
    ["Remember to stay hydrated — drink at least 8 glasses of water daily.", "wellness", ""]
];

$count = 0;
foreach($messages as $m) {
    $text = mysqli_real_escape_string($conn, $m[0]);
    $cat = $m[1];
    $role = empty($m[2]) ? "NULL" : "'".mysqli_real_escape_string($conn, $m[2])."'";
    
    $check = mysqli_query($conn, "SELECT COUNT(*) as c FROM health_messages WHERE message_text='$text'");
    if (mysqli_fetch_assoc($check)['c'] == 0) {
        if(mysqli_query($conn, "INSERT INTO health_messages (message_text, message_category, target_role, is_active, created_by) VALUES ('$text', '$cat', $role, 1, 1)")) {
            $count++;
        } else {
            echo "Error: ".mysqli_error($conn)."\n";
        }
    }
}
echo "Seeded $count health messages successfully.\n";
