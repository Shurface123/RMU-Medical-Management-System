<?php // TAB: MEDICINE INVENTORY ?>
<div id="sec-medicine" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-pills"></i> Medicine Inventory</h2>
    <span style="font-size:1.2rem;color:var(--text-muted);"><i class="fas fa-eye"></i> View Only</span>
  </div>

  <?php if($stats['low_stock']>0):?>
  <div style="background:var(--danger-light);border-left:4px solid var(--danger);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem;font-size:1.3rem;">
    <i class="fas fa-triangle-exclamation" style="color:var(--danger);font-size:1.6rem;"></i>
    <div><strong style="color:var(--danger);"><?=$stats['low_stock']?> medicine(s)</strong> are low or out of stock — consider this when writing prescriptions.</div>
  </div>
  <?php endif;?>

  <div class="filter-tabs">
    <button class="ftab active" onclick="filterMed('all',this)">All</button>
    <button class="ftab" onclick="filterMed('In Stock',this)">In Stock</button>
    <button class="ftab" onclick="filterMed('Low Stock',this)">Low Stock</button>
    <button class="ftab" onclick="filterMed('Out of Stock',this)">Out of Stock</button>
    <button class="ftab" onclick="filterMed('Expiring Soon',this)">Expiring Soon</button>
  </div>

  <div style="margin-bottom:1.2rem;">
    <div class="adm-search-wrap"><i class="fas fa-search"></i>
      <input type="text" class="adm-search-input" id="medSearch" placeholder="Search medicine name or category…" oninput="filterTable('medSearch','medTable')">
    </div>
  </div>

  <div class="adm-card">
    <div class="adm-table-wrap">
      <table class="adm-table" id="medTable">
        <thead><tr><th>Medicine</th><th>Category</th><th>Stock Qty</th><th>Unit</th><th>Price (GH₵)</th><th>Expiry Date</th><th>Status</th></tr></thead>
        <tbody>
        <?php if(empty($medicines)):?>
          <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);">No medicines in inventory.</td></tr>
        <?php else: foreach($medicines as $med):
          $sc=match($med['stock_status']??''){
            'In Stock'=>'success','Out of Stock'=>'danger','Expiring Soon'=>'warning',default=>'warning'};
        ?>
        <tr data-medstatus="<?=htmlspecialchars($med['stock_status']??'')?>">
          <td>
            <strong><?=htmlspecialchars($med['medicine_name'])?></strong><br>
            <?php if($med['generic_name']):?><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($med['generic_name'])?></span><?php endif;?>
          </td>
          <td><?=htmlspecialchars($med['category']??'—')?></td>
          <td><strong style="font-size:1.6rem;"><?=$med['stock_quantity']?></strong><?php if($med['reorder_level']):?><span style="font-size:1rem;color:var(--text-muted);"> / <?=$med['reorder_level']?> reorder</span><?php endif;?></td>
          <td><?=htmlspecialchars($med['unit']??'tablet')?></td>
          <td>GH₵<?=number_format($med['unit_price'],2)?></td>
          <td><?=$med['expiry_date']?date('d M Y',strtotime($med['expiry_date'])):'<span style="color:var(--text-muted);">—</span>'?></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$med['stock_status']??'Unknown'?></span></td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
function filterMed(status,btn){
  document.querySelectorAll('.filter-tabs .ftab').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  document.querySelectorAll('#medTable tbody tr').forEach(row=>{
    row.style.display=(status==='all'||row.dataset.medstatus===status)?'':'none';
  });
}
</script>
