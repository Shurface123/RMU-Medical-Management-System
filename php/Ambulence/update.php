<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
if ($id <= 0) {
    header('Location: ambulence.php?error=' . urlencode('Invalid ambulance ID'));
    exit;
}

// Ensure record exists
$query = mysqli_query($conn, "SELECT * FROM ambulances WHERE id='$id'");
$amb = mysqli_fetch_assoc($query);
if (!$amb) {
    header('Location: ambulence.php?error=' . urlencode('Ambulance record not found'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $status = trim($_POST['status'] ?? '');
    $vehicle_number = trim($_POST['vehicle_number'] ?? '');
    $driver_name = trim($_POST['driver_name'] ?? '');
    $driver_phone = trim($_POST['driver_phone'] ?? '');

    $sql = "UPDATE ambulances SET status=?, vehicle_number=?, driver_name=?, driver_phone=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $status, $vehicle_number, $driver_name, $driver_phone, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: ambulence.php?success=" . urlencode("Ambulance record updated successfully"));
        exit;
    } else {
        $error_message = "Database error occurred while updating the record.";
    }
    mysqli_stmt_close($stmt);
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$active_page = 'ambulance';
$page_title  = 'Update Ambulance Record';
include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-ambulance"></i> Update Ambulance Record</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Update Ambulance Record</h1>
                <p>Dashboard &rarr; Ambulance Management &rarr; Update Record</p>
            </div>
            <a href="ambulence.php" class="adm-btn adm-btn-ghost"><i class="fas fa-arrow-left"></i> Back to Fleet</a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="adm-alert adm-alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;">
            <div class="adm-card-header" style="background:var(--primary);color:#fff;">
                <h3 style="color:#fff;"><i class="fas fa-edit" style="color:#fff;"></i> Edit Ambulance Details</h3>
            </div>
            <div class="adm-card-body">
                <form action="" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">

                    <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                        <div class="adm-form-group" style="flex:1;min-width:250px;">
                            <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Ambulance ID</label>
                            <input type="text" class="adm-search-input" value="<?php echo htmlspecialchars($amb['ambulance_id']); ?>" disabled>
                        </div>

                        <div class="adm-form-group" style="flex:1;min-width:250px;">
                            <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Vehicle Number <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="vehicle_number" class="adm-search-input" value="<?php echo htmlspecialchars($amb['vehicle_number']); ?>" required>
                        </div>
                    </div>

                    <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                        <div class="adm-form-group" style="flex:1;min-width:250px;">
                            <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Driver Name</label>
                            <input type="text" name="driver_name" class="adm-search-input" value="<?php echo htmlspecialchars($amb['driver_name'] ?? ''); ?>">
                        </div>

                        <div class="adm-form-group" style="flex:1;min-width:250px;">
                            <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Driver Phone</label>
                            <input type="tel" name="driver_phone" class="adm-search-input" value="<?php echo htmlspecialchars($amb['driver_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom:2rem;max-width:300px;">
                        <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Ambulance Status <span style="color:var(--danger);">*</span></label>
                        <select name="status" class="adm-search-input" required>
                            <?php
                            $statuses = ['Available', 'On Duty', 'Maintenance', 'Out of Service'];
                            foreach ($statuses as $s) {
                              $selected = ($amb['status'] === $s) ? 'selected' : '';
                              echo "<option value=\"$s\" $selected>$s</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div style="display:flex;gap:1rem;">
                        <button type="submit" class="adm-btn adm-btn-primary" onclick="if(this.closest('form').checkValidity()) { this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Saving...'; this.style.pointerEvents='none'; this.closest('form').submit(); }"><i class="fas fa-save"></i> Save Changes</button>
                        <a href="ambulence.php" class="adm-btn adm-btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

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