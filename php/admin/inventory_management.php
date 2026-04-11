<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'inventory';
$page_title = 'Inventory Hub';
include '../includes/_sidebar.php';

// Fetch basic stats for immediate render
$totalMeds = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM medicines"))['count'];
$lowStock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM medicines WHERE stock_quantity <= reorder_level"))['count'];
$expired = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM pharmacy_inventory WHERE expiry_date <= CURDATE()"))['count'];
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-boxes"></i> Pharmacy Inventory Hub</span>
        </div>
        <div class="adm-topbar-right">
            <div class="adm-topbar-datetime">
                <i class="far fa-calendar-alt"></i>
                <span><?php echo date('D, M d, Y'); ?></span>
            </div>
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Global Inventory Command</h1>
                <p>Unified drug catalog, stock tracking, and real-time fulfillment monitoring.</p>
            </div>
            <div style="display:flex; gap:1rem;">
                <div class="adm-search-wrapper" style="position:relative;">
                    <i class="fas fa-search" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                    <input type="text" id="searchMed" class="adm-form-input" placeholder="Search medicine..." style="padding-left:3rem; width:280px; height:48px; border-radius:12px;">
                </div>
                <button id="refreshInv" class="btn btn-primary btn" style="background:var(--surface); border:1px solid var(--border); width:48px; height:48px; justify-content:center; padding:0;"><span class="btn-text">
                    <i class="fas fa-sync-alt" style="color:var(--primary);"></i>
                </span></button>
                <button class="btn btn-primary"><span class="btn-text">
                    <i class="fas fa-plus"></i> New Medicine
                </span></button>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="adm-stats-grid">
            <div class="adm-stat-card">
                <div class="adm-stat-icon medicine"><i class="fas fa-pills"></i></div>
                <div class="adm-stat-label">Total Medicines</div>
                <div class="adm-stat-value"><?php echo number_format($totalMeds); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-box"></i> Active Catalog</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background: linear-gradient(135deg, var(--danger), #EC7063);"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="adm-stat-label">Low Stock Items</div>
                <div class="adm-stat-value" style="color:var(--danger);"><?php echo number_format($lowStock); ?></div>
                <div class="adm-stat-footer" style="color:var(--danger);"><i class="fas fa-shopping-cart"></i> Needs Immediate restock</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);"><i class="fas fa-calendar-times"></i></div>
                <div class="adm-stat-label">Expired Records</div>
                <div class="adm-stat-value"><?php echo number_format($expired); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-trash-alt"></i> Flagged for removal</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background: linear-gradient(135deg, var(--success), #58D68D);"><i class="fas fa-shield-alt"></i></div>
                <div class="adm-stat-label">Healthy Stock</div>
                <div class="adm-stat-value"><?php echo number_format($totalMeds - $lowStock); ?></div>
                <div class="adm-stat-footer" style="color:var(--success);"><i class="fas fa-check-double"></i> Optimal levels</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 340px; gap:2.5rem;">
            <!-- Inventory Grid -->
            <div>
                <div class="adm-card-header" style="background:transparent; border:none; padding-left:0; padding-bottom:1.5rem;">
                    <h3 style="font-size:1.4rem;"><i class="fas fa-th-large"></i> Live Drug Inventory</h3>
                </div>
                <div id="inventoryGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;">
                    <div style="grid-column: 1 / -1; padding:5rem; text-align:center; background:var(--surface); border-radius:16px; border:1px solid var(--border);">
                        <i class="fas fa-spinner fa-spin" style="font-size:2.5rem; color:var(--primary); margin-bottom:1rem;"></i>
                        <p style="color:var(--text-secondary); font-weight:500;">Establishing secure connection to pharmacy records...</p>
                    </div>
                </div>
            </div>

            <!-- Alerts & Feed -->
            <div style="display:flex; flex-direction:column; gap:2.5rem;">
                <div class="adm-card shadow-sm" style="border-radius:20px;">
                    <div class="adm-card-header" style="padding: 1.8rem 2rem;">
                        <h3><i class="fas fa-bolt"></i> Stock Alerts</h3>
                        <span class="adm-badge" style="background:var(--danger-light); color:var(--danger);">Real-time</span>
                    </div>
                    <div class="adm-card-body" style="padding: 0;">
                        <div id="alertFeed" style="padding: 1.5rem; max-height:600px; overflow-y:auto;">
                            <!-- Dynamic Content -->
                            <div style="text-align:center; color:var(--text-muted); padding:2rem;">
                                <i class="fas fa-radar fa-spin-pulse" style="font-size:2rem; margin-bottom:1rem; opacity:0.5;"></i>
                                <p>Scanning for anomalies...</p>
                            </div>
                        </div>
                        <div style="padding: 1.5rem; border-top: 1px solid var(--border); background: var(--bg-surface);">
                            <a href="procurement_center.php" class="btn btn-primary btn" style="width:100%; border:1px solid var(--border); justify-content:center;"><span class="btn-text">
                                <i class="fas fa-truck-loading"></i> Open Procurement Center
                            </span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.inventory-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.8rem;
    border: 1px solid var(--border);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.inventory-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); border-color: var(--primary); }
.med-name { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: 1rem 0 0.4rem; }
.med-meta { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; margin-bottom: 1.2rem; }
.stock-badge { position: absolute; top: 1.5rem; right: 1.5rem; padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
.badge-safe { background: var(--success-light); color: var(--success); }
.badge-low { background: var(--warning-light); color: var(--warning); }
.badge-out { background: var(--danger-light); color: var(--danger); }
.stock-stat { display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
.stat-label { color: var(--text-secondary); font-weight: 500; }
.stat-value { color: var(--text-primary); font-weight: 700; }
.inv-btn { flex: 1; border-radius: 8px; padding: 0.6rem; font-size: 0.85rem; font-weight: 600; text-align: center; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; border: none; }
.btn-restock { background: var(--primary-light); color: var(--primary); }
.btn-restock:hover { background: var(--primary); color: #fff; }
.btn-details { background: var(--bg); color: var(--text-secondary); }
.btn-details:hover { background: var(--text-secondary); color: #fff; }
</style>

<script src="../admin/inventory_actions.js"></script>
<script>
// UI Toggles
const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
const menuToggle = document.getElementById('menuToggle');

if (menuToggle) {
    menuToggle.onclick = () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    };
}
if (overlay) {
    overlay.onclick = () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    };
}

const themeToggle = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');

if (themeToggle) {
    themeToggle.onclick = () => {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme') || 'light';
        const target = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', target);
        localStorage.setItem('rmu_theme', target);
        if (themeIcon) themeIcon.className = target === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    };
}
</script>
</body>
</html>

