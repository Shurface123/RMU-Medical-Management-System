<!-- ════════════════════════════════════════════════════════════
     MODULE 7: ANALYTICS
     ════════════════════════════════════════════════════════════ -->
<div id="sec-analytics" class="dash-section <?=($active_tab==='analytics')?'active':''?>">

  <div class="sec-header">
    <h2><i class="fas fa-chart-bar"></i> Analytics</h2>
  </div>

  <!-- Summary Row -->
  <div class="adm-summary-strip" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
    <div class="adm-mini-card">
      <div class="adm-mini-card-num teal"><?=$stats['total_medicines']?></div>
      <div class="adm-mini-card-label">Total Medicines</div>
    </div>
    <div class="adm-mini-card">
      <div class="adm-mini-card-num green"><?=$stats['dispensed_today']?></div>
      <div class="adm-mini-card-label">Dispensed Today</div>
    </div>
    <div class="adm-mini-card">
      <div class="adm-mini-card-num blue"><?=$stats['pending_rx']?></div>
      <div class="adm-mini-card-label">Pending Rx</div>
    </div>
    <div class="adm-mini-card">
      <div class="adm-mini-card-num orange"><?=$stats['active_alerts']?></div>
      <div class="adm-mini-card-label">Active Alerts</div>
    </div>
    <div class="adm-mini-card">
      <div class="adm-mini-card-num teal">GH₵<?=number_format($stats['total_stock_value'],0)?></div>
      <div class="adm-mini-card-label">Total Stock Value</div>
    </div>
  </div>

  <!-- Charts Grid -->
  <div class="charts-grid">
    <div class="adm-card" style="padding:1.8rem;">
      <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-chart-line" style="color:var(--role-accent);margin-right:.5rem;"></i>Dispensing Volume (7-Day)</h3>
      <div class="chart-wrap"><canvas id="analyticsWeekly"></canvas></div>
    </div>
    <div class="adm-card" style="padding:1.8rem;">
      <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-ranking-star" style="color:var(--primary);margin-right:.5rem;"></i>Top Dispensed Medicines (30d)</h3>
      <div class="chart-wrap"><canvas id="analyticsTopMeds"></canvas></div>
    </div>
    <div class="adm-card" style="padding:1.8rem;">
      <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-chart-pie" style="color:var(--warning);margin-right:.5rem;"></i>Stock Status Distribution</h3>
      <div class="chart-wrap"><canvas id="analyticsStockDist"></canvas></div>
    </div>
    <div class="adm-card" style="padding:1.8rem;">
      <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-prescription" style="color:var(--info);margin-right:.5rem;"></i>Prescription Fulfillment</h3>
      <div class="chart-wrap"><canvas id="analyticsFulfill"></canvas></div>
    </div>
  </div>

  <!-- Monthly metrics -->
  <?php
    $monthlyDisp=qv($conn,"SELECT COUNT(*) FROM dispensing_records WHERE dispensing_date>='{$month_start}'");
    $monthlyRevenue=qv($conn,"SELECT COALESCE(SUM(selling_price*quantity_dispensed),0) FROM dispensing_records WHERE dispensing_date>='{$month_start}'");
    $monthlyExpired=qv($conn,"SELECT COUNT(*) FROM stock_transactions WHERE transaction_type='expired' AND transaction_date>='{$month_start}'");
    $avgDispPerDay=qv($conn,"SELECT ROUND(AVG(cnt),1) FROM (SELECT COUNT(*) AS cnt FROM dispensing_records WHERE dispensing_date>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY DATE(dispensing_date)) t");
  ?>
  <div class="adm-card" style="padding:1.8rem;margin-top:1.5rem;">
    <h3 style="font-size:1.4rem;font-weight:700;margin-bottom:1.2rem;"><i class="fas fa-calendar-days" style="color:var(--primary);margin-right:.5rem;"></i>This Month's Metrics</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;">
      <div style="text-align:center;padding:1.5rem;background:var(--surface-2);border-radius:var(--radius-md);">
        <div style="font-size:2.5rem;font-weight:800;color:var(--success);"><?=$monthlyDisp?></div>
        <div style="font-size:1.1rem;color:var(--text-muted);">Dispensing Actions</div>
      </div>
      <div style="text-align:center;padding:1.5rem;background:var(--surface-2);border-radius:var(--radius-md);">
        <div style="font-size:2.5rem;font-weight:800;color:var(--primary);">GH₵<?=number_format($monthlyRevenue,2)?></div>
        <div style="font-size:1.1rem;color:var(--text-muted);">Dispensing Revenue</div>
      </div>
      <div style="text-align:center;padding:1.5rem;background:var(--surface-2);border-radius:var(--radius-md);">
        <div style="font-size:2.5rem;font-weight:800;color:var(--danger);"><?=$monthlyExpired?></div>
        <div style="font-size:1.1rem;color:var(--text-muted);">Expired Removals</div>
      </div>
      <div style="text-align:center;padding:1.5rem;background:var(--surface-2);border-radius:var(--radius-md);">
        <div style="font-size:2.5rem;font-weight:800;color:var(--role-accent);"><?=$avgDispPerDay?:0?></div>
        <div style="font-size:1.1rem;color:var(--text-muted);">Avg. Dispensed/Day</div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
  const isDark=document.documentElement.getAttribute('data-theme')==='dark';
  const gc=isDark?'rgba(255,255,255,.08)':'rgba(0,0,0,.07)';
  const tc=isDark?'#9AAECB':'#5A6A85';

  const aw=document.getElementById('analyticsWeekly');
  if(aw && weeklyData.length){
    new Chart(aw,{type:'line',data:{labels:weeklyLabels,datasets:[{label:'Dispensed',data:weeklyData,borderColor:'#27AE60',backgroundColor:'rgba(39,174,96,.15)',fill:true,tension:.4,pointRadius:4,pointBackgroundColor:'#27AE60'}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{color:tc},grid:{color:gc}},x:{ticks:{color:tc},grid:{display:false}}}}});
  }

  const atm=document.getElementById('analyticsTopMeds');
  if(atm && topMedData.length){
    new Chart(atm,{type:'bar',data:{labels:topMedLabels,datasets:[{label:'Units',data:topMedData,backgroundColor:['rgba(39,174,96,.7)','rgba(47,128,237,.7)','rgba(243,156,18,.7)','rgba(231,76,60,.7)','rgba(41,128,185,.7)'],borderRadius:8}]},
    options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{color:tc},grid:{color:gc}},y:{ticks:{color:tc},grid:{display:false}}}}});
  }

  const asd=document.getElementById('analyticsStockDist');
  if(asd){
    new Chart(asd,{type:'doughnut',data:{labels:stockLabels,datasets:[{data:stockData,backgroundColor:['#27AE60','#F39C12','#E74C3C','#E67E22','#8E44AD'],borderWidth:0,hoverOffset:8}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:tc,padding:14,font:{size:12}}}}}});
  }

  const af=document.getElementById('analyticsFulfill');
  if(af){
    new Chart(af,{type:'doughnut',data:{labels:fulfillLabels,datasets:[{data:fulfillData,backgroundColor:['#F39C12','#27AE60','#2980B9','#E74C3C','#8E44AD'],borderWidth:0,hoverOffset:8}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:tc,padding:14,font:{size:12}}}}}});
  }
});
</script>
