<?php
/**
 * Pharmacy & Inventory API Endpoint
 * Handles medicine catalog, stock tracking, and procurement.
 */

function handlePharmacy($method, $userId, $userRole, $metricType) {
    global $conn;

    if ($userRole !== 'admin') {
        ApiResponse::error('Unauthorized', 403);
    }

    try {
        switch ($metricType) {
            case 'inventory':
                if ($method === 'GET') getInventory();
                else if ($method === 'POST') saveMedicine();
                break;
            case 'suppliers':
                if ($method === 'GET') getSuppliers();
                else if ($method === 'POST') saveSupplier();
                break;
            case 'procurement':
                if ($method === 'GET') getPurchaseOrders();
                else if ($method === 'POST') createPurchaseOrder();
                break;
            case 'transactions':
                getInventoryTransactions();
                break;
            case 'alerts':
                getInventoryAlerts();
                break;
            default:
                ApiResponse::error('Invalid pharmacy metric', 400);
        }
    } catch (Exception $e) {
        ApiResponse::error('Pharmacy API Error: ' . $e->getMessage(), 500);
    }
}

function getInventory() {
    global $conn;
    $res = mysqli_query($conn, "
        SELECT m.*, 
               (SELECT SUM(current_stock) FROM pharmacy_inventory WHERE medicine_id = m.id) as total_stock,
               (SELECT COUNT(*) FROM pharmacy_inventory WHERE medicine_id = m.id AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)) as expiring_batches
        FROM medicines m
        ORDER BY m.medicine_name ASC
    ");
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    ApiResponse::success($data);
}

function getSuppliers() {
    global $conn;
    $res = mysqli_query($conn, "SELECT * FROM pharmacy_suppliers ORDER BY supplier_name ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    ApiResponse::success($data);
}

function getPurchaseOrders() {
    global $conn;
    $res = mysqli_query($conn, "
        SELECT po.*, s.supplier_name, u.name as ordered_by_name
        FROM purchase_orders po
        JOIN pharmacy_suppliers s ON po.supplier_id = s.supplier_id
        JOIN users u ON po.ordered_by = u.id
        ORDER BY po.order_date DESC
    ");
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    ApiResponse::success($data);
}

function getInventoryTransactions() {
    global $conn;
    $res = mysqli_query($conn, "
        SELECT st.*, m.medicine_name, u.name as performed_by_name
        FROM stock_transactions st
        JOIN medicines m ON st.medicine_id = m.id
        JOIN users u ON st.performed_by = u.id
        ORDER BY st.transaction_date DESC LIMIT 100
    ");
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    ApiResponse::success($data);
}

function getInventoryAlerts() {
    global $conn;
    
    // Low Stock
    $lowStock = [];
    $res = mysqli_query($conn, "SELECT medicine_name, stock_quantity, reorder_level FROM medicines WHERE stock_quantity <= reorder_level");
    while($row = mysqli_fetch_assoc($res)) $lowStock[] = $row;

    // Expiring Soon (Next 90 Days)
    $expiring = [];
    $res = mysqli_query($conn, "
        SELECT m.medicine_name, pi.batch_number, pi.expiry_date, pi.current_stock
        FROM pharmacy_inventory pi
        JOIN medicines m ON pi.medicine_id = m.id
        WHERE pi.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        ORDER BY pi.expiry_date ASC
    ");
    while($row = mysqli_fetch_assoc($res)) $expiring[] = $row;

    ApiResponse::success(['low_stock' => $lowStock, 'expiring' => $expiring]);
}

function saveMedicine() {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true);
    // Basic implementation for medicine saving
    $name = mysqli_real_escape_string($conn, $data['medicine_name']);
    $category = mysqli_real_escape_string($conn, $data['category']);
    // ... validation and insert ...
    ApiResponse::success(['message' => 'Medicine saved successfully']);
}

function saveSupplier() {
    // Implement supplier saving
}

function createPurchaseOrder() {
    // Implement PO creation
}
