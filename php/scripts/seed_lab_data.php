<?php
// ============================================
// RMU MEDICAL SYSTEM - LAB DATA SEEDER
// ============================================
// This script assumes it is included from lab_actions.php context
// so $conn, $user_id, etc. are available.

if (!isset($conn)) { die("Direct access forbidden."); }

// Only allow execution if the catalog is empty to prevent duplicates
$check = mysqli_query($conn, "SELECT COUNT(*) FROM lab_test_catalog");
$count = mysqli_fetch_row($check)[0];

if ($count > 0) {
    throw new Exception("Seeder aborted: Database already contains test catalog data.");
}

// 1. LAB TEST CATALOG SEED DATA
$catalog_seeds = [
    // Hematology
    ['Complete Blood Count (CBC)', 'CBC01', 'Hematology', 'Blood', 'EDTA Tube (Purple)', 'Fasting not strictly required', 2, 4, 50.00, 0, 1],
    ['Hemoglobin (Hb)', 'HB02', 'Hematology', 'Blood', 'EDTA Tube (Purple)', 'None', 1, 2, 20.00, 0, 1],
    ['Erythrocyte Sedimentation Rate (ESR)', 'ESR03', 'Hematology', 'Blood', 'EDTA Tube (Purple)', 'None', 1, 2, 25.00, 0, 1],
    ['Malaria Parasite (MP) Screen', 'MP04', 'Hematology', 'Blood', 'EDTA Tube (Purple)', 'None', 1, 3, 30.00, 0, 1],
    ['Blood Grouping & Rh Typing', 'BG05', 'Hematology', 'Blood', 'EDTA Tube (Purple)', 'None', 1, 3, 40.00, 0, 1],

    // Biochemistry
    ['Fasting Blood Sugar (FBS)', 'FBS01', 'Biochemistry', 'Blood', 'Fluoride Tube (Grey)', 'Patient MUST fast for 8-12 hours', 1, 3, 30.00, 1, 1],
    ['Random Blood Sugar (RBS)', 'RBS02', 'Biochemistry', 'Blood', 'Fluoride Tube', 'None', 1, 3, 30.00, 0, 1],
    ['Liver Function Test (LFT)', 'LFT03', 'Biochemistry', 'Blood', 'SST (Yellow/Red)', 'Fasting preferred', 3, 6, 120.00, 1, 1],
    ['Kidney / Renal Function Test (KFT)', 'KFT04', 'Biochemistry', 'Blood', 'SST (Yellow/Red)', 'None', 3, 6, 110.00, 0, 1],
    ['Lipid Profile', 'LIP05', 'Biochemistry', 'Blood', 'SST (Yellow/Red)', 'Patient MUST fast for 10-12 hours', 3, 6, 150.00, 1, 1],
    ['Uric Acid', 'URA06', 'Biochemistry', 'Blood', 'SST (Yellow/Red)', 'None', 2, 4, 45.00, 0, 1],

    // Immunology / Serology
    ['HIV I & II Screening', 'HIV01', 'Immunology', 'Blood', 'SST (Yellow/Red)', 'Pre-test counseling required', 1, 2, 50.00, 0, 1],
    ['Hepatitis B Surface Antigen (HBsAg)', 'HBS02', 'Immunology', 'Blood', 'SST (Yellow/Red)', 'None', 1, 2, 60.00, 0, 1],
    ['Hepatitis C Virus (HCV) Test', 'HCV03', 'Immunology', 'Blood', 'SST (Yellow/Red)', 'None', 1, 2, 65.00, 0, 1],
    ['Widal Test (Typhoid)', 'WID04', 'Immunology', 'Blood', 'SST (Yellow/Red)', 'None', 1, 2, 40.00, 0, 1],
    ['Syphilis (VDRL/RPR)', 'VDR05', 'Immunology', 'Blood', 'SST (Yellow/Red)', 'None', 1, 2, 45.00, 0, 1],
    ['Pregnancy Test (Serum hCG)', 'HCG06', 'Immunology', 'Blood', 'SST (Yellow/Red)', 'First morning sample preferred', 1, 2, 50.00, 0, 1],

    // Microbiology 
    ['Urine Routine Examination (R/E)', 'URE01', 'Urinalysis', 'Urine', 'Sterile Container', 'Mid-stream urine', 1, 2, 30.00, 0, 1],
    ['Urine Culture and Sensitivity', 'UCS02', 'Microbiology', 'Urine', 'Sterile Container', 'Mid-stream clean catch', 48, 72, 100.00, 0, 1],
    ['Stool Routine Examination', 'SRE03', 'Microbiology', 'Stool', 'Stool Container', 'Fresh sample required', 2, 4, 35.00, 0, 1],
    ['Blood Culture', 'BC04', 'Microbiology', 'Blood', 'Blood Culture Bottle', 'Collect before antibiotics', 72, 120, 150.00, 0, 1],
    ['High Vaginal Swab (HVS) M/C/S', 'HVS05', 'Microbiology', 'Swab', 'Sterile Swab with Transport Media', 'None', 48, 72, 120.00, 0, 1],

    // Others
    ['Sputum for AFB', 'AFB01', 'Microbiology', 'Sputum', 'Sputum Container', 'Early morning deep cough sample', 24, 48, 60.00, 0, 1],
    ['COVID-19 Rapid Antigen', 'COV01', 'Immunology', 'Swab', 'Nasopharyngeal Swab', 'None', 1, 2, 100.00, 0, 1]
];

