<?php
// TABs: BUDGET, FEE SCHEDULE, ANALYTICS, REPORTS — Modules 9,5,10,11
// Budget
$budgets=[];
$bq=mysqli_query($conn,"SELECT ba.*,rc.category_name,u.name AS creator FROM budget_allocations ba JOIN revenue_categories rc ON ba.category_id=rc.category_id LEFT JOIN users u ON ba.created_by=u.id ORDER BY ba.created_at DESC LIMIT 50");
if($bq) while($r=mysqli_fetch_assoc($bq)) $budgets[]=$r;
// Fee Schedule
$fees=[];
$fq=mysqli_query($conn,"SELECT fs.*,rc.category_name FROM fee_schedule fs LEFT JOIN revenue_categories rc ON fs.category_id=rc.category_id ORDER BY fs.service_name");
if($fq) while($r=mysqli_fetch_assoc($fq)) $fees[]=$r;
// Revenue categories for dropdowns
$rev_cats=[];
$rcq=mysqli_query($conn,"SELECT * FROM revenue_categories WHERE is_active=1 ORDER BY category_name");
if($rcq) while($r=mysqli_fetch_assoc($rcq)) $rev_cats[]=$r;
?>

<!-- ══ TAB: BUDGET ════════════════════════════════════════ -->
<div id="sec-budget" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-chart-pie" style="color:var(--role-accent);"></i> Budget Management</h1>
    <p>Track and manage budgets for each department and revenue category</p>
  </div>
  <?php if($user_role==='finance_manager'): ?>
  <button onclick="openModal('modalNewBudget')" class="adm-btn adm-btn-primary"><i class="fas fa-plus"></i> New Allocation</button>
  <?php endif;?>
</div>
<div class="adm-card">
  <?php if(empty($budgets)): ?>
    <div style="text-align:center;padding:4rem;color:var(--text-muted);"><i class="fas fa-chart-pie" style="font-size:3rem;opacity:.3;display:block;margin-bottom:1rem;"></i><p>No budget allocations yet.</p></div>
  <?php else: ?>
  <div class="adm-card-body">
    <?php foreach($budgets as $b):
      $pct = $b['allocated_amount'] > 0 ? round(($b['spent_amount']/$b['allocated_amount'])*100) : 0;
      $barcls = $pct>=100?'red':($pct>=80?'amber':'green');
    ?>
    <div style="padding:1.8rem;border-bottom:1px solid var(--border);">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:.8rem;">
        <div>
          <div style="font-weight:700;font-size:1.5rem;"><?=htmlspecialchars($b['category_name']??'—')?> — <?=htmlspecialchars($b['department']??'General')?></div>
          <div style="font-size:1.2rem;color:var(--text-muted);"><?=htmlspecialchars($b['fiscal_year'])?> <?=htmlspecialchars($b['fiscal_period'])?>
            <span class="adm-badge adm-badge-<?=$b['status']==='Active'?'success':'info'?>" style="margin-left:.5rem;"><?=$b['status']?></span>
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:1.8rem;font-weight:800;color:var(--role-accent);">GHS <?=number_format($b['spent_amount'],2)?> <span style="font-size:1.2rem;color:var(--text-muted);">/ <?=number_format($b['allocated_amount'],2)?></span></div>
          <div style="font-size:1.2rem;color:<?=$pct>=100?'var(--danger)':($pct>=80?'var(--warning)':'var(--text-muted)')?>;"><?=$pct?>% used</div>
        </div>
      </div>
      <div class="fin-progress"><div class="fin-progress-fill <?=$barcls?>" style="width:<?=min(100,$pct)?>%;"></div></div>
      <?php if($pct>=80): ?>
      <div style="font-size:1.15rem;color:<?=$pct>=100?'var(--danger)':'var(--warning)'?>;margin-top:.5rem;"><i class="fas fa-triangle-exclamation"></i> <?=$pct>=100?'Budget exhausted!':'Approaching budget limit'?></div>
      <?php endif;?>
    </div>
    <?php endforeach;?>
  </div>
  <?php endif;?>
</div>
</div>

