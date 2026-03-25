/**
 * RMU Medical Sickbay — Inventory & Procurement Logic
 */

class InventoryManager {
    constructor() {
        this.apiBase = '/RMU-Medical-Management-System/php/api/router.php?path=pharmacy/';
        this.init();
    }

    init() {
        this.loadInventory();
        this.loadAlerts();
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.getElementById('searchMed')?.addEventListener('input', (e) => this.filterInventory(e.target.value));
        document.getElementById('refreshInv')?.addEventListener('click', () => this.loadInventory());
    }

    async loadInventory() {
        const grid = document.getElementById('inventoryGrid');
        if (!grid) return;

        try {
            const res = await fetch(this.apiBase + 'inventory');
            const result = await res.json();
            if (result.success) {
                this.renderInventory(result.data);
            }
        } catch (e) {
            console.error('Inventory Fetch Failed:', e);
        }
    }

    renderInventory(data) {
        const grid = document.getElementById('inventoryGrid');
        grid.innerHTML = data.map(med => this.createMedCard(med)).join('');
    }

    createMedCard(med) {
        const stockStatus = this.getStockStatus(med.total_stock, med.reorder_level);
        return `
            <div class="inventory-card">
                <span class="stock-badge badge-${stockStatus.class}">${stockStatus.label}</span>
                <div class="med-name">${med.medicine_name}</div>
                <div class="med-meta">${med.category} | Batch tracking enabled</div>
                
                <div class="stock-stat">
                    <span class="stat-label">Stock Quantity</span>
                    <span class="stat-value">${med.total_stock || 0} ${med.unit || 'units'}</span>
                </div>
                <div class="stock-stat">
                    <span class="stat-label">Reorder Level</span>
                    <span class="stat-value">${med.reorder_level}</span>
                </div>
                <div class="stock-stat" style="border-bottom:none;">
                    <span class="stat-label">Expiring Batches</span>
                    <span class="stat-value" style="color:${med.expiring_batches > 0 ? '#ef4444' : '#10b981'}">${med.expiring_batches}</span>
                </div>

                <div style="display:flex; gap:0.5rem; margin-top:1rem;">
                    <button class="inv-btn btn-restock" onclick="Inv.showRestockModal(${med.id})">
                        <i class="fas fa-plus"></i> Restock
                    </button>
                    <button class="inv-btn btn-details" onclick="Inv.showDetails(${med.id})">
                        <i class="fas fa-info-circle"></i> Details
                    </button>
                </div>
            </div>
        `;
    }

    getStockStatus(stock, threshold) {
        if (stock <= 0) return { label: 'Out of Stock', class: 'out' };
        if (stock <= threshold) return { label: 'Low Stock', class: 'low' };
        return { label: 'In Stock', class: 'safe' };
    }

    async loadAlerts() {
        const container = document.getElementById('alertFeed');
        if (!container) return;

        try {
            const res = await fetch(this.apiBase + 'alerts');
            const result = await res.json();
            if (result.success) {
                this.renderAlerts(result.data);
            }
        } catch (e) {
            console.error('Alerts Fetch Failed:', e);
        }
    }

    renderAlerts(data) {
        const feed = document.getElementById('alertFeed');
        let html = '';
        
        data.expiring.forEach(ex => {
            html += `
                <div style="padding:0.75rem; border-left:4px solid #8b5cf6; background:#f5f3ff; margin-bottom:0.5rem; border-radius:0 0.5rem 0.5rem 0;">
                    <div style="font-weight:700; color:#6b21a8; font-size:0.875rem;">BATCH EXPIRY ALERT</div>
                    <div style="font-size:0.8rem;">${ex.medicine_name} [${ex.batch_number}] expires on ${ex.expiry_date}</div>
                </div>
            `;
        });

        data.low_stock.forEach(low => {
            html += `
                <div style="padding:0.75rem; border-left:4px solid #ef4444; background:#fef2f2; margin-bottom:0.5rem; border-radius:0 0.5rem 0.5rem 0;">
                    <div style="font-weight:700; color:#991b1b; font-size:0.875rem;">LOW STOCK ALERT</div>
                    <div style="font-size:0.8rem;">${low.medicine_name} is below threshold (${low.stock_quantity}/${low.reorder_level})</div>
                </div>
            `;
        });

        feed.innerHTML = html || '<div style="color:#64748b; font-size:0.875rem; text-align:center; padding:1rem;">No critical alerts</div>';
    }

    filterInventory(query) {
        const cards = document.querySelectorAll('.inventory-card');
        cards.forEach(card => {
            const name = card.querySelector('.med-name').textContent.toLowerCase();
            card.style.display = name.includes(query.toLowerCase()) ? 'block' : 'none';
        });
    }

    showRestockModal(id) {
        console.log('Restocking medicine:', id);
        // Implement restock modal logic
    }
}

window.Inv = new InventoryManager();
