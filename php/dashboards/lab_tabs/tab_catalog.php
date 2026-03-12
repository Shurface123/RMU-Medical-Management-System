<!-- ═══════════════ MODULE 5b: TEST CATALOG ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-book-medical" style="color:var(--role-accent);margin-right:.6rem;"></i> Test Catalog</h1>
    <p>All available laboratory tests — categories, requirements, pricing, turnaround times</p>
  </div>
  <button class="adm-btn adm-btn-primary" onclick="openAddTest()"><i class="fas fa-plus-circle"></i> Add Test</button>
</div>

<!-- Category Filter -->
<div class="filter-tabs">
  <span class="ftab active" onclick="filterCatalog('all',this)">All (<?=count($test_catalog)?>)</span>
  <?php $cats=[]; foreach($test_catalog as $tc){$c=$tc['category']??'Other';$cats[$c]=($cats[$c]??0)+1;} ksort($cats);
  foreach($cats as $cat=>$cnt):?><span class="ftab" onclick="filterCatalog('<?=e($cat)?>',this)"><?=e($cat)?> (<?=$cnt?>)</span><?php endforeach;?>
</div>

<!-- Catalog Cards Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem;" id="catalogGrid">
<?php foreach($test_catalog as $tc):?>
<div class="adm-card" style="margin-bottom:0;" data-category="<?=e($tc['category']??'Other')?>">
  <div class="adm-card-header" style="background:linear-gradient(135deg,rgba(142,68,173,.08),rgba(195,155,211,.08));border:none;">
    <h3 style="font-size:1.4rem;"><i class="fas fa-flask" style="color:var(--role-accent);"></i> <?=e($tc['test_name'])?></h3>
    <span class="adm-badge adm-badge-primary"><?=e($tc['test_code'])?></span>
  </div>
  <div class="adm-card-body" style="font-size:1.2rem;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;">
      <div><i class="fas fa-tag" style="color:var(--role-accent);width:16px;"></i> <?=e($tc['category']??'—')?></div>
      <div><i class="fas fa-vial" style="color:var(--info);width:16px;"></i> <?=e($tc['sample_type']??'Blood')?></div>
      <div><i class="fas fa-clock" style="color:var(--warning);width:16px;"></i> <?=$tc['processing_time_hours']??1?>h processing</div>
      <div><i class="fas fa-hourglass-half" style="color:var(--success);width:16px;"></i> <?=$tc['normal_turnaround_hours']??24?>h TAT</div>
      <div><i class="fas fa-ghana-cedi-sign" style="color:var(--success);width:16px;"></i> GH₵ <?=number_format($tc['price']??0,2)?></div>
      <div><?=$tc['requires_fasting']?'<span class="adm-badge adm-badge-warning"><i class="fas fa-utensils"></i> Fasting</span>':'<span class="adm-badge adm-badge-success">No fasting</span>'?></div>
    </div>
    <?php if($tc['collection_instructions']):?><div style="margin-top:.8rem;color:var(--text-muted);font-size:1.1rem;"><i class="fas fa-info-circle"></i> <?=e(substr($tc['collection_instructions'],0,100))?></div><?php endif;?>
    <div class="adm-table-actions" style="margin-top:1rem;justify-content:flex-end;">
      <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick='editTest(<?=json_encode($tc)?>)'><i class="fas fa-edit"></i> Edit</button>
      <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="deleteTest(<?=$tc['id']?>)"><i class="fas fa-trash"></i></button>
    </div>
  </div>
</div>
<?php endforeach;?>
</div>

