<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'maintenance';
$page_title = 'Facility Maintenance';
include '../includes/_sidebar.php';

// Handle quick form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_maintenance') {
    $title = trim($_POST['title']);
    $priority = trim($_POST['priority']);
    $location = trim($_POST['location']);
    $desc = trim($_POST['description']);
    $reported = $_SESSION['user_name'] ?? 'Admin';

    $sql = "INSERT INTO maintenance_requests (reported_by, equipment_or_area, issue_description, location, priority, status, issue_category) VALUES (?, ?, ?, ?, ?, 'reported', 'other')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $reported, $title, $desc, $location, $priority);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: facility_maintenance.php?success=1");
    exit();
}

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

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-tools"></i> Facility Maintenance</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div>
                <h1>Maintenance & Repairs</h1>
                <p>Log and track facility repair requirements for the maintenance team.</p>
            </div>
            <button class="adm-btn adm-btn-primary" onclick="document.getElementById('maintModal').classList.add('active')">
                <i class="fas fa-plus"></i> Report Issue
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Maintenance request logged successfully. Sent to maintenance staff dashboard.</div>
        <?php
endif; ?>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-clipboard-list"></i> Active Work Orders</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Date</th><th>Issue / Location</th><th>Priority</th><th>Reported By</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($requests)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;">No maintenance requests found.</td></tr>
                        <?php
else:
    foreach ($requests as $req):
        $pc = $req['priority'] === 'high' || $req['priority'] === 'urgent' ? 'danger' : ($req['priority'] === 'medium' ? 'warning' : 'success');
        $sc = $req['status'] === 'completed' ? 'success' : ($req['status'] === 'in progress' || $req['status'] === 'assigned' ? 'info' : 'warning');
?>
                        <tr>
                            <td style="white-space:nowrap;"><?php echo date('d M Y, g:i A', strtotime($req['reported_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($req['equipment_or_area']); ?></strong>
                                <div style="font-size:.8rem;color:var(--text-muted);"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($req['location']); ?></div>
                                <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;"><?php echo htmlspecialchars(substr($req['issue_description'], 0, 80)); ?>...</div>
                            </td>
                            <td><span class="adm-badge adm-badge-<?php echo $pc; ?>"><?php echo strtoupper($req['priority']); ?></span></td>
                            <td><?php echo htmlspecialchars($req['reported_by'] ?? 'Admin'); ?></td>
                            <td><span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo ucfirst($req['status']); ?></span></td>
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

<div class="adm-modal" id="maintModal">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3><i class="fas fa-wrench"></i> Report Maintenance Issue</h3>
            <button class="adm-modal-close" onclick="document.getElementById('maintModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <form method="post" action="facility_maintenance.php">
                <input type="hidden" name="action" value="add_maintenance">
                <div class="adm-form-group">
                    <label>Issue Title</label>
                    <input type="text" name="title" class="adm-search-input" required placeholder="e.g. Broken AC in Ward 4">
                </div>
                <div style="display:flex;gap:1rem;">
                    <div class="adm-form-group" style="flex:1;">
                        <label>Location / Ward</label>
                        <input type="text" name="location" class="adm-search-input" required placeholder="Ward 4, Room 12">
                    </div>
                    <div class="adm-form-group" style="flex:1;">
                        <label>Priority</label>
                        <select name="priority" class="adm-search-input">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label>Details / Description</label>
                    <textarea name="description" class="adm-search-input" rows="3" required></textarea>
                </div>
                <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;">Submit Request</button>
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