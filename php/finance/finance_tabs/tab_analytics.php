<?php
// TAB: ANALYTICS — Revenue analytics with Chart.js
// Revenue by month (last 12 months)
$monthly_rev = [];
for ($i = 11; $i >= 0; $i--) {
    $m_start = date('Y-m-01', strtotime("-$i months"));
    $m_end   = date('Y-m-t',  strtotime("-$i months"));
    $label   = date('M Y',    strtotime($m_start));
    $val     = (float)fval($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_date BETWEEN '$m_start' AND '$m_end 23:59:59' AND status='Completed'");
    $monthly_rev[] = ['label' => $label, 'value' => $val];
}

// Top 8 services by revenue this month
$top_services = [];
$tsq = mysqli_query($conn,
    "SELECT fs.service_name, COALESCE(SUM(ili.line_total),0) AS revenue, COUNT(ili.line_item_id) AS txn_count
     FROM invoice_line_items ili
     LEFT JOIN fee_schedule fs ON ili.fee_id = fs.fee_id
     JOIN billing_invoices bi ON ili.invoice_id = bi.invoice_id
     WHERE bi.created_at >= '$month_start' AND bi.status NOT IN('Cancelled','Void')
     GROUP BY ili.fee_id ORDER BY revenue DESC LIMIT 8");
if ($tsq) while ($r = mysqli_fetch_assoc($tsq)) $top_services[] = $r;

// Revenue by patient type (student vs staff)
$pt_student = (float)fval($conn, "SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN billing_invoices bi ON p.invoice_id=bi.invoice_id JOIN patients pt ON bi.patient_id=pt.id WHERE pt.is_student=1 AND p.status='Completed' AND p.payment_date>='$month_start'");
$pt_staff   = (float)fval($conn, "SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN billing_invoices bi ON p.invoice_id=bi.invoice_id JOIN patients pt ON bi.patient_id=pt.id WHERE pt.is_student=0 AND p.status='Completed' AND p.payment_date>='$month_start'");

// Conversion JSON
$monthly_labels_j = json_encode(array_column($monthly_rev, 'label'));
$monthly_data_j   = json_encode(array_column($monthly_rev, 'value'));
$svc_labels_j     = json_encode(array_column($top_services, 'service_name'));
$svc_data_j       = json_encode(array_map('floatval', array_column($top_services, 'revenue')));
?>
<div id="sec-analytics" class="dash-section">
  <div class="adm-page-header">
    <div class="adm-page-header-left">
      <h1><i class="fas fa-chart-line" style="color:var(--role-accent);"></i> Revenue Analytics</h1>
      <p>Visual breakdown of revenue trends, payment methods and service performance</p>
    </div>
    <select id="analyticsPeriod" onchange="reloadAnalyticsCharts(this.value)" style="padding:.9rem 1.2rem;border:1.5px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text-primary);font-size:1.3rem;">
      <option value="month" selected>This Month</option>
      <option value="quarter">This Quarter</option>
      <option value="year">This Year</option>
    </select>
  </div>

  <!-- KPI Strip -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.2rem;margin-bottom:2rem;">
    <?php
    $rev_month_total = (float)fval($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_date>='$month_start' AND status='Completed'");
    $inv_issued      = (int)fval($conn,   "SELECT COUNT(*) FROM billing_invoices WHERE created_at>='$month_start' AND status!='Draft'");
    $inv_paid        = (int)fval($conn,   "SELECT COUNT(*) FROM billing_invoices WHERE created_at>='$month_start' AND status='Paid'");
    $collection_rate = $inv_issued > 0 ? round(($inv_paid / $inv_issued) * 100, 1) : 0;
    $avg_invoice     = $inv_issued > 0 ? round((float)fval($conn, "SELECT COALESCE(SUM(total_amount),0) FROM billing_invoices WHERE created_at>='$month_start' AND status!='Draft'") / $inv_issued, 2) : 0;
    $kpi_items = [
      ['label'=>'Month Revenue','value'=>'GHS '.number_format($rev_month_total,2),'icon'=>'fa-coins','color'=>'green'],
      ['label'=>'Invoices Issued','value'=>$inv_issued,'icon'=>'fa-file-invoice','color'=>'blue'],
      ['label'=>'Collection Rate','value'=>$collection_rate.'%','icon'=>'fa-percent','color'=>$collection_rate>=70?'green':'orange'],
      ['label'=>'Avg Invoice Value','value'=>'GHS '.number_format($avg_invoice,2),'icon'=>'fa-calculator','color'=>'gold'],
    ];
    foreach ($kpi_items as $k):
    ?>
    <div class="fin-kpi-card <?= $k['color'] ?>">
      <div class="fin-kpi-icon <?= $k['color'] ?>"><i class="fas <?= $k['icon'] ?>"></i></div>
      <div class="fin-kpi-label"><?= $k['label'] ?></div>
      <div class="fin-kpi-value" style="font-size:2rem;"><?= $k['value'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Charts Row 1: Monthly Trend + Patient Type -->
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
    <div class="adm-card" style="padding:2rem;">
      <div style="font-weight:700;font-size:1.5rem;margin-bottom:1.5rem;color:var(--text-primary);">
        <i class="fas fa-chart-area" style="color:var(--role-accent);margin-right:.5rem;"></i>12-Month Revenue Trend
      </div>
      <div style="height:280px;"><canvas id="chartMonthlyRev"></canvas></div>
    </div>
    <div class="adm-card" style="padding:2rem;">
      <div style="font-weight:700;font-size:1.5rem;margin-bottom:1.5rem;color:var(--text-primary);">
        <i class="fas fa-users" style="color:var(--role-accent);margin-right:.5rem;"></i>Patient Type (Current Month)
      </div>
      <div style="height:220px;"><canvas id="chartPatientType"></canvas></div>
      <div style="margin-top:1rem;text-align:center;font-size:1.2rem;">
        <span style="background:var(--role-accent-light);color:var(--role-accent);padding:.2rem .7rem;border-radius:10px;margin-right:.5rem;">Students: GHS <?= number_format($pt_student,2) ?></span>
        <span style="background:var(--info-light);color:var(--info);padding:.2rem .7rem;border-radius:10px;">Staff: GHS <?= number_format($pt_staff,2) ?></span>
      </div>
    </div>
  </div>

  <!-- Charts Row 2: Top Services Bar -->
  <div class="adm-card" style="padding:2rem;margin-bottom:1.5rem;">
    <div style="font-weight:700;font-size:1.5rem;margin-bottom:1.5rem;color:var(--text-primary);">
      <i class="fas fa-ranking-star" style="color:var(--role-accent);margin-right:.5rem;"></i>Top Services by Revenue (Current Month)
    </div>
    <?php if (empty($top_services)): ?>
      <div style="text-align:center;padding:3rem;color:var(--text-muted);">No service revenue data for this month yet.</div>
    <?php else: ?>
      <div style="height:280px;"><canvas id="chartTopServices"></canvas></div>
    <?php endif; ?>
  </div>

  <!-- Revenue Breakdown Table -->
  <div class="adm-card">
    <div style="padding:1.8rem 2rem 0;font-weight:700;font-size:1.5rem;color:var(--text-primary);">
      <i class="fas fa-table" style="color:var(--role-accent);margin-right:.5rem;"></i>Service Revenue Breakdown — Current Month
    </div>
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead><tr>
          <th>Service</th><th>Transactions</th><th>Revenue (GHS)</th><th>% of Total</th>
        </tr></thead>
        <tbody>
        <?php
        $total_svc_rev = array_sum(array_column($top_services, 'revenue'));
        if (empty($top_services)):
        ?>
          <tr><td colspan="4" style="text-align:center;padding:3rem;color:var(--text-muted);">No data for current month.</td></tr>
        <?php else: foreach ($top_services as $svc):
          $pct = $total_svc_rev > 0 ? round(($svc['revenue'] / $total_svc_rev) * 100, 1) : 0;
        ?>
          <tr>
            <td><?= htmlspecialchars($svc['service_name'] ?? 'Unknown Service') ?></td>
            <td><?= number_format($svc['txn_count']) ?></td>
            <td><strong>GHS <?= number_format($svc['revenue'], 2) ?></strong></td>
            <td>
              <div style="display:flex;align-items:center;gap:.8rem;">
                <div class="fin-progress" style="flex:1;"><div class="fin-progress-fill green" style="width:<?= $pct ?>%;"></div></div>
                <span style="font-weight:600;min-width:38px;"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div><!-- /sec-analytics -->

<script>
const monthlyLabels = <?= $monthly_labels_j ?>;
const monthlyData   = <?= $monthly_data_j ?>;
const svcLabels     = <?= $svc_labels_j ?>;
const svcData       = <?= $svc_data_j ?>;
const ptStudentRev  = <?= $pt_student ?>;
const ptStaffRev    = <?= $pt_staff ?>;

function initAnalyticsCharts() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const grid  = isDark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.05)';
  const text  = isDark ? '#9AAECB' : '#5A6A85';

  // 12-Month Revenue Trend
  const mCtx = document.getElementById('chartMonthlyRev');
  if (mCtx) new Chart(mCtx, {
    type: 'bar',
    data: {
      labels: monthlyLabels,
      datasets: [{
        label: 'Revenue (GHS)',
        data: monthlyData,
        backgroundColor: monthlyData.map((v, i) => i === monthlyData.length - 1 ? '#1a9e6e' : 'rgba(26,158,110,.4)'),
        borderRadius: 8,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { color: text, callback: v => 'GHS ' + v.toLocaleString() }, grid: { color: grid } },
        x: { ticks: { color: text, maxRotation: 45 }, grid: { display: false } }
      }
    }
  });

  // Patient Type Doughnut
  const ptCtx = document.getElementById('chartPatientType');
  if (ptCtx && (ptStudentRev + ptStaffRev) > 0) new Chart(ptCtx, {
    type: 'doughnut',
    data: {
      labels: ['Students', 'Staff'],
      datasets: [{ data: [ptStudentRev, ptStaffRev], backgroundColor: ['#1a9e6e', '#2F80ED'], borderWidth: 0, hoverOffset: 8 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: text, padding: 14, font: { size: 12 } } } } }
  });

  // Top Services Horizontal Bar
  const sCtx = document.getElementById('chartTopServices');
  if (sCtx && svcData.length) new Chart(sCtx, {
    type: 'bar',
    data: {
      labels: svcLabels,
      datasets: [{ label: 'Revenue (GHS)', data: svcData, backgroundColor: 'rgba(26,158,110,.75)', borderRadius: 6 }]
    },
    options: {
      indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { beginAtZero: true, ticks: { color: text, callback: v => 'GHS ' + v.toLocaleString() }, grid: { color: grid } },
        y: { ticks: { color: text }, grid: { display: false } }
      }
    }
  });
}

// Call after main initCharts in finance_dashboard.php
document.addEventListener('DOMContentLoaded', () => setTimeout(initAnalyticsCharts, 300));
</script>
