<!-- ═══════════════ MODULE 3: SAMPLE TRACKING SYSTEM ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-vial" style="color:var(--role-accent);margin-right:.6rem;"></i> Sample Tracking</h1>
    <p>Register, track, and manage lab samples through their full lifecycle</p>
  </div>
  <button class="adm-btn adm-btn-primary" onclick="openModal('addSampleModal')"><i class="fas fa-plus-circle"></i> Register Sample</button>
</div>

<!-- Sample Pipeline Overview -->
<div class="adm-summary-strip">
  <?php
  $smp_stats=['Collected'=>0,'Received'=>0,'Processing'=>0,'Stored'=>0,'Rejected'=>0,'Disposed'=>0];
  foreach($samples as $s) if(isset($smp_stats[$s['status']])) $smp_stats[$s['status']]++;
  $scolors=['Collected'=>'','Received'=>'','Processing'=>'','Stored'=>'green','Rejected'=>'red','Disposed'=>'orange'];
  foreach($smp_stats as $st=>$cnt):?>
  <div class="adm-mini-card"><div class="adm-mini-card-num <?=$scolors[$st]?>"><?=$cnt?></div><div class="adm-mini-card-label"><?=$st?></div></div>
  <?php endforeach;?>
</div>

<!-- Filters -->
<div class="filter-tabs" id="smpFilters">
  <span class="ftab active" onclick="filterSamples('all',this)">All</span>
  <?php foreach(array_keys($smp_stats) as $st):?><span class="ftab" onclick="filterSamples('<?=$st?>',this)"><?=$st?> (<?=$smp_stats[$st]?>)</span><?php endforeach;?>
</div>

<!-- Samples Table -->
<div class="adm-card">
  <div class="adm-card-body" style="padding:0;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="samplesTable">
        <thead><tr>
          <th>Sample ID</th><th>Barcode</th><th>Patient</th><th>Test</th><th>Type</th><th>Collected</th><th>Condition</th><th>Location</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if(empty($samples)):?>
          <tr><td colspan="10" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-vial" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>No samples registered</td></tr>
        <?php else: foreach($samples as $s):
          $cond_cls=['Good'=>'success','Haemolysed'=>'danger','Clotted'=>'danger','Insufficient'=>'danger','Contaminated'=>'danger','Lipemic'=>'warning'][$s['condition_on_receipt']??'Good']??'info';
          $st_cls=['Collected'=>'info','Received'=>'primary','Processing'=>'warning','Stored'=>'success','Rejected'=>'danger','Disposed'=>'danger'][$s['status']]??'info';
        ?>
          <tr class="<?=in_array($s['condition_on_receipt']??'',['Haemolysed','Clotted','Insufficient','Contaminated'])?'row-danger':''?>" data-status="<?=e($s['status'])?>">
            <td><span style="font-family:monospace;font-weight:700;color:var(--role-accent);"><?=e($s['sample_id'])?></span></td>
            <td>
              <span style="font-family:monospace;font-size:1.1rem;"><?=e($s['sample_code']??'—')?></span>
              <br><button class="adm-btn adm-btn-sm adm-btn-ghost" onclick="printBarcode('<?=e($s['sample_code']??$s['sample_id'])?>')" title="Print Barcode" style="padding:.2rem .5rem;"><i class="fas fa-barcode"></i></button>
            </td>
            <td><?=e($s['patient_name']??'—')?></td>
            <td><?=e($s['test_name']??'—')?></td>
            <td><?=e($s['sample_type']??'—')?></td>
            <td style="white-space:nowrap;font-size:1.2rem;"><?=$s['collection_date']?date('d M',strtotime($s['collection_date'])):'—'?> <?=$s['collection_time']?date('h:i A',strtotime($s['collection_time'])):''?></td>
            <td><span class="adm-badge adm-badge-<?=$cond_cls?>"><?=e($s['condition_on_receipt']??'Good')?></span></td>
            <td style="font-size:1.15rem;"><?=e($s['storage_location']??'—')?></td>
            <td>
              <span class="adm-badge adm-badge-<?=$st_cls?>"><?=e($s['status'])?></span>
              <!-- Status pipeline mini -->
              <div class="status-pipeline" style="margin-top:.4rem;">
                <?php $pipeline=['Collected','Received','Processing','Stored'];$ci=array_search($s['status'],$pipeline);foreach($pipeline as $pi=>$ps):?>
                <span class="pipeline-step <?=$pi<$ci?'completed':($pi===$ci?'active':'')?>" style="padding:.2rem .5rem;font-size:.9rem;"><?=$ps?></span>
                <?php if($pi<3):?><span class="pipeline-arrow" style="font-size:.7rem;"><i class="fas fa-chevron-right"></i></span><?php endif; endforeach;?>
              </div>
            </td>
            <td class="adm-table-actions">
              <?php if($s['status']==='Collected'):?>
                <button class="adm-btn adm-btn-sm adm-btn-primary" onclick="updateSampleStatus(<?=$s['id']?>,'Received')" title="Mark Received"><i class="fas fa-inbox"></i></button>
              <?php elseif($s['status']==='Received'):?>
                <button class="adm-btn adm-btn-sm adm-btn-warning" onclick="updateSampleStatus(<?=$s['id']?>,'Processing')" title="Start Processing"><i class="fas fa-cog"></i></button>
              <?php elseif($s['status']==='Processing'):?>
                <button class="adm-btn adm-btn-sm adm-btn-success" onclick="updateSampleStatus(<?=$s['id']?>,'Stored')" title="Store"><i class="fas fa-box"></i></button>
              <?php endif;?>
              <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick="updateSampleLocation(<?=$s['id']?>)" title="Update Location"><i class="fas fa-map-marker-alt"></i></button>
              <?php if(!in_array($s['status'],['Rejected','Disposed'])):?>
              <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="rejectSample(<?=$s['id']?>)" title="Reject"><i class="fas fa-times"></i></button>
              <?php endif;?>
            </td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Register Sample Modal -->
