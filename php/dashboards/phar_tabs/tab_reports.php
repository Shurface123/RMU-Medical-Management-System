<!-- ════════════════════════════════════════════════════════════
     MODULE 8: REPORTS
     ════════════════════════════════════════════════════════════ -->
<div id="sec-reports" class="dash-section <?=($active_tab==='reports')?'active':''?>">

  <div class="sec-header">
    <div style="display:flex;align-items:center;gap:1.5rem;">
        <div style="width:50px;height:50px;border-radius:15px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.8rem;">
            <i class="fas fa-file-export"></i>
        </div>
        <div>
            <h2 style="margin:0;font-size:2rem;font-weight:700;">Pharmacy Reports</h2>
            <p style="margin:.3rem 0 0;color:var(--text-muted);font-size:1.1rem;">Generate and download analytical reports</p>
        </div>
    </div>
  </div>

  <!-- Quick Stats Strip -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1.5rem;margin-bottom:2rem;">
    <div class="adm-card" style="padding:1.5rem;display:flex;align-items:center;gap:1.5rem;box-shadow:0 4px 15px rgba(0,0,0,0.02);border:none;">
      <div style="width:48px;height:48px;border-radius:12px;background:var(--success-light);color:var(--success);display:flex;align-items:center;justify-content:center;font-size:1.6rem;"><i class="fas fa-chart-line"></i></div>
      <div>
         <div style="font-size:1.1rem;color:var(--text-muted);margin-bottom:.3rem;">Total Dispensed Month</div>
         <div style="font-size:1.6rem;font-weight:700;color:var(--text-color);">GH₵ <?=number_format($stats['month_dispensed'] ?? 0, 2)?></div>
      </div>
    </div>
    <div class="adm-card" style="padding:1.5rem;display:flex;align-items:center;gap:1.5rem;box-shadow:0 4px 15px rgba(0,0,0,0.02);border:none;">
      <div style="width:48px;height:48px;border-radius:12px;background:var(--info-light);color:var(--info);display:flex;align-items:center;justify-content:center;font-size:1.6rem;"><i class="fas fa-file-invoice"></i></div>
      <div>
         <div style="font-size:1.1rem;color:var(--text-muted);margin-bottom:.3rem;">Reports Generated</div>
         <div style="font-size:1.6rem;font-weight:700;color:var(--text-color);"><?=$stats['reports_generated'] ?? 12?></div>
      </div>
    </div>
    <div class="adm-card" style="padding:1.5rem;display:flex;align-items:center;gap:1.5rem;box-shadow:0 4px 15px rgba(0,0,0,0.02);border:none;">
      <div style="width:48px;height:48px;border-radius:12px;background:var(--warning-light);color:var(--warning);display:flex;align-items:center;justify-content:center;font-size:1.6rem;"><i class="fas fa-pills"></i></div>
      <div>
         <div style="font-size:1.1rem;color:var(--text-muted);margin-bottom:.3rem;">Top Medicine</div>
         <div style="font-size:1.3rem;font-weight:700;color:var(--text-color);"><?=htmlspecialchars($stats['top_medicine'] ?? 'Amoxicillin')?></div>
      </div>
    </div>
  </div>

  <div class="cards-grid" style="margin-bottom:2rem;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));">
    <?php foreach([
      ['inventory_status','Inventory Status','fa-pills','Full inventory report with stock levels, expiry dates, and values','success'],
      ['dispensing_records','Dispensing Records','fa-hand-holding-medical','All dispensing activity by date range or patient','primary'],
      ['stock_transactions','Stock Transactions','fa-exchange-alt','Restock, adjustments, returns, and expired removals','info'],
      ['alert_summary','Alert Summary','fa-triangle-exclamation','Low stock, out of stock, and expiry alerts','warning'],
      ['purchase_orders','Purchase Orders','fa-file-invoice','All purchase orders with supplier and delivery status','primary'],
      ['supplier_report','Supplier Performance','fa-truck','Supplier details, performance, and order history','info'],
      ['prescription_fulfillment','Rx Fulfillment','fa-prescription','Prescription completion rates — dispensed vs cancelled','success'],
      ['analytics_summary','Analytics Summary','fa-chart-pie','Comprehensive overview of all pharmacy metrics','warning'],
    ] as [$type,$title,$icon,$desc,$color]):?>
    <div class="info-card rpt-card" style="cursor:pointer;position:relative;overflow:hidden;border:1px solid var(--border);transition:all .3s ease;" onclick="openReportModal('<?=$type?>','<?=$title?>')">
      <!-- Decorative background icon -->
      <i class="fas <?=$icon?>" style="position:absolute;right:-15px;bottom:-15px;font-size:6rem;color:var(--<?=$color?>);opacity:0.05;pointer-events:none;"></i>
      
      <div style="display:flex;align-items:flex-start;gap:1.2rem;margin-bottom:1rem;position:relative;z-index:2;">
        <div style="width:50px;height:50px;border-radius:12px;background:var(--<?=$color?>-light);color:var(--<?=$color?>);display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;"><i class="fas <?=$icon?>"></i></div>
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin:0 0 .3rem;line-height:1.2;"><?=$title?></h4>
            <p style="font-size:1.1rem;color:var(--text-secondary);margin:0;line-height:1.4;"><?=$desc?></p>
        </div>
      </div>
      <div style="margin-top:1.5rem;display:flex;gap:.5rem;position:relative;z-index:2;align-items:center;justify-content:space-between;">
        <div style="display:flex;gap:.5rem;">
            <span class="adm-badge" style="background:var(--danger-light);color:var(--danger);font-size:.9rem;padding:.2rem .5rem;"><i class="fas fa-file-pdf"></i> PDF</span>
            <span class="adm-badge" style="background:var(--success-light);color:var(--success);font-size:.9rem;padding:.2rem .5rem;"><i class="fas fa-file-csv"></i> CSV</span>
            <span class="adm-badge" style="background:var(--primary-light);color:var(--primary);font-size:.9rem;padding:.2rem .5rem;"><i class="fas fa-file-excel"></i> XLSX</span>
        </div>
        <i class="fas fa-arrow-right" style="color:var(--border);"></i>
      </div>
    </div>
    <?php endforeach;?>
  </div>

  <!-- Recent Reports -->
  <?php
    $recent_reports=[];
    $q=mysqli_query($conn,"SELECT pr.*, u.name AS generated_by_name FROM pharmacy_reports pr JOIN users u ON pr.generated_by=u.id ORDER BY pr.generated_at DESC LIMIT 10");
    if($q) while($r=mysqli_fetch_assoc($q)) $recent_reports[]=$r;
  ?>
  <?php if(!empty($recent_reports)):?>
  <div class="adm-card" style="box-shadow: 0 8px 30px rgba(0,0,0,0.04); border: 1px solid var(--border);">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:1.5rem;">
      <h3 style="font-size:1.4rem;font-weight:700;margin:0;"><i class="fas fa-history" style="color:var(--role-accent);margin-right:.8rem;"></i>Recently Generated Reports</h3>
    </div>
    <div class="adm-table-wrap">
      <table class="adm-table" style="margin:0;">
        <thead style="background:var(--bg-main);"><tr><th>Report Type</th><th>Format</th><th>Generated By</th><th>Date & Time</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($recent_reports as $rpt):
            $fmtIcon = ['PDF'=>'fa-file-pdf','CSV'=>'fa-file-csv','XLSX'=>'fa-file-excel'][$rpt['format']] ?? 'fa-file';
            $fmtCol = ['PDF'=>'danger','CSV'=>'success','XLSX'=>'primary'][$rpt['format']] ?? 'info';
        ?>
        <tr style="transition:all .2s;" onmouseover="this.style.background='var(--bg-main)'" onmouseout="this.style.background='transparent'">
          <td><strong><?=ucwords(str_replace('_',' ',$rpt['report_type']))?></strong></td>
          <td>
            <span class="adm-badge" style="background:var(--<?=$fmtCol?>-light);color:var(--<?=$fmtCol?>);">
                <i class="fas <?=$fmtIcon?>"></i> <?=$rpt['format']?>
            </span>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:.6rem;">
                <div style="width:28px;height:28px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;">
                  <?=strtoupper(substr($rpt['generated_by_name'],0,1))?>
                </div>
                <span><?=htmlspecialchars($rpt['generated_by_name'])?></span>
              </div>
          </td>
          <td>
            <div style="font-weight:600;"><?=date('d M Y',strtotime($rpt['generated_at']))?></div>
            <div style="color:var(--text-muted);font-size:1.1rem;"><?=date('g:i A',strtotime($rpt['generated_at']))?></div>
          </td>
          <td>
            <?php if($rpt['file_path']):?>
            <a href="/RMU-Medical-Management-System/<?=htmlspecialchars($rpt['file_path'])?>" class="btn-icon btn btn-primary btn-sm" style="background:var(--primary-light);color:var(--primary);" download title="Download"><span class="btn-text"><i class="fas fa-download"></i></span></a>
            <?php else:?><span style="color:var(--text-muted);">—</span><?php endif;?>
          </td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif;?>
