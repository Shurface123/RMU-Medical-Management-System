<!-- ═══════════════ MODULE 5: REFERENCE RANGE MANAGEMENT ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-ruler-combined" style="color:var(--role-accent);margin-right:.6rem;"></i> Reference Ranges</h1>
    <p>Manage reference ranges by test parameter, gender, and age group</p>
  </div>
  <button class="adm-btn adm-btn-primary" onclick="openModal('addRefRangeModal')"><i class="fas fa-plus-circle"></i> Add Range</button>
</div>

<!-- Search & Filter -->
<div class="adm-search-form">
  <div class="adm-search-wrap">
    <i class="fas fa-search"></i>
    <input class="adm-search-input" id="refRangeSearch" placeholder="Search by test, parameter..." onkeyup="filterRefRanges(this.value)">
  </div>
  <select class="form-control" style="max-width:200px;" id="refTestFilter" onchange="filterRefRanges(document.getElementById('refRangeSearch').value)">
    <option value="">All Tests</option>
    <?php $seen_tests=[]; foreach($ref_ranges as $rr): if(!in_array($rr['test_name'],$seen_tests)):$seen_tests[]=$rr['test_name'];?>
    <option value="<?=e($rr['test_name'])?>"><?=e($rr['test_name'])?></option>
    <?php endif; endforeach;?>
  </select>
</div>

<div class="adm-card">
  <div class="adm-card-body" style="padding:0;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="refRangeTable">
        <thead><tr>
          <th>Test</th><th>Parameter</th><th>Gender</th><th>Age Group</th><th>Unit</th>
          <th>Normal Min</th><th>Normal Max</th><th>Critical Low</th><th>Critical High</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if(empty($ref_ranges)):?>
          <tr><td colspan="10" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-database" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>No reference ranges configured</td></tr>
        <?php else: foreach($ref_ranges as $rr):?>
          <tr data-test="<?=e($rr['test_name']??'')?>">
            <td><strong><?=e($rr['test_name']??'—')?></strong></td>
            <td><?=e($rr['parameter_name'])?></td>
            <td><?=e($rr['gender']??'All')?></td>
            <td><?=e($rr['age_group']??'All')?></td>
            <td><span class="adm-badge adm-badge-info"><?=e($rr['unit']??'—')?></span></td>
            <td style="color:var(--success);font-weight:600;"><?=$rr['normal_min']?></td>
            <td style="color:var(--success);font-weight:600;"><?=$rr['normal_max']?></td>
            <td style="color:var(--danger);font-weight:700;"><?=$rr['critical_low']??'—'?></td>
            <td style="color:var(--danger);font-weight:700;"><?=$rr['critical_high']??'—'?></td>
            <td class="adm-table-actions">
              <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick='editRefRange(<?=json_encode($rr)?>)' title="Edit"><i class="fas fa-edit"></i></button>
              <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="deleteRefRange(<?=$rr['id']?>)" title="Delete"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Reference Range Preview Tool -->
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-eye"></i> Reference Range Preview</h3></div>
  <div class="adm-card-body">
    <p style="color:var(--text-muted);margin-bottom:1.5rem;">Enter a value to see how it would be flagged against current reference ranges.</p>
    <div class="form-row">
      <div class="form-group">
        <label>Test</label>
        <select id="prev_test" class="form-control" onchange="loadPreviewParams()">
          <option value="">Select test...</option>
          <?php foreach($test_catalog as $tc):?><option value="<?=$tc['id']?>"><?=e($tc['test_name'])?></option><?php endforeach;?>
        </select>
      </div>
      <div class="form-group">
        <label>Parameter</label>
        <select id="prev_param" class="form-control"><option value="">Select parameter...</option></select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Gender</label><select id="prev_gender" class="form-control"><option>All</option><option>Male</option><option>Female</option></select></div>
      <div class="form-group"><label>Value</label><input id="prev_value" type="number" step="0.0001" class="form-control" placeholder="Enter value"></div>
    </div>
    <button class="adm-btn adm-btn-primary" onclick="previewRefRange()"><i class="fas fa-search"></i> Check Flag</button>
    <div id="previewResult" style="margin-top:1.5rem;"></div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-bg" id="addRefRangeModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3 id="refRangeModalTitle"><i class="fas fa-ruler-combined"></i> Add Reference Range</h3><button class="modal-close" onclick="closeModal('addRefRangeModal')">&times;</button></div>
    <input type="hidden" id="rr_id">
    <div class="form-row">
      <div class="form-group"><label>Test *</label>
        <select id="rr_test" class="form-control">
          <option value="">Select test...</option>
          <?php foreach($test_catalog as $tc):?><option value="<?=$tc['id']?>"><?=e($tc['test_name'])?></option><?php endforeach;?>
        </select>
      </div>
      <div class="form-group"><label>Parameter Name *</label><input id="rr_param" class="form-control" placeholder="e.g. Hemoglobin, WBC"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Gender</label><select id="rr_gender" class="form-control"><option>All</option><option>Male</option><option>Female</option></select></div>
      <div class="form-group"><label>Age Group</label><select id="rr_age" class="form-control"><option>All</option><option>Neonatal</option><option>Infant</option><option>Child</option><option>Adolescent</option><option>Adult</option><option>Elderly</option></select></div>
    </div>
    <div class="form-group"><label>Unit</label><input id="rr_unit" class="form-control" placeholder="e.g. g/dL, mmol/L"></div>
    <div class="form-row">
      <div class="form-group"><label>Normal Min *</label><input id="rr_nmin" type="number" step="0.0001" class="form-control"></div>
      <div class="form-group"><label>Normal Max *</label><input id="rr_nmax" type="number" step="0.0001" class="form-control"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Critical Low</label><input id="rr_clow" type="number" step="0.0001" class="form-control"></div>
      <div class="form-group"><label>Critical High</label><input id="rr_chigh" type="number" step="0.0001" class="form-control"></div>
    </div>
    <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="saveRefRange()"><i class="fas fa-save"></i> Save Reference Range</button>
  </div>
