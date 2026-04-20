<?php
// ============================================================
// LAB DASHBOARD - NOTIFICATIONS TAB
// ============================================================
if (!isset($conn)) exit;

// Fetch notifications for the lab technician
$notifs = [];
$q_notifs = mysqli_query($conn, "
    SELECT * FROM lab_notifications 
    WHERE recipient_id = $user_id 
    ORDER BY created_at DESC 
    LIMIT 100
");
if ($q_notifs) {
    while ($r = mysqli_fetch_assoc($q_notifs)) $notifs[] = $r;
}
?>

<div class="tab-content <?= ($active_tab === 'notifications') ? 'active' : '' ?>" id="notifications" style="animation:fadeIn 0.4s ease;">
    
    <div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.02)); border:1px solid rgba(47,128,237,0.15); padding: 2.2rem; border-radius: 16px; margin-bottom: 2rem; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.3rem;">
                <i class="fas fa-bell" style="color:var(--role-accent); margin-right:.8rem;"></i> Notification Center
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Manage your lab orders, alerts, messages, and system notifications.</p>
        </div>
        <div style="display:flex; gap:1rem;">
            <?php if ($unread_notifs > 0): ?>
                <button class="adm-btn adm-adm-btn adm-btn-primary" onclick="markAllNotificationsRead()" style="border-radius:12px; font-weight:700;"><span class="btn-text">
                    <i class="fas fa-check-double"></i> Mark All as Read
                </span></button>
            <?php endif; ?>
            <button class="adm-btn" onclick="window.location.href='?tab=notifications';" style="background: var(--surface-2); color: var(--text-primary); border-radius:12px; font-weight:700;"><span class="btn-text">
                <i class="fas fa-sync-alt"></i> Refresh
            </span></button>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="adm-tab-group" style="margin-bottom:2rem; border-bottom:none; padding-bottom:1rem; gap:1rem;">
        <button class="ftab active" id="btn-all-notifs" onclick="filterNotifs('all')">
            <i class="fas fa-inbox"></i> All Feed
        </button>
        <button class="ftab" id="btn-unread-notifs" onclick="filterNotifs('unread')" style="display:flex; align-items:center;">
            <i class="fas fa-envelope"></i> Unread 
            <?php if($unread_notifs > 0): ?>
            <span class="adm-badge pulse-fade" style="background:var(--danger); color:#fff; border-radius:20px; font-size:.9rem; font-weight:800; padding:2px 8px; margin-left:.5rem;"><?= $unread_notifs ?></span>
            <?php endif; ?>
        </button>
        <button class="ftab" id="btn-read-notifs" onclick="filterNotifs('read')">
            <i class="fas fa-envelope-open-text"></i> Read History
        </button>
    </div>

    <!-- Notifications List -->
    <div class="adm-card shadow-sm" style="background:var(--surface);">
        <div class="adm-card-body" style="padding:0;" id="notifListContainer">
            <?php if (empty($notifs)): ?>
                <div style="padding:6rem 2rem; text-align:center; color:var(--text-muted);">
                    <i class="fas fa-bell-slash" style="font-size:5rem; opacity:0.1; display:block; margin-bottom:1.5rem;"></i>
                    <h3 style="font-weight:800; font-size:1.6rem; color:var(--text-primary); margin:0;">All Caught Up!</h3>
                    <p style="font-size:1.2rem; margin-top:.5rem;">Your notification feed is completely clear.</p>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column;">
                    <?php 
                    foreach ($notifs as $n): 
                        $is_read = (int)($n['is_read'] ?? 0);
                        
                        // Determine Icon and Color based on notification type
                        $nType = strtolower($n['type'] ?? $n['related_module'] ?? 'system');
                        $nIcon = 'fa-bell';
                        $nColor = 'var(--primary)';
                        $bgLight = 'var(--primary-light)';
                        $actionLink = '#';
                        
                        if (str_contains($nType, 'urgent') || str_contains($nType, 'critical') || str_contains($nType, 'alert')) {
                            $nIcon = 'fa-exclamation-triangle';
                            $nColor = 'var(--danger)';
                            $bgLight = 'rgba(231,76,60,0.1)';
                            if(str_contains($nType, 'equipment')) $actionLink = '?tab=equipment';
                            elseif(str_contains($nType, 'reagent')) $actionLink = '?tab=inventory';
                            else $actionLink = '?tab=results';
                        } elseif (str_contains($nType, 'order') || str_contains($nType, 'ready')) {
                            $nIcon = 'fa-microscope';
                            $nColor = 'var(--role-accent)';
                            $bgLight = 'var(--role-accent-light)';
                            $actionLink = '?tab=orders';
                        } elseif (str_contains($nType, 'message')) {
                            $nIcon = 'fa-envelope-open-text';
                            $nColor = 'var(--warning)';
                            $bgLight = 'rgba(241,196,15,0.1)';
                            $actionLink = '?tab=messages';
                        }
                    ?>
                        <div class="notif-item" data-read="<?= $is_read ? 'true' : 'false' ?>" 
                             style="display:flex; align-items:flex-start; gap:1.5rem; padding:1.8rem 2rem; border-bottom:1px solid var(--border); background:<?= $is_read ? 'transparent' : 'rgba(47, 128, 237, 0.03)' ?>; transition:all 0.2s; cursor: pointer;"
                             onclick="window.location.href='<?= $actionLink ?>';">
                            
                            <div style="width:48px; height:48px; border-radius:12px; background:<?= $bgLight ?>; color:<?= $nColor ?>; display:flex; align-items:center; justify-content:center; font-size:1.8rem; flex-shrink:0;">
                                <i class="fas <?= $nIcon ?>"></i>
                            </div>
                            
                            <div style="flex:1;">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                    <h4 style="font-weight:<?= $is_read ? '600' : '900' ?>; color:<?= $is_read ? 'var(--text-secondary)' : 'var(--text-primary)' ?>; font-size:1.4rem; margin:0 0 .5rem; line-height:1.4;">
                                        <?= e($n['message'] ?? 'System Notification') ?>
                                    </h4>
                                    <span style="font-size:1.1rem; color:var(--text-muted); font-weight:600; white-space:nowrap; margin-left:1rem;">
                                        <?= date('M d, g:i A', strtotime($n['created_at'])) ?>
                                    </span>
                                </div>
                                
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:.5rem;">
                                    <span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); padding:.3rem .8rem; font-size:1rem; text-transform:uppercase;">
                                        <?= e($n['type'] ?? 'System Info') ?>
                                    </span>
                                    
                                    <?php if (!$is_read): ?>
                                        <button class="adm-btn adm-btn-sm" onclick="event.stopPropagation(); markNotificationRead(<?= $n['id'] ?>)" style="background:transparent; color:var(--primary); font-weight:800; border:2px solid var(--primary); border-radius:8px; padding:.4rem 1rem; font-size:1.1rem;">
                                            <i class="fas fa-check"></i> Mark as Read
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function filterNotifs(filter) {
    $('.ftab').removeClass('active');
    $(`#btn-${filter}-notifs`).addClass('active');

    const items = document.querySelectorAll('.notif-item');
    items.forEach(el => {
        const isRead = el.getAttribute('data-read') === 'true';
        if (filter === 'all') {
            el.style.display = 'flex';
        } else if (filter === 'unread') {
            el.style.display = !isRead ? 'flex' : 'none';
        } else if (filter === 'read') {
            el.style.display = isRead ? 'flex' : 'none';
        }
    });

    if (filter === 'unread') {
        let unreadCount = 0;
        items.forEach(el => { if(el.getAttribute('data-read') === 'false') unreadCount++; });
        if (unreadCount === 0) {
            $('#noUnreadMsg').remove();
            $('#notifListContainer').append('<div id="noUnreadMsg" style="padding:4rem; text-align:center; color:var(--text-muted); font-size:1.3rem; font-weight:600;">No unread notifications!</div>');
        } else {
            $('#noUnreadMsg').remove();
        }
    } else {
        $('#noUnreadMsg').remove();
    }
}

function markNotificationRead(id) {
    if (!id) return;
    $.post('lab_actions.php', {
        action: 'mark_notif_read',
        notification_id: id,
        _csrf: '<?= e($csrf_token) ?>'
    }, function(res) {
        if (res.success) {
            window.location.href = '?tab=notifications';
        } else {
            alert('Error: ' + (res.message || 'Failed to update.'));
        }
    }, 'json');
}

function markAllNotificationsRead() {
    if(confirm('Mark all as read?')) {
        $.post('lab_actions.php', {
            action: 'mark_all_notifs_read',
            _csrf: '<?= e($csrf_token) ?>'
        }, function(res) {
            if (res.success) {
                window.location.href = '?tab=notifications';
            } else {
                alert('Error: ' + (res.message || 'Failed to update.'));
            }
        }, 'json');
    }
}
</script>
