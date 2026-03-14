<?php
/**
 * tab_reports.php — Module 13: Reports
 */
// Role-specific reports list
$report_defs = [];

switch ($staffRole) {
    case 'ambulance_driver':
        $report_defs = [
            ['key'=>'trip_summary',   'label'=>'My Trip Summary',      'icon'=>'fa-ambulance', 'desc'=>'All trips I completed, with patient and timing details.'],
            ['key'=>'fuel_log',       'label'=>'Vehicle Fuel logs',     'icon'=>'fa-gas-pump',  'desc'=>'All fuel top-ups logged by me.'],
            ['key'=>'vehicle_issues', 'label'=>'Vehicle Issue Reports', 'icon'=>'fa-tools',     'desc'=>'All vehicle faults I reported.'],
        ];
        break;
    case 'cleaner':
        $report_defs = [
            ['key'=>'cleaning_history',    'label'=>'Cleaning History',        'icon'=>'fa-broom',    'desc'=>'All areas I cleaned with timestamps.'],
            ['key'=>'contamination_report','label'=>'Contamination Reports',   'icon'=>'fa-biohazard','desc'=>'All contamination incidents I reported.'],
        ];
        break;
    case 'laundry_staff':
        $report_defs = [
            ['key'=>'laundry_batches','label'=>'My Laundry Batches','icon'=>'fa-tshirt','desc'=>'All batches I processed.'],
            ['key'=>'laundry_damage', 'label'=>'Damage Reports',    'icon'=>'fa-exclamation','desc'=>'All linen damage reports I filed.'],
        ];
        break;
    case 'maintenance':
        $report_defs = [
            ['key'=>'repairs_completed','label'=>'Completed Repairs',   'icon'=>'fa-check','desc'=>'All maintenance jobs I completed.'],
            ['key'=>'pending_jobs',     'label'=>'Outstanding Jobs',    'icon'=>'fa-tools','desc'=>'Jobs still open or on hold.'],
        ];
        break;
    case 'security':
        $report_defs = [
            ['key'=>'incidents',     'label'=>'Incident Reports', 'icon'=>'fa-exclamation-triangle','desc'=>'All security incidents I reported.'],
            ['key'=>'visitor_log',   'label'=>'Visitor Log',      'icon'=>'fa-user-check','desc'=>'All visitors I logged.'],
            ['key'=>'patrol_report', 'label'=>'Patrol Check-ins', 'icon'=>'fa-route','desc'=>'My patrol checkpoint history.'],
        ];
        break;
    case 'kitchen_staff':
        $report_defs = [
            ['key'=>'meal_delivery','label'=>'Meal Delivery Report','icon'=>'fa-utensils','desc'=>'All meals I prepared and delivered.'],
            ['key'=>'dietary_flags','label'=>'Dietary Flags',      'icon'=>'fa-allergies','desc'=>'Dietary issues I reported.'],
        ];
        break;
}

