<!-- ═══════════════ MODULE 11: NOTIFICATIONS ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-bell" style="color:var(--role-accent);margin-right:.6rem;"></i> Notifications</h1>
    <p>System alerts, order updates, and communication notices</p>
  </div>
  <button class="adm-btn adm-btn-primary" onclick="markAllRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
</div>

<div class="filter-tabs">
  <span class="ftab active" onclick="filterNotifs('all',this)">All (<?=count($notifs)?>)</span>
  <span class="ftab" onclick="filterNotifs('unread',this)">Unread (<?=$stats['unread_notifs']?>)</span>
  <span class="ftab" onclick="filterNotifs('read',this)">Read</span>
</div>

<div id="notifList">
<?php if(empty($notifs)):?>
<div class="adm-card"><div class="adm-card-body" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-bell-slash" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>No notifications</div></div>
<?php else: foreach($notifs as $n):
  $type_icons=['order'=>'clipboard-list','result'=>'microscope','critical'=>'exclamation-triangle','equipment'=>'tools','reagent'=>'prescription-bottle','system'=>'cog','message'=>'comments','qc'=>'check-double'];
  $type_colors=['order'=>'var(--info)','result'=>'var(--role-accent)','critical'=>'var(--danger)','equipment'=>'var(--warning)','reagent'=>'var(--success)','system'=>'var(--text-muted)','message'=>'var(--info)','qc'=>'var(--warning)'];
  $icon=$type_icons[$n['type']??'system']??'bell';
  $color=$type_colors[$n['type']??'system']??'var(--text-muted)';
?>
<div class="adm-card" style="margin-bottom:.8rem;<?=!$n['is_read']?'border-left:4px solid var(--role-accent);background:var(--primary-light);':''?>" data-read="<?=$n['is_read']?'read':'unread'?>">
  <div class="adm-card-body">
    <div style="display:flex;align-items:flex-start;gap:1.2rem;">
      <div style="width:46px;height:46px;border-radius:12px;background:<?=$color?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;<?=$n['type']==='critical'?'animation:pulse-emergency 2s infinite;':''?>"><i class="fas fa-<?=$icon?>"></i></div>
      <div style="flex:1;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem;">
          <strong style="font-size:1.35rem;"><?=e($n['title']??'Notification')?></strong>
          <span style="color:var(--text-muted);font-size:1.05rem;white-space:nowrap;"><?=date('d M, h:i A',strtotime($n['created_at']))?></span>
        </div>
        <p style="margin:0;color:var(--text-secondary);font-size:1.25rem;"><?=e($n['message'])?></p>
        <?php if($n['action_url']):?><a href="<?=e($n['action_url'])?>" class="adm-btn adm-btn-sm adm-btn-ghost" style="margin-top:.5rem;"><i class="fas fa-arrow-right"></i> View</a><?php endif;?>
      </div>
      <?php if(!$n['is_read']):?><button class="adm-btn adm-btn-sm adm-btn-ghost" onclick="markNotifRead(<?=$n['id']?>,this)" title="Mark Read"><i class="fas fa-check"></i></button><?php endif;?>
    </div>
  </div>
</div>
<?php endforeach; endif;?>
</div>

<script>
function filterNotifs(f,el){
  el.parentNode.querySelectorAll('.ftab').forEach(t=>t.classList.remove('active'));el.classList.add('active');
  document.querySelectorAll('#notifList .adm-card').forEach(c=>{
    c.style.display=(f==='all'||(f==='unread'&&c.dataset.read==='unread')||(f==='read'&&c.dataset.read==='read'))?'':'none';
  });
}
async function markNotifRead(id,btn){
  const r=await labAction({action:'mark_notification_read',notification_id:id});
  if(r.success){btn.closest('.adm-card').style.borderLeft='';btn.closest('.adm-card').style.background='';btn.closest('.adm-card').dataset.read='read';btn.remove();showToast('Marked as read');}
}
async function markAllRead(){
  const r=await labAction({action:'mark_all_notifications_read'});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}
</script>
