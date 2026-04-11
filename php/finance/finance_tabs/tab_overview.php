<?php // TAB: OVERVIEW — Module 1 ?>
<div id="sec-overview" class="dash-section">

<!-- Hero Banner -->
<div class="fin-hero">
  <div class="fin-hero-icon">
    <?php if(!empty($fs_row['profile_image'])&&file_exists(dirname(dirname(dirname(__DIR__))).'/'.$fs_row['profile_image'])): ?>
      <img src="/RMU-Medical-Management-System/<?=htmlspecialchars($fs_row['profile_image'])?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;" alt="Profile">
    <?php else: ?>
      <i class="fas fa-user-tie"></i>
    <?php endif; ?>
  </div>
  <div class="fin-hero-info" style="flex:1;position:relative;z-index:1;">
    <h2>Welcome back, <?=htmlspecialchars(explode(' ',$fs_row['name'])[0])?> <i class="fas fa-coins" style="font-size:1.6rem;opacity:.7;"></i></h2>
    <p><?=htmlspecialchars(ucfirst(str_replace('_',' ',$fs_row['role_level']??'Finance Officer')))?> &mdash; <?=htmlspecialchars($fs_row['department']??'Finance & Revenue')?></p>
    <div style="margin-top:.6rem;">
      <span class="fin-hero-badge"><i class="fas fa-id-badge"></i><?=htmlspecialchars($fs_row['staff_code']??'FIN-000')?></span>
      <span class="fin-hero-badge"><i class="fas fa-calendar-day"></i><?=date('l, d M Y')?></span>
      <span class="fin-hero-badge"><i class="fas fa-clock"></i><span id="liveClock"><?=date('g:i A')?></span></span>
    </div>
  </div>
  <div style="display:flex;gap:.6rem;flex-wrap:wrap;position:relative;z-index:1;">
    <button onclick="openModal('modalCreateInvoice')" class="btn btn-primary btn" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);"><span class="btn-text">
      <i class="fas fa-file-plus"></i> Create Invoice
    </span></button>
    <button onclick="openModal('modalRecordPayment')" class="btn btn-primary btn" style="background:rgba(212,160,23,.4);color:#fff;border:1px solid rgba(212,160,23,.5);"><span class="btn-text">
      <i class="fas fa-money-bill-wave"></i> Record Payment
    </span></button>
  </div>
</div>

