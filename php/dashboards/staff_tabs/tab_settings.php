<?php
/**
 * tab_settings.php
 * Module 8: Settings & Security (Theme, Notifications, Password Change, Active Sessions)
 */
?>
<div id="sec-settings" class="dash-section <?=($active_tab==='settings')?'active':''?>">
    
    <div class="adm-card" style="margin-bottom:2rem;">
        <div class="adm-card-header">
            <h3><i class="fas fa-shield-alt" style="color:var(--role-accent);"></i> Security & Account Settings</h3>
        </div>
        <div class="adm-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;">
            
            <!-- Change Password -->
            <div>
                <h4 style="font-size:1.4rem;font-weight:600;margin-bottom:1.5rem;color:var(--text-primary);"><i class="fas fa-key" style="color:var(--text-muted);"></i> Change Password</h4>
                <form id="frmPassword" onsubmit="event.preventDefault(); changePassword();">
                    <input type="hidden" name="action" value="update_password">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Min 8 characters, uppercase, number" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary" id="btnSavePwd"><i class="fas fa-lock"></i> Update Password</button>
                </form>
            </div>

            <!-- Active Sessions -->
            <div>
                <h4 style="font-size:1.4rem;font-weight:600;margin-bottom:1.5rem;color:var(--text-primary);display:flex;justify-content:space-between;align-items:center;">
                    <span><i class="fas fa-desktop" style="color:var(--text-muted);"></i> Active Sessions</span>
                    <button class="adm-btn adm-btn-sm adm-btn-danger" id="btnKillAll" onclick="terminateAllSessions()"><i class="fas fa-power-off"></i> Terminate All Others</button>
                </h4>
                
                <div style="display:flex;flex-direction:column;gap:1.2rem;">
                    <?php
                    $sessions = dbSelect($conn, "SELECT * FROM staff_sessions WHERE staff_id=? ORDER BY is_current DESC, login_time DESC", "i", [$staff_id]);
                    if(empty($sessions)): ?>
                        <p style="color:var(--text-muted);">No active sessions found.</p>
                    <?php else:
                        foreach($sessions as $sess): 
                            $is_curr = (int)$sess['is_current'] === 1;
                    ?>
                        <div style="padding:1.2rem;border-radius:8px;border:1px solid <?= $is_curr ? 'var(--role-accent)' : 'var(--border)' ?>;background:var(--surface-2);display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <strong style="display:block;font-size:1.3rem;">
                                    <i class="fas fa-laptop" style="color:<?= $is_curr ? 'var(--role-accent)' : 'var(--text-muted)' ?>;"></i> 
                                    <?=e($sess['device_info']??'Unknown Device')?>
                                </strong>
                                <span style="font-size:1.1rem;color:var(--text-secondary);"><?=e($sess['browser']??'Unknown Browser')?> &bull; IP: <?=e($sess['ip_address']??'—')?></span><br>
                                <span style="font-size:1.1rem;color:var(--text-muted);">Logged in: <?=date('d M h:i A', strtotime($sess['login_time']))?></span>
                            </div>
                            <?php if($is_curr): ?>
                                <span class="adm-badge" style="background:var(--role-accent-light);color:var(--role-accent);">Current Mode</span>
                            <?php else: ?>
                                <button class="adm-btn adm-btn-sm adm-btn-outline" style="color:var(--danger);border-color:var(--danger);" onclick="terminateSession(<?=$sess['session_id']?>)" title="Log out this device">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
async function changePassword() {
    const btn = document.getElementById('btnSavePwd');
    const form = document.getElementById('frmPassword');
    
    // Quick validation
    const np = form.new_password.value;
    const cp = form.confirm_password.value;
    if(np !== cp) {
        showToast('New passwords do not match!', 'error');
        return;
    }
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    const fd = new FormData(form);
    const res = await staffAction(fd);
    
    showToast(res.message, res.success ? 'success' : 'error');
    if(res.success) form.reset();
    
    btn.innerHTML = '<i class="fas fa-lock"></i> Update Password';
    btn.disabled = false;
}

async function terminateSession(sessId) {
    if(!confirmAction("Terminate this active session? The user on that device will be logged out.")) return;
    const res = await staffAction({ action: 'logout_session', session_id: sessId });
    showToast(res.message, res.success ? 'success' : 'error');
    if(res.success) setTimeout(() => location.reload(), 1000);
}

async function terminateAllSessions() {
    if(!confirmAction("Terminate ALL OTHER active sessions? Only this current browser will remain logged in.")) return;
    const btn = document.getElementById('btnKillAll');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Terminating...';
    const res = await staffAction({ action: 'logout_all_sessions' });
    showToast(res.message, res.success ? 'success' : 'error');
    if(res.success) setTimeout(() => location.reload(), 1000);
    else btn.innerHTML = '<i class="fas fa-power-off"></i> Terminate All Others';
}
</script>
