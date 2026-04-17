<?php
require_once 'php/db_conn.php';

echo "Testing doc_dashboard queries...\n";

try {
    function qval($conn,$sql){$r=mysqli_query($conn,$sql);return $r?(mysqli_fetch_row($r)[0]??0):0;}
    $doc_pk = 1;
    $today = date('Y-m-d');
    $user_id = 1;
    
    // Line 29-36
    $stats['today_appts']    = qval($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND appointment_date='$today'");
    $stats['total_patients'] = qval($conn,"SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id=$doc_pk");
    $stats['active_rx']      = qval($conn,"SELECT COUNT(*) FROM prescriptions WHERE doctor_id=$doc_pk AND status='Pending'");
    $stats['avail_beds']     = qval($conn,"SELECT COUNT(*) FROM beds WHERE status='Available'");
    $stats['low_stock']      = qval($conn,"SELECT COUNT(*) FROM medicines WHERE stock_quantity<=reorder_level");
    $stats['unread_notifs']  = qval($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND is_read=0");
    $stats['pending_appts']  = qval($conn,"SELECT COUNT(*) FROM appointments WHERE doctor_id=$doc_pk AND status='Pending'");
    
    // Line 84-86
    $medicines=[];
    $q=mysqli_query($conn,"SELECT * FROM medicine_inventory ORDER BY stock_status ASC, medicine_name ASC LIMIT 150");
    
    // Line 97-99
    $beds=[];
    $q=mysqli_query($conn,"SELECT * FROM bed_management ORDER BY ward, bed_number");

    echo "All queries successful.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