$stmtCat = $conn->prepare("INSERT INTO lab_test_catalog (test_name, test_code, category, sample_type_required, container_type, collection_instructions, processing_time_hours, normal_turnaround_time_hours, price, requires_fasting, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$cat_map = []; // Map test_code -> id for reference ranges mapping
foreach ($catalog_seeds as $c) {
    $stmtCat->bind_param("ssssssiidii", $c[0], $c[1], $c[2], $c[3], $c[4], $c[5], $c[6], $c[7], $c[8], $c[9], $c[10]);
    $stmtCat->execute();
    $cat_map[$c[1]] = $conn->insert_id;
}
$stmtCat->close();

// 2. REFERENCE RANGES SEED DATA (A selection for standard biochem/hema)
$ref_seeds = [
    // format: [test_code, parameter_name, unit, min_val, max_val, gender, age_min, age_max]
    ['HB02', 'Hemoglobin (Male)', 'g/dL', 13.5, 17.5, 'Male', 18, 120],
    ['HB02', 'Hemoglobin (Female)', 'g/dL', 12.0, 15.5, 'Female', 18, 120],
    ['FBS01', 'Fasting Blood Sugar', 'mg/dL', 70.0, 100.0, 'All', 0, 120],
    ['RBS02', 'Random Blood Sugar', 'mg/dL', 70.0, 140.0, 'All', 0, 120],
    ['LFT03', 'ALT (SGPT)', 'U/L', 7.0, 56.0, 'All', 0, 120],
    ['LFT03', 'AST (SGOT)', 'U/L', 8.0, 48.0, 'All', 0, 120],
    ['LFT03', 'Total Bilirubin', 'mg/dL', 0.1, 1.2, 'All', 0, 120],
    ['KFT04', 'Serum Creatinine (Male)', 'mg/dL', 0.74, 1.35, 'Male', 18, 120],
    ['KFT04', 'Serum Creatinine (Female)', 'mg/dL', 0.59, 1.04, 'Female', 18, 120],
    ['KFT04', 'Blood Urea Nitrogen', 'mg/dL', 7.0, 20.0, 'All', 0, 120],
    ['LIP05', 'Total Cholesterol', 'mg/dL', 125.0, 200.0, 'All', 18, 120],
    ['LIP05', 'LDL Cholesterol', 'mg/dL', 0.0, 100.0, 'All', 18, 120],
    ['LIP05', 'HDL Cholesterol (Male)', 'mg/dL', 40.0, 60.0, 'Male', 18, 120],
    ['LIP05', 'HDL Cholesterol (Female)', 'mg/dL', 50.0, 60.0, 'Female', 18, 120],
    ['URA06', 'Uric Acid (Male)', 'mg/dL', 4.0, 8.5, 'Male', 18, 120],
    ['URA06', 'Uric Acid (Female)', 'mg/dL', 2.7, 7.3, 'Female', 18, 120]
];

$stmtRef = $conn->prepare("INSERT INTO lab_reference_ranges (test_catalog_id, parameter_name, unit, normal_min, normal_max, gender_applicability, age_min_years, age_max_years) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($ref_seeds as $r) {
    if (isset($cat_map[$r[0]])) {
        $id = $cat_map[$r[0]];
        $stmtRef->bind_param("issddssi", $id, $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7]);
        $stmtRef->execute();
    }
}
$stmtRef->close();

