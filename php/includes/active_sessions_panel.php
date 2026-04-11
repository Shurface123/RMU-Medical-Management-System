<?php
// Active Sessions Panel
$uid = (int)($_SESSION['user_id'] ?? 0);
$curr_sid = session_id();
$sess_q = mysqli_query($conn, "SELECT * FROM active_sessions WHERE user_id = $uid ORDER BY last_active DESC");
$user_sessions = [];
if($sess_q) while($r = mysqli_fetch_assoc($sess_q)) $user_sessions[] = $r;

// If included from a different directory depth, we calculate the absolute path for URLs
$base = "/RMU-Medical-Management-System";
?>
<div class="adm-card" style="margin-top:1.5rem; margin-bottom:0;">
  <div class="adm-card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
    <h3><i class="fas fa-desktop" style="color:var(--primary); margin-right:0.5rem;"></i> Active Devices & Sessions</h3>
    <?php if(count($user_sessions) > 1): ?>
    <button type="button" class="btn btn-danger btn-sm" onclick="logoutAllOtherSessions()" style="white-space:nowrap;"><span class="btn-text">
      <i class="fas fa-sign-out-alt"></i> Sign out all other devices
    </span></button>
    <?php endif; ?>
  </div>
  <div style="padding:2rem;">
    <p style="color:var(--text-muted); font-size:1rem; margin-bottom:1.5rem;">Manage the devices currently logged into your account. Terminate any unrecognized activity instantly.</p>
    <div style="display:flex; flex-direction:column; gap:1rem;" id="activeSessList">
      <?php foreach($user_sessions as $idx => $s): 
        $is_curr = ($s['session_id'] === $curr_sid);
        $ua_lower = strtolower($s['browser']??'');
        $icon = strpos($ua_lower, 'mobile') !== false ? 'fa-mobile-alt' : (strpos($ua_lower, 'mac') !== false ? 'fa-apple' : 'fa-laptop');
      ?>
      <div style="display:flex; justify-content:space-between; align-items:center; padding:1.2rem; border:1px solid <?=$is_curr?'var(--primary)':'var(--border)'?>; border-radius:12px; background:<?=$is_curr?'rgba(47,128,237,0.03)':'var(--surface)'?>; flex-wrap:wrap; gap:1rem;">
        <div style="display:flex; gap:1.2rem; align-items:center;">
          <div style="width:48px; height:48px; min-width:48px; border-radius:12px; background:var(--surface-2); color:<?=$is_curr?'var(--primary)':'var(--text-secondary)'?>; display:flex; align-items:center; justify-content:center; font-size:1.6rem;">
            <i class="fas <?=$icon?>"></i>
          </div>
          <div>
            <div style="font-weight:600; font-size:1.15rem; color:var(--text-primary); margin-bottom:0.2rem;">
              <?=htmlspecialchars($s['device_info'] ?? 'Unknown Device')?>
              <?php if($is_curr): ?> 
                <span class="adm-badge adm-badge-success" style="font-size:0.75rem; padding:0.25rem 0.6rem; margin-left:0.5rem;"><i class="fas fa-check-circle" style="font-size:11px;"></i> Current Session</span> 
              <?php endif; ?>
            </div>
            <div style="font-size:0.95rem; color:var(--text-muted); line-height:1.4;">
              <?=htmlspecialchars(substr($s['browser']??'Unknown Browser', 0, 50))?><br>
              <strong>IP:</strong> <?=htmlspecialchars($s['ip_address'])?>
            </div>
          </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:0.9rem; color:var(--text-secondary); margin-bottom:0.6rem;">
              <i class="fas fa-clock"></i> Active: <?= date('M d, g:i A', strtotime($s['last_active'])) ?>
            </div>
            <?php if(!$is_curr): ?>
            <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger); border:1px solid var(--danger-light); padding:0.4rem 1rem;" onclick="logoutSpecificSession('<?=$s['session_id']?>')"><span class="btn-text">
            Termnate Session
            </span></button>
            <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
async function logoutAllOtherSessions(){
  if(!confirm('Are you sure you want to sign out of all other devices?')) return;
  const fd = new FormData(); fd.append('action','logout_all_other');
  try{
    const req = await fetch('<?=$base?>/php/ajax/session_actions.php',{method:'POST',body:fd});
    const res = await req.json();
    if(res.success){ if(typeof showToast==='function')showToast('Signed out of all other devices'); setTimeout(()=>location.reload(),1000); }
    else if(typeof showToast==='function')showToast(res.message||'Failed','warning');
  }catch(e){console.error(e);}
}
async function logoutSpecificSession(sid){
  if(!confirm('Revoke access for this device immediately?')) return;
  const fd = new FormData(); fd.append('action','logout_specific'); fd.append('session_id',sid);
  try{
    const req = await fetch('<?=$base?>/php/ajax/session_actions.php',{method:'POST',body:fd});
    const res = await req.json();
    if(res.success){ if(typeof showToast==='function')showToast('Device connection terminated'); setTimeout(()=>location.reload(),600); }
    else if(typeof showToast==='function')showToast(res.message||'Failed','warning');
  }catch(e){console.error(e);}
}
</script>
