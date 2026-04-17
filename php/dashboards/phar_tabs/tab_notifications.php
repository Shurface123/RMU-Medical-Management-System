<!-- ════════════════════════════════════════════════════════════
     NOTIFICATIONS TAB
     ════════════════════════════════════════════════════════════ -->
<div id="sec-notifications" class="dash-section <?=($active_tab==='notifications')?'active':''?>">

  <div class="sec-header">
    <div style="display:flex;align-items:center;gap:1.5rem;">
        <div style="width:50px;height:50px;border-radius:15px;background:var(--role-accent-light);color:var(--role-accent);display:flex;align-items:center;justify-content:center;font-size:1.8rem;position:relative;">
            <i class="fas fa-bell"></i>
            <?php if($stats['unread_notifs']>0):?>
            <span style="position:absolute;top:-5px;right:-5px;background:var(--danger);color:#fff;font-size:.9rem;font-weight:700;padding:.2rem .5rem;border-radius:20px;border:2px solid #fff;"><?=$stats['unread_notifs']?></span>
            <?php endif;?>
        </div>
        <div>
            <h2 style="margin:0;font-size:2rem;font-weight:700;">Notifications</h2>
            <p style="margin:.3rem 0 0;color:var(--text-muted);font-size:1.1rem;">System alerts and updates</p>
        </div>
    </div>
    <div style="display:flex;gap:.8rem;">
      <?php if($stats['unread_notifs']>0):?>
      <button class="btn-icon btn btn-primary btn-sm" onclick="markAllRead()" style="background:var(--primary);color:#fff;border-radius:8px;padding:.8rem 1.2rem;"><span class="btn-text"><i class="fas fa-check-double"></i> Mark All Read</span></button>
      <?php endif;?>
    </div>
  </div>

  <div class="filter-tabs" style="background:var(--bg-card);padding:.5rem;border-radius:12px;display:inline-flex;box-shadow:0 2px 10px rgba(0,0,0,0.05);margin-bottom:2rem;">
    <button class="btn btn-primary ftab active" onclick="filterNotifCards('all',this)" style="border-radius:8px;"><span class="btn-text">All</span></button>
    <button class="btn btn-warning btn-icon ftab" onclick="filterNotifCards('unread',this)" style="border-radius:8px;background:transparent;color:var(--text-color);border:none;"><span class="btn-text">Unread (<?=$stats['unread_notifs']?>)</span></button>
    <button class="btn btn-primary ftab" onclick="filterNotifCards('read',this)" style="border-radius:8px;background:transparent;color:var(--text-color);border:none;"><span class="btn-text">Read</span></button>
  </div>

  <div id="notifList" style="display:flex;flex-direction:column;gap:1.2rem;">
    <?php if(empty($notifs)):?>
    <div class="adm-card" style="text-align:center;padding:4rem;background:var(--bg-card);border:1px dashed var(--border);border-radius:16px;">
      <i class="fas fa-bell-slash" style="font-size:4rem;color:var(--text-muted);display:block;margin-bottom:1rem;opacity:0.5;"></i>
      <h3 style="margin:0 0 .5rem;font-size:1.5rem;font-weight:700;">All Caught Up!</h3>
      <p style="color:var(--text-muted);font-size:1.2rem;margin:0;">You have no new notifications.</p>
    </div>
    <?php else: foreach($notifs as $n):
      $isRead=(int)($n['is_read']??0);
      $iconMap=['prescription'=>'fa-prescription-bottle-medical','stock'=>'fa-boxes-stacked','alert'=>'fa-triangle-exclamation','dispensing'=>'fa-hand-holding-medical','appointment'=>'fa-calendar-check','system'=>'fa-gear','refill'=>'fa-rotate'];
      $colorMap=['prescription'=>'primary','stock'=>'success','alert'=>'danger','dispensing'=>'info','appointment'=>'primary','system'=>'warning','refill'=>'warning'];
      $nType=strtolower($n['type']??$n['related_module']??'system');
      $nIcon=$iconMap[$nType]??'fa-bell';
      $nColor=$colorMap[$nType]??'primary';
      
      // Calculate time ago
      $time = strtotime($n['created_at']);
      $diff = time() - $time;
      if ($diff < 60) $time_ago = 'Just now';
      elseif ($diff < 3600) $time_ago = floor($diff/60) . ' mins ago';
      elseif ($diff < 86400) $time_ago = floor($diff/3600) . ' hours ago';
      elseif ($diff < 604800) $time_ago = floor($diff/86400) . ' days ago';
      else $time_ago = date('d M Y', $time);
    ?>
    <div class="alert-card notif-item" data-status="<?=$isRead?'read':'unread'?>" style="display:flex;align-items:flex-start;gap:1.5rem;background:var(--bg-card);padding:1.5rem;border-radius:12px;border:1px solid var(--border);box-shadow:0 4px 15px rgba(0,0,0,0.02);transition:all .3s;<?=$isRead?'opacity:0.7;':'border-left:4px solid var(--'.$nColor.');'?>">
      <div style="width:50px;height:50px;border-radius:50%;background:var(--<?=$nColor?>-light);color:var(--<?=$nColor?>);display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0;">
          <i class="fas <?=$nIcon?>"></i>
      </div>
      <div style="flex:1;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;">
            <div>
                <span class="adm-badge adm-badge-<?=$nColor?>" style="margin-bottom:.5rem;display:inline-block;padding:.3rem .6rem;border-radius:6px;"><?=ucfirst($nType)?></span>
                <p style="font-size:1.3rem;font-weight:<?=$isRead?'500':'700'?>;color:var(--text-color);margin:0 0 .5rem;line-height:1.4;">
                  <?=htmlspecialchars($n['message']??$n['content']??'Notification')?>
                </p>
                <div style="display:flex;align-items:center;gap:.5rem;color:var(--text-muted);font-size:1.1rem;font-weight:500;">
                    <i class="fas fa-clock"></i> <?=$time_ago?> • <span style="opacity:0.7;"><?=date('g:i A', $time)?></span>
                </div>
            </div>
            <?php if(!$isRead):?>
            <button class="btn-icon" onclick="markRead(<?=$n['id']?>)" style="background:var(--primary-light);color:var(--primary);border:none;border-radius:8px;padding:.5rem .8rem;cursor:pointer;transition:all .2s;" title="Mark as read"><i class="fas fa-check"></i></button>
            <?php endif;?>
        </div>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>
</div>

<script>
function filterNotifCards(filter,btn){
  document.querySelectorAll('.filter-tabs .ftab').forEach(b=>{
      b.classList.remove('active');
      b.style.background='transparent';
      b.style.color='var(--text-color)';
      b.style.boxShadow='none';
  });
  if(btn){
      btn.classList.add('active');
      btn.style.background='var(--primary)';
      btn.style.color='#fff';
      btn.style.boxShadow='0 4px 12px rgba(var(--primary-rgb),0.3)';
  }
  document.querySelectorAll('#notifList .notif-item').forEach(card=>{
    card.style.display=(filter==='all'||card.dataset.status===filter)?'flex':'none';
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
