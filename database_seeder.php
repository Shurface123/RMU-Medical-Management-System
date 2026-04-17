<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * RMU Medical Sickbay — Comprehensive Database Seeder
 * ═══════════════════════════════════════════════════════════════════
 * Appends ~10 rows of realistic mock data across all major tables.
 * Run from CLI: php database_seeder.php
 */

set_time_limit(300);
ini_set('memory_limit', '256M');

$conn = mysqli_connect('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if (!$conn) die("DB Connection failed: " . mysqli_connect_error() . "\n");
mysqli_set_charset($conn, 'utf8mb4');
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");

$log = [];
$errors = [];

function ins($conn, $table, $data) {
    $cols  = implode(',', array_map(fn($c) => "`$c`", array_keys($data)));
    $vals  = implode(',', array_map(fn($v) => $v === 'NULL' ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string)$v) . "'", array_values($data)));
    $sql   = "INSERT INTO `$table` ($cols) VALUES ($vals)";
    if (!mysqli_query($conn, $sql)) {
        return mysqli_error($conn);
    }
    return mysqli_insert_id($conn);
}

function rnd($arr) { return $arr[array_rand($arr)]; }
function rdate($from = '-2 years', $to = 'now') {
    return date('Y-m-d H:i:s', rand(strtotime($from), strtotime($to)));
}
function rdate_only($from = '-2 years', $to = 'now') {
    return date('Y-m-d', rand(strtotime($from), strtotime($to)));
}
function future_date($from = 'now', $to = '+3 months') {
    return date('Y-m-d', rand(strtotime($from), strtotime($to)));
}

// ─── Data Dictionaries ───────────────────────────────────────────────────────
$first_names_male   = ['Kwame','Kofi','Ama','Isaac','Samuel','Emmanuel','Joseph','Daniel','Michael','George','Nana','Osei','Bright','Eric','Frank'];
$first_names_female = ['Abena','Akua','Efua','Adwoa','Yaa','Esi','Maame','Abiba','Afia','Adjoa','Diana','Grace','Stella','Rita','Eva'];
$last_names         = ['Mensah','Asante','Boateng','Owusu','Appiah','Acheampong','Gyamfi','Ofori','Amoah','Darko','Bekoe','Antwi','Adu','Tawiah','Yeboah'];
$specializations    = ['General Practice','Cardiology','Pediatrics','Orthopedics','Neurology','Dermatology','Gynecology','Ophthalmology','ENT','Psychiatry'];
$diagnoses          = ['Hypertension','Malaria','Typhoid Fever','Diabetes Mellitus','Pneumonia','Gastroenteritis','Anemia','Asthma','Sickle Cell Disease','Appendicitis'];
$medications        = ['Amoxicillin','Paracetamol','Metformin','Amlodipine','Artemether','Omeprazole','Ciprofloxacin','Lisinopril','Chloroquine','Azithromycin'];
$dosages            = ['250mg','500mg','100mg','10mg','50mg','20mg','200mg','5mg'];
$frequencies        = ['Once daily','Twice daily','Three times daily','Every 8 hours','Every 12 hours'];
$lab_tests          = ['Complete Blood Count','Malaria Parasite Test','Lipid Profile','Liver Function Test','Renal Function Test','Blood Glucose','Urinalysis','HbA1c','Blood Culture','Thyroid Function Test'];
$departments_list   = ['Emergency','Cardiology','Pediatrics','Surgery','Pharmacy','Laboratory','Radiology','Nursing','Administration','Finance'];
$blood_groups       = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
$vitals_statuses    = ['normal','critical','warning','stable'];
$complaint_types    = ['appointment','prescription','lab_result','billing','general'];
$ward_names         = ['Ward A - General','Ward B - Surgical','ICU','Maternity Ward','Pediatric Ward'];
$bed_types          = ['standard','icu','maternity','pediatric','isolation'];
$vehicle_plates     = ['GR-1234-21','AS-5678-22','ER-9012-20','BA-3456-21','UW-7890-22'];
$genders            = ['Male','Female'];
$statuses_appt      = ['scheduled','confirmed','completed','cancelled'];
$invoice_statuses   = ['pending','paid','partial','overdue'];

// ─── 1. Pre-load existing IDs ─────────────────────────────────────────────
function get_ids($conn, $table, $col, $limit = 20) {
    $ids = [];
    $q = mysqli_query($conn, "SELECT `$col` FROM `$table` LIMIT $limit");
    if ($q) while ($r = mysqli_fetch_array($q)) $ids[] = $r[0];
    return $ids ?: [1];
}

$existing_user_ids    = get_ids($conn, 'users', 'id');
$existing_patient_ids = get_ids($conn, 'patients', 'id');
$existing_doctor_ids  = get_ids($conn, 'doctors', 'id');
$existing_nurse_ids   = get_ids($conn, 'nurses', 'id');
$existing_appt_ids    = get_ids($conn, 'appointments', 'id');
$existing_rx_ids      = get_ids($conn, 'prescriptions', 'id');
$existing_inv_ids     = get_ids($conn, 'billing_invoices', 'id');
$existing_ward_ids    = get_ids($conn, 'wards', 'id');
$existing_bed_ids     = get_ids($conn, 'beds', 'id');
$existing_med_ids     = get_ids($conn, 'medicines', 'id');
$existing_dept_ids    = get_ids($conn, 'departments', 'id');
$existing_staff_ids   = get_ids($conn, 'staff', 'id');
$existing_lab_order_ids = get_ids($conn, 'lab_test_orders', 'id');
$existing_lab_ids     = get_ids($conn, 'lab_technicians', 'id');
$existing_fin_ids     = get_ids($conn, 'finance_staff', 'id');
$existing_nurse_user_ids = get_ids($conn, 'nurses', 'user_id');
$existing_pharm_ids   = get_ids($conn, 'pharmacist_profile', 'id');
$existing_rec_ids     = get_ids($conn, 'medical_records', 'id');

