<?php
/**
 * TAB: BILLING — Patient Portal
 * Displays invoices, payments, and Paystack checkout.
 */
if(!isset($conn)) { require_once '../../db_conn.php'; }
$pat_id = (int)($pat_row['id'] ?? 0);

// Fetch unpaid invoices
$unpaid_invoices = [];
$uq = mysqli_query($conn, "SELECT * FROM billing_invoices WHERE patient_id=$pat_id AND status IN ('Pending', 'Partially Paid', 'Overdue') ORDER BY invoice_date DESC");
if($uq) while($r=mysqli_fetch_assoc($uq)) $unpaid_invoices[]=$r;

// Fetch all invoices
$all_invoices = [];
$aq = mysqli_query($conn, "SELECT * FROM billing_invoices WHERE patient_id=$pat_id ORDER BY invoice_date DESC");
if($aq) while($r=mysqli_fetch_assoc($aq)) $all_invoices[]=$r;

// Metrics
$total_outstanding = (float)mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(balance_due) FROM billing_invoices WHERE patient_id=$pat_id AND status NOT IN ('Paid','Cancelled','Void')"))[0];
?>

<div id="sec-billing" class="dash-section">
    <div class="dash-header" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:2rem;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; margin-bottom:0.5rem;">Billing & Payments</h2>
            <p style="color:var(--text-secondary); font-size:1.3rem;">Manage your medical bills, insurance claims, and payment receipts.</p>
        </div>
        <div style="text-align:right;">
            <div style="font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Total Outstanding</div>
            <div style="font-size:2.4rem; font-weight:800; color:var(--danger);">GHS <?=number_format($total_outstanding, 2)?></div>
        </div>
    </div>

    <!-- Alert for unpaid bills -->
    <?php if(!empty($unpaid_invoices)): ?>
    <div style="background:var(--warning-light); border-left:5px solid var(--warning); padding:1.5rem; border-radius:8px; margin-bottom:2rem; display:flex; align-items:center; gap:1.5rem;">
        <i class="fas fa-circle-exclamation" style="font-size:2rem; color:var(--warning);"></i>
        <div style="flex:1;">
            <h4 style="font-size:1.4rem; font-weight:700; color:var(--warning); margin-bottom:0.2rem;">Pending Payments</h4>
            <p style="font-size:1.2rem; color:var(--text-secondary);">You have <?=count($unpaid_invoices)?> invoice(s) awaiting payment. Please settle them to ensure uninterrupted service.</p>
        </div>
        <button onclick="document.getElementById('unpaidList').scrollIntoView({behavior:'smooth'})" class="btn-icon btn btn-warning"><span class="btn-text">View Bills</span></button>
    </div>
    <?php endif; ?>

    <div class="filter-tabs">
        <div class="ftab active" data-target="all" onclick="filterBilling(this, 'all')">All Invoices</div>
        <div class="ftab" data-target="pending" onclick="filterBilling(this, 'pending')">Unpaid</div>
        <div class="ftab" data-target="payments" onclick="filterBilling(this, 'history')">Payment History</div>
    </div>

    <!-- Invoices List -->
    <div class="adm-card" style="margin-bottom:2rem;" id="unpaidList">
        <div class="adm-card-header"><h3><i class="fas fa-receipt"></i> Invoice List</h3></div>
        <div class="adm-table-wrap">
            <table class="adm-table" id="tableInvoices">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Balance Due</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($all_invoices)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">No invoices found.</td></tr>
                    <?php else: foreach($all_invoices as $inv): 
                        $is_overdue = (strtotime($inv['due_date']) < time() && $inv['status'] !== 'Paid');
                        $badge = $inv['status'] === 'Paid' ? 'success' : ($is_overdue ? 'danger' : 'warning');
                    ?>
                        <tr class="billing-row" data-status="<?=strtolower($inv['status'])?>">
                            <td><strong><?=htmlspecialchars($inv['invoice_number'])?></strong></td>
                            <td style="font-size:1.1rem; color:var(--text-muted);"><?=date('d M Y', strtotime($inv['invoice_date']))?></td>
                            <td style="font-weight:700;">GHS <?=number_format($inv['total_amount'], 2)?></td>
                            <td><span class="adm-badge adm-badge-<?=$badge?>"><?=$is_overdue ? 'Overdue' : $inv['status']?></span></td>
                            <td style="font-weight:700; color:<?=$inv['balance_due']>0?'var(--danger)':'var(--success)'?>">GHS <?=number_format($inv['balance_due'], 2)?></td>
                            <td>
                                <div style="display:flex; gap:0.6rem;">
                                    <button onclick="viewInvoice(<?=$inv['invoice_id']?>)" class="btn btn-sm btn-ghost" title="View Detail"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
                                    <button onclick="window.open('/RMU-Medical-Management-System/php/finance/print_invoice.php?id=<?=$inv['invoice_id']?>','_blank')" class="btn-icon btn btn-sm btn-ghost" title="Print/Download"><span class="btn-text"><i class="fas fa-download"></i></span></button>
                                    <?php if($inv['balance_due'] > 0): ?>
                                    <button onclick="initializePayment(<?=$inv['invoice_id']?>, <?=$inv['balance_due']?>)" class="btn btn-sm btn-primary" style="background:#1a9e6e; border-color:#1a1a1a;"><span class="btn-text">Pay Now</span></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Invoice Details -->
    <div class="modal-bg" id="modalViewInvoice">
        <div class="modal-box" style="max-width:800px;">
            <div class="modal-header">
                <h3>Invoice Details</h3>
                <button class="btn btn-primary modal-close" onclick="closeModal('modalViewInvoice')"><span class="btn-text">&times;</span></button>
            </div>
            <div id="invoiceDetailContent" style="min-height:200px;">
                <!-- Loaded via AJAX -->
            </div>
            <div style="margin-top:2rem; text-align:right;">
                <button onclick="closeModal('modalViewInvoice')" class="btn btn-ghost"><span class="btn-text">Close</span></button>
                <button id="modalPrintBtn" class="btn-icon btn btn-primary"><span class="btn-text"><i class="fas fa-print"></i> Print</span></button>
            </div>
        </div>
    </div>

    <!-- Include Paystack Inline JS -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
