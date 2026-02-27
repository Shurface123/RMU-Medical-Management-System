<?php
session_start();
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = mysqli_real_escape_string($conn, trim($_POST['medicine_name'] ?? ''));
    $generic    = mysqli_real_escape_string($conn, trim($_POST['generic_name'] ?? ''));
    $category   = mysqli_real_escape_string($conn, trim($_POST['category'] ?? 'Other'));
    $stock      = (int)($_POST['stock_quantity'] ?? 0);
    $reorder    = (int)($_POST['reorder_level'] ?? 10);
    $price      = (float)($_POST['unit_price'] ?? 0);
    $expiry     = mysqli_real_escape_string($conn, $_POST['expiry_date'] ?? '');
    $rx         = ((int)($_POST['is_prescription_required'] ?? 0)) === 1 ? 1 : 0;
    $desc       = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));

    if ($name) {
        $last = mysqli_fetch_row(mysqli_query($conn, "SELECT MAX(id) FROM medicines"))[0] ?? 0;
        $med_id = 'MED-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
        $expiry_val = $expiry ? "'$expiry'" : 'NULL';
        $desc_val   = $desc   ? "'$desc'"   : 'NULL';
        $generic_val = $generic ? "'$generic'" : 'NULL';

        $sql = "INSERT INTO medicines (medicine_id, medicine_name, generic_name, category, stock_quantity, reorder_level, unit_price, expiry_date, is_prescription_required, description)
                VALUES ('$med_id','$name',$generic_val,'$category',$stock,$reorder,$price,$expiry_val,$rx,$desc_val)";
        if (mysqli_query($conn, $sql)) {
            header('Location: medicine.php?success=Medicine+added+successfully');
            exit();
        }
    }
    header('Location: add-medicine.php?error=Failed+to+add+medicine');
    exit();
}
?>