<?php
// TAB: PAYSTACK TRANSACTIONS — Module 3 (Paystack view)
$ps_list = [];
$q = mysqli_query($conn,
  "SELECT pt.*, p.invoice_id, p.patient_id, u.name AS patient_name, bi.invoice_number
   FROM paystack_transactions pt
   LEFT JOIN payments p ON pt.payment_id = p.payment_id
   LEFT JOIN patients pat ON p.patient_id = pat.id
   LEFT JOIN users u ON pat.user_id = u.id
   LEFT JOIN billing_invoices bi ON p.invoice_id = bi.invoice_id
   ORDER BY pt.created_at DESC LIMIT 200");
if ($q) while ($r = mysqli_fetch_assoc($q)) $ps_list[] = $r;

$ps_today_vol = (float)fval($conn,"SELECT COALESCE(SUM(amount_ghs),0) FROM paystack_transactions WHERE DATE(created_at)='$today' AND status='Success'");
$ps_today_cnt = (int)fval($conn,"SELECT COUNT(*) FROM paystack_transactions WHERE DATE(created_at)='$today' AND status='Success'");
$ps_failed    = (int)fval($conn,"SELECT COUNT(*) FROM paystack_transactions WHERE DATE(created_at)='$today' AND status='Failed'");
?>
<div id="sec-paystack" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-credit-card" style="color:var(--role-accent);"></i> Paystack Transactions</h1>
    <p>All online payments processed via Paystack — verification and reconciliation</p>
  </div>
  <div style="display:flex;gap:1rem;">
    <button onclick="reconcilePaystack()" class="adm-btn adm-btn-primary"><i class="fas fa-scale-balanced"></i> Reconcile with Paystack</button>
    <button onclick="exportPaystack()" class="adm-btn adm-btn-ghost"><i class="fas fa-file-csv"></i> Export CSV</button>
  </div>
</div>

<div class="adm-summary-strip">
  <div class="adm-mini-card">
    <div class="adm-mini-card-num green"><?=$ps_today_cnt?></div>
    <div class="adm-mini-card-label"><i class="fas fa-check"></i> Successful Today</div>
  </div>
  <div class="adm-mini-card">
    <div class="adm-mini-card-num" style="font-size:1.8rem;color:var(--role-accent);">GHS <?=number_format($ps_today_vol,2)?></div>
    <div class="adm-mini-card-label">Volume Today</div>
  </div>
  <div class="adm-mini-card">
    <div class="adm-mini-card-num red"><?=$ps_failed?></div>
    <div class="adm-mini-card-label"><i class="fas fa-xmark"></i> Failed Today</div>
  </div>
  <div class="adm-mini-card">
    <div class="adm-mini-card-num"><?=count($ps_list)?></div>
    <div class="adm-mini-card-label">Total Loaded</div>
  </div>
</div>

<div class="fin-filter-row">
  <div class="adm-search-wrap" style="flex:2;min-width:200px;">
    <i class="fas fa-search"></i>
    <input type="text" id="psSearch" class="adm-search-input" placeholder="Search reference, patient..." oninput="filterTable('psSearch','paystackTable')">
  </div>
  <select id="psStatusFilter" onchange="applyPsFilters()">
    <option value="">All Statuses</option>
    <?php foreach(['Initialized','Pending','Success','Failed','Abandoned','Reversed'] as $s): ?>
    <option><?=$s?></option>
    <?php endforeach; ?>
  </select>
  <select id="psChannelFilter" onchange="applyPsFilters()">
    <option value="">All Channels</option>
    <option value="card">Card</option>
    <option value="mobile_money">Mobile Money</option>
    <option value="bank">Bank</option>
    <option value="ussd">USSD</option>
  </select>
  <input type="date" id="psDateFrom" onchange="applyPsFilters()">
  <input type="date" id="psDateTo" onchange="applyPsFilters()">
  <button onclick="clearPsFilters()" class="adm-btn adm-btn-ghost adm-btn-sm"><i class="fas fa-xmark"></i></button>
</div>

<!-- Discrepancy Alert -->
<div id="psDiscrepancyAlert" style="display:none;" class="adm-alert adm-alert-warning" style="margin-bottom:1.5rem;">
  <i class="fas fa-triangle-exclamation"></i>
  <div><strong>Discrepancies found</strong> — Some Paystack transactions don't match internal records. <button onclick="viewDiscrepancies()" class="adm-btn adm-btn-sm adm-btn-warning" style="margin-left:.5rem;">View Details</button></div>
</div>

<div class="adm-card">
  <div class="adm-table-wrap">
    <table class="adm-table" id="paystackTable">
      <thead><tr>
        <th>Paystack Reference</th><th>Patient</th><th>Invoice #</th>
        <th>Amount (GHS)</th><th>Channel</th><th>Gateway Response</th>
        <th>Status</th><th>Paid At</th><th>Webhook</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($ps_list)): ?>
        <tr><td colspan="10" style="text-align:center;padding:4rem;color:var(--text-muted);">
          <i class="fas fa-credit-card" style="font-size:3rem;opacity:.3;display:block;margin-bottom:1rem;"></i>
          No Paystack transactions yet.
        </td></tr>
      <?php else: foreach($ps_list as $ps):
        $sc_map=['Success'=>'success','Failed'=>'danger','Pending'=>'warning','Abandoned'=>'','Reversed'=>'info','Initialized'=>'info'];
        $sc=$sc_map[$ps['status']]??'info';
      ?>
        <tr data-status="<?=$ps['status']?>" data-channel="<?=htmlspecialchars($ps['channel']??'')?>" data-date="<?=substr($ps['created_at'],0,10)?>">
          <td><strong style="font-size:1.1rem;"><?=htmlspecialchars($ps['paystack_reference'])?></strong></td>
          <td><?=htmlspecialchars($ps['patient_name']??'—')?></td>
          <td><?=htmlspecialchars($ps['invoice_number']??'—')?></td>
          <td><strong style="color:var(--role-accent);">GHS <?=number_format($ps['amount_ghs'],2)?></strong></td>
          <td><?=htmlspecialchars(ucwords(str_replace('_',' ',$ps['channel']??'—')))?></td>
          <td style="font-size:1.2rem;color:var(--text-secondary);"><?=htmlspecialchars($ps['gateway_response']??'—')?></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=$ps['status']?></span></td>
          <td><?=$ps['paid_at']?date('d M Y, g:i A',strtotime($ps['paid_at'])):' —'?></td>
          <td>
            <?php if($ps['webhook_signature_valid']===null): ?>
              <span class="adm-badge" style="background:#f0f0f0;color:#666;">No webhook</span>
            <?php elseif($ps['webhook_signature_valid']): ?>
              <span class="adm-badge adm-badge-success"><i class="fas fa-check"></i> Valid</span>
            <?php else: ?>
              <span class="adm-badge adm-badge-danger"><i class="fas fa-xmark"></i> Invalid</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="adm-table-actions">
              <button onclick="verifyPaystackTxn('<?=htmlspecialchars($ps['paystack_reference'])?>')" class="adm-btn adm-btn-sm adm-btn-ghost" title="Verify with Paystack API"><i class="fas fa-shield-check"></i></button>
              <button onclick="viewRawPayload(<?=$ps['transaction_id']?>)" class="adm-btn adm-btn-sm" style="background:var(--surface-2);color:var(--text-secondary);" title="Raw Payload"><i class="fas fa-code"></i></button>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div><!-- /sec-paystack -->

