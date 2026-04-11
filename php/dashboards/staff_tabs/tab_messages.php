<?php
/**
 * tab_messages.php — Module 11: Internal Messages (Staff ↔ Admin)
 */
// Get all admin staff to message
$admins = dbSelect($conn,"SELECT id, full_name FROM staff WHERE role='admin' LIMIT 20");
// If no admin staff, get admin from users table
if(empty($admins)) {
    $admin_users = dbSelect($conn,"SELECT id, name AS full_name FROM users WHERE user_role='admin' LIMIT 10");
}
$conversations = dbSelect($conn,
    "SELECT m.*, s.full_name AS sender_name, s2.full_name AS receiver_name
     FROM staff_messages m
     LEFT JOIN staff s  ON m.sender_id   = s.id
     LEFT JOIN staff s2 ON m.receiver_id = s2.id
     WHERE m.sender_id=? OR m.receiver_id=?
     ORDER BY m.sent_at DESC LIMIT 50",
    "ii",[$staff_id,$staff_id]);
$unread_msg_count = (int)dbVal($conn,"SELECT COUNT(*) FROM staff_messages WHERE receiver_id=? AND is_read=0","i",[$staff_id]);
?>
<div id="sec-messages" class="dash-section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
        <h2 style="font-size:2.2rem;font-weight:700;"><i class="fas fa-envelope" style="color:var(--role-accent);"></i> Messages
            <?php if($unread_msg_count>0): ?><span class="badge badge-urgent" style="font-size:1.2rem;margin-left:.8rem;"><?=$unread_msg_count?> unread</span><?php endif; ?>
        </h2>
        <button class="btn btn-primary" onclick="openModal('composeModal')"><span class="btn-text"><i class="fas fa-pen"></i> Compose Message</span></button>
    </div>

    <div class="card">
        <div class="card-body-flush">
            <?php if(empty($conversations)): ?>
                <div style="text-align:center;padding:6rem 2rem;">
                    <i class="fas fa-envelope-open" style="font-size:5rem;color:var(--text-muted);display:block;margin-bottom:1.5rem;"></i>
                    <h3 style="color:var(--text-secondary);">No Messages Yet</h3>
                    <p style="color:var(--text-muted);">Compose a message to your admin or supervisor.</p>
                    <button class="btn btn-primary" style="margin-top:1.5rem;" onclick="openModal('composeModal')"><span class="btn-text"><i class="fas fa-pen"></i> Compose Message</span></button>
                </div>
            <?php else: foreach($conversations as $m):
                $is_mine = ((int)$m['sender_id'] === $staff_id);
                $is_unread = !$is_mine && !(bool)$m['is_read'];
                $other_name = $is_mine ? ($m['receiver_name']??'Admin') : ($m['sender_name']??'Admin');
                $priority=$m['priority']??'normal';
            ?>
            <div style="display:flex;align-items:flex-start;gap:1.5rem;padding:1.8rem 2rem;border-bottom:1px solid var(--border);<?=$is_unread?'background:var(--role-accent-light);':''?>"
                 onclick="markMsgRead(<?=$m['id']?>,this)">
                <div style="width:46px;height:46px;border-radius:50%;background:var(--role-accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.6rem;flex-shrink:0;">
                    <?=strtoupper(substr($other_name,0,1))?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem;">
                        <strong style="font-size:1.4rem;color:var(--text-primary);">
                            <?=$is_mine?'You → '.e($other_name):'From: '.e($other_name)?>
                        </strong>
                        <div style="display:flex;align-items:center;gap:.6rem;">
                            <?php if($priority==='urgent'): ?><span class="badge badge-urgent" style="font-size:1rem;">URGENT</span><?php endif; ?>
                            <span style="font-size:1.1rem;color:var(--text-muted);"><?=date('d M, h:i A',strtotime($m['sent_at']))?></span>
                            <?php if($is_unread): ?><span class="badge" style="background:var(--role-accent);color:#fff;font-size:.9rem;">NEW</span><?php endif; ?>
                        </div>
                    </div>
                    <?php if(!empty($m['subject'])): ?>
                        <p style="font-weight:600;margin-bottom:.3rem;font-size:1.3rem;color:var(--text-primary);"><?=e($m['subject'])?></p>
                    <?php endif; ?>
                    <p style="font-size:1.3rem;color:var(--text-secondary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=e($m['body'])?></p>
                </div>
                <?php if($is_mine): ?><i class="fas fa-paper-plane" style="color:var(--role-accent);align-self:center;"></i><?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Compose Modal -->
<div class="modal-bg" id="composeModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-pen" style="color:var(--role-accent);"></i> Compose Message</h3>
            <button class="btn btn-primary modal-close" onclick="closeModal('composeModal')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <form id="frmCompose" onsubmit="event.preventDefault();sendMessage();">
            <input type="hidden" name="action" value="send_message">
            <div class="form-group">
                <label>Send To *</label>
                <select name="receiver_id" class="form-control" required>
                    <option value="">Select recipient</option>
                    <?php
                    // Get admin staff
                    $msg_admins = dbSelect($conn,"SELECT id, full_name FROM staff WHERE role='admin'");
                    if(empty($msg_admins)) {
                        // Fallback: admin from users table (id maps to staff id differently, skip for now)
                        echo '<option value="1">System Admin</option>';
                    } else {
                        foreach($msg_admins as $adm): echo '<option value="'.e($adm['id']).'">'.e($adm['full_name']).' (Admin)</option>'; endforeach;
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Priority</label>
                <select name="priority" class="form-control">
                    <option value="normal">Normal</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" class="form-control" placeholder="Brief subject...">
            </div>
            <div class="form-group">
                <label>Message *</label>
                <textarea name="body" class="form-control" rows="5" required placeholder="Type your message here..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-wide" id="btnSendMsg"><span class="btn-text"><i class="fas fa-paper-plane"></i> Send Message</span></button>
        </form>
    </div>
</div>

<script>
async function sendMessage(){
    const btn=document.getElementById('btnSendMsg'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmCompose'));
    const res=await doAction(fd,'Message sent successfully!');
    btn.innerHTML='<i class="fas fa-paper-plane"></i> Send Message'; btn.disabled=false;
    if(res){closeModal('composeModal'); document.getElementById('frmCompose').reset(); setTimeout(()=>location.reload(),800);}
}
async function markMsgRead(id,el){
    await doAction({action:'mark_message_read', message_id:id});
    el.style.background='var(--surface)';
    el.querySelector('.badge[style*="role-accent"]')?.remove();
}
</script>
