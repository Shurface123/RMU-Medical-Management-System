<!-- ═══════════════════════════════════════════════════════════
     MODULE 10: DOCTOR-NURSE COMMUNICATION — tab_messages.php
     ═══════════════════════════════════════════════════════════ -->
<?php
$conversations = dbSelect($conn,
    "SELECT m.*, u.name AS other_name, u.profile_image AS other_photo,
            u.is_active AS other_online,
            (SELECT COUNT(*) FROM nurse_doctor_messages m2 WHERE m2.sender_id=m.sender_id AND m2.receiver_id=? AND m2.is_read=0) AS unread_count
     FROM nurse_doctor_messages m
     JOIN users u ON (CASE WHEN m.sender_id=? THEN m.receiver_id ELSE m.sender_id END)=u.id
     WHERE m.sender_id=? OR m.receiver_id=?
     ORDER BY m.sent_at DESC","iiii",[$user_id,$user_id,$user_id,$user_id]);

// Deduplicate to unique conversations
$unique_convos = [];
foreach($conversations as $c){
  $other_id = ($c['sender_id']==$user_id) ? $c['receiver_id'] : $c['sender_id'];
  if(!isset($unique_convos[$other_id])){
    $unique_convos[$other_id] = [
      'other_id'=>$other_id,'other_name'=>$c['other_name'],'other_photo'=>$c['other_photo'],
      'other_online'=>$c['other_online'],'last_message'=>$c['message_text'],
      'last_time'=>$c['sent_at'],'unread'=>(int)($c['unread_count']??0)
    ];
  }
}

$doctors_list = dbSelect($conn,
    "SELECT u.id, u.name, u.profile_image, u.is_active
     FROM users u WHERE u.user_role='doctor' AND u.is_active=1
     ORDER BY u.name ASC");
?>
<div id="sec-messages" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-comment-medical"></i> Doctor-Nurse Messages</h2>
    <button class="btn btn-primary" onclick="openModal('newMessageModal')"><i class="fas fa-plus"></i> New Message</button>
  </div>

  <div style="display:grid;grid-template-columns:300px 1fr;gap:1.5rem;min-height:500px;">
    <!-- ── Conversation List ── -->
    <div class="info-card" style="max-height:600px;overflow-y:auto;">
      <h4 style="margin-bottom:1rem;">Conversations</h4>
      <?php if(empty($unique_convos)):?>
        <p class="text-center text-muted" style="padding:2rem;">No conversations yet</p>
      <?php else: foreach($unique_convos as $oid => $cv):?>
        <div style="display:flex;align-items:center;gap:.8rem;padding:.8rem;border-radius:var(--radius-sm);cursor:pointer;transition:var(--transition);border-bottom:1px solid var(--border);"
             onclick="loadConversation(<?=$oid?>,'<?=e($cv['other_name'])?>')" class="msg-conv-item" data-id="<?=$oid?>">
          <div style="position:relative;">
            <?php if($cv['other_photo'] && $cv['other_photo']!=='default-avatar.png'):?>
              <img src="/RMU-Medical-Management-System/<?=e($cv['other_photo'])?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
            <?php else:?>
              <div style="width:40px;height:40px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;"><?=strtoupper(substr($cv['other_name'],0,1))?></div>
            <?php endif;?>
            <span style="position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;background:<?=$cv['other_online']?'var(--success)':'var(--text-muted)'?>;border:2px solid var(--surface);"></span>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:1.2rem;"><?=e($cv['other_name'])?></div>
            <div style="font-size:1rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=e(substr($cv['last_message'],0,40))?></div>
          </div>
          <?php if($cv['unread']>0):?><span class="badge badge-danger"><?=$cv['unread']?></span><?php endif;?>
        </div>
      <?php endforeach; endif;?>
    </div>

    <!-- ── Message Thread ── -->
    <div class="info-card" style="display:flex;flex-direction:column;">
      <div id="msgThreadHeader" style="display:flex;align-items:center;gap:.8rem;padding-bottom:1rem;border-bottom:1px solid var(--border);margin-bottom:1rem;">
        <h4 id="msgThreadTitle" style="flex:1;">Select a conversation</h4>
      </div>
      <div id="msgThreadBody" style="flex:1;overflow-y:auto;max-height:400px;padding:1rem 0;">
        <p class="text-center text-muted" style="padding:3rem;"><i class="fas fa-comments" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.3;"></i>Select a conversation to view messages</p>
      </div>
      <div id="msgReplyBox" style="display:none;border-top:1px solid var(--border);padding-top:1rem;margin-top:1rem;">
        <div style="display:flex;gap:.8rem;">
          <input type="hidden" id="msg_receiver_id">
          <input id="msg_reply_text" class="form-control" style="flex:1;" placeholder="Type your message..." onkeypress="if(event.key==='Enter')sendReply()">
          <button class="btn btn-primary" onclick="sendReply()"><i class="fas fa-paper-plane"></i></button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════ NEW MESSAGE MODAL ═══════ -->
<div class="modal-bg" id="newMessageModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-paper-plane" style="color:var(--role-accent);"></i> New Message</h3><button class="modal-close" onclick="closeModal('newMessageModal')"><i class="fas fa-times"></i></button></div>
    <div class="form-group"><label>Doctor *</label>
      <select id="nm_doctor" class="form-control"><option value="">Select Doctor</option>
        <?php foreach($doctors_list as $d):?>
          <option value="<?=$d['id']?>"><?=e($d['name'])?> <?=$d['is_active']?'🟢':'🔴'?></option>
        <?php endforeach;?></select>
    </div>
    <div class="form-group"><label>Related Patient (optional)</label>
      <select id="nm_patient" class="form-control"><option value="">None</option>
        <?php foreach($all_patients_for_vitals as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?></option><?php endforeach;?></select>
    </div>
    <div class="form-group"><label>Message *</label><textarea id="nm_message" class="form-control" rows="4" placeholder="Type your message..."></textarea></div>
    <div class="form-group"><label>Priority</label>
      <select id="nm_priority" class="form-control"><option value="Normal">Normal</option><option value="Urgent">Urgent</option></select>
    </div>
    <button class="btn btn-primary" onclick="sendNewMessage()" style="width:100%;"><i class="fas fa-paper-plane"></i> Send Message</button>
  </div>
</div>

<script>
let currentConvoId = null;

async function loadConversation(doctorId, name){
  currentConvoId = doctorId;
  document.getElementById('msgThreadTitle').textContent = 'Dr. ' + name;
  document.getElementById('msg_receiver_id').value = doctorId;
  document.getElementById('msgReplyBox').style.display = 'block';
  document.querySelectorAll('.msg-conv-item').forEach(el=>el.style.background='');
  document.querySelector(`.msg-conv-item[data-id="${doctorId}"]`)?.style.setProperty('background','var(--role-accent-light)');

  const r = await nurseAction({action:'get_messages', other_user_id: doctorId});
  if(!r.success){document.getElementById('msgThreadBody').innerHTML='<p class="text-center" style="color:var(--danger);">Error loading messages</p>';return;}

  const msgs = r.data || [];
  const myId = <?=$user_id?>;
  document.getElementById('msgThreadBody').innerHTML = msgs.length===0 ? '<p class="text-center text-muted">No messages yet</p>' :
    msgs.map(m => {
      const isMine = m.sender_id == myId;
      return `<div style="display:flex;justify-content:${isMine?'flex-end':'flex-start'};margin-bottom:.8rem;">
        <div style="max-width:70%;padding:.8rem 1.2rem;border-radius:${isMine?'16px 16px 4px 16px':'16px 16px 16px 4px'};
          background:${isMine?'var(--role-accent)':'var(--surface-2)'};color:${isMine?'#fff':'var(--text-primary)'};font-size:1.2rem;">
          <div>${m.message_text}</div>
          <div style="font-size:.9rem;opacity:.7;margin-top:.3rem;text-align:right;">${new Date(m.sent_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</div>
        </div></div>`;
    }).join('');

  // Scroll to bottom
  const body = document.getElementById('msgThreadBody');
  body.scrollTop = body.scrollHeight;

  // Mark as read
  nurseAction({action:'mark_messages_read', other_user_id: doctorId});
}

async function sendReply(){
  const text = document.getElementById('msg_reply_text').value.trim();
  if(!text) return;
  const r = await nurseAction({action:'send_message', receiver_id: document.getElementById('msg_receiver_id').value, message_text: text});
  if(r.success){
    document.getElementById('msg_reply_text').value='';
    loadConversation(currentConvoId, document.getElementById('msgThreadTitle').textContent.replace('Dr. ',''));
  } else { showToast(r.message||'Error','error'); }
}

async function sendNewMessage(){
  if(!validateForm({nm_doctor:'Doctor',nm_message:'Message'})) return;
  const r = await nurseAction({action:'send_message', receiver_id: document.getElementById('nm_doctor').value,
    message_text: document.getElementById('nm_message').value,
    patient_id: document.getElementById('nm_patient').value,
    priority: document.getElementById('nm_priority').value});
  showToast(r.message||'Sent',r.success?'success':'error');
  if(r.success){closeModal('newMessageModal');setTimeout(()=>location.reload(),1000);}
}
</script>
