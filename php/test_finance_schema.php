<?php
require 'db_conn.php';
$tables = ['payments', 'finance', 'waivers', 'budgets', 'paystack', 'insurance', 'transactions', 'invoices'];
foreach (['%pay%', '%financ%', '%waiver%', '%budget%', '%insur%', '%transact%', '%invoice%'] as $like) {
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$like'");
    while($row = mysqli_fetch_row($res)) {
        echo "TABLE: " . $row[0] . "\n";
        $desc = mysqli_query($conn, "DESCRIBE " . $row[0]);
        while($d = mysqli_fetch_assoc($desc)){
            echo "  " . $d['Field'] . " - " . $d['Type'] . "\n";
        }
    }
}
