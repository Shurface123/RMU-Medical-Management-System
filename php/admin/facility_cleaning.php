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

<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">
<style>
.staff-hero{display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;background:linear-gradient(135deg,#059669,#064e3b);border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap;position:relative;overflow:hidden;}
.staff-hero-avatar{width:72px;height:72px;border-radius:50%;border:3px solid rgba(255,255,255,.35);background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0;z-index:2;}
.staff-hero-info{z-index:2;}.staff-hero-info h2{font-size:2rem;font-weight:700;margin:0;}.staff-hero-info p{font-size:1.3rem;margin:.3rem 0 0;opacity:.85;}
.hero-bg-icon{position:absolute;right:-20px;bottom:-40px;font-size:15rem;opacity:.07;transform:rotate(-15deg);z-index:1;}
.stf-table{width:100%;border-collapse:collapse;font-size:1.15rem;}
.stf-table th{background:var(--surface-2);color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left;}
.stf-table td{padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle;}
.stf-table tr:hover td{background:var(--surface-2);}
.contamination-card { background:rgba(231,76,60,0.08); border:1px solid rgba(231,76,60,0.2); border-radius:12px; padding:1.5rem; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center; backdrop-filter:blur(5px); }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-broom"></i> Hygiene & Infection Control</span>
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
            <i class="fas fa-virus-slash hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-hand-sparkles"></i></div>
            <div class="staff-hero-info">
                <h2>Infection Control Command</h2>
                <p>Manage cleaning tasks, isolation wards, and biological contamination reports.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <button class="btn btn-primary" onclick="document.getElementById('cleanModal').classList.add('active')" style="background:#fff; color:#059669; border:none; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                    <i class="fas fa-plus"></i> Dispatch Cleaner
                </button>
            </div>
        </div>

        <div class="adm-stats-grid">
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #ef4444, #dc2626);"><i class="fas fa-biohazard"></i></div>
                <div class="adm-stat-label">Active Alerts</div>
                <div class="adm-stat-value" style="color:#ef4444;"><?php echo count($alerts); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-exclamation-triangle"></i> Requires attention</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #3b82f6, #2563eb);"><i class="fas fa-broom"></i></div>
                <div class="adm-stat-label">Active Tasks</div>
                <div class="adm-stat-value"><?php echo count(array_filter($schedules, fn($s) => in_array($s['status'], ['Dispatched', 'In Progress']))); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-sync-alt"></i> Cleaners in field</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-check-double"></i></div>
                <div class="adm-stat-label">Completed Today</div>
                <div class="adm-stat-value"><?php echo count(array_filter($schedules, fn($s) => in_array($s['status'], ['Completed', 'Inspected']))); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-history"></i> Last 24 hours</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #6366f1, #4f46e5);"><i class="fas fa-shield-virus"></i></div>
                <div class="adm-stat-label">Hygiene Rating</div>
                <div class="adm-stat-value">98<span style="font-size:1.5rem; opacity:0.6;">%</span></div>
                <div class="adm-stat-footer"><i class="fas fa-chart-line"></i> Operational health</div>
            </div>
        </div>

        <?php if (!empty($alerts)): ?>
        <div class="adm-card shadow-sm" style="border:1px solid rgba(231,76,60,0.3); background:var(--surface); margin-bottom:2.5rem; border-radius:20px; overflow:hidden;">
            <div class="adm-card-header" style="background:rgba(231,76,60,0.05); padding:1.5rem 2.5rem; border-bottom:1px solid rgba(231,76,60,0.2);">
                <h3 style="color:var(--danger); font-size:1.4rem;"><i class="fas fa-biohazard"></i> Active Contamination Alerts</h3>
            </div>
            <div class="adm-card-body" style="padding:1.5rem 2.5rem;">
                <?php foreach ($alerts as $al): ?>
                <div class="contamination-card">
                    <div>
                        <strong style="color:var(--danger); font-size:1.2rem; display:block; margin-bottom:0.4rem;"><?php echo htmlspecialchars($al['location'] ?? ''); ?>: <?php echo ucfirst($al['contamination_type']); ?></strong>
                        <div style="font-size:1rem; color:var(--text-secondary);"><?php echo htmlspecialchars($al['description'] ?? ''); ?></div>
                        <div style="font-size:0.85rem; margin-top:0.8rem; color:var(--text-muted);"><i class="far fa-user"></i> Reported by <?php echo htmlspecialchars($al['reporter_name'] ?? ''); ?> • <i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($al['reported_at'])); ?></div>
                    </div>
                    <div style="text-align:right;">
                        <span class="adm-badge" style="background:var(--danger-light); color:var(--danger); font-weight:800; padding:0.6rem 1.2rem; font-size:0.9rem; animation:pulse 2s infinite;"><i class="fas fa-exclamation-triangle"></i> <?php echo strtoupper($al['severity']); ?> SEVERITY</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="adm-alert adm-alert-success" style="margin-bottom:2.5rem; border-radius:12px;"><i class="fas fa-check-circle"></i> Cleaning task dispatched successfully.</div>
        <?php endif; ?>

        <div class="adm-card shadow-sm" style="border-radius:20px; border:1px solid var(--border); overflow:hidden;">
            <div class="adm-card-header" style="padding: 1.8rem 2.5rem; background:var(--surface-2); border-bottom:1px solid var(--border);">
                <h3><i class="fas fa-clipboard-check" style="color:var(--primary);"></i> Cleaning & Sanitization Log</h3>
            </div>
            <div class="adm-table-wrap" style="padding:0;">
                <table class="stf-table">
                    <thead>
                        <tr>
                            <th>Task Issued</th>
                            <th>Location / Zone</th>
                            <th>Type & Protocol</th>
                            <th>Staff Assignment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schedules)): ?>
                            <tr><td colspan="5" style="text-align:center;padding:5rem;color:var(--text-muted);">No active cleaning schedules.</td></tr>
                        <?php else:
                            foreach ($schedules as $cs):
                                $sc = ($cs['status'] === 'Completed' || $cs['status'] === 'Inspected') ? 'success' : ($cs['status'] === 'In Progress' ? 'warning' : 'info');
                                $is_overdue = ($cs['status'] === 'Dispatched' || $cs['status'] === 'Scheduled') && strtotime($cs['scheduled_time']) < (time() - 3600);
                                $is_hazard = $cs['cleaning_type'] === 'Biohazard Clean' || ($cs['contamination_level'] ?? '') === 'Biohazard';
                                $ppe = json_decode($cs['required_ppe'], true) ?? [];
                        ?>
                        <tr <?php if($is_overdue) echo 'style="background-color: var(--warning-light);"'; ?> <?php if($is_hazard) echo 'style="background-color: rgba(231,76,60,0.05);"'; ?>>
                            <td style="white-space:nowrap;">
                                <strong style="font-size:1.1rem; color:var(--text-primary);"><?php echo date('d M, g:i A', strtotime($cs['scheduled_time'])); ?></strong>
                                <?php if($cs['recurrence_pattern']): ?><br><span class="adm-badge" style="background:var(--primary-light); color:var(--primary); font-size:0.8rem; margin-top:4px;"><i class="fas fa-redo-alt"></i> <?php echo htmlspecialchars($cs['recurrence_pattern']); ?></span><?php endif; ?>
                            </td>
                            <td>
                                <strong style="font-size:1.1rem; color:var(--text-primary);"><?php echo htmlspecialchars($cs['ward_area'] ?? 'General Area'); ?></strong>
                                <?php if($cs['specific_room']) echo " <span style='color:var(--text-muted);'>• " . htmlspecialchars($cs['specific_room']) . "</span>"; ?>
                                <div style="font-size:.9rem;color:var(--text-muted); margin-top:0.3rem;"><i class="fas fa-building"></i> <?php echo htmlspecialchars($cs['location_type']); ?> <?php echo $cs['floor_building'] ? "({$cs['floor_building']})" : ''; ?></div>
                            </td>
                            <td>
                                <strong style="font-size:1rem;"><?php echo htmlspecialchars($cs['cleaning_type']); ?></strong>
                                <?php 
                                $lvl = strtolower($cs['contamination_level'] ?? 'none');
                                if($lvl === 'biohazard') echo '<br><span class="adm-badge" style="background:var(--danger-light); color:var(--danger); font-size:0.75rem; font-weight:800; margin-top:4px;"><i class="fas fa-biohazard"></i> BIOHAZARD</span>';
                                elseif($lvl === 'high') echo '<br><span class="adm-badge" style="background:var(--warning-light); color:var(--warning); font-size:0.75rem; font-weight:800; margin-top:4px;">High Risk</span>';
                                ?>
                            </td>
                            <td>
                                <strong style="font-size:1.1rem; color:var(--text-primary);"><?php echo htmlspecialchars($cs['primary_cleaner'] ?? 'Unassigned'); ?></strong>
                                <?php if($cs['backup_cleaner']): ?><div style="font-size:0.85rem; color:var(--text-muted); margin-top:0.2rem;">Backup: <?php echo htmlspecialchars($cs['backup_cleaner']); ?></div><?php endif; ?>
                            </td>
                            <td>
                                <span class="adm-badge" style="background:var(--<?php echo $sc; ?>-light); color:var(--<?php echo $sc; ?>); font-weight:700;"><?php echo ucfirst($cs['status']); ?></span>
                                <?php if($is_overdue): ?><br><span class="adm-badge" style="background:var(--danger-light); color:var(--danger); margin-top:6px; font-size:0.75rem; font-weight:800;">OVERDUE</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Dispatch Modal -->