<!-- ══ TAB: FEE SCHEDULE ══════════════════════════════════ -->
<div id="sec-fee_schedule" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-list-ol" style="color:var(--role-accent);"></i> Fee Schedule</h1>
    <p>Manage billable service prices used for invoice generation</p>
  </div>
  <?php if($user_role==='finance_manager'): ?>
  <button onclick="openModal('modalNewFee')" class="adm-btn adm-btn-primary"><i class="fas fa-plus"></i> Add New Fee</button>
  <?php endif;?>
</div>
<div class="fin-filter-row">
  <div class="adm-search-wrap" style="flex:2;min-width:200px;">
    <i class="fas fa-search"></i>
    <input type="text" id="feeSearch" class="adm-search-input" placeholder="Search service name, code..." oninput="filterTable('feeSearch','feeTable')">
  </div>
  <select id="feeCatFilter" onchange="document.querySelectorAll('#feeTable tbody tr').forEach(r=>{r.style.display=(!this.value||r.dataset.cat===this.value)?'':'none';})">
    <option value="">All Categories</option>
    <?php foreach($rev_cats as $rc): ?><option value="<?=$rc['category_id']?>"><?=htmlspecialchars($rc['category_name'])?></option><?php endforeach;?>
  </select>
  <select id="feeActiveFilter" onchange="document.querySelectorAll('#feeTable tbody tr').forEach(r=>{r.style.display=(!this.value||r.dataset.active===this.value)?'':'none';})">
    <option value="">All</option><option value="1">Active</option><option value="0">Inactive</option>
  </select>
</div>
<div class="adm-card">
  <div class="adm-table-wrap">
    <table class="adm-table" id="feeTable">
      <thead><tr>
        <th>Service Name</th><th>Code</th><th>Category</th>
        <th>Base (GHS)</th><th>Student (GHS)</th><th>Tax %</th>
        <th>Taxable</th><th>Effective From</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($fees)): ?>
        <tr><td colspan="10" style="text-align:center;padding:3rem;color:var(--text-muted);">No fee entries yet.</td></tr>
      <?php else: foreach($fees as $f): ?>
        <tr data-cat="<?=$f['category_id']?>" data-active="<?=$f['is_active']?>">
          <td><strong><?=htmlspecialchars($f['service_name'])?></strong></td>
          <td style="font-size:1.2rem;color:var(--text-muted);"><?=htmlspecialchars($f['service_code'])?></td>
          <td><?=htmlspecialchars($f['category_name']??'—')?></td>
          <td style="color:var(--role-accent);font-weight:700;"><?=number_format($f['base_amount'],2)?></td>
          <td style="color:var(--info);"><?=$f['student_amount']?number_format($f['student_amount'],2):'—'?></td>
          <td><?=number_format($f['tax_rate_pct'],1)?>%</td>
          <td><span class="adm-badge <?=$f['is_taxable']?'adm-badge-warning':'adm-badge-success'?>"><?=$f['is_taxable']?'Yes':'No'?></span></td>
          <td><?=date('d M Y',strtotime($f['effective_from']))?></td>
          <td><span class="adm-badge <?=$f['is_active']?'adm-badge-success':'adm-badge-danger'?>"><?=$f['is_active']?'Active':'Inactive'?></span></td>
          <td>
            <div class="adm-table-actions">
              <button onclick="editFee(<?=json_encode($f)?>)" class="adm-btn adm-btn-sm adm-btn-ghost" title="Edit"><i class="fas fa-pen"></i></button>
              <button onclick="toggleFeeActive(<?=$f['fee_id']?>,<?=$f['is_active']?>)" class="adm-btn adm-btn-sm <?=$f['is_active']?'adm-btn-danger':'adm-btn-success'?>" title="<?=$f['is_active']?'Deactivate':'Activate'?>">
                <i class="fas fa-<?=$f['is_active']?'ban':'check'?>"></i>
              </button>
            </div>
          </td>
        </tr>
      <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- ══ TAB: ANALYTICS ════════════════════════════════════ -->
