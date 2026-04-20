<?php
// ============================================================
// LAB DASHBOARD - TAB EQUIPMENT (PREMIUM UI REWRITE)
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
        return '<span class="adm-badge" style="background:var(--danger);color:white;box-shadow:0 0 10px rgba(231,76,60,0.4);"><i class="fas fa-exclamation-triangle"></i> Calibration Overdue</span>';
    }

    if($s==='Operational') return '<span class="adm-badge" style="background:rgba(34,197,94,0.1); color:#22c55e;"><i class="fas fa-check-circle"></i> Operational</span>';
    if($s==='Calibration Due') return '<span class="adm-badge" style="background:rgba(245,158,11,0.1); color:#f59e0b;"><i class="fas fa-sliders-h"></i> Calibration Due</span>';
    if($s==='Maintenance') return '<span class="adm-badge" style="background:rgba(245,158,11,0.1); color:#f59e0b;"><i class="fas fa-wrench"></i> Maintenance Hold</span>';
    if($s==='Out of Service') return '<span class="adm-badge" style="background:rgba(244,63,94,0.1); color:#f43f5e;"><i class="fas fa-times-circle"></i> Out of Service</span>';
    if($s==='Decommissioned') return '<span class="adm-badge" style="background:var(--surface-3); color:var(--text-muted);"><i class="fas fa-ban"></i> Decommissioned</span>';
    
    return '<span class="adm-badge">'.e($s).'</span>';
}
?>

