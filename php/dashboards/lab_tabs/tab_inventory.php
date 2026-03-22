<?php
// ============================================================
// LAB DASHBOARD - TAB INVENTORY (Module 7)
// ============================================================
if (!isset($user_id)) { exit; }

$today = date('Y-m-d');
$thirty_days = date('Y-m-d', strtotime('+30 days'));

$query = "SELECT * FROM reagent_inventory ORDER BY status ASC, name ASC";
$inv_res = mysqli_query($conn, $query);

function getInvStatusBadge($s, $qty, $min, $exp) {
    global $today, $thirty_days;
    
    // Calculate derived statuses overrides
    if ($exp && $exp < $today) return '<span class="adm-badge" style="background:var(--danger);color:white;"><i class="fas fa-biohazard"></i> Expired</span>';
    if ($exp && $exp <= $thirty_days) return '<span class="adm-badge adm-badge-warning"><i class="fas fa-hourglass-half"></i> Expiring Soon</span>';
    if ($qty <= 0) return '<span class="adm-badge adm-badge-danger">Out of Stock</span>';
    if ($qty <= $min) return '<span class="adm-badge adm-badge-warning">Low Stock</span>';
    
    return '<span class="adm-badge adm-badge-success">In Stock</span>';
}

function getStockBar($qty, $min, $max) {
    $max = $max > 0 ? $max : ($qty > 0 ? $qty * 2 : 100); // Failsafe
    $percent = min(100, max(0, ($qty / $max) * 100));
    $color = 'var(--success)';
    if ($qty <= $min) $color = 'var(--warning)';
    if ($qty <= ($min / 2)) $color = 'var(--danger)';
    
    return "
    <div style='display:flex; align-items:center; gap:10px; font-size: 0.9em;'>
        <div style='width: 100px; height: 8px; background: var(--surface-2); border-radius: 4px; overflow: hidden;'>
            <div style='width: {$percent}%; height: 100%; background: {$color}; border-radius: 4px;'></div>
        </div>
        <span style='font-weight: 600; color: {$color};'>{$qty}</span>
    </div>";
}
function getDaysRemaining($conn, $item_id, $current_qty) {
    // Look at how many units were deducted from this item in the last 30 days
    $q = mysqli_query($conn, "
        SELECT SUM(ABS(CAST(new_value AS DECIMAL(10,2)) - CAST(old_value AS DECIMAL(10,2)))) AS total_used
        FROM lab_audit_trail
        WHERE module_affected = 'Reagent Inventory' 
          AND record_id_affected = $item_id
          AND action_type LIKE '%Deduct%'
          AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    if(!$q) return null;
    $row = mysqli_fetch_assoc($q);
    $total_used = (float)($row['total_used'] ?? 0);
    if ($total_used <= 0) return null; // Insufficient data
    $avg_daily = $total_used / 30.0;
    return (int)ceil($current_qty / $avg_daily);
}
?>

<div class="sec-header">
    <h2><i class="fas fa-boxes"></i> Reagent Inventory</h2>
    <div style="display:flex; gap:1rem;">
        <button class="adm-btn adm-btn-teal" onclick="logUsageModal()"><i class="fas fa-minus-circle"></i> Log Usage</button>
        <button class="adm-btn adm-btn-primary" onclick="addStockModal()"><i class="fas fa-plus-circle"></i> Add New Stock</button>
    </div>
</div>

<div class="adm-table-wrap" style="background: var(--surface); padding: 1.5rem;">
    <table id="inventoryTable" class="adm-table display" style="font-size: 1.05rem;">
        <thead>
            <tr>
                <th>Item ID</th>
                <th>Reagent Name</th>
                <th>Supplier</th>
                <th>Stock Level</th>
                <th>Storage</th>
                <th>Expiry Date</th>
                <th>Est. Days Left</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($inv_res)): ?>
            <tr>
                <td><strong><?= e($row['item_code']) ?></strong></td>
                <td>
                    <strong><?= e($row['name']) ?></strong><br>
                    <small style="color:var(--text-secondary);">Batch: <?= e($row['batch_number']) ?: 'N/A' ?></small>
                </td>
                <td><?= e($row['supplier']) ?></td>
                <td>
                    <?= getStockBar($row['quantity_in_stock'], $row['reorder_level'], $row['quantity_in_stock'] * 3) ?>
                    <small style="color:var(--text-muted);">Reorder at: <?= e($row['reorder_level']) ?> <?= e($row['unit']) ?></small>
                </td>
                <td><?= e($row['storage_conditions']) ?></td>
                <td>
                    <?php if($row['expiry_date']): ?>
                        <span style="<?= ($row['expiry_date'] < $today) ? 'color:var(--danger); font-weight:600;' : (($row['expiry_date'] <= $thirty_days) ? 'color:var(--warning); font-weight:600;' : '') ?>">
                            <?= date('d M Y', strtotime($row['expiry_date'])) ?>
                        </span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= getInvStatusBadge($row['status'], $row['quantity_in_stock'], $row['reorder_level'], $row['expiry_date']) ?></td>
                <td><?php
                    // Phase 8: Reagent Consumption Forecasting
                    $days = getDaysRemaining($conn, $row['id'], $row['quantity_in_stock']);
                    if ($days === null) {
                        echo '<span style="color:var(--text-muted); font-size:0.85em;">No usage data</span>';
                    } elseif ($days <= 3) {
                        echo "<span style='color:var(--danger); font-weight:700;'><i class='fas fa-fire'></i> {$days}d (CRITICAL)</span>";
                    } elseif ($days <= 7) {
                        echo "<span style='color:var(--warning); font-weight:600;'><i class='fas fa-exclamation'></i> {$days}d</span>";
                    } else {
                        echo "<span style='color:var(--success);'><i class='fas fa-check'></i> ~{$days}d</span>";
                    }
                ?></td>
                <td><?= getInvStatusBadge($row['status'], $row['quantity_in_stock'], $row['reorder_level'], $row['expiry_date']) ?></td>
                <td>
                    <div class="action-btns">
                        <button class="adm-btn adm-btn-sm" style="background:var(--surface-2);" onclick="viewInv(<?= $row['id'] ?>)"><i class="fas fa-eye"></i></button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal Placeholders -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h5 class="modal-title"><i class="fas fa-plus-circle" style="color:var(--primary);"></i> Add Incoming Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body">
                <p>Register newly received reagents or consumables here.</p>
                <div class="form-group">
                    <label>Reagent Name</label>
                    <input type="text" class="form-control">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Quantity Received</label>
                        <input type="number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Batch Number</label>
                    <input type="text" class="form-control">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-sm" style="background:var(--surface-2);" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" onclick="alert('Save action triggered')">Save Stock Addition</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#inventoryTable').DataTable({
        pageLength: 15,
        language: { search: "", searchPlaceholder: "Search reagents, batches..." }
    });
});

function logUsageModal() {
    alert("Open 'Log Reagent Usage' Modal. Will deduct from `quantity_in_stock` via AJAX.");
}

function addStockModal() {
    new bootstrap.Modal(document.getElementById('addStockModal')).show();
}

function viewInv(id) {
    alert("Viewing transaction history for Inventory ID " + id);
}
</script>
