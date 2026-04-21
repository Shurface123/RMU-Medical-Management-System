<?php
/**
 * tab_visitors.php — Security: Visitor Log Module (Modernized)
 */
if ($staffRole !== 'security') { echo '<div id="sec-visitors" class="dash-section"></div>'; return; }

$today_visitors = dbSelect($conn,"SELECT * FROM visitor_logs WHERE logged_by=? AND DATE(entry_time)=? ORDER BY log_id DESC","is",[$staff_id,$today]);
$active_visitors = dbSelect($conn,"SELECT * FROM visitor_logs WHERE logged_by=? AND DATE(entry_time)=? AND exit_time IS NULL ORDER BY log_id DESC","is",[$staff_id,$today]);
?>
<div id="sec-visitors" class="dash-section">

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1.5rem;margin-bottom:3rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-user-check" style="color:var(--role-accent);"></i> Visitor Clearance</h2>
            <p style="font-size:1.35rem;color:var(--text-muted);margin:0.5rem 0 0;">Manage facility access and security check-ins</p>
        </div>
        <div style="display:flex;gap:1rem;">
            <button class="btn btn-primary" onclick="openModal('addVisitorModal')"><i class="fas fa-user-plus mr-2"></i> Register Arrival</button>
            <button class="btn btn-outline" onclick="location.reload()"><i class="fas fa-sync"></i></button>
        </div>
    </div>

    <!-- Active Occupancy Board -->
    <?php if(!empty($active_visitors)): ?>
    <div class="card mb-8" style="background:var(--surface); border:1px solid var(--border);">
        <div class="card-header" style="background:rgba(242, 153, 74, 0.05); padding:1.5rem 2.5rem; display:flex; align-items:center; gap:1.2rem; border-bottom:1px solid var(--border);">
            <div style="width:36px; height:36px; border-radius:10px; background:#F2994A22; color:#F2994A; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-users-rays"></i>
            </div>
            <h3 style="font-size:1.6rem; font-weight:800;">Currently On-Site (<?= count($active_visitors) ?>)</h3>
        </div>
        <div style="padding:1.5rem 2.5rem;">
            <table id="tblActiveVisitors" class="display responsive nowrap" style="width:100%">
                <thead><tr><th>Nominal Name</th><th>ID Credentials</th><th>Destination</th><th>Entry Point</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($active_visitors as $v): ?>
                    <tr>
                        <td><strong style="font-size:1.35rem;"><?= e($v['visitor_name']) ?></strong></td>
                        <td><code style="font-size:1.15rem; background:var(--surface-2); padding:.2rem .5rem; border-radius:4px;"><?= e($v['visitor_id_number']??'UNIDENTIFIED') ?></code></td>
                        <td>
                            <div style="font-size:1.2rem; font-weight:600;"><?= e($v['person_visiting']??'General') ?></div>
                            <div style="font-size:1.05rem; color:var(--text-muted);"><?= e($v['ward_department']??'Reception') ?></div>
                        </td>
                        <td><span style="font-size:1.15rem; font-weight:700; color:var(--role-accent);"><?= date('H:i', strtotime($v['entry_time'])) ?></span></td>
                        <td><button class="btn btn-success btn-xs" onclick="logExit(<?= $v['log_id'] ?>)"><i class="fas fa-sign-out mr-1"></i> LOG EXIT</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Master Daily Log -->
    <div class="card">
        <div class="card-header" style="padding:1.8rem 2.5rem;">
            <h3 style="font-size:1.6rem; font-weight:800;"><i class="fas fa-list-check mr-2"></i> Daily Clearance Log</h3>
        </div>
        <div style="padding:1.5rem 2.5rem;">
            <table id="tblAllVisitors" class="display responsive nowrap" style="width:100%">
                <thead><tr><th>Visitor</th><th>Purpose</th><th>Visiting</th><th>Entry</th><th>Exit</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach($today_visitors as $v):
                        $has_exit = !empty($v['exit_time']);
                    ?>
                    <tr>
                        <td><div style="font-weight:800; font-size:1.3rem;"><?= e($v['visitor_name']) ?></div></td>
                        <td><span style="font-size:1.15rem; color:var(--text-secondary);"><?= e($v['purpose']??'—') ?></span></td>
                        <td><div style="font-size:1.15rem;"><?= e($v['person_visiting']??'—') ?></div></td>
                        <td><span style="font-size:1.15rem; font-weight:700;"><?= date('H:i', strtotime($v['entry_time'])) ?></span></td>
                        <td><span style="font-size:1.15rem; color:var(--text-muted);"><?= $has_exit ? date('H:i', strtotime($v['exit_time'])) : '—' ?></span></td>
                        <td>
                            <?php if($has_exit): ?><span class="p-badge" style="background:var(--border); color:var(--text-muted); opacity:.7;"><i class="fas fa-check-circle mr-1"></i> EXITED</span>
                            <?php else: ?><span class="p-badge status active" style="background:#27AE6015; color:#27AE60;"><i class="fas fa-circle-dot mr-1"></i> INSIDE</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
.btn-xs { padding:.4rem .9rem; font-size:1rem; font-weight:900; border-radius:8px; }
</style>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#tblActiveVisitors, #tblAllVisitors').DataTable({
            responsive: true,
            pageLength: 10,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: { search: "_INPUT_", searchPlaceholder: "Search visitor names..." }
        });
    }
});

async function submitVisitor(){
    const fd = new FormData(document.getElementById('frmVisitor'));
    const res = await doAction(fd, 'Visitor access granted. Clearance logged.');
    if(res){ closeModal('addVisitorModal'); setTimeout(()=>location.reload(), 800); }
}
async function logExit(id){
    if(!confirmAction('Verify visitor exit and close clearance?')) return;
    const res = await doAction({action:'log_visitor_exit', visitor_log_id:id}, 'Visitor record closed. Exit verified.');
    if(res) setTimeout(()=>location.reload(), 800);
}
</script>
