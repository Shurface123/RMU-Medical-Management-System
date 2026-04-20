<?php // TAB: MEDICINE INVENTORY ?>
<div id="sec-medicine" class="dash-section">

<style>
.adm-tab-group { display:flex; gap:.8rem; flex-wrap:wrap; margin-bottom:1.8rem; padding-bottom:1rem; border-bottom:1px solid var(--border); }
.ftab-v2 { 
  display:inline-flex;align-items:center;gap:.6rem;padding:.55rem 1.4rem;border-radius:20px;
  font-size:1.2rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);
  background:var(--surface);color:var(--text-secondary);transition:all 0.3s ease;
}
.ftab-v2:hover { background:var(--primary-light);color:var(--primary);border-color:var(--primary);transform:translateY(-1px); }
.ftab-v2.active { background:var(--primary);color:#fff;border-color:var(--primary);box-shadow:0 4px 12px rgba(47,128,237,.25); }
</style>

  <div class="sec-header">
    <h2><i class="fas fa-pills" style="color:var(--primary);"></i> Medicine Inventory <span style="font-size:1.2rem;color:var(--text-muted);font-weight:normal;margin-left:1rem;"><i class="fas fa-eye"></i> View Only</span></h2>
  </div>

  <?php if($stats['low_stock']>0):?>
  <div style="background:var(--danger-light);border-left:4px solid var(--danger);border-radius:0 10px 10px 0;padding:1.4rem 1.8rem;margin-bottom:2rem;display:flex;align-items:center;gap:1.2rem;font-size:1.3rem;">
    <i class="fas fa-triangle-exclamation" style="color:var(--danger);font-size:1.8rem;"></i>
    <div><strong style="color:var(--danger);"><?=$stats['low_stock']?> medicine(s)</strong> are low or out of stock — consider this when writing prescriptions.</div>
  </div>
  <?php endif;?>

  <div class="adm-tab-group">
    <button class="ftab-v2 active" onclick="filterMed('all',this)"><i class="fas fa-list"></i> All</button>
    <button class="ftab-v2" onclick="filterMed('In Stock',this)"><i class="fas fa-check-circle" style="color:var(--success);"></i> In Stock</button>
    <button class="ftab-v2" onclick="filterMed('Low Stock',this)"><i class="fas fa-triangle-exclamation" style="color:var(--warning);"></i> Low Stock</button>
    <button class="ftab-v2" onclick="filterMed('Out of Stock',this)"><i class="fas fa-circle-xmark" style="color:var(--danger);"></i> Out of Stock</button>
    <button class="ftab-v2" onclick="filterMed('Expiring Soon',this)"><i class="fas fa-clock" style="color:var(--warning);"></i> Expiring Soon</button>
  </div>



  <div class="adm-card shadow-sm" style="overflow:hidden;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="medTable">
        <thead><tr style="background:linear-gradient(90deg, var(--surface-2), var(--surface));"><th>Medicine</th><th>Category</th><th>Stock Qty</th><th>Unit</th><th>Price (GH₵)</th><th>Expiry Date</th><th>Status</th></tr></thead>
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
function filterMed(status, btn) {
    document.querySelectorAll('#sec-medicine .ftab-v2').forEach(b=>b.classList.remove('active'));
    if(btn) btn.classList.add('active');
    
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#medTable')) {
        const dt = $('#medTable').DataTable();
        if(status === 'all') { dt.column(6).search('').draw(); }
        else { dt.column(6).search(status, true, false).draw(); }
    } else {
        document.querySelectorAll('#medTable tbody tr').forEach(row=>{
            row.style.display=(status==='all'||row.dataset.medstatus===status)?'':'none';
        });
    }
}

$(document).ready(function() {
    if($.fn.DataTable) {
        $('#medTable').DataTable({
            pageLength: 10,
            language: { search: "", searchPlaceholder: "Quick search medicines..." }
        });
    }
});
</script>