<div id="sec-analytics" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-chart-line" style="color:var(--role-accent);"></i> Revenue Analytics</h1>
    <p>Comprehensive financial analytics and performance metrics</p>
  </div>
  <div style="display:flex;gap:1rem;align-items:center;">
    <input type="date" id="analyticsFrom" value="<?=$month_start?>" class="adm-search-input" style="width:160px;">
    <input type="date" id="analyticsTo" value="<?=$today?>" class="adm-search-input" style="width:160px;">
    <button onclick="loadAnalytics()" class="adm-btn adm-btn-primary"><i class="fas fa-rotate"></i> Apply</button>
    <button onclick="exportAnalytics()" class="adm-btn adm-btn-ghost"><i class="fas fa-file-export"></i> Export</button>
  </div>
</div>
<div class="adm-charts-grid">
  <div class="adm-chart-card">
    <h3><i class="fas fa-chart-bar"></i> Revenue by Service Category</h3>
    <div style="height:260px;"><canvas id="chartRevByCategory"></canvas></div>
  </div>
  <div class="adm-chart-card">
    <h3><i class="fas fa-chart-pie"></i> Revenue by Payment Method</h3>
    <div style="height:260px;"><canvas id="chartMethod2"></canvas></div>
  </div>
</div>
<div class="adm-charts-grid">
  <div class="adm-chart-card" style="grid-column:1/-1;">
    <h3><i class="fas fa-chart-line"></i> Revenue Trend
      <div style="display:flex;gap:.5rem;margin-left:auto;">
        <button onclick="setAnalyticsPeriod('daily')" class="adm-btn adm-btn-sm adm-btn-ghost" id="btnDaily">Daily</button>
        <button onclick="setAnalyticsPeriod('weekly')" class="adm-btn adm-btn-sm adm-btn-ghost" id="btnWeekly">Weekly</button>
        <button onclick="setAnalyticsPeriod('monthly')" class="adm-btn adm-btn-sm adm-btn-ghost" id="btnMonthly">Monthly</button>
      </div>
    </h3>
    <div style="height:280px;"><canvas id="chartRevTrend2"></canvas></div>
  </div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
  <!-- Invoice Aging -->
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-hourglass"></i> Invoice Aging Analysis</h3></div>
    <div class="adm-card-body" id="agingPanel">
    <?php
    $aging=[['0-30 days',0,30,'success'],['31-60 days',31,60,'warning'],['61-90 days',61,90,'orange'],['90+ days',91,9999,'danger']];
    foreach($aging as [$label,$min,$max,$cl]):
      $cnt=(int)fval($conn,"SELECT COUNT(*) FROM billing_invoices WHERE status NOT IN ('Paid','Cancelled','Void') AND due_date IS NOT NULL AND DATEDIFF('$today',due_date) BETWEEN $min AND $max");
      $val=(float)fval($conn,"SELECT COALESCE(SUM(balance_due),0) FROM billing_invoices WHERE status NOT IN ('Paid','Cancelled','Void') AND due_date IS NOT NULL AND DATEDIFF('$today',due_date) BETWEEN $min AND $max");
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem 0;border-bottom:1px solid var(--border);">
      <div><span class="adm-badge adm-badge-<?=$cl?>"><?=$label?></span></div>
      <div style="text-align:right;"><div style="font-weight:700;font-size:1.4rem;color:var(--<?=$cl==='success'?'success':'danger'?>);">GHS <?=number_format($val,2)?></div><div style="font-size:1.1rem;color:var(--text-muted);"><?=$cnt?> invoice(s)</div></div>
    </div>
    <?php endforeach;?>
    </div>
  </div>

  <!-- Top Billing Patients (anonymized) -->
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-users"></i> Top Billing Patients (Anonymized)</h3></div>
    <div class="adm-card-body">
    <?php
    $top=[];
    $tq=mysqli_query($conn,"SELECT p.patient_id, COALESCE(SUM(bi.total_amount),0) AS total FROM billing_invoices bi JOIN patients p ON bi.patient_id=p.id GROUP BY p.patient_id ORDER BY total DESC LIMIT 10");
    if($tq) while($r=mysqli_fetch_assoc($tq)) $top[]=$r;
    foreach($top as $i=>$t):
    ?>
    <div style="display:flex;align-items:center;gap:1rem;padding:.8rem 0;border-bottom:1px solid var(--border);">
      <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--role-accent),#27AE60);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;flex-shrink:0;"><?=$i+1?></div>
      <div style="flex:1;font-weight:600;">PAT-<?=str_pad($i+1,4,'*',STR_PAD_LEFT)?> <span style="color:var(--text-muted);font-size:1.1rem;">(anonymized)</span></div>
      <div style="font-weight:700;color:var(--role-accent);">GHS <?=number_format($t['total'],2)?></div>
    </div>
    <?php endforeach;?>
    </div>
  </div>
