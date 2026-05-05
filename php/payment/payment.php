<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'payment';
$page_title  = 'Financial Hub';
include '../includes/_sidebar.php';

date_default_timezone_set('Africa/Accra');

// ── Check table exists ─────────────────────────────────────────
$tbl_check = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
$tbl_exists = $tbl_check && mysqli_num_rows($tbl_check) > 0;

if ($tbl_exists) {
    // Stats
    $today_str = date('Y-m-d');
    $stat_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM payments"))[0] ?? 0;
    $stat_paid = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM payments WHERE status='Paid'"))[0] ?? 0;
    $stat_pending = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM payments WHERE status='Pending'"))[0] ?? 0;
    $stat_revenue = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Paid'"))[0] ?? 0;
    $stat_today = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Paid' AND DATE(payment_date)='$today_str'"))[0] ?? 0;
}
?>

<!-- DataTables Dependencies -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<style>
/* ── V2 Payment Styles ── */
.payment-hero {
    background: linear-gradient(135deg, #27AE60 0%, #1a2a6c 100%);
    color: white;
    padding: 3rem;
    border-radius: var(--radius-lg);
    margin-bottom: 3rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.stat-v2-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-v2-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 2.2rem;
    display: flex;
    align-items: center;
    gap: 1.8rem;
    transition: var(--transition);
}

.payment-status { padding: 0.5rem 1rem; border-radius: 10px; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
.status-paid { background: rgba(39, 174, 96, 0.1); color: #27AE60; }
.status-pending { background: rgba(243, 156, 18, 0.1); color: #F39C12; }
.status-overdue { background: rgba(231, 76, 60, 0.1); color: #E74C3C; }
.status-refunded { background: rgba(47, 128, 237, 0.1); color: #2F80ED; }

/* Modal Styling */
.modal-glass {
    position: fixed;
    inset: 0;
    background: rgba(15, 22, 40, 0.85);
    backdrop-filter: blur(10px);
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}
.modal-content {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    width: 100%;
    max-width: 550px;
    padding: 3rem;
    box-shadow: var(--shadow-lg);
    animation: modalSlide 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalSlide { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-receipt" style="color:var(--success);margin-right:.8rem;"></i>Financial Operations</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar" style="overflow:hidden; border:2px solid rgba(39, 174, 96, 0.2);">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" style="width:100%; height:100%; object-fit:cover;">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <div class="payment-hero">
            <div>
                <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 0.5rem;">Financial Hub</h1>
                <p style="opacity: 0.9; font-size: 1.3rem;">Audit consultation fees, insurance claims, and real-time revenue streams.</p>
            </div>
            <button class="btn btn-primary" style="background:white; color:var(--success); border:none; padding:1.2rem 2.5rem; font-weight:700; border-radius:15px; box-shadow:0 10px 20px rgba(0,0,0,0.1);" onclick="$('#addPayModal').css('display', 'flex')"><span class="btn-text">
                <i class="fas fa-plus-circle"></i> Create New Invoice
            </span></button>
        </div>

        <?php if ($tbl_exists): ?>
        <div class="stat-v2-grid">
            <div class="stat-v2-card" style="border-bottom:4px solid var(--success);">
                <div style="font-size:2.8rem; font-weight:900; color:var(--success);">GH₵<?= number_format($stat_revenue, 2) ?></div>
                <div style="font-size:1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Total Revenue</div>
            </div>
            <div class="stat-v2-card" style="border-bottom:4px solid var(--primary);">
                <div style="font-size:2.8rem; font-weight:900; color:var(--primary);">GH₵<?= number_format($stat_today, 2) ?></div>
                <div style="font-size:1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Today's Settlement</div>
            </div>
            <div class="stat-v2-card" style="border-bottom:4px solid var(--warning);">
                <div style="font-size:2.8rem; font-weight:900; color:var(--warning);"><?= $stat_pending ?></div>
                <div style="font-size:1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Pending Invoices</div>
            </div>
            <div class="stat-v2-card" style="border-bottom:4px solid #9b59b6;">
                <div style="font-size:2.8rem; font-weight:900; color:#9b59b6;"><?= $stat_total ?></div>
                <div style="font-size:1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Transactions</div>
            </div>
        </div>

        <div class="adm-card" style="padding:2.5rem; border-radius:24px;">
            <table class="clinical-table display responsive nowrap" id="paymentsTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Invoice / Receipt</th>
                        <th>Patient Entity</th>
                        <th>Amount (GH₵)</th>
                        <th>Settlement Method</th>
                        <th>Status</th>
                        <th>Financial Date</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT pm.*, u.name AS patient_name
                            FROM payments pm
                            LEFT JOIN patients p ON pm.patient_id = p.id
                            LEFT JOIN users u ON p.user_id = u.id
                            ORDER BY pm.created_at DESC";
                    $q = mysqli_query($conn, $sql);
                    while ($pm = mysqli_fetch_assoc($q)):
                        $s_cls = 'status-' . strtolower($pm['status']);
                        $method_icons = ['Cash' => 'fa-coins', 'GhIPSS' => 'fa-university', 'Mobile Money' => 'fa-mobile-alt', 'Card' => 'fa-credit-card'];
                        $mi = $method_icons[$pm['payment_method'] ?? ''] ?? 'fa-wallet';
                    ?>
                    <tr>
                        <td><span class="adm-badge adm-badge-primary" style="font-weight:700; letter-spacing:1px;"><?= htmlspecialchars($pm['receipt_number']) ?></span></td>
                        <td>
                            <div style="font-weight:800; font-size:1.3rem; color:var(--text-primary);"><?= htmlspecialchars($pm['patient_name'] ?? 'Walk-in Patient') ?></div>
                        </td>
                        <td><div style="font-weight:900; font-size:1.5rem; color:var(--text-primary);">GH₵ <?= number_format($pm['amount'], 2) ?></div></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem; font-weight:700; color:var(--text-secondary);">
                                <i class="fas <?= $mi ?>" style="font-size:1.2rem; color:var(--primary);"></i>
                                <?= htmlspecialchars($pm['payment_method']) ?>
                            </div>
                        </td>
                        <td><span class="payment-status <?= $s_cls ?>"><?= $pm['status'] ?></span></td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);"><?= date('d M Y', strtotime($pm['payment_date'] ?? $pm['created_at'])) ?></div>
                            <div style="font-size:0.9rem; color:var(--text-muted);"><?= date('H:i A', strtotime($pm['created_at'])) ?></div>
                        </td>
                        <td>
                            <?php if ($pm['status'] !== 'Paid'): ?>
                                <button class="btn btn-success btn-sm" style="border-radius:10px; padding:0 1.2rem;" onclick="markPaid(<?= $pm['payment_id'] ?>, this)"><i class="fas fa-check-double"></i> Settle</button>
                            <?php else: ?>
                                <button class="btn btn-outline btn-sm" style="border-radius:10px; cursor:default; opacity:0.6;"><i class="fas fa-file-invoice"></i> Receipt</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="adm-card" style="padding:5rem; text-align:center;">
                <i class="fas fa-database" style="font-size:4rem; color:var(--text-muted); opacity:0.3; margin-bottom:2rem;"></i>
                <h2 style="font-weight:800;">Schema Not Found</h2>
                <p style="color:var(--text-muted); font-size:1.2rem;">The payments module requires a schema update. Please contact the system administrator.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Add Payment Modal -->
<div class="modal-glass" id="addPayModal">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem;">
            <h3 style="font-size:1.8rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-plus-circle" style="color:var(--success);"></i> Generate Invoice</h3>
            <button onclick="$('#addPayModal').fadeOut()" style="background:none; border:none; font-size:1.8rem; color:var(--text-muted); cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form id="addPayForm">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                <div class="form-group">
                    <label>Patient Reference</label>
                    <input type="text" name="patient_name" class="form-control" placeholder="Full legal name" required>
                </div>
                <div class="form-group">
                    <label>Amount (GH₵)</label>
                    <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" required>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                <div class="form-group">
                    <label>Payment Mode</label>
                    <select name="method" class="form-control">
                        <option>Cash</option>
                        <option>GhIPSS</option>
                        <option>Mobile Money</option>
                        <option>Card</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Initial Status</label>
                    <select name="status" class="form-control">
                        <option>Pending</option>
                        <option>Paid</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Institutional Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Description of services provided..."></textarea>
            </div>
            <div style="display:flex; gap:1rem; justify-content:flex-end; margin-top:2rem;">
                <button type="button" class="btn btn-outline" onclick="$('#addPayModal').fadeOut()">Discard</button>
                <button type="submit" class="btn btn-primary" style="padding:1rem 3rem;"><i class="fas fa-save"></i> Post Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
async function markPaid(id, btn) {
    if (!confirm('Authorize manual settlement for this invoice?')) return;
    btn.disabled = true;
    const fd = new FormData(); fd.append('action', 'mark_paid'); fd.append('id', id);
    try {
        const r = await fetch('payment.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) location.reload();
        else alert('Settlement failed: ' + d.message);
    } catch(e) { alert('Network error during settlement.'); }
    finally { btn.disabled = false; }
}

$(document).ready(function() {
    $('#paymentsTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: { search: "_INPUT_", searchPlaceholder: "Search ledger..." },
        dom: '<"top"f>rt<"bottom"lip><"clear">',
    });

    $('#addPayForm').on('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        try {
            const r = await fetch('/RMU-Medical-Management-System/php/payment/payment_handler.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) location.reload();
            else alert('Creation failed: ' + d.message);
        } catch(e) { alert('Network error.'); }
    });

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon   = document.getElementById('themeIcon');
    const html        = document.documentElement;
    function applyTheme(t) { html.setAttribute('data-theme',t); localStorage.setItem('rmu_theme',t); themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon'; }
    themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
    
    const sidebar = document.getElementById('admSidebar');
    const overlay = document.getElementById('admOverlay');
    document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
    overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
});
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>