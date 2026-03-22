<?php
// ============================================================
// LAB DASHBOARD - TAB AUDIT (Module 11 - formerly 12 in some plans)
// ============================================================
if (!isset($user_id)) { exit; }

$tech_filter = $_GET['tech_id'] ?? 'All';
$module_filter = $_GET['module'] ?? 'All';

// Build Query
$query = "SELECT a.*, l.full_name AS tech_name, l.technician_id
          FROM lab_audit_trail a
          JOIN lab_technicians l ON a.technician_id = l.user_id
          WHERE 1=1";

if ($tech_filter !== 'All') {
    $query .= " AND a.technician_id = " . (int)$tech_filter;
}
if ($module_filter !== 'All') {
    $query .= " AND a.module_affected = '" . mysqli_real_escape_string($conn, $module_filter) . "'";
}
$query .= " ORDER BY a.timestamp DESC LIMIT 200";
$audit_res = mysqli_query($conn, $query);

// Fetch technicians for filter
$techs_q = mysqli_query($conn, "SELECT user_id, full_name FROM lab_technicians ORDER BY full_name");

// Fetch modules for filter
$mods_q = mysqli_query($conn, "SELECT DISTINCT module_affected FROM lab_audit_trail ORDER BY module_affected");
?>

<div class="sec-header">
    <h2><i class="fas fa-history"></i> System Audit Trail</h2>
    <div style="display:flex; gap:1rem;">
        <button class="adm-btn adm-btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print Log</button>
    </div>
</div>

<div class="adm-table-wrap" style="background: var(--surface); padding: 1.5rem;">
    <!-- Filters -->
    <form class="form-row" style="margin-bottom: 2rem; background: var(--surface-2); padding: 1rem; border-radius: 8px;">
        <input type="hidden" name="tab" value="audit">
        <div class="form-group mb-0">
            <label>Technician</label>
            <select name="tech_id" class="form-select" onchange="this.form.submit()">
                <option value="All">All Technicians</option>
                <?php while($t = mysqli_fetch_assoc($techs_q)): ?>
                    <option value="<?= $t['user_id'] ?>" <?= $tech_filter == $t['user_id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group mb-0">
            <label>Target Module</label>
            <select name="module" class="form-select" onchange="this.form.submit()">
                <option value="All">All Modules</option>
                <?php while($m = mysqli_fetch_assoc($mods_q)): ?>
                    <option value="<?= e($m['module_affected']) ?>" <?= $module_filter == $m['module_affected'] ? 'selected' : '' ?>><?= e($m['module_affected']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>

    <table id="auditTable" class="adm-table display" style="font-size: 1rem;">
        <thead>
            <tr>
                <th>Log ID</th>
                <th>Timestamp</th>
                <th>Technician</th>
                <th>Action Type</th>
                <th>Module</th>
                <th>Target Record ID</th>
                <th>IP / Device</th>
                <th>Data Changes</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($audit_res)): ?>
            <tr>
                <td>#ADT-<?= str_pad($row['log_id'], 5, '0', STR_PAD_LEFT) ?></td>
                <td style="white-space:nowrap;"><?= date('y-m-d H:i:s', strtotime($row['timestamp'])) ?></td>
                <td>
                    <strong><?= e($row['tech_name']) ?></strong><br>
                    <small style="color:var(--text-muted);"><?= e($row['technician_id']) ?></small>
                </td>
                <td><span class="adm-badge" style="background:var(--role-accent-light);color:var(--role-accent);"><?= e($row['action_type']) ?></span></td>
                <td><?= e($row['module_affected']) ?></td>
                <td><?= $row['record_id_affected'] ?: '-' ?></td>
                <td>
                    <?= e($row['ip_address']) ?><br>
                    <small style="color:var(--text-muted);" title="<?= e($row['device_info']) ?>"><?= substr(e($row['device_info']), 0, 20) ?>...</small>
                </td>
                <td>
                    <button class="adm-btn adm-btn-sm" style="background:var(--surface-2);" onclick='viewDiff(<?= json_encode($row['old_value']) ?>, <?= json_encode($row['new_value']) ?>)'><i class="fas fa-code-branch"></i> View</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Diff Viewer Modal -->
<div class="modal fade" id="diffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h5 class="modal-title"><i class="fas fa-search"></i> Data Change Delta (JSON)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div>
                        <h6 style="color:var(--danger);">Old State/Value</h6>
                        <pre id="old_json" style="background:rgba(231,76,60,0.1); padding:1rem; border-radius:4px; max-height:300px; overflow-y:auto; border:1px solid rgba(231,76,60,0.3); font-size: 0.9em;"></pre>
                    </div>
                    <div>
                        <h6 style="color:var(--success);">New State/Value</h6>
                        <pre id="new_json" style="background:rgba(39,174,96,0.1); padding:1rem; border-radius:4px; max-height:300px; overflow-y:auto; border:1px solid rgba(39,174,96,0.3); font-size: 0.9em;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#auditTable').DataTable({
        pageLength: 20,
        order: [[1, 'desc']], // Sort by Timestamp descending
        language: { search: "", searchPlaceholder: "Search logs..." }
    });
});

function viewDiff(oldVal, newVal) {
    try {
        const oldObj = oldVal ? JSON.parse(oldVal) : null;
        const newObj = newVal ? JSON.parse(newVal) : null;
        $('#old_json').text(oldObj ? JSON.stringify(oldObj, null, 2) : 'NULL');
        $('#new_json').text(newObj ? JSON.stringify(newObj, null, 2) : 'NULL');
    } catch(e) {
        // Fallback for non-JSON strings
        $('#old_json').text(oldVal || 'NULL');
        $('#new_json').text(newVal || 'NULL');
    }
    new bootstrap.Modal(document.getElementById('diffModal')).show();
}
</script>
