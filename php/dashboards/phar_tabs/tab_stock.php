<!-- ════════════════════════════════════════════════════════════
     MODULE 4: STOCK MANAGEMENT
     ════════════════════════════════════════════════════════════ -->
<div id="sec-stock" class="dash-section <?=($active_tab==='stock')?'active':''?>">

  <div class="sec-header">
    <div style="display:flex;align-items:center;gap:1.5rem;">
        <div style="width:50px;height:50px;border-radius:15px;background:var(--role-accent-light);color:var(--role-accent);display:flex;align-items:center;justify-content:center;font-size:1.8rem;">
            <i class="fas fa-boxes-stacked"></i>
        </div>
        <div>
            <h2 style="margin:0;font-size:2rem;font-weight:700;">Stock Management</h2>
            <p style="margin:.3rem 0 0;color:var(--text-muted);font-size:1.1rem;">Manage inventory transactions and suppliers</p>
        </div>
    </div>
    <div style="display:flex;gap:.8rem;flex-wrap:wrap;">
      <button class="btn btn-success btn-sm" onclick="openModal('modalAddStock')" style="padding: .8rem 1.2rem; border-radius: 8px; box-shadow: 0 4px 15px rgba(var(--success-rgb), 0.2);"><span class="btn-text"><i class="fas fa-plus"></i> Receive Stock</span></button>
      <button class="btn btn-primary btn btn-sm" onclick="openModal('modalAdjustStock')" style="background:var(--warning-light);color:var(--warning);border-color:var(--warning);padding: .8rem 1.2rem; border-radius: 8px;"><span class="btn-text"><i class="fas fa-sliders"></i> Adjust Stock</span></button>
      <button class="btn btn-primary btn-sm" onclick="openModal('modalCreatePO')" style="background:var(--primary);color:#fff;border-color:var(--primary);padding: .8rem 1.2rem; border-radius: 8px;"><span class="btn-text"><i class="fas fa-file-invoice"></i> New PO</span></button>
      <button class="btn btn-primary btn btn-sm" onclick="openModal('modalAddSupplier')" style="background:var(--info-light);color:var(--info);border-color:var(--info);padding: .8rem 1.2rem; border-radius: 8px;"><span class="btn-text"><i class="fas fa-truck"></i> Add Supplier</span></button>
    </div>
  </div>

  <!-- Sub-tabs within Stock Management -->
  <div class="filter-tabs" id="stockSubTabs" style="background:var(--bg-card);padding:.5rem;border-radius:12px;display:inline-flex;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
    <button class="btn btn-primary ftab active" onclick="showStockSub('transactions',this)" style="border-radius:8px;"><span class="btn-text">Transaction History</span></button>
    <button class="btn btn-warning btn-icon ftab" onclick="showStockSub('orders',this)" style="border-radius:8px;background:transparent;color:var(--text-color);border:none;"><span class="btn-text">Orders (<?=$stats['pending_orders']?>)</span></button>
    <button class="btn btn-primary ftab" onclick="showStockSub('suppliers',this)" style="border-radius:8px;background:transparent;color:var(--text-color);border:none;"><span class="btn-text">Suppliers (<?=count($suppliers)?>)</span></button>
  </div>

  <!-- Stock Transactions -->
  <div id="stockSub-transactions" class="stock-sub active">
    <div class="adm-card" style="box-shadow: 0 8px 30px rgba(0,0,0,0.04); border: 1px solid var(--border);">
      <div style="display:flex;justify-content:space-between;align-items:center;padding:1.5rem;">
        <h3 style="font-size:1.4rem;font-weight:700;margin:0;"><i class="fas fa-exchange-alt" style="color:var(--role-accent);margin-right:.8rem;"></i>Stock Transactions</h3>
        <div class="adm-search-wrap" style="width:250px;border-radius:20px;background:var(--bg-main);border:1px solid var(--border);"><i class="fas fa-search" style="color:var(--text-muted);left:12px;"></i><input type="text" class="adm-search-input" id="txSearch" placeholder="Search transactions…" oninput="filterTable('txSearch','txTable')" style="padding-left:35px;background:transparent;border:none;"></div>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table" id="txTable" style="margin:0;">
          <thead style="background:var(--bg-main);"><tr><th>Date & Time</th><th>Medicine</th><th>Type</th><th>Change</th><th>Balance</th><th>Performed By</th><th>Notes</th></tr></thead>
          <tbody>
          <?php if(empty($stock_txns)):?><tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-file-invoice" style="font-size:2rem;margin-bottom:1rem;display:block;"></i>No transactions recorded</td></tr>
          <?php else: foreach($stock_txns as $tx):
            $typeMap=['restock'=>['success','fa-arrow-down'],'dispensed'=>['primary','fa-arrow-up'],'expired'=>['danger','fa-calendar-xmark'],'adjusted'=>['warning','fa-sliders'],'returned'=>['info','fa-undo'],'damaged'=>['danger','fa-circle-xmark']];
            $tInfo=$typeMap[$tx['transaction_type']]??['info','fa-circle'];
            $isPos = in_array($tx['transaction_type'],['restock','returned']);
          ?>
          <tr style="transition:all .2s;" onmouseover="this.style.background='var(--bg-main)'" onmouseout="this.style.background='transparent'">
            <td data-label="Date & Time">
                <div style="font-weight:600;"><?=date('d M Y',strtotime($tx['transaction_date']))?></div>
                <div style="color:var(--text-muted);font-size:1.1rem;"><?=date('g:i A',strtotime($tx['transaction_date']))?></div>
            </td>
            <td data-label="Medicine"><strong><?=htmlspecialchars($tx['medicine_name'])?></strong></td>
            <td data-label="Type"><span class="adm-badge adm-badge-<?=$tInfo[0]?>" style="padding:.4rem .8rem;"><i class="fas <?=$tInfo[1]?>" style="margin-right:.4rem;"></i> <?=ucfirst($tx['transaction_type'])?></span></td>
            <td data-label="Change">
                <span class="adm-badge adm-badge-<?=$isPos?'success':'danger'?>" style="font-weight:700;padding:.3rem .6rem;background:var(--<?=$isPos?'success':'danger'?>-light);color:var(--<?=$isPos?'success':'danger'?>);">
                    <?=$isPos?'+':'-'?><?=$tx['quantity']?>
                </span>
            </td>
            <td data-label="Balance">
                <div style="display:flex;align-items:center;gap:.5rem;">
                    <span style="color:var(--text-muted);font-size:1.1rem;text-decoration:line-through;"><?=$tx['previous_quantity']?></span>
                    <i class="fas fa-arrow-right" style="color:var(--border);font-size:1rem;"></i>
                    <strong style="font-size:1.2rem;"><?=$tx['new_quantity']?></strong>
                </div>
            </td>
            <td data-label="Performed By">
              <div style="display:flex;align-items:center;gap:.6rem;">
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--info));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;box-shadow:0 2px 4px rgba(0,0,0,0.2);">
                  <?=strtoupper(substr($tx['performed_by_name'],0,1))?>
                </div>
                <span><?=htmlspecialchars($tx['performed_by_name'])?></span>
              </div>
            </td>
            <td data-label="Notes" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;color:var(--text-muted);"><?=htmlspecialchars($tx['notes']??'—')?></td>
          </tr>
          <?php endforeach; endif;?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Purchase Orders -->
  <div id="stockSub-orders" class="stock-sub" style="display:none;">
    <div class="adm-card" style="box-shadow: 0 8px 30px rgba(0,0,0,0.04); border: 1px solid var(--border);">
      <div style="display:flex;justify-content:space-between;align-items:center;padding:1.5rem;">
        <h3 style="font-size:1.4rem;font-weight:700;margin:0;"><i class="fas fa-file-invoice" style="color:var(--warning);margin-right:.8rem;"></i>Purchase Orders</h3>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table" id="poTable" style="margin:0;">
          <thead style="background:var(--bg-main);"><tr><th>Order #</th><th>Supplier</th><th>Date / Expected</th><th>Total Amnt</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($purchase_orders)):?><tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-receipt" style="font-size:2rem;margin-bottom:1rem;display:block;"></i>No purchase orders yet</td></tr>
          <?php else: foreach($purchase_orders as $po):
            $poScMap=['draft'=>'info','sent'=>'warning','received'=>'success','partially_received'=>'primary','cancelled'=>'danger'];
            $poSc=$poScMap[$po['status']]??'info';
          ?>
          <tr style="transition:all .2s;" onmouseover="this.style.background='var(--bg-main)'" onmouseout="this.style.background='transparent'">
            <td data-label="Order #"><code style="background:var(--info-light);color:var(--info);padding:.3rem .6rem;border-radius:4px;font-weight:700;"><?=htmlspecialchars($po['order_number'])?></code></td>
            <td data-label="Supplier">
                <div style="display:flex;align-items:center;gap:.6rem;">
                    <div style="width:32px;height:32px;border-radius:8px;background:var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted);"><i class="fas fa-building"></i></div>
                    <strong><?=htmlspecialchars($po['supplier_name'])?></strong>
                </div>
            </td>
            <td data-label="Date Expected">
                <div><span style="color:var(--text-muted);font-size:1.1rem;">Ordered:</span> <?=date('d M Y',strtotime($po['order_date']))?></div>
                <div><span style="color:var(--text-muted);font-size:1.1rem;">Expected:</span> <?=$po['expected_delivery_date']?date('d M Y',strtotime($po['expected_delivery_date'])):'—'?></div>
            </td>
            <td data-label="Total Amount" style="font-weight:700;font-size:1.3rem;">GH₵<?=number_format($po['total_amount'],2)?></td>
            <td data-label="Status"><span class="adm-badge adm-badge-<?=$poSc?>" style="padding:.4rem .8rem;"><?=ucwords(str_replace('_',' ',$po['status']))?></span></td>
            <td data-label="Actions">
              <div class="action-btns">
                <button class="btn-icon btn btn-primary btn-sm" onclick="viewPO(<?=$po['id']?>)" style="background:var(--primary-light);color:var(--primary);" title="View Details"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
                <?php if($po['status']==='draft'||$po['status']==='sent'):?>
                <button class="btn-icon btn btn-success btn-sm" onclick="receiveOrder(<?=$po['id']?>)" style="background:var(--success-light);color:var(--success);" title="Mark Received"><span class="btn-text"><i class="fas fa-check"></i></span></button>
                <?php endif;?>
                <?php if($po['status']==='draft'):?>
                <button class="btn-icon btn btn-danger btn-sm" onclick="cancelPO(<?=$po['id']?>)" style="background:var(--danger-light);color:var(--danger);" title="Cancel"><span class="btn-text"><i class="fas fa-times"></i></span></button>
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

  <!-- Suppliers (Card Grid Layout) -->
  <div id="stockSub-suppliers" class="stock-sub" style="display:none;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <h3 style="font-size:1.4rem;font-weight:700;margin:0;"><i class="fas fa-truck" style="color:var(--info);margin-right:.8rem;"></i>Supplier Directory</h3>
        <div class="adm-search-wrap" style="width:250px;border-radius:20px;background:#fff;border:1px solid var(--border);box-shadow:0 2px 8px rgba(0,0,0,0.02);"><i class="fas fa-search" style="color:var(--text-muted);left:12px;"></i><input type="text" class="adm-search-input" id="supSearch" placeholder="Search suppliers…" oninput="filterCards('supSearch','supGrid','.sup-card')" style="padding-left:35px;background:transparent;border:none;"></div>
    </div>
    
    <div id="supGrid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:1.5rem;">
        <?php if(empty($suppliers)):?>
            <div style="grid-column:1/-1;text-align:center;padding:4rem;background:var(--bg-card);border-radius:12px;border:1px solid var(--border);">
                <i class="fas fa-store-slash" style="font-size:3rem;color:var(--text-muted);margin-bottom:1rem;display:block;"></i>
                <p style="color:var(--text-muted);font-size:1.3rem;">No suppliers found</p>
            </div>
        <?php else: foreach($suppliers as $sp):
            $initial = strtoupper(substr($sp['supplier_name'],0,1));
        ?>
        <div class="sup-card" data-search="<?=strtolower($sp['supplier_name'].' '.$sp['contact_person'].' '.$sp['supply_categories'])?>" style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;box-shadow:0 4px 15px rgba(0,0,0,0.03);position:relative;display:flex;flex-direction:column;">
            <?php if(!$sp['is_active']):?>
            <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.6);z-index:1;border-radius:12px;pointer-events:none;"></div>
            <?php endif;?>
            
            <div style="position:absolute;top:1rem;right:1rem;z-index:2;">
                <button class="btn-icon" data-dropdown="supDrop_<?=$sp['supplier_id']?>" style="background:transparent;color:var(--text-muted);border:none;font-size:1.4rem;cursor:pointer;"><i class="fas fa-ellipsis-v"></i></button>
                <div id="supDrop_<?=$sp['supplier_id']?>" class="dropdown-menu" style="display:none;position:absolute;right:0;top:100%;background:#fff;border:1px solid var(--border);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);min-width:140px;z-index:10;">
                    <a href="#" onclick="editSupplier(<?=htmlspecialchars(json_encode($sp))?>);return false;" style="display:block;padding:.8rem 1rem;color:var(--text-color);text-decoration:none;border-bottom:1px solid var(--border);"><i class="fas fa-edit" style="width:20px;color:var(--warning);"></i> Edit</a>
                    <a href="#" onclick="toggleSupplier(<?=$sp['supplier_id']?>,<?=$sp['is_active']?>);return false;" style="display:block;padding:.8rem 1rem;color:var(--<?=$sp['is_active']?'danger':'success'?>);text-decoration:none;"><i class="fas fa-<?=$sp['is_active']?'ban':'check'?>" style="width:20px;"></i> <?=$sp['is_active']?'Deactivate':'Activate'?></a>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:1.2rem;margin-bottom:1.2rem;z-index:2;">
                <div style="width:50px;height:50px;border-radius:12px;background:var(--info-light);color:var(--info);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700;flex-shrink:0;">
                    <?=$initial?>
                </div>
                <div>
                    <h4 style="margin:0;font-size:1.4rem;font-weight:700;color:var(--text-color);line-height:1.2;"><?=htmlspecialchars($sp['supplier_name'])?></h4>
                    <div style="margin-top:.4rem;">
                        <?php $rating=(float)($sp['rating']??0); for($s=1;$s<=5;$s++): ?>
                        <i class="fas fa-star" style="color:<?=$s<=$rating?'var(--warning)':'var(--border)'?>;font-size:1rem;"></i>
                        <?php endfor;?> <span style="color:var(--text-muted);font-size:1.1rem;margin-left:.3rem;"><?=$rating==0?'New':$rating?></span>
                    </div>
                </div>
            </div>

            <div style="flex:1;z-index:2;background:var(--bg-main);border-radius:8px;padding:1rem;margin-bottom:1rem;">
                <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.6rem;color:var(--text-secondary);font-size:1.2rem;">
                    <i class="fas fa-user" style="width:16px;text-align:center;color:var(--text-muted);"></i> <?=htmlspecialchars($sp['contact_person']??'N/A')?>
                </div>
                <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.6rem;color:var(--text-secondary);font-size:1.2rem;">
                    <i class="fas fa-phone" style="width:16px;text-align:center;color:var(--text-muted);"></i> <?=htmlspecialchars($sp['phone']??'N/A')?>
                </div>
                <div style="display:flex;align-items:center;gap:.8rem;color:var(--text-secondary);font-size:1.2rem;">
                    <i class="fas fa-envelope" style="width:16px;text-align:center;color:var(--text-muted);"></i> <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($sp['email']??'N/A')?></span>
                </div>
            </div>

            <div style="z-index:2;">
                <div style="font-size:1.1rem;color:var(--text-muted);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:1px;font-weight:600;">Categories</div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                    <?php 
                        $cats = array_filter(array_map('trim', explode(',', $sp['supply_categories']??'')));
                        if(empty($cats)) echo '<span class="adm-badge adm-badge-info" style="font-weight:500;">General</span>';
                        else foreach(array_slice($cats,0,3) as $c) echo '<span class="adm-badge adm-badge-primary" style="font-weight:500;">'.htmlspecialchars($c).'</span>';
                        if(count($cats)>3) echo '<span class="adm-badge adm-badge-outline" style="font-weight:500;">+'.(count($cats)-3).'</span>';
                    ?>
                </div>
            </div>
            
            <?php if(!$sp['is_active']):?>
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:3;background:var(--danger);color:#fff;padding:.4rem 1rem;border-radius:20px;font-weight:700;font-size:1.1rem;box-shadow:0 4px 10px rgba(var(--danger-rgb),0.3);"><i class="fas fa-ban"></i> INACTIVE</div>
            <?php endif;?>
        </div>
        <?php endforeach; endif;?>
    </div>
  </div>
