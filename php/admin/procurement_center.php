<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'procurement';
$page_title = 'Procurement Center';
include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-truck-loading"></i> Procurement Command Center</span>
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
                <h1>Supplier & Purchase Operations</h1>
                <p>Manage medical supply chains, vendor relations, and procurement lifecycle.</p>
            </div>
            <div style="display:flex; gap:1rem;">
                <button class="adm-btn adm-btn-outline" style="background:var(--surface); border:1px solid var(--border);">
                    <i class="fas fa-truck"></i> Vendor Directory
                </button>
                <button class="adm-btn adm-btn-primary">
                    <i class="fas fa-plus"></i> New Purchase Order
                </button>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 380px; gap:2.5rem;">
            <!-- Purchase Orders -->
            <div class="adm-card shadow-sm" style="border-radius:20px;">
                <div class="adm-card-header" style="padding: 1.8rem 2.5rem;">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Active Purchase Orders</h3>
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <span class="adm-badge" style="background:var(--primary-light); color:var(--primary);">Processing</span>
                    </div>
                </div>
                <div class="adm-table-wrap" style="padding:0;">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Order Reference</th>
                                <th>Primary Supplier</th>
                                <th>Issue Date</th>
                                <th>Status</th>
                                <th>Total Value</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="poTableBody">
                            <tr>
                                <td colspan="6" style="text-align:center; padding:5rem; color:var(--text-muted);">
                                    <i class="fas fa-spinner fa-spin" style="font-size:2rem; margin-bottom:1rem; display:block; color:var(--primary);"></i>
                                    Decrypting procurement ledger...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Audit Trail -->
            <div style="display:flex; flex-direction:column; gap:2.5rem;">
                <div class="adm-card shadow-sm" style="border-radius:20px; height:fit-content;">
                    <div class="adm-card-header" style="padding: 1.8rem 2rem;">
                        <h3><i class="fas fa-history"></i> Stock Audit Trail</h3>
                        <button class="adm-btn adm-btn-sm" style="background:transparent; color:var(--primary); padding:0; height:auto; width:auto;"><i class="fas fa-external-link-alt"></i></button>
                    </div>
                    <div class="adm-card-body" style="padding: 0;">
                        <div id="transactionFeed" style="padding: 1.5rem; display:flex; flex-direction:column; gap:1.2rem; max-height:700px; overflow-y:auto;">
                            <!-- Dynamic rendering -->
                            <div style="text-align:center; padding:3rem; color:var(--text-muted);">
                                <i class="fas fa-sync fa-spin" style="font-size:1.5rem; margin-bottom:0.5rem; opacity:0.5;"></i>
                                <p>Syncing transactions...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.stock-badge { padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
.badge-safe { background: var(--success-light); color: var(--success); }
.badge-low { background: var(--warning-light); color: var(--warning); }
.badge-out { background: var(--danger-light); color: var(--danger); }
.badge-received { background: var(--success-light); color: var(--success); }
.badge-pending { background: var(--warning-light); color: var(--warning); }

.audit-item { display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; border-radius: 12px; background: var(--bg-surface); border: 1px solid var(--border); transition: 0.2s; }
.audit-item:hover { border-color: var(--primary); background: var(--surface); }
</style>

<script>
    const apiBase = '/RMU-Medical-Management-System/php/api/router.php?path=pharmacy/';
    
    async function loadProcurement() {
        const body = document.getElementById('poTableBody');
        const feed = document.getElementById('transactionFeed');
        
        try {
            const res = await fetch(apiBase + 'procurement');
            const result = await res.json();
            if (result.success) renderPO(result.data);

            const res2 = await fetch(apiBase + 'transactions');
            const result2 = await res2.json();
            if (result2.success) renderTransactions(result2.data);
        } catch (e) { 
            console.error(e); 
            if(body) body.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--danger);">Error connecting to API.</td></tr>';
        }
    }

    function renderPO(orders) {
        const body = document.getElementById('poTableBody');
        if (!body) return;
        if (orders.length === 0) {
            body.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:4rem; color:var(--text-muted);">No active purchase orders found in the system.</td></tr>';
            return;
        }
        body.innerHTML = orders.map(po => {
            const statusClass = po.status.toLowerCase() === 'received' ? 'received' : 'pending';
            return `
                <tr>
                    <td style="font-weight:700; color:var(--primary);">#PO-${po.order_id.toString().padStart(4, '0')}</td>
                    <td><strong>${po.supplier_name}</strong></td>
                    <td style="color:var(--text-secondary); font-size:0.9rem;">${new Date(po.order_date).toLocaleDateString()}</td>
                    <td><span class="stock-badge badge-${statusClass}">${po.status}</span></td>
                    <td style="font-weight:800; color:var(--text-primary);">$${parseFloat(po.total_amount).toLocaleString()}</td>
                    <td style="text-align:right;">
                        <button class="adm-btn adm-btn-sm" style="background:var(--primary-light); color:var(--primary);"><i class="fas fa-eye"></i> Details</button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderTransactions(txs) {
        const feed = document.getElementById('transactionFeed');
        if (!feed) return;
        if (txs.length === 0) {
            feed.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-muted);">No audit logs available.</div>';
            return;
        }
        feed.innerHTML = txs.map(tx => `
            <div class="audit-item">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <span style="font-weight:700; color:var(--text-primary); font-size:0.95rem;">${tx.medicine_name}</span>
                    <span class="adm-badge" style="background:${tx.transaction_type === 'Addition' ? 'var(--success-light)' : 'var(--danger-light)'}; color:${tx.transaction_type === 'Addition' ? 'var(--success)' : 'var(--danger)'}; padding:0.2rem 0.5rem; font-size:0.75rem;">
                        ${tx.transaction_type === 'Addition' ? '+' : '-'}${tx.quantity}
                    </span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:var(--text-muted);">
                    <span><i class="fas fa-user-edit" style="width:14px;"></i> ${tx.performed_by_name}</span>
                    <span><i class="far fa-clock" style="width:14px;"></i> ${new Date(tx.transaction_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                </div>
            </div>
        `).join('');
    }

    loadProcurement();

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

