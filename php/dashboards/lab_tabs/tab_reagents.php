<!-- ═══════════════ MODULE 7: REAGENT INVENTORY ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-prescription-bottle" style="color:var(--role-accent);margin-right:.6rem;"></i> Reagent Inventory</h1>
    <p>Stock levels, expiry tracking, usage logging, and supplier management</p>
  </div>
  <button class="adm-btn adm-btn-primary" onclick="openModal('addReagentModal')"><i class="fas fa-plus-circle"></i> Add Reagent</button>
</div>

<div class="adm-summary-strip">
  <?php
  $rg_stats=['In Stock'=>0,'Low Stock'=>0,'Out of Stock'=>0,'Expiring Soon'=>0,'Expired'=>0];
  foreach($reagents as $rg) if(isset($rg_stats[$rg['status']])) $rg_stats[$rg['status']]++;
  $rgcolors=['In Stock'=>'green','Low Stock'=>'orange','Out of Stock'=>'red','Expiring Soon'=>'orange','Expired'=>'red'];
  foreach($rg_stats as $st=>$cnt):?>
  <div class="adm-mini-card"><div class="adm-mini-card-num <?=$rgcolors[$st]?>"><?=$cnt?></div><div class="adm-mini-card-label"><?=$st?></div></div>
  <?php endforeach;?>
</div>

<div class="filter-tabs">
  <span class="ftab active" onclick="filterTable('rgTable','all',this)">All</span>
  <?php foreach(array_keys($rg_stats) as $st):if($rg_stats[$st]>0):?><span class="ftab" onclick="filterTable('rgTable','<?=$st?>',this)"><?=$st?> (<?=$rg_stats[$st]?>)</span><?php endif;endforeach;?>
</div>

<div class="adm-card">
  <div class="adm-card-body" style="padding:0;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="rgTable">
        <thead><tr><th>Name</th><th>Cat#</th><th>Stock</th><th>Reorder</th><th>Expiry</th><th>Supplier</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($reagents)):?>
          <tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-prescription-bottle" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>No reagents registered</td></tr>
        <?php else: foreach($reagents as $rg):
          $st_cls=['In Stock'=>'success','Low Stock'=>'warning','Out of Stock'=>'danger','Expired'=>'danger','Expiring Soon'=>'warning'][$rg['status']]??'info';
        ?>
        <tr class="<?=in_array($rg['status'],['Out of Stock','Expired'])?'row-danger':($rg['status']==='Low Stock'?'row-warning':'')?>" data-status="<?=e($rg['status'])?>">
          <td><strong><?=e($rg['name'])?></strong><?php if($rg['batch_number']):?><br><span style="font-family:monospace;color:var(--text-muted);font-size:1rem;">Batch: <?=e($rg['batch_number'])?></span><?php endif;?></td>
          <td style="font-family:monospace;"><?=e($rg['catalog_number']??'—')?></td>
          <td style="font-weight:700;font-size:1.4rem;color:<?=$rg['quantity_in_stock']<=($rg['reorder_level']??0)?'var(--danger)':'var(--text-primary)'?>;"><?=$rg['quantity_in_stock']?> <span style="color:var(--text-muted);font-size:1rem;"><?=e($rg['unit'])?></span></td>
          <td><?=$rg['reorder_level']?></td>
          <td style="<?=$rg['expiry_date']&&$rg['expiry_date']<$today?'color:var(--danger);font-weight:700;':''?>"><?=$rg['expiry_date']?date('d M Y',strtotime($rg['expiry_date'])):'—'?></td>
          <td><?=e($rg['supplier']??'—')?></td>
          <td><span class="adm-badge adm-badge-<?=$st_cls?>"><?=e($rg['status'])?></span></td>
          <td class="adm-table-actions">
            <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick='editReagent(<?=json_encode($rg)?>)' title="Edit"><i class="fas fa-edit"></i></button>
            <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="useReagent(<?=$rg['id']?>,'<?=e($rg['name'])?>')" title="Record Usage"><i class="fas fa-minus-circle"></i></button>
            <button class="adm-btn adm-btn-sm adm-btn-success" onclick="receiveReagent(<?=$rg['id']?>,'<?=e($rg['name'])?>')" title="Receive Stock"><i class="fas fa-plus-circle"></i></button>
            <?php if($rg['status']==='Expired'):?><button class="adm-btn adm-btn-sm adm-btn-warning" onclick="disposeReagent(<?=$rg['id']?>,'<?=e($rg['name'])?>')" title="Dispose"><i class="fas fa-trash"></i></button><?php endif;?>
          </td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Reagent Modal -->