$new_user_ids    = [];
$new_patient_ids = [];
$new_doctor_ids  = [];
$new_appt_ids    = [];
$new_rx_ids      = [];
$new_inv_ids     = [];
$new_bed_ids     = [];
$new_lab_order_ids = [];

echo "========================================\n";
echo "  RMU Medical Sickbay — DB Seeder\n";
echo "========================================\n\n";

// ─── SEEDED CREDENTIALS REGISTRY ────────────────────────────────────────────
// Password for ALL seeded accounts: Sickbay@2025
// Hashed with password_hash('Sickbay@2025', PASSWORD_DEFAULT)
$pwd_hash = password_hash('Sickbay@2025', PASSWORD_DEFAULT);

// ─── 2. USERS ─────────────────────────────────────────────────────────────
echo "[1/30] Seeding users...\n";
$seed_users = [
    ['name'=>'Dr. Kwame Mensah',      'email'=>'kwame.mensah.doc@rmu.test',    'role'=>'doctor',     'gender'=>'Male'],
    ['name'=>'Dr. Abena Asante',      'email'=>'abena.asante.doc@rmu.test',    'role'=>'doctor',     'gender'=>'Female'],
    ['name'=>'Dr. Samuel Boateng',    'email'=>'samuel.boateng.doc@rmu.test',  'role'=>'doctor',     'gender'=>'Male'],
    ['name'=>'Nurse Efua Owusu',      'email'=>'efua.owusu.nurse@rmu.test',    'role'=>'nurse',      'gender'=>'Female'],
    ['name'=>'Nurse Isaac Appiah',    'email'=>'isaac.appiah.nurse@rmu.test',  'role'=>'nurse',      'gender'=>'Male'],
    ['name'=>'Patient Emmanuel Kofi', 'email'=>'emmanuel.kofi@rmu.test',       'role'=>'patient',    'gender'=>'Male'],
    ['name'=>'Patient Yaa Acheampong','email'=>'yaa.acheampong@rmu.test',      'role'=>'patient',    'gender'=>'Female'],
    ['name'=>'Patient Nana Gyamfi',   'email'=>'nana.gyamfi@rmu.test',         'role'=>'patient',    'gender'=>'Male'],
    ['name'=>'Finance Adwoa Ofori',   'email'=>'adwoa.ofori.finance@rmu.test', 'role'=>'finance',    'gender'=>'Female'],
    ['name'=>'Lab Tech Bright Amoah', 'email'=>'bright.amoah.lab@rmu.test',   'role'=>'lab_tech',   'gender'=>'Male'],
];
foreach ($seed_users as $u) {
    $id = ins($conn, 'users', [
        'name'           => $u['name'],
        'user_name'      => strtolower(str_replace([' '], ['_'], explode('@', $u['email'])[0])) . rand(10,99),
        'email'          => $u['email'],
        'password'       => $pwd_hash,
        'user_role'      => $u['role'],
        'gender'         => $u['gender'],
        'is_active'      => 1,
        'is_verified'    => 1,
        'account_status' => 'active',
        'status'         => 'active',
        'created_at'     => rdate('-1 year', '-1 month'),
    ]);
    if (is_int($id)) {
        $new_user_ids[$u['role']][] = $id;
        $existing_user_ids[] = $id;
    } else {
        $errors[] = "users: $id";
    }
}
$log[] = "users: " . count($new_user_ids ? array_merge(...array_values($new_user_ids)) : []) . " rows";

// ─── 3. PATIENTS ──────────────────────────────────────────────────────────
echo "[2/30] Seeding patients...\n";
$patient_user_ids = $new_user_ids['patient'] ?? [];
foreach ($patient_user_ids as $uid) {
    $gender = rnd($genders);
    $fn = $gender === 'Male' ? rnd($first_names_male) : rnd($first_names_female);
    $ln = rnd($last_names);
    $id = ins($conn, 'patients', [
        'user_id'       => $uid,
        'patient_id'    => 'PAT-' . rand(1000,9999),
        'full_name'     => "$fn $ln",
        'date_of_birth' => rdate_only('-60 years','-18 years'),
        'gender'        => $gender === 'Male' ? 'Male' : 'Female',
        'blood_group'   => rnd($blood_groups),
        'secondary_phone'         => '024' . rand(1000000,9999999),
        'street_address'       => rand(1,50) . ' Lagos Town, Accra',
        'emergency_contact_name'  => rnd($first_names_male).' '.rnd($last_names),
        'emergency_contact_phone' => '050' . rand(1000000,9999999),
        'account_status' => 'active',
        'created_at'     => rdate('-1 year', '-1 month'),
    ]);
    if (is_int($id)) {
        $new_patient_ids[] = $id;
        $existing_patient_ids[] = $id;
    } else $errors[] = "patients: $id";
}
// Add extra patients if new_user_ids patient is empty
if (empty($new_patient_ids)) {
    for ($i = 0; $i < 5; $i++) {
        $gender = rnd($genders);
        $fn = $gender === 'Male' ? rnd($first_names_male) : rnd($first_names_female);
        $ln = rnd($last_names);
        $uid = rnd($existing_user_ids);
        $id = ins($conn, 'patients', [
            'user_id'       => $uid,
            'patient_id'    => 'PAT-' . rand(5000,9999),
            'full_name'     => "$fn $ln",
            'date_of_birth' => rdate_only('-60 years','-18 years'),
            'gender'        => $gender === 'Male' ? 'Male' : 'Female',
            'blood_group'   => rnd($blood_groups),
            'secondary_phone' => '024' . rand(1000000,9999999),
            'street_address'=> rand(1,50) . ' Accra, Ghana',
            'created_at' => rdate('-1 year', '-1 month'),
            'account_status'=> 'active',
        ]);
        if (is_int($id)) { $new_patient_ids[] = $id; $existing_patient_ids[] = $id; }
    }
}
$log[] = "patients: " . count($new_patient_ids) . " rows";