</div>

<!-- ══ Report Generation Modal ══ -->
<div class="modal-bg" id="modalReport">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-file-export" style="color:var(--primary);"></i> <span id="reportTitle">Generate Report</span></h3><button class="btn btn-primary modal-close" onclick="closeModal('modalReport')"><span class="btn-text">&times;</span></button></div>
    <form onsubmit="submitReport(event)" style="padding:1rem .5rem;">
      <input type="hidden" name="report_type" id="reportType">
      
      <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;">
          <button type="button" class="btn btn-outline btn-sm" onclick="setRptDates(this, 'today')"><span class="btn-text">Today</span></button>
          <button type="button" class="btn btn-outline btn-sm" onclick="setRptDates(this, 'week')"><span class="btn-text">This Week</span></button>
          <button type="button" class="btn btn-outline btn-sm active" onclick="setRptDates(this, 'month')"><span class="btn-text">This Month</span></button>
      </div>

      <div class="form-row">
        <div class="form-group"><label>Start Date</label><input type="date" class="form-control" name="start_date" id="rptStart" value="<?=date('Y-m-01')?>"></div>
        <div class="form-group"><label>End Date</label><input type="date" class="form-control" name="end_date" id="rptEnd" value="<?=date('Y-m-d')?>"></div>
      </div>
      <div class="form-group">
          <label>Export Format <span style="color:var(--danger);">*</span></label>
          <div style="display:flex;gap:1rem;margin-top:.5rem;">
              <label style="flex:1;border:1px solid var(--border);border-radius:8px;padding:1rem;cursor:pointer;display:flex;align-items:center;gap:.8rem;transition:all .2s;" class="fmt-radio active">
                  <input type="radio" name="format" value="PDF" checked style="display:none;" onchange="updateFmtRadios(this)">
                  <i class="fas fa-file-pdf" style="font-size:1.5rem;color:var(--danger);"></i> <strong style="font-size:1.2rem;">PDF</strong>
              </label>
              <label style="flex:1;border:1px solid var(--border);border-radius:8px;padding:1rem;cursor:pointer;display:flex;align-items:center;gap:.8rem;transition:all .2s;" class="fmt-radio">
                  <input type="radio" name="format" value="CSV" style="display:none;" onchange="updateFmtRadios(this)">
                  <i class="fas fa-file-csv" style="font-size:1.5rem;color:var(--success);"></i> <strong style="font-size:1.2rem;">CSV</strong>
              </label>
              <label style="flex:1;border:1px solid var(--border);border-radius:8px;padding:1rem;cursor:pointer;display:flex;align-items:center;gap:.8rem;transition:all .2s;" class="fmt-radio">
                  <input type="radio" name="format" value="XLSX" style="display:none;" onchange="updateFmtRadios(this)">
                  <i class="fas fa-file-excel" style="font-size:1.5rem;color:var(--primary);"></i> <strong style="font-size:1.2rem;">Excel</strong>
              </label>
          </div>
      </div>
      <button type="submit" class="btn-icon btn btn-primary" style="width:100%;justify-content:center;padding:1rem;font-size:1.3rem;margin-top:1.5rem;"><span class="btn-text"><i class="fas fa-download"></i> Generate & Download</span></button>
    </form>
  </div>
