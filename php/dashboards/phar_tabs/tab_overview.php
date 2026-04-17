<?php
// ── Stock Value Breakdown: fetch per medicine for overview ──
$stock_breakdown = [];
$sbq = mysqli_query($conn, "SELECT medicine_name, category, stock_quantity, unit_price, (stock_quantity * unit_price) AS line_value
    FROM medicines WHERE status='active' AND stock_quantity > 0 ORDER BY line_value DESC LIMIT 100");
if ($sbq) while ($r = mysqli_fetch_assoc($sbq)) $stock_breakdown[] = $r;
$sb_by_cat = [];
foreach ($stock_breakdown as $sb) {
    $cat = $sb['category'] ?: 'Uncategorized';
    $sb_by_cat[$cat] = ($sb_by_cat[$cat] ?? 0) + $sb['line_value'];
}
?>
<!-- ════════════════════════════════════════════════════════════
     MODULE 1: OVERVIEW / MAIN DASHBOARD  (REVAMPED)
     ════════════════════════════════════════════════════════════ -->
<div id="sec-overview" class="dash-section <?=($active_tab==='overview')?'active':''?>">

<style>
/* ── Premium Hero Banner ── */
.pharm-hero-v2 {
  position:relative;overflow:hidden;border-radius:20px;margin-bottom:2rem;
  background:linear-gradient(135deg,#0f2027 0%,#1a6b5e 50%,#08a88a 100%);
  padding:2.5rem 3rem;display:flex;align-items:center;gap:2rem;flex-wrap:wrap;
  box-shadow:0 20px 60px rgba(8,168,138,.3);
}
.pharm-hero-v2::before {
  content:'';position:absolute;top:-50%;right:-10%;width:400px;height:400px;
  background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);border-radius:50%;
}
.pharm-hero-v2::after{
  content:'';position:absolute;bottom:-30%;left:30%;width:300px;height:300px;
  background:radial-gradient(circle,rgba(39,174,96,.15) 0%,transparent 70%);border-radius:50%;
}
.pharm-hero-avatar-v2 {
  width:80px;height:80px;border-radius:50%;border:3px solid rgba(255,255,255,.4);
  background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;
  font-size:2.8rem;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden;
  backdrop-filter:blur(10px);position:relative;z-index:1;
}
.pharm-hero-info-v2 { flex:1;position:relative;z-index:1; }
.pharm-hero-info-v2 h2 { font-size:2.2rem;font-weight:800;color:#fff;margin:0 0 .3rem; }
.pharm-hero-info-v2 p  { font-size:1.25rem;color:rgba(255,255,255,.75);margin:0 0 .8rem; }
.pharm-hero-chips { display:flex;gap:.7rem;flex-wrap:wrap; }
.pharm-chip {
  display:inline-flex;align-items:center;gap:.5rem;padding:.35rem 1rem;
  border-radius:20px;font-size:1.1rem;font-weight:600;
  background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);
  backdrop-filter:blur(5px);
}
.pharm-chip.valid   { background:rgba(39,174,96,.3);border-color:rgba(39,174,96,.5); }
.pharm-chip.warning { background:rgba(243,156,18,.3);border-color:rgba(243,156,18,.5); }
.pharm-chip.danger  { background:rgba(231,76,60,.3);border-color:rgba(231,76,60,.5); }

/* ── Premium Stat Cards ── */
.pharm-stat-grid {
  display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:1.5rem;margin-bottom:2rem;
}
.pharm-stat-card {
  background:var(--surface);border:1px solid var(--border);border-radius:16px;
  padding:1.8rem 1.6rem;position:relative;overflow:hidden;cursor:pointer;
  transition:all .25s cubic-bezier(.4,0,.2,1);box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.pharm-stat-card::before {
  content:'';position:absolute;top:0;left:0;right:0;height:4px;
  border-radius:16px 16px 0 0;background:var(--card-accent,var(--role-accent));
}
.pharm-stat-card:hover { transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.12); }
.pharm-stat-card .sc-icon {
  width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;
  font-size:1.6rem;margin-bottom:1rem;
}
.pharm-stat-card .sc-val { font-size:2.8rem;font-weight:800;line-height:1;color:var(--text-primary);margin-bottom:.4rem; }
.pharm-stat-card .sc-lbl { font-size:1.15rem;font-weight:500;color:var(--text-secondary); }
.pharm-stat-card .sc-badge {
  position:absolute;top:1.2rem;right:1.2rem;font-size:.95rem;font-weight:700;
  padding:.2rem .7rem;border-radius:20px;
}

/* ── Alert Banner ── */
.pharm-alert-banner {
  background:linear-gradient(135deg,rgba(231,76,60,.08),rgba(243,156,18,.05));
  border:1px solid rgba(231,76,60,.25);border-left:4px solid var(--danger);
  border-radius:0 12px 12px 0;padding:1.2rem 1.8rem;margin-bottom:2rem;
  display:flex;align-items:center;gap:1rem;flex-wrap:wrap;
}

/* ── Quick Action Buttons ── */
.pharm-quick-grid {
  display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.2rem;margin-bottom:2.5rem;
}
.pharm-quick-btn {
  display:flex;flex-direction:column;align-items:center;gap:.7rem;padding:1.8rem 1rem;
  border-radius:14px;border:1.5px solid var(--border);background:var(--surface);
  cursor:pointer;transition:all .2s;text-decoration:none;
}
.pharm-quick-btn:hover { border-color:var(--role-accent);box-shadow:0 6px 24px rgba(0,0,0,.1);transform:translateY(-2px); }
.pharm-quick-btn .qb-icon {
  width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;
}
.pharm-quick-btn .qb-label { font-size:1.2rem;font-weight:600;color:var(--text-primary);text-align:center; }

/* ── Charts Grid ── */
.pharm-charts-grid {
  display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;
}
@media(max-width:900px){.pharm-charts-grid{grid-template-columns:1fr;}}
.pharm-chart-card {
  background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:2rem;
  box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.pharm-chart-card h4 {
  font-size:1.35rem;font-weight:700;color:var(--text-primary);margin:0 0 1.2rem;
  display:flex;align-items:center;gap:.7rem;
}
.pharm-chart-card .chart-wrap { height:200px;position:relative; }

/* ── Stock Value Breakdown ── */
.stock-breakdown-card {
  background:var(--surface);border:1px solid var(--border);border-radius:16px;
  margin-bottom:2rem;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.stock-breakdown-header {
  display:flex;align-items:center;justify-content:space-between;
  padding:1.8rem 2rem;border-bottom:1px solid var(--border);background:linear-gradient(135deg,rgba(8,168,138,.06),rgba(39,174,96,.04));
}
.stock-breakdown-header h3 { font-size:1.5rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.7rem;margin:0; }
.stock-breakdown-header h3 i { color:var(--role-accent); }
.stock-grand-total {
  font-size: clamp(1.4rem, 2.5vw, 2rem);
  font-weight:800;color:var(--role-accent);background:rgba(8,168,138,.1);
  padding:.5rem 1.4rem;border-radius:10px;
  white-space: nowrap;
}
.sb-table { width:100%;border-collapse:collapse;font-size:1.25rem; }
.sb-table thead tr { background:var(--surface-2); }
.sb-table th { padding:1.1rem 1.6rem;text-align:left;font-weight:700;font-size:1.1rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em; }
.sb-table td { padding:1rem 1.6rem;border-bottom:1px solid var(--border);vertical-align:middle; }
.sb-table tr:last-child td { border-bottom:none; }
.sb-table tr:hover td { background:var(--surface-2); }
.sb-cat-badge { display:inline-block;padding:.2rem .8rem;border-radius:20px;font-size:1rem;font-weight:600;background:var(--role-accent-light);color:var(--role-accent); }
.sb-qty-bar-wrap { display:flex;align-items:center;gap:.8rem; }
.sb-qty-bar { flex:1;height:6px;background:var(--border);border-radius:3px;overflow:hidden;min-width:60px; }
.sb-qty-bar-fill { height:100%;border-radius:3px;background:var(--role-accent);transition:width .5s; }
.sb-subtotal-row td { background:rgba(8,168,138,.04);font-weight:600;font-size:1.15rem;border-top:2px solid var(--border); }
.sb-toggle-btn { background:var(--role-accent);color:#fff;border:none;padding:.6rem 1.4rem;border-radius:8px;font-size:1.1rem;font-weight:600;cursor:pointer;transition:.2s; }
.sb-toggle-btn:hover { opacity:.85; }

/* ── Activity Feed ── */
.pharm-activity-card {
  background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:2rem;box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.pharm-activity-card h4 { font-size:1.35rem;font-weight:700;margin:0 0 1.5rem;display:flex;align-items:center;gap:.7rem; }
.activity-feed-item {
  display:flex;align-items:flex-start;gap:1.2rem;padding:1rem 0;border-bottom:1px solid var(--border);
  animation:fadeIn .3s ease;
}
.activity-feed-item:last-child { border-bottom:none; }
.af-dot {
  width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:1.3rem;flex-shrink:0;margin-top:.2rem;
}
.af-dot.green  { background:rgba(39,174,96,.15);color:#27AE60; }
.af-dot.blue   { background:rgba(47,128,237,.15);color:#2F80ED; }
.af-dot.red    { background:rgba(231,76,60,.15);color:#E74C3C; }
.af-dot.orange { background:rgba(243,156,18,.15);color:#F39C12; }
.af-dot.teal   { background:rgba(8,168,138,.15);color:#08a88a; }
.af-body { flex:1; }
.af-body .af-desc { font-size:1.25rem;font-weight:500;color:var(--text-primary);margin:0 0 .2rem; }
.af-body .af-time { font-size:1.1rem;color:var(--text-muted);display:flex;align-items:center;gap:.4rem; }
</style>

  <!-- Hero Banner -->
  <div class="pharm-hero-v2">
    <div class="pharm-hero-avatar-v2">
      <?php $pImg=$pharm_row['profile_photo']??$pharm_row['profile_image']??'';
        if($pImg && $pImg!=='default-avatar.png' && !empty($pImg)):?>
        <img src="/RMU-Medical-Management-System/<?=htmlspecialchars($pImg)?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
      <?php else: echo strtoupper(substr($pharm_row['full_name']??$pharmacistName,0,1)); endif;?>
    </div>
    <div class="pharm-hero-info-v2">
      <h2>Good <?=date('H')<12?'Morning':(date('H')<17?'Afternoon':'Evening')?>, <?=htmlspecialchars(explode(' ',$pharm_row['full_name']??$pharmacistName)[0])?> 💊</h2>
      <p><?=date('l, d F Y')?> &middot; <?=date('g:i A')?></p>
      <div class="pharm-hero-chips">
        <span class="pharm-chip"><i class="fas fa-id-badge"></i> <?=htmlspecialchars($pharm_row['license_number']??'No License')?></span>
        <?php $licExp=$pharm_row['license_expiry']??null;
          if($licExp){
            $daysLeft=(strtotime($licExp)-time())/86400;
            $licStatus=$daysLeft>90?'Valid':($daysLeft>0?'Expiring Soon':'Expired');
            $licClass=$daysLeft>90?'valid':($daysLeft>0?'warning':'danger');
        ?>
        <span class="pharm-chip <?=$licClass?>"><i class="fas fa-<?=$daysLeft>0?'check-circle':'exclamation-circle'?>"></i> License <?=$licStatus?></span>
        <?php }?>
        <span class="pharm-chip"><i class="fas fa-clock"></i> <?=$stats['dispensed_today']?> dispensed today</span>
        <span class="pharm-chip"><i class="fas fa-bell"></i> <?=$stats['unread_notifs']?> unread</span>
      </div>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="pharm-stat-grid">
    <?php $cardDefs=[
      ['fa-pills','Total Medicines',$stats['total_medicines'],'#08a88a','rgba(8,168,138,.1)','inventory','#08a88a'],
      ['fa-box-open','In Stock',$stats['in_stock'],'#27AE60','rgba(39,174,96,.1)','inventory','#27AE60'],
      ['fa-exclamation-triangle','Low Stock',$stats['low_stock'],'#F39C12','rgba(243,156,18,.1)','alerts','#F39C12'],
      ['fa-times-circle','Out of Stock',$stats['out_of_stock'],'#E74C3C','rgba(231,76,60,.1)','alerts','#E74C3C'],
      ['fa-clock','Expiring Soon',$stats['expiring_soon'],'#E67E22','rgba(230,126,34,.1)','alerts','#E67E22'],
      ['fa-prescription-bottle-medical','Pending Rx',$stats['pending_rx'],'#2F80ED','rgba(47,128,237,.1)','prescriptions','#2F80ED'],
      ['fa-check-double','Dispensed Today',$stats['dispensed_today'],'#27AE60','rgba(39,174,96,.1)','overview','#27AE60'],
      ['fa-coins','Stock Value','GH₵'.number_format($stats['total_stock_value'],2),'#8E44AD','rgba(142,68,173,.1)','overview','#8E44AD'],
    ];
    foreach($cardDefs as [$ic,$lbl,$val,$accent,$iconBg,$tabLink,$accentHex]):?>
    <div class="pharm-stat-card" style="--card-accent:<?=$accentHex?>" onclick="showTab('<?=$tabLink?>',null)">
      <div class="sc-icon" style="background:<?=$iconBg?>;color:<?=$accent?>"><i class="fas <?=$ic?>"></i></div>
      <div class="sc-val"><?=$val?></div>
      <div class="sc-lbl"><?=$lbl?></div>
    </div>
    <?php endforeach;?>
  </div>

  <!-- Alert Banner -->
  <?php if($stats['out_of_stock']>0||$stats['low_stock']>0||$stats['expiring_soon']>0||$stats['expired']>0):?>
  <div class="pharm-alert-banner">
    <i class="fas fa-triangle-exclamation" style="font-size:1.8rem;color:var(--danger);flex-shrink:0;"></i>
    <div style="flex:1;">
      <strong style="font-size:1.3rem;color:var(--danger);">Inventory Attention Required</strong>
      <div style="font-size:1.2rem;margin-top:.3rem;color:var(--text-secondary);">
        <?=$stats['out_of_stock']?> out of stock &bull; <?=$stats['low_stock']?> low stock &bull;
        <?=$stats['expiring_soon']?> expiring soon &bull; <?=$stats['expired']?> expired
      </div>
    </div>
    <button class="btn btn-primary btn-sm" onclick="showTab('alerts',null)" style="background:var(--danger);flex-shrink:0;"><span class="btn-text"><i class="fas fa-arrow-right"></i> Review Alerts</span></button>
  </div>
  <?php endif;?>

  <!-- Quick Actions -->
  <div class="pharm-quick-grid">
    <button class="pharm-quick-btn" onclick="showTab('prescriptions',null)">
      <div class="qb-icon" style="background:rgba(39,174,96,.12);color:#27AE60;"><i class="fas fa-prescription-bottle-medical"></i></div>
      <span class="qb-label">Dispense Prescription</span>
    </button>
    <button class="pharm-quick-btn" onclick="openModal('modalAddStock')">
      <div class="qb-icon" style="background:rgba(8,168,138,.12);color:#08a88a;"><i class="fas fa-plus-circle"></i></div>
      <span class="qb-label">Receive Stock</span>
    </button>
    <button class="pharm-quick-btn" onclick="openModal('modalAddMed')">
      <div class="qb-icon" style="background:rgba(47,128,237,.12);color:#2F80ED;"><i class="fas fa-pills"></i></div>
      <span class="qb-label">Add Medicine</span>
    </button>
    <button class="pharm-quick-btn" onclick="showTab('alerts',null)">
      <div class="qb-icon" style="background:rgba(243,156,18,.12);color:#F39C12;"><i class="fas fa-bell"></i></div>
      <span class="qb-label">View Alerts<?php if($stats['active_alerts']>0): ?> <span style="background:var(--danger);color:#fff;border-radius:20px;padding:.1rem .5rem;font-size:.9rem;margin-left:.3rem;"><?=$stats['active_alerts']?></span><?php endif;?></span>
    </button>
    <button class="pharm-quick-btn" onclick="showTab('reports',null)">
      <div class="qb-icon" style="background:rgba(142,68,173,.12);color:#8E44AD;"><i class="fas fa-file-export"></i></div>
      <span class="qb-label">Generate Report</span>
    </button>
  </div>

  <!-- Charts -->
  <div class="pharm-charts-grid">
    <div class="pharm-chart-card">
      <h4><i class="fas fa-chart-bar" style="color:var(--role-accent);"></i>Dispensing This Week</h4>
      <div class="chart-wrap"><canvas id="chartWeeklyDisp"></canvas></div>
    </div>
    <div class="pharm-chart-card">
      <h4><i class="fas fa-pills" style="color:#2F80ED;"></i>Top 5 Medicines (30 days)</h4>
      <div class="chart-wrap"><canvas id="chartTopMeds"></canvas></div>
    </div>
    <div class="pharm-chart-card">
      <h4><i class="fas fa-chart-pie" style="color:#F39C12;"></i>Stock Status Breakdown</h4>
      <div class="chart-wrap"><canvas id="chartStockStatus"></canvas></div>
    </div>
    <div class="pharm-chart-card">
      <h4><i class="fas fa-prescription" style="color:#27AE60;"></i>Rx Fulfillment Rate</h4>
      <div class="chart-wrap"><canvas id="chartFulfill"></canvas></div>
    </div>
  </div>

  <!-- Stock Value Breakdown -->
  <?php if(!empty($stock_breakdown)):
    $maxVal = max(array_column($stock_breakdown,'line_value'));
  ?>
  <div class="stock-breakdown-card">
    <div class="stock-breakdown-header">
      <h3><i class="fas fa-coins"></i>Stock Value Breakdown</h3>
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sb-toggle-btn" onclick="toggleBreakdown()"><i class="fas fa-eye" id="sbToggleIcon"></i> <span id="sbToggleLabel">Collapse</span></button>
        <div class="stock-grand-total">GH₵<?=number_format($stats['total_stock_value'],2)?></div>
      </div>
    </div>
    <div id="stockBreakdownBody">
      <?php
      // Category subtotals row interspersed
      $currentCat = null;
      $catSubtotal = 0;
      $catItemCount = 0;
      $catCount = count($sb_by_cat);
      arsort($sb_by_cat); // sort cats by value
      ?>
      <div style="overflow-x:auto;">
        <table class="sb-table">
          <thead><tr>
            <th>#</th>
            <th>Medicine</th>
            <th>Category</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Line Value</th>
            <th style="width:150px;">Stock Level</th>
          </tr></thead>
          <tbody>
          <?php
          // Group by category
          $grouped = [];
          foreach ($stock_breakdown as $sb) {
              $cat = $sb['category'] ?: 'Uncategorized';
              $grouped[$cat][] = $sb;
          }
          ksort($grouped);
          $rowNum = 0;
          foreach ($grouped as $cat => $items):
              $catTotal = array_sum(array_column($items, 'line_value'));
              $maxCatQty = max(array_column($items,'stock_quantity'));
          ?>
          <?php foreach ($items as $sb): $rowNum++;?>
          <tr>
            <td style="color:var(--text-muted);font-size:1.1rem;"><?=$rowNum?></td>
            <td><strong><?=htmlspecialchars($sb['medicine_name'])?></strong></td>
            <td><span class="sb-cat-badge"><?=htmlspecialchars($cat)?></span></td>
            <td style="font-weight:700;"><?=number_format($sb['stock_quantity'])?></td>
            <td>GH₵<?=number_format($sb['unit_price'],2)?></td>
            <td style="font-weight:700;color:var(--role-accent);">GH₵<?=number_format($sb['line_value'],2)?></td>
            <td>
              <div class="sb-qty-bar-wrap">
                <div class="sb-qty-bar">
                  <div class="sb-qty-bar-fill" style="width:<?=min(100,round(($sb['stock_quantity']/$maxCatQty)*100))?>%;"></div>
                </div>
                <span style="font-size:1rem;color:var(--text-muted);white-space:nowrap;"><?=$sb['stock_quantity']?></span>
              </div>
            </td>
          </tr>
          <?php endforeach;?>
          <!-- Category subtotal row -->
          <tr class="sb-subtotal-row">
            <td colspan="3" style="padding-left:2rem;"><i class="fas fa-layer-group" style="color:var(--role-accent);margin-right:.5rem;"></i><?=htmlspecialchars($cat)?> Subtotal (<?=count($items)?> items)</td>
            <td><?=number_format(array_sum(array_column($items,'stock_quantity')))?></td>
            <td>—</td>
            <td style="color:var(--role-accent);">GH₵<?=number_format($catTotal,2)?></td>
            <td></td>
          </tr>
          <?php endforeach;?>
          </tbody>
          <tfoot>
            <tr style="background:linear-gradient(135deg,rgba(8,168,138,.08),rgba(39,174,96,.05));border-top:2px solid var(--role-accent);">
              <td colspan="4" style="padding:1.4rem 1.6rem;font-size:1.4rem;font-weight:700;color:var(--text-primary);">
                <i class="fas fa-sigma" style="color:var(--role-accent);margin-right:.5rem;"></i>GRAND TOTAL — <?=count($stock_breakdown)?> medicines
              </td>
              <td style="font-size:1.1rem;color:var(--text-muted);">—</td>
              <td style="font-size:1.8rem;font-weight:800;color:var(--role-accent);">GH₵<?=number_format($stats['total_stock_value'],2)?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
  <?php endif;?>

  <!-- Recent Activity -->
  <div class="pharm-activity-card">
    <h4><i class="fas fa-clock-rotate-left" style="color:var(--role-accent);"></i>Recent Activity</h4>
    <?php if(empty($activity)):?>
    <div style="text-align:center;padding:3rem;color:var(--text-muted);">
      <i class="fas fa-inbox" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.4;"></i>
      No recent activity
    </div>
    <?php else: foreach($activity as $act):
      $dotMap=['Dispensed'=>['green','fa-hand-holding-medical'],'Prescription'=>['blue','fa-prescription'],'Alert'=>['red','fa-triangle-exclamation'],'Stock'=>['teal','fa-boxes-stacked']];
      $dotInfo=$dotMap[$act['type']]??['orange','fa-circle'];
    ?>
    <div class="activity-feed-item">
      <div class="af-dot <?=$dotInfo[0]?>"><i class="fas <?=$dotInfo[1]?>"></i></div>
      <div class="af-body">
        <div class="af-desc"><?=htmlspecialchars($act['description'])?></div>
        <div class="af-time"><i class="fas fa-clock"></i><?=date('d M Y, g:i A',strtotime($act['ts']))?></div>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>

</div>

<script>
function toggleBreakdown(){
  const body=document.getElementById('stockBreakdownBody');
  const icon=document.getElementById('sbToggleIcon');
  const lbl=document.getElementById('sbToggleLabel');
  if(body.style.display==='none'){
    body.style.display='';icon.className='fas fa-eye';lbl.textContent='Collapse';
  } else {
    body.style.display='none';icon.className='fas fa-eye-slash';lbl.textContent='Expand';
  }
}
</script>
