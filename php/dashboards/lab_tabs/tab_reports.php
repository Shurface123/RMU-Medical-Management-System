<!-- ═══════════════ MODULE 10: REPORT GENERATION ═══════════════ -->
<?php if (!defined('BASE')) define('BASE', '/RMU-Medical-Management-System'); ?>
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-file-export" style="color:var(--role-accent);margin-right:.6rem;"></i> Report Generation</h1>
    <p>Generate daily, monthly, equipment, reagent, QC, and TAT reports</p>
  </div>
</div>

<!-- Report Type Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.5rem;margin-bottom:2rem;">
  <?php
  $report_types=[
    ['icon'=>'calendar-day','title'=>'Daily Activity','desc'=>'All tests processed today','color'=>'#8E44AD'],
    ['icon'=>'calendar-alt','title'=>'Monthly Summary','desc'=>'Aggregate stats, trends, workload','color'=>'#2980B9'],
    ['icon'=>'tools','title'=>'Equipment Report','desc'=>'Calibration, maintenance logs','color'=>'#F39C12'],
    ['icon'=>'prescription-bottle','title'=>'Reagent Report','desc'=>'Usage, stock levels, expiry','color'=>'#27AE60'],
    ['icon'=>'check-double','title'=>'QC Report','desc'=>'Pass/fail rates, corrective actions','color'=>'#E74C3C'],
    ['icon'=>'clock','title'=>'TAT Report','desc'=>'Turnaround time analysis','color'=>'#1ABC9C'],
  ];
  foreach($report_types as $ri=>$rt):?>
  <div class="adm-card" style="margin-bottom:0;cursor:pointer;transition:var(--transition);" onclick="selectReport(<?=$ri?>)" id="reportCard<?=$ri?>">
    <div class="adm-card-body" style="text-align:center;padding:2rem;">
      <div style="width:60px;height:60px;margin:0 auto 1rem;border-radius:16px;background:<?=$rt['color']?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;"><i class="fas fa-<?=$rt['icon']?>"></i></div>
      <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:.4rem;"><?=$rt['title']?></h3>
      <p style="color:var(--text-muted);font-size:1.2rem;"><?=$rt['desc']?></p>
    </div>
  </div>
  <?php endforeach;?>
</div>

<!-- Date Range & Generate -->
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-cog"></i> Report Configuration</h3></div>
  <div class="adm-card-body">
    <div class="form-row">
      <div class="form-group"><label>Report Type</label>
        <select id="rpt_type" class="form-control">
          <option value="daily">Daily Activity</option>
          <option value="monthly">Monthly Summary</option>
          <option value="equipment">Equipment Report</option>
          <option value="reagent">Reagent Report</option>
          <option value="qc">QC Report</option>
          <option value="tat">TAT Report</option>
        </select>
      </div>
      <div class="form-group"><label>Format</label>
        <select id="rpt_format" class="form-control"><option value="pdf">PDF</option><option value="csv">CSV</option><option value="html">HTML Preview</option></select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>From</label><input id="rpt_from" type="date" class="form-control" value="<?=$month_start?>"></div>
      <div class="form-group"><label>To</label><input id="rpt_to" type="date" class="form-control" value="<?=$today?>"></div>
    </div>
    <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="generateReport()"><i class="fas fa-file-export"></i> Generate Report</button>
  </div>
</div>

<!-- Previously Generated Reports -->
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-history"></i> Report History</h3></div>
  <div class="adm-card-body">
    <?php
    $prev_reports=[];$q=mysqli_query($conn,"SELECT * FROM lab_reports WHERE technician_id=$tech_pk ORDER BY created_at DESC LIMIT 20");
    if($q) while($pr=mysqli_fetch_assoc($q)) $prev_reports[]=$pr;
    if(empty($prev_reports)):?>
    <p style="text-align:center;color:var(--text-muted);padding:2rem;"><i class="fas fa-file" style="font-size:2rem;display:block;margin-bottom:1rem;"></i>No reports generated yet</p>
    <?php else:?>
    <div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Type</th><th>Date Range</th><th>Generated</th><th>Format</th><th>Actions</th></tr></thead><tbody>
      <?php foreach($prev_reports as $pr):?>
      <tr>
        <td><span class="adm-badge adm-badge-primary"><?=e(ucfirst($pr['report_type']))?></span></td>
        <td><?=$pr['date_from']?date('d M',strtotime($pr['date_from'])):'—'?> – <?=$pr['date_to']?date('d M Y',strtotime($pr['date_to'])):'—'?></td>
        <td style="white-space:nowrap;"><?=date('d M Y, h:i A',strtotime($pr['created_at']))?></td>
        <td><?=e($pr['report_format']??'PDF')?></td>
        <td class="adm-table-actions">
          <?php if($pr['file_path']):?><a href="<?=BASE?>/<?=e($pr['file_path'])?>" target="_blank" class="adm-btn adm-btn-sm adm-btn-primary"><i class="fas fa-download"></i> Download</a><?php endif;?>
          <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="deleteReport(<?=$pr['id']?>)"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
      <?php endforeach;?>
    </tbody></table></div>
    <?php endif;?>
  </div>
</div>

<script>
function selectReport(idx){
  const types=['daily','monthly','equipment','reagent','qc','tat'];
  document.getElementById('rpt_type').value=types[idx]||'daily';
  document.querySelectorAll('[id^=reportCard]').forEach(c=>c.style.borderColor='');
  document.getElementById('reportCard'+idx).style.borderColor='var(--role-accent)';
}
async function generateReport(){
  const r=await labAction({action:'generate_report',report_type:document.getElementById('rpt_type').value,format:document.getElementById('rpt_format').value,date_from:document.getElementById('rpt_from').value,date_to:document.getElementById('rpt_to').value});
  showToast(r.message,r.success?'success':'error');
  if(r.success&&r.file_path) window.open(BASE+'/'+r.file_path,'_blank');
  if(r.success) setTimeout(()=>location.reload(),1500);
}
async function deleteReport(id){if(!confirmAction('Delete this report?'))return;const r=await labAction({action:'delete_report',id:id});showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);}
</script>
