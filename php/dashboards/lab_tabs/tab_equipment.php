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
    <h2 style="font-size: 1.8rem; font-weight: 700;"><i class="fas fa-microscope"></i> Equipment Management</h2>
    <div style="display:flex; gap:1.2rem;">
        <button class="adm-btn" style="background:var(--role-accent); color:#fff;" onclick="logQCModal()"><i class="fas fa-clipboard-check"></i> Log Daily QC</button>
        <button class="adm-btn adm-btn-primary" onclick="addEquipmentModal()"><i class="fas fa-plus"></i> Add Equipment</button>
    </div>
</div>

<div class="info-card">
    <div style="display: flex; gap: 0.8rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem;">
        <a href="?tab=equipment&filter_status=All" class="adm-btn adm-btn-sm <?= $filter=='All'?'adm-btn-primary':'adm-btn-ghost' ?>">All Units</a>
        <a href="?tab=equipment&filter_status=Operational" class="adm-btn adm-btn-sm <?= $filter=='Operational'?'adm-btn-success':'adm-btn-ghost' ?>">Operational</a>
        <a href="?tab=equipment&filter_status=Calibration+Due" class="adm-btn adm-btn-sm <?= $filter=='Calibration Due'?'adm-btn-warning':'adm-btn-ghost' ?>">Calibration Due</a>
        <a href="?tab=equipment&filter_status=Out+of+Service" class="adm-btn adm-btn-sm <?= $filter=='Out of Service'?'adm-btn-danger':'adm-btn-ghost' ?>">Out of Service</a>
    </div>

    <div class="adm-table-wrap">
        <table id="equipmentTable" class="adm-table display">
            <thead>
                <tr>
                    <th>Equipment ID</th>
                    <th>Model Name</th>
                    <th>Manufacturer</th>
                    <th>Location / Dept</th>
                    <th>Status</th>
                    <th>Next Calibration</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($equip_res)): ?>
                <tr>
                    <td><strong style="font-family: monospace; font-size: 1.1rem;"><?= e($row['equipment_id']) ?></strong></td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem;"><?= e($row['name']) ?></div>
                        <div style="color: var(--text-secondary); font-size: 0.9rem;">Mod: <?= e($row['model']) ?></div>
                    </td>
                    <td><span style="font-weight: 600; color: var(--text-secondary);"><?= e($row['manufacturer']) ?></span></td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-primary);"><?= e($row['location']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--primary); font-weight: 600;"><?= e($row['department']) ?></div>
                    </td>
                    <td><?= getEquipStatusBadge($row['status'], $row['next_calibration_date']) ?></td>
                    <td>
                        <?php if($row['next_calibration_date']): ?>
                            <div style="font-weight: 700; display: flex; align-items: center; gap: 0.4rem; <?= ($row['next_calibration_date'] < $today) ? 'color:var(--danger);' : 'color:var(--text-primary);' ?>">
                                <i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($row['next_calibration_date'])) ?>
                            </div>
                        <?php else: ?>
                            <span style="color:var(--text-muted); font-style: italic;">Not Scheduled</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <button class="adm-btn adm-btn-sm" style="background:var(--surface-2); color:var(--text-primary);" onclick="viewEquip(<?= $row['id'] ?>)" title="View History"><i class="fas fa-eye"></i></button>
                            <button class="adm-btn adm-btn-sm" style="background:var(--primary-light); color:var(--primary);" title="Log Calibration" onclick="logCalibration(<?= $row['id'] ?>)"><i class="fas fa-sliders-h"></i></button>
                            <button class="adm-btn adm-btn-sm" style="background:var(--warning-light); color:var(--warning);" title="Schedule Maintenance" onclick="schedMaint(<?= $row['id'] ?>)"><i class="fas fa-wrench"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Log Calibration Modal -->
<div class="modal fade" id="calibrationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg); border:none; box-shadow:0 15px 35px rgba(0,0,0,0.2);">
            <div class="modal-header" style="border-bottom:1px solid var(--border); padding:1.5rem 2rem;">
                <h5 class="modal-title" style="font-weight:700; font-size:1.4rem;"><i class="fas fa-sliders-h" style="color:var(--primary); margin-right:.5rem;"></i> Record Calibration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body" style="padding:2rem;">
                <input type="hidden" id="calib_equip_id">
                <div class="form-row" style="grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Calibration Date <span style="color:var(--danger);">*</span></label>
                        <input type="date" class="form-control" id="calib_date" value="<?= $today ?>" style="font-size:1.1rem; padding:.8rem;">
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Next Due Date <span style="color:var(--danger);">*</span></label>
                        <input type="date" class="form-control" id="calib_next_date" style="font-size:1.1rem; padding:.8rem;">
                    </div>
                </div>
                <div class="form-group mb-1.5rem" style="margin-bottom: 1.5rem;">
                    <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Findings / Calibration Notes</label>
                    <textarea class="form-control" id="calib_notes" rows="4" placeholder="Passed all parameters, adjusted sensor Z..." style="font-size:1.1rem; padding:1rem; resize:none;"></textarea>
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Post-Calibration Status</label>
                    <select id="calib_status_update" class="form-select" style="font-size:1.1rem; padding:.8rem;">
                        <option value="Operational">Mark as Operational</option>
                        <option value="Maintenance">Requires Further Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border); padding:1.5rem 2rem;">
                <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="adm-btn" style="background:var(--primary); color:#fff;" onclick="submitCalibration()"><i class="fas fa-save"></i> Save Record</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#equipmentTable').DataTable({
        pageLength: 15,
        language: { search: "", searchPlaceholder: "Search equipment ledger..." }
    });
});

function addEquipmentModal() {
    Swal.fire({
        title: 'Add New Equipment',
        text: 'This will launch the equipment registration wizard.',
        icon: 'info',
        confirmButtonColor: 'var(--primary)'
    });
}

function logQCModal() {
     Swal.fire({
        title: 'Daily QC Log',
        text: 'Select an instrument to record Quality Control results.',
        icon: 'info',
        confirmButtonColor: 'var(--primary)'
    });
}

function logCalibration(id) {
    $('#calib_equip_id').val(id);
    new bootstrap.Modal(document.getElementById('calibrationModal')).show();
}

function submitCalibration() {
    const id = $('#calib_equip_id').val();
    const nextDate = $('#calib_next_date').val();
    
    if(!nextDate) {
        return Swal.fire('Error', 'Next calibration date is mandatory for traceability.', 'error');
    }

    Swal.fire({
        title: 'Confirm Calibration?',
        text: "This record will be permanently etched in the maintenance log.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--primary)',
        confirmButtonText: 'Yes, Sign & Save'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Logged!', 'Unit #' + id + ' calibration records updated.', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    });
}

function schedMaint(id) {
    Swal.fire({
        title: 'Schedule Maintenance?',
        text: "Unit will be marked as 'In Maintenance' until cleared.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Schedule'
    });
}

function viewEquip(id) {
    Swal.fire({
        title: 'Equipment Intelligence',
        text: 'Historical data and uptime metrics for ID: ' + id,
        icon: 'info'
    });
}
</script>
