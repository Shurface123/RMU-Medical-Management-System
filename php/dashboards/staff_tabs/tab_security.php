<?php
/**
 * tab_security.php — Module 7: Security Staff Module (Modernized)
 */
if ($staffRole !== 'security') { echo '<div id="sec-security" class="dash-section"></div>'; return; }

$incidents    = dbSelect($conn,"SELECT * FROM security_incidents WHERE staff_id=? ORDER BY reported_at DESC LIMIT 50","i",[$staff_id]);
$patrol_logs  = dbSelect($conn,"SELECT * FROM security_logs WHERE staff_id=? AND incident_type='patrol log' ORDER BY reported_at DESC LIMIT 50","i",[$staff_id]);
?>
<div id="sec-security" class="dash-section">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-shield-alt" style="color:var(--role-accent);"></i> Incident Command</h2>
            <p style="font-size:1.35rem;color:var(--text-muted);margin:0.5rem 0 0;">Facility surveillance, incident reporting, and patrol monitoring</p>
        </div>
        <div style="display:flex;gap:1rem;">
            <button class="btn btn-danger" onclick="openModal('incidentModal')"><i class="fas fa-exclamation-triangle mr-2"></i> Report Incident</button>
            <button class="btn btn-primary" onclick="openModal('patrolModal')"><i class="fas fa-route mr-2"></i> Log Patrol</button>
        </div>
    </div>

    <!-- Security KPI Pulse -->
    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:2.5rem; margin-bottom:3.5rem;">
        <?php
        $stats_sec = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM security_incidents WHERE staff_id=? AND DATE(reported_at)=?","is",[$staff_id,$today]), 'label'=>'Active Incidents','icon'=>'fa-sensor-alert','clr'=>'#EB5757'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM security_logs WHERE staff_id=? AND incident_type='patrol log' AND DATE(reported_at)=?","is",[$staff_id,$today]), 'label'=>'Patrol Checks','icon'=>'fa-user-police-tie','clr'=>'#2F80ED'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM visitor_logs WHERE logged_by=? AND DATE(entry_time)=?","is",[$staff_id,$today]), 'label'=>'Total Visitors','icon'=>'fa-id-card-clip','clr'=>'#27AE60'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM visitor_logs WHERE logged_by=? AND exit_time IS NULL","i",[$staff_id]), 'label'=>'On-Site Now','icon'=>'fa-users-viewfinder','clr'=>'#9B51E0'],
        ];
        foreach($stats_sec as $s): ?>
        <div class="stat-card-v2">
            <div class="s-icon" style="background:<?= $s['clr'] ?>15; color:<?= $s['clr'] ?>;"><i class="fas <?= $s['icon'] ?>"></i></div>
            <div class="s-data"><span><?= $s['label'] ?></span><strong><?= $s['val']??0 ?></strong></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:grid; grid-template-columns:1.8fr 1fr; gap:3rem; margin-bottom:3rem;">
        <!-- Incident Feed -->
        <div class="card">
            <div class="card-header" style="padding:1.8rem 2.5rem; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:1.6rem; font-weight:800;"><i class="fas fa-tower-observation mr-2 text-danger"></i> Deployment Logs</h3>
                <span class="p-badge"><?= count($incidents) ?> Total Entries</span>
            </div>
            <div style="padding:1.5rem 2.5rem;">
                <table id="tblIncidents" class="display responsive nowrap" style="width:100%">
                    <thead><tr><th>Event Type</th><th>Location Matrix</th><th>Priority</th><th>Timeline</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($incidents as $inc):
                            $sv = strtolower($inc['severity']??'low'); 
                            $sc = ['low'=>'#27AE60','medium'=>'#F2C94C','high'=>'#F2994A','critical'=>'#EB5757'][$sv]??'#999';
                            $st = strtolower($inc['status']??'reported'); 
                            $stc = ['reported'=>'#F2994A','investigating'=>'#2F80ED','resolved'=>'#27AE60'][$st]??'#999';
                        ?>
                        <tr>
                            <td><strong style="font-size:1.3rem;"><?= e(ucfirst($inc['incident_type'])) ?></strong></td>
                            <td><div style="font-size:1.2rem;"><i class="fas fa-map-marker-alt mr-2 opacity-50"></i><?= e($inc['location']) ?></div></td>
                            <td><span class="p-badge" style="background:<?= $sc ?>22; color:<?= $sc ?>; font-weight:800;"><?= strtoupper($sv) ?></span></td>
                            <td><span style="font-size:1.15rem; color:var(--text-muted);"><?= date('H:i, d M', strtotime($inc['reported_at'])) ?></span></td>
                            <td><span class="p-badge status active" style="background:<?= $stc ?>22; color:<?= $stc ?>;"><i class="fas fa-radar mr-2"></i><?= strtoupper($st) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Patrol Audit -->
        <div class="card">
            <div class="card-header" style="padding:1.8rem 2.5rem;">
                <h3 style="font-size:1.6rem; font-weight:800;"><i class="fas fa-route mr-2 text-primary"></i> Patrol Chronology</h3>
            </div>
            <div style="padding:2rem 2.5rem; max-height:600px; overflow-y:auto;">
                <?php if(empty($patrol_logs)): ?>
                <div style="padding:4rem; text-align:center; color:var(--text-muted);">No patrols logged today.</div>
                <?php else: foreach($patrol_logs as $p): ?>
                <div class="patrol-item" style="border-left:3px solid var(--role-accent); padding:1rem 1.5rem; background:var(--surface-2); border-radius:0 12px 12px 0; margin-bottom:1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.5rem;">
                        <strong style="font-size:1.3rem; color:var(--text-primary);"><?= e($p['location']) ?></strong>
                        <span style="font-size:1.15rem; font-weight:800; color:var(--role-accent);"><?= date('H:i', strtotime($p['reported_at'])) ?></span>
                    </div>
                    <p style="font-size:1.15rem; color:var(--text-secondary); line-height:1.4;"><?= e($p['notes']??'Checkpoint clear. No anomalies detected.') ?></p>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

</div>

<style>
.patrol-item { transition: .3s; }
.patrol-item:hover { background: color-mix(in srgb, var(--role-accent) 5%, var(--surface-2)); transform: translateX(5px); }
</style>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#tblIncidents').DataTable({
            responsive: true,
            pageLength: 10,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: { search: "_INPUT_", searchPlaceholder: "Filter incidents..." }
        });
    }
});

async function submitIncident(){
    const fd = new FormData(document.getElementById('frmIncident'));
    const res = await doAction(fd, 'Incident report successfully uplinked to central command.');
    if(res){ closeModal('incidentModal'); setTimeout(()=>location.reload(), 800); }
}
async function submitPatrol(){
    const fd = new FormData(document.getElementById('frmPatrol'));
    const res = await doAction(fd, 'Patrol telemetry synchronized. Checkpoint verified.');
    if(res){ closeModal('patrolModal'); setTimeout(()=>location.reload(), 800); }
}
</script>
