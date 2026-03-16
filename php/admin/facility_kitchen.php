<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'kitchen';
$page_title = 'Dietary & Kitchen';
include '../includes/_sidebar.php';

// Quick form handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_diet') {
    $ward = trim($_POST['ward']);
    $type = trim($_POST['diet_type']);
    $notes = trim($_POST['notes']);
    $meal = trim($_POST['meal_time']);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $assigned = 0; // default, no specific staff assigned from admin side

    $sql = "INSERT INTO kitchen_tasks (assigned_to, meal_type, ward_department, dietary_requirements, quantity, notes, preparation_status, delivery_status) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending')";
    $stmt = mysqli_prepare($conn, $sql);
    $diet_json = json_encode(['type' => $type]);
    mysqli_stmt_bind_param($stmt, "isssis", $assigned, $meal, $ward, $diet_json, $qty, $notes);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: facility_kitchen.php?success=1");
    exit();
}

$orders = [];
$q = mysqli_query($conn, "SELECT * FROM kitchen_tasks ORDER BY FIELD(preparation_status,'pending','in preparation','ready','delivered'), created_at DESC LIMIT 50");
if ($q)
    while ($r = mysqli_fetch_assoc($q))
        $orders[] = $r;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-utensils"></i> Dietary & Kitchen</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div>
                <h1>Meal & Dietary Orders</h1>
                <p>Assign dietary requirements and meal prep tasks to the kitchen staff.</p>
            </div>
            <button class="adm-btn adm-btn-primary" onclick="document.getElementById('dietModal').classList.add('active')">
                <i class="fas fa-plus"></i> New Dietary Order
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Dietary order dispatched to kitchen.</div>
        <?php
endif; ?>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-concierge-bell"></i> Kitchen Ticket Queue</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Time Created</th><th>Patient / Location</th><th>Meal Time</th><th>Dietary Type</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($orders)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;">No active kitchen orders.</td></tr>
                        <?php
else:
    foreach ($orders as $o):
        $sc = $o['preparation_status'] === 'delivered' || $o['preparation_status'] === 'ready' ? 'success' : ($o['preparation_status'] === 'in preparation' ? 'warning' : 'info');
?>
                        <tr>
                            <td style="white-space:nowrap;"><?php echo date('d M Y, g:i A', strtotime($o['created_at'])); ?></td>
                            <td>
                                <strong>Qty: <?php echo htmlspecialchars($o['quantity']); ?></strong>
                                <div style="font-size:.8rem;color:var(--text-muted);"><i class="fas fa-bed"></i> <?php echo htmlspecialchars($o['ward_department']); ?></div>
                            </td>
                            <td><span class="adm-badge adm-badge-secondary"><?php echo strtoupper($o['meal_type']); ?></span></td>
                            <td>
                                <?php
        $diet = json_decode($o['dietary_requirements'], true);
        echo '<strong>' . ucfirst($diet['type'] ?? 'Standard') . '</strong>';
        if ($o['notes'])
            echo '<div style="font-size:.75rem;color:var(--danger);"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($o['notes']) . '</div>';
?>
                            </td>
                            <td>
                                <span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo ucfirst($o['preparation_status']); ?></span>
                                <?php if ($o['delivery_status'] === 'delivered'): ?>
                                    <span class="adm-badge adm-badge-success" style="margin-top:2px;">Delivered</span>
                                <?php
        endif; ?>
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

<div class="adm-modal" id="dietModal">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3><i class="fas fa-utensils"></i> Submit Kitchen Order</h3>
            <button class="adm-modal-close" onclick="document.getElementById('dietModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <form method="post" action="facility_kitchen.php">
                <input type="hidden" name="action" value="add_diet">
                <div style="display:flex;gap:1rem;">
                    <div class="adm-form-group" style="flex:1;">
                        <label>Ward / Room Number</label>
                        <input type="text" name="ward" class="adm-search-input" required placeholder="Ward 2">
                    </div>
                    <div class="adm-form-group" style="flex:1;">
                        <label>Quantity (Portions)</label>
                        <input type="number" name="quantity" class="adm-search-input" min="1" value="1" required>
                    </div>
                </div>
                <div style="display:flex;gap:1rem;">
                    <div class="adm-form-group" style="flex:1;">
                        <label>Diet Type</label>
                        <select name="diet_type" class="adm-search-input">
                            <option value="regular">Regular Diet</option>
                            <option value="diabetic">Diabetic / Low Sugar</option>
                            <option value="low_sodium">Low Sodium</option>
                            <option value="liquid">Liquid Only</option>
                            <option value="halal">Halal</option>
                            <option value="vegetarian">Vegetarian</option>
                        </select>
                    </div>
                    <div class="adm-form-group" style="flex:1;">
                        <label>Meal Time</label>
                        <select name="meal_time" class="adm-search-input">
                            <option value="breakfast">Breakfast</option>
                            <option value="lunch" selected>Lunch</option>
                            <option value="dinner">Dinner</option>
                            <option value="snack">Snack / Other</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label>Allergies / Special Notes</label>
                    <textarea name="notes" class="adm-search-input" rows="2" placeholder="Strictly no peanuts..."></textarea>
                </div>
                <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;">Create Order</button>
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