</div>

<!-- ══ Receive Stock Modal ══ -->
<div class="modal-bg glass-panel" id="modalAddStock">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-plus-circle" style="color:var(--success);"></i> Receive Stock</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalAddStock')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="submitReceiveStock(event)" style="padding:1rem .5rem;">
      <div class="form-group"><label>Medicine <span class="req">*</span></label>
        <select class="form-control" name="medicine_id" required>
          <option value="">Select medicine…</option>
          <?php foreach($medicines as $m):?><option value="<?=$m['id']?>"><?=htmlspecialchars($m['medicine_name'])?> (Current: <?=$m['stock_quantity']?>)</option><?php endforeach;?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Quantity <span class="req">*</span></label><input type="number" class="form-control" name="quantity" min="1" required></div>
        <div class="form-group"><label>Supplier</label>
          <select class="form-control" name="supplier_id">
            <option value="">Select…</option>
            <?php foreach($suppliers as $s):if($s['is_active']):?><option value="<?=$s['supplier_id']?>"><?=htmlspecialchars($s['supplier_name'])?></option><?php endif;endforeach;?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Batch Number</label><input class="form-control" name="batch_number" placeholder="Optional"></div>
        <div class="form-group"><label>Expiry Date</label><input type="date" class="form-control" name="expiry_date"></div>
      </div>
      <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" rows="2" placeholder="Purchase order ref, invoice #, etc."></textarea></div>
      <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;padding:1rem;font-size:1.3rem;margin-top:1rem;"><span class="btn-text"><i class="fas fa-plus"></i> Add Stock</span></button>
    </form>
  </div>
