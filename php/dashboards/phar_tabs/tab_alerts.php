<!-- ════════════════════════════════════════════════════════════
     MODULE 5: LOW STOCK & EXPIRY ALERTS
     ════════════════════════════════════════════════════════════ -->
<div id="sec-alerts" class="dash-section <?=($active_tab==='alerts')?'active':''?>">

  <div class="sec-header">
    <h2><i class="fas fa-triangle-exclamation"></i> Stock Alerts</h2>
    <span class="adm-badge adm-badge-danger" style="font-size:1.2rem;"><?=$stats['active_alerts']?> active</span>
  </div>

  <div class="filter-tabs">
    <button class="ftab active" onclick="filterByAttr('all','alertsList',this)">All (<?=$stats['active_alerts']?>)</button>
    <button class="ftab" onclick="filterByAttr('low_stock','alertsList',this)">Low Stock</button>
    <button class="ftab" onclick="filterByAttr('out_of_stock','alertsList',this)">Out of Stock</button>
    <button class="ftab" onclick="filterByAttr('expiring_soon','alertsList',this)">Expiring Soon</button>
    <button class="ftab" onclick="filterByAttr('expired','alertsList',this)">Expired</button>
  </div>

  <div id="alertsList">
    <?php if(empty($alerts)):?>
    <div class="adm-card" style="text-align:center;padding:3rem;"><i class="fas fa-check-circle" style="font-size:3rem;color:var(--success);display:block;margin-bottom:1rem;"></i><p style="color:var(--text-muted);font-size:1.4rem;">No active alerts — inventory looks healthy!</p></div>
    <?php else: foreach($alerts as $al):
      $alMap=['low_stock'=>['Low Stock','orange','fa-exclamation-triangle','Restock immediately'],'out_of_stock'=>['Out of Stock','red','fa-times-circle','Critical — zero units remaining'],'expiring_soon'=>['Expiring Soon','orange','fa-clock','Check expiry and rotate stock'],'expired'=>['Expired','red','fa-calendar-xmark','Remove from inventory']];
      $aInfo=$alMap[$al['alert_type']]??['Alert','blue','fa-bell','Review'];
    ?>
    <div class="alert-card" data-status="<?=$al['alert_type']?>">
      <div class="alert-icon <?=$aInfo[1]?>"><i class="fas <?=$aInfo[2]?>"></i></div>
      <div style="flex:1;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;">
          <div>
            <span class="adm-badge adm-badge-<?=$aInfo[1]==='red'?'danger':'warning'?>"><?=$aInfo[0]?></span>
            <h4 style="font-size:1.4rem;font-weight:700;margin:.4rem 0 .2rem;"><?=htmlspecialchars($al['medicine_name'])?></h4>
          </div>
          <button class="adm-btn adm-btn-success adm-btn-sm" onclick="resolveAlert(<?=$al['id']?>)"><i class="fas fa-check"></i> Resolve</button>
        </div>
        <div style="display:flex;gap:2rem;flex-wrap:wrap;color:var(--text-secondary);font-size:1.2rem;margin-top:.5rem;">
          <span><i class="fas fa-box" style="margin-right:.3rem;"></i>Current: <strong style="color:var(--<?=$al['stock_quantity']<=($al['reorder_level']??10)?'danger':'text-primary'?>);"><?=$al['stock_quantity']?></strong></span>
          <span><i class="fas fa-level-down-alt" style="margin-right:.3rem;"></i>Reorder at: <?=$al['reorder_level']??10?></span>
          <?php if($al['expiry_date']):?><span><i class="fas fa-calendar" style="margin-right:.3rem;"></i>Expires: <?=date('d M Y',strtotime($al['expiry_date']))?></span><?php endif;?>
          <span><i class="fas fa-truck" style="margin-right:.3rem;"></i><?=htmlspecialchars($al['supplier_name']??'No supplier')?></span>
        </div>
        <div style="font-size:1.1rem;color:var(--text-muted);margin-top:.4rem;"><i class="fas fa-lightbulb" style="margin-right:.3rem;color:var(--warning);"></i><?=$aInfo[3]?></div>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>
</div>

<script>
async function resolveAlert(id){
  if(!confirm('Mark this alert as resolved?')) return;
  const r=await pharmAction({action:'resolve_alert',alert_id:id});
  if(r.success){toast(r.message||'Alert resolved');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}
</script>