</div>
</div>

<!-- ══ TAB: REPORTS ══════════════════════════════════════ -->
<div id="sec-reports" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-file-lines" style="color:var(--role-accent);"></i> Financial Reports</h1>
    <p>Generate comprehensive reports with RMU Medical Sickbay branding</p>
  </div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.8rem;">
  <?php
  $reports=[
    ['Daily Revenue Summary','fa-calendar-day','Generate all transactions for a selected day with method totals','daily_revenue','warning'],
    ['Monthly Financial Statement','fa-file-invoice','Income statement format with all revenue categories','monthly_statement','blue'],
    ['Invoice Aging Report','fa-hourglass-half','All outstanding invoices grouped by overdue period','invoice_aging','orange'],
    ['Payment Method Breakdown','fa-chart-pie','Cash vs Paystack vs Insurance vs Mobile Money','payment_methods','purple'],
    ['Insurance Claims Report','fa-shield-halved','All claims with status, amounts, and approval rates','insurance_claims','blue'],
    ['Outstanding Balances','fa-sack-xmark','All patients with unpaid invoice balances','outstanding','red'],
    ['Paystack Reconciliation','fa-credit-card','Internal vs Paystack transaction comparison','paystack_recon','green'],
    ['Waivers & Discounts','fa-percent','All waivers granted with values and approvers','waivers_report','info'],
    ['Refunds Report','fa-rotate-left','All processed refunds with methods and reasons','refunds_report','danger'],
    ['Budget Utilization','fa-chart-pie','Allocated vs spent by department and category','budget_util','success'],
    ['Revenue by Service','fa-bars','Which service categories generate the most revenue','revenue_by_service','green'],
    ['Custom Report','fa-sliders','Choose any combination of filters and date range','custom','primary'],
  ];
  foreach($reports as [$title,$icon,$desc,$type,$col]):
  ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:2rem;box-shadow:var(--shadow-sm);transition:var(--transition);" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='var(--shadow-hover)'" onmouseleave="this.style.transform='';this.style.boxShadow='var(--shadow-sm)'">
    <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,var(--role-accent),#27AE60);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;margin-bottom:1.2rem;"><i class="fas <?=$icon?>"></i></div>
    <div style="font-size:1.5rem;font-weight:700;margin-bottom:.5rem;"><?=$title?></div>
    <div style="font-size:1.2rem;color:var(--text-secondary);margin-bottom:1.5rem;"><?=$desc?></div>
    <div style="display:flex;gap:.6rem;width:100%;">
      <input type="date" id="rpt_from_<?=$type?>" value="<?=$month_start?>" class="adm-search-input" style="flex:1;font-size:1.2rem;padding:.6rem .8rem;">
      <input type="date" id="rpt_to_<?=$type?>" value="<?=$today?>" class="adm-search-input" style="flex:1;font-size:1.2rem;padding:.6rem .8rem;">
    </div>
    <div style="display:flex;gap:.6rem;margin-top:.8rem;">
      <button onclick="generateReport('<?=$type?>','pdf')" class="adm-btn adm-btn-sm adm-btn-danger" style="flex:1;"><i class="fas fa-file-pdf"></i> PDF</button>
      <button onclick="generateReport('<?=$type?>','csv')" class="adm-btn adm-btn-sm adm-btn-ghost" style="flex:1;"><i class="fas fa-file-csv"></i> CSV</button>
      <button onclick="generateReport('<?=$type?>','xlsx')" class="adm-btn adm-btn-sm adm-btn-success" style="flex:1;"><i class="fas fa-file-excel"></i> Excel</button>
    </div>
  </div>
  <?php endforeach;?>
