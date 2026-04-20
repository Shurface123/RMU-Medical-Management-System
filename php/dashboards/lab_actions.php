<?php
// ============================================
// LAB TECHNICIAN AJAX ACTIONS HANDLER
// ============================================
require_once 'lab_security.php';
initSecureSession();
$user_id = enforceLabTechRole(); // Ensures only Lab Techs and Admins can access

require_once '../db_conn.php';
date_default_timezone_set('Africa/Accra');

header('Content-Type: application/json');

// All requests must be POST and include action parameter
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing action']);
    exit();
}

// Strict CSRF verification
$headers = apache_request_headers();
$received_token = $_POST['csrf_token'] ?? $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? $headers['x-csrf-token'] ?? '';
verifyCsrfToken($received_token); // Automatically redirects or stops script if token invalid

$action = $_POST['action'];
$response = ['success' => false, 'message' => 'Unknown action'];

// Helper to escape output
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ------------------------------------------------------------------
// 1. DASHBOARD OVERVIEW MODULE ACTIONS
// ------------------------------------------------------------------
if ($action === 'fetch_dashboard_stats') {
    try {
        // Fetch real-time counts for the summary cards
        $stats = [
            'pending_orders' => 0,
            'samples_awaiting' => 0,
            'processing_tests' => 0,
            'results_awaiting_val' => 0,
            'critical_flagged' => 0,
            'equip_calibration' => 0,
            'low_reagent' => 0
        ];

        // Pending Test Orders
        $q1 = mysqli_query($conn, "SELECT COUNT(*) FROM lab_test_orders WHERE order_status = 'Pending'");
        if($q1) $stats['pending_orders'] = (int)mysqli_fetch_row($q1)[0];

        // Samples Awaiting Collection (Assuming status 'Collected' means waiting for lab to receive)
        $q1b = mysqli_query($conn, "SELECT COUNT(*) FROM lab_samples WHERE status = 'Collected'");
        if($q1b) $stats['samples_awaiting'] = (int)mysqli_fetch_row($q1b)[0];

        // Tests Processing
        $q2 = mysqli_query($conn, "SELECT COUNT(*) FROM lab_test_orders WHERE order_status = 'Processing'");
        if($q2) $stats['processing_tests'] = (int)mysqli_fetch_row($q2)[0];

        // Results Awaiting Validation
        $q3 = mysqli_query($conn, "SELECT COUNT(*) FROM lab_results WHERE result_status = 'Pending Validation'");
        if($q3) $stats['results_awaiting_val'] = (int)mysqli_fetch_row($q3)[0];

        // Critical Results Today
        $today = date('Y-m-d');
        $q4 = mysqli_query($conn, "SELECT COUNT(*) FROM lab_results WHERE result_interpretation = 'Critical' AND DATE(created_at) = '$today'");
        if($q4) $stats['critical_flagged'] = (int)mysqli_fetch_row($q4)[0];

        // Equipment Calibration Due
        $q5 = mysqli_query($conn, "SELECT COUNT(*) FROM lab_equipment WHERE status = 'Calibration Due' OR (next_calibration_date IS NOT NULL AND next_calibration_date <= DATE_ADD('$today', INTERVAL 7 DAY))");
        if($q5) $stats['equip_calibration'] = (int)mysqli_fetch_row($q5)[0];

        // Low Reagent Alerts
        $q6 = mysqli_query($conn, "SELECT COUNT(*) FROM reagent_inventory WHERE status IN ('Low Stock', 'Out of Stock') OR quantity_in_stock <= reorder_level");
        if($q6) $stats['low_reagent'] = (int)mysqli_fetch_row($q6)[0];

        // --- Phase 8: TAT Escalation (Orders that have exceeded their normal turnaround time) ---
        $esc_q = mysqli_query($conn, "
            SELECT o.id as order_id, c.test_name, p.full_name as patient_name,
                   TIMESTAMPDIFF(HOUR, o.created_at, NOW()) as hours_elapsed,
                   c.normal_turnaround_time_hours
            FROM lab_test_orders o
            JOIN lab_test_catalog c ON o.test_catalog_id = c.id
            JOIN patients p ON o.patient_id = p.id
            WHERE o.order_status IN ('Pending', 'Processing', 'Accepted')
              AND TIMESTAMPDIFF(HOUR, o.created_at, NOW()) > c.normal_turnaround_time_hours
            ORDER BY hours_elapsed DESC
            LIMIT 5
        ");

        $stats['escalated'] = [];
        if ($esc_q) {
            while ($ev = mysqli_fetch_assoc($esc_q)) {
                $stats['escalated'][] = [
                    'order_id' => (int)$ev['order_id'],
                    'test_name' => $ev['test_name'],
                    'patient_name' => $ev['patient_name'],
                    'hours_overdue' => (int)($ev['hours_elapsed'] - $ev['normal_turnaround_time_hours'])
                ];
            }
        }

        $response = ['success' => true, 'stats' => $stats];

    } catch (Exception $e) {
        $response['message'] = 'Error fetching stats: ' . $e->getMessage();
    }
}

// ------------------------------------------------------------------
// 2. ORDER MANAGEMENT ACTIONS
// ------------------------------------------------------------------
if ($action === 'update_order_status') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if($order_id > 0 && in_array($status, ['Accepted', 'Rejected', 'Processing', 'Sample Collected', 'Completed', 'Cancelled'])) {
        try {
            mysqli_begin_transaction($conn);
            
            // Get original order data for audit trail using Prepared Statement
            $stmt = $conn->prepare("SELECT order_status, doctor_id, patient_id FROM lab_test_orders WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $orig_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $old_val = json_encode(['status' => $orig_data['order_status']]);
            
            if ($status === 'Rejected') {
                $reason = $_POST['rejection_reason'] ?? 'No reason provided';
                $stmt = $conn->prepare("UPDATE lab_test_orders SET order_status = ?, rejection_reason = ? WHERE id = ?");
                $stmt->bind_param("ssi", $status, $reason, $order_id);
            } else if ($status === 'Accepted') {
                $stmt = $conn->prepare("UPDATE lab_test_orders SET order_status = ?, technician_id_assigned = ? WHERE id = ?");
                $stmt->bind_param("sii", $status, $user_id, $order_id);
            } else {
                $stmt = $conn->prepare("UPDATE lab_test_orders SET order_status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $order_id);
            }
            
            if ($stmt->execute()) {
                $stmt->close();
                // Log Audit Trail
                $new_val = json_encode(['status' => $status]);
                $ip = $_SERVER['REMOTE_ADDR'];
                $ua = $_SERVER['HTTP_USER_AGENT'];
                
                $stmt = $conn->prepare("INSERT INTO lab_audit_trail (technician_id, action_type, module_affected, record_id, old_value, new_value, ip_address, device_info)
                                     VALUES (?, 'Update Order Status', 'Test Order Management', ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissss", $user_id, $order_id, $old_val, $new_val, $ip, $ua);
                $stmt->execute();
                $stmt->close();

                // Notify Doctor
                $doc_pk = $orig_data['doctor_id'];
                $stmt = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
                $stmt->bind_param("i", $doc_pk);
                $stmt->execute();
                $d_res = $stmt->get_result();
                
                if ($d_res->num_rows > 0) {
                    $d_user_id = $d_res->fetch_assoc()['user_id'];
                    $msg = "Lab Order #ORD-".str_pad($order_id, 5, '0', STR_PAD_LEFT)." has been $status.";
                    $title = "Lab Order $status";
                    if ($status === 'Rejected') $msg .= " Reason: $reason";
                    
                    $ntf = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, related_id, created_at)
                                         VALUES (?, 'doctor', 'lab_update', ?, ?, 0, 'Orders', ?, NOW())");
                    $ntf->bind_param("issi", $d_user_id, $title, $msg, $order_id);
                    $ntf->execute();
                    $ntf->close();
                }
                $stmt->close();

                mysqli_commit($conn);
                $response = ['success' => true, 'message' => "Order updated to $status"];
            } else {
                throw new Exception("Database update failed.");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid order ID or status.';
    }
}

// ------------------------------------------------------------------
// 3. SAMPLE TRACKING ACTIONS
// ------------------------------------------------------------------
if ($action === 'update_sample_status') {
    $sample_id = (int)($_POST['sample_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $location = $_POST['location'] ?? '';

    if($sample_id > 0 && in_array($status, ['Received', 'Processing', 'Stored', 'Disposed', 'Rejected'])) {
        try {
            mysqli_begin_transaction($conn);
            
            // Get original sample format using prepared statement
            $stmt = $conn->prepare("SELECT status, condition_on_receipt, order_id FROM lab_samples WHERE id = ?");
            $stmt->bind_param("i", $sample_id);
            $stmt->execute();
            $orig_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $order_id = $orig_data['order_id'];
            $old_val = json_encode(['status' => $orig_data['status']]);
            
            $query = "UPDATE lab_samples SET status = ?";
            $types = "s";
            $params = [$status];
            
            if ($condition !== '') {
                $query .= ", condition_on_receipt = ?";
                $types .= "s";
                $params[] = $condition;
            }
            if ($location !== '') {
                $query .= ", storage_location = ?";
                $types .= "s";
                $params[] = $location;
            }
            $query .= " WHERE id = ?";
            $types .= "i";
            $params[] = $sample_id;
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $stmt->close();
                
                if ($status === 'Received') {
                    $stmt = $conn->prepare("UPDATE lab_test_orders SET order_status = 'Sample Collected' WHERE id = ? AND order_status != 'Processing'");
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    $stmt->close();
                } else if ($status === 'Processing') {
                    $stmt = $conn->prepare("UPDATE lab_test_orders SET order_status = 'Processing' WHERE id = ?");
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    $stmt->close();
                } else if ($status === 'Rejected') {
                    $stmt = $conn->prepare("SELECT doctor_id FROM lab_test_orders WHERE id = ?");
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    $doc_pk = $stmt->get_result()->fetch_assoc()['doctor_id'];
                    $stmt->close();
                    
                    $stmt = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
                    $stmt->bind_param("i", $doc_pk);
                    $stmt->execute();
                    $d_res = $stmt->get_result();
                    if ($d_res->num_rows > 0) {
                        $d_user_id = $d_res->fetch_assoc()['user_id'];
                        $msg = "Sample for Lab Order #ORD-".str_pad($order_id, 5, '0', STR_PAD_LEFT)." was rejected. Condition: $condition. Please arrange a new sample collection.";
                        $ntf = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, related_id, created_at)
                                             VALUES (?, 'doctor', 'lab_update', 'Sample Rejected', ?, 0, 'Orders', ?, NOW())");
                        $ntf->bind_param("isi", $d_user_id, $msg, $order_id);
                        $ntf->execute();
                        $ntf->close();
                    }
                    $stmt->close();
                }

                $new_val = json_encode(['status' => $status]);
                $ip = $_SERVER['REMOTE_ADDR'];
                $ua = $_SERVER['HTTP_USER_AGENT'];
                
                $stmt = $conn->prepare("INSERT INTO lab_audit_trail (technician_id, action_type, module_affected, record_id, old_value, new_value, ip_address, device_info)
                                     VALUES (?, 'Update Sample Status', 'Sample Tracking', ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissss", $user_id, $sample_id, $old_val, $new_val, $ip, $ua);
                $stmt->execute();
                $stmt->close();

                mysqli_commit($conn);
                $response = ['success' => true, 'message' => "Sample updated to $status"];
            } else {
                throw new Exception("Database update failed.");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid sample ID or status.';
    }
}

// ------------------------------------------------------------------
// 4. RESULT MANAGEMENT ACTIONS
// ------------------------------------------------------------------
if ($action === 'update_result_status') {
    $result_id = (int)($_POST['result_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if($result_id > 0 && in_array($status, ['Pending Validation', 'Validated', 'Released', 'Amended'])) {
        try {
            mysqli_begin_transaction($conn);
            
            // Phase 5: Result Status Prep Statement
            $stmt = $conn->prepare("SELECT result_status, order_id FROM lab_results WHERE result_id = ?");
            $stmt->bind_param("i", $result_id);
            $stmt->execute();
            $orig_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $order_id = $orig_data['order_id'];
            $old_val = json_encode(['status' => $orig_data['result_status']]);
            
            // Phase 5: Security Gate — Result MUST be validated before release
            if ($status === 'Released' && $orig_data['result_status'] !== 'Validated') {
                throw new Exception("Validation Gate: Results cannot be released until they have been marked as Validated.");
            }
            
            $query = "UPDATE lab_results SET result_status = ?";
            
            // Auto-timestamp and user stamping
            if ($status === 'Validated') {
                $query .= ", validated_by = ?, validated_at = CURRENT_TIMESTAMP";
            }
            if ($status === 'Released') {
                $query .= ", released_to_doctor = 1, released_at = CURRENT_TIMESTAMP";
            }
            $query .= " WHERE result_id = ?";
            
            $stmt = $conn->prepare($query);
            if ($status === 'Validated') {
                $stmt->bind_param("sii", $status, $user_id, $result_id);
            } else {
                $stmt->bind_param("si", $status, $result_id);
            }

            if ($stmt->execute()) {
                $stmt->close();
                
                // If Released, update order status
                if ($status === 'Released') {
                    $stmt = $conn->prepare("UPDATE lab_test_orders SET order_status = 'Completed' WHERE id = ?");
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Notify Doctor
                    $stmt = $conn->prepare("SELECT doctor_id FROM lab_test_orders WHERE id = ?");
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    $doc_pk = $stmt->get_result()->fetch_assoc()['doctor_id'];
                    $stmt->close();
                    
                    $stmt = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
                    $stmt->bind_param("i", $doc_pk);
                    $stmt->execute();
                    $d_res = $stmt->get_result();
                    if ($d_res->num_rows > 0) {
                        $d_user_id = $d_res->fetch_assoc()['user_id'];
                        $msg = "Lab Results for Order #ORD-".str_pad($order_id, 5, '0', STR_PAD_LEFT)." have been Released.";
                        $ntf = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, related_id, created_at)
                                             VALUES (?, 'doctor', 'lab_result', 'Results Released', ?, 0, 'Results', ?, NOW())");
                        $ntf->bind_param("isi", $d_user_id, $msg, $result_id);
                        $ntf->execute();
                        $ntf->close();

                        // Patient Notification
                        $stmt2 = $conn->prepare("SELECT patient_id FROM lab_test_orders WHERE id = ?");
                        $stmt2->bind_param("i", $order_id);
                        $stmt2->execute();
                        $pat_pk = $stmt2->get_result()->fetch_assoc()['patient_id'];
                        $stmt2->close();
                        
                        $stmt3 = $conn->prepare("SELECT user_id FROM patients WHERE id = ?");
                        $stmt3->bind_param("i", $pat_pk);
                        $stmt3->execute();
                        $p_res = $stmt3->get_result();
                        if ($p_res->num_rows > 0) {
                            $p_user_id = $p_res->fetch_assoc()['user_id'];
                            $pmsg = "Your recent lab test results are ready and have been sent to your doctor.";
                            $ntf2 = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, related_id, created_at)
                                                 VALUES (?, 'patient', 'lab_result', 'Lab Results Ready', ?, 0, 'Results', ?, NOW())");
                            $ntf2->bind_param("isi", $p_user_id, $pmsg, $result_id);
                            $ntf2->execute();
                            $ntf2->close();
                        }
                        $stmt3->close();
                    }
                    if(isset($stmt)) @$stmt->close();
                } else if ($status === 'Amended') {
                    $stmt = $conn->prepare("SELECT doctor_id FROM lab_test_orders WHERE id = ?");
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    $doc_pk = $stmt->get_result()->fetch_assoc()['doctor_id'];
                    $stmt->close();
                    
                    $stmt = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
                    $stmt->bind_param("i", $doc_pk);
                    $stmt->execute();
                    $d_res = $stmt->get_result();
                    if ($d_res->num_rows > 0) {
                        $d_user_id = $d_res->fetch_assoc()['user_id'];
                        $msg = "Lab Results for Order #ORD-".str_pad($order_id, 5, '0', STR_PAD_LEFT)." have been AMENDED by the lab technician. Please review the updated values immediately.";
                        $ntf = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, related_id, created_at)
                                             VALUES (?, 'doctor', 'lab_result', 'Results Amended', ?, 0, 'Results', ?, NOW())");
                        $ntf->bind_param("isi", $d_user_id, $msg, $result_id);
                        $ntf->execute();
                        $ntf->close();
                    }
                    $stmt->close();
                }

                $new_val = json_encode(['status' => $status]);
                $ip = $_SERVER['REMOTE_ADDR'];
                $ua = $_SERVER['HTTP_USER_AGENT'];
                
                $stmt = $conn->prepare("INSERT INTO lab_audit_trail (technician_id, action_type, module_affected, record_id, old_value, new_value, ip_address, device_info)
                                     VALUES (?, 'Update Result Status', 'Result Entry', ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissss", $user_id, $result_id, $old_val, $new_val, $ip, $ua);
                $stmt->execute();
                $stmt->close();

                mysqli_commit($conn);
                $response = ['success' => true, 'message' => "Result status updated to $status"];
            } else {
                throw new Exception("Database update failed.");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid result ID or status.';
    }
}

// ------------------------------------------------------------------
// 5. INTERNAL MESSAGING
// ------------------------------------------------------------------
if ($action === 'send_internal_message') {
    $to_doc = (int)($_POST['to'] ?? 0);
    $msg_txt = $_POST['message'] ?? '';
    
    if($to_doc > 0 && !empty($msg_txt)) {
        try {
            $e_msg = mysqli_real_escape_string($conn, $msg_txt);
            mysqli_query($conn, "INSERT INTO lab_internal_messages (sender_id, sender_role, receiver_id, receiver_role, message)
                                 VALUES ($user_id, 'lab_technician', $to_doc, 'doctor', '$e_msg')");
            
            // Send global app notification to Doctor
            $d_user_q = mysqli_query($conn, "SELECT user_id FROM doctors WHERE id = $to_doc");
            if ($d_user_q && mysqli_num_rows($d_user_q) > 0) {
                $d_user_id = mysqli_fetch_assoc($d_user_q)['user_id'];
                $nrtxt = mysqli_real_escape_string($conn, "Urgent message from Lab Technician: " . substr($msg_txt, 0, 50) . "...");
                mysqli_query($conn, "INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, created_at)
                                     VALUES ($d_user_id, 'doctor', 'message', 'New Message from Lab', '$nrtxt', 0, 'Messages', NOW())");
            }
            $response = ['success' => true, 'message' => 'Message sent successfully'];
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid message payload.';
    }
}

// ------------------------------------------------------------------
// 6. EXCEPTIONAL & ADMIN NOTIFICATIONS
// ------------------------------------------------------------------
if ($action === 'report_delay') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $reason = $_POST['reason'] ?? 'Unspecified delay';
    $doc_q = mysqli_query($conn, "SELECT doctor_id FROM lab_test_orders WHERE id = $order_id");
    if($doc_q && mysqli_num_rows($doc_q) > 0) {
        $doc_pk = mysqli_fetch_assoc($doc_q)['doctor_id'];
        $d_user_id = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM doctors WHERE id = $doc_pk"))['user_id'];
        $msg = "Processing for Lab Order #ORD-$order_id is delayed. Reason: $reason";
        $ntf = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, related_id, created_at)
                             VALUES (?, 'doctor', 'lab_update', 'Test Processing Delayed', ?, 0, 'Orders', ?, NOW())");
        $ntf->bind_param("isi", $d_user_id, $msg, $order_id);
        $ntf->execute();
        $ntf->close();
        $response = ['success' => true, 'message' => 'Delay reported'];
    }
}

if ($action === 'report_critical_result') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $param_name = strip_tags($_POST['parameter'] ?? 'Unknown Parameter');
    $val = strip_tags($_POST['value'] ?? '');
    
    $stmt = $conn->prepare("SELECT doctor_id FROM lab_test_orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $doc_q = $stmt->get_result();
    
    if($doc_q->num_rows > 0) {
        $doc_pk = $doc_q->fetch_assoc()['doctor_id'];
        $stmt->close();
        
        $stmt2 = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
        $stmt2->bind_param("i", $doc_pk);
        $stmt2->execute();
        $d_user_id = $stmt2->get_result()->fetch_assoc()['user_id'];
        $stmt2->close();
        
        $msg = "URGENT: Critical value detected for $param_name ($val) in Lab Order #ORD-$order_id.";
        
        $ntf = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, related_id, created_at)
                             VALUES (?, 'doctor', 'lab_alert', 'CRITICAL LAB RESULT', ?, 0, 'Results', ?, NOW())");
        $ntf->bind_param("isi", $d_user_id, $msg, $order_id);
        $ntf->execute();
        $ntf->close();
        $response = ['success' => true, 'message' => 'Critical alert dispatched'];
    }
}

// ------------------------------------------------------------------
// 7. REPORTING & ANALYTICS API & AI FEATURES (Phase 8)
// ------------------------------------------------------------------
if ($action === 'check_historical_anomaly') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $param_name = strip_tags(trim($_POST['parameter_name'] ?? ''));
    $val = (float)($_POST['current_value'] ?? 0);

    // Fetch the last 15 historical JSON parameter clumps for this patient
    $query = "SELECT r.parameters_json FROM lab_results r 
              JOIN lab_test_orders o ON r.order_id = o.id 
              WHERE o.patient_id = ? AND r.result_status IN ('Validated', 'Released', 'Amended') 
              ORDER BY r.created_at DESC LIMIT 15";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $historical_values = [];
    while($row = $res->fetch_assoc()) {
        $json = json_decode($row['parameters_json'], true) ?: [];
        foreach($json as $k => $v) {
            // Find the matching parameter name in the JSON blob
            if (isset($v['name']) && stripos($v['name'], $param_name) !== false) {
                if (is_numeric($v['value'])) {
                    $historical_values[] = (float)$v['value'];
                }
            }
        }
    }
    $stmt->close();
    
    // Calculate Standard Deviation & Variance if we have enough historical data points
    if (count($historical_values) >= 2) {
        $mean = array_sum($historical_values) / count($historical_values);
        $variance = 0.0;
        foreach ($historical_values as $i) {
            $variance += pow($i - $mean, 2);
        }
        $variance /= count($historical_values);
        $sd = sqrt($variance);
        
        // Apply a strict floor to the SD (5% of mean) to prevent divide-by-zero or triggering on tiny algorithmic jumps
        $min_sd = abs($mean * 0.05) > 0 ? abs($mean * 0.05) : 0.1;
        $active_sd = max($sd, $min_sd);
        
        $z_score = abs($val - $mean) / $active_sd;
        
        // Trigger anomaly if deviation > 2.5 standard deviations (typical 99% confidence interval bound)
        if ($z_score >= 2.5) { 
            $deviation_percent = round((abs($val - $mean) / max(abs($mean), 0.1)) * 100, 1);
            $direction = $val > $mean ? 'higher' : 'lower';
            
            echo json_encode([
                'success' => true,
                'is_anomaly' => true,
                'mean' => round($mean, 2),
                'sd' => round($sd, 2),
                'deviation_percent' => $deviation_percent,
                'direction' => $direction
            ]);
            exit;
        }
    }
    
    echo json_encode(['success' => true, 'is_anomaly' => false]);
    exit;
}

if ($action === 'run_database_seeder') {
    try {
        require_once '../scripts/seed_lab_data.php';
        $response = ['success' => true, 'message' => 'Lab Database successfully completed Phase 7 data seeding with standardized tests, equipment, and catalogs!'];
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
}

if ($action === 'fetch_report_data') {
    try {
        $volume = ['labels' => [], 'values' => []];
        $tat = ['labels' => [], 'values' => []];

        // --- 1. Test Volume (Last 14 Days) ---
        // Fetch everyday for the last 14 days and map to completed tests
        $vol_q = mysqli_query($conn, "
            SELECT DATE(created_at) as test_date, COUNT(*) as total 
            FROM lab_results 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
            GROUP BY DATE(created_at) 
            ORDER BY DATE(created_at) ASC
        ");

        // Initialize array with last 14 days (0 counts)
        $dateArray = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = date('M d', strtotime("-$i days"));
            $dateArray[$date] = 0;
        }

        if ($vol_q && mysqli_num_rows($vol_q) > 0) {
            while ($row = mysqli_fetch_assoc($vol_q)) {
                $lbl = date('M d', strtotime($row['test_date']));
                if(array_key_exists($lbl, $dateArray)) {
                    $dateArray[$lbl] = (int)$row['total'];
                }
            }
        }
        foreach($dateArray as $k => $v) {
            $volume['labels'][] = $k;
            $volume['values'][] = $v;
        }

        // --- 2. Turnaround Time (TAT) per Test Category ---
        // Formula: average hours between sample 'Received' (or order created) and result 'Validated'
        $tat_q = mysqli_query($conn, "
            SELECT c.category,
                   AVG(TIMESTAMPDIFF(HOUR, o.created_at, r.validated_at)) as avg_hours
            FROM lab_results r
            JOIN lab_test_orders o ON r.order_id = o.id
            JOIN lab_test_catalog c ON o.test_catalog_id = c.id
            WHERE r.result_status IN ('Validated', 'Released', 'Amended')
              AND r.validated_at IS NOT NULL
              AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY c.category
            ORDER BY avg_hours DESC
        ");

        if ($tat_q && mysqli_num_rows($tat_q) > 0) {
             while ($row = mysqli_fetch_assoc($tat_q)) {
                  $tat['labels'][] = $row['category'] ?: 'Uncategorized';
                  $tat['values'][] = round((float)$row['avg_hours'], 1);
             }
        } else {
             // Fallback if no robust data exists yet
             $tat['labels'] = ['Hematology', 'Biochemistry', 'Microbiology', 'Immunology'];
             $tat['values'] = [0, 0, 0, 0];
        }

        $response = [
            'success' => true, 
            'data' => [
                'volume' => $volume,
                'tat' => $tat
            ]
        ];
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
}

// ------------------------------------------------------------------
// 7. SECURE FILE UPLOAD HANDLER
// ------------------------------------------------------------------
if ($action === 'upload_document') {

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed or was aborted.");
    }
    
    $file = $_FILES['document'];
    $max_size = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $max_size) throw new Exception("File exceeds maximum allowed size of 5MB.");
    
    $allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime, $allowed_mimes)) throw new Exception("Invalid file type. Only PDF, JPG, and PNG are allowed.");
    
    // Protected path outside standard web scope (or protected by .htaccess)
    $upload_dir = realpath(__DIR__ . '/../../uploads/tech_docs/');
    if (!$upload_dir) throw new Exception("Upload directory configuration error.");
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'doc_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $upload_dir . DIRECTORY_SEPARATOR . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        // Log to database
        $dname = mysqli_real_escape_string($conn, $_POST['document_name'] ?? 'Uploaded Document');
        $dtype = mysqli_real_escape_string($conn, $_POST['document_type'] ?? 'Other');
        
        $tech_q = mysqli_query($conn, "SELECT id FROM lab_technicians WHERE user_id = $user_id");
        if ($tech_q && mysqli_num_rows($tech_q) > 0) {
            $tech_id = mysqli_fetch_assoc($tech_q)['id'];
            mysqli_query($conn, "INSERT INTO lab_technician_documents (technician_id, document_name, document_type, file_path) 
                                 VALUES ($tech_id, '$dname', '$dtype', '$new_filename')");
            $response = ['success' => true, 'message' => 'Document securely uploaded.'];
        } else {
            unlink($dest); // rollback file
            throw new Exception("Technician profile not found.");
        }
    } else {
        throw new Exception("Failed to move uploaded file.");
    }
}

// ------------------------------------------------------------------
// 8. SAMPLE REGISTRATION (NEW)
// ------------------------------------------------------------------
if ($action === 'register_sample') {
    $order_id   = (int)($_POST['order_id']   ?? 0);
    $sample_type = trim($_POST['sample_type'] ?? '');
    $container   = trim($_POST['container']   ?? '');
    $coll_date   = $_POST['collection_date'] ?? date('Y-m-d');
    $coll_time   = $_POST['collection_time'] ?? date('H:i:s');
    $notes       = trim($_POST['notes']       ?? '');

    if ($order_id > 0 && $sample_type !== '' && $container !== '') {
        try {
            // Validate order exists and get patient
            $stmt = $conn->prepare("SELECT patient_id FROM lab_test_orders WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $ord = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$ord) throw new Exception("Test order #$order_id not found.");

            $patient_id = $ord['patient_id'];
            // Generate unique sample barcode: SMP-YYYYMMDD-{rand6}
            $barcode = 'SMP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $stmt = $conn->prepare("INSERT INTO lab_samples
                (order_id, patient_id, sample_code, sample_type, container_type,
                 collection_date, collection_time, notes, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Collected', NOW())");
            $stmt->bind_param("iissssss", $order_id, $patient_id, $barcode, $sample_type,
                              $container, $coll_date, $coll_time, $notes);
            $stmt->execute();
            $new_id = $stmt->insert_id;
            $stmt->close();

            // Update order status to 'Sample Collected'
            $stmt = $conn->prepare("UPDATE lab_test_orders SET order_status = 'Sample Collected' WHERE id = ? AND order_status = 'Accepted'");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();

            // Audit trail
            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'];
            $stmt = $conn->prepare("INSERT INTO lab_audit_trail (technician_id, action_type, module_affected, record_id, new_value, ip_address, device_info)
                VALUES (?, 'Register Sample', 'Sample Tracking', ?, ?, ?, ?)");
            $nv = json_encode(['barcode' => $barcode, 'order_id' => $order_id]);
            $stmt->bind_param("iisss", $user_id, $new_id, $nv, $ip, $ua);
            $stmt->execute();
            $stmt->close();

            $response = ['success' => true, 'message' => "Sample $barcode registered.", 'barcode' => $barcode];
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Missing required sample registration fields.';
    }
}

// ------------------------------------------------------------------
// 9. REFERENCE RANGE: FETCH BY ID  (NEW)
// ------------------------------------------------------------------
if ($action === 'fetch_range_by_id') {
    $range_id = (int)($_POST['range_id'] ?? 0);
    if ($range_id > 0) {
        $stmt = $conn->prepare("SELECT r.*, c.test_name FROM lab_reference_ranges r JOIN lab_test_catalog c ON r.test_catalog_id = c.id WHERE r.id = ? LIMIT 1");
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $response = ['success' => true, 'range' => $row];
        } else {
            $response['message'] = 'Range not found.';
        }
    } else {
        $response['message'] = 'Invalid range ID.';
    }
}

// ------------------------------------------------------------------
// 10. REFERENCE RANGE: SAVE / UPDATE  (NEW)
// ------------------------------------------------------------------
if ($action === 'save_reference_range') {
    $range_id  = (int)($_POST['range_id'] ?? 0);
    $test_id   = (int)($_POST['test_id']  ?? 0);
    $param     = trim($_POST['param']     ?? '');
    $gender    = $_POST['gender']         ?? 'Both';
    $age_min   = (int)($_POST['age_min']  ?? 0);
    $age_max   = (int)($_POST['age_max']  ?? 150);
    $norm_min  = trim($_POST['norm_min']  ?? '');
    $norm_max  = trim($_POST['norm_max']  ?? '');
    $crit_low  = trim($_POST['crit_low']  ?? '');
    $crit_high = trim($_POST['crit_high'] ?? '');
    $unit      = trim($_POST['unit']      ?? '');

    if ($test_id > 0 && $param !== '' && $norm_min !== '' && $norm_max !== '' && $unit !== '') {
        try {
            $e_param     = mysqli_real_escape_string($conn, $param);
            $e_gender    = mysqli_real_escape_string($conn, $gender);
            $e_norm_min  = mysqli_real_escape_string($conn, $norm_min);
            $e_norm_max  = mysqli_real_escape_string($conn, $norm_max);
            $e_crit_low  = mysqli_real_escape_string($conn, $crit_low);
            $e_crit_high = mysqli_real_escape_string($conn, $crit_high);
            $e_unit      = mysqli_real_escape_string($conn, $unit);

            if ($range_id > 0) {
                $q = "UPDATE lab_reference_ranges
                    SET test_catalog_id=$test_id, parameter_name='$e_param', gender='$e_gender',
                        age_min_years=$age_min, age_max_years=$age_max,
                        normal_min='$e_norm_min', normal_max='$e_norm_max',
                        critical_low='$e_crit_low', critical_high='$e_crit_high', unit='$e_unit'
                    WHERE id=$range_id";
                $action_label = 'Update Reference Range';
            } else {
                $q = "INSERT INTO lab_reference_ranges
                    (test_catalog_id, parameter_name, gender, age_min_years, age_max_years,
                     normal_min, normal_max, critical_low, critical_high, unit)
                    VALUES ($test_id, '$e_param', '$e_gender', $age_min, $age_max,
                            '$e_norm_min', '$e_norm_max', '$e_crit_low', '$e_crit_high', '$e_unit')";
                $action_label = 'Add Reference Range';
            }

            mysqli_begin_transaction($conn);
            if (!mysqli_query($conn, $q)) {
                throw new Exception("DB error: " . mysqli_error($conn));
            }
            if ($range_id === 0) {
                $range_id = (int)mysqli_insert_id($conn);
            }
            mysqli_commit($conn);

            // Audit trail
            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'];
            $nv = json_encode(['param' => $param, 'range' => "$norm_min - $norm_max $unit"]);
            $aud = $conn->prepare("INSERT INTO lab_audit_trail
                (technician_id, action_type, module_affected, record_id, new_value, ip_address, device_info)
                VALUES (?, ?, 'Reference Ranges', ?, ?, ?, ?)");
            $aud->bind_param("isisss", $user_id, $action_label, $range_id, $nv, $ip, $ua);
            $aud->execute();
            $aud->close();

            $response = ['success' => true, 'message' => 'Reference range saved successfully.'];
        } catch (Exception $e) {
            @mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Missing required fields (test, parameter, normal range, and unit).';
    }
}

// ------------------------------------------------------------------
// 11. EQUIPMENT: LOG CALIBRATION  (NEW)
// ------------------------------------------------------------------
if ($action === 'log_calibration') {
    $equip_id   = (int)($_POST['equip_id']   ?? 0);
    $calib_date = $_POST['calib_date']        ?? date('Y-m-d');
    $next_date  = $_POST['next_date']         ?? '';
    $notes      = trim($_POST['notes']        ?? '');
    $new_status = $_POST['new_status']        ?? 'Operational';

    if ($equip_id > 0 && $next_date !== '') {
        try {
            mysqli_begin_transaction($conn);

            // Insert calibration log
            $stmt = $conn->prepare("INSERT INTO lab_equipment_maintenance
                (equipment_id, maintenance_type, performed_by, maintenance_date, next_due_date, notes, outcome)
                VALUES (?, 'Calibration', ?, ?, ?, ?, 'Completed')");
            $stmt->bind_param("iisss", $equip_id, $user_id, $calib_date, $next_date, $notes);
            $stmt->execute();
            $stmt->close();

            // Update equipment status and next_calibration_date
            $stmt = $conn->prepare("UPDATE lab_equipment SET status = ?, next_calibration_date = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_status, $next_date, $equip_id);
            $stmt->execute();
            $stmt->close();

            // Audit
            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'];
            $nv = json_encode(['status' => $new_status, 'next_calibration' => $next_date]);
            $aud = $conn->prepare("INSERT INTO lab_audit_trail (technician_id, action_type, module_affected, record_id, new_value, ip_address, device_info)
                VALUES (?, 'Log Calibration', 'Equipment Management', ?, ?, ?, ?)");
            $aud->bind_param("iisss", $user_id, $equip_id, $nv, $ip, $ua);
            $aud->execute();
            $aud->close();

            mysqli_commit($conn);
            $response = ['success' => true, 'message' => 'Calibration record saved.'];
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Missing required calibration fields.';
    }
}

// ------------------------------------------------------------------
// 12. EQUIPMENT: SCHEDULE MAINTENANCE  (NEW)
// ------------------------------------------------------------------
if ($action === 'schedule_maintenance') {
    $equip_id  = (int)($_POST['equip_id']  ?? 0);
    $maint_date = $_POST['maint_date']     ?? '';
    $reason    = trim($_POST['reason']     ?? '');

    if ($equip_id > 0) {
        try {
            mysqli_begin_transaction($conn);

            // Mark equipment as under maintenance
            $stmt = $conn->prepare("UPDATE lab_equipment SET status = 'Maintenance' WHERE id = ?");
            $stmt->bind_param("i", $equip_id);
            $stmt->execute();
            $stmt->close();

            // Insert maintenance log
            $stmt = $conn->prepare("INSERT INTO lab_equipment_maintenance
                (equipment_id, maintenance_type, performed_by, maintenance_date, notes, outcome)
                VALUES (?, 'Scheduled Maintenance', ?, ?, ?, 'Pending')");
            $stmt->bind_param("iiss", $equip_id, $user_id, $maint_date ?: date('Y-m-d'), $reason);
            $stmt->execute();
            $stmt->close();

            mysqli_commit($conn);
            $response = ['success' => true, 'message' => 'Maintenance scheduled. Equipment marked accordingly.'];
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid equipment ID.';
    }
}

// ------------------------------------------------------------------
// 13. EQUIPMENT: LOG DAILY QC  (NEW)
// ------------------------------------------------------------------
if ($action === 'log_qc') {
    $equip_id   = (int)($_POST['equip_id']   ?? 0);
    $qc_result  = $_POST['qc_result']         ?? 'Pass';
    $qc_notes   = trim($_POST['qc_notes']     ?? '');
    $run_date   = $_POST['run_date']           ?? date('Y-m-d');

    if ($equip_id > 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO lab_equipment_maintenance
                (equipment_id, maintenance_type, performed_by, maintenance_date, notes, outcome)
                VALUES (?, 'Daily QC', ?, ?, ?, ?)");
            $outcome = ($qc_result === 'Pass') ? 'Completed' : 'Failed';
            $full_notes = "QC Result: $qc_result. $qc_notes";
            $stmt->bind_param("iisss", $equip_id, $user_id, $run_date, $full_notes, $outcome);
            $stmt->execute();
            $stmt->close();

            // If QC failed, flag equipment
            if ($qc_result === 'Fail') {
                $stmt = $conn->prepare("UPDATE lab_equipment SET status = 'Maintenance' WHERE id = ?");
                $stmt->bind_param("i", $equip_id);
                $stmt->execute();
                $stmt->close();

                // Notify admins
                $admin_q = mysqli_query($conn, "SELECT id FROM users WHERE user_role = 'admin' LIMIT 3");
                while ($adm = mysqli_fetch_assoc($admin_q)) {
                    $ntf = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, title, message, is_read, related_module, related_id, created_at)
                        VALUES (?, 'admin', 'equipment_alert', 'QC Failure Alert', ?, 0, 'Equipment', ?, NOW())");
                    $ntf_msg = "Daily QC FAILED for equipment ID #$equip_id. Equipment flagged as Maintenance.";
                    $ntf->bind_param("isi", $adm['id'], $ntf_msg, $equip_id);
                    $ntf->execute();
                    $ntf->close();
                }
            }

            $response = ['success' => true, 'message' => "QC log recorded. Result: $qc_result."];
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid equipment ID.';
    }
}

// ------------------------------------------------------------------
// 14. EQUIPMENT: ADD NEW EQUIPMENT  (NEW)
// ------------------------------------------------------------------
if ($action === 'add_equipment') {
    $equip_id_str = trim($_POST['equipment_id_str'] ?? '');
    $name         = trim($_POST['name']             ?? '');
    $model        = trim($_POST['model']            ?? '');
    $manufacturer = trim($_POST['manufacturer']     ?? '');
    $location     = trim($_POST['location']         ?? '');
    $department   = trim($_POST['department']       ?? 'Laboratory');
    $purchase_date = $_POST['purchase_date']        ?? null;
    $serial_no    = trim($_POST['serial_no']        ?? '');

    if ($equip_id_str !== '' && $name !== '' && $model !== '') {
        try {
            mysqli_begin_transaction($conn);

            // Check for duplicate equipment_id
            $dup = $conn->prepare("SELECT id FROM lab_equipment WHERE equipment_id = ? LIMIT 1");
            $dup->bind_param("s", $equip_id_str);
            $dup->execute();
            if ($dup->get_result()->num_rows > 0) {
                throw new Exception("Equipment ID '$equip_id_str' already exists. Please use a unique ID.");
            }
            $dup->close();

            $stmt = $conn->prepare("INSERT INTO lab_equipment
                (equipment_id, name, model, manufacturer, location, department, serial_number, purchase_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Operational')");
            $stmt->bind_param("ssssssss", $equip_id_str, $name, $model, $manufacturer, $location, $department, $serial_no, $purchase_date);
            $stmt->execute();
            $new_eid = $stmt->insert_id;
            $stmt->close();

            // Audit
            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'];
            $nv = json_encode(['name' => $name, 'model' => $model, 'equip_id' => $equip_id_str]);
            $aud = $conn->prepare("INSERT INTO lab_audit_trail (technician_id, action_type, module_affected, record_id, new_value, ip_address, device_info)
                VALUES (?, 'Add Equipment', 'Equipment Management', ?, ?, ?, ?)");
            $aud->bind_param("iisss", $user_id, $new_eid, $nv, $ip, $ua);
            $aud->execute();
            $aud->close();

            mysqli_commit($conn);
            $response = ['success' => true, 'message' => "Equipment '$name' added to inventory."];
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Equipment ID, name, and model are required.';
    }
}

// ------------------------------------------------------------------
// 15. MESSAGING: MULTI-ROLE SEND MESSAGE  (EXTEND)
// ------------------------------------------------------------------
if ($action === 'send_msg') {
    $to_user_id  = (int)($_POST['to_user_id']  ?? 0);
    $to_role     = trim($_POST['to_role']       ?? 'doctor');
    $msg_txt     = trim($_POST['message']       ?? '');

    $valid_roles = ['doctor', 'nurse', 'lab_technician', 'pharmacist'];
    if ($to_user_id > 0 && !empty($msg_txt) && in_array($to_role, $valid_roles)) {
        try {
            $e_msg = mysqli_real_escape_string($conn, $msg_txt);
            mysqli_query($conn, "INSERT INTO lab_internal_messages
                (sender_id, sender_role, receiver_id, receiver_role, message_content)
                VALUES ($user_id, 'lab_technician', $to_user_id, '$to_role', '$e_msg')");


            // Push notification to recipient
            $ntf_title = "New Message from Lab";
            $ntf_body  = "Lab message: " . substr($msg_txt, 0, 60) . (strlen($msg_txt) > 60 ? '...' : '');
            $e_body    = mysqli_real_escape_string($conn, $ntf_body);
            mysqli_query($conn, "INSERT INTO notifications
                (user_id, user_role, type, title, message, is_read, related_module, created_at)
                VALUES ($to_user_id, '$to_role', 'message', '$ntf_title', '$e_body', 0, 'Messages', NOW())");

            $response = ['success' => true, 'message' => 'Message sent.'];
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid message payload.';
    }
}

// ------------------------------------------------------------------
// 16. NOTIFICATIONS: MARK READ (NEW)
// ------------------------------------------------------------------
if ($action === 'mark_notif_read') {
    $notif_id = (int)($_POST['notification_id'] ?? 0);
    if ($notif_id > 0) {
        try {
            mysqli_query($conn, "UPDATE lab_notifications SET is_read = 1 WHERE id = $notif_id AND recipient_id = $user_id");
            $response = ['success' => true, 'message' => 'Notification marked as read.'];
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

if ($action === 'mark_all_notifs_read') {
    try {
        mysqli_query($conn, "UPDATE lab_notifications SET is_read = 1 WHERE recipient_id = $user_id AND is_read = 0");
        $response = ['success' => true, 'message' => 'All notifications marked as read.'];
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
}

// ------------------------------------------------------------------
// 17. MESSAGING: MARK MESSAGE READ (NEW)
// ------------------------------------------------------------------
if ($action === 'mark_msg_read') {
    $msg_id = (int)($_POST['msg_id'] ?? 0);
    if ($msg_id > 0) {
        try {
            mysqli_query($conn, "UPDATE lab_internal_messages SET is_read = 1 WHERE id = $msg_id AND receiver_id = $user_id AND receiver_role = 'lab_technician'");
            $response = ['success' => true, 'message' => 'Message marked as read.'];
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

echo json_encode($response);
exit();
?>

