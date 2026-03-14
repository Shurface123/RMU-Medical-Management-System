<?php
/**
 * tab_cleaning.php
 * Module: Hospital Cleaning Schedules and Logs (Only visible to Cleaners)
 */
?>
<div id="sec-cleaning" class="dash-section <?=($active_tab==='cleaning')?'active':''?>">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
        <h2 style="font-size:2.2rem;font-weight:700;color:var(--text-primary);"><i class="fas fa-broom" style="color:var(--role-accent);"></i> Cleaning Schedules & Logs</h2>
        <button class="adm-btn adm-btn-primary" onclick="openModal('addCleanLogModal')"><i class="fas fa-plus"></i> New Log Entry</button>
    </div>

    <!-- Active Schedules -->
    <div class="adm-card" style="margin-bottom:2rem;">
        <div class="adm-card-header">
            <h3><i class="fas fa-calendar-alt"></i> Today's Assigned Areas</h3>
        </div>
        <div class="adm-card-body" style="padding:0;">
            <table style="width:100%;text-align:left;border-collapse:collapse;">
                <thead>
                    <tr style="background:var(--surface-2);border-bottom:2px solid var(--border);">
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Area / Ward</th>
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Time</th>
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Type</th>
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $scheds = dbSelect($conn, "SELECT * FROM cleaning_schedules WHERE assigned_to=? AND schedule_date=? ORDER BY start_time ASC", "is", [$staff_id, date('Y-m-d')]);
                    if(empty($scheds)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:3rem;color:var(--text-muted);">No specific areas scheduled for today.</td></tr>
                    <?php else: foreach($scheds as $sc): 
                        $st = $sc['status'];
                        $bg = ['scheduled'=>'var(--info)','in progress'=>'var(--warning)','completed'=>'var(--success)','missed'=>'var(--danger)'][$st] ?? 'var(--text-muted)';
                    ?>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:1.5rem;font-weight:600;font-size:1.3rem;"><i class="fas fa-map-marker-alt" style="color:var(--text-muted);"></i> <?=e($sc['ward_room_area'])?></td>
                            <td style="padding:1.5rem;font-size:1.3rem;"><?=date('H:i',strtotime($sc['start_time']))?> - <?=date('H:i',strtotime($sc['end_time']))?></td>
                            <td style="padding:1.5rem;"><span class="adm-badge" style="background:#f0f0f0;color:#333;font-weight:600;"><?=ucwords(e($sc['cleaning_type']))?></span></td>
                            <td style="padding:1.5rem;">
                                <span class="adm-badge" style="background:<?=$bg?>20;color:<?=$bg?>;"><?=ucwords($st)?></span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Logs -->
    <div class="adm-card">
        <div class="adm-card-header">
            <h3><i class="fas fa-clipboard-check"></i> Recent Activity Logs</h3>
        </div>
        <div class="adm-card-body" style="padding:0;">
            <table style="width:100%;text-align:left;border-collapse:collapse;">
                <thead>
                    <tr style="background:var(--surface-2);border-bottom:2px solid var(--border);">
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Area</th>
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Type</th>
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Time In/Out</th>
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $logs = dbSelect($conn, "SELECT * FROM cleaning_logs WHERE staff_id=? ORDER BY id DESC LIMIT 10", "i", [$staff_id]);
                    if(empty($logs)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:3rem;color:var(--text-muted);">No cleaning logs recorded.</td></tr>
                    <?php else: foreach($logs as $L): 
                        $san = $L['sanitation_status'];
                        $sbg = ['clean'=>'var(--success)','contaminated'=>'var(--danger)','pending inspection'=>'var(--warning)'][$san] ?? 'var(--text-muted)';
                    ?>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:1.5rem;font-weight:600;font-size:1.3rem;"><?=e($L['ward_room_area'])?></td>
                            <td style="padding:1.5rem;font-size:1.3rem;color:var(--text-secondary);"><?=e(ucfirst($L['cleaning_type']))?></td>
                            <td style="padding:1.5rem;font-size:1.3rem;">
                                <?=date('H:i',strtotime($L['started_at']))?> 
                                <?php if($L['completed_at']): ?> - <?=date('H:i',strtotime($L['completed_at']))?><?php endif; ?>
                            </td>
                            <td style="padding:1.5rem;">
                                <span class="adm-badge" style="background:<?=$sbg?>20;color:<?=$sbg?>;">
                                    <?=ucwords($san)?>
                                </span>
                                <?php if((int)$L['issues_reported']===1): ?>
                                    <i class="fas fa-exclamation-triangle" style="color:var(--danger);margin-left:.5rem;" title="Issue Reported"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Log -->
<div class="modal-bg" id="addCleanLogModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-broom"></i> Record Cleaning Log</h3>
            <button class="modal-close" onclick="closeModal('addCleanLogModal')"><i class="fas fa-times"></i></button>
        </div>
        <form onsubmit="event.preventDefault(); showToast('Feature in development for Phase 5 Database Integration.','info'); closeModal('addCleanLogModal');">
            <div class="form-group">
                <label>Ward / Room / Area *</label>
                <input type="text" class="form-control" required placeholder="e.g. Ward A, ICU Bed 3, Main Corridor">
            </div>
            <div class="form-group">
                <label>Cleaning Type *</label>
                <select class="form-control" required>
                    <option value="">Select Protocol</option>
                    <option value="routine">Routine Cleaning</option>
                    <option value="deep clean">Deep Clean / Terminal</option>
                    <option value="biohazard">Biohazard / Spill Cleanup</option>
                    <option value="post-discharge">Post-Discharge Room Prep</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Time Started</label><input type="time" class="form-control" required></div>
                <div class="form-group"><label>Time Finished</label><input type="time" class="form-control" required></div>
            </div>
            <div class="form-group">
                <label>Sanitation Status</label>
                <select class="form-control">
                    <option value="clean">Clean & Cleared</option>
                    <option value="pending inspection">Pending Supervisor Inspection</option>
                    <option value="contaminated">Contamination Issue (Escalate)</option>
                </select>
            </div>
            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;padding:1rem;font-size:1.4rem;">Save Log</button>
        </form>
    </div>
</div>
