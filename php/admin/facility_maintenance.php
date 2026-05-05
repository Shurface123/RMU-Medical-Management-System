<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'maintenance';
$page_title = 'Facility Maintenance';
include '../includes/_sidebar.php';

// POST handler removed. Now handled via admin_maintenance_actions.php

// Fetch available Maintenance Staff
$maint_staff = [];
$qs = mysqli_query($conn, "SELECT s.id, u.name, s.status FROM staff s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1 AND u.user_role = 'maintenance' ORDER BY u.name");
if ($qs) while($r = mysqli_fetch_assoc($qs)) $maint_staff[] = $r;

// Fetch Locations/Departments
$locations = [];
$ql = mysqli_query($conn, "SELECT name FROM staff_departments WHERE is_active = 1 ORDER BY name");
if ($ql) while($r = mysqli_fetch_assoc($ql)) $locations[] = $r['name'];

$requests = [];
$q = mysqli_query($conn, "
    SELECT m.*
    FROM maintenance_requests m 
    ORDER BY FIELD(m.status, 'reported', 'assigned', 'in progress', 'on hold', 'completed', 'cancelled'), m.reported_at DESC LIMIT 50
");
if ($q)
    while ($r = mysqli_fetch_assoc($q))
        $requests[] = $r;
?>

<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">
<style>
.staff-hero{display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;background:linear-gradient(135deg,#374151,#111827);border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap;position:relative;overflow:hidden;}
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
            <span class="adm-page-title"><i class="fas fa-tools"></i> Facility Maintenance</span>
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
            <i class="fas fa-tools hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-hard-hat"></i></div>
            <div class="staff-hero-info">
                <h2>Infrastructure Command</h2>
                <p>Log and track facility repair requirements for the maintenance team.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <button class="btn btn-primary" onclick="document.getElementById('maintModal').classList.add('active')" style="background:#fff; color:#111827; border:none; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                    <i class="fas fa-plus"></i> Report Issue
                </button>
            </div>
        </div>

        <div class="adm-stats-grid">
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #3b82f6, #2563eb);"><i class="fas fa-tasks"></i></div>
                <div class="adm-stat-label">Total Orders</div>
                <div class="adm-stat-value"><?php echo count($requests); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-history"></i> Recent logs</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-spinner"></i></div>
                <div class="adm-stat-label">In Progress</div>
                <div class="adm-stat-value"><?php echo count(array_filter($requests, fn($r) => in_array($r['status'], ['assigned', 'in progress']))); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-wrench"></i> Active repairs</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #ef4444, #dc2626);"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="adm-stat-label">High Priority</div>
                <div class="adm-stat-value" style="color:#ef4444;"><?php echo count(array_filter($requests, fn($r) => in_array($r['priority'], ['high', 'urgent']))); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-bolt"></i> Requires attention</div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-check-double"></i></div>
                <div class="adm-stat-label">Completed</div>
                <div class="adm-stat-value"><?php echo count(array_filter($requests, fn($r) => $r['status'] === 'completed')); ?></div>
                <div class="adm-stat-footer"><i class="fas fa-check-circle"></i> Resolved missions</div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="adm-alert adm-alert-success" style="margin-bottom:2.5rem; border-radius:12px;"><i class="fas fa-check-circle"></i> Maintenance request logged successfully. Sent to maintenance staff dashboard.</div>
        <?php endif; ?>

        <div class="adm-card shadow-sm" style="border-radius:20px; border:1px solid var(--border); overflow:hidden;">
            <div class="adm-card-header" style="padding: 1.8rem 2.5rem; background:var(--surface-2); border-bottom:1px solid var(--border);">
                <h3><i class="fas fa-clipboard-list" style="color:var(--primary);"></i> Active Work Orders</h3>
            </div>
            <div class="adm-table-wrap" style="padding:0;">
                <table class="stf-table">
                    <thead><tr><th>Date</th><th>Issue / Location</th><th>Priority</th><th>Reported By</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($requests)): ?><tr><td colspan="5" style="text-align:center;padding:5rem;color:var(--text-muted);">No maintenance requests found.</td></tr>
                        <?php else:
                            foreach ($requests as $req):
                                $pc = $req['priority'] === 'high' || $req['priority'] === 'urgent' ? 'danger' : ($req['priority'] === 'medium' ? 'warning' : 'success');
                                $sc = $req['status'] === 'completed' ? 'success' : ($req['status'] === 'in progress' || $req['status'] === 'assigned' ? 'info' : 'warning');
                        ?>
                        <tr>
                            <td style="white-space:nowrap;">
                                <strong style="font-size:1.1rem; color:var(--text-primary);"><?php echo date('d M Y', strtotime($req['reported_at'])); ?></strong>
                                <div style="font-size:.9rem;color:var(--text-muted);"><?php echo date('g:i A', strtotime($req['reported_at'])); ?></div>
                            </td>
                            <td>
                                <strong style="font-size:1.1rem; color:var(--text-primary);"><?php echo htmlspecialchars($req['equipment_or_area']); ?></strong>
                                <div style="font-size:.9rem;color:var(--text-muted); margin-top:0.3rem;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($req['location']); ?></div>
                                <div style="font-size:.85rem;color:var(--text-secondary);margin-top:0.5rem; max-width:400px; line-height:1.4;"><?php echo htmlspecialchars(substr($req['issue_description'], 0, 120)); ?>...</div>
                            </td>
                            <td><span class="adm-badge" style="background:var(--<?php echo $pc; ?>-light); color:var(--<?php echo $pc; ?>); font-weight:800; font-size:0.75rem;"><?php echo strtoupper($req['priority']); ?></span></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:0.6rem;">
                                    <div style="width:30px; height:30px; border-radius:50%; background:var(--surface-2); display:flex; align-items:center; justify-content:center; font-size:0.8rem; font-weight:600;"><?php echo strtoupper(substr($req['reported_by'] ?? 'A', 0, 1)); ?></div>
                                    <span style="font-weight:600;"><?php echo htmlspecialchars($req['reported_by'] ?? 'Admin'); ?></span>
                                </div>
                            </td>
                            <td><span class="adm-badge" style="background:var(--<?php echo $sc; ?>-light); color:var(--<?php echo $sc; ?>); font-weight:700;"><?php echo ucfirst($req['status']); ?></span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="adm-modal" id="maintModal">
    <div class="adm-modal-content" style="max-height:90vh;overflow-y:auto;max-width:800px;">
        <div class="adm-modal-header" style="background:var(--primary);color:#fff;">
            <h3 style="color:#fff;"><i class="fas fa-wrench"></i> Report Maintenance Issue</h3>
            <button class="btn btn-primary adm-modal-close" style="color:#fff;" onclick="document.getElementById('maintModal').classList.remove('active')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <div class="adm-modal-body">
            <form id="maintForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <h4 style="margin-top:0;margin-bottom:1rem;color:var(--text-main);font-size:1rem;border-bottom:1px solid #e5e7eb;padding-bottom:0.5rem;"><i class="fas fa-map-marker-alt"></i> 1. Location & Identification</h4>
                
                <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <div class="adm-form-group" style="flex:1;min-width:200px;margin:0;">
                        <label>Location / Area *</label>
                        <select name="location_select" class="adm-search-input" onchange="const trg=document.getElementById('locOverride'); if(this.value==='other'){trg.style.display='block';trg.required=true;}else{trg.style.display='none';trg.required=false;trg.value='';}" required>
                            <option value="">-- Select Location --</option>
                            <?php foreach($locations as $l): ?>
                                <option value="<?php echo htmlspecialchars($l); ?>"><?php echo htmlspecialchars($l); ?></option>
                            <?php endforeach; ?>
                            <option value="other">Other (Type Below)...</option>
                        </select>
                        <input type="text" id="locOverride" name="location_override" class="adm-search-input" style="display:none;margin-top:10px;" placeholder="Specify exact location...">
                    </div>
                    <div class="adm-form-group" style="flex:1;min-width:200px;margin:0;">
                        <label>Specific Room or Equipment *</label>
                        <input type="text" name="equipment_or_area" class="adm-search-input" required placeholder="e.g. Bed 4, Main Sink, Backup Generator">
                    </div>
                </div>

                <h4 style="margin-bottom:1rem;color:var(--text-main);font-size:1rem;border-bottom:1px solid #e5e7eb;padding-bottom:0.5rem;"><i class="fas fa-info-circle"></i> 2. Issue Details</h4>
                
                <div class="adm-form-group">
                    <label>Issue Title *</label>
                    <input type="text" name="title" class="adm-search-input" required placeholder="Brief summary of the problem">
                </div>

                <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <div class="adm-form-group" style="flex:1;min-width:200px;margin:0;">
                        <label>Issue Category *</label>
                        <select name="issue_category" class="adm-search-input" required>
                            <option value="electrical">Electrical</option>
                            <option value="plumbing">Plumbing</option>
                            <option value="structural">Structural</option>
                            <option value="equipment">Equipment Failure</option>
                            <option value="furniture">Furniture</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="adm-form-group" style="flex:1;min-width:200px;margin:0;">
                        <label>Severity / Priority *</label>
                        <select name="priority" class="adm-search-input" id="prioritySelect" required>
                            <option value="low" style="color:var(--success);">Low</option>
                            <option value="medium" style="color:#ca8a04;" selected>Medium</option>
                            <option value="high" style="color:#ea580c;">High</option>
                            <option value="urgent" style="color:var(--danger);font-weight:bold;">Urgent (Critical)</option>
                        </select>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label>Detailed Description *</label>
                    <textarea name="description" class="adm-search-input" rows="3" required placeholder="Describe the problem, when it started, and current impact..."></textarea>
                </div>
                
                <div class="adm-form-group">
                    <label>Photo Evidence / Documents (Optional, JPG/PNG/PDF)</label>
                    <input type="file" name="evidence[]" class="adm-search-input" accept="image/jpeg,image/png,application/pdf" multiple style="padding:10px;background:var(--bg-lite);border:2px dashed #cbd5e1;cursor:pointer;">
                </div>

                <h4 style="margin-bottom:1rem;color:var(--text-main);font-size:1rem;border-bottom:1px solid #e5e7eb;padding-bottom:0.5rem;"><i class="fas fa-user-hard-hat"></i> 3. Assignment</h4>
                
                <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
                    <div class="adm-form-group" style="flex:1;min-width:200px;margin:0;">
                        <label>Assign To (Optional)</label>
                        <select name="assigned_to" class="adm-search-input">
                            <option value="">-- Unassigned (General Queue) --</option>
                            <?php foreach($maint_staff as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?> [<?php echo htmlspecialchars($s['status']); ?>]</option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($maint_staff)): ?><span style="color:#dc2626;font-size:0.75rem;"><i class="fas fa-exclamation-triangle"></i> No maintenance staff found.</span><?php endif; ?>
                    </div>
                </div>

                <div class="adm-form-group" style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="is_emergency" name="is_emergency" value="1" onchange="const p = document.getElementById('prioritySelect'); if(this.checked){p.value='urgent';p.style.pointerEvents='none';}else{p.style.pointerEvents='auto';p.value='medium';}">
                    <label for="is_emergency" style="margin:0;cursor:pointer;color:var(--danger);font-weight:bold;">Mark as Emergency — notifies all staff immediately</label>
                </div>

                <div style="display:flex;gap:1rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1;font-size:1.1rem;padding:0.75rem;"><span class="btn-text"><i class="fas fa-paper-plane"></i> Submit Report</span></button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('maintModal').classList.remove('active')"><span class="btn-text">Cancel</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" style="position:fixed;bottom:20px;right:20px;z-index:9999;"></div>

<script>
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

function showToast(msg, isSuccess=true) {
    const t = document.createElement('div');
    t.style.cssText = `background:${isSuccess?'var(--success)':'var(--danger)'};color:#fff;padding:1rem 1.5rem;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);margin-top:10px;display:flex;align-items:center;gap:10px;animation:slideIn 0.3s ease-out forwards;`;
    t.innerHTML = `<i class="fas ${isSuccess?'fa-check-circle':'fa-exclamation-triangle'}"></i> ${msg}`;
    document.getElementById('toastContainer').appendChild(t);
    setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.remove(),300); }, 4000);
}

document.getElementById('maintForm').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const origText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    btn.disabled = true;

    const fd = new FormData(e.target);
    try {
        const res = await fetch('admin_maintenance_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            showToast(data.message, true);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(data.message, false);
            btn.innerHTML = origText;
            btn.disabled = false;
        }
    } catch(err) {
        showToast('Network error while processing file upload.', false);
        console.error(err);
        btn.innerHTML = origText;
        btn.disabled = false;
    }
});
</script>
<style>
@keyframes slideIn { from{transform:translateX(100%);opacity:0;} to{transform:translateX(0);opacity:1;} }
</style>
</body>
</html>