<?php
/**
 * tab_security.php — Module 7: Security Staff Module
 */
if ($staffRole !== 'security') { echo '<div id="sec-security" class="dash-section"></div>'; return; }

$incidents    = dbSelect($conn,"SELECT * FROM security_incidents WHERE staff_id=? ORDER BY reported_at DESC LIMIT 20","i",[$staff_id]);
$patrol_logs  = dbSelect($conn,"SELECT * FROM security_logs WHERE staff_id=? AND incident_type='patrol log' ORDER BY reported_at DESC LIMIT 10","i",[$staff_id]);
?>
<div id="sec-security" class="dash-section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
        <h2 style="font-size:2.2rem;font-weight:700;"><i class="fas fa-shield-alt" style="color:var(--role-accent);"></i> Security Operations</h2>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            <button class="btn btn-primary" onclick="openModal('incidentModal')"><span class="btn-text"><i class="fas fa-exclamation-triangle"></i> Report Incident</span></button>
            <button class="btn btn-outline" onclick="openModal('patrolModal')"><span class="btn-text"><i class="fas fa-map-pin"></i> Log Patrol Check-in</span></button>
        </div>
    </div>

    <!-- Today's Stats Strip -->
    <div class="stat-grid" style="margin-bottom:2rem;">
        <?php
        $stats_sec = [
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM security_incidents WHERE staff_id=? AND DATE(reported_at)=?","is",[$staff_id,$today]), 'label'=>'Incidents Today','icon'=>'fa-exclamation-triangle','color'=>'var(--danger)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM security_logs WHERE staff_id=? AND incident_type='patrol log' AND DATE(reported_at)=?","is",[$staff_id,$today]), 'label'=>'Patrol Check-ins','icon'=>'fa-route','color'=>'var(--primary)'],
            ['val'=>dbVal($conn,"SELECT COUNT(*) FROM visitor_logs WHERE logged_by=? AND DATE(entry_time)=?","is",[$staff_id,$today]), 'label'=>'Visitors Logged','icon'=>'fa-users','color'=>'var(--success)'],
        ];
        foreach($stats_sec as $s): ?>
        <div class="stat-mini">
            <div style="width:44px;height:44px;border-radius:12px;background:color-mix(in srgb,<?=$s['color']?> 15%,#fff 85%);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="fas <?=$s['icon']?>" style="font-size:1.8rem;color:<?=$s['color']?>;"></i>
            </div>
            <div class="stat-mini-val" style="color:<?=$s['color']?>;"><?=$s['val']??0?></div>
            <div class="stat-mini-lbl"><?=$s['label']?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">
        <!-- Incidents -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-exclamation-triangle"></i> Incident Reports</h3></div>
            <?php if(empty($incidents)): ?>
            <div class="card-body"><p style="text-align:center;color:var(--text-muted);">No incidents reported.</p></div>
            <?php else: ?>
            <div class="card-body-flush"><table class="stf-table">
                <thead><tr><th>Type</th><th>Location</th><th>Severity</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($incidents as $inc):
                    $sv=$inc['severity']??'low'; $sc=['low'=>'var(--success)','medium'=>'var(--warning)','high'=>'#E67E22','critical'=>'var(--danger)'][$sv]??'var(--info)';
                    $st=$inc['status']??'reported'; $stc=['reported'=>'var(--warning)','investigating'=>'var(--info)','resolved'=>'var(--success)'][$st]??'var(--text-muted)';
                ?><tr>
                    <td><?=e(ucfirst($inc['incident_type']??'—'))?></td>
                    <td><?=e($inc['location']??'—')?></td>
                    <td><span class="badge" style="background:color-mix(in srgb,<?=$sc?> 15%,#fff 85%);color:<?=$sc?>;"><?=ucfirst($sv)?></span></td>
                    <td><?=date('d M, H:i',strtotime($inc['reported_at']))?></td>
                    <td><span class="badge" style="background:color-mix(in srgb,<?=$stc?> 15%,#fff 85%);color:<?=$stc?>;"><?=ucfirst($st)?></span></td>
                </tr><?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>

        <!-- Patrol Log -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-route"></i> Patrol Check-ins</h3></div>
            <?php if(empty($patrol_logs)): ?>
            <div class="card-body"><p style="text-align:center;color:var(--text-muted);">No patrol check-ins logged today.</p></div>
            <?php else: ?>
            <div class="card-body-flush"><table class="stf-table">
                <thead><tr><th>Checkpoint</th><th>Time</th><th>Notes</th></tr></thead>
                <tbody>
                <?php foreach($patrol_logs as $p): ?>
                <tr>
                    <td><i class="fas fa-map-marker-alt" style="color:var(--role-accent);margin-right:.5rem;"></i><?=e($p['location']??'—')?></td>
                    <td><?=date('H:i',strtotime($p['reported_at']))?></td>
                    <td style="color:var(--text-muted);font-size:1.2rem;"><?=e($p['notes']??'—')?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Incident Report Modal -->