</div>

<script>
function filterRefRanges(q){
  const tf=document.getElementById('refTestFilter').value;
  document.querySelectorAll('#refRangeTable tbody tr').forEach(r=>{
    const matchQ=!q||r.textContent.toLowerCase().includes(q.toLowerCase());
    const matchT=!tf||r.dataset.test===tf;
    r.style.display=(matchQ&&matchT)?'':'none';
  });
}
function editRefRange(rr){
  document.getElementById('refRangeModalTitle').innerHTML='<i class="fas fa-ruler-combined"></i> Edit Reference Range';
  document.getElementById('rr_id').value=rr.id;
  document.getElementById('rr_test').value=rr.test_catalog_id||'';
  document.getElementById('rr_param').value=rr.parameter_name;
  document.getElementById('rr_gender').value=rr.gender||'All';
  document.getElementById('rr_age').value=rr.age_group||'All';
  document.getElementById('rr_unit').value=rr.unit||'';
  document.getElementById('rr_nmin').value=rr.normal_min;
  document.getElementById('rr_nmax').value=rr.normal_max;
  document.getElementById('rr_clow').value=rr.critical_low||'';
  document.getElementById('rr_chigh').value=rr.critical_high||'';
  openModal('addRefRangeModal');
}
async function saveRefRange(){
  if(!validateForm({rr_param:'Parameter Name',rr_nmin:'Normal Min',rr_nmax:'Normal Max'}))return;
  const r=await labAction({action:'save_ref_range',id:document.getElementById('rr_id').value,test_catalog_id:document.getElementById('rr_test').value,parameter_name:document.getElementById('rr_param').value,gender:document.getElementById('rr_gender').value,age_group:document.getElementById('rr_age').value,unit:document.getElementById('rr_unit').value,normal_min:document.getElementById('rr_nmin').value,normal_max:document.getElementById('rr_nmax').value,critical_low:document.getElementById('rr_clow').value,critical_high:document.getElementById('rr_chigh').value});
  showToast(r.message,r.success?'success':'error');if(r.success){closeModal('addRefRangeModal');setTimeout(()=>location.reload(),800);}
}
async function deleteRefRange(id){if(!confirmAction('Delete this reference range?'))return;const r=await labAction({action:'delete_ref_range',id:id});showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);}
async function loadPreviewParams(){
  const tid=document.getElementById('prev_test').value;if(!tid)return;
  const r=await labAction({action:'get_ref_params',test_catalog_id:tid});
  const sel=document.getElementById('prev_param');sel.innerHTML='<option value="">Select...</option>';
  if(r.success&&r.params){r.params.forEach(p=>{const o=document.createElement('option');o.value=p.parameter_name;o.textContent=p.parameter_name;sel.appendChild(o);});}
}
async function previewRefRange(){
  const tid=document.getElementById('prev_test').value,pn=document.getElementById('prev_param').value,val=document.getElementById('prev_value').value,g=document.getElementById('prev_gender').value;
  if(!tid||!pn||!val){showToast('Fill all fields','error');return;}
  const r=await labAction({action:'preview_ref_range',test_catalog_id:tid,parameter_name:pn,value:val,gender:g});
  const div=document.getElementById('previewResult');
  if(r.success){
    const colors={Normal:'var(--success)',Low:'var(--warning)',High:'var(--warning)','Critical Low':'var(--danger)','Critical High':'var(--danger)'};
    div.innerHTML='<div class="adm-alert adm-alert-'+(r.flag.includes('Critical')?'danger':r.flag==='Normal'?'success':'warning')+'" style="margin:0;"><i class="fas fa-'+(r.flag==='Normal'?'check-circle':'exclamation-triangle')+'"></i><div><strong>Value: '+val+' '+r.unit+'</strong><br>Flag: <span style="font-weight:800;color:'+colors[r.flag]+';font-size:1.5rem;">'+r.flag+'</span><br>Normal Range: '+r.normal_min+' – '+r.normal_max+'</div></div>';
  }else{div.innerHTML='<div class="adm-alert adm-alert-warning"><i class="fas fa-info-circle"></i> '+r.message+'</div>';}
}
</script>
