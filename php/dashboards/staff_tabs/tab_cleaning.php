<?php
/**
 * tab_cleaning.php — Module 4: Cleaner Module
 */
if ($staffRole !== 'cleaner') { echo '<div id="sec-cleaning" class="dash-section"></div>'; return; }

$schedules = dbSelect($conn,"SELECT * FROM cleaning_schedules WHERE assigned_to=? ORDER BY FIELD(status,'urgent','scheduled','in progress','completed'),shift_date ASC, scheduled_time ASC LIMIT 30","i",[$staff_id]);
$my_reports = dbSelect($conn,"SELECT * FROM contamination_reports WHERE staff_id=? ORDER BY reported_at DESC LIMIT 10","i",[$staff_id]);

// Sanitation board overview
$sanit_board = dbSelect($conn,"SELECT ward_room_area, MAX(sanitation_status) as san_status FROM cleaning_logs WHERE completed_at IS NOT NULL GROUP BY ward_room_area ORDER BY ward_room_area LIMIT 20");
?>
<div id="sec-cleaning" class="dash-section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
        <h2 style="font-size:2.2rem;font-weight:700;"><i class="fas fa-broom" style="color:var(--role-accent);"></i> Cleaning Log</h2>
        <button class="btn btn-danger" onclick="openModal('contamReportModal')"><i class="fas fa-biohazard"></i> Report Contamination</button>
    </div>

    <!-- Sanitation Status Board -->
    <?php if(!empty($sanit_board)): ?>
    <div class="card" style="margin-bottom:2rem;">
        <div class="card-header"><h3><i class="fas fa-clipboard-check"></i> Sanitation Status Board</h3></div>
        <div class="card-body" style="display:flex;flex-wrap:wrap;gap:1rem;">
            <?php $sc=['clean'=>'var(--success)','in progress'=>'var(--primary)','pending'=>'var(--warning)','contaminated'=>'var(--danger)'];
            foreach($sanit_board as $s):
                $ss=$s['san_status']??'pending';
                $color=$sc[$ss]??'var(--text-muted)';
            ?>
            <div style="padding:.7rem 1.3rem;border-radius:12px;background:color-mix(in srgb,<?=$color?> 15%,#fff 85%);border:1.5px solid <?=$color?>;font-size:1.2rem;display:flex;align-items:center;gap:.6rem;">
                <i class="fas fa-circle" style="font-size:.8rem;color:<?=$color?>;"></i>
                <span style="font-weight:600;"><?=e($s['ward_room_area'])?></span>
                <span style="color:<?=$color?>;font-size:1rem;"><?=ucfirst($ss)?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cleaning Schedule -->
    <div class="card" style="margin-bottom:2rem;">
        <div class="card-header"><h3><i class="fas fa-calendar-check"></i> My Cleaning Schedule</h3></div>
        <?php if(empty($schedules)): ?>
        <div class="card-body" style="text-align:center;padding:4rem;"><p style="color:var(--text-muted);">No cleaning tasks scheduled.</p></div>
        <?php else: ?>
        <div class="card-body-flush">
        <table class="stf-table">
            <thead><tr><th>Area / Room</th><th>Type</th><th>Scheduled Time</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($schedules as $s):
                $st=$s['status']??'scheduled';
                $st_c=['scheduled'=>'var(--info)','in progress'=>'var(--warning)','completed'=>'var(--success)','urgent'=>'var(--danger)'][$st]??'var(--text-muted)';
                $type_icon=['routine'=>'fa-broom','deep clean'=>'fa-spray-can','biohazard'=>'fa-biohazard','disinfection'=>'fa-shield-virus'][$s['cleaning_type']??'']??'fa-broom';
            ?>
            <tr>
                <td><i class="fas fa-door-open" style="color:var(--role-accent);margin-right:.5rem;"></i><?=e($s['ward_room_area']??'—')?></td>
                <td><i class="fas <?=$type_icon?>" style="margin-right:.4rem;"></i><?=e(ucfirst($s['cleaning_type']??'—'))?></td>
                <td><?=$s['scheduled_time']?date('d M, H:i',strtotime($s['scheduled_time'])):'—'?></td>
                <td><span class="badge" style="background:color-mix(in srgb,<?=$st_c?> 15%,#fff 85%);color:<?=$st_c?>;"><?=ucfirst($st)?></span></td>
                <td>
                    <?php if($st==='scheduled'||$st==='urgent'): ?>
                    <button class="btn btn-primary btn-sm" onclick="startCleaning(<?=$s['id']?>)"><i class="fas fa-play"></i> Start</button>
                    <?php elseif($st==='in progress'): ?>
                    <button class="btn btn-success btn-sm" onclick="openCompleteClean(<?=$s['id']?>, '<?=e($s['ward_room_area']??'')?>')"><i class="fas fa-check"></i> Complete</button>
                    <?php else: ?>
                    <span style="color:var(--text-muted);font-size:1.2rem;">Done</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Contamination Reports -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-biohazard"></i> My Contamination Reports</h3></div>
        <?php if(empty($my_reports)): ?>
        <div class="card-body" style="text-align:center;padding:4rem;"><p style="color:var(--text-muted);">No contamination reports filed.</p></div>
        <?php else: ?>
        <div class="card-body-flush">
        <table class="stf-table">
            <thead><tr><th>Location</th><th>Type</th><th>Severity</th><th>Reported At</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($my_reports as $r):
                $sv=$r['severity']??'medium';
                $sv_c=['low'=>'var(--success)','medium'=>'var(--warning)','high'=>'var(--danger)','critical'=>'#8B0000'][$sv]??'var(--warning)';
                $rst=$r['status']??'reported';
                $rst_c=['reported'=>'var(--warning)','acknowledged'=>'var(--info)','in progress'=>'var(--primary)','resolved'=>'var(--success)'][$rst]??'var(--text-muted)';
            ?>
            <tr>
                <td><?=e($r['location'])?></td>
                <td><?=e(ucfirst($r['contamination_type']??'—'))?></td>
                <td><span class="badge" style="background:color-mix(in srgb,<?=$sv_c?> 15%,#fff 85%);color:<?=$sv_c?>;"><?=ucfirst($sv)?></span></td>
                <td><?=date('d M Y, H:i',strtotime($r['reported_at']))?></td>
                <td><span class="badge" style="background:color-mix(in srgb,<?=$rst_c?> 15%,#fff 85%);color:<?=$rst_c?>;"><?=ucfirst($rst)?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Complete Cleaning Modal -->