// ─── 4. DOCTORS ───────────────────────────────────────────────────────────
echo "[3/30] Seeding doctors...\n";
$doctor_user_ids = $new_user_ids['doctor'] ?? [];
foreach ($doctor_user_ids as $uid) {
    $spec = rnd($specializations);
    $id = ins($conn, 'doctors', [
        'doctor_id'           => 'DOC-'.rand(1000,9999),
        'user_id'             => $uid,
        'full_name'           => 'Dr. ' . rnd($first_names_male) . ' ' . rnd($last_names),
        'specialization'      => $spec,
        'license_number'      => 'GH-MED-' . rand(10000,99999),
        'experience_years'    => rand(3, 25),
        'availability_status' => rnd(['Online','Offline','Busy']),
        'is_available'        => 1,
        'consultation_fee'    => rand(100, 500) * 1.0,
        'bio'                 => "Experienced $spec specialist at RMU Medical Sickbay.",
        'approval_status'     => 'approved',
        'created_at'          => rdate('-2 years', '-6 months'),
    ]);
    if (is_int($id)) {
        $new_doctor_ids[] = $id;
        $existing_doctor_ids[] = $id;
    } else $errors[] = "doctors: $id";
}
$log[] = "doctors: " . count($new_doctor_ids) . " rows";

// ─── 5. NURSES ────────────────────────────────────────────────────────────
echo "[4/30] Seeding nurses...\n";
$nurse_user_ids = $new_user_ids['nurse'] ?? [];
foreach ($nurse_user_ids as $uid) {
    $id = ins($conn, 'nurses', [
        'user_id'        => $uid,
        'nurse_id'       => 'NRS-' . rand(1000, 9999),
        'full_name'      => 'Nurse ' . rnd($first_names_female) . ' ' . rnd($last_names),
        'gender'         => rnd(['Male','Female']),
        'email'          => 'nurse'.rand(100,999).'@rmu.test',
        'department_id'  => 1,
        'license_number' => 'GH-NRS-' . rand(10000,99999),
        'shift_type'     => rnd(['Morning','Afternoon','Night']),
        'years_of_experience' => rand(1,15),
        'status'         => 'Active',
        'approval_status'=> 'approved',
        'created_at'     => rdate('-1 year', '-2 months'),
    ]);
    if (!is_int($id)) $errors[] = "nurses: $id";
}
$log[] = "nurses: " . count($nurse_user_ids) . " rows";

// ─── 6. DEPARTMENTS ────────────────────────────────────────────────────────
echo "[5/30] Seeding departments...\n";
foreach (array_slice($departments_list, 0, 5) as $dept_name) {
    $id = ins($conn, 'departments', [
        'name'        => $dept_name . ' Dept',
        'description' => "The $dept_name department at RMU Medical Sickbay.",
        'is_active'   => 1,
        'created_at'  => rdate('-3 years', '-1 year'),
    ]);
    if (is_int($id)) $existing_dept_ids[] = $id;
    else $errors[] = "departments: $id";
}
$log[] = "departments: 5 rows";

// ─── 7. MEDICINES ──────────────────────────────────────────────────────────
echo "[6/30] Seeding medicines...\n";
foreach ($medications as $med) {
    $id = ins($conn, 'medicines', [
        'medicine_id'   => 'MED-' . rand(10000,99999),
        'medicine_name' => $med,
        'generic_name'  => $med,
        'category'      => rnd(['Antibiotic','Analgesic','Antidiabetic','Antimalarial','Antihypertensive']),
        'unit'          => rnd(['Tablet','Capsule','Syrup','Injection']),
        'unit_price'    => rand(5, 200) * 1.0,
        'stock_quantity'=> rand(50, 500),
        'reorder_level' => rand(20, 50),
        'status'        => 'active',
        'created_at'    => rdate('-2 years', '-6 months'),
    ]);
    if (is_int($id)) $existing_med_ids[] = $id;
    else $errors[] = "medicines: $id";
}
$log[] = "medicines: " . count($medications) . " rows";

// ─── 8. APPOINTMENTS ──────────────────────────────────────────────────────
echo "[7/30] Seeding appointments...\n";
for ($i = 0; $i < 10; $i++) {
    $pat_id = rnd($existing_patient_ids);
    $doc_id = rnd($existing_doctor_ids);
    $status = rnd($statuses_appt);
    $appt_date = $status === 'scheduled' ? future_date() : rdate_only('-6 months', 'now');
    $complaint = rnd(['headache','fever','chest pain','fatigue','cough']);
    $id = ins($conn, 'appointments', [
        'appointment_id'  => 'APT-' . rand(10000,99999),
        'patient_id'      => $pat_id,
        'doctor_id'       => $doc_id,
        'appointment_date'=> $appt_date,
        'appointment_time'=> rnd(['08:00:00','09:30:00','10:00:00','11:00:00','14:00:00','15:30:00','16:00:00']),
        'status'          => ucfirst($status),
        'service_type'    => rnd(['Consultation','Follow-Up','Check-Up','Emergency']),
        'symptoms'        => "Patient complains of $complaint.",
        'notes'           => rnd(['Routine check-up','Follow-up on medication','New patient visit']),
        'urgency_level'   => rnd(['Low','Medium','High']),
        'created_at'      => rdate('-6 months', 'now'),
    ]);
    if (is_int($id)) {
        $new_appt_ids[] = $id;
        $existing_appt_ids[] = $id;
    } else $errors[] = "appointments: $id";
}
$log[] = "appointments: 10 rows";

