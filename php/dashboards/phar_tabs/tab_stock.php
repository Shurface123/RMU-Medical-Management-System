<!-- ════════════════════════════════════════════════════════════
     MODULE 4: STOCK MANAGEMENT
     ════════════════════════════════════════════════════════════ -->
<div id="sec-stock" class="dash-section <?=($active_tab==='stock')?'active':''?>">

  <div class="sec-header">
    <h2><i class="fas fa-boxes-stacked"></i> Stock Management</h2>
    <div style="display:flex;gap:.8rem;flex-wrap:wrap;">
      <button class="btn btn-success btn-sm" onclick="openModal('modalAddStock')"><span class="btn-text"><i class="fas fa-plus"></i> Receive Stock</span></button>
      <button class="btn btn-primary btn btn-sm" onclick="openModal('modalAdjustStock')" style="background:var(--warning-light);color:var(--warning);border-color:var(--warning);"><span class="btn-text"><i class="fas fa-sliders"></i> Adjust Stock</span></button>
      <button class="btn btn-primary btn-sm" onclick="openModal('modalCreatePO')"><span class="btn-text"><i class="fas fa-file-invoice"></i> New Purchase Order</span></button>
      <button class="btn btn-primary btn btn-sm" onclick="openModal('modalAddSupplier')" style="background:var(--info-light);color:var(--info);border-color:var(--info);"><span class="btn-text"><i class="fas fa-truck"></i> Add Supplier</span></button>
    </div>
  </div>

  <!-- Sub-tabs within Stock Management -->
  <div class="filter-tabs" id="stockSubTabs">
    <button class="btn btn-primary ftab active" onclick="showStockSub('transactions',this)"><span class="btn-text">Transaction History</span></button>
    <button class="btn btn-warning btn-icon ftab" onclick="showStockSub('orders',this)"><span class="btn-text">Purchase Orders (<?=$stats['pending_orders']?>)</span></button>
    <button class="btn btn-primary ftab" onclick="showStockSub('suppliers',this)"><span class="btn-text">Suppliers (<?=count($suppliers)?>)</span></button>
  </div>

  <!-- Stock Transactions -->
  <div id="stockSub-transactions" class="stock-sub active">
    <div class="adm-card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;padding:0 .5rem;">
        <h3 style="font-size:1.4rem;font-weight:700;"><i class="fas fa-exchange-alt" style="color:var(--role-accent);margin-right:.5rem;"></i>Stock Transactions</h3>
        <div class="adm-search-wrap"><i class="fas fa-search"></i><input type="text" class="adm-search-input" id="txSearch" placeholder="Search…" oninput="filterTable('txSearch','txTable')"></div>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table" id="txTable">
          <thead><tr><th>Date</th><th>Medicine</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>By</th><th>Notes</th></tr></thead>
          <tbody>
          <?php if(empty($stock_txns)):?><tr><td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No transactions recorded</td></tr>
          <?php else: foreach($stock_txns as $tx):
            $typeMap=['restock'=>['success','fa-arrow-down'],'dispensed'=>['primary','fa-arrow-up'],'expired'=>['danger','fa-calendar-xmark'],'adjusted'=>['warning','fa-sliders'],'returned'=>['info','fa-undo'],'damaged'=>['danger','fa-circle-xmark']];
            $tInfo=$typeMap[$tx['transaction_type']]??['info','fa-circle'];
          ?>
          <tr>
            <td><?=date('d M Y, g:i A',strtotime($tx['transaction_date']))?></td>
            <td><strong><?=htmlspecialchars($tx['medicine_name'])?></strong></td>
            <td><span class="adm-badge adm-badge-<?=$tInfo[0]?>"><i class="fas <?=$tInfo[1]?>"></i> <?=ucfirst($tx['transaction_type'])?></span></td>
            <td style="font-weight:700;color:var(--<?=in_array($tx['transaction_type'],['restock','returned'])?'success':'danger'?>);"><?=in_array($tx['transaction_type'],['restock','returned'])?'+':'-'?><?=$tx['quantity']?></td>
            <td><?=$tx['previous_quantity']?></td>
            <td style="font-weight:600;"><?=$tx['new_quantity']?></td>
            <td><?=htmlspecialchars($tx['performed_by_name'])?></td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($tx['notes']??'—')?></td>
          </tr>
          <?php endforeach; endif;?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Purchase Orders -->
  <div id="stockSub-orders" class="stock-sub" style="display:none;">
    <div class="adm-card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;padding:0 .5rem;">
        <h3 style="font-size:1.4rem;font-weight:700;"><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:.5rem;"></i>Purchase Orders</h3>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table" id="poTable">
          <thead><tr><th>Order #</th><th>Supplier</th><th>Date</th><th>Expected</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($purchase_orders)):?><tr><td colspan="7" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No purchase orders yet</td></tr>
          <?php else: foreach($purchase_orders as $po):
            $poScMap=['draft'=>'info','sent'=>'warning','received'=>'success','partially_received'=>'primary','cancelled'=>'danger'];
            $poSc=$poScMap[$po['status']]??'info';
          ?>
          <tr>
            <td><code style="font-weight:600;"><?=htmlspecialchars($po['order_number'])?></code></td>
            <td><?=htmlspecialchars($po['supplier_name'])?></td>
            <td><?=date('d M Y',strtotime($po['order_date']))?></td>
            <td><?=$po['expected_delivery_date']?date('d M Y',strtotime($po['expected_delivery_date'])):'—'?></td>
            <td style="font-weight:600;">GH₵<?=number_format($po['total_amount'],2)?></td>
            <td><span class="adm-badge adm-badge-<?=$poSc?>"><?=ucwords(str_replace('_',' ',$po['status']))?></span></td>
            <td>
              <div class="action-btns">
                <?php if($po['status']==='draft'||$po['status']==='sent'):?>
                <button class="btn btn-success btn-sm" onclick="receiveOrder(<?=$po['id']?>)" title="Mark Received"><span class="btn-text"><i class="fas fa-check"></i></span></button>
                <?php endif;?>
                <?php if($po['status']==='draft'):?>
                <button class="btn btn-danger btn-sm" onclick="cancelPO(<?=$po['id']?>)" title="Cancel"><span class="btn-text"><i class="fas fa-times"></i></span></button>
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

  <!-- Suppliers -->
  <div id="stockSub-suppliers" class="stock-sub" style="display:none;">
    <div class="adm-card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;padding:0 .5rem;">
        <h3 style="font-size:1.4rem;font-weight:700;"><i class="fas fa-truck" style="color:var(--info);margin-right:.5rem;"></i>Suppliers</h3>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table" id="supTable">
          <thead><tr><th>Supplier</th><th>Contact</th><th>Phone</th><th>Email</th><th>Categories</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($suppliers)):?><tr><td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No suppliers</td></tr>
          <?php else: foreach($suppliers as $sp):?>
          <tr>
            <td><strong><?=htmlspecialchars($sp['supplier_name'])?></strong></td>
            <td><?=htmlspecialchars($sp['contact_person']??'—')?></td>
            <td><?=htmlspecialchars($sp['phone']??'—')?></td>
            <td><?=htmlspecialchars($sp['email']??'—')?></td>
            <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($sp['supply_categories']??'—')?></td>
            <td>
              <?php $rating=(float)($sp['rating']??0); for($s=1;$s<=5;$s++): ?>
              <i class="fas fa-star" style="color:<?=$s<=$rating?'var(--warning)':'var(--border)'?>;font-size:1rem;"></i>
              <?php endfor;?>
            </td>
            <td><span class="adm-badge adm-badge-<?=$sp['is_active']?'success':'danger'?>"><?=$sp['is_active']?'Active':'Inactive'?></span></td>
            <td>
              <div class="action-btns">
                <button class="btn btn-primary btn btn-sm" onclick="editSupplier(<?=$sp['supplier_id']?>)" style="background:var(--warning-light);color:var(--warning);border-color:var(--warning);"><span class="btn-text"><i class="fas fa-edit"></i></span></button>
                <button class="btn btn-primary btn btn-sm" onclick="toggleSupplier(<?=$sp['supplier_id']?>,<?=$sp['is_active']?>)" style="background:var(--<?=$sp['is_active']?'danger':'success'?>-light);color:var(--<?=$sp['is_active']?'danger':'success'?>);border-color:var(--<?=$sp['is_active']?'danger':'success'?>);"><span class="btn-text"><i class="fas fa-<?=$sp['is_active']?'ban':'check'?>"></i></span></button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif;?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ══ Receive Stock Modal ══ -->
