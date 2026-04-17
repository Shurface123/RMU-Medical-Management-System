<?php
// MODULE 8: NOTIFICATIONS (Redesigned v2) — Real-time + BroadcastReceiver integrated
$all_notifs = [];
$q = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 100");
if ($q) while ($r = mysqli_fetch_assoc($q)) $all_notifs[] = $r;
$unread_count = $stats['unread_notif'];

// Count by type
$typeCounts = [];
foreach ($all_notifs as $n) {
    $t = $n['type'] ?? $n['related_module'] ?? 'general';
    $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
}
?>
<div id="sec-notif_page" class="dash-section">

<style>
.notif-item2{display:flex;align-items:flex-start;gap:1.2rem;padding:1.2rem 1.5rem;border-bottom:1px solid var(--border);cursor:pointer;transition:background .15s;position:relative;}
.notif-item2:hover{background:var(--surface-2);}
.notif-item2.unread{background:var(--primary-light);}
.notif-item2.unread::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--role-accent);border-radius:0 2px 2px 0;}

.notif-icon-wrap2{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.4rem;transition:var(--transition);}

.notif-unread-dot{width:10px;height:10px;border-radius:50%;background:var(--danger);flex-shrink:0;margin-top:6px;animation: pulse-ring 1.5s infinite;}
@keyframes pulse-ring{0%{box-shadow:0 0 0 0 rgba(231,76,60,.4);}70%{box-shadow:0 0 0 8px rgba(231,76,60,0);}100%{box-shadow:0 0 0 0 rgba(231,76,60,0);}}

.notif-type-chip{display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .7rem;border-radius:20px;font-size:1rem;font-weight:600;margin-top:.4rem;}
</style>

  <div class="adm-card">
    <!-- Header -->
    <div class="adm-card-header">
      <h3>
        <i class="fas fa-bell" style="color:var(--warning);"></i>
        Notifications
        <?php if ($unread_count): ?>
        <span class="adm-badge adm-badge-danger" style="margin-left:.5rem;"><?= $unread_count ?> unread</span>
        <?php endif; ?>
      </h3>
      <?php if ($unread_count > 0): ?>
      <button class="btn-icon btn btn-primary btn-sm" onclick="markAllRead2()">
        <span class="btn-text"><i class="fas fa-check-double"></i> Mark All Read</span>
      </button>
      <?php endif; ?>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs" style="padding:.8rem 1.5rem 0;margin-bottom:0;" id="notifFilters2">
      <span class="ftab active" onclick="filterNotifs2('all',this)">All (<?= count($all_notifs) ?>)</span>
      <span class="ftab" onclick="filterNotifs2('unread',this)">Unread (<?= $unread_count ?>)</span>
      <span class="ftab" onclick="filterNotifs2('appointment',this)">Appointments</span>
      <span class="ftab" onclick="filterNotifs2('prescription',this)">Prescriptions</span>
      <span class="ftab" onclick="filterNotifs2('broadcast',this)">Broadcasts</span>
    </div>

    <!-- Notification List -->
    <div id="notifList2">
      <?php if (empty($all_notifs)): ?>
      <div style="text-align:center;padding:4rem;color:var(--text-muted);">
        <i class="fas fa-bell-slash" style="font-size:3rem;opacity:.3;display:block;margin-bottom:1rem;"></i>
        <p style="font-size:1.4rem;">All caught up! No notifications.</p>
      </div>
      <?php else: foreach ($all_notifs as $n):
        $isRead = (int)$n['is_read'];
        $ntype  = $n['type'] ?? $n['related_module'] ?? 'general';
        $iconMap = [
          'appointment'    => ['fa-calendar-check',  '#2980B9', '#EBF5FB'],
          'prescription'   => ['fa-pills',            '#F39C12', '#FEF9E7'],
          'lab'            => ['fa-flask',             '#8e44ad', '#F5EEF8'],
          'payment'        => ['fa-receipt',           '#27AE60', '#EAFAF1'],
          'broadcast'      => ['fa-bullhorn',          '#E74C3C', '#FDEDEC'],
          'medical_record' => ['fa-file-medical',      '#2F80ED', '#EBF3FF'],
        ];
        [$ico, $iconColor, $iconBg] = $iconMap[$ntype] ?? ['fa-bell', 'var(--role-accent)', 'var(--role-accent-light, rgba(142,68,173,.1))'];
        $tabMap = ['appointments'=>'appointments','prescriptions'=>'prescriptions','lab'=>'lab','medical_records'=>'records','billing'=>'billing'];
        $tabTarget = $tabMap[$n['related_module'] ?? ''] ?? '';
      ?>
      <div class="notif-item2 notif-type-<?= htmlspecialchars($ntype) ?> <?= $isRead ? '' : 'unread' ?>"
           data-id="<?= $n['notification_id'] ?>"
           data-read="<?= $isRead ?>"
           onclick="clickNotif2(<?= $n['notification_id'] ?>, '<?= $tabTarget ?>')">
        <!-- Icon -->
        <div class="notif-icon-wrap2" style="background:<?= $iconBg ?>;color:<?= $iconColor ?>;">
          <i class="fas <?= $ico ?>"></i>
        </div>
        <!-- Content -->
        <div style="flex:1;">
          <div style="font-weight:<?= $isRead ? '500' : '700' ?>;font-size:1.35rem;color:var(--text-primary);">
            <?= htmlspecialchars($n['title'] ?? 'Notification') ?>
          </div>
          <div style="font-size:1.2rem;color:var(--text-secondary);margin-top:.2rem;line-height:1.5;">
            <?= htmlspecialchars($n['message']) ?>
          </div>
          <div style="display:flex;align-items:center;gap:.8rem;margin-top:.4rem;flex-wrap:wrap;">
            <span style="font-size:1.05rem;color:var(--text-muted);">
              <i class="fas fa-clock"></i> <?= date('d M Y, g:i A', strtotime($n['created_at'])) ?>
            </span>
            <span class="notif-type-chip" style="background:<?= $iconBg ?>;color:<?= $iconColor ?>;">
              <i class="fas <?= $ico ?>"></i> <?= ucfirst($ntype) ?>
            </span>
            <?php if ($tabTarget): ?>
            <span style="font-size:1.05rem;color:var(--primary);font-weight:600;"><i class="fas fa-arrow-right"></i> View</span>
            <?php endif; ?>
          </div>
        </div>
        <!-- Unread indicator -->
        <?php if (!$isRead): ?>
        <div class="notif-unread-dot"></div>
        <?php endif; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<script>
