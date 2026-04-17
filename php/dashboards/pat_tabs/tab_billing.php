<?php
/**
 * TAB: BILLING — Patient Portal (Redesigned v2)
 * Uses the established finance system backend (billing_invoices, payments, paystack_helper).
 * Does NOT modify any finance logic — only the presentation layer.
 */
if (!isset($conn)) { require_once '../../db_conn.php'; }
$pat_id = (int)($pat_row['id'] ?? 0);

// Fetch all invoices
$all_invoices = [];
$aq = mysqli_query($conn, "SELECT * FROM billing_invoices WHERE patient_id=$pat_id ORDER BY invoice_date DESC");
if ($aq) while ($r = mysqli_fetch_assoc($aq)) $all_invoices[] = $r;

// Stats
$total_outstanding = 0; $total_paid = 0; $overdue_count = 0; $paid_count = 0;
foreach ($all_invoices as $inv) {
    if ($inv['status'] === 'Paid') { $total_paid += (float)$inv['total_amount']; $paid_count++; }
    elseif (!in_array($inv['status'], ['Cancelled','Void'])) {
        $total_outstanding += (float)$inv['balance_due'];
        if (strtotime($inv['due_date']) < time() && $inv['status'] !== 'Paid') $overdue_count++;
    }
}
?>

<div id="sec-billing" class="dash-section">

<style>
.bill-stat-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;}
.bill-stat{border-radius:var(--radius-md);padding:1.5rem;border:1px solid var(--border);display:flex;align-items:center;gap:1rem;transition:var(--transition);}
.bill-stat:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);}
.bill-stat-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;flex-shrink:0;}
.bill-stat-num{font-size:2rem;font-weight:800;line-height:1.1;}
.bill-stat-lbl{font-size:1.1rem;color:var(--text-muted);margin-top:.2rem;}

.inv-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:1rem;box-shadow:var(--shadow-sm);transition:var(--transition);overflow:hidden;}
.inv-card:hover{box-shadow:var(--shadow-md);}
.inv-card.inv-paid{border-left:5px solid var(--success);}
.inv-card.inv-pending,.inv-card.inv-partially{border-left:5px solid var(--warning);}
.inv-card.inv-overdue{border-left:5px solid var(--danger);}

.inv-card-header{display:flex;align-items:center;gap:1.2rem;padding:1.4rem 1.8rem;flex-wrap:wrap;}
.inv-num-badge{font-size:1rem;font-weight:700;background:var(--surface-2);border-radius:8px;padding:.5rem .8rem;color:var(--text-secondary);white-space:nowrap;font-family:monospace;}
.inv-amount-ring{display:flex;flex-direction:column;align-items:flex-end;flex-shrink:0;}

