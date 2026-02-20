<?php
$active_page = 'payment';
$page_title  = 'Add Payment';
include '../includes/_sidebar.php';
require_once '../db_conn.php';

// Check table
$tbl = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
$tbl_exists = $tbl && mysqli_num_rows($tbl) > 0;

// Fetch appointments for linking
$appts = [];
if ($tbl_exists) {
    $qa = mysqli_query($conn,
        "SELECT a.id, a.appointment_id, a.appointment_date, u.name AS patient_name, u2.name AS doctor_name
         FROM appointments a
         LEFT JOIN patients p ON a.patient_id = p.id
         LEFT JOIN users u ON p.user_id = u.id
         JOIN doctors d ON a.doctor_id = d.id
         JOIN users u2 ON d.user_id = u2.id
         ORDER BY a.appointment_date DESC LIMIT 100"
    );
    if ($qa) while ($r = mysqli_fetch_assoc($qa)) $appts[] = $r;
}
?>
<main class="adm-main">
<div class="adm-topbar">
    <div class="adm-topbar-left">
        <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <span class="adm-page-title"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:.8rem;"></i>Add Payment</span>
    </div>
    <div class="adm-topbar-right">
        <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
        <div class="adm-avatar"><i class="fas fa-user"></i></div>
    </div>
</div>
<div class="adm-content">
    <div class="adm-page-header">
        <div class="adm-page-header-left">
            <h1>Add Payment Record</h1>
            <p>Record a new payment or consultation fee.</p>
        </div>
        <a href="/RMU-Medical-Management-System/php/payment/payment.php" class="adm-btn adm-btn-back">
            <i class="fas fa-arrow-left"></i> Back to Payments
        </a>
    </div>

    <?php if (!$tbl_exists): ?>
    <div class="adm-alert adm-alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        The <code>payments</code> table does not exist. Please run the setup SQL from the Payments page first.
    </div>
    <?php else: ?>

    <div class="adm-card" style="max-width:640px;">
        <div class="adm-card-header">
            <h3><i class="fas fa-receipt"></i> Payment Details</h3>
        </div>
        <div class="adm-card-body">
            <div id="formMsg" style="display:none;" class="adm-alert"></div>
            <form id="payForm">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.35rem;">Patient Name <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="patient_name" class="adm-search-input" style="width:100%;box-sizing:border-box;" placeholder="Full name" required>
                    </div>
                    <div>
                        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.35rem;">Amount (GH₵) <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="amount" class="adm-search-input" style="width:100%;box-sizing:border-box;" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.35rem;">Payment Method</label>
                        <select name="method" class="adm-search-input" style="width:100%;box-sizing:border-box;">
                            <option>Cash</option>
                            <option>GhIPSS</option>
                            <option>Mobile Money</option>
                            <option>Card</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.35rem;">Status</label>
                        <select name="status" class="adm-search-input" style="width:100%;box-sizing:border-box;">
                            <option>Pending</option>
                            <option>Paid</option>
                            <option>Overdue</option>
                        </select>
                    </div>
                </div>
                <?php if (!empty($appts)): ?>
                <div style="margin-bottom:1rem;">
                    <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.35rem;">Link to Appointment (optional)</label>
                    <select name="appointment_id" class="adm-search-input" style="width:100%;box-sizing:border-box;">
                        <option value="">— None —</option>
                        <?php foreach ($appts as $ap): ?>
                        <option value="<?php echo $ap['id']; ?>">
                            <?php echo htmlspecialchars($ap['appointment_id'].' — '.($ap['patient_name']??'Walk-in').' @ Dr. '.$ap['doctor_name'].' ('.date('d M Y',strtotime($ap['appointment_date'])).')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div style="margin-bottom:1.2rem;">
                    <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.35rem;">Notes</label>
                    <textarea name="notes" class="adm-search-input" rows="3" placeholder="Any additional notes…" style="width:100%;box-sizing:border-box;resize:vertical;"></textarea>
                </div>
                <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                    <a href="payment.php" class="adm-btn adm-btn-back">Cancel</a>
                    <button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-save"></i> Save Payment</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
</main>

<script>
const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click',()=>{sidebar.classList.toggle('active');overlay.classList.toggle('active');});
overlay?.addEventListener('click',()=>{sidebar.classList.remove('active');overlay.classList.remove('active');});
const html = document.documentElement;
const themeIcon = document.getElementById('themeIcon');
function applyTheme(t){html.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon';}
applyTheme(localStorage.getItem('rmu_theme')||'light');
document.getElementById('themeToggle')?.addEventListener('click',()=>applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));

document.getElementById('payForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const msg = document.getElementById('formMsg');
    msg.style.display='none';
    const fd = new FormData(e.target);
    fd.append('action','add_payment');
    const r = await fetch('/RMU-Medical-Management-System/php/payment/payment_handler.php', {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) {
        msg.className='adm-alert adm-alert-success';
        msg.innerHTML='<i class="fas fa-check-circle"></i> Payment recorded! Receipt ID: <strong>'+d.receipt_id+'</strong>';
        msg.style.display='flex';
        e.target.reset();
        setTimeout(()=>window.location.href='payment.php', 1800);
    } else {
        msg.className='adm-alert adm-alert-danger';
        msg.innerHTML='<i class="fas fa-exclamation-triangle"></i> '+(d.message||'Failed to save payment.');
        msg.style.display='flex';
    }
});
</script>
</body></html>
