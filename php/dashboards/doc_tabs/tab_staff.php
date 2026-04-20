<?php // TAB: STAFF DIRECTORY ?>
<div id="sec-staff" class="dash-section">

<style>
.adm-tab-group { display:flex; gap:.8rem; flex-wrap:wrap; margin-bottom:1.8rem; padding-bottom:1rem; border-bottom:1px solid var(--border); }
.ftab-v2 { 
  display:inline-flex;align-items:center;gap:.6rem;padding:.55rem 1.4rem;border-radius:20px;
  font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);
  background:var(--surface);color:var(--text-secondary);transition:all 0.3s ease;
}
.ftab-v2:hover { background:var(--primary-light);color:var(--primary);border-color:var(--primary);transform:translateY(-1px); }
.ftab-v2.active { background:var(--primary);color:#fff;border-color:var(--primary);box-shadow:0 4px 12px rgba(47,128,237,.25); }

.staff-card-v2 { background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,.04); overflow:hidden; padding:2rem; position:relative; display:flex; flex-direction:column; }
.staff-card-v2:hover { border-color:var(--primary); }
.premium-modal { border-radius:18px; border:1px solid rgba(255,255,255,0.1); }
</style>

  <div class="sec-header">
    <h2><i class="fas fa-address-book" style="color:var(--primary);"></i> Global Staff Directory</h2>
  </div>

  <div class="adm-tab-group">
    <button class="ftab-v2 active" onclick="filterStaff('all',this)"><i class="fas fa-users"></i> All Staff</button>
    <?php foreach(['Doctor'=>'fa-user-md','Nurse'=>'fa-user-nurse','Lab Technician'=>'fa-flask','Pharmacist'=>'fa-pills','Admin'=>'fa-user-shield'] as $sr=>$io):?>
    <button class="ftab-v2" onclick="filterStaff('<?=$sr?>',this)"><i class="fas <?=$io?>"></i> <?=$sr?></button>
    <?php endforeach;?>
  </div>

  <div style="margin-bottom:2rem;">
    <div class="adm-search-wrap" style="max-width:450px;"><i class="fas fa-search" style="color:var(--primary);"></i>
      <input type="text" class="adm-search-input" id="staffSearch" placeholder="Search by name, role, or department…" oninput="filterTable('staffSearch','staffTable')">
    </div>
  </div>

  <div class="cards-grid" id="staffTable">
    <?php if(empty($staff)):?>
      <div class="staff-card-v2" style="grid-column:1/-1;text-align:center;padding:4rem;">
        <i class="fas fa-users-slash" style="font-size:3.5rem;opacity:.2;margin-bottom:1rem;display:block;"></i>
        <p style="color:var(--text-muted);font-size:1.3rem;">No staff records found. Add staff to the system to populate this directory.</p>
      </div>
    <?php else: foreach($staff as $s):
      $role=$s['role']??$s['role']??'Staff';
      $icon=match($role){'Doctor'=>'fa-user-md','Nurse'=>'fa-user-nurse','Lab Technician'=>'fa-flask','Pharmacist'=>'fa-pills',default=>'fa-user'};
      $sc=match($s['status']??'Active'){'Active'=>'success','On Leave'=>'warning','Inactive'=>'info',default=>'danger'};
      $sj=json_encode(['name'=>$s['full_name'],'role'=>$role,'dept'=>$s['department']??'','phone'=>$s['phone']??'','email'=>$s['email']??'','status'=>$s['status']??'Active','staff_id'=>$s['staff_id']??''],JSON_HEX_QUOT|JSON_HEX_APOS);
    ?>
    <div class="staff-card-v2 info-card" data-staffrole="<?=$role?>">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:1rem;">
          <div style="width:50px;height:50px;border-radius:12px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0;">
            <i class="fas <?=$icon?>"></i>
          </div>
          <div>
            <div style="font-weight:800;font-size:1.4rem;color:var(--text-primary);"><?=htmlspecialchars($s['full_name'])?></div>
            <div style="font-size:1.15rem;color:var(--text-secondary);font-weight:600;"><?=htmlspecialchars($role)?></div>
          </div>
        </div>
        <span class="adm-badge adm-badge-<?=$sc?>" style="font-size:1.1rem;"><?=$s['status']??'Active'?></span>
      </div>
      
      <div style="flex-grow:1;margin-bottom:2rem;padding-left:0.5rem;border-left:2px solid var(--border);">
        <?php if($s['department']??''):?><div style="font-size:1.2rem;color:var(--text-secondary);margin-bottom:.8rem;"><i class="fas fa-building" style="color:var(--text-muted);margin-right:.8rem;width:15px;text-align:center;"></i><?=htmlspecialchars($s['department']??'')?></div><?php endif;?>
        <?php if($s['phone']??''):?><div style="font-size:1.2rem;color:var(--text-secondary);margin-bottom:.8rem;"><i class="fas fa-phone" style="color:var(--primary);margin-right:.8rem;width:15px;text-align:center;"></i><?=htmlspecialchars($s['phone']??'')?></div><?php endif;?>
        <?php if($s['email']??''):?><div style="font-size:1.2rem;color:var(--text-secondary;margin-bottom:.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><i class="fas fa-envelope" style="color:var(--primary);margin-right:.8rem;width:15px;text-align:center;"></i><?=htmlspecialchars($s['email']??'')?></div><?php endif;?>
      </div>
      
      <div style="display:flex;gap:.8rem;">
        <?php if(in_array($role,['Nurse','Lab Technician'])):?>
        <button onclick='openStaffMsg(<?=$sj?>)' class="btn btn-primary btn-sm" style="flex:1;justify-content:center;"><span class="btn-text"><i class="fas fa-paper-plane"></i> Instruct</span></button>
        <?php endif;?>
        <button onclick='viewStaff(<?=$sj?>)' class="btn btn-outline-primary btn-sm" style="flex:1;justify-content:center;"><span class="btn-text"><i class="fas fa-eye"></i> Details</span></button>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>
