<?php
// ============================================================
// DOCTOR DASHBOARD - NOTIFICATIONS TAB
// ============================================================
if (!isset($conn)) exit;

// Fetch full notifications list for the doctor
$notifs = [];
$q_notifs = mysqli_query($conn, "
    SELECT * FROM notifications 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC 
    LIMIT 100
");
if ($q_notifs) {
    while ($r = mysqli_fetch_assoc($q_notifs)) $notifs[] = $r;
}

$unread_count = array_reduce($notifs, fn($c,$n) => $c + ($n['is_read']?0:1), 0);
?>

<div class="dash-section" id="sec-notifications" style="animation:fadeIn 0.4s ease;">
    
    <div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.02)); border:1px solid rgba(47,128,237,0.15); padding: 2.2rem; border-radius: 16px; margin-bottom: 2rem; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.3rem;">
                <i class="fas fa-bell" style="color:var(--primary); margin-right:.8rem;"></i> Notification Center
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Manage your clinical alerts, patient updates, and system notifications.</p>
        </div>
        <div style="display:flex; gap:1rem;">
            <button class="btn btn-primary" onclick="markAllNotificationsRead()" style="border-radius:12px; font-weight:700;"><span class="btn-text">
                <i class="fas fa-check-double"></i> Mark All as Read
            </span></button>
            <button class="btn" onclick="window.location.href='?tab=notifications';" style="background: var(--surface-2); color: var(--text-primary); border-radius:12px; font-weight:700;"><span class="btn-text">
                <i class="fas fa-sync-alt"></i> Refresh
            </span></button>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="adm-tab-group" style="margin-bottom:2rem; border-bottom:none; padding-bottom:1rem; gap:1rem; display:flex;">
        <button class="ftab-v2 active" id="btn-all-notifs" onclick="filterNotifs('all')" style="display:inline-flex;align-items:center;padding:.55rem 1.4rem;border-radius:20px;font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--primary);color:#fff;">
            <i class="fas fa-inbox"></i> All Feed
        </button>
        <button class="ftab-v2" id="btn-unread-notifs" onclick="filterNotifs('unread')" style="display:inline-flex;align-items:center;padding:.55rem 1.4rem;border-radius:20px;font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);">
            <i class="fas fa-envelope"></i> Unread 
            <?php if($unread_count > 0): ?>
                <span class="adm-badge adm-badge-danger" style="margin-left:8px;"><?= $unread_count ?></span>
            <?php endif; ?>
        </button>
        <button class="ftab-v2" id="btn-read-notifs" onclick="filterNotifs('read')" style="display:inline-flex;align-items:center;padding:.55rem 1.4rem;border-radius:20px;font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);">
            <i class="fas fa-envelope-open-text"></i> Read History
        </button>
    </div>

    <!-- Notifications List -->
    <div class="setting-card-v2" style="background:var(--surface);">
        <div style="padding:0;" id="notifListContainer">
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
                            $actionLink = '?tab=records';
                        } elseif (str_contains($nType, 'appointment')) {
                            $nIcon = 'fa-calendar-check';
                            $nColor = 'var(--success)';
                            $bgLight = 'rgba(39, 174, 96, 0.1)';
                            $actionLink = '?tab=appointments';
                        } elseif (str_contains($nType, 'lab') || str_contains($nType, 'result')) {
                            $nIcon = 'fa-flask';
                            $nColor = 'var(--info)';
                            $bgLight = 'rgba(41, 128, 185, 0.1)';
                            $actionLink = '?tab=lab_requests';
                        } elseif (str_contains($nType, 'message') || str_contains($nType, 'note')) {
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
                                        <?= htmlspecialchars($n['message'] ?? 'System Notification') ?>
                                    </h4>
                                    <span style="font-size:1.1rem; color:var(--text-muted); font-weight:600; white-space:nowrap; margin-left:1rem;">
                                        <?= date('M d, g:i A', strtotime($n['created_at'])) ?>
                                    </span>
                                </div>
                                
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:.5rem;">
                                    <span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); padding:.3rem .8rem; font-size:1rem; text-transform:uppercase;">
                                        <?= htmlspecialchars($n['type'] ?? 'System Info') ?>
                                    </span>
                                    
                                    <?php if (!$is_read): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); markNotificationRead(<?= $n['notification_id'] ?>)" style="font-weight:800; border-radius:8px; padding:.4rem 1rem; font-size:1.1rem;">
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
    document.querySelectorAll('#sec-notifications .ftab-v2').forEach(b => {
        b.style.background = 'var(--surface)';
        b.style.color = 'var(--text-secondary)';
    });
    const activeBtn = document.getElementById(`btn-${filter}-notifs`);
    activeBtn.style.background = 'var(--primary)';
    activeBtn.style.color = '#fff';

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
}

function markNotificationRead(id) {
    if (!id) return;
    docAction({ action: 'mark_notif_read', notification_id: id }).then(res => {
        if (res.success) {
            window.location.href = '?tab=notifications';
        } else {
            toast(res.message || 'Failed to update', 'danger');
        }
    });
}

function markAllNotificationsRead() {
    if(confirm('Mark all as read?')) {
        docAction({ action: 'mark_all_notifs_read' }).then(res => {
            if (res.success) {
                window.location.href = '?tab=notifications';
            } else {
                toast(res.message || 'Failed to update', 'danger');
            }
        });
    }
}
</script>
