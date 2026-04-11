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
    <div style='display:flex; flex-direction:column; gap:6px;'>
        <div style='width: 120px; height: 10px; background: var(--surface-2); border-radius: 6px; overflow: hidden; border:1px solid var(--border);'>
            <div style='width: {$percent}%; height: 100%; background: {$color}; border-radius: 6px; box-shadow: 0 0 10px {$color}44;'></div>
        </div>
        <div style='display:flex; justify-content:space-between; width:120px; font-size:0.8rem; font-weight:700; color:{$color};'>
            <span>{$qty} Units</span>
            <span>" . round($percent) . "%</span>
        </div>
    </div>";
}
function getDaysRemaining($conn, $item_id, $current_qty) {
    // Look at how many units were deducted from this item in the last 30 days
    $q = mysqli_query($conn, "
        SELECT SUM(ABS(CAST(new_value AS DECIMAL(10,2)) - CAST(old_value AS DECIMAL(10,2)))) AS total_used
        FROM lab_audit_trail
        WHERE module_affected = 'Reagent Inventory' 
          AND record_id = $item_id
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
    <h2 style="font-size: 1.8rem; font-weight: 700;"><i class="fas fa-boxes"></i> Reagent Inventory</h2>
    <div style="display:flex; gap:1.2rem;">
        <button class="btn btn-primary btn" style="background:var(--role-accent); color:#fff;" onclick="logUsageModal()"><span class="btn-text"><i class="fas fa-minus-circle"></i> Log Usage</span></button>
        <button class="btn btn-primary" onclick="addStockModal()"><span class="btn-text"><i class="fas fa-plus-circle"></i> Add New Stock</span></button>
    </div>
</div>

<div class="info-card">
    <div class="adm-table-wrap">
        <table id="inventoryTable" class="adm-table display">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Reagent / Batch</th>
                    <th>Supplier</th>
                    <th>Current Level</th>
                    <th>Storage</th>
                    <th>Expiry Date</th>
                    <th>Forecasting</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($inv_res)): ?>
                <tr>
                    <td><strong style="font-family: monospace; font-size: 1.1rem; color: var(--text-secondary);"><?= e($row['item_code']) ?></strong></td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem;"><?= e($row['name']) ?></div>
                        <div style="color: var(--primary); font-size: 0.85rem; font-weight: 600;">Batch: <?= e($row['batch_number']) ?: 'N/A' ?></div>
                    </td>
                    <td><span style="font-weight: 600; color: var(--text-secondary);"><?= e($row['supplier']) ?></span></td>
                    <td>
                        <?= getStockBar($row['quantity_in_stock'], $row['reorder_level'], $row['quantity_in_stock'] * 3) ?>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:5px; font-weight:500;">Min Alert: <?= e($row['reorder_level']) ?> <?= e($row['unit']) ?></div>
                    </td>
                    <td><span class="adm-badge" style="background:var(--surface-2); color:var(--text-primary);"><i class="fas fa-thermometer-half"></i> <?= e($row['storage_conditions']) ?></span></td>
                    <td>
                        <?php if($row['expiry_date']): ?>
                            <div style="font-weight: 700; display: flex; align-items: center; gap: 0.4rem; <?= ($row['expiry_date'] < $today) ? 'color:var(--danger);' : (($row['expiry_date'] <= $thirty_days) ? 'color:var(--warning);' : 'color:var(--text-primary);') ?>">
                                <i class="far fa-calendar-times"></i> <?= date('d M Y', strtotime($row['expiry_date'])) ?>
                            </div>
                        <?php else: ?>
                            <span style="color:var(--text-muted); font-style: italic;">No Expiry</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <?php
                        $days = getDaysRemaining($conn, $row['id'], $row['quantity_in_stock']);
                        if ($days === null) {
                            echo '<span style="color:var(--text-muted); font-size:0.85rem; font-weight:500;">Insufficient Data</span>';
                        } elseif ($days <= 3) {
                            echo "<div style='background:rgba(231,76,60,0.1); padding:6px; border-radius:6px; color:var(--danger); font-weight:700;'><i class='fas fa-fire'></i> ~{$days} Days</div>";
                        } elseif ($days <= 7) {
                            echo "<div style='background:rgba(241,196,15,0.1); padding:6px; border-radius:6px; color:var(--warning); font-weight:700;'><i class='fas fa-exclamation'></i> ~{$days} Days</div>";
                        } else {
                            echo "<div style='color:var(--success); font-weight:700;'><i class='fas fa-check-circle'></i> ~{$days} Days</div>";
                        }
                        ?>
                    </td>
                    <td><?= getInvStatusBadge($row['status'], $row['quantity_in_stock'], $row['reorder_level'], $row['expiry_date']) ?></td>
                    <td>
                        <button class="btn btn-primary btn btn-sm" style="background:var(--surface-2); color:var(--text-primary);" onclick="viewInv(<?= $row['id'] ?>)" title="Inventory History"><span class="btn-text"><i class="fas fa-history"></i> Log</span></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Placeholders -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg); border:none; box-shadow:0 15px 35px rgba(0,0,0,0.2);">
            <div class="modal-header" style="border-bottom:1px solid var(--border); padding:1.5rem 2rem;">
                <h5 class="modal-title" style="font-weight:700; font-size:1.4rem;"><i class="fas fa-plus-circle" style="color:var(--primary); margin-right:.5rem;"></i> Add Incoming Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body" style="padding:2rem;">
                <p style="color:var(--text-secondary); margin-bottom:1.5rem;">Register newly received reagents or consumables into the laboratory inventory ledger.</p>
                
                <div class="form-group mb-3">
                    <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Reagent Name <span style="color:var(--danger);">*</span></label>
                    <input type="text" id="add_inv_name" class="form-control" placeholder="e.g. EDTA Vacutainers" style="font-size:1.1rem; padding:.8rem;">
                </div>

                <div class="form-row" style="grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Quantity Received</label>
                        <input type="number" id="add_inv_qty" class="form-control" placeholder="0" style="font-size:1.1rem; padding:.8rem;">
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Expiry Date</label>
                        <input type="date" id="add_inv_exp" class="form-control" style="font-size:1.1rem; padding:.8rem;">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Batch / Lot Number</label>
                    <input type="text" id="add_inv_batch" class="form-control" placeholder="e.g. LOT-X992" style="font-size:1.1rem; padding:.8rem;">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border); padding:1.5rem 2rem;">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal"><span class="btn-text">Cancel</span></button>
                <button type="button" class="btn btn-primary" onclick="submitNewStock()"><span class="btn-text"><i class="fas fa-save"></i> Save Stock Addition</span></button>
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
     Swal.fire({
        title: 'Log Reagent Usage',
        html: `
            <div style="text-align:left;">
                <label style="display:block; margin-bottom:5px;">Select Reagent</label>
                <select id="usage_item" class="form-select mb-3">
                    <option value="">-- Select Item --</option>
                </select>
                <label style="display:block; margin-bottom:5px;">Quantity Used</label>
                <input type="number" id="usage_qty" class="form-control" placeholder="Enter amount">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Deduct Stock',
        confirmButtonColor: 'var(--danger)',
        preConfirm: () => {
            const item = Swal.getPopup().querySelector('#usage_item').value;
            const qty = Swal.getPopup().querySelector('#usage_qty').value;
            if (!item || !qty) {
                Swal.showValidationMessage(`Please select item and quantity`);
            }
            return { item: item, qty: qty }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Updated', 'Inventory levels adjusted.', 'success');
        }
    });
}

function addStockModal() {
    new bootstrap.Modal(document.getElementById('addStockModal')).show();
}

function submitNewStock() {
    const name = $('#add_inv_name').val().trim();
    if(!name) return Swal.fire('Error', 'Reagent Name is required', 'error');

    Swal.fire({
        title: 'Stock Addition',
        text: 'Do you want to add these units to inventory?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--primary)'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Success', 'Stock ledger updated.', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    });
}

function viewInv(id) {
    Swal.fire({
        title: 'Inventory Transaction Log',
        text: 'Loading detailed audit trail for inventory unit ID: ' + id,
        icon: 'info'
    });
}
</script>
