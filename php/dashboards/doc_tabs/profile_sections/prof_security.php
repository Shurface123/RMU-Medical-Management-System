<?php // SECTION G: Account & Security 
$sessionsQ = mysqli_query($conn, "SELECT * FROM active_sessions WHERE user_id = " . intval($_SESSION['user_id']) . " ORDER BY last_active DESC");
$active_sessions_list = [];
if ($sessionsQ) while ($r = mysqli_fetch_assoc($sessionsQ)) $active_sessions_list[] = $r;
$current_sid = session_id();
?>
<div id="prof-security" class="prof-section" style="display:none;">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
    <!-- Password -->
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3><i class="fas fa-lock"></i> Change Password</h3></div>
      <div style="padding:2rem;">
        <form id="formPwdProf" onsubmit="changePwdProf(event)">
          <div class="form-group"><label>Current Password</label><input type="password" name="current_password" class="form-control" required></div>
          <div class="form-group"><label>New Password</label><input type="password" name="new_password" id="newPwdProf" class="form-control" required minlength="8" oninput="checkStrength(this)"><div id="pwdStrength" style="margin-top:.4rem;font-size:1.1rem;font-weight:600;"></div></div>
          <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
          <button type="submit" class="btn btn-warning" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-key"></i> Update Password</span></button>
        </form>
      </div>
    </div>
    <!-- Email -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
      <div class="adm-card" style="margin:0;">
        <div class="adm-card-header"><h3><i class="fas fa-envelope"></i> Update Email</h3></div>
        <div style="padding:2rem;">
          <form onsubmit="updateEmailProf(event)">
            <div class="form-group"><label>Current Email</label><input type="email" class="form-control" value="<?=htmlspecialchars($prof['email']??'')?>" readonly style="background:var(--surface-2);"></div>
            <div class="form-group"><label>New Email</label><input type="email" name="new_email" class="form-control" required></div>
            <div class="form-group"><label>Password Confirmation</label><input type="password" name="password" class="form-control" required></div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-save"></i> Update Email</span></button>
          </form>
        </div>
      </div>
      <!-- Deactivation -->
      <div class="adm-card" style="margin:0;border:1.5px solid var(--danger);">
        <div class="adm-card-header"><h3 style="color:var(--danger);"><i class="fas fa-user-slash"></i> Account Deactivation</h3></div>
        <div style="padding:2rem;">
          <p style="font-size:1.2rem;color:var(--text-muted);margin-bottom:1rem;">Request temporary account deactivation. This will be reviewed by admin. Your data will not be deleted.</p>
          <form onsubmit="requestDeact(event)">
            <div class="form-group"><label>Reason</label><textarea name="reason" class="form-control" rows="2" required placeholder="Please provide a reason"></textarea></div>
            <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-power-off"></i> Request Deactivation</span></button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Active Sessions (Phase 3) -->
  <div class="adm-card" style="margin-top:1.5rem; border-left: 4px solid var(--primary);">
    <div class="adm-card-header">
        <h3><i class="fas fa-laptop-house"></i> Active Devices & Sessions</h3>
        <?php if (count($active_sessions_list) > 1): ?>
        <button class="btn btn-danger btn-sm" onclick="revokeSession('all')"><span class="btn-text"><i class="fas fa-sign-out-alt"></i> Logout All Other Devices</span></button>
        <?php endif; ?>
    </div>
    <div style="padding:1.5rem;overflow-x:auto;">
      <table class="adm-table">
        <thead>
            <tr>
              <th>Device / Browser</th>
              <th>IP Address</th>
              <th>Last Active</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
        </thead>
        <tbody>
          <?php foreach ($active_sessions_list as $s): 
              $is_current = ($s['session_id'] === $current_sid);
          ?>
            <tr>
              <td>
                <div style="font-weight:600;"><i class="fas <?= stripos($s['device_info']??'','mac')!==false?'fa-apple':(stripos($s['device_info']??'','win')!==false?'fa-windows':'fa-desktop') ?>"></i> <?= htmlspecialchars($s['device_info'] ?? 'Unknown Device') ?></div>
                <div style="font-size:0.8rem;color:var(--text-muted);"><?= htmlspecialchars($s['browser'] ?? $s['user_agent'] ?? 'Unknown Browser') ?></div>
              </td>
              <td><?= htmlspecialchars($s['ip_address'] ?? '127.0.0.1') ?></td>
              <td><?= date('M j, Y g:i A', strtotime($s['last_active'])) ?></td>
              <td>
                  <?php if($is_current): ?>
                      <span class="adm-badge adm-badge-success">Current Session</span>
                  <?php else: ?>
                      <span class="adm-badge adm-badge-warning">Active Remote</span>
                  <?php endif; ?>
              </td>
              <td>
                  <?php if(!$is_current): ?>
                  <button class="btn btn-ghost btn-sm" style="color:var(--danger);" onclick="revokeSession('<?= htmlspecialchars($s['session_id']) ?>')"><span class="btn-text"><i class="fas fa-times-circle"></i> Revoke</span></button>
                  <?php else: ?>
                  <span style="color:var(--text-muted);font-size:0.85rem;">Active</span>
                  <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Activity Log -->
  <div class="adm-card" style="margin-top:1.5rem;">
    <div class="adm-card-header"><h3><i class="fas fa-clipboard-list"></i> Activity Log</h3>
      <button class="btn btn-primary btn btn-sm" onclick="loadActivityLog()"><span class="btn-text"><i class="fas fa-sync-alt"></i> Refresh</span></button>
    </div>
    <div style="padding:1.5rem;overflow-x:auto;">
      <table class="adm-table" id="activityTable">
        <thead><tr><th>Action</th><th>IP Address</th><th>Device</th><th>Time</th></tr></thead>
        <tbody><tr><td colspan="4" style="text-align:center;color:var(--text-muted);">Click Refresh to load</td></tr></tbody>
      </table>
    </div>
  </div>
