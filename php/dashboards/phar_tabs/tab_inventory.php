<!-- ════════════════════════════════════════════════════════════
     MODULE 2: MEDICINE INVENTORY  (REVAMPED)
     ════════════════════════════════════════════════════════════ -->
<div id="sec-inventory" class="dash-section <?=($active_tab==='inventory')?'active':''?>">

<style>
.inv-table { width:100%;border-collapse:collapse;font-size:1.25rem;min-width:900px; }
.inv-table thead tr { background:var(--surface-2); position:sticky;top:0;z-index:2; }
.inv-table th { padding:1.1rem 1.4rem;text-align:left;font-size:1.1rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap; }
.inv-table td { padding:1rem 1.4rem;border-bottom:1px solid var(--border);vertical-align:middle; }
.inv-table tr:hover td { background:var(--surface-2); }
.inv-table tr:last-child td { border-bottom:none; }
.inv-med-name { font-weight:700;color:var(--text-primary);font-size:1.3rem; }
.inv-med-generic { font-size:1.05rem;color:var(--text-muted);margin-top:.2rem; }
.inv-stock-bar { width:80px;height:6px;background:var(--border);border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:.5rem; }
.inv-stock-fill { height:100%;border-radius:3px;transition:width .4s; }
.inv-filter-nav { display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.5rem; }
.inv-fn-btn { padding:.45rem 1.2rem;border-radius:20px;border:1px solid var(--border);background:var(--surface);font-size:1.1rem;font-weight:500;color:var(--text-secondary);cursor:pointer;transition:all .2s; }
.inv-fn-btn:hover,.inv-fn-btn.active { background:var(--role-accent);color:#fff;border-color:var(--role-accent); }
.cat-pill { display:inline-block;padding:.2rem .8rem;border-radius:20px;font-size:1rem;font-weight:600;background:var(--surface-2);color:var(--text-secondary); }
.batch-code { font-family:monospace;font-size:1.05rem;background:var(--surface-2);padding:.15rem .6rem;border-radius:5px;color:var(--text-secondary); }
</style>

  <div class="sec-header">
    <h2><i class="fas fa-pills"></i> Medicine Inventory</h2>
    <div style="display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;">
      <div class="adm-search-wrap"><i class="fas fa-search"></i><input type="text" class="adm-search-input" id="invSearch" placeholder="Search medicines…" oninput="filterTable('invSearch','invTable')"></div>
      <button class="btn btn-success btn-sm" onclick="openModal('modalAddMed')"><span class="btn-text"><i class="fas fa-plus"></i> Add Medicine</span></button>
    </div>
  </div>

  <nav class="inv-filter-nav">
    <button class="inv-fn-btn active" onclick="invFilter('all',this)">All (<?=$stats['total_medicines']?>)</button>
    <button class="inv-fn-btn" onclick="invFilter('in_stock',this)">In Stock (<?=$stats['in_stock']?>)</button>
    <button class="inv-fn-btn" onclick="invFilter('low_stock',this)">Low Stock (<?=$stats['low_stock']?>)</button>
    <button class="inv-fn-btn" onclick="invFilter('out_of_stock',this)">Out of Stock (<?=$stats['out_of_stock']?>)</button>
    <button class="inv-fn-btn" onclick="invFilter('expiring_soon',this)">Expiring Soon (<?=$stats['expiring_soon']?>)</button>
    <button class="inv-fn-btn" onclick="invFilter('expired',this)">Expired (<?=$stats['expired']?>)</button>
  </nav>

  <div class="adm-card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
      <table class="inv-table" id="invTable">
        <thead><tr>
          <th>Medicine</th><th>Category</th><th>Batch</th>
          <th>Stock Level</th><th>Unit Price</th><th>Expiry</th>
          <th>Supplier</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if(empty($medicines)):?>
          <tr><td colspan="9" style="text-align:center;padding:4rem;color:var(--text-muted);"><i class="fas fa-pills" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.3;"></i>No medicines in inventory</td></tr>
        <?php else: foreach($medicines as $med):
          $mStatus='in_stock';
          if($med['stock_quantity']==0) $mStatus='out_of_stock';
          elseif($med['stock_quantity']<=$med['reorder_level']) $mStatus='low_stock';
          if($med['expiry_date'] && strtotime($med['expiry_date'])<time()) $mStatus='expired';
          elseif($med['expiry_date'] && strtotime($med['expiry_date'])<strtotime('+30 days')) $mStatus=($mStatus==='out_of_stock'?'out_of_stock':'expiring_soon');
          $statusMap=['in_stock'=>['In Stock','success'],'low_stock'=>['Low Stock','warning'],'out_of_stock'=>['Out of Stock','danger'],'expiring_soon'=>['Expiring Soon','warning'],'expired'=>['Expired','danger']];
          $sInfo=$statusMap[$mStatus]??['Unknown','info'];
          $reorder=(int)($med['reorder_level']??10);
          $qty=(int)$med['stock_quantity'];
          $barPct=$reorder>0?min(100,round(($qty/$reorder)*100)):($qty>0?100:0);
          $barColor=$qty==0?'#E74C3C':($qty<=$reorder?'#F39C12':'#27AE60');
        ?>
        <tr data-status="<?=$mStatus?>" data-id="<?=$med['id']?>">
          <td data-label="Medicine">
            <div class="inv-med-name"><?=htmlspecialchars($med['medicine_name'])?></div>
            <?php if($med['generic_name']):?><div class="inv-med-generic"><?=htmlspecialchars($med['generic_name'])?></div><?php endif;?>
          </td>
          <td data-label="Category"><span class="cat-pill"><?=htmlspecialchars($med['category']??'—')?></span></td>
          <td data-label="Batch"><span class="batch-code"><?=htmlspecialchars($med['batch_number']??'—')?></span></td>
          <td data-label="Stock Level">
            <div style="display:flex;align-items:center;gap:.7rem;">
              <div class="inv-stock-bar"><div class="inv-stock-fill" style="width:<?=$barPct?>%;background:<?=$barColor?>;"></div></div>
              <strong style="color:<?=$barColor?>;font-size:1.5rem;"><?=$qty?></strong>
              <span style="color:var(--text-muted);font-size:1rem;">/ <?=$reorder?></span>
            </div>
          </td>
          <td data-label="Unit Price" style="font-weight:600;">GH₵<?=number_format($med['unit_price'],2)?></td>
          <td data-label="Expiry">
            <?php if($med['expiry_date']):
              $daysToExp=(strtotime($med['expiry_date'])-time())/86400;
              $expColor=$daysToExp<0?'var(--danger)':($daysToExp<30?'var(--warning)':'var(--text-primary)');?>
            <span style="color:<?=$expColor?>;font-weight:600;"><?=date('d M Y',strtotime($med['expiry_date']))?></span>
            <?php if($daysToExp<30&&$daysToExp>0):?><br><span style="font-size:.95rem;color:var(--warning);"><?=round($daysToExp)?> days left</span><?php endif;?>
            <?php if($daysToExp<0):?><br><span style="font-size:.95rem;color:var(--danger);font-weight:700;">EXPIRED</span><?php endif;?>
            <?php else:?>—<?php endif;?>
          </td>
          <td data-label="Supplier" style="color:var(--text-secondary);"><?=htmlspecialchars($med['supplier_name']??'—')?></td>
          <td data-label="Status"><span class="adm-badge adm-badge-<?=$sInfo[1]?>"><?=$sInfo[0]?></span></td>
          <td data-label="Actions">
            <div class="action-btns">
              <button class="btn btn-primary btn-sm" onclick="viewMedicine(<?=$med['id']?>)" title="View"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
              <button class="btn btn-sm" onclick="editMedicine(<?=$med['id']?>)" title="Edit" style="background:var(--warning-light);color:var(--warning);border:1px solid var(--warning);"><span class="btn-text"><i class="fas fa-edit"></i></span></button>
              <button class="btn btn-sm" onclick="discontinueMedicine(<?=$med['id']?>,<?=json_encode($med['medicine_name'])?>)" title="Discontinue" style="background:var(--danger-light);color:var(--danger);border:1px solid var(--danger);"><span class="btn-text"><i class="fas fa-ban"></i></span></button>
            </div>
          </td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ══ Add Medicine Modal ══ -->
<div class="modal-bg glass-panel" id="modalAddMed">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-plus-circle" style="color:var(--role-accent);"></i> Add New Medicine</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalAddMed')"><span class="btn-text">&times;</span></button></div>
    <form id="formAddMed" onsubmit="submitAddMedicine(event)">
      <div class="form-row">
        <div class="form-group"><label>Medicine Name *</label><input class="form-control" name="medicine_name" required></div>
        <div class="form-group"><label>Generic Name</label><input class="form-control" name="generic_name"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Category</label>
          <select class="form-control" name="category">
            <option value="">Select…</option>
            <?php foreach(['Analgesic','Antibiotic','Antimalarial','Antifungal','Antiviral','Antacid','Antihistamine','Cardiovascular','Dermatological','Gastrointestinal','Hormonal','Nutritional','Respiratory','Vitamin','Other'] as $c):?>
            <option><?=$c?></option><?php endforeach;?>
          </select>
        </div>
        <div class="form-group"><label>Manufacturer</label><input class="form-control" name="manufacturer"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Unit *</label>
          <select class="form-control" name="unit" required>
            <?php foreach(['tablet','capsule','syrup','injection','cream','ointment','drops','inhaler','sachet','bottle'] as $u):?>
            <option value="<?=$u?>"><?=ucfirst($u)?></option><?php endforeach;?>
          </select>
        </div>
        <div class="form-group"><label>Unit Price (GH₵) *</label><input type="number" class="form-control" name="unit_price" step="0.01" min="0" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Stock Quantity *</label><input type="number" class="form-control" name="stock_quantity" min="0" required></div>
        <div class="form-group"><label>Reorder Level</label><input type="number" class="form-control" name="reorder_level" value="10" min="0"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Batch Number</label><input class="form-control" name="batch_number"></div>
        <div class="form-group"><label>Expiry Date</label><input type="date" class="form-control" name="expiry_date"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Supplier</label>
          <select class="form-control" name="supplier_name">
            <option value="">Select…</option>
            <?php foreach($suppliers as $s):?><option><?=htmlspecialchars($s['supplier_name'])?></option><?php endforeach;?>
          </select>
        </div>
        <div class="form-group"><label>Requires Prescription</label>
          <select class="form-control" name="is_prescription_required"><option value="1">Yes</option><option value="0">No</option></select>
        </div>
      </div>
      <div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
      <div class="form-row">
        <div class="form-group"><label>Side Effects</label><textarea class="form-control" name="side_effects" rows="2"></textarea></div>
        <div class="form-group"><label>Contraindications</label><textarea class="form-control" name="contraindications" rows="2"></textarea></div>
      </div>
      <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;margin-top:.5rem;"><span class="btn-text"><i class="fas fa-plus"></i> Add Medicine</span></button>
    </form>
  </div>
</div>

<!-- ══ View Medicine Modal ══ -->
<div class="modal-bg glass-panel" id="modalViewMed">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-pills" style="color:var(--role-accent);"></i> Medicine Details</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalViewMed')"><span class="btn-text">&times;</span></button></div>
    <div id="medDetailContent"><p style="text-align:center;padding:3rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;"></i></p></div>
  </div>
</div>

<!-- ══ Edit Medicine Modal ══ -->
<div class="modal-bg glass-panel" id="modalEditMed">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-edit" style="color:var(--warning);"></i> Edit Medicine</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalEditMed')"><span class="btn-text">&times;</span></button></div>
    <div id="editMedContent"><p style="text-align:center;padding:3rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;"></i></p></div>
  </div>
</div>

<script>
function invFilter(status, btn) {
  document.querySelectorAll('.inv-filter-nav .inv-fn-btn').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  document.querySelectorAll('#invTable tbody tr').forEach(row=>{
    row.style.display=(status==='all'||row.dataset.status===status)?'':'none';
  });
}

async function submitAddMedicine(e){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target)); fd.action='add_medicine';
  const r=await pharmAction(fd);
  if(r.success){toast(r.message||'Medicine added');closeModal('modalAddMed');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function viewMedicine(id){
  openModal('modalViewMed');
  document.getElementById('medDetailContent').innerHTML='<p style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--role-accent);"></i></p>';
  const r=await pharmAction({action:'get_medicine',id});
  if(!r.success){document.getElementById('medDetailContent').innerHTML=`<p style="color:var(--danger);text-align:center;padding:2rem;">${r.message}</p>`;return;}
  const m=r.medicine;
  const expColor=m.days_to_expiry<0?'var(--danger)':(m.days_to_expiry<30?'var(--warning)':'var(--success)');
  const stockColor=m.stock_quantity<=m.reorder_level?'var(--danger)':'var(--success)';
  document.getElementById('medDetailContent').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
      <div class="dm-info-block"><div class="dm-lbl">Medicine Name</div><div class="dm-val">${m.medicine_name}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Generic Name</div><div class="dm-val">${m.generic_name||'—'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Category</div><div class="dm-val">${m.category||'—'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Manufacturer</div><div class="dm-val">${m.manufacturer||'—'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Unit / Form</div><div class="dm-val">${m.unit||'—'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Unit Price</div><div class="dm-val">GH₵${parseFloat(m.unit_price).toFixed(2)}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Current Stock</div><div class="dm-val" style="font-size:2.5rem;font-weight:800;color:${stockColor};">${m.stock_quantity} <span style="font-size:1.2rem;color:var(--text-muted);">/ ${m.reorder_level} min</span></div></div>
      <div class="dm-info-block"><div class="dm-lbl">Batch Number</div><div class="dm-val"><span class="batch-code">${m.batch_number||'—'}</span></div></div>
      <div class="dm-info-block"><div class="dm-lbl">Expiry Date</div><div class="dm-val" style="color:${expColor};font-weight:700;">${m.expiry_date||'—'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Supplier</div><div class="dm-val">${m.supplier_name||'—'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Requires Rx</div><div class="dm-val">${m.is_prescription_required?'Yes':'No'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Status</div><div class="dm-val"><span class="adm-badge adm-badge-${m.status==='active'?'success':'danger'}">${m.status}</span></div></div>
    </div>
    ${m.description?`<div class="dm-info-block" style="margin-top:1.5rem;"><div class="dm-lbl">Description</div><div class="dm-val" style="font-weight:400;">${m.description}</div></div>`:''}
    ${m.side_effects?`<div class="dm-info-block" style="margin-top:1rem;"><div class="dm-lbl">Side Effects</div><div class="dm-val" style="font-weight:400;">${m.side_effects}</div></div>`:''}`;
}

async function editMedicine(id){
  openModal('modalEditMed');
  document.getElementById('editMedContent').innerHTML='<p style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--role-accent);"></i></p>';
  const r=await pharmAction({action:'get_medicine',id});
  if(!r.success){document.getElementById('editMedContent').innerHTML=`<p style="color:var(--danger);text-align:center;padding:2rem;">${r.message}</p>`;return;}
  const m=r.medicine;
  document.getElementById('editMedContent').innerHTML=`
    <form onsubmit="submitEditMed(event,${id})">
      <div class="form-row">
        <div class="form-group"><label>Medicine Name</label><input class="form-control" name="medicine_name" value="${m.medicine_name}" required></div>
        <div class="form-group"><label>Generic Name</label><input class="form-control" name="generic_name" value="${m.generic_name||''}"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Category</label><input class="form-control" name="category" value="${m.category||''}"></div>
        <div class="form-group"><label>Manufacturer</label><input class="form-control" name="manufacturer" value="${m.manufacturer||''}"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Unit Price (GH₵)</label><input type="number" class="form-control" name="unit_price" value="${m.unit_price}" step="0.01" min="0"></div>
        <div class="form-group"><label>Reorder Level</label><input type="number" class="form-control" name="reorder_level" value="${m.reorder_level}" min="0"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Stock Quantity</label><input type="number" class="form-control" name="stock_quantity" value="${m.stock_quantity}" min="0"></div>
        <div class="form-group"><label>Batch Number</label><input class="form-control" name="batch_number" value="${m.batch_number||''}"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Expiry Date</label><input type="date" class="form-control" name="expiry_date" value="${m.expiry_date||''}"></div>
        <div class="form-group"><label>Supplier</label><input class="form-control" name="supplier_name" value="${m.supplier_name||''}"></div>
      </div>
      <div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="2">${m.description||''}</textarea></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-save"></i> Save Changes</span></button>
    </form>`;
}

async function submitEditMed(e, id){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target)); fd.action='update_medicine'; fd.id=id;
  const r=await pharmAction(fd);
  if(r.success){toast(r.message||'Updated');closeModal('modalEditMed');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function discontinueMedicine(id, name){
  if(!confirm(`Discontinue "${name}"? This will mark it inactive.`)) return;
  const r=await pharmAction({action:'discontinue_medicine',id});
  if(r.success){toast(r.message||'Discontinued');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}
</script>
