<?php
// TAB: RECONCILIATION — Module 8
$recon_date = $_GET['recon_date'] ?? $today;
$daily_rpt = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM daily_cash_reports WHERE report_date='$recon_date' LIMIT 1")) ?: [];
?>
<div id="sec-reconciliation" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-scale-balanced" style="color:var(--role-accent);"></i> Daily Cash Reconciliation</h1>
    <p>Verify and reconcile all transactions for a given day</p>
  </div>
  <div style="display:flex;gap:1rem;align-items:center;">
    <input type="date" id="reconDatePicker" value="<?=$recon_date?>" max="<?=$today?>" class="adm-search-input" style="width:180px;" onchange="loadReconciliation(this.value)">
    <button onclick="loadReconciliation(document.getElementById('reconDatePicker').value)" class="adm-btn adm-btn-primary"><i class="fas fa-rotate"></i> Load</button>
    <?php if(!empty($daily_rpt)&&$daily_rpt['status']!=='Reconciled'&&$user_role==='finance_manager'): ?>
    <button onclick="markReconciled()" class="adm-btn adm-btn-success"><i class="fas fa-check-double"></i> Mark Reconciled</button>
    <?php endif;?>
    <button onclick="exportReconReport()" class="adm-btn adm-btn-ghost"><i class="fas fa-file-pdf"></i> Export PDF</button>
  </div>
</div>

<?php if(!empty($daily_rpt)&&$daily_rpt['status']==='Reconciled'): ?>
<div class="adm-alert adm-alert-success" style="margin-bottom:1.5rem;">
  <i class="fas fa-lock"></i>
  <div><strong>Day Locked</strong> — This day has been reconciled and cannot be edited. Reconciled by <?=htmlspecialchars($daily_rpt['reconciled_by']??'manager')?> on <?=date('d M Y',strtotime($daily_rpt['reconciled_at']??$today))?>.</div>
</div>
<?php endif;?>

