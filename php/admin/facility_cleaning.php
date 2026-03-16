<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'cleaning';
$page_title = 'Cleaning & Hygiene';
include '../includes/_sidebar.php';

// Quick dispatch action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_cleaning') {
    $title = trim($_POST['title']);
    $type = trim($_POST['type']); // rutine, biohazard, deep_clean
    $location = trim($_POST['location']);
    $desc = trim($_POST['notes']);
    $staff_id = (int)$_POST['staff_id'];

    // We insert straight into cleaning_logs as an assigned task
    $sql = "INSERT INTO cleaning_logs (staff_id, ward_room_area, cleaning_type, started_at, sanitation_status, notes) VALUES (?, ?, ?, NOW(), 'pending inspection', ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isss", $staff_id, $location, $type, $desc);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: facility_cleaning.php?success=1");
    exit();
}

// Fetch cleaners list
$cleaners = [];
$qc = mysqli_query($conn, "SELECT s.id, u.name FROM staff s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1 AND u.user_role = 'cleaner'");
if ($qc)
    while ($r = mysqli_fetch_assoc($qc))
        $cleaners[] = $r;

// Fetch logs
$logs = [];
$q = mysqli_query($conn, "
    SELECT cl.*, u.name as cleaner_name
    FROM cleaning_logs cl
    LEFT JOIN staff s ON cl.staff_id = s.id
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY FIELD(cl.sanitation_status, 'pending inspection', 'contaminated', 'clean'), cl.created_at DESC LIMIT 50
");
if ($q)
    while ($r = mysqli_fetch_assoc($q))
        $logs[] = $r;

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
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Task Started</th><th>Location / Ward</th><th>Type</th><th>Assigned Cleaner</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($logs)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;">No logs.</td></tr>
                        <?php
else:
    foreach ($logs as $cl):
        $sc = $cl['sanitation_status'] === 'clean' ? 'success' : ($cl['sanitation_status'] === 'contaminated' ? 'danger' : 'warning');
        $tc = $cl['cleaning_type'] === 'biohazard' ? 'danger' : ($cl['cleaning_type'] === 'deep_clean' ? 'primary' : 'secondary');
?>
                        <tr>
                            <td style="white-space:nowrap;"><?php echo date('d M Y, g:i A', strtotime($cl['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($cl['ward_room_area']); ?></strong>
                                <?php if ($cl['notes'])
            echo '<div style="font-size:.75rem;color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' . htmlspecialchars($cl['notes']) . '">' . htmlspecialchars($cl['notes']) . '</div>'; ?>
                            </td>
                            <td><span class="adm-badge adm-badge-<?php echo $tc; ?>"><?php echo ucfirst(str_replace('_', ' ', $cl['cleaning_type'])); ?></span></td>
                            <td><?php echo htmlspecialchars($cl['cleaner_name'] ?? 'Unassigned'); ?></td>
                            <td><span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo ucfirst($cl['sanitation_status']); ?></span></td>
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
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3><i class="fas fa-broom"></i> Dispatch Cleaner</h3>
            <button class="adm-modal-close" onclick="document.getElementById('cleanModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <form method="post" action="facility_cleaning.php">
                <input type="hidden" name="action" value="add_cleaning">
                <div class="adm-form-group">
                    <label>Task Title / Brief</label>
                    <input type="text" name="title" class="adm-search-input" required placeholder="e.g. Sanitization of Waiting Area">
                </div>
                <div style="display:flex;gap:1rem;">
                    <div class="adm-form-group" style="flex:1;">
                        <label>Location / Ward</label>
                        <input type="text" name="location" class="adm-search-input" required placeholder="Main Lobby">
                    </div>
                    <div class="adm-form-group" style="flex:1;">
                        <label>Task Type</label>
                        <select name="type" class="adm-search-input">
                            <option value="routine" selected>Routine Clean</option>
                            <option value="deep_clean">Deep Clean / Sanitization</option>
                            <option value="biohazard">Biohazard / Spill Response</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label>Assign to Cleaner</label>
                    <select name="staff_id" class="adm-search-input" required>
                        <option value="">-- Choose Staff --</option>
                        <?php foreach ($cleaners as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php
endforeach; ?>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label>Special Instructions</label>
                    <textarea name="notes" class="adm-search-input" rows="2" placeholder="Use strong bleach..."></textarea>
                </div>
                <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;">Dispatch Task</button>
            </form>
        </div>
    </div>
</div>
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
</script>
</body>
</html>