</div>

<script>
function filterBilling(el, target){
    document.querySelectorAll('.ftab').forEach(f=>f.classList.remove('active'));
    el.classList.add('active');
    const rows = document.querySelectorAll('.billing-row');
    rows.forEach(r => {
        if(target === 'all') r.style.display = '';
        else if(target === 'pending') r.style.display = (r.dataset.status === 'pending' || r.dataset.status === 'partially paid' || r.dataset.status === 'overdue') ? '' : 'none';
        else r.style.display = (r.dataset.status === 'paid') ? '' : 'none';
    });
}

function viewInvoice(id){
    const content = document.getElementById('invoiceDetailContent');
    content.innerHTML = '<div style="text-align:center;padding:3rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--role-accent);"></i><p>Loading invoice details...</p></div>';
    openModal('modalViewInvoice');
    document.getElementById('modalPrintBtn').onclick = () => window.open('/RMU-Medical-Management-System/php/finance/print_invoice.php?id='+id, '_blank');
    
    patAction({action: 'get_invoice_detail', invoice_id: id}).then(d => {
        if(d.success) content.innerHTML = d.html;
        else content.innerHTML = '<p class="text-danger">'+(d.message||'Failed to load')+'</p>';
    });
}

function verifyPaystackPayment(reference, invId) {
    patAction({
        action: 'verify_payment',
        reference: reference,
        invoice_id: invId
    }).then(ver => {
        if(ver.success){
            toast('Transaction verified successfully!', 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            toast('Verification failed: ' + ver.message, 'danger');
        }
    });
}

async function initializePayment(invId, amount){
    toast('Preparing secure checkout...', 'info');
    const d = await patAction({action: 'initialize_payment', invoice_id: invId, amount: amount});
    if(!d.success){ toast(d.message||'System error', 'danger'); return; }

    const handler = PaystackPop.setup({
        key: d.key,
        email: d.email,
        amount: d.amount_pesewas,
        currency: 'GHS',
        ref: d.reference,
        metadata: {
            custom_fields: [
                { display_name: "Invoice Num", variable_name: "invoice_num", value: d.invoice_number }
            ]
        },
        callback: function(response){
            toast('Payment successful! Verifying...', 'success');
            // Verify server-side via AJAX
            verifyPaystackPayment(response.reference, invId);
        },
        onClose: function(){
            toast('Payment window closed.', 'warning');
        }
    });
    handler.openIframe();
}
</script>
