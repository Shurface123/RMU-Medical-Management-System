<!-- ════════════════════════════════════════════════════════════
     MODULE 1: OVERVIEW / MAIN DASHBOARD
     ════════════════════════════════════════════════════════════ -->
<div id="sec-overview" class="dash-section <?=($active_tab==='overview')?'active':''?>">

  <!-- Hero Banner -->
  <div class="pharm-hero">
    <div class="pharm-avatar-hero">
      <?php $pImg=$pharm_row['profile_photo']??$pharm_row['profile_image']??'';
        if($pImg && $pImg!=='default-avatar.png'):?>
        <img src="/RMU-Medical-Management-System/<?=htmlspecialchars($pImg)?>" alt="Profile">
      <?php else: echo strtoupper(substr($pharm_row['full_name']??$pharmacistName,0,1)); endif;?>
    </div>
    <div class="pharm-hero-info">
      <h2>Good <?=date('H')<12?'Morning':(date('H')<17?'Afternoon':'Evening')?>, <?=htmlspecialchars(explode(' ',$pharm_row['full_name']??$pharmacistName)[0])?> 💊</h2>
      <p><?=date('l, d F Y')?> &middot; <?=date('g:i A')?></p>
      <div style="margin-top:.5rem;">
        <span class="hero-badge"><i class="fas fa-id-badge"></i> <?=htmlspecialchars($pharm_row['license_number']??'N/A')?></span>
        <?php $licExp=$pharm_row['license_expiry']??null;
          if($licExp):
            $daysLeft=(strtotime($licExp)-time())/86400;
            $licStatus=$daysLeft>90?'Valid':($daysLeft>0?'Expiring Soon':'Expired');
            $licColor=$daysLeft>90?'rgba(39,174,96,.8)':($daysLeft>0?'rgba(243,156,18,.8)':'rgba(231,76,60,.8)');
        ?>
        <span class="hero-badge" style="background:<?=$licColor?>;border-color:<?=$licColor?>;">
          <i class="fas fa-<?=$daysLeft>0?'check-circle':'exclamation-circle'?>"></i> License <?=$licStatus?>
        </span>
        <?php endif;?>
        <span class="hero-badge"><i class="fas fa-clock"></i> Today: <?=$stats['dispensed_today']?> dispensed</span>
      </div>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="adm-summary-strip">
    <?php foreach([
      ['fa-pills','Total Medicines',$stats['total_medicines'],'teal'],
      ['fa-box-open','In Stock',$stats['in_stock'],'green'],
      ['fa-exclamation-triangle','Low Stock',$stats['low_stock'],'orange'],
      ['fa-times-circle','Out of Stock',$stats['out_of_stock'],'red'],
      ['fa-clock','Expiring Soon',$stats['expiring_soon'],'orange'],
      ['fa-prescription','Pending Rx',$stats['pending_rx'],'blue'],
      ['fa-check-double','Dispensed Today',$stats['dispensed_today'],'green'],
      ['fa-coins','Stock Value','GH₵'.number_format($stats['total_stock_value'],2),'teal'],
    ] as [$ic,$lbl,$val,$col]):?>
    <div class="adm-mini-card" onclick="showTab('<?=$lbl==='Pending Rx'?'prescriptions':($lbl==='Low Stock'||$lbl==='Out of Stock'||$lbl==='Expiring Soon'?'alerts':'inventory')?>',null)">
      <div class="adm-mini-card-num <?=$col?>"><?=$val?></div>
      <div class="adm-mini-card-label"><i class="fas <?=$ic?>" style="margin-right:.3rem;"></i><?=$lbl?></div>
    </div>
    <?php endforeach;?>
  </div>

  <!-- Alert Banner -->
  <?php if($stats['out_of_stock']>0||$stats['low_stock']>0||$stats['expiring_soon']>0||$stats['expired']>0):?>
  <div style="background:var(--danger-light);border-left:4px solid var(--danger);border-radius:0 12px 12px 0;padding:1.2rem 1.6rem;margin-bottom:1.5rem;font-size:1.3rem;color:var(--danger);">
    <i class="fas fa-triangle-exclamation"></i>
    <strong><?=$stats['out_of_stock']?> out of stock</strong>, <strong><?=$stats['low_stock']?> low stock</strong>, <strong><?=$stats['expiring_soon']?> expiring</strong>, <strong><?=$stats['expired']?> expired</strong> — <a href="#" onclick="showTab('alerts',null);return false;" style="color:var(--danger);font-weight:700;text-decoration:underline;">review alerts</a>
  </div>
  <?php endif;?>

  <!-- Quick Actions -->
  <div class="quick-actions">
    <button class="quick-action-btn" onclick="showTab('prescriptions',null)"><i class="fas fa-prescription-bottle-medical" style="color:var(--primary);"></i> Dispense Prescription</button>
    <button class="quick-action-btn" onclick="openModal('modalAddStock')"><i class="fas fa-plus-circle" style="color:var(--success);"></i> Add Stock</button>
    <button class="quick-action-btn" onclick="showTab('alerts',null)"><i class="fas fa-bell" style="color:var(--warning);"></i> View Alerts</button>
    <button class="quick-action-btn" onclick="showTab('reports',null)"><i class="fas fa-file-export" style="color:var(--info);"></i> Generate Report</button>
  </div>

  <!-- Charts Row -->
  <div class="charts-grid">
    <div class="adm-card" style="padding:1.8rem;">
      <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-chart-bar" style="color:var(--role-accent);margin-right:.5rem;"></i>Dispensing This Week</h3>
      <div class="chart-wrap"><canvas id="chartWeeklyDisp"></canvas></div>
    </div>
    <div class="adm-card" style="padding:1.8rem;">
      <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-pills" style="color:var(--primary);margin-right:.5rem;"></i>Top 5 Medicines (30d)</h3>
      <div class="chart-wrap"><canvas id="chartTopMeds"></canvas></div>
    </div>
    <div class="adm-card" style="padding:1.8rem;">
      <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-chart-pie" style="color:var(--warning);margin-right:.5rem;"></i>Stock Status</h3>
      <div class="chart-wrap"><canvas id="chartStockStatus"></canvas></div>
    </div>
    <div class="adm-card" style="padding:1.8rem;">
      <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-prescription" style="color:var(--info);margin-right:.5rem;"></i>Rx Fulfillment</h3>
      <div class="chart-wrap"><canvas id="chartFulfill"></canvas></div>
    </div>
  </div>

  <!-- Recent Activity -->
  <div class="adm-card" style="padding:1.8rem;">
    <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-clock-rotate-left" style="color:var(--role-accent);margin-right:.5rem;"></i>Recent Activity</h3>
    <?php if(empty($activity)):?><p style="text-align:center;padding:2rem;color:var(--text-muted);">No recent activity</p>
    <?php else: foreach($activity as $act):
      $dotMap=['Dispensed'=>'','Prescription'=>'blue','Alert'=>'red']; $dotCls=$dotMap[$act['type']]??'';
    ?>
    <div class="activity-item">
      <div class="activity-dot <?=$dotCls?>"></div>
      <div style="flex:1;">
        <div style="font-size:1.3rem;font-weight:500;"><?=htmlspecialchars($act['description'])?></div>
        <div style="font-size:1.1rem;color:var(--text-muted);margin-top:.2rem;">
          <i class="fas fa-clock" style="margin-right:.3rem;"></i><?=date('d M Y, g:i A',strtotime($act['ts']))?>
        </div>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>

</div>