// ─── 9. MEDICAL RECORDS ────────────────────────────────────────────────────
echo "[8/30] Seeding medical_records...\n";
for ($i = 0; $i < 10; $i++) {
    $pat_id = rnd($existing_patient_ids);
    $doc_id = rnd($existing_doctor_ids);
    $diag   = rnd($diagnoses);
    $id = ins($conn, 'medical_records', [
        'record_id'        => 'REC-' . rand(10000,99999),
        'patient_id'       => $pat_id,
        'doctor_id'        => $doc_id,
        'visit_date'       => rdate_only('-6 months', 'now'),
        'symptoms'         => "Patient presents with $diag symptoms.",
        'diagnosis'        => $diag,
        'treatment'        => "Prescribed " . rnd($medications) . " " . rnd($dosages) . ".",
        'treatment_plan'   => "Take medications as directed. Rest and increase fluid intake.",
        'vital_signs'      => json_encode(['bp'=>rand(110,145).'/'.rand(70,95),'temp'=>number_format(rand(365,380)/10,1),'pulse'=>rand(60,100),'spo2'=>rand(95,100)]),
        'notes'            => "Patient responded well to initial treatment.",
        'severity'         => rnd(['Mild','Moderate','Severe']),
        'follow_up_required'=> 1,
        'follow_up_date'   => future_date('now', '+2 months'),
        'patient_visible'  => 1,
        'created_at'       => rdate('-6 months', 'now'),
    ]);
    if (is_int($id)) $existing_rec_ids[] = $id;
    else $errors[] = "medical_records: $id";
}
$log[] = "medical_records: 10 rows";

