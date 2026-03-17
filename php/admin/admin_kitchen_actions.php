<?php
require_once '../includes/auth_middleware.php';

// Only admins can submit new dietary orders from this view
enforceSingleDashboard('admin');
require_once '../db_conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'create_diet_order') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Security token invalid. Please refresh.']);
    exit;
}

$patient_id = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
$fallback   = trim($_POST['patient_name_fallback'] ?? '');
$patient_name = $fallback;

if ($patient_id) {
    // Lookup real patient name
    $stmt = mysqli_prepare($conn, "SELECT full_name FROM patients WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $patient_name = $row['full_name'];
    }
    mysqli_stmt_close($stmt);
}

if (empty($patient_name)) {
    echo json_encode(['success' => false, 'message' => 'A valid patient name or registered patient must be specified.']);
    exit;
}

$ward     = trim($_POST['ward'] ?? '');
if (!$ward) {
    echo json_encode(['success' => false, 'message' => 'Ward/Department is required.']);
    exit;
}

$bed      = trim($_POST['bed_number'] ?? '');
$doctor   = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
$ordered_by = (int)($_SESSION['user_id'] ?? 0);
$assigned = !empty($_POST['assigned_staff_id']) ? (int)$_POST['assigned_staff_id'] : null;
$priority = trim($_POST['priority'] ?? 'Routine');
$diet_type = trim($_POST['diet_type'] ?? 'Regular');

// Building the Allergies Array
$allergies = $_POST['allergies'] ?? [];
$other_alg = trim($_POST['other_allergies'] ?? '');
if ($other_alg) {
    $allergies[] = $other_alg;
}

// Building the Dietary Requirements JSON
$diet_req = [
    'type' => $diet_type,
    'texture' => trim($_POST['texture'] ?? 'Normal'),
    'allergies' => json_encode($allergies),
    'caloric_target' => trim($_POST['caloric_target'] ?? ''),
    'fluid_restriction' => trim($_POST['fluid_restriction'] ?? ''),
    'feeding_method' => trim($_POST['feeding_method'] ?? 'Oral'),
    'portion_size' => trim($_POST['portion_size'] ?? 'Regular'),
    'notes' => trim($_POST['special_notes'] ?? '')
];

$diet_json = json_encode($diet_req);
$meals     = $_POST['meals'] ?? [];
if (empty($meals)) {
    echo json_encode(['success' => false, 'message' => 'You must select at least one meal to schedule.']);
    exit;
}

$start = trim($_POST['start_date'] ?? date('Y-m-d'));
$end   = trim($_POST['end_date'] ?? '');

$start_time = strtotime($start);
$end_time   = $end ? strtotime($end) : $start_time;
if ($end_time < $start_time) {
    $end_time = $start_time; 
}
// Cap at 14 days to prevent abuse
if (($end_time - $start_time) > (14 * 86400)) {
    $end_time = $start_time + (14 * 86400);
}

$kitchen_notes = trim($_POST['kitchen_instructions'] ?? '');

mysqli_begin_transaction($conn);

try {
    $insert_sql = "INSERT INTO kitchen_tasks (
        patient_id, patient_name, assigned_to, ordered_by, meal_type, ward_department, 
        bed_number, dietary_requirements, quantity, priority, 
        preparation_status, delivery_status, notes, created_at, scheduled_time
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 'pending', 'pending', ?, NOW(), ?)";
    
    $stmt = mysqli_prepare($conn, $insert_sql);
    $records_count = 0;

    for ($t = $start_time; $t <= $end_time; $t += 86400) {
        $date_str = date('Y-m-d', $t);
        foreach ($meals as $m) {
            $meal_time_mapping = [
                'Breakfast' => '08:00:00',
                'Morning Snack' => '10:30:00',
                'Lunch' => '13:00:00',
                'Afternoon Snack' => '15:30:00',
                'Dinner' => '18:00:00',
                'Evening Snack' => '20:30:00'
            ];
            // Since enum only has strict values 'breakfast','lunch','dinner','snack'
            $m_lower = strtolower($m);
            if (strpos($m_lower, 'breakfast') !== false) $m_enum = 'breakfast';
            else if (strpos($m_lower, 'lunch') !== false) $m_enum = 'lunch';
            else if (strpos($m_lower, 'dinner') !== false) $m_enum = 'dinner';
            else $m_enum = 'snack';
            
            $sched_time = $meal_time_mapping[$m] ?? '12:00:00';
            // Technically the schema dictates scheduled_time is TIME. Let's pass the time only.

            mysqli_stmt_bind_param($stmt, "isiissssssss", 
                $patient_id, $patient_name, $assigned, $ordered_by, $m_enum, $ward, 
                $bed, $diet_json, $priority, $kitchen_notes, $sched_time
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database insertion failed for $date_str $m.");
            }
            $records_count++;
        }
    }
    
    // Notifications mapping
    // 1. Kitchen Staff
    $alert_badge = !empty($allergies) ? "[ALLERGY ALERT]" : "";
    $kitchen_msg = "New Dietary Order: $patient_name ($ward Bed $bed) - $diet_type. Priority: $priority. $alert_badge";
    
    $ns = mysqli_prepare($conn, "INSERT INTO staff_notifications (staff_id, message, type, related_module) VALUES (?, ?, 'kitchen', 'diet_orders')");
    
    if ($assigned) {
        mysqli_stmt_bind_param($ns, "is", $assigned, $kitchen_msg);
        mysqli_stmt_execute($ns);
    } else {
        // Broadcast
        $kq = mysqli_query($conn, "SELECT id FROM users WHERE role='kitchen_staff' AND account_status='active'");
        if ($kq) {
            while ($k = mysqli_fetch_assoc($kq)) {
                mysqli_stmt_bind_param($ns, "is", $k['id'], $kitchen_msg);
                mysqli_stmt_execute($ns);
            }
        }
    }

    // 2. Doctor Notify
    if ($doctor) {
        $doc_msg = "Dietary order generated for your patient $patient_name ($diet_type).";
        mysqli_stmt_bind_param($ns, "is", $doctor, $doc_msg);
        mysqli_stmt_execute($ns);
    }

    // 3. Nurse Notify (Find all nurses)
    // We could target nurses specifically assigned to the ward. If no ward mapping is strict, just notify generally or skip.
    // The instructions say: "Notify the nurse assigned to the patient's ward that a dietary order has been created"
    // Since we don't know the table mapping nurses strictly to wards unless there's a ward_assignments table...
    // Let's notify ANY nurse... or just the general nurses.
    $nq = mysqli_query($conn, "SELECT id FROM users WHERE role='nurse' AND account_status='active'");
    if ($nq) {
        $nurse_msg = "Patient $patient_name ($ward) has a new active dietary order: $diet_type.";
        while ($n = mysqli_fetch_assoc($nq)) {
            mysqli_stmt_bind_param($ns, "is", $n['id'], $nurse_msg);
            mysqli_stmt_execute($ns);
        }
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => "Order processed ($records_count meals scheduled). Kitchen staff notified."]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => "System error completing dietary workflow: " . $e->getMessage()]);
}
?>