<div class="modal-bg" id="complCleaning">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Complete Cleaning Task</h3>
            <button class="modal-close" onclick="closeModal('complCleaning')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmComplClean" onsubmit="event.preventDefault();submitComplCleaning();">
            <input type="hidden" name="action" value="complete_cleaning">
            <input type="hidden" name="schedule_id" id="complCleanId">
            <p id="complCleanArea" style="font-weight:600;font-size:1.4rem;margin-bottom:1.5rem;color:var(--text-secondary);"></p>
            <div class="form-group">
                <label>Sanitation Status After Cleaning</label>
                <select name="sanitation_status" class="form-control" required>
                    <option value="clean">Clean ✓</option>
                    <option value="pending">Pending follow-up</option>
                </select>
            </div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Any remarks..."></textarea></div>
            <div class="form-group"><label>Photo Proof (Required if biohazard)</label><input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png"></div>
            <button type="submit" class="btn btn-success btn-wide" id="btnComplClean"><i class="fas fa-check"></i> Mark Complete</button>
        </form>
    </div>
</div>

<!-- Contamination Report Modal -->
<div class="modal-bg" id="contamReportModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-biohazard" style="color:var(--danger);"></i> Report Contamination</h3>
            <button class="modal-close" onclick="closeModal('contamReportModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmContam" onsubmit="event.preventDefault();submitContam();">
            <input type="hidden" name="action" value="report_contamination">
            <div class="form-group"><label>Location *</label><input type="text" name="location" class="form-control" required placeholder="Ward/Room/Area name"></div>
            <div class="form-row">
                <div class="form-group"><label>Contamination Type *</label>
                    <select name="contamination_type" class="form-control" required>
                        <option value="">Select</option>
                        <option value="blood">Blood</option><option value="biohazard waste">Biohazard Waste</option>
                        <option value="chemical">Chemical Spill</option><option value="bodily fluids">Bodily Fluids</option>
                        <option value="sharps">Sharps/Needles</option><option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label>Severity *</label>
                    <select name="severity" class="form-control" required>
                        <option value="low">Low</option><option value="medium">Medium</option>
                        <option value="high">High</option><option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Description *</label><textarea name="description" class="form-control" rows="3" required placeholder="Describe the contamination..."></textarea></div>
            <div class="form-group"><label>Photo Evidence</label><input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png"></div>
            <button type="submit" class="btn btn-danger btn-wide" id="btnContam"><i class="fas fa-biohazard"></i> Submit Report</button>
        </form>
    </div>
</div>

<script>
async function startCleaning(sId){ const res=await doAction({action:'start_cleaning',schedule_id:sId},'Cleaning started!'); if(res) setTimeout(()=>location.reload(),700); }
function openCompleteClean(id,area){ document.getElementById('complCleanId').value=id; document.getElementById('complCleanArea').textContent='Area: '+area; openModal('complCleaning'); }
async function submitComplCleaning(){
    const btn=document.getElementById('btnComplClean'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmComplClean'));
    const res=await doAction(fd,'Cleaning completed!');
    btn.innerHTML='<i class="fas fa-check"></i> Mark Complete'; btn.disabled=false;
    if(res){ closeModal('complCleaning'); setTimeout(()=>location.reload(),700); }
}
async function submitContam(){
    const btn=document.getElementById('btnContam'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmContam'));
    const res=await doAction(fd,'Contamination reported!');
    btn.innerHTML='<i class="fas fa-biohazard"></i> Submit Report'; btn.disabled=false;
    if(res){ closeModal('contamReportModal'); document.getElementById('frmContam').reset(); setTimeout(()=>location.reload(),700); }
}
</script>
