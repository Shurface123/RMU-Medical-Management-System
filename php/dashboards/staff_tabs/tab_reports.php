<?php
/**
 * tab_reports.php — Module 13: Reports (Modernized)
 */
$report_defs = [];

switch ($staffRole) {
    case 'maintenance':
        $report_defs = [
            ['key'=>'repairs_completed','label'=>'Repair Performance', 'icon'=>'fa-check-double','desc'=>'Comprehensive log of all resolved maintenance tickets.'],
            ['key'=>'pending_jobs',      'label'=>'Backlog Analytics',  'icon'=>'fa-hourglass-half','desc'=>'Detailed view of active and bottlenecked tasks.'],
        ];
        break;
    case 'ambulance_driver':
        $report_defs = [
            ['key'=>'trip_summary',   'label'=>'Dispatch History',   'icon'=>'fa-route',     'desc'=>'Official record of all completed emergency dispatches.'],
            ['key'=>'fuel_log',       'label'=>'Fuel Consumption',   'icon'=>'fa-gas-pump',  'desc'=>'Historical logs of all vehicle refueling events.'],
            ['key'=>'vehicle_issues', 'label'=>'Maintenance Flags',  'icon'=>'fa-exclamation','desc'=>'Archive of all safety/mechanical issues reported.'],
        ];
        break;
    case 'cleaner':
        $report_defs = [
            ['key'=>'cleaning_history',    'label'=>'Sanitation Logs',   'icon'=>'fa-broom',    'desc'=>'Chronological history of all area sanitations.'],
            ['key'=>'contamination_report','label'=>'Hazard Reports',    'icon'=>'fa-biohazard','desc'=>'Protocol records for all contamination incidents.'],
        ];
        break;
    // Add other roles as needed...
}

// Universal Reports
$report_defs = array_merge($report_defs, [
    ['key'=>'task_report',    'label'=>'Task Audit Trail',   'icon'=>'fa-clipboard-check','desc'=>'Verification records for all assigned workflows.'],
    ['key'=>'attendance_log', 'label'=>'Time & Attendance',  'icon'=>'fa-history',        'desc'=>'Official clock-in/out and shift participation records.'],
    ['key'=>'leave_history',  'label'=>'Absence Summary',    'icon'=>'fa-plane-departure','desc'=>'Consolidated history of leave requests and status.'],
]);
?>
<div id="sec-reports" class="dash-section">
    
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2.5rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-chart-bar" style="color:var(--role-accent);"></i> Insight Reports</h2>
            <p style="font-size:1.3rem;color:var(--text-muted);margin:0.5rem 0 0;">Generate and export official performance data</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card" style="margin-bottom:3rem; padding:1.8rem 2.5rem; background:rgba(255,255,255,0.03); backdrop-filter:blur(10px);">
        <div style="display:flex; align-items:center; gap:2rem; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:1rem;">
                <div style="width:36px; height:36px; border-radius:10px; background:var(--role-accent); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                    <i class="fas fa-calendar"></i>
                </div>
                <span style="font-weight:700; font-size:1.3rem; color:var(--text-secondary);">Reporting Period</span>
            </div>
            
            <div style="display:flex; align-items:center; gap:1rem; flex:1; min-width:300px;">
                <input type="date" id="rptFromDate" class="form-control" style="flex:1; padding:.8rem 1.2rem; border-radius:8px;" value="<?= date('Y-m-01') ?>">
                <span style="color:var(--text-muted); font-weight:600;">to</span>
                <input type="date" id="rptToDate" class="form-control" style="flex:1; padding:.8rem 1.2rem; border-radius:8px;" value="<?= date('Y-m-d') ?>">
            </div>

            <div style="display:flex; gap:.8rem;">
                <button class="btn btn-outline btn-sm" style="padding:.6rem 1.2rem;" onclick="setRptRange('7days')">7 Days</button>
                <button class="btn btn-outline btn-sm" style="padding:.6rem 1.2rem;" onclick="setRptRange('month')">Month</button>
            </div>
        </div>
    </div>

    <!-- Report Grid -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:2rem;">
        <?php foreach($report_defs as $rpt): ?>
        <div class="rpt-card card" style="transition:.3s; cursor:default; border:1px solid var(--border);">
            <div class="card-body" style="padding:2rem;">
                <div style="display:flex; align-items:flex-start; gap:1.8rem; margin-bottom:2rem;">
                    <div class="rpt-icon" style="width:54px; height:54px; border-radius:16px; background:var(--surface-2); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:.3s; color:var(--role-accent);">
                        <i class="fas <?= e($rpt['icon']) ?>" style="font-size:2.2rem;"></i>
                    </div>
                    <div>
                        <h4 style="font-size:1.6rem; font-weight:800; margin:0 0 .5rem; line-height:1.2;"><?= e($rpt['label']) ?></h4>
                        <p style="font-size:1.2rem; color:var(--text-muted); margin:0; line-height:1.5; font-weight:500;"><?= e($rpt['desc']) ?></p>
                    </div>
                </div>
                
                <div style="display:flex; gap:1rem; background:var(--surface-2); padding:.6rem; border-radius:12px;">
                    <button class="btn btn-primary" style="flex:1; padding:.8rem; font-size:1.2rem;" onclick="runReport('<?= e($rpt['key']) ?>', '<?= e($rpt['label']) ?>', 'html')">
                        <span class="btn-text"><i class="fas fa-search-plus"></i> Preview</span>
                    </button>
                    <button class="btn btn-outline alternate" style="flex:1; padding:.8rem; font-size:1.2rem;" onclick="runReport('<?= e($rpt['key']) ?>', '<?= e($rpt['label']) ?>', 'csv')">
                        <span class="btn-text"><i class="fas fa-file-csv"></i> Export</span>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Dynamic Report Viewer -->
    <div id="reportContainer" style="display:none; margin-top:4rem; border-top:2px solid var(--border); padding-top:4rem;">
        <div class="card" style="box-shadow:var(--shadow-lg);">
            <div class="card-header" style="background:var(--surface-2); padding:1.8rem 2.5rem; display:flex; justify-content:space-between; align-items:center; border:none;">
                <h3 id="rptViewTitle" style="font-size:1.8rem; font-weight:800; margin:0;">Report Preview</h3>
                <div style="display:flex; gap:1rem;">
                    <button class="btn btn-outline btn-sm" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
                    <button class="btn btn-outline btn-sm" onclick="hideReportViewer()"><i class="fas fa-times"></i> Dismiss</button>
                </div>
            </div>
            <div class="card-body" style="padding:0;">
                <div id="rptLoading" style="display:none; text-align:center; padding:8rem 2rem;">
                    <div class="spinner-container" style="margin-bottom:2rem;">
                        <i class="fas fa-circle-notch fa-spin fa-4x" style="color:var(--role-accent);"></i>
                    </div>
                    <div style="font-size:1.6rem; font-weight:700;">Scanning Secure Databases...</div>
                    <p style="color:var(--text-muted); margin-top:.5rem;">Your customized report is being compiled securely.</p>
                </div>
                <div id="rptContent" style="padding:2.5rem; overflow-x:auto;">
                    <!-- Data Injected Here -->
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function setRptRange(type) {
    const from = document.getElementById('rptFromDate');
    const to = document.getElementById('rptToDate');
    const today = new Date().toISOString().split('T')[0];
    
    if (type === '7days') {
        const d = new Date();
        d.setDate(d.getDate() - 7);
        from.value = d.toISOString().split('T')[0];
    } else {
        const d = new Date();
        d.setDate(1);
        from.value = d.toISOString().split('T')[0];
    }
    to.value = today;
}