</div>

<!-- Recent Generated Reports -->
<div class="adm-card" style="margin-top:2rem;">
  <div class="adm-card-header"><h3><i class="fas fa-clock-rotate-left"></i> Recently Generated Reports</h3></div>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Report Type</th><th>Period</th><th>Format</th><th>Generated By</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php
      $rpts=[];
      $rq=mysqli_query($conn,"SELECT fr.*,u.name AS gen_name FROM financial_reports fr LEFT JOIN users u ON fr.generated_by=u.id ORDER BY fr.created_at DESC LIMIT 20");
      if($rq) while($r=mysqli_fetch_assoc($rq)) $rpts[]=$r;
      if(empty($rpts)): ?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No reports generated yet.</td></tr>
      <?php else: foreach($rpts as $rp): ?>
        <tr>
          <td><?=htmlspecialchars($rp['title']??$rp['report_type'])?></td>
          <td><?=date('d M Y',strtotime($rp['period_start']))?> — <?=date('d M Y',strtotime($rp['period_end']))?></td>
          <td><span class="adm-badge adm-badge-info"><?=htmlspecialchars($rp['file_format']??'PDF')?></span></td>
          <td><?=htmlspecialchars($rp['gen_name']??'System')?></td>
          <td><?=date('d M Y, g:i A',strtotime($rp['created_at']))?></td>
          <td>
            <?php if(!empty($rp['file_path'])): ?>
            <a href="/RMU-Medical-Management-System/<?=htmlspecialchars($rp['file_path'])?>" target="_blank" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fas fa-download"></i></a>
            <?php endif;?>
          </td>
        </tr>
      <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- New Budget Modal -->
<div class="adm-modal" id="modalNewBudget">
  <div class="adm-modal-content" style="max-width:580px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-chart-pie" style="color:var(--role-accent);"></i> New Budget Allocation</h3>
      <button class="adm-modal-close" onclick="closeModal('modalNewBudget')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="adm-modal-body">
      <form id="formNewBudget">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Fiscal Year *</label>
            <input type="text" name="fiscal_year" class="adm-search-input" value="<?=date('Y')?>" required>
          </div>
          <div class="adm-form-group">
            <label>Period *</label>
            <select name="fiscal_period" class="adm-search-input">
              <?php foreach(['Annual','Q1','Q2','Q3','Q4','Monthly'] as $p): ?><option><?=$p?></option><?php endforeach;?>
            </select>
          </div>
        </div>
        <div class="adm-form-group">
          <label>Revenue Category *</label>
          <select name="category_id" class="adm-search-input" required>
            <option value="">— Select Category —</option>
            <?php foreach($rev_cats as $rc): ?><option value="<?=$rc['category_id']?>"><?=htmlspecialchars($rc['category_name'])?></option><?php endforeach;?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Department</label>
            <input type="text" name="department" class="adm-search-input" placeholder="e.g. Laboratory">
          </div>
          <div class="adm-form-group">
            <label>Allocated Amount (GHS) *</label>
            <input type="number" name="allocated_amount" class="adm-search-input" step="0.01" min="0.01" required>
          </div>
        </div>
        <div class="adm-form-group">
          <label>Notes</label>
          <textarea name="notes" class="adm-search-input" rows="2" style="resize:vertical;"></textarea>
        </div>
      </form>
    </div>
    <div class="adm-modal-footer">
      <button onclick="closeModal('modalNewBudget')" class="adm-btn adm-btn-ghost">Cancel</button>
      <button onclick="saveBudget()" class="adm-btn adm-btn-primary"><i class="fas fa-check"></i> Create Allocation</button>
    </div>
  </div>
</div>

