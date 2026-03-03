<!-- ════════════════════════════════════════════════════════════
     NOTIFICATIONS TAB
     ════════════════════════════════════════════════════════════ -->
<div id="sec-notifications" class="dash-section <?=($active_tab==='notifications')?'active':''?>">

  <div class="sec-header">
    <h2><i class="fas fa-bell"></i> Notifications</h2>
    <div style="display:flex;gap:.8rem;">
      <?php if($stats['unread_notifs']>0):?>
      <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="markAllRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
      <?php endif;?>
    </div>
  </div>

  <div class="filter-tabs">
    <button class="ftab active" onclick="filterNotifs('all',this)">All</button>
    <button class="ftab" onclick="filterNotifs('unread',this)">Unread (<?=$stats['unread_notifs']?>)</button>
    <button class="ftab" onclick="filterNotifs('read',this)">Read</button>
  </div>

  <div id="notifList">
    <?php if(empty($notifs)):?>
    <div class="adm-card" style="text-align:center;padding:3rem;">
      <i class="fas fa-bell-slash" style="font-size:3rem;color:var(--text-muted);display:block;margin-bottom:1rem;"></i>
      <p style="color:var(--text-muted);font-size:1.4rem;">No notifications yet</p>
    </div>
    <?php else: foreach($notifs as $n):
      $isRead=(int)($n['is_read']??0);
      $iconMap=['prescription'=>'fa-prescription-bottle-medical','stock'=>'fa-boxes-stacked','alert'=>'fa-triangle-exclamation','dispensing'=>'fa-hand-holding-medical','appointment'=>'fa-calendar-check','system'=>'fa-gear','refill'=>'fa-rotate'];
      $colorMap=['prescription'=>'primary','stock'=>'success','alert'=>'warning','dispensing'=>'info','appointment'=>'primary','system'=>'info','refill'=>'warning'];
      $nType=strtolower($n['type']??$n['related_module']??'system');
      $nIcon=$iconMap[$nType]??'fa-bell';
      $nColor=$colorMap[$nType]??'primary';
    ?>
    <div class="alert-card" data-read="<?=$isRead?'read':'unread'?>" style="<?=$isRead?'opacity:.7;':'border-left:3px solid var(--role-accent);'?>">
      <div class="alert-icon" style="background:var(--<?=$nColor?>-light);color:var(--<?=$nColor?>);"><i class="fas <?=$nIcon?>"></i></div>
      <div style="flex:1;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;">
          <div>
            <p style="font-size:1.3rem;font-weight:<?=$isRead?'400':'600'?>;margin:0 0 .3rem;">
              <?=htmlspecialchars($n['message']??$n['content']??'Notification')?>
            </p>
            <span style="font-size:1.1rem;color:var(--text-muted);"><i class="fas fa-clock" style="margin-right:.3rem;"></i><?=date('d M Y, g:i A',strtotime($n['created_at']))?></span>
          </div>
          <?php if(!$isRead):?>
          <button class="adm-btn adm-btn-sm" onclick="markRead(<?=$n['id']?>)" style="background:var(--role-accent-light);color:var(--role-accent);border-color:var(--role-accent);flex-shrink:0;" title="Mark as read"><i class="fas fa-check"></i></button>
          <?php endif;?>
        </div>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>
</div>

<script>
function filterNotifs(filter,btn){
  document.querySelectorAll('.filter-tabs .ftab').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  document.querySelectorAll('#notifList .alert-card').forEach(card=>{
    card.style.display=(filter==='all'||card.dataset.read===filter)?'':'none';
  });
}

async function markRead(id){
  const r=await pharmAction({action:'mark_notification_read',notification_id:id});
  if(r.success){toast('Marked as read');setTimeout(()=>location.reload(),600);}
  else toast(r.message||'Error','danger');
}

async function markAllRead(){
  const r=await pharmAction({action:'mark_all_notifications_read'});
  if(r.success){toast('All marked as read');setTimeout(()=>location.reload(),600);}
  else toast(r.message||'Error','danger');
}
</script>
