<?php
require_once 'php/db_conn.php';

$emails = ['www.lovelacejohnbaidoo@gmail.com', 'lovelacejohnbaidoo@gmail.com'];

foreach ($emails as $email) {
    echo "Checking: $email\n";
    $stmt = mysqli_prepare($conn, "SELECT id, user_role, name, email FROM users WHERE email=? OR user_name=?");
    $uname = 'Shurface'; // From screenshot
    mysqli_stmt_bind_param($stmt, 'ss', $email, $uname);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            print_r($row);
        }
    } else {
        echo "No records found for $email or username 'Shurface'.\n";
    }
    echo "\n";
}
?>