</div>

<!-- ══ Adjust Stock Modal ══ -->
<div class="modal-bg glass-panel" id="modalAdjustStock">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-sliders" style="color:var(--warning);"></i> Adjust Stock</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalAdjustStock')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="submitAdjustStock(event)" style="padding:1rem .5rem;">
      <div class="form-group"><label>Medicine <span class="req">*</span></label>
        <select class="form-control" name="medicine_id" required>
          <option value="">Select medicine…</option>
          <?php foreach($medicines as $m):?><option value="<?=$m['id']?>"><?=htmlspecialchars($m['medicine_name'])?> (Current: <?=$m['stock_quantity']?>)</option><?php endforeach;?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Adjustment Type <span class="req">*</span></label>
          <select class="form-control" name="type" required>
            <option value="adjusted">Manual Adjustment (+/-)</option>
            <option value="damaged">Damaged (-)</option>
            <option value="returned">Returned to Supplier (-)</option>
            <option value="expired">Expired Removal (-)</option>
          </select>
        </div>
        <div class="form-group"><label>Quantity <span class="req">*</span></label><input type="number" class="form-control" name="quantity" min="1" required placeholder="Positive number"></div>
      </div>
      <div class="alert alert-warning" style="background:var(--warning-light);color:var(--warning);border:1px solid var(--warning);padding:1rem;border-radius:8px;margin-bottom:1.5rem;display:flex;align-items:flex-start;gap:.8rem;">
        <i class="fas fa-info-circle" style="margin-top:.2rem;"></i>
        <span style="font-size:1.1rem;line-height:1.4;">Quantity will be deducted for Damaged, Returned, and Expired. For 'Manual Adjustment', specify actual change if implementing custom logic, otherwise treated as strict subtract. (Default is subtraction for safety).</span>
      </div>
      <div class="form-group"><label>Reason <span class="req">*</span></label><textarea class="form-control" name="notes" rows="2" required placeholder="Mandatory reason for adjustment"></textarea></div>
      <button type="submit" class="btn btn-primary" style="background:var(--warning);border-color:var(--warning);width:100%;justify-content:center;padding:1rem;font-size:1.3rem;margin-top:1rem;"><span class="btn-text"><i class="fas fa-save"></i> Save Adjustment</span></button>
    </form>
  </div>
