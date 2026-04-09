<?php
// TAB: INVOICES — Module 2
// Fetch invoices list
$inv_list = [];
$q = mysqli_query($conn,
  "SELECT bi.*, u.name AS patient_name, u2.name AS generated_by_name
   FROM billing_invoices bi
   JOIN patients pt ON bi.patient_id = pt.id
   JOIN users u ON pt.user_id = u.id
   LEFT JOIN users u2 ON bi.generated_by = u2.id
   ORDER BY bi.created_at DESC LIMIT 100");
if ($q) while ($r = mysqli_fetch_assoc($q)) $inv_list[] = $r;

// Fetch patients for dropdown
$patient_list = [];
$pq = mysqli_query($conn, "SELECT p.id, p.patient_id, u.name FROM patients p JOIN users u ON p.user_id=u.id WHERE u.is_active=1 ORDER BY u.name");
if ($pq) while ($r = mysqli_fetch_assoc($pq)) $patient_list[] = $r;

// Fetch fee schedule for line items
$fee_list = [];
$fq = mysqli_query($conn, "SELECT fee_id,service_name,service_code,base_amount,tax_rate,is_taxable FROM fee_schedule WHERE is_active=1 ORDER BY service_name");
if ($fq) while ($r = mysqli_fetch_assoc($fq)) $fee_list[] = $r;
?>
<div id="sec-invoices" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-file-invoice-dollar" style="color:var(--role-accent);"></i> Invoice Management</h1>
    <p>Create, view, and manage all patient billing invoices</p>
  </div>
  <div style="display:flex;gap:1rem;flex-wrap:wrap;">
    <button onclick="openModal('modalCreateInvoice')" class="adm-btn adm-btn-primary"><i class="fas fa-file-plus"></i> Create Invoice</button>
    <button onclick="exportInvoices()" class="adm-btn adm-btn-ghost"><i class="fas fa-file-export"></i> Export</button>
  </div>
</div>

<!-- Summary Strip -->
<div class="adm-summary-strip">
  <?php
  $statuses = ['Paid'=>'success','Pending'=>'warning','Overdue'=>'danger','Partially Paid'=>'orange'];
  foreach($statuses as $st => $cls):
    $cnt = (int)fval($conn,"SELECT COUNT(*) FROM billing_invoices WHERE status='$st'");
  ?>
  <div class="adm-mini-card">
    <div class="adm-mini-card-num <?=$cls?>"><?=$cnt?></div>
    <div class="adm-mini-card-label"><?=$st?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter Row -->
<div class="fin-filter-row">
  <div class="adm-search-wrap" style="min-width:220px;flex:2;">
    <i class="fas fa-search"></i>
    <input type="text" id="invSearch" class="adm-search-input" placeholder="Search invoice number, patient..." oninput="filterTable('invSearch','invoiceTable')">
  </div>
  <select id="invStatusFilter" onchange="applyInvFilters()">
    <option value="">All Statuses</option>
    <?php foreach(['Draft','Pending','Partially Paid','Paid','Overdue','Cancelled','Void','Written Off'] as $s): ?>
    <option value="<?=$s?>"><?=$s?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" id="invDateFrom" onchange="applyInvFilters()" placeholder="From">
  <input type="date" id="invDateTo" onchange="applyInvFilters()" placeholder="To">
  <button onclick="clearInvFilters()" class="adm-btn adm-btn-ghost adm-btn-sm"><i class="fas fa-xmark"></i> Clear</button>
</div>

<!-- Invoice Table -->
<div class="adm-card">
  <div class="adm-table-wrap">
    <table class="adm-table" id="invoiceTable">
      <thead><tr>
        <th><input type="checkbox" id="checkAll" onclick="toggleAll(this,'invCheck')"></th>
        <th>Invoice #</th><th>Patient</th><th>Date</th><th>Due Date</th>
        <th>Total (GHS)</th><th>Paid (GHS)</th><th>Balance (GHS)</th>
        <th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($inv_list)): ?>
        <tr><td colspan="10" style="text-align:center;padding:4rem;color:var(--text-muted);">
          <i class="fas fa-file-invoice" style="font-size:3rem;opacity:.3;display:block;margin-bottom:1rem;"></i>
          No invoices yet. <a href="#" onclick="openModal('modalCreateInvoice')" style="color:var(--role-accent);">Create the first invoice</a>
        </td></tr>
      <?php else: foreach($inv_list as $inv):
        $badge_map=[
          'Draft'=>'badge-draft','Pending'=>'badge-pending','Partially Paid'=>'badge-partial',
          'Paid'=>'badge-paid','Overdue'=>'badge-overdue-fin','Cancelled'=>'badge-cancelled',
          'Void'=>'badge-void','Written Off'=>'badge-void'
        ];
        $badge=$badge_map[$inv['status']]??'badge-pending';
        $tr_class = ($inv['status']==='Overdue') ? 'row-overdue' : (($inv['status']==='Paid') ? 'row-completed' : '');
      ?>
        <tr class="<?=$tr_class?>" data-status="<?=$inv['status']?>" data-date="<?=$inv['invoice_date']?>">
          <td><input type="checkbox" class="invCheck" value="<?=$inv['invoice_id']?>"></td>
          <td><strong><?=htmlspecialchars($inv['invoice_number'])?></strong></td>
          <td><?=htmlspecialchars($inv['patient_name']??'—')?></td>
          <td><?=date('d M Y',strtotime($inv['invoice_date']))?></td>
          <td><?=$inv['due_date']?date('d M Y',strtotime($inv['due_date'])):' —'?></td>
          <td><strong><?=number_format($inv['total_amount'],2)?></strong></td>
          <td style="color:var(--success);"><?=number_format($inv['paid_amount'],2)?></td>
          <td style="color:<?=$inv['balance_due']>0?'var(--danger)':'var(--success)';?>"><strong><?=number_format($inv['balance_due'],2)?></strong></td>
          <td><span class="badge-fin <?=$badge?>"><?=htmlspecialchars($inv['status'])?></span></td>
          <td>
            <div class="adm-table-actions">
              <button onclick="viewInvoiceDetail(<?=$inv['invoice_id']?>)" class="adm-btn adm-btn-sm adm-btn-ghost" title="View"><i class="fas fa-eye"></i></button>
              <?php if(in_array($inv['status'],['Pending','Partially Paid','Overdue'])): ?>
              <button onclick="openRecordPaymentFor(<?=$inv['invoice_id']?>,'<?=htmlspecialchars($inv['invoice_number'])?>',<?=$inv['balance_due']?>)" class="adm-btn adm-btn-sm adm-btn-success" title="Record Payment"><i class="fas fa-money-bill"></i></button>
              <?php endif; ?>
              <?php if($inv['status']==='Draft'): ?>
              <button onclick="issueInvoice(<?=$inv['invoice_id']?>)" class="adm-btn adm-btn-sm adm-btn-primary" title="Issue Invoice"><i class="fas fa-paper-plane"></i></button>
              <?php endif; ?>
              <button onclick="printInvoice(<?=$inv['invoice_id']?>)" class="adm-btn adm-btn-sm" style="background:var(--primary-light);color:var(--primary);" title="Print"><i class="fas fa-print"></i></button>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <!-- Bulk Actions -->
  <div style="padding:1.2rem 2rem;border-top:1px solid var(--border);display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
    <span style="font-size:1.2rem;color:var(--text-muted);">Bulk Actions:</span>
    <button onclick="bulkIssue()" class="adm-btn adm-btn-sm adm-btn-primary"><i class="fas fa-paper-plane"></i> Issue Selected</button>
    <button onclick="bulkReminder()" class="adm-btn adm-btn-sm adm-btn-warning"><i class="fas fa-bell"></i> Send Reminders</button>
    <button onclick="exportSelected()" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fas fa-download"></i> Export Selected</button>
  </div>
