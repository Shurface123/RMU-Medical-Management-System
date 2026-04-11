<!-- ════════════════════════════════════════════════════════════
     MODULE 2: MEDICINE INVENTORY
     ════════════════════════════════════════════════════════════ -->
<div id="sec-inventory" class="dash-section <?=($active_tab==='inventory')?'active':''?>">

  <div class="sec-header">
    <h2><i class="fas fa-pills"></i> Medicine Inventory</h2>
    <div style="display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;">
      <div class="adm-search-wrap"><i class="fas fa-search"></i><input type="text" class="adm-search-input" id="invSearch" placeholder="Search medicines…" oninput="filterTable('invSearch','invTable')"></div>
      <button class="btn btn-success btn-sm" onclick="openModal('modalAddMed')"><span class="btn-text"><i class="fas fa-plus"></i> Add Medicine</span></button>
    </div>
  </div>

  <!-- Filter Tabs -->
  <div class="filter-tabs">
    <button class="btn btn-primary ftab active" onclick="filterByAttr('all','invTable',this)"><span class="btn-text">All (<?=$stats['total_medicines']?>)</span></button>
    <button class="btn btn-primary ftab" onclick="filterByAttr('in_stock','invTable',this)"><span class="btn-text">In Stock (<?=$stats['in_stock']?>)</span></button>
    <button class="btn btn-primary ftab" onclick="filterByAttr('low_stock','invTable',this)"><span class="btn-text">Low Stock (<?=$stats['low_stock']?>)</span></button>
    <button class="btn btn-primary ftab" onclick="filterByAttr('out_of_stock','invTable',this)"><span class="btn-text">Out of Stock (<?=$stats['out_of_stock']?>)</span></button>
    <button class="btn btn-primary ftab" onclick="filterByAttr('expiring_soon','invTable',this)"><span class="btn-text">Expiring Soon (<?=$stats['expiring_soon']?>)</span></button>
    <button class="btn btn-primary ftab" onclick="filterByAttr('expired','invTable',this)"><span class="btn-text">Expired (<?=$stats['expired']?>)</span></button>
  </div>

  <div class="adm-card">
    <div class="adm-table-wrap">
      <table class="adm-table" id="invTable">
        <thead><tr><th>Medicine</th><th>Category</th><th>Batch</th><th>Stock Qty</th><th>Unit Price</th><th>Expiry</th><th>Supplier</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($medicines)):?>
          <tr><td colspan="9" style="text-align:center;padding:3rem;color:var(--text-muted);">No medicines in inventory</td></tr>
        <?php else: foreach($medicines as $med):
          // Determine status
          $mStatus='in_stock';
          if($med['stock_quantity']==0) $mStatus='out_of_stock';
          elseif($med['stock_quantity']<=$med['reorder_level']) $mStatus='low_stock';
          if($med['expiry_date'] && strtotime($med['expiry_date'])<time()) $mStatus='expired';
          elseif($med['expiry_date'] && strtotime($med['expiry_date'])<strtotime('+30 days')) $mStatus=($mStatus==='out_of_stock'?'out_of_stock':'expiring_soon');

          $statusMap=['in_stock'=>['In Stock','success'],'low_stock'=>['Low Stock','warning'],'out_of_stock'=>['Out of Stock','danger'],'expiring_soon'=>['Expiring Soon','warning'],'expired'=>['Expired','danger']];
          $sInfo=$statusMap[$mStatus]??['Unknown','info'];
        ?>
        <tr data-status="<?=$mStatus?>" data-id="<?=$med['id']?>">
          <td>
            <strong><?=htmlspecialchars($med['medicine_name'])?></strong>
            <?php if($med['generic_name']):?><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($med['generic_name'])?></span><?php endif;?>
          </td>
          <td><?=htmlspecialchars($med['category']??'—')?></td>
          <td><code style="font-size:1.1rem;"><?=htmlspecialchars($med['batch_number']??'—')?></code></td>
          <td>
            <strong style="font-size:1.5rem;color:var(--<?=$med['stock_quantity']<=($med['reorder_level']??10)?'danger':'text-primary'?>);"><?=$med['stock_quantity']?></strong>
            <span style="font-size:1rem;color:var(--text-muted);">/<?=$med['reorder_level']??10?> min</span>
          </td>
          <td>GH₵<?=number_format($med['unit_price'],2)?></td>
          <td>
            <?php if($med['expiry_date']):
              $daysToExp=(strtotime($med['expiry_date'])-time())/86400;
              $expColor=$daysToExp<0?'var(--danger)':($daysToExp<30?'var(--warning)':'var(--text-primary)');
            ?>
            <span style="color:<?=$expColor?>;font-weight:600;"><?=date('d M Y',strtotime($med['expiry_date']))?></span>
            <?php if($daysToExp<30 && $daysToExp>0):?><br><span style="font-size:1rem;color:var(--warning);"><?=round($daysToExp)?> days left</span><?php endif;?>
            <?php if($daysToExp<0):?><br><span style="font-size:1rem;color:var(--danger);">EXPIRED</span><?php endif;?>
            <?php else:?>—<?php endif;?>
          </td>
          <td><?=htmlspecialchars($med['supplier_name']??'—')?></td>
          <td><span class="adm-badge adm-badge-<?=$sInfo[1]?>"><?=$sInfo[0]?></span></td>
          <td>
            <div class="action-btns">
              <button class="btn btn-primary btn-sm" onclick="viewMedicine(<?=$med['id']?>)" title="View"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
              <button class="btn btn-primary btn btn-sm" onclick="editMedicine(<?=$med['id']?>)" title="Edit" style="background:var(--warning-light);color:var(--warning);border-color:var(--warning);"><span class="btn-text"><i class="fas fa-edit"></i></span></button>
              <button class="btn btn-primary btn btn-sm" onclick="discontinueMedicine(<?=$med['id']?>,<?=json_encode($med['medicine_name'])?>)" title="Discontinue" style="background:var(--danger-light);color:var(--danger);border-color:var(--danger);"><span class="btn-text"><i class="fas fa-ban"></i></span></button>
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
<div class="modal-bg" id="modalAddMed">
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
            <option>Analgesic</option><option>Antibiotic</option><option>Antimalarial</option>
            <option>Antifungal</option><option>Antiviral</option><option>Antacid</option>
            <option>Antihistamine</option><option>Cardiovascular</option><option>Dermatological</option>
            <option>Gastrointestinal</option><option>Hormonal</option><option>Nutritional</option>
            <option>Respiratory</option><option>Vitamin</option><option>Other</option>
          </select>
        </div>
        <div class="form-group"><label>Manufacturer</label><input class="form-control" name="manufacturer"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Unit *</label>
          <select class="form-control" name="unit" required>
            <option value="tablet">Tablet</option><option value="capsule">Capsule</option>
            <option value="syrup">Syrup</option><option value="injection">Injection</option>
            <option value="cream">Cream</option><option value="ointment">Ointment</option>
            <option value="drops">Drops</option><option value="inhaler">Inhaler</option>
            <option value="sachet">Sachet</option><option value="bottle">Bottle</option>
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
      <div class="form-group"><label>Storage Instructions</label><textarea class="form-control" name="storage_instructions" rows="2"></textarea></div>
      <div class="form-row">
        <div class="form-group"><label>Side Effects</label><textarea class="form-control" name="side_effects" rows="2"></textarea></div>
        <div class="form-group"><label>Contraindications</label><textarea class="form-control" name="contraindications" rows="2"></textarea></div>
      </div>
      <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;margin-top:.5rem;"><span class="btn-text"><i class="fas fa-plus"></i> Add Medicine</span></button>
    </form>
  </div>