</div>

<!-- ══ Create PO Modal ══ -->
<div class="modal-bg glass-panel" id="modalCreatePO">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-file-invoice" style="color:var(--primary);"></i> New Purchase Order</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalCreatePO')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="submitPurchaseOrder(event)" style="padding:1rem .5rem;">
      <div class="form-row">
        <div class="form-group"><label>Supplier <span class="req">*</span></label>
          <select class="form-control" name="supplier_id" required>
            <option value="">Select supplier…</option>
            <?php foreach($suppliers as $s):if($s['is_active']):?><option value="<?=$s['supplier_id']?>"><?=htmlspecialchars($s['supplier_name'])?></option><?php endif;endforeach;?>
          </select>
        </div>
        <div class="form-group"><label>Expected Delivery</label><input type="date" class="form-control" name="expected_delivery_date"></div>
      </div>
      <div class="form-group"><label>Medicine <span class="req">*</span></label>
        <select class="form-control" name="medicine_id" required onchange="poMedChange(this)">
          <option value="">Select medicine…</option>
          <?php foreach($medicines as $m):?><option value="<?=$m['id']?>" data-price="<?=$m['unit_price']?>"><?=htmlspecialchars($m['medicine_name'])?></option><?php endforeach;?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Quantity <span class="req">*</span></label><input type="number" class="form-control" id="poQty" name="quantity" min="1" required oninput="poCalcTotal()"></div>
        <div class="form-group"><label>Unit Cost Price (GH₵) <span class="req">*</span></label><input type="number" class="form-control" id="poPrice" name="unit_price" step="0.01" min="0" required oninput="poCalcTotal()"></div>
      </div>
      <div style="background:var(--bg-main);padding:1.5rem;border-radius:8px;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:1.2rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Estimated Total</span>
        <span style="font-size:1.8rem;font-weight:700;color:var(--primary);">GH₵ <span id="poTotalAmt">0.00</span></span>
      </div>
      <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:1rem;font-size:1.3rem;"><span class="btn-text"><i class="fas fa-paper-plane"></i> Submit Order</span></button>
    </form>
  </div>