<!-- Add/Edit Test Modal -->
<div class="modal-bg" id="addTestModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3 id="testModalTitle"><i class="fas fa-book-medical"></i> Add Test</h3><button class="modal-close" onclick="closeModal('addTestModal')">&times;</button></div>
    <input type="hidden" id="tc_id">
    <div class="form-row">
      <div class="form-group"><label>Test Name *</label><input id="tc_name" class="form-control"></div>
      <div class="form-group"><label>Test Code *</label><input id="tc_code" class="form-control" placeholder="e.g. FBC-001"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Category</label><select id="tc_cat" class="form-control"><option>Hematology</option><option>Biochemistry</option><option>Microbiology</option><option>Immunology</option><option>Urinalysis</option><option>Histology</option><option>Radiology</option><option>Serology</option><option>Parasitology</option><option>Other</option></select></div>
      <div class="form-group"><label>Sample Type</label><select id="tc_sample" class="form-control"><option>Blood</option><option>Urine</option><option>Stool</option><option>Swab</option><option>CSF</option><option>Tissue</option><option>Sputum</option><option>Other</option></select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Container</label><input id="tc_container" class="form-control" placeholder="e.g. EDTA, Plain"></div>
      <div class="form-group"><label>Price (GH₵)</label><input id="tc_price" type="number" step="0.01" class="form-control"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Processing Time (hrs)</label><input id="tc_proc" type="number" step="0.5" class="form-control" value="1"></div>
      <div class="form-group"><label>Turnaround Time (hrs)</label><input id="tc_tat" type="number" step="0.5" class="form-control" value="24"></div>
    </div>
    <div class="form-group"><label>Collection Instructions</label><textarea id="tc_instr" class="form-control" rows="2"></textarea></div>
    <div class="form-group"><label><input type="checkbox" id="tc_fasting"> Requires Fasting</label></div>
    <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="saveTest()"><i class="fas fa-save"></i> Save</button>
  </div>
</div>

<script>
function filterCatalog(cat,el){
  el.parentNode.querySelectorAll('.ftab').forEach(f=>f.classList.remove('active'));el.classList.add('active');
  document.querySelectorAll('#catalogGrid > .adm-card').forEach(c=>{c.style.display=(cat==='all'||c.dataset.category===cat)?'':'none';});
}
function openAddTest(){
  document.getElementById('testModalTitle').innerHTML='<i class="fas fa-book-medical"></i> Add Test';
  ['tc_id','tc_name','tc_code','tc_container','tc_price','tc_instr'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('tc_proc').value='1';document.getElementById('tc_tat').value='24';document.getElementById('tc_fasting').checked=false;
  openModal('addTestModal');
}
function editTest(t){
  document.getElementById('testModalTitle').innerHTML='<i class="fas fa-book-medical"></i> Edit Test';
  document.getElementById('tc_id').value=t.id;document.getElementById('tc_name').value=t.test_name;document.getElementById('tc_code').value=t.test_code;
  document.getElementById('tc_cat').value=t.category||'Other';document.getElementById('tc_sample').value=t.sample_type||'Blood';
  document.getElementById('tc_container').value=t.container_type||'';document.getElementById('tc_price').value=t.price||0;
  document.getElementById('tc_proc').value=t.processing_time_hours||1;document.getElementById('tc_tat').value=t.normal_turnaround_hours||24;
  document.getElementById('tc_instr').value=t.collection_instructions||'';document.getElementById('tc_fasting').checked=!!t.requires_fasting;
  openModal('addTestModal');
}
async function saveTest(){
  if(!validateForm({tc_name:'Test Name',tc_code:'Test Code'}))return;
  const r=await labAction({action:'save_test_catalog',id:document.getElementById('tc_id').value,test_name:document.getElementById('tc_name').value,test_code:document.getElementById('tc_code').value,category:document.getElementById('tc_cat').value,sample_type:document.getElementById('tc_sample').value,container_type:document.getElementById('tc_container').value,price:document.getElementById('tc_price').value,processing_time:document.getElementById('tc_proc').value,tat:document.getElementById('tc_tat').value,instructions:document.getElementById('tc_instr').value,fasting:document.getElementById('tc_fasting').checked?1:0});
  showToast(r.message,r.success?'success':'error');if(r.success){closeModal('addTestModal');setTimeout(()=>location.reload(),800);}
}
async function deleteTest(id){if(!confirmAction('Deactivate this test?'))return;const r=await labAction({action:'delete_test_catalog',id:id});showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);}
</script>
