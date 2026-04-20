<?php
// ============================================================
// LAB DASHBOARD - TAB AUDIT (PREMIUM UI REWRITE)
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

<div class="tab-content <?= ($active_tab === 'audit') ? 'active' : '' ?>" id="audit">

    <div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.01)); border:1px solid rgba(47,128,237,0.15); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.5rem;">
                <i class="fas fa-history" style="color:var(--primary); margin-right:.8rem;"></i> Immutable Cryptographic Ledger
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted); margin:0;">Track global mutations, permission escalations, and system-level operations.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center; margin-top:1.5rem; flex-wrap:wrap;">
            <button class="adm-btn adm-btn-primary" onclick="exportAuditTrail()" style="border-radius:10px; font-weight:800;"><span class="btn-text"><i class="fas fa-file-csv"></i> Construct CSV Dump</span></button>
            <button class="adm-btn" onclick="window.print()" style="border-radius:10px; font-weight:800; background:var(--surface-1); color:var(--text-primary); border:2px dashed var(--border);"><span class="btn-text"><i class="fas fa-print"></i> Formal Print Request</span></button>
        </div>
    </div>

    <div class="adm-card shadow-sm" style="border-radius:16px;">
        <!-- Filter Bar -->
        <div class="adm-card-header" style="background:var(--surface-1); padding:2rem; border-bottom:1px solid var(--border);">
            <form class="form-row" style="display:flex; gap:2rem; flex-wrap:wrap;">
                <input type="hidden" name="tab" value="audit">
                <div style="flex:1; min-width:250px;">
                    <label style="font-size:1.1rem; font-weight:800; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Personnel Vector Filtering</label>
                    <select name="tech_id" class="form-control" onchange="this.form.submit()" style="font-size:1.2rem; padding:1rem; border:2px solid var(--border);">
                        <option value="All">Global Network Nodes</option>
                        <?php 
                        mysqli_data_seek($techs_q, 0);
                        while($t = mysqli_fetch_assoc($techs_q)): ?>
                            <option value="<?= $t['user_id'] ?>" <?= $tech_filter == $t['user_id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div style="flex:1; min-width:250px;">
                    <label style="font-size:1.1rem; font-weight:800; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Application Domain Targeting</label>
                    <select name="module" class="form-control" onchange="this.form.submit()" style="font-size:1.2rem; padding:1rem; border:2px solid var(--border);">
                        <option value="All">All Operational Modules</option>
                        <?php 
                        mysqli_data_seek($mods_q, 0);
                        while($m = mysqli_fetch_assoc($mods_q)): ?>
                            <option value="<?= e($m['module_affected']) ?>" <?= $module_filter == $m['module_affected'] ? 'selected' : '' ?>><?= e($m['module_affected']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="adm-card-body" style="padding:1rem;">
            <div class="adm-table-wrap">
                <table id="auditTable" class="adm-table">
                    <thead>
                        <tr>
                            <th>Chain Index</th>
                            <th>Timestamp Block</th>
                            <th>Authorized Signer</th>
                            <th>Operation Type</th>
                            <th>System Vector</th>
                            <th>Document Key</th>
                            <th>IP / Telemetry</th>
                            <th>Delta Matrix</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($audit_res)): ?>
                        <tr>
                            <td><strong style="color:var(--primary); font-family:monospace; font-size:1.2rem;">#<?= str_pad($row['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                            <td style="font-weight:700; color:var(--text-secondary); white-space:nowrap;"><?= date('Y-m-d | H:i:s', strtotime($row['created_at'])) ?></td>
                            <td>
                                <div style="font-weight: 800; font-size:1.1rem; color:var(--text-primary);"><?= e($row['tech_name']) ?></div>
                                <div style="font-size: 0.9rem; color:var(--text-muted); font-weight:600;"><i class="fas fa-fingerprint"></i> UID: <?= e($row['technician_id']) ?></div>
                            </td>
                            <td>
                                <?php 
                                    $act = $row['action_type'];
                                    $bg = 'var(--surface-2)'; $tx = 'var(--text-primary)';
                                    if(stripos($act, 'reject') !== false || stripos($act, 'delete') !== false) { $bg = 'rgba(239,68,68,0.1)'; $tx = '#ef4444'; }
                                    elseif(stripos($act, 'amend') !== false || stripos($act, 'update') !== false) { $bg = 'rgba(245,158,11,0.1)'; $tx = '#f59e0b'; }
                                    else { $bg = 'rgba(34,197,94,0.1)'; $tx = '#22c55e'; }
                                ?>
                                <span class="adm-badge" style="background:<?= $bg ?>; color:<?= $tx ?>; font-weight:800;"><i class="fas fa-exchange-alt"></i> <?= e($act) ?></span>
                            </td>
                            <td><span class="adm-badge" style="background:var(--surface-3); color:var(--text-secondary); font-weight:700; font-size:1rem; border:1px solid var(--border);"><?= e($row['module_affected']) ?></span></td>
                            <td><strong style="color:var(--text-primary); font-size:1.2rem;">#<?= $row['record_id'] ?: 'NULL' ?></strong></td>
                            <td>
                                <div style="font-family:monospace; font-size:1rem; font-weight:800; color:var(--text-primary);"><?= e($row['ip_address']) ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); font-weight:500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" title="<?= e($row['device_info']) ?>"><?= e($row['device_info']) ?></div>
                            </td>
                            <td>
                                <button class="adm-btn adm-btn-primary btn-sm" style="border-radius:8px;" onclick='viewDiff(<?= json_encode($row['old_value']) ?>, <?= json_encode($row['new_value']) ?>)'><span class="btn-text"><i class="fas fa-code"></i> Assess Delta</span></button>
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
<!-- MODAL: DELTA VIEWER                        -->
<!-- ========================================== -->
<div class="modal-bg" id="diffModal">
    <div class="modal-box" style="max-width:1000px; padding:0; overflow:hidden;">
        <div class="modal-header" style="background:var(--surface-2); padding:2rem 3rem; margin:0; border-bottom:1px solid var(--border);">
            <h3 style="color:var(--text-primary); font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-terminal text-primary"></i> Object Delta Analysis Matrix</h3>
            <button class="adm-btn modal-close" onclick="document.getElementById('diffModal').style.display='none'" type="button" style="color:var(--text-muted); background:transparent; font-size:2rem; padding:0;"><span class="btn-text">&times;</span></button>
        </div>
        <div style="padding:3rem;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 2.5rem;">
                <div>
                    <h6 style="color:#ef4444; font-weight:900; margin-bottom:1rem; text-transform:uppercase; font-size:1.1rem; letter-spacing:0.05em;"><i class="fas fa-minus-circle"></i> Retracted State</h6>
                    <pre id="old_json" style="background:#000; color:#ef4444; padding:1.5rem; border-radius:12px; height:500px; overflow-y:auto; border:2px solid rgba(239,68,68,0.2); font-family:monospace; font-size: 1.1rem; line-height:1.6;"></pre>
                </div>
                <div>
                    <h6 style="color:#22c55e; font-weight:900; margin-bottom:1rem; text-transform:uppercase; font-size:1.1rem; letter-spacing:0.05em;"><i class="fas fa-plus-circle"></i> Committed State</h6>
                    <pre id="new_json" style="background:#000; color:#22c55e; padding:1.5rem; border-radius:12px; height:500px; overflow-y:auto; border:2px solid rgba(34,197,94,0.2); font-family:monospace; font-size: 1.1rem; line-height:1.6;"></pre>
                </div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; margin-top:2.5rem; border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('diffModal').style.display='none'" style="font-weight:700;"><span class="btn-text">Terminate Review Session</span></button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#auditTable').DataTable({
        pageLength: 20,
        order: [[1, 'desc']],
        language: { search: "", searchPlaceholder: "Deep dive telemetry search..." }
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
    document.getElementById('diffModal').style.display = 'flex';
}

function exportAuditTrail() {
    const csrftoken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    alert("SYSTEM SECURE EXPORT: Assembling CSV dump for encrypted transfer...");
    window.location.href = 'lab_exports.php?action=export_audit_trail&csrf=' + encodeURIComponent(csrftoken || '');
}
</script>