// 3. EQUIPMENT SEED DATA
$equip_seeds = [
    ['C001', 'Sysmex XN-1000', 'Automated Hematology Analyzer', 'Sysmex', 'XN-1000', 'Maintenance Rm A', '2023-01-15', 'Active', '2026-06-25'],
    ['C002', 'Mindray BS-240', 'Clinical Chemistry Analyzer', 'Mindray', 'BS-240', 'Main Lab Bench', '2022-11-10', 'Active', '2026-05-10'],
    ['C003', 'Eppendorf 5424', 'Microcentrifuge', 'Eppendorf', '5424', 'Prep Bench', '2024-02-01', 'Active', '2026-10-01'],
    ['C004', 'Thermo Scientific TSX', 'Laboratory Refrigerator', 'Thermo Fisher', 'TSX2305', 'Storage Area', '2021-08-14', 'Active', '2026-12-15']
];

$stmtEq = $conn->prepare("INSERT INTO lab_equipment (equipment_code, name, type, manufacturer, model, location, date_acquired, status, next_calibration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($equip_seeds as $e) {
    $stmtEq->bind_param("sssssssss", $e[0], $e[1], $e[2], $e[3], $e[4], $e[5], $e[6], $e[7], $e[8]);
    $stmtEq->execute();
}
$stmtEq->close();

// 4. REAGENT INVENTORY SEED DATA
$reag_seeds = [
    ['Cellpack DCL', 'R001', 'Sysmex Corp', '124112', '2026-12-30', 50, 10, 'Liters', 'Maintained', 'Storage Room A'],
    ['Stromatolyser-WH', 'R002', 'Sysmex Corp', '112993', '2026-10-15', 20, 5, 'Liters', 'Maintained', 'Storage Room A'],
    ['Glucose Hexokinase Reagent', 'R003', 'Mindray', 'MGL11', '2025-11-20', 15, 3, 'Kits', 'Maintained', 'Fridge 1'],
    ['ALT/GPT Reagent', 'R004', 'Mindray', 'MALT2', '2025-08-30', 10, 2, 'Kits', 'Low Stock', 'Fridge 1'],
    ['AST/GOT Reagent', 'R005', 'Mindray', 'MAST3', '2025-08-30', 8, 2, 'Kits', 'Maintained', 'Fridge 1'],
    ['Total Cholesterol Reagent', 'R006', 'Mindray', 'MCHOL', '2025-12-10', 5, 2, 'Kits', 'Maintained', 'Fridge 2'],
    ['Urine Test Strips (10 Parameter)', 'R007', 'Roche', 'RTS10', '2026-05-01', 100, 20, 'Bottles', 'Maintained', 'Bench B'],
    ['Widal Antigen Kit', 'R008', 'Spinreact', 'SP102', '2025-09-15', 5, 1, 'Kits', 'Low Stock', 'Fridge 2'],
    ['Malaria Rapid Test Kits', 'R009', 'SD Bioline', 'SD001', '2026-01-20', 200, 50, 'Boxes', 'Maintained', 'Bench A']
];

$stmtReag = $conn->prepare("INSERT INTO reagent_inventory (item_name, item_code, manufacturer, lot_number, expiration_date, quantity_in_stock, reorder_level, unit, status, storage_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($reag_seeds as $rn) {
    $stmtReag->bind_param("sssssiisss", $rn[0], $rn[1], $rn[2], $rn[3], $rn[4], $rn[5], $rn[6], $rn[7], $rn[8], $rn[9]);
    $stmtReag->execute();
}
$stmtReag->close();

?>
