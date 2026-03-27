<?php
require_once __DIR__ . '/php/db_conn.php';

// 1. Seed Health Messages (10 per role + 10 global)
$roles = ['doctor', 'nurse', 'patient', 'pharmacist', 'lab_technician', 'staff'];
$categories = ['wellness', 'safety', 'reminder', 'motivational', 'health tip'];

$seeds = [
    // Doctor
    "Review your final patient charts before departing.",
    "Ensure your prescription pad is secured.",
    "Take a moment to decompress after your consulting shift.",
    "Verify all urgent lab requests have been reviewed.",
    "Your dedication saves lives. Have a restful evening.",
    "Check your schedule for tomorrow's early appointments.",
    "Remember to properly log out of the EMR system.",
    "Stay hydrated! Doctors need care too.",
    "Ensure all patient handoffs are communicated to the night shift.",
    "Rest well. The clinic relies on your sharp mind tomorrow.",
    
    // Nurse
    "Ensure the medication cart is locked and secured.",
    "Double-check patient vital sign logs before shift end.",
    "Thank you for your tireless care today. Rest well.",
    "Communicate all pending IV drip changes to the next shift.",
    "Have you submitted all incident reports?",
    "Wipe down your vitals station equipment.",
    "Nurses are the heart of healthcare. Have a great day!",
    "Verify all patient bedside alarms are active.",
    "Rest your feet. You've earned a good break.",
    "Remember to wash your hands as you exit the ward.",

    // Patient
    "Remember to take your prescribed medications on time.",
    "Drink at least 8 glasses of water today.",
    "Your health is your greatest wealth.",
    "If you feel worse, please contact the clinic immediately.",
    "A good night's sleep accelerates recovery.",
    "Keep your follow-up appointment dates in mind.",
    "Eat a balanced meal to support your immune system.",
    "Call the emergency hotline if you experience shortness of breath.",
    "Physical rest is just as important as medical treatment.",
    "Thank you for choosing RMU Medical for your care.",

    // Pharmacist
    "Ensure the controlled substance cabinet is double-locked.",
    "Verify all pending prescription labels have been printed.",
    "Thank you for keeping our patients safely medicated.",
    "Check refrigerator temperatures before leaving.",
    "Ensure the pharmacy counter is cleared of loose pills.",
    "Cross-check today's narcotic dispensary logs.",
    "Rest well! Accuracy requires a well-rested mind.",
    "Please secure the main pharmacy vault.",
    "Ensure no pending refill requests were missed.",
    "Have a safe journey home.",

    // Lab Technician
    "Ensure all incubators are properly sealed.",
    "Verify that the centrifuge is clean and powered down.",
    "Thank you for providing the diagnostic clarity we need.",
    "Check that all sensitive reagents are refrigerated.",
    "Sanitize your lab bench before departing.",
    "Ensure all hazardous waste is properly binned.",
    "Double-check that all pending urgent results are broadcasted.",
    "Secure the hematology analyzer for the night.",
    "Rest your eyes! Microscope work is demanding.",
    "Have a great evening.",

    // Staff
    "Ensure all administrative files are locked in the cabinets.",
    "Verify that tomorrow's patient rosters are printed.",
    "Thank you for keeping the clinic running smoothly.",
    "Check that all waiting area systems are powered down.",
    "Remember to submit your daily cash reconciliation.",
    "Ensure the main entrance is secured if you are the last to leave.",
    "Rest well! Tomorrow is another busy day.",
    "Verify that all incoming emails have been triaged.",
    "Ensure the reception desk is cleared and tidy.",
    "Have a wonderful and restful day off."
];

$global_seeds = [
    "Thank you for using the RMU Medical Sickbay System.",
    "Security is everyone's responsibility.",
    "Have a wonderful and safe day.",
    "Your session has been securely terminated.",
    "Always remember to lock your screen when stepping away.",
    "Stay positive, work hard, make it happen.",
    "The system will now securely purge your local data.",
    "Disconnecting securely from the hospital network.",
    "Health is a state of body. Wellness is a state of being.",
    "Goodbye! See you next time."
];

// Combine all
$insertQ = "INSERT INTO health_messages (message_text, message_category, target_role, is_active) VALUES (?, ?, ?, 1)";
$stmt = mysqli_prepare($conn, $insertQ);

// Determine index
$role_idx = 0;
foreach($roles as $r) {
    for($i=0; $i<10; $i++) {
        $msg = $seeds[($role_idx * 10) + $i];
        $cat = $categories[array_rand($categories)];
        mysqli_stmt_bind_param($stmt, "sss", $msg, $cat, $r);
        mysqli_stmt_execute($stmt);
    }
    $role_idx++;
}

foreach($global_seeds as $gmsg) {
    $cat = $categories[array_rand($categories)];
    $nl = null;
    mysqli_stmt_bind_param($stmt, "sss", $gmsg, $cat, $nl);
    mysqli_stmt_execute($stmt);
}

// 2. Check for notifications tables
$res = mysqli_query($conn, "SHOW TABLES LIKE '%notif%'");
$tables = [];
while($r = mysqli_fetch_row($res)) {
    $tables[] = $r[0];
}

echo "Seeded 70 health messages.\n";
echo "Notification tables found:\n";
print_r($tables);
?>
