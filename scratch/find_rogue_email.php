<?php
require_once 'php/db_conn.php';

$email_to_check = 'lovelacejohnbaidoo@gmail.com';
$email_to_check_alt = 'www.lovelacejohnbaidoo@gmail.com';

echo "Searching for records related to: $email_to_check and $email_to_check_alt\n\n";

$tables = [
    'users' => 'email',
    'registration_sessions' => 'email',
    'email_verifications' => 'email'
];

foreach ($tables as $table => $column) {
    echo "--- Checking Table: $table ---\n";
    $query = "SELECT * FROM `$table` WHERE `$column` LIKE '%lovelacejohnbaidoo%'";
    $res = mysqli_query($conn, $query);
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            print_r($row);
        }
    } else {
        echo "No records found.\n";
    }
    echo "\n";
}
?>