<div class="modal-bg" id="modalAddStock">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-plus-circle" style="color:var(--success);"></i> Receive Stock</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalAddStock')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="submitReceiveStock(event)">
      <div class="form-group"><label>Medicine *</label>
        <select class="form-control" name="medicine_id" required>
          <option value="">Select medicine…</option>
          <?php foreach($medicines as $m):?><option value="<?=$m['id']?>"><?=htmlspecialchars($m['medicine_name'])?> (Current: <?=$m['stock_quantity']?>)</option><?php endforeach;?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Quantity *</label><input type="number" class="form-control" name="quantity" min="1" required></div>
        <div class="form-group"><label>Supplier</label>
          <select class="form-control" name="supplier_id">
            <option value="">Select…</option>
            <?php foreach($suppliers as $s):if($s['is_active']):?><option value="<?=$s['supplier_id']?>"><?=htmlspecialchars($s['supplier_name'])?></option><?php endif;endforeach;?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Batch Number</label><input class="form-control" name="batch_number"></div>
        <div class="form-group"><label>Expiry Date</label><input type="date" class="form-control" name="expiry_date"></div>
      </div>
      <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" rows="2" placeholder="Purchase order ref, invoice #, etc."></textarea></div>
      <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-plus"></i> Add Stock</span></button>
    </form>
  </div>
