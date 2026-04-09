<?php
/**
 * SEED FEES — RMU Medical Sickbay Finance
 * Run this to populate the fee schedule for testing.
 */
require_once __DIR__.'/../db_conn.php';

$fees = [
    ['Consultation - General', 'CONSULT-001', 'Consultation', 50.00, 20.00, 15.0, 1],
    ['Consultation - Specialist', 'CONSULT-002', 'Consultation', 150.00, 100.00, 15.0, 1],
    ['Malaria Test (RDT)', 'LAB-001', 'Laboratory', 25.00, 15.00, 0, 0],
    ['Full Blood Count (FBC)', 'LAB-002', 'Laboratory', 80.00, 60.00, 0, 0],
    ['Paracetamol 500mg (10 tabs)', 'PHARM-001', 'Pharmacy', 10.00, 5.00, 0, 0],
    ['Amoxicillin 500mg', 'PHARM-002', 'Pharmacy', 35.00, 25.00, 0, 0],
    ['Medical Certificate (Standard)', 'MISC-001', 'Administrative', 20.00, 10.00, 15.0, 1],
    ['Ambulance Service (Local)', 'MISC-002', 'Administrative', 100.00, 50.00, 0, 0]
];

echo "<h2>Seeding Fee Schedule...</h2>";

foreach($fees as [$name, $code, $cat_name, $base, $student, $tax, $is_taxable]){
    // Find category ID
    $cat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT category_id FROM revenue_categories WHERE category_name='$cat_name' LIMIT 1"));
    $cat_id = $cat ? $cat['category_id'] : 'NULL';
    
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fee_id FROM fee_schedule WHERE service_code='$code' LIMIT 1"));
    if($check){
        echo "Updating: $name ($code)...<br>";
        mysqli_query($conn, "UPDATE fee_schedule SET service_name='$name', category_id=$cat_id, base_amount=$base, student_amount=$student, tax_rate_pct=$tax, is_taxable=$is_taxable WHERE service_code='$code'");
    } else {
        echo "Inserting: $name ($code)...<br>";
        mysqli_query($conn, "INSERT INTO fee_schedule(service_name, service_code, category_id, base_amount, student_amount, tax_rate_pct, is_taxable, effective_from, is_active) 
                             VALUES('$name', '$code', $cat_id, $base, $student, $tax, $is_taxable, CURDATE(), 1)");
    }
}

echo "<h3>Seeding Complete!</h3>";
?>
