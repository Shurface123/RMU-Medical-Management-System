<!-- ═══════════════ MODULE 9: ANALYTICS DASHBOARD ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-chart-bar" style="color:var(--role-accent);margin-right:.6rem;"></i> Lab Analytics</h1>
    <p>Trends, workload breakdown, turnaround times, and performance metrics</p>
  </div>
</div>

<!-- Key Metrics -->
<div class="adm-stats-grid" style="margin-bottom:2rem;">
  <div class="adm-stat-card">
    <div class="adm-stat-icon lab"><i class="fas fa-flask"></i></div>
    <span class="adm-stat-label">Month Total</span>
    <div class="adm-stat-value"><?=$stats['total_this_month']?></div>
    <div class="adm-stat-footer"><i class="fas fa-calendar"></i> Since <?=date('d M',strtotime($month_start))?></div>
  </div>
  <div class="adm-stat-card">
    <div class="adm-stat-icon lab-success"><i class="fas fa-clock"></i></div>
    <span class="adm-stat-label">Avg TAT</span>
    <div class="adm-stat-value"><?=$avg_tat?>h</div>
    <div class="adm-stat-footer"><i class="fas fa-hourglass-half"></i> This month</div>
  </div>
  <div class="adm-stat-card">
    <div class="adm-stat-icon lab-success"><i class="fas fa-check-circle"></i></div>
    <span class="adm-stat-label">Completed Today</span>
    <div class="adm-stat-value"><?=$stats['completed_today']?></div>
    <div class="adm-stat-footer"><?=$stats['completed_today']>=$stats['completed_yest']?'↑':'↓'?> vs yesterday (<?=$stats['completed_yest']?>)</div>
  </div>
  <div class="adm-stat-card">
    <div class="adm-stat-icon lab-danger"><i class="fas fa-exclamation-triangle"></i></div>
    <span class="adm-stat-label">Critical Flagged</span>
    <div class="adm-stat-value"><?=$interp_data['Critical']?></div>
    <div class="adm-stat-footer"><i class="fas fa-bolt"></i> This period</div>
  </div>
</div>

<!-- Charts Grid -->
<div class="adm-charts-grid">
  <div class="adm-chart-card">
    <h3><i class="fas fa-chart-line"></i> Weekly Order Volume</h3>
    <div style="position:relative;height:280px;"><canvas id="anaWeeklyChart"></canvas></div>
  </div>
  <div class="adm-chart-card">
    <h3><i class="fas fa-chart-pie"></i> Test Categories</h3>
    <div style="position:relative;height:280px;"><canvas id="anaCatChart"></canvas></div>
  </div>
  <div class="adm-chart-card">
    <h3><i class="fas fa-chart-bar"></i> Result Interpretations</h3>
    <div style="position:relative;height:280px;"><canvas id="anaInterpChart"></canvas></div>
  </div>
  <div class="adm-chart-card">
    <h3><i class="fas fa-chart-area"></i> Status Breakdown</h3>
    <div style="position:relative;height:280px;"><canvas id="anaStatusChart"></canvas></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
  // Weekly Line Chart
  if(document.getElementById('anaWeeklyChart')){
    new Chart(document.getElementById('anaWeeklyChart'),{type:'line',data:{labels:<?=$weekly_labels?>,datasets:[{label:'Orders',data:<?=$weekly_data?>,borderColor:'#8E44AD',backgroundColor:'rgba(142,68,173,.15)',fill:true,tension:.4,pointBackgroundColor:'#8E44AD',pointRadius:5,pointHoverRadius:8,borderWidth:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}}}});
  }
  // Category Pie
  if(document.getElementById('anaCatChart')){
    new Chart(document.getElementById('anaCatChart'),{type:'doughnut',data:{labels:<?=$cat_labels?>,datasets:[{data:<?=$cat_data?>,backgroundColor:['#8E44AD','#2980B9','#27AE60','#F39C12','#E74C3C','#1ABC9C','#34495E','#C0392B']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}}});
  }
  // Interpretation Bar
  if(document.getElementById('anaInterpChart')){
    new Chart(document.getElementById('anaInterpChart'),{type:'bar',data:{labels:<?=json_encode(array_keys($interp_data))?>,datasets:[{label:'Count',data:<?=json_encode(array_values($interp_data))?>,backgroundColor:['#27AE60','#F39C12','#E74C3C','#2980B9'],borderRadius:8,borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}}}});
  }
  // Status Doughnut
  if(document.getElementById('anaStatusChart')){
    new Chart(document.getElementById('anaStatusChart'),{type:'polarArea',data:{labels:<?=$status_labels?>,datasets:[{data:<?=$status_data?>,backgroundColor:['rgba(243,156,18,.6)','rgba(41,128,185,.6)','rgba(26,188,156,.6)','rgba(142,68,173,.6)','rgba(39,174,96,.6)','rgba(231,76,60,.6)']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}}});
  }
});
</script>