<!-- New Fee Modal -->
<div class="adm-modal" id="modalNewFee">
  <div class="adm-modal-content" style="max-width:620px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-list-ol" style="color:var(--role-accent);"></i> <span id="feeModalTitle">Add Fee Entry</span></h3>
      <button class="adm-modal-close" onclick="closeModal('modalNewFee')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="adm-modal-body">
      <form id="formNewFee">
        <input type="hidden" name="fee_id" id="editFeeId">
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Service Name *</label>
            <input type="text" name="service_name" id="feeServiceName" class="adm-search-input" required>
          </div>
          <div class="adm-form-group">
            <label>Service Code *</label>
            <input type="text" name="service_code" id="feeServiceCode" class="adm-search-input" required placeholder="e.g. CONSULT-001">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Category</label>
            <select name="category_id" id="feeCategoryId" class="adm-search-input">
              <option value="">— None —</option>
              <?php foreach($rev_cats as $rc): ?><option value="<?=$rc['category_id']?>"><?=htmlspecialchars($rc['category_name'])?></option><?php endforeach;?>
            </select>
          </div>
          <div class="adm-form-group">
            <label>Base Amount (GHS) *</label>
            <input type="number" name="base_amount" id="feeBaseAmount" class="adm-search-input" step="0.01" min="0" required>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Student Amount</label>
            <input type="number" name="student_amount" id="feeStudentAmt" class="adm-search-input" step="0.01" min="0">
          </div>
          <div class="adm-form-group">
            <label>Tax Rate %</label>
            <input type="number" name="tax_rate_pct" id="feeTaxRate" class="adm-search-input" step="0.01" min="0" max="100" value="0">
          </div>
          <div class="adm-form-group">
            <label>Taxable?</label>
            <select name="is_taxable" id="feeIsTaxable" class="adm-search-input">
              <option value="0">No</option><option value="1">Yes</option>
            </select>
          </div>
        </div>
        <div class="adm-form-group">
          <label>Effective From *</label>
          <input type="date" name="effective_from" id="feeEffectiveFrom" class="adm-search-input" value="<?=date('Y-m-d')?>" required>
        </div>
      </form>
    </div>
    <div class="adm-modal-footer">
      <button onclick="closeModal('modalNewFee')" class="adm-btn adm-btn-ghost">Cancel</button>
      <button onclick="saveFee()" class="adm-btn adm-btn-primary"><i class="fas fa-check"></i> Save Fee</button>
    </div>
  </div>
</div>

