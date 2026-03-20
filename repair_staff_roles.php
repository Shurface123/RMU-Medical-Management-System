<?php
require 'php/db_conn.php';
echo "Repairing staff roles...\n";

// Update users with empty role (failed sub-role insertion) to 'staff'
$q = mysqli_query($conn, "UPDATE users SET user_role = 'staff' WHERE (user_role = '' OR user_role IS NULL) AND id IN (SELECT user_id FROM staff)");
$affected = mysqli_affected_rows($conn);
echo "✓ Fixed $affected users with empty roles in users table.\n";

// Ensure all staff records have a valid role in the staff table
// (They already do because role was inserted as is)

echo "Repair complete.\n";
?>
