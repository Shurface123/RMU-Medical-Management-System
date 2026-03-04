<!-- ═══════════════════════════════════════════════════════════
     NOTIFICATIONS — tab_notifications.php
     ═══════════════════════════════════════════════════════════ -->
<?php
$notifications = dbSelect($conn,
    "SELECT * FROM nurse_notifications WHERE nurse_id=? ORDER BY created_at DESC LIMIT 100","i",[$nurse_pk]);
$global_notifs = dbSelect($conn,
    "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50","i",[$user_id]);
$all_notifs = array_merge($notifications, $global_notifs);
usort($all_notifs, fn($a,$b)=>strtotime($b['created_at'])-strtotime($a['created_at']));
?>
<div id="sec-notifications" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-bell"></i> Notifications</h2>
    <div style="display:flex;gap:.8rem;">
      <button class="btn btn-outline" onclick="markAllRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
      <button class="btn btn-outline" onclick="clearAllNotifs()"><i class="fas fa-trash"></i> Clear All</button>
    </div>
  </div>

  <div class="filter-tabs">
    <span class="ftab active" onclick="filterNotifs('all',this)">All</span>
    <span class="ftab" onclick="filterNotifs('unread',this)">Unread</span>
    <span class="ftab" onclick="filterNotifs('Task',this)">Tasks</span>
    <span class="ftab" onclick="filterNotifs('Medication',this)">Meds</span>
    <span class="ftab" onclick="filterNotifs('Emergency',this)">Emergency</span>
    <span class="ftab" onclick="filterNotifs('System',this)">System</span>
  </div>

  <div class="info-card">
    <?php if(empty($all_notifs)):?>
      <p class="text-center text-muted" style="padding:3rem;"><i class="fas fa-bell-slash" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.3;"></i>No notifications</p>
    <?php else: foreach($all_notifs as $n):
      $is_read = (int)($n['is_read']??0);
      $type = $n['type']??'System';
      $type_icons=['Task'=>'fa-clipboard-list','Medication'=>'fa-pills','Vital'=>'fa-heartbeat','Emergency'=>'fa-triangle-exclamation','Message'=>'fa-comment','System'=>'fa-bell','Shift'=>'fa-clock','Handover'=>'fa-exchange-alt'];
      $type_colors=['Task'=>'primary','Medication'=>'info','Vital'=>'warning','Emergency'=>'danger','Message'=>'success','System'=>'secondary','Shift'=>'warning','Handover'=>'info'];
    ?>
      <div class="alert-card notif-item" data-type="<?=e($type)?>" data-read="<?=$is_read?>" style="<?=!$is_read?'background:var(--role-accent-light);border-left:3px solid var(--role-accent);':''?>">
        <div class="alert-icon <?=$type_colors[$type]??'blue'?>"><i class="fas <?=$type_icons[$type]??'fa-bell'?>"></i></div>
        <div style="flex:1;">
          <div style="font-size:1.25rem;font-weight:<?=$is_read?'400':'600'?>;color:var(--text-primary);"><?=e($n['message']??'')?></div>
          <div style="font-size:1.05rem;color:var(--text-muted);margin-top:.3rem;">
            <span class="badge badge-<?=$type_colors[$type]??'secondary'?>" style="font-size:.9rem;"><?=e($type)?></span>
            · <?=date('d M h:i A',strtotime($n['created_at']))?>
          </div>
        </div>
        <?php if(!$is_read):?><span style="width:8px;height:8px;border-radius:50%;background:var(--role-accent);flex-shrink:0;"></span><?php endif;?>
      </div>
    <?php endforeach; endif;?>
  </div>
</div>

<script>
function filterNotifs(type,el){
  document.querySelectorAll('#sec-notifications .ftab').forEach(f=>f.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.notif-item').forEach(item=>{
    if(type==='all') item.style.display='';
    else if(type==='unread') item.style.display=(item.dataset.read==='0')?'':'none';
    else item.style.display=(item.dataset.type===type)?'':'none';
  });
}

async function markAllRead(){
  const r=await nurseAction({action:'mark_all_notifications_read'});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),800);
}

async function clearAllNotifs(){
  if(!confirmAction('Clear all notifications?')) return;
  const r=await nurseAction({action:'clear_all_notifications'});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),800);
}
</script>
