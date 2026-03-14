<!-- ═══════════════ MODULE 6: EQUIPMENT MANAGEMENT ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-tools" style="color:var(--role-accent);margin-right:.6rem;"></i> Equipment Management</h1>
    <p>Equipment register, calibration tracking, maintenance scheduling, and usage logging</p>
  </div>
  <button class="adm-btn adm-btn-primary" onclick="openModal('addEquipModal')"><i class="fas fa-plus-circle"></i> Add Equipment</button>
</div>

<!-- Status Summary -->
<div class="adm-summary-strip">
  <?php
  $eq_stats=['Operational'=>0,'Calibration Due'=>0,'Maintenance'=>0,'Out of Service'=>0,'Decommissioned'=>0];
  foreach($equipment as $e) if(isset($eq_stats[$e['status']])) $eq_stats[$e['status']]++;
  $ecolors=['Operational'=>'green','Calibration Due'=>'orange','Maintenance'=>'orange','Out of Service'=>'red','Decommissioned'=>''];
  foreach($eq_stats as $st=>$cnt):?>
  <div class="adm-mini-card"><div class="adm-mini-card-num <?=$ecolors[$st]?>"><?=$cnt?></div><div class="adm-mini-card-label"><?=$st?></div></div>
  <?php endforeach;?>
</div>

<div class="filter-tabs">
  <span class="ftab active" onclick="filterEquip('all',this)">All</span>
  <?php foreach(array_keys($eq_stats) as $st):if($eq_stats[$st]>0):?><span class="ftab" onclick="filterEquip('<?=$st?>',this)"><?=$st?></span><?php endif;endforeach;?>
</div>

<div class="adm-card">
  <div class="adm-card-body" style="padding:0;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="equipTable">
        <thead><tr><th>Name</th><th>Model / Serial</th><th>Manufacturer</th><th>Status</th><th>Last Calibration</th><th>Next Calibration</th><th>Location</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($equipment)):?>
          <tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-tools" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>No equipment registered</td></tr>
        <?php else: foreach($equipment as $eq):
          $st_cls=['Operational'=>'success','Calibration Due'=>'warning','Maintenance'=>'warning','Out of Service'=>'danger','Decommissioned'=>'info'][$eq['status']]??'info';
          $overdue=$eq['next_calibration_date']&&$eq['next_calibration_date']<$today;
        ?>
          <tr class="<?=$overdue?'row-danger':($eq['next_calibration_date']&&$eq['next_calibration_date']<=date('Y-m-d',strtotime('+7 days'))?'row-warning':'')?>" data-status="<?=e($eq['status'])?>">
            <td><strong><?=e($eq['name'])?></strong></td>
            <td style="font-size:1.15rem;"><?=e($eq['model']??'—')?><br><span style="font-family:monospace;color:var(--text-muted);"><?=e($eq['serial_number']??'—')?></span></td>
            <td><?=e($eq['manufacturer']??'—')?></td>
            <td><span class="adm-badge adm-badge-<?=$st_cls?>"><?=e($eq['status'])?></span><?php if($overdue):?><br><span class="adm-badge adm-badge-danger" style="font-size:.9rem;animation:pulse-emergency 2s infinite;">⚠️ OVERDUE</span><?php endif;?></td>
            <td><?=$eq['last_calibration_date']?date('d M Y',strtotime($eq['last_calibration_date'])):'Never'?></td>
            <td style="<?=$overdue?'color:var(--danger);font-weight:700;':''?>"><?=$eq['next_calibration_date']?date('d M Y',strtotime($eq['next_calibration_date'])):'—'?></td>
            <td><?=e($eq['location']??'—')?></td>
              <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick='viewEquipDetail(<?=json_encode($eq)?>)' title="Details"><i class="fas fa-eye"></i></button>
              <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick='editEquip(<?=json_encode($eq)?>)' title="Edit"><i class="fas fa-edit"></i></button>
              <button class="adm-btn adm-btn-sm adm-btn-primary" onclick="openMaintenanceModal(<?=$eq['id']?>,'<?=e($eq['name'])?>')" title="Log Maintenance"><i class="fas fa-wrench"></i></button>
              <button class="adm-btn adm-btn-sm adm-btn-success" onclick="logCalibration(<?=$eq['id']?>)" title="Log Calibration"><i class="fas fa-bullseye"></i></button>
              <!-- Phase 6: QC Trends -->
              <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick="viewQCTrends(<?=$eq['id']?>,'<?=e($eq['name'])?>')" title="View QC Trends (Levey-Jennings)"><i class="fas fa-chart-line" style="color:var(--role-accent);"></i></button>
            </td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Equipment Modal -->
