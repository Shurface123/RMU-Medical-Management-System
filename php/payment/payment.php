<?php
$active_page = 'payment';
$page_title  = 'Payment Management';
include '../includes/_sidebar.php';
include 'db_conn.php';

date_default_timezone_set('Africa/Accra');

// ── AJAX: mark as paid ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    if ($action === 'mark_paid') {
        $id = (int)$_POST['id'];
        if ($id > 0 && mysqli_query($conn, "UPDATE payments SET status='Paid', paid_at=NOW() WHERE id=$id")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
    }
    exit;
}

// ── Check table exists ─────────────────────────────────────────
$tbl_check = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
$tbl_exists = $tbl_check && mysqli_num_rows($tbl_check) > 0;

if ($tbl_exists) {
    // Filters
    $search   = isset($_GET['q'])      ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
    $stat_f   = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status'])  : '';
    $date_f   = isset($_GET['date'])   ? mysqli_real_escape_string($conn, $_GET['date'])    : '';

    $where = ['1=1'];
    if ($search)  $where[] = "(u.name LIKE '%$search%' OR pm.receipt_id LIKE '%$search%')";
    if ($stat_f)  $where[] = "pm.status = '$stat_f'";
    if ($date_f)  $where[] = "DATE(pm.created_at) = '$date_f'";
    $where_sql = implode(' AND ', $where);

    // Stats
    $today_str = date('Y-m-d');
    $stat_total   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM payments"))[0] ?? 0;
    $stat_paid    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM payments WHERE status='Paid'"))[0] ?? 0;
    $stat_pending = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM payments WHERE status='Pending'"))[0] ?? 0;
    $stat_revenue = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Paid'"))[0] ?? 0;
    $stat_today   = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Paid' AND DATE(paid_at)='$today_str'"))[0] ?? 0;
}
?>

<main class="adm-main">
<div class="adm-topbar">
    <div class="adm-topbar-left">
        <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <span class="adm-page-title"><i class="fas fa-credit-card" style="color:var(--primary);margin-right:.8rem;"></i>Payment Management</span>
    </div>
    <div class="adm-topbar-right">
        <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
        <div class="adm-avatar"><i class="fas fa-user"></i></div>
    </div>
</div>

<div class="adm-content">
<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Payment Management</h1>
        <p>Track consultation fees, receipts, and payment statuses.</p>
    </div>
    <?php if ($tbl_exists): ?>
    <button class="adm-btn adm-btn-primary" onclick="document.getElementById('addPayModal').style.display='flex'">
        <i class="fas fa-plus"></i> Add Payment
    </button>
    <?php endif; ?>
</div>

<?php if (!$tbl_exists): ?>
<!-- Module not yet set up -->
<div class="adm-card" style="padding:3rem;text-align:center;">
    <i class="fas fa-credit-card" style="font-size:3rem;color:var(--text-muted);opacity:.3;display:block;margin-bottom:1rem;"></i>
    <h3 style="margin-bottom:.5rem;">Payment Module Not Yet Configured</h3>
    <p style="color:var(--text-secondary);margin-bottom:1.5rem;max-width:480px;margin-left:auto;margin-right:auto;">
        The <code>payments</code> table has not been created in the database. Run the SQL below to enable this module.
    </p>
    <pre style="background:var(--bg-secondary);border-radius:12px;padding:1.5rem;text-align:left;font-size:.8rem;overflow-x:auto;max-width:640px;margin:0 auto;">CREATE TABLE IF NOT EXISTS payments (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  receipt_id     VARCHAR(30) NOT NULL UNIQUE,
  appointment_id INT DEFAULT NULL,
  patient_id     INT DEFAULT NULL,
  amount         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  method         ENUM('Cash','GhIPSS','Mobile Money','Card') DEFAULT 'Cash',
  status         ENUM('Pending','Paid','Overdue','Refunded') DEFAULT 'Pending',
  notes          TEXT,
  paid_at        DATETIME DEFAULT NULL,
  created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
  FOREIGN KEY (patient_id)     REFERENCES patients(id)     ON DELETE SET NULL
);</pre>
</div>

<?php else: ?>

<!-- Stats -->
<div class="adm-summary-strip">
    <div class="adm-mini-card">
        <div class="adm-mini-card-num"><?php echo $stat_total; ?></div>
        <div class="adm-mini-card-label">Total Records</div>
    </div>
    <div class="adm-mini-card">
        <div class="adm-mini-card-num green">GH₵<?php echo number_format($stat_revenue, 2); ?></div>
        <div class="adm-mini-card-label">Total Revenue</div>
    </div>
    <div class="adm-mini-card">
        <div class="adm-mini-card-num blue">GH₵<?php echo number_format($stat_today, 2); ?></div>
        <div class="adm-mini-card-label">Today's Income</div>
    </div>
    <div class="adm-mini-card">
        <div class="adm-mini-card-num orange"><?php echo $stat_pending; ?></div>
        <div class="adm-mini-card-label">Pending</div>
    </div>
    <div class="adm-mini-card">
        <div class="adm-mini-card-num green"><?php echo $stat_paid; ?></div>
        <div class="adm-mini-card-label">Paid</div>
    </div>
</div>

<!-- Filters -->
<form method="get" class="adm-card" style="padding:1rem 1.5rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;margin-bottom:1rem;">
    <div style="flex:1;min-width:180px;">
        <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.3rem;color:var(--text-secondary);">Search</label>
        <input type="text" name="q" class="adm-search-input" placeholder="Patient name or receipt ID" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
    </div>
    <div style="min-width:140px;">
        <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.3rem;color:var(--text-secondary);">Status</label>
        <select name="status" class="adm-search-input">
            <option value="">All Statuses</option>
            <?php foreach (['Paid','Pending','Overdue','Refunded'] as $s): ?>
            <option value="<?php echo $s;?>" <?php echo ($stat_f===$s)?'selected':'';?>><?php echo $s;?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="min-width:150px;">
        <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.3rem;color:var(--text-secondary);">Date</label>
        <input type="date" name="date" class="adm-search-input" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">
    </div>
    <div style="display:flex;gap:.5rem;">
        <button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="payment.php" class="adm-btn adm-btn-back"><i class="fas fa-times"></i> Clear</a>
    </div>
