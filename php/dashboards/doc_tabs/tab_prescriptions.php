<?php // TAB: PRESCRIPTIONS ?>
<div id="sec-prescriptions" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-prescription-bottle-medical"></i> Prescriptions</h2>
    <button onclick="openModal('modalNewRx')" class="btn btn-primary"><span class="btn-text"><i class="fas fa-plus"></i> New Prescription</span></button>
  </div>

  <div class="filter-tabs">
    <button class="btn btn-primary ftab active" onclick="filterByStatus('all','rxTable',4)"><span class="btn-text">All</span></button>
    <button class="btn btn-warning btn-icon ftab" onclick="filterByStatus('Pending','rxTable',4)"><span class="btn-text">Pending</span></button>
    <button class="btn btn-primary ftab" onclick="filterByStatus('Dispensed','rxTable',4)"><span class="btn-text">Dispensed</span></button>
    <button class="btn btn-ghost ftab" onclick="filterByStatus('Cancelled','rxTable',4)"><span class="btn-text">Cancelled</span></button>
  </div>

  <div style="margin-bottom:1.2rem;">
    <div class="adm-search-wrap"><i class="fas fa-search"></i>
      <input type="text" class="adm-search-input" id="rxSearch" placeholder="Search patient or medicine…" oninput="filterTable('rxSearch','rxTable')">
    </div>
  </div>

  <div class="adm-card">
    <div class="adm-table-wrap">
      <table class="adm-table" id="rxTable">
        <thead><tr><th>Rx ID</th><th>Patient</th><th>Medicine</th><th>Dosage / Duration</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($prescriptions)):?>
          <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);">No prescriptions found.</td></tr>
        <?php else: foreach($prescriptions as $rx):
          $sc=match($rx['status']??''){
            'Dispensed'=>'success','Cancelled'=>'danger',default=>'warning'};
          $rxj=json_encode(['id'=>$rx['id'],'patient_name'=>$rx['patient_name'],'medication_name'=>$rx['medication_name'],
            'dosage'=>$rx['dosage'],'frequency'=>$rx['frequency'],'duration'=>$rx['duration'],
            'instructions'=>$rx['instructions'],'status'=>$rx['status'],'quantity'=>$rx['quantity']],JSON_HEX_QUOT|JSON_HEX_APOS);
        ?>
        <tr data-status="<?=$rx['status']?>">
          <td><code><?=htmlspecialchars($rx['prescription_id']??'#'.$rx['id'])?></code></td>
          <td><?=htmlspecialchars($rx['patient_name'])?><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($rx['p_ref'])?></span></td>
          <td><strong><?=htmlspecialchars($rx['medication_name'])?></strong></td>
          <td><?=htmlspecialchars($rx['dosage'])?> &middot; <?=htmlspecialchars($rx['frequency'])?><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($rx['duration'])?></span></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$rx['status']?></span></td>
          <td><?=date('d M Y',strtotime($rx['prescription_date']))?></td>
          <td>
            <div class="action-btns">
              <button onclick='viewRx(<?=$rxj?>)' class="btn btn-ghost btn-sm"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
              <?php if($rx['status']==='Pending'):?>
              <button onclick="cancelRx(<?=$rx['id']?>)" class="btn btn-danger btn-sm"><span class="btn-text"><i class="fas fa-xmark"></i></span></button>
              <?php endif;?>
            </div>
          </td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: New Prescription -->