</div>

<!-- ══ Edit Supplier Modal ══ -->
<div class="modal-bg glass-panel" id="modalEditSupplier">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-truck" style="color:var(--warning);"></i> Edit Supplier</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalEditSupplier')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="submitEditSupplier(event)" style="padding:1rem .5rem;">
      <input type="hidden" name="supplier_id" id="esup_id">
      <div class="form-group"><label>Supplier Name <span class="req">*</span></label><input class="form-control" name="supplier_name" id="esup_name" required></div>
      <div class="form-row">
        <div class="form-group"><label>Contact Person</label><input class="form-control" name="contact_person" id="esup_person"></div>
        <div class="form-group"><label>Phone</label><input class="form-control" name="phone" id="esup_phone"></div>
      </div>
      <div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" id="esup_email"></div>
      <div class="form-group"><label>Address</label><textarea class="form-control" name="address" id="esup_address" rows="2"></textarea></div>
      <div class="form-row">
        <div class="form-group"><label>Supply Categories</label><input class="form-control" name="supply_categories" id="esup_cats" placeholder="Antibiotics, Analgesics…"></div>
        <div class="form-group"><label>Payment Terms</label><input class="form-control" name="payment_terms" id="esup_terms"></div>
      </div>
      <div class="form-group"><label>Rating (1-5)</label><input type="number" step="0.1" min="1" max="5" class="form-control" name="rating" id="esup_rating"></div>
      <button type="submit" class="btn btn-primary" style="background:var(--warning);border-color:var(--warning);width:100%;justify-content:center;padding:1rem;font-size:1.3rem;margin-top:1rem;"><span class="btn-text"><i class="fas fa-save"></i> Save Changes</span></button>
    </form>
  </div>