</div>
<script>
function checkStrength(inp){
  const v=inp.value,el=document.getElementById('pwdStrength');
  let s=0;if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  const labels=['','Weak','Fair','Strong','Very Strong'],colors=['','var(--danger)','var(--warning)','var(--info)','var(--success)'];
  el.textContent=labels[s]||'';el.style.color=colors[s]||'';
}
async function changePwdProf(e){
  e.preventDefault();const fd=new FormData(e.target),data={action:'change_password'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await profAction(data);
  if(res.success){toast('Password changed!');e.target.reset();document.getElementById('pwdStrength').textContent='';}
  else toast(res.message||'Error','danger');
}
async function updateEmailProf(e){
  e.preventDefault();const fd=new FormData(e.target),data={action:'update_email'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await profAction(data);
  if(res.success){toast('Email updated!');location.reload();}else toast(res.message||'Error','danger');
}
async function requestDeact(e){
  e.preventDefault();if(!confirm('Are you sure you want to request deactivation?'))return;
  const fd=new FormData(e.target),data={action:'request_deactivation'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await profAction(data);
  if(res.success)toast('Request sent to admin');else toast(res.message||'Error','danger');
}
async function loadActivityLog(){
  const res=await(await fetch(PROF_API+'?action=get_activity_log&limit=50')).json();
  if(!res.success)return;
  const tb=document.querySelector('#activityTable tbody');
  tb.innerHTML=res.log.length?res.log.map(l=>`<tr><td>${l.action}</td><td>${l.ip_address||'—'}</td><td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${l.device||'—'}</td><td>${new Date(l.created_at).toLocaleString()}</td></tr>`).join(''):'<tr><td colspan="4" style="text-align:center;">No activity yet</td></tr>';
}
async function revokeSession(sid) {
    if(!confirm(sid==='all' ? "Are you sure you want to log out all other devices?" : "Force logout this specific device?")) return;
    try {
        const response = await fetch('/RMU-Medical-Management-System/php/handlers/revoke_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({session_id: sid})
        });
        const res = await response.json();
        if(res.success){
            if(window.showToast) showToast(res.message);
            else alert(res.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            if(window.showToast) showToast(res.message, 'danger');
            else alert(res.message);
        }
    } catch(e) { console.error(e); }
}
</script>