<div class="modal-bg" id="addEquipModal"><div class="modal-box wide">
  <div class="modal-header"><h3 id="equipModalTitle"><i class="fas fa-tools"></i> Add Equipment</h3><button class="modal-close" onclick="closeModal('addEquipModal')">&times;</button></div>
  <input type="hidden" id="eq_id">
  <div class="form-row"><div class="form-group"><label>Name *</label><input id="eq_name" class="form-control"></div><div class="form-group"><label>Model</label><input id="eq_model" class="form-control"></div></div>
  <div class="form-row"><div class="form-group"><label>Serial Number</label><input id="eq_serial" class="form-control"></div><div class="form-group"><label>Manufacturer</label><input id="eq_mfr" class="form-control"></div></div>
  <div class="form-row"><div class="form-group"><label>Category</label><select id="eq_cat" class="form-control"><option>Analyzer</option><option>Centrifuge</option><option>Microscope</option><option>Incubator</option><option>Refrigerator</option><option>Other</option></select></div><div class="form-group"><label>Location</label><input id="eq_loc" class="form-control"></div></div>
  <div class="form-row"><div class="form-group"><label>Purchase Date</label><input id="eq_pdate" type="date" class="form-control"></div><div class="form-group"><label>Warranty Expiry</label><input id="eq_warranty" type="date" class="form-control"></div></div>
  <div class="form-row"><div class="form-group"><label>Status</label><select id="eq_status" class="form-control"><option>Operational</option><option>Calibration Due</option><option>Maintenance</option><option>Out of Service</option><option>Decommissioned</option></select></div><div class="form-group"><label>Next Calibration</label><input id="eq_nextcal" type="date" class="form-control"></div></div>
  <div class="form-group"><label>Notes</label><textarea id="eq_notes" class="form-control" rows="2"></textarea></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="saveEquip()"><i class="fas fa-save"></i> Save</button>
</div></div>

<!-- Maintenance Modal -->
<div class="modal-bg" id="maintenanceModal"><div class="modal-box">
  <div class="modal-header"><h3><i class="fas fa-wrench"></i> <span id="maintTitle">Log Maintenance</span></h3><button class="modal-close" onclick="closeModal('maintenanceModal')">&times;</button></div>
  <input type="hidden" id="maint_eq_id">
  <div class="form-group"><label>Type</label><select id="maint_type" class="form-control"><option>Calibration</option><option>Repair</option><option>Service</option><option>Inspection</option></select></div>
  <div class="form-row"><div class="form-group"><label>Date Performed</label><input id="maint_date" type="datetime-local" class="form-control" value="<?=date('Y-m-d\TH:i')?>"></div><div class="form-group"><label>Next Due Date</label><input id="maint_next" type="date" class="form-control"></div></div>
  <div class="form-group"><label>Findings</label><textarea id="maint_findings" class="form-control" rows="2"></textarea></div>
  <div class="form-group"><label>Cost (GH₵)</label><input id="maint_cost" type="number" step="0.01" class="form-control"></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="saveMaintenance()"><i class="fas fa-save"></i> Save</button>
</div></div>

<!-- QC Trends Chart Modal (Phase 6) -->
<div class="modal-bg" id="qcTrendsModal">
  <div class="modal-box wide">
    <div class="modal-header">
      <h3><i class="fas fa-chart-line" style="color:var(--role-accent);"></i> <span id="qcChartTitle">QC Trends</span></h3>
      <button class="modal-close" onclick="closeModal('qcTrendsModal')">&times;</button>
    </div>
    <div style="position:relative;height:350px;width:100%;">
      <canvas id="qcLeveyJenningsChart"></canvas>
    </div>
    <p style="text-align:center;color:var(--text-muted);margin-top:1rem;font-size:1.1rem;">Levey-Jennings Chart: Tracks daily QC results against +/- 2SD action limits to identify systematic errors.</p>
  </div>
</div>

<script>
let qcJChart = null; // Global instance for the Chart.js object

async function viewQCTrends(eqId, eqName) {
    document.getElementById('qcChartTitle').textContent = 'QC Trends: ' + eqName;
    openModal('qcTrendsModal');
    
    try {
        const r = await fetch(BASE + '/php/api/endpoints/qc_trends.php?equipment_id=' + eqId);
        const res = await r.json();
        
        if (!res.success) {
            showToast(res.message, 'error');
            return;
        }
        
        const data = res.data;
        if(data.dates.length === 0) {
            showToast('No recent QC data available for this equipment.', 'warning');
            return;
        }

        const ctx = document.getElementById('qcLeveyJenningsChart').getContext('2d');
        if(qcJChart) qcJChart.destroy();
        
        qcJChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.dates,
                datasets: [
                    {
                        label: 'Result Value',
                        data: data.results,
                        borderColor: '#2980B9', // Blue
                        backgroundColor: '#2980B9',
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        fill: false,
                        tension: 0
                    },
                    {
                        label: 'Mean',
                        data: data.mean,
                        borderColor: '#27AE60', // Green
                        borderDash: [5, 5],
                        borderWidth: 2,
                        fill: false,
                        pointRadius: 0
                    },
                    {
                        label: '+2 SD',
                        data: data.sd_plus_2,
                        borderColor: '#F39C12', // Orange
                        borderDash: [5, 5],
                        borderWidth: 2,
                        fill: false,
                        pointRadius: 0
                    },
                    {
                        label: '-2 SD',
                        data: data.sd_minus_2,
                        borderColor: '#F39C12', // Orange
                        borderDash: [5, 5],
                        borderWidth: 2,
                        fill: false,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: {
                        title: { display: true, text: 'QC Result Value' }
                    }
                }
            }
        });
        
    } catch(e) {
        showToast('Failed to load QC data', 'error');
        console.error(e);
    }
}

