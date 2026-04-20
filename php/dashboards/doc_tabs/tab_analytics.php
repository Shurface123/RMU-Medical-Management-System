<?php // TAB: ANALYTICS ?>
<div id="sec-analytics" class="dash-section">

<style>
.charts-grid-v2 { display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem; }
.adm-card-v2 { background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,.04); overflow:hidden; }
.adm-card-v2 .adm-card-header { padding:1.8rem 2rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface); }
.adm-card-v2 .adm-card-header h3 { font-size:1.4rem; font-weight:700; color:var(--text-primary); margin:0; }
</style>

  <div class="sec-header">
    <h2><i class="fas fa-chart-bar" style="color:var(--primary);"></i> Clinical Analytics</h2>
    <span style="font-size:1.2rem;color:var(--text-muted);">Real-time clinical intelligence</span>
  </div>

  <!-- Row 1 -->
  <div class="charts-grid-v2" style="grid-template-columns:2fr 1fr;">
    <div class="adm-card-v2">
      <div class="adm-card-header"><h3><i class="fas fa-chart-line" style="color:var(--primary);margin-right:.5rem;"></i> Workload — Last 7 Days</h3></div>
      <div style="padding:1.5rem;"><div class="chart-wrap"><canvas id="chartWeekly2"></canvas></div></div>
    </div>
    <div class="adm-card-v2">
      <div class="adm-card-header"><h3><i class="fas fa-chart-pie" style="color:var(--primary);margin-right:.5rem;"></i> Status Breakdown</h3></div>
      <div style="padding:1.5rem;"><div class="chart-wrap"><canvas id="chartStatus2"></canvas></div></div>
    </div>
  </div>

  <!-- Row 2 -->
  <div class="charts-grid-v2">
    <div class="adm-card-v2">
      <div class="adm-card-header"><h3><i class="fas fa-stethoscope" style="color:var(--primary);margin-right:.5rem;"></i> Top Diagnoses This Month</h3></div>
      <div style="padding:1.5rem;"><div style="height:220px;"><canvas id="chartDiagnoses"></canvas></div></div>
    </div>
    <div class="adm-card-v2">
      <div class="adm-card-header"><h3><i class="fas fa-bed" style="color:var(--primary);margin-right:.5rem;"></i> System Bed Occupancy</h3></div>
      <div style="padding:2rem;">
        <?php
        $total_beds=count($beds); $occ=count(array_filter($beds,fn($b)=>$b['bed_status']==='Occupied'));
        $pct=$total_beds>0?round($occ/$total_beds*100):0;
        ?>
        <div style="text-align:center;margin-bottom:1.5rem;">
          <div style="font-size:4rem;font-weight:800;color:<?=$pct>80?'var(--danger)':($pct>50?'var(--warning)':'var(--success)')?>"><?=$pct?>%</div>
          <div style="color:var(--text-muted);font-size:1.2rem;"><?=$occ?>/<?=$total_beds?> beds occupied</div>
        </div>
        <div style="background:var(--surface-2);border-radius:50px;height:16px;overflow:hidden;">
          <div style="height:100%;width:<?=$pct?>%;background:linear-gradient(90deg,var(--role-accent),var(--primary));border-radius:50px;transition:width .8s ease;"></div>
        </div>
        <?php foreach(['Available'=>'success','Occupied'=>'danger','Maintenance'=>'warning'] as $bs=>$bc):
          $cnt=count(array_filter($beds,fn($b)=>$b['bed_status']===$bs));
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem;font-size:1.2rem;">
          <span><span class="adm-badge adm-badge-<?=$bc?>"><?=$bs?></span></span>
          <strong><?=$cnt?></strong>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>

  <!-- Row 3 -->
  <div class="charts-grid-v2">
    <div class="adm-card-v2">
      <div class="adm-card-header"><h3><i class="fas fa-pills" style="color:var(--primary);margin-right:.5rem;"></i> Rx Fulfillment Status</h3></div>
      <div style="padding:2rem;">
        <?php
        $rx_pending=count(array_filter($prescriptions,fn($r)=>$r['status']==='Pending'));
        $rx_dispensed=count(array_filter($prescriptions,fn($r)=>$r['status']==='Dispensed'));
        $rx_total=count($prescriptions);
        ?>
        <div style="display:flex;gap:1rem;">
          <div style="flex:1;background:var(--surface-2);border-radius:12px;padding:1.5rem;text-align:center;">
             <div style="font-size:2.5rem;font-weight:800;color:var(--primary);"><?=$rx_total?></div>
             <div style="font-size:1.1rem;color:var(--text-secondary);font-weight:600;">Total</div>
          </div>
          <div style="flex:1;background:var(--surface-2);border-radius:12px;padding:1.5rem;text-align:center;">
             <div style="font-size:2.5rem;font-weight:800;color:var(--warning);"><?=$rx_pending?></div>
             <div style="font-size:1.1rem;color:var(--text-secondary);font-weight:600;">Pending</div>
          </div>
          <div style="flex:1;background:var(--surface-2);border-radius:12px;padding:1.5rem;text-align:center;">
             <div style="font-size:2.5rem;font-weight:800;color:var(--success);"><?=$rx_dispensed?></div>
             <div style="font-size:1.1rem;color:var(--text-secondary);font-weight:600;">Dispensed</div>
          </div>
        </div>
      </div>
    </div>
    <div class="adm-card-v2" style="border:none;background:transparent;box-shadow:none;"></div>
  </div>
</div>
<script>
// Analytics tab duplicates the charts (overview uses chartWeekly, analytics uses chartWeekly2)
document.addEventListener('DOMContentLoaded',()=>{
  const isDark=document.documentElement.getAttribute('data-theme')==='dark';
  const gridColor=isDark?'rgba(255,255,255,.08)':'rgba(0,0,0,.07)';
  const textColor=isDark?'#9AAECB':'#5A6A85';
  const w2=document.getElementById('chartWeekly2');
  if(w2) new Chart(w2,{type:'line',data:{labels:weeklyLabels,datasets:[{label:'Appointments',data:weeklyData,backgroundColor:'rgba(26,188,156,.15)',borderColor:'#1abc9c',borderWidth:2.5,pointRadius:5,pointBackgroundColor:'#1abc9c',fill:true,tension:.4}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1,color:textColor},grid:{color:gridColor}},x:{ticks:{color:textColor},grid:{display:false}}}}});
  const s2=document.getElementById('chartStatus2');
  if(s2) new Chart(s2,{type:'doughnut',data:{labels:statusLabels,datasets:[{data:statusData,backgroundColor:['#F39C12','#27AE60','#2F80ED','#E74C3C','#1abc9c'],borderWidth:0,hoverOffset:6}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:textColor,font:{size:12}}}}}});
});
</script>