<div class="tab-content <?= ($active_tab === 'equipment') ? 'active' : '' ?>" id="equipment">

    <div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.01)); border:1px solid rgba(47,128,237,0.15); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.5rem;">
                <i class="fas fa-microscope" style="color:var(--primary); margin-right:.8rem;"></i> Clinical Equipment Fleet
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted); margin:0;">Monitor hardware health, execute calibration schedules, and maintain instrument compliance.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center; margin-top:1.5rem; flex-wrap:wrap;">
            <button class="adm-btn adm-btn-primary" onclick="document.getElementById('addEquipModal').style.display='flex';" style="border-radius:10px; font-weight:800;"><span class="btn-text"><i class="fas fa-plus"></i> Register Machine</span></button>
            <button class="adm-btn" onclick="document.getElementById('logQcModal').style.display='flex';" style="border-radius:10px; font-weight:800; background:var(--surface-1); color:var(--text-primary); border:2px dashed var(--border);"><span class="btn-text"><i class="fas fa-clipboard-check"></i> Log General QC</span></button>
        </div>
    </div>

    <!-- State Machine Metrics / Filters -->
    <div style="display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
        <a href="?tab=equipment&filter_status=All" class="adm-btn <?= $filter=='All'?'adm-adm-btn adm-btn-primary':'adm-btn-ghost' ?>" style="font-weight:700; border-radius:30px;"><span class="btn-text">Global Fleet</span></a>
        <a href="?tab=equipment&filter_status=Operational" class="adm-btn <?= $filter=='Operational'?'':'adm-btn-ghost' ?>" style="font-weight:700; border-radius:30px; <?= $filter=='Operational'?'background:#22c55e;color:white;':'' ?>"><span class="btn-text"><i class="fas fa-check-circle" style="margin-right:.5rem;"></i> Active</span></a>
        <a href="?tab=equipment&filter_status=Calibration+Due" class="adm-btn <?= $filter=='Calibration Due'?'':'adm-btn-ghost' ?>" style="font-weight:700; border-radius:30px; <?= $filter=='Calibration Due'?'background:#f59e0b;color:white;':'' ?>"><span class="btn-text"><i class="fas fa-sliders-h" style="margin-right:.5rem;"></i> Calibration Matrix</span></a>
        <a href="?tab=equipment&filter_status=Out+of+Service" class="adm-btn <?= $filter=='Out of Service'?'':'adm-btn-ghost' ?>" style="font-weight:700; border-radius:30px; <?= $filter=='Out of Service'?'background:#f43f5e;color:white;':'' ?>"><span class="btn-text"><i class="fas fa-times-circle" style="margin-right:.5rem;"></i> Out of Service</span></a>
    </div>

    <div class="adm-card shadow-sm" style="border-radius:16px;">
        <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-list-ul" style="color:var(--primary); margin-right:.5rem;"></i> Asset Ledger</h3>
        </div>
        <div class="adm-card-body" style="padding:1rem;">
            <div class="adm-table-wrap">
                <table id="equipmentTable" class="adm-table">
                    <thead>
                        <tr>
                            <th>Hardware Token</th>
                            <th>Instrument Target</th>
                            <th>Supplier Schema</th>
                            <th>Local Network</th>
                            <th>Systems State</th>
                            <th>Calibration Cycle</th>
                            <th>Control Vectors</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($equip_res)): ?>
                        <tr>
                            <td><strong style="color:var(--primary); font-family:monospace; font-size:1.2rem;"><?= e($row['serial_number'] ?: '#EQP-'.str_pad($row['id'], 3, '0', STR_PAD_LEFT)) ?></strong></td>
                            <td>
                                <div style="font-weight:800; color:var(--text-primary); font-size:1.2rem;"><?= e($row['name']) ?></div>
                                <div style="color:var(--text-muted); font-size:1rem; font-weight:600;"><i class="fas fa-microchip"></i> Mod: <?= e($row['model']) ?></div>
                            </td>
                            <td><span style="font-weight:600; color:var(--text-secondary);"><?= e($row['manufacturer']) ?></span></td>
                            <td>
                                <div style="font-weight:700; color:var(--text-primary);"><?= e($row['location']) ?></div>
                                <div style="font-size:.9rem; color:var(--primary); font-weight:800; text-transform:uppercase;"><?= e($row['department']) ?></div>
                            </td>
                            <td><?= getEquipStatusBadge($row['status'], $row['next_calibration_date']) ?></td>
                            <td>
                                <?php if($row['next_calibration_date']): ?>
                                    <div style="font-weight:700; display:flex; align-items:center; gap:0.4rem; <?= ($row['next_calibration_date'] < $today) ? 'color:var(--danger);' : 'color:var(--text-primary);' ?>">
                                        <i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($row['next_calibration_date'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-style:italic;">Not Specified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:.5rem;">
                                    <button class="adm-btn adm-btn-ghost btn-icon text-primary" title="Audit Telemetry" onclick="viewEquip(<?= $row['id'] ?>)"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
                                    <button class="adm-btn adm-btn-primary btn-sm" style="border-radius:8px;" title="Execute Calibration" onclick="logCalibration(<?= $row['id'] ?>)"><span class="btn-text"><i class="fas fa-sliders-h"></i> Sync</span></button>
                                    <button class="adm-btn btn-sm" style="background:var(--warning); color:#fff; border-radius:8px;" title="Maintenance Protocol" onclick="schedMaint(<?= $row['id'] ?>)"><span class="btn-text"><i class="fas fa-wrench"></i> Maint</span></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADD EQUIPMENT                       -->
<!-- ========================================== -->
<div class="modal-bg" id="addEquipModal">
    <div class="modal-box" style="max-width:750px; padding:0; overflow:hidden;">
        <div class="modal-header" style="background:linear-gradient(135deg,#1C3A6B,var(--primary)); padding:2rem 3rem; margin:0;">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-microchip"></i> Integrate New Instrument Vector</h3>
            <button class="adm-btn modal-close" onclick="document.getElementById('addEquipModal').style.display='none'" type="button" style="color:#fff; background:transparent; font-size:2rem; padding:0;"><span class="btn-text">&times;</span></button>
        </div>
        <div style="padding:3rem;">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Hardware ID <span style="color:var(--danger);">*</span></label>
                    <input type="text" id="add_equip_id" class="form-control" placeholder="e.g. LAB-CX-450" style="font-size:1.3rem; padding:1rem;" required>
                </div>
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Network Group</label>
                    <input type="text" id="add_equip_dept" class="form-control" value="Laboratory" style="font-size:1.3rem; padding:1rem;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Instrument Name <span style="color:var(--danger);">*</span></label>
                    <input type="text" id="add_equip_name" class="form-control" placeholder="Auto-Analyzer" style="font-size:1.3rem; padding:1rem;" required>
                </div>
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Firmware/Model <span style="color:var(--danger);">*</span></label>
                    <input type="text" id="add_equip_model" class="form-control" placeholder="X-Series v8" style="font-size:1.3rem; padding:1rem;" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2.5rem;">
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Supplier/Mfg</label>
                    <input type="text" id="add_equip_manuf" class="form-control" placeholder="Sysmex / Roche" style="font-size:1.3rem; padding:1rem;">
                </div>
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Zone</label>
                    <input type="text" id="add_equip_loc" class="form-control" placeholder="Main Lab Sector A" style="font-size:1.3rem; padding:1rem;">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('addEquipModal').style.display='none'" style="font-weight:700;"><span class="btn-text">Halt Sequence</span></button>
                <button type="button" class="adm-btn adm-btn-primary" style="border-radius:10px; font-weight:900;" onclick="submitAddEquipment()"><span class="btn-text"><i class="fas fa-plug" style="margin-right:.5rem;"></i> Integrate Interface</span></button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: CALIBRATION / SYNC                  -->
<!-- ========================================== -->
<div class="modal-bg" id="calibrationModal">
    <div class="modal-box" style="max-width:650px; padding:0; overflow:hidden;">
        <div class="modal-header" style="background:linear-gradient(135deg,#1C3A6B,var(--primary)); padding:2rem 3rem; margin:0;">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-sliders-h"></i> Synchronize Calibration Telemetry</h3>
            <button class="adm-btn modal-close" onclick="document.getElementById('calibrationModal').style.display='none'" type="button" style="color:#fff; background:transparent; font-size:2rem; padding:0;"><span class="btn-text">&times;</span></button>
        </div>
        <div style="padding:3rem;">
            <input type="hidden" id="calib_equip_id">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Execution Date <span style="color:var(--danger);">*</span></label>
                    <input type="date" id="calib_date" class="form-control" value="<?= date('Y-m-d') ?>" style="font-size:1.3rem; padding:1rem;" required>
                </div>
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Next Validation Due <span style="color:var(--danger);">*</span></label>
                    <input type="date" id="calib_next_date" class="form-control" style="font-size:1.3rem; padding:1rem;" required>
                </div>
            </div>

            <div style="margin-bottom:2rem;">
                <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Telemetry & System Notes</label>
                <textarea id="calib_notes" class="form-control" rows="4" placeholder="Z-Index offset corrected. Baseline optical values affirmed..." style="font-size:1.3rem; padding:1.2rem; border:2px solid var(--border); border-radius:10px;"></textarea>
            </div>
            
            <div style="margin-bottom:2.5rem;">
                <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Resulting Machine State</label>
                <select id="calib_status_update" class="form-control" style="font-size:1.3rem; padding:1rem; border:2px solid var(--primary);">
                    <option value="Operational">Operational (Validation Approved)</option>
                    <option value="Maintenance">Maintenance Hold (Requires Further Triage)</option>
                </select>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('calibrationModal').style.display='none'" style="font-weight:700;"><span class="btn-text">Cancel Request</span></button>
                <button type="button" class="adm-btn adm-btn-primary" style="border-radius:10px; font-weight:900;" onclick="submitCalibration()"><span class="btn-text"><i class="fas fa-satellite-dish" style="margin-right:.5rem;"></i> Append Run Log</span></button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: LOG GENERAL QC                      -->
<!-- ========================================== -->
<div class="modal-bg" id="logQcModal">
    <div class="modal-box" style="max-width:650px; padding:0; overflow:hidden;">
        <div class="modal-header" style="background:var(--surface-2); padding:2rem 3rem; margin:0;">
            <h3 style="color:var(--text-primary); font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-clipboard-list text-primary"></i> Routine Quality Control</h3>
            <button class="adm-btn modal-close" onclick="document.getElementById('logQcModal').style.display='none'" type="button" style="color:var(--text-muted); background:transparent; font-size:2rem; padding:0;"><span class="btn-text">&times;</span></button>
        </div>
        <div style="padding:3rem;">
            <p style="font-size:1.2rem; color:var(--text-muted); margin-bottom:2rem;">Quality assurance module is operating under generalized fleet tracking mode. Granular analyzer linkage is deferred.</p>
            <div style="display:flex; justify-content:flex-end;">
                <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('logQcModal').style.display='none'"><span class="btn-text">Close</span></button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#equipmentTable').DataTable({
        pageLength: 10,
        language: { search: "", searchPlaceholder: "Search machines, models..." }
    });
});

