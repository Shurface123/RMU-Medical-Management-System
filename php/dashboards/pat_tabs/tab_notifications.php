<?php
// MODULE 8: NOTIFICATIONS — Full page
$all_notifs=[];
$q=mysqli_query($conn,"SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $all_notifs[]=$r;
$unread_count=$stats['unread_notif'];
?>
<div id="sec-notif_page" class="dash-section">
  <div class="adm-card">
    <div class="adm-card-header">
      <h3><i class="fas fa-bell" style="color:var(--warning);"></i> Notifications <?php if($unread_count):?><span class="adm-badge adm-badge-danger"><?=$unread_count?> unread</span><?php endif;?></h3>
      <?php if($unread_count>0):?>
      <button class="btn-icon btn btn-primary btn-sm" onclick="markAllRead()"><span class="btn-text"><i class="fas fa-check-double"></i> Mark All Read</span></button>
      <?php endif;?>
    </div>
    <!-- Filters -->
    <div class="filter-tabs" style="padding:.5rem 1.5rem 0;" id="notifFilters">
      <span class="ftab active" onclick="filterNotifs('all',this)">All</span>
      <span class="ftab" onclick="filterNotifs('unread',this)">Unread (<?=$unread_count?>)</span>
      <span class="ftab" onclick="filterNotifs('appointment',this)">Appointments</span>
      <span class="ftab" onclick="filterNotifs('prescription',this)">Prescriptions</span>
      <span class="ftab" onclick="filterNotifs('lab',this)">Lab</span>
    </div>
    <div id="notifList" style="padding:.5rem 1rem;">
      <?php if(empty($all_notifs)):?>
      <div style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-bell-slash" style="font-size:2.5rem;opacity:.4;display:block;margin-bottom:1rem;"></i><p>No notifications</p></div>
      <?php else: foreach($all_notifs as $n):
        $isRead=$n['is_read'];
        $ntype=$n['type']??$n['related_module']??'';
        $iconMap=['appointment'=>'fa-calendar','prescription'=>'fa-pills','lab'=>'fa-flask','medical_record'=>'fa-file-medical']; $icon=$iconMap[$ntype]??'fa-bell';
        $iconColorMap=['appointment'=>'var(--primary)','prescription'=>'var(--warning)','lab'=>'var(--role-accent)','medical_record'=>'var(--info)']; $iconColor=$iconColorMap[$ntype]??'var(--text-muted)';
        $tabMap=['appointments'=>'appointments','prescriptions'=>'prescriptions','lab'=>'lab','medical_records'=>'records']; $tabTarget=$tabMap[$n['related_module']??'']??'';
      ?>
      <div class="notif-item notif-type-<?=$n['type']??$n['related_module']??'general'?>" data-id="<?=$n['notification_id']?>" data-read="<?=$isRead?>"
        style="display:flex;align-items:flex-start;gap:1rem;padding:1.2rem;border-bottom:1px solid var(--border);background:<?=$isRead?'transparent':'var(--primary-light)'?>;border-radius:8px;margin-bottom:.3rem;cursor:pointer;transition:background .2s;"
        onclick="clickNotif(<?=$n['notification_id']?>,'<?=$tabTarget?>')">
        <div style="width:40px;height:40px;border-radius:50%;background:<?=$isRead?'var(--surface-2)':'var(--role-accent-light)'?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="fas <?=$icon?>" style="color:<?=$iconColor?>;font-size:1.3rem;"></i>
        </div>
        <div style="flex:1;">
          <div style="font-weight:<?=$isRead?'500':'700'?>;font-size:1.3rem;color:var(--text-primary);"><?=htmlspecialchars($n['title']??'Notification')?></div>
          <div style="font-size:1.2rem;color:var(--text-secondary);margin-top:.2rem;"><?=htmlspecialchars($n['message'])?></div>
          <div style="font-size:1.05rem;color:var(--text-muted);margin-top:.3rem;"><i class="fas fa-clock"></i> <?=date('d M Y, g:i A',strtotime($n['created_at']))?></div>
        </div>
        <?php if(!$isRead):?><div style="width:10px;height:10px;border-radius:50%;background:var(--danger);flex-shrink:0;margin-top:.5rem;"></div><?php endif;?>
      </div>
      <?php endforeach; endif;?>
    </div>
  </div>
</div>

<script>
function filterNotifs(filter,btn){
  document.querySelectorAll('#notifFilters .ftab').forEach(f=>f.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.notif-item').forEach(n=>{
    if(filter==='all') n.style.display='';
    else if(filter==='unread') n.style.display=n.dataset.read==='0'?'':'none';
    else n.style.display=n.classList.contains('notif-type-'+filter)?'':'none';
  });
}
async function clickNotif(id,tab){
  await patAction({action:'mark_notification_read',id});
  const el=document.querySelector(`.notif-item[data-id="${id}"]`);
  if(el){el.style.background='transparent';el.dataset.read='1';el.querySelector('.fas:last-child')?.remove();}
  updateBellBadge();
  if(tab) showTab(tab,document.querySelector(`.adm-nav-item[onclick*="${tab}"]`));
}
async function markAllRead(){
  const r=await patAction({action:'mark_all_read'});
  if(r.success){
    toast('All notifications marked as read');
    document.querySelectorAll('.notif-item').forEach(n=>{n.style.background='transparent';n.dataset.read='1';});
    updateBellBadge();
  }
}
function updateBellBadge(){
  const unread=document.querySelectorAll('.notif-item[data-read="0"]').length;
  const badge=document.getElementById('rmuBellBadge');
  if(badge){badge.textContent=unread;badge.style.display=unread>0?'flex':'none';}
}
</script>
