<?php
// TAB: INSURANCE CLAIMS — Module 4
$claim_list=[];
$q=mysqli_query($conn,
  "SELECT ic.*,bi.invoice_number,u.name AS patient_name, u2.name AS officer_name
   FROM insurance_claims ic
   JOIN billing_invoices bi ON ic.invoice_id=bi.invoice_id
   JOIN patients pt ON ic.patient_id=pt.id
   JOIN users u ON pt.user_id=u.id
   LEFT JOIN users u2 ON ic.claims_officer=u2.id
   ORDER BY ic.created_at DESC LIMIT 100");
if($q) while($r=mysqli_fetch_assoc($q)) $claim_list[]=$r;
?>
<div id="sec-insurance" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-shield-halved" style="color:var(--role-accent);"></i> Insurance Claims</h1>
    <p>Submit, track, and manage insurance claim submissions</p>
  </div>
  <div style="display:flex;gap:1rem;">
    <button onclick="openModal('modalNewClaim')" class="btn btn-primary"><span class="btn-text"><i class="fas fa-plus"></i> New Claim</span></button>
    <button onclick="exportClaims()" class="btn-icon btn btn-ghost"><span class="btn-text"><i class="fas fa-file-export"></i> Export</span></button>
  </div>
</div>

<div class="adm-summary-strip">
  <?php
  $cstats=[['Draft','info'],['Submitted','warning'],['Approved','success'],['Rejected','danger'],['Paid','success']];
  foreach($cstats as [$st,$cl]):
    $cnt=(int)fval($conn,"SELECT COUNT(*) FROM insurance_claims WHERE status='$st'");
  ?>
  <div class="adm-mini-card">
    <div class="adm-mini-card-num <?=$cl?>"><?=$cnt?></div>
    <div class="adm-mini-card-label"><?=$st?></div>
  </div>
  <?php endforeach;?>
</div>

<div class="fin-filter-row">
  <div class="adm-search-wrap" style="flex:2;min-width:200px;">
    <i class="fas fa-search"></i>
    <input type="text" id="claimSearch" class="adm-search-input" placeholder="Search claim #, patient, insurer..." oninput="filterTable('claimSearch','claimTable')">
  </div>
  <select id="claimStatusFilter" onchange="applyClaimFilters()">
    <option value="">All Statuses</option>
    <?php foreach(['Draft','Submitted','Under Review','Approved','Partially Approved','Rejected','Paid','Appealed'] as $s): ?>
    <option><?=$s?></option>
    <?php endforeach;?>
  </select>
  <button onclick="clearClaimFilters()" class="btn btn-ghost btn-sm"><span class="btn-text"><i class="fas fa-xmark"></i></span></button>
</div>

<div class="adm-card">
  <div class="adm-table-wrap">
    <table class="adm-table" id="claimTable">
      <thead><tr>
        <th>Claim #</th><th>Patient</th><th>Invoice #</th><th>Insurer</th>
        <th>Policy #</th><th>Claim Amt</th><th>Approved</th><th>Patient Pay</th>
        <th>Status</th><th>Submitted</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($claim_list)): ?>
        <tr><td colspan="11" style="text-align:center;padding:4rem;color:var(--text-muted);">
          <i class="fas fa-shield-halved" style="font-size:3rem;opacity:.3;display:block;margin-bottom:1rem;"></i>
          No insurance claims yet.
        </td></tr>
      <?php else: foreach($claim_list as $cl):
        $sc_map=['Draft'=>'info','Submitted'=>'warning','Under Review'=>'warning','Approved'=>'success','Partially Approved'=>'success','Rejected'=>'danger','Paid'=>'success','Appealed'=>''];
        $sc=$sc_map[$cl['status']]??'info';
      ?>
        <tr data-status="<?=$cl['status']?>">
          <td><strong><?=htmlspecialchars($cl['claim_number'])?></strong></td>
          <td><?=htmlspecialchars($cl['patient_name']??'—')?></td>
          <td><?=htmlspecialchars($cl['invoice_number']??'—')?></td>
          <td><?=htmlspecialchars($cl['insurance_provider'])?></td>
          <td><?=htmlspecialchars($cl['policy_number'])?></td>
          <td><strong>GHS <?=number_format($cl['claim_amount'],2)?></strong></td>
          <td style="color:var(--success);"><?=$cl['approved_amount']?'GHS '.number_format($cl['approved_amount'],2):'—'?></td>
          <td style="color:var(--warning);"><?=$cl['patient_copay']?'GHS '.number_format($cl['patient_copay'],2):'—'?></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=htmlspecialchars($cl['status'])?></span></td>
          <td><?=$cl['submission_date']?date('d M Y',strtotime($cl['submission_date'])):' —'?></td>
          <td>
            <div class="adm-table-actions">
              <button onclick="viewClaimDetail(<?=$cl['claim_id']?>)" class="btn btn-sm btn-ghost" title="View"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
              <button onclick="updateClaimStatus(<?=$cl['claim_id']?>,'<?=$cl['status']?>')" class="btn btn-sm btn-primary" title="Update Status"><span class="btn-text"><i class="fas fa-pen"></i></span></button>
            </div>
          </td>
        </tr>
      <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>
