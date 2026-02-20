<?php
include 'db_conn.php';

$active_page = 'medicine';
$page_title  = 'Medicine Inventory';
include '../includes/_sidebar.php';
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
                <p>Track stock levels, expiry dates, and manage the pharmacy inventory using FEFO principles.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/medicine/add-medicine.php" class="adm-btn adm-btn-primary">
                <i class="fas fa-plus"></i> Add Medicine
            </a>
        </div>

        <?php
        $total     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines"))[0] ?? 0;
        $low_stock = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines WHERE stock_quantity <= reorder_level AND stock_quantity > 0"))[0] ?? 0;
        $out_stock = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines WHERE stock_quantity = 0"))[0] ?? 0;
        $expiring  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND expiry_date > CURDATE()"))[0] ?? 0;
        ?>

        <!-- Low-stock alert -->
        <?php if ($low_stock > 0 || $out_stock > 0): ?>
        <div class="adm-alert adm-alert-<?php echo $out_stock > 0 ? 'danger' : 'warning'; ?>">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <?php if ($out_stock > 0): ?>
                    <strong><?php echo $out_stock; ?> medicine(s) are completely out of stock.</strong>
                <?php endif; ?>
                <?php if ($low_stock > 0): ?>
                    <strong><?php echo $low_stock; ?> medicine(s) are running low.</strong>
                <?php endif; ?>
                Restock promptly to maintain patient care.
            </div>
        </div>
        <?php endif; ?>

        <!-- Expiry alert -->
        <?php if ($expiring > 0): ?>
        <div class="adm-alert adm-alert-warning">
            <i class="fas fa-calendar-times"></i>
            <div><strong><?php echo $expiring; ?> medicine(s)</strong> are expiring within the next 60 days. Apply FEFO (First-Expiry, First-Out) management.</div>
        </div>
        <?php endif; ?>

        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total; ?></div>
                <div class="adm-mini-card-label">Total Items</div>
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
                <div class="adm-mini-card-num orange"><?php echo $expiring; ?></div>
                <div class="adm-mini-card-label">Expiring Soon</div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-box-open"></i> Medicine Stock</h3>
                <form method="post" action="search.php" class="adm-search-form" style="margin:0;">
                    <div class="adm-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="adm-search-input" placeholder="Search by name or category...">
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Medicine ID</th>
                            <th>Name</th>
                            <th>Generic Name</th>
                            <th>Category</th>
                            <th>Unit Price (GH₵)</th>
                            <th>Stock Qty</th>
                            <th>Expiry Date</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql   = "SELECT * FROM medicines ORDER BY category, medicine_name";
                        $query = mysqli_query($conn, $sql);
                        if (!$query || mysqli_num_rows($query) === 0) {
                            echo "<tr><td colspan='10' style='text-align:center;padding:3rem;color:var(--text-muted);'>No medicines found. <a href='add-medicine.php' style='color:var(--primary);font-weight:600;'>Add one now.</a></td></tr>";
                        } else {
                            $n = 1;
                            while ($med = mysqli_fetch_assoc($query)):
                                // Stock badge
                                if ($med['stock_quantity'] == 0) {
                                    $stock_badge = 'adm-badge-danger';
                                    $stock_label = 'Out of Stock';
                                    $row_class   = 'row-danger';
                                } elseif ($med['stock_quantity'] <= $med['reorder_level']) {
                                    $stock_badge = 'adm-badge-warning';
                                    $stock_label = 'Low';
                                    $row_class   = 'row-warning';
                                } else {
                                    $stock_badge = 'adm-badge-success';
                                    $stock_label = 'OK';
                                    $row_class   = '';
                                }
                                // Expiry highlight
                                $expiry_warn = '';
                                if (!empty($med['expiry_date'])) {
                                    $days_left = (strtotime($med['expiry_date']) - time()) / 86400;
                                    if ($days_left <= 30) $expiry_warn = 'color:var(--danger);font-weight:700;';
                                    elseif ($days_left <= 60) $expiry_warn = 'color:var(--warning);font-weight:600;';
                                }
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($med['medicine_id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($med['medicine_name']); ?></strong></td>
                            <td style="color:var(--text-secondary);"><?php echo htmlspecialchars($med['generic_name'] ?? 'N/A'); ?></td>
                            <td><span class="adm-badge adm-badge-info"><?php echo htmlspecialchars($med['category'] ?? 'N/A'); ?></span></td>
                            <td><?php echo number_format($med['unit_price'], 2); ?></td>
                            <td>
                                <?php echo $med['stock_quantity']; ?>
                                <span class="adm-badge <?php echo $stock_badge; ?>" style="margin-left:.4rem;"><?php echo $stock_label; ?></span>
                            </td>
                            <td style="<?php echo $expiry_warn; ?>">
                                <?php echo $med['expiry_date'] ?: 'N/A'; ?>
                            </td>
                            <td>
                                <?php if ($med['is_prescription_required']): ?>
                                    <span class="adm-badge adm-badge-primary">Rx</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge-success">OTC</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/medicine/update.php?medicine_id=<?php echo $med['medicine_id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="/RMU-Medical-Management-System/php/medicine/Delete.php?medicine_id=<?php echo $med['medicine_id']; ?>"
                                       class="adm-btn adm-btn-danger adm-btn-sm"
                                       onclick="return confirm('Delete this medicine?');"><i class="fas fa-trash"></i> Delete</a>
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
const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
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