<div class="adm-modal" id="cleanModal">
    <div class="adm-modal-content" style="max-width: 1000px; transform: translateY(-20px); transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);">
        <div class="adm-modal-header" style="background: var(--primary); color: #fff; padding: 1.5rem 2rem; border-radius: 16px 16px 0 0;">
            <div style="display:flex; align-items:center; gap:1rem;">
                <div style="width:48px; height:48px; background:rgba(255,255,255,0.2); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                    <i class="fas fa-broom"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.5rem;">Dispatch Command Hub</h3>
                    <p style="margin:0; opacity:0.8; font-size:0.85rem;">Initiate infection control and cleaning protocols.</p>
                </div>
            </div>
            <button class="btn btn-primary adm-modal-close" onclick="document.getElementById('cleanModal').classList.remove('active')" style="color:#fff; opacity:0.7; transition:opacity 0.3s;"><span class="btn-text">
                <i class="fas fa-times"></i>
            </span></button>
        </div>
        <div class="adm-modal-body" style="padding: 2rem;">
            <form id="cleaningDispatchForm" onsubmit="submitCleaningDispatch(event)">
                <input type="hidden" name="action" value="dispatch_cleaner">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;">
                    <!-- LEFT COLUMN: Logistics -->
                    <div style="display:flex; flex-direction:column; gap:2rem;">
                        <section>
                            <h4 class="adm-section-title"><i class="fas fa-map-marker-alt"></i> Deployment Location</h4>
                            <div class="adm-form-grid">
                                <div class="adm-form-group" style="grid-column: span 2;">
                                    <label>Facility Zone Type *</label>
                                    <div class="adm-radio-group" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; padding: 0.5rem; background: var(--bg-surface); border: 1px solid var(--border);">
                                        <?php foreach(['Ward', 'Room', 'Common Area', 'Theatre', 'Laboratory', 'Pharmacy', 'Kitchen', 'Laundry', 'Outdoor'] as $loc): ?>
                                            <label class="adm-radio-label" style="padding: 0.5rem; border-radius: 6px; transition: background 0.3s; border: 1px solid transparent;">
                                                <input type="radio" name="location_type" value="<?php echo $loc; ?>" required> <?php echo $loc; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="adm-form-group">
                                    <label><i class="fas fa-hospital-user"></i> Ward / Area *</label>
                                    <select name="ward_area" id="ward_area" class="adm-form-input" required style="height: 48px; border-radius: 10px;">
                                        <option value="">Select Target Area</option>
                                        <?php foreach($wards as $w): ?>
                                            <option value="<?php echo htmlspecialchars($w['ward_name']); ?>"><?php echo htmlspecialchars($w['ward_name']); ?></option>
                                        <?php endforeach; ?>
                                        <option value="Hallways">General Hallways</option>
                                        <option value="Exterior">Exterior Facility</option>
                                    </select>
                                </div>
                                <div class="adm-form-group">
                                    <label><i class="fas fa-door-open"></i> Specific Room / Bin</label>
                                    <input type="text" name="specific_room" class="adm-form-input" placeholder="e.g. ICU Bed 4" style="height: 48px; border-radius: 10px;">
                                </div>
                                <div class="adm-form-group" style="grid-column: span 2;">
                                    <label><i class="fas fa-layer-group"></i> Floor & Building Assignment</label>
                                    <select name="floor_building" class="adm-form-input" style="height: 48px; border-radius: 10px;">
                                        <option value="">Not Applicable</option>
                                        <optgroup label="Main Block">
                                            <option value="Ground Floor">Ground Floor</option>
                                            <option value="First Floor">First Floor</option>
                                            <option value="Second Floor">Second Floor</option>
                                            <option value="Basement">Basement</option>
                                        </optgroup>
                                        <optgroup label="Wings">
                                            <option value="East Wing">East Wing</option>
                                            <option value="West Wing">West Wing</option>
                                            <option value="Annex">Annex Building</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h4 class="adm-section-title"><i class="fas fa-user-clock"></i> Assignment Matrix</h4>
                            <div class="adm-form-grid">
                                <div class="adm-form-group">
                                    <label><i class="fas fa-user-check"></i> Primary Responder *</label>
                                    <select name="assigned_cleaner_id" class="adm-form-input" required style="height: 48px; border-radius: 10px;">
                                        <option value="">-- Choose Staff --</option>
                                        <?php foreach ($cleaners as $c): ?>
                                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group">
                                    <label><i class="fas fa-user-shield"></i> Backup Responder</label>
                                    <select name="backup_cleaner_id" class="adm-form-input" style="height: 48px; border-radius: 10px;">
                                        <option value="">-- Standby Staff --</option>
                                        <?php foreach ($cleaners as $c): ?>
                                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group">
                                    <label><i class="fas fa-tachometer-alt"></i> Execution Priority *</label>
                                    <select name="priority" class="adm-form-input" required style="height: 48px; border-radius: 10px;">
                                        <option value="Routine">🟢 Routine (Low)</option>
                                        <option value="Urgent">🟠 Urgent (Medium)</option>
                                        <option value="Emergency">🔴 Emergency (High)</option>
                                    </select>
                                </div>
                                <div class="adm-form-group">
                                    <label><i class="fas fa-history"></i> Est. Duration (Min)</label>
                                    <input type="number" name="estimated_duration" class="adm-form-input" value="30" min="5" style="height: 48px; border-radius: 10px;">
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- RIGHT COLUMN: Protocols & Safety -->
                    <div style="display:flex; flex-direction:column; gap:2rem;">
                        <section id="hazardPanelBox" style="padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); background: var(--bg-surface); position: relative; overflow: hidden;">
                            <h4 class="adm-section-title" style="margin-top:0;"><i class="fas fa-biohazard"></i> Risk Assessment</h4>
                            
                            <div class="adm-form-group" style="margin-bottom:1.2rem;">
                                <label>Protocol Type *</label>
                                <select name="cleaning_type" class="adm-form-input" required style="height: 42px; border-radius: 8px;">
                                    <option value="Routine Clean">Routine Maintenance</option>
                                    <option value="Deep Clean">Full Deep Clean</option>
                                    <option value="Biohazard Clean">Biohazard Protocol</option>
                                    <option value="Post-Discharge Clean">Post-Discharge Sanitize</option>
                                    <option value="Infection Control Clean">Infection Control</option>
                                </select>
                            </div>

                            <div class="adm-form-group" style="margin-bottom:1.2rem;">
                                <label>Contamination Level *</label>
                                <select name="contamination_level" class="adm-form-input" required onchange="handleContaminationChange(this)" style="height: 42px; border-radius: 8px;">
                                    <option value="None">Safe / None</option>
                                    <option value="Low" data-color="#27ae60">L1 - Low Risk</option>
                                    <option value="Medium" data-color="#f39c12">L2 - Medium Risk</option>
                                    <option value="High" data-color="#e67e22">L3 - High Risk</option>
                                    <option value="Biohazard" data-color="#c0392b">L4 - BIOHAZARD</option>
                                </select>
                            </div>

                            <div class="adm-form-group">
                                <label style="color:var(--danger); font-weight:bold; font-size: 0.8rem; text-transform:uppercase;">Hazard Flags</label>
                                <div class="adm-checkbox-group" id="hazardCheckboxes" style="display: grid; grid-template-columns: 1fr; gap: 0.4rem; border: none; background: transparent; padding: 0;">
                                    <?php foreach(['Bloodborne Pathogen', 'Chemical Spill', 'Infectious Disease', 'Sharps / Needles', 'Radiation'] as $row): ?>
                                        <label class="adm-checkbox-label" style="font-size: 0.85rem; padding: 0.4rem; border: 1px solid var(--border); border-radius: 6px;">
                                            <input type="checkbox" name="hazard_flags[]" value="<?php echo $row; ?>"> <?php echo $row; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h4 class="adm-section-title"><i class="fas fa-shield-alt"></i> Mandatory PPE</h4>
                            <div class="adm-checkbox-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <?php foreach(['Gloves', 'Mask', 'Apron', 'Full Suit', 'Eye Pro', 'Respirator'] as $row): ?>
                                    <label class="adm-checkbox-label" style="font-size: 0.85rem;">
                                        <input type="checkbox" name="required_ppe[]" value="<?php echo $row; ?>"> <?php echo $row; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section>
                            <h4 class="adm-section-title"><i class="fas fa-calendar-alt"></i> Timing Control</h4>
                            <div class="adm-radio-group" style="margin-bottom:1rem;">
                                <label class="adm-radio-label"><input type="radio" name="dispatch_type" value="immediate" checked onchange="toggleScheduleOptions()"> Immediate</label>
                                <label class="adm-radio-label"><input type="radio" name="dispatch_type" value="scheduled" onchange="toggleScheduleOptions()"> Scheduled</label>
                            </div>
                            <div id="schedDateBox" style="display:none; margin-bottom:0.5rem;">
                                <input type="date" name="scheduled_date" class="adm-form-input" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div id="schedTimeBox" style="display:none;">
                                <input type="time" name="scheduled_time" class="adm-form-input" value="<?php echo date('H:i'); ?>">
                            </div>
                        </section>
                    </div>
                </div>

                <div class="adm-form-group" style="margin-top:2rem;">
                    <label><i class="fas fa-comment-medical"></i> Special Dispatch Notes</label>
                    <textarea name="special_instructions" class="adm-form-input" rows="3" placeholder="Provide specialized guidance for this deployment..." style="border-radius: 12px;"></textarea>
                </div>
                
                <div class="adm-modal-footer" style="padding-top: 2rem; border-top: 1px solid var(--border); margin-top: 2rem; gap: 1rem;">
                    <button type="button" class="btn btn-primary btn" onclick="document.getElementById('cleanModal').classList.remove('active')" style="padding: 0.8rem 2rem;"><span class="btn-text">Dismiss</span></button>
                    <button type="button" id="submitDispatchBtn" class="btn btn-primary" onclick="validateAndOpenConfirm()" style="padding: 0.8rem 3rem; font-weight: 600;"><span class="btn-text">
                        <i class="fas fa-paper-plane"></i> Execute Dispatch
                    </span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Biohazard Override -->