// ─── 10. PATIENT VITALS ───────────────────────────────────────────────────
echo "[9/30] Seeding patient_vitals...\n";
for ($i = 0; $i < 10; $i++) {
    $pat_id = rnd($existing_patient_ids);
    $sys = rand(100,155); $dia = rand(65,95);
    $id = ins($conn, 'patient_vitals', [
        'vital_id'          => 'VIT-' . rand(10000,99999),
        'patient_id'        => $pat_id,
        'nurse_id'          => rnd(array_merge($existing_nurse_ids, [1])),
        'bp_systolic'       => $sys,
        'bp_diastolic'      => $dia,
        'temperature'       => number_format(rand(365,390)/10, 1),
        'pulse_rate'        => rand(60, 110),
        'respiratory_rate'  => rand(14, 22),
        'oxygen_saturation' => rand(94, 100),
        'weight'            => rand(50, 110),
        'height'            => rand(155, 195),
        'blood_glucose'     => rand(70, 180),
        'is_flagged'        => rnd([0,0,0,1]),
        'doctor_notified'   => 0,
        'recorded_at'       => rdate('-3 months', 'now'),
        'created_at'        => rdate('-3 months', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "patient_vitals: $id";
}
$log[] = "patient_vitals: 10 rows";

// ─── 11. PRESCRIPTIONS ────────────────────────────────────────────────────
echo "[10/30] Seeding prescriptions...\n";
for ($i = 0; $i < 10; $i++) {
    $pat_id = rnd($existing_patient_ids);
    $doc_id = rnd($existing_doctor_ids);
    $status = rnd(['active','completed','cancelled']);
    $med_name = rnd($medications);
    $id = ins($conn, 'prescriptions', [
        'prescription_id'  => 'RX-' . rand(10000,99999),
        'patient_id'       => $pat_id,
        'doctor_id'        => $doc_id,
        'medication_name'  => $med_name,
        'dosage'           => rnd($dosages),
        'frequency'        => rnd($frequencies),
        'duration'         => rnd(['3 days','5 days','7 days','10 days','14 days']),
        'quantity'         => rand(10,30),
        'refills_allowed'  => rnd([0,1,2,3]),
        'instructions'     => 'Take all medications as directed. Drink plenty of water.',
        'prescription_date'=> rdate_only('-3 months', 'now'),
        'status'           => rnd(['Pending','Dispensed','Partially Dispensed']),
        'created_at'       => rdate('-3 months', 'now'),
    ]);
    if (is_int($id)) {
        $new_rx_ids[] = $id;
        $existing_rx_ids[] = $id;
    } else $errors[] = "prescriptions: $id";
}
$log[] = "prescriptions: 10 rows";

// ─── 12. PRESCRIPTION ITEMS ───────────────────────────────────────────────
echo "[11/30] Seeding prescription_items...\n";
foreach (array_slice(array_merge($new_rx_ids, $existing_rx_ids), 0, 10) as $rx_id) {
    for ($j = 0; $j < 2; $j++) {
        $id = ins($conn, 'prescription_items', [
            'prescription_id'      => $rx_id,
            'medicine_id'          => rnd($existing_med_ids),
            'dosage'               => rnd($dosages),
            'frequency'            => rnd($frequencies),
            'duration'             => rnd(['3 days','5 days','7 days','10 days','14 days']),
            'quantity'             => rand(10, 30),
            'dispensed_quantity'   => 0,
            'instructions'         => rnd(['Take after food','Take before bed','Take on empty stomach','Take with water']),
            'substitution_allowed' => 0,
            'status'               => 'pending',
        ]);
        if (!is_int($id)) $errors[] = "prescription_items: $id";
    }
}
$log[] = "prescription_items: ~20 rows";

// ─── 13. LAB TEST CATALOG ─────────────────────────────────────────────────
echo "[12/30] Seeding lab_test_catalog...\n";
$cat_ids = [];
foreach ($lab_tests as $test) {
    $code = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', $test))));
    $id = ins($conn, 'lab_test_catalog', [
        'test_name'              => $test,
        'test_code'              => substr($code,0,6) . rand(100,999),
        'category'               => rnd(['Hematology','Microbiology','Biochemistry','Immunology','Urinalysis']),
        'sample_type'            => rnd(['Blood','Urine','Stool','Swab','Tissue']),
        'price'                  => rand(50, 300) * 1.0,
        'normal_turnaround_hours'=> rand(2, 48),
        'requires_fasting'       => rnd([0,0,1]),
        'is_active'              => 1,
        'created_at'             => rdate('-2 years', '-6 months'),
    ]);
    if (is_int($id)) $cat_ids[] = $id;
    else $errors[] = "lab_test_catalog: $id";
}
$log[] = "lab_test_catalog: " . count($lab_tests) . " rows";

// ─── 14. LAB TEST ORDERS ──────────────────────────────────────────────────
echo "[13/30] Seeding lab_test_orders...\n";
for ($i = 0; $i < 10; $i++) {
    $pat_id = rnd($existing_patient_ids);
    $doc_id = rnd($existing_doctor_ids);
    $status = rnd(['pending','in_progress','completed','rejected']);
    $id = ins($conn, 'lab_test_orders', [
        'order_id'       => 'ORD-' . rand(10000,99999),
        'patient_id'     => $pat_id,
        'doctor_id'      => $doc_id,
        'test_name'      => rnd($lab_tests),
        'urgency'        => rnd(['Routine','Urgent','STAT']),
        'order_status'   => rnd(['Pending','Accepted','Processing','Completed']),
        'clinical_notes' => 'Investigate for ' . rnd($diagnoses),
        'order_date'     => rdate_only('-3 months', 'now'),
    ]);
    if (is_int($id)) {
        $new_lab_order_ids[] = $id;
        $existing_lab_order_ids[] = $id;
    } else $errors[] = "lab_test_orders: $id";
}
$log[] = "lab_test_orders: 10 rows";

// ─── 15. LAB RESULTS ──────────────────────────────────────────────────────
echo "[14/30] Seeding lab_results...\n";
foreach (array_slice($new_lab_order_ids, 0, 8) as $order_id) {
    $test_id = rnd(array_merge($cat_ids, [1]));
    $result_val = number_format(rand(50,300)/10, 1);
    $id = ins($conn, 'lab_results', [
        'patient_id'           => rnd($existing_patient_ids),
        'test_id'              => $test_id,
        'order_id'             => $order_id,
        'doctor_id'            => rnd($existing_doctor_ids),
        'test_date'            => rdate_only('-2 months', 'now'),
        'result_date'          => rdate_only('-2 months', 'now'),
        'status'               => 'Completed',
        'results'              => json_encode(['value' => $result_val, 'unit' => rnd(['mg/dL','g/dL','mmol/L','U/L'])]),
        'interpretation'       => 'Results reviewed and validated.',
        'result_interpretation'=> rnd(['Normal','Abnormal','Critical']),
        'technician_id'        => rnd(array_merge($existing_lab_ids, [1])),
        'patient_accessible'   => 1,
        'patient_notified'     => 0,
        'doctor_reviewed'      => 0,
        'released_to_doctor'   => 1,
        'released_to_patient'  => 1,
        'result_status'        => 'Released',
        'created_at'           => rdate('-2 months', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "lab_results: $id";
}
$log[] = "lab_results: 8 rows";

// ─── 16. WARDS ────────────────────────────────────────────────────────────
echo "[15/30] Seeding wards...\n";
foreach ($ward_names as $wn) {
    $id = ins($conn, 'wards', [
        'ward_name'   => $wn,
        'capacity'    => rand(10, 40),
        'status'      => 'Active',
        'current_occupancy' => rand(0,5),
        'created_at'  => rdate('-3 years', '-1 year'),
    ]);
    if (is_int($id)) $existing_ward_ids[] = $id;
    else $errors[] = "wards: $id";
}
$log[] = "wards: " . count($ward_names) . " rows";

// ─── 17. BEDS ─────────────────────────────────────────────────────────────
echo "[16/30] Seeding beds...\n";
for ($i = 0; $i < 10; $i++) {
    $ward_id = rnd($existing_ward_ids);
    $id = ins($conn, 'beds', [
        'bed_id'     => 'BED-' . rand(1000, 9999),
        'bed_number' => 'B-' . rand(1, 50) . '-' . rand(100,999),
        'ward'       => rnd($ward_names),
        'bed_type'   => rnd(['General','ICU','Private','Semi-Private']),
        'status'     => rnd(['Available','Occupied','Maintenance','Reserved']),
        'daily_rate' => rand(50, 500) * 1.0,
        'created_at' => rdate('-2 years', '-6 months'),
    ]);
    if (is_int($id)) {
        $new_bed_ids[] = $id;
        $existing_bed_ids[] = $id;
    } else $errors[] = "beds: $id";
}
$log[] = "beds: 10 rows";

// ─── 18. BED ASSIGNMENTS ──────────────────────────────────────────────────
echo "[17/30] Seeding bed_assignments...\n";
for ($i = 0; $i < 6; $i++) {
    $id = ins($conn, 'bed_assignments', [
        'assignment_id'   => 'ASSIGN-' . rand(1000,9999),
        'patient_id'      => rnd($existing_patient_ids),
        'bed_id'          => rnd($existing_bed_ids),
        'assigned_nurse_id' => rnd($existing_nurse_ids),
        'admission_date'  => rdate('-2 months', '-1 week'),
        'discharge_date'  => rnd(['NULL', date('Y-m-d H:i:s', strtotime('+2 weeks'))]),
        'status'          => rnd(['Active','Discharged']),
        'reason'          => 'Standard admission procedure followed.',
        'created_at'      => rdate('-2 months', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "bed_assignments: $id";
}
$log[] = "bed_assignments: 6 rows";

// ─── 19. BILLING INVOICES ─────────────────────────────────────────────────
echo "[18/30] Seeding billing_invoices...\n";
for ($i = 0; $i < 10; $i++) {
    $pat_id = rnd($existing_patient_ids);
    $status = rnd($invoice_statuses);
    $total  = rand(200, 5000) * 1.0;
    $paid   = $status === 'paid' ? $total : ($status === 'partial' ? rand(50, (int)$total - 50) * 1.0 : 0.0);
    $inv_status_map = ['pending'=>'Pending','paid'=>'Paid','partial'=>'Partially Paid','overdue'=>'Overdue'];
    $id = ins($conn, 'billing_invoices', [
        'patient_id'      => $pat_id,
        'invoice_number'  => 'INV-' . rand(10000, 99999),
        'subtotal'        => $total,
        'tax_amount'      => round($total * 0.025, 2),
        'discount_amount' => 0.00,
        'total_amount'    => $total,
        'paid_amount'     => $paid,
        'balance_due'     => $total - $paid,
        'currency'        => 'GHS',
        'status'          => $inv_status_map[$status] ?? 'Pending',
        'notes'           => 'Invoice generated for medical services rendered.',
        'invoice_date'    => rdate_only('-3 months', 'now'),
        'due_date'        => future_date('now', '+1 month'),
        'is_student_invoice' => 0,
        'created_at'      => rdate('-3 months', 'now'),
    ]);
    if (is_int($id)) {
        $new_inv_ids[] = $id;
        $existing_inv_ids[] = $id;
    } else $errors[] = "billing_invoices: $id";
}
$log[] = "billing_invoices: 10 rows";

// ─── 20. INVOICE LINE ITEMS ───────────────────────────────────────────────
echo "[19/30] Seeding invoice_line_items...\n";
foreach (array_slice($new_inv_ids, 0, 8) as $inv_id) {
    for ($j = 0; $j < 2; $j++) {
        $qty   = rand(1,3);
        $price = rand(50, 500) * 1.0;
        $disc  = 0.00;
        $tax   = round($price * $qty * 0.025, 2);
        $total = round($qty * $price - $disc + $tax, 2);
        $id = ins($conn, 'invoice_line_items', [
            'invoice_id'          => (int)$inv_id,
            'service_description' => rnd(['Consultation Fee','Laboratory Test','Medication','Ward Charges','Procedure Fee','Nursing Care']),
            'quantity'            => $qty,
            'unit_price'          => $price,
            'discount_pct'        => 0,
            'discount_amount'     => $disc,
            'tax_amount'          => $tax,
            'line_total'          => $total,
            'created_at'          => rdate('-3 months', 'now'),
        ]);
        if (!is_int($id)) $errors[] = "invoice_line_items: $id";
    }
}
$log[] = "invoice_line_items: ~16 rows";

// ─── 21. PAYMENTS ─────────────────────────────────────────────────────────
echo "[20/30] Seeding payments...\n";
for ($i = 0; $i < 8; $i++) {
    $inv_id = rnd(array_merge($new_inv_ids, $existing_inv_ids));
    $inv_id_uint = (int)$inv_id;
    $pay_ref = 'PAY-' . strtoupper(bin2hex(random_bytes(5)));
    $id = ins($conn, 'payments', [
        'payment_reference'=> $pay_ref,
        'invoice_id'       => $inv_id_uint,
        'patient_id'       => rnd($existing_patient_ids),
        'amount'           => rand(100, 2000) * 1.0,
        'currency'         => 'GHS',
        'payment_method'   => rnd(['Cash','Mobile Money','Card','Paystack']),
        'payment_date'     => rdate('-3 months', 'now'),
        'status'           => rnd(['Completed','Pending','Failed']),
        'channel'          => rnd(['Counter','Online','Mobile']),
        'receipt_number'   => 'RCP-' . rand(10000,99999),
        'notes'            => 'Payment received at cashier.',
        'reconciled'       => 0,
        'created_at'       => rdate('-3 months', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "payments: $id";
}
$log[] = "payments: 8 rows";

// ─── 22. NOTIFICATIONS ────────────────────────────────────────────────────
echo "[21/30] Seeding notifications...\n";
$notif_msgs = [
    'Your appointment has been confirmed.',
    'Lab results are now available.',
    'Your prescription has been dispensed.',
    'Reminder: appointment tomorrow at 10:00 AM.',
    'Your invoice has been generated.',
    'New medical record has been updated.',
    'Password changed successfully.',
    'Your refill request has been approved.',
    'Payment received successfully.',
    'Welcome to RMU Medical Sickbay!',
];
foreach ($notif_msgs as $msg) {
    $id = ins($conn, 'notifications', [
        'user_id'    => rnd($existing_user_ids),
        'type'       => rnd($complaint_types),
        'title'      => 'Medical Update',
        'message'    => $msg,
        'is_read'    => rnd([0,0,0,1]),
        'created_at' => rdate('-1 month', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "notifications: $id";
}
$log[] = "notifications: 10 rows";

// ─── 23. EMERGENCY ALERTS ─────────────────────────────────────────────────
echo "[22/30] Seeding emergency_alerts...\n";
$alert_msgs = [
    'Code Blue — cardiac arrest in Ward A.',
    'Patient fall reported in Room 12.',
    'Critical lab result for patient ID P-101.',
    'Fire alarm activated — evacuation in progress.',
    'Missing patient reported — alert all staff.',
];
foreach ($alert_msgs as $msg) {
    $nurse_id = rnd(array_merge($existing_nurse_ids, [rnd($existing_user_ids)]));
    $id = ins($conn, 'emergency_alerts', [
        'alert_id'      => 'ALT-' . rand(10000,99999),
        'nurse_id'      => (int)$nurse_id,
        'patient_id'    => rnd($existing_patient_ids),
        'alert_type'    => rnd(['Code Blue','Rapid Response','Fall','Cardiac Arrest','General Emergency']),
        'severity'      => rnd(['Critical','High','Medium','Low']),
        'message'       => $msg,
        'status'        => rnd(['Active','Responded','Resolved']),
        'triggered_at'  => rdate('-1 month', 'now'),
        'created_at'    => rdate('-1 month', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "emergency_alerts: $id";
}
$log[] = "emergency_alerts: 5 rows";

// ─── 24. PATIENT ACTIVITY LOG ─────────────────────────────────────────────
echo "[23/30] Seeding patient_activity_log...\n";
$activities = ['Logged in','Updated medical profile','Booked appointment','Cancelled appointment','Viewed prescription','Downloaded invoice','Changed password','Submitted refill request','Viewed lab results','Updated emergency contact'];
for ($i = 0; $i < 10; $i++) {
    $pat_id = rnd($existing_patient_ids);
    $id = ins($conn, 'patient_activity_log', [
        'patient_id'       => $pat_id,
        'user_id'          => rnd($existing_user_ids),
        'action_type'      => rnd(['login','view','update','booking','download','payment']),
        'action_description' => rnd($activities),
        'ip_address'       => rand(102,192).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,254),
        'device_info'      => 'Chrome/120 Windows',
        'created_at'       => rdate('-2 months', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "patient_activity_log: $id";
}
$log[] = "patient_activity_log: 10 rows";

// ─── 25. APPOINTMENT REMINDERS ────────────────────────────────────────────
echo "[24/30] Seeding appointment_reminders...\n";
foreach (array_slice(array_merge($new_appt_ids, $existing_appt_ids), 0, 8) as $appt_id) {
    $id = ins($conn, 'appointment_reminders', [
        'appointment_id' => $appt_id,
        'reminder_type'  => rnd(['email','sms','notification']),
        'scheduled_time' => rdate('-2 months', 'now'),
        'sent_at'        => rdate('-2 months', 'now'),
        'status'         => rnd(['Sent','Pending','Failed']),
        'created_at'     => rdate('-2 months', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "appointment_reminders: $id";
}
$log[] = "appointment_reminders: 8 rows";

// ─── 26. EMERGENCY CONTACTS ───────────────────────────────────────────────
echo "[25/30] Seeding emergency_contacts...\n";
for ($i = 0; $i < 8; $i++) {
    $id = ins($conn, 'emergency_contacts', [
        'patient_id'   => rnd($existing_patient_ids),
        'contact_name' => rnd($first_names_male).' '.rnd($last_names),
        'relationship' => rnd(['Spouse','Parent','Sibling','Child','Friend']),
        'phone'        => '054' . rand(1000000, 9999999),
        'email'        => 'contact'.rand(100,999).'@gmail.com',
        'address'      => rand(1,20).' Kumasi, Ghana',
        'is_primary'   => ($i === 0) ? 1 : 0,
        'created_at'   => rdate('-1 year', '-1 month'),
    ]);
    if (!is_int($id)) $errors[] = "emergency_contacts: $id";
}
$log[] = "emergency_contacts: 8 rows";

// ─── 27. AMBULANCE REQUESTS ───────────────────────────────────────────────
echo "[26/30] Seeding ambulance_requests...\n";
for ($i = 0; $i < 5; $i++) {
    $fn = rnd($first_names_male).' '.rnd($last_names);
    $id = ins($conn, 'ambulance_requests', [
        'request_id'     => 'AMB-' . rand(10000,99999),
        'patient_name'   => $fn,
        'patient_phone'  => '024' . rand(1000000, 9999999),
        'pickup_location'=> rand(1,50).' Main Street, Accra, Ghana',
        'destination'    => 'RMU Medical Sickbay, Accra',
        'emergency_type' => rnd(['Cardiac Arrest','Trauma','Stroke','Obstetric','General Medical']),
        'status'         => rnd(['Pending','Dispatched','In Transit','Completed']),
        'request_time'   => rdate('-2 months', 'now'),
        'notes'          => rnd(['Patient conscious','Patient unconscious','Minor injury','Critical condition']),
        'created_at'     => rdate('-2 months', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "ambulance_requests: $id";
}
$log[] = "ambulance_requests: 5 rows";

// ─── 28. MEDICINE INVENTORY / STOCK TRANSACTIONS ──────────────────────────
echo "[27/30] Seeding stock_transactions...\n";
for ($i = 0; $i < 10; $i++) {
    $qty_change = rand(5, 100);
    $prev_qty   = rand(100, 500);
    $id = ins($conn, 'stock_transactions', [
        'medicine_id'      => rnd($existing_med_ids),
        'transaction_type' => rnd(['restock','dispensed','expired','adjusted','returned']),
        'quantity'         => $qty_change,
        'previous_quantity'=> $prev_qty,
        'new_quantity'     => $prev_qty + $qty_change,
        'notes'            => rnd(['Restocking','Dispensed to patient','Expired','Adjustment','Return']),
        'performed_by'     => rnd($existing_user_ids),
        'transaction_date' => rdate('-3 months', 'now'),
        'created_at'       => rdate('-3 months', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "stock_transactions: $id";
}
$log[] = "stock_transactions: 10 rows";

// ─── 29. AUDIT LOG ────────────────────────────────────────────────────────
echo "[28/30] Seeding audit_log...\n";
$audit_actions = ['login','logout','view_record','update_record','create_appointment','cancel_appointment','generate_invoice','process_payment','modify_prescription','download_report'];
for ($i = 0; $i < 10; $i++) {
    $id = ins($conn, 'audit_log', [
        'user_id'    => rnd($existing_user_ids),
        'action'     => rnd($audit_actions),
        'table_name' => rnd(['appointments','medical_records','prescriptions','billing_invoices','patients']),
        'record_id'  => (string)rand(1, 100),
        'old_values' => 'NULL',
        'new_values' => json_encode(['status'=>'updated']),
        'ip_address' => rand(10,192).'.'.rand(1,255).'.'.rand(0,255).'.'.rand(1,254),
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
        'created_at' => rdate('-3 months', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "audit_log: $id";
}
$log[] = "audit_log: 10 rows";

// ─── 30. BROADCASTS ───────────────────────────────────────────────────────
echo "[29/30] Seeding broadcasts...\n";
$broadcast_msgs = [
    ['title'=>'System Maintenance Notice','msg'=>'The system will undergo scheduled maintenance on Saturday from 2 AM – 4 AM.'],
    ['title'=>'COVID-19 Health Advisory','msg'=>'All staff and patients are reminded to wear masks in clinical areas.'],
    ['title'=>'Pharmacy Update','msg'=>'New medications have been added to the pharmacy inventory.'],
    ['title'=>'Emergency Drill','msg'=>'A fire evacuation drill is scheduled for next Friday at noon.'],
    ['title'=>'New Lab Equipment','msg'=>'The laboratory has received new diagnostic equipment effective this week.'],
];
foreach ($broadcast_msgs as $bc) {
    $id = ins($conn, 'broadcasts', [
        'subject'      => $bc['title'],
        'body'         => $bc['msg'],
        'priority'     => rnd(['Informational','Important','Urgent']),
        'sender_id'    => rnd($existing_user_ids),
        'audience_type'=> rnd(['Everyone','Role','Department']),
        'status'       => 'Sent',
        'created_at'   => rdate('-1 month', 'now'),
    ]);
    if (!is_int($id)) $errors[] = "broadcasts: $id";
}
$log[] = "broadcasts: 5 rows";

// ─── 31. DOCTOR AVAILABILITY ──────────────────────────────────────────────
echo "[30/30] Seeding doctor_availability...\n";
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
mysqli_query($conn, "TRUNCATE TABLE doctor_availability");
foreach (array_slice($existing_doctor_ids, 0, 5) as $doc_id) {
    foreach (array_slice($days, 0, 3) as $day) {
        $id = ins($conn, 'doctor_availability', [
            'doctor_id'        => $doc_id,
            'day_of_week'      => $day,
            'start_time'       => '08:00:00',
            'end_time'         => '17:00:00',
            'slot_duration_min'=> 30,
            'max_appointments' => 16,
            'is_available'     => 1,
        ]);
        if (!is_int($id)) $errors[] = "doctor_availability: $id (may already exist)";
    }
}
$log[] = "doctor_availability: seeded per doctor";

// ─── Re-enable FK checks ──────────────────────────────────────────────────
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");

// ─── SUMMARY ──────────────────────────────────────────────────────────────
echo "\n========================================\n";
echo "  SEEDER COMPLETE — SUMMARY\n";
echo "========================================\n";
foreach ($log as $l) echo "  ✓ $l\n";

echo "\n------ ERRORS (" . count($errors) . ") ------\n";
if (empty($errors)) {
    echo "  No errors! All records inserted cleanly.\n";
} else {
    foreach ($errors as $e) echo "  ✗ $e\n";
}

echo "\n========================================\n";
echo "  SEEDED CREDENTIALS\n";
echo "========================================\n";
echo "  Password (ALL accounts): Sickbay@2025\n\n";
echo "  PATIENTS:\n";
echo "    Emmanuel Kofi      — emmanuel.kofi@rmu.test\n";
echo "    Yaa Acheampong     — yaa.acheampong@rmu.test\n";
echo "    Nana Gyamfi        — nana.gyamfi@rmu.test\n\n";
echo "  DOCTORS:\n";
echo "    Dr. Kwame Mensah   — kwame.mensah.doc@rmu.test\n";
echo "    Dr. Abena Asante   — abena.asante.doc@rmu.test\n";
echo "    Dr. Samuel Boateng — samuel.boateng.doc@rmu.test\n\n";
echo "  NURSES:\n";
echo "    Efua Owusu         — efua.owusu.nurse@rmu.test\n";
echo "    Isaac Appiah       — isaac.appiah.nurse@rmu.test\n\n";
echo "  FINANCE:\n";
echo "    Adwoa Ofori        — adwoa.ofori.finance@rmu.test\n\n";
echo "  LAB TECHNICIAN:\n";
echo "    Bright Amoah       — bright.amoah.lab@rmu.test\n";
echo "========================================\n";
