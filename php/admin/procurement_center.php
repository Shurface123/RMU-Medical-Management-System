<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'procurement';
$page_title = 'Procurement Center';
include '../includes/_sidebar.php';
?>

<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">
<style>
.staff-hero{display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;background:linear-gradient(135deg,#4f46e5,#3730a3);border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap;position:relative;overflow:hidden;}
.staff-hero-avatar{width:72px;height:72px;border-radius:50%;border:3px solid rgba(255,255,255,.35);background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0;z-index:2;}
.staff-hero-info{z-index:2;}.staff-hero-info h2{font-size:2rem;font-weight:700;margin:0;}.staff-hero-info p{font-size:1.3rem;margin:.3rem 0 0;opacity:.85;}
.hero-bg-icon{position:absolute;right:-20px;bottom:-40px;font-size:15rem;opacity:.07;transform:rotate(-15deg);z-index:1;}
.stf-table{width:100%;border-collapse:collapse;font-size:1.15rem;}
.stf-table th{background:var(--surface-2);color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left;}
.stf-table td{padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle;}
.stf-table tr:hover td{background:var(--surface-2);}
</style>

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

    <div class="adm-content" style="animation:fadeIn .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-truck hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="staff-hero-info">
                <h2>Supplier & Purchase Operations</h2>
                <p>Manage medical supply chains, vendor relations, and procurement lifecycle.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <button class="btn" style="background:rgba(255,255,255,0.15); color:#fff; border:1px solid rgba(255,255,255,0.3);"><i class="fas fa-truck"></i> Vendor Directory</button>
                <button class="btn btn-primary" style="background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); color:#fff; backdrop-filter:blur(5px);"><i class="fas fa-plus"></i> New Purchase Order</button>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 380px; gap:2.5rem;">
            <!-- Purchase Orders -->
            <div class="adm-card shadow-sm" style="border-radius:20px; overflow:hidden; border:1px solid var(--border);">
                <div class="adm-card-header" style="padding: 1.8rem 2.5rem; background:var(--surface-2); border-bottom:1px solid var(--border);">
                    <h3><i class="fas fa-file-invoice-dollar" style="color:var(--primary);"></i> Active Purchase Orders</h3>
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <span class="adm-badge" style="background:var(--primary-light); color:var(--primary); font-weight:700;">Processing</span>
                    </div>
                </div>
                <div class="adm-table-wrap" style="padding:0;">
                    <table class="stf-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Supplier</th>
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
                <div class="adm-card shadow-sm" style="border-radius:20px; border:1px solid var(--border); overflow:hidden;">
                    <div class="adm-card-header" style="padding: 1.8rem 2rem; background:var(--surface-2); border-bottom:1px solid var(--border);">
                        <h3><i class="fas fa-history" style="color:var(--text-muted);"></i> Stock Audit Trail</h3>
                    </div>
                    <div class="adm-card-body" style="padding: 0; background:var(--surface);">
                        <div id="transactionFeed" style="padding: 1.5rem; display:flex; flex-direction:column; gap:1.2rem; max-height:700px; overflow-y:auto;">
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
                        <button class="btn btn-primary btn btn-sm" style="background:var(--primary-light); color:var(--primary);"><span class="btn-text"><i class="fas fa-eye"></i> Details</span></button>
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

