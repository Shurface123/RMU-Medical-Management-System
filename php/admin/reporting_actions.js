/**
 * RMU Medical Sickbay — Reporting Center Actions
 */

class ReportBuilder {
    constructor() {
        this.apiBase = '/RMU-Medical-Management-System/php/api/router.php?path=reports/';
        this.currentData = null;
        this.currentConfig = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateCategoryOptions();
    }

    setupEventListeners() {
        document.getElementById('reportCategory')?.addEventListener('change', () => this.updateCategoryOptions());
        document.getElementById('generateForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.generateReport();
        });
        
        document.getElementById('exportCsv')?.addEventListener('click', () => this.exportCSV());
        document.getElementById('exportPdf')?.addEventListener('click', () => window.print());
        document.getElementById('exportXlsx')?.addEventListener('click', () => this.exportCSV(true)); // Simplified to CSV for now
    }

    updateCategoryOptions() {
        const cat = document.getElementById('reportCategory').value;
        const typeSelect = document.getElementById('reportType');
        if (!typeSelect) return;

        let options = '';
        if (cat === 'patient') {
            options = `
                <option value="admissions_discharges">Admissions & Discharges</option>
                <option value="demographics">Patient Demographics</option>
                <option value="census">Ward Census Report</option>
                <option value="los">Length of Stay</option>
                <option value="readmissions">Readmission Rates</option>
            `;
        } else if (cat === 'clinical') {
            options = `
                <option value="vitals">Vital Signs Register</option>
                <option value="med_admin">Medication Administration</option>
                <option value="lab_results">Laboratory Results</option>
                <option value="alerts">Emergency Alerts</option>
            `;
        } else if (cat === 'staff') {
            options = `
                <option value="attendance">Attendance & Shifts</option>
                <option value="task_completion">Task Completion</option>
                <option value="performance">Performance Summary</option>
            `;
        } else if (cat === 'pharmacy') {
            options = `
                <option value="inventory">Drug Inventory & Stock</option>
                <option value="expiry">Drug Expiry</option>
                <option value="fulfillment">Prescription Fulfillment</option>
            `;
        } else if (cat === 'financial') {
            options = `
                <option value="revenue_summary">Revenue Summary by Dept</option>
                <option value="invoices">Invoices & Billing</option>
                <option value="outstanding">Outstanding Balances</option>
            `;
        } else if (cat === 'system') {
            options = `
                <option value="audit_trail">Full Audit Trail</option>
                <option value="access_log">Access & Login Logs</option>
            `;
        }

        typeSelect.innerHTML = options;
    }

    async generateReport() {
        const btn = document.getElementById('genBtn');
        const origText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        btn.disabled = true;

        const category = document.getElementById('reportCategory').value;
        const type = document.getElementById('reportType').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        const params = { start_date: startDate, end_date: endDate };

        try {
            const res = await fetch(this.apiBase + 'generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ category, type, parameters: params })
            });
            const result = await res.json();
            
            if (result.success) {
                this.currentData = result.data.data;
                this.currentConfig = { category, type, startDate, endDate };
                this.renderPreview(result.data.data, category, type);
            } else {
                alert('Error: ' + result.message);
            }
        } catch (e) {
            console.error(e);
            alert('Failed to generate report.');
        } finally {
            btn.innerHTML = origText;
            btn.disabled = false;
        }
    }

    renderPreview(data, cat, type) {
        const titleEl = document.getElementById('rptTitle');
        const metaEl = document.getElementById('rptMeta');
        const tableEl = document.getElementById('rptTable');

        if (!titleEl || !metaEl || !tableEl) return;

        titleEl.textContent = `${cat.toUpperCase()} / ${type.replace(/_/g, ' ').toUpperCase()}`;
        metaEl.textContent = `Generated on ${new Date().toLocaleString()} | Records: ${data.length}`;

        if (data.length === 0) {
            tableEl.innerHTML = '<tr><td style="text-align:center; padding:3rem;">No records found for the selected criteria.</td></tr>';
            return;
        }

        // Generate headers dynamically
        const columns = Object.keys(data[0]);
        const thead = `<tr>${columns.map(col => `<th>${col.replace(/_/g, ' ').toUpperCase()}</th>`).join('')}</tr>`;

        // Generate rows
        const tbody = data.map(row => 
            `<tr>${columns.map(col => `<td>${row[col] !== null ? row[col] : '-'}</td>`).join('')}</tr>`
        ).join('');

        tableEl.innerHTML = `<thead>${thead}</thead><tbody>${tbody}</tbody>`;
    }

    exportCSV(isXlsx = false) {
        if (!this.currentData || this.currentData.length === 0) {
            alert('No data to export. Generate a report first.');
            return;
        }

        const columns = Object.keys(this.currentData[0]);
        let csvContent = "data:text/csv;charset=utf-8," 
            + columns.join(",") + "\n"
            + this.currentData.map(e => columns.map(c => `"${(e[c]||'').toString().replace(/"/g, '""')}"`).join(",")).join("\n");

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `report_${this.currentConfig.type}_${Date.now()}.${isXlsx ? 'xlsx' : 'csv'}`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

window.ReportBuilder = new ReportBuilder();