<div id="reconContent">
<?php
// Compute on-the-fly
$rdt = $recon_date;
$totals = [
  'cash'       => (float)fval($conn,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(payment_date)='$rdt' AND payment_method='Cash' AND status='Completed'"),
  'mobile'     => (float)fval($conn,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(payment_date)='$rdt' AND payment_method='Mobile Money' AND status='Completed'"),
  'card'       => (float)fval($conn,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(payment_date)='$rdt' AND payment_method IN ('Card','Paystack') AND status='Completed'"),
  'bank'       => (float)fval($conn,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(payment_date)='$rdt' AND payment_method='Bank Transfer' AND status='Completed'"),
  'insurance'  => (float)fval($conn,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(payment_date)='$rdt' AND payment_method='Insurance' AND status='Completed'"),
  'refunds'    => (float)fval($conn,"SELECT COALESCE(SUM(refund_amount),0) FROM refunds WHERE DATE(created_at)='$rdt' AND status='Completed'"),
  'waivers'    => (float)fval($conn,"SELECT COALESCE(SUM(waived_amount),0) FROM payment_waivers WHERE DATE(approved_at)='$rdt' AND status='Approved'"),
];
$gross = $totals['cash']+$totals['mobile']+$totals['card']+$totals['bank']+$totals['insurance'];
$net   = $gross - $totals['refunds'] - $totals['waivers'];
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
  <!-- Breakdown -->
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-money-bill-wave"></i> Revenue Breakdown — <?=date('d M Y',strtotime($rdt))?></h3></div>
    <div class="adm-card-body">
      <?php foreach([
        ['Cash Received',        $totals['cash'],    'green'],
        ['Mobile Money',         $totals['mobile'],  'blue'],
        ['Card / Paystack',      $totals['card'],    'blue'],
        ['Bank Transfer',        $totals['bank'],    'blue'],
        ['Insurance Receipts',   $totals['insurance'],'purple'],
      ] as [$label,$amt,$col]): ?>
      <div style="display:flex;justify-content:space-between;padding:1rem 0;border-bottom:1px solid var(--border);font-size:1.35rem;">
        <span style="color:var(--text-secondary);"><?=$label?></span>
        <strong style="color:var(--<?=($col==='green'?'role-accent':'primary')?>);">GHS <?=number_format($amt,2)?></strong>
      </div>
      <?php endforeach;?>
      <div style="display:flex;justify-content:space-between;padding:1rem 0;border-bottom:1px solid var(--border);font-size:1.35rem;">
        <span style="color:var(--danger);">Less Refunds</span><strong style="color:var(--danger);">- GHS <?=number_format($totals['refunds'],2)?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;padding:1rem 0;border-bottom:1px solid var(--border);font-size:1.35rem;">
        <span style="color:var(--warning);">Less Waivers</span><strong style="color:var(--warning);">- GHS <?=number_format($totals['waivers'],2)?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;padding:1.4rem 0;font-size:1.7rem;font-weight:800;margin-top:.5rem;border-top:2px solid var(--border);">
        <span>Net Revenue</span><span style="color:var(--role-accent);">GHS <?=number_format($net,2)?></span>
      </div>
    </div>
  </div>

  <!-- Paystack Reconciliation -->
  <div class="adm-card">
    <div class="adm-card-header">
      <h3><i class="fas fa-credit-card"></i> Paystack Reconciliation</h3>
      <button onclick="fetchPaystackReconcile('<?=$rdt?>')" class="adm-btn adm-btn-sm adm-btn-primary"><i class="fas fa-rotate"></i> Fetch from Paystack</button>
    </div>
    <div class="adm-card-body" id="paystackReconPanel">
      <div style="text-align:center;padding:3rem;color:var(--text-muted);">
        <i class="fas fa-credit-card" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:1rem;"></i>
        <p>Click "Fetch from Paystack" to compare Paystack transactions against your internal records.</p>
      </div>
    </div>
  </div>
</div>

<!-- Transaction Detail Table -->
<div class="adm-card" style="margin-top:2rem;">
  <div class="adm-card-header"><h3><i class="fas fa-list-ul"></i> All Transactions — <?=date('d M Y',strtotime($rdt))?></h3></div>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Time</th><th>Patient</th><th>Invoice #</th><th>Receipt #</th><th>Amount (GHS)</th><th>Method</th><th>Status</th></tr></thead>
      <tbody>
      <?php
      $day_pays=[];
      $dq=mysqli_query($conn,"SELECT p.*,bi.invoice_number,u.name AS patient_name FROM payments p JOIN billing_invoices bi ON p.invoice_id=bi.invoice_id JOIN patients pt ON p.patient_id=pt.id JOIN users u ON pt.user_id=u.id WHERE DATE(p.payment_date)='$rdt' ORDER BY p.payment_date");
      if($dq) while($r=mysqli_fetch_assoc($dq)) $day_pays[]=$r;
      if(empty($day_pays)): ?>
        <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No transactions for this date.</td></tr>
      <?php else: foreach($day_pays as $dp):
        $sc=['Completed'=>'success','Pending'=>'warning','Failed'=>'danger'][$dp['status']]??'info';
      ?>
        <tr>
          <td><?=date('g:i A',strtotime($dp['payment_date']))?></td>
          <td><?=htmlspecialchars($dp['patient_name']??'—')?></td>
          <td><?=htmlspecialchars($dp['invoice_number']??'—')?></td>
          <td><?=htmlspecialchars($dp['receipt_number']??'—')?></td>
          <td><strong>GHS <?=number_format($dp['amount'],2)?></strong></td>
          <td><?=htmlspecialchars($dp['payment_method'])?></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$dp['status']?></span></td>
        </tr>
      <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>
</div>
</div><!-- /reconContent -->
</div><!-- /sec-reconciliation -->

<script>
function loadReconciliation(date){ window.location.href='?tab=reconciliation&recon_date='+date; }
async function fetchPaystackReconcile(date){
  document.getElementById('paystackReconPanel').innerHTML='<div style="text-align:center;padding:3rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--role-accent);"></i><p style="margin-top:1rem;color:var(--text-muted);">Fetching from Paystack API...</p></div>';
  const d=await finAction({action:'paystack_reconcile_date',date});
  if(d.success){
    let html=`<div style="margin-bottom:1.5rem;display:flex;gap:1.5rem;flex-wrap:wrap;">
      <div class="adm-mini-card"><div class="adm-mini-card-num green">${d.paystack_count}</div><div class="adm-mini-card-label">Paystack Records</div></div>
      <div class="adm-mini-card"><div class="adm-mini-card-num">${d.internal_count}</div><div class="adm-mini-card-label">Internal Records</div></div>
      <div class="adm-mini-card"><div class="adm-mini-card-num ${d.discrepancies>0?'red':'green'}">${d.discrepancies}</div><div class="adm-mini-card-label">Discrepancies</div></div>
    </div>`;
    if(d.discrepancies>0&&d.missing?.length){
      html+='<div class="adm-alert adm-alert-warning"><i class="fas fa-triangle-exclamation"></i><div><strong>Missing in internal records:</strong><br>'+d.missing.map(r=>`${r.reference} — GHS ${parseFloat(r.amount).toFixed(2)}`).join('<br>')+'</div></div>';
    } else { html+='<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i><div>All Paystack transactions match internal records.</div></div>'; }
    document.getElementById('paystackReconPanel').innerHTML=html;
  } else document.getElementById('paystackReconPanel').innerHTML='<p style="color:var(--danger);padding:2rem;text-align:center;">'+( d.message||'Failed to fetch from Paystack.')+'</p>';
}
async function markReconciled(){
  if(!confirm('Mark this day as reconciled? This action locks the records.')) return;
  const d=await finAction({action:'mark_reconciled',date:document.getElementById('reconDatePicker').value});
  if(d.success){ toast('Day marked as reconciled and locked.','success'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
function exportReconReport(){ window.open(`/RMU-Medical-Management-System/php/finance/export_reconciliation.php?date=${document.getElementById('reconDatePicker').value}`,'_blank'); }
</script>