function filterNotifs2(filter, btn){
  if (btn) {
    document.querySelectorAll('#notifFilters2 .ftab').forEach(f => f.classList.remove('active'));
    btn.classList.add('active');
  }
  document.querySelectorAll('.notif-item2').forEach(n => {
    const cls = [...n.classList];
    if (filter === 'all') { n.style.display = ''; return; }
    if (filter === 'unread') { n.style.display = n.dataset.read === '0' ? '' : 'none'; return; }
    n.style.display = cls.some(c => c === 'notif-type-' + filter) ? '' : 'none';
  });
}

async function clickNotif2(id, tab){
  await patAction({action:'mark_notification_read', id});
  const el = document.querySelector(`.notif-item2[data-id="${id}"]`);
  if (el) {
    el.classList.remove('unread');
    el.dataset.read = '1';
    el.querySelector('.notif-unread-dot')?.remove();
  }
  updateBellBadge();
  if (tab) showTab(tab, document.querySelector(`.adm-nav-item[onclick*="${tab}"]`));
}

async function markAllRead2(){
  const r = await patAction({action:'mark_all_read'});
  if (r.success) {
    toast('All notifications marked as read');
    document.querySelectorAll('.notif-item2').forEach(n => {
      n.classList.remove('unread');
      n.dataset.read = '1';
      n.querySelector('.notif-unread-dot')?.remove();
    });
    updateBellBadge();
  }
}

function updateBellBadge(){
  const unread = document.querySelectorAll('.notif-item2[data-read="0"]').length;
  const badge = document.getElementById('rmuBellBadge');
  if (badge) { badge.textContent = unread; badge.style.display = unread > 0 ? 'flex' : 'none'; }
}
</script>
