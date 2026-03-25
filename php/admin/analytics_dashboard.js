/**
 * RMU Medical Sickbay — Analytics Dashboard Logic
 * Handles real-time KPI updates, Chart.js visualizations, and filtering.
 */

class AnalyticsDashboard {
    constructor() {
        this.baseUrl = '/RMU-Medical-Management-System/php/api/router.php?path=analytics/';
        this.charts = {};
        this.refreshInterval = 60000; // 60 seconds
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.refreshAll();
        this.startAutoRefresh();
    }

    setupEventListeners() {
        document.getElementById('applyFilters')?.addEventListener('click', () => this.refreshAll());
        document.getElementById('refreshToggle')?.addEventListener('change', (e) => {
            if (e.target.checked) this.startAutoRefresh();
            else this.stopAutoRefresh();
        });
    }

    async refreshAll() {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const params = `&startDate=${start}&endDate=${end}`;

        await Promise.all([
            this.fetchKPIs(),
            this.fetchPatientAnalytics(params),
            this.fetchClinicalAnalytics(params),
            this.fetchStaffPerformance(params),
            this.fetchPharmacyAnalytics(params),
            this.fetchFinancialAnalytics(params),
            this.fetchSystemUsage(params)
        ]);
    }

    async fetchKPIs() {
        try {
            const res = await fetch(this.baseUrl + 'executive');
            const result = await res.json();
            if (result.success) this.updateKPIs(result.data);
        } catch (e) {
            console.error('KPI Update Failed:', e);
        }
    }

    updateKPIs(data) {
        document.getElementById('patientsToday').textContent = data.patients_today;
        document.getElementById('activeAdmissions').textContent = data.active_admissions;
        document.getElementById('staffOnDuty').textContent = data.staff_on_duty;
        document.getElementById('pendingEmergencies').textContent = data.pending_emergencies;
        document.getElementById('medsToday').textContent = data.meds_today;
        document.getElementById('labsToday').textContent = data.labs_today;
        
        const indicator = document.querySelector('.refreshing-indicator');
        if (indicator) {
            indicator.style.display = 'inline-block';
            setTimeout(() => indicator.style.display = 'none', 2000);
        }
    }

    async fetchPatientAnalytics(params) {
        const res = await fetch(this.baseUrl + 'patient' + params);
        const result = await res.json();
        if (result.success) this.renderPatientCharts(result.data);
    }

    renderPatientCharts(data) {
        // Admissions Line Chart
        this.updateChart('admissionChart', 'line', {
            labels: data.trends.admissions?.map(t => t.date) || [],
            datasets: [
                {
                    label: 'Admissions',
                    data: data.trends.admissions?.map(t => t.admissions) || [],
                    borderColor: '#3b82f6',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)'
                },
                {
                    label: 'Discharges',
                    data: data.trends.discharges?.map(t => t.discharges) || [],
                    borderColor: '#10b981',
                    tension: 0.4
                }
            ]
        });

        // Ward Donut
        this.updateChart('wardChart', 'doughnut', {
            labels: data.ward_dist.map(w => w.ward),
            datasets: [{
                data: data.ward_dist.map(w => w.count),
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
            }]
        });

        // LOS Bar
        this.updateChart('losChart', 'bar', {
            labels: data.los.map(l => l.department),
            datasets: [{
                label: 'Avg LOS (Days)',
                data: data.los.map(l => l.avg_los),
                backgroundColor: '#f59e0b'
            }]
        });

        // Age/Gender
        this.updateChart('ageChart', 'bar', {
            labels: data.demographics.age.map(a => a.age_group),
            datasets: [{
                label: 'Patients by Age',
                data: data.demographics.age.map(a => a.count),
                backgroundColor: '#3b82f6'
            }]
        });

