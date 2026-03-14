<?php
/**
 * tab_notifications.php
 * Module: System and Actionable Notifications
 */
?>
<div id="sec-notifications" class="dash-section <?=($active_tab==='notifications')?'active':''?>">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
        <h2 style="font-size:2.2rem;font-weight:700;color:var(--text-primary);"><i class="fas fa-bell" style="color:var(--role-accent);"></i> Notifications</h2>
    </div>

    <!-- The actual list -->
    <div class="adm-card">
        <div class="adm-card-body" style="padding:0;">
            <?php
            $all_notifs = dbSelect($conn, "SELECT * FROM staff_notifications WHERE staff_id=? ORDER BY id DESC LIMIT 50", "i", [$staff_id]);
            if(empty($all_notifs)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:5rem 2rem;font-size:1.4rem;">You have no notifications at this time.</p>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;">
                <?php foreach($all_notifs as $n): 
                    $is_unread = (int)$n['is_read'] === 0;
                    $bg = $is_unread ? 'background:rgba(79,70,229,.05);' : 'background:var(--surface);';
                    $border = $is_unread ? 'border-left:4px solid var(--role-accent);' : 'border-left:4px solid transparent;';
                    
                    $iconMap = [
                        'task'=>'fa-check-circle', 'alert'=>'fa-exclamation-triangle', 'shift'=>'fa-calendar-alt',
                        'emergency'=>'fa-ambulance', 'system'=>'fa-cog', 'message'=>'fa-envelope', 'maintenance'=>'fa-tools'
                    ];
                    $i_class = $iconMap[$n['type']] ?? 'fa-info-circle';
                    $i_color = ($n['type']==='emergency'||$n['type']==='alert') ? 'var(--danger)' : 'var(--role-accent)';
                ?>
                    <div style="padding:1.5rem 2rem;border-bottom:1px solid var(--border);<?=$bg?><?=$border?>display:flex;gap:1.5rem;align-items:flex-start;">
                        <i class="fas <?=$i_class?>" style="font-size:2rem;color:<?=$i_color?>;margin-top:.3rem;"></i>
                        <div style="flex:1;">
                            <p style="margin:0 0 .5rem 0;font-size:1.4rem;color:var(--text-primary);font-weight:<?=$is_unread?'600':'400'?>;">
                                <?=e($n['message'])?>
                            </p>
                            <span style="font-size:1.1rem;color:var(--text-secondary);">
                                <?=date('d M Y, h:i A', strtotime($n['created_at']))?> 
                                &bull; <?=ucwords(e($n['type']))?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