</div>
</div><!-- /sec-invoices -->

<!-- ══ CREATE INVOICE MODAL ═══════════════════════════════ -->
<div class="adm-modal" id="modalCreateInvoice">
  <div class="adm-modal-content" style="max-width:900px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-file-plus" style="color:var(--role-accent);"></i> Create New Invoice</h3>
      <button class="adm-modal-close" onclick="closeModal('modalCreateInvoice')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="adm-modal-body">
      <form id="formCreateInvoice">
        <!-- Patient -->
        <div class="adm-form-group">
          <label><i class="fas fa-user"></i> Patient *</label>
          <select id="invPatientId" name="patient_id" class="adm-search-input" required onchange="loadPatientInfo(this.value)">
            <option value="">— Select Patient —</option>
            <?php foreach($patient_list as $p): ?>
            <option value="<?=$p['id']?>" data-ref="<?=htmlspecialchars($p['patient_id'])?>">
              <?=htmlspecialchars($p['name'])?> (<?=htmlspecialchars($p['patient_id'])?>)
            </option>
            <?php endforeach; ?>
          </select>
          <div id="patientInfoBox" style="margin-top:.8rem;padding:1rem;background:var(--surface-2);border-radius:10px;border:1px solid var(--border);display:none;font-size:1.2rem;">
            <div id="patientInfoContent"></div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="adm-form-group">
            <label><i class="fas fa-calendar"></i> Invoice Date *</label>
            <input type="date" name="invoice_date" class="adm-search-input" value="<?=date('Y-m-d')?>" required>
          </div>
          <div class="adm-form-group">
            <label><i class="fas fa-calendar-day"></i> Due Date</label>
            <input type="date" name="due_date" class="adm-search-input" value="<?=date('Y-m-d',strtotime('+30 days'))?>">
          </div>
        </div>

        <!-- Line Items -->
        <div class="adm-form-group">
          <label><i class="fas fa-list"></i> Service Line Items</label>
          <div id="lineItemsContainer">
            <!-- Initial row added by JS -->
          </div>
          <button type="button" onclick="addLineItem()" class="adm-btn adm-btn-ghost adm-btn-sm" style="margin-top:.8rem;"><i class="fas fa-plus"></i> Add Service</button>
        </div>

        <!-- Invoice Summary -->
        <div class="inv-summary">
          <div class="inv-summary-row"><span>Subtotal</span><span id="sumSubtotal">GHS 0.00</span></div>
          <div class="inv-summary-row"><span>Discount</span><span id="sumDiscount" style="color:var(--success);">—</span></div>
          <div class="inv-summary-row"><span>Tax</span><span id="sumTax">GHS 0.00</span></div>
          <div class="inv-summary-row total"><span>Total Amount</span><span id="sumTotal">GHS 0.00</span></div>
        </div>

        <div class="adm-form-group" style="margin-top:1.5rem;">
          <label><i class="fas fa-note-sticky"></i> Notes</label>
          <textarea name="notes" class="adm-search-input" rows="2" style="resize:vertical;" placeholder="Optional invoice notes..."></textarea>
        </div>
      </form>
    </div>
    <div class="adm-modal-footer">
      <button onclick="closeModal('modalCreateInvoice')" class="adm-btn adm-btn-ghost">Cancel</button>
      <button onclick="saveInvoice('draft')" class="adm-btn" style="background:var(--surface-2);border:1px solid var(--border);color:var(--text-primary);">
        <i class="fas fa-floppy-disk"></i> Save Draft
      </button>
      <button onclick="saveInvoice('issue')" class="adm-btn adm-btn-primary">
        <i class="fas fa-paper-plane"></i> Issue Invoice
      </button>
    </div>
  </div>
