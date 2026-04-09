<?php
// TAB: REPORTS — Financial report generation
$report_periods = [
    ['key' => 'today',   'label' => 'Today'],
    ['key' => 'week',    'label' => 'This Week'],
    ['key' => 'month',   'label' => 'This Month'],
    ['key' => 'quarter', 'label' => 'This Quarter'],
    ['key' => 'year',    'label' => 'This Year'],
    ['key' => 'custom',  'label' => 'Custom Range'],
];

// Quick summary stats for the reports panel
$report_month_rev    = (float)fval($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_date>='$month_start' AND status='Completed'");
$report_month_inv    = (int)fval($conn,   "SELECT COUNT(*) FROM billing_invoices WHERE created_at>='$month_start' AND status!='Draft'");
$report_month_claims = (float)fval($conn, "SELECT COALESCE(SUM(approved_amount),0) FROM insurance_claims WHERE status='Approved' AND updated_at>='$month_start'");
$report_month_waivers= (float)fval($conn, "SELECT COALESCE(SUM(waived_amount),0) FROM payment_waivers WHERE status='Approved' AND approved_at>='$month_start'");
?>
<div id="sec-reports" class="dash-section">
  <div class="adm-page-header">
    <div class="adm-page-header-left">
      <h1><i class="fas fa-file-lines" style="color:var(--role-accent);"></i> Financial Reports</h1>
      <p>Generate, download and export financial reports by period</p>
    </div>
  </div>

  <!-- Month Snapshot Cards -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.2rem;margin-bottom:2rem;">
    <div class="fin-kpi-card green" style="cursor:default;">
      <div class="fin-kpi-icon green"><i class="fas fa-coins"></i></div>
      <div class="fin-kpi-label">Month Collected</div>
      <div class="fin-kpi-value" style="font-size:1.8rem;">GHS <?= number_format($report_month_rev, 2) ?></div>
    </div>
    <div class="fin-kpi-card blue" style="cursor:default;">
      <div class="fin-kpi-icon blue"><i class="fas fa-file-invoice-dollar"></i></div>
      <div class="fin-kpi-label">Invoices Issued</div>
      <div class="fin-kpi-value" style="font-size:1.8rem;"><?= $report_month_inv ?></div>
    </div>
    <div class="fin-kpi-card gold" style="cursor:default;">
      <div class="fin-kpi-icon gold"><i class="fas fa-shield-halved"></i></div>
      <div class="fin-kpi-label">Insurance Settled</div>
      <div class="fin-kpi-value" style="font-size:1.8rem;">GHS <?= number_format($report_month_claims, 2) ?></div>
    </div>
    <div class="fin-kpi-card orange" style="cursor:default;">
      <div class="fin-kpi-icon orange"><i class="fas fa-percent"></i></div>
      <div class="fin-kpi-label">Waivers Granted</div>
      <div class="fin-kpi-value" style="font-size:1.8rem;">GHS <?= number_format($report_month_waivers, 2) ?></div>
    </div>
  </div>

  <!-- Report Generator -->
  <div class="adm-card" style="padding:2.5rem;margin-bottom:1.5rem;">
    <div style="font-weight:700;font-size:1.6rem;margin-bottom:2rem;color:var(--text-primary);">
      <i class="fas fa-sliders" style="color:var(--role-accent);margin-right:.6rem;"></i>Generate Report
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:1.2rem;align-items:end;flex-wrap:wrap;">
      <div>
        <label style="display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.04em;">Report Type</label>
        <select id="reportType" class="adm-search-input">
          <option value="revenue_summary">Revenue Summary</option>
          <option value="invoice_report">Invoice Report</option>
          <option value="payment_report">Payment Report</option>
          <option value="insurance_report">Insurance Claims Report</option>
          <option value="waiver_report">Waivers & Discounts Report</option>
          <option value="overdue_report">Overdue Invoices Report</option>
          <option value="reconciliation_report">Reconciliation Report</option>
          <option value="fee_performance">Service Fee Performance</option>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.04em;">Period</label>
        <select id="reportPeriod" onchange="toggleCustomDates(this.value)" class="adm-search-input">
          <?php foreach ($report_periods as $rp): ?>
          <option value="<?= $rp['key'] ?>" <?= $rp['key'] === 'month' ? 'selected' : '' ?>><?= $rp['label'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.04em;">Format</label>
        <select id="reportFormat" class="adm-search-input">
          <option value="pdf">PDF</option>
          <option value="xlsx">Excel (XLSX)</option>
          <option value="csv">CSV</option>
        </select>
      </div>
      <button onclick="generateReport()" class="adm-btn adm-btn-primary" style="white-space:nowrap;">
        <i class="fas fa-file-export"></i> Generate
      </button>
    </div>

    <!-- Custom Date Range (hidden by default) -->
    <div id="customDateRange" style="display:none;margin-top:1.5rem;display:none;">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;max-width:500px;">
        <div>
          <label style="display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;">From Date</label>
          <input type="date" id="reportDateFrom" class="adm-search-input" value="<?= $month_start ?>">
        </div>
        <div>
          <label style="display:block;font-size:1.2rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;">To Date</label>
          <input type="date" id="reportDateTo" class="adm-search-input" value="<?= date('Y-m-d') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Report Type Cards -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.2rem;">
    <?php
    $rpt_types = [
      ['type'=>'revenue_summary',     'icon'=>'fa-coins',             'color'=>'#1a9e6e', 'title'=>'Revenue Summary',         'desc'=>'Total collections, payment methods, daily breakdown'],
      ['type'=>'invoice_report',      'icon'=>'fa-file-invoice-dollar','color'=>'#2F80ED', 'title'=>'Invoice Report',          'desc'=>'All invoices with statuses, amounts and patients'],
      ['type'=>'payment_report',      'icon'=>'fa-money-bill-transfer','color'=>'#27ae60', 'title'=>'Payment Report',          'desc'=>'All payments received, receipts and methods'],
      ['type'=>'insurance_report',    'icon'=>'fa-shield-halved',     'color'=>'#9B59B6', 'title'=>'Insurance Claims',         'desc'=>'Claims submitted, approved and rejected'],
      ['type'=>'waiver_report',       'icon'=>'fa-percent',           'color'=>'#d4a017', 'title'=>'Waivers & Discounts',      'desc'=>'All approved and pending waiver requests'],
      ['type'=>'overdue_report',      'icon'=>'fa-triangle-exclamation','color'=>'#E74C3C','title'=>'Overdue Invoices',        'desc'=>'Invoices past due date with days overdue'],
      ['type'=>'reconciliation_report','icon'=>'fa-scale-balanced',   'color'=>'#56CCF2', 'title'=>'Reconciliation Report',   'desc'=>'Daily cash reconciliation with variances'],
      ['type'=>'fee_performance',     'icon'=>'fa-ranking-star',      'color'=>'#F39C12', 'title'=>'Service Fee Performance', 'desc'=>'Revenue per service, utilisation rates'],
    ];
    foreach ($rpt_types as $rt):
    ?>
    <div onclick="quickReport('<?= $rt['type'] ?>')"
      style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.8rem;cursor:pointer;transition:var(--transition);box-shadow:var(--shadow-sm);"
      onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='var(--shadow-hover)';"
      onmouseout="this.style.transform='';this.style.boxShadow='var(--shadow-sm)';">
      <div style="width:44px;height:44px;border-radius:12px;background:<?= $rt['color'] ?>22;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
        <i class="fas <?= $rt['icon'] ?>" style="font-size:1.8rem;color:<?= $rt['color'] ?>;"></i>
      </div>
      <div style="font-weight:700;font-size:1.4rem;color:var(--text-primary);margin-bottom:.4rem;"><?= $rt['title'] ?></div>
      <div style="font-size:1.15rem;color:var(--text-muted);line-height:1.4;"><?= $rt['desc'] ?></div>
      <div style="margin-top:1rem;font-size:1.15rem;color:<?= $rt['color'] ?>;font-weight:600;">
        <i class="fas fa-download" style="margin-right:.4rem;"></i>Download PDF
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div><!-- /sec-reports -->

<script>
function toggleCustomDates(val) {
  const el = document.getElementById('customDateRange');
  el.style.display = val === 'custom' ? 'block' : 'none';
}

function generateReport() {
  const type    = document.getElementById('reportType').value;
  const period  = document.getElementById('reportPeriod').value;
  const format  = document.getElementById('reportFormat').value;
  const from    = document.getElementById('reportDateFrom')?.value || '';
  const to      = document.getElementById('reportDateTo')?.value || '';
  const url = `/RMU-Medical-Management-System/php/finance/generate_report.php?type=${type}&period=${period}&format=${format}&from=${from}&to=${to}`;
  window.open(url, '_blank');
  toast('Generating report…', 'info');
}

function quickReport(type) {
  const url = `/RMU-Medical-Management-System/php/finance/generate_report.php?type=${type}&period=month&format=pdf`;
  window.open(url, '_blank');
  toast('Generating ' + type.replace(/_/g,' ') + ' report…', 'info');
}
</script>
