<?php
// TAB: REFUNDS — Module 7
$refunds=[];
$q=mysqli_query($conn,"SELECT r.*,bi.invoice_number,u.name AS patient_name,u2.name AS approver_name
   FROM refunds r JOIN billing_invoices bi ON r.invoice_id=bi.invoice_id
   JOIN patients pt ON r.patient_id=pt.id JOIN users u ON pt.user_id=u.id
   LEFT JOIN users u2 ON r.approved_by=u2.id
   ORDER BY r.created_at DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $refunds[]=$r;
// payments available for refund
$pay_for_refund=[];
$pq=mysqli_query($conn,"SELECT p.payment_id,p.payment_reference,p.amount,p.payment_method,p.paystack_reference,bi.invoice_number,u.name AS patient_name
   FROM payments p JOIN billing_invoices bi ON p.invoice_id=bi.invoice_id
   JOIN patients pt ON p.patient_id=pt.id JOIN users u ON pt.user_id=u.id
   WHERE p.status='Completed' ORDER BY p.created_at DESC LIMIT 100");
if($pq) while($r=mysqli_fetch_assoc($pq)) $pay_for_refund[]=$r;
?>
<div id="sec-refunds" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-rotate-left" style="color:var(--role-accent);"></i> Refunds</h1>
    <p>Process and track all payment refunds including Paystack reversals</p>
  </div>
  <button onclick="openModal('modalInitRefund')" class="btn btn-primary"><span class="btn-text"><i class="fas fa-plus"></i> Initiate Refund</span></button>
</div>

<div class="adm-summary-strip">
  <?php foreach([['Pending Approval','warning'],['Approved','success'],['Completed','success'],['Failed','danger']] as [$st,$cl]):
    $cnt=(int)fval($conn,"SELECT COUNT(*) FROM refunds WHERE status='$st'");
    $val=(float)fval($conn,"SELECT COALESCE(SUM(refund_amount),0) FROM refunds WHERE status='$st'");
  ?>
  <div class="adm-mini-card">
    <div class="adm-mini-card-num <?=$cl?>"><?=$cnt?></div>
    <div class="adm-mini-card-label"><?=$st?> — GHS <?=number_format($val,0)?></div>
  </div>
  <?php endforeach;?>
</div>

<div class="adm-card">
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr>
        <th>Refund Ref</th><th>Patient</th><th>Invoice #</th><th>Amount (GHS)</th>
        <th>Method</th><th>Paystack Ref</th><th>Status</th><th>Approved By</th><th>Date</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($refunds)): ?>
        <tr><td colspan="10" style="text-align:center;padding:3rem;color:var(--text-muted);">No refunds yet.</td></tr>
      <?php else: foreach($refunds as $rf):
        $sc=['Pending Approval'=>'warning','Approved'=>'info','Processing'=>'warning','Completed'=>'success','Rejected'=>'danger','Failed'=>'danger'][$rf['status']]??'info';
      ?>
        <tr>
          <td><strong><?=htmlspecialchars($rf['refund_reference'])?></strong></td>
          <td><?=htmlspecialchars($rf['patient_name']??'—')?></td>
          <td><?=htmlspecialchars($rf['invoice_number']??'—')?></td>
          <td style="color:var(--danger);font-weight:700;">GHS <?=number_format($rf['refund_amount'],2)?></td>
          <td><?=htmlspecialchars($rf['refund_method'])?></td>
          <td style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($rf['paystack_refund_reference']??'—')?></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=htmlspecialchars($rf['status'])?></span></td>
          <td><?=htmlspecialchars($rf['approver_name']??'—')?></td>
          <td><?=date('d M Y',strtotime($rf['created_at']))?></td>
          <td>
            <?php if($rf['status']==='Pending Approval'&&$user_role==='finance_manager'): ?>
            <div class="adm-table-actions">
              <button onclick="approveRefund(<?=$rf['refund_id']?>)" class="btn btn-sm btn-success"><span class="btn-text"><i class="fas fa-check"></i></span></button>
              <button onclick="rejectRefund(<?=$rf['refund_id']?>)" class="btn btn-sm btn-danger"><span class="btn-text"><i class="fas fa-xmark"></i></span></button>
            </div>
            <?php endif;?>
          </td>
        </tr>
      <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>
</div>
</div><!-- /sec-refunds -->

<!-- Initiate Refund Modal -->
<div class="adm-modal" id="modalInitRefund">
  <div class="adm-modal-content" style="max-width:660px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-rotate-left" style="color:var(--role-accent);"></i> Initiate Refund</h3>
      <button class="btn btn-primary adm-modal-close" onclick="closeModal('modalInitRefund')"><span class="btn-text"><i class="fas fa-xmark"></i></span></button>
    </div>
    <div class="adm-modal-body">
      <form id="formInitRefund">
        <div class="adm-form-group">
          <label>Select Payment to Refund *</label>
          <select name="payment_id" class="adm-search-input" required onchange="onRefundPaymentSelect(this)">
            <option value="">— Select Payment —</option>
            <?php foreach($pay_for_refund as $pf): ?>
            <option value="<?=$pf['payment_id']?>" data-amount="<?=$pf['amount']?>" data-method="<?=htmlspecialchars($pf['payment_method'])?>" data-ps="<?=htmlspecialchars($pf['paystack_reference']??'')?>">
              <?=htmlspecialchars($pf['payment_reference'])?> — <?=htmlspecialchars($pf['patient_name'])?> — GHS <?=number_format($pf['amount'],2)?>
            </option>
            <?php endforeach;?>
          </select>
          <div id="refundPayInfo" style="display:none;padding:.8rem 1rem;background:var(--surface-2);border-radius:8px;font-size:1.2rem;margin-top:.5rem;border:1px solid var(--border);"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Refund Amount (GHS) *</label>
            <input type="number" name="refund_amount" id="refundAmtInput" class="adm-search-input" step="0.01" min="0.01" required>
          </div>
          <div class="adm-form-group">
            <label>Refund Method *</label>
            <select name="refund_method" id="refundMethodSel" class="adm-search-input" required>
              <?php foreach(['Cash','Mobile Money','Card Reversal','Bank Transfer','Paystack Refund','Other'] as $m): ?><option><?=$m?></option><?php endforeach;?>
            </select>
          </div>
        </div>
        <div id="paystackRefundWarning" style="display:none;" class="adm-alert adm-alert-info">
          <i class="fas fa-info-circle"></i>
          <div>This will call the <strong>Paystack Refunds API</strong> automatically on approval.</div>
        </div>
        <div class="adm-form-group">
          <label>Reason *</label>
          <textarea name="reason" class="adm-search-input" rows="2" style="resize:vertical;" required placeholder="Reason for refund..."></textarea>
        </div>
      </form>
    </div>
    <div class="adm-modal-footer">
      <button onclick="closeModal('modalInitRefund')" class="btn btn-ghost"><span class="btn-text">Cancel</span></button>
      <button onclick="submitRefund()" class="btn btn-danger"><span class="btn-text"><i class="fas fa-rotate-left"></i> Submit Refund Request</span></button>
    </div>
  </div>
</div>

<script>
function onRefundPaymentSelect(sel){
  const opt=sel.options[sel.selectedIndex];
  const box=document.getElementById('refundPayInfo');
  const warn=document.getElementById('paystackRefundWarning');
  const methodSel=document.getElementById('refundMethodSel');
  if(sel.value){
    box.style.display='block'; document.getElementById('refundAmtInput').value=parseFloat(opt.dataset.amount||0).toFixed(2);
    box.innerHTML=`Payment: <strong>${opt.text.split('—')[0].trim()}</strong> &mdash; Method: <strong>${opt.dataset.method}</strong> &mdash; Amount: <strong style="color:var(--danger)">GHS ${parseFloat(opt.dataset.amount||0).toFixed(2)}</strong>`;
    if(opt.dataset.ps){ warn.style.display='flex'; for(let o of methodSel.options) if(o.value==='Paystack Refund'){o.selected=true;break;} }
    else warn.style.display='none';
  } else { box.style.display='none'; warn.style.display='none'; }
}
async function submitRefund(){
  const f=document.getElementById('formInitRefund');
  const d=await finAction({action:'initiate_refund',payment_id:f.querySelector('[name=payment_id]').value,refund_amount:f.querySelector('[name=refund_amount]').value,refund_method:f.querySelector('[name=refund_method]').value,reason:f.querySelector('[name=reason]').value});
  if(d.success){ toast(d.requires_approval?'Refund submitted for manager approval.':'Refund processed!','success'); closeModal('modalInitRefund'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
async function approveRefund(id){
  if(!confirm('Approve this refund? Paystack API will be called if applicable.')) return;
  const d=await finAction({action:'approve_refund',refund_id:id});
  if(d.success){ toast('Refund approved and processing!','success'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
async function rejectRefund(id){
  const reason=prompt('Reason for rejecting this refund:');
  if(reason===null) return;
  const d=await finAction({action:'reject_refund',refund_id:id,reason});
  if(d.success){ toast('Refund rejected.','info'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
</script>