</div><!-- /modal create invoice -->

<!-- ══ INVOICE DETAIL MODAL ═══════════════════════════════ -->
<div class="adm-modal" id="modalInvoiceDetail">
  <div class="adm-modal-content" style="max-width:820px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-file-invoice-dollar" style="color:var(--role-accent);"></i> Invoice Detail</h3>
      <button class="adm-modal-close" onclick="closeModal('modalInvoiceDetail')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="adm-modal-body" id="invoiceDetailContent" style="min-height:200px;">
      <div style="text-align:center;padding:4rem;color:var(--text-muted);"><i class="fas fa-spinner fa-spin" style="font-size:2rem;"></i></div>
    </div>
    <div class="adm-modal-footer">
      <button onclick="closeModal('modalInvoiceDetail')" class="adm-btn adm-btn-ghost">Close</button>
      <button onclick="printCurrentInvoice()" class="adm-btn adm-btn-ghost"><i class="fas fa-print"></i> Print</button>
      <button onclick="downloadInvoicePDF()" class="adm-btn adm-btn-primary"><i class="fas fa-file-pdf"></i> Download PDF</button>
    </div>
  </div>
</div>

<script>
const feeSchedule = <?=json_encode($fee_list)?>;
let lineItemCount = 0;

function addLineItem(data={}) {
  lineItemCount++;
  const idx = lineItemCount;
  const feeOpts = feeSchedule.map(f =>
    `<option value="${f.fee_id}" data-price="${f.base_amount}" data-tax="${f.tax_rate}" data-taxable="${f.is_taxable}">${f.service_name} (GHS ${parseFloat(f.base_amount).toFixed(2)})</option>`
  ).join('');

  const row = document.createElement('div');
  row.className = 'line-item-row';
  row.id = `lineItem_${idx}`;
  row.innerHTML = `
    <select onchange="onFeeSelect(this,${idx})" style="padding:.8rem;border:1.5px solid var(--border);border-radius:8px;background:var(--surface);color:var(--text-primary);font-size:1.2rem;width:100%;">
      <option value="">— Select Service —</option>${feeOpts}
    </select>
    <input type="number" id="qty_${idx}" value="1" min="1" oninput="calcLineTotal(${idx})" placeholder="Qty" style="padding:.8rem;border:1.5px solid var(--border);border-radius:8px;background:var(--surface);color:var(--text-primary);font-size:1.2rem;width:100%;">
    <input type="number" id="price_${idx}" value="${data.unit_price||0}" step="0.01" min="0" oninput="calcLineTotal(${idx})" placeholder="Unit Price" style="padding:.8rem;border:1.5px solid var(--border);border-radius:8px;background:var(--surface);color:var(--text-primary);font-size:1.2rem;width:100%;">
    <input type="number" id="disc_${idx}" value="${data.disc||0}" min="0" max="100" step="0.1" oninput="calcLineTotal(${idx})" placeholder="Disc %" style="padding:.8rem;border:1.5px solid var(--border);border-radius:8px;background:var(--surface);color:var(--text-primary);font-size:1.2rem;width:100%;">
    <div id="lineTotal_${idx}" style="font-weight:700;font-size:1.3rem;color:var(--role-accent);text-align:right;">GHS 0.00</div>
    <button type="button" onclick="removeLineItem(${idx},this)" style="width:34px;height:34px;border-radius:8px;background:var(--danger-light);color:var(--danger);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.3rem;" title="Remove"><i class="fas fa-xmark"></i></button>
    <input type="hidden" id="feeId_${idx}" name="line_items[${idx}][fee_id]">
    <input type="hidden" id="tax_${idx}" value="0">
    <input type="hidden" id="taxable_${idx}" value="0">
  `;
  document.getElementById('lineItemsContainer').appendChild(row);
  calcInvoiceTotal();
}