<!-- ── KPI Cards ─────────────────────────────────────────── -->
<div class="fin-kpi-grid">
  <div class="fin-kpi-card green" onclick="showTab('payments', document.querySelector('.adm-nav-item[onclick*=payments]'))">
    <div class="fin-kpi-icon green"><i class="fas fa-arrow-trend-up"></i></div>
    <div class="fin-kpi-label">Revenue Today</div>
    <div class="fin-kpi-value">GHS <?=number_format($kpi['revenue_today'],2)?></div>
    <div class="fin-kpi-sub"><i class="fas fa-calendar-day"></i> <?=date('d M Y')?></div>
  </div>

  <div class="fin-kpi-card blue" onclick="showTab('analytics', document.querySelector('.adm-nav-item[onclick*=analytics]'))">
    <div class="fin-kpi-icon blue"><i class="fas fa-chart-line"></i></div>
    <div class="fin-kpi-label">Revenue This Month</div>
    <div class="fin-kpi-value">GHS <?=number_format($kpi['revenue_month'],2)?></div>
    <div class="fin-kpi-sub"><i class="fas fa-calendar"></i> <?=date('F Y')?></div>
  </div>

  <div class="fin-kpi-card orange" onclick="showTab('invoices', document.querySelector('.adm-nav-item[onclick*=invoices]'))">
    <div class="fin-kpi-icon orange"><i class="fas fa-file-invoice"></i></div>
    <div class="fin-kpi-label">Pending Invoices</div>
    <div class="fin-kpi-value"><?=$kpi['pending_inv_count']?></div>
    <div class="fin-kpi-sub"><i class="fas fa-coins"></i> GHS <?=number_format($kpi['pending_inv_val'],2)?></div>
  </div>

  <div class="fin-kpi-card red" onclick="showTab('invoices', document.querySelector('.adm-nav-item[onclick*=invoices]'))">
    <div class="fin-kpi-icon red"><i class="fas fa-file-circle-exclamation"></i></div>
    <div class="fin-kpi-label">Overdue Invoices</div>
    <div class="fin-kpi-value"><?=$kpi['overdue_count']?></div>
    <div class="fin-kpi-sub"><i class="fas fa-coins"></i> GHS <?=number_format($kpi['overdue_val'],2)?></div>
  </div>

  <div class="fin-kpi-card green" onclick="showTab('paystack', document.querySelector('.adm-nav-item[onclick*=paystack]'))">
    <div class="fin-kpi-icon green"><i class="fas fa-credit-card"></i></div>
    <div class="fin-kpi-label">Paystack Txns Today</div>
    <div class="fin-kpi-value"><?=$kpi['paystack_today']?></div>
    <div class="fin-kpi-sub"><i class="fas fa-check-circle"></i> Successful</div>
  </div>

  <div class="fin-kpi-card purple" onclick="showTab('insurance', document.querySelector('.adm-nav-item[onclick*=insurance]'))">
    <div class="fin-kpi-icon purple"><i class="fas fa-shield-halved"></i></div>
    <div class="fin-kpi-label">Pending Claims</div>
    <div class="fin-kpi-value"><?=$kpi['insurance_count']?></div>
    <div class="fin-kpi-sub"><i class="fas fa-coins"></i> GHS <?=number_format($kpi['insurance_val'],2)?></div>
  </div>

  <div class="fin-kpi-card gold" onclick="showTab('invoices', document.querySelector('.adm-nav-item[onclick*=invoices]'))">
    <div class="fin-kpi-icon gold"><i class="fas fa-sack-xmark"></i></div>
    <div class="fin-kpi-label">Outstanding Balance</div>
    <div class="fin-kpi-value" style="font-size:2.2rem;">GHS <?=number_format($kpi['outstanding'],2)?></div>
    <div class="fin-kpi-sub"><i class="fas fa-users"></i> Total unpaid</div>
  </div>

  <div class="fin-kpi-card blue" onclick="showTab('waivers', document.querySelector('.adm-nav-item[onclick*=waivers]'))">
    <div class="fin-kpi-icon blue"><i class="fas fa-percent"></i></div>
    <div class="fin-kpi-label">Waivers This Month</div>
    <div class="fin-kpi-value"><?=$kpi['waivers_month']?></div>
    <div class="fin-kpi-sub"><i class="fas fa-thumbs-up"></i> Approved</div>
  </div>
</div>

<!-- ── Quick Actions ──────────────────────────────────────── -->
<div class="fin-quick-actions">
  <button onclick="openModal('modalCreateInvoice')" class="btn btn-ghost fin-action-tile"><span class="btn-text">
    <i class="fas fa-file-plus" style="background:linear-gradient(135deg,#1a9e6e,#27AE60);"></i>Create Invoice
  </span></button>
  <button onclick="openModal('modalRecordPayment')" class="btn btn-ghost fin-action-tile"><span class="btn-text">
    <i class="fas fa-money-bill-wave" style="background:linear-gradient(135deg,#d4a017,#E67E22);"></i>Record Payment
  </span></button>
  <button onclick="showTab('insurance',document.querySelector('.adm-nav-item[onclick*=insurance]'))" class="btn btn-ghost fin-action-tile"><span class="btn-text">
    <i class="fas fa-shield-halved" style="background:linear-gradient(135deg,#7D3C98,#9B59B6);"></i>Process Claim
  </span></button>
  <button onclick="showTab('reports',document.querySelector('.adm-nav-item[onclick*=reports]'))" class="btn btn-ghost fin-action-tile"><span class="btn-text">
    <i class="fas fa-file-chart-column" style="background:linear-gradient(135deg,#2F80ED,#56CCF2);"></i>Generate Report
  </span></button>
  <a href="https://dashboard.paystack.com" target="_blank" class="fin-action-tile">
    <i class="fas fa-arrow-up-right-from-square" style="background:linear-gradient(135deg,#1a9e6e,#0d3b2e);"></i>Paystack Dashboard
  </a>
