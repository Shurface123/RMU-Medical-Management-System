<?php
// ═══════════════ MODULE 8: DOCTOR-LAB COMMUNICATION ═══════════════
// Fetch doctors for compose
$doctors_list=[];
$q=mysqli_query($conn,"SELECT d.id AS doc_pk, u.id AS uid, u.name, d.specialization FROM doctors d JOIN users u ON d.user_id=u.id WHERE u.status='active' ORDER BY u.name");
if($q) while($r=mysqli_fetch_assoc($q)) $doctors_list[]=$r;

// Group messages into threads by unique sender-recipient-order combos
$threads=[]; $thread_map=[];
foreach($messages as $m){
  $key=min($m['sender_id'],$m['recipient_id']).'-'.max($m['sender_id'],$m['recipient_id']);
  if(!isset($thread_map[$key])){$thread_map[$key]=count($threads);$threads[]=['messages'=>[],'other_name'=>'','unread'=>0,'last_ts'=>'','order_id'=>$m['order_id']??null];}
  $idx=$thread_map[$key];
  $threads[$idx]['messages'][]=$m;
  if(!$threads[$idx]['other_name']) $threads[$idx]['other_name']=($m['sender_id']==$user_id)?($m['recipient_name']??'—'):($m['sender_name']??'—');
  if(!$m['is_read']&&$m['recipient_id']==$tech_pk) $threads[$idx]['unread']++;
  if(!$threads[$idx]['last_ts']||$m['created_at']>$threads[$idx]['last_ts']) $threads[$idx]['last_ts']=$m['created_at'];
}
usort($threads,function($a,$b){return strtotime($b['last_ts'])-strtotime($a['last_ts']);});
?>
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-comments" style="color:var(--role-accent);margin-right:.6rem;"></i> Doctor-Lab Communication</h1>
    <p>Direct messaging with doctors, order clarification requests, and critical value alerts</p>
  </div>
  <button class="adm-btn adm-btn-primary" onclick="openModal('composeModal')"><i class="fas fa-pen"></i> New Message</button>
</div>

<div style="display:grid;grid-template-columns:350px 1fr;gap:2rem;min-height:500px;">
  <!-- Thread List -->
  <div class="adm-card" style="margin-bottom:0;">
    <div class="adm-card-header"><h3><i class="fas fa-inbox"></i> Conversations</h3></div>
    <div class="adm-card-body" style="padding:0;max-height:500px;overflow-y:auto;" id="threadList">
      <?php if(empty($threads)):?>
        <div style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-comments" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>No conversations yet</div>
      <?php else: foreach($threads as $ti=>$th):?>
        <div class="adm-nav-item" style="padding:1.2rem 1.5rem;margin:0;border-radius:0;border-bottom:1px solid var(--border);color:var(--text-primary);cursor:pointer;<?=$th['unread']?'background:var(--primary-light);':''?>" onclick="openThread(<?=$ti?>)">
          <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--role-accent),#C39BD3);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;"><?=strtoupper(substr($th['other_name'],0,1))?></div>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <strong style="font-size:1.3rem;"><?=e($th['other_name'])?></strong>
              <?php if($th['unread']):?><span class="adm-badge adm-badge-danger" style="font-size:.9rem;"><?=$th['unread']?></span><?php endif;?>
            </div>
            <div style="font-size:1.1rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?=e(substr($th['messages'][count($th['messages'])-1]['message']??'',0,50))?><?=strlen($th['messages'][count($th['messages'])-1]['message']??'')>50?'...':''?>
            </div>
            <div style="font-size:1rem;color:var(--text-muted);"><?=date('d M h:i A',strtotime($th['last_ts']))?></div>
          </div>
        </div>
      <?php endforeach; endif;?>
    </div>
  </div>

  <!-- Message Viewer -->
  <div class="adm-card" style="margin-bottom:0;display:flex;flex-direction:column;">
    <div class="adm-card-header" id="msgHeader"><h3><i class="fas fa-envelope"></i> Select a conversation</h3></div>
    <div class="adm-card-body" style="flex:1;overflow-y:auto;max-height:400px;" id="msgBody">
      <div style="text-align:center;color:var(--text-muted);padding:4rem 0;"><i class="fas fa-envelope-open" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>Select a conversation to view messages</div>
    </div>
    <div style="padding:1.5rem 2rem;border-top:1px solid var(--border);display:none;" id="msgReplyBar">
      <div style="display:flex;gap:1rem;">
        <input id="replyInput" class="form-control" style="margin:0;flex:1;" placeholder="Type a message..." onkeypress="if(event.key==='Enter')sendReply()">
        <button class="adm-btn adm-btn-primary" onclick="sendReply()"><i class="fas fa-paper-plane"></i></button>
      </div>
    </div>
  </div>
</div>