<div class="adm-modal" id="biohazardConfirmModal" style="z-index: 1100;">
    <div class="adm-modal-content" style="max-width: 550px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(231, 76, 60, 0.5);">
        <div class="adm-modal-header" style="background: var(--danger); color: #fff; padding: 1.5rem 2rem; border-radius: 20px 20px 0 0;">
            <div style="display:flex; align-items:center; gap:1rem;">
                <i class="fas fa-radiation-alt fa-2x fa-spin" style="animation-duration: 4s;"></i>
                <div>
                    <h3 style="margin:0;">L4 Biohazard Protocol</h3>
                    <p style="margin:0; opacity:0.8; font-size:0.85rem;">Emergency authorization required.</p>
                </div>
            </div>
        </div>
        <div class="adm-modal-body" style="padding: 2rem;">
            <div style="background: rgba(231,76,60,0.1); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; display:flex; gap:1rem; align-items:center; border: 1px solid rgba(231,76,60,0.2);">
                <i class="fas fa-info-circle fa-2x" style="color:var(--danger);"></i>
                <p style="margin:0; font-size:0.95rem; color: #721c24;">Critical: This action will notify the **Infection Control Lead** and **Chief Medical Officer** immediately.</p>
            </div>
            <p style="font-size:1.1rem; line-height:1.6;">Are you certain you want to initiate this high-risk dispatch?</p>
            <div class="adm-modal-footer" style="padding: 0; border: none; margin-top: 2rem;">
                <button type="button" class="btn btn-primary btn" onclick="document.getElementById('biohazardConfirmModal').classList.remove('active')" style="flex:1;"><span class="btn-text">Abort</span></button>
                <button type="button" class="btn-icon btn btn-danger" onclick="executeDispatchPost()" style="flex:2; padding: 1rem;"><span class="btn-text">Confirm & Execute</span></button>
            </div>
        </div>
    </div>