</div>
</div><!-- /sec-insurance -->

<!-- ══ NEW CLAIM MODAL ════ -->
<div class="adm-modal" id="modalNewClaim">
  <div class="adm-modal-content" style="max-width:700px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-shield-halved" style="color:var(--role-accent);"></i> New Insurance Claim</h3>
      <button class="btn btn-primary adm-modal-close" onclick="closeModal('modalNewClaim')"><span class="btn-text"><i class="fas fa-xmark"></i></span></button>
    </div>
    <div class="adm-modal-body">
      <form id="formNewClaim">
        <div class="adm-form-group">
          <label>Invoice *</label>
          <select name="invoice_id" class="adm-search-input" required onchange="loadClaimPatientInfo(this)">
            <option value="">— Select Invoice —</option>
            <?php foreach($inv_for_pay as $inv): ?>
            <option value="<?=$inv['invoice_id']?>" data-patient="<?=htmlspecialchars($inv['patient_name'])?>"
                    data-balance="<?=$inv['balance_due']?>">
              <?=htmlspecialchars($inv['invoice_number'])?> — <?=htmlspecialchars($inv['patient_name'])?>
            </option>
            <?php endforeach;?>
          </select>
        </div>
        <div id="claimPatientBox" style="display:none;padding:1rem;background:var(--surface-2);border-radius:10px;border:1px solid var(--border);font-size:1.2rem;margin-bottom:1.5rem;"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Insurance Provider *</label>
            <input type="text" name="insurance_provider" class="adm-search-input" required placeholder="e.g. NHIS, Enterprise">
          </div>
          <div class="adm-form-group">
            <label>Policy Number *</label>
            <input type="text" name="policy_number" class="adm-search-input" required placeholder="Member/Policy number">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Claim Amount (GHS) *</label>
            <input type="number" name="claim_amount" class="adm-search-input" step="0.01" min="0.01" required placeholder="0.00">
          </div>
          <div class="adm-form-group">
            <label>Insurer Reference</label>
            <input type="text" name="insurer_reference" class="adm-search-input" placeholder="Insurer claim/auth number">
          </div>
        </div>
        <div class="adm-form-group">
          <label>Notes</label>
          <textarea name="notes" class="adm-search-input" rows="2" style="resize:vertical;"></textarea>
        </div>
      </form>
    </div>
    <div class="adm-modal-footer">
      <button onclick="closeModal('modalNewClaim')" class="btn btn-ghost"><span class="btn-text">Cancel</span></button>
      <button onclick="saveDraftClaim()" class="btn btn-ghost"><span class="btn-text"><i class="fas fa-floppy-disk"></i> Save Draft</span></button>
      <button onclick="submitClaim()" class="btn btn-primary"><span class="btn-text"><i class="fas fa-paper-plane"></i> Submit Claim</span></button>
    </div>
  </div>
</div>

