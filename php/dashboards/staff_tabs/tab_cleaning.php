<?php
/**
 * tab_cleaning.php — Module 4: Cleaner Module (Modernized)
 */
if ($staffRole !== 'cleaner') { echo '<div id="sec-cleaning" class="dash-section"></div>'; return; }

$schedules = dbSelect($conn,"SELECT * FROM cleaning_schedules WHERE assigned_to=? ORDER BY FIELD(status,'urgent','scheduled','in progress','completed'),schedule_date ASC, start_time ASC LIMIT 50","i",[$staff_id]);
$my_reports = dbSelect($conn,"SELECT * FROM contamination_reports WHERE reported_by=? ORDER BY reported_at DESC LIMIT 20","i",[$staff_id]);

// Sanitation board overview
$sanit_board = dbSelect($conn,"SELECT ward_room_area, MAX(sanitation_status) as san_status FROM cleaning_logs WHERE completed_at IS NOT NULL GROUP BY ward_room_area ORDER BY ward_room_area LIMIT 24");
?>
<div id="sec-cleaning" class="dash-section">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-hand-sparkles" style="color:var(--role-accent);"></i> Sanitation Hub</h2>
            <p style="font-size:1.35rem;color:var(--text-muted);margin:0.5rem 0 0;">Maintain facility hygiene and report biohazard risks</p>
        </div>
        <div style="display:flex;gap:1rem;">
            <button class="btn btn-danger" onclick="openModal('contamReportModal')"><i class="fas fa-biohazard mr-2"></i> Report Biohazard</button>
            <button class="btn btn-outline" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        </div>
    </div>

    <!-- Health & Safety Status Board -->
    <div class="card mb-8" style="background:var(--surface); border:1px solid var(--border); overflow:hidden;">
        <div class="card-header" style="background:var(--surface-2); padding:1.5rem 2.5rem; display:flex; align-items:center; gap:1.2rem;">
            <div style="width:36px; height:36px; border-radius:10px; background:var(--success)22; color:var(--success); display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-shield-virus"></i>
            </div>
            <h3 style="font-size:1.6rem; font-weight:800;">Facility Sanitation Pulse</h3>
        </div>
        <div style="padding:2.5rem; display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1.5rem;">
            <?php if(empty($sanit_board)): ?>
                <div style="grid-column:1/-1; padding:3rem; text-align:center; color:var(--text-muted);">No sanitation data available.</div>
            <?php else: 
                $sc = ['clean'=>'#27AE60','in progress'=>'#2F80ED','pending'=>'#F2C94C','contaminated'=>'#EB5757'];
                foreach($sanit_board as $s):
                    $ss = strtolower($s['san_status']??'pending');
                    $clr = $sc[$ss] ?? 'var(--text-muted)';
            ?>
            <div class="sanit-node" style="--node-clr:<?= $clr ?>;">
                <div class="node-head">
                    <span class="node-dot"></span>
                    <strong><?= e($s['ward_room_area']) ?></strong>
                </div>
                <div class="node-status"><?= strtoupper($ss) ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:3rem; margin-bottom:3rem;">
        <!-- Daily Operations -->
        <div class="card">
            <div class="card-header" style="padding:1.8rem 2.5rem; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:1.6rem; font-weight:800;"><i class="fas fa-clipboard-list mr-2 text-primary"></i> Sterilization Queue</h3>
                <span class="p-badge"><?= count($schedules) ?> Tasks Active</span>
            </div>
            <div style="padding:1.5rem 2.5rem;">
                <table id="tblSchedules" class="display responsive nowrap" style="width:100%">
                    <thead><tr><th>Location</th><th>Sterilization Type</th><th>Timer</th><th>Workflow</th></tr></thead>
                    <tbody>
                        <?php foreach($schedules as $s):
                            $st = strtolower($s['status']??'scheduled');
                            $type_ico = ['routine'=>'fa-broom','deep clean'=>'fa-spray-can-sparkles','biohazard'=>'fa-skull-crossbones','disinfection'=>'fa-virus-slash'][$s['cleaning_type']??'']??'fa-broom';
                            $status_clr = ['scheduled'=>'#2F80ED','in progress'=>'#F2C94C','completed'=>'#27AE60','urgent'=>'#EB5757'][$st]??'#999';
                        ?>
                        <tr>
                            <td><div style="font-weight:800; font-size:1.35rem;"><i class="fas fa-door-open mr-2 opacity-50"></i><?= e($s['ward_room_area']) ?></div></td>
                            <td><div style="font-size:1.2rem;"><i class="fas <?= $type_ico ?> mr-2" style="color:var(--role-accent);"></i><?= e(ucfirst($s['cleaning_type'])) ?></div></td>
                            <td><span style="font-size:1.15rem; color:var(--text-muted); font-weight:700;"><?= $s['start_time']?date('H:i, d M',strtotime($s['start_time'])):'—' ?></span></td>
                            <td>
                                <?php if($st==='scheduled'||$st==='urgent'): ?>
                                <button class="btn btn-primary btn-xs" onclick="startCleaning(<?= $s['schedule_id'] ?>)"><i class="fas fa-play mr-1"></i> EXECUTE</button>
                                <?php elseif($st==='in progress'): ?>
                                <button class="btn btn-success btn-xs" onclick="openCompleteClean(<?= $s['schedule_id'] ?>, '<?= e($s['ward_room_area']) ?>')"><i class="fas fa-check-double mr-1"></i> FINAL</button>
                                <?php else: ?>
                                <span class="p-badge status active"><i class="fas fa-check"></i> SECURED</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Contamination Audit -->
        <div class="card">
            <div class="card-header" style="padding:1.8rem 2.5rem;">
                <h3 style="font-size:1.6rem; font-weight:800;"><i class="fas fa-history mr-2 text-warning"></i> Incident History</h3>
            </div>
            <div style="padding:1.5rem 2.5rem; max-height:600px; overflow-y:auto;">
                <?php if(empty($my_reports)): ?>
                <div style="padding:5rem; text-align:center; color:var(--text-muted);">No filed incidents.</div>
                <?php else: foreach($my_reports as $r):
                    $sev = strtolower($r['severity']??'medium');
                    $sev_clr = ['low'=>'#27AE60','medium'=>'#F2C94C','high'=>'#F2994A','critical'=>'#EB5757'][$sev]??'#999';
                ?>
                <div class="audit-item" style="border-bottom:1px solid var(--border); padding-bottom:1.5rem; margin-bottom:1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.6rem;">
                        <span class="p-badge" style="background:<?= $sev_clr ?>22; color:<?= $sev_clr ?>;"><?= ucfirst($sev) ?> RISK</span>
                        <span style="font-size:1.1rem; color:var(--text-muted);"><?= date('d M, H:i', strtotime($r['reported_at'])) ?></span>
                    </div>
                    <strong style="font-size:1.3rem; display:block;"><?= e($r['location']) ?></strong>
                    <p style="font-size:1.15rem; color:var(--text-secondary); margin-top:.4rem;"><?= e($r['contamination_type']) ?> — <?= mb_strimwidth(e($r['description']),0,60,'...') ?></p>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

