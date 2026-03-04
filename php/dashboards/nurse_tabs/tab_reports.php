<!-- ═══════════════════════════════════════════════════════════
     MODULE 12: REPORTS — tab_reports.php
     ═══════════════════════════════════════════════════════════ -->
<div id="sec-reports" class="dash-section">
  <div class="sec-header"><h2><i class="fas fa-file-export"></i> Reports</h2></div>

  <div class="cards-grid">
    <!-- Report Cards -->
    <?php
    $report_types = [
      ['id'=>'vitals','icon'=>'fa-heartbeat','color'=>'#E91E63','title'=>'Vital Signs Report','desc'=>'BP, HR, Temp, SpO2 readings by patient & date range'],
      ['id'=>'medications','icon'=>'fa-pills','color'=>'#2980B9','title'=>'Medication Admin Report','desc'=>'Administered, missed, refused medications by patient'],
      ['id'=>'notes','icon'=>'fa-notes-medical','color'=>'#F39C12','title'=>'Nursing Notes Report','desc'=>'All nursing notes by patient, shift, date range'],
      ['id'=>'fluids','icon'=>'fa-droplet','color'=>'#3498DB','title'=>'Fluid Balance Report','desc'=>'Intake/output/net balance by patient and date'],
      ['id'=>'tasks','icon'=>'fa-clipboard-list','color'=>'#27AE60','title'=>'Task Completion Report','desc'=>'Task completion rates by shift and date range'],
      ['id'=>'emergency','icon'=>'fa-triangle-exclamation','color'=>'#E74C3C','title'=>'Emergency Alerts Report','desc'=>'All emergency alerts, response times, outcomes'],
      ['id'=>'wounds','icon'=>'fa-band-aid','color'=>'#9B59B6','title'=>'Wound Care Report','desc'=>'Wound assessments and healing progress by patient'],
      ['id'=>'handover','icon'=>'fa-exchange-alt','color'=>'#1ABC9C','title'=>'Shift Handover Report','desc'=>'Handover summaries and acknowledgements'],
      ['id'=>'education','icon'=>'fa-book-medical','color'=>'#E67E22','title'=>'Education & Discharge Report','desc'=>'Patient education records and discharge instructions'],
    ];
    foreach($report_types as $rt):
    ?>
    <div class="info-card" style="cursor:pointer;border-left:3px solid <?=$rt['color']?>;" onclick="openReportConfig('<?=$rt['id']?>','<?=e($rt['title'])?>')">
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:.8rem;">
        <div style="width:48px;height:48px;border-radius:12px;background:<?=$rt['color']?>15;color:<?=$rt['color']?>;display:flex;align-items:center;justify-content:center;font-size:1.6rem;"><i class="fas <?=$rt['icon']?>"></i></div>
        <div><div style="font-size:1.4rem;font-weight:700;"><?=$rt['title']?></div><div style="font-size:1.1rem;color:var(--text-secondary);"><?=$rt['desc']?></div></div>
      </div>
      <div style="display:flex;gap:.5rem;">
        <span class="badge badge-secondary"><i class="fas fa-file-pdf"></i> PDF</span>
        <span class="badge badge-secondary"><i class="fas fa-file-csv"></i> CSV</span>
        <span class="badge badge-secondary"><i class="fas fa-file-excel"></i> Excel</span>
      </div>
    </div>
    <?php endforeach;?>
  </div>
</div>

<!-- ═══════ REPORT CONFIG MODAL ═══════ -->
<div class="modal-bg" id="reportConfigModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-file-export" style="color:var(--role-accent);"></i> <span id="rpt_title">Generate Report</span></h3><button class="modal-close" onclick="closeModal('reportConfigModal')"><i class="fas fa-times"></i></button></div>
    <input type="hidden" id="rpt_type">
    <div class="form-group"><label>Patient (optional)</label>
      <select id="rpt_patient" class="form-control"><option value="">All Patients</option>
        <?php foreach($all_patients_for_vitals as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['patient_name'])?></option><?php endforeach;?></select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Date From *</label><input id="rpt_from" type="date" class="form-control" value="<?=date('Y-m-01')?>"></div>
      <div class="form-group"><label>Date To *</label><input id="rpt_to" type="date" class="form-control" value="<?=$today?>"></div>
    </div>
    <div class="form-group"><label>Export Format *</label>
      <select id="rpt_format" class="form-control"><option value="pdf">PDF</option><option value="csv">CSV</option><option value="xlsx">Excel (XLSX)</option></select>
    </div>
    <button class="btn btn-primary" onclick="generateReport()" style="width:100%;"><i class="fas fa-download"></i> Generate & Download</button>
  </div>
</div>

<script>
function openReportConfig(type,title){
  document.getElementById('rpt_type').value=type;
  document.getElementById('rpt_title').textContent=title;
  openModal('reportConfigModal');
}

async function generateReport(){
  const type=document.getElementById('rpt_type').value;
  const format=document.getElementById('rpt_format').value;
  const from=document.getElementById('rpt_from').value;
  const to=document.getElementById('rpt_to').value;
  const patient=document.getElementById('rpt_patient').value;

  if(!from||!to){showToast('Please select date range','error');return;}

  showToast('Generating report...','info');
  const r=await nurseAction({action:'generate_report',report_type:type,format:format,date_from:from,date_to:to,patient_id:patient});
  if(r.success && r.download_url){
    const a=document.createElement('a');a.href=r.download_url;a.download='';a.click();
    showToast('Report downloaded','success');
  } else if(r.success && r.data) {
    // For CSV, create blob
    const blob=new Blob([r.data],{type:'text/csv'});
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a');a.href=url;a.download=`nurse_${type}_report.${format}`;a.click();
    showToast('Report downloaded','success');
  } else { showToast(r.message||'Report generation failed','error'); }
  closeModal('reportConfigModal');
}
</script>