function onFeeSelect(sel, idx) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById(`price_${idx}`).value = opt.dataset.price || 0;
  document.getElementById(`tax_${idx}`).value = opt.dataset.tax || 0;
  document.getElementById(`taxable_${idx}`).value = opt.dataset.taxable || 0;
  document.getElementById(`feeId_${idx}`).value = sel.value;
  calcLineTotal(idx);
}

function calcLineTotal(idx) {
  const qty   = parseFloat(document.getElementById(`qty_${idx}`)?.value || 1);
  const price = parseFloat(document.getElementById(`price_${idx}`)?.value || 0);
  const disc  = parseFloat(document.getElementById(`disc_${idx}`)?.value || 0);
  const taxable = document.getElementById(`taxable_${idx}`)?.value === '1';
  const taxRate = parseFloat(document.getElementById(`tax_${idx}`)?.value || 0);
  const sub    = qty * price;
  const discAmt = sub * (disc / 100);
  const taxAmt  = taxable ? (sub - discAmt) * (taxRate / 100) : 0;
  const total   = sub - discAmt + taxAmt;
  const el = document.getElementById(`lineTotal_${idx}`);
  if (el) el.textContent = 'GHS ' + total.toFixed(2);
  calcInvoiceTotal();
}

function removeLineItem(idx, btn) {
  btn.closest('.line-item-row').remove();
  calcInvoiceTotal();
}

