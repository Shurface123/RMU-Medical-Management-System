<?php
// TAB: PAYMENTS — Module 3
$pay_list = [];
$q = mysqli_query($conn,
  "SELECT p.*, bi.invoice_number, u.name AS patient_name, u2.name AS processed_by_name
   FROM payments p
   JOIN billing_invoices bi ON p.invoice_id = bi.invoice_id
   JOIN patients pt ON p.patient_id = pt.id
   JOIN users u ON pt.user_id = u.id
   LEFT JOIN users u2 ON p.processed_by = u2.id
   ORDER BY p.created_at DESC LIMIT 150");
if ($q) while ($r = mysqli_fetch_assoc($q)) $pay_list[] = $r;

$inv_for_pay = [];
$iq = mysqli_query($conn,
  "SELECT bi.invoice_id, bi.invoice_number, bi.balance_due, u.name AS patient_name
   FROM billing_invoices bi
   JOIN patients pt ON bi.patient_id = pt.id
   JOIN users u ON pt.user_id = u.id
   WHERE bi.status NOT IN ('Paid','Cancelled','Void','Written Off') AND bi.balance_due > 0
   ORDER BY bi.invoice_number");
if ($iq) while ($r = mysqli_fetch_assoc($iq)) $inv_for_pay[] = $r;
?>
<div id="sec-payments" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-money-bill-transfer" style="color:var(--role-accent);"></i> Payment Processing</h1>
    <p>Record manual payments and view all transaction history</p>
  </div>
  <div style="display:flex;gap:1rem;">
    <button onclick="openModal('modalRecordPayment')" class="btn btn-primary"><span class="btn-text"><i class="fas fa-plus"></i> Record Payment</span></button>
    <button onclick="exportPayments()" class="btn-icon btn btn-ghost"><span class="btn-text"><i class="fas fa-file-export"></i> Export CSV</span></button>
  </div>
</div>

<div class="adm-summary-strip">
  <?php
  $pmethods = ['Cash','Mobile Money','Card','Bank Transfer'];
  foreach($pmethods as $pm):
    $amt = (float)fval($conn,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_method='$pm' AND status='Completed' AND DATE(payment_date)='$today'");
  ?>
  <div class="adm-mini-card">
    <div class="adm-mini-card-num" style="font-size:1.8rem;color:var(--role-accent);">GHS<?=number_format($amt,0)?></div>
    <div class="adm-mini-card-label"><?=$pm?> Today</div>
  </div>
  <?php endforeach; ?>
</div>

<div class="fin-filter-row">
  <div class="adm-search-wrap" style="flex:2;min-width:200px;">
    <i class="fas fa-search"></i>
    <input type="text" id="paySearch" class="adm-search-input" placeholder="Search patient, invoice, receipt..." oninput="filterTable('paySearch','paymentTable')">
  </div>
  <select id="payMethodFilter" onchange="applyPayFilters()">
    <option value="">All Methods</option>
    <?php foreach(['Cash','Mobile Money','Card','Bank Transfer','Paystack','Insurance','Cheque','Other'] as $m): ?>
    <option value="<?=$m?>"><?=$m?></option>
    <?php endforeach; ?>
  </select>
  <select id="payStatusFilter" onchange="applyPayFilters()">
    <option value="">All Statuses</option>
    <?php foreach(['Pending','Completed','Failed','Refunded','Cancelled'] as $s): ?>
    <option value="<?=$s?>"><?=$s?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" id="payDateFrom" onchange="applyPayFilters()">
  <input type="date" id="payDateTo" onchange="applyPayFilters()">
  <button onclick="clearPayFilters()" class="btn btn-ghost btn-sm"><span class="btn-text"><i class="fas fa-xmark"></i></span></button>
</div>

<div class="adm-card">
  <div class="adm-table-wrap">
    <table class="adm-table" id="paymentTable">
      <thead><tr>
        <th>Receipt #</th><th>Patient</th><th>Invoice #</th>
        <th>Amount (GHS)</th><th>Method</th><th>Channel</th>
        <th>Status</th><th>Date</th><th>Processed By</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($pay_list)): ?>
        <tr><td colspan="10" style="text-align:center;padding:4rem;color:var(--text-muted);">
          <i class="fas fa-money-bills" style="font-size:3rem;opacity:.3;display:block;margin-bottom:1rem;"></i>
          No payment records yet.
        </td></tr>
      <?php else: foreach($pay_list as $pay):
        $sc_map=['Completed'=>'success','Pending'=>'warning','Failed'=>'danger','Refunded'=>'info','Cancelled'=>''];
        $sc=$sc_map[$pay['status']]??'info';
      ?>
        <tr data-method="<?=$pay['payment_method']?>" data-status="<?=$pay['status']?>" data-date="<?=substr($pay['payment_date'],0,10)?>">
          <td><strong><?=htmlspecialchars($pay['receipt_number']??'—')?></strong></td>
          <td><?=htmlspecialchars($pay['patient_name']??'—')?></td>
          <td><?=htmlspecialchars($pay['invoice_number']??'—')?></td>
          <td><strong style="color:var(--role-accent);">GHS <?=number_format($pay['amount'],2)?></strong></td>
          <td><?=htmlspecialchars($pay['payment_method'])?></td>
          <td><span class="adm-badge adm-badge-info"><?=htmlspecialchars($pay['channel']??'Counter')?></span></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$pay['status']?></span></td>
          <td><?=date('d M Y, g:i A',strtotime($pay['payment_date']))?></td>
          <td><?=htmlspecialchars($pay['processed_by_name']??'System')?></td>
          <td>
            <div class="adm-table-actions">
              <?php if(!empty($pay['receipt_path'])): ?>
              <a href="/RMU-Medical-Management-System/<?=htmlspecialchars($pay['receipt_path'])?>" target="_blank" class="btn btn-sm btn-ghost" title="Download Receipt"><span class="btn-text"><i class="fas fa-receipt"></i></span></a>
              <?php else: ?>
              <button onclick="generateReceipt(<?=$pay['payment_id']?>)" class="btn btn-sm btn-ghost" title="Generate Receipt"><span class="btn-text"><i class="fas fa-receipt"></i></span></button>
              <?php endif; ?>
              <?php if($pay['status']==='Completed'&&!empty($pay['paystack_reference'])): ?>
              <button onclick="viewPaystackTxn('<?=htmlspecialchars($pay['paystack_reference'])?>')" class="btn btn-primary btn btn-sm" style="background:var(--role-accent-light);color:var(--role-accent);" title="Paystack Details"><span class="btn-text"><i class="fas fa-credit-card"></i></span></button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div><!-- /sec-payments -->

<!-- ══ RECORD PAYMENT MODAL (shared) ══════════════════════ -->
<div class="adm-modal" id="modalRecordPayment">
  <div class="adm-modal-content" style="max-width:640px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-money-bill-wave" style="color:var(--role-accent);"></i> Record Manual Payment</h3>
      <button class="btn btn-primary adm-modal-close" onclick="closeModal('modalRecordPayment')"><span class="btn-text"><i class="fas fa-xmark"></i></span></button>
    </div>
    <div class="adm-modal-body">
      <form id="formRecordPayment">
        <div class="adm-form-group">
          <label>Invoice * <span id="rpInvoiceNum" style="color:var(--role-accent);font-weight:700;"></span></label>
          <select id="rpInvoiceId" name="invoice_id" class="adm-search-input" required onchange="onInvoiceSelect(this)">
            <option value="">— Search Invoice or Patient —</option>
            <?php foreach($inv_for_pay as $inv): ?>
            <option value="<?=$inv['invoice_id']?>" data-balance="<?=$inv['balance_due']?>" data-patient="<?=htmlspecialchars($inv['patient_name'])?>">
              <?=htmlspecialchars($inv['invoice_number'])?> — <?=htmlspecialchars($inv['patient_name'])?> (GHS <?=number_format($inv['balance_due'],2)?> due)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Amount Received (GHS) *</label>
            <input type="number" id="rpAmount" name="amount" class="adm-search-input" step="0.01" min="0.01" required placeholder="0.00">
            <input type="hidden" id="rpMaxAmount">
          </div>
          <div class="adm-form-group">
            <label>Payment Method *</label>
            <select name="payment_method" class="adm-search-input" required>
              <?php foreach(['Cash','Mobile Money','Card','Bank Transfer','Cheque','Other'] as $m): ?>
              <option value="<?=$m?>"><?=$m?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Payment Date *</label>
            <input type="datetime-local" name="payment_date" class="adm-search-input" value="<?=date('Y-m-d\TH:i')?>" required>
          </div>
          <div class="adm-form-group">
            <label>Reference / Transaction ID</label>
            <input type="text" name="reference" class="adm-search-input" placeholder="e.g. MoMo ref, bank ref">
          </div>
        </div>
        <div class="adm-form-group">
          <label>Notes</label>
          <textarea name="notes" class="adm-search-input" rows="2" style="resize:vertical;" placeholder="Optional payment notes..."></textarea>
        </div>
        <div id="rpBalanceInfo" style="padding:1rem;background:var(--surface-2);border-radius:10px;border:1px solid var(--border);font-size:1.25rem;display:none;">
          Outstanding Balance: <strong id="rpBalanceDisplay" style="color:var(--danger);"></strong>
        </div>
      </form>
    </div>
    <div class="adm-modal-footer">
      <button onclick="closeModal('modalRecordPayment')" class="btn btn-ghost"><span class="btn-text">Cancel</span></button>
      <button onclick="submitPayment()" class="btn btn-primary"><span class="btn-text"><i class="fas fa-check-circle"></i> Record & Generate Receipt</span></button>
    </div>
  </div>
</div>

<script>
function onInvoiceSelect(sel) {
  const opt = sel.options[sel.selectedIndex];
  const bal = opt.dataset.balance || 0;
  document.getElementById('rpAmount').value = parseFloat(bal).toFixed(2);
  document.getElementById('rpMaxAmount').value = bal;
  const info = document.getElementById('rpBalanceInfo');
  if(sel.value){ info.style.display='block'; document.getElementById('rpBalanceDisplay').textContent='GHS '+parseFloat(bal).toFixed(2); }
  else info.style.display='none';
}

async function submitPayment() {
  const form = document.getElementById('formRecordPayment');
  const invId = document.getElementById('rpInvoiceId').value;
  if(!invId){ toast('Please select an invoice.','danger'); return; }
  const amount = parseFloat(form.querySelector('[name=amount]').value);
  if(!amount||amount<=0){ toast('Enter a valid amount.','danger'); return; }

  const data = {
    action:'record_manual_payment',
    invoice_id: invId, amount,
    payment_method: form.querySelector('[name=payment_method]').value,
    payment_date:   form.querySelector('[name=payment_date]').value,
    reference:      form.querySelector('[name=reference]').value,
    notes:          form.querySelector('[name=notes]').value
  };
  const btn = document.querySelector('#modalRecordPayment .adm-btn-primary');
  btn.classList.add('loading'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Processing...';
  const res = await finAction(data);
  btn.classList.remove('loading'); btn.innerHTML='<i class="fas fa-check-circle"></i> Record & Generate Receipt';
  if(res.success){
    toast('Payment recorded! Receipt generated.','success');
    if(res.receipt_url) window.open(res.receipt_url,'_blank');
    closeModal('modalRecordPayment');
    setTimeout(()=>location.reload(),1500);
  } else toast(res.message||'Error recording payment.','danger');
}

function applyPayFilters(){
  const method = document.getElementById('payMethodFilter').value;
  const status = document.getElementById('payStatusFilter').value;
  const from   = document.getElementById('payDateFrom').value;
  const to     = document.getElementById('payDateTo').value;
  document.querySelectorAll('#paymentTable tbody tr').forEach(r=>{
    const rm=r.dataset.method||'', rs=r.dataset.status||'', rd=r.dataset.date||'';
    const ok = (!method||rm===method)&&(!status||rs===status)&&(!from||rd>=from)&&(!to||rd<=to);
    r.style.display=ok?'':'none';
  });
}
function clearPayFilters(){
  ['payMethodFilter','payStatusFilter','payDateFrom','payDateTo'].forEach(id=>{ const el=document.getElementById(id); if(el) el.value=''; });
  document.querySelectorAll('#paymentTable tbody tr').forEach(r=>r.style.display='');
}
async function generateReceipt(payId){
  const d=await finAction({action:'generate_receipt',payment_id:payId});
  if(d.success){ toast('Receipt generated!','success'); if(d.url) window.open(d.url,'_blank'); }
  else toast(d.message||'Error.','danger');
}
function viewPaystackTxn(ref){ showTab('paystack',document.querySelector('.adm-nav-item[onclick*=paystack]')); setTimeout(()=>{ const s=document.getElementById('psSearch'); if(s){s.value=ref;filterTable('psSearch','paystackTable');} },150); }
function exportPayments(){ window.open('/RMU-Medical-Management-System/php/finance/export_payments.php?format=csv','_blank'); }
</script>
