<?php
/**
 * tab_messages.php — Module 11: Internal Messages (Staff ↔ Admin) - Modernized
 */
$conversations = dbSelect($conn,
    "SELECT m.*, s.name AS sender_name, s2.name AS receiver_name
     FROM staff_messages m
     LEFT JOIN users s  ON m.sender_id   = s.id
     LEFT JOIN users s2 ON m.receiver_id = s2.id
     WHERE m.sender_id=? OR m.receiver_id=?
     ORDER BY m.sent_at DESC LIMIT 100",
    "ii", [$staff_id, $staff_id]);


$unread_msg_count = (int)dbVal($conn, "SELECT COUNT(*) FROM staff_messages WHERE receiver_id=? AND is_read=0", "i", [$staff_id]);
?>
<div id="sec-messages" class="dash-section">
    
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2.5rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-envelope" style="color:var(--role-accent);"></i> Secure Communications</h2>
            <p style="font-size:1.3rem;color:var(--text-muted);margin:0.5rem 0 0;">Internal messaging with facility management</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('mdlCompose')" style="padding:1.2rem 2.2rem; font-weight:700;">
            <span class="btn-text"><i class="fas fa-paper-plane"></i> New Message</span>
        </button>
    </div>

    <!-- Stats Row -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem; margin-bottom:2.5rem;">
        <div class="card" style="padding:1.5rem; display:flex; align-items:center; gap:1.2rem; border-left:4px solid var(--role-accent);">
            <div style="width:45px; height:45px; border-radius:12px; background:rgba(47,128,237,0.1); color:var(--role-accent); display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
                <i class="fas fa-inbox"></i>
            </div>
            <div>
                <div style="font-size:1.8rem; font-weight:800;"><?= count($conversations) ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); font-weight:600;">Total Messages</div>
            </div>
        </div>
        <div class="card" style="padding:1.5rem; display:flex; align-items:center; gap:1.2rem; border-left:4px solid var(--danger);">
            <div style="width:45px; height:45px; border-radius:12px; background:rgba(231,76,60,0.1); color:var(--danger); display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div>
                <div style="font-size:1.8rem; font-weight:800;"><?= $unread_msg_count ?></div>
                <div style="font-size:1.1rem; color:var(--text-muted); font-weight:600;">Unread</div>
            </div>
        </div>
    </div>

    <!-- Message List -->
    <div class="card" style="overflow:hidden; background:rgba(255,255,255,0.02); backdrop-filter:blur(10px);">
        <?php if(empty($conversations)): ?>
            <div style="text-align:center; padding:8rem 2rem;">
                <div style="width:100px; height:100px; border-radius:50%; background:var(--surface-2); display:flex; align-items:center; justify-content:center; margin:0 auto 2rem;">
                    <i class="fas fa-comments" style="font-size:4rem; opacity:.2;"></i>
                </div>
                <h3 style="font-size:1.8rem; font-weight:700;">Your inbox is empty</h3>
                <p style="font-size:1.3rem; color:var(--text-muted); margin-top:.8rem;">Start a professional conversation with the administrative team.</p>
                <button class="btn btn-outline" style="margin-top:2rem;" onclick="openModal('mdlCompose')">
                    <span class="btn-text"><i class="fas fa-pen"></i> Compose Now</span>
                </button>
            </div>
        <?php else: ?>
            <div id="messageList">
                <?php foreach($conversations as $m):
                    $is_mine = ((int)$m['sender_id'] === $staff_id);
                    $is_unread = !$is_mine && !(bool)$m['is_read'];
                    $other_name = $is_mine ? ($m['receiver_name'] ?? 'Facility Admin') : ($m['sender_name'] ?? 'Facility Admin');
                    $priority = strtolower($m['priority'] ?? 'normal');
                ?>
                <div class="msg-bubble <?= $is_unread ? 'unread' : '' ?>" 
                     onclick="viewMessage(<?= htmlspecialchars(json_encode($m)) ?>, this)"
                     style="padding:2rem 2.5rem; border-bottom:1px solid var(--border); cursor:pointer; transition:.2s; display:flex; gap:1.8rem; align-items:flex-start; position:relative;">
                    
                    <?php if($is_unread): ?>
                        <div style="position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--role-accent);"></div>
                    <?php endif; ?>

                    <div style="width:52px; height:52px; border-radius:15px; background:<?= $is_mine ? 'var(--role-accent)' : 'var(--surface-2)' ?>; color:<?= $is_mine ? '#fff' : 'var(--role-accent)' ?>; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.8rem; flex-shrink:0;">
                        <?= strtoupper(substr($other_name, 0, 1)) ?>
                    </div>

                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.6rem; gap:1rem;">
                            <div style="display:flex; align-items:center; gap:.8rem;">
                                <strong style="font-size:1.45rem; color:var(--text-primary);">
                                    <?= $is_mine ? '<span style="color:var(--role-accent); opacity:.8;">To:</span> ' . e($other_name) : e($other_name) ?>
                                </strong>
                                <?php if($priority === 'urgent'): ?>
                                    <span class="badge" style="background:#E74C3C; color:#fff; font-size:0.9rem; font-weight:800;">URGENT</span>
                                <?php endif; ?>
                            </div>
                            <span style="font-size:1.15rem; color:var(--text-muted); font-weight:600;"><?= date('d M, H:i', strtotime($m['sent_at'])) ?></span>
                        </div>
                        
                        <div style="font-weight:700; font-size:1.35rem; color:var(--text-secondary); margin-bottom:.4rem;"><?= e($m['subject'] ?: '(No Subject)') ?></div>
                        <div style="font-size:1.25rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($m['message_content'] ?? ($m['body'] ?? '')) ?></div>
                    </div>


                    <?php if($is_mine): ?>
                        <div style="align-self:center; opacity:.4;" title="Sent Message"><i class="fas fa-paper-plane fa-lg"></i></div>
                    <?php elseif($is_unread): ?>
                        <div style="align-self:center; font-size:.8rem; color:var(--role-accent);"><i class="fas fa-circle"></i></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ════════════════ COMPOSE MODAL ════════════════ -->