function calcInvoiceTotal() {
  let subtotal = 0, discount = 0, tax = 0;
  for (let i = 1; i <= lineItemCount; i++) {
    const el = document.getElementById(`lineTotal_${i}`);
    if (!el) continue;
    const qty   = parseFloat(document.getElementById(`qty_${i}`)?.value || 1);
    const price = parseFloat(document.getElementById(`price_${i}`)?.value || 0);
    const disc  = parseFloat(document.getElementById(`disc_${i}`)?.value || 0);
    const taxable = document.getElementById(`taxable_${i}`)?.value === '1';
    const taxRate = parseFloat(document.getElementById(`tax_${i}`)?.value || 0);
    const sub = qty * price;
    const discAmt = sub * (disc / 100);
    const taxAmt  = taxable ? (sub - discAmt) * (taxRate / 100) : 0;
    subtotal += sub; discount += discAmt; tax += taxAmt;
  }
  const total = subtotal - discount + tax;
  document.getElementById('sumSubtotal').textContent = 'GHS ' + subtotal.toFixed(2);
  document.getElementById('sumDiscount').textContent = discount > 0 ? '- GHS ' + discount.toFixed(2) : '—';
  document.getElementById('sumTax').textContent = 'GHS ' + tax.toFixed(2);
  document.getElementById('sumTotal').textContent = 'GHS ' + total.toFixed(2);
}

function loadPatientInfo(patId) {
  if (!patId) { document.getElementById('patientInfoBox').style.display='none'; return; }
  fetch('/RMU-Medical-Management-System/php/finance/finance_actions.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'get_patient_info', patient_id: patId})
  }).then(r=>r.json()).then(d=>{
    if(d.success){
      document.getElementById('patientInfoBox').style.display='block';
      document.getElementById('patientInfoContent').innerHTML =
        `<strong>${d.name}</strong> &bull; ID: ${d.patient_id} &bull; Insurance: ${d.insurance||'None'}
         <span style="margin-left:1rem;color:var(--danger);">Outstanding: GHS ${parseFloat(d.outstanding||0).toFixed(2)}</span>`;
    }
  }).catch(()=>{});
}

async function saveInvoice(mode) {
  const form   = document.getElementById('formCreateInvoice');
  const fd     = new FormData(form);
  const patId  = document.getElementById('invPatientId').value;
  if (!patId) { toast('Please select a patient.', 'danger'); return; }

  // Collect line items
  const lines = [];
  for (let i=1; i<=lineItemCount; i++) {
    if (!document.getElementById(`lineItem_${i}`)) continue;
    lines.push({
      fee_id: document.getElementById(`feeId_${i}`)?.value,
      qty:    document.getElementById(`qty_${i}`)?.value,
      price:  document.getElementById(`price_${i}`)?.value,
      disc:   document.getElementById(`disc_${i}`)?.value,
    });
  }
  if (!lines.length) { toast('Add at least one service.', 'danger'); return; }

  const data = {
    action: mode === 'issue' ? 'issue_invoice' : 'save_invoice_draft',
    patient_id:   patId,
    invoice_date: form.querySelector('[name=invoice_date]').value,
    due_date:     form.querySelector('[name=due_date]').value,
    notes:        form.querySelector('[name=notes]').value,
    line_items:   lines
  };

  const btn = document.querySelector('#modalCreateInvoice .adm-btn-primary');
  btn.classList.add('loading'); btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  const res = await finAction(data);
  btn.classList.remove('loading'); btn.innerHTML = '<i class="fas fa-paper-plane"></i> Issue Invoice';

  if (res.success) {
    toast(mode==='issue' ? 'Invoice issued & patient notified!' : 'Draft saved.', 'success');
    closeModal('modalCreateInvoice');
    setTimeout(()=>location.reload(), 1500);
  } else {
    toast(res.message || 'Error saving invoice.', 'danger');
  }
}

function viewInvoiceDetail(invId) {
  openModal('modalInvoiceDetail');
  document.getElementById('invoiceDetailContent').innerHTML = '<div style="text-align:center;padding:4rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--role-accent);"></i></div>';
  fetch('/RMU-Medical-Management-System/php/finance/finance_actions.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'get_invoice_detail', invoice_id: invId})
  }).then(r=>r.json()).then(d=>{
    if(d.html) document.getElementById('invoiceDetailContent').innerHTML = d.html;
    else document.getElementById('invoiceDetailContent').innerHTML = '<p style="color:var(--danger);text-align:center;">Failed to load invoice.</p>';
    window._currentInvoiceId = invId;
  });
}