<div class="modal-bg" id="addReagentModal"><div class="modal-box wide">
  <div class="modal-header"><h3 id="rgModalTitle"><i class="fas fa-prescription-bottle"></i> Add Reagent</h3><button class="modal-close" onclick="closeModal('addReagentModal')">&times;</button></div>
  <input type="hidden" id="rg_id">
  <div class="form-row"><div class="form-group"><label>Name *</label><input id="rg_name" class="form-control"></div><div class="form-group"><label>Catalog Number</label><input id="rg_catnum" class="form-control"></div></div>
  <div class="form-row"><div class="form-group"><label>Manufacturer</label><input id="rg_mfr" class="form-control"></div><div class="form-group"><label>Supplier</label><input id="rg_supplier" class="form-control"></div></div>
  <div class="form-row"><div class="form-group"><label>Category</label><input id="rg_category" class="form-control"></div><div class="form-group"><label>Unit</label><input id="rg_unit" class="form-control" value="pcs"></div></div>
  <div class="form-row"><div class="form-group"><label>Quantity</label><input id="rg_qty" type="number" class="form-control" value="0"></div><div class="form-group"><label>Reorder Level</label><input id="rg_reorder" type="number" class="form-control" value="5"></div></div>
  <div class="form-row"><div class="form-group"><label>Unit Cost (GH₵)</label><input id="rg_cost" type="number" step="0.01" class="form-control"></div><div class="form-group"><label>Expiry Date</label><input id="rg_expiry" type="date" class="form-control"></div></div>
  <div class="form-row"><div class="form-group"><label>Batch Number</label><input id="rg_batch" class="form-control"></div><div class="form-group"><label>Storage Conditions</label><input id="rg_storage" class="form-control" placeholder="e.g. 2-8°C"></div></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="saveReagent()"><i class="fas fa-save"></i> Save</button>
</div></div>

<script>
function editReagent(r){document.getElementById('rgModalTitle').innerHTML='<i class="fas fa-prescription-bottle"></i> Edit Reagent';document.getElementById('rg_id').value=r.id;document.getElementById('rg_name').value=r.name;document.getElementById('rg_catnum').value=r.catalog_number||'';document.getElementById('rg_mfr').value=r.manufacturer||'';document.getElementById('rg_supplier').value=r.supplier||'';document.getElementById('rg_category').value=r.category||'';document.getElementById('rg_unit').value=r.unit;document.getElementById('rg_qty').value=r.quantity_in_stock;document.getElementById('rg_reorder').value=r.reorder_level;document.getElementById('rg_cost').value=r.unit_cost;document.getElementById('rg_expiry').value=r.expiry_date||'';document.getElementById('rg_batch').value=r.batch_number||'';document.getElementById('rg_storage').value=r.storage_conditions||'';openModal('addReagentModal');}
async function saveReagent(){if(!validateForm({rg_name:'Reagent Name'}))return;const r=await labAction({action:'save_reagent',id:document.getElementById('rg_id').value,name:document.getElementById('rg_name').value,catalog_number:document.getElementById('rg_catnum').value,manufacturer:document.getElementById('rg_mfr').value,supplier:document.getElementById('rg_supplier').value,category:document.getElementById('rg_category').value,unit:document.getElementById('rg_unit').value,quantity:document.getElementById('rg_qty').value,reorder_level:document.getElementById('rg_reorder').value,unit_cost:document.getElementById('rg_cost').value,expiry_date:document.getElementById('rg_expiry').value,batch_number:document.getElementById('rg_batch').value,storage_conditions:document.getElementById('rg_storage').value});showToast(r.message,r.success?'success':'error');if(r.success){closeModal('addReagentModal');setTimeout(()=>location.reload(),800);}}
async function useReagent(id,name){const qty=prompt('Quantity used for '+name+':');if(!qty||isNaN(qty))return;const r=await labAction({action:'reagent_transaction',reagent_id:id,type:'Used',quantity:qty});showToast(r.message,r.success?'success':'error');if(r.success) setTimeout(()=>location.reload(),800);}
async function receiveReagent(id,name){const qty=prompt('Quantity received for '+name+':');if(!qty||isNaN(qty))return;const r=await labAction({action:'reagent_transaction',reagent_id:id,type:'Received',quantity:qty});showToast(r.message,r.success?'success':'error');if(r.success) setTimeout(()=>location.reload(),800);}
async function disposeReagent(id,name){if(!confirmAction('Dispose expired reagent: '+name+'?'))return;const r=await labAction({action:'reagent_transaction',reagent_id:id,type:'Disposed',quantity:0});showToast(r.message,r.success?'success':'error');if(r.success) setTimeout(()=>location.reload(),800);}
</script>
