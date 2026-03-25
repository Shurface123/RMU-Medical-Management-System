/**
 * RMU Medical Sickbay — Staff & HR Hub Actions
 */

class HRManager {
    constructor() {
        this.apiBase = '/RMU-Medical-Management-System/php/api/router.php?path=hr/';
        this.init();
    }

    init() {
        this.loadPerformance();
        this.loadRoster();
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.getElementById('refreshHR')?.addEventListener('click', () => {
            this.loadPerformance();
            this.loadRoster();
        });
    }

    async loadPerformance() {
        const grid = document.getElementById('performanceGrid');
        if (!grid) return;

        try {
            const res = await fetch(this.apiBase + 'performance');
            const result = await res.json();
            if (result.success) this.renderPerformance(result.data);
        } catch (e) {
            console.error('Performance Fetch Failed:', e);
        }
    }

    renderPerformance(data) {
        const grid = document.getElementById('performanceGrid');
        grid.innerHTML = data.map(staff => this.createPerfCard(staff)).join('');
    }

    createPerfCard(staff) {
        const roleLbl = staff.role.replace('_', ' ').toUpperCase();
        return `
            <div class="perf-card">
                <div class="staff-profile">
                    <div class="staff-avatar">${staff.name.charAt(0)}</div>
                    <div>
                        <div style="font-weight:700; color:#1e293b;">${staff.name}</div>
                        <div style="font-size:0.75rem; color:#64748b;">${roleLbl} • ${staff.department}</div>
                    </div>
                </div>

                <div class="kpi-section">
                    <div style="display:flex; justify-content:space-between; font-size:0.875rem;">
                        <span style="color:#64748b; font-weight:500;">Task Completion</span>
                        <span style="font-weight:700; color:#6366f1;">${staff.completion_rate}%</span>
                    </div>
                    <div class="kpi-bar-container">
                        <div class="kpi-bar" style="width: ${staff.completion_rate}%"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.75rem; margin-top:0.25rem; color:#94a3b8;">
                        <span>${staff.completed_tasks} completed</span>
                        <span>${staff.total_tasks} total</span>
                    </div>
                </div>

                <div style="margin-top:1rem; display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-size:0.75rem; color:#64748b;">
                        Latest KPI: <strong>${staff.latest_kpi || '--'}%</strong>
                    </div>
                    <button class="adm-btn adm-btn-back adm-btn-sm" onclick="HR.showReviewPanel(${staff.id})">
                        <i class="fas fa-pen"></i> Review
                    </button>
                </div>
            </div>
        `;
    }

    async loadRoster() {
        const feed = document.getElementById('rosterFeed');
        if (!feed) return;

        try {
            const res = await fetch(this.apiBase + 'duty_roster');
            const result = await res.json();
            if (result.success) this.renderRoster(result.data);
        } catch (e) {
            console.error('Roster Fetch Failed:', e);
        }
    }

    renderRoster(data) {
        const feed = document.getElementById('rosterFeed');
        if (data.length === 0) {
            feed.innerHTML = '<div style="text-align:center; padding:2rem; color:#94a3b8;">No staff currently on duty</div>';
            return;
        }

        feed.innerHTML = data.map(staff => `
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem; border-bottom:1px solid #f1f5f9;">
                <div>
                    <div style="font-weight:600; color:#1e293b;">${staff.name}</div>
                    <div style="font-size:0.75rem; color:#64748b;">${staff.department}</div>
                </div>
                <div style="text-align:right;">
                    <span class="status-badge on-duty">On Duty</span>
                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.25rem;">Shift ends ${staff.end_time}</div>
                </div>
            </div>
        `).join('');
    }

    showReviewPanel(id) {
        console.log('Opening review panel for staff:', id);
    }
}

window.HR = new HRManager();
