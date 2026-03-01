<?php // TAB: ANALYTICS ?>
<div id="sec-analytics" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-chart-bar"></i> Analytics</h2>
    <span style="font-size:1.2rem;color:var(--text-muted);">Live data from your records</span>
  </div>

  <!-- Row 1 -->
  <div class="charts-grid" style="grid-template-columns:2fr 1fr;">
    <div class="adm-card">
      <div class="adm-card-header"><h3><i class="fas fa-chart-line"></i> Appointments — Last 7 Days</h3></div>
      <div style="padding:1.5rem;"><div class="chart-wrap"><canvas id="chartWeekly2"></canvas></div></div>
    </div>
    <div class="adm-card">
      <div class="adm-card-header"><h3><i class="fas fa-chart-pie"></i> Status Breakdown</h3></div>
      <div style="padding:1.5rem;"><div class="chart-wrap"><canvas id="chartStatus2"></canvas></div></div>
    </div>
  </div>

  <!-- Row 2 -->
  <div class="charts-grid">
    <div class="adm-card">
      <div class="adm-card-header"><h3><i class="fas fa-stethoscope"></i> Top Diagnoses This Month</h3></div>
      <div style="padding:1.5rem;"><div style="height:220px;"><canvas id="chartDiagnoses"></canvas></div></div>
    </div>
    <div class="adm-card">
      <div class="adm-card-header"><h3><i class="fas fa-bed"></i> Bed Occupancy</h3></div>
      <div style="padding:1.5rem;">
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
  <div class="charts-grid">
    <div class="adm-card">
      <div class="adm-card-header"><h3><i class="fas fa-pills"></i> Prescription Status</h3></div>
      <div style="padding:1.5rem;">
        <?php
        $rx_pending=count(array_filter($prescriptions,fn($r)=>$r['status']==='Pending'));
        $rx_dispensed=count(array_filter($prescriptions,fn($r)=>$r['status']==='Dispensed'));
        $rx_total=count($prescriptions);
        ?>
        <div class="adm-summary-strip" style="grid-template-columns:repeat(3,1fr);margin:0;">
          <div class="adm-mini-card"><div class="adm-mini-card-num blue"><?=$rx_total?></div><div class="adm-mini-card-label">Total</div></div>
          <div class="adm-mini-card"><div class="adm-mini-card-num orange"><?=$rx_pending?></div><div class="adm-mini-card-label">Pending</div></div>
          <div class="adm-mini-card"><div class="adm-mini-card-num green"><?=$rx_dispensed?></div><div class="adm-mini-card-label">Dispensed</div></div>
        </div>
      </div>
    </div>
    <div class="adm-card">
      <div class="adm-card-header"><h3><i class="fas fa-flask"></i> Lab Request Status</h3></div>
      <div style="padding:1.5rem;">
        <?php
        $lb_pending=count(array_filter($lab_requests,fn($r)=>$r['status']==='Pending'));
        $lb_done=count(array_filter($lab_requests,fn($r)=>in_array($r['status'],['Completed','Reviewed'])));
        $lb_total=count($lab_requests);
        ?>
        <div class="adm-summary-strip" style="grid-template-columns:repeat(3,1fr);margin:0;">
          <div class="adm-mini-card"><div class="adm-mini-card-num blue"><?=$lb_total?></div><div class="adm-mini-card-label">Total</div></div>
          <div class="adm-mini-card"><div class="adm-mini-card-num orange"><?=$lb_pending?></div><div class="adm-mini-card-label">Pending</div></div>
          <div class="adm-mini-card"><div class="adm-mini-card-num green"><?=$lb_done?></div><div class="adm-mini-card-label">Reviewed</div></div>
        </div>
      </div>
    </div>
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