</div>

<style>
.rpt-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); border-color: var(--primary); }
.rpt-card:hover .fa-arrow-right { color: var(--primary) !important; transform: translateX(3px); }
.fmt-radio.active { border-color: var(--primary) !important; background: var(--primary-light); }
</style>

<script>
function openReportModal(type,title){
  document.getElementById('reportType').value=type;
  document.getElementById('reportTitle').textContent=title;
  openModal('modalReport');
}

function updateFmtRadios(sel) {
    document.querySelectorAll('.fmt-radio').forEach(l => l.classList.remove('active'));
    sel.closest('.fmt-radio').classList.add('active');
}

function setRptDates(btn, range) {
    btn.parentElement.querySelectorAll('.btn').forEach(b => b.classList.remove('active', 'btn-primary'));
    btn.parentElement.querySelectorAll('.btn').forEach(b => b.classList.add('btn-outline'));
    btn.classList.remove('btn-outline');
    btn.classList.add('active', 'btn-primary');
    
    const start = document.getElementById('rptStart');
    const end = document.getElementById('rptEnd');
    const d = new Date();
    
    // PHP Y-m-d format
    const fmt = date => date.toISOString().split('T')[0];
    
    if(range === 'today') {
        start.value = fmt(d);
        end.value = fmt(d);
    } else if(range === 'week') {
        const first = d.getDate() - d.getDay() + (d.getDay() === 0 ? -6 : 1); // Monday
        const d1 = new Date(d.setDate(first));
        const d2 = new Date();
        start.value = fmt(d1);
        end.value = fmt(d2);
    } else if(range === 'month') {
        const d1 = new Date(d.getFullYear(), d.getMonth(), 1);
        const d2 = new Date();
        start.value = fmt(d1);
        end.value = fmt(d2);
    }
}

async function submitReport(e){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target));
  fd.action='generate_report';
  const r=await pharmAction(fd);
  if(r.success){
    toast(r.message||'Report generated');
    closeModal('modalReport');
    if(r.download_url) window.open(r.download_url,'_blank');
    setTimeout(()=>location.reload(),1500);
  } else toast(r.message||'Error generating report','danger');
}
</script>
