<?php // TAB: REPORTS ?>
<div id="sec-reports" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-file-export"></i> Generate Reports</h2>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
    <!-- Report Builder -->
    <div class="adm-card" style="margin:0;">
      <div class="adm-card-header"><h3><i class="fas fa-sliders"></i> Report Builder</h3></div>
      <div style="padding:2rem;">
        <form id="formReport" onsubmit="buildReport(event)">
          <div class="form-group"><label>Report Type</label>
            <select class="form-control" id="reportType" name="report_type" required onchange="updateReportOptions(this.value)">
              <option value="">-- Select Type --</option>
              <option value="appointments">Appointments Summary</option>
              <option value="prescriptions">Prescription Report</option>
              <option value="lab_results">Lab Results Report</option>
              <option value="patient_history">Patient Visit History</option>
              <option value="medicine_inventory">Medicine Inventory Status</option>
              <option value="bed_management">Bed Management Summary</option>
              <option value="analytics_summary">Analytics Summary</option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Date From</label><input type="date" name="date_from" class="form-control" id="reportDateFrom" value="<?=$month_start?>"></div>
            <div class="form-group"><label>Date To</label><input type="date" name="date_to" class="form-control" id="reportDateTo" value="<?=$today?>"></div>
          </div>
          <div class="form-group" id="rpPatient"><label>Patient (Optional)</label>
            <select class="form-control" name="patient_id">
              <option value="">-- All Patients --</option>
              <?php foreach($patients as $pt):?>
              <option value="<?=$pt['id']?>"><?=htmlspecialchars($pt['name'])?> (<?=htmlspecialchars($pt['p_ref'])?>)</option>
              <?php endforeach;?>
            </select>
          </div>
          <div class="form-group" id="rpStatus"><label>Status Filter</label>
            <select class="form-control" name="status_filter">
              <option value="">All Statuses</option>
              <option value="Pending">Pending</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          <div class="form-group"><label>Export Format</label>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;margin-top:.3rem;">
              <?php foreach(['PDF'=>'fa-file-pdf','CSV'=>'fa-file-csv','Excel (XLSX)'=>'fa-file-excel'] as $fmt=>$ic):
                $val=strtolower(explode(' ',$fmt)[0]);
              ?>
              <label style="cursor:pointer;border:1.5px solid var(--border);border-radius:10px;padding:.8rem;text-align:center;transition:var(--transition);" class="fmt-option">
                <input type="radio" name="export_format" value="<?=$val?>" style="display:none;" <?=$val==='pdf'?'checked':''?> onchange="document.querySelectorAll('.fmt-option').forEach(e=>e.style.setProperty('border-color','var(--border)'));this.parentElement.style.setProperty('border-color','var(--role-accent)');">
                <i class="fas <?=$ic?>" style="display:block;font-size:1.6rem;margin-bottom:.3rem;color:var(--role-accent);"></i>
                <span style="font-size:1.1rem;"><?=$fmt?></span>
              </label>
              <?php endforeach;?>
            </div>
          </div>
          <button type="submit" class="btn-icon btn btn-primary" style="width:100%;justify-content:center;padding:1.2rem;"><span class="btn-text"><i class="fas fa-download"></i> Generate &amp; Download Report</span></button>
        </form>
      </div>
    </div>

    <!-- Recent Reports & Preview -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
      <!-- Quick Report Cards -->
      <div class="adm-card" style="margin:0;">
        <div class="adm-card-header"><h3><i class="fas fa-bolt"></i> Quick Reports (Today)</h3></div>
        <div style="padding:1.5rem;display:flex;flex-direction:column;gap:.7rem;">
          <?php foreach([
            ['appointments','Today\'s Appointments','fa-calendar-check','primary'],
            ['prescriptions','Pending Prescriptions','fa-prescription-bottle-medical','warning'],
            ['lab_results','Lab Results Summary','fa-flask','info'],
            ['medicine_inventory','Low Stock Report','fa-pills','danger']
          ] as [$type,$label,$icon,$col]):?>
          <form method="GET" action="/RMU-Medical-Management-System/php/dashboards/doctor_report.php" target="_blank">
            <input type="hidden" name="report_type" value="<?=$type?>">
            <input type="hidden" name="date_from" value="<?=$today?>">
            <input type="hidden" name="date_to" value="<?=$today?>">
            <input type="hidden" name="export_format" value="pdf">
            <button type="submit" class="btn btn-primary btn btn-<?=$col?> btn-sm" style="width:100%;justify-content:flex-start;gap:.8rem;"><span class="btn-text">
              <i class="fas <?=$icon?>"></i> <?=$label?>
            </span></button>
          </form>
          <?php endforeach;?>
        </div>
      </div>

      <!-- Report Tips -->
      <div class="adm-card" style="margin:0;">
        <div class="adm-card-header"><h3><i class="fas fa-lightbulb"></i> Report Tips</h3></div>
        <div style="padding:1.5rem;font-size:1.2rem;color:var(--text-secondary);line-height:2;">
          <div><i class="fas fa-file-pdf" style="color:#E74C3C;margin-right:.5rem;"></i><strong>PDF</strong> — Professional printable layout with header &amp; date</div>
          <div><i class="fas fa-file-csv" style="color:#27AE60;margin-right:.5rem;"></i><strong>CSV</strong> — Raw spreadsheet data for Excel import</div>
          <div><i class="fas fa-file-excel" style="color:#2F80ED;margin-right:.5rem;"></i><strong>XLSX</strong> — Formatted Excel workbook</div>
          <div style="margin-top:.8rem;padding:.8rem;background:var(--role-accent-light);border-radius:8px;color:var(--role-accent);">
            <i class="fas fa-info-circle"></i> All reports pull live data at generation time.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<style>@media(max-width:768px){#sec-reports .charts-grid,#sec-reports>div>div:first-child+div{margin-top:1.5rem;}#sec-reports>div{grid-template-columns:1fr!important;}}</style>
<script>
function updateReportOptions(v){
  const show=['appointments','prescriptions','lab_results','patient_history'];
  document.getElementById('rpPatient').style.display=show.includes(v)?'block':'none';
  document.getElementById('rpStatus').style.display=show.includes(v)?'block':'none';
}
function buildReport(e){
  e.preventDefault();
  const fd=new FormData(e.target);
  const params=new URLSearchParams(fd);
  window.open('/RMU-Medical-Management-System/php/dashboards/doctor_report.php?'+params.toString(),'_blank');
}
</script>
