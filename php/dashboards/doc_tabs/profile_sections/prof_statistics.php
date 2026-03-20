<?php // SECTION F: Statistics Panel ?>
<div id="prof-statistics" class="prof-section" style="display:none;">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-chart-bar"></i> Patient Load & Statistics</h3>
      <button class="adm-btn adm-btn-sm" onclick="loadStats()"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>
    <div style="padding:2rem;">
      <div id="statsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.2rem;margin-bottom:2rem;">
        <div class="stat-mini"><i class="fas fa-users" style="color:var(--role-accent);"></i><div><span class="stat-val" id="st-totalPat">—</span><span class="stat-lbl">Total Patients</span></div></div>
        <div class="stat-mini"><i class="fas fa-calendar-check" style="color:var(--success);"></i><div><span class="stat-val" id="st-totalAppts">—</span><span class="stat-lbl">Completed Appointments</span></div></div>
        <div class="stat-mini"><i class="fas fa-calendar" style="color:var(--info);"></i><div><span class="stat-val" id="st-monthAppts">—</span><span class="stat-lbl">This Month Appointments</span></div></div>
        <div class="stat-mini"><i class="fas fa-prescription" style="color:#9B59B6;"></i><div><span class="stat-val" id="st-totalRx">—</span><span class="stat-lbl">Total Prescriptions</span></div></div>
        <div class="stat-mini"><i class="fas fa-prescription" style="color:#E67E22;"></i><div><span class="stat-val" id="st-monthRx">—</span><span class="stat-lbl">Rx This Month</span></div></div>
        <div class="stat-mini"><i class="fas fa-fire" style="color:var(--danger);"></i><div><span class="stat-val" id="st-busiest">—</span><span class="stat-lbl">Busiest Day</span></div></div>
        <div class="stat-mini"><i class="fas fa-clock" style="color:var(--warning);"></i><div><span class="stat-val" id="st-hours">—</span><span class="stat-lbl">Consult Hours (Month)</span></div></div>
      </div>
      <style>.stat-mini{display:flex;align-items:center;gap:1rem;padding:1.2rem;background:var(--surface-2);border-radius:12px;}.stat-mini i{font-size:2rem;}.stat-val{display:block;font-size:1.8rem;font-weight:800;line-height:1.1;}.stat-lbl{font-size:1.1rem;color:var(--text-muted);}</style>
      <!-- Chart -->
      <h4 style="margin-bottom:1rem;"><i class="fas fa-chart-line" style="color:var(--role-accent);"></i> Appointments — Last 6 Months</h4>
      <div style="background:var(--surface-2);border-radius:12px;padding:1.5rem;">
        <canvas id="statsChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>
<script>
let statsLoaded=false;
async function loadStats(){
  const res=await(await fetch(PROF_API+'?action=get_stats')).json();
  if(!res.success)return;
  const s=res.stats;
  document.getElementById('st-totalPat').textContent=s.total_patients;
  document.getElementById('st-totalAppts').textContent=s.total_appointments;
  document.getElementById('st-monthAppts').textContent=s.month_appointments;
  document.getElementById('st-totalRx').textContent=s.total_prescriptions;
  document.getElementById('st-monthRx').textContent=s.month_prescriptions;
  document.getElementById('st-busiest').textContent=s.busiest_day;
  document.getElementById('st-hours').textContent=s.month_consult_hours+'h';
  // Draw chart
  if(s.chart&&s.chart.length&&typeof Chart!=='undefined'){
    const ctx=document.getElementById('statsChart').getContext('2d');
    new Chart(ctx,{type:'bar',data:{labels:s.chart.map(c=>c.label),datasets:[{label:'Appointments',data:s.chart.map(c=>c.value),backgroundColor:'rgba(26,188,156,.6)',borderColor:'rgba(26,188,156,1)',borderWidth:1,borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
  }
  statsLoaded=true;
}
// Auto-load when section shown
const origShowProf=window.showProfSection;
window.showProfSection=function(id,btn){origShowProf(id,btn);if(id==='statistics'&&!statsLoaded)loadStats();};
</script>