</div>

<!-- Modal: View Staff -->
<div class="modal-bg" id="modalViewStaff">
  <div class="modal-box premium-modal">
    <div class="modal-header">
      <h3><i class="fas fa-address-card" style="color:#fff;"></i> Staff Profile</h3>
      <button class="modal-close" onclick="closeModal('modalViewStaff')">&times;</button>
    </div>
    <div id="staffDetail" style="padding:1rem;"></div>
  </div>
</div>

<!-- Modal: Send Message/Note to Staff -->
<div class="modal-bg" id="modalStaffMsg">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-paper-plane" style="color:var(--role-accent);"></i> Send Instruction</h3>
      <button class="btn btn-primary modal-close" onclick="closeModal('modalStaffMsg')"><span class="btn-text">&times;</span></button>
    </div>
    <p id="staffMsgTarget" style="font-weight:600;font-size:1.4rem;margin-bottom:1.2rem;"></p>
    <input type="hidden" id="staffMsgUserId">
    <div class="form-group"><label>Message / Instruction</label>
      <textarea id="staffMsgContent" class="form-control" rows="4" placeholder="e.g. Please administer 500mg Paracetamol to Patient P001 at 2PM…"></textarea>
    </div>
    <button onclick="sendStaffMsg()" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-paper-plane"></i> Send Instruction</span></button>
  </div>
</div>

<script>
let currentStaffUserId=null;
function filterStaff(role,btn){
  document.querySelectorAll('#sec-staff .ftab-v2').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  document.querySelectorAll('.info-card[data-staffrole]').forEach(c=>{
    c.style.display=(role==='all'||c.dataset.staffrole===role)?'':'none';
  });
}
function viewStaff(s){
  document.getElementById('staffDetail').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;font-size:1.3rem;background:var(--surface-2);padding:1.5rem;border-radius:12px;">
      <div><strong style="color:var(--text-secondary);">Name</strong><br><span style="font-weight:600;color:var(--text-primary);">${s.name}</span></div>
      <div><strong style="color:var(--text-secondary);">Role</strong><br><span style="font-weight:700;color:var(--primary);">${s.role}</span></div>
      <div><strong style="color:var(--text-secondary);">Department</strong><br><span style="font-weight:600;">${s.dept||'—'}</span></div>
      <div><strong style="color:var(--text-secondary);">Status</strong><br><span class="adm-badge adm-badge-success" style="margin-top:.4rem;">${s.status}</span></div>
      ${s.phone?`<div><strong style="color:var(--text-secondary);">Phone</strong><br><span>${s.phone}</span></div>`:''}
      ${s.email?`<div><strong style="color:var(--text-secondary);">Email</strong><br><span>${s.email}</span></div>`:''}
      ${s.staff_id?`<div style="grid-column:1 / span 2;"><strong style="color:var(--text-secondary);">Staff ID</strong><br><span style="font-family:monospace;color:var(--primary);">${s.staff_id}</span></div>`:''}
    </div>
    <div style="display:flex;justify-content:flex-end;">
      <button onclick="closeModal('modalViewStaff')" class="btn btn-primary" style="border-radius:12px;padding:.8rem 1.8rem;"><span class="btn-text">Close</span></button>
    </div>
  `;
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