</div>

<!-- ══ View Medicine Detail Modal ══ -->
<div class="modal-bg" id="modalViewMed">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-pills" style="color:var(--role-accent);"></i> Medicine Details</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalViewMed')"><span class="btn-text">&times;</span></button></div>
    <div id="medDetailContent" style="font-size:1.3rem;"><p style="text-align:center;color:var(--text-muted);padding:2rem;">Loading…</p></div>
  </div>
</div>

<!-- ══ Edit Medicine Modal ══ -->
<div class="modal-bg" id="modalEditMed">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-edit" style="color:var(--warning);"></i> Edit Medicine</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalEditMed')"><span class="btn-text">&times;</span></button></div>
    <div id="editMedContent"><p style="text-align:center;color:var(--text-muted);padding:2rem;">Loading…</p></div>
  </div>
</div>

<script>
async function submitAddMedicine(e){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target));
  fd.action='add_medicine';
  const r=await pharmAction(fd);
  if(r.success){toast(r.message||'Medicine added');closeModal('modalAddMed');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function viewMedicine(id){
  openModal('modalViewMed');
  const r=await pharmAction({action:'get_medicine',id});
  if(!r.success){document.getElementById('medDetailContent').innerHTML='<p style="color:var(--danger);">'+r.message+'</p>';return;}
  const m=r.medicine;
  const expColor=m.days_to_expiry<0?'var(--danger)':(m.days_to_expiry<30?'var(--warning)':'var(--success)');
  document.getElementById('medDetailContent').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">MEDICINE NAME</strong><p style="font-weight:600;">${m.medicine_name}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">GENERIC NAME</strong><p>${m.generic_name||'—'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">CATEGORY</strong><p>${m.category||'—'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">MANUFACTURER</strong><p>${m.manufacturer||'—'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">UNIT / FORM</strong><p>${m.unit||'—'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">UNIT PRICE</strong><p>GH₵${parseFloat(m.unit_price).toFixed(2)}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">STOCK</strong><p style="font-size:1.6rem;font-weight:800;color:${m.stock_quantity<=m.reorder_level?'var(--danger)':'var(--success)'};">${m.stock_quantity} <span style="font-size:1.1rem;color:var(--text-muted);">/ ${m.reorder_level} min</span></p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">BATCH</strong><p>${m.batch_number||'—'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">EXPIRY DATE</strong><p style="color:${expColor};font-weight:600;">${m.expiry_date||'—'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">SUPPLIER</strong><p>${m.supplier_name||'—'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">REQUIRES RX</strong><p>${m.is_prescription_required?'Yes':'No'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">STATUS</strong><p>${m.status}</p></div>
    </div>
    <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0;">
    <div style="margin-bottom:1rem;"><strong style="color:var(--text-muted);font-size:1.1rem;">DESCRIPTION</strong><p>${m.description||'No description'}</p></div>
    <div style="margin-bottom:1rem;"><strong style="color:var(--text-muted);font-size:1.1rem;">STORAGE INSTRUCTIONS</strong><p>${m.storage_instructions||'None specified'}</p></div>
    <div style="margin-bottom:1rem;"><strong style="color:var(--text-muted);font-size:1.1rem;">SIDE EFFECTS</strong><p>${m.side_effects||'None listed'}</p></div>
    <div><strong style="color:var(--text-muted);font-size:1.1rem;">CONTRAINDICATIONS</strong><p>${m.contraindications||'None listed'}</p></div>`;
}

async function editMedicine(id){
  openModal('modalEditMed');
  const r=await pharmAction({action:'get_medicine',id});
  if(!r.success){document.getElementById('editMedContent').innerHTML='<p style="color:var(--danger);">'+r.message+'</p>';return;}
  const m=r.medicine;
  document.getElementById('editMedContent').innerHTML=`
    <form onsubmit="submitEditMedicine(event,${id})">
      <div class="form-row">
        <div class="form-group"><label>Medicine Name</label><input class="form-control" name="medicine_name" value="${m.medicine_name}" required></div>
        <div class="form-group"><label>Generic Name</label><input class="form-control" name="generic_name" value="${m.generic_name||''}"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Category</label><input class="form-control" name="category" value="${m.category||''}"></div>
        <div class="form-group"><label>Manufacturer</label><input class="form-control" name="manufacturer" value="${m.manufacturer||''}"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Unit Price</label><input type="number" class="form-control" name="unit_price" value="${m.unit_price}" step="0.01" min="0"></div>
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
      <div class="form-group"><label>Storage Instructions</label><textarea class="form-control" name="storage_instructions" rows="2">${m.storage_instructions||''}</textarea></div>
      <div class="form-row">
        <div class="form-group"><label>Side Effects</label><textarea class="form-control" name="side_effects" rows="2">${m.side_effects||''}</textarea></div>
        <div class="form-group"><label>Contraindications</label><textarea class="form-control" name="contraindications" rows="2">${m.contraindications||''}</textarea></div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-save"></i> Save Changes</span></button>
    </form>`;
}

async function submitEditMedicine(e,id){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target));
  fd.action='update_medicine'; fd.id=id;
  const r=await pharmAction(fd);
  if(r.success){toast(r.message||'Updated');closeModal('modalEditMed');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function discontinueMedicine(id,name){
  if(!confirm(`Discontinue "${name}"? This will mark it inactive.`)) return;
  const r=await pharmAction({action:'discontinue_medicine',id});
  if(r.success){toast(r.message||'Discontinued');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}
</script>
