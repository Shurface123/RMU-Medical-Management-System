<?php // TAB: PRESCRIPTIONS ?>
<div id="sec-prescriptions" class="dash-section">

<style>
.adm-tab-group { display:flex; gap:.8rem; flex-wrap:wrap; margin-bottom:1.8rem; padding-bottom:1rem; border-bottom:1px solid var(--border); }
.ftab-v2 { 
  display:inline-flex;align-items:center;gap:.6rem;padding:.55rem 1.4rem;border-radius:20px;
  font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);
  background:var(--surface);color:var(--text-secondary);transition:all 0.3s ease;
}
.ftab-v2:hover { background:var(--primary-light);color:var(--primary);border-color:var(--primary);transform:translateY(-1px); }
.ftab-v2.active { background:var(--primary);color:#fff;border-color:var(--primary);box-shadow:0 4px 12px rgba(47,128,237,.25); }
.premium-modal { border-radius:18px; border:1px solid rgba(255,255,255,0.1); }
</style>

  <div class="sec-header">
    <h2><i class="fas fa-prescription-bottle-medical" style="color:var(--primary);"></i> Prescriptions</h2>
    <div style="display:flex;gap:.7rem;flex-wrap:wrap;">
      <button onclick="openModal('modalNewRx')" class="btn btn-primary" style="border-radius:12px;padding:.8rem 1.4rem;"><span class="btn-text"><i class="fas fa-plus"></i> New Prescription</span></button>
    </div>
  </div>

  <div class="adm-tab-group">
    <button class="ftab-v2 active" onclick="filterRx('all',this)"><i class="fas fa-list"></i> All</button>
    <button class="ftab-v2" onclick="filterRx('Pending',this)"><i class="fas fa-clock" style="color:var(--warning);"></i> Pending</button>
    <button class="ftab-v2" onclick="filterRx('Dispensed',this)"><i class="fas fa-check-circle" style="color:var(--success);"></i> Dispensed</button>
    <button class="ftab-v2" onclick="filterRx('Cancelled',this)"><i class="fas fa-xmark-circle" style="color:var(--danger);"></i> Cancelled</button>
  </div>

  <div class="adm-card shadow-sm" style="overflow:hidden;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="rxTable">
        <thead><tr style="background:linear-gradient(90deg, var(--surface-2), var(--surface));"><th>Rx ID</th><th>Patient</th><th>Medicine</th><th>Dosage / Duration</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
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
  <div class="modal-box wide premium-modal">
    <div class="modal-header">
      <h3><i class="fas fa-prescription-bottle-medical" style="color:#fff;"></i> Issue New Prescription</h3>
      <button class="modal-close" onclick="closeModal('modalNewRx')">&times;</button>
    </div>
    <form id="formNewRx" onsubmit="submitRx(event)" style="padding:1rem;">
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
  <div class="modal-box premium-modal">
    <div class="modal-header">
      <h3><i class="fas fa-prescription-bottle-medical" style="color:#fff;"></i> Prescription Details</h3>
      <button class="modal-close" onclick="closeModal('modalViewRx')">&times;</button>
    </div>
    <div id="rxDetail" style="padding:1rem;"></div>
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
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;font-size:1.3rem;background:var(--surface-2);padding:1.5rem;border-radius:12px;">
      <div><strong style="color:var(--text-secondary);">Patient</strong><br><span style="font-weight:600;color:var(--text-primary);">${r.patient_name}</span></div>
      <div><strong style="color:var(--text-secondary);">Medicine</strong><br><span style="font-weight:700;color:var(--primary);">${r.medication_name}</span></div>
      <div><strong style="color:var(--text-secondary);">Dosage</strong><br><span style="font-weight:600;">${r.dosage}</span></div>
      <div><strong style="color:var(--text-secondary);">Frequency</strong><br><span style="font-weight:600;">${r.frequency}</span></div>
      <div><strong style="color:var(--text-secondary);">Duration</strong><br><span style="font-weight:600;">${r.duration}</span></div>
      <div><strong style="color:var(--text-secondary);">Quantity</strong><br><span style="font-weight:600;">${r.quantity}</span></div>
      <div style="grid-column:1 / span 2;"><strong style="color:var(--text-secondary);">Status</strong><br><span class="adm-badge adm-badge-primary" style="margin-top:.4rem;">${r.status}</span></div>
    </div>
    ${r.instructions?`
    <div class="rx-card-v2" style="padding:1.8rem;margin:0;border:1.5px solid var(--primary);border-radius:12px;box-shadow:0 4px 15px rgba(47,128,237,0.1);">
      <strong style="color:var(--primary);font-size:1.2rem;text-transform:uppercase;letter-spacing:0.04em;"><i class="fas fa-clipboard-list"></i> Instructions</strong>
      <p style="margin-top:.8rem;color:var(--text-secondary);font-size:1.3rem;">${r.instructions}</p>
    </div>`:''}`;
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

function filterRx(status, btn) {
    document.querySelectorAll('.adm-tab-group .ftab-v2').forEach(b=>b.classList.remove('active'));
    if(btn) btn.classList.add('active');
    
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#rxTable')) {
        const dt = $('#rxTable').DataTable();
        if(status === 'all') { dt.column(4).search('').draw(); }
        else { dt.column(4).search(status, true, false).draw(); }
    } else {
        filterByStatus(status, 'rxTable', 4);
    }
}

$(document).ready(function() {
    if($.fn.DataTable) {
        $('#rxTable').DataTable({
            pageLength: 10,
            language: { search: "", searchPlaceholder: "Quick search prescriptions..." }
        });
    }
});
</script>