.pay-progress-bar{height:8px;border-radius:4px;background:var(--border);overflow:hidden;width:120px;margin-top:.4rem;}
.pay-progress-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--success),#27ae60);transition:width .5s;}
</style>

  <!-- Header -->
  <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
    <div>
      <h2 style="font-size:2.2rem;font-weight:800;margin-bottom:.4rem;"><i class="fas fa-receipt" style="color:var(--role-accent);"></i> Billing & Payments</h2>
      <p style="font-size:1.3rem;color:var(--text-muted);">Manage your medical bills and payment receipts.</p>
    </div>
  </div>

  <!-- Stats Strip -->
  <div class="bill-stat-strip">
    <div class="bill-stat" style="background:var(--danger-light);">
      <div class="bill-stat-icon" style="background:linear-gradient(135deg,var(--danger),#c0392b);">
        <i class="fas fa-file-invoice-dollar"></i>
      </div>
      <div>
        <div class="bill-stat-num" style="color:var(--danger);">GHS <?= number_format($total_outstanding, 2) ?></div>
        <div class="bill-stat-lbl">Outstanding</div>
      </div>
    </div>
    <div class="bill-stat" style="background:var(--success-light);">
      <div class="bill-stat-icon" style="background:linear-gradient(135deg,var(--success),#1e8449);">
        <i class="fas fa-check-circle"></i>
      </div>
      <div>
        <div class="bill-stat-num" style="color:var(--success);">GHS <?= number_format($total_paid, 2) ?></div>
        <div class="bill-stat-lbl">Total Paid</div>
      </div>
    </div>
    <div class="bill-stat" style="background:var(--warning-light);">
      <div class="bill-stat-icon" style="background:linear-gradient(135deg,var(--warning),#e67e22);">
        <i class="fas fa-clock"></i>
      </div>
      <div>
        <div class="bill-stat-num" style="color:var(--warning);"><?= $overdue_count ?></div>
        <div class="bill-stat-lbl">Overdue</div>
      </div>
    </div>
    <div class="bill-stat" style="background:var(--surface-2);">
      <div class="bill-stat-icon" style="background:linear-gradient(135deg,var(--primary),var(--secondary));">
        <i class="fas fa-list"></i>
      </div>
      <div>
        <div class="bill-stat-num" style="color:var(--primary);"><?= count($all_invoices) ?></div>
        <div class="bill-stat-lbl">Total Invoices</div>
      </div>
    </div>
  </div>

  <!-- Outstanding Alert -->
  <?php if ($total_outstanding > 0): ?>
  <div style="background:var(--warning-light);border-left:5px solid var(--warning);padding:1.2rem 1.5rem;border-radius:0 var(--radius-sm) var(--radius-sm) 0;margin-bottom:1.5rem;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
    <i class="fas fa-circle-exclamation" style="font-size:2rem;color:var(--warning);"></i>
    <div style="flex:1;">
      <strong style="font-size:1.35rem;color:var(--warning);">You have unpaid invoices.</strong>
      <p style="font-size:1.2rem;color:var(--text-secondary);margin-top:.2rem;">Settle outstanding bills to ensure uninterrupted care access.</p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filter Tabs -->
  <div class="filter-tabs" id="billFilters" style="margin-bottom:1.5rem;">
    <div class="ftab active" onclick="filterBilling2(this,'all')">All Invoices</div>
    <div class="ftab" onclick="filterBilling2(this,'unpaid')">Unpaid</div>
    <div class="ftab" onclick="filterBilling2(this,'paid')">Paid</div>
    <div class="ftab" onclick="filterBilling2(this,'overdue')">Overdue</div>
  </div>

  <!-- Invoice Cards -->
  <?php if (empty($all_invoices)): ?>
  <div class="adm-card" style="text-align:center;padding:5rem;">
    <i class="fas fa-receipt" style="font-size:3.5rem;opacity:.2;display:block;margin-bottom:1rem;"></i>
    <h3 style="color:var(--text-muted);">No Invoices Found</h3>
    <p style="color:var(--text-muted);font-size:1.3rem;margin-top:.5rem;">Your billing invoices will appear here.</p>
  </div>
  <?php else: foreach ($all_invoices as $inv):
    $is_overdue = (strtotime($inv['due_date']) < time() && !in_array($inv['status'], ['Paid','Cancelled','Void']));
    $paid_pct = $inv['total_amount'] > 0 ? round(((float)$inv['paid_amount'] / (float)$inv['total_amount']) * 100) : 0;
    $statusCls = $inv['status'] === 'Paid' ? 'inv-paid'
        : ($is_overdue ? 'inv-overdue'
        : ($inv['status'] === 'Partially Paid' ? 'inv-partially' : 'inv-pending'));
    $badgeCls = $inv['status'] === 'Paid' ? 'success' : ($is_overdue ? 'danger' : 'warning');
    $displayStatus = $is_overdue ? 'Overdue' : $inv['status'];
    $billCat = $inv['status'] === 'Paid' ? 'paid' : ($is_overdue ? 'overdue' : 'unpaid');
  ?>
  <div class="inv-card <?= $statusCls ?> bill-row" data-cat="<?= $billCat ?>">
    <div class="inv-card-header">
      <!-- Invoice Number -->
      <div>
        <div class="inv-num-badge"><i class="fas fa-hashtag"></i> <?= htmlspecialchars($inv['invoice_number']) ?></div>
        <div style="font-size:1.1rem;color:var(--text-muted);margin-top:.4rem;">
          <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($inv['invoice_date'])) ?>
          &nbsp;·&nbsp;
          Due: <?= date('d M Y', strtotime($inv['due_date'])) ?>
        </div>
      </div>
      <!-- Center: Status + breakdown -->
      <div style="flex:1;padding:0 1rem;">
        <div style="display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;margin-bottom:.5rem;">
          <span class="adm-badge adm-badge-<?= $badgeCls ?>" style="font-size:1.15rem;"><?= $displayStatus ?></span>
          <?php if ($inv['status'] === 'Partially Paid'): ?>
          <span style="font-size:1.1rem;color:var(--text-muted);">Paid <?= $paid_pct ?>%</span>
          <?php endif; ?>
        </div>
        <?php if ($inv['status'] !== 'Paid'): ?>
        <div class="pay-progress-bar">
          <div class="pay-progress-fill" style="width:<?= $paid_pct ?>%;"></div>
        </div>
        <?php endif; ?>
      </div>
      <!-- Right: Amounts + Actions -->
      <div class="inv-amount-ring">
        <div style="text-align:right;margin-bottom:.5rem;">
          <div style="font-size:1.1rem;color:var(--text-muted);">Total: <strong>GHS <?= number_format($inv['total_amount'], 2) ?></strong></div>
          <div style="font-size:1.5rem;font-weight:800;color:<?= $inv['balance_due'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
            <?= $inv['balance_due'] > 0 ? 'GHS ' . number_format($inv['balance_due'], 2) . ' due' : '<i class="fas fa-check-circle"></i> Fully Paid' ?>
          </div>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end;">
          <button onclick="viewInvoice2(<?= $inv['invoice_id'] ?>)" class="btn btn-primary btn-sm" title="View Details">
            <span class="btn-text"><i class="fas fa-eye"></i> View</span>
          </button>
          <button onclick="window.open('/RMU-Medical-Management-System/php/finance/print_invoice.php?id=<?= $inv['invoice_id'] ?>','_blank')" class="btn btn-sm" style="background:var(--surface-2);border:1px solid var(--border);" title="Download/Print">
            <span class="btn-text"><i class="fas fa-download"></i></span>
          </button>
          <?php if ((float)$inv['balance_due'] > 0): ?>
          <button onclick="initializePayment(<?= $inv['invoice_id'] ?>, <?= $inv['balance_due'] ?>)" class="btn btn-sm" style="background:var(--success);color:#fff;border:none;" title="Pay Now">
            <span class="btn-text"><i class="fas fa-credit-card"></i> Pay Now</span>
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; endif; ?>

  <!-- Modal: Invoice Details -->
  <div class="modal-bg" id="modalViewInvoice2">
    <div class="modal-box" style="max-width:820px;">
      <div class="modal-header">
        <h3><i class="fas fa-file-invoice-dollar" style="color:var(--role-accent);margin-right:.5rem;"></i>Invoice Details</h3>
        <button class="btn btn-primary modal-close" onclick="closeModal('modalViewInvoice2')"><span class="btn-text">&times;</span></button>
      </div>
      <div id="invoiceDetailContent2" style="min-height:200px;">
        <div style="text-align:center;padding:3rem;color:var(--text-muted);">
          <i class="fas fa-spinner fa-spin fa-2x display:block;"></i>
        </div>
      </div>
      <div style="margin-top:2rem;display:flex;gap:.8rem;justify-content:flex-end;flex-wrap:wrap;">
        <button id="modalInvPrintBtn2" class="btn-icon btn btn-primary btn-sm"><span class="btn-text"><i class="fas fa-print"></i> Print / Download</span></button>
        <button onclick="closeModal('modalViewInvoice2')" class="btn btn-ghost btn-sm"><span class="btn-text">Close</span></button>
      </div>
    </div>
  </div>

  <!-- Include Paystack Inline JS -->
  <script src="https://js.paystack.co/v1/inline.js"></script>
