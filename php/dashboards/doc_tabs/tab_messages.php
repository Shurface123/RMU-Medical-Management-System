<?php
// ============================================================
// DOCTOR DASHBOARD - MESSAGES 
// ============================================================
if (!isset($conn)) exit;

// ── GET STAFF (For recipient list) ────────────────
$staff_list = [];
$q_staff = mysqli_query($conn, "
    SELECT id AS user_id, name, user_role 
    FROM users 
    WHERE user_role IN ('lab_technician', 'pharmacist', 'nurse', 'admin') AND id != $user_id
    ORDER BY user_role ASC, name ASC
");
if ($q_staff) {
    while($r = mysqli_fetch_assoc($q_staff)) $staff_list[] = $r;
}

// ── GET INBOX MESSAGES ───────────────────────────────────────
$inbox = [];
$q_inbox = mysqli_query($conn, "
    SELECT m.*, u.name AS sender_name, u.user_role AS sender_role_display
    FROM lab_internal_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = $user_id AND m.receiver_role = 'doctor'
    ORDER BY m.sent_at DESC
");
if ($q_inbox) {
    while($r = mysqli_fetch_assoc($q_inbox)) $inbox[] = $r;
}

// ── GET SENT MESSAGES ────────────────────────────────────────
$sent = [];
$q_sent = mysqli_query($conn, "
    SELECT m.*, u.name AS receiver_name, u.user_role AS receiver_role_display
    FROM lab_internal_messages m
    JOIN users u ON m.receiver_id = u.id
    WHERE m.sender_id = $user_id AND m.sender_role = 'doctor'
    ORDER BY m.sent_at DESC LIMIT 50
");
if ($q_sent) {
    while($r = mysqli_fetch_assoc($q_sent)) $sent[] = $r;
}
?>

<div class="dash-section <?= ($active_tab === 'messages') ? 'active' : '' ?>" id="sec-messages" style="animation:fadeIn 0.4s ease;">

    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--primary); margin-bottom:.3rem;"><i class="fas fa-comments pulse-fade"></i> Clinical Communications</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Secure messaging between physicians, lab staff, and pharmacy units.</p>
        </div>
        <button class="adm-btn adm-adm-btn adm-btn-primary" onclick="document.getElementById('composeForm').reset(); document.getElementById('composeModal').style.display='flex';" style="border-radius:12px; font-weight:700;"><span class="btn-text">
            <i class="fas fa-pen"></i> New Message
        </span></button>
    </div>

    <!-- Messages Layout Grid -->
    <div style="display:grid; grid-template-columns: 380px 1fr; gap: 2rem; align-items: start;">
        
        <!-- Folders & Message List Card -->
        <div class="adm-card shadow-sm" style="margin-bottom:0; height:650px; display:flex; flex-direction:column;">
            <div class="adm-card-header" style="padding: 1.5rem 2rem; background:rgba(47,128,237,0.03); border-bottom:1.5px solid var(--border);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                     <h3 style="font-size:1.4rem; font-weight:800; color:var(--primary); margin:0;">Mailbox</h3>
                     <i class="fas fa-search" style="color:var(--text-muted); cursor:pointer; opacity:0.5;"></i>
                </div>
                <div style="display:flex; gap:1.5rem; border-bottom:1.5px solid var(--border);">
                    <button class="adm-btn adm-btn-ghost tab-link active" id="inbox-ftab" onclick="switchFolder('inbox')" style="padding:1rem 0; font-weight:800; font-size:1.2rem; color:var(--primary); border-bottom:3px solid var(--primary); background:none; border:none; cursor:pointer; display:flex; align-items:center; gap:.8rem;"><span class="btn-text">
                        <i class="fas fa-inbox"></i> Inbox
                        <?php 
                            $unread = array_reduce($inbox, fn($c,$m) => $c + ($m['is_read']?0:1), 0);
                            if($unread > 0) echo "<span style='background:var(--danger); color:#fff; font-size:.9rem; padding:.2rem .6rem; border-radius:10px; font-weight:900;'>$unread</span>";
                        ?>
                    </span></button>
                    <button class="adm-btn adm-btn-ghost tab-link" id="sent-ftab" onclick="switchFolder('sent')" style="padding:1rem 0; font-weight:700; font-size:1.2rem; color:var(--text-muted); border-bottom:3px solid transparent; background:none; border:none; cursor:pointer; display:flex; align-items:center; gap:.8rem;"><span class="btn-text">
                        <i class="fas fa-paper-plane"></i> Sent
                    </span></button>
                </div>
            </div>
            
            <div class="adm-card-body custom-scrollbar" style="padding:0; overflow-y: auto; flex:1;">
                <!-- Inbox List -->
                <div id="inbox-list" class="folder-content">
                    <?php if(empty($inbox)): ?>
                        <div style="padding:5rem 2rem; text-align:center; color:var(--text-muted);">
                            <i class="fas fa-envelope-open-text" style="font-size:4rem; opacity:0.1; margin-bottom:1.5rem; display:block;"></i>
                            <div style="font-weight:700; font-size:1.4rem;">Inbox Empty</div>
                            <p style="font-size:1.1rem; margin-top:.5rem;">Your secure clinical inbox is clear.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($inbox as $m): 
                            $is_unread = !$m['is_read'];
                        ?>
                            <div class="activity-item msg-item <?= $is_unread ? 'unread-msg' : '' ?>" style="padding: 1.8rem 2rem; cursor:pointer; border-bottom:1px solid var(--border); transition:0.2s ease;" onclick="viewMessage('inbox', <?= htmlspecialchars(json_encode($m)) ?>, this)">
                                <div class="activity-dot" style="<?= $is_unread ? 'background:var(--primary); box-shadow:0 0 8px var(--primary);' : 'background:var(--border);' ?> width:10px; height:10px; left:12px; top:28px;"></div>
                                <div style="flex:1; min-width:0;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.4rem;">
                                        <h6 style="margin:0; font-size:1.4rem; font-weight:<?= $is_unread ? '800' : '600' ?>; color:var(--text-primary);" class="text-truncate">
                                            <?= e($m['sender_name']) ?>
                                        </h6>
                                        <small style="color:var(--text-muted); font-size:1.1rem; font-weight:700;"><?= date('H:i', strtotime($m['sent_at'])) ?></small>
                                    </div>
                                    <div style="font-size:1.25rem; color:<?= $is_unread ? 'var(--primary)' : 'var(--text-secondary)' ?>; font-weight:<?= $is_unread ? '700' : '500' ?>; margin-bottom:0.4rem;" class="text-truncate">
                                        <?= e($m['subject'] ?? 'Clinical Communication') ?>
                                    </div>
                                    <div style="font-size:1.15rem; color:var(--text-muted); opacity:0.8;" class="text-truncate"><?= e(substr($m['message_content'] ?? $m['message'] ?? '', 0, 50)) ?>...</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Sent List -->
                <div id="sent-list" class="folder-content" style="display:none;">
                    <?php if(empty($sent)): ?>
                         <div style="padding:5rem 2rem; text-align:center; color:var(--text-muted);">
                            <i class="fas fa-paper-plane" style="font-size:4rem; opacity:0.1; margin-bottom:1.5rem; display:block;"></i>
                            <div style="font-weight:700; font-size:1.4rem;">No Sent Messages</div>
                        </div>
                    <?php else: ?>
                        <?php foreach($sent as $m): ?>
                            <div class="activity-item msg-item" style="padding: 1.8rem 2rem; cursor:pointer; border-bottom:1px solid var(--border);" onclick="viewMessage('sent', <?= htmlspecialchars(json_encode($m)) ?>, this)">
                                <div class="activity-dot" style="background:var(--border); width:10px; height:10px; left:12px; top:28px;"></div>
                                <div style="flex:1; min-width:0;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.4rem;">
                                        <h6 style="margin:0; font-size:1.4rem; font-weight:600; color:var(--text-primary);" class="text-truncate">
                                            To: <?= e($m['receiver_name']) ?>
                                        </h6>
                                        <small style="color:var(--text-muted); font-size:1.1rem; font-weight:700;"><?= date('d M', strtotime($m['sent_at'])) ?></small>
                                    </div>
                                    <div style="font-size:1.25rem; color:var(--text-secondary); margin-bottom:0.4rem;" class="text-truncate">
                                        <?= e($m['subject'] ?? 'Clinical Communication') ?>
                                    </div>
                                    <div style="font-size:1.15rem; color:var(--text-muted); display:flex; align-items:center; gap:.5rem;">
                                        <?php if($m['is_read']): ?><i class="fas fa-check-double text-success" style="font-size:1rem;"></i><?php endif; ?>
                                        <span class="text-truncate"><?= e(substr($m['message_content'] ?? $m['message'] ?? '', 0, 50)) ?>...</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reading Pane Card -->
        <div class="adm-card shadow-sm" style="margin-bottom:0; height:650px; display:flex; flex-direction:column; background:var(--surface-1);">
                <div id="read-pane-empty" style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0.4;">
                    <div style="width: 120px; height: 120px; border-radius:50%; background:var(--surface-3); display:flex; align-items:center; justify-content:center; margin-bottom:2rem;">
                         <i class="fas fa-envelope-open" style="font-size:4rem; color:var(--text-muted);"></i>
                    </div>
                    <h5 style="font-size:1.6rem; font-weight:700; color:var(--text-muted);">Select a clinical message to read</h5>
                    <p style="font-size:1.2rem; color:var(--text-muted);">Patient privacy is protected during communication.</p>
                </div>

                <div id="read-pane-content" style="display:none; flex-direction:column; height:100%;">
                    <div style="padding:3rem; background:rgba(47,128,237,0.03); border-bottom:1.5px solid var(--border);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:2.5rem;">
                             <h4 id="msgSubject" style="font-size:2.2rem; font-weight:800; color:var(--text-primary); margin:0;">Subject</h4>
                             <div style="display:flex; gap:1rem;">
                                 <button class="btn-icon adm-btn adm-btn-ghost" style="padding:.5rem 1rem; border-radius:10px;"><span class="btn-text"><i class="fas fa-print"></i></span></button>
                                 <button class="adm-btn adm-btn-ghost text-danger" style="padding:.5rem 1rem; border-radius:10px; border-color:rgba(231,76,60,0.2);"><span class="btn-text"><i class="fas fa-trash"></i></span></button>
                             </div>
                        </div>
                        
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:1.5rem;">
                                <div id="msgAvatar" style="width:56px; height:56px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:2rem; color:#fff; box-shadow:0 8px 15px rgba(47,128,237,0.2);">?</div>
                                <div>
                                    <h6 id="msgFromTo" style="margin:0; font-size:1.6rem; font-weight:700; color:var(--text-primary);">Sender</h6>
                                    <small id="msgDate" style="color:var(--text-muted); font-size:1.2rem; font-weight:600;">Date Time</small>
                                </div>
                            </div>
                            <button class="adm-btn adm-adm-btn adm-btn-primary" id="btnReply" onclick="replyMessage()" style="padding:.8rem 2.5rem; border-radius:12px; font-weight:700;"><span class="btn-text"><i class="fas fa-reply" style="margin-right:.6rem;"></i> Reply</span></button>
                        </div>
                    </div>

                    <div id="msgBody" class="custom-scrollbar" style="padding:4rem 3.5rem; overflow-y:auto; flex:1; font-size:1.5rem; line-height:1.8; color:var(--text-primary); background:#fff;">
                        Message Content
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width:6px; }
.custom-scrollbar::-webkit-scrollbar-track { background:transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background:var(--border); border-radius:10px; }
.unread-msg { background: rgba(47,128,237, 0.04); }
.active-msg { background: rgba(47,128,237, 0.1) !important; border-left: 5px solid var(--primary) !important; }
.msg-item:hover { background: var(--surface-2); }
</style>

<!-- ========================================== -->
<!-- MODAL: COMPOSE MESSAGE                     -->
<!-- ========================================== -->
<div class="modal-bg" id="composeModal">
    <div class="modal-box" style="max-width:800px; padding:0; overflow:hidden;">
        <div class="modal-header" style="background:linear-gradient(135deg, var(--primary), #1C3A6B); padding:2rem 3rem; margin:0;">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-pen-nib"></i> Compose Secure Clinical Message</h3>
            <button class="adm-btn modal-close" onclick="document.getElementById('composeModal').style.display='none'" type="button" style="color:#fff; background:transparent; font-size:2rem; padding:0;"><span class="btn-text">&times;</span></button>
        </div>
        <div style="padding:3rem;">
            <form id="composeForm">
                <input type="hidden" name="action" value="send_msg">
                <input type="hidden" name="to_role" id="compRole" value="">
                
                <div style="display:grid; grid-template-columns:1fr; gap:2rem; margin-bottom:2rem;">
                    <div class="form-group">
                        <label style="display:block; font-size:1.1rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Recipient</label>
                        <select class="form-control" name="to_user_id" id="compReceiver" required style="padding:1rem; font-weight:800; font-size:1.4rem; border:2px solid var(--primary);" onchange="document.getElementById('compRole').value = this.options[this.selectedIndex].getAttribute('data-role');">
                            <option value="">-- select staff member --</option>
                            <?php foreach($staff_list as $s): ?>
                                <option value="<?= $s['user_id'] ?>" data-role="<?= $s['user_role'] ?>"><?= strtoupper(str_replace('_',' ',e($s['user_role']))) ?>: <?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:2rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Message Instructions / Narrative</label>
                    <textarea class="form-control" name="message" id="compBody" rows="7" placeholder="Provide detailed clinical query..." required style="padding:1.5rem; font-size:1.3rem; line-height:1.6; font-weight:500; border:1px solid var(--border);"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('composeModal').style.display='none'" style="font-weight:700;"><span class="btn-text">Discard</span></button>
                    <button type="submit" class="adm-btn adm-adm-btn adm-btn-primary" id="btnSendMsg" style="padding:.8rem 5rem; font-weight:900; border-radius:12px; font-size:1.4rem;"><span class="btn-text">
                        <i class="fas fa-paper-plane" style="margin-right:.8rem;"></i> TRANSMIT MESSAGE
                    </span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentMsg = null;
let currentFolder = 'inbox';

function switchFolder(folder) {
    $('.tab-link').removeClass('active').css({'color': 'var(--text-muted)', 'border-bottom-color': 'transparent', 'font-weight': '700'});
    $(`#${folder}-ftab`).addClass('active').css({'color': 'var(--primary)', 'border-bottom-color': 'var(--primary)', 'font-weight': '800'});
    
    if(folder === 'inbox') {
        $('#inbox-list').show();
        $('#sent-list').hide();
    } else {
        $('#inbox-list').hide();
        $('#sent-list').show();
    }
}

function viewMessage(folder, msg, element) {
    currentMsg = msg;
    currentFolder = folder;
    
    $('.msg-item').removeClass('active-msg shadow-sm');
    $(element).addClass('active-msg shadow-sm');
    
    $('#read-pane-empty').hide();
    $('#read-pane-content').css('display', 'flex').css('opacity', '0').animate({ opacity: 1 }, 200);
    
    const subj = msg.subject || 'Clinical Communication';
    const content = msg.message_content || msg.message || '';
    $('#msgSubject').text(subj);
    $('#msgBody').html(content.replace(/\n/g, '<br>'));
    
    const msgDate = new Date(msg.sent_at);
    $('#msgDate').text(msgDate.toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit' }));
    
    if(folder === 'inbox') {
        const initial = msg.sender_name.charAt(0).toUpperCase();
        $('#msgAvatar').text(initial).css('background', 'var(--primary)');
        $('#msgFromTo').html(`${msg.sender_name} <small style="font-weight:600; color:var(--text-muted); margin-left:.5rem; font-size:1.2rem;">to me</small>`);
        $('#btnReply').show();
        
        if(msg.is_read == 0) {
            docAction({ action: 'mark_msg_read', msg_id: msg.id });
            msg.is_read = 1;
            $(element).find('.activity-dot').css({'background': 'var(--border)', 'box-shadow': 'none'});
            $(element).find('h6').css('font-weight', '600');
            $(element).removeClass('unread-msg');
        }
    } else {
        const initial = msg.receiver_name.charAt(0).toUpperCase();
        $('#msgAvatar').text(initial).css('background', 'var(--info)');
        $('#msgFromTo').html(`Me <small style="font-weight:600; color:var(--text-muted); margin-left:.5rem; font-size:1.2rem;">to ${msg.receiver_name}</small>`);
        $('#btnReply').hide();
    }
}

function replyMessage() {
    if(!currentMsg || currentFolder !== 'inbox') return;
    document.getElementById('composeForm').reset();
    $('#compReceiver').val(currentMsg.sender_id);
    
    // Select the correct role
    const sel = document.getElementById('compReceiver');
    $('#compRole').val(sel.options[sel.selectedIndex].getAttribute('data-role'));
    
    $('#compBody').val('\n\n--- Original Message ---\n' + (currentMsg.message_content || currentMsg.message || '')).focus();
    document.getElementById('composeModal').style.display='flex';
}

$(document).ready(function() {
    $('#composeForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSendMsg');
        const origHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Transmitting...');
        
        const data = {
            action: 'send_msg',
            to_user_id: $('#compReceiver').val(),
            to_role: $('#compRole').val(),
            message: $('#compBody').val()
        };

        docAction(data).then(res => {
            if(res.success) {
                toast('Message Transmitted Successfully');
                document.getElementById('composeModal').style.display='none';
                window.location.href = '?tab=messages';
            } else {
                toast(res.message || 'Error', 'danger');
                btn.prop('disabled', false).html(origHtml);
            }
        });
    });
});
</script>