function issueInvoice(invId) {
  if(!confirm('Issue this invoice? The patient will be notified.')) return;
  finAction({action:'issue_invoice_direct', invoice_id: invId}).then(d=>{
    if(d.success){ toast('Invoice issued!','success'); setTimeout(()=>location.reload(),1200); }
    else toast(d.message||'Error.','danger');
  });
}

function openRecordPaymentFor(invId, invNum, balance) {
  document.getElementById('rpInvoiceId').value = invId;
  document.getElementById('rpInvoiceNum').textContent = invNum;
  document.getElementById('rpMaxAmount').value = balance;
  document.getElementById('rpAmount').value = balance;
  openModal('modalRecordPayment');
}

function printInvoice(invId) {
  window.open(`/RMU-Medical-Management-System/php/finance/print_invoice.php?id=${invId}`, '_blank');
}
function printCurrentInvoice() { if(window._currentInvoiceId) printInvoice(window._currentInvoiceId); }
function downloadInvoicePDF()  { if(window._currentInvoiceId) window.open(`/RMU-Medical-Management-System/php/finance/download_invoice.php?id=${window._currentInvoiceId}`,'_blank'); }

function applyInvFilters() {
  const status = document.getElementById('invStatusFilter').value;
  const from   = document.getElementById('invDateFrom').value;
  const to     = document.getElementById('invDateTo').value;
  document.querySelectorAll('#invoiceTable tbody tr').forEach(r => {
    const rs = r.dataset.status || '';
    const rd = r.dataset.date || '';
    const statOk = !status || rs===status;
    const fromOk = !from || rd >= from;
    const toOk   = !to || rd <= to;
    r.style.display = (statOk && fromOk && toOk) ? '' : 'none';
  });
}
function clearInvFilters() {
  document.getElementById('invStatusFilter').value = '';
  document.getElementById('invDateFrom').value = '';
  document.getElementById('invDateTo').value = '';
  document.getElementById('invSearch').value = '';
  document.querySelectorAll('#invoiceTable tbody tr').forEach(r => r.style.display = '');
}
function toggleAll(master, cls) {
  document.querySelectorAll('.'+cls).forEach(cb => cb.checked = master.checked);
}
function getSelectedIds(cls) {
  return [...document.querySelectorAll('.'+cls+':checked')].map(cb => cb.value);
}
async function bulkIssue() {
  const ids = getSelectedIds('invCheck');
  if(!ids.length){ toast('No invoices selected.','warning'); return; }
  const d = await finAction({action:'bulk_issue_invoices', invoice_ids: ids});
  if(d.success){ toast(`${d.count} invoice(s) issued.`,'success'); setTimeout(()=>location.reload(),1200); }
  else toast(d.message||'Error.','danger');
}
async function bulkReminder() {
  const ids = getSelectedIds('invCheck');
  if(!ids.length){ toast('No invoices selected.','warning'); return; }
  const d = await finAction({action:'bulk_send_reminders', invoice_ids: ids});
  if(d.success) toast(`Reminders sent to ${d.count} patient(s).`,'success');
  else toast(d.message||'Error.','danger');
}
function exportSelected() {
  const ids = getSelectedIds('invCheck');
  window.open(`/RMU-Medical-Management-System/php/finance/export_invoices.php?ids=${ids.join(',')}&format=xlsx`,'_blank');
}
function exportInvoices() {
  window.open('/RMU-Medical-Management-System/php/finance/export_invoices.php?format=xlsx','_blank');
}

// Add first empty line item on modal open
document.getElementById('modalCreateInvoice').addEventListener('transitionend',()=>{});
document.querySelector('[onclick="openModal(\'modalCreateInvoice\')"]') && document.querySelectorAll('[onclick*="modalCreateInvoice"]').forEach(b=>{
  b.addEventListener('click',()=>{ lineItemCount=0; document.getElementById('lineItemsContainer').innerHTML=''; addLineItem(); calcInvoiceTotal(); });
});
</script>