</div>

<script>
function filterBilling2(el, target){
  document.querySelectorAll('#billFilters .ftab').forEach(f => f.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.bill-row').forEach(r => {
    const cat = r.dataset.cat;
    if (target === 'all') r.style.display = '';
    else r.style.display = cat === target ? '' : 'none';
  });
}

function viewInvoice2(id){
  const content = document.getElementById('invoiceDetailContent2');
  content.innerHTML = '<div style="text-align:center;padding:3rem;"><i class="fas fa-spinner fa-spin" style="font-size:2.5rem;color:var(--role-accent);display:block;margin-bottom:.8rem;"></i><p style="color:var(--text-muted);">Loading invoice details...</p></div>';
  openModal('modalViewInvoice2');
  document.getElementById('modalInvPrintBtn2').onclick = () => window.open('/RMU-Medical-Management-System/php/finance/print_invoice.php?id='+id, '_blank');
  patAction({action:'get_invoice_detail', invoice_id:id}).then(d => {
    if (d.success) content.innerHTML = d.html;
    else content.innerHTML = `<p style="color:var(--danger);padding:1.5rem;text-align:center;">${d.message||'Failed to load invoice details.'}</p>`;
  });
}

function verifyPaystackPayment(reference, invId){
  patAction({action:'verify_payment', reference, invoice_id:invId}).then(ver => {
    if (ver.success) {
      toast('Payment verified successfully! Receipt: ' + (ver.receipt||''), 'success');
      setTimeout(() => location.reload(), 2500);
    } else {
      toast('Verification failed: ' + (ver.message||''), 'danger');
    }
  });
}

async function initializePayment(invId, amount){
  toast('Preparing secure checkout...', 'info');
  const d = await patAction({action:'initialize_payment', invoice_id:invId, amount});
  if (!d.success){ toast(d.message||'System error', 'danger'); return; }
  const handler = PaystackPop.setup({
    key: d.key,
    email: d.email,
    amount: d.amount_pesewas,
    currency: 'GHS',
    ref: d.reference,
    metadata: { custom_fields: [{ display_name:'Invoice', variable_name:'invoice_num', value:d.invoice_number }] },
    callback: function(response){
      toast('Payment successful! Verifying with server...', 'success');
      verifyPaystackPayment(response.reference, invId);
    },
    onClose: function(){ toast('Payment window closed.', 'warning'); }
  });
  handler.openIframe();
}
</script>