function filterEquip(st,el){el.parentNode.querySelectorAll('.ftab').forEach(f=>f.classList.remove('active'));el.classList.add('active');document.querySelectorAll('#equipTable tbody tr').forEach(r=>{r.style.display=(st==='all'||r.dataset.status===st)?'':'none';});}
function editEquip(e){document.getElementById('equipModalTitle').innerHTML='<i class="fas fa-tools"></i> Edit Equipment';document.getElementById('eq_id').value=e.id;document.getElementById('eq_name').value=e.name;document.getElementById('eq_model').value=e.model||'';document.getElementById('eq_serial').value=e.serial_number||'';document.getElementById('eq_mfr').value=e.manufacturer||'';document.getElementById('eq_cat').value=e.category||'Other';document.getElementById('eq_loc').value=e.location||'';document.getElementById('eq_pdate').value=e.purchase_date||'';document.getElementById('eq_warranty').value=e.warranty_expiry||'';document.getElementById('eq_status').value=e.status;document.getElementById('eq_nextcal').value=e.next_calibration_date||'';document.getElementById('eq_notes').value=e.notes||'';openModal('addEquipModal');}
function viewEquipDetail(e){
  let h='<div style="font-size:1.3rem;display:grid;gap:1rem;">';h+='<div style="display:flex;justify-content:space-between;"><h3 style="font-weight:800;">'+e.name+'</h3><span class="adm-badge adm-badge-'+(e.status==='Operational'?'success':'warning')+'">'+e.status+'</span></div>';
  h+='<div class="form-row"><div><strong>Model:</strong> '+(e.model||'—')+'</div><div><strong>Serial:</strong> '+(e.serial_number||'—')+'</div></div>';
  h+='<div class="form-row"><div><strong>Manufacturer:</strong> '+(e.manufacturer||'—')+'</div><div><strong>Location:</strong> '+(e.location||'—')+'</div></div>';
  h+='<div class="form-row"><div><strong>Last Calibration:</strong> '+(e.last_calibration_date||'Never')+'</div><div><strong>Next Calibration:</strong> '+(e.next_calibration_date||'—')+'</div></div>';
  h+='<div class="form-row"><div><strong>Last Maintenance:</strong> '+(e.last_maintenance_date||'Never')+'</div><div><strong>Warranty:</strong> '+(e.warranty_expiry||'—')+'</div></div>';
  if(e.notes) h+='<div><strong>Notes:</strong> '+e.notes+'</div>';h+='</div>';
  document.getElementById('orderDetailBody').innerHTML=h;openModal('orderDetailModal');
}
async function saveEquip(){if(!validateForm({eq_name:'Equipment Name'}))return;const r=await labAction({action:'save_equipment',id:document.getElementById('eq_id').value,name:document.getElementById('eq_name').value,model:document.getElementById('eq_model').value,serial_number:document.getElementById('eq_serial').value,manufacturer:document.getElementById('eq_mfr').value,category:document.getElementById('eq_cat').value,location:document.getElementById('eq_loc').value,purchase_date:document.getElementById('eq_pdate').value,warranty_expiry:document.getElementById('eq_warranty').value,status:document.getElementById('eq_status').value,next_calibration_date:document.getElementById('eq_nextcal').value,notes:document.getElementById('eq_notes').value});showToast(r.message,r.success?'success':'error');if(r.success){closeModal('addEquipModal');setTimeout(()=>location.reload(),800);}}
function openMaintenanceModal(id,name){document.getElementById('maint_eq_id').value=id;document.getElementById('maintTitle').textContent='Maintenance — '+name;openModal('maintenanceModal');}
async function saveMaintenance(){const r=await labAction({action:'log_maintenance',equipment_id:document.getElementById('maint_eq_id').value,maintenance_type:document.getElementById('maint_type').value,performed_at:document.getElementById('maint_date').value,next_due:document.getElementById('maint_next').value,findings:document.getElementById('maint_findings').value,cost:document.getElementById('maint_cost').value});showToast(r.message,r.success?'success':'error');if(r.success){closeModal('maintenanceModal');setTimeout(()=>location.reload(),800);}}
async function logCalibration(id){const next=prompt('Next calibration due date (YYYY-MM-DD):');if(!next)return;const r=await labAction({action:'log_maintenance',equipment_id:id,maintenance_type:'Calibration',performed_at:new Date().toISOString().slice(0,16).replace('T',' '),next_due:next,findings:'Calibration completed',cost:0});showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);}
</script>
