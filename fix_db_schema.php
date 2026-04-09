<?php
$conn = mysqli_connect('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if (!$conn) die('Connection failed');

echo "Checking tables...\n";

// Ensure finance_audit_trail is correct
$q = mysqli_query($conn, "SHOW COLUMNS FROM finance_audit_trail");
$cols = [];
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) $cols[] = $r['Field'];
}

if (!in_array('actor_user_id', $cols)) {
    echo "Fixing finance_audit_trail: adding actor_user_id...\n";
    // Check if maybe it's named 'user_id' or 'staff_id'
    if (in_array('user_id', $cols)) {
        mysqli_query($conn, "ALTER TABLE finance_audit_trail CHANGE user_id actor_user_id INT");
    } else {
        mysqli_query($conn, "ALTER TABLE finance_audit_trail ADD COLUMN actor_user_id INT AFTER id");
    }
}

// Ensure finance_staff is correct
$q = mysqli_query($conn, "SHOW COLUMNS FROM finance_staff");
$cols = [];
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) $cols[] = $r['Field'];
}
if (!in_array('user_id', $cols)) {
    echo "Fixing finance_staff: adding user_id...\n";
    mysqli_query($conn, "ALTER TABLE finance_staff ADD COLUMN user_id INT AFTER finance_staff_id");
    mysqli_query($conn, "CREATE INDEX idx_staff_user ON finance_staff(user_id)");
}

mysqli_close($conn);
echo "Finalizing integrity check...\n";
?>