<!-- Update Status Modal -->
<div class="adm-modal" id="modalUpdateClaim">
  <div class="adm-modal-content" style="max-width:520px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-pen"></i> Update Claim Status</h3>
      <button class="btn btn-primary adm-modal-close" onclick="closeModal('modalUpdateClaim')"><span class="btn-text"><i class="fas fa-xmark"></i></span></button>
    </div>
    <div class="adm-modal-body">
      <input type="hidden" id="updateClaimId">
      <div class="adm-form-group">
        <label>New Status *</label>
        <select id="newClaimStatus" class="adm-search-input" onchange="onClaimStatusChange(this.value)">
          <?php foreach(['Submitted','Under Review','Approved','Partially Approved','Rejected','Paid','Appealed'] as $s): ?>
          <option><?=$s?></option>
          <?php endforeach;?>
        </select>
      </div>
      <div id="claimApprovalFields" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label>Approved Amount (GHS)</label>
            <input type="number" id="claimApprovedAmt" class="adm-search-input" step="0.01" min="0">
          </div>
          <div class="adm-form-group">
            <label>Patient Co-pay (GHS)</label>
            <input type="number" id="claimCopay" class="adm-search-input" step="0.01" min="0">
          </div>
        </div>
      </div>
      <div class="adm-form-group">
        <label>Response Date</label>
        <input type="date" id="claimResponseDate" class="adm-search-input" value="<?=date('Y-m-d')?>">
      </div>
      <div class="adm-form-group">
        <label>Notes / Rejection Reason</label>
        <textarea id="claimNotes" class="adm-search-input" rows="2" style="resize:vertical;"></textarea>
      </div>
    </div>
    <div class="adm-modal-footer">
      <button onclick="closeModal('modalUpdateClaim')" class="btn btn-ghost"><span class="btn-text">Cancel</span></button>
      <button onclick="applyClaimUpdate()" class="btn btn-primary"><span class="btn-text"><i class="fas fa-check"></i> Update Status</span></button>
    </div>
  </div>
</div>

<script>
function loadClaimPatientInfo(sel){
  const opt=sel.options[sel.selectedIndex];
  const box=document.getElementById('claimPatientBox');
  if(sel.value){ box.style.display='block'; box.innerHTML=`Patient: <strong>${opt.dataset.patient}</strong> &mdash; Balance Due: <strong style="color:var(--danger)">GHS ${parseFloat(opt.dataset.balance||0).toFixed(2)}</strong>`; }
  else box.style.display='none';
}
async function saveDraftClaim(){ await submitClaimForm('Draft'); }
async function submitClaim(){ await submitClaimForm('Submitted'); }
async function submitClaimForm(status){
  const f=document.getElementById('formNewClaim');
  const data={action:'save_insurance_claim',status,
    invoice_id:f.querySelector('[name=invoice_id]').value,
    insurance_provider:f.querySelector('[name=insurance_provider]').value,
    policy_number:f.querySelector('[name=policy_number]').value,
    claim_amount:f.querySelector('[name=claim_amount]').value,
    insurer_reference:f.querySelector('[name=insurer_reference]').value,
    notes:f.querySelector('[name=notes]').value};
  if(!data.invoice_id||!data.insurance_provider||!data.policy_number||!data.claim_amount){ toast('Fill required fields.','danger'); return; }
  const d=await finAction(data);
  if(d.success){ toast(status==='Submitted'?'Claim submitted!':'Draft saved.','success'); closeModal('modalNewClaim'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
function updateClaimStatus(id,current){
  document.getElementById('updateClaimId').value=id;
  document.getElementById('newClaimStatus').value=current;
  onClaimStatusChange(current);
  openModal('modalUpdateClaim');
}
function onClaimStatusChange(v){
  const fields=document.getElementById('claimApprovalFields');
  fields.style.display=(v==='Approved'||v==='Partially Approved')?'block':'none';
}
async function applyClaimUpdate(){
  const id=document.getElementById('updateClaimId').value;
  const status=document.getElementById('newClaimStatus').value;
  const d=await finAction({action:'update_claim_status',claim_id:id,status,
    approved_amount:document.getElementById('claimApprovedAmt').value||null,
    patient_copay:document.getElementById('claimCopay').value||null,
    response_date:document.getElementById('claimResponseDate').value,
    notes:document.getElementById('claimNotes').value});
  if(d.success){ toast('Claim updated!','success'); closeModal('modalUpdateClaim'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
function applyClaimFilters(){
  const status=document.getElementById('claimStatusFilter').value;
  document.querySelectorAll('#claimTable tbody tr').forEach(r=>{ r.style.display=(!status||r.dataset.status===status)?'':'none'; });
}
function clearClaimFilters(){ document.getElementById('claimStatusFilter').value=''; document.querySelectorAll('#claimTable tbody tr').forEach(r=>r.style.display=''); }
function viewClaimDetail(id){ toast('Loading claim details...','info'); }
function exportClaims(){ window.open('/RMU-Medical-Management-System/php/finance/export_claims.php?format=csv','_blank'); }
</script>
