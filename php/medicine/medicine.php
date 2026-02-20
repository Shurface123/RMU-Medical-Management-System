<?php
include 'db_conn.php';

$active_page = 'medicines';
$page_title  = 'Medicine Inventory';
include '../includes/_sidebar.php';

// Stats
$total_med  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines"))[0] ?? 0;
$low_stock  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines WHERE stock_quantity > 0 AND stock_quantity <= reorder_level"))[0] ?? 0;
$out_stock  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines WHERE stock_quantity = 0"))[0] ?? 0;
$exp_meds   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)"))[0] ?? 0;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-pills" style="color:var(--primary);margin-right:.8rem;"></i>Medicine Inventory</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Medicine Inventory</h1>
                <p>Manage pharmacy stock, expiry dates, and reorder levels using FEFO principles.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/medicine/add-medicine.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-plus"></i> Add Medicine
            </a>
        </div>

        <?php if ($low_stock > 0 || $out_stock > 0): ?>
        <div class="adm-alert adm-alert-<?php echo $out_stock > 0 ? 'danger' : 'warning'; ?>">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Inventory Alert!</strong>
                <?php if ($out_stock > 0): ?> <b><?php echo $out_stock; ?></b> medicine(s) are <b>out of stock</b>.<?php endif; ?>
                <?php if ($low_stock > 0): ?> <b><?php echo $low_stock; ?></b> medicine(s) are <b>running low</b>.<?php endif; ?>
                Please restock immediately.
            </div>
        </div>
        <?php endif; ?>

        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_med; ?></div>
                <div class="adm-mini-card-label">Total Medicines</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $total_med - $low_stock - $out_stock; ?></div>
                <div class="adm-mini-card-label">In Stock</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $low_stock; ?></div>
                <div class="adm-mini-card-label">Low Stock</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num red"><?php echo $out_stock; ?></div>
                <div class="adm-mini-card-label">Out of Stock</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num orange"><?php echo $exp_meds; ?></div>
                <div class="adm-mini-card-label">Expiring ≤ 60 days</div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-pills"></i> Medicine Register</h3>
                <form method="get" class="adm-search-form" style="margin:0;">
                    <div class="adm-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="adm-search-input"
                               placeholder="Search by name or category..."
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Search</button>
                </form>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Medicine ID</th>
                            <th>Name</th>
                            <th>Generic</th>
                            <th>Category</th>
                            <th>Stock Qty</th>
                            <th>Reorder Lvl</th>
                            <th>Unit Price (GH₵)</th>
                            <th>Expiry Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
                        $where  = $search ? "WHERE (medicine_name LIKE '%$search%' OR generic_name LIKE '%$search%' OR category LIKE '%$search%')" : '';
                        $sql    = "SELECT * FROM medicines $where ORDER BY stock_quantity ASC";
                        $query  = mysqli_query($conn, $sql);
                        if (!$query || mysqli_num_rows($query) === 0) {
                            echo "<tr><td colspan='12' style='text-align:center;padding:3rem;color:var(--text-muted);'>No medicines found.</td></tr>";
                        } else {
                            $n = 1;
                            while ($med = mysqli_fetch_assoc($query)):
                                $qty = (int)$med['stock_quantity'];
                                $reorder = (int)$med['reorder_level'];

                                if ($qty === 0) { $stock_badge = 'adm-badge adm-badge-danger'; $stock_lbl = 'Out of Stock'; $row_cls = 'row-danger'; }
                                elseif ($qty <= $reorder) { $stock_badge = 'adm-badge adm-badge-warning'; $stock_lbl = 'Low Stock'; $row_cls = 'row-warning'; }
                                else { $stock_badge = 'adm-badge adm-badge-success'; $stock_lbl = 'In Stock'; $row_cls = ''; }

                                $expiry_style = '';
                                if ($med['expiry_date']) {
                                    $days_left = (strtotime($med['expiry_date']) - time()) / 86400;
                                    if ($days_left <= 30) $expiry_style = 'color:#e74c3c;font-weight:700;';
                                    elseif ($days_left <= 60) $expiry_style = 'color:#f39c12;font-weight:700;';
                                }
                                $rx_badge = $med['is_prescription_required'] ? '<span class="adm-badge adm-badge-danger">Rx</span>' : '<span class="adm-badge adm-badge-info">OTC</span>';
                        ?>
                        <tr class="<?php echo $row_cls; ?>">
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($med['medicine_id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($med['medicine_name']); ?></strong></td>
                            <td style="color:var(--text-secondary);"><?php echo htmlspecialchars($med['generic_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($med['category'] ?? 'N/A'); ?></td>
                            <td><strong><?php echo number_format($qty); ?></strong></td>
                            <td><?php echo number_format($reorder); ?></td>
                            <td><?php echo number_format($med['unit_price'], 2); ?></td>
                            <td style="<?php echo $expiry_style; ?>">
                                <?php echo $med['expiry_date'] ? date('d M Y', strtotime($med['expiry_date'])) : 'N/A'; ?>
                            </td>
                            <td><?php echo $rx_badge; ?></td>
                            <td><span class="<?php echo $stock_badge; ?>"><?php echo $stock_lbl; ?></span></td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/medicine/update.php?id=<?php echo $med['id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i></a>
                                    <a href="/RMU-Medical-Management-System/php/medicine/Delete.php?id=<?php echo $med['id']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm"
                                       onclick="return confirm('Delete this medicine?');"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = document.getElementById('themeIcon');
const html        = document.documentElement;
function applyTheme(t) { html.setAttribute('data-theme',t); localStorage.setItem('rmu_theme',t); themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon'; }
applyTheme(localStorage.getItem('rmu_theme') || 'light');
themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
</script>
</body>
</html>