<?php
// TAB: WAIVERS — Module 6
$waivers=[];
$q=mysqli_query($conn,"SELECT pw.*,bi.invoice_number,u.name AS patient_name,u2.name AS approver_name,u3.name AS creator_name
   FROM payment_waivers pw JOIN billing_invoices bi ON pw.invoice_id=bi.invoice_id
   JOIN patients pt ON pw.patient_id=pt.id JOIN users u ON pt.user_id=u.id
   LEFT JOIN users u2 ON pw.approved_by=u2.id LEFT JOIN users u3 ON pw.created_by=u3.id
   ORDER BY pw.created_at DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $waivers[]=$r;
?>
<div id="sec-waivers" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-percent" style="color:var(--role-accent);"></i> Waivers & Discounts</h1>
    <p>Request, approve, and track payment waivers for patients</p>
  </div>
  <button onclick="openModal('modalNewWaiver')" class="btn btn-primary"><span class="btn-text"><i class="fas fa-plus"></i> New Waiver Request</span></button>
</div>

<div class="adm-summary-strip">
  <?php foreach([['Pending','warning'],['Approved','success'],['Rejected','danger']] as [$st,$cl]):
    $cnt=(int)fval($conn,"SELECT COUNT(*) FROM payment_waivers WHERE status='$st'");
    $val=(float)fval($conn,"SELECT COALESCE(SUM(waived_amount),0) FROM payment_waivers WHERE status='$st'");
  ?>
  <div class="adm-mini-card">
    <div class="adm-mini-card-num <?=$cl?>"><?=$cnt?></div>
    <div class="adm-mini-card-label"><?=$st?> — GHS <?=number_format($val,0)?></div>
  </div>
  <?php endforeach;?>
</div>

<div class="fin-filter-row">
  <div class="adm-search-wrap" style="flex:2;min-width:200px;">
    <i class="fas fa-search"></i>
    <input type="text" id="waiverSearch" class="adm-search-input" placeholder="Search waiver #, patient..." oninput="filterTable('waiverSearch','waiverTable')">
  </div>
  <select id="waiverStatusFilter" onchange="document.querySelectorAll('#waiverTable tbody tr').forEach(r=>{r.style.display=(!this.value||r.dataset.status===this.value)?'':'none';})">
    <option value="">All Statuses</option>
    <?php foreach(['Pending','Approved','Rejected','Revoked'] as $s): ?><option><?=$s?></option><?php endforeach;?>
  </select>
</div>

<div class="adm-card">
  <div class="adm-table-wrap">
    <table class="adm-table" id="waiverTable">
      <thead><tr>
        <th>Waiver #</th><th>Patient</th><th>Invoice #</th><th>Type</th>
        <th>Original (GHS)</th><th>Waived (GHS)</th><th>Remaining (GHS)</th>
        <th>Status</th><th>Approved By</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($waivers)): ?>
        <tr><td colspan="10" style="text-align:center;padding:3rem;color:var(--text-muted);">No waivers yet.</td></tr>
      <?php else: foreach($waivers as $w):
        $sc=['Pending'=>'warning','Approved'=>'success','Rejected'=>'danger','Revoked'=>''][$w['status']]??'info';
      ?>
        <tr data-status="<?=$w['status']?>">
          <td><strong><?=htmlspecialchars($w['waiver_number'])?></strong></td>
          <td><?=htmlspecialchars($w['patient_name']??'—')?></td>
          <td><?=htmlspecialchars($w['invoice_number']?? '—')?></td>
          <td><span class="adm-badge adm-badge-info" style="font-size:1rem;"><?=htmlspecialchars($w['waiver_type'])?></span></td>
          <td><?=number_format($w['original_amount'],2)?></td>
          <td style="color:var(--success);font-weight:700;"><?=number_format($w['waived_amount'],2)?></td>
          <td style="color:var(--warning);"><?=number_format($w['remaining_amount'],2)?></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=htmlspecialchars($w['status'])?></span></td>
          <td><?=htmlspecialchars($w['approver_name']??'—')?></td>
          <td>
            <?php if($w['status']==='Pending'&&$user_role==='finance_manager'): ?>
            <div class="adm-table-actions">
              <button onclick="approveWaiver(<?=$w['waiver_id']?>)" class="btn-icon btn btn-sm btn-success"><span class="btn-text"><i class="fas fa-check"></i> Approve</span></button>
              <button onclick="rejectWaiver(<?=$w['waiver_id']?>)" class="btn-icon btn btn-sm btn-danger"><span class="btn-text"><i class="fas fa-xmark"></i> Reject</span></button>
            </div>
            <?php elseif($w['status']==='Pending'): ?>
            <span style="font-size:1.2rem;color:var(--text-muted);">Awaiting manager</span>
            <?php else: ?>
            <span class="adm-badge adm-badge-<?=$sc?>"><?=$w['status']?></span>
            <?php endif;?>
          </td>
        </tr>
      <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>
