<?php
// ============================================================
// PHARMACY ACTIONS — Secure AJAX Handler
// All queries use prepared statements via pharmacy_security.php
// ============================================================
define('AJAX_REQUEST', true);
require_once 'pharmacy_security.php';
initSecureSession();
$user_id = enforcePharmacistRole();
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');

require_once '../db_conn.php';

// Support both JSON body and multipart FormData (file uploads)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'multipart/form-data') !== false || strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
    $input = $_POST;
} else {
    $input = json_decode(file_get_contents('php://input'), true);
}
if (!$input || !isset($input['action'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid request']); exit;
}

// ── CSRF verification (skip for read-only GETs) ───────────
$readOnlyActions = ['get_medicine','get_prescription'];
if (!in_array($input['action'], $readOnlyActions)) {
    verifyCsrfToken($input['_csrf'] ?? '');
}

$action = $input['action'];

// ── Pharmacist PK ─────────────────────────────────────────
$pharm_pk = (int)dbVal($conn, "SELECT id FROM pharmacist_profile WHERE user_id=?", "i", [$user_id]);

// ── Helper: secure stock alert check ──────────────────────
function secCheckStockAlerts($conn, $medId, $medName, $newQty, $reorder) {
    if ($newQty == 0) {
        secureNotifyAdmins($conn, "⚠️ OUT OF STOCK: $medName — 0 units remaining.", 'alert', 'pharmacy');
        $docs = dbSelect($conn, "SELECT id FROM users WHERE user_role='doctor' AND is_active=1");
        foreach ($docs as $d) {
            secureNotify($conn, $d['id'], "Pharmacy Alert: $medName is now OUT OF STOCK.", 'alert', 'pharmacy');
        }
    } elseif ($newQty <= $reorder) {
        secureNotifyAdmins($conn, "⚠️ LOW STOCK: $medName — only $newQty units left (reorder: $reorder).", 'alert', 'pharmacy');
    }
}

// ── Helper: recalculate profile completeness ──────────────
function updateCompleteness($conn, $pharmId) {
    $row = dbRow($conn, "SELECT * FROM pharmacist_profile_completeness WHERE pharmacist_id=?", "i", [$pharmId]);
    if (!$row) return;
    $sections = ['personal_info','professional_profile','qualifications','photo_uploaded','security_setup','documents_uploaded'];
    $done = 0;
    foreach ($sections as $s) { if (!empty($row[$s])) $done++; }
    $pct = round(($done / count($sections)) * 100);
    dbExecute($conn, "UPDATE pharmacist_profile_completeness SET overall_pct=? WHERE pharmacist_id=?", "ii", [$pct, $pharmId]);
    dbExecute($conn, "UPDATE pharmacist_profile SET profile_completion=? WHERE id=?", "ii", [$pct, $pharmId]);
}

switch ($action) {

// ════════════════════════════════════════════════════════════
// MEDICINE CRUD
// ════════════════════════════════════════════════════════════
case 'add_medicine':
    $name = trim($input['medicine_name'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'message'=>'Medicine name is required']); exit; }

    $generic  = trim($input['generic_name'] ?? '');
    $category = trim($input['category'] ?? '');
    $mfr      = trim($input['manufacturer'] ?? '');
    $unit     = trim($input['unit'] ?? 'tablet');
    $price    = validateFloat($input['unit_price'] ?? 0, 0);
    $qty      = validateInt($input['stock_quantity'] ?? 0, 0);
    $reorder  = validateInt($input['reorder_level'] ?? 10, 0);
    $batch    = trim($input['batch_number'] ?? '');
    $expiry   = trim($input['expiry_date'] ?? '');
    $supplier = trim($input['supplier_name'] ?? '');
    $rxReq    = (int)($input['is_prescription_required'] ?? 1);
    $desc     = trim($input['description'] ?? '');
    $storage  = trim($input['storage_instructions'] ?? '');
    $side     = trim($input['side_effects'] ?? '');
    $contra   = trim($input['contraindications'] ?? '');

    if ($price === false) { echo json_encode(['success'=>false,'message'=>'Invalid unit price']); exit; }
    if ($qty === false)   { echo json_encode(['success'=>false,'message'=>'Invalid stock quantity']); exit; }

    $medId = 'MED-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    $expiryVal = ($expiry && validateDate($expiry)) ? $expiry : null;

    $newId = dbInsert($conn,
        "INSERT INTO medicines (medicine_id, medicine_name, generic_name, category, manufacturer, unit, unit_price,
         stock_quantity, reorder_level, batch_number, expiry_date, supplier_name, is_prescription_required,
         description, storage_instructions, side_effects, contraindications, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'active')",
        "ssssssdiisissssss",
        [$medId, $name, $generic, $category, $mfr, $unit, $price, $qty, $reorder, $batch, $expiryVal, $supplier, $rxReq, $desc, $storage, $side, $contra]
    );

    if ($newId) {
        if ($qty > 0) {
            dbExecute($conn, "INSERT INTO stock_transactions (medicine_id, transaction_type, quantity, previous_quantity, new_quantity, performed_by, notes) VALUES (?, 'restock', ?, 0, ?, ?, 'Initial stock')", "iiis", [$newId, $qty, $qty, $user_id]);
        }
        secureLog($conn, $pharm_pk, "Added medicine: $name (Qty: $qty)", 'inventory');
        echo json_encode(['success'=>true,'message'=>"$name added successfully"]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Failed to add medicine']);
    }
    break;

case 'get_medicine':
    $id = validateInt($input['id'] ?? 0, 1);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $r = dbRow($conn, "SELECT * FROM medicines WHERE id=?", "i", [$id]);
    if ($r) {
        $r['days_to_expiry'] = $r['expiry_date'] ? (int)((strtotime($r['expiry_date']) - time()) / 86400) : 999;
        echo json_encode(['success'=>true,'medicine'=>$r]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Medicine not found']);
    }
    break;

case 'update_medicine':
    $id = validateInt($input['id'] ?? 0, 1);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }

    $fields = []; $types = ''; $params = [];
    $allowedFields = ['medicine_name','generic_name','category','manufacturer','batch_number','expiry_date','supplier_name','description','storage_instructions','side_effects','contraindications'];
    foreach ($allowedFields as $f) {
        if (isset($input[$f])) { $fields[] = "$f=?"; $types .= 's'; $params[] = trim($input[$f]); }
    }
    foreach (['unit_price'] as $f) {
        if (isset($input[$f])) { $v = validateFloat($input[$f], 0); if ($v !== false) { $fields[] = "$f=?"; $types .= 'd'; $params[] = $v; } }
    }
    foreach (['stock_quantity','reorder_level'] as $f) {
        if (isset($input[$f])) { $v = validateInt($input[$f], 0); if ($v !== false) { $fields[] = "$f=?"; $types .= 'i'; $params[] = $v; } }
    }
    if (empty($fields)) { echo json_encode(['success'=>false,'message'=>'Nothing to update']); exit; }

    $types .= 'i'; $params[] = $id;
    $ok = dbExecute($conn, "UPDATE medicines SET " . implode(',', $fields) . " WHERE id=?", $types, $params);
    if ($ok !== false) {
        secureLog($conn, $pharm_pk, "Updated medicine #$id", 'inventory');
        echo json_encode(['success'=>true,'message'=>'Medicine updated']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Update failed']);
    }
    break;

case 'discontinue_medicine':
    $id = validateInt($input['id'] ?? 0, 1);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $ok = dbExecute($conn, "UPDATE medicines SET status='discontinued' WHERE id=?", "i", [$id]);
    if ($ok !== false) {
        secureLog($conn, $pharm_pk, "Discontinued medicine #$id", 'inventory');
        echo json_encode(['success'=>true,'message'=>'Medicine discontinued']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Error']);
    }
    break;

// ════════════════════════════════════════════════════════════
// PRESCRIPTION DISPENSING
// ════════════════════════════════════════════════════════════
case 'get_prescription':
    $id = validateInt($input['id'] ?? 0, 1);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $rx = dbRow($conn,
        "SELECT pr.*, up.name AS patient_name, ud.name AS doctor_name, p.user_id AS patient_user_id, d.user_id AS doctor_user_id
         FROM prescriptions pr
         JOIN patients p ON pr.patient_id=p.id JOIN users up ON p.user_id=up.id
         JOIN doctors d ON pr.doctor_id=d.id JOIN users ud ON d.user_id=ud.id
         WHERE pr.id=?", "i", [$id]);
    if ($rx) {
        $stk = dbRow($conn, "SELECT id, stock_quantity FROM medicines WHERE medicine_name=?", "s", [$rx['medication_name']]);
        $rx['stock_available'] = (int)($stk['stock_quantity'] ?? 0);
        $rx['medicine_db_id']  = (int)($stk['id'] ?? 0);
        echo json_encode(['success'=>true,'prescription'=>$rx]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Prescription not found']);
    }
    break;

case 'dispense_prescription':
    $rxId   = validateInt($input['prescription_id'] ?? 0, 1);
    $qty    = validateInt($input['qty'] ?? 0, 1);
    $payStatus = trim($input['payment_status'] ?? 'unpaid');
    $notes  = trim($input['notes'] ?? '');

    if (!$rxId || !$qty) { echo json_encode(['success'=>false,'message'=>'Invalid data']); exit; }
    if (!in_array($payStatus, ['paid','unpaid','insurance'])) $payStatus = 'unpaid';

    $rx = dbRow($conn,
        "SELECT pr.*, p.user_id AS patient_user_id, d.user_id AS doctor_user_id
         FROM prescriptions pr JOIN patients p ON pr.patient_id=p.id JOIN doctors d ON pr.doctor_id=d.id
         WHERE pr.id=?", "i", [$rxId]);
    if (!$rx) { echo json_encode(['success'=>false,'message'=>'Prescription not found']); exit; }

    $med = dbRow($conn, "SELECT id, stock_quantity, unit_price, reorder_level FROM medicines WHERE medicine_name=?", "s", [$rx['medication_name']]);
    if (!$med) { echo json_encode(['success'=>false,'message'=>"Medicine not in inventory"]); exit; }
    if ((int)$med['stock_quantity'] < $qty) { echo json_encode(['success'=>false,'message'=>"Insufficient stock. Available: {$med['stock_quantity']}"]); exit; }

    $medId   = (int)$med['id'];
    $prevQty = (int)$med['stock_quantity'];
    $newQty  = $prevQty - $qty;
    $price   = (float)$med['unit_price'];
    $reorder = (int)$med['reorder_level'];
    $rxQty   = (int)($rx['quantity'] ?? 1);
    $newStatus = ($qty >= $rxQty) ? 'Dispensed' : 'Partially Dispensed';
    $medName = $rx['medication_name'];

    mysqli_begin_transaction($conn);
    try {
        dbExecute($conn, "UPDATE medicines SET stock_quantity=? WHERE id=?", "ii", [$newQty, $medId]);
        dbExecute($conn, "UPDATE prescriptions SET status=?, pharmacist_id=?, dispensed_by=?, dispensed_date=NOW() WHERE id=?", "siii", [$newStatus, $user_id, $user_id, $rxId]);
        dbExecute($conn, "INSERT INTO dispensing_records (prescription_id, patient_id, pharmacist_id, medicine_id, quantity_dispensed, selling_price, payment_status, notes) VALUES (?,?,?,?,?,?,?,?)",
            "iiiidsss", [$rxId, $rx['patient_id'], $user_id, $medId, $qty, $price, $payStatus, $notes]);
        dbExecute($conn, "INSERT INTO stock_transactions (medicine_id, transaction_type, quantity, previous_quantity, new_quantity, performed_by, notes) VALUES (?, 'dispensed', ?, ?, ?, ?, ?)",
            "iiiis", [$medId, $qty, $prevQty, $newQty, $user_id, "Dispensed for Rx #$rxId"]);
        secureNotify($conn, $rx['patient_user_id'], "Your prescription for $medName has been dispensed.", 'dispensing', 'prescriptions');
        secureNotify($conn, $rx['doctor_user_id'], "Prescription #$rxId ($medName) has been dispensed.", 'dispensing', 'prescriptions');
        secCheckStockAlerts($conn, $medId, $medName, $newQty, $reorder);
        mysqli_commit($conn);
        secureLog($conn, $pharm_pk, "Dispensed $qty x $medName for Rx #$rxId", 'dispensing');
        echo json_encode(['success'=>true,'message'=>"$qty x $medName dispensed ($newStatus)"]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success'=>false,'message'=>'Transaction failed']);
    }
    break;

// ════════════════════════════════════════════════════════════
// STOCK MANAGEMENT
// ════════════════════════════════════════════════════════════
case 'receive_stock':
    $medId = validateInt($input['medicine_id'] ?? 0, 1);
    $qty   = validateInt($input['quantity'] ?? 0, 1);
    if (!$medId || !$qty) { echo json_encode(['success'=>false,'message'=>'Medicine and quantity required']); exit; }

    $med = dbRow($conn, "SELECT stock_quantity, medicine_name, reorder_level FROM medicines WHERE id=?", "i", [$medId]);
    if (!$med) { echo json_encode(['success'=>false,'message'=>'Medicine not found']); exit; }

    $prev = (int)$med['stock_quantity'];
    $newQ = $prev + $qty;
    $batch  = trim($input['batch_number'] ?? '');
    $expiry = trim($input['expiry_date'] ?? '');
    $notes  = trim($input['notes'] ?? '');

    mysqli_begin_transaction($conn);
    try {
        $updateParts = ["stock_quantity=?"]; $updateTypes = "i"; $updateParams = [$newQ];
        if ($batch) { $updateParts[] = "batch_number=?"; $updateTypes .= "s"; $updateParams[] = $batch; }
        if ($expiry && validateDate($expiry)) { $updateParts[] = "expiry_date=?"; $updateTypes .= "s"; $updateParams[] = $expiry; }
        $updateTypes .= "i"; $updateParams[] = $medId;
        dbExecute($conn, "UPDATE medicines SET " . implode(',', $updateParts) . " WHERE id=?", $updateTypes, $updateParams);
        dbExecute($conn, "INSERT INTO stock_transactions (medicine_id, transaction_type, quantity, previous_quantity, new_quantity, performed_by, notes) VALUES (?, 'restock', ?, ?, ?, ?, ?)",
            "iiiis", [$medId, $qty, $prev, $newQ, $user_id, $notes]);
        if ($newQ > (int)$med['reorder_level']) {
            dbExecute($conn, "UPDATE stock_alerts SET is_resolved=1, resolved_by=?, resolved_at=NOW() WHERE medicine_id=? AND alert_type IN('low_stock','out_of_stock') AND is_resolved=0", "ii", [$user_id, $medId]);
        }
        mysqli_commit($conn);
        secureLog($conn, $pharm_pk, "Restocked {$med['medicine_name']}: +$qty (now $newQ)", 'stock');
        echo json_encode(['success'=>true,'message'=>"+$qty units added. New stock: $newQ"]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success'=>false,'message'=>'Error']);
    }
    break;

case 'adjust_stock':
    $medId = validateInt($input['medicine_id'] ?? 0, 1);
    $qty   = validateInt($input['quantity'] ?? 0, 1);
    $type  = trim($input['type'] ?? 'adjusted');
    $notes = trim($input['notes'] ?? '');

    if (!$medId || !$qty) { echo json_encode(['success'=>false,'message'=>'Medicine and quantity required']); exit; }
    if (!$notes) { echo json_encode(['success'=>false,'message'=>'Reason is mandatory']); exit; }
    if (!in_array($type, ['adjusted','damaged','returned','expired'])) $type = 'adjusted';

    $med = dbRow($conn, "SELECT stock_quantity, medicine_name, reorder_level FROM medicines WHERE id=?", "i", [$medId]);
    if (!$med) { echo json_encode(['success'=>false,'message'=>'Medicine not found']); exit; }

    $prev = (int)$med['stock_quantity'];
    $newQ = max(0, $prev - $qty);

    mysqli_begin_transaction($conn);
    try {
        dbExecute($conn, "UPDATE medicines SET stock_quantity=? WHERE id=?", "ii", [$newQ, $medId]);
        dbExecute($conn, "INSERT INTO stock_transactions (medicine_id, transaction_type, quantity, previous_quantity, new_quantity, performed_by, notes) VALUES (?,?,?,?,?,?,?)",
            "isiiiis", [$medId, $type, $qty, $prev, $newQ, $user_id, $notes]);
        secCheckStockAlerts($conn, $medId, $med['medicine_name'], $newQ, (int)$med['reorder_level']);
        mysqli_commit($conn);
        secureLog($conn, $pharm_pk, "Stock adjustment ($type): {$med['medicine_name']} -$qty ($prev→$newQ)", 'stock');
        echo json_encode(['success'=>true,'message'=>"Stock adjusted. {$med['medicine_name']}: $prev → $newQ"]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success'=>false,'message'=>'Error']);
    }
    break;

// ════════════════════════════════════════════════════════════
// PURCHASE ORDERS
// ════════════════════════════════════════════════════════════
case 'create_purchase_order':
    $supId = validateInt($input['supplier_id'] ?? 0, 1);
    $medId = validateInt($input['medicine_id'] ?? 0, 1);
    $qty   = validateInt($input['quantity'] ?? 0, 1);
    $price = validateFloat($input['unit_price'] ?? 0, 0);
    if (!$supId || !$medId || !$qty) { echo json_encode(['success'=>false,'message'=>'Supplier, medicine, and quantity required']); exit; }

    $expDel = trim($input['expected_delivery_date'] ?? '');
    $notes  = trim($input['notes'] ?? '');
    $orderNum = 'PO-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    $total = $qty * ($price ?: 0);
    $expDelVal = ($expDel && validateDate($expDel)) ? $expDel : null;

    mysqli_begin_transaction($conn);
    try {
        $poId = dbInsert($conn, "INSERT INTO purchase_orders (order_number, supplier_id, ordered_by, order_date, expected_delivery_date, status, total_amount, notes) VALUES (?,?,?,CURDATE(),?,?,?,?)",
            "siissds", [$orderNum, $supId, $user_id, $expDelVal, 'draft', $total, $notes]);
        dbExecute($conn, "INSERT INTO purchase_order_items (order_id, medicine_id, ordered_quantity, unit_price, total_price, status) VALUES (?,?,?,?,?,'pending')",
            "iiidd", [$poId, $medId, $qty, $price, $total]);
        $mName = dbVal($conn, "SELECT medicine_name FROM medicines WHERE id=?", "i", [$medId]);
        secureNotifyAdmins($conn, "📋 New PO $orderNum: $qty x $mName (GH₵".number_format($total,2)."). Review and approve.", 'purchase_order', 'pharmacy');
        mysqli_commit($conn);
        secureLog($conn, $pharm_pk, "Created PO $orderNum (Qty: $qty)", 'purchase_order');
        echo json_encode(['success'=>true,'message'=>"Purchase order $orderNum created"]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success'=>false,'message'=>'Error']);
    }
    break;

case 'receive_order':
    $poId = validateInt($input['order_id'] ?? 0, 1);
    if (!$poId) { echo json_encode(['success'=>false,'message'=>'Invalid order ID']); exit; }
    $po = dbRow($conn, "SELECT * FROM purchase_orders WHERE id=?", "i", [$poId]);
    if (!$po) { echo json_encode(['success'=>false,'message'=>'Order not found']); exit; }

    mysqli_begin_transaction($conn);
    try {
        dbExecute($conn, "UPDATE purchase_orders SET status='received', actual_delivery_date=CURDATE() WHERE id=?", "i", [$poId]);
        $items = dbSelect($conn, "SELECT * FROM purchase_order_items WHERE order_id=?", "i", [$poId]);
        foreach ($items as $item) {
            $mId = (int)$item['medicine_id'];
            $oQty = (int)$item['ordered_quantity'];
            dbExecute($conn, "UPDATE purchase_order_items SET received_quantity=?, status='received' WHERE id=?", "ii", [$oQty, $item['id']]);
            $prev = (int)dbVal($conn, "SELECT stock_quantity FROM medicines WHERE id=?", "i", [$mId]);
            $newQ = $prev + $oQty;
            dbExecute($conn, "UPDATE medicines SET stock_quantity=? WHERE id=?", "ii", [$newQ, $mId]);
            dbExecute($conn, "INSERT INTO stock_transactions (medicine_id, transaction_type, quantity, previous_quantity, new_quantity, performed_by, notes) VALUES (?, 'restock', ?, ?, ?, ?, ?)",
                "iiiis", [$mId, $oQty, $prev, $newQ, $user_id, "PO {$po['order_number']} received"]);
        }
        mysqli_commit($conn);
        secureLog($conn, $pharm_pk, "Received PO {$po['order_number']}", 'purchase_order');
        echo json_encode(['success'=>true,'message'=>'Order received and stock updated']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success'=>false,'message'=>'Error']);
    }
    break;

case 'cancel_order':
    $poId = validateInt($input['order_id'] ?? 0, 1);
    if (!$poId) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $ok = dbExecute($conn, "UPDATE purchase_orders SET status='cancelled' WHERE id=? AND status='draft'", "i", [$poId]);
    if ($ok) {
        secureLog($conn, $pharm_pk, "Cancelled PO #$poId", 'purchase_order');
        echo json_encode(['success'=>true,'message'=>'Order cancelled']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Cannot cancel — order may already be sent']);
    }
    break;

// ════════════════════════════════════════════════════════════
// SUPPLIERS
// ════════════════════════════════════════════════════════════
case 'add_supplier':
    $name = trim($input['supplier_name'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'message'=>'Supplier name required']); exit; }
    $ok = dbExecute($conn,
        "INSERT INTO pharmacy_suppliers (supplier_name, contact_person, phone, email, address, supply_categories, payment_terms) VALUES (?,?,?,?,?,?,?)",
        "sssssss", [$name, trim($input['contact_person']??''), trim($input['phone']??''), trim($input['email']??''), trim($input['address']??''), trim($input['supply_categories']??''), trim($input['payment_terms']??'Net 30')]);
    if ($ok !== false) {
        secureLog($conn, $pharm_pk, "Added supplier: $name", 'supplier');
        echo json_encode(['success'=>true,'message'=>"Supplier '$name' added"]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Error adding supplier']);
    }
    break;

case 'toggle_supplier':
    $supId  = validateInt($input['supplier_id'] ?? 0, 1);
    $active = (int)($input['active'] ?? 0);
    if (!$supId) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    dbExecute($conn, "UPDATE pharmacy_suppliers SET is_active=? WHERE supplier_id=?", "ii", [$active, $supId]);
    secureLog($conn, $pharm_pk, ($active ? 'Activated' : 'Deactivated') . " supplier #$supId", 'supplier');
    echo json_encode(['success'=>true,'message'=>'Supplier ' . ($active ? 'activated' : 'deactivated')]);
    break;

// ════════════════════════════════════════════════════════════
// ALERTS
// ════════════════════════════════════════════════════════════
case 'resolve_alert':
    $alertId = validateInt($input['alert_id'] ?? 0, 1);
    if (!$alertId) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    dbExecute($conn, "UPDATE stock_alerts SET is_resolved=1, resolved_by=?, resolved_at=NOW() WHERE id=?", "ii", [$user_id, $alertId]);
    secureLog($conn, $pharm_pk, "Resolved alert #$alertId", 'alert');
    echo json_encode(['success'=>true,'message'=>'Alert resolved']);
    break;

// ════════════════════════════════════════════════════════════
// DISPENSING
// ════════════════════════════════════════════════════════════
case 'mark_paid':
    $dispId = validateInt($input['dispensing_id'] ?? 0, 1);
    if (!$dispId) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    dbExecute($conn, "UPDATE dispensing_records SET payment_status='paid' WHERE id=?", "i", [$dispId]);
    echo json_encode(['success'=>true,'message'=>'Payment status updated']);
    break;

// ════════════════════════════════════════════════════════════
// NOTIFICATIONS
// ════════════════════════════════════════════════════════════
case 'mark_notification_read':
    $nId = validateInt($input['notification_id'] ?? 0, 1);
    if ($nId) dbExecute($conn, "UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?", "ii", [$nId, $user_id]);
    echo json_encode(['success'=>true]);
    break;

case 'mark_all_notifications_read':
    dbExecute($conn, "UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0", "i", [$user_id]);
    echo json_encode(['success'=>true]);
    break;

// ════════════════════════════════════════════════════════════
// SETTINGS
// ════════════════════════════════════════════════════════════
case 'update_settings':
    $sets = [];
    $types = ''; $params = [];
    foreach (['notif_new_prescription','notif_low_stock','notif_expiring_meds','notif_purchase_orders','notif_refill_requests','notif_system_alerts'] as $k) {
        $sets[] = "$k=?"; $types .= 'i'; $params[] = (int)($input[$k] ?? 0);
    }
    $channel = trim($input['preferred_channel'] ?? 'dashboard');
    $theme   = trim($input['theme_preference'] ?? 'light');
    $lang    = trim($input['language'] ?? 'English');
    if (!in_array($channel,['dashboard','email','sms','all'])) $channel='dashboard';
    if (!in_array($theme,['light','dark'])) $theme='light';
    $sets[] = "preferred_channel=?"; $types .= 's'; $params[] = $channel;
    $sets[] = "theme_preference=?";  $types .= 's'; $params[] = $theme;
    $sets[] = "language=?";          $types .= 's'; $params[] = $lang;

    $exists = (int)dbVal($conn, "SELECT COUNT(*) FROM pharmacy_settings WHERE pharmacist_id=?", "i", [$pharm_pk]);
    if ($exists) {
        $types .= 'i'; $params[] = $pharm_pk;
        dbExecute($conn, "UPDATE pharmacy_settings SET " . implode(',', $sets) . " WHERE pharmacist_id=?", $types, $params);
    } else {
        $cols = array_map(function($s){ return explode('=', $s)[0]; }, $sets);
        $placeholders = array_fill(0, count($cols), '?');
        $types = 'i' . $types; array_unshift($params, $pharm_pk);
        dbExecute($conn, "INSERT INTO pharmacy_settings (pharmacist_id, " . implode(',', $cols) . ") VALUES (?, " . implode(',', $placeholders) . ")", $types, $params);
    }
    secureLog($conn, $pharm_pk, 'Updated settings', 'settings');
    echo json_encode(['success'=>true,'message'=>'Settings saved']);
    break;

case 'change_password':
    $current = $input['current_password'] ?? '';
    $newPw   = $input['new_password'] ?? '';
    if (strlen($newPw) < 6) { echo json_encode(['success'=>false,'message'=>'Password must be at least 6 characters']); exit; }

    if (!verifyPassword($conn, $user_id, $current)) {
        secureLog($conn, $pharm_pk, 'Failed password change attempt', 'security');
        echo json_encode(['success'=>false,'message'=>'Current password is incorrect']); exit;
    }
    $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
    dbExecute($conn, "UPDATE users SET password=? WHERE id=?", "si", [$hash, $user_id]);
    secureLog($conn, $pharm_pk, 'Changed password', 'security');
    echo json_encode(['success'=>true,'message'=>'Password changed successfully']);
    break;

case 'revoke_session':
    $sessId = validateInt($input['session_id'] ?? 0, 1);
    if ($sessId) dbExecute($conn, "DELETE FROM pharmacist_sessions WHERE id=? AND pharmacist_id=?", "ii", [$sessId, $pharm_pk]);
    echo json_encode(['success'=>true,'message'=>'Session revoked']);
    break;

// ════════════════════════════════════════════════════════════
// REPORTS (secured — download via pharmacy_download.php)
// ════════════════════════════════════════════════════════════
case 'generate_report':
    $type   = trim($input['report_type'] ?? 'inventory_status');
    $format = trim($input['format'] ?? 'CSV');
    $start  = trim($input['start_date'] ?? date('Y-m-01'));
    $end    = trim($input['end_date'] ?? date('Y-m-d'));

    if (!in_array($type, ['inventory_status','dispensing_records','stock_transactions','alert_summary','purchase_orders','supplier_report','prescription_fulfillment','analytics_summary'])) {
        echo json_encode(['success'=>false,'message'=>'Invalid report type']); exit;
    }
    if ($start && !validateDate($start)) { echo json_encode(['success'=>false,'message'=>'Invalid start date']); exit; }
    if ($end && !validateDate($end)) { echo json_encode(['success'=>false,'message'=>'Invalid end date']); exit; }

    $reportDir = __DIR__ . '/../../uploads/pharmacy_reports';
    if (!is_dir($reportDir)) @mkdir($reportDir, 0755, true);

    $fileName = $type . '_' . date('Ymd_His') . '.csv';
    $filePath = "uploads/pharmacy_reports/$fileName";
    $fullPath = $reportDir . '/' . $fileName;

    $fp = fopen($fullPath, 'w');
    fputcsv($fp, ['RMU Medical Management System — Pharmacy Report']);
    fputcsv($fp, ['Report: ' . ucwords(str_replace('_', ' ', $type))]);
    fputcsv($fp, ['Period: ' . $start . ' to ' . $end]);
    fputcsv($fp, ['Generated by: ' . ($_SESSION['user_name'] ?? 'Pharmacist')]);
    fputcsv($fp, ['Date: ' . date('d M Y, g:i A')]);
    fputcsv($fp, []);

    switch ($type) {
        case 'inventory_status':
            fputcsv($fp, ['Medicine','Generic Name','Category','Stock','Reorder','Unit Price','Expiry','Supplier','Status']);
            $rows = dbSelect($conn, "SELECT * FROM medicines WHERE status='active' ORDER BY medicine_name");
            foreach ($rows as $r) {
                $st = $r['stock_quantity'] == 0 ? 'Out of Stock' : ($r['stock_quantity'] <= $r['reorder_level'] ? 'Low Stock' : 'In Stock');
                fputcsv($fp, [$r['medicine_name'],$r['generic_name'],$r['category'],$r['stock_quantity'],$r['reorder_level'],$r['unit_price'],$r['expiry_date'],$r['supplier_name'],$st]);
            }
            break;
        case 'dispensing_records':
            fputcsv($fp, ['ID','Patient','Medicine','Quantity','Date','Payment','Notes']);
            $rows = dbSelect($conn,
                "SELECT dr.*, m.medicine_name, up.name AS patient_name FROM dispensing_records dr JOIN medicines m ON dr.medicine_id=m.id JOIN patients p ON dr.patient_id=p.id JOIN users up ON p.user_id=up.id WHERE dr.dispensing_date BETWEEN ? AND CONCAT(?,' 23:59:59') ORDER BY dr.dispensing_date DESC",
                "ss", [$start, $end]);
            foreach ($rows as $r) { fputcsv($fp, [$r['id'],$r['patient_name'],$r['medicine_name'],$r['quantity_dispensed'],$r['dispensing_date'],$r['payment_status'],$r['notes']]); }
            break;
        case 'stock_transactions':
            fputcsv($fp, ['Date','Medicine','Type','Quantity','Before','After','By','Notes']);
            $rows = dbSelect($conn,
                "SELECT st.*, m.medicine_name, u.name FROM stock_transactions st JOIN medicines m ON st.medicine_id=m.id JOIN users u ON st.performed_by=u.id WHERE st.transaction_date BETWEEN ? AND CONCAT(?,' 23:59:59') ORDER BY st.transaction_date DESC",
                "ss", [$start, $end]);
            foreach ($rows as $r) { fputcsv($fp, [$r['transaction_date'],$r['medicine_name'],$r['transaction_type'],$r['quantity'],$r['previous_quantity'],$r['new_quantity'],$r['name'],$r['notes']]); }
            break;
        case 'alert_summary':
            fputcsv($fp, ['Medicine','Alert Type','Current Stock','Threshold','Expiry','Created','Resolved']);
            $rows = dbSelect($conn,
                "SELECT sa.*, m.medicine_name, m.expiry_date FROM stock_alerts sa JOIN medicines m ON sa.medicine_id=m.id WHERE sa.created_at BETWEEN ? AND CONCAT(?,' 23:59:59') ORDER BY sa.created_at DESC",
                "ss", [$start, $end]);
            foreach ($rows as $r) { fputcsv($fp, [$r['medicine_name'],$r['alert_type'],$r['current_value'],$r['threshold_value'],$r['expiry_date'],$r['created_at'],$r['is_resolved']?'Yes':'No']); }
            break;
        default:
            fputcsv($fp, ['No data for this report type']);
            break;
    }
    fclose($fp);

    // Save record & return secure download URL
    $reportId = dbInsert($conn, "INSERT INTO pharmacy_reports (generated_by, report_type, parameters, file_path, format) VALUES (?, ?, ?, ?, 'CSV')",
        "ssss", [$user_id, $type, json_encode(['start_date'=>$start,'end_date'=>$end]), $filePath]);
    secureLog($conn, $pharm_pk, "Generated report: $type", 'report');

    echo json_encode(['success'=>true,'message'=>'Report generated','download_url'=>"/RMU-Medical-Management-System/php/dashboards/pharmacy_download.php?type=report&id=$reportId"]);
    break;

// ════════════════════════════════════════════════════════════
// MODULE 9: PHARMACIST PROFILE ACTIONS
// ════════════════════════════════════════════════════════════
case 'update_profile_personal':
    $fields = []; $types = ''; $params = [];
    $allowed = ['full_name','date_of_birth','gender','nationality','marital_status','national_id',
                'phone','secondary_phone','email','personal_email','street_address','city','region','country','postal_code','office_location'];
    foreach ($allowed as $f) {
        if (isset($input[$f])) { $fields[] = "$f=?"; $types .= 's'; $params[] = trim($input[$f]); }
    }
    if (empty($fields)) { echo json_encode(['success'=>false,'message'=>'Nothing to update']); exit; }
    $types .= 'i'; $params[] = $pharm_pk;
    $ok = dbExecute($conn, "UPDATE pharmacist_profile SET " . implode(',', $fields) . " WHERE id=?", $types, $params);
    if ($ok !== false) {
        // Update completeness
        $hasPersonal = !empty(trim($input['full_name']??'')) && !empty(trim($input['phone']??''));
        dbExecute($conn, "INSERT INTO pharmacist_profile_completeness (pharmacist_id, personal_info) VALUES (?,?) ON DUPLICATE KEY UPDATE personal_info=?",
            "iii", [$pharm_pk, (int)$hasPersonal, (int)$hasPersonal]);
        updateCompleteness($conn, $pharm_pk);
        secureLog($conn, $pharm_pk, 'Updated personal information', 'profile');
        echo json_encode(['success'=>true,'message'=>'Personal information saved']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Update failed']);
    }
    break;

case 'update_profile_professional':
    $fields = []; $types = ''; $params = [];
    $allowed = ['license_number','license_issuing_body','license_expiry','specialization','department',
                'pharmacy_school','graduation_year','postgrad_training','bio'];
    foreach ($allowed as $f) {
        if (isset($input[$f])) {
            if ($f === 'years_of_experience' || $f === 'graduation_year') {
                $fields[] = "$f=?"; $types .= 'i'; $params[] = (int)$input[$f];
            } else {
                $fields[] = "$f=?"; $types .= 's'; $params[] = trim($input[$f]);
            }
        }
    }
    if (isset($input['years_of_experience'])) {
        $fields[] = "years_of_experience=?"; $types .= 'i'; $params[] = (int)$input['years_of_experience'];
    }
    if (empty($fields)) { echo json_encode(['success'=>false,'message'=>'Nothing to update']); exit; }
    $types .= 'i'; $params[] = $pharm_pk;
    $ok = dbExecute($conn, "UPDATE pharmacist_profile SET " . implode(',', $fields) . " WHERE id=?", $types, $params);
    if ($ok !== false) {
        $hasProfessional = !empty(trim($input['license_number']??'')) && !empty(trim($input['specialization']??''));
        dbExecute($conn, "INSERT INTO pharmacist_profile_completeness (pharmacist_id, professional_profile) VALUES (?,?) ON DUPLICATE KEY UPDATE professional_profile=?",
            "iii", [$pharm_pk, (int)$hasProfessional, (int)$hasProfessional]);
        updateCompleteness($conn, $pharm_pk);
        // License expiry alert
        if (!empty($input['license_expiry'])) {
            $daysLeft = (int)((strtotime($input['license_expiry']) - time()) / 86400);
            if ($daysLeft <= 60 && $daysLeft > 0) {
                secureNotify($conn, $user_id, "⚠️ Your pharmacy license expires in $daysLeft days. Please renew.", 'alert', 'profile');
                secureNotifyAdmins($conn, "⚠️ Pharmacist license expiring: {$input['license_number']} expires in $daysLeft days.", 'alert', 'pharmacy');
            }
        }
        secureLog($conn, $pharm_pk, 'Updated professional profile', 'profile');
        echo json_encode(['success'=>true,'message'=>'Professional profile saved']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Update failed']);
    }
    break;

case 'upload_photo':
    if (empty($_FILES['photo'])) { echo json_encode(['success'=>false,'message'=>'No file received']); exit; }
    $vld = validateUpload($_FILES['photo'], ['image/jpeg','image/png'], 2097152);
    if (!$vld['valid']) { echo json_encode(['success'=>false,'message'=>$vld['error']]); exit; }
    $uploadDir = __DIR__ . '/../../uploads/pharmacist_photos';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $fileName = 'pharm_' . $pharm_pk . '_' . time() . '.' . $ext;
    $dest = $uploadDir . '/' . $fileName;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
        $relPath = 'uploads/pharmacist_photos/' . $fileName;
        dbExecute($conn, "UPDATE pharmacist_profile SET profile_photo=? WHERE id=?", "si", [$relPath, $pharm_pk]);
        dbExecute($conn, "INSERT INTO pharmacist_profile_completeness (pharmacist_id, photo_uploaded) VALUES (?,1) ON DUPLICATE KEY UPDATE photo_uploaded=1", "i", [$pharm_pk]);
        updateCompleteness($conn, $pharm_pk);
        secureLog($conn, $pharm_pk, 'Uploaded profile photo', 'profile');
        echo json_encode(['success'=>true,'message'=>'Photo uploaded','photo_url'=>'/RMU-Medical-Management-System/' . $relPath]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Upload failed']);
    }
    break;

case 'add_qualification':
    $degree = trim($input['degree_name'] ?? '');
    $inst   = trim($input['institution'] ?? '');
    if (!$degree || !$inst) { echo json_encode(['success'=>false,'message'=>'Degree and institution required']); exit; }
    $year = validateInt($input['year_awarded'] ?? 0, 1950, 2030);
    $filePath = null;
    if (!empty($_FILES['cert_file']) && $_FILES['cert_file']['error'] === UPLOAD_ERR_OK) {
        $vld = validateUpload($_FILES['cert_file'], ['application/pdf','image/jpeg','image/png'], 5242880);
        if (!$vld['valid']) { echo json_encode(['success'=>false,'message'=>$vld['error']]); exit; }
        $dir = __DIR__ . '/../../uploads/pharmacist_certs';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $fn = 'qual_' . $pharm_pk . '_' . time() . '.' . pathinfo($_FILES['cert_file']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['cert_file']['tmp_name'], "$dir/$fn");
        $filePath = "uploads/pharmacist_certs/$fn";
    }
    $qId = dbInsert($conn, "INSERT INTO pharmacist_qualifications (pharmacist_id, degree_name, institution, year_awarded, cert_file_path) VALUES (?,?,?,?,?)",
        "issis", [$pharm_pk, $degree, $inst, $year ?: null, $filePath]);
    if ($qId) {
        dbExecute($conn, "INSERT INTO pharmacist_profile_completeness (pharmacist_id, qualifications) VALUES (?,1) ON DUPLICATE KEY UPDATE qualifications=1", "i", [$pharm_pk]);
        updateCompleteness($conn, $pharm_pk);
        secureLog($conn, $pharm_pk, "Added qualification: $degree", 'profile');
        echo json_encode(['success'=>true,'message'=>"Qualification '$degree' added"]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Error']);
    }
    break;

case 'delete_qualification':
    $id = validateInt($input['id'] ?? 0, 1);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    if (!confirmAction('Delete this qualification?')) {}
    // Delete file if exists
    $row = dbRow($conn, "SELECT cert_file_path FROM pharmacist_qualifications WHERE id=? AND pharmacist_id=?", "ii", [$id, $pharm_pk]);
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
    if ($row['cert_file_path']) @unlink(__DIR__ . '/../../' . $row['cert_file_path']);
    dbExecute($conn, "DELETE FROM pharmacist_qualifications WHERE id=? AND pharmacist_id=?", "ii", [$id, $pharm_pk]);
    secureLog($conn, $pharm_pk, "Deleted qualification #$id", 'profile');
    echo json_encode(['success'=>true,'message'=>'Qualification deleted']);
    break;

case 'add_certification':
    $name = trim($input['cert_name'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'message'=>'Certification name required']); exit; }
    $issuer   = trim($input['issuing_body'] ?? '');
    $issueD   = trim($input['issue_date'] ?? '');
    $expiryD  = trim($input['expiry_date'] ?? '');
    $filePath = null;
    if (!empty($_FILES['cert_file']) && $_FILES['cert_file']['error'] === UPLOAD_ERR_OK) {
        $vld = validateUpload($_FILES['cert_file'], ['application/pdf','image/jpeg','image/png'], 5242880);
        if (!$vld['valid']) { echo json_encode(['success'=>false,'message'=>$vld['error']]); exit; }
        $dir = __DIR__ . '/../../uploads/pharmacist_certs';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $fn = 'cert_' . $pharm_pk . '_' . time() . '.' . pathinfo($_FILES['cert_file']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['cert_file']['tmp_name'], "$dir/$fn");
        $filePath = "uploads/pharmacist_certs/$fn";
    }
    $issueDVal  = ($issueD  && validateDate($issueD))  ? $issueD  : null;
    $expiryDVal = ($expiryD && validateDate($expiryD)) ? $expiryD : null;
    $cId = dbInsert($conn, "INSERT INTO pharmacist_certifications (pharmacist_id, cert_name, issuing_body, issue_date, expiry_date, cert_file_path) VALUES (?,?,?,?,?,?)",
        "isssss", [$pharm_pk, $name, $issuer, $issueDVal, $expiryDVal, $filePath]);
    if ($cId) {
        // Expiry alert
        if ($expiryDVal) {
            $dLeft = (int)((strtotime($expiryDVal) - time()) / 86400);
            if ($dLeft <= 60 && $dLeft > 0) {
                secureNotify($conn, $user_id, "⚠️ Certification '$name' expires in $dLeft days.", 'alert', 'profile');
            }
        }
        secureLog($conn, $pharm_pk, "Added certification: $name", 'profile');
        echo json_encode(['success'=>true,'message'=>"Certification '$name' added"]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Error']);
    }
    break;

case 'delete_certification':
    $id = validateInt($input['id'] ?? 0, 1);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $row = dbRow($conn, "SELECT cert_file_path FROM pharmacist_certifications WHERE id=? AND pharmacist_id=?", "ii", [$id, $pharm_pk]);
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
    if ($row['cert_file_path']) @unlink(__DIR__ . '/../../' . $row['cert_file_path']);
    dbExecute($conn, "DELETE FROM pharmacist_certifications WHERE id=? AND pharmacist_id=?", "ii", [$id, $pharm_pk]);
    secureLog($conn, $pharm_pk, "Deleted certification #$id", 'profile');
    echo json_encode(['success'=>true,'message'=>'Certification deleted']);
    break;

case 'upload_document':
    if (empty($_FILES['document'])) { echo json_encode(['success'=>false,'message'=>'No file received']); exit; }
    $vld = validateUpload($_FILES['document'], ['application/pdf','image/jpeg','image/png'], 5242880);
    if (!$vld['valid']) { echo json_encode(['success'=>false,'message'=>$vld['error']]); exit; }
    $dir = __DIR__ . '/../../uploads/pharmacist_docs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $origName = basename($_FILES['document']['name']);
    $ext = pathinfo($origName, PATHINFO_EXTENSION);
    $safeName = 'doc_' . $pharm_pk . '_' . time() . '.' . $ext;
    $dest = "$dir/$safeName";
    if (move_uploaded_file($_FILES['document']['tmp_name'], $dest)) {
        $relPath = "uploads/pharmacist_docs/$safeName";
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($dest);
        dbInsert($conn, "INSERT INTO pharmacist_documents (pharmacist_id, file_name, file_path, file_type, file_size) VALUES (?,?,?,?,?)",
            "isssi", [$pharm_pk, $origName, $relPath, $mime, (int)$_FILES['document']['size']]);
        dbExecute($conn, "INSERT INTO pharmacist_profile_completeness (pharmacist_id, documents_uploaded) VALUES (?,1) ON DUPLICATE KEY UPDATE documents_uploaded=1", "i", [$pharm_pk]);
        updateCompleteness($conn, $pharm_pk);
        secureLog($conn, $pharm_pk, "Uploaded document: $origName", 'profile');
        echo json_encode(['success'=>true,'message'=>"Document '$origName' uploaded"]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Upload failed']);
    }
    break;

case 'delete_document':
    $id = validateInt($input['id'] ?? 0, 1);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $row = dbRow($conn, "SELECT file_path FROM pharmacist_documents WHERE id=? AND pharmacist_id=?", "ii", [$id, $pharm_pk]);
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
    @unlink(__DIR__ . '/../../' . $row['file_path']);
    dbExecute($conn, "DELETE FROM pharmacist_documents WHERE id=? AND pharmacist_id=?", "ii", [$id, $pharm_pk]);
    secureLog($conn, $pharm_pk, "Deleted document #$id", 'profile');
    echo json_encode(['success'=>true,'message'=>'Document deleted']);
    break;

case 'request_deactivation':
    secureNotifyAdmins($conn, "🔴 Pharmacist (ID: $pharm_pk, User: $user_id) has requested account deactivation. Please review.", 'security', 'pharmacy');
    secureLog($conn, $pharm_pk, 'Requested account deactivation', 'security');
    echo json_encode(['success'=>true,'message'=>'Deactivation request sent to admin. Your data is preserved.']);
    break;

// ════════════════════════════════════════════════════════════
// MODULE 10: SYSTEM SETTINGS
// ════════════════════════════════════════════════════════════
case 'save_system_settings':
    $settings = $input['settings'] ?? [];
    if (empty($settings) || !is_array($settings)) { echo json_encode(['success'=>false,'message'=>'No settings provided']); exit; }
    $allowed = ['system_name','timezone','date_format','currency','currency_symbol',
                'default_reorder_level','expiry_warning_days','stock_alert_threshold',
                'session_timeout','password_change_days','max_login_attempts','default_theme','system_language'];
    $count = 0;
    foreach ($settings as $key => $val) {
        if (!in_array($key, $allowed)) continue;
        $existing = dbVal($conn, "SELECT COUNT(*) FROM system_settings WHERE setting_key=?", "s", [$key]);
        if ($existing) {
            dbExecute($conn, "UPDATE system_settings SET setting_value=?, updated_by=? WHERE setting_key=?", "sis", [$val, $user_id, $key]);
        } else {
            dbExecute($conn, "INSERT INTO system_settings (setting_key, setting_value, updated_by) VALUES (?,?,?)", "ssi", [$key, $val, $user_id]);
        }
        $count++;
    }
    secureLog($conn, $pharm_pk, "Updated $count system settings", 'settings');
    echo json_encode(['success'=>true,'message'=>"$count settings saved"]);
    break;

default:
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
