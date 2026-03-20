<?php
// ============================================================
// PROCESS IV & FLUID MANAGEMENT (AJAX Endpoint)
// ============================================================
require_once '../dashboards/nurse_security.php';
initSecureSession();
$nurse_id = enforceNurseRole();
require_once '../db_conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}

verifyCsrfToken($_POST['_csrf'] ?? '');
$action = sanitize($_POST['action'] ?? '');

$nurse_pk = dbVal($conn, "SELECT id FROM nurses WHERE user_id=$nurse_id LIMIT 1", "i", []);

if ($action === 'start_iv') {
    $patient_id      = validateInt($_POST['patient_id'] ?? 0);
    $fluid_type      = sanitize($_POST['fluid_type'] ?? '');
    $site            = sanitize($_POST['site'] ?? '');
    $volume_ordered  = validateFloat($_POST['volume_ordered'] ?? 0);
    $infusion_rate   = validateFloat($_POST['infusion_rate'] ?? 0);
    $status          = sanitize($_POST['initial_status'] ?? 'Ordered');

    if (!$patient_id || empty($fluid_type) || $volume_ordered <= 0 || $infusion_rate <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing or invalid IV infusion parameters.']);
        exit;
    }

    $record_id = 'IVF-' . strtoupper(uniqid());

    $stmt = mysqli_prepare($conn, "
        INSERT INTO iv_fluid_records (record_id, patient_id, nurse_id, fluid_type, volume_ordered, volume_infused, infusion_rate, start_time, site, status) 
        VALUES (?, ?, ?, ?, ?, 0, ?, NOW(), ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "siisdddss", $record_id, $patient_id, $nurse_pk, $fluid_type, $volume_ordered, $infusion_rate, $site, $status);

    if (mysqli_stmt_execute($stmt)) {
        secureLogNurse($conn, $nurse_pk, "Started IV $fluid_type ($volume_ordered ml) for Patient PK $patient_id", "fluids");
        echo json_encode(['success' => true, 'message' => 'IV order created and documented successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error recording IV order: ' . mysqli_error($conn)]);
    }
    exit;

} elseif ($action === 'update_iv_status') {
    $iv_id  = validateInt($_POST['iv_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');

    if (!$iv_id || !in_array($status, ['Running', 'Paused', 'Completed', 'Stopped'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status update.']);
        exit;
    }

    $q = "UPDATE iv_fluid_records SET status = ?";
    if (in_array($status, ['Completed', 'Stopped'])) {
        $q .= ", end_time = NOW()";
    }
    $q .= " WHERE id = ?";

    // Optional: if it's completed, we should probably update volume_infused = volume_ordered to make it look clean.
    // For now, keep it simple.

    if (dbExecute($conn, $q, "si", [$status, $iv_id])) {
        secureLogNurse($conn, $nurse_pk, "Updated IV Record $iv_id status to $status", "fluids");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update IV status.']);
    }
    exit;

} elseif ($action === 'update_io') {
    $patient_id = validateInt($_POST['patient_id'] ?? 0);
    
    // Read individual additions
    $add_oral   = validateFloat($_POST['in_oral'] ?? 0);
    $add_iv     = validateFloat($_POST['in_iv'] ?? 0);
    $add_ng     = validateFloat($_POST['in_ng'] ?? 0);
    
    $add_urine  = validateFloat($_POST['out_urine'] ?? 0);
    $add_drain  = validateFloat($_POST['out_drain'] ?? 0);
    $add_emesis = validateFloat($_POST['out_emesis'] ?? 0);

    // Sum new totals
    $new_in  = $add_oral + $add_iv + $add_ng;
    $new_out = $add_urine + $add_drain + $add_emesis;

    if (!$patient_id) {
         echo json_encode(['success' => false, 'message' => 'Patient selection is required.']);
         exit;
    }
    if ($new_in == 0 && $new_out == 0) {
         echo json_encode(['success' => false, 'message' => 'No volumes entered to record.']);
         exit;
    }

    $today = date('Y-m-d');

    // Does a record already exist for today?
    $existing = dbRow($conn, "SELECT id, intake_sources, output_sources, total_intake, total_output FROM fluid_balance WHERE patient_id=? AND record_date=?", "is", [$patient_id, $today]);

    if ($existing) {
        $curr_in_src  = json_decode($existing['intake_sources'] ?? '{"oral":0, "iv":0, "ng_tube":0}', true);
        $curr_out_src = json_decode($existing['output_sources'] ?? '{"urine":0, "drain":0, "emesis":0}', true);

        // Add
        $curr_in_src['oral']    = ($curr_in_src['oral'] ?? 0) + $add_oral;
        $curr_in_src['iv']      = ($curr_in_src['iv'] ?? 0) + $add_iv;
        $curr_in_src['ng_tube'] = ($curr_in_src['ng_tube'] ?? 0) + $add_ng;

        $curr_out_src['urine']  = ($curr_out_src['urine'] ?? 0) + $add_urine;
        $curr_out_src['drain']  = ($curr_out_src['drain'] ?? 0) + $add_drain;
        $curr_out_src['emesis'] = ($curr_out_src['emesis'] ?? 0) + $add_emesis;

        $total_in  = $existing['total_intake'] + $new_in;
        $total_out = $existing['total_output'] + $new_out;
        $net = $total_in - $total_out;

        $stmt = mysqli_prepare($conn, "
            UPDATE fluid_balance 
            SET total_intake=?, total_output=?, net_balance=?, intake_sources=?, output_sources=?, nurse_id=? 
            WHERE id=?
        ");
        $json_in = json_encode($curr_in_src);
        $json_out = json_encode($curr_out_src);
        mysqli_stmt_bind_param($stmt, "dddssii", $total_in, $total_out, $net, $json_in, $json_out, $nurse_pk, $existing['id']);
        $res = mysqli_stmt_execute($stmt);

    } else {
        // Create new record for today
        $balance_id = 'FIO-' . strtoupper(uniqid());

        $curr_in_src = ['oral' => $add_oral, 'iv' => $add_iv, 'ng_tube' => $add_ng];
        $curr_out_src = ['urine' => $add_urine, 'drain' => $add_drain, 'emesis' => $add_emesis];
        $net = $new_in - $new_out;

        $stmt = mysqli_prepare($conn, "
            INSERT INTO fluid_balance (balance_id, patient_id, nurse_id, record_date, total_intake, total_output, net_balance, intake_sources, output_sources) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $json_in = json_encode($curr_in_src);
        $json_out = json_encode($curr_out_src);
        mysqli_stmt_bind_param($stmt, "siisdddss", $balance_id, $patient_id, $nurse_pk, $today, $new_in, $new_out, $net, $json_in, $json_out);
        $res = mysqli_stmt_execute($stmt);
    }

    if ($res) {
        secureLogNurse($conn, $nurse_pk, "Updated Intake & Output Chart for Patient PK $patient_id (Added In: $new_in, Out: $new_out)", "fluids");
        
        // Notify Doctor if Net Balance is Critical (>2000 or <-2000 ml)
        if (abs($net) >= 2000) {
            $pat_q = mysqli_query($conn, "
                SELECT u.name, ba.doctor_id 
                FROM patients p 
                JOIN users u ON p.user_id = u.id 
                LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status='Occupied'
                WHERE p.id = $patient_id LIMIT 1
            ");
            if ($pat_row = mysqli_fetch_assoc($pat_q)) {
                if ($pat_row['doctor_id']) {
                    $doc_user_id = dbVal($conn, "SELECT user_id FROM doctors WHERE id=?", "i", [$pat_row['doctor_id']]);
                    if ($doc_user_id) {
                        $msg = "Critical Fluid Imbalance: {$pat_row['name']} has a net balance of {$net}ml.";
                        dbExecute($conn, 
                            "INSERT INTO notifications (user_id, message, type, related_module, created_at) VALUES (?, ?, 'Fluid Alert', 'fluid_balance', NOW())",
                            "is", [$doc_user_id, $msg]
                        );
                    }
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'I&O Chart updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error updating I&O chart.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Action']);