<!-- Raw Payload Modal -->
<div class="adm-modal" id="modalRawPayload">
  <div class="adm-modal-content" style="max-width:700px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-code"></i> Paystack Raw Payload</h3>
      <button class="adm-modal-close" onclick="closeModal('modalRawPayload')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="adm-modal-body">
      <pre id="rawPayloadContent" style="background:var(--surface-2);padding:1.5rem;border-radius:10px;font-size:1.15rem;overflow:auto;max-height:400px;white-space:pre-wrap;word-break:break-all;"></pre>
    </div>
  </div>
</div>

<script>
function applyPsFilters(){
  const status = document.getElementById('psStatusFilter').value;
  const channel= document.getElementById('psChannelFilter').value;
  const from   = document.getElementById('psDateFrom').value;
  const to     = document.getElementById('psDateTo').value;
  document.querySelectorAll('#paystackTable tbody tr').forEach(r=>{
    const ok=(!status||r.dataset.status===status)&&(!channel||r.dataset.channel===channel)&&(!from||r.dataset.date>=from)&&(!to||r.dataset.date<=to);
    r.style.display=ok?'':'none';
  });
}
function clearPsFilters(){
  ['psStatusFilter','psChannelFilter','psDateFrom','psDateTo'].forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});
  document.querySelectorAll('#paystackTable tbody tr').forEach(r=>r.style.display='');
}
async function verifyPaystackTxn(ref){
  toast('Verifying with Paystack API...','info');
  const d=await finAction({action:'verify_paystack',reference:ref});
  if(d.success) toast(`Verified: ${d.status} — GHS ${d.amount}`,'success');
  else toast(d.message||'Verification failed.','danger');
}
async function viewRawPayload(txnId){
  const d=await finAction({action:'get_paystack_raw',transaction_id:txnId});
  openModal('modalRawPayload');
  document.getElementById('rawPayloadContent').textContent = d.raw ? JSON.stringify(JSON.parse(d.raw),null,2) : 'No payload stored.';
}
async function reconcilePaystack(){
  const date = prompt('Reconcile for date (YYYY-MM-DD):','<?=date('Y-m-d')?>');
  if(!date) return;
  toast('Fetching Paystack transactions...','info');
  const d=await finAction({action:'reconcile_paystack',date});
  if(d.success){
    if(d.discrepancies>0){
      document.getElementById('psDiscrepancyAlert').style.display='flex';
      toast(`Reconciliation complete. ${d.discrepancies} discrepanc${d.discrepancies===1?'y':'ies'} found.`,'warning');
    } else toast('Reconciliation complete. All records match!','success');
  } else toast(d.message||'Reconciliation failed.','danger');
}
function exportPaystack(){window.open('/RMU-Medical-Management-System/php/finance/export_paystack.php?format=csv','_blank');}
function viewDiscrepancies(){toast('Opening discrepancy report...','info');}
</script>