        // Readmission Trend
        this.updateChart('readmissionChart', 'line', {
            labels: data.readmission_trend.map(r => r.date),
            datasets: [{
                label: '30-Day Readmissions',
                data: data.readmission_trend.map(r => r.count),
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true
            }]
        });
    }

    async fetchClinicalAnalytics(params) {
        const res = await fetch(this.baseUrl + 'clinical' + params);
        const result = await res.json();
        if (result.success) this.renderClinicalCharts(result.data);
    }

    renderClinicalCharts(data) {
        this.updateChart('vitalsChart', 'bar', {
            labels: data.vitals_trends.map(v => v.date),
            datasets: [{
                label: 'Flagged Vitals',
                data: data.vitals_trends.map(v => v.flagged),
                backgroundColor: '#ef4444'
            }]
        });

        this.updateChart('tatChart', 'bar', {
            labels: data.lab_tat.map(t => t.test_name),
            datasets: [{
                label: 'Avg TAT (Mins)',
                data: data.lab_tat.map(t => t.avg_tat),
                backgroundColor: '#8b5cf6'
            }]
        });

        this.updateChart('medComplianceChart', 'doughnut', {
            labels: data.med_compliance.map(m => m.status),
            datasets: [{
                data: data.med_compliance.map(m => m.count),
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b']
            }]
        });
    }

    async fetchStaffPerformance(params) {
        const res = await fetch(this.baseUrl + 'staff' + params);
        const result = await res.json();
        if (result.success) this.renderStaffCharts(result.data);
    }

    renderStaffCharts(data) {
        this.updateChart('staffTaskChart', 'bar', {
            labels: data.task_performance.map(s => s.name),
            datasets: [{
                label: 'Task Completion %',
                data: data.task_performance.map(s => (s.completed / s.total * 100).toFixed(1)),
                backgroundColor: '#8b5cf6'
            }]
        });

        this.updateChart('volumeChart', 'bar', {
            labels: data.doctor_rx.map(d => d.name),
            datasets: [
                {
                    label: 'Prescriptions',
                    data: data.doctor_rx.map(d => d.count),
                    backgroundColor: '#3b82f6'
                },
                {
                    label: 'Lab Tests',
                    data: data.lab_volume.map(l => l.count),
                    backgroundColor: '#10b981'
                }
            ]
        });

        this.renderTable('loginActivityTable', ['Name', 'Role', 'Sessions', 'Last Active'], 
            data.login_activity.map(l => [l.name, l.user_role, l.sessions, l.last_login || 'Never']));
    }

    async fetchPharmacyAnalytics(params) {
        const res = await fetch(this.baseUrl + 'pharmacy' + params);
        const result = await res.json();
        if (result.success) this.renderPharmacyCharts(result.data);
    }

    renderPharmacyCharts(data) {
        this.updateChart('pharmacyChart', 'bar', {
            labels: data.top_meds.map(m => m.medication_name),
            datasets: [{
                label: 'Prescription Vol',
                data: data.top_meds.map(m => m.count),
                backgroundColor: '#3b82f6'
            }],
            options: { indexAxis: 'y' }
        });

        this.updateChart('fulfillmentChart', 'doughnut', {
            labels: data.fulfillment.map(f => f.status),
            datasets: [{
                data: data.fulfillment.map(f => f.count),
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b']
            }]
        });

        this.renderTable('stockAlertsTable', ['Medicine', 'Stock', 'Threshold'], 
            data.stock_alerts.map(a => [a.medicine_name, a.stock_quantity, a.reorder_level]));
    }

    async fetchFinancialAnalytics(params) {
        const res = await fetch(this.baseUrl + 'financial' + params);
        const result = await res.json();
        if (result.success) this.renderFinancialCharts(result.data);
    }

    renderFinancialCharts(data) {
        this.updateChart('revenueChart', 'line', {
            labels: data.revenue_trend.map(r => r.date),
            datasets: [{
                label: 'Daily Revenue',
                data: data.revenue_trend.map(r => r.total),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true
            }]
        });

        this.updateChart('paymentMethodChart', 'doughnut', {
            labels: data.methods.map(m => m.payment_method),
            datasets: [{
                data: data.methods.map(m => m.count),
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']
            }]
        });

        this.updateChart('deptRevenueChart', 'bar', {
            labels: data.dept_revenue.map(d => d.department),
            datasets: [{
                label: 'Revenue by Dept',
                data: data.dept_revenue.map(d => d.total),
                backgroundColor: '#f59e0b'
            }]
        });

        this.renderTable('outstandingTable', ['Patient', 'Total Owed'], 
            data.outstanding.map(o => [o.full_name, `$${parseFloat(o.total).toLocaleString()}`]));
    }

    async fetchSystemUsage(params) {
        const res = await fetch(this.baseUrl + 'system' + params);
        const result = await res.json();
        if (result.success) this.renderSystemCharts(result.data);
    }

    renderSystemCharts(data) {
        this.updateChart('usageChart', 'line', {
            labels: data.dau.map(d => d.date),
            datasets: [{
                label: 'Daily Active Users',
                data: data.dau.map(d => d.users),
                borderColor: '#6366f1'
            }]
        });

        this.updateChart('failedLoginChart', 'line', {
            labels: data.failed_logins.map(f => f.date),
            datasets: [{
                label: 'Failed Logins',
                data: data.failed_logins.map(f => f.count),
                borderColor: '#ef4444'
            }]
        });

        this.updateChart('moduleChart', 'doughnut', {
            labels: data.top_modules.map(m => m.table_name || 'System'),
            datasets: [{
                data: data.top_modules.map(m => m.count),
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6366f1']
            }]
        });
    }

    renderTable(id, headers, rows) {
        const container = document.getElementById(id);
        if (!container) return;

        let html = `<table class="analytics-table">
            <thead><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead>
            <tbody>
                ${rows.length > 0 ? rows.map(r => `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`).join('') : '<tr><td colspan="'+headers.length+'" style="text-align:center; padding:2rem;">No data available for this period</td></tr>'}
            </tbody>
        </table>`;
        container.innerHTML = html;
    }

    updateChart(id, type, data, extraOptions = {}) {
        const ctx = document.getElementById(id)?.getContext('2d');
        if (!ctx) return;

        if (this.charts[id]) this.charts[id].destroy();

        this.charts[id] = new Chart(ctx, {
            type: type,
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                ...extraOptions
            }
        });
    }

    startAutoRefresh() {
        this.timer = setInterval(() => this.fetchKPIs(), this.refreshInterval);
    }

    stopAutoRefresh() {
        if (this.timer) clearInterval(this.timer);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.Analytics = new AnalyticsDashboard();
});
