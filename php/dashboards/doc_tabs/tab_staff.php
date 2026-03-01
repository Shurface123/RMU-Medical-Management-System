<?php // TAB: STAFF DIRECTORY ?>
<div id="sec-staff" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-address-book"></i> Staff Directory</h2>
  </div>

  <div class="filter-tabs">
    <button class="ftab active" onclick="filterStaff('all',this)">All Staff</button>
    <?php foreach(['Doctor','Nurse','Lab Technician','Pharmacist','Admin'] as $sr):?>
    <button class="ftab" onclick="filterStaff('<?=$sr?>',this)"><?=$sr?></button>
    <?php endforeach;?>
  </div>

  <div style="margin-bottom:1.2rem;">
    <div class="adm-search-wrap"><i class="fas fa-search"></i>
      <input type="text" class="adm-search-input" id="staffSearch" placeholder="Search by name, role, or department…" oninput="filterTable('staffSearch','staffTable')">
    </div>
  </div>

  <div class="cards-grid">
    <?php if(empty($staff)):?>
      <div class="adm-card" style="grid-column:1/-1;text-align:center;padding:3rem;">
        <i class="fas fa-users-slash" style="font-size:2.5rem;opacity:.3;margin-bottom:1rem;display:block;"></i>
        <p style="color:var(--text-muted);">No staff records found. Add staff to the staff directory.</p>
      </div>
    <?php else: foreach($staff as $s):
      $role=$s['role']??$s['role']??'Staff';
      $icon=match($role){'Doctor'=>'fa-user-md','Nurse'=>'fa-user-nurse','Lab Technician'=>'fa-flask','Pharmacist'=>'fa-pills',default=>'fa-user'};
      $sc=match($s['status']??'Active'){'Active'=>'success','On Leave'=>'warning','Inactive'=>'info',default=>'danger'};
      $sj=json_encode(['name'=>$s['full_name'],'role'=>$role,'dept'=>$s['department']??'','phone'=>$s['phone']??'','email'=>$s['email']??'','status'=>$s['status']??'Active','staff_id'=>$s['staff_id']??''],JSON_HEX_QUOT|JSON_HEX_APOS);
    ?>
    <div class="info-card" data-staffrole="<?=$role?>">
      <div class="info-card-head">
        <div style="display:flex;align-items:center;gap:.8rem;">
          <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--role-accent),var(--primary));color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0;">
            <i class="fas <?=$icon?>"></i>
          </div>
          <div>
            <div style="font-weight:700;font-size:1.35rem;"><?=htmlspecialchars($s['full_name'])?></div>
            <div style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($role)?></div>
          </div>
        </div>
        <span class="adm-badge adm-badge-<?=$sc?>"><?=$s['status']??'Active'?></span>
      </div>
      <?php if($s['department']??''):?><div style="font-size:1.2rem;color:var(--text-secondary);margin-bottom:.7rem;"><i class="fas fa-building" style="color:var(--text-muted);margin-right:.5rem;"></i><?=htmlspecialchars($s['department']??'')?></div><?php endif;?>
      <?php if($s['phone']??''):?><div style="font-size:1.2rem;color:var(--text-secondary);margin-bottom:.4rem;"><i class="fas fa-phone" style="color:var(--role-accent);margin-right:.5rem;"></i><?=htmlspecialchars($s['phone']??'')?></div><?php endif;?>
      <?php if($s['email']??''):?><div style="font-size:1.1rem;color:var(--text-secondary);margin-bottom:1rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><i class="fas fa-envelope" style="color:var(--primary);margin-right:.5rem;"></i><?=htmlspecialchars($s['email']??'')?></div><?php endif;?>
      <div style="display:flex;gap:.5rem;">
        <?php if(in_array($role,['Nurse','Lab Technician'])):?>
        <button onclick='openStaffMsg(<?=$sj?>)' class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-paper-plane"></i> Send Note</button>
        <?php endif;?>
        <button onclick='viewStaff(<?=$sj?>)' class="adm-btn adm-btn-ghost adm-btn-sm"><i class="fas fa-eye"></i> View</button>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>
</div>

<!-- Modal: View Staff -->
<div class="modal-bg" id="modalViewStaff">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-address-card" style="color:var(--role-accent);"></i> Staff Profile</h3>
      <button class="modal-close" onclick="closeModal('modalViewStaff')">&times;</button>
    </div>
    <div id="staffDetail" style="font-size:1.3rem;line-height:2.2;"></div>
  </div>
</div>

<!-- Modal: Send Message/Note to Staff -->
<div class="modal-bg" id="modalStaffMsg">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-paper-plane" style="color:var(--role-accent);"></i> Send Instruction</h3>
      <button class="modal-close" onclick="closeModal('modalStaffMsg')">&times;</button>
    </div>
    <p id="staffMsgTarget" style="font-weight:600;font-size:1.4rem;margin-bottom:1.2rem;"></p>
    <input type="hidden" id="staffMsgUserId">
    <div class="form-group"><label>Message / Instruction</label>
      <textarea id="staffMsgContent" class="form-control" rows="4" placeholder="e.g. Please administer 500mg Paracetamol to Patient P001 at 2PM…"></textarea>
    </div>
    <button onclick="sendStaffMsg()" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-paper-plane"></i> Send Instruction</button>
  </div>
</div>

<script>
let currentStaffUserId=null;
function filterStaff(role,btn){
  document.querySelectorAll('.filter-tabs .ftab').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  document.querySelectorAll('.info-card[data-staffrole]').forEach(c=>{
    c.style.display=(role==='all'||c.dataset.staffrole===role)?'':'none';
  });
}
function viewStaff(s){
  document.getElementById('staffDetail').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
      <div><strong>Name</strong><br>${s.name}</div>
      <div><strong>Role</strong><br>${s.role}</div>
      <div><strong>Department</strong><br>${s.dept||'—'}</div>
      <div><strong>Status</strong><br><span class="adm-badge adm-badge-success">${s.status}</span></div>
      ${s.phone?`<div><strong>Phone</strong><br>${s.phone}</div>`:''}
      ${s.email?`<div><strong>Email</strong><br>${s.email}</div>`:''}
      ${s.staff_id?`<div><strong>Staff ID</strong><br>${s.staff_id}</div>`:''}
    </div>`;
  openModal('modalViewStaff');
}
function openStaffMsg(s){
  currentStaffUserId=s.user_id||null;
  document.getElementById('staffMsgTarget').textContent='To: '+s.name+' ('+s.role+')';
  document.getElementById('staffMsgUserId').value=s.user_id||'';
  openModal('modalStaffMsg');
}
async function sendStaffMsg(){
  const msg=document.getElementById('staffMsgContent').value;
  const uid=document.getElementById('staffMsgUserId').value;
  if(!msg.trim()){toast('Please type a message','warning');return;}
  const res=await docAction({action:'send_staff_note',target_user_id:uid,message:msg});
  if(res.success){toast('Instruction sent!');closeModal('modalStaffMsg');document.getElementById('staffMsgContent').value='';}
  else toast(res.message||'Error','danger');
}
</script>
