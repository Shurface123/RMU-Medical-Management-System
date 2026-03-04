<!-- ═══════════════════════════════════════════════════════════
     MODULE 11: ANALYTICS — tab_analytics.php
     ═══════════════════════════════════════════════════════════ -->
<div id="sec-analytics" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-chart-bar"></i> Analytics</h2>
    <div style="display:flex;gap:.8rem;align-items:center;">
      <label style="font-size:1.2rem;font-weight:600;">Range:</label>
      <select id="analyticRange" class="form-control" style="width:auto;" onchange="loadAnalytics()">
        <option value="7d">Last 7 Days</option><option value="30d">Last 30 Days</option><option value="90d">Last 90 Days</option>
      </select>
    </div>
  </div>

  <div class="charts-grid">
    <!-- Vitals Recorded Per Day -->
    <div class="info-card"><h4 style="margin-bottom:.8rem;"><i class="fas fa-heartbeat" style="color:var(--role-accent);"></i> Vitals Recorded Per Day</h4><div class="chart-wrap"><canvas id="chartVitalsDaily"></canvas></div></div>

    <!-- Medication Compliance -->
    <div class="info-card"><h4 style="margin-bottom:.8rem;"><i class="fas fa-pills" style="color:var(--primary);"></i> Medication Admin Compliance</h4><div class="chart-wrap"><canvas id="chartMedCompliance"></canvas></div></div>

    <!-- Task Completion by Shift -->
    <div class="info-card"><h4 style="margin-bottom:.8rem;"><i class="fas fa-clipboard-list" style="color:var(--success);"></i> Task Completion Rate</h4><div class="chart-wrap"><canvas id="chartTaskRate"></canvas></div></div>

    <!-- Emergency Alerts Over Time -->
    <div class="info-card"><h4 style="margin-bottom:.8rem;"><i class="fas fa-triangle-exclamation" style="color:var(--danger);"></i> Emergency Alerts</h4><div class="chart-wrap"><canvas id="chartEmergency"></canvas></div></div>

    <!-- Fluid Balance Trend -->
    <div class="info-card"><h4 style="margin-bottom:.8rem;"><i class="fas fa-droplet" style="color:var(--info);"></i> Fluid Balance Trend</h4><div class="chart-wrap"><canvas id="chartFluidBalance"></canvas></div></div>

    <!-- Bed Occupancy -->
    <div class="info-card"><h4 style="margin-bottom:.8rem;"><i class="fas fa-bed" style="color:var(--warning);"></i> Bed Occupancy Rate</h4><div class="chart-wrap"><canvas id="chartBedOccupancy"></canvas></div></div>
  </div>

  <!-- Patient Education This Month -->
  <div class="info-card" style="margin-top:1rem;">
    <h4 style="margin-bottom:.8rem;"><i class="fas fa-book-medical" style="color:var(--role-accent);"></i> Patient Education Completed (This Month)</h4>
    <div class="chart-wrap" style="height:200px;"><canvas id="chartEducation"></canvas></div>
  </div>
</div>

<script>
let analyticsCharts = {};

async function loadAnalytics(){
  const range = document.getElementById('analyticRange')?.value || '7d';
  const r = await nurseAction({action:'get_analytics', range: range});
  if(!r.success) return;
  const d = r.data;

  // Destroy old charts
  Object.values(analyticsCharts).forEach(c=>c&&c.destroy&&c.destroy());
  analyticsCharts = {};

  const mkChart = (id,type,labels,datasets,opts={}) => {
    const ctx = document.getElementById(id);
    if(!ctx) return;
    analyticsCharts[id] = new Chart(ctx,{type,data:{labels,datasets},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:datasets.length>1,position:'bottom',labels:{font:{size:11}}}},...opts}});
  };

  // 1. Vitals daily
  mkChart('chartVitalsDaily','bar',d.vitals_daily?.labels||[],[{label:'Vitals Recorded',data:d.vitals_daily?.data||[],backgroundColor:'#E91E6344',borderColor:'#E91E63',borderWidth:2}]);

  // 2. Medication compliance (doughnut)
  mkChart('chartMedCompliance','doughnut',['Administered','Missed','Refused','Held'],[{data:[d.med_admin||0,d.med_missed||0,d.med_refused||0,d.med_held||0],backgroundColor:['#27AE60','#E74C3C','#F39C12','#3498DB']}],{scales:{}});

  // 3. Task completion rate
  mkChart('chartTaskRate','bar',d.task_rate?.labels||[],[{label:'Completed',data:d.task_rate?.completed||[],backgroundColor:'#27AE6044',borderColor:'#27AE60',borderWidth:2},{label:'Pending/Overdue',data:d.task_rate?.pending||[],backgroundColor:'#E74C3C44',borderColor:'#E74C3C',borderWidth:2}]);

  // 4. Emergency alerts
  mkChart('chartEmergency','line',d.emergency?.labels||[],[{label:'Alerts',data:d.emergency?.data||[],borderColor:'#E74C3C',backgroundColor:'#E74C3C22',tension:.4,fill:true}]);

  // 5. Fluid balance trend
  mkChart('chartFluidBalance','line',d.fluid?.labels||[],[{label:'Intake',data:d.fluid?.intake||[],borderColor:'#3498DB',tension:.3},{label:'Output',data:d.fluid?.output||[],borderColor:'#F39C12',tension:.3}]);

  // 6. Bed occupancy (doughnut)
  mkChart('chartBedOccupancy','doughnut',['Occupied','Available','Maintenance'],[{data:[d.beds_occupied||0,d.beds_available||0,d.beds_maintenance||0],backgroundColor:['#3498DB','#27AE60','#95A5A6']}],{scales:{}});

  // 7. Education
  mkChart('chartEducation','bar',d.education?.labels||[],[{label:'Education Records',data:d.education?.data||[],backgroundColor:'#E91E6344',borderColor:'#E91E63',borderWidth:2}]);
}

// Load analytics when tab is shown
document.addEventListener('DOMContentLoaded',()=>{
  const observer = new MutationObserver(()=>{
    if(document.getElementById('sec-analytics')?.classList.contains('active') && !analyticsCharts['chartVitalsDaily']){
      loadAnalytics();
    }
  });
  observer.observe(document.getElementById('sec-analytics'),{attributes:true,attributeFilter:['class']});
});
</script>