<div class="modal-bg" id="mdlCompose">
    <div class="modal-box" style="max-width:650px;">
        <div class="modal-header" style="background:linear-gradient(135deg, var(--role-accent), var(--role-accent-dark)); color:#fff; border:none; padding:2rem 2.5rem;">
            <h3 style="font-size:1.8rem; font-weight:700;"><i class="fas fa-pen"></i> New Message</h3>
            <button class="modal-close" onclick="closeModal('mdlCompose')" style="background:rgba(255,255,255,0.2); color:#fff; border:none; width:34px; height:34px; border-radius:10px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:2.5rem;">
            <form id="frmCompose" onsubmit="event.preventDefault(); sendMessage();">
                <input type="hidden" name="action" value="send_message">
                
                <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:2rem; margin-bottom:1.8rem;">
                    <div class="form-group">
                        <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;">Recipient *</label>
                        <select name="receiver_id" id="msgRecipientSelect" class="form-control" required style="padding:1.2rem; border-radius:10px;">
                            <option value="">Loading recipients...</option>
                        </select>

                    </div>
                    <div class="form-group">
                        <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;">Importance</label>
                        <select name="priority" class="form-control" style="padding:1.2rem; border-radius:10px;">
                            <option value="normal">Normal Priority</option>
                            <option value="urgent">Urgent / Priority</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:1.8rem;">
                    <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;">Subject *</label>
                    <input type="text" name="subject" class="form-control" required placeholder="Main topic of discussion" style="padding:1.2rem; border-radius:10px;">
                </div>

                <div class="form-group" style="margin-bottom:2.5rem;">
                    <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;">Detailed Message *</label>
                    <textarea name="body" class="form-control" rows="6" required style="resize:none; padding:1.2rem; border-radius:10px;" placeholder="Describe your request or update in detail..."></textarea>
                </div>

                <div style="display:flex; gap:1.2rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('mdlCompose')" style="flex:1;">Discard</button>
                    <button type="submit" class="btn btn-primary" id="btnSend" style="flex:2; padding:1.3rem; font-weight:700; font-size:1.4rem;">
                        <span class="btn-text">Transmit Message</span>
                    </button>

                </div>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════ VIEW MESSAGE MODAL ════════════════ -->