<div class="modal-bg" id="modalNewRx">
  <div class="modal-box wide">
    <div class="modal-header">
      <h3><i class="fas fa-prescription-bottle-medical" style="color:var(--role-accent);"></i> Issue New Prescription</h3>
      <button class="btn btn-primary modal-close" onclick="closeModal('modalNewRx')"><span class="btn-text">&times;</span></button>
    </div>
    <form id="formNewRx" onsubmit="submitRx(event)">
      <div class="form-row">
        <div class="form-group"><label>Select Patient</label>
          <select class="form-control" name="patient_id" required>
            <option value="">-- Choose Patient --</option>
            <?php foreach($patients as $pt):?>
            <option value="<?=$pt['id']?>"><?=htmlspecialchars($pt['name'])?> (<?=htmlspecialchars($pt['p_ref'])?>)</option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="form-group"><label>Medicine</label>
          <select class="form-control" name="medicine_name" required onchange="checkStock(this)">
            <option value="">-- Select Medicine --</option>
            <?php foreach($medicines as $med):?>
            <option value="<?=htmlspecialchars($med['medicine_name'])?>" data-status="<?=htmlspecialchars($med['stock_status'])?>" data-qty="<?=$med['stock_quantity']?>">
              <?=htmlspecialchars($med['medicine_name'])?> — <?=$med['stock_status']?> (<?=$med['stock_quantity']?> left)
            </option>
            <?php endforeach;?>
          </select>
          <div id="stockAlert" style="margin-top:.5rem;font-size:1.2rem;"></div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Dosage</label><input type="text" name="dosage" class="form-control" placeholder="e.g. 500mg" required></div>
        <div class="form-group"><label>Frequency</label>
          <select name="frequency" class="form-control" required>
            <option value="">-- Select --</option>
            <option>Once daily</option><option>Twice daily</option><option>Three times daily</option>
            <option>Four times daily</option><option>Every 6 hours</option><option>Every 8 hours</option>
            <option>As needed (PRN)</option><option>At bedtime</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Duration</label><input type="text" name="duration" class="form-control" placeholder="e.g. 7 days" required></div>
        <div class="form-group"><label>Quantity</label><input type="number" name="quantity" class="form-control" min="1" value="1" required></div>
      </div>
      <div class="form-group"><label>Instructions / Notes</label><textarea name="instructions" class="form-control" rows="2" placeholder="Take with food, avoid alcohol, etc."></textarea></div>
      <div class="form-group"><label>Prescription Date</label><input type="date" name="prescription_date" class="form-control" value="<?=$today?>" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-prescription-bottle-medical"></i> Issue Prescription</span></button>
    </form>
  </div>
</div>

<!-- Modal: View Rx -->
<div class="modal-bg" id="modalViewRx">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-prescription-bottle-medical" style="color:var(--role-accent);"></i> Prescription Details</h3>
      <button class="btn btn-primary modal-close" onclick="closeModal('modalViewRx')"><span class="btn-text">&times;</span></button>
    </div>
    <div id="rxDetail" style="font-size:1.3rem;line-height:2.2;"></div>
  </div>
</div>

<script>
function checkStock(sel){
  const opt=sel.options[sel.selectedIndex];
  const status=opt.dataset.status, qty=opt.dataset.qty, el=document.getElementById('stockAlert');
  if(!status){el.innerHTML='';return;}
  const map={
    'Out of Stock':'<span style="color:var(--danger);font-weight:600;"><i class="fas fa-circle-xmark"></i> Out of Stock — Cannot Prescribe</span>',
    'Low Stock':'<span style="color:var(--warning);font-weight:600;"><i class="fas fa-triangle-exclamation"></i> Low Stock ('+qty+' remaining)</span>',
    'Expiring Soon':'<span style="color:var(--warning);font-weight:600;"><i class="fas fa-clock"></i> Expiring Soon — Use With Caution</span>',
    'In Stock':'<span style="color:var(--success);font-weight:600;"><i class="fas fa-circle-check"></i> In Stock ('+qty+' available)</span>'
  };
  el.innerHTML=map[status]||'';
}
function viewRx(r){
  document.getElementById('rxDetail').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
      <div><strong>Patient</strong><br>${r.patient_name}</div>
      <div><strong>Medicine</strong><br>${r.medication_name}</div>
      <div><strong>Dosage</strong><br>${r.dosage}</div>
      <div><strong>Frequency</strong><br>${r.frequency}</div>
      <div><strong>Duration</strong><br>${r.duration}</div>
      <div><strong>Quantity</strong><br>${r.quantity}</div>
      <div><strong>Status</strong><br><span class="adm-badge adm-badge-primary">${r.status}</span></div>
    </div>
    ${r.instructions?`<div style="margin-top:1rem;"><strong>Instructions</strong><p style="color:var(--text-secondary);margin-top:.3rem;">${r.instructions}</p></div>`:''}`;
  openModal('modalViewRx');
}
async function submitRx(e){
  e.preventDefault();
  const fd=new FormData(e.target);
  const data={action:'create_prescription'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await docAction(data);
  if(res.success){toast('Prescription issued successfully!');closeModal('modalNewRx');setTimeout(()=>location.reload(),1200);}
  else toast(res.message||'Error','danger');
}
async function cancelRx(id){
  if(!confirm('Cancel this prescription?')) return;
  const res=await docAction({action:'cancel_prescription',id});
  if(res.success){toast('Prescription cancelled');setTimeout(()=>location.reload(),1000);}
  else toast(res.message||'Error','danger');
}
</script>
