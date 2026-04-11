<?php
// MODULE 7: EMERGENCY CONTACTS
$emerg_contacts=[];
$q=mysqli_query($conn,"SELECT * FROM emergency_contacts WHERE patient_id=$pat_pk ORDER BY is_primary DESC, contact_name ASC");
if($q) while($r=mysqli_fetch_assoc($q)) $emerg_contacts[]=$r;
?>
<div id="sec-emergency" class="dash-section">
  <div class="adm-card">
    <div class="adm-card-header">
      <h3><i class="fas fa-phone-alt" style="color:#e74c3c;"></i> Emergency Contacts</h3>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('addEcForm').style.display='block';"><span class="btn-text"><i class="fas fa-plus"></i> Add Contact</span></button>
    </div>
    <div style="padding:1.5rem;">
      <!-- Add form (hidden) -->
      <div id="addEcForm" style="display:none;background:var(--surface-2);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
        <h4 style="margin-bottom:1rem;"><i class="fas fa-user-plus" style="color:var(--primary);"></i> New Emergency Contact</h4>
        <form onsubmit="addEmergencyContact(event)">
          <div class="form-row">
            <div class="form-group"><label>Full Name *</label><input type="text" name="contact_name" class="form-control" required></div>
            <div class="form-group"><label>Relationship *</label>
              <select name="relationship" class="form-control" required>
                <option value="">Select...</option>
                <option>Parent</option><option>Spouse</option><option>Sibling</option><option>Child</option><option>Guardian</option><option>Friend</option><option>Other</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Phone Number *</label><input type="tel" name="phone" class="form-control" required pattern="[0-9+\-\s]{7,20}"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
          </div>
          <div class="form-group"><label>Address</label><input type="text" name="address" class="form-control"></div>
          <div class="form-group" style="display:flex;align-items:center;gap:.8rem;">
            <label style="margin:0;cursor:pointer;"><input type="checkbox" name="is_primary" value="1"> Mark as Primary Contact</label>
          </div>
          <div style="display:flex;gap:.8rem;">
            <button type="submit" class="btn btn-primary"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button>
            <button type="button" class="btn btn-ghost btn" onclick="this.closest('#addEcForm').style.display='none'"><span class="btn-text">Cancel</span></button>
          </div>
        </form>
      </div>

      <!-- Contact Cards -->
      <div id="ecList">
        <?php if(empty($emerg_contacts)):?>
        <div style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-address-book" style="font-size:2.5rem;opacity:.4;display:block;margin-bottom:1rem;"></i><p>No emergency contacts saved yet</p></div>
        <?php else: foreach($emerg_contacts as $ec):?>
        <div class="ec-card" data-id="<?=$ec['id']?>" style="display:flex;align-items:center;gap:1.2rem;padding:1.2rem;border-bottom:1px solid var(--border);">
          <div style="width:50px;height:50px;border-radius:50%;background:<?=$ec['is_primary']?'var(--role-accent)':'var(--primary-light)'?>;color:<?=$ec['is_primary']?'#fff':'var(--primary)'?>;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
            <i class="fas fa-user"></i>
          </div>
          <div style="flex:1;">
            <div style="font-weight:700;font-size:1.4rem;">
              <?=htmlspecialchars($ec['contact_name'])?>
              <?php if($ec['is_primary']):?><span class="adm-badge adm-badge-teal" style="margin-left:.5rem;">Primary</span><?php endif;?>
            </div>
            <div style="font-size:1.15rem;color:var(--text-muted);display:flex;gap:1rem;flex-wrap:wrap;margin-top:.3rem;">
              <span><i class="fas fa-heart"></i> <?=htmlspecialchars($ec['relationship'])?></span>
              <span><i class="fas fa-phone"></i> <?=htmlspecialchars($ec['phone'])?></span>
              <?php if($ec['email']):?><span><i class="fas fa-envelope"></i> <?=htmlspecialchars($ec['email'])?></span><?php endif;?>
            </div>
            <?php if($ec['address']):?><div style="font-size:1.1rem;color:var(--text-muted);margin-top:.2rem;"><i class="fas fa-map-marker-alt"></i> <?=htmlspecialchars($ec['address'])?></div><?php endif;?>
          </div>
          <div style="display:flex;gap:.4rem;">
            <button class="btn btn-primary btn btn-sm" onclick='editEc(<?=json_encode($ec)?>)' title="Edit"><span class="btn-text"><i class="fas fa-edit"></i></span></button>
            <button class="btn btn-danger btn-sm" onclick="deleteEc(<?=$ec['id']?>)" title="Delete"><span class="btn-text"><i class="fas fa-trash"></i></span></button>
          </div>
        </div>
        <?php endforeach; endif;?>
      </div>
    </div>
  </div>
</div>

<!-- Edit EC Modal -->
<div class="modal-bg" id="modalEditEc">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-edit" style="color:var(--primary);margin-right:.5rem;"></i>Edit Contact</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalEditEc')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="updateEc(event)">
      <input type="hidden" id="editEcId" name="id">
      <div class="form-row">
        <div class="form-group"><label>Full Name *</label><input type="text" id="editEcName" name="contact_name" class="form-control" required></div>
        <div class="form-group"><label>Relationship *</label>
          <select id="editEcRel" name="relationship" class="form-control" required>
            <option>Parent</option><option>Spouse</option><option>Sibling</option><option>Child</option><option>Guardian</option><option>Friend</option><option>Other</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Phone *</label><input type="tel" id="editEcPhone" name="phone" class="form-control" required></div>
        <div class="form-group"><label>Email</label><input type="email" id="editEcEmail" name="email" class="form-control"></div>
      </div>
      <div class="form-group"><label>Address</label><input type="text" id="editEcAddr" name="address" class="form-control"></div>
      <div class="form-group"><label><input type="checkbox" id="editEcPrimary" name="is_primary" value="1"> Mark as Primary</label></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-save"></i> Update</span></button>
    </form>
  </div>
</div>

<script>
async function addEmergencyContact(e){
  e.preventDefault();const fd=new FormData(e.target);
  const data={action:'add_emergency_contact'};
  fd.forEach((v,k)=>data[k]=v);
  if(!fd.has('is_primary')) data.is_primary=0;
  const r=await patAction(data);
  if(r.success){toast('Contact added!');location.reload();}else toast(r.message||'Error','danger');
}
function editEc(ec){
  document.getElementById('editEcId').value=ec.id;
  document.getElementById('editEcName').value=ec.contact_name;
  document.getElementById('editEcRel').value=ec.relationship;
  document.getElementById('editEcPhone').value=ec.phone;
  document.getElementById('editEcEmail').value=ec.email||'';
  document.getElementById('editEcAddr').value=ec.address||'';
  document.getElementById('editEcPrimary').checked=!!ec.is_primary;
  openModal('modalEditEc');
}
async function updateEc(e){
  e.preventDefault();const fd=new FormData(e.target);
  const data={action:'update_emergency_contact'};
  fd.forEach((v,k)=>data[k]=v);
  if(!fd.has('is_primary')) data.is_primary=0;
  const r=await patAction(data);
  if(r.success){toast('Contact updated!');closeModal('modalEditEc');location.reload();}else toast(r.message||'Error','danger');
}
async function deleteEc(id){
  if(!confirm('Delete this emergency contact?'))return;
  const r=await patAction({action:'delete_emergency_contact',id});
  if(r.success){toast('Deleted');document.querySelector(`.ec-card[data-id="${id}"]`)?.remove();}else toast(r.message||'Error','danger');
}
</script>