<div class="modal-bg" id="incidentModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle" style="color:var(--danger);"></i> Report Incident</h3>
            <button class="btn btn-primary modal-close" onclick="closeModal('incidentModal')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <form id="frmIncident" onsubmit="event.preventDefault();submitIncident();">
            <input type="hidden" name="action" value="report_incident">
            <div class="form-row">
                <div class="form-group"><label>Incident Type *</label>
                    <select name="incident_type" class="form-control" required>
                        <option value="">Select type</option>
                        <option value="theft">Theft</option><option value="vandalism">Vandalism</option>
                        <option value="unauthorized access">Unauthorized Access</option><option value="altercation">Altercation</option>
                        <option value="suspicious activity">Suspicious Activity</option><option value="medical emergency">Medical Emergency</option>
                        <option value="fire">Fire Alarm</option><option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label>Severity *</label>
                    <select name="severity" class="form-control" required>
                        <option value="low">Low</option><option value="medium">Medium</option>
                        <option value="high">High</option><option value="critical">Critical (Auto-alerts Admin)</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Location *</label><input name="location" type="text" class="form-control" required placeholder="Where did it occur?"></div>
            <div class="form-group"><label>Description *</label><textarea name="description" class="form-control" rows="4" required placeholder="Detailed description of what happened..."></textarea></div>
            <div class="form-group"><label>Persons Involved</label><input name="persons_involved" type="text" class="form-control" placeholder="Names or descriptions..."></div>
            <div class="form-group"><label>Actions Taken</label><textarea name="actions_taken" class="form-control" rows="2" placeholder="What immediate actions did you take?"></textarea></div>
            <button type="submit" class="btn btn-danger btn-wide" id="btnIncident"><span class="btn-text"><i class="fas fa-paper-plane"></i> Submit Report</span></button>
        </form>
    </div>
</div>

<!-- Patrol Check-in Modal -->
<div class="modal-bg" id="patrolModal">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <h3><i class="fas fa-map-pin" style="color:var(--role-accent);"></i> Patrol Check-in</h3>
            <button class="btn btn-primary modal-close" onclick="closeModal('patrolModal')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <form id="frmPatrol" onsubmit="event.preventDefault();submitPatrol();">
            <input type="hidden" name="action" value="log_patrol_checkin">
            <div class="form-group"><label>Checkpoint *</label>
                <select name="checkpoint" class="form-control" required>
                    <option value="">Select checkpoint</option>
                    <option value="Main Gate">Main Gate</option><option value="Emergency Entrance">Emergency Entrance</option>
                    <option value="Parking Lot A">Parking Lot A</option><option value="Ward Block A">Ward Block A</option>
                    <option value="Ward Block B">Ward Block B</option><option value="ICU Level">ICU Level</option>
                    <option value="OPD Reception">OPD Reception</option><option value="Pharmacy Wing">Pharmacy Wing</option>
                    <option value="Staff Car Park">Staff Car Park</option><option value="Back Perimeter">Back Perimeter</option>
                </select>
            </div>
            <div class="form-group"><label>Observations / Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="All clear / any observations..."></textarea></div>
            <button type="submit" class="btn btn-primary btn-wide" id="btnPatrol"><span class="btn-text"><i class="fas fa-map-pin"></i> Log Check-in</span></button>
        </form>
    </div>
</div>

<script>
async function submitIncident(){
    const btn=document.getElementById('btnIncident'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmIncident'));
    const res=await doAction(fd,'Incident report submitted!');
    btn.innerHTML='<i class="fas fa-paper-plane"></i> Submit Report'; btn.disabled=false;
    if(res){ closeModal('incidentModal'); document.getElementById('frmIncident').reset(); setTimeout(()=>location.reload(),700); }
}
async function submitPatrol(){
    const btn=document.getElementById('btnPatrol'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmPatrol'));
    const res=await doAction(fd,'Patrol check-in logged!');
    btn.innerHTML='<i class="fas fa-map-pin"></i> Log Check-in'; btn.disabled=false;
    if(res){ closeModal('patrolModal'); document.getElementById('frmPatrol').reset(); setTimeout(()=>location.reload(),700); }
}
</script>
