<?php
/**
 * tab_notifications.php — Module 10: Notifications (Modernized)
 */
$all_notifs = dbSelect($conn, "SELECT * FROM staff_notifications WHERE staff_id=? ORDER BY notification_id DESC LIMIT 100", "i", [$staff_id]);
$unread_count = count(array_filter($all_notifs, fn($n) => !$n['is_read']));
?>
<div id="sec-notifications" class="dash-section">
    
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2.5rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-bell" style="color:var(--role-accent);"></i> Activity Stream</h2>
            <p style="font-size:1.3rem;color:var(--text-muted);margin:0.5rem 0 0;">Stay updated with latest assignments and alerts</p>
        </div>
        <?php if($unread_count > 0): ?>
            <button class="btn btn-outline" onclick="notifMarkAllRead()" style="font-weight:700;">
                <span class="btn-text"><i class="fas fa-check-double"></i> Mark All as Read</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Notification Feed -->
    <div class="card" style="overflow:hidden; background:rgba(255,255,255,0.02); backdrop-filter:blur(10px);">
        <div class="card-body" style="padding:0;">
            <?php if(empty($all_notifs)): ?>
                <div style="text-align:center; padding:10rem 2rem;">
                    <div style="width:120px; height:120px; border-radius:50%; background:var(--surface-2); display:flex; align-items:center; justify-content:center; margin:0 auto 2.5rem;">
                        <i class="fas fa-bell-slash" style="font-size:5rem; opacity:.15;"></i>
                    </div>
                    <h3 style="font-size:2rem; font-weight:700; color:var(--text-secondary);">Silence is Golden</h3>
                    <p style="font-size:1.4rem; color:var(--text-muted); margin-top:1rem;">You have no notifications or alerts at this time.</p>
                </div>
            <?php else: 
                $icon_map = [
                    'task'        => ['icon'=>'fa-thumbtack', 'color'=>'#2F80ED'],
                    'alert'       => ['icon'=>'fa-exclamation-triangle', 'color'=>'#E67E22'],
                    'shift'       => ['icon'=>'fa-calendar-alt', 'color'=>'#2980B9'],
                    'emergency'   => ['icon'=>'fa-ambulance', 'color'=>'#E74C3C'],
                    'system'      => ['icon'=>'fa-cog', 'color'=>'#95A5A6'],
                    'message'     => ['icon'=>'fa-envelope', 'color'=>'#8E44AD'],
                    'maintenance' => ['icon'=>'fa-tools', 'color'=>'#2F80ED'],
                    'leave'       => ['icon'=>'fa-umbrella-beach', 'color'=>'#27AE60']
                ];
                foreach($all_notifs as $n):
                    $is_unread = !(bool)$n['is_read'];
                    $type = strtolower($n['type'] ?? 'system');
                    $cfg = $icon_map[$type] ?? ['icon'=>'fa-info-circle', 'color'=>'var(--role-accent)'];
            ?>
            <div class="notif-item <?= $is_unread ? 'is-unread' : '' ?>" 
                 data-id="<?= $n['notification_id'] ?>"
                 onclick="notifMarkRead(<?= $n['notification_id'] ?>, this)"
                 style="display:flex; align-items:flex-start; gap:2rem; padding:2rem 2.5rem; border-bottom:1px solid var(--border); cursor:pointer; transition:.2s; position:relative;">
                
                <?php if($is_unread): ?>
                    <div class="unread-glow" style="position:absolute; left:0; top:0; bottom:0; width:4px; background:<?= $cfg['color'] ?>; box-shadow:2px 0 10px <?= $cfg['color'] ?>44;"></div>
                <?php endif; ?>

                <div style="width:50px; height:50px; border-radius:15px; background:color-mix(in srgb, <?= $cfg['color'] ?> 12%, transparent 88%); color:<?= $cfg['color'] ?>; display:flex; align-items:center; justify-content:center; font-size:1.8rem; flex-shrink:0;">
                    <i class="fas <?= $cfg['icon'] ?>"></i>
                </div>

                <div style="flex:1; min-width:0;">
                    <p style="margin:0 0 .6rem 0; font-size:1.4rem; font-weight:<?= $is_unread ? '700' : '500' ?>; color:var(--text-primary); line-height:1.4;"><?= e($n['message']) ?></p>
                    <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap;">
                        <span style="font-size:1.15rem; color:var(--text-muted); font-weight:600;"><i class="far fa-clock"></i> <?= date('d M, h:i A', strtotime($n['created_at'])) ?></span>
                        <span style="background:var(--surface-2); color:var(--text-secondary); padding:.2rem .8rem; border-radius:10px; font-size:1rem; font-weight:800; text-transform:uppercase; letter-spacing:0.02em;"><?= $type ?></span>
                        <?php if($is_unread): ?>
                            <span class="badge" style="background:<?= $cfg['color'] ?>; color:#fff; font-size:0.9rem; font-weight:800; animation: pulse-blue 2s infinite;">NEW</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="opacity:.2;">
                   <i class="fas fa-chevron-right fa-sm"></i>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>
async function notifMarkRead(id, el) {
    if (!el.classList.contains('is-unread')) return;
    
    const res = await doAction({action: 'mark_notification_read', notification_id: id});
    if (res) {
        el.classList.remove('is-unread');
        el.style.background = 'transparent';
        el.querySelector('.unread-glow')?.remove();
        el.querySelector('.badge')?.remove();
        el.querySelector('p').style.fontWeight = '500';
        
        // Update top bar badge if exists
        const navNotifBadge = document.getElementById('notifBadge');
        if (navNotifBadge) {
            let count = parseInt(navNotifBadge.innerText) - 1;
            if (count <= 0) navNotifBadge.remove();
            else navNotifBadge.innerText = count;
        }
    }
}

async function notifMarkAllRead() {
    const res = await doAction({action: 'mark_notification_read', notification_id: 0}, "All alerts marked as seen.");
    if (res) {
        document.querySelectorAll('.notif-item.is-unread').forEach(item => {
            item.classList.remove('is-unread');
            item.style.background = 'transparent';
            item.querySelector('.unread-glow')?.remove();
            item.querySelector('.badge')?.remove();
            item.querySelector('p').style.fontWeight = '500';
        });
        
        const mainBadge = document.getElementById('notifBadge');
        if (mainBadge) mainBadge.remove();
        
        const pageBadge = document.querySelector('.badge-urgent');
        if (pageBadge) pageBadge.remove();
        
        document.querySelector('button[onclick="notifMarkAllRead()"]')?.remove();
    }
}
</script>

<style>
.notif-item:hover { background: rgba(255,255,255,0.05) !important; transform: translateX(5px); }
.notif-item.is-unread { background: rgba(47,128,237,0.03) !important; }

@keyframes pulse-blue {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(47,128,237, 0.4); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(47,128,237, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(47,128,237, 0); }
}

.card { border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
</style>