<div class="modal-bg" id="mdlViewMsg">
    <div class="modal-box" style="max-width:600px;">
        <div class="modal-header" style="padding:2rem 2.5rem; border-bottom:1px solid var(--border);">
            <div id="msgHeaderInfo" style="display:flex; align-items:center; gap:1.2rem;">
                <div id="msgAvatar" style="width:40px; height:40px; border-radius:10px; background:var(--role-accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800;">?</div>
                <div>
                    <h4 id="msgSender" style="margin:0; font-size:1.4rem; font-weight:700;">Loading...</h4>
                    <span id="msgTime" style="font-size:1.1rem; color:var(--text-muted);">...</span>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal('mdlViewMsg')" style="color:var(--text-muted); background:none; border:none; font-size:1.8rem; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:2.5rem;">
            <div id="vSubject" style="font-size:1.6rem; font-weight:800; margin-bottom:1.5rem;">Subject</div>
            <div id="vBody" style="font-size:1.4rem; color:var(--text-secondary); line-height:1.6; white-space:pre-wrap; background:var(--surface-2); padding:2rem; border-radius:12px;">Message body...</div>
            
            <div style="margin-top:2.5rem; text-align:right;">
                <button class="btn btn-primary" onclick="replyToCurrent();"><span class="btn-text"><i class="fas fa-reply"></i> Reply</span></button>
            </div>
        </div>
    </div>
</div>

<script>
let currentViewingMsg = null;

async function sendMessage() {
    const btn = document.getElementById('btnSend');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Encrypting & Sending...';
    btn.disabled = true;
    
    const fd = new FormData(document.getElementById('frmCompose'));
    const res = await doAction(fd, "Message dispatched to the recipient.");
    
    if (res) {
        closeModal('mdlCompose');
        document.getElementById('frmCompose').reset();
        setTimeout(() => location.reload(), 1500);
    } else {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

async function viewMessage(msg, el) {
    currentViewingMsg = msg;
    
    // Fill UI
    document.getElementById('msgSender').innerText = (msg.sender_id == <?= $staff_id ?>) ? "To: " + (msg.receiver_name || "Admin") : "From: " + (msg.sender_name || "Admin");
    document.getElementById('msgTime').innerText = msg.sent_at;
    document.getElementById('msgAvatar').innerText = (msg.sender_name || "A").substring(0,1).toUpperCase();
    document.getElementById('vSubject').innerText = msg.subject || "(No Subject)";
    document.getElementById('vBody').innerText = msg.message_content;

    
    if (el.classList.contains('unread')) {
        await doAction({action: 'mark_message_read', message_id: msg.id});
        el.classList.remove('unread');
        el.style.boxShadow = 'none';
        el.querySelector('[style*="color:var(--role-accent)"]')?.remove();
    }
    
    openModal('mdlViewMsg');
}

function replyToCurrent() {
    if(!currentViewingMsg) return;
    closeModal('mdlViewMsg');
    
    // Pre-fill compose
    const f = document.getElementById('frmCompose');
    f.receiver_id.value = currentViewingMsg.sender_id;
    f.subject.value = "Re: " + (currentViewingMsg.subject || "");
    f.body.value = "\n\n----- Original Message -----\n" + currentViewingMsg.body;
    
    
    openModal('mdlCompose');
    f.body.focus();
    f.body.setSelectionRange(0, 0);
}

// Load recipients on open
document.addEventListener('DOMContentLoaded', async () => {
    const res = await staffFetch({action: 'get_available_recipients'});
    if (res && res.success && res.recipients) {
        const sel = document.getElementById('msgRecipientSelect');
        sel.innerHTML = '<option value="">Select Recipient</option>';
        res.recipients.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = `${r.full_name} (${r.role.replace('_', ' ')})`;
            sel.appendChild(opt);
        });
    }
});
</script>


<style>
.msg-bubble:hover { background: rgba(47,128,237,0.04) !important; transform: translateX(5px); }
.msg-bubble.unread { background: color-mix(in srgb, var(--role-accent) 5%, transparent 95%) !important; }
.card { border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
</style>