// Always add universal reports
$report_defs = array_merge($report_defs, [
    ['key'=>'task_report',    'label'=>'My Task Report',       'icon'=>'fa-clipboard-list','desc'=>'Complete task history with statuses and completion details.'],
    ['key'=>'attendance_log', 'label'=>'Attendance / Shift Log','icon'=>'fa-calendar-check','desc'=>'My complete shift and attendance records.'],
    ['key'=>'leave_history',  'label'=>'Leave Request History', 'icon'=>'fa-umbrella-beach','desc'=>'All leave requests with outcomes.'],
]);
?>
<div id="sec-reports" class="dash-section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
        <h2 style="font-size:2.2rem;font-weight:700;"><i class="fas fa-file-alt" style="color:var(--role-accent);"></i> My Reports</h2>
    </div>

    <!-- Date Range Picker -->
    <div class="card" style="margin-bottom:2rem;padding:1.5rem 2rem;">
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
            <span style="font-weight:600;font-size:1.3rem;"><i class="fas fa-filter" style="color:var(--role-accent);"></i> Date Range:</span>
            <input type="date" id="rptFromDate" class="form-control" style="width:auto;" value="<?=date('Y-m-01')?>">
            <span>to</span>
            <input type="date" id="rptToDate" class="form-control" style="width:auto;" value="<?=date('Y-m-d')?>">
            <button class="btn btn-outline btn-sm" onclick="document.getElementById('rptFromDate').value='<?=date('Y-m-d',strtotime('-7 days'))?>';document.getElementById('rptToDate').value='<?=date('Y-m-d')?>';">Last 7 Days</button>
            <button class="btn btn-outline btn-sm" onclick="document.getElementById('rptFromDate').value='<?=date('Y-m-01')?>';document.getElementById('rptToDate').value='<?=date('Y-m-d')?>';">This Month</button>
        </div>
    </div>

    <!-- Report Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.8rem;">
        <?php foreach($report_defs as $rpt): ?>
        <div class="card" style="cursor:pointer;transition:var(--transition);" onmouseenter="this.style.boxShadow='var(--shadow-md)';this.style.transform='translateY(-3px)'" onmouseleave="this.style.boxShadow='';this.style.transform=''">
            <div class="card-body">
                <div style="display:flex;align-items:flex-start;gap:1.5rem;margin-bottom:1.5rem;">
                    <div style="width:50px;height:50px;border-radius:14px;background:var(--role-accent-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas <?=e($rpt['icon'])?>" style="font-size:2rem;color:var(--role-accent);"></i>
                    </div>
                    <div>
                        <h4 style="font-size:1.5rem;font-weight:700;margin:0 0 .4rem;"><?=e($rpt['label'])?></h4>
                        <p style="font-size:1.2rem;color:var(--text-muted);margin:0;"><?=e($rpt['desc'])?></p>
                    </div>
                </div>
                <div style="display:flex;gap:1rem;">
                    <button class="btn btn-primary btn-sm" style="flex:1;" onclick="generateReport('<?=e($rpt['key'])?>','<?=e($rpt['label'])?>','html')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn btn-outline btn-sm" style="flex:1;" onclick="generateReport('<?=e($rpt['key'])?>','<?=e($rpt['label'])?>','csv')">
                        <i class="fas fa-download"></i> CSV
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Report Viewer -->
    <div id="reportViewer" style="display:none;margin-top:2rem;">
        <div class="card">
            <div class="card-header">
                <h3 id="reportViewerTitle"><i class="fas fa-table"></i> Report</h3>
                <button class="btn btn-outline btn-sm" onclick="document.getElementById('reportViewer').style.display='none'"><i class="fas fa-times"></i> Close</button>
            </div>
            <div class="card-body" id="reportViewerBody" style="overflow-x:auto;">
                <div id="reportLoading" style="text-align:center;padding:4rem;color:var(--text-muted);">
                    <i class="fas fa-spinner fa-spin" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>Generating report...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function generateReport(key, label, format) {
    const from = document.getElementById('rptFromDate').value;
    const to   = document.getElementById('rptToDate').value;
    if(!from||!to){ showToast('Please select a date range.','warning'); return; }

    if(format === 'csv') {
        const url = `${BASE}/php/dashboards/staff_actions.php?action=export_report&report_key=${key}&from=${from}&to=${to}&format=csv`;
        window.open(url,'_blank');
        return;
    }

    document.getElementById('reportViewer').style.display='block';
    document.getElementById('reportViewerTitle').innerHTML = `<i class="fas fa-table"></i> ${label}`;
    document.getElementById('reportViewerBody').innerHTML = `<div id="reportLoading" style="text-align:center;padding:4rem;color:var(--text-muted);">
        <i class="fas fa-spinner fa-spin" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>Generating report...</div>`;
    document.getElementById('reportViewer').scrollIntoView({behavior:'smooth'});

    try {
        const res = await fetch(`${BASE}/php/dashboards/staff_actions.php`,{
            method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`action=get_report&report_key=${key}&from=${from}&to=${to}&format=html`
        });
        const data = await res.json();
        if(data.success && data.html) {
            document.getElementById('reportViewerBody').innerHTML = data.html;
        } else {
            document.getElementById('reportViewerBody').innerHTML = `<p style="text-align:center;padding:3rem;color:var(--text-muted);">${data.message||'No data found for the selected period.'}</p>`;
        }
    } catch(e) {
        document.getElementById('reportViewerBody').innerHTML = `<p style="color:var(--danger);text-align:center;padding:3rem;">Failed to generate report. Please try again.</p>`;
    }
}
</script>