<script>
// ── Budget ───────────────────────────────────────────────────
async function saveBudget(){
  const f=document.getElementById('formNewBudget');
  const d=await finAction({action:'create_budget',fiscal_year:f.querySelector('[name=fiscal_year]').value,fiscal_period:f.querySelector('[name=fiscal_period]').value,category_id:f.querySelector('[name=category_id]').value,department:f.querySelector('[name=department]').value,allocated_amount:f.querySelector('[name=allocated_amount]').value,notes:f.querySelector('[name=notes]').value});
  if(d.success){ toast('Budget allocation created!','success'); closeModal('modalNewBudget'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}

// ── Fee Schedule ─────────────────────────────────────────────
function editFee(f){
  document.getElementById('feeModalTitle').textContent='Edit Fee Entry';
  document.getElementById('editFeeId').value=f.fee_id;
  document.getElementById('feeServiceName').value=f.service_name||'';
  document.getElementById('feeServiceCode').value=f.service_code||'';
  document.getElementById('feeCategoryId').value=f.category_id||'';
  document.getElementById('feeBaseAmount').value=f.base_amount||0;
  document.getElementById('feeStudentAmt').value=f.student_amount||'';
  document.getElementById('feeTaxRate').value=f.tax_rate_pct||0;
  document.getElementById('feeIsTaxable').value=f.is_taxable||0;
  document.getElementById('feeEffectiveFrom').value=f.effective_from||'<?=date('Y-m-d')?>';
  openModal('modalNewFee');
}
async function saveFee(){
  const f=document.getElementById('formNewFee');
  const data={action:'save_fee',fee_id:document.getElementById('editFeeId').value,service_name:f.querySelector('[name=service_name]').value,service_code:f.querySelector('[name=service_code]').value,category_id:f.querySelector('[name=category_id]').value,base_amount:f.querySelector('[name=base_amount]').value,student_amount:f.querySelector('[name=student_amount]').value,tax_rate_pct:f.querySelector('[name=tax_rate_pct]').value,is_taxable:f.querySelector('[name=is_taxable]').value,effective_from:f.querySelector('[name=effective_from]').value};
  const d=await finAction(data);
  if(d.success){ toast('Fee saved!','success'); closeModal('modalNewFee'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
async function toggleFeeActive(id,current){
  const action=current?'deactivate_fee':'activate_fee';
  const d=await finAction({action,fee_id:id});
  if(d.success){ toast(current?'Fee deactivated.':'Fee activated.','success'); setTimeout(()=>location.reload(),1000); }
  else toast(d.message||'Error.','danger');
}

// ── Reports ──────────────────────────────────────────────────
async function generateReport(type, format){
  const from=document.getElementById('rpt_from_'+type)?.value||'<?=$month_start?>';
  const to=document.getElementById('rpt_to_'+type)?.value||'<?=$today?>';
  toast('Generating '+format.toUpperCase()+' report...','info');
  if(format==='pdf'||format==='xlsx') window.open(`/RMU-Medical-Management-System/php/finance/generate_report.php?type=${type}&format=${format}&from=${from}&to=${to}`,'_blank');
  else window.open(`/RMU-Medical-Management-System/php/finance/generate_report.php?type=${type}&format=csv&from=${from}&to=${to}`,'_blank');
}
function exportAnalytics(){ generateReport('analytics_export','csv'); }
function loadAnalytics(){
  const from=document.getElementById('analyticsFrom').value;
  const to=document.getElementById('analyticsTo').value;
  toast('Refreshing analytics for '+from+' to '+to,'info');
}
function setAnalyticsPeriod(p){
  ['Daily','Weekly','Monthly'].forEach(x=>document.getElementById('btn'+x)?.classList.remove('adm-btn-primary'));
  document.getElementById('btn'+p.charAt(0).toUpperCase()+p.slice(1))?.classList.add('adm-btn-primary');
}

// ── Analytics Charts ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded',()=>{
  const isDark=document.documentElement.getAttribute('data-theme')==='dark';
  const tc=isDark?'#9AAECB':'#5A6A85';
  const gc=isDark?'rgba(255,255,255,.07)':'rgba(0,0,0,.06)';

  const revByCat=document.getElementById('chartRevByCategory');
  if(revByCat){
    fetch('/RMU-Medical-Management-System/php/finance/finance_actions.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'analytics_rev_by_category',from:'<?=$month_start?>',to:'<?=$today?>'})})
    .then(r=>r.json()).then(d=>{
      if(d.labels) new Chart(revByCat,{type:'bar',data:{labels:d.labels,datasets:[{label:'GHS',data:d.values,backgroundColor:'rgba(26,158,110,.75)',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{ticks:{color:tc,callback:v=>'GHS '+v},grid:{color:gc}},x:{ticks:{color:tc},grid:{display:false}}}}});
    }).catch(()=>{});
  }
  const m2ctx=document.getElementById('chartMethod2');
  if(m2ctx&&window.methodLabels&&window.methodData&&methodLabels.length)
    new Chart(m2ctx,{type:'doughnut',data:{labels:methodLabels,datasets:[{data:methodData,backgroundColor:['#1a9e6e','#2F80ED','#d4a017','#9B59B6','#E74C3C','#56CCF2'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:tc,padding:16,font:{size:12}}}}}});
  const t2ctx=document.getElementById('chartRevTrend2');
  if(t2ctx&&window.trendLabels&&trendLabels.length)
    new Chart(t2ctx,{type:'line',data:{labels:trendLabels,datasets:[{label:'Revenue (GHS)',data:trendData,borderColor:'#1a9e6e',backgroundColor:'rgba(26,158,110,.1)',borderWidth:2.5,fill:true,tension:.4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{color:tc,callback:v=>'GHS '+v},grid:{color:gc}},x:{ticks:{color:tc,maxTicksLimit:12},grid:{display:false}}}}});
});
</script>