</div>

<!-- ══ Adjust Stock Modal ══ -->
<div class="modal-bg" id="modalAdjustStock">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-sliders" style="color:var(--warning);"></i> Adjust Stock</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalAdjustStock')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="submitAdjustStock(event)">
      <div class="form-group"><label>Medicine *</label>
        <select class="form-control" name="medicine_id" required>
          <option value="">Select medicine…</option>
          <?php foreach($medicines as $m):?><option value="<?=$m['id']?>"><?=htmlspecialchars($m['medicine_name'])?> (Current: <?=$m['stock_quantity']?>)</option><?php endforeach;?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Adjustment Type *</label>
          <select class="form-control" name="type" required>
            <option value="adjusted">Manual Adjustment</option>
            <option value="damaged">Damaged</option>
            <option value="returned">Returned to Supplier</option>
            <option value="expired">Expired Removal</option>
          </select>
        </div>
        <div class="form-group"><label>Quantity *</label><input type="number" class="form-control" name="quantity" min="1" required></div>
      </div>
      <div class="form-group"><label>Reason *</label><textarea class="form-control" name="notes" rows="2" required placeholder="Mandatory reason for adjustment"></textarea></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-save"></i> Save Adjustment</span></button>
    </form>
  </div>
</div>

<!-- ══ Create Purchase Order Modal ══ -->
<div class="modal-bg" id="modalCreatePO">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-file-invoice" style="color:var(--primary);"></i> New Purchase Order</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalCreatePO')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="submitPurchaseOrder(event)">
      <div class="form-row">
        <div class="form-group"><label>Supplier *</label>
          <select class="form-control" name="supplier_id" required>
            <option value="">Select supplier…</option>
            <?php foreach($suppliers as $s):if($s['is_active']):?><option value="<?=$s['supplier_id']?>"><?=htmlspecialchars($s['supplier_name'])?></option><?php endif;endforeach;?>
          </select>
        </div>
        <div class="form-group"><label>Expected Delivery</label><input type="date" class="form-control" name="expected_delivery_date"></div>
      </div>
      <div class="form-group"><label>Medicine *</label>
        <select class="form-control" name="medicine_id" required>
          <option value="">Select medicine…</option>
          <?php foreach($medicines as $m):?><option value="<?=$m['id']?>" data-price="<?=$m['unit_price']?>"><?=htmlspecialchars($m['medicine_name'])?></option><?php endforeach;?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Quantity *</label><input type="number" class="form-control" name="quantity" min="1" required></div>
        <div class="form-group"><label>Unit Price (GH₵)</label><input type="number" class="form-control" name="unit_price" step="0.01" min="0" required></div>
      </div>
      <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-paper-plane"></i> Create Order</span></button>
    </form>
  </div>
