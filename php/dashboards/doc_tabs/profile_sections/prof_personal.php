<?php // SECTION B: Personal Information ?>
<div id="prof-personal" class="prof-section" style="display:none;">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-user"></i> Personal Information</h3></div>
    <div style="padding:2rem;">
      <form id="formPersonal" onsubmit="savePersonal(event)">
        <div class="form-row">
          <div class="form-group"><label>Full Name *</label><input type="text" name="name" class="form-control" value="<?=htmlspecialchars($prof['name']??'')?>" required></div>
          <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" class="form-control" value="<?=htmlspecialchars($prof['date_of_birth']??'')?>" onchange="calcAge(this)"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Age</label><input type="text" id="ageField" class="form-control" value="<?=$age?$age.' years':'—'?>" readonly style="background:var(--surface-2);"></div>
          <div class="form-group"><label>Gender</label>
            <select name="gender" class="form-control">
              <option value="">Select</option>
              <?php foreach(['Male','Female','Other'] as $g):?><option value="<?=$g?>" <?=($prof['gender']??'')===$g?'selected':''?>><?=$g?></option><?php endforeach;?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Nationality</label><input type="text" name="nationality" class="form-control" value="<?=htmlspecialchars($prof['nationality']??'')?>"></div>
          <div class="form-group"><label>Marital Status</label>
            <select name="marital_status" class="form-control">
              <option value="">Select</option>
              <?php foreach(['Single','Married','Divorced','Widowed'] as $m):?><option value="<?=$m?>" <?=($prof['marital_status']??'')===$m?'selected':''?>><?=$m?></option><?php endforeach;?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Religion (optional)</label><input type="text" name="religion" class="form-control" value="<?=htmlspecialchars($prof['religion']??'')?>"></div>
          <div class="form-group"><label>National ID / License No. *</label><input type="text" name="national_id" class="form-control" value="<?=htmlspecialchars($prof['national_id']??'')?>"></div>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0;">
        <h4 style="font-size:1.4rem;margin-bottom:1rem;"><i class="fas fa-phone" style="color:var(--role-accent);margin-right:.5rem;"></i>Contact Details</h4>
        <div class="form-row">
          <div class="form-group"><label>Primary Phone *</label><input type="tel" name="phone" class="form-control" value="<?=htmlspecialchars($prof['phone']??'')?>"></div>
          <div class="form-group"><label>Secondary Phone</label><input type="tel" name="secondary_phone" class="form-control" value="<?=htmlspecialchars($prof['secondary_phone']??'')?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Official Email *</label><input type="email" name="email" class="form-control" value="<?=htmlspecialchars($prof['email']??'')?>"></div>
          <div class="form-group"><label>Personal Email</label><input type="email" name="personal_email" class="form-control" value="<?=htmlspecialchars($prof['personal_email']??'')?>"></div>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0;">
        <h4 style="font-size:1.4rem;margin-bottom:1rem;"><i class="fas fa-map-marker-alt" style="color:var(--role-accent);margin-right:.5rem;"></i>Address</h4>
        <div class="form-group"><label>Street Address</label><input type="text" name="street_address" class="form-control" value="<?=htmlspecialchars($prof['street_address']??'')?>"></div>
        <div class="form-row">
          <div class="form-group"><label>City</label><input type="text" name="city" class="form-control" value="<?=htmlspecialchars($prof['city']??'')?>"></div>
          <div class="form-group"><label>Region/State</label><input type="text" name="region" class="form-control" value="<?=htmlspecialchars($prof['region']??'')?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Country</label><input type="text" name="country" class="form-control" value="<?=htmlspecialchars($prof['country']??'Ghana')?>"></div>
          <div class="form-group"><label>Postal Code</label><input type="text" name="postal_code" class="form-control" value="<?=htmlspecialchars($prof['postal_code']??'')?>"></div>
        </div>
        <div class="form-group"><label>Office/Room Location</label><input type="text" name="office_location" class="form-control" value="<?=htmlspecialchars($prof['office_location']??'')?>" placeholder="e.g. Room 204, Block B"></div>
        <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;margin-top:1rem;"><i class="fas fa-save"></i> Save Personal Information</button>
      </form>
    </div>
  </div>
</div>
<script>
function calcAge(inp){
  if(!inp.value)return;
  const bd=new Date(inp.value),today=new Date();
  let a=today.getFullYear()-bd.getFullYear();
  if(today.getMonth()<bd.getMonth()||(today.getMonth()===bd.getMonth()&&today.getDate()<bd.getDate()))a--;
  document.getElementById('ageField').value=a+' years';
}
async function savePersonal(e){
  e.preventDefault();
  const fd=new FormData(e.target),data={action:'update_personal_info'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await profAction(data);
  if(res.success)toast('Personal information saved!');
  else toast(res.message||'Error','danger');
}
</script>