async function runReport(key, label, format) {
    const from = document.getElementById('rptFromDate').value;
    const to = document.getElementById('rptToDate').value;
    
    if (!from || !to) {
        showToast("Reporting timeframe incomplete.", "warning");
        return;
    }

    if (format === 'csv') {
        const url = `${BASE}/php/dashboards/staff_actions.php?action=export_report&report_key=${key}&from=${from}&to=${to}&format=csv`;
        window.open(url, '_blank');
        return;
    }

    // HTML Preview Path
    const container = document.getElementById('reportContainer');
    const content = document.getElementById('rptContent');
    const loader = document.getElementById('rptLoading');
    const title = document.getElementById('rptViewTitle');

    container.style.display = 'block';
    content.innerHTML = '';
    loader.style.display = 'block';
    title.innerText = label;
    container.scrollIntoView({ behavior: 'smooth' });

    try {
        const res = await staffFetch({ action: 'get_report', report_key: key, from, to, format: 'html' });

        loader.style.display = 'none';
        
        if (res && res.success && res.html) {
            content.innerHTML = res.html;
            // Initialize DataTable if table returned
            const tbl = content.querySelector('table');
            if (tbl && $.fn.DataTable) {
                $(tbl).addClass('display responsive nowrap').DataTable({
                    responsive: true,
                    pageLength: 20,
                    dom: 'Bfrtip',
                    buttons: ['copy', 'excel', 'pdf']
                });
            }
        } else {
            content.innerHTML = `<div style="text-align:center; padding:5rem; opacity:.5;">
                <i class="fas fa-search-minus fa-4x mb-3"></i>
                <h3>No Data Identified</h3>
                <p>Try adjusting your timeframe or audit filters.</p>
            </div>`;
        }
    } catch (err) {
        loader.style.display = 'none';
        content.innerHTML = `<div style="text-align:center; padding:5rem; color:var(--danger);">
            <i class="fas fa-exclamation-triangle fa-4x mb-3"></i>
            <h3>Kernel Exception</h3>
            <p>We encountered an error while synthesizing this report.</p>
        </div>`;
    }
}

function hideReportViewer() {
    document.getElementById('reportContainer').style.display = 'none';
}

function printReport() {
    const content = document.getElementById('rptContent').innerHTML;
    const win = window.open('', '_blank');
    win.document.write(`<html><head><title>Report Export</title>
        <link rel="stylesheet" href="${BASE}/css/style.css">
        <style>body{padding:2rem;} table{width:100%; border-collapse:collapse;} th,td{padding:8px; border:1px solid #ddd; text-align:left;}</style>
        </head><body>${content}</body></html>`);
    win.document.close();
    win.print();
}
</script>

<style>
.rpt-card:hover { transform: translateY(-8px); border-color: var(--role-accent) !important; box-shadow: var(--shadow-lg); }
.rpt-card:hover .rpt-icon { background: var(--role-accent); color: #fff; transform: scale(1.1); }
.btn-outline.alternate { border-color: var(--text-muted); color: var(--text-muted); }
.btn-outline.alternate:hover { border-color: var(--role-accent); color: var(--role-accent); background: rgba(47,128,237,0.05); }

.card { border-radius: 16px; overflow: hidden; }
</style>

