<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'cleaning';
$page_title = 'Cleaning & Hygiene';
include '../includes/_sidebar.php';

// Wards fetch
$wards = [];
$qw = mysqli_query($conn, "SELECT id, ward_name FROM wards ORDER BY ward_name");
if ($qw) while ($r = mysqli_fetch_assoc($qw)) $wards[] = $r;

// Fetch cleaners list
$cleaners = [];
$qc = mysqli_query($conn, "SELECT s.id, u.name FROM staff s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1 AND u.user_role = 'cleaner'");
if ($qc)
    while ($r = mysqli_fetch_assoc($qc))
        $cleaners[] = $r;

// Fetch cleaning schedules
$schedules = [];
$q = mysqli_query($conn, "
    SELECT cs.*, u1.name as primary_cleaner, u2.name as backup_cleaner
    FROM cleaning_schedules cs
    LEFT JOIN users u1 ON cs.assigned_cleaner_id = u1.id
    LEFT JOIN users u2 ON cs.backup_cleaner_id = u2.id
    ORDER BY FIELD(cs.status, 'Dispatched', 'In Progress', 'Scheduled', 'Completed', 'Inspected'), cs.created_at DESC LIMIT 50
");
if ($q)
    while ($r = mysqli_fetch_assoc($q))
        $schedules[] = $r;

// Contamination Alerts (from other staff)
$alerts = [];
$qa = mysqli_query($conn, "
    SELECT cr.*, u.name as reporter_name
    FROM contamination_reports cr
    LEFT JOIN users u ON cr.reported_by = u.id
    WHERE cr.status != 'resolved'
    ORDER BY cr.reported_at DESC
");
if ($qa)
    while ($r = mysqli_fetch_assoc($qa))
        $alerts[] = $r;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-broom"></i> Hygiene & Infection Control</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div>
                <h1>Infection Control Command</h1>
                <p>Manage cleaning tasks, isolation wards, and biological contamination reports.</p>
            </div>
            <button class="adm-btn adm-btn-primary" onclick="document.getElementById('cleanModal').classList.add('active')">
                <i class="fas fa-plus"></i> Dispatch Cleaner
            </button>
        </div>

        <?php if (!empty($alerts)): ?>
        <div class="adm-card" style="border:1px solid var(--danger);background:#fff1f0;margin-bottom:2rem;">
            <div class="adm-card-header" style="border-bottom:1px solid rgba(231,76,60,0.2);">
                <h3 style="color:var(--danger);"><i class="fas fa-biohazard"></i> Active Contamination Alerts</h3>
            </div>
            <div class="adm-card-body" style="padding:1rem 1.5rem;">
                <?php foreach ($alerts as $al): ?>
                <div style="background:#fff;padding:1rem;border-radius:8px;border-left:4px solid var(--danger);margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div>
                        <strong style="color:var(--danger);font-size:1.1rem;"><?php echo htmlspecialchars($al['location']); ?>: <?php echo ucfirst($al['contamination_type']); ?></strong>
                        <div style="font-size:.85rem;color:var(--text-muted);margin-top:.3rem;"><?php echo htmlspecialchars($al['description']); ?></div>
                        <div style="font-size:.75rem;margin-top:.5rem;">Reported by <?php echo htmlspecialchars($al['reporter_name']); ?> at <?php echo date('g:i A', strtotime($al['reported_at'])); ?></div>
                    </div>
                    <div>
                        <span class="adm-badge adm-badge-danger" style="animation:pulse 2s infinite;"><i class="fas fa-exclamation-triangle"></i> <?php echo strtoupper($al['severity']); ?> SEVERITY</span>
                        <!-- Quick assign drop-down in a real scenario would go here -->
                    </div>
                </div>
                <?php
    endforeach; ?>
            </div>
        </div>
        <?php
endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Cleaning task dispatched successfully.</div>
        <?php
endif; ?>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-clipboard-check"></i> Cleaning & Sanitization Log</h3>
            </div>
            <div class="adm-table-wrap" style="overflow-x: auto; width: 100%;">
                <table class="adm-table" style="width: 100%; min-width: 900px;">
                    <thead><tr><th>Task Issued</th><th>Location / Ward</th><th>Type & PPE</th><th>Assigned Cleaner</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($schedules)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;">No active schedules.</td></tr>
                        <?php
else:
    foreach ($schedules as $cs):
        $sc = $cs['status'] === 'Completed' || $cs['status'] === 'Inspected' ? 'success' : ($cs['status'] === 'In Progress' ? 'warning' : 'info');
        $is_overdue = ($cs['status'] === 'Dispatched' || $cs['status'] === 'Scheduled') && strtotime($cs['scheduled_time']) < (time() - 3600); // 1 hour past
        $row_bg = $is_overdue ? 'background-color:#ffeaea !important;' : ($cs['cleaning_type'] === 'Biohazard Clean' || $cs['cleaning_type'] === 'Infectious Disease' ? 'background-color:#fff3e0 !important;' : '');
        $ppe = json_decode($cs['required_ppe'], true) ?? [];
?>
                        <tr style="<?php echo $row_bg; ?>" class="<?php echo $is_overdue ? 'row-overdue' : ''; ?>">
                            <td style="white-space:nowrap;">
                                <?php echo date('d M Y, g:i A', strtotime($cs['scheduled_time'])); ?>
                                <?php if($cs['recurrence_pattern']): ?><br><span class="adm-badge adm-badge-secondary" style="margin-top:4px;"><i class="fas fa-redo-alt"></i> <?php echo htmlspecialchars($cs['recurrence_pattern']); ?></span><?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($cs['ward_area']); ?></strong>
                                <?php if($cs['specific_room']) echo " - " . htmlspecialchars($cs['specific_room']); ?>
                                <div style="font-size:.8rem;color:var(--text-muted);"><i class="fas fa-building"></i> <?php echo htmlspecialchars($cs['location_type']); ?> <?php echo $cs['floor_building'] ? " (Layer: {$cs['floor_building']})" : ''; ?></div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($cs['cleaning_type']); ?></strong>
                                <?php 
                                $lvl = strtolower($cs['contamination_level'] ?? 'none');
                                if($lvl === 'biohazard') echo '<span class="adm-badge badge-biohazard"><i class="fas fa-biohazard"></i> BIOHAZARD</span>';
                                elseif($lvl === 'high') echo '<span class="adm-badge badge-high">High</span>';
                                elseif($lvl === 'medium') echo '<span class="adm-badge badge-medium">Medium</span>';
                                elseif($lvl === 'low') echo '<span class="adm-badge badge-low">Low</span>';
                                ?>
                                <?php if(!empty($ppe)): ?>
                                    <div style="margin-top:4px;">
                                        <?php foreach($ppe as $p): 
                                            $icon = 'fa-shield-virus';
                                            if(stripos($p, 'glove') !== false) $icon = 'fa-hands-wash';
                                            if(stripos($p, 'mask') !== false) $icon = 'fa-head-side-mask';
                                            if(stripos($p, 'eye') !== false) $icon = 'fa-glasses';
                                        ?>
                                            <span class="adm-badge adm-badge-secondary" title="<?php echo htmlspecialchars($p); ?>"><i class="fas <?php echo $icon; ?>"></i></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($cs['primary_cleaner'] ?? 'Unassigned'); ?>
                                <?php if($cs['backup_cleaner']): ?><div style="font-size:0.75rem; color:var(--text-muted);">(Backup: <?php echo htmlspecialchars($cs['backup_cleaner']); ?>)</div><?php endif; ?>
                            </td>
                            <td>
                                <span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo ucfirst($cs['status']); ?></span>
                                <?php if($is_overdue): ?><span class="adm-badge badge-overdue" style="margin-top:4px;">OVERDUE</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php
    endforeach;
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="adm-modal" id="cleanModal">
    <div class="adm-modal-content" style="max-width: 950px; width: 95%;">
        <div class="adm-modal-header">
            <h3><i class="fas fa-broom"></i> Dispatch Cleaner</h3>
            <button class="adm-modal-close" type="button" onclick="document.getElementById('cleanModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <form id="cleaningDispatchForm" onsubmit="submitCleaningDispatch(event)">
                <input type="hidden" name="action" value="dispatch_cleaner">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                <!-- SECTION: Location -->
                <h4 style="margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.5rem; color:var(--primary);"><i class="fas fa-map-marker-alt"></i> Cleaning Location & Scope</h4>
                <div class="row" style="margin-bottom:1.5rem;">
                    <div class="col-md-12 adm-form-group">
                        <label>Location Type *</label>
                        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:5px;">
                            <?php foreach(['Ward', 'Room', 'Common Area', 'Theatre', 'Laboratory', 'Pharmacy', 'Kitchen', 'Laundry', 'Outdoor', 'Entire Floor'] as $loc): ?>
                                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="radio" name="location_type" value="<?php echo $loc; ?>" required> <?php echo $loc; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Ward / General Area *</label>
                        <select name="ward_area" id="ward_area" class="adm-search-input" required>
                            <option value="">Select Target Area</option>
                            <?php foreach($wards as $w): ?>
                                <option value="<?php echo htmlspecialchars($w['ward_name']); ?>"><?php echo htmlspecialchars($w['ward_name']); ?></option>
                            <?php endforeach; ?>
                            <option value="Hallways">Hallways</option>
                            <option value="Exterior">Exterior</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Specific Room / Bed / Zone</label>
                        <input type="text" name="specific_room" class="adm-search-input" placeholder="e.g. ICU Bed 4, or Male Restroom">
                    </div>
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Floor / Building / Wing</label>
                        <select name="floor_building" class="adm-search-input">
                            <option value="">N/A</option>
                            <option value="Ground Floor">Ground Floor</option>
                            <option value="First Floor">First Floor</option>
                            <option value="Second Floor">Second Floor</option>
                            <option value="Basement">Basement</option>
                            <option value="East Wing">East Wing</option>
                            <option value="West Wing">West Wing</option>
                        </select>
                    </div>
                </div>

                <!-- SECTION: Hazard -->
                <h4 style="margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.5rem; color:var(--primary);"><i class="fas fa-shield-virus"></i> Hazard & Contamination Protocol</h4>
                <div class="row" style="margin-bottom:1.5rem; padding: 10px; border-radius: 8px; background:var(--surface-2);" id="hazardPanelBox">
                    <div class="col-md-6 adm-form-group">
                        <label>Cleaning Type *</label>
                        <select name="cleaning_type" class="adm-search-input" required>
                            <option value="Routine Clean">Routine Clean</option>
                            <option value="Deep Clean">Deep Clean</option>
                            <option value="Biohazard Clean">Biohazard Clean</option>
                            <option value="Post-Discharge Clean">Post-Discharge Clean</option>
                            <option value="Emergency Sanitization">Emergency Sanitization</option>
                            <option value="Pre-Theatre Clean">Pre-Theatre Clean</option>
                            <option value="Infection Control Clean">Infection Control Clean</option>
                        </select>
                    </div>
                    <div class="col-md-6 adm-form-group">
                        <label>Contamination Level *</label>
                        <select name="contamination_level" class="adm-search-input" required onchange="this.style.backgroundColor = this.options[this.selectedIndex].style.backgroundColor; this.style.color = '#fff'; if(this.value==='None'){ this.style.color='inherit'; this.style.backgroundColor=''; }">
                            <option value="None" style="background:#e2e8f0; color:#000;">None</option>
                            <option value="Low" style="background:#27ae60;">Low Risk</option>
                            <option value="Medium" style="background:#f39c12;">Medium Risk</option>
                            <option value="High" style="background:#e67e22;">High Risk</option>
                            <option value="Biohazard" style="background:#c0392b;">BIOHAZARD</option>
                        </select>
                    </div>
                    <div class="col-md-12 adm-form-group">
                        <label style="color:var(--danger); font-weight:bold;">Special Hazard Flags (Select all that apply)</label>
                        <div style="display:flex; flex-wrap:wrap; gap:15px; margin-top:5px;" id="hazardCheckboxes">
                            <?php foreach(['Bloodborne Pathogen', 'Chemical Spill', 'Infectious Disease', 'Sharps / Needles', 'Radiation'] as $row): ?>
                                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="checkbox" name="hazard_flags[]" value="<?php echo $row; ?>"> <?php echo $row; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- SECTION: Scheduling -->
                <h4 style="margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.5rem; color:var(--primary);"><i class="fas fa-clock"></i> Scheduling</h4>
                <div class="row" style="margin-bottom:1.5rem;">
                    <div class="col-md-6 adm-form-group">
                        <label>Dispatch Type *</label>
                        <div style="display:flex; gap:15px; margin-top:5px;">
                            <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="radio" name="dispatch_type" value="immediate" checked onchange="toggleScheduleOptions()"> Immediate Dispatch</label>
                            <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="radio" name="dispatch_type" value="scheduled" onchange="toggleScheduleOptions()"> Scheduled Dispatch</label>
                        </div>
                    </div>
                    <div class="col-md-6 adm-form-group">
                        <label>Estimated Duration (Minutes) *</label>
                        <input type="number" name="estimated_duration" class="adm-search-input" value="30" min="5" required>
                    </div>
                    <div class="col-md-6 adm-form-group" id="schedDateBox" style="display:none;">
                        <label>Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="adm-search-input" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-6 adm-form-group" id="schedTimeBox" style="display:none;">
                        <label>Scheduled Time</label>
                        <input type="time" name="scheduled_time" class="adm-search-input" value="<?php echo date('H:i'); ?>">
                    </div>
                    <div class="col-md-12 adm-form-group">
                        <label>Recurrence (Optional)</label>
                        <select name="recurrence_pattern" class="adm-search-input">
                            <option value="">One-Time Only</option>
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Mon-Fri">Weekdays (Mon-Fri)</option>
                        </select>
                    </div>
                </div>

                <!-- SECTION: Assignment -->
                <h4 style="margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.5rem; color:var(--primary);"><i class="fas fa-user-tag"></i> Cleaner Assignment</h4>
                <div class="row" style="margin-bottom:1.5rem;">
                    <div class="col-md-6 adm-form-group">
                        <label>Assign To (Primary Cleaner) *</label>
                        <select name="assigned_cleaner_id" class="adm-search-input" required>
                            <option value="">-- Choose Staff --</option>
                            <?php foreach ($cleaners as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (Cleaner)</option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($cleaners)): ?><small style="color:var(--danger); font-weight:bold;">No cleaners currently available. Dispatch will log as unassigned.</small><?php endif; ?>
                    </div>
                    <div class="col-md-6 adm-form-group">
                        <label>Assign Backup (Optional)</label>
                        <select name="backup_cleaner_id" class="adm-search-input">
                            <option value="">-- Standby Staff --</option>
                            <?php foreach ($cleaners as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 adm-form-group">
                        <label>Priority Level *</label>
                        <select name="priority" class="adm-search-input" required onchange="this.style.backgroundColor = this.options[this.selectedIndex].style.backgroundColor; this.style.color = '#fff';">
                            <option value="Routine" style="background:#27ae60;">Routine</option>
                            <option value="Urgent" style="background:#f39c12;">Urgent</option>
                            <option value="Emergency" style="background:#c0392b;">Emergency</option>
                        </select>
                    </div>
                    <div class="col-md-6 adm-form-group">
                        <label>Required PPE</label>
                        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:5px;">
                            <?php foreach(['Gloves', 'Mask', 'Apron', 'Full PPE', 'Eye Protection', 'Hazmat Suit'] as $row): ?>
                                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="checkbox" name="required_ppe[]" value="<?php echo $row; ?>"> <?php echo $row; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="adm-form-group" style="margin-bottom:1.5rem;">
                    <label>Special Instructions</label>
                    <textarea name="special_instructions" class="adm-search-input" rows="2" placeholder="Specific guidance for the cleaner..."></textarea>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:1rem; border-top:1px solid var(--border); padding-top:1.5rem;">
                    <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('cleanModal').classList.remove('active')">Cancel</button>
                    <button type="button" id="submitDispatchBtn" class="adm-btn adm-btn-primary" onclick="validateAndOpenConfirm()"><i class="fas fa-paper-plane"></i> Dispatch Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Biohazard Override -->
<div class="adm-modal" id="biohazardConfirmModal">
    <div class="adm-modal-content" style="max-width: 500px; border-left: 5px solid var(--danger);">
        <div class="adm-modal-header" style="color:var(--danger);">
            <h3><i class="fas fa-exclamation-triangle"></i> Biohazard Confirmation</h3>
            <button class="adm-modal-close" type="button" onclick="document.getElementById('biohazardConfirmModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <p><strong>Warning:</strong> This dispatch involves biohazardous materials or infectious hazards. This will automatically trigger alerts to the entire infection control team and shift supervisors.</p>
            <p style="margin-top:1rem; font-size:1.1rem;">Ensure the cleaner has been issued appropriate PPE. Do you want to proceed?</p>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:2rem;">
                <button type="button" class="adm-btn" onclick="document.getElementById('biohazardConfirmModal').classList.remove('active')">Cancel</button>
                <button type="button" class="adm-btn" style="background:var(--danger);color:#fff;" onclick="executeDispatchPost()"><i class="fas fa-check"></i> Acknowledge & Dispatch</button>
            </div>
        </div>
    </div>
</div>
<script>
function toggleScheduleOptions() {
    const isImmediate = document.querySelector('input[name="dispatch_type"]:checked').value === 'immediate';
    document.getElementById('schedDateBox').style.display = isImmediate ? 'none' : 'block';
    document.getElementById('schedTimeBox').style.display = isImmediate ? 'none' : 'block';
}

function checkBiohazard() {
    let checked = false;
    document.querySelectorAll('#hazardCheckboxes input[type="checkbox"]:checked').forEach(cb => checked = true);
    
    const clvl = document.querySelector('select[name="contamination_level"]').value;
    const ctype = document.querySelector('select[name="cleaning_type"]').value;
    
    if (checked || clvl === 'Biohazard' || ctype === 'Biohazard Clean') return true;
    return false;
}

// Visual cue mapping for hazards
document.querySelectorAll('#hazardCheckboxes input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', () => {
        const box = document.getElementById('hazardPanelBox');
        if (checkBiohazard()) {
            box.style.border = '2px solid var(--danger)';
            box.style.backgroundColor = '#fff1f0';
        } else {
            box.style.border = '';
            box.style.backgroundColor = 'var(--surface-2)';
        }
    });
});

function validateAndOpenConfirm() {
    const form = document.getElementById('cleaningDispatchForm');
    if(!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    if (checkBiohazard()) {
        document.getElementById('biohazardConfirmModal').classList.add('active');
    } else {
        executeDispatchPost();
    }
}

function executeDispatchPost() {
    document.getElementById('biohazardConfirmModal').classList.remove('active');
    
    const form = document.getElementById('cleaningDispatchForm');
    const fd = new FormData(form);
    
    const btn = document.getElementById('submitDispatchBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dispatching...';
    btn.disabled = true;

    fetch('admin_cleaning_actions.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r=>r.json())
    .then(res => {
        if(res.success) {
            alert('Success: ' + res.message);
            window.location.reload();
        } else {
            alert('Error: ' + (res.message || 'Validation failed.'));
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Dispatch Task';
            btn.disabled = false;
        }
    })
    .catch(err => {
        alert('Exception: ' + err);
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Dispatch Task';
        btn.disabled = false;
    });
}

function submitCleaningDispatch(e) {
    e.preventDefault();
}

const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
</body>
</html>