</form>

<!-- Table -->
<div class="adm-card">
    <div class="adm-card-header">
        <h3><i class="fas fa-list"></i> Payment Records</h3>
    </div>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Receipt ID</th>
                    <th>Patient</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT pm.*, u.name AS patient_name
                    FROM payments pm
                    LEFT JOIN patients p ON pm.patient_id = p.id
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE $where_sql
                    ORDER BY pm.created_at DESC LIMIT 100";
            $q = mysqli_query($conn, $sql);
            if (!$q || mysqli_num_rows($q) === 0) {
                echo "<tr><td colspan='9' style='text-align:center;padding:3rem;color:var(--text-muted);'><i class='fas fa-receipt' style='font-size:2rem;display:block;margin-bottom:.75rem;opacity:.3;'></i>No payment records found.</td></tr>";
            } else {
                $n = 1;
                while ($pm = mysqli_fetch_assoc($q)):
                    $sc = $pm['status']==='Paid' ? 'success' : ($pm['status']==='Overdue' ? 'danger' : ($pm['status']==='Refunded' ? 'info' : 'warning'));
                    $method_icons = ['Cash'=>'fa-coins','GhIPSS'=>'fa-building-columns','Mobile Money'=>'fa-mobile-alt','Card'=>'fa-credit-card'];
                    $mi = $method_icons[$pm['method']] ?? 'fa-circle-dollar-to-slot';
            ?>
            <tr>
                <td><?php echo $n++; ?></td>
                <td><span class="adm-badge adm-badge-primary" style="font-size:.72rem;"><?php echo htmlspecialchars($pm['receipt_id']); ?></span></td>
                <td><strong><?php echo htmlspecialchars($pm['patient_name'] ?? 'Walk-in'); ?></strong></td>
                <td><strong style="color:var(--primary);">GH₵<?php echo number_format($pm['amount'], 2); ?></strong></td>
                <td><i class="fas <?php echo $mi;?>" style="margin-right:.35rem;color:var(--text-secondary);"></i><?php echo htmlspecialchars($pm['method']); ?></td>
                <td><span class="adm-badge adm-badge-<?php echo $sc;?>"><?php echo $pm['status']; ?></span></td>
                <td><?php echo $pm['paid_at'] ? date('d M Y', strtotime($pm['paid_at'])) : date('d M Y', strtotime($pm['created_at'])); ?></td>
                <td style="max-width:160px;font-size:.8rem;color:var(--text-muted);"><?php echo htmlspecialchars(substr($pm['notes'] ?? '', 0, 50)); ?></td>
                <td>
                    <div class="adm-table-actions">
                        <?php if ($pm['status'] !== 'Paid'): ?>
                        <button class="adm-btn adm-btn-success adm-btn-sm" onclick="markPaid(<?php echo $pm['id'];?>, this)" title="Mark as Paid"><i class="fas fa-check"></i></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Payment Modal -->
<div id="addPayModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:var(--bg-card);border-radius:20px;padding:2rem;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
            <h3 style="margin:0;"><i class="fas fa-plus" style="color:var(--primary);margin-right:.5rem;"></i>Add Payment</h3>
            <button onclick="document.getElementById('addPayModal').style.display='none'" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--text-muted);">&times;</button>
        </div>
        <form id="addPayForm">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                    <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Patient Name</label>
                    <input type="text" name="patient_name" class="adm-search-input" placeholder="Patient name" style="width:100%;box-sizing:border-box;" required>
                </div>
                <div>
                    <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Amount (GH₵)</label>
                    <input type="number" name="amount" class="adm-search-input" placeholder="0.00" step="0.01" min="0" style="width:100%;box-sizing:border-box;" required>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                    <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Payment Method</label>
                    <select name="method" class="adm-search-input" style="width:100%;box-sizing:border-box;">
                        <option>Cash</option><option>GhIPSS</option><option>Mobile Money</option><option>Card</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Status</label>
                    <select name="status" class="adm-search-input" style="width:100%;box-sizing:border-box;">
                        <option>Pending</option><option>Paid</option><option>Overdue</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:1.2rem;">
                <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Notes</label>
                <textarea name="notes" class="adm-search-input" rows="2" placeholder="Optional notes…" style="width:100%;box-sizing:border-box;resize:vertical;"></textarea>
            </div>
            <div id="addPayError" style="display:none;" class="adm-alert adm-alert-danger"></div>
            <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('addPayModal').style.display='none'" class="adm-btn adm-btn-back">Cancel</button>
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

async function markPaid(id, btn) {
    if (!confirm('Mark this payment as Paid?')) return;
    btn.disabled = true;
    const fd = new FormData(); fd.append('action','mark_paid'); fd.append('id', id);
    const r = await fetch('payment.php', {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) location.reload();
    else { alert('Could not update payment.'); btn.disabled=false; }
}

document.getElementById('addPayForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const errEl = document.getElementById('addPayError');
    errEl.style.display = 'none';
    const fd = new FormData(e.target);
    const r = await fetch('/RMU-Medical-Management-System/php/payment/payment_handler.php', {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) location.reload();
    else { errEl.innerHTML='<i class="fas fa-exclamation-triangle"></i> '+(d.message||'Failed to add payment.'); errEl.style.display='flex'; }
});
</script>
</body></html>
