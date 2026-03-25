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
$query .= " ORDER BY a.created_at DESC LIMIT 200";
$audit_res = mysqli_query($conn, $query);

// Fetch technicians for filter
$techs_q = mysqli_query($conn, "SELECT user_id, full_name FROM lab_technicians ORDER BY full_name");

// Fetch modules for filter
$mods_q = mysqli_query($conn, "SELECT DISTINCT module_affected FROM lab_audit_trail ORDER BY module_affected");
?>

<div class="sec-header">
    <h2 style="font-size: 1.8rem; font-weight: 700;"><i class="fas fa-history"></i> Immutable Security Ledger</h2>
    <div style="display:flex; gap:1.2rem;">
        <button class="adm-btn adm-btn-danger" onclick="exportAuditTrail()"><i class="fas fa-file-csv"></i> Export Security Log</button>
        <button class="adm-btn adm-btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print Records</button>
    </div>
</div>

<div class="info-card" style="border-top: 4px solid var(--danger);">
    <!-- Enhanced Filters -->
    <div style="background: var(--surface-2); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid var(--border);">
        <form class="form-row" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <input type="hidden" name="tab" value="audit">
            <div class="form-group mb-0">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.5rem; display:block;">Filter by Personnel</label>
                <select name="tech_id" class="form-select" onchange="this.form.submit()" style="padding:0.7rem;">
                    <option value="All">All Technicians</option>
                    <?php 
                    mysqli_data_seek($techs_q, 0);
                    while($t = mysqli_fetch_assoc($techs_q)): ?>
                        <option value="<?= $t['user_id'] ?>" <?= $tech_filter == $t['user_id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.5rem; display:block;">Target Intelligence Module</label>
                <select name="module" class="form-select" onchange="this.form.submit()" style="padding:0.7rem;">
                    <option value="All">All Modules</option>
                    <?php 
                    mysqli_data_seek($mods_q, 0);
                    while($m = mysqli_fetch_assoc($mods_q)): ?>
                        <option value="<?= e($m['module_affected']) ?>" <?= $module_filter == $m['module_affected'] ? 'selected' : '' ?>><?= e($m['module_affected']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="adm-table-wrap">
        <table id="auditTable" class="adm-table display">
            <thead>
                <tr>
                    <th>Entry ID</th>
                    <th>Timestamp (UTC)</th>
                    <th>Signed By</th>
                    <th>Operation</th>
                    <th>Module</th>
                    <th>Resource ID</th>
                    <th>Network/Device</th>
                    <th>Delta</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($audit_res)): ?>
                <tr>
                    <td><strong style="font-family:monospace; color:var(--text-muted);">#<?= str_pad($row['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                    <td style="white-space:nowrap; font-weight:600;"><?= date('y-m-d H:i:s', strtotime($row['created_at'])) ?></td>
                    <td>
                        <div style="font-weight: 700;"><?= e($row['tech_name']) ?></div>
                        <div style="font-size: 0.75rem; color: var(--primary); font-weight:600;">ID: <?= e($row['technician_id']) ?></div>
                    </td>
                    <td>
                        <?php 
                            $act = $row['action_type'];
                            $b_class = 'adm-badge-sm ';
                            if(stripos($act, 'reject') !== false || stripos($act, 'delete') !== false) $b_class .= 'adm-badge-danger';
                            elseif(stripos($act, 'amend') !== false || stripos($act, 'update') !== false) $b_class .= 'adm-badge-warning';
                            else $b_class .= 'adm-badge-success';
                        ?>
                        <span class="adm-badge <?= $b_class ?>"><?= e($act) ?></span>
                    </td>
                    <td><span class="adm-badge" style="background:var(--surface-2); color:var(--text-primary); font-weight:700;"><?= e($row['module_affected']) ?></span></td>
                    <td><strong style="color:var(--primary);">#<?= $row['record_id'] ?: 'N/A' ?></strong></td>
                    <td>
                        <div style="font-family:monospace; font-size:0.85rem;"><?= e($row['ip_address']) ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;" title="<?= e($row['device_info']) ?>"><?= e($row['device_info']) ?></div>
                    </td>
                    <td>
                        <button class="adm-btn adm-btn-sm" style="background:var(--surface-2); color:var(--text-primary);" onclick='viewDiff(<?= json_encode($row['old_value']) ?>, <?= json_encode($row['new_value']) ?>)'><i class="fas fa-microchip"></i> Diff</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Diff Viewer Modal -->
<div class="modal fade" id="diffModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg); border:none; box-shadow:0 15px 45px rgba(0,0,0,0.3);">
            <div class="modal-header" style="border-bottom:1px solid var(--border); padding:1.5rem 2rem;">
                <h5 class="modal-title" style="font-weight:700; font-size:1.4rem;"><i class="fas fa-terminal" style="color:var(--primary); margin-right:.5rem;"></i> Audit Delta Inspector</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body" style="padding:2rem;">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h6 style="color:var(--danger); font-weight:700; margin-bottom:1rem; text-transform:uppercase; font-size:0.8rem; letter-spacing:0.05em;">Prior State</h6>
                        <pre id="old_json" style="background:#1e1e1e; color:#d4d4d4; padding:1.2rem; border-radius:10px; max-height:400px; overflow-y:auto; border:1px solid #333; font-family:'Fira Code', monospace; font-size: 0.9rem; line-height:1.5;"></pre>
                    </div>
                    <div>
                        <h6 style="color:var(--success); font-weight:700; margin-bottom:1rem; text-transform:uppercase; font-size:0.8rem; letter-spacing:0.05em;">Resultant State</h6>
                        <pre id="new_json" style="background:#1e1e1e; color:#d4d4d4; padding:1.2rem; border-radius:10px; max-height:400px; overflow-y:auto; border:1px solid #333; font-family:'Fira Code', monospace; font-size: 0.9rem; line-height:1.5;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border); padding:1.5rem 2rem;">
                <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal">Close Inspector</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#auditTable').DataTable({
        pageLength: 20,
        order: [[1, 'desc']],
        language: { search: "", searchPlaceholder: "Search ledger..." }
    });
});

function viewDiff(oldVal, newVal) {
    try {
        const oldObj = oldVal ? JSON.parse(oldVal) : oldVal;
        const newObj = newVal ? JSON.parse(newVal) : newVal;
        $('#old_json').text(typeof oldObj === 'object' ? JSON.stringify(oldObj, null, 2) : (oldObj || 'NULL'));
        $('#new_json').text(typeof newObj === 'object' ? JSON.stringify(newObj, null, 2) : (newObj || 'NULL'));
    } catch(e) {
        $('#old_json').text(oldVal || 'NULL');
        $('#new_json').text(newVal || 'NULL');
    }
    new bootstrap.Modal(document.getElementById('diffModal')).show();
}

function exportAuditTrail() {
    Swal.fire({
        title: 'Export Security Log',
        text: 'Preparing encrypted CSV dump of the audit trail...',
        icon: 'info',
        showConfirmButton: false,
        timer: 1500,
        didOpen: () => { Swal.showLoading(); }
    }).then(() => {
        window.location.href = 'lab_exports.php?action=export_audit_trail&csrf=' + encodeURIComponent(document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    });
}
</script>
