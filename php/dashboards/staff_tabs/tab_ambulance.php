<?php
/**
 * tab_ambulance.php
 * Module: Transport & Vehicles (Only visible to Ambulance Drivers)
 */
?>
<div id="sec-ambulance" class="dash-section <?=($active_tab==='ambulance')?'active':''?>">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
        <h2 style="font-size:2.2rem;font-weight:700;color:var(--text-primary);"><i class="fas fa-ambulance" style="color:var(--role-accent);"></i> Emergency Transport</h2>
    </div>

    <!-- Active Trips Map / Card View -->
    <div class="adm-card" style="margin-bottom:2rem;">
        <div class="adm-card-header">
            <h3><i class="fas fa-route"></i> Dispatched Trips</h3>
        </div>
        <div class="adm-card-body" style="padding:0;">
            <table style="width:100%;text-align:left;border-collapse:collapse;">
                <thead>
                    <tr style="background:var(--surface-2);border-bottom:2px solid var(--border);">
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Trip Details</th>
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">From &rarr; To</th>
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Time</th>
                        <th style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Status</th>
                        <th style="padding:1.5rem;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $trips = dbSelect($conn, "SELECT t.*, v.registration_number FROM ambulance_trips t LEFT JOIN vehicles v ON t.vehicle_id = v.vehicle_id WHERE t.driver_id=? AND DATE(t.created_at)=? ORDER BY t.trip_id DESC", "is", [$staff_id, date('Y-m-d')]);
                    if(empty($trips)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:3rem;color:var(--text-muted);">No trips dispatched today.</td></tr>
                    <?php else: foreach($trips as $tr): 
                        $st = $tr['trip_status'];
                        $bg = ['requested'=>'var(--danger)','en route'=>'var(--warning)','patient onboard'=>'var(--info)','arrived'=>'var(--success)','completed'=>'var(--text-muted)'][$st] ?? 'var(--text-muted)';
                    ?>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:1.5rem;">
                                <strong style="font-size:1.4rem;display:block;">Req: #<?=sprintf('%05d', $tr['trip_id'])?></strong>
                                <span class="adm-badge" style="background:#eee;color:#333;margin-top:.5rem;"><i class="fas fa-car"></i> <?=e($tr['registration_number']??'Assigning...')?></span>
                            </td>
                            <td style="padding:1.5rem;">
                                <div style="font-size:1.3rem;margin-bottom:.5rem;"><strong>A:</strong> <?=e($tr['pickup_location'])?></div>
                                <div style="font-size:1.3rem;"><strong>B:</strong> <?=e($tr['destination'])?></div>
                            </td>
                            <td style="padding:1.5rem;font-size:1.3rem;">
                                Acc: <?= $tr['accepted_at'] ? date('H:i', strtotime($tr['accepted_at'])) : '--:--' ?><br>
                                Arr: <?= $tr['arrived_at'] ? date('H:i', strtotime($tr['arrived_at'])) : '--:--' ?>
                            </td>
                            <td style="padding:1.5rem;">
                                <span class="adm-badge" style="background:<?=$bg?>20;color:<?=$bg?>;font-weight:700;"><i class="fas fa-circle" style="font-size:1rem;margin-right:.4rem;"></i> <?=ucwords($st)?></span>
                            </td>
                            <td style="padding:1.5rem;">
                                <?php if($st !== 'completed' && $st !== 'cancelled'): ?>
                                    <button class="adm-btn adm-btn-outline" onclick="showToast('Status transitions coming in Phase 5','info')">Update Status</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
