<?php
/**
 * tab_notifications.php — Module 10: Notifications
 */
$all_notifs = dbSelect($conn,"SELECT * FROM staff_notifications WHERE staff_id=? ORDER BY notification_id DESC LIMIT 60","i",[$staff_id]);
$unread_count = count(array_filter($all_notifs, fn($n) => !$n['is_read']));
?>
<div id="sec-notifications" class="dash-section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
        <h2 style="font-size:2.2rem;font-weight:700;"><i class="fas fa-bell" style="color:var(--role-accent);"></i> Notifications
            <?php if($unread_count>0): ?><span class="badge badge-urgent" style="font-size:1.2rem;margin-left:.8rem;"><?=$unread_count?> new</span><?php endif; ?>
        </h2>
        <?php if($unread_count>0): ?>
        <button class="btn btn-outline btn-sm" onclick="markAllRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body-flush">
            <?php if(empty($all_notifs)): ?>
                <div style="text-align:center;padding:6rem 2rem;">
                    <i class="fas fa-bell-slash" style="font-size:5rem;color:var(--text-muted);display:block;margin-bottom:1.5rem;"></i>
                    <h3 style="color:var(--text-secondary);">No Notifications</h3>
                    <p style="color:var(--text-muted);">You're all caught up!</p>
                </div>
            <?php else: $icon_map=['task'=>'fa-check-circle','alert'=>'fa-exclamation-triangle','shift'=>'fa-calendar-alt','emergency'=>'fa-ambulance','system'=>'fa-cog','message'=>'fa-envelope','maintenance'=>'fa-tools','leave'=>'fa-umbrella-beach']; foreach($all_notifs as $n):
                $is_unread=!(bool)$n['is_read'];
                $ico=$icon_map[$n['type']??'']??'fa-info-circle';
                $ic_color=($n['type']==='emergency'||$n['type']==='alert')?'var(--danger)':'var(--role-accent)';
            ?>
            <div class="notif-row" data-id="<?=$n['notification_id']?>" style="display:flex;align-items:flex-start;gap:1.5rem;padding:1.6rem 2rem;border-bottom:1px solid var(--border);cursor:pointer;<?=$is_unread?'background:var(--role-accent-light);':''?>" onclick="markRead(<?=$n['notification_id']?>, this)">
                <div style="width:42px;height:42px;border-radius:50%;background:color-mix(in srgb,<?=$ic_color?> 15%,#fff 85%);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas <?=$ico?>" style="font-size:1.6rem;color:<?=$ic_color?>;"></i>
                </div>
                <div style="flex:1;">
                    <p style="margin:0 0 .4rem 0;font-size:1.35rem;font-weight:<?=$is_unread?'600':'400'?>;color:var(--text-primary);"><?=e($n['message'])?></p>
                    <div style="display:flex;align-items:center;gap:1rem;font-size:1.1rem;color:var(--text-muted);">
                        <span><i class="far fa-clock"></i> <?=date('d M Y, h:i A',strtotime($n['created_at']))?></span>
                        <span class="badge" style="background:#f0f0f0;color:#555;font-size:1rem;"><?=ucwords(e($n['type']??'system'))?></span>
                        <?php if($is_unread): ?><span class="badge" style="background:var(--role-accent);color:#fff;font-size:.9rem;">NEW</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>
async function markRead(id, el) {
    await doAction({action:'mark_notification_read', notif_id:id});
    el.style.background='var(--surface)';
    const newBadge = el.querySelector('.badge[style*="role-accent"]');
    if(newBadge) newBadge.remove();
    const boldP = el.querySelector('p');
    if(boldP) boldP.style.fontWeight='400';
    const badge = document.getElementById('notifBadge');
    if(badge){ const c=parseInt(badge.textContent)-1; if(c<=0) badge.remove(); else badge.textContent=c; }
}
async function markAllRead() {
    await doAction({action:'mark_notification_read', notif_id:0});
    document.querySelectorAll('.notif-row').forEach(r=>{ r.style.background='var(--surface)'; });
    showToast('All notifications marked as read.','success');
    const badge=document.getElementById('notifBadge'); if(badge) badge.remove();
    document.querySelector('.badge-urgent')?.remove();
}
</script>