</div>

<style>
.sanit-node { background:var(--surface-2); padding:1.2rem 1.5rem; border-radius:14px; border:1px solid var(--border); transition:.3s; cursor:default; }
.sanit-node:hover { transform:translateY(-3px); border-color:var(--node-clr); box-shadow:var(--shadow-sm); }
.node-head { display:flex; align-items:center; gap:.8rem; margin-bottom:.5rem; }
.node-dot { width:8px; height:8px; border-radius:50%; background:var(--node-clr); }
.node-head strong { font-size:1.3rem; font-weight:800; color:var(--text-primary); }
.node-status { font-size:1rem; font-weight:900; color:var(--node-clr); margin-left:1.6rem; letter-spacing:1px; }

.audit-item:last-child { border-bottom:none; margin-bottom:0; }
.btn-xs { padding:.4rem .8rem; font-size:1rem; font-weight:800; border-radius:8px; }
</style>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#tblSchedules').DataTable({
            responsive: true,
            pageLength: 10,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: { search: "_INPUT_", searchPlaceholder: "Filter queue..." }
        });
    }
});

async function startCleaning(sId){ 
    const res = await doAction({action:'start_cleaning',schedule_id:sId},'Sterilization protocol initiated.'); 
    if(res) setTimeout(()=>location.reload(),800); 
}
function openCompleteClean(id, area){ 
    document.getElementById('complCleanId').value = id; 
    document.getElementById('complCleanArea').textContent = ' sterilizing Area: ' + area; 
    openModal('complCleaning'); 
}
async function submitComplCleaning(){
    const fd = new FormData(document.getElementById('frmComplClean'));
    const res = await doAction(fd, 'Sanitation verified. Area secured.');
    if(res){ closeModal('complCleaning'); setTimeout(()=>location.reload(),800); }
}
async function submitContam(){
    const fd = new FormData(document.getElementById('frmContam'));
    const res = await doAction(fd, 'Biohazard report successfully transmitted.');
    if(res){ closeModal('contamReportModal'); setTimeout(()=>location.reload(),800); }
}
</script>