</div>

<style>
.adm-section-title { margin-bottom: 1.2rem; border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; color: var(--primary); font-size: 1.1rem; font-weight: 600; font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 0.8rem; }
.adm-modal-content { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: none; border-radius: 20px; }
.adm-radio-label:hover { background: var(--bg-body) !important; border-color: var(--primary) !important; }
.adm-radio-label input:checked + label { color: var(--primary); }
.adm-form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1); }
@media (max-width: 900px) { .adm-modal-body > form > div { grid-template-columns: 1fr !important; } }
</style>

<script>
function toggleScheduleOptions() {
    const isImmediate = document.querySelector('input[name="dispatch_type"]:checked').value === 'immediate';
    document.getElementById('schedDateBox').style.display = isImmediate ? 'none' : 'block';
    document.getElementById('schedTimeBox').style.display = isImmediate ? 'none' : 'block';
}

function handleContaminationChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    if(opt.dataset.color) {
        sel.style.backgroundColor = opt.dataset.color;
        sel.style.color = '#fff';
    } else {
        sel.style.backgroundColor = '';
        sel.style.color = '';
    }
    updateHazardUI();
}

function checkBiohazard() {
    let checked = false;
    document.querySelectorAll('#hazardCheckboxes input[type="checkbox"]:checked').forEach(cb => checked = true);
    const clvl = document.querySelector('select[name="contamination_level"]').value;
    const ctype = document.querySelector('select[name="cleaning_type"]').value;
    return (checked || clvl === 'Biohazard' || ctype === 'Biohazard Clean');
}

function updateHazardUI() {
    const box = document.getElementById('hazardPanelBox');
    if (checkBiohazard()) {
        box.style.border = '2px solid var(--danger)';
        box.style.backgroundColor = 'rgba(231,76,60,0.05)';
    } else {
        box.style.border = '1px solid var(--border)';
        box.style.backgroundColor = '';
    }
}

document.querySelectorAll('#hazardCheckboxes input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', updateHazardUI);
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

function submitCleaningDispatch(e) { e.preventDefault(); }

const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
document.getElementById('menuToggle').onclick = () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); };
overlay.onclick = () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); };

document.getElementById('themeToggle').onclick = () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    document.getElementById('themeIcon').className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
};
</script>
</body>
</html>