</div>

<!-- ══ Add Supplier Modal ══ -->
<div class="modal-bg glass-panel" id="modalAddSupplier">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-truck" style="color:var(--info);"></i> Add Supplier</h3><button class="btn btn-primary modal-close" onclick="closeModal('modalAddSupplier')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="submitAddSupplier(event)" style="padding:1rem .5rem;">
      <div class="form-group"><label>Supplier Name <span class="req">*</span></label><input class="form-control" name="supplier_name" required></div>
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
      <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;padding:1rem;font-size:1.3rem;margin-top:1rem;"><span class="btn-text"><i class="fas fa-plus"></i> Add Supplier</span></button>
    </form>
  </div>
</div>

<style>
.stock-sub{display:none;animation:fadeIn .3s ease;}
.stock-sub.active{display:block;}
.filter-tabs .btn { transition: all .2s; }
.filter-tabs .btn:not(.active) { color: var(--text-muted); }
.filter-tabs .btn.active { background:var(--primary); color:#fff; box-shadow: 0 4px 12px rgba(var(--primary-rgb),0.3); }
.req{color:var(--danger);}
</style>
<script>
function showStockSub(id,btn){
  document.querySelectorAll('.stock-sub').forEach(s=>{s.classList.remove('active');s.style.display='none';});
  const el=document.getElementById('stockSub-'+id);
  if(el){el.classList.add('active');el.style.display='block';}
  document.querySelectorAll('#stockSubTabs .ftab').forEach(b=>{
      b.classList.remove('active');
      b.style.background='transparent';
      b.style.color='var(--text-color)';
      b.style.boxShadow='none';
  });
  if(btn){
      btn.classList.add('active');
      btn.style.background='var(--primary)';
      btn.style.color='#fff';
      btn.style.boxShadow='0 4px 12px rgba(var(--primary-rgb),0.3)';
  }
}

function filterCards(inputId, gridId, cardSelector) {
    const query = document.getElementById(inputId).value.toLowerCase();
    const cards = document.querySelectorAll(`#${gridId} ${cardSelector}`);
    cards.forEach(card => {
        const text = card.dataset.search || '';
        card.style.display = text.includes(query) ? 'flex' : 'none';
    });
}

function poMedChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    if(opt && opt.dataset.price) {
        // usually PO cost is lower than selling price, but we default to selling price as a start
        const price = (parseFloat(opt.dataset.price) * 0.7).toFixed(2); 
        document.getElementById('poPrice').value = price;
        poCalcTotal();
    }
}
function poCalcTotal() {
    const q = parseFloat(document.getElementById('poQty').value)||0;
    const p = parseFloat(document.getElementById('poPrice').value)||0;
    document.getElementById('poTotalAmt').textContent = (q*p).toFixed(2);
}