<div class="modal-bg" id="addSampleModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-vial"></i> Register New Sample</h3><button class="modal-close" onclick="closeModal('addSampleModal')">&times;</button></div>
    <div class="form-group"><label>Link to Order *</label>
      <select id="smp_order" class="form-control">
        <option value="">Select order...</option>
        <?php foreach($all_orders as $o):if(in_array($o['order_status'],['Accepted','Sample Collected','Processing'])):?>
        <option value="<?=$o['id']?>" data-patient="<?=$o['patient_id']??0?>"><?=e($o['order_id'])?> — <?=e($o['test_name']??'')?> — <?=e($o['patient_name']??'')?></option>
        <?php endif;endforeach;?>
      </select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Sample Type *</label><select id="smp_type" class="form-control"><option>Blood</option><option>Urine</option><option>Stool</option><option>Swab</option><option>CSF</option><option>Tissue</option><option>Sputum</option><option>Other</option></select></div>
      <div class="form-group"><label>Container Type</label><select id="smp_container" class="form-control"><option>EDTA</option><option>Plain</option><option>Heparin</option><option>Citrate</option><option>Fluoride</option><option>Sterile Cup</option><option>Swab Tube</option></select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Volume (mL)</label><input id="smp_volume" type="number" step="0.1" class="form-control" value="5"></div>
      <div class="form-group"><label>Condition on Receipt</label><select id="smp_condition" class="form-control"><option>Good</option><option>Haemolysed</option><option>Clotted</option><option>Lipemic</option><option>Insufficient</option><option>Contaminated</option></select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Storage Location</label><input id="smp_storage" class="form-control" placeholder="e.g. Rack A, Shelf 2, Position 5"></div>
      <div class="form-group"><label>Collection Time</label><input id="smp_time" type="time" class="form-control" value="<?=date('H:i')?>"></div>
    </div>
    <div class="form-group"><label>Notes</label><textarea id="smp_notes" class="form-control" rows="2"></textarea></div>
    <div class="adm-alert adm-alert-info"><i class="fas fa-info-circle"></i> A unique sample ID and barcode will be auto-generated.</div>
    <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="registerSample()"><i class="fas fa-save"></i> Register Sample</button>
  </div>
</div>

<script>
function filterSamples(st,el){
  el.parentNode.querySelectorAll('.ftab').forEach(f=>f.classList.remove('active'));el.classList.add('active');
  document.querySelectorAll('#samplesTable tbody tr').forEach(r=>{r.style.display=(st==='all'||r.dataset.status===st)?'':'none';});
}
async function registerSample(){
  if(!document.getElementById('smp_order').value){showToast('Select an order','error');return;}
  const opt=document.getElementById('smp_order').selectedOptions[0];
  const r=await labAction({action:'log_sample',order_id:document.getElementById('smp_order').value,patient_id:opt?.dataset.patient||0,sample_type:document.getElementById('smp_type').value,container_type:document.getElementById('smp_container').value,volume:document.getElementById('smp_volume').value,condition:document.getElementById('smp_condition').value,storage:document.getElementById('smp_storage').value,notes:document.getElementById('smp_notes').value});
  showToast(r.message,r.success?'success':'error');
  if(r.success){showToast('Sample ID: '+r.sample_id,'success');closeModal('addSampleModal');setTimeout(()=>location.reload(),1200);}
}
async function updateSampleStatus(id,status){
  if(!confirmAction('Update sample to '+status+'?'))return;
  const r=await labAction({action:'update_sample_status',sample_id:id,status:status});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}
function updateSampleLocation(id){
  const loc=prompt('Enter new storage location (e.g. Rack B, Shelf 3):');if(!loc)return;
  labAction({action:'update_sample_location',sample_id:id,location:loc}).then(r=>{showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);});
}
async function rejectSample(id){
  const reason=prompt('Reason for rejecting this sample:');if(!reason)return;
  const r=await labAction({action:'reject_sample',sample_id:id,reason:reason});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}
function printBarcode(code){
  const w=window.open('','Barcode','width=400,height=250');
  w.document.write('<html><head><title>Sample Barcode</title><style>body{text-align:center;padding:2rem;font-family:monospace;}#bc{font-family:"Libre Barcode 39",monospace;font-size:4rem;letter-spacing:2px;}.label{font-size:16px;font-weight:bold;margin-top:8px;}</style><link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet"></head><body>');
  w.document.write('<div id="bc">*'+code+'*</div><div class="label">'+code+'</div><p style="font-size:12px;color:#888;">RMU Medical — Lab Sample</p><button onclick="window.print()" style="margin-top:1rem;padding:.5rem 1.5rem;cursor:pointer;">Print</button>');
  w.document.write('</body></html>');w.document.close();
}
</script>
