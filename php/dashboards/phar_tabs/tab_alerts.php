<!-- ════════════════════════════════════════════════════════════
     MODULE 5: STOCK ALERTS  (REVAMPED — Filter & Resolve Fixed)
     ════════════════════════════════════════════════════════════ -->
<div id="sec-alerts" class="dash-section <?=($active_tab==='alerts')?'active':''?>">

<style>
/* Alert filter pills */
.alert-filter-nav { display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.8rem; }
.alert-fn-btn {
  display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1.4rem;
  border-radius:20px;border:1px solid var(--border);background:var(--surface);
  font-size:1.15rem;font-weight:500;color:var(--text-secondary);cursor:pointer;transition:all .2s;
}
.alert-fn-btn:hover, .alert-fn-btn.active { color:#fff; }
.alert-fn-btn[data-filter="all"].active     { background:#5f6368;border-color:#5f6368; }
.alert-fn-btn[data-filter="low_stock"].active     { background:#F39C12;border-color:#F39C12; }
.alert-fn-btn[data-filter="out_of_stock"].active  { background:#E74C3C;border-color:#E74C3C; }
.alert-fn-btn[data-filter="expiring_soon"].active { background:#E67E22;border-color:#E67E22; }
.alert-fn-btn[data-filter="expired"].active       { background:#8E44AD;border-color:#8E44AD; }
/* Alert cards */
.alert-adv-card {
  background:var(--surface);border:1px solid var(--border);border-radius:14px;
  margin-bottom:1.2rem;overflow:hidden;transition:all .25s;
  box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.alert-adv-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.12);transform:translateY(-2px); }
.alert-adv-card[data-status="low_stock"]     { border-left:5px solid #F39C12; }
.alert-adv-card[data-status="out_of_stock"]  { border-left:5px solid #E74C3C; }
.alert-adv-card[data-status="expiring_soon"] { border-left:5px solid #E67E22; }
.alert-adv-card[data-status="expired"]       { border-left:5px solid #8E44AD; }
.alert-adv-inner { padding:1.8rem 2rem;display:flex;align-items:flex-start;gap:1.5rem; }
.alert-type-icon {
  width:54px;height:54px;border-radius:14px;display:flex;align-items:center;justify-content:center;
  font-size:1.8rem;flex-shrink:0;
}
.ati-low_stock     { background:rgba(243,156,18,.15);color:#F39C12; }
.ati-out_of_stock  { background:rgba(231,76,60,.15);color:#E74C3C; }
.ati-expiring_soon { background:rgba(230,126,34,.15);color:#E67E22; }
.ati-expired       { background:rgba(142,68,173,.15);color:#8E44AD; }
.alert-adv-body { flex:1; }
.alert-adv-top { display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:.8rem; }
.alert-adv-name { font-size:1.5rem;font-weight:700;color:var(--text-primary);margin:.3rem 0 0; }
.alert-adv-badge {
  display:inline-block;padding:.3rem 1rem;border-radius:20px;font-size:1.05rem;font-weight:700;
  margin-bottom:.3rem;
}
.badge-low_stock     { background:rgba(243,156,18,.15);color:#c17f10; }
.badge-out_of_stock  { background:rgba(231,76,60,.15);color:#c0392b; }
.badge-expiring_soon { background:rgba(230,126,34,.15);color:#c05c00; }
.badge-expired       { background:rgba(142,68,173,.15);color:#6c3483; }
.alert-meta-row { display:flex;gap:2rem;flex-wrap:wrap;margin-top:.8rem;color:var(--text-secondary);font-size:1.2rem; }
.alert-meta-item { display:flex;align-items:center;gap:.5rem; }
.alert-mini-bar-wrap { display:flex;align-items:center;gap:1rem;margin-top:.8rem; }
.alert-mini-bar { flex:1;height:8px;background:var(--border);border-radius:4px;overflow:hidden;max-width:140px; }
.alert-mini-fill { height:100%;border-radius:4px; }
.alert-action-group { display:flex;gap:.7rem;flex-wrap:wrap;margin-top:1.2rem; }
.tip-text { font-size:1.1rem;color:var(--text-muted);margin-top:.5rem;display:flex;align-items:center;gap:.4rem; }
</style>

  <div class="sec-header">
    <h2><i class="fas fa-triangle-exclamation"></i> Stock Alerts</h2>
    <span style="background:rgba(231,76,60,.1);color:var(--danger);font-weight:700;padding:.4rem 1.2rem;border-radius:20px;font-size:1.2rem;">
      <?=$stats['active_alerts']?> active
    </span>
  </div>

  <!-- Filter Nav (FIXED: works on div cards via filterAlertCards()) -->
  <nav class="alert-filter-nav">
    <button class="alert-fn-btn active" data-filter="all" onclick="filterAlertCards('all',this)">
      <i class="fas fa-list"></i> All (<?=$stats['active_alerts']?>)
    </button>
    <button class="alert-fn-btn" data-filter="low_stock" onclick="filterAlertCards('low_stock',this)">
      <i class="fas fa-exclamation-triangle"></i> Low Stock (<?=$stats['low_stock']?>)
    </button>
    <button class="alert-fn-btn" data-filter="out_of_stock" onclick="filterAlertCards('out_of_stock',this)">
      <i class="fas fa-times-circle"></i> Out of Stock (<?=$stats['out_of_stock']?>)
    </button>
    <button class="alert-fn-btn" data-filter="expiring_soon" onclick="filterAlertCards('expiring_soon',this)">
      <i class="fas fa-clock"></i> Expiring Soon (<?=$stats['expiring_soon']?>)
    </button>
    <button class="alert-fn-btn" data-filter="expired" onclick="filterAlertCards('expired',this)">
      <i class="fas fa-calendar-xmark"></i> Expired (<?=$stats['expired']?>)
    </button>
  </nav>

  <div id="alertsList">
    <?php if(empty($alerts)):?>
    <div class="adm-card" style="text-align:center;padding:4rem;">
      <i class="fas fa-check-circle" style="font-size:4rem;color:var(--success);display:block;margin-bottom:1.2rem;"></i>
      <p style="color:var(--text-muted);font-size:1.4rem;">No active alerts — inventory looks healthy!</p>
    </div>
    <?php else: foreach($alerts as $al):
      $alMap=[
        'low_stock'     =>['Low Stock','low_stock','fa-exclamation-triangle','Consider placing a restock order soon'],
        'out_of_stock'  =>['Out of Stock','out_of_stock','fa-times-circle','Critical — zero units. Restock immediately'],
        'expiring_soon' =>['Expiring Soon','expiring_soon','fa-clock','Check expiry and rotate stock'],
        'expired'       =>['Expired','expired','fa-calendar-xmark','Remove expired units from inventory'],
      ];
      $aInfo=$alMap[$al['alert_type']]??['Alert','out_of_stock','fa-bell','Review this alert'];
      $isExpiredType=in_array($al['alert_type'],['expired']);
      $isStockType=in_array($al['alert_type'],['low_stock','out_of_stock']);
      // Stock bar: percentage of reorder_level
      $reorder=(int)($al['reorder_level']??10);
      $qty=(int)$al['stock_quantity'];
      $barPct=$reorder>0?min(100,round(($qty/$reorder)*100)):0;
      $barColor=$qty==0?'#E74C3C':($qty<=$reorder?'#F39C12':'#27AE60');
    ?>
    <div class="alert-adv-card" data-status="<?=htmlspecialchars($al['alert_type'])?>" data-med-id="<?=$al['medicine_id']??0?>" data-alert-id="<?=$al['id']?>">
      <div class="alert-adv-inner">
        <div class="alert-type-icon ati-<?=$aInfo[1]?>"><i class="fas <?=$aInfo[2]?>"></i></div>
        <div class="alert-adv-body">
          <div class="alert-adv-top">
            <div>
              <span class="alert-adv-badge badge-<?=$aInfo[1]?>"><?=$aInfo[0]?></span>
              <div class="alert-adv-name"><?=htmlspecialchars($al['medicine_name'])?></div>
            </div>
            <div class="alert-action-group">
              <?php if($isExpiredType):?>
              <button class="btn btn-sm" style="background:rgba(142,68,173,.12);color:#8E44AD;border:1px solid rgba(142,68,173,.3);" 
                onclick="resolveExpired(<?=$al['medicine_id']??0?>, '<?=addslashes(htmlspecialchars($al['medicine_name']))?>', <?=$qty?>)">
                <span class="btn-text"><i class="fas fa-trash-alt"></i> Remove Expired Stock</span>
              </button>
              <?php elseif($isStockType):?>
              <button class="btn btn-success btn-sm" 
                onclick="openModal('modalAddStock')">
                <span class="btn-text"><i class="fas fa-plus"></i> Restock Now</span>
              </button>
              <button class="btn btn-sm" style="background:var(--surface-2);color:var(--text-secondary);border:1px solid var(--border);" 
                onclick="resolveAlertOnly(<?=$al['id']?>)">
                <span class="btn-text"><i class="fas fa-check"></i> Acknowledge</span>
              </button>
              <?php else:?>
              <button class="btn btn-success btn-sm" onclick="resolveAlertOnly(<?=$al['id']?>)">
                <span class="btn-text"><i class="fas fa-check"></i> Resolve</span>
              </button>
              <?php endif;?>
            </div>
          </div>

          <!-- Meta row -->
          <div class="alert-meta-row">
            <span class="alert-meta-item">
              <i class="fas fa-box" style="color:<?=$barColor?>;"></i>
              Current: <strong style="color:<?=$barColor?>;"><?=$qty?></strong>
            </span>
            <span class="alert-meta-item"><i class="fas fa-level-down-alt"></i>Reorder at: <?=$reorder?></span>
            <?php if($al['expiry_date']):?>
            <span class="alert-meta-item"><i class="fas fa-calendar"></i>Expires: <?=date('d M Y',strtotime($al['expiry_date']))?></span>
            <?php endif;?>
            <span class="alert-meta-item"><i class="fas fa-truck"></i><?=htmlspecialchars($al['supplier_name']??'No supplier')?></span>
          </div>

          <!-- Stock level mini bar -->
          <?php if($isStockType):?>
          <div class="alert-mini-bar-wrap">
            <span style="font-size:1.05rem;color:var(--text-muted);">Stock level:</span>
            <div class="alert-mini-bar">
              <div class="alert-mini-fill" style="width:<?=$barPct?>%;background:<?=$barColor?>;"></div>
            </div>
            <span style="font-size:1.05rem;color:var(--text-muted);"><?=$barPct?>% of reorder threshold</span>
          </div>
          <?php endif;?>

          <div class="tip-text"><i class="fas fa-lightbulb" style="color:var(--warning);"></i><?=$aInfo[3]?></div>
        </div>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>

</div>

<script>
// ── FIXED: Filter alert div-cards (not table rows) ──────────
function filterAlertCards(filter, btn) {
  document.querySelectorAll('.alert-filter-nav .alert-fn-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  document.querySelectorAll('#alertsList .alert-adv-card').forEach(card => {
    card.style.display = (filter === 'all' || card.dataset.status === filter) ? '' : 'none';
  });
}

// ── Resolve: Remove expired stock (persists to DB) ──────────
async function resolveExpired(medId, medName, qty) {
  const msg = `Remove "${medName}" from inventory?\n\n` +
    `• ${qty} expired units will be cleared to 0\n` +
    `• Medicine will be marked discontinued\n` +
    `• This cannot be undone. Confirm?`;
  if (!confirm(msg)) return;
  const r = await pharmAction({action: 'resolve_expired', medicine_id: medId});
  if (r.success) {
    toast(r.message || 'Expired stock removed');
    // Remove the card from DOM immediately so user sees instant feedback
    document.querySelectorAll(`#alertsList .alert-adv-card[data-med-id="${medId}"]`).forEach(c => {
      c.style.transition = 'all .4s';
      c.style.opacity = '0';
      c.style.transform = 'translateX(20px)';
      setTimeout(() => c.remove(), 400);
    });
    setTimeout(() => location.reload(), 1500);
  } else {
    toast(r.message || 'Error resolving alert', 'danger');
  }
}

// ── Acknowledge only (for low_stock confirmations) ──────────
async function resolveAlertOnly(alertId) {
  if (!confirm('Mark this alert as acknowledged?')) return;
  const r = await pharmAction({action: 'resolve_alert', alert_id: alertId});
  if (r.success) {
    toast('Alert acknowledged');
    const card = document.querySelector(`#alertsList .alert-adv-card[data-alert-id="${alertId}"]`);
    if (card) {
      card.style.transition = 'all .4s';
      card.style.opacity = '0';
      setTimeout(() => { card.remove(); }, 400);
    }
  } else {
    toast(r.message || 'Error', 'danger');
  }
}
</script>
