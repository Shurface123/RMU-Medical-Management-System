<?php
session_start();
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_name   = mysqli_real_escape_string($conn, trim($_POST['test_name'] ?? ''));
    $category    = mysqli_real_escape_string($conn, trim($_POST['category'] ?? 'Other'));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $cost        = (float)($_POST['cost'] ?? 0);
    $duration    = (int)($_POST['duration_minutes'] ?? 30);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if ($test_name) {
        $last_t = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tests"))[0] ?? 0;
        $test_id = 'TST-' . str_pad($last_t + 1, 4, '0', STR_PAD_LEFT);
        $desc_val = $description ? "'$description'" : 'NULL';
        $sql = "INSERT INTO tests (test_id, test_name, category, description, cost, duration_minutes, is_active)
                VALUES ('$test_id','$test_name','$category',$desc_val,$cost,$duration,$is_active)";
        if (mysqli_query($conn, $sql)) {
            header('Location: test.php?success=Test+added+successfully');
            exit();
        }
    }
    header('Location: add-test.php?error=Failed+to+add+test');
    exit();
}
?>