</div>

<!-- ══ Add Supplier Modal ══ -->
<div class="modal-bg" id="modalAddSupplier">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-truck" style="color:var(--info);"></i> Add Supplier</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalAddSupplier')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="submitAddSupplier(event)">
      <div class="form-group"><label>Supplier Name *</label><input class="form-control" name="supplier_name" required></div>
      <div class="form-row">
        <div class="form-group"><label>Contact Person</label><input class="form-control" name="contact_person"></div>
        <div class="form-group"><label>Phone</label><input class="form-control" name="phone"></div>
      </div>
      <div class="form-group"><label>Email</label><input type="email" class="form-control" name="email"></div>
      <div class="form-group"><label>Address</label><textarea class="form-control" name="address" rows="2"></textarea></div>
      <div class="form-row">
        <div class="form-group"><label>Supply Categories</label><input class="form-control" name="supply_categories" placeholder="Antibiotics, Analgesics…"></div>
        <div class="form-group"><label>Payment Terms</label><input class="form-control" name="payment_terms" value="Net 30"></div>
      </div>
      <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;"><span class="btn-text"><i class="fas fa-plus"></i> Add Supplier</span></button>
    </form>
  </div>
</div>

<style>.stock-sub{display:none;animation:fadeIn .3s ease;}.stock-sub.active{display:block;}</style>
<script>
function showStockSub(id,btn){
  document.querySelectorAll('.stock-sub').forEach(s=>{s.classList.remove('active');s.style.display='none';});
  const el=document.getElementById('stockSub-'+id);
  if(el){el.classList.add('active');el.style.display='block';}
  document.querySelectorAll('#stockSubTabs .ftab').forEach(b=>b.classList.remove('active'));
  if(btn)btn.classList.add('active');
}

async function submitReceiveStock(e){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target)); fd.action='receive_stock';
  const r=await pharmAction(fd);
  if(r.success){toast(r.message||'Stock added');closeModal('modalAddStock');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function submitAdjustStock(e){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target)); fd.action='adjust_stock';
  const r=await pharmAction(fd);
  if(r.success){toast(r.message||'Stock adjusted');closeModal('modalAdjustStock');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function submitPurchaseOrder(e){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target)); fd.action='create_purchase_order';
  const r=await pharmAction(fd);
  if(r.success){toast(r.message||'Purchase order created');closeModal('modalCreatePO');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function receiveOrder(poId){
  if(!confirm('Mark this order as received? Stock will be updated.')) return;
  const r=await pharmAction({action:'receive_order',order_id:poId});
  if(r.success){toast(r.message||'Order received');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function cancelPO(poId){
  if(!confirm('Cancel this purchase order?')) return;
  const r=await pharmAction({action:'cancel_order',order_id:poId});
  if(r.success){toast('Order cancelled');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function submitAddSupplier(e){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target)); fd.action='add_supplier';
  const r=await pharmAction(fd);
  if(r.success){toast(r.message||'Supplier added');closeModal('modalAddSupplier');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function editSupplier(id){
  // Placeholder — load supplier data same pattern as editMedicine
  toast('Edit supplier — coming soon','info');
}

async function toggleSupplier(id,current){
  if(!confirm(current?'Deactivate this supplier?':'Activate this supplier?')) return;
  const r=await pharmAction({action:'toggle_supplier',supplier_id:id,active:current?0:1});
  if(r.success){toast(r.message||'Updated');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}
</script>