function submitAddEquipment() {
    const pld = {
        action: 'add_equipment',
        equipment_id_str: $('#add_equip_id').val(),
        name: $('#add_equip_name').val(),
        model: $('#add_equip_model').val(),
        manufacturer: $('#add_equip_manuf').val(),
        location: $('#add_equip_loc').val(),
        department: $('#add_equip_dept').val(),
        _csrf: '<?= $csrf_token ?>'
    };

    if(!pld.equipment_id_str || !pld.name || !pld.model) {
        alert("CRITICAL LOCKOUT: Essential hardware identities must be provided."); return;
    }

    $.post('lab_actions.php', pld, function(res) {
        if(res.success) {
            window.location.reload();
        } else {
            alert('Integration Fault: ' + res.message);
        }
    }, 'json');
}

function logCalibration(eid) {
    $('#calib_equip_id').val(eid);
    const d = new Date();
    d.setMonth(d.getMonth() + 6);
    $('#calib_next_date').val(d.toISOString().split('T')[0]);
    $('#calib_notes').val('');
    document.getElementById('calibrationModal').style.display = 'flex';
}

function submitCalibration() {
    const eid = $('#calib_equip_id').val();
    const dt = $('#calib_date').val();
    const ndt = $('#calib_next_date').val();
    if(!dt || !ndt) { alert("Timeline parameters are mandated."); return; }

    $.post('lab_actions.php', {
        action: 'log_calibration',
        equip_id: eid,
        log_date: dt,
        next_date: ndt,
        notes: $('#calib_notes').val(),
        status: $('#calib_status_update').val(),
        _csrf: '<?= $csrf_token ?>'
    }, function(res) {
        if(res.success) window.location.reload();
        else alert("Data Transmission Error: " + res.message);
    }, 'json');
}

function schedMaint(eid) {
    if(!confirm("Initiate strict Maintenance protocol hold for this asset?")) return;
    $.post('lab_actions.php', {
        action: 'update_equipment_status',
        equip_id: eid,
        status: 'Maintenance',
        _csrf: '<?= $csrf_token ?>'
    }, function(res) {
        if(res.success) window.location.reload();
        else alert('Fault: ' + res.message);
    }, 'json');
}

function viewEquip(eid) {
    alert("Asset Telemetry Stream Initialized for UID: " + eid);
}
</script>