</div>
</div><!-- /sec-waivers -->

<!-- New Waiver Modal -->
<div class="adm-modal" id="modalNewWaiver">
  <div class="adm-modal-content" style="max-width:620px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-percent" style="color:var(--role-accent);"></i> New Waiver Request</h3>
      <button class="btn btn-primary adm-modal-close" onclick="closeModal('modalNewWaiver')"><span class="btn-text"><i class="fas fa-xmark"></i></span></button>
    </div>
    <div class="adm-modal-body">
      <form id="formNewWaiver">
        <div class="adm-form-group">
          <label>Invoice *</label>
          <select name="invoice_id" class="adm-search-input" required onchange="loadWaiverInvoiceInfo(this)">
            <option value="">— Select Invoice —</option>
            <?php foreach($inv_for_pay as $inv): ?>
            <option value="<?=$inv['invoice_id']?>" data-balance="<?=$inv['balance_due']?>" data-patient="<?=htmlspecialchars($inv['patient_name'])?>">
              <?=htmlspecialchars($inv['invoice_number'])?> — <?=htmlspecialchars($inv['patient_name'])?>
            </option>
            <?php endforeach;?>
          </select>
          <div id="waiverInvBox" style="display:none;padding:.8rem 1rem;background:var(--surface-2);border-radius:8px;font-size:1.2rem;margin-top:.5rem;border:1px solid var(--border);"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Waiver Type *</label>
            <select name="waiver_type" class="adm-search-input" required>
              <?php foreach(['Full','Partial','Student Discount','Staff Discount','Indigent','Hardship','Other'] as $t): ?><option><?=$t?></option><?php endforeach;?>
            </select>
          </div>
          <div class="adm-form-group">
            <label>Waived Amount (GHS) *</label>
            <input type="number" name="waived_amount" id="waivedAmtInput" class="adm-search-input" step="0.01" min="0.01" required oninput="calcWaiverRemaining()">
          </div>
        </div>
        <div id="waiverRemainingBox" style="background:var(--surface-2);border-radius:8px;padding:1rem;border:1px solid var(--border);font-size:1.3rem;margin-bottom:1.5rem;display:none;">
          Remaining: <strong id="waiverRemaining" style="color:var(--warning);">GHS 0.00</strong>
        </div>
        <div class="adm-form-group">
          <label>Reason *</label>
          <textarea name="reason" class="adm-search-input" rows="3" style="resize:vertical;" required placeholder="Detailed justification for the waiver..."></textarea>
        </div>
      </form>
    </div>
    <div class="adm-modal-footer">
      <button onclick="closeModal('modalNewWaiver')" class="btn btn-ghost"><span class="btn-text">Cancel</span></button>
      <button onclick="submitWaiverRequest()" class="btn btn-primary"><span class="btn-text"><i class="fas fa-paper-plane"></i> Submit for Approval</span></button>
    </div>
  </div>
</div>

<script>
let waiverInvBalance = 0;
function loadWaiverInvoiceInfo(sel){
  const opt=sel.options[sel.selectedIndex];
  waiverInvBalance=parseFloat(opt.dataset.balance||0);
  const box=document.getElementById('waiverInvBox');
  if(sel.value){ box.style.display='block'; box.innerHTML=`Patient: <strong>${opt.dataset.patient}</strong> &mdash; Balance Due: <strong style="color:var(--danger)">GHS ${waiverInvBalance.toFixed(2)}</strong>`; document.getElementById('waiverRemainingBox').style.display='block'; calcWaiverRemaining(); }
  else { box.style.display='none'; document.getElementById('waiverRemainingBox').style.display='none'; }
}
function calcWaiverRemaining(){
  const waived=parseFloat(document.getElementById('waivedAmtInput')?.value||0);
  const rem=Math.max(0,waiverInvBalance-waived);
  document.getElementById('waiverRemaining').textContent='GHS '+rem.toFixed(2);
  document.getElementById('waiverRemaining').style.color=rem<=0?'var(--success)':'var(--warning)';
}
async function submitWaiverRequest(){
  const f=document.getElementById('formNewWaiver');
  const d=await finAction({action:'create_waiver',invoice_id:f.querySelector('[name=invoice_id]').value,waiver_type:f.querySelector('[name=waiver_type]').value,waived_amount:f.querySelector('[name=waived_amount]').value,reason:f.querySelector('[name=reason]').value});
  if(d.success){ toast('Waiver submitted for approval.','success'); closeModal('modalNewWaiver'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
async function approveWaiver(id){
  if(!confirm('Approve this waiver? Invoice will be updated.')) return;
  const d=await finAction({action:'approve_waiver',waiver_id:id});
  if(d.success){ toast('Waiver approved! Invoice updated.','success'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
async function rejectWaiver(id){
  const reason=prompt('Reason for rejection:');
  if(reason===null) return;
  const d=await finAction({action:'reject_waiver',waiver_id:id,reason});
  if(d.success){ toast('Waiver rejected.','info'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
</script>
