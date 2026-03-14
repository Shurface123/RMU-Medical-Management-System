<?php
/**
 * tab_schedule.php
 * Module: View shifts and attendance
 */
?>
<div id="sec-schedule" class="dash-section <?=($active_tab==='schedule')?'active':''?>">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
        <h2 style="font-size:2.2rem;font-weight:700;color:var(--text-primary);"><i class="fas fa-calendar-alt" style="color:var(--role-accent);"></i> Shift Schedule</h2>
    </div>

    <div class="adm-card">
        <div class="adm-card-header">
            <h3><i class="fas fa-list"></i> Upcoming & Recent Shifts</h3>
        </div>
        <div class="adm-card-body" style="padding:0;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;text-align:left;">
                    <thead>
                        <tr style="background:var(--surface-2);border-bottom:2px solid var(--border);">
                            <th style="padding:1.5rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:1.2rem;">Date</th>
                            <th style="padding:1.5rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:1.2rem;">Shift Type</th>
                            <th style="padding:1.5rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:1.2rem;">Hours</th>
                            <th style="padding:1.5rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:1.2rem;">Location</th>
                            <th style="padding:1.5rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:1.2rem;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $shifts = dbSelect($conn, "SELECT * FROM staff_shifts WHERE staff_id=? ORDER BY shift_date DESC LIMIT 14", "i", [$staff_id]);
                        if(empty($shifts)): ?>
                            <tr><td colspan="5" style="text-align:center;padding:3rem;color:var(--text-muted);">No shifts assigned in the system.</td></tr>
                        <?php else: foreach($shifts as $s): 
                            $is_today = $s['shift_date'] === date('Y-m-d');
                            $s_type = ucfirst(e($s['shift_type']));
                            
                            $bg = $is_today ? 'background:var(--role-accent-light);' : '';
                            
                            // Status Badge
                            $st_badge = '';
                            switch($s['status']) {
                                case 'scheduled': $st_badge = '<span class="adm-badge" style="background:var(--info-light);color:var(--info);">Scheduled</span>'; break;
                                case 'active': $st_badge = '<span class="adm-badge" style="background:var(--success-light);color:var(--success);">Active</span>'; break;
                                case 'completed': $st_badge = '<span class="adm-badge" style="background:#ddd;color:#555;">Completed</span>'; break;
                                case 'missed': $st_badge = '<span class="adm-badge" style="background:var(--danger-light);color:var(--danger);">Missed</span>'; break;
                                case 'swapped': $st_badge = '<span class="adm-badge" style="background:var(--warning-light);color:var(--warning);">Swapped</span>'; break;
                            }
                        ?>
                            <tr style="border-bottom:1px solid var(--border); <?=$bg?>">
                                <td style="padding:1.5rem;">
                                    <strong style="font-size:1.3rem;display:block;"><?=date('D, M d, Y', strtotime($s['shift_date']))?></strong>
                                    <?php if($is_today): ?><span style="font-size:1.1rem;color:var(--role-accent);font-weight:600;">TODAY</span><?php endif; ?>
                                </td>
                                <td style="padding:1.5rem;font-size:1.3rem;font-weight:500;">
                                    <?php
                                        // Simple icon mapping
                                        if($s_type==='Morning') echo '<i class="fas fa-sun" style="color:#F39C12;width:20px;"></i> ';
                                        if($s_type==='Afternoon') echo '<i class="fas fa-cloud-sun" style="color:#E67E22;width:20px;"></i> ';
                                        if($s_type==='Night') echo '<i class="fas fa-moon" style="color:#2C3E50;width:20px;"></i> ';
                                    ?>
                                    <?=$s_type?>
                                </td>
                                <td style="padding:1.5rem;font-size:1.3rem;font-weight:600;color:var(--text-secondary);">
                                    <?=date('H:i', strtotime($s['start_time']))?> - <?=date('H:i', strtotime($s['end_time']))?>
                                </td>
                                <td style="padding:1.5rem;font-size:1.3rem;">
                                    <i class="fas fa-map-marker-alt" style="color:var(--text-muted);"></i> <?=e($s['location_ward_assigned'])?>
                                </td>
                                <td style="padding:1.5rem;">
                                    <?=$st_badge?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
