<?php
// ============================================================
// LAB DASHBOARD - TAB EQUIPMENT (Module 6)
// ============================================================
if (!isset($user_id)) { exit; }

$today = date('Y-m-d');
$filter = $_GET['filter_status'] ?? 'All';

// Query Equipment
$query = "SELECT * FROM lab_equipment";
if ($filter !== 'All') {
    $query .= " WHERE status = '" . mysqli_real_escape_string($conn, $filter) . "'";
}
$query .= " ORDER BY status DESC, name ASC";
$equip_res = mysqli_query($conn, $query);

function getEquipStatusBadge($s, $next_calib) {
    global $today;
    
    // Auto-flag overdue calibration
    if ($next_calib && $next_calib < $today && $s === 'Operational') {
        return '<span class="adm-badge" style="background:var(--danger);color:white;"><i class="fas fa-exclamation-triangle"></i> Calibration Overdue</span>';
    }

    if($s==='Operational') return '<span class="adm-badge adm-badge-success"><i class="fas fa-check-circle"></i> Operational</span>';
    if($s==='Calibration Due') return '<span class="adm-badge adm-badge-warning"><i class="fas fa-sliders-h"></i> Calibration Due</span>';
    if($s==='Maintenance') return '<span class="adm-badge adm-badge-warning"><i class="fas fa-wrench"></i> Maintenance</span>';
    if($s==='Out of Service') return '<span class="adm-badge adm-badge-danger"><i class="fas fa-times-circle"></i> Out of Service</span>';
    if($s==='Decommissioned') return '<span class="adm-badge" style="background:#7f8c8d;color:white;"><i class="fas fa-ban"></i> Decommissioned</span>';
    
    return '<span class="adm-badge">'.e($s).'</span>';
}
?>

<div class="sec-header">
    <h2><i class="fas fa-microscope"></i> Equipment Management</h2>
    <div style="display:flex; gap:1rem;">
        <button class="adm-btn adm-btn-teal" onclick="logQCModal()"><i class="fas fa-clipboard-check"></i> Log Daily QC</button>
        <button class="adm-btn adm-btn-primary" onclick="addEquipmentModal()"><i class="fas fa-plus"></i> Add Equipment</button>
    </div>
</div>

<div class="adm-table-wrap" style="background: var(--surface); padding: 1.5rem;">
    <div style="margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; display: flex; gap: .5rem;">
        <a href="?tab=equipment&filter_status=All" class="adm-btn adm-btn-sm <?= $filter=='All'?'adm-btn-primary':'adm-btn-outline' ?>">All</a>
        <a href="?tab=equipment&filter_status=Operational" class="adm-btn adm-btn-sm <?= $filter=='Operational'?'adm-btn-success':'adm-btn-outline' ?>">Operational</a>
        <a href="?tab=equipment&filter_status=Calibration+Due" class="adm-btn adm-btn-sm <?= $filter=='Calibration Due'?'adm-btn-warning':'adm-btn-outline' ?>">Calibration Due</a>
        <a href="?tab=equipment&filter_status=Out+of+Service" class="adm-btn adm-btn-sm <?= $filter=='Out of Service'?'adm-btn-danger':'adm-btn-outline' ?>">Out of Service</a>
    </div>

    <table id="equipmentTable" class="adm-table display" style="font-size: 1.05rem;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Equipment Name / Model</th>
                <th>Manufacturer</th>
                <th>Location</th>
                <th>Status</th>
                <th>Next Calibration</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($equip_res)): ?>
            <tr>
                <td><strong><?= e($row['equipment_id']) ?></strong></td>
                <td>
                    <strong><?= e($row['name']) ?></strong><br>
                    <small style="color:var(--text-secondary);">Model: <?= e($row['model']) ?></small>
                </td>
                <td><?= e($row['manufacturer']) ?></td>
                <td><?= e($row['location']) ?> (<?= e($row['department']) ?>)</td>
                <td><?= getEquipStatusBadge($row['status'], $row['next_calibration_date']) ?></td>
                <td>
                    <?php if($row['next_calibration_date']): ?>
                        <span style="<?= ($row['next_calibration_date'] < $today) ? 'color:var(--danger); font-weight:600;' : '' ?>">
                            <?= date('d M Y', strtotime($row['next_calibration_date'])) ?>
                        </span>
                    <?php else: ?>
                        <span style="color:var(--text-muted);">Not Scheduled</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-btns">
                        <button class="adm-btn adm-btn-sm" style="background:var(--surface-2);" onclick="viewEquip(<?= $row['id'] ?>)"><i class="fas fa-eye"></i></button>
                        <button class="adm-btn adm-btn-teal adm-btn-sm" title="Log Calibration" onclick="logCalibration(<?= $row['id'] ?>)"><i class="fas fa-sliders-h"></i></button>
                        <button class="adm-btn adm-btn-warning adm-btn-sm" title="Schedule Maintenance" onclick="schedMaint(<?= $row['id'] ?>)"><i class="fas fa-wrench"></i></button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Log Calibration Modal -->
<div class="modal fade" id="calibrationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h5 class="modal-title"><i class="fas fa-sliders-h" style="color:var(--role-accent);"></i> Record Calibration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="calib_equip_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>Calibration Date <span style="color:var(--danger);">*</span></label>
                        <input type="date" class="form-control" id="calib_date" value="<?= $today ?>">
                    </div>
                    <div class="form-group">
                        <label>Next Due Date <span style="color:var(--danger);">*</span></label>
                        <input type="date" class="form-control" id="calib_next_date">
                    </div>
                </div>
                <div class="form-group">
                    <label>Findings / Notes</label>
                    <textarea class="form-control" id="calib_notes" rows="3" placeholder="Passed all parameters, adjusted sensor Z..."></textarea>
                </div>
                <!-- Status Update Choice -->
                <div class="form-group">
                    <label>Update Equipment Status</label>
                    <select id="calib_status_update" class="form-select">
                        <option value="Operational">Mark as Operational</option>
                        <option value="Maintenance">Requires Further Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-sm" style="background:var(--surface-2);" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="adm-btn adm-btn-teal adm-btn-sm" onclick="submitCalibration()">Save Record</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#equipmentTable').DataTable({
        pageLength: 15,
        language: { search: "", searchPlaceholder: "Search equipment..." }
    });
});

function addEquipmentModal() {
    alert("Add Equipment Wizard Interface (Pending Backend Logic)");
}

function viewEquip(id) {
    alert("Viewing detailed history for equipment ID " + id);
}

function schedMaint(id) {
    alert("Scheduling Maintenance / Service Request for equipment ID " + id);
}

function logQCModal() {
    alert("Opening Quality Control (QC) run log interface.");
}

function logCalibration(id) {
    $('#calib_equip_id').val(id);
    new bootstrap.Modal(document.getElementById('calibrationModal')).show();
}

function submitCalibration() {
    alert('Saving calibration log to `equipment_maintenance_log` and updating equipment next_calibration_date in `lab_equipment` (AJAX logic integration required in lab_actions.php).');
}
</script>