// Dropdown click outside handling
document.addEventListener('click', e => {
    if(!e.target.closest('[data-dropdown]')) {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display='none');
    }
});
document.querySelectorAll('[data-dropdown]').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        const drop = document.getElementById(btn.dataset.dropdown);
        const isOpen = drop.style.display === 'block';
        document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display='none');
        if(!isOpen) drop.style.display = 'block';
    });
});

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

async function viewPO(poId){
    toast('View PO details feature implementation pending', 'info');
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

function editSupplier(sp){
  document.getElementById('esup_id').value = sp.supplier_id;
  document.getElementById('esup_name').value = sp.supplier_name || '';
  document.getElementById('esup_person').value = sp.contact_person || '';
  document.getElementById('esup_phone').value = sp.phone || '';
  document.getElementById('esup_email').value = sp.email || '';
  document.getElementById('esup_address').value = sp.address || '';
  document.getElementById('esup_cats').value = sp.supply_categories || '';
  document.getElementById('esup_terms').value = sp.payment_terms || '';
  document.getElementById('esup_rating').value = sp.rating || '';
  openModal('modalEditSupplier');
}

async function submitEditSupplier(e){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target)); fd.action='update_supplier';
  const r=await pharmAction(fd);
  if(r.success){toast(r.message||'Supplier updated');closeModal('modalEditSupplier');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function toggleSupplier(id,current){
  if(!confirm(current?'Deactivate this supplier?':'Activate this supplier?')) return;
  const r=await pharmAction({action:'toggle_supplier',supplier_id:id,active:current?0:1});
  if(r.success){toast(r.message||'Updated');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}
</script>