<!-- Compose Modal -->
<div class="modal-bg" id="composeModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-pen"></i> New Message</h3><button class="modal-close" onclick="closeModal('composeModal')">&times;</button></div>
    <div class="form-group">
      <label>Recipient Doctor *</label>
      <select id="msg_doctor" class="form-control">
        <option value="">Select doctor...</option>
        <?php foreach($doctors_list as $dl):?><option value="<?=$dl['uid']?>"><?=e($dl['name'])?><?=$dl['specialization']?' — '.e($dl['specialization']):''?></option><?php endforeach;?>
      </select>
    </div>
    <div class="form-group">
      <label>Related Order (optional)</label>
      <select id="msg_order" class="form-control">
        <option value="">None</option>
        <?php foreach($all_orders as $o):?><option value="<?=$o['id']?>"><?=e($o['order_id'])?> — <?=e($o['test_name']??'')?> — <?=e($o['patient_name']??'')?></option><?php endforeach;?>
      </select>
    </div>
    <div class="form-group">
      <label>Message Type</label>
      <select id="msg_type" class="form-control">
        <option value="General">General</option>
        <option value="Critical Alert">⚠️ Critical Value Alert</option>
        <option value="Order Clarification">Order Clarification Request</option>
        <option value="Result Notification">Result Notification</option>
      </select>
    </div>
    <div class="form-group"><label>Subject</label><input id="msg_subject" class="form-control" placeholder="Message subject"></div>
    <div class="form-group"><label>Message *</label><textarea id="msg_body" class="form-control" rows="4" placeholder="Type your message..."></textarea></div>
    <div class="form-row">
      <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="sendMessage()"><i class="fas fa-paper-plane"></i> Send Message</button>
      <button class="adm-btn adm-btn-danger" style="width:100%;" onclick="sendMessage('critical')"><i class="fas fa-exclamation-triangle"></i> Send Critical Alert</button>
    </div>
  </div>
</div>

<script>
const allThreads=<?=json_encode($threads)?>;
let activeThreadIdx=null;

function openThread(idx){
  activeThreadIdx=idx;
  const th=allThreads[idx];
  document.getElementById('msgHeader').innerHTML='<h3><i class="fas fa-user-md"></i> '+th.other_name+'</h3>';
  let html='';
  th.messages.forEach(m=>{
    const mine=m.sender_id==<?=$user_id?>;
    html+='<div style="display:flex;justify-content:'+(mine?'flex-end':'flex-start')+';margin-bottom:1rem;">';
    html+='<div style="max-width:70%;padding:1rem 1.4rem;border-radius:'+(mine?'14px 14px 4px 14px':'14px 14px 14px 4px')+';background:'+(mine?'var(--role-accent)':'var(--surface-2)')+';color:'+(mine?'#fff':'var(--text-primary)')+';font-size:1.3rem;">';
    if(m.subject) html+='<div style="font-weight:700;margin-bottom:.3rem;">'+m.subject+'</div>';
    if(m.message_type&&m.message_type!=='General') html+='<span class="adm-badge '+(m.message_type.includes('Critical')?'adm-badge-danger':'adm-badge-info')+'" style="font-size:.9rem;margin-bottom:.5rem;display:inline-block;">'+m.message_type+'</span> ';
    html+=m.message;
    html+='<div style="font-size:.95rem;opacity:.7;margin-top:.4rem;text-align:right;">'+new Date(m.created_at).toLocaleString('en-GB',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'})+'</div>';
    html+='</div></div>';
  });
  document.getElementById('msgBody').innerHTML=html;
  document.getElementById('msgBody').scrollTop=99999;
  document.getElementById('msgReplyBar').style.display='block';
  // Mark as read
  th.messages.forEach(m=>{if(!m.is_read&&m.recipient_id==<?=$tech_pk?>) labAction({action:'mark_message_read',id:m.id});});
}
async function sendReply(){
  const msg=document.getElementById('replyInput').value.trim();if(!msg||activeThreadIdx===null)return;
  const th=allThreads[activeThreadIdx];
  const lastMsg=th.messages[th.messages.length-1];
  const recipientId=lastMsg.sender_id==<?=$user_id?>?lastMsg.recipient_id:lastMsg.sender_id;
  const r=await labAction({action:'send_message',recipient_id:recipientId,message:msg,order_id:th.order_id||'',message_type:'General',subject:''});
  if(r.success){document.getElementById('replyInput').value='';th.messages.push({sender_id:<?=$user_id?>,message:msg,created_at:new Date().toISOString(),is_read:0});openThread(activeThreadIdx);}
  else showToast(r.message,'error');
}
async function sendMessage(mode){
  const doc=document.getElementById('msg_doctor').value;
  const msg=document.getElementById('msg_body').value;
  if(!doc||!msg){showToast('Select doctor and enter message','error');return;}
  const mtype=mode==='critical'?'Critical Alert':document.getElementById('msg_type').value;
  const r=await labAction({action:'send_message',recipient_id:doc,message:msg,order_id:document.getElementById('msg_order').value,message_type:mtype,subject:document.getElementById('msg_subject').value});
  showToast(r.message,r.success?'success':'error');
  if(r.success){closeModal('composeModal');setTimeout(()=>location.reload(),800);}
}
</script>