</div>

<!-- ── Charts Row ─────────────────────────────────────────── -->
<div class="adm-charts-grid" style="margin-bottom:2rem;">
  <div class="adm-chart-card">
    <h3><i class="fas fa-chart-line"></i>Revenue Trend — Last 30 Days</h3>
    <div style="height:240px;"><canvas id="chartRevTrend"></canvas></div>
  </div>
  <div class="adm-chart-card">
    <h3><i class="fas fa-chart-pie"></i>Revenue by Payment Method</h3>
    <div style="height:240px;"><canvas id="chartRevMethod"></canvas></div>
  </div>
</div>

<?php if(!empty($overdue_alerts)): ?>
<!-- ── Overdue Alert Panel ─────────────────────────────────── -->
<div class="overdue-panel">
  <div class="overdue-panel-title"><i class="fas fa-circle-exclamation"></i> Overdue Invoices — Immediate Attention Required (>7 days)</div>
  <?php foreach($overdue_alerts as $ov): ?>
  <div class="overdue-item">
    <span class="overdue-days"><?=$ov['days_overdue']?> days</span>
    <div style="flex:1;">
      <div style="font-weight:600;font-size:1.3rem;"><?=htmlspecialchars($ov['patient_name'])?></div>
      <div style="font-size:1.15rem;color:var(--text-secondary);"><?=htmlspecialchars($ov['invoice_number'])?> &middot; Due <?=date('d M Y',strtotime($ov['due_date']))?></div>
    </div>
    <div style="font-weight:700;color:var(--danger);font-size:1.4rem;">GHS <?=number_format($ov['balance_due'],2)?></div>
    <button onclick="viewInvoice('<?=htmlspecialchars($ov['invoice_number'])?>')" class="btn-icon btn btn-sm btn-danger"><span class="btn-text">View</span></button>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Recent Transactions Feed ───────────────────────────── -->
<div class="adm-card">
  <div class="adm-card-header">
    <h3><i class="fas fa-receipt"></i>Recent Transactions</h3>
    <button onclick="showTab('payments',document.querySelector('.adm-nav-item[onclick*=payments]'))" class="btn-icon btn btn-ghost btn-sm"><span class="btn-text">View All</span></button>
  </div>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr>
        <th>Patient</th><th>Invoice #</th><th>Amount</th>
        <th>Method</th><th>Status</th><th>Time</th>
      </tr></thead>
      <tbody>
      <?php if(empty($recent_payments)): ?>
        <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text-muted);">No transactions yet today.</td></tr>
      <?php else: foreach($recent_payments as $pay):
        $sc_map=['Completed'=>'success','Pending'=>'warning','Failed'=>'danger','Refunded'=>'info'];
        $sc=$sc_map[$pay['status']]??'info';
      ?>
        <tr>
          <td><?=htmlspecialchars($pay['patient_name']??'—')?></td>
          <td><strong><?=htmlspecialchars($pay['invoice_number']??'—')?></strong></td>
          <td><strong>GHS <?=number_format($pay['amount'],2)?></strong></td>
          <td><?=htmlspecialchars($pay['payment_method']??'—')?></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$pay['status']?></span></td>
          <td><?=date('d M, g:i A',strtotime($pay['created_at']))?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div><!-- /sec-overview -->

<script>
// Live clock
setInterval(()=>{
  const now=new Date(); const c=document.getElementById('liveClock');
  if(c) c.textContent=now.toLocaleTimeString('en-GH',{hour:'numeric',minute:'2-digit',hour12:true});
},1000);

function viewInvoice(invNum){
  showTab('invoices',document.querySelector('.adm-nav-item[onclick*=invoices]'));
  setTimeout(()=>{ const s=document.getElementById('invSearch'); if(s){s.value=invNum;filterTable('invSearch','invoiceTable');} },150);
}
</script>
