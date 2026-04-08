<?php
require 'c:/wamp64/www/RMU-Medical-Management-System/php/db_conn.php';
$r = mysqli_query($conn, "SELECT * FROM system_email_config WHERE is_active=1");
if ($r && mysqli_num_rows($r) > 0) {
    while($row = mysqli_fetch_assoc($r)) {
        print_r($row);
        $pwResult = mysqli_query($conn, "SELECT CAST(AES_DECRYPT(smtp_password, SHA2('RMU_SICKBAY_2025_SECRET',256)) AS CHAR) AS pw FROM system_email_config WHERE id={$row['id']}");
        $pwRow = mysqli_fetch_assoc($pwResult);
        echo "Decrypted PW: " . ($pwRow['pw'] ?? 'NULL') . "\n";
    }
} else {
    echo "No active email config in DB.\n";
}
